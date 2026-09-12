<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Punch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Punch>
 */
class PunchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'card_uid' => strtoupper(bin2hex(random_bytes(4))),
            'user_id' => User::factory(),
            'direction' => fake()->randomElement(['in', 'out']),
            'punched_at' => fake()->dateTimeBetween('-2 weeks'),
            'recorded_at' => now(),
            'organization_id' => Organization::factory(),
        ];
    }
}
