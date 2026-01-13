<?php

namespace App\Enums\Workflow;

enum StepType: string
{
    case START = 'start';
    case END = 'end';
    case APPROVAL = 'approval';
    case CONDITION = 'condition';
    case ACTION = 'action';
    case WAIT = 'wait';
    case NOTIFICATION = 'notification';
    case FORM = 'form';
    case SUBPROCESS = 'subprocess';
    case PARALLEL_SPLIT = 'parallel_split';
    case PARALLEL_JOIN = 'parallel_join';

    public function label(): string
    {
        return match ($this) {
            self::START => 'Début',
            self::END => 'Fin',
            self::APPROVAL => 'Approbation',
            self::CONDITION => 'Condition',
            self::ACTION => 'Action',
            self::WAIT => 'Attente',
            self::NOTIFICATION => 'Notification',
            self::FORM => 'Formulaire',
            self::SUBPROCESS => 'Sous-processus',
            self::PARALLEL_SPLIT => 'Division parallèle',
            self::PARALLEL_JOIN => 'Jonction parallèle',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::START => 'green',
            self::END => 'red',
            self::APPROVAL => 'blue',
            self::CONDITION => 'yellow',
            self::ACTION => 'purple',
            self::WAIT => 'orange',
            self::NOTIFICATION => 'cyan',
            self::FORM => 'indigo',
            self::SUBPROCESS => 'pink',
            self::PARALLEL_SPLIT => 'teal',
            self::PARALLEL_JOIN => 'teal',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::START => 'play',
            self::END => 'stop',
            self::APPROVAL => 'check-badge',
            self::CONDITION => 'arrows-pointing-out',
            self::ACTION => 'bolt',
            self::WAIT => 'clock',
            self::NOTIFICATION => 'bell',
            self::FORM => 'document-text',
            self::SUBPROCESS => 'squares-2x2',
            self::PARALLEL_SPLIT => 'arrows-expand',
            self::PARALLEL_JOIN => 'arrows-pointing-in',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::START => 'Point de départ du workflow',
            self::END => 'Point de fin du workflow',
            self::APPROVAL => 'Demande d\'approbation à un ou plusieurs utilisateurs',
            self::CONDITION => 'Branchement conditionnel basé sur une expression',
            self::ACTION => 'Exécution d\'une action automatique',
            self::WAIT => 'Pause pour une durée ou jusqu\'à un événement',
            self::NOTIFICATION => 'Envoi de notification',
            self::FORM => 'Affichage d\'un formulaire à remplir',
            self::SUBPROCESS => 'Démarrage d\'un sous-workflow',
            self::PARALLEL_SPLIT => 'Division en branches parallèles',
            self::PARALLEL_JOIN => 'Attente de toutes les branches parallèles',
        };
    }

    public function requiresConfig(): bool
    {
        return match ($this) {
            self::START, self::END, self::PARALLEL_SPLIT, self::PARALLEL_JOIN => false,
            default => true,
        };
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
            'icon' => $case->icon(),
            'description' => $case->description(),
        ], self::cases());
    }

    public function category(): string
    {
        return match ($this) {
            self::START, self::END => 'flow',
            self::APPROVAL => 'approval',
            self::CONDITION, self::PARALLEL_SPLIT, self::PARALLEL_JOIN => 'logic',
            self::ACTION, self::NOTIFICATION, self::WAIT => 'action',
            self::FORM => 'form',
            self::SUBPROCESS => 'subprocess',
        };
    }

    public static function groupedOptions(): array
    {
        $groups = [];
        $categoryLabels = [
            'flow' => 'Flux',
            'approval' => 'Approbation',
            'logic' => 'Logique',
            'action' => 'Actions',
            'form' => 'Formulaires',
            'subprocess' => 'Sous-processus',
        ];

        foreach (self::cases() as $case) {
            $category = $case->category();
            if (!isset($groups[$category])) {
                $groups[$category] = [
                    'label' => $categoryLabels[$category] ?? $category,
                    'options' => [],
                ];
            }
            $groups[$category]['options'][] = [
                'value' => $case->value,
                'label' => $case->label(),
                'color' => $case->color(),
                'icon' => $case->icon(),
                'description' => $case->description(),
            ];
        }
        return array_values($groups);
    }
}
