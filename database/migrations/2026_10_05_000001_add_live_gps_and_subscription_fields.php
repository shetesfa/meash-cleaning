<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cleaning_teams', function (Blueprint $table) {
            $table->decimal('current_latitude', 10, 7)->nullable()->after('status');
            $table->decimal('current_longitude', 10, 7)->nullable()->after('current_latitude');
            $table->timestamp('location_updated_at')->nullable()->after('current_longitude');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('subscription_plan', 32)->nullable()->default('one_time')->after('source'); // one_time, weekly, biweekly, monthly
            $table->decimal('subscription_discount', 8, 2)->default(0)->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('cleaning_teams', function (Blueprint $table) {
            $table->dropColumn(['current_latitude', 'current_longitude', 'location_updated_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'subscription_discount']);
        });
    }
};
