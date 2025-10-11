<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case PLANNING = 'planning';
    case ACTIVE = 'active';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLANNING => 'En planification',
            self::ACTIVE => 'Actif',
            self::ON_HOLD => 'En pause',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PLANNING => 'blue',
            self::ACTIVE => 'green',
            self::ON_HOLD => 'yellow',
            self::COMPLETED => 'gray',
            self::CANCELLED => 'red',
        };
    }
}
