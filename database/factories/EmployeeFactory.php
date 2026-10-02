<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(['male', 'female']),
            'mobile' => fake()->numerify('9#########'),
            'joining_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'status' => 'active',
        ];
    }

    public function separated(): static
    {
        return $this->state(['status' => 'separated', 'separation_date' => now()]);
    }
}
