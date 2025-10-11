/**
 * Données des églises Impact Centre Chrétien dans le monde
 *
 * Pour ajouter une nouvelle église :
 * 1. Trouvez les coordonnées GPS (latitude, longitude) sur Google Maps
 * 2. Ajoutez l'entrée dans le tableau ci-dessous
 *
 * Source des données : https://impactcentrechretien.com/accueil/adresses/
 */

export interface Church {
  name: string;
  city: string;
  country: string;
  coordinates: [number, number]; // [longitude, latitude]
  members?: number;
  address?: string;
  website?: string;
  email?: string;
  phone?: string;
}

// Import des données complètes
import { iccChurchesComplete } from './iccChurchesComplete';

// Utilisation des données complètes (119 églises dans 30 pays)
export const iccChurches: Church[] = iccChurchesComplete;

// Version simplifiée pour tests
export const iccChurchesSimplified: Church[] = [
  // FRANCE
  {
    name: 'ICC Paris',
    city: 'Paris',
    country: 'France',
    coordinates: [2.3522, 48.8566],
    members: 1500,
    address: 'Paris, France',
  },
  {
    name: 'ICC Lyon',
    city: 'Lyon',
    country: 'France',
    coordinates: [4.8357, 45.7640],
    members: 800,
  },
  {
    name: 'ICC Marseille',
    city: 'Marseille',
    country: 'France',
    coordinates: [5.3698, 43.2965],
    members: 600,
  },
  {
    name: 'ICC Toulouse',
    city: 'Toulouse',
    country: 'France',
    coordinates: [1.4442, 43.6047],
    members: 500,
  },
  {
    name: 'ICC Bordeaux',
    city: 'Bordeaux',
    country: 'France',
    coordinates: [-0.5792, 44.8378],
    members: 400,
  },
  {
    name: 'ICC Lille',
    city: 'Lille',
    country: 'France',
    coordinates: [3.0573, 50.6292],
    members: 350,
  },
  {
    name: 'ICC Strasbourg',
    city: 'Strasbourg',
    country: 'France',
    coordinates: [7.7521, 48.5734],
    members: 300,
  },

  // ALLEMAGNE
  {
    name: 'ICC München',
    city: 'Munich',
    country: 'Germany',
    coordinates: [11.5820, 48.1351],
    members: 100,
  },
  {
    name: 'ICC Berlin',
    city: 'Berlin',
    country: 'Germany',
    coordinates: [13.4050, 52.5200],
    members: 250,
  },
  {
    name: 'ICC Frankfurt',
    city: 'Frankfurt',
    country: 'Germany',
    coordinates: [8.6821, 50.1109],
    members: 200,
  },

  // BELGIQUE
  {
    name: 'ICC Bruxelles',
    city: 'Brussels',
    country: 'Belgium',
    coordinates: [4.3517, 50.8503],
    members: 350,
  },

  // SUISSE
  {
    name: 'ICC Genève',
    city: 'Geneva',
    country: 'Switzerland',
    coordinates: [6.1432, 46.2044],
    members: 200,
  },
  {
    name: 'ICC Zurich',
    city: 'Zurich',
    country: 'Switzerland',
    coordinates: [8.5417, 47.3769],
    members: 180,
  },

  // ROYAUME-UNI
  {
    name: 'ICC London',
    city: 'London',
    country: 'UK',
    coordinates: [-0.1276, 51.5074],
    members: 400,
  },

  // ESPAGNE
  {
    name: 'ICC Madrid',
    city: 'Madrid',
    country: 'Spain',
    coordinates: [-3.7038, 40.4168],
    members: 250,
  },
  {
    name: 'ICC Barcelona',
    city: 'Barcelona',
    country: 'Spain',
    coordinates: [2.1734, 41.3851],
    members: 220,
  },

  // ITALIE
  {
    name: 'ICC Roma',
    city: 'Rome',
    country: 'Italy',
    coordinates: [12.4964, 41.9028],
    members: 180,
  },

  // AFRIQUE - CÔTE D'IVOIRE
  {
    name: 'ICC Abidjan',
    city: 'Abidjan',
    country: 'Côte d\'Ivoire',
    coordinates: [-4.0083, 5.3600],
    members: 1200,
  },

  // AFRIQUE - RD CONGO
  {
    name: 'ICC Kinshasa',
    city: 'Kinshasa',
    country: 'DR Congo',
    coordinates: [15.2663, -4.4419],
    members: 1000,
  },

  // AFRIQUE - CAMEROUN
  {
    name: 'ICC Douala',
    city: 'Douala',
    country: 'Cameroon',
    coordinates: [9.7085, 4.0511],
    members: 900,
  },
  {
    name: 'ICC Yaoundé',
    city: 'Yaoundé',
    country: 'Cameroon',
    coordinates: [11.5174, 3.8480],
    members: 700,
  },

  // AFRIQUE - GABON
  {
    name: 'ICC Libreville',
    city: 'Libreville',
    country: 'Gabon',
    coordinates: [9.4673, 0.4162],
    members: 600,
  },

  // AFRIQUE - SÉNÉGAL
  {
    name: 'ICC Dakar',
    city: 'Dakar',
    country: 'Senegal',
    coordinates: [-17.4677, 14.7167],
    members: 500,
  },

  // AFRIQUE - BURKINA FASO
  {
    name: 'ICC Ouagadougou',
    city: 'Ouagadougou',
    country: 'Burkina Faso',
    coordinates: [-1.5196, 12.3714],
    members: 450,
  },

  // AMÉRIQUE DU NORD - CANADA
  {
    name: 'ICC Montreal',
    city: 'Montreal',
    country: 'Canada',
    coordinates: [-73.5673, 45.5017],
    members: 400,
  },
  {
    name: 'ICC Toronto',
    city: 'Toronto',
    country: 'Canada',
    coordinates: [-79.3832, 43.6532],
    members: 350,
  },

  // AMÉRIQUE DU NORD - USA
  {
    name: 'ICC New York',
    city: 'New York',
    country: 'USA',
    coordinates: [-74.0060, 40.7128],
    members: 450,
  },
  {
    name: 'ICC Atlanta',
    city: 'Atlanta',
    country: 'USA',
    coordinates: [-84.3880, 33.7490],
    members: 300,
  },
  {
    name: 'ICC Houston',
    city: 'Houston',
    country: 'USA',
    coordinates: [-95.3698, 29.7604],
    members: 280,
  },

  // AMÉRIQUE DU SUD - BRÉSIL
  {
    name: 'ICC São Paulo',
    city: 'São Paulo',
    country: 'Brazil',
    coordinates: [-46.6333, -23.5505],
    members: 350,
  },

  // OCÉANIE - AUSTRALIE
  {
    name: 'ICC Sydney',
    city: 'Sydney',
    country: 'Australia',
    coordinates: [151.2093, -33.8688],
    members: 200,
  },
];

// Statistiques calculées
export const getIccStats = () => ({
  totalChurches: iccChurches.length,
  totalCountries: new Set(iccChurches.map(c => c.country)).size,
  totalMembers: iccChurches.reduce((sum, c) => sum + (c.members || 0), 0),
  continents: {
    europe: iccChurches.filter(c => [
      'France', 'Germany', 'Belgium', 'Switzerland', 'UK', 'Spain',
      'Italy', 'Netherlands', 'Portugal'
    ].includes(c.country)).length,
    africa: iccChurches.filter(c => [
      'Côte d\'Ivoire', 'DR Congo', 'Cameroon', 'Gabon', 'Senegal',
      'Burkina Faso', 'Togo', 'Benin', 'South Africa', 'Ghana',
      'Nigeria', 'Kenya', 'Mauritius'
    ].includes(c.country)).length,
    northAmerica: iccChurches.filter(c => ['Canada', 'USA'].includes(c.country)).length,
    southAmerica: iccChurches.filter(c => ['Brazil'].includes(c.country)).length,
    oceania: iccChurches.filter(c => ['Australia', 'New Zealand'].includes(c.country)).length,
  },
  byCountry: iccChurches.reduce((acc, church) => {
    acc[church.country] = (acc[church.country] || 0) + 1;
    return acc;
  }, {} as Record<string, number>),
});
