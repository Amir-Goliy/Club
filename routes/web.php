<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect('/login');
    }

    return match (auth()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'user' => redirect()->route('member.dashboard'),
    };
});

Route::middleware('auth')->group(function () {
    Route::livewire('/admin/dashboard', 'admin-dashboard')
        ->name('admin.dashboard')
        ->middleware('role:admin');

    Route::livewire('/member/dashboard', 'member-dashboard')
        ->name('member.dashboard')
        ->middleware('role:user');
});

Route::livewire('/activate-account', 'pages::auth.activate-account')
    ->name('activate.account');

require __DIR__.'/settings.php';
