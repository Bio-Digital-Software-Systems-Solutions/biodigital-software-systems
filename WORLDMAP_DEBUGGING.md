# Guide de Débogage - Carte Mondiale ICC

## 🔍 Problème : Les bulles ne sont pas visibles

### ✅ Solutions implémentées

1. **Augmentation de la taille des bulles**
   - Taille minimale : 4px → **6px**
   - Taille maximale : 20px → **25px**
   - Fichier: `WorldMap.tsx` ligne ~56-58

2. **Ajout d'anneaux extérieurs**
   - Chaque église a maintenant un cercle + un anneau
   - L'anneau pulse pour attirer l'attention
   - Meilleure visibilité même avec zoom arrière

3. **Amélioration du contraste**
   - Couleur bulle : `#3b82f6` (bleu vif)
   - Contour : `#ffffff` (blanc) au lieu de bleu foncé
   - Opacité : 0.9 (au lieu de 0.7)
   - Drop-shadow pour effet 3D

4. **Ordre des éléments SVG**
   - Les bulles sont maintenant dans un groupe séparé
   - Ajoutées APRÈS la carte pour être au-dessus
   - Fichier: `WorldMap.tsx` ligne ~72

5. **Animation améliorée**
   - Effet "elastic" pour l'apparition
   - Animation de pulsation sur les bulles ET les anneaux
   - Durée: 1200ms avec délai en cascade

## 🧪 Comment vérifier que les bulles s'affichent

### Méthode 1 : Console du navigateur
1. Ouvrir la page (http://localhost:5174/)
2. Appuyer sur F12 pour ouvrir DevTools
3. Aller dans l'onglet "Console"
4. Rechercher des erreurs D3.js ou TopoJSON
5. Vérifier qu'il n'y a pas d'erreurs de projection

### Méthode 2 : Inspection du DOM
1. F12 → Onglet "Elements" (ou "Inspect")
2. Chercher l'élément `<svg>`
3. Vérifier la présence de :
   ```html
   <g class="bubbles">
     <g class="church-group" transform="translate(x,y)">
       <circle class="church-ring" ...>
       <circle class="church" ...>
     </g>
   </g>
   ```
4. Vérifier que les attributs `r` ne sont pas à 0
5. Vérifier que `transform` a des coordonnées valides

### Méthode 3 : Test de comptage
1. Ouvrir la console (F12)
2. Taper :
   ```javascript
   document.querySelectorAll('.church').length
   ```
3. Devrait retourner **117** (nombre d'églises)

### Méthode 4 : Test visuel zoom
1. Zoomer sur la carte (molette souris)
2. Les bulles devraient devenir TRÈS visibles au zoom x2 ou x3
3. Si elles n'apparaissent toujours pas, il y a un problème

## 🐛 Problèmes possibles et solutions

### Problème 1 : Les bulles sont à (0,0)
**Cause**: Mauvaise projection ou coordonnées invalides
**Solution**: Vérifier dans `iccChurchesComplete.ts` que:
- Les coordonnées sont `[longitude, latitude]` (pas l'inverse!)
- Longitude entre -180 et 180
- Latitude entre -90 et 90

### Problème 2 : Les bulles sont masquées
**Cause**: Ordre des éléments SVG incorrect
**Solution**: Vérifier que bubbleGroup est créé APRÈS g (la carte)
```typescript
// ❌ MAUVAIS
const bubbleGroup = svg.append('g');  // avant la carte
// ✅ BON
const bubbleGroup = svg.append('g');  // après la carte dans le code
```

### Problème 3 : Animation ne se lance pas
**Cause**: Erreur JavaScript bloquante
**Solution**:
1. Vérifier la console pour les erreurs
2. Vérifier que D3.js est bien chargé
3. Tester avec `console.log(iccChurches.length)`

### Problème 4 : Couleur des bulles invisible
**Cause**: Couleur trop proche du fond
**Solution**: Dans `WorldMap.tsx`, changer les couleurs:
```typescript
.attr('fill', '#FF0000')  // Rouge vif pour test
.attr('stroke', '#000000') // Noir pour contraste
```

## 🔧 Tests de débogage rapides

### Test 1 : Forcer une grosse bulle visible
Dans `WorldMap.tsx`, ligne ~143, remplacer temporairement :
```typescript
// Test: Forcer toutes les bulles à 50px
bubbles.transition()
  .duration(1200)
  .attr('r', 50) // au lieu de sizeScale(...)
```

### Test 2 : Couleur flashy
Ligne ~98, temporairement :
```typescript
.attr('fill', '#FF0000') // Rouge vif
.attr('fill-opacity', 1) // Opacité complète
.attr('stroke', '#FFFF00') // Jaune
.attr('stroke-width', 5) // Contour épais
```

### Test 3 : Désactiver l'animation
Ligne ~143, temporairement :
```typescript
// Pas d'animation, bulles immédiatement visibles
bubbles.attr('r', d => sizeScale(d.members || 100));
// Commenter la transition
```

### Test 4 : Logger les coordonnées
Ligne ~79, ajouter :
```typescript
.attr('transform', d => {
  const coords = projection(d.coordinates);
  console.log(d.name, coords); // Voir dans console
  return `translate(${coords?.[0] || 0},${coords?.[1] || 0})`;
})
```

## 📊 Valeurs attendues

### Coordonnées projetées (exemples)
- Paris (2.35, 48.86) → environ (620, 140)
- New York (-74.01, 40.71) → environ (220, 180)
- Sydney (151.21, -33.87) → environ (1050, 520)

### Tailles de bulles
- Petite église (100 membres) → ~6px
- Moyenne église (500 membres) → ~15px
- Grande église (2000 membres) → ~25px

## 💡 Astuces de débogage

1. **Utiliser les DevTools de D3**
   ```javascript
   // Dans la console
   d3.selectAll('.church')
     .style('fill', 'red')  // Rendre toutes les bulles rouges
     .attr('r', 30);        // Augmenter la taille
   ```

2. **Vérifier le viewport SVG**
   ```javascript
   // Vérifier que le SVG est visible
   const svg = document.querySelector('svg');
   console.log(svg.getBoundingClientRect());
   ```

3. **Compter les éléments**
   ```javascript
   console.log('Églises:', document.querySelectorAll('.church').length);
   console.log('Anneaux:', document.querySelectorAll('.church-ring').length);
   console.log('Groupes:', document.querySelectorAll('.church-group').length);
   ```

## 🎯 Checklist de vérification

- [ ] Le serveur dev tourne (http://localhost:5174/)
- [ ] Pas d'erreurs dans la console navigateur
- [ ] Le SVG de la carte est visible
- [ ] 117 éléments `.church` dans le DOM
- [ ] Les coordonnées `transform` ne sont pas (0,0)
- [ ] L'attribut `r` des circles n'est pas 0
- [ ] Les couleurs sont bien définies
- [ ] Le z-index/ordre SVG est correct

## 📞 Support

Si les bulles ne s'affichent toujours pas :
1. Faire un screenshot du réseau (F12 → Network) montrant le chargement de D3.js
2. Copier-coller les erreurs de la console
3. Exporter le HTML de l'élément SVG (clic droit → Copy → Copy outerHTML)
