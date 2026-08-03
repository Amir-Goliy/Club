<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'login')->name('login');

Route::middleware('auth')->group(function () {
    Route::livewire('/admin/dashboard', 'admin-dashboard')
        ->name('admin.dashboard')
        ->middleware('role:admin');

    Route::livewire('/member/dashboard', 'member-dashboard')
        ->name('member.dashboard')
        ->middleware('role:user');
});

Route::post('/logout', function () {
    Auth::logout();

    session()->invalidate();
    session()->regenerateToken();

    return redirect('/');
})->name('logout');

Route::get('/pwa-assets.json', function () {

    $manifest = json_decode(
        file_get_contents(public_path('build/manifest1.json')),
        true
    );

    return response()->json([
        'assets' => [
            '/build/' . $manifest['resources/css/app.css']['file'],
            '/build/' . $manifest['resources/js/app.js']['file'],
        ],
    ]);

});

Route::get('/manifest.json', function () {
    $club = auth()->user()?->club;

    return response()->json([
        'id' => '/',
        'name' => $club?->name ?? 'Club Manager',
        'short_name' => $club?->name ?? 'Club',
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => '#111827',
        'scope' => '/',
        'lang' => str_replace('_', '-', app()->getLocale()),
        'dir' => app()->getLocale() == 'fa' ? 'rtl' : 'ltr',
        'icons' => [
            [
                'src' => $club?->logo
                    ? asset('storage/' . $club->logo)
                    : asset('icons/icon-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => asset('icons/icon-512-maskable.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ],

    ]);

});

require __DIR__ . '/settings.php';
