<?php

namespace App\Enums\Need;

enum NeedPriority: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public function label(): string
    {
        return match ($this) {
            self::CRITICAL => 'Critique',
            self::HIGH => 'Haute',
            self::MEDIUM => 'Moyenne',
            self::LOW => 'Basse',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CRITICAL => 'red',
            self::HIGH => 'orange',
            self::MEDIUM => 'yellow',
            self::LOW => 'green',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CRITICAL => 'exclamation-triangle',
            self::HIGH => 'arrow-up',
            self::MEDIUM => 'minus',
            self::LOW => 'arrow-down',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::CRITICAL => 1,
            self::HIGH => 2,
            self::MEDIUM => 3,
            self::LOW => 4,
        };
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Need\NeedPriority $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
            'icon' => $case->icon(),
        ], self::cases());
    }
}
