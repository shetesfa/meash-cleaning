<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('plan_type', 32); // weekly, biweekly, monthly
            $table->decimal('discount_percent', 5, 2)->default(10.00);
            $table->string('service_summary');
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('discounted_price', 10, 2)->default(0);
            $table->string('preferred_day', 32)->nullable(); // Monday, Tuesday...
            $table->string('preferred_time_slot', 64)->default('Morning (8:00 AM - 12:00 PM)');
            $table->date('start_date');
            $table->date('next_service_date')->nullable();
            $table->string('status', 32)->default('active')->index(); // active, paused, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
