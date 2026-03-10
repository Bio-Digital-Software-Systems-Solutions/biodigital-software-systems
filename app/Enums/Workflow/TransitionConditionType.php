<?php

namespace App\Enums\Workflow;

enum TransitionConditionType: string
{
    case ALWAYS = 'always';
    case EXPRESSION = 'expression';
    case APPROVAL_RESULT = 'approval_result';
    case FORM_FIELD = 'form_field';
    case VARIABLE = 'variable';

    public function label(): string
    {
        return match ($this) {
            self::ALWAYS => 'Toujours',
            self::EXPRESSION => 'Expression',
            self::APPROVAL_RESULT => 'Résultat d\'approbation',
            self::FORM_FIELD => 'Champ de formulaire',
            self::VARIABLE => 'Variable',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ALWAYS => 'La transition est toujours effectuée',
            self::EXPRESSION => 'Évaluation d\'une expression logique',
            self::APPROVAL_RESULT => 'Basé sur le résultat de l\'approbation (approuvé/rejeté)',
            self::FORM_FIELD => 'Basé sur la valeur d\'un champ de formulaire',
            self::VARIABLE => 'Basé sur une variable du contexte',
        };
    }

    public function requiresConfig(): bool
    {
        return $this !== self::ALWAYS;
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Workflow\TransitionConditionType $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
            'requires_config' => $case->requiresConfig(),
        ], self::cases());
    }
}
