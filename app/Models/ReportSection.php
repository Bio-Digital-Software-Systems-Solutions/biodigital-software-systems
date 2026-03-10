<?php

namespace App\Models;

use App\Enums\Report\ReportSectionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property int $report_id
 * @property ReportSectionType $type
 * @property string $title
 * @property string|null $description
 * @property array<array-key, mixed>|null $content
 * @property int $order
 * @property bool $is_required
 * @property bool $is_visible
 * @property array<array-key, mixed>|null $config
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportAttachment> $attachments
 * @property-read int|null $attachments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReportComment> $comments
 * @property-read int|null $comments_count
 * @property-read bool $is_complete
 * @property-read string $type_icon
 * @property-read string $type_label
 * @property-read \App\Models\DepartmentReport $report
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereIsRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereIsVisible($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportSection whereUuid($value)
 * @mixin \Eloquent
 */
class ReportSection extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'report_id',
        'type',
        'title',
        'description',
        'content',
        'order',
        'is_required',
        'is_visible',
        'config',
        'metadata',
    ];

    protected $casts = [
        'type' => ReportSectionType::class,
        'content' => 'array',
        'is_required' => 'boolean',
        'is_visible' => 'boolean',
        'config' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'is_complete',
        'type_label',
        'type_icon',
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

    public function comments(): HasMany
    {
        return $this->hasMany(ReportComment::class, 'section_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class, 'section_id');
    }

    // Accessors
    public function getIsCompleteAttribute(): bool
    {
        if (empty($this->content)) {
            return false;
        }

        return match ($this->type) {
            ReportSectionType::TEXT => !empty($this->content['text'] ?? null),
            ReportSectionType::METRICS => !empty($this->content['metrics'] ?? []),
            ReportSectionType::CHART => !empty($this->content['data'] ?? []),
            ReportSectionType::TABLE => !empty($this->content['rows'] ?? []),
            ReportSectionType::CHECKLIST => collect($this->content['items'] ?? [])->every(fn($i) => $i['completed'] ?? false),
            ReportSectionType::LIST => !empty($this->content['items'] ?? []),
            ReportSectionType::BUDGET => isset($this->content['total']),
            ReportSectionType::TIMELINE => !empty($this->content['events'] ?? []),
            ReportSectionType::GALLERY => !empty($this->content['images'] ?? []),
            ReportSectionType::CUSTOM => !empty($this->content),
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type->label();
    }

    public function getTypeIconAttribute(): string
    {
        return $this->type->icon();
    }

    // Methods
    public function updateContent(array $content): self
    {
        $this->content = array_merge($this->content ?? [], $content);
        $this->save();
        return $this;
    }

    public function getDefaultConfig(): array
    {
        return $this->config ?? $this->type->defaultConfig();
    }
}
