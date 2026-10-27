<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_works_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@meash.com',
            'phone' => '0911000001',
            'password' => Hash::make('secret123'),
            'role' => 'owner',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'owner@meash.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_works_by_phone(): void
    {
        $user = User::factory()->create([
            'email' => 'cleaner@meash.com',
            'phone' => '0922000002',
            'password' => Hash::make('secret123'),
            'role' => 'cleaner',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => '0922000002',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_rejects_bad_password(): void
    {
        $user = User::factory()->create([
            'email' => 'user@meash.com',
            'password' => Hash::make('correct_password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'user@meash.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_throttles_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'target@meash.com',
            'password' => Hash::make('correct_password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'login' => 'target@meash.com',
                'password' => 'wrong_password',
            ]);
        }

        // 6th attempt must be throttled with 429
        $response = $this->postJson('/api/auth/login', [
            'login' => 'target@meash.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(429);
    }
}
