<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (!Schema::hasColumn('customers', 'status')) {
                    $table->string('status', 32)->default('active')->index()->after('notes');
                }
                if (!Schema::hasColumn('customers', 'total_orders')) {
                    $table->unsignedInteger('total_orders')->default(0)->after('status');
                }
                if (!Schema::hasColumn('customers', 'total_spent')) {
                    $table->decimal('total_spent', 12, 2)->default(0.00)->after('total_orders');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (Schema::hasColumn('customers', 'total_spent')) {
                    $table->dropColumn('total_spent');
                }
                if (Schema::hasColumn('customers', 'total_orders')) {
                    $table->dropColumn('total_orders');
                }
                if (Schema::hasColumn('customers', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
};
