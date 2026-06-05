<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $homepage_section_id
 * @property string $block_type
 * @property array<string, mixed> $content
 * @property array<string, mixed>|null $design_settings
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\HomepageSection $section
 */
class HomepageSubsection extends Model
{
    use ClearsCache;
    use HasFactory;
    use HasUuid;
    use LogsActivity;

    public const BLOCK_TYPES = ['heading', 'paragraph', 'image', 'button', 'card'];

    protected $fillable = [
        'homepage_section_id',
        'block_type',
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
            'homepage_section_id' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(HomepageSection::class, 'homepage_section_id');
    }
}
