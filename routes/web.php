<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/owner/dashboard');

Route::middleware('auth')->group(function () {
    Route::livewire('/owner/dashboard', 'owner-dashboard')->name('owner.dashboard')->middleware('role');
    Route::livewire('/admin/dashboard', 'admin-dashboard')->name('admin.dashboard')->middleware('rolee');
    Route::livewire('/member/dashboard', 'member-dashboard')->name('member.dashboard')->middleware('roleee');
});

require __DIR__.'/settings.php';
