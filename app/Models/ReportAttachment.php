<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $uuid
 * @property int $report_id
 * @property int|null $section_id
 * @property int $uploaded_by
 * @property string $filename
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size
 * @property string $path
 * @property array<array-key, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read string $extension
 * @property-read bool $is_image
 * @property-read bool $is_pdf
 * @property-read string $size_formatted
 * @property-read string $url
 * @property-read \App\Models\DepartmentReport $report
 * @property-read \App\Models\ReportSection|null $section
 * @property-read \App\Models\User $uploader
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment forReport(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment forSection(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment images()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment pdfs()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereMimeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereOriginalFilename($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereSectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereUploadedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportAttachment withoutTrashed()
 * @mixin \Eloquent
 */
class ReportAttachment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'report_id',
        'section_id',
        'uploaded_by',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'path',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $appends = [
        'url',
        'size_formatted',
        'is_image',
        'is_pdf',
        'extension',
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
        static::deleting(function ($attachment): void {
            if ($attachment->isForceDeleting()) {
                Storage::disk('public')->delete($attachment->path);
            }
        });
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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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

    public function scopeImages($q)
    {
        return $q->where('mime_type', 'like', 'image/%');
    }

    public function scopePdfs($q)
    {
        return $q->where('mime_type', 'application/pdf');
    }

    // Accessors
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    public function getSizeFormattedAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_filename, PATHINFO_EXTENSION);
    }

    // Methods
    public function download()
    {
        return Storage::disk('public')->download($this->path, $this->original_filename);
    }

    public function getContents(): ?string
    {
        return Storage::disk('public')->get($this->path);
    }
}
