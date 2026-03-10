<?php

namespace App\Enums\Report;

enum TrendDirection: string
{
    case HIGHER_IS_BETTER = 'higher_is_better';
    case LOWER_IS_BETTER = 'lower_is_better';
    case TARGET_IS_BEST = 'target_is_best';

    public function label(): string
    {
        return match ($this) {
            self::HIGHER_IS_BETTER => 'Plus élevé = mieux',
            self::LOWER_IS_BETTER => 'Plus bas = mieux',
            self::TARGET_IS_BEST => 'Cible = idéal',
        };
    }

    public function isGood(float $current, float $previous, ?float $target = null): bool
    {
        return match ($this) {
            self::HIGHER_IS_BETTER => $target !== null ? $current >= $target : $current > $previous,
            self::LOWER_IS_BETTER => $target !== null ? $current <= $target : $current < $previous,
            self::TARGET_IS_BEST => $target !== null
                ? abs($current - $target) <= abs($previous - $target)
                : true,
        };
    }

    public function getTrendIcon(float $current, float $previous): string
    {
        if ($current === $previous) {
            return 'minus';
        }

        $isIncreasing = $current > $previous;

        return match ($this) {
            self::HIGHER_IS_BETTER => $isIncreasing ? 'trending-up' : 'trending-down',
            self::LOWER_IS_BETTER => $isIncreasing ? 'trending-down' : 'trending-up',
            self::TARGET_IS_BEST => $isIncreasing ? 'trending-up' : 'trending-down',
        };
    }

    public function getTrendColor(float $current, float $previous, ?float $target = null): string
    {
        if ($this->isGood($current, $previous, $target)) {
            return 'green';
        }
        return 'red';
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toSelectOptions(): array
    {
        return array_map(fn(\App\Enums\Report\TrendDirection $case): array => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
