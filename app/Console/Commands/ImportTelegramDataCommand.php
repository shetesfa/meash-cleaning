<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\TelegramUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTelegramDataCommand extends Command
{
    protected $signature = 'telegram:import-export {path?}';
    protected $description = 'Imports Telegram Desktop exported data (result.json) into database customers and telegram_users cleanly';

    public function handle(): int
    {
        $defaultPath = 'e:\\users\\download\\Telegram Desktop\\DataExport_2026-11-03 (1)\\result.json';
        $path = $this->argument('path') ?: $defaultPath;

        if (!file_exists($path)) {
            $this->error("File not found at: {$path}");
            return 1;
        }

        $this->info("Loading Telegram export from: {$path}");
        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (!$data) {
            $this->error("Invalid JSON data.");
            return 1;
        }

        $realTgMap = []; // [normalized_name => numeric_tg_id]
        $realTgPhoneMap = []; // [clean_phone => numeric_tg_id]

        // 1. Process Frequent Contacts (Real numeric Telegram IDs)
        $frequentList = $data['frequent_contacts']['list'] ?? [];
        foreach ($frequentList as $fc) {
            $type = $fc['type'] ?? '';
            $rawId = $fc['id'] ?? null;
            $name = trim($fc['name'] ?? '');

            if ($type === 'user' && !empty($rawId) && is_numeric($rawId)) {
                $tgId = (int)$rawId;
                if (!empty($name)) {
                    $realTgMap[mb_strtolower($name)] = $tgId;
                }
            }
        }

        // 2. Process Personal Chats from chats.list (Real Telegram user IDs)
        $chatsList = $data['chats']['list'] ?? [];
        foreach ($chatsList as $chat) {
            $chatType = $chat['type'] ?? '';
            $rawId = $chat['id'] ?? null;
            $name = trim($chat['name'] ?? '');

            // In Telegram Desktop exports, personal chats have numeric IDs (e.g. 123456789 or user123456789)
            if ($chatType === 'personal_chat' && !empty($rawId)) {
                $numericId = preg_replace('/\D+/', '', (string)$rawId);
                if (!empty($numericId) && is_numeric($numericId) && strlen($numericId) >= 5) {
                    $tgId = (int)$numericId;
                    if (!empty($name)) {
                        $realTgMap[mb_strtolower($name)] = $tgId;
                    }
                }
            }
        }

        $this->info("Indexed " . count($realTgMap) . " verified Telegram User IDs from frequent contacts and personal chats.");

        // 3. Process Contacts List in 500-sized chunks without fake IDs
        $contactsList = $data['contacts']['list'] ?? [];
        $totalContacts = count($contactsList);
        $this->info("Processing {$totalContacts} contacts in 500-sized batch chunks...");

        $importedCust = 0;
        $importedTg = 0;

        $chunks = array_chunk($contactsList, 500);

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();
            try {
                foreach ($chunk as $contact) {
                    $rawPhone = $contact['phone_number'] ?? '';
                    $first = trim($contact['first_name'] ?? '');
                    $last = trim($contact['last_name'] ?? '');
                    $fullName = trim("{$first} {$last}");

                    $digits = preg_replace('/\D+/', '', $rawPhone);
                    $cleanPhone = $rawPhone;

                    if (str_starts_with($digits, '00251')) {
                        $cleanPhone = '0' . substr($digits, 5);
                    } elseif (str_starts_with($digits, '251')) {
                        $cleanPhone = '0' . substr($digits, 3);
                    } elseif (str_starts_with($digits, '09') || str_starts_with($digits, '07')) {
                        $cleanPhone = $digits;
                    } elseif (!empty($digits)) {
                        $cleanPhone = $digits;
                    }

                    if (empty($fullName)) {
                        $fullName = !empty($cleanPhone) ? "ደንበኛ ({$cleanPhone})" : "ቴሌግራም ደንበኛ";
                    }

                    // Only use REAL Telegram ID if resolved from frequent contacts or personal chats. NEVER fake one from phone!
                    $matchedTgId = $realTgMap[mb_strtolower($fullName)] ?? null;

                    // Upsert Customer
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

                    // Upsert TelegramUser
                    // Match existing by telegram_id if real ID exists, or by phone_number
                    $tgUser = null;
                    if ($matchedTgId) {
                        $tgUser = TelegramUser::where('telegram_id', $matchedTgId)->first();
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
                        if (!empty($cleanPhone)) {
                            $tgUser->phone_number = $cleanPhone;
                        }
                        if ($matchedTgId && empty($tgUser->telegram_id)) {
                            $tgUser->telegram_id = $matchedTgId;
                        }
                        $tgUser->is_active = true;
                        $tgUser->source = 'imported';
                        $tgUser->marketing_consent = true;
                        $tgUser->save();
                    } else {
                        // Create cleanly with REAL matched ID or NULL (never fake numeric ID!)
                        TelegramUser::create([
                            'telegram_id' => $matchedTgId ?: null,
                            'first_name' => $fullName,
                            'phone_number' => $cleanPhone ?: null,
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
                $this->info("Completed chunk " . ($chunkIndex + 1) . " of " . count($chunks));
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error("Chunk import error: " . $e->getMessage());
            }
        }

        $this->info("==========================================");
        $this->info("IMPORT COMPLETED CLEANLY!");
        $this->info("Total Customers in Database: {$importedCust}");
        $this->info("Total Telegram Users in Database: {$importedTg}");
        $this->info("==========================================");

        return 0;
    }
}
