<?php

namespace App\Models;

use App\Enums\Star\StarCategory;
use App\Enums\Star\StarStatus;
use App\Enums\Star\StarType;
use App\Traits\ClearsCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $user_id
 * @property int|null $department_id
 * @property int|null $nominated_by
 * @property string $star_number
 * @property string|null $title
 * @property string|null $description
 * @property StarStatus $status
 * @property StarType $type
 * @property StarCategory|null $category
 * @property int $points
 * @property int $level
 * @property \Illuminate\Support\Carbon|null $recognition_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property array<array-key, mixed>|null $achievements
 * @property array<array-key, mixed>|null $badges
 * @property array<array-key, mixed>|null $skills
 * @property array<array-key, mixed>|null $areas_of_service
 * @property array<array-key, mixed>|null $available_days
 * @property string|null $available_from
 * @property string|null $available_to
 * @property int $hours_per_week
 * @property int $total_hours_served
 * @property bool $is_contactable
 * @property string|null $preferred_contact_method
 * @property bool $receive_notifications
 * @property string|null $bio
 * @property string|null $avatar
 * @property string|null $cover_image
 * @property bool $is_public_profile
 * @property bool $is_featured
 * @property int $display_order
 * @property string|null $testimonial
 * @property string|null $favorite_verse
 * @property string|null $notes
 * @property string|null $internal_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Department|null $department
 * @property-read int|null $days_until_expiry
 * @property-read string $full_name
 * @property-read bool $is_expired
 * @property-read string $level_title
 * @property-read int $next_level_points
 * @property-read int $progress_to_next_level
 * @property-read float|null $service_duration
 * @property-read \App\Models\User|null $nominator
 * @property-read \App\Models\User $user
 * @method static Builder<static>|Star active()
 * @method static Builder<static>|Star ambassadors()
 * @method static Builder<static>|Star availableOn(string $day)
 * @method static Builder<static>|Star byCategory(\App\Enums\Star\StarCategory $category)
 * @method static Builder<static>|Star byStatus(\App\Enums\Star\StarStatus $status)
 * @method static Builder<static>|Star byType(\App\Enums\Star\StarType $type)
 * @method static Builder<static>|Star contactable()
 * @method static Builder<static>|Star expiringSoon(int $days = 30)
 * @method static \Database\Factories\StarFactory factory($count = null, $state = [])
 * @method static Builder<static>|Star featured()
 * @method static Builder<static>|Star graduated()
 * @method static Builder<static>|Star inDepartment(int $departmentId)
 * @method static Builder<static>|Star inactive()
 * @method static Builder<static>|Star leaders()
 * @method static Builder<static>|Star mentors()
 * @method static Builder<static>|Star minLevel(int $level)
 * @method static Builder<static>|Star newModelQuery()
 * @method static Builder<static>|Star newQuery()
 * @method static Builder<static>|Star notExpired()
 * @method static Builder<static>|Star onBreak()
 * @method static Builder<static>|Star onlyTrashed()
 * @method static Builder<static>|Star publicProfile()
 * @method static Builder<static>|Star query()
 * @method static Builder<static>|Star recognizedAfter(\Carbon\Carbon $date)
 * @method static Builder<static>|Star recognizedBefore(\Carbon\Carbon $date)
 * @method static Builder<static>|Star search(string $search)
 * @method static Builder<static>|Star suspended()
 * @method static Builder<static>|Star volunteers()
 * @method static Builder<static>|Star whereAchievements($value)
 * @method static Builder<static>|Star whereAreasOfService($value)
 * @method static Builder<static>|Star whereAvailableDays($value)
 * @method static Builder<static>|Star whereAvailableFrom($value)
 * @method static Builder<static>|Star whereAvailableTo($value)
 * @method static Builder<static>|Star whereAvatar($value)
 * @method static Builder<static>|Star whereBadges($value)
 * @method static Builder<static>|Star whereBio($value)
 * @method static Builder<static>|Star whereCategory($value)
 * @method static Builder<static>|Star whereCoverImage($value)
 * @method static Builder<static>|Star whereCreatedAt($value)
 * @method static Builder<static>|Star whereDeletedAt($value)
 * @method static Builder<static>|Star whereDepartmentId($value)
 * @method static Builder<static>|Star whereDescription($value)
 * @method static Builder<static>|Star whereDisplayOrder($value)
 * @method static Builder<static>|Star whereExpiryDate($value)
 * @method static Builder<static>|Star whereFavoriteVerse($value)
 * @method static Builder<static>|Star whereHoursPerWeek($value)
 * @method static Builder<static>|Star whereId($value)
 * @method static Builder<static>|Star whereInternalNotes($value)
 * @method static Builder<static>|Star whereIsContactable($value)
 * @method static Builder<static>|Star whereIsFeatured($value)
 * @method static Builder<static>|Star whereIsPublicProfile($value)
 * @method static Builder<static>|Star whereLevel($value)
 * @method static Builder<static>|Star whereNominatedBy($value)
 * @method static Builder<static>|Star whereNotes($value)
 * @method static Builder<static>|Star wherePoints($value)
 * @method static Builder<static>|Star wherePreferredContactMethod($value)
 * @method static Builder<static>|Star whereReceiveNotifications($value)
 * @method static Builder<static>|Star whereRecognitionDate($value)
 * @method static Builder<static>|Star whereSkills($value)
 * @method static Builder<static>|Star whereStarNumber($value)
 * @method static Builder<static>|Star whereStatus($value)
 * @method static Builder<static>|Star whereTestimonial($value)
 * @method static Builder<static>|Star whereTitle($value)
 * @method static Builder<static>|Star whereTotalHoursServed($value)
 * @method static Builder<static>|Star whereType($value)
 * @method static Builder<static>|Star whereUpdatedAt($value)
 * @method static Builder<static>|Star whereUserId($value)
 * @method static Builder<static>|Star whereUuid($value)
 * @method static Builder<static>|Star withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Star withoutTrashed()
 * @mixin \Eloquent
 */
class Star extends Model
{
    use ClearsCache, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'department_id',
        'nominated_by',
        'star_number',
        'title',
        'description',
        'status',
        'type',
        'category',
        'points',
        'level',
        'recognition_date',
        'expiry_date',
        'achievements',
        'badges',
        'skills',
        'areas_of_service',
        'available_days',
        'available_from',
        'available_to',
        'hours_per_week',
        'total_hours_served',
        'is_contactable',
        'preferred_contact_method',
        'receive_notifications',
        'bio',
        'avatar',
        'cover_image',
        'is_public_profile',
        'is_featured',
        'display_order',
        'testimonial',
        'favorite_verse',
        'notes',
        'internal_notes',
    ];

    protected $casts = [
        'status' => StarStatus::class,
        'type' => StarType::class,
        'category' => StarCategory::class,
        'recognition_date' => 'date',
        'expiry_date' => 'date',
        'achievements' => 'array',
        'badges' => 'array',
        'skills' => 'array',
        'areas_of_service' => 'array',
        'available_days' => 'array',
        'points' => 'integer',
        'level' => 'integer',
        'hours_per_week' => 'integer',
        'total_hours_served' => 'integer',
        'display_order' => 'integer',
        'is_contactable' => 'boolean',
        'receive_notifications' => 'boolean',
        'is_public_profile' => 'boolean',
        'is_featured' => 'boolean',
    ];

    protected $appends = ['full_name', 'is_expired', 'days_until_expiry', 'service_duration'];

    /**
     * The relationships that should always be loaded.
     *
     * @var array<string>
     */
    protected $with = ['user'];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
            if (empty($model->star_number)) {
                $model->star_number = self::generateStarNumber();
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

    public function nominator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nominated_by');
    }

    // ==========================================
    // Accessors
    // ==========================================

    public function getFullNameAttribute(): string
    {
        return (string) ($this->user?->full_name ?? 'N/A');
    }

    public function getIsExpiredAttribute(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (! $this->expiry_date || $this->expiry_date->isPast()) {
            return null;
        }

        return (int) Carbon::now()->diffInDays($this->expiry_date);
    }

    public function getServiceDurationAttribute(): ?float
    {
        if (! $this->recognition_date) {
            return null;
        }
        $endDate = $this->expiry_date && $this->expiry_date->isPast()
            ? $this->expiry_date
            : Carbon::now();

        return round($this->recognition_date->diffInMonths($endDate), 1);
    }

    public function getLevelTitleAttribute(): string
    {
        return match ($this->level) {
            1 => 'Bronze',
            2 => 'Argent',
            3 => 'Or',
            4 => 'Platine',
            5 => 'Diamant',
            default => 'Bronze',
        };
    }

    public function getNextLevelPointsAttribute(): int
    {
        return match ($this->level) {
            1 => 100,
            2 => 250,
            3 => 500,
            4 => 1000,
            5 => 2000,
            default => 100,
        };
    }

    public function getProgressToNextLevelAttribute(): int
    {
        $required = $this->next_level_points;
        if ($required === 0) {
            return 100;
        }

        return min(100, (int) (($this->points / $required) * 100));
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', StarStatus::ACTIVE);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', StarStatus::INACTIVE);
    }

    public function scopeOnBreak(Builder $query): Builder
    {
        return $query->where('status', StarStatus::ON_BREAK);
    }

    public function scopeGraduated(Builder $query): Builder
    {
        return $query->where('status', StarStatus::GRADUATED);
    }

    public function scopeSuspended(Builder $query): Builder
    {
        return $query->where('status', StarStatus::SUSPENDED);
    }

    public function scopeByStatus(Builder $query, StarStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByType(Builder $query, StarType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory(Builder $query, StarCategory $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeVolunteers(Builder $query): Builder
    {
        return $query->where('type', StarType::VOLUNTEER);
    }

    public function scopeLeaders(Builder $query): Builder
    {
        return $query->where('type', StarType::LEADER);
    }

    public function scopeMentors(Builder $query): Builder
    {
        return $query->where('type', StarType::MENTOR);
    }

    public function scopeAmbassadors(Builder $query): Builder
    {
        return $query->where('type', StarType::AMBASSADOR);
    }

    public function scopeInDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePublicProfile(Builder $query): Builder
    {
        return $query->where('is_public_profile', true);
    }

    public function scopeMinLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', '>=', $level);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [Carbon::now(), Carbon::now()->addDays($days)]);
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q): void {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>', Carbon::now());
        });
    }

    public function scopeRecognizedAfter(Builder $query, Carbon $date): Builder
    {
        return $query->where('recognition_date', '>=', $date);
    }

    public function scopeRecognizedBefore(Builder $query, Carbon $date): Builder
    {
        return $query->where('recognition_date', '<=', $date);
    }

    public function scopeAvailableOn(Builder $query, string $day): Builder
    {
        return $query->whereJsonContains('available_days', strtolower($day));
    }

    public function scopeContactable(Builder $query): Builder
    {
        return $query->where('is_contactable', true);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search): void {
            $q->where('star_number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhereHas('user', function ($userQuery) use ($search): void {
                    $userQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
        });
    }

    // ==========================================
    // Methods
    // ==========================================

    public static function generateStarNumber(): string
    {
        $prefix = 'STR';
        $year = date('Y');
        $lastStar = self::withTrashed()
            ->where('star_number', 'like', "{$prefix}{$year}%")
            ->orderBy('star_number', 'desc')
            ->first();

        if ($lastStar) {
            $lastNumber = (int) substr((string) $lastStar->star_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    public function canServe(): bool
    {
        return $this->status === StarStatus::ACTIVE && ! $this->is_expired;
    }

    public function isAvailableOn(Carbon $date): bool
    {
        if (! $this->canServe()) {
            return false;
        }

        if (! $this->available_days) {
            return true;
        }

        $dayOfWeek = strtolower($date->format('l'));

        return in_array($dayOfWeek, $this->available_days);
    }

    public function activate(): void
    {
        $this->update([
            'status' => StarStatus::ACTIVE,
        ]);
    }

    public function deactivate(): void
    {
        $this->update([
            'status' => StarStatus::INACTIVE,
        ]);
    }

    public function setOnBreak(): void
    {
        $this->update([
            'status' => StarStatus::ON_BREAK,
        ]);
    }

    public function graduate(): void
    {
        $this->update([
            'status' => StarStatus::GRADUATED,
            'expiry_date' => Carbon::now(),
        ]);
    }

    public function suspend(): void
    {
        $this->update([
            'status' => StarStatus::SUSPENDED,
        ]);
    }

    public function addPoints(int $points): void
    {
        $this->increment('points', $points);
        $this->checkLevelUp();
    }

    public function removePoints(int $points): void
    {
        $newPoints = max(0, $this->points - $points);
        $this->update(['points' => $newPoints]);
    }

    protected function checkLevelUp(): void
    {
        $this->refresh();
        $newLevel = match (true) {
            $this->points >= 2000 => 5,
            $this->points >= 1000 => 4,
            $this->points >= 500 => 3,
            $this->points >= 250 => 2,
            default => 1,
        };

        if ($newLevel > $this->level) {
            $this->update(['level' => $newLevel]);
        }
    }

    public function addHoursServed(int $hours): void
    {
        $this->increment('total_hours_served', $hours);
    }

    public function addAchievement(string $achievement): void
    {
        $achievements = $this->achievements ?? [];
        if (! in_array($achievement, $achievements)) {
            $achievements[] = $achievement;
            $this->update(['achievements' => $achievements]);
        }
    }

    public function removeAchievement(string $achievement): void
    {
        if (! $this->achievements) {
            return;
        }

        $achievements = array_filter($this->achievements, fn ($a): bool => $a !== $achievement);
        $this->update(['achievements' => array_values($achievements)]);
    }

    public function hasAchievement(string $achievement): bool
    {
        return $this->achievements && in_array($achievement, $this->achievements);
    }

    public function addBadge(string $badge): void
    {
        $badges = $this->badges ?? [];
        if (! in_array($badge, $badges)) {
            $badges[] = $badge;
            $this->update(['badges' => $badges]);
        }
    }

    public function removeBadge(string $badge): void
    {
        if (! $this->badges) {
            return;
        }

        $badges = array_filter($this->badges, fn ($b): bool => $b !== $badge);
        $this->update(['badges' => array_values($badges)]);
    }

    public function hasBadge(string $badge): bool
    {
        return $this->badges && in_array($badge, $this->badges);
    }

    public function hasSkill(string $skill): bool
    {
        return $this->skills && in_array(strtolower($skill), array_map(strtolower(...), $this->skills));
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

        $skills = array_filter($this->skills, fn ($s): bool => strtolower((string) $s) !== strtolower($skill));
        $this->update(['skills' => array_values($skills)]);
    }

    public function setFeatured(bool $featured = true): void
    {
        $this->update(['is_featured' => $featured]);
    }

    public function renewForMonths(int $months): void
    {
        $this->update([
            'expiry_date' => Carbon::now()->addMonths($months),
            'status' => StarStatus::ACTIVE,
        ]);
    }
}
