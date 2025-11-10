<?php

namespace App\Models;

use App\Traits\ClearsCache;
use App\Traits\HasUuid;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property string|null $avatar
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Article> $articles
 * @property-read int|null $articles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $assignedTasks
 * @property-read int|null $assigned_tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BookRental> $bookRentals
 * @property-read int|null $book_rentals_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatMessage> $chatMessages
 * @property-read int|null $chat_messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ChatRoom> $chatRooms
 * @property-read int|null $chat_rooms_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $createdEvents
 * @property-read int|null $created_events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Department> $departments
 * @property-read int|null $departments_count
 * @property-read string $full_name
 * @property-read string $name
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $participatingEvents
 * @property-read int|null $participating_events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Program> $programs
 * @property-read int|null $programs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $receivedMessages
 * @property-read int|null $received_messages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Message> $sentMessages
 * @property-read int|null $sent_messages_count
 * @property-read \App\Models\Student|null $student
 * @property-read \App\Models\Teacher|null $teacher
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Training> $trainings
 * @property-read int|null $trainings_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @mixin \Eloquent
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, HasUuid, Notifiable, TwoFactorAuthenticatable, LogsActivity, ClearsCache;

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
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'birth_date',
        'avatar',
        'is_active',
        'is_blocked',
        'status_reason',
        'status_changed_at',
        'status_changed_by',
        'last_login_at',
        'last_login_ip',
        'last_login_user_agent',
        'email_notifications',
        'sms_notifications',
        'push_notifications',
        'newsletter',
        'event_reminders',
        'training_updates',
        'message_notifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'full_name',
        'name',
    ];

    /**
     * Cache patterns to invalidate when this model changes.
     * Users affect many other cached data sets.
     */
    protected $relatedCacheKeys = [
        'events.*',        // Events cache (participations, creations)
        'articles.*',      // Articles cache (author changes)
        'books.*',         // Books cache (rental data)
        'trainings.*',     // Training cache (enrollments)
        'chat.*',          // Chat cache (messages, rooms)
        'dashboard.*',     // Dashboard cache (user stats)
        'permissions.*',   // Permission-based caches
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'last_login_at' => 'datetime',
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'newsletter' => 'boolean',
            'event_reminders' => 'boolean',
            'training_updates' => 'boolean',
            'message_notifications' => 'boolean',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Get the user's name (alias for full_name).
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * User's departments relationship.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class);
    }

    /**
     * User's groups relationship.
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user')
            ->using(\App\Models\Pivots\GroupUser::class)
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    /**
     * User's created articles.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * User's created events.
     */
    public function createdEvents(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * User's created events (alias for createdEvents).
     */
    public function events(): HasMany
    {
        return $this->createdEvents();
    }

    /**
     * Events the user participates in.
     */
    public function participatingEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_user');
    }

    /**
     * Events the user participates in (alias for participatingEvents).
     */
    public function participatedEvents(): BelongsToMany
    {
        return $this->participatingEvents();
    }

    /**
     * User's created programs.
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * Tasks assigned to the user.
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /**
     * User's book rentals.
     */
    public function bookRentals(): HasMany
    {
        return $this->hasMany(BookRental::class);
    }

    /**
     * Messages sent by the user.
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Messages received by the user.
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Chat rooms the user participates in.
     */
    public function chatRooms(): BelongsToMany
    {
        return $this->belongsToMany(ChatRoom::class, 'chat_room_user', 'user_id', 'chat_room_id');
    }

    /**
     * Chat messages sent by the user.
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    /**
     * User's teacher profile.
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    /**
     * User's student profile.
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /**
     * Trainings the user is enrolled in (as a student).
     */
    public function trainings(): BelongsToMany
    {
        return $this->belongsToMany(Training::class, 'training_enrollments')
            ->withPivot([
                'status',
                'progress',
                'grade',
                'attendance_rate',
                'motivation',
                'payment_method',
                'enrolled_at',
                'completed_at',
                'training_class_id',
            ])
            ->withTimestamps();
    }

    /**
     * Pastor availability relation
     */
    public function availability(): HasMany
    {
        return $this->hasMany(PastorAvailability::class, 'pastor_id');
    }

    /**
     * Get active availability for pastor
     */
    public function activeAvailability(): HasMany
    {
        return $this->availability()->active();
    }
}
