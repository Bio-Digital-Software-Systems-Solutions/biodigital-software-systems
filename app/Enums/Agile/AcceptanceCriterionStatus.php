<?php

namespace App\Enums\Agile;

enum AcceptanceCriterionStatus: string
{
    case PENDING = 'pending';
    case IN_REVIEW = 'in_review';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return __('agile.statuses.acceptance_criterion.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::IN_REVIEW => 'yellow',
            self::VALIDATED => 'green',
            self::REJECTED => 'red',
        };
    }

    public function isValidated(): bool
    {
        return $this === self::VALIDATED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::VALIDATED, self::REJECTED], true);
    }
}
