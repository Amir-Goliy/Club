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
    $manifestPath = public_path('build/manifest.json');

    abort_unless(is_file($manifestPath), 500, 'Vite manifest not found.');

    $contents = file_get_contents($manifestPath);

    abort_if($contents === false, 500, 'Unable to read Vite manifest.');

    $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

    abort_unless(
        isset(
            $manifest['resources/css/app.css']['file'],
            $manifest['resources/js/app.js']['file'],
        ),
        500,
        'Required Vite assets not found.',
    );

    return response()->json([
        'assets' => [
            '/build/'.$manifest['resources/css/app.css']['file'],
            '/build/'.$manifest['resources/js/app.js']['file'],
        ],
    ]);
})->name('pwa-assets.json');
