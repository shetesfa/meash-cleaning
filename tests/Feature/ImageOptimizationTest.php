<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * S2 (2026-11-19): gallery WebP recompression + logo resize.
 *
 * Lighthouse "Improve image delivery" (2026-11-19 8:52 AM run) estimated
 * 384 KiB of savings, mostly gallery WebP files. All 23 WebP files in
 * public/images/gallery were re-encoded with libwebp quality 75 (preset
 * photo) at identical filenames and dimensions.
 *
 * Measured result: gallery WebP total 2,077,050 -> 1,781,738 bytes
 * (-295,312, -14.2%). SSIM (original vs recompressed) on the five
 * largest files: 0.985-0.990 — visually near-identical, confirmed by
 * before/after screenshots at 390px and 1280px.
 *
 * public/logo.jpg was deliberately NOT resized (640x640 kept): it is
 * also the og:image (website.blade.php reads its size live with
 * getimagesize), so shrinking it to 200x200 would have shrunk every
 * Telegram/WhatsApp/Facebook link preview to 200px — a share-appearance
 * regression not worth its ~15.6 KiB. A dedicated 1200x630 share image
 * (public/images/share.jpg, which the blade already prefers when it
 * exists) is the right fix for that, as separate work.
 *
 * NOTE: the binary files are NOT part of the S2 commit — the GitHub
 * App push flow rejects binaries, so the parent uploads them via the
 * browser. These tests pass once the recompressed binaries are in
 * place (they assert the intended on-disk state).
 */
class ImageOptimizationTest extends TestCase
{
    public function test_logo_stays_640_for_og_image(): void
    {
        // logo.jpg is ALSO the og:image (website.blade.php reads its
        // size live with getimagesize), so it must stay 640x640: the
        // S2 plan to shrink it to 200x200 was reverted because it
        // would have shrunk every link preview to 200px. OgMetaTest
        // asserts the 640x640 meta tags; this guards the file itself.
        $path = public_path('logo.jpg');
        $this->assertFileExists($path);

        [$width, $height] = getimagesize($path);
        $this->assertSame([640, 640], [$width, $height]);
    }

    public function test_gallery_webp_files_are_valid_webp(): void
    {
        $files = glob(public_path('images/gallery/*.webp'));
        $this->assertNotEmpty($files, 'expected WebP files under public/images/gallery');

        foreach ($files as $file) {
            $head = file_get_contents($file, false, null, 0, 12);
            $this->assertSame('RIFF', substr($head, 0, 4), basename($file) . ' is not a RIFF file');
            $this->assertSame('WEBP', substr($head, 8, 4), basename($file) . ' is not a WebP file');
        }
    }

    public function test_largest_flagged_images_are_recompressed(): void
    {
        // Thresholds sit just above the S2 recompressed sizes, so a
        // future re-save at the old (uncompressed) sizes fails loudly.
        $this->assertLessThanOrEqual(150000, filesize(public_path('images/gallery/office-cleaning-800.webp')));
        $this->assertLessThanOrEqual(125000, filesize(public_path('images/gallery/facade-scaffold-800.webp')));
        $this->assertLessThanOrEqual(65000, filesize(public_path('images/gallery/hotel-restaurant-800.webp')));
    }

    public function test_gallery_webp_total_stays_under_budget(): void
    {
        $total = array_sum(array_map(
            'filesize',
            glob(public_path('images/gallery/*.webp'))
        ));

        // Was 2,077,050 bytes before S2; recompressed total is 1,781,738.
        $this->assertLessThanOrEqual(1850000, $total);
    }

    public function test_recompressed_images_keep_their_dimensions(): void
    {
        // Filenames AND dimensions are unchanged — only the encoding
        // quality changed, so srcset/sizes and layouts are unaffected.
        $expected = [
            'office-cleaning-800.webp' => [800, 1067],
            'facade-scaffold-800.webp' => [800, 1067],
            'hotel-restaurant-800.webp' => [800, 446],
        ];

        foreach ($expected as $file => [$width, $height]) {
            [$w, $h] = getimagesize(public_path('images/gallery/' . $file));
            $this->assertSame([$width, $height], [$w, $h], $file . ' dimensions changed');
        }
    }
}
