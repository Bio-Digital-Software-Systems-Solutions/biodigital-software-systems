# 🚀 Déploiement des Rôles et Permissions

Ce document explique comment mettre à jour les rôles et permissions lors d'un déploiement en production.

## 📋 Vue d'ensemble

Le système de permissions utilise des scripts automatisés pour synchroniser les rôles et permissions entre les environnements local et production, évitant les erreurs manuelles et garantissant la cohérence.

## 🛠️ Outils Disponibles

### 1. Commande Artisan (Recommandée)

```bash
php artisan permissions:update [options]
```

**Options disponibles :**
- `--dry-run` : Simule les changements sans les appliquer
- `--reset-super-admin` : Remet à zéro les permissions du SuperAdmin
- `--force` : Force l'exécution sans confirmation

### 2. Script Bash (Plus Pratique)

```bash
./scripts/update-permissions-production.sh [options]
```

**Options supplémentaires :**
- `--backup` : Crée une sauvegarde avant les modifications
- `--help` : Affiche l'aide détaillée

## 🔄 Processus de Déploiement

### Étape 1 : Vérification (Dry Run)

```bash
# Commande Artisan
php artisan permissions:update --dry-run

# Ou script bash
./scripts/update-permissions-production.sh --dry-run
```

Cette commande affiche ce qui serait modifié sans faire de changements réels.

### Étape 2 : Déploiement Normal

```bash
# Avec sauvegarde automatique (recommandé)
./scripts/update-permissions-production.sh --backup

# Ou commande simple
php artisan permissions:update
```

### Étape 3 : Reset SuperAdmin (Si Nécessaire)

```bash
# Si le SuperAdmin a des permissions manquantes
php artisan permissions:update --reset-super-admin

# Ou avec le script complet
./scripts/update-permissions-production.sh --reset-super-admin --backup
```

## 📊 Actions Effectuées

Le script effectue automatiquement :

1. **✅ Nettoyage du cache** des permissions
2. **✅ Création des permissions manquantes** (basées sur le code source)
3. **✅ Création des rôles manquants**
4. **✅ Mise à jour des permissions des rôles** existants
5. **✅ Reset du SuperAdmin** (optionnel)
6. **✅ Nettoyage des caches** de l'application

## 🔒 Sécurité

### Protections Intégrées

- **Confirmation en production** : Le script demande confirmation en environnement production
- **Mode simulation** : `--dry-run` permet de voir les changements avant application
- **Sauvegarde automatique** : Option `--backup` pour créer une sauvegarde
- **Validation des données** : Vérification de l'intégrité avant modifications

### SuperAdmin

Le rôle **SuperAdmin** est protégé :
- ✅ **Interface** : Le bouton "Enregistrer" est masqué
- ✅ **Backend** : Protection explicite contre les modifications
- ✅ **Script** : Option `--reset-super-admin` pour correction si nécessaire

## 🐛 Résolution des Problèmes

### Permissions Manquantes

```bash
# Vérifier l'état actuel
php artisan permissions:update --dry-run

# Corriger les permissions manquantes
php artisan permissions:update
```

### SuperAdmin avec Permissions Incomplètes

```bash
# Reset complet du SuperAdmin
php artisan permissions:update --reset-super-admin
```

### Problèmes de Cache

```bash
# Nettoyage manuel des caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

## 📈 Monitoring

### Vérification Post-Déploiement

```bash
# Voir un résumé de l'état actuel
php artisan permissions:update --dry-run
```

Le résumé affiche :
- Nombre total de permissions
- Nombre total de rôles
- État du SuperAdmin
- Permissions manquantes éventuelles

### Logs

Les opérations sont loggées avec des emoji pour faciliter la lecture :
- 🚀 Début d'opération
- ✅ Succès
- ⚠️ Avertissement
- ❌ Erreur
- 📊 Statistiques

## 💡 Bonnes Pratiques

### En Développement

```bash
# Toujours faire un dry-run d'abord
php artisan permissions:update --dry-run

# Puis appliquer
php artisan permissions:update
```

### En Production

```bash
# Toujours avec sauvegarde
./scripts/update-permissions-production.sh --backup

# En cas de problème critique, reset SuperAdmin
./scripts/update-permissions-production.sh --reset-super-admin --backup --force
```

### Déploiement Automatisé (CI/CD)

```bash
# Dans votre pipeline de déploiement
./scripts/update-permissions-production.sh --force --backup
```

## 📁 Structure des Fichiers

```
/app/Console/Commands/
├── UpdateRolesAndPermissions.php    # Commande Artisan principale

/scripts/
├── update-permissions-production.sh  # Script bash avec protections

/storage/backups/
├── permissions_backup_*.sql          # Sauvegardes automatiques
```

## 🚨 Urgences

### Accès Bloqué en Production

Si le SuperAdmin perd ses permissions :

```bash
# Connexion SSH au serveur
ssh user@production-server

# Reset d'urgence
php artisan permissions:update --reset-super-admin --force

# Vérification
php artisan tinker --execute="
\$admin = \Spatie\Permission\Models\Role::where('name', 'SuperAdmin')->first();
echo 'SuperAdmin permissions: ' . \$admin->permissions->count();
"
```

### Rollback

Si vous avez créé une sauvegarde :

```bash
# Restaurer depuis la sauvegarde
mysql -h[host] -u[user] -p[password] [database] < storage/backups/permissions_backup_[timestamp].sql

# Puis nettoyer le cache
php artisan cache:clear
```

---

**⚡ Utilisation Rapide :**

```bash
# Déploiement standard
./scripts/update-permissions-production.sh --backup

# Vérification seulement
./scripts/update-permissions-production.sh --dry-run

# Urgence SuperAdmin
./scripts/update-permissions-production.sh --reset-super-admin --force
```