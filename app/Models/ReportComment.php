<?php

namespace App\Models;

use App\Enums\Report\CommentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property int $report_id
 * @property int|null $section_id
 * @property int $user_id
 * @property int|null $parent_id
 * @property CommentType $type
 * @property string $content
 * @property bool $is_resolved
 * @property int|null $resolved_by
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read string $type_color
 * @property-read string $type_icon
 * @property-read string $type_label
 * @property-read ReportComment|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ReportComment> $replies
 * @property-read int|null $replies_count
 * @property-read \App\Models\DepartmentReport $report
 * @property-read \App\Models\User|null $resolver
 * @property-read \App\Models\ReportSection|null $section
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment byType(\App\Enums\Report\CommentType $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment forReport(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment forSection(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment resolved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment rootLevel()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment unresolved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereIsResolved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereResolvedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereResolvedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereSectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportComment withoutTrashed()
 * @mixin \Eloquent
 */
class ReportComment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'report_id',
        'section_id',
        'user_id',
        'parent_id',
        'type',
        'content',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'metadata',
    ];

    protected $casts = [
        'type' => CommentType::class,
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'type_label',
        'type_icon',
        'type_color',
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
    public function report(): BelongsTo
    {
        return $this->belongsTo(DepartmentReport::class, 'report_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ReportSection::class, 'section_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ReportComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ReportComment::class, 'parent_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeForReport($q, int $id)
    {
        return $q->where('report_id', $id);
    }

    public function scopeForSection($q, int $id)
    {
        return $q->where('section_id', $id);
    }

    public function scopeRootLevel($q)
    {
        return $q->whereNull('parent_id');
    }

    public function scopeUnresolved($q)
    {
        return $q->where('is_resolved', false);
    }

    public function scopeResolved($q)
    {
        return $q->where('is_resolved', true);
    }

    public function scopeByType($q, CommentType $type)
    {
        return $q->where('type', $type->value);
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return $this->type->label();
    }

    public function getTypeIconAttribute(): string
    {
        return $this->type->icon();
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type->color();
    }

    // Methods
    public function resolve(?int $userId = null): self
    {
        $this->is_resolved = true;
        $this->resolved_by = $userId ?? auth()->id();
        $this->resolved_at = now();
        $this->save();
        return $this;
    }

    public function unresolve(): self
    {
        $this->is_resolved = false;
        $this->resolved_by = null;
        $this->resolved_at = null;
        $this->save();
        return $this;
    }

    public function reply(int $userId, string $content, ?CommentType $type = null): self
    {
        return static::create([
            'report_id' => $this->report_id,
            'section_id' => $this->section_id,
            'user_id' => $userId,
            'parent_id' => $this->id,
            'type' => $type ?? CommentType::COMMENT,
            'content' => $content,
        ]);
    }
}
