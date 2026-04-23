<?php

namespace App\Http\Requests\Agile;

use App\Enums\Agile\UserStoryStatus;
use App\Models\Agile\UserStory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserStoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', UserStory::class);
    }

    public function rules(): array
    {
        return [
            'epic_id' => ['nullable', 'integer', 'exists:epics,id'],
            'sprint_id' => ['nullable', 'integer', 'exists:sprints,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'reporter_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'as_a' => ['required', 'string', 'max:255'],
            'i_want' => ['required', 'string'],
            'so_that' => ['required', 'string'],
            'story_points' => ['nullable', 'integer', 'min:0', 'max:999'],
            'priority' => ['nullable', 'integer', 'between:1,5'],
            'status' => ['nullable', Rule::enum(UserStoryStatus::class), Rule::notIn([UserStoryStatus::DONE->value])],
        ];
    }

    public function messages(): array
    {
        return [
            'status.not_in' => __('agile.validation.user_story.status_done_forbidden_on_store'),
        ];
    }
}
