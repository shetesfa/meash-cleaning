<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('telegram_id')->unique();
            $table->string('username')->nullable()->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number', 32)->nullable()->index();
            $table->string('language_code', 16)->default('en');
            $table->string('bot_state', 64)->nullable(); // wizard step tracking
            $table->json('payload_cache')->nullable(); // booking draft state
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('consent_timestamp')->nullable();
            $table->timestamp('last_interaction_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('channel', 32)->default('telegram'); // telegram, sms, in_app
            $table->string('audience_filter', 64)->default('opted_in'); // opted_in, all_customers, corporate, individual
            $table->text('message_text');
            $table->string('status', 32)->default('draft')->index(); // draft, pending_approval, approved, sending, completed, cancelled
            $table->integer('total_targets')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('recipient_id', 64); // telegram_id or phone
            $table->string('status', 32)->default('pending'); // pending, sent, failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('telegram_users');
    }
};
