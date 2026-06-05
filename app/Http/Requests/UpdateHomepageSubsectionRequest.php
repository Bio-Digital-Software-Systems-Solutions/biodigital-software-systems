<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesHomepageDesignSettings;
use App\Models\HomepageSubsection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHomepageSubsectionRequest extends FormRequest
{
    use ValidatesHomepageDesignSettings;

    public function authorize(): bool
    {
        return $this->user()?->can('manage homepage sections') ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return array_merge([
            'block_type' => ['sometimes', 'required', Rule::in(HomepageSubsection::BLOCK_TYPES)],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'content' => ['sometimes', 'required', 'array'],
            'content.text' => ['nullable', 'string'],
            'content.level' => ['nullable', 'integer', 'in:1,2,3'],
            'content.url' => ['nullable', 'string', 'max:1024'],
            'content.alt' => ['nullable', 'string', 'max:255'],
            'content.caption' => ['nullable', 'string', 'max:500'],
            'content.label' => ['nullable', 'string', 'max:255'],
            'content.href' => ['nullable', 'string', 'max:512'],
            'content.variant' => ['nullable', 'in:default,outline,ghost,secondary'],
            'content.title' => ['nullable', 'string', 'max:255'],
            'content.body' => ['nullable', 'string'],
            'content.icon' => ['nullable', 'string', 'max:64'],
            'content.color' => ['nullable', 'string', 'max:32'],
        ], $this->designSettingsRules());
    }
}
