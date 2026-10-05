<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique(); // e.g. MEASH-000001
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_team_id')->nullable()->constrained('cleaning_teams')->nullOnDelete();
            $table->string('source', 32)->default('phone')->index(); // website, telegram_bot, telegram_inline, telegram_miniapp, phone, walkin, corporate_contract
            $table->string('order_status', 32)->default('new')->index();
            // new, pending_confirmation, confirmed, assigned, on_the_way, cleaning, completed, paid, follow_up, cancelled, rescheduled, no_show, problem_reported
            $table->string('payment_status', 32)->default('unpaid')->index(); // unpaid, partially_paid, paid, refunded
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->date('appointment_date')->index();
            $table->string('appointment_time_slot', 64)->default('morning'); // morning, afternoon, flexible, or e.g. 09:00 - 11:00
            $table->text('address');
            $table->string('subcity', 64)->nullable()->index();
            $table->string('woreda', 32)->nullable();
            $table->string('house_no', 32)->nullable();
            $table->string('landmark')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('notes')->nullable();
            $table->text('completion_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('version')->default(1); // For Optimistic Concurrency Control
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('item_name'); // e.g. Sofa, Carpet, Mattress
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
