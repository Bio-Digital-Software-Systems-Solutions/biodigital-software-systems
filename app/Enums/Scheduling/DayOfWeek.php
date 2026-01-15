<?php

namespace App\Enums\Scheduling;

enum DayOfWeek: int
{
    case SUNDAY = 0;
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 3;
    case THURSDAY = 4;
    case FRIDAY = 5;
    case SATURDAY = 6;

    public function label(): string
    {
        return match ($this) {
            self::MONDAY => 'Lundi',
            self::TUESDAY => 'Mardi',
            self::WEDNESDAY => 'Mercredi',
            self::THURSDAY => 'Jeudi',
            self::FRIDAY => 'Vendredi',
            self::SATURDAY => 'Samedi',
            self::SUNDAY => 'Dimanche',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday',
            self::SUNDAY => 'Sunday',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::MONDAY => 'Montag',
            self::TUESDAY => 'Dienstag',
            self::WEDNESDAY => 'Mittwoch',
            self::THURSDAY => 'Donnerstag',
            self::FRIDAY => 'Freitag',
            self::SATURDAY => 'Samstag',
            self::SUNDAY => 'Sonntag',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::MONDAY => 'Lun',
            self::TUESDAY => 'Mar',
            self::WEDNESDAY => 'Mer',
            self::THURSDAY => 'Jeu',
            self::FRIDAY => 'Ven',
            self::SATURDAY => 'Sam',
            self::SUNDAY => 'Dim',
        };
    }

    public function shortLabelEn(): string
    {
        return match ($this) {
            self::MONDAY => 'Mon',
            self::TUESDAY => 'Tue',
            self::WEDNESDAY => 'Wed',
            self::THURSDAY => 'Thu',
            self::FRIDAY => 'Fri',
            self::SATURDAY => 'Sat',
            self::SUNDAY => 'Sun',
        };
    }

    public function shortLabelDe(): string
    {
        return match ($this) {
            self::MONDAY => 'Mo',
            self::TUESDAY => 'Di',
            self::WEDNESDAY => 'Mi',
            self::THURSDAY => 'Do',
            self::FRIDAY => 'Fr',
            self::SATURDAY => 'Sa',
            self::SUNDAY => 'So',
        };
    }

    public function isWeekend(): bool
    {
        return in_array($this, [self::SATURDAY, self::SUNDAY]);
    }

    public function isWeekday(): bool
    {
        return !$this->isWeekend();
    }

    /**
     * Get all weekdays
     */
    public static function weekdays(): array
    {
        return [
            self::MONDAY,
            self::TUESDAY,
            self::WEDNESDAY,
            self::THURSDAY,
            self::FRIDAY,
        ];
    }

    /**
     * Get weekend days
     */
    public static function weekend(): array
    {
        return [
            self::SATURDAY,
            self::SUNDAY,
        ];
    }

    /**
     * Get ISO day number (Monday = 1, Sunday = 7)
     */
    public function isoNumber(): int
    {
        return match ($this) {
            self::MONDAY => 1,
            self::TUESDAY => 2,
            self::WEDNESDAY => 3,
            self::THURSDAY => 4,
            self::FRIDAY => 5,
            self::SATURDAY => 6,
            self::SUNDAY => 7,
        };
    }

    /**
     * Create from Carbon dayOfWeek (0=Sunday, 6=Saturday)
     */
    public static function fromCarbon(int $carbonDay): self
    {
        return self::from($carbonDay);
    }

    /**
     * Get ordered list starting from Monday
     */
    public static function orderedFromMonday(): array
    {
        return [
            self::MONDAY,
            self::TUESDAY,
            self::WEDNESDAY,
            self::THURSDAY,
            self::FRIDAY,
            self::SATURDAY,
            self::SUNDAY,
        ];
    }
}
