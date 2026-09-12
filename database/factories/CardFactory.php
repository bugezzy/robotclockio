<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_uid' => strtoupper(bin2hex(random_bytes(4))),
            'user_id' => User::factory(),
            'label' => fake()->optional()->word(),
            'issued_at' => now(),
            'revoked_at' => null,
        ];
    }

    /**
     * Indicate that the card has been revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }
}
