<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $managers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['Admin', 'ProjectManager', 'SuperAdmin']);
        })->get();

        if ($managers->isEmpty()) {
            throw new \Exception('No managers found. Please seed users first.');
        }

        $projects = [
            [
                'name' => 'Refonte du Site Web',
                'description' => 'Migration complète du site web vers une architecture moderne avec React et Laravel',
                'status' => 'active',
                'priority' => 'high',
                'color' => '#3B82F6',
                'start_date' => now()->subDays(30),
                'end_date' => now()->addDays(60),
                'budget' => 50000,
            ],
            [
                'name' => 'Application Mobile',
                'description' => 'Développement d\'une application mobile native pour iOS et Android',
                'status' => 'planning',
                'priority' => 'highest',
                'color' => '#8B5CF6',
                'start_date' => now()->addDays(15),
                'end_date' => now()->addDays(120),
                'budget' => 75000,
            ],
            [
                'name' => 'Migration Cloud',
                'description' => 'Migration de l\'infrastructure vers AWS avec conteneurisation Docker',
                'status' => 'active',
                'priority' => 'medium',
                'color' => '#10B981',
                'start_date' => now()->subDays(20),
                'end_date' => now()->addDays(40),
                'budget' => 30000,
            ],
            [
                'name' => 'Système de Gestion des Stocks',
                'description' => 'Développement d\'un système complet de gestion des stocks avec suivi en temps réel',
                'status' => 'on_hold',
                'priority' => 'low',
                'color' => '#F59E0B',
                'start_date' => now()->subDays(60),
                'end_date' => now()->addDays(30),
                'budget' => 40000,
            ],
            [
                'name' => 'Plateforme E-learning',
                'description' => 'Création d\'une plateforme de formation en ligne avec système de gestion de cours',
                'status' => 'completed',
                'priority' => 'medium',
                'color' => '#06B6D4',
                'start_date' => now()->subDays(90),
                'end_date' => now()->subDays(10),
                'budget' => 60000,
            ],
            [
                'name' => 'API Gateway',
                'description' => 'Implémentation d\'une passerelle API avec authentification et rate limiting',
                'status' => 'active',
                'priority' => 'high',
                'color' => '#EF4444',
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(45),
                'budget' => 35000,
            ],
        ];

        foreach ($projects as $projectData) {
            $projectData['slug'] = Str::slug($projectData['name']);
            $projectData['project_manager_id'] = $managers->random()->id;

            $project = Project::create($projectData);

            // Add project manager as member
            $project->members()->attach($projectData['project_manager_id'], [
                'is_lead' => true,
                'started_at' => $projectData['start_date'],
            ]);

            // Add 2-4 additional members
            $additionalMembers = User::whereNotIn('id', [$projectData['project_manager_id']])
                ->inRandomOrder()
                ->take(rand(2, 4))
                ->get();

            foreach ($additionalMembers as $member) {
                $project->members()->attach($member->id, [
                    'is_lead' => false,
                    'started_at' => $projectData['start_date'],
                ]);
            }

            // Create tasks for each project
            $taskStatuses = ['todo', 'in_progress', 'in_review', 'done'];
            $taskTypes = ['feature', 'bug', 'task', 'story'];
            $taskPriorities = ['lowest', 'low', 'medium', 'high', 'highest'];

            $numberOfTasks = rand(5, 15);

            // Generate project key (first 3-4 letters of project name in uppercase)
            $projectKey = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $project->name), 0, 4));

            for ($i = 1; $i <= $numberOfTasks; $i++) {
                $status = $project->status === 'completed' ? 'done' : $taskStatuses[array_rand($taskStatuses)];

                ProjectTask::create([
                    'project_id' => $project->id,
                    'key' => $projectKey.'-'.$i,
                    'title' => "Tâche #{$i} - {$project->name}",
                    'description' => "Description détaillée de la tâche {$i} pour le projet {$project->name}",
                    'status' => $status,
                    'type' => $taskTypes[array_rand($taskTypes)],
                    'priority' => $taskPriorities[array_rand($taskPriorities)],
                    'assignee_id' => $project->members->random()->id,
                    'reporter_id' => $projectData['project_manager_id'],
                    'estimated_hours' => rand(2, 40),
                    'due_date' => now()->addDays(rand(1, 60)),
                ]);
            }
        }
    }
}
