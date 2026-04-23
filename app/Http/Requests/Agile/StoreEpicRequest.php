<?php

namespace App\Http\Requests\Agile;

use App\Enums\Agile\EpicStatus;
use App\Models\Agile\Epic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEpicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Epic::class);
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'owner_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'business_value' => ['nullable', 'string'],
            'status' => ['nullable', Rule::enum(EpicStatus::class)],
            'priority' => ['nullable', 'integer', 'between:1,5'],
            'target_date' => ['nullable', 'date'],
            'labels' => ['nullable', 'array'],
            'labels.*' => ['string', 'max:64'],
        ];
    }
}
