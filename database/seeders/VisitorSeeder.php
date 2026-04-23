<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\GroupActivity;
use App\Models\GroupMeeting;
use App\Models\IntegrationPathwayTemplate;
use App\Models\IntegrationSuggestion;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitorAttendance;
use App\Models\VisitorVisit;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VisitorSeeder extends Seeder
{
    private array $firstNames = [
        'male' => ['Jean', 'Pierre', 'Paul', 'Marc', 'David', 'Samuel', 'Joseph', 'Emmanuel', 'Patrick', 'Olivier', 'Daniel', 'Éric', 'Michel', 'André', 'François', 'Thierry', 'Serge', 'Alain', 'Christian', 'Philippe', 'Blaise', 'Cédric', 'Arnaud', 'Kevin', 'Fabrice'],
        'female' => ['Marie', 'Anne', 'Claire', 'Sophie', 'Céline', 'Nathalie', 'Isabelle', 'Christine', 'Sandrine', 'Valérie', 'Florence', 'Élise', 'Chantal', 'Monique', 'Patricia', 'Sylvie', 'Brigitte', 'Béatrice', 'Joséphine', 'Grâce', 'Esther', 'Rachel', 'Sarah', 'Rebecca', 'Lydia'],
    ];

    private array $lastNames = [
        'Mbeki', 'Nguema', 'Koumba', 'Moussavou', 'Nzoghe', 'Obame', 'Essono', 'Ndong', 'Mba', 'Ondo',
        'Ntoutoume', 'Bibang', 'Engonga', 'Mezui', 'Edzang', 'Oyono', 'Bekale', 'Nze', 'Owono', 'Eyene',
        'Müller', 'Schmidt', 'Weber', 'Fischer', 'Wagner', 'Becker', 'Hoffmann', 'Koch', 'Richter', 'Klein',
        'Dupont', 'Martin', 'Bernard', 'Durand', 'Moreau', 'Laurent', 'Simon', 'Thomas', 'Robert', 'Petit',
    ];

    private array $sources = ['friend', 'online', 'event', 'walk_in', 'other'];

    public function run(): void
    {
        $groups = Group::with('leader')->get();

        if ($groups->isEmpty()) {
            $this->command->warn('No groups found. Please run GroupSeeder first.');

            return;
        }

        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->first()
            ?? User::first();

        // Create default integration pathway template
        $template = $this->createDefaultPathwayTemplate($admin);

        $this->command->info('Created default integration pathway template with '.count($template->steps).' steps.');

        // Generate visitors over 12 months (2-3 per week avg = ~120 visitors)
        $totalVisitors = 0;
        $startDate = now()->subMonths(12);

        foreach ($groups as $group) {
            $visitorsForGroup = $this->seedVisitorsForGroup($group, $admin, $startDate, $template);
            $totalVisitors += $visitorsForGroup;
            $this->command->info("  Group \"{$group->name}\": {$visitorsForGroup} visitors");
        }

        $this->command->info("VisitorSeeder completed. Created {$totalVisitors} visitor records across {$groups->count()} groups.");
    }

    private function createDefaultPathwayTemplate(User $admin): IntegrationPathwayTemplate
    {
        $template = IntegrationPathwayTemplate::create([
            'name' => 'Parcours d\'intégration standard',
            'description' => 'Parcours par défaut pour l\'intégration des visiteurs dans les groupes.',
            'target_type' => 'group',
            'is_default' => true,
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $steps = [
            [
                'name' => 'Présence régulière aux réunions',
                'description' => 'Le visiteur doit assister régulièrement aux réunions du groupe.',
                'order_index' => 0,
                'type' => 'meeting_attendance',
                'criteria' => ['min_attendance' => 6, 'period_weeks' => 12],
                'weight' => 4,
                'is_required' => true,
            ],
            [
                'name' => 'Participation aux activités',
                'description' => 'Participer activement aux activités organisées par le groupe.',
                'order_index' => 1,
                'type' => 'activity_participation',
                'criteria' => ['min_activities' => 3, 'period_weeks' => 12],
                'weight' => 3,
                'is_required' => true,
            ],
            [
                'name' => 'Présence globale',
                'description' => 'Nombre total de présences enregistrées.',
                'order_index' => 2,
                'type' => 'attendance_count',
                'criteria' => ['min_attendance' => 10, 'period_weeks' => 12],
                'weight' => 2,
                'is_required' => true,
            ],
            [
                'name' => 'Approbation du responsable',
                'description' => 'Le responsable du groupe valide l\'intégration du visiteur.',
                'order_index' => 3,
                'type' => 'manual_approval',
                'criteria' => null,
                'weight' => 1,
                'is_required' => false,
            ],
        ];

        foreach ($steps as $stepData) {
            $template->steps()->create($stepData);
        }

        $template->load('steps');

        return $template;
    }

    private function seedVisitorsForGroup(Group $group, User $admin, Carbon $startDate, IntegrationPathwayTemplate $template): int
    {
        // 2-3 visitors/week over 52 weeks = ~104-156 visitors total across all groups
        // Split evenly: each group gets ~15-22 visitors
        $numVisitors = rand(14, 22);
        $weekSpan = 52;
        $visitors = [];

        // Create group activities and meetings for attendance tracking
        $activities = $this->ensureGroupActivities($group, $admin, $startDate);
        $meetings = $this->ensureGroupMeetings($group, $admin, $startDate);

        for ($i = 0; $i < $numVisitors; $i++) {
            $gender = fake()->randomElement(['male', 'female']);
            $firstName = fake()->randomElement($this->firstNames[$gender]);
            $lastName = fake()->randomElement($this->lastNames);

            // Distribute visitors across 12 months
            $weekOffset = rand(0, $weekSpan - 1);
            $firstVisitDate = $startDate->copy()->addWeeks($weekOffset)->addDays(rand(0, 6));

            if ($firstVisitDate->isAfter(now())) {
                $firstVisitDate = now()->subDays(rand(1, 14));
            }

            $visitor = Visitor::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => strtolower($firstName).'.'.strtolower($lastName).rand(1, 999).'@example.com',
                'phone' => fake()->optional(0.7)->phoneNumber(),
                'city' => fake()->optional(0.5)->randomElement(['Munich', 'Augsburg', 'Ingolstadt', 'Regensburg', 'Nürnberg']),
                'country' => 'Allemagne',
                'gender' => $gender,
                'date_of_birth' => fake()->optional(0.4)->dateTimeBetween('-55 years', '-18 years'),
                'source' => fake()->randomElement($this->sources),
                'first_visit_date' => $firstVisitDate,
                'status' => 'active',
                'notes' => fake()->optional(0.3)->sentence(),
                'created_by' => $admin->id,
            ]);

            // Create visitor visit (link to group)
            $invitedBy = $group->users->isNotEmpty() ? $group->users->random()->id : null;
            $visit = VisitorVisit::create([
                'visitor_id' => $visitor->id,
                'visitable_type' => Group::class,
                'visitable_id' => $group->id,
                'first_visited_at' => $firstVisitDate,
                'integration_score' => 0,
                'integration_status' => 'visiting',
                'invited_by' => $invitedBy,
                'notes' => fake()->optional(0.2)->sentence(),
            ]);

            // Generate attendance records based on visitor regularity
            $this->generateAttendanceRecords($visit, $firstVisitDate, $activities, $meetings, $admin);

            // Calculate integration score
            $score = $this->calculateAndSetScore($visit, $template);

            $visitors[] = ['visitor' => $visitor, 'visit' => $visit, 'score' => $score];
        }

        // Create integration suggestions for visitors with score >= 80
        $this->createSuggestions($group, $visitors);

        // Mark some older visitors as integrated
        $this->integrateOldVisitors($visitors);

        return $numVisitors;
    }

    private function ensureGroupActivities(Group $group, User $admin, Carbon $startDate): array
    {
        $existing = GroupActivity::where('group_id', $group->id)->get();

        if ($existing->count() >= 10) {
            return $existing->all();
        }

        $activities = $existing->all();
        $types = ['meeting', 'task', 'event', 'other'];
        $titles = [
            'Étude biblique', 'Prière du soir', 'Sortie d\'équipe', 'Formation leadership',
            'Culte de louange', 'Journée de service', 'Atelier créatif', 'Soirée de partage',
            'Conférence invité', 'Retraite spirituelle', 'Visite pastorale', 'Séance de mentorat',
        ];

        for ($i = $existing->count(); $i < 24; $i++) {
            $date = $startDate->copy()->addWeeks(rand(0, 51));
            if ($date->isAfter(now())) {
                $date = now()->subDays(rand(1, 30));
            }

            $activities[] = GroupActivity::create([
                'group_id' => $group->id,
                'created_by' => $admin->id,
                'title' => $titles[array_rand($titles)].' #'.($i + 1),
                'description' => fake()->optional()->sentence(),
                'activity_date' => $date->format('Y-m-d'),
                'start_time' => fake()->randomElement(['09:00', '14:00', '18:00', '19:00']),
                'end_time' => fake()->randomElement(['11:00', '16:00', '20:00', '21:00']),
                'status' => $date->isBefore(now()) ? 'completed' : 'planned',
                'type' => fake()->randomElement($types),
                'location' => fake()->optional(0.6)->randomElement(['Salle A', 'Salle B', 'Église principale', 'En ligne']),
            ]);
        }

        return $activities;
    }

    private function ensureGroupMeetings(Group $group, User $admin, Carbon $startDate): array
    {
        $existing = GroupMeeting::where('group_id', $group->id)->get();

        return $existing->all();
    }

    private function generateAttendanceRecords(VisitorVisit $visit, Carbon $firstVisitDate, array $activities, array $meetings, User $admin): void
    {
        // Determine visitor regularity profile
        $profile = fake()->randomElement(['very_regular', 'regular', 'occasional', 'rare']);
        $attendanceRate = match ($profile) {
            'very_regular' => rand(75, 95) / 100,
            'regular' => rand(50, 74) / 100,
            'occasional' => rand(25, 49) / 100,
            'rare' => rand(5, 24) / 100,
        };

        // Filter activities that happened after the visitor's first visit
        $eligibleActivities = collect($activities)->filter(
            fn ($a) => Carbon::parse($a->activity_date)->isAfter($firstVisitDate)
                && Carbon::parse($a->activity_date)->isBefore(now())
        );

        foreach ($eligibleActivities as $activity) {
            if (fake()->boolean((int) ($attendanceRate * 100))) {
                $status = fake()->randomElement(
                    array_merge(
                        array_fill(0, 80, 'present'),
                        array_fill(0, 10, 'late'),
                        array_fill(0, 5, 'excused'),
                        array_fill(0, 5, 'absent')
                    )
                );

                VisitorAttendance::create([
                    'visitor_id' => $visit->visitor_id,
                    'visitor_visit_id' => $visit->id,
                    'attendable_type' => GroupActivity::class,
                    'attendable_id' => $activity->id,
                    'attended_at' => Carbon::parse($activity->activity_date)->setHour(rand(9, 19)),
                    'status' => $status,
                    'recorded_by' => $admin->id,
                ]);
            }
        }

        // Also record attendance for meetings
        $eligibleMeetings = collect($meetings)->filter(
            fn ($m) => $m->created_at->isAfter($firstVisitDate)
                && $m->created_at->isBefore(now())
        );

        foreach ($eligibleMeetings as $meeting) {
            if (fake()->boolean((int) ($attendanceRate * 100))) {
                VisitorAttendance::create([
                    'visitor_id' => $visit->visitor_id,
                    'visitor_visit_id' => $visit->id,
                    'attendable_type' => GroupMeeting::class,
                    'attendable_id' => $meeting->id,
                    'attended_at' => $meeting->created_at,
                    'status' => fake()->randomElement(['present', 'present', 'present', 'late']),
                    'recorded_by' => $admin->id,
                ]);
            }
        }
    }

    private function calculateAndSetScore(VisitorVisit $visit, IntegrationPathwayTemplate $template): float
    {
        $presentCount = VisitorAttendance::where('visitor_visit_id', $visit->id)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $activityCount = VisitorAttendance::where('visitor_visit_id', $visit->id)
            ->where('attendable_type', GroupActivity::class)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $meetingCount = VisitorAttendance::where('visitor_visit_id', $visit->id)
            ->where('attendable_type', GroupMeeting::class)
            ->whereIn('status', ['present', 'late'])
            ->count();

        // Calculate score based on template steps
        $totalWeight = 0;
        $weightedScore = 0;

        foreach ($template->steps as $step) {
            $stepProgress = match ($step->type) {
                'meeting_attendance' => min(100, ($meetingCount / max(1, $step->criteria['min_attendance'] ?? 6)) * 100),
                'activity_participation' => min(100, ($activityCount / max(1, $step->criteria['min_activities'] ?? 3)) * 100),
                'attendance_count' => min(100, ($presentCount / max(1, $step->criteria['min_attendance'] ?? 10)) * 100),
                'manual_approval' => 0,
                default => 0,
            };

            if ($step->is_required || $stepProgress > 0) {
                $weightedScore += $stepProgress * $step->weight;
                $totalWeight += $step->weight;
            }
        }

        $score = $totalWeight > 0 ? round($weightedScore / $totalWeight, 2) : 0;
        $score = min(100, max(0, $score));

        $status = match (true) {
            $score >= 80 => 'ready',
            $score >= 25 => 'progressing',
            default => 'visiting',
        };

        $visit->update([
            'integration_score' => $score,
            'integration_status' => $status,
        ]);

        return $score;
    }

    private function createSuggestions(Group $group, array $visitors): void
    {
        $leader = $group->leader;
        if (! $leader) {
            return;
        }

        foreach ($visitors as $data) {
            if ($data['score'] >= 80 && $data['visit']->integration_status === 'ready') {
                // 60% chance of creating a suggestion
                if (fake()->boolean(60)) {
                    IntegrationSuggestion::create([
                        'visitor_visit_id' => $data['visit']->id,
                        'suggested_to' => $leader->id,
                        'score_at_suggestion' => $data['score'],
                        'status' => fake()->randomElement(['pending', 'pending', 'pending', 'accepted', 'deferred']),
                        'responded_at' => fake()->boolean(30) ? now()->subDays(rand(1, 30)) : null,
                    ]);
                }
            }
        }
    }

    private function integrateOldVisitors(array $visitors): void
    {
        foreach ($visitors as $data) {
            $visit = $data['visit'];
            $visitor = $data['visitor'];

            // Visitors who first visited > 8 months ago with high score are integrated
            if ($visit->first_visited_at->isBefore(now()->subMonths(8)) && $data['score'] >= 75) {
                if (fake()->boolean(40)) {
                    $visit->update([
                        'integration_status' => 'integrated',
                    ]);
                    $visitor->update([
                        'status' => 'integrated',
                    ]);
                }
            }
        }
    }
}
