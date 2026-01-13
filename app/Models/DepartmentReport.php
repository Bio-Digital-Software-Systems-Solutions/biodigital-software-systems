<?php

namespace App\Models;

use App\Enums\Report\ReportStatus;
use App\Enums\Report\ReportType;
use App\Enums\Report\ReportPeriodType;
use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class DepartmentReport extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, ClearsCache;

    protected $fillable = [
        'uuid',
        'department_id',
        'template_id',
        'author_id',
        'approver_id',
        'title',
        'type',
        'status',
        'period_type',
        'period_start',
        'period_end',
        'executive_summary',
        'submission_notes',
        'approval_notes',
        'rejection_reason',
        'submitted_at',
        'approved_at',
        'published_at',
        'version',
        'metadata',
    ];

    protected $casts = [
        'type' => ReportType::class,
        'status' => ReportStatus::class,
        'period_type' => ReportPeriodType::class,
        'period_start' => 'date',
        'period_end' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'period_label',
        'progress',
        'can_edit',
        'can_submit',
        'status_label',
        'status_color',
        'status_icon',
    ];

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
        static::creating(fn($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // Relations
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ReportSection::class, 'report_id')->orderBy('order');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ReportApproval::class, 'report_id')->orderBy('step');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReportComment::class, 'report_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ReportVersion::class, 'report_id')->orderByDesc('version_number');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class, 'report_id');
    }

    public function kpiValues(): HasMany
    {
        return $this->hasMany(DepartmentKpiValue::class, 'report_id');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(ReportTag::class, 'report_id');
    }

    // Scopes
    public function scopeForDepartment($q, int $id)
    {
        return $q->where('department_id', $id);
    }

    public function scopeForPeriod($q, Carbon $start, Carbon $end)
    {
        return $q->where('period_start', '>=', $start)->where('period_end', '<=', $end);
    }

    public function scopeWithStatus($q, ReportStatus $status)
    {
        return $q->where('status', $status->value);
    }

    public function scopePending($q)
    {
        return $q->whereIn('status', [ReportStatus::PENDING_REVIEW->value, ReportStatus::UNDER_REVIEW->value]);
    }

    public function scopePublished($q)
    {
        return $q->where('status', ReportStatus::PUBLISHED->value);
    }

    public function scopeByType($q, ReportType $type)
    {
        return $q->where('type', $type->value);
    }

    public function scopeByYear($q, int $year)
    {
        return $q->whereYear('period_start', $year);
    }

    // Accessors
    public function getPeriodLabelAttribute(): string
    {
        return match ($this->period_type) {
            ReportPeriodType::MONTHLY => $this->period_start->translatedFormat('F Y'),
            ReportPeriodType::QUARTERLY => 'T' . ceil($this->period_start->month / 3) . ' ' . $this->period_start->year,
            ReportPeriodType::ANNUAL => (string) $this->period_start->year,
            ReportPeriodType::WEEKLY => 'Semaine ' . $this->period_start->weekOfYear . ' ' . $this->period_start->year,
            default => $this->period_start->format('d/m/Y') . ' - ' . $this->period_end->format('d/m/Y'),
        };
    }

    public function getProgressAttribute(): int
    {
        if ($this->sections->isEmpty()) {
            return 0;
        }
        $completed = $this->sections->filter(fn($s) => $s->is_complete)->count();
        return (int) round(($completed / $this->sections->count()) * 100);
    }

    public function getCanEditAttribute(): bool
    {
        return in_array($this->status, [ReportStatus::DRAFT, ReportStatus::REVISION_REQUESTED]);
    }

    public function getCanSubmitAttribute(): bool
    {
        if ($this->status !== ReportStatus::DRAFT) {
            return false;
        }
        $required = $this->sections->where('is_required', true);
        return $required->isEmpty() || $required->every(fn($s) => $s->is_complete);
    }

    public function getCurrentApprovalStepAttribute(): ?ReportApproval
    {
        return $this->approvals->where('status', 'pending')->sortBy('step')->first();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status->label();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status->color();
    }

    public function getStatusIconAttribute(): string
    {
        return $this->status->icon();
    }

    // Méthodes
    public function transitionTo(ReportStatus $newStatus): bool
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            return false;
        }
        $this->status = $newStatus;
        match ($newStatus) {
            ReportStatus::PENDING_REVIEW => $this->submitted_at = now(),
            ReportStatus::APPROVED => $this->approved_at = now(),
            ReportStatus::PUBLISHED => $this->published_at = now(),
            default => null,
        };
        return $this->save();
    }

    public function createVersion(?string $summary = null): ReportVersion
    {
        return $this->versions()->create([
            'version_number' => $this->version,
            'snapshot' => $this->toExportArray(),
            'change_summary' => $summary,
            'created_by' => auth()->id(),
        ]);
    }

    public function duplicate(): self
    {
        $new = $this->replicate(['uuid', 'status', 'submitted_at', 'approved_at', 'published_at', 'version']);
        $new->uuid = (string) Str::uuid();
        $new->status = ReportStatus::DRAFT;
        $new->title = $this->title . ' (copie)';
        $new->version = 1;
        $new->save();

        foreach ($this->sections as $section) {
            $newSection = $section->replicate(['uuid']);
            $newSection->uuid = (string) Str::uuid();
            $newSection->report_id = $new->id;
            $newSection->save();
        }

        return $new;
    }

    public function toExportArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'title' => $this->title,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'period_label' => $this->period_label,
            'executive_summary' => $this->executive_summary,
            'department' => $this->department?->only(['id', 'name']),
            'author' => $this->author?->only(['id', 'first_name', 'last_name', 'email']),
            'sections' => $this->sections->map(fn($s) => [
                'type' => $s->type->value,
                'title' => $s->title,
                'content' => $s->content,
            ])->toArray(),
            'version' => $this->version,
            'exported_at' => now()->toIso8601String(),
        ];
    }
}
