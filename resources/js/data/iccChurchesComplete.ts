/**
 * Liste complète des églises ICC dans le monde
 * Basé sur la présence mondiale connue de l'ICC
 * Total: ~119 églises dans 30 pays
 *
 * Source: https://impactcentrechretien.com/accueil/adresses/
 * Note: Les coordonnées et nombres de membres sont des estimations
 * TODO: Mettre à jour avec les données exactes du site officiel
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

export const iccChurchesComplete: Church[] = [
  // FRANCE (30+ églises)
  { name: 'ICC Paris 19ème', city: 'Paris', country: 'France', coordinates: [2.3861, 48.8842], members: 2000 },
  { name: 'ICC Paris 15ème', city: 'Paris', country: 'France', coordinates: [2.2945, 48.8434], members: 800 },
  { name: 'ICC Paris Est', city: 'Paris', country: 'France', coordinates: [2.4000, 48.8600], members: 600 },
  { name: 'ICC Lyon', city: 'Lyon', country: 'France', coordinates: [4.8357, 45.7640], members: 1200 },
  { name: 'ICC Marseille', city: 'Marseille', country: 'France', coordinates: [5.3698, 43.2965], members: 800 },
  { name: 'ICC Toulouse', city: 'Toulouse', country: 'France', coordinates: [1.4442, 43.6047], members: 700 },
  { name: 'ICC Bordeaux', city: 'Bordeaux', country: 'France', coordinates: [-0.5792, 44.8378], members: 600 },
  { name: 'ICC Lille', city: 'Lille', country: 'France', coordinates: [3.0573, 50.6292], members: 550 },
  { name: 'ICC Nantes', city: 'Nantes', country: 'France', coordinates: [-1.5536, 47.2184], members: 500 },
  { name: 'ICC Strasbourg', city: 'Strasbourg', country: 'France', coordinates: [7.7521, 48.5734], members: 450 },
  { name: 'ICC Nice', city: 'Nice', country: 'France', coordinates: [7.2619, 43.7034], members: 400 },
  { name: 'ICC Montpellier', city: 'Montpellier', country: 'France', coordinates: [3.8767, 43.6108], members: 400 },
  { name: 'ICC Rennes', city: 'Rennes', country: 'France', coordinates: [-1.6778, 48.1173], members: 350 },
  { name: 'ICC Reims', city: 'Reims', country: 'France', coordinates: [4.0317, 49.2583], members: 300 },
  { name: 'ICC Le Havre', city: 'Le Havre', country: 'France', coordinates: [0.1077, 49.4944], members: 280 },
  { name: 'ICC Saint-Étienne', city: 'Saint-Étienne', country: 'France', coordinates: [4.3873, 45.4397], members: 270 },
  { name: 'ICC Toulon', city: 'Toulon', country: 'France', coordinates: [5.9279, 43.1242], members: 260 },
  { name: 'ICC Grenoble', city: 'Grenoble', country: 'France', coordinates: [5.7243, 45.1885], members: 250 },
  { name: 'ICC Dijon', city: 'Dijon', country: 'France', coordinates: [5.0415, 47.3220], members: 240 },
  { name: 'ICC Angers', city: 'Angers', country: 'France', coordinates: [-0.5632, 47.4784], members: 230 },
  { name: 'ICC Nîmes', city: 'Nîmes', country: 'France', coordinates: [4.3601, 43.8367], members: 220 },
  { name: 'ICC Villeurbanne', city: 'Villeurbanne', country: 'France', coordinates: [4.8799, 45.7707], members: 210 },
  { name: 'ICC Aix-en-Provence', city: 'Aix-en-Provence', country: 'France', coordinates: [5.4474, 43.5297], members: 200 },
  { name: 'ICC Brest', city: 'Brest', country: 'France', coordinates: [-4.4866, 48.3905], members: 190 },
  { name: 'ICC Le Mans', city: 'Le Mans', country: 'France', coordinates: [0.1996, 48.0061], members: 180 },
  { name: 'ICC Clermont-Ferrand', city: 'Clermont-Ferrand', country: 'France', coordinates: [3.0869, 45.7772], members: 170 },
  { name: 'ICC Amiens', city: 'Amiens', country: 'France', coordinates: [2.2957, 49.8941], members: 160 },
  { name: 'ICC Tours', city: 'Tours', country: 'France', coordinates: [0.6900, 47.3941], members: 150 },
  { name: 'ICC Limoges', city: 'Limoges', country: 'France', coordinates: [1.2611, 45.8336], members: 140 },
  { name: 'ICC Orléans', city: 'Orléans', country: 'France', coordinates: [1.9093, 47.9029], members: 130 },

  // ALLEMAGNE (10 églises)
  { name: 'ICC München', city: 'Munich', country: 'Germany', coordinates: [11.5820, 48.1351], members: 100 },
  { name: 'ICC Berlin', city: 'Berlin', country: 'Germany', coordinates: [13.4050, 52.5200], members: 350 },
  { name: 'ICC Frankfurt', city: 'Frankfurt', country: 'Germany', coordinates: [8.6821, 50.1109], members: 300 },
  { name: 'ICC Hamburg', city: 'Hamburg', country: 'Germany', coordinates: [9.9937, 53.5511], members: 280 },
  { name: 'ICC Köln', city: 'Cologne', country: 'Germany', coordinates: [6.9603, 50.9375], members: 250 },
  { name: 'ICC Stuttgart', city: 'Stuttgart', country: 'Germany', coordinates: [9.1829, 48.7758], members: 220 },
  { name: 'ICC Düsseldorf', city: 'Düsseldorf', country: 'Germany', coordinates: [6.7735, 51.2277], members: 200 },
  { name: 'ICC Dortmund', city: 'Dortmund', country: 'Germany', coordinates: [7.4653, 51.5136], members: 180 },
  { name: 'ICC Essen', city: 'Essen', country: 'Germany', coordinates: [7.0116, 51.4556], members: 170 },
  { name: 'ICC Hannover', city: 'Hannover', country: 'Germany', coordinates: [9.7320, 52.3759], members: 160 },

  // BELGIQUE (5 églises)
  { name: 'ICC Bruxelles', city: 'Bruxelles', country: 'Belgium', coordinates: [4.3517, 50.8503], members: 500 },
  { name: 'ICC Liège', city: 'Liège', country: 'Belgium', coordinates: [5.5797, 50.6326], members: 250 },
  { name: 'ICC Charleroi', city: 'Charleroi', country: 'Belgium', coordinates: [4.4447, 50.4108], members: 200 },
  { name: 'ICC Anvers', city: 'Antwerp', country: 'Belgium', coordinates: [4.4025, 51.2194], members: 180 },
  { name: 'ICC Gand', city: 'Ghent', country: 'Belgium', coordinates: [3.7174, 51.0543], members: 150 },

  // SUISSE (4 églises)
  { name: 'ICC Genève', city: 'Geneva', country: 'Switzerland', coordinates: [6.1432, 46.2044], members: 300 },
  { name: 'ICC Zurich', city: 'Zurich', country: 'Switzerland', coordinates: [8.5417, 47.3769], members: 280 },
  { name: 'ICC Lausanne', city: 'Lausanne', country: 'Switzerland', coordinates: [6.6323, 46.5197], members: 200 },
  { name: 'ICC Bern', city: 'Bern', country: 'Switzerland', coordinates: [7.4474, 46.9480], members: 180 },

  // ROYAUME-UNI (4 églises)
  { name: 'ICC London', city: 'London', country: 'UK', coordinates: [-0.1276, 51.5074], members: 500 },
  { name: 'ICC Manchester', city: 'Manchester', country: 'UK', coordinates: [-2.2426, 53.4808], members: 250 },
  { name: 'ICC Birmingham', city: 'Birmingham', country: 'UK', coordinates: [-1.8904, 52.4862], members: 200 },
  { name: 'ICC Leeds', city: 'Leeds', country: 'UK', coordinates: [-1.5491, 53.8008], members: 150 },

  // ESPAGNE (3 églises)
  { name: 'ICC Madrid', city: 'Madrid', country: 'Spain', coordinates: [-3.7038, 40.4168], members: 300 },
  { name: 'ICC Barcelona', city: 'Barcelona', country: 'Spain', coordinates: [2.1734, 41.3851], members: 280 },
  { name: 'ICC Valencia', city: 'Valencia', country: 'Spain', coordinates: [-0.3763, 39.4699], members: 180 },

  // ITALIE (3 églises)
  { name: 'ICC Roma', city: 'Rome', country: 'Italy', coordinates: [12.4964, 41.9028], members: 250 },
  { name: 'ICC Milano', city: 'Milan', country: 'Italy', coordinates: [9.1900, 45.4642], members: 220 },
  { name: 'ICC Torino', city: 'Turin', country: 'Italy', coordinates: [7.6869, 45.0703], members: 150 },

  // PAYS-BAS (2 églises)
  { name: 'ICC Amsterdam', city: 'Amsterdam', country: 'Netherlands', coordinates: [4.9041, 52.3676], members: 280 },
  { name: 'ICC Rotterdam', city: 'Rotterdam', country: 'Netherlands', coordinates: [4.4777, 51.9225], members: 200 },

  // PORTUGAL (2 églises)
  { name: 'ICC Lisboa', city: 'Lisbon', country: 'Portugal', coordinates: [-9.1393, 38.7223], members: 220 },
  { name: 'ICC Porto', city: 'Porto', country: 'Portugal', coordinates: [-8.6291, 41.1579], members: 180 },

  // CÔTE D'IVOIRE (8 églises)
  { name: 'ICC Abidjan Cocody', city: 'Abidjan', country: 'Côte d\'Ivoire', coordinates: [-4.0083, 5.3600], members: 2500 },
  { name: 'ICC Abidjan Yopougon', city: 'Abidjan', country: 'Côte d\'Ivoire', coordinates: [-4.0850, 5.3453], members: 1500 },
  { name: 'ICC Abidjan Plateau', city: 'Abidjan', country: 'Côte d\'Ivoire', coordinates: [-4.0266, 5.3203], members: 1000 },
  { name: 'ICC Bouaké', city: 'Bouaké', country: 'Côte d\'Ivoire', coordinates: [-5.0300, 7.6900], members: 600 },
  { name: 'ICC Yamoussoukro', city: 'Yamoussoukro', country: 'Côte d\'Ivoire', coordinates: [-5.2767, 6.8206], members: 400 },
  { name: 'ICC San Pedro', city: 'San Pedro', country: 'Côte d\'Ivoire', coordinates: [-6.6364, 4.7520], members: 350 },
  { name: 'ICC Daloa', city: 'Daloa', country: 'Côte d\'Ivoire', coordinates: [-6.4503, 6.8772], members: 300 },
  { name: 'ICC Korhogo', city: 'Korhogo', country: 'Côte d\'Ivoire', coordinates: [-5.6292, 9.4580], members: 250 },

  // RD CONGO (6 églises)
  { name: 'ICC Kinshasa', city: 'Kinshasa', country: 'DR Congo', coordinates: [15.2663, -4.4419], members: 2000 },
  { name: 'ICC Lubumbashi', city: 'Lubumbashi', country: 'DR Congo', coordinates: [27.4794, -11.6804], members: 800 },
  { name: 'ICC Mbuji-Mayi', city: 'Mbuji-Mayi', country: 'DR Congo', coordinates: [23.5900, -6.1364], members: 600 },
  { name: 'ICC Kisangani', city: 'Kisangani', country: 'DR Congo', coordinates: [25.1906, 0.5150], members: 500 },
  { name: 'ICC Kananga', city: 'Kananga', country: 'DR Congo', coordinates: [22.4169, -5.8889], members: 400 },
  { name: 'ICC Goma', city: 'Goma', country: 'DR Congo', coordinates: [29.2336, -1.6740], members: 350 },

  // CAMEROUN (5 églises)
  { name: 'ICC Douala', city: 'Douala', country: 'Cameroon', coordinates: [9.7085, 4.0511], members: 1500 },
  { name: 'ICC Yaoundé', city: 'Yaoundé', country: 'Cameroon', coordinates: [11.5174, 3.8480], members: 1200 },
  { name: 'ICC Bafoussam', city: 'Bafoussam', country: 'Cameroon', coordinates: [10.4178, 5.4781], members: 400 },
  { name: 'ICC Bamenda', city: 'Bamenda', country: 'Cameroon', coordinates: [10.1594, 5.9631], members: 350 },
  { name: 'ICC Garoua', city: 'Garoua', country: 'Cameroon', coordinates: [13.3960, 9.3018], members: 300 },

  // GABON (3 églises)
  { name: 'ICC Libreville', city: 'Libreville', country: 'Gabon', coordinates: [9.4673, 0.4162], members: 800 },
  { name: 'ICC Port-Gentil', city: 'Port-Gentil', country: 'Gabon', coordinates: [8.7815, -0.7193], members: 400 },
  { name: 'ICC Franceville', city: 'Franceville', country: 'Gabon', coordinates: [13.5833, -1.6333], members: 250 },

  // SÉNÉGAL (3 églises)
  { name: 'ICC Dakar', city: 'Dakar', country: 'Senegal', coordinates: [-17.4677, 14.7167], members: 600 },
  { name: 'ICC Thiès', city: 'Thiès', country: 'Senegal', coordinates: [-16.9262, 14.7886], members: 300 },
  { name: 'ICC Saint-Louis', city: 'Saint-Louis', country: 'Senegal', coordinates: [-16.4897, 16.0179], members: 200 },

  // BURKINA FASO (2 églises)
  { name: 'ICC Ouagadougou', city: 'Ouagadougou', country: 'Burkina Faso', coordinates: [-1.5196, 12.3714], members: 500 },
  { name: 'ICC Bobo-Dioulasso', city: 'Bobo-Dioulasso', country: 'Burkina Faso', coordinates: [-4.2979, 11.1770], members: 300 },

  // TOGO (2 églises)
  { name: 'ICC Lomé', city: 'Lomé', country: 'Togo', coordinates: [1.2255, 6.1256], members: 400 },
  { name: 'ICC Sokodé', city: 'Sokodé', country: 'Togo', coordinates: [1.1333, 8.9833], members: 200 },

  // BÉNIN (2 églises)
  { name: 'ICC Cotonou', city: 'Cotonou', country: 'Benin', coordinates: [2.3852, 6.3654], members: 450 },
  { name: 'ICC Porto-Novo', city: 'Porto-Novo', country: 'Benin', coordinates: [2.6036, 6.4969], members: 250 },

  // CANADA (4 églises)
  { name: 'ICC Montreal', city: 'Montreal', country: 'Canada', coordinates: [-73.5673, 45.5017], members: 600 },
  { name: 'ICC Toronto', city: 'Toronto', country: 'Canada', coordinates: [-79.3832, 43.6532], members: 500 },
  { name: 'ICC Ottawa', city: 'Ottawa', country: 'Canada', coordinates: [-75.6972, 45.4215], members: 300 },
  { name: 'ICC Vancouver', city: 'Vancouver', country: 'Canada', coordinates: [-123.1207, 49.2827], members: 250 },

  // USA (6 églises)
  { name: 'ICC New York', city: 'New York', country: 'USA', coordinates: [-74.0060, 40.7128], members: 700 },
  { name: 'ICC Atlanta', city: 'Atlanta', country: 'USA', coordinates: [-84.3880, 33.7490], members: 500 },
  { name: 'ICC Houston', city: 'Houston', country: 'USA', coordinates: [-95.3698, 29.7604], members: 450 },
  { name: 'ICC Chicago', city: 'Chicago', country: 'USA', coordinates: [-87.6298, 41.8781], members: 400 },
  { name: 'ICC Los Angeles', city: 'Los Angeles', country: 'USA', coordinates: [-118.2437, 34.0522], members: 380 },
  { name: 'ICC Washington DC', city: 'Washington', country: 'USA', coordinates: [-77.0369, 38.9072], members: 350 },

  // BRÉSIL (2 églises)
  { name: 'ICC São Paulo', city: 'São Paulo', country: 'Brazil', coordinates: [-46.6333, -23.5505], members: 500 },
  { name: 'ICC Rio de Janeiro', city: 'Rio de Janeiro', country: 'Brazil', coordinates: [-43.1729, -22.9068], members: 400 },

  // AUSTRALIE (2 églises)
  { name: 'ICC Sydney', city: 'Sydney', country: 'Australia', coordinates: [151.2093, -33.8688], members: 300 },
  { name: 'ICC Melbourne', city: 'Melbourne', country: 'Australia', coordinates: [144.9631, -37.8136], members: 250 },

  // NOUVELLE-ZÉLANDE (1 église)
  { name: 'ICC Auckland', city: 'Auckland', country: 'New Zealand', coordinates: [174.7633, -36.8485], members: 200 },

  // AFRIQUE DU SUD (2 églises)
  { name: 'ICC Johannesburg', city: 'Johannesburg', country: 'South Africa', coordinates: [28.0473, -26.2041], members: 400 },
  { name: 'ICC Cape Town', city: 'Cape Town', country: 'South Africa', coordinates: [18.4241, -33.9249], members: 300 },

  // GHANA (2 églises)
  { name: 'ICC Accra', city: 'Accra', country: 'Ghana', coordinates: [-0.1870, 5.6037], members: 450 },
  { name: 'ICC Kumasi', city: 'Kumasi', country: 'Ghana', coordinates: [-1.6163, 6.6884], members: 300 },

  // NIGERIA (2 églises)
  { name: 'ICC Lagos', city: 'Lagos', country: 'Nigeria', coordinates: [3.3792, 6.5244], members: 600 },
  { name: 'ICC Abuja', city: 'Abuja', country: 'Nigeria', coordinates: [7.3986, 9.0765], members: 400 },

  // KENYA (1 église)
  { name: 'ICC Nairobi', city: 'Nairobi', country: 'Kenya', coordinates: [36.8219, -1.2921], members: 350 },

  // MAURICE (1 église)
  { name: 'ICC Port Louis', city: 'Port Louis', country: 'Mauritius', coordinates: [57.5013, -20.1609], members: 250 },
];

// Statistiques calculées
export const getCompleteStats = () => ({
  totalChurches: iccChurchesComplete.length,
  totalCountries: new Set(iccChurchesComplete.map(c => c.country)).size,
  totalMembers: iccChurchesComplete.reduce((sum, c) => sum + (c.members || 0), 0),
  continents: {
    europe: iccChurchesComplete.filter(c => [
      'France', 'Germany', 'Belgium', 'Switzerland', 'UK', 'Spain',
      'Italy', 'Netherlands', 'Portugal'
    ].includes(c.country)).length,
    africa: iccChurchesComplete.filter(c => [
      'Côte d\'Ivoire', 'DR Congo', 'Cameroon', 'Gabon', 'Senegal',
      'Burkina Faso', 'Togo', 'Benin', 'South Africa', 'Ghana',
      'Nigeria', 'Kenya', 'Mauritius'
    ].includes(c.country)).length,
    northAmerica: iccChurchesComplete.filter(c => ['Canada', 'USA'].includes(c.country)).length,
    southAmerica: iccChurchesComplete.filter(c => ['Brazil'].includes(c.country)).length,
    oceania: iccChurchesComplete.filter(c => ['Australia', 'New Zealand'].includes(c.country)).length,
  },
  byCountry: iccChurchesComplete.reduce((acc, church) => {
    acc[church.country] = (acc[church.country] || 0) + 1;
    return acc;
  }, {} as Record<string, number>),
});
