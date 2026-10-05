<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code', 32)->unique(); // e.g. MEASH-C0001
            $table->string('full_name')->index();
            $table->string('phone', 32)->index();
            $table->string('alt_phone', 32)->nullable();
            $table->text('address')->nullable();
            $table->string('subcity', 64)->nullable()->index(); // Bole, Yeka, Kirkos, etc.
            $table->string('woreda', 32)->nullable();
            $table->string('house_no', 32)->nullable();
            $table->string('landmark')->nullable();
            $table->string('customer_type', 32)->default('individual')->index();
            $table->text('notes')->nullable();
            $table->string('preferred_contact_method', 32)->default('phone');
            $table->string('telegram_user_id', 64)->nullable()->index();
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('consent_timestamp')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
