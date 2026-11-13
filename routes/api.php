<?php

use App\Http\Controllers\Api\AdminWebsiteController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerCareController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\NotificationMarketingController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OutdoorSalesController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\PublicWebsiteController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TelegramController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MEASH CLEANING SOLUTION - Central API Routes
|--------------------------------------------------------------------------
*/

// Health Check / Ping
Route::get('/ping', function () {
    return response()->json([
        'status' => 'online',
        'system' => 'Meash Cleaning Solution',
        'time' => now()->toIso8601String(),
    ]);
});

// 1. PUBLIC WEBSITE & CUSTOMER BOOKING
Route::prefix('public')->group(function () {
    Route::get('/settings', [PublicWebsiteController::class, 'settings'])->middleware('throttle:60,1');
    Route::get('/services', [PublicWebsiteController::class, 'services'])->middleware('throttle:60,1');
    Route::post('/book', [PublicWebsiteController::class, 'book'])->middleware('throttle:15,1');
    Route::match(['get', 'post'], '/track-order', [PublicWebsiteController::class, 'trackOrder'])->middleware('throttle:10,1');
    Route::post('/reschedule-order', [PublicWebsiteController::class, 'rescheduleOrder'])->middleware('throttle:15,1');
    Route::get('/reviews', [PublicWebsiteController::class, 'publicReviews'])->middleware('throttle:60,1');
    Route::post('/reviews', [PublicWebsiteController::class, 'submitReview'])->middleware('throttle:10,1');
    Route::post('/quick-message', [PublicWebsiteController::class, 'quickMessage'])->middleware('throttle:15,1');
});

// 2. TELEGRAM ENGINE (Bot, Inline Query @meash, Mini App)
Route::prefix('telegram')->group(function () {
    Route::post('/webhook', [TelegramController::class, 'webhook'])->middleware('throttle:120,1');
    Route::post('/miniapp/book', [TelegramController::class, 'miniAppBooking'])->middleware('throttle:15,1');
    // NOTE: GET /telegram/status was public (it leaks bot identity +
    // webhook info) — it now lives in the owner-only group below (L).
});

// 3. AUTHENTICATION
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('throttle:5,1');
    });
});

// 4. PROTECTED OPERATIONAL API (Sanctum Authenticated)
Route::middleware('auth:sanctum')->group(function () {

    // A. Dashboards
    Route::prefix('dashboard')->group(function () {
        Route::get('/owner', [DashboardController::class, 'ownerSummary'])->middleware('role:owner');
        Route::get('/reception', [DashboardController::class, 'receptionOverview'])->middleware('role:owner,reception');
    });

    // B. Customers
    Route::apiResource('customers', CustomerController::class)->except(['destroy'])->middleware('role:owner,reception');

    // C. Services
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/all', [ServiceController::class, 'all'])->middleware('role:owner,reception');
    Route::post('/services', [ServiceController::class, 'store'])->middleware('role:owner,reception');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->middleware('role:owner,reception');

    // D. Orders (Multi-item)
    Route::get('/orders', [OrderController::class, 'index'])->middleware('role:owner,reception,cleaner,finance');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->middleware('role:owner,reception,cleaner,finance');
    Route::post('/orders', [OrderController::class, 'store'])->middleware('role:owner,reception');
    Route::match(['put', 'patch'], '/orders/{order}', [OrderController::class, 'update'])->middleware('role:owner,reception');
    Route::match(['put', 'patch', 'post'], '/orders/{order}/update', [OrderController::class, 'update'])->middleware('role:owner,reception');
    Route::post('/orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('role:owner,reception,cleaner');
    Route::post('/orders/{order}/assign-team', [OrderController::class, 'assignTeam'])->middleware('role:owner,reception');
    Route::post('/orders/{order}/submit-payment', [OrderController::class, 'submitPayment'])->middleware('role:owner,reception,cleaner');
    Route::post('/orders/{order}/approve-payment', [OrderController::class, 'approvePayment'])->middleware('role:owner,reception,finance');
    Route::post('/orders/{order}/payments/{payment}/approve', [OrderController::class, 'approvePayment'])->middleware('role:owner,reception,finance');
    Route::post('/orders/{order}/verify-payment', [OrderController::class, 'verifyPayment'])->middleware('role:owner,reception,finance');
    Route::post('/orders/{order}/payments/{payment}/verify', [OrderController::class, 'verifyPayment'])->middleware('role:owner,reception,finance');
    Route::post('/orders/{order}/reject-payment', [OrderController::class, 'rejectPayment'])->middleware('role:owner,reception,finance');
    Route::post('/orders/{order}/payment', [OrderController::class, 'recordPayment'])->middleware('role:owner,reception,finance');
    Route::post('/orders/{order}/payments', [OrderController::class, 'recordPayment'])->middleware('role:owner,reception,finance');
    Route::post('/orders/{order}/refund', [FinanceController::class, 'recordRefund'])->middleware('role:owner,finance');
    Route::post('/orders/{order}/postpone', [OrderController::class, 'postpone'])->middleware('role:owner,reception');
    Route::post('/orders/{order}/send-review-request', [OrderController::class, 'sendReviewRequest'])->middleware('role:owner,reception');

    // E. Appointments & Calendar
    Route::get('/appointments', [AppointmentController::class, 'index'])->middleware('role:owner,reception,cleaner');
    Route::post('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->middleware('role:owner,reception');

    // F. Cleaning Teams & Cleaner Mobile View
    Route::get('/teams', [TeamController::class, 'index'])->middleware('role:owner,reception,cleaner');
    Route::post('/teams', [TeamController::class, 'store'])->middleware('role:owner,reception');
    Route::get('/employees', [TeamController::class, 'getEmployees'])->middleware('role:owner,reception');
    Route::post('/employees', [TeamController::class, 'storeEmployee'])->middleware('role:owner');
    Route::get('/employees/{id}/history', [TeamController::class, 'getEmployeeHistory'])->middleware('role:owner,reception,finance');
    Route::get('/teams/my-jobs', [TeamController::class, 'myJobs'])->middleware('role:cleaner,owner');
    Route::post('/teams/jobs/{order}/action', [TeamController::class, 'handleJobAction'])->middleware('role:cleaner,owner');
    Route::post('/teams/location', [TeamController::class, 'updateLocation'])->middleware('role:cleaner,owner');
    Route::post('/teams/update-location', [TeamController::class, 'updateLocation'])->middleware('role:cleaner,owner');

    // Subscriptions (Recurring Cleaning Model)
    Route::prefix('subscriptions')->middleware('role:owner,reception')->group(function () {
        Route::get('/', [SubscriptionController::class, 'index']);
        Route::post('/', [SubscriptionController::class, 'store']);
        Route::match(['post', 'patch'], '/{subscription}/status', [SubscriptionController::class, 'updateStatus']);
    });

    // G. Outdoor Sales CRM
    Route::prefix('sales')->middleware('role:owner,sales')->group(function () {
        Route::get('/organizations', [OutdoorSalesController::class, 'organizations']);
        Route::post('/organizations', [OutdoorSalesController::class, 'storeOrganization']);
        Route::get('/visits', [OutdoorSalesController::class, 'visits']);
        Route::post('/visits', [OutdoorSalesController::class, 'storeVisit']);
        Route::post('/visits/{visit}/stage', [OutdoorSalesController::class, 'updateVisitStage']);
        Route::delete('/visits/{visit}', [OutdoorSalesController::class, 'destroyVisit']);
        Route::get('/pipeline', [OutdoorSalesController::class, 'pipeline']);
        Route::get('/proformas', [OutdoorSalesController::class, 'proformas']);
        Route::post('/proformas', [OutdoorSalesController::class, 'storeProforma']);
        Route::get('/contracts', [OutdoorSalesController::class, 'contracts']);
        Route::post('/contracts', [OutdoorSalesController::class, 'storeContract']);
    });

    // H. Finance & Reports
    Route::prefix('finance')->group(function () {
        Route::get('/expenses', [FinanceController::class, 'expenses'])->middleware('role:owner,finance');
        Route::post('/expenses', [FinanceController::class, 'storeExpense'])->middleware('role:owner,finance');
        Route::post('/payroll', [FinanceController::class, 'recordPayroll'])->middleware('role:owner,finance');
        Route::get('/payments', [FinanceController::class, 'payments'])->middleware('role:owner,reception,finance');
        Route::post('/payments/{payment}/verify', [FinanceController::class, 'verifyPayment'])->middleware('role:owner,reception,finance');
        Route::get('/profit-report', [FinanceController::class, 'profitReport'])->middleware('role:owner,finance');
        Route::post('/refund/{order}', [FinanceController::class, 'recordRefund'])->middleware('role:owner,finance');
    });

    // I. Customer Care (Follow-ups, Feedback, Complaints, 1-to-1 Messaging)
    Route::prefix('care')->middleware('role:owner,reception')->group(function () {
        Route::get('/followups', [CustomerCareController::class, 'followups']);
        Route::post('/followups/generate', [CustomerCareController::class, 'generateFollowups']);
        Route::put('/followups/{followup}', [CustomerCareController::class, 'updateFollowup']);
        Route::match(['post', 'put', 'patch'], '/followups/{followup}/complete', [CustomerCareController::class, 'completeFollowup']);
        Route::get('/feedbacks', [CustomerCareController::class, 'feedbacks']);
        Route::post('/feedbacks', [CustomerCareController::class, 'storeFeedback']);
        Route::post('/feedbacks/{feedback}/approve', [CustomerCareController::class, 'approveFeedback']);
        Route::get('/complaints', [CustomerCareController::class, 'complaints']);
        Route::post('/complaints', [CustomerCareController::class, 'storeComplaint']);
        Route::post('/complaints/{complaint}/resolve', [CustomerCareController::class, 'resolveComplaint']);
        Route::post('/send-sms', [CustomerCareController::class, 'sendSms']);
        Route::post('/send-telegram', [CustomerCareController::class, 'sendTelegram']);
    });

    Route::prefix('customer-care')->middleware('role:owner,reception')->group(function () {
        Route::post('/send-sms', [CustomerCareController::class, 'sendSms']);
        Route::post('/send-telegram', [CustomerCareController::class, 'sendTelegram']);
    });

    // J. Notifications & Marketing
    Route::get('/notifications', [NotificationMarketingController::class, 'notifications']);
    Route::post('/notifications/{notification}/read', [NotificationMarketingController::class, 'markAsRead']);
    Route::get('/campaigns', [NotificationMarketingController::class, 'campaigns'])->middleware('role:owner');
    Route::post('/campaigns', [NotificationMarketingController::class, 'storeCampaign'])->middleware('role:owner');
    Route::post('/campaigns/{campaign}/send', [NotificationMarketingController::class, 'sendCampaign'])->middleware('role:owner');
    Route::post('/notifications/send-direct-sms', [NotificationMarketingController::class, 'sendDirectSms'])->middleware('role:owner,reception');
    Route::get('/sms/status', [NotificationMarketingController::class, 'smsStatus']);

    // Telegram Broadcast Center (Bulk Import, Selection, Safe Dispatch, Contact Management)
    Route::prefix('telegram-broadcast')->middleware('role:owner,reception')->group(function () {
        Route::get('/recipients', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'getRecipients']);
        Route::post('/add-recipient', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'addSingleRecipient']);
        Route::put('/recipients/{id}', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'updateRecipient']);
        Route::delete('/recipients/{id}', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'deleteRecipient']);
        Route::post('/recipients/bulk-action', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'bulkActionRecipients']);
        Route::get('/export', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'exportRecipients']);
        Route::post('/import', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'bulkImportRecipients']);
        Route::post('/dispatch', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'dispatchBroadcast']);
        Route::post('/send-broadcast', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'sendBroadcast']);
        Route::post('/create-sms-session', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'createSmsSession']);
        Route::get('/campaign-status/{id}', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'getCampaignStatus']);
    });

    // K. Website Settings & Content CMS
    Route::prefix('admin/website')->middleware('role:owner,reception')->group(function () {
        Route::get('/settings', [AdminWebsiteController::class, 'getSettings']);
        Route::post('/settings', [AdminWebsiteController::class, 'updateSettings']);
        Route::get('/feedbacks', [AdminWebsiteController::class, 'getFeedbacks']);
        Route::post('/feedbacks', [AdminWebsiteController::class, 'storeFeedback']);
        Route::put('/feedbacks/{id}/toggle-approve', [AdminWebsiteController::class, 'toggleFeedbackApproval']);
        Route::delete('/feedbacks/{id}', [AdminWebsiteController::class, 'deleteFeedback']);

        // Website Photos (admin Photos page — hero slider, service
        // cards, gallery, feature strip). Same Sanctum + role
        // protection as the rest of the Website CMS.
        Route::get('/photos', [PhotoController::class, 'index']);
        Route::post('/photos', [PhotoController::class, 'store']);
        Route::match(['put', 'patch'], '/photos/{photo}', [PhotoController::class, 'update']);
        Route::post('/photos/{photo}/toggle-status', [PhotoController::class, 'toggleStatus']);
        Route::delete('/photos/{photo}', [PhotoController::class, 'destroy']);
    });

    // K. Offline-First Synchronization & Conflicts
    Route::prefix('sync')->group(function () {
        Route::post('/batch', [SyncController::class, 'batchSync']);
        Route::get('/conflicts', [SyncController::class, 'listConflicts'])->middleware('role:owner,reception');
        Route::post('/conflicts/{conflict}/resolve', [SyncController::class, 'resolveConflict'])->middleware('role:owner,reception');
        Route::post('/quarantine/export', [SyncController::class, 'exportQuarantined'])->middleware('role:owner');
        Route::post('/quarantine/recover', [SyncController::class, 'recoverQuarantined'])->middleware('role:owner');
    });

    // L. Telegram Administrative Operations (Strictly Owner Only)
    Route::prefix('telegram')->middleware('role:owner')->group(function () {
        Route::get('/status', [TelegramController::class, 'getWebhookStatus']);
        Route::post('/set-webhook', [TelegramController::class, 'setRenderWebhook']);
        Route::post('/send-test', [TelegramController::class, 'sendTestMessage']);
    });
});

// M. Public Token-Authenticated iPhone / Android SMS Bridge Endpoints
Route::prefix('sms-bridge')->group(function () {
    Route::get('/pull-queue', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'pullSmsQueue'])->middleware('throttle:120,1');
    Route::post('/report-status', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'reportSmsStatus'])->middleware('throttle:120,1');
    Route::get('/launch/{campaign_id}', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'launchSmsBridge'])->middleware('throttle:120,1');
    // Public token-checked campaign status for the phone launch page
    // (the phone has no admin token for the auth:sanctum route).
    Route::get('/campaign-status/{campaign_id}', [\App\Http\Controllers\Api\TelegramBroadcastController::class, 'bridgeCampaignStatus'])->middleware('throttle:120,1');
});

