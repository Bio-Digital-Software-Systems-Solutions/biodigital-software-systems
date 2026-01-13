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
        static::creating(fn($m) => $m->uuid = $m->uuid ?? (string) Str::uuid());
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
