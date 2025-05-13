<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donation>
 */
class DonationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'campaign_id' => Campaign::factory(),
            'amount' => $this->faker->randomFloat(2, 5, 500),
            'status' => $this->faker->randomElement(['pending', 'succeeded', 'failed']),
            // 'payment_intent_id' can be nullable or defined if needed for specific tests
        ];
    }
}
