<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Homepage freeze fixes (2026-11-18): after the R1–R8 redesign the public
 * homepage never finished loading for real users — half-empty page, no
 * scroll, dead buttons. No single JS line blocked the main thread
 * (all inline scripts run clean with zero errors); the freeze was a
 * loading-side compound: QR imgs with src="" re-downloaded the whole
 * 370 KB document twice, R8 fade-up could leave content invisible
 * forever if its observer never fired, the slider script died entirely
 * on any engine without matchMedia, and the SPA fallback served the
 * homepage HTML (200) for missing static files.
 */
class HomepageFreezeFixTest extends TestCase
{
    use RefreshDatabase;

    // The 6 essential services, as seeded live (DatabaseSeeder).
    private function seedEssentialServices(): void
    {
        foreach ([
            ['code' => 'floor_ceramic', 'name_en' => 'Ceramic & Hardwood Floor Cleaning', 'name_am' => 'የሴራሚክ እና የእንጨት ወለሎች ጥልቅ ጽዳት', 'base_price' => 60, 'unit' => 'sqm', 'sort_order' => 1],
            ['code' => 'facilities_general', 'name_en' => 'Deep & General Facilities Cleaning (Corporate spaces & offices)', 'name_am' => 'የተቋማት አጠቃላይ እና ጥልቅ ጽዳት (ለቢሮዎች እና ለድርጅቶች)', 'base_price' => 3500, 'unit' => 'service', 'sort_order' => 2],
            ['code' => 'kitchen_dining', 'name_en' => 'Kitchen & Dining Sanitation (Hotels, Cafés & Restaurants)', 'name_am' => 'የወጥ ቤት እና የምግብ ማስተናገጃ ቦታዎች ጽዳት (ለሆቴል፣ ካፌ እና ሬስቶራንት)', 'base_price' => 3000, 'unit' => 'service', 'sort_order' => 3],
            ['code' => 'post_construction', 'name_en' => 'Post-Construction Handover Cleaning (For new developments)', 'name_am' => 'ከግንባታ ማጠናቀቅ በኋላ የሚደረግ የርክክብ ጽዳት', 'base_price' => 4500, 'unit' => 'service', 'sort_order' => 4],
            ['code' => 'glass', 'name_en' => 'High-Rise Window & Glass Cleaning', 'name_am' => 'የመስታወት እና የፎቅ ውጫዊ ገጽታ ጽዳት', 'base_price' => 70, 'unit' => 'sqm', 'sort_order' => 5],
            ['code' => 'upholstery_carpet', 'name_en' => 'Upholstery & Carpet Deep Care', 'name_am' => 'የሶፋ እና ምንጣፍ ጥልቅ እንክብካቤ (ቴክኖሎጂን ሳይጎዱ ማፅዳት)', 'base_price' => 350, 'unit' => 'seat', 'sort_order' => 6],
        ] as $svc) {
            \App\Models\Service::create($svc + ['is_active' => true, 'description_en' => 'x', 'description_am' => 'x']);
        }
    }

    // FIX 1: QR <img> tags must never carry src="" — an empty src makes
    // the browser re-request the document URL, i.e. each QR img
    // re-downloaded the whole 370 KB homepage (and held a PHP-FPM
    // worker). The 1x1 transparent GIF placeholder is gone: both QRs
    // are now real goQR.me SVG files in public/assets, served with
    // their real src from the first paint (2026-11-24).
    public function test_qr_images_never_have_empty_src(): void
    {
        $this->seedEssentialServices();

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        $this->assertStringNotContainsString('src=""', $html);
        $this->assertStringNotContainsString('data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7', $html);
        $this->assertStringContainsString('id="qr-website"', $html);
        $this->assertStringContainsString('id="qr-telegram"', $html);
        $this->assertStringContainsString('/assets/qr-website.svg"', $html);
        $this->assertStringContainsString('/assets/qr-telegram.svg"', $html);
        $this->assertFileExists(public_path('assets/qr-website.svg'));
        $this->assertFileExists(public_path('assets/qr-telegram.svg'));
    }

    // FIX 2: R8 fade-up must have a safety net — if the
    // IntersectionObserver never fires for an element already in view
    // (slow load, throttled observer), it would stay opacity:0 forever
    // and the page would look empty/frozen. A 1.5s timeout reveals
    // whatever is in the viewport anyway; below-fold elements keep
    // their scroll animation.
    public function test_fade_up_has_viewport_safety_net(): void
    {
        $this->seedEssentialServices();

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        $this->assertStringContainsString("if (r.top < window.innerHeight && r.bottom > 0) el.classList.add('ms-in');", $html);
        $this->assertStringContainsString('}, 1500);', $html);
    }

    // FIX 3: the hero/gallery slider script must guard matchMedia — the
    // unguarded call threw on any engine without it and killed the
    // whole script (no transforms, no dots, dead arrows, dead lightbox).
    public function test_slider_match_media_is_guarded(): void
    {
        $this->seedEssentialServices();

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        $this->assertStringContainsString("window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)').matches : false", $html);
        $this->assertStringNotContainsString("var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;", $html);
    }

    // FIX 4: missing static files must 404, not serve the homepage HTML
    // with HTTP 200 — every miss wasted a full 370 KB page download
    // (and a PHP-FPM worker) and could be cached by the service worker
    // as that asset.
    public function test_missing_static_files_return_404_not_homepage(): void
    {
        $this->get('/images/gallery/does-not-exist.webp')->assertStatus(404);
        $this->get('/images/gallery/does-not-exist.jpg')->assertStatus(404);
        $this->get('/css/does-not-exist.css')->assertStatus(404);
        $this->get('/js/does-not-exist.js')->assertStatus(404);
        $this->get('/vendor/does-not-exist.js')->assertStatus(404);
        $this->get('/fonts/does-not-exist.woff2')->assertStatus(404);
        $this->get('/assets/does-not-exist.png')->assertStatus(404);
        $this->get('/favicon-unknown.png')->assertStatus(404);
        // Unknown non-section paths 404 too — /toggle-status used to
        // return the homepage with HTTP 200 and got indexed by Google
        // as a duplicate homepage.
        $this->get('/toggle-status')->assertStatus(404);
    }

    // The SPA fallback still serves genuine website section URLs.
    public function test_spa_fallback_still_serves_section_urls(): void
    {
        $this->seedEssentialServices();

        $res = $this->get('/services');
        $res->assertStatus(200);
        $this->assertStringContainsString('Meash', $res->getContent());
    }
}
