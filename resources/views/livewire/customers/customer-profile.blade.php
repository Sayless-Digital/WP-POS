<div class="h-full flex flex-col bg-gray-50">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                {{-- Back Button --}}
                <a 
                    href="{{ route('customers.index') }}"
                    class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>

                {{-- Customer Avatar & Name --}}
                <div class="flex items-center space-x-3">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-2xl">
                        {{ strtoupper(substr($customer->first_name, 0, 1)) }}{{ strtoupper(substr($customer->last_name, 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">{{ $customer->full_name }}</h2>
                        <div class="flex items-center space-x-2 mt-1">
                            @if($customer->customerGroup)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $customer->customerGroup->name }}
                                </span>
                            @endif
                            @if($customer->isVip())
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    VIP
                                </span>
                            @endif
                            @if($customer->isActive())
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    Inactive
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center space-x-3">
                <button 
                    wire:click="editCustomer"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Customer
                </button>
                <button 
                    wire:click="deleteCustomer"
                    wire:confirm="Are you sure you want to delete this customer?"
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            </div>
        </div>
    </div>

    {{-- Content --}}
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-7xl mx-auto space-y-6">
            {{-- Statistics Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                {{-- Total Orders --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Orders</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ $statistics['total_orders'] }}</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Spent --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Spent</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">${{ number_format($statistics['total_spent'], 2) }}</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Average Order Value --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Avg Order Value</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">${{ number_format($statistics['average_order_value'], 2) }}</p>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Loyalty Points --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Loyalty Points</p>
                            <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($customer->loyalty_points) }}</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-lg">
                            <svg class="w-8 h-8 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                    </div>
                    <button 
                        wire:click="manageLoyaltyPoints"
                        class="mt-3 w-full px-3 py-1.5 bg-orange-50 hover:bg-orange-100 text-orange-700 text-sm font-medium rounded transition-colors"
                    >
                        Manage Points
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Customer Information --}}
                <div class="lg:col-span-1 space-y-6">
                    {{-- Contact Information --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                        <div class="space-y-3">
                            @if($customer->email)
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm text-gray-500">Email</p>
                                        <p class="text-sm font-medium text-gray-900">{{ $customer->email }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($customer->phone)
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm text-gray-500">Phone</p>
                                        <p class="text-sm font-medium text-gray-900">{{ $customer->phone }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($customer->address || $customer->city || $customer->postal_code)
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-gray-400 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm text-gray-500">Address</p>
                                        @if($customer->address)
                                            <p class="text-sm font-medium text-gray-900">{{ $customer->address }}</p>
                                        @endif
                                        @if($customer->city || $customer->postal_code)
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $customer->city }}{{ $customer->city && $customer->postal_code ? ', ' : '' }}{{ $customer->postal_code }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Additional Information --}}
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Information</h3>
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-500">Customer Since</p>
                                <p class="text-sm font-medium text-gray-900">{{ $statistics['customer_since']->format('M d, Y') }}</p>
                            </div>

                            @if($statistics['last_order_date'])
                                <div>
                                    <p class="text-sm text-gray-500">Last Order</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $statistics['last_order_date']->format('M d, Y') }}</p>
                                    @if($statistics['days_since_last_order'])
                                        <p class="text-xs text-gray-500">{{ $statistics['days_since_last_order'] }} days ago</p>
                                    @endif
                                </div>
                            @endif

                            <div>
                                <p class="text-sm text-gray-500">Total Items Purchased</p>
                                <p class="text-sm font-medium text-gray-900">{{ number_format($statistics['total_items_purchased']) }}</p>
                            </div>

                            @if($customer->customerGroup)
                                <div>
                                    <p class="text-sm text-gray-500">Customer Group</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $customer->customerGroup->name }}</p>
                                    @if($customer->customerGroup->discount_percentage > 0)
                                        <p class="text-xs text-green-600">{{ $customer->customerGroup->discount_percentage }}% discount</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if($customer->notes)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $customer->notes }}</p>
                        </div>
                    @endif
                </div>

                {{-- Recent Orders --}}
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Orders</h3>
                            <button 
                                wire:click="togglePurchaseHistory"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                            >
                                View All Orders
                            </button>
                        </div>
                        <div class="p-6">
                            @if($recentOrders->isEmpty())
                                <div class="text-center py-8 text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    <p>No orders yet</p>
                                </div>
                            @else
                                <div class="space-y-4">
                                    @foreach($recentOrders as $order)
                                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                            <div class="flex items-center justify-between mb-2">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">Order #{{ $order->order_number }}</p>
                                                    <p class="text-xs text-gray-500">{{ $order->created_at->format('M d, Y g:i A') }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-bold text-gray-900">${{ number_format($order->total, 2) }}</p>
                                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium 
                                                        {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                        {{ $order->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                        {{ $order->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                                                    ">
                                                        {{ ucfirst($order->status) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-between text-xs text-gray-500">
                                                <span>{{ $order->items->count() }} item(s)</span>
                                                <span>{{ $order->payments->first()?->payment_method ?? 'N/A' }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit Customer Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeEditModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <livewire:customers.customer-form :customer-id="$customer->id" :key="'edit-'.$customer->id" />
                </div>
            </div>
        </div>
    @endif

    {{-- Loyalty Points Modal --}}
    @if($showLoyaltyModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeLoyaltyModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                    <div class="bg-white px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Manage Loyalty Points</h3>
                        
                        <div class="space-y-4">
                            {{-- Current Points --}}
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <p class="text-sm text-gray-600">Current Points</p>
                                <p class="text-2xl font-bold text-orange-600">{{ number_format($customer->loyalty_points) }}</p>
                            </div>

                            {{-- Action Type --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <label class="relative flex items-center p-3 border-2 rounded-lg cursor-pointer {{ $loyaltyAction === 'add' ? 'border-green-500 bg-green-50' : 'border-gray-200' }}">
                                        <input type="radio" wire:model="loyaltyAction" value="add" class="sr-only">
                                        <span class="font-medium {{ $loyaltyAction === 'add' ? 'text-green-900' : 'text-gray-900' }}">Add Points</span>
                                    </label>
                                    <label class="relative flex items-center p-3 border-2 rounded-lg cursor-pointer {{ $loyaltyAction === 'redeem' ? 'border-red-500 bg-red-50' : 'border-gray-200' }}">
                                        <input type="radio" wire:model="loyaltyAction" value="redeem" class="sr-only">
                                        <span class="font-medium {{ $loyaltyAction === 'redeem' ? 'text-red-900' : 'text-gray-900' }}">Redeem Points</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Points Amount --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Points</label>
                                <input 
                                    type="number"
                                    wire:model.live="loyaltyPoints"
                                    min="1"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Enter points amount"
                                >
                                @error('loyaltyPoints') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Discount Preview (for redeem) --}}
                            @if($loyaltyAction === 'redeem' && $loyaltyPoints > 0)
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                    <p class="text-sm text-gray-600">Discount Value</p>
                                    <p class="text-lg font-bold text-blue-600">${{ number_format($loyaltyDiscount, 2) }}</p>
                                </div>
                            @endif

                            {{-- Reason --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Reason (Optional)</label>
                                <input 
                                    type="text"
                                    wire:model="loyaltyReason"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., Birthday bonus, Purchase reward"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex items-center justify-between">
                        <button 
                            wire:click="closeLoyaltyModal"
                            class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            wire:click="awardLoyaltyPoints"
                            class="px-4 py-2 {{ $loyaltyAction === 'add' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} text-white font-medium rounded-lg transition-colors"
                        >
                            {{ $loyaltyAction === 'add' ? 'Add Points' : 'Redeem Points' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Purchase History Modal --}}
    @if($showPurchaseHistory)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="togglePurchaseHistory"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <livewire:customers.purchase-history :customer-id="$customer->id" :key="'history-'.$customer->id" />
                </div>
            </div>
        </div>
    @endif
</div>