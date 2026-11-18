<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * F2 (2026-11-19): public/css/meash.css went stale — it was last built
 * on Nov 17, before the R1–R8 redesign and the 15-prompt fixes added
 * classes to website.blade.php, so 74 classes used by the page were
 * missing from the stylesheet (the "Get quote & book" button rendered
 * white text on a white background because bg-[#3E8ECC] was absent).
 *
 * The committed stylesheet must always be rebuilt from the current
 * blade (npm run build:css) and must contain every class the page
 * relies on. This test guards the committed CSS against going stale
 * again: it extracts the critical classes from the blade and asserts
 * each one exists (Tailwind-escaped) in public/css/meash.css.
 */
class TailwindCssCoverageTest extends TestCase
{
    /** Escape a Tailwind class the way the compiler escapes selectors. */
    private function escapeClass(string $class): string
    {
        return (string) preg_replace('/([^a-zA-Z0-9\-_])/', '\\\\$1', $class);
    }

    private function builtCss(): string
    {
        return (string) file_get_contents(public_path('css/meash.css'));
    }

    private function bladeClasses(): array
    {
        $blade = (string) file_get_contents(resource_path('views/website.blade.php'));
        preg_match_all('/class="([^"]+)"/', $blade, $m);
        $classes = [];
        foreach ($m[1] as $attr) {
            foreach (preg_split('/\s+/', trim($attr)) as $token) {
                if ($token !== '') {
                    $classes[$token] = true;
                }
            }
        }
        return array_keys($classes);
    }

    public function test_button_blue_background_is_in_the_built_css(): void
    {
        // The exact regression: bg-[#3E8ECC] was missing, so the main
        // "Get quote & book" button was white-on-white.
        $this->assertStringContainsString(
            $this->escapeClass('bg-[#3E8ECC]'),
            $this->builtCss(),
            'bg-[#3E8ECC] is missing from public/css/meash.css — rebuild with: npm run build:css'
        );
    }

    public function test_every_arbitrary_background_color_in_the_blade_is_built(): void
    {
        $css = $this->builtCss();
        $missing = [];
        foreach ($this->bladeClasses() as $class) {
            // Only plain + hover/focus arbitrary bg colors (the F2 bug class).
            if (preg_match('/^(hover:|focus:)?bg-\[#[0-9A-Fa-f]+\]$/', $class)) {
                if (! str_contains($css, $this->escapeClass($class))) {
                    $missing[] = $class;
                }
            }
        }
        $this->assertSame([], $missing, 'Classes used in the blade but missing from the built CSS: ' . implode(', ', $missing));
    }

    public function test_previously_missing_classes_are_built(): void
    {
        $css = $this->builtCss();
        $required = [
            'bg-[#F4F8FC]',
            'hover:bg-[#2F6FA8]',
            'text-[#2F6FA8]',
            'min-h-[44px]',
            'min-h-[48px]',
            'min-w-[44px]',
            'md:py-24',
            'md:order-1',
            'md:order-2',
            'inset-x-0',
            'bottom-[4.5rem]',
            'mt-10',
            'dark:bg-slate-800/95',
        ];
        $missing = [];
        foreach ($required as $class) {
            if (! str_contains($css, $this->escapeClass($class))) {
                $missing[] = $class;
            }
        }
        $this->assertSame([], $missing, 'Missing from public/css/meash.css: ' . implode(', ', $missing));
    }
}
