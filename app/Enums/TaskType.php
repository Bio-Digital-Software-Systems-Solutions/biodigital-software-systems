<?php

namespace App\Enums;

enum TaskType: string
{
    case TASK = 'task';
    case BUG = 'bug';
    case FEATURE = 'feature';
    case STORY = 'story';
    case EPIC = 'epic';
    case SUBTASK = 'subtask';

    public function label(): string
    {
        return match ($this) {
            self::TASK => 'Tâche',
            self::BUG => 'Bug',
            self::FEATURE => 'Fonctionnalité',
            self::STORY => 'User Story',
            self::EPIC => 'Epic',
            self::SUBTASK => 'Sous-tâche',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TASK => 'check-circle',
            self::BUG => 'bug',
            self::FEATURE => 'sparkles',
            self::STORY => 'book-open',
            self::EPIC => 'layers',
            self::SUBTASK => 'list',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TASK => 'blue',
            self::BUG => 'red',
            self::FEATURE => 'purple',
            self::STORY => 'green',
            self::EPIC => 'indigo',
            self::SUBTASK => 'gray',
        };
    }
}
