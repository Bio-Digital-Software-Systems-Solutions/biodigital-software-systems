<?php

namespace App\Services;

use App\Models\Group;
use App\Models\GroupActivity;
use App\Models\GroupMeeting;
use App\Models\IntegrationPathwayStep;
use App\Models\IntegrationPathwayTemplate;
use App\Models\IntegrationSuggestion;
use App\Models\VisitorAttendance;
use App\Models\VisitorIntegrationProgress;
use App\Models\VisitorVisit;

class IntegrationScoreService
{
    public function calculateScore(VisitorVisit $visitorVisit): float
    {
        $template = $this->getTemplate($visitorVisit);

        if (! $template) {
            return 0;
        }

        $steps = $template->orderedSteps;

        if ($steps->isEmpty()) {
            return 0;
        }

        $totalWeight = 0;
        $weightedScore = 0;

        foreach ($steps as $step) {
            $stepProgress = $this->evaluateStep($step, $visitorVisit);

            $this->updateStepProgress($visitorVisit, $step, $stepProgress);

            if ($step->is_required || $stepProgress > 0) {
                $weightedScore += $stepProgress * $step->weight;
                $totalWeight += $step->weight;
            }
        }

        $score = $totalWeight > 0 ? round($weightedScore / $totalWeight, 2) : 0;
        $score = min(100, max(0, $score));

        $visitorVisit->update([
            'integration_score' => $score,
            'integration_status' => $this->determineStatus($score, $visitorVisit),
        ]);

        $this->checkAndCreateSuggestion($visitorVisit, $score);

        return $score;
    }

    public function recalculateForGroup(Group $group): void
    {
        $visits = VisitorVisit::where('visitable_type', Group::class)
            ->where('visitable_id', $group->id)
            ->where('integration_status', '!=', 'integrated')
            ->get();

        foreach ($visits as $visit) {
            $this->calculateScore($visit);
        }
    }

    protected function getTemplate(VisitorVisit $visitorVisit): ?IntegrationPathwayTemplate
    {
        $targetType = $visitorVisit->visitable_type === Group::class ? 'group' : 'department';

        return IntegrationPathwayTemplate::active()
            ->forType($targetType)
            ->orderByDesc('is_default')
            ->first();
    }

    protected function evaluateStep(IntegrationPathwayStep $step, VisitorVisit $visitorVisit): float
    {
        return match ($step->type) {
            'attendance_count' => $this->evaluateAttendanceCount($step, $visitorVisit),
            'meeting_attendance' => $this->evaluateMeetingAttendance($step, $visitorVisit),
            'activity_participation' => $this->evaluateActivityParticipation($step, $visitorVisit),
            'training_completion' => $this->evaluateTrainingCompletion($step, $visitorVisit),
            'manual_approval' => $this->evaluateManualApproval($step, $visitorVisit),
            'custom' => $this->evaluateCustom($step, $visitorVisit),
            default => 0,
        };
    }

    protected function evaluateAttendanceCount(IntegrationPathwayStep $step, VisitorVisit $visitorVisit): float
    {
        $criteria = $step->criteria ?? [];
        $minAttendance = $criteria['min_attendance'] ?? 4;
        $periodWeeks = $criteria['period_weeks'] ?? 8;

        $count = VisitorAttendance::where('visitor_visit_id', $visitorVisit->id)
            ->where('status', 'present')
            ->where('attended_at', '>=', now()->subWeeks($periodWeeks))
            ->count();

        return min(100, ($count / $minAttendance) * 100);
    }

    protected function evaluateMeetingAttendance(IntegrationPathwayStep $step, VisitorVisit $visitorVisit): float
    {
        $criteria = $step->criteria ?? [];
        $minAttendance = $criteria['min_attendance'] ?? 4;
        $periodWeeks = $criteria['period_weeks'] ?? 8;

        $count = VisitorAttendance::where('visitor_visit_id', $visitorVisit->id)
            ->where('attendable_type', GroupMeeting::class)
            ->where('status', 'present')
            ->where('attended_at', '>=', now()->subWeeks($periodWeeks))
            ->count();

        return min(100, ($count / $minAttendance) * 100);
    }

    protected function evaluateActivityParticipation(IntegrationPathwayStep $step, VisitorVisit $visitorVisit): float
    {
        $criteria = $step->criteria ?? [];
        $minActivities = $criteria['min_activities'] ?? 3;
        $periodWeeks = $criteria['period_weeks'] ?? 8;

        $count = VisitorAttendance::where('visitor_visit_id', $visitorVisit->id)
            ->where('attendable_type', GroupActivity::class)
            ->where('status', 'present')
            ->where('attended_at', '>=', now()->subWeeks($periodWeeks))
            ->count();

        return min(100, ($count / $minActivities) * 100);
    }

    protected function evaluateTrainingCompletion(IntegrationPathwayStep $step, VisitorVisit $visitorVisit): float
    {
        $criteria = $step->criteria ?? [];
        $minTrainings = $criteria['min_trainings'] ?? 1;

        $count = VisitorAttendance::where('visitor_visit_id', $visitorVisit->id)
            ->where('attendable_type', 'App\\Models\\TrainingClass')
            ->where('status', 'present')
            ->count();

        return min(100, ($count / $minTrainings) * 100);
    }

    protected function evaluateManualApproval(IntegrationPathwayStep $step, VisitorVisit $visitorVisit): float
    {
        $progress = VisitorIntegrationProgress::where('visitor_visit_id', $visitorVisit->id)
            ->where('step_id', $step->id)
            ->first();

        if ($progress && $progress->status === 'completed') {
            return 100;
        }

        return 0;
    }

    protected function evaluateCustom(IntegrationPathwayStep $step, VisitorVisit $visitorVisit): float
    {
        $progress = VisitorIntegrationProgress::where('visitor_visit_id', $visitorVisit->id)
            ->where('step_id', $step->id)
            ->first();

        return $progress ? (float) $progress->progress_value : 0;
    }

    protected function updateStepProgress(VisitorVisit $visitorVisit, IntegrationPathwayStep $step, float $progressValue): void
    {
        $status = match (true) {
            $progressValue >= 100 => 'completed',
            $progressValue > 0 => 'in_progress',
            default => 'pending',
        };

        VisitorIntegrationProgress::updateOrCreate(
            [
                'visitor_visit_id' => $visitorVisit->id,
                'step_id' => $step->id,
            ],
            [
                'progress_value' => min(100, $progressValue),
                'status' => $status,
                'completed_at' => $status === 'completed' ? now() : null,
            ]
        );
    }

    protected function determineStatus(float $score, VisitorVisit $visitorVisit): string
    {
        if ($visitorVisit->integration_status === 'integrated') {
            return 'integrated';
        }

        return match (true) {
            $score >= 80 => 'ready',
            $score >= 25 => 'progressing',
            default => 'visiting',
        };
    }

    protected function checkAndCreateSuggestion(VisitorVisit $visitorVisit, float $score): void
    {
        if ($score < 80) {
            return;
        }

        if ($visitorVisit->integration_status === 'integrated') {
            return;
        }

        $existingSuggestion = IntegrationSuggestion::where('visitor_visit_id', $visitorVisit->id)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        if ($existingSuggestion) {
            return;
        }

        $leader = $this->getLeaderForVisit($visitorVisit);

        if (! $leader) {
            return;
        }

        IntegrationSuggestion::create([
            'visitor_visit_id' => $visitorVisit->id,
            'suggested_to' => $leader->id,
            'score_at_suggestion' => $score,
            'status' => 'pending',
        ]);
    }

    protected function getLeaderForVisit(VisitorVisit $visitorVisit): ?\App\Models\User
    {
        $visitable = $visitorVisit->visitable;

        if ($visitable instanceof Group) {
            return $visitable->leader;
        }

        if ($visitable instanceof \App\Models\Department) {
            return $visitable->headOfDepartment;
        }

        return null;
    }
}
