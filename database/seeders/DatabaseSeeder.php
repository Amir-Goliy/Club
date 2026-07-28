<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Member;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Club::factory(10)->create();
        User::factory(10)->create();
        Member::factory(10)->create();
        Payment::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'national_code' => '1234567890',
            'role' => 'owner',
        ]);
    }
}
