<?php

namespace App\Enums\Workflow;

enum WorkflowStatus: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case DEPRECATED = 'deprecated';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::ACTIVE => 'Actif',
            self::DEPRECATED => 'Obsolète',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::ACTIVE => 'green',
            self::DEPRECATED => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'pencil',
            self::ACTIVE => 'check-circle',
            self::DEPRECATED => 'archive',
        };
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Workflow\WorkflowStatus $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }
}
