<?php

namespace App\Enums\Event;

enum ParticipantRole: string
{
    case ATTENDEE = 'attendee';
    case SPEAKER = 'speaker';
    case MODERATOR = 'moderator';
    case SPONSOR = 'sponsor';
    case EXHIBITOR = 'exhibitor';
    case STAFF = 'staff';
    case VOLUNTEER = 'volunteer';
    case ORGANIZER = 'organizer';

    public function label(): string
    {
        return match ($this) {
            self::ATTENDEE => 'Participant',
            self::SPEAKER => 'Intervenant',
            self::MODERATOR => 'Modérateur',
            self::SPONSOR => 'Sponsor',
            self::EXHIBITOR => 'Exposant',
            self::STAFF => 'Personnel',
            self::VOLUNTEER => 'Bénévole',
            self::ORGANIZER => 'Organisateur',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::ATTENDEE => 'Attendee',
            self::SPEAKER => 'Speaker',
            self::MODERATOR => 'Moderator',
            self::SPONSOR => 'Sponsor',
            self::EXHIBITOR => 'Exhibitor',
            self::STAFF => 'Staff',
            self::VOLUNTEER => 'Volunteer',
            self::ORGANIZER => 'Organizer',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::ATTENDEE => 'Teilnehmer',
            self::SPEAKER => 'Referent',
            self::MODERATOR => 'Moderator',
            self::SPONSOR => 'Sponsor',
            self::EXHIBITOR => 'Aussteller',
            self::STAFF => 'Personal',
            self::VOLUNTEER => 'Freiwilliger',
            self::ORGANIZER => 'Organisator',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ATTENDEE => 'gray',
            self::SPEAKER => 'blue',
            self::MODERATOR => 'purple',
            self::SPONSOR => 'amber',
            self::EXHIBITOR => 'cyan',
            self::STAFF => 'green',
            self::VOLUNTEER => 'pink',
            self::ORGANIZER => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ATTENDEE => 'user',
            self::SPEAKER => 'microphone',
            self::MODERATOR => 'shield-check',
            self::SPONSOR => 'banknotes',
            self::EXHIBITOR => 'building-storefront',
            self::STAFF => 'identification',
            self::VOLUNTEER => 'hand-raised',
            self::ORGANIZER => 'key',
        };
    }

    public function hasSpecialAccess(): bool
    {
        return in_array($this, [
            self::SPEAKER,
            self::MODERATOR,
            self::SPONSOR,
            self::STAFF,
            self::ORGANIZER,
        ]);
    }

    public function canManageEvent(): bool
    {
        return in_array($this, [self::ORGANIZER, self::STAFF]);
    }

    public function requiresBadge(): bool
    {
        return true; // All roles require badges
    }

    public static function staffRoles(): array
    {
        return [self::STAFF, self::VOLUNTEER, self::ORGANIZER];
    }

    public static function speakerRoles(): array
    {
        return [self::SPEAKER, self::MODERATOR];
    }
}
