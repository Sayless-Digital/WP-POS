<div>
    @if($show)
    <!-- Modal Overlay -->
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                            <h3 class="text-xl font-semibold text-white">
                                Held Orders
                            </h3>
                            <span class="px-3 py-1 text-sm font-medium text-blue-600 bg-white rounded-full">
                                {{ $this->heldOrders->count() }} Orders
                            </span>
                        </div>
                        <button wire:click="closeModal" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Search and Filter Bar -->
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Search Input -->
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.300ms="searchQuery"
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Search by reference, customer, or notes..."
                                >
                                @if($searchQuery)
                                <button 
                                    wire:click="$set('searchQuery', '')"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </div>

                        <!-- Filter Buttons -->
                        <div class="flex gap-2">
                            <button 
                                wire:click="$set('filterBy', 'all')"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $filterBy === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300' }}"
                            >
                                All Orders
                            </button>
                            <button 
                                wire:click="$set('filterBy', 'mine')"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $filterBy === 'mine' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300' }}"
                            >
                                My Orders
                            </button>
                            <button 
                                wire:click="$set('filterBy', 'today')"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $filterBy === 'today' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300' }}"
                            >
                                Today
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content Area -->
                <div class="flex" style="height: 500px;">
                    <!-- Orders List (Left Side) -->
                    <div class="w-1/2 border-r border-gray-200 overflow-y-auto">
                        @if($this->heldOrders->isEmpty())
                            <!-- Empty State -->
                            <div class="flex flex-col items-center justify-center h-full text-gray-500 p-8">
                                <svg class="w-16 h-16 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                                <p class="text-lg font-medium mb-2">No Held Orders</p>
                                <p class="text-sm text-center">
                                    @if($searchQuery)
                                        No orders match your search criteria
                                    @else
                                        There are no held orders at the moment
                                    @endif
                                </p>
                            </div>
                        @else
                            <!-- Orders List -->
                            <div class="divide-y divide-gray-200">
                                @foreach($this->heldOrders as $order)
                                <div 
                                    wire:click="selectOrder({{ $order->id }})"
                                    class="p-4 hover:bg-gray-50 cursor-pointer transition-colors {{ $selectedOrderId === $order->id ? 'bg-blue-50 border-l-4 border-blue-600' : '' }}"
                                >
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-sm font-semibold text-gray-900">{{ $order->reference }}</span>
                                                @if($order->user_id === auth()->id())
                                                <span class="px-2 py-0.5 text-xs font-medium text-blue-600 bg-blue-100 rounded">Mine</span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-500">{{ $getTimeAgo($order->created_at) }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-bold text-gray-900">{{ $formatCurrency($order->total) }}</p>
                                            <p class="text-xs text-gray-500">{{ $getOrderItemsCount($order->items) }} items</p>
                                        </div>
                                    </div>
                                    
                                    @if($order->customer)
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-medium">
                                            {{ substr($order->customer->name, 0, 1) }}
                                        </div>
                                        <span class="text-sm text-gray-700">{{ $order->customer->name }}</span>
                                    </div>
                                    @endif
                                    
                                    @if($order->notes)
                                    <p class="text-xs text-gray-600 line-clamp-2">{{ $order->notes }}</p>
                                    @endif
                                    
                                    <div class="flex items-center gap-2 mt-2 text-xs text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <span>{{ $order->user->name }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Order Preview (Right Side) -->
                    <div class="w-1/2 overflow-y-auto bg-gray-50">
                        @if($this->selectedOrder)
                            <div class="p-6">
                                <!-- Order Header -->
                                <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <h4 class="text-lg font-semibold text-gray-900">{{ $this->selectedOrder->reference }}</h4>
                                            <p class="text-sm text-gray-500">{{ $this->selectedOrder->created_at->format('M d, Y h:i A') }}</p>
                                        </div>
                                        <span class="px-3 py-1 text-sm font-medium text-blue-600 bg-blue-100 rounded-full">
                                            Held
                                        </span>
                                    </div>

                                    <!-- Customer Info -->
                                    @if($this->selectedOrder->customer)
                                    <div class="border-t border-gray-200 pt-4">
                                        <p class="text-xs font-medium text-gray-500 uppercase mb-2">Customer</p>
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-medium">
                                                {{ substr($this->selectedOrder->customer->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900">{{ $this->selectedOrder->customer->name }}</p>
                                                @if($this->selectedOrder->customer->email)
                                                <p class="text-xs text-gray-500">{{ $this->selectedOrder->customer->email }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <!-- Held By -->
                                    <div class="border-t border-gray-200 pt-4 mt-4">
                                        <p class="text-xs font-medium text-gray-500 uppercase mb-2">Held By</p>
                                        <p class="text-sm text-gray-900">{{ $this->selectedOrder->user->name }}</p>
                                    </div>

                                    <!-- Notes -->
                                    @if($this->selectedOrder->notes)
                                    <div class="border-t border-gray-200 pt-4 mt-4">
                                        <p class="text-xs font-medium text-gray-500 uppercase mb-2">Notes</p>
                                        <p class="text-sm text-gray-700">{{ $this->selectedOrder->notes }}</p>
                                    </div>
                                    @endif
                                </div>

                                <!-- Order Items -->
                                <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                                    <h5 class="text-sm font-semibold text-gray-900 mb-3">Order Items</h5>
                                    <div class="space-y-3">
                                        @foreach($this->selectedOrder->items as $item)
                                        <div class="flex items-start justify-between py-2 border-b border-gray-100 last:border-0">
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900">{{ $item['name'] }}</p>
                                                <p class="text-xs text-gray-500">{{ $item['sku'] }}</p>
                                                <p class="text-xs text-gray-600 mt-1">
                                                    {{ $item['quantity'] }} × {{ $formatCurrency($item['price']) }}
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-gray-900">
                                                    {{ $formatCurrency($item['subtotal']) }}
                                                </p>
                                                @if(isset($item['discount_amount']) && $item['discount_amount'] > 0)
                                                <p class="text-xs text-green-600">
                                                    -{{ $formatCurrency($item['discount_amount']) }}
                                                </p>
                                                @endif
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Order Summary -->
                                <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
                                    <h5 class="text-sm font-semibold text-gray-900 mb-3">Order Summary</h5>
                                    <div class="space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Subtotal</span>
                                            <span class="text-gray-900">{{ $formatCurrency($this->selectedOrder->subtotal) }}</span>
                                        </div>
                                        @if($this->selectedOrder->discount_amount > 0)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Discount</span>
                                            <span class="text-green-600">-{{ $formatCurrency($this->selectedOrder->discount_amount) }}</span>
                                        </div>
                                        @endif
                                        @if($this->selectedOrder->tax_amount > 0)
                                        <div class="flex justify-between text-sm">
                                            <span class="text-gray-600">Tax</span>
                                            <span class="text-gray-900">{{ $formatCurrency($this->selectedOrder->tax_amount) }}</span>
                                        </div>
                                        @endif
                                        <div class="flex justify-between text-base font-semibold pt-2 border-t border-gray-200">
                                            <span class="text-gray-900">Total</span>
                                            <span class="text-gray-900">{{ $formatCurrency($this->selectedOrder->total) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex gap-3">
                                    <button 
                                        wire:click="resumeOrder({{ $this->selectedOrder->id }})"
                                        wire:loading.attr="disabled"
                                        class="flex-1 bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <span wire:loading.remove wire:target="resumeOrder({{ $this->selectedOrder->id }})">
                                            Resume Order
                                        </span>
                                        <span wire:loading wire:target="resumeOrder({{ $this->selectedOrder->id }})">
                                            Resuming...
                                        </span>
                                    </button>
                                    <button 
                                        wire:click="deleteOrder({{ $this->selectedOrder->id }})"
                                        wire:confirm="Are you sure you want to delete this held order?"
                                        wire:loading.attr="disabled"
                                        class="px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @else
                            <!-- No Selection State -->
                            <div class="flex flex-col items-center justify-center h-full text-gray-500 p-8">
                                <svg class="w-16 h-16 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <p class="text-lg font-medium mb-2">Select an Order</p>
                                <p class="text-sm text-center">
                                    Click on an order from the list to view details
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            Showing {{ $this->heldOrders->count() }} held order(s)
                        </div>
                        <div class="flex gap-3">
                            @if(auth()->user()->hasRole('admin') && $this->heldOrders->isNotEmpty())
                            <button 
                                wire:click="deleteAllOrders"
                                wire:confirm="Are you sure you want to delete ALL held orders? This action cannot be undone."
                                class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700 transition-colors"
                            >
                                Delete All
                            </button>
                            @endif
                            <button 
                                wire:click="closeModal"
                                class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>