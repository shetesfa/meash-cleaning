<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cleaning_teams', function (Blueprint $table) {
            $table->id();
            $table->string('team_name', 128);
            $table->foreignId('team_leader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('phone', 32)->nullable();
            $table->string('vehicle_plate', 64)->nullable();
            $table->string('status', 32)->default('active')->index(); // active, on_job, off_duty
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cleaning_team_id')->constrained('cleaning_teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_in_team', 64)->default('cleaner'); // leader, cleaner, driver
            $table->timestamps();

            $table->unique(['cleaning_team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('cleaning_teams');
    }
};
