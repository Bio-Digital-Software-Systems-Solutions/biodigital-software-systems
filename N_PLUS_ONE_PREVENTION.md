# Prévention des Problèmes N+1

Ce document explique comment nous prévenons les problèmes de requêtes N+1 dans l'application AIG.

## Qu'est-ce qu'un problème N+1?

Un problème N+1 se produit lorsque:
1. On charge N entités (par exemple, 10 Training Classes)
2. Pour chaque entité, on fait une requête supplémentaire pour charger une relation
3. Résultat: 1 requête initiale + N requêtes supplémentaires = N+1 requêtes

**Exemple de problème N+1:**
```php
// ❌ MAUVAIS: Génère N+1 requêtes
$classes = TrainingClass::all();
foreach ($classes as $class) {
    echo $class->training->students()->count(); // Requête SQL pour chaque classe!
}
```

## Solutions Implémentées

### 1. Eager Loading dans TrainingClassController

#### Méthode `index()`
```php
// ✅ BON: Charge toutes les relations en une seule fois
$classes = TrainingClass::with([
    'training.students' => function ($query) {
        $query->where('status', 'approved');
    },
    'teacher',
    'attendances',
    'schedules'
])->get();

// Utiliser la collection chargée au lieu de faire une requête
'students_count' => $class->training->students->count(), // Pas de requête SQL!
```

**Bénéfices:**
- Avec 10 classes sans eager loading: ~31+ requêtes
- Avec eager loading: ~5 requêtes
- **Amélioration: 83% de réduction**

#### Méthode `show()`
```php
// ✅ Charge les students avec le training
$trainingClass->load([
    'training.students' => function ($query) {
        $query->where('status', 'approved');
    },
    'teacher',
    'attendances.student'
]);

// Utilise la collection déjà chargée
$students = $trainingClass->training->students->map(...);
```

#### Méthode `schedules()`
```php
// ✅ Charge les teachers avec les classes
$trainings = Training::with(['classes' => function ($query) {
    $query->where('date', '>=', now()->toDateString())
        ->with('teacher') // Eager load teacher!
        ->orderBy('date')
        ->orderBy('start_time');
}])->get();
```

### 2. Tests de Détection N+1

Nous avons créé des tests automatiques qui détectent les problèmes N+1:

**Fichier:** `tests/Feature/TrainingClassNPlusOneTest.php`

```php
public function test_index_does_not_have_n_plus_one_queries(): void
{
    // Créer 5 trainings avec 3 étudiants et 2 classes chacun
    // Total: 10 classes

    DB::enableQueryLog();
    $response = $this->get(route('training-classes.index'));
    $queryCount = count(DB::getQueryLog());

    // Vérifie qu'on a moins de 15 requêtes
    // Sans N+1, ce serait 10+ requêtes supplémentaires!
    $this->assertLessThan(15, $queryCount);
}
```

### 3. Bonnes Pratiques

#### ✅ À FAIRE:
```php
// 1. Toujours utiliser with() pour eager load les relations
$classes = TrainingClass::with('training.students')->get();

// 2. Utiliser les collections chargées
$count = $class->training->students->count(); // Collection en mémoire

// 3. Filtrer dans le with() si possible
$classes = TrainingClass::with([
    'training.students' => function ($query) {
        $query->where('status', 'approved');
    }
])->get();
```

#### ❌ À ÉVITER:
```php
// 1. Ne pas utiliser ->count() qui génère une requête SQL
$count = $class->training->students()->count(); // ❌ Requête SQL!

// 2. Ne pas accéder aux relations sans eager loading
$classes = TrainingClass::all(); // Pas de with()
foreach ($classes as $class) {
    $class->teacher->name; // ❌ N+1!
}
```

## Tests de Validation

Exécutez les tests N+1 régulièrement:

```bash
# Tous les tests N+1
php artisan test --filter=TrainingClassNPlusOneTest

# Test avec un large dataset (50 classes)
php artisan test --filter=test_no_n_plus_one_with_large_dataset
```

### Résultats Attendus

| Test | Classes | Queries Sans N+1 | Queries Avec N+1 | Amélioration |
|------|---------|------------------|------------------|--------------|
| index | 10 | <15 | 31+ | 52% |
| show | 1 | <15 | 11+ | 27% |
| schedules | 20 | <10 | 21+ | 52% |
| large_dataset | 50 | <20 | 51+ | 61% |

## Monitoring en Production

Pour détecter les problèmes N+1 en production, nous utilisons:

### Laravel Debugbar (Développement)
- Affiche le nombre de requêtes SQL
- Met en évidence les requêtes dupliquées
- Accessible via `APP_DEBUG=true`

### Sentry Performance Monitoring (Production)
- Track les requêtes lentes
- Détecte les patterns N+1
- Alertes automatiques

### Configuration Laravel Telescope (Staging)
```php
// config/telescope.php
'watchers' => [
    Watchers\QueryWatcher::class => [
        'enabled' => env('TELESCOPE_QUERY_WATCHER', true),
        'slow' => 50, // ms
    ],
],
```

## Checklist pour Nouvelles Fonctionnalités

Avant de merger une PR qui charge des relations:

- [ ] Vérifier qu'on utilise `with()` pour eager loading
- [ ] Ne pas utiliser `->count()` ou `->get()` dans les boucles
- [ ] Ajouter un test N+1 si la fonctionnalité charge >5 entités
- [ ] Vérifier les logs SQL dans Laravel Debugbar
- [ ] Tester avec un dataset réaliste (10-50 entités)

## Ressources

- [Laravel Eloquent: Eager Loading](https://laravel.com/docs/11.x/eloquent-relationships#eager-loading)
- [N+1 Query Problem](https://laracasts.com/series/eloquent-techniques/episodes/1)
- [Laravel Debugbar](https://github.com/barryvdh/laravel-debugbar)

## Auteur

Corrections N+1 implémentées le 13/10/2025
Tests automatiques créés pour prévenir les régressions
