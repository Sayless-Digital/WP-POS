<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\CashDrawerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected Customer $customer;
    protected CashDrawerSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'price' => 50.00,
            'stock' => 100,
        ]);
        $this->customer = Customer::factory()->create();
        $this->session = CashDrawerSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_create_order()
    {
        $orderData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'price' => 50.00,
                ]
            ],
            'subtotal' => 100.00,
            'tax' => 10.00,
            'total' => 110.00,
            'payment_method' => 'cash',
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'total',
                    'status',
                ]
            ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'total' => 110.00,
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function it_updates_stock_after_order()
    {
        $initialStock = $this->product->stock;

        $orderData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'price' => 50.00,
                ]
            ],
            'subtotal' => 250.00,
            'tax' => 25.00,
            'total' => 275.00,
            'payment_method' => 'cash',
        ];

        $this->postJson('/api/orders', $orderData);

        $this->product->refresh();
        $this->assertEquals($initialStock - 5, $this->product->stock);
    }

    /** @test */
    public function it_creates_payment_record()
    {
        $orderData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 50.00,
                ]
            ],
            'subtotal' => 50.00,
            'tax' => 5.00,
            'total' => 55.00,
            'payment_method' => 'cash',
            'amount_paid' => 60.00,
        ];

        $response = $this->postJson('/api/orders', $orderData);
        $orderId = $response->json('data.id');

        $this->assertDatabaseHas('payments', [
            'order_id' => $orderId,
            'amount' => 55.00,
            'payment_method' => 'cash',
        ]);
    }

    /** @test */
    public function it_prevents_order_with_insufficient_stock()
    {
        $this->product->update(['stock' => 2]);

        $orderData = [
            'customer_id' => $this->customer->id,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'price' => 50.00,
                ]
            ],
            'subtotal' => 250.00,
            'tax' => 25.00,
            'total' => 275.00,
            'payment_method' => 'cash',
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    /** @test */
    public function it_can_refund_order()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'status' => 'completed',
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/refund", [
            'amount' => 100.00,
            'reason' => 'Customer request',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount' => 100.00,
            'reason' => 'Customer request',
        ]);

        $order->refresh();
        $this->assertEquals('refunded', $order->status);
    }

    /** @test */
    public function it_can_list_orders()
    {
        Order::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /** @test */
    public function it_can_filter_orders_by_date()
    {
        Order::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(5),
        ]);

        Order::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/orders?from=' . now()->subDay()->toDateString());

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_get_order_details()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'total',
                    'status',
                    'items',
                    'payments',
                ]
            ]);
    }
}