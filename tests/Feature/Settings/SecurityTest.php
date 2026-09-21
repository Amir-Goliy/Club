<?php

use App\Models\Club;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('security settings page can be rendered', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
    ]);

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk();
});

test('security settings page renders without two factor when feature is disabled', function () {
    config(['fortify.features' => []]);

    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
    ]);

    $this->actingAs($user)
        ->get(route('security.edit'))
        ->assertOk();
});

test('password can be updated', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.security')
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $response->assertHasNoErrors();

    expect(Hash::check(
        'new-password',
        $user->refresh()->password
    ))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.security')
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword');

    $response->assertHasErrors(['current_password']);
});
