<?php

namespace App\Enums\Scheduling;

enum SwapRequestStatus: string
{
    case PENDING_COLLEAGUE = 'pending_colleague';
    case PENDING_MANAGER = 'pending_manager';
    case APPROVED = 'approved';
    case REJECTED_COLLEAGUE = 'rejected_colleague';
    case REJECTED_MANAGER = 'rejected_manager';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PENDING_COLLEAGUE => 'En attente du collègue',
            self::PENDING_MANAGER => 'En attente du responsable',
            self::APPROVED => 'Approuvé',
            self::REJECTED_COLLEAGUE => 'Refusé par le collègue',
            self::REJECTED_MANAGER => 'Refusé par le responsable',
            self::CANCELLED => 'Annulé',
            self::EXPIRED => 'Expiré',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::PENDING_COLLEAGUE => 'Pending Colleague',
            self::PENDING_MANAGER => 'Pending Manager',
            self::APPROVED => 'Approved',
            self::REJECTED_COLLEAGUE => 'Rejected by Colleague',
            self::REJECTED_MANAGER => 'Rejected by Manager',
            self::CANCELLED => 'Cancelled',
            self::EXPIRED => 'Expired',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::PENDING_COLLEAGUE => 'Warten auf Kollegen',
            self::PENDING_MANAGER => 'Warten auf Manager',
            self::APPROVED => 'Genehmigt',
            self::REJECTED_COLLEAGUE => 'Vom Kollegen abgelehnt',
            self::REJECTED_MANAGER => 'Vom Manager abgelehnt',
            self::CANCELLED => 'Storniert',
            self::EXPIRED => 'Abgelaufen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING_COLLEAGUE => 'yellow',
            self::PENDING_MANAGER => 'blue',
            self::APPROVED => 'green',
            self::REJECTED_COLLEAGUE, self::REJECTED_MANAGER => 'red',
            self::CANCELLED => 'gray',
            self::EXPIRED => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING_COLLEAGUE => 'user-circle',
            self::PENDING_MANAGER => 'user-group',
            self::APPROVED => 'check-circle',
            self::REJECTED_COLLEAGUE, self::REJECTED_MANAGER => 'x-circle',
            self::CANCELLED => 'minus-circle',
            self::EXPIRED => 'clock',
        };
    }

    public function isPending(): bool
    {
        return in_array($this, [self::PENDING_COLLEAGUE, self::PENDING_MANAGER]);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::APPROVED, self::REJECTED_COLLEAGUE, self::REJECTED_MANAGER, self::CANCELLED, self::EXPIRED]);
    }

    public function isRejected(): bool
    {
        return in_array($this, [self::REJECTED_COLLEAGUE, self::REJECTED_MANAGER]);
    }
}
