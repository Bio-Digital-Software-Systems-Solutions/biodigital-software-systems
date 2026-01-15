<?php

namespace App\Models\Event;

use App\Enums\Event\BadgeStatus;
use App\Models\User;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EventBadge extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $fillable = [
        'registration_id',
        'badge_number',
        'status',
        'badge_type',
        'file_path',
        'badge_data',
        'generated_at',
        'printed_at',
        'printed_by',
        'collected_at',
        'collected_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => BadgeStatus::class,
            'badge_data' => 'array',
            'generated_at' => 'datetime',
            'printed_at' => 'datetime',
            'collected_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($badge) {
            if (empty($badge->badge_number)) {
                $badge->badge_number = static::generateBadgeNumber();
            }
        });
    }

    // Relationships

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    public function printedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    public function collectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', BadgeStatus::PENDING);
    }

    public function scopeGenerated($query)
    {
        return $query->where('status', BadgeStatus::GENERATED);
    }

    public function scopePrinted($query)
    {
        return $query->where('status', BadgeStatus::PRINTED);
    }

    public function scopeCollected($query)
    {
        return $query->where('status', BadgeStatus::COLLECTED);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('badge_type', $type);
    }

    public function scopeNeedsPrinting($query)
    {
        return $query->whereIn('status', [BadgeStatus::GENERATED, BadgeStatus::LOST]);
    }

    // Accessors

    public function getIsPendingAttribute(): bool
    {
        return $this->status === BadgeStatus::PENDING;
    }

    public function getIsGeneratedAttribute(): bool
    {
        return $this->status === BadgeStatus::GENERATED;
    }

    public function getIsPrintedAttribute(): bool
    {
        return $this->status === BadgeStatus::PRINTED;
    }

    public function getIsCollectedAttribute(): bool
    {
        return $this->status === BadgeStatus::COLLECTED;
    }

    public function getFileUrlAttribute(): ?string
    {
        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }

        return null;
    }

    public function getCanPrintAttribute(): bool
    {
        return $this->status->canPrint();
    }

    public function getCanCollectAttribute(): bool
    {
        return $this->status->canCollect();
    }

    // Methods

    public function generate(array $data = []): void
    {
        $badgeData = array_merge([
            'name' => $this->registration->full_name,
            'email' => $this->registration->email,
            'company' => $this->registration->company,
            'job_title' => $this->registration->job_title,
            'role' => $this->registration->participant_role->value,
            'event_name' => $this->registration->event->title,
            'qr_code' => $this->registration->qr_code,
            'badge_number' => $this->badge_number,
            'badge_type' => $this->badge_type,
        ], $data);

        $this->update([
            'badge_data' => $badgeData,
            'status' => BadgeStatus::GENERATED,
            'generated_at' => now(),
        ]);
    }

    public function markAsPrinted(?int $printedBy = null): void
    {
        $this->update([
            'status' => BadgeStatus::PRINTED,
            'printed_at' => now(),
            'printed_by' => $printedBy,
        ]);
    }

    public function markAsCollected(?int $collectedBy = null): void
    {
        $this->update([
            'status' => BadgeStatus::COLLECTED,
            'collected_at' => now(),
            'collected_by' => $collectedBy,
        ]);
    }

    public function markAsLost(?string $notes = null): void
    {
        $this->update([
            'status' => BadgeStatus::LOST,
            'notes' => $notes,
        ]);
    }

    public function replace(?int $replacedBy = null): self
    {
        // Mark current badge as replaced
        $this->update([
            'status' => BadgeStatus::REPLACED,
            'notes' => ($this->notes ? $this->notes . "\n" : '') . 'Replaced on ' . now()->format('Y-m-d H:i:s'),
        ]);

        // Create new badge
        $newBadge = static::create([
            'registration_id' => $this->registration_id,
            'badge_type' => $this->badge_type,
            'status' => BadgeStatus::PENDING,
            'notes' => 'Replacement for badge #' . $this->badge_number,
        ]);

        // Generate the new badge with the same data
        $newBadge->generate();

        return $newBadge;
    }

    // Static methods

    public static function generateBadgeNumber(): string
    {
        do {
            $number = 'BDG-' . strtoupper(Str::random(6));
        } while (static::where('badge_number', $number)->exists());

        return $number;
    }

    public static function findByBadgeNumber(string $number): ?self
    {
        return static::where('badge_number', $number)->first();
    }

    public static function getStatsByEvent(int $eventId): array
    {
        return [
            'pending' => static::whereHas('registration', fn ($q) => $q->where('event_id', $eventId))
                ->pending()->count(),
            'generated' => static::whereHas('registration', fn ($q) => $q->where('event_id', $eventId))
                ->generated()->count(),
            'printed' => static::whereHas('registration', fn ($q) => $q->where('event_id', $eventId))
                ->printed()->count(),
            'collected' => static::whereHas('registration', fn ($q) => $q->where('event_id', $eventId))
                ->collected()->count(),
        ];
    }
}
