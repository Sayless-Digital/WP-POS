<div class="h-screen flex flex-col bg-gray-100" x-data="posTerminal()" @keydown.window="handleKeyboard($event)">
    <!-- Top Bar -->
    <div class="bg-white shadow-sm border-b border-gray-200 px-4 py-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h1 class="text-2xl font-bold text-gray-800">POS Terminal</h1>
                <span class="text-sm text-gray-500">{{ auth()->user()->name }}</span>
            </div>
            
            <div class="flex items-center space-x-2">
                <!-- Customer Info -->
                @if($customer)
                    <div class="flex items-center space-x-2 bg-blue-50 px-3 py-2 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">{{ $customer->first_name }} {{ $customer->last_name }}</span>
                        <button wire:click="selectCustomer(null)" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @else
                    <button wire:click="openCustomerModal" class="flex items-center space-x-2 bg-gray-100 hover:bg-gray-200 px-3 py-2 rounded-lg transition">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="text-sm font-medium text-gray-700">Select Customer</span>
                    </button>
                @endif
                
                <!-- Quick Actions -->
                <button wire:click="openHeldOrdersModal" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Held Orders (F2)">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>
                
                <button wire:click="clearCart" wire:confirm="Are you sure you want to clear the cart?" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition" title="Clear Cart (F3)">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Left Side - Product Search & Selection -->
        <div class="flex-1 flex flex-col bg-white border-r border-gray-200">
            <!-- Search Bar -->
            <div class="p-4 border-b border-gray-200">
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="Search products or scan barcode (F1)..."
                        class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        autofocus
                        x-ref="searchInput"
                    >
                    <svg class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    
                    @if($searchQuery)
                        <button wire:click="$set('searchQuery', '')" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
                
                <!-- Search Results Dropdown -->
                @if(!empty($searchResults))
                    <div class="absolute z-10 mt-2 w-full max-w-2xl bg-white border border-gray-200 rounded-lg shadow-lg max-h-96 overflow-y-auto">
                        @foreach($searchResults as $result)
                            <button 
                                wire:click="addToCart({{ json_encode($result) }})"
                                class="w-full flex items-center justify-between p-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0 text-left"
                            >
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">{{ $result['name'] }}</div>
                                    <div class="text-sm text-gray-500">SKU: {{ $result['sku'] }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900">${{ number_format($result['price'], 2) }}</div>
                                    <div class="text-sm text-gray-500">Stock: {{ $result['stock'] ?? 'N/A' }}</div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Product Grid/Categories (Placeholder) -->
            <div class="flex-1 overflow-y-auto p-4">
                <div class="text-center text-gray-500 py-12">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <p class="text-lg font-medium">Search for products above</p>
                    <p class="text-sm mt-1">or scan a barcode to add items to cart</p>
                </div>
            </div>
        </div>

        <!-- Right Side - Cart -->
        <div class="w-96 flex flex-col bg-gray-50">
            <!-- Cart Header -->
            <div class="bg-white border-b border-gray-200 px-4 py-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800">Cart</h2>
                    <span class="text-sm text-gray-500">{{ $this->cartSummary['items_count'] }} items</span>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                @forelse($cart as $index => $item)
                    <div class="bg-white rounded-lg p-3 shadow-sm border border-gray-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-medium text-gray-900 truncate">{{ $item['name'] }}</h3>
                                <p class="text-sm text-gray-500">{{ $item['sku'] }}</p>
                                
                                <!-- Quantity Controls -->
                                <div class="flex items-center space-x-2 mt-2">
                                    <button 
                                        wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                        class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                        </svg>
                                    </button>
                                    
                                    <input 
                                        type="number" 
                                        wire:change="updateQuantity({{ $index }}, $event.target.value)"
                                        value="{{ $item['quantity'] }}"
                                        class="w-16 text-center border border-gray-300 rounded py-1"
                                        min="1"
                                    >
                                    
                                    <button 
                                        wire:click="updateQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                        class="w-8 h-8 flex items-center justify-center bg-gray-100 hover:bg-gray-200 rounded transition"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                        </svg>
                                    </button>
                                    
                                    <button 
                                        wire:click="removeFromCart({{ $index }})"
                                        class="ml-auto text-red-600 hover:text-red-700"
                                        title="Remove item"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="text-right ml-3">
                                <div class="font-semibold text-gray-900">${{ number_format($item['price'] * $item['quantity'], 2) }}</div>
                                <div class="text-sm text-gray-500">${{ number_format($item['price'], 2) }} each</div>
                            </div>
                        </div>
                        
                        @if(isset($item['discount']) && $item['discount'] > 0)
                            <div class="mt-2 text-sm text-green-600">
                                Discount: -${{ number_format($item['discount'], 2) }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="text-center text-gray-400 py-12">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="font-medium">Cart is empty</p>
                        <p class="text-sm mt-1">Add products to get started</p>
                    </div>
                @endforelse
            </div>

            <!-- Cart Summary -->
            <div class="bg-white border-t border-gray-200 p-4 space-y-3">
                <!-- Subtotal -->
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal</span>
                    <span class="font-medium">${{ number_format($this->cartSummary['subtotal'], 2) }}</span>
                </div>
                
                <!-- Discount -->
                @if($this->cartSummary['discount'] > 0)
                    <div class="flex justify-between text-sm text-green-600">
                        <span>Discount</span>
                        <span>-${{ number_format($this->cartSummary['discount'], 2) }}</span>
                    </div>
                @endif
                
                <!-- Tax -->
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Tax</span>
                    <span class="font-medium">${{ number_format($this->cartSummary['tax'], 2) }}</span>
                </div>
                
                <div class="border-t border-gray-200 pt-3">
                    <div class="flex justify-between text-lg font-bold">
                        <span>Total</span>
                        <span class="text-blue-600">${{ number_format($this->cartSummary['total'], 2) }}</span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="space-y-2 pt-2">
                    <button
                        wire:click="openDiscountModal"
                        class="w-full py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition"
                        @disabled(empty($cart))
                    >
                        Apply Discount
                    </button>
                    
                    <button 
                        wire:click="holdOrder"
                        class="w-full py-2 px-4 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition"
                        @disabled(empty($cart))
                    >
                        Hold Order (F2)
                    </button>
                    
                    <button 
                        wire:click="proceedToCheckout"
                        class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition text-lg"
                        @disabled(empty($cart))
                    >
                        Checkout (F12)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div wire:loading class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700 font-medium">Processing...</span>
        </div>
    </div>

    <!-- Modals -->
    @if($showCustomerModal)
        <livewire:pos.customer-search-modal />
    @endif

    @if($showDiscountModal)
        <livewire:pos.discount-modal />
    @endif

    @if($showHeldOrdersModal)
        <livewire:pos.held-orders-modal />
    @endif
</div>

@push('scripts')
<script>
function posTerminal() {
    return {
        handleKeyboard(event) {
            // F1 - Focus search
            if (event.key === 'F1') {
                event.preventDefault();
                this.$refs.searchInput.focus();
            }
            
            // F2 - Hold order
            if (event.key === 'F2') {
                event.preventDefault();
                @this.holdOrder();
            }
            
            // F3 - Clear cart
            if (event.key === 'F3') {
                event.preventDefault();
                if (confirm('Are you sure you want to clear the cart?')) {
                    @this.clearCart();
                }
            }
            
            // F4 - Customer lookup
            if (event.key === 'F4') {
                event.preventDefault();
                @this.openCustomerModal();
            }
            
            // F12 - Checkout
            if (event.key === 'F12') {
                event.preventDefault();
                @this.proceedToCheckout();
            }
            
            // ESC - Clear search
            if (event.key === 'Escape') {
                @this.set('searchQuery', '');
            }
        }
    }
}

// Listen for barcode scanner input
let barcodeBuffer = '';
let barcodeTimeout;

document.addEventListener('keypress', (e) => {
    // Ignore if typing in an input field
    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
        return;
    }
    
    // Scanner typically sends Enter after barcode
    if (e.key === 'Enter' && barcodeBuffer.length > 0) {
        Livewire.dispatch('barcode-scanned', { barcode: barcodeBuffer });
        barcodeBuffer = '';
    } else {
        barcodeBuffer += e.key;
        clearTimeout(barcodeTimeout);
        barcodeTimeout = setTimeout(() => {
            barcodeBuffer = '';
        }, 100);
    }
});

// Toast notifications
window.addEventListener('barcode-success', event => {
    // Show success toast
    console.log('Success:', event.detail.message);
});

window.addEventListener('barcode-error', event => {
    // Show error toast
    console.log('Error:', event.detail.message);
});

window.addEventListener('error', event => {
    // Show error toast
    alert(event.detail.message);
});

window.addEventListener('item-added', event => {
    // Show success toast
    console.log('Success:', event.detail.message);
});
</script>
@endpush