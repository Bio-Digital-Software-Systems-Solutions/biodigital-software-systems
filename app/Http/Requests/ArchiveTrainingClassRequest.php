<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArchiveTrainingClassRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'access_duration_months' => 'nullable|integer|min:1|max:24',
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'access_duration_months.integer' => 'La durée d\'accès doit être un nombre entier.',
            'access_duration_months.min' => 'La durée d\'accès doit être d\'au moins 1 mois.',
            'access_duration_months.max' => 'La durée d\'accès ne peut pas dépasser 24 mois.',
        ];
    }
}
