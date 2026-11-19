<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reception dashboard 500 fix.
 *
 * DashboardController::receptionOverview referenced `Notification` with no
 * import, so PHP resolved it to App\Http\Controllers\Api\Notification —
 * a class that does not exist — and GET /api/dashboard/reception
 * returned 500 on every load. The fix imports App\Models\Notification.
 */
class ReceptionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function reception(): User
    {
        return User::where('role', 'reception')->first() ?: User::create([
            'name' => 'Dash Reception',
            'username' => 'dash_reception',
            'email' => 'dash_reception@test.com',
            'password' => bcrypt('password'),
            'role' => 'reception',
        ]);
    }

    private function owner(): User
    {
        return User::where('role', 'owner')->first() ?: User::create([
            'name' => 'Dash Owner',
            'username' => 'dash_owner',
            'email' => 'dash_owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
    }

    public function test_reception_dashboard_loads(): void
    {
        $response = $this->actingAs($this->reception())
            ->getJson('/api/dashboard/reception');

        $response->assertOk()->assertJsonStructure([
            'website_messages',
            'new_bookings',
        ]);
    }

    public function test_owner_can_load_reception_dashboard(): void
    {
        $response = $this->actingAs($this->owner())
            ->getJson('/api/dashboard/reception');

        $response->assertOk();
    }
}
