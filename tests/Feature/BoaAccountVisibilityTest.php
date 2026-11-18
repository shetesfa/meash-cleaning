<?php

namespace Tests\Feature;

use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prompt 4: the Abyssinia (BOA) account number '54321678' was a test
 * number. Until the owner types the real number in admin settings,
 * the Abyssinia line must not appear anywhere on the website — not
 * even in the booking-confirmation payment block.
 */
class BoaAccountVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_boa_number_set_hides_the_abyssinia_line(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        $this->assertStringNotContainsString('አቢሲኒያ', $html);
        $this->assertStringNotContainsString('54321678', $html);
        // Telebirr and CBE stay visible.
        $this->assertStringContainsString('ቴሌብር', $html);
        $this->assertStringContainsString('ንግድ ባንክ', $html);
    }

    public function test_placeholder_number_is_treated_as_unset(): void
    {
        WebsiteSetting::setVal('boa_account_number', '54321678', 'general');

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        $this->assertStringNotContainsString('አቢሲኒያ', $html);
        $this->assertStringNotContainsString('54321678', $html);
    }

    public function test_real_boa_number_is_shown(): void
    {
        WebsiteSetting::setVal('boa_account_number', '9876543210987', 'general');

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        $this->assertStringContainsString('አቢሲኒያ', $html);
        $this->assertStringContainsString('9876543210987', $html);
    }
}
