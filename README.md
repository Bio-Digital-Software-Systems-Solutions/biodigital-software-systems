# AIG-App - Organizational Management Platform

![CI/CD Pipeline](https://github.com/VOTRE_USERNAME/icc-munich/actions/workflows/ci.yml/badge.svg)
![PR Checks](https://github.com/VOTRE_USERNAME/icc-munich/actions/workflows/pr-checks.yml/badge.svg)
![Dependency Review](https://github.com/VOTRE_USERNAME/icc-munich/actions/workflows/dependency-review.yml/badge.svg)
[![Laravel](https://img.shields.io/badge/Laravel-12-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://www.php.net)
[![React](https://img.shields.io/badge/React-18-blue.svg)](https://reactjs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.0-blue.svg)](https://www.typescriptlang.org)

> **Note:** Remplacez `VOTRE_USERNAME` dans les badges ci-dessus par votre nom d'utilisateur GitHub.

## À Propos

AIG-App est une plateforme complète de gestion organisationnelle construite avec Laravel, Inertia.js, React et TypeScript. Elle offre une suite d'outils pour gérer les événements, les livres, les articles, et faciliter la communication entre les membres de l'organisation.

### Fonctionnalités Principales

- **Gestion d'Événements** - Créer, gérer et participer à des événements organisationnels
- **Système de Bibliothèque** - Gestion de prêts de livres avec suivi de disponibilité
- **Système d'Articles** - Création et partage de contenu
- **Chat en Temps Réel** - Messagerie instantanée entre utilisateurs
- **Gestion des Utilisateurs** - Système de permissions basé sur les rôles (Spatie)
- **Internationalisation** - Support multi-langues (FR/EN/DE)
- **Thème Clair/Sombre** - Basculement automatique avec préférence système
- **Monitoring** - Intégration Sentry pour le suivi des erreurs
- **Activity Log** - Suivi de toutes les modifications sur les modèles

## Stack Technologique

### Backend
- **Laravel 12** - Framework PHP
- **Inertia.js** - SSR avec expérience SPA
- **Spatie Laravel Permission** - Gestion des rôles et permissions
- **Spatie Laravel Activity Log** - Tracking des activités
- **Sentry** - Monitoring des erreurs et performances
- **MySQL** - Base de données

### Frontend
- **React 18** - Bibliothèque UI
- **TypeScript** - Typage statique
- **TailwindCSS** - Framework CSS
- **Heroicons** - Bibliothèque d'icônes
- **React i18next** - Internationalisation
- **Vite** - Outil de build

### Qualité de Code
- **PHPStan** (niveau 10) - Analyse statique PHP
- **PHP_CodeSniffer** - Conformité PSR-12
- **PHPMD** - Détection de complexité et design
- **Laravel Pint** - Formatage du code
- **Pest / PHPUnit** - Tests backend
- **Jest** - Tests frontend

## Installation

### Prérequis
- PHP 8.2 ou supérieur
- Composer 2.x
- Node.js 18 ou 20
- MySQL 8.0
- Make (pour les commandes du Makefile)

### Configuration

1. **Cloner le repository**
   ```bash
   git clone <votre-repo-url>
   cd icc-munich
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   npm ci
   ```

3. **Configurer l'environnement**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configurer la base de données**
   ```bash
   # Éditer .env avec vos paramètres MySQL
   php artisan migrate --seed
   ```

5. **Compiler les assets**
   ```bash
   npm run build
   ```

6. **Démarrer le serveur**
   ```bash
   php artisan serve
   # ou pour l'environnement complet de dev
   composer dev
   ```

## Développement

### Commandes Makefile

```bash
# Analyse statique et qualité de code
make phpstan       # PHPStan analyse statique (niveau 10)
make phpcs         # PHP_CodeSniffer (conformité PSR-12)
make phpmd         # PHP Mess Detector (complexité, design)
make pint          # Laravel Pint (vérification du style)

# Tests
make pest          # Exécuter les tests Pest
make test          # Exécuter les tests PHPUnit (legacy)

# Utilitaires
make fix           # Auto-fix du style de code avec Pint
make clear         # Vider tous les caches Laravel
make quality       # Exécuter tous les checks de qualité
make quality-fix   # Fixer le code + exécuter tous les checks
make help          # Afficher l'aide
```

### Workflow de Développement

1. **Créer une branche de feature**
   ```bash
   git checkout -b feature/ma-fonctionnalite
   ```

2. **Développer avec vérification continue**
   ```bash
   # Vérifier la qualité du code
   make quality

   # Exécuter les tests
   make pest
   npm test
   ```

3. **Fixer automatiquement le style**
   ```bash
   make fix
   ```

4. **Commiter et pusher**
   ```bash
   git add .
   git commit -m "feat: ma nouvelle fonctionnalité"
   git push origin feature/ma-fonctionnalite
   ```

5. **Créer une Pull Request**
   - Les checks CI/CD s'exécutent automatiquement
   - Le bot analyse le code et commente la PR
   - Les labels de taille sont ajoutés automatiquement

## CI/CD Pipeline

Le projet utilise **GitHub Actions** pour l'intégration et la livraison continues. Le pipeline s'exécute automatiquement à chaque push et pull request.

### Workflows

- **`ci.yml`** - Pipeline principal (tests, qualité, sécurité)
- **`pr-checks.yml`** - Analyse des PRs avec commentaires automatiques
- **`dependency-review.yml`** - Revue des dépendances (hebdomadaire)

### Ce qui est vérifié

✅ Analyse statique avec PHPStan (niveau 10)
✅ Conformité PSR-12 avec PHP_CodeSniffer
✅ Complexité du code avec PHPMD
✅ Style de code avec Laravel Pint
✅ Tests backend (Pest + PHPUnit) sur PHP 8.2 et 8.3
✅ Tests frontend (Jest) sur Node.js 18 et 20
✅ Compilation TypeScript
✅ Build de production
✅ Audit de sécurité (Composer + npm)
✅ Vérification des licences

**Documentation complète :** Voir [CI_CD.md](./CI_CD.md)

## Tests

### Backend
```bash
# Tous les tests
make pest
make test

# Avec coverage
vendor/bin/pest --coverage
```

### Frontend
```bash
# Tous les tests
npm test

# Mode watch
npm run test:watch

# Avec coverage
npm run test:coverage
```

## Structure du Projet

```
icc-munich/
├── app/                    # Code Laravel
│   ├── Http/Controllers/  # Contrôleurs
│   ├── Models/            # Modèles Eloquent
│   └── Policies/          # Policies d'autorisation
├── database/
│   ├── migrations/        # Migrations
│   └── seeders/           # Seeders
├── resources/
│   ├── js/                # Code React/TypeScript
│   │   ├── Components/   # Composants React
│   │   ├── Layouts/      # Layouts
│   │   └── Pages/        # Pages Inertia
│   └── views/            # Templates Blade
├── tests/
│   ├── Feature/          # Tests de features
│   └── Unit/             # Tests unitaires
├── .github/workflows/    # Workflows GitHub Actions
├── Makefile              # Commandes de qualité
└── CI_CD.md             # Documentation CI/CD
```

## Permissions et Rôles

### Rôles Disponibles
- **admin** - Accès complet à toutes les fonctionnalités
- **project-manager** - Gestion des événements et projets
- **event-manager** - Gestion des événements uniquement
- **writer** - Création et gestion d'articles
- **member** - Accès de base (visualisation, participation, location)

### Permissions Principales
- Events: `view`, `create`, `edit`, `delete`
- Books: `view`, `manage-library`, `rent`
- Articles: `view`, `create`, `edit`, `delete`
- Chat: `use-chat`

## Monitoring

### Sentry
L'application est configurée avec Sentry pour :
- Suivi des erreurs en temps réel
- Monitoring des performances
- Contexte utilisateur automatique
- Breadcrumbs pour le debugging

**Documentation :** Voir [SENTRY.md](./SENTRY.md)

### Activity Log
Toutes les modifications sur les 52 modèles de l'application sont tracées avec Spatie Activity Log :
- Création, modification, suppression
- Attributs modifiés uniquement
- Historique complet d'audit

## Internationalisation

L'application supporte trois langues :
- 🇫🇷 Français (FR)
- 🇬🇧 Anglais (EN)
- 🇩🇪 Allemand (DE)

Les fichiers de traduction se trouvent dans `resources/js/locales/`.

## UI Guidelines

### Composants à Utiliser

**NE JAMAIS utiliser :**
- `confirm()` / `window.confirm()` pour les confirmations
- `alert()` pour les notifications

**TOUJOURS utiliser :**
- `DeleteConfirmationDialog` pour les confirmations de suppression
- `toast` (sonner) pour les notifications

**Documentation :** Voir [UI_GUIDELINES.md](./UI_GUIDELINES.md)

## Sécurité

- Toutes les routes protégées par authentification
- Autorisation basée sur les permissions
- Validation des entrées sur tous les formulaires
- Protection CSRF activée
- Pas de secrets en version control
- Audit de sécurité automatique via CI/CD

### Signaler une Vulnérabilité

Si vous découvrez une vulnérabilité de sécurité, veuillez envoyer un email à [security@votre-domaine.com].

## Contribution

1. Fork le projet
2. Créer une branche de feature (`git checkout -b feature/AmazingFeature`)
3. Commiter vos changements (`git commit -m 'feat: Add some AmazingFeature'`)
4. Pusher vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

**Important :** Assurez-vous que `make quality` passe avant de créer une PR.

## Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## Support

Pour obtenir de l'aide :
- Consultez la [documentation CI/CD](./CI_CD.md)
- Consultez le [guide du projet](./CLAUDE.md)
- Ouvrez une issue sur GitHub

---

**Développé avec ❤️ en utilisant Laravel, React et TypeScript**
