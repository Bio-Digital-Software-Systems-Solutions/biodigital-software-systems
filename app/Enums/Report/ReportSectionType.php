<?php

namespace App\Enums\Report;

enum ReportSectionType: string
{
    case TEXT = 'text';
    case METRICS = 'metrics';
    case TABLE = 'table';
    case CHART = 'chart';
    case LIST = 'list';
    case CHECKLIST = 'checklist';
    case TIMELINE = 'timeline';
    case BUDGET = 'budget';
    case KANBAN = 'kanban';
    case GALLERY = 'gallery';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'Texte',
            self::METRICS => 'Métriques',
            self::TABLE => 'Tableau',
            self::CHART => 'Graphique',
            self::LIST => 'Liste',
            self::CHECKLIST => 'Checklist',
            self::TIMELINE => 'Chronologie',
            self::BUDGET => 'Budget',
            self::KANBAN => 'Kanban',
            self::GALLERY => 'Galerie',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::TEXT => 'align-left',
            self::METRICS => 'bar-chart-2',
            self::TABLE => 'table',
            self::CHART => 'pie-chart',
            self::LIST => 'list',
            self::CHECKLIST => 'check-square',
            self::TIMELINE => 'git-branch',
            self::BUDGET => 'dollar-sign',
            self::KANBAN => 'columns',
            self::GALLERY => 'image',
        };
    }

    public function defaultConfig(): array
    {
        return match ($this) {
            self::TEXT => ['format' => 'markdown', 'max_length' => 5000],
            self::METRICS => ['columns' => 3, 'show_trend' => true],
            self::TABLE => ['sortable' => true, 'paginated' => false],
            self::CHART => ['type' => 'bar', 'show_legend' => true],
            self::LIST => ['show_progress' => true, 'show_assignee' => true],
            self::CHECKLIST => ['show_progress' => true, 'allow_edit' => false],
            self::TIMELINE => ['orientation' => 'vertical'],
            self::BUDGET => ['currency' => 'EUR', 'show_variance' => true],
            self::KANBAN => ['columns' => ['todo', 'in_progress', 'done']],
            self::GALLERY => ['columns' => 4, 'lightbox' => true],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'icon' => $case->icon(),
        ], self::cases());
    }
}
