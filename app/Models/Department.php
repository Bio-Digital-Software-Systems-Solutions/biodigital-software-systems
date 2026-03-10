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
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string|null $image
 * @property int|null $head_of_department
 * @property int|null $first_deputy_id
 * @property int|null $second_deputy_id
 * @property numeric|null $budget
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentPosition> $activePositions
 * @property-read int|null $active_positions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentWorkflow> $activeWorkflows
 * @property-read int|null $active_workflows_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Appointment> $appointments
 * @property-read int|null $appointments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentDocumentCategory> $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentDocument> $documents
 * @property-read int|null $documents_count
 * @property-read \App\Models\User|null $firstDeputy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentForm> $forms
 * @property-read int|null $forms_count
 * @property-read mixed $head_of_department_user
 * @property-read int|null $users_count
 * @property-read \App\Models\User|null $headOfDepartment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Appointment> $meetingAppointments
 * @property-read int|null $meeting_appointments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentMeeting> $meetings
 * @property-read int|null $meetings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentNeed> $needs
 * @property-read int|null $needs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentNeed> $pendingNeeds
 * @property-read int|null $pending_needs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentPosition> $positions
 * @property-read int|null $positions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentForm> $publishedForms
 * @property-read int|null $published_forms_count
 * @property-read \App\Models\User|null $secondDeputy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scheduling\DepartmentTodo> $todos
 * @property-read int|null $todos_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Appointment> $upcomingAppointments
 * @property-read int|null $upcoming_appointments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentMeeting> $upcomingMeetings
 * @property-read int|null $upcoming_meetings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WorkflowInstance> $workflowInstances
 * @property-read int|null $workflow_instances_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentWorkflow> $workflows
 * @property-read int|null $workflows_count
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereFirstDeputyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereHeadOfDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereSecondDeputyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department withoutTrashed()
 * @mixin \Eloquent
 */
class Department extends Model
{
    use ClearsCache, HasFactory, LogsActivity, SoftDeletes;

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

        static::creating(function ($model): void {
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
        'first_deputy_id',
        'second_deputy_id',
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
     * Alias for users() - for scheduling system compatibility.
     */
    public function members(): BelongsToMany
    {
        return $this->users();
    }

    /**
     * Get the todos for this department.
     */
    public function todos(): HasMany
    {
        return $this->hasMany(\App\Models\Scheduling\DepartmentTodo::class);
    }

    /**
     * Get the head of department user.
     */
    public function headOfDepartment(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_of_department');
    }

    /**
     * Get the first deputy of the department.
     */
    public function firstDeputy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_deputy_id');
    }

    /**
     * Get the second deputy of the department.
     */
    public function secondDeputy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'second_deputy_id');
    }

    /**
     * Get the total number of users in this department.
     * Uses eager-loaded count if available to avoid N+1 queries.
     */
    public function getUsersCountAttribute(): int
    {
        // Check if users_count was eager-loaded with withCount('users')
        if (array_key_exists('users_count', $this->attributes)) {
            return (int) $this->attributes['users_count'];
        }

        // Check if users relationship was already loaded
        if ($this->relationLoaded('users')) {
            return $this->users->count();
        }

        // Fallback to query (should be avoided in lists)
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
     * Check if a user can access this department.
     * Access is granted if:
     * - User has "manage departments" permission
     * - User has "access all departments" permission
     * - User is the head of department
     * - User is a first or second deputy
     * - User is a member of the department
     */
    public function isAccessibleBy(User $user): bool
    {
        // Admin/manager with permission can always access
        if ($user->can('manage departments')) {
            return true;
        }

        // Users with "access all departments" permission can access any department
        if ($user->can('access all departments')) {
            return true;
        }

        // Head of department can access
        if ($this->head_of_department === $user->id) {
            return true;
        }

        // Deputies can access
        if ($this->first_deputy_id === $user->id || $this->second_deputy_id === $user->id) {
            return true;
        }

        // Member of the department can access
        // Use eager-loaded relationship if available
        if ($this->relationLoaded('users')) {
            return $this->users->contains('id', $user->id);
        }

        return $this->users()->where('user_id', $user->id)->exists();
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
            ->whereHas('appointment', function ($query): void {
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

    /**
     * Get document categories for this department.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(DepartmentDocumentCategory::class);
    }

    /**
     * Get positions for this department.
     */
    public function positions(): HasMany
    {
        return $this->hasMany(DepartmentPosition::class);
    }

    /**
     * Get active positions for this department.
     */
    public function activePositions(): HasMany
    {
        return $this->hasMany(DepartmentPosition::class)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}
