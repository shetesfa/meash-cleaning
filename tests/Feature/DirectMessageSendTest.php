<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A5 — 1-to-1 message Send button was a silent no-op.
 *
 * Root cause: CustomerCareController::sendSms / sendTelegram passed
 * $customer?->id (null for phone-only sends — the 1-to-1 modal sends a
 * phone + message, no customer_id) to AuditLog::logAction(), whose $id
 * parameter is a non-nullable int. The TypeError 500'd the whole send
 * and the frontend only showed a generic "internal system error" alert.
 * Fix: null-safe id ($customer?->id ?? 0) at all three call sites.
 * No SMS gateway exists by design — the message is recorded in the
 * audit log + sms.log and the response says so honestly.
 */
class DirectMessageSendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function owner(): User
    {
        return User::where('role', 'owner')->first() ?: User::create([
            'name' => 'Send Owner',
            'username' => 'send_owner',
            'email' => 'send_owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
    }

    public function test_send_sms_to_phone_only_contact_succeeds_and_is_recorded(): void
    {
        $response = $this->actingAs($this->owner())
            ->postJson('/api/customer-care/send-sms', [
                'phone' => '0911000001',
                'message' => 'Hello, this is a test message.',
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $log = AuditLog::where('action', 'customer_care_sms_sent')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame(0, (int) $log->auditable_id);
        $this->assertStringContainsString('Hello, this is a test message.', json_encode($log->new_values));
        // Phone is stored normalized (+251 form) by SmsService.
        $this->assertStringContainsString('911000001', json_encode($log->new_values));
    }

    public function test_send_sms_to_known_customer_records_the_customer_id(): void
    {
        $customer = Customer::create([
            'full_name' => 'Send Customer',
            'phone' => '0911770004',
            'address' => 'Bole Medhanialem',
        ]);

        $response = $this->actingAs($this->owner())
            ->postJson('/api/customer-care/send-sms', [
                'customer_id' => $customer->id,
                'message' => 'Your cleaning is scheduled.',
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer_care_sms_sent',
            'auditable_id' => $customer->id,
        ]);
    }

    public function test_send_telegram_phone_only_falls_back_to_sms_and_is_recorded(): void
    {
        $response = $this->actingAs($this->owner())
            ->postJson('/api/customer-care/send-telegram', [
                'phone' => '0911000002',
                'message' => 'Hello via Telegram fallback.',
            ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer_care_telegram_routed_to_sms',
            'auditable_id' => 0,
        ]);
    }

    public function test_send_without_phone_or_customer_fails_with_clear_message(): void
    {
        $response = $this->actingAs($this->owner())
            ->postJson('/api/customer-care/send-sms', [
                'message' => 'No recipient here.',
            ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
    }

    public function test_send_without_message_is_rejected(): void
    {
        $response = $this->actingAs($this->owner())
            ->postJson('/api/customer-care/send-sms', [
                'phone' => '0911000003',
            ]);

        $response->assertStatus(422);
    }
}
