<?php

namespace App\Enums\Workflow;

enum WorkflowInstanceStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::ACTIVE => 'En cours',
            self::PAUSED => 'En pause',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
            self::FAILED => 'Échoué',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'gray',
            self::ACTIVE => 'blue',
            self::PAUSED => 'yellow',
            self::COMPLETED => 'green',
            self::CANCELLED => 'orange',
            self::FAILED => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::ACTIVE => 'play',
            self::PAUSED => 'pause',
            self::COMPLETED => 'check-circle',
            self::CANCELLED => 'x-circle',
            self::FAILED => 'exclamation-circle',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::FAILED]);
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::ACTIVE, self::CANCELLED]),
            self::ACTIVE => in_array($newStatus, [self::PAUSED, self::COMPLETED, self::CANCELLED, self::FAILED]),
            self::PAUSED => in_array($newStatus, [self::ACTIVE, self::CANCELLED]),
            self::COMPLETED, self::CANCELLED, self::FAILED => false,
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
