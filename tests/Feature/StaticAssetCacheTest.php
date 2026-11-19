<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * S1 (2026-11-19): Cache-Control for static assets.
 *
 * Lighthouse "Use efficient cache lifetimes" (2026-11-19 8:52 AM run)
 * flagged 19 first-party resources with no cache TTL — fonts, gallery
 * images, CSS/JS. Apache serves public/ directly, so the headers live
 * in public/.htaccess (Laravel middleware never sees these requests).
 *
 * Rules (see public/.htaccess):
 * - Fonts (content-hash names): 1 year + immutable — the URL changes
 *   when the file changes, so a long cache can never go stale.
 * - CSS/JS and images: 30 days (max-age=2592000) — the minimum
 *   Lighthouse accepts, and the site's existing 30-day asset
 *   convention. Not immutable: several /app references carry no ?v=
 *   version, and gallery photos are replaceable at the same URL.
 * - sw.js: no-cache, so service-worker updates propagate.
 * - manifest.json: 7 days.
 *
 * Live header behaviour is verified with curl against a local Apache
 * serving public/ (see the S1 commit message); these tests lock the
 * .htaccess rules and the versioning assumptions they rely on.
 */
class StaticAssetCacheTest extends TestCase
{
    private function htaccess(): string
    {
        return file_get_contents(public_path('.htaccess'));
    }

    public function test_fonts_get_one_year_immutable(): void
    {
        $htaccess = $this->htaccess();

        $this->assertMatchesRegularExpression(
            '/<FilesMatch[^>]*woff2[^>]*>\s*Header set Cache-Control "public, max-age=31536000, immutable"/s',
            $htaccess
        );
    }

    public function test_css_js_get_thirty_days_without_immutable(): void
    {
        $htaccess = $this->htaccess();

        $this->assertMatchesRegularExpression(
            '/<FilesMatch[^>]*\(css\|js\)[^>]*>\s*Header set Cache-Control "public, max-age=2592000"/s',
            $htaccess
        );
        // The css/js block must not claim immutable: /app references
        // (app.blade.php) carry no ?v= version, so a 1-year immutable
        // cache could serve a stale stylesheet after a deploy.
        $this->assertDoesNotMatchRegularExpression(
            '/<FilesMatch[^>]*\(css\|js\)[^>]*>\s*Header set Cache-Control "[^"]*immutable"/s',
            $htaccess
        );
    }

    public function test_images_get_thirty_days(): void
    {
        $htaccess = $this->htaccess();

        $this->assertMatchesRegularExpression(
            '/<FilesMatch[^>]*jpg\|jpeg\|png\|webp[^>]*>\s*Header set Cache-Control "public, max-age=2592000"/s',
            $htaccess
        );
    }

    public function test_service_worker_is_never_long_cached(): void
    {
        $htaccess = $this->htaccess();

        $this->assertMatchesRegularExpression(
            '/<Files "sw\.js">\s*Header set Cache-Control "no-cache"/s',
            $htaccess
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<Files "sw\.js">\s*Header set Cache-Control "[^"]*max-age/s',
            $htaccess
        );
    }

    public function test_manifest_gets_seven_days(): void
    {
        $this->assertMatchesRegularExpression(
            '/<Files "manifest\.json">\s*Header set Cache-Control "public, max-age=604800"/s',
            $this->htaccess()
        );
    }

    public function test_cache_rules_are_guarded_by_ifmodule(): void
    {
        // If mod_headers is missing, Apache must skip the block instead
        // of failing every request with a 500.
        $this->assertStringContainsString('<IfModule mod_headers.c>', $this->htaccess());
    }

    public function test_every_font_filename_is_content_hashed(): void
    {
        // The 1-year immutable font rule is only safe because every
        // font URL carries a content hash — a changed font gets a new
        // URL, so no browser can hold a stale one.
        $files = glob(public_path('fonts/*.woff2'));
        $this->assertNotEmpty($files, 'expected woff2 fonts under public/fonts');

        foreach ($files as $file) {
            $this->assertMatchesRegularExpression(
                '/font-\d+-[0-9a-f]{8}\.woff2$/',
                basename($file),
                basename($file) . ' has no content hash; immutable caching would be unsafe'
            );
        }
    }

    public function test_homepage_css_links_stay_filemtime_versioned(): void
    {
        // The public homepage CSS links must keep ?v=filemtime so a
        // deploy changes the URL (Q4). The 30-day TTL is the backstop.
        $blade = file_get_contents(resource_path('views/website.blade.php'));

        $this->assertStringContainsString(
            "/css/fonts.css?v={{ filemtime(public_path('css/fonts.css')) }}",
            $blade
        );
        $this->assertStringContainsString(
            "/css/meash.css?v={{ filemtime(public_path('css/meash.css')) }}",
            $blade
        );
    }
}
