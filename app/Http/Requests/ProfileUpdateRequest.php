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
            // Avatar can be either an uploaded file or a string (filename from TUS upload)
            'avatar' => ['nullable', function ($attribute, $value, $fail) {
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
                    if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) {
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
}
