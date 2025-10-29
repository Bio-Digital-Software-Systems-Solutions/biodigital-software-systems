<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectTaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $project = Project::factory()->create();
        $statuses = Status::all();
        $randomStatus = $statuses->isNotEmpty() ? $statuses->random() : null;

        return [
            'title' => fake()->sentence(),
            'key' => strtoupper(fake()->lexify('????')).'-'.fake()->numberBetween(1, 999),
            'description' => fake()->paragraph(),
            'taskable_type' => Project::class,
            'taskable_id' => $project->id,
            'reporter_id' => User::factory(),
            'assignee_id' => fake()->boolean() ? User::factory() : null,
            'status_id' => $randomStatus?->id,
            'priority' => fake()->randomElement(['lowest', 'low', 'medium', 'high', 'highest']),
            'type' => fake()->randomElement(['task', 'bug', 'feature', 'story', 'epic', 'subtask']),
            'story_points' => fake()->optional()->numberBetween(1, 13),
            'estimated_hours' => fake()->optional()->randomFloat(2, 0.5, 40),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+2 months'),
            'labels' => fake()->optional()->randomElements(['frontend', 'backend', 'urgent', 'bug', 'feature'], fake()->numberBetween(0, 3)),
            'position' => 0,
        ];
    }
}
