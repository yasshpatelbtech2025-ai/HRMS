<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Tests act as an admin unless they ask for a user.
            'role' => 'admin',
            'is_active' => true,
        ];
    }

    /**
     * A user (not an admin), limited to the given companies.
     */
    public function user(iterable $companies = []): static
    {
        return $this->state(['role' => 'user'])->afterCreating(fn (\App\Models\User $user) => $user->companies()->sync(collect($companies)->map(fn ($c) => is_object($c) ? $c->getKey() : $c)->all()));
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
