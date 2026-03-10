<?php

namespace App\Http\Requests;

use App\Models\PastoralCare;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class PastoralCareUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user has permission to update pastoral care appointments
        if (!auth()->check() || !auth()->user()->can('pastoral_care.edit')) {
            return false;
        }

        // Get the pastoral care appointment being updated
        $pastoralCare = $this->route('pastoralCare');

        // Only pastors can update their own appointments
        if ($pastoralCare && auth()->user()->hasRole('pastor')) {
            return $pastoralCare->pastor_id === auth()->id();
        }

        // Allow admins to update any appointment
        return auth()->user()->can('pastoral_care.manage_all');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $pastoralCare = $this->route('pastoralCare');

        return [
            'client_name' => 'sometimes|required|string|max:255',
            'client_email' => 'sometimes|required|email|max:255',
            'client_phone' => 'sometimes|nullable|string|max:20',
            'appointment_date' => [
                'sometimes',
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail): void {
                    // Don't allow booking too far in advance (6 months)
                    if (Carbon::parse($value) > now()->addMonths(6)) {
                        $fail('La date de rendez-vous ne peut pas être plus de 6 mois dans le futur.');
                    }
                }
            ],
            'appointment_time' => [
                'sometimes',
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail): void {
                    $time = Carbon::createFromFormat('H:i', $value);
                    // Check business hours (9 AM to 5 PM)
                    if ($time->hour < 9 || $time->hour >= 17) {
                        $fail('L\'heure de rendez-vous doit être entre 09:00 et 17:00.');
                    }
                }
            ],
            'duration_minutes' => [
                'sometimes',
                'required',
                'integer',
                'min:30',
                'max:180',
                function ($attribute, $value, $fail): void {
                    // Duration must be in 30-minute increments
                    if ($value % 30 !== 0) {
                        $fail('La durée doit être un multiple de 30 minutes.');
                    }
                }
            ],
            'location_type' => 'sometimes|required|in:in_person,zoom,hybrid',
            'zoom_link' => 'sometimes|nullable|url|required_if:location_type,zoom,hybrid',
            'notes' => 'sometimes|nullable|string|max:1000',
            'status' => [
                'sometimes',
                'in:pending,confirmed,completed,cancelled,no_show',
                function ($attribute, $value, $fail) use ($pastoralCare): void {
                    if ($pastoralCare) {
                        // Validate status transitions
                        $currentStatus = $pastoralCare->status;
                        $validTransitions = $this->getValidStatusTransitions($currentStatus);

                        if (!in_array($value, $validTransitions)) {
                            $fail("Impossible de changer le statut de '$currentStatus' à '$value'.");
                        }
                    }
                }
            ],
            'cancellation_reason' => 'sometimes|nullable|string|max:500|required_if:status,cancelled',
        ];
    }

    /**
     * Get valid status transitions for the current status.
     */
    protected function getValidStatusTransitions(string $currentStatus): array
    {
        $transitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['completed', 'cancelled', 'no_show'],
            'completed' => [], // Cannot change from completed
            'cancelled' => [], // Cannot change from cancelled
            'no_show' => [], // Cannot change from no_show
        ];

        return $transitions[$currentStatus] ?? [];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'client_name.required' => 'Le nom du client est requis.',
            'client_name.max' => 'Le nom du client ne doit pas dépasser 255 caractères.',
            'client_email.required' => 'L\'adresse email est requise.',
            'client_email.email' => 'L\'adresse email doit être valide.',
            'client_phone.max' => 'Le numéro de téléphone ne doit pas dépasser 20 caractères.',
            'appointment_date.required' => 'La date de rendez-vous est requise.',
            'appointment_date.date' => 'La date de rendez-vous doit être une date valide.',
            'appointment_date.after_or_equal' => 'La date de rendez-vous ne peut pas être dans le passé.',
            'appointment_time.required' => 'L\'heure de rendez-vous est requise.',
            'appointment_time.date_format' => 'L\'heure doit être au format HH:MM.',
            'duration_minutes.required' => 'La durée est requise.',
            'duration_minutes.integer' => 'La durée doit être un nombre entier.',
            'duration_minutes.min' => 'La durée minimum est de 30 minutes.',
            'duration_minutes.max' => 'La durée maximum est de 180 minutes.',
            'location_type.required' => 'Le type de lieu est requis.',
            'location_type.in' => 'Le type de lieu doit être: présentiel, zoom ou hybride.',
            'zoom_link.url' => 'Le lien Zoom doit être une URL valide.',
            'zoom_link.required_if' => 'Le lien Zoom est requis pour les rendez-vous en ligne ou hybrides.',
            'notes.max' => 'Les notes ne doivent pas dépasser 1000 caractères.',
            'status.in' => 'Le statut n\'est pas valide.',
            'cancellation_reason.required_if' => 'La raison d\'annulation est requise lors de l\'annulation.',
            'cancellation_reason.max' => 'La raison d\'annulation ne doit pas dépasser 500 caractères.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (!$validator->errors()->any()) {
                $this->validateTimeSlotAvailability($validator);
                $this->validateAppointmentIsEditable($validator);
            }
        });
    }

    /**
     * Validate that the time slot is available (if time/date is being updated).
     */
    protected function validateTimeSlotAvailability($validator): void
    {
        $pastoralCare = $this->route('pastoralCare');

        // Only validate if we're updating time-related fields
        if (!$this->has('appointment_date') && !$this->has('appointment_time') && !$this->has('duration_minutes')) {
            return;
        }

        $pastorId = $pastoralCare->pastor_id;
        $appointmentDate = $this->input('appointment_date', $pastoralCare->appointment_date->format('Y-m-d'));
        $appointmentTime = $this->input('appointment_time', $pastoralCare->appointment_time->format('H:i'));
        $duration = $this->input('duration_minutes', $pastoralCare->duration_minutes);

        // Combine date and time
        $appointmentDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $appointmentDate . ' ' . $appointmentTime
        );

        // Check if the time slot is available (excluding current appointment)
        if (!PastoralCare::isTimeSlotAvailable($pastorId, $appointmentDateTime, $duration, $pastoralCare->id)) {
            $validator->errors()->add(
                'appointment_time',
                'Ce créneau horaire n\'est pas disponible.'
            );
        }
    }

    /**
     * Validate that the appointment can be edited.
     */
    protected function validateAppointmentIsEditable($validator): void
    {
        $pastoralCare = $this->route('pastoralCare');

        if (!$pastoralCare) {
            return;
        }

        // Cannot edit completed, cancelled, or no-show appointments (except status changes by admin)
        $restrictedStatuses = ['completed', 'cancelled', 'no_show'];

        // Allow only status updates by admins
        if (in_array($pastoralCare->status, $restrictedStatuses) && (!auth()->user()->can('pastoral_care.manage_all') || $this->except(['status', 'cancellation_reason']))) {
            $validator->errors()->add(
                'status',
                'Les rendez-vous terminés, annulés ou avec absence ne peuvent plus être modifiés.'
            );
        }

        // Cannot edit appointments in the past (except marking as no-show or completed)
        if ($pastoralCare->appointment_time < now() && $this->except(['status', 'notes'])) {
            $validator->errors()->add(
                'appointment_time',
                'Les rendez-vous passés ne peuvent plus être modifiés.'
            );
        }
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'client_name' => 'nom du client',
            'client_email' => 'email du client',
            'client_phone' => 'téléphone du client',
            'appointment_date' => 'date de rendez-vous',
            'appointment_time' => 'heure de rendez-vous',
            'duration_minutes' => 'durée',
            'location_type' => 'type de lieu',
            'zoom_link' => 'lien Zoom',
            'notes' => 'notes',
            'status' => 'statut',
            'cancellation_reason' => 'raison d\'annulation',
        ];
    }
}
