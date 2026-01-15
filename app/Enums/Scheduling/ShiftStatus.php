<?php

namespace App\Enums\Scheduling;

enum ShiftStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Brouillon',
            self::PUBLISHED => 'Publié',
            self::CONFIRMED => 'Confirmé',
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminé',
            self::CANCELLED => 'Annulé',
            self::NO_SHOW => 'Absent',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::CONFIRMED => 'Confirmed',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::NO_SHOW => 'No Show',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::DRAFT => 'Entwurf',
            self::PUBLISHED => 'Veröffentlicht',
            self::CONFIRMED => 'Bestätigt',
            self::IN_PROGRESS => 'In Bearbeitung',
            self::COMPLETED => 'Abgeschlossen',
            self::CANCELLED => 'Abgesagt',
            self::NO_SHOW => 'Nicht erschienen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'blue',
            self::CONFIRMED => 'indigo',
            self::IN_PROGRESS => 'yellow',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
            self::NO_SHOW => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::DRAFT => 'pencil',
            self::PUBLISHED => 'paper-airplane',
            self::CONFIRMED => 'check-circle',
            self::IN_PROGRESS => 'play',
            self::COMPLETED => 'check',
            self::CANCELLED => 'x-circle',
            self::NO_SHOW => 'exclamation-triangle',
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions());
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PUBLISHED, self::CANCELLED],
            self::PUBLISHED => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::IN_PROGRESS, self::CANCELLED, self::NO_SHOW],
            self::IN_PROGRESS => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED => [],
            self::CANCELLED => [self::DRAFT],
            self::NO_SHOW => [],
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::PUBLISHED]);
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PUBLISHED, self::CONFIRMED, self::IN_PROGRESS]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED, self::NO_SHOW]);
    }
}
