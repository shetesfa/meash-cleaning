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
}
