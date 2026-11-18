<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * F4 (2026-11-19): small design bugs on the hero and trust bar.
 *
 * 1. The floating "From 60 ETB / sqm" card was absolutely positioned
 *    over the slider panel (-bottom-5) and covered the slider arrows
 *    and dots on desktop. It now sits in normal flow below the panel.
 * 2. The 4th trust-bar item ("Up to 15% discount") had a blank icon
 *    box: its data-lucide name "badge-percent" was not in the 66-icon
 *    inline SVG sprite, so the createIcons() shim skipped it. The
 *    sprite now has 67 icons.
 * 3. "Mon–Sat 8:00–18:00" wrapped mid-time to two lines on desktop.
 *    The hours title now has lg:whitespace-nowrap (the grid was
 *    already equal-width at lg:grid-cols-4).
 * 4. The fixed sticky bottom bar (~64px on phones) covered the footer
 *    and last form fields: the page now has mobile-only bottom
 *    padding for it.
 */
class SmallDesignBugsTest extends TestCase
{
    private function bladeHtml(): string
    {
        return (string) file_get_contents(resource_path('views/website.blade.php'));
    }

    public function test_hero_price_card_does_not_cover_slider_controls(): void
    {
        $blade = $this->bladeHtml();
        // The card must not be absolutely positioned over the panel.
        $this->assertStringNotContainsString('absolute -bottom-5 left-5', $blade);
        // It sits in normal flow below the slider panel instead.
        $this->assertMatchesRegularExpression(
            '/<div class="hidden md:flex mt-4 items-center gap-3 bg-white/',
            $blade
        );
    }

    public function test_trust_discount_icon_is_in_the_sprite(): void
    {
        $sprite = (string) file_get_contents(resource_path('views/partials/lucide-sprite.blade.php'));
        $this->assertStringContainsString('id="lucide-badge-percent"', $sprite);
    }

    public function test_every_static_public_icon_is_in_the_sprite(): void
    {
        $sprite = (string) file_get_contents(resource_path('views/partials/lucide-sprite.blade.php'));
        preg_match_all('/data-lucide="([a-z0-9-]+)"/', $this->bladeHtml(), $m);
        $missing = [];
        foreach (array_unique($m[1]) as $name) {
            if (! str_contains($sprite, 'id="lucide-' . $name . '"')) {
                $missing[] = $name;
            }
        }
        $this->assertSame([], $missing, 'Public-page icons missing from the sprite: ' . implode(', ', $missing));
    }

    public function test_hours_title_does_not_wrap_on_desktop(): void
    {
        $blade = $this->bladeHtml();
        // Equal-width trust items (already the case at lg).
        $this->assertStringContainsString('grid grid-cols-2 lg:grid-cols-4', $blade);
        // The hours title keeps the time on one line on desktop.
        $this->assertStringContainsString('lg:whitespace-nowrap"><span data-lang-block="am">{{ $trustHoursAm }}</span>', $blade);
    }

    public function test_mobile_page_has_bottom_padding_for_the_sticky_bar(): void
    {
        $blade = $this->bladeHtml();
        $this->assertStringContainsString('@media (max-width: 767px)', $blade);
        $this->assertStringContainsString('body { padding-bottom: calc(4.5rem + env(safe-area-inset-bottom)); }', $blade);
    }
}
