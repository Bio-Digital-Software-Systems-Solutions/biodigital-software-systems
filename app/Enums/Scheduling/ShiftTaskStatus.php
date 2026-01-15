<?php

namespace App\Enums\Scheduling;

enum ShiftTaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case BLOCKED = 'blocked';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::TODO => 'À faire',
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminée',
            self::BLOCKED => 'Bloquée',
            self::CANCELLED => 'Annulée',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::TODO => 'To Do',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::BLOCKED => 'Blocked',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::TODO => 'Zu erledigen',
            self::IN_PROGRESS => 'In Bearbeitung',
            self::COMPLETED => 'Abgeschlossen',
            self::BLOCKED => 'Blockiert',
            self::CANCELLED => 'Storniert',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TODO => 'gray',
            self::IN_PROGRESS => 'blue',
            self::COMPLETED => 'green',
            self::BLOCKED => 'red',
            self::CANCELLED => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TODO => 'circle',
            self::IN_PROGRESS => 'play',
            self::COMPLETED => 'check-circle',
            self::BLOCKED => 'hand-raised',
            self::CANCELLED => 'x-circle',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::TODO, self::IN_PROGRESS, self::BLOCKED]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }
}
