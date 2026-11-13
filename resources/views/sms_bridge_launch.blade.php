<!DOCTYPE html>
<html lang="am">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>ሜሽ Auto-SMS Bridge | iPhone</title>
  <link rel="stylesheet" href="/css/fonts.css">
  <link rel="stylesheet" href="/css/meash.css">
  <style>
    body {
      font-family: 'Noto Sans Ethiopic', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #0B1220;
      color: #F8FAFC;
    }
  </style>
</head>
<body class="p-4 max-w-md mx-auto min-h-screen flex flex-col justify-between">
  <div>
    <!-- Header -->
    <div class="flex items-center gap-3 p-3.5 rounded-2xl bg-slate-800/90 border border-slate-700 mb-5 shadow-lg">
      <img src="/logo.jpg" alt="Logo" class="w-12 h-12 rounded-xl object-cover border border-cyan-500/40">
      <div>
        <h1 class="text-sm font-extrabold text-white">ሜሽ የፅዳት አገልግሎት</h1>
        <p class="text-[11px] text-cyan-400 font-semibold">iPhone 1-Scan Auto-SMS Bridge</p>
      </div>
    </div>

    <!-- Active Campaign Card -->
    <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 border border-cyan-500/30 mb-5 shadow-xl">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-bold text-slate-300">የዘመቻ ሁኔታ (Campaign)</span>
        <span id="badge-status" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-cyan-950 text-cyan-400 border border-cyan-500/40">በሂደት ላይ</span>
      </div>
      <h3 class="text-base font-black text-white mb-2">{{ $campaign->title }}</h3>
      <p class="text-xs text-slate-300 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800 mb-3 italic">
        "{{ Str::limit($campaign->message_text, 120) }}"
      </p>

      <!-- Live Progress Bar -->
      <div class="space-y-1 mb-2">
        <div class="flex justify-between text-xs font-bold">
          <span class="text-slate-400">የተላኩ መልእክቶች:</span>
          <span id="progress-text" class="text-cyan-400 font-mono">{{ $sent }} / {{ $total }}</span>
        </div>
        <div class="w-full bg-slate-950 rounded-full h-3 border border-slate-800 overflow-hidden">
          <div id="progress-bar" class="bg-gradient-to-r from-cyan-500 to-blue-500 h-3 rounded-full transition-all duration-500" style="width: {{ $total > 0 ? min(100, round(($sent / $total) * 100)) : 0 }}%"></div>
        </div>
      </div>
    </div>

    <!-- Main Action Button -->
    <div class="space-y-3 mb-6">
      <a href="{{ $shortcutDirectUrl }}" id="btn-launch-shortcut" class="w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/25 active:scale-98 transition-all">
        <span>በአይፎን ላክ (Launch Shortcut)</span>
      </a>

      <p class="text-[11px] text-center text-slate-400">
        አይፎንዎ መልእክቶቹን ከሲም ካርድዎ በጀርባ (background) በራሱ ይልካል::
      </p>
      <p class="text-[11px] text-center text-amber-400/90">
        ምንም ካልተከፈተ "Meash Auto SMS" Shortcut ገና አልተጫነም — ከታች ያለውን ማዘጋጃ ይከተሉ። (Android ላይ Shortcut አይሰራም — SMS በ Messages App እጅዎ ይላኩ።)
      </p>
    </div>

    <!-- 1-Minute Shortcut Setup Instructions -->
    <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-700/80 mb-6 text-xs text-slate-300">
      <div class="flex items-center gap-2 mb-2 font-bold text-white text-sm">
        <span>የአይፎን Shortcut ለመጀመሪያ ጊዜ ማዘጋጃ (1 ደቂቃ)</span>
      </div>
      <p class="text-[11px] text-slate-400 mb-3">በአይፎንዎ ውስጥ ያለውን <strong>Shortcuts</strong> መተግበሪያ በመክፈት የሚከተሉትን 4 እርምጃዎች 1 ጊዜ ብቻ ያዘጋጁ፦</p>
      <ol class="space-y-2 list-decimal list-inside text-[11px] leading-relaxed">
        <li>በ <strong>Shortcuts</strong> አፕ አዲስ Shortcut ፈጥረው ስሙን <code>Meash Auto SMS</code> ይበሉት::</li>
        <li>ድርጊት 1: <strong>Get Contents of URL</strong> አስገብተው የ URL ቦታውን <code>Shortcut Input</code> ያድርጉት::</li>
        <li>ድርጊት 2: <strong>Get Dictionary Value</strong> ያክሉ፤ Key <code>items</code> ያድርጉት (ከ Contents of URL)።</li>
        <li>ድርጊት 3: <strong>Repeat with Each</strong> ያክሉ (በ items ላይ)።</li>
        <li>ድርጊት 4: በ Repeat <strong>ውስጥ</strong> <strong>Send Message</strong> ያክሉ፤ ተቀባዩን <code>Repeat Item -&gt; phone</code>፣ መልእክቱን <code>Repeat Item -&gt; message</code> ያድርጉ::</li>
        <li><strong>እጅግ ወሳኝ፦</strong> በ Send Message ውስጥ <strong>"Show When Run"</strong> የሚለውን አጥፉ (<strong>OFF</strong> ያድርጉት)፤ ይህም እርስዎ Send ሳይነኩ በራሱ እንዲልክ ያደርገዋል!</li>
        <li>ድርጊት 5: <strong>በ Repeat ውስጥ</strong>፣ ከ Send Message በኋላ፣ <strong>Get Contents of URL</strong> ያክሉ፤ Method <strong>POST</strong>፣ URL <code>{{ $reportUrl }}</code>፣ Header <code>Content-Type: application/json</code>፣ Body (JSON)፦ <code>{"campaign_id": {{ $campaign->id }}, "token": "{{ $token }}", "recipient_id": Repeat Item.recipient_id, "status": "sent"}</code>።</li>
        <li>አንድ ጊዜ እስከ 500 ተቀባዮች ብቻ ይላካሉ፤ እድገቱ 100% ካልደረሰ Shortcut እንደገና ያሂዱት — ቀሪዎቹ ብቻ ይላካሉ።</li>
      </ol>
    </div>
  </div>

  <div class="text-center text-[10px] text-slate-500 py-3 border-t border-slate-800">
    ሜሽ የፅዳት አገልግሎት &bull; Secure Phone SMS Automation
  </div>

  <script>
    // NOTE: no auto-redirect — if the Shortcut is not installed, Safari
    // shows a "cannot open page" error and the user is stuck. The manual
    // button above is the only launch path.
    // Live progress polling (public token-checked route — the phone has
    // no admin token, so the auth:sanctum route would 401 forever).
    setInterval(async () => {
      try {
        const res = await fetch('/api/sms-bridge/campaign-status/{{ $campaign->id }}?token={{ $token }}');
        const data = await res.json();
        if (data) {
          const sent = data.sent_count || 0;
          const total = data.total_targets || {{ $total }};
          const pct = data.percentage || 0;
          document.getElementById('progress-text').innerText = `${sent} / ${total}`;
          document.getElementById('progress-bar').style.width = `${pct}%`;
          if (data.is_completed) {
            document.getElementById('badge-status').innerText = 'ተጠናቋል';
            document.getElementById('badge-status').className = 'px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-950 text-emerald-400 border border-emerald-500/40';
          }
        }
      } catch(e) {}
    }, 2000);
  </script>
</body>
</html>
