<div class="min-h-screen bg-gray-100">
    @if($orderCompleted)
        <!-- Order Completed Screen -->
        <div class="max-w-2xl mx-auto py-12 px-4">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <!-- Success Icon -->
                <div class="mb-6">
                    <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Completed!</h1>
                <p class="text-gray-600 mb-2">Order #{{ $completedOrderId }}</p>
                
                @if($changeAmount > 0)
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <p class="text-sm text-gray-600">Change Due</p>
                        <p class="text-3xl font-bold text-blue-600">${{ number_format($changeAmount, 2) }}</p>
                    </div>
                @endif
                
                <!-- Action Buttons -->
                <div class="space-y-3">
                    <button 
                        wire:click="printReceipt"
                        class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                    >
                        <div class="flex items-center justify-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            <span>Print Receipt</span>
                        </div>
                    </button>
                    
                    @if($customer && $customer->email)
                        <button 
                            wire:click="emailReceipt"
                            class="w-full py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition"
                        >
                            <div class="flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span>Email Receipt</span>
                            </div>
                        </button>
                    @endif
                    
                    <button 
                        wire:click="newTransaction"
                        class="w-full py-3 px-4 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg transition"
                    >
                        New Transaction
                    </button>
                </div>
            </div>
        </div>
    @else
        <!-- Checkout Screen -->
        <div class="max-w-6xl mx-auto py-6 px-4">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm mb-6 p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Checkout</h1>
                        <p class="text-sm text-gray-500">Complete the transaction</p>
                    </div>
                    <button 
                        wire:click="cancelCheckout"
                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition"
                    >
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                            <span>Back to POS</span>
                        </div>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Order Summary -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Customer Info -->
                    @if($customer)
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Customer</h2>
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $customer->first_name }} {{ $customer->last_name }}</p>
                                    <p class="text-sm text-gray-500">{{ $customer->email ?? 'No email' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Cart Items -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Order Items</h2>
                        <div class="space-y-3">
                            @foreach($cart as $item)
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">{{ $item['name'] }}</p>
                                        <p class="text-sm text-gray-500">{{ $item['sku'] }} × {{ $item['quantity'] }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900">${{ number_format($item['price'] * $item['quantity'], 2) }}</p>
                                        <p class="text-sm text-gray-500">${{ number_format($item['price'], 2) }} each</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Method</h2>
                        
                        <!-- Payment Method Selection -->
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <button 
                                wire:click="$set('selectedPaymentMethod', 'cash')"
                                class="p-4 border-2 rounded-lg transition {{ $selectedPaymentMethod === 'cash' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}"
                            >
                                <svg class="w-8 h-8 mx-auto mb-2 {{ $selectedPaymentMethod === 'cash' ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="text-sm font-medium {{ $selectedPaymentMethod === 'cash' ? 'text-blue-600' : 'text-gray-600' }}">Cash</p>
                            </button>
                            
                            <button 
                                wire:click="$set('selectedPaymentMethod', 'card')"
                                class="p-4 border-2 rounded-lg transition {{ $selectedPaymentMethod === 'card' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}"
                            >
                                <svg class="w-8 h-8 mx-auto mb-2 {{ $selectedPaymentMethod === 'card' ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                <p class="text-sm font-medium {{ $selectedPaymentMethod === 'card' ? 'text-blue-600' : 'text-gray-600' }}">Card</p>
                            </button>
                            
                            <button 
                                wire:click="$set('selectedPaymentMethod', 'mobile')"
                                class="p-4 border-2 rounded-lg transition {{ $selectedPaymentMethod === 'mobile' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}"
                            >
                                <svg class="w-8 h-8 mx-auto mb-2 {{ $selectedPaymentMethod === 'mobile' ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-sm font-medium {{ $selectedPaymentMethod === 'mobile' ? 'text-blue-600' : 'text-gray-600' }}">Mobile</p>
                            </button>
                        </div>

                        <!-- Cash Tendered Input -->
                        @if($selectedPaymentMethod === 'cash')
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cash Tendered</label>
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-gray-500">$</span>
                                    <input 
                                        type="number" 
                                        wire:model.live="cashTendered"
                                        step="0.01"
                                        min="0"
                                        class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-lg font-semibold"
                                        autofocus
                                    >
                                </div>
                                
                                <!-- Quick Amount Buttons -->
                                <div class="grid grid-cols-4 gap-2 mt-3">
                                    @foreach([20, 50, 100, 200] as $amount)
                                        <button 
                                            wire:click="$set('cashTendered', {{ $amount }})"
                                            class="py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded transition text-sm"
                                        >
                                            ${{ $amount }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Change Display -->
                            @if($changeAmount > 0)
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                    <p class="text-sm text-gray-600 mb-1">Change Due</p>
                                    <p class="text-3xl font-bold text-green-600">${{ number_format($changeAmount, 2) }}</p>
                                </div>
                            @endif
                        @endif

                        <!-- Split Payment Section -->
                        @if(!empty($payments))
                            <div class="mt-4 border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-semibold text-gray-700 mb-2">Split Payments</h3>
                                <div class="space-y-2">
                                    @foreach($payments as $index => $payment)
                                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                                            <div>
                                                <p class="font-medium text-gray-900 capitalize">{{ $payment['method'] }}</p>
                                                <p class="text-sm text-gray-500">${{ number_format($payment['amount'], 2) }}</p>
                                            </div>
                                            <button 
                                                wire:click="removePayment({{ $index }})"
                                                class="text-red-600 hover:text-red-700"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Add Payment Button (for split payment) -->
                        @if($remainingBalance > 0 && !empty($payments))
                            <button 
                                wire:click="addPayment"
                                class="w-full mt-4 py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition"
                            >
                                Add Payment (${{{ number_format($remainingBalance, 2) }} remaining)
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Right Column - Summary & Actions -->
                <div class="space-y-6">
                    <!-- Order Summary -->
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium">${{ number_format($this->cartSummary['subtotal'], 2) }}</span>
                            </div>
                            
                            @if($this->cartSummary['discount'] > 0)
                                <div class="flex justify-between text-sm text-green-600">
                                    <span>Discount</span>
                                    <span>-${{ number_format($this->cartSummary['discount'], 2) }}</span>
                                </div>
                            @endif
                            
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tax</span>
                                <span class="font-medium">${{ number_format($this->cartSummary['tax'], 2) }}</span>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-3">
                                <div class="flex justify-between">
                                    <span class="text-lg font-bold text-gray-900">Total</span>
                                    <span class="text-2xl font-bold text-blue-600">${{ number_format($this->cartSummary['total'], 2) }}</span>
                                </div>
                            </div>

                            @if(!empty($payments))
                                <div class="border-t border-gray-200 pt-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Paid</span>
                                        <span class="font-medium text-green-600">${{ number_format($this->getTotalPaid(), 2) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm mt-2">
                                        <span class="text-gray-600">Remaining</span>
                                        <span class="font-medium text-orange-600">${{ number_format($remainingBalance, 2) }}</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Complete Order Button -->
                    <button 
                        wire:click="completeOrder"
                        wire:loading.attr="disabled"
                        class="w-full py-4 px-4 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white font-bold rounded-lg transition text-lg"
                        @disabled($processing || ($selectedPaymentMethod === 'cash' && $cashTendered < $this->cartSummary['total']))
                    >
                        <span wire:loading.remove wire:target="completeOrder">
                            Complete Order
                        </span>
                        <span wire:loading wire:target="completeOrder">
                            Processing...
                        </span>
                    </button>

                    @if($notes)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-sm font-medium text-gray-700 mb-1">Order Notes</p>
                            <p class="text-sm text-gray-600">{{ $notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Loading Overlay -->
    <div wire:loading.flex wire:target="completeOrder" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-700 font-medium">Processing order...</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Listen for print receipt event
window.addEventListener('print-receipt', event => {
    window.print();
});

// Listen for success/error events
window.addEventListener('success', event => {
    // Show success notification
    alert(event.detail.message);
});

window.addEventListener('error', event => {
    // Show error notification
    alert(event.detail.message);
});
</script>
@endpush