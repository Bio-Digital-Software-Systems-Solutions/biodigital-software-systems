<?php

namespace App\Models\Scheduling;

use App\Enums\Scheduling\SwapRequestStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShiftSwapRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'requester_id',
        'target_user_id',
        'requested_shift_id',
        'offered_shift_id',
        'status',
        'reason',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'expires_at',
    ];

    protected $casts = [
        'status' => SwapRequestStatus::class,
        'approved_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addDays(3);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relations
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function requestedShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'requested_shift_id');
    }

    public function offeredShift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'offered_shift_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Alias for approvedBy to match controller expectations
     */
    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeForRequester(Builder $query, int $userId): Builder
    {
        return $query->where('requester_id', $userId);
    }

    public function scopeForTarget(Builder $query, int $userId): Builder
    {
        return $query->where('target_user_id', $userId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('requester_id', $userId)
                ->orWhere('target_user_id', $userId);
        });
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', SwapRequestStatus::PENDING_COLLEAGUE)
            ->orWhere('status', SwapRequestStatus::PENDING_MANAGER);
    }

    public function scopePendingForColleague(Builder $query): Builder
    {
        return $query->where('status', SwapRequestStatus::PENDING_COLLEAGUE);
    }

    public function scopePendingForManager(Builder $query): Builder
    {
        return $query->where('status', SwapRequestStatus::PENDING_MANAGER);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now())
            ->whereIn('status', [
                SwapRequestStatus::PENDING_COLLEAGUE,
                SwapRequestStatus::PENDING_MANAGER,
            ]);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at <= now() && $this->status->isPending();
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status->isPending();
    }

    public function getCanBeAcceptedByColleagueAttribute(): bool
    {
        return $this->status === SwapRequestStatus::PENDING_COLLEAGUE && !$this->is_expired;
    }

    public function getCanBeApprovedByManagerAttribute(): bool
    {
        return $this->status === SwapRequestStatus::PENDING_MANAGER && !$this->is_expired;
    }

    // Methods
    public function acceptByColleague(): bool
    {
        if (!$this->can_be_accepted_by_colleague) {
            return false;
        }

        $this->update([
            'status' => SwapRequestStatus::PENDING_MANAGER,
        ]);

        return true;
    }

    public function rejectByColleague(): bool
    {
        if ($this->status !== SwapRequestStatus::PENDING_COLLEAGUE) {
            return false;
        }

        $this->update([
            'status' => SwapRequestStatus::REJECTED_COLLEAGUE,
        ]);

        return true;
    }

    public function approveByManager(User $manager): bool
    {
        if (!$this->can_be_approved_by_manager) {
            return false;
        }

        return DB::transaction(function () use ($manager) {
            $this->update([
                'status' => SwapRequestStatus::APPROVED,
                'approved_by' => $manager->id,
                'approved_at' => now(),
            ]);

            // Execute the swap
            return $this->executeSwap();
        });
    }

    public function rejectByManager(User $manager, ?string $reason = null): bool
    {
        if ($this->status !== SwapRequestStatus::PENDING_MANAGER) {
            return false;
        }

        $this->update([
            'status' => SwapRequestStatus::REJECTED_MANAGER,
            'approved_by' => $manager->id,
            'rejection_reason' => $reason,
            'approved_at' => now(),
        ]);

        return true;
    }

    public function approve(User $approver): bool
    {
        $this->update([
            'status' => SwapRequestStatus::APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        return true;
    }

    public function cancel(): bool
    {
        if ($this->status->isFinal()) {
            return false;
        }

        $this->update(['status' => SwapRequestStatus::CANCELLED]);
        return true;
    }

    public function markAsExpired(): bool
    {
        if (!$this->status->isPending()) {
            return false;
        }

        $this->update(['status' => SwapRequestStatus::EXPIRED]);
        return true;
    }

    protected function executeSwap(): bool
    {
        $requestedShift = $this->requestedShift;
        $offeredShift = $this->offeredShift;

        // Swap the assignments
        $tempUserId = $requestedShift->user_id;

        $requestedShift->update(['user_id' => $offeredShift ? $offeredShift->user_id : $this->requester_id]);

        if ($offeredShift) {
            $offeredShift->update(['user_id' => $tempUserId]);
        }

        return true;
    }

    public static function markExpiredRequests(): int
    {
        return self::expired()
            ->update(['status' => SwapRequestStatus::EXPIRED]);
    }
}
