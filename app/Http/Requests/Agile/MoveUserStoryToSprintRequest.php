<?php

namespace App\Http\Requests\Agile;

use Illuminate\Foundation\Http\FormRequest;

class MoveUserStoryToSprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('moveToSprint', $this->route('user_story'));
    }

    public function rules(): array
    {
        return [
            'sprint_id' => ['nullable', 'integer', 'exists:sprints,id'],
        ];
    }
}
