# POS System - Feature Implementation Guide

## 4. Feature Implementation Plan

### Phase 1: Core Foundation (Week 1-2)

#### 1.1 Laravel Setup & Configuration
**Goal:** Set up Laravel project with basic structure

**Steps:**
1. Download Laravel 10 via Composer locally
2. Configure `.env` for Hostinger MySQL
3. Set up basic authentication with Laravel Breeze (no Node build)
4. Create database migrations for core tables
5. Seed initial data (roles, permissions, admin user)

**Files to Create:**
- [`config/pos.php`](config/pos.php) - POS configuration
- [`database/migrations/2024_01_01_000001_create_roles_table.php`](database/migrations/2024_01_01_000001_create_roles_table.php)
- [`database/migrations/2024_01_01_000002_create_permissions_table.php`](database/migrations/2024_01_01_000002_create_permissions_table.php)
- [`database/seeders/RolePermissionSeeder.php`](database/seeders/RolePermissionSeeder.php)

**Implementation Notes:**
```php
// config/pos.php
return [
    'currency' => 'USD',
    'currency_symbol' => '$',
    'tax_rate' => 0.00,
    'receipt_footer' => 'Thank you for your business!',
    'low_stock_threshold' => 10,
    'order_number_prefix' => 'POS-',
    'enable_offline_mode' => true,
    'sync_interval' => 300, // 5 minutes
];
```

#### 1.2 User Authentication & Roles
**Goal:** Implement role-based access control

**Steps:**
1. Create User, Role, Permission models
2. Implement role middleware
3. Create PIN login for quick cashier access
4. Build user management interface

**Livewire Components:**
```php
// app/Livewire/Auth/PinLogin.php
class PinLogin extends Component
{
    public $pin = '';
    
    public function login()
    {
        $user = User::where('pin', $this->pin)
            ->where('is_active', true)
            ->first();
            
        if ($user) {
            Auth::login($user);
            return redirect()->route('pos.terminal');
        }
        
        $this->addError('pin', 'Invalid PIN');
    }
}
```

**Permissions Structure:**
```
Cashier:
- pos.access
- pos.sell
- products.view
- customers.view

Manager:
- All Cashier permissions
- pos.refund
- pos.discount
- reports.view
- users.manage
- cash_drawer.manage

Storekeeper:
- products.manage
- inventory.manage
- inventory.adjust
- products.import
```

#### 1.3 Product Management
**Goal:** Create product catalog with variants

**Steps:**
1. Create Product, ProductVariant, ProductCategory models
2. Build product CRUD interface
3. Implement barcode association
4. Add product search functionality

**Livewire Component Example:**
```php
// app/Livewire/Products/ProductList.php
class ProductList extends Component
{
    public $search = '';
    public $category = '';
    
    public function render()
    {
        $products = Product::query()
            ->when($this->search, function($query) {
                $query->where('name', 'like', "%{$this->search}%")
                      ->orWhere('sku', 'like', "%{$this->search}%");
            })
            ->when($this->category, function($query) {
                $query->where('category_id', $this->category);
            })
            ->with(['category', 'inventory'])
            ->paginate(20);
            
        return view('livewire.products.product-list', [
            'products' => $products
        ]);
    }
}
```

### Phase 2: POS Terminal (Week 3-4)

#### 2.1 POS Interface
**Goal:** Build main point-of-sale screen

**Components:**
- Product search/scan
- Cart display
- Customer selection
- Quick actions (hold, clear, discount)

**Livewire Component:**
```php
// app/Livewire/Pos/PosTerminal.php
class PosTerminal extends Component
{
    public $cart = [];
    public $customer = null;
    public $search = '';
    public $barcode = '';
    
    protected $listeners = [
        'productScanned' => 'addToCart',
        'cartCleared' => 'clearCart',
    ];
    
    public function addToCart($productId, $quantity = 1)
    {
        $product = Product::with('inventory')->find($productId);
        
        if (!$product) {
            $this->dispatch('error', 'Product not found');
            return;
        }
        
        // Check inventory
        if ($product->track_inventory && 
            $product->inventory->quantity < $quantity) {
            $this->dispatch('error', 'Insufficient stock');
            return;
        }
        
        $cartItem = [
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'quantity' => $quantity,
            'tax_rate' => $product->tax_rate,
        ];
        
        // Check if item exists in cart
        $key = array_search($product->id, array_column($this->cart, 'product_id'));
        
        if ($key !== false) {
            $this->cart[$key]['quantity'] += $quantity;
        } else {
            $this->cart[] = $cartItem;
        }
        
        $this->dispatch('cartUpdated');
    }
    
    public function removeFromCart($index)
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->dispatch('cartUpdated');
    }
    
    public function updateQuantity($index, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeFromCart($index);
            return;
        }
        
        $this->cart[$index]['quantity'] = $quantity;
        $this->dispatch('cartUpdated');
    }
    
    public function getSubtotalProperty()
    {
        return collect($this->cart)->sum(function($item) {
            return $item['price'] * $item['quantity'];
        });
    }
    
    public function getTaxProperty()
    {
        return collect($this->cart)->sum(function($item) {
            $subtotal = $item['price'] * $item['quantity'];
            return $subtotal * ($item['tax_rate'] / 100);
        });
    }
    
    public function getTotalProperty()
    {
        return $this->subtotal + $this->tax;
    }
}
```

**Alpine.js for Barcode Scanner:**
```javascript
// resources/js/pos/barcode-listener.js
document.addEventListener('alpine:init', () => {
    Alpine.data('barcodeScanner', () => ({
        buffer: '',
        timeout: null,
        
        init() {
            document.addEventListener('keypress', (e) => {
                // Ignore if typing in input field
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    return;
                }
                
                clearTimeout(this.timeout);
                
                if (e.key === 'Enter') {
                    if (this.buffer.length > 0) {
                        this.processBarcode(this.buffer);
                        this.buffer = '';
                    }
                } else {
                    this.buffer += e.key;
                    
                    // Auto-process after 100ms of no input
                    this.timeout = setTimeout(() => {
                        if (this.buffer.length > 0) {
                            this.processBarcode(this.buffer);
                            this.buffer = '';
                        }
                    }, 100);
                }
            });
        },
        
        processBarcode(barcode) {
            // Find product by barcode
            fetch(`/api/products/by-barcode/${barcode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.product) {
                        Livewire.dispatch('productScanned', {
                            productId: data.product.id
                        });
                    } else {
                        alert('Product not found');
                    }
                });
        }
    }));
});
```

#### 2.2 Checkout Process
**Goal:** Handle payment and order completion

**Steps:**
1. Payment method selection
2. Split payment support
3. Change calculation
4. Order creation
5. Receipt generation

**Livewire Component:**
```php
// app/Livewire/Pos/Checkout.php
class Checkout extends Component
{
    public $cart;
    public $customer;
    public $payments = [];
    public $paymentMethod = 'cash';
    public $amountTendered = 0;
    
    public function addPayment()
    {
        $this->validate([
            'paymentMethod' => 'required',
            'amountTendered' => 'required|numeric|min:0.01',
        ]);
        
        $this->payments[] = [
            'method' => $this->paymentMethod,
            'amount' => $this->amountTendered,
        ];
        
        $this->reset(['paymentMethod', 'amountTendered']);
    }
    
    public function completeOrder()
    {
        $totalPaid = collect($this->payments)->sum('amount');
        $orderTotal = $this->calculateTotal();
        
        if ($totalPaid < $orderTotal) {
            $this->dispatch('error', 'Insufficient payment');
            return;
        }
        
        DB::beginTransaction();
        
        try {
            // Create order
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $this->customer?->id,
                'user_id' => auth()->id(),
                'status' => 'completed',
                'subtotal' => $this->calculateSubtotal(),
                'tax_amount' => $this->calculateTax(),
                'total' => $orderTotal,
                'payment_status' => 'paid',
            ]);
            
            // Create order items
            foreach ($this->cart as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'sku' => $item['sku'],
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'tax_rate' => $item['tax_rate'],
                    'subtotal' => $item['price'] * $item['quantity'],
                    'total' => $this->calculateItemTotal($item),
                ]);
                
                // Update inventory
                $product = Product::find($item['product_id']);
                if ($product->track_inventory) {
                    $product->inventory->decrement('quantity', $item['quantity']);
                    
                    // Record stock movement
                    StockMovement::create([
                        'inventoriable_type' => Product::class,
                        'inventoriable_id' => $product->id,
                        'type' => 'sale',
                        'quantity' => -$item['quantity'],
                        'reference_type' => Order::class,
                        'reference_id' => $order->id,
                        'user_id' => auth()->id(),
                    ]);
                }
            }
            
            // Create payments
            foreach ($this->payments as $payment) {
                $order->payments()->create([
                    'payment_method' => $payment['method'],
                    'amount' => $payment['amount'],
                ]);
            }
            
            // Queue for WooCommerce sync
            SyncQueue::create([
                'syncable_type' => Order::class,
                'syncable_id' => $order->id,
                'action' => 'create',
                'status' => 'pending',
            ]);
            
            DB::commit();
            
            // Generate receipt
            $receiptUrl = app(ReceiptService::class)->generate($order);
            
            return redirect()->route('pos.receipt', $order->id);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Order failed: ' . $e->getMessage());
        }
    }
    
    private function generateOrderNumber()
    {
        $prefix = config('pos.order_number_prefix', 'POS-');
        $date = now()->format('Ymd');
        $sequence = Order::whereDate('created_at', today())->count() + 1;
        
        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
```

#### 2.3 Hold & Resume Orders
**Goal:** Allow parking incomplete transactions

**Implementation:**
```php
// app/Livewire/Pos/HeldOrders.php
class HeldOrders extends Component
{
    public function holdOrder($cart, $customer = null)
    {
        HeldOrder::create([
            'user_id' => auth()->id(),
            'customer_id' => $customer?->id,
            'cart_data' => json_encode($cart),
            'notes' => '',
        ]);
        
        $this->dispatch('orderHeld');
    }
    
    public function resumeOrder($heldOrderId)
    {
        $heldOrder = HeldOrder::findOrFail($heldOrderId);
        $cart = json_decode($heldOrder->cart_data, true);
        
        $this->dispatch('orderResumed', [
            'cart' => $cart,
            'customer' => $heldOrder->customer,
        ]);
        
        $heldOrder->delete();
    }
}
```

### Phase 3: Inventory Management (Week 5)

#### 3.1 Stock Tracking
**Goal:** Real-time inventory updates

**Service Implementation:**
```php
// app/Services/InventoryService.php
class InventoryService
{
    public function adjustStock($inventoriable, $quantity, $type, $notes = null)
    {
        DB::beginTransaction();
        
        try {
            $inventory = $inventoriable->inventory;
            
            if (!$inventory) {
                $inventory = Inventory::create([
                    'inventoriable_type' => get_class($inventoriable),
                    'inventoriable_id' => $inventoriable->id,
                    'quantity' => 0,
                ]);
            }
            
            $inventory->increment('quantity', $quantity);
            
            StockMovement::create([
                'inventoriable_type' => get_class($inventoriable),
                'inventoriable_id' => $inventoriable->id,
                'type' => $type,
                'quantity' => $quantity,
                'notes' => $notes,
                'user_id' => auth()->id(),
            ]);
            
            // Check low stock
            if ($inventory->quantity <= $inventory->low_stock_threshold) {
                event(new LowStockAlert($inventoriable, $inventory));
            }
            
            DB::commit();
            
            return $inventory;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function getStockLevel($inventoriable)
    {
        return $inventoriable->inventory?->quantity ?? 0;
    }
    
    public function isInStock($inventoriable, $quantity = 1)
    {
        $available = $this->getStockLevel($inventoriable);
        return $available >= $quantity;
    }
}
```

### Phase 4: Customer Management (Week 6)

#### 4.1 Customer Profiles
**Goal:** Store customer information

**Implementation:**
```php
// app/Services/CustomerService.php
class CustomerService
{
    public function createOrUpdate($data)
    {
        $customer = Customer::updateOrCreate(
            ['email' => $data['email']],
            [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]
        );
        
        return $customer;
    }
    
    public function getPurchaseHistory($customer)
    {
        return Order::where('customer_id', $customer->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
```

### Phase 5: Reporting (Week 7)

#### 5.1 Sales Reports
**Goal:** Generate business insights

**Livewire Component:**
```php
// app/Livewire/Reports/SalesSummary.php
class SalesSummary extends Component
{
    public $startDate;
    public $endDate;
    
    public function mount()
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }
    
    public function render()
    {
        $sales = Order::whereBetween('created_at', [
                $this->startDate,
                $this->endDate . ' 23:59:59'
            ])
            ->where('status', 'completed')
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as order_count,
                SUM(total) as total
            ')
            ->groupBy('date')
            ->get();
            
        return view('livewire.reports.sales-summary', [
            'sales' => $sales,
            'totalRevenue' => $sales->sum('total'),
        ]);
    }
}