<?php

namespace App\Models\Accounting;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $account_number
 * @property string $name
 * @property string|null $description
 * @property int $class_id
 * @property int|null $parent_id
 * @property int $level
 * @property string $normal_balance
 * @property bool $is_active
 * @property int $sort_order
 */
class OhadaAccount extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'account_number',
        'name',
        'description',
        'class_id',
        'parent_id',
        'level',
        'normal_balance',
        'is_active',
        'sort_order',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function accountClass(): BelongsTo
    {
        return $this->belongsTo(OhadaAccountClass::class, 'class_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeByClass(Builder $query, int $classNumber): Builder
    {
        return $query->whereHas('accountClass', fn (Builder $q) => $q->where('class_number', $classNumber));
    }
}
