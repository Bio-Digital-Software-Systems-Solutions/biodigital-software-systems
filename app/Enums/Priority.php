<?php

namespace App\Enums;

enum Priority: string
{
    case LOWEST = 'lowest';
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case HIGHEST = 'highest';

    public function label(): string
    {
        return match ($this) {
            self::LOWEST => 'Très faible',
            self::LOW => 'Faible',
            self::MEDIUM => 'Moyenne',
            self::HIGH => 'Haute',
            self::HIGHEST => 'Critique',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::LOWEST => 'arrow-down',
            self::LOW => 'minus',
            self::MEDIUM => 'equal',
            self::HIGH => 'arrow-up',
            self::HIGHEST => 'alert-triangle',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LOWEST => 'gray',
            self::LOW => 'blue',
            self::MEDIUM => 'yellow',
            self::HIGH => 'orange',
            self::HIGHEST => 'red',
        };
    }
}
