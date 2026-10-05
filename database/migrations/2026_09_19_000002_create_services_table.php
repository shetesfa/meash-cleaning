<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique(); // sofa, carpet, mattress, glass, home, office
            $table->string('name_en');
            $table->string('name_am');
            $table->text('description_en')->nullable();
            $table->text('description_am')->nullable();
            $table->string('icon', 64)->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->string('unit', 32)->default('piece'); // seat, sqm, room, piece, hour
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
