<?php

namespace App\Enums\Event;

enum EventType: string
{
    case CONFERENCE = 'conference';
    case WORKSHOP = 'workshop';
    case SEMINAR = 'seminar';
    case WEBINAR = 'webinar';
    case HYBRID = 'hybrid';
    case NETWORKING = 'networking';
    case TRAINING = 'training';
    case CORPORATE = 'corporate';
    case MEETING = 'meeting';
    case CEREMONY = 'ceremony';
    case RETREAT = 'retreat';
    case SERVICE = 'service';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CONFERENCE => 'Conférence',
            self::WORKSHOP => 'Atelier',
            self::SEMINAR => 'Séminaire',
            self::WEBINAR => 'Webinaire',
            self::HYBRID => 'Hybride',
            self::NETWORKING => 'Networking',
            self::TRAINING => 'Formation',
            self::CORPORATE => 'Entreprise',
            self::MEETING => 'Réunion',
            self::CEREMONY => 'Cérémonie',
            self::RETREAT => 'Retraite',
            self::SERVICE => 'Culte',
            self::OTHER => 'Autre',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::CONFERENCE => 'Conference',
            self::WORKSHOP => 'Workshop',
            self::SEMINAR => 'Seminar',
            self::WEBINAR => 'Webinar',
            self::HYBRID => 'Hybrid',
            self::NETWORKING => 'Networking',
            self::TRAINING => 'Training',
            self::CORPORATE => 'Corporate',
            self::MEETING => 'Meeting',
            self::CEREMONY => 'Ceremony',
            self::RETREAT => 'Retreat',
            self::SERVICE => 'Service',
            self::OTHER => 'Other',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::CONFERENCE => 'Konferenz',
            self::WORKSHOP => 'Workshop',
            self::SEMINAR => 'Seminar',
            self::WEBINAR => 'Webinar',
            self::HYBRID => 'Hybrid',
            self::NETWORKING => 'Networking',
            self::TRAINING => 'Schulung',
            self::CORPORATE => 'Unternehmen',
            self::MEETING => 'Sitzung',
            self::CEREMONY => 'Zeremonie',
            self::RETREAT => 'Klausur',
            self::SERVICE => 'Gottesdienst',
            self::OTHER => 'Andere',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CONFERENCE => 'microphone',
            self::WORKSHOP => 'wrench-screwdriver',
            self::SEMINAR => 'academic-cap',
            self::WEBINAR => 'video-camera',
            self::HYBRID => 'globe-alt',
            self::NETWORKING => 'user-group',
            self::TRAINING => 'book-open',
            self::CORPORATE => 'building-office',
            self::MEETING => 'chat-bubble-left-right',
            self::CEREMONY => 'sparkles',
            self::RETREAT => 'home',
            self::SERVICE => 'heart',
            self::OTHER => 'calendar',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CONFERENCE => 'blue',
            self::WORKSHOP => 'amber',
            self::SEMINAR => 'indigo',
            self::WEBINAR => 'purple',
            self::HYBRID => 'cyan',
            self::NETWORKING => 'green',
            self::TRAINING => 'orange',
            self::CORPORATE => 'slate',
            self::MEETING => 'gray',
            self::CEREMONY => 'pink',
            self::RETREAT => 'emerald',
            self::SERVICE => 'red',
            self::OTHER => 'neutral',
        };
    }

    public function isVirtual(): bool
    {
        return $this == self::WEBINAR;
    }

    public function isHybrid(): bool
    {
        return $this === self::HYBRID;
    }

    public function requiresVenue(): bool
    {
        return !$this->isVirtual();
    }
}
