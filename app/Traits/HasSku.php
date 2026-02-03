<?php

namespace App\Traits;

use App\Models\Category;

trait HasSku
{
    /**
     * Boot the trait.
     */
    protected static function bootHasSku(): void
    {
        static::creating(function ($model) {
            if (empty($model->sku)) {
                $model->sku = static::generateUniqueSku($model->category_id ?? null);
            }
        });
    }

    /**
     * Generate a unique SKU code.
     *
     * Format: STK-{CATEGORY_PREFIX}-{YYYYMMDD}-{RANDOM}
     * Example: STK-SAF-20260203-1234
     */
    public static function generateUniqueSku(?int $categoryId = null): string
    {
        $prefix = 'GEN';

        if ($categoryId) {
            $category = Category::find($categoryId);
            if ($category) {
                // Use first 3 letters of category name as prefix (uppercase, letters only)
                $cleanName = preg_replace('/[^a-zA-Z]/', '', $category->name);
                $prefix = strtoupper(substr($cleanName, 0, 3));
            }
        }

        $date = now()->format('Ymd');
        $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        $sku = "STK-{$prefix}-{$date}-{$random}";

        // Ensure uniqueness by regenerating random part if needed
        $maxAttempts = 100;
        $attempts = 0;

        while (static::where('sku', $sku)->exists() && $attempts < $maxAttempts) {
            $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $sku = "STK-{$prefix}-{$date}-{$random}";
            $attempts++;
        }

        // If still not unique after max attempts, add timestamp milliseconds
        if (static::where('sku', $sku)->exists()) {
            $sku = "STK-{$prefix}-{$date}-".now()->format('His');
        }

        return $sku;
    }

    /**
     * Get the SKU prefix for the model based on category.
     */
    public function getSkuPrefix(): string
    {
        if ($this->category_id && $this->category) {
            $cleanName = preg_replace('/[^a-zA-Z]/', '', $this->category->name);

            return strtoupper(substr($cleanName, 0, 3));
        }

        return 'GEN';
    }
}
