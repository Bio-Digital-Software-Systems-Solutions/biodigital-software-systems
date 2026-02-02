<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property-read bool $can_be_confirmed
 * @property-read bool $can_be_cancelled
 * @property-read string $formatted_appointment_time
 * @property-read string $formatted_appointment_date
 * @property-read \Carbon\Carbon $appointment_end_time
 * @property-read bool $is_upcoming
 * @property-read bool $is_past
 * @property int $id
 * @property string $uuid
 * @property int|null $user_id
 * @property int $pastor_id
 * @property \Illuminate\Support\Carbon $appointment_date
 * @property \Illuminate\Support\Carbon $appointment_time
 * @property int $duration_minutes
 * @property string $status
 * @property string $location_type
 * @property string|null $zoom_link
 * @property string|null $client_name
 * @property string|null $client_email
 * @property string|null $client_phone
 * @property string|null $notes
 * @property string|null $pastor_notes
 * @property \Illuminate\Support\Carbon|null $confirmation_sent_at
 * @property \Illuminate\Support\Carbon|null $reminder_sent_at
 * @property \Illuminate\Support\Carbon|null $notification_email_sent_at
 * @property array|null $notification_channels
 * @property \Illuminate\Support\Carbon|null $sms_reminder_sent_at
 * @property \Illuminate\Support\Carbon|null $whatsapp_reminder_sent_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $pastor
 * @property-read \App\Models\User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare betweenDates($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare cancelled()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare confirmed()
 * @method static \Database\Factories\PastoralCareFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare forPastor($pastorId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare onDate($date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereAppointmentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereAppointmentTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereClientEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereClientName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereClientPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereConfirmationSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare wherePastorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare wherePastorNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereReminderSentAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare whereZoomLink($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PastoralCare withoutTrashed()
 *
 * @mixin \Eloquent
 */
class PastoralCare extends Model
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
        'mlr_agent_id',
        'proposal_submitted_at',
        'proposal_reviewed_at',
        'counter_proposal_sent_at',
        'client_responded_at',
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
    protected $with = ['user', 'pastor', 'transferredFrom', 'transferredTo', 'mlrAgent', 'themes'];

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
            ->useLogName('pastoral-care');
    }

    // Boot method to auto-generate UUID and confirmation tokens
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
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
        });
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
        return $this->belongsTo(PastoralCare::class, 'parent_id');
    }

    /**
     * Get the follow-up appointments for this appointment
     */
    public function followUps()
    {
        return $this->hasMany(PastoralCare::class, 'parent_id');
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
     * Get the MLR agent who handled the proposal
     */
    public function mlrAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mlr_agent_id');
    }

    /**
     * Get the themes for this pastoral care appointment.
     */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(PastoralCareTheme::class, 'pastoral_care_pastoral_care_theme')
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
    public function getFormattedAppointmentTimeAttribute()
    {
        return $this->appointment_time->format('H:i');
    }

    public function getFormattedAppointmentDateAttribute()
    {
        return $this->appointment_date->format('d/m/Y');
    }

    public function getAppointmentEndTimeAttribute()
    {
        return $this->appointment_time->addMinutes($this->duration_minutes);
    }

    public function getIsUpcomingAttribute()
    {
        return $this->appointment_date >= now()->toDateString() &&
            in_array($this->status, ['pending', 'confirmed']);
    }

    public function getIsPastAttribute()
    {
        return $this->appointment_date < now()->toDateString() ||
            ($this->appointment_date == now()->toDateString() && $this->appointment_time < now());
    }

    public function getCanBeCancelledAttribute()
    {
        return in_array($this->status, ['pending', 'confirmed']) &&
            $this->appointment_time > now()->addHours(24);
    }

    public function getCanBeConfirmedAttribute()
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
    public function confirm()
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

    public function cancel($reason = null)
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
        activity('pastoral-care')
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
        activity('pastoral-care')
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
            activity('pastoral-care')
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

    public function complete()
    {
        if ($this->status !== 'confirmed') {
            throw new \Exception('Only confirmed appointments can be marked as completed.');
        }

        $this->update(['status' => 'completed']);

        return $this;
    }

    public function markAsNoShow()
    {
        if ($this->status !== 'confirmed' || ! $this->is_past) {
            throw new \Exception('Only past confirmed appointments can be marked as no-show.');
        }

        $this->update(['status' => 'no_show']);

        return $this;
    }

    public function sendReminderEmail()
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

        activity('pastoral-care')
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
    public function acceptProposal(int $pastorId, int $mlrAgentId): self
    {
        if (! $this->is_proposal) {
            throw new \Exception('Ce rendez-vous n\'est pas une proposition.');
        }

        if ($this->proposal_response_status !== 'pending') {
            throw new \Exception('Cette proposition a déjà été traitée.');
        }

        activity('pastoral-care')
            ->performedOn($this)
            ->withProperties([
                'action' => 'proposal_accepted',
                'pastor_id' => $pastorId,
                'mlr_agent_id' => $mlrAgentId,
            ])
            ->log('Proposition de rendez-vous acceptée');

        $this->update([
            'pastor_id' => $pastorId,
            'mlr_agent_id' => $mlrAgentId,
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
        int $mlrAgentId,
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

        activity('pastoral-care')
            ->performedOn($this)
            ->withProperties([
                'action' => $hasCounterProposal ? 'counter_proposal_sent' : 'proposal_rejected',
                'mlr_agent_id' => $mlrAgentId,
                'rejection_reason' => $rejectionReason,
                'counter_proposed_date' => $counterProposedDate,
                'counter_proposed_time' => $counterProposedTime,
            ])
            ->log($hasCounterProposal ? 'Contre-proposition envoyée' : 'Proposition refusée');

        $updateData = [
            'mlr_agent_id' => $mlrAgentId,
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

        activity('pastoral-care')
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

        activity('pastoral-care')
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
    public static function isTimeSlotAvailable($pastorId, $appointmentTime, $durationMinutes = 60, $excludeId = null)
    {
        // Ensure durationMinutes is an integer
        $durationMinutes = (int) $durationMinutes;

        $appointmentStart = Carbon::parse($appointmentTime);
        $appointmentEnd = $appointmentStart->copy()->addMinutes($durationMinutes);

        // Get existing appointments for the pastor on the same day
        $existingAppointments = static::where('pastor_id', $pastorId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('appointment_time', $appointmentStart->toDateString())
            ->when($excludeId, function ($query, $excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
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

    public static function getAvailableTimeSlots($pastorId, $date, $durationMinutes = 60)
    {
        // Ensure durationMinutes is an integer
        $durationMinutes = (int) $durationMinutes;
        $timeSlots = [];
        $currentDate = Carbon::parse($date);

        // Get pastor's availability for this date
        $availabilities = \App\Models\PastorAvailability::where('pastor_id', $pastorId)
            ->active()
            ->where(function ($query) use ($currentDate) {
                // Check for weekly recurring availability
                $query->where(function ($q) use ($currentDate) {
                    // Use Carbon dayOfWeek format directly (0=Sunday, 1=Monday, etc.)
                    $dayOfWeek = $currentDate->dayOfWeek;
                    $q->where('type', 'weekly')
                        ->where('day_of_week', $dayOfWeek);
                })
                    // Or specific date availability
                    ->orWhere(function ($q) use ($currentDate) {
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
