<?php

namespace Database\Seeders;

use App\Enums\Scheduling\ShiftTaskStatus;
use App\Enums\Scheduling\TodoPriority;
use App\Models\Department;
use App\Models\Scheduling\DepartmentTodo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentTodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::with('users')->get();

        if ($departments->isEmpty()) {
            $this->command->warn('No departments found. Please run DepartmentSeeder first.');

            return;
        }

        $todoTemplates = [
            // Administrative tasks
            ['title' => 'Mettre à jour la documentation', 'priority' => TodoPriority::MEDIUM, 'estimated_minutes' => 60],
            ['title' => 'Réviser les procédures internes', 'priority' => TodoPriority::LOW, 'estimated_minutes' => 120],
            ['title' => 'Préparer le rapport mensuel', 'priority' => TodoPriority::HIGH, 'estimated_minutes' => 90],
            ['title' => 'Organiser la réunion d\'équipe', 'priority' => TodoPriority::MEDIUM, 'estimated_minutes' => 30],
            ['title' => 'Archiver les documents anciens', 'priority' => TodoPriority::LOW, 'estimated_minutes' => 45],
            // Technical tasks
            ['title' => 'Résoudre le bug critique', 'priority' => TodoPriority::URGENT, 'estimated_minutes' => 180],
            ['title' => 'Tester les nouvelles fonctionnalités', 'priority' => TodoPriority::HIGH, 'estimated_minutes' => 120],
            ['title' => 'Optimiser les performances', 'priority' => TodoPriority::MEDIUM, 'estimated_minutes' => 150],
            ['title' => 'Mettre à jour les dépendances', 'priority' => TodoPriority::LOW, 'estimated_minutes' => 60],
            ['title' => 'Refactoriser le code legacy', 'priority' => TodoPriority::MEDIUM, 'estimated_minutes' => 240],
            // Communication tasks
            ['title' => 'Répondre aux demandes clients', 'priority' => TodoPriority::HIGH, 'estimated_minutes' => 45],
            ['title' => 'Envoyer le newsletter', 'priority' => TodoPriority::MEDIUM, 'estimated_minutes' => 60],
            ['title' => 'Mettre à jour les réseaux sociaux', 'priority' => TodoPriority::LOW, 'estimated_minutes' => 30],
            ['title' => 'Préparer la présentation', 'priority' => TodoPriority::HIGH, 'estimated_minutes' => 120],
            ['title' => 'Rédiger le compte-rendu', 'priority' => TodoPriority::MEDIUM, 'estimated_minutes' => 45],
            // Project tasks
            ['title' => 'Planifier le sprint', 'priority' => TodoPriority::HIGH, 'estimated_minutes' => 60],
            ['title' => 'Définir les objectifs trimestriels', 'priority' => TodoPriority::HIGH, 'estimated_minutes' => 90],
            ['title' => 'Évaluer les risques du projet', 'priority' => TodoPriority::MEDIUM, 'estimated_minutes' => 75],
            ['title' => 'Mettre à jour le backlog', 'priority' => TodoPriority::MEDIUM, 'estimated_minutes' => 45],
            ['title' => 'Préparer la démo client', 'priority' => TodoPriority::URGENT, 'estimated_minutes' => 180],
            // Training & Development
            ['title' => 'Former les nouveaux membres', 'priority' => TodoPriority::HIGH, 'estimated_minutes' => 180],
            ['title' => 'Suivre une formation technique', 'priority' => TodoPriority::LOW, 'estimated_minutes' => 240],
            ['title' => 'Créer le guide d\'utilisation', 'priority' => TodoPriority::MEDIUM, 'estimated_minutes' => 120],
            ['title' => 'Documenter les best practices', 'priority' => TodoPriority::LOW, 'estimated_minutes' => 90],
            ['title' => 'Réaliser une revue de code', 'priority' => TodoPriority::MEDIUM, 'estimated_minutes' => 60],
        ];

        $now = Carbon::now();

        foreach ($departments as $department) {
            $members = $department->users;
            if ($members->isEmpty()) {
                // Use head of department or skip
                $headOfDepartment = $department->head_of_department ? User::find($department->head_of_department) : null;
                if (! $headOfDepartment) {
                    continue;
                }
                $members = collect([$headOfDepartment]);
            }

            $creator = $members->first();

            // Create tasks spread across different time periods for realistic statistics
            // Last 12 months of data
            for ($monthsAgo = 0; $monthsAgo < 12; $monthsAgo++) {
                $monthStart = $now->copy()->subMonths($monthsAgo)->startOfMonth();
                $monthEnd = $now->copy()->subMonths($monthsAgo)->endOfMonth();

                // Number of tasks per month varies (10-25)
                $tasksThisMonth = random_int(10, 25);

                for ($i = 0; $i < $tasksThisMonth; $i++) {
                    $template = $todoTemplates[array_rand($todoTemplates)];
                    $assignee = $members->random();

                    // Random date within the month (use midday to avoid DST issues)
                    $randomDay = random_int(1, min($monthEnd->day, $now->day));
                    $createdAt = $monthStart->copy()->addDays($randomDay - 1)->setTime(12, 0, 0);
                    if ($createdAt->gt($now)) {
                        $createdAt = $now->copy()->setTime(12, 0, 0);
                    }

                    // Determine status based on age and randomness
                    $daysOld = $createdAt->diffInDays($now);
                    $status = $this->determineStatus($monthsAgo);

                    // Set completion date for completed tasks
                    $completedAt = null;
                    $completedBy = null;
                    if ($status === ShiftTaskStatus::COMPLETED) {
                        // Completed between 1-14 days after creation
                        $completedAt = $createdAt->copy()->addDays(random_int(1, min(14, $daysOld ?: 1)));
                        $completedBy = $assignee->id;
                    }

                    // Due date: some tasks have due dates
                    $dueDate = null;
                    if (random_int(1, 100) > 30) { // 70% have due dates
                        $dueDate = $createdAt->copy()->addDays(random_int(3, 21));
                    }

                    DepartmentTodo::create([
                        'uuid' => Str::uuid()->toString(),
                        'department_id' => $department->id,
                        'assigned_to' => $assignee->id,
                        'created_by' => $creator->id,
                        'title' => $template['title'],
                        'description' => 'Tâche créée automatiquement pour les données de test.',
                        'status' => $status,
                        'priority' => $template['priority'],
                        'due_date' => $dueDate,
                        'estimated_minutes' => $template['estimated_minutes'],
                        'completed_at' => $completedAt,
                        'completed_by' => $completedBy,
                        'created_at' => $createdAt,
                        'updated_at' => $completedAt ?? $createdAt,
                    ]);
                }
            }
        }

        $this->command->info('DepartmentTodo seeder completed. Created tasks for '.$departments->count().' departments.');
    }

    /**
     * Determine task status based on age
     */
    private function determineStatus(int $monthsAgo): ShiftTaskStatus
    {
        // Older tasks are more likely to be completed
        if ($monthsAgo > 2) {
            // Tasks from 3+ months ago: 85% completed, 10% cancelled, 5% other
            $rand = random_int(1, 100);
            if ($rand <= 85) {
                return ShiftTaskStatus::COMPLETED;
            }
            if ($rand <= 95) {
                return ShiftTaskStatus::CANCELLED;
            }

            return ShiftTaskStatus::TODO;
        }

        if ($monthsAgo >= 1) {
            // Tasks from 1-2 months ago: 70% completed, 5% cancelled, 25% active
            $rand = random_int(1, 100);
            if ($rand <= 70) {
                return ShiftTaskStatus::COMPLETED;
            }
            if ($rand <= 75) {
                return ShiftTaskStatus::CANCELLED;
            }
            if ($rand <= 85) {
                return ShiftTaskStatus::IN_PROGRESS;
            }
            if ($rand <= 90) {
                return ShiftTaskStatus::BLOCKED;
            }

            return ShiftTaskStatus::TODO;
        }

        // Current month: mix of statuses
        $rand = random_int(1, 100);
        if ($rand <= 40) {
            return ShiftTaskStatus::COMPLETED;
        }
        if ($rand <= 60) {
            return ShiftTaskStatus::IN_PROGRESS;
        }
        if ($rand <= 70) {
            return ShiftTaskStatus::BLOCKED;
        }
        if ($rand <= 75) {
            return ShiftTaskStatus::CANCELLED;
        }

        return ShiftTaskStatus::TODO;
    }
}
