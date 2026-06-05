<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesHomepageDesignSettings;
use App\Models\HomepageSubsection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHomepageSubsectionRequest extends FormRequest
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
            'block_type' => ['required', Rule::in(HomepageSubsection::BLOCK_TYPES)],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'content' => ['required', 'array'],
            'content.text' => ['required_if:block_type,heading,paragraph', 'string'],
            'content.level' => ['nullable', 'integer', 'in:1,2,3'],
            'content.url' => ['required_if:block_type,image', 'string', 'max:1024'],
            'content.alt' => ['nullable', 'string', 'max:255'],
            'content.caption' => ['nullable', 'string', 'max:500'],
            'content.label' => ['required_if:block_type,button', 'string', 'max:255'],
            'content.href' => ['required_if:block_type,button', 'string', 'max:512'],
            'content.variant' => ['nullable', 'in:default,outline,ghost,secondary'],
            'content.title' => ['required_if:block_type,card', 'string', 'max:255'],
            'content.body' => ['nullable', 'string'],
            'content.icon' => ['nullable', 'string', 'max:64'],
            'content.color' => ['nullable', 'string', 'max:32'],
        ], $this->designSettingsRules());
    }
}
