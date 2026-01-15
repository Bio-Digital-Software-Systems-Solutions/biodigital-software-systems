<?php

namespace App\Enums\Employee;

enum EmployeeStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ON_LEAVE = 'on_leave';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Actif',
            self::INACTIVE => 'Inactif',
            self::ON_LEAVE => 'En congé',
            self::TERMINATED => 'Terminé',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::ON_LEAVE => 'On Leave',
            self::TERMINATED => 'Terminated',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::ACTIVE => 'Aktiv',
            self::INACTIVE => 'Inaktiv',
            self::ON_LEAVE => 'Im Urlaub',
            self::TERMINATED => 'Beendet',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::ON_LEAVE => 'yellow',
            self::TERMINATED => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ACTIVE => 'check-circle',
            self::INACTIVE => 'pause-circle',
            self::ON_LEAVE => 'clock',
            self::TERMINATED => 'x-circle',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canWork(): bool
    {
        return $this === self::ACTIVE;
    }
}
