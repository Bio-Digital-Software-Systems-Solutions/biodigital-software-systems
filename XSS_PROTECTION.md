# XSS Protection Guide

## Overview

Cross-Site Scripting (XSS) is one of the most critical web security vulnerabilities. This guide documents the XSS protection measures implemented in the AIG-App project and provides best practices for preventing XSS attacks.

## What is XSS?

XSS attacks occur when malicious scripts are injected into web pages viewed by other users. There are three main types:

1. **Stored XSS**: Malicious script is permanently stored on the target server
2. **Reflected XSS**: Script is reflected off a web server (e.g., in error messages, search results)
3. **DOM-based XSS**: Vulnerability exists in client-side code rather than server-side code

## Current Status

### ✅ Implemented

1. **isomorphic-dompurify Package**: Installed for universal (client + server) HTML sanitization
2. **SafeHTML Component**: Custom React component for secure HTML rendering
3. **Type Definitions**: @types/dompurify installed for TypeScript support

### ⚠️ Action Required

**12 files currently use `dangerouslySetInnerHTML`** and need to be refactored:

- `/resources/js/Pages/Articles/Show.tsx`
- `/resources/js/Pages/Profile/Partials/TwoFactorAuthenticationForm.tsx`
- `/resources/js/Pages/Books/Index.tsx`
- `/resources/js/Pages/Groups/Index.tsx`
- `/resources/js/Pages/Departments/Index.tsx`
- `/resources/js/Pages/Messages/Index.tsx`
- `/resources/js/Pages/Stocks/Index.tsx`
- `/resources/js/Pages/Events/Index.tsx`
- `/resources/js/Pages/Articles/Index.tsx`
- `/resources/js/Pages/BookRentals/Index.tsx`
- `/resources/js/Pages/Programs/Index.tsx`
- `/resources/js/Pages/Messages/Show.tsx`

## SafeHTML Component

### Basic Usage

```tsx
import SafeHTML from '@/Components/SafeHTML';

// Replace this UNSAFE code:
<div dangerouslySetInnerHTML={{ __html: userContent }} />

// With this SAFE code:
<SafeHTML html={userContent} />
```

### Advanced Usage

#### Custom HTML Tag

```tsx
// Render as a <p> tag instead of default <div>
<SafeHTML html={description} tag="p" />

// Render as <article>
<SafeHTML html={content} tag="article" />
```

#### With Styling

```tsx
// Apply CSS classes
<SafeHTML
    html={richText}
    className="prose dark:prose-invert max-w-none"
/>

// With TailwindCSS typography
<SafeHTML
    html={article.content}
    className="prose lg:prose-xl"
/>
```

#### Custom Sanitization Config

```tsx
import SafeHTML, { DOMPurifyPresets } from '@/Components/SafeHTML';

// Strict mode - only basic formatting
<SafeHTML
    html={userComment}
    config={DOMPurifyPresets.STRICT}
/>

// Basic mode - formatting + links
<SafeHTML
    html={description}
    config={DOMPurifyPresets.BASIC}
/>

// Rich text mode - full formatting
<SafeHTML
    html={article.body}
    config={DOMPurifyPresets.RICH_TEXT}
/>

// Custom configuration
<SafeHTML
    html={content}
    config={{
        ALLOWED_TAGS: ['p', 'b', 'i', 'a'],
        ALLOWED_ATTR: ['href', 'target']
    }}
/>
```

## DOMPurify Presets

### STRICT

**Use for**: User comments, short descriptions, untrusted content

**Allowed**: Basic text formatting only

```tsx
config={DOMPurifyPresets.STRICT}
```

Allows: `<b>`, `<i>`, `<em>`, `<strong>`, `<p>`, `<br>`

### BASIC

**Use for**: Descriptions with links, user bios

**Allowed**: Text formatting and links

```tsx
config={DOMPurifyPresets.BASIC}
```

Allows: `<a>`, `<b>`, `<i>`, `<em>`, `<strong>`, `<p>`, `<br>`, `<ul>`, `<ol>`, `<li>`

### RICH_TEXT

**Use for**: Article content, blog posts, rich text editor output

**Allowed**: Rich text formatting with images

```tsx
config={DOMPurifyPresets.RICH_TEXT}
```

Allows: Headings, paragraphs, lists, links, images, quotes, code blocks

### FULL

**Use for**: Trusted admin content only

**Allowed**: Most HTML elements

```tsx
config={DOMPurifyPresets.FULL}
```

⚠️ **Warning**: Only use with content from trusted sources

## useSanitizedHTML Hook

For cases where you need the sanitized HTML string directly:

```tsx
import { useSanitizedHTML } from '@/Components/SafeHTML';

const MyComponent = ({ userContent }) => {
    // Sanitize HTML
    const safeHTML = useSanitizedHTML(userContent);

    // Use in state, pass to other components, etc.
    const [content, setContent] = useState(safeHTML);

    return <div dangerouslySetInnerHTML={{ __html: content }} />;
};
```

## Utility Function

For server-side or non-component sanitization:

```tsx
import { sanitizeHTML } from '@/Components/SafeHTML';

// Sanitize before sending to API
const payload = {
    title: formData.title,
    content: sanitizeHTML(formData.content),
};

// Sanitize API response
const response = await fetch('/api/content');
const data = await response.json();
const safeContent = sanitizeHTML(data.content);
```

## Migration Guide

### Step 1: Identify Usage

Find all instances of `dangerouslySetInnerHTML`:

```bash
grep -r "dangerouslySetInnerHTML" resources/js
```

### Step 2: Analyze Content Source

For each instance, determine:
- Is the content user-generated? → **Must sanitize**
- Is it from database? → **Must sanitize**
- Is it static content? → **Can skip, but sanitizing is still recommended**

### Step 3: Replace with SafeHTML

**Before:**
```tsx
<div
    className="prose"
    dangerouslySetInnerHTML={{ __html: article.content }}
/>
```

**After:**
```tsx
<SafeHTML
    html={article.content}
    className="prose"
    config={DOMPurifyPresets.RICH_TEXT}
/>
```

### Step 4: Test Thoroughly

Test with malicious payloads:

```tsx
// Test cases
const maliciousInputs = [
    '<script>alert("XSS")</script>',
    '<img src=x onerror="alert(\'XSS\')">',
    '<iframe src="javascript:alert(\'XSS\')"></iframe>',
    '<svg onload="alert(\'XSS\')">',
    '<a href="javascript:alert(\'XSS\')">Click me</a>',
];

maliciousInputs.forEach(input => {
    console.log('Input:', input);
    console.log('Sanitized:', sanitizeHTML(input));
});
```

## Backend Protection

### Laravel Validation

Always validate and sanitize on the backend:

```php
// In Form Request or Controller
$validated = $request->validate([
    'content' => ['required', 'string', 'max:10000'],
    'title' => ['required', 'string', 'max:255'],
]);

// Optional: Strip tags if HTML not needed
$validated['title'] = strip_tags($validated['title']);
```

### Laravel Purifier (Optional)

For backend HTML sanitization:

```bash
composer require mews/purifier
```

```php
use Mews\Purifier\Facades\Purifier;

$cleanContent = Purifier::clean($request->input('content'));
```

## Security Best Practices

### 1. Never Trust User Input

**Always** sanitize user-generated content, even if it comes from authenticated users.

### 2. Use Content Security Policy (CSP)

Add CSP headers to prevent inline script execution:

```php
// In middleware or controller
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'");
```

### 3. Escape Output

Laravel Blade automatically escapes output:

```blade
{{-- Safe (auto-escaped) --}}
{{ $userContent }}

{{-- Unsafe (unescaped) --}}
{!! $userContent !!}  {{-- Only use with sanitized content! --}}
```

### 4. Validate on Both Sides

- **Frontend**: Improve UX with immediate feedback
- **Backend**: Enforce security (NEVER trust frontend alone)

### 5. Use HTTPOnly Cookies

Prevent JavaScript from accessing session cookies:

```env
SESSION_HTTP_ONLY=true  # Already enabled ✓
```

### 6. Implement Rate Limiting

Prevent XSS payload injection attempts:

```php
// In routes/web.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/comments', [CommentController::class, 'store']);
});
```

## Testing for XSS Vulnerabilities

### Manual Testing

1. **Input Field Testing**:
   ```javascript
   <script>alert('XSS')</script>
   <img src=x onerror=alert('XSS')>
   <svg/onload=alert('XSS')>
   ```

2. **URL Parameter Testing**:
   ```
   ?search=<script>alert('XSS')</script>
   ?name=<img src=x onerror=alert(1)>
   ```

3. **Header Testing**:
   ```
   User-Agent: <script>alert('XSS')</script>
   Referer: javascript:alert('XSS')
   ```

### Automated Testing

```tsx
// __tests__/security/xss.test.tsx
import { sanitizeHTML } from '@/Components/SafeHTML';

describe('XSS Protection', () => {
    it('should remove script tags', () => {
        const input = '<script>alert("XSS")</script>';
        const output = sanitizeHTML(input);
        expect(output).not.toContain('<script>');
        expect(output).not.toContain('alert');
    });

    it('should remove event handlers', () => {
        const input = '<img src=x onerror="alert(\'XSS\')">';
        const output = sanitizeHTML(input);
        expect(output).not.toContain('onerror');
    });

    it('should remove javascript: URLs', () => {
        const input = '<a href="javascript:alert(\'XSS\')">Click</a>';
        const output = sanitizeHTML(input);
        expect(output).not.toContain('javascript:');
    });

    it('should allow safe HTML', () => {
        const input = '<p><strong>Bold</strong> and <em>italic</em></p>';
        const output = sanitizeHTML(input);
        expect(output).toContain('<p>');
        expect(output).toContain('<strong>');
        expect(output).toContain('<em>');
    });
});
```

## Common XSS Patterns to Avoid

### ❌ Never Do This

```tsx
// DANGEROUS: Direct innerHTML
element.innerHTML = userInput;

// DANGEROUS: Direct dangerouslySetInnerHTML
<div dangerouslySetInnerHTML={{ __html: userContent }} />

// DANGEROUS: eval() with user input
eval(userInput);

// DANGEROUS: new Function() with user input
new Function(userInput)();

// DANGEROUS: setTimeout/setInterval with string
setTimeout(userInput, 1000);
```

### ✅ Always Do This

```tsx
// SAFE: Use SafeHTML component
<SafeHTML html={userContent} />

// SAFE: Escape content
<div>{userContent}</div>

// SAFE: Use textContent
element.textContent = userInput;

// SAFE: Sanitize before using
<div dangerouslySetInnerHTML={{ __html: sanitizeHTML(userContent) }} />
```

## Content Security Policy (CSP)

Implement CSP headers to provide an additional layer of protection:

```php
// app/Http/Middleware/ContentSecurityPolicy.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Adjust as needed
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
        ];

        $response->headers->set('Content-Security-Policy', implode('; ', $csp));

        return $response;
    }
}
```

## Monitoring and Alerts

### Log Suspicious Activity

```php
// In controller or middleware
use Illuminate\Support\Facades\Log;

if (preg_match('/<script|javascript:|onerror=/i', $input)) {
    Log::warning('Potential XSS attempt detected', [
        'input' => $input,
        'user_id' => auth()->id(),
        'ip' => request()->ip(),
        'url' => request()->fullUrl(),
    ]);
}
```

### Integration with Sentry

```php
// Report to Sentry
\Sentry\captureMessage('XSS attempt detected', [
    'level' => 'warning',
    'extra' => [
        'input' => $input,
        'user' => auth()->user(),
    ],
]);
```

## Migration Checklist

- [ ] Install isomorphic-dompurify ✅
- [ ] Create SafeHTML component ✅
- [ ] Replace dangerouslySetInnerHTML in Articles/Show.tsx
- [ ] Replace dangerouslySetInnerHTML in TwoFactorAuthenticationForm.tsx
- [ ] Replace dangerouslySetInnerHTML in Books/Index.tsx
- [ ] Replace dangerouslySetInnerHTML in Groups/Index.tsx
- [ ] Replace dangerouslySetInnerHTML in Departments/Index.tsx
- [ ] Replace dangerouslySetInnerHTML in Messages/Index.tsx
- [ ] Replace dangerouslySetInnerHTML in Stocks/Index.tsx
- [ ] Replace dangerouslySetInnerHTML in Events/Index.tsx
- [ ] Replace dangerouslySetInnerHTML in Articles/Index.tsx
- [ ] Replace dangerouslySetInnerHTML in BookRentals/Index.tsx
- [ ] Replace dangerouslySetInnerHTML in Programs/Index.tsx
- [ ] Replace dangerouslySetInnerHTML in Messages/Show.tsx
- [ ] Create XSS tests
- [ ] Add backend validation
- [ ] Implement CSP headers
- [ ] Set up monitoring
- [ ] Document in team wiki

## Timeline

### Week 1: High Priority Files
- Articles/Show.tsx
- Messages/Show.tsx
- Messages/Index.tsx

### Week 2: Medium Priority Files
- Events/Index.tsx
- Books/Index.tsx
- Articles/Index.tsx

### Week 3: Remaining Files
- All other files with dangerouslySetInnerHTML

### Week 4: Testing & Monitoring
- Comprehensive XSS testing
- Set up monitoring
- Document lessons learned

## Resources

- [OWASP XSS Guide](https://owasp.org/www-community/attacks/xss/)
- [DOMPurify Documentation](https://github.com/cure53/DOMPurify)
- [Content Security Policy (CSP)](https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP)
- [React Security Best Practices](https://react.dev/reference/react-dom/components/common#dangerously-setting-the-inner-html)

## Conclusion

XSS protection is critical for maintaining the security and integrity of the AIG-App platform. By consistently using the SafeHTML component and following these best practices, we can prevent XSS attacks and protect our users' data.

**Remember**: Security is not a one-time effort but an ongoing commitment. Always sanitize user input, validate on both client and server, and stay informed about the latest security threats.
