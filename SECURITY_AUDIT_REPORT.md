# Rapport d'Audit de Sécurité - AIG-App (ICC München)

**Date:** 2025-10-07
**Application:** AIG-App - Plateforme de gestion organisationnelle
**Technologie:** Laravel 12 + Inertia.js + React + TypeScript
**Auditeur:** Claude Code Security Audit

---

## 🔴 Vulnérabilités CRITIQUES (Action Immédiate Requise)

### 1. **Exposition du fichier `.env` en production**
**Sévérité:** 🔴 CRITIQUE
**Localisation:** `.env` (ligne 3-4)
**Problème:**
```env
APP_KEY=base64:Zg8Yaw8XnAVhWdWwnc//4HkIEYYuNYxeP+Bw98WxBXc=
APP_DEBUG=true
```

**Risques:**
- Clé d'encryption de l'application exposée
- Mode debug activé révèle des informations sensibles (stack traces, variables d'environnement)
- Possibilité de déchiffrer toutes les données chiffrées (cookies de session, données utilisateur)

**Solution:**
```bash
# 1. Régénérer la clé APP_KEY
php artisan key:generate

# 2. Modifier .env pour la production
APP_DEBUG=false
APP_ENV=production

# 3. S'assurer que .env n'est JAMAIS commité dans Git
# Vérifier que .env est dans .gitignore
echo ".env" >> .gitignore
git rm --cached .env 2>/dev/null || true

# 4. Utiliser des variables d'environnement du serveur en production
# Ne jamais stocker .env sur le serveur de production
```

**Impact si exploité:** Prise de contrôle totale de l'application, vol de données utilisateurs

---

### 2. **Absence d'autorisation au niveau de la ressource (IDOR)**
**Sévérité:** 🔴 CRITIQUE
**Localisation:** Plusieurs controllers

**Problème dans EventController.php (ligne 169-185):**
```php
public function toggleParticipation(Event $event): RedirectResponse
{
    $user = Auth::user();
    // ❌ Aucune vérification si l'utilisateur peut participer à cet événement
    // ❌ Aucune vérification de permission
    if ($event->participants->contains($user)) {
        $event->participants()->detach($user);
        // ...
    }
}
```

**Problème dans ArticleController.php (ligne 277-296):**
```php
public function destroy(Article $article): RedirectResponse
{
    // ✅ Vérifie la permission 'delete articles' via middleware
    // ❌ MAIS ne vérifie pas si l'utilisateur est propriétaire de l'article
    // Un admin peut supprimer les articles de n'importe qui
    $article->delete();
    // ...
}
```

**Problème dans ChatController.php (ligne 95-122):**
```php
public function getMessages(ChatRoom $room, Request $request): JsonResponse
{
    // ✅ Vérifie si l'utilisateur est participant
    if (! $room->participants()->where('user_id', Auth::id())->exists()) {
        abort(403);
    }
    // ✅ Bonne implémentation
}
```

**Solution:**

1. **Créer des Policies pour chaque ressource:**

```bash
php artisan make:policy EventPolicy --model=Event
php artisan make:policy ArticlePolicy --model=Article
php artisan make:policy BookPolicy --model=Book
php artisan make:policy TrainingPolicy --model=Training
```

2. **Exemple EventPolicy.php:**
```php
<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view events');
    }

    public function view(User $user, Event $event): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir les événements publics
        if ($event->is_public) {
            return $user->can('view events');
        }

        // Les événements privés sont visibles par le créateur et les participants
        return $user->id === $event->user_id
            || $event->participants->contains($user)
            || $user->can('manage event participants');
    }

    public function create(User $user): bool
    {
        return $user->can('create events');
    }

    public function update(User $user, Event $event): bool
    {
        // Seul le créateur ou quelqu'un avec permission 'edit events' peut modifier
        return $user->id === $event->user_id || $user->can('edit events');
    }

    public function delete(User $user, Event $event): bool
    {
        // Seul le créateur ou quelqu'un avec permission 'delete events' peut supprimer
        return $user->id === $event->user_id || $user->can('delete events');
    }

    public function participate(User $user, Event $event): bool
    {
        // Vérifier si l'événement est public ou si l'utilisateur est invité
        if (!$event->is_public) {
            return $event->participants->contains($user) || $user->can('manage event participants');
        }
        return true;
    }
}
```

3. **Utiliser les Policies dans les controllers:**

```php
// Dans EventController.php
public function update(Request $request, Event $event): RedirectResponse
{
    // Vérifier l'autorisation avec Policy
    $this->authorize('update', $event);

    // Reste du code...
}

public function destroy(Event $event): RedirectResponse
{
    $this->authorize('delete', $event);

    $event->delete();
    return redirect()->route('events.index');
}

public function toggleParticipation(Event $event): RedirectResponse
{
    // Vérifier si l'utilisateur peut participer
    $this->authorize('participate', $event);

    // Reste du code...
}
```

4. **Enregistrer les Policies dans AuthServiceProvider:**
```php
protected $policies = [
    Event::class => EventPolicy::class,
    Article::class => ArticlePolicy::class,
    Book::class => BookPolicy::class,
    Training::class => TrainingPolicy::class,
];
```

**Impact si exploité:**
- Utilisateurs non autorisés peuvent lire/modifier/supprimer des ressources
- Escalade de privilèges
- Violation de confidentialité des données

---

### 3. **Injection SQL potentielle via recherche**
**Sévérité:** 🟠 HAUTE
**Localisation:** ArticleController.php (ligne 34-38), BookController.php (ligne 32-37)

**Problème dans ArticleController:**
```php
if ($request->search) {
    $query->where(function ($q) use ($request) {
        $q->where('title', 'like', "%{$request->search}%")
          ->orWhere('content', 'like', "%{$request->search}%");
    });
}
```

**Analyse:**
- ✅ Laravel échappe automatiquement les paramètres avec l'Eloquent ORM
- ✅ Utilisation correcte de bindings paramétrés
- ⚠️ MAIS absence de validation du paramètre `search`

**Risque secondaire:**
- ReDoS (Regular Expression Denial of Service) si la recherche est trop complexe
- Surcharge de la base de données avec des requêtes lourdes

**Solution:**
```php
// Dans ArticleController.php - Méthode index()
public function index(Request $request): Response
{
    // Valider les paramètres de recherche
    $validated = $request->validate([
        'search' => 'nullable|string|max:255',
        'category' => 'nullable|integer|exists:categories,id',
        'status' => 'nullable|in:published,draft',
    ]);

    $query = Article::with(['category', 'user', 'tags'])
        ->withCount('likes');

    if (!empty($validated['search'])) {
        // Limiter la longueur et nettoyer les caractères spéciaux
        $searchTerm = trim($validated['search']);
        $searchTerm = preg_replace('/[^a-zA-Z0-9\s\-_àâäéèêëïîôöùûüÿæœç]/', '', $searchTerm);

        $query->where(function ($q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
              ->orWhere('content', 'like', "%{$searchTerm}%");
        });
    }

    if (!empty($validated['category'])) {
        $query->where('category_id', $validated['category']);
    }

    if (!empty($validated['status'])) {
        if ($validated['status'] === 'published') {
            $query->whereNotNull('published_at');
        } elseif ($validated['status'] === 'draft') {
            $query->whereNull('published_at');
        }
    }

    // Le reste du code...
}
```

**Impact si exploité:** Peu probable avec Eloquent, mais défense en profondeur nécessaire

---

### 4. **Gestion des fichiers uploadés non sécurisée**
**Sévérité:** 🔴 CRITIQUE
**Localisation:** ArticleController.php (ligne 102-112), TrainingController.php (ligne 131-134)

**Problème dans ArticleController:**
```php
// Ligne 95-96
'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
'video_file' => 'nullable|file|mimes:mp4,mov,avi,wmv,flv,webm|max:50000',

// Ligne 104-106
if ($request->hasFile('cover_image')) {
    $coverImagePath = $request->file('cover_image')->store('articles/covers', 'public');
}

// ❌ Pas de validation du contenu réel du fichier
// ❌ Pas de génération de nom aléatoire
// ❌ Fichiers stockés dans public/ accessibles directement
```

**Risques:**
- Upload de fichiers malveillants déguisés (PHP shell, malware)
- Exécution de code arbitraire si le serveur est mal configuré
- Path traversal attacks
- DoS via upload de fichiers très lourds

**Solution complète:**

1. **Créer un service de validation de fichiers:**

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class FileUploadService
{
    private const MAX_IMAGE_SIZE = 2048; // 2MB
    private const MAX_VIDEO_SIZE = 50000; // 50MB

    private const ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const ALLOWED_VIDEO_MIMES = ['video/mp4', 'video/quicktime', 'video/x-msvideo'];

    /**
     * Upload et sécuriser une image
     */
    public function uploadImage(UploadedFile $file, string $directory = 'images'): string
    {
        // 1. Valider le type MIME réel (pas seulement l'extension)
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_IMAGE_MIMES)) {
            throw new \InvalidArgumentException('Type de fichier non autorisé');
        }

        // 2. Valider la taille
        if ($file->getSize() > self::MAX_IMAGE_SIZE * 1024) {
            throw new \InvalidArgumentException('Fichier trop volumineux');
        }

        // 3. Vérifier le contenu réel de l'image
        try {
            $image = Image::make($file->getRealPath());

            // Limiter les dimensions
            if ($image->width() > 4000 || $image->height() > 4000) {
                throw new \InvalidArgumentException('Dimensions de l\'image trop grandes');
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Fichier image invalide');
        }

        // 4. Générer un nom de fichier unique et sécurisé
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // 5. Supprimer les métadonnées EXIF (pour la confidentialité)
        $image = Image::make($file->getRealPath());
        $image->orientate(); // Corriger l'orientation

        // 6. Stocker le fichier de manière sécurisée
        $path = $directory . '/' . $filename;
        Storage::disk('public')->put($path, (string) $image->encode());

        return $path;
    }

    /**
     * Upload et sécuriser une vidéo
     */
    public function uploadVideo(UploadedFile $file, string $directory = 'videos'): string
    {
        // 1. Valider le type MIME réel
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_VIDEO_MIMES)) {
            throw new \InvalidArgumentException('Type de vidéo non autorisé');
        }

        // 2. Valider la taille
        if ($file->getSize() > self::MAX_VIDEO_SIZE * 1024) {
            throw new \InvalidArgumentException('Vidéo trop volumineuse');
        }

        // 3. Générer un nom unique
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // 4. Stocker de manière sécurisée
        $path = $directory . '/' . $filename;
        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return $path;
    }

    /**
     * Supprimer un fichier de manière sécurisée
     */
    public function deleteFile(?string $path): bool
    {
        if (!$path) {
            return false;
        }

        // Vérifier que le chemin ne contient pas de path traversal
        if (str_contains($path, '..') || str_starts_with($path, '/')) {
            throw new \InvalidArgumentException('Chemin de fichier invalide');
        }

        return Storage::disk('public')->delete($path);
    }
}
```

2. **Utiliser le service dans les controllers:**

```php
// Dans ArticleController.php
use App\Services\FileUploadService;

class ArticleController extends Controller
{
    public function __construct(
        private FileUploadService $fileUploadService
    ) {
        $this->middleware('can:view articles')->only(['index', 'show']);
        $this->middleware('can:create articles')->only(['create', 'store']);
        $this->middleware('can:edit articles')->only(['edit', 'update']);
        $this->middleware('can:delete articles')->only(['destroy']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'video_file' => 'nullable|file|mimes:mp4,mov,avi|max:50000',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'is_published' => 'boolean',
        ]);

        // Upload sécurisé de l'image
        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            try {
                $coverImagePath = $this->fileUploadService->uploadImage(
                    $request->file('cover_image'),
                    'articles/covers'
                );
            } catch (\Exception $e) {
                return back()->withErrors(['cover_image' => $e->getMessage()]);
            }
        }

        // Upload sécurisé de la vidéo
        $videoFilePath = null;
        if ($request->hasFile('video_file')) {
            try {
                $videoFilePath = $this->fileUploadService->uploadVideo(
                    $request->file('video_file'),
                    'articles/videos'
                );
            } catch (\Exception $e) {
                return back()->withErrors(['video_file' => $e->getMessage()]);
            }
        }

        // Reste du code...
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        // Supprimer les fichiers de manière sécurisée
        try {
            $this->fileUploadService->deleteFile($article->cover_image);
            $this->fileUploadService->deleteFile($article->video_file);
        } catch (\Exception $e) {
            // Logger l'erreur mais continuer
            \Log::error('Erreur suppression fichier: ' . $e->getMessage());
        }

        $article->tags()->detach();
        $article->delete();

        return redirect()->route('articles.index')
            ->with('message', 'Article supprimé avec succès.');
    }
}
```

3. **Configuration serveur web (Nginx):**

```nginx
# Dans /etc/nginx/sites-available/aig-app
location ~* ^/storage/.*\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$ {
    deny all;
}

location ~* ^/storage/ {
    # Désactiver l'exécution de scripts
    add_header X-Content-Type-Options "nosniff";
    add_header Content-Security-Policy "default-src 'none'; img-src 'self'; media-src 'self'";
}
```

4. **Ajouter Intervention/Image pour la validation d'images:**

```bash
composer require intervention/image
```

**Impact si exploité:**
- Exécution de code arbitraire sur le serveur
- Compromission totale du système
- Vol de données, installation de malware

---

## 🟠 Vulnérabilités HAUTES

### 5. **Protection CSRF manquante sur certaines routes**
**Sévérité:** 🟠 HAUTE
**Localisation:** `routes/web.php` (ligne 25)

**Problème:**
```php
// HeroSlides route accessible publiquement sans auth
Route::resource('hero-slides', App\Http\Controllers\HeroSlideController::class);
```

**Analyse:**
- ✅ Laravel inclut automatiquement CSRF protection pour toutes les routes POST/PUT/DELETE
- ⚠️ MAIS les routes ne sont pas protégées par authentification au niveau des routes
- Le controller HeroSlideController (ligne 14) protège via middleware mais c'est moins sûr

**Solution:**
```php
// Dans routes/web.php

// Hero Slides routes - PUBLIQUE pour index/show
Route::get('hero-slides', [App\Http\Controllers\HeroSlideController::class, 'index'])
    ->name('hero-slides.index');
Route::get('hero-slides/{heroSlide}', [App\Http\Controllers\HeroSlideController::class, 'show'])
    ->name('hero-slides.show');

// Hero Slides routes - AUTHENTIFIÉES pour CRUD
Route::middleware(['auth', 'verified', 'can:manage hero slides'])->group(function () {
    Route::get('hero-slides/create', [App\Http\Controllers\HeroSlideController::class, 'create'])
        ->name('hero-slides.create');
    Route::post('hero-slides', [App\Http\Controllers\HeroSlideController::class, 'store'])
        ->name('hero-slides.store');
    Route::get('hero-slides/{heroSlide}/edit', [App\Http\Controllers\HeroSlideController::class, 'edit'])
        ->name('hero-slides.edit');
    Route::put('hero-slides/{heroSlide}', [App\Http\Controllers\HeroSlideController::class, 'update'])
        ->name('hero-slides.update');
    Route::delete('hero-slides/{heroSlide}', [App\Http\Controllers\HeroSlideController::class, 'destroy'])
        ->name('hero-slides.destroy');
});
```

**Impact si exploité:** Actions non autorisées au nom de l'utilisateur

---

### 6. **Rate Limiting insuffisant**
**Sévérité:** 🟠 HAUTE

**Problème:**
- Aucun rate limiting visible sur les endpoints sensibles
- Risque de brute force sur login/registration
- Risque de DoS sur les endpoints d'upload

**Solution:**

1. **Ajouter rate limiting dans `app/Providers/RouteServiceProvider.php`:**

```php
protected function configureRateLimiting(): void
{
    RateLimiter::for('api', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });

    // Rate limit pour login
    RateLimiter::for('login', function (Request $request) {
        return Limit::perMinute(5)->by($request->ip());
    });

    // Rate limit pour registration
    RateLimiter::for('register', function (Request $request) {
        return Limit::perHour(3)->by($request->ip());
    });

    // Rate limit pour upload de fichiers
    RateLimiter::for('uploads', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
    });

    // Rate limit pour chat messages
    RateLimiter::for('chat', function (Request $request) {
        return Limit::perMinute(30)->by($request->user()->id);
    });
}
```

2. **Appliquer dans les routes:**

```php
// Dans routes/auth.php
Route::middleware('throttle:login')->group(function () {
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('throttle:register')->group(function () {
    Route::post('register', [RegisteredUserController::class, 'store']);
});

// Dans routes/web.php
Route::middleware(['auth', 'throttle:uploads'])->group(function () {
    Route::post('articles', [ArticleController::class, 'store']);
    Route::put('articles/{article}', [ArticleController::class, 'update']);
});

Route::middleware(['auth', 'throttle:chat'])->group(function () {
    Route::post('chat/rooms/{room}/messages', [ChatController::class, 'sendMessage']);
});
```

**Impact si exploité:**
- Brute force de mots de passe
- Déni de service (DoS)
- Spam de messages

---

### 7. **Validation insuffisante sur les entrées utilisateur**
**Sévérité:** 🟠 HAUTE
**Localisation:** TrainingController.php (ligne 208-241)

**Problème dans la méthode `enroll`:**
```php
public function enroll(Request $request, Training $training)
{
    // ...
    $validated = $request->validate([
        'selectedClassId' => 'required|exists:training_classes,id',
        'firstName' => 'nullable|string|max:255',  // ❌ Nullable mais utilisé
        'lastName' => 'nullable|string|max:255',   // ❌ Nullable mais utilisé
        'email' => 'nullable|email',               // ❌ Pas de validation unique
        'phone' => 'nullable|string|max:20',       // ❌ Pas de format
        'motivation' => 'required|string|min:50',  // ✅ OK
        'paymentMethod' => 'required|string|in:monthly,quarterly,full,card',
        'hasReadTerms' => 'required|accepted',
        'hasReadPrivacyPolicy' => 'required|accepted',
    ]);

    $training->students()->attach($user->id, [
        'status' => 'pending',
        'enrolled_at' => now(),
        'training_class_id' => $validated['selectedClassId'],
        'motivation' => $validated['motivation'],
        'payment_method' => $validated['paymentMethod'],
    ]);
    // ...
}
```

**Risques:**
- XSS via injection de HTML/JS dans le champ motivation
- Données incohérentes
- Pas de vérification que la classe appartient bien à la formation

**Solution:**
```php
public function enroll(Request $request, Training $training)
{
    $user = $request->user();

    // Vérifier que l'utilisateur n'est pas déjà inscrit
    if ($training->students()->where('user_id', $user->id)->exists()) {
        return back()->with('error', 'Vous êtes déjà inscrit à cette formation.');
    }

    // Validation renforcée
    $validated = $request->validate([
        'selectedClassId' => [
            'required',
            'integer',
            'exists:training_classes,id',
            // Vérifier que la classe appartient à cette formation
            Rule::exists('training_classes', 'id')->where(function ($query) use ($training) {
                $query->where('training_id', $training->id);
            }),
        ],
        'firstName' => 'nullable|string|max:100|regex:/^[\pL\s\-]+$/u',
        'lastName' => 'nullable|string|max:100|regex:/^[\pL\s\-]+$/u',
        'email' => 'nullable|email:rfc,dns|max:255',
        'phone' => ['nullable', 'string', 'regex:/^[+]?[0-9\s\-()]{10,20}$/'],
        'motivation' => 'required|string|min:50|max:1000',
        'paymentMethod' => 'required|string|in:monthly,quarterly,full,card',
        'hasReadTerms' => 'required|accepted',
        'hasReadPrivacyPolicy' => 'required|accepted',
    ]);

    // Nettoyer et sécuriser le texte de motivation
    $cleanMotivation = strip_tags($validated['motivation']);
    $cleanMotivation = htmlspecialchars($cleanMotivation, ENT_QUOTES, 'UTF-8');

    // Vérifier la capacité de la classe
    $trainingClass = \App\Models\TrainingClass::findOrFail($validated['selectedClassId']);
    $currentEnrollments = $trainingClass->enrollments()->count();

    if ($trainingClass->max_students && $currentEnrollments >= $trainingClass->max_students) {
        return back()->with('error', 'Cette classe est complète.');
    }

    $training->students()->attach($user->id, [
        'status' => 'pending',
        'enrolled_at' => now(),
        'training_class_id' => $validated['selectedClassId'],
        'motivation' => $cleanMotivation,
        'payment_method' => $validated['paymentMethod'],
    ]);

    $training->increment('students_count');

    return back()->with('success', 'Votre demande d\'inscription a été enregistrée.');
}
```

---

## 🟡 Vulnérabilités MOYENNES

### 8. **Sessions non sécurisées par défaut**
**Sévérité:** 🟡 MOYENNE
**Localisation:** `.env` (ligne 30-34)

**Problème:**
```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false  # ❌ Sessions non chiffrées
SESSION_PATH=/
SESSION_DOMAIN=null
```

**Solution:**
```env
# Dans .env
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true  # ✅ Chiffrer les sessions
SESSION_PATH=/
SESSION_DOMAIN=yourdomain.com  # Limiter au domaine
SESSION_SECURE_COOKIE=true     # HTTPS only
SESSION_HTTP_ONLY=true         # Pas accessible via JS
SESSION_SAME_SITE=lax         # Protection CSRF
```

```php
// Dans config/session.php
'secure' => env('SESSION_SECURE_COOKIE', true),
'http_only' => env('SESSION_HTTP_ONLY', true),
'same_site' => env('SESSION_SAME_SITE', 'lax'),
```

---

### 9. **Logging insuffisant des actions sensibles**
**Sévérité:** 🟡 MOYENNE

**Problème:**
- Aucun logging visible des actions administratives
- Pas d'audit trail pour les modifications de données
- Pas de détection d'activités suspectes

**Solution:**

1. **Créer un système de logging d'audit:**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuditLogService
{
    public function logAction(string $action, string $model, int $modelId, array $data = []): void
    {
        $user = auth()->user();

        Log::channel('audit')->info('Audit Log', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'action' => $action, // create, update, delete, view
            'model' => $model,
            'model_id' => $modelId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
            'timestamp' => now(),
        ]);
    }
}
```

2. **Utiliser dans les controllers critiques:**

```php
use App\Services\AuditLogService;

class ArticleController extends Controller
{
    public function __construct(
        private AuditLogService $auditLog
    ) {
        // ...
    }

    public function store(Request $request): RedirectResponse
    {
        // ... création de l'article

        $this->auditLog->logAction('create', 'Article', $article->id, [
            'title' => $article->title,
            'published' => $article->published_at !== null,
        ]);

        return redirect()->route('articles.index');
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        $this->auditLog->logAction('delete', 'Article', $article->id, [
            'title' => $article->title,
        ]);

        // ... suppression
    }
}
```

3. **Configuration du channel audit:**

```php
// Dans config/logging.php
'channels' => [
    // ...
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 90, // Garder 90 jours d'audit
    ],
],
```

---

### 10. **Headers de sécurité HTTP manquants**
**Sévérité:** 🟡 MOYENNE

**Problème:**
- Pas de Content Security Policy (CSP)
- Pas de X-Frame-Options
- Pas de X-Content-Type-Options

**Solution:**

1. **Créer un middleware de sécurité:**

```bash
php artisan make:middleware SecurityHeaders
```

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Content Security Policy
        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
            "style-src 'self' 'unsafe-inline'; " .
            "img-src 'self' data: https:; " .
            "font-src 'self' data:; " .
            "connect-src 'self'; " .
            "media-src 'self'; " .
            "frame-ancestors 'none';"
        );

        // Empêcher le site d'être intégré dans une iframe
        $response->headers->set('X-Frame-Options', 'DENY');

        // Empêcher le MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Activer XSS protection
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Référer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->headers->set('Permissions-Policy',
            'geolocation=(), microphone=(), camera=()'
        );

        // HSTS (HTTP Strict Transport Security) - À activer en production avec HTTPS
        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
```

2. **Enregistrer le middleware:**

```php
// Dans app/Http/Kernel.php
protected $middleware = [
    // ...
    \App\Http\Middleware\SecurityHeaders::class,
];
```

---

## 🔵 Recommandations GÉNÉRALES

### 11. **Protection contre les attaques par force brute**

**Mesures recommandées:**

1. **Implémenter un système de blocage temporaire après échecs:**

```php
// Dans app/Http/Controllers/Auth/AuthenticatedSessionController.php
use Illuminate\Support\Facades\Cache;

public function store(LoginRequest $request)
{
    $key = 'login_attempts_' . $request->ip();
    $attempts = Cache::get($key, 0);

    if ($attempts >= 5) {
        $lockoutTime = Cache::get($key . '_lockout');
        if ($lockoutTime && now()->lt($lockoutTime)) {
            throw ValidationException::withMessages([
                'email' => 'Trop de tentatives. Réessayez dans 15 minutes.',
            ]);
        }
        Cache::forget($key);
    }

    try {
        $request->authenticate();
        Cache::forget($key);
    } catch (ValidationException $e) {
        Cache::increment($key);
        Cache::put($key, Cache::get($key), now()->addMinutes(15));

        if (Cache::get($key) >= 5) {
            Cache::put($key . '_lockout', now()->addMinutes(15));
        }

        throw $e;
    }

    $request->session()->regenerate();
    return redirect()->intended(RouteServiceProvider::HOME);
}
```

---

### 12. **Amélioration de la gestion des mots de passe**

**Recommandations:**

```php
// Dans config/auth.php - Augmenter les rounds de bcrypt
'bcrypt_rounds' => env('BCRYPT_ROUNDS', 12), // Minimum 12

// Dans la validation des mots de passe
// Ajouter des règles de complexité
use Illuminate\Validation\Rules\Password;

$request->validate([
    'password' => [
        'required',
        'confirmed',
        Password::min(12)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised(), // Vérifier contre Have I Been Pwned
    ],
]);
```

---

### 13. **Protection des données sensibles dans les logs**

**Recommandations:**

```php
// Créer un middleware pour masquer les données sensibles
// Dans app/Http/Middleware/SanitizeLogging.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanitizeLogging
{
    private $hiddenFields = [
        'password',
        'password_confirmation',
        'token',
        'api_key',
        'secret',
        'credit_card',
        'ssn',
    ];

    public function handle(Request $request, Closure $next)
    {
        $request->merge(
            $this->sanitizeData($request->all())
        );

        return $next($request);
    }

    private function sanitizeData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $this->hiddenFields)) {
                $data[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitizeData($value);
            }
        }

        return $data;
    }
}
```

---

## 📋 Plan d'Action Prioritaire

### Phase 1 - URGENT (À faire immédiatement)
1. ✅ Régénérer APP_KEY
2. ✅ Désactiver APP_DEBUG en production
3. ✅ Créer et appliquer les Policies pour Event, Article, Book, Training
4. ✅ Implémenter le FileUploadService pour la sécurité des uploads
5. ✅ Configurer Nginx pour bloquer l'exécution dans /storage/

### Phase 2 - HAUTE PRIORITÉ (Cette semaine)
6. ✅ Ajouter rate limiting sur toutes les routes sensibles
7. ✅ Renforcer la validation des entrées dans TrainingController
8. ✅ Sécuriser les sessions (SESSION_ENCRYPT=true, cookies sécurisés)
9. ✅ Ajouter SecurityHeaders middleware
10. ✅ Implémenter le système d'audit logging

### Phase 3 - MOYENNE PRIORITÉ (Ce mois)
11. ✅ Configurer Content Security Policy
12. ✅ Améliorer les règles de complexité des mots de passe
13. ✅ Ajouter protection brute force sur login
14. ✅ Auditer tous les controllers pour IDOR
15. ✅ Tests de pénétration automatisés

### Phase 4 - MAINTENANCE CONTINUE
16. ✅ Mettre à jour Laravel et dépendances régulièrement
17. ✅ Monitorer les logs d'audit
18. ✅ Effectuer des audits de sécurité trimestriels
19. ✅ Former l'équipe aux bonnes pratiques de sécurité

---

## 🛠️ Commandes de Vérification

```bash
# Vérifier les vulnérabilités des dépendances
composer audit

# Analyser le code pour des failles de sécurité
composer require --dev enlightn/security-checker
php artisan security:check

# Vérifier les permissions des fichiers
find . -type f -perm 0777

# Tester les headers de sécurité
curl -I https://votre-domaine.com

# Vérifier les logs d'erreurs
tail -f storage/logs/laravel.log

# Audit des permissions
php artisan permission:show
```

---

## 📚 Ressources Complémentaires

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [PHP Security Checklist](https://www.php.net/manual/en/security.php)
- [CSP Generator](https://report-uri.com/home/generate)

---

**Conclusion:** L'application présente plusieurs vulnérabilités critiques qui nécessitent une attention immédiate, notamment la gestion des fichiers uploadés et l'absence de contrôles d'autorisation au niveau des ressources. Le plan d'action ci-dessus doit être suivi rigoureusement pour sécuriser l'application avant son déploiement en production.
