<?php

namespace App\Http\Requests\Agile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcceptanceCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('acceptance_criterion'));
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'position' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
