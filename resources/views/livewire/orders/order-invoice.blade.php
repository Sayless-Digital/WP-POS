<div>
    {{-- Action Buttons (Hidden when printing) --}}
    <div class="mb-6 flex justify-between items-center print:hidden">
        <a href="{{ route('orders.details', $order) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Order
        </a>
        <div class="flex gap-2">
            @if($order->customer && $order->customer->email)
                <button wire:click="emailInvoice" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Email Invoice
                </button>
            @endif
            <button wire:click="print" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print Invoice
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg print:hidden">
            {{ session('success') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg print:hidden">
            {{ session('error') }}
        </div>
    @endif

    {{-- Invoice --}}
    <div class="bg-white shadow-lg rounded-lg overflow-hidden print:shadow-none">
        <div class="p-8 md:p-12">
            {{-- Header --}}
            <div class="flex justify-between items-start mb-8 pb-8 border-b-2 border-gray-300">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $companyInfo['name'] }}</h1>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p>{{ $companyInfo['address'] }}</p>
                        <p>{{ $companyInfo['city'] }}</p>
                        <p>Phone: {{ $companyInfo['phone'] }}</p>
                        <p>Email: {{ $companyInfo['email'] }}</p>
                        @if($companyInfo['website'])
                            <p>{{ $companyInfo['website'] }}</p>
                        @endif
                        @if($companyInfo['tax_id'])
                            <p>Tax ID: {{ $companyInfo['tax_id'] }}</p>
                        @endif
                    </div>
                </div>
                <div class="text-right">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">INVOICE</h2>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><span class="font-semibold">Invoice #:</span> {{ $order->order_number }}</p>
                        <p><span class="font-semibold">Date:</span> {{ $order->created_at->format('M d, Y') }}</p>
                        <p><span class="font-semibold">Time:</span> {{ $order->created_at->format('H:i') }}</p>
                        <p>
                            <span class="font-semibold">Status:</span>
                            <span class="px-2 py-1 text-xs font-semibold rounded
                                {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $order->status === 'processing' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $order->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Customer & Cashier Info --}}
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase mb-2">Bill To:</h3>
                    @if($order->customer)
                        <div class="text-sm text-gray-700 space-y-1">
                            <p class="font-semibold">{{ $order->customer->first_name }} {{ $order->customer->last_name }}</p>
                            <p>{{ $order->customer->email }}</p>
                            @if($order->customer->phone)
                                <p>{{ $order->customer->phone }}</p>
                            @endif
                            @if($order->customer->address)
                                <p>{{ $order->customer->address }}</p>
                            @endif
                            @if($order->customer->city || $order->customer->state || $order->customer->zip_code)
                                <p>
                                    {{ $order->customer->city }}
                                    @if($order->customer->state), {{ $order->customer->state }}@endif
                                    {{ $order->customer->zip_code }}
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="text-sm text-gray-700">Guest Customer</p>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 uppercase mb-2">Served By:</h3>
                    <div class="text-sm text-gray-700 space-y-1">
                        <p class="font-semibold">{{ $order->user->name }}</p>
                        <p>{{ $order->user->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Order Items --}}
            <div class="mb-8">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-gray-300">
                            <th class="text-left py-3 text-sm font-semibold text-gray-900 uppercase">Item</th>
                            <th class="text-center py-3 text-sm font-semibold text-gray-900 uppercase">Qty</th>
                            <th class="text-right py-3 text-sm font-semibold text-gray-900 uppercase">Price</th>
                            <th class="text-right py-3 text-sm font-semibold text-gray-900 uppercase">Tax</th>
                            <th class="text-right py-3 text-sm font-semibold text-gray-900 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr class="border-b border-gray-200">
                                <td class="py-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $item->name }}</div>
                                    @if($item->variant)
                                        <div class="text-xs text-gray-500">{{ $item->variant->name }}</div>
                                    @endif
                                    <div class="text-xs text-gray-500">SKU: {{ $item->sku }}</div>
                                    @if($item->discount_amount > 0)
                                        <div class="text-xs text-red-600">Discount: -${{ number_format($item->discount_amount, 2) }}</div>
                                    @endif
                                </td>
                                <td class="py-3 text-center text-sm text-gray-900">{{ $item->quantity }}</td>
                                <td class="py-3 text-right text-sm text-gray-900">${{ number_format($item->price, 2) }}</td>
                                <td class="py-3 text-right text-sm text-gray-900">
                                    ${{ number_format($item->tax_amount, 2) }}
                                    @if($item->tax_rate > 0)
                                        <span class="text-xs text-gray-500">({{ $item->tax_rate }}%)</span>
                                    @endif
                                </td>
                                <td class="py-3 text-right text-sm font-medium text-gray-900">${{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="flex justify-end mb-8">
                <div class="w-64">
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-gray-600">Subtotal:</span>
                        <span class="font-medium text-gray-900">${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    @if($order->discount_amount > 0)
                        <div class="flex justify-between py-2 text-sm">
                            <span class="text-gray-600">Discount:</span>
                            <span class="font-medium text-red-600">-${{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-gray-600">Tax:</span>
                        <span class="font-medium text-gray-900">${{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-3 border-t-2 border-gray-300">
                        <span class="text-lg font-bold text-gray-900">Total:</span>
                        <span class="text-lg font-bold text-gray-900">${{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Payment Information --}}
            @if($order->payments->count() > 0)
                <div class="mb-8 pb-8 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase mb-3">Payment Information</h3>
                    <div class="space-y-2">
                        @foreach($order->payments as $payment)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">
                                    {{ $payment->payment_method_name }}
                                    @if($payment->reference)
                                        <span class="text-xs text-gray-500">(Ref: {{ $payment->reference }})</span>
                                    @endif
                                </span>
                                <span class="font-medium text-gray-900">${{ number_format($payment->amount, 2) }}</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between pt-2 border-t border-gray-200">
                            <span class="text-sm font-semibold text-gray-900">Total Paid:</span>
                            <span class="text-sm font-semibold text-green-600">${{ number_format($order->total_paid, 2) }}</span>
                        </div>
                        @if($order->remaining_balance > 0)
                            <div class="flex justify-between">
                                <span class="text-sm font-semibold text-gray-900">Balance Due:</span>
                                <span class="text-sm font-semibold text-red-600">${{ number_format($order->remaining_balance, 2) }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Notes --}}
            @if($order->notes)
                <div class="mb-8">
                    <h3 class="text-sm font-semibold text-gray-900 uppercase mb-2">Notes</h3>
                    <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $order->notes }}</p>
                </div>
            @endif

            {{-- Footer --}}
            <div class="text-center text-sm text-gray-600 pt-8 border-t border-gray-200">
                <p class="mb-2">Thank you for your business!</p>
                <p>If you have any questions about this invoice, please contact us at {{ $companyInfo['email'] }} or {{ $companyInfo['phone'] }}</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('print-invoice', () => {
            window.print();
        });
    });
</script>
@endpush

<style>
    @media print {
        body * {
            visibility: hidden;
        }
        .print\:shadow-none, .print\:shadow-none * {
            visibility: visible;
        }
        .print\:shadow-none {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .print\:hidden {
            display: none !important;
        }
    }
</style>