<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'lifecycle_stage')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('lifecycle_stage', 32)->default('new')->index();
            });
        }

        if (Schema::hasTable('followups') && !Schema::hasColumn('followups', 'type')) {
            Schema::table('followups', function (Blueprint $table) {
                $table->string('type', 32)->default('routine')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'lifecycle_stage')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('lifecycle_stage');
            });
        }

        if (Schema::hasTable('followups') && Schema::hasColumn('followups', 'type')) {
            Schema::table('followups', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
