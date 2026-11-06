<?php

namespace App\Services;

use Carbon\Carbon;
use DateTimeInterface;

class EthiopianCalendarService
{
    /**
     * Julian Day Number (JDN) of Ethiopian Calendar Epoch (Meskerem 1, 1 EC)
     * Corresponding to August 29, 8 AD (Julian) / August 27, 8 AD (Gregorian proleptic).
     */
    public const ETHIOPIAN_EPOCH_JDN = 1724221;

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
     * Determines whether an Ethiopian year is a leap year (ዘመነ ሉቃስ)
     * In Ethiopian calendar, leap years occur every 4 years when (year + 1) % 4 === 0 (i.e. year % 4 === 3).
     * Examples: 2007, 2011, 2015, 2019, 2023 EC.
     */
    public static function isEthiopianLeapYear(int $year): bool
    {
        return (($year + 1) % 4 === 0);
    }

    /**
     * Determines whether a Gregorian year is a leap year.
     */
    public static function isGregorianLeapYear(int $year): bool
    {
        return ($year % 4 === 0 && ($year % 100 !== 0 || $year % 400 === 0));
    }

    /**
     * Returns the number of days in Pagume for a given Ethiopian year (6 in leap year, 5 otherwise).
     */
    public static function getPagumeDays(int $year): int
    {
        return self::isEthiopianLeapYear($year) ? 6 : 5;
    }

    /**
     * Converts a Gregorian date (Y, M, D) to Julian Day Number using Fliegel & van Flandern algorithm.
     */
    public static function gregorianToJdn(int $year, int $month, int $day): int
    {
        $a = intdiv(14 - $month, 12);
        $y = $year + 4800 - $a;
        $m = $month + 12 * $a - 3;
        return $day + intdiv(153 * $m + 2, 5) + 365 * $y + intdiv($y, 4) - intdiv($y, 100) + intdiv($y, 400) - 32045;
    }

    /**
     * Converts a Julian Day Number (JDN) to Gregorian date array [year, month, day].
     */
    public static function jdnToGregorian(int $jdn): array
    {
        $l = $jdn + 68569;
        $n = intdiv(4 * $l, 146097);
        $l = $l - intdiv(146097 * $n + 3, 4);
        $i = intdiv(4000 * ($l + 1), 1461001);
        $l = $l - intdiv(1461 * $i, 4) + 31;
        $j = intdiv(80 * $l, 2447);
        $d = $l - intdiv(2447 * $j, 80);
        $l = intdiv($j, 11);
        $m = $j + 2 - 12 * $l;
        $y = 100 * ($n - 49) + $i + $l;
        return [$y, $m, $d];
    }

    /**
     * Converts an Ethiopian date (year, month, day) to Julian Day Number (JDN).
     */
    public static function ethToJdn(int $year, int $month, int $day): int
    {
        return self::ETHIOPIAN_EPOCH_JDN
            + 365 * ($year - 1)
            + intdiv($year, 4)
            + 30 * ($month - 1)
            + $day - 1;
    }

    /**
     * Converts a Julian Day Number (JDN) to Ethiopian date array [year, month, day].
     */
    public static function jdnToEth(int $jdn): array
    {
        $d = $jdn - self::ETHIOPIAN_EPOCH_JDN;
        $n4 = intdiv($d, 1461);
        $r4 = $d % 1461;
        if ($r4 < 0) {
            $r4 += 1461;
            $n4--;
        }

        // Ethiopian 4-year cycle: Year 0 (365d), Year 1 (365d), Year 2 (366d leap), Year 3 (365d)
        if ($r4 < 365) {
            $yearIn4 = 0;
            $dayInYear = $r4;
        } elseif ($r4 < 730) {
            $yearIn4 = 1;
            $dayInYear = $r4 - 365;
        } elseif ($r4 < 1096) {
            $yearIn4 = 2;
            $dayInYear = $r4 - 730;
        } else {
            $yearIn4 = 3;
            $dayInYear = $r4 - 1096;
        }

        $ey = 4 * $n4 + $yearIn4 + 1;
        $em = intdiv($dayInYear, 30) + 1;
        $ed = ($dayInYear % 30) + 1;
        return [$ey, $em, $ed];
    }

    /**
     * Converts any Gregorian date to Ethiopian date array [year, month, day, month_name_am, ...].
     */
    public static function toEthiopian(string|Carbon|DateTimeInterface $gregorianDate): array
    {
        $date = $gregorianDate instanceof Carbon
            ? $gregorianDate
            : ($gregorianDate instanceof DateTimeInterface
                ? Carbon::instance($gregorianDate)
                : Carbon::parse($gregorianDate));

        $gy = (int)$date->year;
        $gm = (int)$date->month;
        $gd = (int)$date->day;

        $jdn = self::gregorianToJdn($gy, $gm, $gd);
        [$ey, $em, $ed] = self::jdnToEth($jdn);

        $isLeap = self::isEthiopianLeapYear($ey);
        $pagumeDays = self::getPagumeDays($ey);

        return [
            'year' => $ey,
            'month' => $em,
            'day' => $ed,
            'month_name_am' => self::$amharicMonths[$em] ?? '',
            'month_name_en' => self::$englishMonths[$em] ?? '',
            'formatted_am' => (self::$amharicMonths[$em] ?? '') . " {$ed} ቀን {$ey} ዓ.ም.",
            'formatted_en' => (self::$englishMonths[$em] ?? '') . " {$ed}, {$ey}",
            'is_leap_year' => $isLeap,
            'pagume_days' => $pagumeDays,
        ];
    }

    /**
     * Converts an Ethiopian date (year, month 1..13, day 1..30) to Gregorian Carbon instance.
     */
    public static function toGregorian(int $year, int $month, int $day): Carbon
    {
        $jdn = self::ethToJdn($year, $month, $day);
        [$gy, $gm, $gd] = self::jdnToGregorian($jdn);
        return Carbon::createFromDate($gy, $gm, $gd)->startOfDay();
    }

    /**
     * Formats a Gregorian date into full Amharic date string.
     */
    public static function formatAmharic(string|Carbon|DateTimeInterface $gregorianDate): string
    {
        $eth = self::toEthiopian($gregorianDate);
        return $eth['formatted_am'];
    }
}
