<?php

namespace Database\Factories;

use App\Models\Club;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'club_id' => Club::factory(),
            'amount' => fake()->phoneNumber(),
            'month' => fake()->numberBetween(1, 12),
            'year' => fake()->numberBetween(2000, 9999),
            'status' => fake()->numberBetween(0, 1),
            'description' => fake()->text(),
            'paid_at' => fake()->dateTime(),
        ];
    }
}
