<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property int $user_id
 * @property string $email
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property bool $acknowledged
 * @property int|null $acknowledged_by
 * @property \Illuminate\Support\Carbon|null $acknowledged_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 * @property-read User|null $acknowledgedByUser
 */
class BlockedLoginAttempt extends Model
{
    use HasFactory, LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'acknowledged',
        'acknowledged_by',
        'acknowledged_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'acknowledged' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acknowledged' => 'boolean',
            'acknowledged_at' => 'datetime',
        ];
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The user who attempted to log in.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin who acknowledged this attempt.
     */
    public function acknowledgedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Scope to get unacknowledged attempts.
     */
    public function scopeUnacknowledged($query)
    {
        return $query->where('acknowledged', false);
    }

    /**
     * Scope to get recent attempts.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Mark the attempt as acknowledged.
     */
    public function acknowledge(User $admin): void
    {
        $this->update([
            'acknowledged' => true,
            'acknowledged_by' => $admin->id,
            'acknowledged_at' => now(),
        ]);
    }
}
