<?php

namespace App\Enums\Scheduling;

enum AbsenceType: string
{
    case VACATION = 'vacation';
    case SICK_LEAVE = 'sick_leave';
    case FAMILY_LEAVE = 'family_leave';
    case MATERNITY_LEAVE = 'maternity_leave';
    case PATERNITY_LEAVE = 'paternity_leave';
    case TRAINING = 'training';
    case UNPAID_LEAVE = 'unpaid_leave';
    case COMPENSATORY = 'compensatory';
    case PUBLIC_HOLIDAY = 'public_holiday';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::VACATION => 'Congés payés',
            self::SICK_LEAVE => 'Maladie',
            self::FAMILY_LEAVE => 'Congé familial',
            self::MATERNITY_LEAVE => 'Congé maternité',
            self::PATERNITY_LEAVE => 'Congé paternité',
            self::TRAINING => 'Formation',
            self::UNPAID_LEAVE => 'Congé sans solde',
            self::COMPENSATORY => 'Récupération',
            self::PUBLIC_HOLIDAY => 'Jour férié',
            self::OTHER => 'Autre',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::VACATION => 'Vacation',
            self::SICK_LEAVE => 'Sick Leave',
            self::FAMILY_LEAVE => 'Family Leave',
            self::MATERNITY_LEAVE => 'Maternity Leave',
            self::PATERNITY_LEAVE => 'Paternity Leave',
            self::TRAINING => 'Training',
            self::UNPAID_LEAVE => 'Unpaid Leave',
            self::COMPENSATORY => 'Compensatory Time',
            self::PUBLIC_HOLIDAY => 'Public Holiday',
            self::OTHER => 'Other',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::VACATION => 'Urlaub',
            self::SICK_LEAVE => 'Krankmeldung',
            self::FAMILY_LEAVE => 'Familienurlaub',
            self::MATERNITY_LEAVE => 'Mutterschaftsurlaub',
            self::PATERNITY_LEAVE => 'Vaterschaftsurlaub',
            self::TRAINING => 'Fortbildung',
            self::UNPAID_LEAVE => 'Unbezahlter Urlaub',
            self::COMPENSATORY => 'Freizeitausgleich',
            self::PUBLIC_HOLIDAY => 'Feiertag',
            self::OTHER => 'Sonstige',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::VACATION => 'blue',
            self::SICK_LEAVE => 'red',
            self::FAMILY_LEAVE => 'purple',
            self::MATERNITY_LEAVE => 'pink',
            self::PATERNITY_LEAVE => 'cyan',
            self::TRAINING => 'green',
            self::UNPAID_LEAVE => 'gray',
            self::COMPENSATORY => 'orange',
            self::PUBLIC_HOLIDAY => 'indigo',
            self::OTHER => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::VACATION => 'sun',
            self::SICK_LEAVE => 'heart',
            self::FAMILY_LEAVE => 'home',
            self::MATERNITY_LEAVE => 'user-group',
            self::PATERNITY_LEAVE => 'user-group',
            self::TRAINING => 'academic-cap',
            self::UNPAID_LEAVE => 'banknotes',
            self::COMPENSATORY => 'clock',
            self::PUBLIC_HOLIDAY => 'calendar',
            self::OTHER => 'document-text',
        };
    }

    public function isPaid(): bool
    {
        return match ($this) {
            self::VACATION, self::SICK_LEAVE, self::FAMILY_LEAVE,
            self::MATERNITY_LEAVE, self::PATERNITY_LEAVE, self::TRAINING,
            self::COMPENSATORY, self::PUBLIC_HOLIDAY => true,
            self::UNPAID_LEAVE, self::OTHER => false,
        };
    }

    public function requiresApproval(): bool
    {
        return match ($this) {
            self::SICK_LEAVE, self::PUBLIC_HOLIDAY => false,
            default => true,
        };
    }

    public function requiresDocument(): bool
    {
        return match ($this) {
            self::SICK_LEAVE, self::MATERNITY_LEAVE, self::PATERNITY_LEAVE => true,
            default => false,
        };
    }

    /**
     * Get minimum notice days required
     */
    public function minimumNoticeDays(): int
    {
        return match ($this) {
            self::SICK_LEAVE => 0,
            self::FAMILY_LEAVE => 0,
            self::VACATION => 14,
            self::UNPAID_LEAVE => 30,
            self::TRAINING => 7,
            self::COMPENSATORY => 3,
            default => 0,
        };
    }

    /**
     * Check if this absence type deducts from leave balance
     */
    public function deductsFromBalance(): bool
    {
        return match ($this) {
            self::VACATION, self::FAMILY_LEAVE, self::COMPENSATORY => true,
            default => false,
        };
    }
}
