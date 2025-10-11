# Données des Églises ICC

Ce dossier contient les données des églises Impact Centre Chrétien dans le monde.

## Comment mettre à jour les données

### 1. Fichier principal : `iccChurches.ts`

Ce fichier contient la liste de toutes les églises ICC. Pour ajouter une nouvelle église :

```typescript
{
  name: 'ICC Nom de la Ville',
  city: 'Nom de la Ville',
  country: 'Pays',
  coordinates: [longitude, latitude],
  members: 100, // optionnel
  address: 'Adresse complète', // optionnel
  website: 'https://...', // optionnel
  email: 'contact@...', // optionnel
  phone: '+...', // optionnel
}
```

### 2. Comment trouver les coordonnées GPS

#### Méthode 1 : Google Maps
1. Allez sur [Google Maps](https://maps.google.com)
2. Recherchez l'adresse de l'église
3. Cliquez droit sur le marqueur
4. Cliquez sur "Coordonnées GPS" ou les coordonnées affichées
5. Les coordonnées sont au format : `latitude, longitude`
6. **Important** : Dans le code, inversez l'ordre → `[longitude, latitude]`

#### Méthode 2 : latlong.net
1. Allez sur [https://www.latlong.net](https://www.latlong.net)
2. Entrez l'adresse
3. Copiez les coordonnées
4. Format dans le code : `[longitude, latitude]`

### 3. Exemple pratique

Si Google Maps affiche : `48.8566, 2.3522`
Dans le code, écrivez : `coordinates: [2.3522, 48.8566]`

### 4. Source des données

Les données officielles sont disponibles sur :
https://impactcentrechretien.com/accueil/adresses/

### 5. Après modification

Après avoir modifié le fichier `iccChurches.ts`, la carte se mettra automatiquement à jour lors du rechargement de la page.

## Structure des données

```typescript
interface Church {
  name: string;           // Nom de l'église (obligatoire)
  city: string;           // Ville (obligatoire)
  country: string;        // Pays (obligatoire)
  coordinates: [number, number]; // [longitude, latitude] (obligatoire)
  members?: number;       // Nombre de membres (optionnel)
  address?: string;       // Adresse complète (optionnel)
  website?: string;       // Site web (optionnel)
  email?: string;         // Email (optionnel)
  phone?: string;         // Téléphone (optionnel)
}
```

## Conseils

- Regroupez les églises par pays pour faciliter la maintenance
- Utilisez des commentaires pour séparer les régions
- Vérifiez que les coordonnées sont correctes en testant sur la carte
- Le nombre de membres influence la taille des bulles sur la carte
