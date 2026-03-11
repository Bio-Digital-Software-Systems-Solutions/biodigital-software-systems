<?php

namespace App\Models\Accounting;

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
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property int $accounting_system_id
 * @property array|null $structure
 * @property bool $is_required
 * @property int $sort_order
 */
class OhadaFinancialStatement extends Model
{
    use ClearsCache, HasFactory, HasUuid, LogsActivity;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'accounting_system_id',
        'structure',
        'is_required',
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
            'structure' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function accountingSystem(): BelongsTo
    {
        return $this->belongsTo(AccountingSystem::class);
    }
}
