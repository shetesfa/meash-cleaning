// Smoke test for the Meash Cleaning homepage (F1, 2026-11-19).
//
// Opens "/" at phone size (390x844), waits for the `load` event and fails
// when the page is slow or any single main-thread task is long — the exact
// symptoms of the MutationObserver freeze (the tab never finished loading).
//
// Usage (local, with a server running):
//   php artisan serve --host=127.0.0.1 --port=8000
//   node scripts/smoke.mjs http://127.0.0.1:8000/
//
// In CI the workflow starts the server and runs:
//   node scripts/smoke.mjs http://127.0.0.1:8000/
//
// Requires the `playwright` devDependency and a Chromium build
// (`npx playwright install chromium`).
import { chromium } from 'playwright';

const url = process.argv[2] || process.env.SMOKE_URL || 'http://127.0.0.1:8000/';
const LOAD_BUDGET_MS = 3000;
const LONG_TASK_BUDGET_MS = 300;

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 390, height: 844 } });

// Record long tasks (any single main-thread task over 50ms) from the very
// start of the page, so tasks during initial load are captured too.
await page.addInitScript(() => {
  window.__longTasks = [];
  try {
    new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) window.__longTasks.push(entry.duration);
    }).observe({ entryTypes: ['longtask'] });
  } catch (e) {
    // PerformanceObserver/longtask unsupported — leave the list empty.
  }
});

const startedAt = Date.now();
let loadMs = null;
try {
  await page.goto(url, { waitUntil: 'load', timeout: 30000 });
  loadMs = Date.now() - startedAt;
} catch (e) {
  // The load event never fired — the freeze symptom itself.
  console.error(`FAIL: page never reached the load event within 30000ms (${e.message.split('\n')[0]})`);
  await browser.close();
  process.exit(1);
}

const longTasks = await page.evaluate(() => window.__longTasks || []);
const worstLongTask = longTasks.length ? Math.max(...longTasks) : 0;

console.log(`url: ${url}`);
console.log(`load: ${loadMs}ms (budget ${LOAD_BUDGET_MS}ms)`);
console.log(`long tasks: ${longTasks.length}, worst: ${worstLongTask.toFixed(0)}ms (budget ${LONG_TASK_BUDGET_MS}ms)`);

await browser.close();

let failed = false;
if (loadMs > LOAD_BUDGET_MS) {
  console.error(`FAIL: load took ${loadMs}ms, over the ${LOAD_BUDGET_MS}ms budget`);
  failed = true;
}
if (worstLongTask > LONG_TASK_BUDGET_MS) {
  console.error(`FAIL: longest main-thread task was ${worstLongTask.toFixed(0)}ms, over the ${LONG_TASK_BUDGET_MS}ms budget`);
  failed = true;
}
if (!failed) console.log('SMOKE OK');
process.exit(failed ? 1 : 0);
