<?php

namespace Database\Factories;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_code' => fake()->unique()->numerify('####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('9#########'),
            'gender' => fake()->randomElement(Gender::cases()),
            'date_of_birth' => fake()->dateTimeBetween('-55 years', '-20 years'),
            'date_of_joining' => fake()->dateTimeBetween('-5 years'),
            'employment_type' => EmploymentType::FullTime,
            'status' => EmployeeStatus::Active,
            'pay_frequency' => 'monthly',
            'daily_hour' => 8,
            'monthly_salary_calculation' => 'calendar_month',
            'weekly_leave_day' => 'sunday',
        ];
    }

    public function separated(?string $on = null): static
    {
        return $this->state(['separation_date' => $on ?? today()->subDay()->toDateString()]);
    }
}
