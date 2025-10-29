<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'todo';
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case IN_REVIEW = 'in_review';
    case UNDER_REVIEW = 'under_review';
    case BLOCKED = 'blocked';
    case ON_HOLD = 'on_hold';
    case DONE = 'done';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::TODO => 'À faire',
            self::PENDING => 'En attente',
            self::IN_PROGRESS => 'En cours',
            self::IN_REVIEW, self::UNDER_REVIEW => 'En révision',
            self::BLOCKED => 'Bloqué',
            self::ON_HOLD => 'En pause',
            self::DONE, self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TODO, self::PENDING => 'gray',
            self::IN_PROGRESS => 'blue',
            self::IN_REVIEW, self::UNDER_REVIEW => 'yellow',
            self::BLOCKED => 'red',
            self::ON_HOLD => 'orange',
            self::DONE, self::COMPLETED => 'green',
            self::CANCELLED => 'gray',
        };
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::DONE, self::COMPLETED, self::CANCELLED]);
    }
}
