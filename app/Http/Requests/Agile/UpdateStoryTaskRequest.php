<?php

namespace App\Http\Requests\Agile;

use App\Enums\Agile\StoryTaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoryTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('storyTask'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'work_type' => ['sometimes', Rule::enum(StoryTaskType::class)],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'estimated_hours' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999.99'],
            'actual_hours' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999.99'],
            'status_id' => ['sometimes', 'integer', 'exists:statuses,id'],
            'priority' => ['sometimes', 'in:low,medium,high'],
        ];
    }
}
