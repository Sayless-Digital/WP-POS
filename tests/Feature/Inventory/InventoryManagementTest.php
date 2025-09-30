<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'stock' => 100,
            'low_stock_threshold' => 10,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_adjust_stock()
    {
        $response = $this->postJson('/api/inventory/adjust', [
            'product_id' => $this->product->id,
            'quantity' => 50,
            'type' => 'addition',
            'reason' => 'Stock replenishment',
        ]);

        $response->assertStatus(200);

        $this->product->refresh();
        $this->assertEquals(150, $this->product->stock);
    }

    /** @test */
    public function it_records_stock_movement()
    {
        $this->postJson('/api/inventory/adjust', [
            'product_id' => $this->product->id,
            'quantity' => 25,
            'type' => 'subtraction',
            'reason' => 'Damaged goods',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'quantity' => -25,
            'type' => 'adjustment',
            'reason' => 'Damaged goods',
        ]);
    }

    /** @test */
    public function it_can_get_stock_movements()
    {
        StockMovement::factory()->count(5)->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->getJson("/api/inventory/{$this->product->id}/movements");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_can_get_low_stock_products()
    {
        Product::factory()->create([
            'stock' => 5,
            'low_stock_threshold' => 10,
        ]);

        Product::factory()->create([
            'stock' => 50,
            'low_stock_threshold' => 10,
        ]);

        $response = $this->getJson('/api/inventory/low-stock');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_prevents_negative_stock()
    {
        $this->product->update(['stock' => 10]);

        $response = $this->postJson('/api/inventory/adjust', [
            'product_id' => $this->product->id,
            'quantity' => 20,
            'type' => 'subtraction',
            'reason' => 'Test',
        ]);

        $response->assertStatus(422);

        $this->product->refresh();
        $this->assertEquals(10, $this->product->stock);
    }

    /** @test */
    public function it_can_bulk_update_stock()
    {
        $product2 = Product::factory()->create(['stock' => 50]);

        $response = $this->postJson('/api/inventory/bulk-adjust', [
            'adjustments' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10,
                    'type' => 'addition',
                ],
                [
                    'product_id' => $product2->id,
                    'quantity' => 5,
                    'type' => 'subtraction',
                ],
            ],
            'reason' => 'Bulk adjustment',
        ]);

        $response->assertStatus(200);

        $this->product->refresh();
        $product2->refresh();

        $this->assertEquals(110, $this->product->stock);
        $this->assertEquals(45, $product2->stock);
    }

    /** @test */
    public function it_can_get_inventory_value()
    {
        Product::factory()->create([
            'cost' => 10.00,
            'stock' => 100,
        ]);

        Product::factory()->create([
            'cost' => 20.00,
            'stock' => 50,
        ]);

        $response = $this->getJson('/api/inventory/value');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_value',
                'total_items',
                'by_category',
            ]);
    }

    /** @test */
    public function it_can_export_inventory()
    {
        Product::factory()->count(10)->create();

        $response = $this->getJson('/api/inventory/export');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}