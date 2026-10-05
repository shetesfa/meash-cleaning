<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 32)->unique(); // e.g. EXP-0001
            $table->string('category', 64)->index();
            // transport, chemicals, materials, employee_payments, equipment_repair, fuel, marketing, rent, utilities, other
            $table->decimal('amount', 12, 2);
            $table->string('reference_number', 64)->nullable()->index();
            $table->text('description');
            $table->string('receipt_path')->nullable();
            $table->date('date')->index();
            $table->foreignId('entered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
