// F7 final-check metrics for the Meash Cleaning homepage.
//
// Measures, per viewport (phone 390x844, desktop 1440x900) against a
// running server: load time, LCP, CLS, TBT, total KB, request count,
// DB query count is NOT measured here (see /tmp/measure-homepage.php
// and tests/Feature/HomepagePerformanceTest.php — warm hit is 0
// queries with a file/array cache store, 3 cache lookups with the
// database store). Also checks: no horizontal scroll, no raw Blade
// text on the page, hero CTA + booking button actually visible with
// a non-white background.
//
// Usage (local, with a server running):
//   php artisan serve --host=127.0.0.1 --port=8000
//   node scripts/metrics.mjs [url]
//
// Exit 1 when the load event never fires, the page scrolls
// horizontally, raw Blade text is visible, or a primary button is
// invisible. LCP/CLS/TBT/KB are reported, not gated (no local
// throttling profile matches a real 4G phone).
import { chromium } from 'playwright';

const url = process.argv[2] || process.env.SMOKE_URL || 'http://127.0.0.1:8000/';
const viewports = [
  ['mobile-390', { width: 390, height: 844 }],
  ['desktop-1440', { width: 1440, height: 900 }],
];

const browser = await chromium.launch();
let failed = false;

for (const [label, viewport] of viewports) {
  const page = await browser.newPage({ viewport });
  await page.addInitScript(() => {
    window.__longTasks = [];
    window.__lcp = 0;
    window.__cls = 0;
    try {
      new PerformanceObserver((l) => {
        for (const e of l.getEntries()) window.__longTasks.push(e.duration);
      }).observe({ entryTypes: ['longtask'] });
      new PerformanceObserver((l) => {
        for (const e of l.getEntries()) window.__lcp = Math.max(window.__lcp, e.renderTime || e.startTime);
      }).observe({ entryTypes: ['largest-contentful-paint'] });
      new PerformanceObserver((l) => {
        for (const e of l.getEntries()) if (!e.hadRecentInput) window.__cls += e.value;
      }).observe({ entryTypes: ['layout-shift'] });
    } catch (e) { /* observers unsupported — numbers stay 0 */ }
  });

  const startedAt = Date.now();
  try {
    await page.goto(url, { waitUntil: 'load', timeout: 30000 });
  } catch (e) {
    console.error(`FAIL ${label}: page never reached load (${e.message.split('\n')[0]})`);
    failed = true;
    await page.close();
    continue;
  }
  const loadMs = Date.now() - startedAt;
  // Let autoplay, count-up and lazy images settle before measuring.
  await page.waitForTimeout(3000);

  const m = await page.evaluate(() => {
    const nav = performance.getEntriesByType('navigation')[0] || {};
    const res = performance.getEntriesByType('resource');
    const totalKB = Math.round((res.reduce((s, r) => s + (r.transferSize || 0), 0) + (nav.transferSize || 0)) / 1024);
    const longTasks = window.__longTasks || [];
    const tbt = Math.round(longTasks.reduce((s, d) => s + Math.max(0, d - 50), 0));
    const text = document.body.innerText || '';
    // Buttons that SHOULD be visible right now: hero CTAs + buttons in
    // the currently shown wizard step. Buttons inside a hidden wizard
    // step (Back/Continue on steps 2-7, calendar toggles, Confirm) are
    // hidden by design until the user reaches that step — not a bug.
    const isInHidden = (el) => {
      if (el.classList && el.classList.contains('hidden')) return true;
      let p = el.parentElement;
      while (p) {
        if (p.classList && p.classList.contains('hidden')) return true;
        p = p.parentElement;
      }
      return false;
    };
    const btns = [];
    document.querySelectorAll('#hero a, #hero button, #book button').forEach((b) => {
      if (isInHidden(b)) return; // hidden step / drawer — by design
      const cs = getComputedStyle(b);
      const rect = b.getBoundingClientRect();
      btns.push({
        label: (b.innerText || '').trim().slice(0, 30),
        visible: cs.display !== 'none' && cs.visibility !== 'hidden' && parseFloat(cs.opacity) > 0 && rect.width > 0 && rect.height > 0,
        bg: cs.backgroundColor,
      });
    });
    // Primary CTAs must carry the logo-blue background (the F2
    // white-on-white bug). Match the exact class token bg-[#3E8ECC] —
    // not bg-[#3E8ECC]/10 tints or hover: variants.
    const primaryBad = [];
    document.querySelectorAll('a, button').forEach((el) => {
      if (!el.classList || !el.classList.contains('bg-[#3E8ECC]')) return;
      if (isInHidden(el)) return;
      const cs = getComputedStyle(el);
      if (cs.backgroundColor !== 'rgb(62, 142, 204)') primaryBad.push({ label: (el.innerText || el.tagName).trim().slice(0, 30), bg: cs.backgroundColor });
    });
    return {
      dclMs: Math.round(nav.domContentLoadedEventEnd || 0),
      loadEventMs: Math.round(nav.loadEventEnd || 0),
      lcpMs: Math.round(window.__lcp || 0),
      cls: +(window.__cls || 0).toFixed(4),
      tbtMs: tbt,
      worstLongTaskMs: Math.round(longTasks.length ? Math.max(...longTasks) : 0),
      totalKB,
      requests: res.length + 1,
      hScroll: document.documentElement.scrollWidth > window.innerWidth + 1,
      scrollW: document.documentElement.scrollWidth,
      innerW: window.innerWidth,
      rawBlade: ['{--', '--}', '{{', '@if', '@endif'].filter((s) => text.includes(s)),
      buttons: btns.filter((b) => b.label),
      primaryBad,
    };
  });

  console.log(`== ${label} ==`);
  console.log(`load (goto->load event): ${loadMs}ms | domContentLoaded: ${m.dclMs}ms | loadEvent: ${m.loadEventMs}ms`);
  console.log(`LCP: ${m.lcpMs}ms | CLS: ${m.cls} | TBT: ${m.tbtMs}ms (worst long task ${m.worstLongTaskMs}ms)`);
  console.log(`total: ${m.totalKB} KB over ${m.requests} requests`);
  console.log(`horizontal scroll: ${m.hScroll ? 'YES scrollW=' + m.scrollW + ' innerW=' + m.innerW : 'no'}`);
  console.log(`raw Blade text on page: ${m.rawBlade.length ? m.rawBlade.join(', ') : 'none'}`);
  const invisible = m.buttons.filter((b) => !b.visible);
  console.log(`buttons checked: ${m.buttons.length}, invisible: ${invisible.length}, primary CTAs with wrong bg: ${m.primaryBad.length}`);
  invisible.forEach((b) => console.log(`  INVISIBLE: ${b.label}`));
  m.primaryBad.forEach((b) => console.log(`  WRONG BG: ${b.label} bg=${b.bg}`));

  if (m.hScroll) { console.error(`FAIL ${label}: horizontal scroll`); failed = true; }
  if (m.rawBlade.length) { console.error(`FAIL ${label}: raw Blade text visible: ${m.rawBlade.join(', ')}`); failed = true; }
  if (invisible.length) { console.error(`FAIL ${label}: ${invisible.length} invisible button(s)`); failed = true; }
  if (m.primaryBad.length) { console.error(`FAIL ${label}: ${m.primaryBad.length} primary CTA(s) without the blue background`); failed = true; }
  await page.close();
}

await browser.close();
console.log(failed ? 'METRICS: FAILURES ABOVE' : 'METRICS OK');
process.exit(failed ? 1 : 0);
