<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class IntegrationSuggestion extends Model
{
    use ClearsCache, HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'visitor_visit_id',
        'suggested_to',
        'score_at_suggestion',
        'status',
        'responded_at',
        'response_notes',
    ];

    protected function casts(): array
    {
        return [
            'score_at_suggestion' => 'decimal:2',
            'responded_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function visitorVisit(): BelongsTo
    {
        return $this->belongsTo(VisitorVisit::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_to');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
