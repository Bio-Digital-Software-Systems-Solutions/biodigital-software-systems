<?php

namespace App\Enums;

enum RoutineAssigneeRole: string
{
    case Assignee = 'assignee';
    case Validator = 'validator';
    case Observer = 'observer';

    public function label(): string
    {
        return match ($this) {
            self::Assignee => 'Assigné',
            self::Validator => 'Validateur',
            self::Observer => 'Observateur',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Assignee => 'Assignee',
            self::Validator => 'Validator',
            self::Observer => 'Observer',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::Assignee => 'Zugewiesen',
            self::Validator => 'Prüfer',
            self::Observer => 'Beobachter',
        };
    }
}
