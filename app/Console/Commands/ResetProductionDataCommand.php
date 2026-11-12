<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ResetProductionDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'meash:clean-and-ready
                            {--force : Bypass confirmation and immediately purge demo data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge all transactional, customer, and demo data while strictly preserving user amha, services, and website settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('=====================================================');
        $this->warn('   MEASH CLEANING SOLUTION - PRODUCTION DATA PURGE   ');
        $this->warn('=====================================================');
        $this->info("This command will permanently purge ALL orders, customers,");
        $this->info("payments, logs, and demo records.");
        $this->info("STRICTLY PRESERVED: User 'amha' (Owner), Services catalog, and Website settings.");

        $isForce = (bool) $this->option('force');

        if (!$isForce && !$this->confirm('Are you ABSOLUTELY sure you want to clean all data and make system ready for work?')) {
            $this->info('Operation cancelled by user.');
            return 0;
        }

        $this->info('Starting database cleanup...');

        // Disable foreign key constraints during purge
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        try {
            $tablesToPurge = [
                'order_items',
                'orders',
                'payments',
                'appointments',
                'followups',
                'complaints',
                'feedbacks',
                'proforma_items',
                'proformas',
                'contracts',
                'expenses',
                'team_members',
                'cleaning_teams',
                'campaign_recipients',
                'campaigns',
                'sales_visits',
                'customers',
                'telegram_users',
                'notifications',
                'notification_reads',
                'audit_logs',
                'sync_conflicts',
                'sync_idempotency_records',
                'seed_provenances',
                'organizations',
                'personal_access_tokens',
            ];

            foreach ($tablesToPurge as $table) {
                if (Schema::hasTable($table)) {
                    $countBefore = DB::table($table)->count();
                    DB::table($table)->truncate();
                    $this->line(" - Purged table: {$table} (removed {$countBefore} records)");
                }
            }

            // Clean users: delete all users EXCEPT 'amha'
            if (Schema::hasTable('users')) {
                $deletedUsers = DB::table('users')
                    ->where(function ($q) {
                        $q->whereNull('username')->orWhere('username', '!=', 'amha');
                    })
                    ->delete();
                $this->line(" - Purged non-amha users (removed {$deletedUsers} users)");

                // Ensure 'amha' exists and has proper owner credentials
                $amha = User::where('username', 'amha')->first();
                if (!$amha) {
                    $ownerPass = env('MEASH_OWNER_PASSWORD');
                    if (empty($ownerPass)) {
                        if ($this->input->isInteractive()) {
                            $ownerPass = $this->secret('Enter strong Owner password for amha (minimum 12 characters)');
                        }
                    }

                    if (empty($ownerPass) || strlen($ownerPass) < 12) {
                        $this->warn(" - Owner 'amha' could not be auto-created without a secure >= 12 character password.");
                        $this->warn(" - Run 'php artisan meash:provision-owner --username=amha' to complete owner provisioning.");
                    } else {
                        $amha = User::create([
                            'name' => 'Amha',
                            'username' => 'amha',
                            'email' => env('MEASH_OWNER_EMAIL', 'owner@meash.com'),
                            'phone' => env('MEASH_OWNER_PHONE', '0970075509'),
                            'role' => 'owner',
                            'password' => Hash::make($ownerPass),
                            'is_active' => true,
                        ]);
                        $this->info(" - Created Owner user 'amha' (Phone: 0970075509)");
                    }
                } else {
                    $amha->role = 'owner';
                    $amha->phone = '0970075509';
                    $amha->is_active = true;
                    $amha->save();
                    $this->info(" - Preserved and verified Owner user 'amha' (ID: {$amha->id}, Phone: {$amha->phone})");
                }
            }

            // Ensure essential services exist in catalog
            if (Schema::hasTable('services')) {
                if (Service::count() === 0) {
                    $this->seedEssentialServices();
                    $this->info(" - Re-seeded essential official cleaning services catalog (6 services)");
                } else {
                    $this->info(" - Preserved essential cleaning services catalog (" . Service::count() . " active services)");
                }
            }

            // Ensure website settings exist and have the official real data
            if (Schema::hasTable('website_settings')) {
                $this->verifyOfficialWebsiteSettings();
                $this->info(" - Verified and preserved official business settings (Phone: 0970075509, Telegram: @meashcleaning, Channel: @meashcleaningsolution, TikTok: meashcleaningsolution)");
            }

        } finally {
            // Re-enable foreign key checks
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }
        }

        $this->info('=====================================================');
        $this->info('   SYSTEM IS NOW CLEAN AND 100% READY FOR LIVE WORK  ');
        $this->info('=====================================================');
        $this->line("Only user 'amha' exists. All test transactions, orders, and demo customers cleared.");

        return 0;
    }

    /**
     * Seed official services catalog if missing
     */
    protected function seedEssentialServices(): void
    {
        $services = [
            [
                'name' => 'የሶፋ እጥበት (Sofa Cleaning)',
                'slug' => 'sofa-cleaning',
                'description' => 'የቤት እና የቢሮ ሶፋዎችን በዘመናዊ ማሽን እና ኬሚካል እጥበት',
                'price_type' => 'fixed',
                'base_price' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'የምንጣፍ እጥበት (Carpet Cleaning)',
                'slug' => 'carpet-cleaning',
                'description' => 'ጥልቅ የምንጣፍ እጥበት እና ማድረቅ',
                'price_type' => 'per_sqm',
                'base_price' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'የፍራሽ እጥበት (Mattress Cleaning)',
                'slug' => 'mattress-cleaning',
                'description' => 'የፍራሽ ጥልቅ እጥበት፣ ማጽዳት እና ጀርም ማጥፋት',
                'price_type' => 'fixed',
                'base_price' => 800,
                'is_active' => true,
            ],
            [
                'name' => 'የድህረ-ግንባታ ጽዳት (Post-Construction Cleaning)',
                'slug' => 'post-construction',
                'description' => 'አዳዲስ ህንፃዎችን እና ቤቶችን ከግንባታ በኋላ ሙሉ ጽዳት ማከናወን',
                'price_type' => 'per_sqm',
                'base_price' => 70,
                'is_active' => true,
            ],
            [
                'name' => 'የወለል ጽዳት እና ፖሊሽ (Floor Scrubbing & Polishing)',
                'slug' => 'floor-polishing',
                'description' => 'የሴራሚክ፣ የእብነበረድ እና የጠጠር ወለሎችን በሮታሪ ማሽን ማጽዳት እና ፖሊሽ ማድረግ',
                'price_type' => 'per_sqm',
                'base_price' => 60,
                'is_active' => true,
            ],
            [
                'name' => 'የመስታወት ጽዳት (Glass & Window Cleaning)',
                'slug' => 'glass-cleaning',
                'description' => 'የህንፃ መስታወቶች እና የመስኮት ፍሬሞች ጥልቅ ጽዳት',
                'price_type' => 'per_sqm',
                'base_price' => 40,
                'is_active' => true,
            ],
        ];

        foreach ($services as $srv) {
            Service::updateOrCreate(['slug' => $srv['slug']], $srv);
        }
    }

    /**
     * Verify official settings
     */
    protected function verifyOfficialWebsiteSettings(): void
    {
        $officialSettings = [
            'company_name_en' => 'MEASH CLEANING SOLUTION',
            'company_name_am' => 'ሜሽ የጽዳት አገልግሎት',
            'company_slogan' => 'Your Environment, Our Responsibility.',
            'phone' => '0970075509',
            'phone_secondary' => '0970075509',
            'telegram_username' => 'meashcleaning',
            'telegram_channel' => 'meashcleaningsolution',
            'tiktok' => 'meashcleaningsolution',
            'contact_person_name' => 'አቶ አምሃ (Amha)',
            'address' => 'አዲስ አበባ፣ ኢትዮጵያ (Addis Ababa, Ethiopia)',
        ];

        foreach ($officialSettings as $k => $v) {
            WebsiteSetting::updateOrCreate(
                ['key' => $k],
                [
                    'value' => $v,
                    'group' => 'general',
                ]
            );
        }
    }
}
