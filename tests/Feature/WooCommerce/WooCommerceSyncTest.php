<?php

namespace Tests\Feature\WooCommerce;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use App\Services\WooCommerce\ProductSyncService;
use App\Services\WooCommerce\OrderSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;

class WooCommerceSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Set WooCommerce config for testing
        config([
            'woocommerce.store_url' => 'https://test-store.com',
            'woocommerce.consumer_key' => 'test_key',
            'woocommerce.consumer_secret' => 'test_secret',
            'woocommerce.enabled' => true,
        ]);
    }

    /** @test */
    public function it_can_sync_products_from_woocommerce()
    {
        Http::fake([
            '*/wp-json/wc/v3/products*' => Http::response([
                [
                    'id' => 123,
                    'name' => 'Test Product',
                    'price' => '29.99',
                    'sku' => 'TEST-SKU',
                    'stock_quantity' => 100,
                    'manage_stock' => true,
                ]
            ], 200),
        ]);

        $response = $this->postJson('/api/woocommerce/sync/products');

        $response->assertStatus(200);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU',
            'woocommerce_id' => 123,
        ]);
    }

    /** @test */
    public function it_can_sync_order_to_woocommerce()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total' => 100.00,
            'status' => 'completed',
        ]);

        Http::fake([
            '*/wp-json/wc/v3/orders' => Http::response([
                'id' => 456,
                'status' => 'processing',
            ], 201),
        ]);

        $response = $this->postJson("/api/woocommerce/sync/orders/{$order->id}");

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals(456, $order->woocommerce_id);
    }

    /** @test */
    public function it_can_sync_inventory_to_woocommerce()
    {
        $product = Product::factory()->create([
            'woocommerce_id' => 123,
            'stock' => 50,
        ]);

        Http::fake([
            '*/wp-json/wc/v3/products/123' => Http::response([
                'id' => 123,
                'stock_quantity' => 50,
            ], 200),
        ]);

        $response = $this->postJson('/api/woocommerce/sync/inventory', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_woocommerce_webhook()
    {
        Http::fake();

        $webhookData = [
            'id' => 789,
            'status' => 'completed',
            'line_items' => [
                [
                    'product_id' => 123,
                    'quantity' => 2,
                ]
            ],
        ];

        $response = $this->postJson('/api/woocommerce/webhook/order', $webhookData);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_get_sync_status()
    {
        $response = $this->getJson('/api/woocommerce/sync/status');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'enabled',
                'last_sync',
                'pending_syncs',
            ]);
    }

    /** @test */
    public function it_validates_woocommerce_credentials()
    {
        Http::fake([
            '*/wp-json/wc/v3/system_status' => Http::response([
                'environment' => [
                    'version' => '6.0.0',
                ]
            ], 200),
        ]);

        $response = $this->postJson('/api/woocommerce/validate', [
            'store_url' => 'https://test-store.com',
            'consumer_key' => 'test_key',
            'consumer_secret' => 'test_secret',
        ]);

        $response->assertStatus(200)
            ->assertJson(['valid' => true]);
    }

    /** @test */
    public function it_handles_sync_failures()
    {
        Http::fake([
            '*/wp-json/wc/v3/products*' => Http::response([], 500),
        ]);

        $response = $this->postJson('/api/woocommerce/sync/products');

        $response->assertStatus(500);

        $this->assertDatabaseHas('sync_logs', [
            'type' => 'product',
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function it_can_retry_failed_syncs()
    {
        $product = Product::factory()->create([
            'woocommerce_id' => null,
        ]);

        Http::fake([
            '*/wp-json/wc/v3/products' => Http::response([
                'id' => 999,
            ], 201),
        ]);

        $response = $this->postJson('/api/woocommerce/sync/retry');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_disable_sync_for_specific_products()
    {
        $product = Product::factory()->create([
            'sync_to_woocommerce' => false,
        ]);

        Http::fake();

        $response = $this->postJson('/api/woocommerce/sync/products');

        $response->assertStatus(200);

        Http::assertNothingSent();
    }
}