<?php

namespace App\Http\Requests\Agile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestScenarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('test_scenario'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'given' => ['nullable', 'string'],
            'when' => ['nullable', 'string'],
            'then' => ['nullable', 'string'],
            'free_form' => ['nullable', 'string'],
            'automated_test_ref' => ['nullable', 'string', 'max:255'],
        ];
    }
}
