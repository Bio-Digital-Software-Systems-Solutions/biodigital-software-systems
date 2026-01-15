<?php

namespace App\Enums\Event;

enum TicketType: string
{
    case FREE = 'free';
    case PAID = 'paid';
    case DONATION = 'donation';
    case EARLY_BIRD = 'early_bird';
    case VIP = 'vip';
    case GROUP = 'group';
    case STUDENT = 'student';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::FREE => 'Gratuit',
            self::PAID => 'Payant',
            self::DONATION => 'Don',
            self::EARLY_BIRD => 'Early Bird',
            self::VIP => 'VIP',
            self::GROUP => 'Groupe',
            self::STUDENT => 'Étudiant',
            self::MEMBER => 'Membre',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::PAID => 'Paid',
            self::DONATION => 'Donation',
            self::EARLY_BIRD => 'Early Bird',
            self::VIP => 'VIP',
            self::GROUP => 'Group',
            self::STUDENT => 'Student',
            self::MEMBER => 'Member',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::FREE => 'Kostenlos',
            self::PAID => 'Kostenpflichtig',
            self::DONATION => 'Spende',
            self::EARLY_BIRD => 'Frühbucher',
            self::VIP => 'VIP',
            self::GROUP => 'Gruppe',
            self::STUDENT => 'Student',
            self::MEMBER => 'Mitglied',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FREE => 'green',
            self::PAID => 'blue',
            self::DONATION => 'pink',
            self::EARLY_BIRD => 'amber',
            self::VIP => 'purple',
            self::GROUP => 'cyan',
            self::STUDENT => 'indigo',
            self::MEMBER => 'emerald',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::FREE => 'gift',
            self::PAID => 'credit-card',
            self::DONATION => 'heart',
            self::EARLY_BIRD => 'clock',
            self::VIP => 'star',
            self::GROUP => 'user-group',
            self::STUDENT => 'academic-cap',
            self::MEMBER => 'identification',
        };
    }

    public function requiresPayment(): bool
    {
        return in_array($this, [self::PAID, self::DONATION, self::EARLY_BIRD, self::VIP, self::GROUP]);
    }

    public function hasDiscount(): bool
    {
        return in_array($this, [self::EARLY_BIRD, self::GROUP, self::STUDENT, self::MEMBER]);
    }

    public function isTimeLimited(): bool
    {
        return $this === self::EARLY_BIRD;
    }
}
