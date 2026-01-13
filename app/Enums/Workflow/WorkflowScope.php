<?php

namespace App\Enums\Workflow;

enum WorkflowScope: string
{
    case DEPARTMENT = 'department';
    case ENTERPRISE = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::DEPARTMENT => 'Département',
            self::ENTERPRISE => 'Entreprise',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DEPARTMENT => 'blue',
            self::ENTERPRISE => 'purple',
        };
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }
}
