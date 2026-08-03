<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/manifest.json', function () {
    return response()->json([
        'id' => '/',
        'name' => 'Club',
        'short_name' => 'Club',
        'start_url' => '/',
        'display' => 'standalone',
        'lang' => str_replace('_', '-', app()->getLocale()),
        'dir' => app()->getLocale() == 'fa' ? 'rtl' : 'ltr',
        'icons' => [
            [
                'src' => asset('icons/icon-512.png'),
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
})->name('manifest.json');

Route::get('/pwa-assets.json', function () {
    $manifest = json_decode(
        file_get_contents(public_path('build/manifest.json')),
        true
    );

    return response()->json([
        'assets' => [
            '/build/'.$manifest['resources/css/app.css']['file'],
            '/build/'.$manifest['resources/js/app.js']['file'],
        ],
    ]);

});
