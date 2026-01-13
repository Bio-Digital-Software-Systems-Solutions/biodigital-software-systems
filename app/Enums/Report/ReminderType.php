<?php

namespace App\Enums\Report;

enum ReminderType: string
{
    case GENERATION = 'generation';
    case SUBMISSION = 'submission';
    case REVIEW = 'review';
    case DEADLINE = 'deadline';

    public function label(): string
    {
        return match ($this) {
            self::GENERATION => 'Génération',
            self::SUBMISSION => 'Soumission',
            self::REVIEW => 'Révision',
            self::DEADLINE => 'Échéance',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::GENERATION => 'file-plus',
            self::SUBMISSION => 'send',
            self::REVIEW => 'eye',
            self::DEADLINE => 'clock',
        };
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
            'icon' => $case->icon(),
        ], self::cases());
    }
}
