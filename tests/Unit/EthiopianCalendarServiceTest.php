<?php

namespace Tests\Unit;

use App\Services\EthiopianCalendarService;
use Carbon\Carbon;
use Tests\TestCase;

class EthiopianCalendarServiceTest extends TestCase
{
    public function test_ethiopian_leap_year_detection(): void
    {
        // Ethiopian leap years have (year + 1) % 4 === 0 (or year % 4 === 3)
        $this->assertTrue(EthiopianCalendarService::isEthiopianLeapYear(2007));
        $this->assertTrue(EthiopianCalendarService::isEthiopianLeapYear(2011));
        $this->assertTrue(EthiopianCalendarService::isEthiopianLeapYear(2015));
        $this->assertTrue(EthiopianCalendarService::isEthiopianLeapYear(2019));
        $this->assertTrue(EthiopianCalendarService::isEthiopianLeapYear(2023));

        // Regular years have Pagume 5
        $this->assertFalse(EthiopianCalendarService::isEthiopianLeapYear(2016));
        $this->assertFalse(EthiopianCalendarService::isEthiopianLeapYear(2017));
        $this->assertFalse(EthiopianCalendarService::isEthiopianLeapYear(2018));
        $this->assertFalse(EthiopianCalendarService::isEthiopianLeapYear(2020));

        // Pagume days
        $this->assertEquals(6, EthiopianCalendarService::getPagumeDays(2015));
        $this->assertEquals(5, EthiopianCalendarService::getPagumeDays(2016));
    }

    public function test_new_year_and_pagume_transitions_around_leap_year(): void
    {
        // 2015 EC was a Leap Year (Pagume has 6 days)
        // Pagume 5, 2015 EC -> September 10, 2023
        $p5 = EthiopianCalendarService::toEthiopian('2023-09-10');
        $this->assertEquals(2015, $p5['year']);
        $this->assertEquals(13, $p5['month']);
        $this->assertEquals(5, $p5['day']);
        $this->assertEquals(6, $p5['pagume_days']);
        $this->assertTrue($p5['is_leap_year']);

        // Pagume 6, 2015 EC -> September 11, 2023
        $p6 = EthiopianCalendarService::toEthiopian('2023-09-11');
        $this->assertEquals(2015, $p6['year']);
        $this->assertEquals(13, $p6['month']);
        $this->assertEquals(6, $p6['day']);

        // Meskerem 1, 2016 EC falls on September 12, 2023 (after Pagume 6)
        $m1 = EthiopianCalendarService::toEthiopian('2023-09-12');
        $this->assertEquals(2016, $m1['year']);
        $this->assertEquals(1, $m1['month']);
        $this->assertEquals(1, $m1['day']);
        $this->assertEquals('መስከረም', $m1['month_name_am']);

        // 2016 EC was a regular year (Pagume has 5 days)
        // Pagume 5, 2016 EC -> September 10, 2024
        $p5_2016 = EthiopianCalendarService::toEthiopian('2024-09-10');
        $this->assertEquals(2016, $p5_2016['year']);
        $this->assertEquals(13, $p5_2016['month']);
        $this->assertEquals(5, $p5_2016['day']);
        $this->assertEquals(5, $p5_2016['pagume_days']);
        $this->assertFalse($p5_2016['is_leap_year']);

        // Meskerem 1, 2017 EC falls on September 11, 2024 (regular year transition)
        $m1_2017 = EthiopianCalendarService::toEthiopian('2024-09-11');
        $this->assertEquals(2017, $m1_2017['year']);
        $this->assertEquals(1, $m1_2017['month']);
        $this->assertEquals(1, $m1_2017['day']);
    }

    public function test_gregorian_leap_year_transition_in_february(): void
    {
        // 2024 is a Gregorian leap year (Feb 29 exists)
        $feb28 = EthiopianCalendarService::toEthiopian('2024-02-28');
        $this->assertEquals(2016, $feb28['year']);
        $this->assertEquals(6, $feb28['month']); // Yekatit
        $this->assertEquals(20, $feb28['day']);

        $feb29 = EthiopianCalendarService::toEthiopian('2024-02-29');
        $this->assertEquals(2016, $feb29['year']);
        $this->assertEquals(6, $feb29['month']); // Yekatit
        $this->assertEquals(21, $feb29['day']);

        $mar01 = EthiopianCalendarService::toEthiopian('2024-03-01');
        $this->assertEquals(2016, $mar01['year']);
        $this->assertEquals(6, $mar01['month']); // Yekatit
        $this->assertEquals(22, $mar01['day']);
    }

    public function test_to_gregorian_conversion_roundtrip(): void
    {
        $dates = [
            ['eth' => [2015, 13, 5], 'greg' => '2023-09-10'],
            ['eth' => [2015, 13, 6], 'greg' => '2023-09-11'],
            ['eth' => [2016, 1, 1], 'greg' => '2023-09-12'],
            ['eth' => [2016, 6, 21], 'greg' => '2024-02-29'],
            ['eth' => [2016, 13, 5], 'greg' => '2024-09-10'],
            ['eth' => [2017, 1, 1], 'greg' => '2024-09-11'],
            ['eth' => [2019, 13, 6], 'greg' => '2027-09-11'],
            ['eth' => [2020, 1, 1], 'greg' => '2027-09-12'],
        ];

        foreach ($dates as $item) {
            [$ey, $em, $ed] = $item['eth'];
            $gregCarbon = EthiopianCalendarService::toGregorian($ey, $em, $ed);
            $this->assertEquals($item['greg'], $gregCarbon->toDateString(), "toGregorian({$ey}, {$em}, {$ed}) failed");

            $backEth = EthiopianCalendarService::toEthiopian($item['greg']);
            $this->assertEquals($ey, $backEth['year'], "toEthiopian year mismatch for {$item['greg']}");
            $this->assertEquals($em, $backEth['month'], "toEthiopian month mismatch for {$item['greg']}");
            $this->assertEquals($ed, $backEth['day'], "toEthiopian day mismatch for {$item['greg']}");
        }
    }

    public function test_amharic_formatting(): void
    {
        $res = EthiopianCalendarService::toEthiopian('2024-09-11');
        $this->assertEquals('መስከረም 1 ቀን 2017 ዓ.ም.', $res['formatted_am']);
        $this->assertEquals('Meskerem 1, 2017', $res['formatted_en']);

        $str = EthiopianCalendarService::formatAmharic('2023-09-11');
        $this->assertEquals('ጳጉሜ 6 ቀን 2015 ዓ.ም.', $str);
    }
}
