<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 32)->nullable()->after('payment_status');
            $table->string('payment_reference', 64)->nullable()->after('payment_method');
            $table->timestamp('payment_submitted_at')->nullable()->after('payment_reference');
            $table->foreignId('payment_submitted_by_user_id')->nullable()->after('payment_submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('payment_approved_at')->nullable()->after('payment_submitted_by_user_id');
            $table->foreignId('payment_approved_by_user_id')->nullable()->after('payment_approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['payment_submitted_by_user_id']);
            $table->dropForeign(['payment_approved_by_user_id']);
            $table->dropColumn([
                'payment_method',
                'payment_reference',
                'payment_submitted_at',
                'payment_submitted_by_user_id',
                'payment_approved_at',
                'payment_approved_by_user_id',
            ]);
        });
    }
};
