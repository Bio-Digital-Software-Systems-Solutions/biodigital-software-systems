<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    public function test_index_displays_stocks(): void
    {
        $stocks = Stock::factory()->count(3)->create(['category_id' => $this->category->id]);

        $this->user->givePermissionTo('view stocks');

        $response = $this->actingAs($this->user)
            ->get(route('stocks.index'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Stocks/Index')
            ->has('stocks.data', 3)
            ->has('categories')
        );
    }

    public function test_index_can_filter_by_category(): void
    {
        $category2 = Category::factory()->create();

        Stock::factory()->count(2)->create(['category_id' => $this->category->id]);
        Stock::factory()->create(['category_id' => $category2->id]);

        $this->user->givePermissionTo('view stocks');

        $response = $this->actingAs($this->user)
            ->get(route('stocks.index', ['category' => $this->category->id]));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Stocks/Index')
            ->has('stocks.data', 2)
        );
    }

    public function test_index_can_filter_by_low_stock(): void
    {
        Stock::factory()->create([
            'category_id' => $this->category->id,
            'quantity' => 5,
            'minimum_quantity' => 10,
        ]);

        Stock::factory()->create([
            'category_id' => $this->category->id,
            'quantity' => 20,
            'minimum_quantity' => 10,
        ]);

        $this->user->givePermissionTo('view stocks');

        $response = $this->actingAs($this->user)
            ->get(route('stocks.index', ['status' => 'low_stock']));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Stocks/Index')
            ->has('stocks.data', 1)
        );
    }

    public function test_create_displays_form(): void
    {
        $this->user->givePermissionTo('manage stocks');

        $response = $this->actingAs($this->user)
            ->get(route('stocks.create'));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Stocks/Create')
            ->has('categories')
        );
    }

    public function test_store_creates_stock(): void
    {
        $this->user->givePermissionTo('manage stocks');

        $stockData = [
            'name' => 'Test Stock Item',
            'sku' => 'TSI-001',
            'description' => 'Test stock description',
            'quantity' => 100,
            'minimum_quantity' => 10,
            'unit_price' => '25.50',
            'supplier' => 'Test Supplier',
            'supplier_contact' => 'supplier@test.com',
            'location' => 'Warehouse A',
            'is_active' => true,
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('stocks.store'), $stockData);

        $response->assertRedirect(route('stocks.index'));
        $response->assertSessionHas('success', 'Stock item created successfully.');

        $this->assertDatabaseHas('stocks', [
            'name' => 'Test Stock Item',
            'sku' => 'TSI-001',
            'quantity' => 100,
            'minimum_quantity' => 10,
            'unit_price' => 25.50,
            'category_id' => $this->category->id,
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $this->user->givePermissionTo('manage stocks');

        $response = $this->actingAs($this->user)
            ->post(route('stocks.store'), []);

        $response->assertSessionHasErrors([
            'name', 'sku', 'quantity', 'minimum_quantity', 'unit_price', 'category_id',
        ]);
    }

    public function test_store_validates_unique_sku(): void
    {
        Stock::factory()->create([
            'category_id' => $this->category->id,
            'sku' => 'DUPLICATE-SKU',
        ]);

        $this->user->givePermissionTo('manage stocks');

        $response = $this->actingAs($this->user)
            ->post(route('stocks.store'), [
                'name' => 'Test Item',
                'sku' => 'DUPLICATE-SKU',
                'quantity' => 10,
                'minimum_quantity' => 5,
                'unit_price' => '10.00',
                'category_id' => $this->category->id,
            ]);

        $response->assertSessionHasErrors(['sku']);
    }

    public function test_store_validates_numeric_fields(): void
    {
        $this->user->givePermissionTo('manage stocks');

        $response = $this->actingAs($this->user)
            ->post(route('stocks.store'), [
                'name' => 'Test Item',
                'sku' => 'TEST-001',
                'quantity' => 'not-a-number',
                'minimum_quantity' => 'not-a-number',
                'unit_price' => 'not-a-number',
                'category_id' => $this->category->id,
            ]);

        $response->assertSessionHasErrors(['quantity', 'minimum_quantity', 'unit_price']);
    }

    public function test_store_validates_negative_values(): void
    {
        $this->user->givePermissionTo('manage stocks');

        $response = $this->actingAs($this->user)
            ->post(route('stocks.store'), [
                'name' => 'Test Item',
                'sku' => 'TEST-001',
                'quantity' => -5,
                'minimum_quantity' => -2,
                'unit_price' => -10.00,
                'category_id' => $this->category->id,
            ]);

        $response->assertSessionHasErrors(['quantity', 'minimum_quantity', 'unit_price']);
    }

    public function test_store_validates_expiry_date(): void
    {
        $this->user->givePermissionTo('manage stocks');

        $response = $this->actingAs($this->user)
            ->post(route('stocks.store'), [
                'name' => 'Test Item',
                'sku' => 'TEST-001',
                'quantity' => 10,
                'minimum_quantity' => 5,
                'unit_price' => '10.00',
                'expiry_date' => now()->subDay()->format('Y-m-d'),
                'category_id' => $this->category->id,
            ]);

        $response->assertSessionHasErrors(['expiry_date']);
    }

    public function test_show_displays_stock(): void
    {
        $stock = Stock::factory()->create(['category_id' => $this->category->id]);

        $this->user->givePermissionTo('view stocks');

        $response = $this->actingAs($this->user)
            ->get(route('stocks.show', $stock));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Stocks/Show')
            ->where('stock.id', $stock->id)
            ->where('stock.name', $stock->name)
        );
    }

    public function test_edit_displays_form(): void
    {
        $stock = Stock::factory()->create(['category_id' => $this->category->id]);

        $this->user->givePermissionTo('manage stocks');

        $response = $this->actingAs($this->user)
            ->get(route('stocks.edit', $stock));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Stocks/Edit')
            ->where('stock.id', $stock->id)
            ->has('categories')
        );
    }

    public function test_update_modifies_stock(): void
    {
        $stock = Stock::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Original Name',
            'sku' => 'ORIG-001',
            'quantity' => 50,
        ]);

        $this->user->givePermissionTo('manage stocks');

        $updateData = [
            'name' => 'Updated Name',
            'sku' => 'UPD-001',
            'description' => 'Updated description',
            'quantity' => 75,
            'minimum_quantity' => 15,
            'unit_price' => '30.00',
            'supplier' => 'Updated Supplier',
            'location' => 'Warehouse B',
            'is_active' => false,
            'category_id' => $this->category->id,
        ];

        $response = $this->actingAs($this->user)
            ->put(route('stocks.update', $stock), $updateData);

        $response->assertRedirect(route('stocks.index'));
        $response->assertSessionHas('success', 'Stock item updated successfully.');

        $stock->refresh();
        $this->assertEquals('Updated Name', $stock->name);
        $this->assertEquals('UPD-001', $stock->sku);
        $this->assertEquals(75, $stock->quantity);
        $this->assertEquals(15, $stock->minimum_quantity);
        $this->assertEquals(30.00, (float) $stock->unit_price);
        $this->assertFalse($stock->is_active);
    }

    public function test_update_validates_unique_sku_excluding_current(): void
    {
        $stock1 = Stock::factory()->create([
            'category_id' => $this->category->id,
            'sku' => 'STOCK-001',
        ]);

        $stock2 = Stock::factory()->create([
            'category_id' => $this->category->id,
            'sku' => 'STOCK-002',
        ]);

        $this->user->givePermissionTo('manage stocks');

        $response = $this->actingAs($this->user)
            ->put(route('stocks.update', $stock2), [
                'name' => 'Updated Stock',
                'sku' => 'STOCK-001', // Try to use existing SKU
                'quantity' => 10,
                'minimum_quantity' => 5,
                'unit_price' => '10.00',
                'category_id' => $this->category->id,
            ]);

        $response->assertSessionHasErrors(['sku']);
    }

    public function test_destroy_deletes_stock(): void
    {
        $stock = Stock::factory()->create(['category_id' => $this->category->id]);

        $this->user->givePermissionTo('manage stocks');

        $response = $this->actingAs($this->user)
            ->delete(route('stocks.destroy', $stock));

        $response->assertRedirect(route('stocks.index'));
        $response->assertSessionHas('success', 'Stock item deleted successfully.');

        $this->assertModelMissing($stock);
    }

    public function test_unauthorized_user_cannot_manage_stocks(): void
    {
        $stock = Stock::factory()->create(['category_id' => $this->category->id]);

        $response = $this->actingAs($this->user)
            ->get(route('stocks.index'));

        $response->assertForbidden();
    }

    public function test_can_filter_by_supplier(): void
    {
        Stock::factory()->count(2)->create([
            'category_id' => $this->category->id,
            'supplier' => 'Acme Corp',
        ]);

        Stock::factory()->create([
            'category_id' => $this->category->id,
            'supplier' => 'Other Supplier',
        ]);

        $this->user->givePermissionTo('view stocks');

        $response = $this->actingAs($this->user)
            ->get(route('stocks.index', ['supplier' => 'Acme']));

        $response->assertOk();
        $response->assertInertia(fn ($assert) => $assert->component('Stocks/Index')
            ->has('stocks.data', 2)
        );
    }
}
