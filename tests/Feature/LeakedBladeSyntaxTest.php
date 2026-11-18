<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * F3 (2026-11-19): a Blade comment written with a single brace —
 * "{-- ... --}" at website.blade.php:1939–1944 — is not a comment at
 * all, so Blade printed the raw text onto the page inside the
 * Services section. A proper Blade comment is "{{-- ... --}}".
 *
 * This test loads "/" and fails if the rendered HTML contains any
 * raw Blade syntax ("{--", "--}", "{{", "@if") outside <script> and
 * <style> blocks (inline scripts legitimately contain "{{" inside
 * Blade-evaluated strings, and JSON-LD lives in a script block, so
 * those blocks are stripped before checking). It also statically
 * guards every .blade.php file against single-brace comments.
 */
class LeakedBladeSyntaxTest extends TestCase
{
    use RefreshDatabase;

    private function seedEssentialServices(): void
    {
        foreach ([
            ['code' => 'floor_ceramic', 'name_en' => 'Ceramic & Hardwood Floor Cleaning', 'name_am' => 'የሴራሚክ እና የእንጨት ወለሎች ጥልቅ ጽዳት', 'base_price' => 60, 'unit' => 'sqm', 'sort_order' => 1],
            ['code' => 'facilities_general', 'name_en' => 'Deep & General Facilities Cleaning', 'name_am' => 'የተቋማት አጠቃላይ እና ጥልቅ ጽዳት', 'base_price' => 3500, 'unit' => 'service', 'sort_order' => 2],
            ['code' => 'kitchen_dining', 'name_en' => 'Kitchen & Dining Sanitation', 'name_am' => 'የወጥ ቤት እና የምግብ ማስተናገጃ ቦታዎች ጽዳት', 'base_price' => 3000, 'unit' => 'service', 'sort_order' => 3],
            ['code' => 'post_construction', 'name_en' => 'Post-Construction Handover Cleaning', 'name_am' => 'ከግንባታ ማጠናቀቅ በኋላ የሚደረግ የርክክብ ጽዳት', 'base_price' => 4500, 'unit' => 'service', 'sort_order' => 4],
            ['code' => 'glass', 'name_en' => 'High-Rise Window & Glass Cleaning', 'name_am' => 'የመስታወት እና የፎቅ ውጫዊ ገጽታ ጽዳት', 'base_price' => 70, 'unit' => 'sqm', 'sort_order' => 5],
            ['code' => 'upholstery_carpet', 'name_en' => 'Upholstery & Carpet Deep Care', 'name_am' => 'የሶፋ እና ምንጣፍ ጥልቅ እንክብካቤ', 'base_price' => 350, 'unit' => 'seat', 'sort_order' => 6],
        ] as $svc) {
            \App\Models\Service::create($svc + ['is_active' => true, 'description_en' => 'x', 'description_am' => 'x']);
        }
    }

    public function test_homepage_renders_no_raw_blade_syntax(): void
    {
        $this->seedEssentialServices();

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        // Inline scripts and styles legitimately contain Blade-evaluated
        // strings (e.g. "{{" inside JS template literals before Blade
        // renders them is gone; JSON-LD lives in a script block), so
        // strip those blocks and check only the visible page.
        $visible = (string) preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);
        $visible = (string) preg_replace('#<style\b[^>]*>.*?</style>#is', '', $visible);

        foreach (['{--', '--}', '{{', '@if'] as $leak) {
            $this->assertStringNotContainsString($leak, $visible, "Rendered homepage leaks raw Blade syntax: {$leak}");
        }
        // The R4 comment must not print on the page at all.
        $this->assertStringNotContainsString('Commercial + Core Services merged', $visible);
    }

    public function test_no_blade_file_has_a_single_brace_comment(): void
    {
        $files = glob(resource_path('views/**/*.blade.php')) ?: [];
        $files = array_merge($files, glob(resource_path('views/*.blade.php')) ?: []);
        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $src = (string) file_get_contents($file);
            // A single-brace "{--" that is not the start of "{{--".
            $this->assertDoesNotMatchRegularExpression(
                '/(?<!\{)\{--(?!-)/',
                $src,
                basename($file) . ' contains a single-brace "{--" comment'
            );
            // A single-brace "--}" that is not the end of "--}}".
            $this->assertDoesNotMatchRegularExpression(
                '/--\}(?!\})/',
                $src,
                basename($file) . ' contains a single-brace "--}" comment end'
            );
        }
    }
}
