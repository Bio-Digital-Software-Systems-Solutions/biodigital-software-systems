<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
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
        $studentId = $this->route('student')->id;

        return [
            'user_id' => 'sometimes|required|exists:users,id|unique:students,user_id,'.$studentId,
            'student_number' => 'sometimes|required|string|max:50|unique:students,student_number,'.$studentId,
            'level' => 'sometimes|required|string|in:Beginner,Intermediate,Advanced,Expert',
            'enrollment_date' => 'sometimes|required|date',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ];
    }
}
