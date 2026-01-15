<?php

namespace App\Enums\Scheduling;

enum ShiftType: string
{
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';
    case EVENING = 'evening';
    case NIGHT = 'night';
    case FULL_DAY = 'full_day';
    case SPLIT = 'split';
    case ON_CALL = 'on_call';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::MORNING => 'Matin',
            self::AFTERNOON => 'Après-midi',
            self::EVENING => 'Soirée',
            self::NIGHT => 'Nuit',
            self::FULL_DAY => 'Journée complète',
            self::SPLIT => 'Coupé',
            self::ON_CALL => 'Astreinte',
            self::CUSTOM => 'Personnalisé',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::MORNING => 'Morning',
            self::AFTERNOON => 'Afternoon',
            self::EVENING => 'Evening',
            self::NIGHT => 'Night',
            self::FULL_DAY => 'Full Day',
            self::SPLIT => 'Split Shift',
            self::ON_CALL => 'On Call',
            self::CUSTOM => 'Custom',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::MORNING => 'Morgen',
            self::AFTERNOON => 'Nachmittag',
            self::EVENING => 'Abend',
            self::NIGHT => 'Nacht',
            self::FULL_DAY => 'Ganztägig',
            self::SPLIT => 'Geteilt',
            self::ON_CALL => 'Bereitschaft',
            self::CUSTOM => 'Benutzerdefiniert',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MORNING => 'yellow',
            self::AFTERNOON => 'orange',
            self::EVENING => 'purple',
            self::NIGHT => 'indigo',
            self::FULL_DAY => 'blue',
            self::SPLIT => 'pink',
            self::ON_CALL => 'gray',
            self::CUSTOM => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MORNING => 'sun',
            self::AFTERNOON => 'cloud-sun',
            self::EVENING => 'moon',
            self::NIGHT => 'star',
            self::FULL_DAY => 'clock',
            self::SPLIT => 'scissors',
            self::ON_CALL => 'phone',
            self::CUSTOM => 'adjustments-horizontal',
        };
    }

    /**
     * Get default hours for shift type (start_time, end_time)
     */
    public function defaultHours(): array
    {
        return match ($this) {
            self::MORNING => ['06:00', '14:00'],
            self::AFTERNOON => ['14:00', '22:00'],
            self::EVENING => ['18:00', '23:00'],
            self::NIGHT => ['22:00', '06:00'],
            self::FULL_DAY => ['08:00', '17:00'],
            self::SPLIT => ['08:00', '12:00'], // First part only
            self::ON_CALL => ['00:00', '23:59'],
            self::CUSTOM => ['09:00', '17:00'],
        };
    }

    /**
     * Get default break duration in minutes
     */
    public function defaultBreakDuration(): int
    {
        return match ($this) {
            self::MORNING => 30,
            self::AFTERNOON => 30,
            self::EVENING => 15,
            self::NIGHT => 30,
            self::FULL_DAY => 60,
            self::SPLIT => 120, // Long break between parts
            self::ON_CALL => 0,
            self::CUSTOM => 30,
        };
    }

    /**
     * Check if this shift type spans midnight
     */
    public function spansOvernight(): bool
    {
        return $this === self::NIGHT;
    }

    /**
     * Get default duration in hours for this shift type
     */
    public function defaultDuration(): float
    {
        $hours = $this->defaultHours();
        $start = \Carbon\Carbon::parse($hours[0]);
        $end = \Carbon\Carbon::parse($hours[1]);

        // Handle overnight shifts
        if ($end <= $start) {
            $end->addDay();
        }

        $totalMinutes = $start->diffInMinutes($end);
        $breakMinutes = $this->defaultBreakDuration();

        return round(($totalMinutes - $breakMinutes) / 60, 2);
    }
}
