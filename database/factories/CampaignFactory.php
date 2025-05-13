<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // Associate with a new or existing user
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'goal_amount' => $this->faker->randomFloat(2, 100, 10000),
            'current_amount' => $this->faker->randomFloat(2, 0, 5000), // Optional, can default to 0
            'start_date' => $this->faker->optional()->dateTimeBetween('-1 month', '+1 month'),
            'end_date' => $this->faker->optional()->dateTimeBetween('+1 month', '+6 months'),
            'status' => $this->faker->randomElement(['active', 'inactive', 'completed', 'cancelled']),
        ];
    }
}
