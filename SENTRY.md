# Sentry Configuration Guide

## Overview

Sentry is integrated into the AIG-App to provide real-time error tracking, performance monitoring, and debugging information. This guide will help you set up and use Sentry effectively.

## Features Configured

- ✅ Automatic exception capturing
- ✅ User context tracking (authenticated users)
- ✅ Performance monitoring (traces)
- ✅ Breadcrumbs for debugging context
- ✅ SQL query tracking
- ✅ Cache operations tracking
- ✅ HTTP client request tracking
- ✅ Queue job monitoring
- ✅ Custom context and tags

## Setup

### 1. Create a Sentry Project

1. Go to [https://sentry.io](https://sentry.io)
2. Create an account or log in
3. Create a new project for your application
4. Choose "Laravel" as the platform
5. Copy the DSN (Data Source Name) provided

### 2. Configure Environment Variables

Add the following to your `.env` file:

```env
# Sentry Configuration
# Get your DSN from https://sentry.io/settings/your-org/projects/your-project/keys/
SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/your-project-id

# Sample rates (0.0 to 1.0)
# 0.2 means 20% of transactions/profiles will be sent to Sentry
SENTRY_TRACES_SAMPLE_RATE=0.2
SENTRY_PROFILES_SAMPLE_RATE=0.2

# Privacy settings
SENTRY_SEND_DEFAULT_PII=false

# Enable logs (can be expensive in production)
SENTRY_ENABLE_LOGS=false
```

### 3. Environment-Specific Configuration

**Local/Development:**
```env
SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=1.0
SENTRY_PROFILES_SAMPLE_RATE=1.0
SENTRY_SEND_DEFAULT_PII=true
```

**Staging:**
```env
SENTRY_LARAVEL_DSN=your-staging-dsn
SENTRY_TRACES_SAMPLE_RATE=0.5
SENTRY_PROFILES_SAMPLE_RATE=0.5
SENTRY_SEND_DEFAULT_PII=false
```

**Production:**
```env
SENTRY_LARAVEL_DSN=your-production-dsn
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.1
SENTRY_SEND_DEFAULT_PII=false
SENTRY_ENABLE_LOGS=false
```

## What Gets Tracked

### 1. Exceptions

All uncaught exceptions are automatically sent to Sentry with:
- Stack trace
- Request information
- User context (if authenticated)
- Environment variables (non-sensitive)
- Breadcrumbs leading to the error

### 2. User Context

When a user is authenticated, Sentry automatically captures:
- User ID
- Email address
- Full name
- Roles
- Permissions

This information helps identify which users are affected by errors.

### 3. Breadcrumbs

Breadcrumbs provide context about what happened before an error:
- Log messages
- Cache operations (hits, misses, writes)
- SQL queries (with or without bindings)
- Queue job information
- HTTP client requests
- Notifications sent

### 4. Performance Monitoring

Traces capture performance data for:
- HTTP requests
- Database queries
- Cache operations
- Queue jobs
- View rendering
- HTTP client requests

## Testing Sentry Integration

### Test Routes (Local/Development Only)

The following test routes are available when `APP_ENV=local`:

1. **Test Exception Capture**
   ```
   GET /sentry/test-error
   ```
   Throws a test exception to verify error tracking.

2. **Test Message Capture**
   ```
   GET /sentry/test-message
   ```
   Sends a test message without throwing an exception.

3. **Test Breadcrumbs**
   ```
   GET /sentry/test-breadcrumbs
   ```
   Throws an exception with custom breadcrumbs and context.

**Important:** These routes are only accessible when authenticated and in local/development environments.

## Manual Usage

### Capture Exceptions

```php
try {
    // Your code here
} catch (\Exception $e) {
    \Sentry\captureException($e);
    // Handle the exception
}
```

### Capture Messages

```php
// Info level
\Sentry\captureMessage('User performed action X', \Sentry\Severity::info());

// Warning level
\Sentry\captureMessage('Approaching rate limit', \Sentry\Severity::warning());

// Error level
\Sentry\captureMessage('Critical configuration missing', \Sentry\Severity::error());
```

### Add Custom Context

```php
\Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
    $scope->setContext('order', [
        'order_id' => 12345,
        'total' => 99.99,
        'items_count' => 3,
    ]);
});
```

### Add Tags

```php
\Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
    $scope->setTag('payment_method', 'credit_card');
    $scope->setTag('subscription_tier', 'premium');
});
```

### Add Breadcrumbs

```php
\Sentry\addBreadcrumb(
    new \Sentry\Breadcrumb(
        \Sentry\Breadcrumb::LEVEL_INFO,
        \Sentry\Breadcrumb::TYPE_USER,
        'user_action',
        'User clicked checkout button'
    )
);
```

### Set User Context Manually

```php
\Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
    $scope->setUser([
        'id' => $user->id,
        'email' => $user->email,
        'username' => $user->name,
    ]);
});
```

## Configuration Options

The Sentry configuration file is located at `config/sentry.php`. Key options include:

### Sample Rates

- `sample_rate`: Controls what percentage of errors are sent (default: 1.0 = 100%)
- `traces_sample_rate`: Controls what percentage of transactions are profiled
- `profiles_sample_rate`: Controls what percentage of transactions have profiles

### Privacy

- `send_default_pii`: Whether to send personally identifiable information (emails, IPs, etc.)

### Ignored Transactions

By default, the `/up` health check endpoint is ignored. You can add more:

```php
'ignore_transactions' => [
    '/up',
    '/health',
    '/status',
],
```

### Breadcrumb Configuration

Enable/disable specific breadcrumb types in `config/sentry.php`:

```php
'breadcrumbs' => [
    'logs' => true,
    'cache' => true,
    'sql_queries' => true,
    'sql_bindings' => false, // Enable to capture SQL parameters
    'queue_info' => true,
    'http_client_requests' => true,
],
```

### Performance Tracing

Configure what gets traced:

```php
'tracing' => [
    'queue_job_transactions' => true,
    'sql_queries' => true,
    'sql_bindings' => false, // Enable to capture SQL parameters
    'views' => true,
    'http_client_requests' => true,
    'cache' => true,
],
```

## Best Practices

### 1. Sample Rates in Production

Don't send 100% of events in production. Use appropriate sample rates:
- Errors: 1.0 (capture all errors)
- Traces: 0.1-0.2 (10-20% of transactions)
- Profiles: 0.1-0.2 (10-20% of transactions)

### 2. Sensitive Data

Never log sensitive information like:
- Passwords
- Credit card numbers
- API keys
- Personal health information

Set `SENTRY_SEND_DEFAULT_PII=false` in production.

### 3. Release Tracking

Set a release version to track which deployments cause errors:

```env
SENTRY_RELEASE=v1.2.3
```

Or use git hash:
```env
SENTRY_RELEASE=$(git rev-parse --short HEAD)
```

### 4. Environment Names

Use clear environment names:
```env
SENTRY_ENVIRONMENT=production
SENTRY_ENVIRONMENT=staging
SENTRY_ENVIRONMENT=development
```

### 5. Alert Configuration

Configure alerts in Sentry dashboard to notify you when:
- Error rate exceeds threshold
- New error types appear
- Performance degrades

## Troubleshooting

### Sentry Not Capturing Errors

1. Check DSN is correctly set in `.env`
2. Verify `APP_ENV` is not `testing`
3. Check logs: `storage/logs/laravel.log`
4. Test with: `/sentry/test-error` route

### Too Many Events

1. Reduce sample rates in `.env`
2. Add ignore patterns in `config/sentry.php`
3. Implement custom filtering in exception handler

### Missing User Context

1. Ensure user is authenticated
2. Check `SentryContext` middleware is registered
3. Verify middleware is running on the request

## Resources

- [Sentry Laravel Documentation](https://docs.sentry.io/platforms/php/guides/laravel/)
- [Sentry Dashboard](https://sentry.io)
- [Best Practices](https://docs.sentry.io/platforms/php/guides/laravel/best-practices/)
- [Configuration Options](https://docs.sentry.io/platforms/php/guides/laravel/configuration/options/)

## Support

For issues with Sentry integration:
1. Check this documentation
2. Review Sentry logs in dashboard
3. Check Laravel logs: `storage/logs/laravel.log`
4. Visit [Sentry Support](https://sentry.io/support/)
