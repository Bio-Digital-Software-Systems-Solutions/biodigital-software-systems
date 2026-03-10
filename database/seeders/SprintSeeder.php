<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\Task;
use Illuminate\Database\Seeder;

class SprintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();

        if ($projects->isEmpty()) {
            $this->call(ProjectSeeder::class);
            $projects = Project::all();
        }

        $statuses = Status::all();
        if ($statuses->isEmpty()) {
            $this->call(StatusSeeder::class);
            $statuses = Status::all();
        }

        $todoStatus = $statuses->firstWhere('name', 'todo') ?? $statuses->firstWhere('name', 'pending');
        $inProgressStatus = $statuses->firstWhere('name', 'in_progress');
        $completedStatus = $statuses->firstWhere('name', 'completed');
        $underReviewStatus = $statuses->firstWhere('name', 'under_review');

        $sprintGoals = [
            'Améliorer les performances de l\'application',
            'Implémenter les nouvelles fonctionnalités utilisateur',
            'Corriger les bugs critiques identifiés',
            'Mettre à jour l\'interface utilisateur',
            'Intégrer les API tierces',
            'Optimiser la base de données',
            'Améliorer la sécurité du système',
            'Déployer la nouvelle version en production',
            'Documenter les processus techniques',
            'Automatiser les tests de régression',
        ];

        $taskTypes = ['story', 'task', 'bug', 'feature'];
        $taskPriorities = ['lowest', 'low', 'medium', 'high', 'highest'];

        foreach ($projects as $project) {
            // Skip projects that already have sprints
            if ($project->sprints()->count() > 0) {
                continue;
            }

            // Create 2-4 sprints per project
            $numberOfSprints = random_int(2, 4);
            $sprintNumber = 1;

            // Create a completed sprint
            if ($numberOfSprints >= 1) {
                $completedSprint = Sprint::create([
                    'project_id' => $project->id,
                    'name' => "Sprint {$sprintNumber} - {$project->name}",
                    'goal' => $sprintGoals[array_rand($sprintGoals)],
                    'start_date' => now()->subDays(random_int(45, 60)),
                    'end_date' => now()->subDays(random_int(15, 30)),
                    'status' => 'completed',
                    'capacity' => random_int(40, 80),
                ]);

                // Create 5-8 completed tasks for this sprint
                $this->createTasksForSprint($completedSprint, $project, $completedStatus, $taskTypes, $taskPriorities, random_int(5, 8));
                $sprintNumber++;
            }

            // Create an active sprint
            if ($numberOfSprints >= 2) {
                $activeSprint = Sprint::create([
                    'project_id' => $project->id,
                    'name' => "Sprint {$sprintNumber} - {$project->name}",
                    'goal' => $sprintGoals[array_rand($sprintGoals)],
                    'start_date' => now()->subDays(random_int(5, 10)),
                    'end_date' => now()->addDays(random_int(4, 10)),
                    'status' => 'active',
                    'capacity' => random_int(50, 100),
                ]);

                // Create tasks with mixed statuses for active sprint
                $this->createMixedTasksForSprint(
                    $activeSprint,
                    $project,
                    [$todoStatus, $inProgressStatus, $underReviewStatus, $completedStatus],
                    $taskTypes,
                    $taskPriorities,
                    random_int(6, 12)
                );
                $sprintNumber++;
            }

            // Create a planned sprint
            if ($numberOfSprints >= 3) {
                $plannedSprint = Sprint::create([
                    'project_id' => $project->id,
                    'name' => "Sprint {$sprintNumber} - {$project->name}",
                    'goal' => $sprintGoals[array_rand($sprintGoals)],
                    'start_date' => now()->addDays(random_int(5, 15)),
                    'end_date' => now()->addDays(random_int(20, 35)),
                    'status' => 'planned',
                    'capacity' => random_int(60, 100),
                ]);

                // Create only todo tasks for planned sprint
                $this->createTasksForSprint($plannedSprint, $project, $todoStatus, $taskTypes, $taskPriorities, random_int(4, 8));
                $sprintNumber++;
            }

            // Optionally create another planned or cancelled sprint
            if ($numberOfSprints >= 4) {
                $status = random_int(0, 1) === 0 ? 'planned' : 'cancelled';
                $extraSprint = Sprint::create([
                    'project_id' => $project->id,
                    'name' => "Sprint {$sprintNumber} - {$project->name}",
                    'goal' => $sprintGoals[array_rand($sprintGoals)],
                    'start_date' => now()->addDays(random_int(20, 40)),
                    'end_date' => now()->addDays(random_int(45, 60)),
                    'status' => $status,
                    'capacity' => random_int(50, 80),
                ]);

                if ($status === 'planned') {
                    $this->createTasksForSprint($extraSprint, $project, $todoStatus, $taskTypes, $taskPriorities, random_int(3, 6));
                }
            }
        }

        $this->command->info('Sprints seeded successfully with associated tasks.');
    }

    /**
     * Create tasks for a sprint with a single status.
     */
    private function createTasksForSprint(
        Sprint $sprint,
        Project $project,
        ?Status $status,
        array $taskTypes,
        array $taskPriorities,
        int $count
    ): void {
        if (!$status instanceof \App\Models\Status) {
            return;
        }

        $projectKey = strtoupper(substr((string) preg_replace('/[^a-zA-Z]/', '', $project->name), 0, 4));
        $existingTaskCount = Task::where('taskable_type', Project::class)
            ->where('taskable_id', $project->id)
            ->count();

        $members = $project->members;
        if ($members->isEmpty()) {
            $members = collect([$project->manager ?? \App\Models\User::first()]);
        }

        for ($i = 1; $i <= $count; $i++) {
            $taskNumber = $existingTaskCount + $i;
            Task::create([
                'taskable_type' => Project::class,
                'taskable_id' => $project->id,
                'sprint_id' => $sprint->id,
                'key' => "{$projectKey}-{$taskNumber}",
                'title' => fake()->sentence(4),
                'description' => fake()->paragraph(),
                'status_id' => $status->id,
                'type' => $taskTypes[array_rand($taskTypes)],
                'priority' => $taskPriorities[array_rand($taskPriorities)],
                'assigned_to' => $members->random()->id,
                'reporter_id' => $project->project_manager_id,
                'story_points' => random_int(1, 13),
                'estimated_hours' => random_int(2, 20),
                'due_date' => fake()->dateTimeBetween($sprint->start_date, $sprint->end_date),
            ]);
        }
    }

    /**
     * Create tasks for a sprint with mixed statuses.
     */
    private function createMixedTasksForSprint(
        Sprint $sprint,
        Project $project,
        array $statuses,
        array $taskTypes,
        array $taskPriorities,
        int $count
    ): void {
        $validStatuses = array_filter($statuses, fn ($s): bool => $s !== null);
        if ($validStatuses === []) {
            return;
        }

        $projectKey = strtoupper(substr((string) preg_replace('/[^a-zA-Z]/', '', $project->name), 0, 4));
        $existingTaskCount = Task::where('taskable_type', Project::class)
            ->where('taskable_id', $project->id)
            ->count();

        $members = $project->members;
        if ($members->isEmpty()) {
            $members = collect([$project->manager ?? \App\Models\User::first()]);
        }

        for ($i = 1; $i <= $count; $i++) {
            $taskNumber = $existingTaskCount + $i;
            $status = $validStatuses[array_rand($validStatuses)];

            Task::create([
                'taskable_type' => Project::class,
                'taskable_id' => $project->id,
                'sprint_id' => $sprint->id,
                'key' => "{$projectKey}-{$taskNumber}",
                'title' => fake()->sentence(4),
                'description' => fake()->paragraph(),
                'status_id' => $status->id,
                'type' => $taskTypes[array_rand($taskTypes)],
                'priority' => $taskPriorities[array_rand($taskPriorities)],
                'assigned_to' => $members->random()->id,
                'reporter_id' => $project->project_manager_id,
                'story_points' => random_int(1, 13),
                'estimated_hours' => random_int(2, 20),
                'actual_hours' => $status->name === 'completed' ? random_int(1, 25) : null,
                'due_date' => fake()->dateTimeBetween($sprint->start_date, $sprint->end_date),
            ]);
        }
    }
}
