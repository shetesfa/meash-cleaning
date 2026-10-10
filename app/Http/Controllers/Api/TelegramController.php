<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\TelegramUser;
use App\Services\EthiopianCalendarService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    private string $botToken;
    private string $botUsername;

    public function __construct()
    {
        $this->botToken = (string)(config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN', '8964703337:AAEV9nUYr83pMQj9GxeLTep1uaWyxkrzX9w'));
        $this->botUsername = (string)(config('services.telegram.bot_username') ?: env('TELEGRAM_BOT_USERNAME', 'meash_cleaning_solution_bot'));
    }

    public function getMiniAppHttpsUrl(): ?string
    {
        $miniAppUrl = config('services.telegram.miniapp_url') ?: env('TELEGRAM_MINIAPP_URL', 'https://shetesfa.github.io/meash-mini-app/');
        if (!empty($miniAppUrl) && str_starts_with($miniAppUrl, 'https://')) {
            return rtrim($miniAppUrl, '/');
        }

        $url = config('app.url');
        if (!empty($url) && str_starts_with($url, 'https://')) {
            return rtrim($url, '/') . '/telegram-miniapp';
        }
        return null;
    }

    public function webhook(Request $request): JsonResponse
    {
        try {
            $secretToken = config('services.telegram.webhook_secret') ?: env('TELEGRAM_WEBHOOK_SECRET');
            if (!empty($secretToken) && $request->header('X-Telegram-Bot-Api-Secret-Token') !== $secretToken) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $update = $request->all();

            // 1. Handle Inline Query (@meashdeepcleaning_solution_bot <query>)
            if (isset($update['inline_query'])) {
                $this->handleInlineQuery($update['inline_query']);
                return response()->json(['status' => 'inline_handled']);
            }

            // 2. Handle Callback Query (Button clicks)
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
                return response()->json(['status' => 'callback_handled']);
            }

            // 3. Handle Private Message
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
                return response()->json(['status' => 'message_handled']);
            }

            return response()->json(['status' => 'ignored']);
        } catch (\Throwable $e) {
            Log::error("Telegram webhook error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
            ], 200); // Return 200 to prevent Telegram from looping failed webhooks, while returning diagnostic details
        }
    }

    /**
     * Handles Telegram Inline Mode query (@meashdeepcleaning_solution_bot <query>)
     */
    public function handleInlineQuery(array $inlineQuery): void
    {
        $queryId = $inlineQuery['id'];
        $rawQuery = trim($inlineQuery['query'] ?? '');
        $query = mb_strtolower($rawQuery, 'UTF-8');

        $results = $this->buildInlineResults($query);

        $resp = $this->telegramApi('answerInlineQuery', [
            'inline_query_id' => $queryId,
            'results' => json_encode($results),
            'cache_time' => 1,
            'is_personal' => false,
        ]);

        if (!($resp['ok'] ?? false)) {
            Log::warning("answerInlineQuery failed for [{$queryId}]: " . json_encode($resp));
        }
    }

    /**
     * Builds attractive rich inline result cards with Services, Social Media, Booking, HD Photos, and Reception
     */
    public function buildInlineResults(string $query = ''): array
    {
        $httpsMiniApp = $this->getMiniAppHttpsUrl();
        $bookingButton = $httpsMiniApp
            ? ['text' => '📅 ቦታ ያስይዙ | Book Now (1 min)', 'web_app' => ['url' => $httpsMiniApp]]
            : ['text' => '📅 ቦታ ያስይዙ | Book Now', 'url' => "https://t.me/{$this->botUsername}?start=book"];

        $allCards = [
            // CARD 1: All-in-One Official Showcase (Shown first on empty search)
            [
                'id' => 'meash_all_in_one',
                'title' => '🌟 ሜሽ የፅዳት አገልግሎት | Meash Cleaning Solution',
                'description' => '🛋 6ቱ አገልግሎቶች | 📅 ቦታ ማስያዣ | 📱 ሶሻል ሚዲያ | 📞 0970075509',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=240&h=240&fit=crop',
                'message_text' => "✨ <b>ሜሽ የፅዳት አገልግሎት | MEASH CLEANING SOLUTION</b>\n" .
                    "💎 <b>ጥራት ያለው ፅዳት:: ንፁህ:: አስተማማኝ::</b>\n" .
                    "<i>(Professional Cleaning. Fresh. Clean. Reliable.)</i>\n\n" .
                    "በአዲስ አበባ እና አካባቢው ዓለም አቀፍ ደረጃቸውን በጠበቁ የኢንዱስትሪ ማሽኖች፣ በስቲም (Steam) እና በደህንነታቸው በተረጋገጡ ኬሚካሎች አስተማማኝ የፅዳት አገልግሎት እንሰጣለን::\n\n" .
                    "🧹 <b>የምንሰጣቸው 6ቱ ዋና ዋና አገልግሎቶች እና ዋጋ:</b>\n" .
                    "1️⃣ 🛋 <b>የሶፋ እጥበት:</b> 350 ብር / ወንበር (ጥልቅ አረፋና ስቲም)\n" .
                    "2️⃣ 🧶 <b>የምንጣፍ እጥበት:</b> 80 ብር / ካሬ ሜትር (ሮተሪ ማሽን)\n" .
                    "3️⃣ 🛏 <b>የፍራሽ ሳኒታይዜሽን:</b> 600 ብር / ፍራሽ (ፀረ-ተባይና አቧራ ትል)\n" .
                    "4️⃣ 🪟 <b>የመስታወት ፅዳት:</b> 70 ብር / ካሬ ሜትር (አያንጸባርቅም)\n" .
                    "5️⃣ 🏠 <b>የመኖሪያ ቤት ጥልቅ ፅዳት:</b> ከ 2,500 ብር ጀምሮ\n" .
                    "6️⃣ 🏢 <b>የቢሮ እና የተቋማት ፅዳት:</b> በኮንትራት ወይም በፕሮፎርማ\n\n" .
                    "📱 <b>ማህበራዊ ሚዲያ (Social Media):</b>\n" .
                    "• 📢 ቴሌግራም ቻናል: @meashdeepcleaning\n" .
                    "• 📱 TikTok: @meashdeepcleaning\n" .
                    "• 👥 Facebook: facebook.com/meashclean\n" .
                    "• 📸 Instagram: @meashdeepcleaning\n\n" .
                    "📞 <b>የሪሴፕሽን ስልክ:</b> 0970075509\n" .
                    "📍 <b>አድራሻ:</b> አዲስ አበባ፣ ሃያት አደባባይ ተስፉ ሞል ፊትለፊት\n\n" .
                    "ቦታ ለማስያዝ ወይም ለመደወል ከታች ያሉትን አማራጮች ይጫኑ 👇",
                'buttons' => [
                    [
                        $bookingButton,
                        ['text' => '🧹 አገልግሎቶች | Services', 'switch_inline_query_current_chat' => 'አገልግሎቶች'],
                    ],
                    [
                        ['text' => '📢 ቴሌግራም ቻናል', 'url' => 'https://t.me/meashdeepcleaning'],
                        ['text' => '📱 TikTok', 'url' => 'https://tiktok.com/@meashdeepcleaning'],
                    ],
                    [
                        ['text' => '👥 Facebook', 'url' => 'https://facebook.com/meashclean'],
                        ['text' => '📸 Instagram', 'url' => 'https://instagram.com/@meashdeepcleaning'],
                    ],
                    [
                        ['text' => '📞 ሪሴፕሽን ደውሉ (0970075509)', 'url' => "https://t.me/{$this->botUsername}?start=contact"],
                    ],
                ],
                'keywords' => ['all', 'official', 'about', 'meash', 'ሜሽ', 'ፅዳት', 'አዲስ አበባ', 'ዋጋ', 'ስልክ', 'ሶሻል', 'social', 'booking'],
            ],

            // CARD 2: Services & Rates Breakdown
            [
                'id' => 'meash_services_rates',
                'title' => '🧹 6ቱ አገልግሎቶች እና የዋጋ ዝርዝር | Services & Rates',
                'description' => 'ሶፋ 350 ብር | ምንጣፍ 80 ብር | ፍራሽ 600 ብር | መስታወት 70 ብር | ቤት 2,500 ብር',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1584820927498-cfe5211fd8bf?w=240&h=240&fit=crop',
                'message_text' => "🧹 <b>የሜሽ የፅዳት አገልግሎቶች እና የዋጋ ዝርዝር</b>\n" .
                    "<i>(Meash Cleaning Services & Official Price List)</i>\n\n" .
                    "1️⃣ 🛋 <b>የሶፋ እና ወንበር ጥልቅ አረፋ እጥበት</b>\n" .
                    "   • ዋጋ: <b>350 ብር</b> / በአንድ መቀመጫ ወንበር\n" .
                    "   • በኃይለኛ ቫኪዩም አቧራ ማውጣት፣ በአረፋ ማጠብ፣ በስቲም ማድረቅ\n\n" .
                    "2️⃣ 🧶 <b>የምንጣፍ ማጠብ እና ስቲም እጥበት</b>\n" .
                    "   • ዋጋ: <b>80 ብር</b> / በአንድ ካሬ ሜትር\n" .
                    "   • በሮተሪ ማሽን ማሸት፣ ጀርም ማጥፋትና ወስደን አድርቀን የማምጣት አማራጭ\n\n" .
                    "3️⃣ 🛏 <b>የፍራሽ ሳኒታይዜሽን እና እጥበት</b>\n" .
                    "   • ዋጋ: <b>600 ብር</b> / በአንድ ፍራሽ\n" .
                    "   • ፀረ-አለርጂ፣ የአቧራ ትሎችን በሙቅ ስቲም 99.9% ማጥፋት\n\n" .
                    "4️⃣ 🪟 <b>የመስታወት እና የፊት ለፊት ህንፃ ፅዳት</b>\n" .
                    "   • ዋጋ: <b>70 ብር</b> / በአንድ ካሬ ሜትር\n" .
                    "   • ምንም አይነት የውሃ ነጠብጣብ የሌለው የሚያንጸባርቅ እጥበት\n\n" .
                    "5️⃣ 🏠 <b>የመኖሪያ ቤት ሙሉ ጥልቅ ፅዳት</b>\n" .
                    "   • ዋጋ: <b>ከ 2,500 ብር</b> ጀምሮ\n" .
                    "   • ከግንባታ በኋላ፣ የኩሽና ቅባት፣ ሽንት ቤት ሳኒታይዜሽንና ወለል ፖሊሽ\n\n" .
                    "6️⃣ 🏢 <b>የቢሮ እና የተቋማት ፅዳት</b>\n" .
                    "   • በስምምነት እና በወርሃዊ ኮንትራት (ከህጋዊ ደረሰኝ ጋር)\n\n" .
                    "📞 <b>የሪሴፕሽን ስልክ:</b> 0970075509",
                'buttons' => [
                    [
                        $bookingButton,
                        ['text' => '📞 ሪሴፕሽን ደውሉ', 'url' => "https://t.me/{$this->botUsername}?start=contact"],
                    ],
                    [
                        ['text' => '📢 ቴሌግራም ቻናል', 'url' => 'https://t.me/meashdeepcleaning'],
                        ['text' => '📱 TikTok', 'url' => 'https://tiktok.com/@meashdeepcleaning'],
                    ],
                ],
                'keywords' => ['services', 'pricing', 'rates', 'አገልግሎቶች', 'ዋጋ', 'ዝርዝር', 'ስንት', 'ምን'],
            ],

            // CARD 3: Social Media & Contacts
            [
                'id' => 'meash_social_media',
                'title' => '📱 ሶሻል ሚዲያ እና የሪሴፕሽን አድራሻ | Social Media & Hotline',
                'description' => 'ቴሌግራም ቻናል | TikTok | Facebook | Instagram | ስልክ 0970075509',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=240&h=240&fit=crop',
                'message_text' => "📱 <b>የሜሽ የፅዳት አገልግሎት ማህበራዊ ሚዲያ እና የመገናኛ አድራሻዎች</b>\n" .
                    "<i>(Meash Cleaning Solution Social Channels & Contacts)</i>\n\n" .
                    "የስራዎቻችንን ቪዲዮዎች፣ ቅናሾችና ጠቃሚ የፅዳት ምክሮችን ለማግኘት ይከተሉን:\n\n" .
                    "• 📢 <b>ቴሌግራም ቻናል:</b> https://t.me/meashdeepcleaning\n" .
                    "• 📱 <b>TikTok:</b> https://tiktok.com/@meashdeepcleaning\n" .
                    "• 👥 <b>Facebook:</b> https://facebook.com/meashclean\n" .
                    "• 📸 <b>Instagram:</b> https://instagram.com/@meashdeepcleaning\n\n" .
                    "📞 <b>የሪሴፕሽን ስልክ መስመሮች:</b>\n" .
                    "• ዋና ስልክ: <b>0970075509</b>\n" .
                    "• ተጨማሪ ስልክ: <b>0970075509</b>\n\n" .
                    "🕒 <b>የስራ ሰዓት:</b>ከሰኞ እስከ ቅዳሜ ከጠዋቱ 2:00 እስከ ምሽቱ 12:00\n" .
                    "📍 <b>አድራሻ:</b> አዲስ አበባ፣ ሃያት አደባባይ ተስፉ ሞል ፊትለፊት",
                'buttons' => [
                    [
                        ['text' => '📢 ቴሌግራም ቻናል', 'url' => 'https://t.me/meashdeepcleaning'],
                        ['text' => '📱 TikTok', 'url' => 'https://tiktok.com/@meashdeepcleaning'],
                    ],
                    [
                        ['text' => '👥 Facebook', 'url' => 'https://facebook.com/meashclean'],
                        ['text' => '📸 Instagram', 'url' => 'https://instagram.com/@meashdeepcleaning'],
                    ],
                    [
                        ['text' => '📞 ሪሴፕሽን: 0970075509', 'url' => "https://t.me/{$this->botUsername}?start=contact"],
                        $bookingButton,
                    ],
                ],
                'keywords' => ['social', 'tiktok', 'telegram', 'facebook', 'instagram', 'contact', 'phone', 'ሶሻል', 'ስልክ', 'አድራሻ', 'ቻናል'],
            ],

            // CARD 4: Instant 1-Minute Booking
            [
                'id' => 'meash_book_now',
                'title' => '📅 በ 1 ደቂቃ ውስጥ ቦታ ያስይዙ | 1-Minute Booking',
                'description' => 'ሶፋ፣ ምንጣፍ፣ ፍራሽ፣ መስታወትና ቤት ፅዳት ቀጠሮ በቀላሉ ይያዙ',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1506784365847-bbad939e9335?w=240&h=240&fit=crop',
                'message_text' => "📅 <b>ፈጣን የቀጠሮ ማስያዣ | Fast Appointment Booking</b>\n" .
                    "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
                    "በጣም ቀላል እና ፈጣን! የሚፈልጉትን አገልግሎት፣ ቀን እና አድራሻዎን በማስገባት በ 1 ደቂቃ ውስጥ ቀጠሮ ማስያዝ ይችላሉ::\n\n" .
                    "1. 🛋 የሶፋ እና ወንበር እጥበት\n" .
                    "2. 🧶 የምንጣፍ ማጠብና ስቲም\n" .
                    "3. 🛏 የፍራሽ ሳኒታይዜሽን\n" .
                    "4. 🪟 የመስታወት ፅዳት\n" .
                    "5. 🏠 የመኖሪያ ቤት ጥልቅ ፅዳት\n" .
                    "6. 🏢 የቢሮ እና የተቋማት ፅዳት\n\n" .
                    "ወዲያውኑ ቦታ ለማስያዝ ከታች ያለውን ቁልፍ ይጫኑ ወይም በ 0970075509 ይደውሉ::",
                'buttons' => [
                    [
                        $bookingButton,
                    ],
                    [
                        ['text' => '📞 በስልክ ለማዘዝ (0970075509)', 'url' => "https://t.me/{$this->botUsername}?start=contact"],
                        ['text' => '🧹 አገልግሎቶች', 'switch_inline_query_current_chat' => 'አገልግሎቶች'],
                    ],
                ],
                'keywords' => ['book', 'booking', 'reserve', 'appointment', 'ቦታ', 'ቀጠሮ', 'ማስያዣ'],
            ],

            // CARD 5: Sofa Cleaning (HD Image of Modern Sofa)
            [
                'id' => 'meash_sofa',
                'title' => '🛋 የሶፋ እና ወንበር ጥልቅ እጥበት | Sofa Cleaning',
                'description' => '350 ብር/ወንበር:: አቧራ፣ እድፍና መጥፎ ጠረን በስቲም ማስወገድ::',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=240&h=240&fit=crop',
                'message_text' => "🛋 <b>የሶፋ እና ወንበር ጥልቅ አረፋ እጥበት (SOFA CLEANING)</b>\n" .
                    "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
                    "💧 <b>የአሰራር ሂደታችን:</b>\n" .
                    "1. በሃይል የበረታ የኢንዱስትሪ ቫኪዩም (የውስጥ አቧራ ማውጣት)\n" .
                    "2. ለጨርቁና ለቆዳው ተስማሚ ሻምፖ በመርጨት እድፍ ማለስለስ\n" .
                    "3. በሮተሪ ብሩሽ በጥንቃቄ ማሸትና እድፍ ማስለቀቅ\n" .
                    "4. በባለሁለት ሞተር ማሽን የቆሸሸውን ውሃ ሙሉ በሙሉ መምጠጥ\n\n" .
                    "💰 <b>ዋጋ:</b> 350 ብር / በአንድ መቀመጫ ወንበር\n" .
                    "✨ ለቆዳ (Leather)፣ ለቬልቬት፣ ለጨርቅና ለተለያዩ ሶፋዎች ፍቱን::\n\n" .
                    "📞 <b>ስልክ:</b> 0970075509\n" .
                    "🤖 <b>ቦት:</b> @meashdeepcleaning_solution_bot",
                'buttons' => [
                    [
                        $bookingButton,
                        ['text' => '📞 በስልክ ለማዘዝ', 'url' => "https://t.me/{$this->botUsername}?start=contact"],
                    ],
                ],
                'keywords' => ['sofa', 'couch', 'chair', 'furniture', 'seats', 'ሶፋ', 'ወንበር', 'ሶፋዎች'],
            ],

            // CARD 6: Carpet Washing (HD Image of Modern Clean Carpet)
            [
                'id' => 'meash_carpet',
                'title' => '🧶 የምንጣፍ ማጠብና ስቲም እጥበት | Carpet Washing',
                'description' => '80 ብር/ካሬ ሜትር:: በቦታው ወይም ወስደን በሮተሪ ማሽን ማጠብ::',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=240&h=240&fit=crop',
                'message_text' => "🧶 <b>የምንጣፍ ማጠብ እና ስቲም እጥበት (CARPET WASHING)</b>\n" .
                    "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
                    "✨ የመኖሪያ ቤት፣ የኤምባሲ እና የሆቴል ምንጣፎችን ወደነበሩበት ውበት እንመልሳለን!\n" .
                    "• 99.9% ባክቴሪያ እና አለርጂን በሙቅ ስቲም ያጠፋል\n" .
                    "• ደስ የሚል መዓዛ ያላቸው የተፈጥሮ ማጽጃዎች\n" .
                    "• በቦታው ላይ የማጠብ ወይም ወስደን አድርቀን የማምጣት አማራጭ\n\n" .
                    "💰 <b>ዋጋ:</b> 80 ብር / በአንድ ካሬ ሜትር\n\n" .
                    "📞 <b>ስልክ:</b> 0970075509\n" .
                    "🤖 <b>ቦት:</b> @meashdeepcleaning_solution_bot",
                'buttons' => [
                    [
                        $bookingButton,
                        ['text' => '📞 በስልክ ለማዘዝ', 'url' => "https://t.me/{$this->botUsername}?start=contact"],
                    ],
                ],
                'keywords' => ['carpet', 'rug', 'floor', 'ምንጣፍ', 'ምንጣፎች'],
            ],

            // CARD 7: Mattress Sanitization (HD Image of Hotel Mattress)
            [
                'id' => 'meash_mattress',
                'title' => '🛏 የፍራሽ ሳኒታይዜሽንና እጥበት | Mattress Sanitization',
                'description' => '600 ብር/ፍራሽ:: በሙቅ ስቲም ፀረ-ተባይና የአቧራ ትሎች ማጥፋት::',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=240&h=240&fit=crop',
                'message_text' => "🛏 <b>የፍራሽ ሳኒታይዜሽን እና ጥልቅ እጥበት (MATTRESS SANITIZATION)</b>\n" .
                    "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
                    "😴 <b>ፍጹም ንፁህ እና ጤናማ እንቅልፍ ይተኛሉ:</b>\n" .
                    "• በአይን የማይታዩ የአቧራ ትሎችን እና አለርጂን ያጠፋል\n" .
                    "• የላብ፣ የውሃ እና የቆሻሻ ምልክቶችን ያስለቅቃል\n" .
                    "• የሆስፒታል ደረጃውን የጠበቀ ፀረ-ባክቴሪያ ሳኒታይዜሽን\n\n" .
                    "💰 <b>ዋጋ:</b> 600 ብር / በአንድ ፍራሽ (ኪንግ፤ ኩዊን፤ ነጠላ)\n\n" .
                    "📞 <b>ስልክ:</b> 0970075509\n" .
                    "🤖 <b>ቦት:</b> @meashdeepcleaning_solution_bot",
                'buttons' => [
                    [
                        $bookingButton,
                        ['text' => '📞 በስልክ ለማዘዝ', 'url' => "https://t.me/{$this->botUsername}?start=contact"],
                    ],
                ],
                'keywords' => ['mattress', 'bed', 'sleep', 'allergy', 'ፍራሽ', 'አልጋ'],
            ],

            // CARD 8: Glass Facade (HD Image of Glass Architecture)
            [
                'id' => 'meash_glass',
                'title' => '🪟 የመስታወት እና ፊት ለፊት ግድግዳ ፅዳት | Glass Facade',
                'description' => '70 ብር/ካሬ ሜትር:: የቪላ፤ አፓርታማና የህንፃ መስታወት ፅዳት::',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=240&h=240&fit=crop',
                'message_text' => "🪟 <b>የመስታወት እና የፊት ለፊት ህንፃ ፅዳት (GLASS & FACADE)</b>\n" .
                    "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
                    "☀️ ምንም አይነት የውሃ ነጠብጣብ የሌለው ፍጹም የሚያንጸባርቅ መስታወት!\n" .
                    "• የውስጥ እና የውጭ መስታወት እጥበት\n" .
                    "• የረንዳ መስታወት፣ የሻወር ካቢኔ እና የበር መስታወት\n" .
                    "• ለከፍተኛ ህንፃዎች ደህንነታቸው የተጠበቀ ዘመናዊ መወጣጫዎች\n\n" .
                    "💰 <b>ዋጋ:</b> 70 ብር / በአንድ ካሬ ሜትር\n\n" .
                    "📞 <b>ስልክ:</b> 0970075509\n" .
                    "🤖 <b>ቦት:</b> @meashdeepcleaning_solution_bot",
                'buttons' => [
                    [
                        $bookingButton,
                        ['text' => '📞 በስልክ ለማዘዝ', 'url' => "https://t.me/{$this->botUsername}?start=contact"],
                    ],
                ],
                'keywords' => ['glass', 'window', 'facade', 'መስታወት', 'መስኮት'],
            ],

            // CARD 9: Residential Deep Clean (HD Image of Spotless Home)
            [
                'id' => 'meash_home',
                'title' => '🏠 የመኖሪያ ቤት ሙሉ ጥልቅ ፅዳት | Residential Deep Clean',
                'description' => 'ከ 2,500 ብር:: የግንባታ ማጠናቀቂያ ወይም የመኖሪያ ቤት ፅዳት::',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=240&h=240&fit=crop',
                'message_text' => "🏠 <b>የመኖሪያ ቤት ሙሉ ጥልቅ ፅዳት (RESIDENTIAL DEEP CLEAN)</b>\n" .
                    "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
                    "✨ አጠቃላይ የቪላ እና የአፓርታማ ጥልቅ ፅዳት:\n" .
                    "• ከግንባታ በኋላ የቀሩ የቀለምና የሲሚንቶ ቅርፊቶች ማስወገድ\n" .
                    "• የኩሽና ቅባት ማስለቀቅ እና የወለል ንጣፎች እጥበት\n" .
                    "• የመታጠቢያ ቤትና ሽንት ቤት ፀረ-ጀርም ሳኒታይዜሽን\n" .
                    "• የወለል ማሽነሪ ፖሊሽ እና አጠቃላይ ንፅህና\n\n" .
                    "💰 <b>ዋጋ:</b> ከ 2,500 ብር ጀምሮ\n\n" .
                    "📞 <b>ስልክ:</b> 0970075509\n" .
                    "🤖 <b>ቦት:</b> @meashdeepcleaning_solution_bot",
                'buttons' => [
                    [
                        $bookingButton,
                        ['text' => '📞 በስልክ ለማዘዝ', 'url' => "https://t.me/{$this->botUsername}?start=contact"],
                    ],
                ],
                'keywords' => ['home', 'house', 'residential', 'apartment', 'villa', 'ቤት', 'ቪላ'],
            ],

            // CARD 10: Commercial & Office Cleaning (HD Image of Modern Office)
            [
                'id' => 'meash_office',
                'title' => '🏢 የቢሮ እና ተቋማት ፅዳት | Commercial & Office Cleaning',
                'description' => 'ለባንኮች፤ ኤምባሲዎች፤ ሆቴሎች እና ድርጅቶች በኮንትራት ወይም በፕሮፎርማ::',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=240&h=240&fit=crop',
                'message_text' => "🏢 <b>የቢሮ እና የተቋማት ፅዳት (COMMERCIAL & CORPORATE)</b>\n" .
                    "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
                    "💼 የስራ ቦታዎ ሁልጊዜ ሳቢ እና ንፁህ ሆኖ እንዲቆይ:\n" .
                    "• ሳምንታዊ እና ወርሃዊ መደበኛ የኮንትራት ስምምነቶች\n" .
                    "• የደንብ ልብስ የለበሱ የታመኑና የሰለጠኑ ቋሚ ሰራተኞች\n" .
                    "• ህጋዊ ደረሰኝ (VAT/TOT) እና የክፍያ ስምምነት ሰነዶች\n\n" .
                    "📞 <b>ስልክ:</b> 0970075509\n" .
                    "🤖 <b>ቦት:</b> @meashdeepcleaning_solution_bot",
                'buttons' => [
                    [
                        ['text' => '📞 ፕሮፎርማ ይጠይቁ (0970075509)', 'url' => "https://t.me/{$this->botUsername}?start=contact"],
                    ],
                ],
                'keywords' => ['office', 'commercial', 'corporate', 'hotel', 'bank', 'ቢሮ', 'ተቋም', 'ድርጅት'],
            ],

            // CARD 11: Help Guide & Commands
            [
                'id' => 'meash_bot_help',
                'title' => 'ℹ የቦቱ አጠቃቀም እና ትዕዛዞች | Bot Commands & Guide',
                'description' => '/start, /services, /book, /status, /contact, /help',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=240&h=240&fit=crop',
                'message_text' => "ℹ <b>የሜሽ ቴሌግራም ቦት የአጠቃቀም መመሪያ</b>\n\n" .
                    "የሚከተሉትን ትዕዛዞች መጠቀም ይችላሉ:\n" .
                    "• <code>/start</code> - ዋናውን ማውጫ ይከፍታል\n" .
                    "• <code>/services</code> - የ 6ቱ አገልግሎቶችና የዋጋ ዝርዝር\n" .
                    "• <code>/book</code> - በ 1 ደቂቃ ውስጥ ቀጠሮ ማስያዣ\n" .
                    "• <code>/status ORD-...</code> - የትዕዛዝ ሁኔታ መከታተያ\n" .
                    "• <code>/contact</code> - የሪሴፕሽን ስልክ አድራሻ (0970075509)\n" .
                    "• <code>/help</code> - ይህንን መመሪያ ያሳያል\n\n" .
                    "በተጨማሪም በማንኛውም ቻት ውስጥ <code>@meashdeepcleaning_solution_bot</code> ብለው በመጻፍ አገልግሎቶችን ለወዳጅዎ ማጋራት ይችላሉ!",
                'buttons' => [
                    [
                        ['text' => '🚀 ቦቱን ክፈት | Open Bot', 'url' => "https://t.me/{$this->botUsername}?start=help"],
                        ['text' => '📢 ቴሌግራም ቻናል', 'url' => 'https://t.me/meashdeepcleaning'],
                    ],
                ],
                'keywords' => ['help', 'commands', 'guide', 'መመሪያ', 'ትዕዛዝ', 'እርዳታ'],
            ],
        ];

        $matched = [];
        foreach ($allCards as $card) {
            if (empty($query)) {
                $matched[] = $card;
            } else {
                $found = false;
                foreach ($card['keywords'] as $kw) {
                    if (str_contains($kw, $query) || str_contains($query, $kw)) {
                        $found = true;
                        break;
                    }
                }
                if ($found || str_contains(mb_strtolower($card['title'], 'UTF-8'), $query)) {
                    $matched[] = $card;
                }
            }
        }

        // Convert to Telegram InlineQueryResultArticle format with HTML parse_mode & HD image thumbnails
        $results = [];
        foreach ($matched as $c) {
            $results[] = [
                'type' => 'article',
                'id' => $c['id'],
                'title' => $c['title'],
                'description' => $c['description'],
                'thumbnail_url' => $c['thumbnail_url'],
                'thumbnail_width' => 120,
                'thumbnail_height' => 120,
                'input_message_content' => [
                    'message_text' => $c['message_text'],
                    'parse_mode' => 'HTML',
                ],
                'reply_markup' => [
                    'inline_keyboard' => $c['buttons'],
                ],
            ];
        }

        return $results;
    }

    /**
     * Handles private chat messages with HTML parse_mode
     */
    public function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'] ?? null;
        $from = $message['from'] ?? [];
        $text = trim($message['text'] ?? '');
        $location = $message['location'] ?? null;
        $contact = $message['contact'] ?? null;

        if (!$chatId) return;

        // Upsert Telegram user record
        $tgUser = null;
        $tgId = $from['id'] ?? $chatId;
        if (!empty($tgId)) {
            try {
                $tgUser = TelegramUser::updateOrCreate(
                    ['telegram_id' => $tgId],
                    [
                        'username' => $from['username'] ?? null,
                        'first_name' => $from['first_name'] ?? ($message['chat']['first_name'] ?? null),
                        'last_name' => $from['last_name'] ?? null,
                        'language_code' => $from['language_code'] ?? 'am',
                        'last_interaction_at' => now(),
                    ]
                );
            } catch (\Throwable $th) {
                Log::warning('Could not record telegram user: ' . $th->getMessage());
            }
        }

        // Handle Mini App WebApp Data submission (Telegram.WebApp.sendData)
        if (isset($message['web_app_data'])) {
            $this->handleWebAppData($chatId, $from, $message['web_app_data']);
            return;
        }

        // Handle Cancellation
        if ($text === '/cancel' || $text === 'ሰርዝ' || $text === '❌ ሰርዝ' || $text === '❌ ሰርዝ (Cancel)') {
            if ($tgUser) {
                $tgUser->bot_state = null;
                $tgUser->payload_cache = null;
                $tgUser->save();
            }
            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => "❌ <b>የቀጠሮ ምዝገባው ተሰርዟል::</b>\n\nአዲስ ቀጠሮ ለመያዝ /book ወይም /start ብለው መጀመር ይችላሉ::",
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['remove_keyboard' => true]),
            ]);
            return;
        }

        // Handle Interactive Booking Wizard States (Name, Phone, Location Pin, Address)
        if ($tgUser && $tgUser->bot_state && str_starts_with($tgUser->bot_state, 'booking_')) {
            if ($this->handleInteractiveBookingMessage($tgUser, $message, $chatId)) {
                return;
            }
        }

        // Handle Deep Linking (/start book, /start contact, etc.)
        if (str_starts_with($text, '/start')) {
            $param = trim(substr($text, 6));
            if ($param === 'book') {
                $this->sendBookingGuide($chatId);
                return;
            } elseif ($param === 'contact') {
                $this->sendContactInfo($chatId);
                return;
            } elseif ($param === 'services') {
                $this->sendServicesMenu($chatId);
                return;
            } elseif ($param === 'help') {
                $this->sendHelpMessage($chatId);
                return;
            }

            $welcome = "👋 <b>እንኳን ወደ ሜሽ የፅዳት አገልግሎት በደህና መጡ!</b>\n" .
                "<b>Welcome to Meash Cleaning Solution!</b>\n\n" .
                "✨ <b>ጥራት ያለው ፅዳት:: ንፁህ:: አስተማማኝ::</b>\n" .
                "<i>(Professional Cleaning. Fresh. Clean. Reliable.)</i>\n\n" .
                "በአዲስ አበባ አስተማማኝ የሶፋ፣ የምንጣፍ፣ የፍራሽ፣ የመስታወት እና የቤት/ቢሮ ጥልቅ ፅዳት እንሰጣለን::\n\n" .
                "ዛሬ ምን ማፅዳት ይፈልጋሉ? ከታች ካሉት አማራጮች አንዱን ይምረጡ:";

            $httpsUrl = $this->getMiniAppHttpsUrl();
            $wizardButton = ['text' => '⚡ በቦቱ ቀጠሮ ያስይዙ (1 ደቂቃ)', 'callback_data' => 'book_start_wizard'];
            $appButton = $httpsUrl
                ? ['text' => '📱 በቴሌግራም ሚኒ አፕ (Mini App)', 'web_app' => ['url' => $httpsUrl]]
                : ['text' => '📅 ቦታ ያስይዙ | Book Now', 'callback_data' => 'menu_book'];

            $replyMarkup = [
                'inline_keyboard' => [
                    [
                        $wizardButton,
                    ],
                    [
                        $appButton,
                    ],
                    [
                        ['text' => '🛋 አገልግሎቶችና ዋጋ | Services', 'callback_data' => 'menu_services'],
                        ['text' => '📋 የትዕዛዝ ሁኔታ | Status', 'callback_data' => 'menu_status'],
                    ],
                    [
                        ['text' => '💬 ሪሴፕሽን ያግኙ | Reception', 'callback_data' => 'menu_contact'],
                        ['text' => '⭐ አስተያየት ይስጡ | Feedback', 'callback_data' => 'menu_feedback'],
                    ],
                    [
                        ['text' => '📢 ቴሌግራም ቻናል', 'url' => 'https://t.me/meashdeepcleaning'],
                        ['text' => '📱 TikTok', 'url' => 'https://tiktok.com/@meashdeepcleaning'],
                    ],
                    [
                        ['text' => '👥 Facebook', 'url' => 'https://facebook.com/meashclean'],
                        ['text' => '📸 Instagram', 'url' => 'https://instagram.com/@meashdeepcleaning'],
                    ],
                    [
                        ['text' => '📲 ለሌሎች አጋራ | Share', 'switch_inline_query_current_chat' => ''],
                    ],
                ],
            ];

            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => $welcome,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($replyMarkup),
            ]);
        } elseif (str_starts_with($text, '/services') || $text === 'አገልግሎቶች') {
            $this->sendServicesMenu($chatId);
        } elseif (str_starts_with($text, '/book') || $text === 'ቀጠሮ') {
            $this->startInteractiveBooking($tgUser, $chatId);
        } elseif (str_starts_with($text, '/contact') || $text === 'ስልክ' || $text === 'ሪሴፕሽን') {
            $this->sendContactInfo($chatId);
        } elseif (str_starts_with($text, '/status') || str_starts_with($text, 'ORD-')) {
            $orderCode = strtoupper(trim(str_replace('/status', '', $text)));
            $this->checkOrderStatus($chatId, $orderCode);
        } elseif (str_starts_with($text, '/help') || $text === 'እርዳታ') {
            $this->sendHelpMessage($chatId);
        } else {
            // Check if this is a direct booking message with phone number (e.g. 09... / 07...)
            if ($this->tryParseDirectMessageBooking($chatId, $from, $text)) {
                return;
            }

            // General guidance
            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => "ጤና ይስጥልኝ! ሜሽ የፅዳት አገልግሎት ነው::\n\nቀጠሮ ለመያዝ፣ አገልግሎቶችን ለማየት ወይም ሪሴፕሽን ለማነጋገር /start ብለው ይላኩ::\n\nለበለጠ መረጃ: 0970075509\nቦት: @meashdeepcleaning_solution_bot",
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '🚀 ዋና ማውጫ | Main Menu', 'callback_data' => 'menu_start']],
                        [['text' => '📅 ቦታ ያስይዙ | Book Now', 'callback_data' => 'menu_book']],
                    ]
                ]),
            ]);
        }
    }

    private function tryParseDirectMessageBooking(int|string $chatId, array $from, string $text): bool
    {
        // Extract Ethiopian phone number
        if (!preg_match('/(09\d{8}|07\d{8}|\+2519\d{8}|\+2517\d{8})/', $text, $matches)) {
            return false;
        }

        $phone = $matches[1];
        $lines = array_values(array_filter(array_map('trim', explode("\n", $text))));

        // Extract Name
        $name = null;
        if (!empty($lines[0]) && !preg_match('/\d/', $lines[0])) {
            $name = $lines[0];
        } else {
            $name = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: 'ውድ ደንበኛችን';
        }

        // Detect Service
        $lowerText = mb_strtolower($text, 'UTF-8');
        $serviceCode = 'sofa';
        $serviceNameAm = 'የሶፋ ጥልቅ አረፋ እጥበት';
        $unitPrice = 350;
        $qty = 5;

        if (str_contains($lowerText, 'ምንጣፍ') || str_contains($lowerText, 'carpet')) {
            $serviceCode = 'carpet';
            $serviceNameAm = 'የምንጣፍ ማጠብና ስቲም እጥበት';
            $unitPrice = 80;
            $qty = 20;
        } elseif (str_contains($lowerText, 'ፍራሽ') || str_contains($lowerText, 'mattress')) {
            $serviceCode = 'mattress';
            $serviceNameAm = 'የፍራሽ ሳኒታይዜሽን';
            $unitPrice = 600;
            $qty = 2;
        } elseif (str_contains($lowerText, 'መስታወት') || str_contains($lowerText, 'glass')) {
            $serviceCode = 'glass';
            $serviceNameAm = 'የመስታወት ፅዳት';
            $unitPrice = 70;
            $qty = 15;
        } elseif (str_contains($lowerText, 'ቤት') || str_contains($lowerText, 'home')) {
            $serviceCode = 'residential_deep';
            $serviceNameAm = 'የመኖሪያ ቤት ሙሉ ጥልቅ ፅዳት';
            $unitPrice = 2500;
            $qty = 1;
        } elseif (str_contains($lowerText, 'ቢሮ') || str_contains($lowerText, 'office')) {
            $serviceCode = 'commercial';
            $serviceNameAm = 'የቢሮ እና የተቋማት ፅዳት';
            $unitPrice = 3500;
            $qty = 1;
        }

        // Subcity / address detection
        $subcities = [
            'ለሚኩራ' => 'Lemi Kura',
            'ለሚ ኩራ' => 'Lemi Kura',
            'ቦሌ' => 'Bole',
            'ቂርቆስ' => 'Kirkos',
            'የካ' => 'Yeka',
            'አራዳ' => 'Arada',
            'አዲስ ከተማ' => 'Addis Ketema',
            'ልደታ' => 'Lideta',
            'ኮልፌ' => 'Kolfe Keranio',
            'ጉለሌ' => 'Gullele',
            'አቃቂ' => 'Akaky Kaliti',
            'ንፋስ ስልክ' => 'Nifas Silk-Lafto',
        ];

        $subcity = 'Addis Ababa';
        $address = 'አዲስ አበባ';
        foreach ($subcities as $amKw => $subcEn) {
            if (str_contains($text, $amKw)) {
                $subcity = $subcEn;
                $address = $amKw;
                break;
            }
        }

        foreach ($lines as $line) {
            if ($line !== $name && !str_contains($line, $phone) && !str_contains($line, 'ሶፋ') && !str_contains($line, 'monday')) {
                $address = $line;
            }
        }

        // Date calculation
        $apptDate = now()->addDay();
        if (str_contains($lowerText, 'monday') || str_contains($lowerText, 'ሰኞ')) {
            $apptDate = now()->next(\Carbon\Carbon::MONDAY);
        }

        // Find or create customer
        $customer = Customer::where('phone', $phone)->first();
        if (!$customer) {
            $customer = Customer::create([
                'customer_code' => Customer::generateNextCode(),
                'full_name' => $name,
                'phone' => $phone,
                'address' => $address,
                'subcity' => $subcity,
                'telegram_user_id' => (string)$chatId,
                'marketing_consent' => true,
                'consent_timestamp' => now(),
            ]);
        }

        $orderNumber = Order::generateNextNumber();
        $order = Order::create([
            'order_number' => $orderNumber,
            'customer_id' => $customer->id,
            'source' => 'telegram_bot',
            'order_status' => 'new',
            'payment_status' => 'unpaid',
            'appointment_date' => $apptDate->format('Y-m-d'),
            'appointment_time_slot' => 'morning',
            'address' => $address,
            'subcity' => $subcity,
            'notes' => "Direct message booking: {$text}",
            'version' => 1,
        ]);

        $service = Service::where('code', $serviceCode)->first();
        OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $service?->id,
            'item_name' => $serviceNameAm,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'subtotal' => (float)$qty * (float)$unitPrice,
        ]);

        \App\Models\Appointment::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'appointment_date' => $order->appointment_date,
            'status' => 'scheduled',
            'notes' => 'Direct Telegram booking',
        ]);

        // Send instant booking confirmation SMS to customer's mobile phone
        try {
            SmsService::sendBookingConfirmation($order);
        } catch (\Throwable $th) {
            Log::warning('Direct SMS confirmation failed: ' . $th->getMessage());
        }

        // Send instant Telegram confirmation
        $totalEst = $order->fresh()->total_amount;
        $ethAppt = \App\Services\EthiopianCalendarService::toEthiopian($order->appointment_date);

        $confirmMsg = "🎉 <b>እናመሰግናለን {$name}! የቀጠሮ ጥያቄዎ በተሳካ ሁኔታ ተመዝግቧል!</b>\n\n" .
            "📋 <b>የትዕዛዝ ቁጥር:</b> <code>{$order->order_number}</code>\n" .
            "👤 <b>ደንበኛ:</b> {$name}\n" .
            "📱 <b>ስልክ:</b> {$phone}\n" .
            "🛋 <b>አገልግሎት:</b> {$serviceNameAm}\n" .
            "📍 <b>አድራሻ:</b> {$address} ({$subcity})\n" .
            "📅 <b>የቀጠሮ ቀን:</b> {$order->appointment_date->format('Y-m-d')} ({$ethAppt['formatted_am']}) ጠዋት 2:00\n" .
            "💰 <b>የተገመተ ዋጋ:</b> " . number_format($totalEst, 2) . " ብር\n\n" .
            "📩 <b>የማረጋገጫ ኤስኤምኤስ (SMS) ወደ ስልክዎ ({$phone}) ተልኳል::</b>\n" .
            "የሜሽ ሪሴፕሽን ቡድን በቅርቡ በስልክ (0970075509) ደውሎ ያረጋግጥልዎታል:: ስለመረጡን እናመሰግናለን! 🙏";

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $confirmMsg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '📋 የትዕዛዝ ሁኔታ | Status', 'callback_data' => 'menu_status'],
                        ['text' => '📞 ሪሴፕሽን (0970075509)', 'callback_data' => 'menu_contact'],
                    ],
                    [
                        ['text' => '📢 ቴሌግራም ቻናል', 'url' => 'https://t.me/meashdeepcleaning'],
                    ]
                ]
            ]),
        ]);

        return true;
    }

    /**
     * Handles callback queries from inline buttons
     */
    public function handleCallbackQuery(array $callbackQuery): void
    {
        $id = $callbackQuery['id'];
        $chatId = $callbackQuery['message']['chat']['id'] ?? null;
        $data = $callbackQuery['data'] ?? '';
        $from = $callbackQuery['from'] ?? [];

        if (!$chatId) return;

        $tgUser = null;
        $tgId = $from['id'] ?? $chatId;
        if (!empty($tgId)) {
            try {
                $tgUser = TelegramUser::firstOrCreate(
                    ['telegram_id' => $tgId],
                    [
                        'username' => $from['username'] ?? null,
                        'first_name' => $from['first_name'] ?? null,
                        'last_name' => $from['last_name'] ?? null,
                        'language_code' => $from['language_code'] ?? 'am',
                        'last_interaction_at' => now(),
                    ]
                );
            } catch (\Throwable $th) {
                Log::warning('Could not get/create telegram user on callback: ' . $th->getMessage());
            }
        }

        // Handle interactive booking wizard callbacks
        if (str_starts_with($data, 'book_')) {
            if ($this->handleInteractiveBookingCallback($tgUser, $data, $chatId)) {
                $this->telegramApi('answerCallbackQuery', ['callback_query_id' => $id]);
                return;
            }
        }

        if ($data === 'menu_services') {
            $this->sendServicesMenu($chatId);
        } elseif ($data === 'menu_book') {
            $this->sendBookingGuide($chatId);
        } elseif ($data === 'menu_contact') {
            $this->sendContactInfo($chatId);
        } elseif ($data === 'menu_status') {
            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => "📋 <b>የትዕዛዝዎን ሁኔታ ለመከታተል:</b>\n\nየትዕዛዝ ቁጥርዎን (ለምሳሌ <code>ORD-2026-0001</code>) ጽፈው ይላኩልን:: የትዕዛዝዎን ዝርዝር እና የቡድኑን ሁኔታ ወዲያውኑ እንልክልዎታለን::",
                'parse_mode' => 'HTML',
            ]);
        } elseif ($data === 'menu_feedback') {
            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => "⭐ <b>የደንበኞች አስተያየት እና ደረጃ:</b>\n\nየተሰጠዎት የፅዳት አገልግሎት እንዴት ነበር? አስተያየትዎን ወይም ደረጃዎን ከ 1 እስከ 5 ኮከብ በ 0970075509 ይንገሩን:: የእርስዎ እርካታ የኩባንያችን ዋና አላማ ነው!",
                'parse_mode' => 'HTML',
            ]);
        } elseif ($data === 'menu_complaint') {
            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => "⚠ <b>ቅሬታ ማሳወቂያ እና አስቸኳይ ክትትል:</b>\n\nበተሰጠው አገልግሎት ላይ ያልተሟላ ነገር ካለ ወይም ቅሬታ ካለዎት ወዲያውኑ በ 0970075509 ይደውሉልን:: የቡድን መሪያችን በ 24 ሰዓት ውስጥ መጥቶ ያለምንም ተጨማሪ ክፍያ ዳግም እንዲፀዳ ይደረጋል!",
                'parse_mode' => 'HTML',
            ]);
        } elseif ($data === 'menu_start') {
            $this->handleMessage(['chat' => ['id' => $chatId], 'text' => '/start']);
        }

        $this->telegramApi('answerCallbackQuery', [
            'callback_query_id' => $id,
        ]);
    }

    private function sendBookingGuide(int|string $chatId, ?TelegramUser $tgUser = null): void
    {
        $httpsUrl = $this->getMiniAppHttpsUrl();
        $buttons = [
            [
                ['text' => '⚡ በቦቱ ቀጠሮ ያስይዙ (1 ደቂቃ)', 'callback_data' => 'book_start_wizard'],
            ],
        ];

        if ($httpsUrl) {
            $buttons[] = [['text' => '📱 በቴሌግራም ሚኒ አፕ ክፈት (Mini App)', 'web_app' => ['url' => $httpsUrl]]];
        }

        $buttons[] = [
            ['text' => '📞 በስልክ ለማዘዝ (0970075509)', 'callback_data' => 'menu_contact'],
            ['text' => '🛋 አገልግሎቶች ዝርዝር', 'callback_data' => 'menu_services'],
        ];

        $msg = "📅 <b>ቀጠሮ ለመያዝ | Appointment Booking</b>\n\n" .
            "በሜሽ ክሊኒንግ 3 ምቹ የቀጠሮ ማስያዣ መንገዶች አሉ:\n\n" .
            "1️⃣ ⚡ <b>በቦቱ ደረጃ-በደረጃ ቀጠሮ ማስያዝ (Interactive Wizard):</b>\n" .
            "   አገልግሎት፣ ስም፣ ስልክና የቀጥታ <b>GPS ካርታ (Live Map Pin)</b> በመላክ በ 1 ደቂቃ ውስጥ ይመዝገቡ!\n\n" .
            "2️⃣ 📱 <b>በቴሌግራም ሚኒ አፕ (Telegram Mini App):</b>\n" .
            "   ቀጥታ በስማርት ፎርም እቃዎችን መርጠው ዋጋውን እያዩ ማዘዝ ይችላሉ::\n\n" .
            "3️⃣ 📞 <b>በስልክ በቀጥታ ለማዘዝ:</b>\n" .
            "   • 0970075509 ወይም 0970075509\n\n" .
            "አሁኑኑ ለመጀመር ከታች ያለውን <b>'⚡ በቦቱ ቀጠሮ ያስይዙ'</b> የሚለውን ቁልፍ ይጫኑ 👇";

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $buttons]),
        ]);
    }

    private function sendServicesMenu(int|string $chatId): void
    {
        $dbServices = Service::where('is_active', true)->orderBy('sort_order')->get();
        if ($dbServices->isNotEmpty()) {
            $msg = "🧹 <b>የሜሽ የፅዳት አገልግሎቶች እና ወቅታዊ የዋጋ ዝርዝር</b>\n" .
                "<i>(Meash Cleaning Official Services & Real-time Rates)</i>\n\n";
            $idx = 1;
            foreach ($dbServices as $svc) {
                $name = $svc->name_am ?: $svc->name_en;
                $price = number_format((float)$svc->base_price);
                $unit = $svc->pricing_unit_am ?: ($svc->pricing_unit ?: 'አገልግሎት');
                $desc = $svc->description_am ? "   • {$svc->description_am}\n" : '';
                $msg .= "{$idx}. <b>{$name}</b>\n   • ዋጋ: <b>{$price} ብር</b> / {$unit}\n{$desc}\n";
                $idx++;
            }
            $msg .= "ቦታ ለማስያዝ ወይም ለመደወል ከታች ያለውን ቁልፍ ይጫኑ 👇";
        } else {
            $msg = "🧹 <b>የሜሽ የፅዳት አገልግሎቶች እና ዋጋ ዝርዝር</b>\n\n" .
                "1. 🛋 <b>የሶፋ እጥበት:</b> 350 ብር / ወንበር\n" .
                "2. 🧶 <b>የምንጣፍ እጥበት:</b> 80 ብር / ካሬ\n" .
                "3. 🛏 <b>የፍራሽ ሳኒታይዜሽን:</b> 600 ብር / ፍራሽ\n" .
                "4. 🪟 <b>የመስታወት ፅዳት:</b> 70 ብር / ካሬ\n" .
                "5. 🏠 <b>የቤት ጥልቅ ፅዳት:</b> ከ 2,500 ብር ጀምሮ\n\n" .
                "ቀጠሮ ለመያዝ ከታች ያለውን ቁልፍ ይጫኑ 👇";
        }

        $buttons = [
            [['text' => '📅 አሁኑኑ ቦታ ያስይዙ | Book Now', 'callback_data' => 'menu_book']],
            [['text' => '📞 በስልክ ለማዘዝ | Call Reception', 'callback_data' => 'menu_contact']],
        ];

        $httpsUrl = $this->getMiniAppHttpsUrl();
        if ($httpsUrl) {
            $buttons[0] = [['text' => '📅 አሁኑኑ ቦታ ያስይዙ | Book Now', 'web_app' => ['url' => $httpsUrl]]];
        }

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $buttons]),
        ]);
    }

    private function sendContactInfo(int|string $chatId): void
    {
        $msg = "📞 <b>የሜሽ የሪሴፕሽን ስልክ አድራሻ</b>\n" .
            "<i>(Customer Reception & Support Desk)</i>\n\n" .
            "📱 <b>ዋና ስልክ:</b> 0970075509\n" .
            "📱 <b>ተጨማሪ ስልክ:</b> 0970075509\n" .
            "🕒 <b>የስራ ሰዓት:</b>ከሰኞ እስከ ቅዳሜ ከጠዋቱ 2:00 እስከ ምሽቱ 12:00\n" .
            "📍 <b>አድራሻ:</b> አዲስ አበባ፣ ሃያት አደባባይ ተስፉ ሞል ፊትለፊት\n\n" .
            "🤖 <b>ኦፊሴላዊ ቦት:</b> @meashdeepcleaning_solution_bot\n" .
            "ጥያቄ፣ አስተያየት ወይም የቀጠሮ ማስተካከያ ካለዎት በማንኛውም ጊዜ ይደውሉልን!";

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '📅 ቦታ ያስይዙ | Book Now', 'callback_data' => 'menu_book']],
                    [
                        ['text' => '📢 ቴሌግራም ቻናል', 'url' => 'https://t.me/meashdeepcleaning'],
                        ['text' => '📱 TikTok', 'url' => 'https://tiktok.com/@meashdeepcleaning'],
                    ]
                ]
            ]),
        ]);
    }

    private function checkOrderStatus(int|string $chatId, string $orderCode): void
    {
        if (empty($orderCode)) {
            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => "እባክዎን የትዕዛዝ ቁጥርዎን አያይዘው ይላኩ (ለምሳሌ <code>/status ORD-2026-0001</code>)::",
                'parse_mode' => 'HTML',
            ]);
            return;
        }

        $order = Order::with(['items', 'customer', 'assignedTeam'])->where('order_number', $orderCode)->first();
        if (!$order) {
            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => "❌ ይቅርታ፣ በትዕዛዝ ቁጥር <code>{$orderCode}</code> የተመዘገበ መረጃ አልተገኘም:: እባክዎን ቁጥሩን አስተካክለው ይሞክሩ ወይም በ 0970075509 ይደውሉ::",
                'parse_mode' => 'HTML',
            ]);
            return;
        }

        $statusAm = match ($order->order_status) {
            'new' => 'አዲስ ተመዝግቧል (New)',
            'confirmed' => 'ተረጋግጧል (Confirmed)',
            'in_progress' => 'በስራ ላይ (In Progress)',
            'completed' => 'ተጠናቋል (Completed)',
            'cancelled' => 'ተሰርዟል (Cancelled)',
            default => $order->order_status,
        };

        $teamName = $order->assignedTeam ? $order->assignedTeam->name : 'በመመደብ ላይ';

        $msg = "📋 <b>የትዕዛዝ መረጃ - {$order->order_number}</b>\n\n" .
            "👤 <b>ደንበኛ:</b> {$order->customer->full_name}\n" .
            "📍 <b>አድራሻ:</b> {$order->address} ({$order->subcity})\n" .
            "📅 <b>የቀጠሮ ቀን:</b> {$order->appointment_date->format('Y-m-d')} ({$order->appointment_time_slot})\n" .
            "🔄 <b>የትዕዛዝ ሁኔታ:</b> <b>{$statusAm}</b>\n" .
            "🚐 <b>የተመደበው ቡድን:</b> {$teamName}\n" .
            "💰 <b>ጠቅላላ ዋጋ:</b> " . number_format($order->total_amount, 2) . " ብር\n\n" .
            "ማንኛውም ጥያቄ ካለዎት በ 0970075509 ይደውሉልን::";

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
        ]);
    }

    private function sendHelpMessage(int|string $chatId): void
    {
        $msg = "ℹ <b>የሜሽ ቴሌግራም ቦት መመሪያ</b>\n\n" .
            "የሚከተሉትን ትዕዛዞች መጠቀም ይችላሉ:\n" .
            "• <code>/start</code> - ዋናውን ማውጫ ይከፍታል\n" .
            "• <code>/services</code> - የአገልግሎቶች እና የዋጋ ዝርዝር\n" .
            "• <code>/book</code> - በ 1 ደቂቃ ውስጥ ቀጠሮ ማስያዣ\n" .
            "• <code>/status ORD-...</code> - የትዕዛዝ ሁኔታ መከታተያ\n" .
            "• <code>/contact</code> - የሪሴፕሽን ስልክ አድራሻ (0970075509)\n" .
            "• <code>/help</code> - ይህንን መመሪያ ያሳያል\n\n" .
            "እንዲሁም በማንኛውም የቴሌግራም ቻት ውስጥ <code>@meashdeepcleaning_solution_bot</code> ብለው በመጻፍ አገልግሎቶችን ለወዳጅዎ ማጋራት ይችላሉ!";

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [['text' => '🚀 ዋና ማውጫ | Main Menu', 'callback_data' => 'menu_start']],
                    [['text' => '📅 ቦታ ያስይዙ | Book Now', 'callback_data' => 'menu_book']],
                ]
            ]),
        ]);
    }

    /**
     * Mini App Booking API endpoint
     */
    public function miniAppBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:32',
            'address' => 'required|string',
            'subcity' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'appointment_date' => 'required|date',
            'appointment_time_slot' => 'nullable|string',
            'subscription_plan' => 'nullable|string|in:one_time,weekly,biweekly,monthly',
            'telegram_user_id' => 'nullable|string',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'nullable|exists:services,id',
            'items.*.item_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $plan = $validated['subscription_plan'] ?? 'one_time';
        $discRates = ['weekly' => 15.0, 'biweekly' => 10.0, 'monthly' => 5.0, 'one_time' => 0.0];
        $discPercent = $discRates[$plan] ?? 0.0;

        // Find or create customer
        $customer = Customer::where('phone', $validated['customer_phone'])->first();
        if (!$customer) {
            $customer = Customer::create([
                'customer_code' => Customer::generateNextCode(),
                'full_name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'],
                'address' => $validated['address'],
                'subcity' => $validated['subcity'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'telegram_user_id' => $validated['telegram_user_id'] ?? null,
                'marketing_consent' => true,
                'consent_timestamp' => now(),
            ]);
        }

        $orderNumber = Order::generateNextNumber();
        $order = Order::create([
            'order_number' => $orderNumber,
            'customer_id' => $customer->id,
            'source' => 'telegram_miniapp',
            'subscription_plan' => $plan,
            'order_status' => 'new',
            'payment_status' => 'unpaid',
            'subtotal' => 0,
            'discount' => 0,
            'subscription_discount' => 0,
            'tax' => 0,
            'total' => 0,
            'appointment_date' => $validated['appointment_date'],
            'appointment_time_slot' => $validated['appointment_time_slot'] ?? 'morning',
            'address' => $validated['address'],
            'subcity' => $validated['subcity'] ?? $customer->subcity,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'notes' => $validated['notes'] ?? 'Booked via Telegram Mini App',
            'version' => 1,
        ]);

        $subtotalCalc = 0;
        $itemsSummary = [];
        foreach ($validated['items'] as $it) {
            $sub = (float)$it['quantity'] * (float)$it['unit_price'];
            $subtotalCalc += $sub;
            $itemsSummary[] = "{$it['item_name']} (x{$it['quantity']})";
            OrderItem::create([
                'order_id' => $order->id,
                'service_id' => $it['service_id'] ?? null,
                'item_name' => $it['item_name'],
                'quantity' => $it['quantity'],
                'unit_price' => $it['unit_price'],
                'subtotal' => $sub,
            ]);
        }

        // Update order subtotal and total
        $order->subtotal = $subtotalCalc;
        $order->total = $subtotalCalc;

        // Apply recurring subscription discount if chosen
        if ($discPercent > 0) {
            $subDiscount = ($subtotalCalc * $discPercent) / 100.0;
            $order->subscription_discount = $subDiscount;
            $order->discount = (float)$order->discount + $subDiscount;
            $order->total = max(0, $subtotalCalc - (float)$order->discount);

            $startDate = Carbon::parse($order->appointment_date);
            $nextDate = match ($plan) {
                'weekly' => $startDate->copy()->addWeek(),
                'biweekly' => $startDate->copy()->addWeeks(2),
                'monthly' => $startDate->copy()->addMonth(),
                default => null,
            };

            Subscription::create([
                'customer_id' => $customer->id,
                'plan_type' => $plan,
                'discount_percent' => $discPercent,
                'service_summary' => implode(', ', $itemsSummary),
                'base_price' => $subtotalCalc,
                'discounted_price' => max(0, $subtotalCalc - $subDiscount),
                'preferred_day' => $startDate->format('l'),
                'preferred_time_slot' => $order->appointment_time_slot,
                'start_date' => $startDate,
                'next_service_date' => $nextDate,
                'status' => 'active',
                'notes' => "Auto-created from Telegram Mini App booking #{$orderNumber}",
            ]);
        }
        $order->save();

        // Send instant notification in Amharic to customer if booked through Telegram
        if (!empty($validated['telegram_user_id'])) {
            $totalEst = (float)$order->fresh()->total;
            $planTxt = $plan !== 'one_time' ? "\n🔄 <b>የተመረጠ እቅድ:</b> {$plan} ({$discPercent}% ቅናሽ)" : "";
            $this->telegramApi('sendMessage', [
                'chat_id' => $validated['telegram_user_id'],
                'text' => "🎉 <b>እንኳን ደስ አላችሁ! የቀጠሮ ጥያቄዎ በተሳካ ሁኔታ ተመዝግቧል!</b>\n\n" .
                    "📋 <b>የትዕዛዝ ቁጥር:</b> <code>{$order->order_number}</code>\n" .
                    "📅 <b>የቀጠሮ ቀን:</b> {$order->appointment_date->format('Y-m-d')} ({$order->appointment_time_slot}){$planTxt}\n" .
                    "📍 <b>አድራሻ:</b> {$order->address}\n" .
                    "💰 <b>የተገመተ ጠቅላላ ዋጋ:</b> " . number_format($totalEst, 2) . " ብር\n\n" .
                    "የሜሽ ሪሴፕሽን ቡድን በቅርቡ በስልክ (0970075509) ደውሎ ያረጋግጥልዎታል:: ስለመረጡን እናመሰግናለን! 🙏",
                'parse_mode' => 'HTML',
            ]);
        }

        // Send instant SMS confirmation to customer's mobile phone
        try {
            SmsService::sendBookingConfirmation($order);
        } catch (\Throwable $th) {
            Log::warning('MiniApp SMS confirmation failed: ' . $th->getMessage());
        }

        return response()->json([
            'message' => 'ቀጠሮዎ በተሳካ ሁኔታ ተመዝግቧል! የሪሴፕሽን ሰራተኞቻችን በቅርቡ ያረጋግጡልዎታል::',
            'booking_id' => $order->order_number,
            'order' => $order->fresh(['items', 'customer']),
        ], 201);
    }

    private function telegramApi(string $method, array $params = []): array
    {
        if (empty($this->botToken)) {
            Log::info("Telegram API call simulated [{$method}]: " . json_encode($params));
            return ['ok' => true, 'simulated' => true];
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/{$method}";
        try {
            $response = Http::timeout(15)->post($url, $params);
            $data = $response->json();
            if (!($data['ok'] ?? false)) {
                Log::warning("Telegram API non-ok [{$method}]: " . $response->body());
            }
            return $data ?? [];
        } catch (\Exception $e) {
            Log::error("Telegram API exception [{$method}]: " . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * ========================================================
     * INTERACTIVE TELEGRAM BOOKING ENGINE WITH LIVE MAP / GPS
     * ========================================================
     */

    public function startInteractiveBooking(?TelegramUser $tgUser, int|string $chatId): void
    {
        if (!$tgUser) {
            try {
                $tgUser = TelegramUser::firstOrCreate(
                    ['telegram_id' => $chatId],
                    ['bot_state' => 'booking_service', 'payload_cache' => []]
                );
            } catch (\Throwable $e) {
                Log::warning("Could not auto-create tgUser: " . $e->getMessage());
            }
        }

        if ($tgUser) {
            $tgUser->bot_state = 'booking_service';
            $tgUser->payload_cache = [];
            $tgUser->save();
        }

        $msg = "✨ <b>ደረጃ 1/5፡ የሚፈልጉትን የፅዳት አገልግሎት ይምረጡ</b>\n" .
            "<i>(Step 1/5: Choose Cleaning Service)</i>\n\n" .
            "እባክዎን ከታች ካሉት አገልግሎቶች አንዱን ይጫኑ:";

        $dbServices = Service::where('is_active', true)->orderBy('sort_order')->get();
        $buttons = [];
        if ($dbServices->isNotEmpty()) {
            foreach ($dbServices as $s) {
                $icon = $s->icon ?: '🧹';
                $name = $s->name_am ?: $s->name_en;
                $price = number_format((float)$s->base_price);
                $unit = $s->unit ?: 'ስራ';
                $buttons[] = [
                    ['text' => "{$icon} {$name} ({$price} ብር/{$unit})", 'callback_data' => 'book_svc:' . $s->code],
                ];
            }
        } else {
            $buttons = [
                [['text' => '🛋 የሶፋ ጥልቅ ፅዳት (350 ብር/ወንበር)', 'callback_data' => 'book_svc:sofa']],
                [['text' => '🧶 የምንጣፍ እጥበት (80 ብር/ካሬ)', 'callback_data' => 'book_svc:carpet']],
                [['text' => '🛏 የፍራሽ ሳኒታይዜሽን (600 ብር/ፍራሽ)', 'callback_data' => 'book_svc:mattress']],
                [['text' => '🪟 የመስታወት እና ህንፃ ፅዳት (70 ብር/ካሬ)', 'callback_data' => 'book_svc:glass']],
                [['text' => '🏠 የመኖሪያ ቤት ሙሉ ጥልቅ ፅዳት (ከ 2,500 ብር)', 'callback_data' => 'book_svc:home']],
                [['text' => '🏢 የቢሮ እና ተቋማት ፅዳት', 'callback_data' => 'book_svc:office']],
            ];
        }
        $buttons[] = [
            ['text' => '❌ ሰርዝ | Cancel', 'callback_data' => 'book_cancel'],
        ];

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $buttons]),
        ]);
    }

    public function handleInteractiveBookingCallback(?TelegramUser $tgUser, string $data, int|string $chatId): bool
    {
        if ($data === 'book_start_wizard') {
            $this->startInteractiveBooking($tgUser, $chatId);
            return true;
        }

        if ($data === 'book_cancel') {
            if ($tgUser) {
                $tgUser->bot_state = null;
                $tgUser->payload_cache = null;
                $tgUser->save();
            }
            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => "❌ <b>የቀጠሮ ምዝገባው ተሰርዟል::</b>\n\nእንደገና ለመጀመር /book ወይም /start ብለው መጀመር ይችላሉ::",
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['remove_keyboard' => true]),
            ]);
            return true;
        }

        // STEP 1 -> STEP 2: Service chosen
        if (str_starts_with($data, 'book_svc:')) {
            $svcCode = substr($data, 9);
            $service = Service::where('code', $svcCode)->first();
            $serviceNameAm = $service ? $service->name_am : 'የፅዳት አገልግሎት';
            $unitPrice = $service ? (float)$service->base_price : 350;

            $payload = $tgUser ? ($tgUser->payload_cache ?? []) : [];
            $payload['service_code'] = $svcCode;
            $payload['service_name'] = $serviceNameAm;
            $payload['unit_price'] = $unitPrice;
            $payload['quantity'] = match($svcCode) {
                'sofa' => 5,
                'carpet' => 20,
                'mattress' => 1,
                'glass' => 30,
                default => 1,
            };

            if ($tgUser) {
                $tgUser->bot_state = 'booking_name';
                $tgUser->payload_cache = $payload;
                $tgUser->save();
            }

            $tgName = trim(($tgUser?->first_name ?? '') . ' ' . ($tgUser?->last_name ?? ''));
            $namePrompt = "👤 <b>ደረጃ 2/5፡ ሙሉ ስምዎን ያስገቡ:</b>\n" .
                "<i>(Step 2/5: Enter Your Full Name)</i>\n\n" .
                "የተመረጠ አገልግሎት: <b>{$serviceNameAm}</b>\n\n" .
                ($tgName ? "ከታች ያለውን ቁልፍ በመጫን የቴሌግራም ስምዎን መጠቀም ወይም ስምዎን መጻፍ ይችላሉ:" : "እባክዎን ሙሉ ስምዎን ጽፈው ይላኩልን:");

            $buttons = [];
            if ($tgName) {
                $buttons[] = [['text' => "✍ በቴሌግራም ስሜ ቀጥል: {$tgName}", 'callback_data' => 'book_use_tg_name']];
            }
            $buttons[] = [['text' => '❌ ሰርዝ | Cancel', 'callback_data' => 'book_cancel']];

            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => $namePrompt,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode(['inline_keyboard' => $buttons]),
            ]);
            return true;
        }

        // Use Telegram account name
        if ($data === 'book_use_tg_name') {
            $tgName = trim(($tgUser?->first_name ?? '') . ' ' . ($tgUser?->last_name ?? '')) ?: 'ደንበኛ';
            $payload = $tgUser ? ($tgUser->payload_cache ?? []) : [];
            $payload['customer_name'] = $tgName;

            if ($tgUser) {
                $tgUser->bot_state = 'booking_phone';
                $tgUser->payload_cache = $payload;
                $tgUser->save();
            }

            $this->promptForPhone($chatId, $tgName);
            return true;
        }

        // Subcity chosen via button (for users not at home or picking location)
        if (str_starts_with($data, 'book_subcity:')) {
            $subcity = substr($data, 13);
            $payload = $tgUser ? ($tgUser->payload_cache ?? []) : [];
            $payload['subcity'] = $subcity;
            unset($payload['latitude'], $payload['longitude']);

            if ($tgUser) {
                $tgUser->bot_state = 'booking_detail_address';
                $tgUser->payload_cache = $payload;
                $tgUser->save();
            }

            $msg = "🏢 <b>የተመረጠ ክፍለ ከተማ፡ {$subcity}</b>\n\n" .
                "እባክዎን የሰፈር ስም፣ ልዩ መለያ ቦታ ወይም የቤት ቁጥር ጽፈው ይላኩልን:\n" .
                "<i>(ምሳሌ፡ ቦሌ ሚካኤል፣ ታክሲ ተራ አካባቢ ወይም ህንፃ ቁጥር)</i>\n\n" .
                "👉 ተጨማሪ ዝርዝር መጻፍ ካልፈለጉ ከስር ያለውን <b>'⏩ እንደዚሁ ይለፍ'</b> የሚለውን ቁልፍ ይጫኑ:";

            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => $msg,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [['text' => '⏩ እንደዚሁ ይለፍ (Skip Detail)', 'callback_data' => 'book_skip_detail_address']],
                        [['text' => '❌ ሰርዝ | Cancel', 'callback_data' => 'book_cancel']],
                    ]
                ]),
            ]);
            return true;
        }

        // Skip detail address after subcity selection
        if ($data === 'book_skip_detail_address') {
            $payload = $tgUser ? ($tgUser->payload_cache ?? []) : [];
            $subcity = $payload['subcity'] ?? 'አዲስ አበባ';
            $payload['address'] = "{$subcity} ክፍለ ከተማ";
            unset($payload['latitude'], $payload['longitude']);

            if ($tgUser) {
                $tgUser->bot_state = 'booking_date';
                $tgUser->payload_cache = $payload;
                $tgUser->save();
            }

            $this->promptForDate($chatId, $payload);
            return true;
        }

        // STEP 4 -> STEP 5: Date chosen
        if (str_starts_with($data, 'book_date:')) {
            $val = substr($data, 10);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                $apptDate = $val;
            } else {
                $apptDate = match($val) {
                    'today' => now()->format('Y-m-d'),
                    'tomorrow' => now()->addDay()->format('Y-m-d'),
                    'dayafter' => now()->addDays(2)->format('Y-m-d'),
                    'monday' => now()->next(\Carbon\Carbon::MONDAY)->format('Y-m-d'),
                    'saturday' => now()->next(\Carbon\Carbon::SATURDAY)->format('Y-m-d'),
                    'sunday' => now()->next(\Carbon\Carbon::SUNDAY)->format('Y-m-d'),
                    default => now()->addDay()->format('Y-m-d'),
                };
            }

            $payload = $tgUser ? ($tgUser->payload_cache ?? []) : [];
            $payload['appointment_date'] = $apptDate;

            if ($tgUser) {
                $tgUser->bot_state = 'booking_time';
                $tgUser->payload_cache = $payload;
                $tgUser->save();
            }

            $this->promptForTime($chatId, $payload);
            return true;
        }

        // Time slot chosen
        if (str_starts_with($data, 'book_time:')) {
            $slot = substr($data, 10);
            $payload = $tgUser ? ($tgUser->payload_cache ?? []) : [];
            $payload['appointment_time_slot'] = $slot;

            if ($tgUser) {
                $tgUser->bot_state = 'booking_review';
                $tgUser->payload_cache = $payload;
                $tgUser->save();
            }

            $this->showBookingReview($chatId, $payload);
            return true;
        }

        // Final Confirm
        if ($data === 'book_confirm') {
            $this->finalizeInteractiveBooking($tgUser, $chatId);
            return true;
        }

        return false;
    }

    public function handleInteractiveBookingMessage(TelegramUser $tgUser, array $message, int|string $chatId): bool
    {
        $state = $tgUser->bot_state;
        $text = trim($message['text'] ?? '');
        $location = $message['location'] ?? null;
        $contact = $message['contact'] ?? null;
        $payload = $tgUser->payload_cache ?? [];

        switch ($state) {
            case 'booking_name':
                if (empty($text)) {
                    $this->telegramApi('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "እባክዎን ስምዎን በጽሁፍ ይላኩ (ለምሳሌ፡ ተስፋ ካሳ)::",
                    ]);
                    return true;
                }
                $payload['customer_name'] = $text;
                $tgUser->payload_cache = $payload;
                $tgUser->bot_state = 'booking_phone';
                $tgUser->save();
                $this->promptForPhone($chatId, $text);
                return true;

            case 'booking_phone':
                $phone = null;
                if ($contact && !empty($contact['phone_number'])) {
                    $phone = $contact['phone_number'];
                    if (str_starts_with($phone, '251')) {
                        $phone = '0' . substr($phone, 3);
                    } elseif (str_starts_with($phone, '+251')) {
                        $phone = '0' . substr($phone, 4);
                    }
                } elseif (!empty($text)) {
                    if (preg_match('/(09\d{8}|07\d{8}|\+2519\d{8}|\+2517\d{8})/', $text, $m)) {
                        $phone = $m[1];
                        if (str_starts_with($phone, '+251')) {
                            $phone = '0' . substr($phone, 4);
                        }
                    }
                }

                if (!$phone) {
                    $this->telegramApi('sendMessage', [
                        'chat_id' => $chatId,
                        'text' => "❌ <b>እባክዎን ትክክለኛ የኢትዮጵያ ስልክ ቁጥር ያስገቡ:</b>\n\nለምሳሌ: <code>0911223344</code> ወይም <code>0711223344</code>",
                        'parse_mode' => 'HTML',
                    ]);
                    return true;
                }

                $payload['customer_phone'] = $phone;
                $tgUser->payload_cache = $payload;
                $tgUser->bot_state = 'booking_location';
                $tgUser->save();
                $this->promptForLocation($chatId, $payload);
                return true;

            case 'booking_location':
                if ($location) {
                    // LIVE GPS PIN
                    $lat = $location['latitude'];
                    $lng = $location['longitude'];
                    $payload['latitude'] = $lat;
                    $payload['longitude'] = $lng;
                    $payload['address'] = "የቀጥታ GPS ካርታ መገኛ (Live Location Pin)";
                    $tgUser->payload_cache = $payload;
                    $tgUser->bot_state = 'booking_date';
                    $tgUser->save();
                    $this->promptForDate($chatId, $payload);
                    return true;
                } elseif (!empty($text)) {
                    if ($text === '✍ አድራሻዬን እጽፋለሁ (Type Manually)') {
                        $this->telegramApi('sendMessage', [
                            'chat_id' => $chatId,
                            'text' => "✍ <b>እባክዎን አድራሻዎን ጽፈው ይላኩልን:</b>\n\n(ምሳሌ፡ <i>ቦሌ ሚካኤል፣ ታክሲ ተራ አካባቢ፣ የቤት ቁ. 412</i>)",
                            'parse_mode' => 'HTML',
                        ]);
                        return true;
                    }

                    // Manual address typed
                    unset($payload['latitude'], $payload['longitude']);
                    $payload['address'] = $text;
                    $subcity = $this->detectSubcity($text);
                    if ($subcity) {
                        $payload['subcity'] = $subcity;
                    }
                    $tgUser->payload_cache = $payload;
                    $tgUser->bot_state = 'booking_date';
                    $tgUser->save();
                    $this->promptForDate($chatId, $payload);
                    return true;
                }
                return true;

            case 'booking_detail_address':
                if (!empty($text)) {
                    $subcity = $payload['subcity'] ?? 'አዲስ አበባ';
                    $payload['address'] = "{$subcity}፣ {$text}";
                    unset($payload['latitude'], $payload['longitude']);
                    $tgUser->payload_cache = $payload;
                    $tgUser->bot_state = 'booking_date';
                    $tgUser->save();
                    $this->promptForDate($chatId, $payload);
                    return true;
                }
                return true;
        }

        return false;
    }

    private function promptForPhone(int|string $chatId, string $customerName): void
    {
        $msg = "📱 <b>ደረጃ 3/5፡ እባክዎን ስልክ ቁጥርዎን ያስገቡ:</b>\n" .
            "<i>(Step 3/5: Enter Your Phone Number)</i>\n\n" .
            "ስም: <b>{$customerName}</b>\n\n" .
            "ለምሳሌ፡ <code>0912345678</code> ወይም <code>0712345678</code> ጽፈው ይላኩ\n\n" .
            "ወይም ከስር ያለውን <b>'📱 ስልክ ቁጥሬን አጋራ'</b> የሚለውን ቁልፍ ይጫኑ:";

        $keyboard = [
            'keyboard' => [
                [
                    ['text' => '📱 ስልክ ቁጥሬን በቴሌግራም አጋራ (Share Phone)', 'request_contact' => true],
                ],
                [
                    ['text' => '❌ ሰርዝ (Cancel)'],
                ],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($keyboard),
        ]);
    }

    private function promptForLocation(int|string $chatId, array $payload): void
    {
        $name = $payload['customer_name'] ?? 'ደንበኛ';
        $phone = $payload['customer_phone'] ?? '';

        $msg = "📍 <b>ደረጃ 4/5፡ አድራሻ እና የካርታ መገኛ (Location & Map):</b>\n" .
            "<i>(Step 4/5: Send Live GPS Pin or Type Address)</i>\n\n" .
            "👤 ደንበኛ: <b>{$name}</b> (📱 {$phone})\n\n" .
            "ፅዳቱ የሚከናወንበትን ቦታ ይምረጡ:\n\n" .
            "1️⃣ <b>አሁን በቤትዎ ከሆኑ (ወይም ፅዳቱ ባለበት ቦታ):</b>\n" .
            "ከስር ያለውን <b>'📍 የቀጥታ ካርታ / GPS ላክ'</b> የሚለውን ቁልፍ ይጫኑ:: የቀጥታ GPS መገኛዎ ለፅዳት ቡድናችን ይደርሳል!\n\n" .
            "2️⃣ <b>አሁን በቤትዎ ካልሆኑ (ወይም ፅዳቱ የሚደረግበት ቦታ ሌላ ከሆነ):</b>\n" .
            "ከታች ካሉት ክፍለ ከተሞች አንዱን ይምረጡ ወይም አድራሻዎን ጽፈው ይላኩልን (ለምሳሌ፡ <i>ለሚኩራ ጎሮ 412</i> ወይም <i>ቦሌ ሚካኤል</i>)::";

        $replyKeyboard = [
            'keyboard' => [
                [
                    ['text' => '📍 አሁን ያለሁበትን የቀጥታ ካርታ (GPS) ላክ', 'request_location' => true],
                ],
                [
                    ['text' => '✍ አድራሻዬን እጽፋለሁ (Type Manually)'],
                    ['text' => '❌ ሰርዝ (Cancel)'],
                ],
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => true,
        ];

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($replyKeyboard),
        ]);

        $subcityButtons = [
            'inline_keyboard' => [
                [
                    ['text' => '🏢 ቦሌ (Bole)', 'callback_data' => 'book_subcity:Bole'],
                    ['text' => '🏢 ለሚኩራ (Lemi Kura)', 'callback_data' => 'book_subcity:Lemi Kura'],
                ],
                [
                    ['text' => '🏢 የካ (Yeka)', 'callback_data' => 'book_subcity:Yeka'],
                    ['text' => '🏢 ቂርቆስ (Kirkos)', 'callback_data' => 'book_subcity:Kirkos'],
                ],
                [
                    ['text' => '🏢 ንፋስ ስልክ (Nifas)', 'callback_data' => 'book_subcity:Nifas Silk'],
                    ['text' => '🏢 ኮልፌ (Kolfe)', 'callback_data' => 'book_subcity:Kolfe'],
                ],
                [
                    ['text' => '🏢 አራዳ (Arada)', 'callback_data' => 'book_subcity:Arada'],
                    ['text' => '🏢 ጉለሌ (Gulele)', 'callback_data' => 'book_subcity:Gulele'],
                ],
                [
                    ['text' => '🏢 አቃቂ (Akaki)', 'callback_data' => 'book_subcity:Akaki'],
                    ['text' => '🏢 ልደታ (Lideta)', 'callback_data' => 'book_subcity:Lideta'],
                ],
            ],
        ];

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => "🏢 <b>በቤትዎ ካልሆኑ ክፍለ ከተማዎን ይምረጡ:</b>",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($subcityButtons),
        ]);
    }

    private function promptForDate(int|string $chatId, array $payload): void
    {
        $address = $payload['address'] ?? 'አዲስ አበባ';
        $subcity = $payload['subcity'] ?? '';
        $lat = $payload['latitude'] ?? null;
        $lng = $payload['longitude'] ?? null;

        $locText = $address;
        if ($subcity && !str_contains($address, $subcity)) {
            $locText .= " ({$subcity})";
        }
        if ($lat && $lng) {
            $locText .= " (📍 GPS: <a href=\"https://www.google.com/maps?q={$lat},{$lng}\">ካርታ ክፈት</a>)";
        }

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => "📍 <b>የተመዘገበ አድራሻ:</b> {$locText} ✅",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['remove_keyboard' => true]),
        ]);

        $dayNames = [
            0 => ['am' => 'እሑድ', 'en' => 'Sun'],
            1 => ['am' => 'ሰኞ', 'en' => 'Mon'],
            2 => ['am' => 'ማክሰኞ', 'en' => 'Tue'],
            3 => ['am' => 'ረቡዕ', 'en' => 'Wed'],
            4 => ['am' => 'ሐሙስ', 'en' => 'Thu'],
            5 => ['am' => 'አርብ', 'en' => 'Fri'],
            6 => ['am' => 'ቅዳሜ', 'en' => 'Sat'],
        ];

        $buttons = [];
        // Day 0: ዛሬ (Today)
        $d0 = now();
        $d0Ymd = $d0->format('Y-m-d');
        $d0Am = $dayNames[$d0->dayOfWeek]['am'];
        $d0En = $dayNames[$d0->dayOfWeek]['en'];
        $d0Format = $d0->format('M j');

        // Day 1: ነገ (Tomorrow)
        $d1 = now()->addDay();
        $d1Ymd = $d1->format('Y-m-d');
        $d1Am = $dayNames[$d1->dayOfWeek]['am'];
        $d1En = $dayNames[$d1->dayOfWeek]['en'];
        $d1Format = $d1->format('M j');

        $buttons[] = [
            ['text' => "📅 ዛሬ | Today ({$d0Am} {$d0Format})", 'callback_data' => "book_date:{$d0Ymd}"],
            ['text' => "📅 ነገ | Tomorrow ({$d1Am} {$d1Format})", 'callback_data' => "book_date:{$d1Ymd}"],
        ];

        // Day 2 (Overmorrow) & Day 3
        $d2 = now()->addDays(2);
        $d2Ymd = $d2->format('Y-m-d');
        $d2Am = $dayNames[$d2->dayOfWeek]['am'];
        $d2Format = $d2->format('M j');

        $d3 = now()->addDays(3);
        $d3Ymd = $d3->format('Y-m-d');
        $d3Am = $dayNames[$d3->dayOfWeek]['am'];
        $d3Format = $d3->format('M j');

        $buttons[] = [
            ['text' => "📅 ከነገ ወዲያ ({$d2Am} {$d2Format})", 'callback_data' => "book_date:{$d2Ymd}"],
            ['text' => "📅 {$d3Am} ({$d3->format('l')} {$d3Format})", 'callback_data' => "book_date:{$d3Ymd}"],
        ];

        // Day 4 & Day 5
        $d4 = now()->addDays(4);
        $d4Ymd = $d4->format('Y-m-d');
        $d4Am = $dayNames[$d4->dayOfWeek]['am'];
        $d4Format = $d4->format('M j');

        $d5 = now()->addDays(5);
        $d5Ymd = $d5->format('Y-m-d');
        $d5Am = $dayNames[$d5->dayOfWeek]['am'];
        $d5Format = $d5->format('M j');

        $buttons[] = [
            ['text' => "📅 {$d4Am} ({$d4->format('l')} {$d4Format})", 'callback_data' => "book_date:{$d4Ymd}"],
            ['text' => "📅 {$d5Am} ({$d5->format('l')} {$d5Format})", 'callback_data' => "book_date:{$d5Ymd}"],
        ];

        // Day 6 & Cancel
        $d6 = now()->addDays(6);
        $d6Ymd = $d6->format('Y-m-d');
        $d6Am = $dayNames[$d6->dayOfWeek]['am'];
        $d6Format = $d6->format('M j');

        $buttons[] = [
            ['text' => "📅 {$d6Am} ({$d6->format('l')} {$d6Format})", 'callback_data' => "book_date:{$d6Ymd}"],
            ['text' => '❌ ሰርዝ | Cancel', 'callback_data' => 'book_cancel'],
        ];

        $msg = "📅 <b>ደረጃ 5/5፡ የቀጠሮ ቀን እና ሰዓት ይምረጡ:</b>\n" .
            "<i>(Step 5/5: Choose Appointment Date)</i>\n\n" .
            "ዛሬ <b>{$d0Am} ({$d0->format('l')}, {$d0Format})</b> ነው:: የፅዳት ስራው የሚከናወንበትን ትክክለኛ ቀን ይምረጡ:";

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $buttons]),
        ]);
    }

    private function promptForTime(int|string $chatId, array $payload): void
    {
        $apptDate = $payload['appointment_date'] ?? now()->addDay()->format('Y-m-d');
        $ethDate = EthiopianCalendarService::toEthiopian(\Carbon\Carbon::parse($apptDate));

        $msg = "🕒 <b>የቀጠሮ ሰዓት ይምረጡ:</b>\n" .
            "<i>(Choose Time Slot)</i>\n\n" .
            "ቀን: <b>{$apptDate} ({$ethDate['formatted_am']})</b>\n\n" .
            "የሚመችዎትን ሰዓት ይጫኑ:";

        $timeButtons = [
            'inline_keyboard' => [
                [
                    ['text' => '🌅 ጠዋት (2:00 - 6:00 ሰዓት / Morning)', 'callback_data' => 'book_time:morning'],
                ],
                [
                    ['text' => '🌇 ከሰዓት (7:00 - 11:00 ሰዓት / Afternoon)', 'callback_data' => 'book_time:afternoon'],
                ],
                [
                    ['text' => '⏰ ሙሉ ቀን / በማንኛውም ሰዓት (Flexible)', 'callback_data' => 'book_time:flexible'],
                ],
                [
                    ['text' => '❌ ሰርዝ | Cancel', 'callback_data' => 'book_cancel'],
                ],
            ],
        ];

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($timeButtons),
        ]);
    }

    private function showBookingReview(int|string $chatId, array $payload): void
    {
        $name = $payload['customer_name'] ?? 'ደንበኛ';
        $phone = $payload['customer_phone'] ?? '';
        $svcName = $payload['service_name'] ?? 'የፅዳት አገልግሎት';
        $qty = $payload['quantity'] ?? 1;
        $unitPrice = $payload['unit_price'] ?? 350;
        $estTotal = (float)$qty * (float)$unitPrice;
        $address = $payload['address'] ?? 'አዲስ አበባ';
        $subcity = $payload['subcity'] ?? '';
        $lat = $payload['latitude'] ?? null;
        $lng = $payload['longitude'] ?? null;
        $apptDate = $payload['appointment_date'] ?? now()->addDay()->format('Y-m-d');
        $timeSlot = $payload['appointment_time_slot'] ?? 'morning';

        $timeSlotAm = match($timeSlot) {
            'morning' => 'ጠዋት (ከ 2:00 - 6:00)',
            'afternoon' => 'ከሰዓት (ከ 7:00 - 11:00)',
            default => 'በማንኛውም ሰዓት (ሙሉ ቀን)',
        };

        $ethDate = EthiopianCalendarService::toEthiopian(\Carbon\Carbon::parse($apptDate));

        $locLine = "📍 <b>አድራሻ:</b> {$address}";
        if ($subcity && !str_contains($address, $subcity)) {
            $locLine .= " ({$subcity})";
        }
        if ($lat && $lng) {
            $locLine .= "\n🗺 <b>የቀጥታ GPS ካርታ:</b> ተያይዟል ✅ (<a href=\"https://www.google.com/maps?q={$lat},{$lng}\">Google Maps</a>)";
        } else {
            $locLine .= "\n🗺 <b>ካርታ:</b> አልተመረጠም (የጽሁፍ አድራሻ ብቻ)";
        }

        $msg = "📋 <b>የትዕዛዝዎ ማጠቃለያ (Booking Summary):</b>\n\n" .
            "👤 <b>ደንበኛ:</b> {$name}\n" .
            "📱 <b>ስልክ:</b> {$phone}\n" .
            "🛋 <b>አገልግሎት:</b> {$svcName} ({$qty} መጠን)\n" .
            "{$locLine}\n" .
            "📅 <b>የቀጠሮ ቀን:</b> {$apptDate} ({$ethDate['formatted_am']})\n" .
            "🕒 <b>የቀጠሮ ሰዓት:</b> {$timeSlotAm}\n" .
            "💰 <b>የተገመተ ጠቅላላ ዋጋ:</b> " . number_format($estTotal, 2) . " ብር\n\n" .
            "ትዕዛዙ ትክክል ከሆነ ከታች ያለውን <b>'✅ ትዕዛዙን አረጋግጥ'</b> የሚለውን ቁልፍ ይጫኑ:";

        $buttons = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ ትዕዛዙን አረጋግጥ | Confirm Booking', 'callback_data' => 'book_confirm'],
                ],
                [
                    ['text' => '❌ ሰርዝ | Cancel', 'callback_data' => 'book_cancel'],
                ],
            ],
        ];

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($buttons),
        ]);
    }

    private function finalizeInteractiveBooking(?TelegramUser $tgUser, int|string $chatId): void
    {
        $payload = $tgUser ? ($tgUser->payload_cache ?? []) : [];
        if (empty($payload)) {
            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => "❌ ይቅርታ፣ የቀጠሮ መረጃው አልተገኘም:: እባክዎን /book ብለው እንደገና ይጀምሩ::",
                'parse_mode' => 'HTML',
            ]);
            return;
        }

        $name = $payload['customer_name'] ?? ($tgUser?->first_name ?: 'ደንበኛ');
        $phone = $payload['customer_phone'] ?? '0911000000';
        $svcCode = $payload['service_code'] ?? 'sofa';
        $svcName = $payload['service_name'] ?? 'የሶፋ ጥልቅ እጥበት';
        $qty = (float)($payload['quantity'] ?? 1);
        $unitPrice = (float)($payload['unit_price'] ?? 350);
        $address = $payload['address'] ?? 'አዲስ አበባ';
        $subcity = $payload['subcity'] ?? null;
        $lat = $payload['latitude'] ?? null;
        $lng = $payload['longitude'] ?? null;
        $apptDate = $payload['appointment_date'] ?? now()->addDay()->format('Y-m-d');
        $timeSlot = $payload['appointment_time_slot'] ?? 'morning';

        // 1. Customer
        $customer = Customer::where('phone', $phone)->first();
        if (!$customer) {
            $customer = Customer::create([
                'customer_code' => Customer::generateNextCode(),
                'full_name' => $name,
                'phone' => $phone,
                'address' => $address,
                'subcity' => $subcity,
                'latitude' => $lat,
                'longitude' => $lng,
                'telegram_user_id' => (string)$chatId,
                'marketing_consent' => true,
                'consent_timestamp' => now(),
            ]);
        } else {
            $customer->update([
                'full_name' => $name,
                'address' => $address ?: $customer->address,
                'subcity' => $subcity ?: $customer->subcity,
                'latitude' => $lat ?: $customer->latitude,
                'longitude' => $lng ?: $customer->longitude,
                'telegram_user_id' => (string)$chatId,
            ]);
        }

        // 2. Order
        $orderNumber = Order::generateNextNumber();
        $order = Order::create([
            'order_number' => $orderNumber,
            'customer_id' => $customer->id,
            'source' => 'telegram_bot',
            'order_status' => 'new',
            'payment_status' => 'unpaid',
            'appointment_date' => $apptDate,
            'appointment_time_slot' => $timeSlot,
            'address' => $address,
            'subcity' => $subcity ?? $customer->subcity,
            'latitude' => $lat,
            'longitude' => $lng,
            'notes' => 'Interactive Telegram Bot Booking',
            'version' => 1,
        ]);

        // 3. Order Items
        $service = Service::where('code', $svcCode)->first();
        OrderItem::create([
            'order_id' => $order->id,
            'service_id' => $service?->id,
            'item_name' => $svcName,
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'subtotal' => $qty * $unitPrice,
        ]);

        // Recalculate order totals
        $order->recalculateTotals();

        // 4. Appointment
        Appointment::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'appointment_date' => $order->appointment_date,
            'appointment_time_slot' => $order->appointment_time_slot,
            'status' => 'scheduled',
            'notes' => 'Booked via interactive Telegram Bot wizard',
        ]);

        // 5. Send SMS
        try {
            SmsService::sendBookingConfirmation($order);
        } catch (\Throwable $th) {
            Log::warning('SMS confirmation failed: ' . $th->getMessage());
        }

        // 6. Send Rich Confirmation Card
        $totalEst = (float)$order->total;
        $ethAppt = EthiopianCalendarService::toEthiopian($order->appointment_date);
        $timeSlotAm = match($timeSlot) {
            'morning' => 'ጠዋት (ከ 2:00 - 6:00)',
            'afternoon' => 'ከሰዓት (ከ 7:00 - 11:00)',
            default => 'በማንኛውም ሰዓት',
        };

        $confirmMsg = "🎉 <b>እናመሰግናለን {$name}! የቀጠሮ ጥያቄዎ በተሳካ ሁኔታ ተመዝግቧል!</b>\n\n" .
            "📋 <b>የትዕዛዝ ቁጥር:</b> <code>{$order->order_number}</code>\n" .
            "👤 <b>ደንበኛ:</b> {$name}\n" .
            "📱 <b>ስልክ:</b> {$phone}\n" .
            "🛋 <b>አገልግሎት:</b> {$svcName}\n" .
            "📍 <b>አድራሻ:</b> {$address}" . ($subcity ? " ({$subcity})" : "") . "\n" .
            ($lat && $lng ? "🗺 <b>የቀጥታ GPS ካርታ:</b> ተያይዟል ✅\n" : "") .
            "📅 <b>የቀጠሮ ቀን:</b> {$order->appointment_date->format('Y-m-d')} ({$ethAppt['formatted_am']}) {$timeSlotAm}\n" .
            "💰 <b>የተገመተ ጠቅላላ ዋጋ:</b> " . number_format($totalEst, 2) . " ብር\n\n" .
            "📩 <b>የማረጋገጫ አጭር የጽሁፍ መልዕክት (SMS) ወደ ስልክዎ ({$phone}) ተልኳል::</b>\n" .
            "የሜሽ ሪሴፕሽን ቡድን በቅርቡ በስልክ (0970075509) ደውሎ ያረጋግጥልዎታል:: ስለመረጡን እናመሰግናለን! 🙏";

        $actionButtons = [];
        if ($lat && $lng) {
            $actionButtons[] = [['text' => '🗺 በጉግል ካርታ ይመልከቱ (Google Maps)', 'url' => "https://www.google.com/maps?q={$lat},{$lng}"]];
        }
        $actionButtons[] = [
            ['text' => '📋 የትዕዛዝ ሁኔታ | Status', 'callback_data' => 'menu_status'],
            ['text' => '📞 ሪሴፕሽን (0970075509)', 'callback_data' => 'menu_contact'],
        ];
        $actionButtons[] = [
            ['text' => '🚀 ዋና ማውጫ | Main Menu', 'callback_data' => 'menu_start'],
        ];

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $confirmMsg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $actionButtons]),
        ]);

        // Reset user state
        if ($tgUser) {
            $tgUser->bot_state = null;
            $tgUser->payload_cache = null;
            $tgUser->phone_number = $phone;
            $tgUser->save();
        }
    }

    /**
     * Handles booking submission received directly from Telegram Mini App via Telegram.WebApp.sendData()
     */
    public function handleWebAppData(int|string $chatId, array $from, array $webAppData): void
    {
        $raw = $webAppData['data'] ?? '';
        $data = json_decode($raw, true);

        if (!$data || !is_array($data)) {
            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => "⚠️ <b>የላኩት መረጃ በትክክል አልደረሰም::</b> እባክዎ እንደገና ይሞክሩ::",
                'parse_mode' => 'HTML',
            ]);
            return;
        }

        $customerName = trim($data['customer_name'] ?? (($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: 'ውድ ደንበኛችን');
        $phone = trim($data['customer_phone'] ?? '');
        $subcity = trim($data['subcity'] ?? '');
        $address = trim($data['address'] ?? '');
        $lat = isset($data['latitude']) && is_numeric($data['latitude']) ? (float)$data['latitude'] : null;
        $lng = isset($data['longitude']) && is_numeric($data['longitude']) ? (float)$data['longitude'] : null;
        $apptDate = !empty($data['appointment_date']) ? $data['appointment_date'] : now()->addDay()->toDateString();
        $timeSlot = trim($data['appointment_time_slot'] ?? 'ጠዋት (ከ 3:00 - 6:00)');
        $notes = trim($data['notes'] ?? 'በሜሽ ቴሌግራም ሚኒ አፕ የተመዘገበ');
        $items = $data['items'] ?? [];

        if (empty($phone)) {
            $this->telegramApi('sendMessage', [
                'chat_id' => $chatId,
                'text' => "⚠️ <b>እባክዎ ትክክለኛ ስልክ ቁጥር ያስገቡ::</b>",
                'parse_mode' => 'HTML',
            ]);
            return;
        }

        // Normalize Ethiopian phone number
        if (str_starts_with($phone, '+251')) {
            $phone = '0' . substr($phone, 4);
        } elseif (str_starts_with($phone, '251')) {
            $phone = '0' . substr($phone, 3);
        }

        // 1. Customer
        $customer = Customer::where('phone', $phone)->first();
        if (!$customer) {
            $customer = Customer::create([
                'phone' => $phone,
                'full_name' => $customerName,
                'address' => $address,
                'subcity' => $subcity,
                'latitude' => $lat,
                'longitude' => $lng,
                'telegram_user_id' => (string)$chatId,
            ]);
        } else {
            $customer->update([
                'full_name' => $customerName ?: $customer->full_name,
                'address' => $address ?: $customer->address,
                'subcity' => $subcity ?: $customer->subcity,
                'latitude' => $lat ?: $customer->latitude,
                'longitude' => $lng ?: $customer->longitude,
                'telegram_user_id' => (string)$chatId,
            ]);
        }

        // 2. Order
        $orderNumber = Order::generateNextNumber();
        $order = Order::create([
            'order_number' => $orderNumber,
            'customer_id' => $customer->id,
            'source' => 'telegram_miniapp',
            'order_status' => 'new',
            'payment_status' => 'unpaid',
            'appointment_date' => $apptDate,
            'appointment_time_slot' => $timeSlot,
            'address' => $address,
            'subcity' => $subcity ?: $customer->subcity,
            'latitude' => $lat,
            'longitude' => $lng,
            'notes' => $notes,
            'version' => 1,
        ]);

        // 3. Order Items
        $calculatedTotal = 0;
        if (!empty($items)) {
            foreach ($items as $item) {
                $itemName = $item['item_name'] ?? 'የሶፋ ጥልቅ እጥበት';
                $qty = max(1, (int)($item['quantity'] ?? 1));
                $unitPrice = (float)($item['unit_price'] ?? 350);
                $subtotal = $qty * $unitPrice;
                $calculatedTotal += $subtotal;

                $svc = Service::where('name_am', 'like', "%{$itemName}%")
                    ->orWhere('name_en', 'like', "%{$itemName}%")
                    ->orWhere('code', 'like', "%{$itemName}%")
                    ->first();
                OrderItem::create([
                    'order_id' => $order->id,
                    'service_id' => $svc?->id,
                    'item_name' => $itemName,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }
        } else {
            $calculatedTotal = 1750;
            $svc = Service::where('code', 'sofa')->first() ?: Service::first();
            OrderItem::create([
                'order_id' => $order->id,
                'service_id' => $svc?->id,
                'item_name' => 'የሶፋ ጥልቅ እጥበት',
                'quantity' => 5,
                'unit_price' => 350,
                'subtotal' => 1750,
            ]);
        }

        $order->update([
            'subtotal' => $calculatedTotal,
            'total' => $calculatedTotal,
        ]);

        // 4. Appointment
        Appointment::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'appointment_date' => $order->appointment_date,
            'appointment_time_slot' => $order->appointment_time_slot,
            'status' => 'scheduled',
            'notes' => 'Booked via Meash Telegram Mini App',
        ]);

        // 5. Send SMS
        try {
            SmsService::sendBookingConfirmation($order);
        } catch (\Throwable $th) {
            Log::warning('SMS confirmation failed: ' . $th->getMessage());
        }

        // 6. Send Rich Confirmation Card
        $totalEst = $calculatedTotal;
        $ethAppt = EthiopianCalendarService::toEthiopian($order->appointment_date);

        $confirmMsg = "🎉 <b>እናመሰግናለን {$customerName}! የቀጠሮ ጥያቄዎ በሚኒ አፕ በተሳካ ሁኔታ ተመዝግቧል!</b>\n\n" .
            "📋 <b>የትዕዛዝ ቁጥር:</b> <code>{$order->order_number}</code>\n" .
            "👤 <b>ደንበኛ:</b> {$customerName}\n" .
            "📱 <b>ስልክ:</b> {$phone}\n" .
            "📍 <b>አድራሻ:</b> {$address}" . ($subcity ? " ({$subcity})" : "") . "\n" .
            ($lat && $lng ? "🗺 <b>የቀጥታ GPS ካርታ:</b> ተያይዟል ✅\n" : "") .
            "📅 <b>የቀጠሮ ቀን:</b> " . (is_string($order->appointment_date) ? $order->appointment_date : $order->appointment_date->format('Y-m-d')) . " ({$ethAppt['formatted_am']}) {$timeSlot}\n" .
            "💰 <b>የተገመተ ጠቅላላ ዋጋ:</b> " . number_format($totalEst, 2) . " ብር\n\n" .
            "📩 <b>የማረጋገጫ አጭር የጽሁፍ መልዕክት (SMS) ወደ ስልክዎ ({$phone}) ተልኳል::</b>\n" .
            "የሜሽ ሪሴፕሽን ቡድን በቅርቡ በስልክ (0970075509) ደውሎ ያረጋግጥልዎታል:: ስለመረጡን እናመሰግናለን! 🙏";

        $actionButtons = [];
        if ($lat && $lng) {
            $actionButtons[] = [['text' => '🗺 በጉግል ካርታ ይመልከቱ (Google Maps)', 'url' => "https://www.google.com/maps?q={$lat},{$lng}"]];
        }
        $actionButtons[] = [
            ['text' => '📋 የትዕዛዝ ሁኔታ | Status', 'callback_data' => 'menu_status'],
            ['text' => '📞 ሪሴፕሽን (0970075509)', 'callback_data' => 'menu_contact'],
        ];
        $actionButtons[] = [
            ['text' => '🚀 ዋና ማውጫ | Main Menu', 'callback_data' => 'menu_start'],
        ];

        $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $confirmMsg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode(['inline_keyboard' => $actionButtons]),
        ]);

        // Update Telegram user phone
        try {
            $tgUser = TelegramUser::where('telegram_id', $from['id'] ?? $chatId)->first();
            if ($tgUser) {
                $tgUser->phone_number = $phone;
                $tgUser->save();
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Sends customer notification in Telegram when their order is postponed / rescheduled
     */
    public function sendOrderPostponedMessage(Order $order, string $reason, string $newDate, string $newSlot): bool
    {
        $order->loadMissing('customer');
        $chatId = $order->customer?->telegram_user_id;

        if (!$chatId && $order->customer?->phone) {
            $tgUser = TelegramUser::where('phone_number', $order->customer->phone)->first();
            $chatId = $tgUser?->telegram_id;
        }

        if (!$chatId) {
            return false;
        }

        $name = $order->customer?->full_name ?: 'ውድ ደንበኛችን';
        $orderNo = $order->order_number;

        $timeSlotAm = match($newSlot) {
            'morning' => 'ጠዋት (ከ 2:00 - 6:00)',
            'afternoon' => 'ከሰዓት (ከ 7:00 - 11:00)',
            default => $newSlot,
        };

        try {
            $eth = EthiopianCalendarService::toEthiopian(\Carbon\Carbon::parse($newDate));
            $ethDateStr = $eth['formatted_am'];
        } catch (\Throwable $e) {
            $ethDateStr = $newDate;
        }

        $msg = "⚠️ <b>ውድ {$name}፣ የቀጠሮ ማስተላለፍ ማሳወቂያ!</b>\n" .
            "━━━━━━━━━━━━━━━━━━━━\n" .
            "📋 <b>የትዕዛዝ ቁጥር:</b> <code>{$orderNo}</code>\n" .
            "📌 <b>የተላለፈበት ምክንያት:</b> {$reason}\n" .
            "📅 <b>አዲሱ የቀጠሮ ቀን:</b> {$newDate} ({$ethDateStr})\n" .
            "⏰ <b>ሰዓት:</b> {$timeSlotAm}\n" .
            "━━━━━━━━━━━━━━━━━━━━\n" .
            "ለተፈጠረው መስተጓጎል ከልብ ይቅርታ እንጠይቃለን! የሜሽ የፅዳት ቡድን በተጠቀሰው አዲስ ሰዓት የሚገኝ ይሆናል::\n\n" .
            "📞 <b>ለማንኛውም ጥያቄ ወይም ሰዓት ለማስተካከል:</b> 0970075509";

        $resp = $this->telegramApi('sendMessage', [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => '📋 የትዕዛዝ ሁኔታ | Status', 'callback_data' => "track_{$orderNo}"],
                        ['text' => '📞 ሪሴፕሽን (0970075509)', 'callback_data' => 'menu_contact'],
                    ],
                    [
                        ['text' => '🚀 ዋና ማውጫ | Main Menu', 'callback_data' => 'menu_start'],
                    ]
                ]
            ])
        ]);

        return ($resp['ok'] ?? false) === true;
    }
}
