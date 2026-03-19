<?php

namespace App\Enums;

enum RoutineSopStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Validated = 'validated';
    case Obsolete = 'obsolete';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Active => 'Actif',
            self::Validated => 'Validé',
            self::Obsolete => 'Obsolète',
            self::Inactive => 'Inactif',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::Validated => 'Validated',
            self::Obsolete => 'Obsolete',
            self::Inactive => 'Inactive',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::Active => 'Aktiv',
            self::Validated => 'Validiert',
            self::Obsolete => 'Veraltet',
            self::Inactive => 'Inaktiv',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'blue',
            self::Validated => 'green',
            self::Obsolete => 'red',
            self::Inactive => 'yellow',
        };
    }
}
