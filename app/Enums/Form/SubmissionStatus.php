<?php

namespace App\Enums\Form;

enum SubmissionStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::SUBMITTED => 'Soumis',
            self::PROCESSING => 'En traitement',
            self::COMPLETED => 'Terminé',
            self::REJECTED => 'Rejeté',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'blue',
            self::PROCESSING => 'yellow',
            self::COMPLETED => 'green',
            self::REJECTED => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'pencil',
            self::SUBMITTED => 'paper-airplane',
            self::PROCESSING => 'arrow-path',
            self::COMPLETED => 'check-circle',
            self::REJECTED => 'x-circle',
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
