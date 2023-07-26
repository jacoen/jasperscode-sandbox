<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'author_id' => User::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->sentences(6, true),
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
