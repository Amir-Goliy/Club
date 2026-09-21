<?php

use App\Models\Club;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('member dashboard can be rendered', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->assertSuccessful();
});

test('member can see their own profile information', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
        'phone' => '09123456789',
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->assertSet('phone', '09123456789')
        ->assertSee('Ali')
        ->assertSee('Ahmadi');
});

test('member can start editing their profile', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->assertSet('editing', false)
        ->call('startEditing')
        ->assertSet('editing', true);
});

test('member can update their phone number', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    $member = Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
        'phone' => '09123456789',
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->call('startEditing')
        ->set('phone', '09987654321')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('editing', false);

    expect($member->refresh()->phone)->toBe('09987654321');
});

test('member phone number is optional', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    $member = Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
        'phone' => '09123456789',
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->call('startEditing')
        ->set('phone', null)
        ->call('update')
        ->assertHasNoErrors();

    expect($member->refresh()->phone)->toBeNull();
});

test('member phone number must contain eleven digits', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
        'phone' => '09123456789',
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->call('startEditing')
        ->set('phone', '09123')
        ->call('update')
        ->assertHasErrors(['phone']);
});

test('member can cancel profile editing', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
        'phone' => '09123456789',
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->call('startEditing')
        ->set('phone', '09987654321')
        ->call('cancel')
        ->assertSet('editing', false)
        ->assertSet('phone', '09123456789')
        ->assertSet('image', null)
        ->assertHasNoErrors();
});

test('member can see current year payments', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    $member = Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
    ]);

    Payment::create([
        'member_id' => $member->id,
        'year' => jdate()->getYear(),
        'month' => 1,
        'amount' => 500000,
        'paid_at' => now(),
    ]);

    Payment::create([
        'member_id' => $member->id,
        'year' => jdate()->getYear(),
        'month' => 2,
        'amount' => 600000,
        'paid_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->assertSee('500000')
        ->assertSee('600000');
});

test('member does not see payments from another year', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    $member = Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
    ]);

    Payment::create([
        'member_id' => $member->id,
        'year' => jdate()->getYear(),
        'month' => 1,
        'amount' => 500000,
        'paid_at' => now(),
    ]);

    Payment::create([
        'member_id' => $member->id,
        'year' => jdate()->getYear() - 1,
        'month' => 1,
        'amount' => 900000,
        'paid_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->assertSee('500000')
        ->assertDontSee('900000');
});

test('member payments are ordered by month descending', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    $member = Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
    ]);

    Payment::create([
        'member_id' => $member->id,
        'year' => jdate()->getYear(),
        'month' => 1,
        'amount' => 100000,
        'paid_at' => now(),
    ]);

    Payment::create([
        'member_id' => $member->id,
        'year' => jdate()->getYear(),
        'month' => 6,
        'amount' => 600000,
        'paid_at' => now(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test('⚡member-dashboard');

    $payments = $component->get('currentYearPayments');

    expect($payments->first()->month)->toBe(6)
        ->and($payments->last()->month)->toBe(1);
});

test('member can upload a new profile image', function () {
    Storage::fake('public');

    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    $member = Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
        'image' => null,
    ]);

    $image = UploadedFile::fake()->create(
        'profile.jpg',
        100,
        'image/jpeg'
    );

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->call('startEditing')
        ->set('image', $image)
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('editing', false);

    $member->refresh();

    expect($member->image)->not->toBeNull();

    Storage::disk('public')->assertExists($member->image);
});

test('member old profile image is deleted when uploading a new image', function () {
    Storage::fake('public');

    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    Storage::disk('public')->put(
        'image/old-profile.jpg',
        'old image'
    );

    $member = Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
        'image' => 'image/old-profile.jpg',
    ]);

    $image = UploadedFile::fake()->create(
        'new-profile.jpg',
        100,
        'image/jpeg'
    );

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->call('startEditing')
        ->set('image', $image)
        ->call('update')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->image)
        ->not->toBe('image/old-profile.jpg');

    Storage::disk('public')
        ->assertMissing('image/old-profile.jpg');

    Storage::disk('public')
        ->assertExists($member->image);
});

test('member cannot upload an invalid image', function () {
    Storage::fake('public');

    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
    ]);

    $file = UploadedFile::fake()->create(
        'document.gif',
        100,
        'image/gif'
    );

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->call('startEditing')
        ->set('image', $file)
        ->call('update')
        ->assertHasErrors(['image']);
});

test('member without a member record cannot access member dashboard', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard');
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
