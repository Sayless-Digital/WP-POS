<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-start">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('orders.index') }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Order {{ $order->order_number }}</h2>
                    <p class="mt-1 text-sm text-gray-600">Created {{ $order->created_at->format('M d, Y \a\t H:i') }}</p>
                </div>
            </div>
        </div>
        <div class="flex gap-2">
            <button wire:click="printOrder" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print
            </button>
            <button wire:click="duplicateOrder" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                Reorder
            </button>
            @if($order->status !== 'cancelled' && $order->status !== 'refunded')
                <button wire:click="openStatusModal" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Update Status
                </button>
            @endif
            @if($order->status !== 'cancelled' && $order->status !== 'refunded')
                <button wire:click="openCancelModal" class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cancel Order
                </button>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Order Items --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Order Items</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Discount</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tax</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $item->name }}</div>
                                        @if($item->variant)
                                            <div class="text-xs text-gray-500">{{ $item->variant->name }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $item->sku }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">${{ number_format($item->price, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">{{ $item->quantity }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">
                                        @if($item->discount_amount > 0)
                                            <span class="text-red-600">-${{ number_format($item->discount_amount, 2) }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900 text-right">
                                        ${{ number_format($item->tax_amount, 2) }}
                                        @if($item->tax_rate > 0)
                                            <span class="text-xs text-gray-500">({{ $item->tax_rate }}%)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 text-right">${{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="6" class="px-6 py-3 text-right text-sm font-medium text-gray-700">Subtotal:</td>
                                <td class="px-6 py-3 text-right text-sm font-medium text-gray-900">${{ number_format($order->subtotal, 2) }}</td>
                            </tr>
                            @if($order->discount_amount > 0)
                                <tr>
                                    <td colspan="6" class="px-6 py-3 text-right text-sm font-medium text-gray-700">Discount:</td>
                                    <td class="px-6 py-3 text-right text-sm font-medium text-red-600">-${{ number_format($order->discount_amount, 2) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="6" class="px-6 py-3 text-right text-sm font-medium text-gray-700">Tax:</td>
                                <td class="px-6 py-3 text-right text-sm font-medium text-gray-900">${{ number_format($order->tax_amount, 2) }}</td>
                            </tr>
                            <tr class="border-t-2 border-gray-300">
                                <td colspan="6" class="px-6 py-4 text-right text-base font-bold text-gray-900">Total:</td>
                                <td class="px-6 py-4 text-right text-base font-bold text-gray-900">${{ number_format($order->total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Payments --}}
            @if($order->payments->count() > 0)
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Payments</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($order->payments as $payment)
                                <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $payment->payment_method_name }}</div>
                                        <div class="text-xs text-gray-500">{{ $payment->created_at->format('M d, Y H:i') }}</div>
                                        @if($payment->reference)
                                            <div class="text-xs text-gray-500">Ref: {{ $payment->reference }}</div>
                                        @endif
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">${{ number_format($payment->amount, 2) }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Total Paid:</span>
                            <span class="text-lg font-bold text-green-600">${{ number_format($order->total_paid, 2) }}</span>
                        </div>
                        @if($order->remaining_balance > 0)
                            <div class="mt-2 flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700">Remaining Balance:</span>
                                <span class="text-lg font-bold text-red-600">${{ number_format($order->remaining_balance, 2) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Refunds --}}
            @if($order->refunds->count() > 0)
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Refunds</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($order->refunds as $refund)
                                <div class="flex justify-between items-start py-2 border-b border-gray-100 last:border-0">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">${{ number_format($refund->amount, 2) }}</div>
                                        <div class="text-xs text-gray-500">{{ $refund->created_at->format('M d, Y H:i') }}</div>
                                        <div class="text-xs text-gray-500">By: {{ $refund->user->name }}</div>
                                        @if($refund->reason)
                                            <div class="text-xs text-gray-600 mt-1">{{ $refund->reason }}</div>
                                        @endif
                                    </div>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        {{ number_format($refund->refund_percentage, 1) }}%
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200 flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Total Refunded:</span>
                            <span class="text-lg font-bold text-red-600">${{ number_format($order->total_refunded, 2) }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Notes --}}
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Notes</h3>
                    <button wire:click="openNotesModal" class="text-sm text-blue-600 hover:text-blue-800">
                        Edit
                    </button>
                </div>
                <div class="p-6">
                    @if($order->notes)
                        <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $order->notes }}</p>
                    @else
                        <p class="text-sm text-gray-500 italic">No notes added</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Status Card --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-600">Order Status</label>
                        <div class="mt-1">
                            <span class="px-3 py-1 text-sm font-semibold rounded-full
                                {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $order->status === 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $order->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">Payment Status</label>
                        <div class="mt-1">
                            <span class="px-3 py-1 text-sm font-semibold rounded-full
                                {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $order->payment_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $order->payment_status === 'partial' ? 'bg-orange-100 text-orange-800' : '' }}
                                {{ $order->payment_status === 'refunded' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </div>
                    </div>
                    @if($order->is_synced)
                        <div>
                            <label class="text-sm text-gray-600">Sync Status</label>
                            <div class="mt-1 flex items-center text-sm text-green-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Synced {{ $order->synced_at->diffForHumans() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Customer Info --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer</h3>
                @if($order->customer)
                    <div class="space-y-2">
                        <div>
                            <a href="{{ route('customers.profile', $order->customer) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                {{ $order->customer->first_name }} {{ $order->customer->last_name }}
                            </a>
                        </div>
                        <div class="text-sm text-gray-600">{{ $order->customer->email }}</div>
                        @if($order->customer->phone)
                            <div class="text-sm text-gray-600">{{ $order->customer->phone }}</div>
                        @endif
                        <div class="pt-2 border-t border-gray-200">
                            <div class="text-xs text-gray-500">Total Orders: {{ $order->customer->total_orders }}</div>
                            <div class="text-xs text-gray-500">Total Spent: ${{ number_format($order->customer->total_spent, 2) }}</div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Guest Customer</p>
                @endif
            </div>

            {{-- Cashier Info --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Cashier</h3>
                <div class="space-y-2">
                    <div class="text-sm font-medium text-gray-900">{{ $order->user->name }}</div>
                    <div class="text-sm text-gray-600">{{ $order->user->email }}</div>
                </div>
            </div>

            {{-- Profitability --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Profitability</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Revenue:</span>
                        <span class="text-sm font-medium text-gray-900">${{ number_format($profitability['revenue'], 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Cost:</span>
                        <span class="text-sm font-medium text-gray-900">${{ number_format($profitability['cost'], 2) }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-gray-200">
                        <span class="text-sm font-medium text-gray-700">Profit:</span>
                        <span class="text-sm font-bold {{ $profitability['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            ${{ number_format($profitability['profit'], 2) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm font-medium text-gray-700">Margin:</span>
                        <span class="text-sm font-bold {{ $profitability['profit_margin'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ number_format($profitability['profit_margin'], 1) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cancel Order Modal --}}
    @if($showCancelModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Cancel Order</h3>
                </div>
                <div class="p-6">
                    <p class="text-sm text-gray-600 mb-4">Please provide a reason for cancelling this order:</p>
                    <textarea wire:model="cancelReason" rows="4" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500" placeholder="Reason for cancellation..."></textarea>
                    @error('cancelReason') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                    <button wire:click="$set('showCancelModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Close
                    </button>
                    <button wire:click="cancelOrder" class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700">
                        Cancel Order
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Update Status Modal --}}
    @if($showStatusModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Update Order Status</h3>
                </div>
                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
                    <select wire:model="newStatus" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    @error('newStatus') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                    <button wire:click="$set('showStatusModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="updateStatus" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                        Update Status
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Notes Modal --}}
    @if($showNotesModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Notes</h3>
                </div>
                <div class="p-6">
                    <textarea wire:model="orderNotes" rows="6" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Add notes about this order..."></textarea>
                    @error('orderNotes') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                </div>
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-2">
                    <button wire:click="$set('showNotesModal', false)" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button wire:click="saveNotes" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700">
                        Save Notes
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('print-order', () => {
            window.print();
        });
    });
</script>
@endpush