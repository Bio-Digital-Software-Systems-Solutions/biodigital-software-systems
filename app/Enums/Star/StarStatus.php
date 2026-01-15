<?php

namespace App\Enums\Star;

enum StarStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ON_BREAK = 'on_break';
    case GRADUATED = 'graduated';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Actif',
            self::INACTIVE => 'Inactif',
            self::ON_BREAK => 'En pause',
            self::GRADUATED => 'Diplômé',
            self::SUSPENDED => 'Suspendu',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ON_BREAK => 'On Break',
            self::GRADUATED => 'Graduated',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktiv',
            self::INACTIVE => 'Inaktiv',
            self::ON_BREAK => 'Auf Pause',
            self::GRADUATED => 'Abgeschlossen',
            self::SUSPENDED => 'Suspendiert',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::ON_BREAK => 'yellow',
            self::GRADUATED => 'blue',
            self::SUSPENDED => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE => 'check-circle',
            self::INACTIVE => 'x-circle',
            self::ON_BREAK => 'pause-circle',
            self::GRADUATED => 'academic-cap',
            self::SUSPENDED => 'exclamation-circle',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canServe(): bool
    {
        return $this === self::ACTIVE;
    }

    public static function availableForService(): array
    {
        return [self::ACTIVE];
    }
}
