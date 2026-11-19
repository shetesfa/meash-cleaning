<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * S3 (2026-11-19): defer the non-critical fonts.css stylesheet.
 *
 * Lighthouse (2026-11-19 8:52 AM run) flagged two render-blocking
 * stylesheets: /css/meash.css (12.1 KiB, 450ms) and /css/fonts.css
 * (1.2 KiB, 150ms).
 *
 * - fonts.css is deferred with preload + onload swap (+ <noscript>
 *   fallback). Safe because the file holds ONLY @font-face rules, all
 *   with font-display: swap — text paints at once in a fallback font
 *   and swaps when the font CSS loads. No layout, colour or sizing
 *   rule lives in it, so first paint cannot change.
 * - meash.css deliberately STAYS a normal render-blocking stylesheet:
 *   it carries every Tailwind utility and custom style the hero
 *   depends on, and deferring it would flash the hero unstyled.
 *   Its transfer is small (12.1 KiB); the blocking time left is
 *   Slow-4G round-trip time, not size.
 *
 * Visual proof: Playwright screenshots at 390x844 and 1280px before
 * and after S3 (hero + gallery) and scripts/smoke.mjs (load < 3s,
 * no long task > 300ms) — see the S3 commit message.
 */
class DeferredFontsCssTest extends TestCase
{
    use RefreshDatabase;

    private function homepageHtml(): string
    {
        $res = $this->get('/');
        $res->assertStatus(200);

        return $res->getContent();
    }

    public function test_fonts_css_is_deferred_with_preload_onload(): void
    {
        $html = $this->homepageHtml();

        $this->assertMatchesRegularExpression(
            '/<link rel="preload" href="\/css\/fonts\.css\?v=\d+" as="style" onload="this\.onload=null;this\.rel=\'stylesheet\'">/',
            $html
        );
    }

    public function test_fonts_css_stylesheet_link_exists_only_in_noscript(): void
    {
        $html = $this->homepageHtml();

        // Exactly one plain stylesheet link to fonts.css, and it is the
        // no-JS fallback — the blocking link is gone from the head.
        preg_match_all('/<link rel="stylesheet" href="\/css\/fonts\.css\?v=\d+">/', $html, $matches);
        $this->assertCount(1, $matches[0]);
        $this->assertMatchesRegularExpression(
            '/<noscript><link rel="stylesheet" href="\/css\/fonts\.css\?v=\d+"><\/noscript>/',
            $html
        );
    }

    public function test_meash_css_stays_render_blocking(): void
    {
        $html = $this->homepageHtml();

        // Plain stylesheet link, not a preload and not deferred.
        $this->assertMatchesRegularExpression(
            '/<link rel="stylesheet" href="\/css\/meash\.css\?v=\d+">/',
            $html
        );
        $this->assertStringNotContainsString('rel="preload" href="/css/meash.css', $html);
    }

    public function test_fonts_css_holds_only_font_face_rules_with_swap(): void
    {
        // The safety premise of the deferral: nothing in fonts.css can
        // change first-paint layout — only @font-face registrations,
        // every one with font-display: swap.
        $css = file_get_contents(public_path('css/fonts.css'));

        $this->assertSame(70, substr_count($css, '@font-face'));
        $this->assertSame(70, substr_count($css, 'font-display: swap'));

        // After removing every @font-face block and every comment,
        // nothing but whitespace may remain — no selector, no rule.
        $stripped = preg_replace('/@font-face\s*\{[^}]*\}/s', '', $css);
        $stripped = preg_replace('/\/\*.*?\*\//s', '', $stripped);
        $this->assertSame('', trim($stripped), 'fonts.css must hold only @font-face rules');
    }

    public function test_ethiopic_font_preload_still_present(): void
    {
        // Q1: the first-screen Ethiopic font is still preloaded (its
        // URL must match fonts.css byte for byte to share the download).
        $this->assertStringContainsString(
            '<link rel="preload" href="/fonts/font-1-f7346798.woff2" as="font" type="font/woff2" crossorigin>',
            $this->homepageHtml()
        );
    }
}
