<?php

namespace App\Enums\Workflow;

enum StepInstanceStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case SKIPPED = 'skipped';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
    case WAITING = 'waiting';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::ACTIVE => 'Actif',
            self::COMPLETED => 'Terminé',
            self::SKIPPED => 'Ignoré',
            self::FAILED => 'Échoué',
            self::CANCELLED => 'Annulé',
            self::WAITING => 'En attente (timer)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::ACTIVE => 'blue',
            self::COMPLETED => 'green',
            self::SKIPPED => 'yellow',
            self::FAILED => 'red',
            self::CANCELLED => 'orange',
            self::WAITING => 'purple',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::ACTIVE => 'play',
            self::COMPLETED => 'check',
            self::SKIPPED => 'forward',
            self::FAILED => 'x-circle',
            self::CANCELLED => 'ban',
            self::WAITING => 'pause',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::SKIPPED, self::FAILED, self::CANCELLED]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::ACTIVE, self::WAITING]);
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
