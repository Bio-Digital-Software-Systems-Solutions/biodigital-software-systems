<?php

namespace App\Enums\Form;

enum FormStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PUBLISHED => 'Publié',
            self::ARCHIVED => 'Archivé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
            self::ARCHIVED => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'pencil',
            self::PUBLISHED => 'check-circle',
            self::ARCHIVED => 'archive-box',
        };
    }

    public function canEdit(): bool
    {
        return $this === self::DRAFT;
    }

    public function canSubmit(): bool
    {
        return $this === self::PUBLISHED;
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Form\FormStatus $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }
}
