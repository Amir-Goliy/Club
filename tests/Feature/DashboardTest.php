<?php

use App\Models\Club;
use App\Models\Member;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login'));
});

test('admin can visit the admin dashboard', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $response = $this->actingAs($user)
        ->get(route('admin.dashboard'));

    $response->assertOk();
});

test('member can visit the member dashboard', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('member.dashboard'));

    $response->assertOk();
});
