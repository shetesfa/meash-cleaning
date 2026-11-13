<?php

namespace Database\Seeders;

use App\Models\Photo;
use Illuminate\Database\Seeder;

/**
 * Seeds the photos table with the exact 14 photos already live on the
 * website (5 real Meash photos + 9 generated), in all 4 sections:
 * hero slider (14), service cards (6), gallery (14), feature strip (3)
 * = 37 image places, matching the approved admin Photos sample.
 * Runs ONLY when the table is empty — it never overwrites admin edits,
 * and it never re-seeds after the owner deletes everything on purpose.
 * (Called from DatabaseSeeder; the entrypoint runs db:seed on deploy.)
 */
class PhotoSeeder extends Seeder
{
    public function run(): void
    {
        if (Photo::count() > 0) {
            $this->command->info('Photos table already has rows. Skipping PhotoSeeder.');

            return;
        }

        // The 14 live photos, in the exact order/captions/service codes
        // used by website.blade.php (5 real photos first, then 9 new).
        $photos = [
            ['file' => 'office-cleaning.jpg', 'code' => 'floor_ceramic', 'am' => 'የወለል ማሽን ጽዳት', 'en' => 'Floor machine cleaning'],
            ['file' => 'sofa-steam.jpg', 'code' => 'upholstery_carpet', 'am' => 'የሶፋ በእንፋሎት ጽዳት', 'en' => 'Sofa steam cleaning'],
            ['file' => 'villa-carpet.jpg', 'code' => 'facilities_general', 'am' => 'የሆቴል ላውንጅ ጥልቅ ጽዳት', 'en' => 'Hotel lounge deep clean'],
            ['file' => 'rope-access.jpg', 'code' => 'glass', 'am' => 'የረጃጅም ሕንፃ መስታወት ጽዳት', 'en' => 'High-rise glass cleaning'],
            ['file' => 'facade-scaffold.jpg', 'code' => 'post_construction', 'am' => 'የሕንፃ የፊት ገጽ ጽዳት', 'en' => 'Facade cleaning'],
            ['file' => 'living-room.jpg', 'code' => 'facilities_general', 'am' => 'የቤት ጥልቅ ጽዳት', 'en' => 'Home deep cleaning'],
            ['file' => 'hotel-restaurant.jpg', 'code' => 'facilities_general', 'am' => 'የሆቴል እና ሬስቶራንት ጽዳት', 'en' => 'Hotel & restaurant cleaning'],
            ['file' => 'office-polisher.jpg', 'code' => 'floor_ceramic', 'am' => 'የቢሮ ወለል ማሽን ጽዳት', 'en' => 'Office floor machine cleaning'],
            ['file' => 'bedroom.jpg', 'code' => 'facilities_general', 'am' => 'የመኝታ ቤት ጽዳት', 'en' => 'Bedroom cleaning'],
            ['file' => 'bathroom.jpg', 'code' => 'facilities_general', 'am' => 'የመታጠቢያ ቤት ጽዳት', 'en' => 'Bathroom cleaning'],
            ['file' => 'sofa-carpet-steam.jpg', 'code' => 'upholstery_carpet', 'am' => 'የሶፋ እና ካርፔት በእንፋሎት ጽዳት', 'en' => 'Sofa & carpet steam cleaning'],
            ['file' => 'windows-ladder.jpg', 'code' => 'glass', 'am' => 'የመስኮት ጽዳት', 'en' => 'Window cleaning'],
            ['file' => 'post-construction.jpg', 'code' => 'post_construction', 'am' => 'ከግንባታ በኋላ ጽዳት', 'en' => 'Post-construction cleaning'],
            ['file' => 'kitchen.jpg', 'code' => 'kitchen_dining', 'am' => 'የኩሽና ጽዳት', 'en' => 'Kitchen cleaning'],
        ];

        // Service code -> photo (the live service-card mapping).
        $servicePhoto = [
            'floor_ceramic' => 'office-cleaning.jpg',
            'facilities_general' => 'villa-carpet.jpg',
            'kitchen_dining' => 'kitchen.jpg',
            'post_construction' => 'post-construction.jpg',
            'glass' => 'rope-access.jpg',
            'upholstery_carpet' => 'sofa-steam.jpg',
        ];

        // Feature strip (3-step "How we deliver") photos, with the live
        // alt texts as their titles.
        $featurePhotos = [
            ['file' => 'facade-scaffold.jpg', 'am' => 'የመስታወት ጽዳት መፍትሔ የሚያዘጋጅ የሜሽ ሰራተኛ', 'en' => 'Meash cleaner preparing glass-cleaning solution'],
            ['file' => 'office-cleaning.jpg', 'am' => 'የሜሽ ቡድን በቢሮ ውስጥ እየሰራ', 'en' => 'Meash team at work in an office'],
            ['file' => 'villa-carpet.jpg', 'am' => 'በብሩህ የሆቴል ላውንጅ ውስጥ አዲስ የታጠበ ካርፔት', 'en' => 'Freshly shampooed carpet in a bright hotel lounge'],
        ];

        $byFile = [];
        foreach ($photos as $p) {
            $byFile[$p['file']] = $p;
        }

        $rows = [];
        foreach ($photos as $i => $p) {
            $rows[] = $this->row('/images/gallery/' . $p['file'], $p['en'], $p['am'], 'hero', $p['code'], $i);
        }
        foreach ($photos as $i => $p) {
            $rows[] = $this->row('/images/gallery/' . $p['file'], $p['en'], $p['am'], 'gallery', $p['code'], $i);
        }
        $order = 0;
        foreach ($servicePhoto as $code => $file) {
            $p = $byFile[$file];
            $rows[] = $this->row('/images/gallery/' . $file, $p['en'], $p['am'], 'service', $code, $order++);
        }
        foreach ($featurePhotos as $i => $p) {
            $rows[] = $this->row('/images/gallery/' . $p['file'], $p['en'], $p['am'], 'feature', null, $i);
        }

        foreach ($rows as $row) {
            Photo::create($row);
        }

        $this->command->info('PhotoSeeder: seeded ' . count($rows) . ' photo rows (hero 14, gallery 14, service 6, feature 3).');
    }

    protected function row(string $url, string $en, string $am, string $section, ?string $code, int $order): array
    {
        return [
            'url' => $url,
            'title_en' => $en,
            'title_am' => $am,
            'section' => $section,
            'service_code' => $code,
            'status' => Photo::STATUS_ADDED,
            'sort_order' => $order,
        ];
    }
}
