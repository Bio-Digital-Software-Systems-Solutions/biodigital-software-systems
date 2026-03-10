<?php

namespace App\Enums\Scheduling;

enum ScheduleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case LOCKED = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PUBLISHED => 'Publié',
            self::LOCKED => 'Verrouillé',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::LOCKED => 'Locked',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::DRAFT => 'Entwurf',
            self::PUBLISHED => 'Veröffentlicht',
            self::LOCKED => 'Gesperrt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
            self::LOCKED => 'blue',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'pencil',
            self::PUBLISHED => 'check-circle',
            self::LOCKED => 'lock-closed',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::DRAFT;
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::DRAFT => $newStatus == self::PUBLISHED,
            self::PUBLISHED => in_array($newStatus, [self::LOCKED, self::DRAFT]),
            self::LOCKED => false,
        };
    }
}
