<?php

namespace App\Enums\Employee;

enum EmploymentType: string
{
    case FULL_TIME = 'full_time';
    case PART_TIME = 'part_time';
    case CONTRACT = 'contract';
    case INTERN = 'intern';
    case VOLUNTEER = 'volunteer';

    public function label(): string
    {
        return match ($this) {
            self::FULL_TIME => 'Temps plein',
            self::PART_TIME => 'Temps partiel',
            self::CONTRACT => 'Contrat',
            self::INTERN => 'Stagiaire',
            self::VOLUNTEER => 'Bénévole',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::FULL_TIME => 'Full Time',
            self::PART_TIME => 'Part Time',
            self::CONTRACT => 'Contract',
            self::INTERN => 'Intern',
            self::VOLUNTEER => 'Volunteer',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::FULL_TIME => 'Vollzeit',
            self::PART_TIME => 'Teilzeit',
            self::CONTRACT => 'Vertrag',
            self::INTERN => 'Praktikant',
            self::VOLUNTEER => 'Freiwilliger',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FULL_TIME => 'blue',
            self::PART_TIME => 'purple',
            self::CONTRACT => 'orange',
            self::INTERN => 'cyan',
            self::VOLUNTEER => 'green',
        };
    }

    public function defaultWeeklyHours(): float
    {
        return match ($this) {
            self::FULL_TIME => 40.0,
            self::PART_TIME => 20.0,
            self::CONTRACT => 40.0,
            self::INTERN => 35.0,
            self::VOLUNTEER => 10.0,
        };
    }

    public function isPaid(): bool
    {
        return $this !== self::VOLUNTEER;
    }
}
