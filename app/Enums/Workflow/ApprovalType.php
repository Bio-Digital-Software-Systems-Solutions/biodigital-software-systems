<?php

namespace App\Enums\Workflow;

enum ApprovalType: string
{
    case ANY = 'any';
    case ALL = 'all';
    case MAJORITY = 'majority';
    case SEQUENTIAL = 'sequential';

    public function label(): string
    {
        return match ($this) {
            self::ANY => 'Un seul approbateur',
            self::ALL => 'Tous les approbateurs',
            self::MAJORITY => 'Majorité',
            self::SEQUENTIAL => 'Séquentiel',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ANY => 'La première approbation suffit',
            self::ALL => 'Tous les approbateurs doivent approuver',
            self::MAJORITY => 'Plus de 50% doivent approuver',
            self::SEQUENTIAL => 'Les approbateurs sont sollicités un par un dans l\'ordre',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ANY => 'green',
            self::ALL => 'blue',
            self::MAJORITY => 'purple',
            self::SEQUENTIAL => 'orange',
        };
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Workflow\ApprovalType $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'color' => $case->color(),
        ], self::cases());
    }
}
