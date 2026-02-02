<?php

namespace App\Models;

use App\Enums\Employee\EmployeeStatus;
use App\Enums\Employee\EmploymentType;
use App\Enums\Employee\PaymentMethod;
use App\Traits\ClearsCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use ClearsCache, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'department_id',
        'manager_id',
        'employee_number',
        'position',
        'job_title',
        'birth_date',
        'nationality',
        'social_security_number',
        'tax_id',
        'personal_email',
        'work_phone',
        'personal_phone',
        'address',
        'city',
        'postal_code',
        'country',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'status',
        'employment_type',
        'hire_date',
        'probation_end_date',
        'contract_end_date',
        'termination_date',
        'termination_reason',
        'hourly_rate',
        'monthly_salary',
        'payment_method',
        'bank_name',
        'bank_iban',
        'bank_bic',
        'weekly_hours',
        'working_days',
        'default_start_time',
        'default_end_time',
        'annual_leave_days',
        'remaining_leave_days',
        'sick_days_taken',
        'skills',
        'certifications',
        'languages',
        'avatar',
        'contract_document',
        'id_document',
        'notes',
        'internal_notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'probation_end_date' => 'date',
        'contract_end_date' => 'date',
        'termination_date' => 'date',
        'status' => EmployeeStatus::class,
        'employment_type' => EmploymentType::class,
        'payment_method' => PaymentMethod::class,
        'hourly_rate' => 'decimal:2',
        'monthly_salary' => 'decimal:2',
        'weekly_hours' => 'decimal:2',
        'working_days' => 'array',
        'skills' => 'array',
        'certifications' => 'array',
        'languages' => 'array',
        'annual_leave_days' => 'integer',
        'remaining_leave_days' => 'integer',
        'sick_days_taken' => 'integer',
    ];

    protected $appends = ['full_name', 'is_on_probation', 'years_of_service'];

    /**
     * The relationships that should always be loaded.
     *
     * @var array<string>
     */
    protected $with = ['user'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
            if (empty($model->employee_number)) {
                $model->employee_number = self::generateEmployeeNumber();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(\App\Models\Scheduling\Shift::class, 'user_id', 'user_id');
    }

    public function absences(): HasMany
    {
        return $this->hasMany(\App\Models\Scheduling\Absence::class, 'user_id', 'user_id');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(\App\Models\Scheduling\TimeEntry::class, 'user_id', 'user_id');
    }

    // ==========================================
    // Accessors
    // ==========================================

    public function getFullNameAttribute(): string
    {
        return $this->user?->full_name ?? 'N/A';
    }

    public function getIsOnProbationAttribute(): bool
    {
        if (! $this->probation_end_date) {
            return false;
        }

        return $this->probation_end_date->isFuture();
    }

    public function getYearsOfServiceAttribute(): ?float
    {
        if (! $this->hire_date) {
            return null;
        }
        $endDate = $this->termination_date ?? Carbon::now();

        return round($this->hire_date->diffInYears($endDate), 1);
    }

    public function getAgeAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return $this->birth_date->age;
    }

    public function getRemainingProbationDaysAttribute(): ?int
    {
        if (! $this->probation_end_date || $this->probation_end_date->isPast()) {
            return null;
        }

        return Carbon::now()->diffInDays($this->probation_end_date);
    }

    public function getContractRemainingDaysAttribute(): ?int
    {
        if (! $this->contract_end_date || $this->contract_end_date->isPast()) {
            return null;
        }

        return Carbon::now()->diffInDays($this->contract_end_date);
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', EmployeeStatus::ACTIVE);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', EmployeeStatus::INACTIVE);
    }

    public function scopeOnLeave(Builder $query): Builder
    {
        return $query->where('status', EmployeeStatus::ON_LEAVE);
    }

    public function scopeTerminated(Builder $query): Builder
    {
        return $query->where('status', EmployeeStatus::TERMINATED);
    }

    public function scopeByStatus(Builder $query, EmployeeStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByEmploymentType(Builder $query, EmploymentType $type): Builder
    {
        return $query->where('employment_type', $type);
    }

    public function scopeFullTime(Builder $query): Builder
    {
        return $query->where('employment_type', EmploymentType::FULL_TIME);
    }

    public function scopePartTime(Builder $query): Builder
    {
        return $query->where('employment_type', EmploymentType::PART_TIME);
    }

    public function scopeInDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeHiredAfter(Builder $query, Carbon $date): Builder
    {
        return $query->where('hire_date', '>=', $date);
    }

    public function scopeHiredBefore(Builder $query, Carbon $date): Builder
    {
        return $query->where('hire_date', '<=', $date);
    }

    public function scopeOnProbation(Builder $query): Builder
    {
        return $query->where('probation_end_date', '>', Carbon::now());
    }

    public function scopeContractEndingSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [Carbon::now(), Carbon::now()->addDays($days)]);
    }

    public function scopeWithManager(Builder $query): Builder
    {
        return $query->whereNotNull('manager_id');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('employee_number', 'like', "%{$search}%")
                ->orWhere('position', 'like', "%{$search}%")
                ->orWhere('job_title', 'like', "%{$search}%")
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
        });
    }

    // ==========================================
    // Methods
    // ==========================================

    public static function generateEmployeeNumber(): string
    {
        $prefix = 'EMP';
        $year = date('Y');
        $lastEmployee = self::withTrashed()
            ->where('employee_number', 'like', "{$prefix}{$year}%")
            ->orderBy('employee_number', 'desc')
            ->first();

        if ($lastEmployee) {
            $lastNumber = (int) substr($lastEmployee->employee_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function canWork(): bool
    {
        return $this->status === EmployeeStatus::ACTIVE;
    }

    public function isAvailableOn(Carbon $date): bool
    {
        if (! $this->canWork()) {
            return false;
        }

        if (! $this->working_days) {
            return true;
        }

        $dayOfWeek = strtolower($date->format('l'));

        return in_array($dayOfWeek, $this->working_days);
    }

    public function terminate(string $reason, ?Carbon $date = null): void
    {
        $this->update([
            'status' => EmployeeStatus::TERMINATED,
            'termination_date' => $date ?? Carbon::now(),
            'termination_reason' => $reason,
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => EmployeeStatus::ACTIVE,
            'termination_date' => null,
            'termination_reason' => null,
        ]);
    }

    public function setOnLeave(): void
    {
        $this->update(['status' => EmployeeStatus::ON_LEAVE]);
    }

    public function deductLeaveDays(int $days): bool
    {
        if ($this->remaining_leave_days < $days) {
            return false;
        }

        $this->decrement('remaining_leave_days', $days);

        return true;
    }

    public function addSickDay(): void
    {
        $this->increment('sick_days_taken');
    }

    public function resetAnnualLeave(): void
    {
        $this->update([
            'remaining_leave_days' => $this->annual_leave_days,
            'sick_days_taken' => 0,
        ]);
    }

    public function hasSkill(string $skill): bool
    {
        return $this->skills && in_array(strtolower($skill), array_map('strtolower', $this->skills));
    }

    public function addSkill(string $skill): void
    {
        $skills = $this->skills ?? [];
        if (! $this->hasSkill($skill)) {
            $skills[] = $skill;
            $this->update(['skills' => $skills]);
        }
    }

    public function removeSkill(string $skill): void
    {
        if (! $this->skills) {
            return;
        }

        $skills = array_filter($this->skills, fn ($s) => strtolower($s) !== strtolower($skill));
        $this->update(['skills' => array_values($skills)]);
    }

    public function getWorkedHoursInPeriod(Carbon $start, Carbon $end): float
    {
        return $this->timeEntries()
            ->whereBetween('date', [$start, $end])
            ->sum('actual_hours');
    }

    public function getScheduledHoursInPeriod(Carbon $start, Carbon $end): float
    {
        return $this->shifts()
            ->whereBetween('date', [$start, $end])
            ->get()
            ->sum('duration_hours');
    }
}
