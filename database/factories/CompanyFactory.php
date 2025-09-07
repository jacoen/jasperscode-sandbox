<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'country' => fake()->country(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'postal_code' => fake()->postcode(),
            'phone' => fake()->e164PhoneNumber(),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->email(),
        ];
    }
}
