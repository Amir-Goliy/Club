<?php

use App\Models\Club;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    $member = Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
        'phone' => '09123456789',
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->assertSet('member.id', $member->id)
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
        ->set('phone', '09987654321')
        ->call('update')
        ->assertHasNoErrors()
        ->assertSet('editing', false);

    expect($member->fresh()->phone)->toBe('09987654321');
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
        ->set('phone', null)
        ->call('update')
        ->assertHasNoErrors();

    expect($member->fresh()->phone)->toBeNull();
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
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
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
        ->assertSet('phone', '09123456789');
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
        'month' => 5,
        'amount' => 500000,
        'paid_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->assertSee('500000');
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
        'year' => jdate()->getYear() - 1,
        'month' => 5,
        'amount' => 900000,
        'paid_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
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
        'month' => 2,
        'amount' => 200000,
        'paid_at' => now(),
    ]);

    Payment::create([
        'member_id' => $member->id,
        'year' => jdate()->getYear(),
        'month' => 8,
        'amount' => 800000,
        'paid_at' => now(),
    ]);

    $this->actingAs($user);

    $component = Livewire::test('⚡member-dashboard');

    $payments = $component->instance()->currentYearPayments;

    expect($payments->first()->month)->toBe(8)
        ->and($payments->last()->month)->toBe(2);
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
    ]);

    $file = UploadedFile::fake()->create(
        'profile.jpg',
        100,
        'image/jpeg'
    );

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->set('image', $file)
        ->call('update')
        ->assertHasNoErrors();

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

    $file = UploadedFile::fake()->create(
        'new-profile.jpg',
        100,
        'image/jpeg'
    );

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->set('image', $file)
        ->call('update')
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing('image/old-profile.jpg');

    expect($member->fresh()->image)
        ->not->toBe('image/old-profile.jpg');
});

test('member cannot upload an invalid image', function () {
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
})->throws(ModelNotFoundException::class);

test('member can only access their own profile', function () {
    $club = Club::factory()->create();

    $user = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    $member = Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $user->id,
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
        'phone' => '09123456789',
    ]);

    $otherUser = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'user',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'user_id' => $otherUser->id,
        'first_name' => 'Reza',
        'last_name' => 'Mohammadi',
        'phone' => '09987654321',
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->assertSet('member.id', $member->id)
        ->assertSet('member.first_name', 'Ali')
        ->assertSet('member.last_name', 'Ahmadi');
});

test('member dashboard does not expose another members information', function () {
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

    Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Hacker',
        'last_name' => 'Target',
        'phone' => '09999999999',
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->assertSee('Ali')
        ->assertSee('Ahmadi')
        ->assertDontSee('Hacker')
        ->assertDontSee('Target')
        ->assertDontSee('09999999999');
});

test('member can only update their own member record', function () {
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

    $otherMember = Member::factory()->create([
        'club_id' => $club->id,
        'phone' => '09987654321',
    ]);

    $this->actingAs($user);

    Livewire::test('⚡member-dashboard')
        ->set('phone', '09111111111')
        ->call('update')
        ->assertHasNoErrors();

    expect($member->fresh()->phone)
        ->toBe('09111111111')
        ->and($otherMember->fresh()->phone)
        ->toBe('09987654321');
});
