# Configuration des Queues et Envoi de Mails

Ce document explique comment configurer le traitement des jobs en file d'attente (queues) et l'envoi de mails selon votre environnement de deploiement.

## Table des matieres

1. [Vue d'ensemble](#vue-densemble)
2. [Mails synchrones vs asynchrones](#mails-synchrones-vs-asynchrones)
3. [Configuration SMTP](#configuration-smtp)
4. [Deploiement avec Docker](#deploiement-avec-docker)
5. [Deploiement avec Supervisor (VPS/Serveur dedie)](#deploiement-avec-supervisor-vpsserveur-dedie)
6. [Deploiement sur hebergement partage](#deploiement-sur-hebergement-partage)
7. [Verification et depannage](#verification-et-depannage)

---

## Vue d'ensemble

L'application utilise le systeme de queues de Laravel pour traiter les taches en arriere-plan :

- **Notifications par email** : `TaskAssigned`, `DepartmentTodoAssigned`, `AppointmentReminder`, etc.
- **Notifications en base de donnees** : stockees dans la table `notifications`
- **Rappels planifies** : envoi automatique de rappels de rendez-vous

### Architecture

```
Utilisateur assigne une tache
    |
    v
Observer (TaskObserver / DepartmentTodoObserver)
    |
    v
Notification::send() --> Job en queue (table `jobs`)
    |
    v
Queue Worker traite le job --> SMTP --> Email envoye
```

Les notifications implementent `ShouldQueue`, ce qui signifie qu'elles ne sont **pas envoyees immediatement** mais placees en file d'attente. Un **worker** doit tourner en permanence pour les traiter.

### Driver de queue

L'application utilise le driver `database` par defaut (`QUEUE_CONNECTION=database`). Les jobs sont stockes dans la table `jobs` de MySQL. Cela fonctionne sans Redis ni service externe.

---

## Mails synchrones vs asynchrones

L'application utilise **deux mecanismes differents** pour envoyer des emails. Il est important de comprendre la difference car cela impacte directement le besoin (ou non) d'un queue worker.

### Envoi synchrone (`Mail::send`)

Certains emails sont envoyes **immediatement** pendant la requete HTTP, sans passer par la queue. L'utilisateur attend que le serveur SMTP reponde avant d'etre redirige.

**Exemple : email de bienvenue a l'inscription**

```php
// app/Http/Controllers/Auth/RegisteredUserController.php
Mail::to($user->email)->send(new WelcomeMail($user, $verificationUrl));
```

La classe `WelcomeMail` est un `Mailable` standard qui n'implemente **pas** `ShouldQueue`. L'appel `Mail::send()` contacte le serveur SMTP directement et attend sa reponse.

**Mails synchrones dans l'application :**

| Mail | Declencheur | Fichier |
|------|-------------|---------|
| `WelcomeMail` | Inscription d'un utilisateur | `RegisteredUserController::store()` |
| `ContactSubmitted` | Formulaire de contact | `ContactController::store()` |

**Avantages** : Fonctionne sans queue worker. Simple et fiable.
**Inconvenients** : L'utilisateur attend la reponse du SMTP (1-5 secondes). Si le SMTP est lent ou en erreur, la requete echoue.

### Envoi asynchrone (Notifications avec `ShouldQueue`)

La majorite des emails passent par le systeme de **notifications** de Laravel, qui implemente `ShouldQueue`. Le mail est place dans la table `jobs` et traite plus tard par un worker.

**Exemple : notification d'assignation de tache**

```php
// app/Observers/TaskObserver.php
$assignee->notify(new TaskAssigned($task, $assignedBy));

// app/Notifications/TaskAssigned.php
class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;
    // ...
}
```

**Notifications asynchrones dans l'application :**

| Notification | Declencheur | Queue requise |
|-------------|-------------|---------------|
| `TaskAssigned` | Assignation d'une tache projet | Oui |
| `DepartmentTodoAssigned` | Assignation d'une tache departement | Oui |
| `AppointmentReminder` | Rappel de rendez-vous (planifie) | Oui |
| `AppointmentCreated` | Creation d'un rendez-vous | Oui |
| `AppointmentCancellation` | Annulation d'un rendez-vous | Oui |
| `TaskCommentAdded` | Commentaire sur une tache | Oui |
| `ProjectParticipantAdded` | Ajout a un projet | Oui |
| `DepartmentMeetingCreated` | Reunion de departement | Oui |
| `WorkflowApprovalRequired` | Approbation workflow | Oui |

**Avantages** : L'utilisateur n'attend pas. Resilient (retry automatique en cas d'erreur SMTP). Traitement en parallele.
**Inconvenients** : Necessite un queue worker actif. Sans worker, les mails restent indefiniment dans la table `jobs`.

### Tableau comparatif

| | Synchrone (`Mail::send`) | Asynchrone (`ShouldQueue`) |
|---|---|---|
| **Quand le mail part** | Immediatement, pendant la requete | Quand le worker traite le job |
| **Impact utilisateur** | Attend la reponse SMTP | Aucun delai visible |
| **Queue worker requis** | Non | Oui |
| **En cas d'erreur SMTP** | La requete HTTP echoue | Le job est reessaye (3 tentatives) |
| **Utilise pour** | Mails critiques (verification email) | Notifications, rappels, alertes |

### Pourquoi les deux approches ?

- Le **mail de bienvenue** est synchrone car il contient le lien de verification email. L'utilisateur doit le recevoir immediatement apres l'inscription. Si le mail echoue, il est preferable que l'inscription echoue aussi (l'utilisateur peut reessayer).
- Les **notifications** sont asynchrones car elles ne sont pas bloquantes. L'utilisateur qui assigne une tache n'a pas besoin d'attendre que le mail parte. De plus, une erreur SMTP ne doit pas bloquer l'assignation.

> **Important** : Si vous deployez sans queue worker, les mails synchrones (`WelcomeMail`) fonctionneront, mais toutes les notifications (`TaskAssigned`, `DepartmentTodoAssigned`, etc.) resteront bloquees dans la table `jobs` et ne seront **jamais envoyees**.

---

## Configuration SMTP

### Variables d'environnement

Configurez ces variables dans votre fichier `.env` :

#### Developpement local (Mailhog)

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_SCHEME=null
MAIL_FROM_ADDRESS="noreply@icc-munich.de"
MAIL_FROM_NAME="ICC Munich"
```

#### Production

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.votre-provider.com
MAIL_PORT=587
MAIL_USERNAME=votre-username
MAIL_PASSWORD=votre-mot-de-passe
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS="contact@icc-munich.de"
MAIL_FROM_NAME="ICC Munich"
```

### Providers SMTP compatibles

| Provider | MAIL_HOST | MAIL_PORT | MAIL_SCHEME |
|----------|-----------|-----------|-------------|
| Gmail | smtp.gmail.com | 587 | tls |
| Outlook/Office 365 | smtp.office365.com | 587 | tls |
| Amazon SES | email-smtp.eu-west-1.amazonaws.com | 587 | tls |
| Mailgun | smtp.mailgun.org | 587 | tls |
| SendGrid | smtp.sendgrid.net | 587 | tls |
| OVH | ssl0.ovh.net | 465 | ssl |
| IONOS | smtp.ionos.de | 587 | tls |
| Strato | smtp.strato.de | 465 | ssl |
| Hetzner | mail.your-server.de | 587 | tls |

> **Important** : Apres toute modification du `.env`, videz le cache :
> ```bash
> php artisan config:clear
> php artisan config:cache  # en production uniquement
> ```

---

## Deploiement avec Docker

### Developpement local

Le `docker-compose.yml` inclut deja :
- **Mailhog** sur le port 8025 (interface web) et 1025 (SMTP)
- Un **queue worker** (`icc-queue`)
- Un **scheduler** (`icc-scheduler`)

Les variables `MAIL_HOST=mailhog` et `MAIL_PORT=1025` sont definies dans les services `app`, `queue` et `scheduler`.

```bash
# Demarrer tous les services
docker compose up -d

# Verifier que le worker tourne
docker compose logs queue --tail=10

# Interface Mailhog pour voir les emails
# http://localhost:8025
```

### Production Docker

Le `docker-compose.prod.yml` utilise `env_file: .env` pour charger la configuration.

```bash
# 1. Configurer le .env de production sur le serveur
nano .env

# 2. Demarrer les services
docker compose -f docker-compose.prod.yml up -d

# 3. Verifier les workers
docker compose -f docker-compose.prod.yml logs queue --tail=20
docker compose -f docker-compose.prod.yml logs scheduler --tail=20
```

L'image Docker de production inclut egalement **Supervisor** pre-configure. Si vous souhaitez consolider tous les process dans un seul container :

```bash
# Lancer avec Supervisor (PHP-FPM + queue worker + scheduler dans un seul container)
docker compose -f docker-compose.prod.yml exec app supervisord -c /etc/supervisor/supervisord.conf
```

---

## Deploiement avec Supervisor (VPS/Serveur dedie)

C'est la methode recommandee par Laravel pour la production. Elle necessite un acces **root** ou **sudo**.

### 1. Installer Supervisor

```bash
# Ubuntu / Debian
sudo apt-get update
sudo apt-get install supervisor

# CentOS / RHEL
sudo yum install supervisor

# Verifier l'installation
supervisord --version
```

### 2. Copier la configuration

Un fichier de configuration pret a l'emploi est fourni dans le projet :

```bash
sudo cp docker/supervisor/production-no-docker.conf /etc/supervisor/conf.d/icc-munich.conf
```

### 3. Adapter les chemins

Editez le fichier si le chemin de l'application n'est pas `/var/www/icc-munich` :

```bash
sudo nano /etc/supervisor/conf.d/icc-munich.conf
```

Remplacez toutes les occurrences de `/var/www/icc-munich` par le chemin reel de votre application.

### 4. Contenu de la configuration

```ini
[program:icc-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/icc-munich/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/icc-munich
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/icc-munich/storage/logs/queue-worker.log
stopwaitsecs=3600

[program:icc-scheduler]
process_name=%(program_name)s
command=sh -c "while true; do php /var/www/icc-munich/artisan schedule:run --verbose --no-interaction; sleep 60; done"
directory=/var/www/icc-munich
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/icc-munich/storage/logs/scheduler.log
stopwaitsecs=60
```

#### Explication des parametres

| Parametre | Valeur | Description |
|-----------|--------|-------------|
| `numprocs` | 2 | Nombre de workers en parallele (augmenter si beaucoup de jobs) |
| `--sleep=3` | 3s | Temps d'attente entre les verifications de nouveaux jobs |
| `--tries=3` | 3 | Nombre de tentatives avant d'abandonner un job |
| `--max-time=3600` | 1h | Le worker redemarre toutes les heures (libere la memoire) |
| `stopwaitsecs` | 3600 | Temps max accorde au worker pour finir son job avant arret force |
| `autorestart` | true | Relance automatique si le process crash |
| `user` | www-data | L'utilisateur systeme qui execute le worker |

### 5. Activer et demarrer

```bash
# Relire les configurations
sudo supervisorctl reread

# Appliquer les changements
sudo supervisorctl update

# Demarrer tous les process
sudo supervisorctl start all

# Verifier le statut
sudo supervisorctl status
```

Resultat attendu :

```
icc-queue-worker:icc-queue-worker_00   RUNNING   pid 12345, uptime 0:01:00
icc-queue-worker:icc-queue-worker_01   RUNNING   pid 12346, uptime 0:01:00
icc-scheduler                          RUNNING   pid 12347, uptime 0:01:00
```

### 6. Apres chaque deploiement

```bash
# Redemarrer gracieusement les workers (finissent le job en cours puis s'arretent)
php artisan queue:restart

# Supervisor les relancera automatiquement
```

### Commandes Supervisor utiles

```bash
# Statut de tous les process
sudo supervisorctl status

# Redemarrer les workers
sudo supervisorctl restart icc-queue-worker:*

# Arreter les workers
sudo supervisorctl stop icc-queue-worker:*

# Voir les logs en temps reel
sudo tail -f /var/www/icc-munich/storage/logs/queue-worker.log
```

---

## Deploiement sur hebergement partage

Sur un hebergement partage (OVH, IONOS, Strato, Hostinger, etc.), vous n'avez pas acces a Supervisor. L'application inclut une solution alternative basee sur le **scheduler Laravel** et un **cron job**.

### Comment ca fonctionne

```
Cron (toutes les minutes)
    |
    v
php artisan schedule:run
    |
    v
queue:work --stop-when-empty --max-time=55
    |
    v
Traite tous les jobs en attente, puis s'arrete
```

Le scheduler Laravel lance `queue:work --stop-when-empty` chaque minute. Le worker :
1. Traite **tous les jobs en attente** (emails, notifications)
2. S'arrete des que la queue est vide
3. Se termine au bout de **55 secondes** max (avant le prochain cron a 60s)
4. `--withoutOverlapping` empeche les executions simultanees

### 1. Configurer le cron job

Ajoutez **un seul cron** via votre panneau d'administration (cPanel, Plesk, DirectAdmin, etc.) :

```
* * * * * cd /chemin/vers/icc-munich && php artisan schedule:run >> /dev/null 2>&1
```

#### Selon l'hebergeur

**cPanel** :
1. Aller dans "Cron Jobs" / "Taches planifiees"
2. Selectionner "Toutes les minutes" (`* * * * *`)
3. Commande : `cd /home/votre-user/public_html && php artisan schedule:run >> /dev/null 2>&1`

**Plesk** :
1. Aller dans "Taches planifiees"
2. Ajouter une tache avec l'intervalle `* * * * *`
3. Commande : `cd /var/www/vhosts/votre-domaine.com/httpdocs && php artisan schedule:run >> /dev/null 2>&1`

**IONOS / 1&1** :
1. Aller dans "Actions planifiees" dans l'espace client
2. Creer une nouvelle action : URL ou commande SSH
3. Frequence : toutes les minutes
4. Si URL : `https://votre-domaine.com/schedule-run` (necessite une route dediee)

> **Note sur le chemin PHP** : Certains hebergeurs necessitent le chemin complet vers PHP.
> Exemples :
> - `/usr/bin/php`
> - `/usr/local/bin/php8.4`
> - `/opt/alt/php84/usr/bin/php`
>
> Verifiez avec : `which php` ou demandez a votre hebergeur.

### 2. Verifier que le cron fonctionne

```bash
# Executer manuellement pour tester
php artisan schedule:run

# Verifier les logs
cat storage/logs/queue-worker.log
```

### 3. Alternative : route web pour declencher le scheduler

Si votre hebergeur ne supporte que les crons via URL (pas de commande SSH), vous pouvez ajouter une route protegee par un token :

```php
// routes/web.php
Route::get('/schedule-run/{token}', function (string $token) {
    if ($token !== config('app.schedule_token')) {
        abort(403);
    }
    Artisan::call('schedule:run');
    return response('OK', 200);
});
```

Puis dans le `.env` :
```env
SCHEDULE_TOKEN=un-token-secret-aleatoire
```

Et dans le `config/app.php` :
```php
'schedule_token' => env('SCHEDULE_TOKEN'),
```

URL du cron : `https://votre-domaine.com/schedule-run/un-token-secret-aleatoire`

### Limitations de l'hebergement partage

| Aspect | Impact | Solution |
|--------|--------|----------|
| Latence | Max ~1 minute avant l'envoi | Acceptable pour les notifications |
| Execution limitee | Certains hebergeurs limitent a 30s | `--max-time=25` si necessaire |
| Pas de process permanent | Le worker s'arrete entre les crons | `--stop-when-empty` gere cela |
| Memoire limitee | Workers limites en RAM | Un seul worker, pas de parallelisme |

---

## Verification et depannage

### Tester l'envoi de mail

```bash
# Depuis la console Laravel
php artisan tinker

# Envoyer un mail de test
Mail::raw('Test email', function ($message) {
    $message->to('test@example.com')->subject('Test');
});
```

### Verifier la queue

```bash
# Voir les jobs en attente
php artisan queue:monitor database:default

# Traiter un seul job manuellement
php artisan queue:work --once

# Voir les jobs echoues
php artisan queue:failed

# Relancer les jobs echoues
php artisan queue:retry all

# Supprimer les jobs echoues
php artisan queue:flush
```

### Problemes courants

#### Les mails ne partent pas

1. **Verifier la config SMTP** :
   ```bash
   php artisan tinker --execute="dd(config('mail.mailers.smtp'));"
   ```

2. **Verifier que le worker tourne** :
   ```bash
   # Avec Supervisor
   sudo supervisorctl status

   # Avec Docker
   docker compose logs queue --tail=20

   # Verifier les jobs en attente
   php artisan tinker --execute="dd(DB::table('jobs')->count());"
   ```

3. **Verifier les jobs echoues** :
   ```bash
   php artisan queue:failed
   ```

#### Les mails restent en queue (ne sont jamais traites)

- **Cause** : Le queue worker ne tourne pas
- **Diagnostic** : `php artisan tinker --execute="dd(DB::table('jobs')->count());"`
  - Si le nombre augmente sans diminuer, le worker ne tourne pas
- **Solution** : Verifier Supervisor / cron selon votre environnement

#### Les jobs echouent avec "Connection refused"

- **Cause** : Mauvais `MAIL_HOST` (souvent `127.0.0.1` en production)
- **Solution** : Configurer le vrai serveur SMTP dans le `.env`
- **Docker** : Verifier que `MAIL_HOST=mailhog` (dev) ou le vrai SMTP (prod) est dans les variables d'environnement du container

#### Les mails partent en double

- **Cause** : Plusieurs workers traitent le meme job
- **Solution** : Verifier que `--withoutOverlapping` est actif (scheduler) ou que `numprocs` est raisonnable (Supervisor)

### Logs utiles

```bash
# Logs Laravel generaux
tail -f storage/logs/laravel.log

# Logs du queue worker
tail -f storage/logs/queue-worker.log

# Logs du scheduler
tail -f storage/logs/scheduler.log

# Logs Supervisor (si installe)
sudo tail -f /var/log/supervisor/icc-queue-worker.log
```

### Monitoring en production

Pour surveiller la sante des queues, verifiez regulierement :

```bash
# Nombre de jobs en attente (devrait rester bas)
php artisan tinker --execute="echo 'Pending: ' . DB::table('jobs')->count() . ', Failed: ' . DB::table('failed_jobs')->count();"
```

Si le nombre de jobs en attente augmente continuellement, le worker ne traite pas assez vite ou est arrete.

---

## Resume par environnement

| Environnement | Methode | Latence | Configuration |
|---------------|---------|---------|---------------|
| **Dev local** | `php artisan queue:work` | Instantane | Lancer manuellement |
| **Docker dev** | Container `icc-queue` | Instantane | `docker compose up -d` |
| **Docker prod** | Container `icc-queue` + Supervisor | Instantane | `docker-compose.prod.yml` |
| **VPS / Serveur dedie** | Supervisor | Instantane | `production-no-docker.conf` |
| **Hebergement partage** | Cron + Scheduler | Max 1 min | Un seul cron job |
