<?php

namespace App\Enums\Scheduling;

enum RecurrenceType: string
{
    case NONE = 'none';
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Aucune',
            self::DAILY => 'Quotidienne',
            self::WEEKLY => 'Hebdomadaire',
            self::BIWEEKLY => 'Bihebdomadaire',
            self::MONTHLY => 'Mensuelle',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::NONE => 'None',
            self::DAILY => 'Daily',
            self::WEEKLY => 'Weekly',
            self::BIWEEKLY => 'Biweekly',
            self::MONTHLY => 'Monthly',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::NONE => 'Keine',
            self::DAILY => 'Täglich',
            self::WEEKLY => 'Wöchentlich',
            self::BIWEEKLY => 'Zweiwöchentlich',
            self::MONTHLY => 'Monatlich',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::NONE => 'minus',
            self::DAILY => 'sun',
            self::WEEKLY => 'calendar-days',
            self::BIWEEKLY => 'calendar',
            self::MONTHLY => 'calendar-date-range',
        };
    }

    /**
     * Get interval in days
     */
    public function intervalDays(): ?int
    {
        return match ($this) {
            self::NONE => null,
            self::DAILY => 1,
            self::WEEKLY => 7,
            self::BIWEEKLY => 14,
            self::MONTHLY => 30, // Approximate
        };
    }

    public function hasRecurrence(): bool
    {
        return $this !== self::NONE;
    }
}
