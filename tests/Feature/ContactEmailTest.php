<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\TelegramController;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prompt 15: one contact email setting everywhere. The admin sets
 * contact_email once; the website (footer, mailto, JSON-LD), the bot
 * (help text, /contact, booking confirmation) all show that address.
 * Gmail stays the default; company_email is only a fallback.
 */
class ContactEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_the_configured_contact_email(): void
    {
        WebsiteSetting::setVal('contact_email', 'hello@example.com', 'general');

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        $this->assertStringContainsString('mailto:hello@example.com', $html);
        $this->assertStringContainsString('"email":"hello@example.com"', $html);
        $this->assertStringNotContainsString('meashcleaningsolution@gmail.com', $html);
    }

    public function test_homepage_defaults_to_gmail_when_nothing_is_set(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $this->assertStringContainsString('meashcleaningsolution@gmail.com', $res->getContent());
    }

    public function test_public_settings_expose_contact_email(): void
    {
        WebsiteSetting::setVal('contact_email', 'hello@example.com', 'general');

        $res = $this->getJson('/api/public/settings');
        $res->assertStatus(200);
        $this->assertSame('hello@example.com', $res->json('settings.contact_email'));
    }

    public function test_bot_get_email_prefers_contact_email_then_company_email(): void
    {
        $controller = new TelegramController();

        // contact_email wins when set.
        WebsiteSetting::setVal('contact_email', 'new@example.com', 'general');
        WebsiteSetting::setVal('company_email', 'legacy@example.com', 'general');
        $this->assertSame('new@example.com', $controller->getEmail());

        // Empty contact_email falls back to company_email.
        WebsiteSetting::setVal('contact_email', '', 'general');
        $this->assertSame('legacy@example.com', $controller->getEmail());

        // Both empty falls back to the Gmail default.
        WebsiteSetting::setVal('company_email', '', 'general');
        $this->assertSame('meashcleaningsolution@gmail.com', $controller->getEmail());
    }
}
