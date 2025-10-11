/**
 * Script de vérification des données des églises ICC
 * Exécutez ce fichier pour vérifier l'intégrité des données
 */

import { iccChurches, getIccStats } from './iccChurches';

// Fonction de vérification
export function verifyChurchData() {
  const errors: string[] = [];
  const warnings: string[] = [];

  console.log('🔍 Vérification des données des églises ICC...\n');

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

  // Afficher les statistiques
  const stats = getIccStats();
  console.log('📊 Statistiques:');
  console.log(`   Total églises: ${stats.totalChurches}`);
  console.log(`   Total pays: ${stats.totalCountries}`);
  console.log(`   Total membres: ${stats.totalMembers.toLocaleString()}`);
  console.log(`\n📍 Par continent:`);
  console.log(`   Europe: ${stats.continents.europe}`);
  console.log(`   Afrique: ${stats.continents.africa}`);
  console.log(`   Amérique du Nord: ${stats.continents.northAmerica}`);
  console.log(`   Amérique du Sud: ${stats.continents.southAmerica}`);
  console.log(`   Océanie: ${stats.continents.oceania}`);

  if (stats.byCountry) {
    console.log(`\n🌍 Top 10 pays:`);
    Object.entries(stats.byCountry)
      .sort((a, b) => b[1] - a[1])
      .slice(0, 10)
      .forEach(([country, count], index) => {
        console.log(`   ${index + 1}. ${country}: ${count} églises`);
      });
  }

  // Afficher les résultats
  console.log('\n');
  if (errors.length === 0 && warnings.length === 0) {
    console.log('✅ Toutes les données sont valides!\n');
    return true;
  }

  if (errors.length > 0) {
    console.log(`❌ ${errors.length} erreur(s) trouvée(s):`);
    errors.forEach(error => console.log(`   - ${error}`));
    console.log('');
  }

  if (warnings.length > 0) {
    console.log(`⚠️  ${warnings.length} avertissement(s):`);
    warnings.forEach(warning => console.log(`   - ${warning}`));
    console.log('');
  }

  return errors.length === 0;
}

// Exécuter si appelé directement
if (require.main === module) {
  const isValid = verifyChurchData();
  process.exit(isValid ? 0 : 1);
}
