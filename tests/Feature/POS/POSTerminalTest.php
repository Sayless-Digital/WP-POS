<?php

namespace Tests\Feature\POS;

use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CashDrawerSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Pos\PosTerminal;

class POSTerminalTest extends TestCase
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
            'price' => 10.00,
            'stock' => 100,
            'is_active' => true,
        ]);
        $this->customer = Customer::factory()->create();
        $this->session = CashDrawerSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'opening_balance' => 100.00,
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_render_pos_terminal()
    {
        Livewire::test(PosTerminal::class)
            ->assertStatus(200)
            ->assertSee('POS Terminal');
    }

    /** @test */
    public function it_can_add_product_to_cart()
    {
        Livewire::test(PosTerminal::class)
            ->call('addToCart', $this->product->id)
            ->assertSet('cart', function ($cart) {
                return count($cart) === 1 && $cart[0]['product_id'] === $this->product->id;
            });
    }

    /** @test */
    public function it_can_update_cart_item_quantity()
    {
        Livewire::test(PosTerminal::class)
            ->call('addToCart', $this->product->id)
            ->call('updateQuantity', 0, 3)
            ->assertSet('cart', function ($cart) {
                return $cart[0]['quantity'] === 3;
            });
    }

    /** @test */
    public function it_can_remove_item_from_cart()
    {
        Livewire::test(PosTerminal::class)
            ->call('addToCart', $this->product->id)
            ->call('removeFromCart', 0)
            ->assertSet('cart', []);
    }

    /** @test */
    public function it_calculates_totals_correctly()
    {
        Livewire::test(PosTerminal::class)
            ->call('addToCart', $this->product->id)
            ->call('updateQuantity', 0, 2)
            ->assertSet('subtotal', 20.00)
            ->assertSet('total', function ($total) {
                return $total >= 20.00; // May include tax
            });
    }

    /** @test */
    public function it_can_apply_discount()
    {
        Livewire::test(PosTerminal::class)
            ->call('addToCart', $this->product->id)
            ->set('discountType', 'percentage')
            ->set('discountValue', 10)
            ->call('applyDiscount')
            ->assertSet('discount', 1.00);
    }

    /** @test */
    public function it_can_select_customer()
    {
        Livewire::test(PosTerminal::class)
            ->call('selectCustomer', $this->customer->id)
            ->assertSet('selectedCustomer', function ($customer) {
                return $customer->id === $this->customer->id;
            });
    }

    /** @test */
    public function it_can_clear_cart()
    {
        Livewire::test(PosTerminal::class)
            ->call('addToCart', $this->product->id)
            ->call('clearCart')
            ->assertSet('cart', [])
            ->assertSet('selectedCustomer', null)
            ->assertSet('discount', 0);
    }

    /** @test */
    public function it_prevents_adding_inactive_products()
    {
        $inactiveProduct = Product::factory()->create(['is_active' => false]);

        Livewire::test(PosTerminal::class)
            ->call('addToCart', $inactiveProduct->id)
            ->assertSet('cart', [])
            ->assertDispatched('error');
    }

    /** @test */
    public function it_prevents_adding_out_of_stock_products()
    {
        $outOfStockProduct = Product::factory()->create(['stock' => 0]);

        Livewire::test(PosTerminal::class)
            ->call('addToCart', $outOfStockProduct->id)
            ->assertSet('cart', [])
            ->assertDispatched('error');
    }

    /** @test */
    public function it_can_hold_order()
    {
        Livewire::test(PosTerminal::class)
            ->call('addToCart', $this->product->id)
            ->call('holdOrder', 'Test Order')
            ->assertSet('cart', [])
            ->assertDispatched('success');

        $this->assertDatabaseHas('held_orders', [
            'user_id' => $this->user->id,
            'reference' => 'Test Order',
        ]);
    }

    /** @test */
    public function it_can_search_products_by_barcode()
    {
        $this->product->barcodes()->create([
            'barcode' => '1234567890',
            'type' => 'EAN13',
        ]);

        Livewire::test(PosTerminal::class)
            ->set('barcodeSearch', '1234567890')
            ->call('searchByBarcode')
            ->assertSet('cart', function ($cart) {
                return count($cart) === 1;
            });
    }
}