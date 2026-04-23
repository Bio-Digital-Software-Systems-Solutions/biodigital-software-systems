<?php

namespace App\Http\Requests\Agile;

use App\Enums\Agile\UserStoryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('userStory'));
    }

    public function rules(): array
    {
        return [
            'epic_id' => ['sometimes', 'nullable', 'integer', 'exists:epics,id'],
            'sprint_id' => ['sometimes', 'nullable', 'integer', 'exists:sprints,id'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'as_a' => ['sometimes', 'string', 'max:255'],
            'i_want' => ['sometimes', 'string'],
            'so_that' => ['sometimes', 'string'],
            'story_points' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:999'],
            'priority' => ['sometimes', 'integer', 'between:1,5'],
            // Passage à 'done' interdit via update — doit passer par /complete qui exécute les invariants
            'status' => ['sometimes', Rule::enum(UserStoryStatus::class), Rule::notIn([UserStoryStatus::DONE->value])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.not_in' => __('agile.validation.user_story.use_complete_endpoint'),
        ];
    }
}
