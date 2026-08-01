<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Member;
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
        Club::factory(1)
            ->create(['image' => 'image/image.png'])
            ->each(function ($club) {

                User::factory()->create([
                    'name' => 'Test User',
                    'national_code' => '1234567890',
                    'role' => 'admin',
                    'club_id' => $club->id,
                ]);

                Member::factory(10)->create([
                    'club_id' => $club->id,
                ]);

            });
    }
}
