<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - MEASH CLEANING SOLUTION
|--------------------------------------------------------------------------
*/

// 0. Light health check for uptime monitors (see README "Keep the
// site awake"). Plain "OK" — no database, no view, no session. The
// framework's default /up rendered the welcome page (1.8 KB of HTML
// with external font/CDN links), so it is replaced by this route.
Route::get('/up', function () {
    return response('OK', 200)->header('Content-Type', 'text/plain');
})->withoutMiddleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
]);

// 1. Public Customer Website & 7-Step Booking Wizard
// F5: the homepage is identical for every visitor (public settings,
// services, photos — no user data), so the browser/CDN may reuse the
// HTML for 60 seconds (was: no-cache). Admin edits clear the server
// cache at once and show within 60s at most. Assets keep the 30-day
// cache and are versioned with filemtime.
// F5: no session/cookie middleware on this public GET (StartSession,
// CSRF cookie, EncryptCookies). A session used to be started and
// written on every visit. The staff app (/app) and the booking POST
// keep their sessions — only this route skips them.
Route::get('/', function () {
    return response()->view('website')->header('Cache-Control', 'public, max-age=60');
})->withoutMiddleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
]);

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

// 5. Static /storage fallback to serve uploads reliably even if public/storage symlink is missing on Plesk
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    if (!file_exists($filePath) || !is_file($filePath)) {
        abort(404);
    }
    return response()->file($filePath, [
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');

// 6. Fallback route: genuine website section URLs (nav links,
//    bookmarks, old links) still serve the single-page site — they are
//    real sections, not unknown paths. Every other unregistered path
//    404s, so garbage URLs like /toggle-status are never indexed by
//    Google as duplicate homepages. /app/{any?} and all defined routes
//    are registered above, so this never shadows them.
Route::fallback(function () {
    $section = trim(request()->path(), '/');
    $sections = ['about', 'services', 'commercial', 'gallery', 'reviews', 'contact', 'track', 'book', 'booking', 'how', 'process', 'advantage'];
    if (in_array($section, $sections, true)) {
        return response()->view('website')->header('Cache-Control', 'public, max-age=60');
    }
    if (request()->is('api/*')) {
        return response()->json(['message' => 'Not Found.'], 404);
    }
    abort(404);
});


