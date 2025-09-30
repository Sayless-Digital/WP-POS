<?php

namespace Tests\Feature\Offline;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'price' => 25.00,
            'stock' => 100,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_cache_products_for_offline()
    {
        $response = $this->getJson('/api/products/cache');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'price',
                        'stock',
                        'barcode',
                    ]
                ],
                'timestamp',
            ]);
    }

    /** @test */
    public function it_can_sync_offline_orders()
    {
        $offlineOrders = [
            [
                'offline_id' => 'offline-001',
                'customer_id' => null,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'price' => 25.00,
                    ]
                ],
                'subtotal' => 50.00,
                'tax' => 5.00,
                'total' => 55.00,
                'payment_method' => 'cash',
                'created_at' => now()->subHours(2)->toISOString(),
            ]
        ];

        $response = $this->postJson('/api/orders/sync', [
            'orders' => $offlineOrders,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'synced' => [
                    '*' => [
                        'offline_id',
                        'order_id',
                        'status',
                    ]
                ],
                'failed',
            ]);

        $this->assertDatabaseHas('orders', [
            'total' => 55.00,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_handles_duplicate_offline_orders()
    {
        $offlineOrder = [
            'offline_id' => 'offline-002',
            'customer_id' => null,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 25.00,
                ]
            ],
            'subtotal' => 25.00,
            'tax' => 2.50,
            'total' => 27.50,
            'payment_method' => 'cash',
            'created_at' => now()->toISOString(),
        ];

        // First sync
        $this->postJson('/api/orders/sync', [
            'orders' => [$offlineOrder],
        ]);

        // Second sync (duplicate)
        $response = $this->postJson('/api/orders/sync', [
            'orders' => [$offlineOrder],
        ]);

        $response->assertStatus(200);

        // Should only have one order
        $this->assertEquals(1, Order::where('total', 27.50)->count());
    }

    /** @test */
    public function it_can_sync_inventory_changes()
    {
        $inventoryChanges = [
            [
                'product_id' => $this->product->id,
                'quantity_sold' => 5,
                'timestamp' => now()->subHour()->toISOString(),
            ]
        ];

        $response = $this->postJson('/api/inventory/sync', [
            'changes' => $inventoryChanges,
        ]);

        $response->assertStatus(200);

        $this->product->refresh();
        $this->assertEquals(95, $this->product->stock);
    }

    /** @test */
    public function it_can_get_product_by_barcode()
    {
        $this->product->barcodes()->create([
            'barcode' => '1234567890123',
            'type' => 'EAN13',
        ]);

        $response = $this->getJson('/api/products/by-barcode/1234567890123');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                ]
            ]);
    }

    /** @test */
    public function it_can_check_connection()
    {
        $response = $this->head('/api/ping');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_can_get_offline_stats()
    {
        Order::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->getJson('/api/offline/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_orders',
                'pending_syncs',
                'last_sync',
                'products_cached',
            ]);
    }

    /** @test */
    public function it_handles_conflicts_in_offline_sync()
    {
        // Create an order that will conflict
        $existingOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'total' => 100.00,
        ]);

        $conflictingOrder = [
            'offline_id' => 'offline-003',
            'server_id' => $existingOrder->id,
            'customer_id' => null,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 25.00,
                ]
            ],
            'subtotal' => 25.00,
            'tax' => 2.50,
            'total' => 27.50,
            'payment_method' => 'cash',
            'created_at' => now()->toISOString(),
        ];

        $response = $this->postJson('/api/orders/sync', [
            'orders' => [$conflictingOrder],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'synced',
                'failed',
                'conflicts',
            ]);
    }

    /** @test */
    public function it_validates_offline_order_data()
    {
        $invalidOrder = [
            'offline_id' => 'offline-004',
            'items' => [], // Empty items
            'total' => 0,
        ];

        $response = $this->postJson('/api/orders/sync', [
            'orders' => [$invalidOrder],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_batch_sync_multiple_orders()
    {
        $orders = [];
        for ($i = 1; $i <= 10; $i++) {
            $orders[] = [
                'offline_id' => "offline-{$i}",
                'customer_id' => null,
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 1,
                        'price' => 25.00,
                    ]
                ],
                'subtotal' => 25.00,
                'tax' => 2.50,
                'total' => 27.50,
                'payment_method' => 'cash',
                'created_at' => now()->subMinutes($i)->toISOString(),
            ];
        }

        $response = $this->postJson('/api/orders/sync', [
            'orders' => $orders,
        ]);

        $response->assertStatus(200);

        $this->assertEquals(10, Order::where('total', 27.50)->count());
    }
}