<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\TelegramUser;
use App\Models\WebsiteSetting;
use App\Services\EthiopianCalendarService;
use App\Services\OrderLifecycleService;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TelegramController extends Controller
{
  private string $botToken;
  private string $botUsername;

  public function __construct()
  {
    $dbToken = null;
    try {
      if (class_exists(WebsiteSetting::class)) {
        $dbToken = WebsiteSetting::getVal('telegram_bot_token');
      }
    } catch (\Throwable $e) {
      // In-memory or pre-migration fallback
    }
    $this->botToken = (string)($dbToken ?: (config('services.telegram.bot_token') ?: env('TELEGRAM_BOT_TOKEN')));
    $this->botUsername = (string)(config('services.telegram.bot_username') ?: env('TELEGRAM_BOT_USERNAME', 'meash_cleaning_solution_bot'));
  }

  public function getPhone1(): string
  {
    try {
      return (string)WebsiteSetting::getVal('contact_phone_1', '0970075509');
    } catch (\Throwable $e) {
      return '0970075509';
    }
  }

  public function getPhone2(): string
  {
    try {
      return (string)WebsiteSetting::getVal('contact_phone_2', '0998142190');
    } catch (\Throwable $e) {
      return '0998142190';
    }
  }

  public function getPhonesCombined(): string
  {
    $p1 = $this->getPhone1();
    $p2 = $this->getPhone2();
    return ($p2 && $p2 !== $p1) ? "{$p1} / {$p2}" : $p1;
  }

  public function getReceptionTelegramUrl(): string
  {
    try {
      $val = WebsiteSetting::getVal('telegram_reception_url');
      if ($val) return $val;
      $support = WebsiteSetting::getVal('telegram_support');
      if ($support) {
        $username = ltrim(str_replace('https://t.me/', '', $support), '@');
        return "https://t.me/{$username}";
      }
      $bot = WebsiteSetting::getVal('telegram_bot');
      if ($bot) {
        $username = ltrim(str_replace('https://t.me/', '', $bot), '@');
        return "https://t.me/{$username}";
      }
      return 'https://t.me/meashcleaning';
    } catch (\Throwable $e) {
      return 'https://t.me/meashcleaning';
    }
  }

  public function getTiktokUrl(): string
  {
    try {
      return (string)WebsiteSetting::getVal('tiktok', 'https://www.tiktok.com/@meashcleaningsolution');
    } catch (\Throwable $e) {
      return 'https://www.tiktok.com/@meashcleaningsolution';
    }
  }

  public function getFacebookUrl(): string
  {
    try {
      return (string)WebsiteSetting::getVal('facebook', 'https://facebook.com/meashcleaningsolution');
    } catch (\Throwable $e) {
      return 'https://facebook.com/meashcleaningsolution';
    }
  }

  public function getInstagramUrl(): string
  {
    try {
      return (string)WebsiteSetting::getVal('instagram', 'https://instagram.com/meashcleaningsolution');
    } catch (\Throwable $e) {
      return 'https://instagram.com/meashcleaningsolution';
    }
  }

  public function getTelegramChannelUrl(): string
  {
    try {
      return (string)WebsiteSetting::getVal('telegram_channel', 'https://t.me/meashdeepcleanning');
    } catch (\Throwable $e) {
      return 'https://t.me/meashdeepcleanning';
    }
  }

  public function getTelegramSupportHandle(): string
  {
    try {
      return (string)WebsiteSetting::getVal('telegram_support', '@meashcleaning');
    } catch (\Throwable $e) {
      return '@meashcleaning';
    }
  }

  public function getEmail(): string
  {
    try {
      return (string)WebsiteSetting::getVal('company_email', 'meashcleaningsolution@gmail.com');
    } catch (\Throwable $e) {
      return 'meashcleaningsolution@gmail.com';
    }
  }

  public function getAddress(): string
  {
    try {
      return (string)WebsiteSetting::getVal('office_address_am', 'አያት አደባባይ፣ አዲስ አበባ');
    } catch (\Throwable $e) {
      return 'አያት አደባባይ፣ አዲስ አበባ';
    }
  }

  public function getMiniAppHttpsUrl(): ?string
  {
    $miniAppUrl = config('services.telegram.miniapp_url') ?: env('TELEGRAM_MINIAPP_URL');
    if (!empty($miniAppUrl) && str_starts_with($miniAppUrl, 'https://') && !str_contains($miniAppUrl, 'github.io')) {
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
    return $this->handleWebhook($request);
  }

  public function handleWebhook(Request $request): JsonResponse
  {
    $updateId = $request->input('update_id');

    try {
      $secretToken = config('services.telegram.webhook_secret') ?: env('TELEGRAM_WEBHOOK_SECRET');

      if (!empty($secretToken)) {
        $incomingSecret = (string)$request->header('X-Telegram-Bot-Api-Secret-Token');
        if (empty($incomingSecret) || !hash_equals($secretToken, $incomingSecret)) {
          Log::warning('Telegram webhook request rejected: Secret token missing or mismatch.');
          return response()->json(['error' => 'Unauthorized'], 401);
        }
      }

      // Idempotency: If this update has already been successfully processed, return 200 immediately
      if ($updateId && (Cache::has("tg_update_{$updateId}") || Cache::has("tg_update_done_{$updateId}"))) {
        return response()->json(['ok' => true, 'status' => 'already_processed']);
      }

      // Prevent concurrent processing of the exact same update
      if ($updateId && !Cache::add("tg_update_lock_{$updateId}", true, now()->addMinutes(2))) {
        return response()->json(['ok' => true, 'status' => 'in_progress']);
      }

      $update = $request->all();
      $status = 'ignored';

      // 1. Handle Inline Query (@botUsername <query>)
      if (isset($update['inline_query'])) {
        $this->handleInlineQuery($update['inline_query']);
        $status = 'inline_handled';
      }
      // 2. Handle Callback Query (Button clicks)
      elseif (isset($update['callback_query'])) {
        $this->handleCallbackQuery($update['callback_query']);
        $status = 'callback_handled';
      }
      // 3. Handle Private Message
      elseif (isset($update['message'])) {
        $this->handleMessage($update['message']);
        $status = 'message_handled';
      }

      // Mark update successfully processed only AFTER the business operation completed
      if ($updateId) {
        Cache::put("tg_update_{$updateId}", true, now()->addHours(24));
        Cache::put("tg_update_done_{$updateId}", true, now()->addHours(24));
        Cache::forget("tg_update_lock_{$updateId}");
      }

      return response()->json(['status' => $status]);
    } catch (\Throwable $e) {
      if (!empty($updateId)) {
        Cache::forget("tg_update_{$updateId}");
        Cache::forget("tg_update_done_{$updateId}");
        Cache::forget("tg_update_lock_{$updateId}");
      }
      Log::error('Telegram webhook error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);
      return response()->json([
        'status' => 'error',
        'message' => 'An error occurred while processing the webhook request.',
      ], 500);
    }
  }

  /**
   * Handles Telegram Inline Mode query (@botUsername <query>)
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
      ? ['text' => ' ቦታ ያስይዙ | Book Now (1 min)', 'web_app' => ['url' => $httpsMiniApp]]
      : ['text' => ' ቦታ ያስይዙ | Book Now', 'url' => "https://t.me/{$this->botUsername}?start=book"];

    $servicesMap = Service::where('is_active', true)->get()->keyBy('code');
    $sofaP = number_format((float)($servicesMap->get('sofa')?->base_price ?? 350));
    $carpetP = number_format((float)($servicesMap->get('carpet')?->base_price ?? 80));
    $mattressP = number_format((float)($servicesMap->get('mattress')?->base_price ?? 600));
    $glassP = number_format((float)($servicesMap->get('glass')?->base_price ?? 70));
    $homeP = number_format((float)($servicesMap->get('residential_deep')?->base_price ?? ($servicesMap->get('residential')?->base_price ?? 2500)));
    $ceramicP = number_format((float)($servicesMap->get('floor_ceramic')?->base_price ?? ($servicesMap->get('floor')?->base_price ?? 60)));
    $facilityP = number_format((float)($servicesMap->get('commercial_deep')?->base_price ?? ($servicesMap->get('office')?->base_price ?? 3500)));
    $kitchenP = number_format((float)($servicesMap->get('kitchen_deep')?->base_price ?? 3000));
    $postConstP = number_format((float)($servicesMap->get('post_construction')?->base_price ?? 4500));

    $phone1 = $this->getPhone1();
    $phone2 = $this->getPhone2();
    $combinedPhones = $this->getPhonesCombined();
    $receptionUrl = $this->getReceptionTelegramUrl();
    $tiktokUrl = $this->getTiktokUrl();
    $facebookUrl = $this->getFacebookUrl();
    $instagramUrl = $this->getInstagramUrl();
    $tgChannelUrl = $this->getTelegramChannelUrl();
    $tgSupportHandle = $this->getTelegramSupportHandle();
    $tgSupportUrl = str_starts_with($tgSupportHandle, 'http') ? $tgSupportHandle : 'https://t.me/' . ltrim($tgSupportHandle, '@');
    $tgChannelHandle = str_replace('https://t.me/', '@', $tgChannelUrl);
    $socialHandle = (string)WebsiteSetting::getVal('social_handle', '@meashcleaningsolution');
    $companyEmail = $this->getEmail();
    $officeAddress = $this->getAddress();

    $allCards = [
      // CARD 1: All-in-One Official Showcase (Shown first on empty search)
      [
        'id' => 'meash_all_in_one',
        'title' => 'ሜአሽ የጽዳት አገልግሎት | MEASH CLEANING SOLUTION',
        'description' => "6ቱ አገልግሎቶች | \"Your Environment, Our Responsibility.\" | {$combinedPhones}",
        'thumbnail_url' => 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=240&h=240&fit=crop',
        'message_text' => "<b>ሜአሽ የጽዳት አገልግሎት | MEASH CLEANING SOLUTION</b>\n" .
          "<b>\"Your Environment, Our Responsibility.\"</b>\n" .
          "<i>(Premium Commercial Cleaning Solutions — Hotels | Restaurants | Cafés | Offices)</i>\n\n" .
          "በአዲስ አበባ እና አካባቢው ዓለም አቀፍ ደረጃቸውን በጠበቁ የኢንዱስትሪ ማሽኖች፣ በስቲም (Steam) እና በደህንነታቸው በተረጋገጡ ኬሚካሎች አስተማማኝ የጽዳት አገልግሎት እንሰጣለን::\n\n" .
          "<b>የምንሰጣቸው 6ቱ ዋና ዋና ይፋዊ አገልግሎቶች:</b>\n" .
          "1. <b>የሴራሚክ እና የእንጨት ወለሎች ጥልቅ ጽዳት</b> (Ceramic & Hardwood Floor)\n" .
          "2. <b>የተቋማት አጠቃላይ እና ጥልቅ ጽዳት</b> (Deep & General Facilities Cleaning)\n" .
          "3. <b>የወጥ ቤት እና የምግብ ማስተናገጃ ቦታዎች ጽዳት</b> (Kitchen & Dining Sanitation)\n" .
          "4. <b>ከግንባታ ማጠናቀቅ በኋላ የሚደረግ የርክክብ ጽዳት</b> (Post-Construction Handover)\n" .
          "5. <b>የመስታወት እና የፎቅ ውጫዊ ገጽታ ጽዳት</b> (High-Rise Window & Glass)\n" .
          "6. <b>የሶፋ እና ምንጣፍ ጥልቅ እንክብካቤ</b> (Upholstery & Carpet Deep Care)\n\n" .
          "<b>ማህበራዊ ሚዲያ እና ይፋዊ አድራሻዎች (Social Media):</b>\n" .
          "• TikTok: {$socialHandle}\n" .
          "• Instagram: {$socialHandle}\n" .
          "• Facebook: {$socialHandle}\n" .
          "• ቴሌግራም ቻናል: {$tgChannelHandle}\n" .
          "• ቀጥታ ለማነጋገር (Direct Contact): {$tgSupportHandle}\n\n" .
          "<b>የሪሴፕሽን ስልክ:</b> {$combinedPhones}\n" .
          "<b>ኢሜይል:</b> {$companyEmail}\n" .
          "<b>አድራሻ:</b> {$officeAddress}\n\n" .
          "ቦታ ለማስያዝ ወይም ለመደወል ከታች ያሉትን አማራጮች ይጫኑ",
        'buttons' => [
          [
            $bookingButton,
            ['text' => 'አገልግሎቶች | Services', 'switch_inline_query_current_chat' => 'አገልግሎቶች'],
          ],
          [
            ['text' => 'ቴሌግራም ቻናል', 'url' => $tgChannelUrl],
            ['text' => "ቀጥታ ያናግሩ ({$tgSupportHandle})", 'url' => $tgSupportUrl],
          ],
          [
            ['text' => 'TikTok', 'url' => $tiktokUrl],
            ['text' => 'Instagram', 'url' => $instagramUrl],
          ],
          [
            ['text' => "በስልክ ያግኙን ({$phone1})", 'url' => $receptionUrl],
          ],
        ],
        'keywords' => ['all', 'official', 'about', 'meash', 'ሜአሽ', 'ሜሽ', 'ጽዳት', 'አዲስ አበባ', 'ዋጋ', 'ስልክ', 'ሶሻል', 'social', 'booking'],
      ],

      // CARD 2: Services & Rates Breakdown
      [
        'id' => 'meash_services_rates',
        'title' => '6ቱ ይፋዊ አገልግሎቶች | Core Services & Rates',
        'description' => 'የወለል፣ የተቋማት፣ የወጥ ቤት፣ የርክክብ፣ የመስታወት፣ የሶፋና ምንጣፍ ጥልቅ ጽዳት',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1584820927498-cfe5211fd8bf?w=240&h=240&fit=crop',
        'message_text' => "<b>የሜአሽ የጽዳት አገልግሎቶች (MEASH CLEANING SOLUTION)</b>\n" .
          "<b>\"Your Environment, Our Responsibility.\"</b>\n" .
          "<i>(Official 6 Core Cleaning Services)</i>\n\n" .
          "1. <b>የሴራሚክ እና የእንጨት ወለሎች ጥልቅ ጽዳት</b>\n" .
          "  • Ceramic & Hardwood Floor Cleaning ({$ceramicP} ETB / sqm)\n\n" .
          "2. <b>የተቋማት አጠቃላይ እና ጥልቅ ጽዳት</b>\n" .
          "  • Deep & General Facilities Cleaning ({$facilityP} ETB)\n\n" .
          "3. <b>የወጥ ቤት እና የምግብ ማስተናገጃ ቦታዎች ጽዳት</b>\n" .
          "  • Kitchen & Dining Sanitation ({$kitchenP} ETB)\n\n" .
          "4. <b>ከግንባታ ማጠናቀቅ በኋላ የሚደረግ የርክክብ ጽዳት</b>\n" .
          "  • Post-Construction Handover Cleaning ({$postConstP} ETB)\n\n" .
          "5. <b>የመስታወት እና የፎቅ ውጫዊ ገጽታ ጽዳት</b>\n" .
          "  • High-Rise Window & Glass Cleaning ({$glassP} ETB / sqm)\n\n" .
          "6. <b>የሶፋ እና ምንጣፍ ጥልቅ እንክብካቤ</b>\n" .
          "  • Upholstery & Carpet Deep Care ({$sofaP} ETB / seat)\n\n" .
          "<b>የሪሴፕሽን ስልክ:</b> {$combinedPhones}\n" .
          "<b>አድራሻ:</b> {$officeAddress}",
        'buttons' => [
          [
            $bookingButton,
            ['text' => "ሪሴፕሽን ({$phone1})", 'url' => $receptionUrl],
          ],
          [
            ['text' => 'ቴሌግራም ቻናል', 'url' => $tgChannelUrl],
            ['text' => 'TikTok', 'url' => $tiktokUrl],
          ],
        ],
        'keywords' => ['services', 'pricing', 'rates', 'አገልግሎቶች', 'ዋጋ', 'ዝርዝር', 'ስንት', 'ምን'],
      ],

      // CARD 3: Social Media & Contacts
      [
        'id' => 'meash_social_media',
        'title' => 'ሶሻል ሚዲያ እና አድራሻ | Social Media & Hotline',
        'description' => "TikTok | Instagram | Facebook | ስልክ {$combinedPhones}",
        'thumbnail_url' => 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?w=240&h=240&fit=crop',
        'message_text' => "<b>ሜአሽ የጽዳት አገልግሎት (MEASH CLEANING SOLUTION)</b>\n" .
          "<b>\"Your Environment, Our Responsibility.\"</b>\n\n" .
          "የስራዎቻችንን ቪዲዮዎች፣ ቅናሾችና ጠቃሚ የጽዳት ምክሮችን ለማግኘት ይከተሉን:\n\n" .
          "• <b>TikTok:</b> {$tiktokUrl}\n" .
          "• <b>Instagram:</b> {$instagramUrl}\n" .
          "• <b>Facebook:</b> {$facebookUrl}\n" .
          "• <b>ቴሌግራም ቻናል:</b> {$tgChannelUrl}\n\n" .
          "<b>የሪሴፕሽን ስልክ መስመሮች:</b>\n" .
          "• ዋና ስልክ: <b>{$phone1}</b>\n" .
          ($phone2 ? "• ተጨማሪ ስልክ: <b>{$phone2}</b>\n" : "") .
          "• ኢሜይል: <b>{$companyEmail}</b>\n\n" .
          "<b>የስራ ሰዓት:</b> ከሰኞ እስከ ቅዳሜ ከጠዋቱ 2:00 እስከ ምሽቱ 12:00 (እሁድ እና የሌሊት ፈረቃ በቀጠሮ)\n" .
          "<b>አድራሻ:</b> {$officeAddress}",
        'buttons' => [
          [
            ['text' => 'TikTok', 'url' => $tiktokUrl],
            ['text' => 'Instagram', 'url' => $instagramUrl],
          ],
          [
            ['text' => 'Facebook', 'url' => $facebookUrl],
            ['text' => 'ቴሌግራም ቻናል', 'url' => $tgChannelUrl],
          ],
          [
            ['text' => "ሪሴፕሽን: {$phone1}", 'url' => $receptionUrl],
            $bookingButton,
          ],
        ],
        'keywords' => ['social', 'tiktok', 'telegram', 'facebook', 'instagram', 'contact', 'phone', 'ሶሻል', 'ስልክ', 'አድራሻ', 'ቻናል'],
      ],

      // CARD 4: Instant 1-Minute Booking
      [
        'id' => 'meash_book_now',
        'title' => ' በ 1 ደቂቃ ውስጥ ቦታ ያስይዙ | 1-Minute Booking',
        'description' => 'ሶፋ፣ ምንጣፍ፣ ፍራሽ፣ መስታወትና ቤት ፅዳት ቀጠሮ በቀላሉ ይያዙ',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1506784365847-bbad939e9335?w=240&h=240&fit=crop',
        'message_text' => "<b>ፈጣን የቀጠሮ ማስያዣ | Fast Appointment Booking</b>\n" .
          "<i>ሜአሽ የጽዳት አገልግሎት — MEASH CLEANING SOLUTION</i>\n" .
          "<b>\"Your Environment, Our Responsibility.\"</b>\n\n" .
          "በጣም ቀላል እና ፈጣን! የሚፈልጉትን አገልግሎት፣ ቀን እና አድራሻዎን በማስገባት በ 1 ደቂቃ ውስጥ ቀጠሮ ማስያዝ ይችላሉ::\n\n" .
          "1. የሴራሚክ እና የእንጨት ወለሎች ጥልቅ ጽዳት\n" .
          "2. የተቋማት አጠቃላይ እና ጥልቅ ጽዳት\n" .
          "3. የወጥ ቤት እና የምግብ ማስተናገጃ ቦታዎች ጽዳት\n" .
          "4. ከግንባታ ማጠናቀቅ በኋላ የሚደረግ የርክክብ ጽዳት\n" .
          "5. የመስታወት እና የፎቅ ውጫዊ ገጽታ ጽዳት\n" .
          "6. የሶፋ እና ምንጣፍ ጥልቅ እንክብካቤ\n\n" .
          "ወዲያውኑ ቦታ ለማስያዝ ከታች ያለውን ቁልፍ ይጫኑ ወይም በ {$combinedPhones} ይደውሉ::",
        'buttons' => [
          [
            $bookingButton,
          ],
          [
            ['text' => "በስልክ ለማዘዝ ({$phone1})", 'url' => $receptionUrl],
            ['text' => 'አገልግሎቶች', 'switch_inline_query_current_chat' => 'አገልግሎቶች'],
          ],
        ],
        'keywords' => ['book', 'booking', 'reserve', 'appointment', 'ቦታ', 'ቀጠሮ', 'ማስያዣ'],
      ],

      // CARD 5: Sofa Cleaning (HD Image of Modern Sofa)
      [
        'id' => 'meash_sofa',
        'title' => ' የሶፋ እና ወንበር ጥልቅ እጥበት | Sofa Cleaning',
        'description' => "{$sofaP} ብር/ወንበር:: አቧራ፣ እድፍና መጥፎ ጠረን በስቲም ማስወገድ::",
        'thumbnail_url' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=240&h=240&fit=crop',
        'message_text' => " <b>የሶፋ እና ወንበር ጥልቅ አረፋ እጥበት (SOFA CLEANING)</b>\n" .
          "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
          " <b>የአሰራር ሂደታችን:</b>\n" .
          "1. በሃይል የበረታ የኢንዱስትሪ ቫኪዩም (የውስጥ አቧራ ማውጣት)\n" .
          "2. ለጨርቁና ለቆዳው ተስማሚ ሻምፖ በመርጨት እድፍ ማለስለስ\n" .
          "3. በሮተሪ ብሩሽ በጥንቃቄ ማሸትና እድፍ ማስለቀቅ\n" .
          "4. በባለሁለት ሞተር ማሽን የቆሸሸውን ውሃ ሙሉ በሙሉ መምጠጥ\n\n" .
          " <b>ዋጋ:</b> {$sofaP} ብር / በአንድ መቀመጫ ወንበር\n" .
          " ለቆዳ (Leather)፣ ለቬልቬት፣ ለጨርቅና ለተለያዩ ሶፋዎች ፍቱን::\n\n" .
          " <b>ስልክ:</b> {$phone1}\n" .
          " <b>ቦት:</b> @{$this->botUsername}",
        'buttons' => [
          [
            $bookingButton,
            ['text' => " ሪሴፕሽን / ስልክ ({$phone1})", 'url' => $receptionUrl],
          ],
        ],
        'keywords' => ['sofa', 'couch', 'chair', 'furniture', 'seats', 'ሶፋ', 'ወንበር', 'ሶፋዎች'],
      ],

      // CARD 6: Carpet Washing (HD Image of Modern Clean Carpet)
      [
        'id' => 'meash_carpet',
        'title' => ' የምንጣፍ ማጠብና ስቲም እጥበት | Carpet Washing',
        'description' => "{$carpetP} ብር/ካሬ ሜትር:: በቦታው ወይም ወስደን በሮተሪ ማሽን ማጠብ::",
        'thumbnail_url' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=240&h=240&fit=crop',
        'message_text' => " <b>የምንጣፍ ማጠብ እና ስቲም እጥበት (CARPET WASHING)</b>\n" .
          "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
          " የመኖሪያ ቤት፣ የኤምባሲ እና የሆቴል ምንጣፎችን ወደነበሩበት ውበት እንመልሳለን!\n" .
          "• 99.9% ባክቴሪያ እና አለርጂን በሙቅ ስቲም ያጠፋል\n" .
          "• ደስ የሚል መዓዛ ያላቸው የተፈጥሮ ማጽጃዎች\n" .
          "• በቦታው ላይ የማጠብ ወይም ወስደን አድርቀን የማምጣት አማራጭ\n\n" .
          " <b>ዋጋ:</b> {$carpetP} ብር / በአንድ ካሬ ሜትር\n\n" .
          " <b>ስልክ:</b> {$phone1}\n" .
          " <b>ቦት:</b> @{$this->botUsername}",
        'buttons' => [
          [
            $bookingButton,
            ['text' => " ሪሴፕሽን / ስልክ ({$phone1})", 'url' => $receptionUrl],
          ],
        ],
        'keywords' => ['carpet', 'rug', 'floor', 'ምንጣፍ', 'ምንጣፎች'],
      ],

      // CARD 7: Mattress Sanitization (HD Image of Hotel Mattress)
      [
        'id' => 'meash_mattress',
        'title' => ' የፍራሽ ሳኒታይዜሽንና እጥበት | Mattress Sanitization',
        'description' => "{$mattressP} ብር/ፍራሽ:: በሙቅ ስቲም ፀረ-ተባይና የአቧራ ትሎች ማጥፋት::",
        'thumbnail_url' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=240&h=240&fit=crop',
        'message_text' => " <b>የፍራሽ ሳኒታይዜሽን እና ጥልቅ እጥበት (MATTRESS SANITIZATION)</b>\n" .
          "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
          " <b>ፍጹም ንፁህ እና ጤናማ እንቅልፍ ይተኛሉ:</b>\n" .
          "• በአይን የማይታዩ የአቧራ ትሎችን እና አለርጂን ያጠፋል\n" .
          "• የላብ፣ የውሃ እና የቆሻሻ ምልክቶችን ያስለቅቃል\n" .
          "• የሆስፒታል ደረጃውን የጠበቀ ፀረ-ባክቴሪያ ሳኒታይዜሽን\n\n" .
          " <b>ዋጋ:</b> {$mattressP} ብር / በአንድ ፍራሽ (ኪንግ፤ ኩዊን፤ ነጠላ)\n\n" .
          " <b>ስልክ:</b> {$phone1}\n" .
          " <b>ቦት:</b> @{$this->botUsername}",
        'buttons' => [
          [
            $bookingButton,
            ['text' => " ሪሴፕሽን / ስልክ ({$phone1})", 'url' => $receptionUrl],
          ],
        ],
        'keywords' => ['mattress', 'bed', 'sleep', 'allergy', 'ፍራሽ', 'አልጋ'],
      ],

      // CARD 8: Glass Facade (HD Image of Glass Architecture)
      [
        'id' => 'meash_glass',
        'title' => ' የመስታወት እና ፊት ለፊት ግድግዳ ፅዳት | Glass Facade',
        'description' => "{$glassP} ብር/ካሬ ሜትር:: የቪላ፤ አፓርታማና የህንፃ መስታወት ፅዳት::",
        'thumbnail_url' => 'https://images.unsplash.com/photo-1513694203232-719a280e022f?w=240&h=240&fit=crop',
        'message_text' => " <b>የመስታወት እና የፊት ለፊት ህንፃ ፅዳት (GLASS & FACADE)</b>\n" .
          "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
          " ምንም አይነት የውሃ ነጠብጣብ የሌለው ፍጹም የሚያንጸባርቅ መስታወት!\n" .
          "• የውስጥ እና የውጭ መስታወት እጥበት\n" .
          "• የረንዳ መስታወት፣ የሻወር ካቢኔ እና የበር መስታወት\n" .
          "• ለከፍተኛ ህንፃዎች ደህንነታቸው የተጠበቀ ዘመናዊ መወጣጫዎች\n\n" .
          " <b>ዋጋ:</b> {$glassP} ብር / በአንድ ካሬ ሜትር\n\n" .
          " <b>ስልክ:</b> {$phone1}\n" .
          " <b>ቦት:</b> @{$this->botUsername}",
        'buttons' => [
          [
            $bookingButton,
            ['text' => " ሪሴፕሽን / ስልክ ({$phone1})", 'url' => $receptionUrl],
          ],
        ],
        'keywords' => ['glass', 'window', 'facade', 'መስታወት', 'መስኮት'],
      ],

      // CARD 9: Residential Deep Clean (HD Image of Spotless Home)
      [
        'id' => 'meash_home',
        'title' => ' የመኖሪያ ቤት ሙሉ ጥልቅ ፅዳት | Residential Deep Clean',
        'description' => "ከ {$homeP} ብር:: የግንባታ ማጠናቀቂያ ወይም የመኖሪያ ቤት ፅዳት::",
        'thumbnail_url' => 'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=240&h=240&fit=crop',
        'message_text' => " <b>የመኖሪያ ቤት ሙሉ ጥልቅ ፅዳት (RESIDENTIAL DEEP CLEAN)</b>\n" .
          "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
          " አጠቃላይ የቪላ እና የአፓርታማ ጥልቅ ፅዳት:\n" .
          "• ከግንባታ በኋላ የቀሩ የቀለምና የሲሚንቶ ቅርፊቶች ማስወገድ\n" .
          "• የኩሽና ቅባት ማስለቀቅ እና የወለል ንጣፎች እጥበት\n" .
          "• የመታጠቢያ ቤትና ሽንት ቤት ፀረ-ጀርም ሳኒታይዜሽን\n" .
          "• የወለል ማሽነሪ ፖሊሽ እና አጠቃላይ ንፅህና\n\n" .
          " <b>ዋጋ:</b> ከ {$homeP} ብር ጀምሮ\n\n" .
          " <b>ስልክ:</b> {$phone1}\n" .
          " <b>ቦት:</b> @{$this->botUsername}",
        'buttons' => [
          [
            $bookingButton,
            ['text' => " ሪሴፕሽን / ስልክ ({$phone1})", 'url' => $receptionUrl],
          ],
        ],
        'keywords' => ['home', 'house', 'residential', 'apartment', 'villa', 'ቤት', 'ቪላ'],
      ],

      // CARD 10: Commercial & Office Cleaning (HD Image of Modern Office)
      [
        'id' => 'meash_office',
        'title' => ' የቢሮ እና ተቋማት ፅዳት | Commercial & Office Cleaning',
        'description' => 'ለባንኮች፤ ኤምባሲዎች፤ ሆቴሎች እና ድርጅቶች በኮንትራት ወይም በፕሮፎርማ::',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=240&h=240&fit=crop',
        'message_text' => " <b>የቢሮ እና የተቋማት ፅዳት (COMMERCIAL & CORPORATE)</b>\n" .
          "<i>ሜሽ የፅዳት አገልግሎት - Meash Cleaning Solution</i>\n\n" .
          " የስራ ቦታዎ ሁልጊዜ ሳቢ እና ንፁህ ሆኖ እንዲቆይ:\n" .
          "• ሳምንታዊ እና ወርሃዊ መደበኛ የኮንትራት ስምምነቶች\n" .
          "• የደንብ ልብስ የለበሱ የታመኑና የሰለጠኑ ቋሚ ሰራተኞች\n" .
          "• ህጋዊ ደረሰኝ (VAT/TOT) እና የክፍያ ስምምነት ሰነዶች\n\n" .
          " <b>ስልክ:</b> {$phone1}\n" .
          " <b>ቦት:</b> @{$this->botUsername}",
        'buttons' => [
          [
            ['text' => " ፕሮፎርማ ይጠይቁ ({$phone1})", 'url' => $receptionUrl],
          ],
        ],
        'keywords' => ['office', 'commercial', 'corporate', 'hotel', 'bank', 'ቢሮ', 'ተቋም', 'ድርጅት'],
      ],

      // CARD 11: Help Guide & Commands
      [
        'id' => 'meash_bot_help',
        'title' => ' የቦቱ አጠቃቀም እና ትዕዛዞች | Bot Commands & Guide',
        'description' => '/start, /services, /book, /status, /contact, /help',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=240&h=240&fit=crop',
        'message_text' => " <b>የሜሽ ቴሌግራም ቦት የአጠቃቀም መመሪያ</b>\n\n" .
          "የሚከተሉትን ትዕዛዞች መጠቀም ይችላሉ:\n" .
          "• <code>/start</code> - ዋናውን ማውጫ ይከፍታል\n" .
          "• <code>/services</code> - የ 6ቱ አገልግሎቶችና የዋጋ ዝርዝር\n" .
          "• <code>/book</code> - በ 1 ደቂቃ ውስጥ ቀጠሮ ማስያዣ\n" .
          "• <code>/status ORD-...</code> - የትዕዛዝ ሁኔታ መከታተያ\n" .
          "• <code>/contact</code> - የሪሴፕሽን ስልክ አድራሻ ({$phone1})\n" .
          "• <code>/help</code> - ይህንን መመሪያ ያሳያል\n\n" .
          "በተጨማሪም በማንኛውም ቻት ውስጥ <code>@{$this->botUsername}</code> ብለው በመጻፍ አገልግሎቶችን ለወዳጅዎ ማጋራት ይችላሉ!",
        'buttons' => [
          [
            ['text' => ' ቦቱን ክፈት | Open Bot', 'url' => "https://t.me/{$this->botUsername}?start=help"],
            ['text' => ' ቴሌግራም ቻናል', 'url' => $tgChannelUrl],
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

    // Auto-capture phone from contact or text and sync directly to Customer
    $extractedPhone = null;
    if ($contact && !empty($contact['phone_number'])) {
      $extractedPhone = $contact['phone_number'];
    } elseif (!empty($text) && preg_match('/(09\d{8}|07\d{8}|\+2519\d{8}|\+2517\d{8})/', $text, $m)) {
      $extractedPhone = $m[1];
    }
    if ($extractedPhone) {
      if (str_starts_with($extractedPhone, '+251')) {
        $extractedPhone = '0' . substr($extractedPhone, 4);
      } elseif (str_starts_with($extractedPhone, '251')) {
        $extractedPhone = '0' . substr($extractedPhone, 3);
      }
      if ($tgUser) {
        $tgUser->phone_number = $extractedPhone;
        $tgUser->save();
      }
      try {
        $cName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: 'የቴሌግራም ደንበኛ';
        Customer::updateOrCreate(
          ['phone' => $extractedPhone],
          [
            'customer_code' => Customer::generateNextCode(),
            'full_name' => $cName,
            'address' => 'አያት አደባባይ፣ አዲስ አበባ',
            'telegram_user_id' => (string)$chatId,
            'marketing_consent' => true,
            'consent_timestamp' => now(),
          ]
        );
      } catch (\Throwable $th) {
        Log::warning('Auto customer sync failed: ' . $th->getMessage());
      }
    }

    // Handle Mini App WebApp Data submission (Telegram.WebApp.sendData)
    if (isset($message['web_app_data'])) {
      $this->handleWebAppData($chatId, $from, $message['web_app_data']);
      return;
    }

    // Handle Cancellation
    if ($text === '/cancel' || $text === 'ሰርዝ' || $text === ' ሰርዝ' || $text === ' ሰርዝ (Cancel)') {
      if ($tgUser) {
        $tgUser->bot_state = null;
        $tgUser->payload_cache = null;
        $tgUser->save();
      }
      $this->telegramApi('sendMessage', [
        'chat_id' => $chatId,
        'text' => " <b>የቀጠሮ ምዝገባው ተሰርዟል::</b>\n\nአዲስ ቀጠሮ ለመያዝ /book ወይም /start ብለው መጀመር ይችላሉ::",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode(['remove_keyboard' => true]),
      ]);
      return;
    }

    // Handle customer text review comment submission after rating
    if (Cache::has("tg_awaiting_feedback_{$chatId}")) {
      $feedbackSession = Cache::get("tg_awaiting_feedback_{$chatId}");
      Cache::forget("tg_awaiting_feedback_{$chatId}");

      if ($text === '/skip' || $text === 'ዝለል') {
        $this->telegramApi('sendMessage', [
          'chat_id' => $chatId,
          'text' => "እናመሰግናለን! መልካም ቀን ይሁንልዎ::",
          'parse_mode' => 'HTML',
        ]);
        return;
      } elseif (!empty($text) && !empty($feedbackSession['feedback_id'])) {
        try {
          $fb = Feedback::with(['customer', 'order'])->find($feedbackSession['feedback_id']);
          if ($fb) {
            $isPlaceholder = empty($fb->comment) || $fb->comment === 'አስተያየት ተመዝግቧል' || $fb->comment === 'ደንበኛ የሰጠው ደረጃ';
            $finalComment = $isPlaceholder ? $text : ($fb->comment . "\n" . $text);
            $fb->comment = $finalComment;
            $fb->save();
            if ($fb->customer) {
              OrderLifecycleService::recordCustomerReviewAndPromote(
                $fb->customer,
                $fb->order,
                $fb->rating,
                $finalComment,
                'telegram'
              );
            }
          }
        } catch (\Throwable $th) {
          Log::warning('Saving feedback comment failed: ' . $th->getMessage());
        }

        $this->telegramApi('sendMessage', [
          'chat_id' => $chatId,
          'text' => "<b>አስተያየትዎን ተቀብለናል!</b>\n\nአገልግሎታችንን ለማሻሻል ስላገዙን እና የተሰማዎትን ስላጋሩን ከልብ እናመሰግናለን:: መልካም ቀን ይሁንልዎ!",
          'parse_mode' => 'HTML',
        ]);
        return;
      }
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

      $welcomeAm = WebsiteSetting::getVal('bot_welcome_am', "<b>እንኳን ወደ ሜአሽ የጽዳት አገልግሎት (MEASH CLEANING SOLUTION) በደህና መጡ!</b>\n\n\"Your Environment, Our Responsibility.\"\n\nከፍተኛ ጥራት የጠበቀ የንግድ ተቋማት የጽዳት አገልግሎት — ሆቴሎች | ሬስቶራንቶች | ካፌዎች | ቢሮዎች።\n\nዛሬ ምን ማፅዳት ይፈልጋሉ? ከታች ካሉት አማራጮች አንዱን ይምረጡ:");
      $welcomeEn = WebsiteSetting::getVal('bot_welcome_en', "Welcome to MEASH CLEANING SOLUTION!\n\n\"Your Environment, Our Responsibility.\"\n\nPremium Commercial Cleaning Solutions — Hotels | Restaurants | Cafés | Offices.");
      $welcome = "{$welcomeAm}\n\n<i>{$welcomeEn}</i>";

      $httpsUrl = $this->getMiniAppHttpsUrl();
      $wizardButton = ['text' => 'በቦቱ ቀጠሮ ያስይዙ (1 ደቂቃ)', 'callback_data' => 'book_start_wizard'];
      $appButton = $httpsUrl
        ? ['text' => 'በቴሌግራም ሚኒ አፕ (Mini App)', 'web_app' => ['url' => $httpsUrl]]
        : ['text' => 'ቦታ ያስይዙ | Book Now', 'callback_data' => 'menu_book'];

      $replyMarkup = [
        'inline_keyboard' => [
          [
            $wizardButton,
          ],
          [
            $appButton,
          ],
          [
            ['text' => 'አገልግሎቶችና ዋጋ | Services', 'callback_data' => 'menu_services'],
            ['text' => 'የትዕዛዝ ሁኔታ | Status', 'callback_data' => 'menu_status'],
          ],
          [
            ['text' => 'ሪሴፕሽን ያግኙ | Reception', 'callback_data' => 'menu_contact'],
            ['text' => 'አስተያየት ይስጡ | Feedback', 'callback_data' => 'menu_feedback'],
          ],
          [
            ['text' => 'ቴሌግራም ቻናል', 'url' => 'https://t.me/meashdeepcleanning'],
            ['text' => 'ቀጥታ ለማነጋገር (Contact)', 'url' => 'https://t.me/meashcleaning'],
          ],
          [
            ['text' => 'TikTok', 'url' => 'https://www.tiktok.com/@meashcleaningsolution'],
            ['text' => 'Instagram', 'url' => 'https://instagram.com/meashcleaningsolution'],
          ],
          [
            ['text' => 'ለሌሎች አጋራ | Share', 'switch_inline_query_current_chat' => ''],
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
    } elseif (str_starts_with($text, '/status') || str_starts_with($text, '/track') || str_starts_with($text, 'ORD-') || str_starts_with($text, 'MEASH-')) {
      $orderCode = strtoupper(trim(str_replace(['/status', '/track'], '', $text)));
      $this->checkOrderStatus($chatId, $orderCode);
      return;
    } elseif (false) {
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
        'text' => "ጤና ይስጥልኝ! ሜአሽ የጽዳት አገልግሎት (MEASH CLEANING SOLUTION) ነው::\n\n\"Your Environment, Our Responsibility.\"\n\nቀጠሮ ለመያዝ፣ አገልግሎቶችን ለማየት ወይም ሪሴፕሽን ለማነጋገር /start ብለው ይላኩ::\n\nለበለጠ መረጃ: " . $this->getPhonesCombined() . "\nቦት: @{$this->botUsername}",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
          'inline_keyboard' => [
            [['text' => 'ዋና ማውጫ | Main Menu', 'callback_data' => 'menu_start']],
            [['text' => 'ቦታ ያስይዙ | Book Now', 'callback_data' => 'menu_book']],
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

    // Detect Service dynamically from database
    $activeServices = Service::where('is_active', true)->get();
    $matchedService = $activeServices->firstWhere('code', 'sofa') ?: $activeServices->first();
    $qty = 5;

    $lowerText = mb_strtolower($text, 'UTF-8');

    if (str_contains($lowerText, 'ምንጣፍ') || str_contains($lowerText, 'carpet')) {
      $found = $activeServices->firstWhere('code', 'carpet');
      if ($found) { $matchedService = $found; $qty = 20; }
    } elseif (str_contains($lowerText, 'ፍራሽ') || str_contains($lowerText, 'mattress')) {
      $found = $activeServices->firstWhere('code', 'mattress');
      if ($found) { $matchedService = $found; $qty = 2; }
    } elseif (str_contains($lowerText, 'መስታወት') || str_contains($lowerText, 'glass')) {
      $found = $activeServices->firstWhere('code', 'glass');
      if ($found) { $matchedService = $found; $qty = 15; }
    } elseif (str_contains($lowerText, 'ቤት') || str_contains($lowerText, 'መኖሪያ') || str_contains($lowerText, 'home') || str_contains($lowerText, 'residential')) {
      $found = $activeServices->firstWhere('code', 'residential_deep') ?: $activeServices->firstWhere('code', 'residential');
      if ($found) { $matchedService = $found; $qty = 1; }
    } elseif (str_contains($lowerText, 'ቢሮ') || str_contains($lowerText, 'ተቋም') || str_contains($lowerText, 'office') || str_contains($lowerText, 'commercial')) {
      $found = $activeServices->firstWhere('code', 'commercial') ?: $activeServices->firstWhere('code', 'office');
      if ($found) { $matchedService = $found; $qty = 1; }
    } elseif (str_contains($lowerText, 'ሶፋ') || str_contains($lowerText, 'sofa') || str_contains($lowerText, 'ወንበር')) {
      $found = $activeServices->firstWhere('code', 'sofa');
      if ($found) { $matchedService = $found; $qty = 5; }
    }

    $serviceCode = $matchedService?->code ?? 'sofa';
    $serviceNameAm = $matchedService?->name_am ?: ($matchedService?->name_en ?: 'የሶፋ ጥልቅ አረፋ እጥበት');
    $unitPrice = (float)($matchedService?->base_price ?? 350.0);

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

    $service = $matchedService ?: Service::where('code', $serviceCode)->first();
    $unitPrice = $service ? (float)$service->base_price : $unitPrice;
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

    // Dispatch non-critical side effects (SMS, Admin alert) after response to guarantee sub-2s response
    $this->dispatchPostBookingSideEffects($order, 'telegram_direct');

    // Send instant Telegram confirmation
    $totalEst = $order->fresh()->total_amount;
    $ethAppt = \App\Services\EthiopianCalendarService::toEthiopian($order->appointment_date);

    $confirmMsg = " <b>እናመሰግናለን {$name}! የቀጠሮ ጥያቄዎ በተሳካ ሁኔታ ተመዝግቧል!</b>\n\n" .
      " <b>የትዕዛዝ ቁጥር:</b> <code>{$order->order_number}</code>\n" .
      " <b>ደንበኛ:</b> {$name}\n" .
      " <b>ስልክ:</b> {$phone}\n" .
      " <b>አገልግሎት:</b> {$serviceNameAm}\n" .
      " <b>አድራሻ:</b> {$address} ({$subcity})\n" .
      " <b>የቀጠሮ ቀን:</b> {$order->appointment_date->format('Y-m-d')} ({$ethAppt['formatted_am']}) ጠዋት 2:00\n" .
      " <b>ጠቅላላ ክፍያ / ዋጋ:</b> " . number_format($totalEst, 2) . " ብር\n\n" .
      " <b>የማረጋገጫ ኤስኤምኤስ (SMS) ወደ ስልክዎ ({$phone}) ተልኳል::</b>\n" .
      "የሜሽ ሪሴፕሽን ቡድን በቅርቡ በስልክ (" . $this->getPhone1() . ") ደውሎ ያረጋግጥልዎታል:: ስለመረጡን እናመሰግናለን! ";

    $this->telegramApi('sendMessage', [
      'chat_id' => $chatId,
      'text' => $confirmMsg,
      'parse_mode' => 'HTML',
      'reply_markup' => json_encode([
        'inline_keyboard' => [
          [
            ['text' => ' የትዕዛዝ ሁኔታ | Status', 'callback_data' => 'menu_status'],
            ['text' => ' ሪሴፕሽን (' . $this->getPhone1() . ')', 'callback_data' => 'menu_contact'],
          ],
          [
            ['text' => ' ቴሌግራም ቻናል', 'url' => $this->getTelegramChannelUrl()],
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

    // Handle customer 5-star rating callback
    if (str_starts_with($data, 'rate_order:')) {
      $parts = explode(':', $data);
      $orderId = isset($parts[1]) ? (int)$parts[1] : 0;
      $stars = isset($parts[2]) ? (int)$parts[2] : 5;
      $stars = max(1, min(5, $stars));

      $order = Order::with('customer')->find($orderId);
      if ($order && $order->customer) {
        $feedback = OrderLifecycleService::recordCustomerReviewAndPromote(
          $order->customer,
          $order,
          $stars,
          null,
          'telegram'
        );

        Cache::put("tg_awaiting_feedback_{$chatId}", [
          'feedback_id' => $feedback->id,
          'order_id' => $order->id,
        ], now()->addHours(2));

        $this->telegramApi('answerCallbackQuery', [
          'callback_query_id' => $id,
          'text' => "የሰጡት {$stars} ኮከብ ተመዝግቧል! እናመሰግናለን።",
        ]);

        $this->telegramApi('sendMessage', [
          'chat_id' => $chatId,
          'text' => "<b>እናመሰግናለን!</b> የሰጡት ደረጃ ({$stars}/5) በተሳካ ሁኔታ ተመዝግቧል::\n\nተጨማሪ አስተያየት ካለዎት በጽሑፍ ይላኩልን (ወይም /skip ይበሉ)።",
          'parse_mode' => 'HTML',
        ]);
        return;
      } else {
        $this->telegramApi('answerCallbackQuery', [
          'callback_query_id' => $id,
          'text' => 'የትዕዛዝ መረጃው አልተገኘም።',
        ]);
        return;
      }
    }

    // Handle interactive booking wizard callbacks
    if (str_starts_with($data, 'book_')) {
      if ($this->handleInteractiveBookingCallback($tgUser, $data, $chatId)) {
        $this->telegramApi('answerCallbackQuery', ['callback_query_id' => $id]);
        return;
      }
    }

        if (str_starts_with($data, 'resched_')) {
      $orderNo = trim(str_replace('resched_', '', $data));
      $this->telegramApi('sendMessage', [
        'chat_id' => $chatId,
        'text' => " <b>ቀጠሮዎን ለማራዘም ወይም አዲስ ቀን ለመቀየር:</b>\n\nየትዕዛዝ ቁጥር: <code>{$orderNo}</code>\n\nእባክዎ የሚፈልጉትን አዲስ ቀን በ " . $this->getPhone1() . " ደውለው ወይም በ " . $this->getTelegramSupportHandle() . " መልዕክት ይንገሩን:: ሪሴፕሽናችን ወዲያውኑ ያስተካክላል::",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
          'inline_keyboard' => [
            [['text' => ' ሪሴፕሽን ደውል (' . $this->getPhone1() . ')', 'callback_data' => 'menu_contact']],
            [['text' => ' የትዕዛዝ ሁኔታ | Status', 'callback_data' => "track_{$orderNo}"]],
          ]
        ])
      ]);
      $this->telegramApi('answerCallbackQuery', ['callback_query_id' => $id]);
      return;
    }

    if (str_starts_with($data, 'track_')) {
      $orderNo = trim(str_replace('track_', '', $data));
      $this->checkOrderStatus($chatId, $orderNo);
      $this->telegramApi('answerCallbackQuery', ['callback_query_id' => $id]);
      return;
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
        'text' => "<b>የትዕዛዝዎን ሁኔታ ለመከታተል:</b>\n\nየትዕዛዝ ቁጥርዎን (ለምሳሌ <code>ORD-2026-0001</code>) ጽፈው ይላኩልን:: የትዕዛዝዎን ዝርዝር እና የቡድኑን ሁኔታ ወዲያውኑ እንልክልዎታለን::",
        'parse_mode' => 'HTML',
      ]);
    } elseif ($data === 'menu_feedback') {
      $this->telegramApi('sendMessage', [
        'chat_id' => $chatId,
        'text' => "<b>የደንበኞች አስተያየት እና ደረጃ:</b>\n\nየተሰጠዎት የፅዳት አገልግሎት እንዴት ነበር? አስተያየትዎን ወይም ደረጃዎን ከ 1 እስከ 5 ኮከብ በ " . $this->getPhone1() . " ይንገሩን:: የእርስዎ እርካታ የኩባንያችን ዋና አላማ ነው!",
        'parse_mode' => 'HTML',
      ]);
    } elseif ($data === 'menu_complaint') {
      $this->telegramApi('sendMessage', [
        'chat_id' => $chatId,
        'text' => "<b>ቅሬታ ማሳወቂያ እና አስቸኳይ ክትትል:</b>\n\nበተሰጠው አገልግሎት ላይ ያልተሟላ ነገር ካለ ወይም ቅሬታ ካለዎት ወዲያውኑ በ " . $this->getPhone1() . " ይደውሉልን:: የቡድን መሪያችን በ 24 ሰዓት ውስጥ መጥቶ ያለምንም ተጨማሪ ክፍያ ዳግም እንዲፀዳ ይደረጋል!",
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
        ['text' => ' በቦቱ ቀጠሮ ያስይዙ (1 ደቂቃ)', 'callback_data' => 'book_start_wizard'],
      ],
    ];

    if ($httpsUrl) {
      $buttons[] = [['text' => ' በቴሌግራም ሚኒ አፕ ክፈት (Mini App)', 'web_app' => ['url' => $httpsUrl]]];
    }

    $buttons[] = [
      ['text' => ' በስልክ ለማዘዝ (' . $this->getPhone1() . ')', 'callback_data' => 'menu_contact'],
      ['text' => ' አገልግሎቶች ዝርዝር', 'callback_data' => 'menu_services'],
    ];

    $msg = " <b>ቀጠሮ ለመያዝ | Appointment Booking</b>\n\n" .
      "በሜሽ ክሊኒንግ 3 ምቹ የቀጠሮ ማስያዣ መንገዶች አሉ:\n\n" .
      "1. <b>በቦቱ ደረጃ-በደረጃ ቀጠሮ ማስያዝ (Interactive Wizard):</b>\n" .
      "  አገልግሎት፣ ስም፣ ስልክና አድራሻዎን በማስገባት በ 1 ደቂቃ ውስጥ ይመዝገቡ!\n\n" .
      "2. <b>በቴሌግራም ሚኒ አፕ (Telegram Mini App):</b>\n" .
      "  ቀጥታ በስማርት ፎርም እቃዎችን መርጠው ዋጋውን እያዩ ማዘዝ ይችላሉ::\n\n" .
      "3. <b>በስልክ በቀጥታ ለማዘዝ:</b>\n" .
      "  • " . $this->getPhonesCombined() . "\n\n" .
      "አሁኑኑ ለመጀመር ከታች ያለውን <b>' በቦቱ ቀጠሮ ያስይዙ'</b> የሚለውን ቁልፍ ይጫኑ ";

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
      $msg = " <b>የሜሽ የፅዳት አገልግሎቶች እና ወቅታዊ የዋጋ ዝርዝር</b>\n" .
        "<i>(Meash Cleaning Official Services & Real-time Rates)</i>\n\n";
      $idx = 1;
      foreach ($dbServices as $svc) {
        $name = $svc->name_am ?: $svc->name_en;
        $price = number_format((float)$svc->base_price);
        $unit = $svc->pricing_unit_am ?: ($svc->pricing_unit ?: 'አገልግሎት');
        $desc = $svc->description_am ? "  • {$svc->description_am}\n" : '';
        $msg .= "{$idx}. <b>{$name}</b>\n  • ዋጋ: <b>{$price} ብር</b> / {$unit}\n{$desc}\n";
        $idx++;
      }
      $msg .= "ቦታ ለማስያዝ ወይም ለመደወል ከታች ያለውን ቁልፍ ይጫኑ ";
    } else {
      $msg = " <b>የሜሽ የፅዳት አገልግሎቶች እና ዋጋ ዝርዝር</b>\n\n" .
        "1. <b>የሶፋ እጥበት:</b> 350 ብር / ወንበር\n" .
        "2. <b>የምንጣፍ እጥበት:</b> 80 ብር / ካሬ\n" .
        "3. <b>የፍራሽ ሳኒታይዜሽን:</b> 600 ብር / ፍራሽ\n" .
        "4. <b>የመስታወት ፅዳት:</b> 70 ብር / ካሬ\n" .
        "5. <b>የቤት ጥልቅ ፅዳት:</b> ከ 2,500 ብር ጀምሮ\n\n" .
        "ቀጠሮ ለመያዝ ከታች ያለውን ቁልፍ ይጫኑ ";
    }

    $buttons = [
      [['text' => ' አሁኑኑ ቦታ ያስይዙ | Book Now', 'callback_data' => 'menu_book']],
      [['text' => ' በስልክ ለማዘዝ | Call Reception', 'callback_data' => 'menu_contact']],
    ];

    $httpsUrl = $this->getMiniAppHttpsUrl();
    if ($httpsUrl) {
      $buttons[0] = [['text' => ' አሁኑኑ ቦታ ያስይዙ | Book Now', 'web_app' => ['url' => $httpsUrl]]];
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
    $phone1 = WebsiteSetting::getVal('contact_phone_1', '0970075509');
    $phone2 = WebsiteSetting::getVal('contact_phone_2', '0998142190');
    $addressAm = WebsiteSetting::getVal('company_address_am', 'አያት አደባባይ፣ አዲስ አበባ');
    $hoursAm = WebsiteSetting::getVal('working_hours_am', 'ከሰኞ እስከ ቅዳሜ ከጠዋቱ 2:00 እስከ ምሽቱ 12:00 (እሁድ እና የሌሊት ፈረቃ በቀጠሮ)');
    $channelUrl = $this->getTelegramChannelUrl();
    $tgSupport = $this->getTelegramSupportHandle();
    $tgSupportUrl = str_starts_with($tgSupport, 'http') ? $tgSupport : 'https://t.me/' . ltrim($tgSupport, '@');
    $tiktokUrl = (string)WebsiteSetting::getVal('tiktok', 'https://www.tiktok.com/@meashcleaningsolution');

    $msg = "<b>የሜአሽ የጽዳት አገልግሎት (MEASH CLEANING SOLUTION)</b>\n" .
      "<b>\"Your Environment, Our Responsibility.\"</b>\n\n" .
      "<b>የደንበኞች አገልግሎትና ሪሴፕሽን (Customer Support Desk):</b>\n\n" .
      "• <b>ዋና ስልክ:</b> {$phone1}\n" .
      ($phone2 ? "• <b>ተጨማሪ ስልክ:</b> {$phone2}\n" : '') .
      "• <b>ኢሜይል:</b> meashcleaningsolution@gmail.com\n" .
      "• <b>የቴሌግራም ቀጥታ ማውሪያ:</b> {$tgSupport}\n" .
      "• <b>የስራ ሰዓት:</b> {$hoursAm}\n" .
      "• <b>አድራሻ:</b> {$addressAm}\n\n" .
      "• <b>TikTok:</b> @meashcleaningsolution\n" .
      "• <b>Instagram | Facebook:</b> @meashcleaningsolution\n" .
      "• <b>ኦፊሴላዊ ቦት:</b> @{$this->botUsername}\n" .
      "• <b>ኦፊሴላዊ ቻናል:</b> {$channelUrl}\n\n" .
      "ጥያቄ፣ አስተያየት ወይም የቀጠሮ ማስተካከያ ካለዎት በማንኛውም ጊዜ ይደውሉልን ወይም በቴሌግራም {$tgSupport} ያናግሩን!";

    $this->telegramApi('sendMessage', [
      'chat_id' => $chatId,
      'text' => $msg,
      'parse_mode' => 'HTML',
      'reply_markup' => json_encode([
        'inline_keyboard' => [
          [['text' => "ቀጥታ ያናግሩ ({$tgSupport})", 'url' => $tgSupportUrl]],
          [['text' => 'ቦታ ያስይዙ | Book Now', 'callback_data' => 'menu_book']],
          [
            ['text' => 'ቴሌግራም ቻናል', 'url' => $channelUrl],
            ['text' => 'TikTok ገጽ', 'url' => $tiktokUrl],
          ]
        ]
      ])
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
        'text' => " ይቅርታ፣ በትዕዛዝ ቁጥር <code>{$orderCode}</code> የተመዘገበ መረጃ አልተገኘም:: እባክዎን ቁጥሩን አስተካክለው ይሞክሩ ወይም በ " . $this->getPhone1() . " ይደውሉ::",
        'parse_mode' => 'HTML',
      ]);
      return;
    }

    $isPostponed = str_contains((string)$order->notes, 'ቀጠሮ ተላልፏል') || $order->order_status === 'rescheduled';

    $statusAm = match ($order->order_status) {
      'new' => 'አዲስ ተመዝግቧል (New)',
      'confirmed' => 'ተረጋግጧል (Confirmed)',
      'in_progress' => 'በስራ ላይ (In Progress)',
      'completed' => 'ተጠናቋል (Completed)',
      'cancelled' => 'ተሰርዟል (Cancelled)',
      'rescheduled' => ' ቀጠሮ ተላልፏል (Rescheduled)',
      default => $order->order_status,
    };

    $teamName = $order->assignedTeam ? ($order->assignedTeam->team_name ?? $order->assignedTeam->name) : 'በመመደብ ላይ';
    $apptDateStr = $order->appointment_date ? (is_object($order->appointment_date) ? $order->appointment_date->format('Y-m-d') : substr((string)$order->appointment_date, 0, 10)) : 'ቀን አልተወሰነም';

    $eth = \App\Services\EthiopianCalendarService::toEthiopian($apptDateStr);
    $ethDateStr = $eth['formatted_am'] ?? $apptDateStr;

    $postponeBanner = '';
    if ($isPostponed) {
      $postponeBanner = " <b>ማሳሰቢያ፡ የዚህ ትዕዛዝ ቀጠሮ ተላልፏል! (Rescheduled)</b>\n" .
        " <b>አዲሱ የቀጠሮ ቀን:</b> <b>{$apptDateStr} ({$ethDateStr})</b>\n\n";
    }

    $msg = " <b>የትዕዛዝ መረጃ - {$order->order_number}</b>\n\n" .
      $postponeBanner .
      " <b>ደንበኛ:</b> " . ($order->customer?->full_name ?? 'ውድ ደንበኛችን') . "\n" .
      " <b>አድራሻ:</b> {$order->address} ({$order->subcity})\n" .
      " <b>የቀጠሮ ቀን:</b> <b>{$apptDateStr}</b> ({$ethDateStr})\n" .
      " <b>ሰዓት:</b> {$order->appointment_time_slot}\n" .
      " <b>የትዕዛዝ ሁኔታ:</b> <b>{$statusAm}</b>\n" .
      " <b>የተመደበው ቡድን:</b> {$teamName}\n" .
      " <b>ጠቅላላ ክፍያ:</b> " . number_format((float)($order->total ?? $order->total_amount ?? 0), 2) . " ብር\n\n" .
      "ቀጠሮዎን መቀየር ወይም ማራዘም ከፈለጉ ከስር ያለውን ቁልፍ ይጫኑ ወይም በ " . $this->getPhone1() . " ይደውሉ::";

    $buttons = [
      [
        ['text' => ' ቀጠሮ ቀን አስተላልፍ (Change Date)', 'callback_data' => "resched_{$order->order_number}"],
        ['text' => ' ሪሴፕሽን (' . $this->getPhone1() . ')', 'callback_data' => 'menu_contact'],
      ],
      [
        ['text' => ' ዋና ማውጫ | Main Menu', 'callback_data' => 'menu_start'],
      ]
    ];

    $this->telegramApi('sendMessage', [
      'chat_id' => $chatId,
      'text' => $msg,
      'parse_mode' => 'HTML',
      'reply_markup' => json_encode(['inline_keyboard' => $buttons]),
    ]);
  }

  private function sendHelpMessage(int|string $chatId): void
  {
    $helpAm = WebsiteSetting::getVal('bot_help_am', "የሚከተሉትን ትዕዛዞች መጠቀም ይችላሉ:\n• <code>/start</code> - ዋናውን ማውጫ ይከፍታል\n• <code>/services</code> - የአገልግሎቶች እና የዋጋ ዝርዝር\n• <code>/book</code> - በ 1 ደቂቃ ውስጥ ቀጠሮ ማስያዣ\n• <code>/status ORD-...</code> - የትዕዛዝ ሁኔታ መከታተያ\n• <code>/contact</code> - የሪሴፕሽን ስልክ አድራሻ\n• <code>/help</code> - ይህንን መመሪያ ያሳያል");
    $helpEn = WebsiteSetting::getVal('bot_help_en', "Available commands:\n• <code>/start</code> - Main menu\n• <code>/services</code> - Services & Pricing\n• <code>/book</code> - 1-Minute Booking\n• <code>/status ORD-...</code> - Track Order\n• <code>/contact</code> - Reception Hotline\n• <code>/help</code> - Help guide");
    $phone1 = WebsiteSetting::getVal('contact_phone_1', '0970075509');

    $msg = "<b>የሜሽ ቴሌግራም ቦት መመሪያ</b>\n\n" .
      "{$helpAm}\n\n" .
      "<i>{$helpEn}</i>\n\n" .
      "<b>ሪሴፕሽን:</b> {$phone1}\n\n" .
      "እንዲሁም በማንኛውም የቴሌግራም ቻት ውስጥ <code>@{$this->botUsername}</code> ብለው በመጻፍ አገልግሎቶችን ለወዳጅዎ ማጋራት ይችላሉ!";

    $this->telegramApi('sendMessage', [
      'chat_id' => $chatId,
      'text' => $msg,
      'parse_mode' => 'HTML',
      'reply_markup' => json_encode([
        'inline_keyboard' => [
          [['text' => ' ዋና ማውጫ | Main Menu', 'callback_data' => 'menu_start']],
          [['text' => ' ቦታ ያስይዙ | Book Now', 'callback_data' => 'menu_book']],
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
      'init_data' => 'nullable|string',
      'notes' => 'nullable|string',
      'items' => 'required|array|min:1',
      'items.*.service_id' => 'nullable|exists:services,id',
      'items.*.service_code' => 'nullable|string|max:64',
      'items.*.item_name' => 'required|string',
      'items.*.quantity' => 'required|numeric|min:0.1',
      'items.*.unit_price' => 'required|numeric|min:0',
    ]);

    $initData = $request->input('init_data');
    $enforceInitData = config('services.telegram.enforce_initdata') !== null
      ? (bool)config('services.telegram.enforce_initdata')
      : filter_var(env('TELEGRAM_ENFORCE_INITDATA', false), FILTER_VALIDATE_BOOLEAN);

    $maxAgeSeconds = (int)(config('services.telegram.auth_date_max_age') ?: env('TELEGRAM_AUTH_DATE_MAX_AGE', 900));
    $initDataHash = !empty($initData) ? hash('sha256', (string)$initData) : null;

    if (!empty($initData)) {
      // Atomic claim for replay & concurrency protection across the entire validity window
      if ($initDataHash && !Cache::add("tg_miniapp_replay_{$initDataHash}", true, now()->addSeconds($maxAgeSeconds))) {
        throw ValidationException::withMessages([
          'init_data' => ['This Telegram WebApp session request has already been claimed or processed. Please refresh.'],
        ]);
      }

      $extractedUser = null;
      $isValid = $this->verifyTelegramInitData((string)$initData, $this->botToken, $extractedUser);
      if ($isValid && !empty($extractedUser['id'])) {
        // Authoritative assignment of telegram_user_id ONLY from cryptographically verified Telegram payload
        $validated['telegram_user_id'] = (string)$extractedUser['id'];
      } elseif (!$isValid && $enforceInitData) {
        if ($initDataHash) {
          Cache::forget("tg_miniapp_replay_{$initDataHash}");
        }
        throw ValidationException::withMessages([
          'init_data' => ['Invalid or untrusted Telegram WebApp signature.'],
        ]);
      } elseif (!empty($request->input('telegram_user_id'))) {
        $validated['telegram_user_id'] = (string)$request->input('telegram_user_id');
      }
    } elseif (!empty($request->input('telegram_user_id'))) {
      $validated['telegram_user_id'] = (string)$request->input('telegram_user_id');
    } elseif ($enforceInitData) {
      throw ValidationException::withMessages([
        'init_data' => ['Telegram WebApp initData is strictly required for booking.'],
      ]);
    }

    // Resolve every item before creating records. The browser never controls a price.
    $catalogServices = [];
    foreach ($validated['items'] as $index => $item) {
      $service = $this->resolveMiniAppService($item);
      if (!$service) {
        throw ValidationException::withMessages([
          "items.{$index}.service_id" => ['Selected service is unavailable. Please refresh the Mini App and try again.'],
        ]);
      }
      $catalogServices[$index] = $service;
    }

    $plan = $validated['subscription_plan'] ?? 'one_time';
    $pricingRules = WebsiteSetting::getJson('pricing_rules', [
        'weekly_discount' => 15,
        'biweekly_discount' => 10,
        'monthly_discount' => 5,
    ]);
    $discRates = [
        'weekly' => (float)($pricingRules['weekly_discount'] ?? 15.0),
        'biweekly' => (float)($pricingRules['biweekly_discount'] ?? 10.0),
        'monthly' => (float)($pricingRules['monthly_discount'] ?? 5.0),
        'one_time' => 0.0,
    ];
    $discPercent = $discRates[$plan] ?? 0.0;

    $order = DB::transaction(function () use ($validated, $catalogServices, $plan, $discPercent) {
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
      } else {
        if (!empty($validated['latitude']) && !empty($validated['longitude'])) {
          $customer->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
          ]);
        }
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
      foreach ($validated['items'] as $index => $it) {
        $service = $catalogServices[$index];
        $unitPrice = (float)$service->base_price;
        $itemName = $service->name_am ?: $service->name_en;
        $sub = round((float)$it['quantity'] * $unitPrice, 2);
        $subtotalCalc += $sub;
        $itemsSummary[] = "{$itemName} (x{$it['quantity']})";

        OrderItem::create([
          'order_id' => $order->id,
          'service_id' => $service->id,
          'item_name' => $itemName,
          'quantity' => $it['quantity'],
          'unit_price' => $unitPrice,
          'subtotal' => $sub,
        ]);
      }

      // Update order subtotal and total
      $order->subtotal = $subtotalCalc;
      $order->total = $subtotalCalc;

      // Apply recurring subscription discount if chosen
      if ($discPercent > 0) {
        $subDiscount = round(($subtotalCalc * $discPercent) / 100.0, 2);
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

      // Create corresponding Appointment record for Reception Dispatch Calendar and Metrics
      [$startTime, $endTime] = $this->parseTimeSlot($order->appointment_time_slot);

      $notesParts = [];
      if (!empty($order->appointment_time_slot)) {
        $notesParts[] = "Slot: {$order->appointment_time_slot}";
      }
      $location = trim(($order->subcity ? "{$order->subcity}, " : '') . ($order->address ?? ''));
      if ($location) {
        $notesParts[] = "Location: {$location}";
      }
      if (!empty($order->notes) && $order->notes !== 'Booked via Telegram Mini App') {
        $notesParts[] = "Notes: {$order->notes}";
      } else {
        $notesParts[] = "Source: Telegram MiniApp";
      }

      Appointment::create([
        'order_id' => $order->id,
        'customer_id' => $customer->id,
        'cleaning_team_id' => null,
        'appointment_date' => $order->appointment_date,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'status' => 'scheduled',
        'notes' => implode(' | ', $notesParts),
      ]);

      return $order;
    });

    // Send instant notification in Amharic to customer if booked through Telegram
    if (!empty($validated['telegram_user_id'])) {
      $totalEst = (float)$order->fresh()->total;
      $planTxt = $plan !== 'one_time' ? "\n <b>የተመረጠ እቅድ:</b> {$plan} ({$discPercent}% ቅናሽ)" : "";
      $this->telegramApi('sendMessage', [
        'chat_id' => $validated['telegram_user_id'],
        'text' => " <b>እንኳን ደስ አላችሁ! የቀጠሮ ጥያቄዎ በተሳካ ሁኔታ ተመዝግቧል!</b>\n\n" .
          " <b>የትዕዛዝ ቁጥር:</b> <code>{$order->order_number}</code>\n" .
          " <b>የቀጠሮ ቀን:</b> {$order->appointment_date->format('Y-m-d')} ({$order->appointment_time_slot}){$planTxt}\n" .
          " <b>አድራሻ:</b> {$order->address}\n" .
          " <b>ጠቅላላ ክፍያ / ዋጋ:</b> " . number_format($totalEst, 2) . " ብር\n\n" .
          "የሜሽ ሪሴፕሽን ቡድን በቅርቡ በስልክ (" . $this->getPhone1() . ") ደውሎ ያረጋግጥልዎታል:: ስለመረጡን እናመሰግናለን! ",
        'parse_mode' => 'HTML',
      ]);
    }

    // Mark initData hash as used to prevent replay attacks
    if ($initDataHash) {
      Cache::put("tg_miniapp_used_{$initDataHash}", true, now()->addMinutes(30));
    }

    // Dispatch non-critical side effects (SMS, Admin alert) after response to guarantee sub-2s response
    $this->dispatchPostBookingSideEffects($order, 'telegram_miniapp_api');

    return response()->json([
      'message' => 'ቀጠሮዎ በተሳካ ሁኔታ ተመዝግቧል! የሪሴፕሽን ሰራተኞቻችን በቅርቡ ያረጋግጡልዎታል::',
      'booking_id' => $order->order_number,
      'order' => $order->fresh(['items', 'customer']),
    ], 201);
  }

  private function resolveMiniAppService(array $item): ?Service
  {
    $query = Service::query()->where('is_active', true);

    if (!empty($item['service_id'])) {
      return $query->whereKey($item['service_id'])->first();
    }

    if (!empty($item['service_code'])) {
      return $query->where('code', $item['service_code'])->first();
    }

    if (!empty($item['item_name'])) {
      return $query->where(function ($q) use ($item) {
        $q->where('name_en', $item['item_name'])->orWhere('name_am', $item['item_name']);
      })->first();
    }

    return null;
  }

  /**
   * Alias for miniAppBooking endpoint
   */
  public function miniAppBook(Request $request): JsonResponse
  {
    return $this->miniAppBooking($request);
  }

  /**
   * Cryptographically verifies Telegram WebApp initData using HMAC-SHA256
   * as specified in the official Telegram Bot API WebApp documentation:
   * secret_key = HMAC_SHA256(key = "WebAppData", data = bot_token)
   * data_check_string = key=value pairs sorted alphabetically, joined with "\n"
   */
  public function verifyTelegramInitData(string $initData, string $botToken, ?array &$extractedUser = null): bool
  {
    if (empty($initData) || empty($botToken)) {
      return false;
    }

    parse_str($initData, $params);
    if (empty($params['hash'])) {
      return false;
    }

    // Freshness & anti-replay check: validate auth_date within configurable freshness window
    if (empty($params['auth_date']) || !is_numeric($params['auth_date'])) {
      Log::warning('Telegram initData verification failed: missing or non-numeric auth_date.');
      return false;
    }
    $authTimestamp = (int)$params['auth_date'];
    $currentTime = time();
    $maxAgeSeconds = (int)(config('services.telegram.auth_date_max_age') ?: env('TELEGRAM_AUTH_DATE_MAX_AGE', 900));
    if (($currentTime - $authTimestamp) > $maxAgeSeconds || $authTimestamp > ($currentTime + 60)) {
      Log::warning("Telegram initData verification failed: auth_date expired or future timestamp (auth: {$authTimestamp}, current: {$currentTime}).");
      return false;
    }

    $hash = $params['hash'];
    unset($params['hash']);

    // Sort parameters alphabetically by key
    ksort($params);

    // Build data-check-string (key=value separated by \n)
    $dataCheckArr = [];
    foreach ($params as $k => $v) {
      $dataCheckArr[] = "{$k}={$v}";
    }
    $dataCheckString = implode("\n", $dataCheckArr);

    // HMAC-SHA256 of botToken with key "WebAppData"
    $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
    $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

    if (!hash_equals($calculatedHash, $hash)) {
      return false;
    }

    if (!empty($params['user'])) {
      $userObj = json_decode($params['user'], true);
      if (is_array($userObj)) {
        $extractedUser = $userObj;
      }
    }

    return true;
  }

  /**
   * Parse human or standard time slots into start_time and end_time (H:i:s).
   */
  private function parseTimeSlot(?string $slot): array
  {
    if (!$slot) {
      return [null, null];
    }
    $slotLower = strtolower(trim($slot));
    if ($slotLower === 'morning') {
      return ['09:00:00', '12:00:00'];
    }
    if ($slotLower === 'afternoon') {
      return ['14:00:00', '17:00:00'];
    }
    if (preg_match('/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', $slot, $matches)) {
      $start = date('H:i:s', strtotime($matches[1]));
      $end = date('H:i:s', strtotime($matches[2]));
      return [$start, $end];
    }
    $parsed = strtotime($slot);
    if ($parsed !== false) {
      return [date('H:i:s', $parsed), null];
    }
    return [null, null];
  }

  private function telegramApi(string $method, array $params = []): array
  {
    if (empty($this->botToken)) {
      Log::info("Telegram API call simulated [{$method}]: " . json_encode($params));
      return ['ok' => true, 'simulated' => true];
    }

    $url = "https://api.telegram.org/bot{$this->botToken}/{$method}";
    try {
      $response = Http::timeout(4)
        ->connectTimeout(2)
        ->post($url, $params);

      $data = $response->json();
      if (!($data['ok'] ?? false)) {
        Log::warning("Telegram API non-ok [{$method}]: " . $response->body());
      }
      return $data ?? [];
    } catch (\Throwable $e) {
      Log::error("Telegram API exception [{$method}]: " . $e->getMessage());
      return ['ok' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Safely dispatch non-critical post-booking side effects (SMS, Admin alert, audit logs)
   * in the background after the HTTP 200 response returns to the client/Telegram in <2 seconds.
   */
  private function dispatchPostBookingSideEffects(Order $order, string $channel = 'telegram'): void
  {
    dispatch(function () use ($order, $channel) {
      // 1. Customer confirmation SMS via SmsService
      try {
        SmsService::sendBookingConfirmation($order);
      } catch (\Throwable $th) {
        Log::warning("[{$channel}] Post-booking SMS failed safely: " . $th->getMessage());
      }

      // 2. Admin group / reception alert on Telegram
      try {
        $adminChatId = null;
        if (class_exists(WebsiteSetting::class)) {
          $adminChatId = WebsiteSetting::getVal('telegram_admin_chat_id');
        }
        $adminChatId = $adminChatId ?: (config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID'));

        if (!empty($adminChatId) && !empty($this->botToken)) {
          $order->loadMissing(['customer', 'items']);
          $custName = $order->customer ? $order->customer->full_name : 'ደንበኛ';
          $custPhone = $order->customer ? $order->customer->phone : 'ያልታወቀ';
          $total = number_format((float)($order->total_amount ?: $order->total), 2);
          $adminText = "<b>አዲስ የፅዳት ቀጠሮ ተመዝግቧል!</b>\n\n" .
            "<b>ትዕዛዝ #:</b> <code>{$order->order_number}</code>\n" .
            "<b>ደንበኛ:</b> {$custName}\n" .
            "<b>ስልክ:</b> <code>{$custPhone}</code>\n" .
            "<b>አድራሻ:</b> {$order->address} " . ($order->subcity ? "({$order->subcity})" : "") . "\n" .
            "<b>ቀን:</b> " . ($order->appointment_date ? (is_string($order->appointment_date) ? $order->appointment_date : $order->appointment_date->format('Y-m-d')) : 'ያልተገለጸ') . " ({$order->appointment_time_slot})\n" .
            "<b>ጠቅላላ ዋጋ:</b> {$total} ብር\n" .
            "<b>የትዕዛዝ ምንጭ:</b> {$order->source}\n\n" .
            "እባክዎ በሪሴፕሽን ዳሽቦርድ ያረጋግጡ!";

          $this->telegramApi('sendMessage', [
            'chat_id' => $adminChatId,
            'text' => $adminText,
            'parse_mode' => 'HTML',
          ]);
        }
      } catch (\Throwable $th) {
        Log::warning("[{$channel}] Post-booking admin notification failed safely: " . $th->getMessage());
      }
    })->afterResponse();
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

    $msg = " <b>ደረጃ 1/5፡ የሚፈልጉትን የፅዳት አገልግሎት ይምረጡ</b>\n" .
      "<i>(Step 1/5: Choose Cleaning Service)</i>\n\n" .
      "እባክዎን ከታች ካሉት አገልግሎቶች አንዱን ይጫኑ:";

    $dbServices = Service::where('is_active', true)->orderBy('sort_order')->get();
    $buttons = [];
    if ($dbServices->isNotEmpty()) {
      foreach ($dbServices as $s) {
        $icon = $s->icon ?: '';
        $name = $s->name_am ?: $s->name_en;
        $price = number_format((float)$s->base_price);
        $unit = $s->unit ?: 'ስራ';
        $buttons[] = [
          ['text' => "{$icon} {$name} ({$price} ብር/{$unit})", 'callback_data' => 'book_svc:' . $s->code],
        ];
      }
    } else {
      $buttons = [
        [['text' => ' የሶፋ ጥልቅ ፅዳት (350 ብር/ወንበር)', 'callback_data' => 'book_svc:sofa']],
        [['text' => ' የምንጣፍ እጥበት (80 ብር/ካሬ)', 'callback_data' => 'book_svc:carpet']],
        [['text' => ' የፍራሽ ሳኒታይዜሽን (600 ብር/ፍራሽ)', 'callback_data' => 'book_svc:mattress']],
        [['text' => ' የመስታወት እና ህንፃ ፅዳት (70 ብር/ካሬ)', 'callback_data' => 'book_svc:glass']],
        [['text' => ' የመኖሪያ ቤት ሙሉ ጥልቅ ፅዳት (ከ 2,500 ብር)', 'callback_data' => 'book_svc:home']],
        [['text' => ' የቢሮ እና ተቋማት ፅዳት', 'callback_data' => 'book_svc:office']],
      ];
    }
    $buttons[] = [
      ['text' => ' ሰርዝ | Cancel', 'callback_data' => 'book_cancel'],
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
        'text' => " <b>የቀጠሮ ምዝገባው ተሰርዟል::</b>\n\nእንደገና ለመጀመር /book ወይም /start ብለው መጀመር ይችላሉ::",
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
      $namePrompt = " <b>ደረጃ 2/5፡ ሙሉ ስምዎን ያስገቡ:</b>\n" .
        "<i>(Step 2/5: Enter Your Full Name)</i>\n\n" .
        "የተመረጠ አገልግሎት: <b>{$serviceNameAm}</b>\n\n" .
        ($tgName ? "ከታች ያለውን ቁልፍ በመጫን የቴሌግራም ስምዎን መጠቀም ወይም ስምዎን መጻፍ ይችላሉ:" : "እባክዎን ሙሉ ስምዎን ጽፈው ይላኩልን:");

      $buttons = [];
      if ($tgName) {
        $buttons[] = [['text' => " በቴሌግራም ስሜ ቀጥል: {$tgName}", 'callback_data' => 'book_use_tg_name']];
      }
      $buttons[] = [['text' => ' ሰርዝ | Cancel', 'callback_data' => 'book_cancel']];

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

      $msg = " <b>የተመረጠ ክፍለ ከተማ፡ {$subcity}</b>\n\n" .
        "እባክዎን የሰፈር ስም፣ ልዩ መለያ ቦታ ወይም የቤት ቁጥር ጽፈው ይላኩልን:\n" .
        "<i>(ምሳሌ፡ ቦሌ ሚካኤል፣ ታክሲ ተራ አካባቢ ወይም ህንፃ ቁጥር)</i>\n\n" .
        " ተጨማሪ ዝርዝር መጻፍ ካልፈለጉ ከስር ያለውን <b>' እንደዚሁ ይለፍ'</b> የሚለውን ቁልፍ ይጫኑ:";

      $this->telegramApi('sendMessage', [
        'chat_id' => $chatId,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
          'inline_keyboard' => [
            [['text' => ' እንደዚሁ ይለፍ (Skip Detail)', 'callback_data' => 'book_skip_detail_address']],
            [['text' => ' ሰርዝ | Cancel', 'callback_data' => 'book_cancel']],
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
            'text' => " <b>እባክዎን ትክክለኛ የኢትዮጵያ ስልክ ቁጥር ያስገቡ:</b>\n\nለምሳሌ: <code>0911223344</code> ወይም <code>0711223344</code>",
            'parse_mode' => 'HTML',
          ]);
          return true;
        }

        $payload['customer_phone'] = $phone;
        $tgUser->phone_number = $phone;
        $tgUser->payload_cache = $payload;
        $tgUser->bot_state = 'booking_location';
        $tgUser->save();

        // ደንበኛውን ወዲያውኑ ወደ Customer እንመዝግበው!
        try {
          $custName = $payload['customer_name'] ?? trim(($tgUser->first_name ?? '') . ' ' . ($tgUser->last_name ?? '')) ?: 'የቴሌግራም ደንበኛ';
          Customer::updateOrCreate(
            ['phone' => $phone],
            [
              'customer_code' => Customer::generateNextCode(),
              'full_name' => $custName,
              'address' => 'አዲስ አበባ',
              'telegram_user_id' => (string)$chatId,
              'marketing_consent' => true,
              'consent_timestamp' => now(),
            ]
          );
        } catch (\Throwable $th) {
          Log::warning("Could not auto-register Customer from phone prompt: " . $th->getMessage());
        }

        $this->promptForLocation($chatId, $payload);
        return true;

      case 'booking_location':
        if ($location) {
          $payload['address'] = "አዲስ አበባ";
          $tgUser->payload_cache = $payload;
          $tgUser->bot_state = 'booking_date';
          $tgUser->save();
          $this->promptForDate($chatId, $payload);
          return true;
        } elseif (!empty($text)) {
          if ($text === ' አድራሻዬን እጽፋለሁ (Type Manually)') {
            $this->telegramApi('sendMessage', [
              'chat_id' => $chatId,
              'text' => " <b>እባክዎን አድራሻዎን ጽፈው ይላኩልን:</b>\n\n(ምሳሌ፡ <i>ቦሌ ሚካኤል፣ ታክሲ ተራ አካባቢ፣ የቤት ቁ. 412</i>)",
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
    $msg = " <b>ደረጃ 3/5፡ እባክዎን ስልክ ቁጥርዎን ያስገቡ:</b>\n" .
      "<i>(Step 3/5: Enter Your Phone Number)</i>\n\n" .
      "ስም: <b>{$customerName}</b>\n\n" .
      "ለምሳሌ፡ <code>0912345678</code> ወይም <code>0712345678</code> ጽፈው ይላኩ\n\n" .
      "ወይም ከስር ያለውን <b>' ስልክ ቁጥሬን አጋራ'</b> የሚለውን ቁልፍ ይጫኑ:";

    $keyboard = [
      'keyboard' => [
        [
          ['text' => ' ስልክ ቁጥሬን በቴሌግራም አጋራ (Share Phone)', 'request_contact' => true],
        ],
        [
          ['text' => ' ሰርዝ (Cancel)'],
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

    $msg = "<b>ደረጃ 4/5፡ አድራሻዎን ይምረጡ ወይም ይጻፉ (Address):</b>\n\n" .
      "ደንበኛ: <b>{$name}</b> ({$phone})\n\n" .
      "ፅዳቱ የሚከናወንበትን ክፍለ ከተማ ከስር ካሉት ቁልፎች ይምረጡ ወይም የሰፈር/ጎዳና አድራሻዎን ጽፈው ይላኩልን (ለምሳሌ፡ <i>አያት ተስፉ ሞል ፊት ለፊት</i>)::";

    $replyKeyboard = [
      'keyboard' => [
        [
          ['text' => ' ቦሌ (Bole)'],
          ['text' => ' ቂርቆስ (Kirkos)'],
        ],
        [
          ['text' => ' የካ (Yeka)'],
          ['text' => ' ለሚኩራ (Lemi Kura)'],
        ],
        [
          ['text' => ' ንፋስ ስልክ (Nifas Silk)'],
          ['text' => ' አራዳ (Arada)'],
        ],
        [
          ['text' => ' ሰርዝ (Cancel)'],
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

    $configuredSubcities = WebsiteSetting::getJson('subcities', []);
    $activeSubcities = array_filter($configuredSubcities, fn($s) => !isset($s['active']) || !empty($s['active']));
    if (empty($activeSubcities)) {
      $activeSubcities = [
        ['id' => 'bole', 'name_am' => 'ቦሌ', 'name_en' => 'Bole'],
        ['id' => 'lemi_kura', 'name_am' => 'ለሚኩራ', 'name_en' => 'Lemi Kura'],
        ['id' => 'yeka', 'name_am' => 'የካ', 'name_en' => 'Yeka'],
        ['id' => 'kirkos', 'name_am' => 'ቂርቆስ', 'name_en' => 'Kirkos'],
        ['id' => 'nifas_silk', 'name_am' => 'ንፋስ ስልክ', 'name_en' => 'Nifas Silk'],
        ['id' => 'kolfe', 'name_am' => 'ኮልፌ', 'name_en' => 'Kolfe'],
        ['id' => 'arada', 'name_am' => 'አራዳ', 'name_en' => 'Arada'],
        ['id' => 'gulele', 'name_am' => 'ጉለሌ', 'name_en' => 'Gulele'],
        ['id' => 'akaki_kality', 'name_am' => 'አቃቂ', 'name_en' => 'Akaki'],
        ['id' => 'lideta', 'name_am' => 'ልደታ', 'name_en' => 'Lideta'],
      ];
    }

    $inlineRows = [];
    $currentRow = [];
    foreach ($activeSubcities as $s) {
      $sEn = $s['name_en'] ?? ($s['name_am'] ?? 'Addis');
      $sAm = $s['name_am'] ?? $sEn;
      $currentRow[] = ['text' => " {$sAm} ({$sEn})", 'callback_data' => "book_subcity:{$sEn}"];
      if (count($currentRow) >= 2) {
        $inlineRows[] = $currentRow;
        $currentRow = [];
      }
    }
    if (!empty($currentRow)) {
      $inlineRows[] = $currentRow;
    }
    $inlineRows[] = [
      ['text' => ' ሰርዝ | Cancel', 'callback_data' => 'book_cancel'],
    ];

    $subcityButtons = ['inline_keyboard' => $inlineRows];

    $this->telegramApi('sendMessage', [
      'chat_id' => $chatId,
      'text' => " <b>በቤትዎ ካልሆኑ ክፍለ ከተማዎን ይምረጡ:</b>",
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
      // GPS link removed for clean text address
    }

    $this->telegramApi('sendMessage', [
      'chat_id' => $chatId,
      'text' => " <b>የተመዘገበ አድራሻ:</b> {$locText} ",
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
      ['text' => " ዛሬ | Today ({$d0Am} {$d0Format})", 'callback_data' => "book_date:{$d0Ymd}"],
      ['text' => " ነገ | Tomorrow ({$d1Am} {$d1Format})", 'callback_data' => "book_date:{$d1Ymd}"],
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
      ['text' => " ከነገ ወዲያ ({$d2Am} {$d2Format})", 'callback_data' => "book_date:{$d2Ymd}"],
      ['text' => " {$d3Am} ({$d3->format('l')} {$d3Format})", 'callback_data' => "book_date:{$d3Ymd}"],
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
      ['text' => " {$d4Am} ({$d4->format('l')} {$d4Format})", 'callback_data' => "book_date:{$d4Ymd}"],
      ['text' => " {$d5Am} ({$d5->format('l')} {$d5Format})", 'callback_data' => "book_date:{$d5Ymd}"],
    ];

    // Day 6 & Cancel
    $d6 = now()->addDays(6);
    $d6Ymd = $d6->format('Y-m-d');
    $d6Am = $dayNames[$d6->dayOfWeek]['am'];
    $d6Format = $d6->format('M j');

    $buttons[] = [
      ['text' => " {$d6Am} ({$d6->format('l')} {$d6Format})", 'callback_data' => "book_date:{$d6Ymd}"],
      ['text' => ' ሰርዝ | Cancel', 'callback_data' => 'book_cancel'],
    ];

    $msg = " <b>ደረጃ 5/5፡ የቀጠሮ ቀን እና ሰዓት ይምረጡ:</b>\n" .
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

    $msg = " <b>የቀጠሮ ሰዓት ይምረጡ:</b>\n" .
      "<i>(Choose Time Slot)</i>\n\n" .
      "ቀን: <b>{$apptDate} ({$ethDate['formatted_am']})</b>\n\n" .
      "የሚመችዎትን ሰዓት ይጫኑ:";

    $configuredTimeSlots = WebsiteSetting::getJson('time_slots', []);
    $activeSlots = array_filter($configuredTimeSlots, fn($ts) => !isset($ts['active']) || !empty($ts['active']));
    if (empty($activeSlots)) {
      $activeSlots = [
        ['id' => 'morning', 'name_am' => 'ጠዋት (2:00 - 6:00 ሰዓት / Morning)'],
        ['id' => 'afternoon', 'name_am' => 'ከሰዓት (7:00 - 11:00 ሰዓት / Afternoon)'],
        ['id' => 'full_day', 'name_am' => 'ሙሉ ቀን / በማንኛውም ሰዓት (Flexible)'],
      ];
    }

    $inlineTimeRows = [];
    foreach ($activeSlots as $slot) {
      $slotId = $slot['id'] ?? 'morning';
      $slotLabel = $slot['name_am'] ?? ($slot['slot'] ?? $slotId);
      $inlineTimeRows[] = [
        ['text' => " {$slotLabel}", 'callback_data' => "book_time:{$slotId}"],
      ];
    }
    $inlineTimeRows[] = [
      ['text' => ' ሰርዝ | Cancel', 'callback_data' => 'book_cancel'],
    ];

    $timeButtons = ['inline_keyboard' => $inlineTimeRows];

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

    $locLine = " <b>አድራሻ:</b> {$address}";
    if ($subcity && !str_contains($address, $subcity)) {
      $locLine .= " ({$subcity})";
    }

    $msg = " <b>የትዕዛዝዎ ማጠቃለያ (Booking Summary):</b>\n\n" .
      " <b>ደንበኛ:</b> {$name}\n" .
      " <b>ስልክ:</b> {$phone}\n" .
      " <b>አገልግሎት:</b> {$svcName} ({$qty} መጠን)\n" .
      "{$locLine}\n" .
      " <b>የቀጠሮ ቀን:</b> {$apptDate} ({$ethDate['formatted_am']})\n" .
      " <b>የቀጠሮ ሰዓት:</b> {$timeSlotAm}\n" .
      " <b>ጠቅላላ ክፍያ / ዋጋ:</b> " . number_format($estTotal, 2) . " ብር\n\n" .
      "ትዕዛዙ ትክክል ከሆነ ከታች ያለውን <b>'ትዕዛዙን አረጋግጥ'</b> የሚለውን ቁልፍ ይጫኑ:";

    $buttons = [
      'inline_keyboard' => [
        [
          ['text' => ' ትዕዛዙን አረጋግጥ | Confirm Booking', 'callback_data' => 'book_confirm'],
        ],
        [
          ['text' => ' ሰርዝ | Cancel', 'callback_data' => 'book_cancel'],
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
        'text' => " ይቅርታ፣ የቀጠሮ መረጃው አልተገኘም:: እባክዎን /book ብለው እንደገና ይጀምሩ::",
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
    $unitPrice = $service ? (float)$service->base_price : (float)($payload['unit_price'] ?? 0);
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

    // 5. Dispatch non-critical side effects (SMS, Admin alert) after response to guarantee sub-2s response
    $this->dispatchPostBookingSideEffects($order, 'telegram_bot_wizard');

    // 6. Send Rich Confirmation Card
    $totalEst = (float)$order->total;
    $ethAppt = EthiopianCalendarService::toEthiopian($order->appointment_date);
    $timeSlotAm = match($timeSlot) {
      'morning' => 'ጠዋት (ከ 2:00 - 6:00)',
      'afternoon' => 'ከሰዓት (ከ 7:00 - 11:00)',
      default => 'በማንኛውም ሰዓት',
    };

    $receptionPhone = WebsiteSetting::getVal('contact_phone_1', '0970075509');
    $telebirrAcc = WebsiteSetting::getVal('telebirr_account_number', '0970075509');
    $cbeAcc = WebsiteSetting::getVal('cbe_account_number', '1000552467891');

    $confirmMsg = " <b>እናመሰግናለን {$name}! የቀጠሮ ጥያቄዎ በተሳካ ሁኔታ ተመዝግቧል!</b>\n\n" .
      " <b>የትዕዛዝ ቁጥር:</b> <code>{$order->order_number}</code>\n" .
      " <b>ደንበኛ:</b> {$name}\n" .
      " <b>ስልክ:</b> {$phone}\n" .
      " <b>አገልግሎት:</b> {$svcName}\n" .
      " <b>አድራሻ:</b> {$address}" . ($subcity ? " ({$subcity})" : "") . "\n" .
      " <b>የቀጠሮ ቀን:</b> {$order->appointment_date->format('Y-m-d')} ({$ethAppt['formatted_am']}) {$timeSlotAm}\n" .
      " <b>ጠቅላላ ክፍያ / ዋጋ:</b> " . number_format($totalEst, 2) . " ብር\n\n" .
      " <b>የክፍያ አማራጮች:</b>\n" .
      "• <b>ቴሌብር:</b> <code>{$telebirrAcc}</code>\n" .
      "• <b>ንግድ ባንክ (CBE):</b> <code>{$cbeAcc}</code>\n" .
      "• ወይም በቦታው በጥሬ ገንዘብ (Cash)\n\n" .
      " <b>የማረጋገጫ አጭር የጽሁፍ መልዕክት (SMS) ወደ ስልክዎ ({$phone}) ተልኳል::</b>\n" .
      "የሜሽ ሪሴፕሽን ቡድን በቅርቡ በስልክ ({$receptionPhone}) ደውሎ ያረጋግጥልዎታል:: ስለመረጡን እናመሰግናለን! ";

    $actionButtons = [];
    if ($lat && $lng) {
      // Google maps button removed
    }
    $actionButtons[] = [
      ['text' => ' የትዕዛዝ ሁኔታ | Status', 'callback_data' => 'menu_status'],
      ['text' => " ሪሴፕሽን ({$receptionPhone})", 'callback_data' => 'menu_contact'],
    ];
    $actionButtons[] = [
      ['text' => ' ዋና ማውጫ | Main Menu', 'callback_data' => 'menu_start'],
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
        'text' => " <b>የላኩት መረጃ በትክክል አልደረሰም::</b> እባክዎ እንደገና ይሞክሩ::",
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
    if (!empty($data['payment_method'])) {
      $rawPayMethod = $data['payment_method'];
      $payMethod = ($rawPayMethod === 'cbe_birr') ? 'cbe' : $rawPayMethod;
      $payLabels = ['cash' => 'ጥሬ ገንዘብ (Cash)', 'telebirr' => 'ቴሌብር (Telebirr)', 'cbe' => 'የኢትዮጵያ ንግድ ባንክ (CBE)', 'bank_transfer' => 'የባንክ ዝውውር (Bank Transfer)'];
      $notes = ($notes ? $notes . ' | ' : '') . 'የተመረጠ የክፍያ መንገድ: ' . ($payLabels[$payMethod] ?? $payMethod);
    }
    $items = $data['items'] ?? [];

    if (empty($phone)) {
      $this->telegramApi('sendMessage', [
        'chat_id' => $chatId,
        'text' => " <b>እባክዎ ትክክለኛ ስልክ ቁጥር ያስገቡ::</b>",
        'parse_mode' => 'HTML',
      ]);
      return;
    }

    if (!is_array($items) || count($items) === 0) {
      $this->telegramApi('sendMessage', [
        'chat_id' => $chatId,
        'text' => " <b>እባክዎ ቢያንስ አንድ አገልግሎት ይምረጡ::</b>",
        'parse_mode' => 'HTML',
      ]);
      return;
    }

    // Resolve all services first so a request cannot create an order with a
    // client-controlled or unknown price.
    $catalogItems = [];
    foreach ($items as $item) {
      $service = $this->resolveMiniAppService(is_array($item) ? $item : []);
      if (!$service) {
        $this->telegramApi('sendMessage', [
          'chat_id' => $chatId,
          'text' => " <b>የተመረጠው አገልግሎት አልተገኘም::</b> እባክዎ ሚኒ አፑን አድስና እንደገና ይሞክሩ::",
          'parse_mode' => 'HTML',
        ]);
        return;
      }
      $catalogItems[] = [
        'service' => $service,
        'quantity' => max(0.1, (float)($item['quantity'] ?? 1)),
      ];
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
        'customer_code' => Customer::generateNextCode(),
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
    foreach ($catalogItems as $catalogItem) {
      $svc = $catalogItem['service'];
      $qty = $catalogItem['quantity'];
      $unitPrice = (float)$svc->base_price;
      $subtotal = round($qty * $unitPrice, 2);
      $calculatedTotal += $subtotal;
      OrderItem::create([
        'order_id' => $order->id,
        'service_id' => $svc->id,
        'item_name' => $svc->name_am ?: $svc->name_en,
        'quantity' => $qty,
        'unit_price' => $unitPrice,
        'subtotal' => $subtotal,
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

    // 5. Dispatch non-critical side effects (SMS, Admin alert) after response to guarantee sub-2s response
    $this->dispatchPostBookingSideEffects($order, 'telegram_miniapp_web_app');

    // 6. Send Rich Confirmation Card
    $totalEst = $calculatedTotal;
    $ethAppt = EthiopianCalendarService::toEthiopian($order->appointment_date);

    $confirmMsg = " <b>እናመሰግናለን {$customerName}! የቀጠሮ ጥያቄዎ በሚኒ አፕ በተሳካ ሁኔታ ተመዝግቧል!</b>\n\n" .
      " <b>የትዕዛዝ ቁጥር:</b> <code>{$order->order_number}</code>\n" .
      " <b>ደንበኛ:</b> {$customerName}\n" .
      " <b>ስልክ:</b> {$phone}\n" .
      " <b>አድራሻ:</b> {$address}" . ($subcity ? " ({$subcity})" : "") . "\n" .
      " <b>የቀጠሮ ቀን:</b> " . (is_string($order->appointment_date) ? $order->appointment_date : $order->appointment_date->format('Y-m-d')) . " ({$ethAppt['formatted_am']}) {$timeSlot}\n" .
      " <b>ጠቅላላ ክፍያ / ዋጋ:</b> " . number_format($totalEst, 2) . " ብር\n\n" .
      " <b>የማረጋገጫ አጭር የጽሁፍ መልዕክት (SMS) ወደ ስልክዎ ({$phone}) ተልኳል::</b>\n" .
      "የሜሽ ሪሴፕሽን ቡድን በቅርቡ በስልክ (" . $this->getPhone1() . ") ደውሎ ያረጋግጥልዎታል:: ስለመረጡን እናመሰግናለን! ";

    $actionButtons = [];
    if ($lat && $lng) {
      // Google maps button removed
    }
    $actionButtons[] = [
      ['text' => ' የትዕዛዝ ሁኔታ | Status', 'callback_data' => 'menu_status'],
      ['text' => ' ሪሴፕሽን (' . $this->getPhone1() . ')', 'callback_data' => 'menu_contact'],
    ];
    $actionButtons[] = [
      ['text' => ' ዋና ማውጫ | Main Menu', 'callback_data' => 'menu_start'],
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
    } catch (\Throwable $e) {
      \Illuminate\Support\Facades\Log::warning('Failed to update Telegram user phone: ' . $e->getMessage());
    }
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

    $msg = " <b>ውድ {$name}፣ የቀጠሮ ማስተላለፍ ማሳወቂያ!</b>\n" .
      "━━━━━━━━━━━━━━━━━━━━\n" .
      " <b>የትዕዛዝ ቁጥር:</b> <code>{$orderNo}</code>\n" .
      " <b>የተላለፈበት ምክንያት:</b> {$reason}\n" .
      " <b>አዲሱ የቀጠሮ ቀን:</b> {$newDate} ({$ethDateStr})\n" .
      " <b>ሰዓት:</b> {$timeSlotAm}\n" .
      "━━━━━━━━━━━━━━━━━━━━\n" .
      "ለተፈጠረው መስተጓጎል ከልብ ይቅርታ እንጠይቃለን! የሜሽ የፅዳት ቡድን በተጠቀሰው አዲስ ሰዓት የሚገኝ ይሆናል::\n\n" .
      " <b>ለማንኛውም ጥያቄ ወይም ሰዓት ለማስተካከል:</b> " . $this->getPhone1();

    $resp = $this->telegramApi('sendMessage', [
      'chat_id' => $chatId,
      'text' => $msg,
      'parse_mode' => 'HTML',
      'reply_markup' => json_encode([
        'inline_keyboard' => [
          [
            ['text' => ' የትዕዛዝ ሁኔታ | Status', 'callback_data' => "track_{$orderNo}"],
            ['text' => ' ሪሴፕሽን (' . $this->getPhone1() . ')', 'callback_data' => 'menu_contact'],
          ],
          [
            ['text' => ' ዋና ማውጫ | Main Menu', 'callback_data' => 'menu_start'],
          ]
        ]
      ])
    ]);

    return ($resp['ok'] ?? false) === true;
  }

  /**
   * Diagnostic: check real-time Telegram Bot & Webhook connection status
   */
  public function getWebhookStatus(): JsonResponse
  {
    $token = $this->botToken;
    if (empty($token)) {
      return response()->json([
        'configured' => false,
        'valid' => false,
        'message' => 'የቴሌግራም ቦት ቶከን (TELEGRAM_BOT_TOKEN) አልተዋቀረም (Not configured).',
      ]);
    }

    $maskedToken = strlen($token) > 12 ? substr($token, 0, 7) . '...' . substr($token, -4) : '******';

    $getMe = $this->telegramApi('getMe');
    if (!($getMe['ok'] ?? false)) {
      return response()->json([
        'configured' => true,
        'masked_token' => $maskedToken,
        'valid' => false,
        'error' => $getMe['description'] ?? 'Unauthorized / Invalid Token',
        'message' => 'ልክ ያልሆነ ወይም የተሰረዘ (Revoked) የቦት ቶከን ነው! አዲስ ቶከን ከ @BotFather ወስደው ያስገቡ::',
        'get_me' => $getMe,
      ]);
    }

    $whInfo = $this->telegramApi('getWebhookInfo');
    $wh = $whInfo['result'] ?? [];
    $isWebhookActive = !empty($wh['url'] ?? '');

    return response()->json([
      'configured' => true,
      'masked_token' => $maskedToken,
      'valid' => true,
      'webhook_active' => $isWebhookActive,
      'bot' => $getMe['result'] ?? null,
      'webhook' => $wh,
      'expected_webhook_url' => config('services.telegram.webhook_url') ?: (config('app.url') ? rtrim(config('app.url'), '/') . '/api/telegram/webhook' : url('/api/telegram/webhook')),
    ]);
  }

  /**
   * Register or reset Webhook with Telegram
   */
  public function setRenderWebhook(Request $request): JsonResponse
  {
    if ($request->filled('token')) {
      $newToken = trim($request->input('token'));
      WebsiteSetting::setVal('telegram_bot_token', $newToken, 'telegram');
      $this->botToken = $newToken;
    }

    $token = $this->botToken;
    if (empty($token)) {
      return response()->json([
        'success' => false,
        'message' => 'ቦት ቶከን (Bot Token) አልተገኘም! እባክዎ መጀመሪያ ቶከኑን ያስገቡ።',
      ], 400);
    }

    // Verify token with getMe
    $getMe = $this->telegramApi('getMe');
    if (!($getMe['ok'] ?? false)) {
      return response()->json([
        'success' => false,
        'message' => 'የተሳሳተ ወይም የተሰረዘ (Revoked) የቦት ቶከን ነው! Telegram: ' . ($getMe['description'] ?? 'Unauthorized'),
      ], 400);
    }

    $url = config('services.telegram.webhook_url') ?: (config('app.url') ? rtrim(config('app.url'), '/') . '/api/telegram/webhook' : url('/api/telegram/webhook'));
    $params = [
      'url' => $url,
      'drop_pending_updates' => true,
    ];

    $secret = config('services.telegram.webhook_secret') ?: env('TELEGRAM_WEBHOOK_SECRET');
    if (!empty($secret) && preg_match('/^[A-Za-z0-9_-]{1,256}$/', $secret)) {
      $params['secret_token'] = $secret;
    }

    $resp = $this->telegramApi('setWebhook', $params);

    if ($resp['ok'] ?? false) {
      $botName = $getMe['result']['first_name'] ?? 'Meash Bot';
      $botUser = $getMe['result']['username'] ?? $this->botUsername;
      return response()->json([
        'success' => true,
        'message' => "ቦቱ (@{$botUser}) በተሳካ ሁኔታ ከ Webhook ጋር ተገናኝቷል! አሁን 24 ሰዓት ይሰራል::",
        'bot' => $getMe['result'] ?? null,
        'webhook_url' => $url,
      ]);
    }

    return response()->json([
      'success' => false,
      'message' => 'ቴሌግራም ዌብሁክ ማገናኘት አልቻለም፡ ' . ($resp['description'] ?? json_encode($resp)),
    ], 400);
  }

  /**
   * Send a test Telegram alert to verify end-to-end delivery
   */
  public function sendTestMessage(Request $request): JsonResponse
  {
    $token = $this->botToken;
    if (empty($token)) {
      return response()->json(['success' => false, 'message' => 'የቦት ቶከን አልተዋቀረም (Bot token not set).'], 400);
    }

    $targetChatId = $request->input('chat_id') ?: (config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID'));
    if (empty($targetChatId)) {
      $latestUser = TelegramUser::latest()->first();
      $targetChatId = $latestUser?->telegram_id;
    }

    if (empty($targetChatId)) {
      return response()->json([
        'success' => false,
        'message' => 'እስካሁን ቦቱን ያናገረ ደንበኛ ወይም የ Admin Chat ID አልተገኘም። እባክዎ መጀመሪያ በቴሌግራም @' . $this->botUsername . ' ላይ /start ብለው ያናግሩት።',
      ], 400);
    }

    $resp = $this->telegramApi('sendMessage', [
      'chat_id' => $targetChatId,
      'text' => " <b>የሜሽ ቴሌግራም ቦት የሙከራ መልዕክት (Test Alert)</b>\n\n ቦቱ ከ Webhook ጋር በትክክል ተገናኝቷል!\n ሰዓት፡ " . now()->toDateTimeString() . "\n MEASH CLEANING SOLUTION",
      'parse_mode' => 'HTML',
    ]);

    if ($resp['ok'] ?? false) {
      return response()->json([
        'success' => true,
        'message' => "የሙከራ መልእክት ለ Chat ID ({$targetChatId}) በተሳካ ሁኔታ ተልኳል!",
      ]);
    }

    return response()->json([
      'success' => false,
      'message' => 'መልእክት መላክ አልተቻለም፡ ' . ($resp['description'] ?? json_encode($resp)),
    ], 400);
  }

  /**
   * Send 5-star interactive review request to customer via Telegram
   */
  public static function sendOrderReviewRequest(Order $order): bool
  {
    try {
      $order->loadMissing('customer');
      $customer = $order->customer;
      if (!$customer) {
        return false;
      }

      $chatId = $customer->telegram_user_id ?: null;

      // If no direct telegram_user_id on customer, try finding by phone in telegram_users
      if (!$chatId && !empty($customer->phone)) {
        $cleanPhone = SmsService::normalizeEthiopianPhone($customer->phone);
        $localPhone = str_starts_with($cleanPhone, '251') ? '0' . substr($cleanPhone, 3) : $cleanPhone;
        $chatId = TelegramUser::where('phone_number', $cleanPhone)
          ->orWhere('phone_number', $localPhone)
          ->orWhere('phone_number', '+' . $cleanPhone)
          ->value('telegram_id');
      }

      if (!$chatId) {
        return false;
      }

      $instance = app(self::class);
      $text = "<b>ክቡር ደንበኛችን</b>፣ የሜአሽ ጽዳት አገልግሎታችን እንዴት ነበር?\n\n" .
        "የትዕዛዝ ቁጥር: <code>{$order->order_number}</code>\n\n" .
        "እባክዎ ከ1 እስከ 5 ኮከብ በመስጠት ደረጃ ይስጡን፦";

      $keyboard = [
        'inline_keyboard' => [
          [
            ['text' => '1 ኮከብ', 'callback_data' => "rate_order:{$order->id}:1"],
            ['text' => '2 ኮከብ', 'callback_data' => "rate_order:{$order->id}:2"],
            ['text' => '3 ኮከብ', 'callback_data' => "rate_order:{$order->id}:3"],
            ['text' => '4 ኮከብ', 'callback_data' => "rate_order:{$order->id}:4"],
            ['text' => '5 ኮከብ', 'callback_data' => "rate_order:{$order->id}:5"],
          ]
        ]
      ];

      $res = $instance->telegramApi('sendMessage', [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode($keyboard),
      ]);

      return !empty($res['ok']);
    } catch (\Throwable $th) {
      Log::warning("Telegram review request failed for order #{$order->id}: " . $th->getMessage());
      return false;
    }
  }
}
