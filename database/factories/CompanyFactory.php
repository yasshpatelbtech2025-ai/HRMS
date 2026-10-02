<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'establishment_name' => fake()->company(),
            'address_line_1' => fake()->streetAddress(),
            'village' => fake()->city(),
            'district' => fake()->city(),
            'state' => fake()->state(),
        ];
    }
}
