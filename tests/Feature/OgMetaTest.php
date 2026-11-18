<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prompt 8: og:image must be an absolute URL (Telegram/Facebook need it),
 * with width/height, locales and Twitter card tags. With no share.jpg
 * uploaded yet the logo is used (640x640).
 */
class OgMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_graph_image_is_absolute_with_dimensions_and_twitter_card(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        // Absolute URL, not a relative path.
        $this->assertMatchesRegularExpression(
            '/<meta property="og:image" content="https?:\/\/[^"]+">/',
            $html
        );
        // Logo dimensions (no share.jpg yet, so the 640x640 logo is used).
        $this->assertStringContainsString('<meta property="og:image:width" content="640">', $html);
        $this->assertStringContainsString('<meta property="og:image:height" content="640">', $html);
        // Locales: Amharic primary, English alternate.
        $this->assertStringContainsString('<meta property="og:locale" content="am_ET">', $html);
        $this->assertStringContainsString('<meta property="og:locale:alternate" content="en_US">', $html);
        // Twitter large card.
        $this->assertStringContainsString('<meta name="twitter:card" content="summary_large_image">', $html);
        $this->assertMatchesRegularExpression(
            '/<meta name="twitter:image" content="https?:\/\/[^"]+">/',
            $html
        );
    }
}
