<?php

namespace App\Models\Accounting;

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
 * @property int $class_number
 * @property string $name
 * @property string|null $description
 * @property string $category
 * @property int $sort_order
 */
class PcgAccountClass extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'class_number',
        'name',
        'description',
        'category',
        'sort_order',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(PcgAccount::class, 'class_id')->orderBy('sort_order');
    }
}
