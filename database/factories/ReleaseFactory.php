<?php

namespace Database\Factories;

use App\Models\Release;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Release>
 */
class ReleaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'platform' => fake()->randomElement(['windows', 'linux']),
            'version' => fake()->numerify('#.#.#'),
            'url' => fake()->url(),
            'is_latest' => false,
            'created_by' => User::factory()->system(),
        ];
    }

    /**
     * Indicate that this is the current latest release for its platform.
     */
    public function latest(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_latest' => true,
        ]);
    }
}
