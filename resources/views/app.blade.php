<!DOCTYPE html>
<html lang="am" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>ሜሽ የፅዳት አገልግሎት | የንግድ ስራ መምሪያ ሲስተም (Mesh OS)</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0F172A">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Mesh OS">
    <link rel="apple-touch-icon" href="/assets/icon-192.png">

    <!-- Leaflet.js CSS & Engine (In-App Interactive Ride Map) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        meash: {
                            navy: '#0F172A',
                            card: '#1E293B',
                            cyan: '#06B6D4',
                            blue: '#0284C7',
                            emerald: '#10B981',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Engines -->
    <script src="/js/meash-i18n.js"></script>
    <script src="/js/meash-offline.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Noto+Sans+Ethiopic:wght@400;600;700&display=swap');
        :root {
            --sat: env(safe-area-inset-top, 0px);
            --sab: env(safe-area-inset-bottom, 0px);
        }
        body {
            font-family: 'Plus Jakarta Sans', 'Noto Sans Ethiopic', sans-serif;
            -webkit-tap-highlight-color: transparent;
            overscroll-behavior-y: none;
        }
        #mobile-bottom-nav {
            padding-bottom: max(0.5rem, var(--sab));
            height: calc(4rem + var(--sab));
        }
        .leaflet-container {
            font-family: inherit;
            background: #0f172a;
        }
    </style>
</head>
<body class="h-full text-slate-100 flex flex-col bg-slate-950 antialiased overflow-hidden">

    <!-- 0. SECURE STAFF LOGIN SCREEN (Displayed when unauthenticated) -->
    <div id="app-login-screen" class="hidden fixed inset-0 z-50 bg-slate-950 flex items-center justify-center p-4 overflow-y-auto">
        <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative">
            <div class="text-center mb-6">
                <img src="/logo.jpg" alt="Meash Logo" class="w-16 h-16 rounded-2xl mx-auto mb-3 object-cover border border-cyan-500/40 shadow-lg shadow-cyan-500/20">
                <h2 class="text-xl font-extrabold text-white tracking-tight">ሜሽ የፅዳት አገልግሎት</h2>
                <p class="text-xs text-cyan-400 font-bold uppercase tracking-wider mt-1">የንግድ ስራ መምሪያ ሲስተም (Mesh OS)</p>
                <p class="text-xs text-slate-400 mt-2">የሰራተኞች እና የአስተዳዳሪ ደህንነቱ የተጠበቀ መግቢያ</p>
            </div>

            <!-- Error Banner -->
            <div id="login-error-banner" class="hidden mb-4 p-3 rounded-xl bg-red-950/80 border border-red-500/50 text-red-200 text-xs flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-400 shrink-0"></i>
                <span id="login-error-text">የተሳሳተ መረጃ አስገብተዋል</span>
            </div>

            <form onsubmit="handleAppLoginSubmit(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">የተጠቃሚ ስም ወይም ስልክ ቁጥር</label>
                    <div class="relative">
                        <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="app-login-input" required placeholder="ስምዎን ያስገቡ (ለምሳሌ፦ Meash General Manager ወይም 0911000001)" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">የይለፍ ቃል (Password)</label>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" id="app-password-input" required placeholder="••••••••" class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs focus:outline-none focus:border-cyan-500">
                        <button type="button" onclick="togglePasswordVisibility('app-password-input')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- 3 Core Roles hint badge -->
                <div class="pt-2 pb-1">
                    <p class="text-[11px] text-slate-400 mb-1.5 font-bold uppercase tracking-wider text-center">3ቱ የስርዓቱ ዋና የስራ ድርሻዎች (Core Roles)፦</p>
                    <div class="grid grid-cols-3 gap-1.5 text-[10px] text-slate-300 font-semibold">
                        <button type="button" onclick="fillTestAccount('Meash General Manager')" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg p-1.5 text-center cursor-pointer transition">
                            <span class="block text-cyan-400 text-xs">👑</span>
                            <span class="block truncate">owner</span>
                            <span class="block text-[8px] text-slate-400">ስራ አስኪያጅ</span>
                        </button>
                        <button type="button" onclick="fillTestAccount('Bethlehem Tadesse')" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg p-1.5 text-center cursor-pointer transition">
                            <span class="block text-cyan-400 text-xs">📞</span>
                            <span class="block truncate">reception</span>
                            <span class="block text-[8px] text-slate-400">ሪሴፕሽን</span>
                        </button>
                        <button type="button" onclick="fillTestAccount('Solomon Kebede')" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-lg p-1.5 text-center cursor-pointer transition">
                            <span class="block text-cyan-400 text-xs">🧹</span>
                            <span class="block truncate">cleaner</span>
                            <span class="block text-[8px] text-slate-400">ጽዳት ሰራተኛ</span>
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2 text-center">↑ ስምዎን ወይም የስራ ድርሻዎን ያስገቡ • የይለፍ ቃል፦ password</p>
                </div>

                <button type="submit" id="btn-app-login" class="w-full mt-3 py-3 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-cyan-500/20 flex items-center justify-center gap-2 transition-all">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span id="btn-app-login-text">ወደ ሲስተም ግባ (Login to Mesh OS)</span>
                </button>
            </form>

            <div class="mt-6 pt-4 border-t border-slate-800 text-center">
                <a href="/" class="text-xs text-slate-400 hover:text-cyan-400 font-semibold flex items-center justify-center gap-1">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>ወደ ደንበኞች ድረ-ገጽ ተመለስ</span>
                </a>
            </div>
        </div>
    </div>

    <!-- MAIN AUTHENTICATED SYSTEM WRAPPER -->
    <div id="app-authenticated-wrapper" class="h-full flex flex-col">

    <!-- TOP MODERN ENTERPRISE HEADER (Phone-First & Responsive) -->
    <header class="h-14 sm:h-16 bg-slate-900/95 backdrop-blur-md border-b border-slate-800 shrink-0 px-2.5 sm:px-6 flex items-center justify-between z-40 transition-all">
        <!-- 1. Left: Mobile Menu Trigger + Brand Identity -->
        <div class="flex items-center gap-1.5 sm:gap-2">
            <!-- Mobile Sidebar Hamburger Button -->
            <button type="button" onclick="toggleMobileSidebar()" class="md:hidden p-1.5 rounded-xl bg-slate-800 border border-slate-700 text-slate-300 hover:text-white transition active:scale-95 cursor-pointer" title="ሳይድባር ሜኑ (Open Sidebar)">
                <i data-lucide="menu" class="w-4 h-4"></i>
            </button>
            <img src="/logo.jpg" alt="Meash Logo" class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl object-cover border border-cyan-500/30 shadow-md shrink-0">
            <div>
                <div class="flex items-center gap-1">
                    <span class="text-xs sm:text-base font-black text-white tracking-tight">ሜሽ</span>
                    <span class="text-[8px] sm:text-[9px] font-extrabold uppercase px-1 py-0.2 rounded bg-cyan-950 text-cyan-400 border border-cyan-800/80">OS</span>
                </div>
                <div class="flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" id="sync-indicator-dot"></span>
                    <span class="text-[9px] sm:text-[10px] text-slate-400 font-semibold" id="sync-status-text">ኦንላይን</span>
                </div>
            </div>
        </div>

        <!-- 2. Center: Elegant Compact Role Switcher -->
        <div class="relative">
            <button type="button" onclick="toggleRoleDropdown(event)" id="role-dropdown-btn" class="flex items-center gap-1.5 px-2 py-1.5 sm:px-3.5 sm:py-2 rounded-xl bg-slate-800 border border-slate-700 hover:border-cyan-500 text-white font-bold text-xs shadow-sm transition cursor-pointer">
                <span id="header-role-badge">👑 owner</span>
                <i data-lucide="chevron-down" id="role-chevron" class="w-3.5 h-3.5 text-slate-400 transition-transform"></i>
            </button>

            <!-- Floating Role Menu -->
            <div id="role-dropdown-menu" class="hidden absolute left-1/2 -translate-x-1/2 mt-2 w-56 sm:w-60 bg-slate-900 border border-slate-700 rounded-2xl p-1.5 shadow-2xl z-50 space-y-1">
                <div class="px-3 py-1.5 border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    የስራ ዘርፍ ይቀይሩ (Switch Role)
                </div>
                <button type="button" onclick="switchRoleAccount('Meash General Manager', 'owner'); closeRoleDropdown();" class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-slate-800 text-xs font-bold text-slate-200 hover:text-white transition group text-left cursor-pointer">
                    <span class="flex items-center gap-2">
                        <span class="text-base">👑</span>
                        <span>1. ዋና ስራ አስኪያጅ</span>
                    </span>
                    <span class="text-[10px] font-mono text-cyan-400 bg-cyan-950/60 px-1.5 py-0.5 rounded border border-cyan-900">owner</span>
                </button>
                <button type="button" onclick="switchRoleAccount('Bethlehem Tadesse', 'reception'); closeRoleDropdown();" class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-slate-800 text-xs font-bold text-slate-200 hover:text-white transition group text-left cursor-pointer">
                    <span class="flex items-center gap-2">
                        <span class="text-base">📞</span>
                        <span>2. ሪሴፕሽን</span>
                    </span>
                    <span class="text-[10px] font-mono text-cyan-400 bg-cyan-950/60 px-1.5 py-0.5 rounded border border-cyan-900">reception</span>
                </button>
                <button type="button" onclick="switchRoleAccount('Solomon Kebede', 'cleaner'); closeRoleDropdown();" class="w-full flex items-center justify-between p-2 rounded-xl hover:bg-slate-800 text-xs font-bold text-slate-200 hover:text-white transition group text-left cursor-pointer">
                    <span class="flex items-center gap-2">
                        <span class="text-base">🧹</span>
                        <span>3. ጽዳት ሰራተኛ</span>
                    </span>
                    <span class="text-[10px] font-mono text-cyan-400 bg-cyan-950/60 px-1.5 py-0.5 rounded border border-cyan-900">cleaner</span>
                </button>
            </div>
        </div>

        <!-- 3. Right: Compact Language + Website + Logout -->
        <div class="flex items-center gap-1 sm:gap-2">
            <!-- Language Toggle (Compact) -->
            <div class="flex items-center bg-slate-800 rounded-lg p-0.5 border border-slate-700 text-[10px] sm:text-[11px]">
                <button type="button" onclick="window.meashI18n.setLanguage('en')" class="px-1.5 py-0.5 rounded font-bold hover:bg-slate-700 text-slate-400" id="app-lang-en">EN</button>
                <button type="button" onclick="window.meashI18n.setLanguage('am')" class="px-1.5 py-0.5 rounded font-bold hover:bg-slate-700 text-cyan-300" id="app-lang-am">አማ</button>
            </div>

            <!-- Website link (Desktop only to save mobile space) -->
            <a href="/" target="_blank" title="የደንበኞች ድረ-ገጽ እይ" class="hidden sm:flex p-2 text-slate-400 hover:text-cyan-400 hover:bg-slate-800/60 rounded-xl transition-colors">
                <i data-lucide="globe" class="w-4 h-4"></i>
            </a>

            <!-- Clean Logout Button -->
            <button type="button" onclick="logoutUser()" title="ውጣ (Logout)" class="p-1.5 sm:p-2 text-red-400 hover:text-red-300 hover:bg-red-950/40 rounded-xl transition-colors cursor-pointer flex items-center gap-1">
                <i data-lucide="log-out" class="w-4 h-4"></i>
                <span class="hidden md:inline text-xs font-bold">ውጣ</span>
            </button>
        </div>
    </header>

    <!-- MOBILE SIDEBAR SLIDE-OVER DRAWER (Android / iPhone) -->
    <div id="mobile-sidebar-drawer" class="fixed inset-0 z-50 hidden transition-all">
        <div class="fixed inset-0 bg-black/75 backdrop-blur-sm transition-opacity cursor-pointer" onclick="toggleMobileSidebar()"></div>
        <aside class="fixed inset-y-0 left-0 w-72 max-w-[85vw] bg-slate-900 border-r border-slate-800 p-4 flex flex-col z-10 shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                <div class="flex items-center gap-2">
                    <img src="/logo.jpg" alt="Logo" class="w-7 h-7 rounded-lg object-cover">
                    <span class="font-black text-white text-sm">ሜሽ OS ሙሉ ሜኑ</span>
                </div>
                <button type="button" onclick="toggleMobileSidebar()" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 cursor-pointer">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <nav class="space-y-1 flex-1 overflow-y-auto" id="mobile-drawer-nav">
                <!-- Navigation links populated by role -->
            </nav>
            <div class="pt-3 border-t border-slate-800/80 text-xs space-y-2 mt-auto">
                <button onclick="promptPwaInstall(); toggleMobileSidebar();" class="w-full py-2 bg-cyan-950/60 border border-cyan-500/30 text-cyan-300 font-bold rounded-xl flex items-center justify-center gap-2 cursor-pointer">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>መተግበሪያውን ጫን (PWA)</span>
                </button>
            </div>
        </aside>
    </div>

    <!-- APP BODY -->
    <div class="flex-1 flex overflow-hidden">
        <!-- SIDEBAR NAVIGATION (Desktop / Tablet) -->
        <aside class="hidden md:flex flex-col w-64 bg-slate-900/90 border-r border-slate-800 p-4 shrink-0 overflow-y-auto" id="main-sidebar">
            <nav class="space-y-1.5 flex-1" id="nav-items-container">
                <!-- Navigation links populated by role -->
            </nav>

            <!-- Bottom: Ethiopian Date + GC + Time + PWA -->
            <div class="pt-4 border-t border-slate-800/80 text-xs space-y-1">
                <div id="sidebar-eth-date" class="space-y-0.5">
                    <p class="text-cyan-400 font-bold text-xs" id="sidebar-eth-date-main">— ዓ.ም</p>
                    <p class="text-slate-500 font-semibold text-[10px]" id="sidebar-gc-date-sub">—</p>
                    <p class="text-slate-600 font-mono text-[10px]">
                        <span id="sidebar-eth-time">—</span>
                        <span class="text-slate-700 mx-1">•</span>
                        <span id="sidebar-gc-time">—</span>
                    </p>
                </div>
                <button onclick="promptPwaInstall()" id="btn-pwa-install" class="w-full py-2 bg-cyan-950/60 hover:bg-cyan-900/60 border border-cyan-500/30 text-cyan-300 text-xs font-bold rounded-xl flex items-center justify-center gap-2">
                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                    <span>መተግበሪያውን ጫን (PWA)</span>
                </button>
            </div>
        </aside>

        <!-- MAIN VIEWPORT CONTAINER -->
        <main class="flex-1 overflow-y-auto bg-slate-950 p-4 sm:p-6 lg:p-8" id="app-viewport">
            <!-- Dynamic Role Screen rendered here -->
        </main>
    </div>

    <!-- MOBILE BOTTOM NAVIGATION (Android / iPhone) -->
    <nav class="md:hidden h-16 bg-slate-900 border-t border-slate-800 flex items-center justify-around px-2 shrink-0 z-40" id="mobile-bottom-nav">
        <!-- Filled based on active role -->
    </nav>

    <!-- CONFLICT RESOLUTION MODAL -->
    <div id="conflict-modal" class="hidden fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-amber-500/50 rounded-3xl max-w-lg w-full p-6 shadow-2xl">
            <div class="flex items-center gap-3 mb-4 text-amber-400">
                <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                <h3 class="text-lg font-bold text-white">የመረጃ ማመሳሰል ግጭት ተፈጥሯል</h3>
            </div>
            <p class="text-xs text-slate-300 mb-4">
                ኦፍላይን የነበረ መረጃ ከሰርቨሩ ጋር ልዩነት ፈጥሯል። የትኛው መረጃ ተቀባይነት እንዲያገኝ ይፈልጋሉ?
            </p>
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="p-3 rounded-xl bg-slate-800 border border-slate-700">
                    <span class="text-[10px] text-cyan-400 font-bold uppercase block mb-1">የአካባቢው (ኦፍላይን) መረጃ</span>
                    <p class="text-xs font-bold text-white" id="conflict-local-val">Status: Completed</p>
                </div>
                <div class="p-3 rounded-xl bg-slate-800 border border-slate-700">
                    <span class="text-[10px] text-amber-400 font-bold uppercase block mb-1">የሰርቨሩ መረጃ</span>
                    <p class="text-xs font-bold text-white" id="conflict-server-val">Status: Cancelled</p>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="resolveCurrentConflict('server_wins')" class="px-4 py-2 bg-slate-800 text-slate-200 text-xs font-bold rounded-xl border border-slate-700">የሰርቨሩን አስቀምጥ</button>
                <button onclick="resolveCurrentConflict('client_wins')" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded-xl">የኦፍላይኑን ተግብር</button>
            </div>
        </div>
    </div>

    <!-- MODALS: Create Order, Log Visit, Record Expense, etc. -->
    <div id="generic-modal-container"></div>
    </div> <!-- Close app-authenticated-wrapper -->

    <!-- APP SCRIPT LOGIC -->
    <script>
        // ============================================================
        // ETHIOPIAN CALENDAR UTILITY
        // ============================================================
        const EC = {
            months: ['መስከረም','ጥቅምት','ኅዳር','ታኅሣሥ','ጥር','የካቲት','መጋቢት','ሚያዝያ','ግንቦት','ሰኔ','ሐምሌ','ነሐሴ','ጳጉሜ'],
            toEth(gcDate) {
                if (!gcDate) return { year: 2019, month: 1, day: 1 };
                let d;
                if (typeof gcDate === 'string') {
                    const cleanDate = gcDate.split('T')[0].split(' ')[0];
                    const parts = cleanDate.split('-');
                    if (parts.length === 3) {
                        d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                    } else {
                        d = new Date(gcDate);
                    }
                } else if (gcDate instanceof Date) {
                    d = gcDate;
                } else {
                    d = new Date();
                }

                if (isNaN(d.getTime())) return { year: 2019, month: 1, day: 1 };

                const gy = d.getFullYear();
                const gm = d.getMonth() + 1;
                const gd = d.getDate();

                const isGLeap = (gy % 4 === 0 && (gy % 100 !== 0 || gy % 400 === 0));
                const gDays = [0, 31, isGLeap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

                let dayOfYear = gd;
                for (let i = 1; i < gm; i++) {
                    dayOfYear += gDays[i];
                }

                // Ethiopian new year is Sept 11 (or Sept 12 before Gregorian leap year)
                const isNextGLeap = ((gy + 1) % 4 === 0 && ((gy + 1) % 100 !== 0 || (gy + 1) % 400 === 0));
                const newYearDay = isGLeap ? 12 : 11;
                const newYearDayOfYear = 243 + (isGLeap ? 1 : 0) + newYearDay; // Sept 11 is 254 (non-leap) or 256 (leap)

                let ey, ethDays;
                if (dayOfYear >= newYearDayOfYear) {
                    ey = gy - 7;
                    ethDays = dayOfYear - newYearDayOfYear + 1;
                } else {
                    ey = gy - 8;
                    const prevGLeap = ((gy - 1) % 4 === 0 && ((gy - 1) % 100 !== 0 || (gy - 1) % 400 === 0));
                    const prevTotalDays = 365 + (prevGLeap ? 1 : 0);
                    const prevNewYearDay = prevGLeap ? 12 : 11;
                    const prevNewYearDayOfYear = 243 + (prevGLeap ? 1 : 0) + prevNewYearDay;
                    ethDays = (prevTotalDays - prevNewYearDayOfYear) + dayOfYear + 1;
                }

                let em = Math.ceil(ethDays / 30);
                let ed = ethDays - ((em - 1) * 30);
                if (em > 13) em = 13;
                if (ed <= 0) ed = 1;

                return { year: ey, month: em, day: ed };
            },
            formatEth(arg1, arg2, arg3) {
                try {
                    let y, m, d;
                    if (arg2 !== undefined && arg3 !== undefined) {
                        y = arg1; m = arg2; d = arg3;
                    } else if (arg1 && typeof arg1 === 'object' && arg1.year) {
                        y = arg1.year; m = arg1.month; d = arg1.day;
                    } else {
                        const e = this.toEth(arg1);
                        y = e.year; m = e.month; d = e.day;
                    }
                    const mName = this.months[m - 1] || 'መስከረም';
                    return `${d} ${mName} ${y} ዓ.ም`;
                } catch {
                    return arg1 || '—';
                }
            },
            formatBoth(gcDate) {
                if (!gcDate) return '—';
                try {
                    const eth = this.formatEth(gcDate);
                    const d = new Date(gcDate + 'T00:00:00');
                    const gc = d.toLocaleDateString('en-GB', { day:'numeric', month:'short', year:'numeric' });
                    return `${eth} <span class="text-slate-400 font-normal text-[10px]">(${gc})</span>`;
                } catch { return gcDate; }
            },
            toEthTime(hours24, minutes) {
                let ethH = (hours24 + 18) % 12;
                if (ethH === 0) ethH = 12;
                const min = String(minutes || 0).padStart(2,'0');
                const period = hours24 < 6 ? 'ለሊቱ' : hours24 < 12 ? 'ጠዋቱ' : hours24 < 18 ? 'ከሰዓቱ' : 'ሌሊቱ';
                return `${ethH}:${min} ${period}`;
            },
            nowFormatted() {
                const now = new Date();
                const eth = this.formatEth(now);
                const timeGC = now.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit' });
                const timeETH = this.toEthTime(now.getHours(), now.getMinutes());
                return { eth, timeGC, timeETH };
            }
        };

        // Helper: format any date string showing Ethiopian primary + GC secondary
        function fmtDate(d) { return d ? EC.formatBoth(d) : '—'; }
        function fmtDateEth(d) { return d ? EC.formatEth(d) : '—'; }

        // Helper: format time slot in both Ethiopian and Gregorian times
        function formatTimeSlot(slot) {
            if (!slot) return '—';
            const s = String(slot).toLowerCase();
            if (s.includes('morning') || s.includes('ጥዋት') || s.includes('ጠዋት')) {
                return '🌅 ጥዋት (2:00–6:00 ጠዋቱ ETH • 8am–12pm GC)';
            }
            if (s.includes('afternoon') || s.includes('ከሰዓት')) {
                return '☀️ ከሰዓት (7:00–11:00 ከሰዓቱ ETH • 1pm–5pm GC)';
            }
            return slot;
        }

        function updateSidebarClock() {
            const elMain = document.getElementById('sidebar-eth-date-main');
            const elSub = document.getElementById('sidebar-gc-date-sub');
            const elEthTime = document.getElementById('sidebar-eth-time');
            const elGcTime = document.getElementById('sidebar-gc-time');
            if (!elMain) return;

            const now = new Date();
            const ethStr = EC.formatEth(now);
            const gcStr = now.toLocaleDateString('en-GB', { weekday:'short', day:'numeric', month:'short', year:'numeric' });
            const ethTime = EC.toEthTime(now.getHours(), now.getMinutes());
            const gcTime = now.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit' });

            elMain.textContent = '🗓 ' + ethStr;
            if (elSub) elSub.textContent = '🌐 GC: ' + gcStr;
            if (elEthTime) elEthTime.textContent = '🕐 ' + ethTime;
            if (elGcTime) elGcTime.textContent = 'GC ' + gcTime;
        }

        function startSidebarLiveClock() {
            updateSidebarClock();
            setInterval(updateSidebarClock, 30000);
        }

        // ============================================================
        // STATE
        // ============================================================
        let currentUser = {
            id: 1,
            name: 'Meash General Manager',
            role: 'owner',
            phone: '0970075550',
        };
        let activeTab = 'dashboard';
        let deferredPwaPrompt = null;
        let activeConflict = null;

        function togglePasswordVisibility(id) {
            const input = document.getElementById(id);
            if (input) {
                input.type = input.type === 'password' ? 'text' : 'password';
            }
        }

        function fillTestAccount(email) {
            const loginInput = document.getElementById('app-login-input');
            const pwdInput = document.getElementById('app-password-input');
            if (loginInput) loginInput.value = email;
            if (pwdInput) {
                pwdInput.value = 'password';
                pwdInput.focus();
            }
        }

        async function autoLoginAs(email, role) {
            const loginInput = document.getElementById('app-login-input');
            const pwdInput = document.getElementById('app-password-input');
            if (loginInput) loginInput.value = email;
            if (pwdInput) pwdInput.value = 'password';
            
            const btn = document.getElementById('btn-app-login');
            const btnText = document.getElementById('btn-app-login-text');
            const errBanner = document.getElementById('login-error-banner');
            if (errBanner) errBanner.classList.add('hidden');
            if (btnText) btnText.innerText = 'በማረጋገጥ ላይ...';

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
                    currentUser = Object.assign(currentUser, data.user);
                    checkAuthStatus();
                    await initApp();
                } else {
                    alert('መግባት አልተቻለም: ' + ((data && data.message) ? data.message : 'የተሳሳተ መረጃ'));
                }
            } catch (err) {
                console.error(err);
                alert('የሰርቨር ግንኙነት ችግር አጋጥሟል::');
            } finally {
                if (btnText) btnText.innerText = 'ወደ ሲስተም ግባ (Login to Mesh OS)';
            }
        }

        async function switchRoleAccount(email, role) {
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
                    currentUser = Object.assign(currentUser, data.user);
                }
            } catch (e) {
                console.warn('Seamless role switch fallback:', e);
            }
            
            currentUser.role = role || currentUser.role;
            await switchRole(currentUser.role);
        }

        function checkAuthStatus() {
            const token = localStorage.getItem('meash_token');
            const loginScreen = document.getElementById('app-login-screen');
            const appWrapper = document.getElementById('app-authenticated-wrapper');
            
            if (!token) {
                if (loginScreen) loginScreen.classList.remove('hidden');
                if (appWrapper) appWrapper.classList.add('hidden');
                return false;
            } else {
                if (loginScreen) loginScreen.classList.add('hidden');
                if (appWrapper) appWrapper.classList.remove('hidden');
                return true;
            }
        }

        async function handleAppLoginSubmit(e) {
            e.preventDefault();
            const loginInput = document.getElementById('app-login-input').value.trim();
            const pwdInput = document.getElementById('app-password-input').value;
            const btn = document.getElementById('btn-app-login');
            const btnText = document.getElementById('btn-app-login-text');
            const errBanner = document.getElementById('login-error-banner');
            const errText = document.getElementById('login-error-text');

            if (errBanner) errBanner.classList.add('hidden');
            if (btn) btn.disabled = true;
            if (btnText) btnText.innerText = 'በማረጋገጥ ላይ...';

            try {
                const res = await fetch('/api/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ login: loginInput, password: pwdInput })
                });
                const data = await res.json();
                if (res.ok && data.token) {
                    localStorage.setItem('meash_token', data.token);
                    localStorage.setItem('meash_user', JSON.stringify(data.user));
                    currentUser = Object.assign(currentUser, data.user);
                    checkAuthStatus();
                    initApp();
                } else {
                    const msg = (data && data.message) ? data.message : 'የተሳሳተ ኢሜይል/ስልክ ወይም የይለፍ ቃል አስገብተዋል::';
                    if (errText) errText.innerText = msg;
                    if (errBanner) errBanner.classList.remove('hidden');
                }
            } catch (err) {
                if (errText) errText.innerText = 'የኔትወርክ ወይም የሰርቨር ችግር አጋጥሟል:: እባክዎ እንደገና ይሞክሩ::';
                if (errBanner) errBanner.classList.remove('hidden');
            } finally {
                if (btn) btn.disabled = false;
                if (btnText) btnText.innerText = 'ወደ ሲስተም ግባ (Login to Mesh OS)';
            }
        }

        // Restore logged in user data if present
        try {
            const storedUser = localStorage.getItem('meash_user');
            if (storedUser) {
                const u = JSON.parse(storedUser);
                currentUser.id = u.id || currentUser.id;
                currentUser.name = u.name || currentUser.name;
                currentUser.role = u.role || currentUser.role;
                currentUser.phone = u.phone || currentUser.phone;
            }
        } catch(e) {}

        function logoutUser() {
            localStorage.removeItem('meash_token');
            localStorage.removeItem('meash_user');
            checkAuthStatus();
        }

        async function getAuthToken() {
            return localStorage.getItem('meash_token') || null;
        }

        async function apiFetch(url, options = {}) {
            const token = await getAuthToken();
            const headers = Object.assign({
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }, options.headers || {});

            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            let res = await fetch(url, { ...options, headers });

            if (res.status === 401) {
                logoutUser();
            }

            return res;
        }

        // Role configurations with bilingual support
        const roleNavs = {
            owner: [
                { id: 'dashboard', label_en: 'Executive BI', label_am: 'የአመራር ዳሽቦርድ', icon: 'bar-chart-3' },
                { id: 'orders', label_en: 'All Orders', label_am: 'ሁሉም ትዕዛዞች', icon: 'clipboard-list' },
                { id: 'subscriptions', label_en: 'Subscriptions', label_am: 'የደንበኝነት እቅዶች', icon: 'repeat' },
                { id: 'customers', label_en: 'Customers', label_am: 'ደንበኞች', icon: 'users' },
                { id: 'teams', label_en: 'Staff & Teams', label_am: 'ሰራተኞች እና ቡድኖች', icon: 'users' },
                { id: 'sales', label_en: 'Sales CRM', label_am: 'የውጭ ሽያጭ CRM', icon: 'trending-up' },
                { id: 'finance', label_en: 'Finance & Expenses', label_am: 'ፋይናንስ እና ወጪዎች', icon: 'wallet' },
                { id: 'care', label_en: 'Customer Care', label_am: 'የደንበኞች እንክብካቤ', icon: 'heart-handshake' },
                { id: 'campaigns', label_en: 'ማስታወቂያ እና ፕሮሞሽን', label_am: 'የማስታወቂያ ዘመቻ', icon: 'send' },
                { id: 'settings', label_en: 'System Settings', label_am: 'የሲስተም ቅንብሮች', icon: 'settings' },
            ],
            reception: [
                { id: 'reception_desk', label_en: 'Reception Desk', label_am: 'ሪሴፕሽን ዴስክ', icon: 'layout-dashboard' },
                { id: 'orders', label_en: 'Orders & Booking', label_am: 'ትዕዛዝ እና ቀጠሮ', icon: 'clipboard-list' },
                { id: 'subscriptions', label_en: 'Subscriptions', label_am: 'የደንበኝነት እቅዶች', icon: 'repeat' },
                { id: 'calendar', label_en: 'Appointments', label_am: 'የቀጠሮዎች ካላንደር', icon: 'calendar' },
                { id: 'customers', label_en: 'Customer CRM', label_am: 'የደንበኞች መረጃ', icon: 'users' },
                { id: 'care', label_en: 'Follow-up & ክፍት ቅሬታዎች', label_am: 'ክትትል እና ቅሬታዎች', icon: 'phone-call' },
                { id: 'finance', label_en: 'Payments & Revenue', label_am: 'ክፍያዎች እና ገቢ', icon: 'credit-card' },
            ],
            cleaner: [
                { id: 'cleaner_jobs', label_en: "የዛሬ ስራዎች", label_am: 'የዛሬ ስራዎች', icon: 'check-square' },
                { id: 'cleaner_upcoming', label_en: 'Upcoming', label_am: 'ቀጣይ ስራዎች', icon: 'calendar' },
                { id: 'cleaner_history', label_en: 'Completed', label_am: 'የተጠናቀቁ ስራዎች', icon: 'clock' },
            ],
            sales: [
                { id: 'sales_pipeline', label_en: 'Sales Pipeline', label_am: 'የሽያጭ ሂደት', icon: 'kanban' },
                { id: 'sales_orgs', label_en: 'Organizations', label_am: 'ተቋማት / ድርጅቶች', icon: 'building-2' },
                { id: 'sales_visits', label_en: 'Visit Logs', label_am: 'የጉብኝት መዝገብ', icon: 'map-pin' },
                { id: 'sales_proformas', label_en: 'Proformas', label_am: 'ፕሮፎርማዎች', icon: 'file-text' },
                { id: 'sales_contracts', label_en: 'Contracts', label_am: 'ኮንትራቶች', icon: 'award' },
            ]
        };

        document.addEventListener('DOMContentLoaded', async () => {
            lucide.createIcons();
            const isAuthed = checkAuthStatus();
            if (isAuthed) {
                await initApp();
            }
        });

        async function initApp() {
            lucide.createIcons();
            setupPwaPrompt();
            setupOfflineListener();
            startSidebarLiveClock();
            window.addEventListener('meash-lang-changed', () => {
                renderNavigation();
                loadActiveTab();
            });
            await switchRole(currentUser.role || 'owner');
        }

        function setupOfflineListener() {
            window.meashOffline.onStatusChange((status) => {
                const pill = document.getElementById('sync-status-pill');
                const dot = document.getElementById('sync-indicator-dot');
                const text = document.getElementById('sync-status-text');

                if (text) text.innerText = status.online ? 'ኦንላይን' : 'ኦፍላይን';
                if (dot) {
                    dot.className = status.online ? 'w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse' : 'w-1.5 h-1.5 rounded-full bg-amber-400';
                }
                if (pill) {
                    if (status.online) {
                        pill.className = 'px-3 py-1 rounded-full text-xs font-bold bg-emerald-950/80 border border-emerald-500/30 text-emerald-400 flex items-center gap-2 shadow-sm transition-all hover:bg-emerald-900/60';
                    } else {
                        pill.className = 'px-3 py-1 rounded-full text-xs font-bold bg-amber-950/80 border border-amber-500/30 text-amber-400 flex items-center gap-2 shadow-sm';
                    }
                }
            });

            window.addEventListener('meash-sync-conflict', (e) => {
                if (e.detail && e.detail.length > 0) {
                    showConflictModal(e.detail[0]);
                }
            });
        }

        function setupPwaPrompt() {
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPwaPrompt = e;
                const btn = document.getElementById('btn-pwa-install');
                if (btn) btn.classList.remove('hidden');
            });
        }

        function promptPwaInstall() {
            if (deferredPwaPrompt) {
                deferredPwaPrompt.prompt();
                deferredPwaPrompt.userChoice.then(() => { deferredPwaPrompt = null; });
            } else {
                alert('To install on iPhone: tap Share in Safari -> Add to Home Screen.\nOn Android/Chrome: tap browser menu -> Install app.');
            }
        }

        function toggleRoleDropdown(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('role-dropdown-menu');
            const chevron = document.getElementById('role-chevron');
            if (!menu) return;
            const isOpen = !menu.classList.contains('hidden');
            if (isOpen) {
                closeRoleDropdown();
            } else {
                menu.classList.remove('hidden');
                if (chevron) chevron.classList.add('rotate-180');
            }
        }

        function closeRoleDropdown() {
            const menu = document.getElementById('role-dropdown-menu');
            const chevron = document.getElementById('role-chevron');
            if (menu) menu.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }

        document.addEventListener('click', (e) => {
            const btn = document.getElementById('role-dropdown-btn');
            const menu = document.getElementById('role-dropdown-menu');
            if (menu && !menu.contains(e.target) && btn && !btn.contains(e.target)) {
                closeRoleDropdown();
            }
        });

        async function switchRole(role) {
            currentUser.role = role;
            if (role === 'owner') {
                currentUser.name = 'የሜሽ ዋና ስራ አስኪያጅ (GM)';
                activeTab = 'dashboard';
            } else if (role === 'reception') {
                currentUser.name = 'ቤተልሔም ታደሰ (ሪሴፕሽን)';
                activeTab = 'reception_desk';
            } else if (role === 'cleaner') {
                currentUser.name = 'ሰለሞን ከበደ (ቡድን አልፋ)';
                activeTab = 'cleaner_jobs';
            } else if (role === 'sales') {
                currentUser.name = 'ዳንኤል ግርማ (የውጭ ሽያጭ)';
                activeTab = 'sales_pipeline';
            }

            // Update header badge text
            const badge = document.getElementById('header-role-badge');
            if (badge) {
                const roleLabels = {
                    owner: '👑 ዋና ስራ አስኪያጅ',
                    reception: '📞 ሪሴፕሽን & ሽያጭ',
                    cleaner: '🧹 የፅዳት ቡድን',
                    sales: '💼 የውጭ ሽያጭ'
                };
                badge.innerText = roleLabels[role] || role;
            }

            const nameEl = document.getElementById('user-display-name');
            if (nameEl) nameEl.innerText = currentUser.name;
            const roleEl = document.getElementById('user-display-role');
            if (roleEl) roleEl.innerText = role.toUpperCase();
            const avatarEl = document.getElementById('user-avatar-text');
            if (avatarEl) avatarEl.innerText = currentUser.name.split(' ').map(n=>n[0]).join('').slice(0,2);

            renderNavigation();
            await getAuthToken();
            loadActiveTab();
        }

        function toggleMobileSidebar() {
            const drawer = document.getElementById('mobile-sidebar-drawer');
            if (drawer) {
                drawer.classList.toggle('hidden');
                if (!drawer.classList.contains('hidden')) {
                    if (window.lucide) lucide.createIcons();
                }
            }
        }

        function renderNavigation() {
            const isAmharic = window.meashI18n.getLanguage() === 'am';
            const navs = roleNavs[currentUser.role] || roleNavs.owner;
            const sidebar = document.getElementById('nav-items-container');
            const drawerNav = document.getElementById('mobile-drawer-nav');
            const mobile = document.getElementById('mobile-bottom-nav');

            const navItemsHtml = (isDrawer = false) => navs.map(item => {
                const label = isAmharic ? (item.label_am || item.label_en) : item.label_en;
                const clickHandler = isDrawer ? `setTab('${item.id}'); toggleMobileSidebar();` : `setTab('${item.id}');`;
                return `
                    <button onclick="${clickHandler}" class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-bold transition-all text-left cursor-pointer ${activeTab === item.id ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200'}">
                        <i data-lucide="${item.icon}" class="w-4 h-4"></i>
                        <span>${label}</span>
                    </button>
                `;
            }).join('');

            // 1. Desktop Sidebar (All tabs)
            if (sidebar) sidebar.innerHTML = navItemsHtml(false);

            // 2. Mobile Drawer Sidebar (All tabs accessible on phone!)
            if (drawerNav) drawerNav.innerHTML = navItemsHtml(true);

            // 3. Mobile Bottom Nav (Top 3 core tabs + "ተጨማሪ" / Menu button)
            if (mobile) {
                const topTabs = navs.slice(0, 3).map(item => {
                    const label = isAmharic ? (item.label_am || item.label_en) : item.label_en;
                    return `
                        <button onclick="setTab('${item.id}')" class="flex flex-col items-center gap-1 p-1.5 text-[10px] font-bold cursor-pointer ${activeTab === item.id ? 'text-cyan-400 font-black' : 'text-slate-400 hover:text-slate-200'}">
                            <i data-lucide="${item.icon}" class="w-5 h-5"></i>
                            <span class="truncate max-w-[65px]">${label.split(' ')[0]}</span>
                        </button>
                    `;
                }).join('');

                const moreBtn = `
                    <button onclick="toggleMobileSidebar()" class="flex flex-col items-center gap-1 p-1.5 text-[10px] font-bold text-slate-400 hover:text-cyan-300 cursor-pointer">
                        <i data-lucide="grid" class="w-5 h-5 text-cyan-400"></i>
                        <span>${isAmharic ? 'ተጨማሪ' : 'More'}</span>
                    </button>
                `;

                mobile.innerHTML = topTabs + moreBtn;
            }

            lucide.createIcons();
            updateLangSwitcherUI();
        }

        function updateLangSwitcherUI() {
            const isAm = window.meashI18n.getLanguage() === 'am';
            const btnEn = document.getElementById('app-lang-en');
            const btnAm = document.getElementById('app-lang-am');
            if (btnEn && btnAm) {
                if (isAm) {
                    btnAm.className = 'px-2 py-0.5 text-xs rounded font-bold bg-cyan-600 text-white shadow-sm';
                    btnEn.className = 'px-2 py-0.5 text-xs rounded font-bold text-slate-400 hover:text-white';
                } else {
                    btnEn.className = 'px-2 py-0.5 text-xs rounded font-bold bg-cyan-600 text-white shadow-sm';
                    btnAm.className = 'px-2 py-0.5 text-xs rounded font-bold text-slate-400 hover:text-white';
                }
            }
        }

        function setTab(tabId) {
            activeTab = tabId;
            renderNavigation();
            loadActiveTab();
        }

        // Modern Skeleton Shimmer Loader (No more old text "ገጹ በመጫን ላይ ነው...")
        function getSkeletonLoader() {
            return `
                <div class="max-w-7xl mx-auto space-y-4 animate-pulse">
                    <!-- Skeleton Top Header -->
                    <div class="flex items-center justify-between">
                        <div class="space-y-1.5">
                            <div class="h-2.5 w-24 bg-slate-800 rounded-full"></div>
                            <div class="h-6 w-44 bg-slate-800 rounded-xl"></div>
                        </div>
                        <div class="flex gap-2">
                            <div class="h-8 w-20 bg-slate-800 rounded-xl"></div>
                            <div class="h-8 w-20 bg-slate-800 rounded-xl"></div>
                        </div>
                    </div>

                    <!-- Skeleton Hero Profit Card -->
                    <div class="rounded-3xl bg-slate-900 border border-slate-800 p-4 sm:p-6 space-y-3.5">
                        <div class="flex justify-between items-center">
                            <div class="h-3 w-36 bg-slate-800 rounded-full"></div>
                            <div class="h-4 w-16 bg-slate-800 rounded-full"></div>
                        </div>
                        <div class="h-9 w-48 bg-slate-800 rounded-2xl"></div>
                        <div class="grid grid-cols-2 gap-2.5 pt-2 border-t border-slate-800/80">
                            <div class="h-12 bg-slate-800/60 rounded-2xl"></div>
                            <div class="h-12 bg-slate-800/60 rounded-2xl"></div>
                        </div>
                    </div>

                    <!-- Skeleton 4 Status Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <div class="h-16 bg-slate-900 border border-slate-800 rounded-2xl"></div>
                        <div class="h-16 bg-slate-900 border border-slate-800 rounded-2xl"></div>
                        <div class="h-16 bg-slate-900 border border-slate-800 rounded-2xl"></div>
                        <div class="h-16 bg-slate-900 border border-slate-800 rounded-2xl"></div>
                    </div>

                    <!-- Skeleton Items List Feed -->
                    <div class="rounded-3xl bg-slate-900 border border-slate-800 p-4 sm:p-6 space-y-3">
                        <div class="h-4 w-36 bg-slate-800 rounded-full"></div>
                        <div class="h-12 bg-slate-800/40 rounded-2xl"></div>
                        <div class="h-12 bg-slate-800/40 rounded-2xl"></div>
                        <div class="h-12 bg-slate-800/40 rounded-2xl"></div>
                    </div>
                </div>
            `;
        }

        function loadActiveTab() {
            const viewport = document.getElementById('app-viewport');
            viewport.innerHTML = getSkeletonLoader();
            lucide.createIcons();

            if (currentUser.role === 'cleaner' || activeTab.startsWith('cleaner_')) {
                renderCleanerView(viewport);
            } else if (currentUser.role === 'reception' && activeTab === 'reception_desk') {
                renderReceptionView(viewport);
            } else if (activeTab === 'dashboard') {
                renderOwnerDashboard(viewport);
            } else if (activeTab === 'orders') {
                renderOrdersModule(viewport);
            } else if (activeTab === 'subscriptions') {
                renderSubscriptionsModule(viewport);
            } else if (activeTab === 'calendar') {
                renderCalendarModule(viewport);
            } else if (activeTab === 'customers') {
                renderCustomersModule(viewport);
            } else if (activeTab === 'teams') {
                renderTeamsModule(viewport);
            } else if (activeTab === 'sales' || activeTab.startsWith('sales_')) {
                renderSalesModule(viewport);
            } else if (activeTab === 'finance') {
                renderFinanceModule(viewport);
            } else if (activeTab === 'care') {
                renderCustomerCareModule(viewport);
            } else if (activeTab === 'campaigns') {
                renderCampaignsModule(viewport);
            } else if (activeTab === 'settings') {
                renderSettingsModule(viewport);
            } else {
                renderOwnerDashboard(viewport);
            }
        }

        // ==========================================
        // 1. OWNER DASHBOARD (iPhone & PC BI View)
        // ==========================================
        async function renderOwnerDashboard(container) {
            try {
                const res = await apiFetch('/api/dashboard/owner');
                const data = await res.json();
                const today = data.today || {};
                const mtd = data.month_to_date || {};

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-4 sm:space-y-6">
                        <!-- Top Greeting & Header -->
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <span class="text-[10px] sm:text-xs font-bold text-cyan-400 uppercase tracking-wider block">${today.eth_date || 'Today'}</span>
                                <h2 class="text-lg sm:text-2xl font-black text-white tracking-tight">ስራ አስኪያጅ መቆጣጠሪያ</h2>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="openCreateOrderModal()" class="px-3 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-extrabold text-xs rounded-xl shadow-md shadow-cyan-500/20 flex items-center gap-1.5 transition cursor-pointer">
                                    <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                    <span class="hidden sm:inline">+ አዲስ ትዕዛዝ</span>
                                    <span class="sm:hidden">+ ትዕዛዝ</span>
                                </button>
                                <button onclick="openRecordExpenseModal()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 flex items-center gap-1.5 transition cursor-pointer">
                                    <i data-lucide="minus-circle" class="w-3.5 h-3.5 text-red-400"></i>
                                    <span class="hidden sm:inline">- ወጪ መዝግብ</span>
                                    <span class="sm:hidden">- ወጪ</span>
                                </button>
                            </div>
                        </div>

                        <!-- 1. PHONE-FIRST EXECUTIVE PROFIT & LOSS HERO CARD -->
                        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-900 to-slate-950 border border-slate-800 p-4 sm:p-6 shadow-2xl">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800/80">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                                    <span class="text-xs font-extrabold text-slate-300">የተጣራ የድርጅቱ ትርፍ (Net Profit)</span>
                                </div>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-950/80 text-emerald-400 border border-emerald-500/30">
                                    ${parseFloat(mtd.net_profit || 0) >= 0 ? 'ትርፋማ' : 'ኪሳራ'} • ${parseFloat(mtd.income || 0) > 0 ? Math.round((parseFloat(mtd.net_profit || 0) / parseFloat(mtd.income || 1)) * 100) : 0}%
                                </span>
                            </div>

                            <!-- Big Profit Number -->
                            <div class="py-3">
                                <div class="flex items-baseline gap-2">
                                    <span class="text-3xl sm:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-cyan-300 to-blue-400 tracking-tight">
                                        ${parseFloat(mtd.net_profit || 0).toLocaleString()}
                                    </span>
                                    <span class="text-sm font-bold text-cyan-400 font-mono">ETB</span>
                                </div>
                                <p class="text-[10px] sm:text-xs text-slate-500 mt-0.5">የወሩ አጠቃላይ የተጣራ ትርፍ (ከተረጋገጡ ክፍያዎች እና ወጪዎች)</p>
                            </div>

                            <!-- Income vs Expense Bar in Mobile -->
                            <div class="grid grid-cols-2 gap-2.5 pt-3 border-t border-slate-800/80">
                                <div class="p-2.5 sm:p-3 rounded-2xl bg-emerald-950/20 border border-emerald-500/20">
                                    <span class="text-[10px] text-emerald-400 font-bold block">የወሩ ገቢ</span>
                                    <span class="text-base sm:text-lg font-black text-white mt-0.5 block">
                                        ${parseFloat(mtd.income || 0).toLocaleString()} <span class="text-[9px] font-mono text-emerald-400">ETB</span>
                                    </span>
                                </div>
                                <div class="p-2.5 sm:p-3 rounded-2xl bg-red-950/20 border border-red-500/20">
                                    <span class="text-[10px] text-red-400 font-bold block">የወሩ ወጪ</span>
                                    <span class="text-base sm:text-lg font-black text-white mt-0.5 block">
                                        ${parseFloat(mtd.expenses || 0).toLocaleString()} <span class="text-[9px] font-mono text-red-400">ETB</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- 2. THE 4 ESSENTIAL OPERATIONAL STATUS CARDS -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">የዛሬ የስራ ሁኔታ</span>
                                <button type="button" onclick="toggleDetailedMetrics()" class="text-[11px] text-cyan-400 font-bold hover:underline flex items-center gap-1 cursor-pointer">
                                    <span id="text-toggle-metrics">ተጨማሪ ዝርዝር (6)</span>
                                    <i data-lucide="chevron-down" id="icon-toggle-metrics" class="w-3.5 h-3.5 transition-transform"></i>
                                </button>
                            </div>

                            <!-- 4 Key Grid Cards -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-medium block">የዛሬ ስራዎች</span>
                                        <span class="text-xl font-black text-white mt-0.5 block">${today.cleaning_jobs || 0}</span>
                                    </div>
                                    <span class="text-lg">🧹</span>
                                </div>

                                <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-medium block">ማረጋገጫ የሚጠብቁ</span>
                                        <span class="text-xl font-black text-amber-400 mt-0.5 block">${today.pending_bookings || 0}</span>
                                    </div>
                                    <span class="text-lg">⏳</span>
                                </div>

                                <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-medium block">ቡድን ያልተመደቡ</span>
                                        <span class="text-xl font-black ${today.unassigned_jobs > 0 ? 'text-red-400' : 'text-slate-400'} mt-0.5 block">${today.unassigned_jobs || 0}</span>
                                    </div>
                                    <span class="text-lg">👥</span>
                                </div>

                                <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-medium block">የዛሬ የተጣራ</span>
                                        <span class="text-base sm:text-lg font-black text-cyan-300 mt-0.5 block">${parseFloat(today.net_result || 0).toLocaleString()} <span class="text-[9px] font-mono">ETB</span></span>
                                    </div>
                                    <span class="text-lg">💰</span>
                                </div>
                            </div>

                            <!-- Collapsible 6 Secondary Metric Cards -->
                            <div id="detailed-metrics-drawer" class="hidden grid grid-cols-2 sm:grid-cols-3 gap-2.5 mt-2.5">
                                <div class="p-3 rounded-2xl bg-slate-900/70 border border-slate-800">
                                    <span class="text-[10px] text-slate-400 font-medium block">አዳዲስ ደንበኞች</span>
                                    <span class="text-lg font-black text-cyan-400 mt-0.5 block">${today.new_customers || 0}</span>
                                </div>
                                <div class="p-3 rounded-2xl bg-slate-900/70 border border-slate-800">
                                    <span class="text-[10px] text-slate-400 font-medium block">የክትትል ጥሪዎች</span>
                                    <span class="text-lg font-black text-blue-400 mt-0.5 block">${today.followups_due || 0}</span>
                                </div>
                                <div class="p-3 rounded-2xl bg-slate-900/70 border border-slate-800">
                                    <span class="text-[10px] text-slate-400 font-medium block">ክፍት ቅሬታዎች</span>
                                    <span class="text-lg font-black ${today.open_complaints > 0 ? 'text-red-400' : 'text-slate-400'} mt-0.5 block">${today.open_complaints || 0}</span>
                                </div>
                                <div class="p-3 rounded-2xl bg-slate-900/70 border border-slate-800">
                                    <span class="text-[10px] text-slate-400 font-medium block">የሚጠበቁ ፕሮፎርማዎች</span>
                                    <span class="text-lg font-black text-purple-400 mt-0.5 block">${today.pending_proformas || 0}</span>
                                </div>
                                <div class="p-3 rounded-2xl bg-emerald-950/30 border border-emerald-500/20">
                                    <span class="text-[10px] text-emerald-400 font-medium block">የዛሬ ገቢ</span>
                                    <span class="text-base font-black text-white mt-0.5 block">${parseFloat(today.income || 0).toLocaleString()} ETB</span>
                                </div>
                                <div class="p-3 rounded-2xl bg-red-950/30 border border-red-500/20">
                                    <span class="text-[10px] text-red-400 font-medium block">የዛሬ ወጪ</span>
                                    <span class="text-base font-black text-white mt-0.5 block">${parseFloat(today.expenses || 0).toLocaleString()} ETB</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Orders Feed -->
                        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-base font-bold text-white">የቅርብ ጊዜ የፅዳት ትዕዛዞች</h3>
                                <button onclick="setTab('orders')" class="text-xs text-cyan-400 font-bold hover:underline">ሁሉንም ትዕዛዞች እይ &rarr;</button>
                            </div>
                            <div class="divide-y divide-slate-800">
                                ${(data.recent_orders || []).map(o => `
                                    <div class="py-4 flex items-center justify-between gap-4">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold text-cyan-400">${o.order_number}</span>
                                                <span class="text-sm font-bold text-white">${o.customer?.full_name || 'Customer'}</span>
                                                <span class="text-xs text-slate-500">(${o.subcity || 'Addis Ababa'})</span>
                                            </div>
                                            <p class="text-xs text-slate-400 mt-1">የተመደበለት ቡድን: <strong>${o.assigned_team?.team_name || 'ቡድን አልተመደበም'}</strong></p>
                                        </div>
                                        <div class="text-right">
                                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold uppercase ${o.order_status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : (o.order_status === 'assigned' ? 'bg-blue-500/20 text-blue-400' : 'bg-amber-500/20 text-amber-400')}">
                                                ${o.order_status}
                                            </span>
                                            <p class="text-xs font-black text-white mt-1">${parseFloat(o.total).toLocaleString()} ETB</p>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                container.innerHTML = `<div class="p-6 text-center text-red-400">Failed to load ዋና ስራ አስኪያጅ summary: ${err.message}</div>`;
            }
        }

        function toggleDetailedMetrics() {
            const drawer = document.getElementById('detailed-metrics-drawer');
            const txt = document.getElementById('text-toggle-metrics');
            const icon = document.getElementById('icon-toggle-metrics');
            if (!drawer) return;
            const isHidden = drawer.classList.contains('hidden');
            if (isHidden) {
                drawer.classList.remove('hidden');
                if (txt) txt.innerText = 'አሳንስ (Hide)';
                if (icon) icon.classList.add('rotate-180');
            } else {
                drawer.classList.add('hidden');
                if (txt) txt.innerText = 'ተጨማሪ ዝርዝር (6)';
                if (icon) icon.classList.remove('rotate-180');
            }
        }

        // ==========================================
        // 2. RECEPTION DESK (Windows PC Operations)
        // ==========================================
        async function renderReceptionView(container) {
            try {
                const res = await apiFetch('/api/dashboard/reception');
                const data = await res.json();

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-8">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">${data.eth_date}</span>
                                <h2 class="text-2xl sm:text-3xl font-black text-white">የሪሴፕሽን ስራ መምሪያ ዴስክ (Reception Command Desk)</h2>
                                <p class="text-xs text-slate-400 mt-1">የደንበኛ ጥሪዎችን በቀጥታ መመዝገብ፣ ቀጠሮዎችን መያዝ፣ ቡድኖችን መመደብና ክትትል ማድረግ።</p>
                            </div>
                            <button onclick="openCreateOrderModal()" class="px-5 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2">
                                <i data-lucide="phone-incoming" class="w-4 h-4"></i>
                                <span>📞 ፈጣን ጥሪ መቀበያ / አዲስ ትዕዛዝ</span>
                            </button>
                        </div>

                        <!-- 4 Priority Queues for Reception -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Queue 1: New Booking Requests Needing Review -->
                            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 flex flex-col justify-between">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                                            <i data-lucide="bell" class="w-4 h-4"></i>
                                        </div>
                                        <h3 class="font-bold text-sm text-white">ማረጋገጫ የሚሹ አዳዲስ ትዕዛዞች (${data.counts?.new_bookings || 0})</h3>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    ${(data.new_bookings || []).length === 0 ? '<p class="text-xs text-slate-500 py-4 text-center">ማረጋገጫ የሚጠብቅ አዲስ ትዕዛዝ የለም።</p>' : ''}
                                    ${(data.new_bookings || []).map(b => `
                                        <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700 flex items-center justify-between gap-3">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-cyan-400">${b.order_number}</span>
                                                    <span class="text-xs font-bold text-white">${b.customer?.full_name}</span>
                                                </div>
                                                <p class="text-[11px] text-slate-400 mt-0.5">📞 ${b.customer?.phone} | 📍 ${b.subcity || 'Addis Ababa'}</p>
                                                <p class="text-[10px] text-slate-500">${b.items?.map(i => i.item_name).join(', ')}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button onclick="confirmAndAssignOrder(${b.id})" class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold">አረጋግጥ እና ሰራተኛ መድብ</button>
                                                <button onclick="openPostponeOrderModal(${b.id}, '${b.order_number}', '${b.customer?.full_name}')" class="px-2.5 py-1.5 rounded-lg bg-amber-950/70 hover:bg-amber-900 text-amber-300 border border-amber-500/40 text-xs font-bold" title="ቀጠሮ አስተላልፍ">📅 አስተላልፍ</button>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- Queue 2: Today's Cleaning Dispatch Schedule -->
                            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 flex flex-col justify-between">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center">
                                            <i data-lucide="calendar" class="w-4 h-4"></i>
                                        </div>
                                        <h3 class="font-bold text-sm text-white">የዛሬ የስራ መርሃ-ግብር እና ቡድኖች (${data.counts?.today_jobs || 0})</h3>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    ${(data.today_schedule || []).length === 0 ? '<p class="text-xs text-slate-500 py-4 text-center">ለዛሬ የተያዘ ቀጠሮ የለም።</p>' : ''}
                                    ${(data.today_schedule || []).map(s => `
                                        <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700 flex items-center justify-between gap-3">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-bold text-white">${s.customer?.full_name}</span>
                                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-700 text-slate-300 font-bold">${s.appointment_time_slot}</span>
                                                </div>
                                                <p class="text-[11px] text-slate-400 mt-0.5">የተመደበ ቡድን: <strong>${s.assigned_team?.team_name || 'ቡድን አልተመደበም'}</strong></p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-bold ${s.order_status === 'completed' ? 'text-emerald-400' : 'text-cyan-400'} uppercase">${s.order_status}</span>
                                                ${s.order_status !== 'completed' ? `
                                                    <button onclick="openPostponeOrderModal(${s.id}, '${s.order_number}', '${s.customer?.full_name}')" class="px-2 py-1 rounded-lg bg-amber-950/60 hover:bg-amber-900/60 text-amber-300 border border-amber-500/30 text-[10px] font-bold">📅 አስተላልፍ</button>
                                                ` : ''}
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- Queue 3: Next-Day የክትትል ጥሪዎች -->
                            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center">
                                            <i data-lucide="phone-forwarded" class="w-4 h-4"></i>
                                        </div>
                                        <h3 class="font-bold text-sm text-white">ለደንበኞች የሚደረጉ የክትትል ጥሪዎች (${data.counts?.followups_due || 0})</h3>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    ${(data.due_followups || []).length === 0 ? '<p class="text-xs text-slate-500 py-4 text-center">ሁሉም የክትትል ጥሪዎች ተከናውነዋል!</p>' : ''}
                                    ${(data.due_followups || []).map(f => `
                                        <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700 flex items-center justify-between gap-3">
                                            <div>
                                                <span class="text-xs font-bold text-white">${f.customer?.full_name}</span>
                                                <p class="text-[11px] text-slate-400">Order: ${f.order?.order_number} | 📞 ${f.customer?.phone}</p>
                                            </div>
                                            <button onclick="openFollowupCallModal(${f.id}, '${f.customer?.full_name}', '${f.customer?.phone}')" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold">ጥሪ መዝግብ</button>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- Queue 4: ንቁ ስራዎች ክፍት ቅሬታዎች Desk -->
                            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center">
                                            <i data-lucide="alert-circle" class="w-4 h-4"></i>
                                        </div>
                                        <h3 class="font-bold text-sm text-white">ንቁ ስራዎች ክፍት ቅሬታዎች (${data.counts?.open_complaints || 0})</h3>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    ${(data.urgent_complaints || []).length === 0 ? '<p class="text-xs text-slate-500 py-4 text-center">ምንም ያልተፈታ ቅሬታ የለም። በጣም ጥሩ!</p>' : ''}
                                    ${(data.urgent_complaints || []).map(c => `
                                        <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-red-500/30 flex items-center justify-between gap-3">
                                            <div>
                                                <span class="text-xs font-bold text-white">${c.customer?.full_name}</span>
                                                <p class="text-[11px] text-red-400 font-semibold">${c.category}: ${c.description}</p>
                                            </div>
                                            <button onclick="openResolveComplaintModal(${c.id})" class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-xs font-bold">መፍትሄ ስጥ</button>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                container.innerHTML = `<div class="p-6 text-center text-red-400">Failed to load Reception overview: ${err.message}</div>`;
            }
        }

        // ==========================================
        // 3. CLEANING TEAM HUB (Android Phone View)
        // ==========================================
        async function renderCleanerView(container) {
            try {
                let jobs = [];
                // Check offline cached jobs first if available
                const cached = await window.meashOffline.getAllItems('my_jobs');
                if (cached && cached.length > 0 && !window.meashOffline.isOnline) {
                    jobs = cached;
                } else {
                    const res = await apiFetch('/api/teams/my-jobs');
                    const data = await res.json();
                    jobs = data.today_jobs || [];
                    // Cache into IndexedDB for offline access
                    if (window.meashOffline.db) {
                        window.meashOffline.cacheItems('my_jobs', jobs);
                    }
                }

                container.innerHTML = `
                    <div class="max-w-2xl mx-auto space-y-6">
                        <div class="p-4 rounded-2xl bg-gradient-to-r from-cyan-900/60 to-blue-900/60 border border-cyan-500/30 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider block">የመስክ አጽጂ ቡድን መከታተያ</span>
                                <h2 class="text-xl font-black text-white">የዛሬ የስራ ዝርዝሮች</h2>
                                <p class="text-xs text-cyan-300 font-semibold mt-0.5">🗓 ${EC.formatEth(new Date())} • Team Alpha (Solomon Kebede)</p>
                            </div>
                            <span class="px-3 py-1 rounded-xl bg-cyan-500/20 text-cyan-400 text-xs font-black">${jobs.length} ንቁ ስራዎች</span>
                        </div>

                        ${jobs.length === 0 ? `
                            <div class="p-12 text-center bg-slate-900 rounded-3xl border border-slate-800">
                                <i data-lucide="check-circle-2" class="w-12 h-12 text-emerald-400 mx-auto mb-3"></i>
                                <h4 class="text-lg font-bold text-white">ሁሉም ስራዎች ተጠናቀዋል!</h4>
                                <p class="text-xs text-slate-400 mt-1">ለዛሬ ለቡድንዎ የተመደበ ያልተጠናቀቀ ስራ የለም።</p>
                            </div>
                        ` : ''}

                        <!-- JOB CARDS -->
                        <div class="space-y-4">
                            ${jobs.map(job => `
                                <div class="bg-slate-900 border ${job.order_status === 'cleaning' ? 'border-cyan-500 shadow-xl shadow-cyan-500/10' : 'border-slate-800'} rounded-3xl p-5 sm:p-6 space-y-4">
                                    <!-- የቀጠሮ ሰዓት & Status -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full ${job.order_status === 'cleaning' ? 'bg-cyan-400 animate-ping' : 'bg-blue-400'}"></span>
                                            <span class="text-xs font-bold text-white">${formatTimeSlot(job.appointment_time_slot)}</span>
                                        </div>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${job.order_status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : (job.order_status === 'cleaning' ? 'bg-cyan-500/20 text-cyan-400' : 'bg-blue-500/20 text-blue-400')}">
                                            ${job.order_status}
                                        </span>
                                    </div>

                                    <!-- Customer Details -->
                                    <div>
                                        <h3 class="text-lg font-black text-white">${job.customer?.full_name}</h3>
                                        <p class="text-xs font-medium text-slate-400 flex items-center gap-1.5 mt-1">
                                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-cyan-400"></i>
                                            <span>${job.address || job.customer?.address}</span>
                                        </p>
                                    </div>

                                    <!-- የሚፀዱ እቃዎች / አገልግሎት Breakdown -->
                                    <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700/80 space-y-2">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">የሚፀዱ የተመደቡ እቃዎች</span>
                                        ${(job.items || []).map(item => `
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="font-bold text-slate-200">${item.item_name}</span>
                                                <span class="px-2 py-0.5 rounded bg-slate-700 text-cyan-400 font-extrabold">× ${parseFloat(item.quantity)}</span>
                                            </div>
                                        `).join('')}
                                    </div>

                                    <!-- Action Buttons (1-Tap Experience) -->
                                    <div class="pt-2 grid grid-cols-2 sm:grid-cols-4 gap-2">
                                        <a href="tel:${job.customer?.phone}" class="py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold flex items-center justify-center gap-1.5 border border-slate-700">
                                            <i data-lucide="phone" class="w-3.5 h-3.5 text-emerald-400"></i>
                                            <span>ደውል</span>
                                        </a>
                                        ${(job.latitude && job.longitude) ? `
                                            <button onclick="openInAppLiveRideMap(${job.id}, ${job.latitude}, ${job.longitude}, '${(job.customer?.full_name || 'ውድ ደንበኛ').replace(/'/g, "\\'")}', '${(job.customer?.phone || '').replace(/'/g, "\\'")}', '${(job.address || job.customer?.address || 'Addis Ababa').replace(/'/g, "\\'")}', 'Team Alpha')" class="py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-cyan-300 border border-cyan-500/40 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors">
                                                <i data-lucide="navigation-2" class="w-3.5 h-3.5 text-cyan-400"></i>
                                                <span>የቀጥታ ካርታ</span>
                                            </button>
                                        ` : `
                                            <div class="py-2.5 rounded-xl bg-slate-800/40 text-slate-400 border border-slate-700/50 text-[11px] font-medium flex items-center justify-center gap-1 text-center" title="ደንበኛው ካርታ አልመረጠም">
                                                <i data-lucide="map-pin-off" class="w-3.5 h-3.5 text-slate-500"></i>
                                                <span>ካርታ የለውም</span>
                                            </div>
                                        `}

                                        ${(job.order_status === 'new' || job.order_status === 'assigned') ? `
                                            <button onclick="cleanerStartJourney(${job.id})" class="py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white text-xs font-extrabold flex items-center justify-center gap-1.5 shadow-lg col-span-2">
                                                <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
                                                <span>🚗 ወደ ደንበኛው ጉዞ ጀምር</span>
                                            </button>
                                        ` : (job.order_status === 'on_the_way' ? `
                                            <button onclick="cleanerSendLiveGps(${job.id})" class="py-2.5 rounded-xl bg-emerald-950/80 hover:bg-emerald-900 border border-emerald-500/40 text-emerald-300 text-xs font-bold flex items-center justify-center gap-1.5">
                                                <i data-lucide="map-pin" class="w-3.5 h-3.5 animate-pulse text-emerald-400"></i>
                                                <span>📍 GPS አድስ</span>
                                            </button>
                                            <button onclick="cleanerJobAction(${job.id}, 'start')" class="py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-lg">
                                                <i data-lucide="play" class="w-3.5 h-3.5"></i>
                                                <span>ስራ ጀምር</span>
                                            </button>
                                        ` : (job.order_status === 'cleaning' ? `
                                            <button onclick="cleanerSendLiveGps(${job.id})" class="py-2.5 rounded-xl bg-cyan-950/80 hover:bg-cyan-900 border border-cyan-500/40 text-cyan-300 text-xs font-bold flex items-center justify-center gap-1.5">
                                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-cyan-400"></i>
                                                <span>📍 GPS አድስ</span>
                                            </button>
                                            <button onclick="cleanerJobAction(${job.id}, 'complete')" class="py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white text-xs font-extrabold flex items-center justify-center gap-1.5 shadow-lg">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>ስራ ጨርስ</span>
                                            </button>
                                        ` : `
                                            <span class="py-2.5 text-center text-xs font-bold text-emerald-400 col-span-2">✅ ተጠናቋል</span>
                                        `))}

                                        <button onclick="openProblemReportModal(${job.id})" class="py-2.5 rounded-xl bg-red-950/50 hover:bg-red-900/50 text-red-300 text-xs font-bold flex items-center justify-center gap-1.5 border border-red-500/30">
                                            <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-red-400"></i>
                                            <span>ችግር</span>
                                        </button>
                                        <button onclick="openPostponeOrderModal(${job.id}, '${job.order_number}', '${job.customer?.full_name}')" class="py-2.5 rounded-xl bg-amber-950/60 hover:bg-amber-900/60 text-amber-300 text-xs font-bold flex items-center justify-center gap-1.5 border border-amber-500/30 ${job.order_status === 'new' || job.order_status === 'assigned' ? 'col-span-1' : 'col-span-2 sm:col-span-4'} transition-all">
                                            <i data-lucide="calendar-clock" class="w-3.5 h-3.5 text-amber-400"></i>
                                            <span>📅 ቀጠሮ አስተላልፍ</span>
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                container.innerHTML = `<div class="p-6 text-center text-red-400">Failed to load Cleaner jobs: ${err.message}</div>`;
            }
        }

        async function cleanerStartJourney(orderId) {
            let lat = null, lng = null;
            if (navigator.geolocation) {
                try {
                    const pos = await new Promise((res, rej) => navigator.geolocation.getCurrentPosition(res, rej, { timeout: 8000, enableHighAccuracy: true }));
                    lat = pos.coords.latitude;
                    lng = pos.coords.longitude;
                } catch (e) {
                    console.warn('Geolocation capture skipped:', e.message);
                }
            }

            await cleanerJobAction(orderId, 'on_the_way', { latitude: lat, longitude: lng });
            alert('🚗 ጉዞ ጀምረዋል! ለደንበኛው የጽዳት ቡድኑ በመንገድ ላይ መሆኑን የሚገልጽ SMS እና የቀጥታ ካርታ መከታተያ ተልኳል።');
        }

        async function cleanerSendLiveGps(orderId) {
            if (!navigator.geolocation) {
                alert('የስልክዎ/ብሮውዘርዎ ጂፒኤስ (GPS) አልተገኘም።');
                return;
            }

            navigator.geolocation.getCurrentPosition(async (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                try {
                    await apiFetch('/api/teams/update-location', {
                        method: 'POST',
                        body: JSON.stringify({ latitude: lat, longitude: lng })
                    });
                    alert(`📍 የቀጥታ GPS መገኛዎ ታድሷል! (${lat.toFixed(4)}, ${lng.toFixed(4)}) ደንበኛው በካርታ ላይ እያየዎት ነው።`);
                } catch(e) {
                    alert('GPS ማዘመን አልተቻለም: ' + e.message);
                }
            }, (err) => {
                alert('የጂፒኤስ መረጃ ማግኘት አልተቻለም: ' + err.message);
            }, { enableHighAccuracy: true, timeout: 8000 });
        }

        async function cleanerJobAction(orderId, action, extraPayload = {}) {
            const payload = Object.assign({
                order_id: orderId,
                action: action,
                order_status: action === 'complete' ? 'completed' : (action === 'start' ? 'cleaning' : (action === 'on_the_way' ? 'on_the_way' : action)),
            }, extraPayload);

            // Queue via offline engine so it works 100% when internet is absent!
            await window.meashOffline.enqueue('order', 'job_action', payload);

            // Reload UI immediately to reflect optimistic update
            loadActiveTab();
        }

        // ==========================================
        // 4. OUTDOOR SALES CRM & PIPELINE
        // ==========================================
        // ==========================================
        // 4. OUTDOOR SALES CRM & ACTIVITY LOG (Excel-style Table)
        // ==========================================
        async function renderSalesModule(container) {
            try {
                const [visitsRes, orgsRes] = await Promise.all([
                    apiFetch('/api/sales/visits?per_page=100'),
                    apiFetch('/api/sales/organizations?per_page=100')
                ]);
                const visitsData = await visitsRes.json();
                const orgsData = await orgsRes.json();
                const visits = visitsData.data || (Array.isArray(visitsData) ? visitsData : []);
                const orgs = orgsData.data || (Array.isArray(orgsData) ? orgsData : []);

                window._salesVisitsData = visits;
                window._salesOrgsData = orgs;

                const stageAmharic = {
                    'new_lead': 'አዲስ ግንኙነት',
                    'visited': 'ተጎብኝቷል',
                    'contact_established': 'በሂደት ላይ',
                    'interested': 'ፍላጎት አላቸው',
                    'proforma_requested': 'ፕሮፎርማ ጠይቀዋል',
                    'proforma_sent': 'ፕሮፎርማ ተልኳል',
                    'negotiation': 'ድርድር ላይ',
                    'won': 'ተስማምተዋል',
                    'lost': 'አልተስማሙም',
                    'followup_later': 'ቀጠሮ ተይዟል'
                };

                const industryAmharic = {
                    'hotel': 'ሆቴል',
                    'restaurant': 'ሬስቶራንት',
                    'cafe': 'ካፌ',
                    'office': 'ቢሮ',
                    'bank': 'ባንክ',
                    'school': 'ት/ቤት',
                    'hospital': 'ሆስፒታል',
                    'real_estate': 'ሪል እስቴት',
                    'embassy': 'ኤምባሲ',
                    'mall': 'ሞል',
                    'other': 'ሌላ'
                };

                container.innerHTML = `
                    <div class="max-w-[98%] mx-auto space-y-6">
                        <!-- Top Header & Action Controls -->
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">የተቋማትና የሆቴሎች አካውንቶች</span>
                                <h2 class="text-2xl sm:text-3xl font-black text-white">የውጭ ሽያጭ እና የድርጅቶች የስራ ሂደት (CRM)</h2>
                                <p class="text-xs text-slate-400 mt-0.5">የመስክ ሽያጭ ሰራተኞች የድርጅቶች ጉብኝት፣ ፕሮፎርማ እና የስራ እንቅስቃሴ መዝገብ።</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <button onclick="openLogSalesVisitModal()" class="px-4 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                    <span>+ እንቅስቃሴ መዝግብ</span>
                                </button>
                                <button onclick="openAddOrganizationModal()" class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="building" class="w-4 h-4 text-cyan-400"></i>
                                    <span>+ ድርጅት መዝግብ</span>
                                </button>
                                <button onclick="openCreateProformaModal()" class="px-3.5 py-2.5 bg-amber-600/90 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="file-text" class="w-4 h-4"></i>
                                    <span>🖨️ ፕሮፎርማ አዘጋጅ</span>
                                </button>
                                <button onclick="exportSalesTableToExcel()" class="px-3.5 py-2.5 bg-emerald-600/90 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                    <span>📥 Excel አውርድ (.xls)</span>
                                </button>
                            </div>
                        </div>

                        <!-- EXCEL-STYLE TABLE CONTAINER -->
                        <div class="rounded-2xl border-2 border-slate-700 overflow-hidden shadow-2xl bg-white text-slate-900">
                            <!-- Excel Title Banner -->
                            <div class="bg-[#1b4382] text-white py-2.5 px-4 text-center select-none border-b-2 border-slate-700">
                                <h3 class="text-base sm:text-lg font-black tracking-wider uppercase">MEASH CLEANING SOLUTION</h3>
                                <p class="text-xs font-semibold text-blue-200 tracking-wider">Outdoor Sales Activity Log</p>
                            </div>

                            <!-- Table Scroll Area -->
                            <div class="overflow-x-auto max-h-[72vh]">
                                <table id="sales-activity-table" class="w-full border-collapse text-[12px] font-sans">
                                    <thead>
                                        <tr class="text-center font-bold text-white uppercase text-[11px] select-none border-b border-slate-800">
                                            <th class="bg-[#8ecae6] text-slate-900 border border-slate-400 px-2 py-2.5 w-12">ተ.ቁ</th>
                                            <th class="bg-[#c2a649] text-white border border-slate-400 px-3 py-2.5 w-24">ቀን</th>
                                            <th class="bg-[#9c84b8] text-white border border-slate-400 px-2.5 py-2.5 w-20">ዕለት</th>
                                            <th class="bg-[#a89cb8] text-white border border-slate-400 px-4 py-2.5 min-w-[160px] text-left">የድርጅቱ ስም</th>
                                            <th class="bg-[#6b9080] text-white border border-slate-400 px-3 py-2.5 min-w-[140px]">የድርጅቱ ዓይነት<br><span class="text-[9px] font-normal lowercase">(ሆቴል/ሬስቶራንት/ቢሮ)</span></th>
                                            <th class="bg-[#2a6f97] text-white border border-slate-400 px-4 py-2.5 min-w-[180px] text-left">ያነጋገሩት ኃላፊ ስም (ማዕረግ)</th>
                                            <th class="bg-[#7f7053] text-white border border-slate-400 px-3 py-2.5 w-28">ስልክ ቁጥር</th>
                                            <th class="bg-[#788e40] text-white border border-slate-400 px-3 py-2.5 min-w-[120px]">አድራሻ / ቦታ</th>
                                            <th class="bg-[#9f86c0] text-white border border-slate-400 px-3 py-2.5 min-w-[130px]">የደረሱበት ደረጃ</th>
                                            <th class="bg-[#c7f9cc] text-slate-900 border border-slate-400 px-3 py-2.5 min-w-[120px]">ፕሮፎርማ / Quote</th>
                                            <th class="bg-[#f2e9e4] text-slate-900 border border-slate-400 px-4 py-2.5 min-w-[220px] text-left">ተጨማሪ ማስታወሻ/ያጋጠመ ነገር</th>
                                            <th class="bg-slate-700 text-white border border-slate-400 px-3 py-2.5 w-28 text-center">እርምጃ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-300 bg-white">
                                        ${visits.length === 0 ? `
                                            <tr>
                                                <td colspan="12" class="py-12 text-center text-slate-500 font-medium italic">
                                                    እስካሁን የተመዘገበ የውጭ ሽያጭ እንቅስቃሴ የለም። ከላይ «+ እንቅስቃሴ መዝግብ» የሚለውን ተጭነው የመጀመሪያውን መዝገብ ያስገቡ።
                                                </td>
                                            </tr>
                                        ` : visits.map((v, idx) => {
                                            const orgName = v.organization?.name || '—';
                                            const orgType = industryAmharic[v.organization?.industry] || v.organization?.industry || 'ድርጅት';
                                            const contactName = v.contact_person ? `${v.contact_person} ${v.contact_position ? '(' + v.contact_position + ')' : ''}` : '—';
                                            const phone = v.contact_phone || v.organization?.phone || '—';
                                            const address = v.organization?.address || v.organization?.subcity || 'አዲስ አበባ';
                                            const stage = stageAmharic[v.stage] || v.stage;
                                            const proformaReq = (v.stage === 'proforma_requested' || v.stage === 'proforma_sent' || (v.proformas && v.proformas.length > 0)) ? 'ያስፈልጋል' : (v.stage === 'won' ? 'ተጠናቋል' : 'በሂደት ላይ');
                                            const notes = v.notes || (v.summary ? v.summary : '—');
                                            const ethDate = v.eth_visit_date || (v.visit_date ? EC.formatEth(v.visit_date) : '—');
                                            const dayName = v.day_of_week_am || getDayNameAm(v.visit_date);

                                            return `
                                                <tr class="hover:bg-blue-50/70 transition-colors">
                                                    <td class="border border-slate-300 py-2 px-2 text-center font-bold text-slate-700 font-mono">${idx + 1}</td>
                                                    <td class="border border-slate-300 py-2 px-2 text-center font-bold font-mono text-slate-800">${ethDate}</td>
                                                    <td class="border border-slate-300 py-2 px-2 text-center font-medium text-slate-700">${dayName}</td>
                                                    <td class="border border-slate-300 py-2 px-3 font-black text-slate-900">${orgName}</td>
                                                    <td class="border border-slate-300 py-2 px-2.5 text-center font-medium text-slate-800">${orgType}</td>
                                                    <td class="border border-slate-300 py-2 px-3 text-slate-800">${contactName}</td>
                                                    <td class="border border-slate-300 py-2 px-2.5 text-center font-mono font-semibold text-slate-800">${phone}</td>
                                                    <td class="border border-slate-300 py-2 px-2.5 text-center text-slate-700">${address}</td>
                                                    <td class="border border-slate-300 py-2 px-2.5 text-center">
                                                        <span class="px-2 py-0.5 rounded text-[11px] font-bold ${v.stage === 'won' ? 'bg-emerald-100 text-emerald-800' : (v.stage === 'proforma_requested' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800')}">
                                                            ${stage}
                                                        </span>
                                                    </td>
                                                    <td class="border border-slate-300 py-2 px-2.5 text-center font-semibold text-slate-800">${proformaReq}</td>
                                                    <td class="border border-slate-300 py-2 px-3 text-slate-700 leading-snug">${notes}</td>
                                                    <td class="border border-slate-300 py-2 px-2 text-center whitespace-nowrap space-x-1">
                                                        <button onclick="openPrintProformaDirectModal('${(orgName).replace(/'/g, "\\'")}', '${(contactName).replace(/'/g, "\\'")}', '${(phone).replace(/'/g, "\\'")}', '${(address).replace(/'/g, "\\'")}')" class="px-2 py-1 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded text-[10px]" title="ፕሮፎርማ አትም">
                                                            🖨️ Proforma
                                                        </button>
                                                        <button onclick="advanceSalesStageDirect(${v.id}, '${v.stage}')" class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded text-[10px]" title="ደረጃ ቀይር">
                                                            ✏️ ደረጃ
                                                        </button>
                                                    </td>
                                                </tr>
                                            `;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                container.innerHTML = `<div class="p-6 text-center text-red-400">Failed to load Sales Activity Log: ${err.message}</div>`;
            }
        }

        function getDayNameAm(dateStr) {
            if (!dateStr) return '—';
            try {
                const d = new Date(dateStr);
                const days = ['እሁድ', 'ሰኞ', 'ማክሰኞ', 'ረቡዕ', 'ሐሙስ', 'አርብ', 'ቅዳሜ'];
                return days[d.getDay()] || '—';
            } catch (e) {
                return '—';
            }
        }

        async function advanceSalesStageDirect(visitId, currentStage) {
            const stages = [
                { key: 'new_lead', name: 'አዲስ ግንኙነት' },
                { key: 'visited', name: 'ተጎብኝቷል' },
                { key: 'contact_established', name: 'በሂደት ላይ' },
                { key: 'interested', name: 'ፍላጎት አላቸው' },
                { key: 'proforma_requested', name: 'ፕሮፎርማ ጠይቀዋል' },
                { key: 'proforma_sent', name: 'ፕሮፎርማ ተልኳል' },
                { key: 'negotiation', name: 'ድርድር ላይ' },
                { key: 'won', name: 'ተስማምተዋል (ውል)' },
                { key: 'lost', name: 'አልተስማሙም' }
            ];

            const current = stages.find(s => s.key === currentStage);
            const selectOptions = stages.map(s => `<option value="${s.key}" ${s.key === currentStage ? 'selected' : ''}>${s.name}</option>`).join('');

            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-sm w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <h3 class="text-sm font-bold text-white">የሽያጭ ደረጃ አዘምን</h3>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white">&times;</button>
                        </div>
                        <div class="space-y-3 text-xs">
                            <label class="block text-slate-300">አዲስ ደረጃ ይምረጡ፦</label>
                            <select id="stage-select-direct" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold">
                                ${selectOptions}
                            </select>
                            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                                <button onclick="closeModal()" class="px-3 py-1.5 bg-slate-800 text-slate-300 rounded-xl">ሰርዝ</button>
                                <button onclick="confirmStageChange(${visitId})" class="px-4 py-1.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl">አረጋግጥ</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        async function confirmStageChange(visitId) {
            const newStage = document.getElementById('stage-select-direct').value;
            await apiFetch(`/api/sales/visits/${visitId}/stage`, {
                method: 'POST',
                body: JSON.stringify({ stage: newStage }),
            });
            closeModal();
            loadActiveTab();
        }

        function exportSalesTableToExcel() {
            const table = document.getElementById('sales-activity-table');
            if (!table) return alert('ሰንጠረዡ አልተገኘም።');

            // Clone table and strip last action column
            const clone = table.cloneNode(true);
            const allRows = clone.querySelectorAll('tr');
            allRows.forEach(row => {
                const cells = row.querySelectorAll('th, td');
                if (cells.length > 0) {
                    cells[cells.length - 1].remove(); // remove last column (action buttons)
                }
            });

            const todayStr = new Date().toISOString().split('T')[0];
            const template = `
                <html>
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <style>
                        table { border-collapse: collapse; font-family: Segoe UI, Tahoma, sans-serif; font-size: 11pt; width: 100%; }
                        th { background-color: #1b4382; color: #ffffff; font-weight: bold; border: 1px solid #777777; padding: 10px; text-align: left; }
                        td { border: 1px solid #dddddd; padding: 8px; vertical-align: top; }
                        .banner { background-color: #1b4382; color: #ffffff; padding: 15px; font-size: 16pt; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class="banner">MEASH CLEANING SOLUTION — Outdoor Sales Activity Log (${todayStr})</div>
                    <br/>
                    ${clone.outerHTML}
                </body>
                </html>
            `;

            const blob = new Blob(['\uFEFF' + template], { type: 'application/vnd.ms-excel;charset=utf-8' });
            const downloadLink = document.createElement('a');
            downloadLink.download = `Meash_Outdoor_Sales_Activity_Log_${todayStr}.xls`;
            downloadLink.href = window.URL.createObjectURL(blob);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }

        // ==========================================
        // 5. ALL ORDERS MODULE
        // ==========================================
        async function renderOrdersModule(container) {
            try {
                const res = await apiFetch('/api/orders');
                const data = await res.json();
                const orders = data.data || [];

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">የትዕዛዞች ማስተዳደሪያ (Orders Management)</h2>
                                <p class="text-xs text-slate-400 mt-1">ሁሉንም የፅዳት ትዕዛዞች መመልከት፣ ማጣራት እና ማስተዳደር።</p>
                            </div>
                            <button onclick="openCreateOrderModal()" class="px-4 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span>+ አዲስ ትዕዛዝ</span>
                            </button>
                        </div>

                        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-800/80 text-slate-400 uppercase text-[10px] tracking-wider">
                                        <tr>
                                            <th class="p-4">ትዕዛዝ #</th>
                                            <th class="p-4">ደንበኛ</th>
                                            <th class="p-4">ቀጠሮ</th>
                                            <th class="p-4">የተመደበ ቡድን</th>
                                            <th class="p-4">ጠቅላላ ዋጋ</th>
                                            <th class="p-4">ሁኔታ</th>
                                            <th class="p-4 text-right">እርምጃ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800">
                                        ${orders.map(o => `
                                            <tr class="hover:bg-slate-800/40">
                                                <td class="p-4 font-bold text-cyan-400">${o.order_number}</td>
                                                <td class="p-4">
                                                    <span class="font-bold text-white block">${o.customer?.full_name}</span>
                                                    <span class="text-slate-400 text-[11px]">${o.customer?.phone}</span>
                                                </td>
                                                <td class="p-4">
                                                    <span class="font-bold text-white block text-xs">🗓 ${o.eth_appointment_date || (o.appointment_date ? EC.formatEth(o.appointment_date) : '—')}</span>
                                                    <span class="text-slate-400 text-[10px] block">🌐 ${o.appointment_date || '—'}</span>
                                                    <span class="text-[10px] font-semibold text-cyan-400 block mt-0.5">
                                                        ${formatTimeSlot(o.appointment_time_slot)}
                                                    </span>
                                                </td>
                                                <td class="p-4 text-slate-300 font-semibold">${o.assigned_team?.team_name || '<span class="text-red-400">ሰራተኛ አልተመደበም</span>'}</td>
                                                <td class="p-4 font-extrabold text-white">${parseFloat(o.total).toLocaleString()} ETB</td>
                                                <td class="p-4">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${o.order_status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-cyan-500/20 text-cyan-400'}">
                                                        ${o.order_status}
                                                    </span>
                                                </td>
                                                <td class="p-4 text-right space-x-1.5 whitespace-nowrap">
                                                    <button onclick="confirmAndAssignOrder(${o.id})" class="px-2.5 py-1 bg-blue-950/90 hover:bg-blue-900 text-blue-300 font-bold text-[11px] rounded-lg border border-blue-500/40 transition-colors" title="የፅዳት ሰራተኛ መድብ">👤 ሰራተኛ መድብ</button>
                                                    <button onclick="openDirectSmsModal('${o.customer?.phone || ''}', '${o.customer?.full_name || ''}')" class="px-2.5 py-1 bg-cyan-950/80 hover:bg-cyan-900 text-cyan-300 font-bold text-[11px] rounded-lg border border-cyan-500/30">📩 SMS</button>
                                                    <button onclick="openOrderDetailsModal(${o.id})" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-cyan-400 font-bold text-[11px] rounded-lg border border-slate-700">ዝርዝር</button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (e) {
                container.innerHTML = `<div class="p-6 text-red-400">Failed to load orders: ${e.message}</div>`;
            }
        }

        // ==========================================
        // 5b. SUBSCRIPTIONS & RECURRING SERVICES MODULE
        // ==========================================
        async function renderSubscriptionsModule(container) {
            try {
                const res = await apiFetch('/api/subscriptions');
                const data = await res.json();
                const subs = data.data || [];

                const activeCount = subs.filter(s => s.status === 'active').length;
                const weeklyCount = subs.filter(s => s.plan_type === 'weekly').length;
                const biweeklyCount = subs.filter(s => s.plan_type === 'biweekly').length;
                const monthlyCount = subs.filter(s => s.plan_type === 'monthly').length;

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">የደንበኝነት እቅዶች አስተዳደር (Subscriptions)</h2>
                                <p class="text-xs text-slate-400 mt-1">ተደጋጋሚ የጽዳት ውሎችን፣ ሳምንታዊ፣ የሁለት ሳምንት እና ወርሃዊ እቅዶችን ማስተዳደር።</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1.5 rounded-xl bg-cyan-950/80 border border-cyan-500/30 text-cyan-300 text-xs font-bold">
                                    🔄 ${activeCount} ንቁ የደንበኝነት ተጠቃሚዎች
                                </span>
                            </div>
                        </div>

                        <!-- 4 STAT CARDS -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
                                <span class="text-xs text-slate-400 font-semibold block">ጠቅላላ የደንበኝነት ተጠቃሚ</span>
                                <span class="text-2xl font-black text-white mt-1 block">${subs.length}</span>
                                <span class="text-[10px] text-emerald-400 font-bold">${activeCount} ንቁ (Active)</span>
                            </div>
                            <div class="p-4 rounded-2xl bg-cyan-950/30 border border-cyan-500/30">
                                <span class="text-xs text-cyan-300 font-semibold block">ሳምንታዊ (Weekly)</span>
                                <span class="text-2xl font-black text-white mt-1 block">${weeklyCount}</span>
                                <span class="text-[10px] text-cyan-400 font-bold">15% ቋሚ ቅናሽ</span>
                            </div>
                            <div class="p-4 rounded-2xl bg-blue-950/30 border border-blue-500/30">
                                <span class="text-xs text-blue-300 font-semibold block">የሁለት ሳምንት (Bi-Weekly)</span>
                                <span class="text-2xl font-black text-white mt-1 block">${biweeklyCount}</span>
                                <span class="text-[10px] text-blue-400 font-bold">10% ቋሚ ቅናሽ</span>
                            </div>
                            <div class="p-4 rounded-2xl bg-purple-950/30 border border-purple-500/30">
                                <span class="text-xs text-purple-300 font-semibold block">ወርሃዊ (Monthly)</span>
                                <span class="text-2xl font-black text-white mt-1 block">${monthlyCount}</span>
                                <span class="text-[10px] text-purple-400 font-bold">5% ቋሚ ቅናሽ</span>
                            </div>
                        </div>

                        <!-- SUBSCRIPTIONS TABLE -->
                        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-800/80 text-slate-400 uppercase text-[10px] tracking-wider">
                                        <tr>
                                            <th class="p-4">ደንበኛ</th>
                                            <th class="p-4">አገልግሎት</th>
                                            <th class="p-4">የእቅድ ዓይነት</th>
                                            <th class="p-4">ቅናሽ</th>
                                            <th class="p-4">የሚቀጥለው የጽዳት ቀን</th>
                                            <th class="p-4">ሁኔታ</th>
                                            <th class="p-4 text-right">እርምጃ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800">
                                        ${subs.length === 0 ? `
                                            <tr>
                                                <td colspan="7" class="p-8 text-center text-slate-500">
                                                    እስካሁን የተመዘገበ የደንበኝነት ተጠቃሚ የለም። ደንበኞች በድረ-ገጹ ወይም በቴሌግራም ሲመዘገቡ እዚህ ይታያሉ።
                                                </td>
                                            </tr>
                                        ` : subs.map(s => `
                                            <tr class="hover:bg-slate-800/40">
                                                <td class="p-4">
                                                    <span class="font-bold text-white block">${s.customer?.full_name || 'N/A'}</span>
                                                    <span class="text-slate-400 text-[11px]">📞 ${s.customer?.phone || ''}</span>
                                                </td>
                                                <td class="p-4 font-semibold text-slate-300">${s.service?.name_am || s.service?.name_en || 'መደበኛ ጽዳት'}</td>
                                                <td class="p-4">
                                                    <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold ${s.plan_type === 'weekly' ? 'bg-cyan-500/20 text-cyan-300' : (s.plan_type === 'biweekly' ? 'bg-blue-500/20 text-blue-300' : 'bg-purple-500/20 text-purple-300')}">
                                                        ${s.plan_type === 'weekly' ? '📅 ሳምንታዊ (Weekly)' : (s.plan_type === 'biweekly' ? '📅 የሁለት ሳምንት' : '📅 ወርሃዊ (Monthly)')}
                                                    </span>
                                                </td>
                                                <td class="p-4 font-black text-emerald-400">${s.discount_percentage}% OFF</td>
                                                <td class="p-4 font-bold text-cyan-400">${s.next_service_date || 'አልተወሰነም'}</td>
                                                <td class="p-4">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${s.status === 'active' ? 'bg-emerald-500/20 text-emerald-400' : (s.status === 'paused' ? 'bg-amber-500/20 text-amber-400' : 'bg-red-500/20 text-red-400')}">
                                                        ${s.status === 'active' ? 'ንቁ (Active)' : (s.status === 'paused' ? 'የቆመ (Paused)' : 'የተሰረዘ (Cancelled)')}
                                                    </span>
                                                </td>
                                                <td class="p-4 text-right space-x-1.5">
                                                    <button onclick="openDirectSmsModal('${s.customer?.phone || ''}', '${s.customer?.full_name || ''}')" class="px-2.5 py-1 bg-cyan-950/80 hover:bg-cyan-900 text-cyan-300 font-bold text-[11px] rounded-lg border border-cyan-500/30">📩 SMS</button>
                                                    ${s.status === 'active' ? `
                                                        <button onclick="updateSubscriptionStatus(${s.id}, 'paused')" class="px-2.5 py-1 bg-amber-950/70 hover:bg-amber-900 text-amber-300 font-bold text-[11px] rounded-lg border border-amber-500/30">አቁም (Pause)</button>
                                                    ` : (s.status === 'paused' ? `
                                                        <button onclick="updateSubscriptionStatus(${s.id}, 'active')" class="px-2.5 py-1 bg-emerald-950/70 hover:bg-emerald-900 text-emerald-300 font-bold text-[11px] rounded-lg border border-emerald-500/30">ቀጥል (Activate)</button>
                                                    ` : '')}
                                                    ${s.status !== 'cancelled' ? `
                                                        <button onclick="updateSubscriptionStatus(${s.id}, 'cancelled')" class="px-2.5 py-1 bg-red-950/60 hover:bg-red-900 text-red-300 font-bold text-[11px] rounded-lg border border-red-500/30">ሰርዝ</button>
                                                    ` : ''}
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (e) {
                container.innerHTML = `<div class="p-6 text-red-400">Failed to load subscriptions: ${e.message}</div>`;
            }
        }

        async function updateSubscriptionStatus(subId, newStatus) {
            const confirmMsg = newStatus === 'paused' ? 'ይህንን የደንበኝነት እቅድ ለጊዜው ማቆም ይፈልጋሉ?' : (newStatus === 'cancelled' ? 'ይህንን የደንበኝነት እቅድ ሙሉ ለሙሉ መሰረዝ ይፈልጋሉ?' : 'ይህንን የደንበኝነት እቅድ መልሰው ማግበር ይፈልጋሉ?');
            if (!confirm(confirmMsg)) return;

            try {
                const res = await apiFetch(`/api/subscriptions/${subId}/status`, {
                    method: 'PATCH',
                    body: JSON.stringify({ status: newStatus })
                });
                const data = await res.json();
                if (res.ok) {
                    alert('✅ ' + (data.message || 'የደንበኝነት እቅድ ሁኔታ ተዘምኗል!'));
                    loadActiveTab();
                } else {
                    alert('❌ ስህተት: ' + (data.message || 'ሁኔታውን ማዘመን አልተቻለም'));
                }
            } catch (e) {
                alert('የግንኙነት ስህተት: ' + e.message);
            }
        }

        // ==========================================
        // 5c. DIRECT SMS SENDER MODAL (Admin & Reception)
        // ==========================================
        function openDirectSmsModal(phone = '', name = '') {
            const container = document.getElementById('generic-modal-container');
            const cleanPhone = (phone || '').replace(/\s+/g, '');
            const safeName = name || 'ውድ ደንበኛ';

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-cyan-500/40 rounded-3xl max-w-lg w-full p-6 shadow-2xl">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-cyan-600/20 text-cyan-400 flex items-center justify-center">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-white">ለተጠቃሚው በቀጥታ SMS መላኪያ</h3>
                                    <p class="text-[11px] text-slate-400">የአስተዳዳሪ እና የሰራተኞች ይፋዊ የኤስኤምኤስ መላኪያ በይነገጽ</p>
                                </div>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <form onsubmit="submitDirectSms(event)" class="space-y-4 text-xs">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 font-bold mb-1">የላኪ ስልክ (የባለቤቱ / የድርጅቱ)</label>
                                    <input type="text" id="direct-sms-sender" value="0943854325" readonly class="w-full bg-slate-800/60 border border-slate-700/80 rounded-xl px-3.5 py-2.5 text-cyan-400 font-mono font-bold cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">የተቀባይ ደንበኛ ስልክ *</label>
                                    <input type="text" id="direct-sms-phone" required value="${cleanPhone || '0922998581'}" placeholder="0922998581" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-mono focus:border-cyan-500 focus:outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ፈጣን ዝግጁ አብነቶች (SMS Templates)</label>
                                <select onchange="applySmsTemplate(this.value, '${safeName}')" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-slate-300 focus:outline-none focus:border-cyan-500">
                                    <option value="">-- ፈጣን አብነት ይምረጡ --</option>
                                    <option value="confirm">✅ የቀጠሮ ማረጋገጫ (Appointment Confirmed)</option>
                                    <option value="dispatched">🚗 የቡድን ስምሪት እና የቀጥታ ካርታ (Dispatched on the way)</option>
                                    <option value="feedback">⭐ የአስተያየት መቀበያ እና ምስጋና (Feedback & Thanks)</option>
                                    <option value="subscription">🔄 የደንበኝነት አገልግሎት አስታዋሽ (Subscription Reminder)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የመልእክቱ ይዘት (SMS Message) *</label>
                                <textarea id="direct-sms-message" oninput="updateDirectSmsSimLink()" required rows="3" placeholder="መልእክትዎን እዚህ ይጻፉ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white focus:outline-none focus:border-cyan-500">ሰላም ${safeName}፣ የሜሽ ክሊኒንግ ቀጠሮዎ በትክክል ተረጋግጧል። በሰዓቱ እንገኛለን። እናመሰግናለን!</textarea>
                                <span class="text-[10px] text-slate-400 block mt-1">💡 በስልክዎ ላይ ሲሆኑ «በስልክህ SIM በነፃ ላክ» የሚለውን በመጫን ያለ ተጨማሪ ክፍያ መላክ ይችላሉ።</span>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-800">
                                <a id="btn-direct-sim-sms" href="sms:${cleanPhone || '0922998581'}?body=ሰላም ${encodeURIComponent(safeName)}፣ የሜሽ ክሊኒንግ ቀጠሮዎ በትክክል ተረጋግጧል። በሰዓቱ እንገኛለን። እናመሰግናለን!" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer text-xs">
                                    <span>📱 በስልክህ SIM በነፃ ላክ</span>
                                </a>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl hover:bg-slate-700">ሰርዝ</button>
                                    <button type="submit" id="btn-send-sms" class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-extrabold rounded-xl shadow-lg flex items-center gap-2">
                                        <i data-lucide="send" class="w-4 h-4"></i>
                                        <span>በሲስተም SMS ላክ</span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
            updateDirectSmsSimLink();
        }

        function updateDirectSmsSimLink() {
            const phone = document.getElementById('direct-sms-phone')?.value || '0922998581';
            const msg = document.getElementById('direct-sms-message')?.value || '';
            const link = document.getElementById('btn-direct-sim-sms');
            if (link) {
                link.href = `sms:${phone.replace(/\s+/g, '')}?body=${encodeURIComponent(msg)}`;
            }
        }

        function applySmsTemplate(type, customerName) {
            const msgBox = document.getElementById('direct-sms-message');
            if (!msgBox) return;
            if (type === 'confirm') {
                msgBox.value = `ሰላም ${customerName}፣ የሜሽ ክሊኒንግ ቀጠሮዎ በትክክል ተረጋግጧል። በሰዓቱ እንገኛለን። እናመሰግናለን!`;
            } else if (type === 'dispatched') {
                msgBox.value = `ሰላም ${customerName}፣ የሜሽ ክሊኒንግ ቡድን ወደ እርሶ በመጓዝ ላይ ይገኛል። በድረ-ገጻችን የቀጥታ ካርታ መከታተያ ይከታተሉን።`;
            } else if (type === 'feedback') {
                msgBox.value = `ሰላም ${customerName}፣ የሰጡን አስተያየት ደርሶናል፤ ተቀብለን አስፈላጊውን እርምጃ ወስደናል። ሜሽን ስለመረጡ እናመሰግናለን!`;
            } else if (type === 'subscription') {
                msgBox.value = `ሰላም ${customerName}፣ የደንበኝነት አገልግሎትዎ ቀጣይ የጽዳት ቀን ደርሷል። ቡድናችን በተያዘለት ሰዓት ይደርሳል።`;
            }
            updateDirectSmsSimLink();
        }

        async function submitDirectSms(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-send-sms');
            btn.disabled = true;
            btn.innerText = 'በመላክ ላይ...';

            const phone = document.getElementById('direct-sms-phone').value;
            const message = document.getElementById('direct-sms-message').value;

            try {
                const res = await apiFetch('/api/notifications/send-direct-sms', {
                    method: 'POST',
                    body: JSON.stringify({ phone, message })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    closeModal();
                    alert('✅ ' + (data.message || 'ኤስኤምኤሱ በተሳካ ሁኔታ ተልኳል!'));
                } else {
                    alert('❌ ስህተት: ' + (data.message || 'ኤስኤምኤስ መላክ አልተቻለም።'));
                }
            } catch (err) {
                alert('የግንኙነት ስህተት: ' + err.message);
            } finally {
                btn.disabled = false;
            }
        }

        // ==========================================
        // 6. CUSTOMERS MODULE (With Timeline)
        // ==========================================
        async function renderCustomersModule(container) {
            try {
                const res = await apiFetch('/api/customers');
                const data = await res.json();
                const customers = data.data || [];

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">የደንበኞች መረጃ ቋት (Customer CRM)</h2>
                                <p class="text-xs text-slate-400 mt-1">የደንበኞች ሙሉ መረጃ፣ የቀድሞ የአገልግሎት ታሪክ፣ ክፍያዎች እና ምርጫዎች።</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            ${customers.map(c => `
                                <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 space-y-3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider">${c.customer_code}</span>
                                            <h3 class="text-base font-bold text-white">${c.full_name}</h3>
                                        </div>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-800 text-slate-300">${c.customer_type}</span>
                                    </div>
                                    <p class="text-xs text-slate-400">📞 ${c.phone} | 📍 ${c.subcity || 'Addis Ababa'}</p>
                                    <div class="pt-3 border-t border-slate-800 flex justify-between items-center text-xs">
                                        <button onclick="openDirectSmsModal('${c.phone}', '${c.full_name}')" class="px-2.5 py-1 bg-cyan-950/80 hover:bg-cyan-900 text-cyan-300 font-bold text-[11px] rounded-lg border border-cyan-500/30 flex items-center gap-1">
                                            <i data-lucide="send" class="w-3 h-3"></i>
                                            <span>📩 SMS</span>
                                        </button>
                                        <button onclick="openCustomerProfileModal(${c.id})" class="text-cyan-400 font-bold hover:underline">የአገልግሎት ታሪክ እይ &rarr;</button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (e) {
                container.innerHTML = `<div class="p-6 text-red-400">Failed to load customers: ${e.message}</div>`;
            }
        }

        // ==========================================
        // 7. FINANCE & EXPENSES MODULE
        // ==========================================
        async function renderFinanceModule(container) {
            try {
                const currentPeriod = window._financePeriod || 'all_time';
                const [reportRes, expRes] = await Promise.all([
                    apiFetch('/api/finance/profit-report?period=' + currentPeriod),
                    apiFetch('/api/finance/expenses?per_page=50')
                ]);
                const data = await reportRes.json();
                const expData = await expRes.json();
                const expensesList = expData.data || (Array.isArray(expData) ? expData : []);

                const categoryLabels = {
                    chemicals: 'ኬሚካሎች እና ሳሙናዎች',
                    fuel: 'ነዳጅ / ትራንስፖርት',
                    materials: 'የፅዳት መገልገያ እቃዎች',
                    employee_payments: '💵 የሰራተኞች ደመወዝ / ክፍያ',
                    equipment_repair: 'የማሽን ጥገና',
                    rent: 'የቢሮ ኪራይ',
                    marketing: 'ማስታወቂያ እና ፕሮሞሽን',
                    utilities: 'መብራት / ውሃ / ኢንተርኔት',
                    other: 'ሌሎች ወጪዎች'
                };

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">ፋይናንስ፣ ወጪዎች እና የተጣራ ትርፍ</h2>
                                <p class="text-xs text-slate-400 mt-1">የቀጥታ ገቢ፣ የሰራተኞች ደመወዝ፣ የስራ ማስኬጃ ወጪዎች እና የተጣራ ትርፍ ስሌት።</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Period Filter -->
                                <div class="flex items-center gap-2 bg-slate-900 border border-slate-700 rounded-xl px-2.5 py-1.5 text-xs shadow">
                                    <i data-lucide="calendar" class="w-3.5 h-3.5 text-cyan-400"></i>
                                    <span class="text-slate-400 font-bold">ጊዜ፡</span>
                                    <select onchange="window._financePeriod = this.value; renderFinanceModule(document.getElementById('app-viewport'));" class="bg-slate-800 border border-slate-700 text-white font-bold rounded-lg px-2.5 py-1">
                                        <option value="all_time" ${currentPeriod === 'all_time' ? 'selected' : ''}>ሁልጊዜ (ጠቅላላ)</option>
                                        <option value="this_month" ${currentPeriod === 'this_month' ? 'selected' : ''}>የዚህ ወር</option>
                                        <option value="this_week" ${currentPeriod === 'this_week' ? 'selected' : ''}>የዚህ ሳምንት</option>
                                        <option value="today" ${currentPeriod === 'today' ? 'selected' : ''}>የዛሬ</option>
                                        <option value="this_year" ${currentPeriod === 'this_year' ? 'selected' : ''}>የዚህ ዓመት</option>
                                    </select>
                                </div>
                                <button onclick="openPayrollModal()" class="px-4 py-2.5 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2 cursor-pointer transition-all shadow-amber-950/40">
                                    <i data-lucide="banknote" class="w-4 h-4"></i>
                                    <span>💵 ደመወዝ ክፈል (Payroll)</span>
                                </button>
                                <button onclick="openRecordExpenseModal()" class="px-4 py-2.5 bg-red-600/80 hover:bg-red-600 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2 cursor-pointer transition-all">
                                    <i data-lucide="minus-circle" class="w-4 h-4"></i>
                                    <span>- ወጪ መዝግብ</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div class="p-6 rounded-3xl bg-emerald-950/30 border border-emerald-500/30">
                                <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider block">የተረጋገጠ ጠቅላላ ገቢ</span>
                                <span class="text-3xl font-black text-white mt-2 block">${parseFloat(data.revenue || 0).toLocaleString()} ETB</span>
                            </div>
                            <div class="p-6 rounded-3xl bg-red-950/30 border border-red-500/30">
                                <span class="text-xs font-bold text-red-400 uppercase tracking-wider block">አጠቃላይ ወጪ (ደመወዝን ጨምሮ)</span>
                                <span class="text-3xl font-black text-white mt-2 block">${parseFloat(data.expenses || 0).toLocaleString()} ETB</span>
                            </div>
                            <div class="p-6 rounded-3xl bg-cyan-950/30 border border-cyan-500/30">
                                <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider block">የተጣራ ትርፍ (${data.profit_margin || 0}%)</span>
                                <span class="text-3xl font-black text-cyan-300 mt-2 block">${parseFloat(data.net_profit || 0).toLocaleString()} ETB</span>
                            </div>
                        </div>

                        <!-- Expense Breakdown by Category -->
                        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800">
                            <h4 class="text-sm font-bold text-white mb-4">የወጪዎች ዝርዝር በየምድቡ</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                ${(data.expense_breakdown || []).map(b => `
                                    <div class="p-3.5 rounded-2xl ${b.category === 'employee_payments' ? 'bg-amber-950/40 border border-amber-500/40' : 'bg-slate-800 border border-slate-700'}">
                                        <span class="text-xs ${b.category === 'employee_payments' ? 'text-amber-300 font-extrabold' : 'text-slate-400 font-bold'} uppercase block">${categoryLabels[b.category] || b.category}</span>
                                        <span class="text-lg font-black text-white mt-1 block">${parseFloat(b.total_amount).toLocaleString()} ETB</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>

                        <!-- Recent Expenses & Payroll Table -->
                        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                <div>
                                    <h4 class="text-base font-extrabold text-white flex items-center gap-2">
                                        <i data-lucide="receipt" class="w-4 h-4 text-cyan-400"></i>
                                        <span>የቅርብ ጊዜ የተመዘገቡ ወጪዎች እና የደመወዝ ክፍያዎች</span>
                                    </h4>
                                    <p class="text-xs text-slate-400 mt-0.5">የተከፈሉ ደመወዞች እና የስራ ማስኬጃ ወጪዎች የቀጥታ መዝገብ።</p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                    ${expensesList.length} ወጪዎች
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="text-slate-400 border-b border-slate-800 text-[11px] uppercase tracking-wider">
                                            <th class="py-3 px-3">ቀን</th>
                                            <th class="py-3 px-3">የወጪ ምድብ</th>
                                            <th class="py-3 px-3">ማብራሪያ / ሰራተኛ</th>
                                            <th class="py-3 px-3">የመዘገበው</th>
                                            <th class="py-3 px-3 text-right">የገንዘብ መጠን</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60">
                                        ${expensesList.length === 0 ? `
                                            <tr>
                                                <td colspan="5" class="py-8 text-center text-slate-500 italic">እስካሁን የተመዘገበ ወጪ የለም።</td>
                                            </tr>
                                        ` : expensesList.map(exp => `
                                            <tr class="hover:bg-slate-800/40 transition-colors">
                                                <td class="py-3 px-3 font-mono text-slate-300">
                                                    <span class="block font-bold text-white">${exp.eth_date || exp.date}</span>
                                                    <span class="text-[10px] text-slate-500">${exp.date}</span>
                                                </td>
                                                <td class="py-3 px-3">
                                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold border ${exp.category === 'employee_payments' ? 'bg-amber-950/80 text-amber-300 border-amber-500/40' : 'bg-slate-800 text-slate-300 border-slate-700'}">
                                                        ${categoryLabels[exp.category] || exp.category}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-3 font-semibold text-white">
                                                    ${exp.description || '—'}
                                                    ${exp.reference_number ? `<span class="block text-[10px] font-mono text-slate-400 mt-0.5">Ref: ${exp.reference_number}</span>` : ''}
                                                </td>
                                                <td class="py-3 px-3 text-slate-400">
                                                    ${exp.entered_by?.name || 'አድሚን'}
                                                </td>
                                                <td class="py-3 px-3 text-right font-black ${exp.category === 'employee_payments' ? 'text-amber-400' : 'text-red-400'} text-sm font-mono">
                                                    -${parseFloat(exp.amount).toLocaleString()} ETB
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (e) {
                container.innerHTML = `<div class="p-6 text-red-400">Failed to load finance reports: ${e.message}</div>`;
            }
        }

        // ==========================================
        // MODALS (Create Order, Log Expense, View Profile)
        // ==========================================
        async function openCreateOrderModal() {
            // Fetch live services if not cached
            if (!window._appServicesList || window._appServicesList.length === 0) {
                try {
                    const res = await apiFetch('/api/services');
                    if (res.ok) {
                        window._appServicesList = await res.json();
                    }
                } catch (e) {
                    console.error('Failed to load services:', e);
                }
            }

            const services = (window._appServicesList && window._appServicesList.length > 0) ? window._appServicesList : [
                { id: 1, code: 'sofa', name_am: 'የሶፋ ጥልቅ ፅዳት', name_en: 'Sofa Cleaning', base_price: 350, unit: 'ወንበር' },
                { id: 2, code: 'carpet', name_am: 'የምንጣፍ እጥበት', name_en: 'Carpet Cleaning', base_price: 80, unit: 'ካሬ' },
                { id: 3, code: 'mattress', name_am: 'የፍራሽ ሳኒታይዜሽን', name_en: 'Mattress Sanitization', base_price: 600, unit: 'ፍራሽ' },
                { id: 4, code: 'glass', name_am: 'የመስታወት እና ህንፃ ፅዳት', name_en: 'Window & Glass', base_price: 70, unit: 'ካሬ' },
                { id: 5, code: 'home', name_am: 'የመኖሪያ ቤት ሙሉ ጥልቅ ፅዳት', name_en: 'Home Deep Cleaning', base_price: 2500, unit: 'ስራ' },
                { id: 6, code: 'office', name_am: 'የቢሮ እና ተቋማት ፅዳት', name_en: 'Office Cleaning', base_price: 3500, unit: 'ስራ' },
            ];

            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-xl w-full p-6 shadow-2xl my-8">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-white">አዲስ የፅዳት ትዕዛዝ መመዝገቢያ</h3>
                                <p class="text-[11px] text-slate-400">የደንበኛ መረጃ፣ የቀጠሮ ቀን እና የሚፀዳ አገልግሎት ይምረጡ</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white">&times;</button>
                        </div>
                        <form onsubmit="submitAdminOrder(event)" class="space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">የደንበኛ ሙሉ ስም *</label>
                                    <input type="text" id="mo-cust-name" required placeholder="የደንበኛ ስም" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">ስልክ ቁጥር *</label>
                                    <input type="tel" id="mo-cust-phone" required placeholder="09..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none font-mono">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">ክፍለ ከተማ *</label>
                                    <select id="mo-subcity" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none cursor-pointer">
                                        <option value="">-- ክፍለ ከተማ ይምረጡ --</option>
                                        <option value="Bole">ቦሌ (Bole)</option>
                                        <option value="Yeka">የካ (Yeka)</option>
                                        <option value="Kirkos">ቂርቆስ (Kirkos)</option>
                                        <option value="Arada">አራዳ (Arada)</option>
                                        <option value="Lideta">ልደታ (Lideta)</option>
                                        <option value="Gulele">ጉለሌ (Gulele)</option>
                                        <option value="Addis Ketema">አዲስ ከተማ (Addis Ketema)</option>
                                        <option value="Kolfe Keranyo">ኮልፌ ቀራኒዮ (Kolfe Keranyo)</option>
                                        <option value="Nifas Silk-Lafto">ንፋስ ስልክ ላፍቶ (Nifas Silk-Lafto)</option>
                                        <option value="Akaki Kality">አቃቂ ቃሊቲ (Akaki Kality)</option>
                                        <option value="Lemi Kura">ለሚ ኩራ (Lemi Kura)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">ሙሉ አድራሻ / ሰፈር *</label>
                                    <input type="text" id="mo-address" required placeholder="ለምሳሌ፡ ቦሌ መድሃኒዓለም፣ የቤት ቁጥር..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">የቀጠሮ ቀን *</label>
                                    <input type="date" id="mo-date" required onchange="document.getElementById('mo-eth-hint').textContent='🗓 ' + EC.formatEth(this.value);" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                                    <p id="mo-eth-hint" class="text-[10px] text-cyan-400 font-bold mt-1">🗓 — ዓ.ም</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">የቀጠሮ ሰዓት (ጥዋት / ከሰዓት ብቻ) *</label>
                                    <select id="mo-slot" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none cursor-pointer">
                                        <option value="morning">🌅 ጥዋት (2:00–6:00 ጠዋቱ ETH • 8am–12pm)</option>
                                        <option value="afternoon">☀️ ከሰዓት (7:00–11:00 ከሰዓቱ ETH • 1pm–5pm)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Services Dropdown and Add Service Action -->
                            <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-slate-300 uppercase tracking-wider">የሚፀዱ እቃዎች / አገልግሎት ምርጫ (Dropdown) *</span>
                                    <button type="button" onclick="openAddNewServiceModal()" class="text-cyan-400 hover:text-cyan-300 text-[11px] font-extrabold flex items-center gap-1 cursor-pointer">
                                        <span>+ አዲስ የፅዳት አገልግሎት ፍጠር</span>
                                    </button>
                                </div>
                                <div>
                                    <select id="mo-service-select" onchange="onOrderServiceSelected(this)" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-bold cursor-pointer">
                                        <option value="">-- የፅዳት አገልግሎት ይምረጡ --</option>
                                        ${services.map(s => {
                                            const sName = s.name_am || s.name_en;
                                            const sPrice = parseFloat(s.base_price || 0);
                                            const sUnit = s.unit || 'ስራ';
                                            const sIcon = s.icon || '🧹';
                                            return `<option value="${s.id}" data-name="${sName}" data-price="${sPrice}" data-unit="${sUnit}">${sIcon} ${sName} — ${sPrice.toLocaleString()} ብር / ${sUnit}</option>`;
                                        }).join('')}
                                    </select>
                                </div>
                                <div class="grid grid-cols-3 gap-2 pt-1">
                                    <div>
                                        <label class="block text-[10px] text-slate-400 mb-0.5">የአገልግሎቱ ስም</label>
                                        <input type="text" id="mo-item-name" value="የሶፋ ጥልቅ ፅዳት" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white font-medium">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-400 mb-0.5">ብዛት (<span id="mo-unit-hint" class="text-cyan-400 font-bold">ወንበር</span>)</label>
                                        <input type="number" id="mo-item-qty" value="5" min="1" step="any" placeholder="Qty" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white font-mono font-bold">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-400 mb-0.5">ነጠላ ዋጋ (ብር)</label>
                                        <input type="number" id="mo-item-price" value="350" min="0" step="any" placeholder="Price" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white font-mono font-bold">
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-5 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white text-xs font-bold rounded-xl shadow-lg">ትዕዛዝ መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            const todayIso = new Date().toISOString().split('T')[0];
            document.getElementById('mo-date').value = todayIso;
            document.getElementById('mo-eth-hint').textContent = '🗓 ' + EC.formatEth(todayIso);

            // Pre-select first service
            const selectEl = document.getElementById('mo-service-select');
            if (selectEl && selectEl.options.length > 1) {
                selectEl.selectedIndex = 1;
                onOrderServiceSelected(selectEl);
            }
        }

        function onOrderServiceSelected(sel) {
            const opt = sel.options[sel.selectedIndex];
            if (!opt || !opt.value) return;
            const name = opt.getAttribute('data-name');
            const price = opt.getAttribute('data-price');
            const unit = opt.getAttribute('data-unit') || 'ስራ';
            if (name) document.getElementById('mo-item-name').value = name;
            if (price) document.getElementById('mo-item-price').value = price;
            const hint = document.getElementById('mo-unit-hint');
            if (hint) hint.textContent = unit;
        }

        function openAddNewServiceModal() {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-[60] bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white">✨ አዲስ የፅዳት አገልግሎት መመዝገቢያ</h3>
                                <p class="text-[11px] text-slate-400">በዳታቤዝ ገብቶ በቦት፣ በዌብሳይት፣ በሚኒ-አፕ እና እዚህ በአንድ ጊዜ ይታያል!</p>
                            </div>
                            <button onclick="openCreateOrderModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitAddNewService(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-400 mb-1">የአገልግሎቱ ስም በአማርኛ *</label>
                                <input type="text" id="new-svc-name-am" required placeholder="ለምሳሌ፡ የጋርተን እና የመጋረጃ እጥበት" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">Service Name (English) *</label>
                                <input type="text" id="new-svc-name-en" required placeholder="e.g. Curtain & Blind Cleaning" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">መለያ ኮድ (Code) *</label>
                                    <input type="text" id="new-svc-code" required placeholder="ለምሳሌ፡ curtain_wash" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono">
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">መነሻ ዋጋ (ብር) *</label>
                                    <input type="number" id="new-svc-price" required min="0" placeholder="ለምሳሌ፡ 200" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">መለኪያ (Unit) *</label>
                                    <select id="new-svc-unit" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold cursor-pointer">
                                        <option value="ወንበር">ወንበር (Chair/Seat)</option>
                                        <option value="ካሬ">ካሬ ሜትር (Sq Meter)</option>
                                        <option value="ፍራሽ">ፍራሽ (Mattress)</option>
                                        <option value="ስራ">ሙሉ ስራ (Job/Task)</option>
                                        <option value="ክፍል">ክፍል (Room)</option>
                                        <option value="መኪና">መኪና (Vehicle)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">አዶ (Icon)</label>
                                    <select id="new-svc-icon" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-base cursor-pointer">
                                        <option value="✨">✨ ኮከብ (Sparkle)</option>
                                        <option value="🛋">🛋 ሶፋ (Sofa)</option>
                                        <option value="🧶">🧶 ምንጣፍ (Carpet)</option>
                                        <option value="🛏">🛏 ፍራሽ (Mattress)</option>
                                        <option value="🪟">🪟 መስታወት (Window)</option>
                                        <option value="🏠">🏠 ቤት (House)</option>
                                        <option value="🏢">🏢 ህንፃ/ቢሮ (Building)</option>
                                        <option value="🚗">🚗 ተሽከርካሪ (Car)</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">አጭር መግለጫ (አማርኛ)</label>
                                <textarea id="new-svc-desc" rows="2" placeholder="ስለ አገልግሎቱ አጭር ማብራሪያ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                                <button type="button" onclick="openCreateOrderModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ተመለስ</button>
                                <button type="submit" id="btn-submit-service" class="px-5 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-lg">አገልግሎቱን መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
        }

        async function submitAddNewService(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-service');
            btn.disabled = true;
            btn.textContent = 'በመመዝገብ ላይ...';

            const payload = {
                code: document.getElementById('new-svc-code').value.trim(),
                name_am: document.getElementById('new-svc-name-am').value.trim(),
                name_en: document.getElementById('new-svc-name-en').value.trim(),
                base_price: parseFloat(document.getElementById('new-svc-price').value),
                unit: document.getElementById('new-svc-unit').value,
                icon: document.getElementById('new-svc-icon').value,
                description_am: document.getElementById('new-svc-desc').value.trim(),
                description_en: document.getElementById('new-svc-name-en').value.trim(),
                is_active: true,
                sort_order: (window._appServicesList?.length || 6) + 1,
            };

            try {
                const res = await apiFetch('/api/services', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                const result = await res.json();
                if (res.ok) {
                    alert('✓ አዲሱ የፅዳት አገልግሎት በተሳካ ሁኔታ ተመዝግቧል! አሁን በቴሌግራም ቦት፣ በዌብሳይት፣ በሚኒ-አፕ እና በትዕዛዝ መመዝገቢያ ላይ በአንድ ጊዜ ይታያል።');
                    if (!window._appServicesList) window._appServicesList = [];
                    window._appServicesList.push(result.service || payload);
                    openCreateOrderModal();
                } else {
                    alert('ስህተት፡ ' + (result.message || JSON.stringify(result.errors || 'አልተመዘገበም')));
                    btn.disabled = false;
                    btn.textContent = 'አገልግሎቱን መዝግብ';
                }
            } catch (err) {
                alert('የኔትወርክ ስህተት፡ ' + err.message);
                btn.disabled = false;
                btn.textContent = 'አገልግሎቱን መዝግብ';
            }
        }

        async function submitAdminOrder(e) {
            e.preventDefault();
            const payload = {
                customer_name: document.getElementById('mo-cust-name').value,
                customer_phone: document.getElementById('mo-cust-phone').value,
                subcity: document.getElementById('mo-subcity').value,
                address: document.getElementById('mo-address').value,
                appointment_date: document.getElementById('mo-date').value,
                appointment_time_slot: document.getElementById('mo-slot').value,
                items: [
                    {
                        item_name: document.getElementById('mo-item-name').value,
                        quantity: parseFloat(document.getElementById('mo-item-qty').value),
                        unit_price: parseFloat(document.getElementById('mo-item-price').value),
                    }
                ]
            };

            // Enqueue via offline engine (optimistic save + sync)
            await window.meashOffline.enqueue('order', 'create', payload);
            closeModal();
            loadActiveTab();
        }

        function openRecordExpenseModal() {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
                            <h3 class="text-base font-bold text-white">የስራ ማስኬጃ ወጪ መመዝገቢያ</h3>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white">&times;</button>
                        </div>
                        <form onsubmit="submitExpense(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-400 mb-1">የወጪ ምድብ *</label>
                                <select id="exp-category" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                    <option value="chemicals">ኬሚካሎች እና ሳሙናዎች</option>
                                    <option value="fuel">ነዳጅ / ትራንስፖርት</option>
                                    <option value="materials">የፅዳት መገልገያ እቃዎች</option>
                                    <option value="employee_payments">የሰራተኞች ክፍያ</option>
                                    <option value="equipment_repair">የማሽን ጥገና</option>
                                    <option value="rent">የቢሮ ኪራይ</option>
                                    <option value="marketing">ማስታወቂያ እና ፕሮሞሽን</option>
                                    <option value="other">ሌሎች ወጪዎች</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">የገንዘብ መጠን (ብር) *</label>
                                <input type="number" id="exp-amount" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">ዝርዝር ማብራሪያ *</label>
                                <textarea id="exp-desc" required rows="2" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-3 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white font-bold rounded-xl">ወጪ መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
        }

        async function submitExpense(e) {
            e.preventDefault();
            const payload = {
                category: document.getElementById('exp-category').value,
                amount: parseFloat(document.getElementById('exp-amount').value),
                description: document.getElementById('exp-desc').value,
                date: new Date().toISOString().split('T')[0],
            };
            await window.meashOffline.enqueue('expense', 'create', payload);
            closeModal();
            loadActiveTab();
        }

        function closeModal() {
            document.getElementById('generic-modal-container').innerHTML = '';
        }

        function showConflictModal(conflict) {
            activeConflict = conflict;
            document.getElementById('conflict-local-val').innerText = JSON.stringify(conflict.client_state);
            document.getElementById('conflict-server-val').innerText = JSON.stringify(conflict.server_state);
            document.getElementById('conflict-modal').classList.remove('hidden');
        }

        async function resolveCurrentConflict(choice) {
            if (activeConflict) {
                await apiFetch(`/api/sync/conflicts/${activeConflict.conflict_id}/resolve`, {
                    method: 'POST',
                    body: JSON.stringify({ resolution: choice }),
                });
            }
            document.getElementById('conflict-modal').classList.add('hidden');
            activeConflict = null;
            loadActiveTab();
        }

        // ==========================================
        // ==========================================
        // 8. TEAMS & DISPATCH MODULE (Cleaners & Staff Directory)
        // ==========================================
        async function renderTeamsModule(container) {
            try {
                const [teamsRes, empRes] = await Promise.all([
                    apiFetch('/api/teams'),
                    apiFetch('/api/employees')
                ]);
                const teamsData = await teamsRes.json();
                const empData = await empRes.json();
                
                const teams = Array.isArray(teamsData) ? teamsData : (teamsData.data || []);
                const employees = Array.isArray(empData) ? empData : (empData.data || []);

                const roleBadges = {
                    owner: 'bg-purple-950/80 text-purple-300 border-purple-800',
                    reception: 'bg-blue-950/80 text-blue-300 border-blue-800',
                    cleaner: 'bg-emerald-950/80 text-emerald-300 border-emerald-800',
                    sales: 'bg-amber-950/80 text-amber-300 border-amber-800',
                    finance: 'bg-cyan-950/80 text-cyan-300 border-cyan-800'
                };

                const roleLabels = {
                    owner: 'ዋና ስራ አስኪያጅ',
                    reception: 'ሪሴፕሽን & ሽያጭ',
                    cleaner: 'የፅዳት ባለሙያ',
                    sales: 'የውጭ ሽያጭ',
                    finance: 'ፋይናንስ'
                };

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-8">
                        <!-- Top Header with Actions -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-3xl bg-slate-900 border border-slate-800">
                            <div>
                                <h2 class="text-2xl font-black text-white flex items-center gap-2.5">
                                    <i data-lucide="users" class="w-6 h-6 text-cyan-400"></i>
                                    <span>የሰራተኞች እና የፅዳት ባለሙያዎች አስተዳደር</span>
                                </h2>
                                <p class="text-xs text-slate-400 mt-1">የሰራተኞች ምዝገባ፣ የደመወዝ ክፍያ (Payroll) እና የመስክ ስራ ስምሪት ማስተዳደሪያ።</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2.5">
                                <button onclick="openPayrollModal()" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold text-xs flex items-center gap-2 shadow-lg shadow-amber-950/50 transition-all cursor-pointer">
                                    <i data-lucide="banknote" class="w-4 h-4"></i>
                                    <span>+ 💵 ደመወዝ ክፈል (Payroll)</span>
                                </button>
                                <button onclick="openAddEmployeeModal()" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-xs flex items-center gap-2 shadow-lg shadow-emerald-950/50 transition-all cursor-pointer">
                                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                                    <span>+ አዲስ ሰራተኛ መዝግብ</span>
                                </button>
                                <button onclick="openCreateTeamModal()" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold text-xs flex items-center gap-2 shadow-lg shadow-cyan-950/50 transition-all cursor-pointer">
                                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                    <span>+ አዲስ የፅዳት ቡድን ፍጠር</span>
                                </button>
                            </div>
                        </div>

                        <!-- 1. Cleaning Teams Grid -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                                    <span>የመስክ የፅዳት ቡድኖች (Cleaning Teams)</span>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-cyan-950 text-cyan-400 border border-cyan-800">${teams.length} ቡድኖች</span>
                                </h3>
                            </div>

                            ${teams.length === 0 ? `
                                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800 text-center space-y-3">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-800 text-cyan-400 flex items-center justify-center mx-auto text-xl">👥</div>
                                    <p class="text-sm font-bold text-white">እስካሁን የተፈጠረ የፅዳት ቡድን የለም</p>
                                    <p class="text-xs text-slate-400 max-w-sm mx-auto">ከላይ ያለውን "+ አዲስ የፅዳት ቡድን ፍጠር" የሚለውን በመጫን ቡድን መፍጠር ይችላሉ፤ ወይም ሰራተኞችን በቀጥታ ለትዕዛዞች መመደብ ይችላሉ።</p>
                                    <button onclick="openCreateTeamModal()" class="px-4 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs">ቡድን ፍጠር</button>
                                </div>
                            ` : `
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                    ${teams.map(t => `
                                        <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 hover:border-cyan-500/40 transition-all space-y-4 shadow-xl">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <span class="text-[10px] font-mono font-bold text-cyan-400 bg-cyan-950/80 px-2 py-0.5 rounded border border-cyan-800 uppercase tracking-wider">${t.team_name}</span>
                                                    <h4 class="text-lg font-extrabold text-white mt-1">${t.team_name}</h4>
                                                </div>
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase border ${t.status === 'active' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700'}">
                                                    ${t.status === 'active' ? '● ዝግጁ / ንቁ' : t.status}
                                                </span>
                                            </div>
                                            <div class="space-y-2 text-xs text-slate-400 bg-slate-950/50 p-3.5 rounded-2xl border border-slate-800/80">
                                                <div class="flex items-center justify-between">
                                                    <span>👤 የቡድን መሪ:</span>
                                                    <strong class="text-white">${t.leader?.name || 'አልተመደበም'}</strong>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span>📞 ስልክ:</span>
                                                    <span class="text-cyan-300 font-mono">${t.phone || t.leader?.phone || '—'}</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span>👥 አባላት:</span>
                                                    <span class="text-cyan-400 font-bold">${t.members?.length || 1} አባላት</span>
                                                </div>
                                            </div>
                                            <div class="pt-2 flex items-center justify-between text-xs">
                                                <span class="text-slate-400 font-semibold flex items-center gap-1">
                                                    <i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-400"></i>
                                                    <span>${t.active_jobs_count || 0} ንቁ ስራዎች</span>
                                                </span>
                                                <button onclick="openTeamLiveLocationMap(${t.id}, '${(t.team_name || 'የፅዳት ቡድን').replace(/'/g, "\\'")}', '${(t.leader?.name || 'አልተመደበም').replace(/'/g, "\\'")}', '${(t.phone || t.leader?.phone || '').replace(/'/g, "\\'")}', ${t.members?.length || 1}, ${t.active_jobs_count || 0}, ${t.current_latitude || 9.025}, ${t.current_longitude || 38.746})" class="px-3 py-1.5 rounded-xl bg-cyan-950/80 hover:bg-cyan-900 border border-cyan-500/40 text-cyan-300 font-bold flex items-center gap-1.5 transition-colors cursor-pointer">
                                                    <i data-lucide="navigation-2" class="w-3.5 h-3.5 text-cyan-400"></i>
                                                    <span>የቀጥታ ካርታ</span>
                                                </button>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            `}
                        </div>

                        <!-- 2. Staff Directory Table -->
                        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-800">
                                <div>
                                    <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                                        <i data-lucide="users" class="w-4 h-4 text-emerald-400"></i>
                                        <span>የሰራተኞች እና የፅዳት ባለሙያዎች መዝገብ (Staff Directory)</span>
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">በሜሽ ክሊኒንግ ሲስተም ውስጥ የተመዘገቡ ሁሉም ሰራተኞች፣ የስራ ድርሻቸው እና የደመወዝ ክፍያ።</p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700 w-fit">
                                    ጠቅላላ፡ ${employees.length} ሰራተኞች
                                </span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="text-slate-400 border-b border-slate-800 text-[11px] uppercase tracking-wider">
                                            <th class="py-3 px-3">ሰራተኛ</th>
                                            <th class="py-3 px-3">የስራ ድርሻ (Role)</th>
                                            <th class="py-3 px-3">ስልክ</th>
                                            <th class="py-3 px-3">ኢሜይል</th>
                                            <th class="py-3 px-3">ሁኔታ</th>
                                            <th class="py-3 px-3 text-right">ክፍያ / Payroll</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60">
                                        ${employees.map(e => `
                                            <tr class="hover:bg-slate-800/40 transition-colors">
                                                <td class="py-3 px-3 font-bold text-white flex items-center gap-2.5">
                                                    <div class="w-7 h-7 rounded-lg bg-slate-800 text-cyan-400 font-extrabold flex items-center justify-center text-[11px]">
                                                        ${(e.name || 'S').slice(0, 2).toUpperCase()}
                                                    </div>
                                                    <span>${e.name}</span>
                                                </td>
                                                <td class="py-3 px-3">
                                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold border uppercase ${roleBadges[e.role] || 'bg-slate-800 text-slate-300 border-slate-700'}">
                                                        ${roleLabels[e.role] || e.role}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-3 font-mono text-slate-300">${e.phone || '—'}</td>
                                                <td class="py-3 px-3 font-mono text-slate-400">${e.email}</td>
                                                <td class="py-3 px-3">
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                        <span>ንቁ (Active)</span>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-3 text-right">
                                                    <button onclick="openPayrollModal(${e.id}, '${e.name.replace(/'/g, "\\'")}')" class="px-3 py-1.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 font-bold text-[11px] inline-flex items-center gap-1.5 cursor-pointer transition-all shadow-sm">
                                                        <i data-lucide="banknote" class="w-3.5 h-3.5 text-amber-400"></i>
                                                        <span>💵 ደመወዝ ክፈል</span>
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                container.innerHTML = `<div class="p-6 text-red-400">Failed to load cleaning teams: ${err.message}</div>`;
            }
        }

        // ==========================================
        // ADD EMPLOYEE MODAL & SUBMIT
        // ==========================================
        function openAddEmployeeModal() {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white flex items-center gap-2">
                                    <i data-lucide="user-plus" class="w-4 h-4 text-emerald-400"></i>
                                    <span>አዲስ ሰራተኛ መዝግብ</span>
                                </h3>
                                <p class="text-[11px] text-slate-400">ወደ ሜሽ ክሊኒንግ ሲስተም አዲስ ሰራተኛ ያስገቡ።</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitNewEmployee(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ሙሉ ስም *</label>
                                <input type="text" id="new-emp-name" required placeholder="ለምሳሌ፡ ዮናስ አበበ" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white">
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ኢሜይል (ለመግቢያ የሚያገለግል) *</label>
                                <input type="email" id="new-emp-email" required placeholder="yonas@meash.com" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-mono">
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ስልክ ቁጥር *</label>
                                <input type="tel" id="new-emp-phone" required placeholder="0911223344" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-mono">
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የስራ ድርሻ (Role) *</label>
                                <select id="new-emp-role" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white">
                                    <option value="cleaner">🧹 የፅዳት ባለሙያ (Cleaner)</option>
                                    <option value="reception">📞 ሪሴፕሽን & ሽያጭ (Reception)</option>
                                    <option value="sales">💼 የውጭ ሽያጭ (Outdoor Sales)</option>
                                    <option value="finance">💰 ፋይናንስ & ሂሳብ (Finance)</option>
                                    <option value="owner">👑 ዋና ስራ አስኪያጅ (Owner)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የይለፍ ቃል (Password) *</label>
                                <input type="password" id="new-emp-password" required minlength="6" placeholder="ቢያንስ 6 ፊደላት" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white">
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" id="btn-save-emp" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl flex items-center gap-1.5">
                                    <span>መዝግብ</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function submitNewEmployee(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-emp');
            btn.disabled = true;
            btn.innerText = 'በመመዝገብ ላይ...';

            const payload = {
                name: document.getElementById('new-emp-name').value.trim(),
                email: document.getElementById('new-emp-email').value.trim(),
                phone: document.getElementById('new-emp-phone').value.trim(),
                role: document.getElementById('new-emp-role').value,
                password: document.getElementById('new-emp-password').value,
            };

            try {
                const res = await apiFetch('/api/employees', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert('✅ አዲሱ ሰራተኛ በተሳካ ሁኔታ ተመዝግቧል!');
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('ስህተት: ' + (data.message || 'ሰራተኛውን መመዝገብ አልተቻለም'));
                }
            } catch (err) {
                alert('የሰርቨር ግንኙነት ችግር: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerText = 'መዝግብ';
            }
        }

        // ==========================================
        // CREATE CLEANING TEAM MODAL & SUBMIT
        // ==========================================
        async function openCreateTeamModal() {
            const container = document.getElementById('generic-modal-container');
            const empRes = await apiFetch('/api/employees?role=cleaner');
            const empData = await empRes.json();
            const cleaners = Array.isArray(empData) ? empData : (empData.data || []);

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white flex items-center gap-2">
                                    <i data-lucide="plus-circle" class="w-4 h-4 text-cyan-400"></i>
                                    <span>አዲስ የፅዳት ቡድን ፍጠር</span>
                                </h3>
                                <p class="text-[11px] text-slate-400">አዲስ የመስክ የፅዳት ቡድን እና መሪ ይመድቡ።</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitNewTeam(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የቡድን ስም *</label>
                                <input type="text" id="new-team-name" required placeholder="ለምሳሌ፡ Team Delta" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white">
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የቡድን መሪ (Team Leader)</label>
                                <select id="new-team-leader" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white">
                                    <option value="">-- መሪ ይምረጡ (አማራጭ) --</option>
                                    ${cleaners.map(c => `<option value="${c.id}">${c.name} (${c.phone || c.email})</option>`).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የቡድኑ ስልክ</label>
                                <input type="tel" id="new-team-phone" placeholder="0911000004" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-mono">
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ተጨማሪ ማስታወሻ</label>
                                <textarea id="new-team-notes" rows="2" placeholder="የቡድኑ ዞን ወይም ተጨማሪ መረጃ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" id="btn-save-team" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl flex items-center gap-1.5">
                                    <span>ፍጠር</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function submitNewTeam(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-team');
            btn.disabled = true;
            btn.innerText = 'በመፍጠር ላይ...';

            const leaderId = document.getElementById('new-team-leader').value;
            const payload = {
                team_name: document.getElementById('new-team-name').value.trim(),
                team_leader_id: leaderId ? parseInt(leaderId) : null,
                phone: document.getElementById('new-team-phone').value.trim() || null,
                vehicle_plate: 'Company Shared Car',
                notes: document.getElementById('new-team-notes').value.trim() || null,
            };

            try {
                const res = await apiFetch('/api/teams', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert('✅ የፅዳት ቡድኑ በተሳካ ሁኔታ ተፈጥሯል!');
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('ስህተት: ' + (data.message || 'ቡድኑን መፍጠር አልተቻለም'));
                }
            } catch (err) {
                alert('የሰርቨር ግንኙነት ችግር: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerText = 'ፍጠር';
            }
        }

        // ==========================================
        // PAYROLL / SALARY PAYMENT MODAL & SUBMIT
        // ==========================================
        async function openPayrollModal(preselectedEmployeeId = null, preselectedEmployeeName = '') {
            const container = document.getElementById('generic-modal-container');
            const res = await apiFetch('/api/employees');
            const data = await res.json();
            const employees = Array.isArray(data) ? data : (data.data || []);
            const todayStr = new Date().toISOString().split('T')[0];

            const months = [
                'መስከረም', 'ጥቅምት', 'ህዳር', 'ታህሳስ', 'ጥር', 'የካቲት',
                'መጋቢት', 'ሚያዝያ', 'ግንቦት', 'ሰኔ', 'ሐምሌ', 'ነሐሴ', 'ጳጉሜ'
            ];

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white flex items-center gap-2">
                                    <i data-lucide="banknote" class="w-5 h-5 text-amber-400"></i>
                                    <span>የደመወዝ ክፍያ መመዝገቢያ (Pay Salary)</span>
                                </h3>
                                <p class="text-[11px] text-slate-400">ለሰራተኛ የተከፈለ ደመወዝ ይመዝግቡ (በራስ-ሰር የወጪ መዝገብ ላይ ይገባል)።</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitPayroll(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ሰራተኛ ይምረጡ *</label>
                                <select id="payroll-employee-id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white">
                                    <option value="">-- ሰራተኛ ይምረጡ --</option>
                                    ${employees.map(e => `
                                        <option value="${e.id}" ${preselectedEmployeeId && e.id == preselectedEmployeeId ? 'selected' : ''}>
                                            ${e.name} (${e.role === 'cleaner' ? 'የፅዳት ባለሙያ' : e.role}) - ${e.phone || e.email}
                                        </option>
                                    `).join('')}
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የተከፈለ ደመወዝ መጠን (ETB) *</label>
                                <input type="number" id="payroll-amount" required min="1" step="any" placeholder="ለምሳሌ፡ 7500" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-mono text-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">ክፍያው የሚመለከተው ወር *</label>
                                    <select id="payroll-month" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                        ${months.map((m, idx) => `<option value="${m}" ${idx === 0 ? 'selected' : ''}>${m}</option>`).join('')}
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">የተከፈለበት ቀን *</label>
                                    <input type="date" id="payroll-date" required value="${todayStr}" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono">
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ተጨማሪ ማስታወሻ / ደረሰኝ ቁጥር</label>
                                <textarea id="payroll-notes" rows="2" placeholder="የባንክ ማመሳከሪያ ወይም ተጨማሪ ማስታወሻ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-white"></textarea>
                            </div>
                            <div class="p-3 rounded-2xl bg-amber-950/40 border border-amber-500/30 text-amber-300 text-[11px] flex items-start gap-2">
                                <i data-lucide="info" class="w-4 h-4 text-amber-400 shrink-0 mt-0.5"></i>
                                <span>ይህ የተከፈለ ገንዘብ በራስ-ሰር በአድሚኑ የወጪ ገጽ (Expense Page) ላይ እንደ «የሰራተኞች ክፍያ» ወጪ ሆኖ ይመዘገባል።</span>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" id="btn-save-payroll" class="px-5 py-2 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold rounded-xl flex items-center gap-1.5 shadow-lg shadow-amber-950/50">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    <span>ክፍያውን መዝግብ</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function submitPayroll(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-payroll');
            btn.disabled = true;
            btn.innerText = 'በመመዝገብ ላይ...';

            const empId = document.getElementById('payroll-employee-id').value;
            const payload = {
                employee_id: empId,
                user_id: empId,
                amount: parseFloat(document.getElementById('payroll-amount').value),
                month: document.getElementById('payroll-month').value,
                payment_date: document.getElementById('payroll-date').value,
                notes: document.getElementById('payroll-notes').value.trim() || null,
            };

            try {
                const res = await apiFetch('/api/finance/payroll', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert('✅ የደመወዝ ክፍያ በተሳካ ሁኔታ ተመዝግቧል! በአድሚኑ የወጪ ገጽ ላይ ታክሏል።');
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('❌ ስህተት: ' + (data.message || 'ክፍያውን መመዝገብ አልተቻለም'));
                }
            } catch (err) {
                alert('የሰርቨር ግንኙነት ችግር: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerText = 'ክፍያውን መዝግብ';
            }
        }


        // ==========================================
        // 9. CUSTOMER CARE & COMPLAINTS
        // ==========================================
        async function renderCustomerCareModule(container) {
            try {
                const [followupsRes, complaintsRes, feedbacksRes] = await Promise.all([
                    apiFetch('/api/care/followups'),
                    apiFetch('/api/care/complaints'),
                    apiFetch('/api/care/feedbacks')
                ]);
                const followupsData = await followupsRes.json();
                const complaintsData = await complaintsRes.json();
                const feedbacksData = await feedbacksRes.json();
                const followups = followupsData.data || [];
                const complaints = complaintsData.data || [];
                const feedbacks = feedbacksData.data || [];

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-8">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">የደንበኞች እንክብካቤ፣ ቅሬታዎች እና የአስተያየት ማጽደቂያ</h2>
                                <p class="text-xs text-slate-400 mt-1">የድህረ-ፅዳት ክትትል ጥሪዎች፣ ክፍት ቅሬታዎች እና በድረ-ገጽ ላይ የሚታዩ የተጠቃሚ አስተያየቶች ማጣሪያ።</p>
                            </div>
                            <button onclick="openLogNewComplaintModal()" class="px-4 py-2.5 bg-red-600/90 hover:bg-red-600 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2 cursor-pointer transition-all">
                                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                <span>+ አዲስ ቅሬታ መዝግብ</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Follow-ups Column -->
                            <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                        <i data-lucide="phone-call" class="w-4 h-4 text-blue-400"></i>
                                        የክትትል ጥሪዎች (${followups.length})
                                    </h3>
                                </div>
                                <div class="space-y-3">
                                    ${followups.length === 0 ? '<p class="text-xs text-slate-500 text-center py-6">ምንም የሚጠበቅ የክትትል ጥሪ የለም!</p>' : ''}
                                    ${followups.map(f => `
                                        <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700 flex items-center justify-between gap-2">
                                            <div>
                                                <h4 class="text-xs font-bold text-white">${f.customer?.full_name}</h4>
                                                <p class="text-[11px] text-slate-400">📞 ${f.customer?.phone}</p>
                                                <span class="text-[9px] px-2 py-0.5 rounded-full uppercase font-bold ${f.status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-blue-500/20 text-blue-400'}">${f.status}</span>
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <button onclick="openDirectSmsModal('${f.customer?.phone || ''}', '${f.customer?.full_name || ''}')" class="px-2 py-1 rounded-lg bg-cyan-950/80 hover:bg-cyan-900 border border-cyan-500/30 text-cyan-300 text-[11px] font-bold">📩</button>
                                                <button onclick="openFollowupCallModal(${f.id}, '${f.customer?.full_name}', '${f.customer?.phone}')" class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-[11px] font-bold">መዝግብ</button>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- ክፍት ቅሬታዎች Column -->
                            <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                        <i data-lucide="alert-circle" class="w-4 h-4 text-red-400"></i>
                                        ቅሬታዎች (${complaints.length})
                                    </h3>
                                </div>
                                <div class="space-y-3">
                                    ${complaints.length === 0 ? '<p class="text-xs text-slate-500 text-center py-6">ምንም የተመዘገበ ቅሬታ የለም።</p>' : ''}
                                    ${complaints.map(c => `
                                        <div class="p-3.5 rounded-2xl bg-slate-800/80 border ${c.status === 'resolved' ? 'border-slate-700' : 'border-red-500/40'} flex items-center justify-between gap-2">
                                            <div>
                                                <div class="flex items-center gap-1.5">
                                                    <h4 class="text-xs font-bold text-white">${c.customer?.full_name}</h4>
                                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full uppercase font-bold ${c.status === 'resolved' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'}">${c.status}</span>
                                                </div>
                                                <p class="text-[10px] text-red-300 font-semibold mt-0.5">${c.category}: ${c.description}</p>
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <button onclick="openDirectSmsModal('${c.customer?.phone || ''}', '${c.customer?.full_name || ''}')" class="px-2 py-1 rounded-lg bg-cyan-950/80 hover:bg-cyan-900 border border-cyan-500/30 text-cyan-300 text-[11px] font-bold">📩</button>
                                                ${c.status !== 'resolved' ? `
                                                    <button onclick="openResolveComplaintModal(${c.id})" class="px-2.5 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-[11px] font-bold">መፍትሄ</button>
                                                ` : '<span class="text-[11px] text-emerald-400 font-bold">✓</span>'}
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- የደንበኞች አስተያየቶች & Website Moderation Column -->
                            <div class="p-5 rounded-3xl bg-slate-900 border border-cyan-500/30 space-y-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                        <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
                                        የድረ-ገጽ አስተያየቶች ማጣሪያ (${feedbacks.length})
                                    </h3>
                                    <span class="text-[10px] text-cyan-400 font-bold">የአስተዳዳሪ ማረጋገጫ</span>
                                </div>
                                <div class="space-y-3">
                                    ${feedbacks.length === 0 ? '<p class="text-xs text-slate-500 text-center py-6">ምንም የተሰጠ አስተያየት የለም።</p>' : ''}
                                    ${feedbacks.map(fb => `
                                        <div class="p-3.5 rounded-2xl bg-slate-800/80 border ${fb.is_approved ? 'border-emerald-500/40' : 'border-slate-700'} space-y-2">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <span class="text-xs font-bold text-white">${fb.customer?.full_name || 'ውድ ደንበኛ'}</span>
                                                    <span class="text-[10px] text-amber-400 ml-1.5 font-bold">${'★'.repeat(fb.rating)}${'☆'.repeat(5 - fb.rating)}</span>
                                                </div>
                                                <span class="text-[9px] px-2 py-0.5 rounded-full font-bold ${fb.is_approved ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-400'}">
                                                    ${fb.is_approved ? 'በድረ-ገጽ ይታያል' : 'ተደብቋል'}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-slate-300 italic">"${fb.comment || 'ምንም የጽሁፍ አስተያየት የለም'}"</p>
                                            <div class="pt-2 border-t border-slate-700/60 flex justify-between items-center text-[10px]">
                                                <span class="text-slate-400">${fb.source || 'web'}</span>
                                                <button onclick="toggleFeedbackApproval(${fb.id}, ${!fb.is_approved})" class="px-2.5 py-1 rounded-lg ${fb.is_approved ? 'bg-red-950/70 hover:bg-red-900 border border-red-500/30 text-red-300' : 'bg-emerald-950/80 hover:bg-emerald-900 border border-emerald-500/40 text-emerald-300'} font-bold transition-all">
                                                    ${fb.is_approved ? '🚫 ከድረ-ገጽ ደብቅ' : '✅ በድረ-ገጽ እንዲታይ ፍቀድ'}
                                                </button>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                container.innerHTML = `<div class="p-6 text-red-400">Failed to load Customer Care: ${err.message}</div>`;
            }
        }

        async function toggleFeedbackApproval(id, approve) {
            try {
                const res = await apiFetch(`/api/care/feedbacks/${id}/approve`, {
                    method: 'POST',
                    body: JSON.stringify({ is_approved: approve })
                });
                const data = await res.json();
                if (res.ok) {
                    alert('✅ ' + (data.message || 'ተዘምኗል!'));
                    loadActiveTab();
                } else {
                    alert('❌ ስህተት: ' + (data.message || 'ማዘመን አልተቻለም'));
                }
            } catch(e) {
                alert('የግንኙነት ስህተት: ' + e.message);
            }
        }

        async function openLogNewComplaintModal() {
            const container = document.getElementById('generic-modal-container');
            const res = await apiFetch('/api/customers?per_page=50');
            const data = await res.json();
            const customers = data.data || [];

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white flex items-center gap-2">
                                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-400"></i>
                                    <span>አዲስ የደንበኛ ቅሬታ መዝግብ</span>
                                </h3>
                                <p class="text-[11px] text-slate-400">በስልክ ወይም በአካል የቀረበን ቅሬታ ለመከታተል መዝግብ።</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitNewComplaint(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ደንበኛ ይምረጡ *</label>
                                <select id="complaint-customer-id" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold">
                                    <option value="">-- ደንበኛ ይምረጡ --</option>
                                    ${customers.map(c => `
                                        <option value="${c.id}">${c.full_name} (${c.phone}) - ${c.subcity || ''}</option>
                                    `).join('')}
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">የቅሬታው ምድብ *</label>
                                    <select id="complaint-category" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                        <option value="service_quality">የፅዳት ጥራት ጉድለት</option>
                                        <option value="staff_behavior">የሰራተኛ ስነ-ምግባር</option>
                                        <option value="delay_timing">የሰዓት መዘግየት</option>
                                        <option value="pricing_billing">የዋጋ / ክፍያ አለመግባባት</option>
                                        <option value="damage_reported">የዕቃ ጉዳት ጥቆማ</option>
                                        <option value="other">ሌላ ቅሬታ</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">አስቸኳይነት (Priority)</label>
                                    <select id="complaint-priority" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold">
                                        <option value="high">ከፍተኛ (High)</option>
                                        <option value="medium" selected>መካከለኛ (Medium)</option>
                                        <option value="low">ዝቅተኛ (Low)</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የቅሬታው ዝርዝር ማብራሪያ *</label>
                                <textarea id="complaint-desc" required rows="3" placeholder="ደንበኛው የገለጸውን ቅሬታ በግልጽ ያስቀምጡ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-500 text-white font-bold rounded-xl shadow-lg">ቅሬታውን መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function submitNewComplaint(e) {
            e.preventDefault();
            const customerId = document.getElementById('complaint-customer-id').value;
            const category = document.getElementById('complaint-category').value;
            const priority = document.getElementById('complaint-priority').value;
            const desc = document.getElementById('complaint-desc').value;

            try {
                const res = await apiFetch('/api/care/complaints', {
                    method: 'POST',
                    body: JSON.stringify({
                        customer_id: customerId,
                        category: category,
                        priority: priority,
                        description: desc,
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    alert('✅ ቅሬታው ተመዝግቧል! ወደ ቅሬታዎች ዝርዝር ታክሏል።');
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('❌ ስህተት: ' + (data.message || 'ቅሬታውን መመዝገብ አልተቻለም'));
                }
            } catch (err) {
                alert('የግንኙነት ስህተት: ' + err.message);
            }
        }

        // ==========================================
        // 10. MARKETING CAMPAIGNS
        // ==========================================
        async function renderCampaignsModule(container) {
            try {
                const res = await apiFetch('/api/campaigns');
                const data = await res.json();
                const campaigns = data.data || [];

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">የማስታወቂያ ዘመቻዎች (SMS & Telegram)</h2>
                                <p class="text-xs text-slate-400 mt-1">የበዓላት ቅናሽ ማስታወቂያዎች፣ ልዩ ቅናሾች እና አውቶሜትድ መልዕክቶች።</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="openCreateCampaignModal()" class="px-4 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    <span>+ አዲስ ዘመቻ ፍጠር / ላክ</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            ${campaigns.length === 0 ? '<div class="p-8 text-slate-500 col-span-3 text-center">ምንም የተዘጋጀ የማስታወቂያ ዘመቻ የለም። ከላይ ያለውን "+ አዲስ ዘመቻ ፍጠር" ተጭነው ይጀምሩ።</div>' : ''}
                            ${campaigns.map(c => {
                                const title = (c && c.title && c.title !== 'undefined') ? c.title : (c && c.name ? c.name : 'የበዓላት ቅናሽ ማስታወቂያ');
                                const msgText = (c && c.message_text && c.message_text !== 'undefined') ? c.message_text : (c && c.message ? c.message : 'መልዕክት አልተገለጸም');
                                const audienceMap = {
                                    'all_customers': 'ሁሉም ደንበኞች (All)',
                                    'all': 'ሁሉም ደንበኞች (All)',
                                    'vip': 'ቪአይፒ ደንበኞች (VIP)',
                                    'regular': 'መደበኛ ደንበኞች (Regular)',
                                    'dormant': 'የቆዩ ደንበኞች (Dormant)',
                                    'corporate': 'ድርጅታዊ ደንበኞች (Corporate)',
                                    'opted_in': 'ፍቃድ የሰጡ ደንበኞች'
                                };
                                const rawAudience = (c && c.audience_filter && c.audience_filter !== 'undefined') ? c.audience_filter : 'all_customers';
                                const audience = audienceMap[rawAudience] || rawAudience;
                                const sentCount = (c && c.sent_count !== undefined && c.sent_count !== null) ? c.sent_count : (c && c.total_targets ? c.total_targets : 0);
                                const channel = ((c && c.channel) || 'sms').toUpperCase();
                                const isSent = c && (c.status === 'sent' || c.status === 'completed');
                                const isDraft = !isSent;

                                return `
                                <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-3 hover:border-slate-700 transition">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider flex items-center gap-1">
                                            <i data-lucide="${channel === 'TELEGRAM' ? 'send' : 'message-square'}" class="w-3 h-3"></i>
                                            ${channel} Campaign
                                        </span>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${isSent ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-400 border border-amber-500/30'}">
                                            ${isSent ? 'ተልኳል (Completed)' : 'ረቂቅ (Draft)'}
                                        </span>
                                    </div>
                                    <h3 class="text-base font-bold text-white">${title}</h3>
                                    <p class="text-xs text-slate-300 bg-slate-800/60 p-3 rounded-xl border border-slate-700/50 leading-relaxed font-sans">
                                        "${msgText}"
                                    </p>
                                    <div class="pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                                        <span>ዒላማ: <strong class="text-white">${audience}</strong></span>
                                        <span>የተላከላቸው: <strong class="text-cyan-400 font-mono text-sm">${sentCount}</strong></span>
                                    </div>
                                    ${isDraft ? `
                                        <div class="pt-2">
                                            <button onclick="dispatchCampaignNow(${c.id})" class="w-full py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-1.5 shadow-lg cursor-pointer">
                                                <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                                <span>🚀 አሁን ለሁሉም ላክ (Send Now)</span>
                                            </button>
                                        </div>
                                    ` : ''}
                                </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                container.innerHTML = `<div class="p-6 text-red-400">Failed to load ማስታወቂያ እና ፕሮሞሽን Campaigns: ${err.message}</div>`;
            }
        }

        async function dispatchCampaignNow(campaignId) {
            if (!confirm('ይህንን ማስታወቂያ አሁን ለታለሙ ደንበኞች በሙሉ መላክ ይፈልጋሉ?')) return;
            try {
                const res = await apiFetch(`/api/campaigns/${campaignId}/send`, { method: 'POST' });
                const data = await res.json();
                if (res.ok) {
                    alert('✓ ' + (data.message || 'ማስታወቂያው በተሳካ ሁኔታ ተልኳል!'));
                    loadActiveTab();
                } else {
                    alert('ስህተት፡ ' + (data.message || 'መላክ አልተቻለም'));
                }
            } catch (err) {
                alert('የኔትወርክ ስህተት፡ ' + err.message);
            }
        }

        function openCreateCampaignModal() {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white">📢 አዲስ የማስታወቂያ ዘመቻ ፍጠር</h3>
                                <p class="text-[11px] text-slate-400">ለደንበኞች በ SMS ወይም በቴሌግራም መልዕክት ማስተላለፊያ</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitCreateCampaign(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-400 mb-1">የዘመቻው ርዕስ *</label>
                                <input type="text" id="cmp-title" required placeholder="ለምሳሌ፡ የአዲስ ዓመት ልዩ የ 20% ቅናሽ" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">የመላኪያ ቻናል *</label>
                                    <select id="cmp-channel" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold cursor-pointer">
                                        <option value="sms">📱 SMS (የጽሑፍ መልዕክት)</option>
                                        <option value="telegram">✈️ Telegram (ቴሌግራም ቦት)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">የደንበኛ ዒላማ *</label>
                                    <select id="cmp-audience" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white cursor-pointer">
                                        <option value="all_customers">ሁሉም ደንበኞች (All)</option>
                                        <option value="vip">ቪአይፒ ደንበኞች (VIP)</option>
                                        <option value="corporate">ድርጅታዊ ደንበኞች (Corporate)</option>
                                        <option value="regular">መደበኛ ደንበኞች (Regular)</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">የመልዕክቱ ይዘት *</label>
                                <textarea id="cmp-message" required rows="3" placeholder="ለደንበኞች የሚላከው ማስታወቂያ ወይም የቅናሽ መልዕክት..." class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white leading-relaxed"></textarea>
                            </div>
                            <div class="flex items-center gap-2 pt-1">
                                <input type="checkbox" id="cmp-send-now" checked class="rounded border-slate-700 bg-slate-800 text-cyan-500 focus:ring-0">
                                <label for="cmp-send-now" class="text-slate-300 font-bold cursor-pointer">ወዲያውኑ ለደንበኞች ይላክ (Send Immediately)</label>
                            </div>
                            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl cursor-pointer">ሰርዝ</button>
                                <button type="submit" id="btn-submit-cmp" class="px-5 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-lg cursor-pointer">ዘመቻውን አስመዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function submitCreateCampaign(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-cmp');
            btn.disabled = true;
            btn.textContent = 'በማስኬድ ላይ...';

            const payload = {
                title: document.getElementById('cmp-title').value.trim(),
                channel: document.getElementById('cmp-channel').value,
                audience_filter: document.getElementById('cmp-audience').value,
                message_text: document.getElementById('cmp-message').value.trim(),
            };

            const shouldSendNow = document.getElementById('cmp-send-now')?.checked;

            try {
                const res = await apiFetch('/api/campaigns', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (res.ok) {
                    const campaignId = data.campaign?.id;
                    if (shouldSendNow && campaignId) {
                        btn.textContent = 'ወዲያውኑ በመላክ ላይ...';
                        await apiFetch(`/api/campaigns/${campaignId}/send`, { method: 'POST' });
                    }
                    alert('✓ የማስታወቂያ ዘመቻው በተሳካ ሁኔታ ተፈጥሮ ተልኳል!');
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('ስህተት፡ ' + (data.message || 'ማስታወቂያውን መፍጠር አልተቻለም'));
                    btn.disabled = false;
                    btn.textContent = 'ዘመቻውን አስመዝግብ';
                }
            } catch (err) {
                alert('የኔትወርክ ስህተት፡ ' + err.message);
                btn.disabled = false;
                btn.textContent = 'ዘመቻውን አስመዝግብ';
            }
        }

        // ==========================================
        // 10b. SYSTEM SETTINGS & SMS/TELEGRAM CONFIGURATION
        // ==========================================
        async function renderSettingsModule(container) {
            container.innerHTML = `
                <div class="max-w-5xl mx-auto space-y-6">
                    <div>
                        <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">የስርዓት አስተዳደር</span>
                        <h2 class="text-2xl sm:text-3xl font-black text-white">የሲስተም ቅንብሮች እና የመልዕክት መላኪያ (Settings)</h2>
                        <p class="text-xs text-slate-400 mt-0.5">የባለቤቱ ይፋዊ ስልክ፣ የኤስኤምኤስ ጌትዌይ እና የቴሌግራም ቦት ዝርዝር መረጃዎች።</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- 1. Owner & Company Profile Card -->
                        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 shadow-xl">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                                <div class="w-8 h-8 rounded-xl bg-cyan-600/20 text-cyan-400 flex items-center justify-center">
                                    <i data-lucide="building" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white">የባለቤቱ እና የድርጅቱ መረጃ</h3>
                                    <span class="text-[10px] text-slate-400">ይፋዊ የድርጅት ምስክር ወረቀት መረጃ</span>
                                </div>
                            </div>

                            <div class="space-y-3 text-xs">
                                <div>
                                    <label class="block text-slate-400 mb-1">የባለቤቱ ስልክ ቁጥር (ይፋዊ መላኪያ)</label>
                                    <div class="flex items-center gap-2">
                                        <input type="text" id="setting-owner-phone" value="0943854325" class="w-full bg-slate-800 border border-cyan-500/50 rounded-xl px-3 py-2 text-cyan-300 font-mono font-bold">
                                        <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 font-bold text-[10px] whitespace-nowrap">✓ ተረጋግጧል</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-slate-400 mb-1">የድርጅት ስም</label>
                                    <input type="text" value="Meash Cleaning Solution (ሜሽ የፅዳት አገልግሎት)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold" readonly>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-slate-400 mb-1">TIN ቁጥር</label>
                                        <input type="text" value="0098765432" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-slate-300 font-mono" readonly>
                                    </div>
                                    <div>
                                        <label class="block text-slate-400 mb-1">አድራሻ</label>
                                        <input type="text" value="ቦሌ፣ አዲስ አበባ" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-slate-300" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. SMS Gateway & Live Test Card -->
                        <div class="p-6 rounded-3xl bg-slate-900 border border-cyan-500/30 space-y-4 shadow-xl">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-xl bg-blue-600/20 text-blue-400 flex items-center justify-center">
                                        <i data-lucide="send" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-white">የኤስኤምኤስ (SMS) ግንኙነት ማረጋገጫ</h3>
                                        <span class="text-[10px] text-emerald-400 font-bold">● SMS Gateway ንቁ (Active)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3 text-xs">
                                <div>
                                    <label class="block text-slate-400 mb-1">የሙከራ ተቀባይ ደንበኛ ስልክ ቁጥር</label>
                                    <input type="text" id="setting-test-recipient" oninput="updateOwnerSimSmsLink()" value="0922998581" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold">
                                </div>

                                <div>
                                    <label class="block text-slate-400 mb-1">የመልዕክት ይዘት (Test Message)</label>
                                    <textarea id="setting-test-msg" oninput="updateOwnerSimSmsLink()" rows="2" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white">ሰላም! ይህ ከሜሽ ክሊኒንግ (0943854325) የተላከ ይፋዊ የሙከራ ኤስኤምኤስ ነው። ሲስተሙ በትክክል እየሰራ ነው።</textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <button onclick="sendTestSmsFromSettings()" id="btn-settings-test-sms" class="py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer text-xs">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                        <span>በሲስተም SMS ላክ</span>
                                    </button>
                                    <a id="btn-owner-direct-sim-sms" href="sms:0922998581?body=ሰላም! ይህ ከሜሽ ክሊኒንግ (0943854325) የተላከ ይፋዊ የሙከራ ኤስኤምኤስ ነው።" class="py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer text-xs text-center">
                                        <span>📱 በስልክህ SIM በነፃ ላክ</span>
                                    </a>
                                </div>

                                <!-- Large High-Resolution Mobile QR Code Scanner -->
                                <div class="mt-3 p-4 rounded-2xl bg-slate-800/90 border border-slate-700 flex flex-col items-center gap-3 text-center">
                                    <div class="w-52 h-52 sm:w-60 sm:h-60 bg-white p-3 rounded-2xl shrink-0 flex items-center justify-center shadow-2xl ring-4 ring-emerald-500/20">
                                        <img id="qr-sim-sms" src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=SMSTO:0922998581:ሰላም!%20ይህ%20ከሜሽ%20ክሊኒንግ%20(0943854325)%20የተላከ%20ይፋዊ%20የሙከራ%20ኤስኤምኤስ%20ነው።" alt="SMS QR" class="w-full h-full object-contain">
                                    </div>
                                    <div class="text-xs text-slate-300">
                                        <p class="font-bold text-white flex items-center justify-center gap-1.5 text-sm">
                                            <i data-lucide="qr-code" class="w-4 h-4 text-emerald-400"></i>
                                            <span>በባለቤቱ ስልክ ካሜራ ስካን ያድርጉ</span>
                                        </p>
                                        <p class="text-slate-400 text-[11px] mt-1">በባለቤቱ ስልክ (0943854325) ካሜራ ይህንን QR ኮድ ሲያነቡ ወዲያውኑ መልዕክቱ ተዘጋጅቶ ይከፈታል።</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Telegram Bot Status Card -->
                        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 shadow-xl">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                                <div class="w-8 h-8 rounded-xl bg-sky-600/20 text-sky-400 flex items-center justify-center">
                                    <i data-lucide="bot" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white">የቴሌግራም ቦት (Telegram Bot)</h3>
                                    <span class="text-[10px] text-emerald-400 font-bold">● የተገናኘ (Connected)</span>
                                </div>
                            </div>

                            <div class="space-y-2.5 text-xs text-slate-300">
                                <div class="flex justify-between items-center p-2 rounded-xl bg-slate-800/80">
                                    <span class="text-slate-400">የቦት ቶከን (Token):</span>
                                    <span class="font-mono text-cyan-300 font-bold text-[11px]">8964703337:AAGT...oiWA3U</span>
                                </div>
                                <div class="flex justify-between items-center p-2 rounded-xl bg-slate-800/80">
                                    <span class="text-slate-400">የቦት ሁኔታ:</span>
                                    <span class="text-white font-bold text-[11px]">ትዕዛዝ ይቀበላል / ንቁ</span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. System Calendar & Locale Card -->
                        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 shadow-xl">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                                <div class="w-8 h-8 rounded-xl bg-amber-600/20 text-amber-400 flex items-center justify-center">
                                    <i data-lucide="calendar" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white">የኢትዮጵያ ቀን እና ሰዓት አቆጣጠር</h3>
                                    <span class="text-[10px] text-amber-400 font-bold">ዘመን፡ 2019 ዓ.ም (አዲስ ዘመን)</span>
                                </div>
                            </div>

                            <div class="space-y-2 text-xs text-slate-300">
                                <div class="p-3 rounded-2xl bg-amber-950/20 border border-amber-500/20">
                                    <p class="font-bold text-white text-sm">🗓 የዛሬ ቀን፡ ${EC.formatEth(new Date())}</p>
                                    <p class="text-[11px] text-slate-400 mt-1">የኢትዮጵያ ቀን አቆጣጠር አልጎሪዝም በትክክል ተስተካክሎ 2019 ዓ.ም እያሳየ ይገኛል።</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function sendTestSmsFromSettings() {
            const btn = document.getElementById('btn-settings-test-sms');
            btn.disabled = true;
            btn.innerText = 'በመላክ ላይ...';

            const phone = document.getElementById('setting-test-recipient').value;
            const message = document.getElementById('setting-test-msg').value;

            try {
                const res = await apiFetch('/api/notifications/send-direct-sms', {
                    method: 'POST',
                    body: JSON.stringify({ phone, message })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert('✅ ኤስኤምኤሱ በተሳካ ሁኔታ ለደንበኛው (' + phone + ') ተልኳል! በሲስተም መዝገብ ላይ ተመዝግቧል።');
                } else {
                    alert('ስህተት: ' + (data.message || 'መላክ አልተቻለም'));
                }
            } catch (e) {
                alert('የግንኙነት ስህተት: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerText = 'ለደንበኛው (0922998581) SMS ላክ';
            }
        }

        function updateOwnerSimSmsLink() {
            const phone = document.getElementById('setting-test-recipient')?.value || '0922998581';
            const msg = document.getElementById('setting-test-msg')?.value || '';
            const link = document.getElementById('btn-owner-direct-sim-sms');
            if (link) {
                link.href = `sms:${phone.replace(/\s+/g, '')}?body=${encodeURIComponent(msg)}`;
            }
            const qr = document.getElementById('qr-sim-sms');
            if (qr) {
                qr.src = `https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=SMSTO:${phone.replace(/\s+/g, '')}:${encodeURIComponent(msg)}`;
            }
        }
        // ==========================================
        async function renderCalendarModule(container) {
            try {
                const res = await apiFetch('/api/appointments');
                const data = await res.json();
                const appointments = data.data || [];

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div>
                            <h2 class="text-2xl font-black text-white">የቀጠሮዎች እና የስራ መርሃ-ግብር ካላንደር</h2>
                            <p class="text-xs text-slate-400 mt-1">የቀን መቁጠሪያ በኢትዮጵያ ዘመን አቆጣጠር፣ በሰዓት እና በተመደቡ ቡድኖች።</p>
                        </div>

                        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden p-4">
                            <div class="space-y-3">
                                ${appointments.length === 0 ? '<p class="text-center text-slate-500 py-8">የተያዘ ቀጠሮ የለም።</p>' : ''}
                                ${appointments.map(a => `
                                    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xs font-bold text-cyan-400">🗓 ${a.eth_date || EC.formatEth(a.appointment_date)}</span>
                                                <span class="text-[10px] text-slate-400 font-normal">🌐 ${a.appointment_date || '—'}</span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-700/80 text-cyan-300 font-bold">${formatTimeSlot(a.appointment_time_slot)}</span>
                                            </div>
                                            <h4 class="text-sm font-bold text-white mt-1">${a.customer?.full_name || 'Customer'} - 📞 ${a.customer?.phone || ''}</h4>
                                            <p class="text-xs text-slate-400">📍 ${a.order?.address || 'Addis Ababa'} | የተመደበ ቡድን: <strong>${a.team?.team_name || 'ቡድን አልተመደበም'}</strong></p>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase self-start sm:self-auto ${a.status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-cyan-500/20 text-cyan-400'}">${a.status}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                container.innerHTML = `<div class="p-6 text-red-400">Failed to load Calendar: ${err.message}</div>`;
            }
        }

        // ==========================================
        // 12. DETAILED MODALS IMPLEMENTATION
        // ==========================================
        async function openCustomerProfileModal(customerId) {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = '<div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center text-white"><i data-lucide="loader-2" class="w-8 h-8 animate-spin text-cyan-400"></i></div>';
            lucide.createIcons();
            try {
                const res = await apiFetch(`/api/customers/${customerId}`);
                const data = await res.json();
                const c = data.customer || data.data || data || {};
                const orders = c.orders || [];
                const summary = data.summary || {};
                const timeline = data.timeline || [];

                const tierBadges = {
                    vip: 'bg-amber-500/20 text-amber-300 border-amber-500/40',
                    regular: 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40',
                    new: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40'
                };
                const tierLabels = {
                    vip: '👑 ቪአይፒ (VIP)',
                    regular: '⭐ መደበኛ ደንበኛ',
                    new: '🌱 አዲስ ደንበኛ'
                };
                const currentTier = (c.customer_type || 'new').toLowerCase();

                container.innerHTML = `
                    <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-2xl w-full p-6 shadow-2xl my-8 space-y-5">
                            <!-- HEADER -->
                            <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-600 text-white font-black text-lg flex items-center justify-center shadow-lg shadow-cyan-950/50">
                                        ${(c.full_name || 'C').slice(0, 2).toUpperCase()}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-mono font-bold text-cyan-400 bg-cyan-950/80 px-2 py-0.5 rounded border border-cyan-800 uppercase tracking-wider">${c.customer_code || ('CUST-' + c.id)}</span>
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border ${tierBadges[currentTier] || 'bg-slate-800 text-slate-300'}">
                                                ${tierLabels[currentTier] || c.customer_type || 'አዲስ ደንበኛ'}
                                            </span>
                                        </div>
                                        <h3 class="text-xl font-black text-white mt-1">${c.full_name || 'ደንበኛ'}</h3>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button onclick="openDirectSmsModal('${c.phone || ''}', '${(c.full_name || '').replace(/'/g, "\\'")}')" class="px-3 py-1.5 rounded-xl bg-cyan-950 hover:bg-cyan-900 border border-cyan-500/40 text-cyan-300 text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer" title="ቀጥታ SMS ላክ">
                                        <i data-lucide="message-square" class="w-3.5 h-3.5 text-cyan-400"></i>
                                        <span>SMS ላክ</span>
                                    </button>
                                    <button onclick="closeModal()" class="text-slate-400 hover:text-white text-2xl font-bold px-1.5">&times;</button>
                                </div>
                            </div>

                            <!-- 4 STAT SUMMARY CARDS -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div class="p-3.5 bg-slate-800/80 border border-slate-700/60 rounded-2xl">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold block">ስልክ ቁጥር</span>
                                    <a href="tel:${c.phone || ''}" class="text-xs font-bold text-white hover:text-cyan-300 font-mono mt-1 block">${c.phone || '—'}</a>
                                </div>
                                <div class="p-3.5 bg-slate-800/80 border border-slate-700/60 rounded-2xl">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold block">መኖሪያ / አድራሻ</span>
                                    <span class="text-xs font-bold text-white mt-1 block truncate" title="${c.address || c.subcity || ''}">${c.subcity || c.address || 'አዲስ አበባ'}</span>
                                </div>
                                <div class="p-3.5 bg-slate-800/80 border border-slate-700/60 rounded-2xl">
                                    <span class="text-[10px] text-slate-400 uppercase font-bold block">ጠቅላላ ትዕዛዞች</span>
                                    <span class="text-lg font-black text-cyan-400 mt-0.5 block">${summary.total_orders !== undefined ? summary.total_orders : orders.length}</span>
                                </div>
                                <div class="p-3.5 bg-emerald-950/40 border border-emerald-500/30 rounded-2xl">
                                    <span class="text-[10px] text-emerald-300 uppercase font-bold block">የከፈሉት ድምር</span>
                                    <span class="text-lg font-black text-emerald-400 mt-0.5 block">${parseFloat(summary.total_spending || c.orders?.reduce((sum, o) => sum + parseFloat(o.total || 0), 0) || 0).toLocaleString()} ETB</span>
                                </div>
                            </div>

                            <!-- SERVICE & ORDER HISTORY -->
                            <div>
                                <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                                    <i data-lucide="history" class="w-4 h-4 text-cyan-400"></i>
                                    <span>የቀድሞ አገልግሎቶች ታሪክ እና ሂደት (Order History)</span>
                                </h4>
                                <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                    ${orders.length === 0 ? `
                                        <div class="p-6 text-center rounded-2xl bg-slate-800/40 border border-slate-800 text-slate-400 text-xs">
                                            እስካሁን ምንም የቀድሞ ትዕዛዝ አልተመዘገበም።
                                        </div>
                                    ` : orders.map(o => `
                                        <div class="p-3 bg-slate-800/80 border border-slate-700/60 rounded-2xl flex items-center justify-between text-xs hover:border-cyan-500/30 transition-colors">
                                            <div class="space-y-0.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-white">${o.order_number}</span>
                                                    <span class="text-[11px] text-cyan-300">🗓 ${o.eth_appointment_date || EC.formatEth(o.appointment_date)}</span>
                                                </div>
                                                <p class="text-[11px] text-slate-400">${(o.items || []).map(i => `${i.item_name} (x${parseFloat(i.quantity)})`).join(', ') || 'የፅዳት አገልግሎት'}</p>
                                            </div>
                                            <div class="text-right">
                                                <span class="font-extrabold text-white block">${parseFloat(o.total || 0).toLocaleString()} ETB</span>
                                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full ${o.order_status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-cyan-500/20 text-cyan-400'}">${o.order_status}</span>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- TIMELINE (Payments, Feedbacks, Notes) -->
                            ${timeline.length > 0 ? `
                                <div>
                                    <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                                        <i data-lucide="activity" class="w-4 h-4 text-emerald-400"></i>
                                        <span>የደንበኛው ሙሉ እንቅስቃሴ እና አስተያየቶች (Activity Timeline)</span>
                                    </h4>
                                    <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                        ${timeline.map(t => `
                                            <div class="p-2.5 bg-slate-950/50 border border-slate-800 rounded-xl flex items-start gap-2.5 text-xs">
                                                <span class="text-base shrink-0 mt-0.5">
                                                    ${t.type === 'feedback' ? '⭐' : (t.type === 'payment' ? '💳' : (t.type === 'complaint' ? '⚠️' : '📦'))}
                                                </span>
                                                <div class="flex-1">
                                                    <div class="flex items-center justify-between">
                                                        <strong class="text-white font-bold">${t.title}</strong>
                                                        <span class="text-[10px] text-slate-500 font-mono">${t.eth_date || t.date?.split('T')[0]}</span>
                                                    </div>
                                                    <p class="text-slate-400 text-[11px] mt-0.5">${t.subtitle}</p>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                            ` : ''}

                            <div class="flex justify-end pt-3 border-t border-slate-800">
                                <button onclick="closeModal()" class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl transition-colors">ዝጋ</button>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (e) {
                alert('የደንበኛውን መረጃ መጫን አልተቻለም: ' + e.message);
                closeModal();
            }
        }

        async function openOrderDetailsModal(orderId) {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = '<div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center text-white"><i data-lucide="loader-2" class="w-8 h-8 animate-spin text-cyan-400"></i></div>';
            lucide.createIcons();
            try {
                const res = await apiFetch(`/api/orders/${orderId}`);
                const data = await res.json();
                const o = data.data || data;

                container.innerHTML = `
                    <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
                        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-xl w-full p-6 shadow-2xl my-8">
                            <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
                                <div>
                                    <span class="text-xs font-bold text-cyan-400">${o.order_number}</span>
                                    <h3 class="text-lg font-bold text-white">የትዕዛዝ ሙሉ ዝርዝር መረጃ</h3>
                                </div>
                                <button onclick="closeModal()" class="text-slate-400 hover:text-white text-2xl font-bold">&times;</button>
                            </div>

                            <div class="space-y-4 text-xs">
                                <div class="grid grid-cols-2 gap-3 p-3 bg-slate-800 rounded-xl">
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">ደንበኛ</span>
                                        <strong class="text-white">${o.customer?.full_name}</strong>
                                        <p class="text-slate-400 text-[11px]">📞 ${o.customer?.phone}</p>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Schedule & መገኛ</span>
                                        <strong class="text-white">${o.eth_appointment_date || o.appointment_date} (${o.appointment_time_slot})</strong>
                                        <p class="text-slate-400 text-[11px]">📍 ${o.address || o.customer?.address}</p>
                                        ${(o.latitude && o.longitude) ? `
                                            <button onclick="closeModal(); openInAppLiveRideMap(${o.id}, ${o.latitude}, ${o.longitude}, '${(o.customer?.full_name || 'ውድ ደንበኛ').replace(/'/g, "\\'")}', '${(o.customer?.phone || '').replace(/'/g, "\\'")}', '${(o.address || o.customer?.address || 'Addis Ababa').replace(/'/g, "\\'")}', '${(o.assigned_team?.team_name || 'የፅዳት ቡድን').replace(/'/g, "\\'")}', ${o.assigned_team?.current_latitude || 9.025}, ${o.assigned_team?.current_longitude || 38.746});" class="mt-2 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-cyan-950/90 border border-cyan-500/40 text-cyan-300 font-bold text-xs hover:bg-cyan-900 transition-colors">
                                                <i data-lucide="navigation-2" class="w-3.5 h-3.5 text-cyan-400"></i>
                                                <span>የቀጥታ ካርታ እይ (In-App Ride Map)</span>
                                            </button>
                                        ` : `
                                            <div class="mt-2 text-slate-400 text-[11px] flex items-center gap-1">
                                                <i data-lucide="map-pin-off" class="w-3.5 h-3.5 text-slate-500"></i>
                                                <span>ካርታ አልመረጠም (የጽሁፍ አድራሻ ብቻ)</span>
                                            </div>
                                        `}
                                    </div>
                                </div>

                                <div>
                                    <h4 class="font-bold text-slate-300 mb-2">የሚፀዱ እቃዎች / አገልግሎት:</h4>
                                    <div class="space-y-1.5">
                                        ${(o.items || []).map(i => `
                                            <div class="flex justify-between p-2 rounded-lg bg-slate-800/60 text-slate-200">
                                                <span>${i.item_name} × ${parseFloat(i.quantity)}</span>
                                                <span class="font-bold">${parseFloat(i.subtotal).toLocaleString()} ETB</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                    <div class="flex justify-between p-3 mt-2 bg-slate-800 rounded-xl text-white font-extrabold text-sm">
                                        <span>Total:</span>
                                        <span class="text-cyan-400">${parseFloat(o.total).toLocaleString()} ETB</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between p-3 bg-slate-800 rounded-xl">
                                    <span>የክፍያ ሁኔታ: <strong class="uppercase ${o.payment_status === 'paid' ? 'text-emerald-400' : 'text-amber-400'}">${o.payment_status}</strong></span>
                                    <span>የተመደበ ሰራተኛ/ቡድን: <strong class="text-cyan-300">${o.assigned_team?.team_name || 'አልተመደበም'}</strong></span>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-4 border-t border-slate-800 mt-4">
                                ${o.order_status !== 'completed' && o.order_status !== 'cancelled' ? `
                                    <button onclick="closeModal(); confirmAndAssignOrder(${o.id});" class="px-4 py-2 bg-blue-950/80 hover:bg-blue-900 border border-blue-500/40 text-blue-300 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors">
                                        <i data-lucide="user-check" class="w-4 h-4 text-blue-400"></i>
                                        <span>👤 ሰራተኛ መድብ (Assign Worker)</span>
                                    </button>
                                    <button onclick="closeModal(); openPostponeOrderModal(${o.id}, '${o.order_number}', '${o.customer?.full_name}');" class="px-4 py-2 bg-amber-950/70 hover:bg-amber-900 border border-amber-500/40 text-amber-300 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors">
                                        <i data-lucide="calendar-clock" class="w-4 h-4 text-amber-400"></i>
                                        <span>📅 ቀጠሮ አስተላልፍ (Postpone)</span>
                                    </button>
                                ` : ''}
                                <button onclick="closeModal()" class="px-5 py-2 bg-slate-800 text-slate-300 text-xs font-bold rounded-xl hover:bg-slate-700">ዝጋ</button>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (e) {
                alert('Failed to load order: ' + e.message);
                closeModal();
            }
        }

        // ==========================================
        // IN-APP LIVE RIDE MAP (Car into House - Ride/Uber Style)
        // ==========================================
        let activeInAppMap = null;

        function openInAppLiveRideMap(orderId, custLat, custLng, custName, custPhone, address, teamName = 'Team Alpha', teamLat = 9.025, teamLng = 38.746) {
            if (!custLat || !custLng || isNaN(parseFloat(custLat)) || isNaN(parseFloat(custLng))) {
                alert('ይህ ደንበኛ የቀጥታ GPS ካርታ አልመረጠም፤ አድራሻው በጽሁፍ ብቻ ነው::');
                return;
            }
            const container = document.getElementById('generic-modal-container');
            
            custLat = parseFloat(custLat);
            custLng = parseFloat(custLng);
            teamLat = parseFloat(teamLat) || 9.025;
            teamLng = parseFloat(teamLng) || 38.746;

            const distKm = calculateDistanceKm(custLat, custLng, teamLat, teamLng);
            const etaMin = Math.max(3, Math.round(distKm * 3.5));

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-2 sm:p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-cyan-500/40 rounded-3xl max-w-2xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[92vh]">
                        <!-- TOP HEADER -->
                        <div class="p-4 bg-slate-900/95 border-b border-slate-800 flex items-center justify-between shrink-0">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-2xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center border border-cyan-500/30">
                                    <i data-lucide="navigation-2" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm sm:text-base font-black text-white">የቀጥታ ስምሪት ካርታ (Live Ride Tracking)</h3>
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-black uppercase animate-pulse">● የቀጥታ መስመር</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400">${teamName} &rarr; ${custName} (${address})</p>
                                </div>
                            </div>
                            <button onclick="closeModal()" class="w-8 h-8 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 flex items-center justify-center font-bold text-lg">&times;</button>
                        </div>

                        <!-- LIVE MAP CANVAS CONTAINER -->
                        <div class="relative w-full h-[360px] sm:h-[420px] bg-slate-950 shrink-0">
                            <div id="inapp-ride-map" class="w-full h-full z-10"></div>
                            
                            <!-- FLOATING OVERLAY STATS -->
                            <div class="absolute top-3 left-3 right-3 z-20 pointer-events-none flex justify-between gap-2">
                                <div class="pointer-events-auto bg-slate-900/90 backdrop-blur-md border border-slate-700/80 rounded-2xl px-3 py-2 shadow-xl flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold">
                                        <i data-lucide="truck" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <span class="text-[9px] text-slate-400 uppercase font-bold block">የቀረ ርቀት</span>
                                        <span class="text-xs font-black text-white" id="ride-map-dist">${distKm.toFixed(1)} ኪ.ሜ</span>
                                    </div>
                                </div>
                                <div class="pointer-events-auto bg-slate-900/90 backdrop-blur-md border border-cyan-500/40 rounded-2xl px-3 py-2 shadow-xl flex items-center gap-3">
                                    <div class="w-7 h-7 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold">
                                        <i data-lucide="clock" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <span class="text-[9px] text-cyan-400 uppercase font-bold block">የመድረሻ ጊዜ (ETA)</span>
                                        <span class="text-xs font-black text-white" id="ride-map-eta">~${etaMin} ደቂቃ</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BOTTOM CONTROLS & INFO BAR -->
                        <div class="p-4 bg-slate-900 border-t border-slate-800 shrink-0 space-y-3">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></div>
                                    <span class="font-bold text-white">${custName}</span>
                                    <span class="text-slate-400">(${custPhone})</span>
                                </div>
                                <a href="tel:${custPhone}" class="px-3 py-1.5 bg-emerald-950/80 hover:bg-emerald-900 border border-emerald-500/40 text-emerald-300 font-bold text-xs rounded-xl flex items-center gap-1.5 transition-colors">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                    <span>ደውል</span>
                                </a>
                            </div>

                            <div class="flex gap-2">
                                <button onclick="refreshInAppMapGps(${orderId})" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-cyan-300 border border-slate-700 text-xs font-bold rounded-xl flex items-center justify-center gap-2 transition-colors">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5" id="btn-refresh-spin"></i>
                                    <span>የቀጥታ GPS አድስ (Live Ping)</span>
                                </button>
                                ${currentUser.role === 'cleaner' ? `
                                    <button onclick="cleanerJobAction(${orderId}, 'start'); closeModal();" class="flex-1 py-2.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 shadow-lg">
                                        <i data-lucide="play" class="w-3.5 h-3.5"></i>
                                        <span>ቦታው ደርሻለሁ & ስራ ጀምር</span>
                                    </button>
                                ` : `
                                    <button onclick="openDirectSmsModal('${custPhone}', '${custName}'); closeModal();" class="flex-1 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded-xl flex items-center justify-center gap-2 shadow-lg">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                        <span>ለደንበኛው SMS ላክ</span>
                                    </button>
                                `}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            lucide.createIcons();

            setTimeout(() => {
                const mapEl = document.getElementById('inapp-ride-map');
                if (!mapEl) return;

                if (activeInAppMap) {
                    try { activeInAppMap.remove(); } catch(e){}
                }

                activeInAppMap = L.map('inapp-ride-map', {
                    zoomControl: true,
                    attributionControl: false
                }).setView([teamLat, teamLng], 14);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19
                }).addTo(activeInAppMap);

                const houseIcon = L.divIcon({
                    html: '<div style="background:#0284c7;color:#fff;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(2,132,199,0.8);border:2px solid #fff;font-size:16px;">🏠</div>',
                    className: 'custom-house-pin',
                    iconSize: [34, 34],
                    iconAnchor: [17, 17]
                });

                const carIcon = L.divIcon({
                    html: '<div style="background:#10b981;color:#fff;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 0 15px rgba(16,185,129,0.9);border:2px solid #fff;font-size:18px;">🚗</div>',
                    className: 'custom-car-pin',
                    iconSize: [36, 36],
                    iconAnchor: [18, 18]
                });

                const custMarker = L.marker([custLat, custLng], { icon: houseIcon })
                    .addTo(activeInAppMap)
                    .bindPopup(`<b>🏠 የደንበኛ ቤት</b><br>${custName}<br>${address}`);

                const teamMarker = L.marker([teamLat, teamLng], { icon: carIcon })
                    .addTo(activeInAppMap)
                    .bindPopup(`<b>🚗 ${teamName}</b><br>ሁኔታ: በመንገድ ላይ ወደ ደንበኛ ቤት`)
                    .openPopup();

                const routeLine = L.polyline([[teamLat, teamLng], [custLat, custLng]], {
                    color: '#06b6d4',
                    weight: 4,
                    opacity: 0.85,
                    dashArray: '8, 8'
                }).addTo(activeInAppMap);

                const bounds = L.latLngBounds([[custLat, custLng], [teamLat, teamLng]]);
                activeInAppMap.fitBounds(bounds, { padding: [45, 45] });

                window._activeCustMarker = custMarker;
                window._activeTeamMarker = teamMarker;
                window._activeRouteLine = routeLine;
                window._activeCustCoords = [custLat, custLng];
            }, 250);
        }

        function calculateDistanceKm(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        async function refreshInAppMapGps(orderId) {
            const spinner = document.getElementById('btn-refresh-spin');
            if (spinner) spinner.classList.add('animate-spin');

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(async (pos) => {
                    const newLat = pos.coords.latitude;
                    const newLng = pos.coords.longitude;

                    if (window._activeTeamMarker && activeInAppMap) {
                        window._activeTeamMarker.setLatLng([newLat, newLng]);
                        if (window._activeCustCoords && window._activeRouteLine) {
                            window._activeRouteLine.setLatLngs([[newLat, newLng], window._activeCustCoords]);
                            const dist = calculateDistanceKm(window._activeCustCoords[0], window._activeCustCoords[1], newLat, newLng);
                            const eta = Math.max(2, Math.round(dist * 3.5));
                            const distEl = document.getElementById('ride-map-dist');
                            const etaEl = document.getElementById('ride-map-eta');
                            if (distEl) distEl.innerText = `${dist.toFixed(1)} ኪ.ሜ`;
                            if (etaEl) etaEl.innerText = `~${eta} ደቂቃ`;
                        }
                    }

                    try {
                        await apiFetch('/api/teams/location', {
                            method: 'POST',
                            body: JSON.stringify({ latitude: newLat, longitude: newLng })
                        });
                    } catch(e){}

                    if (spinner) spinner.classList.remove('animate-spin');
                }, (err) => {
                    if (spinner) spinner.classList.remove('animate-spin');
                }, { enableHighAccuracy: true, timeout: 6000 });
            } else {
                if (spinner) spinner.classList.remove('animate-spin');
            }
        }

        async function confirmAndAssignOrder(orderId) {
            const container = document.getElementById('generic-modal-container');
            const [cleanersRes, teamsRes] = await Promise.all([
                apiFetch('/api/employees?role=cleaner'),
                apiFetch('/api/teams')
            ]);
            const cleanersData = await cleanersRes.json();
            const teamsData = await teamsRes.json();
            const cleaners = Array.isArray(cleanersData) ? cleanersData : (cleanersData.data || []);
            const teams = Array.isArray(teamsData) ? teamsData : (teamsData.data || []);

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white flex items-center gap-2">
                                    <i data-lucide="user-check" class="w-5 h-5 text-cyan-400"></i>
                                    <span>ለትዕዛዙ ሰራተኛ / ቡድን መድብ</span>
                                </h3>
                                <p class="text-[11px] text-slate-400">ለትዕዛዙ የፅዳት ሰራተኛ በቀጥታ ይምረጡ ወይም የመስክ ቡድን ይመድቡ።</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white font-bold text-lg">&times;</button>
                        </div>
                        
                        <div class="space-y-4 text-xs">
                            <!-- 1. Direct Employee Assignment (Primary) -->
                            <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <label class="block font-bold text-cyan-300">👤 የፅዳት ሰራተኛ በቀጥታ መድብ (Cleaners)</label>
                                    <span class="text-[10px] bg-cyan-950 text-cyan-400 border border-cyan-800 px-2 py-0.5 rounded-full font-bold">${cleaners.length} ሰራተኞች</span>
                                </div>
                                ${cleaners.length === 0 ? `
                                    <p class="text-slate-500 italic text-[11px]">እስካሁን የተመዘገበ የፅዳት ሰራተኛ የለም።</p>
                                    <button onclick="closeModal(); openAddEmployeeModal();" class="text-cyan-400 hover:underline text-[11px] font-bold">+ አዲስ ሰራተኛ መዝግብ</button>
                                ` : `
                                    <select id="assign-worker-select" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white">
                                        <option value="">-- የፅዳት ሰራተኛ ይምረጡ --</option>
                                        ${cleaners.map(c => `<option value="${c.id}">${c.name} (📞 ${c.phone || c.email})</option>`).join('')}
                                    </select>
                                    <button onclick="submitWorkerAssignment(${orderId})" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold flex items-center justify-center gap-1.5 shadow-md shadow-cyan-950/40">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                        <span>የተመረጠውን ሰራተኛ መድብ</span>
                                    </button>
                                `}
                            </div>

                            <!-- 2. Or Assign Cleaning Team if available -->
                            ${teams.length > 0 ? `
                                <div class="p-3.5 rounded-2xl bg-slate-950/40 border border-slate-800/80 space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <label class="block font-bold text-slate-300">👥 ወይም የተደራጀ ቡድን መድብ (Teams)</label>
                                        <span class="text-[10px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded-full">${teams.length} ቡድኖች</span>
                                    </div>
                                    <select id="assign-team-select" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-white">
                                        <option value="">-- ቡድን ይምረጡ --</option>
                                        ${teams.map(t => `<option value="${t.id}">${t.team_name} (${t.leader?.name || 'መሪ የለውም'})</option>`).join('')}
                                    </select>
                                    <button onclick="submitTeamAssignment(${orderId})" class="w-full py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold flex items-center justify-center gap-1.5">
                                        <span>ይህን ቡድን መድብ</span>
                                    </button>
                                </div>
                            ` : ''}

                            <div class="flex justify-end pt-2 border-t border-slate-800">
                                <button onclick="closeModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl">ዝጋ</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function submitWorkerAssignment(orderId) {
            const selectEl = document.getElementById('assign-worker-select');
            if (!selectEl || !selectEl.value) {
                alert('እባክዎ መጀመሪያ የፅዳት ሰራተኛ ይምረጡ!');
                return;
            }
            const workerId = selectEl.value;
            try {
                const res = await apiFetch(`/api/orders/${orderId}/assign-team`, {
                    method: 'POST',
                    body: JSON.stringify({ worker_id: parseInt(workerId) })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert('✅ ' + (data.message || 'ሰራተኛው ለትዕዛዙ በተሳካ ሁኔታ ተመድቧል!'));
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('ስህተት: ' + (data.message || 'ሰራተኛውን መመደብ አልተቻለም'));
                }
            } catch (err) {
                alert('የሰርቨር ግንኙነት ችግር: ' + err.message);
            }
        }

        async function submitTeamAssignment(orderId) {
            const selectEl = document.getElementById('assign-team-select');
            if (!selectEl || !selectEl.value) {
                alert('እባክዎ ቡድን ይምረጡ!');
                return;
            }
            const teamId = selectEl.value;
            try {
                const res = await apiFetch(`/api/orders/${orderId}/assign-team`, {
                    method: 'POST',
                    body: JSON.stringify({ team_id: parseInt(teamId) })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert('✅ ' + (data.message || 'ቡድኑ ለትዕዛዙ በተሳካ ሁኔታ ተመድቧል!'));
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('ስህተት: ' + (data.message || 'ቡድኑን መመደብ አልተቻለም'));
                }
            } catch (err) {
                alert('የሰርቨር ግንኙነት ችግር: ' + err.message);
            }
        }

        function openFollowupCallModal(followupId, name, phone) {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                            <div>
                                <h3 class="text-base font-bold text-white">የድህረ-ፅዳት ክትትል ጥሪ መዝግብ</h3>
                                <p class="text-xs text-slate-400">${name} (📞 ${phone})</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitFollowupCall(event, ${followupId})" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-400 mb-1">የደንበኛ የእርካታ ደረጃ (ከ 1 እስከ 5 ኮከብ) *</label>
                                <select id="f-rating" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                    <option value="5">⭐⭐⭐⭐⭐ Excellent (5)</option>
                                    <option value="4">⭐⭐⭐⭐ Very Good (4)</option>
                                    <option value="3">⭐⭐⭐ Neutral / Okay (3)</option>
                                    <option value="2">⭐⭐ Dissatisfied (2)</option>
                                    <option value="1">⭐ Unacceptable (1)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">የደንበኛው አስተያየት *</label>
                                <textarea id="f-notes" required rows="3" placeholder="Customer said carpets look brand new..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-3 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl">የጥሪ ውጤት መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
        }

        async function submitFollowupCall(e, followupId) {
            e.preventDefault();
            const rating = parseInt(document.getElementById('f-rating').value);
            const notes = document.getElementById('f-notes').value.trim();
            const outcome = rating >= 4 ? 'satisfied' : (rating <= 2 ? 'complaint' : 'satisfied');

            try {
                const res = await apiFetch(`/api/care/followups/${followupId}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        status: 'completed',
                        outcome: outcome,
                        rating: rating,
                        notes: notes,
                        feedback_notes: notes,
                        satisfaction_score: rating
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    alert('✅ የደንበኛው አስተያየት በተሳካ ሁኔታ ተመዝግቧል! ከክትትል ወረፋው ተወግዶ በደንበኛው የግል ታሪክ ውስጥ ገብቷል።');
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('❌ ስህተት: ' + (data.message || 'የጥሪ ውጤቱን መመዝገብ አልተቻለም'));
                }
            } catch (err) {
                alert('የሰርቨር ግንኙነት ችግር: ' + err.message);
            }
        }

        function openResolveComplaintModal(complaintId) {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                            <h3 class="text-base font-bold text-white">መፍትሄ ስጥ Customer Complaint</h3>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitResolveComplaint(event, ${complaintId})" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-400 mb-1">የተወሰደ የመፍትሄ እርምጃ *</label>
                                <textarea id="comp-action" required rows="3" placeholder="Dispatched supervisor to re-clean spot free of charge..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-3 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl">Mark መፍትሄ ስጥd</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
        }

        async function submitResolveComplaint(e, complaintId) {
            e.preventDefault();
            const action = document.getElementById('comp-action').value;
            await apiFetch(`/api/care/complaints/${complaintId}/resolve`, {
                method: 'POST',
                body: JSON.stringify({ resolution_action: action })
            });
            closeModal();
            loadActiveTab();
        }

        function openProblemReportModal(orderId) {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                            <h3 class="text-base font-bold text-white">የመስክ ችግር ሪፖርት ማድረጊያ</h3>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitProblemReport(event, ${orderId})" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-400 mb-1">Problem የወጪ ምድብ *</label>
                                <select id="prob-category" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                    <option value="customer_not_available">ደንበኛ አልተገኘም / በሩ ተቆልፏል</option>
                                    <option value="water_electricity_cutoff">በቦታው ውኃ ወይም መብራት የለም</option>
                                    <option value="severe_stain_damage">የቆየ የጨርቅ ጉዳት ወይም የማይለቅ እድፍ</option>
                                    <option value="extra_items_requested">ደንበኛው ያልተመዘገቡ ተጨማሪ እቃዎች ጨምሯል</option>
                                    <option value="equipment_issue">የማሽን ወይም የኬሚካል ብልሽት</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">ዝርዝር ማብራሪያ *</label>
                                <textarea id="prob-desc" required rows="3" placeholder="Explain the situation..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-3 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-bold rounded-xl">ለሪሴፕሽን ላክ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
        }

        async function submitProblemReport(e, orderId) {
            e.preventDefault();
            const category = document.getElementById('prob-category').value;
            const description = document.getElementById('prob-desc').value;
            await apiFetch('/api/care/complaints', {
                method: 'POST',
                body: JSON.stringify({
                    order_id: orderId,
                    category: category,
                    description: description,
                    severity: 'medium'
                })
            });
            closeModal();
            alert('Problem report logged and flagged for Reception attention.');
        }

        function openAddOrganizationModal() {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                            <h3 class="text-base font-bold text-white">አዲስ ተቋም / ድርጅት መመዝገቢያ</h3>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitAddOrganization(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-400 mb-1">የድርጅቱ ስም *</label>
                                <input type="text" id="org-name" required placeholder="ለምሳሌ፡ ጊዮን ሆቴል፣ ሉሲ ካፌ፣ ንብ ባንክ" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">የድርጅቱ ዓይነት (ዘርፍ) *</label>
                                    <select id="org-sector" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold">
                                        <option value="hotel">ሆቴል</option>
                                        <option value="restaurant">ሬስቶራንት</option>
                                        <option value="cafe">ካፌ</option>
                                        <option value="office">ቢሮ / ድርጅት</option>
                                        <option value="bank">ባንክ / ፋይናንስ ተቋም</option>
                                        <option value="school">ት/ቤት / ኮሌጅ</option>
                                        <option value="hospital">ሆስፒታል / ክሊኒክ</option>
                                        <option value="real_estate">ሪል እስቴት</option>
                                        <option value="embassy">ኤምባሲ / NGO</option>
                                        <option value="mall">ሞል / የገበያ ማዕከል</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">ያነጋገሩት ኃላፊ ስም *</label>
                                    <input type="text" id="org-contact" required placeholder="ለምሳሌ፡ አቶ ዮሐንስ (ማናጀር)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">ስልክ ቁጥር *</label>
                                    <input type="tel" id="org-phone" required placeholder="0911..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">አድራሻ / ቦታ *</label>
                                    <input type="text" id="org-subcity" required placeholder="ቦሌ / ሃያት / ፒያሳ" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-3 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl">ድርጅቱን መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
        }

        async function submitAddOrganization(e) {
            e.preventDefault();
            await apiFetch('/api/sales/organizations', {
                method: 'POST',
                body: JSON.stringify({
                    name: document.getElementById('org-name').value,
                    industry: document.getElementById('org-sector').value,
                    contact_person: document.getElementById('org-contact').value,
                    phone: document.getElementById('org-phone').value,
                    address: document.getElementById('org-subcity').value,
                })
            });
            closeModal();
            loadActiveTab();
        }

        async function openLogSalesVisitModal() {
            const container = document.getElementById('generic-modal-container');
            const res = await apiFetch('/api/sales/organizations?per_page=100');
            const data = await res.json();
            const orgs = data.data || [];

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-lg w-full p-6 shadow-2xl">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                            <div>
                                <h3 class="text-base font-bold text-white">የመስክ ሽያጭ የስራ እንቅስቃሴ መዝግብ</h3>
                                <p class="text-[11px] text-slate-400">በቀጥታ በ Outdoor Sales Activity Log ኤክሴል ሰንጠረዥ ላይ ይመዘገባል</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitLogSalesVisit(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-400 mb-1">የድርጅቱ ስም *</label>
                                ${orgs.length > 0 ? `
                                    <select id="visit-org" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold mb-2">
                                        ${orgs.map(o => `<option value="${o.id}">${o.name} (${o.industry || 'ተቋም'}) - ${o.address || 'አዲስ አበባ'}</option>`).join('')}
                                    </select>
                                ` : `
                                    <input type="text" id="visit-new-org-name" required placeholder="የድርጅቱ ስም (ለምሳሌ፡ አቢሲንያ ሆቴል)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white mb-2">
                                `}
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">ያነጋገሩት ኃላፊ ስም (ማዕረግ) *</label>
                                    <input type="text" id="visit-contact" required placeholder="ለምሳሌ፡ ብርሃኑ አሰፋ (ማናጀር)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">ስልክ ቁጥር *</label>
                                    <input type="tel" id="visit-phone" required placeholder="0911..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">የደረሱበት ደረጃ *</label>
                                    <select id="visit-stage" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold">
                                        <option value="visited">ተጎብኝቷል (Visited)</option>
                                        <option value="contact_established">በሂደት ላይ (Contact Established)</option>
                                        <option value="interested">ፍላጎት አላቸው (Interested)</option>
                                        <option value="proforma_requested">ፕሮፎርማ ጠይቀዋል (Proforma Requested)</option>
                                        <option value="won">ተስማምተዋል (Won / Contract)</option>
                                        <option value="followup_later">ቀጠሮ ተይዟል (Follow-up)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">ፕሮፎርማ / Quote</label>
                                    <select id="visit-proforma-need" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                        <option value="yes">ያስፈልጋል</option>
                                        <option value="sent">ተልኳል</option>
                                        <option value="no">አያስፈልግም</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">ተጨማሪ ማስታወሻ / ያጋጠመ ነገር *</label>
                                <textarea id="visit-notes" required rows="3" placeholder="ለምሳሌ፡ የሶፋ እና የምንጣፍ ፕሮፎርማ እንድንልክ ጠይቀዋል፤ ከባለቤቱ ጋር ተነጋግራ ልትደውልልን ቀጥረናለች..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white leading-relaxed"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-5 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl">በሰንጠረዡ መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
        }

        async function submitLogSalesVisit(e) {
            e.preventDefault();
            try {
                const orgSelect = document.getElementById('visit-org');
                let orgId = orgSelect ? orgSelect.value : null;

                if (!orgId) {
                    const orgNameInput = document.getElementById('visit-new-org-name');
                    const orgName = orgNameInput ? orgNameInput.value.trim() : 'ተቋም';
                    const newOrgRes = await apiFetch('/api/sales/organizations', {
                        method: 'POST',
                        body: JSON.stringify({
                            name: orgName,
                            industry: 'office',
                            contact_person: document.getElementById('visit-contact').value,
                            phone: document.getElementById('visit-phone').value,
                            address: 'Addis Ababa',
                        })
                    });
                    const orgResult = await newOrgRes.json();
                    orgId = orgResult.organization?.id;
                }

                if (!orgId) {
                    alert('ስህተት፡ እባክዎን ድርጅት ይምረጡ ወይም ስሙን ያስገቡ።');
                    return;
                }

                const stage = document.getElementById('visit-stage').value;
                const notes = document.getElementById('visit-notes').value;
                const contact = document.getElementById('visit-contact').value;
                const phone = document.getElementById('visit-phone').value;

                const visitRes = await apiFetch('/api/sales/visits', {
                    method: 'POST',
                    body: JSON.stringify({
                        organization_id: orgId,
                        contact_person: contact,
                        phone: phone,
                        contact_phone: phone,
                        address: 'Addis Ababa',
                        stage: stage,
                        interest_level: 'medium',
                        notes: notes,
                        discussion_notes: notes,
                        visit_date: new Date().toISOString().split('T')[0],
                    })
                });

                if (visitRes.ok) {
                    alert('✓ የመስክ ሽያጭ የስራ እንቅስቃሴው በተሳካ ሁኔታ በሰንጠረዡ ላይ ተመዝግቧል!');
                    closeModal();
                    loadActiveTab();
                } else {
                    const errData = await visitRes.json();
                    alert('ምዝገባው አልተሳካም፡ ' + (errData.message || JSON.stringify(errData.errors || '')));
                }
            } catch (err) {
                alert('የኔትወርክ ስህተት፡ ' + err.message);
            }
        }

        // ==========================================
        // PROFORMA GENERATOR & OFFICIAL PDF PRINT MODAL
        // ==========================================
        function openPrintProformaDirectModal(orgName, contactName, phone, address) {
            openCreateProformaModal(orgName, contactName, phone, address);
        }

        function openCreateProformaModal(defaultOrg = '', defaultContact = '', defaultPhone = '', defaultAddress = '') {
            const todayEth = EC.todayEth();
            const dateStr = `${todayEth.day}/${todayEth.month}/${todayEth.year}`;
            const profNum = 'MSH-PRF-' + Math.floor(1000 + Math.random() * 9000);

            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4 overflow-y-auto">
                    <div class="bg-white text-slate-900 rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl space-y-6 my-auto max-h-[92vh] overflow-y-auto border border-slate-300">
                        <!-- Top Controls: Print and Close -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200 print:hidden">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-blue-100 text-blue-900 font-extrabold text-xs rounded-full">📄 ይፋዊ ፕሮፎርማ ሰነድ</span>
                                <span class="text-xs text-slate-500 font-mono">Ref: ${profNum}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="window.print()" class="px-5 py-2.5 bg-gradient-to-r from-blue-700 to-cyan-600 hover:from-blue-600 hover:to-cyan-500 text-white font-extrabold text-xs rounded-xl shadow-lg flex items-center gap-2 cursor-pointer transition-all">
                                    <i data-lucide="printer" class="w-4 h-4"></i>
                                    <span>🖨️ አትም / በ PDF አውርድ</span>
                                </button>
                                <button onclick="closeModal()" class="px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl">ዝጋ</button>
                            </div>
                        </div>

                        <!-- OFFICIAL PRINTABLE PROFORMA SHEET -->
                        <div id="printable-proforma-area" class="space-y-6 text-slate-900 font-sans p-2">
                            <!-- Company Official Header & Logo -->
                            <div class="flex items-start justify-between border-b-2 border-blue-900 pb-5">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-cyan-600 to-blue-700 flex items-center justify-center text-white font-black text-2xl shadow-md">
                                            M
                                        </div>
                                        <div>
                                            <h1 class="text-xl sm:text-2xl font-black text-blue-950 tracking-tight">MEASH CLEANING SOLUTION</h1>
                                            <p class="text-xs font-bold text-blue-800">ሜሽ የፅዳት እና የህንፃ አያያዝ አገልግሎት</p>
                                        </div>
                                    </div>
                                    <div class="text-[11px] text-slate-600 mt-2 space-y-0.5">
                                        <p>📍 አድራሻ፡ ቦሌ ክፍለ ከተማ፣ አዲስ አበባ፣ ኢትዮጵያ</p>
                                        <p>📞 ስልክ፡ <strong>0943854325</strong> / 0911000003</p>
                                        <p>🌐 ድረ-ገጽ፡ https://meash-cleaning.et | Email: info@meash.et</p>
                                        <p>🆔 የግብር ከፋይ መለያ (TIN): <strong>0098765432</strong></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="inline-block bg-blue-50 border border-blue-200 rounded-xl p-3 text-right">
                                        <span class="block text-[10px] uppercase font-bold text-blue-700 tracking-wider">የዋጋ ማቅረቢያ (PROFORMA)</span>
                                        <span class="block text-base font-black text-blue-950 font-mono mt-0.5">${profNum}</span>
                                        <span class="block text-xs font-bold text-slate-700 mt-1">ቀን፡ ${dateStr} ዓ.ም</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer / Organization Details (Editable fields) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs">
                                <div>
                                    <span class="text-[10px] font-extrabold uppercase text-slate-500 block mb-1">የደንበኛው / የድርጅቱ መረጃ፦</span>
                                    <input type="text" id="prof-org-name" value="${defaultOrg || 'አቢሲንያ ሆቴል'}" placeholder="የድርጅቱ ስም" class="w-full font-bold text-sm bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 mb-1.5">
                                    <input type="text" id="prof-contact" value="${defaultContact || 'አቶ ብርሃኑ አሰፋ (ማናጀር)'}" placeholder="ያነጋገሩት ኃላፊ" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1 mb-1.5 text-xs">
                                </div>
                                <div>
                                    <span class="text-[10px] font-extrabold uppercase text-slate-500 block mb-1">አድራሻ እና ስልክ፦</span>
                                    <input type="tel" id="prof-phone" value="${defaultPhone || '0912121212'}" placeholder="ስልክ ቁጥር" class="w-full font-mono bg-white border border-slate-300 rounded-lg px-2.5 py-1 mb-1.5 text-xs">
                                    <input type="text" id="prof-address" value="${defaultAddress || 'አዲስ አበባ፣ ሃያት'}" placeholder="አድራሻ / ቦታ" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1 text-xs">
                                </div>
                            </div>

                            <!-- Services / Items Table (Interactive) -->
                            <div>
                                <div class="flex items-center justify-between mb-2 print:hidden">
                                    <span class="text-xs font-bold text-slate-700">የአገልግሎቶች እና የዋጋ ዝርዝር፦</span>
                                    <button type="button" onclick="addProformaRow()" class="px-3 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-100 font-bold text-xs flex items-center gap-1 cursor-pointer">
                                        <span>+ ንጥል ጨምር (Add Item)</span>
                                    </button>
                                </div>
                                <table class="w-full border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-blue-950 text-white font-bold text-[11px] uppercase">
                                            <th class="border border-slate-700 p-2 text-center w-8">ተ.ቁ</th>
                                            <th class="border border-slate-700 p-2 text-left">የአገልግሎቱ ዝርዝር (Service Description)</th>
                                            <th class="border border-slate-700 p-2 text-center w-24">ብዛት</th>
                                            <th class="border border-slate-700 p-2 text-right w-24">ነጠላ ዋጋ</th>
                                            <th class="border border-slate-700 p-2 text-right w-28">ጠቅላላ (ETB)</th>
                                            <th class="border border-slate-700 p-2 text-center w-8 print:hidden"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="prof-items-body" class="divide-y divide-slate-300">
                                        <tr class="prof-row">
                                            <td class="border border-slate-300 p-1.5 text-center font-bold text-slate-500 row-idx">1</td>
                                            <td class="border border-slate-300 p-1">
                                                <input type="text" value="የቢሮ እና የኮሪደር ምንጣፍ ጥልቅ ፅዳት (Carpet Deep Shampooing)" class="w-full bg-transparent font-medium border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-desc">
                                            </td>
                                            <td class="border border-slate-300 p-1">
                                                <input type="text" value="150 ካሬ" oninput="recalcProforma()" class="w-full bg-transparent text-center font-mono border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-qty">
                                            </td>
                                            <td class="border border-slate-300 p-1">
                                                <input type="number" value="50" oninput="recalcProforma()" class="w-full bg-transparent text-right font-mono border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-price">
                                            </td>
                                            <td class="border border-slate-300 p-1.5 text-right font-mono font-bold text-slate-800 item-total">7,500.00</td>
                                            <td class="border border-slate-300 p-1 text-center print:hidden">
                                                <button type="button" onclick="removeProformaRow(this)" class="text-red-500 hover:text-red-700 font-bold px-1">&times;</button>
                                            </td>
                                        </tr>
                                        <tr class="prof-row">
                                            <td class="border border-slate-300 p-1.5 text-center font-bold text-slate-500 row-idx">2</td>
                                            <td class="border border-slate-300 p-1">
                                                <input type="text" value="የቢሮ መቀመጫ ሶፋዎች እና ወንበሮች እጥበት (Office Sofa & Chairs Cleaning)" class="w-full bg-transparent font-medium border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-desc">
                                            </td>
                                            <td class="border border-slate-300 p-1">
                                                <input type="text" value="20 ወንበር" oninput="recalcProforma()" class="w-full bg-transparent text-center font-mono border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-qty">
                                            </td>
                                            <td class="border border-slate-300 p-1">
                                                <input type="number" value="150" oninput="recalcProforma()" class="w-full bg-transparent text-right font-mono border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-price">
                                            </td>
                                            <td class="border border-slate-300 p-1.5 text-right font-mono font-bold text-slate-800 item-total">3,000.00</td>
                                            <td class="border border-slate-300 p-1 text-center print:hidden">
                                                <button type="button" onclick="removeProformaRow(this)" class="text-red-500 hover:text-red-700 font-bold px-1">&times;</button>
                                            </td>
                                        </tr>
                                        <tr class="prof-row">
                                            <td class="border border-slate-300 p-1.5 text-center font-bold text-slate-500 row-idx">3</td>
                                            <td class="border border-slate-300 p-1">
                                                <input type="text" value="የህንፃ የውስጥ እና የውጭ መስታወት እጥበት (Facade & Window Cleaning)" class="w-full bg-transparent font-medium border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-desc">
                                            </td>
                                            <td class="border border-slate-300 p-1">
                                                <input type="text" value="1 ስራ" oninput="recalcProforma()" class="w-full bg-transparent text-center font-mono border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-qty">
                                            </td>
                                            <td class="border border-slate-300 p-1">
                                                <input type="number" value="4500" oninput="recalcProforma()" class="w-full bg-transparent text-right font-mono border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-price">
                                            </td>
                                            <td class="border border-slate-300 p-1.5 text-right font-mono font-bold text-slate-800 item-total">4,500.00</td>
                                            <td class="border border-slate-300 p-1 text-center print:hidden">
                                                <button type="button" onclick="removeProformaRow(this)" class="text-red-500 hover:text-red-700 font-bold px-1">&times;</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-bold border-t-2 border-slate-400">
                                            <td colspan="4" class="p-2 text-right text-slate-700">ንዑስ ድምር (Subtotal):</td>
                                            <td id="prof-subtotal" class="p-2 text-right font-mono text-slate-900">15,000.00 ETB</td>
                                            <td class="print:hidden"></td>
                                        </tr>
                                        <tr class="font-bold">
                                            <td colspan="4" class="p-2 text-right text-slate-700">ተጨማሪ እሴት ታክስ (15% VAT):</td>
                                            <td id="prof-vat" class="p-2 text-right font-mono text-slate-900">2,250.00 ETB</td>
                                            <td class="print:hidden"></td>
                                        </tr>
                                        <tr class="font-black text-sm bg-blue-50 border-t-2 border-blue-900">
                                            <td colspan="4" class="p-2.5 text-right text-blue-950">ጠቅላላ ክፍያ (Total Amount):</td>
                                            <td id="prof-grand-total" class="p-2.5 text-right font-mono text-blue-950 text-base">17,250.00 ETB</td>
                                            <td class="print:hidden"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <!-- Terms & Stamp Section -->
                            <div class="pt-4 border-t border-slate-300 grid grid-cols-1 sm:grid-cols-2 gap-6 items-end">
                                <div class="text-[11px] text-slate-600 space-y-1">
                                    <p class="font-bold text-slate-800 uppercase">ውሎች እና የክፍያ ሁኔታዎች፦</p>
                                    <p>• ይህ የዋጋ ማቅረቢያ ለ30 (ሰላሳ) ቀናት ፀንቶ ይቆያል።</p>
                                    <p>• ስራ ከመጀመሩ በፊት 50% ቅድመ ክፍያ፣ ስራው ተጠናቆ ሲረከቡ ቀሪው 50% ይፈጸማል።</p>
                                    <p>• ክፍያ በሲቢኢ ብር፣ ቴሌብር ወይም በባንክ ዝውውር መክፈል ይቻላል።</p>
                                </div>
                                <div class="flex flex-col items-center justify-center text-center">
                                    <div class="relative w-36 h-36 flex items-center justify-center">
                                        <!-- Official Circular Stamp Graphic -->
                                        <div class="w-32 h-32 rounded-full border-4 border-dashed border-blue-800 flex flex-col items-center justify-center p-2 text-blue-900 select-none rotate-[-6deg] opacity-90 shadow-inner">
                                            <span class="text-[9px] font-black uppercase tracking-wider">MEASH CLEANING</span>
                                            <span class="text-[8px] font-bold">★ OFFICIAL STAMP ★</span>
                                            <span class="text-[14px] font-black text-blue-800 my-0.5">ሜሽ</span>
                                            <span class="text-[7px] font-mono font-bold">TIN: 0098765432</span>
                                            <span class="text-[8px] font-extrabold uppercase">APPROVED</span>
                                        </div>
                                    </div>
                                    <div class="w-48 border-b border-slate-700 mt-1"></div>
                                    <span class="text-[10px] font-bold text-slate-700 mt-1">የተፈቀደው ኃላፊ ፊርማ እና ማህተም</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        function addProformaRow() {
            const tbody = document.getElementById('prof-items-body');
            if (!tbody) return;
            const count = tbody.querySelectorAll('tr').length + 1;
            const tr = document.createElement('tr');
            tr.className = 'prof-row';
            tr.innerHTML = `
                <td class="border border-slate-300 p-1.5 text-center font-bold text-slate-500 row-idx">${count}</td>
                <td class="border border-slate-300 p-1">
                    <input type="text" placeholder="የአገልግሎቱ ዝርዝር ስም..." class="w-full bg-transparent font-medium border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-desc">
                </td>
                <td class="border border-slate-300 p-1">
                    <input type="text" value="1" oninput="recalcProforma()" class="w-full bg-transparent text-center font-mono border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-qty">
                </td>
                <td class="border border-slate-300 p-1">
                    <input type="number" value="1000" oninput="recalcProforma()" class="w-full bg-transparent text-right font-mono border-0 focus:ring-1 focus:ring-blue-500 rounded p-1 text-xs item-price">
                </td>
                <td class="border border-slate-300 p-1.5 text-right font-mono font-bold text-slate-800 item-total">1,000.00</td>
                <td class="border border-slate-300 p-1 text-center print:hidden">
                    <button type="button" onclick="removeProformaRow(this)" class="text-red-500 hover:text-red-700 font-bold px-1">&times;</button>
                </td>
            `;
            tbody.appendChild(tr);
            recalcProforma();
        }

        function removeProformaRow(btn) {
            const row = btn.closest('tr');
            if (row) {
                row.remove();
                const rows = document.querySelectorAll('#prof-items-body tr');
                rows.forEach((r, idx) => {
                    const idxEl = r.querySelector('.row-idx');
                    if (idxEl) idxEl.textContent = idx + 1;
                });
                recalcProforma();
            }
        }

        function recalcProforma() {
            let subtotal = 0;
            const rows = document.querySelectorAll('#prof-items-body tr');
            rows.forEach(r => {
                const qtyVal = parseFloat(r.querySelector('.item-qty')?.value) || 1;
                const priceVal = parseFloat(r.querySelector('.item-price')?.value) || 0;
                const total = qtyVal * priceVal;
                subtotal += total;
                const totalEl = r.querySelector('.item-total');
                if (totalEl) totalEl.textContent = total.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            });

            const vat = subtotal * 0.15;
            const grandTotal = subtotal + vat;

            const subEl = document.getElementById('prof-subtotal');
            if (subEl) subEl.textContent = subtotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ETB';

            const vatEl = document.getElementById('prof-vat');
            if (vatEl) vatEl.textContent = vat.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ETB';

            const grandEl = document.getElementById('prof-grand-total');
            if (grandEl) grandEl.textContent = grandTotal.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ETB';
        }

        // ==========================================
        // TEAM LIVE LOCATION MAP MODAL (Modern Addis Ababa Pulse View)
        // ==========================================
        function openTeamLiveLocationMap(teamId, teamName, leaderName, phone, membersCount, activeJobs, lat, lng) {
            lat = lat || 9.025;
            lng = lng || 38.746;
            const isWorking = (activeJobs > 0);

            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-2xl w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full ${isWorking ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'}"></span>
                                    <h3 class="text-base font-extrabold text-white">${teamName} — የቀጥታ የመስክ መገኛ</h3>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">${isWorking ? 'ቡድኑ በስራ ስምሪት ላይ ስለሆነ የቀጥታ ጂፒኤስ (GPS) መገኛ ይታያል።' : 'ቡድኑ ንቁ ስራ ላይ አይደለም (የግል መገኛ ለደህንነት ተደብቋል)።'}</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <!-- Team Info Pills -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                            <div class="p-2.5 rounded-xl bg-slate-800/90 border border-slate-700">
                                <span class="text-[10px] text-slate-400 block">የቡድን መሪ</span>
                                <strong class="text-white text-xs">${leaderName}</strong>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-800/90 border border-slate-700">
                                <span class="text-[10px] text-slate-400 block">ስልክ ቁጥር</span>
                                <strong class="text-cyan-400 font-mono text-xs">${phone}</strong>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-800/90 border border-slate-700">
                                <span class="text-[10px] text-slate-400 block">የቡድን አባላት</span>
                                <strong class="text-white text-xs">${membersCount} አባላት</strong>
                            </div>
                            <div class="p-2.5 rounded-xl bg-slate-800/90 border border-slate-700">
                                <span class="text-[10px] text-slate-400 block">የአሁን ሁኔታ</span>
                                <strong class="${isWorking ? 'text-emerald-400' : 'text-slate-400'} text-xs">${isWorking ? '● ንቁ / በስራ ላይ' : '○ እረፍት / ዝግጁ'}</strong>
                            </div>
                        </div>

                        <!-- Interactive Leaflet Map or Privacy Notice -->
                        ${isWorking ? `
                            <div class="relative w-full h-80 rounded-2xl overflow-hidden border border-slate-700">
                                <div id="team-live-map-canvas" class="w-full h-full bg-slate-950"></div>
                                <div class="absolute top-3 left-3 z-[1000] bg-slate-900/90 backdrop-blur border border-slate-700 rounded-xl px-3 py-1.5 text-[11px] font-bold text-white shadow flex items-center gap-1.5">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-cyan-400"></i>
                                    <span>የስራ ቦታ አካባቢ፡ ቦሌ / መስቀል ፍላወር፣ አዲስ አበባ</span>
                                </div>
                            </div>
                        ` : `
                            <div class="p-6 rounded-2xl bg-slate-800/60 border border-slate-700 text-center space-y-2">
                                <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center mx-auto text-xl font-bold">🔒</div>
                                <h4 class="text-sm font-bold text-white">የሰራተኞች ግላዊነት እና ደህንነት ጥበቃ</h4>
                                <p class="text-xs text-slate-400 max-w-md mx-auto leading-relaxed">ቡድኑ በአሁኑ ሰዓት ንቁ የደንበኛ የስራ ስምሪት ላይ አይደለም። ሰራተኞች ወደ ቤታቸው በሚሄዱበት ወይም ስራ በሌለበት ጊዜ የቤት አድራሻ እንዳይታይ መገኛቸው በራስ-ሰር ይዘጋል። አዲስ ስራ ሲጀምሩ ካርታው ወዲያውኑ ይከፈታል።</p>
                            </div>
                        `}

                        <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-xs">
                            <span class="text-slate-400">${isWorking ? 'GPS Ping: የቀጥታ መረጃ በየጊዜው ይታደሳል' : 'የደህንነት ሁኔታ: ጥበቃ የተደረገለት'}</span>
                            <div class="flex gap-2">
                                <a href="tel:${phone}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl flex items-center gap-1.5">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                    <span>ደውል</span>
                                </a>
                                <button onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ዝጋ</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            lucide.createIcons();

            if (isWorking) {
                setTimeout(() => {
                    try {
                        const mapEl = document.getElementById('team-live-map-canvas');
                        if (!mapEl) return;
                        const map = L.map('team-live-map-canvas').setView([lat, lng], 14);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap'
                        }).addTo(map);

                        const customIcon = L.divIcon({
                            className: 'team-pulse-marker',
                            html: `
                                <div style="position:relative; display:flex; align-items:center; justify-content:center;">
                                    <div style="position:absolute; width:44px; height:44px; border-radius:50%; background:rgba(6,182,212,0.3); animation: ping 1.5s cubic-bezier(0,0,0.2,1) infinite;"></div>
                                    <div style="width:36px; height:36px; border-radius:50%; background:#0284c7; border:3px solid #ffffff; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.4); font-size:16px;">
                                        🚗
                                    </div>
                                </div>
                            `,
                            iconSize: [44, 44],
                            iconAnchor: [22, 22]
                        });

                        L.marker([lat, lng], { icon: customIcon })
                            .addTo(map)
                            .bindPopup(`<b>${teamName}</b><br>መሪ፡ ${leaderName}<br>📞 ${phone}`)
                            .openPopup();
                    } catch (e) {
                        console.error('Map init error:', e);
                    }
                }, 200);
            }
        }

        // ==========================================
        // POSTPONE ORDER MODAL & NOTIFICATION ENGINE
        // ==========================================
        function openPostponeOrderModal(orderId, orderNumber, customerName) {
            const container = document.getElementById('generic-modal-container');
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().split('T')[0];

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-amber-500/40 rounded-3xl max-w-lg w-full p-6 shadow-2xl my-8">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center">
                                    <i data-lucide="calendar-clock" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-white">የቀጠሮ ማስተላለፊያ ቅጽ (Postpone Order)</h3>
                                    <span class="text-[11px] text-cyan-400 font-semibold">${orderNumber} - ${customerName}</span>
                                </div>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-lg">&times;</button>
                        </div>

                        <form onsubmit="submitPostponeOrder(event, ${orderId})" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1.5">አዲሱ የቀጠሮ ቀን (New Date) *</label>
                                <input type="date" id="postpone-date" required value="${tomorrowStr}" min="${new Date().toISOString().split('T')[0]}" onchange="const eth=EC.toEth(this.value); document.getElementById('postpone-eth-hint').textContent='🗓 ' + EC.formatEth(eth.year, eth.month, eth.day);" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                                <p id="postpone-eth-hint" class="text-[10px] text-amber-400 font-bold mt-1">🗓 ${EC.formatEth(tomorrow)}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1.5">የቀጠሮ ሰዓት (ጠዋት ወይም ከሰዓት ብቻ) *</label>
                                <select id="postpone-slot" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                                    <option value="morning">🌅 ጥዋት (2:00 - 6:00 ጠዋቱ ETH • Morning 8:00 AM - 12:00 PM)</option>
                                    <option value="afternoon">☀️ ከሰዓት (7:00 - 11:00 ከሰዓቱ ETH • Afternoon 1:00 PM - 5:00 PM)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1.5">የተላለፈበት ምክንያት (Reason) *</label>
                                <select id="postpone-reason-preset" onchange="if(this.value!=='ሌላ'){document.getElementById('postpone-reason').value=this.value;}" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-500 mb-2">
                                    <option value="የቡድን ስራ መደራረብ (Team Overbooked / Cleaner engaged)">የቡድን ስራ መደራረብ (Team Overbooked / Cleaner engaged)</option>
                                    <option value="ሰራተኛ ባለመገኘቱ (Cleaner unavailable)">ሰራተኛ ባለመገኘቱ (Cleaner unavailable)</option>
                                    <option value="የትራንስፖርት መዘግየት (Transport / Traffic Delay)">የትራንስፖርት መዘግየት (Transport / Traffic Delay)</option>
                                    <option value="የደንበኛ ጥያቄ (Customer Requested Postponement)">የደንበኛ ጥያቄ (Customer Requested Postponement)</option>
                                    <option value="የመሳሪያ ጥገና ወይም እጥረት (Equipment Maintenance)">የመሳሪያ ጥገና ወይም እጥረት (Equipment Maintenance)</option>
                                    <option value="ሌላ">ሌላ ምክንያት (ሌሎች ወጪዎች - Type below)...</option>
                                </select>
                                <textarea id="postpone-reason" required rows="2" placeholder="ዝርዝር ምክንያት ይጻፉ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none focus:border-amber-500">የቡድን ስራ መደራረብ (Team Overbooked / Cleaner engaged)</textarea>
                            </div>

                            <div class="p-3 rounded-2xl bg-amber-950/40 border border-amber-500/30 text-amber-300 text-[11px] leading-relaxed flex items-center gap-2">
                                <i data-lucide="info" class="w-4 h-4 shrink-0 text-amber-400"></i>
                                <span>ይህንን ሲያረጋግጡ ወዲያውኑ ለደንበኛው <strong>SMS እና የቴሌግራም መልዕክት</strong> በአዲሱ ቀን እና ምክንያት ይላካል::</span>
                            </div>

                            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl">ሰርዝ (Cancel)</button>
                                <button type="submit" id="btn-submit-postpone" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white text-xs font-extrabold rounded-xl shadow-lg flex items-center gap-1.5">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    <span>ቀጠሮውን አስተላልፍ & SMS/ቴሌግራም ላክ</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function submitPostponeOrder(e, orderId) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-postpone');
            btn.disabled = true;
            btn.innerText = 'በማስተላለፍ ላይ...';

            const payload = {
                new_appointment_date: document.getElementById('postpone-date').value,
                new_time_slot: document.getElementById('postpone-slot').value,
                reason: document.getElementById('postpone-reason').value,
            };

            try {
                const res = await apiFetch(`/api/orders/${orderId}/postpone`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (res.ok) {
                    closeModal();
                    alert('✅ ' + data.message);
                    // Refresh current view
                    switchRole(currentUser.role);
                } else {
                    alert('❌ ስህተት: ' + (data.message || 'ቀጠሮውን ማስተላለፍ አልተቻለም::'));
                }
            } catch (err) {
                alert('❌ የግንኙነት ስህተት: ' + err.message);
            } finally {
                btn.disabled = false;
            }
        }

        // PWA Service Worker Registration & Install Prompt Handler
        let deferredPrompt = null;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            const btn = document.getElementById('btn-pwa-install');
            if (btn) btn.classList.remove('hidden');
        });

        async function triggerPwaInstall() {
            if (!deferredPrompt) {
                alert('መተግበሪያው አስቀድሞ ተጭኗል ወይም በብሮውዘርዎ ሜኑ (Menu -> Add to Home Screen) መጫን ይችላሉ።');
                return;
            }
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`User response to install prompt: ${outcome}`);
            deferredPrompt = null;
            const btn = document.getElementById('btn-pwa-install');
            if (btn) btn.classList.add('hidden');
        }

        async function requestPushNotification() {
            if (!('Notification' in window)) {
                alert('ይህ ብሮውዘር ማሳወቂያዎችን (Notifications) አይደግፍም።');
                return;
            }
            const perm = await Notification.requestPermission();
            if (perm === 'granted') {
                if ('serviceWorker' in navigator && navigator.serviceWorker.ready) {
                    const reg = await navigator.serviceWorker.ready;
                    reg.showNotification('ሜሽ የፅዳት አገልግሎት | Mesh Cleaning', {
                        body: '✅ ማሳወቂያዎች በተሳካ ሁኔታ ተፈቅደዋል! የትዕዛዝ እና የቀጠሮ ማንቂያዎች ይደርሱዎታል።',
                        icon: '/assets/icon-192.png',
                        badge: '/assets/icon-192.png',
                        vibrate: [200, 100, 200]
                    });
                } else {
                    new Notification('ሜሽ የፅዳት አገልግሎት | Mesh Cleaning', {
                        body: '✅ ማሳወቂያዎች በተሳካ ሁኔታ ተፈቅደዋል!',
                        icon: '/assets/icon-192.png'
                    });
                }
                alert('✅ ማሳወቂያዎች ተፈቅደዋል! የስልክዎ/የኮምፒውተርዎ የስክሪን ማሳወቂያ ደርሶዎታል።');
            } else {
                alert('⚠️ ማሳወቂያዎች አልተፈቀዱም። እባክዎ በብሮውዘር ቅንብር ውስጥ ይፍቀዱ።');
            }
        }

        // Register Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then((reg) => {
                    console.log('Mesh ServiceWorker registered successfully:', reg.scope);
                }).catch((err) => {
                    console.warn('Mesh ServiceWorker registration failed:', err);
                });
            });
        }
    </script>
</body>
</html>
