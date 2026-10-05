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
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->boolean('is_approved')->default(false)->after('source')->index();
        });

        // Auto approve existing high rating feedbacks for seed display
        \Illuminate\Support\Facades\DB::table('feedbacks')
            ->where('rating', '>=', 4)
            ->update(['is_approved' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn('is_approved');
        });
    }
};
