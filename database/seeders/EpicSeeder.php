<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use Illuminate\Database\Seeder;

class EpicSeeder extends Seeder
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

        $epicTemplates = [
            [
                'title' => 'Authentification et Gestion des Utilisateurs',
                'description' => 'Implémenter un système complet d\'authentification incluant la connexion, l\'inscription, la récupération de mot de passe et la gestion des profils utilisateurs.',
                'color' => '#3B82F6',
                'stories' => [
                    'Création du formulaire de connexion',
                    'Implémentation de l\'inscription utilisateur',
                    'Système de récupération de mot de passe',
                    'Page de profil utilisateur',
                    'Gestion des rôles et permissions',
                ],
            ],
            [
                'title' => 'Tableau de Bord et Analytics',
                'description' => 'Développer un tableau de bord interactif avec des graphiques et statistiques pour visualiser les données clés du système.',
                'color' => '#10B981',
                'stories' => [
                    'Conception du layout du dashboard',
                    'Intégration des graphiques de performance',
                    'Widget de statistiques en temps réel',
                    'Export des rapports en PDF',
                    'Filtres et paramètres personnalisables',
                ],
            ],
            [
                'title' => 'Système de Notifications',
                'description' => 'Mettre en place un système de notifications multicanal (email, push, in-app) pour informer les utilisateurs des événements importants.',
                'color' => '#F59E0B',
                'stories' => [
                    'Notifications in-app',
                    'Intégration des emails transactionnels',
                    'Notifications push mobile',
                    'Centre de préférences de notification',
                    'Historique des notifications',
                ],
            ],
            [
                'title' => 'Gestion des Fichiers',
                'description' => 'Implémenter un système robuste de gestion des fichiers avec upload, stockage cloud et prévisualisation.',
                'color' => '#8B5CF6',
                'stories' => [
                    'Upload de fichiers avec drag & drop',
                    'Intégration stockage cloud (S3)',
                    'Prévisualisation des documents',
                    'Gestion des versions de fichiers',
                    'Compression et optimisation des images',
                ],
            ],
            [
                'title' => 'API et Intégrations Tierces',
                'description' => 'Développer une API REST complète et intégrer les services tiers nécessaires au fonctionnement de l\'application.',
                'color' => '#EF4444',
                'stories' => [
                    'Documentation API avec OpenAPI/Swagger',
                    'Authentification API (tokens)',
                    'Rate limiting et throttling',
                    'Webhooks pour événements externes',
                    'Intégration paiement (Stripe)',
                ],
            ],
            [
                'title' => 'Performance et Optimisation',
                'description' => 'Optimiser les performances de l\'application pour garantir une expérience utilisateur fluide.',
                'color' => '#06B6D4',
                'stories' => [
                    'Mise en cache des requêtes',
                    'Optimisation des requêtes SQL',
                    'Lazy loading des composants',
                    'CDN pour les assets statiques',
                    'Monitoring et alertes performance',
                ],
            ],
        ];

        $taskPriorities = ['lowest', 'low', 'medium', 'high', 'highest'];
        $storyTypes = ['story', 'task', 'feature'];

        foreach ($projects as $project) {
            // Check if project already has epics
            $existingEpics = Task::where('taskable_type', Project::class)
                ->where('taskable_id', $project->id)
                ->where('type', 'epic')
                ->count();

            if ($existingEpics > 0) {
                continue;
            }

            $projectKey = strtoupper(substr((string) preg_replace('/[^a-zA-Z]/', '', $project->name), 0, 4));
            $existingTaskCount = Task::where('taskable_type', Project::class)
                ->where('taskable_id', $project->id)
                ->count();

            $members = $project->members;
            if ($members->isEmpty()) {
                $members = collect([$project->manager ?? \App\Models\User::first()]);
            }

            // Select 2-3 random epic templates for this project
            $selectedTemplates = collect($epicTemplates)->random(random_int(2, 3));
            $taskNumber = $existingTaskCount + 1;

            foreach ($selectedTemplates as $template) {
                // Determine epic status based on project status
                $projectStatusValue = $project->status instanceof \App\Enums\ProjectStatus
                    ? $project->status->value
                    : $project->status;

                $epicStatus = match ($projectStatusValue) {
                    'completed' => $completedStatus,
                    'active' => fake()->randomElement([$inProgressStatus, $todoStatus]),
                    'planning' => $todoStatus,
                    default => $todoStatus,
                };

                // Create the epic (a task with type='epic')
                $epic = Task::create([
                    'taskable_type' => Project::class,
                    'taskable_id' => $project->id,
                    'key' => "{$projectKey}-{$taskNumber}",
                    'title' => $template['title'],
                    'description' => $template['description'],
                    'type' => 'epic',
                    'status_id' => $epicStatus?->id,
                    'priority' => $taskPriorities[array_rand($taskPriorities)],
                    'assigned_to' => $members->random()->id,
                    'reporter_id' => $project->project_manager_id,
                    'story_points' => null, // Epics don't have story points directly
                    'labels' => ['epic', 'feature-set'],
                    'custom_fields' => [
                        'color' => $template['color'],
                        'progress' => $epicStatus?->name === 'completed' ? 100 : random_int(0, 80),
                    ],
                ]);

                $taskNumber++;

                // Create child stories/tasks for this epic
                $storyStatuses = $this->getStoryStatusesForEpic($epicStatus, $todoStatus, $inProgressStatus, $completedStatus, $underReviewStatus);

                foreach ($template['stories'] as $storyTitle) {
                    $storyStatus = $storyStatuses[array_rand($storyStatuses)];

                    Task::create([
                        'taskable_type' => Project::class,
                        'taskable_id' => $project->id,
                        'epic_id' => $epic->id,
                        'key' => "{$projectKey}-{$taskNumber}",
                        'title' => $storyTitle,
                        'description' => fake()->paragraph(2),
                        'type' => $storyTypes[array_rand($storyTypes)],
                        'status_id' => $storyStatus?->id,
                        'priority' => $taskPriorities[array_rand($taskPriorities)],
                        'assigned_to' => $members->random()->id,
                        'reporter_id' => $project->project_manager_id,
                        'story_points' => random_int(1, 8),
                        'estimated_hours' => random_int(4, 24),
                        'actual_hours' => $storyStatus?->name === 'completed' ? random_int(2, 30) : null,
                        'due_date' => $project->end_date
                            ? fake()->dateTimeBetween($project->start_date ?? 'now', $project->end_date)
                            : fake()->dateTimeBetween('now', '+2 months'),
                    ]);

                    $taskNumber++;
                }
            }
        }

        $this->command->info('Epics seeded successfully with associated stories/tasks.');
    }

    /**
     * Get appropriate story statuses based on epic status.
     */
    private function getStoryStatusesForEpic(
        ?Status $epicStatus,
        ?Status $todoStatus,
        ?Status $inProgressStatus,
        ?Status $completedStatus,
        ?Status $underReviewStatus
    ): array {
        $validStatuses = array_filter([$todoStatus, $inProgressStatus, $completedStatus, $underReviewStatus], fn (?\App\Models\Status $s): bool => $s instanceof \App\Models\Status);

        if ($validStatuses === []) {
            return [$todoStatus];
        }

        if ($epicStatus?->name === 'completed') {
            return array_filter([$completedStatus], fn (?\App\Models\Status $s): bool => $s instanceof \App\Models\Status);
        }

        if ($epicStatus?->name === 'in_progress') {
            return array_filter([$todoStatus, $inProgressStatus, $underReviewStatus, $completedStatus], fn (?\App\Models\Status $s): bool => $s instanceof \App\Models\Status);
        }

        // For todo/pending epics, stories are mostly todo
        return array_filter([$todoStatus, $inProgressStatus], fn (?\App\Models\Status $s): bool => $s instanceof \App\Models\Status);
    }
}
