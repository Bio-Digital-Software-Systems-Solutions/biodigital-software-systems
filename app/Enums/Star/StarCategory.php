<?php

namespace App\Enums\Star;

enum StarCategory: string
{
    case SERVICE = 'service';
    case LEADERSHIP = 'leadership';
    case COMMUNITY = 'community';
    case WORSHIP = 'worship';
    case MEDIA = 'media';
    case HOSPITALITY = 'hospitality';
    case CHILDREN = 'children';
    case YOUTH = 'youth';
    case OUTREACH = 'outreach';
    case ADMINISTRATION = 'administration';

    public function label(): string
    {
        return match ($this) {
            self::SERVICE => 'Service',
            self::LEADERSHIP => 'Leadership',
            self::COMMUNITY => 'Communauté',
            self::WORSHIP => 'Louange',
            self::MEDIA => 'Médias',
            self::HOSPITALITY => 'Accueil',
            self::CHILDREN => 'Enfants',
            self::YOUTH => 'Jeunesse',
            self::OUTREACH => 'Évangélisation',
            self::ADMINISTRATION => 'Administration',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::SERVICE => 'Service',
            self::LEADERSHIP => 'Leadership',
            self::COMMUNITY => 'Community',
            self::WORSHIP => 'Worship',
            self::MEDIA => 'Media',
            self::HOSPITALITY => 'Hospitality',
            self::CHILDREN => 'Children',
            self::YOUTH => 'Youth',
            self::OUTREACH => 'Outreach',
            self::ADMINISTRATION => 'Administration',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::SERVICE => 'Dienst',
            self::LEADERSHIP => 'Leitung',
            self::COMMUNITY => 'Gemeinschaft',
            self::WORSHIP => 'Lobpreis',
            self::MEDIA => 'Medien',
            self::HOSPITALITY => 'Empfang',
            self::CHILDREN => 'Kinder',
            self::YOUTH => 'Jugend',
            self::OUTREACH => 'Mission',
            self::ADMINISTRATION => 'Verwaltung',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SERVICE => 'blue',
            self::LEADERSHIP => 'purple',
            self::COMMUNITY => 'green',
            self::WORSHIP => 'pink',
            self::MEDIA => 'orange',
            self::HOSPITALITY => 'teal',
            self::CHILDREN => 'yellow',
            self::YOUTH => 'red',
            self::OUTREACH => 'indigo',
            self::ADMINISTRATION => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SERVICE => 'wrench-screwdriver',
            self::LEADERSHIP => 'user-group',
            self::COMMUNITY => 'users',
            self::WORSHIP => 'musical-note',
            self::MEDIA => 'video-camera',
            self::HOSPITALITY => 'home',
            self::CHILDREN => 'face-smile',
            self::YOUTH => 'rocket-launch',
            self::OUTREACH => 'globe-alt',
            self::ADMINISTRATION => 'clipboard-document-list',
        };
    }
}
