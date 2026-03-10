<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasSkuTraitTest extends TestCase
{
    use RefreshDatabase;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->category = Category::factory()->create(['name' => 'Safety Equipment']);
    }

    public function test_sku_is_auto_generated_on_creation(): void
    {
        $stock = Stock::factory()->create([
            'category_id' => $this->category->id,
            'sku' => null, // Explicitly set to null
        ]);

        $this->assertNotNull($stock->sku);
        $this->assertNotEmpty($stock->sku);
    }

    public function test_sku_format_starts_with_stk_prefix(): void
    {
        $stock = Stock::factory()->create([
            'category_id' => $this->category->id,
            'sku' => null,
        ]);

        $this->assertStringStartsWith('STK-', $stock->sku);
    }

    public function test_sku_includes_category_prefix(): void
    {
        $stock = Stock::factory()->create([
            'category_id' => $this->category->id,
            'sku' => null,
        ]);

        // Category is "Safety Equipment" so prefix should be "SAF"
        $this->assertStringContainsString('-SAF-', $stock->sku);
    }

    public function test_sku_includes_current_date(): void
    {
        $stock = Stock::factory()->create([
            'category_id' => $this->category->id,
            'sku' => null,
        ]);

        $today = now()->format('Ymd');
        $this->assertStringContainsString("-{$today}-", $stock->sku);
    }

    public function test_sku_uses_gen_prefix_when_no_category(): void
    {
        // Create stock without category (if allowed by validation)
        // Since category_id is required in the controller, we test the trait directly
        $sku = Stock::generateUniqueSku();

        $this->assertStringContainsString('-GEN-', $sku);
    }

    public function test_sku_uses_category_prefix_with_special_characters_removed(): void
    {
        $categoryWithSpecialChars = Category::factory()->create([
            'name' => 'IT & Computers 2024',
        ]);

        $stock = Stock::factory()->create([
            'category_id' => $categoryWithSpecialChars->id,
            'sku' => null,
        ]);

        // Should use "ITC" (first 3 letters of "ITComputers" after removing special chars)
        $this->assertStringContainsString('-ITC-', $stock->sku);
    }

    public function test_multiple_stocks_get_unique_skus(): void
    {
        $skus = [];

        for ($i = 0; $i < 10; $i++) {
            $stock = Stock::factory()->create([
                'category_id' => $this->category->id,
                'sku' => null,
            ]);
            $skus[] = $stock->sku;
        }

        // All SKUs should be unique
        $this->assertCount(10, array_unique($skus));
    }

    public function test_sku_is_not_overwritten_if_provided(): void
    {
        $customSku = 'CUSTOM-SKU-123';

        $stock = Stock::factory()->create([
            'category_id' => $this->category->id,
            'sku' => $customSku,
        ]);

        $this->assertEquals($customSku, $stock->sku);
    }

    public function test_generate_unique_sku_static_method(): void
    {
        $sku = Stock::generateUniqueSku($this->category->id);

        $this->assertNotNull($sku);
        $this->assertStringStartsWith('STK-', $sku);
        $this->assertStringContainsString('-SAF-', $sku);
    }

    public function test_generate_unique_sku_avoids_duplicates(): void
    {
        // Create a stock with auto-generated SKU
        $stock1 = Stock::factory()->create([
            'category_id' => $this->category->id,
            'sku' => null,
        ]);

        // Generate another SKU - should be different
        $sku2 = Stock::generateUniqueSku($this->category->id);

        $this->assertNotEquals($stock1->sku, $sku2);
    }

    public function test_sku_format_has_four_parts(): void
    {
        $stock = Stock::factory()->create([
            'category_id' => $this->category->id,
            'sku' => null,
        ]);

        $parts = explode('-', $stock->sku);

        // Format: STK-{PREFIX}-{DATE}-{RANDOM}
        $this->assertCount(4, $parts);
        $this->assertEquals('STK', $parts[0]);
        $this->assertEquals('SAF', $parts[1]); // Category prefix
        $this->assertEquals(now()->format('Ymd'), $parts[2]); // Date
        $this->assertEquals(4, strlen($parts[3])); // Random part is 4 digits
    }

    public function test_get_sku_prefix_method(): void
    {
        $stock = Stock::factory()->create([
            'category_id' => $this->category->id,
        ]);

        $stock->load('category');

        $this->assertEquals('SAF', $stock->getSkuPrefix());
    }

    public function test_get_sku_prefix_returns_gen_without_category(): void
    {
        $stock = new Stock;
        $stock->category_id = null;

        $this->assertEquals('GEN', $stock->getSkuPrefix());
    }

    public function test_short_category_name_uses_available_letters(): void
    {
        $shortCategory = Category::factory()->create(['name' => 'IT']);

        $stock = Stock::factory()->create([
            'category_id' => $shortCategory->id,
            'sku' => null,
        ]);

        // Should use "IT" as prefix (only 2 letters available)
        $this->assertStringContainsString('-IT-', $stock->sku);
    }

    public function test_unicode_category_name_handles_non_ascii(): void
    {
        $unicodeCategory = Category::factory()->create(['name' => 'Équipement Médical']);

        $stock = Stock::factory()->create([
            'category_id' => $unicodeCategory->id,
            'sku' => null,
        ]);

        // Should extract only ASCII letters: "quipementMdical" -> "QUI"
        $this->assertStringContainsString('-QUI-', $stock->sku);
    }

    public function test_concurrent_creation_generates_unique_skus(): void
    {
        // Simulate bulk creation
        $stocks = Stock::factory()->count(50)->create([
            'category_id' => $this->category->id,
            'sku' => null,
        ]);

        $skus = $stocks->pluck('sku')->toArray();
        $uniqueSkus = array_unique($skus);

        // All 50 SKUs should be unique
        $this->assertCount(50, $uniqueSkus);
    }
}
