# Monitoring & Backup Documentation

## Vue d'ensemble

Ce projet intègre trois systèmes de monitoring et de sauvegarde :

1. **Laravel Telescope** - Debugging et monitoring local
2. **APM (New Relic / Datadog)** - Application Performance Monitoring en production
3. **Automated Backups** - Sauvegardes automatiques de la base de données et des fichiers

---

## 1. Laravel Telescope

### Qu'est-ce que c'est ?

Laravel Telescope est un assistant de débogage élégant pour Laravel. Il fournit une vue détaillée des requêtes, commandes, jobs, mails, notifications, cache, et bien plus encore.

### Installation

Telescope est déjà installé en tant que dépendance de développement.

### Configuration

Le fichier de configuration se trouve dans `config/telescope.php`.

### Accéder à Telescope

En développement local, accédez à Telescope via :

```
http://localhost:8000/telescope
```

### Fonctionnalités Principales

#### 📊 Requests
Visualisez toutes les requêtes HTTP avec :
- Méthode et URL
- Durée d'exécution
- Mémoire utilisée
- Réponse HTTP

#### 🗄️ Queries
Analysez les requêtes SQL :
- SQL complet avec bindings
- Temps d'exécution
- Détection des requêtes N+1
- Requêtes lentes

#### 📧 Mails
Visualisez les emails envoyés :
- Destinataires
- Sujet et contenu
- Pièces jointes

#### 🔔 Notifications
Suivez les notifications envoyées :
- Type de notification
- Canal utilisé (mail, SMS, Slack, etc.)
- Destinataire

#### ⚡ Jobs & Queue
Monitorez les jobs en queue :
- Statut (pending, processing, completed, failed)
- Durée d'exécution
- Tentatives

#### 💾 Cache
Analysez les opérations de cache :
- Hits et misses
- Clés stockées
- Durée de vie (TTL)

#### 🐛 Exceptions
Tracez les exceptions :
- Stack trace complet
- Contexte de l'erreur
- Fréquence

### Utilisation en Production

**⚠️ IMPORTANT : Ne jamais activer Telescope en production publiquement !**

Pour utiliser Telescope en production de manière sécurisée :

1. **Restreindre l'accès** dans `app/Providers/TelescopeServiceProvider.php` :

```php
protected function gate()
{
    Gate::define('viewTelescope', function ($user) {
        return in_array($user->email, [
            'admin@example.com',
        ]);
    });
}
```

2. **Désactiver l'enregistrement des données sensibles** :

```php
// config/telescope.php
'ignore_paths' => [
    'nova-api*',
    'login',
    'password*',
],
```

### Commandes Utiles

```bash
# Nettoyer les anciennes données (plus de 24h)
php artisan telescope:prune

# Nettoyer les données plus anciennes que X heures
php artisan telescope:prune --hours=48

# Publier les assets
php artisan telescope:publish
```

---

## 2. Application Performance Monitoring (APM)

### Providers Supportés

Le projet supporte deux providers APM majeurs :
1. **New Relic** - APM complet avec infrastructure monitoring
2. **Datadog** - APM, logs, et infrastructure monitoring

### Configuration

Le fichier de configuration se trouve dans `config/monitoring.php`.

---

### 2.1 New Relic

#### Installation

1. **Installer l'extension PHP New Relic** :

```bash
# Ubuntu/Debian
wget -O - https://download.newrelic.com/548C16BF.gpg | sudo apt-key add -
echo "deb http://apt.newrelic.com/debian/ newrelic non-free" | sudo tee /etc/apt/sources.list.d/newrelic.list
sudo apt-get update
sudo apt-get install newrelic-php5

# Configurer
sudo newrelic-install install
```

2. **Configurer dans `.env`** :

```env
APM_ENABLED=true
NEWRELIC_ENABLED=true
NEWRELIC_APP_NAME="AIG-App Production"
NEWRELIC_LICENSE_KEY=your_license_key_here
NEWRELIC_TRANSACTION_TRACER_ENABLED=true
NEWRELIC_ERROR_COLLECTOR_ENABLED=true
NEWRELIC_DISTRIBUTED_TRACING_ENABLED=true

# Thresholds
NEWRELIC_TRANSACTION_THRESHOLD=0.5
NEWRELIC_SLOW_SQL_THRESHOLD=0.1
```

3. **Redémarrer PHP-FPM** :

```bash
sudo systemctl restart php8.2-fpm
```

#### Fonctionnalités New Relic

##### Application Monitoring
- **Response Time** - Temps de réponse des requêtes
- **Throughput** - Nombre de requêtes par minute
- **Error Rate** - Taux d'erreurs
- **Apdex Score** - Score de satisfaction utilisateur

##### Transaction Traces
- Traces détaillées des transactions lentes
- Breakdown par composant (DB, External, PHP)
- Flame graphs pour l'analyse

##### Database Monitoring
- Requêtes SQL les plus lentes
- Temps d'exécution moyen
- Détection des requêtes N+1

##### Error Analytics
- Groupement des erreurs par type
- Stack traces détaillées
- Fréquence et impact

##### Custom Instrumentation

```php
// Tracker une transaction personnalisée
if (function_exists('newrelic_name_transaction')) {
    newrelic_name_transaction('Custom/ImportUsers');
}

// Ajouter des attributs personnalisés
if (function_exists('newrelic_add_custom_parameter')) {
    newrelic_add_custom_parameter('user_id', $user->id);
    newrelic_add_custom_parameter('tenant', $tenant->name);
}

// Ignorer une transaction
if (function_exists('newrelic_ignore_transaction')) {
    newrelic_ignore_transaction();
}

// Tracer une erreur personnalisée
if (function_exists('newrelic_notice_error')) {
    newrelic_notice_error('Custom error message', $exception);
}
```

#### Dashboard New Relic

Accédez à votre dashboard via :
```
https://one.newrelic.com/
```

---

### 2.2 Datadog

#### Installation

1. **Installer l'extension PHP Datadog** :

```bash
# Via script d'installation automatique
DD_AGENT_MAJOR_VERSION=7 DD_API_KEY=your_api_key DD_SITE="datadoghq.com" bash -c "$(curl -L https://s3.amazonaws.com/dd-agent/scripts/install_script.sh)"

# Installer l'extension PHP
wget https://github.com/DataDog/dd-trace-php/releases/latest/download/datadog-setup.php
php datadog-setup.php --php-bin=all
```

2. **Configurer dans `.env`** :

```env
APM_ENABLED=true
DATADOG_ENABLED=true
DATADOG_APP_NAME=aig-app
DATADOG_API_KEY=your_api_key_here
DATADOG_AGENT_HOST=localhost
DATADOG_AGENT_PORT=8126
DATADOG_ENV=production
DATADOG_VERSION=1.0.0
DATADOG_SERVICE=aig-app-backend
DATADOG_DISTRIBUTED_TRACING=true
DATADOG_SAMPLING_RATE=1.0
```

3. **Redémarrer PHP-FPM** :

```bash
sudo systemctl restart php8.2-fpm
```

#### Fonctionnalités Datadog

##### APM (Application Performance Monitoring)
- **Traces** - Traces distribuées de bout en bout
- **Services Map** - Carte des dépendances entre services
- **Resource Monitoring** - Performance par endpoint
- **Error Tracking** - Suivi des erreurs avec contexte

##### Logs Management
- Collecte et agrégation des logs
- Parsing automatique des logs Laravel
- Corrélation logs <-> traces
- Recherche et alertes

##### Infrastructure Monitoring
- Métriques système (CPU, RAM, Disk, Network)
- Processes monitoring
- Container monitoring (Docker/Kubernetes)

##### Real User Monitoring (RUM)
- Performance frontend
- Erreurs JavaScript
- Analytics utilisateur

##### Custom Metrics

```php
// Envoyer une métrique personnalisée
if (function_exists('dd_trace_send_metrics_to_agent')) {
    dd_trace_send_metrics_to_agent([
        'name' => 'app.users.registered',
        'value' => 1,
        'tags' => ['environment:production', 'source:backend'],
    ]);
}

// Ajouter des tags à un span
if (function_exists('dd_trace_push_span_id')) {
    $span = dd_trace_push_span_id();
    $span->meta['user.id'] = $user->id;
    $span->meta['tenant.id'] = $tenant->id;
}
```

#### Dashboard Datadog

Accédez à votre dashboard via :
```
https://app.datadoghq.com/
```

---

### 2.3 Métriques Personnalisées

Le projet inclut un système de métriques personnalisées qui fonctionne indépendamment des APM.

#### Configuration

```env
METRICS_ENABLED=true
METRICS_SLOW_QUERY_THRESHOLD=100  # ms
METRICS_SLOW_REQUEST_THRESHOLD=1000  # ms
METRICS_HIGH_MEMORY_THRESHOLD=128  # MB
```

#### Logs de Monitoring

Les métriques sont enregistrées dans `storage/logs/monitoring.log`.

Exemple :

```json
{
  "level": "warning",
  "message": "Slow query detected",
  "context": {
    "sql": "SELECT * FROM users WHERE ...",
    "time": 234.56,
    "connection": "mysql"
  }
}
```

---

### 2.4 Health Check Endpoint

Un endpoint de santé est disponible pour les load balancers et le monitoring externe.

#### Configuration

```env
HEALTH_CHECK_ENABLED=true
HEALTH_CHECK_ENDPOINT=/health
```

#### Utilisation

```bash
curl http://localhost:8000/health
```

#### Réponse

```json
{
  "status": "healthy",
  "timestamp": "2025-10-11T10:00:00+00:00",
  "checks": {
    "database": {
      "healthy": true,
      "message": "Database connection successful",
      "response_time_ms": 12.34
    },
    "cache": {
      "healthy": true,
      "message": "Cache working correctly",
      "driver": "redis"
    },
    "storage": {
      "healthy": true,
      "message": "Storage working correctly",
      "driver": "local"
    },
    "queue": {
      "healthy": true,
      "message": "Queue connection successful",
      "driver": "redis",
      "pending_jobs": 42
    }
  },
  "environment": "production",
  "version": "1.0.0"
}
```

#### Codes de Statut

- `200` - Tout est OK
- `503` - Un ou plusieurs services sont down

#### Intégration dans Load Balancer

**Nginx :**

```nginx
upstream backend {
    server app1:8000 max_fails=3 fail_timeout=30s;
    server app2:8000 max_fails=3 fail_timeout=30s;
}

server {
    location / {
        proxy_pass http://backend;
        health_check uri=/health;
    }
}
```

**AWS Application Load Balancer :**

```terraform
resource "aws_lb_target_group" "app" {
  health_check {
    path                = "/health"
    healthy_threshold   = 2
    unhealthy_threshold = 3
    timeout             = 5
    interval            = 30
    matcher             = "200"
  }
}
```

---

## 3. Automated Backups

### Vue d'ensemble

Le système de backup utilise **Spatie Laravel Backup** pour créer des sauvegardes automatiques de :
- Base de données MySQL
- Fichiers du répertoire `storage/app`
- Fichiers personnalisés configurés

### Configuration

Le fichier de configuration se trouve dans `config/backup.php`.

#### Configuration de Base

```env
# Backup settings
BACKUP_NAME=aig-app
BACKUP_INCLUDE_FILES=true
BACKUP_INCLUDE_DATABASE=true
BACKUP_COMPRESSION_METHOD=gzip  # gzip, bzip2, zip
BACKUP_ENCRYPTION_ENABLED=false
BACKUP_ENCRYPTION_PASSWORD=your_secure_password

# Notification
BACKUP_NOTIFICATION_MAIL=admin@example.com

# Destinations
BACKUP_DISK=local  # local, s3, etc.

# Retention
BACKUP_KEEP_ALL_DAYS=7
BACKUP_KEEP_DAILY_BACKUPS=14
BACKUP_KEEP_WEEKLY_BACKUPS=8
BACKUP_KEEP_MONTHLY_BACKUPS=12
BACKUP_KEEP_YEARLY_BACKUPS=5
```

### Commandes de Backup

#### Backup Manuel

```bash
# Backup complet (DB + fichiers)
php artisan backup:run

# Backup DB uniquement
php artisan backup:database --only-db

# Backup fichiers uniquement
php artisan backup:database --only-files

# Backup avec notification
php artisan backup:run --only-to-disk=s3
```

#### Gestion des Backups

```bash
# Lister les backups
php artisan backup:list

# Nettoyer les anciens backups
php artisan backup:clean

# Monitorer les backups
php artisan backup:monitor
```

### Backup Automatisé

#### Via Cron (Serveur Linux)

Ajoutez à votre crontab :

```bash
# Backup quotidien à 2h00 du matin
0 2 * * * cd /path/to/your/app && php artisan backup:run >> /dev/null 2>&1

# Nettoyage hebdomadaire
0 3 * * 0 cd /path/to/your/app && php artisan backup:clean >> /dev/null 2>&1
```

#### Via Task Scheduler (Laravel)

Ajoutez dans `app/Console/Kernel.php` :

```php
protected function schedule(Schedule $schedule)
{
    // Backup quotidien à 2h00
    $schedule->command('backup:run')
             ->dailyAt('02:00')
             ->environments(['production']);

    // Nettoyage hebdomadaire
    $schedule->command('backup:clean')
             ->weekly()
             ->sundays()
             ->at('03:00')
             ->environments(['production']);

    // Monitoring quotidien
    $schedule->command('backup:monitor')
             ->daily()
             ->at('04:00')
             ->environments(['production']);
}
```

#### Via GitHub Actions

Un workflow automatique est configuré dans `.github/workflows/backup.yml`.

**Déclenchement :**
- Automatique : Tous les jours à 2h00
- Manuel : Via l'interface GitHub Actions

**Configuration requise (GitHub Secrets) :**

```
DB_HOST=your_db_host
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
BACKUP_PASSWORD=encryption_password
BACKUP_NOTIFICATION_EMAIL=admin@example.com

# Optionnel : Pour upload sur S3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_REGION=us-east-1
AWS_BACKUP_BUCKET=your-backup-bucket
```

**Exécution manuelle :**

```bash
# Via GitHub CLI
gh workflow run backup.yml -f backup_type=full

# Ou via l'interface web
https://github.com/ICC-Munich/icc-munich/actions/workflows/backup.yml
```

### Destinations de Backup

#### Local

```php
// config/backup.php
'destination' => [
    'disks' => [
        'local',
    ],
],
```

#### Amazon S3

1. **Installer le driver S3** :

```bash
composer require league/flysystem-aws-s3-v3 "^3.0"
```

2. **Configurer** `.env` :

```env
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-backup-bucket
AWS_USE_PATH_STYLE_ENDPOINT=false
```

3. **Configurer** `config/filesystems.php` :

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
],
```

4. **Mettre à jour** `config/backup.php` :

```php
'destination' => [
    'disks' => [
        's3',
    ],
],
```

#### Google Cloud Storage

1. **Installer le driver** :

```bash
composer require league/flysystem-google-cloud-storage "^3.0"
```

2. **Configurer** `.env` :

```env
GOOGLE_CLOUD_PROJECT_ID=your_project
GOOGLE_CLOUD_KEY_FILE=/path/to/service-account.json
GOOGLE_CLOUD_STORAGE_BUCKET=your-backup-bucket
```

#### DigitalOcean Spaces

Utilise le même driver que S3 :

```env
AWS_ACCESS_KEY_ID=your_spaces_key
AWS_SECRET_ACCESS_KEY=your_spaces_secret
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=your-space-name
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### Notifications

#### Configuration

```php
// config/backup.php
'notifications' => [
    'mail' => [
        'to' => env('BACKUP_NOTIFICATION_MAIL', 'admin@example.com'),
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'name' => env('MAIL_FROM_NAME', 'Laravel Backup'),
        ],
    ],

    'slack' => [
        'webhook_url' => env('BACKUP_SLACK_WEBHOOK'),
        'channel' => null,
        'username' => null,
        'icon' => null,
    ],

    'discord' => [
        'webhook_url' => env('BACKUP_DISCORD_WEBHOOK'),
        'username' => 'Laravel Backup',
        'avatar_url' => '',
    ],
],
```

#### Événements Notifiés

- ✅ Backup réussi
- ❌ Backup échoué
- ⚠️ Espace disque faible
- 🧹 Nettoyage effectué
- 💾 Backup trop vieux

### Restauration

#### Restaurer la Base de Données

```bash
# Lister les backups disponibles
php artisan backup:list

# Extraire le backup
cd storage/app/backups/
unzip your-backup-name.zip

# Restaurer MySQL
mysql -u username -p database_name < db-dumps/mysql-database.sql
```

#### Restaurer les Fichiers

```bash
# Extraire les fichiers du backup
unzip your-backup-name.zip -d /tmp/restore

# Copier les fichiers
cp -r /tmp/restore/storage/* storage/
```

### Monitoring des Backups

#### Vérifier les Backups

```bash
php artisan backup:monitor
```

Cette commande vérifie :
- ✅ Que les backups sont récents
- ✅ Que la taille est raisonnable
- ✅ Que les fichiers ne sont pas corrompus

#### Alertes

Configurez des alertes si :
- Aucun backup depuis 24h
- Backup échoue 2 fois de suite
- Espace disque < 10%

### Sécurité des Backups

#### Chiffrement

Activez le chiffrement pour protéger les données sensibles :

```env
BACKUP_ENCRYPTION_ENABLED=true
BACKUP_ENCRYPTION_PASSWORD=your_very_secure_password_min_16_chars
```

#### Permissions

```bash
# Restreindre l'accès aux backups
chmod 700 storage/app/backups/
```

#### Rotation

Les backups sont automatiquement supprimés selon la politique de rétention :
- Tous les backups : 7 jours
- Backups quotidiens : 14 jours
- Backups hebdomadaires : 8 semaines
- Backups mensuels : 12 mois
- Backups annuels : 5 ans

---

## 4. Intégration CI/CD

### Workflows GitHub Actions

#### Backup Workflow (`.github/workflows/backup.yml`)

**Exécution :**
- Quotidienne à 2h00
- Manuelle via l'interface

**Actions :**
1. Créer un backup complet
2. Uploader sur GitHub Artifacts (30 jours)
3. Optionnel : Upload sur S3
4. Nettoyer les anciens backups
5. Notifier en cas d'échec

### Intégration avec le Monitoring

Le pipeline CI/CD envoie automatiquement les métriques vers :
- **New Relic** : Via l'extension PHP
- **Datadog** : Via l'agent Datadog
- **GitHub Actions** : Artifacts et summary

---

## 5. Best Practices

### Monitoring

1. **Ne jamais exposer Telescope publiquement en production**
2. **Activer APM uniquement en production**
3. **Configurer des alertes pour les métriques critiques**
4. **Monitorer les requêtes lentes** (> 100ms)
5. **Surveiller la consommation mémoire**
6. **Configurer des dashboards personnalisés**

### Backups

1. **Tester régulièrement les restaurations**
2. **Stocker les backups dans un endroit différent** (S3, Google Cloud)
3. **Chiffrer les backups contenant des données sensibles**
4. **Maintenir au moins 3 copies** (local + 2 remote)
5. **Automatiser le monitoring des backups**
6. **Documenter la procédure de restauration**
7. **Vérifier l'intégrité des backups**

### Performance

1. **Optimiser les requêtes N+1**
2. **Utiliser le cache Redis pour les données fréquentes**
3. **Indexer les colonnes fréquemment recherchées**
4. **Paginer les résultats larges**
5. **Utiliser les jobs asynchrones pour les tâches longues**

---

## 6. Troubleshooting

### Telescope ne s'affiche pas

```bash
# Publier les assets
php artisan telescope:publish

# Migrer la base de données
php artisan migrate

# Vérifier les permissions
php artisan telescope:install
```

### APM ne track pas

**New Relic :**

```bash
# Vérifier l'extension
php -m | grep newrelic

# Vérifier la configuration
php -i | grep newrelic

# Redémarrer PHP-FPM
sudo systemctl restart php8.2-fpm
```

**Datadog :**

```bash
# Vérifier l'extension
php -m | grep ddtrace

# Vérifier l'agent
sudo systemctl status datadog-agent

# Redémarrer
sudo systemctl restart datadog-agent php8.2-fpm
```

### Backup échoue

```bash
# Vérifier les permissions
ls -la storage/app/

# Vérifier l'espace disque
df -h

# Tester la connexion DB
php artisan tinker
>>> DB::connection()->getPdo();

# Logs
tail -f storage/logs/laravel.log
```

---

## 7. Ressources

### Documentation Officielle

- **Telescope :** https://laravel.com/docs/telescope
- **Spatie Backup :** https://spatie.be/docs/laravel-backup
- **New Relic :** https://docs.newrelic.com/docs/apm/
- **Datadog :** https://docs.datadoghq.com/

### Dashboards Recommandés

#### New Relic
- Application Performance
- Database Performance
- Error Analytics
- Custom Dashboards

#### Datadog
- APM Overview
- Infrastructure Map
- Logs Explorer
- RUM Dashboard

---

**Dernière mise à jour :** 2025-10-11
**Version :** 1.0.0
