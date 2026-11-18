<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prompt 11: honest alt text. Gallery thumbnails must carry the photo
 * caption (not alt=""), the facade-scaffold step photo must be
 * described as cleaners washing a facade from scaffolding (not
 * "preparing solution"), and every photo img must carry Amharic +
 * English alt data so setLang() can swap it.
 */
class PhotoAltTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_thumbnails_and_steps_have_caption_alts(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        // No empty alt attributes anywhere on the page.
        $this->assertStringNotContainsString('alt=""', $html);

        // Facade-scaffold step photo: honest description.
        $this->assertStringContainsString('Cleaners washing a glass facade from scaffolding', $html);
        $this->assertStringNotContainsString('preparing glass-cleaning solution', $html);
        $this->assertStringContainsString('ሰራተኞች ከስካፎልድ ላይ የሕንፃ መስታወት እያጸዱ', $html);

        // Amharic alt data is present for the language toggle.
        $this->assertStringContainsString('data-alt-am=', $html);
        // Lightbox alt follows the shown photo's caption.
        $this->assertStringContainsString('lbImg.alt = (document.documentElement.getAttribute(\'data-lang\')', $html);
    }
}
