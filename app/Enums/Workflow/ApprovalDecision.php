<?php

namespace App\Enums\Workflow;

enum ApprovalDecision: string
{
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ABSTAINED = 'abstained';
    case DELEGATED = 'delegated';
    case REQUESTED_CHANGES = 'requested_changes';

    public function label(): string
    {
        return match ($this) {
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Rejeté',
            self::ABSTAINED => 'Abstention',
            self::DELEGATED => 'Délégué',
            self::REQUESTED_CHANGES => 'Modifications demandées',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::ABSTAINED => 'gray',
            self::DELEGATED => 'blue',
            self::REQUESTED_CHANGES => 'yellow',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::APPROVED => 'check',
            self::REJECTED => 'x-mark',
            self::ABSTAINED => 'minus',
            self::DELEGATED => 'arrow-right',
            self::REQUESTED_CHANGES => 'pencil',
        };
    }

    public function isPositive(): bool
    {
        return $this === self::APPROVED;
    }

    public function isNegative(): bool
    {
        return $this === self::REJECTED;
    }

    public function isNeutral(): bool
    {
        return in_array($this, [self::ABSTAINED, self::DELEGATED, self::REQUESTED_CHANGES]);
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Workflow\ApprovalDecision $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }
}
