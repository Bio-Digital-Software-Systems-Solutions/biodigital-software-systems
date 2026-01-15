<?php

namespace App\Enums\Event;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case PARTIAL = 'partial';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::COMPLETED => 'Complété',
            self::FAILED => 'Échoué',
            self::REFUNDED => 'Remboursé',
            self::PARTIAL => 'Partiel',
            self::CANCELLED => 'Annulé',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
            self::PARTIAL => 'Partial',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::PENDING => 'Ausstehend',
            self::COMPLETED => 'Abgeschlossen',
            self::FAILED => 'Fehlgeschlagen',
            self::REFUNDED => 'Erstattet',
            self::PARTIAL => 'Teilweise',
            self::CANCELLED => 'Storniert',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::REFUNDED => 'blue',
            self::PARTIAL => 'orange',
            self::CANCELLED => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::COMPLETED => 'check-circle',
            self::FAILED => 'x-circle',
            self::REFUNDED => 'arrow-uturn-left',
            self::PARTIAL => 'minus-circle',
            self::CANCELLED => 'ban',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::COMPLETED;
    }

    public function canRefund(): bool
    {
        return $this === self::COMPLETED;
    }

    public function requiresAction(): bool
    {
        return in_array($this, [self::PENDING, self::FAILED, self::PARTIAL]);
    }
}
