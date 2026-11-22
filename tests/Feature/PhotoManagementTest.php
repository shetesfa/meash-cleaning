<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->owner = User::where('role', 'owner')->first() ?? User::factory()->create([
            'role' => 'owner',
            'phone' => '0911000001',
            'email' => 'test_owner_' . uniqid() . '@meash.com',
        ]);
    }

    /**
     * Batch 19: the logo migration inserts ONE admin-managed logo row,
     * and its presence must not stop PhotoSeeder from seeding the 37
     * website photo rows on a fresh database (migrations run before
     * db:seed on deploy).
     */
    public function test_logo_row_exists_and_seeder_still_seeds_37_photos(): void
    {
        $this->assertSame(1, Photo::where('section', 'logo')->count());
        $this->assertDatabaseHas('photos', [
            'section' => 'logo',
            'url' => '/logo.jpg',
            'status' => Photo::STATUS_ADDED,
            'title_en' => 'Website logo',
        ]);
        $this->assertSame(37, Photo::where('section', '!=', 'logo')->count());
        $this->assertSame(38, Photo::count());
    }

    /**
     * Batch 19: running the logo migration again must not create a
     * second logo row.
     */
    public function test_logo_migration_up_is_idempotent(): void
    {
        $migration = require database_path('migrations/2026_11_17_000001_add_logo_photo_row.php');
        $migration->up();
        $migration->up();

        $this->assertSame(1, Photo::where('section', 'logo')->count());
    }

    /**
     * Batch 19: replacing the image of a photo that appears in several
     * places (hero + gallery + service card copies share one url)
     * updates ALL of them, and the response says how many places.
     */
    public function test_updating_a_photo_propagates_to_all_rows_with_the_same_url(): void
    {
        $sharedUrl = '/images/gallery/shared-test.jpg';
        $hero = Photo::create(['url' => $sharedUrl, 'title_en' => 'Shared hero', 'section' => 'hero', 'status' => 'added', 'sort_order' => 90]);
        $gallery = Photo::create(['url' => $sharedUrl, 'title_en' => 'Shared gallery', 'section' => 'gallery', 'status' => 'added', 'sort_order' => 90]);
        $service = Photo::create(['url' => $sharedUrl, 'title_en' => 'Shared service', 'section' => 'service', 'service_code' => 'glass', 'status' => 'added', 'sort_order' => 90]);
        $other = Photo::create(['url' => '/images/gallery/other-test.jpg', 'title_en' => 'Other', 'section' => 'gallery', 'status' => 'added', 'sort_order' => 91]);

        $res = $this->actingAs($this->owner)->putJson('/api/admin/website/photos/' . $hero->id, [
            'url' => 'https://example.com/new-photo.jpg',
        ]);

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);
        $res->assertJsonPath('message', 'Photo updated in 3 places / ፎቶው በ3 ቦታዎች ተቀይሯል');

        foreach ([$hero, $gallery, $service] as $photo) {
            $this->assertSame('https://example.com/new-photo.jpg', $photo->fresh()->url);
        }
        $this->assertSame('/images/gallery/other-test.jpg', $other->fresh()->url);
        $this->assertSame(0, Photo::where('url', $sharedUrl)->count());
    }

    /**
     * Batch 19: a captions-only update must not touch the image or
     * any sibling row sharing the url.
     */
    public function test_caption_only_update_does_not_touch_sibling_rows(): void
    {
        $sharedUrl = '/images/gallery/shared-caption.jpg';
        $hero = Photo::create(['url' => $sharedUrl, 'title_en' => 'Hero caption', 'section' => 'hero', 'status' => 'added', 'sort_order' => 92]);
        $gallery = Photo::create(['url' => $sharedUrl, 'title_en' => 'Gallery caption', 'section' => 'gallery', 'status' => 'added', 'sort_order' => 92]);

        $res = $this->actingAs($this->owner)->putJson('/api/admin/website/photos/' . $hero->id, [
            'title_en' => 'New hero caption',
        ]);

        $res->assertStatus(200);
        $res->assertJsonPath('message', 'Photo updated.');
        $this->assertSame('New hero caption', $hero->fresh()->title_en);
        $this->assertSame($sharedUrl, $gallery->fresh()->url);
        $this->assertSame('Gallery caption', $gallery->fresh()->title_en);
    }

    /**
     * R6: before/after pair fields are stored on create, editable on
     * update, and rejected when the role is not before/after.
     */
    public function test_pair_fields_store_update_and_validate(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('photos', 'pair_id'));
        $this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumn('photos', 'role'));

        $res = $this->actingAs($this->owner)->postJson('/api/admin/website/photos', [
            'url' => 'https://example.com/sofa-before.jpg',
            'title_en' => 'Sofa before',
            'section' => 'gallery',
            'pair_id' => 'sofa-1',
            'role' => 'before',
        ]);
        $res->assertStatus(201);
        $this->assertDatabaseHas('photos', ['pair_id' => 'sofa-1', 'role' => 'before']);
        $this->assertSame('sofa-1', $res->json('photo.pair_id'));
        $this->assertSame('before', $res->json('photo.role'));

        $photo = Photo::where('pair_id', 'sofa-1')->first();
        $res = $this->actingAs($this->owner)->putJson('/api/admin/website/photos/' . $photo->id, [
            'role' => 'after',
        ]);
        $res->assertStatus(200);
        $this->assertSame('after', $photo->fresh()->role);
        $this->assertSame('sofa-1', $photo->fresh()->pair_id);

        $res = $this->actingAs($this->owner)->putJson('/api/admin/website/photos/' . $photo->id, [
            'role' => 'sideways',
        ]);
        $res->assertStatus(422);
    }

    /**
     * 2026-11-23: deleting ONLY the 9 AI rows must remove exactly
     * those photos from the site while the 5 real photos keep showing.
     * (Tesfa deleted the Gemini-generated rows in admin; the old
     * `count() > 0` gates resurrected them from the hardcoded lists.)
     */
    public function test_deleting_only_ai_rows_keeps_real_photos_and_removes_ai_photos(): void
    {
        $aiFiles = [
            'living-room.jpg', 'hotel-restaurant.jpg', 'office-polisher.jpg',
            'bedroom.jpg', 'bathroom.jpg', 'sofa-carpet-steam.jpg',
            'windows-ladder.jpg', 'post-construction.jpg', 'kitchen.jpg',
        ];
        $realFiles = [
            'office-cleaning.jpg', 'villa-carpet.jpg', 'sofa-steam.jpg',
            'rope-access.jpg', 'facade-scaffold.jpg',
        ];

        Photo::where('section', '!=', 'logo')->get()->each(function (Photo $photo) use ($aiFiles) {
            foreach ($aiFiles as $aiFile) {
                if (str_ends_with($photo->url, $aiFile)) {
                    $photo->delete();
                }
            }
        });

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        foreach ($realFiles as $file) {
            $this->assertStringContainsString($file, $html, "Real photo {$file} must still show.");
        }
        foreach ($aiFiles as $file) {
            $this->assertStringNotContainsString($file, $html, "Deleted AI photo {$file} must not reappear.");
        }
    }

    /**
     * 2026-11-23: a section with zero Added rows shows no fallback
     * photos — the DB is the truth, fallbacks apply only on a DB error.
     */
    public function test_section_with_zero_added_rows_shows_no_fallback_photos(): void
    {
        Photo::where('section', '!=', 'logo')->get()->each->delete();

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        foreach ([
            'office-cleaning.jpg', 'villa-carpet.jpg', 'sofa-steam.jpg',
            'rope-access.jpg', 'facade-scaffold.jpg', 'living-room.jpg',
            'hotel-restaurant.jpg', 'office-polisher.jpg', 'bedroom.jpg',
            'bathroom.jpg', 'sofa-carpet-steam.jpg', 'windows-ladder.jpg',
            'post-construction.jpg', 'kitchen.jpg',
        ] as $file) {
            $this->assertStringNotContainsString($file, $html, "Fallback photo {$file} must not show with zero Added rows.");
        }

        // The page itself still renders: services, booking, prices.
        $this->assertStringContainsString('Get Quote', $html);
        $this->assertStringContainsString('Book Now', $html);
    }

    /**
     * 2026-11-23: DB rows are the source of truth — a single hero /
     * gallery / service row shows its own photo, never a fallback.
     */
    public function test_homepage_with_only_db_rows_shows_db_photos_not_fallbacks(): void
    {
        Photo::where('section', '!=', 'logo')->get()->each->delete();

        Photo::create(['url' => 'https://example.com/real-hero.jpg', 'title_en' => 'Real hero', 'section' => 'hero', 'status' => 'added', 'sort_order' => 0]);
        Photo::create(['url' => 'https://example.com/real-gallery.jpg', 'title_en' => 'Real gallery', 'section' => 'gallery', 'status' => 'added', 'sort_order' => 0]);
        Photo::create(['url' => 'https://example.com/real-glass.jpg', 'title_en' => 'Real glass', 'section' => 'service', 'service_code' => 'glass', 'status' => 'added', 'sort_order' => 0]);

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        $this->assertStringContainsString('real-hero.jpg', $html);
        $this->assertStringContainsString('real-gallery.jpg', $html);
        $this->assertStringContainsString('real-glass.jpg', $html);
        $this->assertStringNotContainsString('living-room.jpg', $html);
        $this->assertStringNotContainsString('office-cleaning.jpg', $html);
    }

    /**
     * 2026-11-23: the feature strip renders only the rows that exist —
     * one row means one step photo and no undefined-index error (the
     * old code padded missing steps with fallback photos).
     */
    public function test_feature_strip_with_one_row_renders_without_error(): void
    {
        Photo::where('section', '!=', 'logo')->get()->each->delete();

        Photo::create(['url' => 'https://example.com/real-step.jpg', 'title_en' => 'Real step', 'section' => 'feature', 'status' => 'added', 'sort_order' => 0]);

        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        $this->assertStringContainsString('real-step.jpg', $html);
        $this->assertStringNotContainsString('facade-scaffold.jpg', $html);
    }

    /**
     * 2026-11-23: the bulk "Add all Not added to site" endpoint
     * publishes every Not added photo at once and clears the homepage
     * cache, so the photos show on the site immediately.
     */
    public function test_add_all_publishes_every_not_added_photo(): void
    {
        // Start from an empty photo table so the feature strip (which
        // shows at most 3 rows) renders exactly the rows this test adds.
        Photo::where('section', '!=', 'logo')->get()->each->delete();

        Photo::create(['url' => 'https://example.com/bulk-hero.jpg', 'title_en' => 'Bulk hero', 'section' => 'hero', 'status' => 'not_added', 'sort_order' => 50]);
        Photo::create(['url' => 'https://example.com/bulk-gallery.jpg', 'title_en' => 'Bulk gallery', 'section' => 'gallery', 'status' => 'not_added', 'sort_order' => 50]);
        Photo::create(['url' => 'https://example.com/bulk-feature.jpg', 'title_en' => 'Bulk feature', 'section' => 'feature', 'status' => 'not_added', 'sort_order' => 50]);

        $res = $this->actingAs($this->owner)->postJson('/api/admin/website/photos/add-all');
        $res->assertStatus(200);
        $res->assertJson(['success' => true]);
        $res->assertJsonPath('added_count', 3);

        $this->assertSame(0, Photo::where('status', 'not_added')->count());

        // The newly published photos show on the homepage right away.
        $home = $this->get('/');
        $home->assertStatus(200);
        $home->assertSee('bulk-hero.jpg');
        $home->assertSee('bulk-gallery.jpg');
        $home->assertSee('bulk-feature.jpg');
    }

    /**
     * 2026-11-23: the bulk "Add all" button's two-tap confirm silently
     * never fired — the armed flag expired after 4s, so a slow second
     * tap just re-armed instead of publishing (observed live, tried
     * 3 times). The armed state is now a window-level timestamp that
     * survives re-renders and lasts 60s, and the Photos tab renders
     * the armed button from it. There is no JS test harness in this
     * repo, so this test guards the fix at the template level.
     */
    public function test_add_all_button_arm_state_survives_rerender_and_lasts_60s(): void
    {
        $blade = file_get_contents(base_path('resources/views/app.blade.php'));
        $this->assertNotFalse($blade);

        // Armed state is a window-level timestamp, not a plain boolean.
        $this->assertStringContainsString('window._photoAddAllArmedAt', $blade);
        $this->assertStringContainsString('ADD_ALL_ARM_MS', $blade);
        $this->assertStringNotContainsString('window._photoAddAllArmed = true;', $blade);
        $this->assertStringNotContainsString('window._photoAddAllArmed = false;', $blade);

        // The Photos tab renders the armed button from that state, so a
        // re-render between the two taps keeps the confirm visible.
        $this->assertStringContainsString("isAddAllArmed() ? 'እርግጠኛ ነህ?", $blade);

        // The confirm handler itself: the second tap checks the
        // window-level armed state and fires the endpoint, with no
        // 4-second auto-disarm inside it.
        $start = strpos($blade, 'async function addAllPhotosToSite');
        $this->assertNotFalse($start);
        $end = strpos($blade, 'async function deletePhoto', $start);
        $this->assertNotFalse($end);
        $fn = substr($blade, $start, $end - $start);
        $this->assertStringContainsString('isAddAllArmed()', $fn);
        $this->assertStringContainsString('/api/admin/website/photos/add-all', $fn);
        $this->assertStringNotContainsString('4000', $fn);
    }

    /**
     * 2026-11-23: the logo keeps its own fallback — deleting the logo
     * row still shows the real /logo.jpg file (unlike gallery photos).
     */
    public function test_logo_still_falls_back_to_logo_file(): void
    {
        Photo::where('section', 'logo')->delete();

        $res = $this->get('/');
        $res->assertStatus(200);
        $res->assertSee('/logo.jpg');
    }
}
