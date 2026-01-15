<?php

namespace App\Models\Event;

use App\Models\User;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SessionSpeaker extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $fillable = [
        'session_id',
        'user_id',
        'name',
        'email',
        'title',
        'company',
        'bio',
        'photo',
        'role',
        'social_links',
        'sort_order',
        'is_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'sort_order' => 'integer',
            'is_confirmed' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships

    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    public function scopeConfirmed($query)
    {
        return $query->where('is_confirmed', true);
    }

    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Accessors

    public function getDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->name;
    }

    public function getDisplayEmailAttribute(): ?string
    {
        if ($this->user) {
            return $this->user->email;
        }

        return $this->email;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo) {
            return asset('storage/' . $this->photo);
        }

        if ($this->user && $this->user->avatar) {
            return asset('storage/' . $this->user->avatar);
        }

        return null;
    }

    public function getFullTitleAttribute(): string
    {
        $parts = [];

        if ($this->title) {
            $parts[] = $this->title;
        }

        if ($this->company) {
            $parts[] = $this->company;
        }

        return implode(' - ', $parts);
    }

    // Methods

    public function isSpeaker(): bool
    {
        return $this->role === 'speaker';
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    public function isPanelist(): bool
    {
        return $this->role === 'panelist';
    }

    public function getSocialLink(string $platform): ?string
    {
        return $this->social_links[$platform] ?? null;
    }
}
