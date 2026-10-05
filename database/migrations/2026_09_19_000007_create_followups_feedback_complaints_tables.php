<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('due_date')->index();
            $table->string('status', 32)->default('pending')->index(); // pending, contacted, no_answer, completed
            $table->string('outcome', 64)->nullable(); // satisfied, dissatisfied, complaint, requested_service
            $table->text('notes')->nullable();
            $table->foreignId('handled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->tinyInteger('rating')->unsigned(); // 1 to 5 stars
            $table->text('comment')->nullable();
            $table->string('source', 32)->default('web'); // web, telegram, phone
            $table->timestamps();
        });

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_number', 32)->unique(); // e.g. CMP-0001
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('category', 64); // service_quality, punctuality, staff_behavior, pricing, damage, other
            $table->text('description');
            $table->string('priority', 32)->default('medium')->index(); // low, medium, high, urgent
            $table->string('status', 32)->default('new')->index(); // new, assigned, in_progress, resolved, closed
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('feedbacks');
        Schema::dropIfExists('followups');
    }
};
