<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case IN_REVIEW = 'in_review';
    case BLOCKED = 'blocked';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::TODO => 'À faire',
            self::IN_PROGRESS => 'En cours',
            self::IN_REVIEW => 'En révision',
            self::BLOCKED => 'Bloqué',
            self::DONE => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TODO => 'gray',
            self::IN_PROGRESS => 'blue',
            self::IN_REVIEW => 'yellow',
            self::BLOCKED => 'red',
            self::DONE => 'green',
            self::CANCELLED => 'gray',
        };
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::DONE, self::CANCELLED]);
    }
}
