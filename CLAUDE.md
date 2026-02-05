# AIG-App Development Notes

## Project Overview
AIG-App is a comprehensive Laravel + Inertia + React + TypeScript application for organizational management with the following key features:

- **Event Management**: Create, manage, and participate in organizational events
- **Book Rental System**: Library management with book lending capabilities
- **Article System**: Content creation and sharing
- **Chat Functionality**: Real-time messaging between users
- **User Management**: Role-based permissions using Spatie Laravel Permission
- **Internationalization**: Multi-language support (FR/EN/DE)
- **Theme Switching**: Dark/Light mode with system preference support

## Technology Stack

### Backend
- **Laravel 12**: PHP framework
- **Inertia.js**: Server-side rendering with SPA-like experience
- **Spatie Laravel Permission**: Role and permission management
- **Spatie Laravel Activity Log**: Activity tracking for all models
- **Sentry**: Error tracking and performance monitoring
- **MySQL**: Database

### Frontend
- **React 18**: UI framework
- **TypeScript**: Type safety
- **TailwindCSS**: Styling framework with dark mode support
- **Heroicons**: Icon library
- **React i18next**: Internationalization
- **Vite**: Build tool

## Key Commands

### Development
```bash
# Start development server
php artisan serve
npm run dev

# Database operations
php artisan migrate
php artisan db:seed

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test files
php artisan test --filter=EventControllerTest
php artisan test --filter=BookControllerTest

# Run with coverage
php artisan test --coverage
```

### Build
```bash
# Production build
npm run build
php artisan optimize
```

## Architecture

### Database Schema
- **Users**: Extended with first_name, last_name, phone, address
- **Events**: With polymorphic address relationship and participant many-to-many
- **Books**: With categories, libraries relationship, and rental tracking
- **Articles**: With categories, author relationship, and publication status
- **Chat**: Rooms and messages for real-time communication
- **Permissions**: Granular role-based access control

### Key Models
- `User`: Extended with roles, permissions, and relationships
- `Event`: Event management with participants and addresses
- `Book`/`BookRental`: Library system with availability tracking
- `Article`: Content management with publication workflow
- `ChatRoom`/`ChatMessage`: Messaging system

### Controllers
- Resource controllers following RESTful patterns
- Proper authorization middleware
- Comprehensive validation
- Inertia responses with proper data loading

## Permissions System

### Roles
- **super-admin**: Accès complet à toutes les fonctionnalités + gestion utilisateurs
- **admin**: Accès complet à toutes les fonctionnalités
- **writer**: Création et gestion d'articles
- **project-manager**: Gestion des événements et projets
- **event-manager**: Gestion des événements uniquement
- **library-manager**: Gestion de la bibliothèque
- **group-leader**: Chef de groupe
- **department-leader**: Chef de département
- **impact-family-leader**: Chef de famille d'impact
- **pastor**: Accès aux soins pastoraux
- **teacher**: Espace enseignant
- **student**: Espace étudiant
- **member**: Accès de base (voir, participer, louer)
- **employee**: Employé
- **star**: Membre star
- **mlr-agent**: Agent MLR

### Key Permissions
- **Events**: view, create, edit, delete events
- **Books**: view books, manage library, rent books
- **Articles**: view, create, edit, delete articles
- **Chat**: use chat functionality
- **General**: view departments, programs, stocks

### Role Enum Synchronization (PHP ↔ TypeScript)

**IMPORTANT**: Les enums de rôles doivent être synchronisés entre PHP et TypeScript.

| Fichier PHP | Fichier TypeScript |
|-------------|-------------------|
| `app/Enums/Role.php` | `resources/js/Enums/Role.ts` |

**Format des valeurs** : kebab-case (ex: `super-admin`, `project-manager`)

```php
// PHP - app/Enums/Role.php
enum Role: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case WRITER = 'writer';
    // ...
}
```

```typescript
// TypeScript - resources/js/Enums/Role.ts
export enum Role {
    SUPER_ADMIN = 'super-admin',
    ADMIN = 'admin',
    WRITER = 'writer',
    // ...
}
```

**⚠️ Si vous ajoutez un nouveau rôle** :
1. Ajouter dans `app/Enums/Role.php`
2. Ajouter dans `resources/js/Enums/Role.ts` avec la **même valeur**
3. Ajouter dans le seeder `database/seeders/RoleSeeder.php`
4. Exécuter `npm run build` pour recompiler le frontend

**Fonctions helper disponibles** (TypeScript) :
- `hasRole(userRoles, Role.SUPER_ADMIN)` - vérifie un rôle spécifique
- `hasAnyRole(userRoles, [Role.ADMIN, Role.SUPER_ADMIN])` - vérifie plusieurs rôles
- `isAdmin(userRoles)` - vérifie si admin ou super-admin

## Frontend Structure

### Components
- **Layouts**: DashboardLayout with sidebar navigation
- **Pages**: Organized by feature (Events, Books, Articles, Chat)
- **Components**: Reusable UI components (Carousel, FeatureCard, etc.)
- **Utilities**: Theme switcher, language switcher, internationalization

### Routing
- All routes protected by authentication
- Permission-based access control
- RESTful resource routes with additional custom endpoints

## Features Implemented

### ✅ Completed Features
1. **Project Setup**: Laravel + Inertia + React + TypeScript configuration
2. **Authentication**: Laravel Breeze integration
3. **Database**: All migrations and model relationships
4. **Permissions**: Roles and permissions seeder
5. **Landing Page**: Carousel and feature showcase
6. **Dashboard**: Statistics, quick actions, recent activity
7. **Event Management**: Full CRUD with participation system
8. **Book System**: Library management with rental functionality
9. **Article System**: Content creation with categories and status
10. **Chat System**: Real-time messaging with rooms
11. **Internationalization**: Multi-language support (FR/EN/DE)
12. **Theme System**: Dark/Light mode with system preference
13. **Testing**: Comprehensive feature and unit tests

### 🧪 Testing Coverage
- **Feature Tests**: EventController, BookController with permissions
- **Unit Tests**: User model, Event model relationships
- **Authorization**: Permission-based access testing
- **Validation**: Form validation and business logic testing

### 📊 Velocity Statistics

La vélocité mesure la productivité en comptant les tâches terminées sur une période donnée.

#### Département (DepartmentController)

**Formule** : Nombre de tâches terminées ce mois vs mois dernier

```php
// Tâches terminées CE mois
$completedThisMonth = DepartmentTodo::where('department_id', $id)
    ->where('status', 'completed')
    ->where('completed_at', '>=', now()->startOfMonth())
    ->count();

// Tâches terminées le mois DERNIER
$completedLastMonth = DepartmentTodo::where('department_id', $id)
    ->where('status', 'completed')
    ->whereBetween('completed_at', [
        now()->subMonth()->startOfMonth(),
        now()->subMonth()->endOfMonth()
    ])
    ->count();

// Évolution en pourcentage
$velocityChange = $completedLastMonth > 0
    ? (($completedThisMonth - $completedLastMonth) / $completedLastMonth) * 100
    : ($completedThisMonth > 0 ? 100 : 0);
```

**Exemple** :
- Janvier : 12 tâches terminées
- Février : 15 tâches terminées
- Évolution : ((15 - 12) / 12) × 100 = **+25%**

**Affichage** : `15 tâches/mois` avec `↑ 25% vs mois dernier (12)`

#### Projet (ProjectStatisticsService)

**Formule** : Moyenne de tâches terminées par période

```php
// Vélocité quotidienne (30 derniers jours)
$dailyTasks = $completedTasks->filter(fn($t) =>
    $t->updated_at->between(now()->subDays(30), now())
)->count();
$dailyAverage = round($dailyTasks / 30, 1);

// Vélocité hebdomadaire (8 dernières semaines)
$weeklyTasks = $completedTasks->filter(fn($t) =>
    $t->updated_at->between(now()->subWeeks(8), now())
)->count();
$weeklyAverage = round($weeklyTasks / 8, 1);

// Vélocité mensuelle (12 derniers mois)
$monthlyTasks = $completedTasks->filter(fn($t) =>
    $t->updated_at->between(now()->subMonths(12), now())
)->count();
$monthlyAverage = round($monthlyTasks / 12, 1);
```

**Exemple** :
- 30 tâches terminées en 30 jours → `1.0 tâche/jour`
- 30 tâches terminées en 8 semaines → `3.8 tâches/semaine`
- 30 tâches terminées en 12 mois → `2.5 tâches/mois`

#### Jauge Speedometer

La jauge affiche la vélocité avec :
- **Échelle fixe** : 0 à 300
- **Graduations** : tous les 20, labels majeurs (0, 100, 200, 300) en gras
- **Couleur de l'aiguille** selon le ratio valeur/max :
  - Vert (`#10b981`) : ratio < 33%
  - Orange (`#f59e0b`) : ratio < 66%
  - Rouge (`#ef4444`) : ratio ≥ 66%

#### Seeders de données de test

**Département (DepartmentTodo) :**
```bash
php artisan db:seed --class=DepartmentTodoSeeder
```
Crée ~200 tâches par département sur 12 mois avec distribution réaliste des statuts.

**Projets, Tasks, Sprints, Epics :**
```bash
php artisan db:seed --class=ProjectTaskHistoricalSeeder
```
Crée ~1000 tâches de projet sur 12 mois avec :
- Distribution réaliste des statuts (85% terminées pour les anciennes tâches)
- Sprints avec dates historiques
- Epics avec stories associées
- Données de vélocité (daily, weekly, monthly)

### 🔍 Monitoring & Debugging
1. **Sentry Integration**: Real-time error tracking and performance monitoring
   - Automatic exception capturing
   - User context tracking
   - Performance traces
   - Breadcrumbs for debugging
   - See `SENTRY.md` for complete documentation
2. **Activity Log**: Track all model changes with Spatie Activity Log
   - All 52 models configured with logging
   - Tracks create, update, and delete operations
   - Only logs changed attributes
   - Complete audit trail available

### 📱 Notification System (SMS, WhatsApp, Telegram)

L'application supporte plusieurs canaux de notification pour les rappels de rendez-vous.

#### Configuration

Variables d'environnement requises dans `.env` :

```env
# Twilio SMS/WhatsApp
TWILIO_ACCOUNT_SID=your_sid
TWILIO_AUTH_TOKEN=your_token
TWILIO_FROM_NUMBER=+1234567890
TWILIO_WHATSAPP_FROM=+1234567890
SMS_INTEGRATION_ENABLED=true
WHATSAPP_INTEGRATION_ENABLED=true

# Telegram Bot
TELEGRAM_INTEGRATION_ENABLED=true
TELEGRAM_BOT_TOKEN=your_bot_token_from_botfather
TELEGRAM_BOT_USERNAME=YourBotUsername
```

#### Services disponibles

| Service | Classe | Description |
|---------|--------|-------------|
| SMS/WhatsApp | `AppointmentSmsNotificationService` | Via Twilio API |
| Telegram | `TelegramNotificationService` | Via Telegram Bot API |

#### Telegram Bot API

**Création du bot :**
1. Ouvrir Telegram et chercher **@BotFather**
2. Envoyer `/newbot` et suivre les instructions
3. Copier le token API fourni dans `TELEGRAM_BOT_TOKEN`
4. Copier le username du bot dans `TELEGRAM_BOT_USERNAME`

**Structure des fichiers Telegram :**
```
app/
├── Services/
│   └── TelegramNotificationService.php    # Service principal
├── Notifications/
│   ├── Channels/
│   │   └── TelegramChannel.php            # Canal Laravel
│   └── Messages/
│       └── TelegramMessage.php            # Builder de messages
```

**Utilisation du service :**
```php
use App\Services\TelegramNotificationService;

$telegram = app(TelegramNotificationService::class);

// Vérifier si activé
if ($telegram->isEnabled()) {
    // Envoyer un message
    $telegram->sendMessage($chatId, 'Votre message');

    // Envoyer un rappel de rendez-vous
    $telegram->sendReminder($appointment, $participant);

    // Envoyer à l'organisateur
    $telegram->sendOrganizerReminder($appointment);

    // Autres notifications
    $telegram->sendConfirmation($appointment, $participant);
    $telegram->sendCancellation($appointment, $participant);
    $telegram->sendInvitation($appointment, $participant, $confirmUrl, $declineUrl);
    $telegram->sendUpdate($appointment, $participant, $changes);
}
```

**Utilisation via Laravel Notifications :**
```php
use App\Notifications\AppointmentReminder;

// Le canal Telegram est ajouté automatiquement si l'utilisateur a configuré Telegram
$user->notify(new AppointmentReminder($appointment));
```

**Builder TelegramMessage :**
```php
use App\Notifications\Messages\TelegramMessage;

$message = TelegramMessage::create()
    ->line('Ligne de texte')
    ->bold('Texte en gras')
    ->italic('Texte en italique')
    ->link('Cliquez ici', 'https://example.com')
    ->lineBreak()
    ->content('Contenu supplémentaire')
    ->html()           // ou ->markdown() ou ->markdownV2()
    ->silent()         // Envoyer sans notification
    ->replyTo($msgId); // Répondre à un message
```

#### Champs de base de données

**Table `users` :**
- `telegram_chat_id` : ID du chat Telegram de l'utilisateur
- `telegram_username` : Username Telegram (optionnel)
- `telegram_notifications` : Préférence de notification (boolean)

**Table `appointments` :**
- `telegram_reminder_sent_at` : Timestamp du dernier rappel envoyé

#### Commande de rappels automatiques

```bash
# Envoyer les rappels 24h avant (par défaut)
php artisan appointments:send-reminders

# Envoyer les rappels 18h avant
php artisan appointments:send-reminders --hours=18

# Mode simulation (dry-run)
php artisan appointments:send-reminders --dry-run
```

La commande est planifiée automatiquement dans `routes/console.php` :
- 9h00 : rappels 24h avant
- 18h00 : rappels 18h avant

#### Tests

```bash
# Tests du service Telegram
php artisan test tests/Feature/TelegramNotificationServiceTest.php

# Tests du builder de messages
php artisan test tests/Unit/TelegramChannelTest.php
```

#### Obtenir le chat_id d'un utilisateur

Pour qu'un utilisateur reçoive des notifications Telegram, il doit :
1. Démarrer une conversation avec le bot en envoyant `/start`
2. L'application peut récupérer le `chat_id` via `$telegram->getUpdates()`

```php
// Récupérer les derniers messages envoyés au bot
$updates = $telegram->getUpdates();

foreach ($updates as $update) {
    $chatId = $update['message']['chat']['id'];
    $username = $update['message']['from']['username'] ?? null;
    // Sauvegarder dans la table users
}
```

## Development Guidelines

### Code Style
- Follow Laravel and React best practices
- Use TypeScript interfaces for type safety
- Implement proper error handling
- Use consistent naming conventions
- Comment complex business logic

### UI/UX Guidelines
- **NEVER use native `confirm()` or `window.confirm()` for delete confirmations**
- Always use the `DeleteConfirmationDialog` component from `/resources/js/Components/ui/delete-confirmation-dialog.tsx`
- **NEVER use native `alert()` for notifications**
- Always use `toast` from `sonner` library for success/error/warning/info messages
- Toasts are automatically styled for dark mode and positioned at top-right
- This provides a consistent, accessible, and visually appealing user experience
- See `/UI_GUIDELINES.md` for detailed implementation examples

### Security
- All routes protected by authentication middleware
- Permission-based authorization on controllers
- Input validation on all forms
- CSRF protection enabled
- No secrets in version control

### Performance
- Eager loading relationships to avoid N+1 queries
- Proper database indexing
- Image optimization for uploads
- Lazy loading for large datasets
- Caching strategies for static content

## Deployment Notes

### Environment Setup
- Configure database connection
- Set up file storage (public disk)
- Configure mail settings for notifications
- Set proper APP_ENV and APP_DEBUG values

### Production Checklist
- Run `php artisan optimize`
- Run `npm run build`
- Set up proper file permissions
- Configure web server (Nginx/Apache)
- Set up SSL certificates
- Configure backup strategies

## Troubleshooting

### Common Issues
- **NPM dependency conflicts**: Use `--legacy-peer-deps` flag
- **Permission errors**: Check file permissions and ownership
- **Database issues**: Run migrations and seeders
- **Cache issues**: Clear all Laravel caches

### Debug Commands
```bash
# View routes
php artisan route:list

# View permissions
php artisan permission:show

# Check model relationships
php artisan tinker
```

This application provides a solid foundation for organizational management with proper security, scalability, and maintainability considerations.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.8
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- tightenco/ziggy (ZIGGY) - v2
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- laravel/telescope (TELESCOPE) - v5
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/react (INERTIA) - v2
- react (REACT) - v18
- tailwindcss (TAILWINDCSS) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use `search-docs` with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with a query of 'form component resetting' for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== inertia-react/core rules ===

## Inertia + React

- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="react">

import { Link } from '@inertiajs/react'
<Link href="/">Home</Link>

</code-snippet>


=== inertia-react/v2/forms rules ===

## Inertia + React Forms

<code-snippet name="`<Form>` Component Example" lang="react">

import { Form } from '@inertiajs/react'

export default () => (
    <Form action="/users" method="post">
        {({
            errors,
            hasErrors,
            processing,
            wasSuccessful,
            recentlySuccessful,
            clearErrors,
            resetAndClearErrors,
            defaults
        }) => (
        <>
        <input type="text" name="name" />

        {errors.name && <div>{errors.name}</div>}

        <button type="submit" disabled={processing}>
            {processing ? 'Creating...' : 'Create User'}
        </button>

        {wasSuccessful && <div>User created successfully!</div>}
        </>
    )}
    </Form>
)

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v3 rules ===

## Tailwind 3

- Always use Tailwind CSS v3 - verify you're using only classes supported by this version.
</laravel-boost-guidelines>
