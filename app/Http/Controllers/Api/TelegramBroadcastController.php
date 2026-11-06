<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\TelegramUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBroadcastController extends Controller
{
    /**
     * Get all broadcast recipients (Telegram Users + Customers with telegram_user_id)
     */
    public function getRecipients(Request $request): JsonResponse
    {
        $search = $request->query('search', '');
        
        $tgQuery = TelegramUser::query();
        if (!empty($search)) {
            $tgQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        $tgUsers = $tgQuery->orderBy('last_interaction_at', 'desc')->get()->map(function ($u) {
            $fullName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
            if (empty($fullName)) {
                $fullName = $u->username ? '@' . $u->username : 'የቴሌግራም ደንበኛ #' . $u->telegram_id;
            }
            return [
                'id' => 'tg_' . $u->id,
                'db_id' => $u->id,
                'source' => 'telegram_user',
                'telegram_id' => (string)$u->telegram_id,
                'name' => $fullName,
                'username' => $u->username ? '@' . ltrim($u->username, '@') : '',
                'phone' => $u->phone_number ?: 'ያልተመዘገበ',
                'last_active' => $u->last_interaction_at ? $u->last_interaction_at->diffForHumans() : 'በቅርቡ',
                'is_active' => true,
            ];
        });

        // Also merge Customers that have telegram_user_id but not in TelegramUser
        $custWithTg = Customer::whereNotNull('telegram_user_id')
            ->where('telegram_user_id', '!=', '')
            ->get();

        $existingTgIds = $tgUsers->pluck('telegram_id')->toArray();
        $mergedList = $tgUsers->toArray();

        foreach ($custWithTg as $c) {
            $chatId = (string)$c->telegram_user_id;
            if (!in_array($chatId, $existingTgIds)) {
                $mergedList[] = [
                    'id' => 'cust_' . $c->id,
                    'db_id' => $c->id,
                    'source' => 'customer_record',
                    'telegram_id' => $chatId,
                    'name' => $c->full_name ?: 'ደንበኛ #' . $c->id,
                    'username' => '',
                    'phone' => $c->phone ?: 'ያልተመዘገበ',
                    'last_active' => $c->updated_at ? $c->updated_at->diffForHumans() : 'በቅርቡ',
                    'is_active' => true,
                ];
                $existingTgIds[] = $chatId;
            }
        }

        return response()->json([
            'total_count' => count($mergedList),
            'users' => $mergedList,
            'bot_username' => config('services.telegram.bot_username') ?: env('TELEGRAM_BOT_USERNAME', 'meash_cleaning_solution_bot'),
        ]);
    }

    /**
     * Add a single Telegram recipient manually
     * Name is mandatory, Father name is optional.
     * At least one of Username OR Phone number is mandatory.
     */
    public function addSingleRecipient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'username' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:32',
            'telegram_id' => 'nullable|numeric',
        ]);

        $cleanUsername = !empty($validated['username']) ? ltrim(trim($validated['username']), '@') : null;
        $cleanPhone = !empty($validated['phone_number']) ? preg_replace('/\D+/', '', trim($validated['phone_number'])) : null;

        // Either Username OR Phone Number must be present
        if (empty($cleanUsername) && empty($cleanPhone)) {
            return response()->json([
                'success' => false,
                'message' => 'እባክዎ የቴሌግራም Username ወይም ስልክ ቁጥር ቢያንስ አንዱን ያስገቡ!',
            ], 422);
        }

        // Determine Telegram ID
        $tgId = !empty($validated['telegram_id']) ? (int)$validated['telegram_id'] : null;
        if (empty($tgId)) {
            if (!empty($cleanPhone)) {
                $tgId = (int)substr($cleanPhone, -9);
            } else {
                $tgId = (int)mt_rand(100000000, 999999999);
            }
        }

        // Format clean phone for Ethiopian format if applicable
        $formattedPhone = $cleanPhone;
        if (!empty($cleanPhone)) {
            if (str_starts_with($cleanPhone, '00251')) {
                $formattedPhone = '0' . substr($cleanPhone, 5);
            } elseif (str_starts_with($cleanPhone, '251')) {
                $formattedPhone = '0' . substr($cleanPhone, 3);
            }
        }

        $user = TelegramUser::updateOrCreate(
            ['telegram_id' => $tgId],
            [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'] ?? null,
                'username' => $cleanUsername,
                'phone_number' => $formattedPhone,
                'marketing_consent' => true,
                'last_interaction_at' => now(),
            ]
        );

        // Also record in Customer directory if phone is present
        if (!empty($formattedPhone)) {
            $fullName = trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? ''));
            Customer::updateOrCreate(
                ['phone' => $formattedPhone],
                [
                    'full_name' => $fullName,
                    'telegram_user_id' => (string)$tgId,
                    'marketing_consent' => true,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => "የቴሌግራም ደንበኛ {$user->first_name} በተሳካ ሁኔታ ተመዝግቧል!",
            'user' => $user,
        ]);
    }

    /**
     * Bulk import recipients from text/JSON/CSV
     */
    public function bulkImportRecipients(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'raw_data' => 'required|string',
        ]);

        $raw = trim($validated['raw_data']);
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $importedCount = 0;
        $skippedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Handle formats:
            // 1. "12345678, Abebe, Kebede, 0911223344, @username" (Comma or tab separated)
            // 2. "12345678 Abebe Kebede" (Space separated)
            // 3. Or JSON object
            $parts = preg_split('/[,\t]+/', $line);
            if (count($parts) >= 2) {
                $tgId = preg_replace('/\D+/', '', trim($parts[0]));
                $name = trim($parts[1] ?? 'ደንበኛ');
                $phone = isset($parts[2]) ? preg_replace('/\D+/', '', trim($parts[2])) : null;
                $username = isset($parts[3]) ? ltrim(trim($parts[3]), '@') : null;
            } else {
                // Try space separated
                $spaceParts = preg_split('/\s+/', $line, 3);
                $tgId = preg_replace('/\D+/', '', trim($spaceParts[0]));
                $name = trim(($spaceParts[1] ?? 'ደንበኛ') . ' ' . ($spaceParts[2] ?? ''));
                $phone = null;
                $username = null;
            }

            if (!empty($tgId) && is_numeric($tgId) && strlen($tgId) >= 5) {
                TelegramUser::updateOrCreate(
                    ['telegram_id' => (int)$tgId],
                    [
                        'first_name' => $name,
                        'phone_number' => $phone,
                        'username' => $username,
                        'marketing_consent' => true,
                        'last_interaction_at' => now(),
                    ]
                );
                $importedCount++;
            } else {
                $skippedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "በአጠቃላይ {$importedCount} የቴሌግራም ደንበኞች በተሳካ ሁኔታ ተመዝግበዋል! (ያልተመዘገቡ፡ {$skippedCount})",
            'imported_count' => $importedCount,
            'skipped_count' => $skippedCount,
        ]);
    }

    /**
     * Dispatch smart safe telegram broadcast
     * Handles batching, rate-limiting, and excludes unselected users (family/friends)
     */
    public function dispatchBroadcast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:4000',
            'excluded_telegram_ids' => 'nullable|array',
            'excluded_telegram_ids.*' => 'nullable',
        ]);

        $botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');
        if (empty($botToken)) {
            return response()->json([
                'success' => false,
                'message' => 'የቴሌግራም ቦት ቶከን (TELEGRAM_BOT_TOKEN) በሲስተሙ ውስጥ አልተዋቀረም!',
            ], 422);
        }

        // Pre-flight check: Verify bot token validity with Telegram
        try {
            $botCheck = Http::withoutVerifying()->timeout(6)->get("https://api.telegram.org/bot{$botToken}/getMe");
            if (!$botCheck->successful()) {
                $errDetail = $botCheck->json('description') ?? 'Unauthorized or invalid token';
                return response()->json([
                    'success' => false,
                    'message' => "የቴሌግራም ቦት ቶከን ውድቅ ተደርጓል (Telegram Error: {$errDetail})። እባክዎ ከ @BotFather ትክክለኛውን ቶከን ያረጋግጡ!",
                ], 422);
            }
        } catch (\Throwable $e) {
            Log::warning("Telegram getMe pre-check failed: " . $e->getMessage());
        }

        $excludedIds = array_map('strval', $validated['excluded_telegram_ids'] ?? []);

        // Gather all eligible recipients
        $tgUsers = TelegramUser::all();
        $targetChatIds = [];

        foreach ($tgUsers as $u) {
            $chatId = (string)$u->telegram_id;
            if (!empty($chatId) && !in_array($chatId, $excludedIds)) {
                $targetChatIds[$chatId] = [
                    'name' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '')),
                    'phone' => $u->phone_number,
                ];
            }
        }

        // Also merge customer records with telegram_user_id
        $custRecords = Customer::whereNotNull('telegram_user_id')
            ->where('telegram_user_id', '!=', '')
            ->get();

        foreach ($custRecords as $c) {
            $chatId = (string)$c->telegram_user_id;
            if (!empty($chatId) && !in_array($chatId, $excludedIds) && !isset($targetChatIds[$chatId])) {
                $targetChatIds[$chatId] = [
                    'name' => $c->full_name,
                    'phone' => $c->phone,
                ];
            }
        }

        $totalTargets = count($targetChatIds);
        if ($totalTargets === 0) {
            return response()->json([
                'success' => false,
                'message' => 'ምንም የሚላክለት የቴሌግራም ደንበኛ አልተመረጠም (ሁሉም ተገልለዋል)!',
            ], 422);
        }

        // Log campaign in DB
        $campaign = Campaign::create([
            'title' => $validated['title'],
            'channel' => 'telegram',
            'audience_filter' => 'custom_broadcast_selection',
            'message_text' => $validated['message'],
            'status' => 'sending',
            'total_targets' => $totalTargets,
            'sent_count' => 0,
            'failed_count' => 0,
            'created_by_user_id' => $request->user()?->id,
            'sent_at' => now(),
        ]);

        $title = $validated['title'];
        $body = $validated['message'];
        $formattedMessage = "<b>" . htmlspecialchars($title) . "</b>\n\n" . htmlspecialchars($body) . "\n\n—\n<b>ሜሽ የፅዳት አገልግሎት (Meash Cleaning)</b>\n 0970075509 / 0998142190";
        $userId = $request->user()?->id;

        // Dispatch background chunked broadcast with afterResponse to prevent HTTP 504 gateway timeouts
        dispatch(function () use ($targetChatIds, $botToken, $formattedMessage, $campaign, $title, $totalTargets, $excludedIds, $userId) {
            $sentSuccess = 0;
            $failedCount = 0;
            $lastErrorMessage = null;

            // Process recipients in chunks of 20
            $chunks = array_chunk($targetChatIds, 20, true);

            foreach ($chunks as $chunk) {
                foreach ($chunk as $chatId => $info) {
                    try {
                        $response = Http::withoutVerifying()->timeout(6)->connectTimeout(2)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                            'chat_id' => $chatId,
                            'text' => $formattedMessage,
                            'parse_mode' => 'HTML',
                            'disable_web_page_preview' => false,
                        ]);

                        if ($response->successful()) {
                            $sentSuccess++;
                        } else {
                            $failedCount++;
                            $resJson = $response->json();
                            $lastErrorMessage = $resJson['description'] ?? 'የማይታወቅ ስህተት';
                            // If flood control occurs, pause for safe retry
                            if (isset($resJson['parameters']['retry_after'])) {
                                $waitSec = (int)$resJson['parameters']['retry_after'];
                                sleep(min($waitSec, 5));
                            }
                        }
                    } catch (\Throwable $e) {
                        $failedCount++;
                        $lastErrorMessage = $e->getMessage();
                        Log::warning("Telegram Broadcast error for chat {$chatId}: " . $e->getMessage());
                    }

                    // Rate-limiting: 50ms pause = max 20 messages/sec per Telegram API limits
                    usleep(50000);
                }
            }

            $campaign->update([
                'status' => 'completed',
                'sent_count' => $sentSuccess,
                'failed_count' => $failedCount,
            ]);

            if ($userId) {
                AuditLog::logAction(
                    $userId,
                    'telegram_broadcast_completed',
                    Campaign::class,
                    $campaign->id,
                    null,
                    [
                        'title' => $title,
                        'total_targets' => $totalTargets,
                        'sent_success' => $sentSuccess,
                        'failed_count' => $failedCount,
                        'excluded_count' => count($excludedIds),
                        'last_error' => $lastErrorMessage,
                    ]
                );
            }
        })->afterResponse();

        return response()->json([
            'success' => true,
            'message' => "የቴሌግራም መልእክት ስርጭት ለ {$totalTargets} ደንበኞች በጀርባ (background) በመላክ ላይ ነው!",
            'campaign_id' => $campaign->id,
            'total_targets' => $totalTargets,
            'sent_success' => $totalTargets,
            'failed_count' => 0,
            'excluded_count' => count($excludedIds),
            'status' => 'processing',
        ]);
    }
}
