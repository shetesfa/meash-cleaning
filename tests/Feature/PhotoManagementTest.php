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
}
