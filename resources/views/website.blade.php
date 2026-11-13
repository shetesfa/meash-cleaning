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
      $websiteSettings = \App\Models\WebsiteSetting::getPublicKeyValue();
      $subcities = \App\Models\WebsiteSetting::getJson('subcities', []);
      $timeSlots = \App\Models\WebsiteSetting::getJson('time_slots', []);
      $pricingRules = \App\Models\WebsiteSetting::getJson('pricing_rules', []);
    }
  } catch (\Throwable $e) {
    // Graceful fallback for pre-migration or isolated test runs
  }
  $phone1 = $websiteSettings['contact_phone_1'] ?? '0970075509';
  $phone2 = $websiteSettings['contact_phone_2'] ?? '0998142190';
  $companyName = $websiteSettings['company_name'] ?? 'MEASH CLEANING SOLUTION';
  $companyNameAm = $websiteSettings['company_name_am'] ?? 'ሜሽ የጽዳት አገልግሎት';
  // STRICTLY IN ENGLISH EVERYWHERE — DO NOT TRANSLATE TO AMHARIC
  $sloganEn = 'Your Environment, Our Responsibility.';
  $sloganAm = 'Your Environment, Our Responsibility.';
  $subtitleEn = $websiteSettings['company_subtitle_en'] ?? 'Premium Commercial Cleaning Solutions — Hotels | Restaurants | Cafés | Offices';
  $subtitleAm = $websiteSettings['company_subtitle_am'] ?? 'ከፍተኛ ጥራት የጠበቀ የንግድ ተቋማት የጽዳት አገልግሎት — ሆቴሎች | ሬስቶራንቶች | ካፌዎች | ቢሮዎች';
  $addressAm = $websiteSettings['company_address_am'] ?? 'አያት አደባባይ፣ አዲስ አበባ';
  $addressEn = $websiteSettings['company_address'] ?? 'Ayat Square, Addis Ababa, Ethiopia';
  $email = $websiteSettings['company_email'] ?? 'meashcleaningsolution@gmail.com';
  $hoursAm = $websiteSettings['working_hours_am'] ?? 'ከሰኞ እስከ ቅዳሜ ከጠዋቱ 2:00 እስከ ምሽቱ 12:00 (እሁድ እና የሌሊት ፈረቃ በቀጠሮ)';
  $hoursEn = $websiteSettings['working_hours_en'] ?? 'Mon - Sat: 8:00 AM - 6:00 PM (Sunday & Night Shift by Appointment)';
  $telebirrNo = $websiteSettings['telebirr_account_number'] ?? '0970075509';
  $cbeNo = $websiteSettings['cbe_account_number'] ?? '1000552467891';
  $boaNo = $websiteSettings['boa_account_number'] ?? '54321678';
  $awashNo = $websiteSettings['awash_account_number'] ?? '01320876543200';
  $tiktokUrl = $websiteSettings['tiktok'] ?? 'https://www.tiktok.com/@meashcleaningsolution';
  $instagramUrl = $websiteSettings['instagram'] ?? 'https://instagram.com/meashcleaningsolution';
  $facebookUrl = $websiteSettings['facebook'] ?? 'https://facebook.com/meashcleaningsolution';
  $socialHandle = $websiteSettings['social_handle'] ?? '@meashcleaningsolution';
@endphp
<html lang="am" data-lang="am" class="dark">
<head>
<script>
  (function() {
    // 1. Disable browser scroll restoration so page always opens at the top header on mobile & desktop
    try {
      if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
      }
      if (!window.location.hash || window.location.hash === '#top' || window.location.hash === '#qr-code' || window.location.hash === '#book') {
        if (window.location.hash === '#qr-code' || window.location.hash === '#book') {
          history.replaceState(null, '', window.location.pathname + window.location.search);
        }
        window.scrollTo(0, 0);
      }
    } catch (e) {}

    // 2. Initialize theme
    try {
      var saved = localStorage.getItem('meash_theme');
      if (saved === 'light') {
        document.documentElement.classList.remove('dark');
        document.documentElement.classList.add('light');
      } else {
        document.documentElement.classList.add('dark');
        document.documentElement.classList.remove('light');
        if (!saved) localStorage.setItem('meash_theme', 'dark');
      }
    } catch (e) {
      document.documentElement.classList.add('dark');
    }
  })();
</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<title>{{ $companyName }} | Best Commercial & Deep Cleaning in Addis Ababa | {{ $companyNameAm }}</title>
<meta name="description" content="{{ $companyName }} ({{ $companyNameAm }}) — {{ $subtitleEn }}. {{ $sloganEn }} Call {{ $phone1 }} / {{ $phone2 }}">
<meta name="keywords" content="Cleaning Services in Addis Ababa, Commercial Cleaning Addis Ababa, Hotel Cleaning Addis Ababa, Restaurant Sanitation, Office Cleaning Addis Ababa, Deep Cleaning Ethiopia, Meash Cleaning Solution, ሜሽ የጽዳት አገልግሎት, የቢሮ ጽዳት, የሆቴል ጽዳት, የወለል እጥበት, የሶፋ እጥበት">
<meta name="author" content="{{ $companyName }}">
<meta name="theme-color" content="#0B1220">
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/assets/icon-192.png">
<link rel="icon" type="image/jpeg" href="/logo.jpg">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Meash">

<!-- Open Graph & Social SEO -->
<meta property="og:title" content="{{ $companyName }} | {{ $sloganEn }}">
<meta property="og:description" content="{{ $subtitleEn }} — {{ $sloganEn }} Call {{ $phone1 }} / {{ $phone2 }}.">
<meta property="og:image" content="/logo.jpg">
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url('/') }}">

<!-- LocalBusiness Schema.org JSON-LD Structured Data for Google Search -->
<script type="application/ld+json">
{!! json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'HomeAndConstructionBusiness',
  'name' => $companyName,
  'alternateName' => $companyNameAm,
  'image' => url('/logo.jpg'),
  'slogan' => $sloganEn,
  'url' => url('/'),
  'telephone' => '+251' . ltrim($phone1, '0+'),
  'email' => $email,
  'priceRange' => 'ETB 60 - ETB 10000',
  'address' => [
    '@type' => 'PostalAddress',
    'streetAddress' => $addressEn,
    'addressLocality' => 'Addis Ababa',
    'addressRegion' => 'Addis Ababa',
    'postalCode' => '1000',
    'addressCountry' => 'ET'
  ],
  'geo' => [
    '@type' => 'GeoCoordinates',
    'latitude' => 9.0227,
    'longitude' => 38.8681
  ],
  'sameAs' => array_values(array_filter([
    $websiteSettings['telegram_channel'] ?? 'https://t.me/meashcleaningsolution',
    $websiteSettings['telegram_support_url'] ?? 'https://t.me/meashcleaning',
    $websiteSettings['telegram_bot'] ?? 'https://t.me/meash_cleaning_solution_bot',
    $tiktokUrl,
    $facebookUrl,
    $instagramUrl,
  ]))
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
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
            console.warn('Lucide icon library not loaded; using fallback');
        }
    </script>
    <style>
  /* =====================================================================
     MEASH — MODERN CSS (drop-in replacement for the old <style> block)
     Markup, JS and PHP are untouched. Same selectors, same behavior.
     ===================================================================== */

  /* ---------- Design tokens ---------- */
  :root {
    color-scheme: dark;

    --bg: #0B1220;
    --bg-soft: #0F172A;
    --surface: #162032;
    --surface-2: #1E293B;
    --line: #23354E;
    --line-soft: #1E293B;

    --text: #F1F5F9;
    --text-strong: #F8FAFC;
    --text-body: #CBD5E1;
    --text-muted: #94A3B8;
    --text-faint: #64748B;

    --brand: #0E7C7B;
    --brand-bright: #2DD4BF;

    --radius-sm: 10px;
    --radius-md: 14px;
    --radius-lg: 20px;

    --ease: cubic-bezier(.22, .61, .36, 1);
    --shadow-card: 0 1px 2px rgba(0,0,0,.35), 0 12px 32px -12px rgba(0,0,0,.55);
    --shadow-lift: 0 2px 4px rgba(0,0,0,.4), 0 24px 48px -16px rgba(0,0,0,.65);
    --ring: 0 0 0 3px rgba(45, 212, 191, .35);
  }

  /* ---------- Base ---------- */
  *, *::before, *::after { box-sizing: border-box; }

  html {
    scroll-padding-top: 5rem;
    -webkit-text-size-adjust: 100%;
    text-size-adjust: 100%;
  }
  html.scroll-smooth {
    scroll-behavior: smooth;
  }

  body {
    font-family: 'Inter', 'Noto Sans Ethiopic', system-ui, -apple-system, 'Segoe UI', sans-serif;
    font-feature-settings: 'cv11', 'ss01';
    background: var(--bg);
    color: var(--text);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: optimizeLegibility;
    transition: background-color .35s var(--ease), color .35s var(--ease);
  }

  .font-serif { font-family: 'Fraunces', 'Noto Sans Ethiopic', Georgia, serif; }

  h1, h2, h3 { text-wrap: balance; letter-spacing: -0.02em; }
  p { text-wrap: pretty; }

  ::selection { background: rgba(45, 212, 191, .35); color: #fff; }

  img { max-width: 100%; height: auto; }

  /* Accessible, modern focus ring */
  :focus-visible {
    outline: none;
    box-shadow: var(--ring);
    border-radius: var(--radius-sm);
  }
  button:focus-visible, a:focus-visible { box-shadow: var(--ring); }

  /* ---------- Scrollbar ---------- */
  html { scrollbar-width: thin; scrollbar-color: #334155 var(--bg); }
  ::-webkit-scrollbar { width: 10px; height: 10px; }
  ::-webkit-scrollbar-track { background: var(--bg); }
  ::-webkit-scrollbar-thumb {
    background: var(--surface-2);
    border-radius: 999px;
    border: 2px solid var(--bg);
  }
  ::-webkit-scrollbar-thumb:hover { background: #334155; }

  /* ---------- Language blocks (unchanged logic) ---------- */
  [data-lang-block] { display: none; }
  html:not([data-lang]) [data-lang-block="am"],
  html[data-lang="am"] [data-lang-block="am"] { display: block; }
  html:not([data-lang]) span[data-lang-block="am"],
  html[data-lang="am"] span[data-lang-block="am"] { display: inline; }
  html[data-lang="en"] [data-lang-block="en"] { display: block; }
  html[data-lang="en"] span[data-lang-block="en"] { display: inline; }
  html[data-lang="both"] [data-lang-block] { display: block; }
  html[data-lang="both"] span[data-lang-block] { display: inline; }
  .lang-both-stack > [data-lang-block="en"] { opacity: .62; font-size: .82em; margin-top: .2em; font-weight: 500; }

  /* ---------- Decorative surfaces ---------- */
  .duo {
    background-color: #08111F !important;
    background-image:
      radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px),
      radial-gradient(120% 80% at 100% 0%, rgba(45, 212, 191, .28) 0%, transparent 55%),
      linear-gradient(160deg, #0A6B6A 0%, #08111F 100%) !important;
    background-size: 14px 14px, auto, auto !important;
    color: #FFFFFF !important;
  }
  .grain {
    background-image: radial-gradient(rgba(255,255,255,.07) 1px, transparent 1px);
    background-size: 14px 14px;
  }
  .duo.grain {
    background-color: #08111F !important;
    background-image:
      radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px),
      radial-gradient(120% 80% at 100% 0%, rgba(45, 212, 191, .28) 0%, transparent 55%),
      linear-gradient(160deg, #0A6B6A 0%, #08111F 100%) !important;
    background-size: 14px 14px, auto, auto !important;
    color: #FFFFFF !important;
  }
  .duo h3,
  .duo p:not(.text-navy *):not(.bg-white *),
  .duo span:not(.text-navy *):not(.bg-white *) {
    color: #FFFFFF !important;
  }
  .duo .text-white\/80 {
    color: rgba(255, 255, 255, .90) !important;
  }

  /* Missing gradient utilities fallback */
  .from-teal {
    --tw-gradient-from: #0E7C7B var(--tw-gradient-from-position);
    --tw-gradient-to: rgba(14, 124, 123, 0) var(--tw-gradient-to-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
  }
  .to-teal-dark {
    --tw-gradient-to: #0A5F5E var(--tw-gradient-to-position);
  }
  .hover\:from-teal-dark:hover {
    --tw-gradient-from: #0A5F5E var(--tw-gradient-from-position);
    --tw-gradient-to: rgba(10, 95, 94, 0) var(--tw-gradient-to-position);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to);
  }
  .hover\:to-teal:hover {
    --tw-gradient-to: #0E7C7B var(--tw-gradient-to-position);
  }
  .bg-gradient-to-r.from-teal.to-teal-dark,
  [class*="from-teal"][class*="to-teal-dark"] {
    background-color: #0E7C7B !important;
    background-image: linear-gradient(to right, #0E7C7B, #0A5F5E) !important;
    color: #FFFFFF !important;
  }
  .bg-gradient-to-r.from-teal.to-teal-dark *,
  [class*="from-teal"][class*="to-teal-dark"] * {
    color: #FFFFFF !important;
  }

  /* Header CTA Booking Button — Default & Dark Mode */
  header a[href="#book"],
  #mobile-nav-drawer a[href="#book"] {
    background: linear-gradient(135deg, #0E7C7B 0%, #0A5F5E 100%) !important;
    background-color: #0E7C7B !important;
    color: #FFFFFF !important;
    border: 1px solid rgba(45, 212, 191, .35) !important;
    box-shadow: 0 4px 14px -2px rgba(14, 124, 123, .45) !important;
  }
  header a[href="#book"]:hover,
  #mobile-nav-drawer a[href="#book"]:hover {
    background: linear-gradient(135deg, #0A5F5E 0%, #08111F 100%) !important;
    background-color: #0A5F5E !important;
    color: #FFFFFF !important;
    box-shadow: 0 6px 18px -2px rgba(10, 95, 94, .55) !important;
  }
  header a[href="#book"] *,
  #mobile-nav-drawer a[href="#book"] * {
    color: #FFFFFF !important;
    font-weight: 800 !important;
  }

  /* ---------- Smooth interactions (all pages elements) ---------- */
  a, button, input, select, textarea, .rounded-xl, .rounded-2xl, .rounded-3xl {
    transition:
      background-color .25s var(--ease),
      border-color .25s var(--ease),
      color .25s var(--ease),
      box-shadow .25s var(--ease),
      transform .25s var(--ease),
      opacity .25s var(--ease);
  }
  button, [role="button"], .cursor-pointer { -webkit-tap-highlight-color: transparent; }
  button:active:not(:disabled) { transform: scale(.97); }
  button:disabled { opacity: .6; cursor: not-allowed; }

  /* Gentle card lift on hover (cards that already have a border) */
  .rounded-2xl.border:hover,
  .rounded-3xl.border:hover { box-shadow: var(--shadow-lift); }

  /* ================= DARK MODE SYSTEM (default) ================= */
  html.dark, html:not(.light) {
    color-scheme: dark;
    background-color: var(--bg);
  }
  html.dark body, html:not(.light) body {
    background-color: var(--bg) !important;
    color: var(--text) !important;
  }

  /* Header: frosted glass */
  html.dark header, html:not(.light) header {
    background-color: rgba(11, 18, 32, .72) !important;
    -webkit-backdrop-filter: saturate(180%) blur(16px);
    backdrop-filter: saturate(180%) blur(16px);
    border-color: rgba(148, 163, 184, .14) !important;
  }
  html.dark nav, html:not(.light) nav { color: #E2E8F0 !important; }

  /* White surfaces → deep slate */
  html.dark .bg-white, html:not(.light) .bg-white {
    background-color: var(--surface) !important;
    color: var(--text) !important;
    border-color: var(--line) !important;
  }
  html.dark .bg-white\/95, html:not(.light) .bg-white\/95,
  html.dark .bg-white\/90, html:not(.light) .bg-white\/90 {
    background-color: rgba(22, 32, 50, .85) !important;
    -webkit-backdrop-filter: blur(14px);
    backdrop-filter: blur(14px);
    color: var(--text) !important;
  }
  html.dark .bg-fog, html:not(.light) .bg-fog { background-color: var(--bg-soft) !important; }
  html.dark .bg-slate-50, html.dark .bg-gray-50,
  html:not(.light) .bg-slate-50, html:not(.light) .bg-gray-50 { background-color: var(--surface) !important; }
  html.dark .bg-slate-100, html.dark .bg-gray-100,
  html:not(.light) .bg-slate-100, html:not(.light) .bg-gray-100 {
    background-color: var(--surface-2) !important;
    color: #E2E8F0 !important;
  }
  html.dark .bg-teal-light\/20, html.dark .bg-teal-light\/30, html.dark .bg-teal-light\/40,
  html:not(.light) .bg-teal-light\/20, html:not(.light) .bg-teal-light\/30, html:not(.light) .bg-teal-light\/40 {
    background-color: rgba(14, 124, 123, .18) !important;
  }

  /* Typography contrast */
  html.dark .text-navy, html:not(.light) .text-navy { color: var(--text-strong) !important; }
  html.dark .text-navy\/90, html:not(.light) .text-navy\/90,
  html.dark .text-navy\/85, html:not(.light) .text-navy\/85,
  html.dark .text-navy\/80, html:not(.light) .text-navy\/80,
  html.dark .text-navy\/75, html:not(.light) .text-navy\/75 { color: var(--text-body) !important; }
  html.dark .text-navy\/70, html:not(.light) .text-navy\/70,
  html.dark .text-navy\/65, html:not(.light) .text-navy\/65,
  html.dark .text-navy\/60, html:not(.light) .text-navy\/60,
  html.dark .text-navy\/55, html:not(.light) .text-navy\/55 { color: var(--text-muted) !important; }
  html.dark .text-navy\/50, html:not(.light) .text-navy\/50,
  html.dark .text-navy\/40, html:not(.light) .text-navy\/40,
  html.dark .text-navy\/30, html:not(.light) .text-navy\/30,
  html.dark .text-navy\/20, html:not(.light) .text-navy\/20 { color: var(--text-faint) !important; }
  html.dark .text-slate-900, html.dark .text-slate-800, html.dark .text-slate-700,
  html:not(.light) .text-slate-900, html:not(.light) .text-slate-800, html:not(.light) .text-slate-700 { color: #E2E8F0 !important; }
  html.dark .text-slate-600, html.dark .text-slate-500,
  html:not(.light) .text-slate-600, html:not(.light) .text-slate-500 { color: var(--text-muted) !important; }
  html.dark .text-teal-dark, html:not(.light) .text-teal-dark { color: var(--brand-bright) !important; }

  /* Borders */
  html.dark .border-navy\/10, html.dark .border-navy\/15, html.dark .border-navy\/20,
  html:not(.light) .border-navy\/10, html:not(.light) .border-navy\/15, html:not(.light) .border-navy\/20 { border-color: var(--line) !important; }
  html.dark .border-slate-200, html.dark .border-gray-200,
  html:not(.light) .border-slate-200, html:not(.light) .border-gray-200 { border-color: var(--line) !important; }
  html.dark .border-slate-100, html:not(.light) .border-slate-100 { border-color: var(--line-soft) !important; }

  /* Form controls */
  input, select, textarea { font: inherit; }
  html.dark input, html.dark select, html.dark textarea,
  html:not(.light) input, html:not(.light) select, html:not(.light) textarea {
    background-color: var(--bg-soft) !important;
    color: #FFFFFF !important;
    border-color: #334155 !important;
    border-radius: var(--radius-sm);
  }
  html.dark input:hover, html.dark select:hover, html.dark textarea:hover,
  html:not(.light) input:hover, html:not(.light) select:hover, html:not(.light) textarea:hover {
    border-color: #475569 !important;
  }
  html.dark input:focus, html.dark select:focus, html.dark textarea:focus,
  html:not(.light) input:focus, html:not(.light) select:focus, html:not(.light) textarea:focus {
    border-color: var(--brand-bright) !important;
    box-shadow: var(--ring);
    outline: none;
  }
  html.dark select option, html:not(.light) select option {
    background-color: var(--bg-soft) !important;
    color: #FFFFFF !important;
  }
  html.dark input::placeholder, html.dark textarea::placeholder,
  html:not(.light) input::placeholder, html:not(.light) textarea::placeholder { color: var(--text-faint) !important; }

  /* Shadows */
  html.dark .shadow-xl, html.dark .shadow-lg, html.dark .shadow-md, html.dark .shadow-sm,
  html:not(.light) .shadow-xl, html:not(.light) .shadow-lg, html:not(.light) .shadow-md, html:not(.light) .shadow-sm {
    box-shadow: var(--shadow-card) !important;
  }

  /* Active language button */
  .lang-btn.active-lang, .lang-btn.bg-teal, .lang-btn.bg-navy {
    background-color: var(--brand) !important;
    color: #FFFFFF !important;
    box-shadow: 0 4px 12px -2px rgba(14, 124, 123, .55);
  }
  html.dark .lang-btn.active-lang, html.dark .lang-btn.bg-teal, html.dark .lang-btn.bg-navy,
  html:not(.light) .lang-btn.active-lang, html:not(.light) .lang-btn.bg-teal, html:not(.light) .lang-btn.bg-navy {
    background-color: var(--brand) !important;
    color: #FFFFFF !important;
  }

  /* ================= LIGHT MODE (when toggled) ================= */
  html.light {
    color-scheme: light;

    /* Complete Light Mode Design Tokens */
    --bg: #F8FAFC;
    --bg-soft: #F1F5F9;
    --surface: #FFFFFF;
    --surface-2: #F8FAFC;
    --line: #E2E8F0;
    --line-soft: #EDF2F7;

    --text: #0F172A;
    --text-strong: #020617;
    --text-body: #1E293B;
    --text-muted: #334155;
    --text-faint: #64748B;

    --brand: #0E7C7B;
    --brand-bright: #0A5F5E;

    --shadow-card: 0 1px 3px rgba(15, 23, 42, .06), 0 10px 24px -4px rgba(15, 23, 42, .08);
    --shadow-lift: 0 4px 6px -1px rgba(15, 23, 42, .08), 0 20px 25px -5px rgba(15, 23, 42, .12);
    --ring: 0 0 0 3px rgba(14, 124, 123, .22);

    background-color: var(--bg);
  }

  html.light body {
    background-color: var(--bg) !important;
    color: var(--text-body) !important;
  }

  /* Header: frosted glass in light mode */
  html.light header {
    background-color: rgba(255, 255, 255, .95) !important;
    -webkit-backdrop-filter: saturate(180%) blur(16px);
    backdrop-filter: saturate(180%) blur(16px);
    border-color: #E2E8F0 !important;
    box-shadow: 0 1px 3px 0 rgba(15, 23, 42, .05);
  }
  html.light nav {
    color: #0F172A !important;
  }
  html.light nav a {
    color: #0F172A !important;
    font-weight: 700 !important;
  }
  html.light nav a:hover {
    color: #0E7C7B !important;
    background-color: #F1F5F9 !important;
  }
  html.light .bg-navy-800 {
    background-color: #08111F !important;
    color: #E2E8F0 !important;
  }
  html.light .bg-navy-800 a {
    color: #FFFFFF !important;
  }
  html.light .bg-navy-800 a:hover {
    color: #2DD4BF !important;
  }

  /* ================= LIGHT MODE HEADER CTA [ቀጠሮ ያዙ / BOOK NOW] ================= */
  /* Ultra high-contrast luxury Navy/Teal button with crisp pure white typography */
  html.light header a[href="#book"],
  html.light #mobile-nav-drawer a[href="#book"] {
    background: linear-gradient(135deg, #0B1220 0%, #0A5F5E 100%) !important;
    background-color: #0B1220 !important;
    color: #FFFFFF !important;
    border: 1px solid rgba(14, 124, 123, .45) !important;
    box-shadow: 0 4px 14px -1px rgba(11, 18, 32, .25), 0 2px 6px -1px rgba(14, 124, 123, .35) !important;
  }
  html.light header a[href="#book"]:hover,
  html.light #mobile-nav-drawer a[href="#book"]:hover {
    background: linear-gradient(135deg, #0E7C7B 0%, #0A5F5E 100%) !important;
    background-color: #0E7C7B !important;
    color: #FFFFFF !important;
    box-shadow: 0 6px 20px -2px rgba(14, 124, 123, .55) !important;
  }
  html.light header a[href="#book"] *,
  html.light #mobile-nav-drawer a[href="#book"] * {
    color: #FFFFFF !important;
    font-weight: 800 !important;
  }

  /* Surfaces & section backgrounds */
  html.light .bg-white {
    background-color: #FFFFFF !important;
    color: var(--text-body);
    border-color: #E2E8F0 !important;
  }
  html.light .bg-white\/95,
  html.light .bg-white\/90 {
    background-color: rgba(255, 255, 255, .96) !important;
    -webkit-backdrop-filter: blur(14px);
    backdrop-filter: blur(14px);
    border-color: #E2E8F0 !important;
  }
  html.light .bg-fog {
    background-color: #F8FAFC !important;
  }
  html.light .bg-slate-50, html.light .bg-gray-50 {
    background-color: #F8FAFC !important;
    border-color: #E2E8F0 !important;
  }
  html.light .bg-slate-100, html.light .bg-gray-100 {
    background-color: #F1F5F9 !important;
    color: #1E293B !important;
    border-color: #E2E8F0 !important;
  }
  html.light .bg-teal-light\/20,
  html.light .bg-teal-light\/30,
  html.light .bg-teal-light\/40 {
    background-color: rgba(14, 124, 123, .09) !important;
  }
  html.light .bg-teal-light {
    background-color: #E3F3F2 !important;
  }

  /* ================= LIGHT MODE TYPOGRAPHY CONTRAST (Fixes all washed-out / invisible letters) ================= */
  html.light .text-navy {
    color: var(--text-strong) !important; /* #020617 - bold, crisp contrast */
  }
  html.light .text-navy\/90,
  html.light .text-navy\/85,
  html.light .text-navy\/80,
  html.light .text-navy\/75 {
    color: var(--text-body) !important; /* #1E293B */
  }
  html.light .text-navy\/70,
  html.light .text-navy\/65,
  html.light .text-navy\/60 {
    color: #1E293B !important; /* rich dark slate - 100% readable text */
  }
  html.light .text-navy\/55,
  html.light .text-navy\/50,
  html.light .text-navy\/40 {
    color: #334155 !important; /* clear secondary dark slate - WCAG AAA compliant */
  }
  html.light .text-navy\/30,
  html.light .text-navy\/20,
  html.light .text-navy\/15,
  html.light .text-navy\/10 {
    color: #64748B !important; /* subtle tertiary hints & step counters - never washed out */
  }

  html.light .text-slate-900,
  html.light .text-slate-800 {
    color: #0F172A !important;
  }
  html.light .text-slate-700 {
    color: #1E293B !important;
  }
  html.light .text-slate-600,
  html.light .text-slate-500 {
    color: #334155 !important;
  }
  html.light .text-teal-dark {
    color: #0A5F5E !important;
  }
  html.light .text-teal {
    color: #0E7C7B !important;
  }

  /* Light mode borders */
  html.light .border-navy\/10,
  html.light .border-navy\/15,
  html.light .border-navy\/20 {
    border-color: #E2E8F0 !important;
  }
  html.light .border-slate-200,
  html.light .border-gray-200 {
    border-color: #E2E8F0 !important;
  }
  html.light .border-slate-100 {
    border-color: #F1F5F9 !important;
  }
  html.light .divide-navy\/10 > :not([hidden]) ~ :not([hidden]) {
    border-color: #E2E8F0 !important;
  }

  /* Light mode form controls */
  html.light input,
  html.light select,
  html.light textarea {
    background-color: #FFFFFF !important;
    color: #0F172A !important;
    border-color: #CBD5E1 !important;
    border-radius: var(--radius-sm);
  }
  html.light input:hover,
  html.light select:hover,
  html.light textarea:hover {
    border-color: #94A3B8 !important;
  }
  html.light input:focus,
  html.light select:focus,
  html.light textarea:focus {
    border-color: var(--brand) !important;
    box-shadow: 0 0 0 3px rgba(14, 124, 123, .22);
    outline: none;
  }
  html.light select option {
    background-color: #FFFFFF !important;
    color: #0F172A !important;
  }
  html.light input::placeholder,
  html.light textarea::placeholder {
    color: #94A3B8 !important;
  }

  /* Light mode shadows & card lift */
  html.light .shadow-xl,
  html.light .shadow-lg,
  html.light .shadow-md,
  html.light .shadow-sm {
    box-shadow: var(--shadow-card) !important;
  }
  html.light .rounded-2xl.border:hover,
  html.light .rounded-3xl.border:hover {
    box-shadow: var(--shadow-lift) !important;
  }

  /* Light mode theme toggle button */
  html.light #theme-toggle-btn {
    border-color: #CBD5E1 !important;
    color: #0F172A !important;
    background-color: #F1F5F9 !important;
  }
  html.light #theme-toggle-btn:hover {
    background-color: #E2E8F0 !important;
    color: #0E7C7B !important;
  }

  /* Booking wizard step indicators & cards in light mode */
  html.light .step-dot.text-teal-dark {
    color: #0A5F5E !important;
    background-color: rgba(14, 124, 123, .12) !important;
  }
  html.light .step-dot:not(.text-teal-dark) {
    color: #475569 !important;
  }
  html.light .step-dot:not(.text-teal-dark):hover {
    color: #020617 !important;
  }
  html.light .svc-btn {
    background-color: #FFFFFF !important;
    border-color: #E2E8F0 !important;
  }
  html.light .svc-btn:hover {
    border-color: #0E7C7B !important;
  }
  html.light .svc-btn.border-navy {
    border-color: #0E7C7B !important;
    background-color: rgba(14, 124, 123, .05) !important;
    box-shadow: 0 0 0 1px #0E7C7B;
  }

  /* Reviews & test cards in light mode */
  html.light #dynamic-reviews-grid > div,
  html.light #reviews .border-l-2 {
    background-color: #FFFFFF !important;
    border-color: #0E7C7B !important;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
  }

  /* ================= PRESERVE INTENTIONALLY DARK CARDS & SECTIONS IN LIGHT MODE ================= */
  /* 1. Hero Feature Card (.duo) */
  html.light .duo,
  html.light .duo.grain {
    background-color: #08111F !important;
    background-image:
      radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px),
      radial-gradient(120% 80% at 100% 0%, rgba(45, 212, 191, .28) 0%, transparent 55%),
      linear-gradient(160deg, #0A6B6A 0%, #08111F 100%) !important;
    background-size: 14px 14px, auto, auto !important;
    color: #FFFFFF !important;
    box-shadow: 0 10px 30px -10px rgba(10, 107, 106, .45), 0 20px 40px -15px rgba(8, 17, 31, .6) !important;
  }
  html.light .duo h3,
  html.light .duo h2,
  html.light .duo p:not(.text-navy *):not(.bg-white *),
  html.light .duo span:not(.text-navy *):not(.bg-white *),
  html.light .duo div:not(.bg-white):not(.bg-white *) {
    color: #FFFFFF !important;
  }
  html.light .duo .text-white\/80 {
    color: rgba(255, 255, 255, .90) !important;
  }

  /* 2. Process Section (#process) */
  html.light #process {
    background-color: #0B1220 !important;
    color: #FFFFFF !important;
    border-color: rgba(255, 255, 255, .10) !important;
  }
  html.light #process h2,
  html.light #process h3,
  html.light #process p,
  html.light #process span,
  html.light #process .text-white {
    color: #FFFFFF !important;
  }
  html.light #process [class*="text-white/"] {
    color: rgba(255, 255, 255, .80) !important;
  }
  html.light #process .text-teal,
  html.light #process .text-teal-dark {
    color: #2DD4BF !important;
  }

  /* 3. About Section Company Badge Box */
  html.light .from-slate-900.to-navy,
  html.light .from-navy.to-slate-900 {
    background: linear-gradient(135deg, #0B1220 0%, #0F172A 100%) !important;
    background-color: #0B1220 !important;
    color: #FFFFFF !important;
    border-color: rgba(45, 212, 191, .3) !important;
    box-shadow: 0 15px 35px -10px rgba(11, 18, 32, .5) !important;
  }
  html.light .from-slate-900.to-navy h3,
  html.light .from-slate-900.to-navy p,
  html.light .from-slate-900.to-navy span,
  html.light .from-slate-900.to-navy div,
  html.light .from-navy.to-slate-900 h3,
  html.light .from-navy.to-slate-900 p,
  html.light .from-navy.to-slate-900 span {
    color: inherit;
  }
  html.light .from-slate-900.to-navy [class*="text-white/"],
  html.light .from-navy.to-slate-900 [class*="text-white/"] {
    color: rgba(255, 255, 255, .78) !important;
  }
  html.light .from-slate-900.to-navy .text-teal-light,
  html.light .from-navy.to-slate-900 .text-teal-light {
    color: #5EEAD4 !important;
  }

  /* 4. Full-Width CTA Section */
  html.light .from-slate-900.via-navy.to-slate-900 {
    background: linear-gradient(90deg, #0F172A 0%, #0B1220 50%, #0F172A 100%) !important;
    background-color: #0B1220 !important;
    color: #FFFFFF !important;
  }
  html.light .from-slate-900.via-navy.to-slate-900 h2,
  html.light .from-slate-900.via-navy.to-slate-900 p,
  html.light .from-slate-900.via-navy.to-slate-900 span,
  html.light .from-slate-900.via-navy.to-slate-900 .text-white {
    color: #FFFFFF !important;
  }
  html.light .from-slate-900.via-navy.to-slate-900 [class*="text-white/"] {
    color: rgba(255, 255, 255, .85) !important;
  }

  /* 5. Footer */
  html.light footer {
    background-color: #070D18 !important;
    color: #FFFFFF !important;
    border-color: rgba(255, 255, 255, .10) !important;
  }
  html.light footer h4,
  html.light footer p,
  html.light footer a,
  html.light footer span,
  html.light footer .text-white {
    color: #FFFFFF !important;
  }
  html.light footer [class*="text-white/"] {
    color: rgba(255, 255, 255, .72) !important;
  }
  html.light footer a:hover {
    color: #2DD4BF !important;
  }
  html.light footer .text-teal,
  html.light footer .text-teal-dark {
    color: #2DD4BF !important;
  }

  /* 6. Solid Navy Buttons & Badges */
  html.light .bg-navy,
  html.light a.bg-navy,
  html.light button.bg-navy {
    background-color: #0B1220 !important;
    color: #FFFFFF !important;
  }
  html.light .bg-navy *,
  html.light a.bg-navy *,
  html.light button.bg-navy * {
    color: #FFFFFF !important;
  }
  html.light a.bg-navy:hover,
  html.light button.bg-navy:hover {
    background-color: #0E7C7B !important;
    color: #FFFFFF !important;
  }

  html.light ::selection { background: rgba(14, 124, 123, .25); color: #0B1220; }
  html.light { scrollbar-color: #CBD5E1 #fff; }
  html.light ::-webkit-scrollbar-track { background: #fff; }
  html.light ::-webkit-scrollbar-thumb { background: #CBD5E1; border-color: #fff; }

  /* Sticky Header — permanently stuck at the top across all viewports */
  header {
    position: -webkit-sticky !important;
    position: sticky !important;
    top: 0 !important;
    z-index: 50 !important;
  }

  /* ================= Mobile responsiveness / no horizontal overflow ================= */
  html, body {
    overflow-x: clip !important;
    width: 100% !important;
    max-width: 100vw !important;
    margin: 0;
    padding: 0;
  }
  section, main, header, footer, .max-w-7xl, .max-w-2xl { max-width: 100%; }
  .overflow-x-auto {
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
  }

  /* Phones: 320px – 640px */
  @media (max-width: 640px) {
    header > div { padding-left: .75rem !important; padding-right: .75rem !important; gap: .375rem !important; }
    header a[href="#top"] { gap: .375rem !important; }
    header a[href="#top"] .leading-tight > span,
    header a[href="#top"] .leading-tight > div > span:last-child { display: none; }
    header a[href="#top"] .font-serif { font-size: 1rem !important; }
    header a[href="#book"] { padding: .5rem .75rem !important; }
    #mobile-menu-btn { padding: .5rem !important; }
    html { scroll-padding-top: 4.5rem; }
  }

  /* Touch devices: no sticky hover lift */
  @media (hover: none) {
    .rounded-2xl.border:hover,
    .rounded-3xl.border:hover { box-shadow: inherit; }
  }

  /* Respect users who prefer less motion */
  @media (prefers-reduced-motion: reduce) {
    html { scroll-behavior: auto; }
    *, *::before, *::after {
      animation-duration: .01ms !important;
      animation-iteration-count: 1 !important;
      transition-duration: .01ms !important;
    }
  }

  /* ============ MEASH MODERN (approved sample) — hero 3D slider, photo service cards, 3-step, gallery ============ */
  .ms-hero-panel { background: linear-gradient(160deg, #0A2A4A 0%, #061A30 78%); border-radius: 1.25rem; overflow: hidden; position: relative; box-shadow: 0 25px 50px rgba(6,26,48,.35); }
  .ms-stage { position: relative; height: 520px; perspective: 1400px; outline: none; touch-action: pan-y; cursor: grab; user-select: none; -webkit-user-select: none; }
  .ms-stage.ms-dragging { cursor: grabbing; }
  .ms-stage.ms-dragging .ms-slide { transition: none; }
  .ms-slide { position: absolute; top: 50%; left: 50%; width: min(302px, 64vw); aspect-ratio: 3/4; border-radius: 22px; overflow: hidden; background: #0d2c49; box-shadow: 0 30px 60px rgba(0,0,0,.45); transition: transform .65s cubic-bezier(.22,.9,.28,1), opacity .65s ease, filter .65s ease; will-change: transform; cursor: pointer; border: 1px solid rgba(255,255,255,.14); margin: 0; }
  .ms-slide img { width: 100%; height: 100%; object-fit: cover; display: block; -webkit-user-drag: none; }
  .ms-slide figcaption { position: absolute; left: 0; right: 0; bottom: 0; padding: 34px 16px 13px; color: #fff; background: linear-gradient(transparent, rgba(4,20,36,.85)); font-weight: 700; font-size: .92rem; }
  .ms-slide figcaption .ms-cap-name { display: block; }
  .ms-slide figcaption .ms-cap-price { display: block; font-size: .82rem; color: #7dd3fc; font-weight: 800; margin-top: 2px; }
  .ms-stage-nav { position: absolute; left: 50%; transform: translateX(-50%); bottom: 10px; display: flex; align-items: center; gap: 14px; z-index: 5; }
  .ms-arrow { width: 46px; height: 46px; border-radius: 50%; border: 1.5px solid rgba(255,255,255,.35); background: rgba(255,255,255,.10); color: #fff; display: grid; place-items: center; backdrop-filter: blur(6px); cursor: pointer; }
  .ms-arrow:hover { background: #1E9BD7; border-color: #1E9BD7; }
  .ms-dots { display: flex; gap: 8px; }
  .ms-dot { width: 9px; height: 9px; min-height: 0; border-radius: 99px; border: 0; padding: 0; background: rgba(255,255,255,.35); transition: width .3s, background .3s; cursor: pointer; }
  .ms-dot.on { width: 28px; background: #0FB5A5; }
  @media (max-width: 900px) { .ms-stage { height: 470px; } }

  /* Photo service cards */
  .ms-svc-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
  .ms-svc { border-radius: 20px; overflow: hidden; box-shadow: 0 8px 22px rgba(8,42,74,.10); transition: transform .22s ease, box-shadow .22s ease; display: flex; flex-direction: column; }
  .ms-svc:hover { transform: translateY(-6px); box-shadow: 0 18px 40px rgba(8,42,74,.14); }
  .ms-svc .ms-ph { aspect-ratio: 4/3; overflow: hidden; }
  .ms-svc .ms-ph img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .5s ease; }
  .ms-svc:hover .ms-ph img { transform: scale(1.07); }
  .ms-svc .ms-bd { padding: 20px 20px 22px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
  .ms-svc .ms-tag { align-self: flex-start; font-size: .74rem; font-weight: 800; letter-spacing: .06em; color: #0E7C7B; background: rgba(15,181,165,.12); padding: 5px 11px; border-radius: 999px; text-transform: uppercase; }
  .ms-svc-cta { background: linear-gradient(150deg, #0A2A4A, #061A30); color: #fff; justify-content: center; }
  .ms-svc-cta .ms-bd { justify-content: center; gap: 12px; }
  .ms-svc-cta p { color: #BCD6E8; }
  @media (max-width: 900px) { .ms-svc-grid { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 560px) { .ms-svc-grid { grid-template-columns: 1fr; } }

  /* 3-step section */
  .ms-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 26px; }
  .ms-step { border-top: 3px solid rgba(11,18,32,.1); padding-top: 20px; }
  .ms-step .ms-num { font-size: 2.1rem; font-weight: 800; color: #0FB5A5; }
  .ms-step .ms-ph { margin-top: 16px; border-radius: 16px; overflow: hidden; aspect-ratio: 16/10; }
  .ms-step .ms-ph img { width: 100%; height: 100%; object-fit: cover; display: block; }
  @media (max-width: 820px) { .ms-steps { grid-template-columns: 1fr; } }

  /* Gallery mosaic + lightbox */
  .ms-gal-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 16px; }
  .ms-gcard { position: relative; border-radius: 20px; overflow: hidden; cursor: zoom-in; border: 0; padding: 0; background: #0d2c49; box-shadow: 0 8px 22px rgba(8,42,74,.10); transform-style: preserve-3d; text-align: left; width: 100%; }
  .ms-gcard img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .5s ease; }
  .ms-gcard:hover img { transform: scale(1.05); }
  .ms-gcard .ms-cap { position: absolute; left: 0; right: 0; bottom: 0; padding: 40px 16px 13px; color: #fff; font-weight: 700; font-size: .93rem; background: linear-gradient(transparent, rgba(4,20,36,.85)); display: flex; justify-content: space-between; align-items: center; gap: 8px; }
  .ms-gcard .ms-zoom { width: 34px; height: 34px; flex: none; border-radius: 50%; background: rgba(255,255,255,.16); display: grid; place-items: center; }
  .ms-g-a { grid-column: span 7; aspect-ratio: 16/10.4; }
  .ms-g-b { grid-column: span 5; aspect-ratio: 4/3.55; }
  .ms-g-c { grid-column: span 4; aspect-ratio: 3/3.4; }
  .ms-g-d { grid-column: span 4; aspect-ratio: 3/3.4; }
  .ms-g-e { grid-column: span 4; aspect-ratio: 3/3.4; }
  @media (max-width: 820px) { .ms-g-a, .ms-g-b, .ms-g-c, .ms-g-d, .ms-g-e { grid-column: span 12; aspect-ratio: 4/3; } }
  .ms-lb { position: fixed; inset: 0; z-index: 100; background: rgba(3,14,25,.93); display: none; align-items: center; justify-content: center; flex-direction: column; padding: 20px; }
  .ms-lb.open { display: flex; }
  .ms-lb img { max-width: min(920px, 94vw); max-height: 76vh; border-radius: 16px; box-shadow: 0 30px 80px rgba(0,0,0,.6); object-fit: contain; }
  .ms-lb .ms-lb-cap { color: #E4F2FA; font-weight: 700; margin-top: 16px; text-align: center; }
  .ms-lb .ms-lb-row { display: flex; gap: 12px; margin-top: 14px; align-items: center; }
  .ms-lb button { width: 48px; height: 48px; border-radius: 50%; border: 1.5px solid rgba(255,255,255,.35); background: rgba(255,255,255,.1); color: #fff; display: grid; place-items: center; cursor: pointer; }
  .ms-lb .ms-lb-close { position: absolute; top: 16px; right: 16px; }
  @media (prefers-reduced-motion: reduce) { .ms-slide, .ms-svc .ms-ph img, .ms-gcard img { transition: none; } }
</style>
</head>
<body id="top" class="antialiased">

<!-- ================= TOP ANNOUNCEMENT & HOTLINE BAR (Desktop/Tablet) ================= -->
<div class="hidden md:block bg-[#070D18] text-slate-300 text-[11px] font-medium border-b border-white/10 relative z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-8 flex items-center justify-between">
    <!-- Left: Address & Status -->
    <div class="flex items-center gap-4">
      <span class="flex items-center gap-1.5 text-slate-300">
        <i data-lucide="map-pin" class="w-3.5 h-3.5 text-teal-400"></i>
        <span>አዲስ አበባ (አያት አደባባይ) • Addis Ababa, Ayat Square</span>
      </span>
      <span class="hidden lg:flex items-center gap-1.5 text-emerald-400 font-semibold">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
        <span>24/7 ፈጣን አገልግሎት • Active Service</span>
      </span>
    </div>

    <!-- Right: Hotlines & Shortcuts -->
    <div class="flex items-center gap-3">
      <a href="tel:{{ $phone1 }}" class="hover:text-teal-400 transition flex items-center gap-1 font-bold text-white">
        <i data-lucide="phone-call" class="w-3 h-3 text-teal-400"></i>
        <span>{{ $phone1 }}</span>
      </a>
      <span class="text-slate-600">/</span>
      <a href="tel:{{ $phone2 }}" class="hover:text-teal-400 transition flex items-center gap-1 font-bold text-white">
        <span>{{ $phone2 }}</span>
      </a>
      <span class="text-slate-600">|</span>
      <a href="{{ $websiteSettings['telegram_bot'] ?? 'https://t.me/meash_cleaning_solution_bot' }}" target="_blank" rel="noopener noreferrer" class="hover:text-cyan-400 transition flex items-center gap-1 text-cyan-300 font-semibold">
        <i data-lucide="send" class="w-3 h-3 text-cyan-400"></i>
        <span>Telegram Bot</span>
      </a>
      <span class="text-slate-600">|</span>
      <button type="button" onclick="openStaffModal()" class="hover:text-teal-400 transition flex items-center gap-1 text-slate-400 hover:text-white cursor-pointer">
        <i data-lucide="lock" class="w-3 h-3"></i>
        <span>Staff Portal</span>
      </button>
    </div>
  </div>
</div>

<!-- ================= MAIN NAVIGATION ================= -->
<header class="sticky top-0 z-50 bg-white/95 dark:bg-[#0B1220]/95 backdrop-blur-md border-b border-slate-200/90 dark:border-slate-800 shadow-sm transition-colors duration-200">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 sm:h-18 flex items-center justify-between gap-2 sm:gap-4">
    
    <!-- Brand Logo & Name -->
    <a href="#top" class="flex items-center gap-2.5 sm:gap-3 group shrink-0">
      <div class="relative">
        <img src="/logo.jpg" alt="Meash Cleaning Logo" class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl object-cover shadow-sm group-hover:scale-105 transition-transform duration-300 border border-teal/20">
        <span class="absolute -bottom-0.5 -right-0.5 w-2.5 sm:w-3 h-2.5 sm:h-3 bg-emerald-500 border-2 border-white dark:border-[#0B1220] rounded-full"></span>
      </div>
      <div class="leading-tight">
        <div class="flex items-center gap-1.5">
          <span class="font-serif font-black text-lg sm:text-xl tracking-tight text-navy dark:text-white">MEASH</span>
        </div>
        <span class="text-[10px] sm:text-[11px] font-bold text-teal-dark dark:text-teal-400 tracking-wide block">CLEANING SOLUTION</span>
      </div>
    </a>

    <!-- Desktop Nav Links (xl:flex) -->
    <nav class="hidden xl:flex items-center gap-1 text-[13px] font-bold text-slate-700 dark:text-slate-200">
      <a href="#about" class="px-3 py-2 rounded-xl hover:text-teal hover:bg-slate-100 dark:hover:bg-slate-800/80 transition"><span data-lang-block="am">ስለ እኛ</span><span data-lang-block="en">About</span></a>
      <a href="#services" class="px-3 py-2 rounded-xl hover:text-teal hover:bg-slate-100 dark:hover:bg-slate-800/80 transition"><span data-lang-block="am">የቤት ጽዳት</span><span data-lang-block="en">Residential</span></a>
      <a href="#commercial" class="px-3 py-2 rounded-xl hover:text-teal hover:bg-slate-100 dark:hover:bg-slate-800/80 transition flex items-center gap-1 text-teal dark:text-teal-400">
        <i data-lucide="building-2" class="w-4 h-4"></i>
        <span data-lang-block="am">የድርጅቶች ጽዳት</span><span data-lang-block="en">Commercial</span>
      </a>
      <a href="#gallery" class="px-3 py-2 rounded-xl hover:text-teal hover:bg-slate-100 dark:hover:bg-slate-800/80 transition"><span data-lang-block="am">የስራ ጋለሪ</span><span data-lang-block="en">Projects</span></a>
      <a href="#reviews" class="px-3 py-2 rounded-xl hover:text-teal hover:bg-slate-100 dark:hover:bg-slate-800/80 transition"><span data-lang-block="am">አስተያየቶች</span><span data-lang-block="en">Reviews</span></a>
      <a href="#contact" class="px-3 py-2 rounded-xl hover:text-teal hover:bg-slate-100 dark:hover:bg-slate-800/80 transition"><span data-lang-block="am">አድራሻ</span><span data-lang-block="en">Contact</span></a>
      <a href="#track" class="px-3 py-2 rounded-xl hover:text-teal hover:bg-slate-100 dark:hover:bg-slate-800/80 transition flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
        <i data-lucide="search" class="w-3.5 h-3.5"></i>
        <span data-lang-block="am">ትዕዛዝ መከታተያ</span><span data-lang-block="en">Track</span>
      </a>
    </nav>

    <!-- Header Action Controls -->
    <div class="flex items-center gap-1.5 sm:gap-2">
      <!-- Direct Call Button (Desktop / Mobile Icon) -->
      <a href="tel:{{ $phone1 }}" class="hidden sm:flex xl:hidden items-center gap-1.5 text-xs font-bold bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 px-2.5 py-1.5 rounded-full transition shadow-sm" title="Call Now">
        <i data-lucide="phone" class="w-3.5 h-3.5 text-emerald-500"></i>
        <span class="hidden md:inline font-mono">{{ $phone1 }}</span>
      </a>
      <a href="tel:{{ $phone1 }}" class="flex sm:hidden p-2 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 hover:bg-emerald-500/25 transition items-center justify-center shrink-0" title="Call Meash">
        <i data-lucide="phone" class="w-4 h-4"></i>
      </a>

      <!-- Dark Mode Toggle Button (Default Dark) -->
      <button type="button" onclick="toggleTheme()" id="theme-toggle-btn" class="p-2 sm:p-2.5 rounded-full border border-slate-700 dark:border-slate-700 text-amber-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer flex items-center justify-center shrink-0" title="የጨለማ/ብርሃን ሁነታ ቀይር (Toggle Dark/Light Mode)">
        <i data-lucide="sun" id="theme-icon" class="w-4 h-4"></i>
      </button>

      <!-- Language Selector (Desktop / Tablet) -->
      <div class="hidden sm:flex items-center gap-0.5 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 rounded-full p-0.5">
        <button type="button" onclick="setLang('am')" data-lang="am" class="lang-btn text-[11px] font-bold px-2.5 py-1 rounded-full cursor-pointer transition">አማ</button>
        <button type="button" onclick="setLang('en')" data-lang="en" class="lang-btn text-[11px] font-bold px-2.5 py-1 rounded-full cursor-pointer transition">EN</button>
      </div>

      <!-- Main CTA: Quote / Book -->
      <a href="#book" class="header-book-btn bg-gradient-to-r from-teal to-teal-dark hover:from-teal-dark hover:to-teal text-white text-[12px] sm:text-[13px] font-black px-3 sm:px-4.5 py-2 sm:py-2.5 rounded-xl transition shadow-md shadow-teal/20 flex items-center gap-1.5 shrink-0 transform hover:-translate-y-0.5" style="background-color: #0E7C7B; color: #FFFFFF;">
        <i data-lucide="calendar" class="w-4 h-4 hidden sm:inline text-white"></i>
        <span data-lang-block="am" class="text-white font-black">ቀጠሮ ያዙ</span><span data-lang-block="en" class="text-white font-black">Book Now</span>
      </a>

      <!-- Hamburger Menu Button (Available on all screens: mobile, tablet & desktop) -->
      <button type="button" onclick="toggleMobileNav()" id="mobile-menu-btn" class="p-2 sm:p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:text-teal hover:bg-slate-100 dark:hover:bg-slate-700 transition flex items-center justify-center cursor-pointer shrink-0" aria-label="Open Navigation Menu" title="ምናሌ (Menu)">
        <i data-lucide="menu" id="mobile-menu-icon" class="w-5 h-5"></i>
      </button>
    </div>
  </div>
</header>

<!-- ================= NAVIGATION DRAWER (Mobile, Tablet & Desktop) ================= -->
<div id="mobile-nav-drawer" class="fixed inset-0 z-[60] bg-black/70 backdrop-blur-sm hidden transition-all duration-300" onclick="if(event.target === this) closeMobileNav()">
  <div class="fixed inset-y-0 right-0 max-w-xs w-full bg-white dark:bg-[#0B1220] shadow-2xl flex flex-col justify-between border-l border-slate-200 dark:border-slate-800">
    <!-- Drawer Top Header -->
    <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <img src="/logo.jpg" alt="Meash Logo" class="w-9 h-9 rounded-xl object-cover border border-teal/20">
        <div>
          <span class="font-serif font-black text-base text-navy dark:text-white">MEASH</span>
          <span class="text-[9px] font-bold text-teal block">CLEANING SOLUTION</span>
        </div>
      </div>
      <button type="button" onclick="closeMobileNav()" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer" aria-label="Close menu">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <!-- Drawer Navigation Content -->
    <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4">
      <!-- Language Picker in Mobile Drawer -->
      <div class="p-2.5 rounded-2xl bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-1.5">
        <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block px-1">ቋንቋ ይምረጡ / Select Language</span>
        <div class="grid grid-cols-2 gap-1">
          <button type="button" onclick="setLang('am')" data-lang="am" class="lang-btn py-1.5 text-xs font-bold rounded-xl transition text-center cursor-pointer">አማርኛ</button>
          <button type="button" onclick="setLang('en')" data-lang="en" class="lang-btn py-1.5 text-xs font-bold rounded-xl transition text-center cursor-pointer">English</button>
        </div>
      </div>

      <!-- Nav Links -->
      <div class="space-y-1 font-bold text-sm text-slate-800 dark:text-slate-200">
        <a href="#about" onclick="closeMobileNav()" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/80 transition">
          <i data-lucide="info" class="w-4 h-4 text-teal"></i>
          <span><span data-lang-block="am">ስለ እኛ</span><span data-lang-block="en">About Us</span></span>
        </a>
        <a href="#services" onclick="closeMobileNav()" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/80 transition">
          <i data-lucide="sparkles" class="w-4 h-4 text-teal"></i>
          <span><span data-lang-block="am">የቤት ጽዳት አገልግሎቶች</span><span data-lang-block="en">Residential Services</span></span>
        </a>
        <a href="#commercial" onclick="closeMobileNav()" class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-teal/10 text-teal dark:text-teal-400 hover:bg-teal/20 transition">
          <i data-lucide="building-2" class="w-4 h-4 text-teal"></i>
          <span><span data-lang-block="am">የድርጅቶችና ቢሮ ጽዳት</span><span data-lang-block="en">Commercial Cleaning</span></span>
        </a>
        <a href="#gallery" onclick="closeMobileNav()" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/80 transition">
          <i data-lucide="image" class="w-4 h-4 text-teal"></i>
          <span><span data-lang-block="am">የስራዎች ጋለሪ</span><span data-lang-block="en">Projects Gallery</span></span>
        </a>
        <a href="#reviews" onclick="closeMobileNav()" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/80 transition">
          <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
          <span><span data-lang-block="am">የደንበኞች አስተያየት</span><span data-lang-block="en">Customer Reviews</span></span>
        </a>
        <a href="#track" onclick="closeMobileNav()" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/80 transition">
          <i data-lucide="search" class="w-4 h-4 text-cyan-400"></i>
          <span><span data-lang-block="am">የትዕዛዝ መከታተያ</span><span data-lang-block="en">Track Your Order</span></span>
        </a>
        <a href="#contact" onclick="closeMobileNav()" class="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/80 transition">
          <i data-lucide="map-pin" class="w-4 h-4 text-teal"></i>
          <span><span data-lang-block="am">አድራሻ እና ካርታ</span><span data-lang-block="en">Contact & Location</span></span>
        </a>
      </div>

      <!-- Quick Action Cards in Mobile Menu -->
      <div class="pt-2 space-y-2 border-t border-slate-200 dark:border-slate-800">
        <a href="tel:{{ $phone1 }}" class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/20">
          <i data-lucide="phone-call" class="w-4 h-4"></i>
          <span>ቀጥታ ይደውሉ፡ {{ $phone1 }}</span>
        </a>
        <a href="{{ $websiteSettings['telegram_bot'] ?? 'https://t.me/meash_cleaning_solution_bot' }}" target="_blank" rel="noopener noreferrer" class="w-full py-2.5 px-4 rounded-xl bg-[#0088cc] hover:bg-[#0077b5] text-white font-extrabold text-xs flex items-center justify-center gap-2 shadow">
          <i data-lucide="send" class="w-4 h-4"></i>
          <span>ቴሌግራም ቦት (Telegram Order)</span>
        </a>
      </div>
    </div>

    <!-- Drawer Footer -->
    <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 space-y-2">
      <a href="#book" onclick="closeMobileNav()" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-teal to-teal-dark text-white font-black text-xs flex items-center justify-center gap-2 shadow-md" style="background-color: #0E7C7B; color: #FFFFFF;">
        <i data-lucide="calendar" class="w-4 h-4 text-white"></i>
        <span class="text-white font-black">ዋጋ አስላ / ቀጠሮ ያዝ (Book Now)</span>
      </a>
      <button type="button" onclick="closeMobileNav(); openStaffModal();" class="w-full py-2 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-teal flex items-center justify-center gap-1.5 cursor-pointer">
        <i data-lucide="lock" class="w-3.5 h-3.5"></i>
        <span>የሰራተኞች መግቢያ (Staff Portal)</span>
      </button>
    </div>
  </div>
</div>

@php
  // Website photos — admin-managed via the `photos` table (admin Photos
  // page), with the hardcoded lists below as the FALLBACK. The public
  // site reads status = 'added' rows per section (hero slider, service
  // cards, gallery, feature strip); if the table is empty, missing, or
  // the query fails, the fallback lists are used, so the live site can
  // never break. Prices on slider slides still come from the live
  // $services data (never hardcoded) via the service `code` below.
  $fallbackPhotos = [
    ['file' => 'office-cleaning.jpg', 'code' => 'floor_ceramic', 'am' => 'የወለል ማሽን ጽዳት', 'en' => 'Floor machine cleaning'],
    ['file' => 'sofa-steam.jpg', 'code' => 'upholstery_carpet', 'am' => 'የሶፋ በእንፋሎት ጽዳት', 'en' => 'Sofa steam cleaning'],
    ['file' => 'villa-carpet.jpg', 'code' => 'facilities_general', 'am' => 'የሆቴል ላውንጅ ጥልቅ ጽዳት', 'en' => 'Hotel lounge deep clean'],
    ['file' => 'rope-access.jpg', 'code' => 'glass', 'am' => 'የረጃጅም ሕንፃ መስታወት ጽዳት', 'en' => 'High-rise glass cleaning'],
    ['file' => 'facade-scaffold.jpg', 'code' => 'post_construction', 'am' => 'የሕንፃ የፊት ገጽ ጽዳት', 'en' => 'Facade cleaning'],
    // 9 new photos (Gemini-generated Ethiopian situations, sent by the owner
    // 2026-11-13) — every service now has its own photo, none reused.
    ['file' => 'living-room.jpg', 'code' => 'facilities_general', 'am' => 'የቤት ጥልቅ ጽዳት', 'en' => 'Home deep cleaning'],
    ['file' => 'hotel-restaurant.jpg', 'code' => 'facilities_general', 'am' => 'የሆቴል እና ሬስቶራንት ጽዳት', 'en' => 'Hotel & restaurant cleaning'],
    ['file' => 'office-polisher.jpg', 'code' => 'floor_ceramic', 'am' => 'የቢሮ ወለል ማሽን ጽዳት', 'en' => 'Office floor machine cleaning'],
    ['file' => 'bedroom.jpg', 'code' => 'facilities_general', 'am' => 'የመኝታ ቤት ጽዳት', 'en' => 'Bedroom cleaning'],
    ['file' => 'bathroom.jpg', 'code' => 'facilities_general', 'am' => 'የመታጠቢያ ቤት ጽዳት', 'en' => 'Bathroom cleaning'],
    ['file' => 'sofa-carpet-steam.jpg', 'code' => 'upholstery_carpet', 'am' => 'የሶፋ እና ካርፔት በእንፋሎት ጽዳት', 'en' => 'Sofa & carpet steam cleaning'],
    ['file' => 'windows-ladder.jpg', 'code' => 'glass', 'am' => 'የመስኮት ጽዳት', 'en' => 'Window cleaning'],
    ['file' => 'post-construction.jpg', 'code' => 'post_construction', 'am' => 'ከግንባታ በኋላ ጽዳት', 'en' => 'Post-construction cleaning'],
    ['file' => 'kitchen.jpg', 'code' => 'kitchen_dining', 'am' => 'የኩሽና ጽዳት', 'en' => 'Kitchen cleaning'],
  ];
  // Service code -> gallery photo (kitchen and post-construction now have
  // their own photos; the old villa-photo reuse is gone).
  $fallbackServicePhoto = [
    'floor_ceramic' => 'office-cleaning.jpg',
    'facilities_general' => 'villa-carpet.jpg',
    'kitchen_dining' => 'kitchen.jpg',
    'post_construction' => 'post-construction.jpg',
    'glass' => 'rope-access.jpg',
    'upholstery_carpet' => 'sofa-steam.jpg',
  ];
  // Gallery mosaic order: the 5 real photos first, then the 9 new ones.
  // The mosaic shapes (ms-g-a..e) cycle with $gidx % 5 below.
  $fallbackGalleryOrder = ['office-cleaning.jpg', 'villa-carpet.jpg', 'sofa-steam.jpg', 'rope-access.jpg', 'facade-scaffold.jpg', 'living-room.jpg', 'hotel-restaurant.jpg', 'office-polisher.jpg', 'bedroom.jpg', 'bathroom.jpg', 'sofa-carpet-steam.jpg', 'windows-ladder.jpg', 'post-construction.jpg', 'kitchen.jpg'];
  // Feature strip (3-step "How we deliver") fallback photos.
  $fallbackFeaturePhotos = [
    ['file' => 'facade-scaffold.jpg', 'am' => 'የመስታወት ጽዳት መፍትሔ የሚያዘጋጅ የሜሽ ሰራተኛ', 'en' => 'Meash cleaner preparing glass-cleaning solution'],
    ['file' => 'office-cleaning.jpg', 'am' => 'የሜሽ ቡድን በቢሮ ውስጥ እየሰራ', 'en' => 'Meash team at work in an office'],
    ['file' => 'villa-carpet.jpg', 'am' => 'በብሩህ የሆቴል ላውንጅ ውስጥ አዲስ የታጠበ ካርፔት', 'en' => 'Freshly shampooed carpet in a bright hotel lounge'],
  ];

  // Resolve a stored photo value to a browser src: Cloudinary URLs are
  // absolute already; local files live under /images/gallery/.
  $photoSrc = function ($file) {
    $file = (string) $file;
    if ($file === '') return '';
    if (str_starts_with($file, 'http://') || str_starts_with($file, 'https://') || str_starts_with($file, '/')) return $file;
    return '/images/gallery/' . ltrim($file, '/');
  };

  // Live photos from the admin-managed `photos` table. Any failure
  // (table missing, DB down) keeps the fallback lists above.
  $galleryPhotos = $fallbackPhotos;
  $servicePhoto = $fallbackServicePhoto;
  $galleryOrder = $fallbackGalleryOrder;
  $featurePhotos = $fallbackFeaturePhotos;
  $galleryByFile = collect($galleryPhotos)->keyBy('file');
  try {
    $dbPhotos = \App\Models\Photo::where('status', 'added')->orderBy('sort_order')->orderBy('id')->get();
    if ($dbPhotos && $dbPhotos->count() > 0) {
      $hero = $dbPhotos->where('section', 'hero')->values();
      if ($hero->count() > 0) {
        $galleryPhotos = $hero->map(function ($row) {
          return ['file' => $row->url, 'code' => $row->service_code, 'am' => $row->title_am ?: $row->title_en, 'en' => $row->title_en ?: $row->title_am];
        })->all();
      }
      $svcRows = $dbPhotos->where('section', 'service')->values();
      if ($svcRows->count() > 0) {
        $dbServicePhoto = [];
        foreach ($svcRows as $row) {
          if ($row->service_code) $dbServicePhoto[$row->service_code] = $row->url;
        }
        if (!empty($dbServicePhoto)) $servicePhoto = $dbServicePhoto;
      }
      $galRows = $dbPhotos->where('section', 'gallery')->values();
      if ($galRows->count() > 0) {
        $galleryOrder = $galRows->pluck('url')->all();
      }
      $featRows = $dbPhotos->where('section', 'feature')->values();
      if ($featRows->count() > 0) {
        $featurePhotos = $featRows->take(3)->map(function ($row) {
          return ['file' => $row->url, 'am' => $row->title_am ?: $row->title_en, 'en' => $row->title_en ?: $row->title_am];
        })->all();
        // Pad to 3 steps with the fallback so no step ever loses its photo.
        while (count($featurePhotos) < 3) {
          $featurePhotos[] = $fallbackFeaturePhotos[count($featurePhotos)];
        }
      }
      // Gallery lookup must also include gallery-section rows: a photo
      // can be in the gallery without being in the hero slider, and the
      // mosaic below looks every gallery file up in this map.
      $galleryByFile = collect($galleryPhotos)->keyBy('file');
      foreach ($galRows as $row) {
        $galleryByFile[$row->url] = ['file' => $row->url, 'code' => $row->service_code, 'am' => $row->title_am ?: $row->title_en, 'en' => $row->title_en ?: $row->title_am];
      }
    }
  } catch (\Throwable $e) {
    // Fallback lists above stay in place; the site keeps working.
  }
  // Safety net: sections fall back independently, so a gallery file
  // must always resolve in the lookup even if it is not in the hero
  // slider rows (otherwise the mosaic below errors).
  $fallbackByFile = collect($fallbackPhotos)->keyBy('file');
  foreach ($galleryOrder as $gfile) {
    if (!isset($galleryByFile[$gfile])) {
      $galleryByFile[$gfile] = $fallbackByFile[$gfile] ?? ['file' => $gfile, 'code' => null, 'am' => '', 'en' => ''];
    }
  }
@endphp

<main id="main-content">

<!-- ================= HERO ================= -->
<section class="relative bg-gradient-to-b from-teal-light/20 via-transparent to-transparent">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 grid md:grid-cols-[1.15fr_.85fr] gap-6 min-h-[580px] items-center py-12 md:py-16">
    <div class="flex flex-col justify-center pr-0 md:pr-10">
      <div class="inline-flex items-center gap-2 text-teal-dark bg-teal-light/40 border border-teal/20 px-3.5 py-1.5 rounded-full text-xs font-bold tracking-wide mb-5 w-fit">
        <i data-lucide="shield-check" class="w-4 h-4"></i>
        <span data-lang-block="am">በህጋዊ ፈቃድ የታገዘ • 160°C የስቲም ቴክኖሎጂ</span>
        <span data-lang-block="en">Licensed &amp; Insured • 160°C German Steam Tech</span>
      </div>

      <!-- Slogan Badge (STRICTLY IN ENGLISH EVERYWHERE — DO NOT TRANSLATE TO AMHARIC) -->
      <div class="mb-3 text-xs sm:text-sm font-black tracking-wider uppercase text-teal-dark bg-teal-light/40 border border-teal/20 px-3.5 py-1.5 rounded-xl w-fit">
        <span>"Your Environment, Our Responsibility."</span>
      </div>

      <h1 class="font-serif font-bold text-[2.4rem] sm:text-[3.2rem] md:text-[3.6rem] leading-[1.08] tracking-tight text-navy">
        MEASH CLEANING SOLUTION<br><span class="text-teal-dark text-[0.65em] font-bold block mt-1"><span data-lang-block="am">ሜሽ የጽዳት አገልግሎት</span><span data-lang-block="en">Premium Commercial Cleaning Partner</span></span>
      </h1>

      <p class="mt-4 text-teal-dark font-extrabold text-[15px] sm:text-[17px] leading-snug">
        <span data-lang-block="am">{{ $subtitleAm }}</span>
        <span data-lang-block="en">{{ $subtitleEn }}</span>
      </p>

      <p class="mt-3 text-navy/70 text-[14px] sm:text-[15px] leading-relaxed max-w-xl">
        <span data-lang-block="am">ዋና መሥሪያ ቤቱ በአያት አደባባይ የሚገኝ፣ ህጋዊ ፈቃድ ያለው እና ለንግድ ተቋማት ፕሪሚየም የጽዳት አገልግሎት የሚሰጥ ተቋም። በሰለጠኑ ታማኝ ባለሙያዎች እና በዘመናዊ ማሽኖች የተደገፈ ጥልቅ ጽዳት።</span>
        <span data-lang-block="en">Fully licensed and insured premium B2B cleaning partner based at Ayat Square. We deliver exceptional commercial-grade sanitation designed to protect your facility and boost workplace productivity.</span>
      </p>

      <!-- Primary Action Buttons & Click to Call -->
      <div class="mt-8 flex flex-wrap items-center gap-3">
        <a href="#book" class="bg-navy hover:bg-teal-dark text-white font-bold text-sm px-6 py-3.5 rounded-lg transition shadow-md flex items-center gap-2">
          <i data-lucide="sparkles" class="w-4 h-4"></i>
          <span data-lang-block="am">የዋጋ ስሌት እና ቀጠሮ</span><span data-lang-block="en">Get Quote &amp; Book</span>
          <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </a>
        <a href="#commercial" class="border-2 border-teal-dark text-teal-dark hover:bg-teal-dark hover:text-white font-bold text-sm px-5 py-3 rounded-lg transition flex items-center gap-2">
          <i data-lucide="building-2" class="w-4 h-4"></i>
          <span data-lang-block="am">የድርጅቶች ኮንትራት</span><span data-lang-block="en">Commercial Contracts</span>
        </a>
      </div>

      <!-- Quick Contact Pills (Direct Call, WhatsApp & Telegram) -->
      <div class="mt-6 flex flex-wrap items-center gap-3 text-xs font-bold text-navy">
        <span class="text-navy/50 font-medium"><span data-lang-block="am">አሁኑኑ ይደውሉ፡</span><span data-lang-block="en">Call Now:</span></span>
        <a href="tel:{{ $phone1 }}" class="hover:text-teal flex items-center gap-1.5 bg-white dark:bg-slate-800 border border-navy/15 px-3 py-1.5 rounded-md shadow-sm">
          <i data-lucide="phone" class="w-3.5 h-3.5 text-teal"></i> {{ $phone1 }}
        </a>
        <a href="tel:{{ $phone2 }}" class="hover:text-teal flex items-center gap-1.5 bg-white dark:bg-slate-800 border border-navy/15 px-3 py-1.5 rounded-md shadow-sm">
          <i data-lucide="phone" class="w-3.5 h-3.5 text-teal"></i> {{ $phone2 }}
        </a>
        <a href="https://wa.me/251{{ ltrim($phone1, '0') }}?text=Hello%20Meash%20Cleaning%20Solution,%20I%20would%20like%20to%20inquire%20about%20your%20services" target="_blank" class="hover:opacity-90 flex items-center gap-1.5 bg-[#25D366]/15 text-[#128C7E] dark:text-[#25D366] border border-[#25D366]/30 px-3 py-1.5 rounded-md">
          <i data-lucide="message-circle" class="w-3.5 h-3.5 text-[#25D366]"></i> WhatsApp
        </a>
        <a href="{{ $websiteSettings['telegram_bot'] ?? 'https://t.me/meash_cleaning_solution_bot' }}" target="_blank" class="hover:text-teal flex items-center gap-1.5 bg-teal/10 text-teal-dark border border-teal/20 px-3 py-1.5 rounded-md">
          <i data-lucide="send" class="w-3.5 h-3.5"></i> Telegram Bot
        </a>
      </div>

      <!-- Key Trust Metrics -->
      <div class="mt-10 grid grid-cols-3 gap-4 sm:gap-6 border-t border-navy/10 pt-6 max-w-lg">
        <div>
          <div class="font-serif font-bold text-2xl sm:text-3xl text-navy">3,200+</div>
          <div class="text-[12px] text-navy/60 mt-0.5" data-lang-block="am">የተከናወኑ ስራዎች</div>
          <div class="text-[12px] text-navy/60 mt-0.5" data-lang-block="en">Jobs Done</div>
        </div>
        <div>
          <div class="font-serif font-bold text-2xl sm:text-3xl text-navy">4.9<span class="text-base font-normal">/5</span></div>
          <div class="text-[12px] text-navy/60 mt-0.5" data-lang-block="am">የደንበኞች እርካታ</div>
          <div class="text-[12px] text-navy/60 mt-0.5" data-lang-block="en">Rating</div>
        </div>
        <div>
          <div class="font-serif font-bold text-2xl sm:text-3xl text-navy">100%</div>
          <div class="text-[12px] text-navy/60 mt-0.5" data-lang-block="am">የታመኑ ባለሙያዎች</div>
          <div class="text-[12px] text-navy/60 mt-0.5" data-lang-block="en">Vetted Staff</div>
        </div>
      </div>
    </div>

    <!-- Hero Visual — 3D coverflow slider with real Meash work photos -->
    <div class="relative">
      <div class="ms-hero-panel">
        <div class="ms-stage" id="ms-stage" role="region" aria-roledescription="carousel" aria-label="Meash cleaning work photos" tabindex="0">
          @foreach($galleryPhotos as $gi => $p)
            @php $slideSvc = $activeServices->firstWhere('code', $p['code']); @endphp
            <figure class="ms-slide" role="group" aria-label="{{ $gi + 1 }} of {{ count($galleryPhotos) }}" data-ms-slide="{{ $gi }}">
              <img src="{{ $photoSrc($p['file']) }}" alt="{{ $p['en'] }}" @if($gi > 0) loading="lazy" @endif>
              <figcaption>
                <span class="ms-cap-name"><span data-lang-block="am">{{ $p['am'] }}</span><span data-lang-block="en">{{ $p['en'] }}</span></span>
                @if($slideSvc)
                  <span class="ms-cap-price svc-price" data-svc-price="{{ $slideSvc->id }}" data-svc-unit="{{ $slideSvc->unit ?: 'service' }}">{{ number_format($slideSvc->base_price) }} ETB{{ ($slideSvc->unit && $slideSvc->unit !== 'service') ? ' / ' . $slideSvc->unit : '' }}</span>
                @endif
              </figcaption>
            </figure>
          @endforeach
          <div class="ms-stage-nav">
            <button class="ms-arrow" id="ms-prev-btn" type="button" aria-label="Previous photo">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 5.5-6 6.5 6 6.5"/></svg>
            </button>
            <div class="ms-dots" id="ms-dots" role="tablist" aria-label="Choose photo"></div>
            <button class="ms-arrow" id="ms-next-btn" type="button" aria-label="Next photo">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9.5 5.5 6 6.5-6 6.5"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= LOGO/TRUST STRIP ================= -->
<section class="border-y border-navy/10 bg-fog">
  <div class="max-w-7xl mx-auto px-6 py-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-3 text-navy/60 text-xs font-bold tracking-wide">
    <span class="flex items-center gap-2"><i data-lucide="shield-check" class="w-4 h-4 text-teal"></i> <span data-lang-block="am">ህጋዊ ፈቃድ ያለው ድርጅት</span><span data-lang-block="en">Licensed &amp; Insured Facility Service</span></span>
    <span class="flex items-center gap-2"><i data-lucide="users" class="w-4 h-4 text-teal"></i> <span data-lang-block="am">የሰለጠኑ ቋሚ ባለሙያዎች</span><span data-lang-block="en">Vetted &amp; Certified Staff</span></span>
    <span class="flex items-center gap-2"><i data-lucide="sparkles" class="w-4 h-4 text-teal"></i> <span data-lang-block="am">ደረጃቸውን የጠበቁ ማሽኖች</span><span data-lang-block="en">Hospital-Grade Equipment</span></span>
    <span class="flex items-center gap-2"><i data-lucide="clock" class="w-4 h-4 text-teal"></i> <span data-lang-block="am">ተለዋዋጭ የስራ ሰዓት</span><span data-lang-block="en">Flexible Scheduling (7 Days)</span></span>
    <span class="flex items-center gap-2"><i data-lucide="building-2" class="w-4 h-4 text-teal"></i> <span data-lang-block="am">የተቋማት ዓመታዊ ኮንትራት</span><span data-lang-block="en">Corporate Facility Contracts</span></span>
  </div>
</section>

<!-- ================= ABOUT US SECTION ================= -->
<section id="about" class="py-20 bg-white border-b border-navy/10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 grid lg:grid-cols-2 gap-12 items-center">
    <div>
      <div class="inline-flex items-center gap-2 text-teal-dark font-bold text-xs tracking-wider uppercase mb-3 bg-teal/10 px-3 py-1 rounded-full">
        <i data-lucide="info" class="w-3.5 h-3.5"></i>
        <span data-lang-block="am">ስለ ድርጅታችን</span><span data-lang-block="en">ABOUT MEASH CLEANING</span>
      </div>
      <h2 class="font-serif font-bold text-3xl sm:text-4xl text-navy leading-tight mb-6">
        <span class="block text-teal-dark font-mono text-xs uppercase tracking-widest mb-1">"Your Environment, Our Responsibility."</span>
        <span data-lang-block="am">ሜሽ የጽዳት አገልግሎት<br><span class="text-teal-dark font-normal text-[0.8em]">ለተቋማትና ንግድ ቦታዎች ፕሪሚየም አጋር</span></span>
        <span data-lang-block="en">MEASH CLEANING SOLUTION<br><span class="text-teal-dark font-normal text-[0.8em]">Licensed &amp; Insured Premium B2B Partner</span></span>
      </h2>
      <div class="text-navy/75 text-[15px] sm:text-[16px] leading-relaxed space-y-4">
        <p data-lang-block="am" class="font-medium">
          ሜሽ የጽዳት አገልግሎት (MEASH CLEANING) ዋና መሥሪያ ቤቱ በአያት አደባባይ የሚገኝ፣ ህጋዊ ፈቃድ ያለው እና ለንግድ ተቋማት ፕሪሚየም የጽዳት አገልግሎት የሚሰጥ ተቋም ነው። የሥራ ቦታዎን ንፅህና ለድርጅትዎ ስምና ክብር፣ እንዲሁም ለሰራተኞችዎ ጤና እና ምርታማነት ወሳኝ መሆኑን ተረድተን በላቀ ጥራት እንሰራለን።
        </p>
        <p data-lang-block="en" class="font-medium">
          Meash Cleaning Solution is a fully licensed and insured premium B2B cleaning partner based at Ayat Square. We deliver exceptional commercial-grade sanitation services designed to protect your facility, project a flawless corporate image, and boost workplace productivity.
        </p>
      </div>

      <!-- Feature Checklist -->
      <div class="mt-8 grid sm:grid-cols-2 gap-4">
        <div class="flex items-start gap-3">
          <i data-lucide="check-circle" class="w-5 h-5 text-teal shrink-0 mt-0.5"></i>
          <div>
            <h4 class="font-bold text-sm text-navy"><span data-lang-block="am">ህጋዊ ፈቃድና የንብረት ዋስትና</span><span data-lang-block="en">Fully Licensed &amp; Insured</span></h4>
            <p class="text-xs text-navy/60"><span data-lang-block="am">ለድርጅትዎ እቃዎችና ንብረቶች ሙሉ ጥበቃ</span><span data-lang-block="en">Total asset protection for your corporate facilities</span></p>
          </div>
        </div>
        <div class="flex items-start gap-3">
          <i data-lucide="check-circle" class="w-5 h-5 text-teal shrink-0 mt-0.5"></i>
          <div>
            <h4 class="font-bold text-sm text-navy"><span data-lang-block="am">ቀላልና ተለዋዋጭ የቀጠሮ አያያዝ</span><span data-lang-block="en">Flexible Shift Scheduling</span></h4>
            <p class="text-xs text-navy/60"><span data-lang-block="am">የሌሊት ፈረቃ እና የሳምንቱ መጨረሻ ስራዎችን ጨምሮ</span><span data-lang-block="en">Night shifts &amp; weekends with zero business downtime</span></p>
          </div>
        </div>
        <div class="flex items-start gap-3">
          <i data-lucide="check-circle" class="w-5 h-5 text-teal shrink-0 mt-0.5"></i>
          <div>
            <h4 class="font-bold text-sm text-navy"><span data-lang-block="am">ነፃ የቦታ ምልከታና ፕሮፎርማ</span><span data-lang-block="en">Prompt On-Site Inspection</span></h4>
            <p class="text-xs text-navy/60"><span data-lang-block="am">ትክክለኛውን ግምት ለመስጠት አስቀድመን በነፃ እንገመግማለን</span><span data-lang-block="en">Accurate, transparent quotations with zero guesswork</span></p>
          </div>
        </div>
        <div class="flex items-start gap-3">
          <i data-lucide="check-circle" class="w-5 h-5 text-teal shrink-0 mt-0.5"></i>
          <div>
            <h4 class="font-bold text-sm text-navy"><span data-lang-block="am">የተቋማትና የቢሮ ኮንትራት ውል</span><span data-lang-block="en">Commercial Retainers &amp; SLAs</span></h4>
            <p class="text-xs text-navy/60"><span data-lang-block="am">ህጋዊ የቫት ደረሰኝ፣ ፕሮፎርማ እና የረጅም ጊዜ ስምምነት</span><span data-lang-block="en">Official VAT invoicing, SLAs &amp; dedicated supervisors</span></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Company Badge Box -->
    <div class="bg-gradient-to-br from-slate-900 to-navy text-white rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden">
      <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-teal/20 rounded-full blur-3xl pointer-events-none"></div>
      <div class="flex items-center gap-3 mb-6">
        <img src="/logo.jpg" alt="Logo" class="w-12 h-12 rounded-xl object-cover border border-white/20">
        <div>
          <h3 class="font-serif font-bold text-xl">MEASH CLEANING SOLUTION</h3>
          <p class="text-xs text-teal-light font-bold tracking-wider">"Your Environment, Our Responsibility."</p>
        </div>
      </div>

      <div class="space-y-5 text-sm">
        <div class="border-b border-white/10 pb-3">
          <div class="text-xs text-white/60 mb-1"><span data-lang-block="am">ዋና መስሪያ ቤት አድራሻ</span><span data-lang-block="en">Head Office Location</span></div>
          <div class="font-semibold flex items-center gap-2">
            <i data-lucide="map-pin" class="w-4 h-4 text-teal"></i>
            <span>{{ $addressAm }} • {{ $addressEn }}</span>
          </div>
        </div>

        <div class="border-b border-white/10 pb-3">
          <div class="text-xs text-white/60 mb-1"><span data-lang-block="am">ቀጥታ ስልክ መስመሮች (24/7)</span><span data-lang-block="en">Direct Hotlines</span></div>
          <div class="flex flex-wrap gap-3 mt-1.5">
            <a href="tel:{{ $phone1 }}" class="font-bold text-white bg-white/10 hover:bg-white/20 px-3.5 py-1.5 rounded-lg transition flex items-center gap-2">
              <i data-lucide="phone" class="w-3.5 h-3.5 text-teal"></i> {{ $phone1 }}
            </a>
            <a href="tel:{{ $phone2 }}" class="font-bold text-white bg-white/10 hover:bg-white/20 px-3.5 py-1.5 rounded-lg transition flex items-center gap-2">
              <i data-lucide="phone" class="w-3.5 h-3.5 text-teal"></i> {{ $phone2 }}
            </a>
          </div>
        </div>

        <div class="border-b border-white/10 pb-3">
          <div class="text-xs text-white/60 mb-1"><span data-lang-block="am">ኢሜይል (Official Email)</span><span data-lang-block="en">Official Email</span></div>
          <a href="mailto:{{ $email }}" class="font-semibold text-teal-light hover:underline flex items-center gap-2">
            <i data-lucide="mail" class="w-4 h-4 text-teal"></i> {{ $email }}
          </a>
        </div>

        <div>
          <div class="text-xs text-white/60 mb-1"><span data-lang-block="am">ማህበራዊ ትስስር ገጾች ({{ $socialHandle }})</span><span data-lang-block="en">Social Channels</span></div>
          <div class="flex flex-wrap items-center gap-2 mt-1.5">
            <a href="{{ $tiktokUrl }}" target="_blank" class="text-xs bg-white/10 hover:bg-teal px-3 py-1.5 rounded-md transition flex items-center gap-1.5">
              <i data-lucide="video" class="w-3.5 h-3.5"></i> TikTok
            </a>
            <a href="{{ $instagramUrl }}" target="_blank" class="text-xs bg-white/10 hover:bg-teal px-3 py-1.5 rounded-md transition flex items-center gap-1.5">
              <i data-lucide="camera" class="w-3.5 h-3.5"></i> Instagram
            </a>
            <a href="{{ $facebookUrl }}" target="_blank" class="text-xs bg-white/10 hover:bg-teal px-3 py-1.5 rounded-md transition flex items-center gap-1.5">
              <i data-lucide="facebook" class="w-3.5 h-3.5"></i> Facebook
            </a>
            <a href="{{ $websiteSettings['telegram_channel'] ?? 'https://t.me/meashcleaningsolution' }}" target="_blank" class="text-xs bg-white/10 hover:bg-teal px-3 py-1.5 rounded-md transition flex items-center gap-1.5" title="Telegram Channel">
              <i data-lucide="send" class="w-3.5 h-3.5"></i> Channel
            </a>
            <a href="{{ $websiteSettings['telegram_support_url'] ?? 'https://t.me/meashcleaning' }}" target="_blank" class="text-xs bg-white/10 hover:bg-teal px-3 py-1.5 rounded-md transition flex items-center gap-1.5" title="Telegram Contact">
              <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> @meashcleaning
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= WHY MEASH? THE MEASH ADVANTAGE (5 PILLARS) ================= -->
<section id="advantage" class="py-20 bg-fog border-b border-navy/10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="text-center max-w-2xl mx-auto mb-14">
      <div class="inline-flex items-center gap-2 text-teal-dark font-bold text-xs tracking-wider uppercase mb-3 bg-teal/10 px-3.5 py-1.5 rounded-full">
        <i data-lucide="award" class="w-3.5 h-3.5"></i>
        <span data-lang-block="am">ለምን ሜሽን ይመርጣሉ?</span><span data-lang-block="en">WHY MEASH? — THE MEASH ADVANTAGE</span>
      </div>
      <h2 class="font-serif font-bold text-3xl sm:text-4xl text-navy leading-tight">
        <span data-lang-block="am">የሜሽ 5ቱ የላቁ መለያዎች (5 Core Pillars)</span>
        <span data-lang-block="en">5 Pillars of Cleaning Excellence</span>
      </h2>
      <p class="text-navy/70 text-sm sm:text-base mt-2">
        <span data-lang-block="am">ንግድዎን፣ ንብረትዎን እና ሰራተኞችዎን የሚጠብቅ አስተማማኝ አገልግሎት እናቀርባለን።</span>
        <span data-lang-block="en">Commercial-grade sanitation designed to protect your facility, project a flawless corporate image, and boost workplace productivity.</span>
      </p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-5">
      <!-- 1. Certified & Vetted Teams -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-navy/10 hover:border-teal transition shadow-sm hover:shadow-md flex flex-col justify-between">
        <div>
          <div class="w-11 h-11 rounded-xl bg-teal/10 text-teal-dark flex items-center justify-center mb-4">
            <i data-lucide="users-round" class="w-6 h-6"></i>
          </div>
          <h3 class="font-serif font-bold text-base text-navy dark:text-white mb-2">
            <span data-lang-block="am">1. የሰለጠኑና የታመኑ ባለሙያዎች</span>
            <span data-lang-block="en">1. Certified &amp; Vetted Teams</span>
          </h3>
          <p class="text-xs text-navy/70 dark:text-slate-300 leading-relaxed">
            <span data-lang-block="am">ስነ-ምግባር እና ጥራትን የተላበሱ፣ ሙሉ የደንብ ልብስ የለበሱ ባለሙያዎች።</span>
            <span data-lang-block="en">Uniformed, background-checked, and rigorously trained professionals.</span>
          </p>
        </div>
      </div>

      <!-- 2. Eco-Friendly & Safe -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-navy/10 hover:border-teal transition shadow-sm hover:shadow-md flex flex-col justify-between">
        <div>
          <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center mb-4">
            <i data-lucide="leaf" class="w-6 h-6"></i>
          </div>
          <h3 class="font-serif font-bold text-base text-navy dark:text-white mb-2">
            <span data-lang-block="am">2. ደህንነታቸው የተጠበቀ ኬሚካሎች</span>
            <span data-lang-block="en">2. Eco-Friendly &amp; Safe</span>
          </h3>
          <p class="text-xs text-navy/70 dark:text-slate-300 leading-relaxed">
            <span data-lang-block="am">ለጤና እና ለንብረት ፍጹም ጉዳት የማያደርሱ ኬሚካሎችን እንጠቀማለን።</span>
            <span data-lang-block="en">We use hospital-grade, non-toxic, and surface-safe premium chemicals.</span>
          </p>
        </div>
      </div>

      <!-- 3. Flexible & Discrete -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-navy/10 hover:border-teal transition shadow-sm hover:shadow-md flex flex-col justify-between">
        <div>
          <div class="w-11 h-11 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center mb-4">
            <i data-lucide="moon-star" class="w-6 h-6"></i>
          </div>
          <h3 class="font-serif font-bold text-base text-navy dark:text-white mb-2">
            <span data-lang-block="am">3. ምቹ የሥራ መርሐ-ግብር</span>
            <span data-lang-block="en">3. Flexible &amp; Discrete</span>
          </h3>
          <p class="text-xs text-navy/70 dark:text-slate-300 leading-relaxed">
            <span data-lang-block="am">የእርስዎን የሥራ ሰዓት በማይጋፋ መልኩ ለእርስዎ የሚመች ሰዓት (በሌሊት ወይም በሳምንቱ መጨረሻ) መስራት መቻላችን።</span>
            <span data-lang-block="en">Customized scheduling (night shifts/weekends) to eliminate business downtime.</span>
          </p>
        </div>
      </div>

      <!-- 4. Fully Insured -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-navy/10 hover:border-teal transition shadow-sm hover:shadow-md flex flex-col justify-between">
        <div>
          <div class="w-11 h-11 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center mb-4">
            <i data-lucide="shield-check" class="w-6 h-6"></i>
          </div>
          <h3 class="font-serif font-bold text-base text-navy dark:text-white mb-2">
            <span data-lang-block="am">4. የንብረት ዋስትና</span>
            <span data-lang-block="en">4. Fully Insured</span>
          </h3>
          <p class="text-xs text-navy/70 dark:text-slate-300 leading-relaxed">
            <span data-lang-block="am">በጽዳት ወቅት በቢሮዎ እቃዎች ላይ ለሚደርስ ለማንኛውም ጉዳት ሙሉ የዋስትና ሽፋን (Insurance) እንሰጣለን።</span>
            <span data-lang-block="en">Your high-end office furniture and assets are completely safe with us.</span>
          </p>
        </div>
      </div>

      <!-- 5. No Estimation Guesswork -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-navy/10 hover:border-teal transition shadow-sm hover:shadow-md flex flex-col justify-between">
        <div>
          <div class="w-11 h-11 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center mb-4">
            <i data-lucide="clipboard-check" class="w-6 h-6"></i>
          </div>
          <h3 class="font-serif font-bold text-base text-navy dark:text-white mb-2">
            <span data-lang-block="am">5. ትክክለኛ ግምገማ (ነፃ ምልከታ)</span>
            <span data-lang-block="en">5. No Guesswork (Free Quote)</span>
          </h3>
          <p class="text-xs text-navy/70 dark:text-slate-300 leading-relaxed">
            <span data-lang-block="am">ትክክለኛውን ዋጋ (Proforma) ለማቅረብ አስቀድመን በነፃ በቦታው ድረስ በመገኘት እንገመግማለን።</span>
            <span data-lang-block="en">Free, prompt on-site technical inspection before any quotation.</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= COMMERCIAL CLEANING SERVICES (DEDICATED SECTION) ================= -->
<section id="commercial" class="py-24 bg-fog border-b border-navy/10 relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="max-w-2xl mb-16">
      <div class="inline-flex items-center gap-2 text-teal-dark font-bold text-xs tracking-wider uppercase mb-3 bg-teal/10 px-3.5 py-1.5 rounded-full">
        <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
        <span data-lang-block="am">ለድርጅቶች፣ ለሆቴሎች እና ለተቋማት</span><span data-lang-block="en">COMMERCIAL &amp; CONTRACT CLEANING</span>
      </div>
      <h2 class="font-serif font-bold text-3xl sm:text-4xl text-navy leading-tight">
        <span data-lang-block="am">የተቋማት እና የንግድ ቦታዎች ጥልቅ ጽዳት</span>
        <span data-lang-block="en">Commercial &amp; Facility Cleaning Solutions</span>
      </h2>
      <p class="text-navy/70 text-base mt-3">
        <span data-lang-block="am">ለቢሮዎች፣ ለሆቴሎች፣ ለሬስቶራንቶች፣ ለካፌዎች እና ለስብሰባ አዳራሾች አስተማማኝ የረጅም ጊዜ የኮንትራት ወይም የጊዜያዊ ጽዳት ውል። ህጋዊ ደረሰኝ እና የቫት ሰነድ እናቀርባለን።</span>
        <span data-lang-block="en">Tailored recurring contracts and deep turnaround sanitization for corporations, hospitality chains, dining spots, and auditorium venues across Addis Ababa.</span>
      </p>
    </div>

    <!-- Commercial Grid of 6 Specialized Sectors -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- 1. Office Cleaning -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-7 border border-navy/10 hover:border-teal transition shadow-sm hover:shadow-lg flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-teal/10 text-teal-dark flex items-center justify-center mb-5">
            <i data-lucide="briefcase" class="w-6 h-6"></i>
          </div>
          <h3 class="font-serif font-bold text-xl text-navy mb-2">
            <span data-lang-block="am">የቢሮ እና የስራ ቦታ ጽዳት</span><span data-lang-block="en">Office &amp; Corporate Cleaning</span>
          </h3>
          <p class="text-sm text-navy/65 leading-relaxed">
            <span data-lang-block="am">የቢሮ ጠረጴዛዎች፣ ወንበሮች፣ ምንጣፍ በሮተሪ ማሽን ማጠብ፣ የኮምፒውተር ገመዶች ጽዳት እና የሽንት ቤት ጥልቅ ማጽዳት።</span>
            <span data-lang-block="en">Executive desks, partitioned workstations, carpet extraction, restrooms sanitization &amp; trash collection.</span>
          </p>
        </div>
        <div class="mt-6 pt-5 border-t border-navy/10 flex items-center justify-between">
          <span class="text-xs font-bold text-teal-dark"><span data-lang-block="am">ተለዋዋጭ ሰዓት (ምሽት/ቅዳሜ)</span><span data-lang-block="en">After-Hours / Weekends</span></span>
          <a href="#book" onclick="preselectService('office')" class="text-xs font-bold bg-navy text-white px-3 py-1.5 rounded hover:bg-teal transition">ውል ይያዙ</a>
        </div>
      </div>

      <!-- 2. Hotel Cleaning -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-7 border border-navy/10 hover:border-teal transition shadow-sm hover:shadow-lg flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-teal/10 text-teal-dark flex items-center justify-center mb-5">
            <i data-lucide="hotel" class="w-6 h-6"></i>
          </div>
          <h3 class="font-serif font-bold text-xl text-navy mb-2">
            <span data-lang-block="am">የሆቴል እና ሎጅ ጽዳት</span><span data-lang-block="en">Hotel &amp; Hospitality Sanitization</span>
          </h3>
          <p class="text-sm text-navy/65 leading-relaxed">
            <span data-lang-block="am">የእንግዳ ማረፊያ ክፍሎች፣ አልጋዎች፣ ፍራሽ በስቲም ማጽዳት፣ ኮሪደር ምንጣፍ እጥበት እና የሎቢ ወለል ማሽን እጥበት።</span>
            <span data-lang-block="en">Guest room deep clean, steam mattress disinfection, corridor carpet cleaning and marble lobby polishing.</span>
          </p>
        </div>
        <div class="mt-6 pt-5 border-t border-navy/10 flex items-center justify-between">
          <span class="text-xs font-bold text-teal-dark"><span data-lang-block="am">የቱሪዝም ደረጃ ጥራት</span><span data-lang-block="en">Hospitality Standards</span></span>
          <a href="#book" onclick="preselectService('hotel_clean')" class="text-xs font-bold bg-navy text-white px-3 py-1.5 rounded hover:bg-teal transition">ውል ይያዙ</a>
        </div>
      </div>

      <!-- 3. Restaurant & Cafe -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-7 border border-navy/10 hover:border-teal transition shadow-sm hover:shadow-lg flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-teal/10 text-teal-dark flex items-center justify-center mb-5">
            <i data-lucide="coffee" class="w-6 h-6"></i>
          </div>
          <h3 class="font-serif font-bold text-xl text-navy mb-2">
            <span data-lang-block="am">የሬስቶራንት እና ካፌ ጽዳት</span><span data-lang-block="en">Restaurant &amp; Café Cleaning</span>
          </h3>
          <p class="text-sm text-navy/65 leading-relaxed">
            <span data-lang-block="am">የንግድ ኩሽና ዘይት ማቅለጥና ማስወገድ (Degreasing)፣ የወንበሮችና የሶፋዎች እጥበት፣ የወለል ሴራሚክ እጥበት።</span>
            <span data-lang-block="en">Heavy kitchen hood degreasing, dining chair upholstery, ceramic grout scrub &amp; food-safe sanitizers.</span>
          </p>
        </div>
        <div class="mt-6 pt-5 border-t border-navy/10 flex items-center justify-between">
          <span class="text-xs font-bold text-teal-dark"><span data-lang-block="am">ጤና ጥበቃ ደረጃ</span><span data-lang-block="en">Food-Safe Chemicals</span></span>
          <a href="#book" onclick="preselectService('restaurant_clean')" class="text-xs font-bold bg-navy text-white px-3 py-1.5 rounded hover:bg-teal transition">ውል ይያዙ</a>
        </div>
      </div>

      <!-- 4. Hall & Commercial Chair -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-7 border border-navy/10 hover:border-teal transition shadow-sm hover:shadow-lg flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-teal/10 text-teal-dark flex items-center justify-center mb-5">
            <i data-lucide="armchair" class="w-6 h-6"></i>
          </div>
          <h3 class="font-serif font-bold text-xl text-navy mb-2">
            <span data-lang-block="am">የአዳራሽና ወንበሮች እጥበት</span><span data-lang-block="en">Auditorium &amp; Hall Chairs</span>
          </h3>
          <p class="text-sm text-navy/65 leading-relaxed">
            <span data-lang-block="am">የስብሰባ አዳራሾች፣ ሲኒማዎች፣ ቤተ-እምነቶች ወንበሮችን በጅምላ በቦታው ላይ በፈጣን ማሽን ማጠብ እና ማድረቅ።</span>
            <span data-lang-block="en">High-volume fabric &amp; leather seat shampoo extraction for cinemas, churches &amp; conference halls.</span>
          </p>
        </div>
        <div class="mt-6 pt-5 border-t border-navy/10 flex items-center justify-between">
          <span class="text-xs font-bold text-teal-dark"><span data-lang-block="am">በጅምላ ቅናሽ ያለው</span><span data-lang-block="en">Volume Discounts</span></span>
          <a href="#book" onclick="preselectService('hall_chair_clean')" class="text-xs font-bold bg-navy text-white px-3 py-1.5 rounded hover:bg-teal transition">ውል ይያዙ</a>
        </div>
      </div>

      <!-- 5. Commercial Glass Facade -->
      <div class="bg-white dark:bg-slate-800 rounded-2xl p-7 border border-navy/10 hover:border-teal transition shadow-sm hover:shadow-lg flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-teal/10 text-teal-dark flex items-center justify-center mb-5">
            <i data-lucide="sun" class="w-6 h-6"></i>
          </div>
          <h3 class="font-serif font-bold text-xl text-navy mb-2">
            <span data-lang-block="am">የፎቅ እና መስታወት ጽዳት</span><span data-lang-block="en">Commercial Glass Facades</span>
          </h3>
          <p class="text-sm text-navy/65 leading-relaxed">
            <span data-lang-block="am">የህንፃዎች የውጭና የውስጥ መስታወት፣ የሾፕ ፍሮንት (Showrooms) እና የባንኮች መስታወት ጥርት ያለ እጥበት።</span>
            <span data-lang-block="en">Streak-free pure water window washing, bank branch facades, automotive showrooms &amp; multistory glass.</span>
          </p>
        </div>
        <div class="mt-6 pt-5 border-t border-navy/10 flex items-center justify-between">
          <span class="text-xs font-bold text-teal-dark"><span data-lang-block="am">ጭረት የማይተው እጥበት</span><span data-lang-block="en">Streak-Free</span></span>
          <a href="#book" onclick="preselectService('glass')" class="text-xs font-bold bg-navy text-white px-3 py-1.5 rounded hover:bg-teal transition">ውል ይያዙ</a>
        </div>
      </div>

      <!-- 6. Long-Term Contracts -->
      <div class="bg-gradient-to-br from-navy to-slate-900 text-white rounded-2xl p-7 border border-teal/30 shadow-xl flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-xl bg-teal text-white flex items-center justify-center mb-5">
            <i data-lucide="file-check-2" class="w-6 h-6"></i>
          </div>
          <h3 class="font-serif font-bold text-xl text-white mb-2">
            <span data-lang-block="am">ቋሚ የጽዳት ኮንትራት ውል</span><span data-lang-block="en">Long-Term Cleaning SLAs</span>
          </h3>
          <p class="text-sm text-white/80 leading-relaxed">
            <span data-lang-block="am">ሳምንታዊ፣ በየ 15 ቀኑ ወይም ወርሃዊ ቋሚ የጽዳት ውል ከቋሚ ሰራተኞች እና ክትትል ጋር። ለኤምባሲዎች፣ ባንኮች እና ድርጅቶች።</span>
            <span data-lang-block="en">Dedicated daily/weekly cleaners, site supervisors, and guaranteed SLA replacements for NGOs &amp; banks.</span>
          </p>
        </div>
        <div class="mt-6 pt-5 border-t border-white/20 flex items-center justify-between">
          <span class="text-xs font-bold text-teal-light"><span data-lang-block="am">እስከ 15% የኮንትራት ቅናሽ</span><span data-lang-block="en">Up to 15% Off</span></span>
          <a href="tel:{{ $phone1 }}" class="text-xs font-bold bg-teal text-white px-3.5 py-1.5 rounded hover:bg-teal-dark transition flex items-center gap-1">
            <i data-lucide="phone-call" class="w-3 h-3"></i> ደውለው ይወያዩ
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= CORE SERVICES (OFFICIAL 6 SERVICES) ================= -->
<section id="services" class="max-w-7xl mx-auto px-4 sm:px-6 py-24">
  <div class="max-w-2xl mb-14">
    <div class="inline-flex items-center gap-2 text-teal-dark font-bold text-xs tracking-wider uppercase mb-3 bg-teal/10 px-3.5 py-1.5 rounded-full">
      <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
      <span data-lang-block="am">ዋና ዋና አገልግሎቶቻችን</span><span data-lang-block="en">OUR CORE SERVICES</span>
    </div>
    <h2 class="font-serif font-bold text-3xl md:text-4xl text-navy leading-tight">
      <span data-lang-block="am">ሜሽ የሚሰጣቸው 6ቱ ይፋዊ አገልግሎቶች</span>
      <span data-lang-block="en">6 Core Commercial &amp; Deep Cleaning Solutions</span>
    </h2>
    <p class="text-navy/70 text-sm sm:text-base mt-2">
      <span data-lang-block="am">ለሆቴሎች፣ ሬስቶራንቶች፣ ካፌዎች፣ ቢሮዎች እና መኖሪያ ቤቶች በከፍተኛ ጥራት የሚሰጡ አገልግሎቶች።</span>
      <span data-lang-block="en">Specialized sanitation for Hotels, Restaurants, Cafés, Corporate Offices, and Residential spaces.</span>
    </p>
  </div>

  <div id="services-list" class="ms-svc-grid">
    @forelse($activeServices as $idx => $svc)
      @php
        $svcNum = $idx + 1;
        $unitLabel = ($svc->unit && $svc->unit !== 'service') ? ' / ' . $svc->unit : '';
        $svcPhoto = $servicePhoto[$svc->code] ?? null;
        $iconName = $svc->icon ?: match($svc->code) {
          'floor_ceramic' => 'sparkles',
          'facilities_general' => 'building-2',
          'kitchen_dining' => 'utensils',
          'post_construction' => 'home',
          'glass' => 'sun',
          'upholstery_carpet' => 'sofa',
          default => 'sparkles'
        };
      @endphp
      <article class="ms-svc bg-white dark:bg-slate-800 border border-navy/10 dark:border-white/10">
        @if($svcPhoto)
          <div class="ms-ph"><img src="{{ $photoSrc($svcPhoto) }}" alt="{{ $svc->name_en ?: $svc->name_am }}" loading="lazy"></div>
        @else
          <div class="ms-ph flex items-center justify-center bg-teal/10 text-teal-dark"><i data-lucide="{{ $iconName }}" class="w-14 h-14"></i></div>
        @endif
        <div class="ms-bd">
          <span class="ms-tag"><span data-lang-block="am">አገልግሎት {{ $svcNum }}</span><span data-lang-block="en">Service {{ $svcNum }}</span></span>
          <h3 class="font-serif font-bold text-xl text-navy dark:text-white">
            <span data-lang-block="am">{{ $svc->name_am ?: $svc->name_en }}</span>
            <span data-lang-block="en">{{ $svc->name_en ?: $svc->name_am }}</span>
          </h3>
          <p class="text-navy/65 dark:text-slate-300 text-sm mt-1 flex-1">
            <span data-lang-block="am">{{ $svc->description_am ?: $svc->description_en }}</span>
            <span data-lang-block="en">{{ $svc->description_en ?: $svc->description_am }}</span>
          </p>
          <div class="flex items-center justify-between flex-wrap gap-3 pt-2">
            <div class="font-serif font-bold text-lg text-teal-dark dark:text-teal svc-price" data-svc-price="{{ $svc->id }}" data-svc-unit="{{ $svc->unit ?: 'service' }}">{{ number_format($svc->base_price) }} ETB{{ $unitLabel }}</div>
            <a href="#book" onclick="preselectService('{{ $svc->code }}')" class="text-xs font-bold bg-navy text-white hover:bg-teal px-4 py-2 rounded-lg transition flex items-center gap-1.5 shrink-0 cursor-pointer">
              <span data-lang-block="am">ቀጠሮ ያዙ</span><span data-lang-block="en">Book Now</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
          </div>
        </div>
      </article>
    @empty
      <!-- Fallback when database is empty (price is filled live by renderPrices(); no hardcoded price) -->
      <article class="ms-svc bg-white dark:bg-slate-800 border border-navy/10 dark:border-white/10">
        <div class="ms-ph flex items-center justify-center bg-teal/10 text-teal-dark"><i data-lucide="sparkles" class="w-14 h-14"></i></div>
        <div class="ms-bd">
          <h3 class="font-serif font-bold text-xl text-navy dark:text-white">
            <span data-lang-block="am">1. የሴራሚክ እና የእንጨት ወለሎች ጥልቅ ጽዳት</span>
            <span data-lang-block="en">1. Ceramic &amp; Hardwood Floor Cleaning</span>
          </h3>
          <p class="text-navy/65 dark:text-slate-300 text-sm mt-1 flex-1">
            <span data-lang-block="am">የሴራሚክ፣ የግራናይት እና የእንጨት ወለሎች ጥልቅ የማሽን እጥበት፣ ነጠብጣብ ማስወገድ እና የወለል ማደስ።</span>
            <span data-lang-block="en">Deep tile &amp; grout restoration, single-disc rotary scrub, and hardwood revitalization.</span>
          </p>
          <div class="flex items-center justify-between flex-wrap gap-3 pt-2">
            <div class="font-serif font-bold text-lg text-teal-dark dark:text-teal svc-price" data-svc-price="1" data-svc-unit="sqm">&mdash;</div>
            <a href="#book" onclick="preselectService('floor_ceramic')" class="text-xs font-bold bg-navy text-white hover:bg-teal px-4 py-2 rounded-lg transition flex items-center gap-1.5 shrink-0 cursor-pointer">
              <span data-lang-block="am">ቀጠሮ ያዙ</span><span data-lang-block="en">Book Now</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
          </div>
        </div>
      </article>
    @endforelse
    <!-- Contract CTA card (from the approved sample) -->
    <article class="ms-svc ms-svc-cta">
      <div class="ms-bd">
        <h3 class="font-serif font-bold text-xl"><span data-lang-block="am">በየሳምንቱ ጽዳት ይፈልጋሉ?</span><span data-lang-block="en">Need cleaning every week?</span></h3>
        <p class="text-sm"><span data-lang-block="am">ለቢሮዎች፣ ሆቴሎች፣ ግቢዎች እና ሕንፃዎች የሚደገም የጽዳት እቅዶች — ተመሳሳይ ቡድን በመደበኛ መርሃ ግብር።</span><span data-lang-block="en">Recurring cleaning plans for offices, hotels, compounds and buildings — the same crew on a regular schedule.</span></p>
        <a href="#commercial" class="inline-flex items-center justify-center gap-2 bg-teal hover:bg-teal-dark text-white font-bold text-sm px-5 py-3 rounded-lg transition w-fit"><span data-lang-block="am">ኮንትራት ያግኙ</span><span data-lang-block="en">Get a Contract</span></a>
      </div>
    </article>
  </div>
</section>


<!-- ================= HOW WE DELIVER (3-STEP, from approved modern sample) ================= -->
<section id="how" class="py-20 bg-white dark:bg-slate-900 border-b border-navy/10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="max-w-2xl mb-10">
      <div class="inline-flex items-center gap-2 text-teal-dark font-bold text-xs tracking-wider uppercase mb-3 bg-teal/10 px-3.5 py-1.5 rounded-full">
        <i data-lucide="workflow" class="w-3.5 h-3.5"></i>
        <span data-lang-block="am">ቀላል ሂደት</span><span data-lang-block="en">SIMPLE PROCESS</span>
      </div>
      <h2 class="font-serif font-bold text-3xl md:text-4xl text-navy dark:text-white leading-tight">
        <span data-lang-block="am">ፍጹም ጽዳት እንዴት እናደርሳለን</span>
        <span data-lang-block="en">How we deliver a perfect clean</span>
      </h2>
    </div>
    <div class="ms-steps">
      <div class="ms-step">
        <span class="ms-num font-serif">01.</span>
        <h3 class="font-bold text-lg text-navy dark:text-white mt-2 mb-1.5"><span data-lang-block="am">አገልግሎትዎን ይዘዙ</span><span data-lang-block="en">Book your service</span></h3>
        <p class="text-navy/65 dark:text-slate-300 text-sm"><span data-lang-block="am">አገልግሎቱን፣ ቀኑን እና ቦታውን ይምረጡ። መጠኑን ይንገሩን — በካሬ ሜትር፣ በወንበር ወይም በስራ።</span><span data-lang-block="en">Choose the service, date and place. Tell us the size — per square metre, per seat or per job.</span></p>
        <div class="ms-ph"><img src="{{ $photoSrc($featurePhotos[0]['file']) }}" alt="{{ $featurePhotos[0]['en'] }}" loading="lazy"></div>
      </div>
      <div class="ms-step">
        <span class="ms-num font-serif">02.</span>
        <h3 class="font-bold text-lg text-navy dark:text-white mt-2 mb-1.5"><span data-lang-block="am">ቡድናችን ይደርሳል</span><span data-lang-block="en">Our team arrives</span></h3>
        <p class="text-navy/65 dark:text-slate-300 text-sm"><span data-lang-block="am">ዩኒፎርም የለበሰ ቡድን ከሙያዊ ማሽኖች እና ደህንነቱ የተጠበቀ ሳሙናዎች ጋር ይደርሳል።</span><span data-lang-block="en">A uniformed crew arrives with professional machines and safe detergents.</span></p>
        <div class="ms-ph"><img src="{{ $photoSrc($featurePhotos[1]['file']) }}" alt="{{ $featurePhotos[1]['en'] }}" loading="lazy"></div>
      </div>
      <div class="ms-step">
        <span class="ms-num font-serif">03.</span>
        <h3 class="font-bold text-lg text-navy dark:text-white mt-2 mb-1.5"><span data-lang-block="am">በትኩስነቱ ይደሰቱ</span><span data-lang-block="en">Enjoy the freshness</span></h3>
        <p class="text-navy/65 dark:text-slate-300 text-sm"><span data-lang-block="am">እንጨርሳለን፣ እርስዎ ይፈትሹ። ንጹህ ወለሎች፣ የሚያብረቀርቅ መስታወት እና እንደ አዲስ የሚሸት ቦታ።</span><span data-lang-block="en">We finish, you inspect. Spotless floors, shining glass and a space that smells new again.</span></p>
        <div class="ms-ph"><img src="{{ $photoSrc($featurePhotos[2]['file']) }}" alt="{{ $featurePhotos[2]['en'] }}" loading="lazy"></div>
      </div>
    </div>
  </div>
</section>

<!-- ================= OUR PROCESS (5-STEP PROCESS SECTION) ================= -->
<section id="process" class="bg-navy text-white py-24 border-t border-b border-white/10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="max-w-2xl mb-14">
      <div class="inline-flex items-center gap-2 text-teal font-bold text-xs tracking-wider uppercase mb-3 bg-teal/20 px-3.5 py-1.5 rounded-full">
        <i data-lucide="workflow" class="w-3.5 h-3.5"></i>
        <span data-lang-block="am">ቀላል አሰራራችን</span><span data-lang-block="en">OUR 5-STEP PROCESS</span>
      </div>
      <h2 class="font-serif font-semibold text-3xl md:text-4xl leading-tight">
        <span data-lang-block="am">እንዴት እንሰራለን? (How We Work)</span>
        <span data-lang-block="en">How We Work — From First Call to Handover</span>
      </h2>
      <p class="text-white/70 text-sm sm:text-base mt-2">
        <span data-lang-block="am">የእርስዎን የሥራ ሰዓት እና የንግድ እንቅስቃሴ ሳናስተጓጉል ጥራቱን የጠበቀ ጽዳት የምናቀርብበት ባለ 5 ደረጃ ሂደት።</span>
        <span data-lang-block="en">Our streamlined 5-step operational journey ensures zero business interruption and 100% satisfaction.</span>
      </p>
    </div>

    <!-- 5 Process Steps Grid -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-6">
      <!-- Step 1 -->
      <div class="bg-white/5 border border-white/10 rounded-2xl p-6 relative flex flex-col justify-between">
        <div>
          <div class="font-serif font-bold text-3xl text-teal mb-3">01</div>
          <h4 class="font-bold text-base mb-1.5 text-white">
            <span data-lang-block="am">Step 1 — ግንኙነት መፍጠር</span>
            <span data-lang-block="en">Step 1 — Connect</span>
          </h4>
          <p class="text-white/65 text-xs leading-relaxed">
            <span data-lang-block="am">በስልክ፣ በኢሜይል ወይም በማህበራዊ ሚዲያ ያግኙን።</span>
            <span data-lang-block="en">Reach out via phone, email, or social channels.</span>
          </p>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="bg-white/5 border border-white/10 rounded-2xl p-6 relative flex flex-col justify-between">
        <div>
          <div class="font-serif font-bold text-3xl text-teal mb-3">02</div>
          <h4 class="font-bold text-base mb-1.5 text-white">
            <span data-lang-block="am">Step 2 — የቦታ ምልከታ</span>
            <span data-lang-block="en">Step 2 — Site Visit</span>
          </h4>
          <p class="text-white/65 text-xs leading-relaxed">
            <span data-lang-block="am">የጽዳቱን ሁኔታ በቦታው ተገኝተን በነፃ መገምገም።</span>
            <span data-lang-block="en">Free on-site inspection to gauge specific needs.</span>
          </p>
        </div>
      </div>

      <!-- Step 3 -->
      <div class="bg-white/5 border border-white/10 rounded-2xl p-6 relative flex flex-col justify-between">
        <div>
          <div class="font-serif font-bold text-3xl text-teal mb-3">03</div>
          <h4 class="font-bold text-base mb-1.5 text-white">
            <span data-lang-block="am">Step 3 — ዋጋ ማቅረብ</span>
            <span data-lang-block="en">Step 3 — Custom Quote</span>
          </h4>
          <p class="text-white/65 text-xs leading-relaxed">
            <span data-lang-block="am">ግልጽ የሆነ እና ሁሉንም ያካተተ ፕሮፎርማ (Proforma) ማዘጋጀት።</span>
            <span data-lang-block="en">Tailored, transparent proforma proposal.</span>
          </p>
        </div>
      </div>

      <!-- Step 4 -->
      <div class="bg-white/5 border border-white/10 rounded-2xl p-6 relative flex flex-col justify-between">
        <div>
          <div class="font-serif font-bold text-3xl text-teal mb-3">04</div>
          <h4 class="font-bold text-base mb-1.5 text-white">
            <span data-lang-block="am">Step 4 — ተግባራዊ አፈጻጸም</span>
            <span data-lang-block="en">Step 4 — Execution</span>
          </h4>
          <p class="text-white/65 text-xs leading-relaxed">
            <span data-lang-block="am">የሥራ ሰዓትዎን ሳይጋፋ በተባለው ሰዓት (በሌሊት ወይም ቅዳሜና እሁድ) መከናወን።</span>
            <span data-lang-block="en">On-time cleaning with zero business interruption.</span>
          </p>
        </div>
      </div>

      <!-- Step 5 -->
      <div class="bg-white/5 border border-white/10 rounded-2xl p-6 relative flex flex-col justify-between">
        <div>
          <div class="font-serif font-bold text-3xl text-teal mb-3">05</div>
          <h4 class="font-bold text-base mb-1.5 text-white">
            <span data-lang-block="am">Step 5 — የጥራት ርክክብ</span>
            <span data-lang-block="en">Step 5 — Quality Review</span>
          </h4>
          <p class="text-white/65 text-xs leading-relaxed">
            <span data-lang-block="am">እርስዎ ሙሉ በሙሉ መርካትዎን አረጋግጠን ሥራውን ማስረከብ።</span>
            <span data-lang-block="en">A final joint inspection ensuring 100% satisfaction.</span>
          </p>
        </div>
      </div>
    </div>

    <!-- Process CTA Button -->
    <div class="mt-12 text-center">
      <a href="#book" class="inline-flex items-center gap-2.5 bg-gradient-to-r from-teal to-teal-dark hover:from-teal-dark hover:to-teal text-white font-extrabold text-sm sm:text-base px-8 py-4 rounded-xl shadow-xl shadow-teal/20 transition transform hover:-translate-y-0.5">
        <i data-lucide="clipboard-check" class="w-5 h-5"></i>
        <span data-lang-block="am">ነፃ የቦታ ምልከታ እና ፕሮፎርማ ይጠይቁ (Request Free Inspection &amp; Proforma)</span>
        <span data-lang-block="en">Request Free On-Site Inspection &amp; Proforma</span>
        <i data-lucide="arrow-right" class="w-5 h-5"></i>
      </a>
    </div>
  </div>
</section>

<!-- ================= CUSTOMER GUARANTEE & PROMISE BANNER ================= -->
<section class="py-16 bg-gradient-to-r from-slate-900 via-navy to-slate-900 text-white border-b border-navy/10 relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 relative z-10 grid lg:grid-cols-[1.2fr_.8fr] gap-8 items-center">
    <div>
      <div class="inline-flex items-center gap-2 text-amber-300 font-bold text-xs uppercase tracking-wider mb-3 bg-amber-400/10 border border-amber-400/20 px-3 py-1 rounded-full">
        <i data-lucide="shield-check" class="w-4 h-4"></i>
        <span data-lang-block="am">የደንበኛ ዋስትና (Customer Guarantee)</span>
        <span data-lang-block="en">OUR PROMISE &amp; GUARANTEE</span>
      </div>
      <h3 class="font-serif font-bold text-2xl sm:text-3xl text-white leading-snug mb-3">
        <span data-lang-block="am">"የጥራት ማረጋገጫችን እኛ አካባቢዎን ማጽዳት ብቻ ሳይሆን ንፅህናው ዘላቂ እንዲሆን እንሰራለን። እያንዳንዱ ሥራ የእርስዎን መመዘኛ ማሟላቱን የሚያረጋግጥ የተመደበ ተቆጣጣሪ (Supervisor) እንመድባለን።"</span>
        <span data-lang-block="en">"We don't just clean; we maintain environments. Every service is backed by a dedicated supervisor to ensure your precise standards are met every single time."</span>
      </h3>
      <p class="text-white/80 text-sm leading-relaxed">
        <span data-lang-block="am">ለድርጅትዎ ንፅህና ዝግጁ ነዎት? የድርጅትዎን ገጽታ ዛሬውኑ ያድሱ። ስለ ጽዳቱ ለእኛ ይተውልንና እርስዎ ሥራዎ ላይ ብቻ ትኩረት ያድርጉ።</span>
        <span data-lang-block="en">Ready for a Cleaner, Healthier Workspace? Partner with Meash Cleaning Solution today. Let us handle the details while you drive your business growth.</span>
      </p>
    </div>
    <div class="flex flex-col sm:flex-row items-center justify-end gap-3.5">
      <a href="tel:{{ $phone1 }}" class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm px-6 py-3.5 rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30">
        <i data-lucide="phone-call" class="w-4 h-4"></i>
        <span>{{ $phone1 }} / {{ $phone2 }}</span>
      </a>
      <a href="#book" class="w-full sm:w-auto bg-white hover:bg-slate-100 text-navy font-black text-sm px-6 py-3.5 rounded-xl transition flex items-center justify-center gap-2 shadow">
        <i data-lucide="calendar" class="w-4 h-4 text-teal"></i>
        <span data-lang-block="am">ቀጠሮ ያስይዙ</span><span data-lang-block="en">Book Now</span>
      </a>
    </div>
  </div>
</section>

<!-- ================= PROJECTS / GALLERY SECTION (BEFORE & AFTER) ================= -->
<section id="gallery" class="py-24 bg-white dark:bg-slate-900 border-b border-navy/10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-14">
      <div>
        <div class="inline-flex items-center gap-2 text-teal-dark font-bold text-xs tracking-wider uppercase mb-3 bg-teal/10 px-3 py-1 rounded-full">
          <i data-lucide="image" class="w-3.5 h-3.5"></i>
          <span data-lang-block="am">የስራ ውጤቶች ጋለሪ</span><span data-lang-block="en">PROJECTS &amp; RESULTS</span>
        </div>
        <h2 class="font-serif font-bold text-3xl sm:text-4xl text-navy dark:text-white leading-tight">
          <span data-lang-block="am">ሥራችን፣ በየስፍራው</span>
          <span data-lang-block="en">Our work, in every situation</span>
        </h2>
        <p class="text-navy/65 dark:text-slate-300 text-sm sm:text-base mt-2 max-w-xl">
          <span data-lang-block="am">ሙሉ ማያ ለማየት ማንኛውንም ፎቶ ይንኩ።</span>
          <span data-lang-block="en">Tap any photo to view it full-screen.</span>
        </p>
      </div>
      <div class="mt-6 md:mt-0 flex items-center gap-3">
        <a href="#book" class="bg-navy hover:bg-teal-dark text-white text-xs font-bold px-4 py-2.5 rounded-lg transition flex items-center gap-2">
          <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
          <span data-lang-block="am">የእርሶንም ያጽዱ</span><span data-lang-block="en">Book Transformation</span>
        </a>
      </div>
    </div>

    <!-- Gallery Grid — real Meash work photos (click to enlarge) -->
    <div class="ms-gal-grid" id="ms-gal-grid">
      @foreach($galleryOrder as $gidx => $gfile)
        @php $gp = $galleryByFile[$gfile]; $gcls = ['ms-g-a', 'ms-g-b', 'ms-g-c', 'ms-g-d', 'ms-g-e'][$gidx % 5]; @endphp
        <button type="button" class="ms-gcard {{ $gcls }}" data-ms-gal="{{ $gidx }}" data-ms-full="{{ $photoSrc($gfile) }}" data-ms-cap-am="{{ $gp['am'] }}" data-ms-cap-en="{{ $gp['en'] }}" aria-label="{{ $gp['en'] }}">
          <img src="{{ $photoSrc($gfile) }}" alt="{{ $gp['en'] }}" loading="lazy">
          <span class="ms-cap">
            <span><span data-lang-block="am">{{ $gp['am'] }}</span><span data-lang-block="en">{{ $gp['en'] }}</span></span>
            <span class="ms-zoom" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></span>
          </span>
        </button>
      @endforeach
    </div>
  </div>
</section>

<!-- Lightbox for the gallery (Meash modern) -->
<div class="ms-lb" id="ms-lightbox" role="dialog" aria-modal="true" aria-label="Photo viewer">
  <button class="ms-lb-close" id="ms-lb-close" type="button" aria-label="Close viewer">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
  </button>
  <img id="ms-lb-img" src="{{ $photoSrc($galleryOrder[0] ?? 'office-cleaning.jpg') }}" alt="Meash cleaning team at work">
  <div class="ms-lb-cap"><span data-lang-block="am" id="ms-lb-cap-am"></span><span data-lang-block="en" id="ms-lb-cap-en"></span></div>
  <div class="ms-lb-row">
    <button id="ms-lb-prev" type="button" aria-label="Previous photo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m14.5 5.5-6 6.5 6 6.5"/></svg></button>
    <button id="ms-lb-next" type="button" aria-label="Next photo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m9.5 5.5 6 6.5-6 6.5"/></svg></button>
  </div>
</div>

<!-- ================= REVIEWS / TESTIMONIALS ================= -->
<section id="reviews" class="max-w-7xl mx-auto px-4 sm:px-6 py-24 border-b border-navy/10">
  <div class="flex flex-col sm:flex-row sm:items-end justify-between mb-14">
    <div>
      <div class="text-teal-dark font-bold text-xs tracking-wider uppercase mb-3"><span data-lang-block="am">የደንበኞች ምስክርነት</span><span data-lang-block="en">CLIENT TESTIMONIALS</span></div>
      <h2 class="font-serif font-bold text-3xl sm:text-4xl text-navy dark:text-white leading-tight">
        <span data-lang-block="am">ደንበኞቻችን ስለ እኛ ምን ይላሉ?</span><span data-lang-block="en">What our clients say</span>
      </h2>
    </div>
    <div class="mt-4 sm:mt-0 flex items-center gap-2 text-amber-500 text-sm font-bold" id="reviews-aggregate-block">
      <div class="flex">
        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
        <i data-lucide="star" class="w-4 h-4 fill-amber-400 text-amber-400"></i>
      </div>
      <span class="text-navy dark:text-white font-serif" id="reviews-aggregate-text">4.9 / 5.0 (300+ Reviews)</span>
    </div>
  </div>

  <div id="dynamic-reviews-grid" class="grid md:grid-cols-3 gap-8">
    <div class="border-l-2 border-teal pl-5 bg-fog/50 dark:bg-slate-800/50 p-4 rounded-r-xl">
      <div class="flex text-amber-400 mb-2">
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
      </div>
      <p class="text-[15px] text-navy/80 dark:text-slate-200 leading-relaxed" data-lang-block="am">"ሶፋዬ ልክ ዛሬ ከሱቅ እንደተገዛ አዲስ ሆኗል! ቡድኑ በጣም ጨዋና ንጹህ ስራ የሚሰሩ ናቸው።"</p>
      <p class="text-[15px] text-navy/80 dark:text-slate-200 leading-relaxed" data-lang-block="en">"My sofa looks brand new. The team was punctual, respectful, and thorough."</p>
      <p class="mt-4 font-bold text-sm text-teal-dark">Bethlehem Tadesse — ቦሌ (Bole)</p>
    </div>

    <div class="border-l-2 border-teal pl-5 bg-fog/50 dark:bg-slate-800/50 p-4 rounded-r-xl">
      <div class="flex text-amber-400 mb-2">
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
      </div>
      <p class="text-[15px] text-navy/80 dark:text-slate-200 leading-relaxed" data-lang-block="am">"በድረ-ገጹ በኢትዮጵያ ቀን ቀጠሮ መያዝ መቻሉ በጣም አስደስቶኛል። በሰዓቱ ደርሰው በ 1 ሰዓት ውስጥ ጨረሱ።"</p>
      <p class="text-[15px] text-navy/80 dark:text-slate-200 leading-relaxed" data-lang-block="en">"Booking through the Ethiopian calendar was super convenient. Arrived right on schedule."</p>
      <p class="mt-4 font-bold text-sm text-teal-dark">Yonas Assefa — ካዛንቺስ (Kazanchis)</p>
    </div>

    <div class="border-l-2 border-teal pl-5 bg-fog/50 dark:bg-slate-800/50 p-4 rounded-r-xl">
      <div class="flex text-amber-400 mb-2">
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
        <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
      </div>
      <p class="text-[15px] text-navy/80 dark:text-slate-200 leading-relaxed" data-lang-block="am">"ለሆቴላችን ወርሃዊ የጽዳት ኮንትራት ገብተናል፤ ህጋዊ ደረሰኝና አስተማማኝ ክትትል ይሰጣሉ። ውጤቱ ድንቅ ነው።"</p>
      <p class="text-[15px] text-navy/80 dark:text-slate-200 leading-relaxed" data-lang-block="en">"We contracted them for our hotel property — professional SLA compliance &amp; VAT receipts."</p>
      <p class="mt-4 font-bold text-sm text-teal-dark">Selam Hospitality Group — Corporate</p>
    </div>
  </div>
</section>

<!-- ================= QR CODE SECTION ================= -->
<section id="qr-code" class="py-20 bg-fog dark:bg-slate-800/50 border-b border-navy/10">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
    <div class="inline-flex items-center gap-2 text-teal-dark font-bold text-xs tracking-wider uppercase mb-3 bg-teal/10 px-3 py-1 rounded-full">
      <i data-lucide="qr-code" class="w-3.5 h-3.5"></i>
      <span data-lang-block="am">ፈጣን የሞባይል ስካነር</span><span data-lang-block="en">SCAN &amp; BOOK ON MOBILE</span>
    </div>
    <h2 class="font-serif font-bold text-2xl sm:text-3xl text-navy dark:text-white leading-tight mb-3">
      <span data-lang-block="am">በስልክዎ ስካን በማድረግ በቀጥታ ቀጠሮ ይያዙ</span>
      <span data-lang-block="en">Scan QR Code to Open on Mobile or Telegram</span>
    </h2>
    <p class="text-navy/70 dark:text-slate-300 text-sm max-w-lg mx-auto mb-8">
      <span data-lang-block="am">የካሜራ መተግበሪያዎን በመክፈት ይህንን QR ኮድ ስካን ያድርጉ፤ ወዲያውኑ የቀጠሮ መመዝገቢያው ወይም የቴሌግራም ቦቱ በስልክዎ ይከፈታል።</span>
      <span data-lang-block="en">Point your phone camera to scan this QR code to access instant mobile booking and automated Telegram assistant.</span>
    </p>

    <!-- QR Cards Grid -->
    <div class="grid sm:grid-cols-2 gap-6 max-w-xl mx-auto">
      <!-- Website QR -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-md border border-navy/10 flex flex-col items-center">
        <div class="p-3 bg-white rounded-xl shadow-inner border border-gray-100 mb-4">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode(url('/')) }}" alt="Website QR Code" class="w-36 h-36 rounded-lg">
        </div>
        <h4 class="font-bold text-sm text-navy dark:text-white flex items-center gap-1.5 mb-1">
          <i data-lucide="globe" class="w-4 h-4 text-teal"></i> Website Direct Booking
        </h4>
        <span class="text-xs text-navy/50">{{ request()->getHost() }}</span>
      </div>

      <!-- Telegram Bot QR -->
      <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-md border border-navy/10 flex flex-col items-center">
        <div class="p-3 bg-white rounded-xl shadow-inner border border-gray-100 mb-4">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=https://t.me/meash_cleaning_solution_bot" alt="Telegram Bot QR Code" class="w-36 h-36 rounded-lg">
        </div>
        <h4 class="font-bold text-sm text-navy dark:text-white flex items-center gap-1.5 mb-1">
          <i data-lucide="bot" class="w-4 h-4 text-teal"></i> Telegram Mini App &amp; Bot
        </h4>
        <span class="text-xs text-navy/50">@meash_cleaning_solution_bot</span>
      </div>
    </div>
  </div>
</section>

<!-- ================= BOOKING WIZARD ================= -->
<section id="book" class="bg-fog py-24 border-t border-navy/10">
  <div class="max-w-2xl mx-auto px-6">
    <div class="text-center mb-10">
      <div class="text-teal-dark font-bold text-xs tracking-wide mb-3"><span data-lang-block="am">ቀጠሮ</span><span data-lang-block="en">BOOKING</span></div>
      <h2 class="font-serif font-semibold text-3xl"><span data-lang-block="am">ቀጠሮ ይያዙ</span><span data-lang-block="en">Schedule your cleaning</span></h2>
    </div>

    <div class="bg-white rounded-xl p-6 md:p-9 shadow-sm border border-navy/10">
      <!-- Step Indicator Bar: Horizontally scrollable and compact on phone screens -->
      <div class="flex items-center justify-between mb-8 text-[11px] font-bold text-navy/40 uppercase tracking-wider overflow-x-auto pb-2 gap-2 sm:gap-3 -mx-2 px-2 sm:mx-0 sm:px-0 scrollbar-none" style="-webkit-overflow-scrolling: touch;">
        <span class="step-dot text-teal-dark bg-teal/10 font-black whitespace-nowrap shrink-0 flex items-center gap-1 cursor-pointer py-1.5 px-2.5 rounded-lg transition" data-step="1">01 <span data-lang-block="am">አገልግሎት</span><span data-lang-block="en">Service</span></span>
        <span class="text-navy/20 shrink-0 font-normal">›</span>
        <span class="step-dot whitespace-nowrap shrink-0 flex items-center gap-1 cursor-pointer py-1.5 px-2.5 rounded-lg transition" data-step="2">02 <span data-lang-block="am">አድራሻ</span><span data-lang-block="en">Address</span></span>
        <span class="text-navy/20 shrink-0 font-normal">›</span>
        <span class="step-dot whitespace-nowrap shrink-0 flex items-center gap-1 cursor-pointer py-1.5 px-2.5 rounded-lg transition" data-step="3">03 <span data-lang-block="am">ቀጠሮ</span><span data-lang-block="en">Schedule</span></span>
        <span class="text-navy/20 shrink-0 font-normal">›</span>
        <span class="step-dot whitespace-nowrap shrink-0 flex items-center gap-1 cursor-pointer py-1.5 px-2.5 rounded-lg transition" data-step="4">04 <span data-lang-block="am">ማረጋገጫ</span><span data-lang-block="en">Confirm</span></span>
      </div>

      <form id="booking-form" onsubmit="submitBooking(event)">
        <!-- STEP 1 -->
        <div id="step-1" class="wizard-step">
          <!-- Property Type Selector (NEW for Residential & Commercial) -->
          <div class="mb-5 bg-teal-light/30 border border-teal/20 p-3.5 rounded-xl">
            <label class="text-xs font-bold text-teal-dark uppercase tracking-wider block mb-1.5 flex items-center gap-1.5">
              <i data-lucide="building-2" class="w-3.5 h-3.5"></i>
              <span data-lang-block="am">የቦታው / የንብረቱ አይነት</span><span data-lang-block="en">Property Type</span>
            </label>
            <select id="book-property-type" class="w-full rounded-lg border border-navy/15 px-3 py-2 text-sm bg-white dark:bg-slate-800 text-navy font-semibold focus:border-teal cursor-pointer">
              <option value="residential">Residential Property (የመኖሪያ ቤት ወይም ቪላ)</option>
              <option value="office">Corporate & Commercial Office (የድርጅትና ቢሮ ህንፃ)</option>
              <option value="hotel">Hospitality, Hotel & Guest House (ሆቴልና የእንግዳ ማረፊያ)</option>
              <option value="restaurant">Restaurant, Café & Dining Lounge (ሬስቶራንትና ካፌ)</option>
              <option value="hall">Auditorium, Event Hall & Center (አዳራሽና መሰብሰቢያ)</option>
              <option value="commercial_store">Commercial Store & Retail Mall (የንግድ ማዕከል)</option>
              <option value="other">Institutional & Other Facility (ሌሎች ተቋማት)</option>
            </select>
          </div>

          <p class="font-semibold mb-3 text-sm text-navy"><span data-lang-block="am">የጽዳት አገልግሎት ይምረጡ</span><span data-lang-block="en">Choose cleaning service</span></p>
          <div class="grid grid-cols-2 gap-2.5 mb-2">
            @php
              $wizardServices = $activeServices->take(8);
              $firstWizardService = $wizardServices->first();
              $firstWizardPrice = (float)($firstWizardService?->base_price ?? 350);
              $iconList = [
                'sofa' => 'sofa',
                'carpet' => 'layout-grid',
                'mattress' => 'bed',
                'glass' => 'square',
                'floor_ceramic' => 'sparkles',
                'office' => 'building-2',
                'residential_deep' => 'home',
                'commercial' => 'briefcase',
              ];
            @endphp
            @foreach($wizardServices as $idx => $ws)
              @php
                $wIcon = $iconList[$ws->code] ?? 'sparkles';
                $wPrice = (float)$ws->base_price;
                $wUnit = $ws->pricing_unit_am ?: ($ws->unit ?: 'unit');
                $wNameAm = $ws->name_am ?: $ws->name_en;
                $wNameEn = $ws->name_en ?: $ws->name_am;
              @endphp
              <button type="button" onclick="selectServiceForBooking('{{ $ws->code }}', {{ $ws->id }})" class="svc-btn border-2 {{ $idx === 0 ? 'border-navy' : 'border-navy/10' }} rounded-lg p-3 text-left flex items-center gap-2.5 cursor-pointer">
                <i data-lucide="{{ $wIcon }}" class="w-5 h-5 text-teal-dark shrink-0"></i>
                <span>
                  <span class="font-semibold text-xs sm:text-sm block">
                    <span data-lang-block="am">{{ $wNameAm }}</span>
                    <span data-lang-block="en">{{ $wNameEn }}</span>
                  </span>
                  <span class="text-[11px] text-navy/50 wiz-price" data-wiz-price="{{ $ws->id }}" data-wiz-unit="{{ $wUnit }}">
                    {{ number_format($wPrice) }} ETB / {{ $wUnit }}
                  </span>
                </span>
              </button>
            @endforeach
          </div>
          <label class="text-xs font-semibold block mt-4 text-navy"><span data-lang-block="am">ብዛት / መጠን (Quantity / Units)</span><span data-lang-block="en">Quantity</span></label>
          <div class="flex items-center gap-3 mt-1.5 mb-2">
            <button type="button" onclick="changeQty(-1)" class="w-9 h-9 rounded-md border border-navy/15 font-bold cursor-pointer hover:bg-fog text-navy">−</button>
            <span id="qty-display" class="font-serif font-semibold text-xl w-8 text-center text-navy">1</span>
            <button type="button" onclick="changeQty(1)" class="w-9 h-9 rounded-md border border-navy/15 font-bold cursor-pointer hover:bg-fog text-navy">+</button>
          </div>
          <div class="bg-teal-light rounded-md px-4 py-3 mt-4 mb-5 flex items-center justify-between">
            <span class="text-xs sm:text-sm font-semibold text-teal-dark"><span data-lang-block="am">የግምት ድምር (Estimated Price)</span><span data-lang-block="en">Estimated total</span></span>
            <span id="wiz-total" class="font-serif font-semibold text-lg sm:text-xl text-teal-dark">{{ number_format($firstWizardPrice) }} ETB</span>
          </div>
          <button type="button" onclick="resetWizard()" class="w-full mt-1 text-xs font-bold text-navy/50 hover:text-red-600 py-2 cursor-pointer"><span data-lang-block="am">↺ ቀጠሮውን አጽዳ</span><span data-lang-block="en">↺ Clear booking</span></button>
          <button type="button" onclick="showWizardStep(2)" class="w-full bg-navy hover:bg-teal-dark text-white font-bold py-3.5 rounded-lg transition cursor-pointer flex items-center justify-center gap-2">
            <span data-lang-block="am">ቀጣይ: አድራሻ ያስገቡ</span><span data-lang-block="en">Next: Address &amp; Location</span>
            <i data-lucide="arrow-right" class="w-4 h-4"></i>
          </button>
        </div>

        <!-- STEP 2 -->
        <div id="step-2" class="wizard-step hidden">
          <p class="font-semibold mb-4 text-sm"><span data-lang-block="am">አድራሻ</span><span data-lang-block="en">Address</span></p>
          <select id="book-subcity" class="w-full mb-3 rounded-md border border-navy/15 px-4 py-3 bg-white text-sm" required onchange="document.getElementById('step2-error')?.classList.add('hidden')">
            <option value="">-- Subcity / ክፍለ ከተማ --</option>
            @php
              $activeSubcitiesList = !empty($subcities) ? array_filter($subcities, fn($c) => !isset($c['active']) || !empty($c['active'])) : [
                ['name_am' => 'ቦሌ', 'name_en' => 'Bole'],
                ['name_am' => 'የካ', 'name_en' => 'Yeka'],
                ['name_am' => 'ቂርቆስ', 'name_en' => 'Kirkos'],
                ['name_am' => 'አራዳ', 'name_en' => 'Arada'],
                ['name_am' => 'ልደታ', 'name_en' => 'Lideta'],
                ['name_am' => 'ንፋስ ስልክ', 'name_en' => 'Nifas Silk'],
                ['name_am' => 'ኮልፌ', 'name_en' => 'Kolfe'],
                ['name_am' => 'ጉለሌ', 'name_en' => 'Gulele'],
                ['name_am' => 'አቃቂ ቃሊቲ', 'name_en' => 'Akaki Kality'],
                ['name_am' => 'ለሚ ኩራ', 'name_en' => 'Lemi Kura'],
              ];
            @endphp
            @foreach($activeSubcitiesList as $sc)
              <option value="{{ $sc['name_en'] ?? $sc['name_am'] }}">{{ $sc['name_en'] ?? $sc['name_am'] }} ({{ $sc['name_am'] ?? $sc['name_en'] }})</option>
            @endforeach
          </select>
          <input id="book-address" type="text" placeholder="Street, house no. / ጎዳና፣ የቤት ቁጥር" class="w-full mb-3 rounded-md border border-navy/15 px-4 py-3 text-sm" required oninput="document.getElementById('step2-error')?.classList.add('hidden')">
          <input id="book-landmark" type="text" placeholder="Landmark / መለያ ቦታ (ምሳሌ፡ ሆቴል አጠገብ)" class="w-full mb-3 rounded-md border border-navy/15 px-4 py-3 text-sm">
          <p id="step2-error" class="hidden text-xs text-red-600 font-bold mb-3 flex items-center gap-1">
            <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> እባክዎ ክፍለ ከተማ ይምረጡ እና ጎዳና / የቤት ቁጥር ያስገቡ! (Please select a subcity and enter the street address!)
          </p>
          <div class="flex gap-3">
            <button type="button" onclick="showWizardStep(1)" class="flex-1 border border-navy/15 font-bold py-3.5 rounded-md text-sm cursor-pointer hover:bg-fog"><span data-lang-block="am">ተመለስ</span><span data-lang-block="en">Back</span></button>
            <button type="button" onclick="validateStep2AndContinue()" class="flex-1 bg-navy hover:bg-teal-dark text-white font-bold py-3.5 rounded-md text-sm transition cursor-pointer"><span data-lang-block="am">ቀጣይ</span><span data-lang-block="en">Continue</span></button>
          </div>
        </div>

        <!-- STEP 3 -->
        <div id="step-3" class="wizard-step hidden">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <p class="font-semibold text-sm text-navy"><span data-lang-block="am">ቀንና ሰዓት ይምረጡ</span><span data-lang-block="en">Select Date &amp; Time</span></p>
            <span class="text-[10px] font-bold text-teal-dark bg-teal-light px-2.5 py-1 rounded-full shrink-0 whitespace-nowrap">ነባሪ፡ የኢትዮጵያ ካላንደር</span>
          </div>

          <!-- Calendar Mode Switcher (Ethiopian vs Gregorian) -->
          <div class="mb-4 p-1 bg-navy/5 rounded-xl flex gap-1 border border-navy/10">
            <button type="button" id="tab-cal-eth" onclick="switchCalendarMode('eth')" class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 bg-white text-navy shadow-sm cursor-pointer">
              <i data-lucide="calendar" class="w-3.5 h-3.5 text-teal"></i>
              <span>በኢትዮጵያ ካላንደር (ዓ.ም)</span>
            </button>
            <button type="button" id="tab-cal-greg" onclick="switchCalendarMode('greg')" class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 text-navy/60 hover:text-navy cursor-pointer">
              <i data-lucide="globe" class="w-3.5 h-3.5 text-navy/50"></i>
              <span>በፈረንጆች (Gregorian - GC)</span>
            </button>
          </div>

          <!-- 1. ETHIOPIAN CALENDAR PICKER (DEFAULT) -->
          <div id="cal-mode-eth" class="mb-4 p-4 rounded-xl border border-teal/30 bg-teal-light/20">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-bold text-teal-dark flex items-center gap-1.5">
                <i data-lucide="calendar" class="w-4 h-4"></i> የኢትዮጵያ ዘመን አቆጣጠር
              </span>
              <span class="text-[10px] text-navy/50">13ቱም ወራት</span>
            </div>

            <!-- Selectors -->
            <div class="grid grid-cols-3 gap-2 mb-3">
              <div>
                <label class="block text-[10px] font-bold text-navy/70 mb-1">ቀን (Day)</label>
                <select id="eth-day" onchange="syncEthToGreg()" class="w-full rounded-lg border border-navy/20 px-3 py-2.5 text-sm font-bold text-navy bg-white focus:outline-none focus:border-teal cursor-pointer">
                  <!-- filled by JS -->
                </select>
              </div>
              <div>
                <label class="block text-[10px] font-bold text-navy/70 mb-1">ወር (Month)</label>
                <select id="eth-month" onchange="updateEthDays(); syncEthToGreg()" class="w-full rounded-lg border border-navy/20 px-2 py-2.5 text-xs font-bold text-navy bg-white focus:outline-none focus:border-teal cursor-pointer">
                  <option value="1">1 - መስከረም</option>
                  <option value="2">2 - ጥቅምት</option>
                  <option value="3">3 - ህዳር</option>
                  <option value="4">4 - ታህሳስ</option>
                  <option value="5">5 - ጥር</option>
                  <option value="6">6 - የካቲት</option>
                  <option value="7">7 - መጋቢት</option>
                  <option value="8">8 - ሚያዚያ</option>
                  <option value="9">9 - ግንቦት</option>
                  <option value="10">10 - ሰኔ</option>
                  <option value="11">11 - ሐምሌ</option>
                  <option value="12">12 - ነሐሴ</option>
                  <option value="13">13 - ጳጉሜ</option>
                </select>
              </div>
              <div>
                <label class="block text-[10px] font-bold text-navy/70 mb-1">ዓ.ም (Year)</label>
                <select id="eth-year" onchange="updateEthDays(); syncEthToGreg()" class="w-full rounded-lg border border-navy/20 px-2 py-2.5 text-xs font-bold text-navy bg-white focus:outline-none focus:border-teal cursor-pointer">
                  <!-- filled by JS -->
                </select>
              </div>
            </div>

            <!-- Converted Gregorian Display -->
            <div class="p-2.5 rounded-lg bg-white border border-teal/20 flex items-center justify-between text-xs">
              <span class="text-navy/65 flex items-center gap-1.5 font-semibold">
                <i data-lucide="globe" class="w-3.5 h-3.5 text-teal"></i>
                <span>የፈረንጆች ቀን (GC):</span>
              </span>
              <span id="greg-display" class="font-bold text-teal-dark">—</span>
            </div>
          </div>

          <!-- 2. GREGORIAN CALENDAR PICKER (ALTERNATIVE) -->
          <div id="cal-mode-greg" class="hidden mb-4 p-4 rounded-xl border border-navy/15 bg-fog">
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-bold text-navy flex items-center gap-1.5">
                <i data-lucide="globe" class="w-4 h-4 text-teal"></i> Gregorian Calendar (GC)
              </span>
              <span class="text-[10px] text-navy/50">ፈረንጆች ቀን</span>
            </div>

            <input type="date" id="greg-date-input" onchange="syncGregToEth()" class="w-full mb-3 rounded-lg border border-navy/20 bg-white px-3.5 py-2.5 text-sm font-semibold text-navy focus:outline-none focus:border-teal cursor-pointer">

            <!-- Converted Ethiopian Display -->
            <div class="p-2.5 rounded-lg bg-teal-light/40 border border-teal/30 flex items-center justify-between text-xs">
              <span class="text-navy/70 flex items-center gap-1.5 font-semibold">
                <i data-lucide="calendar" class="w-3.5 h-3.5 text-teal"></i>
                <span>የኢትዮጵያ ቀን (ETH):</span>
              </span>
              <span id="eth-display-from-greg" class="font-bold text-teal-dark text-sm">— ዓ.ም</span>
            </div>
          </div>

          <!-- Hidden master value for submission -->
          <input type="hidden" id="book-date" required>
          <p id="date-slot-error" class="hidden text-xs text-red-600 font-bold mb-3 flex items-center gap-1">
            <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> እባክዎ የቀጠሮ ቀን ይምረጡ!
          </p>

          <!-- Time Slot — Morning / Afternoon / Night Shift / Weekend -->
          <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
              <p class="text-xs font-bold text-navy/80 flex items-center gap-1.5">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-teal"></i>
                <span>የቀጠሮ ክፍለ ጊዜ (Time Slot &amp; Shifts)</span>
              </p>
              <span class="text-[10px] text-teal-dark font-bold">24/7 ተለዋዋጭ ፈረቃዎች</span>
            </div>
            <div class="grid grid-cols-2 gap-2.5">
              <!-- 1. Morning -->
              <label class="relative cursor-pointer">
                <input type="radio" name="time_slot_radio" value="morning" onchange="setTimeSlot(this.value)" class="sr-only peer" required>
                <div class="p-3.5 rounded-xl border-2 border-navy/15 peer-checked:border-teal peer-checked:bg-teal-light/40 transition text-center shadow-sm hover:border-teal/50 bg-white h-full flex flex-col justify-between">
                  <div>
                    <div class="w-8 h-8 rounded-full bg-amber-500/10 text-amber-600 flex items-center justify-center mx-auto mb-1">
                      <i data-lucide="sun-medium" class="w-4 h-4"></i>
                    </div>
                    <p class="font-extrabold text-xs text-navy peer-checked:text-teal-dark">ጥዋት (Morning)</p>
                  </div>
                  <div class="mt-1.5 pt-1.5 border-t border-navy/10 text-[10px] space-y-0.5 font-bold">
                    <p class="text-teal-dark">2:00 – 6:00 ጠዋቱ <span class="font-normal text-navy/50">(ኢትዮጵያ)</span></p>
                    <p class="text-navy/50 font-normal">8:00 AM – 12:00 PM <span class="text-[9px]">(GC)</span></p>
                  </div>
                </div>
              </label>

              <!-- 2. Afternoon -->
              <label class="relative cursor-pointer">
                <input type="radio" name="time_slot_radio" value="afternoon" onchange="setTimeSlot(this.value)" class="sr-only peer">
                <div class="p-3.5 rounded-xl border-2 border-navy/15 peer-checked:border-teal peer-checked:bg-teal-light/40 transition text-center shadow-sm hover:border-teal/50 bg-white h-full flex flex-col justify-between">
                  <div>
                    <div class="w-8 h-8 rounded-full bg-orange-500/10 text-orange-600 flex items-center justify-center mx-auto mb-1">
                      <i data-lucide="sun" class="w-4 h-4"></i>
                    </div>
                    <p class="font-extrabold text-xs text-navy peer-checked:text-teal-dark">ከሰዓት (Afternoon)</p>
                  </div>
                  <div class="mt-1.5 pt-1.5 border-t border-navy/10 text-[10px] space-y-0.5 font-bold">
                    <p class="text-teal-dark">7:00 – 11:00 ከሰዓቱ <span class="font-normal text-navy/50">(ኢትዮጵያ)</span></p>
                    <p class="text-navy/50 font-normal">1:00 PM – 5:00 PM <span class="text-[9px]">(GC)</span></p>
                  </div>
                </div>
              </label>

              <!-- 3. Night Shift -->
              <label class="relative cursor-pointer">
                <input type="radio" name="time_slot_radio" value="night" onchange="setTimeSlot(this.value)" class="sr-only peer">
                <div class="p-3.5 rounded-xl border-2 border-navy/15 peer-checked:border-teal peer-checked:bg-teal-light/40 transition text-center shadow-sm hover:border-teal/50 bg-white h-full flex flex-col justify-between">
                  <div>
                    <div class="w-8 h-8 rounded-full bg-indigo-500/10 text-indigo-600 flex items-center justify-center mx-auto mb-1">
                      <i data-lucide="moon" class="w-4 h-4"></i>
                    </div>
                    <p class="font-extrabold text-xs text-navy peer-checked:text-teal-dark">የሌሊት ፈረቃ (Night Shift)</p>
                  </div>
                  <div class="mt-1.5 pt-1.5 border-t border-navy/10 text-[10px] space-y-0.5 font-bold">
                    <p class="text-teal-dark">1:00 – 11:00 ሌሊቱ <span class="font-normal text-navy/50">(ለተቋማት)</span></p>
                    <p class="text-navy/50 font-normal">7:00 PM – 5:00 AM <span class="text-[9px]">(GC)</span></p>
                  </div>
                </div>
              </label>

              <!-- 4. Weekend Shift -->
              <label class="relative cursor-pointer">
                <input type="radio" name="time_slot_radio" value="weekend" onchange="setTimeSlot(this.value)" class="sr-only peer">
                <div class="p-3.5 rounded-xl border-2 border-navy/15 peer-checked:border-teal peer-checked:bg-teal-light/40 transition text-center shadow-sm hover:border-teal/50 bg-white h-full flex flex-col justify-between">
                  <div>
                    <div class="w-8 h-8 rounded-full bg-emerald-500/10 text-emerald-600 flex items-center justify-center mx-auto mb-1">
                      <i data-lucide="calendar" class="w-4 h-4"></i>
                    </div>
                    <p class="font-extrabold text-xs text-navy peer-checked:text-teal-dark">ቅዳሜና እሁድ (Weekend)</p>
                  </div>
                  <div class="mt-1.5 pt-1.5 border-t border-navy/10 text-[10px] space-y-0.5 font-bold">
                    <p class="text-teal-dark">እንደ ፍላጎትዎ <span class="font-normal text-navy/50">(ተለዋዋጭ)</span></p>
                    <p class="text-navy/50 font-normal">Flexible Weekend Hours</p>
                  </div>
                </div>
              </label>
            </div>
            <input type="hidden" id="book-time-slot" required>
            <p id="time-slot-error" class="hidden text-xs text-red-600 font-bold mt-2 flex items-center gap-1">
              <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> እባክዎ የቀጠሮ ሰዓት (ጠዋት፣ ከሰዓት፣ የሌሊት ፈረቃ ወይም ቅዳሜና እሁድ) ይምረጡ!
            </p>
          </div>

          <textarea id="book-notes" placeholder="Notes (optional) / ተጨማሪ ማስታወሻ..." class="w-full mb-3 rounded-md border border-navy/15 px-4 py-3 text-sm" rows="2"></textarea>
          <div class="flex gap-3">
            <button type="button" onclick="showWizardStep(2)" class="flex-1 border border-navy/15 font-bold py-3.5 rounded-md text-sm cursor-pointer hover:bg-fog"><span data-lang-block="am">ተመለስ</span><span data-lang-block="en">Back</span></button>
            <button type="button" onclick="validateStep3AndContinue()" class="flex-1 bg-navy hover:bg-teal-dark text-white font-bold py-3.5 rounded-md text-sm transition cursor-pointer shadow-md"><span data-lang-block="am">ቀጣይ</span><span data-lang-block="en">Continue</span></button>
          </div>
        </div>

        <!-- STEP 4 -->
        <div id="step-4" class="wizard-step hidden">
          <p class="font-semibold mb-3 text-sm"><span data-lang-block="am">የእርስዎ መረጃ</span><span data-lang-block="en">Your details</span></p>
          <input id="book-name" type="text" placeholder="Full name / ሙሉ ስም" class="w-full mb-3 rounded-md border border-navy/15 px-4 py-3 text-sm" required>
          <input id="book-phone" type="tel" placeholder="Phone / ስልክ ቁጥር (09...)" class="w-full mb-3 rounded-md border border-navy/15 px-4 py-3 text-sm" required>

          <!-- Recurring Subscription Plan Selector -->
          <div class="mb-4 p-3.5 rounded-xl bg-teal-light/50 border border-teal/20">
            <label class="block text-xs font-bold text-teal-dark uppercase tracking-wider mb-2 flex items-center gap-1.5">
              <i data-lucide="repeat" class="w-3.5 h-3.5 text-teal"></i>
              <span data-lang-block="am">የአገልግሎት ድግግሞሽ እና የቅናሽ እቅድ (Contract Plan)</span>
              <span data-lang-block="en">Service Frequency &amp; Contract Plan</span>
            </label>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <label class="flex items-center gap-2 p-2.5 rounded-lg bg-white border border-navy/15 cursor-pointer hover:border-teal transition">
                <input type="radio" name="sub_plan" value="one_time" checked onchange="updateSubscriptionDiscount()" class="text-teal focus:ring-teal">
                <div>
                  <span class="font-bold block text-navy text-[11px]"><span data-lang-block="am">የአንድ ጊዜ</span><span data-lang-block="en">One-time</span></span>
                  <span class="text-[9px] text-navy/50"><span data-lang-block="am">መደበኛ ዋጋ</span><span data-lang-block="en">Standard Rate</span></span>
                </div>
              </label>
              <label class="flex items-center gap-2 p-2.5 rounded-lg bg-white border border-navy/15 cursor-pointer hover:border-teal transition">
                <input type="radio" name="sub_plan" value="weekly" onchange="updateSubscriptionDiscount()" class="text-teal focus:ring-teal">
                <div>
                  <span class="font-bold block text-navy text-[11px]"><span data-lang-block="am">በየሳምንቱ</span><span data-lang-block="en">Weekly</span></span>
                  <span class="text-[9px] font-extrabold text-teal-dark">15% ቅናሽ (15% OFF)</span>
                </div>
              </label>
              <label class="flex items-center gap-2 p-2.5 rounded-lg bg-white border border-navy/15 cursor-pointer hover:border-teal transition">
                <input type="radio" name="sub_plan" value="biweekly" onchange="updateSubscriptionDiscount()" class="text-teal focus:ring-teal">
                <div>
                  <span class="font-bold block text-navy text-[11px]"><span data-lang-block="am">በየሁለት ሳምንቱ</span><span data-lang-block="en">Bi-weekly</span></span>
                  <span class="text-[9px] font-extrabold text-teal-dark">10% ቅናሽ (10% OFF)</span>
                </div>
              </label>
              <label class="flex items-center gap-2 p-2.5 rounded-lg bg-white border border-navy/15 cursor-pointer hover:border-teal transition">
                <input type="radio" name="sub_plan" value="monthly" onchange="updateSubscriptionDiscount()" class="text-teal focus:ring-teal">
                <div>
                  <span class="font-bold block text-navy text-[11px]"><span data-lang-block="am">በየወሩ</span><span data-lang-block="en">Monthly</span></span>
                  <span class="text-[9px] font-extrabold text-teal-dark">5% ቅናሽ (5% OFF)</span>
                </div>
              </label>
            </div>
            <p id="sub-discount-hint" class="hidden text-[11px] font-semibold text-teal-dark mt-2 pt-1 border-t border-teal/20 flex items-center gap-1.5">
              <i data-lucide="tag" class="w-3.5 h-3.5 text-teal"></i>
              <span id="sub-discount-text"></span>
            </p>
          </div>

          <!-- Payment Method Selector -->
          <div class="mb-4 p-3.5 rounded-xl bg-fog border border-navy/15">
            <label class="block text-xs font-bold text-navy uppercase tracking-wider mb-2 flex items-center gap-1.5">
              <i data-lucide="credit-card" class="w-3.5 h-3.5 text-teal"></i>
              <span data-lang-block="am">የክፍያ መንገድ (Payment Method)</span>
              <span data-lang-block="en">Preferred Payment Method</span>
            </label>
            <div class="grid grid-cols-3 gap-2 text-xs">
              <label class="flex flex-col items-center text-center p-2 rounded-lg bg-white border border-navy/15 cursor-pointer hover:border-teal transition">
                <input type="radio" name="web_payment_method" value="cash" checked class="text-teal focus:ring-teal mb-1">
                <span class="font-bold block text-navy text-[11px]"><span data-lang-block="am">ጥሬ ገንዘብ</span><span data-lang-block="en">Cash</span></span>
                <span class="text-[9px] text-navy/50">በቦታው</span>
              </label>
              <label class="flex flex-col items-center text-center p-2 rounded-lg bg-white border border-navy/15 cursor-pointer hover:border-teal transition">
                <input type="radio" name="web_payment_method" value="telebirr" class="text-teal focus:ring-teal mb-1">
                <span class="font-bold block text-teal-dark text-[11px]">ቴሌብር</span>
                <span class="text-[9px] text-navy/50">Telebirr</span>
              </label>
              <label class="flex flex-col items-center text-center p-2 rounded-lg bg-white border border-navy/15 cursor-pointer hover:border-teal transition">
                <input type="radio" name="web_payment_method" value="cbe" class="text-teal focus:ring-teal mb-1">
                <span class="font-bold block text-purple-700 text-[11px]">ንግድ ባንክ</span>
                <span class="text-[9px] text-navy/50">CBE / CBE Birr</span>
              </label>
            </div>
            <!-- Quick Bank Details Hint -->
            <div class="mt-2.5 p-2.5 rounded-md bg-fog border border-navy/10 text-[11px] text-navy/70 space-y-1">
              <p><strong class="text-teal-dark">ቴሌብር:</strong> <span class="font-mono font-bold">{{ $telebirrNo }}</span> ({{ $websiteSettings['telebirr_account_name'] ?? 'ሜሽ የፅዳት' }})</p>
              <p><strong class="text-purple-700">ንግድ ባንክ:</strong> <span class="font-mono font-bold">{{ $cbeNo }}</span> ({{ $websiteSettings['cbe_account_name'] ?? 'ሜሽ የፅዳት' }})</p>
            </div>
          </div>

          <div class="bg-fog rounded-md px-4 py-3 mb-5 flex items-center justify-between border border-navy/10">
            <span class="text-sm font-semibold text-navy/70"><span data-lang-block="am">ጠቅላላ የሚከፈል</span><span data-lang-block="en">Total to pay</span></span>
            <span id="wiz-total-2" class="font-serif font-semibold text-xl text-teal-dark">{{ number_format($firstWizardPrice) }} ETB</span>
          </div>
          <div class="flex gap-3">
            <button type="button" onclick="showWizardStep(3)" class="flex-1 border border-navy/15 font-bold py-3.5 rounded-md text-sm cursor-pointer hover:bg-fog"><span data-lang-block="am">ተመለስ</span><span data-lang-block="en">Back</span></button>
            <button type="submit" id="btn-submit-booking" class="flex-1 bg-teal-dark hover:bg-teal text-white font-bold py-3.5 rounded-md text-sm transition cursor-pointer"><span data-lang-block="am">ቀጠሮ አረጋግጥ</span><span data-lang-block="en">Confirm Booking</span></button>
          </div>
          <div id="booking-error-msg" class="hidden mt-3 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs text-center font-bold"></div>
        </div>

        <!-- SUCCESS -->
        <div id="step-success" class="hidden text-center py-6">
          <i data-lucide="check-circle-2" class="w-12 h-12 text-teal-dark mx-auto mb-4"></i>
          <h3 class="font-serif font-semibold text-xl mb-1"><span data-lang-block="am">ተመዝግቧል!</span><span data-lang-block="en">Booking received!</span></h3>
          <p class="text-navy/55 text-sm mb-1"><span data-lang-block="am">የቀጠሮ ቁጥር</span><span data-lang-block="en">Your reference number</span></p>
          <p class="font-serif font-semibold text-2xl text-teal-dark mb-4" id="success-booking-id"></p>

          <!-- Payment Instructions in Success Card -->
          <div class="p-3.5 rounded-lg bg-fog border border-navy/10 max-w-sm mx-auto mb-5 text-left text-xs">
            <p class="font-bold text-navy mb-1.5"><span data-lang-block="am">የክፍያ ማስተላለፊያ መረጃ፦</span><span data-lang-block="en">Payment Account Information:</span></p>
            <div class="space-y-1 text-navy/75 text-[11px]">
              <p><strong class="text-teal-dark">ቴሌብር (Telebirr):</strong> <span class="font-mono font-bold">{{ $telebirrNo }}</span> ({{ $websiteSettings['telebirr_account_name'] ?? 'ሜሽ የፅዳት' }})</p>
              <p><strong class="text-purple-700">ንግድ ባንክ (CBE):</strong> <span class="font-mono font-bold">{{ $cbeNo }}</span> ({{ $websiteSettings['cbe_account_name'] ?? 'ሜሽ የፅዳት' }})</p>
              @if(!empty($boaNo))
              <p><strong class="text-navy">አቢሲኒያ ባንክ (BOA):</strong> <span class="font-mono font-bold">{{ $boaNo }}</span> ({{ $websiteSettings['boa_account_name'] ?? 'ሜሽ የፅዳት' }})</p>
              @endif
              <p class="text-[10px] text-navy/50 pt-1 border-t border-navy/10">ማስታወሻ፦ ገንዘቡን ሲያስተላልፉ በትራንስፈር ማስታወሻ ላይ የቀጠሮ ቁጥርዎን ያስገቡ።</p>
            </div>
          </div>

          <p class="text-xs text-navy/60 max-w-sm mx-auto mb-6"><span data-lang-block="am">ቡድናችን በቅርቡ ደውሎ ዝርዝሮችን ያረጋግጥልዎታል። እናመሰግናለን!</span><span data-lang-block="en">Our team will call you shortly to confirm the appointment. Thank you!</span></p>
          <button type="button" onclick="resetWizard()" class="border border-navy/15 font-bold px-6 py-3 rounded-md text-sm cursor-pointer hover:bg-fog"><span data-lang-block="am">ሌላ ቀጠሮ ያዙ</span><span data-lang-block="en">Book another</span></button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- ================= TRACK ORDER ================= -->
<section id="track" class="max-w-2xl mx-auto px-6 py-20 text-center">
  <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-light text-teal-dark text-xs font-bold mb-3">
    <i data-lucide="search" class="w-3.5 h-3.5"></i>
    <span data-lang-block="am">የትዕዛዝ ሁኔታ መከታተያ</span>
    <span data-lang-block="en">Order Status Tracking</span>
  </div>
  <h2 class="font-serif font-semibold text-2xl mb-2"><span data-lang-block="am">የትዕዛዝዎን ሁኔታ ይከታተሉ</span><span data-lang-block="en">Track Your Order Status</span></h2>
  <p class="text-xs text-navy/60 mb-5 max-w-md mx-auto"><span data-lang-block="am">የትዕዛዝ ቁጥርዎን ወይም ስልክዎን በማስገባት የትዕዛዝዎን የስራ ሂደት እና የቀጠሮ ቀን ይመልከቱ</span><span data-lang-block="en">Enter your order ID or phone number to see real-time order status and schedule.</span></p>
  <form onsubmit="trackOrder(event)" class="max-w-md mx-auto flex gap-2">
    <input id="track-input" type="text" placeholder="MEASH-000123 or phone" class="flex-1 rounded-md border border-navy/15 px-4 py-3 text-sm focus:outline-none focus:border-teal">
    <button type="submit" id="btn-track-submit" class="bg-navy hover:bg-teal-dark text-white font-bold px-6 rounded-md text-sm transition cursor-pointer"><span data-lang-block="am">ፈልግ</span><span data-lang-block="en">Track</span></button>
  </form>
  <div id="track-result" class="hidden mt-6 text-left"></div>
</section>

</section>

<!-- ================= CONTACT US & GOOGLE MAPS SECTION ================= -->
<section id="contact" class="py-24 bg-white dark:bg-slate-900 border-t border-navy/10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6">
    <div class="text-center max-w-2xl mx-auto mb-16">
      <div class="inline-flex items-center gap-2 text-teal-dark font-bold text-xs tracking-wider uppercase mb-3 bg-teal/10 px-3.5 py-1.5 rounded-full">
        <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
        <span data-lang-block="am">ያግኙን እና አድራሻችን</span><span data-lang-block="en">CONTACT US &amp; LOCATION</span>
      </div>
      <h2 class="font-serif font-bold text-3xl sm:text-4xl text-navy dark:text-white leading-tight">
        <span data-lang-block="am">ከእኛ ጋር በቀላሉ ይገናኙ</span>
        <span data-lang-block="en">Get in Touch with Meash Cleaning</span>
      </h2>
      <p class="text-navy/70 dark:text-slate-300 text-sm sm:text-base mt-2">
        <span data-lang-block="am">ለማንኛውም ጥያቄ፣ የቀጠሮ ማረጋገጫ ወይም የድርጅት ጽዳት ውይይት በማንኛውም ሰዓት ይደውሉልን።</span>
        <span data-lang-block="en">Call our direct lines, chat with us on Telegram, or visit our headquarters at Ayat Square, Addis Ababa, Ethiopia.</span>
      </p>
    </div>

    <div class="grid lg:grid-cols-2 gap-12 items-start">
      <!-- Contact Details & Form -->
      <div class="space-y-6">
        <!-- Phone & Email Cards Grid -->
        <div class="grid sm:grid-cols-2 gap-4">
          <div class="bg-fog dark:bg-slate-800 p-5 rounded-2xl border border-navy/10">
            <div class="w-10 h-10 rounded-xl bg-teal/10 text-teal-dark flex items-center justify-center mb-3">
              <i data-lucide="phone-call" class="w-5 h-5"></i>
            </div>
            <h4 class="font-bold text-xs uppercase tracking-wider text-navy/50 dark:text-slate-400 mb-1"><span data-lang-block="am">ቀጥታ ስልክ መስመሮች (24/7)</span><span data-lang-block="en">Direct Hotlines</span></h4>
            <a href="tel:{{ $phone1 }}" class="block font-bold text-base text-navy dark:text-white hover:text-teal transition">{{ $phone1 }}</a>
            <a href="tel:{{ $phone2 }}" class="block font-bold text-base text-navy dark:text-white hover:text-teal transition mt-0.5">{{ $phone2 }}</a>
          </div>

          <div class="bg-fog dark:bg-slate-800 p-5 rounded-2xl border border-navy/10">
            <div class="flex items-center justify-between mb-3">
              <div class="w-10 h-10 rounded-xl bg-teal/10 text-teal-dark flex items-center justify-center">
                <i data-lucide="message-square" class="w-5 h-5"></i>
              </div>
              <div class="flex gap-2">
                <a href="https://wa.me/251{{ ltrim($phone1, '0') }}?text=Hello%20Meash%20Cleaning%20Solution" target="_blank" rel="noopener noreferrer" class="text-[#25D366] hover:opacity-80" title="WhatsApp"><i data-lucide="message-circle" class="w-5 h-5"></i></a>
                <a href="{{ $websiteSettings['telegram_bot'] ?? 'https://t.me/meash_cleaning_solution_bot' }}" target="_blank" rel="noopener noreferrer" class="text-teal-dark hover:opacity-80" title="Telegram"><i data-lucide="send" class="w-5 h-5"></i></a>
              </div>
            </div>
            <h4 class="font-bold text-xs uppercase tracking-wider text-navy/50 dark:text-slate-400 mb-1"><span data-lang-block="am">ኦንላይን መልእክት እና ኢሜይል</span><span data-lang-block="en">Official Email &amp; Messaging</span></h4>
            <a href="mailto:{{ $email }}" class="block font-semibold text-xs text-navy dark:text-white hover:text-teal transition truncate">{{ $email }}</a>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
              <a href="https://wa.me/251{{ ltrim($phone1, '0') }}?text=Hello%20Meash%20Cleaning%20Solution" target="_blank" rel="noopener noreferrer" class="font-bold text-[#128C7E] dark:text-[#25D366] hover:underline flex items-center gap-1"><i data-lucide="message-circle" class="w-3.5 h-3.5"></i> WhatsApp</a>
              <span class="text-navy/30 dark:text-slate-500">•</span>
              <a href="{{ $websiteSettings['telegram_bot'] ?? 'https://t.me/meash_cleaning_solution_bot' }}" target="_blank" rel="noopener noreferrer" class="font-bold text-teal-dark hover:underline flex items-center gap-1"><i data-lucide="send" class="w-3.5 h-3.5"></i> Telegram</a>
            </div>
          </div>
        </div>

        <!-- Address & Working Hours -->
        <div class="bg-fog dark:bg-slate-800 p-6 rounded-2xl border border-navy/10 space-y-4">
          <div class="flex items-start gap-3">
            <i data-lucide="map-pin" class="w-5 h-5 text-teal shrink-0 mt-0.5"></i>
            <div>
              <h5 class="font-bold text-sm text-navy dark:text-white"><span data-lang-block="am">ዋና ቢሮ አድራሻ</span><span data-lang-block="en">Head Office Location</span></h5>
              <p class="text-xs text-navy/70 dark:text-slate-300 mt-0.5">{{ $addressAm }} • {{ $addressEn }}</p>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <i data-lucide="clock" class="w-5 h-5 text-teal shrink-0 mt-0.5"></i>
            <div>
              <h5 class="font-bold text-sm text-navy dark:text-white"><span data-lang-block="am">የስራ ሰዓት</span><span data-lang-block="en">Working Hours</span></h5>
              <p class="text-xs text-navy/70 dark:text-slate-300 mt-0.5">{{ $hoursAm }} • {{ $hoursEn }}</p>
            </div>
          </div>
        </div>

        <!-- Quick Quote Message Form -->
        <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-navy/10 shadow-sm">
          <h4 class="font-bold text-base text-navy dark:text-white mb-3 flex items-center gap-2">
            <i data-lucide="send" class="w-4 h-4 text-teal"></i>
            <span data-lang-block="am">ፈጣን መልእክት ወይም የዋጋ ጥያቄ ይላኩልን</span>
            <span data-lang-block="en">Send Us a Quick Message</span>
          </h4>
          <form id="quickMessageForm" onsubmit="submitQuickMessage(event)" class="space-y-3">
            <div id="quickMsgAlert" class="hidden p-3 rounded-lg text-xs font-bold"></div>
            <div class="grid sm:grid-cols-2 gap-3">
              <input type="text" id="quickMsgName" placeholder="ሙሉ ስም / Full Name" required class="w-full rounded-lg border border-navy/15 px-3.5 py-2.5 text-sm bg-white dark:bg-slate-900 text-navy dark:text-white focus:border-teal">
              <input type="tel" id="quickMsgPhone" placeholder="ስልክ ቁጥር / Phone Number (09...)" required class="w-full rounded-lg border border-navy/15 px-3.5 py-2.5 text-sm bg-white dark:bg-slate-900 text-navy dark:text-white focus:border-teal">
            </div>
            <textarea id="quickMsgText" placeholder="የሚፈልጉትን አገልግሎት ወይም ጥያቄዎን እዚህ ይጻፉ... / Message or cleaning requirements" rows="3" required class="w-full rounded-lg border border-navy/15 px-3.5 py-2.5 text-sm bg-white dark:bg-slate-900 text-navy dark:text-white focus:border-teal"></textarea>
            <button type="submit" id="quickMsgBtn" class="bg-navy hover:bg-teal-dark text-white font-bold text-xs uppercase tracking-wider px-6 py-3 rounded-lg transition flex items-center justify-center gap-2 w-full cursor-pointer">
              <i data-lucide="send" class="w-3.5 h-3.5"></i>
              <span id="quickMsgBtnText" data-lang-block="am">መልእክት ላክ</span>
              <span data-lang-block="en">Send Message</span>
            </button>
          </form>
          <script>
            async function submitQuickMessage(e) {
              e.preventDefault();
              const btn = document.getElementById('quickMsgBtn');
              const btnText = document.getElementById('quickMsgBtnText');
              const alertBox = document.getElementById('quickMsgAlert');
              const name = document.getElementById('quickMsgName').value.trim();
              const phone = document.getElementById('quickMsgPhone').value.trim();
              const message = document.getElementById('quickMsgText').value.trim();

              btn.disabled = true;
              btnText.textContent = 'እየተላከ ነው...';
              alertBox.className = 'hidden';

              try {
                const res = await fetch('/api/public/quick-message', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                  body: JSON.stringify({ name, phone, message })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                  alertBox.className = 'p-3 rounded-lg text-xs font-bold bg-green-50 text-green-800 border border-green-200 block';
                  alertBox.textContent = data.message || 'መልእክትዎ ደርሶናል! በደቂቃዎች ውስጥ እንደውላለን።';
                  document.getElementById('quickMessageForm').reset();
                } else {
                  alertBox.className = 'p-3 rounded-lg text-xs font-bold bg-red-50 text-red-800 border border-red-200 block';
                  alertBox.textContent = data.message || 'ስህተት ተፈጥሯል። እባክዎ እንደገና ይሞክሩ።';
                }
              } catch (err) {
                alertBox.className = 'p-3 rounded-lg text-xs font-bold bg-green-50 text-green-800 border border-green-200 block';
                alertBox.textContent = 'መልእክትዎ ደርሶናል! የሪሴፕሽን ሰራተኞቻችን በቅርቡ ይደውሉልዎታል።';
                document.getElementById('quickMessageForm').reset();
              } finally {
                btn.disabled = false;
                btnText.textContent = 'መልእክት ላክ';
              }
            }
          </script>
        </div>
      </div>

      <!-- Google Maps Embed Container -->
      <div class="rounded-3xl overflow-hidden border border-navy/10 shadow-lg h-[460px] lg:h-[580px] bg-slate-100 dark:bg-slate-800 relative">
        <iframe
          title="Meash Cleaning Location in Ayat Addis Ababa"
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15760.329864205844!2d38.861111!3d9.022778!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x164b910055555555%3A0x1111111111111111!2sAyat%20Addis%20Ababa!5e0!3m2!1sen!2set!4v1700000000000!5m2!1sen!2set"
          width="100%"
          height="100%"
          style="border:0;"
          allowfullscreen=""
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          class="w-full h-full grayscale-[20%] hover:grayscale-0 transition-all duration-500">
        </iframe>
        <!-- Floating Pill over Map -->
        <div class="absolute bottom-5 left-5 right-5 bg-white/95 dark:bg-slate-900/95 backdrop-blur p-4 rounded-2xl shadow-xl border border-navy/10 text-navy dark:text-white flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-teal-light text-teal-dark flex items-center justify-center shrink-0">
              <i data-lucide="map-pin" class="w-5 h-5 text-teal"></i>
            </div>
            <div>
              <h5 class="font-serif font-bold text-sm">Meash Cleaning HQ</h5>
              <p class="text-xs text-navy/60 dark:text-slate-400">Ayat Square, Addis Ababa, Ethiopia</p>
            </div>
          </div>
          <a href="https://maps.google.com/?q=Ayat+Square+Addis+Ababa" target="_blank" class="text-xs font-bold bg-navy hover:bg-teal text-white px-3 py-1.5 rounded-lg transition shrink-0">
            Open Map
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= FOOTER ================= -->
<footer class="bg-navy text-white/60 py-16 border-t border-white/10">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 grid md:grid-cols-4 gap-10 text-sm">
    <div>
      <div class="flex items-center gap-2.5 mb-3">
        <img src="/logo.jpg" class="w-9 h-9 rounded-lg object-cover shadow-sm">
        <span class="font-serif font-bold text-lg text-white">MEASH CLEANING SOLUTION</span>
      </div>
      <p class="text-xs text-teal-light font-semibold mb-3">"Your Environment, Our Responsibility."</p>
      <p class="text-xs text-white/70 leading-relaxed mb-3">
        ሜሽ የጽዳት አገልግሎት ዋና መሥሪያ ቤቱ በአያት አደባባይ የሚገኝ፣ ህጋዊ ፈቃድ ያለው እና ለንግድ ተቋማትና ለመኖሪያ ቤቶች ፕሪሚየም የጥልቅ ጽዳት አገልግሎት የሚሰጥ ተቋም ነው።
      </p>
      <div class="font-bold text-white text-sm space-y-1">
        <p class="flex items-center gap-2"><i data-lucide="phone" class="w-3.5 h-3.5 text-teal"></i> <a href="tel:{{ $phone1 }}" class="hover:text-teal transition">{{ $phone1 }}</a></p>
        <p class="flex items-center gap-2"><i data-lucide="phone" class="w-3.5 h-3.5 text-teal"></i> <a href="tel:{{ $phone2 }}" class="hover:text-teal transition">{{ $phone2 }}</a></p>
      </div>
    </div>
    <div>
      <p class="text-white font-bold text-sm uppercase tracking-wider mb-4">ይፋዊ የጽዳት አገልግሎቶች</p>
      <ul class="space-y-2 text-xs">
        @forelse($activeServices as $s)
          <li><a href="#services" onclick="preselectService('{{ $s->code }}')" class="hover:text-teal transition">{{ $s->name_am ?: $s->name_en }}</a></li>
        @empty
          <li><a href="#services" class="hover:text-teal transition">የወለል፣ የተቋማትና የሶፋ ጥልቅ ጽዳት</a></li>
        @endforelse
      </ul>
    </div>
    <div>
      <p class="text-white font-bold text-sm uppercase tracking-wider mb-4">ድርጅት እና መረጃ</p>
      <ul class="space-y-2 text-xs">
        <li><a href="#about" class="hover:text-teal transition">ስለ እኛ (About Us)</a></li>
        <li><a href="#advantage" class="hover:text-teal transition">ለምን ሜሽን ይመርጣሉ? (5 Pillars)</a></li>
        <li><a href="#process" class="hover:text-teal transition">ቀላል አሰራራችን (5-Step Process)</a></li>
        <li><a href="#gallery" class="hover:text-teal transition">የስራ ጋለሪ (Before &amp; After)</a></li>
        <li><a href="#reviews" class="hover:text-teal transition">የደንበኞች አስተያየት (Reviews)</a></li>
        <li><a href="#contact" class="hover:text-teal transition">አድራሻ እና ካርታ (Contact)</a></li>
        <li class="pt-2">
          <!-- Staff & Admin Secure Access Button -->
          <button type="button" onclick="openStaffModal()" class="inline-flex items-center gap-1.5 text-xs text-teal hover:text-white transition cursor-pointer py-1 border-b border-teal/40 hover:border-white">
            <i data-lucide="lock" class="w-3.5 h-3.5 text-teal"></i>
            <span>የሰራተኞች እና አድሚን መግቢያ (Staff OS)</span>
          </button>
        </li>
      </ul>
    </div>
    <div>
      <p class="text-white font-bold text-sm uppercase tracking-wider mb-4">ማህበራዊ ትስስር ({{ $socialHandle }})</p>
      <ul class="space-y-2 text-xs">
        <li><a href="{{ $websiteSettings['telegram_channel'] ?? 'https://t.me/meashcleaningsolution' }}" target="_blank" class="hover:text-teal flex items-center gap-1.5 transition"><i data-lucide="send" class="w-3.5 h-3.5 text-teal"></i> Telegram Channel (@meashcleaningsolution)</a></li>
        <li><a href="{{ $websiteSettings['telegram_support_url'] ?? 'https://t.me/meashcleaning' }}" target="_blank" class="hover:text-teal flex items-center gap-1.5 transition"><i data-lucide="message-circle" class="w-3.5 h-3.5 text-teal"></i> Direct Telegram (@meashcleaning)</a></li>
        <li><a href="{{ $websiteSettings['telegram_bot'] ?? 'https://t.me/meash_cleaning_solution_bot' }}" target="_blank" class="hover:text-teal flex items-center gap-1.5 transition"><i data-lucide="bot" class="w-3.5 h-3.5 text-teal"></i> Telegram Bot</a></li>
        <li><a href="{{ $tiktokUrl }}" target="_blank" class="hover:text-teal flex items-center gap-1.5 transition"><i data-lucide="video" class="w-3.5 h-3.5 text-teal"></i> TikTok</a></li>
        <li><a href="{{ $instagramUrl }}" target="_blank" class="hover:text-teal flex items-center gap-1.5 transition"><i data-lucide="camera" class="w-3.5 h-3.5 text-teal"></i> Instagram</a></li>
        <li><a href="{{ $facebookUrl }}" target="_blank" class="hover:text-teal flex items-center gap-1.5 transition"><i data-lucide="facebook" class="w-3.5 h-3.5 text-teal"></i> Facebook</a></li>
      </ul>
      <div class="mt-4 pt-4 border-t border-white/10 text-xs text-white/50">
        {{ $addressAm }} ({{ $addressEn }})
      </div>
    </div>
  </div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-12 pt-6 border-t border-white/10 flex flex-wrap items-center justify-between gap-4 text-xs text-white/40">
    <div>© 2026 MEASH CLEANING SOLUTION. All rights reserved. Slogan: "Your Environment, Our Responsibility."</div>
    <div>
      <button type="button" onclick="openStaffModal()" class="inline-flex items-center gap-1.5 text-white/40 hover:text-white transition cursor-pointer">
        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-teal"></i>
        <span>Meash Staff Management System</span>
      </button>
    </div>
  </div>
</footer>

</main>

<!-- ================= STAFF & ADMIN SECURE AUTH MODAL ================= -->
<div id="staff-auth-modal" class="fixed inset-0 z-[70] hidden bg-navy/80 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto transition-all duration-300" onclick="if(event.target === this) closeStaffModal()">
  <div class="relative w-full max-w-sm max-h-[90vh] overflow-y-auto bg-white rounded-2xl shadow-2xl p-6 border border-navy/10 text-navy my-auto">
    <!-- Header -->
    <div class="flex items-center justify-between pb-3 border-b border-navy/10">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-teal-light text-teal-dark flex items-center justify-center">
          <i data-lucide="shield-check" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-serif font-bold text-sm text-navy">የሰራተኞች እና አስተዳዳሪ መግቢያ</h3>
          <p class="text-[11px] text-navy/50">Staff & Admin Access — MEASH CLEANING SOLUTION</p>
        </div>
      </div>
      <button type="button" onclick="closeStaffModal()" class="w-7 h-7 rounded-lg text-navy/40 hover:text-navy hover:bg-fog flex items-center justify-center transition cursor-pointer">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>

    <!-- Error message -->
    <div id="staff-login-error" class="hidden mt-3 p-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
      <i data-lucide="alert-circle" class="w-4 h-4 text-red-500 shrink-0"></i>
      <span id="staff-login-error-text">የተሳሳተ የይለፍ ቃል ነው!</span>
    </div>

    <!-- Secure Login Form (Always requires password) -->
    <div class="mt-4">
      <div class="mb-3 p-2.5 rounded-xl bg-slate-50 border border-navy/10 flex items-center gap-2">
        <i data-lucide="lock" class="w-4 h-4 text-teal shrink-0"></i>
        <p class="text-[11px] text-navy/70 leading-tight min-w-0 flex-1 break-words">ደህንነቱ የተጠበቀ የአስተዳዳሪ እና ሰራተኞች መግቢያ። እባክዎ ኢሜይል/ስልክ ቁጥርዎን እና የይለፍ ቃልዎን ያስገቡ።</p>
      </div>

      <form onsubmit="handleStaffLogin(event)" class="space-y-3.5">
        <div>
          <label class="block text-[11px] font-bold text-navy/70 mb-1">ኢሜይል ወይም ስልክ ቁጥር (Email or Phone)</label>
          <input type="text" id="staff-login-input" required placeholder="ምሳሌ፦ 0911000001" class="w-full px-3.5 py-2.5 rounded-lg border border-navy/20 bg-fog text-navy text-xs font-semibold focus:outline-none focus:border-teal">
        </div>

        <div>
          <label class="block text-[11px] font-bold text-navy/70 mb-1">የይለፍ ቃል (Password)</label>
          <div class="relative">
            <input type="password" id="staff-password-input" required placeholder="••••••••••••" class="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-navy/20 bg-fog text-navy text-xs focus:outline-none focus:border-teal">
            <button type="button" onclick="toggleStaffPasswordVisibility()" class="absolute right-3 top-1/2 -translate-y-1/2 text-navy/40 hover:text-teal transition cursor-pointer">
              <i id="staff-pwd-eye" data-lucide="eye" class="w-4 h-4"></i>
            </button>
          </div>
        </div>

        <div class="flex items-center gap-2 pt-2">
          <button type="button" onclick="closeStaffModal()" class="w-1/3 py-2.5 border border-navy/20 hover:bg-fog text-navy/60 font-bold text-xs rounded-lg transition cursor-pointer">ይቅር</button>
          <button type="submit" id="staff-login-btn" class="w-2/3 py-2.5 bg-navy hover:bg-teal-dark text-white font-bold text-xs rounded-lg transition flex items-center justify-center gap-1.5 shadow-md cursor-pointer">
            <i data-lucide="log-in" class="w-3.5 h-3.5"></i>
            <span id="staff-login-btn-text">ግባ (Login)</span>
          </button>
        </div>

        <div class="pt-2 text-center border-t border-navy/10">
          <a href="/app" class="text-[11px] text-teal hover:underline font-bold inline-flex items-center gap-1">
            <span>ወደ ዋናው የአስተዳዳሪ ሲስተም ገጽ ይሂዱ (Go to /app)</span>
            <i data-lucide="external-link" class="w-3 h-3"></i>
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // ============================================================
  // ETHIOPIAN CALENDAR UTILITY (Full Bidirectional Converter)
  // ============================================================
  function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }
  window.escapeHtml = escapeHtml;

  const EC = {
    months: ['መስከረም','ጥቅምት','ህዳር','ታህሳስ','ጥር','የካቲት','መጋቢት','ሚያዚያ','ግንቦት','ሰኔ','ሐምሌ','ነሐሴ','ጳጉሜ'],
    monthsEn: ['Meskerem','Tikimt','Hidar','Tahsas','Tir','Yekatit','Megabit','Miyazia','Ginbot','Sene','Hamle','Nehase','Pagume'],
    weekdays: ['ሰኞ','ማክሰኞ','ረቡዕ','ሐሙስ','አርብ','ቅዳሜ','እሁድ'],

    // Convert Gregorian → Ethiopian
    toEth(gcDate) {
      const d = new Date(gcDate);
      const gcY = d.getFullYear(), gcM = d.getMonth() + 1, gcD = d.getDate();
      // Julian Day Number from Gregorian
      const a = Math.floor((14 - gcM) / 12);
      const y = gcY + 4800 - a;
      const m = gcM + 12 * a - 3;
      const jdn = gcD + Math.floor((153*m+2)/5) + 365*y + Math.floor(y/4) - Math.floor(y/100) + Math.floor(y/400) - 32045;
      // Ethiopian from JDN
      const r = (jdn - 1723856) % 1461;
      const n = r % 365 + 365 * Math.floor(r / 1460);
      const etY = 4 * Math.floor((jdn - 1723856) / 1461) + Math.floor(r / 365) - Math.floor(r / 1460);
      const etM = Math.floor(n / 30) + 1;
      const etD = n % 30 + 1;
      return { year: etY, month: etM, day: etD };
    },

    // Convert Ethiopian → Gregorian date string (YYYY-MM-DD)
    toGreg(etY, etM, etD) {
      // JDN from Ethiopian
      const jdn = etD + 30 * (etM - 1) + 365 * etY + Math.floor(etY / 4) + 1723856;
      // Gregorian from JDN
      const a = jdn + 32044;
      const b = Math.floor((4*a+3)/146097);
      const c = a - Math.floor(146097*b/4);
      const dd = Math.floor((4*c+3)/1461);
      const e = c - Math.floor(1461*dd/4);
      const mm = Math.floor((5*e+2)/153);
      const gcD = e - Math.floor((153*mm+2)/5) + 1;
      const gcM = mm + 3 - 12 * Math.floor(mm/10);
      const gcY = 100*b + dd - 4800 + Math.floor(mm/10);
      return `${gcY}-${String(gcM).padStart(2,'0')}-${String(gcD).padStart(2,'0')}`;
    },

    // Format Ethiopian date for display
    formatEth(etY, etM, etD) {
      return `${etD} ${this.months[etM-1]} ${etY} ዓ.ም`;
    },

    // Format Gregorian date for display
    formatGreg(gcDateStr) {
      const d = new Date(gcDateStr + 'T00:00:00');
      return d.toLocaleDateString('en-GB', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    },

    // Ethiopian time: GC 06:00 = 12:00 ET (midnight), GC 12:00 = 6:00 ET (noon)
    toEthTime(hours24, minutes) {
      let ethH = (hours24 + 18) % 12;
      if (ethH === 0) ethH = 12;
      const min = String(minutes).padStart(2,'0');
      let period;
      if (hours24 >= 0 && hours24 < 6) period = 'ለሊቱ';
      else if (hours24 >= 6 && hours24 < 12) period = 'ጠዋቱ';
      else if (hours24 >= 12 && hours24 < 18) period = 'ከሰዓቱ';
      else period = 'ሌሊቱ';
      return `${ethH}:${min} ${period}`;
    },

    // Format "now" with both calendars
    formatNow() {
      const now = new Date();
      const eth = this.toEth(now);
      const gcStr = this.formatGreg(now.toISOString().split('T')[0]);
      const ethStr = this.formatEth(eth.year, eth.month, eth.day);
      const timeGC = now.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit' });
      const timeETH = this.toEthTime(now.getHours(), now.getMinutes());
      return { ethStr, gcStr, timeGC, timeETH };
    }
  };

  // ---- Ethiopian Date Picker Initialization ----
  function initEthDatePicker() {
    const today = EC.toEth(new Date());

    // Populate year selector (today to +2 years)
    const yearSel = document.getElementById('eth-year');
    if (!yearSel) return;
    yearSel.innerHTML = '';
    for (let y = today.year; y <= today.year + 2; y++) {
      const opt = document.createElement('option');
      opt.value = y;
      opt.textContent = y + ' ዓ.ም';
      yearSel.appendChild(opt);
    }
    yearSel.value = today.year;
    document.getElementById('eth-month').value = today.month;
    updateEthDays();
    // Set today's day
    const daySel = document.getElementById('eth-day');
    if (daySel) daySel.value = today.day;
    syncEthToGreg();
  }

  function updateEthDays() {
    const m = parseInt(document.getElementById('eth-month')?.value || 1);
    const y = parseInt(document.getElementById('eth-year')?.value || 0);
    // Pagume has 6 days in an Ethiopian leap year (year % 4 === 3), else 5.
    const maxDay = (m === 13) ? ((y % 4 === 3) ? 6 : 5) : 30;
    const daySel = document.getElementById('eth-day');
    if (!daySel) return;
    const cur = parseInt(daySel.value) || 1;
    daySel.innerHTML = '';
    for (let d = 1; d <= maxDay; d++) {
      const opt = document.createElement('option');
      opt.value = d;
      opt.textContent = d;
      daySel.appendChild(opt);
    }
    daySel.value = Math.min(cur, maxDay);
  }

  function switchCalendarMode(mode) {
    const ethBlock = document.getElementById('cal-mode-eth');
    const gregBlock = document.getElementById('cal-mode-greg');
    const tabEth = document.getElementById('tab-cal-eth');
    const tabGreg = document.getElementById('tab-cal-greg');

    if (mode === 'eth') {
      ethBlock.classList.remove('hidden');
      gregBlock.classList.add('hidden');
      tabEth.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 bg-white text-navy shadow-sm cursor-pointer';
      tabGreg.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 text-navy/60 hover:text-navy cursor-pointer';
      syncEthToGreg();
    } else {
      ethBlock.classList.add('hidden');
      gregBlock.classList.remove('hidden');
      tabGreg.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 bg-white text-navy shadow-sm cursor-pointer';
      tabEth.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 text-navy/60 hover:text-navy cursor-pointer';
      const curDate = document.getElementById('book-date')?.value || new Date().toISOString().split('T')[0];
      const gregInput = document.getElementById('greg-date-input');
      if (gregInput) {
        gregInput.value = curDate;
        gregInput.min = new Date().toISOString().split('T')[0];
      }
      syncGregToEth();
    }
    if (window.lucide) lucide.createIcons();
  }

  function syncEthToGreg() {
    const etY = parseInt(document.getElementById('eth-year')?.value);
    const etM = parseInt(document.getElementById('eth-month')?.value);
    const etD = parseInt(document.getElementById('eth-day')?.value);
    if (!etY || !etM || !etD) return;

    const gcDateStr = EC.toGreg(etY, etM, etD);
    const hidden = document.getElementById('book-date');
    if (hidden) hidden.value = gcDateStr;

    const display = document.getElementById('greg-display');
    if (display) {
      display.textContent = EC.formatGreg(gcDateStr);
    }
  }

  function syncGregToEth() {
    const gregInput = document.getElementById('greg-date-input');
    if (!gregInput || !gregInput.value) return;

    const gcDateStr = gregInput.value;
    const hidden = document.getElementById('book-date');
    if (hidden) hidden.value = gcDateStr;

    const eth = EC.toEth(gcDateStr);
    const ethFormatted = EC.formatEth(eth.year, eth.month, eth.day);

    const ethDisplay = document.getElementById('eth-display-from-greg');
    if (ethDisplay) {
      ethDisplay.textContent = ethFormatted;
    }

    // Also update the Ethiopian dropdowns behind the scenes
    const yearSel = document.getElementById('eth-year');
    const monthSel = document.getElementById('eth-month');
    const daySel = document.getElementById('eth-day');
    if (yearSel && monthSel && daySel) {
      yearSel.value = eth.year;
      monthSel.value = eth.month;
      updateEthDays();
      daySel.value = eth.day;
    }
  }

  function setTimeSlot(val) {
    const hidden = document.getElementById('book-time-slot');
    if (hidden) hidden.value = val;
    const err = document.getElementById('time-slot-error');
    if (err) err.classList.add('hidden');
  }

  function validateStep2AndContinue() {
    const subcity = (document.getElementById('book-subcity')?.value || '').trim();
    const address = (document.getElementById('book-address')?.value || '').trim();
    const err = document.getElementById('step2-error');
    if (!subcity || !address) {
      if (err) err.classList.remove('hidden');
      if (!subcity) document.getElementById('book-subcity')?.focus();
      else document.getElementById('book-address')?.focus();
      return;
    }
    if (err) err.classList.add('hidden');
    showWizardStep(3);
  }

  function validateStep3AndContinue() {
    const date = document.getElementById('book-date')?.value;
    const slot = document.getElementById('book-time-slot')?.value;
    const dateErr = document.getElementById('date-slot-error');
    const slotErr = document.getElementById('time-slot-error');
    if (dateErr) dateErr.classList.add('hidden');
    if (slotErr) slotErr.classList.add('hidden');

    if (!date) {
      if (dateErr) dateErr.classList.remove('hidden');
      return;
    }
    if (!slot) {
      if (slotErr) slotErr.classList.remove('hidden');
      return;
    }
    showWizardStep(4);
  }

  // ---- Mobile Navigation Drawer Toggle ----
  function toggleMobileNav() {
    const drawer = document.getElementById('mobile-nav-drawer');
    const icon = document.getElementById('mobile-menu-icon');
    if (!drawer) return;
    drawer.classList.toggle('hidden');
    const isOpen = !drawer.classList.contains('hidden');
    if (icon) {
      icon.setAttribute('data-lucide', isOpen ? 'x' : 'menu');
      if (window.lucide) lucide.createIcons();
    }
    if (isOpen) {
      document.body.style.overflow = 'hidden';
      document.documentElement.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
      document.documentElement.style.overflow = '';
    }
  }

  function closeMobileNav() {
    const drawer = document.getElementById('mobile-nav-drawer');
    const icon = document.getElementById('mobile-menu-icon');
    if (drawer && !drawer.classList.contains('hidden')) {
      drawer.classList.add('hidden');
      document.body.style.overflow = '';
      document.documentElement.style.overflow = '';
      if (icon) {
        icon.setAttribute('data-lucide', 'menu');
        if (window.lucide) lucide.createIcons();
      }
    }
  }

  // Close mobile drawer when clicking backdrop outside drawer content
  document.addEventListener('click', (e) => {
    const drawer = document.getElementById('mobile-nav-drawer');
    const btn = document.getElementById('mobile-menu-btn');
    if (drawer && !drawer.classList.contains('hidden')) {
      if (e.target === drawer) {
        closeMobileNav();
      }
    }
  });

  // ---- Language toggle ----
  function setLang(l){
    // 'both' mode is retired: it showed Amharic and English side by side with
    // no space between them. Old saved preferences are migrated to Amharic.
    if (l === 'both') l = 'am';
    document.documentElement.setAttribute('data-lang', l);
    localStorage.setItem('meash_lang', l);
    document.querySelectorAll('.lang-btn').forEach(btn => {
      btn.classList.remove('active-lang', 'bg-teal', 'bg-navy', 'text-white', 'shadow-sm');
      btn.classList.add('text-slate-600', 'dark:text-slate-300');
    });
    document.querySelectorAll(`.lang-btn[data-lang="${l}"]`).forEach(btn => {
      btn.classList.add('active-lang', 'bg-teal', 'text-white', 'shadow-sm');
      btn.classList.remove('text-slate-600', 'dark:text-slate-300');
    });
    // Fallback for buttons without data-lang
    document.querySelectorAll('.lang-btn').forEach(btn => {
      if (btn.getAttribute('onclick')?.includes(`'${l}'`)) {
        btn.classList.add('active-lang', 'bg-teal', 'text-white', 'shadow-sm');
        btn.classList.remove('text-slate-600', 'dark:text-slate-300');
      }
    });
    if (window.lucide) lucide.createIcons();
  }

  (function(){
    const saved = localStorage.getItem('meash_lang');
    if (saved) { setLang(saved); }
    else {
      setLang('am');
    }
  })();

  // ---- Live prices from backend ----
  const initialLiveServices = @json($activeServices);
  let servicePrices = {};
  initialLiveServices.forEach(svc => {
    if (svc.id != null && svc.base_price != null) {
      servicePrices[svc.id] = parseFloat(svc.base_price);
    }
  });
  if (Object.keys(servicePrices).length === 0) {
    // Fallback only when the database has no services yet. Prices mirror
    // DatabaseSeeder insertion order (id 1 = floor, 2 = facilities, ...).
    servicePrices = { 1: 60, 2: 3500, 3: 3000, 4: 4500, 5: 70, 6: 350, 7: 350, 8: 80, 9: 600, 10: 2500, 11: 3500, 12: 120 };
  }
  let currency = 'ETB';

  async function loadServicePrices(){
    try {
      const res = await fetch('/api/public/services');
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const data = await res.json();
      const list = Array.isArray(data) ? data : (data.data || []);
      if (!list.length) throw new Error('Empty services list');
      list.forEach(svc => {
        if (svc.id != null && svc.base_price != null) {
          servicePrices[svc.id] = parseFloat(svc.base_price);
        }
      });
      const warnEl = document.getElementById('price-fallback-warning');
      if (warnEl) warnEl.classList.add('hidden');
    } catch (err) {
      console.warn('Could not load live prices from /api/public/services', err);
      const warnEl = document.getElementById('price-fallback-warning');
      if (warnEl) warnEl.classList.remove('hidden');
    }
    renderPrices();
    updateWizardTotal();
  }

  async function loadPublicReviews(){
    try {
      const res = await fetch('/api/public/reviews');
      if (!res.ok) return;
      const data = await res.json();
      const list = Array.isArray(data) ? data : (data.data || []);
      // Honest aggregate: the hardcoded "4.9 / 5.0 (300+ Reviews)" was
      // never real data. With approved reviews, show their real average
      // and count; with none, hide the aggregate entirely (the 3 sample
      // cards below stay as fallback content).
      const aggBlock = document.getElementById('reviews-aggregate-block');
      const aggText = document.getElementById('reviews-aggregate-text');
      if (list && list.length > 0) {
        const avg = list.reduce((a, r) => a + (parseFloat(r.rating) || 0), 0) / list.length;
        if (aggText) aggText.textContent = avg.toFixed(1) + ' / 5.0 (' + list.length + (list.length === 1 ? ' Review)' : ' Reviews)');
        if (aggBlock) aggBlock.classList.remove('hidden');
        const grid = document.getElementById('dynamic-reviews-grid');
        if (grid) {
          grid.innerHTML = list.slice(0, 6).map(r => `
            <div class="border-l-2 border-teal pl-5 bg-fog/50 dark:bg-slate-800/50 p-4 rounded-r-xl">
              <div class="flex text-amber-400 mb-2">
                ${'<i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>'.repeat(r.rating || 5)}
              </div>
              <p class="text-[15px] text-navy/80 dark:text-slate-200 leading-relaxed">"${escapeHtml(r.comment || '')}"</p>
              <p class="mt-4 font-bold text-sm text-teal-dark">${escapeHtml(r.customer ? r.customer.full_name : 'Verified Customer')}</p>
            </div>
          `).join('');
          if (window.lucide) lucide.createIcons();
        }
      } else {
        // No approved reviews yet: do not show the fake "300+ Reviews"
        // aggregate as if it were real.
        if (aggBlock) aggBlock.classList.add('hidden');
      }
    } catch (e) {
      console.warn('Could not load dynamic reviews:', e);
    }
  }

  function formatPrice(v){
    if (v == null || isNaN(v)) return (document.documentElement.getAttribute('data-lang') === 'am') ? 'ዋጋ ለማወቅ ይደውሉ' : 'Call for price';
    return v.toLocaleString() + ' ' + currency;
  }

  function renderPrices(){
    document.querySelectorAll('.svc-price').forEach(el => {
      const id = el.getAttribute('data-svc-price');
      if (servicePrices[id] != null) {
        const unit = el.getAttribute('data-svc-unit');
        el.textContent = formatPrice(servicePrices[id]) + (unit && unit !== 'service' ? ' / ' + unit : '');
      }
    });
    document.querySelectorAll('.wiz-price').forEach(el => {
      const id = el.getAttribute('data-wiz-price');
      const unit = el.getAttribute('data-wiz-unit');
      if (servicePrices[id] != null) el.textContent = servicePrices[id].toLocaleString() + ' ' + currency + (unit ? ' / ' + unit : '');
    });
  }
  // NOTE: renderPrices()/updateWizardTotal() are called after the wizard state
  // (selectedSvcId/wizardQty) is initialised below. Calling them here used to
  // throw "Cannot access 'selectedSvcId' before initialization" (TDZ), which
  // aborted the rest of this script: the Ethiopian date picker never
  // initialised and the quantity +/- buttons never worked.

  // ---- Booking wizard state ----
  // Service code -> live DB id, built from the services actually loaded above.
  // (The old hard-coded id map was stale: ids duplicated and did not match the
  // database, so "Book Now" from a service card priced the wrong service.)
  const svcMap = {};
  initialLiveServices.forEach(s => { if (s.code != null && s.id != null) svcMap[s.code] = s.id; });
  const svcNames = {
    floor_ceramic: 'Ceramic & Hardwood Floor / የሴራሚክ እና የእንጨት ወለሎች ጥልቅ ጽዳት',
    facilities_general: 'Deep & General Facilities Cleaning / የተቋማት አጠቃላይ እና ጥልቅ ጽዳት',
    kitchen_dining: 'Kitchen & Dining Sanitation / የወጥ ቤት እና የምግብ ማስተናገጃ ቦታዎች ጽዳት',
    post_construction: 'Post-Construction Handover / ከግንባታ ማጠናቀቅ በኋላ የሚደረግ የርክክብ ጽዳት',
    glass: 'High-Rise Window & Glass / የመስታወት እና የፎቅ ውጫዊ ገጽታ ጽዳት',
    upholstery_carpet: 'Upholstery & Carpet Deep Care / የሶፋ እና ምንጣፍ ጥልቅ እንክብካቤ',
    sofa: 'Sofa Cleaning / የሶፋ እጥበት',
    carpet: 'Carpet Cleaning / የምንጣፍ እጥበት',
    mattress: 'Mattress Sanitization / የፍራሽ እጥበት',
    home: 'Full Residential Deep Clean / የመኖሪያ ቤት ጥልቅ ጽዳት',
    office: 'Office & Facility Cleaning / የቢሮና ተቋማት ጽዳት',
    curtain: 'Curtains & Drapes Steam / የመጋረጃ እጥበት',
    hotel_clean: 'Hotel Deep Cleaning / የሆቴል ጥልቅ ጽዳት',
    restaurant_clean: 'Restaurant Sanitization / የሬስቶራንት ጽዳት',
    hall_chair_clean: 'Hall & Chairs Cleaning / የአዳራሽ ወንበሮች እጥበት',
    contract_cleaning: 'Regular Contract Cleaning / ቋሚ የኮንትራት ውል'
  };

  // Default to the first service shown in the wizard so the highlighted button
  // and the priced service always agree.
  let selectedSvc = @json($activeServices->take(8)->first()?->code ?? 'sofa');
  let selectedSvcId = {{ (int) ($activeServices->take(8)->first()?->id ?? 1) }};
  let wizardQty = 1;
  const FIRST_WIZARD_SVC = @json($activeServices->take(8)->first()?->code ?? 'sofa');

  function preselectService(svc){
    selectedSvc = svc;
    selectedSvcId = svcMap[svc] || 1;
    document.querySelectorAll('.svc-btn').forEach(b => { b.classList.remove('border-navy'); b.classList.add('border-navy/10'); });
    const btn = document.querySelector(`.svc-btn[onclick*="'${svc}'"]`);
    if (btn) { btn.classList.remove('border-navy/10'); btn.classList.add('border-navy'); }
    showWizardStep(1);
    updateWizardTotal();
  }

  function selectServiceForBooking(svc, id){
    selectedSvc = svc;
    selectedSvcId = id;
    document.querySelectorAll('.svc-btn').forEach(b => { b.classList.remove('border-navy'); b.classList.add('border-navy/10'); });
    event?.currentTarget?.classList.remove('border-navy/10');
    event?.currentTarget?.classList.add('border-navy');
    updateWizardTotal();
  }

  function changeQty(delta){
    wizardQty = Math.max(1, wizardQty + delta);
    document.getElementById('qty-display').innerText = wizardQty;
    updateWizardTotal();
  }

  let selectedSubPlan = 'one_time';
  let livePricingRules = @json($pricingRules);
  const subPlanDiscounts = {
    one_time: 0,
    weekly: Number(livePricingRules?.weekly_discount ?? 15),
    biweekly: Number(livePricingRules?.biweekly_discount ?? 10),
    monthly: Number(livePricingRules?.monthly_discount ?? 5)
  };

  // Fetch live settings asynchronously to keep pricing and accounts always fresh
  (async function loadLiveSettings() {
    try {
      const res = await fetch('/api/public/settings');
      if (res.ok) {
        const d = await res.json();
        if (d.pricing_rules) {
          livePricingRules = d.pricing_rules;
          subPlanDiscounts.weekly = Number(d.pricing_rules.weekly_discount ?? 15);
          subPlanDiscounts.biweekly = Number(d.pricing_rules.biweekly_discount ?? 10);
          subPlanDiscounts.monthly = Number(d.pricing_rules.monthly_discount ?? 5);
        }
      }
    } catch (_) {}
  })();

  function updateSubscriptionDiscount(){
    const checked = document.querySelector('input[name="sub_plan"]:checked');
    selectedSubPlan = checked ? checked.value : 'one_time';
    const discPercent = subPlanDiscounts[selectedSubPlan] || 0;
    const price = servicePrices[selectedSvcId] || 0;
    const baseTotal = price * wizardQty;
    const discountAmount = (baseTotal * discPercent) / 100;
    const finalTotal = Math.max(0, baseTotal - discountAmount);

    const totalEl = document.getElementById('wiz-total-2');
    if (totalEl) {
      if (discPercent > 0) {
        totalEl.innerHTML = `<span class="line-through text-xs text-navy/40 mr-1.5">${baseTotal.toLocaleString()} ETB</span> ${finalTotal.toLocaleString()} ETB`;
      } else {
        totalEl.textContent = baseTotal.toLocaleString() + ' ' + currency;
      }
    }

    const hint = document.getElementById('sub-discount-hint');
    const hintText = document.getElementById('sub-discount-text');
    if (hint && hintText) {
      if (discPercent > 0) {
        hint.classList.remove('hidden');
        hintText.innerText = `${selectedSubPlan === 'weekly' ? 'የሳምንት' : (selectedSubPlan === 'biweekly' ? 'የ2 ሳምንት' : 'የወር')} እቅድ ስለመረጡ ${discPercent}% (${discountAmount.toLocaleString()} ብር) ቅናሽ አግኝተዋል!`;
      } else {
        hint.classList.add('hidden');
      }
    }
  }

  function updateWizardTotal(){
    const price = servicePrices[selectedSvcId];
    const totalEl = document.getElementById('wiz-total');
    const text = price != null ? ((price * wizardQty).toLocaleString() + ' ' + currency) : formatPrice(null);
    if (totalEl) totalEl.textContent = text;
    updateSubscriptionDiscount();
  }

  // Initial price render — placed here, after the wizard state above exists.
  renderPrices();
  updateWizardTotal();

  // Clear the whole booking wizard back to its initial state.
  function resetWizard(){
    const form = document.getElementById('booking-form');
    if (form) form.reset();
    wizardQty = 1;
    const qtyEl = document.getElementById('qty-display');
    if (qtyEl) qtyEl.innerText = wizardQty;
    const step2Err = document.getElementById('step2-error');
    if (step2Err) step2Err.classList.add('hidden');
    document.getElementById('step-success')?.classList.add('hidden');
    preselectService(FIRST_WIZARD_SVC);
    showWizardStep(1);
  }

  function showWizardStep(n){
    document.querySelectorAll('.wizard-step').forEach(s => s.classList.add('hidden'));
    const target = document.getElementById('step-' + n);
    if (target) target.classList.remove('hidden');
    document.querySelectorAll('.step-dot').forEach(d => {
      d.classList.remove('text-teal-dark', 'bg-teal/10', 'font-black');
    });
    const dot = document.querySelector(`.step-dot[data-step="${n}"]`);
    if (dot) {
      dot.classList.add('text-teal-dark', 'bg-teal/10', 'font-black');
    }
    if (n === 4) updateWizardTotal();
    if (window.lucide) lucide.createIcons();
  }

  async function submitBooking(e){
    e.preventDefault();
    const btn = document.getElementById('btn-submit-booking');
    btn.disabled = true;
    const unitPrice = servicePrices[selectedSvcId] || 0;
    const plan = document.querySelector('input[name="sub_plan"]:checked')?.value || 'one_time';
    const payMethod = document.querySelector('input[name="web_payment_method"]:checked')?.value || 'cash';
    const payload = {
      customer_name: document.getElementById('book-name').value,
      customer_phone: document.getElementById('book-phone').value,
      subcity: document.getElementById('book-subcity').value,
      address: document.getElementById('book-address').value + (document.getElementById('book-landmark').value ? ` (${document.getElementById('book-landmark').value})` : ''),
      landmark: document.getElementById('book-landmark').value || null,
      latitude: null,
      longitude: null,
      appointment_date: document.getElementById('book-date').value,
      appointment_time_slot: document.getElementById('book-time-slot').value,
      subscription_plan: plan,
      payment_method: payMethod,
      notes: (document.getElementById('book-notes').value || 'Website booking') + ' | Property: ' + (document.getElementById('book-property-type')?.value || 'residential'),
      items: [{
        service_id: selectedSvcId,
        item_name: svcNames[selectedSvc] || 'Cleaning Service',
        quantity: wizardQty,
        unit_price: unitPrice,
      }]
    };

    try {
      const res = await fetch('/api/public/book', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (res.ok && (data.success || data.booking_id)) {
        const errMsg = document.getElementById('booking-error-msg');
        if (errMsg) errMsg.classList.add('hidden');
        document.getElementById('step-4').classList.add('hidden');
        document.getElementById('step-success').classList.remove('hidden');
        document.getElementById('success-booking-id').innerText = data.order_number || data.booking_id;
        if (window.lucide) lucide.createIcons();
      } else {
        const errMsg = document.getElementById('booking-error-msg');
        const text = data.message || 'ይቅርታ፣ ቀጠሮውን መመዝገብ አልተቻለም። እባክዎ በ {{ $phone1 }} ይደውሉ።';
        if (errMsg) {
          errMsg.innerText = text;
          errMsg.classList.remove('hidden');
        }
      }
    } catch (err) {
      const errMsg = document.getElementById('booking-error-msg');
      const text = 'የኔትወርክ ግንኙነት ስህተት አጋጥሟል። እባክዎ በ {{ $phone1 }} በቀጥታ ይደውሉ።';
      if (errMsg) {
        errMsg.innerText = text;
        errMsg.classList.remove('hidden');
      }
    } finally {
      btn.disabled = false;
    }
  }

  function openWebRescheduleModal(orderNumber) {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowStr = tomorrow.toISOString().split('T')[0];

    const modalHtml = `
      <div id="web-reschedule-backdrop" class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto" onclick="if(event.target === this) this.remove()">
        <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-5 sm:p-6 shadow-2xl space-y-4 my-auto border border-navy/10">
          <div class="flex items-center justify-between pb-3 border-b border-navy/10">
            <div class="flex items-center gap-2">
              <i data-lucide="calendar-clock" class="w-5 h-5 text-teal"></i>
              <h3 class="font-bold text-navy dark:text-white text-sm">ቀጠሮ ቀን ማስተላለፊያ (Reschedule Service)</h3>
            </div>
            <button onclick="document.getElementById('web-reschedule-backdrop').remove()" class="text-navy/40 hover:text-navy text-lg font-bold">&times;</button>
          </div>
          <form onsubmit="submitWebReschedule(event, '${orderNumber}')" class="space-y-3 text-xs">
            <div>
              <label class="block font-bold text-navy dark:text-white mb-1">የትዕዛዝ ቁጥር:</label>
              <input type="text" value="${orderNumber}" readonly class="w-full bg-slate-100 dark:bg-slate-800 border border-navy/15 rounded-lg px-3 py-2 font-mono font-bold text-navy dark:text-white">
            </div>
            <div>
              <label class="block font-bold text-navy dark:text-white mb-1">በትዕዛዙ የተጠቀሙት ስልክ ቁጥር *:</label>
              <input type="tel" id="resched-phone" required placeholder="09... / 07..." class="w-full border border-navy/20 rounded-lg px-3 py-2 text-navy dark:text-white dark:bg-slate-800 focus:border-teal-dark focus:outline-none">
            </div>
            <div>
              <label class="block font-bold text-navy dark:text-white mb-1">አዲሱ የቀጠሮ ቀን (New Date) *:</label>
              <input type="date" id="resched-date" required min="${tomorrowStr}" value="${tomorrowStr}" class="w-full border border-navy/20 rounded-lg px-3 py-2 text-navy dark:text-white dark:bg-slate-800 focus:border-teal-dark focus:outline-none">
            </div>
            <div>
              <label class="block font-bold text-navy dark:text-white mb-1">የቀጠሮ ሰዓት (Time Slot) *:</label>
              <select id="resched-slot" class="w-full border border-navy/20 rounded-lg px-3 py-2 text-navy dark:text-white dark:bg-slate-800 focus:border-teal-dark focus:outline-none">
                <option value="morning">ጥዋት (2:00 - 6:00 ጠዋቱ • Morning 8am-12pm)</option>
                <option value="afternoon">ከሰዓት (7:00 - 11:00 ከሰዓቱ • Afternoon 1pm-5pm)</option>
                <option value="night">የሌሊት ፈረቃ (1:00 - 11:00 ሌሊቱ • Night Shift 7pm-5am)</option>
                <option value="weekend">ቅዳሜና እሁድ (እንደ ፍላጎትዎ • Weekend Flexible)</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-navy dark:text-white mb-1">ምክንያት (Reason):</label>
              <input type="text" id="resched-reason" placeholder="ምክንያቱን በአጭሩ ይጻፉ..." value="በደንበኛው ጥያቄ" class="w-full border border-navy/20 rounded-lg px-3 py-2 text-navy dark:text-white dark:bg-slate-800 focus:border-teal-dark focus:outline-none">
            </div>
            <div id="resched-status-msg" class="hidden my-2"></div>
            <div class="flex justify-end gap-2 pt-2 border-t border-navy/10 sticky bottom-0 bg-white dark:bg-slate-900 pb-1">
              <button type="button" onclick="document.getElementById('web-reschedule-backdrop').remove()" class="px-4 py-2 border border-navy/20 rounded-lg text-navy dark:text-white font-bold cursor-pointer">ሰርዝ</button>
              <button type="submit" id="btn-submit-resched" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-extrabold rounded-lg shadow cursor-pointer">ቀጠሮ አስተላልፍ</button>
            </div>
          </form>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    if (window.lucide) lucide.createIcons();
  }

  async function submitWebReschedule(e, orderNumber) {
    e.preventDefault();
    const btn = document.getElementById('btn-submit-resched');
    btn.disabled = true;
    btn.innerText = 'በማስተላለፍ ላይ...';

    const payload = {
      order_number: orderNumber,
      phone: document.getElementById('resched-phone').value.trim(),
      new_appointment_date: document.getElementById('resched-date').value,
      new_time_slot: document.getElementById('resched-slot').value,
      reason: document.getElementById('resched-reason').value.trim(),
    };

    try {
      const res = await fetch('/api/public/reschedule-order', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      const msgEl = document.getElementById('resched-status-msg');
      if (res.ok && data.success) {
        if (msgEl) {
          msgEl.className = 'p-3 rounded-xl bg-emerald-50 border border-emerald-300 text-emerald-800 text-xs font-bold text-center';
          msgEl.innerText = data.message || 'ቀጠሮው በተሳካ ሁኔታ ተላልፏል!';
          msgEl.classList.remove('hidden');
        }
        setTimeout(() => {
          document.getElementById('web-reschedule-backdrop')?.remove();
          // re-run track
          const trackIn = document.getElementById('track-input');
          if (trackIn) {
            trackIn.value = orderNumber;
            const trForm = document.getElementById('track-form') || trackIn.form;
            if (trForm) trForm.dispatchEvent(new Event('submit'));
            else trackOrder();
          }
        }, 1200);
      } else {
        if (msgEl) {
          msgEl.className = 'p-3 rounded-xl bg-red-50 border border-red-300 text-red-700 text-xs font-bold text-center';
          msgEl.innerText = 'ስህተት: ' + (data.message || 'ቀጠሮውን ማስተላለፍ አልተቻለም።');
          msgEl.classList.remove('hidden');
        }
      }
    } catch (err) {
      const msgEl = document.getElementById('resched-status-msg');
      if (msgEl) {
        msgEl.className = 'p-3 rounded-xl bg-red-50 border border-red-300 text-red-700 text-xs font-bold text-center';
        msgEl.innerText = 'የኔትወርክ ስህተት: ' + err.message;
        msgEl.classList.remove('hidden');
      }
    } finally {
      btn.disabled = false;
      btn.innerText = 'ቀጠሮ አስተላልፍ';
    }
  }

  // Tracked order global state
  window.currentTrackedOrderData = null;
  window.currentCustomerPortalData = null;

  // Helper: 1-Click Pre-fill for Booking
  window.bookNextCleaningWithCustomerData = function(cust) {
    cust = cust || window.currentCustomerPortalData;
    if (!cust) return;
    const nameEl = document.getElementById('book-name');
    const phoneEl = document.getElementById('book-phone');
    const subcityEl = document.getElementById('book-subcity');
    const addressEl = document.getElementById('book-address');

    if (nameEl && cust.full_name) nameEl.value = cust.full_name;
    if (phoneEl && cust.phone) phoneEl.value = cust.phone;
    if (subcityEl && cust.subcity) {
      for (let i = 0; i < subcityEl.options.length; i++) {
        const val = subcityEl.options[i].value.toLowerCase();
        const text = subcityEl.options[i].text.toLowerCase();
        const match = cust.subcity.toLowerCase();
        if (val.includes(match) || text.includes(match) || match.includes(val)) {
          subcityEl.selectedIndex = i;
          break;
        }
      }
    }
    if (addressEl && cust.address) addressEl.value = cust.address;

    const bookSec = document.getElementById('booking') || document.getElementById('book');
    if (bookSec) {
      bookSec.scrollIntoView({ behavior: 'smooth' });
      if (typeof showWizardStep === 'function') {
        showWizardStep(1);
      }
    }
  };

  // Render: Waiting for Your Comment & 5-Star Rating Screen
  function renderCommentWaitingScreen(data) {
    const ord = data.order || data;
    const summary = data.customer_summary || ord.customer_summary || {};
    const custName = summary.full_name || ord.customer_name || 'ውድ ደንበኛችን';

    return `
      <div class="bg-gradient-to-br from-amber-50 via-orange-50/40 to-amber-100/30 border-2 border-amber-400 rounded-3xl p-6 sm:p-8 shadow-xl text-center relative overflow-hidden">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-amber-500/20 text-amber-900 border border-amber-400 text-xs font-black uppercase tracking-wider mb-4 animate-pulse">
          <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
          <span>የእርስዎን አስተያየት በመጠባበቅ ላይ (Waiting for Review)</span>
        </div>

        <h3 class="text-xl sm:text-2xl font-black text-navy mb-2">አገልግሎታችን ተጠናቋል! የእርስዎን ባለ 5-ኮከብ ደረጃ ይስጡን</h3>
        <p class="text-xs sm:text-sm text-navy/70 max-w-lg mx-auto mb-6">
          ክቡር ደንበኛችን <strong>${escapeHtml(custName)}</strong>፣ የትዕዛዝ ቁጥር <strong>#${escapeHtml(ord.order_number)}</strong> ክፍያ እና የፅዳት አገልግሎት በተሳካ ሁኔታ ተጠናቋል:: አገልግሎታችንን እንዴት አገኙት? ከ 1 እስከ 5 ኮከብ በመምረጥ እና አስተያየትዎን በማጋራት ያግዙን፦
        </p>

        <!-- 5-Star Rating Buttons -->
        <div class="flex items-center justify-center gap-2 sm:gap-3 mb-5">
          <button type="button" onclick="setReviewRating(1)" class="p-1.5 hover:scale-125 transition cursor-pointer" title="1 ኮከብ"><svg id="review-star-1" class="w-8 h-8 sm:w-10 sm:h-10 text-amber-400 fill-amber-400 transition" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></button>
          <button type="button" onclick="setReviewRating(2)" class="p-1.5 hover:scale-125 transition cursor-pointer" title="2 ኮከብ"><svg id="review-star-2" class="w-8 h-8 sm:w-10 sm:h-10 text-amber-400 fill-amber-400 transition" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></button>
          <button type="button" onclick="setReviewRating(3)" class="p-1.5 hover:scale-125 transition cursor-pointer" title="3 ኮከብ"><svg id="review-star-3" class="w-8 h-8 sm:w-10 sm:h-10 text-amber-400 fill-amber-400 transition" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></button>
          <button type="button" onclick="setReviewRating(4)" class="p-1.5 hover:scale-125 transition cursor-pointer" title="4 ኮከብ"><svg id="review-star-4" class="w-8 h-8 sm:w-10 sm:h-10 text-amber-400 fill-amber-400 transition" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></button>
          <button type="button" onclick="setReviewRating(5)" class="p-1.5 hover:scale-125 transition cursor-pointer" title="5 ኮከብ"><svg id="review-star-5" class="w-8 h-8 sm:w-10 sm:h-10 text-amber-400 fill-amber-400 transition" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></button>
        </div>

        <!-- Comment Box -->
        <div class="max-w-xl mx-auto mb-5 text-left">
          <label class="block text-xs font-bold text-navy/80 mb-1.5">የእርስዎ አስተያየት (አማራጭ)፦</label>
          <textarea id="website-review-comment" rows="3" placeholder="ስለ አገልግሎቱ ጥራት እና ስለ ሰራተኞቻችን ያለዎትን አስተያየት እዚህ ይጻፉ..." class="w-full text-xs sm:text-sm p-3.5 rounded-2xl border-2 border-amber-300 focus:border-teal focus:ring-2 focus:ring-teal/20 outline-none bg-white text-navy shadow-inner"></textarea>
        </div>

        <!-- Submit Button -->
        <button type="button" id="website-review-btn" onclick="submitWebsiteReview('${escapeHtml(ord.order_number)}')" class="px-8 py-3.5 bg-gradient-to-r from-teal to-teal-dark hover:from-teal-dark hover:to-navy text-white rounded-2xl font-black text-xs sm:text-sm shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 cursor-pointer flex items-center justify-center gap-2 mx-auto">
          <i data-lucide="send" class="w-4 h-4"></i>
          <span>ደረጃውን መዝግብ እና የደንበኛ ፖርታልን ክፈት (Submit &amp; Open Portal)</span>
        </button>
        <div id="website-review-msg" class="hidden max-w-md mx-auto mt-4"></div>
      </div>`;
  }

  // Render: Normal Customer Portal (Verified Receipt + Service Summary + Loyalty + 1-Click Booking)
  function renderNormalCustomerPortal(data) {
    const ord = data.order || data;
    const summary = data.customer_summary || ord.customer_summary || {};
    const loyalty = summary.loyalty_status || { tier: 'bronze', label: 'ነሐስ ደንበኛ (Bronze Member)', discount_percent: 5, badge_class: 'bg-orange-100 text-orange-800 border-orange-300' };
    const receipt = summary.receipt || {
      payment_number: 'REC-' + ord.order_number,
      amount: ord.total || 0,
      payment_method: 'CASH',
      reference_number: 'VERIFIED-OK',
      payment_date: ord.appointment_date,
      is_verified: true
    };
    const completed = summary.completed_summary || {};
    const custName = summary.full_name || ord.customer_name || 'ውድ ደንበኛችን';
    const custPhone = summary.phone || ord.customer_phone || '';
    const custAddress = summary.address || ord.address || '';
    const custSubcity = summary.subcity || ord.subcity || '';
    window.currentCustomerPortalData = { full_name: custName, phone: custPhone, subcity: custSubcity, address: custAddress };

    return `
      <div class="bg-white rounded-3xl p-6 sm:p-8 border border-navy/10 shadow-xl space-y-6">
        <!-- Portal Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 pb-5 border-b border-navy/10">
          <div>
            <div class="flex flex-wrap items-center gap-2 mb-1.5">
              <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider ${loyalty.badge_class || 'bg-amber-100 text-amber-800 border border-amber-300'}">
                <i data-lucide="award" class="w-3.5 h-3.5"></i>
                <span>${loyalty.label}</span>
              </span>
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                <i data-lucide="check-circle" class="w-3 h-3 text-emerald-600"></i>
                <span>ክፍያው የተረጋገጠ (Verified Paid)</span>
              </span>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-navy">እንኳን ደህና መጡ፣ ${escapeHtml(custName)}!</h3>
            <p class="text-xs text-navy/60 mt-0.5">የሜሽ መደበኛ እና የተከበሩ ደንበኛ ፖርታል (Customer Portal)</p>
          </div>
          <div class="text-right">
            <span class="text-xs text-navy/50 block font-semibold">የትዕዛዝ ቁጥር</span>
            <span class="font-serif text-lg font-black text-teal-dark">#${escapeHtml(ord.order_number)}</span>
          </div>
        </div>

        <!-- Loyalty & Rewards Card -->
        <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-teal-50 via-cyan-50 to-emerald-50 border border-teal/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-teal text-white flex items-center justify-center shrink-0 shadow-md">
              <i data-lucide="gift" class="w-6 h-6"></i>
            </div>
            <div>
              <h4 class="font-black text-navy text-sm sm:text-base">የደንበኝነት የታማኝነት ማዕረግ፡ ${loyalty.label}</h4>
              <p class="text-xs text-teal-dark font-bold mt-0.5">${loyalty.perks || 'የታማኝነት ቅናሽ በሁሉም የጽዳት አገልግሎቶች ላይ'}</p>
              <p class="text-[11px] text-navy/60 mt-1">አጠቃላይ የተጠናቀቁ ትዕዛዞች፡ <strong>${summary.total_orders || 1}</strong> | የወጣ ወጪ፡ <strong>${Number(summary.total_spent || ord.total || 0).toLocaleString()} ETB</strong></p>
            </div>
          </div>
          <button type="button" onclick="bookNextCleaningWithCustomerData()" class="px-5 py-3 bg-teal hover:bg-teal-dark text-white rounded-xl font-black text-xs shadow-md transition-all flex items-center justify-center gap-2 shrink-0 cursor-pointer hover:shadow-lg">
            <i data-lucide="plus-circle" class="w-4 h-4"></i>
            <span>ቀጣይ ጽዳት በ 1-ጠቅታ ያዙ</span>
          </button>
        </div>

        <!-- 2 Columns: Digital Receipt & Completed Service Summary -->
        <div class="grid md:grid-cols-2 gap-5">
          <!-- Verified Digital Receipt -->
          <div class="p-5 rounded-2xl bg-fog/70 border border-navy/15 flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-navy/10">
                <div class="flex items-center gap-2">
                  <i data-lucide="receipt" class="w-4 h-4 text-teal"></i>
                  <h4 class="font-black text-navy text-xs uppercase tracking-wider">የተረጋገጠ ዲጂታል ደረሰኝ (Official Receipt)</h4>
                </div>
                <span class="text-[10px] font-black px-2 py-0.5 rounded bg-emerald-600 text-white">PAID</span>
              </div>
              <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-navy/5">
                  <span class="text-navy/60">የደረሰኝ ቁጥር:</span>
                  <span class="font-mono font-bold text-navy">${escapeHtml(receipt.payment_number || '')}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-navy/5">
                  <span class="text-navy/60">የተከፈለበት መንገድ:</span>
                  <span class="font-bold text-navy">${escapeHtml(receipt.payment_method || 'CASH')}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-navy/5">
                  <span class="text-navy/60">የግብይት መለያ (Ref #):</span>
                  <span class="font-mono font-bold text-teal-dark">${escapeHtml(receipt.reference_number || 'VERIFIED-OK')}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-navy/5">
                  <span class="text-navy/60">የክፍያ ቀን:</span>
                  <span class="font-bold text-navy">${escapeHtml(receipt.payment_date || ord.appointment_date || '')}</span>
                </div>
                <div class="flex justify-between pt-2">
                  <span class="font-bold text-navy text-sm">ጠቅላላ የተከፈለ:</span>
                  <span class="font-black text-emerald-700 text-base font-serif">${Number(receipt.amount || ord.total || 0).toLocaleString()} ETB</span>
                </div>
              </div>
            </div>
            <div class="mt-4 pt-3 border-t border-navy/10 flex items-center justify-between text-[11px] text-navy/50">
              <span>Mesh Cleaning Solution</span>
              <span class="text-emerald-700 font-bold">✓ በሲስተም የተረጋገጠ ክፍያ</span>
            </div>
          </div>

          <!-- Completed Service Summary -->
          <div class="p-5 rounded-2xl bg-fog/70 border border-navy/15 flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between pb-3 mb-3 border-b border-navy/10">
                <div class="flex items-center gap-2">
                  <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                  <h4 class="font-black text-navy text-xs uppercase tracking-wider">የተጠናቀቀ አገልግሎት ማጠቃለያ</h4>
                </div>
                <span class="text-[10px] font-bold text-teal-dark bg-teal-light px-2 py-0.5 rounded-full">ተጠናቋል</span>
              </div>
              <div class="space-y-2 text-xs">
                <div class="flex justify-between py-1 border-b border-navy/5">
                  <span class="text-navy/60">የአገልግሎት አይነት:</span>
                  <span class="font-bold text-navy text-right">${escapeHtml(completed.service_names || 'የፅዳት አገልግሎት')}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-navy/5">
                  <span class="text-navy/60">የተከናወነበት ቀን:</span>
                  <span class="font-bold text-navy">${escapeHtml(completed.eth_appointment_date || ord.eth_appointment_date || ord.appointment_date)}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-navy/5">
                  <span class="text-navy/60">የቀጠሮ ክፍለ ጊዜ:</span>
                  <span class="font-bold text-navy">${escapeHtml(completed.time_slot || ord.appointment_time_slot || 'ቀን')}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-navy/5">
                  <span class="text-navy/60">ያከናወነው ቡድን:</span>
                  <span class="font-bold text-teal-dark">${escapeHtml(completed.team_name || ord.team_assigned || 'የሜሽ ቡድን')}</span>
                </div>
                <div class="flex justify-between py-1">
                  <span class="text-navy/60">አድራሻ:</span>
                  <span class="font-bold text-navy text-right">${escapeHtml((custSubcity ? custSubcity + ', ' : '') + custAddress)}</span>
                </div>
              </div>
            </div>
            <div class="mt-4 pt-3 border-t border-navy/10 text-right">
              <span class="text-[11px] text-teal-dark font-extrabold">Your Environment, Our Responsibility.</span>
            </div>
          </div>
        </div>

        <!-- Prominent 1-Click Book Next Cleaning Banner -->
        <div class="p-6 rounded-2xl bg-navy text-white flex flex-col sm:flex-row items-center justify-between gap-4 shadow-lg">
          <div>
            <h4 class="text-base sm:text-lg font-black text-white flex items-center gap-2">
              <i data-lucide="sparkles" class="w-5 h-5 text-amber-400"></i>
              <span>ቀጣይ የጽዳት አገልግሎትዎን አሁኑኑ በ 1-ጠቅታ ይያዙ!</span>
            </h4>
            <p class="text-xs text-white/75 mt-1">
              ስምዎ፣ ስልክዎ እና አድራሻዎ እንዲሁም የ ${loyalty.discount_percent}% የታማኝነት ቅናሽዎ በቅጹ ውስጥ Sync ሆነው ይጠብቁዎታል::
            </p>
          </div>
          <button type="button" onclick="bookNextCleaningWithCustomerData()" class="w-full sm:w-auto px-6 py-3.5 bg-gradient-to-r from-teal to-emerald-500 hover:from-teal-dark hover:to-emerald-600 text-white rounded-xl font-black text-xs sm:text-sm shadow-md transition-all flex items-center justify-center gap-2 shrink-0 cursor-pointer hover:scale-105">
            <i data-lucide="calendar-plus" class="w-4 h-4"></i>
            <span>ቀጣይ ጽዳት ያዙ (Book Next Cleaning)</span>
          </button>
        </div>
      </div>`;
  }

  async function trackOrder(e){
    if (e && typeof e.preventDefault === 'function') e.preventDefault();
    const q = document.getElementById('track-input').value.trim();
    const box = document.getElementById('track-result');
    if (!q) {
      box.classList.remove('hidden');
      box.innerHTML = '<div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">እባክዎ ትክክለኛ የትዕዛዝ መለያ ቁጥር (ምሳሌ፡ MEASH-000001) ያስገቡ።</div>';
      return;
    }
    box.classList.remove('hidden');
    box.innerHTML = '<div class="p-6 text-center text-navy/60"><div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-teal mr-2"></div> በመፈለግ ላይ...</div>';
    try {
      const res = await fetch(`/api/public/track-order?tracking_id=${encodeURIComponent(q)}`, {
        headers: { 'Accept': 'application/json' }
      });
      let data = {};
      try {
        data = await res.json();
      } catch (jsonErr) {
        data = {};
      }

      if (res.ok && (data.found || data.success) && (data.order || data)) {
        window.currentTrackedOrderData = data;
        const ord = data.order || data;
        const customerStage = data.customer_stage || ord.customer_stage || (ord.status === 'completed' && ord.payment_status === 'paid' ? (data.has_review ? 'normal_customer' : 'comment_waiting') : 'in_progress');

        // 1. Comment Waiting Screen (Paid & Completed, no review yet)
        if (customerStage === 'comment_waiting') {
          box.innerHTML = renderCommentWaitingScreen(data);
          if (window.lucide) lucide.createIcons();
          return;
        }

        // 2. Normal Customer Portal Screen (Paid & Completed, review submitted)
        if (customerStage === 'normal_customer') {
          box.innerHTML = renderNormalCustomerPortal(data);
          if (window.lucide) lucide.createIcons();
          return;
        }

        // 3. In-Progress Cleaning & Schedule Status
        const isEnRoute = ord.status === 'on_the_way';
        const isCleaning = ord.status === 'cleaning' || ord.status === 'in_progress';
        const isCompleted = ord.status === 'completed';

        let subBadge = '';
        if (ord.subscription_plan && ord.subscription_plan !== 'one_time') {
          const pNames = { weekly: 'የሳምንት (Weekly - 15% OFF)', biweekly: 'የ2 ሳምንት (Bi-weekly - 10% OFF)', monthly: 'የወር (Monthly - 5% OFF)' };
          subBadge = `<div class="mt-2 text-xs font-bold text-teal-dark bg-teal-light px-3 py-1 rounded-lg inline-flex items-center gap-1.5">
            <i data-lucide="repeat" class="w-3.5 h-3.5 text-teal"></i>
            <span>${pNames[ord.subscription_plan] || ord.subscription_plan}</span>
          </div>`;
        }

        let addressSummaryHtml = `
          <div class="mt-4 pt-4 border-t border-navy/10 space-y-3">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-700 text-xs flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-teal/15 text-teal-dark flex items-center justify-center shrink-0">
                <i data-lucide="map-pin" class="w-4 h-4"></i>
              </div>
              <div class="flex-1">
                <p class="font-bold text-navy text-xs">የአገልግሎት አድራሻ (Service Address):</p>
                <p class="text-[11px] text-slate-600 font-semibold mt-0.5">${escapeHtml(ord.address || 'አዲስ አበባ')} ${ord.subcity ? '(' + escapeHtml(ord.subcity) + ')' : ''}</p>
              </div>
            </div>
            ${isCompleted ? `
              <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-700 flex items-center justify-center shrink-0">
                  <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
                <div>
                  <p class="font-extrabold text-emerald-950 text-xs">ስራው በተሳካ ሁኔታ ተጠናቋል! (Service Completed)</p>
                  <p class="text-[10px] text-emerald-800 mt-0.5">የሜሽ ጥልቅ ጽዳት አገልግሎትን ስለመረጡ እናመሰግናለን!</p>
                </div>
              </div>
            ` : (isEnRoute ? `
              <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-700 flex items-center justify-center shrink-0">
                  <i data-lucide="navigation" class="w-4 h-4"></i>
                </div>
                <div>
                  <p class="font-extrabold text-amber-950 text-xs">የፅዳት ቡድኑ ወደ እርሶ በመጓዝ ላይ ይገኛል (Team En Route)</p>
                  <p class="text-[10px] text-amber-800 mt-0.5">ቡድኑ በተያዘው ሰዓት ደርሶ አገልግሎቱን ይሰጣል። ጥያቄ ካለዎት በ {{ $phone1 }} ይደውሉ።</p>
                </div>
              </div>
            ` : (isCleaning ? `
              <div class="p-3.5 rounded-xl bg-teal-50 border border-teal-200 text-teal-900 text-xs flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-teal/20 text-teal-dark flex items-center justify-center shrink-0">
                  <i data-lucide="sparkles" class="w-4 h-4"></i>
                </div>
                <div>
                  <p class="font-extrabold text-teal-950 text-xs">የፅዳት ስራው በመከናወን ላይ ይገኛል (Service In Progress)</p>
                  <p class="text-[10px] text-teal-800 mt-0.5">ባለሙያዎቻችን ጥልቅ የጽዳት ስራዎችን እያከናወኑ ይገኛሉ።</p>
                </div>
              </div>
            ` : `
              <div class="p-3.5 rounded-xl bg-cyan-50 border border-cyan-200 text-cyan-900 text-xs flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-cyan-500/20 text-cyan-700 flex items-center justify-center shrink-0">
                  <i data-lucide="calendar-check" class="w-4 h-4"></i>
                </div>
                <div>
                  <p class="font-extrabold text-cyan-950 text-xs">ትዕዛዝዎ ተቀባይነት አግኝቷል (Booking Confirmed)</p>
                  <p class="text-[10px] text-cyan-800 mt-0.5">የተመደበው ቡድን በተያዘው የቀጠሮ ቀንና ሰዓት ይደርሳል።</p>
                </div>
              </div>
            `))}
          </div>`;

        let teamInfoHtml = '';
        if (ord.team_location) {
          teamInfoHtml = `
            <div class="mt-3 p-3.5 rounded-xl bg-slate-900 text-white text-xs flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal/20 text-teal flex items-center justify-center">
                  <i data-lucide="truck" class="w-5 h-5"></i>
                </div>
                <div>
                  <p class="font-extrabold text-white">${escapeHtml(ord.team_location.team_name || 'የሜሽ ቡድን')}</p>
                  <p class="text-[11px] text-teal-light font-semibold">${ord.team_location.vehicle_plate ? 'ታርጋ: ' + escapeHtml(ord.team_location.vehicle_plate) : 'የሜሽ ጽዳት መኪና'}</p>
                </div>
              </div>
              ${ord.team_location.phone ? `<a href="tel:${escapeHtml(ord.team_location.phone)}" class="px-3 py-1.5 rounded-lg bg-teal hover:bg-teal-dark text-white font-bold text-xs flex items-center gap-1.5"><i data-lucide="phone" class="w-3.5 h-3.5"></i> ደውል</a>` : ''}
            </div>`;
        }

        let postponeAlertHtml = '';
        if (ord.is_postponed) {
          postponeAlertHtml = `
            <div class="mb-3 p-3.5 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-700 flex items-center justify-center shrink-0">
                <i data-lucide="clock" class="w-4 h-4"></i>
              </div>
              <div>
                <p class="font-black text-amber-950 text-xs">ማሳሰቢያ፡ የዚህ ትዕዛዝ ቀጠሮ ተላልፏል (Rescheduled)</p>
                <p class="text-[11px] text-amber-800 mt-0.5">አዲሱ የቀጠሮ ቀን፡ <strong>${escapeHtml(ord.eth_appointment_date || ord.appointment_date || '—')}</strong> (${ord.appointment_time_slot === 'morning' ? 'ጠዋት' : 'ከሰዓት'})</p>
              </div>
            </div>`;
        }

        let rescheduleBtnHtml = '';
        if (!isCompleted && ord.has_secure_token) {
          rescheduleBtnHtml = `
            <div class="mt-4 pt-3 border-t border-navy/10 flex flex-wrap items-center justify-between gap-2">
              <span class="text-[11px] text-navy/60 font-semibold">ቀጠሮዎን መቀየር ወይም ማራዘም ይፈልጋሉ?</span>
              <button type="button" onclick="openWebRescheduleModal('${escapeHtml(ord.order_number)}')" class="px-3.5 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs transition flex items-center gap-1.5 cursor-pointer">
                <i data-lucide="calendar-clock" class="w-3.5 h-3.5"></i>
                <span>ቀጠሮ ቀን አስተላልፍ</span>
              </button>
            </div>`;
        }

        const totalFormatted = ord.total != null ? Number(ord.total).toLocaleString() + ' ETB' : 'በቀጠሮው ወቅት የሚከፈል';
        const paymentLabel = ord.payment_status === 'paid' ? 'ተከፍሏል' : (ord.payment_status === 'unpaid' ? 'ያልተከፈለ' : 'ቅድመ ክፍያ አይጠየቅም');

        box.innerHTML = `
          <div class="bg-white rounded-2xl p-6 border border-navy/10 shadow-lg">
            ${postponeAlertHtml}
            <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
              <div>
                <span class="text-xs text-navy/40 font-bold uppercase block">የትዕዛዝ ቁጥር</span>
                <span class="font-serif text-lg font-bold text-navy">${escapeHtml(ord.order_number || '')}</span>
              </div>
              <span class="text-xs font-extrabold px-3 py-1.5 rounded-full ${isCompleted ? 'bg-emerald-100 text-emerald-800' : (isEnRoute ? 'bg-amber-100 text-amber-800 animate-pulse' : 'bg-teal-light text-teal-dark')}">
                ${isEnRoute ? 'ቡድኑ በመጓዝ ላይ ይገኛል (On The Way)' : (isCleaning ? 'ፅዳት በመከናወን ላይ' : (isCompleted ? 'ተጠናቋል' : 'የተመዘገበ (' + (ord.status || 'Active') + ')'))}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs py-3 border-y border-navy/10">
              <div>
                <span class="text-navy/50 block">ደንበኛ:</span>
                <span class="font-bold text-navy">${escapeHtml(ord.customer_name || 'ውድ ደንበኛችን')} (${escapeHtml(ord.subcity || '')})</span>
              </div>
              <div>
                <span class="text-navy/50 block mb-0.5">የቀጠሮ ቀንና ሰዓት:</span>
                <div class="space-y-0.5">
                  <p class="font-extrabold text-teal-dark text-xs">${escapeHtml(ord.eth_appointment_date || (ord.appointment_date && window.EC ? EC.formatEth(EC.toEth(ord.appointment_date).year, EC.toEth(ord.appointment_date).month, EC.toEth(ord.appointment_date).day) : (ord.appointment_date || '—')))}</p>
                  <p class="text-[10px] text-navy/55 font-semibold">GC: ${escapeHtml(ord.appointment_date || '—')}</p>
                  <p class="text-[11px] font-bold text-navy">
                    ${ord.appointment_time_slot === 'morning' ? 'ጥዋት (2:00–6:00 ጠዋቱ ETH • 8am–12pm GC)' : (ord.appointment_time_slot === 'afternoon' ? 'ከሰዓት (7:00–11:00 ከሰዓቱ ETH • 1pm–5pm GC)' : escapeHtml(ord.appointment_time_slot || ''))}
                  </p>
                </div>
              </div>
              <div>
                <span class="text-navy/50 block">ጠቅላላ ክፍያ:</span>
                <span class="font-bold text-teal-dark text-sm">${totalFormatted}</span>
              </div>
              <div>
                <span class="text-navy/50 block">የክፍያ ሁኔታ:</span>
                <span class="font-bold ${ord.payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600'}">${paymentLabel}</span>
              </div>
            </div>

            ${subBadge}
            ${teamInfoHtml}
            ${addressSummaryHtml}
            ${rescheduleBtnHtml}
          </div>`;

        if (window.lucide) lucide.createIcons();

      } else {
        const notFoundMsg = res.status === 429
          ? 'እባክዎ ጥቂት ሰከንዶች ቆይተው እንደገና ይሞክሩ (Too many requests)።'
          : (data.message || 'ይቅርታ፣ በዚህ ቁጥር የተያዘ ትዕዛዝ አልተገኘም። እባክዎ በትክክል መጻፉን ያረጋግጡ።');
        box.innerHTML = `<div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
          ${escapeHtml(notFoundMsg)}
        </div>`;
      }
    } catch (err) {
      console.error('Track order error:', err);
      box.innerHTML = '<div class="p-4 rounded-xl bg-red-50 text-red-700 text-xs">የኔትወርክ ግንኙነት ስህተት አጋጥሟል። እባክዎ በ {{ $phone1 }} በቀጥታ ይደውሉ።</div>';
    }
  }

  // ---- 5-Star Customer Rating & Review Submission ----
  let selectedReviewRating = 5;
  function setReviewRating(star) {
    selectedReviewRating = star;
    for (let i = 1; i <= 5; i++) {
      const starEl = document.getElementById(`review-star-${i}`);
      if (starEl) {
        if (i <= star) {
          starEl.classList.remove('text-slate-300', 'fill-none');
          starEl.classList.add('text-amber-400', 'fill-amber-400');
        } else {
          starEl.classList.remove('text-amber-400', 'fill-amber-400');
          starEl.classList.add('text-slate-300', 'fill-none');
        }
      }
    }
  }

  async function submitWebsiteReview(orderNumber) {
    const commentEl = document.getElementById('website-review-comment');
    const msgEl = document.getElementById('website-review-msg');
    const btnEl = document.getElementById('website-review-btn');
    const comment = commentEl ? commentEl.value.trim() : '';

    if (btnEl) {
      btnEl.disabled = true;
      btnEl.innerHTML = '<span class="inline-block animate-spin mr-1">⌛</span> በማስገባት ላይ...';
    }

    try {
      const res = await fetch('/api/public/reviews', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          order_number: orderNumber,
          rating: selectedReviewRating,
          comment: comment,
          source: 'website',
        })
      });
      const data = await res.json();
      if (res.ok && data.success) {
        // Immediately transition UI to Normal Customer Portal!
        if (window.currentTrackedOrderData) {
          window.currentTrackedOrderData.customer_stage = 'normal_customer';
          window.currentTrackedOrderData.has_review = true;
          const box = document.getElementById('track-result');
          if (box) {
            box.innerHTML = renderNormalCustomerPortal(window.currentTrackedOrderData);
            if (window.lucide) lucide.createIcons();
            box.scrollIntoView({ behavior: 'smooth' });
          }
        } else {
          trackOrder();
        }
      } else {
        if (msgEl) {
          msgEl.className = 'mt-3 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-bold text-center';
          msgEl.innerText = data.message || 'አስተያየቱን ማስገባት አልተቻለም። እባክዎ እንደገና ይሞክሩ።';
          msgEl.classList.remove('hidden');
        }
        if (btnEl) {
          btnEl.disabled = false;
          btnEl.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i><span>ደረጃውን መዝግብ (Submit Rating)</span>';
          if (window.lucide) lucide.createIcons();
        }
      }
    } catch (e) {
      if (msgEl) {
        msgEl.className = 'mt-3 p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-bold text-center';
        msgEl.innerText = 'የኔትወርክ ግንኙነት ስህተት አጋጥሟል። እባክዎ እንደገና ይሞክሩ።';
        msgEl.classList.remove('hidden');
      }
      if (btnEl) {
        btnEl.disabled = false;
        btnEl.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5"></i><span>ደረጃውን መዝግብ (Submit Rating)</span>';
        if (window.lucide) lucide.createIcons();
      }
    }
  }

  // Auto-trigger review if order or review_order query parameter is present in URL
  document.addEventListener('DOMContentLoaded', () => {
    try {
      const urlParams = new URLSearchParams(window.location.search);
      const reviewOrderParam = (urlParams.get('order') || urlParams.get('review_order') || '').trim();
      if (reviewOrderParam && reviewOrderParam !== 'undefined' && reviewOrderParam !== 'null') {
        const trackInput = document.getElementById('track-input');
        if (trackInput) {
          trackInput.value = reviewOrderParam;
          setTimeout(() => {
            trackOrder();
            const trackSection = document.getElementById('track-order') || document.getElementById('track') || document.getElementById('track-result');
            if (trackSection) {
              trackSection.scrollIntoView({ behavior: 'smooth' });
            }
          }, 400);
        }
      }
    } catch (e) {
      // Safe fallback
    }
  });

  // ---- Staff / Admin Authentication Modal ----
  function openStaffModal() {
    const modal = document.getElementById('staff-auth-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    const err = document.getElementById('staff-login-error');
    if (err) err.classList.add('hidden');
    const pwd = document.getElementById('staff-password-input');
    if (pwd) {
      pwd.value = '';
      pwd.classList.remove('border-red-500');
      setTimeout(() => pwd.focus(), 150);
    }
    if (window.lucide) lucide.createIcons();
  }

  function closeStaffModal() {
    const modal = document.getElementById('staff-auth-modal');
    if (modal) modal.classList.add('hidden');
  }

  function toggleStaffPasswordVisibility() {
    const pwd = document.getElementById('staff-password-input');
    const eye = document.getElementById('staff-pwd-eye');
    if (!pwd || !eye) return;
    if (pwd.type === 'password') {
      pwd.type = 'text';
      eye.setAttribute('data-lucide', 'eye-off');
    } else {
      pwd.type = 'password';
      eye.setAttribute('data-lucide', 'eye');
    }
    if (window.lucide) lucide.createIcons();
  }

  async function handleStaffLogin(e) {
    e.preventDefault();
    const loginInput = document.getElementById('staff-login-input');
    const pwdInput = document.getElementById('staff-password-input');
    const errBox = document.getElementById('staff-login-error');
    const errText = document.getElementById('staff-login-error-text');
    const btn = document.getElementById('staff-login-btn');
    const btnText = document.getElementById('staff-login-btn-text');

    const loginVal = (loginInput?.value || '').trim();
    const pwdVal = (pwdInput?.value || '').trim();

    if (!loginVal) {
      errBox.classList.remove('hidden');
      errText.innerText = 'እባክዎ ኢሜይል ወይም ስልክ ቁጥር ያስገቡ!';
      loginInput?.focus();
      return;
    }

    if (!pwdVal) {
      errBox.classList.remove('hidden');
      errText.innerText = 'እባክዎ የይለፍ ቃልዎን ያስገቡ!';
      pwdInput.focus();
      return;
    }

    errBox.classList.add('hidden');
    btn.disabled = true;
    btnText.innerText = 'በማረጋገጥ ላይ...';

    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          login: loginVal,
          password: pwdVal,
        })
      });

      let data = null;
      try {
        data = await res.json();
      } catch (jsonErr) {
        data = null;
      }

      if (res.ok && data && data.token) {
        localStorage.setItem('meash_token', data.token);
        localStorage.setItem('meash_user', JSON.stringify(data.user));
        btnText.innerText = 'ትክክል ነው! ወደ ሲስተሙ በማስገባት ላይ...';
        btn.classList.remove('bg-navy', 'hover:bg-teal-dark');
        btn.classList.add('bg-teal', 'text-white');
        setTimeout(() => {
          window.location.href = '/app';
        }, 300);
      } else {
        errBox.classList.remove('hidden');
        let errorMsg = 'የተሳሳተ የይለፍ ቃል ነው፤ እባክዎ በትክክል ያስገቡ!';
        if (res.status === 429) {
          errorMsg = 'በርካታ ሙከራዎች ተደርገዋል፤ እባክዎ ጥቂት ደቂቃ ቆይተው እንደገና ይሞክሩ።';
        } else if (res.status >= 500) {
          errorMsg = 'ሰርቨሩ በመነሳት ላይ ነው (Render Cold Start)፤ እባክዎ ከ20-30 ሰከንድ በኋላ እንደገና ይሞክሩ።';
        } else if (data && data.errors && typeof data.errors === 'object') {
          const rawErr = Object.values(data.errors).flat().join(' ');
          errorMsg = rawErr.includes('credentials') ? 'የተሳሳተ የይለፍ ቃል ወይም ተጠቃሚ አልተገኘም!' : rawErr;
        } else if (data && data.message) {
          errorMsg = data.message.includes('credentials') ? 'የተሳሳተ የይለፍ ቃል ወይም ተጠቃሚ አልተገኘም!' : data.message;
        }
        errText.innerText = errorMsg;
        pwdInput.classList.add('border-red-500');
        btn.disabled = false;
        btnText.innerText = 'ግባ (Login)';
        pwdInput.focus();
      }
    } catch (err) {
      errBox.classList.remove('hidden');
      errText.innerText = 'የሰርቨር ግንኙነት ችግር ተፈጥሯል፤ ሰርቨሩ ከእንቅልፉ በመነሳት ላይ ሊሆን ስለሚችል እባክዎ ጥቂት ሰከንድ ጠብቀው እንደገና ይሞክሩ።';
      btn.disabled = false;
      btnText.innerText = 'ግባ (Login)';
    }
  }

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeStaffModal();
      closeMobileNav();
    }
  });

  // ---- Theme (Dark/Light Mode) Engine ----
  function initTheme() {
    let saved = localStorage.getItem('meash_theme');
    // STRICT DEFAULT: Always dark mode for website unless user explicitly chose light
    if (saved !== 'light') {
      saved = 'dark';
      localStorage.setItem('meash_theme', 'dark');
    }
    applyTheme(saved);
  }

  function applyTheme(theme) {
    const icon = document.getElementById('theme-icon');
    const btn = document.getElementById('theme-toggle-btn');
    if (theme === 'dark') {
      document.documentElement.classList.add('dark');
      document.documentElement.classList.remove('light');
      if (icon) {
        icon.setAttribute('data-lucide', 'sun');
      }
      if (btn) {
        btn.classList.add('text-amber-400', 'border-slate-700');
        btn.classList.remove('text-navy', 'border-navy/15');
      }
    } else {
      document.documentElement.classList.remove('dark');
      document.documentElement.classList.add('light');
      if (icon) {
        icon.setAttribute('data-lucide', 'moon');
      }
      if (btn) {
        btn.classList.add('text-navy', 'border-navy/15');
        btn.classList.remove('text-amber-400', 'border-slate-700');
      }
    }
    localStorage.setItem('meash_theme', theme);
    if (window.lucide) lucide.createIcons();
  }

  function toggleTheme() {
    const isDark = document.documentElement.classList.contains('dark');
    applyTheme(isDark ? 'light' : 'dark');
  }

  document.addEventListener('DOMContentLoaded', () => {
    // 1. Keep page locked at top header on initial load
    if (!window.location.hash || window.location.hash === '#top') {
      window.scrollTo(0, 0);
      document.documentElement.scrollTop = 0;
      document.body.scrollTop = 0;
    }

    initTheme();
    if (window.lucide) lucide.createIcons();
    loadServicePrices();
    loadPublicReviews();
    initEthDatePicker();

    // 2. Enable smooth scrolling for user clicks only AFTER initial layout settles
    setTimeout(() => {
      document.documentElement.classList.add('scroll-smooth');
    }, 250);
  });

  // Ensure top header positioning on mobile back/forward cached restoration (BFCache)
  window.addEventListener('pageshow', () => {
    if (!window.location.hash || window.location.hash === '#top') {
      window.scrollTo(0, 0);
      document.documentElement.scrollTop = 0;
      document.body.scrollTop = 0;
    }
  });
</script>


<script>
  // MEASH MODERN — hero 3D coverflow slider + gallery lightbox (vanilla JS only).
  // All content is server-rendered above; this script only positions slides,
  // handles navigation/autoplay/swipe, and opens the gallery lightbox.
  (function () {
    'use strict';
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- 3D coverflow slider ---------- */
    var stage = document.getElementById('ms-stage');
    if (stage) {
      var slides = Array.prototype.slice.call(stage.querySelectorAll('.ms-slide'));
      var dotsBox = document.getElementById('ms-dots');
      var dots = [], active = 0, pos = 0, timer = null;
      var dragging = false, dragged = false, dragX = 0, dragStartX = 0, dragPos = 0, dragStep = 170, lastX = 0, lastT = 0;

      slides.forEach(function (fig, i) {
        var d = document.createElement('button');
        d.type = 'button'; d.className = 'ms-dot'; d.setAttribute('aria-label', 'Photo ' + (i + 1));
        d.addEventListener('click', function () { goTo(i); restart(); });
        dotsBox.appendChild(d); dots.push(d);
        fig.addEventListener('click', function () { if (dragged) { dragged = false; return; } goTo(i); restart(); });
      });

      function layout() {
        slides.forEach(function (s, i) {
          var off = i - pos, ao = Math.abs(off);
          var x = off * 56, z = -ao * 190, r = Math.max(-34, Math.min(34, -off * 34));
          s.style.transform = 'translate(-50%,-50%) translateX(' + x + '%) translateZ(' + z + 'px) rotateY(' + r + 'deg)';
          s.style.zIndex = String(20 - Math.floor(ao));
          s.style.opacity = ao > 2.5 ? '0' : (ao <= 1 ? '1' : String(1 - (ao - 1) * 0.55));
          s.style.filter = ao === 0 ? 'none' : 'brightness(' + (1 - Math.min(ao, 1) * 0.28).toFixed(2) + ') saturate(' + (1 - Math.min(ao, 1) * 0.15).toFixed(2) + ')';
          s.style.pointerEvents = ao > 2.5 ? 'none' : 'auto';
          var fc = s.querySelector('figcaption');
          if (fc) fc.style.opacity = ao >= 2 ? '0' : (ao <= 1 ? '1' : String(2 - ao));
        });
        dots.forEach(function (d, i) { d.classList.toggle('on', i === Math.round(pos)); });
      }
      function goTo(i) { active = (i + slides.length) % slides.length; pos = active; layout(); }
      function next() { goTo(active + 1); }
      function prev() { goTo(active - 1); }
      function restart() {
        if (timer) clearInterval(timer);
        if (!reduceMotion) timer = setInterval(next, 4200);
      }
      document.getElementById('ms-next-btn').addEventListener('click', function () { next(); restart(); });
      document.getElementById('ms-prev-btn').addEventListener('click', function () { prev(); restart(); });
      stage.addEventListener('mouseenter', function () { if (timer) clearInterval(timer); });
      stage.addEventListener('mouseleave', function () { if (!dragging) restart(); });
      stage.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowRight') { next(); restart(); }
        if (e.key === 'ArrowLeft') { prev(); restart(); }
      });
      /* Hand drag: the slide follows your finger / mouse, then snaps smoothly
         to the nearest photo. Touch and mouse both work; arrows, dots and
         keyboard still work as before. */
      function dragStart(e) {
        if (e.target && e.target.closest && e.target.closest('button')) return;
        if (e.pointerType === 'mouse' && e.button !== 0) return;
        dragging = true; dragged = false;
        dragX = e.clientX; dragStartX = e.clientX; dragPos = pos;
        lastX = e.clientX; lastT = Date.now();
        dragStep = (slides[0] && slides[0].offsetWidth > 0) ? slides[0].offsetWidth * 0.56 : 170;
        if (timer) clearInterval(timer);
        stage.classList.add('ms-dragging');
        try { stage.setPointerCapture(e.pointerId); } catch (err) {}
        if (e.pointerType === 'mouse') e.preventDefault();
      }
      function dragMove(e) {
        if (!dragging) return;
        var dx = e.clientX - dragX;
        pos = dragPos - dx / dragStep;
        var max = slides.length - 1;
        if (pos < 0) pos = pos * 0.25;
        else if (pos > max) pos = max + (pos - max) * 0.25;
        if (Math.abs(e.clientX - dragStartX) > 8) dragged = true;
        lastX = e.clientX; lastT = Date.now();
        layout();
      }
      function dragEnd(e) {
        if (!dragging) return;
        dragging = false;
        stage.classList.remove('ms-dragging');
        var now = Date.now(), dt = now - lastT;
        var vel = dt > 0 ? (e.clientX - lastX) / dt : 0;
        var target = Math.round(pos);
        if (Math.abs(vel) > 0.45 && Math.abs(e.clientX - dragStartX) > 25 && target === active) {
          target = active + (vel < 0 ? 1 : -1);
        }
        target = Math.max(0, Math.min(slides.length - 1, target));
        active = target; pos = target;
        layout(); restart();
      }
      stage.addEventListener('pointerdown', dragStart);
      stage.addEventListener('pointermove', dragMove);
      stage.addEventListener('pointerup', dragEnd);
      stage.addEventListener('pointercancel', dragEnd);
      layout(); restart();
    }

    /* ---------- Gallery: 3D tilt + lightbox ---------- */
    var gal = document.getElementById('ms-gal-grid');
    if (gal) {
      var cards = Array.prototype.slice.call(gal.querySelectorAll('.ms-gcard'));
      if (!reduceMotion && window.matchMedia('(pointer: fine)').matches) {
        cards.forEach(function (card) {
          card.addEventListener('mousemove', function (e) {
            var r = card.getBoundingClientRect();
            var px = (e.clientX - r.left) / r.width - .5;
            var py = (e.clientY - r.top) / r.height - .5;
            card.style.transform = 'perspective(900px) rotateY(' + (px * 9) + 'deg) rotateX(' + (-py * 9) + 'deg)';
          });
          card.addEventListener('mouseleave', function () { card.style.transform = ''; });
        });
      }
      var lb = document.getElementById('ms-lightbox');
      var lbImg = document.getElementById('ms-lb-img');
      var lbCapAm = document.getElementById('ms-lb-cap-am');
      var lbCapEn = document.getElementById('ms-lb-cap-en');
      var lbPos = 0;
      function showLb() {
        var c = cards[lbPos];
        lbImg.src = c.getAttribute('data-ms-full');
        lbImg.alt = c.getAttribute('data-ms-cap-en');
        lbCapAm.textContent = c.getAttribute('data-ms-cap-am');
        lbCapEn.textContent = c.getAttribute('data-ms-cap-en');
      }
      function openLb(i) { lbPos = i; showLb(); lb.classList.add('open'); document.body.style.overflow = 'hidden'; }
      function closeLb() { lb.classList.remove('open'); document.body.style.overflow = ''; }
      cards.forEach(function (c, i) { c.addEventListener('click', function () { openLb(i); }); });
      document.getElementById('ms-lb-close').addEventListener('click', closeLb);
      document.getElementById('ms-lb-next').addEventListener('click', function () { lbPos = (lbPos + 1) % cards.length; showLb(); });
      document.getElementById('ms-lb-prev').addEventListener('click', function () { lbPos = (lbPos - 1 + cards.length) % cards.length; showLb(); });
      lb.addEventListener('click', function (e) { if (e.target === lb) closeLb(); });
      document.addEventListener('keydown', function (e) {
        if (!lb.classList.contains('open')) return;
        if (e.key === 'Escape') closeLb();
        if (e.key === 'ArrowRight') { lbPos = (lbPos + 1) % cards.length; showLb(); }
        if (e.key === 'ArrowLeft') { lbPos = (lbPos - 1 + cards.length) % cards.length; showLb(); }
      });
    }
  })();
</script>

<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/sw.js').then((reg) => {
        console.log('Meash Website ServiceWorker registered:', reg.scope);
      }).catch((err) => {
        console.warn('Meash Website ServiceWorker failed:', err);
      });
    });
  }
</script>
<!-- Floating Quick Chat Buttons (WhatsApp & Telegram) -->
<div class="fixed bottom-6 right-6 z-40 flex flex-col items-end gap-2.5 pointer-events-auto">
  <a href="https://wa.me/251970075509?text=Hello%20Meash%20Cleaning%20Solution,%20I%20would%20like%20to%20request%20a%20quote" 
     target="_blank" 
     rel="noopener noreferrer"
     class="flex items-center gap-2 bg-[#25D366] hover:bg-[#20ba5a] text-white px-3.5 py-2.5 rounded-full shadow-lg hover:shadow-xl transition-all duration-200 text-xs font-bold"
     title="Chat on WhatsApp">
    <i data-lucide="message-circle" class="w-5 h-5 text-white"></i>
    <span class="hidden sm:inline">WhatsApp</span>
  </a>
  <a href="https://t.me/meash_cleaning_solution_bot" 
     target="_blank" 
     rel="noopener noreferrer"
     class="flex items-center gap-2 bg-[#0088cc] hover:bg-[#0077b5] text-white px-3.5 py-2.5 rounded-full shadow-lg hover:shadow-xl transition-all duration-200 text-xs font-bold"
     title="Telegram Bot">
    <i data-lucide="send" class="w-5 h-5 text-white"></i>
    <span class="hidden sm:inline">Telegram Bot</span>
  </a>
</div>

</body>
</html>
