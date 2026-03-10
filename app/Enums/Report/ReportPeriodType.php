<?php

namespace App\Enums\Report;

use Carbon\Carbon;

enum ReportPeriodType: string
{
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case ANNUAL = 'annual';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::WEEKLY => 'Hebdomadaire',
            self::MONTHLY => 'Mensuel',
            self::QUARTERLY => 'Trimestriel',
            self::ANNUAL => 'Annuel',
            self::CUSTOM => 'Personnalisé',
        };
    }

    public function getDates(Carbon $reference): array
    {
        return match ($this) {
            self::WEEKLY => [
                $reference->copy()->startOfWeek(),
                $reference->copy()->endOfWeek(),
            ],
            self::MONTHLY => [
                $reference->copy()->startOfMonth(),
                $reference->copy()->endOfMonth(),
            ],
            self::QUARTERLY => [
                $reference->copy()->firstOfQuarter(),
                $reference->copy()->lastOfQuarter(),
            ],
            self::ANNUAL => [
                $reference->copy()->startOfYear(),
                $reference->copy()->endOfYear(),
            ],
            self::CUSTOM => [$reference, $reference],
        };
    }

    public function getPreviousPeriodDates(Carbon $reference): array
    {
        return match ($this) {
            self::WEEKLY => [
                $reference->copy()->subWeek()->startOfWeek(),
                $reference->copy()->subWeek()->endOfWeek(),
            ],
            self::MONTHLY => [
                $reference->copy()->subMonth()->startOfMonth(),
                $reference->copy()->subMonth()->endOfMonth(),
            ],
            self::QUARTERLY => [
                $reference->copy()->subQuarter()->firstOfQuarter(),
                $reference->copy()->subQuarter()->lastOfQuarter(),
            ],
            self::ANNUAL => [
                $reference->copy()->subYear()->startOfYear(),
                $reference->copy()->subYear()->endOfYear(),
            ],
            self::CUSTOM => [$reference, $reference],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Report\ReportPeriodType $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
