/**
 * Utilitaire pour importer les données des églises ICC depuis le site web
 *
 * Instructions :
 * 1. Allez sur https://impactcentrechretien.com/accueil/adresses/
 * 2. Ouvrez la console développeur (F12)
 * 3. Copiez et exécutez ce script dans la console
 * 4. Le résultat sera affiché en format TypeScript à copier dans iccChurches.ts
 */

// Script à exécuter dans la console du navigateur sur la page des adresses ICC
export const browserScript = `
// Collecte toutes les églises affichées sur la page
const churches = [];
const churchElements = document.querySelectorAll('.church-item, .location-card, [data-church]');

churchElements.forEach((el, index) => {
  // Adaptez les sélecteurs selon la structure HTML réelle
  const name = el.querySelector('.church-name, h3, .title')?.textContent?.trim();
  const city = el.querySelector('.city, .location')?.textContent?.trim();
  const country = el.querySelector('.country')?.textContent?.trim();
  const address = el.querySelector('.address, .full-address')?.textContent?.trim();

  if (name && city) {
    churches.push({
      name: name,
      city: city,
      country: country || 'France',
      address: address || '',
      // Les coordonnées devront être ajoutées manuellement ou via API géocodage
      coordinates: [0, 0],
      members: 100 // Valeur par défaut
    });
  }
});

// Output results
JSON.stringify({ count: churches.length, churches: churches }, null, 2);
`;

// Fonction helper pour convertir une adresse en coordonnées GPS
// Nécessite une clé API Google Maps
export async function geocodeAddress(address: string, apiKey: string): Promise<[number, number] | null> {
  try {
    const response = await fetch(
      `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(address)}&key=${apiKey}`
    );
    const data = await response.json();

    if (data.results && data.results.length > 0) {
      const location = data.results[0].geometry.location;
      return [location.lng, location.lat];
    }
  } catch (error) {
    console.error('Geocoding error:', error);
  }
  return null;
}

// Fonction pour générer le code TypeScript à partir des données
export function generateTypeScriptCode(churches: any[]): string {
  return `export const iccChurches: Church[] = [\n${churches.map(church => `  {
    name: '${church.name}',
    city: '${church.city}',
    country: '${church.country}',
    coordinates: [${church.coordinates[0]}, ${church.coordinates[1]}],
    ${church.members ? `members: ${church.members},` : ''}
    ${church.address ? `address: '${church.address}',` : ''}
  }`).join(',\n')}\n];`;
}
