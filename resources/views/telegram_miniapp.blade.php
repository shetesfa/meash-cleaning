<!DOCTYPE html>
@php
  $activeServices = collect();
  $websiteSettings = [];
  $subcities = [];
  $timeSlots = [];
  $pricingRules = [];
  try {
    if (\Illuminate\Support\Facades\Schema::hasTable('services')) {
      $activeServices = \App\Models\Service::where('is_active', true)->orderBy('sort_order')->get();
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('website_settings')) {
      $websiteSettings = \App\Models\WebsiteSetting::getAllKeyValue();
      $subcities = \App\Models\WebsiteSetting::getJson('subcities', []);
      $timeSlots = \App\Models\WebsiteSetting::getJson('time_slots', []);
      $pricingRules = \App\Models\WebsiteSetting::getJson('pricing_rules', []);
    }
  } catch (\Throwable $e) {
    // Graceful fallback
  }

  if (empty($subcities)) {
    $subcities = [
      ['name_am' => 'ቦሌ', 'name_en' => 'Bole', 'key' => 'Bole'],
      ['name_am' => 'የካ', 'name_en' => 'Yeka', 'key' => 'Yeka'],
      ['name_am' => 'ቂርቆስ', 'name_en' => 'Kirkos', 'key' => 'Kirkos'],
      ['name_am' => 'ልደታ', 'name_en' => 'Lideta', 'key' => 'Lideta'],
      ['name_am' => 'ንፋስ ስልክ ላፍቶ', 'name_en' => 'Nifas Silk', 'key' => 'Nifas Silk'],
      ['name_am' => 'ኮልፌ ቀራኒዮ', 'name_en' => 'Kolfe', 'key' => 'Kolfe'],
      ['name_am' => 'አራዳ', 'name_en' => 'Arada', 'key' => 'Arada'],
      ['name_am' => 'ጉለሌ', 'name_en' => 'Gulele', 'key' => 'Gulele'],
      ['name_am' => 'አዲስ ከተማ', 'name_en' => 'Addis Ketema', 'key' => 'Addis Ketema'],
      ['name_am' => 'አቃቂ ቃሊቲ', 'name_en' => 'Akaki', 'key' => 'Akaki'],
      ['name_am' => 'ለሚ ኩራ', 'name_en' => 'Lemi Kura', 'key' => 'Lemi Kura'],
    ];
  }

  if (empty($timeSlots)) {
    $timeSlots = [
      ['key' => 'morning', 'label_am' => 'ጥዋት (ከ 2:00 - 6:00)', 'label_en' => 'Morning (8:00 AM - 12:00 PM)'],
      ['key' => 'afternoon', 'label_am' => 'ከሰዓት (ከ 7:00 - 11:00)', 'label_en' => 'Afternoon (1:00 PM - 5:00 PM)'],
      ['key' => 'night', 'label_am' => 'የሌሊት ፈረቃ (ከ 1:00 - 11:00)', 'label_en' => 'Night Shift (7:00 PM - 5:00 AM)'],
      ['key' => 'weekend', 'label_am' => 'ቅዳሜና እሁድ (እንደ ፍላጎትዎ)', 'label_en' => 'Weekend Shift (Flexible Hours)'],
    ];
  }

  $weeklyDisc = (int)($pricingRules['weekly_discount_percent'] ?? 15);
  $biweeklyDisc = (int)($pricingRules['biweekly_discount_percent'] ?? 10);
  $monthlyDisc = (int)($pricingRules['monthly_discount_percent'] ?? 5);

  $telebirrNo = $websiteSettings['telebirr_account_number'] ?? '0970075509';
  $telebirrName = $websiteSettings['telebirr_account_name'] ?? 'ሜሽ የጽዳት አገልግሎት / MEASH CLEANING';
  $cbeNo = $websiteSettings['cbe_account_number'] ?? '1000123456789';
  $cbeName = $websiteSettings['cbe_account_name'] ?? 'ሜሽ የጽዳት አገልግሎት / MEASH CLEANING';
  $contactPhone = $websiteSettings['contact_phone_1'] ?? '0970075509';
  $contactPhone2 = $websiteSettings['contact_phone_2'] ?? '0998142190';
  $companyName = $websiteSettings['company_name_am'] ?? ($websiteSettings['company_name'] ?? 'ሜሽ የጽዳት አገልግሎት');
  $companySlogan = 'Your Environment, Our Responsibility.';
  $addressAm = $websiteSettings['company_address_am'] ?? ($websiteSettings['office_address_am'] ?? 'አያት አደባባይ፣ አዲስ አበባ');
@endphp
<html lang="am">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>MEASH CLEANING SOLUTION | Telegram Mini App</title>
    <!-- Telegram WebApp SDK (With Offline Safety) -->
  <script src="https://telegram.org/js/telegram-web-app.js"></script>
  <script>
    // Offline safety if Telegram SDK is unreachable
    if (typeof window.Telegram === 'undefined' || !window.Telegram.WebApp) {
      window.Telegram = {
        WebApp: {
          ready: function(){},
          expand: function(){},
          close: function(){},
          MainButton: { setText: function(){}, show: function(){}, hide: function(){}, onClick: function(){} },
          BackButton: { show: function(){}, hide: function(){}, onClick: function(){} },
          initDataUnsafe: {},
          colorScheme: 'dark',
          themeParams: { bg_color: '#0F172A', text_color: '#F8FAFC' }
        }
      };
      console.warn('Telegram WebApp SDK not reachable (offline mode active)');
    }
  </script>

  <!-- Fonts & Minified Compiled CSS -->
  <link rel="stylesheet" href="/css/fonts.css">
  <link rel="stylesheet" href="/css/meash.css">

  <!-- Self-Hosted Vendor Libraries -->
  <script src="/vendor/lucide.min.js"></script>

  <!-- Safety Fallbacks -->
  <script>
    if (typeof lucide === 'undefined') {
      window.lucide = { createIcons: function(){} };
    }
  </script>
  <style>
    body {
      font-family: 'Noto Sans Ethiopic', 'Plus Jakarta Sans', sans-serif;
      background-color: var(--tg-theme-bg-color, #0F172A);
      color: var(--tg-theme-text-color, #F8FAFC);
    }
    .tma-svc-btn.active {
      border-color: #22d3ee !important;
      background-color: rgba(6, 182, 212, 0.25) !important;
      box-shadow: 0 0 20px rgba(6, 182, 212, 0.4) !important;
      transform: scale(1.02);
    }
  </style>
</head>
<body class="p-4 pb-20">
  <div class="max-w-md mx-auto">
    <!-- Header & Language Toggle -->
    <div class="flex items-center justify-between gap-3 mb-5 p-3.5 rounded-2xl bg-slate-800/90 border border-slate-700/80 shadow-lg">
      <div class="flex items-center gap-3">
        <img src="/logo.jpg" alt="Meash Cleaning Logo" class="w-12 h-12 rounded-xl object-cover border border-cyan-500/40 shadow-md shadow-cyan-500/20 shrink-0">
        <div>
          <h1 class="text-sm font-extrabold text-white tracking-tight" id="txt-app-title">MEASH CLEANING SOLUTION</h1>
          <p class="text-[10px] text-cyan-300 font-bold tracking-tight">"Your Environment, Our Responsibility."</p>
          <p class="text-[10px] text-slate-400 font-semibold" id="tg-user-greeting">እንኳን ደህና መጡ </p>
        </div>
      </div>
      <!-- Language Pill Switcher -->
      <div class="flex items-center bg-slate-900/80 rounded-xl p-1 border border-slate-700 shrink-0">
        <button type="button" onclick="setTmaLang('am')" id="btn-lang-am" class="px-2.5 py-1 text-xs font-extrabold rounded-lg bg-cyan-600 text-white transition-all">አማ</button>
        <button type="button" onclick="setTmaLang('en')" id="btn-lang-en" class="px-2.5 py-1 text-xs font-bold rounded-lg text-slate-400 hover:text-white transition-all">EN</button>
      </div>
    </div>

    <form id="tma-form" onsubmit="submitTmaBooking(event)" class="space-y-4">
      <!-- 1. Service Selection -->
      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2" id="lbl-step1">1. ስራዎቻችንን ይምረጡ (Choose Service)</label>
        <div class="grid grid-cols-2 gap-2" id="tma-services-grid">
          @forelse($activeServices as $index => $svc)
            @php
              $iconMap = [
                'floor_ceramic' => 'sparkles',
                'facilities_general' => 'building-2',
                'kitchen_dining' => 'utensils',
                'post_construction' => 'home',
                'glass' => 'sun',
                'upholstery_carpet' => 'sofa',
                'sofa' => 'sofa',
                'carpet' => 'layers',
                'mattress' => 'bed',
                'residential_deep' => 'home',
                'commercial' => 'building-2',
              ];
              $lucideIcon = $iconMap[$svc->code] ?? 'sparkles';
              $pUnit = $svc->pricing_unit_am ?: ($svc->unit ?: 'ስራ');
              $nameAm = $svc->name_am ?: $svc->name_en;
              $nameEn = $svc->name_en ?: $svc->name_am;
              $priceVal = (float)$svc->base_price;
            @endphp
            <button type="button"
              onclick="selectTmaService({{ $svc->id }}, {{ $priceVal }}, '{{ addslashes($pUnit) }}', '{{ addslashes($nameAm) }}', '{{ addslashes($nameEn) }}', '{{ addslashes($svc->code) }}')"
              class="tma-svc-btn p-3 rounded-xl border relative {{ $index === 0 ? 'active border-cyan-400 bg-cyan-950/70 ring-2 ring-cyan-400/50 shadow-lg shadow-cyan-500/20' : 'border-slate-700 bg-slate-900' }} text-left transition-all cursor-pointer"
              id="tma-svc-btn-{{ $svc->id }}"
              data-service-id="{{ $svc->id }}"
              data-service-code="{{ $svc->code }}">
              <div class="flex items-center justify-between mb-1">
                <i data-lucide="{{ $lucideIcon }}" class="w-5 h-5 text-cyan-400"></i>
                <span class="tma-svc-check w-4 h-4 rounded-full bg-cyan-500 text-slate-950 text-[10px] font-black flex items-center justify-center {{ $index === 0 ? '' : 'hidden' }}">✓</span>
              </div>
              <span class="text-xs font-bold text-white block svc-title">{{ $nameAm }}</span>
              <span class="text-[10px] text-cyan-400 font-semibold svc-price">{{ number_format($priceVal) }} ብር / {{ $pUnit }}</span>
            </button>
          @empty
            <div class="col-span-2 text-center text-xs text-slate-400 py-4">ስራዎቻችን በመጫን ላይ ናቸው...</div>
          @endforelse
        </div>
      </div>

      <!-- 2. Quantity Counter -->
      <div class="p-3.5 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-between">
        <div>
          <span class="text-xs font-bold text-white block" id="tma-selected-service-name">{{ $activeServices->first()?->name_am ?? 'የሶፋ እና ምንጣፍ ጥልቅ እንክብካቤ' }}</span>
          <span class="text-[11px] text-slate-400 font-semibold" id="tma-unit-desc">{{ strtoupper($activeServices->first()?->unit ?? 'seat') }}</span>
        </div>
        <div class="flex items-center gap-3">
          <button type="button" onclick="changeQty(-1)" class="w-9 h-9 rounded-xl bg-slate-700 hover:bg-slate-600 text-white font-bold text-lg flex items-center justify-center active:scale-95 transition-all">-</button>
          <span id="tma-qty" class="text-base font-extrabold text-white w-8 text-center">5</span>
          <button type="button" onclick="changeQty(1)" class="w-9 h-9 rounded-xl bg-slate-700 hover:bg-slate-600 text-white font-bold text-lg flex items-center justify-center active:scale-95 transition-all">+</button>
        </div>
      </div>

      <!-- Total Preview -->
      <div class="p-3.5 rounded-xl bg-gradient-to-r from-cyan-950/80 to-blue-950/80 border border-cyan-500/40 flex justify-between items-center shadow-md">
        <span class="text-xs font-semibold text-slate-300" id="lbl-total-est">ጠቅላላ ክፍያ / ዋጋ:</span>
        <span id="tma-total-preview" class="text-base font-extrabold text-cyan-300">{{ number_format(($activeServices->first()?->base_price ?? 350) * 5) }} ብር</span>
      </div>

      <!-- 3. Address & Location -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider" id="lbl-step2">2. አድራሻ በአዲስ አበባ (Location)</label>
          </div>
        <input type="hidden" id="tma-lat" value="">
        <input type="hidden" id="tma-lng" value="">
        <select id="tma-subcity" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500 mb-2">
          <option value="" id="opt-select-subcity">ክፍለ ከተማ ይምረጡ...</option>
          @foreach($subcities as $sc)
            <option value="{{ $sc['key'] ?? $sc['name_en'] }}">{{ $sc['name_am'] }} ({{ $sc['name_en'] }})</option>
          @endforeach
        </select>
        <input type="text" id="tma-address" required placeholder="የሰፈር ስም፣ ልዩ መለያ እና የቤት ቁጥር..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500">
      </div>

      <!-- 4. Schedule -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2" id="lbl-date">3. የቀጠሮ ቀን</label>
          <input type="date" id="tma-date" required onchange="updateTmaEthDateHint(this.value)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500">
          <p id="tma-eth-date-hint" class="text-[10px] text-cyan-400 font-bold mt-1"> — ዓ.ም</p>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2" id="lbl-slot">የቀጠሮ ሰዓት</label>
          <select id="tma-slot" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500">
            @foreach($timeSlots as $ts)
              @php
                $slotKey = $ts['key'] ?? $ts['id'] ?? $ts['slot'] ?? 'morning';
                $slotAm = $ts['label_am'] ?? $ts['name_am'] ?? $slotKey;
                $slotEn = $ts['label_en'] ?? $ts['name_en'] ?? $slotKey;
              @endphp
              <option value="{{ $slotKey }}" id="opt-{{ $slotKey }}">{{ $slotAm }} ({{ $slotEn }})</option>
            @endforeach
          </select>
        </div>
      </div>

      <!-- Recurring Subscription Plan in Mini App -->
      <div class="p-3.5 rounded-xl bg-slate-800/90 border border-cyan-500/30">
        <label class="block text-xs font-bold text-cyan-300 uppercase tracking-wider mb-2"> የፅዳት ድግግሞሽ እና የቅናሽ እቅድ</label>
        <div class="grid grid-cols-2 gap-2 text-xs">
          <label class="p-2 rounded-lg bg-slate-900 border border-slate-700 flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="tma_sub_plan" value="one_time" checked onchange="updateTotal()" class="text-cyan-500">
            <div>
              <span class="font-bold text-white block text-[11px]">የአንድ ጊዜ</span>
              <span class="text-[9px] text-slate-400">መደበኛ</span>
            </div>
          </label>
          <label class="p-2 rounded-lg bg-slate-900 border border-slate-700 flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="tma_sub_plan" value="weekly" onchange="updateTotal()" class="text-cyan-500">
            <div>
              <span class="font-bold text-white block text-[11px]">በየሳምንቱ</span>
              <span class="text-[9px] text-cyan-400 font-extrabold" id="tma-disc-weekly">{{ $weeklyDisc }}% ቅናሽ</span>
            </div>
          </label>
          <label class="p-2 rounded-lg bg-slate-900 border border-slate-700 flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="tma_sub_plan" value="biweekly" onchange="updateTotal()" class="text-cyan-500">
            <div>
              <span class="font-bold text-white block text-[11px]">በ2 ሳምንት</span>
              <span class="text-[9px] text-cyan-400 font-extrabold" id="tma-disc-biweekly">{{ $biweeklyDisc }}% ቅናሽ</span>
            </div>
          </label>
          <label class="p-2 rounded-lg bg-slate-900 border border-slate-700 flex items-center gap-1.5 cursor-pointer">
            <input type="radio" name="tma_sub_plan" value="monthly" onchange="updateTotal()" class="text-cyan-500">
            <div>
              <span class="font-bold text-white block text-[11px]">በየወሩ</span>
              <span class="text-[9px] text-cyan-400 font-extrabold" id="tma-disc-monthly">{{ $monthlyDisc }}% ቅናሽ</span>
            </div>
          </label>
        </div>
      </div>

      <!-- 5. Contact Phone -->
      <div>
        <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2" id="lbl-step3">4. የእርስዎ መረጃ (Contact Details)</label>
        <input type="text" id="tma-name" required placeholder="ሙሉ ስምዎ" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500 mb-2">
        <input type="tel" id="tma-phone" required placeholder="ስልክ ቁጥር (ለምሳሌ 0970075509)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500">
      </div>

      <button type="submit" id="tma-submit-btn" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-bold text-sm shadow-xl shadow-cyan-500/25 flex items-center justify-center gap-2 active:scale-98 transition-all">
        <i data-lucide="check" class="w-4 h-4"></i>
        <span id="txt-submit-btn">ቀጠሮውን አረጋግጥ (Submit Booking)</span>
      </button>
      <div id="tma-form-error" class="hidden p-3 rounded-xl bg-red-950/80 border border-red-500/40 text-red-200 text-xs text-center font-bold"></div>
    </form>

    <!-- Confirmation State -->
    <div id="tma-success" class="hidden text-center py-8">
      <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto mb-4 border border-emerald-500/30 shadow-lg shadow-emerald-500/10">
        <i data-lucide="check-circle-2" class="w-10 h-10"></i>
      </div>
      <h3 class="text-xl font-black text-white mb-2" id="txt-success-title"> ቀጠሮዎ በተሳካ ሁኔታ ተመዝግቧል!</h3>
      <p class="text-xs text-slate-300 mb-5 leading-relaxed" id="txt-success-desc">የሜሽ ሪሴፕሽን ሰራተኞቻችን የቡድን ምደባውን በስልክ ደውለው ያረጋግጡልዎታል::</p>
      <div class="p-4 rounded-2xl bg-slate-800/90 border border-slate-700 mb-4 shadow-md">
        <span class="text-[11px] font-semibold text-slate-400 block mb-1" id="lbl-tracking-id">የትዕዛዝ መለያ ቁጥር (Order ID)</span>
        <span class="text-2xl font-black text-cyan-400 tracking-wider" id="tma-result-id">ORD-2026-0001</span>
      </div>

      <!-- Payment Notice -->
      <div id="tma-payment-notice" class="p-3.5 rounded-2xl bg-slate-900/90 border border-cyan-500/30 mb-5 text-left text-xs">
        <div class="flex items-center gap-2 mb-2">
          <i data-lucide="info" class="w-4 h-4 text-cyan-400"></i>
          <span class="text-cyan-400 font-bold">የክፍያ ሁኔታ (Payment Policy)</span>
        </div>
        <div class="space-y-1.5 text-slate-300 text-[11px] leading-relaxed">
          <p>• <strong>ምንም ዓይነት ቅድመ ክፍያ አይጠየቅም!</strong></p>
          <p>• ክፍያ የሚፈጸመው ስራው ሙሉ ለሙሉ ተጠናቆ በባለሙያዎቻችን ሲረከቡ ብቻ ነው።</p>
          <p>• <strong>ክፍያዎን በጥሬ ገንዘብ (Cash)፣ በቴሌብር (Telebirr)፣ ወይም በሚመችዎት የኢትዮጵያ ባንክ</strong> (CBE፣ አዋሽ፣ አቢሲንያ ወዘተ) በሞባይል ባንኪንግ መክፈል ይችላሉ።</p>
        </div>
      </div>



      <div class="space-y-2 mb-2">
        <button type="button" onclick="tmaShowBookingForm()" class="w-full py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white text-xs font-black rounded-xl shadow-lg shadow-cyan-500/25 transition-all cursor-pointer flex items-center justify-center gap-2">
          <i data-lucide="plus-circle" class="w-4 h-4"></i>
          <span id="txt-book-another">አዲስ ቀጠሮ ያዙ (Book Another Cleaning)</span>
        </button>
        <button onclick="if(window.Telegram?.WebApp?.close){window.Telegram.WebApp.close();}else{window.location.href='https://t.me/meash_cleaning_solution_bot';}" class="w-full py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold rounded-xl transition-all cursor-pointer" id="btn-close-app">ዝጋ (Close)</button>
      </div>
    </div>

    <p class="text-center text-[11px] text-slate-500 pt-3 border-t border-slate-800/80">
      ኦፊሴላዊ ቦት: <span class="text-cyan-400 font-bold">{{ $websiteSettings['telegram_bot_username'] ?? '@meash_cleaning_solution_bot' }}</span> | ቻናል: <a href="{{ $websiteSettings['telegram_channel'] ?? 'https://t.me/meashdeepcleanning' }}" target="_blank" class="text-cyan-400 font-bold hover:underline">{{ str_replace('https://t.me/', '@', $websiteSettings['telegram_channel'] ?? '@meashdeepcleanning') }}</a> | ቀጥታ ለማነጋገር: <a href="{{ $websiteSettings['telegram_support_url'] ?? 'https://t.me/meashcleaning' }}" target="_blank" class="text-cyan-400 font-bold hover:underline">{{ $websiteSettings['telegram_support'] ?? '@meashcleaning' }}</a><br>
      ስልክ: <a href="tel:{{ $contactPhone }}" class="text-slate-300 font-bold hover:underline">{{ $contactPhone }}</a> @if(!empty($contactPhone2)) / <a href="tel:{{ $contactPhone2 }}" class="text-slate-300 font-bold hover:underline">{{ $contactPhone2 }}</a> @endif | {{ $addressAm }}
    </p>
  </div>

  <script>
    const initialLiveServices = @json($activeServices);
    let currentLang = localStorage.getItem('tma_lang') || 'am';
    let currentSvc = initialLiveServices.length > 0 ? {
      id: Number(initialLiveServices[0].id),
      code: initialLiveServices[0].code,
      price: parseFloat(initialLiveServices[0].base_price || 0),
      unit: initialLiveServices[0].pricing_unit_am || initialLiveServices[0].unit || 'መቀመጫ',
      nameAm: initialLiveServices[0].name_am || initialLiveServices[0].name_en,
      nameEn: initialLiveServices[0].name_en || initialLiveServices[0].name_am
    } : { id: 1, code: 'floor_ceramic', price: 500, unit: 'ካሬ / sqm', nameAm: 'የሴራሚክ እና የእንጨት ወለሎች ጥልቅ ጽዳት', nameEn: 'Ceramic & Hardwood Floor Cleaning' };
    let qty = (currentSvc.code === 'carpet' || currentSvc.id === 2) ? 10 : ((currentSvc.code === 'sofa' || currentSvc.id === 1) ? 5 : 1);
    let tgUser = null;

    const i18nDict = {
      am: {
        appTitle: 'MEASH CLEANING SOLUTION',
        greetingDefault: 'እንኳን ደህና መጡ ',
        step1: '1. ስራዎቻችንን ይምረጡ',
        step2: '2. አድራሻ በአዲስ አበባ',
        step3: '4. የእርስዎ መረጃ',
        stepDate: '3. የቀጠሮ ቀን',
        stepSlot: 'የቀጠሮ ሰዓት እና ፈረቃ',
        selectSubcity: 'ክፍለ ከተማ ይምረጡ...',
        addressPlaceholder: 'የሰፈር ስም፣ ልዩ መለያ እና የቤት ቁጥር...',
        namePlaceholder: 'ሙሉ ስምዎ',
        phonePlaceholder: 'ስልክ ቁጥር (ለምሳሌ 0970075509)',
        totalEst: 'ጠቅላላ ክፍያ / ዋጋ:',
        submitBtn: 'ቀጠሮውን አረጋግጥ (Submit Booking)',
        submitting: 'በመመዝገብ ላይ...',
        successTitle: ' ቀጠሮዎ በተሳካ ሁኔታ ተመዝግቧል!',
        successDesc: 'የሜሽ ሪሴፕሽን ሰራተኞቻችን የቡድን ምደባውን በስልክ ደውለው ያረጋግጡልዎታል::',
        trackingId: 'የትዕዛዝ መለያ ቁጥር (Order ID)',
        closeApp: 'ዝጋ (Close)',
        currency: 'ብር',
        morning: 'ጥዋት (ከ 2:00 - 6:00)',
        afternoon: 'ከሰዓት (ከ 7:00 - 11:00)',
        night: 'የሌሊት ፈረቃ (ከ 1:00 - 11:00)',
        weekend: 'ቅዳሜና እሁድ (እንደ ፍላጎትዎ)',
      },
      en: {
        appTitle: 'MEASH CLEANING SOLUTION',
        greetingDefault: 'Welcome to Meash ',
        step1: '1. Choose Service',
        step2: '2. Location in Addis Ababa',
        step3: '4. Your Contact Details',
        stepDate: '3. Appointment Date',
        stepSlot: 'Time Slot & Shift',
        selectSubcity: 'Select Subcity...',
        addressPlaceholder: 'Specific address, landmark & house number...',
        namePlaceholder: 'Your Full Name',
        phonePlaceholder: 'Phone Number (e.g. 0970075509)',
        totalEst: 'Estimated Total:',
        submitBtn: 'Confirm & Book Now',
        submitting: 'Submitting Booking...',
        successTitle: ' Booking Confirmed!',
        successDesc: 'Our receptionist will call you shortly to confirm team dispatch.',
        trackingId: 'Tracking / Order ID',
        closeApp: 'Close Mini App',
        currency: 'ETB',
        morning: 'Morning (8:00 AM - 12:00 PM)',
        afternoon: 'Afternoon (1:00 PM - 5:00 PM)',
        night: 'Night Shift (7:00 PM - 5:00 AM)',
        weekend: 'Weekend Shift (Flexible Hours)',
      }
    };

    const EC = {
      months: ['መስከረም','ጥቅምት','ህዳር','ታህሳስ','ጥር','የካቲት','መጋቢት','ሚያዚያ','ግንቦት','ሰኔ','ሐምሌ','ነሐሴ','ጳጉሜ'],
      toEth(gcDate) {
        const d = new Date(gcDate);
        const gcY = d.getFullYear(), gcM = d.getMonth() + 1, gcD = d.getDate();
        const a = Math.floor((14 - gcM) / 12);
        const y = gcY + 4800 - a;
        const m = gcM + 12 * a - 3;
        const jdn = gcD + Math.floor((153*m+2)/5) + 365*y + Math.floor(y/4) - Math.floor(y/100) + Math.floor(y/400) - 32045;
        const r = (jdn - 1723856) % 1461;
        const n = r % 365 + 365 * Math.floor(r / 1460);
        const etY = 4 * Math.floor((jdn - 1723856) / 1461) + Math.floor(r / 365) - Math.floor(r / 1460);
        const etM = Math.floor(n / 30) + 1;
        const etD = n % 30 + 1;
        return { year: etY, month: etM, day: etD };
      },
      formatEth(gcDate) {
        try {
          const e = this.toEth(gcDate);
          return `${e.day} ${this.months[e.month-1]} ${e.year} ዓ.ም`;
        } catch { return gcDate || '—'; }
      }
    };

    function updateTmaEthDateHint(val) {
      const el = document.getElementById('tma-eth-date-hint');
      if (el && val) {
        el.innerText = ' ' + EC.formatEth(val);
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      lucide.createIcons();

      // Initialize Telegram WebApp
      if (window.Telegram?.WebApp) {
        const tg = window.Telegram.WebApp;
        tg.ready();
        tg.expand();
        tgUser = tg.initDataUnsafe?.user;

        if (tgUser) {
          const welcomeName = tgUser.first_name || 'ደንበኛችን';
          document.getElementById('tg-user-greeting').innerText = currentLang === 'am' ? `ሰላም ${welcomeName} ` : `Hello ${welcomeName} `;
          document.getElementById('tma-name').value = `${tgUser.first_name || ''} ${tgUser.last_name || ''}`.trim();
        }
      }

      // Check URL query parameter (e.g. ?service=carpet or ?service=2)
      const urlParams = new URLSearchParams(window.location.search);
      const svcParam = urlParams.get('service');
      if (svcParam && initialLiveServices.length > 0) {
        const found = initialLiveServices.find(s => String(s.id) === svcParam || s.code === svcParam);
        if (found) {
          selectTmaService(found.id, parseFloat(found.base_price || 0), found.pricing_unit_am || found.unit || 'ስራ', found.name_am || found.name_en, found.name_en || found.name_am, found.code);
        }
      }

      // Default date tomorrow
      const tomorrow = new Date();
      tomorrow.setDate(tomorrow.getDate() + 1);
      const tomorrowIso = tomorrow.toISOString().split('T')[0];
      document.getElementById('tma-date').value = tomorrowIso;
      document.getElementById('tma-date').min = new Date().toISOString().split('T')[0];
      updateTmaEthDateHint(tomorrowIso);

      setTmaLang(currentLang);
      updateTotal();
      loadTmaLiveServices();
      loadTmaLiveSettings();
    });

    async function loadTmaLiveSettings() {
      try {
        const res = await fetch('/api/public/settings');
        const data = await res.json();
        if (data && data.pricing_rules) {
          const pr = data.pricing_rules;
          if (pr.weekly_discount_percent !== undefined) tmaSubDiscounts.weekly = parseInt(pr.weekly_discount_percent);
          if (pr.biweekly_discount_percent !== undefined) tmaSubDiscounts.biweekly = parseInt(pr.biweekly_discount_percent);
          if (pr.monthly_discount_percent !== undefined) tmaSubDiscounts.monthly = parseInt(pr.monthly_discount_percent);
          
          const elW = document.getElementById('tma-disc-weekly');
          const elB = document.getElementById('tma-disc-biweekly');
          const elM = document.getElementById('tma-disc-monthly');
          if (elW) elW.innerText = `${tmaSubDiscounts.weekly}% ቅናሽ`;
          if (elB) elB.innerText = `${tmaSubDiscounts.biweekly}% ቅናሽ`;
          if (elM) elM.innerText = `${tmaSubDiscounts.monthly}% ቅናሽ`;
          updateTotal();
        }
      } catch(err) {
        console.warn('Could not load live settings in TMA', err);
      }
    }

    async function loadTmaLiveServices() {
      try {
        const res = await fetch('/api/public/services');
        const data = await res.json();
        const list = Array.isArray(data) ? data : (data.data || []);
        if (list.length > 0) {
          const grid = document.getElementById('tma-services-grid');
          if (grid) {
            const lucideIconMap = {
              floor_ceramic: 'sparkles',
              facilities_general: 'building-2',
              kitchen_dining: 'utensils',
              post_construction: 'home',
              glass: 'sun',
              upholstery_carpet: 'sofa',
              sofa: 'sofa',
              carpet: 'layers',
              mattress: 'bed',
              residential_deep: 'home',
              commercial: 'building-2'
            };
            grid.innerHTML = list.map((svc, i) => {
              const price = parseFloat(svc.base_price || 0);
              const unit = svc.pricing_unit_am || svc.pricing_unit || 'አገልግሎት';
              const nameAm = svc.name_am || svc.name_en;
              const nameEn = svc.name_en || svc.name_am;
              const iconName = lucideIconMap[svc.code] || 'sparkles';
              const isSelected = String(currentSvc.id) === String(svc.id) || (!currentSvc.id && i === 0);
              if (isSelected) {
                currentSvc = { id: Number(svc.id), code: svc.code, price, unit, nameAm, nameEn };
                const nameEl = document.getElementById('tma-selected-service-name');
                if (nameEl) nameEl.innerText = currentLang === 'am' ? nameAm : nameEn;
                const unitEl = document.getElementById('tma-unit-desc');
                if (unitEl) unitEl.innerText = unit.toUpperCase();
              }
              return `
                <button type="button"
                  onclick="selectTmaService(${svc.id}, ${price}, '${unit.replace(/'/g, "\\'")}', '${nameAm.replace(/'/g, "\\'")}', '${nameEn.replace(/'/g, "\\'")}', '${(svc.code || '').replace(/'/g, "\\'")}')"
                  class="tma-svc-btn p-3 rounded-xl border relative cursor-pointer ${isSelected ? 'active border-cyan-400 bg-cyan-950/70 ring-2 ring-cyan-400/50 shadow-lg shadow-cyan-500/20' : 'border-slate-700 bg-slate-900'} text-left transition-all"
                  id="tma-svc-btn-${svc.id}"
                  data-service-id="${svc.id}"
                  data-service-code="${svc.code || ''}">
                  <div class="flex items-center justify-between mb-1">
                    <i data-lucide="${iconName}" class="w-5 h-5 text-cyan-400"></i>
                    <span class="tma-svc-check w-4 h-4 rounded-full bg-cyan-500 text-slate-950 text-[10px] font-black flex items-center justify-center ${isSelected ? '' : 'hidden'}">✓</span>
                  </div>
                  <span class="text-xs font-bold text-white block svc-title">${nameAm}</span>
                  <span class="text-[10px] text-cyan-400 font-semibold svc-price">${price.toLocaleString()} ብር / ${unit}</span>
                </button>
              `;
            }).join('');
            updateTotal();
            if (window.lucide) lucide.createIcons();
          }
        }
      } catch(err) {
        console.warn('Could not load live services in TMA', err);
      }
    }

    function setTmaLang(lang) {
      currentLang = lang;
      localStorage.setItem('tma_lang', lang);

      const dict = i18nDict[lang];
      document.getElementById('txt-app-title').innerText = dict.appTitle;
      document.getElementById('lbl-step1').innerText = dict.step1;
      document.getElementById('lbl-step2').innerText = dict.step2;
      document.getElementById('lbl-step3').innerText = dict.step3;
      document.getElementById('lbl-date').innerText = dict.stepDate;
      document.getElementById('lbl-slot').innerText = dict.stepSlot;
      document.getElementById('opt-select-subcity').innerText = dict.selectSubcity;
      document.getElementById('tma-address').placeholder = dict.addressPlaceholder;
      document.getElementById('tma-name').placeholder = dict.namePlaceholder;
      document.getElementById('tma-phone').placeholder = dict.phonePlaceholder;
      document.getElementById('lbl-total-est').innerText = dict.totalEst;
      document.getElementById('txt-submit-btn').innerText = dict.submitBtn;
      if (document.getElementById('opt-morning')) document.getElementById('opt-morning').innerText = dict.morning;
      if (document.getElementById('opt-afternoon')) document.getElementById('opt-afternoon').innerText = dict.afternoon;
      if (document.getElementById('opt-night')) document.getElementById('opt-night').innerText = dict.night;
      if (document.getElementById('opt-weekend')) document.getElementById('opt-weekend').innerText = dict.weekend;

      document.getElementById('txt-success-title').innerText = dict.successTitle;
      document.getElementById('txt-success-desc').innerText = dict.successDesc;
      document.getElementById('lbl-tracking-id').innerText = dict.trackingId;
      document.getElementById('btn-close-app').innerText = dict.closeApp;

      // Highlight active button
      if (lang === 'am') {
        document.getElementById('btn-lang-am').className = 'px-2.5 py-1 text-xs font-extrabold rounded-lg bg-cyan-600 text-white transition-all';
        document.getElementById('btn-lang-en').className = 'px-2.5 py-1 text-xs font-bold rounded-lg text-slate-400 hover:text-white transition-all';
      } else {
        document.getElementById('btn-lang-en').className = 'px-2.5 py-1 text-xs font-extrabold rounded-lg bg-cyan-600 text-white transition-all';
        document.getElementById('btn-lang-am').className = 'px-2.5 py-1 text-xs font-bold rounded-lg text-slate-400 hover:text-white transition-all';
      }

      document.getElementById('tma-selected-service-name').innerText = lang === 'am' ? currentSvc.nameAm : currentSvc.nameEn;
      updateTotal();
    }

    function selectTmaService(idOrCode, price, unit, nameAm, nameEn, code) {
      const numericId = Number.isInteger(Number(idOrCode)) ? Number(idOrCode) : (initialLiveServices.find(s => s.code === idOrCode)?.id || null);
      const finalCode = code || (numericId ? (initialLiveServices.find(s => s.id === numericId)?.code || '') : String(idOrCode));
      currentSvc = { id: numericId, code: finalCode, price: Number(price), unit: unit || 'ስራ', nameAm, nameEn };
      
      // Smart default quantity per service type
      const unitStr = String(unit || '').toLowerCase();
      if (finalCode === 'floor_ceramic' || finalCode === 'glass' || unitStr.includes('ካሬ') || unitStr.includes('sqm')) {
        qty = 20;
      } else if (finalCode === 'sofa' || finalCode === 'upholstery_carpet' || unitStr.includes('መቀመጫ') || unitStr.includes('seat')) {
        qty = 5;
      } else if (finalCode === 'carpet') {
        qty = 10;
      } else {
        qty = 1;
      }

      const qtyEl = document.getElementById('tma-qty');
      if (qtyEl) qtyEl.innerText = qty;

      const nameEl = document.getElementById('tma-selected-service-name');
      if (nameEl) nameEl.innerText = currentLang === 'am' ? nameAm : nameEn;

      const unitEl = document.getElementById('tma-unit-desc');
      if (unitEl) unitEl.innerText = (unit || 'ስራ').toUpperCase();

      document.querySelectorAll('.tma-svc-btn').forEach(btn => {
        btn.classList.remove('active', 'border-cyan-400', 'bg-cyan-950/70', 'ring-2', 'ring-cyan-400/50', 'shadow-lg', 'shadow-cyan-500/20');
        btn.classList.add('border-slate-700', 'bg-slate-900');
        const checkEl = btn.querySelector('.tma-svc-check');
        if (checkEl) checkEl.classList.add('hidden');

        const bId = btn.getAttribute('data-service-id');
        const bCode = btn.getAttribute('data-service-code');
        if ((numericId && String(bId) === String(numericId)) || (finalCode && bCode === finalCode)) {
          btn.classList.remove('border-slate-700', 'bg-slate-900');
          btn.classList.add('active', 'border-cyan-400', 'bg-cyan-950/70', 'ring-2', 'ring-cyan-400/50', 'shadow-lg', 'shadow-cyan-500/20');
          if (checkEl) checkEl.classList.remove('hidden');
        }
      });

      updateTotal();
    }

    function changeQty(delta) {
      qty = Math.max(1, qty + delta);
      document.getElementById('tma-qty').innerText = qty;
      updateTotal();
    }

    let tmaSubDiscounts = { 
      one_time: 0, 
      weekly: {{ $weeklyDisc }}, 
      biweekly: {{ $biweeklyDisc }}, 
      monthly: {{ $monthlyDisc }} 
    };

    function updateTotal() {
      const baseTotal = qty * currentSvc.price;
      const checkedPlan = document.querySelector('input[name="tma_sub_plan"]:checked')?.value || 'one_time';
      const discPercent = tmaSubDiscounts[checkedPlan] || 0;
      const discount = (baseTotal * discPercent) / 100;
      const finalTotal = Math.max(0, baseTotal - discount);

      const cur = currentLang === 'am' ? 'ብር' : 'ETB';
      if (discPercent > 0) {
        document.getElementById('tma-total-preview').innerHTML = `<span class="line-through text-xs text-slate-400 mr-1.5">${baseTotal.toLocaleString()} ${cur}</span> ${finalTotal.toLocaleString()} ${cur}`;
      } else {
        document.getElementById('tma-total-preview').innerText = `${finalTotal.toLocaleString()} ${cur}`;
      }
    }

    function showTmaAlert(message) {
      if (window.Telegram?.WebApp?.showAlert) {
        window.Telegram.WebApp.showAlert(message);
        return;
      }
      const errBox = document.getElementById('tma-form-error');
      if (errBox) {
        errBox.innerText = message;
        errBox.classList.remove('hidden');
        errBox.scrollIntoView({ behavior: 'smooth' });
        setTimeout(() => {
          errBox.classList.add('hidden');
        }, 6000);
      } else {
        alert(message);
      }
    }

    function updateTmaPlan() {
      updateTotal();
    }

    function detectTmaLocation() {
      const btn = document.getElementById('btn-tma-gps-txt');
      if (!navigator.geolocation) {
        showTmaAlert('Geolocation is not supported on this device.');
        return;
      }
      btn.innerText = 'በመፈለግ ላይ...';
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const lat = pos.coords.latitude;
          const lng = pos.coords.longitude;
          const acc = Math.round(pos.coords.accuracy || 0);
          document.getElementById('tma-lat').value = lat;
          document.getElementById('tma-lng').value = lng;
          
          const alertBox = document.getElementById('tma-gps-alert');
          alertBox.classList.remove('hidden');
          
          if (acc > 1000) {
            alertBox.className = 'mb-2 p-2.5 rounded-xl bg-amber-950/80 border border-amber-500/40 text-amber-200 text-[11px] flex items-center justify-between';
            document.getElementById('tma-gps-status').innerHTML = `በኔትወርክ የተገኘ መገኛ (±${(acc/1000).toFixed(1)}km): እባክዎ ሰፈርዎን ይምረጡ`;
          } else {
            alertBox.className = 'mb-2 p-2.5 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-200 text-[11px] flex items-center justify-between';
            document.getElementById('tma-gps-status').innerHTML = `ትክክለኛ የሞባይል GPS ተገኝቷል (±${acc}m)`;
          }

          document.getElementById('tma-gps-link').href = `https://www.google.com/maps?q=${lat},${lng}`;
          btn.innerText = 'ተገኝቷል';
          lucide.createIcons();
        },
        (err) => {
          btn.innerText = 'አድራሻ ይጻፉ';
          showTmaAlert('አድራሻዎን ከታች ባለው ሳጥን ውስጥ ይጻፉ:: (Please type your address).');
        },
        { enableHighAccuracy: true, timeout: 10000 }
      );
    }

    async function submitTmaBooking(e) {
      e.preventDefault();
      const btn = document.getElementById('tma-submit-btn');
      btn.disabled = true;
      btn.innerText = currentLang === 'am' ? 'በመመዝገብ ላይ...' : 'Submitting...';

      const plan = document.querySelector('input[name="tma_sub_plan"]:checked')?.value || 'one_time';
      const payMethod = 'cash';

      const payload = {
        customer_name: document.getElementById('tma-name').value,
        customer_phone: document.getElementById('tma-phone').value,
        subcity: document.getElementById('tma-subcity').value,
        address: document.getElementById('tma-address').value + ` (${document.getElementById('tma-subcity').value})`,
        latitude: document.getElementById('tma-lat')?.value ? parseFloat(document.getElementById('tma-lat').value) : null,
        longitude: document.getElementById('tma-lng')?.value ? parseFloat(document.getElementById('tma-lng').value) : null,
        appointment_date: document.getElementById('tma-date').value,
        appointment_time_slot: document.getElementById('tma-slot').value,
        subscription_plan: plan,
        payment_method: payMethod,
        telegram_user_id: tgUser ? String(tgUser.id) : null,
        init_data: window.Telegram?.WebApp?.initData || '',
        notes: currentLang === 'am' ? 'በቴሌግራም ሚኒ አፕ የተመዘገበ' : 'Booked via Telegram Mini App',
        items: [
          {
            service_id: currentSvc.id ? Number(currentSvc.id) : (Number.isInteger(Number(currentSvc.code)) ? Number(currentSvc.code) : null),
            service_code: String(currentSvc.code || ''),
            item_name: currentLang === 'am' ? currentSvc.nameAm : currentSvc.nameEn,
            quantity: qty,
            unit_price: currentSvc.price,
          }
        ]
      };

      try {
        const res = await fetch('/api/telegram/miniapp/book', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (res.ok) {
          document.getElementById('tma-form').classList.add('hidden');
          document.getElementById('tma-success').classList.remove('hidden');
          document.getElementById('tma-result-id').innerText = data.booking_id;
          lucide.createIcons();
        } else {
          showTmaAlert(data.message || (currentLang === 'am' ? 'ስህተት ተፈጥሯል' : 'Error creating booking'));
        }
      } catch (err) {
        showTmaAlert(currentLang === 'am' ? 'የግንኙነት ችግር ተፈጥሯል' : 'Connection error');
      } finally {
        btn.disabled = false;
        btn.innerText = currentLang === 'am' ? 'ቀጠሮውን አረጋግጥ (Submit Booking)' : 'Confirm & Book Now';
      }
    }

    // Return to TMA booking form
    function tmaShowBookingForm() {
      const formEl = document.getElementById('tma-form');
      const succEl = document.getElementById('tma-success');
      const portalEl = document.getElementById('tma-customer-portal');
      if (succEl) succEl.classList.add('hidden');
      if (portalEl) portalEl.classList.add('hidden');
      if (formEl) {
        formEl.classList.remove('hidden');
        formEl.scrollIntoView({ behavior: 'smooth' });
      }
    }

    // Pre-fill TMA booking form for next cleaning
    function tmaBookNextCleaning(name, phone, subcity) {
      const nameEl = document.getElementById('tma-name');
      const phoneEl = document.getElementById('tma-phone');
      const subcityEl = document.getElementById('tma-subcity');

      if (nameEl && name) nameEl.value = name;
      if (phoneEl && phone) phoneEl.value = phone;
      if (subcityEl && subcity) {
        for (let opt of subcityEl.options) {
          if (opt.value.toLowerCase().includes(subcity.toLowerCase()) || subcity.toLowerCase().includes(opt.value.toLowerCase())) {
            subcityEl.value = opt.value;
            break;
          }
        }
      }

      tmaShowBookingForm();
    }
  </script>

<script>
 if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
   navigator.serviceWorker.register('/sw.js').then((reg) => {
    console.log('Meash MiniApp ServiceWorker registered:', reg.scope);
   }).catch((err) => {
    console.warn('Meash MiniApp ServiceWorker failed:', err);
   });
  });
 }
</script>
</body>
</html>
