<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Sprint;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ProjectTaskHistoricalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates historical task data across 12 months for realistic statistics.
     */
    public function run(): void
    {
        $this->command->info('Starting ProjectTaskHistoricalSeeder...');

        // Get or create statuses
        $statuses = Status::all();
        if ($statuses->isEmpty()) {
            $this->call(StatusSeeder::class);
            $statuses = Status::all();
        }

        $todoStatus = $statuses->firstWhere('name', 'todo') ?? $statuses->firstWhere('name', 'pending');
        $inProgressStatus = $statuses->firstWhere('name', 'in_progress');
        $completedStatus = $statuses->firstWhere('name', 'completed');
        $underReviewStatus = $statuses->firstWhere('name', 'under_review');
        $blockedStatus = $statuses->firstWhere('name', 'blocked');
        $cancelledStatus = $statuses->firstWhere('name', 'cancelled');

        if (! $todoStatus || ! $completedStatus) {
            $this->command->error('Required statuses not found. Please run StatusSeeder first.');

            return;
        }

        // Get projects
        $projects = Project::with(['members', 'manager'])->get();
        if ($projects->isEmpty()) {
            $this->call(ProjectSeeder::class);
            $projects = Project::with(['members', 'manager'])->get();
        }

        $taskTypes = ['feature', 'bug', 'task', 'story'];
        $taskPriorities = ['lowest', 'low', 'medium', 'high', 'highest'];

        $taskTemplates = [
            // Development tasks
            ['title' => 'Implémenter la fonctionnalité de connexion', 'type' => 'feature'],
            ['title' => 'Corriger le bug d\'affichage', 'type' => 'bug'],
            ['title' => 'Optimiser les requêtes SQL', 'type' => 'task'],
            ['title' => 'Ajouter la validation des formulaires', 'type' => 'feature'],
            ['title' => 'Mettre à jour les dépendances', 'type' => 'task'],
            ['title' => 'Implémenter le système de cache', 'type' => 'feature'],
            ['title' => 'Corriger la fuite mémoire', 'type' => 'bug'],
            ['title' => 'Créer les tests unitaires', 'type' => 'task'],
            ['title' => 'Ajouter la pagination', 'type' => 'feature'],
            ['title' => 'Refactoriser le module utilisateur', 'type' => 'task'],
            // Design tasks
            ['title' => 'Créer les maquettes du dashboard', 'type' => 'story'],
            ['title' => 'Améliorer l\'UX du formulaire', 'type' => 'story'],
            ['title' => 'Implémenter le mode sombre', 'type' => 'feature'],
            ['title' => 'Optimiser les images', 'type' => 'task'],
            ['title' => 'Créer les icônes personnalisées', 'type' => 'story'],
            // Infrastructure tasks
            ['title' => 'Configurer le CI/CD', 'type' => 'task'],
            ['title' => 'Mettre en place le monitoring', 'type' => 'feature'],
            ['title' => 'Configurer les backups automatiques', 'type' => 'task'],
            ['title' => 'Optimiser le déploiement', 'type' => 'task'],
            ['title' => 'Configurer les alertes', 'type' => 'feature'],
            // Documentation
            ['title' => 'Documenter l\'API', 'type' => 'task'],
            ['title' => 'Créer le guide utilisateur', 'type' => 'story'],
            ['title' => 'Mettre à jour le README', 'type' => 'task'],
            ['title' => 'Documenter les processus', 'type' => 'task'],
            ['title' => 'Créer les tutoriels vidéo', 'type' => 'story'],
        ];

        $now = Carbon::now();
        $totalTasksCreated = 0;
        $totalSprintsCreated = 0;
        $totalEpicsCreated = 0;

        foreach ($projects as $project) {
            $members = $project->members;
            if ($members->isEmpty()) {
                $manager = $project->manager ?? User::first();
                if (! $manager) {
                    continue;
                }
                $members = collect([$manager]);
            }

            $reporter = $project->manager ?? $members->first();
            $projectKey = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $project->name), 0, 4));

            // Get existing task count for unique keys
            $existingTaskCount = Task::where('taskable_type', Project::class)
                ->where('taskable_id', $project->id)
                ->count();
            $taskNumber = $existingTaskCount + 1;

            // Create sprints for this project (if none exist)
            if ($project->sprints()->count() === 0) {
                $sprints = $this->createSprintsForProject($project, $now);
                $totalSprintsCreated += count($sprints);
            } else {
                $sprints = $project->sprints->toArray();
            }

            // Create epics for this project
            $epics = $this->createEpicsForProject(
                $project,
                $projectKey,
                $taskNumber,
                $members,
                $reporter,
                $todoStatus,
                $inProgressStatus,
                $completedStatus,
                $taskPriorities,
                $now
            );
            $taskNumber += count($epics) * 6; // Each epic has ~5 stories + 1 epic task
            $totalEpicsCreated += count($epics);

            // Create historical tasks spread across 12 months
            for ($monthsAgo = 0; $monthsAgo < 12; $monthsAgo++) {
                $monthStart = $now->copy()->subMonths($monthsAgo)->startOfMonth();
                $monthEnd = $now->copy()->subMonths($monthsAgo)->endOfMonth();

                // Number of tasks per month varies (8-20)
                $tasksThisMonth = rand(8, 20);

                for ($i = 0; $i < $tasksThisMonth; $i++) {
                    $template = $taskTemplates[array_rand($taskTemplates)];
                    $assignee = $members->random();

                    // Random date within the month (use midday to avoid DST issues)
                    $maxDay = $monthsAgo === 0 ? $now->day : $monthEnd->day;
                    $randomDay = rand(1, $maxDay);
                    $createdAt = $monthStart->copy()->addDays($randomDay - 1)->setTime(12, 0, 0);

                    // Determine status based on age
                    $status = $this->determineStatus(
                        $monthsAgo,
                        $todoStatus,
                        $inProgressStatus,
                        $completedStatus,
                        $underReviewStatus,
                        $blockedStatus,
                        $cancelledStatus
                    );

                    // Set updated_at for completed tasks
                    $updatedAt = $createdAt;
                    if ($status && $status->name === 'completed') {
                        // Completed between 1-14 days after creation
                        $daysOld = $createdAt->diffInDays($now);
                        $completionDays = rand(1, min(14, max(1, $daysOld)));
                        $updatedAt = $createdAt->copy()->addDays($completionDays);
                        if ($updatedAt->gt($now)) {
                            $updatedAt = $now;
                        }
                    }

                    // Assign to sprint if available
                    $sprintId = null;
                    if (! empty($sprints) && rand(1, 100) > 30) { // 70% assigned to sprints
                        $sprint = $sprints[array_rand($sprints)];
                        $sprintId = $sprint['id'] ?? $sprint->id ?? null;
                    }

                    // Due date: some tasks have due dates
                    $dueDate = null;
                    if (rand(1, 100) > 20) { // 80% have due dates
                        $dueDate = $createdAt->copy()->addDays(rand(5, 30));
                    }

                    $task = Task::create([
                        'taskable_type' => Project::class,
                        'taskable_id' => $project->id,
                        'sprint_id' => $sprintId,
                        'key' => "{$projectKey}-{$taskNumber}",
                        'title' => $template['title'].' #'.rand(100, 999),
                        'description' => 'Tâche créée automatiquement pour les données historiques de test.',
                        'status_id' => $status?->id,
                        'type' => $template['type'],
                        'priority' => $taskPriorities[array_rand($taskPriorities)],
                        'assigned_to' => $assignee->id,
                        'reporter_id' => $reporter->id,
                        'story_points' => rand(1, 13),
                        'estimated_hours' => rand(2, 40),
                        'actual_hours' => ($status && $status->name === 'completed') ? rand(1, 45) : null,
                        'due_date' => $dueDate,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt,
                    ]);

                    $taskNumber++;
                    $totalTasksCreated++;
                }
            }
        }

        $this->command->info('ProjectTaskHistoricalSeeder completed.');
        $this->command->info("Created {$totalTasksCreated} tasks, {$totalSprintsCreated} sprints, {$totalEpicsCreated} epics for {$projects->count()} projects.");
    }

    /**
     * Create sprints for a project with historical dates.
     */
    private function createSprintsForProject(Project $project, Carbon $now): array
    {
        $sprints = [];
        $sprintGoals = [
            'Améliorer les performances',
            'Implémenter nouvelles fonctionnalités',
            'Corriger les bugs critiques',
            'Mettre à jour l\'interface',
            'Intégrer les API tierces',
            'Optimiser la base de données',
        ];

        // Create 4-6 sprints over the last 6 months
        $numberOfSprints = rand(4, 6);

        for ($i = 0; $i < $numberOfSprints; $i++) {
            $sprintStatus = $this->determineSprintStatus($i, $numberOfSprints);

            // Calculate sprint dates (use noon to avoid DST issues)
            $sprintStart = $now->copy()->subWeeks(($numberOfSprints - $i) * 2)->startOfWeek()->setTime(12, 0, 0);
            $sprintEnd = $sprintStart->copy()->addWeeks(2)->endOfWeek()->setTime(12, 0, 0);

            $sprint = Sprint::create([
                'project_id' => $project->id,
                'name' => 'Sprint '.($i + 1).' - '.$project->name,
                'goal' => $sprintGoals[array_rand($sprintGoals)],
                'start_date' => $sprintStart,
                'end_date' => $sprintEnd,
                'status' => $sprintStatus,
                'capacity' => rand(40, 100),
            ]);

            $sprints[] = $sprint;
        }

        return $sprints;
    }

    /**
     * Determine sprint status based on position.
     */
    private function determineSprintStatus(int $index, int $total): string
    {
        if ($index < $total - 2) {
            return 'completed';
        }
        if ($index === $total - 2) {
            return rand(0, 1) ? 'completed' : 'active';
        }
        if ($index === $total - 1) {
            return rand(0, 1) ? 'active' : 'planned';
        }

        return 'planned';
    }

    /**
     * Create epics for a project.
     */
    private function createEpicsForProject(
        Project $project,
        string $projectKey,
        int &$taskNumber,
        $members,
        $reporter,
        $todoStatus,
        $inProgressStatus,
        $completedStatus,
        array $taskPriorities,
        Carbon $now
    ): array {
        $epicTemplates = [
            [
                'title' => 'Authentification et Sécurité',
                'stories' => ['Connexion utilisateur', 'Inscription', 'Récupération mot de passe', 'Gestion profil', 'Double authentification'],
            ],
            [
                'title' => 'Dashboard et Reporting',
                'stories' => ['Layout dashboard', 'Graphiques performance', 'Export rapports', 'Filtres personnalisés', 'Notifications temps réel'],
            ],
            [
                'title' => 'Gestion des Fichiers',
                'stories' => ['Upload fichiers', 'Prévisualisation', 'Stockage cloud', 'Compression images', 'Gestion versions'],
            ],
        ];

        $epics = [];

        // Create 2-3 epics per project
        $selectedTemplates = array_slice($epicTemplates, 0, rand(2, 3));

        foreach ($selectedTemplates as $template) {
            $createdAt = $now->copy()->subMonths(rand(3, 10))->setTime(12, 0, 0);

            // Determine epic status
            $epicStatus = match (rand(1, 10)) {
                1, 2, 3, 4, 5 => $completedStatus, // 50% completed
                6, 7, 8 => $inProgressStatus, // 30% in progress
                default => $todoStatus, // 20% todo
            };

            $epic = Task::create([
                'taskable_type' => Project::class,
                'taskable_id' => $project->id,
                'key' => "{$projectKey}-{$taskNumber}",
                'title' => $template['title'],
                'description' => 'Epic créée pour regrouper les fonctionnalités associées.',
                'type' => 'epic',
                'status_id' => $epicStatus?->id,
                'priority' => $taskPriorities[array_rand($taskPriorities)],
                'assigned_to' => $members->random()->id,
                'reporter_id' => $reporter->id,
                'created_at' => $createdAt,
                'updated_at' => $epicStatus?->name === 'completed'
                    ? $createdAt->copy()->addDays(rand(14, 60))
                    : $createdAt,
            ]);

            $taskNumber++;
            $epics[] = $epic;

            // Create stories for this epic
            foreach ($template['stories'] as $storyTitle) {
                $storyCreatedAt = $createdAt->copy()->addDays(rand(1, 14))->setTime(14, 0, 0);

                // Story status based on epic status
                $storyStatus = match ($epicStatus?->name) {
                    'completed' => $completedStatus,
                    'in_progress' => match (rand(1, 4)) {
                        1, 2 => $completedStatus,
                        3 => $inProgressStatus,
                        default => $todoStatus,
                    },
                    default => $todoStatus,
                };

                Task::create([
                    'taskable_type' => Project::class,
                    'taskable_id' => $project->id,
                    'epic_id' => $epic->id,
                    'key' => "{$projectKey}-{$taskNumber}",
                    'title' => $storyTitle,
                    'description' => 'Story associée à l\'epic '.$template['title'],
                    'type' => 'story',
                    'status_id' => $storyStatus?->id,
                    'priority' => $taskPriorities[array_rand($taskPriorities)],
                    'assigned_to' => $members->random()->id,
                    'reporter_id' => $reporter->id,
                    'story_points' => rand(1, 8),
                    'estimated_hours' => rand(4, 24),
                    'actual_hours' => $storyStatus?->name === 'completed' ? rand(2, 30) : null,
                    'created_at' => $storyCreatedAt,
                    'updated_at' => $storyStatus?->name === 'completed'
                        ? $storyCreatedAt->copy()->addDays(rand(2, 20))
                        : $storyCreatedAt,
                ]);

                $taskNumber++;
            }
        }

        return $epics;
    }

    /**
     * Determine task status based on age (months ago).
     */
    private function determineStatus(
        int $monthsAgo,
        $todoStatus,
        $inProgressStatus,
        $completedStatus,
        $underReviewStatus,
        $blockedStatus,
        $cancelledStatus
    ) {
        // Older tasks are more likely to be completed
        if ($monthsAgo > 3) {
            // Tasks from 4+ months ago: 85% completed, 10% cancelled, 5% other
            $rand = rand(1, 100);
            if ($rand <= 85) {
                return $completedStatus;
            }
            if ($rand <= 95) {
                return $cancelledStatus;
            }

            return $todoStatus;
        }

        if ($monthsAgo >= 2) {
            // Tasks from 2-3 months ago: 75% completed, 10% in progress, 10% under review, 5% cancelled
            $rand = rand(1, 100);
            if ($rand <= 75) {
                return $completedStatus;
            }
            if ($rand <= 85) {
                return $inProgressStatus;
            }
            if ($rand <= 95) {
                return $underReviewStatus;
            }

            return $cancelledStatus;
        }

        if ($monthsAgo === 1) {
            // Tasks from 1 month ago: 60% completed, 20% in progress, 10% under review, 10% other
            $rand = rand(1, 100);
            if ($rand <= 60) {
                return $completedStatus;
            }
            if ($rand <= 80) {
                return $inProgressStatus;
            }
            if ($rand <= 90) {
                return $underReviewStatus;
            }
            if ($rand <= 95) {
                return $blockedStatus;
            }

            return $todoStatus;
        }

        // Current month: mix of statuses
        $rand = rand(1, 100);
        if ($rand <= 35) {
            return $completedStatus;
        }
        if ($rand <= 55) {
            return $inProgressStatus;
        }
        if ($rand <= 70) {
            return $underReviewStatus;
        }
        if ($rand <= 80) {
            return $blockedStatus;
        }
        if ($rand <= 85) {
            return $cancelledStatus;
        }

        return $todoStatus;
    }
}
