<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'manager_id' => $user,
            'title' => fake()->sentence(4),
            'description' => fake()->text(),
            'due_date' => now()->addMonths(4),
            'status' => 'open',

        ];
    }

    public function trashed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'closed',
                'created_at' => now()->subMinutes(5),
                'updated_at' => now()->subMinutes(5),
                'deleted_at' => now(),
            ];
        });
    }
}
