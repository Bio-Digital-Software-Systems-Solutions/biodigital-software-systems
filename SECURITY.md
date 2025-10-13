# Security Guide

## Overview

This document provides comprehensive security guidelines and best practices for the AIG-App project. Security is a critical aspect of the application and should be treated with the highest priority.

## Table of Contents

1. [Session Security](#session-security)
2. [XSS Protection](#xss-protection)
3. [CSRF Protection](#csrf-protection)
4. [Authentication & Authorization](#authentication--authorization)
5. [API Security](#api-security)
6. [File Upload Security](#file-upload-security)
7. [Database Security](#database-security)
8. [Security Headers](#security-headers)
9. [Environment Configuration](#environment-configuration)
10. [Security Testing](#security-testing)
11. [Incident Response](#incident-response)

---

## Session Security

### Current Status

✅ **Configured**
- Session HTTP_ONLY: `true` (prevents JavaScript access)
- Session SAME_SITE: `lax` (CSRF protection)

⚠️ **Needs Configuration**
- SESSION_ENCRYPT: `false` → Should be `true` in production
- SESSION_SECURE_COOKIE: Not set → Should be `true` in production
- SESSION_DOMAIN: Not set → Should be configured for your domain

### Configuration

#### Development Environment

```env
# .env (local)
SESSION_DRIVER=database
SESSION_ENCRYPT=false  # Can be false in local development
SESSION_SECURE_COOKIE=false  # Must be false for http://localhost
SESSION_HTTP_ONLY=true  # Always true
SESSION_SAME_SITE=lax  # Balance security and usability
SESSION_LIFETIME=120  # 2 hours
```

#### Production Environment

```env
# .env (production)
SESSION_DRIVER=database  # or redis for better performance
SESSION_ENCRYPT=true  # Encrypt all session data
SESSION_SECURE_COOKIE=true  # HTTPS only
SESSION_DOMAIN=.example.com  # Your domain
SESSION_HTTP_ONLY=true  # Prevent JavaScript access
SESSION_SAME_SITE=strict  # Maximum security (or 'lax' if needed)
SESSION_LIFETIME=120  # 2 hours
```

### Best Practices

1. **Always Encrypt Sessions in Production**
   ```env
   SESSION_ENCRYPT=true
   ```

2. **Use HTTPS in Production**
   ```env
   SESSION_SECURE_COOKIE=true
   COOKIE_SECURE=true
   ```

3. **Set Proper Domain**
   ```env
   SESSION_DOMAIN=.yourdomain.com  # Works for all subdomains
   ```

4. **Regenerate Session ID on Login**
   ```php
   // In AuthController
   $request->session()->regenerate();
   ```

5. **Implement Session Timeout**
   ```php
   // In middleware
   if (time() - session('last_activity') > config('session.lifetime') * 60) {
       session()->flush();
       return redirect('/login');
   }
   session(['last_activity' => time()]);
   ```

---

## XSS Protection

### Status

✅ **Implemented**
- DOMPurify installed
- isomorphic-dompurify installed
- SafeHTML component created
- Sanitization utilities created

⚠️ **Action Required**
- 12 files still use `dangerouslySetInnerHTML`
- See [XSS_PROTECTION.md](./XSS_PROTECTION.md) for migration guide

### Protection Layers

#### 1. Frontend Sanitization

```tsx
import SafeHTML from '@/Components/SafeHTML';

// Use SafeHTML component
<SafeHTML html={userContent} />

// Or use sanitization utility
import { sanitizeHtml } from '@/utils/sanitize';
const clean = sanitizeHtml(userContent);
```

#### 2. Backend Validation

```php
// In Form Request or Controller
$validated = $request->validate([
    'content' => ['required', 'string', 'max:10000'],
]);

// Optional: Strip tags
$validated['content'] = strip_tags($validated['content'], '<p><br><strong><em>');
```

#### 3. Blade Escaping

```blade
{{-- Escaped (safe) --}}
{{ $content }}

{{-- Unescaped (only use with sanitized content!) --}}
{!! $sanitizedContent !!}
```

### Content Security Policy (CSP)

Implement CSP headers to prevent inline script execution:

```php
// In app/Http/Middleware/ContentSecurityPolicy.php
public function handle(Request $request, Closure $next)
{
    $response = $next($request);

    $csp = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data: https:",
        "font-src 'self' data:",
        "connect-src 'self'",
        "frame-ancestors 'self'",
    ];

    $response->headers->set(
        'Content-Security-Policy',
        implode('; ', $csp)
    );

    return $response;
}
```

---

## CSRF Protection

### Laravel CSRF Protection

Laravel automatically protects against CSRF attacks. Ensure all forms include the CSRF token:

#### Blade Templates

```blade
<form method="POST" action="/profile">
    @csrf
    <!-- form fields -->
</form>
```

#### Inertia/React Forms

```tsx
import { useForm } from '@inertiajs/react';

const { data, post } = useForm({
    name: '',
    email: '',
});

const submit = (e) => {
    e.preventDefault();
    post('/profile');  // CSRF token automatically included
};
```

#### API Requests

```tsx
const response = await fetch('/api/data', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    },
    body: JSON.stringify(data),
});
```

---

## Authentication & Authorization

### Password Security

```env
# Minimum password requirements
PASSWORD_MIN_LENGTH=8
PASSWORD_TIMEOUT=10800  # 3 hours
```

```php
// In validation rules
'password' => [
    'required',
    'string',
    'min:8',
    'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x]).*$/',
    'confirmed',
],
```

### Two-Factor Authentication

```env
TWO_FACTOR_ENABLED=true
TWO_FACTOR_QR_CODE_SIZE=200
```

### Rate Limiting

#### Login Attempts

```php
// In routes/web.php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

// In RouteServiceProvider
RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->input('email').$request->ip());
});
```

#### API Requests

```php
// In routes/api.php
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// In RouteServiceProvider
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### Authorization (Policies)

```php
// In EventPolicy.php
public function update(User $user, Event $event): bool
{
    return $user->id === $event->user_id
        || $user->hasPermissionTo('edit events');
}

// In Controller
$this->authorize('update', $event);
```

---

## API Security

### Laravel Sanctum

```env
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,yourdomain.com
SANCTUM_COOKIE_NAME=aig_app_session
```

### API Rate Limiting

```env
API_RATE_LIMIT=60
API_THROTTLE_DECAY=1
```

### API Token Security

```php
// Generate token
$token = $user->createToken('api-token', ['read', 'write'])->plainTextToken;

// Verify token abilities
if ($request->user()->tokenCan('read')) {
    // User has 'read' ability
}
```

### CORS Configuration

```env
# Production
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://api.yourdomain.com
CORS_ALLOW_ALL_ORIGINS=false
CORS_SUPPORTS_CREDENTIALS=true

# Development
CORS_ALLOW_ALL_ORIGINS=true
```

---

## File Upload Security

### Validation

```php
// In Form Request
'avatar' => [
    'required',
    'file',
    'mimes:jpeg,png,jpg,gif',
    'max:10240',  // 10MB
],
'document' => [
    'required',
    'file',
    'mimes:pdf,doc,docx',
    'max:20480',  // 20MB
],
```

### Secure File Storage

```php
// In FileUploadService
public function uploadImage(UploadedFile $file, string $directory = 'images'): string
{
    // 1. Validate MIME type
    $mimeType = $file->getMimeType();
    if (!in_array($mimeType, self::ALLOWED_IMAGE_MIMES)) {
        throw new \InvalidArgumentException('Invalid file type');
    }

    // 2. Validate file size
    if ($file->getSize() > self::MAX_IMAGE_SIZE * 1024) {
        throw new \InvalidArgumentException('File too large');
    }

    // 3. Generate unique filename
    $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

    // 4. Store securely
    $path = $directory . '/' . $filename;
    Storage::disk('public')->putFileAs($directory, $file, $filename);

    return $path;
}
```

### File Type Verification

```php
// Verify actual file content, not just extension
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file->getRealPath());
finfo_close($finfo);

if ($mimeType !== 'image/jpeg' && $mimeType !== 'image/png') {
    throw new \Exception('Invalid image type');
}
```

---

## Database Security

### SQL Injection Prevention

```php
// ✅ SAFE: Use Eloquent ORM
User::where('email', $request->email)->first();

// ✅ SAFE: Use parameter binding
DB::table('users')->where('email', '=', $request->email)->get();

// ❌ UNSAFE: Never use raw queries with user input
DB::select("SELECT * FROM users WHERE email = '{$request->email}'");

// ✅ SAFE: Use parameter binding in raw queries
DB::select('SELECT * FROM users WHERE email = ?', [$request->email]);
```

### Database Credentials

```env
# Never commit these to version control!
DB_DATABASE=production_database
DB_USERNAME=db_user
DB_PASSWORD=strong_random_password_here
```

### Backup Security

```env
# Encrypt backups
BACKUP_ENCRYPTION_ENABLED=true
BACKUP_ENCRYPTION_PASSWORD=strong_backup_password

# Restrict backup access
BACKUP_DESTINATION_PATH=/secure/backups
```

---

## Security Headers

### Implement Security Middleware

Create `/app/Http/Middleware/SecurityHeaders.php`:

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

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS filtering
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS (HTTP Strict Transport Security)
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        // Permissions Policy
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=()'
        );

        return $response;
    }
}
```

Register in `/app/Http/Kernel.php`:

```php
protected $middleware = [
    \App\Http\Middleware\SecurityHeaders::class,
    // ...
];
```

---

## Environment Configuration

### Development (.env.local)

```env
APP_ENV=local
APP_DEBUG=true
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=false
COOKIE_SECURE=false
CORS_ALLOW_ALL_ORIGINS=true
```

### Staging (.env.staging)

```env
APP_ENV=staging
APP_DEBUG=false
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.staging.example.com
COOKIE_SECURE=true
CORS_ALLOWED_ORIGINS=https://staging.example.com
```

### Production (.env.production)

```env
APP_ENV=production
APP_DEBUG=false
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.example.com
COOKIE_SECURE=true
SESSION_SAME_SITE=strict
CORS_ALLOWED_ORIGINS=https://example.com,https://www.example.com
CORS_ALLOW_ALL_ORIGINS=false
CSP_ENABLED=true
TWO_FACTOR_ENABLED=true
```

---

## Security Testing

### Manual Testing Checklist

- [ ] Test XSS in all input fields
- [ ] Test SQL injection attempts
- [ ] Test CSRF protection
- [ ] Test file upload with malicious files
- [ ] Test authentication bypass attempts
- [ ] Test authorization for different user roles
- [ ] Test session fixation
- [ ] Test session hijacking
- [ ] Test rate limiting
- [ ] Test HTTPS enforcement
- [ ] Test security headers

### Automated Security Scanning

```bash
# PHP Security Checker
composer require --dev enlightn/security-checker
php artisan security:check

# npm Audit
npm audit

# OWASP Dependency Check
dependency-check --project "AIG-App" --scan ./

# Static Analysis
vendor/bin/phpstan analyse

# Code Quality
make quality
```

### Penetration Testing

Consider hiring professional penetration testers for:
- Full application security audit
- Infrastructure security review
- Social engineering tests
- Physical security assessment

---

## Incident Response

### Security Incident Plan

1. **Detect**
   - Monitor logs (Sentry, Laravel Telescope)
   - Set up alerts for suspicious activity
   - Regular security audits

2. **Respond**
   - Isolate affected systems
   - Assess scope of breach
   - Notify stakeholders
   - Preserve evidence

3. **Recover**
   - Patch vulnerabilities
   - Restore from backups if needed
   - Reset compromised credentials
   - Update security measures

4. **Review**
   - Post-mortem analysis
   - Update security procedures
   - Improve monitoring
   - Train team

### Contact Information

**Security Team**
- Email: security@example.com
- Emergency: +1-XXX-XXX-XXXX

**Responsible Disclosure**
If you discover a security vulnerability, please email security@example.com with:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

We commit to:
- Acknowledge receipt within 24 hours
- Provide regular updates
- Credit researchers (if desired)
- Fix critical vulnerabilities within 48 hours

---

## Security Checklist

### Pre-Deployment

- [ ] All .env variables configured correctly
- [ ] SESSION_ENCRYPT=true
- [ ] SESSION_SECURE_COOKIE=true
- [ ] COOKIE_SECURE=true
- [ ] CORS properly configured
- [ ] CSP headers enabled
- [ ] Security headers configured
- [ ] HTTPS enforced
- [ ] Rate limiting configured
- [ ] File upload validation
- [ ] XSS protection implemented
- [ ] CSRF protection active
- [ ] All dependencies updated
- [ ] Security audit completed
- [ ] Backup system tested
- [ ] Monitoring active
- [ ] Logging configured
- [ ] Incident response plan in place

### Regular Maintenance

- [ ] Weekly: Review logs for suspicious activity
- [ ] Monthly: Update dependencies
- [ ] Quarterly: Security audit
- [ ] Annually: Penetration testing
- [ ] Continuous: Monitor Sentry alerts

---

## Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security](https://laravel.com/docs/security)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)
- [CWE Top 25](https://cwe.mitre.org/top25/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)

---

## Conclusion

Security is an ongoing process, not a one-time task. By following these guidelines and regularly updating security measures, we can protect the AIG-App platform and its users from threats.

**Remember**: Security is everyone's responsibility. If you see something suspicious, report it immediately.
