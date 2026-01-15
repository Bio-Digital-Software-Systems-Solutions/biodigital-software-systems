<?php

namespace App\Enums\Star;

enum StarType: string
{
    case VOLUNTEER = 'volunteer';
    case LEADER = 'leader';
    case MENTOR = 'mentor';
    case AMBASSADOR = 'ambassador';
    case COORDINATOR = 'coordinator';

    public function label(): string
    {
        return match ($this) {
            self::VOLUNTEER => 'Bénévole',
            self::LEADER => 'Leader',
            self::MENTOR => 'Mentor',
            self::AMBASSADOR => 'Ambassadeur',
            self::COORDINATOR => 'Coordinateur',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::VOLUNTEER => 'Volunteer',
            self::LEADER => 'Leader',
            self::MENTOR => 'Mentor',
            self::AMBASSADOR => 'Ambassador',
            self::COORDINATOR => 'Coordinator',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::VOLUNTEER => 'Freiwilliger',
            self::LEADER => 'Leiter',
            self::MENTOR => 'Mentor',
            self::AMBASSADOR => 'Botschafter',
            self::COORDINATOR => 'Koordinator',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::VOLUNTEER => 'blue',
            self::LEADER => 'purple',
            self::MENTOR => 'orange',
            self::AMBASSADOR => 'gold',
            self::COORDINATOR => 'cyan',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::VOLUNTEER => 'hand-raised',
            self::LEADER => 'user-group',
            self::MENTOR => 'academic-cap',
            self::AMBASSADOR => 'megaphone',
            self::COORDINATOR => 'clipboard-document-list',
        };
    }

    public function minLevel(): int
    {
        return match ($this) {
            self::VOLUNTEER => 1,
            self::LEADER => 3,
            self::MENTOR => 4,
            self::AMBASSADOR => 5,
            self::COORDINATOR => 2,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::VOLUNTEER => 'Membre actif dans le service',
            self::LEADER => 'Responsable d\'équipe ou de département',
            self::MENTOR => 'Accompagne et forme les nouveaux membres',
            self::AMBASSADOR => 'Représente l\'organisation à l\'extérieur',
            self::COORDINATOR => 'Coordonne les activités et événements',
        };
    }
}
