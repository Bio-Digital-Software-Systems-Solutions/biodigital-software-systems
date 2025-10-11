import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

/**
 * Formate un nombre avec un maximum de 3 décimales, en supprimant les zéros inutiles
 * @param value - Le nombre à formater
 * @param maxDecimals - Nombre maximum de décimales (défaut: 3)
 * @returns Le nombre formaté sous forme de string
 */
export function formatNumber(value: number | null | undefined, maxDecimals: number = 3): string {
  if (value === null || value === undefined || isNaN(value)) {
    return '0';
  }

  // Arrondir à maxDecimals décimales
  const rounded = Math.round(value * Math.pow(10, maxDecimals)) / Math.pow(10, maxDecimals);

  // Convertir en string et supprimer les zéros inutiles
  return parseFloat(rounded.toFixed(maxDecimals)).toString();
}

/**
 * Interface pour les données de trend
 */
export interface TrendData {
  direction: 'up' | 'down' | 'stable';
  percentage: number;
  formatted: string;
  value: number;
}

/**
 * Calcule le trend entre deux valeurs
 * @param currentValue - Valeur actuelle
 * @param previousValue - Valeur précédente
 * @param isPercentage - Si les valeurs sont déjà en pourcentage
 * @returns Données du trend
 */
export function calculateTrend(
  currentValue: number | null | undefined,
  previousValue: number | null | undefined,
  isPercentage: boolean = false
): TrendData {
  // Si l'une des valeurs est null ou invalide
  if (currentValue === null || currentValue === undefined ||
      previousValue === null || previousValue === undefined || previousValue === 0) {
    return {
      direction: 'stable',
      percentage: 0,
      formatted: 'Pas de données précédentes',
      value: 0,
    };
  }

  // Calculer la différence en pourcentage
  const difference = currentValue - previousValue;
  const percentageChange = (difference / Math.abs(previousValue)) * 100;

  // Déterminer la direction
  let direction: 'up' | 'down' | 'stable' = 'stable';
  if (Math.abs(percentageChange) >= 0.1) { // Seuil de 0.1% pour considérer un changement
    direction = percentageChange > 0 ? 'up' : 'down';
  }

  // Formater le texte
  const formatted = formatTrendText(percentageChange, isPercentage);

  return {
    direction,
    percentage: Math.round(percentageChange * 100) / 100,
    formatted,
    value: Math.round(difference * 100) / 100,
  };
}

/**
 * Formate le trend en texte lisible
 */
function formatTrendText(percentageChange: number, isPercentage: boolean): string {
  const absChange = Math.abs(percentageChange);
  const sign = percentageChange >= 0 ? '+' : '';

  if (absChange < 0.1) {
    return 'Stable';
  }

  if (isPercentage) {
    return `${sign}${formatNumber(percentageChange, 1)} points vs période précédente`;
  }

  return `${sign}${formatNumber(percentageChange, 1)}% vs période précédente`;
}
