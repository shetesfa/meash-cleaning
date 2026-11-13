<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-managed website photos. One row = one image place on the
     * public site (hero slider, service card, gallery, feature strip).
     * The public site reads status = 'added' rows; when the table is
     * empty (or the query fails) website.blade.php falls back to its
     * hardcoded 14-photo list, so the live site can never break.
     */
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            // Full URL (Cloudinary https://...) or local path
            // (/images/gallery/<file>.jpg). Never a credential.
            $table->string('url', 1024);
            $table->string('title_en', 255)->nullable();
            $table->string('title_am', 255)->nullable();
            // hero | service | gallery | feature
            $table->string('section', 32)->index();
            // Service code (floor_ceramic, glass, ...) — only for
            // section = 'service' rows and slider price lookup.
            $table->string('service_code', 64)->nullable()->index();
            // added | not_added
            $table->string('status', 16)->default('not_added')->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
