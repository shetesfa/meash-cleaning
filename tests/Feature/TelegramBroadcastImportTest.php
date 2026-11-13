<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramBroadcastImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->owner = User::where('role', 'owner')->first() ?? User::factory()->create([
            'role' => 'owner',
            'phone' => '0911000001',
            'email' => 'test_owner_' . uniqid() . '@meash.com',
        ]);
    }

    /**
     * Batch 18: plain CSV text lines are legitimate import input and
     * must reach processCsvTextLines() (regression from the batch 1
     * invalid-JSON guard, which 422'd anything json_decode rejected).
     */
    public function test_csv_text_lines_import_successfully(): void
    {
        $res = $this->actingAs($this->owner)->postJson('/api/telegram-broadcast/import', [
            'raw_data' => "Client Alpha, 0971000001\nClient Beta, 0971000002",
        ]);

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);
        $this->assertDatabaseHas('telegram_users', ['phone_number' => '0971000001']);
        $this->assertDatabaseHas('telegram_users', ['phone_number' => '0971000002']);
    }

    /**
     * Batch 18: the batch 1 guard still holds — input that LOOKS like
     * JSON (starts with {) but does not parse must be rejected, never
     * shredded into junk CSV rows.
     */
    public function test_malformed_json_is_rejected(): void
    {
        $res = $this->actingAs($this->owner)->postJson('/api/telegram-broadcast/import', [
            'raw_data' => '{"contacts": [{"name": "Broken"',
        ]);

        $res->assertStatus(422);
        $res->assertJson(['success' => false]);
        $this->assertDatabaseMissing('telegram_users', ['phone_number' => '0971000001']);
    }
}
