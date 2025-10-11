# Code Guidelines - AIG-App

## 📋 Table des Matières
- [Principes Généraux](#principes-généraux)
- [Structure du Code](#structure-du-code)
- [Conventions de Nommage](#conventions-de-nommage)
- [Logging et Débogage](#logging-et-débogage)
- [Gestion des Erreurs](#gestion-des-erreurs)
- [Performance](#performance)
- [Accessibilité](#accessibilité)
- [Sécurité](#sécurité)

## Principes Généraux

### DRY (Don't Repeat Yourself)
- Éviter la duplication de code
- Créer des composants/fonctions réutilisables
- Utiliser les utilitaires partagés dans `/resources/js/utils`

### KISS (Keep It Simple, Stupid)
- Privilégier la simplicité à la complexité
- Code lisible > Code clever
- Commenter uniquement ce qui n'est pas évident

### SOLID Principles
- **Single Responsibility**: Une fonction/classe = une responsabilité
- **Open/Closed**: Ouvert à l'extension, fermé à la modification
- **Dependency Inversion**: Dépendre des abstractions, pas des implémentations

## Structure du Code

### Organisation des Fichiers Frontend

```
resources/js/
├── Components/         # Composants réutilisables
│   ├── ui/            # Composants UI de base (shadcn/ui)
│   └── [Feature]/     # Composants spécifiques à une feature
├── Pages/             # Pages Inertia.js
├── Layouts/           # Layouts de l'application
├── Hooks/             # Custom React hooks
├── utils/             # Fonctions utilitaires
├── types/             # Types TypeScript
└── Enums/             # Enums TypeScript
```

### Organisation des Fichiers Backend

```
app/
├── Http/
│   ├── Controllers/   # Contrôleurs (logique métier minimale)
│   ├── Requests/      # Form Requests (validation)
│   └── Middleware/    # Middleware personnalisés
├── Models/            # Modèles Eloquent
├── Services/          # Services (logique métier complexe)
├── Repositories/      # Couche d'accès aux données (si nécessaire)
└── Enums/             # Enums PHP
```

## Conventions de Nommage

### TypeScript/React
- **Composants**: PascalCase (`UserProfile.tsx`)
- **Hooks**: camelCase avec préfixe 'use' (`useAuth.ts`)
- **Utils**: camelCase (`formatDate.ts`)
- **Types/Interfaces**: PascalCase (`User`, `AuthProps`)
- **Constants**: SCREAMING_SNAKE_CASE (`API_BASE_URL`)

### PHP/Laravel
- **Classes**: PascalCase (`UserController`)
- **Methods**: camelCase (`getUserProfile()`)
- **Variables**: camelCase (`$userData`)
- **Constants**: SCREAMING_SNAKE_CASE (`MAX_LOGIN_ATTEMPTS`)
- **Database**: snake_case (`user_profiles`, `created_at`)

## Logging et Débogage

### ❌ À ÉVITER
```typescript
console.log('User data:', userData);  // Ne jamais utiliser en production
```

### ✅ À UTILISER
```typescript
import { logger } from '@/utils/logger';

logger.debug('User data loaded', { userData });
logger.info('User logged in successfully');
logger.warn('Rate limit approaching');
logger.error('Failed to load user data', error);
```

### Contextes Spécifiques
```typescript
import { apiLogger, uiLogger } from '@/utils/logger';

apiLogger.info('API request completed', { endpoint, duration });
uiLogger.debug('Component rendered', { componentName, props });
```

## Gestion des Erreurs

### Frontend (TypeScript/React)
```typescript
try {
    const response = await api.post('/endpoint', data);
    toast.success('Opération réussie');
} catch (error) {
    logger.error('Operation failed', error);
    toast.error('Une erreur est survenue');
}
```

### Backend (PHP/Laravel)
```php
try {
    $result = $this->service->processData($data);
    CacheService::forgetPattern('data');
    return response()->json(['success' => true, 'data' => $result]);
} catch (\Exception $e) {
    Log::error('Data processing failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Processing failed'], 500);
}
```

## Performance

### Lazy Loading (Frontend)
```typescript
// ✅ Bon: Lazy load des composants lourds
import { LazyRichTextEditor, withLazyLoad } from '@/Components/LazyComponents';
const Editor = withLazyLoad(LazyRichTextEditor, 'Chargement...');

// ❌ Mauvais: Import direct de composants lourds
import RichTextEditor from '@/Components/RichTextEditor';
```

### Caching (Backend)
```php
// ✅ Bon: Utiliser CacheService pour les données fréquemment lues
$users = CacheService::remember(
    'users.list',
    fn() => User::with('roles')->get(),
    CacheService::MEDIUM_CACHE
);

// ✅ Invalider le cache après modification
CacheService::forgetPattern('users');
```

### Optimisation des Requêtes
```php
// ✅ Bon: Eager loading pour éviter N+1
$books = Book::with(['category', 'libraries'])->get();

// ❌ Mauvais: Lazy loading cause N+1
$books = Book::all();
foreach ($books as $book) {
    echo $book->category->name;  // Requête pour chaque book
}
```

## Accessibilité

### Composants Accessibles
```typescript
// ✅ Bon: ARIA labels et roles appropriés
<button
    onClick={handleClick}
    aria-label="Fermer le dialogue"
    aria-expanded={isOpen}
>
    <XMarkIcon className="h-6 w-6" aria-hidden="true" />
</button>

// ✅ Labels cachés mais accessibles
<label htmlFor="search" className="sr-only">
    Rechercher
</label>
<input id="search" type="search" />
```

### Navigation au Clavier
```typescript
// ✅ Utiliser SkipLink pour navigation rapide
import SkipLink from '@/Components/SkipLink';

<SkipLink target="#main-content" />
<main id="main-content">...</main>
```

## Sécurité

### Validation des Entrées (Backend)
```php
// ✅ Toujours valider les données entrantes
$validated = $request->validate([
    'email' => 'required|email|max:255',
    'password' => ['required', 'min:8', Rules\Password::defaults()],
]);
```

### Protection XSS (Frontend)
```typescript
// ✅ Utiliser Purifier pour le HTML utilisateur
import Purifier from 'mews/purifier';

$sanitizedContent = Purifier::clean($request->content);
```

### Protection CSRF
```php
// ✅ Toujours utiliser les tokens CSRF
// Les formulaires Inertia.js incluent automatiquement le token
post(route('action'), data);  // Token CSRF inclus
```

### Upload de Fichiers
```php
// ✅ Valider type, taille et contenu
$request->validate([
    'image' => 'required|image|mimes:jpeg,png,jpg|max:10240',
]);

// ✅ Utiliser FileUploadService pour sécurité
$path = $fileUploadService->uploadImage($file, 'directory');
```

## Tests

### Tests Unitaires
```php
// ✅ Tester les cas normaux et edge cases
public function test_user_can_login_with_valid_credentials()
{
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
}
```

### Tests d'Intégration
```php
// ✅ Tester les flux complets
public function test_user_can_rent_book_flow()
{
    $user = User::factory()->create();
    $book = Book::factory()->create(['stock_quantity' => 5]);

    $this->actingAs($user)
        ->post(route('books.rent', $book), ['rental_days' => 7])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('book_rentals', [
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]);
}
```

## Commits Git

### Messages de Commit
```
feat: Add user profile editing
fix: Resolve book rental validation error
refactor: Improve cache service performance
docs: Update API documentation
test: Add tests for authentication flow
style: Format code with Pint
perf: Optimize database queries
```

### Bonnes Pratiques
- Commits atomiques (une fonctionnalité = un commit)
- Messages descriptifs et concis
- Référencer les issues (`fixes #123`)
- Ne jamais commiter de secrets (`.env`, credentials)

## Code Review Checklist

Avant de soumettre une PR, vérifier:

- [ ] Pas de `console.log` en production
- [ ] Tests ajoutés/mis à jour
- [ ] Documentation mise à jour si nécessaire
- [ ] ARIA attributes pour composants interactifs
- [ ] Cache invalidé après modifications de données
- [ ] Eager loading pour éviter N+1
- [ ] Validation des inputs
- [ ] Gestion d'erreurs appropriée
- [ ] Messages d'erreur traduits
- [ ] Dark mode supporté (Frontend)

---

**Dernière mise à jour:** 2025-10-10
**Mainteneur:** Équipe AIG-App
