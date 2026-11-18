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
        $start = strpos($html, 'id="hero"');
        $end = strpos($html, 'LOGO/TRUST STRIP');
        $this->assertNotFalse($start, 'Hero section missing');
        $this->assertNotFalse($end, 'Trust strip missing');
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
}
