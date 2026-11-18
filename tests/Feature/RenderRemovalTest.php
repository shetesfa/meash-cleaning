<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * F6 (2026-11-19): Render is gone — the site runs on Plesk only.
 *
 * Deleted: render.yaml (TiDB port 4000, SSL CA, onrender webhook URL),
 * Dockerfile, and the whole docker/ directory (entrypoint, nginx,
 * php-fpm, supervisord configs). Every remaining Render/TiDB
 * reference was reworded or repointed (mini-app API base, webhook
 * method name, comments, the Windows bot helper, the README).
 *
 * Kept: a light GET /up health route — plain "OK", no database, no
 * session, no cookie — for the uptime monitor (see README "Keep the
 * site awake"). The framework's default /up rendered the welcome
 * page (1.8 KB of HTML with external fonts), so it was replaced.
 */
class RenderRemovalTest extends TestCase
{
    public function test_render_files_are_gone(): void
    {
        $this->assertFileDoesNotExist(base_path('render.yaml'));
        $this->assertFileDoesNotExist(base_path('Dockerfile'));
        $this->assertDirectoryDoesNotExist(base_path('docker'));
    }

    public function test_no_onrender_or_tidb_references_remain_in_the_repo(): void
    {
        $hits = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path(), \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($it as $file) {
            $path = $file->getPathname();
            // Skip generated / vendored / VCS content, plus this test
            // itself and the README (both legitimately name what was
            // removed — the scan is for live code/config references).
            if (str_contains($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR)
                || str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)
                || str_contains($path, DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR)
                || str_contains($path, DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR)
                || str_ends_with($path, '.phpunit.result.cache')
                || str_ends_with($path, 'README.md')
                || str_ends_with($path, 'RenderRemovalTest.php')
                || str_ends_with($path, '.map')) {
                continue;
            }
            if (!$file->isFile()) {
                continue;
            }
            $contents = @file_get_contents($path);
            if ($contents === false) {
                continue;
            }
            foreach (['onrender.com', 'TiDB', 'tidb', 'Render Cold Start', 'Render ላይ'] as $needle) {
                if (str_contains($contents, $needle)) {
                    $hits[] = $path . ' contains ' . $needle;
                }
            }
        }
        $this->assertSame([], $hits, implode("\n", $hits));
    }

    public function test_up_is_a_light_health_check(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->get('/up');
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $response->assertOk();
        $this->assertSame('OK', $response->getContent());
        $this->assertSame([], $response->headers->getCookies(), '/up must not set any cookie');
        $this->assertSame(0, $queries, '/up must not touch the database');
        $this->assertLessThan(100, strlen($response->getContent()));
    }

    public function test_webhook_url_defaults_to_the_app_url(): void
    {
        // The webhook URL is derived from APP_URL (the real domain on
        // Plesk), never hardcoded to a host. .env.example documents it.
        $example = (string) file_get_contents(base_path('.env.example'));
        $this->assertStringContainsString('TELEGRAM_WEBHOOK_URL="${APP_URL}/api/telegram/webhook"', $example);
        $this->assertStringNotContainsString('onrender.com', $example);

        $url = (string) config('services.telegram.webhook_url');
        $this->assertStringEndsWith('/api/telegram/webhook', $url);
        $this->assertStringNotContainsString('onrender.com', $url);
    }

    public function test_mini_app_points_at_the_real_domain(): void
    {
        $html = (string) file_get_contents(base_path('meash-mini-app/index.html'));
        $this->assertStringContainsString("const API_BASE = 'https://meashcleaningsolution.com.et/api';", $html);
        $this->assertStringNotContainsString('onrender.com', $html);
    }

    public function test_readme_documents_keeping_the_site_awake(): void
    {
        $readme = (string) file_get_contents(base_path('README.md'));
        $this->assertStringContainsString('Keep the site awake', $readme);
        $this->assertStringContainsString('https://meashcleaningsolution.com.et/up', $readme);
        $this->assertStringContainsString('UptimeRobot', $readme);
    }
}
