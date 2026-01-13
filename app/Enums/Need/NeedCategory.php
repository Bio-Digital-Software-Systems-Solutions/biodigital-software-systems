<?php

namespace App\Enums\Need;

enum NeedCategory: string
{
    case EQUIPMENT = 'equipment';
    case SOFTWARE = 'software';
    case FURNITURE = 'furniture';
    case SUPPLIES = 'supplies';
    case SERVICES = 'services';
    case TRAINING = 'training';
    case RECRUITMENT = 'recruitment';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::EQUIPMENT => 'Équipement',
            self::SOFTWARE => 'Logiciel',
            self::FURNITURE => 'Mobilier',
            self::SUPPLIES => 'Fournitures',
            self::SERVICES => 'Services',
            self::TRAINING => 'Formation',
            self::RECRUITMENT => 'Recrutement',
            self::OTHER => 'Autre',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EQUIPMENT => 'blue',
            self::SOFTWARE => 'purple',
            self::FURNITURE => 'orange',
            self::SUPPLIES => 'green',
            self::SERVICES => 'cyan',
            self::TRAINING => 'yellow',
            self::RECRUITMENT => 'pink',
            self::OTHER => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::EQUIPMENT => 'computer-desktop',
            self::SOFTWARE => 'code-bracket',
            self::FURNITURE => 'building-office',
            self::SUPPLIES => 'archive-box',
            self::SERVICES => 'wrench-screwdriver',
            self::TRAINING => 'academic-cap',
            self::RECRUITMENT => 'user-plus',
            self::OTHER => 'ellipsis-horizontal',
        };
    }

    public function requiresBudgetApproval(): bool
    {
        return in_array($this, [self::EQUIPMENT, self::SOFTWARE, self::FURNITURE, self::SERVICES, self::RECRUITMENT]);
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
            'icon' => $case->icon(),
        ], self::cases());
    }
}
