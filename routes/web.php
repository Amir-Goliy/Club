<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'login')->name('login');

Route::middleware('auth')->group(function () {
    Route::livewire('/root/dashboard', 'root-dashboard')
        ->name('root.dashboard')
        ->middleware('role:root');

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

require __DIR__.'/settings.php';
