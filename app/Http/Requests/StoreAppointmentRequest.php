<?php

namespace App\Http\Requests;

use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create appointments');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'start_datetime' => [
                'required',
                'date',
                'after:now',
                function ($attribute, $value, $fail) {
                    $startDateTime = Carbon::parse($value);

                    // Business hours validation (3 AM to midnight)
                    if ($startDateTime->hour == 1 || $startDateTime->hour == 2) {
                        $fail('Le rendez-vous doit être entre 3h et 00h.');
                    }

                    // Minimum advance notice
                    if ($startDateTime->isBefore(now()->addHour())) {
                        $fail('Le rendez-vous doit être pris au minimum 1 heure à l\'avance.');
                    }
                },
            ],
            'end_datetime' => [
                'required',
                'date',
                'after:start_datetime',
                function ($attribute, $value, $fail) {
                    if ($this->has('start_datetime')) {
                        $startDateTime = Carbon::parse($this->input('start_datetime'));
                        $endDateTime = Carbon::parse($value);

                        // Minimum duration
                        $durationInMinutes = $startDateTime->diffInMinutes($endDateTime);
                        if ($durationInMinutes < 30) {
                            $fail('La durée minimale d\'un rendez-vous est de 30 minutes.');
                        }

                        // Maximum duration
                        $durationInHours = $startDateTime->diffInHours($endDateTime);
                        if ($durationInHours > 4) {
                            $fail('La durée maximale d\'un rendez-vous est de 4 heures.');
                        }

                        // Check for conflicts
                        if (Appointment::hasConflict($startDateTime, $endDateTime)) {
                            $fail('Ce créneau horaire n\'est pas disponible.');
                        }
                    }
                },
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_mode' => ['required', 'in:in_person,online,hybrid'],
            'meeting_link' => ['nullable', 'required_if:meeting_mode,online,hybrid', 'url', 'max:500'],
            'meeting_platform' => ['nullable', 'required_if:meeting_mode,online,hybrid', 'in:zoom,google_meet,ms_teams,other'],
            'type' => ['required', 'in:individual,group,consultation,meeting'],
            'visibility' => ['required', 'in:private,public'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
            'appointmentable_type' => ['nullable', 'string'],
            'appointmentable_id' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
            'notification_channels' => ['nullable', 'array'],
            'notification_channels.*' => ['in:email,sms,whatsapp'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre du rendez-vous est obligatoire.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.max' => 'La description ne peut pas dépasser 1000 caractères.',
            'start_datetime.required' => 'L\'heure de début est obligatoire.',
            'start_datetime.date' => 'L\'heure de début doit être une date valide.',
            'start_datetime.after' => 'Le rendez-vous doit être dans le futur.',
            'end_datetime.required' => 'L\'heure de fin est obligatoire.',
            'end_datetime.date' => 'L\'heure de fin doit être une date valide.',
            'end_datetime.after' => 'L\'heure de fin doit être après l\'heure de début.',
            'location.max' => 'L\'emplacement ne peut pas dépasser 255 caractères.',
            'meeting_mode.required' => 'Le mode de réunion est obligatoire.',
            'meeting_mode.in' => 'Le mode de réunion sélectionné est invalide.',
            'meeting_link.required_if' => 'Le lien de la réunion est obligatoire pour les réunions en ligne ou hybrides.',
            'meeting_link.url' => 'Le lien de la réunion doit être une URL valide.',
            'meeting_link.max' => 'Le lien de la réunion ne peut pas dépasser 500 caractères.',
            'meeting_platform.required_if' => 'La plateforme est obligatoire pour les réunions en ligne ou hybrides.',
            'meeting_platform.in' => 'La plateforme sélectionnée est invalide.',
            'type.required' => 'Le type de rendez-vous est obligatoire.',
            'type.in' => 'Le type de rendez-vous sélectionné est invalide.',
            'visibility.required' => 'La visibilité du rendez-vous est obligatoire.',
            'visibility.in' => 'La visibilité sélectionnée est invalide.',
            'participant_ids.array' => 'Les participants doivent être un tableau.',
            'participant_ids.*.exists' => 'Un ou plusieurs participants sélectionnés n\'existent pas.',
            'notification_channels.array' => 'Les canaux de notification doivent être un tableau.',
            'notification_channels.*.in' => 'Canal de notification invalide. Valeurs acceptées : email, sms, whatsapp.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert date strings to Carbon instances for validation
        if ($this->has('start_datetime') && is_string($this->input('start_datetime'))) {
            $this->merge([
                'start_datetime' => Carbon::parse($this->input('start_datetime'))->format('Y-m-d H:i:s'),
            ]);
        }

        if ($this->has('end_datetime') && is_string($this->input('end_datetime'))) {
            $this->merge([
                'end_datetime' => Carbon::parse($this->input('end_datetime'))->format('Y-m-d H:i:s'),
            ]);
        }
    }
}
