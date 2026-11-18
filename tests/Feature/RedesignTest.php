<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Redesign batch (R1–R8, 2026-11-18): light default theme, phone-first
 * hero, trust bar, merged sections, floating menu, before/after,
 * booking form on phone, motion + polish.
 */
class RedesignTest extends TestCase
{
    use RefreshDatabase;

    // R1: LIGHT is the default theme for new visitors (no saved choice).
    public function test_r1_light_is_default_theme(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        // Head inline init: only a saved 'dark' turns dark on.
        $res->assertSee("if (saved === 'dark')", false);
        $res->assertSee("localStorage.setItem('meash_theme', 'light')", false);
        $res->assertDontSee("localStorage.setItem('meash_theme', 'dark')", false);
        // initTheme() also defaults to light.
        $res->assertSee('always LIGHT for new visitors', false);
        // Light theme-color for the browser chrome.
        $res->assertSee('<meta name="theme-color" content="#FFFFFF">', false);
    }

    // R1: alternating section rhythm — white / #F4F8FC, navy only for
    // the process block + final CTA; py-16 on phones, py-24 on desktop.
    public function test_r1_section_rhythm_and_spacing(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertSee('bg-[#F4F8FC]', false);
        $res->assertSee('class="ms-sec py-16 md:py-24', false);
        // The 5-step process section was removed in R4 (only the 3-step
        // process remains); the deep navy block is now ONLY the final CTA.
        $res->assertSee('bg-gradient-to-r from-slate-900 via-navy to-slate-900', false);
        $res->assertDontSee('id="process" class="ms-sec bg-navy', false);
        // One consistent heading system exists.
        $res->assertSee('.ms-sec-label', false);
        $res->assertSee('.ms-sec-title', false);
        $res->assertSee('.ms-sec-sub', false);
        // Card system: white, 16px radius, soft shadow, hover lift.
        $res->assertSee('.ms-card', false);
        $res->assertSee('translateY(-4px)', false);
        // No stray section close: opens == closes.
        $html = $res->getContent();
        $this->assertSame(
            substr_count($html, '<section'),
            substr_count($html, '</section>'),
            'Unbalanced <section> tags on the public page'
        );
    }

    private function heroHtml(string $html): string
    {
        $start = strpos($html, 'HERO');
        $end = strpos($html, 'TRUST BAR');
        $this->assertNotFalse($start, 'Hero section missing');
        $this->assertNotFalse($end, 'Trust bar missing');
        return substr($html, $start, $end - $start);
    }

    // R2: phone-first hero — photo first, ONE headline, ONE sentence,
    // exactly two buttons; the POPULAR-storefront photo is excluded.
    public function test_r2_phone_first_hero(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $hero = $this->heroHtml($res->getContent());

        // The red POPULAR-storefront photo (another company's sign +
        // readable car plates) never appears in the hero slider.
        $this->assertStringNotContainsString('rope-access.jpg', $hero,
            'Hero must not show the POPULAR storefront photo');

        // Slogan appears exactly once in the hero (in the headline).
        $this->assertSame(1, substr_count($hero, 'Your Environment, Our Responsibility.'),
            'Slogan must appear exactly once in the hero');
        // Short company name as the headline.
        $this->assertStringContainsString('MEASH', $hero);
        // The Commercial Contracts button is out of the hero (nav keeps it).
        $this->assertStringNotContainsString('Commercial Contracts', $hero);
        // Exactly two primary hero buttons: Get Quote & Book + round Call.
        $this->assertStringContainsString('Get Quote &amp; Book', $hero);
        $this->assertStringContainsString('tel:', $hero);
        // Desktop floating card on the photo: real price, free inspection.
        $this->assertStringContainsString('ETB/sqm', $hero);
        $this->assertStringContainsString('Free inspection', $hero);
        // Phone stage is compact so the first screen fits 390x844.
        $res->assertSee('.ms-stage { height: 330px; }', false);
    }

    // R3: trust bar — 4 big items, ONLY verified claims from settings.
    public function test_r3_trust_bar_only_verified_claims(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();
        $start = strpos($html, 'id="trust"');
        $end = strpos($html, 'id="about"');
        $this->assertNotFalse($start, 'Trust bar missing');
        $this->assertNotFalse($end, 'About section missing');
        $trust = substr($html, $start, $end - $start);

        // The four verified claims are present.
        $this->assertStringContainsString('Free inspection', $trust);
        $this->assertStringContainsString('Mon–Sat 8:00–18:00', $trust);
        $this->assertStringContainsString('Licensed &amp; Insured', $trust);
        // Discount comes from the real pricing rules (weekly 15 = maximum).
        $this->assertStringContainsString('Up to 15% off', $trust);
        $this->assertStringContainsString('Weekly plans &amp; recurring contracts', $trust);

        // Unverified / false claims are NOT used anywhere on the page.
        $this->assertStringNotContainsString('VAT invoice', $html);
        $this->assertStringNotContainsString('Monthly contracts up to 15% off', $html);
        $this->assertStringNotContainsString('500+ clients', $html);
        $this->assertStringNotContainsString('Vetted &amp; Certified Staff', $html);
        // No client-logo block: there are no real logos to show.
        $this->assertStringNotContainsString('client-logo', $html);
        $this->assertStringNotContainsString('Our Clients', $html);
        // 2x2 grid on phones, single row on desktop.
        $this->assertStringContainsString('grid grid-cols-2 lg:grid-cols-4', $trust);
    }

    // R3: clearing a trust setting hides that claim (steam/equipment pattern).
    public function test_r3_clearing_trust_setting_hides_claim(): void
    {
        \App\Models\WebsiteSetting::setVal('trust_free_inspection_en', '');
        \App\Models\WebsiteSetting::setVal('trust_free_inspection_am', '');
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();
        $start = strpos($html, 'id="trust"');
        $end = strpos($html, 'id="about"');
        $trust = substr($html, $start, $end - $start);
        $this->assertStringNotContainsString('Free inspection', $trust);
        // The other claims still show.
        $this->assertStringContainsString('Licensed &amp; Insured', $trust);
    }

    private function sectionHtml(string $html, string $id, string $nextMarker): string
    {
        $start = strpos($html, 'id="'.$id.'"');
        $end = strpos($html, $nextMarker, $start);
        $this->assertNotFalse($start, "Section #$id missing");
        $this->assertNotFalse($end, "Marker $nextMarker missing");
        return substr($html, $start, $end - $start);
    }

    // R4: About + Five Pillars merged into ONE "Why Meash" section, 4 cards.
    public function test_r4_why_meash_merged(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        // Only ONE section carries the merged content; the old advantage
        // section is gone but its id survives as an anchor.
        $this->assertSame(1, substr_count($html, 'id="about"'));
        $this->assertStringContainsString('id="advantage"', $html);
        $this->assertStringNotContainsString('5 Pillars of Cleaning Excellence', $html);
        $this->assertStringNotContainsString('5 Core Pillars', $html);

        $about = $this->sectionHtml($html, 'about', 'id="services"');
        $this->assertStringContainsString('WHY MEASH?', $about);
        // Exactly 4 icon cards in a 2x2 grid.
        $this->assertStringContainsString('grid grid-cols-2 gap-4', $about);
        $this->assertSame(4, substr_count($about, 'ms-card p-5'), 'Why Meash must have exactly 4 cards');
        $this->assertStringContainsString('Fully Licensed &amp; Insured', $about);
        $this->assertStringContainsString('Certified &amp; Vetted Teams', $about);
        $this->assertStringContainsString('Eco-Friendly &amp; Safe', $about);
        $this->assertStringContainsString('Flexible &amp; Discrete', $about);
        // The unverified VAT-invoicing line is gone (R3 verification).
        $this->assertStringNotContainsString('VAT invoicing', $html);
        $this->assertStringNotContainsString('Official VAT', $html);
        // The company badge box (address/phones/email) survives the merge.
        $this->assertStringContainsString('Head Office Location', $about);
    }

    // R4: Commercial + Core Services merged into ONE tabbed services section.
    public function test_r4_services_tabs(): void
    {
        // The 6 essential services, as seeded live (DatabaseSeeder).
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

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        // Old section ids survive as anchors; only one services section exists.
        $this->assertSame(1, substr_count($html, 'id="services"'));
        $this->assertStringContainsString('id="commercial"', $html);
        $svc = $this->sectionHtml($html, 'services', 'id="how"');

        // Tabs exist; Business is the default.
        $this->assertStringContainsString('data-service-tab="business"', $svc);
        $this->assertStringContainsString('data-service-tab="home"', $svc);
        $this->assertStringContainsString('aria-selected="true" data-service-tab="business"', $svc);
        $this->assertStringContainsString('id="tab-business"', $svc);
        $this->assertStringContainsString('id="tab-home"', $svc);

        // Business tab: the 6 commercial sectors, with photos and real prices.
        $bizStart = strpos($svc, 'id="tab-business"');
        $bizEnd = strpos($svc, 'id="tab-home"');
        $biz = substr($svc, $bizStart, $bizEnd - $bizStart);
        $this->assertSame(6, substr_count($biz, '<article'), 'Business tab must have 6 cards');
        $this->assertStringContainsString('Office &amp; Corporate Cleaning', $biz);
        $this->assertStringContainsString('Hotel &amp; Hospitality Sanitization', $biz);
        $this->assertStringContainsString('Restaurant &amp; Café Cleaning', $biz);
        $this->assertStringContainsString('Auditorium &amp; Hall Chairs', $biz);
        $this->assertStringContainsString('Commercial Glass Facades', $biz);
        $this->assertStringContainsString('Long-Term Cleaning SLAs', $biz);
        $this->assertStringContainsString('3,500 ETB', $biz);
        $this->assertStringContainsString('3,000 ETB', $biz);
        $this->assertStringContainsString('70 ETB / sqm', $biz);
        $this->assertStringContainsString('350 ETB / seat', $biz);
        // Book buttons preselect REAL service codes — the old fake codes
        // (office/hotel_clean/restaurant_clean/hall_chair_clean) are gone.
        $this->assertStringContainsString("preselectService('facilities_general')", $biz);
        $this->assertStringContainsString("preselectService('kitchen_dining')", $biz);
        $this->assertStringContainsString("preselectService('upholstery_carpet')", $biz);
        $this->assertStringContainsString("preselectService('glass')", $biz);
        $this->assertStringNotContainsString("preselectService('office')", $html);
        $this->assertStringNotContainsString("preselectService('hotel_clean')", $html);
        $this->assertStringNotContainsString("preselectService('restaurant_clean')", $html);
        $this->assertStringNotContainsString("preselectService('hall_chair_clean')", $html);

        // Home tab: the 6 DB services still render with Book buttons.
        $home = substr($svc, $bizEnd);
        $this->assertStringContainsString('Deep &amp; General Facilities Cleaning', $home);
        $this->assertStringContainsString("preselectService('floor_ceramic')", $home);

        // Only ONE process section remains: the 3-step one, with the
        // connecting line; the 5-step section is gone (anchor survives).
        $this->assertStringContainsString('id="process"', $html);
        $this->assertStringNotContainsString('OUR 5-STEP PROCESS', $html);
        $this->assertStringContainsString('ms-steps', $html);
        $this->assertStringContainsString('01.', $html);
        $this->assertStringContainsString('02.', $html);
        $this->assertStringContainsString('03.', $html);
    }
}
