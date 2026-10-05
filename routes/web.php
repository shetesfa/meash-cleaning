<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - MEASH CLEANING SOLUTION
|--------------------------------------------------------------------------
*/

// 1. Public Customer Website & 7-Step Booking Wizard
Route::get('/', function () {
    return view('website');
});

// 2. Telegram Mini App (TMA embedded webapp)
Route::get('/telegram-miniapp', function () {
    return view('telegram_miniapp');
});

// 3. Master Internal PWA Operating Platform (Owner, Reception, Cleaners, Sales)
Route::get('/app/{any?}', function () {
    return view('app');
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

