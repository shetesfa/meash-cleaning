<!DOCTYPE html>
<html lang="am" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<title>Meash Cleaning Solution | ሜሽ ክሊኒንግ ሶሉሽን</title>
<meta name="description" content="Meash Cleaning Solution — professional sofa, carpet, mattress & glass steam cleaning in Addis Ababa. Call 0900103183">
<meta name="theme-color" content="#0B1220">
<link rel="manifest" href="/manifest.json">
<link rel="apple-touch-icon" href="/assets/icon-192.png">
<link rel="icon" type="image/jpeg" href="/logo.jpg">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Meash">
<meta property="og:title" content="Meash Cleaning Solution">
<meta property="og:description" content="Professional home & office cleaning in Addis Ababa.">
<meta property="og:image" content="/logo.jpg">
<meta property="og:type" content="website">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Ethiopic:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  // Immediate dark mode theme initialization to avoid flash
  if (localStorage.getItem('meash_theme') === 'dark' || (!localStorage.getItem('meash_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
  }
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        fontFamily: {
          serif: ['Fraunces', 'Noto Sans Ethiopic', 'serif'],
          sans: ['Inter', 'Noto Sans Ethiopic', 'sans-serif'],
        },
        colors: {
          navy:  { DEFAULT: '#0B1220', 800: '#111B2E', 700: '#1B2A45' },
          teal:  { DEFAULT: '#0E7C7B', dark: '#0A5F5E', light: '#E3F3F2' },
          gold:  { DEFAULT: '#B8912F', light: '#F6EFDD' },
          fog:   '#F6F7F9',
        },
      }
    }
  }
</script>
<style>
  body { font-family: 'Inter', 'Noto Sans Ethiopic', sans-serif; background:#FFFFFF; color:#0B1220; transition: background-color 0.3s ease, color 0.3s ease; }
  .font-serif { font-family: 'Fraunces', 'Noto Sans Ethiopic', serif; }
  ::-webkit-scrollbar { width: 10px; } ::-webkit-scrollbar-thumb { background:#D7DCE2; border-radius:8px; }
  [data-lang-block] { display: none; }
  html[data-lang="am"] [data-lang-block="am"] { display: block; }
  html[data-lang="am"] span[data-lang-block="am"] { display: inline; }
  html[data-lang="en"] [data-lang-block="en"] { display: block; }
  html[data-lang="en"] span[data-lang-block="en"] { display: inline; }
  html[data-lang="both"] [data-lang-block] { display: block; }
  html[data-lang="both"] span[data-lang-block] { display: inline; }
  .lang-both-stack > [data-lang-block="en"] { opacity:.62; font-size:.82em; margin-top:.2em; font-weight:500; }
  .duo { background: linear-gradient(160deg, #0E7C7B 0%, #0B1220 100%); }
  .grain { background-image: radial-gradient(rgba(255,255,255,.06) 1px, transparent 1px); background-size: 14px 14px; }

  /* ================= DARK MODE STYLES ================= */
  html.dark body { background: #0B1220 !important; color: #F1F5F9 !important; }
  html.dark header { background: rgba(11, 18, 32, 0.95) !important; border-color: #1E293B !important; }
  html.dark .text-navy { color: #F1F5F9 !important; }
  html.dark .text-navy\/70, html.dark .text-navy\/65, html.dark .text-navy\/60, html.dark .text-navy\/55, html.dark .text-navy\/50 { color: #94A3B8 !important; }
  html.dark .border-navy\/10, html.dark .border-navy\/15 { border-color: #1E293B !important; }
  html.dark .bg-fog { background: #111B2E !important; }
  html.dark .bg-white { background: #1E293B !important; color: #F1F5F9 !important; }
  html.dark .bg-white\/95 { background: rgba(30, 41, 59, 0.95) !important; }
  html.dark .bg-slate-50, html.dark .bg-gray-50 { background: #1E293B !important; }
  html.dark .border-gray-200, html.dark .border-slate-200 { border-color: #334155 !important; }
  html.dark input, html.dark select, html.dark textarea { background: #0F172A !important; color: #FFFFFF !important; border-color: #334155 !important; }
  html.dark .shadow-xl, html.dark .shadow-lg, html.dark .shadow-md, html.dark .shadow-sm { box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important; }
</style>
</head>
<body class="antialiased">

<!-- ================= NAV ================= -->
<header class="sticky top-0 z-50 bg-white/95 backdrop-blur border-b border-navy/10">
  <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
    <a href="#top" class="flex items-center gap-2.5">
      <img src="/logo.jpg" alt="Meash" class="w-9 h-9 rounded-lg object-cover">
      <span class="font-serif font-semibold text-lg tracking-tight">Meash Cleaning Solution</span>
    </a>
    <nav class="hidden md:flex items-center gap-8 text-[13px] font-semibold tracking-wide text-navy/70">
      <a href="#services" class="hover:text-teal"><span data-lang-block="am">አገልግሎቶች</span><span data-lang-block="en">Services</span></a>
      <a href="#process" class="hover:text-teal"><span data-lang-block="am">ሂደት</span><span data-lang-block="en">Process</span></a>
      <a href="#reviews" class="hover:text-teal"><span data-lang-block="am">አስተያየቶች</span><span data-lang-block="en">Reviews</span></a>
      <a href="#track" class="hover:text-teal"><span data-lang-block="am">ትዕዛዝ ፈልግ</span><span data-lang-block="en">Track Order</span></a>
    </nav>
    <div class="flex items-center gap-2">
      <!-- Dark Mode Toggle Button -->
      <button type="button" onclick="toggleTheme()" id="theme-toggle-btn" class="p-2 rounded-full border border-navy/15 text-navy hover:bg-navy/5 transition cursor-pointer flex items-center justify-center" title="የጨለማ/ብርሃን ሁነታ ቀይር (Toggle Dark/Light Mode)">
        <i data-lucide="moon" id="theme-icon" class="w-4 h-4"></i>
      </button>

      <div class="hidden sm:flex items-center gap-1 mr-1 border border-navy/15 rounded-full p-0.5">
        <button type="button" onclick="setLang('am')" class="lang-btn text-[11px] font-bold px-2.5 py-1 rounded-full cursor-pointer">አማ</button>
        <button type="button" onclick="setLang('en')" class="lang-btn text-[11px] font-bold px-2.5 py-1 rounded-full cursor-pointer">EN</button>
        <button type="button" onclick="setLang('both')" class="lang-btn text-[11px] font-bold px-2.5 py-1 rounded-full cursor-pointer">አማ/EN</button>
      </div>
      <a href="#book" class="bg-navy hover:bg-teal-dark text-white text-[13px] font-bold px-5 py-2.5 rounded-md transition">
        <span data-lang-block="am">ቀጠሮ ይያዙ</span><span data-lang-block="en">Book Now</span>
      </a>
    </div>
  </div>
</header>

<main id="top">

<!-- ================= HERO ================= -->
<section class="relative">
  <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-[1.1fr_.9fr] gap-0 min-h-[560px]">
    <div class="flex flex-col justify-center py-16 md:py-24 pr-0 md:pr-14">
      <div class="flex items-center gap-2 text-teal-dark font-bold text-xs tracking-wide mb-6">
        <i data-lucide="badge-check" class="w-4 h-4"></i>
        <span data-lang-block="am">ላይሰንስ ያለው • 160°ሲ ስቲም ቴክኖሎጂ</span>
        <span data-lang-block="en">Licensed &amp; Insured — 160°C Steam Technology</span>
      </div>
      <h1 class="font-serif font-semibold text-[2.6rem] md:text-[3.4rem] leading-[1.05] tracking-tight lang-both-stack">
        <div data-lang-block="am">ንፁህ ቤት፣<br>የተረጋገጠ ውጤት።</div>
        <div data-lang-block="en">A cleaner home,<br>done properly.</div>
      </h1>
      <p class="mt-6 text-navy/65 text-[17px] leading-relaxed max-w-md lang-both-stack">
        <span data-lang-block="am">ሶፋ፣ ምንጣፍ፣ ፍራሽና መስታወት ጥልቅ እጥበት — በሰለጠነ ቡድን፣ ዘመናዊ መሳሪያ በመጠቀም። ዋጋውን ወዲያውኑ ይመልከቱ።</span>
        <span data-lang-block="en">Sofa, carpet, mattress and glass deep-cleaning — carried out by a trained team. See the price instantly, before you book.</span>
      </p>
      <div class="mt-9 flex flex-wrap items-center gap-4">
        <a href="#book" class="bg-navy text-white font-bold text-sm px-7 py-3.5 rounded-md hover:bg-teal-dark transition flex items-center gap-2">
          <span data-lang-block="am">ዋጋ ይመልከቱና ይያዙ</span><span data-lang-block="en">See Price &amp; Book</span>
          <i data-lucide="arrow-up-right" class="w-4 h-4"></i>
        </a>
        <a href="tel:0900103183" class="font-bold text-sm text-navy flex items-center gap-2 border-b-2 border-transparent hover:border-navy pb-0.5">
          <i data-lucide="phone" class="w-4 h-4"></i> 0900 10 31 83
        </a>
      </div>
      <div class="mt-14 grid grid-cols-3 gap-6 border-t border-navy/10 pt-7 max-w-md">
        <div><div class="font-serif font-semibold text-2xl">3,200+</div><div class="text-[12px] text-navy/55 mt-0.5" data-lang-block="am">የተከናወኑ ስራዎች</div><div class="text-[12px] text-navy/55 mt-0.5" data-lang-block="en">Jobs completed</div></div>
        <div><div class="font-serif font-semibold text-2xl">4.9<span class="text-base">/5</span></div><div class="text-[12px] text-navy/55 mt-0.5" data-lang-block="am">የደንበኛ ደረጃ</div><div class="text-[12px] text-navy/55 mt-0.5" data-lang-block="en">Customer rating</div></div>
        <div><div class="font-serif font-semibold text-2xl">6</div><div class="text-[12px] text-navy/55 mt-0.5" data-lang-block="am">ዓመታት ልምድ</div><div class="text-[12px] text-navy/55 mt-0.5" data-lang-block="en">Years in business</div></div>
      </div>
    </div>
    <div class="relative hidden md:block">
      <div class="duo grain absolute inset-y-8 right-0 left-6 rounded-2xl overflow-hidden">
        <div class="absolute bottom-7 left-7 right-7 bg-white/95 backdrop-blur rounded-xl p-5 shadow-xl">
          <div class="flex items-center gap-2 text-teal-dark font-bold text-xs mb-2">
            <i data-lucide="shield-check" class="w-4 h-4"></i>
            <span data-lang-block="am">የዛሬ ውጤት</span><span data-lang-block="en">Today's job</span>
          </div>
          <p class="text-sm text-navy/70 leading-snug" data-lang-block="am">3 መቀመጫ ሶፋ — ቦሌ, ካዛንቺስ<br>ተጠናቅቋል በ 47 ደቂቃ</p>
          <p class="text-sm text-navy/70 leading-snug" data-lang-block="en">3-seat sofa — Bole, Kazanchis<br>Completed in 47 minutes</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ================= LOGO/TRUST STRIP ================= -->
<section class="border-y border-navy/10 bg-fog">
  <div class="max-w-6xl mx-auto px-6 py-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-3 text-navy/50 text-xs font-bold tracking-wide">
    <span class="flex items-center gap-1.5"><i data-lucide="shield-check" class="w-4 h-4"></i> <span data-lang-block="am">የተመዘገበ ንግድ</span><span data-lang-block="en">Registered Business</span></span>
    <span class="flex items-center gap-1.5"><i data-lucide="users" class="w-4 h-4"></i> <span data-lang-block="am">የሰለጠነ ቡድን</span><span data-lang-block="en">Trained Staff</span></span>
    <span class="flex items-center gap-1.5"><i data-lucide="clock" class="w-4 h-4"></i> <span data-lang-block="am">7 ቀን አገልግሎት</span><span data-lang-block="en">7-Day Service</span></span>
    <span class="flex items-center gap-1.5"><i data-lucide="building-2" class="w-4 h-4"></i> <span data-lang-block="am">ኮርፖሬት ውል</span><span data-lang-block="en">Corporate Contracts</span></span>
  </div>
</section>

<!-- ================= SERVICES (with live price) ================= -->
<section id="services" class="max-w-6xl mx-auto px-6 py-24">
  <div class="max-w-lg mb-14">
    <div class="text-teal-dark font-bold text-xs tracking-wide mb-3"><span data-lang-block="am">አገልግሎቶች</span><span data-lang-block="en">SERVICES</span></div>
    <h2 class="font-serif font-semibold text-3xl md:text-4xl leading-tight"><span data-lang-block="am">የምናቀርባቸው አገልግሎቶች</span><span data-lang-block="en">What we clean</span></h2>
  </div>
  <div id="services-list" class="divide-y divide-navy/10 border-t border-b border-navy/10">
    <div class="grid md:grid-cols-[auto_1fr_auto_auto] gap-4 md:gap-8 items-center py-7 group" data-svc-row="1">
      <i data-lucide="sofa" class="w-7 h-7 text-teal-dark"></i>
      <div>
        <h3 class="font-serif font-semibold text-xl"><span data-lang-block="am">የሶፋ እጥበት</span><span data-lang-block="en">Sofa Cleaning</span></h3>
        <p class="text-navy/60 text-sm mt-1"><span data-lang-block="am">ጥልቅ ስቲም እጥበት፣ ሽታ ማስወገጃ ጨምሮ።</span><span data-lang-block="en">Deep steam wash including odor removal.</span></p>
      </div>
      <div class="text-right font-serif font-semibold text-lg svc-price" data-svc-price="1">350 ETB</div>
      <a href="#book" onclick="preselectService('sofa')" class="text-sm font-bold text-navy flex items-center gap-1"><span data-lang-block="am">ይያዙ</span><span data-lang-block="en">Book</span> <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
    </div>
    <div class="grid md:grid-cols-[auto_1fr_auto_auto] gap-4 md:gap-8 items-center py-7 group" data-svc-row="2">
      <i data-lucide="layout-grid" class="w-7 h-7 text-teal-dark"></i>
      <div>
        <h3 class="font-serif font-semibold text-xl"><span data-lang-block="am">የምንጣፍ እጥበት</span><span data-lang-block="en">Carpet Cleaning</span></h3>
        <p class="text-navy/60 text-sm mt-1"><span data-lang-block="am">አቧራና ጠባሳ ማስወገድ በሙያ ማሽኖች።</span><span data-lang-block="en">Dust and stain removal with pro-grade equipment.</span></p>
      </div>
      <div class="text-right font-serif font-semibold text-lg svc-price" data-svc-price="2">80 ETB</div>
      <a href="#book" onclick="preselectService('carpet')" class="text-sm font-bold text-navy flex items-center gap-1"><span data-lang-block="am">ይያዙ</span><span data-lang-block="en">Book</span> <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
    </div>
    <div class="grid md:grid-cols-[auto_1fr_auto_auto] gap-4 md:gap-8 items-center py-7 group" data-svc-row="3">
      <i data-lucide="bed" class="w-7 h-7 text-teal-dark"></i>
      <div>
        <h3 class="font-serif font-semibold text-xl"><span data-lang-block="am">የፍራሽ እጥበት</span><span data-lang-block="en">Mattress Cleaning</span></h3>
        <p class="text-navy/60 text-sm mt-1"><span data-lang-block="am">ንፁህና ጤናማ እንቅልፍ እናቀርባለን።</span><span data-lang-block="en">For a cleaner, healthier night's sleep.</span></p>
      </div>
      <div class="text-right font-serif font-semibold text-lg svc-price" data-svc-price="3">600 ETB</div>
      <a href="#book" onclick="preselectService('mattress')" class="text-sm font-bold text-navy flex items-center gap-1"><span data-lang-block="am">ይያዙ</span><span data-lang-block="en">Book</span> <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
    </div>
    <div class="grid md:grid-cols-[auto_1fr_auto_auto] gap-4 md:gap-8 items-center py-7 group" data-svc-row="4">
      <i data-lucide="square" class="w-7 h-7 text-teal-dark"></i>
      <div>
        <h3 class="font-serif font-semibold text-xl"><span data-lang-block="am">የመስታወት እጥበት</span><span data-lang-block="en">Glass &amp; Window</span></h3>
        <p class="text-navy/60 text-sm mt-1"><span data-lang-block="am">ደማቅ፣ ጭረት የሌለው ንፅህና።</span><span data-lang-block="en">Streak-free shine, inside and out.</span></p>
      </div>
      <div class="text-right font-serif font-semibold text-lg svc-price" data-svc-price="4">70 ETB</div>
      <a href="#book" onclick="preselectService('glass')" class="text-sm font-bold text-navy flex items-center gap-1"><span data-lang-block="am">ይያዙ</span><span data-lang-block="en">Book</span> <i data-lucide="arrow-right" class="w-4 h-4"></i></a>
    </div>
  </div>
  <p class="text-xs text-navy/40 mt-4" data-lang-block="am">* ዋጋዎች በአካባቢና በብዛት ሊለያዩ ይችላሉ።</p>
  <p class="text-xs text-navy/40 mt-4" data-lang-block="en">* Prices shown are per unit and may vary by location or quantity.</p>
</section>

<!-- ================= PROCESS ================= -->
<section id="process" class="bg-navy text-white py-24">
  <div class="max-w-6xl mx-auto px-6">
    <div class="max-w-lg mb-14">
      <div class="text-teal font-bold text-xs tracking-wide mb-3"><span data-lang-block="am">ሂደት</span><span data-lang-block="en">PROCESS</span></div>
      <h2 class="font-serif font-semibold text-3xl md:text-4xl leading-tight"><span data-lang-block="am">ከጥሪ እስከ ማጠናቀቅ</span><span data-lang-block="en">From call to completion</span></h2>
    </div>
    <div class="grid md:grid-cols-4 gap-10">
      <div class="border-t-2 border-teal pt-5">
        <div class="font-serif text-3xl text-teal mb-3">01</div>
        <p class="font-semibold mb-1"><span data-lang-block="am">ቀጠሮ ይያዙ</span><span data-lang-block="en">Book online or by phone</span></p>
        <p class="text-white/55 text-sm"><span data-lang-block="am">አገልግሎት፣ ቀንና ሰዓት ይምረጡ።</span><span data-lang-block="en">Choose a service, date and time.</span></p>
      </div>
      <div class="border-t-2 border-teal pt-5">
        <div class="font-serif text-3xl text-teal mb-3">02</div>
        <p class="font-semibold mb-1"><span data-lang-block="am">ማረጋገጫ እንደውላለን</span><span data-lang-block="en">We confirm the appointment</span></p>
        <p class="text-white/55 text-sm"><span data-lang-block="am">ተቀባይ ደውሎ ዝርዝሮችን ያረጋግጣል።</span><span data-lang-block="en">Our reception confirms details by phone.</span></p>
      </div>
      <div class="border-t-2 border-teal pt-5">
        <div class="font-serif text-3xl text-teal mb-3">03</div>
        <p class="font-semibold mb-1"><span data-lang-block="am">ቡድናችን ይመጣል</span><span data-lang-block="en">Our team arrives on time</span></p>
        <p class="text-white/55 text-sm"><span data-lang-block="am">በዘመናዊ መሳሪያ ስራውን ያከናውናሉ።</span><span data-lang-block="en">Work is done with professional equipment.</span></p>
      </div>
      <div class="border-t-2 border-teal pt-5">
        <div class="font-serif text-3xl text-teal mb-3">04</div>
        <p class="font-semibold mb-1"><span data-lang-block="am">ክፍያና ክትትል</span><span data-lang-block="en">Payment &amp; follow-up</span></p>
        <p class="text-white/55 text-sm"><span data-lang-block="am">በማግስቱ ደውለን እናረጋግጣለን።</span><span data-lang-block="en">We call the next day to check you're satisfied.</span></p>
      </div>
    </div>
  </div>
</section>

<!-- ================= REVIEWS ================= -->
<section id="reviews" class="max-w-6xl mx-auto px-6 py-24">
  <div class="max-w-lg mb-14">
    <div class="text-teal-dark font-bold text-xs tracking-wide mb-3"><span data-lang-block="am">አስተያየት</span><span data-lang-block="en">TESTIMONIALS</span></div>
    <h2 class="font-serif font-semibold text-3xl md:text-4xl leading-tight"><span data-lang-block="am">ደንበኞቻችን ምን ይላሉ</span><span data-lang-block="en">What clients say</span></h2>
  </div>
  <div class="grid md:grid-cols-3 gap-8">
    <div class="border-l-2 border-teal pl-5">
      <p class="text-[15px] text-navy/80 leading-relaxed" data-lang-block="am">"ሶፋዬ እንደ አዲስ ሆኗል፤ ቡድኑ በጣም ጨዋና ፈጣን ነበር።"</p>
      <p class="text-[15px] text-navy/80 leading-relaxed" data-lang-block="en">"My sofa looks brand new. The team was professional and fast."</p>
      <p class="mt-4 font-bold text-sm text-navy/50">Bethlehem T. — Bole</p>
    </div>
    <div class="border-l-2 border-teal pl-5">
      <p class="text-[15px] text-navy/80 leading-relaxed" data-lang-block="am">"በስልክ ቀላል ቀጠሮ ያዝኩ፤ በሰዓቱ ደረሱ።"</p>
      <p class="text-[15px] text-navy/80 leading-relaxed" data-lang-block="en">"Booking was simple and they arrived exactly on time."</p>
      <p class="mt-4 font-bold text-sm text-navy/50">Yonas A. — Kazanchis</p>
    </div>
    <div class="border-l-2 border-teal pl-5">
      <p class="text-[15px] text-navy/80 leading-relaxed" data-lang-block="am">"ለቢሮያችን ወርሃዊ ውል ገባን፣ ውጤቱ በጣም ጥሩ ነው።"</p>
      <p class="text-[15px] text-navy/80 leading-relaxed" data-lang-block="en">"We signed a monthly contract for our office — consistently great results."</p>
      <p class="mt-4 font-bold text-sm text-navy/50">Selam Hotel — Corporate</p>
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
      <div class="flex justify-between mb-8 text-[11px] font-bold text-navy/35 uppercase tracking-wide">
        <span class="step-dot text-teal-dark" data-step="1">01 <span data-lang-block="am">አገልግሎት</span><span data-lang-block="en">Service</span></span>
        <span class="step-dot" data-step="2">02 <span data-lang-block="am">አድራሻ</span><span data-lang-block="en">Address</span></span>
        <span class="step-dot" data-step="3">03 <span data-lang-block="am">ቀጠሮ</span><span data-lang-block="en">Schedule</span></span>
        <span class="step-dot" data-step="4">04 <span data-lang-block="am">ማረጋገጫ</span><span data-lang-block="en">Confirm</span></span>
      </div>

      <form id="booking-form" onsubmit="submitBooking(event)">
        <!-- STEP 1 -->
        <div id="step-1" class="wizard-step">
          <p class="font-semibold mb-4 text-sm"><span data-lang-block="am">አገልግሎት ይምረጡ</span><span data-lang-block="en">Choose a service</span></p>
          <div class="grid grid-cols-2 gap-3 mb-2">
            <button type="button" onclick="selectServiceForBooking('sofa',1)" class="svc-btn border-2 border-navy rounded-lg p-4 text-left flex items-center gap-3 cursor-pointer">
              <i data-lucide="sofa" class="w-5 h-5 text-teal-dark"></i>
              <span><span class="font-semibold text-sm block"><span data-lang-block="am">ሶፋ</span><span data-lang-block="en">Sofa</span></span><span class="text-xs text-navy/50 wiz-price" data-wiz-price="1">350 ETB / unit</span></span>
            </button>
            <button type="button" onclick="selectServiceForBooking('carpet',2)" class="svc-btn border-2 border-navy/10 rounded-lg p-4 text-left flex items-center gap-3 cursor-pointer">
              <i data-lucide="layout-grid" class="w-5 h-5 text-teal-dark"></i>
              <span><span class="font-semibold text-sm block"><span data-lang-block="am">ምንጣፍ</span><span data-lang-block="en">Carpet</span></span><span class="text-xs text-navy/50 wiz-price" data-wiz-price="2">80 ETB / unit</span></span>
            </button>
            <button type="button" onclick="selectServiceForBooking('mattress',3)" class="svc-btn border-2 border-navy/10 rounded-lg p-4 text-left flex items-center gap-3 cursor-pointer">
              <i data-lucide="bed" class="w-5 h-5 text-teal-dark"></i>
              <span><span class="font-semibold text-sm block"><span data-lang-block="am">ፍራሽ</span><span data-lang-block="en">Mattress</span></span><span class="text-xs text-navy/50 wiz-price" data-wiz-price="3">600 ETB / unit</span></span>
            </button>
            <button type="button" onclick="selectServiceForBooking('glass',4)" class="svc-btn border-2 border-navy/10 rounded-lg p-4 text-left flex items-center gap-3 cursor-pointer">
              <i data-lucide="square" class="w-5 h-5 text-teal-dark"></i>
              <span><span class="font-semibold text-sm block"><span data-lang-block="am">መስታወት</span><span data-lang-block="en">Glass</span></span><span class="text-xs text-navy/50 wiz-price" data-wiz-price="4">70 ETB / unit</span></span>
            </button>
          </div>
          <label class="text-sm font-semibold block mt-5"><span data-lang-block="am">ብዛት</span><span data-lang-block="en">Quantity</span></label>
          <div class="flex items-center gap-3 mt-2 mb-2">
            <button type="button" onclick="changeQty(-1)" class="w-9 h-9 rounded-md border border-navy/15 font-bold cursor-pointer hover:bg-fog">−</button>
            <span id="qty-display" class="font-serif font-semibold text-xl w-6 text-center">1</span>
            <button type="button" onclick="changeQty(1)" class="w-9 h-9 rounded-md border border-navy/15 font-bold cursor-pointer hover:bg-fog">+</button>
          </div>
          <div class="bg-teal-light rounded-md px-4 py-3 mt-5 mb-6 flex items-center justify-between">
            <span class="text-sm font-semibold text-teal-dark"><span data-lang-block="am">የግምት ድምር</span><span data-lang-block="en">Estimated total</span></span>
            <span id="wiz-total" class="font-serif font-semibold text-xl text-teal-dark">350 ETB</span>
          </div>
          <button type="button" onclick="showWizardStep(2)" class="w-full bg-navy hover:bg-teal-dark text-white font-bold py-3.5 rounded-md transition cursor-pointer"><span data-lang-block="am">ቀጣይ</span><span data-lang-block="en">Continue</span></button>
        </div>

        <!-- STEP 2 -->
        <div id="step-2" class="wizard-step hidden">
          <p class="font-semibold mb-4 text-sm"><span data-lang-block="am">አድራሻ</span><span data-lang-block="en">Address</span></p>
          <select id="book-subcity" class="w-full mb-3 rounded-md border border-navy/15 px-4 py-3 bg-white text-sm" required>
            <option value="">-- Subcity / ክፍለ ከተማ --</option>
            <option value="Bole">Bole (ቦሌ)</option>
            <option value="Yeka">Yeka (የካ)</option>
            <option value="Kirkos">Kirkos (ቂርቆስ)</option>
            <option value="Arada">Arada (አራዳ)</option>
            <option value="Lideta">Lideta (ልደታ)</option>
            <option value="Nifas Silk">Nifas Silk (ንፋስ ስልክ)</option>
            <option value="Kolfe">Kolfe (ኮልፌ)</option>
            <option value="Gulele">Gulele (ጉለሌ)</option>
            <option value="Akaky Kaliti">Akaky Kaliti (አቃቂ ቃሊቲ)</option>
            <option value="Lemi Kura">Lemi Kura (ለሚ ኩራ)</option>
          </select>
          <input id="book-address" type="text" placeholder="Street, house no. / ጎዳና፣ የቤት ቁጥር" class="w-full mb-3 rounded-md border border-navy/15 px-4 py-3 text-sm" required>
          <input id="book-landmark" type="text" placeholder="Landmark / መለያ ቦታ (ምሳሌ፡ ሆቴል አጠገብ)" class="w-full mb-3 rounded-md border border-navy/15 px-4 py-3 text-sm">
          <input type="hidden" id="book-latitude"><input type="hidden" id="book-longitude">
          <button type="button" id="btn-gps" onclick="getWebGps()" class="w-full mb-4 border border-navy/15 text-navy font-semibold text-sm py-3 rounded-md flex items-center justify-center gap-2 cursor-pointer hover:bg-fog">
            <i data-lucide="map-pin" class="w-4 h-4 text-teal-dark"></i> <span data-lang-block="am">ካርታዬን ያዝ</span><span data-lang-block="en">Use my GPS location</span>
          </button>
          <div id="web-gps-feedback" class="hidden mb-3"></div>
          <div class="flex gap-3">
            <button type="button" onclick="showWizardStep(1)" class="flex-1 border border-navy/15 font-bold py-3.5 rounded-md text-sm cursor-pointer hover:bg-fog"><span data-lang-block="am">ተመለስ</span><span data-lang-block="en">Back</span></button>
            <button type="button" onclick="showWizardStep(3)" class="flex-1 bg-navy hover:bg-teal-dark text-white font-bold py-3.5 rounded-md text-sm transition cursor-pointer"><span data-lang-block="am">ቀጣይ</span><span data-lang-block="en">Continue</span></button>
          </div>
        </div>

        <!-- STEP 3 -->
        <div id="step-3" class="wizard-step hidden">
          <div class="flex items-center justify-between mb-3">
            <p class="font-semibold text-sm text-navy"><span data-lang-block="am">ቀንና ሰዓት ይምረጡ</span><span data-lang-block="en">Select Date &amp; Time</span></p>
            <span class="text-[10px] font-bold text-teal-dark bg-teal-light px-2.5 py-1 rounded-full">ነባሪ፡ የኢትዮጵያ ካላንደር</span>
          </div>

          <!-- Calendar Mode Switcher (Ethiopian vs Gregorian) -->
          <div class="mb-4 p-1 bg-navy/5 rounded-xl flex gap-1 border border-navy/10">
            <button type="button" id="tab-cal-eth" onclick="switchCalendarMode('eth')" class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 bg-white text-navy shadow-sm cursor-pointer">
              <span>🗓 በኢትዮጵያ ካላንደር (ዓ.ም)</span>
            </button>
            <button type="button" id="tab-cal-greg" onclick="switchCalendarMode('greg')" class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-1.5 text-navy/60 hover:text-navy cursor-pointer">
              <span>🌐 በፈረንጆች (Gregorian - GC)</span>
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
              <span class="text-navy/55 flex items-center gap-1 font-semibold">
                <span>🌐 የተለወጠው የፈረንጆች ቀን (GC):</span>
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
              <span class="text-navy/70 flex items-center gap-1 font-semibold">
                <span>🗓 የተለወጠው የኢትዮጵያ ቀን (ETH):</span>
              </span>
              <span id="eth-display-from-greg" class="font-bold text-teal-dark text-sm">— ዓ.ም</span>
            </div>
          </div>

          <!-- Hidden master value for submission -->
          <input type="hidden" id="book-date" required>

          <!-- Time Slot — Morning / Afternoon ONLY (NO EVENING) -->
          <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
              <p class="text-xs font-bold text-navy/80">🕐 የሰዓት ክፍለ ጊዜ ይምረጡ (ጠዋት ወይም ከሰዓት ብቻ)</p>
              <span class="text-[10px] text-navy/50">ማታ የለም (No Evening)</span>
            </div>
            <div class="grid grid-cols-2 gap-2.5">
              <label class="relative cursor-pointer">
                <input type="radio" name="time_slot_radio" value="morning" onchange="setTimeSlot(this.value)" class="sr-only peer" required>
                <div class="p-3.5 rounded-xl border-2 border-navy/15 peer-checked:border-teal peer-checked:bg-teal-light/40 transition text-center shadow-sm hover:border-teal/50 bg-white">
                  <div class="text-xl mb-1">🌅</div>
                  <p class="font-extrabold text-xs text-navy peer-checked:text-teal-dark">ጥዋት (Morning)</p>
                  <div class="mt-1 pt-1 border-t border-navy/10 text-[10px] space-y-0.5 font-bold">
                    <p class="text-teal-dark">2:00 – 6:00 ጠዋቱ <span class="font-normal text-navy/50">(የኢትዮጵያ ሰዓት)</span></p>
                    <p class="text-navy/50 font-normal">8:00 AM – 12:00 PM <span class="text-[9px]">(GC)</span></p>
                  </div>
                </div>
              </label>
              <label class="relative cursor-pointer">
                <input type="radio" name="time_slot_radio" value="afternoon" onchange="setTimeSlot(this.value)" class="sr-only peer">
                <div class="p-3.5 rounded-xl border-2 border-navy/15 peer-checked:border-teal peer-checked:bg-teal-light/40 transition text-center shadow-sm hover:border-teal/50 bg-white">
                  <div class="text-xl mb-1">☀️</div>
                  <p class="font-extrabold text-xs text-navy peer-checked:text-teal-dark">ከሰዓት (Afternoon)</p>
                  <div class="mt-1 pt-1 border-t border-navy/10 text-[10px] space-y-0.5 font-bold">
                    <p class="text-teal-dark">7:00 – 11:00 ከሰዓቱ <span class="font-normal text-navy/50">(የኢትዮጵያ ሰዓት)</span></p>
                    <p class="text-navy/50 font-normal">1:00 PM – 5:00 PM <span class="text-[9px]">(GC)</span></p>
                  </div>
                </div>
              </label>
            </div>
            <input type="hidden" id="book-time-slot" required>
            <p id="time-slot-error" class="hidden text-xs text-red-600 font-bold mt-2 flex items-center gap-1">
              <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i> እባክዎ ሰዓት (ጠዋት ወይም ከሰዓት) ይምረጡ!
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
          <input id="book-phone" type="tel" value="0900103183" placeholder="Phone / ስልክ ቁጥር" class="w-full mb-3 rounded-md border border-navy/15 px-4 py-3 text-sm" required>

          <!-- Recurring Subscription Plan Selector -->
          <div class="mb-4 p-3.5 rounded-xl bg-teal-light/50 border border-teal/20">
            <label class="block text-xs font-bold text-teal-dark uppercase tracking-wider mb-2">
              <span data-lang-block="am">🔄 የፅዳት ድግግሞሽ እና የቅናሽ እቅድ (Subscription Plan)</span>
              <span data-lang-block="en">🔄 Cleaning Frequency &amp; Discount Plan</span>
            </label>
            <div class="grid grid-cols-2 gap-2 text-xs">
              <label class="flex items-center gap-2 p-2.5 rounded-lg bg-white border border-navy/15 cursor-pointer hover:border-teal transition">
                <input type="radio" name="sub_plan" value="one_time" checked onchange="updateSubscriptionDiscount()" class="text-teal focus:ring-teal">
                <div>
                  <span class="font-bold block text-navy text-[11px]"><span data-lang-block="am">የአንድ ጊዜ</span><span data-lang-block="en">One-time</span></span>
                  <span class="text-[9px] text-navy/50"><span data-lang-block="am">መደበኛ ዋጋ</span><span data-lang-block="en">Standard</span></span>
                </div>
              </label>
              <label class="flex items-center gap-2 p-2.5 rounded-lg bg-white border border-navy/15 cursor-pointer hover:border-teal transition">
                <input type="radio" name="sub_plan" value="weekly" onchange="updateSubscriptionDiscount()" class="text-teal focus:ring-teal">
                <div>
                  <span class="font-bold block text-navy text-[11px]"><span data-lang-block="am">በየሳምንቱ</span><span data-lang-block="en">Weekly</span></span>
                  <span class="text-[9px] font-extrabold text-teal-dark">15% ቅናሽ (OFF)</span>
                </div>
              </label>
              <label class="flex items-center gap-2 p-2.5 rounded-lg bg-white border border-navy/15 cursor-pointer hover:border-teal transition">
                <input type="radio" name="sub_plan" value="biweekly" onchange="updateSubscriptionDiscount()" class="text-teal focus:ring-teal">
                <div>
                  <span class="font-bold block text-navy text-[11px]"><span data-lang-block="am">በ2 ሳምንት</span><span data-lang-block="en">Bi-weekly</span></span>
                  <span class="text-[9px] font-extrabold text-teal-dark">10% ቅናሽ (OFF)</span>
                </div>
              </label>
              <label class="flex items-center gap-2 p-2.5 rounded-lg bg-white border border-navy/15 cursor-pointer hover:border-teal transition">
                <input type="radio" name="sub_plan" value="monthly" onchange="updateSubscriptionDiscount()" class="text-teal focus:ring-teal">
                <div>
                  <span class="font-bold block text-navy text-[11px]"><span data-lang-block="am">በየወሩ</span><span data-lang-block="en">Monthly</span></span>
                  <span class="text-[9px] font-extrabold text-teal-dark">5% ቅናሽ (OFF)</span>
                </div>
              </label>
            </div>
            <p id="sub-discount-hint" class="hidden text-[11px] font-semibold text-teal-dark mt-2 pt-1 border-t border-teal/20">
              🎉 <span id="sub-discount-text"></span>
            </p>
          </div>

          <div class="bg-fog rounded-md px-4 py-3 mb-5 flex items-center justify-between border border-navy/10">
            <span class="text-sm font-semibold text-navy/70"><span data-lang-block="am">ጠቅላላ የሚከፈል</span><span data-lang-block="en">Total to pay</span></span>
            <span id="wiz-total-2" class="font-serif font-semibold text-xl text-teal-dark">350 ETB</span>
          </div>
          <div class="flex gap-3">
            <button type="button" onclick="showWizardStep(3)" class="flex-1 border border-navy/15 font-bold py-3.5 rounded-md text-sm cursor-pointer hover:bg-fog"><span data-lang-block="am">ተመለስ</span><span data-lang-block="en">Back</span></button>
            <button type="submit" id="btn-submit-booking" class="flex-1 bg-teal-dark hover:bg-teal text-white font-bold py-3.5 rounded-md text-sm transition cursor-pointer"><span data-lang-block="am">ቀጠሮ አረጋግጥ</span><span data-lang-block="en">Confirm Booking</span></button>
          </div>
        </div>

        <!-- SUCCESS -->
        <div id="step-success" class="hidden text-center py-6">
          <i data-lucide="check-circle-2" class="w-12 h-12 text-teal-dark mx-auto mb-4"></i>
          <h3 class="font-serif font-semibold text-xl mb-1"><span data-lang-block="am">ተመዝግቧል!</span><span data-lang-block="en">Booking received!</span></h3>
          <p class="text-navy/55 text-sm mb-1"><span data-lang-block="am">የቀጠሮ ቁጥር</span><span data-lang-block="en">Your reference number</span></p>
          <p class="font-serif font-semibold text-2xl text-teal-dark mb-4" id="success-booking-id"></p>
          <p class="text-xs text-navy/60 max-w-sm mx-auto mb-6"><span data-lang-block="am">ቡድናችን በቅርቡ ደውሎ ዝርዝሮችን ያረጋግጥልዎታል። እናመሰግናለን!</span><span data-lang-block="en">Our team will call you shortly to confirm the appointment. Thank you!</span></p>
          <button type="button" onclick="resetWizard()" class="border border-navy/15 font-bold px-6 py-3 rounded-md text-sm cursor-pointer hover:bg-fog"><span data-lang-block="am">ሌላ ቀጠሮ ያዙ</span><span data-lang-block="en">Book another</span></button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- ================= TRACK ORDER & LIVE GPS CLEANER TRACKING ================= -->
<section id="track" class="max-w-2xl mx-auto px-6 py-20 text-center">
  <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-light text-teal-dark text-xs font-bold mb-3">
    <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
    <span data-lang-block="am">የቀጥታ የጽዳት ቡድን መገኛ ካርታ (Live GPS)</span>
    <span data-lang-block="en">Live GPS Cleaner Tracking</span>
  </div>
  <h2 class="font-serif font-semibold text-2xl mb-2"><span data-lang-block="am">ትዕዛዝዎን እና የቡድኑን መገኛ ይከታተሉ</span><span data-lang-block="en">Track your order &amp; Cleaner GPS</span></h2>
  <p class="text-xs text-navy/60 mb-5 max-w-md mx-auto"><span data-lang-block="am">የትዕዛዝ ቁጥርዎን ወይም ስልክዎን በማስገባት የቡድኑን የስራ ደረጃ እና የቀጥታ መገኛ በካርታው ላይ ይመልከቱ</span><span data-lang-block="en">Enter your order ID or phone number to see live status and real-time cleaner location.</span></p>
  <form onsubmit="trackOrder(event)" class="max-w-md mx-auto flex gap-2">
    <input id="track-input" type="text" placeholder="MEASH-000123 or phone" class="flex-1 rounded-md border border-navy/15 px-4 py-3 text-sm focus:outline-none focus:border-teal">
    <button type="submit" id="btn-track-submit" class="bg-navy hover:bg-teal-dark text-white font-bold px-6 rounded-md text-sm transition cursor-pointer"><span data-lang-block="am">ፈልግ</span><span data-lang-block="en">Track</span></button>
  </form>
  <div id="track-result" class="hidden mt-6 text-left"></div>
</section>

<!-- ================= FOOTER ================= -->
<footer class="bg-navy text-white/60 py-14 border-t border-white/10">
  <div class="max-w-6xl mx-auto px-6 grid md:grid-cols-4 gap-10 text-sm">
    <div>
      <div class="flex items-center gap-2 mb-3"><img src="/logo.jpg" class="w-8 h-8 rounded-md object-cover"><span class="font-serif font-semibold text-white">Meash</span></div>
      <p>Addis Ababa, Ethiopia</p>
      <p class="font-bold text-white/80 mt-1">0900 10 31 83</p>
    </div>
    <div>
      <p class="text-white font-semibold mb-3">Services</p>
      <p class="mb-1.5"><a href="#services" class="hover:text-teal">Sofa Cleaning</a></p>
      <p class="mb-1.5"><a href="#services" class="hover:text-teal">Carpet Cleaning</a></p>
      <p class="mb-1.5"><a href="#services" class="hover:text-teal">Mattress Cleaning</a></p>
      <p><a href="#services" class="hover:text-teal">Office &amp; Corporate</a></p>
    </div>
    <div>
      <p class="text-white font-semibold mb-3">Company</p>
      <p class="mb-1.5"><a href="#process" class="hover:text-teal">About</a></p>
      <p class="mb-1.5"><a href="#reviews" class="hover:text-teal">Reviews</a></p>
      <p class="mb-3"><a href="tel:0900103183" class="hover:text-teal">Contact</a></p>
      <!-- Staff & Admin Secure Access Button (Only in Footer) -->
      <button type="button" onclick="openStaffModal()" class="inline-flex items-center gap-1.5 text-xs text-teal hover:text-white transition cursor-pointer py-1 border-b border-teal/40 hover:border-white">
        <i data-lucide="lock" class="w-3.5 h-3.5 text-teal"></i>
        <span data-lang-block="am">የሰራተኞች መግቢያ (Staff OS)</span>
        <span data-lang-block="en">Staff &amp; Admin Access</span>
      </button>
    </div>
    <div>
      <p class="text-white font-semibold mb-3">Follow</p>
      <p class="mb-1.5"><a href="https://t.me/meash_cleaning_solution_bot" target="_blank" class="hover:text-teal flex items-center gap-1.5"><i data-lucide="send" class="w-3.5 h-3.5"></i> Telegram Bot</a></p>
      <p class="mb-1.5"><span class="hover:text-teal">Instagram</span></p>
      <p class="mb-1.5"><span class="hover:text-teal">Facebook</span></p>
      <p><span class="hover:text-teal">TikTok</span></p>
    </div>
  </div>
  <div class="max-w-6xl mx-auto px-6 mt-10 pt-6 border-t border-white/10 flex flex-wrap items-center justify-between gap-4 text-xs text-white/40">
    <div>© 2026 Meash Cleaning Solution. All rights reserved.</div>
    <div>
      <button type="button" onclick="openStaffModal()" class="inline-flex items-center gap-1.5 text-white/40 hover:text-white transition cursor-pointer">
        <i data-lucide="shield-check" class="w-3.5 h-3.5 text-teal"></i>
        <span>Staff OS</span>
      </button>
    </div>
  </div>
</footer>

</main>

<!-- ================= STAFF & ADMIN SECURE AUTH MODAL ================= -->
<div id="staff-auth-modal" class="fixed inset-0 z-50 hidden bg-navy/80 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-300" onclick="if(event.target === this) closeStaffModal()">
  <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl p-6 overflow-hidden border border-navy/10 text-navy">
    <!-- Header -->
    <div class="flex items-center justify-between pb-3 border-b border-navy/10">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-teal-light text-teal-dark flex items-center justify-center">
          <i data-lucide="shield-check" class="w-4 h-4"></i>
        </div>
        <div>
          <h3 class="font-serif font-bold text-sm text-navy">የሰራተኞች እና አስተዳዳሪ መግቢያ</h3>
          <p class="text-[11px] text-navy/50">Staff & Admin Access — Meash OS</p>
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
        <p class="text-[11px] text-navy/70 leading-tight">ደህንነቱ የተጠበቀ የአስተዳዳሪ እና ሰራተኞች መግቢያ። እባክዎ የስራ ሚናዎን መርጠው የይለፍ ቃልዎን ያስገቡ።</p>
      </div>

      <form onsubmit="handleStaffLogin(event)" class="space-y-3.5">
        <div>
          <label class="block text-[11px] font-bold text-navy/70 mb-1">የስራ ዘርፍ / ሚና (Staff Role)</label>
          <select id="staff-role-select" class="w-full px-3 py-2.5 rounded-lg border border-navy/20 bg-fog text-navy text-xs font-semibold focus:outline-none focus:border-teal cursor-pointer">
            <option value="owner">👑 1. ዋና ስራ አስኪያጅ (Owner / General Manager)</option>
            <option value="reception">📞 2. ሪሴፕሽን እና ስራ ማስተናገጃ (Reception)</option>
            <option value="cleaner">🧹 3. የፅዳት ሰራተኛ (Cleaner / Technician)</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-bold text-navy/70 mb-1">የይለፍ ቃል (Password)</label>
          <div class="relative">
            <input type="password" id="staff-password-input" required placeholder="••••••••" class="w-full px-3.5 py-2.5 pr-10 rounded-lg border border-navy/20 bg-fog text-navy text-xs focus:outline-none focus:border-teal">
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
      </form>
    </div>
  </div>
</div>

<script>
  // ============================================================
  // ETHIOPIAN CALENDAR UTILITY (Full Bidirectional Converter)
  // ============================================================
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
    const maxDay = (m === 13) ? 6 : 30;
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

  function validateStep3AndContinue() {
    const date = document.getElementById('book-date')?.value;
    const slot = document.getElementById('book-time-slot')?.value;
    if (!date) { alert('እባክዎ ቀን ይምረጡ!'); return; }
    if (!slot) {
      const err = document.getElementById('time-slot-error');
      if (err) err.classList.remove('hidden');
      return;
    }
    showWizardStep(4);
  }

  // ---- Language toggle ----
  function setLang(l){
    document.documentElement.setAttribute('data-lang', l);
    localStorage.setItem('meash_lang', l);
    document.querySelectorAll('.lang-btn').forEach(btn => {
      btn.classList.remove('bg-navy', 'text-white');
    });
    const activeBtn = Array.from(document.querySelectorAll('.lang-btn')).find(b => b.getAttribute('onclick')?.includes(`'${l}'`));
    if (activeBtn) activeBtn.classList.add('bg-navy', 'text-white');
    if (window.lucide) lucide.createIcons();
  }

  (function(){
    const saved = localStorage.getItem('meash_lang');
    if (saved) { setLang(saved); }
    else {
      const browserLang = (navigator.language || '').toLowerCase();
      setLang(browserLang.startsWith('am') ? 'am' : 'both');
    }
  })();

  // ---- Live prices from backend ----
  let servicePrices = { 1: 350, 2: 80, 3: 600, 4: 70 };
  let currency = 'ETB';

  async function loadServicePrices(){
    try {
      const res = await fetch('/api/public/services');
      const data = await res.json();
      const list = Array.isArray(data) ? data : (data.data || []);
      list.forEach(svc => {
        if (svc.id != null && svc.base_price != null) {
          servicePrices[svc.id] = parseFloat(svc.base_price);
        }
      });
    } catch (err) {
      console.warn('Could not load live prices from /api/public/services', err);
    }
    renderPrices();
    updateWizardTotal();
  }

  function formatPrice(v){
    if (v == null || isNaN(v)) return (document.documentElement.getAttribute('data-lang') === 'am') ? 'ዋጋ ለማወቅ ይደውሉ' : 'Call for price';
    return v.toLocaleString() + ' ' + currency;
  }

  function renderPrices(){
    document.querySelectorAll('.svc-price').forEach(el => {
      const id = el.getAttribute('data-svc-price');
      if (servicePrices[id] != null) el.textContent = formatPrice(servicePrices[id]);
    });
    document.querySelectorAll('.wiz-price').forEach(el => {
      const id = el.getAttribute('data-wiz-price');
      if (servicePrices[id] != null) el.textContent = servicePrices[id].toLocaleString() + ' ' + currency + ' / unit';
    });
  }

  // ---- Booking wizard state ----
  const svcMap = { sofa: 1, carpet: 2, mattress: 3, glass: 4 };
  const svcNames = {
    sofa: 'Sofa Cleaning / የሶፋ እጥበት',
    carpet: 'Carpet Cleaning / የምንጣፍ እጥበት',
    mattress: 'Mattress Sanitization / የፍራሽ እጥበት',
    glass: 'Glass & Window Cleaning / የመስታወት እጥበት'
  };

  let selectedSvc = 'sofa';
  let selectedSvcId = 1;
  let wizardQty = 1;

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
  const subPlanDiscounts = { one_time: 0, weekly: 15, biweekly: 10, monthly: 5 };

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

  function showWizardStep(n){
    document.querySelectorAll('.wizard-step').forEach(s => s.classList.add('hidden'));
    const target = document.getElementById('step-' + n);
    if (target) target.classList.remove('hidden');
    document.querySelectorAll('.step-dot').forEach(d => d.classList.remove('text-teal-dark'));
    const dot = document.querySelector(`.step-dot[data-step="${n}"]`);
    if (dot) dot.classList.add('text-teal-dark');
    if (n === 4) updateWizardTotal();
    if (window.lucide) lucide.createIcons();
  }

  function getWebGps(){
    if (!navigator.geolocation) { alert('GPS not supported on this device.'); return; }
    navigator.geolocation.getCurrentPosition((pos) => {
      document.getElementById('book-latitude').value = pos.coords.latitude;
      document.getElementById('book-longitude').value = pos.coords.longitude;
      const feedback = document.getElementById('web-gps-feedback');
      feedback.classList.remove('hidden');
      feedback.className = 'mb-3 p-3 rounded-md bg-teal-light text-teal-dark text-xs font-bold';
      feedback.innerText = 'Location captured (±' + Math.round(pos.coords.accuracy) + 'm)';
    }, () => {
      alert('Could not get location. Please type your address, or call 0900103183.');
    }, { enableHighAccuracy: true, timeout: 10000 });
  }

  async function submitBooking(e){
    e.preventDefault();
    const btn = document.getElementById('btn-submit-booking');
    btn.disabled = true;
    const unitPrice = servicePrices[selectedSvcId] || 0;
    const plan = document.querySelector('input[name="sub_plan"]:checked')?.value || 'one_time';
    const payload = {
      customer_name: document.getElementById('book-name').value,
      customer_phone: document.getElementById('book-phone').value,
      subcity: document.getElementById('book-subcity').value,
      address: document.getElementById('book-address').value + (document.getElementById('book-landmark').value ? ` (${document.getElementById('book-landmark').value})` : ''),
      landmark: document.getElementById('book-landmark').value || null,
      latitude: document.getElementById('book-latitude').value ? parseFloat(document.getElementById('book-latitude').value) : null,
      longitude: document.getElementById('book-longitude').value ? parseFloat(document.getElementById('book-longitude').value) : null,
      appointment_date: document.getElementById('book-date').value,
      appointment_time_slot: document.getElementById('book-time-slot').value,
      subscription_plan: plan,
      notes: document.getElementById('book-notes').value || 'Website booking',
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
        document.getElementById('step-4').classList.add('hidden');
        document.getElementById('step-success').classList.remove('hidden');
        document.getElementById('success-booking-id').innerText = data.order_number || data.booking_id;
        if (window.lucide) lucide.createIcons();
      } else {
        alert('Error: ' + (data.message || 'Could not create booking. Please call 0900103183.'));
      }
    } catch (err) {
      alert('Connection error: ' + err.message + ' — please call 0900103183 directly.');
    } finally {
      btn.disabled = false;
    }
  }

  function resetWizard(){
    document.getElementById('booking-form').reset();
    document.getElementById('book-phone').value = '0900103183';
    document.getElementById('step-success').classList.add('hidden');
    showWizardStep(1);
  }

  let liveTrackMap = null;

  async function trackOrder(e){
    e.preventDefault();
    const q = document.getElementById('track-input').value.trim();
    const box = document.getElementById('track-result');
    box.classList.remove('hidden');
    box.innerHTML = '<div class="p-6 text-center text-navy/60"><div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-teal mr-2"></div> በመፈለግ ላይ...</div>';
    try {
      const res = await fetch(`/api/public/track-order?tracking_id=${encodeURIComponent(q)}`);
      const data = await res.json();
      if (res.ok && data.found && data.order) {
        const ord = data.order;
        const isEnRoute = ord.status === 'on_the_way';
        const isCleaning = ord.status === 'cleaning' || ord.status === 'in_progress';
        const isCompleted = ord.status === 'completed';

        let subBadge = '';
        if (ord.subscription_plan && ord.subscription_plan !== 'one_time') {
          const pNames = { weekly: 'የሳምንት (Weekly - 15% OFF)', biweekly: 'የ2 ሳምንት (Bi-weekly - 10% OFF)', monthly: 'የወር (Monthly - 5% OFF)' };
          subBadge = `<div class="mt-2 text-xs font-bold text-teal-dark bg-teal-light px-3 py-1 rounded-lg inline-flex items-center gap-1.5">
            🔄 ${pNames[ord.subscription_plan] || ord.subscription_plan}
          </div>`;
        }

        let mapHtml = '';
        if (ord.customer_location) {
          mapHtml = `
            <div class="mt-4 pt-4 border-t border-navy/10">
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-extrabold text-navy uppercase tracking-wider flex items-center gap-1.5">
                  <i data-lucide="map-pin" class="w-4 h-4 text-teal"></i> የቀጥታ ካርታ እና የቡድን መገኛ
                </span>
                <span class="text-[11px] font-bold text-teal-dark">${ord.team_assigned || 'ቡድን በመመደብ ላይ'}</span>
              </div>
              <div id="live-tracking-map" class="w-full h-64 rounded-xl border border-navy/15 shadow-inner"></div>
            </div>`;
        }

        let teamInfoHtml = '';
        if (ord.team_location) {
          teamInfoHtml = `
            <div class="mt-3 p-3.5 rounded-xl bg-slate-900 text-white text-xs flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal/20 text-teal flex items-center justify-center text-lg">🚚</div>
                <div>
                  <p class="font-extrabold text-white">${ord.team_location.team_name}</p>
                  <p class="text-[11px] text-teal-light font-semibold">${ord.team_location.vehicle_plate ? 'ታርጋ: ' + ord.team_location.vehicle_plate : 'የሜሽ ጽዳት መኪና'}</p>
                </div>
              </div>
              ${ord.team_location.phone ? `<a href="tel:${ord.team_location.phone}" class="px-3 py-1.5 rounded-lg bg-teal hover:bg-teal-dark text-white font-bold text-xs flex items-center gap-1">📞 ደውል</a>` : ''}
            </div>`;
        }

        box.innerHTML = `
          <div class="bg-white rounded-2xl p-6 border border-navy/10 shadow-lg">
            <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
              <div>
                <span class="text-xs text-navy/40 font-bold uppercase block">የትዕዛዝ ቁጥር</span>
                <span class="font-serif text-lg font-bold text-navy">${ord.order_number}</span>
              </div>
              <span class="text-xs font-extrabold px-3 py-1.5 rounded-full ${isCompleted ? 'bg-emerald-100 text-emerald-800' : (isEnRoute ? 'bg-amber-100 text-amber-800 animate-pulse' : 'bg-teal-light text-teal-dark')}">
                ${isEnRoute ? '🚚 ቡድኑ በመጓዝ ላይ ነው (On The Way)' : (isCleaning ? '🧹 ፅዳት በመከናወን ላይ' : (isCompleted ? '✅ ተጠናቋል' : '📅 ተይዟል (' + ord.status + ')'))}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-3 text-xs py-3 border-y border-navy/10">
              <div>
                <span class="text-navy/50 block">ደንበኛ:</span>
                <span class="font-bold text-navy">${ord.customer_name} (${ord.subcity || ''})</span>
              </div>
              <div>
                <span class="text-navy/50 block mb-0.5">🗓 የቀጠሮ ቀንና ሰዓት:</span>
                <div class="space-y-0.5">
                  <p class="font-extrabold text-teal-dark text-xs">${ord.eth_appointment_date || (ord.appointment_date ? EC.formatEth(EC.toEth(ord.appointment_date).year, EC.toEth(ord.appointment_date).month, EC.toEth(ord.appointment_date).day) : '—')}</p>
                  <p class="text-[10px] text-navy/55 font-semibold">🌐 GC: ${ord.appointment_date || '—'}</p>
                  <p class="text-[11px] font-bold text-navy">
                    ${ord.appointment_time_slot === 'morning' ? '🌅 ጥዋት (2:00–6:00 ጠዋቱ ETH • 8am–12pm)' : (ord.appointment_time_slot === 'afternoon' ? '☀️ ከሰዓት (7:00–11:00 ከሰዓቱ ETH • 1pm–5pm)' : (ord.appointment_time_slot || ''))}
                  </p>
                </div>
              </div>
              <div>
                <span class="text-navy/50 block">ጠቅላላ ክፍያ:</span>
                <span class="font-bold text-teal-dark text-sm">${ord.total.toLocaleString()} ETB</span>
              </div>
              <div>
                <span class="text-navy/50 block">የክፍያ ሁኔታ:</span>
                <span class="font-bold ${ord.payment_status === 'paid' ? 'text-emerald-600' : 'text-amber-600'}">${ord.payment_status === 'paid' ? 'ተከፍሏል' : 'ያልተከፈለ'}</span>
              </div>
            </div>

            ${subBadge}
            ${teamInfoHtml}
            ${mapHtml}
          </div>`;

        if (window.lucide) lucide.createIcons();

        // Initialize Leaflet map if customer location exists
        if (ord.customer_location && window.L) {
          setTimeout(() => {
            const mapContainer = document.getElementById('live-tracking-map');
            if (!mapContainer) return;
            if (liveTrackMap) {
              liveTrackMap.remove();
              liveTrackMap = null;
            }

            const cLat = ord.customer_location.latitude || 9.0108;
            const cLng = ord.customer_location.longitude || 38.7616;

            liveTrackMap = L.map('live-tracking-map').setView([cLat, cLng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
              attribution: '© OpenStreetMap'
            }).addTo(liveTrackMap);

            // Customer marker
            L.marker([cLat, cLng])
              .addTo(liveTrackMap)
              .bindPopup(`<b>📍 የእርስዎ አድራሻ</b><br>${ord.customer_name}`)
              .openPopup();

            // Cleaner team marker if GPS is active
            if (ord.team_location && ord.team_location.latitude && ord.team_location.longitude) {
              const tLat = ord.team_location.latitude;
              const tLng = ord.team_location.longitude;
              L.marker([tLat, tLng])
                .addTo(liveTrackMap)
                .bindPopup(`<b>🚚 የሜሽ ጽዳት ቡድን</b><br>${ord.team_location.team_name}<br>ሁኔታ: በመጓዝ ላይ`)
                .openPopup();

              // Fit bounds to see both customer and cleaner
              const bounds = L.latLngBounds([[cLat, cLng], [tLat, tLng]]);
              liveTrackMap.fitBounds(bounds, { padding: [30, 30] });
            }
          }, 200);
        }

      } else {
        box.innerHTML = `<div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
          ${data.message || 'ይቅርታ፣ በዚህ ቁጥር ወይም ስልክ የተያዘ ትዕዛዝ አልተገኘም። እባክዎ በትክክል መጻፉን ያረጋግጡ።'}
        </div>`;
      }
    } catch (err) {
      box.innerHTML = '<div class="p-4 rounded-xl bg-red-50 text-red-700 text-xs">የኔትወርክ ግንኙነት ስህተት አጋጥሟል። እባክዎ በ 0900103183 በቀጥታ ይደውሉ።</div>';
    }
  }

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
    const roleSelect = document.getElementById('staff-role-select');
    const pwdInput = document.getElementById('staff-password-input');
    const errBox = document.getElementById('staff-login-error');
    const errText = document.getElementById('staff-login-error-text');
    const btn = document.getElementById('staff-login-btn');
    const btnText = document.getElementById('staff-login-btn-text');

    if (!pwdInput.value.trim()) {
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
          login: roleSelect.value,
          password: pwdInput.value,
        })
      });

      const data = await res.json();

      if (res.ok && data.token) {
        localStorage.setItem('meash_token', data.token);
        localStorage.setItem('meash_user', JSON.stringify(data.user));
        btnText.innerText = 'ትክክል ነው! ወደ ሲስተሙ በማስገባት ላይ...';
        btn.classList.remove('bg-navy', 'hover:bg-teal-dark');
        btn.classList.add('bg-teal', 'text-white');
        setTimeout(() => {
          window.location.href = '/app';
        }, 400);
      } else {
        errBox.classList.remove('hidden');
        errText.innerText = data.message || 'የተሳሳተ የይለፍ ቃል ነው፤ እባክዎ በትክክል ያስገቡ!';
        pwdInput.classList.add('border-red-500');
        btn.disabled = false;
        btnText.innerText = 'ግባ (Access Admin)';
        pwdInput.focus();
        pwdInput.select();
      }
    } catch (err) {
      errBox.classList.remove('hidden');
      errText.innerText = 'የሰርቨር ግንኙነት ችግር ተፈጥሯል፤ እባክዎ እንደገና ይሞክሩ።';
      btn.disabled = false;
      btnText.innerText = 'ግባ (Login)';
    }
  }

  async function quickStaffLogin(email) {
    const errBox = document.getElementById('staff-login-error');
    const errText = document.getElementById('staff-login-error-text');
    if (errBox) errBox.classList.add('hidden');

    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ login: email, password: 'password' })
      });
      const data = await res.json();

      if (res.ok && data.token) {
        localStorage.setItem('meash_token', data.token);
        localStorage.setItem('meash_user', JSON.stringify(data.user));
        closeStaffModal();
        // Brief loading flash then redirect
        document.body.insertAdjacentHTML('afterbegin', `
          <div id="quick-login-flash" class="fixed inset-0 z-[999] flex items-center justify-center bg-navy/90 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-3 text-white">
              <div class="w-12 h-12 rounded-2xl bg-teal/20 flex items-center justify-center animate-bounce text-2xl">
                ${email.startsWith('owner') ? '👑' : email.startsWith('reception') ? '📞' : email.startsWith('cleaner') ? '🧹' : '💼'}
              </div>
              <p class="text-sm font-bold">ወደ ሲስተሙ እየገቡ ነው...</p>
              <div class="w-32 h-1 bg-white/10 rounded-full overflow-hidden"><div class="h-full bg-teal animate-pulse" style="width:70%"></div></div>
            </div>
          </div>`);
        setTimeout(() => { window.location.href = '/app'; }, 600);
      } else {
        if (errBox) { errBox.classList.remove('hidden'); errText.innerText = data.message || 'መግባት አልተቻለም!'; }
      }
    } catch (err) {
      if (errBox) { errBox.classList.remove('hidden'); errText.innerText = 'የሰርቨር ግንኙነት ችግር!'; }
    }
  }

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeStaffModal();
  });

  // ---- Theme (Dark/Light Mode) Engine ----
  function initTheme() {
    const saved = localStorage.getItem('meash_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    applyTheme(saved);
  }

  function applyTheme(theme) {
    const icon = document.getElementById('theme-icon');
    const btn = document.getElementById('theme-toggle-btn');
    if (theme === 'dark') {
      document.documentElement.classList.add('dark');
      if (icon) {
        icon.setAttribute('data-lucide', 'sun');
      }
      if (btn) {
        btn.classList.add('text-amber-400', 'border-slate-700');
        btn.classList.remove('text-navy', 'border-navy/15');
      }
    } else {
      document.documentElement.classList.remove('dark');
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
    initTheme();
    if (window.lucide) lucide.createIcons();
    loadServicePrices();
    initEthDatePicker();
  });
</script>
</body>
</html>