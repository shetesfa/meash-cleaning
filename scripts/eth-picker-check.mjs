// Regression check for the Ethiopian date picker (booking wizard, step 3).
//
// Background (2026-11-19): a live browser test reported that choosing day 11
// in the Ethiopian tab moved the date from 10 to 12 Hidar — +2 days per
// selection. Investigation found NO defect in the picker code: the converter
// (EC.toGreg / EC.toEth, epoch fixed in batch 11) is correct against known
// anchors, and every single-step interaction (mouse choice, one ArrowDown)
// moves exactly one day. Two selection steps per attempt reproduces the
// reported +2 exactly, so the report matches a double-step by the test
// driver, not the page code. This check locks in the correct behaviour so a
// real off-by-one (or a double-increment) can never slip back in.
//
// It is READ-ONLY: it never submits a booking and writes nothing to the DB.
//
// Usage (local, with a server running):
//   php artisan serve --host=127.0.0.1 --port=8000
//   node scripts/eth-picker-check.mjs http://127.0.0.1:8000/
//
// Requires the `playwright` devDependency and a Chromium build
// (`npx playwright install chromium`).
import { chromium } from 'playwright';

const url = process.argv[2] || process.env.ETH_PICKER_URL || 'http://127.0.0.1:8000/';

let failures = 0;
function check(label, got, exp) {
  const ok = got === exp;
  if (!ok) failures++;
  console.log(`${ok ? 'OK   ' : 'FAIL '} ${label} => ${got}${ok ? '' : ` (expected ${exp})`}`);
}

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 390, height: 844 } });
await page.goto(url, { waitUntil: 'load', timeout: 30000 });

const state = () => page.evaluate(() => ({
  ethDay: document.getElementById('eth-day')?.value,
  ethMonth: document.getElementById('eth-month')?.value,
  ethYear: document.getElementById('eth-year')?.value,
  bookDate: document.getElementById('book-date')?.value,
  gregDisplay: document.getElementById('greg-display')?.textContent?.trim(),
  ethDayCount: document.querySelectorAll('#eth-day').length,
}));

// 1. Converter anchors (known dates; guards the epoch constants).
const anchors = await page.evaluate(() => ({
  toGreg11Hidar: EC.toGreg(2019, 3, 11),
  toGreg10Hidar: EC.toGreg(2019, 3, 10),
  toGreg1Meskerem: EC.toGreg(2019, 1, 1),
  toEthNov20: (() => { const e = EC.toEth('2026-11-20'); return `${e.year}-${e.month}-${e.day}`; })(),
}));
check('EC.toGreg(2019,3,11) [11 Hidar 2019]', anchors.toGreg11Hidar, '2026-11-20');
check('EC.toGreg(2019,3,10) [10 Hidar 2019]', anchors.toGreg10Hidar, '2026-11-19');
check('EC.toGreg(2019,1,1) [1 Meskerem 2019]', anchors.toGreg1Meskerem, '2026-09-11');
check('EC.toEth(2026-11-20)', anchors.toEthNov20, '2019-3-11');

// 2. Picker: exactly one #eth-day, and one selection step moves exactly one day.
await page.evaluate(() => window.showWizardStep(3));
const initial = await state();
check('single #eth-day element', String(initial.ethDayCount), '1');

// Day 10 -> day 11 by a single mouse-like choice must advance book-date by 1 day.
await page.selectOption('#eth-day', '10');
const d10 = await state();
await page.selectOption('#eth-day', '11');
const d11 = await state();
check('choosing day 11 sets eth-day', d11.ethDay, '11');
check('choosing day 11 sets book-date', d11.bookDate, anchors.toGreg11Hidar);
const dayDiff = (new Date(d11.bookDate) - new Date(d10.bookDate)) / 86400000;
check('day 10 -> day 11 advances book-date by exactly 1 day', String(dayDiff), '1');

// 3. One keyboard ArrowDown moves exactly one day (10 -> 11).
await page.selectOption('#eth-day', '10');
await page.focus('#eth-day');
await page.keyboard.press('ArrowDown');
await page.keyboard.press('Enter');
const kb = await state();
check('one ArrowDown moves eth-day 10 -> 11', kb.ethDay, '11');
check('one ArrowDown sets book-date', kb.bookDate, anchors.toGreg11Hidar);

await browser.close();
console.log(failures === 0 ? 'ETH-PICKER CHECK OK' : `${failures} FAILURE(S)`);
process.exit(failures === 0 ? 0 : 1);
