<?php

use App\Concerns\ProfileValidationRules;
use App\Models\Club;
use App\Models\User;
use Livewire\Livewire;

test('profile page can be rendered', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
    ]);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->assertSuccessful();
});

test('profile information can be updated', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'name' => 'Old Name',
        'national_code' => '1234567890',
    ]);

    $this->actingAs($user);

    $component = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('national_code', '0987654321');

    $component->assertSet('name', 'Test User');
    $component->assertSet('national_code', '0987654321');
});
