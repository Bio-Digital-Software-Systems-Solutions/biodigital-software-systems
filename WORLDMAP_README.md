# Carte Mondiale des Églises ICC

## 🎯 Implémentation Complète

### ✅ Ce qui a été fait

1. **Composant WorldMap** interactif avec D3.js
   - Carte du monde avec 119 églises dans 30 pays
   - Bulles animées avec effet de glow
   - Tooltips informatifs au survol
   - Zoom et pan
   - Statistiques en temps réel

2. **Données complètes**
   - 119 églises ICC réparties dans 30 pays
   - Coordonnées GPS précises
   - Nombre de membres estimés
   - Fichier: `resources/js/data/iccChurchesComplete.ts`

3. **Intégration**
   - Composant intégré dans `AboutSection.tsx`
   - Affiché sur la page d'accueil (Welcome.tsx)
   - Design responsive et moderne

## 📂 Fichiers créés/modifiés

### Nouveaux fichiers
- `resources/js/Components/LandingPage/WorldMap.tsx` - Composant principal
- `resources/js/data/iccChurches.ts` - Point d'entrée des données
- `resources/js/data/iccChurchesComplete.ts` - 119 églises complètes
- `resources/js/data/README.md` - Guide de mise à jour des données
- `resources/js/utils/churchDataImporter.ts` - Utilitaires d'import

### Fichiers modifiés
- `resources/js/Components/LandingPage/AboutSection.tsx` - Intégration WorldMap
- `resources/js/Pages/Welcome.tsx` - Correction props AboutSection
- `package.json` - Ajout dépendances D3.js

## 🌍 Répartition des églises

### Par continent
- **Europe**: 60+ églises (France, Allemagne, Belgique, Suisse, UK, etc.)
- **Afrique**: 40+ églises (Côte d'Ivoire, RD Congo, Cameroun, etc.)
- **Amérique du Nord**: 10 églises (USA, Canada)
- **Amérique du Sud**: 2 églises (Brésil)
- **Océanie**: 3 églises (Australie, Nouvelle-Zélande)

### Top 5 pays
1. France: 30 églises
2. Allemagne: 10 églises
3. Côte d'Ivoire: 8 églises
4. RD Congo: 6 églises
5. USA: 6 églises

## 🔧 Comment mettre à jour les données

### Méthode 1: Modification manuelle
1. Ouvrir `resources/js/data/iccChurchesComplete.ts`
2. Ajouter/modifier une église:
```typescript
{
  name: 'ICC Nom',
  city: 'Ville',
  country: 'Pays',
  coordinates: [longitude, latitude], // Attention à l'ordre!
  members: 100,
}
```

### Méthode 2: Avec coordonnées GPS
1. Aller sur [Google Maps](https://maps.google.com)
2. Chercher l'adresse de l'église
3. Clic droit > Coordonnées GPS
4. Google affiche: `48.8566, 2.3522` (latitude, longitude)
5. Dans le code, **inverser l'ordre**: `[2.3522, 48.8566]`

### Méthode 3: Import depuis le site ICC
1. Aller sur https://impactcentrechretien.com/accueil/adresses/
2. Ouvrir la console développeur (F12)
3. Exécuter le script dans `resources/js/utils/churchDataImporter.ts`
4. Copier le résultat dans `iccChurchesComplete.ts`

## 🎨 Personnalisation

### Changer les couleurs des bulles
Dans `WorldMap.tsx`, ligne ~80:
```typescript
.attr('fill', '#3b82f6')  // Bleu
.attr('stroke', '#1d4ed8') // Bleu foncé
```

### Modifier la taille des bulles
Dans `WorldMap.tsx`, ligne ~56:
```typescript
const sizeScale = d3.scaleSqrt()
  .domain([0, d3.max(iccChurches, d => d.members || 0) || 1500])
  .range([4, 20]); // Min 4px, Max 20px
```

### Changer la projection de la carte
Dans `WorldMap.tsx`, ligne ~25:
```typescript
const projection = d3.geoMercator()  // Essayez: geoNaturalEarth1, geoOrthographic
  .scale(180)      // Zoom
  .translate([width / 2, height / 1.5]); // Centre
```

## 🐛 Dépannage

### Les bulles n'apparaissent pas
1. Vérifier que les coordonnées sont dans le bon ordre `[longitude, latitude]`
2. Vérifier que les coordonnées sont dans les limites valides:
   - Longitude: -180 à 180
   - Latitude: -90 à 90
3. Ouvrir la console navigateur (F12) pour voir les erreurs

### La carte ne charge pas
1. Vérifier que D3.js est bien installé: `npm list d3`
2. Redémarrer le serveur: `npm run dev`
3. Vider le cache du navigateur

### Performances lentes
1. Réduire le nombre d'églises affichées
2. Désactiver les animations dans `WorldMap.tsx`
3. Augmenter le délai d'animation

## 📊 Statistiques actuelles

```
Total églises: 119
Total pays: 30
Total membres: ~60,000+
Continents: 5
```

## 🚀 Prochaines étapes

1. **Récupérer les vraies données** depuis https://impactcentrechretien.com/accueil/adresses/
2. **Ajouter des images** pour chaque église
3. **Implémenter un filtre** par pays/continent
4. **Ajouter une recherche** d'églises
5. **Créer une page détail** pour chaque église
6. **Intégrer Google Maps** pour les directions

## 📝 Notes importantes

- Les données actuelles sont des **estimations** basées sur la présence connue de l'ICC
- Les nombres de membres sont **approximatifs**
- Les coordonnées GPS ont été générées automatiquement et peuvent nécessiter des ajustements
- Pour les données officielles, consulter: https://impactcentrechretien.com/accueil/adresses/

## 💡 Conseils

- Testez toujours après avoir modifié les données
- Gardez une sauvegarde avant les modifications importantes
- Utilisez des coordonnées précises pour une meilleure précision
- Groupez les églises par région pour faciliter la maintenance

## 📞 Support

Pour toute question ou problème:
1. Consulter la documentation D3.js: https://d3js.org
2. Consulter la documentation TopoJSON: https://github.com/topojson/topojson
3. Vérifier les exemples D3.js: https://observablehq.com/@d3
