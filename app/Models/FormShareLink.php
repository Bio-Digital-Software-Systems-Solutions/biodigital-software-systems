<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $form_id
 * @property int $created_by
 * @property string $token
 * @property \Illuminate\Support\Carbon $expires_at
 * @property int|null $max_uses
 * @property int $use_count
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\DepartmentForm $form
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink whereFormId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink whereMaxUses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FormShareLink whereUseCount($value)
 * @mixin \Eloquent
 */
class FormShareLink extends Model
{
    protected $fillable = [
        'form_id',
        'created_by',
        'token',
        'expires_at',
        'max_uses',
        'use_count',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'max_uses' => 'integer',
        'use_count' => 'integer',
    ];

    /**
     * Generate a secure random token.
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32)); // 64 character hex string
    }

    /**
     * Create a new share link for a form.
     */
    public static function createForForm(
        DepartmentForm $form,
        int $createdBy,
        int $expiresInHours = 24,
        ?int $maxUses = null
    ): self {
        return self::create([
            'form_id' => $form->id,
            'created_by' => $createdBy,
            'token' => self::generateToken(),
            'expires_at' => now()->addHours($expiresInHours),
            'max_uses' => $maxUses,
            'use_count' => 0,
            'is_active' => true,
        ]);
    }

    /**
     * Find a valid share link by token.
     */
    public static function findValidByToken(string $token): ?self
    {
        return self::where('token', $token)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->where(function ($query): void {
                $query->whereNull('max_uses')
                    ->orWhereColumn('use_count', '<', 'max_uses');
            })
            ->first();
    }

    /**
     * Check if the link is valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->use_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Increment the use count.
     */
    public function incrementUseCount(): void
    {
        $this->increment('use_count');
    }

    /**
     * Deactivate the link.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get the full URL for this share link.
     */
    public function getUrl(): string
    {
        return route('forms.shared', $this->token);
    }

    /**
     * Relationships
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(DepartmentForm::class, 'form_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
