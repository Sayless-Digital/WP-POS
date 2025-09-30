<?php

namespace Tests\Browser;

use App\Models\User;
use App\Models\Product;
use App\Models\CashDrawerSession;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class POSTerminalTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 29.99,
            'stock' => 100,
            'is_active' => true,
        ]);

        CashDrawerSession::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'opening_balance' => 100.00,
        ]);
    }

    /** @test */
    public function user_can_login_and_access_pos()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'test@example.com')
                ->type('password', 'password')
                ->press('Log in')
                ->assertPathIs('/dashboard')
                ->visit('/pos')
                ->assertSee('POS Terminal');
        });
    }

    /** @test */
    public function user_can_add_product_to_cart()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->waitFor('@product-grid')
                ->click('@product-' . $this->product->id)
                ->waitFor('@cart-item')
                ->assertSee($this->product->name)
                ->assertSee('$29.99');
        });
    }

    /** @test */
    public function user_can_update_cart_quantity()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->click('@product-' . $this->product->id)
                ->waitFor('@cart-item')
                ->click('@quantity-increase')
                ->pause(500)
                ->assertSee('2')
                ->assertSee('$59.98');
        });
    }

    /** @test */
    public function user_can_remove_item_from_cart()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->click('@product-' . $this->product->id)
                ->waitFor('@cart-item')
                ->click('@remove-item')
                ->pause(500)
                ->assertDontSee($this->product->name);
        });
    }

    /** @test */
    public function user_can_search_products()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->type('@product-search', $this->product->name)
                ->pause(1000)
                ->assertSee($this->product->name);
        });
    }

    /** @test */
    public function user_can_scan_barcode()
    {
        $this->product->barcodes()->create([
            'barcode' => '1234567890123',
            'type' => 'EAN13',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->type('@barcode-input', '1234567890123')
                ->keys('@barcode-input', '{enter}')
                ->pause(1000)
                ->waitFor('@cart-item')
                ->assertSee($this->product->name);
        });
    }

    /** @test */
    public function user_can_apply_discount()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->click('@product-' . $this->product->id)
                ->waitFor('@cart-item')
                ->click('@apply-discount')
                ->waitFor('@discount-modal')
                ->select('@discount-type', 'percentage')
                ->type('@discount-value', '10')
                ->click('@confirm-discount')
                ->pause(500)
                ->assertSee('Discount: $3.00');
        });
    }

    /** @test */
    public function user_can_select_customer()
    {
        $customer = \App\Models\Customer::factory()->create([
            'name' => 'John Doe',
        ]);

        $this->browse(function (Browser $browser) use ($customer) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->click('@select-customer')
                ->waitFor('@customer-modal')
                ->type('@customer-search', 'John')
                ->pause(1000)
                ->click('@customer-' . $customer->id)
                ->pause(500)
                ->assertSee('John Doe');
        });
    }

    /** @test */
    public function user_can_complete_cash_sale()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->click('@product-' . $this->product->id)
                ->waitFor('@cart-item')
                ->click('@checkout')
                ->waitFor('@payment-modal')
                ->click('@payment-cash')
                ->type('@amount-received', '50')
                ->click('@complete-payment')
                ->pause(2000)
                ->assertSee('Payment Successful')
                ->assertSee('Change: $20.01');
        });
    }

    /** @test */
    public function user_can_complete_card_sale()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->click('@product-' . $this->product->id)
                ->waitFor('@cart-item')
                ->click('@checkout')
                ->waitFor('@payment-modal')
                ->click('@payment-card')
                ->click('@complete-payment')
                ->pause(2000)
                ->assertSee('Payment Successful');
        });
    }

    /** @test */
    public function user_can_hold_order()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->click('@product-' . $this->product->id)
                ->waitFor('@cart-item')
                ->click('@hold-order')
                ->waitFor('@hold-modal')
                ->type('@order-reference', 'Table 5')
                ->click('@confirm-hold')
                ->pause(1000)
                ->assertSee('Order held successfully');
        });
    }

    /** @test */
    public function user_can_retrieve_held_order()
    {
        $heldOrder = \App\Models\HeldOrder::factory()->create([
            'user_id' => $this->user->id,
            'reference' => 'Table 5',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'price' => 29.99,
                ]
            ],
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->click('@held-orders')
                ->waitFor('@held-orders-modal')
                ->assertSee('Table 5')
                ->click('@retrieve-order-' . $heldOrder->id)
                ->pause(1000)
                ->assertSee($this->product->name);
        });
    }

    /** @test */
    public function user_can_clear_cart()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->click('@product-' . $this->product->id)
                ->waitFor('@cart-item')
                ->click('@clear-cart')
                ->acceptDialog()
                ->pause(500)
                ->assertDontSee($this->product->name);
        });
    }

    /** @test */
    public function pos_shows_low_stock_warning()
    {
        $lowStockProduct = Product::factory()->create([
            'name' => 'Low Stock Item',
            'stock' => 2,
            'low_stock_threshold' => 5,
        ]);

        $this->browse(function (Browser $browser) use ($lowStockProduct) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->click('@product-' . $lowStockProduct->id)
                ->waitFor('@cart-item')
                ->assertSee('Low Stock');
        });
    }

    /** @test */
    public function pos_prevents_overselling()
    {
        $limitedProduct = Product::factory()->create([
            'name' => 'Limited Item',
            'stock' => 1,
        ]);

        $this->browse(function (Browser $browser) use ($limitedProduct) {
            $browser->loginAs($this->user)
                ->visit('/pos')
                ->click('@product-' . $limitedProduct->id)
                ->waitFor('@cart-item')
                ->click('@quantity-increase')
                ->pause(500)
                ->assertSee('Insufficient stock');
        });
    }
}