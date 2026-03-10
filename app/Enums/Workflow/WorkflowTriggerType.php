<?php

namespace App\Enums\Workflow;

enum WorkflowTriggerType: string
{
    case MANUAL = 'manual';
    case EVENT = 'event';
    case SCHEDULED = 'scheduled';
    case FORM_SUBMISSION = 'form_submission';
    case WEBHOOK = 'webhook';
    case API = 'api';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manuel',
            self::EVENT => 'Événement',
            self::SCHEDULED => 'Planifié',
            self::FORM_SUBMISSION => 'Soumission de formulaire',
            self::WEBHOOK => 'Webhook',
            self::API => 'API',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::MANUAL => 'blue',
            self::EVENT => 'purple',
            self::SCHEDULED => 'orange',
            self::FORM_SUBMISSION => 'green',
            self::WEBHOOK => 'cyan',
            self::API => 'indigo',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MANUAL => 'hand',
            self::EVENT => 'bolt',
            self::SCHEDULED => 'clock',
            self::FORM_SUBMISSION => 'document-text',
            self::WEBHOOK => 'globe-alt',
            self::API => 'code-bracket',
        };
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Workflow\WorkflowTriggerType $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }
}
