<?php

namespace App\Models;

use App\Enums\Report\ReportType;
use App\Enums\Report\ReportPeriodType;
use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReportTemplate extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, ClearsCache;

    protected $fillable = [
        'uuid',
        'department_id',
        'name',
        'description',
        'type',
        'period_type',
        'sections_config',
        'default_approvers',
        'is_active',
        'auto_generate',
        'auto_generate_day',
        'metadata',
    ];

    protected $casts = [
        'type' => ReportType::class,
        'period_type' => ReportPeriodType::class,
        'sections_config' => 'array',
        'default_approvers' => 'array',
        'is_active' => 'boolean',
        'auto_generate' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = [
        'type_label',
        'period_type_label',
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

    public function reports(): HasMany
    {
        return $this->hasMany(DepartmentReport::class, 'template_id');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(ReportReminder::class, 'template_id');
    }

    // Scopes
    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeForDepartment($q, int $id)
    {
        return $q->where('department_id', $id);
    }

    public function scopeAutoGenerate($q)
    {
        return $q->where('auto_generate', true);
    }

    public function scopeByType($q, ReportType $type)
    {
        return $q->where('type', $type->value);
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return $this->type->label();
    }

    public function getPeriodTypeLabelAttribute(): string
    {
        return $this->period_type->label();
    }

    // Methods
    public function getSectionsConfig(): array
    {
        return $this->sections_config ?? $this->type->defaultSections();
    }

    public function getDefaultApprovers(): array
    {
        return $this->default_approvers ?? [];
    }
}
