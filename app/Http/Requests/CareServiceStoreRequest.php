<?php

namespace App\Http\Requests;

use App\Models\CareService;
use App\Models\CareServiceTheme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class CareServiceStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow if user has care service create permission
        // OR if this is a public booking (no authenticated user)
        if (auth()->guest()) {
            return true; // Public booking
        }

        return auth()->user()->can('care_service.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pastor_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail): void {
                    if ($value) {
                        $user = User::find($value);
                        if (! $user || ! $user->hasRole('pastor')) {
                            $fail('Le pasteur sélectionné n\'est pas valide.');
                        }
                    }
                },
            ],
            'client_name' => auth()->check() ? 'nullable|string|max:255' : 'required|string|max:255',
            'client_email' => auth()->check() ? 'nullable|email|max:255' : 'required|email|max:255',
            'client_phone' => 'nullable|string|max:20',
            'appointment_date' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail): void {
                    try {
                        // Don't allow booking too far in advance (6 months)
                        if (Carbon::parse($value) > now()->addMonths(6)) {
                            $fail('La date de rendez-vous ne peut pas être plus de 6 mois dans le futur.');
                        }
                    } catch (\Exception) {
                        // Skip validation if date parsing fails
                        // (Laravel's built-in date validation will catch invalid formats)
                        return;
                    }
                },
            ],
            'appointment_time' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail): void {
                    try {
                        $time = Carbon::createFromFormat('H:i', $value);
                        // Check business hours (9 AM to 5 PM)
                        if ($time->hour < 9 || $time->hour >= 17) {
                            $fail('L\'heure de rendez-vous doit être entre 09:00 et 17:00.');
                        }
                    } catch (\Exception) {
                        // Skip validation if time parsing fails
                        // (Laravel's built-in date_format validation will catch invalid formats)
                        return;
                    }
                },
            ],
            'duration_minutes' => [
                'required',
                'integer',
                'min:30',
                'max:180',
                function ($attribute, $value, $fail): void {
                    // Duration must be in 30-minute increments
                    if (is_numeric($value) && ($value % 30 !== 0)) {
                        $fail('La durée doit être un multiple de 30 minutes.');
                    }
                },
            ],
            'location_type' => 'required|in:in_person,zoom,hybrid',
            'zoom_link' => 'nullable|url|required_if:location_type,zoom,hybrid',
            'notes' => 'required|string|max:1000',
            'theme_ids' => 'required|array|min:1',
            'theme_ids.*' => [
                'required',
                'integer',
                'exists:care_service_themes,id',
                function ($attribute, $value, $fail): void {
                    $theme = CareServiceTheme::find($value);
                    if ($theme && ! $theme->is_active) {
                        $fail('Le thème sélectionné n\'est plus disponible.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'pastor_id.required' => 'Veuillez sélectionner un pasteur.',
            'pastor_id.exists' => 'Le pasteur sélectionné n\'existe pas.',
            'client_name.required' => 'Le nom du client est requis pour les réservations publiques.',
            'client_name.max' => 'Le nom du client ne doit pas dépasser 255 caractères.',
            'client_email.required' => 'L\'adresse email est requise pour les réservations publiques.',
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
            'notes.required' => 'Veuillez décrire le sujet ou vos préoccupations.',
            'notes.max' => 'Les notes ne doivent pas dépasser 1000 caractères.',
            'theme_ids.required' => 'Veuillez sélectionner au moins un thème.',
            'theme_ids.array' => 'Le format des thèmes est invalide.',
            'theme_ids.min' => 'Veuillez sélectionner au moins un thème.',
            'theme_ids.*.required' => 'Chaque thème doit être sélectionné.',
            'theme_ids.*.integer' => 'Le thème sélectionné est invalide.',
            'theme_ids.*.exists' => 'Le thème sélectionné n\'existe pas.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If this is from an authenticated pastor, set the pastor_id automatically
        if (auth()->check() && auth()->user()->hasRole('pastor')) {
            $this->merge([
                'pastor_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $validator->errors()->any()) {
                $this->validateTimeSlotAvailability($validator);
            }
        });
    }

    /**
     * Validate that the time slot is available.
     */
    protected function validateTimeSlotAvailability($validator): void
    {
        $pastorId = $this->input('pastor_id');
        $appointmentDate = $this->input('appointment_date');
        $appointmentTime = $this->input('appointment_time');
        $duration = $this->input('duration_minutes');

        if ($pastorId && $appointmentDate && $appointmentTime) {
            try {
                // Combine date and time
                $appointmentDateTime = Carbon::createFromFormat(
                    'Y-m-d H:i',
                    $appointmentDate.' '.$appointmentTime
                );

                // Check if the time slot is available
                if (! CareService::isTimeSlotAvailable($pastorId, $appointmentDateTime, $duration)) {
                    $validator->errors()->add(
                        'appointment_time',
                        'Ce créneau horaire n\'est pas disponible.'
                    );
                }
            } catch (\Exception) {
                // Skip validation if date/time parsing fails
                // (Laravel's built-in date validation will catch invalid formats)
                return;
            }
        }
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'pastor_id' => 'pasteur',
            'client_name' => 'nom du client',
            'client_email' => 'email du client',
            'client_phone' => 'téléphone du client',
            'appointment_date' => 'date de rendez-vous',
            'appointment_time' => 'heure de rendez-vous',
            'duration_minutes' => 'durée',
            'location_type' => 'type de lieu',
            'zoom_link' => 'lien Zoom',
            'notes' => 'notes',
            'theme_ids' => 'thèmes',
        ];
    }
}
