<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\WebsiteSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminWebsiteController extends Controller
{
    /**
     * Get all website settings
     */
    public function getSettings(): JsonResponse
    {
        $settings = WebsiteSetting::getAllKeyValue();
        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Update website settings (contact info, slogan, text, etc.)
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_slogan' => 'nullable|string|max:255',
            'company_slogan_am' => 'nullable|string|max:255',
            'contact_phone_1' => 'nullable|string|max:32',
            'contact_phone_2' => 'nullable|string|max:32',
            'company_email' => 'nullable|email|max:128',
            'company_address' => 'nullable|string|max:255',
            'company_address_am' => 'nullable|string|max:255',
            'telegram_channel' => 'nullable|string|max:255',
            'telegram_bot' => 'nullable|string|max:255',
            'tiktok' => 'nullable|string|max:255',
            'about_text_en' => 'nullable|string',
            'about_text_am' => 'nullable|string',
            'gallery_images' => 'nullable|string',
        ]);

        foreach ($validated as $key => $val) {
            WebsiteSetting::setVal($key, $val, 'general');
        }

        return response()->json([
            'success' => true,
            'message' => 'የድረ-ገጽ ቅንብሮች በተሳካ ሁኔታ ተዘምነዋል! (Website settings updated successfully)',
            'settings' => WebsiteSetting::getAllKeyValue(),
        ]);
    }

    /**
     * List all customer feedbacks/reviews for admin
     */
    public function getFeedbacks(): JsonResponse
    {
        $feedbacks = Feedback::with('customer:id,full_name,phone')
            ->latest()
            ->paginate(20);

        return response()->json($feedbacks);
    }

    /**
     * Admin manually adds a review/testimonial
     */
    public function storeFeedback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
            'source' => 'nullable|string|max:32',
            'is_approved' => 'nullable|boolean',
        ]);

        // Find or create customer by name
        $customer = Customer::firstOrCreate(
            ['full_name' => $validated['customer_name']],
            [
                'customer_code' => Customer::generateNextCode(),
                'phone' => '09' . rand(10000000, 99999999),
                'address' => 'Addis Ababa',
                'subcity' => 'Bole',
            ]
        );

        $feedback = Feedback::create([
            'customer_id' => $customer->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'source' => $validated['source'] ?? 'web',
            'is_approved' => $validated['is_approved'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'አስተያየት በተሳካ ሁኔታ ተመዝግቧል! (Review added successfully)',
            'feedback' => $feedback->load('customer:id,full_name'),
        ]);
    }

    /**
     * Toggle approve/hide review
     */
    public function toggleFeedbackApproval(int $id): JsonResponse
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->is_approved = !$feedback->is_approved;
        $feedback->save();

        return response()->json([
            'success' => true,
            'message' => $feedback->is_approved ? 'አስተያየቱ በድረ-ገጹ እንዲታይ ጸድቋል!' : 'አስተያየቱ ከድረ-ገጽ ተደብቋል!',
            'is_approved' => $feedback->is_approved,
        ]);
    }

    /**
     * Delete a review
     */
    public function deleteFeedback(int $id): JsonResponse
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->delete();

        return response()->json([
            'success' => true,
            'message' => 'አስተያየቱ ተሰርዟል! (Review deleted)',
        ]);
    }
}
