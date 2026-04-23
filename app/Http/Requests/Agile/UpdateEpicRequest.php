<?php

namespace App\Http\Requests\Agile;

use App\Enums\Agile\EpicStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEpicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('epic'));
    }

    public function rules(): array
    {
        return [
            'owner_id' => ['sometimes', 'integer', 'exists:users,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'business_value' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(EpicStatus::class)],
            'priority' => ['sometimes', 'integer', 'between:1,5'],
            'target_date' => ['nullable', 'date'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['string', 'max:64'],
        ];
    }
}
