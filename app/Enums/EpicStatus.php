<?php

namespace App\Enums;

enum EpicStatus: string
{
    case TODO = 'todo';
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case UNDER_REVIEW = 'under_review';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::TODO => 'À faire',
            self::PENDING => 'En attente',
            self::IN_PROGRESS => 'En cours',
            self::UNDER_REVIEW => 'En révision',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TODO, self::PENDING => 'gray',
            self::IN_PROGRESS => 'blue',
            self::UNDER_REVIEW => 'yellow',
            self::COMPLETED => 'green',
            self::CANCELLED => 'gray',
        };
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::IN_PROGRESS, self::UNDER_REVIEW]);
    }
}
