<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create tasks');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10'],
            'due_date' => ['nullable', 'date', 'after:today'],
            'priority' => ['required', 'in:low,medium,high'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'status_id' => ['required', 'exists:statuses,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est requis.',
            'title.max' => 'Le titre ne peut pas dépasser 255 caractères.',
            'description.required' => 'La description est requise.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'due_date.date' => 'La date d\'échéance doit être une date valide.',
            'due_date.after' => 'La date d\'échéance doit être postérieure à aujourd\'hui.',
            'priority.required' => 'La priorité est requise.',
            'priority.in' => 'La priorité doit être basse, moyenne ou haute.',
            'estimated_hours.numeric' => 'Les heures estimées doivent être un nombre.',
            'estimated_hours.min' => 'Les heures estimées ne peuvent pas être négatives.',
            'status_id.required' => 'Le statut est requis.',
            'status_id.exists' => 'Le statut sélectionné est invalide.',
            'assigned_to.exists' => 'L\'utilisateur assigné est invalide.',
        ];
    }
}
