# Performance Optimization Guide

## Vue d'ensemble

Ce guide documente toutes les optimisations de performance implémentées dans le projet AIG-App, incluant la détection des requêtes N+1, le caching avec Redis, et les meilleures pratiques.

---

## Table des Matières

1. [N+1 Query Detection](#n1-query-detection)
2. [Eager Loading](#eager-loading)
3. [Redis Configuration](#redis-configuration)
4. [Caching Strategies](#caching-strategies)
5. [Database Optimization](#database-optimization)
6. [Performance Monitoring](#performance-monitoring)
7. [Best Practices](#best-practices)

---

## 1. N+1 Query Detection

### Packages Installés

- **Laravel Debugbar** (v3.16) - Toolbar de debugging avec query tracking
- **Laravel Query Detector** (v2.1) - Détection automatique des N+1

### Configuration

#### Laravel Debugbar

Activé uniquement en développement :

```env
DEBUGBAR_ENABLED=true  # false en production
```

Accès : La barre de debug apparaît en bas de chaque page en mode développement.

#### Laravel Query Detector

Configure le seuil d'alerte :

```env
QUERY_DETECTOR_ENABLED=true  # false en production
QUERY_DETECTOR_THRESHOLD=1  # Alert si > 1 query par relation
```

### Utilisation

#### Via Debugbar

1. Accédez à n'importe quelle page de l'application
2. Cliquez sur l'onglet "Queries" dans la barre de debug
3. Les queries N+1 sont surlignées en rouge
4. Cliquez pour voir les détails et le stack trace

#### Via Query Detector

Le package affiche automatiquement des warnings dans les logs :

```
[QueryDetector] N+1 Query Detected
- Model: App\Models\User
- Relation: posts
- Queries: 15
- Location: UserController.php:42
```

#### Via Command Line

Une commande personnalisée pour analyser les performances :

```bash
# Analyse complète
php artisan analyze:performance

# Analyser les queries seulement
php artisan analyze:performance --queries

# Analyser le cache
php artisan analyze:performance --cache

# Analyser la mémoire
php artisan analyze:performance --memory
```

Output exemple :

```
🔍 Analyzing application performance...

📁 Analyzing Models for N+1 Risks:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ User.php (5 relations)
⚠️ Event.php (8 relations)
  ⚡ Consider adding eager loading: participants, organizer, location
✅ Book.php (3 relations)

🗄️ Query Performance Analysis:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total queries executed: 3
Total execution time: 45.67ms

💾 Cache Configuration:
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Current driver: redis
✅ Using Redis (Optimal for production)
```

---

## 2. Eager Loading

### Trait HasEagerLoading

Un trait personnalisé pour faciliter l'eager loading :

```php
use App\Traits\HasEagerLoading;

class Event extends Model
{
    use HasEagerLoading;

    // Toujours eager load ces relations
    protected $with = ['organizer', 'location'];
}
```

#### Fonctionnalités du Trait

**1. Auto Eager Loading**

```php
// Ces relations sont TOUJOURS chargées
protected $with = ['organizer', 'location'];

// Récupérer sans ces relations
Event::withoutGlobalScope('eagerLoad')->get();
```

**2. Conditional Eager Loading**

```php
// Charger uniquement si pas déjà chargé
$event->loadIfNotLoaded('participants');
$event->loadIfNotLoaded(['participants', 'comments']);
```

**3. Get Eager Loadable Relations**

```php
// Liste toutes les relations disponibles
$relations = $event->getEagerLoadableRelations();
// ['organizer', 'location', 'participants', 'comments', ...]
```

**4. Scopes Pratiques**

```php
// Charger des relations conditionnellement
Event::withRelations(['participants', 'comments'])->get();

// Compter sans charger
Event::withCount('participants')->get();

// Vérifier l'existence
Event::withExists('participants')->get();
```

### Exemples d'Optimisation

#### ❌ Mauvais (N+1)

```php
$events = Event::all();

foreach ($events as $event) {
    echo $event->organizer->name;  // 1 query par événement
    foreach ($event->participants as $participant) {  // N queries
        echo $participant->name;
    }
}
// Total: 1 + N + N*M queries
```

#### ✅ Bon (Eager Loading)

```php
$events = Event::with(['organizer', 'participants'])->get();

foreach ($events as $event) {
    echo $event->organizer->name;  // Déjà chargé
    foreach ($event->participants as $participant) {  // Déjà chargé
        echo $participant->name;
    }
}
// Total: 3 queries (events, organizers, participants)
```

#### ✅ Excellent (Trait + Protected $with)

```php
// Dans le modèle Event.php
class Event extends Model
{
    use HasEagerLoading;

    protected $with = ['organizer', 'participants'];
}

// Dans le contrôleur
$events = Event::all();  // Relations automatiquement chargées

foreach ($events as $event) {
    echo $event->organizer->name;  // Pas de query supplémentaire
    foreach ($event->participants as $participant) {
        echo $participant->name;  // Pas de query supplémentaire
    }
}
// Total: 3 queries automatiquement
```

### Nested Eager Loading

```php
// Charger les relations imbriquées
Event::with([
    'participants' => function ($query) {
        $query->where('status', 'confirmed');
    },
    'participants.user',
    'participants.user.profile',
    'organizer.company',
])->get();
```

### Lazy Eager Loading

```php
// Charger après la récupération
$events = Event::all();
$events->load('participants', 'comments');

// Conditionnel
if ($needsDetails) {
    $events->load('detailedInfo');
}
```

---

## 3. Redis Configuration

### Installation de Redis

#### Ubuntu/Debian

```bash
sudo apt update
sudo apt install redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Vérifier
redis-cli ping
# PONG
```

#### macOS

```bash
brew install redis
brew services start redis

# Vérifier
redis-cli ping
# PONG
```

#### Docker

```yaml
# docker-compose.yml
version: '3.8'
services:
  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    command: redis-server --appendonly yes

volumes:
  redis_data:
```

### Extension PHP Redis

L'extension **phpredis** est recommandée pour de meilleures performances :

```bash
# Ubuntu/Debian
sudo apt install php8.2-redis

# Via PECL
pecl install redis

# Vérifier
php -m | grep redis
```

### Configuration Laravel

Le projet est déjà configuré pour utiliser Redis :

```env
# .env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis  # Meilleure performance
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Bases de données séparées
REDIS_DB=0  # Default
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_QUEUE_DB=3

# Préfixe pour éviter les collisions
REDIS_PREFIX=aig_app_
```

### Databases Redis Séparées

Le projet utilise 4 bases Redis séparées pour une meilleure organisation :

| Database | Usage | Description |
|----------|-------|-------------|
| 0 | Default | Général |
| 1 | Cache | Cache applicatif |
| 2 | Session | Sessions utilisateurs |
| 3 | Queue | Jobs en queue |

**Avantages :**
- ✅ Isolation des données
- ✅ Meilleure gestion de la mémoire
- ✅ Flush sélectif (ex: `redis-cli -n 1 FLUSHDB` pour cache uniquement)

### Commandes Redis Utiles

```bash
# Se connecter
redis-cli

# Sélectionner une database
SELECT 1

# Voir toutes les clés
KEYS *

# Voir une valeur
GET aig_app_cache:users:1

# Supprimer une clé
DEL aig_app_cache:users:1

# Vider une database
FLUSHDB

# Vider TOUTES les databases (⚠️ ATTENTION)
FLUSHALL

# Voir les infos
INFO

# Monitorer en temps réel
MONITOR
```

---

## 4. Caching Strategies

### Types de Cache

#### 1. Query Results Caching

```php
use Illuminate\Support\Facades\Cache;

// Cache pour 1 heure
$users = Cache::remember('users.active', 3600, function () {
    return User::where('status', 'active')->get();
});

// Cache permanent (jusqu'à invalidation)
$settings = Cache::rememberForever('app.settings', function () {
    return Setting::all()->pluck('value', 'key');
});

// Invalider
Cache::forget('users.active');

// Invalider avec tags (Redis/Memcached seulement)
Cache::tags(['users', 'active'])->flush();
```

#### 2. Model Caching

```php
// Dans le modèle User.php
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class User extends Model
{
    protected static function booted()
    {
        // Invalider le cache à la sauvegarde
        static::saved(function ($user) {
            Cache::forget("user.{$user->id}");
            Cache::tags(['users'])->flush();
        });

        // Invalider à la suppression
        static::deleted(function ($user) {
            Cache::forget("user.{$user->id}");
            Cache::tags(['users'])->flush();
        });
    }

    // Méthode pour récupérer avec cache
    public static function findCached($id)
    {
        return Cache::remember("user.{$id}", 3600, function () use ($id) {
            return static::find($id);
        });
    }
}
```

#### 3. Response Caching

```php
// Dans le contrôleur
use Illuminate\Support\Facades\Cache;

public function index()
{
    return Cache::remember('events.index.page', 600, function () {
        $events = Event::with(['organizer', 'location'])
            ->paginate(20);

        return Inertia::render('Events/Index', [
            'events' => $events,
        ]);
    });
}

// Invalider quand un événement change
Event::saved(function () {
    Cache::forget('events.index.page');
});
```

#### 4. View/Fragment Caching

```blade
{{-- Dans une vue Blade --}}
@cache('sidebar.menu', 3600)
    <ul>
        @foreach(MenuItem::all() as $item)
            <li>{{ $item->name }}</li>
        @endforeach
    </ul>
@endcache
```

### Cache Tags

```php
// Grouper les caches
Cache::tags(['users', 'posts'])->put('user.1', $user, 3600);
Cache::tags(['users'])->put('user.2', $user2, 3600);

// Invalider tous les caches avec un tag
Cache::tags(['users'])->flush();  // Supprime user.1 et user.2
Cache::tags(['posts'])->flush();  // Supprime seulement user.1
```

### Cache Lock (Prévenir les Race Conditions)

```php
use Illuminate\Support\Facades\Cache;

$lock = Cache::lock('expensive_operation', 10);  // 10 secondes

if ($lock->get()) {
    try {
        // Opération coûteuse
        $result = expensiveCalculation();
        Cache::put('result', $result, 3600);
    } finally {
        $lock->release();
    }
} else {
    // Quelqu'un d'autre fait déjà le calcul
    sleep(1);
    return Cache::get('result');
}
```

---

## 5. Database Optimization

### Indexes

#### Ajouter des Indexes

```php
// Dans une migration
Schema::table('events', function (Blueprint $table) {
    // Index simple
    $table->index('status');

    // Index composite
    $table->index(['user_id', 'status']);

    // Index unique
    $table->unique('slug');

    // Index fulltext (MySQL 5.7+)
    $table->fulltext(['title', 'description']);
});
```

#### Quand Ajouter des Indexes ?

✅ **OUI :**
- Colonnes dans WHERE clauses
- Colonnes dans JOIN conditions
- Colonnes dans ORDER BY
- Foreign keys
- Colonnes fréquemment recherchées

❌ **NON :**
- Petites tables (< 1000 lignes)
- Colonnes avec peu de valeurs uniques
- Colonnes rarement utilisées dans les requêtes

### Query Optimization

#### Sélection Spécifique

```php
// ❌ Mauvais
$users = User::all();  // SELECT * FROM users

// ✅ Bon
$users = User::select('id', 'name', 'email')->get();
```

#### Chunking pour Grandes Données

```php
// ❌ Mauvais (charge tout en mémoire)
User::all()->each(function ($user) {
    processUser($user);
});

// ✅ Bon (traite par lots)
User::chunk(100, function ($users) {
    foreach ($users as $user) {
        processUser($user);
    }
});

// ✅ Encore mieux (lazy)
User::lazy()->each(function ($user) {
    processUser($user);
});
```

#### Cursor pour Itération

```php
// Pour très grandes tables
foreach (User::cursor() as $user) {
    processUser($user);
}
```

### Database Connection Pooling

```env
# .env
DB_POOL_MIN=2
DB_POOL_MAX=20
```

---

## 6. Performance Monitoring

### Laravel Telescope

Telescope est déjà configuré et track automatiquement :

- ⏱️ **Requêtes lentes** (> 100ms)
- 🔄 **Requêtes N+1**
- 💾 **Utilisation du cache**
- 🧠 **Consommation mémoire**
- ⚡ **Jobs et queues**

Accès : `http://localhost:8000/telescope`

### Commande d'Analyse

```bash
php artisan analyze:performance
```

Cette commande analyse :
- Modèles avec risques N+1
- Requêtes lentes
- Configuration du cache
- Utilisation mémoire

### Logs de Performance

Les requêtes lentes sont automatiquement loggées :

```php
// config/monitoring.php
'metrics' => [
    'thresholds' => [
        'slow_query' => 100,  // ms
        'slow_request' => 1000,  // ms
        'high_memory' => 128,  // MB
    ],
],
```

Logs dans `storage/logs/monitoring.log` :

```json
{
  "level": "warning",
  "message": "Slow query detected",
  "context": {
    "sql": "SELECT * FROM events WHERE ...",
    "time": 234.56,
    "connection": "mysql"
  }
}
```

### APM Integration

Si vous utilisez New Relic ou Datadog (voir MONITORING.md), vous aurez :

- 📊 Dashboards de performance
- 🎯 Transaction tracing
- 🔍 Query analysis
- 📈 Métriques en temps réel

---

## 7. Best Practices

### DO ✅

1. **Eager Load Relations**
   ```php
   Event::with(['organizer', 'participants'])->get();
   ```

2. **Use Redis for Cache**
   ```env
   CACHE_STORE=redis
   ```

3. **Select Only Needed Columns**
   ```php
   User::select('id', 'name')->get();
   ```

4. **Chunk Large Datasets**
   ```php
   User::chunk(100, function ($users) { });
   ```

5. **Add Indexes to Frequently Queried Columns**
   ```php
   $table->index('email');
   ```

6. **Cache Expensive Operations**
   ```php
   Cache::remember('key', 3600, fn() => expensiveOperation());
   ```

7. **Use Query Scopes**
   ```php
   User::active()->verified()->get();
   ```

8. **Paginate Large Results**
   ```php
   User::paginate(20);
   ```

9. **Use Database Transactions**
   ```php
   DB::transaction(function () { });
   ```

10. **Monitor with Telescope in Development**

### DON'T ❌

1. **Avoid SELECT ***
   ```php
   // ❌ User::all();
   // ✅ User::select('id', 'name')->get();
   ```

2. **Avoid Queries in Loops**
   ```php
   // ❌
   foreach ($events as $event) {
       $event->organizer->name;  // N+1
   }

   // ✅
   Event::with('organizer')->get();
   ```

3. **Avoid count() on Large Tables**
   ```php
   // ❌ User::count();  // Full table scan
   // ✅ Use approximation or cache the count
   ```

4. **Don't Cache Everything**
   - Cache uniquement les données lues fréquemment
   - Pas les données qui changent souvent

5. **Don't Forget Cache Invalidation**
   - Invalider le cache quand les données changent
   - Utiliser cache tags pour grouper

6. **Avoid Heavy Computations in Blade**
   - Faire les calculs dans le contrôleur
   - Passer les résultats à la vue

---

## Performance Checklist

### Development

- [ ] Laravel Debugbar activé
- [ ] Query Detector activé
- [ ] Telescope installé et configuré
- [ ] Logs de performance activés

### Before Production

- [ ] Désactiver Debugbar
- [ ] Désactiver Query Detector
- [ ] Telescope en production sécurisé (gate)
- [ ] Redis installé et configuré
- [ ] Cache configuré sur Redis
- [ ] Sessions sur Redis
- [ ] Queues sur Redis
- [ ] Opcache activé
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan event:cache`
- [ ] `npm run build` (production)

### Monitoring

- [ ] APM configuré (New Relic / Datadog)
- [ ] Alertes sur requêtes lentes
- [ ] Alertes sur haute mémoire
- [ ] Dashboards configurés
- [ ] Logs centralisés

---

## Commandes Utiles

```bash
# Analyse de performance
php artisan analyze:performance

# Clear tous les caches
php artisan optimize:clear

# Cache les configs (production)
php artisan optimize

# Voir les queries lentes
php artisan telescope:prune

# Redis CLI
redis-cli
> SELECT 1
> KEYS *
> FLUSHDB

# Monitorer Redis
redis-cli MONITOR

# Stats Redis
redis-cli INFO
```

---

## Ressources

### Documentation
- Laravel Performance: https://laravel.com/docs/performance
- Laravel Telescope: https://laravel.com/docs/telescope
- Laravel Caching: https://laravel.com/docs/cache
- Redis: https://redis.io/docs/

### Packages
- Laravel Debugbar: https://github.com/barryvdh/laravel-debugbar
- Query Detector: https://github.com/beyondcode/laravel-query-detector

### Tools
- Laravel Telescope
- Redis Commander
- PHPRedis Admin

---

**Dernière mise à jour :** 2025-10-11
**Version :** 1.0.0
