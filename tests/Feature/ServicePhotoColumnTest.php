<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicePhotoColumnTest extends TestCase
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
     * The admin Services table Photo column: services/all returns the
     * service-section photo row's image for a service that has one —
     * the same image the public service card shows.
     */
    public function test_services_all_returns_photo_url_for_service_with_service_photo_row(): void
    {
        $res = $this->actingAs($this->owner)->getJson('/api/services/all');

        $res->assertStatus(200);
        $services = collect($res->json());

        $glass = $services->firstWhere('code', 'glass');
        $this->assertNotNull($glass);
        $this->assertSame('/images/gallery/rope-access.jpg', $glass['photo_url']);

        $floor = $services->firstWhere('code', 'floor_ceramic');
        $this->assertNotNull($floor);
        $this->assertSame('/images/gallery/office-cleaning.jpg', $floor['photo_url']);
    }

    /**
     * No photo row means photo_url is null — never a hardcoded fallback
     * file (e3ecd63: deleted photos stay deleted). This is the live
     * state for kitchen_dining and post_construction, whose photos
     * were deleted AI images.
     */
    public function test_services_all_returns_null_not_fallback_for_service_without_photo_row(): void
    {
        Photo::where('section', 'service')
            ->whereIn('service_code', ['kitchen_dining', 'post_construction'])
            ->delete();

        $res = $this->actingAs($this->owner)->getJson('/api/services/all');

        $res->assertStatus(200);
        $services = collect($res->json());

        foreach (['kitchen_dining', 'post_construction'] as $code) {
            $service = $services->firstWhere('code', $code);
            $this->assertNotNull($service, "service {$code} missing from response");
            $this->assertArrayHasKey('photo_url', $service);
            $this->assertNull($service['photo_url'], "service {$code} must not fall back to a file");
        }

        // A service that still has its row is unaffected.
        $glass = $services->firstWhere('code', 'glass');
        $this->assertSame('/images/gallery/rope-access.jpg', $glass['photo_url']);
    }

    /**
     * A Not added service row must not surface: only Added rows are
     * the service's card photo.
     */
    public function test_services_all_ignores_not_added_service_photo_rows(): void
    {
        Photo::where('section', 'service')->where('service_code', 'glass')->delete();
        Photo::create([
            'url' => '/images/gallery/hidden-test.jpg',
            'title_en' => 'Hidden glass',
            'section' => 'service',
            'service_code' => 'glass',
            'status' => Photo::STATUS_NOT_ADDED,
            'sort_order' => 0,
        ]);

        $res = $this->actingAs($this->owner)->getJson('/api/services/all');

        $res->assertStatus(200);
        $glass = collect($res->json())->firstWhere('code', 'glass');
        $this->assertNull($glass['photo_url']);
    }
}
