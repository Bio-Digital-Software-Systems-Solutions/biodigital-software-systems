<?php

namespace App\Http\Requests\Agile;

use App\Models\Agile\AcceptanceCriterion;
use Illuminate\Foundation\Http\FormRequest;

class StoreAcceptanceCriterionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AcceptanceCriterion::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'position' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
