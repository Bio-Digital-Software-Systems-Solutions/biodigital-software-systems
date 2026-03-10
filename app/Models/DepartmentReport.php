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

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property int|null $template_id
 * @property int $author_id
 * @property int|null $approver_id
 * @property string $title
 * @property ReportType $type
 * @property ReportStatus $status
 * @property ReportPeriodType $period_type
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property string|null $executive_summary
 * @property string|null $submission_notes
 * @property string|null $approval_notes
 * @property string|null $rejection_reason
 * @property \Illuminate\Support\Carbon|null $submitted_at
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property int $version
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportApproval> $approvals
 * @property-read int|null $approvals_count
 * @property-read \App\Models\User|null $approver
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \App\Models\User $author
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportComment> $comments
 * @property-read int|null $comments_count
 * @property-read \App\Models\Department $department
 * @property-read bool $can_edit
 * @property-read bool $can_submit
 * @property-read \App\Models\ReportApproval|null $current_approval_step
 * @property-read string $period_label
 * @property-read int $progress
 * @property-read string $status_color
 * @property-read string $status_icon
 * @property-read string $status_label
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentKpiValue> $kpiValues
 * @property-read int|null $kpi_values_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportSection> $sections
 * @property-read int|null $sections_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportTag> $tags
 * @property-read int|null $tags_count
 * @property-read \App\Models\ReportTemplate|null $template
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportVersion> $versions
 * @property-read int|null $versions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport byType(\App\Enums\Report\ReportType $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport byYear(int $year)
 * @method static \Database\Factories\DepartmentReportFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport forDepartment(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport forPeriod(\Carbon\Carbon $start, \Carbon\Carbon $end)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport published()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereApprovalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereApproverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereExecutiveSummary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport wherePeriodEnd($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport wherePeriodStart($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport wherePeriodType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereSubmissionNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport withStatus(\App\Enums\Report\ReportStatus $status)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentReport withoutTrashed()
 * @mixin \Eloquent
 */
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
        static::creating(fn($m) => $m->uuid ??= (string) Str::uuid());
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
        if ($required->isEmpty()) {
            return true;
        }
        return $required->every(fn($s) => $s->is_complete);
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
            'sections' => $this->sections->map(fn($s): array => [
                'type' => $s->type->value,
                'title' => $s->title,
                'content' => $s->content,
            ])->toArray(),
            'version' => $this->version,
            'exported_at' => now()->toIso8601String(),
        ];
    }
}
