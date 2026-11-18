<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Speed + phone fixes (Q1–Q9, 2026-11-18): render-blocking scripts,
 * icon payload, service-worker precache, cache versioning, trusted
 * proxies, readable text, tap targets and form autofill.
 */
class SpeedOptimizationsTest extends TestCase
{
    use RefreshDatabase;

    // Q1: QR library lazy-loaded, Ethiopic font preloaded.
    // (Q1's lucide defer was superseded by Q2's inline sprite — see below.)
    public function test_q1_qr_is_lazy_and_font_preloaded(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertDontSee('<script src="/vendor/qrcode-generator.min.js"></script>', false);
        $res->assertSee('<link rel="preload" href="/fonts/font-1-f7346798.woff2" as="font" type="font/woff2" crossorigin>', false);
        // QR library is injected on demand when the QR codes scroll into view.
        $res->assertSee('ensureQrLib', false);
        $res->assertSee('IntersectionObserver', false);
    }

    // Q2: public page uses the 66-icon inline sprite, not the 359 KB library;
    // the staff app (/app) still loads the full lucide.min.js.
    public function test_q2_public_page_uses_sprite_and_app_keeps_full_lucide(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertDontSee('<script src="/vendor/lucide.min.js"', false);
        $res->assertSee('id="lucide-menu"', false);
        $res->assertSee('id="lucide-phone"', false);
        $res->assertSee('id="lucide-sofa"', false); // dynamic service icon
        $res->assertSee('id="lucide-layout-grid"', false); // wizard icon
        $res->assertSee('Lucide sprite re-render failed', false);

        $app = $this->get('/app');
        $app->assertStatus(200);
        $app->assertSee('<script src="/vendor/lucide.min.js"></script>', false);
    }

    // Q3: service worker precaches only the public page's needs (v8).
    public function test_q3_sw_precache_is_lean_and_version_bumped(): void
    {
        $sw = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString("const CACHE_NAME = 'meash-pwa-v8';", $sw);
        foreach (["'/'", "'/offline.html'", "'/manifest.json'", "'/css/fonts.css'", "'/css/meash.css'", "'/js/meash-i18n.js'", "'/js/meash-offline.js'", "'/logo.jpg'", "'/assets/icon-192.png'", "'/assets/icon-512.png'"] as $needed) {
            $this->assertStringContainsString($needed, $sw);
        }
        // Heavy items must NOT be precached any more (runtime cache instead).
        // (Check only the array literal, not the comment above it.)
        $start = strpos($sw, 'const PRECACHE_ASSETS = [');
        $precache = substr($sw, $start, strpos($sw, '];', $start) - $start);
        $this->assertStringNotContainsString("'/app'", $precache);
        $this->assertStringNotContainsString("'/telegram-miniapp'", $precache);
        $this->assertStringNotContainsString('chart.umd.js', $precache);
        $this->assertStringNotContainsString('leaflet', $precache);
        $this->assertStringNotContainsString('lucide.min.js', $precache);
        $this->assertStringNotContainsString('/fonts/font-', $precache);
    }

    // Q4: filemtime versions on local assets; no-cache on the HTML page.
    public function test_q4_assets_are_filemtime_versioned_and_page_is_no_cache(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();
        $this->assertMatchesRegularExpression('#/css/fonts\.css\?v=\d+#', $html);
        $this->assertMatchesRegularExpression('#/css/meash\.css\?v=\d+#', $html);
        $this->assertMatchesRegularExpression('#/vendor/qrcode-generator\.min\.js\?v=\d+#', $html);
        // The font preload must stay unversioned so it matches fonts.css.
        $this->assertStringContainsString('<link rel="preload" href="/fonts/font-1-f7346798.woff2" as="font" type="font/woff2" crossorigin>', $html);
        $this->assertStringContainsString('no-cache', $res->headers->get('Cache-Control') ?? '');

        $mini = $this->get('/telegram-miniapp');
        $mini->assertStatus(200);
        $miniHtml = $mini->getContent();
        $this->assertMatchesRegularExpression('#/css/fonts\.css\?v=\d+#', $miniHtml);
        $this->assertMatchesRegularExpression('#/css/meash\.css\?v=\d+#', $miniHtml);
        $this->assertMatchesRegularExpression('#/vendor/lucide\.min\.js\?v=\d+#', $miniHtml);
    }

    // Q6: nginx headers agree with the PHP middleware (no X-XSS, strict
    // Referrer-Policy). Note: docker/nginx.conf is NOT what serves the
    // live Plesk site — the PHP middleware is the live source of truth.
    public function test_q6_nginx_headers_match_middleware(): void
    {
        $nginx = file_get_contents(base_path('docker/nginx.conf'));
        $this->assertStringNotContainsString('add_header X-XSS-Protection', $nginx);
        $this->assertStringNotContainsString('no-referrer-when-downgrade', $nginx);
        $this->assertStringContainsString('Referrer-Policy "strict-origin-when-cross-origin"', $nginx);

        $middleware = file_get_contents(app_path('Http/Middleware/SecurityHeaders.php'));
        $this->assertStringContainsString("Referrer-Policy', 'strict-origin-when-cross-origin'", $middleware);
        $this->assertStringNotContainsString("headers->set('X-XSS-Protection'", $middleware);
    }

    // Q7: no unreadably small text on the public page. All 9px/10px gone;
    // only the 6 allowed 11px spots remain (2 logo taglines + 4 badges).
    public function test_q7_no_tiny_text_on_public_page(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();
        $this->assertStringNotContainsString('text-[9px]', $html);
        $this->assertStringNotContainsString('text-[10px]', $html);
        $this->assertEquals(6, substr_count($html, 'text-[11px]'));
        $this->assertStringContainsString('Verified Paid', $html);
        $this->assertStringContainsString('PAID', $html);
    }
}
