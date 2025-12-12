/**
 * Script de vérification des données des églises ICC
 * Exécutez ce fichier pour vérifier l'intégrité des données
 */

import { iccChurches, getIccStats } from './iccChurches';

interface VerificationResult {
  isValid: boolean;
  errors: string[];
  warnings: string[];
  stats: ReturnType<typeof getIccStats>;
}

// Fonction de vérification
export function verifyChurchData(): VerificationResult {
  const errors: string[] = [];
  const warnings: string[] = [];

  // Vérifier chaque église
  iccChurches.forEach((church, index) => {
    // Vérifier les champs obligatoires
    if (!church.name) {
      errors.push(`Église #${index + 1}: Nom manquant`);
    }
    if (!church.city) {
      errors.push(`${church.name || `Église #${index + 1}`}: Ville manquante`);
    }
    if (!church.country) {
      errors.push(`${church.name || `Église #${index + 1}`}: Pays manquant`);
    }
    if (!church.coordinates || church.coordinates.length !== 2) {
      errors.push(`${church.name || `Église #${index + 1}`}: Coordonnées invalides`);
    } else {
      // Vérifier les limites des coordonnées
      const [lng, lat] = church.coordinates;
      if (lng < -180 || lng > 180) {
        errors.push(`${church.name}: Longitude invalide (${lng}). Doit être entre -180 et 180`);
      }
      if (lat < -90 || lat > 90) {
        errors.push(`${church.name}: Latitude invalide (${lat}). Doit être entre -90 et 90`);
      }
      // Vérifier si les coordonnées sont à (0, 0) - probablement une erreur
      if (lng === 0 && lat === 0) {
        warnings.push(`${church.name}: Coordonnées à (0, 0) - vérifier si c'est correct`);
      }
    }

    // Avertissements pour les données optionnelles
    if (!church.members) {
      warnings.push(`${church.name}: Nombre de membres non spécifié`);
    }
  });

  // Vérifier les doublons
  const nameMap = new Map<string, number>();
  iccChurches.forEach((church, index) => {
    const key = `${church.name}-${church.city}`;
    if (nameMap.has(key)) {
      warnings.push(`Doublon potentiel: ${church.name} à ${church.city} (indices ${nameMap.get(key)}, ${index + 1})`);
    } else {
      nameMap.set(key, index + 1);
    }
  });

  // Récupérer les statistiques
  const stats = getIccStats();

  return {
    isValid: errors.length === 0,
    errors,
    warnings,
    stats
  };
}

// Exécuter si appelé directement
if (require.main === module) {
  const result = verifyChurchData();
  process.exit(result.isValid ? 0 : 1);
}
