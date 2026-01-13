<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property int|null $head_of_department
 * @property numeric|null $budget
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read mixed $head_of_department_user
 * @property-read int|null $users_count
 * @property-read \App\Models\User|null $headOfDepartment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department active()
 * @method static \Database\Factories\DepartmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereBudget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereHeadOfDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withoutTrashed()
 * @property string $uuid
 * @property string|null $image
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUuid($value)
 * @mixin \Eloquent
 */
class Department extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, ClearsCache;

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
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'code',
        'description',
        'head_of_department',
        'budget',
        'is_active',
        'image',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The users that belong to the department.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'department_user')
            ->withTimestamps();
    }

    /**
     * Get the head of department user.
     */
    public function headOfDepartment(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_of_department');
    }

    /**
     * Get the total number of users in this department.
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Get the head of department user.
     */
    public function getHeadOfDepartmentUserAttribute()
    {
        return $this->headOfDepartment;
    }

    /**
     * Check if a user is the head of this department.
     */
    public function isHeadOfDepartment(User $user): bool
    {
        return $this->head_of_department == $user->id;
    }

    /**
     * Scope a query to only include active departments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order departments by name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get workflows for this department.
     */
    public function workflows(): HasMany
    {
        return $this->hasMany(DepartmentWorkflow::class);
    }

    /**
     * Get active workflows for this department.
     */
    public function activeWorkflows(): HasMany
    {
        return $this->hasMany(DepartmentWorkflow::class)->where('status', 'active');
    }

    /**
     * Get forms for this department.
     */
    public function forms(): HasMany
    {
        return $this->hasMany(DepartmentForm::class);
    }

    /**
     * Get published forms for this department.
     */
    public function publishedForms(): HasMany
    {
        return $this->hasMany(DepartmentForm::class)->where('status', 'published');
    }

    /**
     * Get needs for this department.
     */
    public function needs(): HasMany
    {
        return $this->hasMany(DepartmentNeed::class);
    }

    /**
     * Get pending needs for this department.
     */
    public function pendingNeeds(): HasMany
    {
        return $this->hasMany(DepartmentNeed::class)->whereIn('status', ['submitted', 'under_review']);
    }

    /**
     * Get workflow instances for this department.
     */
    public function workflowInstances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    /**
     * Get appointments for this department (polymorphic relation).
     */
    public function appointments(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'appointmentable');
    }

    /**
     * Get upcoming appointments for this department.
     */
    public function upcomingAppointments(): MorphMany
    {
        return $this->morphMany(Appointment::class, 'appointmentable')
            ->where('start_datetime', '>', now())
            ->orderBy('start_datetime');
    }

    /**
     * Get meetings for this department (through pivot table).
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(DepartmentMeeting::class);
    }

    /**
     * Get meeting appointments for this department (through pivot).
     */
    public function meetingAppointments(): BelongsToMany
    {
        return $this->belongsToMany(Appointment::class, 'department_meetings')
            ->withPivot(['uuid', 'created_by', 'notify_all_members', 'is_mandatory', 'notes', 'notified_at'])
            ->withTimestamps();
    }

    /**
     * Get upcoming meetings for this department.
     */
    public function upcomingMeetings(): HasMany
    {
        return $this->hasMany(DepartmentMeeting::class)
            ->whereHas('appointment', function ($query) {
                $query->where('start_datetime', '>', now());
            });
    }

    /**
     * Get documents for this department.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(DepartmentDocument::class);
    }
}
