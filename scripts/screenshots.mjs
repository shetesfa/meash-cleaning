// Section screenshots for the Meash Cleaning homepage (F2/F7).
//
// Captures the hero, trust bar, services tabs and booking form at phone
// (390x844) and desktop (1440x900) against a running server — real
// rendering in Chromium, which is what the cloud browser and thum.io
// could not do reliably for this page.
//
// Usage (local, with a server running):
//   php artisan serve --host=127.0.0.1 --port=8000
//   node scripts/screenshots.mjs [url] [out-dir]
//
// Defaults: http://127.0.0.1:8000/ and ./screenshots
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const url = process.argv[2] || process.env.SMOKE_URL || 'http://127.0.0.1:8000/';
const outDir = process.argv[3] || process.env.SHOT_DIR || './screenshots';
fs.mkdirSync(outDir, { recursive: true });

const sections = [
  ['hero', '#hero'],
  ['trust', '#trust'],
  ['services', '#services'],
  ['process', '#how'],
  ['gallery', '#gallery'],
  ['booking', '#book'],
  ['footer', 'footer'],
];

const viewports = [
  ['mobile-390', { width: 390, height: 844 }],
  ['desktop-1440', { width: 1440, height: 900 }],
];

const browser = await chromium.launch();
for (const [label, viewport] of viewports) {
  const page = await browser.newPage({ viewport });
  await page.goto(url, { waitUntil: 'load', timeout: 30000 });
  // Let the fade-up safety net (1.5s), slider init and lazy images settle.
  await page.waitForTimeout(2500);
  await page.screenshot({ path: path.join(outDir, `${label}-full.png`), fullPage: true });
  for (const [name, selector] of sections) {
    const el = page.locator(selector).first();
    await el.scrollIntoViewIfNeeded().catch(() => {});
    await page.waitForTimeout(400);
    await el.screenshot({ path: path.join(outDir, `${label}-${name}.png`) }).catch((e) => {
      console.error(`  !! could not capture ${label}-${name}: ${e.message.split('\n')[0]}`);
    });
  }
  console.log(`done ${label}`);
  await page.close();
}
await browser.close();
console.log(`screenshots in ${outDir}`);
