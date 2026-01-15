<?php

namespace App\Enums\Event;

enum RegistrationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case WAITLISTED = 'waitlisted';
    case CANCELLED = 'cancelled';
    case CHECKED_IN = 'checked_in';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::CONFIRMED => 'Confirmé',
            self::WAITLISTED => 'Liste d\'attente',
            self::CANCELLED => 'Annulé',
            self::CHECKED_IN => 'Présent',
            self::NO_SHOW => 'Absent',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::WAITLISTED => 'Waitlisted',
            self::CANCELLED => 'Cancelled',
            self::CHECKED_IN => 'Checked In',
            self::NO_SHOW => 'No Show',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::PENDING => 'Ausstehend',
            self::CONFIRMED => 'Bestätigt',
            self::WAITLISTED => 'Warteliste',
            self::CANCELLED => 'Storniert',
            self::CHECKED_IN => 'Eingecheckt',
            self::NO_SHOW => 'Nicht erschienen',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::CONFIRMED => 'green',
            self::WAITLISTED => 'blue',
            self::CANCELLED => 'gray',
            self::CHECKED_IN => 'emerald',
            self::NO_SHOW => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::CONFIRMED => 'check-circle',
            self::WAITLISTED => 'queue-list',
            self::CANCELLED => 'x-circle',
            self::CHECKED_IN => 'check-badge',
            self::NO_SHOW => 'exclamation-triangle',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED, self::WAITLISTED]);
    }

    public function canCheckIn(): bool
    {
        return $this === self::CONFIRMED;
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED, self::WAITLISTED]);
    }

    public function canBePromoted(): bool
    {
        return $this === self::WAITLISTED;
    }

    public static function attendedStatuses(): array
    {
        return [self::CHECKED_IN];
    }

    public static function activeStatuses(): array
    {
        return [self::PENDING, self::CONFIRMED, self::WAITLISTED];
    }
}
