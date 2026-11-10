<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Customer;
use App\Models\TelegramUser;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramBroadcastController extends Controller
{
    /**
     * Get all broadcast recipients with robust filtering, friend exclusions, and stats
     */
    public function getRecipients(Request $request): JsonResponse
    {
        $search = trim($request->query('search', ''));
        $sourceFilter = $request->query('source', '');
        $statusFilter = $request->query('status', ''); // 'all', 'excluded', 'non_excluded'
        $tagFilter = trim($request->query('tag') ?: $request->query('tags') ?: '');

        // Auto-sync all Customers and System Users into TelegramUser table so contacts are 100% unified
        try {
            $unsyncedCustomers = Customer::whereNotNull('phone')
                ->where('phone', '!=', '')
                ->limit(5000)
                ->get();
            foreach ($unsyncedCustomers as $c) {
                Customer::syncToBroadcastList($c);
            }

            // Also auto-sync internal system users / staff if they have phone
            $systemUsers = \App\Models\User::whereNotNull('phone')
                ->where('phone', '!=', '')
                ->get();
            foreach ($systemUsers as $u) {
                $cleanPhone = Customer::normalizePhone($u->phone);
                if ($cleanPhone) {
                    TelegramUser::firstOrCreate(
                        ['phone_number' => $cleanPhone],
                        [
                            'first_name' => $u->name,
                            'is_active' => true,
                            'is_excluded' => false,
                            'source' => 'system_user',
                            'tags' => 'Imported Contacts',
                            'marketing_consent' => true,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Recipient auto-sync non-critical error: ' . $e->getMessage());
        }

        $query = TelegramUser::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('telegram_id', 'like', "%{$search}%")
                  ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        if (!empty($sourceFilter)) {
            $query->where('source', $sourceFilter);
        }

        if (!empty($tagFilter)) {
            $query->where(function ($q) use ($tagFilter) {
                $q->where('tags', 'like', "%{$tagFilter}%");
                if ($tagFilter === 'Normal Active Customers') {
                    $q->orWhere('lifecycle_stage', 'normal_active');
                } elseif ($tagFilter === 'Comment Waiting') {
                    $q->orWhere('lifecycle_stage', 'comment_waiting');
                } elseif ($tagFilter === 'Imported Contacts') {
                    $q->orWhere('source', 'imported')
                      ->orWhere('source', 'bulk_import')
                      ->orWhere('source', 'system_user');
                } elseif ($tagFilter === 'Outdoor Sales Leads') {
                    $q->orWhere('source', 'outdoor_sales');
                }
            });
        }

        if ($statusFilter === 'excluded') {
            $query->where('is_excluded', true);
        } elseif ($statusFilter === 'non_excluded') {
            $query->where('is_excluded', false);
        }

        $allUsers = $query->orderBy('is_excluded', 'asc')
            ->orderBy('id', 'desc')
            ->limit(5000)
            ->get();

        $formatted = $allUsers->map(function ($u) {
            $fullName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''));
            if (empty($fullName)) {
                $fullName = $u->username ? '@' . ltrim($u->username, '@') : ($u->phone_number ? 'ደንበኛ (' . $u->phone_number . ')' : 'ተጠቃሚ #' . $u->id);
            }

            $tags = $u->tags;
            if (empty($tags)) {
                $tags = match ($u->lifecycle_stage) {
                    'normal_active' => 'Normal Active Customers',
                    'comment_waiting' => 'Comment Waiting',
                    default => ($u->source === 'outdoor_sales' ? 'Outdoor Sales Leads' : ($u->source === 'imported' || $u->source === 'bulk_import' ? 'Imported Contacts' : 'Normal Active Customers')),
                };
            }

            return [
                'id' => $u->id,
                'db_id' => $u->id,
                'source' => $u->source ?: 'imported',
                'tags' => $tags,
                'lifecycle_stage' => $u->lifecycle_stage ?: 'normal_active',
                'telegram_id' => $u->telegram_id ? (string)$u->telegram_id : null,
                'name' => $fullName,
                'username' => $u->username ? '@' . ltrim($u->username, '@') : '',
                'phone' => $u->phone_number ?: '',
                'is_excluded' => (bool)$u->is_excluded,
                'is_active' => (bool)$u->is_active,
                'last_active' => $u->last_interaction_at ? $u->last_interaction_at->diffForHumans() : ($u->updated_at ? $u->updated_at->diffForHumans() : 'በቅርቡ'),
            ];
        });

        $totalCount = TelegramUser::count();
        $excludedCount = TelegramUser::where('is_excluded', true)->count();
        $activeNonExcluded = TelegramUser::where('is_excluded', false)->where('is_active', true)->count();
        $withPhoneCount = TelegramUser::whereNotNull('phone_number')->where('phone_number', '!=', '')->count();
        $withTelegramCount = TelegramUser::whereNotNull('telegram_id')->where('telegram_id', '!=', '')->count();

        $normalActiveCount = TelegramUser::where(function($q) {
            $q->where('tags', 'like', '%Normal Active Customers%')
              ->orWhere('lifecycle_stage', 'normal_active');
        })->count();

        $commentWaitingCount = TelegramUser::where(function($q) {
            $q->where('tags', 'like', '%Comment Waiting%')
              ->orWhere('lifecycle_stage', 'comment_waiting');
        })->count();

        $importedCount = TelegramUser::where(function($q) {
            $q->where('tags', 'like', '%Imported Contacts%')
              ->orWhereIn('source', ['imported', 'bulk_import', 'system_user']);
        })->count();

        $outdoorCount = TelegramUser::where(function($q) {
            $q->where('tags', 'like', '%Outdoor Sales Leads%')
              ->orWhere('source', 'outdoor_sales');
        })->count();

        return response()->json([
            'total_count' => $totalCount,
            'active_count' => $activeNonExcluded,
            'excluded_count' => $excludedCount,
            'with_phone_count' => $withPhoneCount,
            'with_telegram_count' => $withTelegramCount,
            'tag_counts' => [
                'normal_active' => $normalActiveCount,
                'comment_waiting' => $commentWaitingCount,
                'imported_contacts' => $importedCount,
                'outdoor_sales' => $outdoorCount,
            ],
            'users' => $formatted,
            'bot_username' => config('services.telegram.bot_username') ?: env('TELEGRAM_BOT_USERNAME', 'meash_cleaning_solution_bot'),
        ]);
    }

    /**
     * Add a single recipient manually without faking telegram_id
     */
    public function addSingleRecipient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'username' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:32',
            'telegram_id' => 'nullable|numeric',
            'is_excluded' => 'nullable|boolean',
            'source' => 'nullable|string|max:64',
        ]);

        $cleanUsername = !empty($validated['username']) ? ltrim(trim($validated['username']), '@') : null;
        $cleanPhone = !empty($validated['phone_number']) ? Customer::normalizePhone($validated['phone_number']) : null;
        $realTgId = (!empty($validated['telegram_id']) && is_numeric($validated['telegram_id']) && strlen((string)$validated['telegram_id']) >= 5)
            ? (int)$validated['telegram_id']
            : null;

        if (empty($cleanUsername) && empty($cleanPhone) && empty($realTgId)) {
            return response()->json([
                'success' => false,
                'message' => 'እባክዎ ስልክ ቁጥር ወይም የቴሌግራም መለያ ቢያንስ አንዱን ያስገቡ!',
            ], 422);
        }

        // Search existing by phone or real telegram_id
        $user = null;
        if ($realTgId) {
            $user = TelegramUser::where('telegram_id', $realTgId)->first();
        }
        if (!$user && $cleanPhone) {
            $suffix = substr($cleanPhone, -9);
            $user = TelegramUser::where('phone_number', $cleanPhone)
                ->orWhere('phone_number', 'like', "%{$suffix}")
                ->first();
        }

        if ($user) {
            $user->first_name = $validated['first_name'];
            $user->last_name = $validated['last_name'] ?? $user->last_name;
            $user->username = $cleanUsername ?: $user->username;
            $user->phone_number = $cleanPhone ?: $user->phone_number;
            if ($realTgId) $user->telegram_id = $realTgId;
            if (isset($validated['is_excluded'])) $user->is_excluded = (bool)$validated['is_excluded'];
            $user->is_active = true;
            $user->last_interaction_at = now();
            $user->save();
        } else {
            $user = TelegramUser::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'] ?? null,
                'username' => $cleanUsername,
                'phone_number' => $cleanPhone,
                'telegram_id' => $realTgId, // Real numeric ID or NULL
                'is_excluded' => (bool)($validated['is_excluded'] ?? false),
                'is_active' => true,
                'source' => $validated['source'] ?? 'phone_customer',
                'marketing_consent' => true,
                'last_interaction_at' => now(),
            ]);
        }

        // Also record/sync in Customer directory if phone is present
        if (!empty($cleanPhone)) {
            $fullName = trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? ''));
            Customer::updateOrCreate(
                ['phone' => $cleanPhone],
                [
                    'full_name' => $fullName,
                    'telegram_user_id' => $realTgId ? (string)$realTgId : null,
                    'marketing_consent' => true,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => "እውቂያው {$user->first_name} በተሳካ ሁኔታ ተመዝግቧል!",
            'user' => $user,
        ]);
    }

    /**
     * Update recipient details & friend exclusion status
     */
    public function updateRecipient(Request $request, $id): JsonResponse
    {
        $user = TelegramUser::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:150',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:32',
            'phone_number' => 'nullable|string|max:32',
            'telegram_id' => 'nullable|numeric',
            'username' => 'nullable|string|max:100',
            'is_excluded' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'source' => 'nullable|string|max:64',
        ]);

        if (isset($validated['name'])) {
            $parts = explode(' ', trim($validated['name']), 2);
            $user->first_name = $parts[0] ?: $user->first_name;
            $user->last_name = $parts[1] ?? null;
        }
        if (isset($validated['first_name'])) $user->first_name = $validated['first_name'];
        if (isset($validated['last_name'])) $user->last_name = $validated['last_name'];

        $phoneInput = $validated['phone'] ?? ($validated['phone_number'] ?? null);
        if ($phoneInput !== null) {
            $user->phone_number = !empty($phoneInput) ? Customer::normalizePhone($phoneInput) : null;
        }

        if (array_key_exists('telegram_id', $validated)) {
            $rawTg = $validated['telegram_id'];
            $user->telegram_id = (!empty($rawTg) && is_numeric($rawTg) && strlen((string)$rawTg) >= 5) ? (int)$rawTg : null;
        }

        if (isset($validated['username'])) {
            $user->username = ltrim(trim($validated['username']), '@');
        }

        if (isset($validated['is_excluded'])) {
            $user->is_excluded = (bool)$validated['is_excluded'];
        }

        if (isset($validated['is_active'])) {
            $user->is_active = (bool)$validated['is_active'];
        }

        if (isset($validated['source'])) {
            $user->source = $validated['source'];
        }

        $user->save();

        // Also sync to Customer record if phone exists
        if ($user->phone_number) {
            Customer::where('phone', $user->phone_number)->update([
                'full_name' => $user->name,
                'telegram_user_id' => $user->telegram_id ? (string)$user->telegram_id : null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'የእውቂያው መረጃ በተሳካ ሁኔታ ተሻሽሏል!',
            'user' => $user,
        ]);
    }

    /**
     * Delete a single recipient permanently
     */
    public function deleteRecipient($id): JsonResponse
    {
        $user = TelegramUser::findOrFail($id);
        $name = $user->name;
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => "እውቂያ \"{$name}\" በቋሚነት ተሰርዟል!",
        ]);
    }

    /**
     * Bulk Action on Contacts: Delete, Exclude (Friends), or Include
     * Handles 50, 500, or 1000 contacts in 1 click!
     */
    public function bulkActionRecipients(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|in:delete,exclude,include',
            'ids' => 'nullable|array',
            'ids.*' => 'integer',
            'select_all' => 'nullable|boolean',
            'filter_status' => 'nullable|string',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'] ?? [];
        $selectAll = !empty($validated['select_all']);

        $query = TelegramUser::query();
        if (!$selectAll) {
            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ምንም የተመረጠ እውቂያ የለም!',
                ], 422);
            }
            $query->whereIn('id', $ids);
        } else {
            // Apply filter status if select all
            if (($validated['filter_status'] ?? '') === 'excluded') {
                $query->where('is_excluded', true);
            } elseif (($validated['filter_status'] ?? '') === 'non_excluded') {
                $query->where('is_excluded', false);
            }
        }

        $count = 0;
        if ($action === 'delete') {
            $count = $query->delete();
            $msg = "{$count} እውቂያዎች በቋሚነት ተሰርዘዋል!";
        } elseif ($action === 'exclude') {
            $count = $query->update(['is_excluded' => true]);
            $msg = "{$count} እውቂያዎች ከስርጭት ዝርዝር ተገልለዋል (Friends Excluded)!";
        } elseif ($action === 'include') {
            $count = $query->update(['is_excluded' => false]);
            $msg = "{$count} እውቂያዎች ወደ ስርጭት ዝርዝር ተመልሰዋል!";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'affected_count' => $count,
        ]);
    }

    /**
     * Export all active contacts as CSV or JSON
     */
    public function exportRecipients(Request $request)
    {
        $format = $request->query('format', 'csv');
        $onlyActive = $request->query('active_only', '1') === '1';

        $query = TelegramUser::query();
        if ($onlyActive) {
            $query->where('is_excluded', false)->where('is_active', true);
        }
        $users = $query->orderBy('id', 'asc')->get();

        if ($format === 'json') {
            $exportData = $users->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'phone' => $u->phone_number,
                    'telegram_id' => $u->telegram_id,
                    'username' => $u->username,
                    'source' => $u->source,
                    'is_excluded' => $u->is_excluded,
                ];
            });

            return response()->json($exportData)
                ->header('Content-Disposition', 'attachment; filename="meash_contacts_' . date('Y_m_d') . '.json"');
        }

        // CSV Export
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="meash_contacts_' . date('Y_m_d') . '.csv"',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility with Amharic
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['ID', 'Full Name', 'Phone Number', 'Telegram ID', 'Username', 'Source', 'Excluded', 'Created At']);

            foreach ($users as $u) {
                fputcsv($file, [
                    $u->id,
                    $u->name,
                    $u->phone_number ?: '',
                    $u->telegram_id ?: '',
                    $u->username ? '@' . $u->username : '',
                    $u->source ?: 'imported',
                    $u->is_excluded ? 'YES' : 'NO',
                    $u->created_at ? $u->created_at->format('Y-m-d H:i') : '',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * High-speed bulk import from JSON or CSV
     * Cleanly extracts Telegram User IDs without fake phone conversions
     */
    public function bulkImportRecipients(Request $request): JsonResponse
    {
        $raw = null;
        if ($request->hasFile('file')) {
            $raw = file_get_contents($request->file('file')->getRealPath());
        } elseif ($request->filled('raw_data')) {
            $raw = trim($request->input('raw_data'));
        }

        if (empty($raw)) {
            return response()->json([
                'success' => false,
                'message' => 'እባክዎ የሚጫን የቴሌግራም JSON ወይም CSV ፋይል/ጽሑፍ ያስገቡ!',
            ], 422);
        }

        // Try JSON parsing first (Telegram Desktop result.json or any machine-readable JSON array/object)
        $jsonData = json_decode($raw, true);
        if ($jsonData) {
            if (isset($jsonData['contacts']) || isset($jsonData['frequent_contacts']) || isset($jsonData['chats'])) {
                return $this->processTelegramDesktopJson($jsonData);
            }
            // Check if it's an array of objects or an object containing a list
            $list = null;
            if (array_is_list($jsonData)) {
                $list = $jsonData;
            } elseif (isset($jsonData['users']) && is_array($jsonData['users'])) {
                $list = $jsonData['users'];
            } elseif (isset($jsonData['members']) && is_array($jsonData['members'])) {
                $list = $jsonData['members'];
            } elseif (isset($jsonData['contacts']) && is_array($jsonData['contacts'])) {
                $list = $jsonData['contacts'];
            } elseif (isset($jsonData['data']) && is_array($jsonData['data'])) {
                $list = $jsonData['data'];
            }

            if ($list !== null) {
                return $this->processGenericJsonArray($list);
            }
        }

        // Otherwise process as plain text / CSV lines
        return $this->processCsvTextLines($raw);
    }

    /**
     * Parse Telegram Desktop result.json cleanly
     */
    protected function processTelegramDesktopJson(array $data): JsonResponse
    {
        $realTgMap = [];
        $frequentList = $data['frequent_contacts']['list'] ?? [];
        foreach ($frequentList as $fc) {
            $type = $fc['type'] ?? '';
            $rawId = $fc['id'] ?? null;
            $name = trim($fc['name'] ?? '');
            if ($type === 'user' && !empty($rawId) && is_numeric($rawId)) {
                if (!empty($name)) $realTgMap[mb_strtolower($name)] = (int)$rawId;
            }
        }

        $chatsList = $data['chats']['list'] ?? [];
        foreach ($chatsList as $chat) {
            $chatType = $chat['type'] ?? '';
            $rawId = $chat['id'] ?? null;
            $name = trim($chat['name'] ?? '');
            if ($chatType === 'personal_chat' && !empty($rawId)) {
                $numericId = preg_replace('/\D+/', '', (string)$rawId);
                if (!empty($numericId) && is_numeric($numericId) && strlen($numericId) >= 5) {
                    if (!empty($name)) $realTgMap[mb_strtolower($name)] = (int)$numericId;
                }
            }
        }

        $contactsList = $data['contacts']['list'] ?? [];
        $importedCust = 0;
        $importedTg = 0;

        $chunks = array_chunk($contactsList, 500);
        foreach ($chunks as $chunk) {
            DB::beginTransaction();
            try {
                foreach ($chunk as $contact) {
                    $rawPhone = $contact['phone_number'] ?? '';
                    $first = trim($contact['first_name'] ?? '');
                    $last = trim($contact['last_name'] ?? '');
                    $fullName = trim("{$first} {$last}");
                    $cleanPhone = !empty($rawPhone) ? Customer::normalizePhone($rawPhone) : null;

                    if (empty($fullName)) {
                        $fullName = $cleanPhone ? "ደንበኛ ({$cleanPhone})" : "ቴሌግራም ደንበኛ";
                    }

                    $matchedTgId = $realTgMap[mb_strtolower($fullName)] ?? null;

                    if (!empty($cleanPhone)) {
                        Customer::updateOrCreate(
                            ['phone' => $cleanPhone],
                            [
                                'full_name' => $fullName,
                                'customer_type' => 'individual',
                                'telegram_user_id' => $matchedTgId ? (string)$matchedTgId : null,
                                'marketing_consent' => true,
                            ]
                        );
                        $importedCust++;
                    }

                    $tgUser = null;
                    if ($matchedTgId) {
                        $tgUser = TelegramUser::where('telegram_id', $matchedTgId)->first();
                    }
                    if (!$tgUser && !empty($cleanPhone)) {
                        $suffix = substr($cleanPhone, -9);
                        $tgUser = TelegramUser::where('phone_number', $cleanPhone)
                            ->orWhere('phone_number', 'like', "%{$suffix}")
                            ->first();
                    }

                    if ($tgUser) {
                        $tgUser->first_name = $fullName ?: $tgUser->first_name;
                        if (!empty($cleanPhone)) $tgUser->phone_number = $cleanPhone;
                        if ($matchedTgId && empty($tgUser->telegram_id)) $tgUser->telegram_id = $matchedTgId;
                        $tgUser->is_active = true;
                        $tgUser->source = 'imported';
                        $tgUser->save();
                    } else {
                        // Create cleanly with REAL matched ID or NULL (never fake numeric ID!)
                        TelegramUser::create([
                            'telegram_id' => $matchedTgId ?: null,
                            'first_name' => $fullName,
                            'phone_number' => $cleanPhone,
                            'is_active' => true,
                            'is_excluded' => false,
                            'source' => 'imported',
                            'marketing_consent' => true,
                            'last_interaction_at' => now(),
                        ]);
                    }
                    $importedTg++;
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::warning('JSON chunk import error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "በአጠቃላይ {$importedTg} የቴሌግራም እውቂያዎች እና {$importedCust} ደንበኞች ከ Telegram Desktop ፋይል በተሳካ ሁኔታ ተመዝግበዋል!",
            'imported_count' => $importedTg,
        ]);
    }

    /**
     * Process generic machine-readable JSON array of contacts / exported users
     */
    protected function processGenericJsonArray(array $items): JsonResponse
    {
        $importedTg = 0;
        $importedCust = 0;

        $chunks = array_chunk($items, 500);
        foreach ($chunks as $chunk) {
            DB::beginTransaction();
            try {
                foreach ($chunk as $item) {
                    if (!is_array($item)) continue;

                    $rawPhone = $item['phone'] ?? $item['phone_number'] ?? $item['mobile'] ?? $item['tel'] ?? null;
                    $cleanPhone = !empty($rawPhone) ? Customer::normalizePhone((string)$rawPhone) : null;

                    $first = trim($item['first_name'] ?? '');
                    $last = trim($item['last_name'] ?? '');
                    $fullName = trim($item['name'] ?? $item['full_name'] ?? "{$first} {$last}");

                    $rawTgId = $item['telegram_id'] ?? $item['tg_id'] ?? $item['chat_id'] ?? ($item['id'] ?? null);
                    $tgId = (is_numeric($rawTgId) && strlen((string)$rawTgId) >= 5) ? (int)$rawTgId : null;

                    $rawUsername = $item['username'] ?? null;
                    $cleanUsername = !empty($rawUsername) ? ltrim(trim($rawUsername), '@') : null;

                    if (empty($fullName)) {
                        $fullName = $cleanPhone ? "ደንበኛ ({$cleanPhone})" : ($cleanUsername ? "@{$cleanUsername}" : "እውቂያ");
                    }

                    if (!empty($cleanPhone)) {
                        Customer::updateOrCreate(
                            ['phone' => $cleanPhone],
                            [
                                'full_name' => $fullName,
                                'customer_type' => 'individual',
                                'telegram_user_id' => $tgId ? (string)$tgId : null,
                                'marketing_consent' => true,
                            ]
                        );
                        $importedCust++;
                    }

                    $tgUser = null;
                    if ($tgId) {
                        $tgUser = TelegramUser::where('telegram_id', $tgId)->first();
                    }
                    if (!$tgUser && !empty($cleanPhone)) {
                        $suffix = substr($cleanPhone, -9);
                        $tgUser = TelegramUser::where(function ($q) use ($cleanPhone, $suffix) {
                            $q->where('phone_number', $cleanPhone)
                              ->orWhere('phone_number', 'like', "%{$suffix}");
                        })->first();
                    }

                    if ($tgUser) {
                        $tgUser->first_name = $fullName ?: $tgUser->first_name;
                        if (!empty($cleanPhone)) $tgUser->phone_number = $cleanPhone;
                        if ($tgId && empty($tgUser->telegram_id)) $tgUser->telegram_id = $tgId;
                        if ($cleanUsername && empty($tgUser->username)) $tgUser->username = $cleanUsername;
                        $tgUser->is_active = true;
                        $tgUser->is_excluded = false;
                        $tgUser->save();
                    } else {
                        TelegramUser::create([
                            'telegram_id' => $tgId,
                            'first_name' => $fullName,
                            'username' => $cleanUsername,
                            'phone_number' => $cleanPhone,
                            'is_active' => true,
                            'is_excluded' => false,
                            'source' => 'json_import',
                            'marketing_consent' => true,
                            'last_interaction_at' => now(),
                        ]);
                    }
                    $importedTg++;
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::warning('Generic JSON chunk import error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "በአጠቃላይ {$importedTg} እውቂያዎች (እና {$importedCust} ደንበኞች) ከ JSON ፋይል በተሳካ ሁኔታ ወደ ሲስተሙ ገብተዋል!",
            'imported_count' => $importedTg,
        ]);
    }

    /**
     * Process CSV / plain text lines
     */
    protected function processCsvTextLines(string $raw): JsonResponse
    {
        $lines = preg_split('/\r\n|\r|\n/', $raw);
        $importedCount = 0;
        $batch = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = preg_split('/[,\t]+/', $line);
            $name = null;
            $phone = null;
            $tgId = null;
            $username = null;

            if (count($parts) >= 2) {
                // Determine which column is phone/tgId/name
                foreach ($parts as $p) {
                    $p = trim($p);
                    if (str_starts_with($p, '@')) {
                        $username = ltrim($p, '@');
                    } elseif (preg_match('/^(09|07|\+2519|\+2517|2519|2517)\d+/', $p)) {
                        $phone = Customer::normalizePhone($p);
                    } elseif (is_numeric($p) && strlen($p) >= 5 && strlen($p) <= 12) {
                        $tgId = (int)$p;
                    } elseif (!empty($p) && empty($name)) {
                        $name = $p;
                    }
                }
            } else {
                $name = $line;
            }

            if (!empty($phone) || !empty($tgId) || !empty($username) || !empty($name)) {
                $batch[] = [
                    'name' => $name ?: 'ደንበኛ',
                    'phone' => $phone,
                    'tg_id' => $tgId,
                    'username' => $username,
                ];
            }
        }

        foreach (array_chunk($batch, 500) as $chunk) {
            DB::beginTransaction();
            try {
                foreach ($chunk as $item) {
                    $tgUser = null;
                    if ($item['tg_id']) {
                        $tgUser = TelegramUser::where('telegram_id', $item['tg_id'])->first();
                    }
                    if (!$tgUser && $item['phone']) {
                        $suffix = substr($item['phone'], -9);
                        $tgUser = TelegramUser::where('phone_number', $item['phone'])
                            ->orWhere('phone_number', 'like', "%{$suffix}")
                            ->first();
                    }

                    if ($tgUser) {
                        $tgUser->first_name = $item['name'] ?: $tgUser->first_name;
                        if ($item['phone']) $tgUser->phone_number = $item['phone'];
                        if ($item['tg_id'] && empty($tgUser->telegram_id)) $tgUser->telegram_id = $item['tg_id'];
                        if ($item['username']) $tgUser->username = $item['username'];
                        $tgUser->is_active = true;
                        $tgUser->save();
                    } else {
                        TelegramUser::create([
                            'telegram_id' => $item['tg_id'] ?: null,
                            'first_name' => $item['name'],
                            'phone_number' => $item['phone'],
                            'username' => $item['username'],
                            'is_active' => true,
                            'is_excluded' => false,
                            'source' => 'imported',
                            'marketing_consent' => true,
                            'last_interaction_at' => now(),
                        ]);
                    }
                    $importedCount++;
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "በአጠቃላይ {$importedCount} እውቂያዎች በተሳካ ሁኔታ ተመዝግበዋል!",
            'imported_count' => $importedCount,
        ]);
    }

    /**
     * Dispatch / Send Broadcast with chat_id validation and phone-only SMS routing/skipping
     */
    public function sendBroadcast(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:4000',
            'excluded_telegram_ids' => 'nullable|array',
            'excluded_telegram_ids.*' => 'nullable',
            'recipient_ids' => 'nullable|array',
            'tag' => 'nullable|string',
            'route_phone_to_sms' => 'nullable|boolean',
        ]);

        $botToken = config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN');

        $explicitExcluded = array_map('strval', $validated['excluded_telegram_ids'] ?? []);
        $recipientIds = $validated['recipient_ids'] ?? [];
        $tag = $validated['tag'] ?? null;
        $routePhoneToSms = (bool)($request->input('route_phone_to_sms', true));

        // Query candidate recipients
        $query = TelegramUser::where('is_excluded', false);

        if (!empty($recipientIds)) {
            $query->whereIn('id', $recipientIds);
        }

        if (!empty($tag)) {
            $query->where(function ($q) use ($tag) {
                $q->where('tags', 'like', "%{$tag}%");
                if ($tag === 'Normal Active Customers') {
                    $q->orWhere('lifecycle_stage', 'normal_active');
                } elseif ($tag === 'Comment Waiting') {
                    $q->orWhere('lifecycle_stage', 'comment_waiting');
                } elseif ($tag === 'Imported Contacts') {
                    $q->orWhereIn('source', ['imported', 'bulk_import', 'system_user']);
                } elseif ($tag === 'Outdoor Sales Leads') {
                    $q->orWhere('source', 'outdoor_sales');
                }
            });
        }

        $allCandidates = $query->get();

        $telegramTargets = [];
        $phoneOnlyTargets = [];

        foreach ($allCandidates as $u) {
            $chatId = $u->telegram_id ? (string)$u->telegram_id : null;
            $phone = $u->phone_number;

            // Validate telegram_chat_id: must be numeric and valid Telegram ID (typically > 10000)
            $isValidTelegram = !empty($chatId) && is_numeric($chatId) && (int)$chatId > 10000 && !in_array($chatId, $explicitExcluded);

            if ($isValidTelegram) {
                $telegramTargets[$chatId] = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'phone' => $phone,
                ];
            } elseif (!empty($phone)) {
                // Phone-only contact without valid telegram_id
                $cleanPhone = Customer::normalizePhone($phone);
                if ($cleanPhone) {
                    $phoneOnlyTargets[] = [
                        'id' => $u->id,
                        'name' => $u->name,
                        'phone' => $cleanPhone,
                    ];
                }
            }
            // Contacts without telegram_id and without phone are skipped automatically without error
        }

        $totalTelegram = count($telegramTargets);
        $totalPhoneOnly = count($phoneOnlyTargets);
        $totalTargets = $totalTelegram + ($routePhoneToSms ? $totalPhoneOnly : 0);

        if ($totalTargets === 0) {
            return response()->json([
                'success' => false,
                'message' => 'ምንም የሚላክለት የቴሌግራም ወይም የስልክ ደንበኛ አልተገኘም (ወይም ሁሉም ተገልለዋል)!',
            ], 422);
        }

        $campaign = Campaign::create([
            'title' => $validated['title'],
            'channel' => ($totalTelegram > 0 && $totalPhoneOnly > 0) ? 'omni_broadcast' : ($totalTelegram > 0 ? 'telegram' : 'sms'),
            'audience_filter' => $tag ?: 'custom_broadcast_selection',
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

        dispatch(function () use ($telegramTargets, $phoneOnlyTargets, $routePhoneToSms, $botToken, $formattedMessage, $body, $campaign, $title, $totalTargets, $userId) {
            $sentSuccess = 0;
            $failedCount = 0;

            // 1. Process Telegram targets with validated chat IDs
            if (!empty($botToken) && !empty($telegramTargets)) {
                $chunks = array_chunk($telegramTargets, 20, true);
                foreach ($chunks as $chunk) {
                    foreach ($chunk as $chatId => $info) {
                        // Double check chat_id validation before calling Telegram API
                        if (empty($chatId) || !is_numeric($chatId) || (int)$chatId <= 10000) {
                            continue; // Skip invalid IDs without throwing errors
                        }

                        try {
                            $response = Http::timeout(6)->connectTimeout(2)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                                'chat_id' => (int)$chatId,
                                'text' => $formattedMessage,
                                'parse_mode' => 'HTML',
                                'disable_web_page_preview' => false,
                            ]);

                            if ($response->successful()) {
                                $sentSuccess++;
                            } else {
                                $failedCount++;
                                $resJson = $response->json();
                                if (isset($resJson['parameters']['retry_after'])) {
                                    sleep(min((int)$resJson['parameters']['retry_after'], 5));
                                }
                            }
                        } catch (\Throwable $e) {
                            $failedCount++;
                        }
                        usleep(50000);
                    }
                }
            }

            // 2. Process Phone-only targets (routed to SMS safely without throwing errors)
            if ($routePhoneToSms && !empty($phoneOnlyTargets)) {
                foreach ($phoneOnlyTargets as $pTarget) {
                    try {
                        $smsResult = SmsService::sendDirectSms($pTarget['phone'], $body);
                        if (!empty($smsResult['success'])) {
                            $sentSuccess++;
                        } else {
                            $failedCount++;
                        }
                    } catch (\Throwable $e) {
                        $failedCount++; // Continue without failing the entire queue
                    }
                    usleep(100000); // 100ms gap
                }
            }

            $campaign->update([
                'status' => 'completed',
                'sent_count' => $sentSuccess,
                'failed_count' => $failedCount,
            ]);

            if ($userId) {
                AuditLog::logAction($userId, 'broadcast_completed', Campaign::class, $campaign->id, null, [
                    'title' => $title,
                    'total_targets' => $totalTargets,
                    'telegram_targets' => count($telegramTargets),
                    'phone_sms_targets' => count($phoneOnlyTargets),
                    'sent_success' => $sentSuccess,
                    'failed_count' => $failedCount,
                ]);
            }
        })->afterResponse();

        $msgParts = [];
        if ($totalTelegram > 0) $msgParts[] = "{$totalTelegram} በቴሌግራም";
        if ($routePhoneToSms && $totalPhoneOnly > 0) $msgParts[] = "{$totalPhoneOnly} በ SMS";

        $destStr = implode(' እና ', $msgParts);

        return response()->json([
            'success' => true,
            'message' => "መልእክቱ ለ {$destStr} በጀርባ (background) በመላክ ላይ ነው!",
            'campaign_id' => $campaign->id,
            'telegram_targets' => $totalTelegram,
            'sms_targets' => $routePhoneToSms ? $totalPhoneOnly : 0,
            'total_targets' => $totalTargets,
            'status' => 'processing',
        ]);
    }

    /**
     * Alias for sendBroadcast to maintain full backwards compatibility
     */
    public function dispatchBroadcast(Request $request): JsonResponse
    {
        return $this->sendBroadcast($request);
    }

    // =========================================================================
    // IPHONE 1-SCAN AUTO-SMS BRIDGE & QUEUE ENGINE
    // =========================================================================

    /**
     * Step 1: Create an SMS Session and prepare pending recipients batch
     */
    public function createSmsSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'title' => 'nullable|string|max:255',
            'target' => 'nullable|string', // 'all', 'selected'
            'selected_ids' => 'nullable|array',
            'excluded_ids' => 'nullable|array',
        ]);

        $message = trim($validated['message']);
        $title = !empty($validated['title']) ? $validated['title'] : 'የጅምላ ኤስኤምኤስ ስርጭት (SMS Campaign)';
        $excludedIds = array_map('intval', $validated['excluded_ids'] ?? []);
        $selectedIds = array_map('intval', $validated['selected_ids'] ?? []);

        $query = TelegramUser::where('is_excluded', false)
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '');

        if (!empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        }
        if (!empty($excludedIds)) {
            $query->whereNotIn('id', $excludedIds);
        }

        $recipients = $query->get();
        if ($recipients->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'ምንም ስልክ ቁጥር ያለው ንቁ ተቀባይ አልተገኘም (ወይም ሁሉም ተገልለዋል)!',
            ], 422);
        }

        $sessionToken = Str::random(40);
        $campaign = Campaign::create([
            'title' => $title,
            'channel' => 'sms',
            'audience_filter' => 'iphone_auto_sms_bridge',
            'message_text' => $message,
            'status' => 'sending',
            'session_token' => $sessionToken,
            'total_targets' => $recipients->count(),
            'sent_count' => 0,
            'failed_count' => 0,
            'created_by_user_id' => $request->user()?->id,
            'sent_at' => now(),
        ]);

        // Insert pending recipients into campaign_recipients in chunks of 500
        $rows = [];
        $now = now();
        foreach ($recipients as $r) {
            $custPhone = Customer::normalizePhone($r->phone_number);
            // Format for international dialing e.g. +2519...
            $intlPhone = $custPhone;
            if (str_starts_with($custPhone, '0')) {
                $intlPhone = '+251' . substr($custPhone, 1);
            } elseif (str_starts_with($custPhone, '251')) {
                $intlPhone = '+' . $custPhone;
            }

            // Personalize message with recipient name if placeholder exists or standard
            $personalized = str_replace(
                ['{name}', '{ስም}', '{first_name}'],
                $r->name,
                $message
            );

            $rows[] = [
                'campaign_id' => $campaign->id,
                'recipient_id' => (string)$r->id,
                'phone' => $intlPhone,
                'recipient_name' => $r->name,
                'message' => $personalized,
                'status' => 'pending',
                'error_message' => null,
                'sent_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, 500) as $batch) {
            CampaignRecipient::insert($batch);
        }

        // Cache session token for fast public verification
        Cache::put("sms_bridge_{$campaign->id}", $sessionToken, now()->addHours(24));

        $baseUrl = rtrim(config('app.url') ?: env('APP_URL', $request->getSchemeAndHttpHost()), '/');
        $launchUrl = "{$baseUrl}/api/sms-bridge/launch/{$campaign->id}?token={$sessionToken}";
        $pullUrl = "{$baseUrl}/api/sms-bridge/pull-queue?campaign_id={$campaign->id}&token={$sessionToken}";
        $shortcutDirectUrl = "shortcuts://run-shortcut?name=Meash%20Auto%20SMS&input=" . urlencode($pullUrl);

        return response()->json([
            'success' => true,
            'campaign_id' => $campaign->id,
            'session_token' => $sessionToken,
            'total_targets' => $recipients->count(),
            'launch_url' => $launchUrl,
            'pull_url' => $pullUrl,
            'shortcut_url' => $shortcutDirectUrl,
            'message' => "የኤስኤምኤስ ክፍለ-ጊዜ ተፈጥሯል! አሁን የአይፎን ካሜራዎን በመጠቀም QR ኮዱን ስካን ያድርጉ::",
        ]);
    }

    /**
     * Step 2: Public token-verified endpoint for iPhone Shortcuts to pull pending recipients
     */
    public function pullSmsQueue(Request $request): JsonResponse
    {
        $campaignId = $request->query('campaign_id');
        $token = $request->query('token');

        if (!$this->verifySmsBridgeToken($campaignId, $token, $campaign)) {
            return response()->json(['error' => 'Invalid or expired session token'], 403);
        }

        $pending = CampaignRecipient::where('campaign_id', $campaign->id)
            ->where('status', 'pending')
            ->orderBy('id', 'asc')
            ->limit(3500)
            ->get();

        $items = $pending->map(function ($p) {
            return [
                'recipient_id' => (int)$p->id,
                'phone' => $p->phone,
                'name' => $p->recipient_name,
                'message' => $p->message,
            ];
        });

        return response()->json($items);
    }

    /**
     * Step 3: Public token-verified endpoint for iPhone Shortcuts to report status in real-time
     */
    public function reportSmsStatus(Request $request): JsonResponse
    {
        $campaignId = $request->input('campaign_id');
        $token = $request->input('token');

        if (!$this->verifySmsBridgeToken($campaignId, $token, $campaign)) {
            return response()->json(['error' => 'Invalid or expired session token'], 403);
        }

        $recipientIds = $request->input('recipient_ids', []);
        if ($request->has('recipient_id')) {
            $recipientIds[] = $request->input('recipient_id');
        }
        $recipientIds = array_filter(array_map('intval', (array)$recipientIds));

        if (empty($recipientIds) && $request->filled('phone')) {
            $phone = trim($request->input('phone'));
            $matchedIds = CampaignRecipient::where('campaign_id', $campaign->id)
                ->where(function($q) use ($phone) {
                    $q->where('phone', $phone)
                      ->orWhere('phone', 'like', "%{$phone}%");
                })
                ->pluck('id')
                ->all();
            $recipientIds = array_merge($recipientIds, $matchedIds);
        }

        $status = $request->input('status', 'sent') === 'failed' ? 'failed' : 'sent';
        $errMsg = $request->input('error_message');

        if (!empty($recipientIds)) {
            CampaignRecipient::where('campaign_id', $campaign->id)
                ->whereIn('id', $recipientIds)
                ->update([
                    'status' => $status,
                    'error_message' => $errMsg,
                    'sent_at' => now(),
                ]);
        }

        // Recalculate campaign statistics
        $sentCount = CampaignRecipient::where('campaign_id', $campaign->id)->where('status', 'sent')->count();
        $failedCount = CampaignRecipient::where('campaign_id', $campaign->id)->where('status', 'failed')->count();
        $totalTargets = $campaign->total_targets;

        $newStatus = ($sentCount + $failedCount >= $totalTargets && $totalTargets > 0) ? 'completed' : 'sending';

        $campaign->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => $newStatus,
        ]);

        return response()->json([
            'success' => true,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'total_targets' => $totalTargets,
            'is_completed' => ($newStatus === 'completed'),
        ]);
    }

    /**
     * Step 4: Mobile Safari landing page opened when iPhone scans laptop QR Code
     */
    public function launchSmsBridge(Request $request, $campaign_id)
    {
        $token = $request->query('token');
        if (!$this->verifySmsBridgeToken($campaign_id, $token, $campaign)) {
            return response('<h3>የኤስኤምኤስ ክፍለ-ጊዜው አልቋል ወይም ልክ ያልሆነ ቶከን ነው (Session Expired)!</h3>', 403);
        }

        $baseUrl = rtrim(config('app.url') ?: env('APP_URL', $request->getSchemeAndHttpHost()), '/');
        $pullUrl = "{$baseUrl}/api/sms-bridge/pull-queue?campaign_id={$campaign->id}&token={$token}";
        $reportUrl = "{$baseUrl}/api/sms-bridge/report-status";
        $shortcutDirectUrl = "shortcuts://run-shortcut?name=Meash%20Auto%20SMS&input=" . urlencode($pullUrl);

        $total = $campaign->total_targets;
        $sent = $campaign->sent_count;

        return response()->view('sms_bridge_launch', [
            'campaign' => $campaign,
            'token' => $token,
            'total' => $total,
            'sent' => $sent,
            'pullUrl' => $pullUrl,
            'reportUrl' => $reportUrl,
            'shortcutDirectUrl' => $shortcutDirectUrl,
        ]);
    }

    /**
     * Real-time polling endpoint for laptop screen
     */
    public function getCampaignStatus(Request $request, $id): JsonResponse
    {
        $campaign = Campaign::findOrFail($id);
        $total = max(1, $campaign->total_targets);
        $sent = $campaign->sent_count;
        $failed = $campaign->failed_count;
        $percent = min(100, round(($sent / $total) * 100));

        return response()->json([
            'campaign_id' => $campaign->id,
            'status' => $campaign->status,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'total_targets' => $campaign->total_targets,
            'percentage' => $percent,
            'is_completed' => in_array($campaign->status, ['completed', 'cancelled']),
        ]);
    }

    /**
     * Verify SMS session token against Cache and DB
     */
    protected function verifySmsBridgeToken($campaignId, $token, &$campaign): bool
    {
        if (empty($campaignId) || empty($token)) return false;

        $campaign = Campaign::find($campaignId);
        if (!$campaign) return false;

        $cachedToken = Cache::get("sms_bridge_{$campaignId}");
        if ($cachedToken && hash_equals($cachedToken, $token)) {
            return true;
        }

        if (!empty($campaign->session_token) && hash_equals($campaign->session_token, $token)) {
            return true;
        }

        return false;
    }
}
