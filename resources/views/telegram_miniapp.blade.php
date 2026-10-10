<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ሜሽ የፅዳት አገልግሎት | Telegram Mini App</title>
    <!-- Telegram WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Ethiopic:wght@400;600;700;800&display=swap');
        body {
            font-family: 'Noto Sans Ethiopic', 'Plus Jakarta Sans', sans-serif;
            background-color: var(--tg-theme-bg-color, #0F172A);
            color: var(--tg-theme-text-color, #F8FAFC);
        }
    </style>
</head>
<body class="p-4 pb-20">
    <div class="max-w-md mx-auto">
        <!-- Header & Language Toggle -->
        <div class="flex items-center justify-between gap-3 mb-5 p-3.5 rounded-2xl bg-slate-800/90 border border-slate-700/80 shadow-lg">
            <div class="flex items-center gap-3">
                <img src="/logo.jpg" alt="Mesh Cleaning Logo" class="w-11 h-11 rounded-xl object-cover border border-cyan-500/40 shadow-md shadow-cyan-500/20 shrink-0">
                <div>
                    <h1 class="text-sm font-extrabold text-white tracking-tight" id="txt-app-title">ሜሽ የፅዳት አገልግሎት</h1>
                    <p class="text-[11px] text-cyan-400 font-semibold" id="tg-user-greeting">እንኳን ደህና መጡ 👋</p>
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
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2" id="lbl-step1">1. አገልግሎት ይምረጡ (Choose Service)</label>
                <div class="grid grid-cols-2 gap-2" id="tma-services-grid">
                    <button type="button" onclick="selectTmaService('sofa', 350, 'መቀመጫ / seats', 'የሶፋ ጥልቅ እጥበት', 'Sofa Deep Cleaning')" class="tma-svc-btn p-3 rounded-xl border border-cyan-500 bg-cyan-950/40 text-left transition-all">
                        <span class="text-xl block mb-1">🛋</span>
                        <span class="text-xs font-bold text-white block svc-title">የሶፋ ጥልቅ እጥበት</span>
                        <span class="text-[10px] text-cyan-400 font-semibold svc-price">350 ብር / መቀመጫ</span>
                    </button>
                    <button type="button" onclick="selectTmaService('carpet', 80, 'ካሬ / sqm', 'የምንጣፍ ማጠብና ስቲም', 'Carpet Washing')" class="tma-svc-btn p-3 rounded-xl border border-slate-700 bg-slate-900 text-left transition-all">
                        <span class="text-xl block mb-1">🧶</span>
                        <span class="text-xs font-bold text-white block svc-title">የምንጣፍ ማጠብ</span>
                        <span class="text-[10px] text-cyan-400 font-semibold svc-price">80 ብር / ካሬ</span>
                    </button>
                    <button type="button" onclick="selectTmaService('mattress', 600, 'ፍራሽ / piece', 'የፍራሽ ሳኒታይዜሽን', 'Mattress Sanitization')" class="tma-svc-btn p-3 rounded-xl border border-slate-700 bg-slate-900 text-left transition-all">
                        <span class="text-xl block mb-1">🛏</span>
                        <span class="text-xs font-bold text-white block svc-title">የፍራሽ ሳኒታይዜሽን</span>
                        <span class="text-[10px] text-cyan-400 font-semibold svc-price">600 ብር / ፍራሽ</span>
                    </button>
                    <button type="button" onclick="selectTmaService('glass', 70, 'ካሬ / sqm', 'የመስታወት ፅዳት', 'Glass Cleaning')" class="tma-svc-btn p-3 rounded-xl border border-slate-700 bg-slate-900 text-left transition-all">
                        <span class="text-xl block mb-1">🪟</span>
                        <span class="text-xs font-bold text-white block svc-title">የመስታወት ፅዳት</span>
                        <span class="text-[10px] text-cyan-400 font-semibold svc-price">70 ብር / ካሬ</span>
                    </button>
                </div>
            </div>

            <!-- 2. Quantity Counter -->
            <div class="p-3.5 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-white block" id="tma-selected-service-name">የሶፋ ጥልቅ እጥበት</span>
                    <span class="text-[11px] text-slate-400" id="tma-unit-desc">መቀመጫ / SEATS</span>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="changeQty(-1)" class="w-9 h-9 rounded-xl bg-slate-700 hover:bg-slate-600 text-white font-bold text-lg flex items-center justify-center active:scale-95 transition-all">-</button>
                    <span id="tma-qty" class="text-base font-extrabold text-white w-8 text-center">5</span>
                    <button type="button" onclick="changeQty(1)" class="w-9 h-9 rounded-xl bg-slate-700 hover:bg-slate-600 text-white font-bold text-lg flex items-center justify-center active:scale-95 transition-all">+</button>
                </div>
            </div>

            <!-- Total Preview -->
            <div class="p-3.5 rounded-xl bg-gradient-to-r from-cyan-950/80 to-blue-950/80 border border-cyan-500/40 flex justify-between items-center shadow-md">
                <span class="text-xs font-semibold text-slate-300" id="lbl-total-est">የተገመተ ጠቅላላ ዋጋ:</span>
                <span id="tma-total-preview" class="text-base font-extrabold text-cyan-300">1,750.00 ብር</span>
            </div>

            <!-- 3. Address & Location -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider" id="lbl-step2">2. አድራሻ በአዲስ አበባ (Location)</label>
                    <button type="button" onclick="detectTmaLocation()" id="btn-tma-gps" class="px-2.5 py-1 rounded-lg bg-cyan-500/20 hover:bg-cyan-500/30 border border-cyan-500/40 text-cyan-300 font-bold text-[10px] flex items-center gap-1 transition-all">
                        <i data-lucide="map-pin" class="w-3 h-3 text-cyan-400"></i>
                        <span id="btn-tma-gps-txt">📍 የቀጥታ GPS ያዝ</span>
                    </button>
                </div>
                <div id="tma-gps-alert" class="hidden mb-2 p-2 rounded-xl bg-emerald-950/70 border border-emerald-500/30 text-emerald-300 text-[11px] flex items-center justify-between">
                    <span id="tma-gps-status">📍 GPS ተመዝግቧል</span>
                    <a id="tma-gps-link" href="#" target="_blank" class="text-cyan-300 underline font-bold text-[10px]">ካርታ እይ</a>
                </div>
                <input type="hidden" id="tma-lat" value="">
                <input type="hidden" id="tma-lng" value="">
                <select id="tma-subcity" required class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500 mb-2">
                    <option value="" id="opt-select-subcity">ክፍለ ከተማ ይምረጡ...</option>
                    <option value="Bole">ቦሌ (Bole)</option>
                    <option value="Yeka">የካ (Yeka)</option>
                    <option value="Kirkos">ቂርቆስ (Kirkos)</option>
                    <option value="Lideta">ልደታ (Lideta)</option>
                    <option value="Nifas Silk">ንፋስ ስልክ ላፍቶ (Nifas Silk)</option>
                    <option value="Kolfe">ኮልፌ ቀራኒዮ (Kolfe Keranio)</option>
                    <option value="Arada">አራዳ (Arada)</option>
                    <option value="Gulele">ጉለሌ (Gulele)</option>
                    <option value="Akaki">አቃቂ ቃሊቲ (Akaki Kality)</option>
                    <option value="Lemi Kura">ለሚ ኩራ (Lemi Kura)</option>
                </select>
                <input type="text" id="tma-address" required placeholder="የሰፈር ስም፣ ልዩ መለያ እና የቤት ቁጥር..." class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500">
            </div>

            <!-- 4. Schedule -->
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2" id="lbl-date">3. የቀጠሮ ቀን</label>
                    <input type="date" id="tma-date" required onchange="updateTmaEthDateHint(this.value)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500">
                    <p id="tma-eth-date-hint" class="text-[10px] text-cyan-400 font-bold mt-1">🗓 — ዓ.ም</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2" id="lbl-slot">የቀጠሮ ሰዓት</label>
                    <select id="tma-slot" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500">
                        <option value="morning" id="opt-morning">🌅 ጠዋት (2:00–6:00 ጠዋቱ ETH)</option>
                        <option value="afternoon" id="opt-afternoon">☀️ ከሰዓት (7:00–11:00 ከሰዓቱ ETH)</option>
                    </select>
                </div>
            </div>

            <!-- Recurring Subscription Plan in Mini App -->
            <div class="p-3.5 rounded-xl bg-slate-800/90 border border-cyan-500/30">
                <label class="block text-xs font-bold text-cyan-300 uppercase tracking-wider mb-2">🔄 የፅዳት ድግግሞሽ እና የቅናሽ እቅድ</label>
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
                            <span class="text-[9px] text-cyan-400 font-extrabold">15% ቅናሽ</span>
                        </div>
                    </label>
                    <label class="p-2 rounded-lg bg-slate-900 border border-slate-700 flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="tma_sub_plan" value="biweekly" onchange="updateTotal()" class="text-cyan-500">
                        <div>
                            <span class="font-bold text-white block text-[11px]">በ2 ሳምንት</span>
                            <span class="text-[9px] text-cyan-400 font-extrabold">10% ቅናሽ</span>
                        </div>
                    </label>
                    <label class="p-2 rounded-lg bg-slate-900 border border-slate-700 flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="tma_sub_plan" value="monthly" onchange="updateTotal()" class="text-cyan-500">
                        <div>
                            <span class="font-bold text-white block text-[11px]">በየወሩ</span>
                            <span class="text-[9px] text-cyan-400 font-extrabold">5% ቅናሽ</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- 5. Contact Phone -->
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2" id="lbl-step3">4. የእርስዎ መረጃ (Contact Details)</label>
                <input type="text" id="tma-name" required placeholder="ሙሉ ስምዎ" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500 mb-2">
                <input type="tel" id="tma-phone" required placeholder="ስልክ ቁጥር (ለምሳሌ 0911223344)" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500">
            </div>

            <button type="submit" id="tma-submit-btn" class="w-full py-3.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-bold text-sm shadow-xl shadow-cyan-500/25 flex items-center justify-center gap-2 active:scale-98 transition-all">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span id="txt-submit-btn">ቀጠሮውን አረጋግጥ (Submit Booking)</span>
            </button>
        </form>

        <!-- Confirmation State -->
        <div id="tma-success" class="hidden text-center py-8">
            <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto mb-4 border border-emerald-500/30 shadow-lg shadow-emerald-500/10">
                <i data-lucide="check-circle-2" class="w-10 h-10"></i>
            </div>
            <h3 class="text-xl font-black text-white mb-2" id="txt-success-title">🎉 ቀጠሮዎ በተሳካ ሁኔታ ተመዝግቧል!</h3>
            <p class="text-xs text-slate-300 mb-5 leading-relaxed" id="txt-success-desc">የሜሽ ሪሴፕሽን ሰራተኞቻችን የቡድን ምደባውን በስልክ ደውለው ያረጋግጡልዎታል::</p>
            <div class="p-4 rounded-2xl bg-slate-800/90 border border-slate-700 mb-6 shadow-md">
                <span class="text-[11px] font-semibold text-slate-400 block mb-1" id="lbl-tracking-id">የትዕዛዝ መለያ ቁጥር (Order ID)</span>
                <span class="text-2xl font-black text-cyan-400 tracking-wider" id="tma-result-id">ORD-2026-0001</span>
            </div>
            <button onclick="window.Telegram?.WebApp?.close()" class="w-full py-3 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold rounded-xl transition-all" id="btn-close-app">ዝጋ (Close)</button>
        </div>

        <p class="text-center text-[11px] text-slate-500 pt-3 border-t border-slate-800/80">
            ኦፊሴላዊ ቦት: <span class="text-cyan-400 font-bold">@meash_cleaning_solution_bot</span> | ቻናል: <a href="https://t.me/meashdeepcleaning" target="_blank" class="text-cyan-400 font-bold hover:underline">@meashdeepcleaning</a> | ስልክ: <a href="tel:0970075509" class="text-slate-300 font-bold hover:underline">0970075509</a>
        </p>
    </div>

    <script>
        let currentLang = localStorage.getItem('tma_lang') || 'am';
        let currentSvc = { code: 'sofa', price: 350, unit: 'መቀመጫ / seats', nameAm: 'የሶፋ ጥልቅ እጥበት', nameEn: 'Sofa Deep Cleaning' };
        let qty = 5;
        let tgUser = null;

        const i18nDict = {
            am: {
                appTitle: 'ሜሽ የፅዳት አገልግሎት',
                greetingDefault: 'እንኳን ደህና መጡ 👋',
                step1: '1. አገልግሎት ይምረጡ',
                step2: '2. አድራሻ በአዲስ አበባ',
                step3: '4. የእርስዎ መረጃ',
                stepDate: '3. የቀጠሮ ቀን',
                stepSlot: 'የቀጠሮ ሰዓት',
                selectSubcity: 'ክፍለ ከተማ ይምረጡ...',
                addressPlaceholder: 'የሰፈር ስም፣ ልዩ መለያ እና የቤት ቁጥር...',
                namePlaceholder: 'ሙሉ ስምዎ',
                phonePlaceholder: 'ስልክ ቁጥር (ለምሳሌ 0911223344)',
                totalEst: 'የተገመተ ጠቅላላ ዋጋ:',
                submitBtn: 'ቀጠሮውን አረጋግጥ (Submit Booking)',
                submitting: 'በመመዝገብ ላይ...',
                successTitle: '🎉 ቀጠሮዎ በተሳካ ሁኔታ ተመዝግቧል!',
                successDesc: 'የሜሽ ሪሴፕሽን ሰራተኞቻችን የቡድን ምደባውን በስልክ ደውለው ያረጋግጡልዎታል::',
                trackingId: 'የትዕዛዝ መለያ ቁጥር (Order ID)',
                closeApp: 'ዝጋ (Close)',
                currency: 'ብር',
                morning: 'ጠዋት (ከ 3:00 - 6:00)',
                afternoon: 'ከሰዓት (ከ 8:00 - 11:00)',
            },
            en: {
                appTitle: 'MESH CLEANING SOLUTION',
                greetingDefault: 'Welcome to Mesh 👋',
                step1: '1. Choose Service',
                step2: '2. Location in Addis Ababa',
                step3: '4. Your Contact Details',
                stepDate: '3. Appointment Date',
                stepSlot: 'Time Slot',
                selectSubcity: 'Select Subcity...',
                addressPlaceholder: 'Specific address, landmark & house number...',
                namePlaceholder: 'Your Full Name',
                phonePlaceholder: 'Phone Number (e.g. 0911223344)',
                totalEst: 'Estimated Total:',
                submitBtn: 'Confirm & Book Now',
                submitting: 'Submitting Booking...',
                successTitle: '🎉 Booking Confirmed!',
                successDesc: 'Our receptionist will call you shortly to confirm team dispatch.',
                trackingId: 'Tracking / Order ID',
                closeApp: 'Close Mini App',
                currency: 'ETB',
                morning: 'Morning (9:00 AM - 12:00 PM)',
                afternoon: 'Afternoon (2:00 PM - 5:00 PM)',
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
                el.innerText = '🗓 ' + EC.formatEth(val);
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
                    document.getElementById('tg-user-greeting').innerText = currentLang === 'am' ? `ሰላም ${welcomeName} 👋` : `Hello ${welcomeName} 👋`;
                    document.getElementById('tma-name').value = `${tgUser.first_name || ''} ${tgUser.last_name || ''}`.trim();
                }
            }

            // Check URL query parameter (e.g. ?service=carpet)
            const urlParams = new URLSearchParams(window.location.search);
            const svcParam = urlParams.get('service');
            if (svcParam === 'carpet') selectTmaService('carpet', 80, 'ካሬ / sqm', 'የምንጣፍ ማጠብና ስቲም', 'Carpet Washing');
            else if (svcParam === 'mattress') selectTmaService('mattress', 600, 'ፍራሽ / piece', 'የፍራሽ ሳኒታይዜሽን', 'Mattress Sanitization');
            else if (svcParam === 'glass') selectTmaService('glass', 70, 'ካሬ / sqm', 'የመስታወት ፅዳት', 'Glass Cleaning');

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
        });

        async function loadTmaLiveServices() {
            try {
                const res = await fetch('/api/public/services');
                const data = await res.json();
                const list = Array.isArray(data) ? data : (data.data || []);
                if (list.length > 0) {
                    const grid = document.getElementById('tma-services-grid');
                    if (grid) {
                        grid.innerHTML = list.map((svc, i) => {
                            const price = parseFloat(svc.base_price || 0);
                            const unit = svc.pricing_unit_am || svc.pricing_unit || 'አገልግሎት';
                            const nameAm = svc.name_am || svc.name_en;
                            const nameEn = svc.name_en || svc.name_am;
                            const icon = svc.icon || (svc.category === 'furniture' ? '🛋' : (svc.category === 'carpet' ? '🧶' : (svc.category === 'mattress' ? '🛏' : (svc.category === 'glass' ? '🪟' : '🏠'))));
                            const isSelected = i === 0;
                            if (isSelected) {
                                currentSvc = { code: svc.id, price, unit, nameAm, nameEn };
                                document.getElementById('tma-selected-service-name').innerText = currentLang === 'am' ? nameAm : nameEn;
                                document.getElementById('tma-unit-desc').innerText = unit.toUpperCase();
                            }
                            return `
                                <button type="button" onclick="selectTmaService('${svc.id}', ${price}, '${unit}', '${nameAm.replace(/'/g, "\\'")}', '${nameEn.replace(/'/g, "\\'")}')" class="tma-svc-btn p-3 rounded-xl border ${isSelected ? 'border-cyan-500 bg-cyan-950/40' : 'border-slate-700 bg-slate-900'} text-left transition-all">
                                    <span class="text-xl block mb-1">${icon}</span>
                                    <span class="text-xs font-bold text-white block svc-title">${nameAm}</span>
                                    <span class="text-[10px] text-cyan-400 font-semibold svc-price">${price.toLocaleString()} ብር / ${unit}</span>
                                </button>
                            `;
                        }).join('');
                        updateTotal();
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
            document.getElementById('opt-morning').innerText = dict.morning;
            document.getElementById('opt-afternoon').innerText = dict.afternoon;

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

        function selectTmaService(code, price, unit, nameAm, nameEn) {
            currentSvc = { code, price, unit, nameAm, nameEn };
            qty = code === 'carpet' ? 10 : (code === 'sofa' ? 5 : 1);
            document.getElementById('tma-qty').innerText = qty;
            document.getElementById('tma-selected-service-name').innerText = currentLang === 'am' ? nameAm : nameEn;
            document.getElementById('tma-unit-desc').innerText = unit.toUpperCase();

            document.querySelectorAll('.tma-svc-btn').forEach(btn => {
                btn.classList.remove('border-cyan-500', 'bg-cyan-950/40');
                btn.classList.add('border-slate-700', 'bg-slate-900');
            });
            event?.currentTarget?.classList?.add('border-cyan-500', 'bg-cyan-950/40');
            updateTotal();
        }

        function changeQty(delta) {
            qty = Math.max(1, qty + delta);
            document.getElementById('tma-qty').innerText = qty;
            updateTotal();
        }

        const tmaSubDiscounts = { one_time: 0, weekly: 15, biweekly: 10, monthly: 5 };

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

        function updateTmaPlan() {
            updateTotal();
        }

        function detectTmaLocation() {
            const btn = document.getElementById('btn-tma-gps-txt');
            if (!navigator.geolocation) {
                alert('Geolocation is not supported on this device.');
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
                        document.getElementById('tma-gps-status').innerHTML = `⚠️ በኮምፒውተር/IP የተገመተ መገኛ (±${(acc/1000).toFixed(1)}km): እባክዎ ሰፈርዎን ይምረጡ`;
                    } else {
                        alertBox.className = 'mb-2 p-2.5 rounded-xl bg-emerald-950/80 border border-emerald-500/40 text-emerald-200 text-[11px] flex items-center justify-between';
                        document.getElementById('tma-gps-status').innerHTML = `✅ ትክክለኛ የሞባይል GPS ተገኝቷል (±${acc}m)`;
                    }

                    document.getElementById('tma-gps-link').href = `https://www.google.com/maps?q=${lat},${lng}`;
                    btn.innerText = '✅ ተገኝቷል';
                    lucide.createIcons();
                },
                (err) => {
                    btn.innerText = '📍 የቀጥታ GPS ያዝ';
                    alert('ካርታውን ማግኘት አልተቻለም:: አድራሻዎን ከታች ይጻፉ:: (Could not access location. Please type your address).');
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
                telegram_user_id: tgUser ? String(tgUser.id) : null,
                notes: currentLang === 'am' ? 'በቴሌግራም ሚኒ አፕ የተመዘገበ' : 'Booked via Telegram Mini App',
                items: [
                    {
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
                    alert(data.message || (currentLang === 'am' ? 'ስህተት ተፈጥሯል' : 'Error creating booking'));
                }
            } catch (err) {
                alert(currentLang === 'am' ? 'የግንኙነት ችግር ተፈጥሯል' : 'Connection error');
            } finally {
                btn.disabled = false;
                btn.innerText = currentLang === 'am' ? 'ቀጠሮውን አረጋግጥ (Submit Booking)' : 'Confirm & Book Now';
            }
        }
    </script>
</body>
</html>
