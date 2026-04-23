<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\GroupTodo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GroupTodoSeeder extends Seeder
{
    /**
     * Seed group todos with realistic data spread over 12 months
     * to show task evolution and velocity progression.
     */
    public function run(): void
    {
        $groups = Group::with('users')->get();

        if ($groups->isEmpty()) {
            $this->command->warn('No groups found. Please run GroupSeeder first.');

            return;
        }

        $todoTemplates = [
            // Organisational
            ['title' => 'Préparer l\'ordre du jour de la réunion', 'priority' => 'medium'],
            ['title' => 'Rédiger le compte-rendu hebdomadaire', 'priority' => 'medium'],
            ['title' => 'Mettre à jour le planning du groupe', 'priority' => 'high'],
            ['title' => 'Organiser l\'événement du groupe', 'priority' => 'high'],
            ['title' => 'Contacter les membres absents', 'priority' => 'low'],
            // Project work
            ['title' => 'Finaliser le document de travail', 'priority' => 'high'],
            ['title' => 'Préparer la présentation trimestrielle', 'priority' => 'critical'],
            ['title' => 'Réviser les objectifs du groupe', 'priority' => 'medium'],
            ['title' => 'Coordonner avec les autres groupes', 'priority' => 'medium'],
            ['title' => 'Soumettre le rapport d\'activité', 'priority' => 'high'],
            // Communication
            ['title' => 'Envoyer le résumé aux membres', 'priority' => 'low'],
            ['title' => 'Publier les annonces du groupe', 'priority' => 'medium'],
            ['title' => 'Partager les ressources de formation', 'priority' => 'low'],
            ['title' => 'Planifier la session de brainstorming', 'priority' => 'medium'],
            ['title' => 'Mettre à jour la page du groupe', 'priority' => 'low'],
            // Follow-up
            ['title' => 'Suivre les actions en cours', 'priority' => 'high'],
            ['title' => 'Évaluer les résultats du mois', 'priority' => 'medium'],
            ['title' => 'Préparer le budget prévisionnel', 'priority' => 'critical'],
            ['title' => 'Archiver les anciens documents', 'priority' => 'low'],
            ['title' => 'Vérifier les échéances à venir', 'priority' => 'high'],
        ];

        $now = Carbon::now();
        $totalCreated = 0;

        foreach ($groups as $group) {
            $members = $group->users;
            if ($members->isEmpty()) {
                $leader = $group->leader_id ? User::find($group->leader_id) : null;
                if (! $leader) {
                    continue;
                }
                $members = collect([$leader]);
            }

            $creator = $members->first();

            // Create tasks spread across 12 months
            for ($monthsAgo = 0; $monthsAgo < 12; $monthsAgo++) {
                $monthStart = $now->copy()->subMonths($monthsAgo)->startOfMonth();
                $monthEnd = $now->copy()->subMonths($monthsAgo)->endOfMonth();

                // Increasing number of tasks over time (growth trend)
                $baseTasks = random_int(8, 15);
                // More recent months have slightly more tasks
                $tasksThisMonth = $baseTasks + max(0, (12 - $monthsAgo) - 6);

                for ($i = 0; $i < $tasksThisMonth; $i++) {
                    $template = $todoTemplates[array_rand($todoTemplates)];
                    $assignee = $members->random();

                    // Random date within the month
                    $maxDay = $monthsAgo === 0
                        ? min($monthEnd->day, $now->day)
                        : $monthEnd->day;
                    $randomDay = random_int(1, max(1, $maxDay));
                    $createdAt = $monthStart->copy()->addDays($randomDay - 1)->setTime(
                        random_int(8, 17),
                        random_int(0, 59),
                        0
                    );
                    if ($createdAt->isAfter($now)) {
                        $createdAt = $now->copy()->subHours(random_int(1, 48));
                    }

                    // Determine status based on age
                    $status = $this->determineStatus($monthsAgo);

                    // Completion data for completed tasks
                    $completedAt = null;
                    $completedBy = null;
                    if ($status === 'completed') {
                        $daysToComplete = random_int(1, min(14, max(1, $createdAt->diffInDays($now))));
                        $completedAt = $createdAt->copy()->addDays($daysToComplete);
                        if ($completedAt->isAfter($now)) {
                            $completedAt = $now->copy()->subMinutes(random_int(1, 120));
                        }
                        $completedBy = $assignee->id;
                    }

                    // Due date: 75% of tasks have one
                    $dueDate = null;
                    if (random_int(1, 100) <= 75) {
                        $dueDate = $createdAt->copy()->addDays(random_int(3, 21));
                    }

                    GroupTodo::create([
                        'uuid' => Str::uuid()->toString(),
                        'group_id' => $group->id,
                        'assigned_to' => $assignee->id,
                        'created_by' => $creator->id,
                        'title' => $template['title'],
                        'description' => 'Tâche générée pour les données de démonstration.',
                        'status' => $status,
                        'priority' => $template['priority'],
                        'due_date' => $dueDate,
                        'completed_at' => $completedAt,
                        'completed_by' => $completedBy,
                        'created_at' => $createdAt,
                        'updated_at' => $completedAt ?? $createdAt,
                    ]);

                    $totalCreated++;
                }
            }
        }

        $this->command->info("GroupTodoSeeder completed. Created {$totalCreated} tasks across {$groups->count()} groups.");
    }

    /**
     * Determine task status based on age (months ago).
     * Older tasks are more likely completed.
     */
    private function determineStatus(int $monthsAgo): string
    {
        if ($monthsAgo > 2) {
            // 3+ months old: 85% completed, 10% cancelled, 5% pending
            $rand = random_int(1, 100);
            if ($rand <= 85) {
                return 'completed';
            }
            if ($rand <= 95) {
                return 'cancelled';
            }

            return 'pending';
        }

        if ($monthsAgo >= 1) {
            // 1-2 months old: 70% completed, 5% cancelled, 15% in_progress, 10% pending
            $rand = random_int(1, 100);
            if ($rand <= 70) {
                return 'completed';
            }
            if ($rand <= 75) {
                return 'cancelled';
            }
            if ($rand <= 90) {
                return 'in_progress';
            }

            return 'pending';
        }

        // Current month: 40% completed, 25% in_progress, 25% pending, 10% cancelled
        $rand = random_int(1, 100);
        if ($rand <= 40) {
            return 'completed';
        }
        if ($rand <= 65) {
            return 'in_progress';
        }
        if ($rand <= 90) {
            return 'pending';
        }

        return 'cancelled';
    }
}
