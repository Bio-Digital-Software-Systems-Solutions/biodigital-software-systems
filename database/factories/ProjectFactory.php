<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['planning', 'active', 'on_hold', 'completed', 'cancelled']),
            'priority' => fake()->randomElement(['lowest', 'low', 'medium', 'high', 'highest']),
            'color' => fake()->hexColor(),
            'start_date' => fake()->date(),
            'end_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
            'budget' => fake()->randomFloat(2, 1000, 100000),
            'project_manager_id' => User::factory(),
            'is_template' => false,
        ];
    }
}
