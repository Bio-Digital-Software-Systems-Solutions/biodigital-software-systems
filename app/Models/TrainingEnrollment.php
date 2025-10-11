<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property int $id
 * @property int $user_id
 * @property int $training_id
 * @property string $status
 * @property string $progress
 * @property string|null $grade
 * @property string $attendance_rate
 * @property string|null $motivation
 * @property string|null $payment_method
 * @property string|null $enrolled_at
 * @property string|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $training_class_id
 * @property string|null $rejection_reason
 * @method static \Database\Factories\TrainingEnrollmentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereAttendanceRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereEnrolledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereGrade($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereMotivation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereTrainingClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereTrainingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TrainingEnrollment whereUserId($value)
 * @mixin \Eloquent
 */
class TrainingEnrollment extends Model
{
    use HasFactory, LogsActivity;

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
}
