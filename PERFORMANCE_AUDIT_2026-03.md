# Audit de Performance Frontend - Mars 2026

## Contexte

Audit effectue le 11 mars 2026 suite a un LCP (Largest Contentful Paint) mesure a **8,65 secondes** via Chrome DevTools.

### Metriques mesurees

| Metrique | Valeur | Seuil acceptable | Statut |
|----------|--------|-------------------|--------|
| LCP (Largest Contentful Paint) | **8,65s** | < 2,5s | MAUVAIS |
| CLS (Cumulative Layout Shift) | 0.00 | < 0,1 | BON |
| INP (Interaction to Next Paint) | 104 ms | < 200ms | BON |

---

## Problemes identifies

### 1. CRITIQUE : Images hero non optimisees (~27 MB)

**Fichiers concernes :** `public/*.png` (images du carousel HeroCarousel)

Le carousel de la page d'accueil charge 9 images PNG totalisant ~27 MB :

| Fichier | Taille |
|---------|--------|
| `20.png` | 3,9 MB |
| `1.png` | 3,5 MB |
| `17.png` | 2,9 MB |
| `11.png` | 2,7 MB |
| `3.png` | 2,4 MB |
| `15.png` | 2,3 MB |
| `4.png` | 2,2 MB |
| `5.png` | 1,7 MB |
| `2.png` | 1,3 MB |

Autres images lourdes :
- `pc.png` : **20 MB**
- `Berger1.png` : 3,0 MB
- `pp.png` : 3,3 MB

**Impact :** Toutes les images sont chargees en meme temps, meme si le carousel n'affiche qu'une seule slide. C'est la **cause principale du LCP a 8,65s**.

**Fichiers source :**
- `resources/js/Pages/Welcome.tsx` (lignes 48-133)
- `resources/js/Components/HeroCarousel.tsx` (lignes 61-118)

---

### 2. CRITIQUE : Pas de code splitting dans Vite

**Fichier concerne :** `vite.config.js`

La configuration Vite n'a aucune optimisation de chunking. Tout le JavaScript (Recharts, D3, Video.js, Uppy, XYFlow, DnD-kit, TipTap...) est bundle dans un seul fichier. Meme les pages qui n'utilisent pas ces librairies les telechargent.

---

### 3. HAUTE : Librairies lourdes non lazy-loadees

| Librairie | Taille estimee | Utilisee dans |
|-----------|---------------|---------------|
| `recharts` | ~600 KB | BurndownChart uniquement |
| `d3` | ~250 KB | BurndownChart, WorldMap |
| `video.js` | ~200 KB | VideoJSPlayer uniquement |
| `@uppy/*` | ~400 KB combinee | UppyFileUploader uniquement |
| `@xyflow/react` | ~150 KB | WorkflowCanvas uniquement |

Seuls 2 composants utilisent `React.lazy()` actuellement :
- `LazyVideoPlayer` (utilise 0 fois)
- `LazyRichTextEditor` (utilise 6 fois)

**Fichier source :** `resources/js/Components/LazyComponents.tsx`

---

### 4. HAUTE : Dashboard - requetes N+1 et pas de cache

**Fichier concerne :** `app/Http/Controllers/DashboardController.php`

Le controleur execute ~15 requetes SQL a chaque chargement sans aucun cache :
- Comptages events, articles, books, messages (x2 pour le mois dernier)
- Activites recentes avec relations
- Quizzes charges deux fois (`getUpcomingQuizzes()` et `getQuizStats()`)
- Requetes dupliquees pour les statistiques mensuelles

---

### 5. MOYENNE : Permissions chargees sur chaque requete

**Fichier concerne :** `app/Http/Middleware/HandleInertiaRequests.php`

`getAllPermissions()` est appele a chaque navigation Inertia, meme quand la page n'en a pas besoin :

```php
'roles' => $request->user()->roles?->pluck('name') ?? [],
'permissions' => $request->user()->getAllPermissions()?->pluck('name') ?? [],
```

---

### 6. MOYENNE : Pas de lazy loading below-the-fold

**Fichier concerne :** `resources/js/Pages/Welcome.tsx`

La page Welcome rend tous les composants d'un coup sans `Suspense` ni lazy loading :
- HeroCarousel (26 MB d'images)
- AboutSection
- OurValues
- FeaturesSection
- TrainingBrowseSection
- ContactSection
- Footer

---

### 7. MOYENNE : DashboardLayout charge 33+ icones eagerly

**Fichier concerne :** `resources/js/Layouts/DashboardLayout.tsx`

Toutes les Heroicons sont importees au top du layout (lignes 6-34), y compris celles qui dependent des permissions et ne seront jamais affichees pour certains utilisateurs.

---

## Plan de remediation

### Priorite P0 (Impact immediat)

| Action | Impact attendu | Complexite |
|--------|----------------|------------|
| Convertir les images en WebP et les compresser (cible : < 200 KB/image) | -90% taille images | Faible |
| Lazy-load les slides du carousel (ne charger que la slide visible) | LCP divise par 3-4 | Faible |
| Ajouter du code splitting dans `vite.config.js` (`manualChunks`) | -50% bundle JS initial | Moyenne |

#### Configuration Vite recommandee

```javascript
// vite.config.js
build: {
    rollupOptions: {
        output: {
            manualChunks: {
                'charts': ['recharts', 'd3'],
                'editor': ['@tiptap/react', '@tiptap/starter-kit'],
                'uploader': ['@uppy/core', '@uppy/dashboard', '@uppy/react'],
                'video': ['video.js'],
                'workflow': ['@xyflow/react'],
                'ui': ['@radix-ui/react-dialog', '@radix-ui/react-dropdown-menu'],
            }
        }
    }
}
```

### Priorite P1 (Gain significatif)

| Action | Impact attendu | Complexite |
|--------|----------------|------------|
| `React.lazy()` pour Recharts, D3, Video.js, Uppy, XYFlow | -1 MB+ sur les pages non concernees | Moyenne |
| Cacher les stats du dashboard (5-10 min via Redis) | -50ms+ par requete | Faible |
| Eliminer les requetes dupliquees dans DashboardController | -30% requetes SQL | Faible |

### Priorite P2 (Amelioration progressive)

| Action | Impact attendu | Complexite |
|--------|----------------|------------|
| Utiliser les Deferred Props d'Inertia v2 pour les donnees secondaires | Meilleur temps percu | Moyenne |
| Lazy-load les sections below-the-fold de Welcome (`Suspense`) | FCP plus rapide | Faible |
| Cacher les permissions utilisateur dans la session | -10ms par requete | Faible |
| Ajouter `loading="lazy"` et `srcset` aux images | Chargement progressif | Faible |
| Convertir le logo en SVG | -300 KB | Faible |

---

## Objectifs

| Metrique | Actuel | Cible |
|----------|--------|-------|
| LCP | 8,65s | < 2,5s |
| Bundle JS initial | ~2 MB+ (estime) | < 500 KB |
| Images page accueil | ~27 MB | < 2 MB |

---

**Date :** 11 mars 2026
**Statut :** A corriger
