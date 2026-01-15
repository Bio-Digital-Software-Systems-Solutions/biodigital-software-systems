<?php

namespace App\Enums\Scheduling;

enum AvailabilityStatus: string
{
    case AVAILABLE = 'available';
    case PARTIALLY_AVAILABLE = 'partially_available';
    case UNAVAILABLE = 'unavailable';
    case PREFERRED = 'preferred';
    case IF_NEEDED = 'if_needed';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Disponible',
            self::PARTIALLY_AVAILABLE => 'Partiellement disponible',
            self::UNAVAILABLE => 'Indisponible',
            self::PREFERRED => 'Préféré',
            self::IF_NEEDED => 'Si nécessaire',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::PARTIALLY_AVAILABLE => 'Partially Available',
            self::UNAVAILABLE => 'Unavailable',
            self::PREFERRED => 'Preferred',
            self::IF_NEEDED => 'If Needed',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Verfügbar',
            self::PARTIALLY_AVAILABLE => 'Teilweise verfügbar',
            self::UNAVAILABLE => 'Nicht verfügbar',
            self::PREFERRED => 'Bevorzugt',
            self::IF_NEEDED => 'Falls nötig',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'green',
            self::PARTIALLY_AVAILABLE => 'yellow',
            self::UNAVAILABLE => 'red',
            self::PREFERRED => 'blue',
            self::IF_NEEDED => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::AVAILABLE => 'check-circle',
            self::PARTIALLY_AVAILABLE => 'clock',
            self::UNAVAILABLE => 'x-circle',
            self::PREFERRED => 'star',
            self::IF_NEEDED => 'exclamation-circle',
        };
    }

    /**
     * Priority for sorting (higher = better availability)
     */
    public function priority(): int
    {
        return match ($this) {
            self::PREFERRED => 5,
            self::AVAILABLE => 4,
            self::PARTIALLY_AVAILABLE => 3,
            self::IF_NEEDED => 2,
            self::UNAVAILABLE => 1,
        };
    }

    /**
     * Check if this status allows assignment
     */
    public function allowsAssignment(): bool
    {
        return $this !== self::UNAVAILABLE;
    }

    /**
     * Check if this is a positive availability
     */
    public function isPositive(): bool
    {
        return in_array($this, [self::AVAILABLE, self::PREFERRED, self::PARTIALLY_AVAILABLE]);
    }

    /**
     * Check if employee is available (alias for isPositive)
     */
    public function isAvailable(): bool
    {
        return $this !== self::UNAVAILABLE;
    }
}
