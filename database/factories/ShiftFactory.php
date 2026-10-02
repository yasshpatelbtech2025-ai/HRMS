<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => 'General Shift '.fake()->unique()->numberBetween(1, 9999),
            'type' => 'general',
            'slot' => fn (array $attributes) => Shift::where('company_id', $attributes['company_id'])->where('type', 'general')->max('slot') + 1,
            'timings' => ['GS' => ['start' => '09:00', 'end' => '18:00']],
        ];
    }

    public function rotational(): static
    {
        return $this->state([
            'name' => 'Rotational Shift',
            'type' => 'rotational',
            'slot' => 1,
            'timings' => [
                'FS' => ['start' => '06:00', 'end' => '14:00'],
                'SS' => ['start' => '14:00', 'end' => '23:00'],
                'NS' => ['start' => '23:00', 'end' => '06:00'],
            ],
        ]);
    }
}
