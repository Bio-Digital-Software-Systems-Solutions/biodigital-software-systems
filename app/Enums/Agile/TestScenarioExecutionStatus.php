<?php

namespace App\Enums\Agile;

enum TestScenarioExecutionStatus: string
{
    case NOT_RUN = 'not_run';
    case PASSED = 'passed';
    case FAILED = 'failed';
    case BLOCKED = 'blocked';

    public function label(): string
    {
        return __('agile.statuses.test_scenario.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::NOT_RUN => 'gray',
            self::PASSED => 'green',
            self::FAILED => 'red',
            self::BLOCKED => 'orange',
        };
    }

    public function isPassed(): bool
    {
        return $this === self::PASSED;
    }
}
