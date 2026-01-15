<?php

namespace App\Enums\Event;

enum EventVisibility: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case INTERNAL = 'internal';
    case INVITE_ONLY = 'invite_only';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::PRIVATE => 'Privé',
            self::INTERNAL => 'Interne',
            self::INVITE_ONLY => 'Sur invitation',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::PUBLIC => 'Public',
            self::PRIVATE => 'Private',
            self::INTERNAL => 'Internal',
            self::INVITE_ONLY => 'Invite Only',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::PUBLIC => 'Öffentlich',
            self::PRIVATE => 'Privat',
            self::INTERNAL => 'Intern',
            self::INVITE_ONLY => 'Nur auf Einladung',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PUBLIC => 'globe-alt',
            self::PRIVATE => 'lock-closed',
            self::INTERNAL => 'building-office',
            self::INVITE_ONLY => 'envelope',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PUBLIC => 'green',
            self::PRIVATE => 'red',
            self::INTERNAL => 'blue',
            self::INVITE_ONLY => 'purple',
        };
    }

    public function isPubliclyVisible(): bool
    {
        return $this === self::PUBLIC;
    }

    public function requiresInvitation(): bool
    {
        return $this === self::INVITE_ONLY;
    }

    public function requiresMembership(): bool
    {
        return in_array($this, [self::INTERNAL, self::PRIVATE]);
    }
}
