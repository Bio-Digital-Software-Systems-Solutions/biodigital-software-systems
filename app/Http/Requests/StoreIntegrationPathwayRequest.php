<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntegrationPathwayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage integration pathways');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_type' => ['nullable', 'in:group,department'],
            'is_default' => ['boolean'],
            'is_active' => ['boolean'],
            'steps' => ['nullable', 'array'],
            'steps.*.name' => ['required_with:steps', 'string', 'max:255'],
            'steps.*.description' => ['nullable', 'string'],
            'steps.*.order_index' => ['required_with:steps', 'integer', 'min:0'],
            'steps.*.type' => ['required_with:steps', 'in:attendance_count,activity_participation,meeting_attendance,training_completion,manual_approval,custom'],
            'steps.*.criteria' => ['nullable', 'array'],
            'steps.*.weight' => ['required_with:steps', 'integer', 'min:1', 'max:10'],
            'steps.*.is_required' => ['boolean'],
        ];
    }
}
