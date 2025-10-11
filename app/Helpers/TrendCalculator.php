<?php

namespace App\Helpers;

class TrendCalculator
{
    /**
     * Calcule le trend entre deux valeurs
     *
     * @param float|int $currentValue Valeur actuelle
     * @param float|int $previousValue Valeur précédente
     * @param bool $isPercentage Si les valeurs sont déjà en pourcentage
     * @return array ['direction' => 'up'|'down'|'stable', 'percentage' => float, 'formatted' => string]
     */
    public static function calculate($currentValue, $previousValue, bool $isPercentage = false): array
    {
        // Si l'une des valeurs est null ou invalide
        if ($currentValue === null || $previousValue === null || $previousValue == 0) {
            return [
                'direction' => 'stable',
                'percentage' => 0,
                'formatted' => 'Pas de données précédentes',
                'value' => 0,
            ];
        }

        // Calculer la différence en pourcentage
        $difference = $currentValue - $previousValue;
        $percentageChange = ($difference / abs($previousValue)) * 100;

        // Déterminer la direction
        $direction = 'stable';
        if (abs($percentageChange) >= 0.1) { // Seuil de 0.1% pour considérer un changement
            $direction = $percentageChange > 0 ? 'up' : 'down';
        }

        // Formater le texte
        $formatted = self::formatTrend($percentageChange, $isPercentage);

        return [
            'direction' => $direction,
            'percentage' => round($percentageChange, 2),
            'formatted' => $formatted,
            'value' => round($difference, 2),
        ];
    }

    /**
     * Formate le trend en texte lisible
     */
    private static function formatTrend(float $percentageChange, bool $isPercentage): string
    {
        $absChange = abs($percentageChange);
        $sign = $percentageChange >= 0 ? '+' : '';

        if ($absChange < 0.1) {
            return 'Stable';
        }

        if ($isPercentage) {
            return sprintf('%s%.1f points vs période précédente', $sign, $percentageChange);
        }

        return sprintf('%s%.1f%% vs période précédente', $sign, $percentageChange);
    }

    /**
     * Calcule le trend pour une métrique sur une période donnée
     *
     * @param callable $metricCallback Fonction qui retourne la valeur pour une période donnée
     * @param string $currentPeriodStart Début période actuelle
     * @param string $currentPeriodEnd Fin période actuelle
     * @param string $comparisonType Type de comparaison: 'previous' (période précédente), 'last_month', 'last_year'
     * @return array
     */
    public static function calculateForPeriod(
        callable $metricCallback,
        string $currentPeriodStart,
        string $currentPeriodEnd,
        string $comparisonType = 'previous'
    ): array {
        $currentValue = $metricCallback($currentPeriodStart, $currentPeriodEnd);

        [$previousStart, $previousEnd] = self::getPreviousPeriod(
            $currentPeriodStart,
            $currentPeriodEnd,
            $comparisonType
        );

        $previousValue = $metricCallback($previousStart, $previousEnd);

        return self::calculate($currentValue, $previousValue);
    }

    /**
     * Obtient la période précédente basée sur le type de comparaison
     */
    private static function getPreviousPeriod(
        string $currentStart,
        string $currentEnd,
        string $comparisonType
    ): array {
        $currentStartDate = new \DateTime($currentStart);
        $currentEndDate = new \DateTime($currentEnd);
        $interval = $currentStartDate->diff($currentEndDate);

        switch ($comparisonType) {
            case 'last_month':
                $previousStart = (clone $currentStartDate)->modify('-1 month');
                $previousEnd = (clone $currentEndDate)->modify('-1 month');
                break;

            case 'last_year':
                $previousStart = (clone $currentStartDate)->modify('-1 year');
                $previousEnd = (clone $currentEndDate)->modify('-1 year');
                break;

            case 'previous':
            default:
                // Période précédente de la même durée
                $previousEnd = clone $currentStartDate;
                $previousEnd->modify('-1 day');
                $previousStart = clone $previousEnd;
                $previousStart->sub($interval);
                break;
        }

        return [
            $previousStart->format('Y-m-d'),
            $previousEnd->format('Y-m-d'),
        ];
    }

    /**
     * Calcule les trends pour plusieurs métriques en une fois
     *
     * @param array $metrics Tableau associatif ['metric_name' => ['current' => value, 'previous' => value]]
     * @return array
     */
    public static function calculateMultiple(array $metrics): array
    {
        $results = [];

        foreach ($metrics as $name => $values) {
            $current = $values['current'] ?? 0;
            $previous = $values['previous'] ?? 0;
            $isPercentage = $values['is_percentage'] ?? false;

            $results[$name] = self::calculate($current, $previous, $isPercentage);
        }

        return $results;
    }
}
