// B1/B2/B3 UI regression check — LOCAL scratch server only, never the live site.
// Usage: start a scratch server first, e.g.
//   DB_CONNECTION=sqlite DB_DATABASE=/tmp/repro.sqlite CACHE_STORE=file SESSION_DRIVER=file \
//     MEASH_OWNER_PASSWORD='ReproOwner123!x' php artisan migrate --force --seed
//   DB_CONNECTION=sqlite DB_DATABASE=/tmp/repro.sqlite CACHE_STORE=file SESSION_DRIVER=file \
//     php artisan serve --host=127.0.0.1 --port=8001
// then: node scripts/b1b2-ui-check.mjs [baseUrl]
// Env: MEASH_CHECK_OWNER_LOGIN (default amha), MEASH_CHECK_OWNER_PASSWORD
//      (default ReproOwner123!x — scratch only), MEASH_CHECK_DB (default
//      /tmp/repro.sqlite) for best-effort cleanup of the TEST- rows it creates.
// IMPORTANT: no dialog handler is registered anywhere in this check — that is
// the point. B1 failed live because a native confirm() is auto-dismissed by
// automated browsers (0 /complete requests fired); B2 failed live because a
// native alert() success message is auto-dismissed (modal just closed, no
// visible feedback). If either native dialog comes back, the assertions below
// fail: no request fires, no toast appears.
import { chromium } from 'playwright';
import { execSync } from 'child_process';

const BASE = process.argv[2] || 'http://127.0.0.1:8001';
const OWNER_LOGIN = process.env.MEASH_CHECK_OWNER_LOGIN || 'amha';
const OWNER_PASSWORD = process.env.MEASH_CHECK_OWNER_PASSWORD || 'ReproOwner123!x';
const DB_PATH = process.env.MEASH_CHECK_DB || '/tmp/repro.sqlite';

let passed = 0;
let failed = 0;
function check(name, ok, extra = '') {
  if (ok) { passed++; console.log(`PASS ${name}`); }
  else { failed++; console.log(`FAIL ${name} ${extra}`); }
}

const api = async (path, token, method = 'GET', body) => {
  const res = await fetch(BASE + path, {
    method,
    headers: { Authorization: `Bearer ${token}`, Accept: 'application/json', 'Content-Type': 'application/json' },
    body: body ? JSON.stringify(body) : undefined,
  });
  const json = await res.json().catch(() => ({}));
  return { status: res.status, json };
};

const loginRes = await fetch(BASE + '/api/auth/login', {
  method: 'POST', headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ login: OWNER_LOGIN, password: OWNER_PASSWORD }),
});
const login = await loginRes.json();
if (!login.token) { console.log('FAIL owner login', JSON.stringify(login).slice(0, 200)); process.exit(1); }
const token = login.token;
console.log('logged in as owner');

// ---- setup via API (all TEST- rows, cleaned up at the end) ----
const today = new Date().toISOString().slice(0, 10);
const cleaner = await api('/api/employees', token, 'POST', { name: 'TEST-B1Cleaner', username: 'test-b1cleaner', phone: '0900000098', role: 'cleaner', password: 'TestClean12!x' });
check('setup cleaner created', cleaner.status === 201, `status=${cleaner.status}`);
const cleanerId = cleaner.json.employee?.id;
const reception = await api('/api/employees', token, 'POST', { name: 'TEST-B1Reception', username: 'test-b1reception', phone: '0900000097', role: 'reception', password: 'TestRecep12!x' });
check('setup reception created', reception.status === 201, `status=${reception.status}`);
const receptionId = reception.json.employee?.id;
const services = await api('/api/public/services', token);
const serviceId = services.json?.data?.[0]?.id || services.json?.[0]?.id || 1;
async function makeOrder(phone, name) {
  const r = await api('/api/orders', token, 'POST', {
    customer_name: name, customer_phone: phone, address: 'Bole',
    appointment_date: today, appointment_time_slot: 'morning', source: 'phone',
    order_status: 'new', items: [{ service_id: serviceId, quantity: 1 }],
  });
  return r.json.order?.id || r.json.data?.id;
}
const order1Id = await makeOrder('0911000005', 'TEST-B1B2');
check('setup order 1 created', !!order1Id);
const order2Id = await makeOrder('0911000006', 'TEST-B1B2b');
check('setup order 2 created', !!order2Id);
for (const oid of [order1Id, order2Id]) {
  const a = await api(`/api/orders/${oid}/assign-team`, token, 'POST', { worker_id: cleanerId });
  check(`setup order ${oid} assigned`, a.status === 200 && (a.json.order?.order_status === 'assigned'), `status=${a.status}`);
}
const orderStatus = async (oid) => { const j = (await api(`/api/orders/${oid}`, token)).json; return j.order_status ?? j.order?.order_status; };

// ---- browser: NO dialog handler (live conditions) ----
const browser = await chromium.launch({ headless: true });
try {
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
const apiCalls = [];
const consoleErrors = [];
page.on('console', (m) => { if (m.type() === 'error') consoleErrors.push(m.text().slice(0, 200)); });
page.on('response', async (res) => {
  const url = res.url();
  if (url.includes('/api/orders/') && url.includes('/complete')) apiCalls.push({ status: res.status(), url: url.replace(BASE, '') });
  if (url.includes('/api/customer-care/send-sms')) apiCalls.push({ status: res.status(), url: url.replace(BASE, ''), body: (await res.text().catch(() => '')).slice(0, 200) });
});
await page.addInitScript(({ t, u }) => {
  localStorage.setItem('meash_token', t);
  localStorage.setItem('meash_user', JSON.stringify({ username: u, role: 'owner', name: 'Owner' }));
}, { t: token, u: OWNER_LOGIN });
await page.goto(BASE + '/app', { waitUntil: 'domcontentloaded', timeout: 30000 });
await page.waitForTimeout(4000);
const authState = await page.evaluate(() => ({
  token: !!localStorage.getItem('meash_token'),
  loginHidden: document.getElementById('app-login-screen')?.classList.contains('hidden'),
  navCount: document.querySelectorAll('#nav-items-container button').length,
  consoleErrors: [],
}));
check('admin loaded authenticated', authState.token && authState.loginHidden && authState.navCount > 0, JSON.stringify(authState));

const toastText = () => page.evaluate(() => document.getElementById('meash-toast-box')?.innerText || '');
async function gotoTab(labelPart) {
  await page.evaluate((lp) => { const b = Array.from(document.querySelectorAll('#nav-items-container button')).find((x) => x.textContent.includes(lp)); if (b) b.click(); }, labelPart);
  await page.waitForTimeout(3000);
}

// ---------- B1: row complete button, two-tap ----------
await gotoTab('ሁሉም ትዕዛዞች');
try {
  await page.locator(`#order-row-${order1Id}`).waitFor({ timeout: 15000 });
} catch (e) {
  const diag = await page.evaluate(() => ({
    navButtons: Array.from(document.querySelectorAll('#nav-items-container button')).map((b) => b.textContent.trim().slice(0, 20)),
    rows: Array.from(document.querySelectorAll('tr[id^="order-row-"]')).map((r) => r.id),
    viewport: (document.getElementById('app-viewport')?.innerText || '').slice(0, 200),
    bodyHasLogin: document.body.innerText.includes('ይግቡ') || document.body.innerText.includes('Login'),
  }));
  console.log('B1 DIAG order1Id=' + order1Id + ' ' + JSON.stringify(diag));
  throw e;
}
const completeCallsBefore = apiCalls.filter((c) => c.url.includes('/complete')).length;
const rowBtn = page.locator(`#order-row-${order1Id} button[title*="Mark complete"]`).first();
check('B1 row complete button present', (await rowBtn.count()) === 1);
await rowBtn.click({ timeout: 5000 });
await page.waitForTimeout(800);
const armedLabel = await rowBtn.innerText().catch(() => '');
check('B1 first tap arms button (confirm text, no request yet)', armedLabel.includes('ይፈልጋሉ?') && apiCalls.filter((c) => c.url.includes('/complete')).length === completeCallsBefore, `label=${armedLabel.slice(0, 40)}`);
await rowBtn.click({ timeout: 5000 });
await page.waitForTimeout(2500);
check('B1 second tap fires /complete 200', apiCalls.some((c) => c.url.includes('/complete') && c.status === 200));
check('B1 toast visible after complete', (await toastText()).includes('ተጠናቀቀ'));
check('B1 order 1 status completed', (await orderStatus(order1Id)) === 'completed', `status=${await orderStatus(order1Id)}`);

// ---------- B1: modal complete button, two-tap ----------
await gotoTab('ሁሉም ትዕዛዞች');
await page.locator(`#order-row-${order2Id}`).waitFor({ timeout: 15000 });
const detailsBtn = page.locator(`#order-row-${order2Id} button:has-text("ዝርዝር")`).first();
await detailsBtn.click({ timeout: 5000 });
await page.waitForTimeout(2000);
const modalBtn = page.locator('button:has-text("ተጠናቋል (Complete)")').first();
check('B1 modal complete button present', (await modalBtn.count()) === 1);
await modalBtn.click({ timeout: 5000 });
await page.waitForTimeout(800);
// After arming, the button's label becomes the confirm question, so the
// original text locator no longer matches it — re-locate by the armed label.
const armedModalBtn = page.locator('button:has-text("ይፈልጋሉ?")').first();
check('B1 modal first tap arms button', (await armedModalBtn.count()) === 1);
await armedModalBtn.click({ timeout: 5000 });
await page.waitForTimeout(2500);
check('B1 modal second tap completes order 2', (await orderStatus(order2Id)) === 'completed', `status=${await orderStatus(order2Id)}`);
check('B1 toast visible after modal complete', (await toastText()).includes('ተጠናቀቀ'));

// ---------- B2: 1-to-1 SMS send from Campaigns ----------
await gotoTab('ማስታወቂያ');
await page.locator('tr:has-text("0911000005")').first().waitFor({ timeout: 15000 });
const msgBtn = page.locator('tr:has-text("0911000005") button[title="1-ለ-1 መልእክት ላክ"]').first();
check('B2 1-to-1 button present for test customer', (await msgBtn.count()) === 1);
// The B1 success toast (bottom-right, 6s) can cover the button — wait it out.
await page.waitForTimeout(6500);
await msgBtn.scrollIntoViewIfNeeded();
await msgBtn.click({ timeout: 15000 });
await page.waitForTimeout(2000);
check('B2 message modal opened', (await page.locator('#direct-care-msg').count()) === 1);
await page.locator('input[name="direct-msg-channel"][value="sms"]').check({ force: true }).catch(() => {});
const sendCallsBefore = apiCalls.filter((c) => c.url.includes('send-sms')).length;
await page.locator('#btn-send-care-msg').click({ timeout: 5000 });
await page.waitForTimeout(3000);
const sendCall = apiCalls.find((c) => c.url.includes('send-sms'));
check('B2 /send-sms returned 200 success', !!sendCall && sendCall.status === 200 && sendCall.body.includes('"success":true'), JSON.stringify(sendCall || {}).slice(0, 200));
check('B2 toast visible after send (persistent, not an alert)', (await toastText()).includes('መዝገብ'), `toast=${(await toastText()).slice(0, 120)}`);
check('B2 modal closed after send', (await page.locator('#direct-care-msg').count()) === 0);
check('B2 no console errors', consoleErrors.length === 0, JSON.stringify(consoleErrors.slice(0, 3)));

// ---------- B3: authenticated reception dashboard ----------
const dashOwner = await page.evaluate(async () => {
  const t = localStorage.getItem('meash_token');
  const r = await fetch('/api/dashboard/reception', { headers: { Authorization: 'Bearer ' + t, Accept: 'application/json' } });
  const j = await r.json().catch(() => ({}));
  return { status: r.status, keys: Object.keys(j) };
});
check('B3 dashboard 200 as owner', dashOwner.status === 200 && dashOwner.keys.includes('website_messages'), JSON.stringify(dashOwner));
const recLogin = await fetch(BASE + '/api/auth/login', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ login: 'test-b1reception', password: 'TestRecep12!x' }) });
const recToken = (await recLogin.json()).token;
const dashRec = await page.evaluate(async (t) => {
  const r = await fetch('/api/dashboard/reception', { headers: { Authorization: 'Bearer ' + t, Accept: 'application/json' } });
  const j = await r.json().catch(() => ({}));
  return { status: r.status, keys: Object.keys(j) };
}, recToken);
check('B3 dashboard 200 as reception', dashRec.status === 200 && dashRec.keys.includes('website_messages'), JSON.stringify(dashRec));
} catch (e) {
  failed++;
  console.log('FAIL check aborted by exception:', String(e).slice(0, 300));
} finally {
  await browser.close().catch(() => {});
}

// ---- best-effort cleanup of TEST- rows (scratch DB only) ----
try {
  const ids = [order1Id, order2Id].filter(Boolean).join(',');
  const stmts = [
    `DELETE FROM followups WHERE order_id IN (${ids})`,
    `DELETE FROM appointments WHERE order_id IN (${ids})`,
    `DELETE FROM order_items WHERE order_id IN (${ids})`,
    `DELETE FROM orders WHERE id IN (${ids})`,
    `DELETE FROM customers WHERE phone IN ('0911000005','0911000006')`,
    `DELETE FROM telegram_users WHERE phone_number IN ('0911000005','0911000006')`,
    `DELETE FROM team_members WHERE user_id = ${cleanerId}`,
    `DELETE FROM cleaning_teams WHERE team_leader_id = ${cleanerId}`,
    `DELETE FROM users WHERE id IN (${cleanerId},${receptionId})`,
    `DELETE FROM audit_logs WHERE new_values LIKE '%0911000005%' OR new_values LIKE '%0911000006%' OR old_values LIKE '%0911000005%'`,
  ];
  for (const s of stmts) { try { execSync(`sqlite3 ${DB_PATH} "${s}"`, { stdio: 'ignore' }); } catch {} }
  console.log('cleanup attempted (scratch DB)');
} catch (e) { console.log('cleanup skipped:', String(e).slice(0, 120)); }

console.log(`\nB1B2 CHECK: ${passed} passed, ${failed} failed`);
process.exit(failed ? 1 : 0);
