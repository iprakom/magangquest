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
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'player',
            'onboarding_status' => 'active',
            'intern_type' => 'mahasiswa',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->addMonths(3),
            'is_critical_zone' => false,
            'is_grace_period' => false,
            'google_id' => null,
            'avatar' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'onboarding_status' => 'active',
            'intern_type' => 'profesional',
        ]);
    }

    public function mentor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'mentor',
            'onboarding_status' => 'active',
            'intern_type' => 'profesional',
        ]);
    }

    public function player(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'player',
            'onboarding_status' => 'active',
            'intern_type' => 'mahasiswa',
        ]);
    }
}
