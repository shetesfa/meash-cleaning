<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - MEASH CLEANING SOLUTION
|--------------------------------------------------------------------------
*/

// 1. Public Customer Website & 7-Step Booking Wizard
// Q4: Cache-Control: no-cache on the HTML page so customers always get
// the newest page (assets themselves keep the 30-day cache and are
// versioned with filemtime, so only the HTML is re-fetched).
Route::get('/', function () {
    return response()->view('website')->header('Cache-Control', 'no-cache');
});

// 2. Telegram Mini App (TMA embedded webapp)
Route::get('/telegram-miniapp', function () {
    $activeServices = \App\Models\Service::where('is_active', true)->orderBy('sort_order')->get();
    $websiteSettings = \App\Models\WebsiteSetting::getAllKeyValue();
    $subcities = \App\Models\WebsiteSetting::getJson('subcities', []);
    $timeSlots = \App\Models\WebsiteSetting::getJson('time_slots', []);
    $pricingRules = \App\Models\WebsiteSetting::getJson('pricing_rules', []);
    return view('telegram_miniapp', compact('activeServices', 'websiteSettings', 'subcities', 'timeSlots', 'pricingRules'));
});
Route::get('/telegram/miniapp', function () {
    return redirect('/telegram-miniapp');
});
Route::get('/miniapp', function () {
    return redirect('/telegram-miniapp');
});

// 3. Master Internal PWA Operating Platform (Owner, Reception, Cleaners, Sales)
// Throttled: the app shell is a plain GET, so without this one IP could
// hammer it forever. 60 loads a minute per IP is far above real use.
Route::get('/app/{any?}', function () {
    return view('app');
})->where('any', '.*')->middleware('throttle:60,1');

Route::get('/admin/{any?}', function () {
    return redirect('/app');
})->where('any', '.*');

Route::get('/login', function () {
    return redirect('/app');
})->name('login');

// 4. Owner System Learning & Coaching Manual (Print-Ready / PDF)
Route::get('/guide', function () {
    return response()->file(public_path('docs/owner_system_guide.html'));
});

Route::get('/guide/pdf', function () {
    return response()->download(public_path('docs/Meash_Complete_User_Guide.pdf'), 'Meash_Complete_User_Guide.pdf');
});

Route::get('/docs/guide', function () {
    return response()->file(public_path('docs/owner_system_guide.html'));
});


// Lightweight Health & Keep-Alive Ping Endpoints for 24/7 Uptime
Route::get('/ping', function () {
    return response('pong', 200)->header('Content-Type', 'text/plain');
});

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'meash-cleaning',
        'uptime' => 'active',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Sitemap for Google Search Console (single-page site: homepage only).
// URLs are built from APP_URL, so it is correct on every host.
Route::get('/sitemap.xml', function () {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n"
        . '  <url><loc>' . e(url('/')) . '</loc><lastmod>' . now()->toDateString() . '</lastmod><changefreq>weekly</changefreq><priority>1.0</priority></url>' . "\n"
        . '</urlset>';
    return response($xml, 200)->header('Content-Type', 'application/xml');
});

// 5. SPA fallback — direct visits, refreshes and bookmarks of website
//    sections (e.g. /services, /booking) serve the single-page site
//    instead of HTTP 404. Registered last so it never shadows /app/*,
//    /guide, /ping, /health, /telegram-miniapp or any defined route.
//    API paths keep their JSON 404 behaviour.
Route::fallback(function () {
    if (request()->is('api/*')) {
        abort(404);
    }
    // FIX (freeze): a missing static file used to return the whole 370 KB
    // homepage HTML with HTTP 200 — every miss wasted a full page download
    // (and a PHP-FPM worker) and could be cached by the service worker as
    // that asset. Real 404 for asset-looking paths; the SPA fallback stays
    // for genuine website section URLs only.
    if (request()->is([
        'images/*', 'css/*', 'js/*', 'vendor/*', 'fonts/*', 'assets/*',
        'storage/*', 'favicon.ico', 'favicon-*.png', 'apple-touch-icon.png',
        'manifest.json', 'sw.js', 'offline.html',
    ])) {
        abort(404);
    }
    return view('website');
});


