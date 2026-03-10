<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property string $name
 * @property string $slug
 * @property int $year
 * @property int $month
 * @property bool $is_system
 * @property int $sort_order
 * @property int|null $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentDocument> $documents
 * @property-read int|null $documents_count
 * @property-read string $month_name
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereIsSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory whereYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentDocumentCategory withoutTrashed()
 * @mixin \Eloquent
 */
class DepartmentDocumentCategory extends Model
{
    use HasFactory, LogsActivity, ClearsCache, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'department_id',
        'name',
        'slug',
        'year',
        'month',
        'is_system',
        'sort_order',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'is_system' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the department that owns the category.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who created the category.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the documents in this category.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(DepartmentDocument::class, 'category', 'slug')
            ->where('department_id', $this->department_id)
            ->where('year', $this->year)
            ->where('month', $this->month);
    }

    /**
     * Get month name.
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return $months[$this->month] ?? '';
    }

    /**
     * Ensure the 'rapports' system category exists for a given department/year/month.
     */
    public static function ensureRapportsCategory(int $departmentId, int $year, int $month): self
    {
        return self::firstOrCreate(
            [
                'department_id' => $departmentId,
                'year' => $year,
                'month' => $month,
                'slug' => 'rapports',
            ],
            [
                'name' => 'Rapports',
                'is_system' => true,
                'sort_order' => 0,
            ]
        );
    }

    /**
     * Ensure a default category exists for a given department/year/month.
     * This category is named after the month and used as default when no category is specified.
     */
    public static function ensureDefaultMonthCategory(int $departmentId, int $year, int $month): self
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        $monthName = $months[$month] ?? 'Documents';
        $slug = Str::slug($monthName);

        return self::firstOrCreate(
            [
                'department_id' => $departmentId,
                'year' => $year,
                'month' => $month,
                'slug' => $slug,
            ],
            [
                'name' => $monthName,
                'is_system' => true,
                'sort_order' => -1, // Show before other categories
            ]
        );
    }

    /**
     * Get all categories for a department/year/month, creating default categories if needed.
     */
    public static function getCategoriesForMonth(int $departmentId, int $year, int $month): \Illuminate\Database\Eloquent\Collection
    {
        // Ensure default month category exists
        self::ensureDefaultMonthCategory($departmentId, $year, $month);
        // Ensure rapports category exists
        self::ensureRapportsCategory($departmentId, $year, $month);

        return self::where('department_id', $departmentId)
            ->where('year', $year)
            ->where('month', $month)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
