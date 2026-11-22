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
        // Desktop card below the photo: free inspection only — the
        // "From 60 ETB / sqm" starting-price line was removed (2026-11-19).
        $this->assertStringNotContainsString('ETB/sqm', $hero);
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
        $this->assertStringContainsString('24/7', $trust);
        $this->assertStringContainsString('Licensed &amp; Insured', $trust);
        // Discount comes from the real pricing rules (weekly 15 = maximum).
        // R8 wraps the number in a count-up span, so assert the pieces.
        $this->assertStringContainsString('Up to <span class="ms-count" data-count="15">15</span>% off', $trust);
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
        $this->assertSame(4, substr_count($about, 'ms-card ms-fade-up p-5'), 'Why Meash must have exactly 4 cards');
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
        $this->seedEssentialServices();

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

    // R5: ONE 56px floating menu button + phone sticky bar (Call / Book now).
    public function test_r5_one_floating_button_and_sticky_bar(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        // Exactly one floating container with one 56px round button.
        $this->assertSame(1, substr_count($html, 'id="floating-actions"'));
        $this->assertStringContainsString('id="fab-toggle"', $html);
        $this->assertStringContainsString('w-14 h-14 rounded-full', $html);
        $this->assertStringContainsString('aria-haspopup="menu"', $html);
        // The small menu above it holds WhatsApp, Telegram and Call.
        $this->assertStringContainsString('ms-fab-menu', $html);
        $this->assertStringContainsString('role="menu"', $html);
        $this->assertSame(1, substr_count($html, 'id="sticky-bottom-bar"'));
        // The old two-pill floating buttons are gone (their container class
        // and the Telegram pill's title no longer exist; the menu reuses the
        // WhatsApp link text and the Telegram blue elsewhere on the page).
        $this->assertStringNotContainsString('bg-[#25D366] hover:bg-[#20ba5a]', $html);
        $this->assertStringNotContainsString('flex flex-col items-end gap-2.5', $html);
        $this->assertStringNotContainsString('title="Telegram Bot"', $html);
        $this->assertStringNotContainsString('floating-lifted', $html);
        // Phone sticky bar: Call + Book now, phones only.
        $barStart = strpos($html, 'id="sticky-bottom-bar"');
        $bar = substr($html, $barStart, 1200);
        $this->assertStringContainsString('md:hidden', $bar);
        $this->assertStringContainsString('Book now', $bar);
        $this->assertStringContainsString('tel:', $bar);
        // Behaviour: fades in only after scrolling past the hero; hides on
        // the booking form and while a modal is open.
        $this->assertStringContainsString('ms-fab-visible', $html);
        $this->assertStringContainsString('hero.offsetHeight', $html);
        $this->assertStringContainsString('floating-hidden', $html);
    }

    // R6: before/after block shows ONLY with a complete pair; the gallery
    // is a side-scrolling strip (2026-11-19, was a masonry grid on
    // desktop), and the phone thumb gap is tightened.
    public function test_r6_before_after_block_only_with_complete_pair(): void
    {
        // No pairs in the data yet: the block stays hidden.
        $res = $this->get('/');
        $res->assertStatus(200);
        $this->assertStringNotContainsString('id="before-after"', $res->getContent());

        // A lone Before (no After) is not a pair: still hidden.
        \App\Models\Photo::create([
            'url' => '/images/gallery/sofa-carpet-steam.jpg', 'title_en' => 'Dirty sofa',
            'section' => 'gallery', 'status' => 'added', 'sort_order' => 50,
            'pair_id' => 'sofa-1', 'role' => 'before',
        ]);
        $res = $this->get('/');
        $this->assertStringNotContainsString('id="before-after"', $res->getContent());

        // The matching After completes the pair: the block renders.
        \App\Models\Photo::create([
            'url' => '/images/gallery/sofa-carpet.jpg', 'title_en' => 'Clean sofa',
            'section' => 'gallery', 'status' => 'added', 'sort_order' => 51,
            'pair_id' => 'sofa-1', 'role' => 'after',
        ]);
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();
        $this->assertStringContainsString('id="before-after"', $html);
        $start = strpos($html, 'id="before-after"');
        $end = strpos($html, 'id="gallery"', $start);
        $block = substr($html, $start, $end - $start);
        $this->assertStringContainsString('sofa-carpet-steam.jpg', $block);
        $this->assertStringContainsString('sofa-carpet.jpg', $block);
        $this->assertStringContainsString('Before', $block);
        $this->assertStringContainsString('After', $block);
        $this->assertStringContainsString('type="range"', $block);
        $this->assertStringContainsString('ms-ba-divider', $block);
        // The block sits directly above the gallery.
        $this->assertStringContainsString('See the difference yourself', $block);
    }

    // 2026-11-19 (Tesfa): the gallery is a side-scrolling strip on
    // phones AND desktop — the R6 masonry grid is gone. The phone
    // thumb gap fix stays.
    public function test_gallery_side_scrolling_strip_and_phone_gap_fix(): void
    {
        // 2026-11-23: the homepage shows DB photo rows only (no
        // hardcoded fallback), so seed the rows this test reads.
        $this->seed();

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();
        // Strip: flex row, horizontal scroll, snap; no masonry grid.
        $this->assertStringContainsString('scroll-snap-type: x mandatory', $html);
        $this->assertStringNotContainsString('column-count: 3;', $html);
        // Dots + thumbnails stay visible (they scroll the strip);
        // arrows + counter are hidden (they sat inside the stage).
        $this->assertStringContainsString('id="ms-gal-dots"', $html);
        $this->assertStringContainsString('id="ms-gal-thumbs"', $html);
        $this->assertStringContainsString('.ms-gal-nav, .ms-gal-count { display: none; }', $html);
        // The lightbox wiring is untouched: tapping a tile still opens it.
        $this->assertStringContainsString('openLb(i)', $html);
        $this->assertStringContainsString('id="ms-lightbox"', $html);
        // Phone gap fix: tighter bottom padding under the thumbnails.
        $this->assertStringContainsString('.ms-gal-sec { padding-bottom: 2.5rem; }', $html);
        $this->assertStringContainsString('ms-gal-sec', $html);
    }

    // R7: booking form on phones — one-column service cards, no price
    // until a service is chosen, property chips, progress bar, sticky bar.
    public function test_r7_booking_form_on_phones(): void
    {
        $this->seedEssentialServices();

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();
        $bookStart = strpos($html, 'id="book"');
        $bookEnd = strpos($html, 'id="track"');
        $book = substr($html, $bookStart, $bookEnd - $bookStart);

        // One-column service cards on phones (2 columns on desktop).
        $this->assertStringContainsString('grid grid-cols-1 md:grid-cols-2 gap-2.5', $book);
        // No service is preselected: every card is the unselected style.
        $step1 = substr($book, strpos($book, 'id="step-1"'), strpos($book, 'id="step-2"') - strpos($book, 'id="step-1"'));
        $this->assertStringContainsString('svc-btn border-2 border-navy/10', $step1);
        $this->assertStringNotContainsString('svc-btn border-2 border-navy ', $step1);
        // Service names clamp to two lines; the price sits on the right.
        $this->assertStringContainsString('ms-clamp2', $step1);
        // The total shows "Choose a service to see the price" — no 60 ETB
        // in the totals — until a service is chosen.
        $this->assertStringContainsString('id="wiz-total"', $book);
        $this->assertStringContainsString('Choose a service to see the price', $book);
        $this->assertStringContainsString('id="wiz-total-sticky"', $book);
        $this->assertStringNotContainsString('id="wiz-total" class="font-serif font-semibold text-lg sm:text-xl text-teal-dark">60 ETB', $book);
        // JS state starts with nothing selected.
        $this->assertStringContainsString('let selectedSvcId = null;', $html);
        $this->assertStringNotContainsString('FIRST_WIZARD_SVC', $html);
        // Property Type is 3 big chips now, not a dropdown; the hidden
        // input keeps the id the submit logic reads (Home = residential).
        $this->assertStringNotContainsString('<select id="book-property-type"', $book);
        $this->assertStringContainsString('<input type="hidden" id="book-property-type" value="residential">', $book);
        $this->assertSame(3, substr_count($book, 'data-property-type="'));
        $this->assertStringContainsString('ms-prop-chip', $book);
        // Clear progress bar: Step 1 of 4, driven by showWizardStep.
        $this->assertStringContainsString('id="wiz-progress-fill"', $book);
        $this->assertStringContainsString('Step <b class="wiz-step-num">1</b> of 4', $book);
        $this->assertStringContainsString('wizStickyNext()', $html);
        // Sticky bottom bar inside the form: total + Next always visible.
        $this->assertStringContainsString('sticky bottom-0 z-10 -mx-6', $book);
        $this->assertStringContainsString('id="wiz-sticky-next"', $book);
        // No error message is visible before the user has typed anything.
        $this->assertStringContainsString('id="step2-error" class="hidden', $book);
        $this->assertStringContainsString('id="step1-hint" class="hidden', $book);
        // The Ethiopian calendar logic in step 3 is untouched.
        $this->assertStringContainsString('id="book-date"', $book);
        $this->assertStringContainsString('EC.toEth', $html);
    }


    public function test_r8_css_animations_and_tap_targets(): void
    {
        $this->seedEssentialServices();
        // 2026-11-23: the homepage shows DB photo rows only (no
        // hardcoded fallback) — seed photos (services already seeded).
        $this->seed(\Database\Seeders\PhotoSeeder::class);

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();
        $head = substr($html, 0, strpos($html, '</style>') ?: strlen($html));

        // Fade-up on scroll (once): hidden state only when JS runs, observer adds .ms-in and unobserves.
        $this->assertStringContainsString("html.js .ms-fade-up { opacity: 0; transform: translateY(16px);", $head);
        $this->assertStringContainsString('html.js .ms-fade-up.ms-in', $head);
        $this->assertStringContainsString("document.documentElement.classList.add('js')", $html);
        $this->assertStringContainsString("document.querySelectorAll('.ms-fade-up')", $html);
        $this->assertStringContainsString('io.unobserve(e.target)', $html);
        // Fade-up is on the redesigned cards: 4 trust + 4 why + 12 service + 3 steps + 1 booking = 24.
        $this->assertGreaterThanOrEqual(24, substr_count($html, 'ms-fade-up'));

        // Count-up on the trust bar discount (the only countable trust-bar number):
        // 15% comes from pricing_rules.weekly_discount (R3), animated 0 -> 15.
        $this->assertStringContainsString('class="ms-count" data-count="15"', $html);
        $this->assertStringContainsString('requestAnimationFrame(tick)', $html);

        // Soft gradient glow behind the hero image.
        $this->assertStringContainsString('<div class="ms-hero-glow" aria-hidden="true"></div>', $html);
        $this->assertStringContainsString('.ms-hero-glow { position: absolute;', $head);
        $this->assertStringContainsString('radial-gradient(60% 60% at 50% 40%, rgba(62,142,204,.35)', $head);

        // Smooth hover on buttons/links + prefers-reduced-motion respected.
        $this->assertStringContainsString('button, a { transition-property:', $head);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $head);
        $this->assertStringContainsString("window.matchMedia('(prefers-reduced-motion: reduce)')", $html);
        $this->assertStringContainsString('reduceMotion', $html);

        // Consistent 20px icons on buttons/cards: no 16px button arrows left.
        $this->assertStringNotContainsString('arrow-right" class="w-4 h-4"', $html);
        $this->assertStringContainsString('arrow-right" class="w-5 h-5"', $html);
        $this->assertStringContainsString('<i data-lucide="message-circle" class="w-5 h-5"></i>', $html);

        // Button radius 12px / height 48px; cards 16px.
        $this->assertStringContainsString('rounded-xl text-sm transition cursor-pointer min-h-[48px]', $html);
        $this->assertStringContainsString('rounded-xl transition flex items-center gap-1.5 shrink-0 cursor-pointer min-h-[44px]', $html);
        $this->assertStringContainsString('<div class="bg-white rounded-2xl ms-fade-up p-6 md:p-9 shadow-sm border border-navy/10">', $html);

        // Tap targets >= 44px: nav links, step dots, track submit, gallery CTA.
        $this->assertStringContainsString('min-h-[44px] flex items-center', $html);
        $this->assertStringContainsString('rounded-lg transition min-h-[44px]" data-step=', $html);
        $this->assertStringContainsString('rounded-xl text-sm transition cursor-pointer min-h-[44px]', $html);
        $this->assertStringContainsString('rounded-xl transition flex items-center gap-2 min-h-[44px]', $html);
        // Dots keep a small look but get a 44px-tall tap area.
        $this->assertStringContainsString('.ms-gal-dot::before', $head);
        $this->assertStringContainsString('.ms-dot::before', $head);
        $this->assertStringContainsString('min-height: 44px; padding: 12px 9px;', $head);

        // Body text at least 14px: the header nav was 13px, now 14px (text-sm).
        $this->assertStringNotContainsString('text-[13px]', $html);
        $this->assertStringContainsString('class="hidden xl:flex items-center gap-1 text-sm font-bold', $html);
    }
}
