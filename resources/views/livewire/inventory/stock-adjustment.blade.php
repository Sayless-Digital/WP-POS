<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('inventory.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Stock Adjustment</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $item->name }}</p>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Current Stock Info -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Current Stock Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Current Stock</h2>
                
                <!-- Item Details -->
                <div class="space-y-3 mb-6">
                    <div>
                        <p class="text-sm text-gray-600">Product Name</p>
                        <p class="font-semibold text-gray-900">{{ $item->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">SKU</p>
                        <p class="font-semibold text-gray-900">{{ $item->sku ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Type</p>
                        <p class="font-semibold text-gray-900">{{ class_basename(get_class($item)) }}</p>
                    </div>
                </div>

                <!-- Stock Levels -->
                <div class="space-y-4 pt-4 border-t">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Current Quantity</span>
                        <span class="text-2xl font-bold text-gray-900">{{ number_format($inventory->quantity) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Reserved</span>
                        <span class="text-lg font-semibold text-gray-900">{{ number_format($inventory->reserved_quantity) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Available</span>
                        <span class="text-lg font-semibold text-green-600">{{ number_format($inventory->available_quantity) }}</span>
                    </div>
                    <div class="flex justify-between items-center pt-4 border-t">
                        <span class="text-sm text-gray-600">Status</span>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full bg-{{ $stockStatus['color'] }}-100 text-{{ $stockStatus['color'] }}-800">
                            {{ $stockStatus['status'] }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Reorder Settings Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Reorder Settings</h2>
                
                <form wire:submit.prevent="updateReorderSettings">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Point</label>
                            <input type="number" wire:model="reorderPoint" min="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('reorderPoint') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Quantity</label>
                            <input type="number" wire:model="reorderQuantity" min="0"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('reorderQuantity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Update Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Right Column - Adjustment Forms -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Adjustment Type Tabs -->
            <div class="bg-white rounded-lg shadow">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button wire:click="setAdjustmentType('add')" 
                            class="px-6 py-4 text-sm font-medium border-b-2 {{ $adjustmentType === 'add' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Add Stock
                        </button>
                        <button wire:click="setAdjustmentType('remove')" 
                            class="px-6 py-4 text-sm font-medium border-b-2 {{ $adjustmentType === 'remove' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Remove Stock
                        </button>
                        <button wire:click="setAdjustmentType('set')" 
                            class="px-6 py-4 text-sm font-medium border-b-2 {{ $adjustmentType === 'set' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Set Quantity
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <form wire:submit.prevent="adjustStock">
                        <!-- Quick Adjustment Buttons -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quick Adjust</label>
                            <div class="grid grid-cols-4 gap-2">
                                @foreach($quickAdjustments as $amount)
                                    <button type="button" wire:click="setQuickQuantity({{ $amount }})"
                                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 text-center font-semibold">
                                        {{ $adjustmentType === 'set' ? $amount : ($adjustmentType === 'add' ? '+' : '-') . $amount }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Quantity Input -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                @if($adjustmentType === 'add')
                                    Quantity to Add
                                @elseif($adjustmentType === 'remove')
                                    Quantity to Remove
                                @else
                                    Set Quantity To
                                @endif
                            </label>
                            <input type="number" wire:model.live="quantity" min="0"
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Enter quantity">
                            @error('quantity') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Projected Quantity -->
                        @if($quantity > 0)
                            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-blue-900">Projected Quantity:</span>
                                    <span class="text-2xl font-bold text-blue-900">{{ number_format($projectedQuantity) }}</span>
                                </div>
                                <div class="mt-2 text-sm text-blue-700">
                                    @if($adjustmentType === 'add')
                                        Adding {{ number_format($quantity) }} to current stock
                                    @elseif($adjustmentType === 'remove')
                                        Removing {{ number_format($quantity) }} from current stock
                                    @else
                                        Setting stock to {{ number_format($quantity) }}
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Reason -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason *</label>
                            <select wire:model="reason" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select a reason</option>
                                <option value="Purchase order received">Purchase order received</option>
                                <option value="Return from customer">Return from customer</option>
                                <option value="Damaged goods">Damaged goods</option>
                                <option value="Theft/Loss">Theft/Loss</option>
                                <option value="Physical count adjustment">Physical count adjustment</option>
                                <option value="Transfer">Transfer</option>
                                <option value="Correction">Correction</option>
                                <option value="Other">Other</option>
                            </select>
                            @error('reason') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Notes -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                            <textarea wire:model="notes" rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Add any additional notes..."></textarea>
                            @error('notes') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" 
                            class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            @if($adjustmentType === 'add')
                                Add Stock
                            @elseif($adjustmentType === 'remove')
                                Remove Stock
                            @else
                                Set Quantity
                            @endif
                        </button>
                    </form>
                </div>
            </div>

            <!-- Stock Count Section -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Physical Stock Count</h2>
                <p class="text-sm text-gray-600 mb-4">Perform a physical count and update the system quantity to match.</p>
                
                <form wire:submit.prevent="performStockCount">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Counted Quantity</label>
                            <input type="number" wire:model="quantity" min="0"
                                class="w-full px-4 py-3 text-lg border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Enter counted quantity">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea wire:model="notes" rows="2"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Add count notes..."></textarea>
                        </div>

                        <button type="submit" class="w-full px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700">
                            Complete Stock Count
                        </button>
                    </div>
                </form>
            </div>

            <!-- Recent Adjustments -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Adjustments</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($recentAdjustments as $adjustment)
                        <div class="px-6 py-4">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="px-2 py-1 text-xs font-semibold rounded {{ $adjustment->type === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $adjustment->type === 'in' ? '+' : '-' }}{{ number_format($adjustment->quantity) }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-900">{{ $adjustment->reason }}</span>
                                    </div>
                                    @if($adjustment->notes)
                                        <p class="text-sm text-gray-600">{{ $adjustment->notes }}</p>
                                    @endif
                                    <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                        <span>{{ $adjustment->created_at->format('M d, Y g:i A') }}</span>
                                        @if($adjustment->user)
                                            <span>By: {{ $adjustment->user->name }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">{{ number_format($adjustment->old_quantity) }} → {{ number_format($adjustment->new_quantity) }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-gray-500">
                            No recent adjustments
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>