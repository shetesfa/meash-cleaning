<?php

namespace App\Services;

use Carbon\Carbon;

class EthiopianCalendarService
{
    private static array $amharicMonths = [
        1 => 'መስከረም',
        2 => 'ጥቅምት',
        3 => 'ኅዳር',
        4 => 'ታኅሣሥ',
        5 => 'ጥር',
        6 => 'የካቲት',
        7 => 'መጋቢት',
        8 => 'ሚያዝያ',
        9 => 'ግንቦት',
        10 => 'ሰኔ',
        11 => 'ሐምሌ',
        12 => 'ነሐሴ',
        13 => 'ጳጉሜ',
    ];

    private static array $englishMonths = [
        1 => 'Meskerem',
        2 => 'Tikimt',
        3 => 'Hidar',
        4 => 'Tahsas',
        5 => 'Tir',
        6 => 'Yekatit',
        7 => 'Megabit',
        8 => 'Miyazya',
        9 => 'Ginbot',
        10 => 'Sene',
        11 => 'Hamle',
        12 => 'Nehase',
        13 => 'Pagume',
    ];

    /**
     * Converts a Gregorian date to Ethiopian date array [year, month, day, monthNameAm, monthNameEn]
     */
    public static function toEthiopian(string|Carbon $gregorianDate): array
    {
        $date = $gregorianDate instanceof Carbon ? $gregorianDate : Carbon::parse($gregorianDate);
        $gy = $date->year;
        $gm = $date->month;
        $gd = $date->day;

        // Number of days in Gregorian months
        $gDays = [0, 31, ($gy % 4 === 0 && ($gy % 100 !== 0 || $gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        // Day of the year
        $dayOfYear = $gd;
        for ($i = 1; $i < $gm; $i++) {
            $dayOfYear += $gDays[$i];
        }

        // Ethiopian new year usually falls on September 11 (or September 12 before a leap year)
        $isLeapYear = (($gy - 1) % 4 === 3);
        $newYearDay = $isLeapYear ? 12 : 11;

        // Day of Sept 11/12
        $newYearDayOfYear = 31 + $gDays[2] + 31 + 30 + 31 + 30 + 31 + 31 + $newYearDay;

        if ($dayOfYear >= $newYearDayOfYear) {
            $ey = $gy - 7;
            $ethDays = $dayOfYear - $newYearDayOfYear + 1;
        } else {
            $ey = $gy - 8;
            $prevLeapYear = (($gy - 2) % 4 === 3);
            $prevNewYearDay = $prevLeapYear ? 12 : 11;
            $prevTotalDays = 365 + ($prevLeapYear ? 1 : 0);
            $prevNewYearDayOfYear = 254 + ($prevLeapYear ? 1 : 0);
            $ethDays = ($prevTotalDays - $prevNewYearDayOfYear) + $dayOfYear + 1;
        }

        $em = (int) ceil($ethDays / 30);
        $ed = $ethDays - (($em - 1) * 30);

        if ($em > 13) {
            $em = 13;
        }

        return [
            'year' => $ey,
            'month' => $em,
            'day' => $ed,
            'month_name_am' => self::$amharicMonths[$em] ?? '',
            'month_name_en' => self::$englishMonths[$em] ?? '',
            'formatted_am' => (self::$amharicMonths[$em] ?? '') . " {$ed} ቀን {$ey} ዓ.ም.",
            'formatted_en' => (self::$englishMonths[$em] ?? '') . " {$ed}, {$ey}",
        ];
    }
}
