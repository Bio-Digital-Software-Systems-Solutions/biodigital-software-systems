<?php

namespace App\Enums\Report;

enum ObjectiveStatus: string
{
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case DELAYED = 'delayed';
    case CANCELLED = 'cancelled';
    case ON_HOLD = 'on_hold';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Non démarré',
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminé',
            self::DELAYED => 'En retard',
            self::CANCELLED => 'Annulé',
            self::ON_HOLD => 'En pause',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'gray',
            self::IN_PROGRESS => 'blue',
            self::COMPLETED => 'green',
            self::DELAYED => 'red',
            self::CANCELLED => 'slate',
            self::ON_HOLD => 'yellow',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'circle',
            self::IN_PROGRESS => 'loader',
            self::COMPLETED => 'check-circle',
            self::DELAYED => 'alert-triangle',
            self::CANCELLED => 'x-circle',
            self::ON_HOLD => 'pause-circle',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::IN_PROGRESS, self::DELAYED]);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
            'icon' => $case->icon(),
        ], self::cases());
    }
}
