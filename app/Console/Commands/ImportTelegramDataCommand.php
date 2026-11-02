<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\TelegramUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTelegramDataCommand extends Command
{
    protected $signature = 'telegram:import-export {path?}';
    protected $description = 'Imports Telegram Desktop exported data (result.json) into database customers and telegram_users';

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

        // 1. Process Frequent Contacts (Real Telegram IDs)
        $frequentList = $data['frequent_contacts']['list'] ?? [];
        $frequentTgMap = [];

        foreach ($frequentList as $fc) {
            $type = $fc['type'] ?? '';
            $rawId = $fc['id'] ?? null;
            $name = trim($fc['name'] ?? '');

            if ($type === 'user' && !empty($rawId) && is_numeric($rawId)) {
                $tgId = (int)$rawId;
                $frequentTgMap[strtolower($name)] = $tgId;

                TelegramUser::updateOrCreate(
                    ['telegram_id' => $tgId],
                    [
                        'first_name' => $name ?: 'ቴሌግራም ደንበኛ',
                        'marketing_consent' => true,
                        'last_interaction_at' => now(),
                    ]
                );
            }
        }
        $this->info("Indexed " . count($frequentTgMap) . " frequent contacts with Telegram IDs.");

        // 2. Process Contacts List inside a single fast DB Transaction
        $contactsList = $data['contacts']['list'] ?? [];
        $totalContacts = count($contactsList);
        $this->info("Importing {$totalContacts} contacts in high-speed transaction...");

        $now = now();
        $importedCust = 0;
        $importedTg = 0;

        DB::beginTransaction();
        try {
            foreach ($contactsList as $contact) {
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

                $matchedTgId = $frequentTgMap[strtolower($fullName)] ?? null;

                // Fast update/insert into Customer
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

                // Fast update/insert into TelegramUser
                $tgNumericId = $matchedTgId;
                if (!$tgNumericId && !empty($cleanPhone)) {
                    $numericPhone = preg_replace('/\D+/', '', $cleanPhone);
                    if (str_starts_with($numericPhone, '0')) {
                        $tgNumericId = (int)('251' . substr($numericPhone, 1));
                    } elseif (strlen($numericPhone) >= 9) {
                        $tgNumericId = (int)$numericPhone;
                    }
                }

                if ($tgNumericId && $tgNumericId > 100000) {
                    TelegramUser::updateOrCreate(
                        ['telegram_id' => $tgNumericId],
                        [
                            'first_name' => $fullName,
                            'phone_number' => $cleanPhone,
                            'marketing_consent' => true,
                            'last_interaction_at' => $now,
                        ]
                    );
                    $importedTg++;
                }
            }

            DB::commit();
            $this->info("==========================================");
            $this->info("IMPORT COMPLETED SUCCESSFULLY!");
            $this->info("Total Customers in Database: {$importedCust}");
            $this->info("Total Telegram Users in Database: {$importedTg}");
            $this->info("==========================================");
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
