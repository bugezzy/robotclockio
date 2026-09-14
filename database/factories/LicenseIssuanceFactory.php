<?php

namespace Database\Factories;

use App\Models\LicenseIssuance;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LicenseIssuance>
 */
class LicenseIssuanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'issued_by' => User::factory(),
            'kiosk_key' => fake()->uuid(),
            'issued_at' => now(),
        ];
    }
}
