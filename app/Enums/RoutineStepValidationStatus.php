<?php

namespace App\Enums;

enum RoutineStepValidationStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Validated => 'Validée',
            self::Rejected => 'Rejetée',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Validated => 'Validated',
            self::Rejected => 'Rejected',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::Pending => 'Ausstehend',
            self::Validated => 'Validiert',
            self::Rejected => 'Abgelehnt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'yellow',
            self::Validated => 'green',
            self::Rejected => 'red',
        };
    }
}
