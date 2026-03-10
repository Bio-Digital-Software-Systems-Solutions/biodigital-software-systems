<?php

namespace App\Enums\Report;

enum CommentType: string
{
    case COMMENT = 'comment';
    case SUGGESTION = 'suggestion';
    case ISSUE = 'issue';
    case RESOLUTION = 'resolution';

    public function label(): string
    {
        return match ($this) {
            self::COMMENT => 'Commentaire',
            self::SUGGESTION => 'Suggestion',
            self::ISSUE => 'Problème',
            self::RESOLUTION => 'Résolution',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::COMMENT => 'gray',
            self::SUGGESTION => 'blue',
            self::ISSUE => 'red',
            self::RESOLUTION => 'green',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::COMMENT => 'message-circle',
            self::SUGGESTION => 'lightbulb',
            self::ISSUE => 'alert-circle',
            self::RESOLUTION => 'check-circle',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Report\CommentType $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
            'icon' => $case->icon(),
        ], self::cases());
    }
}
