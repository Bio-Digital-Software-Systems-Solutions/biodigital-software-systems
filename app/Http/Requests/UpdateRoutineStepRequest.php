<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoutineStepRequest extends FormRequest
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
            'instructions' => ['nullable', 'string', 'max:10000'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:10080'],
            'is_required' => ['boolean'],
            'requires_validation' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de l\'étape est requis.',
            'name.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'duration_minutes.min' => 'La durée doit être d\'au moins 1 minute.',
        ];
    }
}
