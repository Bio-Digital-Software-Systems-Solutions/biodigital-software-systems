<?php

namespace App\Http\Requests;

use App\Enums\RoutineFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoutineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage departments');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'frequency' => ['required', Rule::enum(RoutineFrequency::class)],
            'responsible_id' => ['nullable', 'exists:users,id'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la routine est requis.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'frequency.required' => 'La fréquence est requise.',
            'responsible_id.exists' => 'Le responsable sélectionné est invalide.',
            'estimated_duration_minutes.min' => 'La durée estimée doit être d\'au moins 1 minute.',
        ];
    }
}
