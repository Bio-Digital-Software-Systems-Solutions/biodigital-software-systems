<?php

namespace App\Enums\Scheduling;

enum ShiftTaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Basse',
            self::MEDIUM => 'Moyenne',
            self::HIGH => 'Haute',
            self::URGENT => 'Urgente',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::LOW => 'Niedrig',
            self::MEDIUM => 'Mittel',
            self::HIGH => 'Hoch',
            self::URGENT => 'Dringend',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => 'gray',
            self::MEDIUM => 'blue',
            self::HIGH => 'orange',
            self::URGENT => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LOW => 'minus',
            self::MEDIUM => 'equals',
            self::HIGH => 'chevron-up',
            self::URGENT => 'exclamation',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::URGENT => 4,
            self::HIGH => 3,
            self::MEDIUM => 2,
            self::LOW => 1,
        };
    }
}
