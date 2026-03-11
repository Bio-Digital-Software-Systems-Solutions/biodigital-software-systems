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
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property array|null $applicable_entities
 * @property array|null $required_statements
 * @property string|null $revenue_threshold
 * @property bool $is_active
 * @property int $sort_order
 */
class AccountingSystem extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'applicable_entities',
        'required_statements',
        'revenue_threshold',
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
            'applicable_entities' => 'array',
            'required_statements' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function financialStatements(): HasMany
    {
        return $this->hasMany(OhadaFinancialStatement::class)->orderBy('sort_order');
    }
}
