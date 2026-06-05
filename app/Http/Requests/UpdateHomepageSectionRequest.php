<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesHomepageDesignSettings;
use App\Models\HomepageSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHomepageSectionRequest extends FormRequest
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
        /** @var HomepageSection|null $section */
        $section = $this->route('homepageSection');

        return array_merge([
            'type' => ['sometimes', 'required', Rule::in(HomepageSection::TYPES)],
            'key' => [
                'sometimes',
                'nullable',
                'string',
                'max:64',
                'alpha_dash',
                Rule::unique('homepage_sections', 'key')->ignore($section?->id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'content' => ['nullable', 'array'],
            'content.badge' => ['nullable', 'string', 'max:64'],
            'content.heading' => ['nullable', 'string', 'max:255'],
            'content.subtitle' => ['nullable', 'string'],
            'content.paragraphs' => ['nullable', 'array'],
            'content.paragraphs.*' => ['string'],
            'content.image_url' => ['nullable', 'string', 'max:1024'],
            'content.cta_text' => ['nullable', 'string', 'max:255'],
            'content.cta_link' => ['nullable', 'string', 'max:512'],
            'content.address' => ['nullable', 'string', 'max:500'],
            'content.email' => ['nullable', 'string', 'email', 'max:255'],
            'content.phone' => ['nullable', 'string', 'max:50'],
            'content.items' => ['nullable', 'array'],
            'content.items.*.icon' => ['required_with:content.items', 'string', 'max:64'],
            'content.items.*.iconColor' => ['nullable', 'string', 'max:64'],
            'content.items.*.title' => ['required_with:content.items', 'string', 'max:255'],
            'content.items.*.description' => ['required_with:content.items', 'string'],
            'content.mission_blocks' => ['nullable', 'array'],
            'content.mission_blocks.*.title' => ['required_with:content.mission_blocks', 'string', 'max:255'],
            'content.mission_blocks.*.body' => ['required_with:content.mission_blocks', 'string'],
            'content.mission_blocks.*.color' => ['nullable', 'string', 'max:64'],
            'content.stats' => ['nullable', 'array'],
            'content.stats.*.value' => ['required_with:content.stats', 'string', 'max:64'],
            'content.stats.*.label' => ['required_with:content.stats', 'string', 'max:255'],
            'content.stats.*.color' => ['nullable', 'string', 'max:64'],
            'content.affiliations' => ['nullable', 'array'],
            'content.affiliations.*.label' => ['required_with:content.affiliations', 'string', 'max:255'],
            'content.affiliations.*.color' => ['nullable', 'string', 'max:64'],
        ], $this->designSettingsRules());
    }
}
