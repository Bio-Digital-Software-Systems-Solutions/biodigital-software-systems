<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view visitors');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'visitor_id' => ['required_without:first_name', 'nullable', 'exists:visitors,id'],
            'first_name' => ['required_without:visitor_id', 'nullable', 'string', 'max:255'],
            'last_name' => ['required_without:visitor_id', 'nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'in:friend,online,event,walk_in,other'],
            'first_visited_at' => ['required', 'date'],
            'invited_by' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'visitor_id.required_without' => 'Sélectionnez un visiteur existant ou saisissez un nouveau nom.',
            'first_name.required_without' => 'Le prénom est obligatoire pour un nouveau visiteur.',
            'last_name.required_without' => 'Le nom est obligatoire pour un nouveau visiteur.',
            'first_visited_at.required' => 'La date de première visite est obligatoire.',
        ];
    }
}
