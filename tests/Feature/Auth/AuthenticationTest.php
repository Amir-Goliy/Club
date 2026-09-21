<?php

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('existing user is moved to password step', function () {
    $club = Club::factory()->create();

    User::factory()->create([
        'club_id' => $club->id,
        'national_code' => '1234567890',
        'password' => Hash::make('password'),
    ]);

    Livewire::test('⚡login')
        ->set('national_code', '1234567890')
        ->call('checkNationalCode')
        ->assertSet('step', 2);
});

test('invalid national code shows an error', function () {
    Livewire::test('⚡login')
        ->set('national_code', '1234567890')
        ->call('checkNationalCode')
        ->assertHasErrors(['national_code']);
});

test('user can authenticate with correct password', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'national_code' => '1234567890',
        'password' => Hash::make('password'),
        'role' => 'user',
    ]);

    Livewire::test('⚡login')
        ->set('national_code', '1234567890')
        ->set('step', 2)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('member.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('user cannot authenticate with incorrect password', function () {
    $club = Club::factory()->create();

    User::factory()->create([
        'club_id' => $club->id,
        'national_code' => '1234567890',
        'password' => Hash::make('password'),
        'role' => 'user',
    ]);

    Livewire::test('⚡login')
        ->set('national_code', '1234567890')
        ->set('step', 2)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['password']);

    $this->assertGuest();
});
