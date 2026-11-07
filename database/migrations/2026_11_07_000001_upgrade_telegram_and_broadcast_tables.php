<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade telegram_users
        Schema::table('telegram_users', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_users', 'is_excluded')) {
                $table->boolean('is_excluded')->default(false)->index()->after('payload_cache');
            }
            if (!Schema::hasColumn('telegram_users', 'is_active')) {
                $table->boolean('is_active')->default(true)->index()->after('is_excluded');
            }
            if (!Schema::hasColumn('telegram_users', 'source')) {
                $table->string('source', 64)->default('imported')->index()->after('is_active');
            }
        });

        // Make telegram_id nullable in telegram_users safely if MySQL
        try {
            DB::statement('ALTER TABLE `telegram_users` MODIFY `telegram_id` BIGINT NULL');
        } catch (\Throwable $e) {
            // Ignore if already nullable or not supported on connection
        }

        // 2. Upgrade campaigns
        Schema::table('campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('campaigns', 'session_token')) {
                $table->string('session_token', 100)->nullable()->index()->after('status');
            }
            if (!Schema::hasColumn('campaigns', 'notes')) {
                $table->text('notes')->nullable()->after('session_token');
            }
        });

        // 3. Upgrade campaign_recipients
        Schema::table('campaign_recipients', function (Blueprint $table) {
            if (!Schema::hasColumn('campaign_recipients', 'phone')) {
                $table->string('phone', 32)->nullable()->index()->after('recipient_id');
            }
            if (!Schema::hasColumn('campaign_recipients', 'recipient_name')) {
                $table->string('recipient_name', 150)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('campaign_recipients', 'message')) {
                $table->text('message')->nullable()->after('recipient_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            if (Schema::hasColumn('telegram_users', 'is_excluded')) {
                $table->dropColumn('is_excluded');
            }
            if (Schema::hasColumn('telegram_users', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('telegram_users', 'source')) {
                $table->dropColumn('source');
            }
        });

        Schema::table('campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('campaigns', 'session_token')) {
                $table->dropColumn('session_token');
            }
            if (Schema::hasColumn('campaigns', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        Schema::table('campaign_recipients', function (Blueprint $table) {
            if (Schema::hasColumn('campaign_recipients', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('campaign_recipients', 'recipient_name')) {
                $table->dropColumn('recipient_name');
            }
            if (Schema::hasColumn('campaign_recipients', 'message')) {
                $table->dropColumn('message');
            }
        });
    }
};
