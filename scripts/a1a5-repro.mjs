// Reproduction for A2 (direct cleaner assign) + A5 (1-to-1 send) silent failures.
// Local scratch server only: http://127.0.0.1:8001 with /tmp/repro.sqlite.
// Usage: node /tmp/a1a5-repro.mjs
import { chromium } from 'playwright';

const BASE = 'http://127.0.0.1:8001';

const loginRes = await fetch(BASE + '/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ login: 'amha', password: 'ReproOwner123!x' }),
});
const login = await loginRes.json();
if (!login.token) { console.log('LOGIN FAILED', JSON.stringify(login).slice(0, 300)); process.exit(1); }
console.log('logged in as owner, token ok');

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

const dialogs = [];
const consoleErrors = [];
const pageErrors = [];
const apiCalls = [];
page.on('dialog', async (d) => { dialogs.push({ type: d.type(), message: d.message() }); await d.accept(); });
page.on('console', (m) => { if (m.type() === 'error') consoleErrors.push(m.text().slice(0, 300)); });
page.on('pageerror', (e) => pageErrors.push(String(e).slice(0, 300)));
page.on('response', async (res) => {
  const url = res.url();
  if (url.includes('/api/')) {
    let body = '';
    try { body = (await res.text()).slice(0, 400); } catch {}
    apiCalls.push({ method: res.request().method(), url: url.replace(BASE, ''), status: res.status(), body });
  }
});

await page.addInitScript(({ token, user }) => {
  localStorage.setItem('meash_token', token);
  localStorage.setItem('meash_user', JSON.stringify(user));
}, { token: login.token, user: login.user });

await page.goto(BASE + '/app', { waitUntil: 'domcontentloaded', timeout: 30000 });
await page.waitForTimeout(4000);
console.log('ALL api calls so far:', JSON.stringify(apiCalls.map(c => c.method + ' ' + c.url + ' -> ' + c.status)));
await page.screenshot({ path: '/tmp/repro-dash.png' });
const navBtns = await page.evaluate(() => Array.from(document.querySelectorAll('#nav-items-container button')).map(b => b.textContent.trim().slice(0, 30)));
console.log('nav buttons:', JSON.stringify(navBtns));
await page.evaluate(() => { const b = Array.from(document.querySelectorAll('#nav-items-container button')).find(x => x.textContent.includes('ሁሉም ትዕዛዞች')); if (b) b.click(); });
await page.waitForTimeout(3000);

// ---- A2: direct cleaner assignment ----
console.log('\n=== A2: direct cleaner assignment ===');

const orderVisible = await page.getByText('MEASH-000001').count();
console.log('order row visible:', orderVisible);
const assignBtn = page.locator('button[title="የፅዳት ሰራተኛ መድብ"]').first();
console.log('assign buttons found:', await assignBtn.count());
await assignBtn.click({ timeout: 5000 }).catch((e) => console.log('assign btn click failed:', String(e).slice(0, 150)));
await page.waitForTimeout(2500);
const workerSelect = page.locator('#assign-worker-select');
console.log('worker select visible:', await workerSelect.count());
if (await workerSelect.count()) {
  const opts = await workerSelect.locator('option').allTextContents();
  console.log('worker options:', JSON.stringify(opts));
  await workerSelect.selectOption({ value: '2' });
  const before = dialogs.length;
  await page.getByText('የተመረጠውን ሰራተኛ መድብ').click({ timeout: 5000 });
  await page.waitForTimeout(3000);
  console.log('dialogs after submit:', JSON.stringify(dialogs.slice(before)));
}
const assignCalls = apiCalls.filter((c) => c.url.includes('assign-team'));
console.log('assign-team API calls:', JSON.stringify(assignCalls, null, 1));

// ---- A5: 1-to-1 send from customers ----
console.log('\n=== A5: 1-to-1 send ===');
await page.evaluate(() => { const b = Array.from(document.querySelectorAll('#nav-items-container button')).find(x => x.textContent.includes('ደንበኞች')); if (b) b.click(); });
await page.waitForTimeout(3000);
const sendBtn = page.locator('button:has-text("Send Message")').first();
console.log('Send Message buttons found:', await sendBtn.count());
const box = await sendBtn.boundingBox().catch(() => null);
console.log('send btn box:', JSON.stringify(box));
await page.screenshot({ path: '/tmp/repro-customers.png' });
await sendBtn.scrollIntoViewIfNeeded({ timeout: 5000 }).catch((e) => console.log('scroll failed:', String(e).slice(0, 120)));
const probe = await page.evaluate(() => {
  const btn = document.querySelector('button[onclick*="openDirectCustomerMessageModal"]');
  if (!btn) return { found: false };
  const r = btn.getBoundingClientRect();
  const el = document.elementFromPoint(r.x + r.width / 2, r.y + r.height / 2);
  return { found: true, onclick: (btn.getAttribute('onclick') || '').slice(0, 120), atPoint: el ? (el.tagName + '.' + (el.className || '').toString().slice(0, 60)) : 'none', isButton: el === btn, visible: !!(r.width && r.height) };
});
console.log('probe:', JSON.stringify(probe));
await page.evaluate(() => { const btn = document.querySelector('button[onclick*="openDirectCustomerMessageModal"]'); if (btn) btn.click(); });
await page.waitForTimeout(2000);
console.log('after JS click, textarea visible:', await page.locator('#direct-care-msg').count());
console.log('modal container html len:', await page.evaluate(() => (document.getElementById('generic-modal-container')?.innerHTML || '').length));
await page.waitForTimeout(2000);
const msgBox = page.locator('#direct-care-msg');
console.log('message textarea visible:', await msgBox.count());
if (await msgBox.count()) {
  const before = dialogs.length;
  await page.locator('#btn-send-care-msg').click({ timeout: 5000 });
  await page.waitForTimeout(3000);
  console.log('dialogs after send:', JSON.stringify(dialogs.slice(before)));
}
const sendCalls = apiCalls.filter((c) => c.url.includes('send-'));
console.log('send API calls:', JSON.stringify(sendCalls, null, 1));

console.log('\n=== console errors ===', JSON.stringify(consoleErrors.slice(0, 10)));
console.log('=== page errors ===', JSON.stringify(pageErrors.slice(0, 10)));
console.log('=== all dialogs ===', JSON.stringify(dialogs));
await browser.close();
