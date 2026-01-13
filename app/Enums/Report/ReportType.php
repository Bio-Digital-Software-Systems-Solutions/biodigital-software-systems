<?php

namespace App\Enums\Report;

enum ReportType: string
{
    case MONTHLY_ACTIVITY = 'monthly_activity';
    case MONTHLY_OBJECTIVES = 'monthly_objectives';
    case QUARTERLY_REVIEW = 'quarterly_review';
    case ANNUAL_SUMMARY = 'annual_summary';
    case PROJECT_STATUS = 'project_status';
    case BUDGET_REPORT = 'budget_report';
    case KPI_DASHBOARD = 'kpi_dashboard';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY_ACTIVITY => 'Rapport d\'activité mensuel',
            self::MONTHLY_OBJECTIVES => 'Bilan des objectifs mensuel',
            self::QUARTERLY_REVIEW => 'Revue trimestrielle',
            self::ANNUAL_SUMMARY => 'Synthèse annuelle',
            self::PROJECT_STATUS => 'État du projet',
            self::BUDGET_REPORT => 'Rapport budgétaire',
            self::KPI_DASHBOARD => 'Tableau de bord KPI',
            self::CUSTOM => 'Rapport personnalisé',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MONTHLY_ACTIVITY => 'calendar',
            self::MONTHLY_OBJECTIVES => 'target',
            self::QUARTERLY_REVIEW => 'bar-chart-2',
            self::ANNUAL_SUMMARY => 'file-text',
            self::PROJECT_STATUS => 'folder',
            self::BUDGET_REPORT => 'dollar-sign',
            self::KPI_DASHBOARD => 'pie-chart',
            self::CUSTOM => 'settings',
        };
    }

    public function defaultSections(): array
    {
        return match ($this) {
            self::MONTHLY_ACTIVITY => ['metrics', 'table', 'text'],
            self::MONTHLY_OBJECTIVES => ['checklist', 'list', 'text'],
            self::QUARTERLY_REVIEW => ['metrics', 'checklist', 'chart', 'text'],
            self::ANNUAL_SUMMARY => ['metrics', 'chart', 'table', 'text'],
            self::PROJECT_STATUS => ['metrics', 'timeline', 'list', 'text'],
            self::BUDGET_REPORT => ['budget', 'chart', 'table'],
            self::KPI_DASHBOARD => ['metrics', 'chart'],
            self::CUSTOM => [],
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
