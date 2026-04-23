<?php

namespace App\Observers;

use App\Models\VisitorAttendance;
use App\Services\IntegrationScoreService;

class VisitorAttendanceObserver
{
    public function __construct(protected IntegrationScoreService $scoreService) {}

    public function created(VisitorAttendance $attendance): void
    {
        $this->recalculate($attendance);
    }

    public function updated(VisitorAttendance $attendance): void
    {
        $this->recalculate($attendance);
    }

    public function deleted(VisitorAttendance $attendance): void
    {
        $this->recalculate($attendance);
    }

    protected function recalculate(VisitorAttendance $attendance): void
    {
        $visit = $attendance->visitorVisit;

        if ($visit && $visit->integration_status !== 'integrated') {
            $this->scoreService->calculateScore($visit);
        }
    }
}
