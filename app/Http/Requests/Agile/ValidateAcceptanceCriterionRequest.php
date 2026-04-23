<?php

namespace App\Http\Requests\Agile;

use Illuminate\Foundation\Http\FormRequest;

class ValidateAcceptanceCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('validate', $this->route('criterion'));
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
