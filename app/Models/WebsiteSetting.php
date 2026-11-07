<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    use HasFactory;

    protected $table = 'website_settings';

    protected $fillable = [
        'key',
        'value',
        'group',
    ];

    /**
     * Get a setting value with optional default
     */
    public static function getVal(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();
        if ($setting && $setting->value !== null) {
            return $setting->value;
        }
        $defaults = static::getDefaults();
        return $defaults[$key] ?? $default;
    }

    /**
     * Get a JSON-decoded setting value
     */
    public static function getJson(string $key, array $default = []): array
    {
        $val = static::getVal($key);
        if (!$val) {
            $defaults = static::getDefaults();
            $val = $defaults[$key] ?? null;
        }
        if (is_array($val)) {
            return $val;
        }
        if (is_string($val)) {
            $decoded = json_decode($val, true);
            return is_array($decoded) ? $decoded : $default;
        }
        return $default;
    }

    /**
     * Set a setting value
     */
    public static function setVal(string $key, ?string $value, string $group = 'general'): static
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }

    /**
     * Set a JSON-encoded setting value
     */
    public static function setJson(string $key, array $data, string $group = 'general'): static
    {
        return static::setVal($key, json_encode($data, JSON_UNESCAPED_UNICODE), $group);
    }

    /**
     * Standard system defaults for full customization
     */
    public static function getDefaults(): array
    {
        return [
            'company_name' => 'MEASH CLEANING SOLUTION',
            'company_slogan' => 'Your Environment, Our Responsibility.',
            'company_slogan_am' => 'የአካባቢዎ ንጽህና፣ የኛ ኃላፊነት!',
            'contact_phone_1' => '0970075509',
            'contact_phone_2' => '0998142190',
            'company_email' => 'info@meashcleaning.com',
            'company_address' => 'Ayat, in front of Tesfu Mall, Addis Ababa, Ethiopia',
            'company_address_am' => 'አያት ተስፉ ሞል ፊት ለፊት ህንፃ ላይ፣ አዲስ አበባ፣ ኢትዮጵያ',
            'working_hours_am' => 'ከሰኞ እስከ ቅዳሜ ከጠዋቱ 2:00 እስከ ምሽቱ 12:00 (እሁድ በቀጠሮ)',
            'working_hours_en' => 'Mon - Sat: 8:00 AM - 6:00 PM (Sunday by Appointment)',
            'telegram_channel' => 'https://t.me/meashdeepcleaning',
            'telegram_bot' => 'https://t.me/meash_cleaning_solution_bot',
            'tiktok' => 'https://www.tiktok.com/@meashcleaningsolition',
            'facebook' => 'https://facebook.com/meashclean',
            'instagram' => 'https://instagram.com/@meashdeepcleaning',

            // Payment Accounts
            'telebirr_account_number' => '0970075509',
            'telebirr_account_name' => 'Meash Cleaning / Tesfaye',
            'cbe_account_number' => '1000552467891',
            'cbe_account_name' => 'Meash Cleaning Solution',
            'boa_account_number' => '54321678',
            'boa_account_name' => 'Meash Cleaning Solution',
            'awash_account_number' => '01320876543200',
            'awash_account_name' => 'Meash Cleaning Solution',
            'payment_instructions_am' => 'ክፍያ ፈጽመው ሲያጠናቅቁ የደረሰኝ ቁጥር (Transaction Reference) ወይም ስክሪንሾት ያስገቡልን::',
            'payment_instructions_en' => 'Please provide the transaction reference number after completing the payment.',

            // Coverage Subcities JSON
            'subcities' => json_encode([
                ['id' => 'bole', 'name_am' => 'ቦሌ', 'name_en' => 'Bole', 'active' => true],
                ['id' => 'yeka', 'name_am' => 'የካ', 'name_en' => 'Yeka', 'active' => true],
                ['id' => 'kirkos', 'name_am' => 'ቂርቆስ', 'name_en' => 'Kirkos', 'active' => true],
                ['id' => 'arada', 'name_am' => 'አራዳ', 'name_en' => 'Arada', 'active' => true],
                ['id' => 'gulele', 'name_am' => 'ጉለሌ', 'name_en' => 'Gulele', 'active' => true],
                ['id' => 'lideta', 'name_am' => 'ልደታ', 'name_en' => 'Lideta', 'active' => true],
                ['id' => 'kolfe', 'name_am' => 'ኮልፌ ቀራኒዮ', 'name_en' => 'Kolfe Keranio', 'active' => true],
                ['id' => 'nifas_silk', 'name_am' => 'ንፋስ ስልክ ላፍቶ', 'name_en' => 'Nifas Silk-Lafto', 'active' => true],
                ['id' => 'addis_ketema', 'name_am' => 'አዲስ ከተማ', 'name_en' => 'Addis Ketema', 'active' => true],
                ['id' => 'akaki_kality', 'name_am' => 'አቃቂ ቃሊቲ', 'name_en' => 'Akaki Kality', 'active' => true],
                ['id' => 'lemi_kura', 'name_am' => 'ለሚ ኩራ', 'name_en' => 'Lemi Kura', 'active' => true],
            ], JSON_UNESCAPED_UNICODE),

            // Time Slots JSON
            'time_slots' => json_encode([
                [
                    'id' => 'morning',
                    'name_am' => 'ጠዋት (ከ 2:00 - 6:00 ጠዋት ETH / 8:00 AM - 12:00 PM)',
                    'name_en' => 'Morning (8:00 AM - 12:00 PM)',
                    'slot' => '08:30 AM - 11:30 AM',
                    'active' => true,
                ],
                [
                    'id' => 'afternoon',
                    'name_am' => 'ከሰዓት (ከ 7:00 - 11:00 ከሰዓት ETH / 1:00 PM - 5:00 PM)',
                    'name_en' => 'Afternoon (1:00 PM - 5:00 PM)',
                    'slot' => '01:30 PM - 04:30 PM',
                    'active' => true,
                ],
                [
                    'id' => 'full_day',
                    'name_am' => 'ሙሉ ቀን (ከ 2:30 ጠዋት - 10:30 ከሰዓት)',
                    'name_en' => 'Full Day (8:30 AM - 4:30 PM)',
                    'slot' => '08:30 AM - 04:30 PM',
                    'active' => true,
                ],
            ], JSON_UNESCAPED_UNICODE),

            // Pricing Rules & Discounts JSON
            'pricing_rules' => json_encode([
                'weekly_discount' => 15,
                'biweekly_discount' => 10,
                'monthly_discount' => 5,
                'urgent_surcharge' => 0,
                'sofa_multiplier' => 350,
                'carpet_sqm_multiplier' => 80,
                'window_sqm_multiplier' => 60,
                'deep_cleaning_sqm_multiplier' => 45,
            ], JSON_UNESCAPED_UNICODE),

            // Telegram Bot Texts
            'bot_welcome_am' => "✨ እንኳን ወደ ሜአሽ ጥልቅ የፅዳት አገልግሎት በደህና መጡ!\n\nለሶፋ፣ ምንጣፍ፣ ፍራሽ፣ መስታወትና ለቤትዎ አስተማማኝና ዘመናዊ የጽዳት አገልግሎት እንሰጣለን:: የሚፈልጉትን አገልግሎት ከታች ባሉት አማራጮች ይምረጡ፦",
            'bot_welcome_en' => "✨ Welcome to Meash Deep Cleaning Solution!\n\nWe provide professional cleaning for sofas, carpets, mattresses, windows, and entire residential or office spaces.",
            'bot_help_am' => "📞 ሪሴፕሽን: 0970075509 / 0998142190\n📍 አድራሻ: አያት ተስፉ ሞል ፊት ለፊት፣ አዲስ አበባ\n🌐 ድረ-ገጽ: https://meashcleaning.com\n\nለቀጠሮ ማስያዝ /book ይጫኑ::",
            'bot_help_en' => "📞 Reception: 0970075509 / 0998142190\n📍 Address: Ayat, in front of Tesfu Mall, Addis Ababa\n🌐 Website: https://meashcleaning.com\n\nTo book an appointment type /book.",
        ];
    }

    /**
     * Get all settings merged with defaults
     */
    public static function getAllKeyValue(): array
    {
        $defaults = static::getDefaults();
        $stored = static::pluck('value', 'key')->toArray();
        return array_merge($defaults, $stored);
    }
}
