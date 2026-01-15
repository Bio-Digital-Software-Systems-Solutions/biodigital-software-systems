<?php

namespace App\Enums\Event;

enum SessionFormat: string
{
    case IN_PERSON = 'in_person';
    case VIRTUAL = 'virtual';
    case HYBRID = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::IN_PERSON => 'Présentiel',
            self::VIRTUAL => 'Virtuel',
            self::HYBRID => 'Hybride',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::IN_PERSON => 'In Person',
            self::VIRTUAL => 'Virtual',
            self::HYBRID => 'Hybrid',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::IN_PERSON => 'Vor Ort',
            self::VIRTUAL => 'Virtuell',
            self::HYBRID => 'Hybrid',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::IN_PERSON => 'green',
            self::VIRTUAL => 'purple',
            self::HYBRID => 'blue',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::IN_PERSON => 'building-office-2',
            self::VIRTUAL => 'video-camera',
            self::HYBRID => 'globe-alt',
        };
    }

    public function requiresVenue(): bool
    {
        return in_array($this, [self::IN_PERSON, self::HYBRID]);
    }

    public function requiresStreamingLink(): bool
    {
        return in_array($this, [self::VIRTUAL, self::HYBRID]);
    }
}
