<?php

namespace App\Http\Requests\Agile;

use App\Enums\Agile\StoryTaskType;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStoryTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Task::class);
    }

    public function rules(): array
    {
        return [
            'user_story_id' => ['required', 'integer', 'exists:user_stories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'work_type' => ['required', Rule::enum(StoryTaskType::class)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'status_id' => ['nullable', 'integer', 'exists:statuses,id'],
            'priority' => ['nullable', 'in:low,medium,high'],
        ];
    }
}
