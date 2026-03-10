<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before:today'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_calendar_public' => ['nullable', 'boolean'],
            // Avatar can be either an uploaded file or a string (filename from TUS upload)
            'avatar' => ['nullable', function ($attribute, $value, $fail): void {
                // If it's an uploaded file, validate as image
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $validator = \Validator::make(
                        [$attribute => $value],
                        [$attribute => 'image|mimes:jpeg,png,jpg,gif|max:10240']
                    );
                    if ($validator->fails()) {
                        $fail($validator->errors()->first($attribute));
                    }
                }
                // If it's a string, just validate it's not empty and has valid extension
                elseif (is_string($value)) {
                    $extension = pathinfo($value, PATHINFO_EXTENSION);
                    if (! in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) {
                        $fail('The avatar must be a file of type: jpeg, png, jpg, gif.');
                    }
                }
            }],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
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
            'first_name.required' => 'Le prénom est obligatoire.',
            'last_name.required' => 'Le nom est obligatoire.',
            'birth_date.required' => 'La date de naissance est obligatoire.',
            'birth_date.before' => 'La date de naissance doit être antérieure à aujourd\'hui.',
            'bio.max' => 'La biographie ne peut pas dépasser 1000 caractères.',
            'phone_number.max' => 'Le numéro de téléphone ne peut pas dépasser 20 caractères.',
            'position.max' => 'Le poste ne peut pas dépasser 255 caractères.',
            'address.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
            'email.required' => 'L\'email est obligatoire.',
            'email.email' => 'L\'email doit être une adresse email valide.',
            'email.unique' => 'Cet email est déjà utilisé.',
        ];
    }
}
