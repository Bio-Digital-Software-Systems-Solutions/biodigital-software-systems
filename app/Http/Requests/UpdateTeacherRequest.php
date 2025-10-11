<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherRequest extends FormRequest
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
        $teacherId = $this->route('teacher')->id;

        return [
            'user_id' => 'sometimes|required|exists:users,id|unique:teachers,user_id,'.$teacherId,
            'specialization' => 'sometimes|required|string|max:255',
            'experience_years' => 'sometimes|required|integer|min:0|max:50',
            'bio' => 'nullable|string|max:2000',
            'qualifications' => 'nullable|array',
            'qualifications.*' => 'string|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ];
    }
}
