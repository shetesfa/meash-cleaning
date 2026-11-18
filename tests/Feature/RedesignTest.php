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
        // The 5-step process section keeps its navy block (merged in R4).
        $res->assertSee('id="process" class="ms-sec bg-navy', false);
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
}
