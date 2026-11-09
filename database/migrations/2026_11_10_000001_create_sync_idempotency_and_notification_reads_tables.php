<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sync_idempotency_records', function (Blueprint $table) {
            $table->id();
            $table->string('client_uuid', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type', 64);
            $table->string('action', 64);
            $table->string('status', 32)->default('in_progress')->index(); // in_progress, completed, failed
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->json('response_payload')->nullable();
            $table->string('lock_token', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            $table->unique(['notification_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
        Schema::dropIfExists('sync_idempotency_records');
    }
};
