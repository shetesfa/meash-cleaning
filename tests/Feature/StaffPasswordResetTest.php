<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * A4 — Owner staff password reset.
 *
 * The Staff Directory had only History + Payroll buttons, so the owner
 * could not reset a staff member's password. New endpoint
 * POST /api/employees/{id}/reset-password (owner only) with the same
 * 12-character strength rule as employee creation.
 */
class StaffPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $role, string $username): User
    {
        return User::where('role', $role)->first() ?: User::create([
            'name' => ucfirst($username),
            'username' => $username,
            'email' => $username . '@test.com',
            'password' => bcrypt('OldPassword123!x'),
            'role' => $role,
        ]);
    }

    private function makeCleaner(): User
    {
        return User::create([
            'name' => 'Reset Cleaner',
            'username' => 'reset_cleaner',
            'email' => 'reset_cleaner@test.com',
            'password' => Hash::make('OldPassword123!x'),
            'phone' => '0900000099',
            'role' => 'cleaner',
        ]);
    }

    public function test_owner_can_reset_staff_password(): void
    {
        $cleaner = $this->makeCleaner();

        $response = $this->actingAs($this->user('owner', 'reset_owner'))
            ->postJson("/api/employees/{$cleaner->id}/reset-password", [
                'password' => 'NewPassword123!x',
                'password_confirmation' => 'NewPassword123!x',
            ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertTrue(Hash::check('NewPassword123!x', $cleaner->fresh()->password));
        $this->assertFalse(Hash::check('OldPassword123!x', $cleaner->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'auditable_id' => $cleaner->id,
            'action' => 'employee_password_reset',
        ]);
    }

    public function test_staff_can_log_in_with_the_new_password(): void
    {
        $cleaner = $this->makeCleaner();

        $this->actingAs($this->user('owner', 'reset_owner'))
            ->postJson("/api/employees/{$cleaner->id}/reset-password", [
                'password' => 'NewPassword123!x',
                'password_confirmation' => 'NewPassword123!x',
            ])
            ->assertOk();

        $response = $this->postJson('/api/auth/login', [
            'login' => 'reset_cleaner',
            'password' => 'NewPassword123!x',
        ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_short_or_mismatched_password_is_rejected(): void
    {
        $cleaner = $this->makeCleaner();
        $owner = $this->user('owner', 'reset_owner');

        $this->actingAs($owner)
            ->postJson("/api/employees/{$cleaner->id}/reset-password", [
                'password' => 'Short1!',
                'password_confirmation' => 'Short1!',
            ])
            ->assertStatus(422);

        $this->actingAs($owner)
            ->postJson("/api/employees/{$cleaner->id}/reset-password", [
                'password' => 'NewPassword123!x',
                'password_confirmation' => 'Different123!x',
            ])
            ->assertStatus(422);

        $this->assertTrue(Hash::check('OldPassword123!x', $cleaner->fresh()->password));
    }

    public function test_non_owner_cannot_reset_passwords(): void
    {
        $cleaner = $this->makeCleaner();

        $this->actingAs($this->user('reception', 'reset_reception'))
            ->postJson("/api/employees/{$cleaner->id}/reset-password", [
                'password' => 'NewPassword123!x',
                'password_confirmation' => 'NewPassword123!x',
            ])
            ->assertStatus(403);

        $this->actingAs($cleaner)
            ->postJson("/api/employees/{$cleaner->id}/reset-password", [
                'password' => 'NewPassword123!x',
                'password_confirmation' => 'NewPassword123!x',
            ])
            ->assertStatus(403);

        $this->assertTrue(Hash::check('OldPassword123!x', $cleaner->fresh()->password));
    }

    public function test_owner_password_cannot_be_reset_from_this_control(): void
    {
        $owner = $this->user('owner', 'reset_owner');
        $hashBefore = $owner->password;

        $response = $this->actingAs($owner)
            ->postJson("/api/employees/{$owner->id}/reset-password", [
                'password' => 'NewPassword123!x',
                'password_confirmation' => 'NewPassword123!x',
            ]);

        $response->assertStatus(422);
        $this->assertSame($hashBefore, $owner->fresh()->password);
    }
}
