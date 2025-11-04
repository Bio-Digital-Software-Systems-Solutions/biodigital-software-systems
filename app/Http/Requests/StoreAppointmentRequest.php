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

                    // Business hours validation (7 AM to 11 PM)
                    if ($startDateTime->hour < 7 || $startDateTime->hour >= 23) {
                        $fail('Le rendez-vous doit être entre 7h et 23h.');
                    }

                    // No weekends
                    if ($startDateTime->isWeekend()) {
                        $fail('Les rendez-vous ne sont possibles que du lundi au vendredi.');
                    }

                    // Minimum advance notice
                    if ($startDateTime->isBefore(now()->addHour())) {
                        $fail('Le rendez-vous doit être pris au minimum 1 heure à l\'avance.');
                    }
                }
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
                }
            ],
            'location' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:individual,group,consultation,meeting'],
            'visibility' => ['required', 'in:private,public'],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['exists:users,id'],
            'appointmentable_type' => ['nullable', 'string'],
            'appointmentable_id' => ['nullable', 'integer'],
            'metadata' => ['nullable', 'array'],
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
            'type.required' => 'Le type de rendez-vous est obligatoire.',
            'type.in' => 'Le type de rendez-vous sélectionné est invalide.',
            'visibility.required' => 'La visibilité du rendez-vous est obligatoire.',
            'visibility.in' => 'La visibilité sélectionnée est invalide.',
            'participant_ids.array' => 'Les participants doivent être un tableau.',
            'participant_ids.*.exists' => 'Un ou plusieurs participants sélectionnés n\'existent pas.',
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