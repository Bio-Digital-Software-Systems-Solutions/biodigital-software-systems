<?php

namespace App\Http\Requests\Concerns;

trait ValidatesHomepageDesignSettings
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function designSettingsRules(string $prefix = 'design_settings'): array
    {
        return [
            $prefix => ['nullable', 'array'],
            "{$prefix}.font_family" => ['nullable', 'in:inter,poppins,playfair,roboto'],
            "{$prefix}.heading_size" => ['nullable', 'in:sm,md,lg,xl'],
            "{$prefix}.paragraph_size" => ['nullable', 'in:sm,md,lg'],
            "{$prefix}.text_color" => ['nullable', 'string', 'max:32'],
            "{$prefix}.background_color" => ['nullable', 'string', 'max:32'],
            "{$prefix}.alignment" => ['nullable', 'in:left,center,right'],
            "{$prefix}.layout" => ['nullable', 'in:single,two-col,three-col,grid,carousel'],
            "{$prefix}.padding" => ['nullable', 'in:none,sm,md,lg,xl'],
            "{$prefix}.margin" => ['nullable', 'in:none,sm,md,lg,xl'],
            "{$prefix}.padding_top" => ['nullable', 'in:none,sm,md,lg,xl'],
            "{$prefix}.padding_right" => ['nullable', 'in:none,sm,md,lg,xl'],
            "{$prefix}.padding_bottom" => ['nullable', 'in:none,sm,md,lg,xl'],
            "{$prefix}.padding_left" => ['nullable', 'in:none,sm,md,lg,xl'],
            "{$prefix}.margin_top" => ['nullable', 'in:none,sm,md,lg,xl'],
            "{$prefix}.margin_right" => ['nullable', 'in:none,sm,md,lg,xl'],
            "{$prefix}.margin_bottom" => ['nullable', 'in:none,sm,md,lg,xl'],
            "{$prefix}.margin_left" => ['nullable', 'in:none,sm,md,lg,xl'],
            "{$prefix}.background_image_url" => ['nullable', 'string', 'max:1024'],
            "{$prefix}.overlay_opacity" => ['nullable', 'numeric', 'min:0', 'max:1'],
            "{$prefix}.animation" => ['nullable', 'in:none,fade,slide,zoom'],
            "{$prefix}.dark_mode" => ['nullable', 'in:inherit,force-light,force-dark'],
        ];
    }
}
