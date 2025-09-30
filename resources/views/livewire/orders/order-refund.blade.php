<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-start">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('orders.details', $order) }}" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Process Refund</h2>
                    <p class="mt-1 text-sm text-gray-600">Order {{ $order->order_number }}</p>
                </div>
            </div>
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
        {{-- Refund Form --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Refund Details</h3>
                </div>
                <div class="p-6 space-y-6">
                    {{-- Refund Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Refund Type</label>
                        <div class="flex gap-4">
                            <label class="flex items-center">
                                <input type="radio" wire:model.live="refundType" value="full" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Full Refund</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" wire:model.live="refundType" value="partial" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Partial Refund</span>
                            </label>
                        </div>
                    </div>

                    {{-- Refund Amount --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Refund Amount
                            <span class="text-xs text-gray-500">(Max: ${{ number_format($maxRefundable, 2) }})</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">$</span>
                            </div>
                            <input 
                                type="number" 
                                wire:model="refundAmount" 
                                step="0.01" 
                                min="0.01" 
                                max="{{ $maxRefundable }}"
                                class="pl-7 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                {{ $refundType === 'full' ? 'readonly' : '' }}
                            >
                        </div>
                        @error('refundAmount') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Payment Selection --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Payment to Refund</label>
                        <select wire:model="selectedPaymentId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @foreach($refundablePayments as $payment)
                                @php
                                    $refunded = $order->refunds()->where('payment_id', $payment->id)->sum('amount');
                                    $available = $payment->amount - $refunded;
                                @endphp
                                <option value="{{ $payment->id }}">
                                    {{ $payment->payment_method_name }} - ${{ number_format($payment->amount, 2) }}
                                    (Available: ${{ number_format($available, 2) }})
                                    - {{ $payment->created_at->format('M d, Y H:i') }}
                                </option>
                            @endforeach
                        </select>
                        @error('selectedPaymentId') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Refund Reason --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Refund</label>
                        <textarea 
                            wire:model="refundReason" 
                            rows="4" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Provide a detailed reason for this refund..."
                        ></textarea>
                        @error('refundReason') 
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Restock Items --}}
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="restockItems" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Restock items to inventory</span>
                        </label>
                        <p class="mt-1 text-xs text-gray-500">
                            If checked, the refunded items will be added back to inventory
                        </p>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('orders.details', $order) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button 
                            wire:click="processRefund" 
                            class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700"
                        >
                            Process Refund
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Order Summary Sidebar --}}
        <div class="space-y-6">
            {{-- Order Summary --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Order Total:</span>
                        <span class="font-medium text-gray-900">${{ number_format($order->total, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Total Paid:</span>
                        <span class="font-medium text-green-600">${{ number_format($order->total_paid, 2) }}</span>
                    </div>
                    @if($order->total_refunded > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Already Refunded:</span>
                            <span class="font-medium text-red-600">${{ number_format($order->total_refunded, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm pt-3 border-t border-gray-200">
                        <span class="font-medium text-gray-700">Max Refundable:</span>
                        <span class="font-bold text-gray-900">${{ number_format($maxRefundable, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Order Items --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h3>
                <div class="space-y-3">
                    @foreach($order->items as $item)
                        <div class="flex justify-between items-start text-sm pb-3 border-b border-gray-100 last:border-0">
                            <div class="flex-1">
                                <div class="font-medium text-gray-900">{{ $item->name }}</div>
                                @if($item->variant)
                                    <div class="text-xs text-gray-500">{{ $item->variant->name }}</div>
                                @endif
                                <div class="text-xs text-gray-500">Qty: {{ $item->quantity }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium text-gray-900">${{ number_format($item->total, 2) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Previous Refunds --}}
            @if($order->refunds->count() > 0)
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Previous Refunds</h3>
                    <div class="space-y-3">
                        @foreach($order->refunds as $refund)
                            <div class="text-sm pb-3 border-b border-gray-100 last:border-0">
                                <div class="flex justify-between items-start mb-1">
                                    <span class="font-medium text-gray-900">${{ number_format($refund->amount, 2) }}</span>
                                    <span class="text-xs text-gray-500">{{ $refund->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="text-xs text-gray-600">{{ $refund->reason }}</div>
                                <div class="text-xs text-gray-500 mt-1">By: {{ $refund->user->name }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Warning Notice --}}
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm text-yellow-800">
                        <p class="font-medium">Important</p>
                        <p class="mt-1">This action cannot be undone. Please verify all details before processing the refund.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>