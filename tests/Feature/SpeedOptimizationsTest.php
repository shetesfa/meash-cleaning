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

    // Q1: lucide deferred, QR library lazy-loaded, Ethiopic font preloaded.
    public function test_q1_lucide_is_deferred_qr_is_lazy_and_font_preloaded(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertSee('<script src="/vendor/lucide.min.js" defer></script>', false);
        $res->assertDontSee('<script src="/vendor/qrcode-generator.min.js"></script>', false);
        $res->assertSee('<link rel="preload" href="/fonts/font-1-f7346798.woff2" as="font" type="font/woff2" crossorigin>', false);
        // Icons re-render on DOMContentLoaded (deferred lucide arrives later).
        $res->assertSee('Lucide re-render failed', false);
        // QR library is injected on demand when the QR codes scroll into view.
        $res->assertSee('ensureQrLib', false);
        $res->assertSee('IntersectionObserver', false);
    }
}
