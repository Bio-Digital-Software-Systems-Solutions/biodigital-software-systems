<?php

namespace App\Models\Event;

use App\Models\Event;
use App\Models\User;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $event_id
 * @property int|null $session_id
 * @property int|null $registration_id
 * @property int|null $user_id
 * @property int|null $overall_rating
 * @property int|null $content_rating
 * @property int|null $speaker_rating
 * @property int|null $venue_rating
 * @property int|null $organization_rating
 * @property int|null $nps_score
 * @property string|null $positive_feedback
 * @property string|null $improvement_suggestions
 * @property string|null $additional_comments
 * @property array<array-key, mixed>|null $custom_answers
 * @property bool|null $would_recommend
 * @property bool|null $would_attend_again
 * @property bool $is_anonymous
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Event $event
 * @property-read float|null $average_rating
 * @property-read string|null $nps_category
 * @property-read string|null $responder_name
 * @property-read \App\Models\Event\EventRegistration|null $registration
 * @property-read \App\Models\Event\EventSession|null $session
 * @property-read User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback anonymous()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback forEvent(int $eventId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback forSession(int $sessionId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback public()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereAdditionalComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereContentRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereCustomAnswers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereImprovementSuggestions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereIsAnonymous($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereNpsScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereOrganizationRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereOverallRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback wherePositiveFeedback($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereRegistrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereSpeakerRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereVenueRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereWouldAttendAgain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback whereWouldRecommend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback withNps()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventFeedback withOverallRating()
 * @mixin \Eloquent
 */
class EventFeedback extends Model
{
    use HasFactory, HasUuid, LogsActivity;

    protected $table = 'event_feedback';

    protected $fillable = [
        'event_id',
        'session_id',
        'registration_id',
        'user_id',
        'overall_rating',
        'content_rating',
        'speaker_rating',
        'venue_rating',
        'organization_rating',
        'nps_score',
        'positive_feedback',
        'improvement_suggestions',
        'additional_comments',
        'custom_answers',
        'would_recommend',
        'would_attend_again',
        'is_anonymous',
    ];

    protected function casts(): array
    {
        return [
            'overall_rating' => 'integer',
            'content_rating' => 'integer',
            'speaker_rating' => 'integer',
            'venue_rating' => 'integer',
            'organization_rating' => 'integer',
            'nps_score' => 'integer',
            'custom_answers' => 'array',
            'would_recommend' => 'boolean',
            'would_attend_again' => 'boolean',
            'is_anonymous' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(EventSession::class, 'session_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    public function scopeForEvent($query, int $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeWithOverallRating($query)
    {
        return $query->whereNotNull('overall_rating');
    }

    public function scopeWithNps($query)
    {
        return $query->whereNotNull('nps_score');
    }

    public function scopePublic($query)
    {
        return $query->where('is_anonymous', false);
    }

    public function scopeAnonymous($query)
    {
        return $query->where('is_anonymous', true);
    }

    // Accessors

    public function getAverageRatingAttribute(): ?float
    {
        $ratings = array_filter([
            $this->overall_rating,
            $this->content_rating,
            $this->speaker_rating,
            $this->venue_rating,
            $this->organization_rating,
        ]);

        if ($ratings === []) {
            return null;
        }

        return round(array_sum($ratings) / count($ratings), 1);
    }

    public function getNpsCategoryAttribute(): ?string
    {
        if ($this->nps_score === null) {
            return null;
        }

        if ($this->nps_score >= 9) {
            return 'promoter';
        }

        if ($this->nps_score >= 7) {
            return 'passive';
        }

        return 'detractor';
    }

    public function getResponderNameAttribute(): ?string
    {
        if ($this->is_anonymous) {
            return 'Anonyme';
        }

        if ($this->registration) {
            return (string) $this->registration->full_name;
        }

        if ($this->user) {
            return (string) $this->user->name;
        }

        return null;
    }

    // Static methods for analytics

    public static function getEventStats(int $eventId): array
    {
        $feedback = static::forEvent($eventId)->get();

        if ($feedback->isEmpty()) {
            return [
                'count' => 0,
                'avg_overall' => null,
                'avg_content' => null,
                'avg_speaker' => null,
                'avg_venue' => null,
                'avg_organization' => null,
                'nps' => null,
                'recommend_rate' => null,
                'return_rate' => null,
            ];
        }

        $withNps = $feedback->whereNotNull('nps_score');
        $nps = null;

        if ($withNps->count() > 0) {
            $promoters = $withNps->where('nps_score', '>=', 9)->count();
            $detractors = $withNps->where('nps_score', '<', 7)->count();
            $nps = round((($promoters - $detractors) / $withNps->count()) * 100);
        }

        return [
            'count' => $feedback->count(),
            'avg_overall' => round($feedback->whereNotNull('overall_rating')->avg('overall_rating'), 1),
            'avg_content' => round($feedback->whereNotNull('content_rating')->avg('content_rating'), 1),
            'avg_speaker' => round($feedback->whereNotNull('speaker_rating')->avg('speaker_rating'), 1),
            'avg_venue' => round($feedback->whereNotNull('venue_rating')->avg('venue_rating'), 1),
            'avg_organization' => round($feedback->whereNotNull('organization_rating')->avg('organization_rating'), 1),
            'nps' => $nps,
            'recommend_rate' => $feedback->whereNotNull('would_recommend')->count() > 0
                ? round(($feedback->where('would_recommend', true)->count() / $feedback->whereNotNull('would_recommend')->count()) * 100)
                : null,
            'return_rate' => $feedback->whereNotNull('would_attend_again')->count() > 0
                ? round(($feedback->where('would_attend_again', true)->count() / $feedback->whereNotNull('would_attend_again')->count()) * 100)
                : null,
        ];
    }

    public static function getRatingDistribution(int $eventId): array
    {
        $distribution = [];

        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = static::forEvent($eventId)
                ->where('overall_rating', $i)
                ->count();
        }

        return $distribution;
    }

    public static function getNpsDistribution(int $eventId): array
    {
        $feedback = static::forEvent($eventId)->withNps()->get();

        return [
            'promoters' => $feedback->where('nps_score', '>=', 9)->count(),
            'passives' => $feedback->whereBetween('nps_score', [7, 8])->count(),
            'detractors' => $feedback->where('nps_score', '<', 7)->count(),
        ];
    }
}
