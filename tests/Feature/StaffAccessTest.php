<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Prompt 13: staff access is quiet and guarded. The public site keeps
 * exactly ONE small "Staff" footer link; the staff modal and its login
 * still work; /app and /api/auth/login are throttled; failed logins
 * are written to the audit log (identifier only - never the password).
 */
class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_keeps_one_quiet_staff_link(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        // The loud staff buttons are gone.
        $this->assertStringNotContainsString('Staff Portal', $html);
        $this->assertStringNotContainsString('Staff Management System', $html);
        $this->assertStringNotContainsString('Staff OS', $html);

        // Exactly one button opens the staff modal (the quiet footer link),
        // plus the JS function definition itself.
        $this->assertSame(1, substr_count($html, 'onclick="openStaffModal()"'));
        $this->assertStringContainsString('function openStaffModal()', $html);

        // The quiet link itself: small, dim, bilingual.
        $this->assertStringContainsString('data-lang-block="en">Staff</span>', $html);
        $this->assertStringContainsString('data-lang-block="am">ሰራተኞች</span>', $html);

        // Modal, login handler and the /app link still exist.
        $this->assertStringContainsString('id="staff-auth-modal"', $html);
        $this->assertStringContainsString('handleStaffLogin', $html);
        $this->assertStringContainsString('Go to /app', $html);
    }

    public function test_app_route_and_login_route_are_throttled(): void
    {
        $routes = Route::getRoutes();

        $appRoute = collect($routes)->first(
            fn ($r) => $r->uri() === 'app/{any?}' && in_array('GET', $r->methods())
        );
        $this->assertNotNull($appRoute, '/app route not found');
        $this->assertContains('throttle:60,1', $appRoute->gatherMiddleware());

        $loginRoute = collect($routes)->first(
            fn ($r) => $r->uri() === 'api/auth/login' && in_array('POST', $r->methods())
        );
        $this->assertNotNull($loginRoute, '/api/auth/login route not found');
        $this->assertContains('throttle:10,1', $loginRoute->gatherMiddleware());

        // A few ordinary /app loads still work (no false 429).
        for ($i = 0; $i < 3; $i++) {
            $this->get('/app')->assertStatus(200);
        }
    }

    public function test_failed_login_is_written_to_the_audit_log(): void
    {
        $user = User::factory()->create([
            'phone' => '0911000001',
            'password' => bcrypt('correct-horse'),
        ]);

        $res = $this->postJson('/api/auth/login', [
            'login' => '0911000001',
            'password' => 'wrong-password',
        ]);
        $res->assertStatus(422);

        $this->assertDatabaseCount('audit_logs', 1);
        $entry = AuditLog::first();
        $this->assertSame('login_failed', $entry->action);
        $this->assertSame($user->id, $entry->user_id);
        $this->assertSame($user->id, $entry->auditable_id);
        $this->assertSame('0911000001', $entry->new_values['login'] ?? null);
        // The password must never be stored.
        $this->assertStringNotContainsString('wrong-password', json_encode($entry->new_values));
        $this->assertNotNull($entry->ip_address);
    }

    public function test_failed_login_for_unknown_user_is_also_logged(): void
    {
        $res = $this->postJson('/api/auth/login', [
            'login' => 'nobody@example.com',
            'password' => 'whatever',
        ]);
        $res->assertStatus(422);

        $this->assertDatabaseCount('audit_logs', 1);
        $entry = AuditLog::first();
        $this->assertSame('login_failed', $entry->action);
        $this->assertNull($entry->user_id);
        $this->assertSame('nobody@example.com', $entry->new_values['login'] ?? null);
    }

    public function test_successful_login_is_not_logged_as_a_failure(): void
    {
        $user = User::factory()->create([
            'phone' => '0911000001',
            'password' => bcrypt('correct-horse'),
        ]);

        $res = $this->postJson('/api/auth/login', [
            'login' => '0911000001',
            'password' => 'correct-horse',
        ]);
        $res->assertStatus(200);
        $this->assertDatabaseCount('audit_logs', 0);
    }
}
