// Regression check for the "Our work" gallery strip (2026-11-19).
//
// Background: the gallery was a one-photo-at-a-time slider on phones
// and a masonry grid on desktop. Tesfa asked for a side-scrolling
// image strip on both. This check proves the strip really scrolls
// sideways, all photos sit in one row, and tapping a photo opens the
// lightbox — things a PHP test cannot see (it only reads markup).
//
// It is READ-ONLY: it never submits a booking and writes nothing to
// the DB. It does click one gallery photo (lightbox open/close only).
//
// Usage (local, with a server running):
//   php artisan serve --host=127.0.0.1 --port=8000
//   node scripts/gal-strip-check.mjs http://127.0.0.1:8000/
//
// Requires the `playwright` devDependency and a Chromium build
// (`npx playwright install chromium`).

import { chromium } from 'playwright';

const url = process.argv[2] || 'http://127.0.0.1:8000/';
let failures = 0;
function check(name, ok, extra = '') {
  console.log(`${ok ? 'OK  ' : 'FAIL'} ${name}${extra ? ' => ' + extra : ''}`);
  if (!ok) failures++;
}

const browser = await chromium.launch();

for (const viewport of [{ width: 390, height: 844 }, { width: 1280, height: 900 }]) {
  const tag = viewport.width === 390 ? 'phone 390px' : 'desktop 1280px';
  const page = await browser.newPage({ viewport });
  await page.goto(url, { waitUntil: 'load', timeout: 30000 });
  await page.waitForTimeout(800);

  const strip = await page.evaluate(() => {
    const stage = document.getElementById('ms-gal-stage');
    if (!stage) return null;
    const slides = Array.from(stage.querySelectorAll('.ms-gal-slide'));
    const boxes = slides.map((s) => {
      const r = s.getBoundingClientRect();
      return { x: Math.round(r.x), w: Math.round(r.width) };
    });
    const cs = getComputedStyle(stage);
    return {
      slides: slides.length,
      scrollable: stage.scrollWidth > stage.clientWidth,
      scrollWidth: stage.scrollWidth,
      clientWidth: stage.clientWidth,
      display: cs.display,
      overflowX: cs.overflowX,
      snap: cs.scrollSnapType,
      boxes,
      imagesLoaded: slides.filter((s) => {
        const img = s.querySelector('img');
        return img && img.complete && img.naturalWidth > 0;
      }).length,
    };
  });
  check(`[${tag}] gallery stage exists`, !!strip);
  if (strip) {
    check(`[${tag}] ${strip.slides} slides in the strip`, strip.slides > 0, `${strip.slides} slides`);
    check(`[${tag}] strip is horizontally scrollable`, strip.scrollable, `scrollWidth ${strip.scrollWidth} > clientWidth ${strip.clientWidth}`);
    check(`[${tag}] stage is a flex row with snap`, strip.display === 'flex' && strip.snap.startsWith('x mandatory'), `${strip.display} / ${strip.snap}`);
    const increasing = strip.boxes.every((b, i) => i === 0 || b.x > strip.boxes[i - 1].x);
    check(`[${tag}] slides sit side by side (x increases)`, increasing);

    // Images are lazy-loaded (all but the first), so swipe through the
    // strip slide by slide — like a real user — then count the ones
    // that rendered. (A single jump to the end does not give the
    // single-threaded local dev server time to serve each image.)
    for (let i = 0; i < strip.slides; i++) {
      await page.evaluate((idx) => {
        document.querySelectorAll('#ms-gal-stage .ms-gal-slide')[idx]
          .scrollIntoView({ inline: 'start', block: 'nearest' });
      }, i);
      await page.waitForTimeout(400);
    }
    const loaded = await page.evaluate(() => {
      const slides = Array.from(document.querySelectorAll('#ms-gal-stage .ms-gal-slide'));
      return {
        total: slides.length,
        rendered: slides.filter((s) => {
          const img = s.querySelector('img');
          return img && img.complete && img.naturalWidth > 0;
        }).length,
      };
    });
    check(`[${tag}] all slide images render after swiping through`, loaded.rendered === loaded.total, `${loaded.rendered}/${loaded.total}`);

    // Scroll the strip and confirm it moves.
    const moved = await page.evaluate(() => {
      const stage = document.getElementById('ms-gal-stage');
      stage.scrollLeft = 400;
      return stage.scrollLeft;
    });
    await page.waitForTimeout(400);
    const after = await page.evaluate(() => document.getElementById('ms-gal-stage').scrollLeft);
    check(`[${tag}] strip scrolls (scrollLeft moves)`, after > 0, `scrollLeft ${after}`);

    // Tap a slide -> lightbox opens; close it again.
    await page.evaluate(() => { document.getElementById('ms-gal-stage').scrollLeft = 0; });
    await page.waitForTimeout(300);
    await page.click('.ms-gal-slide >> nth=1');
    await page.waitForTimeout(400);
    const lbOpen = await page.evaluate(() => document.getElementById('ms-lightbox').classList.contains('open'));
    check(`[${tag}] tapping a photo opens the lightbox`, lbOpen);
    await page.click('#ms-lb-close');
    await page.waitForTimeout(300);
    const lbClosed = await page.evaluate(() => !document.getElementById('ms-lightbox').classList.contains('open'));
    check(`[${tag}] lightbox closes again`, lbClosed);
  }
  await page.close();
}

await browser.close();
if (failures) {
  console.log(`GAL-STRIP CHECK FAILED (${failures} failures)`);
  process.exit(1);
}
console.log('GAL-STRIP CHECK OK');
