<?php

namespace App\Enums\Event;

enum BadgeStatus: string
{
    case PENDING = 'pending';
    case GENERATED = 'generated';
    case PRINTED = 'printed';
    case COLLECTED = 'collected';
    case LOST = 'lost';
    case REPLACED = 'replaced';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::GENERATED => 'Généré',
            self::PRINTED => 'Imprimé',
            self::COLLECTED => 'Récupéré',
            self::LOST => 'Perdu',
            self::REPLACED => 'Remplacé',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::GENERATED => 'Generated',
            self::PRINTED => 'Printed',
            self::COLLECTED => 'Collected',
            self::LOST => 'Lost',
            self::REPLACED => 'Replaced',
        };
    }

    public function labelDe(): string
    {
        return match ($this) {
            self::PENDING => 'Ausstehend',
            self::GENERATED => 'Generiert',
            self::PRINTED => 'Gedruckt',
            self::COLLECTED => 'Abgeholt',
            self::LOST => 'Verloren',
            self::REPLACED => 'Ersetzt',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::GENERATED => 'blue',
            self::PRINTED => 'cyan',
            self::COLLECTED => 'green',
            self::LOST => 'red',
            self::REPLACED => 'orange',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'clock',
            self::GENERATED => 'document',
            self::PRINTED => 'printer',
            self::COLLECTED => 'check-circle',
            self::LOST => 'exclamation-triangle',
            self::REPLACED => 'arrow-path',
        };
    }

    public function canPrint(): bool
    {
        return in_array($this, [self::GENERATED, self::LOST]);
    }

    public function canCollect(): bool
    {
        return $this === self::PRINTED;
    }

    public function needsReplacement(): bool
    {
        return $this === self::LOST;
    }
}
