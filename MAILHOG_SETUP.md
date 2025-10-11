# Configuration Mailhog pour ICC München

## Qu'est-ce que Mailhog ?

Mailhog est un outil de test d'emails pour le développement. Il capture tous les emails envoyés par votre application et vous permet de les visualiser dans une interface web, sans les envoyer réellement.

## Installation et Démarrage

### Option 1 : Avec Docker (Recommandé)

1. **Démarrer Mailhog** :
   ```bash
   docker-compose up -d mailhog
   ```

2. **Vérifier que Mailhog fonctionne** :
   ```bash
   docker-compose ps
   ```

3. **Accéder à l'interface web** :
   Ouvrez votre navigateur à : http://localhost:8025

4. **Arrêter Mailhog** :
   ```bash
   docker-compose down
   ```

### Option 2 : Installation native (macOS avec Homebrew)

1. **Installer Mailhog** :
   ```bash
   brew install mailhog
   ```

2. **Démarrer Mailhog** :
   ```bash
   mailhog
   ```

3. **Accéder à l'interface web** :
   Ouvrez votre navigateur à : http://localhost:8025

### Option 3 : Installation native (Linux)

1. **Télécharger Mailhog** :
   ```bash
   wget https://github.com/mailhog/MailHog/releases/download/v1.0.1/MailHog_linux_amd64
   chmod +x MailHog_linux_amd64
   sudo mv MailHog_linux_amd64 /usr/local/bin/mailhog
   ```

2. **Démarrer Mailhog** :
   ```bash
   mailhog
   ```

## Configuration Laravel

La configuration est déjà faite dans le fichier `.env` :

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@icc-muenchen.de"
MAIL_FROM_NAME="ICC München"
```

## Utilisation

### Tester l'envoi d'emails

1. **Démarrez Mailhog** (si ce n'est pas déjà fait)

2. **Inscrivez un nouvel utilisateur** :
   - Allez sur http://localhost:8000/register
   - Remplissez le formulaire d'inscription
   - Soumettez le formulaire

3. **Consultez l'email dans Mailhog** :
   - Ouvrez http://localhost:8025
   - Vous verrez l'email de bienvenue avec le lien de vérification
   - Cliquez sur l'email pour voir le contenu HTML complet

### Tester la réinitialisation de mot de passe

1. **Demandez une réinitialisation** :
   - Allez sur http://localhost:8000/forgot-password
   - Entrez votre email
   - Soumettez le formulaire

2. **Consultez l'email dans Mailhog** :
   - Ouvrez http://localhost:8025
   - Vous verrez l'email de réinitialisation
   - Cliquez sur le lien dans l'email pour réinitialiser votre mot de passe

## Fonctionnalités de Mailhog

### Interface Web (http://localhost:8025)

- **Liste des emails** : Tous les emails capturés apparaissent dans la liste
- **Détails de l'email** : Cliquez sur un email pour voir :
  - Le contenu HTML rendu
  - Le contenu en texte brut
  - Le code source de l'email
  - Les en-têtes de l'email
- **Téléchargement** : Téléchargez l'email au format .eml
- **Suppression** : Supprimez un ou tous les emails
- **Recherche** : Recherchez des emails par destinataire, expéditeur, ou sujet

### API

Mailhog expose également une API REST :

- **Liste des emails** : `GET http://localhost:8025/api/v2/messages`
- **Supprimer tous les emails** : `DELETE http://localhost:8025/api/v1/messages`

## Tests Automatisés

Pour les tests, Mailhog capture automatiquement tous les emails. Vous pouvez vérifier qu'un email a été envoyé dans vos tests :

```php
// Dans un test
Mail::fake();

// Effectuer une action qui envoie un email
$this->post('/register', [...]);

// Vérifier que l'email a été envoyé
Mail::assertSent(WelcomeMail::class, function ($mail) {
    return $mail->hasTo('test@example.com');
});
```

## Dépannage

### Mailhog ne capture pas les emails

1. **Vérifiez que Mailhog est démarré** :
   ```bash
   docker-compose ps  # Pour Docker
   # ou
   ps aux | grep mailhog  # Pour installation native
   ```

2. **Vérifiez la configuration .env** :
   - `MAIL_MAILER=smtp`
   - `MAIL_PORT=1025`
   - `MAIL_HOST=127.0.0.1`

3. **Redémarrez Laravel** :
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

### Le port 1025 est déjà utilisé

Si le port 1025 est déjà utilisé, vous pouvez changer le port dans `.env` et `docker-compose.yml` :

```yaml
# docker-compose.yml
ports:
  - "1026:1025"  # Change le port externe
```

```env
# .env
MAIL_PORT=1026
```

### L'interface web n'est pas accessible

Vérifiez que le port 8025 n'est pas bloqué par votre pare-feu ou utilisé par une autre application.

## Emails disponibles dans l'application

L'application ICC München envoie les emails suivants :

1. **Email de bienvenue** (WelcomeMail) :
   - Envoyé lors de l'inscription
   - Contient un lien de vérification d'email
   - Valide pendant 60 minutes

2. **Email de réinitialisation de mot de passe** :
   - Envoyé via "Mot de passe oublié"
   - Contient un lien de réinitialisation
   - Valide pendant 60 minutes

3. **Notifications 2FA** (si configuré) :
   - Notification lors de l'activation de la 2FA
   - Codes de récupération

## Commandes utiles

```bash
# Démarrer Mailhog (Docker)
docker-compose up -d mailhog

# Voir les logs de Mailhog
docker-compose logs -f mailhog

# Arrêter Mailhog
docker-compose down

# Redémarrer Mailhog
docker-compose restart mailhog

# Tester l'envoi d'email via artisan tinker
php artisan tinker
>>> Mail::raw('Test email', function ($message) { $message->to('test@example.com')->subject('Test'); });
```

## Ressources

- [Documentation Mailhog](https://github.com/mailhog/MailHog)
- [Mailhog Web UI](http://localhost:8025)
- [API Mailhog](http://localhost:8025/api/)
