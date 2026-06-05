<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property int|null $parent_id
 * @property int|null $pastor_id
 * @property int|null $transferred_from_id
 * @property int|null $transferred_to_id
 * @property \Illuminate\Support\Carbon|null $transferred_at
 * @property string|null $transfer_reason
 * @property \Illuminate\Support\Carbon $appointment_date
 * @property \Illuminate\Support\Carbon $appointment_time
 * @property int $duration_minutes
 * @property string|null $status
 * @property bool $is_proposal
 * @property string|null $proposal_reason
 * @property \Illuminate\Support\Carbon|null $counter_proposed_date
 * @property string|null $counter_proposed_time
 * @property string|null $counter_proposal_message
 * @property string|null $proposal_response_status
 * @property string|null $proposal_rejection_reason
 * @property string|null $proposal_token
 * @property int|null $care_service_agent_id
 * @property \Illuminate\Support\Carbon|null $proposal_submitted_at
 * @property \Illuminate\Support\Carbon|null $proposal_reviewed_at
 * @property \Illuminate\Support\Carbon|null $counter_proposal_sent_at
 * @property \Illuminate\Support\Carbon|null $client_responded_at
 * @property string $location_type
 * @property string|null $zoom_link
 * @property string|null $client_name
 * @property string|null $client_email
 * @property string|null $client_phone
 * @property string|null $notes
 * @property string|null $theme
 * @property array<array-key, mixed>|null $pastor_notes
 * @property \Illuminate\Support\Carbon|null $confirmation_sent_at
 * @property \Illuminate\Support\Carbon|null $client_confirmed_at
 * @property \Illuminate\Support\Carbon|null $pastor_confirmed_at
 * @property string|null $client_confirmation_token
 * @property string|null $pastor_confirmation_token
 * @property \Illuminate\Support\Carbon|null $reminder_sent_at
 * @property array<array-key, mixed>|null $notification_channels JSON array of notification channels: email, sms, whatsapp
 * @property \Illuminate\Support\Carbon|null $sms_reminder_sent_at
 * @property \Illuminate\Support\Carbon|null $whatsapp_reminder_sent_at
 * @property \Illuminate\Support\Carbon|null $notification_email_sent_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $assigned_agent_type
 * @property int|null $assigned_agent_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read BaseModel|\Eloquent|null $assignedAgent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CareService> $followUps
 * @property-read int|null $follow_ups_count
 * @property-read mixed $appointment_end_time
 * @property-read mixed $can_be_cancelled
 * @property-read mixed $can_be_confirmed
 * @property-read array $confirmation_status
 * @property-read mixed $formatted_appointment_date
 * @property-read mixed $formatted_appointment_time
 * @property-read bool $is_fully_confirmed
 * @property-read mixed $is_past
 * @property-read mixed $is_upcoming
 * @property-read string|null $proposal_status_label
 * @property-read string|null $theme_label
 * @property-read \App\Models\User|null $careServiceAgent
 * @property-read CareService|null $parent
 * @property-read \App\Models\User|null $pastor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CareServiceTheme> $themes
 * @property-read int|null $themes_count
 * @property-read \App\Models\User|null $transferredFrom
 * @property-read \App\Models\User|null $transferredTo
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService betweenDates($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService byTheme($theme)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService cancelled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService confirmed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService counterProposed()
 * @method static \Database\Factories\CareServiceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService forAssignedAgent($agentId, ?string $agentType = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService forPastor($pastorId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService isFollowUp()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService isProposal()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService notTransferred()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService onDate($date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService pendingProposals()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService proposed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService transferred()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereAppointmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereAppointmentTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereAssignedAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereAssignedAgentType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereClientConfirmationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereClientConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereClientEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereClientName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereClientPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereClientRespondedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereConfirmationSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereCounterProposalMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereCounterProposalSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereCounterProposedDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereCounterProposedTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereIsProposal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereCareServiceAgentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereNotificationChannels($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereNotificationEmailSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService wherePastorConfirmationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService wherePastorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService wherePastorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService wherePastorNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereProposalReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereProposalRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereProposalResponseStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereProposalReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereProposalSubmittedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereProposalToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereReminderSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereSmsReminderSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereTheme($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereTransferReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereTransferredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereTransferredFromId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereTransferredToId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereWhatsappReminderSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService whereZoomLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CareService withoutTrashed()
 *
 * @mixin \Eloquent
 */
class CareService extends BaseModel
{
    use ClearsCache, HasFactory, LogsActivity, SoftDeletes;

    public const THEMES = [
        'spiritual_guidance' => 'Accompagnement spirituel',
        'grief_counseling' => 'Accompagnement de deuil',
        'marriage_counseling' => 'Conseil conjugal',
        'family_issues' => 'Questions familiales',
        'faith_questions' => 'Questions de foi',
        'crisis_support' => 'Soutien en situation de crise',
        'prayer_request' => 'Demande de prière',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'uuid',
        'user_id',
        'pastor_id',
        'parent_id',
        'appointment_date',
        'appointment_time',
        'duration_minutes',
        'status',
        'location_type',
        'zoom_link',
        'client_name',
        'client_email',
        'client_phone',
        'notes',
        'theme',
        'pastor_notes',
        'confirmation_sent_at',
        'client_confirmed_at',
        'pastor_confirmed_at',
        'client_confirmation_token',
        'pastor_confirmation_token',
        'reminder_sent_at',
        'notification_email_sent_at',
        'notification_channels',
        'sms_reminder_sent_at',
        'whatsapp_reminder_sent_at',
        'cancelled_at',
        'cancellation_reason',
        'transferred_from_id',
        'transferred_to_id',
        'transferred_at',
        'transfer_reason',
        // Proposal system fields
        'is_proposal',
        'proposal_reason',
        'counter_proposed_date',
        'counter_proposed_time',
        'counter_proposal_message',
        'proposal_response_status',
        'proposal_rejection_reason',
        'proposal_token',
        'care_service_agent_id',
        'proposal_submitted_at',
        'proposal_reviewed_at',
        'counter_proposal_sent_at',
        'client_responded_at',
        // Polymorphic assigned agent
        'assigned_agent_id',
        'assigned_agent_type',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime',
        'duration_minutes' => 'integer',
        'confirmation_sent_at' => 'datetime',
        'client_confirmed_at' => 'datetime',
        'pastor_confirmed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'notification_email_sent_at' => 'datetime',
        'notification_channels' => 'array',
        'sms_reminder_sent_at' => 'datetime',
        'whatsapp_reminder_sent_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'transferred_at' => 'datetime',
        'pastor_notes' => 'array',
        // Proposal system casts
        'is_proposal' => 'boolean',
        'counter_proposed_date' => 'date',
        'proposal_submitted_at' => 'datetime',
        'proposal_reviewed_at' => 'datetime',
        'counter_proposal_sent_at' => 'datetime',
        'client_responded_at' => 'datetime',
    ];

    protected $dates = [
        'appointment_date',
        'appointment_time',
        'confirmation_sent_at',
        'client_confirmed_at',
        'pastor_confirmed_at',
        'reminder_sent_at',
        'notification_email_sent_at',
        'sms_reminder_sent_at',
        'whatsapp_reminder_sent_at',
        'cancelled_at',
        'transferred_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array<string>
     */
    protected $with = ['user', 'pastor', 'transferredFrom', 'transferredTo', 'careServiceAgent', 'themes', 'assignedAgent'];

    protected $appends = [
        'can_be_confirmed',
        'can_be_cancelled',
        'is_fully_confirmed',
        'confirmation_status',
        'theme_label',
        'proposal_status_label',
    ];

    /**
     * Configure activity logging
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'client_confirmed_at',
                'pastor_confirmed_at',
                'cancelled_at',
                'cancellation_reason',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('care-service');
    }

    // Boot method to auto-generate UUID and confirmation tokens
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
            // Generate unique confirmation tokens
            if (empty($model->client_confirmation_token)) {
                $model->client_confirmation_token = Str::random(64);
            }
            if (empty($model->pastor_confirmation_token)) {
                $model->pastor_confirmation_token = Str::random(64);
            }
            // Generate proposal token for proposals
            if ($model->is_proposal && empty($model->proposal_token)) {
                $model->proposal_token = Str::random(64);
                $model->proposal_submitted_at = now();
                $model->proposal_response_status = 'pending';
            }
            // Sync assigned_agent with pastor_id for backward compatibility
            if ($model->pastor_id && empty($model->assigned_agent_id)) {
                $model->assigned_agent_id = $model->pastor_id;
                $model->assigned_agent_type = User::class;
            }
        });

        static::updating(function ($model): void {
            // Keep assigned_agent in sync when pastor_id changes
            if ($model->isDirty('pastor_id') && $model->pastor_id) {
                $model->assigned_agent_id = $model->pastor_id;
                $model->assigned_agent_type = User::class;
            }
        });
    }

    /**
     * Assign an agent to this appointment (polymorphic).
     *
     * @param  BaseModel  $agent  The agent model (User with pastor/care-service-agent role, or other type)
     */
    public function assignAgent(BaseModel $agent): self
    {
        $this->update([
            'assigned_agent_id' => $agent->id,
            'assigned_agent_type' => $agent::class,
            // Also update pastor_id for backward compatibility if agent is a User
            'pastor_id' => $agent instanceof User ? $agent->id : $this->pastor_id,
        ]);

        return $this;
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pastor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pastor_id');
    }

    /**
     * Get the parent appointment (if this is a follow-up)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(CareService::class, 'parent_id');
    }

    /**
     * Get the follow-up appointments for this appointment
     */
    public function followUps()
    {
        return $this->hasMany(CareService::class, 'parent_id');
    }

    /**
     * Get the user who transferred this appointment
     */
    public function transferredFrom(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_from_id');
    }

    /**
     * Get the user to whom this appointment was transferred
     */
    public function transferredTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_to_id');
    }

    /**
     * Get the care service agent who handled the proposal
     */
    public function careServiceAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'care_service_agent_id');
    }

    /**
     * Get the assigned agent (polymorphic relationship).
     * Can be a User with role pastor, care-service-agent, or any other assignable type.
     */
    public function assignedAgent(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the themes for this care service appointment.
     */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(CareServiceTheme::class, 'care_service_care_service_theme')
            ->withTimestamps();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed']);
    }

    public function scopeForPastor($query, $pastorId)
    {
        return $query->where('pastor_id', $pastorId);
    }

    /**
     * Scope to filter appointments by assigned agent (polymorphic).
     * This is the preferred way to filter appointments by agent.
     */
    public function scopeForAssignedAgent($query, $agentId, ?string $agentType = null)
    {
        $query->where('assigned_agent_id', $agentId);

        if ($agentType) {
            $query->where('assigned_agent_type', $agentType);
        }

        return $query;
    }

    public function scopeOnDate($query, $date)
    {
        return $query->where('appointment_date', $date);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('appointment_date', [$startDate, $endDate]);
    }

    public function scopeTransferred($query)
    {
        return $query->whereNotNull('transferred_at');
    }

    public function scopeIsFollowUp($query)
    {
        return $query->whereNotNull('parent_id');
    }

    public function scopeByTheme($query, $theme)
    {
        return $query->where('theme', $theme);
    }

    public function scopeNotTransferred($query)
    {
        return $query->whereNull('transferred_at');
    }

    public function scopeProposed($query)
    {
        return $query->where('status', 'proposed');
    }

    public function scopeIsProposal($query)
    {
        return $query->where('is_proposal', true);
    }

    public function scopePendingProposals($query)
    {
        return $query->where('is_proposal', true)
            ->where('proposal_response_status', 'pending');
    }

    public function scopeCounterProposed($query)
    {
        return $query->where('is_proposal', true)
            ->where('proposal_response_status', 'counter_proposed');
    }

    // Accessors & Mutators
    public function getFormattedAppointmentTimeAttribute(): string
    {
        return $this->appointment_time->format('H:i');
    }

    public function getFormattedAppointmentDateAttribute(): string
    {
        return $this->appointment_date->format('d/m/Y');
    }

    public function getAppointmentEndTimeAttribute()
    {
        return $this->appointment_time->addMinutes($this->duration_minutes);
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->appointment_date >= now()->toDateString() &&
            in_array($this->status, ['pending', 'confirmed']);
    }

    public function getIsPastAttribute(): bool
    {
        if ($this->appointment_date < now()->toDateString()) {
            return true;
        }

        return $this->appointment_date == now()->toDateString() && $this->appointment_time < now();
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) &&
            $this->appointment_time > now()->addHours(24);
    }

    public function getCanBeConfirmedAttribute(): bool
    {
        return $this->status === 'pending' && $this->appointment_time > now();
    }

    /**
     * Check if both parties have confirmed the appointment
     */
    public function getIsFullyConfirmedAttribute(): bool
    {
        return $this->client_confirmed_at !== null && $this->pastor_confirmed_at !== null;
    }

    /**
     * Get a human-readable confirmation status
     */
    public function getConfirmationStatusAttribute(): array
    {
        return [
            'client_confirmed' => $this->client_confirmed_at !== null,
            'pastor_confirmed' => $this->pastor_confirmed_at !== null,
            'client_confirmed_at' => $this->client_confirmed_at?->toISOString(),
            'pastor_confirmed_at' => $this->pastor_confirmed_at?->toISOString(),
            'is_fully_confirmed' => $this->is_fully_confirmed,
        ];
    }

    // Business Logic Methods

    /**
     * Legacy confirm method - now confirms both parties at once
     * Kept for backward compatibility
     */
    public function confirm(): static
    {
        if (! $this->can_be_confirmed) {
            // Déterminer la raison spécifique de l'impossibilité de confirmer
            if ($this->status !== 'pending') {
                throw new \Exception('Seuls les rendez-vous en attente peuvent être confirmés (statut actuel: '.$this->status.').');
            }

            if ($this->appointment_time <= now()) {
                throw new \Exception('Ce rendez-vous est déjà passé et ne peut plus être confirmé.');
            }

            throw new \Exception('Ce rendez-vous ne peut pas être confirmé.');
        }

        $this->update([
            'status' => 'confirmed',
            'confirmation_sent_at' => now(),
        ]);

        return $this;
    }

    public function cancel($reason = null): static
    {
        if (! $this->can_be_cancelled) {
            // Déterminer la raison spécifique de l'impossibilité d'annuler
            if (! in_array($this->status, ['pending', 'confirmed'])) {
                throw new \Exception('Ce rendez-vous ne peut plus être annulé (statut: '.$this->status.').');
            }

            if ($this->appointment_time <= now()->addHours(24)) {
                throw new \Exception('Délai d\'annulation dépassé (24h).');
            }

            throw new \Exception('Ce rendez-vous ne peut pas être annulé.');
        }

        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $this;
    }

    /**
     * Confirm appointment by client using their confirmation token
     *
     * @throws \Exception
     */
    public function confirmByClient(string $token): self
    {
        // Validate token
        if ($this->client_confirmation_token !== $token) {
            throw new \Exception('Token de confirmation invalide.');
        }

        // Check if already confirmed
        if ($this->client_confirmed_at !== null) {
            throw new \Exception('Vous avez déjà confirmé ce rendez-vous.');
        }

        // Check if appointment is in the past
        if ($this->appointment_time <= now()) {
            throw new \Exception('Ce rendez-vous est déjà passé et ne peut plus être confirmé.');
        }

        // Check if appointment is cancelled
        if ($this->status === 'cancelled') {
            throw new \Exception('Ce rendez-vous a été annulé.');
        }

        // Log the confirmation with activity
        activity('care-service')
            ->performedOn($this)
            ->withProperties([
                'confirmed_by' => 'client',
                'client_name' => $this->client_name,
                'client_email' => $this->client_email,
            ])
            ->log('Client a confirmé le rendez-vous');

        $this->update([
            'client_confirmed_at' => now(),
        ]);

        // Check if both parties have confirmed - if so, update status
        $this->checkAndUpdateConfirmationStatus();

        return $this;
    }

    /**
     * Confirm appointment by pastor using their confirmation token
     *
     * @throws \Exception
     */
    public function confirmByPastor(string $token): self
    {
        // Validate token
        if ($this->pastor_confirmation_token !== $token) {
            throw new \Exception('Token de confirmation invalide.');
        }

        // Check if already confirmed
        if ($this->pastor_confirmed_at !== null) {
            throw new \Exception('Vous avez déjà confirmé ce rendez-vous.');
        }

        // Check if appointment is in the past
        if ($this->appointment_time <= now()) {
            throw new \Exception('Ce rendez-vous est déjà passé et ne peut plus être confirmé.');
        }

        // Check if appointment is cancelled
        if ($this->status === 'cancelled') {
            throw new \Exception('Ce rendez-vous a été annulé.');
        }

        // Log the confirmation with activity
        activity('care-service')
            ->performedOn($this)
            ->causedBy($this->pastor)
            ->withProperties([
                'confirmed_by' => 'pastor',
                'pastor_name' => $this->pastor->first_name.' '.$this->pastor->last_name,
            ])
            ->log('Pasteur a confirmé le rendez-vous');

        $this->update([
            'pastor_confirmed_at' => now(),
        ]);

        // Check if both parties have confirmed - if so, update status
        $this->checkAndUpdateConfirmationStatus();

        return $this;
    }

    /**
     * Check if both parties have confirmed and update status accordingly
     */
    protected function checkAndUpdateConfirmationStatus(): void
    {
        // Refresh the model to get latest data
        $this->refresh();

        if ($this->client_confirmed_at !== null && $this->pastor_confirmed_at !== null) {
            // Both parties have confirmed - update status to confirmed
            activity('care-service')
                ->performedOn($this)
                ->withProperties([
                    'client_confirmed_at' => $this->client_confirmed_at->toISOString(),
                    'pastor_confirmed_at' => $this->pastor_confirmed_at->toISOString(),
                ])
                ->log('Rendez-vous confirmé par les deux parties');

            $this->update([
                'status' => 'confirmed',
                'confirmation_sent_at' => now(),
            ]);
        }
    }

    /**
     * Find an appointment by client confirmation token
     */
    public static function findByClientToken(string $token): ?self
    {
        return static::where('client_confirmation_token', $token)->first();
    }

    /**
     * Find an appointment by pastor confirmation token
     */
    public static function findByPastorToken(string $token): ?self
    {
        return static::where('pastor_confirmation_token', $token)->first();
    }

    /**
     * Regenerate confirmation tokens (useful if tokens are compromised)
     */
    public function regenerateConfirmationTokens(): self
    {
        $this->update([
            'client_confirmation_token' => Str::random(64),
            'pastor_confirmation_token' => Str::random(64),
        ]);

        return $this;
    }

    public function complete(): static
    {
        if ($this->status !== 'confirmed') {
            throw new \Exception('Only confirmed appointments can be marked as completed.');
        }

        $this->update(['status' => 'completed']);

        return $this;
    }

    public function markAsNoShow(): static
    {
        if ($this->status !== 'confirmed' || ! $this->is_past) {
            throw new \Exception('Only past confirmed appointments can be marked as no-show.');
        }

        $this->update(['status' => 'no_show']);

        return $this;
    }

    public function sendReminderEmail(): bool
    {
        if ($this->status !== 'confirmed') {
            return false;
        }

        // This will be implemented when we create the Mail classes
        // Mail::to($this->client_email)->send(new AppointmentReminderMail($this));

        $this->update(['reminder_sent_at' => now()]);

        return true;
    }

    /**
     * Transfer the appointment to another pastor/agent
     *
     * @throws \Exception
     */
    public function transferTo(int $newPastorId, ?string $reason = null): self
    {
        if (! in_array($this->status, ['pending', 'confirmed'])) {
            throw new \Exception('Seuls les rendez-vous en attente ou confirmés peuvent être transférés.');
        }

        if ($this->pastor_id === $newPastorId) {
            throw new \Exception('Le rendez-vous est déjà assigné à ce pasteur/agent.');
        }

        $oldPastorId = $this->pastor_id;

        activity('care-service')
            ->performedOn($this)
            ->withProperties([
                'transferred_from' => $oldPastorId,
                'transferred_to' => $newPastorId,
                'reason' => $reason,
            ])
            ->log('Rendez-vous transféré');

        $this->update([
            'transferred_from_id' => $oldPastorId,
            'transferred_to_id' => $newPastorId,
            'transferred_at' => now(),
            'transfer_reason' => $reason,
            'pastor_id' => $newPastorId,
            // Reset confirmations as new pastor needs to confirm
            'pastor_confirmed_at' => null,
            'pastor_confirmation_token' => Str::random(64),
        ]);

        // Reset status to pending if it was confirmed
        if ($this->status === 'confirmed') {
            $this->update(['status' => 'pending']);
        }

        return $this;
    }

    /**
     * Check if this appointment was transferred
     */
    public function wasTransferred(): bool
    {
        return $this->transferred_at !== null;
    }

    /**
     * Get the theme label
     */
    public function getThemeLabelAttribute(): ?string
    {
        return $this->theme ? (self::THEMES[$this->theme] ?? $this->theme) : null;
    }

    /**
     * Get a human-readable proposal status label
     */
    public function getProposalStatusLabelAttribute(): ?string
    {
        if (! $this->is_proposal) {
            return null;
        }

        return match ($this->proposal_response_status) {
            'pending' => 'En attente de réponse',
            'accepted' => 'Proposition acceptée',
            'rejected' => 'Proposition refusée',
            'counter_proposed' => 'Contre-proposition envoyée',
            default => null,
        };
    }

    /**
     * Check if this appointment has a pending counter-proposal
     */
    public function hasCounterProposal(): bool
    {
        return $this->is_proposal
            && $this->proposal_response_status === 'counter_proposed'
            && $this->counter_proposed_date !== null;
    }

    /**
     * Accept a proposal and convert it to a regular pending appointment
     *
     * @throws \Exception
     */
    public function acceptProposal(int $pastorId, int $careServiceAgentId): self
    {
        if (! $this->is_proposal) {
            throw new \Exception('Ce rendez-vous n\'est pas une proposition.');
        }

        if ($this->proposal_response_status !== 'pending') {
            throw new \Exception('Cette proposition a déjà été traitée.');
        }

        activity('care-service')
            ->performedOn($this)
            ->withProperties([
                'action' => 'proposal_accepted',
                'pastor_id' => $pastorId,
                'care_service_agent_id' => $careServiceAgentId,
            ])
            ->log('Proposition de rendez-vous acceptée');

        $this->update([
            'pastor_id' => $pastorId,
            'care_service_agent_id' => $careServiceAgentId,
            'proposal_response_status' => 'accepted',
            'proposal_reviewed_at' => now(),
            'status' => 'pending',
        ]);

        return $this;
    }

    /**
     * Reject a proposal with optional counter-proposal
     *
     * @throws \Exception
     */
    public function rejectProposal(
        int $careServiceAgentId,
        string $rejectionReason,
        ?string $counterProposedDate = null,
        ?string $counterProposedTime = null,
        ?string $counterProposalMessage = null
    ): self {
        if (! $this->is_proposal) {
            throw new \Exception('Ce rendez-vous n\'est pas une proposition.');
        }

        if ($this->proposal_response_status !== 'pending') {
            throw new \Exception('Cette proposition a déjà été traitée.');
        }

        $hasCounterProposal = $counterProposedDate !== null && $counterProposedTime !== null;

        activity('care-service')
            ->performedOn($this)
            ->withProperties([
                'action' => $hasCounterProposal ? 'counter_proposal_sent' : 'proposal_rejected',
                'care_service_agent_id' => $careServiceAgentId,
                'rejection_reason' => $rejectionReason,
                'counter_proposed_date' => $counterProposedDate,
                'counter_proposed_time' => $counterProposedTime,
            ])
            ->log($hasCounterProposal ? 'Contre-proposition envoyée' : 'Proposition refusée');

        $updateData = [
            'care_service_agent_id' => $careServiceAgentId,
            'proposal_rejection_reason' => $rejectionReason,
            'proposal_reviewed_at' => now(),
        ];

        if ($hasCounterProposal) {
            $updateData['proposal_response_status'] = 'counter_proposed';
            $updateData['counter_proposed_date'] = $counterProposedDate;
            $updateData['counter_proposed_time'] = $counterProposedTime;
            $updateData['counter_proposal_message'] = $counterProposalMessage;
            $updateData['counter_proposal_sent_at'] = now();
        } else {
            $updateData['proposal_response_status'] = 'rejected';
            $updateData['status'] = 'cancelled';
            $updateData['cancelled_at'] = now();
            $updateData['cancellation_reason'] = 'Proposition refusée: '.$rejectionReason;
        }

        $this->update($updateData);

        return $this;
    }

    /**
     * Client accepts the counter-proposal
     *
     * @throws \Exception
     */
    public function acceptCounterProposal(string $token): self
    {
        if ($this->proposal_token !== $token) {
            throw new \Exception('Token de proposition invalide.');
        }

        if (! $this->hasCounterProposal()) {
            throw new \Exception('Aucune contre-proposition à accepter.');
        }

        activity('care-service')
            ->performedOn($this)
            ->withProperties([
                'action' => 'counter_proposal_accepted',
                'original_date' => $this->appointment_date?->toDateString(),
                'original_time' => $this->appointment_time?->format('H:i'),
                'accepted_date' => $this->counter_proposed_date?->toDateString(),
                'accepted_time' => $this->counter_proposed_time,
            ])
            ->log('Contre-proposition acceptée par le client');

        // Update appointment to the counter-proposed date/time
        $counterProposedDateTime = Carbon::parse(
            $this->counter_proposed_date->format('Y-m-d').' '.$this->counter_proposed_time
        );

        $this->update([
            'appointment_date' => $this->counter_proposed_date,
            'appointment_time' => $counterProposedDateTime,
            'proposal_response_status' => 'accepted',
            'client_responded_at' => now(),
            'status' => 'pending',
            // Clear counter-proposal fields
            'counter_proposed_date' => null,
            'counter_proposed_time' => null,
            'counter_proposal_message' => null,
        ]);

        return $this;
    }

    /**
     * Client rejects the counter-proposal
     *
     * @throws \Exception
     */
    public function rejectCounterProposal(string $token, ?string $reason = null): self
    {
        if ($this->proposal_token !== $token) {
            throw new \Exception('Token de proposition invalide.');
        }

        if (! $this->hasCounterProposal()) {
            throw new \Exception('Aucune contre-proposition à refuser.');
        }

        activity('care-service')
            ->performedOn($this)
            ->withProperties([
                'action' => 'counter_proposal_rejected',
                'reason' => $reason,
            ])
            ->log('Contre-proposition refusée par le client');

        $this->update([
            'proposal_response_status' => 'rejected',
            'client_responded_at' => now(),
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?? 'Contre-proposition refusée par le client',
        ]);

        return $this;
    }

    /**
     * Find a proposal by its token
     */
    public static function findByProposalToken(string $token): ?self
    {
        return static::where('proposal_token', $token)->first();
    }

    /**
     * Add a new note with timestamp to pastor_notes
     */
    public function addPastorNote(string $content): self
    {
        $notes = $this->pastor_notes ?? [];
        $notes[] = [
            'content' => $content,
            'created_at' => now()->toISOString(),
        ];

        $this->update(['pastor_notes' => $notes]);

        return $this;
    }

    // Static methods for availability checking
    public static function isTimeSlotAvailable($pastorId, \DateTimeInterface|\Carbon\WeekDay|\Carbon\Month|string|int|float|null $appointmentTime, $durationMinutes = 60, $excludeId = null): bool
    {
        // Ensure durationMinutes is an integer
        $durationMinutes = (int) $durationMinutes;

        $appointmentStart = Carbon::parse($appointmentTime);
        $appointmentEnd = $appointmentStart->copy()->addMinutes($durationMinutes);

        // Get existing appointments for the pastor on the same day
        $existingAppointments = static::where('pastor_id', $pastorId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('appointment_time', $appointmentStart->toDateString())
            ->when($excludeId, fn ($query, $excludeId) => $query->where('id', '!=', $excludeId))
            ->select(['appointment_time', 'duration_minutes'])
            ->get();

        // Check for conflicts with existing appointments
        foreach ($existingAppointments as $existing) {
            $existingStart = Carbon::parse($existing->appointment_time);
            $existingEnd = $existingStart->copy()->addMinutes($existing->duration_minutes);

            // Check for any overlap between the time slots
            if ($appointmentStart->lt($existingEnd) && $appointmentEnd->gt($existingStart)) {
                return false; // Conflict found
            }
        }

        return true; // No conflicts found
    }

    /**
     * @return mixed[]
     */
    public static function getAvailableTimeSlots($pastorId, \DateTimeInterface|\Carbon\WeekDay|\Carbon\Month|string|int|float|null $date, $durationMinutes = 60): array
    {
        // Ensure durationMinutes is an integer
        $durationMinutes = (int) $durationMinutes;
        $timeSlots = [];
        $currentDate = Carbon::parse($date);

        // Get pastor's availability for this date
        $availabilities = \App\Models\CareServiceAvailability::where('pastor_id', $pastorId)
            ->active()
            ->where(function ($query) use ($currentDate): void {
                // Check for weekly recurring availability
                $query->where(function ($q) use ($currentDate): void {
                    // Use Carbon dayOfWeek format directly (0=Sunday, 1=Monday, etc.)
                    $dayOfWeek = $currentDate->dayOfWeek;
                    $q->where('type', 'weekly')
                        ->where('day_of_week', $dayOfWeek);
                })
                    // Or specific date availability
                    ->orWhere(function ($q) use ($currentDate): void {
                        $q->where('type', 'specific_date')
                            ->where('specific_date', $currentDate->toDateString());
                    });
            })
            ->get();

        // If no availability defined, return empty array (no slots available)
        if ($availabilities->isEmpty()) {
            return [];
        }

        // Generate time slots for each availability period
        foreach ($availabilities as $availability) {
            $slots = $availability->getTimeSlotsForDate($currentDate);

            foreach ($slots as $slot) {
                $timeSlot = $currentDate->copy()->setTimeFromTimeString($slot);

                // Skip if in the past
                if ($timeSlot <= now()) {
                    continue;
                }

                // Check if this specific time slot is available (not booked)
                if (static::isTimeSlotAvailable($pastorId, $timeSlot, $durationMinutes)) {
                    $timeSlots[] = $slot;
                }
            }
        }

        // Remove duplicates and sort
        $timeSlots = array_unique($timeSlots);
        sort($timeSlots);

        return $timeSlots;
    }

    // Route key name for UUID-based routing
    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
