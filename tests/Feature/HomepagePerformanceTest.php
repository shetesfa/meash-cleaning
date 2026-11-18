<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\Service;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F5 (2026-11-19): fewer DB queries on the public homepage.
 *
 * Before: 13 queries on every warm visit (3 Schema::hasTable checks,
 * services + 4 settings queries + 3 photo queries + 2 session
 * queries) and a session + CSRF cookie for every anonymous visitor.
 * After: the homepage data is cached for 5 minutes (one settings
 * array, active services, added photos), hasTable is gone from the
 * request path, and "/" runs with no session/cookie middleware.
 * Warm hit: 0 queries, no Set-Cookie. Admin saves clear the cache
 * via model events, so edits show at once.
 */
class HomepagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        Service::create([
            'code' => 'floor_ceramic', 'name_en' => 'Floor Cleaning', 'name_am' => 'ወለል ማጽዳት',
            'base_price' => '60.00', 'unit' => 'sqm', 'sort_order' => 1, 'is_active' => true, 'icon' => 'sparkles',
        ]);
        WebsiteSetting::setVal('contact_phone_1', '0970075509');
        WebsiteSetting::setVal('subcities', json_encode([['id' => 'bole', 'name_am' => 'ቦሌ', 'name_en' => 'Bole', 'active' => true]]));
        Photo::create([
            'url' => 'hero-test.jpg', 'section' => 'hero', 'status' => 'added',
            'sort_order' => 1, 'title_en' => 'Hero test', 'title_am' => 'Hero test',
        ]);
        Photo::create([
            'url' => 'logo-test.jpg', 'section' => 'logo', 'status' => 'added',
            'sort_order' => 1, 'title_en' => 'Logo', 'title_am' => 'Logo',
        ]);
    }

    private function queryCountFor(callable $run): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $run();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_homepage_warm_hit_runs_zero_database_queries(): void
    {
        $cold = $this->queryCountFor(fn () => $this->get('/')->assertOk());
        $warm = $this->queryCountFor(fn () => $this->get('/')->assertOk());

        $this->assertGreaterThan(0, $cold, 'cold hit should query the DB (cache empty)');
        $this->assertSame(0, $warm, 'warm hit should run zero queries, got ' . $warm);
    }

    public function test_homepage_sets_cache_header_and_no_session_cookie(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        // Symfony normalises the directive order ("max-age=60, public").
        $this->assertStringContainsString('max-age=60', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertSame([], $response->headers->getCookies(), 'homepage must not set any cookie');
    }

    public function test_no_has_table_checks_on_the_homepage_path(): void
    {
        $blade = (string) file_get_contents(resource_path('views/website.blade.php'));
        // No real Schema::hasTable() call may remain (the F5 comments
        // above mention the name in prose — that is not a call).
        $this->assertStringNotContainsString('\Illuminate\Support\Facades\Schema::hasTable', $blade);
        $this->assertStringNotContainsString('Schema::hasTable(', $blade);
    }

    public function test_settings_change_invalidates_the_homepage_cache(): void
    {
        $this->get('/')->assertOk()->assertDontSee('0911000000');
        WebsiteSetting::setVal('contact_phone_1', '0911000000');

        $this->get('/')->assertOk()->assertSee('0911000000');
    }

    public function test_service_change_invalidates_the_homepage_cache(): void
    {
        $this->get('/')->assertOk()->assertDontSee('Test Distinctive Service');
        Service::create([
            'code' => 'test_distinctive', 'name_en' => 'Test Distinctive Service', 'name_am' => 'ሙከራ',
            'base_price' => '99.00', 'unit' => 'sqm', 'sort_order' => 2, 'is_active' => true, 'icon' => 'sparkles',
        ]);

        $this->get('/')->assertOk()->assertSee('Test Distinctive Service');
    }

    public function test_photo_change_invalidates_the_homepage_cache(): void
    {
        $this->get('/')->assertOk()->assertDontSee('photo-cache-test.jpg');
        Photo::create([
            'url' => 'photo-cache-test.jpg', 'section' => 'gallery', 'status' => 'added',
            'sort_order' => 2, 'title_en' => 'Cache test', 'title_am' => 'Cache test',
        ]);

        $this->get('/')->assertOk()->assertSee('photo-cache-test.jpg');
    }

    public function test_public_api_get_sets_no_session_cookie(): void
    {
        $response = $this->getJson('/api/public/services');

        $response->assertOk();
        $this->assertSame([], $response->headers->getCookies(), 'public API GET must not set any cookie');
    }

    public function test_staff_app_still_starts_a_session(): void
    {
        $response = $this->get('/app');

        $response->assertOk();
        $this->assertNotEmpty($response->headers->getCookies(), 'staff app must keep its session cookie');
    }
}
