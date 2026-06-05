<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $key
 * @property string $type
 * @property string|null $title
 * @property array<string, mixed>|null $content
 * @property array<string, mixed>|null $design_settings
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HomepageSubsection> $subsections
 */
class HomepageSection extends Model
{
    use ClearsCache;
    use HasFactory;
    use HasUuid;
    use LogsActivity;

    public const TYPES = ['about', 'activities', 'training', 'contact', 'custom'];

    protected $fillable = [
        'key',
        'type',
        'title',
        'content',
        'design_settings',
        'order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'design_settings' => 'array',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function subsections(): HasMany
    {
        return $this->hasMany(HomepageSubsection::class)->orderBy('order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('order');
    }
}
