<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * R6: before/after photo pairs for the public before/after slider.
     * Two photo rows share one pair_id; one has role = 'before', the
     * other role = 'after'. The public slider shows a pair only when
     * BOTH roles exist (and both rows are status = 'added'). No pairs
     * exist yet, so the block stays hidden until an admin creates one.
     */
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            if (!Schema::hasColumn('photos', 'pair_id')) {
                $table->string('pair_id', 64)->nullable()->index()->after('service_code');
            }
            if (!Schema::hasColumn('photos', 'role')) {
                $table->string('role', 16)->nullable()->index()->after('pair_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            if (Schema::hasColumn('photos', 'role')) {
                $table->dropColumn('role');
            }
            if (Schema::hasColumn('photos', 'pair_id')) {
                $table->dropColumn('pair_id');
            }
        });
    }
};
