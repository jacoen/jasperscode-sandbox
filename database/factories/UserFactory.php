<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
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
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'password_token' => null,
            'password_changed_at' => now(),
            'two_factor_enabled' => false,
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
            'locked_until' => null,
        ];
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

    public function superAdmin()
    {
        return $this->state(function () {
            return [];
        })->afterCreating(function (User $user) {
            $user->assignRole('Super Admin');
        });
    }

    public function expiredToken()
    {
        return $this->state(function () {
            return [
                'password_token' => Str::random(32),
                'created_at' => now()->subHour(),
                'updated_at' => now()->subHour(),
                'token_expires_at' => now()->subHour(),
                'password_changed_at' => null,
            ];
        });
    }

    public function notActivated()
    {
        return $this->state(function () {
            return [
                'password_token' => Str::random(32),
                'token_expires_at' => now()->addHour(),
                'password_changed_at' => null,
            ];
        });
    }

    public function withTwoFactorEnabled()
    {
        return $this->state(function () {
            return [
                'two_factor_enabled' => true,
                'two_factor_code' => generateDigitCode(),
                'two_factor_expires_at' => now()->addMinutes(),
            ];
        });
    }
}
