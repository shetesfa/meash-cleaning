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
                { id: 'teams', label_en: 'Cleaning Teams', label_am: 'የፅዳት ቡድኖች', icon: 'truck' },
                { id: 'sales', label_en: 'Sales CRM', label_am: 'የውጭ ሽያጭ CRM', icon: 'trending-up' },
                { id: 'finance', label_en: 'Finance & Profit', label_am: 'ፋይናንስ እና ትርፍ', icon: 'wallet' },
                { id: 'care', label_en: 'Customer Care', label_am: 'የደንበኞች እንክብካቤ', icon: 'heart-handshake' },
                { id: 'campaigns', label_en: 'ማስታወቂያ እና ፕሮሞሽን', label_am: 'የማስታወቂያ ዘመቻ', icon: 'send' },
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
                                                <button onclick="confirmAndAssignOrder(${b.id})" class="px-3 py-1.5 rounded-lg bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold">አረጋግጥ እና ቡድን መድብ</button>
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
        async function renderSalesModule(container) {
            try {
                const res = await apiFetch('/api/sales/pipeline');
                const pipeline = await res.json();

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-8">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider">የተቋማትና የሆቴሎች አካውንቶች</span>
                                <h2 class="text-2xl sm:text-3xl font-black text-white">የውጭ ሽያጭ እና የድርጅቶች የስራ ሂደት (CRM)</h2>
                            </div>
                            <div class="flex items-center gap-3">
                                <button onclick="openAddOrganizationModal()" class="px-4 py-2.5 bg-slate-800 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 flex items-center gap-2">
                                    <i data-lucide="building" class="w-4 h-4 text-cyan-400"></i>
                                    <span>+ አዲስ ድርጅት</span>
                                </button>
                                <button onclick="openLogSalesVisitModal()" class="px-4 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2">
                                    <i data-lucide="map-pin" class="w-4 h-4"></i>
                                    <span>+ ጉብኝት መዝግብ</span>
                                </button>
                            </div>
                        </div>

                        <!-- KANBAN PIPELINE COLUMNS -->
                        <div class="flex gap-4 overflow-x-auto pb-6">
                            ${Object.keys(pipeline).map(key => {
                                const stage = pipeline[key];
                                return `
                                    <div class="w-72 shrink-0 bg-slate-900 border border-slate-800 rounded-3xl p-4 flex flex-col justify-between max-h-[70vh]">
                                        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                                            <h4 class="text-xs font-extrabold text-white">${stage.title}</h4>
                                            <span class="px-2 py-0.5 rounded-full bg-slate-800 text-cyan-400 text-xs font-bold">${stage.count}</span>
                                        </div>
                                        <div class="space-y-3 overflow-y-auto flex-1 pr-1">
                                            ${(stage.leads || []).map(lead => `
                                                <div class="p-3.5 rounded-2xl bg-slate-800/90 border border-slate-700/80 space-y-2">
                                                    <span class="text-xs font-black text-white block">${lead.organization?.name}</span>
                                                    <p class="text-[11px] text-slate-400">Contact: <strong>${lead.contact_person}</strong> (${lead.contact_position || 'Manager'})</p>
                                                    <div class="flex justify-between items-center text-[10px] pt-2 border-t border-slate-700">
                                                        <span class="px-2 py-0.5 rounded bg-slate-900 text-cyan-400 font-bold uppercase">${lead.interest_level}</span>
                                                        <button onclick="advanceSalesStage(${lead.id}, '${key}')" class="text-cyan-400 font-bold hover:underline">ደረጃ አሻግር &rarr;</button>
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                container.innerHTML = `<div class="p-6 text-center text-red-400">Failed to load Sales pipeline: ${err.message}</div>`;
            }
        }

        async function advanceSalesStage(visitId, currentStage) {
            const nextStages = {
                new_lead: 'visited',
                visited: 'contact_established',
                contact_established: 'interested',
                interested: 'proforma_requested',
                proforma_requested: 'proforma_sent',
                proforma_sent: 'negotiation',
                negotiation: 'won',
            };
            const next = nextStages[currentStage] || 'won';
            await apiFetch(`/api/sales/visits/${visitId}/stage`, {
                method: 'POST',
                body: JSON.stringify({ stage: next }),
            });
            loadActiveTab();
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
                                                <td class="p-4 text-slate-300 font-semibold">${o.assigned_team?.team_name || '<span class="text-red-400">ቡድን አልተመደበም</span>'}</td>
                                                <td class="p-4 font-extrabold text-white">${parseFloat(o.total).toLocaleString()} ETB</td>
                                                <td class="p-4">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase ${o.order_status === 'completed' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-cyan-500/20 text-cyan-400'}">
                                                        ${o.order_status}
                                                    </span>
                                                </td>
                                                <td class="p-4 text-right space-x-1.5 whitespace-nowrap">
                                                    <button onclick="confirmAndAssignOrder(${o.id})" class="px-2.5 py-1 bg-blue-950/90 hover:bg-blue-900 text-blue-300 font-bold text-[11px] rounded-lg border border-blue-500/40 transition-colors" title="የፅዳት ቡድን መድብ">🚐 ቡድን መድብ</button>
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
                            <div>
                                <label class="block text-slate-300 font-bold mb-1">የደንበኛ ስልክ ቁጥር *</label>
                                <input type="text" id="direct-sms-phone" required value="${cleanPhone}" placeholder="0911223344" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-mono focus:border-cyan-500 focus:outline-none">
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
                                <textarea id="direct-sms-message" required rows="4" placeholder="መልእክትዎን እዚህ ይጻፉ..." class="w-full bg-slate-800 border border-slate-700 rounded-xl p-3 text-white focus:outline-none focus:border-cyan-500">ሰላም ${safeName}፣ የሜሽ ክሊኒንግ ቀጠሮዎ በትክክል ተረጋግጧል። በሰዓቱ እንገኛለን። እናመሰግናለን!</textarea>
                                <span class="text-[10px] text-slate-500 block mt-1">በአስተዳዳሪው ፈቃድ ብቻ በቀጥታ ለተጠቃሚው ይላካል።</span>
                            </div>

                            <div class="flex justify-end gap-2 pt-2 border-t border-slate-800">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl hover:bg-slate-700">ሰርዝ</button>
                                <button type="submit" id="btn-send-sms" class="px-5 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 text-white font-extrabold rounded-xl shadow-lg flex items-center gap-2">
                                    <i data-lucide="send" class="w-4 h-4"></i>
                                    <span>ኤስኤምኤስ ላክ (Send SMS)</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            lucide.createIcons();
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
                const res = await apiFetch('/api/finance/profit-report');
                const data = await res.json();

                container.innerHTML = `
                    <div class="max-w-7xl mx-auto space-y-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-black text-white">ፋይናንስ እና የተጣራ ትርፍ ትንተና</h2>
                                <p class="text-xs text-slate-400 mt-1">የቀጥታ ገቢ፣ የተመደቡ ወጪዎች እና የተጣራ ትርፍ ስሌት።</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <button onclick="openRecordExpenseModal()" class="px-4 py-2.5 bg-red-600/80 hover:bg-red-600 text-white font-bold text-xs rounded-xl shadow-lg flex items-center gap-2">
                                    <i data-lucide="minus-circle" class="w-4 h-4"></i>
                                    <span>- ወጪ መዝግብ</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div class="p-6 rounded-3xl bg-emerald-950/30 border border-emerald-500/30">
                                <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider block">የተረጋገጠ ጠቅላላ ገቢ</span>
                                <span class="text-3xl font-black text-white mt-2 block">${parseFloat(data.revenue).toLocaleString()} ETB</span>
                            </div>
                            <div class="p-6 rounded-3xl bg-red-950/30 border border-red-500/30">
                                <span class="text-xs font-bold text-red-400 uppercase tracking-wider block">አጠቃላይ የስራ ማስኬጃ ወጪ</span>
                                <span class="text-3xl font-black text-white mt-2 block">${parseFloat(data.expenses).toLocaleString()} ETB</span>
                            </div>
                            <div class="p-6 rounded-3xl bg-cyan-950/30 border border-cyan-500/30">
                                <span class="text-xs font-bold text-cyan-400 uppercase tracking-wider block">የተጣራ ትርፍ (${data.profit_margin}%)</span>
                                <span class="text-3xl font-black text-cyan-300 mt-2 block">${parseFloat(data.net_profit).toLocaleString()} ETB</span>
                            </div>
                        </div>

                        <!-- Expense Breakdown by Category -->
                        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800">
                            <h4 class="text-sm font-bold text-white mb-4">የወጪዎች ዝርዝር በየምድቡ</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                ${(data.expense_breakdown || []).map(b => `
                                    <div class="p-3.5 rounded-2xl bg-slate-800 border border-slate-700">
                                        <span class="text-xs text-slate-400 uppercase font-bold block">${b.category}</span>
                                        <span class="text-lg font-black text-white mt-1 block">${parseFloat(b.total_amount).toLocaleString()} ETB</span>
                                    </div>
                                `).join('')}
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
        function openCreateOrderModal() {
            const container = document.getElementById('generic-modal-container');
            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-xl w-full p-6 shadow-2xl my-8">
                        <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
                            <h3 class="text-lg font-bold text-white">አዲስ የፅዳት ትዕዛዝ መመዝገቢያ</h3>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white">&times;</button>
                        </div>
                        <form onsubmit="submitAdminOrder(event)" class="space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">የደንበኛ ሙሉ ስም *</label>
                                    <input type="text" id="mo-cust-name" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">ስልክ ቁጥር *</label>
                                    <input type="tel" id="mo-cust-phone" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">ክፍለ ከተማ *</label>
                                    <input type="text" id="mo-subcity" required placeholder="e.g. Bole" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">ሙሉ አድራሻ / ሰፈር *</label>
                                    <input type="text" id="mo-address" required placeholder="Specific address..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">የቀጠሮ ቀን *</label>
                                    <input type="date" id="mo-date" required onchange="const eth=EC.toEth(this.value); document.getElementById('mo-eth-hint').textContent='🗓 ' + EC.formatEth(eth.year, eth.month, eth.day);" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none">
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

                            <!-- Item lines -->
                            <div class="p-3 rounded-2xl bg-slate-800/80 border border-slate-700 space-y-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">የሚፀዱ እቃዎች / አገልግሎት</span>
                                <div class="grid grid-cols-3 gap-2">
                                    <input type="text" id="mo-item-name" value="Sofa Cleaning" class="bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                                    <input type="number" id="mo-item-qty" value="5" placeholder="Qty" class="bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                                    <input type="number" id="mo-item-price" value="350" placeholder="Price" class="bg-slate-900 border border-slate-700 rounded-lg px-2.5 py-1.5 text-xs text-white">
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-slate-800 text-slate-300 text-xs font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-5 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded-xl shadow-lg">ትዕዛዝ መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            const todayIso = new Date().toISOString().split('T')[0];
            document.getElementById('mo-date').value = todayIso;
            const todayEth = EC.toEth(todayIso);
            document.getElementById('mo-eth-hint').textContent = '🗓 ' + EC.formatEth(todayEth.year, todayEth.month, todayEth.day);
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
                                    <i data-lucide="truck" class="w-6 h-6 text-cyan-400"></i>
                                    <span>የፅዳት ቡድኖች እና የሰራተኞች አስተዳደር</span>
                                </h2>
                                <p class="text-xs text-slate-400 mt-1">የመስክ ቡድኖች፣ የሰራተኞች መዝገብ፣ አዳዲስ ቅጥር እና የመኪና ስምሪት ማስተዳደሪያ።</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2.5">
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
                                    <div class="w-12 h-12 rounded-2xl bg-slate-800 text-cyan-400 flex items-center justify-center mx-auto text-xl">🚐</div>
                                    <p class="text-sm font-bold text-white">እስካሁን የተፈጠረ የፅዳት ቡድን የለም</p>
                                    <p class="text-xs text-slate-400 max-w-sm mx-auto">ከላይ ያለውን "+ አዲስ የፅዳት ቡድን ፍጠር" የሚለውን ቁልፍ በመጫን የመጀመሪያውን ቡድን ይመዝግቡ።</p>
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
                                                    <span>🚐 መኪና / ታርጋ:</span>
                                                    <span class="text-white font-semibold font-mono">${t.vehicle_plate || 'ታርጋ የለውም'}</span>
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
                                                <button onclick="openInAppLiveRideMap(null, ${t.current_latitude || 9.025}, ${t.current_longitude || 38.746}, '${t.team_name}', '${t.phone || ''}', 'Addis Ababa', '${t.team_name}', ${t.current_latitude || 9.025}, ${t.current_longitude || 38.746})" class="px-3 py-1.5 rounded-xl bg-cyan-950/80 hover:bg-cyan-900 border border-cyan-500/40 text-cyan-300 font-bold flex items-center gap-1.5 transition-colors cursor-pointer">
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
                                    <p class="text-xs text-slate-400 mt-0.5">በሜሽ ክሊኒንግ ሲስተም ውስጥ የተመዘገቡ ሁሉም ሰራተኞች እና የስራ ድርሻቸው።</p>
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
                                <p class="text-[11px] text-slate-400">አዲስ የመስክ ቡድን፣ መሪ እና መኪና ይመድቡ።</p>
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
                                <label class="block text-slate-300 font-bold mb-1">የመኪና ታርጋ ቁጥር</label>
                                <input type="text" id="new-team-plate" placeholder="3-B12345 AA" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-mono">
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
                vehicle_plate: document.getElementById('new-team-plate').value.trim() || null,
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
                        <div>
                            <h2 class="text-2xl font-black text-white">የደንበኞች እንክብካቤ፣ ቅሬታዎች እና የአስተያየት ማጽደቂያ</h2>
                            <p class="text-xs text-slate-400 mt-1">የድህረ-ፅዳት ክትትል ጥሪዎች፣ ክፍት ቅሬታዎች እና በድረ-ገጽ ላይ የሚታዩ የተጠቃሚ አስተያየቶች ማጣሪያ።</p>
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
                                <p class="text-xs text-slate-400 mt-1">የበዓላት ቅናሽ ማስታወቂያዎች እና አውቶሜትድ መልዕክቶች።</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            ${campaigns.length === 0 ? '<div class="p-8 text-slate-500 col-span-3 text-center">ምንም የተዘጋጀ የማስታወቂያ ዘመቻ የለም።</div>' : ''}
                            ${campaigns.map(c => `
                                <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider">${c.channel} Campaign</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${c.status === 'sent' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-amber-500/20 text-amber-400'}">${c.status}</span>
                                    </div>
                                    <h3 class="text-base font-bold text-white">${c.name}</h3>
                                    <p class="text-xs text-slate-400 italic">"${c.message_template}"</p>
                                    <div class="pt-3 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                                        <span>ዒላማ: <strong class="text-white">${c.target_audience}</strong></span>
                                        <span>የተላከላቸው: <strong class="text-cyan-400">${c.recipients_count || 0}</strong></span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (err) {
                container.innerHTML = `<div class="p-6 text-red-400">Failed to load ማስታወቂያ እና ፕሮሞሽን Campaigns: ${err.message}</div>`;
            }
        }

        // ==========================================
        // 11. CALENDAR MODULE
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
                const c = data.data || data;
                const orders = c.orders || [];

                container.innerHTML = `
                    <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
                        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-2xl w-full p-6 shadow-2xl my-8">
                            <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-4">
                                <div>
                                    <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-wider">${c.customer_code}</span>
                                    <h3 class="text-xl font-bold text-white">${c.full_name}</h3>
                                </div>
                                <button onclick="closeModal()" class="text-slate-400 hover:text-white text-2xl font-bold">&times;</button>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                                <div class="p-3 bg-slate-800 rounded-xl">
                                    <span class="text-[10px] text-slate-400 block">ስልክ</span>
                                    <span class="text-xs font-bold text-white">${c.phone}</span>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-xl">
                                    <span class="text-[10px] text-slate-400 block">መገኛ</span>
                                    <span class="text-xs font-bold text-white">${c.subcity || 'Addis Ababa'}</span>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-xl">
                                    <span class="text-[10px] text-slate-400 block">Total ትዕዛዞች</span>
                                    <span class="text-xs font-bold text-cyan-400">${orders.length}</span>
                                </div>
                                <div class="p-3 bg-slate-800 rounded-xl">
                                    <span class="text-[10px] text-slate-400 block">የደንበኛ አይነት</span>
                                    <span class="text-xs font-bold text-emerald-400 uppercase">${c.customer_type}</span>
                                </div>
                            </div>

                            <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3">የቀድሞ አገልግሎቶች ታሪክ እና ሂደት</h4>
                            <div class="space-y-2.5 max-h-60 overflow-y-auto pr-1">
                                ${orders.length === 0 ? '<p class="text-xs text-slate-500 py-4 text-center">ምንም የቀድሞ ትዕዛዝ አልተመዘገበም።</p>' : ''}
                                ${orders.map(o => `
                                    <div class="p-3 bg-slate-800/80 border border-slate-700/60 rounded-xl flex items-center justify-between text-xs">
                                        <div>
                                            <span class="font-bold text-white block">${o.order_number} (${o.eth_appointment_date || o.appointment_date})</span>
                                            <span class="text-[11px] text-slate-400">${(o.items || []).map(i=>i.item_name).join(', ') || 'Cleaning Service'}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="font-bold text-cyan-300 block">${parseFloat(o.total || 0).toLocaleString()} ETB</span>
                                            <span class="text-[10px] uppercase font-bold text-emerald-400">${o.order_status}</span>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>

                            <div class="flex justify-end pt-4 border-t border-slate-800 mt-4">
                                <button onclick="closeModal()" class="px-5 py-2 bg-slate-800 text-slate-300 text-xs font-bold rounded-xl hover:bg-slate-700">ዝጋ</button>
                            </div>
                        </div>
                    </div>
                `;
                lucide.createIcons();
            } catch (e) {
                alert('Failed to load profile: ' + e.message);
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
                                    <span>የተመደበ ቡድን: <strong class="text-cyan-300">${o.assigned_team?.team_name || 'ቡድን አልተመደበም'}</strong></span>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-4 border-t border-slate-800 mt-4">
                                ${o.order_status !== 'completed' && o.order_status !== 'cancelled' ? `
                                    <button onclick="closeModal(); confirmAndAssignOrder(${o.id});" class="px-4 py-2 bg-blue-950/80 hover:bg-blue-900 border border-blue-500/40 text-blue-300 text-xs font-bold rounded-xl flex items-center gap-1.5 transition-colors">
                                        <i data-lucide="truck" class="w-4 h-4 text-blue-400"></i>
                                        <span>🚐 ቡድን መድብ (Assign Team)</span>
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
            const res = await apiFetch('/api/teams');
            const data = await res.json();
            const teams = Array.isArray(data) ? data : (data.data || []);

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-sm w-full p-6 shadow-2xl">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                            <h3 class="text-base font-bold text-white flex items-center gap-2">
                                <i data-lucide="truck" class="w-4 h-4 text-cyan-400"></i>
                                <span>የፅዳት ቡድን መድብ</span>
                            </h3>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white font-bold">&times;</button>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-xs text-slate-400 font-semibold">የመስክ ቡድን ይምረጡ</label>
                            ${teams.length === 0 ? `
                                <div class="p-3 rounded-xl bg-amber-950/50 border border-amber-500/30 text-amber-300 text-xs">
                                    እስካሁን ምንም የተመዘገበ ቡድን የለም።
                                </div>
                                <button onclick="closeModal(); openCreateTeamModal();" class="w-full py-2 bg-cyan-600 hover:bg-cyan-500 text-white font-bold text-xs rounded-xl">
                                    + አዲስ ቡድን ፍጠር
                                </button>
                            ` : `
                                <select id="assign-team-select" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white">
                                    ${teams.map(t => `<option value="${t.id}">${t.team_name} (${t.leader?.name || 'Leader'} - 📞 ${t.phone || t.leader?.phone || 'N/A'})</option>`).join('')}
                                </select>
                                <div class="flex justify-end gap-2 pt-3">
                                    <button onclick="closeModal()" class="px-3.5 py-2 bg-slate-800 text-slate-300 text-xs font-bold rounded-xl">ሰርዝ</button>
                                    <button onclick="submitTeamAssignment(${orderId})" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-bold rounded-xl flex items-center gap-1.5">
                                        <span>አረጋግጥ እና ስምሪት ስጥ</span>
                                    </button>
                                </div>
                            `}
                        </div>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }

        async function submitTeamAssignment(orderId) {
            const teamId = document.getElementById('assign-team-select').value;
            await apiFetch(`/api/orders/${orderId}/assign-team`, {
                method: 'POST',
                body: JSON.stringify({ team_id: teamId })
            });
            closeModal();
            loadActiveTab();
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
            const notes = document.getElementById('f-notes').value;

            await apiFetch(`/api/care/followups/${followupId}`, {
                method: 'PUT',
                body: JSON.stringify({
                    status: 'completed',
                    satisfaction_score: rating,
                    feedback_notes: notes,
                })
            });
            closeModal();
            loadActiveTab();
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
                                <input type="text" id="org-name" required placeholder="e.g. Radisson Blu Hotel" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">የስራው ዘርፍ *</label>
                                    <select id="org-sector" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                        <option value="hotel">ሆቴል / ሎጅ</option>
                                        <option value="bank">ባንክ / የፋይናንስ ተቋም</option>
                                        <option value="office">ድርጅት / ቢሮ</option>
                                        <option value="embassy">ኤምባሲ / መንግስታዊ ያልሆነ ድርጅት</option>
                                        <option value="mall">ሞል / የገበያ ማዕከል</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">ተጠሪ ሰው *</label>
                                    <input type="text" id="org-contact" required placeholder="e.g. Ato Yohannes" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">Phone *</label>
                                    <input type="tel" id="org-phone" required placeholder="0911..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">ክፍለ ከተማ *</label>
                                    <input type="text" id="org-subcity" required placeholder="Bole / Kirkos" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                </div>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-3 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-4 py-2 bg-cyan-600 text-white font-bold rounded-xl">ድርጅቱን መዝግብ</button>
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
                    sector: document.getElementById('org-sector').value,
                    contact_person: document.getElementById('org-contact').value,
                    phone: document.getElementById('org-phone').value,
                    subcity: document.getElementById('org-subcity').value,
                })
            });
            closeModal();
            loadActiveTab();
        }

        async function openLogSalesVisitModal() {
            const container = document.getElementById('generic-modal-container');
            const res = await apiFetch('/api/sales/organizations');
            const data = await res.json();
            const orgs = data.data || [];

            container.innerHTML = `
                <div class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
                    <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 shadow-2xl">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                            <h3 class="text-base font-bold text-white">የመስክ ሽያጭ ጉብኝት መዝግብ</h3>
                            <button onclick="closeModal()" class="text-slate-400 hover:text-white text-xl font-bold">&times;</button>
                        </div>
                        <form onsubmit="submitLogSalesVisit(event)" class="space-y-3 text-xs">
                            <div>
                                <label class="block text-slate-400 mb-1">ድርጅት / ተቋም *</label>
                                <select id="visit-org" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                    ${orgs.map(o => `<option value="${o.id}">${o.name} (${o.contact_person || o.sector})</option>`).join('')}
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-slate-400 mb-1">የድርድር ደረጃ *</label>
                                    <select id="visit-stage" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                        <option value="lead">New Lead</option>
                                        <option value="qualified">Qualified Opportunity</option>
                                        <option value="proforma_sent">Proforma Sent</option>
                                        <option value="negotiation">In Negotiation</option>
                                        <option value="won">Won Contract</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-slate-400 mb-1">የስራው ግምት ዋጋ (ብር)</label>
                                    <input type="number" id="visit-deal" value="50000" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                </div>
                            </div>
                            <div>
                                <label class="block text-slate-400 mb-1">የስብሰባው / የውይይቱ ዝርዝር ነጥቦች *</label>
                                <textarea id="visit-notes" required rows="3" placeholder="Met with Procurement manager. Discussed annual carpet & facade cleaning..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-2">
                                <button type="button" onclick="closeModal()" class="px-3 py-2 bg-slate-800 text-slate-300 font-bold rounded-xl">ሰርዝ</button>
                                <button type="submit" class="px-4 py-2 bg-cyan-600 text-white font-bold rounded-xl">ጉብኝቱን መዝግብ</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
        }

        async function submitLogSalesVisit(e) {
            e.preventDefault();
            await apiFetch('/api/sales/visits', {
                method: 'POST',
                body: JSON.stringify({
                    organization_id: document.getElementById('visit-org').value,
                    stage: document.getElementById('visit-stage').value,
                    estimated_value: parseFloat(document.getElementById('visit-deal').value || 0),
                    discussion_notes: document.getElementById('visit-notes').value,
                    visit_date: new Date().toISOString().split('T')[0],
                })
            });
            closeModal();
            loadActiveTab();
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
