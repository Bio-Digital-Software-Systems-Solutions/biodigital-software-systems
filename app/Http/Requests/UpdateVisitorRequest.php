<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit visitors');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('visitors', 'email')->ignore($this->route('visitor'))],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female,other'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'notes' => ['nullable', 'string'],
            'source' => ['nullable', 'in:friend,online,event,walk_in,other'],
            'first_visit_date' => ['required', 'date'],
            'status' => ['nullable', 'in:active,inactive,integrated,archived'],
        ];
    }
}
