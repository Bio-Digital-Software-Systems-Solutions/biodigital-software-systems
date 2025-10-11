<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuditLogService
{
    /**
     * Log an action for audit purposes
     */
    public function logAction(string $action, string $model, int $modelId, array $data = []): void
    {
        $user = auth()->user();

        Log::channel('audit')->info('Audit Log', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_name' => $user ? ($user->first_name.' '.$user->last_name) : 'Guest',
            'action' => $action, // create, update, delete, view
            'model' => $model,
            'model_id' => $modelId,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log a security event
     */
    public function logSecurityEvent(string $event, string $description, array $data = []): void
    {
        $user = auth()->user();

        Log::channel('security')->warning('Security Event', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'event' => $event,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
