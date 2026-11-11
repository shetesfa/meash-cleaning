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
            'company_name_am' => 'ሜአሽ የጽዳት አገልግሎት',
            'company_slogan' => 'Your Environment, Our Responsibility.',
            'company_slogan_am' => 'Your Environment, Our Responsibility.',
            'company_subtitle_en' => 'Premium Commercial Cleaning Solutions — Hotels | Restaurants | Cafés | Offices',
            'company_subtitle_am' => 'ከፍተኛ ጥራት የጠበቀ የንግድ ተቋማት የጽዳት አገልግሎት — ሆቴሎች | ሬስቶራንቶች | ካፌዎች | ቢሮዎች',
            'contact_phone_1' => '0970075509',
            'contact_phone_2' => '0998142190',
            'company_email' => 'meashcleaningsolution@gmail.com',
            'company_address' => 'Ayat Square, Addis Ababa, Ethiopia',
            'company_address_am' => 'አያት አደባባይ፣ አዲስ አበባ',
            'social_handle' => '@meashcleaningsolution',
            'working_hours_am' => 'ከሰኞ እስከ ቅዳሜ ከጠዋቱ 2:00 እስከ ምሽቱ 12:00 (እሁድ እና የሌሊት ፈረቃ በቀጠሮ)',
            'working_hours_en' => 'Mon - Sat: 8:00 AM - 6:00 PM (Sunday & Night Shift by Appointment)',
            'telegram_channel' => 'https://t.me/meashcleaningsolution',
            'telegram_support' => '@meashcleaning',
            'telegram_bot' => 'https://t.me/meash_cleaning_solution_bot',
            'tiktok' => 'https://www.tiktok.com/@meashcleaningsolution',
            'facebook' => 'https://facebook.com/meashcleaningsolution',
            'instagram' => 'https://instagram.com/meashcleaningsolution',
            'about_text_en' => 'Meash Cleaning Solution is a fully licensed and insured premium B2B cleaning partner based at Ayat Square. We deliver exceptional commercial-grade sanitation services designed to protect your facility, project a flawless corporate image, and boost workplace productivity.',
            'about_text_am' => 'ሜአሽ የጽዳት አገልግሎት (MEASH CLEANING) ዋና መሥሪያ ቤቱ በአያት አደባባይ የሚገኝ፣ ህጋዊ ፈቃድ ያለው እና ለንግድ ተቋማት ፕሪሚየም የጽዳት አገልግሎት የሚሰጥ ተቋም ነው። የሥራ ቦታዎን ንፅህና ለድርጅትዎ ስምና ክብር፣ እንዲሁም ለሰራተኞችዎ ጤና እና ምርታማነት ወሳኝ መሆኑን ተረድተን በላቀ ጥራት እንሰራለን።',
            'customer_promise_en' => "We don't just clean; we maintain environments. Every service is backed by a dedicated supervisor to ensure your precise standards are met every single time.",
            'customer_promise_am' => 'የጥራት ማረጋገጫችን እኛ አካባቢዎን ማጽዳት ብቻ ሳይሆን ንፅህናው ዘላቂ እንዲሆን እንሰራለን። እያንዳንዱ ሥራ የእርስዎን መመዘኛ ማሟላቱን የሚያረጋግጥ የተመደበ ተቆጣጣሪ (Supervisor) እንመድባለን።',
            'cta_text_en' => 'Ready for a Cleaner, Healthier Workspace? Partner with Meash Cleaning Solution today. Let us handle the details while you drive your business growth.',
            'cta_text_am' => 'ለድርጅትዎ ንፅህና ዝግጁ ነዎት? የድርጅትዎን ገጽታ ዛሬውኑ ያድሱ። ስለ ጽዳቱ ለእኛ ይተውልንና እርስዎ ሥራዎ ላይ ብቻ ትኩረት ያድርጉ።',

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

            // Time Slots JSON (Includes Night Shift & Weekend)
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
                [
                    'id' => 'night_shift',
                    'name_am' => 'የሌሊት ፈረቃ (ከምሽቱ 1:00 - ሌሊቱ 11:00 / Night Shift)',
                    'name_en' => 'Night Shift (07:00 PM - 05:00 AM)',
                    'slot' => '07:00 PM - 05:00 AM',
                    'active' => true,
                ],
                [
                    'id' => 'weekend_shift',
                    'name_am' => 'ቅዳሜና እሁድ (እንደ ፍላጎትዎ / Weekend Special)',
                    'name_en' => 'Weekend Shift (Flexible Sat & Sun)',
                    'slot' => 'Flexible Weekend',
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
            'bot_welcome_am' => "እንኳን ወደ ሜአሽ የጽዳት አገልግሎት (MEASH CLEANING SOLUTION) በደህና መጡ!\n\n\"Your Environment, Our Responsibility.\"\n\nከፍተኛ ጥራት የጠበቀ የንግድ ተቋማት የጽዳት አገልግሎት — ሆቴሎች | ሬስቶራንቶች | ካፌዎች | ቢሮዎች። የሚፈልጉትን አገልግሎት ከታች ባሉት አማራጮች ይምረጡ፦",
            'bot_welcome_en' => "Welcome to MEASH CLEANING SOLUTION!\n\n\"Your Environment, Our Responsibility.\"\n\nPremium Commercial Cleaning Solutions — Hotels | Restaurants | Cafés | Offices.",
            'bot_help_am' => "ስልክ: 0970075509 / 0998142190\nኢሜይል: meashcleaningsolution@gmail.com\nአድራሻ: አያት አደባባይ፣ አዲስ አበባ\n\nለቀጠሮ ማስያዝ /book ይጫኑ::",
            'bot_help_en' => "Phone: 0970075509 / 0998142190\nEmail: meashcleaningsolution@gmail.com\nAddress: Ayat Square, Addis Ababa, Ethiopia\n\nTo book an appointment type /book.",
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

    /**
     * Get safe settings for authenticated admin users.
     * Sensitive tokens (Telegram bot token, secrets) are strictly excluded and replaced with status booleans.
     */
    public static function getSanitizedAdminSettings(): array
    {
        $all = static::getAllKeyValue();

        $hasToken = !empty($all['telegram_bot_token'] ?? null) || !empty(config('services.telegram.bot_token'));

        // Strip all sensitive credentials, tokens, and secrets
        $sensitiveKeys = [
            'telegram_bot_token',
            'telegram_admin_chat_id',
            'webhook_secret',
            'admin_secret',
            'db_password',
            'sms_gateway_api_key',
            'api_token',
            'secret_token',
            'jwt_secret',
            'private_key',
        ];

        foreach ($sensitiveKeys as $secKey) {
            unset($all[$secKey]);
        }

        // Secondary defense-in-depth: remove any keys matching sensitive patterns
        foreach (array_keys($all) as $k) {
            if (preg_match('/(token|secret|password|credential|private_key|admin_chat)/i', strtolower($k))) {
                unset($all[$k]);
            }
        }

        $all['telegram_bot_configured'] = (bool) $hasToken;

        return $all;
    }

    /**
     * Explicit whitelist of setting keys that are safe to expose to public unauthenticated visitors.
     */
    public static function getPublicAllowlist(): array
    {
        return [
            'company_name',
            'company_name_am',
            'company_slogan',
            'company_slogan_am',
            'company_subtitle_en',
            'company_subtitle_am',
            'contact_phone_1',
            'contact_phone_2',
            'company_email',
            'company_address',
            'company_address_am',
            'social_handle',
            'working_hours_am',
            'working_hours_en',
            'telegram_channel',
            'telegram_support',
            'telegram_bot',
            'tiktok',
            'facebook',
            'instagram',
            'about_text_en',
            'about_text_am',
            'customer_promise_en',
            'customer_promise_am',
            'cta_text_en',
            'cta_text_am',
            'telebirr_account_number',
            'telebirr_account_name',
            'cbe_account_number',
            'cbe_account_name',
            'boa_account_number',
            'boa_account_name',
            'awash_account_number',
            'awash_account_name',
            'payment_instructions_am',
            'payment_instructions_en',
            'subcities',
            'time_slots',
            'pricing_rules',
            'bot_welcome_am',
            'bot_welcome_en',
            'bot_help_am',
            'bot_help_en',
        ];
    }

    /**
     * Get only safe public settings, strictly enforced by an explicit whitelist.
     * Never returns bot tokens, secrets, database credentials, or private internal configurations.
     */
    public static function getPublicKeyValue(): array
    {
        $all = static::getAllKeyValue();
        $allowlist = static::getPublicAllowlist();

        $filtered = [];
        foreach ($allowlist as $allowedKey) {
            if (array_key_exists($allowedKey, $all)) {
                $filtered[$allowedKey] = $all[$allowedKey];
            }
        }

        // Secondary defense-in-depth: enforce regex block against sensitive words
        return array_filter($filtered, function ($val, $key) {
            $keyLower = strtolower($key);
            if (preg_match('/(token|secret|password|credential|private_key|api_key|admin_chat)/i', $keyLower)) {
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }
}
