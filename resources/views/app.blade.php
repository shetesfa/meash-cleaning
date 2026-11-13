<!DOCTYPE html>
<html lang="am" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>MEASH CLEANING SOLUTION | Staff & Admin Login</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0F172A">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Mesh OS">
    <link rel="apple-touch-icon" href="/assets/icon-192.png">

        <!-- Fonts & Minified Compiled CSS -->
    <link rel="stylesheet" href="/css/fonts.css">
    <link rel="stylesheet" href="/css/meash.css">

    <!-- Self-Hosted Vendor Libraries -->
    <script src="/vendor/lucide.min.js"></script>
    <script src="/vendor/chart.umd.js"></script>
    <script src="/vendor/leaflet/leaflet.js"></script>
    <script src="/vendor/qrcode-generator.min.js"></script>
    <link rel="stylesheet" href="/vendor/leaflet/leaflet.css">

    <!-- Safety Fallbacks -->
    <script>
        if (typeof lucide === 'undefined') {
            window.lucide = { createIcons: function(){} };
            console.warn('Lucide icon library not loaded; using fallback');
        }
        if (typeof Chart === 'undefined') {
            window.Chart = function() { console.warn('Chart.js not loaded'); return { destroy: function(){}, update: function(){} }; };
        }
        if (typeof L === 'undefined') {
            window.L = { map: function(){}, tileLayer: function(){}, marker: function(){} };
        }
    </script>
    <!-- Engines -->
    <script src="/js/meash-i18n.js"></script>
    <script src="/js/meash-offline.js?v=2.3"></script>
    <script>
        // Universal Fail-Safe: Ensure cacheItems exists on MeashOfflineEngine & window.meashOffline
        (function() {
            function patchOfflineEngine() {
                const patchFn = async function(storeName, items) {
                    if (!this.db || !Array.isArray(items)) return;
                    return new Promise((resolve) => {
                        try {
                            const tx = this.db.transaction([storeName], 'readwrite');
                            const store = tx.objectStore(storeName);
                            items.forEach(it => { try { store.put(it); } catch (_) {} });
                            tx.oncomplete = () => resolve(true);
                            tx.onerror = () => resolve(false);
                        } catch (_) { resolve(false); }
                    });
                };

                if (typeof MeashOfflineEngine !== 'undefined') {
                    if (!MeashOfflineEngine.prototype.cacheItems) {
                        MeashOfflineEngine.prototype.cacheItems = patchFn;
                    }
                }
                if (window.meashOffline) {
                    if (typeof window.meashOffline.cacheItems !== 'function') {
                        window.meashOffline.cacheItems = patchFn;
                    }
                }
            }
            patchOfflineEngine();
            document.addEventListener('DOMContentLoaded', patchOfflineEngine);
            window.addEventListener('load', patchOfflineEngine);
        })();
    </script>
    <style>
        :root {
            --sat: env(safe-area-inset-top, 0px);
            --sab: env(safe-area-inset-bottom, 0px);
        }
        *, *::before, *::after {
            box-sizing: border-box;
        }
        html, body {
            overflow-x: hidden !important;
            width: 100% !important;
            max-width: 100vw !important;
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

        /* Mobile Responsiveness & Modal Safety: All action buttons reachable on small screens */
        div[class*="fixed inset-0 z-50"]:not(#mobile-sidebar-drawer) {
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch !important;
        }
        div[class*="fixed inset-0 z-50"]:not(#mobile-sidebar-drawer) > div:not(#mobile-sidebar-drawer):not(nav) {
            max-height: 92vh !important;
            overflow-y: auto !important;
            margin-top: auto !important;
            margin-bottom: auto !important;
        }
        @media (max-width: 640px) {
            .overflow-x-auto {
                -webkit-overflow-scrolling: touch !important;
                scrollbar-width: thin;
            }
        }
    </style>
</head>
<body class="h-full text-slate-100 flex flex-col bg-slate-950 antialiased overflow-hidden">

    <!-- 0. SECURE STAFF LOGIN SCREEN (Displayed when unauthenticated) -->
    <div id="app-login-screen" class="hidden fixed inset-0 z-50 bg-slate-950 flex items-center justify-center p-4 overflow-y-auto">
        <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative">
            <div class="text-center mb-6">
                <img src="/logo.jpg" alt="Meash Logo" class="w-16 h-16 rounded-2xl mx-auto mb-3 object-cover border border-cyan-500/40 shadow-lg shadow-cyan-500/20">
                <h2 class="text-xl font-extrabold text-white tracking-tight">MEASH CLEANING SOLUTION</h2>
                <p class="text-xs text-cyan-400 font-bold uppercase tracking-wider mt-1">የሰራተኞች እና አስተዳዳሪ መግቢያ • Staff & Admin Login</p>
                <p class="text-xs text-slate-400 mt-2">የሰራተኞች እና የአስተዳዳሪ ደህንነቱ የተጠበቀ መግቢያ</p>
            </div>

            <!-- Error Banner -->
            <div id="login-error-banner" class="hidden mb-4 p-3 rounded-xl bg-red-950/80 border border-red-500/50 text-red-200 text-xs flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-4 h-4 text-red-400 shrink-0"></i>
                <span id="login-error-text">የተሳሳተ መረጃ አስገብተዋል</span>
            </div>

            <form onsubmit="handleAppLoginSubmit(event)" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">የተጠቃሚ ስም ወይም ስልክ (Username or Phone)</label>
                    <div class="relative">
                        <i data-lucide="user" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" id="app-login-input" required placeholder="የተጠቃሚ ስም ወይም ስልክ ያስገቡ" autocomplete="username" class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs focus:outline-none focus:border-cyan-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-1.5">የይለፍ ቃል (Password)</label>
                    <div class="relative">
                        <i data-lucide="lock" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="password" id="app-password-input" required placeholder="••••••••••••" autocomplete="current-password" class="w-full pl-10 pr-10 py-2.5 rounded-xl bg-slate-800 border border-slate-700 text-white text-xs focus:outline-none focus:border-cyan-500">
                        <button type="button" onclick="togglePasswordVisibility('app-password-input')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" id="btn-app-login" class="w-full mt-2 py-3 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-extrabold text-xs shadow-lg shadow-cyan-500/20 flex items-center justify-center gap-2 transition-all cursor-pointer">
                    <i data-lucide="log-in" class="w-4 h-4"></i>
                    <span id="btn-app-login-text">ወደ ሲስተም ግባ (Login)</span>
                </button>
            </form>

            <div class="mt-5 pt-4 border-t border-slate-800 text-center">
                <a href="/" class="text-xs text-slate-400 hover:text-cyan-400 font-semibold inline-flex items-center gap-1.5 transition">
                    <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                    <span>ወደ ደንበኞች ድረ-ገጽ ተመለስ (Back to Website)</span>
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
                <span id="header-role-badge">ዋና ስራ አስኪያጅ (GM)</span>
                <i data-lucide="chevron-down" id="role-chevron" class="w-3.5 h-3.5 text-slate-400 transition-transform"></i>
            </button>

            <!-- Floating Role Menu with Cleaning Groups by Name -->
            <div id="role-dropdown-menu" class="hidden absolute left-1/2 -translate-x-1/2 mt-2 w-64 bg-slate-900 border border-slate-700 rounded-2xl p-3 shadow-2xl z-50 space-y-2">
                <div class="px-2 py-1 border-b border-slate-800 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                    የተጠቃሚ መረጃ (Current Profile)
                </div>
                <div class="px-2 py-1.5 bg-slate-800/60 rounded-xl">
                    <p class="text-xs font-bold text-white" id="dropdown-user-name">ተጠቃሚ</p>
                    <p class="text-[10px] text-cyan-400 font-mono mt-0.5" id="dropdown-user-role">role</p>
                </div>
                <button type="button" onclick="logoutUser(); closeRoleDropdown();" class="w-full flex items-center gap-2 p-2.5 rounded-xl hover:bg-red-950/60 text-xs font-bold text-red-400 hover:text-red-300 transition text-left cursor-pointer border border-red-900/40">
                    <i data-lucide="log-out" class="w-4 h-4"></i>
                    <span>ከሲስተም ውጣ (Logout)</span>
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

            <!-- Reload / Force Refresh Cache -->
            <button type="button" onclick="forcePurgePwaCache()" title="ሲስተሙን አድስ (Reload & Update)" class="p-1.5 sm:p-2 text-slate-400 hover:text-cyan-400 hover:bg-slate-800/60 rounded-xl transition-colors cursor-pointer flex items-center gap-1">
                <i data-lucide="rotate-cw" class="w-4 h-4"></i>
            </button>

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

    <!-- UNIVERSAL OFFLINE MODE PERSISTENT BANNER -->
    <div id="universal-offline-banner" class="hidden bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 text-white px-3 sm:px-6 py-2 text-xs font-bold flex items-center justify-between shadow-md transition-all z-30 shrink-0">
        <div class="flex items-center gap-2">
            <i data-lucide="wifi-off" class="w-4 h-4 text-amber-200 animate-pulse shrink-0"></i>
            <span id="universal-offline-banner-text">📶 Offline Mode — የተቀመጡ መረጃዎች እየታዩ ነው። ያለ ኢንተርኔት መስራት ይችላሉ።</span>
        </div>
        <span class="text-[10px] bg-black/30 border border-white/20 px-2.5 py-0.5 rounded-full font-extrabold uppercase tracking-wider shrink-0">ያለ ኢንተርኔት ይሰራል</span>
    </div>

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
            todayEth() {
                return this.toEth(new Date());
            },
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
        return ' ጥዋት (2:00–6:00 ጠዋቱ ETH • 8am–12pm GC)';
            }
            if (s.includes('afternoon') || s.includes('ከሰዓት')) {
        return ' ከሰዓት (7:00–11:00 ከሰዓቱ ETH • 1pm–5pm GC)';
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

      elMain.textContent = ' ' + ethStr;
      if (elSub) elSub.textContent = ' GC: ' + gcStr;
      if (elEthTime) elEthTime.textContent = ' ' + ethTime;
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
        let activeCleaningTeamId = localStorage.getItem('meash_active_team_id') || 1;
        let activeCleaningTeamName = localStorage.getItem('meash_active_team_name') || 'Team Alpha (Upholstery & Carpet)';

        function togglePasswordVisibility(id) {
            const input = document.getElementById(id);
            if (input) {
                input.type = input.type === 'password' ? 'text' : 'password';
            }
        }

        function showLoginScreen(msg = '') {
            const loginScreen = document.getElementById('app-login-screen');
            const appWrapper = document.getElementById('app-authenticated-wrapper');
            if (loginScreen) loginScreen.classList.remove('hidden');
            if (appWrapper) appWrapper.classList.add('hidden');

            const errBanner = document.getElementById('login-error-banner');
            const errText = document.getElementById('login-error-text');
            if (msg && errBanner && errText) {
                errText.innerText = msg;
                errBanner.classList.remove('hidden');
            } else if (errBanner) {
                errBanner.classList.add('hidden');
            }
            if (window.lucide) lucide.createIcons();
        }

        function checkAuthStatus() {
            const token = localStorage.getItem('meash_token');
            const storedUser = localStorage.getItem('meash_user');
            const loginScreen = document.getElementById('app-login-screen');
            const appWrapper = document.getElementById('app-authenticated-wrapper');
            
            // User is only authenticated if token exists and is non-empty
            if (token && token.trim().length > 0 && storedUser) {
                if (loginScreen) loginScreen.classList.add('hidden');
                if (appWrapper) appWrapper.classList.remove('hidden');
                return true;
            } else {
                if (loginScreen) loginScreen.classList.remove('hidden');
                if (appWrapper) appWrapper.classList.add('hidden');
                return false;
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

                let data = null;
                try {
                    data = await res.json();
                } catch(e) {
                    data = null;
                }

                if (res.ok && data && data.token) {
                    localStorage.setItem('meash_token', data.token);
                    localStorage.setItem('meash_user', JSON.stringify(data.user));
                    currentUser = Object.assign(currentUser, data.user);
                    checkAuthStatus();
                    initApp();
                } else if (res.status === 429) {
                    if (errText) errText.innerText = 'በርካታ ያልተሳኩ ሙከራዎች ተደርገዋል፤ እባክዎ ጥቂት ደቂቃ ቆይተው እንደገና ይሞክሩ (Too many attempts)::';
                    if (errBanner) errBanner.classList.remove('hidden');
                } else if (res.status >= 500) {
                    if (errText) errText.innerText = 'የውስጥ ሲስተም ችግር አጋጥሟል፤ እባክዎ ጥቂት ሰከንዶች ቆይተው እንደገና ይሞክሩ::';
                    if (errBanner) errBanner.classList.remove('hidden');
                } else {
                    let msg = 'የተሳሳተ ኢሜይል/ስልክ ወይም የይለፍ ቃል አስገብተዋል::';
                    if (data && data.errors && typeof data.errors === 'object') {
                        const rawErr = Object.values(data.errors).flat().join(' ');
                        msg = rawErr.includes('credentials') ? 'የተሳሳተ የይለፍ ቃል ወይም ተጠቃሚ አልተገኘም (Credentials do not match)::' : rawErr;
                    } else if (data && data.message) {
                        msg = data.message.includes('credentials') ? 'የተሳሳተ የይለፍ ቃል ወይም ተጠቃሚ አልተገኘም (Credentials do not match)::' : data.message;
                    }
                    if (errText) errText.innerText = msg;
                    if (errBanner) errBanner.classList.remove('hidden');
                }
            } catch (err) {
                if (errText) errText.innerText = 'የኔትወርክ ወይም የሰርቨር ችግር አጋጥሟል:: ሰርቨሩ ከእንቅልፉ በመነሳት ላይ ሊሆን ስለሚችል እባክዎ ጥቂት ሰከንድ ጠብቀው እንደገና ይሞክሩ::';
                if (errBanner) errBanner.classList.remove('hidden');
            } finally {
                if (btn) btn.disabled = false;
                if (btnText) btnText.innerText = 'ወደ ሲስተም ግባ (Login)';
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

        // Channel and Payment normalization helpers
        function getChannelBadge(source) {
            const src = (source === 'walkin') ? 'walk_in' : (source || 'phone');
            const map = {
                website: { label: 'ድረ-ገጽ', en: 'Website', icon: 'globe', cls: 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40' },
                telegram_miniapp: { label: 'ሚኒአፕ', en: 'MiniApp', icon: 'smartphone', cls: 'bg-sky-500/20 text-sky-300 border-sky-500/40' },
                telegram_bot: { label: 'ቦት', en: 'Bot', icon: 'bot', cls: 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40' },
                telegram_inline: { label: 'ቴሌግራም', en: 'Telegram', icon: 'send', cls: 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40' },
                phone: { label: 'ስልክ', en: 'Phone', icon: 'phone', cls: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' },
                walk_in: { label: 'በአካል', en: 'Walk-in', icon: 'footprints', cls: 'bg-purple-500/20 text-purple-300 border-purple-500/40' },
                internal: { label: 'ውስጣዊ', en: 'Internal', icon: 'building-2', cls: 'bg-slate-700/80 text-slate-300 border-slate-600' },
                corporate_contract: { label: 'ኮርፖሬት', en: 'Corporate', icon: 'briefcase', cls: 'bg-amber-500/20 text-amber-300 border-amber-500/40' }
            };
            const c = map[src] || { label: src, en: src, icon: 'tag', cls: 'bg-slate-800 text-slate-400 border-slate-700' };
            return `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border ${c.cls}" title="${c.en}">
                <i data-lucide="${c.icon}" class="w-3 h-3"></i>
                <span>${c.label}</span>
            </span>`;
        }
        window.getChannelBadge = getChannelBadge;

        function getPaymentBadge(status, order = null) {
            const s = String(status || 'unpaid').toLowerCase();
            if (s === 'paid') {
                return `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">የተከፈለ (Paid)</span>`;
            }
            if (s === 'pending_approval') {
                const method = order?.pending_payment_method || order?.payment_method || 'cash';
                const ref = order?.pending_payment_reference || order?.payment_reference || '';
                const isCash = method === 'cash';
                const methodLabel = isCash ? 'Cash' : (method === 'telebirr' ? 'Telebirr' : (method === 'cbe' ? 'CBE' : (getPaymentMethodName(method) || 'Transfer')));
                const refLabel = (!isCash && ref) ? ` [${escapeHtml(ref)}]` : '';
                return `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/25 text-amber-300 border border-amber-500/50 inline-flex items-center gap-1 shadow-sm" title="ማጽደቅ የሚጠብቅ ክፍያ">⏳ Pending Approval - ${methodLabel}${refLabel}</span>`;
            }
            if (s === 'partially_paid') {
                return `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-blue-500/20 text-blue-400 border border-blue-500/30">በከፊል (Partial)</span>`;
            }
            return `<span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-700/60 text-slate-300 border border-slate-600">ያልተከፈለ (Unpaid)</span>`;
        }
        window.getPaymentBadge = getPaymentBadge;

        function getPaymentMethodName(method) {
            const m = (method === 'cbe_birr') ? 'cbe' : method;
            const map = {
                cash: 'ጥሬ ገንዘብ (Cash)',
                telebirr: 'ቴሌብር (Telebirr)',
                cbe: 'የኢትዮጵያ ንግድ ባንክ (CBE)',
                bank_transfer: 'የባንክ ዝውውር (Bank Transfer)',
                check: 'ቼክ (Check)'
            };
            return map[m] || (m ? String(m).toUpperCase() : '—');
        }
        window.getPaymentMethodName = getPaymentMethodName;

        async function logoutUser() {
            try {
                await apiFetch('/api/auth/logout', { method: 'POST' });
            } catch (e) {}
            if (window.meashOffline && typeof window.meashOffline.clearUserData === 'function') {
                try { await window.meashOffline.clearUserData(); } catch (err) {}
            }
            localStorage.removeItem('meash_token');
            localStorage.removeItem('meash_user');
            checkAuthStatus();
        }

        function showLoginScreen(msg = '') {
            if (window.meashOffline && typeof window.meashOffline.clearUserData === 'function') {
                try { window.meashOffline.clearUserData(); } catch (err) {}
            }
            localStorage.removeItem('meash_token');
            const loginScreen = document.getElementById('app-login-screen');
            const appWrapper = document.getElementById('app-authenticated-wrapper');
            if (loginScreen) loginScreen.classList.remove('hidden');
            if (appWrapper) appWrapper.classList.add('hidden');
            const errBanner = document.getElementById('login-error-banner');
            const errText = document.getElementById('login-error-text');
            if (errText && msg) errText.innerText = msg;
            if (errBanner && msg) errBanner.classList.remove('hidden');
        }

        async function getAuthToken() {
            return localStorage.getItem('meash_token') || null;
        }

        // ========================================================
        // UNIVERSAL OFFLINE ENGINE: Caching, Fallbacks & Preloading
        // ========================================================
        function createMockResponse(data, status = 200, url = '') {
            const textData = typeof data === 'string' ? data : JSON.stringify(data);
            return {
                ok: status >= 200 && status < 300,
                status: status,
                statusText: 'OK (Offline Cache)',
                headers: new Headers({
                    'content-type': 'application/json',
                    'x-meash-offline': 'true',
                    'x-meash-url': url
                }),
                json: async () => (typeof data === 'string' ? JSON.parse(data) : data),
                text: async () => textData,
                clone: function() { return createMockResponse(data, status, url); }
            };
        }

        function getSafeOfflineFallback(url) {
            const cleanUrl = (url || '').split('?')[0];
            if (cleanUrl.includes('/api/dashboard/owner')) {
                return { today: { eth_date: 'Offline View' }, month_to_date: {} };
            }
            if (cleanUrl.includes('/api/dashboard/reception')) {
                return { eth_date: 'Offline View', channel_counts: {}, today_unassigned: [], pending_approvals: [], active_cleanings: [] };
            }
            if (cleanUrl.includes('/api/customers')) {
                return { data: [], stage_counts: { all: 0, normal_active: 0, comment_waiting: 0, in_service: 0 } };
            }
            if (cleanUrl.includes('/api/orders')) {
                return { data: [], orders: [] };
            }
            if (cleanUrl.includes('/api/subscriptions')) {
                return { data: [], subscriptions: { data: [] }, stats: { total_active: 0, mrr: 0 } };
            }
            if (cleanUrl.includes('/api/appointments')) {
                return { data: [] };
            }
            if (cleanUrl.includes('/api/teams') || cleanUrl.includes('/api/employees')) {
                return { data: [] };
            }
            if (cleanUrl.includes('/api/sales/visits') || cleanUrl.includes('/api/sales/organizations')) {
                return { data: [] };
            }
            if (cleanUrl.includes('/api/finance/profit-report')) {
                return { revenue: 0, expenses: 0, net_profit: 0, profit_margin: 0, breakdown: [] };
            }
            if (cleanUrl.includes('/api/finance/expenses') || cleanUrl.includes('/api/expenses')) {
                return { data: [] };
            }
            if (cleanUrl.includes('/api/care/followups') || cleanUrl.includes('/api/care/complaints') || cleanUrl.includes('/api/care/feedbacks')) {
                return { data: [] };
            }
            if (cleanUrl.includes('/api/telegram-broadcast/recipients')) {
                return { users: [], total_count: 0, active_count: 0, with_phone_count: 0, with_telegram_count: 0, eligible_count: 0 };
            }
            if (cleanUrl.includes('/api/campaigns')) {
                return { data: [] };
            }
            if (cleanUrl.includes('/api/admin/website/settings')) {
                return { settings: {} };
            }
            if (cleanUrl.includes('/api/services') || cleanUrl.includes('/api/public/services')) {
                return [];
            }
            return { success: true, data: [] };
        }

        function setOfflineBanner(show, msg = '', isBackOnline = false) {
            const banner = document.getElementById('universal-offline-banner');
            const bannerText = document.getElementById('universal-offline-banner-text');
            if (!banner) return;
            if (show) {
                banner.classList.remove('hidden');
                if (isBackOnline) {
                    banner.className = 'bg-gradient-to-r from-emerald-600 to-teal-700 text-white px-3 sm:px-6 py-2 text-xs font-bold flex items-center justify-between shadow-md transition-all z-30 shrink-0';
                    if (bannerText) bannerText.innerText = msg || '🌐 Back Online — መረጃዎች Sync እየሆኑ ነው...';
                    setTimeout(() => {
                        banner.classList.add('hidden');
                    }, 4000);
                } else {
                    banner.className = 'bg-gradient-to-r from-amber-600 via-amber-700 to-amber-800 text-white px-3 sm:px-6 py-2 text-xs font-bold flex items-center justify-between shadow-md transition-all z-30 shrink-0';
                    if (bannerText) bannerText.innerText = msg || '📶 Offline Mode — የተቀመጡ መረጃዎች እየታዩ ነው። ያለ ኢንተርኔት መስራት ይችላሉ።';
                }
            } else {
                banner.classList.add('hidden');
            }
            if (window.lucide) lucide.createIcons();
        }

        function pruneOfflineCache() {
            try {
                const keys = [];
                for (let i = 0; i < localStorage.length; i++) {
                    const k = localStorage.key(i);
                    if (k && k.startsWith('meash_api_cache_')) keys.push(k);
                }
                if (keys.length > 80) {
                    for (let i = 0; i < 20; i++) {
                        localStorage.removeItem(keys[i]);
                    }
                }
            } catch (_) {}
        }

        async function preloadAllAdminOfflineData() {
            if (!navigator.onLine) return;
            const endpoints = [
                '/api/dashboard/owner',
                '/api/dashboard/reception',
                '/api/orders?per_page=50',
                '/api/customers?per_page=50',
                '/api/customers?lifecycle_stage=normal_active&per_page=50',
                '/api/customers?lifecycle_stage=comment_waiting&per_page=50',
                '/api/customers?lifecycle_stage=in_service&per_page=50',
                '/api/subscriptions',
                '/api/subscriptions?per_page=50',
                '/api/appointments?',
                '/api/appointments?view=all',
                '/api/teams',
                '/api/employees',
                '/api/sales/visits?per_page=100',
                '/api/sales/organizations?per_page=100',
                '/api/finance/profit-report?period=all_time',
                '/api/finance/expenses?per_page=50',
                '/api/care/followups',
                '/api/care/complaints',
                '/api/care/feedbacks',
                '/api/telegram-broadcast/recipients',
                '/api/campaigns',
                '/api/admin/website/settings',
                '/api/services/all',
                '/api/admin/website/feedbacks',
                '/api/services',
                '/api/teams/my-jobs'
            ];
            console.log('🔄 Preloading all offline admin tabs in background...');
            for (const ep of endpoints) {
                try {
                    await apiFetch(ep);
                } catch (_) {}
            }
            console.log('✅ All offline admin tabs preloaded successfully!');
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

            const method = (options.method || 'GET').toUpperCase();
            const isGet = (method === 'GET');
            const cacheKey = 'meash_api_cache_' + url;
            const isOffline = !navigator.onLine || (window.meashOffline && !window.meashOffline.isOnline);

            // 1. FAST OFFLINE PATH FOR GET REQUESTS (0ms response)
            if (isGet && isOffline) {
                setOfflineBanner(true);
                const cached = localStorage.getItem(cacheKey);
                if (cached) {
                    try {
                        const parsed = JSON.parse(cached);
                        console.info('⚡ Served from offline local cache (0ms):', url);
                        return createMockResponse(parsed, 200, url);
                    } catch (_) {}
                }
                console.warn('⚠️ No local cache for:', url, '- returning safe offline fallback');
                const fallback = getSafeOfflineFallback(url);
                return createMockResponse(fallback, 200, url);
            }

            // 2. ONLINE OR NON-GET REQUESTS
            try {
                let res = await fetch(url, { ...options, headers });

                // CRITICAL ZERO-LOGOUT RULE: Never break script on 401
                if (res.status === 401) {
                    console.warn('API 401 Unauthorized for ' + url + ' - session expired');
                    localStorage.removeItem('meash_token');
                    localStorage.removeItem('meash_user');
                    currentUser = { role: 'cleaner', name: '', id: null, phone: '' };
                    showLoginScreen('የሴሽን ጊዜዎ አልቋል፤ እባክዎ እንደገና ይግቡ (Session expired. Please login again.)');
                    return res;
                }

                // Cache successful GET responses in background
                if (isGet && res.ok) {
                    res.clone().json().then(data => {
                        try {
                            localStorage.setItem(cacheKey, JSON.stringify(data));
                        } catch (quotaErr) {
                            pruneOfflineCache();
                            try { localStorage.setItem(cacheKey, JSON.stringify(data)); } catch (_) {}
                        }
                    }).catch(() => {});
                }

                return res;
            } catch (netErr) {
                console.warn('API fetch network error for ' + url + ':', netErr.message);
                if (isGet) {
                    setOfflineBanner(true);
                    const cached = localStorage.getItem(cacheKey);
                    if (cached) {
                        try {
                            const parsed = JSON.parse(cached);
                            console.info('⚡ Network failed, falling back to offline cache:', url);
                            return createMockResponse(parsed, 200, url);
                        } catch (_) {}
                    }
                    console.warn('⚠️ Network failed and no cache for:', url, '- returning safe offline fallback');
                    const fallback = getSafeOfflineFallback(url);
                    return createMockResponse(fallback, 200, url);
                }

                // If non-GET failed offline, rethrow
                throw netErr;
            }
        }

        async function loadJson(url, options = {}) {
            const res = await apiFetch(url, options);
            if (!res.ok) {
                let errMsg = `Request failed (${res.status})`;
                try {
                    const data = await res.json();
                    errMsg = data.message || data.error || errMsg;
                } catch (_) {
                    try {
                        const txt = await res.text();
                        if (txt) errMsg = txt;
                    } catch (_) {}
                }
                const err = new Error(errMsg);
                err.status = res.status;
                throw err;
            }
            return await res.json();
        }

        function renderErrorCard(container, message, retryFn) {
            if (!container) return;
            const isOffline = !navigator.onLine || (window.meashOffline && !window.meashOffline.isOnline);
            container.innerHTML = `
                <div class="p-8 text-center bg-slate-900/80 border ${isOffline ? 'border-amber-500/30' : 'border-red-500/30'} rounded-3xl max-w-md mx-auto my-12 shadow-2xl space-y-4">
                    <div class="w-14 h-14 rounded-2xl ${isOffline ? 'bg-amber-500/20 text-amber-400' : 'bg-red-500/20 text-red-400'} flex items-center justify-center mx-auto shadow-inner">
                        <i data-lucide="${isOffline ? 'wifi-off' : 'alert-circle'}" class="w-7 h-7"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white mb-1">${isOffline ? '📶 Offline' : 'መረጃውን መጫን አልተቻለም (Failed to Load)'}</h3>
                        <p class="text-xs ${isOffline ? 'text-amber-300' : 'text-red-300'} font-medium">${isOffline ? 'ይህ መረጃ እስካሁን አልተቀመጠም። ኢንተርኔት ሲያገኙ Sync ይሆናል።' : (message || 'የኔትወርክ ወይም የሰርቨር ችግር አጋጥሟል')}</p>
                    </div>
                    ${retryFn ? `
                    <button onclick="${retryFn}" class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-400 hover:to-emerald-500 text-white text-xs font-bold rounded-xl shadow-lg flex items-center justify-center gap-2 mx-auto transition">
                        <i data-lucide="refresh-cw" class="w-4 h-4"></i> እንደገና ሞክር (Retry)
                    </button>
                    ` : ''}
                </div>
            `;
            if (window.lucide) lucide.createIcons();
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
                { id: 'website_cms', label_en: 'Website & CMS', label_am: 'የድረ-ገጽ ማስተዳደሪያ', icon: 'globe' },
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
                { id: 'website_cms', label_en: 'Website CMS', label_am: 'የድረ-ገጽ ማስተዳደሪያ', icon: 'globe' },
                { id: 'settings', label_en: 'System Settings', label_am: 'የሲስተም ቅንብሮች (SMS)', icon: 'settings' },
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
            ],
            finance: [
                { id: 'finance', label_en: 'Finance & Expenses', label_am: 'ፋይናንስ እና ወጪዎች', icon: 'wallet' },
                { id: 'orders', label_en: 'Orders & Payments', label_am: 'ትዕዛዞች እና ክፍያዎች', icon: 'clipboard-list' },
                { id: 'subscriptions', label_en: 'Subscriptions', label_am: 'የደንበኝነት እቅዶች', icon: 'repeat' },
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
            setTimeout(() => {
                preloadAllAdminOfflineData().catch(() => {});
            }, 1200);
        }

        function setupOfflineListener() {
            let wasOffline = false;

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

                if (!status.online) {
                    wasOffline = true;
                    setOfflineBanner(true, '📶 Offline Mode — የተቀመጡ መረጃዎች እየታዩ ነው። ያለ ኢንተርኔት መስራት ይችላሉ።');
                } else if (wasOffline) {
                    wasOffline = false;
                    setOfflineBanner(true, '🌐 Back Online — መረጃዎች Sync እየሆኑ ነው...', true);
                    loadActiveTab();
                    preloadAllAdminOfflineData().catch(() => {});
                }
            });

            window.addEventListener('online', () => {
                setOfflineBanner(true, '🌐 Back Online — መረጃዎች Sync እየሆኑ ነው...', true);
                loadActiveTab();
                preloadAllAdminOfflineData().catch(() => {});
            });

            window.addEventListener('offline', () => {
                setOfflineBanner(true, '📶 Offline Mode — የተቀመጡ መረጃዎች እየታዩ ነው። ያለ ኢንተርኔት መስራት ይችላሉ።');
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

        async function switchCleaningTeam(teamId, teamName) {
            activeCleaningTeamId = teamId;
            activeCleaningTeamName = teamName;
            localStorage.setItem('meash_active_team_id', teamId);
            localStorage.setItem('meash_active_team_name', teamName);

            currentUser.role = 'cleaner';
            currentUser.name = teamName;
            activeTab = 'cleaner_jobs';

            // Update header badge text with selected team name
            const badge = document.getElementById('header-role-badge');
            if (badge) {
                const shortName = teamName.split('(')[0].trim();
        badge.innerText = ` ${shortName}`;
            }

            const nameEl = document.getElementById('user-display-name');
            if (nameEl) nameEl.innerText = currentUser.name;
            const roleEl = document.getElementById('user-display-role');
            if (roleEl) roleEl.innerText = 'CLEANER GROUP';
            const avatarEl = document.getElementById('user-avatar-text');
      if (avatarEl) avatarEl.innerText = '';

            renderNavigation();
            await getAuthToken();
            loadActiveTab();
        }

        async function switchRole(role) {
            currentUser.role = role;
            if (role === 'owner') {
                currentUser.name = 'አምሃ (ዋና ስራ አስኪያጅ)';
                activeTab = 'dashboard';
            } else if (role === 'reception') {
                currentUser.name = 'ቤተልሔም ታደሰ (ሪሴፕሽን)';
                activeTab = 'reception_desk';
            } else if (role === 'cleaner') {
                currentUser.name = activeCleaningTeamName || 'Team Alpha (Upholstery & Carpet)';
                activeTab = 'cleaner_jobs';
            } else if (role === 'sales') {
                currentUser.name = 'ዳንኤል ግርማ (የውጭ ሽያጭ)';
                activeTab = 'sales_pipeline';
            } else if (role === 'finance') {
                currentUser.name = 'ዮናስ ተስፋዬ (ፋይናንስ & ሂሳብ)';
                activeTab = 'finance';
            }

            // Update header badge text
            const badge = document.getElementById('header-role-badge');
            if (badge) {
        const cleanerLabel = activeCleaningTeamName ? ` ${activeCleaningTeamName.split('(')[0].trim()}` : ' የፅዳት ቡድን';
                const roleLabels = {
          owner: ' ዋና ስራ አስኪያጅ',
          reception: ' ሪሴፕሽን & ሽያጭ',
                    cleaner: cleanerLabel,
          sales: ' የውጭ ሽያጭ',
          finance: ' ፋይናንስ & ሂሳብ'
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
            } else if (activeTab === 'finance' || activeTab.startsWith('finance_')) {
                renderFinanceModule(viewport);
            } else if (activeTab === 'care') {
                renderCustomerCareModule(viewport);
            } else if (activeTab === 'campaigns') {
                renderCampaignsModule(viewport);
            } else if (activeTab === 'settings') {
                renderSettingsModule(viewport);
            } else if (activeTab === 'website_cms') {
                renderWebsiteCmsModule(viewport);
            } else {
                renderOwnerDashboard(viewport);
            }
        }

        // ==========================================
        // 1. OWNER DASHBOARD (iPhone & PC BI View)
        // ==========================================
        async function renderOwnerDashboard(container) {
            try {
                let data = null;
                if (navigator.onLine) {
                    try {
                        const res = await apiFetch('/api/dashboard/owner');
                        if (res.status === 401) return;
                        if (res.ok) {
                            data = await res.json();
                            try { localStorage.setItem('meash_cached_owner_dashboard', JSON.stringify(data)); } catch (_) {}
                        }
                    } catch (netErr) {
                        console.warn('Owner dashboard network fetch failed, using offline cache:', netErr);
                    }
                }
                if (!data) {
                    const cached = localStorage.getItem('meash_cached_owner_dashboard');
                    if (cached) {
                        try { data = JSON.parse(cached); } catch (_) {}
                    }
                }
                if (!data) {
                    data = { today: { eth_date: 'Offline View' }, month_to_date: {} };
                }
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
                  <span class="text-lg"></span>
                                </div>

                                <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-medium block">ማረጋገጫ የሚጠብቁ</span>
                                        <span class="text-xl font-black text-amber-400 mt-0.5 block">${today.pending_bookings || 0}</span>
                                    </div>
                                    <span class="text-lg"></span>
                                </div>

                                <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-medium block">ቡድን ያልተመደቡ</span>
                                        <span class="text-xl font-black ${today.unassigned_jobs > 0 ? 'text-red-400' : 'text-slate-400'} mt-0.5 block">${today.unassigned_jobs || 0}</span>
                                    </div>
                  <span class="text-lg"></span>
                                </div>

                                <div class="p-3 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] text-slate-400 font-medium block">የዛሬ የተጣራ</span>
                                        <span class="text-base sm:text-lg font-black text-cyan-300 mt-0.5 block">${parseFloat(today.net_result || 0).toLocaleString()} <span class="text-[9px] font-mono">ETB</span></span>
                                    </div>
                  <span class="text-lg"></span>
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

                        <!-- Channel Breakdown & Source KPIs -->
                        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-6 space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-800">
                                <div>
                                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                                        <i data-lucide="layers" class="w-4 h-4 text-cyan-400"></i>
                                        <span>የትዕዛዝ መነሻ መንገዶች አፈፃፀም (Order Sources)</span>
                                    </h3>
                                    <p class="text-xs text-slate-400">ከድረ-ገጽ፣ ቴሌግራም ሚኒአፕ፣ ቦት፣ ስልክ ጥሪ እና ሌሎች የመጡ ትዕዛዞች እና የገቢ ክፍፍል።</p>
                                </div>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-cyan-950/80 text-cyan-300 border border-cyan-500/30">
                                    የቀጥታ ስታቲስቲክስ
                                </span>
                            </div>

                            <!-- Channel Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                                ${Object.values(data.channel_breakdown || {}).map(ch => `
                                    <div class="p-3 rounded-2xl bg-slate-800/80 border border-slate-700/80 hover:border-cyan-500/50 transition">
                                        <div class="flex items-center justify-between mb-1.5">
                                            <span class="text-xs font-bold text-slate-300">${ch.label_am}</span>
                                            <i data-lucide="${ch.icon || 'tag'}" class="w-3.5 h-3.5 text-cyan-400"></i>
                                        </div>
                                        <span class="text-lg font-black text-white block">${ch.orders_count} <span class="text-[10px] font-normal text-slate-400">ትዕዛዝ</span></span>
                                        <span class="text-[11px] font-semibold text-cyan-400 block mt-0.5">${parseFloat(ch.total_revenue || 0).toLocaleString()} ብር</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>

                        <!-- 7-Day Financial Trend Chart (Real Database Numbers) -->
                        <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-6 space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                <div>
                                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                                        <i data-lucide="trending-up" class="w-4 h-4 text-emerald-400"></i>
                                        <span>የባለፉት 7 ቀናት የቀን-በቀን ገቢ እና ወጪ (7-Day Financial Trend)</span>
                                    </h3>
                                    <p class="text-xs text-slate-400">በእያንዳንዱ ቀን የተሰበሰበ ገቢ እና የወጣ ወጪ ንጽጽር (ከተረጋገጡ ክፍያዎች እና ወጪዎች)።</p>
                                </div>
                                <div class="flex items-center gap-3 text-xs">
                                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-emerald-500"></span><span class="text-slate-300">ገቢ</span></span>
                                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-red-500"></span><span class="text-slate-300">ወጪ</span></span>
                                </div>
                            </div>
                            <div class="grid grid-cols-7 gap-2 pt-2 items-end min-h-[140px]">
                                ${(data.daily_trend || []).map(day => {
                                    const maxVal = Math.max(...(data.daily_trend || []).map(d => Math.max(d.income || 0, d.expense || 0)), 1000);
                                    const incPct = Math.min(100, Math.round(((day.income || 0) / maxVal) * 100));
                                    const expPct = Math.min(100, Math.round(((day.expense || 0) / maxVal) * 100));
                                    return `
                                        <div class="flex flex-col items-center gap-1.5 h-full justify-end">
                                            <div class="flex items-end gap-1 w-full justify-center h-28 bg-slate-950/40 rounded-xl p-1">
                                                <div class="w-1/2 bg-emerald-500 rounded-t-md transition-all hover:bg-emerald-400" style="height: ${Math.max(4, incPct)}%;" title="ገቢ፡ ${parseFloat(day.income || 0).toLocaleString()} ETB"></div>
                                                <div class="w-1/2 bg-red-500 rounded-t-md transition-all hover:bg-red-400" style="height: ${Math.max(4, expPct)}%;" title="ወጪ፡ ${parseFloat(day.expense || 0).toLocaleString()} ETB"></div>
                                            </div>
                                            <span class="text-[10px] font-bold text-slate-400 font-mono">${day.day_name || day.date.slice(5)}</span>
                                            <span class="text-[9px] font-bold ${day.net >= 0 ? 'text-emerald-400' : 'text-red-400'}">${day.net >= 0 ? '+' : ''}${Math.round(day.net / 1000)}k</span>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>

                        <!-- Customer Satisfaction & Team Performance Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <!-- Real Customer Satisfaction Card -->
                            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-6 space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                                        <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
                                        <span>የደንበኞች እርካታ እና ደረጃ (Customer Satisfaction)</span>
                                    </h3>
                                    <span class="text-xs text-slate-400 font-bold">${data.satisfaction?.total_feedbacks || 0} አስተያየቶች</span>
                                </div>
                                <div class="flex items-center gap-6">
                                    <div class="text-center p-4 bg-slate-800/80 rounded-2xl border border-slate-700">
                                        <span class="text-4xl font-black text-amber-400 block">${data.satisfaction?.avg_rating || 5.0}</span>
                                        <span class="text-xs text-slate-400">ከ 5.0 ኮከቦች</span>
                                        <div class="flex items-center justify-center gap-0.5 text-amber-400 mt-1">
                                            ${'★'.repeat(Math.round(data.satisfaction?.avg_rating || 5))}${'☆'.repeat(5 - Math.round(data.satisfaction?.avg_rating || 5))}
                                        </div>
                                    </div>
                                    <div class="flex-1 space-y-1.5">
                                        ${[5, 4, 3, 2, 1].map(stars => {
                                            const item = (data.satisfaction?.rating_distribution && data.satisfaction.rating_distribution[stars]) || { count: 0, percentage: 0 };
                                            return `
                                                <div class="flex items-center gap-2 text-xs">
                                                    <span class="w-8 text-[11px] text-slate-400 font-bold">${stars} ★</span>
                                                    <div class="flex-1 h-2 bg-slate-800 rounded-full overflow-hidden">
                                                        <div class="h-full bg-amber-400 rounded-full transition-all" style="width: ${item.percentage}%;"></div>
                                                    </div>
                                                    <span class="w-8 text-right text-[10px] text-slate-400">${item.count}</span>
                                                </div>
                                            `;
                                        }).join('')}
                                    </div>
                                </div>
                            </div>

                            <!-- Real Team Performance Card -->
                            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-6 space-y-4">
                                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                                        <i data-lucide="award" class="w-4 h-4 text-cyan-400"></i>
                                        <span>የቡድኖች ስራ ማጠናቀቂያ (Team Completion Rate)</span>
                                    </h3>
                                    <span class="text-xs text-emerald-400 font-bold">MRR: ${parseFloat(data.subscriptions?.mrr || 0).toLocaleString()} ETB</span>
                                </div>
                                <div class="space-y-3">
                                    ${(data.team_performance || []).length === 0 ? '<p class="text-xs text-slate-500 py-4 text-center">እስካሁን የተፈጠረ ንቁ ቡድን የለም።</p>' : (data.team_performance || []).map(tp => `
                                        <div class="p-3 bg-slate-800/80 rounded-2xl border border-slate-700/80 flex items-center justify-between gap-3">
                                            <div>
                                                <span class="text-xs font-bold text-white block">${tp.name}</span>
                                                <span class="text-[10px] text-slate-400">መሪ፡ ${tp.leader} • የተጠናቀቀ፡ <strong>${tp.completed}/${tp.total_assigned}</strong></span>
                                            </div>
                                            <div class="text-right">
                                                <span class="px-2.5 py-1 rounded-xl text-xs font-black ${tp.completion_rate >= 80 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'}">
                                                    ${tp.completion_rate}%
                                                </span>
                                            </div>
                                        </div>
                                    `).join('')}
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
                                                ${getChannelBadge(o.source)}
                                            </div>
                                            <p class="text-xs text-slate-400 mt-1">የተመደበለት ቡድን: <strong>${o.assigned_team?.team_name || 'ቡድን አልተመደበም'}</strong></p>
                                        </div>
                                        <div class="text-right flex flex-col items-end gap-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${o.order_status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : (o.order_status === 'assigned' ? 'bg-blue-500/20 text-blue-400' : 'bg-amber-500/20 text-amber-400')}">
                                                    ${o.order_status}
                                                </span>
                                                ${getPaymentBadge(o.payment_status, o)}
                                            </div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-xs font-black text-white">${parseFloat(o.total || 0).toLocaleString()} ETB</span>
                                                ${o.payment_status === 'pending_approval' ? `
                                                    <button onclick="openApprovePaymentModal(${o.id}, '${escapeHtml(o.order_number)}', ${parseFloat(o.total || 0)}, '${escapeHtml(o.pending_payment_method || o.payment_method || 'cash')}', '${escapeHtml(o.pending_payment_reference || o.payment_reference || '')}')" class="px-2 py-0.5 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-[10px] rounded-lg shadow-sm cursor-pointer" title="ክፍያ አጽድቅ">
                                                        አጽድቅ
                                                    </button>
                                                    <button onclick="openRejectPaymentModal(${o.id}, '${escapeHtml(o.order_number)}')" class="px-1.5 py-0.5 bg-red-950/80 hover:bg-red-900 text-red-300 font-bold text-[10px] rounded-lg border border-red-500/30 cursor-pointer" title="ክፍያ ውድቅ አድርግ">
                                                        ውድቅ
                                                    </button>
                                                ` : ''}
                                            </div>
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
                let data = null;
                if (navigator.onLine) {
                    try {
                        const res = await apiFetch('/api/dashboard/reception');
                        if (res.status === 401) return;
                        if (res.ok) {
                            data = await res.json();
                            try { localStorage.setItem('meash_cached_reception_dashboard', JSON.stringify(data)); } catch (_) {}
                        }
                    } catch (netErr) {
                        console.warn('Reception dashboard network fetch failed, using offline cache:', netErr);
                    }
                }
                if (!data) {
                    const cached = localStorage.getItem('meash_cached_reception_dashboard');
                    if (cached) {
                        try { data = JSON.parse(cached); } catch (_) {}
                    }
                }
                if (!data) {
                    data = { eth_date: 'Offline View', channel_counts: {}, today_unassigned: [], pending_approvals: [], active_cleanings: [] };
                }

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">${data.eth_date}</span>
                                <h2 class="text-2xl sm:text-3xl font-black text-white">የሪሴፕሽን ስራ መምሪያ ዴስክ (Reception Command Desk)</h2>
                                <p class="text-xs text-slate-400 mt-1">የደንበኛ ጥሪዎችን በቀጥታ መመዝገብ፣ ቀጠሮዎችን መያዝ፣ ቡድኖችን መመደብና ክትትል ማድረግ።</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="renderReceptionView(document.getElementById('app-viewport'))" class="px-3.5 py-3 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl border border-slate-700 flex items-center gap-1.5 transition cursor-pointer" title="ዳታውን አድስ">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-cyan-400"></i>
                                    <span class="hidden sm:inline">አድስ (Refresh)</span>
                                </button>
                                <button onclick="openCreateOrderModal()" class="px-5 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2 cursor-pointer">
                                    <i data-lucide="phone-incoming" class="w-4 h-4"></i>
                                    <span>ፈጣን ጥሪ መቀበያ / አዲስ ትዕዛዝ</span>
                                </button>
                            </div>
                        </div>

                        <!-- Channel Source Live Counters Bar -->
                        <div class="p-3.5 rounded-2xl bg-slate-900 border border-slate-800 flex flex-wrap items-center gap-2 text-xs">
                            <span class="text-slate-400 font-bold mr-1">አዳዲስ ትዕዛዞች በምንጭ:</span>
                            <span class="px-2.5 py-1 rounded-xl bg-indigo-950/70 text-indigo-300 border border-indigo-500/30 font-bold flex items-center gap-1">ድረ-ገጽ: ${data.channel_counts?.website || 0}</span>
                            <span class="px-2.5 py-1 rounded-xl bg-sky-950/70 text-sky-300 border border-sky-500/30 font-bold flex items-center gap-1">ሚኒአፕ: ${data.channel_counts?.telegram_miniapp || 0}</span>
                            <span class="px-2.5 py-1 rounded-xl bg-cyan-950/70 text-cyan-300 border border-cyan-500/30 font-bold flex items-center gap-1">ቦት: ${data.channel_counts?.telegram_bot || 0}</span>
                            <span class="px-2.5 py-1 rounded-xl bg-emerald-950/70 text-emerald-300 border border-emerald-500/30 font-bold flex items-center gap-1">ስልክ: ${data.channel_counts?.phone || 0}</span>
                            <span class="px-2.5 py-1 rounded-xl bg-purple-950/70 text-purple-300 border border-purple-500/30 font-bold flex items-center gap-1">በአካል: ${data.channel_counts?.walk_in || 0}</span>
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
                                    <span class="text-[10px] text-cyan-400 font-bold">በቅጽበት የሚታይ</span>
                                </div>
                                <div class="space-y-3">
                                    ${(data.new_bookings || []).length === 0 ? '<p class="text-xs text-slate-500 py-4 text-center">ማረጋገጫ የሚጠብቅ አዲስ ትዕዛዝ የለም።</p>' : ''}
                                    ${(data.new_bookings || []).map(b => `
                                        <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xs font-bold text-cyan-400 font-mono">${b.order_number}</span>
                                                    <span class="text-xs font-bold text-white">${b.customer?.full_name}</span>
                                                    ${getChannelBadge(b.source)}
                                                    ${getPaymentBadge(b.payment_status, b)}
                                                </div>
                                                <p class="text-[11px] text-slate-400 mt-0.5">${b.customer?.phone} | ${b.subcity || 'Addis Ababa'}</p>
                                                <p class="text-[10px] text-slate-500">${b.items?.map(i => i.item_name).join(', ')}</p>
                                                <div class="flex items-center gap-1.5 mt-2">
                                                    <span class="text-[10px] text-slate-400 font-bold">ቡድን ፈጥነህ መድብ፡</span>
                                                    <select onchange="quickAssignTeam(${b.id}, this.value)" class="bg-slate-900 border border-slate-700 text-cyan-300 font-bold text-[10px] rounded-lg px-2 py-1 focus:outline-none cursor-pointer">
                                                        <option value="">-- ቡድን ምረጥ --</option>
                                                        ${(data.active_teams || []).map(t => `<option value="${t.id}" ${b.assigned_team_id == t.id ? 'selected' : ''}>${t.team_name}</option>`).join('')}
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1.5 self-end sm:self-auto">
                                                ${b.payment_status === 'pending_approval' ? `
                                                    <button onclick="openApprovePaymentModal(${b.id}, '${escapeHtml(b.order_number)}', ${parseFloat(b.total || 0)}, '${escapeHtml(b.pending_payment_method || b.payment_method || 'cash')}', '${escapeHtml(b.pending_payment_reference || b.payment_reference || '')}')" class="px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-extrabold shadow cursor-pointer" title="ክፍያ አጽድቅ">አጽድቅ</button>
                                                    <button onclick="openRejectPaymentModal(${b.id}, '${escapeHtml(b.order_number)}')" class="px-2 py-1.5 rounded-lg bg-red-950/80 hover:bg-red-900 text-red-300 border border-red-500/40 text-xs font-bold cursor-pointer" title="ክፍያ ውድቅ አድርግ">ውድቅ</button>
                                                ` : ''}
                                                <button onclick="confirmAndAssignOrder(${b.id})" class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold transition">ሰራተኛ መድብ</button>
                                                <button onclick="openOrderDetailsModal(${b.id})" class="px-2.5 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-bold">ዝርዝር</button>
                                                <button onclick="openPostponeOrderModal(${b.id}, '${b.order_number}', '${b.customer?.full_name}')" class="px-2 py-1.5 rounded-lg bg-amber-950/70 hover:bg-amber-900 text-amber-300 border border-amber-500/40 text-xs font-bold" title="ቀጠሮ አስተላልፍ">አስተላልፍ</button>
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
                                        <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xs font-bold text-white">${s.customer?.full_name}</span>
                                                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-700 text-slate-300 font-bold">${formatTimeSlot(s.appointment_time_slot)}</span>
                                                    ${getChannelBadge(s.source)}
                                                    ${getPaymentBadge(s.payment_status, s)}
                                                </div>
                                                <p class="text-[11px] text-slate-400 mt-0.5">የተመደበ ቡድን: <strong>${s.assigned_team?.team_name || 'ቡድን አልተመደበም'}</strong></p>
                                                <div class="flex items-center gap-1.5 mt-1.5">
                                                    <span class="text-[10px] text-slate-400 font-bold">ቡድን ቀይር፡</span>
                                                    <select onchange="quickAssignTeam(${s.id}, this.value)" class="bg-slate-900 border border-slate-700 text-cyan-300 font-bold text-[10px] rounded-lg px-2 py-1 focus:outline-none cursor-pointer">
                                                        <option value="">-- ቡድን ምረጥ --</option>
                                                        ${(data.active_teams || []).map(t => `<option value="${t.id}" ${s.assigned_team_id == t.id ? 'selected' : ''}>${t.team_name}</option>`).join('')}
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1.5 self-end sm:self-auto">
                                                ${s.payment_status === 'pending_approval' ? `
                                                    <button onclick="openApprovePaymentModal(${s.id}, '${escapeHtml(s.order_number)}', ${parseFloat(s.total || 0)}, '${escapeHtml(s.pending_payment_method || s.payment_method || 'cash')}', '${escapeHtml(s.pending_payment_reference || s.payment_reference || '')}')" class="px-2 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-[10px] font-extrabold shadow cursor-pointer" title="ክፍያ አጽድቅ">አጽድቅ</button>
                                                    <button onclick="openRejectPaymentModal(${s.id}, '${escapeHtml(s.order_number)}')" class="px-1.5 py-1 rounded-lg bg-red-950/80 hover:bg-red-900 text-red-300 border border-red-500/40 text-[10px] font-bold cursor-pointer" title="ክፍያ ውድቅ አድርግ">ውድቅ</button>
                                                ` : ''}
                                                <span class="text-xs font-bold ${s.order_status === 'completed' ? 'text-emerald-400' : 'text-cyan-400'} uppercase">${s.order_status}</span>
                                                ${s.order_status === 'completed' ? `
                                                    <button onclick="triggerSendReviewRequest(${s.id}, '${escapeHtml(s.order_number)}')" class="px-2 py-1 rounded-lg bg-amber-950/60 hover:bg-amber-900/60 text-amber-300 border border-amber-500/30 text-[10px] font-bold cursor-pointer" title="የ 5-ኮከብ ደረጃ ጥየቃ ላክ">⭐ ጥየቃ ላክ</button>
                                                ` : ''}
                                                ${s.order_status !== 'completed' ? `
                                                    <button onclick="openPostponeOrderModal(${s.id}, '${s.order_number}', '${s.customer?.full_name}')" class="px-2 py-1 rounded-lg bg-amber-950/60 hover:bg-amber-900/60 text-amber-300 border border-amber-500/30 text-[10px] font-bold">አስተላልፍ</button>
                                                ` : ''}
                                                <button onclick="openOrderDetailsModal(${s.id})" class="px-2 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 text-slate-200 text-[10px] font-bold">ዝርዝር</button>
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
                        <p class="text-[11px] text-slate-400">Order: ${f.order?.order_number} | ${f.customer?.phone}</p>
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

                        <!-- Section: Website Quick Inquiries & Messages -->
                        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-9 h-9 rounded-xl bg-indigo-500/20 text-indigo-400 flex items-center justify-center">
                                        <i data-lucide="message-square" class="w-5 h-5"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-sm text-white">ከድረ-ገጽ የተላኩ ፈጣን ጥያቄዎች እና መልእክቶች (${(data.website_messages || []).length})</h3>
                                        <p class="text-[11px] text-slate-400">ደንበኞች በድረ-ገጹ ላይ የሞሏቸው ፈጣን ጥያቄዎች እዚህ በቅጽበት ይገባሉ::</p>
                                    </div>
                                </div>
                                <span class="text-[10px] bg-indigo-950 text-indigo-300 border border-indigo-500/30 px-3 py-1 rounded-full font-bold">የቀጥታ ጥያቄዎች</span>
                            </div>
                            <div class="space-y-3">
                                ${(data.website_messages || []).length === 0 ? '<p class="text-xs text-slate-500 py-6 text-center">እስካሁን ምንም አዲስ የድረ-ገጽ መልእክት አልተላከም::</p>' : ''}
                                ${(data.website_messages || []).map(m => {
                                    const mData = m.data || {};
                                    const cName = mData.name || m.title || 'ውድ ደንበኛ';
                                    const cPhone = mData.phone || '';
                                    const cMsg = mData.message || m.body || '';
                                    const timeStr = m.created_at ? new Date(m.created_at).toLocaleString() : '';
                                    return `
                                        <div class="p-4 rounded-2xl bg-slate-800/70 border border-slate-700 hover:border-slate-600 transition space-y-2.5">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs font-extrabold text-white">${escapeHtml(cName)}</span>
                                                    ${cPhone ? `<a href="tel:${escapeHtml(cPhone)}" class="text-xs font-bold text-cyan-400 font-mono hover:underline flex items-center gap-1"><i data-lucide="phone" class="w-3 h-3"></i> ${escapeHtml(cPhone)}</a>` : ''}
                                                </div>
                                                <span class="text-[10px] text-slate-400 font-mono">${timeStr}</span>
                                            </div>
                                            <p class="text-xs text-slate-200 bg-slate-900/80 p-3 rounded-xl border border-slate-800 leading-relaxed">${escapeHtml(cMsg)}</p>
                                            <div class="flex items-center gap-2 pt-1">
                                                ${cPhone ? `<a href="tel:${escapeHtml(cPhone)}" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold flex items-center gap-1.5 transition"><i data-lucide="phone-call" class="w-3.5 h-3.5"></i> ደውል</a>` : ''}
                                                <button onclick="openCreateOrderModal()" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white text-xs font-bold flex items-center gap-1.5 transition"><i data-lucide="plus-circle" class="w-3.5 h-3.5"></i> ወደ ትዕዛዝ ቀይር</button>
                                            </div>
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();

                // Live Sync: Background auto-refresh for Reception desk
                if (window._receptionRefreshTimer) clearInterval(window._receptionRefreshTimer);
                window._receptionRefreshTimer = setInterval(() => {
                    const modalOpen = document.getElementById('generic-modal-container')?.querySelector('.fixed');
                    if (activeTab === 'reception' && !modalOpen) {
                        renderReceptionView(document.getElementById('app-viewport'), true);
                    }
                }, 20000);
            } catch (err) {
                container.innerHTML = `<div class="p-6 text-center text-red-400">Failed to load Reception overview: ${err.message}</div>`;
            }
        }

        // ==========================================
        // 3. CLEANING TEAM HUB (Android Phone View)
        // ==========================================
        function showCleanerJobAddress(jobId) {
            const job = (window._currentCleanerJobs || []).find(j => Number(j.id) === Number(jobId));
            if (!job) {
                openCustomerAddressModal(jobId, 'ውድ ደንበኛ', '0970075509', 'አዲስ አበባ (Addis Ababa)', activeCleaningTeamName);
                return;
            }
            const custName = job.customer?.full_name || 'ውድ ደንበኛ';
            const custPhone = job.customer?.phone || job.customer?.alt_phone || '0970075509';
            let addr = job.address || job.customer?.address || '';
            if (job.customer?.subcity && !addr.includes(job.customer.subcity)) {
                addr = addr ? `${addr}, ${job.customer.subcity}` : job.customer.subcity;
            }
            if (job.customer?.landmark && !addr.includes(job.customer.landmark)) {
                addr = addr ? `${addr} (${job.customer.landmark})` : job.customer.landmark;
            }
            if (!addr || addr === 'undefined' || addr === 'null' || !addr.trim()) {
                addr = 'አዲስ አበባ (Addis Ababa)';
            }
            openCustomerAddressModal(job.id, custName, custPhone, addr, activeCleaningTeamName);
        }

        async function renderCleanerView(container) {
            try {
                let jobs = [];
                let currentTeam = null;
                let teamsList = [];
                let todayList = [];
                let upcomingList = [];
                let completedList = [];

                // Check offline cached jobs first if available
                let cached = [];
                try {
                    if (window.meashOffline && typeof window.meashOffline.getAllItems === 'function') {
                        cached = await window.meashOffline.getAllItems('my_jobs');
                    }
                } catch (e) {
                    console.warn('Offline cache read error:', e);
                }

                if (cached && cached.length > 0 && window.meashOffline && !window.meashOffline.isOnline) {
                    jobs = cached;
                    todayList = cached;
                } else {
                    const teamParam = activeCleaningTeamId ? `?team_id=${activeCleaningTeamId}` : '';
                    const res = await apiFetch('/api/teams/my-jobs' + teamParam);
                    if (res.status === 401) {
                        return; // Redirected to login screen cleanly
                    }
                    const data = await res.json();
                    todayList = data.today_jobs || [];
                    upcomingList = data.upcoming_jobs || [];
                    completedList = (data.completed_jobs && data.completed_jobs.length > 0)
                        ? data.completed_jobs
                        : (data.completed_today || []);

                    currentTeam = data.team;
                    teamsList = data.teams_list || [];

                    if (currentTeam && currentTeam.team_name) {
                        activeCleaningTeamName = currentTeam.team_name;
                        activeCleaningTeamId = currentTeam.id;
                    }

                    // Cache into IndexedDB for offline access
                    if (window.meashOffline && typeof window.meashOffline.cacheItems === 'function') {
                        try {
                            await window.meashOffline.cacheItems('my_jobs', todayList);
                        } catch (e) {
                            console.warn('Offline caching skipped:', e);
                        }
                    }
                }

                // Determine which dataset to display based on activeTab
                let tabTitle = 'የዛሬ የስራ ዝርዝሮች';
                let tabBadge = `${todayList.length} ንቁ ስራዎች`;
                let emptyTitle = 'ሁሉም ስራዎች ተጠናቀዋል!';
                let emptyMsg = 'ለዛሬ ለቡድንዎ የተመደበ ያልተጠናቀቀ ስራ የለም።';

                if (activeTab === 'cleaner_history') {
                    jobs = completedList;
                    tabTitle = 'የተጠናቀቁ ስራዎች (Completed Jobs)';
                    tabBadge = `${completedList.length} የተጠናቀቁ`;
                    emptyTitle = 'ምንም የተጠናቀቀ ስራ የለም';
                    emptyMsg = 'እስካሁን የተጠናቀቀ ስራ አልተመዘገበም (No completed jobs found).';
                } else if (activeTab === 'cleaner_upcoming') {
                    jobs = upcomingList;
                    tabTitle = 'ቀጣይ ቀጠሮዎች (Upcoming Appointments)';
                    tabBadge = `${upcomingList.length} ቀጣይ`;
                    emptyTitle = 'ምንም ቀጣይ ቀጠሮዎች የሉም';
                    emptyMsg = 'ምንም ቀጣይ ቀጠሮዎች አልተገኙም (No upcoming jobs).';
                } else {
                    // Default: cleaner_jobs
                    jobs = todayList;
                    tabTitle = 'የዛሬ የስራ ዝርዝሮች (Today\'s Jobs)';
                    tabBadge = `${todayList.length} ንቁ ስራዎች`;
                    emptyTitle = 'ሁሉም ስራዎች ተጠናቀዋል!';
                    emptyMsg = 'ለዛሬ ለቡድንዎ የተመደበ ያልተጠናቀቀ ስራ የለም።';
                }

                window._currentCleanerJobs = jobs;

                container.innerHTML = `
                    <div class="max-w-2xl mx-auto space-y-4 sm:space-y-6">
                        <!-- Top Header Banner -->
                        <div class="p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-cyan-900/70 to-blue-900/70 border border-cyan-500/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xl">
                            <div>
                                <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider block">የመስክ አጽጂ ቡድን መከታተያ</span>
                                <h2 class="text-xl font-black text-white">${tabTitle}</h2>
                                <p class="text-xs text-cyan-300 font-semibold mt-0.5">${EC.formatEth(new Date())} • ${currentTeam?.team_name || activeCleaningTeamName}</p>
                            </div>
                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                <div class="flex items-center gap-1.5 bg-slate-900/90 p-1 rounded-2xl border border-cyan-500/50 shadow">
                                    <label class="text-[10px] font-bold text-slate-300 pl-1 shrink-0">ቡድን ቀይር፡</label>
                                    <select onchange="switchCleaningTeam(this.value, this.options[this.selectedIndex].text)" class="bg-slate-800 border border-slate-700 text-cyan-300 font-bold text-xs rounded-xl px-2 py-1 cursor-pointer focus:outline-none max-w-[140px] truncate">
                                        ${teamsList.map(t => `
                                            <option value="${t.id}" ${(t.id == activeCleaningTeamId || (currentTeam && t.id == currentTeam.id)) ? 'selected' : ''}>${t.team_name}</option>
                                        `).join('')}
                                    </select>
                                </div>
                                <span class="px-3 py-1.5 rounded-xl ${activeTab === 'cleaner_history' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30'} text-xs font-black shrink-0">${tabBadge}</span>
                            </div>
                        </div>

                        <!-- Cleaner Team Real Performance Stats Strip (from DB) -->
                        <div class="grid grid-cols-3 gap-2 p-3 bg-slate-900 border border-slate-800 rounded-2xl text-center shadow-lg">
                            <div>
                                <span class="text-[10px] text-slate-400 block font-semibold">የተጠናቀቁ ስራዎች</span>
                                <span class="text-base font-black text-emerald-400 mt-0.5 block">${data.stats?.total_completed || 0}</span>
                            </div>
                            <div class="border-x border-slate-800">
                                <span class="text-[10px] text-slate-400 block font-semibold">ስራ ማጠናቀቅ</span>
                                <span class="text-base font-black text-cyan-300 mt-0.5 block">${data.stats?.completion_rate || 100}%</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-400 block font-semibold">አማካይ እርካታ</span>
                                <span class="text-base font-black text-amber-400 mt-0.5 block">${data.stats?.avg_rating || 5.0} ★</span>
                            </div>
                        </div>

                        <!-- Tab Quick Switch Pills (All 3 Views Accessible with 1 Tap!) -->
                        <div class="grid grid-cols-3 gap-1.5 p-1.5 bg-slate-900/90 rounded-2xl border border-slate-800 shadow-md">
                            <button onclick="setTab('cleaner_jobs')" class="py-2.5 px-2 text-center rounded-xl text-xs font-black transition-all cursor-pointer flex items-center justify-center gap-1.5 ${activeTab === 'cleaner_jobs' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30' : 'text-slate-400 hover:text-white bg-slate-800/40'}">
                                <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
                                <span>የዛሬ (${todayList.length})</span>
                            </button>
                            <button onclick="setTab('cleaner_upcoming')" class="py-2.5 px-2 text-center rounded-xl text-xs font-black transition-all cursor-pointer flex items-center justify-center gap-1.5 ${activeTab === 'cleaner_upcoming' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/30' : 'text-slate-400 hover:text-white bg-slate-800/40'}">
                                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                <span>ቀጣይ (${upcomingList.length})</span>
                            </button>
                            <button onclick="setTab('cleaner_history')" class="py-2.5 px-2 text-center rounded-xl text-xs font-black transition-all cursor-pointer flex items-center justify-center gap-1.5 ${activeTab === 'cleaner_history' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30' : 'text-slate-400 hover:text-white bg-slate-800/40'}">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                <span>የተጠናቀቁ (${completedList.length})</span>
                            </button>
                        </div>

                        ${jobs.length === 0 ? `
                            <div class="p-12 text-center bg-slate-900 rounded-3xl border border-slate-800">
                                <i data-lucide="${activeTab === 'cleaner_history' ? 'clock' : (activeTab === 'cleaner_upcoming' ? 'calendar' : 'check-circle-2')}" class="w-12 h-12 ${activeTab === 'cleaner_history' ? 'text-emerald-400' : 'text-cyan-400'} mx-auto mb-3"></i>
                                <h4 class="text-lg font-bold text-white">${emptyTitle}</h4>
                                <p class="text-xs text-slate-400 mt-1">${emptyMsg}</p>
                            </div>
                        ` : ''}

                        <!-- JOB CARDS -->
                        <div class="space-y-4">
                            ${jobs.map(job => `
                                <div class="bg-slate-900 border ${job.order_status === 'cleaning' ? 'border-cyan-500 shadow-xl shadow-cyan-500/10' : (job.order_status === 'completed' ? 'border-emerald-500/30' : 'border-slate-800')} rounded-3xl p-5 sm:p-6 space-y-4">
                                    <!-- የቀጠሮ ሰዓት & Status -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full ${job.order_status === 'cleaning' ? 'bg-cyan-400 animate-ping' : (job.order_status === 'completed' ? 'bg-emerald-400' : 'bg-blue-400')}"></span>
                                            <span class="text-xs font-bold text-white">${job.eth_date ? (job.eth_date + ' • ') : ''}${formatTimeSlot(job.appointment_time_slot)}</span>
                                        </div>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${job.order_status === 'completed' ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : (job.order_status === 'cleaning' ? 'bg-cyan-500/20 text-cyan-400 border border-cyan-500/30' : 'bg-blue-500/20 text-blue-400')}">
                                            ${job.order_status === 'completed' ? 'ተጠናቋል (Completed)' : (job.order_status === 'cleaning' ? 'በጽዳት ላይ (In Progress)' : (job.order_status === 'on_the_way' ? 'በመንገድ ላይ' : job.order_status))}
                                        </span>
                                    </div>

                                    <!-- Customer Details -->
                                    <div>
                                        <h3 class="text-lg font-black text-white">${job.customer?.full_name || 'ውድ ደንበኛ'}</h3>
                                        <p class="text-xs font-medium text-slate-400 flex items-center gap-1.5 mt-1">
                                            <i data-lucide="map-pin" class="w-3.5 h-3.5 text-cyan-400 shrink-0"></i>
                                            <span>${job.address || job.customer?.address || 'አዲስ አበባ'}</span>
                                        </p>
                                    </div>

                                    <!-- የሚፀዱ እቃዎች / አገልግሎት Breakdown -->
                                    <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700/80 space-y-2">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">የሚፀዱ የተመደቡ እቃዎች / አገልግሎት</span>
                                        ${(job.items && job.items.length > 0) ? job.items.map(item => `
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="font-bold text-slate-200">${item.service?.name_am || item.item_name || 'ጽዳት'}</span>
                                                <span class="px-2 py-0.5 rounded bg-slate-700 text-cyan-400 font-extrabold">× ${parseFloat(item.quantity || 1)}</span>
                                            </div>
                                        `).join('') : `
                                            <div class="text-xs text-slate-300 font-semibold flex items-center justify-between">
                                                <span>${job.order_number || 'መደበኛ የጽዳት አገልግሎት'}</span>
                                                <span class="text-cyan-400 font-mono">${job.total_amount ? (Number(job.total_amount).toLocaleString() + ' ETB') : ''}</span>
                                            </div>
                                        `}
                                    </div>

                                    <!-- Action Buttons -->
                                    ${job.order_status === 'completed' ? `
                                        <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-2 border-t border-slate-800/80">
                                            <div class="flex items-center gap-2 text-xs">
                                                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                                                <span class="text-emerald-400 font-bold">ስራው ተጠናቋል!</span>
                                                ${job.payment_status === 'paid' ? `
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">የተከፈለ (Paid)</span>
                                                ` : (job.payment_status === 'pending_approval' ? `
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/40">ክፍያ ለመጽደቅ ቀርቧል</span>
                                                ` : `
                                                    <button onclick="openCleanerPaymentModal(${job.id}, '${escapeHtml(job.order_number || '')}', ${parseFloat(job.total || job.total_amount || 0)})" class="px-2.5 py-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[11px] shadow cursor-pointer flex items-center gap-1">
                                                        <span>ክፍያ አስገባ</span>
                                                    </button>
                                                `)}
                                            </div>
                                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                                <a href="tel:${job.customer?.phone || ''}" class="flex-1 sm:flex-initial py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold flex items-center justify-center gap-1.5 border border-slate-700">
                                                    <i data-lucide="phone" class="w-3.5 h-3.5 text-emerald-400"></i>
                                                    <span>ደውል</span>
                                                </a>
                                                <button onclick="showCleanerJobAddress(${job.id})" class="flex-1 sm:flex-initial py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-cyan-300 border border-cyan-500/40 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                                                    <i data-lucide="map-pin" class="w-3.5 h-3.5 text-cyan-400"></i>
                                                    <span>አድራሻ</span>
                                                </button>
                                            </div>
                                        </div>
                                    ` : `
                                        <div class="pt-2 grid grid-cols-2 sm:grid-cols-4 gap-2">
                                            <a href="tel:${job.customer?.phone || ''}" class="py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold flex items-center justify-center gap-1.5 border border-slate-700">
                                                <i data-lucide="phone" class="w-3.5 h-3.5 text-emerald-400"></i>
                                                <span>ደውል</span>
                                            </a>
                                            <button onclick="showCleanerJobAddress(${job.id})" class="py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-cyan-300 border border-cyan-500/40 text-xs font-bold flex items-center justify-center gap-1.5 transition-colors cursor-pointer">
                                                <i data-lucide="map-pin" class="w-3.5 h-3.5 text-cyan-400"></i>
                                                <span>አድራሻ</span>
                                            </button>

                                            ${(job.order_status === 'new' || job.order_status === 'assigned' || job.order_status === 'confirmed') ? `
                                                <button onclick="cleanerStartJourney(${job.id})" class="py-2.5 rounded-xl bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white text-xs font-extrabold flex items-center justify-center gap-1.5 shadow-lg col-span-2 cursor-pointer">
                                                    <i data-lucide="navigation" class="w-3.5 h-3.5"></i>
                                                    <span>ወደ ደንበኛው ጉዞ ጀምር</span>
                                                </button>
                                            ` : (job.order_status === 'on_the_way' ? `
                                                <button onclick="cleanerJobAction(${job.id}, 'start')" class="py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-lg cursor-pointer">
                                                    <i data-lucide="play" class="w-3.5 h-3.5"></i>
                                                    <span>ስራ ጀምር</span>
                                                </button>
                                            ` : (job.order_status === 'cleaning' ? `
                                                <button onclick="openCleanerPaymentModal(${job.id}, '${escapeHtml(job.order_number || '')}', ${parseFloat(job.total || job.total_amount || 0)})" class="py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white text-xs font-extrabold flex items-center justify-center gap-1.5 shadow-lg cursor-pointer">
                                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                    <span>ስራ ጨርስና ክፍያ አስገባ</span>
                                                </button>
                                            ` : `
                                                <span class="py-2.5 text-center text-xs font-bold text-emerald-400 col-span-2">ተጠናቋል</span>
                                            `))}

                                            <button onclick="openProblemReportModal(${job.id})" class="py-2.5 rounded-xl bg-red-950/50 hover:bg-red-900/50 text-red-300 text-xs font-bold flex items-center justify-center gap-1.5 border border-red-500/30 cursor-pointer">
                                                <i data-lucide="alert-triangle" class="w-3.5 h-3.5 text-red-400"></i>
                                                <span>ችግር</span>
                                            </button>
                                            <button onclick="openPostponeOrderModal(${job.id}, '${(job.order_number || '').replace(/'/g, "\\'")}', '${(job.customer?.full_name || 'ደንበኛ').replace(/'/g, "\\'")}')" class="py-2.5 rounded-xl bg-amber-950/60 hover:bg-amber-900/60 text-amber-300 text-xs font-bold flex items-center justify-center gap-1.5 border border-amber-500/30 ${job.order_status === 'new' || job.order_status === 'assigned' ? 'col-span-1' : 'col-span-2 sm:col-span-4'} transition-all cursor-pointer">
                                                <i data-lucide="calendar-clock" class="w-3.5 h-3.5 text-amber-400"></i>
                                                <span>ቀጠሮ አስተላልፍ</span>
                                            </button>
                                        </div>
                                    `}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                console.error('Cleaner view error:', err);
                container.innerHTML = `
                    <div class="p-6 text-center space-y-4 max-w-md mx-auto bg-slate-900 border border-red-500/40 rounded-3xl my-8 shadow-2xl">
                        <i data-lucide="alert-circle" class="w-12 h-12 text-red-400 mx-auto"></i>
                        <div>
                            <h4 class="text-base font-bold text-white">የስራ ዝርዝር መጫን አልተቻለም</h4>
                            <p class="text-xs text-red-300 mt-1">${err.message}</p>
                        </div>
                        <div class="flex flex-wrap gap-2 justify-center pt-2">
                            <button onclick="renderCleanerView(document.getElementById('app-viewport'))" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white rounded-xl text-xs font-bold shadow">
                                እንደገና ሞክር (Retry)
                            </button>
                            <button onclick="forcePurgePwaCache()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-xl text-xs font-bold">
                                ካሽ አጽዳ እና አድስ (Clear Cache)
                            </button>
                        </div>
                    </div>
                `;
                if (window.lucide) lucide.createIcons();
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
        }

        async function cleanerJobAction(orderId, action, extraPayload = {}) {
            const payload = Object.assign({
                order_id: orderId,
                action: action,
                order_status: action === 'complete' ? 'completed' : (action === 'start' ? 'cleaning' : (action === 'on_the_way' ? 'on_the_way' : action)),
            }, extraPayload);

            if (navigator.onLine) {
                try {
                    const res = await apiFetch(`/api/orders/${orderId}/status`, {
                        method: 'PATCH',
                        body: JSON.stringify({ status: payload.order_status, ...extraPayload })
                    });
                    if (res && res.ok) {
            alert(action === 'on_the_way' ? ' ጉዞ ጀምረዋል! (Sent)' : ' የስራ ሁኔታ ተቀይሮ ተልኳል (Sent)');
                    } else {
                        await window.meashOffline.enqueue('order', 'job_action', payload);
            alert(' Offline ተቀምጧል - ኢንተርኔት ሲኖር ይላካል (Saved offline - will send when online)');
                    }
                } catch (e) {
                    await window.meashOffline.enqueue('order', 'job_action', payload);
          alert(' Offline ተቀምጧል - ኢንተርኔት ሲኖር ይላካል (Saved offline - will send when online)');
                }
            } else {
                await window.meashOffline.enqueue('order', 'job_action', payload);
        alert(' Offline ተቀምጧል - ኢንተርኔት ሲኖር ይላካል (Saved offline - will send when online)');
            }

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
                                    <span>ፕሮፎርማ አዘጋጅ</span>
                                </button>
                                <button onclick="exportSalesTableToExcel()" class="px-3.5 py-2.5 bg-emerald-600/90 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="download" class="w-4 h-4"></i>
                                    <span>Excel አውርድ (.xls)</span>
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
                                                            ፕሮፎርማ
                                                        </button>
                                                        <button onclick="advanceSalesStageDirect(${v.id}, '${v.stage}')" class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded text-[10px]" title="ደረጃ ቀይር">
                                                            ደረጃ
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
                renderErrorCard(container, 'የውጭ ሽያጭ መረጃዎችን መጫን አልተቻለም፡ ' + err.message, 'renderSalesModule(document.getElementById(\'app-viewport\'))');
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
            let dataRowCount = 0;
            allRows.forEach(row => {
                const cells = row.querySelectorAll('th, td');
                if (cells.length > 0) {
                    cells[cells.length - 1].remove(); // remove last column (action buttons)
                }
                if (row.parentElement && row.parentElement.tagName === 'TBODY' && !row.innerText.includes('የተመዘገበ የውጭ ሽያጭ እንቅስቃሴ የለም')) {
                    dataRowCount++;
                }
            });

            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            const ethToday = EC.formatEth(today);
            const userName = (typeof currentUser !== 'undefined' && currentUser && currentUser.name) ? currentUser.name : 'የሜሽ ሽያጭ አስተዳደር';

            const template = `
                <html>
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; margin: 0; padding: 15px; background: #ffffff; }
                        table { border-collapse: collapse; width: 100%; font-size: 10pt; }
                        th { background-color: #1b4382; color: #ffffff; font-weight: bold; border: 1px solid #475569; padding: 10px 8px; text-align: center; vertical-align: middle; }
                        td { border: 1px solid #cbd5e1; padding: 8px; vertical-align: middle; }
                        tr:nth-child(even) { background-color: #f8fafc; }
                    </style>
                </head>
                <body>
                    <table style="width:100%; border:none; margin-bottom:12px;">
                        <tr style="border:none;">
                            <td colspan="11" style="border:none; background-color:#0f274a; color:#ffffff; padding:18px; text-align:center;">
                                <div style="font-size:22pt; font-weight:900; letter-spacing:1.5px; color:#ffffff;">MEASH CLEANING SOLUTION</div>
                                <div style="font-size:13pt; font-weight:bold; color:#38bdf8; margin-top:3px;">ሜሽ የፅዳት እና የህንፃ አያያዝ አገልግሎት</div>
                                <div style="font-size:10pt; font-style:italic; color:#cbd5e1; margin-top:4px;">«የአካባቢዎ ንጽህና፣ የኛ ኃላፊነት! | Your Environment, Our Responsibility»</div>
                                <div style="font-size:9.5pt; color:#93c5fd; margin-top:6px;">ስልክ፡ 0970075509 / 0998142190 | ቴሌግራም ቻናል፡ @meashcleaningsolution | ቀጥታ ለማነጋገር (Direct Contact)፡ @meashcleaning | አዲስ አበባ፣ ኢትዮጵያ</div>
                            </td>
                        </tr>
                        <tr style="border:none;">
                            <td colspan="6" style="border:none; padding:8px 0; font-size:10pt; font-weight:bold; color:#1e293b;">
                                ይፋዊ የመስክ ሽያጭ የስራ እንቅስቃሴ መዝገብ (Outdoor Sales Activity Log)
                            </td>
                            <td colspan="5" style="border:none; padding:8px 0; font-size:10pt; text-align:right; color:#475569;">
                                ቀን፡ <strong>${ethToday} (${todayStr})</strong> | ያወጣው፡ <strong>${userName}</strong>
                            </td>
                        </tr>
                    </table>

                    ${clone.outerHTML}

                    <table style="width:100%; border:none; margin-top:14px;">
                        <tr style="border:none;">
                            <td colspan="11" style="border:none; padding:12px; background-color:#f1f5f9; font-size:9.5pt; color:#334155;">
                                <strong>ማጠቃለያ፡</strong> ጠቅላላ የተመዘገቡ የጉብኝትና የድርጅቶች እንቅስቃሴዎች ብዛት፦ <strong>${dataRowCount}</strong> | ይህ ሰነድ በሜሽ ክሊኒንግ ሶሉሽን ይፋዊ የውጭ ሽያጭ መረጃ ቋት የተዘጋጀ ነው።
                            </td>
                        </tr>
                    </table>
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
        window._ordersFilterSource = window._ordersFilterSource || '';
        window._ordersFilterStatus = window._ordersFilterStatus || '';
        window._ordersFilterPayment = window._ordersFilterPayment || '';
        window._ordersSearchQuery = window._ordersSearchQuery || '';
        window._ordersPage = window._ordersPage || 1;
        window._ordersPerPage = window._ordersPerPage || 50;

        async function renderOrdersModule(container, isSilent = false) {
            try {
                let orders = [];
                let totalOrders = 0;
                let lastPage = 1;
                let currentPage = window._ordersPage || 1;

                if (navigator.onLine) {
                    try {
                        const queryParams = new URLSearchParams();
                        if (window._ordersFilterSource) queryParams.set('source', window._ordersFilterSource);
                        if (window._ordersFilterStatus) queryParams.set('status', window._ordersFilterStatus);
                        if (window._ordersFilterPayment) queryParams.set('payment_status', window._ordersFilterPayment);
                        if (window._ordersSearchQuery) queryParams.set('search', window._ordersSearchQuery);
                        queryParams.set('page', window._ordersPage || 1);
                        queryParams.set('per_page', window._ordersPerPage || 50);

                        const res = await apiFetch('/api/orders?' + queryParams.toString());
                        if (res.ok) {
                            const data = await res.json();
                            orders = data.data || [];
                            totalOrders = data.total !== undefined ? data.total : orders.length;
                            lastPage = data.last_page || 1;
                            currentPage = data.current_page || 1;

                            if (window.meashOffline && typeof window.meashOffline.cacheItems === 'function') {
                                window.meashOffline.cacheItems('orders', orders).catch(() => {});
                            }
                            try { localStorage.setItem('meash_cached_orders_list', JSON.stringify(orders)); } catch (_) {}
                        }
                    } catch (netErr) {
                        console.warn('Orders network fetch failed, falling back to cache:', netErr);
                    }
                }

                // If offline or network failed, pull from offline storage
                if (orders.length === 0) {
                    if (window.meashOffline && typeof window.meashOffline.getAllItems === 'function') {
                        try {
                            const cached = await window.meashOffline.getAllItems('orders');
                            if (cached && cached.length > 0) {
                                orders = cached;
                            }
                        } catch (_) {}
                    }
                    if (orders.length === 0) {
                        const localCached = localStorage.getItem('meash_cached_orders_list');
                        if (localCached) {
                            try { orders = JSON.parse(localCached); } catch (_) {}
                        }
                    }
                    totalOrders = orders.length;
                    lastPage = Math.max(1, Math.ceil(totalOrders / (window._ordersPerPage || 50)));
                }

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">የትዕዛዞች ማስተዳደሪያ (Orders Management)</h2>
                                <p class="text-xs text-slate-400 mt-1">ሁሉንም የፅዳት ትዕዛዞች በምንጭ፣ በሁኔታ እና በክፍያ ማጣራትና ማስተዳደር።</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="downloadOrdersCsv()" class="px-3.5 py-2.5 bg-emerald-600/90 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow flex items-center gap-1.5 cursor-pointer transition">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    <span>CSV አውርድ</span>
                                </button>
                                <button onclick="renderOrdersModule(document.getElementById('app-viewport'))" class="px-3.5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl border border-slate-700 flex items-center gap-1.5 transition cursor-pointer" title="ዳታውን አድስ">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-cyan-400"></i>
                                    <span class="hidden sm:inline">አድስ (Refresh)</span>
                                </button>
                                <button onclick="openCreateOrderModal()" class="px-4 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2 cursor-pointer transition">
                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                    <span>+ አዲስ ትዕዛዝ</span>
                                </button>
                            </div>
                        </div>

                        <!-- Multi-Attribute Filters Bar -->
                        <div class="p-4 rounded-3xl bg-slate-900 border border-slate-800 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                            <!-- Search -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">ፍለጋ (ስም፣ ስልክ፣ ወይም #)</label>
                                <div class="relative">
                                    <input type="text" id="order-search-input" value="${escapeHtml(window._ordersSearchQuery)}" placeholder="ፈልግ..." onkeydown="if(event.key==='Enter'){ window._ordersSearchQuery = this.value; window._ordersPage = 1; renderOrdersModule(document.getElementById('app-viewport')); }" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs focus:outline-none focus:border-cyan-500 pr-8">
                                    <button onclick="window._ordersSearchQuery = document.getElementById('order-search-input').value; window._ordersPage = 1; renderOrdersModule(document.getElementById('app-viewport'));" class="absolute right-2 top-2.5 text-slate-400 hover:text-white">
                                        <i data-lucide="search" class="w-3.5 h-3.5"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Channel Source Filter -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">የትዕዛዝ ምንጭ (Channel Source)</label>
                                <select onchange="window._ordersFilterSource = this.value; window._ordersPage = 1; renderOrdersModule(document.getElementById('app-viewport'));" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold cursor-pointer focus:outline-none focus:border-cyan-500">
                                    <option value="" ${window._ordersFilterSource === '' ? 'selected' : ''}>-- ሁሉም ምንጮች (All Channels) --</option>
                                    <option value="website" ${window._ordersFilterSource === 'website' ? 'selected' : ''}>ድረ-ገጽ (Website)</option>
                                    <option value="telegram_miniapp" ${window._ordersFilterSource === 'telegram_miniapp' ? 'selected' : ''}>ቴሌግራም ሚኒአፕ (MiniApp)</option>
                                    <option value="telegram_bot" ${window._ordersFilterSource === 'telegram_bot' ? 'selected' : ''}>ቴሌግራም ቦት (Telegram Bot)</option>
                                    <option value="phone" ${window._ordersFilterSource === 'phone' ? 'selected' : ''}>ስልክ ጥሪ (Phone Call)</option>
                                    <option value="walk_in" ${window._ordersFilterSource === 'walk_in' ? 'selected' : ''}>በአካል የመጣ (Walk-in)</option>
                                    <option value="internal" ${window._ordersFilterSource === 'internal' ? 'selected' : ''}>ውስጣዊ / ሪሴፕሽን (Internal)</option>
                                    <option value="corporate_contract" ${window._ordersFilterSource === 'corporate_contract' ? 'selected' : ''}>ኮርፖሬት ውል (Corporate)</option>
                                </select>
                            </div>

                            <!-- Order Status Filter -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">የትዕዛዝ ሁኔታ (Order Status)</label>
                                <select onchange="window._ordersFilterStatus = this.value; window._ordersPage = 1; renderOrdersModule(document.getElementById('app-viewport'));" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold cursor-pointer focus:outline-none focus:border-cyan-500">
                                    <option value="" ${window._ordersFilterStatus === '' ? 'selected' : ''}>-- ሁሉም ሁኔታዎች (All Statuses) --</option>
                                    <option value="new" ${window._ordersFilterStatus === 'new' ? 'selected' : ''}>አዲስ (New)</option>
                                    <option value="pending_confirmation" ${window._ordersFilterStatus === 'pending_confirmation' ? 'selected' : ''}>ማረጋገጫ የሚጠብቅ</option>
                                    <option value="confirmed" ${window._ordersFilterStatus === 'confirmed' ? 'selected' : ''}>የተረጋገጠ (Confirmed)</option>
                                    <option value="assigned" ${window._ordersFilterStatus === 'assigned' ? 'selected' : ''}>ሰራተኛ የተመደበለት (Assigned)</option>
                                    <option value="on_the_way" ${window._ordersFilterStatus === 'on_the_way' ? 'selected' : ''}>በመንገድ ላይ (On the way)</option>
                                    <option value="cleaning" ${window._ordersFilterStatus === 'cleaning' ? 'selected' : ''}>በፅዳት ላይ (Cleaning)</option>
                                    <option value="completed" ${window._ordersFilterStatus === 'completed' ? 'selected' : ''}>የተጠናቀቀ (Completed)</option>
                                    <option value="cancelled" ${window._ordersFilterStatus === 'cancelled' ? 'selected' : ''}>የተሰረዘ (Cancelled)</option>
                                </select>
                            </div>

                            <!-- Payment Status Filter -->
                            <div>
                                <label class="block text-[11px] font-bold text-slate-400 mb-1">የክፍያ ሁኔታ (Payment Status)</label>
                                <select onchange="window._ordersFilterPayment = this.value; window._ordersPage = 1; renderOrdersModule(document.getElementById('app-viewport'));" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs font-bold cursor-pointer focus:outline-none focus:border-cyan-500">
                                    <option value="" ${window._ordersFilterPayment === '' ? 'selected' : ''}>-- ሁሉም የክፍያ ሁኔታ --</option>
                                    <option value="pending_approval" ${window._ordersFilterPayment === 'pending_approval' ? 'selected' : ''}>ማጽደቅ የሚጠብቅ (Pending Approval)</option>
                                    <option value="unpaid" ${window._ordersFilterPayment === 'unpaid' ? 'selected' : ''}>ያልተከፈለ (Unpaid)</option>
                                    <option value="partially_paid" ${window._ordersFilterPayment === 'partially_paid' ? 'selected' : ''}>በከፊል የተከፈለ (Partial)</option>
                                    <option value="paid" ${window._ordersFilterPayment === 'paid' ? 'selected' : ''}>የተከፈለ (Paid)</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden shadow-xl">
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead class="bg-slate-800/80 text-slate-400 uppercase text-[10px] tracking-wider">
                                        <tr>
                                            <th class="p-4">ትዕዛዝ #</th>
                                            <th class="p-4">ደንበኛ</th>
                                            <th class="p-4">የትዕዛዝ ምንጭ</th>
                                            <th class="p-4">ቀጠሮ</th>
                                            <th class="p-4">የተመደበ ቡድን</th>
                                            <th class="p-4">ዋጋና ክፍያ</th>
                                            <th class="p-4">ሁኔታ</th>
                                            <th class="p-4 text-right">እርምጃ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800">
                                        ${orders.length === 0 ? `
                                            <tr>
                                                <td colspan="8" class="p-8 text-center text-slate-500 italic">ምንም የተገኘ ትዕዛዝ የለም።</td>
                                            </tr>
                                        ` : orders.map(o => `
                                            <tr class="hover:bg-slate-800/40 transition">
                                                <td class="p-4 font-bold text-cyan-400 font-mono">${escapeHtml(o.order_number)}</td>
                                                <td class="p-4">
                                                    <span class="font-bold text-white block">${escapeHtml(o.customer_name || o.customer?.full_name || o.customer?.name || 'ደንበኛ')}</span>
                                                    <span class="text-slate-400 text-[11px]">${escapeHtml(o.customer_phone || o.customer?.phone || '')}</span>
                                                </td>
                                                <td class="p-4 whitespace-nowrap">
                                                    ${getChannelBadge(o.source)}
                                                </td>
                                                <td class="p-4">
                                                    <span class="font-bold text-white block text-xs"> ${o.eth_appointment_date || (o.appointment_date ? EC.formatEth(o.appointment_date) : '—')}</span>
                                                    <span class="text-slate-400 text-[10px] block"> ${escapeHtml(o.appointment_date || '—')}</span>
                                                    <span class="text-[10px] font-semibold text-cyan-400 block mt-0.5">
                                                        ${escapeHtml(formatTimeSlot(o.appointment_time_slot))}
                                                    </span>
                                                </td>
                                                <td class="p-4 text-slate-300 font-semibold">${(o.assigned_team?.team_name || o.assignedTeam?.team_name) ? `<span class="px-2.5 py-1 rounded-xl bg-emerald-950/80 text-emerald-300 border border-emerald-800 font-bold text-xs inline-flex items-center gap-1 shadow-sm"> ${escapeHtml((o.assigned_team?.team_name || o.assignedTeam?.team_name).split('(')[0])}</span>` : '<span class="px-2.5 py-1 rounded-xl bg-amber-950/60 text-amber-400 border border-amber-900/60 text-[11px] font-bold">ቡድን አልተመደበም</span>'}</td>
                                                <td class="p-4">
                                                    <span class="font-extrabold text-white block">${parseFloat(o.total || 0).toLocaleString()} ETB</span>
                                                    <div class="mt-0.5">${getPaymentBadge(o.payment_status, o)}</div>
                                                </td>
                                                <td class="p-4">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${o.order_status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-cyan-500/20 text-cyan-400'}">
                                                        ${escapeHtml(o.order_status || o.status || '')}
                                                    </span>
                                                </td>
                                                <td class="p-4 text-right space-x-1.5 whitespace-nowrap">
                                                    ${o.payment_status === 'pending_approval' ? `
                                                        <button onclick="openApprovePaymentModal(${o.id}, '${escapeHtml(o.order_number)}', ${parseFloat(o.total || 0)}, '${escapeHtml(o.pending_payment_method || o.payment_method || 'cash')}', '${escapeHtml(o.pending_payment_reference || o.payment_reference || '')}')" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-[11px] rounded-lg shadow-sm transition" title="ክፍያ አጽድቅ (Approve)">
                                                            አጽድቅ
                                                        </button>
                                                        <button onclick="openRejectPaymentModal(${o.id}, '${escapeHtml(o.order_number)}')" class="px-2 py-1 bg-red-950/80 hover:bg-red-900 text-red-300 font-bold text-[11px] rounded-lg border border-red-500/40 transition" title="ክፍያ ውድቅ አድርግ / አጣራ">
                                                            ውድቅ
                                                        </button>
                                                    ` : (o.payment_status !== 'paid' ? `
                                                        <button onclick="openRecordOrderPaymentModal(${o.id}, '${escapeHtml(o.order_number)}', ${parseFloat(o.total || 0)}, 0)" class="px-2.5 py-1 bg-emerald-950/90 hover:bg-emerald-900 text-emerald-300 font-bold text-[11px] rounded-lg border border-emerald-500/40 transition" title="ክፍያ መዝግብ">
                                                            ክፍያ
                                                        </button>
                                                    ` : '')}
                                                    <button onclick="confirmAndAssignOrder(${o.id})" class="px-2.5 py-1 bg-blue-950/90 hover:bg-blue-900 text-blue-300 font-bold text-[11px] rounded-lg border border-blue-500/40 transition-colors" title="የፅዳት ሰራተኛ መድብ">ሰራተኛ</button>
                                                    <button onclick="printOrderInvoice(${o.id})" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-amber-300 font-bold text-[11px] rounded-lg border border-amber-500/40 transition" title="ደረሰኝ አትም (Print Invoice)">ደረሰኝ</button>
                                                    <button data-phone="${escapeHtml(o.customer_phone || o.customer?.phone || '')}" data-name="${escapeHtml(o.customer_name || o.customer?.full_name || o.customer?.name || '')}" onclick="openDirectSmsModal(this.dataset.phone, this.dataset.name)" class="px-2.5 py-1 bg-cyan-950/80 hover:bg-cyan-900 text-cyan-300 font-bold text-[11px] rounded-lg border border-cyan-500/30">SMS</button>
                                                    <button onclick="openOrderDetailsModal(${o.id})" class="px-3 py-1 bg-slate-800 hover:bg-slate-700 text-cyan-400 font-bold text-[11px] rounded-lg border border-slate-700">ዝርዝር</button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination Controls Bar -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 p-4 bg-slate-900 border border-slate-800 rounded-2xl text-xs">
                            <div class="text-slate-400 text-xs">
                                ጠቅላላ <span class="font-bold text-white">${totalOrders}</span> ትዕዛዞች • ገጽ <span class="font-bold text-cyan-400">${currentPage}</span> ከ <span class="font-bold text-white">${lastPage}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-[11px] text-slate-400">በአንድ ገጽ፡</label>
                                <select onchange="window._ordersPerPage = parseInt(this.value); window._ordersPage = 1; renderOrdersModule(document.getElementById('app-viewport'));" class="bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1 text-white text-xs cursor-pointer focus:outline-none focus:border-cyan-500">
                                    <option value="20" ${window._ordersPerPage == 20 ? 'selected' : ''}>20</option>
                                    <option value="50" ${window._ordersPerPage == 50 ? 'selected' : ''}>50</option>
                                    <option value="100" ${window._ordersPerPage == 100 ? 'selected' : ''}>100</option>
                                    <option value="200" ${window._ordersPerPage == 200 ? 'selected' : ''}>200</option>
                                </select>
                                <button ${currentPage <= 1 ? 'disabled class="px-3 py-1.5 rounded-lg bg-slate-800/40 text-slate-600 cursor-not-allowed text-xs font-semibold"' : 'onclick="window._ordersPage = ' + (currentPage - 1) + '; renderOrdersModule(document.getElementById(\'app-viewport\'));" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 cursor-pointer text-xs font-semibold"'}">
                                    &larr; ቀዳሚ
                                </button>
                                <button ${currentPage >= lastPage ? 'disabled class="px-3 py-1.5 rounded-lg bg-slate-800/40 text-slate-600 cursor-not-allowed text-xs font-semibold"' : 'onclick="window._ordersPage = ' + (currentPage + 1) + '; renderOrdersModule(document.getElementById(\'app-viewport\'));" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 cursor-pointer text-xs font-semibold"'}">
                                    ቀጣይ &rarr;
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();

                // Live Sync: Background auto-refresh for Orders module
                if (window._ordersRefreshTimer) clearInterval(window._ordersRefreshTimer);
                window._ordersRefreshTimer = setInterval(() => {
                    const modalOpen = document.getElementById('generic-modal-container')?.querySelector('.fixed');
                    if (activeTab === 'orders' && !modalOpen) {
                        renderOrdersModule(document.getElementById('app-viewport'), true);
                    }
                }, 25000);
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
                const subs = (data.subscriptions && data.subscriptions.data) ? data.subscriptions.data : (data.data || (Array.isArray(data) ? data : []));
                const stats = data.stats || {};

                const activeCount = stats.total_active !== undefined ? stats.total_active : subs.filter(s => s.status === 'active').length;
                const weeklyCount = stats.weekly_active !== undefined ? stats.weekly_active : subs.filter(s => s.plan_type === 'weekly').length;
                const biweeklyCount = stats.biweekly_active !== undefined ? stats.biweekly_active : subs.filter(s => s.plan_type === 'biweekly').length;
                const monthlyCount = stats.monthly_active !== undefined ? stats.monthly_active : subs.filter(s => s.plan_type === 'monthly').length;
                const mrr = stats.mrr !== undefined ? stats.mrr : 0;

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">የደንበኝነት እቅዶች አስተዳደር (Subscriptions)</h2>
                                <p class="text-xs text-slate-400 mt-1">ተደጋጋሚ የጽዳት ውሎችን፣ ሳምንታዊ፣ የሁለት ሳምንት እና ወርሃዊ እቅዶችን ማስተዳደር።</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-2 rounded-xl bg-emerald-950/80 border border-emerald-500/30 text-emerald-300 text-xs font-bold">
                                    MRR: ${parseFloat(mrr).toLocaleString()} ETB
                                </span>
                                <button onclick="openCreateSubscriptionModal()" class="px-4 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2 cursor-pointer transition">
                                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                    <span>+ አዲስ ደንበኝነት መዝግብ</span>
                                </button>
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
                          <span class="text-slate-400 text-[11px]"> ${s.customer?.phone || ''}</span>
                                                </td>
                                                <td class="p-4 font-semibold text-slate-300">${s.service?.name_am || s.service?.name_en || 'መደበኛ ጽዳት'}</td>
                                                <td class="p-4">
                                                    <span class="px-2.5 py-1 rounded-xl text-[10px] font-bold ${s.plan_type === 'weekly' ? 'bg-cyan-500/20 text-cyan-300' : (s.plan_type === 'biweekly' ? 'bg-blue-500/20 text-blue-300' : 'bg-purple-500/20 text-purple-300')}">
                            ${s.plan_type === 'weekly' ? ' ሳምንታዊ (Weekly)' : (s.plan_type === 'biweekly' ? ' የሁለት ሳምንት' : ' ወርሃዊ (Monthly)')}
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
                          <button data-phone="${escapeHtml(s.customer?.phone || '')}" data-name="${escapeHtml(s.customer?.full_name || '')}" onclick="openDirectSmsModal(this.dataset.phone, this.dataset.name)" class="px-2.5 py-1 bg-cyan-950/80 hover:bg-cyan-900 text-cyan-300 font-bold text-[11px] rounded-lg border border-cyan-500/30"> SMS</button>
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
                renderErrorCard(container, 'የደንበኝነት እቅዶችን መጫን አልተቻለም፡ ' + e.message, 'renderSubscriptionsModule(document.getElementById(\'app-viewport\'))');
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
          alert(' ' + (data.message || 'የደንበኝነት እቅድ ሁኔታ ተዘምኗል!'));
                    loadActiveTab();
                } else {
          alert(' ስህተት: ' + (data.message || 'ሁኔታውን ማዘመን አልተቻለም'));
                }
            } catch (e) {
                alert('የግንኙነት ስህተት: ' + e.message);
            }
        }

        // ==========================================
        // 5c. DIRECT SMS SENDER MODAL (Admin & Reception)
        // ==========================================
        function formatPhoneForSms(rawPhone) {
            if (!rawPhone) return '+251970075509';
            let clean = rawPhone.toString().trim().replace(/[\s\-\(\)]/g, '');
            if (clean.startsWith('+')) {
                return clean;
            }
            if (clean.startsWith('09')) {
                return '+251' + clean.slice(1);
            }
            if (clean.startsWith('07')) {
                return '+251' + clean.slice(1);
            }
            if (clean.startsWith('251')) {
                return '+' + clean;
            }
            if (clean.length === 9 && (clean.startsWith('9') || clean.startsWith('7'))) {
                return '+251' + clean;
            }
            return clean;
        }

        function openDirectSmsModal(phone = '', name = '') {
            const container = document.getElementById('generic-modal-container');
            const cleanPhone = (phone || '').replace(/\s+/g, '');
            const safeName = name || 'ውድ ደንበኛ';
            const isMobileDevice = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

            // Fetch quick customer suggestions if available
            let customerOptions = '<option value="">-- ከደንበኞች መዝገብ ይምረጡ (ወይም ከታች ስልክ ይጻፉ) --</option>';
            if (window._cachedCustomersList && Array.isArray(window._cachedCustomersList)) {
                customerOptions += window._cachedCustomersList.slice(0, 50).map(c => 
                    `<option value="${c.phone}" data-name="${c.name}">${c.name} (${c.phone})</option>`
                ).join('');
            }

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-cyan-500/50 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 my-auto">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-cyan-600/20 text-cyan-400 flex items-center justify-center">
                                    <i data-lucide="message-square" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-white">ለደንበኛ SMS መልእክት ማዘጋጃ</h3>
                                    <p class="text-[11px] text-slate-400">የተዘጋጀውን መልእክት በስልክዎ በቀላሉ ስካን አድርገው ለደንበኛው በ SMS ይላኩ</p>
                                </div>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <form onsubmit="event.preventDefault();" class="space-y-3.5 text-xs">
                            <!-- Quick Customer Assign / Select -->
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ደንበኛን ይምረጡ (Assign Customer)</label>
                                <select id="direct-sms-customer-select" onchange="assignCustomerToSms(this)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-slate-300 focus:outline-none focus:border-cyan-500">
                                    ${customerOptions}
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 font-bold mb-1">የላኪ ስልክ (የባለቤቱ)</label>
                                    <input type="text" id="direct-sms-sender" value="0970075509" readonly class="w-full bg-slate-800/60 border border-slate-700/80 rounded-xl px-3.5 py-2 text-cyan-400 font-mono font-bold cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">የተቀባይ ደንበኛ ስልክ *</label>
                                    <input type="text" id="direct-sms-phone" oninput="updateDirectSmsSimLink()" required value="${cleanPhone || '0970075509'}" placeholder="0970075509" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-white font-mono font-bold focus:border-cyan-500 focus:outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ፈጣን ዝግጁ አብነቶች (Templates)</label>
                                <select onchange="applySmsTemplate(this.value, document.getElementById('direct-sms-phone')?.dataset?.custName || '${safeName}')" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-slate-300 focus:outline-none focus:border-cyan-500">
                                    <option value="">-- ፈጣን አብነት ይምረጡ --</option>
                  <option value="confirm"> የቀጠሮ ማረጋገጫ (Appointment Confirmed)</option>
                  <option value="dispatched"> የቡድን ስምሪት እና የቀጥታ ካርታ (Dispatched on the way)</option>
                                    <option value="feedback"> የአስተያየት መቀበያ እና ምስጋና (Feedback & Thanks)</option>
                  <option value="subscription"> የደንበኝነት አገልግሎት አስታዋሽ (Subscription Reminder)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የመልእክቱ ይዘት (SMS Message) *</label>
                                <textarea id="direct-sms-message" oninput="updateDirectSmsSimLink()" required rows="3" placeholder="መልእክትዎን እዚህ ይጻፉ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white leading-relaxed focus:outline-none focus:border-cyan-500">ሰላም ${safeName}፣ የሜሽ ክሊኒንግ ቀጠሮዎ በትክክል ተረጋግጧል። በሰዓቱ እንገኛለን። እናመሰግናለን!</textarea>
                            </div>

                            <!-- DEDICATED QR CODE SCANNER (Prominent on PC & Waiting for Owner Scan) -->
                            <div class="p-4 rounded-2xl bg-slate-950 border border-cyan-500/40 flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left shadow-inner">
                                <div class="bg-white p-2 rounded-2xl shadow-xl border border-slate-600 flex-shrink-0">
                                    <img id="direct-sms-qr" src="" alt="SMS QR Code" class="w-32 h-32 object-contain">
                                </div>
                                <div class="space-y-1.5 flex-1">
                                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-1.5">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/40">ለማንኛውም ስማርት ስልክ</span>
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                            <span>ስካን ለማድረግ ዝግጁ</span>
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-300">
                                        በስልክዎ ካሜራ ይህንን QR ኮድ ስካን ሲያደርጉ መልእክቱ በቀጥታ በስልክዎ SMS መላኪያ አፕ ተዘጋጅቶ ይከፈታል::
                                    </p>
                                    <p class="text-[10px] text-slate-400 font-mono">
                                        ተቀባይ ደንበኛ ስልክ፦ <span id="direct-sms-target-display" class="text-cyan-400 font-bold">+251970075509</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Mobile Direct Action Button & Close -->
                            <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-800">
                                ${isMobileDevice ? `
                                    <a id="btn-direct-sim-sms" href="#" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer text-xs">
                                        <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                                        <span>በስልክ SIM ላክ (Go to Messages App)</span>
                                    </a>
                                ` : ''}
                                <button type="button" id="btn-send-sms" onclick="submitDirectSms(event)" class="px-4 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer text-xs">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    <span>በሲስተም መዝገብ ብቻ አስቀምጥ (Log only — not sent)</span>
                                </button>
                                <div class="flex items-center gap-2 ml-auto">
                                    <button type="button" onclick="closeModal()" class="px-5 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl hover:bg-slate-700 cursor-pointer">ዝጋ</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
            updateDirectSmsSimLink();

            // Cache customers list for dropdown if not already cached
            if (!window._cachedCustomersList) {
                apiFetch('/api/customers').then(r => r.json()).then(res => {
                    const cList = res.data || res || [];
                    if (Array.isArray(cList) && cList.length > 0) {
                        window._cachedCustomersList = cList;
                        const sel = document.getElementById('direct-sms-customer-select');
                        if (sel) {
                            sel.innerHTML = '<option value="">-- ከደንበኞች መዝገብ ይምረጡ (ወይም ከታች ስልክ ይጻፉ) --</option>' +
                                cList.slice(0, 50).map(c => `<option value="${c.phone}" data-name="${c.name}">${c.name} (${c.phone})</option>`).join('');
                        }
                    }
                }).catch(() => {});
            }
        }

        function assignCustomerToSms(selectEl) {
            const phone = selectEl.value;
            const opt = selectEl.selectedOptions[0];
            const name = opt ? opt.getAttribute('data-name') : 'ውድ ደንበኛ';
            if (phone) {
                const phoneInput = document.getElementById('direct-sms-phone');
                if (phoneInput) {
                    phoneInput.value = phone;
                    phoneInput.dataset.custName = name;
                }
                const msgBox = document.getElementById('direct-sms-message');
                if (msgBox) {
                    msgBox.value = `ሰላም ${name}፣ የሜሽ ክሊኒንግ ቀጠሮዎ በትክክል ተረጋግጧል። በሰዓቱ እንገኛለን። እናመሰግናለን!`;
                }
                updateDirectSmsSimLink();
            }
        }

        let _smsQrDebounceTimer = null;
        // Local QR generation — no third-party call, no data leaves the
        // browser (the old api.qrserver.com calls sent phone numbers and
        // message text, and the bridge token, to an external service).
        function setLocalQr(imgEl, text) {
            if (!imgEl) return;
            if (typeof qrcode === 'undefined') {
                imgEl.alt = 'QR አልተጫነም — ገጹን እንደገና ይክፈቱ (QR library not loaded)';
                return;
            }
            try {
                const qr = qrcode(0, 'M');   // 0 = auto version, M = 15% error correction
                qr.addData(text);
                qr.make();
                imgEl.src = qr.createDataURL(4, 4);
            } catch (e) { console.error('QR generation failed', e); }
        }

        function updateDirectSmsSimLink() {
            const rawPhone = document.getElementById('direct-sms-phone')?.value || '0970075509';
            const msg = document.getElementById('direct-sms-message')?.value || '';
            const intlPhone = formatPhoneForSms(rawPhone);

            const targetDisp = document.getElementById('direct-sms-target-display');
            if (targetDisp) {
                targetDisp.textContent = intlPhone;
            }

            const link = document.getElementById('btn-direct-sim-sms');
            if (link) {
                // ?&body= is universally recognized across iOS and Android browsers
                link.href = `sms:${intlPhone}?&body=${encodeURIComponent(msg)}`;
            }

            // Debounce QR regeneration so typing remains silky smooth (local
            // generation — no network call, no data leaves the browser).
            if (_smsQrDebounceTimer) clearTimeout(_smsQrDebounceTimer);
            _smsQrDebounceTimer = setTimeout(() => {
                const qr = document.getElementById('direct-sms-qr');
                if (qr) {
                    const payload = `sms:${intlPhone}?&body=${encodeURIComponent(msg)}`;
                    setLocalQr(qr, payload);
                }
            }, 300);
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
            if (e) e.preventDefault();
            const btn = document.getElementById('btn-send-sms');
            const originalText = btn ? btn.innerText : '';
            if (btn) { btn.disabled = true; btn.innerText = 'በመላክ ላይ...'; }

            const phone = document.getElementById('direct-sms-phone')?.value || '';
            const message = document.getElementById('direct-sms-message')?.value || '';
            if (!phone.trim() || !message.trim()) {
                alert('እባክዎ የተቀባይ ስልክ እና የመልእክት ይዘት ያስገቡ።');
                if (btn) { btn.disabled = false; btn.innerText = originalText; }
                return;
            }

            try {
                const res = await apiFetch('/api/notifications/send-direct-sms', {
                    method: 'POST',
                    body: JSON.stringify({ phone, message })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    closeModal();
          alert(' ' + (data.message || 'ተመዝግቧል — SMS አልተላከም (logged only, no gateway)'));
                } else {
          alert(' ስህተት: ' + (data.message || 'ኤስኤምኤስ መላክ አልተቻለም።'));
                }
            } catch (err) {
                alert('የግንኙነት ስህተት: ' + err.message);
            } finally {
                if (btn) { btn.disabled = false; btn.innerText = originalText; }
            }
        }

        // ==========================================
        // 5d. REGISTER NEW CUSTOMER MODAL (Customers Module)
        // ==========================================
        function openRegisterCustomerModal() {
            const container = document.getElementById('generic-modal-container');
            if (!container) return;
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-cyan-500/50 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 my-auto">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-cyan-600/20 text-cyan-400 flex items-center justify-center">
                                    <i data-lucide="user-plus" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-white">አዲስ ደንበኛ መዝግብ</h3>
                                    <p class="text-[11px] text-slate-400">የደንበኛውን መረጃ ያስገቡ እና ይመዝግቡ</p>
                                </div>
                            </div>
                            <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <form onsubmit="submitRegisterCustomer(event)" class="space-y-3.5 text-xs">
                            <div id="register-customer-error" class="hidden p-3 rounded-xl bg-red-500/10 border border-red-500/40 text-red-300 font-bold"></div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ሙሉ ስም *</label>
                                <input type="text" id="reg-cust-name" required maxlength="255" placeholder="ለምሳሌ፡ አበበ ተስፋ" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-white focus:outline-none focus:border-cyan-500">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">ስልክ ቁጥር *</label>
                                    <input type="tel" id="reg-cust-phone" required maxlength="32" placeholder="09..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-white font-mono focus:outline-none focus:border-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">ተጨማሪ ስልክ</label>
                                    <input type="tel" id="reg-cust-alt-phone" maxlength="32" placeholder="09..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-white font-mono focus:outline-none focus:border-cyan-500">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">ክፍለ ከተማ</label>
                                    <input type="text" id="reg-cust-subcity" maxlength="64" placeholder="ለምሳሌ፡ ቦሌ" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-white focus:outline-none focus:border-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">የደንበኛ ዓይነት</label>
                                    <select id="reg-cust-type" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-slate-300 focus:outline-none focus:border-cyan-500">
                                        <option value="individual">ግለሰብ (Individual)</option>
                                        <option value="corporate">ድርጅት (Corporate)</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">አድራሻ</label>
                                <textarea id="reg-cust-address" rows="2" placeholder="ጎዳና / የቤት ቁጥር / ምልክት..." class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white leading-relaxed focus:outline-none focus:border-cyan-500"></textarea>
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ማስታወሻ</label>
                                <textarea id="reg-cust-notes" rows="2" placeholder="ተጨማሪ ማስታወሻ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white leading-relaxed focus:outline-none focus:border-cyan-500"></textarea>
                            </div>
                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-5 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl hover:bg-slate-700 cursor-pointer">ሰርዝ</button>
                                <button type="submit" id="btn-register-customer" class="px-5 py-2 bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-500 hover:to-cyan-500 text-white font-bold rounded-xl shadow-lg cursor-pointer">መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            const nameInput = document.getElementById('reg-cust-name');
            if (nameInput) nameInput.focus();
        }

        async function submitRegisterCustomer(e) {
            if (e) e.preventDefault();
            const errBox = document.getElementById('register-customer-error');
            const btn = document.getElementById('btn-register-customer');
            const originalText = btn ? btn.innerText : '';
            if (errBox) errBox.classList.add('hidden');
            if (btn) { btn.disabled = true; btn.innerText = 'በመመዝገብ ላይ...'; }
            try {
                const payload = {
                    full_name: (document.getElementById('reg-cust-name')?.value || '').trim(),
                    phone: (document.getElementById('reg-cust-phone')?.value || '').trim(),
                    alt_phone: (document.getElementById('reg-cust-alt-phone')?.value || '').trim() || undefined,
                    subcity: (document.getElementById('reg-cust-subcity')?.value || '').trim() || undefined,
                    address: (document.getElementById('reg-cust-address')?.value || '').trim() || undefined,
                    notes: (document.getElementById('reg-cust-notes')?.value || '').trim() || undefined,
                    customer_type: document.getElementById('reg-cust-type')?.value || 'individual'
                };
                if (!payload.full_name || !payload.phone) {
                    throw new Error('ሙሉ ስም እና ስልክ ቁጥር ያስፈልጋሉ።');
                }
                const res = await apiFetch('/api/customers', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                let data = null;
                try { data = await res.json(); } catch (_) {}
                if (res.ok && data && data.customer) {
                    window._cachedCustomersList = null;
                    closeModal();
                    alert('ደንበኛው በተሳካ ሁኔታ ተመዝግቧል!');
                    const container = document.getElementById('app-viewport');
                    if (container) renderCustomersModule(container);
                } else {
                    let msg = (data && data.message) || 'ደንበኛ መመዝገብ አልተቻለም።';
                    if (data && data.errors && typeof data.errors === 'object') {
                        msg = Object.values(data.errors).flat().join(' ');
                    }
                    throw new Error(msg);
                }
            } catch (err) {
                if (errBox) { errBox.textContent = err.message || 'ደንበኛ መመዝገብ አልተቻለም።'; errBox.classList.remove('hidden'); }
                else { alert(err.message || 'ደንበኛ መመዝገብ አልተቻለም።'); }
            } finally {
                if (btn) { btn.disabled = false; btn.innerText = originalText || 'መዝግብ'; }
            }
        }

        // ==========================================
        // 6. CUSTOMERS MODULE (With CRM Filter Tabs, Ratings & 1-Click Message)
        // ==========================================
        window._currentCustomerStage = window._currentCustomerStage || 'all';

        async function switchCustomerStageTab(stage) {
            window._currentCustomerStage = stage;
            const container = document.getElementById('app-viewport');
            if (container) {
                renderCustomersModule(container);
            }
        }

        async function renderCustomersModule(container) {
            try {
                const stage = window._currentCustomerStage || 'all';
                let customers = [];
                let stageCounts = { all: 0, normal_active: 0, comment_waiting: 0, in_service: 0 };

                const url = stage === 'all' ? '/api/customers?per_page=50' : `/api/customers?lifecycle_stage=${stage}&per_page=50`;
                const res = await apiFetch(url);
                if (res.ok) {
                    const data = await res.json();
                    customers = data.data || (data.customers ? (data.customers.data || data.customers) : []);
                    stageCounts = data.stage_counts || stageCounts;
                }

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">የደንበኞች መረጃ ቋት (Customer CRM)</h2>
                                <p class="text-xs text-slate-400 mt-1">የደንበኞች የህይወት ኡደት፣ የኮከብ ደረጃ፣ የአስተያየት ጽሁፍ፣ እና የተጠናቀቀ አገልግሎት ታሪክ።</p>
                            </div>
                            <button onclick="openRegisterCustomerModal()" class="px-4 py-2 bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-500 hover:to-cyan-500 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer">
                                <i data-lucide="user-plus" class="w-4 h-4"></i>
                                <span>+ አዲስ ደንበኛ መዝግብ</span>
                            </button>
                        </div>

                        <!-- Filter Tabs: All, Normal Active Customers (Reviewed), Comment Waiting, In Service -->
                        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 pb-3">
                            <button onclick="switchCustomerStageTab('all')" class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${stage === 'all' ? 'bg-cyan-500 text-slate-950 font-black shadow-lg shadow-cyan-500/20' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'}">
                                ሁሉም ደንበኞች (${stageCounts.all || customers.length})
                            </button>
                            <button onclick="switchCustomerStageTab('normal_active')" class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${stage === 'normal_active' ? 'bg-emerald-500 text-slate-950 font-black shadow-lg shadow-emerald-500/20' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'}">
                                ✓ Normal Active Customers (Reviewed) (${stageCounts.normal_active || 0})
                            </button>
                            <button onclick="switchCustomerStageTab('comment_waiting')" class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${stage === 'comment_waiting' ? 'bg-amber-500 text-slate-950 font-black shadow-lg shadow-amber-500/20' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'}">
                                ⌛ Comment Waiting (${stageCounts.comment_waiting || 0})
                            </button>
                            <button onclick="switchCustomerStageTab('in_service')" class="px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer ${stage === 'in_service' ? 'bg-blue-500 text-slate-950 font-black shadow-lg shadow-blue-500/20' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'}">
                                🛠 In Service (${stageCounts.in_service || 0})
                            </button>
                        </div>

                        <!-- Customer Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            ${customers.length === 0 ? `
                                <div class="col-span-full py-12 text-center text-slate-500 text-xs">
                                    በዚህ የማጣሪያ ክፍል የተገኘ ደንበኛ የለም።
                                </div>
                            ` : customers.map(c => {
                                const stageBadge = c.lifecycle_stage === 'normal_active'
                                    ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">✓ Normal Active</span>'
                                    : (c.lifecycle_stage === 'comment_waiting'
                                        ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-500/20 text-amber-300 border border-amber-500/30 animate-pulse">⌛ Comment Waiting</span>'
                                        : (c.lifecycle_stage === 'in_service'
                                            ? '<span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-500/20 text-blue-400 border border-blue-500/30">🛠 In Service</span>'
                                            : '<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">አዲስ (New)</span>'));

                                const ratingDisplay = c.latest_rating 
                                    ? `<div class="flex items-center gap-1 text-amber-400 text-xs font-bold">${'★'.repeat(c.latest_rating)}${'☆'.repeat(5 - c.latest_rating)} <span class="text-white text-[11px] font-bold ml-1">${c.latest_rating}.0</span></div>`
                                    : '<span class="text-slate-500 text-[10px] italic">ደረጃ አልተሰጠም</span>';

                                return `
                                    <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 hover:border-slate-700 transition-all space-y-3 flex flex-col justify-between">
                                        <div class="space-y-2.5">
                                            <div class="flex justify-between items-start gap-2">
                                                <div>
                                                    <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider block">${escapeHtml(c.customer_code || 'CUST')}</span>
                                                    <h3 class="text-base font-black text-white">${escapeHtml(c.full_name)}</h3>
                                                </div>
                                                ${stageBadge}
                                            </div>

                                            <p class="text-xs text-slate-300 font-semibold">${escapeHtml(c.phone)} ${c.subcity ? '| ' + escapeHtml(c.subcity) : ''}</p>

                                            <!-- Communication Channels Status Badges -->
                                            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                                ${c.telegram_user_id ? `
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30" title="ይህ ደንበኛ ቴሌግራም ቦት ተገናኝቷል፤ የቴሌግራም መልእክት ይደርሰዋል">
                                                        <i data-lucide="send" class="w-3 h-3 text-sky-400"></i> Telegram Ready
                                                    </span>
                                                ` : ''}
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30" title="ይህ ደንበኛ የስልክ መስመር አለው፤ ቀጥታ SMS ይደርሰዋል">
                                                    <i data-lucide="smartphone" class="w-3 h-3 text-emerald-400"></i> SMS Ready
                                                </span>
                                            </div>

                                            <!-- Rating & Comment Section -->
                                            <div class="p-2.5 rounded-2xl bg-slate-800/80 border border-slate-700/60 space-y-1.5">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-[10px] text-slate-400 font-bold">የቅርብ ጊዜ ደረጃ:</span>
                                                    ${ratingDisplay}
                                                </div>
                                                ${c.latest_comment ? `
                                                    <p class="text-[11px] text-slate-300 italic line-clamp-2 pt-1 border-t border-slate-700/50">“${escapeHtml(c.latest_comment)}”</p>
                                                ` : '<p class="text-[10px] text-slate-500 italic">አስተያየት አልተመዘገበም</p>'}
                                            </div>

                                            <!-- Total Spend & Orders Stats -->
                                            <div class="flex items-center justify-between text-xs px-1">
                                                <span class="text-slate-400 font-semibold">አጠቃላይ ወጪ:</span>
                                                <span class="font-mono font-black text-emerald-400 text-sm">${Number(c.total_spent || 0).toLocaleString()} ETB <span class="text-[10px] text-slate-400 font-normal">(${c.total_orders || 0} ትዕዛዝ)</span></span>
                                            </div>
                                        </div>

                                        <!-- Actions: 1-Click Send Message & View Profile -->
                                        <div class="pt-3 border-t border-slate-800 flex items-center justify-between gap-2">
                                            <button data-phone="${escapeHtml(c.phone)}" data-name="${escapeHtml(c.full_name)}" data-tg="${escapeHtml(c.telegram_user_id || '')}" onclick="openDirectCustomerMessageModal(this.dataset.phone, this.dataset.name, this.dataset.tg)" class="flex-1 px-3 py-2 bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-500 hover:to-cyan-500 text-white font-black text-xs rounded-xl shadow-md flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                                                <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                                <span>💬 Send Message</span>
                                            </button>
                                            <button onclick="openCustomerProfileModal(${c.id})" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-cyan-400 font-bold text-xs rounded-xl border border-slate-700 transition-colors">ታሪክ &rarr;</button>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
                if (window.lucide) lucide.createIcons();
            } catch (e) {
                renderErrorCard(container, 'የደንበኞች መረጃዎችን መጫን አልተቻለም፡ ' + e.message, 'renderCustomersModule(document.getElementById(\'app-viewport\'))');
            }
        }

        // ==========================================
        // 7. FINANCE & EXPENSES MODULE
        // ==========================================
        async function renderFinanceModule(container) {
            try {
                const currentPeriod = window._financePeriod || 'all_time';
                let data = null;
                let expData = null;

                if (navigator.onLine) {
                    try {
                        const [reportRes, expRes] = await Promise.all([
                            apiFetch('/api/finance/profit-report?period=' + currentPeriod),
                            apiFetch('/api/finance/expenses?per_page=50')
                        ]);
                        if (reportRes.ok && expRes.ok) {
                            data = await reportRes.json();
                            expData = await expRes.json();
                            try {
                                localStorage.setItem('meash_cached_finance_report', JSON.stringify(data));
                                localStorage.setItem('meash_cached_finance_expenses', JSON.stringify(expData));
                            } catch (_) {}
                        }
                    } catch (netErr) {
                        console.warn('Finance network fetch failed, using offline cache:', netErr);
                    }
                }

                if (!data) {
                    const cachedReport = localStorage.getItem('meash_cached_finance_report');
                    if (cachedReport) {
                        try { data = JSON.parse(cachedReport); } catch (_) {}
                    }
                }
                if (!expData) {
                    const cachedExp = localStorage.getItem('meash_cached_finance_expenses');
                    if (cachedExp) {
                        try { expData = JSON.parse(cachedExp); } catch (_) {}
                    }
                }
                if (!data) {
                    data = { revenue: 0, expenses: 0, net_profit: 0, profit_margin: 0, breakdown: [] };
                }
                if (!expData) {
                    expData = { data: [] };
                }

                const expensesList = expData.data || (Array.isArray(expData) ? expData : []);

                const categoryLabels = {
                    chemicals: 'ኬሚካሎች እና ሳሙናዎች',
                    fuel: 'ነዳጅ / ትራንስፖርት',
                    materials: 'የፅዳት መገልገያ እቃዎች',
          employee_payments: ' የሰራተኞች ደመወዝ / ክፍያ',
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
                                <button onclick="downloadFinanceCsv()" class="px-3.5 py-2.5 bg-emerald-600/90 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow flex items-center gap-1.5 cursor-pointer transition">
                                    <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                    <span>CSV አውርድ</span>
                                </button>
                                <button onclick="openPayrollModal()" class="px-4 py-2.5 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2 cursor-pointer transition-all shadow-amber-950/40">
                                    <i data-lucide="banknote" class="w-4 h-4"></i>
                  <span> ደመወዝ ክፈል (Payroll)</span>
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

                        <!-- Payment Methods Revenue Breakdown -->
                        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800">
                            <h4 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                                <i data-lucide="credit-card" class="w-4 h-4 text-emerald-400"></i>
                                <span>የተሰበሰበ ገቢ በክፍያ መንገድ (Income by Payment Method)</span>
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                                ${(data.payment_breakdown || []).map(pm => `
                                    <div class="p-3.5 rounded-2xl bg-slate-800 border border-slate-700">
                                        <span class="text-xs font-bold text-slate-300 block">${pm.label}</span>
                                        <span class="text-lg font-black text-emerald-400 mt-1 block">${parseFloat(pm.total_amount).toLocaleString()} ETB</span>
                                        <span class="text-[10px] text-slate-400">${pm.count} ግብይት</span>
                                    </div>
                                `).join('')}
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
                        const svcData = await res.json(); window._appServicesList = Array.isArray(svcData) ? svcData : (svcData.data || []); window._pricesLive = (window._appServicesList.length > 0);
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
                            ${!navigator.onLine ? `
                            <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs flex items-center gap-2">
                                <i data-lucide="wifi-off" class="w-4 h-4 shrink-0"></i>
                                <span>📶 Offline Mode። ትዕዛዙ በስልክዎ ላይ ተቀምጦ ኢንተርኔት ሲያገኙ Sync ይሆናል።</span>
                            </div>
                            ` : ''}
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
                  <input type="date" id="mo-date" required onchange="document.getElementById('mo-eth-hint').textContent=' ' + EC.formatEth(this.value);" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                  <p id="mo-eth-hint" class="text-[10px] text-cyan-400 font-bold mt-1"> — ዓ.ም</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">የቀጠሮ ሰዓት (ጥዋት / ከሰዓት ብቻ) *</label>
                                    <select id="mo-slot" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none cursor-pointer">
                    <option value="morning"> ጥዋት (2:00–6:00 ጠዋቱ ETH • 8am–12pm)</option>
                    <option value="afternoon"> ከሰዓት (7:00–11:00 ከሰዓቱ ETH • 1pm–5pm)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">የትዕዛዝ መነሻ መንገድ (Order Source) *</label>
                                    <select id="mo-source" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none cursor-pointer font-bold">
                                        <option value="phone">ስልክ ጥሪ (Phone Call)</option>
                                        <option value="walk_in">በአካል የመጣ (Walk-in)</option>
                                        <option value="website">ድረ-ገጽ (Website)</option>
                                        <option value="telegram_miniapp">ቴሌግራም ሚኒአፕ (MiniApp)</option>
                                        <option value="telegram_bot">ቴሌግራም ቦት (Telegram Bot)</option>
                                        <option value="corporate_contract">ኮርፖሬት ውል (Corporate)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">የክፍያ መንገድ (Payment Method) *</label>
                                    <select id="mo-payment-method" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none cursor-pointer font-bold">
                                        <option value="cash">ጥሬ ገንዘብ (Cash)</option>
                                        <option value="telebirr">ቴሌብር (Telebirr)</option>
                                        <option value="cbe">የኢትዮጵያ ንግድ ባንክ (CBE)</option>
                                        <option value="bank_transfer">የባንክ ዝውውር (Bank Transfer)</option>
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
                      const sIcon = s.icon || '';
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

                            <!-- Assign Cleaning Team directly upon creation -->
                            <div class="p-3 rounded-2xl bg-slate-800/80 border border-slate-700 space-y-1">
                <label class="block text-xs font-semibold text-emerald-400 mb-1"> የፅዳት ቡድን መድብ (Assign Cleaning Team)</label>
                                <select id="mo-team-id" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-bold cursor-pointer">
                                    <option value="">-- በኋላ መድብ (Unassigned) --</option>
                                    <option value="1">Team Alpha (Solomon Kebede - Upholstery & Carpet)</option>
                                    <option value="2">Team Bravo (Rotary Scrub & Mattress)</option>
                                    <option value="3">Team Delta (Facade & Post-Construction)</option>
                                    <option value="4">team 1 (Company Shared Car)</option>
                                </select>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-5 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white shadow-lg text-xs font-bold rounded-xl cursor-pointer">ትዕዛዝ መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            const todayIso = new Date().toISOString().split('T')[0];
            document.getElementById('mo-date').value = todayIso;
      document.getElementById('mo-eth-hint').textContent = ' ' + EC.formatEth(todayIso);

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
                <h3 class="text-base font-bold text-white"> አዲስ የፅዳት አገልግሎት መመዝገቢያ</h3>
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
                    <option value=""> ኮከብ (Sparkle)</option>
                    <option value=""> ሶፋ (Sofa)</option>
                    <option value=""> ምንጣፍ (Carpet)</option>
                    <option value=""> ፍራሽ (Mattress)</option>
                    <option value=""> መስታወት (Window)</option>
                    <option value=""> ቤት (House)</option>
                    <option value=""> ህንፃ/ቢሮ (Building)</option>
                    <option value=""> ተሽከርካሪ (Car)</option>
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
          alert(' አዲሱ የፅዳት አገልግሎት በተሳካ ሁኔታ ተመዝግቧል! አሁን በቴሌግራም ቦት፣ በዌብሳይት፣ በሚኒ-አፕ እና በትዕዛዝ መመዝገቢያ ላይ በአንድ ጊዜ ይታያል።');
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
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'በመመዝገብ ላይ...';
            }

            const teamVal = document.getElementById('mo-team-id')?.value;
            const svcId = parseInt(document.getElementById('mo-service-select')?.value) || 1;
            const payMethod = document.getElementById('mo-payment-method')?.value || 'cash';
            const src = document.getElementById('mo-source')?.value || 'phone';
            const payLabels = { cash: 'ጥሬ ገንዘብ (Cash)', telebirr: 'ቴሌብር (Telebirr)', cbe: 'የኢትዮጵያ ንግድ ባንክ (CBE)', bank_transfer: 'የባንክ ዝውውር (Bank Transfer)' };

            const custName = (document.getElementById('mo-cust-name')?.value || '').trim();
            const custPhone = (document.getElementById('mo-cust-phone')?.value || '').trim();
            const subcity = document.getElementById('mo-subcity')?.value || '';
            const address = (document.getElementById('mo-address')?.value || '').trim();
            const apptDate = document.getElementById('mo-date')?.value || '';
            const apptSlot = document.getElementById('mo-slot')?.value || 'morning';
            const teamId = teamVal ? parseInt(teamVal) : null;

            const payload = {
                customer_name: custName,
                full_name: custName,
                customer_phone: custPhone,
                phone: custPhone,
                subcity: subcity,
                address: address,
                source: src,
                notes: 'የተመረጠ የክፍያ መንገድ: ' + (payLabels[payMethod] || payMethod),
                appointment_date: apptDate,
                appointment_time_slot: apptSlot,
                assigned_team_id: teamId,
                team_id: teamId,
                items: [
                    {
                        service_id: svcId,
                        item_name: document.getElementById('mo-item-name')?.value || 'የፅዳት አገልግሎት',
                        quantity: parseFloat(document.getElementById('mo-item-qty')?.value) || 1,
                        unit_price: parseFloat(document.getElementById('mo-item-price')?.value) || 0,
                    }
                ]
            };

            try {
                // Try live API first for authoritative database save
                const res = await apiFetch('/api/orders', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (res.ok && result.order) {
                    const createdOrder = result.order;
                    payload.order_number = createdOrder.order_number;
                    loadActiveTab();
                    showOrderCreatedSuccessModal(payload, createdOrder);
                    return;
                } else {
                    throw new Error(result.message || JSON.stringify(result.errors || 'ትዕዛዙ አልተመዘገበም'));
                }
            } catch (netErr) {
                console.warn('Live order creation failed or offline, falling back to offline queue:', netErr);
                if (window.meashOffline && typeof window.meashOffline.enqueue === 'function') {
                    await window.meashOffline.enqueue('order', 'create', payload);
                    loadActiveTab();
                    showOrderCreatedSuccessModal(payload, null);
                } else {
                    alert('ስህተት፡ ' + netErr.message);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'ትዕዛዝ መዝግብ';
                    }
                }
            }
        }

        function showOrderCreatedSuccessModal(payload, createdOrder = null) {
            const container = document.getElementById('generic-modal-container');
            const custName = payload.customer_name || 'ደንበኛ';
            const custPhone = (payload.customer_phone || '').replace(/\D+/g, '');
            const cleanPhone = custPhone;
            const slotAm = payload.appointment_time_slot === 'morning' ? 'ጥዋት' : 'ከሰዓት';
            const orderNum = createdOrder?.order_number || payload.order_number || 'በመጠባበቅ ላይ (Queue)';
            const msg = `ሰላም ${custName}፣ የሜሽ ክሊኒንግ የፅዳት ቀጠሮዎ በትክክል ተመዝግቧል። ትዕዛዝ #: ${orderNum}፣ ቀን፡ ${EC.formatEth(payload.appointment_date)} (${slotAm})። በሰዓቱ እንገኛለን! ስልክ፡ 0970075509`;
            const qrData = encodeURIComponent(`SMSTO:${cleanPhone}:${msg}`);

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl text-center space-y-4">
                        <div class="w-12 h-12 bg-emerald-500/20 text-emerald-400 rounded-2xl flex items-center justify-center mx-auto text-2xl font-black">
                            <i data-lucide="check" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">ትዕዛዙ በተሳካ ሁኔታ ተመዝግቧል!</h3>
                            <p class="text-xs font-mono font-bold text-cyan-400 mt-1">${escapeHtml(orderNum)}</p>
                            <p class="text-xs text-slate-400 mt-1">ለደንበኛው (${custName} - ${payload.customer_phone}) የማረጋገጫ SMS ይላኩ</p>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <button onclick="closeModal()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl cursor-pointer">
                                አሁን ዝጋ
                            </button>
                            <a href="sms:${cleanPhone}?&body=${encodeURIComponent(msg)}" class="flex-1 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer">
                                <span>በስልክ SIM ላክ</span>
                            </a>
                        </div>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        function openRecordOrderPaymentModal(orderId, orderNumber, total, paidSoFar = 0) {
            const container = document.getElementById('generic-modal-container');
            const remaining = Math.max(0, parseFloat(total) - parseFloat(paidSoFar));
            const todayIso = new Date().toISOString().split('T')[0];

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <span class="text-xs font-bold text-emerald-400 font-mono">${escapeHtml(orderNumber)}</span>
                                <h3 class="text-base font-bold text-white">የትዕዛዝ ክፍያ መመዝገቢያ (Record Payment)</h3>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700 space-y-1 text-xs">
                            <div class="flex justify-between text-slate-300">
                                <span>ጠቅላላ የትዕዛዝ ዋጋ:</span>
                                <span class="font-bold text-white">${parseFloat(total).toLocaleString()} ETB</span>
                            </div>
                            <div class="flex justify-between text-slate-400">
                                <span>እስካሁን የተከፈለ:</span>
                                <span class="font-semibold text-emerald-400">${parseFloat(paidSoFar).toLocaleString()} ETB</span>
                            </div>
                            <div class="flex justify-between text-cyan-300 pt-1 border-t border-slate-700 font-bold">
                                <span>የሚቀረው ቀሪ ሂሳብ:</span>
                                <span class="font-mono text-sm">${remaining.toLocaleString()} ETB</span>
                            </div>
                        </div>

                        <form onsubmit="submitOrderPayment(event, ${orderId})" class="space-y-3.5 text-xs">
                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">የክፍያ መጠን (ETB) *</label>
                                <input type="number" id="pay-amount" required step="any" min="1" max="${remaining > 0 ? remaining : 999999}" value="${remaining > 0 ? remaining : ''}" placeholder="ለምሳሌ፡ 1000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold focus:outline-none focus:border-emerald-500">
                            </div>

                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">የክፍያ መንገድ (Payment Method) *</label>
                                <select id="pay-method" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold cursor-pointer focus:outline-none focus:border-emerald-500">
                                    <option value="cash">ጥሬ ገንዘብ (Cash)</option>
                                    <option value="telebirr">ቴሌብር (Telebirr)</option>
                                    <option value="cbe">የኢትዮጵያ ንግድ ባንክ (CBE)</option>
                                    <option value="bank_transfer">የባንክ ዝውውር (Bank Transfer)</option>
                                    <option value="check">ቼክ (Check)</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-slate-400 font-semibold mb-1">የትራንዛክሽን ቁጥር (Ref / TXN)</label>
                                    <input type="text" id="pay-ref" placeholder="FT... ወይም Ref #" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-slate-400 font-semibold mb-1">የተከፈለበት ቀን *</label>
                                    <input type="date" id="pay-date" required value="${todayIso}" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white focus:outline-none">
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">ተጨማሪ ማስታወሻ (Notes)</label>
                                <input type="text" id="pay-notes" placeholder="የክፍያው ዝርዝር..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white focus:outline-none">
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl hover:bg-slate-700 cursor-pointer">ሰርዝ</button>
                                <button type="submit" id="btn-submit-pay" class="px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer">
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
        window.openRecordOrderPaymentModal = openRecordOrderPaymentModal;

        async function submitOrderPayment(e, orderId) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-pay');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'በመመዝገብ ላይ...';
            }

            const payload = {
                amount: parseFloat(document.getElementById('pay-amount').value),
                payment_method: document.getElementById('pay-method').value,
                reference_number: document.getElementById('pay-ref').value || null,
                payment_date: document.getElementById('pay-date').value,
                notes: document.getElementById('pay-notes').value || null,
            };

            try {
                const res = await apiFetch(`/api/orders/${orderId}/payments`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok) {
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('ክፍያውን መመዝገብ አልተቻለም: ' + (data.message || JSON.stringify(data.errors || 'ስህተት')));
                    if (btn) {
                        btn.disabled = false;
                        btn.textContent = 'ክፍያውን መዝግብ';
                    }
                }
            } catch (err) {
                alert('የኔትወርክ ስህተት: ' + err.message);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'ክፍያውን መዝግብ';
                }
            }
        }
        window.submitOrderPayment = submitOrderPayment;

        // ==========================================
        // TWO-STEP PAYMENT REPORTING & APPROVAL WORKFLOW
        // ==========================================
        function openCleanerPaymentModal(orderId, orderNumber, totalAmount) {
            const container = document.getElementById('generic-modal-container');
            const total = parseFloat(totalAmount || 0);

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white">የክፍያ መሰብሰቢያ (Collect Payment)</h3>
                                <p class="text-xs text-slate-400 font-mono mt-0.5">${escapeHtml(orderNumber)} • ${total.toLocaleString()} ETB</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <div class="p-3 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs flex items-center gap-2">
                            <i data-lucide="info" class="w-4 h-4 shrink-0 text-amber-400"></i>
                            <span>ማሳሰቢያ፡ ክፍያው በካሽም ሆነ በባንክ/ቴሌብር ሲቀርብ በባለቤት ወይም በሪሴፕሽን መጽደቅ አለበት።</span>
                        </div>

                        <form onsubmit="submitCleanerPayment(event, ${orderId})" class="space-y-3.5 text-xs">
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የክፍያ መንገድ (Payment Method) *</label>
                                <select id="cl-pay-method" required onchange="onCleanerPaymentMethodChange(this.value)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold cursor-pointer focus:outline-none focus:border-cyan-500">
                                    <option value="cash">ጥሬ ገንዘብ (Cash)</option>
                                    <option value="telebirr">ቴሌብር (Telebirr)</option>
                                    <option value="cbe">የኢትዮጵያ ንግድ ባንክ (CBE)</option>
                                    <option value="bank_transfer">የባንክ ዝውውር (Bank Transfer)</option>
                                </select>
                            </div>

                            <div id="cl-ref-group" class="hidden">
                                <label class="block text-slate-300 font-bold mb-1">የትራንዛክሽን ማጣቀሻ / Transaction Ref (ለቴሌብር / CBE) *</label>
                                <input type="text" id="cl-pay-ref" placeholder="ምሳሌ፡ TXN-998877 ወይም የላኪ ስም" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono">
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የተሰበሰበው የብር መጠን (Amount in ETB) *</label>
                                <input type="number" id="cl-pay-amount" step="any" min="0" value="${total}" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold font-mono">
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1.5">የተከናወኑ ስራዎች ዝርዝር (Service Checklist):</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 p-2.5 bg-slate-950/60 rounded-xl border border-slate-800 text-[11px] text-slate-300">
                                    <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="cl-chk" value="ጥልቅ ጽዳት ተጠናቋል" checked class="rounded text-cyan-500"> ጥልቅ ጽዳት ተጠናቋል</label>
                                    <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="cl-chk" value="መስተዋትና መስኮት ጽዳት" checked class="rounded text-cyan-500"> መስተዋትና መስኮት ጽዳት</label>
                                    <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="cl-chk" value="ፎቅ ማጠብና ቫኪዩም" checked class="rounded text-cyan-500"> ፎቅ ማጠብና ቫኪዩም</label>
                                    <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="cl-chk" value="ደንበኛ ስራውን አይቶ አጽድቋል" checked class="rounded text-cyan-500"> ደንበኛ ስራውን አይቷል</label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ተጨማሪ ማስታወሻ (Optional Note)</label>
                                <textarea id="cl-pay-notes" rows="2" placeholder="ለምሳሌ፡ ከባለቤቱ በእጅ ተቀብያለሁ / ቴሌብር ደርሶታል..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" id="cl-pay-btn" class="flex-1 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer">
                                    <span>ክፍያውን ለመጽደቅ ላክ</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }
        window.openCleanerPaymentModal = openCleanerPaymentModal;

        function onCleanerPaymentMethodChange(val) {
            const refGrp = document.getElementById('cl-ref-group');
            const refInp = document.getElementById('cl-pay-ref');
            if (!refGrp) return;
            if (val === 'cash') {
                refGrp.classList.add('hidden');
                if (refInp) refInp.removeAttribute('required');
            } else {
                refGrp.classList.remove('hidden');
                if (refInp) refInp.setAttribute('required', 'required');
            }
        }
        window.onCleanerPaymentMethodChange = onCleanerPaymentMethodChange;

        async function submitCleanerPayment(e, orderId) {
            e.preventDefault();
            const btn = document.getElementById('cl-pay-btn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'በመላክ ላይ...';
            }

            const method = document.getElementById('cl-pay-method').value;
            const ref = document.getElementById('cl-pay-ref')?.value || '';
            const amount = parseFloat(document.getElementById('cl-pay-amount').value) || 0;
            const notes = document.getElementById('cl-pay-notes')?.value || '';

            const checkedBoxes = Array.from(document.querySelectorAll('input[name="cl-chk"]:checked')).map(c => c.value);
            const checklistSummary = checkedBoxes.length > 0 ? "Checklist: " + checkedBoxes.join(', ') : '';
            const finalNotes = [notes, checklistSummary].filter(Boolean).join(' | ');

            const payload = {
                payment_method: method,
                payment_reference: ref,
                reference_number: ref,
                amount: amount,
                notes: finalNotes,
            };

            try {
                // Ensure order is completed if still in progress with full checklist notes
                try {
                    await apiFetch(`/api/orders/${orderId}/status`, {
                        method: 'PATCH',
                        body: JSON.stringify({ status: 'completed', notes: finalNotes, checklist: checkedBoxes })
                    });
                } catch (stErr) {
                    console.warn('Status completion auto-call:', stErr);
                }

                const res = await apiFetch(`/api/orders/${orderId}/submit-payment`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await res.json();

                if (res.ok && result.success) {
                    alert('የክፍያ መረጃው በተሳካ ሁኔታ ተልኳል! ባለቤት ወይም ሪሴፕሽን እስኪያጸድቁት ድረስ በ "Pending Approval" ይቆያል።');
                    closeModal();
                    loadActiveTab();
                } else {
                    throw new Error(result.message || 'ስህተት ተከስቷል');
                }
            } catch (err) {
                alert('ስህተት፡ ' + err.message);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'ክፍያውን ለመጽደቅ ላክ';
                }
            }
        }
        window.submitCleanerPayment = submitCleanerPayment;

        function openApprovePaymentModal(orderId, orderNumber, totalAmount, currentMethod, currentRef, paymentId = null) {
            const container = document.getElementById('generic-modal-container');
            const total = parseFloat(totalAmount || 0);
            const method = currentMethod || 'cash';
            const ref = currentRef || '';
            const pIdArg = (paymentId && parseInt(paymentId) > 0) ? parseInt(paymentId) : 'null';

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-emerald-500/40 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white">ክፍያ ማጽደቂያ (Approve Payment)</h3>
                                <p class="text-xs font-mono text-cyan-400 mt-0.5">${escapeHtml(orderNumber)} • ${total.toLocaleString()} ETB</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <form onsubmit="submitApprovePayment(event, ${orderId}, ${pIdArg})" class="space-y-3.5 text-xs">
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የተከፈለበት መንገድ (Payment Method) *</label>
                                <select id="appr-pay-method" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold cursor-pointer focus:outline-none focus:border-cyan-500">
                                    <option value="cash" ${method === 'cash' ? 'selected' : ''}>ጥሬ ገንዘብ (Cash)</option>
                                    <option value="telebirr" ${method === 'telebirr' ? 'selected' : ''}>ቴሌብር (Telebirr)</option>
                                    <option value="cbe" ${method === 'cbe' ? 'selected' : ''}>የኢትዮጵያ ንግድ ባንክ (CBE)</option>
                                    <option value="bank_transfer" ${method === 'bank_transfer' ? 'selected' : ''}>የባንክ ዝውውር (Bank Transfer)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የትራንዛክሽን ማጣቀሻ / Transaction Ref</label>
                                <input type="text" id="appr-pay-ref" value="${escapeHtml(ref)}" placeholder="TXN-..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono">
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የጸደቀው የብር መጠን (Amount in ETB) *</label>
                                <input type="number" id="appr-pay-amount" step="any" min="0.01" value="${total}" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold font-mono">
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ማስታወሻ</label>
                                <input type="text" id="appr-pay-notes" placeholder="የማረጋገጫ ማስታወሻ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" id="appr-btn" class="flex-1 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer">
                                    <span>አጽድቅ (Approve)</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }
        window.openApprovePaymentModal = openApprovePaymentModal;

        async function submitApprovePayment(e, orderId, paymentId = null) {
            e.preventDefault();
            const btn = document.getElementById('appr-btn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'በማጽደቅ ላይ...';
            }

            const method = document.getElementById('appr-pay-method').value;
            const ref = document.getElementById('appr-pay-ref').value;
            const amount = parseFloat(document.getElementById('appr-pay-amount').value);
            const notes = document.getElementById('appr-pay-notes').value;

            try {
                const endpoint = (paymentId && paymentId !== 'null' && parseInt(paymentId) > 0)
                    ? `/api/orders/${orderId}/payments/${paymentId}/verify`
                    : `/api/orders/${orderId}/approve-payment`;

                const res = await apiFetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        payment_method: method,
                        reference_number: ref,
                        amount: amount,
                        notes: notes
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert('ክፍያው በተሳካ ሁኔታ ጸድቋል! ወደ ገቢ መዝገብ ገብቷል።');
                    closeModal();
                    loadActiveTab();
                } else {
                    throw new Error(data.message || 'ማጽደቅ አልተቻለም');
                }
            } catch (err) {
                alert('ስህተት፡ ' + err.message);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'አጽድቅ (Approve)';
                }
            }
        }
        window.submitApprovePayment = submitApprovePayment;

        function openRejectPaymentModal(orderId, orderNumber) {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-red-500/40 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white">ክፍያ ውድቅ ማድረጊያ / ማጣሪያ</h3>
                                <p class="text-xs font-mono text-slate-400 mt-0.5">${escapeHtml(orderNumber)}</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <form onsubmit="submitRejectPayment(event, ${orderId})" class="space-y-3.5 text-xs">
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ውድቅ የተደረገበት ወይም እንደገና የሚጣራበት ምክንያት *</label>
                                <textarea id="rej-reason" required rows="3" placeholder="ለምሳሌ፡ የቴሌብር ክፍያ አልደረሰም / ሂሳቡ አልተገኘም..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                            </div>

                            <div class="flex items-center gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl">ተመለስ</button>
                                <button type="submit" id="rej-btn" class="flex-1 py-2.5 bg-red-600 hover:bg-red-500 text-white font-black rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer">
                                    <span>ውድቅ አድርግ (Reject)</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }
        window.openRejectPaymentModal = openRejectPaymentModal;

        async function submitRejectPayment(e, orderId) {
            e.preventDefault();
            const btn = document.getElementById('rej-btn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'በማስኬድ ላይ...';
            }
            const reason = document.getElementById('rej-reason').value;

            try {
                const res = await apiFetch(`/api/orders/${orderId}/reject-payment`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ reason })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert('ክፍያው ውድቅ ተደርጓል፤ ወደ ያልተከፈለ ሁኔታ ተመልሷል።');
                    closeModal();
                    loadActiveTab();
                } else {
                    throw new Error(data.message || 'ማስኬድ አልተቻለም');
                }
            } catch (err) {
                alert('ስህተት፡ ' + err.message);
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = 'ውድቅ አድርግ (Reject)';
                }
            }
        }
        window.submitRejectPayment = submitRejectPayment;

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
            const btn = e.target.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;
            const payload = {
                category: document.getElementById('exp-category').value,
                amount: parseFloat(document.getElementById('exp-amount').value),
                description: document.getElementById('exp-desc').value,
                date: new Date().toISOString().split('T')[0],
            };
            try {
                const res = await apiFetch('/api/finance/expenses', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok) {
                    alert('ወጪው በተሳካ ሁኔታ በዳታቤዝ ተመዝግቧል!');
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('ስህተት: ' + (data.message || 'ወጪውን መመዝገብ አልተቻለም'));
                }
            } catch (err) {
                if (window.meashOffline && typeof window.meashOffline.enqueue === 'function') {
                    await window.meashOffline.enqueue('expense', 'create', payload);
                    alert('Offline ስለሆኑ ወጪው በስልክዎ ላይ ተቀምጧል፤ ኢንተርኔት ሲመጣ Sync ይሆናል::');
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('የግንኙነት ስህተት: ' + err.message);
                }
            } finally {
                if (btn) btn.disabled = false;
            }
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
                  <span>+ ደመወዝ ክፈል (Payroll)</span>
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
                  <div class="w-12 h-12 rounded-2xl bg-slate-800 text-cyan-400 flex items-center justify-center mx-auto text-xl"></div>
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
                          <span> የቡድን መሪ:</span>
                                                    <strong class="text-white">${t.leader?.name || 'አልተመደበም'}</strong>
                                                </div>
                                                <div class="flex items-center justify-between">
                          <span> ስልክ:</span>
                                                    <span class="text-cyan-300 font-mono">${t.phone || t.leader?.phone || '—'}</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                          <span> አባላት:</span>
                                                    <span class="text-cyan-400 font-bold">${t.members?.length || 1} አባላት</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                          <span> ስራ ማጠናቀቅ:</span>
                                                    <span class="text-emerald-400 font-bold">${t.completion_rate || 100}% (${t.completed_jobs_count || 0} የተጠናቀቁ)</span>
                                                </div>
                                                <div class="flex items-center justify-between">
                          <span> አማካይ እርካታ:</span>
                                                    <span class="text-amber-400 font-bold">${t.avg_rating || 5.0} ★</span>
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
                                            <th class="py-3 px-3">የተጠቃሚ ስም (Username)</th>
                                            <th class="py-3 px-3">ሁኔታ</th>
                                            <th class="py-3 px-3 text-right">ክፍያ / Payroll</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-800/60">
                                        ${employees.map(e => `
                                            <tr class="hover:bg-slate-800/40 transition-colors">
                                                <td class="py-3 px-3 font-bold text-white">
                                                    <button type="button" onclick="openEmployeeHistoryModal(${e.id})" class="flex items-center gap-2.5 text-left group hover:text-cyan-400 transition cursor-pointer">
                                                        <div class="w-7 h-7 rounded-lg bg-slate-800 group-hover:bg-cyan-950 text-cyan-400 font-extrabold flex items-center justify-center text-[11px] border border-slate-700 group-hover:border-cyan-500/50 transition">
                                                            ${(e.name || 'S').slice(0, 2).toUpperCase()}
                                                        </div>
                                                        <div>
                                                            <span class="group-hover:underline flex items-center gap-1.5 font-bold">
                                                                <span>${e.name}</span>
                                                                <i data-lucide="external-link" class="w-3 h-3 text-cyan-400/60 group-hover:text-cyan-400 opacity-0 group-hover:opacity-100 transition"></i>
                                                            </span>
                                                            <span class="text-[10px] text-slate-500 block">የስራና ክፍያ ታሪክ ለማየት ጠቅ ያድርጉ</span>
                                                        </div>
                                                    </button>
                                                </td>
                                                <td class="py-3 px-3">
                                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold border uppercase ${roleBadges[e.role] || 'bg-slate-800 text-slate-300 border-slate-700'}">
                                                        ${roleLabels[e.role] || e.role}
                                                    </span>
                                                </td>
                                                <td class="py-3 px-3 font-mono text-slate-300">${e.phone || '—'}</td>
                                                <td class="py-3 px-3 font-mono text-cyan-400 font-bold">${e.username || (e.email ? e.email.split('@')[0] : '—')}</td>
                                                <td class="py-3 px-3">
                                                    <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-400">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                                        <span>ንቁ (Active)</span>
                                                    </span>
                                                </td>
                                                <td class="py-3 px-3 text-right">
                                                    <div class="flex items-center justify-end gap-1.5">
                                                        <button onclick="openEmployeeHistoryModal(${e.id})" class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-cyan-300 border border-slate-700 font-bold text-[11px] inline-flex items-center gap-1 cursor-pointer transition-all shadow-sm" title="የሰራተኛው የስራ እና የክፍያ ማህደር">
                                                            <i data-lucide="file-text" class="w-3.5 h-3.5 text-cyan-400"></i>
                                                            <span>ታሪክ</span>
                                                        </button>
                                                        <button onclick="openPayrollModal(${e.id}, '${e.name.replace(/'/g, "\\'")}')" class="px-3 py-1.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 font-bold text-[11px] inline-flex items-center gap-1.5 cursor-pointer transition-all shadow-sm">
                                                            <i data-lucide="banknote" class="w-3.5 h-3.5 text-amber-400"></i>
                                                            <span>ደመወዝ ክፈል</span>
                                                        </button>
                                                    </div>
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
                renderErrorCard(container, 'የቡድኖችና የሰራተኞች መረጃ መጫን አልተቻለም፡ ' + err.message, 'renderTeamsModule(document.getElementById(\'app-viewport\'))');
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
                                <label class="block text-slate-300 font-bold mb-1">የተጠቃሚ ስም (ለመግቢያ የሚሆን Username) *</label>
                                <input type="text" id="new-emp-username" required placeholder="ለምሳሌ፡ yonas" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-mono">
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ስልክ ቁጥር *</label>
                                <input type="tel" id="new-emp-phone" required placeholder="0911223344" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-mono">
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የስራ ድርሻ (Role) *</label>
                                <select id="new-emp-role" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white">
                  <option value="cleaner"> የፅዳት ባለሙያ (Cleaner)</option>
                  <option value="reception"> ሪሴፕሽን & ሽያጭ (Reception)</option>
                  <option value="sales"> የውጭ ሽያጭ (Outdoor Sales)</option>
                  <option value="finance"> ፋይናንስ & ሂሳብ (Finance)</option>
                  <option value="owner"> ዋና ስራ አስኪያጅ (Owner)</option>
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

            const usernameInput = document.getElementById('new-emp-username').value.trim();
            const payload = {
                name: document.getElementById('new-emp-name').value.trim(),
                username: usernameInput,
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
          alert(' አዲሱ ሰራተኛ በተሳካ ሁኔታ ተመዝግቧል!');
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
          alert(' የፅዳት ቡድኑ በተሳካ ሁኔታ ተፈጥሯል!');
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
                                <p class="text-[11px] text-slate-400">ለሰራተኛ የተከፈለ ደመወዝ ይመዝግቡ (በሲስተሙ የወጪ መዝገብ ላይ ይገባል)።</p>
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
                                <span>ይህ የተከፈለ ገንዘብ በሲስተሙ በአድሚኑ የወጪ ገጽ (Expense Page) ላይ እንደ «የሰራተኞች ክፍያ» ወጪ ሆኖ ይመዘገባል።</span>
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
          alert(' የደመወዝ ክፍያ በተሳካ ሁኔታ ተመዝግቧል! በአድሚኑ የወጪ ገጽ ላይ ታክሏል።');
                    closeModal();
                    loadActiveTab();
                } else {
          alert(' ስህተት: ' + (data.message || 'ክፍያውን መመዝገብ አልተቻለም'));
                }
            } catch (err) {
                alert('የሰርቨር ግንኙነት ችግር: ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerText = 'ክፍያውን መዝግብ';
            }
        }

        // ==========================================
        // 8b. EMPLOYEE DOSSIER: MONTHLY PAYROLL & WORK HISTORY MODAL
        // ==========================================
        let currentEmpModalTab = 'payroll';
        async function openEmployeeHistoryModal(employeeId) {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-4xl w-full p-6 shadow-2xl text-center space-y-4 my-auto">
                        <div class="animate-spin w-10 h-10 border-3 border-cyan-500 border-t-transparent rounded-full mx-auto"></div>
                        <p class="text-sm font-bold text-white">የሰራተኛውን የስራ እና የክፍያ ማህደር በመጫን ላይ...</p>
                    </div>
                </div>
            `;
            lucide.createIcons();

            try {
                const res = await apiFetch(`/api/employees/${employeeId}/history`);
                const data = await res.json();

                if (!res.ok || !data.success) {
                    alert('ስህተት፡ ' + (data.message || 'የሰራተኛውን መረጃ ማግኘት አልተቻለም'));
                    closeModal();
                    return;
                }

                const emp = data.employee;
                const payments = data.payments || [];
                const workHistory = data.work_history || [];
                const teams = data.teams || [];
                const totalPaid = data.total_paid || 0;
                const completedJobs = data.completed_jobs_count || 0;
                const activeJobs = data.active_jobs_count || 0;

                const roleLabels = {
                    owner: 'ዋና ስራ አስኪያጅ',
                    reception: 'ሪሴፕሽን & ሽያጭ',
                    cleaner: 'የፅዳት ባለሙያ',
                    sales: 'የውጭ ሽያጭ',
                    finance: 'ፋይናንስ'
                };

                window._currentEmployeeModalData = data;
                renderEmployeeHistoryModalDom(data);
            } catch (err) {
                alert('የኔትወርክ ስህተት፡ ' + err.message);
                closeModal();
            }
        }

        function renderEmployeeHistoryModalDom(data) {
            const container = document.getElementById('generic-modal-container');
            const emp = data.employee;
            const payments = data.payments || [];
            const workHistory = data.work_history || [];
            const teams = data.teams || [];
            const totalPaid = data.total_paid || 0;
            const completedJobs = data.completed_jobs_count || 0;
            const activeJobs = data.active_jobs_count || 0;

            const roleLabels = {
                owner: 'ዋና ስራ አስኪያጅ',
                reception: 'ሪሴፕሽን & ሽያጭ',
                cleaner: 'የፅዳት ባለሙያ',
                sales: 'የውጭ ሽያጭ',
                finance: 'ፋይናንስ'
            };

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-4xl w-full p-5 sm:p-6 shadow-2xl space-y-5 my-auto max-h-[92vh] flex flex-col">
                        <!-- Header with Employee Profile -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800 shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-cyan-600 to-blue-600 text-white font-black text-lg flex items-center justify-center shadow-lg shadow-cyan-950/60">
                                    ${(emp.name || 'S').slice(0, 2).toUpperCase()}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-lg font-black text-white">${emp.name}</h3>
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-cyan-950 text-cyan-400 border border-cyan-800">
                                            ${roleLabels[emp.role] || emp.role}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-400 mt-0.5 font-mono">
                                        <span> ስልክ: <strong class="text-slate-200">${emp.phone || '—'}</strong></span>
                                        <span>•</span>
                                        <span> ስም: <strong class="text-cyan-300">@${emp.username || '—'}</strong></span>
                                        ${teams.length > 0 ? `<span>•</span><span> ቡድን: <strong class="text-emerald-400">${teams.map(t => t.name).join(', ')}</strong></span>` : ''}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 self-end sm:self-center">
                                <button onclick="openPayrollModal(${emp.id}, '${emp.name.replace(/'/g, "\\'")}')" class="px-3 py-1.5 rounded-xl bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-500 hover:to-yellow-500 text-white font-bold text-xs flex items-center gap-1.5 shadow-md cursor-pointer transition">
                                    <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
                                    <span>+ ደመወዝ ክፈል</span>
                                </button>
                                <button onclick="closeModal()" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center text-lg font-bold transition cursor-pointer">&times;</button>
                            </div>
                        </div>

                        <!-- Top Metric Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 shrink-0">
                            <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-amber-500/30 space-y-0.5">
                                <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider block">ጠቅላላ የተከፈለ ደመወዝ</span>
                                <div class="text-lg sm:text-xl font-black text-white font-mono">${totalPaid.toLocaleString()} <span class="text-xs text-amber-400 font-normal">ETB</span></div>
                                <span class="text-[10px] text-slate-400 block">${payments.length} ጊዜ ተከፍሏል</span>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-emerald-500/30 space-y-0.5">
                                <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">ያጠናቀቃቸው ስራዎች</span>
                                <div class="text-lg sm:text-xl font-black text-emerald-400 font-mono">${completedJobs} <span class="text-xs text-slate-400 font-normal">ስራዎች</span></div>
                                <span class="text-[10px] text-slate-400 block">የተጠናቀቁ ትዕዛዞች</span>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-blue-500/30 space-y-0.5">
                                <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider block">ንቁ / በመሰራት ላይ</span>
                                <div class="text-lg sm:text-xl font-black text-blue-400 font-mono">${activeJobs} <span class="text-xs text-slate-400 font-normal">ስራዎች</span></div>
                                <span class="text-[10px] text-slate-400 block">በሂደት ላይ ያሉ</span>
                            </div>
                            <div class="p-3.5 rounded-2xl bg-slate-950/70 border border-cyan-500/30 space-y-0.5">
                                <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider block">የስራ ሁኔታ</span>
                                <div class="text-base sm:text-lg font-black text-cyan-300 font-mono flex items-center gap-1.5 pt-0.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span>${emp.is_active ? 'ንቁ ሰራተኛ' : 'የቦዘነ'}</span>
                                </div>
                                <span class="text-[10px] text-slate-400 block">ከ ${emp.created_at || '—'} ጀምሮ</span>
                            </div>
                        </div>

                        <!-- Subtab Navigation -->
                        <div class="flex items-center gap-2 border-b border-slate-800 pb-2 shrink-0">
                            <button type="button" onclick="switchEmpModalTab('payroll')" id="emp-tab-btn-payroll" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 ${currentEmpModalTab === 'payroll' ? 'bg-amber-600 text-white shadow-lg shadow-amber-950/50' : 'bg-slate-800 text-slate-300 hover:text-white'} cursor-pointer">
                                <i data-lucide="banknote" class="w-3.5 h-3.5"></i>
                                <span>የወርሃዊ ደመወዝ እና ክፍያ ታሪክ (${payments.length})</span>
                            </button>
                            <button type="button" onclick="switchEmpModalTab('work')" id="emp-tab-btn-work" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 ${currentEmpModalTab === 'work' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-950/50' : 'bg-slate-800 text-slate-300 hover:text-white'} cursor-pointer">
                                <i data-lucide="briefcase" class="w-3.5 h-3.5"></i>
                                <span>የስራ ታሪክ እና ስምሪት (${workHistory.length})</span>
                            </button>
                        </div>

                        <!-- Tab Content Body (Scrollable) -->
                        <div class="overflow-y-auto flex-1 pr-1 space-y-3" id="emp-modal-tab-body">
                            ${currentEmpModalTab === 'payroll' ? renderEmpPayrollTabHtml(payments, emp) : renderEmpWorkTabHtml(workHistory, emp)}
                        </div>

                        <!-- Footer -->
                        <div class="pt-3 border-t border-slate-800 flex justify-between items-center text-xs shrink-0">
                            <span class="text-slate-400 font-mono">Employee ID: #${emp.id}</span>
                            <button type="button" onclick="closeModal()" class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl cursor-pointer">ዝጋ (Close)</button>
                        </div>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        function switchEmpModalTab(tab) {
            currentEmpModalTab = tab;
            if (window._currentEmployeeModalData) {
                renderEmployeeHistoryModalDom(window._currentEmployeeModalData);
            }
        }

        function renderEmpPayrollTabHtml(payments, emp) {
            if (!payments || payments.length === 0) {
                return `
                    <div class="p-8 text-center text-slate-500 rounded-2xl bg-slate-950/50 border border-slate-800 space-y-2">
                        <i data-lucide="banknote" class="w-8 h-8 mx-auto text-slate-600"></i>
                        <p class="font-bold text-white text-xs">እስካሁን ለዚህ ሰራተኛ የተመዘገበ የደመወዝ ክፍያ የለም።</p>
                        <p class="text-[11px] text-slate-400">ከላይ ያለውን "+ ደመወዝ ክፈል" በመጫን አዲስ ክፍያ መመዝገብ ይችላሉ።</p>
                    </div>
                `;
            }

            return `
                <div class="overflow-x-auto rounded-2xl border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/90 text-slate-400 border-b border-slate-800 text-[11px] uppercase tracking-wider sticky top-0">
                            <tr>
                                <th class="p-3">ክፍያው የሚመለከተው ወር</th>
                                <th class="p-3">የተከፈለበት ቀን</th>
                                <th class="p-3">መጠን (ETB)</th>
                                <th class="p-3">ደረሰኝ / ማመሳከሪያ</th>
                                <th class="p-3">ዝርዝር ማብራሪያ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 bg-slate-900/40">
                            ${payments.map(p => `
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="p-3 font-bold text-amber-300">
                                        <div class="flex items-center gap-1.5">
                                            <i data-lucide="calendar" class="w-3.5 h-3.5 text-amber-400"></i>
                                            <span>${p.month || 'ያልተገለጸ ወር'}</span>
                                        </div>
                                    </td>
                                    <td class="p-3 font-mono text-slate-300 whitespace-nowrap">${p.date || '—'}</td>
                                    <td class="p-3 font-mono font-black text-white text-sm whitespace-nowrap">
                                        ${p.amount.toLocaleString()} <span class="text-[10px] text-amber-400 font-normal">ETB</span>
                                    </td>
                                    <td class="p-3 font-mono text-cyan-400 text-[11px] whitespace-nowrap">${p.reference_number || p.expense_number || '—'}</td>
                                    <td class="p-3 text-slate-300 text-[11px] max-w-xs">${p.description || '—'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        function renderEmpWorkTabHtml(workHistory, emp) {
            if (!workHistory || workHistory.length === 0) {
                return `
                    <div class="p-8 text-center text-slate-500 rounded-2xl bg-slate-950/50 border border-slate-800 space-y-2">
                        <i data-lucide="briefcase" class="w-8 h-8 mx-auto text-slate-600"></i>
                        <p class="font-bold text-white text-xs">ለዚህ ሰራተኛ እስካሁን የተመዘገበ የስራ ስምሪት የለም።</p>
                        <p class="text-[11px] text-slate-400">ሰራተኛው በፅዳት ቡድን ተመድቦ ስራ ሲሰራ ታሪኩ እዚህ Sync ይሆናል።</p>
                    </div>
                `;
            }

            const statusBadges = {
                completed: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
                in_progress: 'bg-cyan-500/20 text-cyan-400 border-cyan-500/30',
                confirmed: 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                cancelled: 'bg-rose-500/20 text-rose-400 border-rose-500/30'
            };

            return `
                <div class="overflow-x-auto rounded-2xl border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/90 text-slate-400 border-b border-slate-800 text-[11px] uppercase tracking-wider sticky top-0">
                            <tr>
                                <th class="p-3">የትዕዛዝ ቁጥር</th>
                                <th class="p-3">የደንበኛ ስም & ስልክ</th>
                                <th class="p-3">ቦታ / ክፍለ ከተማ</th>
                                <th class="p-3">የአገልግሎት አይነት</th>
                                <th class="p-3">የቀጠሮ ቀን</th>
                                <th class="p-3 text-center">ሁኔታ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 bg-slate-900/40">
                            ${workHistory.map(w => `
                                <tr class="hover:bg-slate-800/30 transition">
                                    <td class="p-3 font-mono font-bold text-cyan-400 whitespace-nowrap">#${w.order_number}</td>
                                    <td class="p-3 whitespace-nowrap">
                                        <div class="font-bold text-white">${w.customer_name}</div>
                                        <div class="text-[11px] font-mono text-slate-400">${w.customer_phone || '—'}</div>
                                    </td>
                                    <td class="p-3 text-slate-300 whitespace-nowrap">${w.subcity || '—'}</td>
                                    <td class="p-3 text-slate-300 text-[11px] max-w-xs truncate">${w.services || 'የፅዳት አገልግሎት'}</td>
                                    <td class="p-3 font-mono text-slate-400 whitespace-nowrap">${w.appointment_date || '—'}</td>
                                    <td class="p-3 text-center whitespace-nowrap">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase border ${statusBadges[w.status] || 'bg-slate-800 text-slate-400 border-slate-700'}">
                                            ${w.status === 'completed' ? 'የተጠናቀቀ' : (w.status === 'in_progress' ? 'በሂደት ላይ' : w.status)}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
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
                const followups = Array.isArray(followupsData) ? followupsData : (followupsData.data || []);
                const complaints = Array.isArray(complaintsData) ? complaintsData : (complaintsData.data || []);
                const feedbacks = Array.isArray(feedbacksData) ? feedbacksData : (feedbacksData.data || []);

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
                            <!-- Comment Waiting (Paid Orders) Column -->
                            <div class="p-5 rounded-3xl bg-slate-900 border border-amber-500/30 space-y-4">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                                    <h3 class="text-sm font-black text-white flex items-center gap-2">
                                        <i data-lucide="clock" class="w-4 h-4 text-amber-400"></i>
                                        <span>Comment Waiting (Paid Orders) (${followups.length})</span>
                                    </h3>
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">ክፍት አስተያየቶች</span>
                                </div>
                                <div class="space-y-3">
                                    ${followups.length === 0 ? '<p class="text-xs text-slate-500 text-center py-6">ምንም የሚጠበቅ የክትትል ጥሪ የለም! ሁሉም ደንበኞች አስተያየታቸውን ሰጥተዋል።</p>' : ''}
                                    ${followups.map(f => `
                                        <div class="p-4 rounded-2xl bg-slate-800/90 border border-slate-700/80 hover:border-amber-500/40 transition-all space-y-2.5">
                                            <div class="flex items-start justify-between gap-2">
                                                <div>
                                                    <div class="flex items-center gap-1.5">
                                                        <h4 class="text-xs font-black text-white">${escapeHtml(f.customer_name || f.customer?.full_name || '')}</h4>
                                                        <span class="text-[9px] px-1.5 py-0.2 rounded-full font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">PAID</span>
                                                    </div>
                                                    <p class="text-[11px] text-slate-300 font-semibold mt-0.5">${escapeHtml(f.customer_phone || f.customer?.phone || '')}</p>
                                                    <p class="text-[10px] text-slate-400 mt-0.5">Order #${escapeHtml(f.order_number || f.order?.order_number || '')} | ${Number(f.order_total || f.order?.total || 0).toLocaleString()} ETB</p>
                                                </div>
                                                <span class="text-[10px] text-slate-400 font-bold bg-slate-900/80 px-2 py-0.5 rounded-lg border border-slate-700">${escapeHtml(f.eth_due_date || f.due_date || 'ዛሬ')}</span>
                                            </div>

                                            <div class="pt-2 border-t border-slate-700/60 flex items-center justify-between gap-1.5">
                                                <button data-phone="${escapeHtml(f.customer_phone || f.customer?.phone || '')}" data-name="${escapeHtml(f.customer_name || f.customer?.full_name || '')}" onclick="openDirectSmsModal(this.dataset.phone, this.dataset.name)" class="px-2.5 py-1.5 rounded-xl bg-cyan-950/80 hover:bg-cyan-900 border border-cyan-500/30 text-cyan-300 text-[11px] font-bold flex items-center gap-1 cursor-pointer">
                                                    <i data-lucide="message-square" class="w-3 h-3"></i>
                                                    <span>SMS</span>
                                                </button>
                                                <button data-id="${f.id}" data-name="${escapeHtml(f.customer_name || f.customer?.full_name || '')}" data-phone="${escapeHtml(f.customer_phone || f.customer?.phone || '')}" data-order="${escapeHtml(f.order_number || f.order?.order_number || '')}" onclick="openRecordCommentAndRatingModal(this.dataset.id, this.dataset.name, this.dataset.phone, this.dataset.order)" class="flex-1 px-3 py-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black text-xs shadow-md flex items-center justify-center gap-1.5 transition-all cursor-pointer">
                                                    <i data-lucide="star" class="w-3.5 h-3.5 fill-slate-950"></i>
                                                    <span>Record Comment & Rating (1–5 ★)</span>
                                                </button>
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
                                                    <h4 class="text-xs font-bold text-white">${escapeHtml(c.customer?.full_name || '')}</h4>
                                                    <span class="text-[9px] px-1.5 py-0.5 rounded-full uppercase font-bold ${c.status === 'resolved' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-red-500/20 text-red-400'}">${escapeHtml(c.status || '')}</span>
                                                </div>
                                                <p class="text-[10px] text-red-300 font-semibold mt-0.5">${escapeHtml(c.category || '')}: ${escapeHtml(c.description || '')}</p>
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                        <button data-phone="${escapeHtml(c.customer?.phone || '')}" data-name="${escapeHtml(c.customer?.full_name || '')}" onclick="openDirectSmsModal(this.dataset.phone, this.dataset.name)" class="px-2 py-1 rounded-lg bg-cyan-950/80 hover:bg-cyan-900 border border-cyan-500/30 text-cyan-300 text-[11px] font-bold"> SMS</button>
                                                ${c.status !== 'resolved' ? `
                                                    <button onclick="openResolveComplaintModal(${c.id})" class="px-2.5 py-1 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-[11px] font-bold">መፍትሄ</button>
                        ` : '<span class="text-[11px] text-emerald-400 font-bold"></span>'}
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
                                                    <span class="text-xs font-bold text-white">${escapeHtml(fb.customer?.full_name || 'ውድ ደንበኛ')}</span>
                                                    <span class="text-[10px] text-amber-400 ml-1.5 font-bold">${'⭐'.repeat(Math.max(1, Math.min(5, fb.rating || 5)))}</span>
                                                </div>
                                                <span class="text-[9px] px-2 py-0.5 rounded-full font-bold ${fb.is_approved ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-400'}">
                                                    ${fb.is_approved ? 'በድረ-ገጽ ይታያል' : 'ተደብቋል'}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-slate-300 italic">"${fb.comment || 'ምንም የጽሁፍ አስተያየት የለም'}"</p>
                                            <div class="pt-2 border-t border-slate-700/60 flex justify-between items-center text-[10px]">
                                                <span class="text-slate-400">${fb.source || 'web'}</span>
                                                <button onclick="toggleFeedbackApproval(${fb.id}, ${!fb.is_approved})" class="px-2.5 py-1 rounded-lg ${fb.is_approved ? 'bg-red-950/70 hover:bg-red-900 border border-red-500/30 text-red-300' : 'bg-emerald-950/80 hover:bg-emerald-900 border border-emerald-500/40 text-emerald-300'} font-bold transition-all">
                          ${fb.is_approved ? ' ከድረ-ገጽ ደብቅ' : ' በድረ-ገጽ እንዲታይ ፍቀድ'}
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
                renderErrorCard(container, 'የደንበኞች እንክብካቤ መረጃዎችን መጫን አልተቻለም፡ ' + err.message, 'renderCustomerCareModule(document.getElementById(\'app-viewport\'))');
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
          alert(' ' + (data.message || 'ተዘምኗል!'));
                    loadActiveTab();
                } else {
          alert(' ስህተት: ' + (data.message || 'ማዘመን አልተቻለም'));
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
          alert(' ቅሬታው ተመዝግቧል! ወደ ቅሬታዎች ዝርዝር ታክሏል።');
                    closeModal();
                    loadActiveTab();
                } else {
          alert(' ስህተት: ' + (data.message || 'ቅሬታውን መመዝገብ አልተቻለም'));
                }
            } catch (err) {
                alert('የግንኙነት ስህተት: ' + err.message);
            }
        }

        // ==========================================
        // 10. TELEGRAM BROADCAST CENTER & MARKETING
        // ==========================================
        let currentMarketingTab = 'telegram_broadcast';
        let broadcastRecipientsList = [];
        let excludedBroadcastIds = new Set();
        let selectedContactIds = new Set();
        let broadcastSearchQuery = '';
        let currentRecipientFilter = 'all';
        let smsProgressInterval = null;

        async function renderCampaignsModule(container) {
            try {
                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <!-- Header & Tab Navigation -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl sm:text-3xl font-black text-white">የደንበኞች መልእክት እና የስልክ አድራሻዎች ማዕከል (ወሰን አልባ / Unlimited ∞)</h2>
                                <p class="text-xs text-slate-400 mt-1">በቀጥታ SMS፣ በቴሌግራም ቦት እና በደንበኞች ስልክ ዝርዝር የተደገፈ</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="switchMarketingTab('telegram_broadcast')" id="tab-btn-broadcast" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer ${currentMarketingTab === 'telegram_broadcast' ? 'bg-cyan-600 text-white shadow-lg' : 'bg-slate-800 text-slate-300 hover:text-white'}">
                                    <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                    <span>የመልእክት መላኪያ እና የደንበኞች ስልክ ዝርዝር</span>
                                </button>
                                <button type="button" onclick="switchMarketingTab('campaign_history')" id="tab-btn-history" class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer ${currentMarketingTab === 'campaign_history' ? 'bg-cyan-600 text-white shadow-lg' : 'bg-slate-800 text-slate-300 hover:text-white'}">
                                    <i data-lucide="history" class="w-3.5 h-3.5"></i>
                                    <span>የቀደሙ ዘመቻዎች ታሪክ</span>
                                </button>
                            </div>
                        </div>

                        <!-- Tab View Content -->
                        <div id="marketing-subtab-container" class="space-y-6">
                            <div class="p-8 text-center text-slate-400">
                                <div class="animate-spin w-8 h-8 border-2 border-cyan-500 border-t-transparent rounded-full mx-auto mb-2"></div>
                                <span>የደንበኞችን ስልክ ዝርዝር በማዘጋጀት ላይ...</span>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();

                if (currentMarketingTab === 'telegram_broadcast') {
                    await loadAndRenderBroadcastCenter();
                } else {
                    await loadAndRenderCampaignHistory();
                }
            } catch (err) {
                renderErrorCard(container, 'የማስታወቂያ ዘመቻዎች መረጃ መጫን አልተቻለም፡ ' + err.message, 'renderCampaignsModule(document.getElementById(\'app-viewport\'))');
            }
        }

        function switchMarketingTab(tab) {
            currentMarketingTab = tab;
            if (smsProgressInterval) {
                clearInterval(smsProgressInterval);
                smsProgressInterval = null;
            }
            const btnBc = document.getElementById('tab-btn-broadcast');
            const btnHist = document.getElementById('tab-btn-history');
            if (btnBc && btnHist) {
                if (tab === 'telegram_broadcast') {
                    btnBc.className = 'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer bg-cyan-600 text-white shadow-lg';
                    btnHist.className = 'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer bg-slate-800 text-slate-300 hover:text-white';
                } else {
                    btnHist.className = 'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer bg-cyan-600 text-white shadow-lg';
                    btnBc.className = 'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer bg-slate-800 text-slate-300 hover:text-white';
                }
            }
            const subContainer = document.getElementById('marketing-subtab-container');
            if (subContainer) {
                subContainer.innerHTML = `
                    <div class="p-8 text-center text-slate-400">
                        <div class="animate-spin w-8 h-8 border-2 border-cyan-500 border-t-transparent rounded-full mx-auto mb-2"></div>
                        <span>መረጃዎችን በማዘጋጀት ላይ...</span>
                    </div>
                `;
                if (tab === 'telegram_broadcast') {
                    loadAndRenderBroadcastCenter();
                } else {
                    loadAndRenderCampaignHistory();
                }
            }
        }

        async function loadAndRenderBroadcastCenter() {
            const subContainer = document.getElementById('marketing-subtab-container');
            if (!subContainer) return;

            try {
                const res = await apiFetch('/api/telegram-broadcast/recipients');
                const data = await res.json();
                broadcastRecipientsList = data.users || [];

                excludedBroadcastIds.clear();

                renderBroadcastCenterView(subContainer, data);
            } catch (e) {
                subContainer.innerHTML = `<div class="p-6 text-red-400">የደንበኞችን ስልክ ዝርዝር መጫን አልተቻለም: ${e.message}</div>`;
            }
        }

        function renderBroadcastCenterView(container, data) {
            const totalCount = data.total_count || broadcastRecipientsList.length;
            const activeCount = data.active_count || totalCount;
            const withPhoneCount = data.with_phone_count || broadcastRecipientsList.filter(u => u.phone).length;
            const withTgCount = data.with_telegram_count || broadcastRecipientsList.filter(u => u.telegram_id).length;
            const eligibleCount = totalCount;
            const botUsername = data.bot_username || 'meash_cleaning_solution_bot';

            container.innerHTML = `
                <!-- Statistics Cards: Clean 4 Metrics without Friend Exclusions -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 space-y-1 shadow-lg">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">ጠቅላላ የደንበኞች ስልክ ቁጥሮች</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-black text-white font-mono" id="stat-total-count">${totalCount.toLocaleString()}</span>
                            <span class="text-[10px] text-slate-500">ሰዎች</span>
                        </div>
                        <p class="text-[10px] text-cyan-400 font-semibold truncate">ወሰን አልባ (∞) አቅም</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900 border border-emerald-500/30 space-y-1 shadow-lg">
                        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider block">ለንግድ ንቁ የሆኑ</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-black text-emerald-400 font-mono" id="stat-eligible-count">${eligibleCount.toLocaleString()}</span>
                            <span class="text-[10px] text-slate-500">ደንበኞች</span>
                        </div>
                        <p class="text-[10px] text-slate-400 truncate">መልእክት የሚላክላቸው</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900 border border-blue-500/30 space-y-1 shadow-lg">
                        <span class="text-[10px] font-bold text-blue-400 uppercase tracking-wider block">ስልክ ያላቸው (SMS)</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-black text-blue-400 font-mono">${withPhoneCount.toLocaleString()}</span>
                            <span class="text-[10px] text-slate-500">ስልኮች</span>
                        </div>
                        <p class="text-[10px] text-slate-400 truncate">ለቀጥታ SMS ዝግጁ</p>
                    </div>

                    <div class="p-4 rounded-2xl bg-slate-900 border border-teal-500/30 space-y-1 shadow-lg">
                        <span class="text-[10px] font-bold text-teal-400 uppercase tracking-wider block">ቴሌግራም ያላቸው</span>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-2xl font-black text-teal-400 font-mono">${withTgCount.toLocaleString()}</span>
                            <span class="text-[10px] text-slate-500">ተጠቃሚዎች</span>
                        </div>
                        <p class="text-[10px] text-slate-400 truncate">ለቦት ስርጭት ዝግጁ</p>
                    </div>
                </div>

                <!-- MESSAGE COMPOSER: DUAL TELEGRAM + IPHONE AUTO-SMS -->
                <div class="p-6 rounded-3xl bg-slate-900 border border-cyan-500/40 shadow-2xl space-y-5">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-800 gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-2xl bg-cyan-600/20 text-cyan-400 flex items-center justify-center">
                                <i data-lucide="edit-3" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-white">የመልእክት መጻፊያ እና ስርጭት ማዕከል</h3>
                                <p class="text-xs text-slate-400">መልእክትዎን እዚህ ይጻፉ፤ በስልክ SMS ወይም በቴሌግራም ቦት ይላኩ</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <span class="text-slate-400">ይፋዊ ቦት፡</span>
                            <span class="font-mono font-bold text-cyan-400 bg-cyan-950/60 px-2.5 py-1 rounded-xl border border-cyan-500/30">@${botUsername}</span>
                        </div>
                    </div>

                    <!-- Quick Templates -->
                    <div class="space-y-1.5">
                        <label class="block text-slate-300 font-bold text-xs">ፈጣን ዝግጁ አብነቶች (Templates)</label>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" onclick="applyBroadcastTemplate('holiday')" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 cursor-pointer transition">
                                 የበዓላት ልዩ ቅናሽ
                            </button>
                            <button type="button" onclick="applyBroadcastTemplate('deep_clean')" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 cursor-pointer transition">
                                 የጥልቅ ጽዳት ጥሪ
                            </button>
                            <button type="button" onclick="applyBroadcastTemplate('sofa')" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 cursor-pointer transition">
                                 የሶፋ እና ምንጣፍ እጥበት
                            </button>
                            <button type="button" onclick="applyBroadcastTemplate('thanks')" class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold border border-slate-700 cursor-pointer transition">
                                 ምስጋና እና አዲስ ቅናሽ
                            </button>
                        </div>
                    </div>

                    <!-- Title & Message Textarea -->
                    <div class="space-y-3">
                        <div>
                            <label class="block text-slate-300 font-bold text-xs mb-1">የማስታወቂያው ርዕስ *</label>
                            <input type="text" id="inpage-bc-title" value="የሜሽ ክሊኒንግ ልዩ የበዓል 25% ቅናሽ!" placeholder="ለምሳሌ፡ የሜሽ ክሊኒንግ ልዩ የበዓል 25% ቅናሽ!" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white font-bold text-xs focus:outline-none focus:border-cyan-500">
                        </div>

                        <div>
                            <label class="block text-slate-300 font-bold text-xs mb-1">የመልእክቱ ሙሉ ዝርዝር (Message Body) *</label>
                            <textarea id="inpage-bc-message" rows="4" placeholder="የማስታወቂያውን መልእክት እዚህ ይጻፉ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white text-xs leading-relaxed focus:outline-none focus:border-cyan-500">ውድ {name}፤ የሜሽ ክሊኒንግ ልዩ የጥልቅ ጽዳት አገልግሎት ቅናሽ ተዘጋጅቶ ይጠብቆታል። አሁኑኑ በ 0970075509 ደውለው ቀጠሮዎን ያስይዙ!</textarea>
                            <p class="text-[11px] text-cyan-400 mt-1">ማስታወሻ፦ <code>{name}</code> ወይም <code>{ስም}</code> የሚለውን ተጠቅመው የእያንዳንዱን ደንበኛ ስም በሲስተሙ ማካተት ይችላሉ::</p>
                        </div>
                    </div>

                    <!-- Dual Send Action Buttons: iPhone Auto-SMS + Telegram Bot -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-800">
                        <div class="text-[11px] text-slate-400 space-y-0.5">
                            <p>• የሚላክላቸው ጠቅላላ፦ <strong class="text-emerald-400 font-mono font-bold" id="label-send-count">${eligibleCount.toLocaleString()}</strong> ደንበኞች</p>
                            <p>• በተመረጡ (checkbox)፦ <strong class="text-cyan-400 font-mono font-bold" id="label-selected-count">0</strong> ደንበኞች</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- 1. Phone SMS Button -->
                            <button type="button" id="btn-iphone-sms" onclick="openIphoneSmsModal()" class="px-6 py-3 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 hover:from-emerald-500 hover:to-teal-500 text-slate-950 font-black text-sm rounded-2xl shadow-xl shadow-emerald-500/20 flex items-center gap-2 cursor-pointer transition transform active:scale-95">
                                <span>በስልክ SMS ላክ</span>
                            </button>

                            <!-- 2. 1-Click Telegram Bot Broadcast Button -->
                            <button type="button" id="btn-inpage-send" onclick="submitInPageBroadcast()" class="px-6 py-3 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-black text-sm rounded-2xl shadow-xl flex items-center gap-2 cursor-pointer transition transform active:scale-95">
                                <i data-lucide="send" class="w-4 h-4"></i>
                                <span id="btn-inpage-send-text">በቴሌግራም ቦት ላክ</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- CONTACTS MANAGEMENT: FILTERING, BULK ACTIONS & TABLE -->
                <div class="p-5 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 shadow-xl">
                    <!-- Top Toolbar: Search & Bulk Action Buttons -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                        <div class="relative flex-1 max-w-md">
                            <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="text" id="broadcast-search-input" oninput="handleInstantBroadcastSearch(this.value)" placeholder="ደንበኛ በስም ወይም በስልክ ቁጥር ይፈልጉ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-10 pr-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500">
                        </div>

                        <!-- Bulk Actions Toolbar -->
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <button type="button" onclick="bulkActionContacts('delete', this)" class="px-3 py-1.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 font-bold flex items-center gap-1 cursor-pointer transition">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                <span>በቋሚነት ሰርዝ (Delete)</span>
                            </button>
                            <button type="button" onclick="exportContactsCsv()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold rounded-xl flex items-center gap-1 cursor-pointer transition border border-slate-700">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                <span>Export (.csv)</span>
                            </button>
                            <button type="button" onclick="syncBroadcastRecipients()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold rounded-xl flex items-center gap-1 cursor-pointer transition border border-slate-700">
                                <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                                <span>ዝርዝሩን አድስ (Sync)</span>
                            </button>
                            <button type="button" onclick="openBulkImportTelegramModal()" class="px-3 py-1.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow flex items-center gap-1 cursor-pointer transition">
                                <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                                <span>+ በጅምላ ጫን (JSON / CSV)</span>
                            </button>
                            <button type="button" onclick="openAddSingleTelegramModal()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold rounded-xl flex items-center gap-1 cursor-pointer transition border border-slate-700">
                                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i>
                                <span>+ አዲስ ስልክ / ደንበኛ</span>
                            </button>
                        </div>
                    </div>

                    <!-- Category Pills Filter: Filterable Tags -->
                    <div class="flex flex-wrap items-center gap-2 pt-1 border-t border-slate-800/80 text-xs">
                        <span class="text-slate-400 font-bold text-[11px] mr-1">አጣራ (Tags)፦</span>
                        <button type="button" onclick="filterBroadcastTable('all')" id="pill-filter-all" class="px-3 py-1 rounded-xl font-bold bg-cyan-600 text-white transition cursor-pointer">ሁሉም (${totalCount.toLocaleString()})</button>
                        <button type="button" onclick="filterBroadcastTable('normal_active')" id="pill-filter-normal_active" class="px-3 py-1 rounded-xl font-semibold bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer">Normal Active (${(data.tag_counts?.normal_active || 0).toLocaleString()})</button>
                        <button type="button" onclick="filterBroadcastTable('comment_waiting')" id="pill-filter-comment_waiting" class="px-3 py-1 rounded-xl font-semibold bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer">Comment Waiting (${(data.tag_counts?.comment_waiting || 0).toLocaleString()})</button>
                        <button type="button" onclick="filterBroadcastTable('imported_contacts')" id="pill-filter-imported_contacts" class="px-3 py-1 rounded-xl font-semibold bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer">Imported Contacts (${(data.tag_counts?.imported_contacts || 0).toLocaleString()})</button>
                        <button type="button" onclick="filterBroadcastTable('outdoor_sales')" id="pill-filter-outdoor_sales" class="px-3 py-1 rounded-xl font-semibold bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer">Outdoor Sales (${(data.tag_counts?.outdoor_sales || 0).toLocaleString()})</button>
                        <button type="button" onclick="filterBroadcastTable('with_phone')" id="pill-filter-phone" class="px-3 py-1 rounded-xl font-semibold bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer">ስልክ (SMS) (${withPhoneCount.toLocaleString()})</button>
                        <button type="button" onclick="filterBroadcastTable('with_tg')" id="pill-filter-tg" class="px-3 py-1 rounded-xl font-semibold bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer">ቴሌግራም (${withTgCount.toLocaleString()})</button>
                    </div>

                    <!-- Directory Table -->
                    <div class="overflow-x-auto max-h-[550px] overflow-y-auto rounded-2xl border border-slate-800">
                        <table class="w-full text-left text-xs text-slate-300" id="broadcast-directory-table">
                            <thead class="bg-slate-950/90 sticky top-0 z-10 text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="p-3 w-10 text-center">
                                        <input type="checkbox" id="broadcast-select-all-cb" onchange="toggleSelectAllBroadcast(this.checked)" class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-cyan-500 focus:ring-0 cursor-pointer">
                                    </th>
                                    <th class="p-3">የደንበኛው ስም</th>
                                    <th class="p-3">ስልክ ቁጥር</th>
                                    <th class="p-3">ቴሌግራም መለያ</th>
                                    <th class="p-3">ምንጭ (Source)</th>
                                    <th class="p-3 text-center">ሁኔታ</th>
                                    <th class="p-3 text-right">እርምጃ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 bg-slate-900/40" id="broadcast-table-body">
                                ${renderBroadcastTableRowsHtml(broadcastRecipientsList)}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        function renderBroadcastTableRowsHtml(usersList) {
            if (!usersList || usersList.length === 0) {
                return `
                    <tr>
                        <td colspan="7" class="p-8 text-center text-slate-500">
                            ምንም የተገኘ የደንበኛ ስልክ የለም። አዳዲስ ስልኮችን ከላይ ባለው "+ በጅምላ ጫን" ይጨምሩ።
                        </td>
                    </tr>
                `;
            }

            return usersList.map(u => {
                const isExcluded = excludedBroadcastIds.has(String(u.id));
                const isChecked = selectedContactIds.has(Number(u.id));
                const tagsStr = (u.tags || '').toLowerCase();
                const searchTokens = `${u.name || ''} ${u.username || ''} ${u.phone || ''} ${u.telegram_id || ''} ${u.source || ''} ${u.tags || ''}`.toLowerCase();
                const sourceBadge = formatRecipientSourceBadge(u.source, u.tags, u.lifecycle_stage);

                return `
                <tr id="bc-row-${u.id}" data-id="${u.id}" data-tokens="${encodeURIComponent(searchTokens)}" data-tags="${encodeURIComponent(tagsStr)}" data-lifecycle="${u.lifecycle_stage || ''}" data-excluded="${isExcluded ? '1' : '0'}" data-has-phone="${u.phone ? '1' : '0'}" data-has-tg="${u.telegram_id ? '1' : '0'}" class="hover:bg-slate-800/40 transition ${isExcluded ? 'opacity-45 bg-slate-950/40' : ''}">
                    <td class="p-3 text-center">
                        <input type="checkbox" id="bc-cb-${u.id}" ${isChecked ? 'checked' : ''} onchange="toggleSelectContactRow(${u.id}, this.checked)" class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-cyan-500 focus:ring-0 cursor-pointer">
                    </td>
                    <td class="p-3 font-bold text-white flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-cyan-600/20 text-cyan-400 flex items-center justify-center font-bold text-[11px] shrink-0">
                            ${(u.name || 'M')[0].toUpperCase()}
                        </div>
                        <span class="truncate max-w-[140px] sm:max-w-none">${u.name}</span>
                    </td>
                    <td class="p-3 font-mono text-slate-300 whitespace-nowrap">${u.phone || '<span class="text-slate-600">—</span>'}</td>
                    <td class="p-3 font-mono text-cyan-400 whitespace-nowrap">${u.username ? u.username : (u.telegram_id ? 'ID: ' + u.telegram_id : '<span class="text-slate-600">—</span>')}</td>
                    <td class="p-3 whitespace-nowrap">${sourceBadge}</td>
                    <td class="p-3 text-center whitespace-nowrap">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                            ንቁ / ዝግጁ
                        </span>
                    </td>
                    <td class="p-3 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            <button type="button" onclick="openDirectCustomerMessageModal('${u.phone || ''}', '${encodeURIComponent(u.name || '')}', '${u.telegram_id || ''}')" class="p-1 px-2 rounded-lg bg-teal-500/20 hover:bg-teal-500/30 text-teal-300 border border-teal-500/30 transition cursor-pointer text-[10px] font-bold flex items-center gap-1" title="1-ለ-1 መልእክት ላክ">
                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                <span>መልእክት</span>
                            </button>
                            <button type="button" onclick="openEditRecipientModal(${u.id})" class="p-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-cyan-400 transition cursor-pointer" title="አስተካክል (Edit)">
                                <i data-lucide="edit-2" class="w-3.5 h-3.5"></i>
                            </button>
                            <button type="button" onclick="deleteSingleRecipient(${u.id}, '${encodeURIComponent(u.name || '')}', this)" class="p-1 rounded-lg bg-slate-800 hover:bg-rose-900/40 text-rose-400 transition cursor-pointer" title="በቋሚነት ሰርዝ (Delete)">
                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                `;
            }).join('');
        }

        function formatRecipientSourceBadge(source, tags, stage) {
            const lowerTags = (tags || '').toLowerCase();
            if (lowerTags.includes('normal active') || stage === 'normal_active') {
                return '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Normal Active</span>';
            }
            if (lowerTags.includes('comment waiting') || stage === 'comment_waiting') {
                return '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">Comment Waiting</span>';
            }
            if (lowerTags.includes('outdoor') || source === 'outdoor_sales') {
                return '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">Outdoor Sales</span>';
            }
            if (lowerTags.includes('imported') || source === 'imported' || source === 'bulk_import') {
                return '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-slate-700/50 text-slate-300 border border-slate-600">Imported Contact</span>';
            }

            switch(source) {
                case 'website_customer':
                    return '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">ዌብሳይት ደንበኛ</span>';
                case 'bot_customer':
                    return '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-teal-500/20 text-teal-300 border border-teal-500/30">ቴሌግራም ቦት</span>';
                case 'miniapp_customer':
                    return '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">ሚኒ አፕ</span>';
                case 'phone_customer':
                    return '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">ስልክ ጥሪ</span>';
                case 'system_user':
                    return '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">የሲስተም አባል</span>';
                default:
                    return '<span class="px-2 py-0.5 rounded text-[9px] font-bold bg-slate-700/50 text-slate-300 border border-slate-600">Normal Active</span>';
            }
        }

        function handleInstantBroadcastSearch(val) {
            broadcastSearchQuery = (val || '').trim().toLowerCase();
            applyTableFiltersDom();
        }

        function filterBroadcastTable(type) {
            currentRecipientFilter = type;
            ['all', 'normal_active', 'comment_waiting', 'imported_contacts', 'outdoor_sales', 'phone', 'tg'].forEach(k => {
                const el = document.getElementById(`pill-filter-${k}`);
                if (el) {
                    el.className = 'px-3 py-1 rounded-xl font-semibold bg-slate-800 text-slate-300 hover:text-white transition cursor-pointer';
                }
            });
            const activeKey = type === 'with_phone' ? 'phone' : (type === 'with_tg' ? 'tg' : type);
            const activeEl = document.getElementById(`pill-filter-${activeKey}`);
            if (activeEl) {
                activeEl.className = 'px-3 py-1 rounded-xl font-bold bg-cyan-600 text-white transition cursor-pointer';
            }
            applyTableFiltersDom();
        }

        function applyTableFiltersDom() {
            const rows = document.querySelectorAll('#broadcast-table-body tr[data-tokens]');
            rows.forEach(r => {
                const tokens = decodeURIComponent(r.getAttribute('data-tokens') || '');
                const tags = decodeURIComponent(r.getAttribute('data-tags') || '');
                const lifecycle = r.getAttribute('data-lifecycle') || '';
                const hasPhone = r.getAttribute('data-has-phone') === '1';
                const hasTg = r.getAttribute('data-has-tg') === '1';

                const matchesSearch = !broadcastSearchQuery || tokens.includes(broadcastSearchQuery);
                let matchesCategory = true;

                if (currentRecipientFilter === 'normal_active') {
                    matchesCategory = tags.includes('normal active') || lifecycle === 'normal_active';
                } else if (currentRecipientFilter === 'comment_waiting') {
                    matchesCategory = tags.includes('comment waiting') || lifecycle === 'comment_waiting';
                } else if (currentRecipientFilter === 'imported_contacts') {
                    matchesCategory = tags.includes('imported') || tokens.includes('imported') || tokens.includes('system_user');
                } else if (currentRecipientFilter === 'outdoor_sales') {
                    matchesCategory = tags.includes('outdoor') || tokens.includes('outdoor_sales');
                } else if (currentRecipientFilter === 'with_phone') {
                    matchesCategory = hasPhone;
                } else if (currentRecipientFilter === 'with_tg') {
                    matchesCategory = hasTg;
                }

                r.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
            });
        }

        function toggleSelectContactRow(id, isChecked) {
            if (isChecked) {
                selectedContactIds.add(id);
            } else {
                selectedContactIds.delete(id);
            }
            updateBroadcastCountersDom();
        }

        function toggleSelectAllBroadcast(selectAll) {
            selectedContactIds.clear();
            const visibleRows = document.querySelectorAll('#broadcast-table-body tr[data-tokens]');
            visibleRows.forEach(r => {
                if (r.style.display !== 'none') {
                    const id = Number(r.getAttribute('data-id'));
                    if (id) {
                        if (selectAll) selectedContactIds.add(id);
                        const cb = document.getElementById(`bc-cb-${id}`);
                        if (cb) cb.checked = selectAll;
                    }
                }
            });
            updateBroadcastCountersDom();
        }

        // Toggle friend exclusion single contact
        async function toggleFriendExclusion(id, shouldExclude) {
            try {
                const res = await apiFetch(`/api/telegram-broadcast/recipients/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify({ is_excluded: shouldExclude })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    if (shouldExclude) {
                        excludedBroadcastIds.add(String(id));
                    } else {
                        excludedBroadcastIds.delete(String(id));
                    }
                    const row = document.getElementById(`bc-row-${id}`);
                    const badge = document.getElementById(`bc-badge-${id}`);
                    if (row) {
                        row.setAttribute('data-excluded', shouldExclude ? '1' : '0');
                        if (shouldExclude) row.classList.add('opacity-45', 'bg-slate-950/40');
                        else row.classList.remove('opacity-45', 'bg-slate-950/40');
                    }
                    if (badge) {
                        badge.innerHTML = shouldExclude
                            ? `<button type="button" onclick="toggleFriendExclusion(${id}, false)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 cursor-pointer transition" title="ጠቅ በማድረግ ወደ ስርጭት ይመልሱ">ጓደኛ ተገልሏል</button>`
                            : `<button type="button" onclick="toggleFriendExclusion(${id}, true)" class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 border border-emerald-500/30 cursor-pointer transition" title="ጠቅ በማድረግ ጓደኛን ያግልሉ">ይላካል</button>`;
                    }
                    updateBroadcastCountersDom();
                }
            } catch (err) {
                alert('ስህተት፡ ' + err.message);
            }
        }

        // Two-tap arming state for broadcast deletes (same pattern as the
        // Photos tab). No native confirm() — automated browsers silently
        // dismiss those dialogs, so delete never ran.
        window._broadcastDeleteArmed = window._broadcastDeleteArmed || {};

        // Bulk Action: Exclude, Include, or Delete selected contacts.
        // First tap arms the button ("are you sure? tap again"), second tap
        // within 4 seconds executes; the arm auto-expires after 4 seconds.
        async function bulkActionContacts(action, btn) {
            const ids = Array.from(selectedContactIds);
            if (ids.length === 0) {
                alert('እባክዎ መጀመሪያ ከሰንጠረዡ ውስጥ ደንበኞችን በቼክቦክስ ይምረጡ!');
                return;
            }

            const actionAm = action === 'delete' ? 'በቋሚነት ይሰረዙ' : (action === 'exclude' ? 'እንደ ጓደኛ/ቤተሰብ ከስርጭት ይገለሉ' : 'ወደ ስርጭት ዝርዝር ይመለሱ');
            const armKey = 'bulk-' + action;
            const span = btn ? btn.querySelector('span') : null;
            const origText = span ? span.innerText : '';
            if (!window._broadcastDeleteArmed[armKey]) {
                window._broadcastDeleteArmed[armKey] = true;
                if (btn) {
                    btn.classList.add('bg-red-600', 'text-white');
                    if (span) span.innerText = 'እርግጠኛ ነህ? እንደገና ጠቅ አድርግ';
                }
                setTimeout(() => {
                    window._broadcastDeleteArmed[armKey] = false;
                    if (btn && btn.isConnected) {
                        btn.classList.remove('bg-red-600', 'text-white');
                        if (span) span.innerText = origText;
                    }
                }, 4000);
                return;
            }
            window._broadcastDeleteArmed[armKey] = false;

            try {
                const res = await apiFetch('/api/telegram-broadcast/recipients/bulk-action', {
                    method: 'POST',
                    body: JSON.stringify({ action: action, ids: ids })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert(' ' + data.message);
                    selectedContactIds.clear();
                    await loadAndRenderBroadcastCenter();
                } else {
                    alert('ስህተት፡ ' + (data.message || 'እርምጃውን ማጠናቀቅ አልተቻለም'));
                }
            } catch (err) {
                alert('የኔትወርክ ስህተት፡ ' + err.message);
            } finally {
                if (btn && btn.isConnected) {
                    btn.classList.remove('bg-red-600', 'text-white');
                    if (span) span.innerText = origText;
                }
            }
        }

        // Single contact delete — two-tap confirmation (same pattern as the
        // Photos tab): first tap arms the button (red + prompt), second tap
        // within 4 seconds executes. No native confirm() — automated
        // browsers silently dismiss those, so delete never ran.
        async function deleteSingleRecipient(id, encodedName, btn) {
            const name = decodeURIComponent(encodedName || 'ደንበኛ');
            if (!window._broadcastDeleteArmed[id]) {
                window._broadcastDeleteArmed[id] = true;
                if (btn) {
                    btn.classList.add('bg-red-600', 'text-white');
                    btn.title = 'እርግጠኛ ነህ? እንደገና ጠቅ አድርግ';
                }
                setTimeout(() => {
                    window._broadcastDeleteArmed[id] = false;
                    if (btn && btn.isConnected) {
                        btn.classList.remove('bg-red-600', 'text-white');
                        btn.title = 'በቋሚነት ሰርዝ (Delete)';
                    }
                }, 4000);
                return;
            }
            window._broadcastDeleteArmed[id] = false;

            try {
                const res = await apiFetch(`/api/telegram-broadcast/recipients/${id}`, { method: 'DELETE' });
                const data = await res.json();
                if (res.ok && data.success) {
                    const row = document.getElementById(`bc-row-${id}`);
                    if (row) row.remove();
                    excludedBroadcastIds.delete(String(id));
                    selectedContactIds.delete(Number(id));
                    broadcastRecipientsList = broadcastRecipientsList.filter(u => u.id != id);
                    updateBroadcastCountersDom();
                } else {
                    alert('ስህተት፡ ' + (data.message || 'መሰረዝ አልተቻለም'));
                }
            } catch (err) {
                alert('ስህተት፡ ' + err.message);
            }
        }

        // Export contacts to CSV (auth:sanctum route — window.open sends no
        // Authorization header, so fetch with the token and download a blob).
        async function exportContactsCsv() {
            const token = await getAuthToken();
            const res = await fetch('/api/telegram-broadcast/export?format=csv', {
                headers: token
                    ? { 'Authorization': `Bearer ${token}`, 'Accept': 'text/csv' }
                    : { 'Accept': 'text/csv' },
            });
            if (!res.ok) {
                alert('Export አልተቻለም (' + res.status + ')። እባክዎ እንደገና ይግቡ።');
                return;
            }
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'meash_contacts_' + new Date().toISOString().slice(0, 10).replace(/-/g, '_') + '.csv';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(url), 5000);
        }

        // Explicit recipient sync (GET /recipients no longer auto-syncs —
        // a plain load must never write). Press once after deploy.
        async function syncBroadcastRecipients() {
            const res = await apiFetch('/api/telegram-broadcast/recipients?sync=1');
            const data = await res.json();
            if (res.ok) {
                broadcastRecipientsList = data.users || [];
                renderBroadcastCenterView(document.getElementById('marketing-subtab-container'), data);
                alert('የደንበኞች ዝርዝር ከሲስተሙ ተመዝግቧል / ተዘምኗል!');
            } else {
                alert('ስህተት፡ ' + (data.message || 'Sync አልተቻለም'));
            }
        }

        // Update counters without re-rendering page
        function updateBroadcastCountersDom() {
            const totalCount = broadcastRecipientsList.length;
            const excludedCount = excludedBroadcastIds.size;
            const eligibleCount = Math.max(0, totalCount - excludedCount);

            const elEligible = document.getElementById('stat-eligible-count');
            const elExcluded = document.getElementById('stat-excluded-count');
            const elLabelSend = document.getElementById('label-send-count');
            const elLabelExcluded = document.getElementById('label-excluded-count');
            const elSelected = document.getElementById('label-selected-count');

            if (elEligible) elEligible.textContent = eligibleCount.toLocaleString();
            if (elExcluded) elExcluded.textContent = excludedCount.toLocaleString();
            if (elSelected) elSelected.textContent = selectedContactIds.size.toLocaleString();
            if (elLabelSend) elLabelSend.textContent = eligibleCount.toLocaleString();
            if (elLabelExcluded) elLabelExcluded.textContent = excludedCount.toLocaleString();
        }

        // Apply templates into in-page message writer
        function applyBroadcastTemplate(type) {
            const titleInput = document.getElementById('inpage-bc-title');
            const msgInput = document.getElementById('inpage-bc-message');
            if (!titleInput || !msgInput) return;

            if (type === 'holiday') {
                titleInput.value = ' የሜሽ ክሊኒንግ ልዩ የበዓል 25% ቅናሽ!';
                msgInput.value = 'ውድ {name}፤ ለመጪው በዓል ቤቶን ወይም ቢሮዎን በባለሙያዎቻችን ጥልቅ ጽዳት (Deep Cleaning) በ 25% ቅናሽ ያስውቡ። ፈጥነው ቀጠሮ ይያዙ! ስልክ: 0970075509';
            } else if (type === 'deep_clean') {
                titleInput.value = ' የቤት እና ቢሮ ጥልቅ የፅዳት አገልግሎት (Deep Cleaning)';
                msgInput.value = 'ሰላም ጤና ይስጥልን {name}! የሜሽ ክሊኒንግ ዘመናዊ የፅዳት ማሽኖችን እና አለም አቀፍ ኬሚካሎችን በመጠቀም ቤቶን እንደ አዲስ ያድሳል። ዛሬውኑ ይደውሉልን: 0970075509';
            } else if (type === 'sofa') {
                titleInput.value = ' የሶፋ፣ ምንጣፍ እና ፍራሽ የእንፋሎት እጥበት በቦታው';
                msgInput.value = 'ውድ {name}፤ የሶፋ፣ ምንጣፍ እና ፍራሽ እድፍና አቧራ አስቸግሮዎታል? በዘመናዊ የእንፋሎት ማሽን ያሉበት ድረስ መጥተን በደቂቃዎች ውስጥ እናጸዳለን! ስልክ: 0970075509';
            } else if (type === 'thanks') {
                titleInput.value = ' ለሜሽ ክሊኒንግ ቤተሰቦች የቀረበ ልዩ ምስጋና እና ቅናሽ';
                msgInput.value = 'ውድ {name}፤ ስለመረጡን እናመሰግናለን! ለቀጣይ አገልግሎትዎ የሚሆን የ 15% የታማኝነት ቅናሽ ኮድ ተዘጋጅቶልዎታል። ሜሽ ጽዳት: 0970075509';
            }
        }

        // In-page Submit Telegram Broadcast
        async function submitInPageBroadcast() {
            const totalCount = broadcastRecipientsList.length;
            const excludedCount = excludedBroadcastIds.size;
            const eligibleCount = Math.max(0, totalCount - excludedCount);

            if (eligibleCount === 0) {
                alert('እባክዎ ቢያንስ አንድ ተቀባይ ይምረጡ (ሁሉም ተጠቃሚዎች ተገልለዋል)!');
                return;
            }

            const title = (document.getElementById('inpage-bc-title')?.value || '').trim();
            const message = (document.getElementById('inpage-bc-message')?.value || '').trim();

            if (!title || !message) {
                alert('እባክዎ የማስታወቂያውን ርዕስ እና መልእክት ያስገቡ!');
                return;
            }

            if (!confirm(`ለመረጧቸው ${eligibleCount} ደንበኞች መልእክቱ አሁን በይፋዊው ቴሌግራም ቦት ይላክ?`)) {
                return;
            }

            const btn = document.getElementById('btn-inpage-send');
            const btnText = document.getElementById('btn-inpage-send-text');
            if (btn) btn.disabled = true;
            if (btnText) btnText.innerHTML = `<span class="animate-spin mr-1"></span> በመላክ ላይ...`;

            try {
                const res = await apiFetch('/api/telegram-broadcast/dispatch', {
                    method: 'POST',
                    body: JSON.stringify({
                        title: title,
                        message: message,
                        excluded_telegram_ids: Array.from(excludedBroadcastIds),
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert(' ' + data.message);
                } else {
                    alert('ስህተት፡ ' + (data.message || 'መላክ አልተቻለም'));
                }
            } catch (err) {
                alert('የኔትወርክ ስህተት፡ ' + err.message);
            } finally {
                if (btn) btn.disabled = false;
                if (btnText) btnText.innerHTML = `በቴሌግራም ቦት ላክ`;
            }
        }

        // =========================================================================
        // IPHONE 1-SCAN AUTO-SMS MODAL & REAL-TIME PROGRESS
        // =========================================================================
        async function openIphoneSmsModal() {
            const title = (document.getElementById('inpage-bc-title')?.value || '').trim();
            const message = (document.getElementById('inpage-bc-message')?.value || '').trim();

            if (!message) {
                alert('እባክዎ በመጀመሪያ የመልእክቱን ዝርዝር ይጻፉ!');
                document.getElementById('inpage-bc-message')?.focus();
                return;
            }

            const btn = document.getElementById('btn-iphone-sms');
            if (btn) btn.disabled = true;

            const modalContainer = document.getElementById('generic-modal-container');
            modalContainer.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-emerald-500/50 rounded-3xl max-w-md w-full p-6 text-center space-y-4">
                        <div class="animate-spin w-10 h-10 border-3 border-emerald-500 border-t-transparent rounded-full mx-auto"></div>
                        <p class="text-sm font-bold text-white">የቀጥታ ስልክ SMS ክፍለ-ጊዜ በማዘጋጀት ላይ...</p>
                    </div>
                </div>
            `;
            lucide.createIcons();

            try {
                const res = await apiFetch('/api/telegram-broadcast/create-sms-session', {
                    method: 'POST',
                    body: JSON.stringify({
                        title: title || 'የሜሽ ክሊኒንግ ኤስኤምኤስ ስርጭት',
                        message: message,
                        excluded_ids: Array.from(excludedBroadcastIds),
                    })
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    alert('ስህተት፡ ' + (data.message || 'ክፍለ-ጊዜ መፍጠር አልተቻለም'));
                    closeModal();
                    if (btn) btn.disabled = false;
                    return;
                }

                modalContainer.innerHTML = `
                    <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                        <div class="bg-slate-900 border border-emerald-500/50 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 my-auto">
                            <!-- Modal Header -->
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                <div>
                                    <h3 class="text-base font-black text-white">በስልክ SMS መላኪያ ማዕከል</h3>
                                    <p class="text-[11px] text-emerald-400 font-bold">ለ ${data.total_targets} ደንበኞች ከእርስዎ ስልክ ሲም ካርድ ይላካል</p>
                                </div>
                                <button onclick="closeSmsBridgeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                            </div>

                            <!-- QR Code & Instructions -->
                            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 text-center space-y-3">
                                <p class="text-xs text-slate-300 font-semibold">
                                    በእርስዎ <strong>ስልክ ካሜራ</strong> ይህንን QR ኮድ ስካን ያድርጉ፦
                                </p>
                                <div class="bg-white p-3 rounded-2xl inline-block shadow-xl border-2 border-emerald-500">
                                    <img id="sms-bridge-qr" src="" alt="SMS QR Code" class="w-48 h-48 sm:w-56 sm:h-56 mx-auto object-contain">
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[11px] text-slate-400">ካሜራዎ ሲያነበው በ Safari ይከፍታል — ከዚያ <strong>በአይፎን ላክ (Launch Shortcut)</strong> የሚለውን ይንኩ</p>
                                    <a href="${data.launch_url}" target="_blank" class="text-xs font-bold text-cyan-400 hover:underline block pt-1">
                                        ወይም እዚህ ጠቅ በማድረግ በስልክዎ ይክፈቱ (Direct Link)
                                    </a>
                                </div>
                            </div>

                            <!-- Live Progress Box -->
                            <div class="p-4 rounded-2xl bg-slate-800/80 border border-cyan-500/30 space-y-2">
                                <div class="flex justify-between text-xs font-bold">
                                    <span class="text-slate-300">የመላክ ሂደት (Live Progress):</span>
                                    <span id="sms-live-count" class="font-mono text-cyan-400">0 / ${data.total_targets} (0%)</span>
                                </div>
                                <div class="w-full bg-slate-950 rounded-full h-3 overflow-hidden border border-slate-700">
                                    <div id="sms-live-bar" class="bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 h-3 rounded-full transition-all duration-500 w-0"></div>
                                </div>
                                <div class="flex justify-between text-[11px] text-slate-400 pt-0.5">
                                    <span id="sms-live-status">ስካን እስኪደረግ በመጠባበቅ ላይ...</span>
                                    <span class="text-emerald-400 font-bold">Auto-Polling Active</span>
                                </div>
                            </div>

                            <!-- 1-Minute Shortcut Setup Instructions Dropdown -->
                            <details class="rounded-2xl bg-slate-950/70 border border-slate-800 p-3.5 text-xs text-slate-300">
                                <summary class="font-bold text-emerald-400 cursor-pointer flex items-center gap-1.5">
                                    <span>የአይፎን Shortcut 1-ደቂቃ ማዘጋጃ መመሪያ (ለመጀመሪያ ጊዜ ብቻ)</span>
                                </summary>
                                <ol class="space-y-2 mt-3 list-decimal list-inside text-[11px] leading-relaxed text-slate-300">
                                    <li>በእርስዎ iPhone ውስጥ <strong>Shortcuts</strong> አፕ ከፍተው አዲስ ፈጥረው ስሙን <code>Meash Auto SMS</code> ይበሉት::</li>
                                    <li>ድርጊት 1: <strong>Get Contents of URL</strong> አስገብተው URL ላይ <code>Shortcut Input</code> ያድርጉ::</li>
                                    <li>ድርጊት 2: <strong>Get Dictionary Value</strong> ያክሉ፤ Key <code>items</code> ያድርጉት።</li>
                                    <li>ድርጊት 3: <strong>Repeat with Each</strong> ያክሉ (በ items ላይ)።</li>
                                    <li>ድርጊት 4: በ Repeat <strong>ውስጥ</strong> <strong>Send Message</strong> ያክሉ፤ ተቀባይ: <code>Repeat Item -> phone</code>፣ መልእክት: <code>Repeat Item -> message</code>::</li>
                                    <li><strong>እጅግ ወሳኝ፦</strong> በ Send Message ውስጥ <strong>"Show When Run"</strong> የሚለውን አጥፉ (<strong>OFF</strong> ያድርጉት)፤ ይህም Send ሳይነኩ በራሱ በጀርባ ይልካል!</li>
                                    <li>ድርጊት 5: <strong>በ Repeat ውስጥ</strong>፣ ከ Send Message በኋላ፣ <strong>Get Contents of URL</strong> ያክሉ፤ Method <strong>POST</strong>፣ URL <code>${data.report_url || ''}</code>፣ Header <code>Content-Type: application/json</code>፣ Body (JSON)፦ <code>{"campaign_id": ${data.campaign_id}, "token": "${data.session_token}", "recipient_id": Repeat Item.recipient_id, "status": "sent"}</code>።</li>
                                    <li>አንድ ጊዜ እስከ 500 ተቀባዮች ብቻ ይላካሉ፤ እድገቱ 100% ካልደረሰ Shortcut እንደገና ያሂዱት — ቀሪዎቹ ብቻ ይላካሉ።</li>
                                </ol>
                            </details>

                            <div class="flex justify-end pt-1">
                                <button type="button" onclick="closeSmsBridgeModal()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs cursor-pointer">
                                    ዝጋ (Close)
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
                // Local QR — generated in the browser, the launch URL (with
                // its session token) never goes to a third-party QR service.
                setLocalQr(document.getElementById('sms-bridge-qr'), data.launch_url);

                // Start polling campaign status every 2 seconds
                if (smsProgressInterval) clearInterval(smsProgressInterval);
                smsProgressInterval = setInterval(async () => {
                    try {
                        const statusRes = await apiFetch(`/api/telegram-broadcast/campaign-status/${data.campaign_id}`);
                        const statusData = await statusRes.json();
                        if (statusData) {
                            const sent = statusData.sent_count || 0;
                            const total = statusData.total_targets || data.total_targets;
                            const pct = statusData.percentage || 0;

                            const elCount = document.getElementById('sms-live-count');
                            const elBar = document.getElementById('sms-live-bar');
                            const elStatus = document.getElementById('sms-live-status');

                            if (elCount) elCount.innerText = `${sent} / ${total} (${pct}%)`;
                            if (elBar) elBar.style.width = `${pct}%`;
                            if (elStatus) {
                                if (statusData.is_completed) {
                                    elStatus.innerText = ' ስርጭቱ ሙሉ በሙሉ ተጠናቋል!';
                                    elStatus.className = 'text-emerald-400 font-bold';
                                    clearInterval(smsProgressInterval);
                                    smsProgressInterval = null;
                                } else if (sent > 0) {
                                    elStatus.innerText = 'አይፎንዎ በጀርባ በመላክ ላይ ነው...';
                                    elStatus.className = 'text-cyan-400 font-bold';
                                }
                            }
                        }
                    } catch(e) {}
                }, 2000);

            } catch (err) {
                alert('የኔትወርክ ስህተት፡ ' + err.message);
                closeModal();
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        function closeSmsBridgeModal() {
            if (smsProgressInterval) {
                clearInterval(smsProgressInterval);
                smsProgressInterval = null;
            }
            closeModal();
        }

        // =========================================================================
        // EDIT RECIPIENT & BULK IMPORT MODALS
        // =========================================================================
        function openEditRecipientModal(id) {
            const user = broadcastRecipientsList.find(u => u.id == id);
            if (!user) return;

            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 my-auto">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white">የደንበኛ ስልክና መረጃ ማስተካከያ</h3>
                                <p class="text-[11px] text-slate-400">ስም፣ ስልክ እና የጓደኛ መገለል ሁኔታን ያሻሽሉ</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <form onsubmit="submitEditRecipient(event, ${user.id})" class="space-y-3.5 text-xs">
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ሙሉ ስም *</label>
                                <input type="text" id="edit-rc-name" value="${user.name || ''}" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold focus:outline-none focus:border-cyan-500">
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ስልክ ቁጥር</label>
                                <input type="text" id="edit-rc-phone" value="${user.phone || ''}" placeholder="0911223344" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono focus:outline-none focus:border-cyan-500">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">ቴሌግራም Username</label>
                                    <input type="text" id="edit-rc-user" value="${user.username ? user.username.replace('@', '') : ''}" placeholder="@username" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono focus:outline-none focus:border-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">ቴሌግራም Chat ID</label>
                                    <input type="number" id="edit-rc-tgid" value="${user.telegram_id || ''}" placeholder="12345678" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono focus:outline-none focus:border-cyan-500">
                                </div>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="edit-rc-excluded" ${user.is_excluded ? 'checked' : ''} class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-0 cursor-pointer">
                                    <div>
                                        <span class="font-bold text-amber-400 block text-xs">ይህ ሰው ጓደኛ/ቤተሰብ ነው (ከስርጭት አግልል)</span>
                                        <span class="text-[10px] text-slate-400">ማስታወቂያ ወይም የጅምላ መልእክት እንዳይላክለት ይከለክላል</span>
                                    </div>
                                </label>
                            </div>

                            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl cursor-pointer">ሰርዝ</button>
                                <button type="submit" id="btn-edit-rc-submit" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-bold rounded-xl shadow-lg cursor-pointer">አስቀምጥ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function submitEditRecipient(e, id) {
            e.preventDefault();
            const btn = document.getElementById('btn-edit-rc-submit');
            if (btn) btn.disabled = true;

            const name = document.getElementById('edit-rc-name').value.trim();
            const phone = document.getElementById('edit-rc-phone').value.trim();
            const username = document.getElementById('edit-rc-user').value.trim();
            const tgid = document.getElementById('edit-rc-tgid').value.trim();
            const isExcluded = document.getElementById('edit-rc-excluded').checked;

            try {
                const res = await apiFetch(`/api/telegram-broadcast/recipients/${id}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        name: name,
                        phone: phone || null,
                        username: username || null,
                        telegram_id: tgid ? parseInt(tgid, 10) : null,
                        is_excluded: isExcluded,
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    closeModal();
                    await loadAndRenderBroadcastCenter();
                } else {
                    alert('ስህተት፡ ' + (data.message || 'ማስተካከል አልተቻለም'));
                }
            } catch (err) {
                alert('ስህተት፡ ' + err.message);
            } finally {
                if (btn) btn.disabled = false;
            }
        }

        function openBulkImportTelegramModal() {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-cyan-500/50 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 my-auto">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-xl bg-cyan-600/20 text-cyan-400 flex items-center justify-center">
                                    <i data-lucide="upload" class="w-4 h-4"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-white">የደንበኞች ስልክ ዝርዝር በጅምላ ማስገቢያ (Import Contacts)</h3>
                                    <p class="text-[11px] text-slate-400">Telegram Export (result.json)፣ የትኛውም JSON ወይም CSV ፋይል ይጫኑ</p>
                                </div>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold cursor-pointer">&times;</button>
                        </div>

                        <form onsubmit="submitBulkImportTelegram(event)" class="space-y-3.5 text-xs">
                            <!-- Option 1: File Upload -->
                            <div class="p-3 rounded-2xl bg-slate-950 border border-dashed border-cyan-500/40 text-center space-y-2">
                                <i data-lucide="file-text" class="w-8 h-8 text-cyan-400 mx-auto"></i>
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">Telegram Data Export (.json) ወይም CSV ፋይል ይጫኑ</label>
                                    <input type="file" id="bulk-import-file" accept=".json,.csv,.txt" class="block w-full text-xs text-slate-400 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-cyan-600 file:text-white hover:file:bg-cyan-500 cursor-pointer">
                                </div>
                                <p class="text-[10px] text-emerald-400 font-medium">✓ ከቴሌግራም የወጣ (Telegram Export JSON)፣ የደንበኞች ዝርዝር ወይም CSV ፋይል በቀጥታ ይደገፋል</p>
                            </div>

                            <div class="text-center text-[10px] text-slate-500 uppercase tracking-wider font-bold">— ወይም ጽሑፉን / JSON ከታች ለጥፉ —</div>

                            <!-- Option 2: Paste Raw Text -->
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ጽሑፍ ወይም JSON መለጠፊያ (ስም፣ ስልክ፣ ቴሌግራም ID)</label>
                                <textarea id="bulk-import-raw" rows="4" placeholder="ምሳሌ 1 (CSV)፦
አቶ አበበ, 0911223344, @abebe
ወ/ሮ አልማዝ, 0922334455, 12345678

ምሳሌ 2 (JSON)፦
[
  {&quot;name&quot;: &quot;ዳዊት&quot;, &quot;phone&quot;: &quot;0911002233&quot;, &quot;username&quot;: &quot;dawit&quot;},
  {&quot;name&quot;: &quot;ሳራ&quot;, &quot;phone&quot;: &quot;0922114455&quot;, &quot;telegram_id&quot;: 987654321}
]" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white font-mono text-xs leading-relaxed focus:outline-none focus:border-cyan-500"></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl hover:bg-slate-700 cursor-pointer">ሰርዝ</button>
                                <button type="submit" id="btn-import-submit" class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-extrabold rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>አስገባ (Import Now)</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function submitBulkImportTelegram(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-import-submit');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'በማስገባት ላይ...';
            }

            const fileInput = document.getElementById('bulk-import-file');
            const rawText = document.getElementById('bulk-import-raw')?.value.trim();

            const formData = new FormData();
            if (fileInput && fileInput.files.length > 0) {
                formData.append('file', fileInput.files[0]);
            } else if (rawText) {
                formData.append('raw_data', rawText);
            } else {
                alert('እባክዎ ፋይል ይምረጡ ወይም ጽሑፍ ይለጥፉ!');
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'አስገባ (Import Now)';
                }
                return;
            }

            try {
                const token = await getAuthToken();
                const res = await fetch('/api/telegram-broadcast/import', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        ...(token ? { 'Authorization': `Bearer ${token}` } : {})
                    },
                    body: formData
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    alert(' ' + data.message);
                    closeModal();
                    await loadAndRenderBroadcastCenter();
                } else {
                    alert('ስህተት፡ ' + (data.message || 'ማስገባት አልተቻለም'));
                }
            } catch (err) {
                alert('የኔትወርክ ስህተት፡ ' + err.message);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'አስገባ (Import Now)';
                }
            }
        }

        function openAddSingleTelegramModal() {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4 my-auto">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white">+ አዲስ ደንበኛ መዝግብ</h3>
                                <p class="text-[11px] text-slate-400">አንድ ደንበኛ ወይም ስልክ ቁጥር በእጅ ለመጨመር</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <form onsubmit="submitAddSingleTelegram(event)" class="space-y-3.5 text-xs">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">ስም *</label>
                                    <input type="text" id="add-tg-first" required placeholder="ለምሳሌ፡ አበበ" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-cyan-500 font-bold">
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">የአባት ስም</label>
                                    <input type="text" id="add-tg-last" placeholder="ከበደ" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-cyan-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">ስልክ ቁጥር</label>
                                    <input type="text" id="add-tg-phone" placeholder="0911223344" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono focus:outline-none focus:border-cyan-500">
                                </div>
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">ቴሌግራም Username</label>
                                    <input type="text" id="add-tg-user" placeholder="@username" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono focus:outline-none focus:border-cyan-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-400 mb-1">ቴሌግራም Chat ID <span class="text-slate-500 text-[10px]">(ካወቁት ብቻ / አማራጭ)</span></label>
                                <input type="number" id="add-tg-id" placeholder="ለምሳሌ፡ 12345678 (ካላወቁት ባዶ ይተዉት)" class="w-full bg-slate-800/70 border border-slate-700 rounded-xl px-3 py-2 text-slate-300 font-mono text-xs focus:outline-none focus:border-cyan-500">
                            </div>

                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="add-tg-excluded" class="w-4 h-4 rounded border-slate-700 bg-slate-800 text-amber-500 focus:ring-0 cursor-pointer">
                                    <div>
                                        <span class="font-bold text-amber-400 block text-xs">ይህ ሰው ጓደኛ/ቤተሰብ ነው (ከስርጭት አግልል)</span>
                                        <span class="text-[10px] text-slate-400">ማስታወቂያ እንዳይላክለት ይከላከላል</span>
                                    </div>
                                </label>
                            </div>

                            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl cursor-pointer">ሰርዝ</button>
                                <button type="submit" id="btn-add-tg-submit" class="px-5 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-lg cursor-pointer">መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function submitAddSingleTelegram(e) {
            e.preventDefault();

            const firstName = (document.getElementById('add-tg-first')?.value || '').trim();
            const lastName = (document.getElementById('add-tg-last')?.value || '').trim();
            const username = (document.getElementById('add-tg-user')?.value || '').trim();
            const phone = (document.getElementById('add-tg-phone')?.value || '').trim();
            let tgId = (document.getElementById('add-tg-id')?.value || '').trim();
            const isExcluded = document.getElementById('add-tg-excluded')?.checked || false;

            if (!firstName) {
                alert('እባክዎ የስም ማስገቢያውን ይሙሉ!');
                return;
            }

            if (!username && !phone && !tgId) {
                alert('እባክዎ ስልክ ቁጥር ወይም ቴሌግራም መለያ ቢያንስ አንዱን ያስገቡ!');
                return;
            }

            const btn = document.getElementById('btn-add-tg-submit');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'በመመዝገብ ላይ...';
            }

            const payload = {
                first_name: firstName,
                last_name: lastName || null,
                username: username ? username.replace(/^@/, '') : null,
                phone_number: phone || null,
                telegram_id: tgId ? parseInt(tgId, 10) : null,
                is_excluded: isExcluded,
                source: 'phone_customer',
            };

            try {
                const res = await apiFetch('/api/telegram-broadcast/add-recipient', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (res.ok && data.success) {
                    alert(' ' + data.message);
                    closeModal();
                    await loadAndRenderBroadcastCenter();
                } else {
                    alert('ስህተት፡ ' + (data.message || 'መመዝገብ አልተቻለም'));
                }
            } catch (err) {
                alert('የኔትወርክ ስህተት፡ ' + err.message);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'መዝግብ';
                }
            }
        }

        async function loadAndRenderCampaignHistory() {
            const subContainer = document.getElementById('marketing-subtab-container');
            if (!subContainer) return;

            try {
                const res = await apiFetch('/api/campaigns');
                const data = await res.json();
                const campaigns = data.data || [];

                subContainer.innerHTML = `
                    <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                        <h3 class="text-sm font-bold text-white">የቀደሙ ዘመቻዎች እና ማስታወቂያዎች ታሪክ</h3>
                        <span class="text-xs text-slate-400">ጠቅላላ፡ ${campaigns.length}</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        ${campaigns.length === 0 ? '<div class="p-8 text-slate-500 col-span-3 text-center">ምንም የተዘጋጀ የማስታወቂያ ዘመቻ የለም።</div>' : ''}
                        ${campaigns.map(c => {
                            const title = (c && c.title && c.title !== 'undefined') ? c.title : 'የበዓላት ቅናሽ ማስታወቂያ';
                            const msgText = (c && c.message_text && c.message_text !== 'undefined') ? c.message_text : 'መልዕክት አልተገለጸም';
                            const sentCount = (c && c.sent_count !== undefined && c.sent_count !== null) ? c.sent_count : (c && c.total_targets ? c.total_targets : 0);
                            const channel = ((c && c.channel) || 'telegram').toUpperCase();
                            const isSent = c && (c.status === 'sent' || c.status === 'completed');

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
                                    <span>ዒላማ: <strong class="text-white">${c.audience_filter || 'All'}</strong></span>
                                    <span>የተላከላቸው: <strong class="text-cyan-400 font-mono text-sm">${sentCount}</strong></span>
                                </div>
                            </div>
                            `;
                        }).join('')}
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                subContainer.innerHTML = `<div class="p-6 text-red-400">የዘመቻ ታሪክ መጫን አልተቻለም: ${err.message}</div>`;
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
                                        <input type="text" id="setting-owner-phone" value="0970075509" class="w-full bg-slate-800 border border-cyan-500/50 rounded-xl px-3 py-2 text-cyan-300 font-mono font-bold">
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-400 font-bold text-[10px] whitespace-nowrap"> ተረጋግጧል</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-slate-400 mb-1">የድርጅት ስም</label>
                                    <input type="text" value="Meash Cleaning Solution (ሜሽ የፅዳት አገልግሎት)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold" readonly>
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
                                        <span id="setting-sms-status-pill" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-800 text-slate-300"> SMS ሁኔታ በማረጋገጥ ላይ...</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3 text-xs">
                                <div>
                                    <label class="block text-slate-400 mb-1">የሙከራ ተቀባይ ስልክ ቁጥሮች (ለቡድን በኮማ ይለዩ)</label>
                                    <input type="text" id="setting-test-recipient" oninput="updateOwnerSimSmsLink()" value="0970075509" placeholder="0970075509" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono font-bold">
                                </div>

                                <div>
                                    <label class="block text-slate-400 mb-1">የመልዕክት ይዘት (Test Message)</label>
                                    <textarea id="setting-test-msg" oninput="updateOwnerSimSmsLink()" rows="2" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-2.5 text-white">ሰላም! ይህ ከሜሽ ክሊኒንግ (0970075509) የተላከ ይፋዊ የሙከራ ኤስኤምኤስ ነው። ሲስተሙ በትክክል እየሰራ ነው።</textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <button onclick="sendTestSmsFromSettings()" id="btn-settings-test-sms" class="py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer text-xs">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                        <span>በሲስተም መዝገብ አስቀምጥ (Log only)</span>
                                    </button>
                                    <a id="btn-owner-direct-sim-sms" href="#" class="py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer text-xs text-center">
                                        <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                                        <span>በስልክ SIM ላክ</span>
                                    </a>
                                </div>

                                <!-- Phone QR Code Camera Scan Card -->
                                <div class="mt-3 p-3 rounded-2xl bg-slate-950/70 border border-cyan-500/30 flex items-center gap-3">
                                    <div class="bg-white p-2 rounded-xl flex-shrink-0 shadow-md">
                                        <img id="qr-sim-sms" src="" alt="SMS QR Code" class="w-24 h-24 object-contain">
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/40">ለማንኛውም ስማርት ስልክ</span>
                                            <span class="text-xs font-bold text-white">ካሜራ ስካን QR ኮድ</span>
                                        </div>
                                        <p class="text-[10px] text-slate-300">በስልክዎ ካሜራ ይህንን QR ኮድ ስካን ሲያደርጉ መልእክቱ በቀጥታ በስልክዎ SMS መላኪያ አፕ ተዘጋጅቶ ይከፈታል::</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Telegram Bot Status & Controller Card -->
                        <div class="p-6 rounded-3xl bg-slate-900 border border-sky-500/30 space-y-4 shadow-xl">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-xl bg-sky-600/20 text-sky-400 flex items-center justify-center">
                                        <i data-lucide="bot" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-bold text-white">የቴሌግራም ቦት (Telegram Bot)</h3>
                                        <span id="setting-tg-status-pill" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-800 text-slate-300"> ሁኔታውን በማጣራት ላይ...</span>
                                    </div>
                                </div>
                                <a href="https://t.me/meash_cleaning_solution_bot" target="_blank" class="px-2.5 py-1 rounded-lg bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 font-bold text-xs flex items-center gap-1 transition">
                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                    <span>ቦቱን ክፈት</span>
                                </a>
                            </div>

                            <div class="space-y-3 text-xs">
                                <div>
                                    <label class="block text-slate-400 mb-1">የቦት ቶከን (Bot Token ከ @BotFather)</label>
                                    <div class="flex items-center gap-2">
                                        <input type="text" id="setting-tg-token" placeholder="አዲስ ቶከን ካለዎት እዚህ ያስገቡ..." class="w-full bg-slate-800 border border-slate-700 focus:border-sky-500 rounded-xl px-3 py-2 text-white font-mono text-xs">
                                    </div>
                                    <span class="text-[10px] text-slate-400 mt-1 block">ቶከኑን ከቀየሩ ወይም አዲስ ካስገቡ ከታች ያለውን "አገናኝ" የሚለውን ይጫኑ::</span>
                                </div>

                                <div class="p-3 rounded-2xl bg-slate-800/60 border border-slate-700/60 space-y-1.5 text-slate-300">
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-400">ቦት ተጠቃሚ (Bot Username):</span>
                                        <span id="setting-tg-username" class="font-bold text-sky-300">@meash_cleaning_solution_bot</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-400">የተገናኘበት Webhook URL:</span>
                                        <span id="setting-tg-webhook-url" class="font-mono text-[10px] text-slate-300 truncate max-w-[200px]">https://...</span>
                                    </div>
                                    <div id="setting-tg-error-row" class="hidden text-red-400 text-[11px] pt-1 border-t border-slate-700">
                                        <strong>ስህተት፡ </strong><span id="setting-tg-error-msg"></span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-1">
                                    <button onclick="setTelegramWebhookFromSettings()" id="btn-settings-tg-set" class="py-2.5 bg-gradient-to-r from-sky-600 to-blue-600 hover:from-sky-500 hover:to-blue-500 text-white font-bold rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer text-xs">
                                        <i data-lucide="link" class="w-3.5 h-3.5"></i>
                                        <span>ቦቱን አገናኝ (Set Webhook)</span>
                                    </button>
                                    <button onclick="sendTestTelegramFromSettings()" id="btn-settings-tg-test" class="py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold rounded-xl shadow-lg flex items-center justify-center gap-1.5 cursor-pointer text-xs">
                                        <i data-lucide="send" class="w-3.5 h-3.5 text-sky-400"></i>
                                        <span>የሙከራ መልእክት ላክ</span>
                                    </button>
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
                  <p class="font-bold text-white text-sm"> የዛሬ ቀን፡ ${EC.formatEth(new Date())}</p>
                                    <p class="text-[11px] text-slate-400 mt-1">የኢትዮጵያ ቀን አቆጣጠር አልጎሪዝም በትክክል ተስተካክሎ 2019 ዓ.ም እያሳየ ይገኛል።</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            lucide.createIcons();
            updateOwnerSimSmsLink();
            // Live SMS status check
            try {
                apiFetch('/api/sms/status').then(r => r.json()).then(sData => {
                    const pill = document.getElementById('setting-sms-status-pill');
                    if (pill) {
                        if (sData.is_live) {
                            pill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-950/80 text-emerald-400 border border-emerald-500/30';
                            pill.textContent = '● SMS: LIVE (' + (sData.provider || 'Gateway') + ')';
                        } else {
                            pill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-950/80 text-amber-400 border border-amber-500/30';
                            pill.textContent = '● SMS: NOT CONNECTED';
                        }
                    }
                }).catch(() => {});
            } catch (_) {}

            // Live Telegram Bot & Webhook Status Check
            checkTelegramBotStatusInSettings();
        }

        async function checkTelegramBotStatusInSettings() {
            try {
                const res = await apiFetch('/api/telegram/status');
                const data = await res.json();
                const pill = document.getElementById('setting-tg-status-pill');
                const usernameEl = document.getElementById('setting-tg-username');
                const webhookUrlEl = document.getElementById('setting-tg-webhook-url');
                const tokenInput = document.getElementById('setting-tg-token');
                const errRow = document.getElementById('setting-tg-error-row');
                const errText = document.getElementById('setting-tg-error-msg');

                if (!pill) return;

                if (!data.configured) {
                    pill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-950/80 text-red-400 border border-red-500/30';
                    pill.textContent = ' ቶከን አልተዋቀረም (Not Configured)';
                    if (errRow) {
                        errRow.classList.remove('hidden');
                        errText.textContent = data.message || 'TELEGRAM_BOT_TOKEN አልተዋቀረም';
                    }
                    return;
                }

                if (tokenInput && data.masked_token && !tokenInput.value) {
                    tokenInput.placeholder = 'የአሁኑ ቶከን፡ ' + data.masked_token;
                }

                if (!data.valid) {
                    pill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-950/80 text-red-400 border border-red-500/30';
                    pill.textContent = ' ልክ ያልሆነ ወይም የተሰረዘ ቶከን (Invalid Token)';
                    if (errRow) {
                        errRow.classList.remove('hidden');
                        errText.textContent = data.error || data.message || 'Telegram rejected this bot token';
                    }
                    return;
                }

                if (usernameEl && data.bot && data.bot.username) {
                    usernameEl.textContent = '@' + data.bot.username;
                }

                const whUrl = data.webhook ? (data.webhook.url || '') : '';
                if (webhookUrlEl) {
                    webhookUrlEl.textContent = whUrl || 'ያልተገናኘ (Inactive)';
                }

                if (data.webhook_active) {
                    pill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-950/80 text-emerald-400 border border-emerald-500/30';
                    pill.textContent = '● በስራ ላይ (Active 24/7 Webhook)';
                    if (errRow) errRow.classList.add('hidden');
                } else {
                    pill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-950/80 text-amber-400 border border-amber-500/30';
                    pill.textContent = ' አልተገናኘም (Inactive Webhook)';
                    if (errRow) {
                        errRow.classList.remove('hidden');
                        errText.textContent = 'ቦቱ በክላውድ አልተገናኘም። እባክዎ "ቦቱን አገናኝ" የሚለውን ይጫኑ::';
                    }
                }

                if (data.webhook && data.webhook.last_error_message && errRow) {
                    errRow.classList.remove('hidden');
                    errText.textContent = 'Telegram Error: ' + data.webhook.last_error_message;
                }
            } catch (err) {
                console.warn('Failed to check telegram bot status:', err);
            }
        }

        async function setTelegramWebhookFromSettings() {
            const btn = document.getElementById('btn-settings-tg-set');
            const tokenInput = document.getElementById('setting-tg-token');
            const token = tokenInput ? tokenInput.value.trim() : '';

            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i><span>በማገናኘት ላይ...</span>';
            if (window.lucide) lucide.createIcons();

            try {
                const res = await apiFetch('/api/telegram/set-webhook', {
                    method: 'POST',
                    body: JSON.stringify({ token: token || undefined }),
                });
                const data = await res.json();
                if (data.success) {
                    alert(' ' + data.message);
                    if (tokenInput && token) tokenInput.value = '';
                    await checkTelegramBotStatusInSettings();
                } else {
                    alert(' ' + (data.message || 'ማገናኘት አልተቻለም'));
                }
            } catch (e) {
                alert('የኔትወርክ ስህተት፡ ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="link" class="w-3.5 h-3.5"></i><span>ቦቱን አገናኝ (Set Webhook)</span>';
                if (window.lucide) lucide.createIcons();
            }
        }

        async function sendTestTelegramFromSettings() {
            const btn = document.getElementById('btn-settings-tg-test');
            btn.disabled = true;
            btn.innerHTML = '<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i><span>በመላክ ላይ...</span>';
            if (window.lucide) lucide.createIcons();

            try {
                const res = await apiFetch('/api/telegram/send-test', {
                    method: 'POST',
                    body: JSON.stringify({}),
                });
                const data = await res.json();
                if (data.success) {
                    alert(' ' + data.message);
                } else {
                    alert(' ' + (data.message || 'መልእክት መላክ አልተቻለም'));
                }
            } catch (e) {
                alert('የኔትወርክ ስህተት፡ ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="send" class="w-3.5 h-3.5 text-sky-400"></i><span>የሙከራ መልእክት ላክ</span>';
                if (window.lucide) lucide.createIcons();
            }
        }

        async function sendTestSmsFromSettings() {
            const btn = document.getElementById('btn-settings-test-sms');
            btn.disabled = true;
            btn.innerText = 'በመላክ ላይ...';

            const rawPhones = document.getElementById('setting-test-recipient').value || '';
            const message = document.getElementById('setting-test-msg').value || '';
            const phoneList = rawPhones.split(/[,;\s]+/).map(p => p.trim()).filter(p => p.length >= 9);

            if (phoneList.length === 0) {
                alert('እባክዎ ቢያንስ አንድ ትክክለኛ ስልክ ቁጥር ያስገቡ።');
                btn.disabled = false;
                btn.innerText = 'በሲስተም መዝገብ አስቀምጥ (Log only)';
                return;
            }

            try {
                let successCount = 0;
                for (const phone of phoneList) {
                    const res = await apiFetch('/api/notifications/send-direct-sms', {
                        method: 'POST',
                        body: JSON.stringify({ phone, message })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        successCount++;
                    }
                }
        alert(` የሙከራ ኤስኤምኤስ ለ ${successCount}/${phoneList.length} ቁጥሮች በሲስተም መዝገብ ላይ ተመዝግቧል (logged — not delivered)!`);
            } catch (e) {
                alert('የግንኙነት ስህተት: ' + e.message);
            } finally {
                btn.disabled = false;
                btn.innerText = 'በሲስተም መዝገብ አስቀምጥ (Log only)';
            }
        }

        function updateOwnerSimSmsLink() {
            const rawPhones = document.getElementById('setting-test-recipient')?.value || '0970075509';
            const msg = document.getElementById('setting-test-msg')?.value || '';
            const phoneList = rawPhones.split(/[,;\s]+/).map(p => formatPhoneForSms(p)).filter(p => p.length >= 9);
            const joinedPhones = phoneList.join(',');

            const link = document.getElementById('btn-owner-direct-sim-sms');
            if (link) {
                link.href = `sms:${joinedPhones}?&body=${encodeURIComponent(msg)}`;
            }
            const qr = document.getElementById('qr-sim-sms');
            if (qr) {
                // iPhone Camera natively opens Messages app with RFC 5724 standard sms:phone?&body=
                const payload = `sms:${joinedPhones}?&body=${encodeURIComponent(msg)}`;
                setLocalQr(qr, payload);
            }
        }
        // ==========================================
        // 10c. WEBSITE & CMS CONTENT MANAGER (NEW)
        // ==========================================
        let currentCmsSubTab = 'services';
        let cmsServicesData = [];
        let cmsSettingsData = {};
        let cmsFeedbacksData = [];
        let cmsPhotosData = [];
        let cmsPhotosCloudinary = false;
        let cmsPhotosStorage = 'local';
        window._photosFilter = window._photosFilter || 'all';
        window._photosSearch = window._photosSearch || '';
        window._photoDeleteArmed = window._photoDeleteArmed || {};

        async function renderWebsiteCmsModule(container) {
            container.innerHTML = `
                <div class="max-w-7xl mx-auto space-y-6">
                    <!-- Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">የድረ-ገጽ ማስተዳደሪያ</span>
                                <span class="text-[10px] bg-cyan-950 text-cyan-400 border border-cyan-500/30 px-2 py-0.5 rounded-full font-bold">Live CMS</span>
                            </div>
                            <h2 class="text-2xl sm:text-3xl font-black text-white mt-1">የድረ-ገጽ ይዘት፣ ዋጋ እና ቅንብሮች (Website CMS)</h2>
                            <p class="text-xs text-slate-400 mt-0.5">የድርጅቱን ስልኮች፣ መፈክር፣ የአገልግሎት ዋጋዎችን እና የደንበኛ ምስክርነቶችን በቀላሉ ያስተዳድሩ።</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="/" target="_blank" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-cyan-300 font-bold text-xs rounded-xl transition border border-slate-700 flex items-center gap-2">
                                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                                <span>ድረ-ገጹን ጎብኝ (Visit Live Site)</span>
                            </a>
                        </div>
                    </div>

                    <!-- CMS Sub-navigation Tabs -->
                    <div class="flex items-center gap-2 border-b border-slate-800 pb-3 overflow-x-auto text-xs font-bold">
                        <button type="button" onclick="switchCmsTab('services')" id="btn-cms-services" class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2 ${currentCmsSubTab === 'services' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/20' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'}">
                            <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                            <span>1. አገልግሎቶች እና ዋጋ (Services &amp; Pricing)</span>
                        </button>
                        <button type="button" onclick="switchCmsTab('contact')" id="btn-cms-contact" class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2 ${currentCmsSubTab === 'contact' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/20' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'}">
                            <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                            <span>2. ስልክ፣ አድራሻ እና መፈክር (Contact &amp; Slogan)</span>
                        </button>
                        <button type="button" onclick="switchCmsTab('reviews')" id="btn-cms-reviews" class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2 ${currentCmsSubTab === 'reviews' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/20' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'}">
                            <i data-lucide="star" class="w-3.5 h-3.5"></i>
                            <span>3. የደንበኛ አስተያየቶች (Testimonials)</span>
                        </button>
                        <button type="button" onclick="switchCmsTab('about')" id="btn-cms-about" class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2 ${currentCmsSubTab === 'about' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/20' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'}">
                            <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                            <span>4. ስለ ድርጅቱ ጽሁፍ (About Us Text)</span>
                        </button>
                        <button type="button" onclick="switchCmsTab('photos')" id="btn-cms-photos" class="px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2 ${currentCmsSubTab === 'photos' ? 'bg-cyan-600 text-white shadow-lg shadow-cyan-600/20' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'}">
                            <i data-lucide="image" class="w-3.5 h-3.5"></i>
                            <span>5. ፎቶዎች (Photos)</span>
                        </button>
                    </div>

                    <!-- CMS Tab Content Container -->
                    <div id="cms-tab-content" class="min-h-[400px]">
                        <div class="p-12 text-center text-slate-500 text-xs">መረጃዎች በመጫን ላይ ናቸው...</div>
                    </div>
                </div>
            `;
            lucide.createIcons();
            loadCmsTabData();
        }

        async function switchCmsTab(tab) {
            currentCmsSubTab = tab;
            ['services', 'contact', 'reviews', 'about', 'photos'].forEach(t => {
                const btn = document.getElementById(`btn-cms-${t}`);
                if (btn) {
                    if (t === tab) {
                        btn.className = 'px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2 bg-cyan-600 text-white shadow-lg shadow-cyan-600/20';
                    } else {
                        btn.className = 'px-4 py-2 rounded-xl transition cursor-pointer flex items-center gap-2 bg-slate-900 text-slate-400 hover:text-white border border-slate-800';
                    }
                }
            });
            loadCmsTabData();
        }

        async function loadCmsTabData() {
            const container = document.getElementById('cms-tab-content');
            if (!container) return;

            if (currentCmsSubTab === 'services') {
                container.innerHTML = `<div class="p-8 text-center text-slate-500 text-xs">አገልግሎቶችን በመጫን ላይ...</div>`;
                try {
                    const res = await apiFetch('/api/services/all');
                    cmsServicesData = await res.json();
                    renderCmsServicesTab(container);
                } catch (e) {
                    renderErrorCard(container, 'አገልግሎቶችን መጫን አልተቻለም፡ ' + e.message, 'loadCmsTabData()');
                }
            } else if (currentCmsSubTab === 'contact') {
                container.innerHTML = `<div class="p-8 text-center text-slate-500 text-xs">ቅንብሮችን በመጫን ላይ...</div>`;
                try {
                    const res = await apiFetch('/api/admin/website/settings');
                    const d = await res.json();
                    cmsSettingsData = d.settings || {};
                    renderCmsContactTab(container);
                } catch (e) {
                    renderErrorCard(container, 'ቅንብሮችን መጫን አልተቻለም፡ ' + e.message, 'loadCmsTabData()');
                }
            } else if (currentCmsSubTab === 'reviews') {
                container.innerHTML = `<div class="p-8 text-center text-slate-500 text-xs">አስተያየቶችን በመጫን ላይ...</div>`;
                try {
                    const res = await apiFetch('/api/admin/website/feedbacks');
                    const d = await res.json();
                    cmsFeedbacksData = d.data || d || [];
                    renderCmsReviewsTab(container);
                } catch (e) {
                    renderErrorCard(container, 'አስተያየቶችን መጫን አልተቻለም፡ ' + e.message, 'loadCmsTabData()');
                }
            } else if (currentCmsSubTab === 'about') {
                if (!Object.keys(cmsSettingsData).length) {
                    try {
                        const res = await apiFetch('/api/admin/website/settings');
                        const d = await res.json();
                        cmsSettingsData = d.settings || {};
                    } catch (_) {}
                }
                renderCmsAboutTab(container);
            } else if (currentCmsSubTab === 'photos') {
                container.innerHTML = `<div class="p-8 text-center text-slate-500 text-xs">ፎቶዎችን በመጫን ላይ...</div>`;
                try {
                    if (!cmsServicesData.length) {
                        try {
                            const sres = await apiFetch('/api/services/all');
                            cmsServicesData = await sres.json();
                        } catch (_) {}
                    }
                    const res = await apiFetch('/api/admin/website/photos');
                    const d = await res.json();
                    cmsPhotosData = d.photos || [];
                    cmsPhotosCloudinary = !!d.cloudinary_configured;
                    cmsPhotosStorage = d.storage || 'local';
                    renderCmsPhotosTab(container);
                } catch (e) {
                    renderErrorCard(container, 'ፎቶዎችን መጫን አልተቻለም፡ ' + e.message, 'loadCmsTabData()');
                }
            }
        }

        // Sub-tab 1: Services & Pricing
        function renderCmsServicesTab(container) {
            container.innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white">የአገልግሎቶች ዝርዝር እና ዋጋዎች (Live Services &amp; Pricing)</h3>
                            <p class="text-xs text-slate-400">እዚህ የተስተካከለ ማንኛውም ዋጋ ወይም ስም ወዲያውኑ በዋናው ድረ-ገጽ ላይ ይንጸባረቃል።</p>
                        </div>
                        <button type="button" onclick="openAddServiceModal()" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 shadow-lg shadow-cyan-600/20 cursor-pointer">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>አዲስ አገልግሎት ጨምር</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900 shadow-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-800/80 text-slate-400 font-bold uppercase tracking-wider text-[11px] border-b border-slate-800">
                                <tr>
                                    <th class="p-3.5">መለያ / ኮድ</th>
                                    <th class="p-3.5">የአገልግሎት ስም (አማርኛ / EN)</th>
                                    <th class="p-3.5">መነሻ ዋጋ (ETB)</th>
                                    <th class="p-3.5">መለኪያ አሃድ</th>
                                    <th class="p-3.5">ሁኔታ (Status)</th>
                                    <th class="p-3.5 text-right">ድርጊት (Actions)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 text-slate-300">
                                ${cmsServicesData.map(s => `
                                    <tr class="hover:bg-slate-800/50 transition">
                                        <td class="p-3.5 font-mono text-cyan-400 font-bold">${s.code}</td>
                                        <td class="p-3.5">
                                            <div class="font-bold text-white text-sm">${s.name_am || s.name_en}</div>
                                            <div class="text-[11px] text-slate-400">${s.name_en}</div>
                                        </td>
                                        <td class="p-3.5">
                                            <div class="flex items-center gap-1.5">
                                                <input type="number" id="cms-svc-price-${s.id}" value="${parseFloat(s.base_price)}" class="w-24 px-2 py-1 bg-slate-950 border border-slate-700 rounded-lg text-white font-mono font-bold text-xs focus:border-cyan-400">
                                                <button type="button" onclick="saveServicePrice(${s.id})" title="ዋጋ መዝግብ" class="p-1 bg-cyan-600/30 hover:bg-cyan-600 text-cyan-300 hover:text-white rounded-md transition cursor-pointer">
                                                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="p-3.5 font-semibold text-slate-400">${s.unit}</td>
                                        <td class="p-3.5">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold ${s.is_active ? 'bg-emerald-950 text-emerald-400 border border-emerald-500/30' : 'bg-red-950 text-red-400 border border-red-500/30'}">
                                                ${s.is_active ? '● ንቁ (Active)' : '○ የጠፋ (Hidden)'}
                                            </span>
                                        </td>
                                        <td class="p-3.5 text-right">
                                            <button type="button" onclick="toggleServiceActiveState(${s.id}, ${s.is_active ? 0 : 1})" class="px-2.5 py-1 text-[11px] font-bold rounded-lg border transition ${s.is_active ? 'border-amber-500/30 text-amber-400 hover:bg-amber-950' : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-950'} cursor-pointer">
                                                ${s.is_active ? 'ደብቅ' : 'አሳይ'}
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function saveServicePrice(id) {
            const input = document.getElementById(`cms-svc-price-${id}`);
            if (!input) return;
            const newPrice = parseFloat(input.value);
            if (isNaN(newPrice) || newPrice < 0) {
                alert('እባክዎ ትክክለኛ የዋጋ መጠን ያስገቡ።');
                return;
            }

            try {
                const res = await apiFetch(`/api/services/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ base_price: newPrice })
                });
                const d = await res.json();
                if (res.ok) {
                    alert('የአገልግሎት ዋጋው በተሳካ ሁኔታ ተዘምኗል!');
                    loadCmsTabData();
                } else {
                    alert('ስህተት፡ ' + (d.message || 'ዋጋ ማስተካከል አልተቻለም'));
                }
            } catch (err) {
                alert('የግንኙነት ችግር፡ ' + err.message);
            }
        }

        async function toggleServiceActiveState(id, newState) {
            try {
                const res = await apiFetch(`/api/services/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ is_active: Boolean(newState) })
                });
                if (res.ok) {
                    loadCmsTabData();
                }
            } catch (err) {
                alert('ስህተት፡ ' + err.message);
            }
        }

        function openAddServiceModal() {
            const modalHtml = `
                <div id="cms-add-service-modal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 text-white">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <h3 class="font-bold text-base flex items-center gap-2">
                                <i data-lucide="plus-circle" class="w-4 h-4 text-cyan-400"></i>
                                <span>አዲስ አገልግሎት መመዝገቢያ</span>
                            </h3>
              <button type="button" onclick="document.getElementById('cms-add-service-modal').remove()" class="text-slate-400 hover:text-white"></button>
                        </div>
                        <form onsubmit="submitNewService(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="text-slate-400 block mb-1">መለያ ኮድ (Unique Code e.g. tile_clean)</label>
                                <input id="new-svc-code" type="text" required class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono">
                            </div>
                            <div>
                                <label class="text-slate-400 block mb-1">የአማርኛ ስም (Amharic Name)</label>
                                <input id="new-svc-name-am" type="text" required placeholder="የወለል ማሽን እጥበት" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            </div>
                            <div>
                                <label class="text-slate-400 block mb-1">የእንግሊዝኛ ስም (English Name)</label>
                                <input id="new-svc-name-en" type="text" required placeholder="Floor Rotary Scrubbing" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-slate-400 block mb-1">መነሻ ዋጋ (Base Price ETB)</label>
                                    <input id="new-svc-price" type="number" required min="0" step="0.5" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                </div>
                                <div>
                                    <label class="text-slate-400 block mb-1">መለኪያ አሃድ (Unit e.g. seat, sqm)</label>
                                    <input id="new-svc-unit" type="text" required value="service" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                                </div>
                            </div>
                            <div>
                                <label class="text-slate-400 block mb-1">አጭር መግለጫ (Description)</label>
                                <textarea id="new-svc-desc" rows="2" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white"></textarea>
                            </div>
                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" onclick="document.getElementById('cms-add-service-modal').remove()" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold">ይቅር</button>
                                <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold">አስቀምጥ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            lucide.createIcons();
        }

        async function submitNewService(e) {
            e.preventDefault();
            const payload = {
                code: document.getElementById('new-svc-code').value.trim().toLowerCase().replace(/\s+/g, '_'),
                name_am: document.getElementById('new-svc-name-am').value.trim(),
                name_en: document.getElementById('new-svc-name-en').value.trim(),
                base_price: parseFloat(document.getElementById('new-svc-price').value),
                unit: document.getElementById('new-svc-unit').value.trim(),
                description_am: document.getElementById('new-svc-desc').value.trim(),
                description_en: document.getElementById('new-svc-desc').value.trim(),
                is_active: true
            };

            try {
                const res = await apiFetch('/api/services', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const d = await res.json();
                if (res.ok) {
                    alert('አዲሱ አገልግሎት በተሳካ ሁኔታ ተመዝግቧል!');
                    document.getElementById('cms-add-service-modal')?.remove();
                    loadCmsTabData();
                } else {
                    alert('ስህተት፡ ' + (d.message || JSON.stringify(d.errors)));
                }
            } catch (err) {
                alert('የግንኙነት ችግር፡ ' + err.message);
            }
        }

        // Sub-tab 2: Website & System Settings (100% Customizable)
        function renderCmsContactTab(container) {
            const s = cmsSettingsData || {};

            // Parse subcities for display
            let subcitiesStr = 'ቦሌ, የካ, ቂርቆስ, አራዳ, ጉለሌ, ልደታ, ኮልፌ ቀራኒዮ, ንፋስ ስልክ ላፍቶ, አዲስ ከተማ, አቃቂ ቃሊቲ, ለሚ ኩራ';
            try {
                if (s.subcities) {
                    const parsed = typeof s.subcities === 'string' ? JSON.parse(s.subcities) : s.subcities;
                    if (Array.isArray(parsed) && parsed.length) {
                        subcitiesStr = parsed.map(c => c.name_am || c.name_en || c).join(', ');
                    }
                }
            } catch (_) {}

            // Parse pricing rules
            let pr = { weekly_discount: 15, biweekly_discount: 10, monthly_discount: 5, urgent_surcharge: 0, sofa_multiplier: 350, carpet_sqm_multiplier: 80, window_sqm_multiplier: 60, deep_cleaning_sqm_multiplier: 45 };
            try {
                if (s.pricing_rules) {
                    const parsedPr = typeof s.pricing_rules === 'string' ? JSON.parse(s.pricing_rules) : s.pricing_rules;
                    if (parsedPr && typeof parsedPr === 'object') {
                        pr = Object.assign(pr, parsedPr);
                    }
                }
            } catch (_) {}

            container.innerHTML = `
                <div class="max-w-4xl space-y-6">
                    <div>
                        <h3 class="text-lg font-bold text-white">የስርዓቱ እና የድረ-ገጽ ቅንብሮች (Website &amp; System Settings)</h3>
                        <p class="text-xs text-slate-400">እዚህ የተቀየረ ማንኛውም መረጃ (ስልኮች፣ የባንክ አካውንቶች፣ ክፍለ ከተሞች፣ ቅናሾችና የቦት መልዕክቶች) ወዲያውኑ በድረ-ገጹ፣ በሚኒ አፑ እና በቴሌግራም ቦቱ ላይ ይንጸባረቃል።</p>
                    </div>

                    <form onsubmit="saveCmsContactSettings(event)" class="space-y-6">

                        <!-- SECTION 1: Branding & Contact Details -->
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-4">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                                <div class="w-8 h-8 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold">1</div>
                                <h4 class="font-extrabold text-white text-sm">የድርጅቱ መረጃ፣ ስልኮች እና አድራሻ (Branding &amp; Contact)</h4>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">ዋና ስልክ ቁጥር 1 (Primary Hotline) *</label>
                                    <input type="text" id="set-phone-1" value="${escapeHtml(s.contact_phone_1 || '0970075509')}" required class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono text-sm focus:border-cyan-400">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">ተጨማሪ ስልክ ቁጥር 2 (Secondary Hotline)</label>
                                    <input type="text" id="set-phone-2" value="${escapeHtml(s.contact_phone_2 || '0998142190')}" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono text-sm focus:border-cyan-400">
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">የድርጅቱ ስም (Company Name) *</label>
                                    <input type="text" id="set-comp-name" value="${escapeHtml(s.company_name || 'MEASH CLEANING SOLUTION')}" required class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:border-cyan-400">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">ይፋዊ ኢሜይል (Company Email)</label>
                                    <input type="email" id="set-comp-email" value="${escapeHtml(s.company_email || 'meashcleaningsolution@gmail.com')}" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:border-cyan-400">
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">የድርጅቱ መፈክር በአማርኛ (Slogan AM — Strict English)</label>
                                    <input type="text" id="set-slogan-am" value="${escapeHtml(s.company_slogan_am || 'Your Environment, Our Responsibility.')}" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:border-cyan-400">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">የድርጅቱ መፈክር በእንግሊዝኛ (Slogan EN)</label>
                                    <input type="text" id="set-slogan-en" value="${escapeHtml(s.company_slogan || 'Your Environment, Our Responsibility.')}" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:border-cyan-400">
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">የቢሮ አድራሻ በአማርኛ (Office Location AM)</label>
                                    <input type="text" id="set-address-am" value="${escapeHtml(s.company_address_am || 'አያት አደባባይ፣ አዲስ አበባ፣ ኢትዮጵያ')}" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:border-cyan-400">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">የቢሮ አድራሻ በእንግሊዝኛ (Office Location EN)</label>
                                    <input type="text" id="set-address-en" value="${escapeHtml(s.company_address || 'Ayat Square, Addis Ababa, Ethiopia')}" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:border-cyan-400">
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">የስራ ሰዓት በአማርኛ (Working Hours AM)</label>
                                    <input type="text" id="set-hours-am" value="${escapeHtml(s.working_hours_am || 'ከሰኞ እስከ እሁድ 24 ሰዓት ክፍት — የቀን እና የሌሊት ፈረቃ')}" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:border-cyan-400">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">የስራ ሰዓት በእንግሊዝኛ (Working Hours EN)</label>
                                    <input type="text" id="set-hours-en" value="${escapeHtml(s.working_hours_en || '24/7 Open — Day, Evening & Night Shifts')}" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-sm focus:border-cyan-400">
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">ቴሌግራም ቦት (Bot Link)</label>
                                    <input type="text" id="set-tg-bot" value="${escapeHtml(s.telegram_bot || 'https://t.me/meash_cleaning_solution_bot')}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs font-mono">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">ቴሌግራም ቻናል (Channel Link)</label>
                                    <input type="text" id="set-tg-chan" value="${escapeHtml(s.telegram_channel || 'https://t.me/meashcleaningsolution')}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs font-mono" placeholder="https://t.me/meashcleaningsolution">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">ቴሌግራም ቀጥታ ለማነጋገር (Direct Contact)</label>
                                    <input type="text" id="set-tg-support" value="${escapeHtml(s.telegram_support || '@meashcleaning')}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs font-mono" placeholder="@meashcleaning">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">TikTok ሊንክ</label>
                                    <input type="text" id="set-tiktok" value="${escapeHtml(s.tiktok || 'https://www.tiktok.com/@meashcleaningsolution')}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs font-mono" placeholder="https://www.tiktok.com/@meashcleaningsolution">
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: Bank & Payment Accounts -->
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-4">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">2</div>
                                <h4 class="font-extrabold text-white text-sm">የባንክ እና የቴሌብር ሂሳብ ቁጥሮች (Bank &amp; Payment Accounts)</h4>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                                    <span class="text-xs font-bold text-cyan-400 flex items-center gap-1.5">ቴሌብር (Telebirr)</span>
                                    <div>
                                        <label class="text-[11px] text-slate-400 block mb-0.5">የስልክ / ሂሳብ ቁጥር:</label>
                                        <input type="text" id="set-pay-telebirr-no" value="${escapeHtml(s.telebirr_account_number || '0970075509')}" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white font-mono text-xs">
                                    </div>
                                    <div>
                                        <label class="text-[11px] text-slate-400 block mb-0.5">የአካውንት ስም (Account Name):</label>
                                        <input type="text" id="set-pay-telebirr-name" value="${escapeHtml(s.telebirr_account_name || 'Meash Cleaning / Tesfaye')}" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs">
                                    </div>
                                </div>

                                <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                                    <span class="text-xs font-bold text-purple-400 flex items-center gap-1.5">ንግድ ባንክ (Commercial Bank of Ethiopia - CBE)</span>
                                    <div>
                                        <label class="text-[11px] text-slate-400 block mb-0.5">የሂሳብ ቁጥር (Account No):</label>
                                        <input type="text" id="set-pay-cbe-no" value="${escapeHtml(s.cbe_account_number || '1000552467891')}" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white font-mono text-xs">
                                    </div>
                                    <div>
                                        <label class="text-[11px] text-slate-400 block mb-0.5">የአካውንት ስም (Account Name):</label>
                                        <input type="text" id="set-pay-cbe-name" value="${escapeHtml(s.cbe_account_name || 'Meash Cleaning Solution')}" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs">
                                    </div>
                                </div>

                                <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                                    <span class="text-xs font-bold text-amber-400 flex items-center gap-1.5">አቢሲኒያ ባንክ (Bank of Abyssinia - BOA)</span>
                                    <div>
                                        <label class="text-[11px] text-slate-400 block mb-0.5">የሂሳብ ቁጥር (Account No):</label>
                                        <input type="text" id="set-pay-boa-no" value="${escapeHtml(s.boa_account_number || '54321678')}" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white font-mono text-xs">
                                    </div>
                                    <div>
                                        <label class="text-[11px] text-slate-400 block mb-0.5">የአካውንት ስም (Account Name):</label>
                                        <input type="text" id="set-pay-boa-name" value="${escapeHtml(s.boa_account_name || 'Meash Cleaning Solution')}" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs">
                                    </div>
                                </div>

                                <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                                    <span class="text-xs font-bold text-blue-400 flex items-center gap-1.5">አዋሽ ባንክ (Awash Bank)</span>
                                    <div>
                                        <label class="text-[11px] text-slate-400 block mb-0.5">የሂሳብ ቁጥር (Account No):</label>
                                        <input type="text" id="set-pay-awash-no" value="${escapeHtml(s.awash_account_number || '01320876543200')}" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white font-mono text-xs">
                                    </div>
                                    <div>
                                        <label class="text-[11px] text-slate-400 block mb-0.5">የአካውንት ስም (Account Name):</label>
                                        <input type="text" id="set-pay-awash-name" value="${escapeHtml(s.awash_account_name || 'Meash Cleaning Solution')}" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-700 rounded-lg text-white text-xs">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-300 block mb-1">የክፍያ መመሪያ ማሳሰቢያ በአማርኛ (Payment Instructions AM)</label>
                                <input type="text" id="set-pay-instr-am" value="${escapeHtml(s.payment_instructions_am || 'ክፍያ ፈጽመው ሲያጠናቅቁ የደረሰኝ ቁጥር (Transaction Reference) ወይም ስክሪንሾት ያስገቡልን::')}" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs">
                            </div>
                        </div>

                        <!-- SECTION 3: Subscription Discounts & Pricing Rules -->
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-4">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                                <div class="w-8 h-8 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center font-bold">3</div>
                                <h4 class="font-extrabold text-white text-sm">የደንበኝነት ቅናሾች እና የዋጋ ማስያ ስሌት (Subscription Discounts &amp; Multipliers)</h4>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">የሳምንት ቅናሽ (Weekly %)</label>
                                    <div class="relative">
                                        <input type="number" step="0.5" id="set-disc-weekly" value="${pr.weekly_discount ?? 15}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono font-bold text-sm focus:border-cyan-400 pr-7">
                                        <span class="absolute right-2.5 top-2 text-slate-500 text-xs font-bold">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">የ2 ሳምንት ቅናሽ (Bi-weekly %)</label>
                                    <div class="relative">
                                        <input type="number" step="0.5" id="set-disc-biweekly" value="${pr.biweekly_discount ?? 10}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono font-bold text-sm focus:border-cyan-400 pr-7">
                                        <span class="absolute right-2.5 top-2 text-slate-500 text-xs font-bold">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">የወር ቅናሽ (Monthly %)</label>
                                    <div class="relative">
                                        <input type="number" step="0.5" id="set-disc-monthly" value="${pr.monthly_discount ?? 5}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono font-bold text-sm focus:border-cyan-400 pr-7">
                                        <span class="absolute right-2.5 top-2 text-slate-500 text-xs font-bold">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-slate-300 block mb-1">አስቸኳይ ተጨማሪ (Urgent Surcharge %)</label>
                                    <div class="relative">
                                        <input type="number" step="0.5" id="set-surcharge-urgent" value="${pr.urgent_surcharge ?? 0}" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white font-mono font-bold text-sm focus:border-cyan-400 pr-7">
                                        <span class="absolute right-2.5 top-2 text-slate-500 text-xs font-bold">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 4: Coverage Subcities & Time Slots -->
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-4">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                                <div class="w-8 h-8 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold">4</div>
                                <h4 class="font-extrabold text-white text-sm">የአገልግሎት ሽፋን ክፍለ ከተሞች (Coverage Subcities)</h4>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-300 block mb-1">ንቁ ክፍለ ከተሞች (በኮማ የተለዩ / Comma-separated):</label>
                                <textarea id="set-subcities-list" rows="2" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs focus:border-cyan-400 leading-relaxed">${escapeHtml(subcitiesStr)}</textarea>
                                <p class="text-[11px] text-slate-400 mt-1">እነዚህ ክፍለ ከተሞች በድረ-ገጹ፣ በሚኒ አፑ እና በቴሌግራም ቦቱ የቀጠሮ ማስያዣ ዝርዝር ውስጥ በቀጥታ ይታያሉ።</p>
                            </div>
                        </div>

                        <!-- SECTION 5: Telegram Bot Welcome Messages -->
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-xl space-y-4">
                            <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                                <div class="w-8 h-8 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold">5</div>
                                <h4 class="font-extrabold text-white text-sm">የቴሌግራም ቦት መልእክቶች (Telegram Bot Welcome &amp; Help Texts)</h4>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-300 block mb-1">የቦቱ የእንኳን ደህና መጡ መልእክት በአማርኛ (Bot Welcome AM):</label>
                                <textarea id="set-bot-welcome-am" rows="3" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs focus:border-cyan-400">${escapeHtml(s.bot_welcome_am || "እንኳን ወደ ሜሽ የጽዳት አገልግሎት በደህና መጡ!\n\n\"Your Environment, Our Responsibility.\"\n\nከፍተኛ ጥራት የጠበቀ የንግድ ተቋማት የጽዳት አገልግሎት — ሆቴሎች | ሬስቶራንቶች | ካፌዎች | ቢሮዎች። የሚፈልጉትን አገልግሎት ከታች ባሉት አማራጮች ይምረጡ፦")}</textarea>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-300 block mb-1">የቦቱ የእንኳን ደህና መጡ መልእክት በእንግሊዝኛ (Bot Welcome EN):</label>
                                <textarea id="set-bot-welcome-en" rows="2" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs focus:border-cyan-400">${escapeHtml(s.bot_welcome_en || "Welcome to MEASH CLEANING SOLUTION!\n\n\"Your Environment, Our Responsibility.\"\n\nPremium Commercial Cleaning Solutions — Hotels | Restaurants | Cafés | Offices.")}</textarea>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-300 block mb-1">የቦቱ የአጠቃቀም መመሪያ በአማርኛ (Bot Help AM):</label>
                                <textarea id="set-bot-help-am" rows="3" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs focus:border-cyan-400">${escapeHtml(s.bot_help_am || "የሚከተሉትን ትዕዛዞች መጠቀም ይችላሉ:\n• /start - ዋናውን ማውጫ ይከፍታል\n• /services - የአገልግሎቶች እና የዋጋ ዝርዝር\n• /book - በ 1 ደቂቃ ውስጥ ቀጠሮ ማስያዣ\n• /status ORD-... - የትዕዛዝ ሁኔታ መከታተያ\n• /contact - የሪሴፕሽን ስልክ አድራሻ\n• /help - ይህንን መመሪያ ያሳያል")}</textarea>
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button type="submit" id="btn-save-contact" class="px-8 py-3.5 bg-cyan-600 hover:bg-cyan-500 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl transition flex items-center gap-2 shadow-xl shadow-cyan-600/30 cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>ቅንብሮችን መዝግብ (Save All Settings)</span>
                            </button>
                        </div>
                    </form>
                </div>
            `;
            lucide.createIcons();
        }

        async function saveCmsContactSettings(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-contact');
            btn.disabled = true;
            btn.innerText = 'በመመዝገብ ላይ...';

            // Parse subcities list from CSV text
            const rawSubcities = document.getElementById('set-subcities-list').value.split(',')
                .map(s => s.trim())
                .filter(Boolean);
            const subcityEnMap = {
                'ቦሌ': 'Bole', 'የካ': 'Yeka', 'ቂርቆስ': 'Kirkos', 'አራዳ': 'Arada',
                'ጉለሌ': 'Gulele', 'ልደታ': 'Lideta', 'ኮልፌ ቀራኒዮ': 'Kolfe Keranio',
                'ኮልፌ': 'Kolfe Keranio', 'ንፋስ ስልክ ላፍቶ': 'Nifas Silk-Lafto',
                'ንፋስ ስልክ': 'Nifas Silk-Lafto', 'አዲስ ከተማ': 'Addis Ketema',
                'አቃቂ ቃሊቲ': 'Akaki Kality', 'አቃቂ': 'Akaki Kality', 'ለሚ ኩራ': 'Lemi Kura', 'ለሚኩራ': 'Lemi Kura'
            };
            const subcitiesArray = rawSubcities.map((amName, idx) => ({
                id: (subcityEnMap[amName] || `subcity_${idx + 1}`).toLowerCase().replace(/[\s-]+/g, '_'),
                name_am: amName,
                name_en: subcityEnMap[amName] || amName,
                active: true
            }));

            // Construct Pricing Rules
            const pricingRules = {
                weekly_discount: parseFloat(document.getElementById('set-disc-weekly')?.value || 15),
                biweekly_discount: parseFloat(document.getElementById('set-disc-biweekly')?.value || 10),
                monthly_discount: parseFloat(document.getElementById('set-disc-monthly')?.value || 5),
                urgent_surcharge: parseFloat(document.getElementById('set-surcharge-urgent')?.value || 0),
            };

            const payload = {
                contact_phone_1: document.getElementById('set-phone-1').value.trim(),
                contact_phone_2: document.getElementById('set-phone-2').value.trim(),
                company_name: document.getElementById('set-comp-name').value.trim(),
                company_email: document.getElementById('set-comp-email').value.trim(),
                company_slogan: document.getElementById('set-slogan-en').value.trim(),
                company_slogan_am: document.getElementById('set-slogan-am').value.trim(),
                company_address_am: document.getElementById('set-address-am').value.trim(),
                company_address: document.getElementById('set-address-en').value.trim(),
                working_hours_am: document.getElementById('set-hours-am').value.trim(),
                working_hours_en: document.getElementById('set-hours-en').value.trim(),
                telegram_bot: document.getElementById('set-tg-bot').value.trim(),
                telegram_channel: document.getElementById('set-tg-chan').value.trim(),
                telegram_support: document.getElementById('set-tg-support') ? document.getElementById('set-tg-support').value.trim() : '@meashcleaning',
                tiktok: document.getElementById('set-tiktok').value.trim(),

                // Bank & Payment Accounts
                telebirr_account_number: document.getElementById('set-pay-telebirr-no').value.trim(),
                telebirr_account_name: document.getElementById('set-pay-telebirr-name').value.trim(),
                cbe_account_number: document.getElementById('set-pay-cbe-no').value.trim(),
                cbe_account_name: document.getElementById('set-pay-cbe-name').value.trim(),
                boa_account_number: document.getElementById('set-pay-boa-no').value.trim(),
                boa_account_name: document.getElementById('set-pay-boa-name').value.trim(),
                awash_account_number: document.getElementById('set-pay-awash-no').value.trim(),
                awash_account_name: document.getElementById('set-pay-awash-name').value.trim(),
                payment_instructions_am: document.getElementById('set-pay-instr-am').value.trim(),

                // Subcities & Pricing Rules as JSON
                subcities: JSON.stringify(subcitiesArray),
                pricing_rules: JSON.stringify(pricingRules),

                // Telegram Bot Texts
                bot_welcome_am: document.getElementById('set-bot-welcome-am').value.trim(),
                bot_welcome_en: document.getElementById('set-bot-welcome-en').value.trim(),
                bot_help_am: document.getElementById('set-bot-help-am').value.trim(),
            };

            try {
                const res = await apiFetch('/api/admin/website/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const d = await res.json();
                if (res.ok) {
                    alert('ሁሉም የስርዓቱ እና የድረ-ገጹ ቅንብሮች በተሳካ ሁኔታ ተመዝግበዋል!');
                    cmsSettingsData = d.settings || payload;
                } else {
                    alert('ስህተት፡ ' + (d.message || 'ማስቀመጥ አልተቻለም'));
                }
            } catch (err) {
                alert('የግንኙነት ችግር፡ ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerText = 'ቅንብሮችን መዝግብ (Save All Settings)';
            }
        }

        // Sub-tab 3: Customer Reviews / Testimonials
        function renderCmsReviewsTab(container) {
            container.innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white">የደንበኞች ምስክርነት ማስተዳደሪያ (Customer Reviews)</h3>
                            <p class="text-xs text-slate-400">እዚህ የጸደቁ (Approved) አስተያየቶች ብቻ በድረ-ገጹ "What Clients Say" ክፍል ላይ ይታያሉ።</p>
                        </div>
                        <button type="button" onclick="openAddReviewModal()" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-xl transition flex items-center gap-1.5 shadow-lg shadow-cyan-600/20 cursor-pointer">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>አዲስ ምስክርነት ጨምር</span>
                        </button>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900 shadow-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-800/80 text-slate-400 font-bold uppercase tracking-wider text-[11px] border-b border-slate-800">
                                <tr>
                                    <th class="p-3.5">ደንበኛ</th>
                                    <th class="p-3.5">ደረጃ (Rating)</th>
                                    <th class="p-3.5">አስተያየት (Comment)</th>
                                    <th class="p-3.5">ምንጭ</th>
                                    <th class="p-3.5">በድረ-ገጽ (Status)</th>
                                    <th class="p-3.5 text-right">ድርጊት</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 text-slate-300">
                                ${cmsFeedbacksData.length === 0 ? `<tr><td colspan="6" class="p-6 text-center text-slate-500">ምንም የተመዘገበ አስተያየት የለም። "አዲስ ምስክርነት ጨምር" የሚለውን ይጫኑ።</td></tr>` : ''}
                                ${cmsFeedbacksData.map(f => `
                                    <tr class="hover:bg-slate-800/50 transition">
                                        <td class="p-3.5 font-bold text-white">
                                            ${f.customer ? f.customer.full_name : 'ደንበኛ #' + (f.customer_id || f.id)}
                                        </td>
                                        <td class="p-3.5">
                                            <div class="flex text-amber-400">
                        ${''.repeat(f.rating || 5)}${''.repeat(5 - (f.rating || 5))}
                                            </div>
                                        </td>
                                        <td class="p-3.5 max-w-xs truncate text-slate-300" title="${f.comment || ''}">
                                            "${f.comment || 'ምንም አስተያየት የለም'}"
                                        </td>
                                        <td class="p-3.5 text-slate-400 uppercase text-[10px] font-mono">${f.source || 'web'}</td>
                                        <td class="p-3.5">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold ${f.is_approved ? 'bg-emerald-950 text-emerald-400 border border-emerald-500/30' : 'bg-amber-950 text-amber-400 border border-amber-500/30'}">
                                                ${f.is_approved ? '● ይፋ የተደረገ (Live)' : '○ ተደብቋል (Hidden)'}
                                            </span>
                                        </td>
                                        <td class="p-3.5 text-right space-x-2">
                                            <button type="button" onclick="toggleReviewApprovalState(${f.id})" class="px-2 py-1 text-[11px] font-bold rounded-lg border transition ${f.is_approved ? 'border-amber-500/30 text-amber-400 hover:bg-amber-950' : 'border-emerald-500/30 text-emerald-400 hover:bg-emerald-950'} cursor-pointer">
                                                ${f.is_approved ? 'ደብቅ' : 'አጽድቅ (Publish)'}
                                            </button>
                                            <button type="button" onclick="deleteCmsReview(${f.id})" class="px-2 py-1 text-[11px] font-bold rounded-lg border border-red-500/30 text-red-400 hover:bg-red-950 transition cursor-pointer">
                                                አጥፋ
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function toggleReviewApprovalState(id) {
            try {
                const res = await apiFetch(`/api/admin/website/feedbacks/${id}/toggle-approve`, { method: 'PUT' });
                if (res.ok) loadCmsTabData();
            } catch (err) {
                alert('ስህተት፡ ' + err.message);
            }
        }

        async function deleteCmsReview(id) {
            if (!confirm('ይህንን አስተያየት መሰረዝ ይፈልጋሉ?')) return;
            try {
                const res = await apiFetch(`/api/admin/website/feedbacks/${id}`, { method: 'DELETE' });
                if (res.ok) loadCmsTabData();
            } catch (err) {
                alert('ስህተት፡ ' + err.message);
            }
        }

        function openAddReviewModal() {
            const modalHtml = `
                <div id="cms-add-review-modal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full space-y-4 text-white">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <h3 class="font-bold text-base flex items-center gap-2">
                                <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
                                <span>አዲስ የደንበኛ ምስክርነት ማከል</span>
                            </h3>
              <button type="button" onclick="document.getElementById('cms-add-review-modal').remove()" class="text-slate-400 hover:text-white"></button>
                        </div>
                        <form onsubmit="submitNewCmsReview(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="text-slate-400 block mb-1">የደንበኛው ስም እና ቦታ (e.g. Bethlehem Tadesse — Bole)</label>
                                <input id="new-rev-name" type="text" required placeholder="Bethlehem Tadesse — Bole" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                            </div>
                            <div>
                                <label class="text-slate-400 block mb-1">የኮከብ ደረጃ (Rating 1 - 5)</label>
                                <select id="new-rev-rating" class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white">
                  <option value="5"> (5 Stars)</option>
                  <option value="4"> (4 Stars)</option>
                  <option value="3"> (3 Stars)</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-slate-400 block mb-1">የደንበኛው አስተያየት (Testimonial Quote)</label>
                                <textarea id="new-rev-comment" rows="3" required placeholder="ሶፋዬ እንደ አዲስ ሆኗል፤ ቡድኑ በጣም ጨዋና ፈጣን ነበር..." class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-white"></textarea>
                            </div>
                            <div class="pt-2 flex justify-end gap-2">
                                <button type="button" onclick="document.getElementById('cms-add-review-modal').remove()" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 font-bold">ይቅር</button>
                                <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-bold">በድረ-ገጽ አትም</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            lucide.createIcons();
        }

        async function submitNewCmsReview(e) {
            e.preventDefault();
            const payload = {
                customer_name: document.getElementById('new-rev-name').value.trim(),
                rating: parseInt(document.getElementById('new-rev-rating').value),
                comment: document.getElementById('new-rev-comment').value.trim(),
                source: 'web',
                is_approved: true
            };

            try {
                const res = await apiFetch('/api/admin/website/feedbacks', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    alert('አስተያየቱ በተሳካ ሁኔታ ተመዝግቦ በድረ-ገጹ እንዲታይ ተደርጓል!');
                    document.getElementById('cms-add-review-modal')?.remove();
                    loadCmsTabData();
                } else {
                    alert('ስህተት ተፈጥሯል');
                }
            } catch (err) {
                alert('የግንኙነት ችግር፡ ' + err.message);
            }
        }

        // Sub-tab 4: About Us Text
        function renderCmsAboutTab(container) {
            const s = cmsSettingsData || {};
            container.innerHTML = `
                <div class="max-w-3xl space-y-6">
                    <div>
                        <h3 class="text-lg font-bold text-white">ስለ ድርጅቱ መግለጫ ጽሁፍ (About Us Narrative)</h3>
                        <p class="text-xs text-slate-400">በዋናው ድረ-ገጽ ላይ "ስለ እኛ (About Us)" ክፍል የሚታዩ ጽሁፎችን በቀላሉ ያርትዑ።</p>
                    </div>

                    <form onsubmit="saveCmsAboutText(event)" class="space-y-4 bg-slate-900 border border-slate-800 p-6 rounded-3xl shadow-xl">
                        <div>
                            <label class="text-xs font-bold text-slate-300 block mb-1">የአማርኛ መግለጫ ጽሁፍ (About Us AM)</label>
                            <textarea id="set-about-am" rows="4" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs leading-relaxed focus:border-cyan-400">${s.about_text_am || 'ሜሽ ክሊኒንግ ሶሉሽን (MEASH CLEANING SOLUTION) በአዲስ አበባ ከተማ ለመኖሪያ ቤቶች፣ ለቢሮዎች፣ ለሆቴሎች እና ለተቋማት አስተማማኝ እና ጥራት ያለው የጥልቅ ጽዳት አገልግሎት በዘመናዊ ማሽኖችና ኬሚካሎች በመታገዝ በታማኝነት ይሰጣል።'}</textarea>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-slate-300 block mb-1">የእንግሊዝኛ መግለጫ ጽሁፍ (About Us EN)</label>
                            <textarea id="set-about-en" rows="4" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs leading-relaxed focus:border-cyan-400">${s.about_text_en || 'MEASH CLEANING SOLUTION is Addis Ababa’s premier residential and commercial deep cleaning specialist. Equipped with modern steam and extraction machines, hospital-grade eco-friendly chemicals, and vetted background-checked cleaning professionals.'}</textarea>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <button type="submit" id="btn-save-about" class="px-6 py-3 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs uppercase tracking-wider rounded-xl transition flex items-center gap-2 shadow-lg shadow-cyan-600/30 cursor-pointer">
                                <i data-lucide="save" class="w-4 h-4"></i>
                                <span>መግለጫውን መዝግብ (Save About Text)</span>
                            </button>
                        </div>
                    </form>
                </div>
            `;
            lucide.createIcons();
        }

        async function saveCmsAboutText(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-about');
            btn.disabled = true;
            btn.innerText = 'በመመዝገብ ላይ...';

            const payload = {
                about_text_am: document.getElementById('set-about-am').value.trim(),
                about_text_en: document.getElementById('set-about-en').value.trim()
            };

            try {
                const res = await apiFetch('/api/admin/website/settings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                if (res.ok) {
                    alert('የድርጅቱ መግለጫ ጽሁፎች በተሳካ ሁኔታ ተዘምነዋል!');
                }
            } catch (err) {
                alert('የግንኙነት ችግር፡ ' + err.message);
            } finally {
                btn.disabled = false;
                btn.innerText = 'መግለጫውን መዝግብ (Save About Text)';
            }
        }

        // Sub-tab 5: Photos — every website image, controlled from here
        const PHOTO_SECTIONS = [
            { id: 'hero', label_en: 'Hero slider', label_am: 'ዋና ስላይደር' },
            { id: 'service', label_en: 'Service cards', label_am: 'የአገልግሎት ካርዶች' },
            { id: 'gallery', label_en: 'Gallery', label_am: 'ጋለሪ' },
            { id: 'feature', label_en: 'Feature strip', label_am: 'የሂደት ክፍል' },
        ];

        function photoSectionLabel(section) {
            const s = PHOTO_SECTIONS.find(x => x.id === section);
            return s ? s.label_en + ' (' + s.label_am + ')' : section;
        }

        function photosVisibleList() {
            const q = (window._photosSearch || '').trim().toLowerCase();
            return cmsPhotosData.filter(p => {
                if (window._photosFilter === 'added' && p.status !== 'added') return false;
                if (window._photosFilter === 'not_added' && p.status !== 'not_added') return false;
                if (q) {
                    const hay = ((p.title_en || '') + ' ' + (p.title_am || '') + ' ' + (p.service_code || '')).toLowerCase();
                    if (hay.indexOf(q) === -1) return false;
                }
                return true;
            });
        }

        function renderCmsPhotosTab(container) {
            const total = cmsPhotosData.length;
            const added = cmsPhotosData.filter(p => p.status === 'added').length;
            const notAdded = total - added;
            const list = photosVisibleList();
            const services = Array.isArray(cmsServicesData) ? cmsServicesData : (cmsServicesData.data || []);
            const serviceOptions = services.map(s =>
                '<option value="' + escapeHtml(s.code) + '">' + escapeHtml(s.name_en || s.name_am || s.code) + '</option>'
            ).join('');
            const filterBtn = (key, label) => '<button type="button" onclick="setPhotosFilter(\'' + key + '\')" class="px-3 py-1.5 rounded-lg font-bold transition cursor-pointer ' + (window._photosFilter === key ? 'bg-cyan-600 text-white shadow' : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800') + '">' + label + '</button>';

            let sectionsHtml = '';
            PHOTO_SECTIONS.forEach(sec => {
                const secAll = cmsPhotosData.filter(p => p.section === sec.id);
                const secAdded = secAll.filter(p => p.status === 'added').length;
                const secList = list.filter(p => p.section === sec.id);
                if (!secList.length && secAll.length) return; // hidden by current filter
                sectionsHtml += '<div class="space-y-3">'
                    + '<div class="flex items-center justify-between"><h4 class="text-sm font-bold text-white">' + sec.label_en + ' <span class="text-slate-400 font-semibold">(' + sec.label_am + ')</span></h4>'
                    + '<span class="text-[11px] font-bold text-slate-400">' + secAdded + ' Added / ' + secAll.length + ' total</span></div>';
                if (!secList.length) {
                    sectionsHtml += '<p class="text-xs text-slate-500 bg-slate-900 border border-dashed border-slate-800 rounded-2xl px-4 py-6 text-center">በዚህ ክፍል ገና ፎቶ የለም — ከላይ ካለው upload control ጨምር።</p>';
                } else {
                    sectionsHtml += '<div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">' + secList.map(renderPhotoCard).join('') + '</div>';
                }
                sectionsHtml += '</div>';
            });

            container.innerHTML = `
                <div class="space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-bold text-white">የድረ-ገጽ ፎቶዎች (Website Photos)</h3>
                            <p class="text-xs text-slate-400">የጣቢያውን ሁሉም ፎቶዎች እዚህ ይቆጣጠራሉ — slider፣ service cards፣ gallery እና feature strip። “Added” ማለት በጣቢያ ላይ ይታያል፤ “Not added” ማለት ተደብቋል።</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs font-bold">
                            <span class="px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-slate-300">${total} photos</span>
                            <span class="px-3 py-1.5 rounded-lg bg-emerald-950 border border-emerald-500/30 text-emerald-400">${added} Added</span>
                            <span class="px-3 py-1.5 rounded-lg bg-amber-950 border border-amber-500/30 text-amber-400">${notAdded} Not added</span>
                        </div>
                    </div>

                    ${cmsPhotosCloudinary
                        ? '<p class="text-xs font-bold text-emerald-400 bg-emerald-950/60 border border-emerald-500/30 rounded-xl px-3.5 py-2.5">Cloudinary ተገናኝቷል — ፎቶ መጫን ይቻላል።</p>'
                        : '<p class="text-xs font-bold text-amber-400 bg-amber-950/60 border border-amber-500/30 rounded-xl px-3.5 py-2.5">Test mode — ፎቶዎች በዚህ ሰርቨር ላይ ይቀመጣሉ (Render ላይ redeploy ሲደረግ ይጠፋሉ፤ Plesk ላይ ይቆያሉ)። Cloudinary በኋላ መጨመር ይቻላል።</p>'}

                    <!-- Upload control -->
                    <form id="ph-upload-form" onsubmit="uploadNewPhoto(event)" class="bg-slate-900 border border-slate-800 rounded-3xl p-5 space-y-4 shadow-xl">
                        <h4 class="text-sm font-bold text-white flex items-center gap-2"><i data-lucide="upload" class="w-4 h-4 text-cyan-400"></i><span>አዲስ ፎቶ ጨምር (Upload a photo)</span></h4>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-bold text-slate-300 block mb-1">ፎቶ ፋይል (Choose photo)</label>
                                <input type="file" name="photo" accept="image/*" class="w-full text-xs text-slate-300 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-cyan-600 file:text-white file:font-bold file:text-xs file:cursor-pointer">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-300 block mb-1">ወይም የፎቶ URL ለጥፍ (or paste image URL)</label>
                                <input type="url" name="url" placeholder="https://..." class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs focus:border-cyan-400">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-300 block mb-1">Caption EN (እንግሊዝኛ)</label>
                                <input type="text" name="title_en" maxlength="255" placeholder="e.g. Kitchen cleaning" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs focus:border-cyan-400">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-300 block mb-1">Caption አማርኛ</label>
                                <input type="text" name="title_am" maxlength="255" placeholder="ለምሳሌ፡ የኩሽና ጽዳት" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs focus:border-cyan-400">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-slate-300 block mb-1">የት ይገባል? (Belongs to)</label>
                                <select name="section" onchange="document.getElementById('ph-new-service-wrap').style.display = this.value === 'service' ? 'block' : 'none'" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs focus:border-cyan-400 cursor-pointer">
                                    <option value="hero">Hero slider</option>
                                    <option value="gallery">Gallery</option>
                                    <option value="service">Service card</option>
                                    <option value="feature">Feature strip</option>
                                </select>
                            </div>
                            <div id="ph-new-service-wrap" style="display:none">
                                <label class="text-xs font-bold text-slate-300 block mb-1">የየትኛው አገልግሎት? (Service)</label>
                                <select name="service_code" class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-white text-xs focus:border-cyan-400 cursor-pointer">
                                    <option value="">— አገልግሎት ምረጥ —</option>
                                    ${serviceOptions}
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="submit" id="btn-ph-upload" class="px-5 py-2.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-xl transition shadow-lg shadow-cyan-600/20 cursor-pointer">ፎቶ ጨምር (Add photo)</button>
                            <span id="ph-upload-msg" class="text-xs font-bold"></span>
                        </div>
                        <p class="text-[11px] text-slate-500">አዲስ ፎቶ “Not added” ሆኖ ይመጣል — “Add to site” እስኪጫን ድረስ በጣቢያ ላይ አይታይም።</p>
                    </form>

                    <!-- Filters + search -->
                    <div class="flex flex-wrap items-center gap-2">
                        ${filterBtn('all', 'ሁሉም (All)')}
                        ${filterBtn('added', 'Added')}
                        ${filterBtn('not_added', 'Not added')}
                        <div class="flex items-center gap-2 ml-auto">
                            <input type="text" id="ph-search-input" value="${escapeHtml(window._photosSearch || '')}" placeholder="Caption ፈልግ..." onkeydown="if(event.key==='Enter'){ window._photosSearch = this.value; renderCmsPhotosTab(document.getElementById('cms-tab-content')); }" class="w-48 bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-white text-xs focus:outline-none focus:border-cyan-500">
                            <button type="button" onclick="window._photosSearch = document.getElementById('ph-search-input').value; renderCmsPhotosTab(document.getElementById('cms-tab-content'));" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 cursor-pointer">ፈልግ</button>
                        </div>
                    </div>

                    ${list.length ? sectionsHtml : '<p class="text-xs text-slate-500 bg-slate-900 border border-dashed border-slate-800 rounded-2xl px-4 py-8 text-center">ምንም ፎቶ አልተገኘም።</p>'}
                </div>
            `;
            lucide.createIcons();
        }

        function renderPhotoCard(p) {
            const added = p.status === 'added';
            const badge = added
                ? '<span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 text-[11px] font-black">✓ Added</span>'
                : '<span class="px-2.5 py-1 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/40 text-[11px] font-black">● Not added</span>';
            return '<div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">'
                + '<div class="relative"><img src="' + escapeHtml(p.src) + '" alt="' + escapeHtml(p.title_en || p.title_am || 'Photo') + '" class="w-full h-40 object-cover" loading="lazy">'
                + '<span class="absolute top-2 left-2">' + badge + '</span></div>'
                + '<div class="p-3.5 space-y-2.5">'
                + '<div><label class="text-[10px] font-bold text-slate-400 block mb-0.5">Caption EN</label>'
                + '<input type="text" id="ph-cap-en-' + p.id + '" value="' + escapeHtml(p.title_en || '') + '" maxlength="255" class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-white text-xs focus:border-cyan-400"></div>'
                + '<div><label class="text-[10px] font-bold text-slate-400 block mb-0.5">Caption አማርኛ</label>'
                + '<input type="text" id="ph-cap-am-' + p.id + '" value="' + escapeHtml(p.title_am || '') + '" maxlength="255" class="w-full px-2.5 py-1.5 bg-slate-950 border border-slate-800 rounded-lg text-white text-xs focus:border-cyan-400"></div>'
                + '<p class="text-[11px] text-slate-500 font-semibold">' + photoSectionLabel(p.section) + (p.service_code ? ' • ' + escapeHtml(p.service_code) : '') + '</p>'
                + '<div class="flex flex-wrap gap-1.5 pt-1">'
                + '<button type="button" onclick="savePhotoCaptions(' + p.id + ')" class="px-2.5 py-1.5 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-[11px] rounded-lg cursor-pointer">Caption አስቀምጥ</button>'
                + '<button type="button" onclick="togglePhotoStatus(' + p.id + ')" class="px-2.5 py-1.5 ' + (added ? 'bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700' : 'bg-emerald-600 hover:bg-emerald-500 text-white') + ' font-bold text-[11px] rounded-lg cursor-pointer">' + (added ? 'Remove' : 'Add to site') + '</button>'
                + '<button type="button" onclick="document.getElementById(\'ph-change-' + p.id + '\').click()" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-[11px] rounded-lg border border-slate-700 cursor-pointer">Change photo</button>'
                + '<button type="button" onclick="deletePhoto(' + p.id + ', this)" class="px-2.5 py-1.5 bg-slate-800 hover:bg-red-900/60 text-red-300 font-bold text-[11px] rounded-lg border border-slate-700 cursor-pointer">Delete</button>'
                + '</div>'
                + '<input type="file" id="ph-change-' + p.id + '" accept="image/*" class="hidden" onchange="changePhotoFile(' + p.id + ', this)">'
                + '</div></div>';
        }

        function setPhotosFilter(f) {
            window._photosFilter = f;
            renderCmsPhotosTab(document.getElementById('cms-tab-content'));
        }

        async function refreshPhotosTab() {
            try {
                const res = await apiFetch('/api/admin/website/photos');
                const d = await res.json();
                cmsPhotosData = d.photos || [];
                cmsPhotosCloudinary = !!d.cloudinary_configured;
                cmsPhotosStorage = d.storage || 'local';
                renderCmsPhotosTab(document.getElementById('cms-tab-content'));
            } catch (e) {
                alert('ፎቶዎችን ማደስ አልተቻለም፡ ' + e.message);
            }
        }

        async function savePhotoCaptions(id) {
            const en = document.getElementById('ph-cap-en-' + id);
            const am = document.getElementById('ph-cap-am-' + id);
            try {
                const res = await apiFetch('/api/admin/website/photos/' + id, {
                    method: 'PUT',
                    body: JSON.stringify({ title_en: en ? en.value.trim() : null, title_am: am ? am.value.trim() : null })
                });
                const d = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(d.message || ('Save failed (' + res.status + ')'));
                alert('Caption ተቀምጧል!');
                refreshPhotosTab();
            } catch (err) {
                alert(err.message);
            }
        }

        async function togglePhotoStatus(id) {
            try {
                const res = await apiFetch('/api/admin/website/photos/' + id + '/toggle-status', { method: 'POST' });
                const d = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(d.message || ('Toggle failed (' + res.status + ')'));
                refreshPhotosTab();
            } catch (err) {
                alert(err.message);
            }
        }

        async function deletePhoto(id, btn) {
            if (!window._photoDeleteArmed[id]) {
                window._photoDeleteArmed[id] = true;
                btn.innerText = 'እርግጠኛ ነህ? እንደገና ጠቅ አድርግ';
                btn.classList.add('bg-red-600', 'text-white');
                setTimeout(() => {
                    window._photoDeleteArmed[id] = false;
                    if (btn.isConnected) {
                        btn.innerText = 'Delete';
                        btn.classList.remove('bg-red-600', 'text-white');
                    }
                }, 4000);
                return;
            }
            window._photoDeleteArmed[id] = false;
            try {
                const res = await apiFetch('/api/admin/website/photos/' + id, { method: 'DELETE' });
                const d = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(d.message || ('Delete failed (' + res.status + ')'));
                refreshPhotosTab();
            } catch (err) {
                alert(err.message);
            }
        }

        // File uploads use raw fetch (apiFetch forces a JSON content type,
        // which breaks multipart FormData). POST + _method=PUT lets
        // Laravel read the multipart body on the update route.
        async function uploadNewPhoto(e) {
            e.preventDefault();
            const form = document.getElementById('ph-upload-form');
            const btn = document.getElementById('btn-ph-upload');
            const msg = document.getElementById('ph-upload-msg');
            const fd = new FormData(form);
            const file = fd.get('photo');
            if (!file || !file.size) fd.delete('photo');
            if (!fd.get('url')) fd.delete('url');
            btn.disabled = true;
            btn.innerText = 'በመጫን ላይ...';
            try {
                const token = await getAuthToken();
                const res = await fetch('/api/admin/website/photos', {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
                    body: fd
                });
                const d = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(d.message || ('Upload failed (' + res.status + ')'));
                msg.className = 'text-xs font-bold text-emerald-400';
                msg.innerText = d.message || 'Photo added.';
                form.reset();
                document.getElementById('ph-new-service-wrap').style.display = 'none';
                refreshPhotosTab();
            } catch (err) {
                msg.className = 'text-xs font-bold text-red-400';
                msg.innerText = err.message;
            } finally {
                btn.disabled = false;
                btn.innerText = 'ፎቶ ጨምር (Add photo)';
            }
        }

        async function changePhotoFile(id, input) {
            if (!input.files || !input.files[0]) return;
            const fd = new FormData();
            fd.append('photo', input.files[0]);
            fd.append('_method', 'PUT');
            try {
                const token = await getAuthToken();
                const res = await fetch('/api/admin/website/photos/' + id, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Authorization': 'Bearer ' + token },
                    body: fd
                });
                const d = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(d.message || ('Change failed (' + res.status + ')'));
                alert('ፎቶ ተቀይሯል!');
                refreshPhotosTab();
            } catch (err) {
                alert(err.message);
            } finally {
                input.value = '';
            }
        }
        // ==========================================
        window._calendarView = window._calendarView || 'all';
        window._calendarSource = window._calendarSource || '';

        async function renderCalendarModule(container, isSilent = false) {
            try {
                const queryParams = new URLSearchParams();
                if (window._calendarView && window._calendarView !== 'all') {
                    queryParams.set('view', window._calendarView);
                }
                if (window._calendarSource) {
                    queryParams.set('source', window._calendarSource);
                }

                const res = await apiFetch('/api/appointments?' + queryParams.toString());
                const data = await res.json();
                const appointments = Array.isArray(data) ? data : (data.data || []);

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">የቀጠሮዎች እና የስራ መርሃ-ግብር ካላንደር</h2>
                                <p class="text-xs text-slate-400 mt-1">የቀን መቁጠሪያ በኢትዮጵያ ዘመን አቆጣጠር፣ በሰዓት፣ በትዕዛዝ ምንጭ እና በተመደቡ ቡድኖች።</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="renderCalendarModule(document.getElementById('app-viewport'))" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl border border-slate-700 flex items-center gap-1.5 transition cursor-pointer" title="ዳታውን አድስ">
                                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-cyan-400"></i>
                                    <span class="hidden sm:inline">አድስ (Refresh)</span>
                                </button>
                                <button onclick="openCreateOrderModal()" class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2 cursor-pointer">
                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                    <span>+ አዲስ ቀጠሮ</span>
                                </button>
                            </div>
                        </div>

                        <!-- Calendar Filters Bar -->
                        <div class="p-4 rounded-3xl bg-slate-900 border border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <!-- View Selector Buttons -->
                            <div class="flex items-center gap-1.5 bg-slate-800/80 p-1 rounded-2xl border border-slate-700">
                                <button onclick="window._calendarView = 'today'; renderCalendarModule(document.getElementById('app-viewport'));" class="px-3 py-1.5 rounded-xl font-bold transition ${window._calendarView === 'today' ? 'bg-cyan-600 text-white shadow' : 'text-slate-400 hover:text-white'}">ዛሬ (Today)</button>
                                <button onclick="window._calendarView = 'week'; renderCalendarModule(document.getElementById('app-viewport'));" class="px-3 py-1.5 rounded-xl font-bold transition ${window._calendarView === 'week' ? 'bg-cyan-600 text-white shadow' : 'text-slate-400 hover:text-white'}">የዚህ ሳምንት</button>
                                <button onclick="window._calendarView = 'month'; renderCalendarModule(document.getElementById('app-viewport'));" class="px-3 py-1.5 rounded-xl font-bold transition ${window._calendarView === 'month' ? 'bg-cyan-600 text-white shadow' : 'text-slate-400 hover:text-white'}">የዚህ ወር</button>
                                <button onclick="window._calendarView = 'all'; renderCalendarModule(document.getElementById('app-viewport'));" class="px-3 py-1.5 rounded-xl font-bold transition ${window._calendarView === 'all' ? 'bg-cyan-600 text-white shadow' : 'text-slate-400 hover:text-white'}">ሁሉንም (All)</button>
                            </div>

                            <!-- Source Dropdown -->
                            <div class="flex items-center gap-2">
                                <span class="text-slate-400 font-bold">ምንጭ፡</span>
                                <select onchange="window._calendarSource = this.value; renderCalendarModule(document.getElementById('app-viewport'));" class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-1.5 text-white font-bold cursor-pointer text-xs">
                                    <option value="" ${window._calendarSource === '' ? 'selected' : ''}>-- ሁሉም ምንጮች --</option>
                                    <option value="website" ${window._calendarSource === 'website' ? 'selected' : ''}>ድረ-ገጽ (Website)</option>
                                    <option value="telegram_miniapp" ${window._calendarSource === 'telegram_miniapp' ? 'selected' : ''}>ሚኒአፕ (MiniApp)</option>
                                    <option value="telegram_bot" ${window._calendarSource === 'telegram_bot' ? 'selected' : ''}>ቴሌግራም ቦት</option>
                                    <option value="phone" ${window._calendarSource === 'phone' ? 'selected' : ''}>ስልክ ጥሪ</option>
                                    <option value="walk_in" ${window._calendarSource === 'walk_in' ? 'selected' : ''}>በአካል (Walk-in)</option>
                                </select>
                            </div>
                        </div>

                        <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden p-5 shadow-xl">
                            <div class="space-y-3">
                                ${appointments.length === 0 ? '<p class="text-center text-slate-500 py-10 italic">ምንም የተያዘ ቀጠሮ የለም።</p>' : ''}
                                ${appointments.map(a => `
                                    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-cyan-500/50 transition">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xs font-bold text-cyan-400">${a.eth_date || EC.formatEth(a.appointment_date)}</span>
                                                <span class="text-[10px] text-slate-400 font-normal">(${a.appointment_date || '—'})</span>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-700/80 text-cyan-300 font-bold">${formatTimeSlot(a.appointment_time_slot || a.order?.appointment_time_slot)}</span>
                                                ${a.order?.order_number ? `<span class="text-xs font-bold text-white font-mono">${escapeHtml(a.order.order_number)}</span>` : ''}
                                                ${getChannelBadge(a.order?.source)}
                                                ${getPaymentBadge(a.order?.payment_status)}
                                            </div>
                                            <h4 class="text-sm font-bold text-white mt-1.5">${escapeHtml(a.order?.customer?.full_name || a.customer?.full_name || 'Customer')} - <span class="font-mono text-cyan-300">${escapeHtml(a.order?.customer?.phone || a.customer?.phone || '')}</span></h4>
                                            <p class="text-xs text-slate-400 mt-0.5">${escapeHtml(a.order?.address || a.order?.customer?.address || 'Addis Ababa')} | የተመደበ ቡድን: <strong class="text-white">${escapeHtml(a.team?.team_name || 'ቡድን አልተመደበም')}</strong></p>
                                        </div>
                                        <div class="flex items-center gap-2 self-end sm:self-auto">
                                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase ${a.status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-cyan-500/20 text-cyan-400'}">${a.status}</span>
                                            ${a.order?.id ? `
                                                <button onclick="openOrderDetailsModal(${a.order.id})" class="px-2.5 py-1 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-bold rounded-lg transition cursor-pointer">ዝርዝር</button>
                                            ` : ''}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();

                // Live Sync: Background auto-refresh for Calendar module
                if (window._calendarRefreshTimer) clearInterval(window._calendarRefreshTimer);
                window._calendarRefreshTimer = setInterval(() => {
                    const modalOpen = document.getElementById('generic-modal-container')?.querySelector('.fixed');
                    if (activeTab === 'calendar' && !modalOpen) {
                        renderCalendarModule(document.getElementById('app-viewport'), true);
                    }
                }, 25000);
            } catch (err) {
                renderErrorCard(container, 'የካላንደር ቀጠሮዎችን መጫን አልተቻለም፡ ' + err.message, 'renderCalendarModule(document.getElementById(\'app-viewport\'))');
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
          vip: ' ቪአይፒ (VIP)',
                    regular: ' መደበኛ ደንበኛ',
          new: ' አዲስ ደንበኛ'
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
                                        <h3 class="text-xl font-black text-white mt-1">${escapeHtml(c.full_name || 'ደንበኛ')}</h3>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button data-phone="${escapeHtml(c.phone || '')}" data-name="${escapeHtml(c.full_name || '')}" onclick="openDirectSmsModal(this.dataset.phone, this.dataset.name)" class="px-3 py-1.5 rounded-xl bg-cyan-950 hover:bg-cyan-900 border border-cyan-500/40 text-cyan-300 text-xs font-bold flex items-center gap-1.5 transition-colors cursor-pointer" title="ቀጥታ SMS ላክ">
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
                          <span class="text-[11px] text-cyan-300"> ${o.eth_appointment_date || EC.formatEth(o.appointment_date)}</span>
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
                          ${t.type === 'feedback' ? '' : (t.type === 'payment' ? '' : (t.type === 'complaint' ? '' : ''))}
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
                                        <p class="text-slate-400 text-[11px]">${o.customer?.phone}</p>
                                        <div class="mt-2 flex items-center gap-1.5">
                                            <span class="text-[10px] text-slate-400">ምንጭ:</span>
                                            ${getChannelBadge(o.source)}
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-slate-400 block text-[11px]">Schedule & መገኛ</span>
                                        <strong class="text-white">${o.eth_appointment_date || o.appointment_date} (${formatTimeSlot(o.appointment_time_slot)})</strong>
                                        <p class="text-slate-400 text-[11px]">${o.address || o.customer?.address}</p>
                                        ${(o.latitude && o.longitude) ? `
                                            <div class="mt-2 text-xs text-cyan-400 font-semibold flex items-center gap-1"><i data-lucide="map-pin" class="w-3.5 h-3.5"></i><span>አድራሻ: ${o.address || o.customer?.address || 'አዲስ አበባ'}</span></div>
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
                                    <div class="flex items-center gap-2">
                                        <span class="text-slate-300">የክፍያ ሁኔታ:</span>
                                        ${getPaymentBadge(o.payment_status)}
                                    </div>
                                    <span>የተመደበ ሰራተኛ/ቡድን: <strong class="text-cyan-300">${o.assigned_team?.team_name || 'አልተመደበም'}</strong></span>
                                </div>
                            </div>

                            <div class="flex flex-wrap justify-end gap-2 pt-4 border-t border-slate-800 mt-4">
                                ${o.order_status === 'completed' ? `
                                    <button onclick="triggerSendReviewRequest(${o.id}, '${escapeHtml(o.order_number)}')" class="px-4 py-2 bg-amber-950/80 hover:bg-amber-900 border border-amber-500/40 text-amber-300 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors cursor-pointer">
                                        <i data-lucide="star" class="w-4 h-4 text-amber-400"></i>
                                        <span>የደረጃ ጥየቃ ላክ (Review Request)</span>
                                    </button>
                                ` : ''}
                                ${o.payment_status !== 'paid' ? `
                                    <button onclick="closeModal(); openRecordOrderPaymentModal(${o.id}, '${escapeHtml(o.order_number)}', ${parseFloat(o.total || 0)}, ${parseFloat(o.payments?.reduce((acc, p) => acc + parseFloat(p.amount), 0) || 0)});" class="px-4 py-2 bg-emerald-950/80 hover:bg-emerald-900 border border-emerald-500/40 text-emerald-300 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors cursor-pointer">
                                        <i data-lucide="credit-card" class="w-4 h-4 text-emerald-400"></i>
                                        <span>ክፍያ መዝግብ</span>
                                    </button>
                                ` : ''}
                                ${o.order_status !== 'completed' && o.order_status !== 'cancelled' ? `
                                    <button onclick="closeModal(); confirmAndAssignOrder(${o.id});" class="px-4 py-2 bg-blue-950/80 hover:bg-blue-900 border border-blue-500/40 text-blue-300 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors cursor-pointer">
                                        <i data-lucide="user-check" class="w-4 h-4 text-blue-400"></i>
                                        <span>ሰራተኛ መድብ</span>
                                    </button>
                                    <button onclick="closeModal(); openPostponeOrderModal(${o.id}, '${o.order_number}', '${o.customer?.full_name}');" class="px-4 py-2 bg-amber-950/70 hover:bg-amber-900 border border-amber-500/40 text-amber-300 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors cursor-pointer">
                                        <i data-lucide="calendar-clock" class="w-4 h-4 text-amber-400"></i>
                                        <span>ቀጠሮ አስተላልፍ</span>
                                    </button>
                                ` : ''}
                                <button onclick="closeModal()" class="px-5 py-2 bg-slate-800 text-slate-300 text-xs font-bold rounded-xl hover:bg-slate-700 cursor-pointer">ዝጋ</button>
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

        async function triggerSendReviewRequest(orderId, orderNumber) {
            if (!confirm(`የትዕዛዝ ቁጥር ${orderNumber} ደንበኛ የ 5-ኮከብ ደረጃ እንዲሰጡ ጥያቄ በቴሌግራም/ኤስኤምኤስ ይላክ?`)) {
                return;
            }
            try {
                showToast('የደረጃ መስጫ ጥያቄ በመላክ ላይ...', 'info');
                const res = await apiFetch(`/api/orders/${orderId}/send-review-request`, {
                    method: 'POST',
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    showToast(data.message || 'የደረጃ መስጫ ጥያቄው በተሳካ ሁኔታ ተልኳል!', 'success');
                } else {
                    showToast(data.message || 'ጥያቄውን መላክ አልተቻለም', 'error');
                }
            } catch (err) {
                showToast('የኔትወርክ ስህተት አጋጥሟል', 'error');
            }
        }
        window.triggerSendReviewRequest = triggerSendReviewRequest;

        // ==========================================
        // IN-APP LIVE RIDE MAP (Car into House - Ride/Uber Style)
        // ==========================================
        let activeInAppMap = null;

        function openCustomerAddressModal(param1, param2, param3, param4, param5) {
            let orderId, custName, custPhone, address, teamName;
            // Support both (orderId, custName, custPhone, address, teamName) and (address, custName, custPhone)
            if (typeof param1 === 'string' && isNaN(Number(param1)) && (param1.includes(' ') || param1.includes(',') || param1.length > 8)) {
                address = param1;
                custName = param2;
                custPhone = param3;
                orderId = null;
                teamName = param5 || activeCleaningTeamName || 'Team Alpha';
            } else if (arguments.length >= 4) {
                orderId = param1;
                custName = param2;
                custPhone = param3;
                address = param4;
                teamName = param5 || activeCleaningTeamName || 'Team Alpha';
            } else {
                orderId = param1;
                custName = param2;
                custPhone = param3;
                address = param4;
                teamName = param5 || activeCleaningTeamName || 'Team Alpha';
            }

            if (!custName || custName === 'undefined' || custName === 'null') {
                custName = 'ውድ ደንበኛ';
            }
            if (!custPhone || custPhone === 'undefined' || custPhone === 'null') {
                custPhone = '0970075509';
            }
            if (!address || address === 'undefined' || address === 'null' || !String(address).trim()) {
                address = 'አዲስ አበባ (Addis Ababa)';
            }
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-cyan-500/40 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div class="flex items-center gap-2">
                                <div class="w-9 h-9 rounded-2xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center font-bold">
                                    <i data-lucide="map-pin" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-extrabold text-white">የደንበኛ አድራሻ እና መገኛ</h3>
                                    <span class="text-xs text-cyan-400 font-semibold">${custName}</span>
                                </div>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <div class="space-y-3">
                            <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700 space-y-1.5">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">የተመዘገበ ሙሉ አድራሻ</span>
                <p class="text-sm font-bold text-white leading-relaxed"> ${address}</p>
                            </div>

                            <div class="p-3.5 rounded-2xl bg-slate-800/60 border border-slate-700/80 flex items-center justify-between">
                                <div>
                                    <span class="text-[10px] text-slate-400 block font-semibold">የደንበኛ ስልክ ቁጥር</span>
                                    <strong class="text-sm font-mono text-cyan-300">${custPhone || '0970075509'}</strong>
                                </div>
                                <a href="tel:${custPhone || '0970075509'}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs flex items-center gap-1.5 shadow-lg">
                                    <i data-lucide="phone" class="w-3.5 h-3.5"></i>
                                    <span>ደውል</span>
                                </a>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-400">
                                 ቡድኑ ወደ ስራው ቦታ ሲሄድ ጥያቄ ካጋጠመው በቀጥታ ለደንበኛው መደወል ይችላል።
                            </div>
                        </div>

                        <div class="flex justify-end pt-2 border-t border-slate-800">
                            <button onclick="closeModal()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl">ዝጋ</button>
                        </div>
                    </div>
                </div>
            `;
            lucide.createIcons();
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
                  <label class="block font-bold text-cyan-300"> የፅዳት ሰራተኛ በቀጥታ መድብ (Cleaners)</label>
                                    <span class="text-[10px] bg-cyan-950 text-cyan-400 border border-cyan-800 px-2 py-0.5 rounded-full font-bold">${cleaners.length} ሰራተኞች</span>
                                </div>
                                ${cleaners.length === 0 ? `
                                    <p class="text-slate-500 italic text-[11px]">እስካሁን የተመዘገበ የፅዳት ሰራተኛ የለም።</p>
                                    <button onclick="closeModal(); openAddEmployeeModal();" class="text-cyan-400 hover:underline text-[11px] font-bold">+ አዲስ ሰራተኛ መዝግብ</button>
                                ` : `
                                    <select id="assign-worker-select" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white">
                                        <option value="">-- የፅዳት ሰራተኛ ይምረጡ --</option>
                    ${cleaners.map(c => `<option value="${c.id}">${c.name} ( ${c.phone || c.email})</option>`).join('')}
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
                    <label class="block font-bold text-slate-300"> ወይም የተደራጀ ቡድን መድብ (Teams)</label>
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
          alert(' ' + (data.message || 'ሰራተኛው ለትዕዛዙ በተሳካ ሁኔታ ተመድቧል!'));
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
                    alert('ቡድኑ ለትዕዛዙ በተሳካ ሁኔታ ተመድቧል!');
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('ስህተት: ' + (data.message || 'ቡድኑን መመደብ አልተቻለም'));
                }
            } catch (err) {
                alert('የሰርቨር ግንኙነት ችግር: ' + err.message);
            }
        }

        async function quickAssignTeam(orderId, teamId) {
            if (!teamId) return;
            try {
                const res = await apiFetch(`/api/orders/${orderId}/assign-team`, {
                    method: 'POST',
                    body: JSON.stringify({ team_id: parseInt(teamId) })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert('ቡድኑ ለትዕዛዙ በተሳካ ሁኔታ ተመድቧል!');
                    if (activeTab === 'reception') {
                        renderReceptionView(document.getElementById('app-viewport'));
                    } else {
                        loadActiveTab();
                    }
                } else {
                    alert('ስህተት: ' + (data.message || 'ቡድን መመደብ አልተቻለም'));
                }
            } catch (e) {
                alert('የግንኙነት ችግር: ' + e.message);
            }
        }

        function openDirectCustomerMessageModal(phone, name, tgId) {
            const container = document.getElementById('generic-modal-container');
            const cleanPhone = (phone || '').replace(/\s+/g, '');
            const decodedName = decodeURIComponent(name || 'ውድ ደንበኛ');
            const hasTg = tgId && String(tgId).length >= 5 && String(tgId) !== 'null';

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
                    <div class="bg-slate-900 border border-teal-500/50 rounded-3xl max-w-md w-full p-6 shadow-2xl space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-2xl bg-teal-500/20 text-teal-400 flex items-center justify-center">
                                    <i data-lucide="message-square" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-white">1-ለ-1 መልእክት መላኪያ</h3>
                                    <p class="text-xs text-slate-400">${decodedName} (${cleanPhone})</p>
                                </div>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>

                        <div class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የመልእክት ቻናል ምረጡ *</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-teal-500/40 bg-teal-950/20 cursor-pointer">
                                        <input type="radio" name="direct-msg-channel" value="telegram" ${hasTg ? 'checked' : ''} class="text-teal-500">
                                        <div>
                                            <span class="font-bold text-white block">Telegram Bot</span>
                                            <span class="text-[10px] text-teal-400">${hasTg ? 'መለያ ተገኝቷል' : 'ካልተገኘ ወደ መዝገብ ይዞራል (falls back to log only)'}</span>
                                        </div>
                                    </label>
                                    <label class="flex items-center gap-2 p-2.5 rounded-xl border border-slate-700 bg-slate-800/40 cursor-pointer">
                                        <input type="radio" name="direct-msg-channel" value="sms" ${!hasTg ? 'checked' : ''} class="text-cyan-500">
                                        <div>
                                            <span class="font-bold text-white block">ቀጥታ SMS (መዝገብ ብቻ)</span>
                                            <span class="text-[10px] text-slate-400">SMS API የለም — ይመዘገባል ብቻ</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የመልእክቱ ዝርዝር *</label>
                                <textarea id="direct-care-msg" rows="4" class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white text-xs leading-relaxed focus:border-teal-400 outline-none" placeholder="መልእክትዎን እዚህ ይጻፉ...">${decodedName !== 'ውድ ደንበኛ' ? 'ሰላም ' + decodedName + '፤ ' : ''}የሜሽ ክሊኒንግ አገልግሎታችንን በተመለከተ ለማነጋገር ፈልገን ነበር። ጥያቄ ወይም አስተያየት ካለዎት በ 0970075509 ሊደውሉልን ይችላሉ::</textarea>
                            </div>

                            <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                                <a href="sms:${cleanPhone}?&body=${encodeURIComponent('ሰላም ' + decodedName + '፤')}" class="text-[11px] text-teal-400 hover:underline flex items-center gap-1">
                                    <i data-lucide="smartphone" class="w-3.5 h-3.5"></i>
                                    <span>በ Messages App ክፈት</span>
                                </a>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl hover:bg-slate-700 cursor-pointer">ሰርዝ</button>
                                    <button type="button" id="btn-send-care-msg" onclick="submit1To1CustomerCareMessage('${cleanPhone}', '${tgId || ''}')" class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-cyan-600 hover:from-teal-400 hover:to-cyan-500 text-white font-black rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer">
                                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                                        <span>ላክ (Send)</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
        }

        async function submit1To1CustomerCareMessage(phone, tgId) {
            const channel = document.querySelector('input[name="direct-msg-channel"]:checked')?.value || 'telegram';
            const msg = (document.getElementById('direct-care-msg')?.value || '').trim();
            if (!msg) {
                alert('እባክዎ የመልእክት ዝርዝር ያስገቡ!');
                return;
            }

            const btn = document.getElementById('btn-send-care-msg');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = `<span class="animate-spin mr-1"></span> በመላክ ላይ...`;
            }

            try {
                const endpoint = channel === 'sms' ? '/api/customer-care/send-sms' : '/api/customer-care/send-telegram';
                const res = await apiFetch(endpoint, {
                    method: 'POST',
                    body: JSON.stringify({
                        phone: phone,
                        telegram_chat_id: tgId || null,
                        message: msg,
                    })
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    alert(' ' + data.message);
                    closeModal();
                } else {
                    alert('ስህተት፡ ' + (data.message || 'መልእክቱን መላክ አልተቻለም!'));
                }
            } catch (err) {
                alert('የኔትወርክ ስህተት፡ ' + err.message);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `<span>ላክ (Send)</span>`;
                }
            }
        }

        function openRecordCommentAndRatingModal(followupId, name, phone, orderNumber) {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-amber-500/50 rounded-3xl max-w-md w-full p-6 shadow-2xl">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                            <div>
                                <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 text-[10px] font-bold uppercase mb-1">
                                    ⭐ Phone Feedback
                                </div>
                                <h3 class="text-base font-black text-white">Record Comment & Rating (1–5 ★)</h3>
                                <p class="text-xs text-slate-300">${name} (${phone}) | Order #${orderNumber || ''}</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitRecordCommentAndRating(event, ${followupId})" class="space-y-4 text-xs">
                            <div>
                                <label class="block text-slate-300 font-bold mb-1.5">የደንበኛ የእርካታ ደረጃ (Rating 1–5 ★) *</label>
                                <select id="care-f-rating" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-bold focus:border-amber-400 outline-none">
                                    <option value="5">⭐⭐⭐⭐⭐ 5 Stars - እጅግ በጣም ረክቷል (Promote to Active)</option>
                                    <option value="4">⭐⭐⭐⭐ 4 Stars - ጥሩ / ረክቷል (Promote to Active)</option>
                                    <option value="3">⭐⭐⭐ 3 Stars - መካከለኛ (Promote to Active)</option>
                                    <option value="2">⭐⭐ 2 Stars - አልረካም (Escalate Complaint)</option>
                                    <option value="1">⭐ 1 Star - ቅሬታ አለው (Escalate Complaint)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-300 font-bold mb-1.5">የደንበኛው አስተያየት ወይም ማስታወሻ *</label>
                                <textarea id="care-f-notes" required rows="3" placeholder="በስልክ የተናገሩትን ቀጥተኛ አስተያየት እዚህ ይጻፉ... (ምሳሌ፦ ቡድኑ በሰዓቱ ደርሶ እጅግ በጣም በጥራት አጽድቷል)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white placeholder-slate-500 focus:border-amber-400 outline-none"></textarea>
                            </div>
                            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" id="care-f-submit-btn" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-slate-950 font-black rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer">
                                    <span>መዝግብ እና ወደ Normal Customer አሸጋግር</span>
                                    <span>&rarr;</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
        }

        async function submitRecordCommentAndRating(e, followupId) {
            e.preventDefault();
            const rating = parseInt(document.getElementById('care-f-rating').value);
            const notes = document.getElementById('care-f-notes').value.trim();
            const btn = document.getElementById('care-f-submit-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerText = 'በማስገባት ላይ...';
            }

            try {
                const res = await apiFetch(`/api/care/followups/${followupId}`, {
                    method: 'PUT',
                    body: JSON.stringify({
                        status: 'completed',
                        rating: rating,
                        notes: notes,
                        feedback_notes: notes,
                        satisfaction_score: rating
                    })
                });
                const data = await res.json();
                if (res.ok) {
                    alert(' የደንበኛው አስተያየት ተመዝግቧል! ደንበኛው ወዲያውኑ ወደ Normal Customers ተሸጋግሯል።');
                    closeModal();
                    loadActiveTab();
                } else {
                    alert(' ስህተት: ' + (data.message || 'አስተያየቱን መመዝገብ አልተቻለም'));
                }
            } catch (err) {
                alert('የሰርቨር ግንኙነት ችግር: ' + err.message);
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerText = 'መዝግብ እና ወደ Normal Customer አሸጋግር →';
                }
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
                <p class="text-xs text-slate-400">${name} ( ${phone})</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitFollowupCall(event, ${followupId})" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-400 mb-1">የደንበኛ የእርካታ ደረጃ (ከ 1 እስከ 5 ኮከብ) *</label>
                                <select id="f-rating" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                    <option value="5">⭐⭐⭐⭐⭐ እጅግ በጣም ረክቷል (5)</option>
                                    <option value="4">⭐⭐⭐⭐ ጥሩ / ረክቷል (4)</option>
                                    <option value="3">⭐⭐⭐ መካከለኛ (3)</option>
                                    <option value="2">⭐⭐ አልረካም (2)</option>
                                    <option value="1">⭐ ቅሬታ አለው / በጣም ቅር ተሰኝቷል (1)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">የደንበኛው አስተያየት *</label>
                                <textarea id="f-notes" required rows="3" placeholder="የደንበኛውን ቀጥተኛ አስተያየት እዚህ ይፃፉ... (ለምሳሌ፦ ምንጣፉ ልክ እንደ አዲስ ሆኖ ፀድቷል፣ አመስግነዋል)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
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
          alert(' የደንበኛው አስተያየት በተሳካ ሁኔታ ተመዝግቧል! ከክትትል ወረፋው ተወግዶ በደንበኛው የግል ታሪክ ውስጥ ገብቷል።');
                    closeModal();
                    loadActiveTab();
                } else {
          alert(' ስህተት: ' + (data.message || 'የጥሪ ውጤቱን መመዝገብ አልተቻለም'));
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
                            <h3 class="text-base font-bold text-white">የደንበኛ ቅሬታ መፍትሄ መዝገብ</h3>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitResolveComplaint(event, ${complaintId})" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-400 mb-1">የተወሰደ የመፍትሄ እርምጃ *</label>
                                <textarea id="comp-action" required rows="3" placeholder="የተወሰደውን የመፍትሄ እርምጃ እዚህ ይፃፉ... (ምሳሌ፦ ተቆጣጣሪ ተልኮ ድጋሚ በነጻ እንዲታጠብ ተደርጓል)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-3 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl">መፍትሄውን መዝግብ (ተፈቷል)</button>
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
                body: JSON.stringify({ resolution_action: action, resolution_notes: action, status: 'resolved' })
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

        function handleOrgSectorChange(val) {
            const box = document.getElementById('org-sector-other-box');
            const input = document.getElementById('org-sector-other-input');
            if (!box) return;
            if (val === 'other') {
                box.classList.remove('hidden');
                if (input) {
                    input.required = true;
                    input.focus();
                }
            } else {
                box.classList.add('hidden');
                if (input) {
                    input.required = false;
                }
            }
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
                                    <select id="org-sector" onchange="handleOrgSectorChange(this.value)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold">
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
                                        <option value="other">ሌላ (የራስዎን ዘርፍ ይጻፉ)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">ያነጋገሩት ኃላፊ ስም</label>
                                    <input type="text" id="org-contact" placeholder="ለምሳሌ፡ አቶ ዮሐንስ (ማናጀር)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                </div>
                            </div>
                            <div id="org-sector-other-box" class="hidden bg-slate-800/90 p-3 rounded-xl border border-cyan-500/60 space-y-1">
                                <label class="block text-cyan-300 font-bold">የስራ ዘርፉን ስም እዚህ ይጻፉ *</label>
                                <input type="text" id="org-sector-other-input" placeholder="ለምሳሌ፡ የመኪና ማጠቢያ፣ ፋብሪካ፣ ሱፐርማርኬት፣ የውበት ሳሎን..." class="w-full bg-slate-900 border border-cyan-500 rounded-xl px-3 py-2 text-white">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">ስልክ ቁጥር *</label>
                                    <input type="tel" id="org-phone" required placeholder="0911..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono">
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
            const sectorSelect = document.getElementById('org-sector').value;
            let finalIndustry = sectorSelect;
            if (sectorSelect === 'other') {
                const customVal = document.getElementById('org-sector-other-input')?.value.trim();
                finalIndustry = customVal || 'ሌላ';
            }
            await apiFetch('/api/sales/organizations', {
                method: 'POST',
                body: JSON.stringify({
                    name: document.getElementById('org-name').value,
                    industry: finalIndustry,
                    contact_person: document.getElementById('org-contact').value,
                    phone: document.getElementById('org-phone').value,
                    address: document.getElementById('org-subcity').value,
                })
            });
            closeModal();
            loadActiveTab();
        }

        function handleSalesVisitOrgChange(orgId) {
            if (!orgId || !window._salesOrgsCache) return;
            const selected = window._salesOrgsCache.find(o => o.id == orgId);
            const contactInput = document.getElementById('visit-contact');
            const phoneInput = document.getElementById('visit-phone');
            if (selected) {
                if (contactInput) {
                    contactInput.placeholder = selected.contact_person ? `አስቀድሞ የተመዘገበ፡ ${selected.contact_person}` : 'ያነጋገሩት ኃላፊ ስም';
                }
                if (phoneInput) {
                    phoneInput.placeholder = selected.phone ? `አስቀድሞ የተመዘገበ፡ ${selected.phone}` : 'ስልክ ቁጥር';
                }
            }
        }

        async function openLogSalesVisitModal() {
            const container = document.getElementById('generic-modal-container');
            const res = await apiFetch('/api/sales/organizations?per_page=100');
            const data = await res.json();
            const orgs = data.data || [];
            window._salesOrgsCache = orgs;

            const firstOrg = orgs[0] || null;
            const defaultContactPlaceholder = firstOrg && firstOrg.contact_person ? `አስቀድሞ የተመዘገበ፡ ${firstOrg.contact_person}` : 'ያነጋገሩት ኃላፊ ስም';
            const defaultPhonePlaceholder = firstOrg && firstOrg.phone ? `አስቀድሞ የተመዘገበ፡ ${firstOrg.phone}` : '0911...';

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
                                    <select id="visit-org" onchange="handleSalesVisitOrgChange(this.value)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold mb-2">
                                        ${orgs.map(o => `<option value="${o.id}">${o.name} (${o.industry || 'ተቋም'}) - ${o.address || 'አዲስ አበባ'}</option>`).join('')}
                                    </select>
                                ` : `
                                    <input type="text" id="visit-new-org-name" required placeholder="የድርጅቱ ስም (ለምሳሌ፡ አቢሲንያ ሆቴል)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white mb-2">
                                `}
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">ያነጋገሩት ኃላፊ ስም <span class="text-slate-500 font-normal text-[10px]">(አማራጭ - ከተቀየረ)</span></label>
                                    <input type="text" id="visit-contact" placeholder="${defaultContactPlaceholder}" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">ስልክ ቁጥር <span class="text-slate-500 font-normal text-[10px]">(አማራጭ - ከተቀየረ)</span></label>
                                    <input type="tel" id="visit-phone" placeholder="${defaultPhonePlaceholder}" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono">
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

                let contact = document.getElementById('visit-contact')?.value.trim();
                let phone = document.getElementById('visit-phone')?.value.trim();

                if (!orgId) {
                    const orgNameInput = document.getElementById('visit-new-org-name');
                    const orgName = orgNameInput ? orgNameInput.value.trim() : 'ተቋም';
                    const newOrgRes = await apiFetch('/api/sales/organizations', {
                        method: 'POST',
                        body: JSON.stringify({
                            name: orgName,
                            industry: 'office',
                            contact_person: contact || 'ኃላፊ',
                            phone: phone || '0911000000',
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

                // If contact person or phone not explicitly provided, use the registered organization's data
                if (orgId && window._salesOrgsCache) {
                    const matchedOrg = window._salesOrgsCache.find(o => o.id == orgId);
                    if (matchedOrg) {
                        if (!contact) contact = matchedOrg.contact_person || 'ኃላፊ';
                        if (!phone) phone = matchedOrg.phone || '';
                    }
                }

                const stage = document.getElementById('visit-stage').value;
                const notes = document.getElementById('visit-notes').value;

                const visitRes = await apiFetch('/api/sales/visits', {
                    method: 'POST',
                    body: JSON.stringify({
                        organization_id: orgId,
                        contact_person: contact || 'ኃላፊ',
                        phone: phone || '0911000000',
                        contact_phone: phone || '0911000000',
                        address: 'Addis Ababa',
                        stage: stage,
                        interest_level: 'medium',
                        notes: notes,
                        discussion_notes: notes,
                        visit_date: new Date().toISOString().split('T')[0],
                    })
                });

                if (visitRes.ok) {
                    alert('የመስክ ሽያጭ የስራ እንቅስቃሴው በተሳካ ሁኔታ በሰንጠረዡ ላይ ተመዝግቧል!');
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
            const todayEth = (typeof EC !== 'undefined' && EC.todayEth) ? EC.todayEth() : ((typeof EC !== 'undefined' && EC.toEth) ? EC.toEth(new Date()) : { year: 2019, month: 2, day: 19 });
            const dateStr = `${todayEth.day}/${todayEth.month}/${todayEth.year}`;
            const profNum = 'MSH-PRF-' + Math.floor(1000 + Math.random() * 9000);

            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4 overflow-y-auto">
                    <div class="bg-white text-slate-900 rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl space-y-6 my-auto max-h-[92vh] overflow-y-auto border border-slate-300">
                        <!-- Top Controls: Print and Close -->
                        <div class="flex flex-wrap items-center justify-between pb-4 border-b border-slate-200 print:hidden gap-3">
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 bg-blue-100 text-blue-900 font-extrabold text-xs rounded-full">ይፋዊ ፕሮፎርማ ሰነድ</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a id="prof-sms-link" href="#" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs rounded-xl shadow-lg flex items-center gap-1.5 cursor-pointer transition-all">
                                    <i data-lucide="smartphone" class="w-4 h-4"></i>
                                    <span>በስልክ SIM ላክ (SMS)</span>
                                </a>
                                <button onclick="window.print()" class="px-4 py-2 bg-gradient-to-r from-blue-700 to-cyan-600 hover:from-blue-600 hover:to-cyan-500 text-white font-extrabold text-xs rounded-xl shadow-lg flex items-center gap-2 cursor-pointer transition-all">
                                    <i data-lucide="printer" class="w-4 h-4"></i>
                                    <span>አትም / PDF</span>
                                </button>
                                <button onclick="closeModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs rounded-xl">ዝጋ</button>
                            </div>
                        </div>

                        <!-- OFFICIAL PRINTABLE PROFORMA SHEET -->
                        <div id="printable-proforma-area" class="space-y-6 text-slate-900 font-sans p-2">
                            <!-- Company Official Header & Logo -->
                            <div class="flex items-start justify-between border-b-2 border-blue-900 pb-5">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <img src="/logo.jpg" alt="Meash Cleaning Solution" class="w-14 h-14 rounded-2xl object-cover shadow-md border border-slate-300">
                                        <div>
                                            <h1 class="text-xl sm:text-2xl font-black text-blue-950 tracking-tight">MEASH CLEANING SOLUTION</h1>
                                            <p class="text-xs font-bold text-blue-800">ሜሽ የፅዳት እና የህንፃ አያያዝ አገልግሎት</p>
                                        </div>
                                    </div>
                                    <div class="text-[11px] text-slate-600 mt-2 space-y-0.5">
                                        <p>አድራሻ፡ <strong>አያት ተስፉ ሞል ፊት ለፊት፣ አዲስ አበባ</strong></p>
                                        <p>ስልክ፡ <strong>0970075509 / 0998142190</strong></p>
                                        <p>ቴሌግራም ቻናል፡ <a href="https://t.me/meashcleaningsolution" target="_blank" class="text-blue-700 font-bold hover:underline">@meashcleaningsolution</a> | ቀጥታ ለማነጋገር (Direct Contact)፡ <a href="https://t.me/meashcleaning" target="_blank" class="text-blue-700 font-bold hover:underline">@meashcleaning</a></p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="inline-block bg-blue-50 border border-blue-200 rounded-xl p-3 text-right">
                                        <span class="block text-xs uppercase font-black text-blue-950 tracking-wider">የዋጋ ማቅረቢያ (PROFORMA)</span>
                                        <span class="block text-xs font-bold text-slate-700 mt-1">ቀን፡ ${dateStr} ዓ.ም</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer / Organization Details (Editable fields) -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs">
                                <div>
                                    <span class="text-[10px] font-extrabold uppercase text-slate-500 block mb-1">የደንበኛው / የድርጅቱ መረጃ፦</span>
                                    <input type="text" id="prof-org-name" oninput="updateProformaSmsQr()" value="${defaultOrg || 'አቢሲንያ ሆቴል'}" placeholder="የድርጅቱ ስም" class="w-full font-bold text-sm bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 mb-1.5">
                                    <input type="text" id="prof-contact" value="${defaultContact || 'አቶ ብርሃኑ አሰፋ (ማናጀር)'}" placeholder="ያነጋገሩት ኃላፊ" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1 mb-1.5 text-xs">
                                </div>
                                <div>
                                    <span class="text-[10px] font-extrabold uppercase text-slate-500 block mb-1">አድራሻ እና ስልክ፦</span>
                                    <input type="tel" id="prof-phone" oninput="updateProformaSmsQr()" value="${defaultPhone || '0912121212'}" placeholder="ስልክ ቁጥር" class="w-full font-mono bg-white border border-slate-300 rounded-lg px-2.5 py-1 mb-1.5 text-xs">
                                    <input type="text" id="prof-address" value="${defaultAddress || 'አዲስ አበባ፣ ሃያት'}" placeholder="አድራሻ / ቦታ" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1 text-xs">
                                </div>
                            </div>

                            <!-- Services / Items Table (Interactive) -->
                            <div>
                                <div class="flex items-center justify-between mb-2 print:hidden">
                                    <span class="text-xs font-bold text-slate-700">የአገልግሎቶች እና የዋጋ ዝርዝር፦</span>
                                    <button type="button" onclick="addProformaRow()" class="px-3.5 py-1.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-100 font-bold text-xs flex items-center gap-1.5 cursor-pointer shadow-sm">
                                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                        <span>Add Item</span>
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
                      <span class="text-[8px] font-bold"> OFFICIAL STAMP </span>
                                            <span class="text-[14px] font-black text-blue-800 my-0.5">ሜሽ</span>
                                            <span class="text-[8px] font-extrabold uppercase">APPROVED</span>
                                        </div>
                                    </div>
                                    <div class="w-48 border-b border-slate-700 mt-1"></div>
                                    <span class="text-[10px] font-bold text-slate-700 mt-1">የተፈቀደው ኃላፊ ፊርማ እና ማህተም</span>
                                </div>
                            </div>
                        </div>

                        <!-- iPhone & Android Proforma SMS QR Code Card (On Screen Only) -->
                        <div class="p-4 rounded-2xl bg-blue-50/80 border border-blue-200 flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left print:hidden">
                            <div class="bg-white p-2 rounded-2xl shadow-md border border-blue-200 flex-shrink-0">
                                <img id="prof-sms-qr" src="" alt="Proforma SMS QR" class="w-24 h-24 object-contain">
                            </div>
                            <div class="space-y-1">
                                <div class="flex items-center justify-center sm:justify-start gap-1.5">
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold bg-blue-100 text-blue-900 border border-blue-200">ለማንኛውም ስማርት ስልክ</span>
                                    <span class="text-xs font-bold text-blue-950">የፕሮፎርማ SMS መላኪያ QR ኮድ</span>
                                </div>
                                <p class="text-[11px] text-slate-700">በስልክዎ ካሜራ ይህንን QR ኮድ ስካን ሲያደርጉ የፕሮፎርማውን ዝርዝር እና አጠቃላይ ዋጋ የያዘውን SMS በስልክዎ መልእክት መላኪያ አፕ ያዘጋጅልዎታል::</p>
                                <p class="text-[10px] text-slate-500 font-mono">ተቀባይ ደንበኛ ስልክ፦ <span id="prof-sms-phone-display" class="text-blue-700 font-bold">+251...</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            lucide.createIcons();
            recalcProforma();
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

            updateProformaSmsQr();
        }

        function updateProformaSmsQr() {
            const phone = document.getElementById('prof-phone')?.value || '0970075509';
            const org = document.getElementById('prof-org-name')?.value || 'ደንበኛ';
            const grand = document.getElementById('prof-grand-total')?.innerText || '';
            const intlPhone = formatPhoneForSms(phone);
            const msg = `ሰላም ${org}፣ የሜሽ ክሊኒንግ ፕሮፎርማ ተዘጋጅቷል። ጠቅላላ ክፍያ፡ ${grand}። ለበለጠ መረጃ፡ 0970075509 / 0998142190`;

            const disp = document.getElementById('prof-sms-phone-display');
            if (disp) {
                disp.textContent = intlPhone;
            }

            const qr = document.getElementById('prof-sms-qr');
            if (qr) {
                // iPhone Camera natively opens Messages app with RFC 5724 standard sms:phone?&body=
                const payload = `sms:${intlPhone}?&body=${encodeURIComponent(msg)}`;
                setLocalQr(qr, payload);
            }
            const link = document.getElementById('prof-sms-link');
            if (link) {
                link.href = `sms:${intlPhone}?&body=${encodeURIComponent(msg)}`;
            }
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
                                <p class="text-xs text-slate-400 mt-0.5">${isWorking ? 'ቡድኑ በስራ ስምሪት ላይ ስለሆነ ወቅታዊ የስራ ደረጃው ይታያል።' : 'ቡድኑ ንቁ ስራ ላይ አይደለም (የግል መገኛ ለደህንነት ተደብቋል)።'}</p>
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

                        <!-- Clean Team Status & Schedule Overview (No Map Required) -->
                        <div class="p-5 rounded-2xl bg-slate-800/80 border border-slate-700 space-y-3">
                            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center text-lg"></div>
                                <div>
                                    <h4 class="text-sm font-bold text-white">${teamName} — የስራ ስምሪት ዝርዝር</h4>
                                    <p class="text-xs text-slate-400 mt-0.5">የተመደቡ ንቁ ስራዎች፡ <strong class="text-cyan-400">${activeJobs} ስራዎች</strong></p>
                                </div>
                            </div>
                            <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 text-xs text-slate-300">
                                <strong>አድራሻ እና የስራ ክልል፡</strong> አዲስ አበባ (አያት ተስፉ ሞል ፊት ለፊት እና አጠቃላይ የጽዳት ክልሎች)
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-800 text-xs">
                            <span class="text-slate-400">${isWorking ? 'የስራ ሁኔታ: ንቁ ስምሪት ላይ' : 'የደህንነት ሁኔታ: ጥበቃ የተደረገለት'}</span>
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
                    
                                    </div>
                                </div>
                            `,
                            iconSize: [44, 44],
                            iconAnchor: [22, 22]
                        });

                        L.marker([lat, lng], { icon: customIcon })
                            .addTo(map)
              .bindPopup(`<b>${teamName}</b><br>መሪ፡ ${leaderName}<br> ${phone}`)
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
                <input type="date" id="postpone-date" required value="${tomorrowStr}" min="${new Date().toISOString().split('T')[0]}" onchange="const eth=EC.toEth(this.value); document.getElementById('postpone-eth-hint').textContent=' ' + EC.formatEth(eth.year, eth.month, eth.day);" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                <p id="postpone-eth-hint" class="text-[10px] text-amber-400 font-bold mt-1"> ${EC.formatEth(tomorrow)}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1.5">የቀጠሮ ሰዓት (ጠዋት ወይም ከሰዓት ብቻ) *</label>
                                <select id="postpone-slot" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-amber-500">
                  <option value="morning"> ጥዋት (2:00 - 6:00 ጠዋቱ ETH • Morning 8:00 AM - 12:00 PM)</option>
                  <option value="afternoon"> ከሰዓት (7:00 - 11:00 ከሰዓቱ ETH • Afternoon 1:00 PM - 5:00 PM)</option>
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

                            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" id="btn-submit-postpone" class="px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-400 hover:to-orange-500 text-white text-xs font-extrabold rounded-xl shadow-lg flex items-center gap-1.5">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    <span>ቀጠሮውን አስተላልፍ</span>
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
          alert(' ' + data.message);
                    // Refresh current view
                    switchRole(currentUser.role);
                } else {
          alert(' ስህተት: ' + (data.message || 'ቀጠሮውን ማስተላለፍ አልተቻለም::'));
                }
            } catch (err) {
        alert(' የግንኙነት ስህተት: ' + err.message);
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
                    reg.showNotification('ሜሽ የጽዳት አገልግሎት | MEASH CLEANING', {
            body: ' ማሳወቂያዎች በተሳካ ሁኔታ ተፈቅደዋል! የትዕዛዝ እና የቀጠሮ ማንቂያዎች ይደርሱዎታል።',
                        icon: '/assets/icon-192.png',
                        badge: '/assets/icon-192.png',
                        vibrate: [200, 100, 200]
                    });
                } else {
                    new Notification('ሜሽ የጽዳት አገልግሎት | MEASH CLEANING', {
            body: ' ማሳወቂያዎች በተሳካ ሁኔታ ተፈቅደዋል!',
                        icon: '/assets/icon-192.png'
                    });
                }
        alert(' ማሳወቂያዎች ተፈቅደዋል! የስልክዎ/የኮምፒውተርዎ የስክሪን ማሳወቂያ ደርሶዎታል።');
            } else {
        alert(' ማሳወቂያዎች አልተፈቀዱም። እባክዎ በብሮውዘር ቅንብር ውስጥ ይፍቀዱ።');
            }
        }

        // ==========================================
        // REAL DATA UTILITIES: CSV EXPORT & PRINT INVOICE
        // ==========================================
        async function downloadOrdersCsv() {
            try {
                const res = await apiFetch('/api/orders?per_page=1000');
                const data = await res.json();
                const orders = data.data || [];
                if (orders.length === 0) return alert('ምንም የሚወርድ የትዕዛዝ መረጃ የለም።');

                const headers = ['Order Number', 'Customer Name', 'Phone', 'Subcity', 'Appointment Date', 'Time Slot', 'Assigned Team', 'Total (ETB)', 'Payment Status', 'Order Status', 'Source'];
                const rows = orders.map(o => [
                    `"${o.order_number || ''}"`,
                    `"${(o.customer?.full_name || o.customer_name || '').replace(/"/g, '""')}"`,
                    `"${o.customer?.phone || o.customer_phone || ''}"`,
                    `"${(o.subcity || o.customer?.subcity || '').replace(/"/g, '""')}"`,
                    `"${o.eth_appointment_date || o.appointment_date || ''}"`,
                    `"${o.appointment_time_slot || ''}"`,
                    `"${(o.assigned_team?.team_name || o.assignedTeam?.team_name || 'Unassigned').replace(/"/g, '""')}"`,
                    parseFloat(o.total || 0),
                    `"${o.payment_status || 'unpaid'}"`,
                    `"${o.order_status || 'new'}"`,
                    `"${o.source || 'website'}"`
                ]);

                const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(r => r.join(','))].join('\r\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', `meash_orders_${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            } catch (e) {
                alert('CSV ማውረድ አልተቻለም: ' + e.message);
            }
        }
        window.downloadOrdersCsv = downloadOrdersCsv;

        async function downloadFinanceCsv() {
            try {
                const [expRes, payRes] = await Promise.all([
                    apiFetch('/api/finance/expenses?per_page=1000'),
                    apiFetch('/api/finance/payments?per_page=1000')
                ]);
                const expData = await expRes.json();
                const payData = await payRes.json();
                const expenses = expData.data || (Array.isArray(expData) ? expData : []);
                const payments = payData.data || (Array.isArray(payData) ? payData : []);

                const headers = ['Type', 'Date', 'Reference / Order', 'Category / Method', 'Description / Customer', 'Amount (ETB)'];
                const rows = [];

                payments.forEach(p => {
                    rows.push([
                        '"INCOME (ገቢ)"',
                        `"${p.eth_date || p.payment_date || ''}"`,
                        `"${p.order?.order_number || p.reference_number || ''}"`,
                        `"${p.payment_method || ''}"`,
                        `"${(p.customer?.full_name || 'Customer').replace(/"/g, '""')}"`,
                        parseFloat(p.amount || 0)
                    ]);
                });

                expenses.forEach(e => {
                    rows.push([
                        '"EXPENSE (ወጪ)"',
                        `"${e.eth_date || e.date || ''}"`,
                        `"${e.expense_number || e.reference_number || ''}"`,
                        `"${e.category || ''}"`,
                        `"${(e.description || '').replace(/"/g, '""')}"`,
                        -parseFloat(e.amount || 0)
                    ]);
                });

                const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(r => r.join(','))].join('\r\n');
                const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.setAttribute('href', url);
                link.setAttribute('download', `meash_financial_report_${new Date().toISOString().split('T')[0]}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
            } catch (e) {
                alert('ፋይናንስ CSV ማውረድ አልተቻለም: ' + e.message);
            }
        }
        window.downloadFinanceCsv = downloadFinanceCsv;

        async function printOrderInvoice(orderId) {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = '<div class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center text-white"><i data-lucide="loader-2" class="w-8 h-8 animate-spin text-cyan-400"></i></div>';
            lucide.createIcons();

            try {
                const res = await apiFetch(`/api/orders/${orderId}`);
                const data = await res.json();
                const o = data.data || data;

                const customerName = o.customer?.full_name || o.customer_name || 'ውድ ደንበኛ';
                const customerPhone = o.customer?.phone || o.customer_phone || '—';
                const customerAddress = o.address || o.customer?.address || 'አዲስ አበባ';
                const orderNum = o.order_number || ('ORD-' + o.id);
                const apptDate = o.eth_appointment_date || o.appointment_date || '—';
                const items = o.items || [];
                const total = parseFloat(o.total || 0);
                const paymentStatus = o.payment_status === 'paid' ? 'የተከፈለ (PAID)' : (o.payment_status === 'pending_approval' ? 'ለመጽደቅ የቀረበ' : 'ያልተከፈለ (UNPAID)');

                container.innerHTML = `
                    <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
                        <div class="bg-white text-slate-900 rounded-3xl max-w-2xl w-full p-6 sm:p-8 shadow-2xl space-y-6 my-8 print:p-0 print:m-0 print:shadow-none print:w-full print:max-w-none">
                            <!-- Top Controls (Hidden during print) -->
                            <div class="flex items-center justify-between pb-4 border-b border-slate-200 print:hidden">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-cyan-700 bg-cyan-50 px-2.5 py-1 rounded-lg border border-cyan-200">የክፍያ ደረሰኝ (Tax Invoice)</span>
                                    <span class="text-xs font-mono text-slate-500">${escapeHtml(orderNum)}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button onclick="window.print()" class="px-4 py-2 bg-gradient-to-r from-blue-700 to-cyan-600 hover:from-blue-600 hover:to-cyan-500 text-white font-extrabold text-xs rounded-xl shadow-lg flex items-center gap-2 cursor-pointer transition">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                        <span>አትም (Print Invoice)</span>
                                    </button>
                                    <button onclick="closeModal()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl cursor-pointer">ዝጋ</button>
                                </div>
                            </div>

                            <!-- Printable Invoice Area -->
                            <div id="printable-order-invoice" class="space-y-6">
                                <!-- Header & Logo -->
                                <div class="flex items-start justify-between border-b-2 border-cyan-800 pb-5">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-10 h-10 rounded-xl bg-cyan-700 text-white flex items-center justify-center font-black text-xl">M</div>
                                            <div>
                                                <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900">MEASH CLEANING SOLUTION</h1>
                                                <p class="text-[11px] font-bold text-cyan-700 tracking-wide">ሜሽ የጽዳት አገልግሎት</p>
                                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">"Your Environment, Our Responsibility."</p>
                                            </div>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-2">ስልክ፡ 0970075509 / 0998142190 | አዲስ አበባ፣ ኢትዮጵያ</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest block">ደረሰኝ / INVOICE</span>
                                        <span class="text-base font-black text-cyan-800 font-mono block mt-1">${escapeHtml(orderNum)}</span>
                                        <span class="text-xs text-slate-500 block mt-1">ቀን፡ ${apptDate}</span>
                                    </div>
                                </div>

                                <!-- Customer & Order Meta -->
                                <div class="grid grid-cols-2 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs">
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">ለተገልጋይ (Billed To):</span>
                                        <strong class="text-sm font-black text-slate-900 block mt-0.5">${escapeHtml(customerName)}</strong>
                                        <span class="text-slate-600 block mt-0.5">ስልክ፡ ${escapeHtml(customerPhone)}</span>
                                        <span class="text-slate-600 block">አድራሻ፡ ${escapeHtml(customerAddress)}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">የክፍያ ሁኔታ (Status):</span>
                                        <span class="inline-block mt-1 px-3 py-1 rounded-full text-xs font-black ${o.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300'}">
                                            ${paymentStatus}
                                        </span>
                                        <span class="text-[11px] text-slate-500 block mt-1.5">ቡድን፡ ${escapeHtml(o.assigned_team?.team_name || o.assignedTeam?.team_name || 'መደበኛ')}</span>
                                    </div>
                                </div>

                                <!-- Items Table -->
                                <div class="border border-slate-200 rounded-2xl overflow-hidden">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-slate-100 text-slate-600 uppercase text-[10px] tracking-wider border-b border-slate-200">
                                            <tr>
                                                <th class="p-3">ተ.ቁ</th>
                                                <th class="p-3">የአገልግሎት ዝርዝር</th>
                                                <th class="p-3 text-center">መጠን (Qty)</th>
                                                <th class="p-3 text-right">የአንዱ ዋጋ (ETB)</th>
                                                <th class="p-3 text-right">ጠቅላላ ዋጋ (ETB)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            ${items.length === 0 ? `
                                                <tr>
                                                    <td colspan="5" class="p-4 text-center text-slate-400 italic">መደበኛ የጽዳት አገልግሎት</td>
                                                </tr>
                                            ` : items.map((it, idx) => `
                                                <tr>
                                                    <td class="p-3 text-slate-400 font-mono">${idx + 1}</td>
                                                    <td class="p-3 font-bold text-slate-800">${escapeHtml(it.service?.name_am || it.item_name || 'የጽዳት አገልግሎት')}</td>
                                                    <td class="p-3 text-center font-bold text-slate-700">${parseFloat(it.quantity || 1)}</td>
                                                    <td class="p-3 text-right font-mono text-slate-700">${parseFloat(it.unit_price || 0).toLocaleString()}</td>
                                                    <td class="p-3 text-right font-black text-slate-900">${parseFloat(it.subtotal || 0).toLocaleString()}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                        <tfoot class="bg-slate-50 border-t-2 border-slate-300 text-xs">
                                            <tr>
                                                <td colspan="4" class="p-3 text-right font-black text-slate-900">ጠቅላላ ክፍያ (Total Amount):</td>
                                                <td class="p-3 text-right font-black text-cyan-800 text-sm font-mono">${total.toLocaleString()} ETB</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>

                                <!-- Footer Signatures -->
                                <div class="pt-6 border-t border-slate-200 flex justify-between items-end text-xs text-slate-500">
                                    <div>
                                        <p class="font-bold text-slate-700">ሜሽ የጽዳት አገልግሎት</p>
                                        <p class="text-[10px] mt-0.5">አገልግሎታችንን ስለመረጡ እናመሰግናለን!</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="w-36 border-b border-slate-400 mb-1"></div>
                                        <span class="text-[10px] text-slate-400">የተረካቢ / የተከፋይ ፊርማ</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (e) {
                alert('ደረሰኝ መክፈት አልተቻለም: ' + e.message);
                closeModal();
            }
        }
        window.printOrderInvoice = printOrderInvoice;

        // ==========================================
        // CREATE SUBSCRIPTION MODAL & SUBMIT
        // ==========================================
        async function openCreateSubscriptionModal() {
            const container = document.getElementById('generic-modal-container');
            const custRes = await apiFetch('/api/customers?per_page=50');
            const custData = await custRes.json();
            const customers = custData.data || [];

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-lg w-full p-6 shadow-2xl space-y-4 my-8">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div>
                                <h3 class="text-base font-bold text-white flex items-center gap-2">
                                    <i data-lucide="repeat" class="w-5 h-5 text-cyan-400"></i>
                                    <span>አዲስ የደንበኝነት እቅድ መዝግብ (New Subscription)</span>
                                </h3>
                                <p class="text-xs text-slate-400">ሳምንታዊ፣ የሁለት ሳምንት ወይም ወርሃዊ ተደጋጋሚ የጽዳት ውል ይመዝገቡ።</p>
                            </div>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white font-bold text-lg">&times;</button>
                        </div>

                        <form onsubmit="submitNewSubscription(event)" class="space-y-3.5 text-xs">
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ደንበኛ ይምረጡ *</label>
                                <select id="sub-cust-id" onchange="toggleInlineCustSub(this.value)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold">
                                    <option value="">-- ከነባር ደንበኞች ይምረጡ --</option>
                                    ${customers.map(c => `<option value="${c.id}">${c.full_name} (${c.phone}) - ${c.subcity || ''}</option>`).join('')}
                                    <option value="new">+ አዲስ ደንበኛ አስገባ</option>
                                </select>
                            </div>

                            <div id="sub-inline-cust" class="hidden p-3 bg-slate-950/60 rounded-xl border border-slate-800 space-y-2">
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[11px] text-slate-400 font-bold mb-0.5">የደንበኛ ሙሉ ስም *</label>
                                        <input type="text" id="sub-inline-name" placeholder="ሙሉ ስም" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] text-slate-400 font-bold mb-0.5">ስልክ ቁጥር *</label>
                                        <input type="tel" id="sub-inline-phone" placeholder="0911000000" class="w-full bg-slate-800 border border-slate-700 rounded-lg px-2.5 py-1.5 text-white font-mono">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">የእቅድ ዓይነት (Plan) *</label>
                                    <select id="sub-plan-type" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-bold">
                                        <option value="weekly">ሳምንታዊ (Weekly - 15% ቅናሽ)</option>
                                        <option value="biweekly">የሁለት ሳምንት (Bi-Weekly - 10% ቅናሽ)</option>
                                        <option value="monthly">ወርሃዊ (Monthly - 5% ቅናሽ)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">የመነሻ ዋጋ (Base Price) *</label>
                                    <input type="number" id="sub-base-price" required min="100" placeholder="ለምሳሌ፡ 2000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono">
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የአገልግሎቱ ዝርዝር (Service Summary) *</label>
                                <input type="text" id="sub-service-summary" required placeholder="ምሳሌ፡ መደበኛ የሳሎን እና የመኝታ ቤት ጽዳት" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">ስራ የሚጀምርበት ቀን *</label>
                                    <input type="date" id="sub-start-date" required value="${new Date().toISOString().split('T')[0]}" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-slate-300 font-bold mb-1">ተመራጭ ሰዓት</label>
                                    <select id="sub-time-slot" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                        <option value="morning">ጠዋት (Morning 8:30 AM - 12:30 PM)</option>
                                        <option value="afternoon">ከሰዓት (Afternoon 1:30 PM - 5:30 PM)</option>
                                        <option value="full_day">ሙሉ ቀን (Full Day)</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-slate-300 font-bold mb-1">ተጨማሪ ማስታወሻ</label>
                                <textarea id="sub-notes" rows="2" placeholder="ልዩ ትዕዛዝ ወይም መመሪያ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                            </div>

                            <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" id="btn-save-sub" class="px-5 py-2 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-bold rounded-xl flex items-center gap-1.5 shadow-lg">
                                    <span>ደንበኝነት መዝግብ</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }
        window.openCreateSubscriptionModal = openCreateSubscriptionModal;

        function toggleInlineCustSub(val) {
            const el = document.getElementById('sub-inline-cust');
            if (!el) return;
            if (val === 'new') {
                el.classList.remove('hidden');
                document.getElementById('sub-inline-name').setAttribute('required', 'required');
                document.getElementById('sub-inline-phone').setAttribute('required', 'required');
            } else {
                el.classList.add('hidden');
                document.getElementById('sub-inline-name')?.removeAttribute('required');
                document.getElementById('sub-inline-phone')?.removeAttribute('required');
            }
        }
        window.toggleInlineCustSub = toggleInlineCustSub;

        async function submitNewSubscription(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-sub');
            if (btn) { btn.disabled = true; btn.innerText = 'በመመዝገብ ላይ...'; }

            const custSelect = document.getElementById('sub-cust-id').value;
            const payload = {
                plan_type: document.getElementById('sub-plan-type').value,
                base_price: parseFloat(document.getElementById('sub-base-price').value),
                service_summary: document.getElementById('sub-service-summary').value,
                start_date: document.getElementById('sub-start-date').value,
                preferred_time_slot: document.getElementById('sub-time-slot').value,
                notes: document.getElementById('sub-notes').value || null,
            };

            if (custSelect === 'new') {
                payload.customer_name = document.getElementById('sub-inline-name').value.trim();
                payload.phone = document.getElementById('sub-inline-phone').value.trim();
            } else if (custSelect) {
                payload.customer_id = parseInt(custSelect);
            } else {
                alert('እባክዎ ደንበኛ ይምረጡ!');
                if (btn) { btn.disabled = false; btn.innerText = 'ደንበኝነት መዝግብ'; }
                return;
            }

            try {
                const res = await apiFetch('/api/subscriptions', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (res.ok) {
                    alert('የደንበኝነት እቅዱ በተሳካ ሁኔታ በዳታቤዝ ተመዝግቧል!');
                    closeModal();
                    loadActiveTab();
                } else {
                    alert('ስህተት: ' + (data.message || 'መመዝገብ አልተቻለም'));
                }
            } catch (err) {
                alert('የሰርቨር ችግር: ' + err.message);
            } finally {
                if (btn) { btn.disabled = false; btn.innerText = 'ደንበኝነት መዝግብ'; }
            }
        }
        window.submitNewSubscription = submitNewSubscription;

        async function forcePurgePwaCache() {
            try {
                if ('caches' in window) {
                    const keys = await caches.keys();
                    await Promise.all(keys.map(k => caches.delete(k)));
                }
                if ('serviceWorker' in navigator) {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    for (const reg of registrations) {
                        await reg.unregister();
                    }
                }
            } catch (e) {
                console.warn('Cache purge error:', e);
            }
            window.location.href = '/app?v=' + Date.now();
        }

        // Register Service Worker with Auto-Update
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then((reg) => {
                    console.log('Meash ServiceWorker registered successfully:', reg.scope);
                    try {
                        reg.update();
                        if (reg.waiting) {
                            reg.waiting.postMessage({ type: 'SKIP_WAITING' });
                        }
                    } catch (_) {}
                }).catch((err) => {
                    console.warn('Meash ServiceWorker registration failed:', err);
                });

                navigator.serviceWorker.addEventListener('controllerchange', () => {
                    console.log('New ServiceWorker controller activated');
                });
            });
        }
    </script>
</body>
</html>
