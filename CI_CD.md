# CI/CD Pipeline Documentation

## Vue d'ensemble

Ce projet utilise **GitHub Actions** pour automatiser les tests, les vérifications de qualité de code et les audits de sécurité. Le pipeline s'exécute automatiquement à chaque `push` et `pull request` sur les branches principales.

## Architecture du Pipeline

### 📋 Workflows Disponibles

Le pipeline CI/CD est composé de trois workflows principaux :

1. **`ci.yml`** - Pipeline principal de CI/CD
2. **`pr-checks.yml`** - Vérifications spécifiques aux Pull Requests
3. **`dependency-review.yml`** - Revue des dépendances et sécurité

---

## 1. Pipeline Principal CI/CD (`ci.yml`)

### Déclenchement
- ✅ Push sur les branches : `main`, `develop`, `staging`
- ✅ Pull Requests vers ces branches

### Jobs Exécutés

#### 🔍 **Job 1 : Code Quality & Static Analysis**
Vérifie la qualité du code PHP sans exécuter les tests.

**Commandes Makefile exécutées :**
- `make phpstan` - Analyse statique avec PHPStan (niveau 10)
- `make phpcs` - Vérification PSR-12 avec PHP_CodeSniffer
- `make phpmd` - Détection de complexité avec PHP Mess Detector
- `make pint` - Vérification du style de code avec Laravel Pint

**Durée estimée :** 2-4 minutes

---

#### 🧪 **Job 2 : Backend Tests**
Exécute tous les tests backend avec plusieurs versions de PHP.

**Configuration :**
- Matrix strategy : PHP 8.2 et 8.3
- Base de données : MySQL 8.0 (service container)
- Coverage : Xdebug activé

**Commandes Makefile exécutées :**
- `make pest` - Tests avec Pest
- `make test` - Tests PHPUnit (legacy)

**Artifacts générés :**
- `coverage.xml` - Rapport de couverture de code

**Durée estimée :** 3-5 minutes par version PHP

---

#### 🎨 **Job 3 : Frontend Tests & Build**
Teste et compile le code TypeScript/React.

**Configuration :**
- Matrix strategy : Node.js 18 et 20
- Cache npm activé

**Étapes :**
1. Compilation TypeScript (`tsc --noEmit`)
2. Tests Jest (`npm run test`)
3. Build production (`npm run build`)

**Artifacts générés :**
- `frontend-build` - Assets compilés

**Durée estimée :** 2-3 minutes par version Node

---

#### 🔒 **Job 4 : Security Checks**
Audit de sécurité des dépendances PHP et npm.

**Vérifications :**
- `composer audit` - Vulnérabilités PHP
- `npm audit` - Vulnérabilités JavaScript

**Durée estimée :** 1-2 minutes

---

#### ✅ **Job 5 : Full Quality Check**
Exécute la commande complète `make quality` après la réussite des jobs précédents.

**Commande Makefile exécutée :**
- `make quality` - Tous les checks (phpstan + phpcs + phpmd + pint + pest)

**Dépendances :** Attend la réussite des jobs 1-4

**Durée estimée :** 4-6 minutes

---

## 2. Pull Request Checks (`pr-checks.yml`)

### Déclenchement
- ✅ Ouverture d'une PR
- ✅ Synchronisation (nouveau commit)
- ✅ Réouverture d'une PR

### Jobs Exécutés

#### 🤖 **PR Analysis**
Analyse les fichiers modifiés et commente la PR avec les résultats.

**Fonctionnalités :**
- Détecte les fichiers PHP modifiés
- Exécute PHPStan et PHPCS sur ces fichiers
- Poste/met à jour un commentaire avec les résultats
- ✅ Affiche un badge vert si aucun problème
- ⚠️ Affiche les erreurs trouvées (tronquées à 2000 caractères)

---

#### 📏 **PR Size Check**
Analyse la taille de la PR et ajoute des labels automatiques.

**Labels automatiques :**
- `size/XS` - ≤ 5 fichiers, ≤ 50 lignes
- `size/S` - ≤ 10 fichiers, ≤ 200 lignes
- `size/M` - ≤ 20 fichiers, ≤ 500 lignes
- `size/L` - ≤ 40 fichiers, ≤ 1000 lignes
- `size/XL` - > 40 fichiers ou > 1000 lignes

**Pourquoi ?** Les PRs plus petites sont plus faciles à reviewer et à merger.

---

## 3. Dependency Review (`dependency-review.yml`)

### Déclenchement
- ✅ Tous les lundis à 9h00 (cron)
- ✅ Exécution manuelle (workflow_dispatch)
- ✅ PRs modifiant `composer.json`, `composer.lock`, `package.json`, `package-lock.json`

### Jobs Exécutés

#### 📦 **Check Outdated Dependencies**
Liste les dépendances obsolètes.

**Commandes :**
- `composer outdated --direct --strict`
- `npm outdated`

**Artifacts :** Rapports de dépendances obsolètes

---

#### 🔐 **Security Audit**
Audit de sécurité approfondi avec rapports JSON.

**Vérifications :**
- Vulnérabilités critiques → Fail le workflow
- Vulnérabilités hautes → Fail le workflow
- Vulnérabilités modérées/faibles → Avertissement

**Artifacts :** Rapports JSON et texte

---

#### ⚖️ **License Check**
Vérifie les licences des dépendances.

**Commandes :**
- `composer licenses`
- `npx license-checker`

**Artifacts :** Rapports de licences

---

## Configuration Locale

### Installation des Outils

```bash
# Installer les dépendances PHP
composer install

# Installer les dépendances npm
npm ci

# Vérifier que Make est installé
make help
```

### Commandes Makefile Disponibles

```bash
# Analyse statique
make phpstan       # PHPStan niveau 10
make phpcs         # PHP_CodeSniffer (PSR-12)
make phpmd         # PHP Mess Detector
make pint          # Laravel Pint (check mode)

# Tests
make pest          # Tests Pest
make test          # Tests PHPUnit

# Utilitaires
make fix           # Auto-fix code style
make clear         # Clear caches Laravel
make quality       # Tous les checks
make quality-fix   # Fix + tous les checks
```

### Exécuter le Pipeline Localement

Pour simuler le pipeline CI/CD en local :

```bash
# 1. Code Quality
make phpstan
make phpcs
make phpmd
make pint

# 2. Tests Backend
cp .env.example .env.testing
php artisan key:generate --env=testing
php artisan migrate --env=testing
make pest
make test

# 3. Frontend Tests
npm run test

# 4. Frontend Build
npm run build

# 5. Security
composer audit
npm audit

# OU tout en une commande
make quality
```

---

## Badges de Statut

Ajoutez ces badges à votre `README.md` pour afficher le statut du pipeline :

```markdown
![CI/CD Pipeline](https://github.com/VOTRE_USERNAME/VOTRE_REPO/actions/workflows/ci.yml/badge.svg)
![PR Checks](https://github.com/VOTRE_USERNAME/VOTRE_REPO/actions/workflows/pr-checks.yml/badge.svg)
![Dependency Review](https://github.com/VOTRE_USERNAME/VOTRE_REPO/actions/workflows/dependency-review.yml/badge.svg)
```

---

## Bonnes Pratiques

### ✅ Avant de Commiter

```bash
# Fixer le style automatiquement
make fix

# Vérifier la qualité
make quality

# Si tout passe, commiter
git add .
git commit -m "Your message"
git push
```

### ✅ Workflow de Développement

1. **Créer une branche** depuis `develop`
   ```bash
   git checkout -b feature/my-feature
   ```

2. **Développer et tester localement**
   ```bash
   make quality
   npm test
   ```

3. **Commiter et pusher**
   ```bash
   git push origin feature/my-feature
   ```

4. **Créer une Pull Request**
   - Le pipeline `pr-checks.yml` s'exécute automatiquement
   - Vérifier les commentaires du bot
   - Corriger les problèmes détectés

5. **Merger après validation**
   - Le pipeline complet `ci.yml` s'exécute
   - Merger uniquement si tous les checks passent ✅

---

## Résolution des Problèmes

### ❌ PHPStan Échoue

```bash
# Exécuter localement pour voir les erreurs détaillées
make phpstan

# Fixer les erreurs de typage
# Ajouter des docblocks si nécessaire
```

### ❌ Tests Backend Échouent

```bash
# Configurer l'environnement de test
cp .env.example .env.testing
php artisan key:generate --env=testing

# Exécuter les migrations
php artisan migrate --env=testing --seed

# Lancer les tests
make pest
```

### ❌ Tests Frontend Échouent

```bash
# Installer les dépendances
npm ci

# Exécuter les tests en mode watch
npm run test:watch
```

### ❌ Vulnérabilités Détectées

```bash
# PHP
composer audit
composer update --with-dependencies

# npm
npm audit
npm audit fix
# ou pour forcer
npm audit fix --force
```

---

## Métriques et Monitoring

### GitHub Actions Dashboard

Accédez à l'onglet **Actions** de votre repository pour :
- ✅ Voir l'historique des workflows
- 📊 Consulter les durées d'exécution
- 📥 Télécharger les artifacts (coverage, reports)
- 🔄 Re-exécuter les workflows échoués

### Artifacts Disponibles

- **coverage-report** : Rapport de couverture XML
- **frontend-build** : Assets frontend compilés
- **dependency-reports** : Rapports de dépendances obsolètes
- **security-reports** : Audits de sécurité JSON/texte
- **license-reports** : Rapports de licences

---

## Performance du Pipeline

### Temps d'Exécution Moyens

| Job | Durée |
|-----|-------|
| Code Quality | 2-4 min |
| Backend Tests (PHP 8.2) | 3-5 min |
| Backend Tests (PHP 8.3) | 3-5 min |
| Frontend Tests (Node 18) | 2-3 min |
| Frontend Tests (Node 20) | 2-3 min |
| Security Checks | 1-2 min |
| Full Quality Check | 4-6 min |
| **Total** | **~12-15 min** (parallèle) |

### Optimisations Mises en Place

✅ **Cache Composer** - Réduit le temps d'installation des dépendances PHP
✅ **Cache npm** - Accélère l'installation des packages JavaScript
✅ **Exécution parallèle** - Les jobs indépendants s'exécutent simultanément
✅ **Matrix strategy** - Teste plusieurs versions PHP/Node en parallèle
✅ **Services MySQL** - Base de données en container pour les tests

---

## Contributions

Lors de l'ajout de nouvelles fonctionnalités :

1. ✅ Ajouter des tests (Pest/Jest)
2. ✅ Vérifier que `make quality` passe
3. ✅ Mettre à jour la documentation si nécessaire
4. ✅ Créer une PR avec une description claire

Le pipeline s'assurera que votre code respecte tous les standards de qualité et de sécurité ! 🚀

---

## Support

Pour toute question ou problème avec le pipeline CI/CD :
1. Consultez les logs dans l'onglet Actions
2. Exécutez les commandes localement pour reproduire
3. Vérifiez cette documentation
4. Contactez l'équipe DevOps si le problème persiste

---

**Dernière mise à jour :** 2025-10-11
**Version du pipeline :** 1.0.0
