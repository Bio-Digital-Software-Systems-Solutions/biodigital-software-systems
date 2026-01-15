<?php

namespace App\Enums\Scheduling;

enum AbsenceStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::APPROVED => 'Approuvé',
            self::REJECTED => 'Refusé',
            self::CANCELLED => 'Annulé',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::PENDING => 'Ausstehend',
            self::APPROVED => 'Genehmigt',
            self::REJECTED => 'Abgelehnt',
            self::CANCELLED => 'Storniert',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::CANCELLED => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::APPROVED => 'check-circle',
            self::REJECTED => 'x-circle',
            self::CANCELLED => 'minus-circle',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED, self::CANCELLED]);
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::APPROVED, self::REJECTED, self::CANCELLED]),
            self::APPROVED => $newStatus === self::CANCELLED,
            self::REJECTED, self::CANCELLED => false,
        };
    }
}
