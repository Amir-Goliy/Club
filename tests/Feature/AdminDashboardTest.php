<?php

use App\Models\Club;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Livewire\Livewire;

test('admin dashboard can be rendered', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->assertSuccessful();
});

test('admin only sees members from their own club', function () {
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Own',
        'last_name' => 'Member',
    ]);

    Member::factory()->create([
        'club_id' => $otherClub->id,
        'first_name' => 'Other',
        'last_name' => 'Member',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->assertSee('Own')
        ->assertDontSee('Other');
});

test('admin can search members by first name', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Reza',
        'last_name' => 'Mohammadi',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('search', 'Ali')
        ->assertSee('Ali')
        ->assertDontSee('Reza');
});

test('admin can search members by last name', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Reza',
        'last_name' => 'Mohammadi',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('search', 'Mohammadi')
        ->assertSee('Mohammadi')
        ->assertDontSee('Ahmadi');
});

test('admin can search members by national code', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Ali',
        'national_code' => '1234567890',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Reza',
        'national_code' => '0987654321',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('search', '1234567890')
        ->assertSee('Ali')
        ->assertDontSee('Reza');
});

test('admin can search members by phone', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Ali',
        'phone' => '09123456789',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Reza',
        'phone' => '09987654321',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('search', '09123456789')
        ->assertSee('Ali')
        ->assertDontSee('Reza');
});

test('search resets pagination', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    Member::factory(15)->create([
        'club_id' => $club->id,
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('paginators.page', 2)
        ->set('search', 'Ali')
        ->assertSet('paginators.page', 1);
});

test('admin can create a member', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('first_name', 'Ali')
        ->set('last_name', 'Ahmadi')
        ->set('national_code', '1234567890')
        ->set('birth_date', '2000-01-01')
        ->set('phone', '09123456789')
        ->set('amount', null)
        ->set('payment_month', 1)
        ->call('store')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('members', [
        'club_id' => $club->id,
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
        'national_code' => '1234567890',
        'phone' => '09123456789',
    ]);
});

test('admin can create a member with a payment', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('first_name', 'Reza')
        ->set('last_name', 'Ahmadi')
        ->set('national_code', '9876543210')
        ->set('birth_date', '2000-01-01')
        ->set('phone', '09123456789')
        ->set('amount', 500000)
        ->set('payment_month', 1)
        ->call('store')
        ->assertHasNoErrors();

    $member = Member::where('national_code', '9876543210')->first();

    expect($member)->not->toBeNull();

    $this->assertDatabaseHas('payments', [
        'member_id' => $member->id,
        'year' => jdate()->getYear(),
        'month' => 1,
        'amount' => 500000,
    ]);
});

test('member first name is required', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('first_name', '')
        ->set('last_name', 'Ahmadi')
        ->set('national_code', '1234567890')
        ->set('payment_month', 1)
        ->call('store')
        ->assertHasErrors(['first_name']);
});

test('member last name is required', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('first_name', 'Ali')
        ->set('last_name', '')
        ->set('national_code', '1234567890')
        ->set('payment_month', 1)
        ->call('store')
        ->assertHasErrors(['last_name']);
});

test('national code must contain exactly ten digits', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('first_name', 'Ali')
        ->set('last_name', 'Ahmadi')
        ->set('national_code', '12345')
        ->set('payment_month', 1)
        ->call('store')
        ->assertHasErrors(['national_code']);
});

test('national code must be unique', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    Member::factory()->create([
        'club_id' => $club->id,
        'national_code' => '1234567890',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('first_name', 'Ali')
        ->set('last_name', 'Ahmadi')
        ->set('national_code', '1234567890')
        ->set('payment_month', 1)
        ->call('store')
        ->assertHasErrors(['national_code']);
});

test('phone must contain exactly eleven digits when provided', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('first_name', 'Ali')
        ->set('last_name', 'Ahmadi')
        ->set('national_code', '1234567890')
        ->set('phone', '09123')
        ->set('payment_month', 1)
        ->call('store')
        ->assertHasErrors(['phone']);
});

test('payment amount cannot be negative', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('first_name', 'Ali')
        ->set('last_name', 'Ahmadi')
        ->set('national_code', '1234567890')
        ->set('amount', -500000)
        ->set('payment_month', 1)
        ->call('store')
        ->assertHasErrors(['amount']);
});

test('admin can update a member', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $member = Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
        'national_code' => '1234567890',
        'phone' => '09123456789',
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->call('edit', $member->id)
        ->set('first_name', 'Reza')
        ->set('last_name', 'Mohammadi')
        ->set('national_code', '0987654321')
        ->set('phone', '09987654321')
        ->set('amount', null)
        ->call('update')
        ->assertHasNoErrors();

    $member->refresh();

    expect($member->first_name)->toBe('Reza')
        ->and($member->last_name)->toBe('Mohammadi')
        ->and($member->national_code)->toBe('0987654321')
        ->and($member->phone)->toBe('09987654321');
});

test('admin can update a member payment', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $member = Member::factory()->create([
        'club_id' => $club->id,
    ]);

    Payment::create([
        'member_id' => $member->id,
        'year' => jdate()->getYear(),
        'month' => 1,
        'amount' => 500000,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->call('edit', $member->id)
        ->set('payment_month', 1)
        ->set('amount', 750000)
        ->call('update')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('payments', [
        'member_id' => $member->id,
        'year' => jdate()->getYear(),
        'month' => 1,
        'amount' => 750000,
    ]);
});

test('admin can delete a member', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $member = Member::factory()->create([
        'club_id' => $club->id,
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->call('confirmDelete', $member->id)
        ->call('delete');

    $this->assertDatabaseMissing('members', [
        'id' => $member->id,
    ]);
});

test('admin cannot edit a member from another club', function () {
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $member = Member::factory()->create([
        'club_id' => $otherClub->id,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test('⚡admin-dashboard');

    try {
        $component->call('edit', $member->id);
    } catch (\Throwable $e) {
        expect($e->getCode())->toBe(403);
    }

    expect($member->fresh()->club_id)->toBe($otherClub->id);
});

test('admin cannot delete a member from another club', function () {
    $club = Club::factory()->create();
    $otherClub = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $member = Member::factory()->create([
        'club_id' => $otherClub->id,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test('⚡admin-dashboard');

    try {
        $component->call('confirmDelete', $member->id);
    } catch (\Throwable $e) {
        expect($e->getCode())->toBe(403);
    }

    expect(Member::find($member->id))->not->toBeNull();
});

test('admin can select a payment month and load its amount', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $member = Member::factory()->create([
        'club_id' => $club->id,
    ]);

    Payment::create([
        'member_id' => $member->id,
        'year' => jdate()->getYear(),
        'month' => 5,
        'amount' => 600000,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->call('edit', $member->id)
        ->set('payment_month', 5)
        ->assertSet('amount', 600000);
});

test('admin can filter debtors', function () {
    $club = Club::factory()->create();

    $admin = User::factory()->create([
        'club_id' => $club->id,
        'role' => 'admin',
    ]);

    $paidMember = Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Paid',
    ]);

    $debtorMember = Member::factory()->create([
        'club_id' => $club->id,
        'first_name' => 'Debtor',
    ]);

    Payment::create([
        'member_id' => $paidMember->id,
        'year' => jdate()->getYear(),
        'month' => jdate()->getMonth(),
        'amount' => 500000,
        'paid_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test('⚡admin-dashboard')
        ->set('debtors', true)
        ->assertSee('Debtor')
        ->assertDontSee('Paid');
});
