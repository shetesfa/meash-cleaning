<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prompt 14: photo shapes. Portrait photos keep their top (faces)
 * inside the fixed-ratio cards; hero and gallery cards keep one fixed
 * height; the lightbox shows the whole photo (object-fit: contain).
 */
class PhotoShapesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 2026-11-23: the homepage shows DB photo rows only (no
        // hardcoded fallback), so seed the rows this test reads.
        $this->seed();
    }

    public function test_portraits_keep_their_top_and_cards_keep_one_height(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        // Portrait photos are marked and the CSS keeps their top.
        $this->assertStringContainsString('class="ms-portrait"', $html);
        $this->assertStringContainsString('img.ms-portrait { object-position: center top; }', $html);

        // Card heights are fixed by aspect-ratio, not by the photo.
        $this->assertStringContainsString('aspect-ratio: 3/4', $html);
        $this->assertStringContainsString('aspect-ratio: 16/10', $html);

        // Lightbox shows the whole photo, never cropped.
        $this->assertStringContainsString('object-fit: contain', $html);
    }
}
