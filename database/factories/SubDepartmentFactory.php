<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubDepartment>
 */
class SubDepartmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'company_id' => fn (array $attributes) => Department::find($attributes['department_id'])->company_id,
            'name' => fake()->unique()->words(2, true),
            'is_active' => true,
        ];
    }
}
