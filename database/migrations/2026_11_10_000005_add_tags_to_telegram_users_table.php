<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            if (!Schema::hasColumn('telegram_users', 'tags')) {
                $table->string('tags', 255)->nullable()->index()->after('source');
            }
            if (!Schema::hasColumn('telegram_users', 'lifecycle_stage')) {
                $table->string('lifecycle_stage', 64)->nullable()->index()->after('tags');
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_users', function (Blueprint $table) {
            if (Schema::hasColumn('telegram_users', 'lifecycle_stage')) {
                $table->dropColumn('lifecycle_stage');
            }
            if (Schema::hasColumn('telegram_users', 'tags')) {
                $table->dropColumn('tags');
            }
        });
    }
};
