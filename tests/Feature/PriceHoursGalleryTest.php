<?php

namespace Tests\Feature;

use App\Models\WebsiteSetting;
use Database\Seeders\UpdateWorkingHoursTo247Seeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2026-11-19 (Tesfa): three homepage content changes.
 *
 * 1. The "ከ 60 ETB / ካሬ ሜትር ጀምሮ" / "From … ETB/sqm" starting-price
 *    line is removed from the hero price card (free inspection stays).
 * 2. Working hours are 24/7 everywhere — any time, including midnight.
 * 3. The "ሥራችን" gallery becomes a side-scrolling image strip.
 */
class PriceHoursGalleryTest extends TestCase
{
    use RefreshDatabase;

    // 1. Starting-price line removed from the hero price card.
    public function test_starting_price_line_removed_from_homepage(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertDontSee('ጀምሮ');
        $res->assertDontSee('ETB/sqm');
        // Free inspection stays on the card.
        $res->assertSee('Free inspection');
        $res->assertSee('ነፃ የቦታ ምልከታ');
    }

    public function test_starting_price_line_removed_from_blade_source(): void
    {
        $blade = (string) file_get_contents(resource_path('views/website.blade.php'));
        $this->assertStringNotContainsString('ካሬ ሜትር ጀምሮ', $blade);
        $this->assertStringNotContainsString('ETB/sqm', $blade);
    }

    // 2. Working hours are 24/7.
    public function test_working_hours_defaults_are_24_7(): void
    {
        $defaults = WebsiteSetting::getDefaults();
        $this->assertSame('24/7 — በማንኛውም ሰዓት፣ ሌሊት ጨምሮ', $defaults['working_hours_am']);
        $this->assertSame('24/7 — Any time, including midnight', $defaults['working_hours_en']);
        $this->assertSame('24/7 — በማንኛውም ሰዓት፣ ሌሊት ጨምሮ', $defaults['trust_hours_am']);
        $this->assertSame('24/7 — Any time, including midnight', $defaults['trust_hours_en']);
    }

    public function test_hours_seeder_rewrites_only_old_mon_sat_values(): void
    {
        WebsiteSetting::create(['key' => 'working_hours_am', 'value' => 'ስልክ እና ቴሌግራም፦ ከሰኞ እስከ ቅዳሜ ከጠዋቱ 2:00 እስከ ምሽቱ 12:00 ይመለሳል፤ አስቸኳይ ጥያቄዎች በመልእክት በማንኛውም ጊዜ። የሌሊት ፈረቃ ለድርጅቶች በቀጠሮ።', 'group' => 'general']);
        WebsiteSetting::create(['key' => 'working_hours_en', 'value' => 'Calls and Telegram: answered Mon-Sat 8:00-18:00; urgent requests by message anytime. Night shift for companies by appointment.', 'group' => 'general']);
        WebsiteSetting::create(['key' => 'trust_hours_am', 'value' => 'ከሰኞ እስከ ቅዳሜ 8:00–18:00', 'group' => 'general']);
        WebsiteSetting::create(['key' => 'trust_hours_en', 'value' => 'Mon–Sat 8:00–18:00', 'group' => 'general']);

        (new UpdateWorkingHoursTo247Seeder)->run();

        $this->assertSame('24/7 — በማንኛውም ሰዓት፣ ሌሊት ጨምሮ', WebsiteSetting::where('key', 'working_hours_am')->first()->value);
        $this->assertSame('24/7 — Any time, including midnight', WebsiteSetting::where('key', 'working_hours_en')->first()->value);
        $this->assertSame('24/7 — በማንኛውም ሰዓት፣ ሌሊት ጨምሮ', WebsiteSetting::where('key', 'trust_hours_am')->first()->value);
        $this->assertSame('24/7 — Any time, including midnight', WebsiteSetting::where('key', 'trust_hours_en')->first()->value);
    }

    public function test_hours_seeder_leaves_a_custom_admin_value_alone(): void
    {
        WebsiteSetting::create(['key' => 'working_hours_am', 'value' => 'Custom hours set in admin', 'group' => 'general']);

        (new UpdateWorkingHoursTo247Seeder)->run();

        $this->assertSame('Custom hours set in admin', WebsiteSetting::where('key', 'working_hours_am')->first()->value);
    }

    public function test_hours_seeder_rewrites_spaced_mon_sat_value(): void
    {
        // Exact live working_hours_en value (spaced hyphen) — the first
        // deploy missed it because the markers only had 'Mon-Sat'/'Mon–Sat'.
        WebsiteSetting::create(['key' => 'working_hours_en', 'value' => 'Mon - Sat: 8:00 AM - 6:00 PM (Sunday & Night Shift by Appointment)', 'group' => 'general']);

        (new UpdateWorkingHoursTo247Seeder)->run();

        $this->assertSame('24/7 — Any time, including midnight', WebsiteSetting::where('key', 'working_hours_en')->first()->value);
    }

    public function test_homepage_shows_24_7_hours_and_no_mon_sat(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertSee('24/7');
        $res->assertDontSee('8:00–18:00');
        $res->assertDontSee('ከሰኞ እስከ ቅዳሜ');
    }

    // 3. Gallery is a side-scrolling strip.
    public function test_gallery_is_a_horizontal_scroll_strip(): void
    {
        $blade = (string) file_get_contents(resource_path('views/website.blade.php'));
        $this->assertStringContainsString('scroll-snap-type: x mandatory', $blade);
        $this->assertStringContainsString('scrollIntoView', $blade);
        // No more desktop masonry grid or gallery autoplay.
        $this->assertStringNotContainsString('column-count: 3', $blade);
        $this->assertStringNotContainsString('setInterval(gNext, 5000)', $blade);
    }

    public function test_homepage_renders_gallery_strip_with_all_photos(): void
    {
        // 2026-11-23: the homepage shows DB photo rows only (no
        // hardcoded fallback), so seed the rows this test reads.
        $this->seed();

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();
        $this->assertStringContainsString('id="ms-gal-stage"', $html);
        $this->assertStringContainsString('scroll-snap-type: x mandatory', $html);
        $this->assertGreaterThan(0, substr_count($html, '<figure class="ms-gal-slide'));
        // Bilingual captions and the lightbox hook stay.
        $this->assertStringContainsString('ms-gal-cap', $html);
        $this->assertStringContainsString('id="ms-lightbox"', $html);
    }
}
