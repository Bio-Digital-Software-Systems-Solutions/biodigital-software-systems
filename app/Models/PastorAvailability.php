<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class PastorAvailability extends Model
{
    use HasFactory, ClearsCache;

    protected $table = 'pastor_availability';

    protected $fillable = [
        'pastor_id',
        'type',
        'day_of_week',
        'specific_date',
        'start_time',
        'end_time',
        'slot_duration',
        'is_active',
        'consultation_mode',
        'meeting_link',
        'location',
        'room',
        'notes',
        'selected_slots',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'specific_date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string',
        'slot_duration' => 'integer',
        'is_active' => 'boolean',
        'selected_slots' => 'array',
    ];

    protected $dates = [
        'specific_date',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function pastor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pastor_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWeekly($query)
    {
        return $query->where('type', 'weekly');
    }

    public function scopeSpecificDate($query)
    {
        return $query->where('type', 'specific_date');
    }

    public function scopeForPastor($query, $pastorId)
    {
        return $query->where('pastor_id', $pastorId);
    }

    public function scopeOnDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    public function scopeOnDate($query, $date)
    {
        return $query->where('specific_date', $date);
    }

    // Methods to get time slots for a specific date
    public function getTimeSlotsForDate($date): array
    {
        // If specific slots are selected, return only those (sorted chronologically)
        if ($this->selected_slots && is_array($this->selected_slots) && count($this->selected_slots) > 0) {
            $slots = $this->selected_slots;

            // Sort slots chronologically
            usort($slots, function ($a, $b) {
                // Convert HH:MM to minutes for comparison
                $timeA = sscanf($a, "%d:%d");
                $timeB = sscanf($b, "%d:%d");
                $minutesA = ($timeA[0] * 60) + ($timeA[1] ?? 0);
                $minutesB = ($timeB[0] * 60) + ($timeB[1] ?? 0);
                return $minutesA - $minutesB;
            });

            return $slots;
        }

        // Otherwise, generate slots based on start_time, end_time, and slot_duration
        $slots = [];
        // Parse time strings correctly - handle both H:i and H:i:s formats
        try {
            // Try H:i:s format first
            if (strlen($this->start_time) > 5) {
                $startTime = Carbon::createFromFormat('H:i:s', $this->start_time);
                $endTime = Carbon::createFromFormat('H:i:s', $this->end_time);
            } else {
                // Fallback to H:i format
                $startTime = Carbon::createFromFormat('H:i', $this->start_time);
                $endTime = Carbon::createFromFormat('H:i', $this->end_time);
            }
        } catch (\Exception $e) {
            // If parsing fails, try to extract just the H:i part
            $startTimeStr = substr($this->start_time, 0, 5);
            $endTimeStr = substr($this->end_time, 0, 5);
            $startTime = Carbon::createFromFormat('H:i', $startTimeStr);
            $endTime = Carbon::createFromFormat('H:i', $endTimeStr);
        }

        $duration = $this->slot_duration;

        $current = $startTime->copy();
        while ($current->copy()->addMinutes($duration) <= $endTime) {
            $slots[] = $current->format('H:i');
            $current->addMinutes($duration);
        }

        return $slots;
    }

    // Check if this availability applies to a specific date
    public function appliesTo($date): bool
    {
        $carbonDate = Carbon::parse($date);

        if ($this->type === 'specific_date') {
            return $this->specific_date && $this->specific_date->isSameDay($carbonDate);
        }

        if ($this->type === 'weekly') {
            return $this->day_of_week === $carbonDate->dayOfWeek;
        }

        return false;
    }

    // Get day name for weekly availability
    public function getDayNameAttribute(): string
    {
        if ($this->type !== 'weekly' || $this->day_of_week === null) {
            return '';
        }

        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        return $days[$this->day_of_week] ?? '';
    }

    // Get formatted time range
    public function getTimeRangeAttribute(): string
    {
        return Carbon::parse($this->start_time)->format('H:i') . ' - ' .
               Carbon::parse($this->end_time)->format('H:i');
    }

    // Get consultation mode label
    public function getConsultationModeLabelAttribute(): string
    {
        $modes = [
            'in_person' => 'En présentiel',
            'online' => 'En ligne',
            'hybrid' => 'Hybride',
        ];

        return $modes[$this->consultation_mode] ?? $this->consultation_mode;
    }

    // Get formatted location
    public function getFormattedLocationAttribute(): string
    {
        $parts = array_filter([$this->location, $this->room]);
        return implode(' - ', $parts);
    }

}
