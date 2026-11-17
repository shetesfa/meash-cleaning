<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Batch 19: add ONE admin-managed logo row (section 'logo') so the
     * website logo can be changed from the admin Photos page like every
     * other website image. Idempotent: inserts only when no logo row
     * exists, and never touches the existing 37 photo rows. The public
     * site falls back to /logo.jpg when the row is missing.
     */
    public function up(): void
    {
        if (!DB::table('photos')->where('section', 'logo')->exists()) {
            DB::table('photos')->insert([
                'url' => '/logo.jpg',
                'title_en' => 'Website logo',
                'title_am' => 'የድረ-ገጽ ሎጎ',
                'section' => 'logo',
                'service_code' => null,
                'status' => 'added',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Only remove the seeded row (the fixed /logo.jpg file); an
        // admin-changed logo is their data and stays.
        DB::table('photos')
            ->where('section', 'logo')
            ->where('url', '/logo.jpg')
            ->delete();
    }
};
