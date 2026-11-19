<?php

namespace Database\Seeders;

use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

/**
 * 2026-11-19: working hours changed to 24/7 (owner decision).
 *
 * The Plesk deploy runs only `db:seed --force` (no migrate step), and
 * DatabaseSeeder::seedEssentialServices() only runs when the services
 * table is empty — so the live rows would otherwise keep the old
 * Mon-Sat text forever. This seeder performs the one-time data move.
 *
 * Only rows that still carry an old Mon-Sat wording are touched. Any
 * other value — including a later admin edit in the CMS — is left
 * alone, so the hours stay editable in admin afterwards.
 */
class UpdateWorkingHoursTo247Seeder extends Seeder
{
    public function run(): void
    {
        $new = [
            'working_hours_am' => '24/7 — በማንኛውም ሰዓት፣ ሌሊት ጨምሮ',
            'working_hours_en' => '24/7 — Any time, including midnight',
            'trust_hours_am' => '24/7 — በማንኛውም ሰዓት፣ ሌሊት ጨምሮ',
            'trust_hours_en' => '24/7 — Any time, including midnight',
        ];

        // Markers of the old Mon-Sat wordings (the seeded defaults).
        // Note: the live working_hours_en value uses a SPACED hyphen
        // ("Mon - Sat: 8:00 AM - 6:00 PM (Sunday & Night Shift by
        // Appointment)") — 'Mon - Sat' must be listed, or that row is
        // silently skipped (found live 2026-11-19 after the first deploy).
        $oldMarkers = [
            'working_hours_am' => ['ሰኞ'],
            'working_hours_en' => ['Mon-Sat', 'Mon–Sat', 'Mon - Sat'],
            'trust_hours_am' => ['ሰኞ'],
            'trust_hours_en' => ['Mon-Sat', 'Mon–Sat', 'Mon - Sat'],
        ];

        foreach ($new as $key => $value) {
            $query = WebsiteSetting::where('key', $key);
            $query->where(function ($q) use ($oldMarkers, $key) {
                foreach ($oldMarkers[$key] as $marker) {
                    $q->orWhere('value', 'like', '%' . $marker . '%');
                }
            });
            $query->update(['value' => $value]);
        }
    }
}
