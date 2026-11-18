<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prompt 2: the three #reviews nav links (desktop menu, mobile menu,
 * footer) must hide together with the #reviews section when there are
 * no approved reviews, and show again when an approved review exists.
 *
 * The JS in website.blade.php (hideReviewsSection / loadPublicReviews)
 * keys off the /api/public/reviews response: [] hides, non-empty shows.
 * These tests pin that API contract plus the markup/JS wiring, so a
 * regression in either half fails here.
 */
class ReviewsLinksVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_zero_approved_reviews_returns_empty_list(): void
    {
        $customer = Customer::create(['full_name' => 'No Review Customer', 'phone' => '0911000001']);

        // An UNAPPROVED review must not count: the public list stays empty.
        Feedback::create([
            'customer_id' => $customer->id,
            'rating' => 5,
            'comment' => 'Waiting for approval',
            'source' => 'web',
            'is_approved' => false,
        ]);

        $res = $this->getJson('/api/public/reviews');
        $res->assertStatus(200);
        $res->assertExactJson([]);
    }

    public function test_one_approved_review_returns_one_item(): void
    {
        $customer = Customer::create(['full_name' => 'Happy Customer', 'phone' => '0911000002']);

        Feedback::create([
            'customer_id' => $customer->id,
            'rating' => 5,
            'comment' => 'Great cleaning service!',
            'source' => 'web',
            'is_approved' => true,
        ]);

        $res = $this->getJson('/api/public/reviews');
        $res->assertStatus(200);
        $res->assertJsonCount(1);
        $res->assertJsonFragment(['comment' => 'Great cleaning service!']);
    }

    public function test_homepage_has_three_reviews_links_wired_to_the_hide_logic(): void
    {
        $res = $this->get('/');
        $res->assertStatus(200);
        $html = $res->getContent();

        // Exactly the three links carry the marker class: desktop nav,
        // mobile drawer, footer.
        $this->assertSame(3, substr_count($html, 'js-reviews-link'));

        // The JS hides the links together with the section …
        $this->assertStringContainsString(
            "document.querySelectorAll('.js-reviews-link').forEach(function (el) { el.classList.add('hidden'); });",
            $html
        );
        // … and shows them again when approved reviews exist.
        $this->assertStringContainsString(
            "document.querySelectorAll('.js-reviews-link').forEach(function (el) { el.classList.remove('hidden'); });",
            $html
        );
    }
}
