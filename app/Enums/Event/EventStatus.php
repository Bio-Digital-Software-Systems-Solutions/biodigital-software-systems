<?php

namespace App\Enums\Event;

enum EventStatus: string
{
    case DRAFT = 'draft';
    case PLANNED = 'planned';
    case PUBLISHED = 'published';
    case ONGOING = 'ongoing';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case POSTPONED = 'postponed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PLANNED => 'Planifié',
            self::PUBLISHED => 'Publié',
            self::ONGOING => 'En cours',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
            self::POSTPONED => 'Reporté',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PLANNED => 'Planned',
            self::PUBLISHED => 'Published',
            self::ONGOING => 'Ongoing',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::POSTPONED => 'Postponed',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::DRAFT => 'Entwurf',
            self::PLANNED => 'Geplant',
            self::PUBLISHED => 'Veröffentlicht',
            self::ONGOING => 'Laufend',
            self::COMPLETED => 'Abgeschlossen',
            self::CANCELLED => 'Abgesagt',
            self::POSTPONED => 'Verschoben',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PLANNED => 'blue',
            self::PUBLISHED => 'indigo',
            self::ONGOING => 'green',
            self::COMPLETED => 'emerald',
            self::CANCELLED => 'red',
            self::POSTPONED => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'pencil',
            self::PLANNED => 'calendar',
            self::PUBLISHED => 'check-circle',
            self::ONGOING => 'play',
            self::COMPLETED => 'check',
            self::CANCELLED => 'x-circle',
            self::POSTPONED => 'clock',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PLANNED, self::PUBLISHED, self::ONGOING]);
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    public function canAcceptRegistrations(): bool
    {
        return $this === self::PUBLISHED;
    }

    public static function activeStatuses(): array
    {
        return [self::PLANNED, self::PUBLISHED, self::ONGOING];
    }

    public static function finishedStatuses(): array
    {
        return [self::COMPLETED, self::CANCELLED];
    }
}
