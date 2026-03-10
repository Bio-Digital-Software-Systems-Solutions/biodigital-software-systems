<?php

namespace App\Enums\Report;

enum ActivityCategory: string
{
    case MEETING = 'meeting';
    case PROJECT_WORK = 'project_work';
    case TRAINING = 'training';
    case ADMINISTRATIVE = 'administrative';
    case CLIENT_INTERACTION = 'client_interaction';
    case RESEARCH = 'research';
    case MAINTENANCE = 'maintenance';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MEETING => 'Réunion',
            self::PROJECT_WORK => 'Travail projet',
            self::TRAINING => 'Formation',
            self::ADMINISTRATIVE => 'Administratif',
            self::CLIENT_INTERACTION => 'Interaction client',
            self::RESEARCH => 'Recherche',
            self::MAINTENANCE => 'Maintenance',
            self::OTHER => 'Autre',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MEETING => 'blue',
            self::PROJECT_WORK => 'green',
            self::TRAINING => 'purple',
            self::ADMINISTRATIVE => 'gray',
            self::CLIENT_INTERACTION => 'orange',
            self::RESEARCH => 'cyan',
            self::MAINTENANCE => 'yellow',
            self::OTHER => 'slate',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MEETING => 'users',
            self::PROJECT_WORK => 'briefcase',
            self::TRAINING => 'graduation-cap',
            self::ADMINISTRATIVE => 'file-text',
            self::CLIENT_INTERACTION => 'message-circle',
            self::RESEARCH => 'search',
            self::MAINTENANCE => 'wrench',
            self::OTHER => 'more-horizontal',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Report\ActivityCategory $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
            'icon' => $case->icon(),
        ], self::cases());
    }
}
