<div>
    {{-- Modal Header --}}
    <div class="bg-white px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Purchase History</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $customer->full_name }}</p>
            </div>
            <button 
                wire:click="$parent.togglePurchaseHistory"
                class="text-gray-400 hover:text-gray-600 transition-colors"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Statistics Bar --}}
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
        <div class="grid grid-cols-4 gap-4">
            <div class="text-center">
                <p class="text-sm text-gray-600">Total Orders</p>
                <p class="text-xl font-bold text-gray-900">{{ $statistics['total_orders'] }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Total Spent</p>
                <p class="text-xl font-bold text-green-600">${{ number_format($statistics['total_spent'], 2) }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Average Order</p>
                <p class="text-xl font-bold text-purple-600">${{ number_format($statistics['average_order'], 2) }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Completed</p>
                <p class="text-xl font-bold text-blue-600">{{ $statistics['completed'] }}</p>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white px-6 py-4 border-b border-gray-200">
        <div class="grid grid-cols-4 gap-4">
            {{-- Search --}}
            <div>
                <input 
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search orders..."
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>

            {{-- Status Filter --}}
            <div>
                <select 
                    wire:model.live="statusFilter"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            {{-- Date From --}}
            <div>
                <input 
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>

            {{-- Date To --}}
            <div>
                <input 
                    type="date"
                    wire:model.live="dateTo"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
            </div>
        </div>

        {{-- Filter Actions --}}
        <div class="mt-3 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing {{ $orders->count() }} of {{ $orders->total() }} orders
            </div>
            <div class="flex items-center space-x-2">
                @if($search || $statusFilter || $dateFrom || $dateTo)
                    <button 
                        wire:click="clearFilters"
                        class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 font-medium transition-colors"
                    >
                        Clear Filters
                    </button>
                @endif
                <button 
                    wire:click="exportOrders"
                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded transition-colors"
                >
                    Export
                </button>
            </div>
        </div>
    </div>

    {{-- Orders List --}}
    <div class="bg-white px-6 py-4 max-h-96 overflow-y-auto">
        @if($orders->isEmpty())
            <div class="text-center py-8 text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <p>No orders found</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach($orders as $order)
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors cursor-pointer" wire:click="viewOrder({{ $order->id }})">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Order #{{ $order->order_number }}</p>
                                    <p class="text-xs text-gray-500">{{ $order->created_at->format('M d, Y g:i A') }}</p>
                                </div>
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium 
                                    {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $order->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                ">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-gray-900">${{ number_format($order->total, 2) }}</p>
                                <p class="text-xs text-gray-500">{{ $order->items->count() }} item(s)</p>
                            </div>
                        </div>

                        {{-- Order Items Preview --}}
                        <div class="mt-2 flex items-center justify-between text-xs text-gray-500">
                            <div class="flex items-center space-x-2">
                                @foreach($order->items->take(3) as $item)
                                    <span class="px-2 py-1 bg-gray-100 rounded">
                                        {{ $item->product->name }} ({{ $item->quantity }})
                                    </span>
                                @endforeach
                                @if($order->items->count() > 3)
                                    <span class="text-gray-400">+{{ $order->items->count() - 3 }} more</span>
                                @endif
                            </div>
                            <span>{{ $order->payments->first()?->payment_method ?? 'N/A' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    {{-- Order Details Modal --}}
    @if($showOrderDetails && $selectedOrder)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeOrderDetails"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                    {{-- Order Details Header --}}
                    <div class="bg-white px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Order #{{ $selectedOrder->order_number }}</h3>
                                <p class="text-sm text-gray-500">{{ $selectedOrder->created_at->format('F d, Y g:i A') }}</p>
                            </div>
                            <button 
                                wire:click="closeOrderDetails"
                                class="text-gray-400 hover:text-gray-600 transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Order Details Content --}}
                    <div class="bg-white px-6 py-4 max-h-96 overflow-y-auto">
                        {{-- Order Status --}}
                        <div class="mb-4">
                            <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium 
                                {{ $selectedOrder->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $selectedOrder->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $selectedOrder->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                            ">
                                {{ ucfirst($selectedOrder->status) }}
                            </span>
                        </div>

                        {{-- Order Items --}}
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Order Items</h4>
                            <div class="space-y-2">
                                @foreach($selectedOrder->items as $item)
                                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-900">{{ $item->product->name }}</p>
                                            @if($item->variant)
                                                <p class="text-xs text-gray-500">{{ $item->variant->name }}</p>
                                            @endif
                                            <p class="text-xs text-gray-500">Qty: {{ $item->quantity }} × ${{ number_format($item->price, 2) }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-semibold text-gray-900">${{ number_format($item->subtotal, 2) }}</p>
                                            @if($item->discount > 0)
                                                <p class="text-xs text-green-600">-${{ number_format($item->discount, 2) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Order Totals --}}
                        <div class="mb-6 bg-gray-50 rounded-lg p-4">
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-medium text-gray-900">${{ number_format($selectedOrder->subtotal, 2) }}</span>
                                </div>
                                @if($selectedOrder->discount > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Discount:</span>
                                        <span class="font-medium text-green-600">-${{ number_format($selectedOrder->discount, 2) }}</span>
                                    </div>
                                @endif
                                @if($selectedOrder->tax > 0)
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Tax:</span>
                                        <span class="font-medium text-gray-900">${{ number_format($selectedOrder->tax, 2) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between text-base font-bold pt-2 border-t border-gray-200">
                                    <span class="text-gray-900">Total:</span>
                                    <span class="text-gray-900">${{ number_format($selectedOrder->total, 2) }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Payment Information --}}
                        @if($selectedOrder->payments->isNotEmpty())
                            <div class="mb-6">
                                <h4 class="text-sm font-semibold text-gray-900 mb-3">Payment Information</h4>
                                @foreach($selectedOrder->payments as $payment)
                                    <div class="flex items-center justify-between py-2 bg-gray-50 rounded px-3">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ ucfirst($payment->payment_method) }}</p>
                                            <p class="text-xs text-gray-500">{{ $payment->created_at->format('M d, Y g:i A') }}</p>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-900">${{ number_format($payment->amount, 2) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Refunds --}}
                        @if($selectedOrder->refunds->isNotEmpty())
                            <div class="mb-6">
                                <h4 class="text-sm font-semibold text-gray-900 mb-3">Refunds</h4>
                                @foreach($selectedOrder->refunds as $refund)
                                    <div class="flex items-center justify-between py-2 bg-red-50 rounded px-3">
                                        <div>
                                            <p class="text-sm font-medium text-red-900">Refund</p>
                                            <p class="text-xs text-red-600">{{ $refund->reason }}</p>
                                            <p class="text-xs text-gray-500">{{ $refund->created_at->format('M d, Y g:i A') }}</p>
                                        </div>
                                        <p class="text-sm font-semibold text-red-900">-${{ number_format($refund->amount, 2) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Notes --}}
                        @if($selectedOrder->notes)
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-2">Notes</h4>
                                <p class="text-sm text-gray-700 bg-gray-50 rounded p-3">{{ $selectedOrder->notes }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Order Details Footer --}}
                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-end">
                        <button 
                            wire:click="closeOrderDetails"
                            class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>