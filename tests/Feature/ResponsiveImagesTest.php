<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prompt 10: lighter photos. Local gallery JPGs must render as
 * <picture> with a WebP srcset (800/1600 variants) and width/height
 * from the original file, so the page loads less and the layout does
 * not jump. The first hero image is fetchpriority="high" (not lazy);
 * everything else is lazy.
 */
class ResponsiveImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_uses_webp_picture_with_dimensions(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        // WebP <picture> sources are present (landscape 1600 + 800).
        $this->assertStringContainsString('type="image/webp"', $html);
        $this->assertStringContainsString('-1600.webp 1600w', $html);
        $this->assertStringContainsString('-800.webp 800w', $html);

        // Width/height from the original files reserve the space.
        $this->assertStringContainsString('width="2048" height="1143"', $html);
        $this->assertStringContainsString('width="896" height="1195"', $html);

        // Original JPGs stay as the <img> fallback.
        $this->assertStringContainsString('src="/images/gallery/', $html);

        // First hero image: eager + fetchpriority, exactly once.
        $this->assertEquals(1, substr_count($html, 'fetchpriority="high"'));

        // Lightbox also serves the WebP variant for the shown photo.
        $this->assertStringContainsString('id="ms-lb-src"', $html);
        $this->assertStringContainsString('data-ms-srcset=', $html);
    }
}
