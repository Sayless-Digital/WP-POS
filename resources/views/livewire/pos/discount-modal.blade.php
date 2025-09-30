<div>
    @if($show)
    <!-- Modal Overlay -->
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="text-xl font-semibold text-white">
                                Apply Discount
                            </h3>
                        </div>
                        <button wire:click="closeModal" class="text-white hover:text-gray-200 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="bg-white px-6 py-6">
                    <!-- Cart Subtotal Display -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-600">Cart Subtotal</span>
                            <span class="text-lg font-bold text-gray-900">{{ $formatCurrency($cartSubtotal) }}</span>
                        </div>
                        @if($currentDiscount > 0)
                        <div class="flex justify-between items-center mt-2 text-sm">
                            <span class="text-gray-600">Current Discount</span>
                            <span class="text-green-600 font-medium">-{{ $formatCurrency($currentDiscount) }}</span>
                        </div>
                        @endif
                    </div>

                    <!-- Discount Type Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Discount Type</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button 
                                wire:click="$set('discountType', 'fixed')"
                                type="button"
                                class="flex items-center justify-center px-4 py-3 border-2 rounded-lg transition-all {{ $discountType === 'fixed' ? 'border-green-600 bg-green-50 text-green-700' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400' }}"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-medium">Fixed Amount</span>
                            </button>
                            <button 
                                wire:click="$set('discountType', 'percentage')"
                                type="button"
                                class="flex items-center justify-center px-4 py-3 border-2 rounded-lg transition-all {{ $discountType === 'percentage' ? 'border-green-600 bg-green-50 text-green-700' : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400' }}"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                <span class="font-medium">Percentage</span>
                            </button>
                        </div>
                    </div>

                    <!-- Quick Discount Buttons (for percentage) -->
                    @if($discountType === 'percentage')
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Quick Discount</label>
                        <div class="grid grid-cols-4 gap-2">
                            <button 
                                wire:click="applyQuickDiscount(5)"
                                type="button"
                                class="px-3 py-2 text-sm font-medium border-2 border-gray-300 rounded-lg hover:border-green-600 hover:bg-green-50 hover:text-green-700 transition-all"
                            >
                                5%
                            </button>
                            <button 
                                wire:click="applyQuickDiscount(10)"
                                type="button"
                                class="px-3 py-2 text-sm font-medium border-2 border-gray-300 rounded-lg hover:border-green-600 hover:bg-green-50 hover:text-green-700 transition-all"
                            >
                                10%
                            </button>
                            <button 
                                wire:click="applyQuickDiscount(15)"
                                type="button"
                                class="px-3 py-2 text-sm font-medium border-2 border-gray-300 rounded-lg hover:border-green-600 hover:bg-green-50 hover:text-green-700 transition-all"
                            >
                                15%
                            </button>
                            <button 
                                wire:click="applyQuickDiscount(20)"
                                type="button"
                                class="px-3 py-2 text-sm font-medium border-2 border-gray-300 rounded-lg hover:border-green-600 hover:bg-green-50 hover:text-green-700 transition-all"
                            >
                                20%
                            </button>
                        </div>
                    </div>
                    @endif

                    <!-- Discount Amount Input -->
                    <div class="mb-6">
                        <label for="discountAmount" class="block text-sm font-medium text-gray-700 mb-2">
                            Discount Amount
                            @if($discountType === 'percentage')
                                <span class="text-gray-500">(0-100%)</span>
                            @else
                                <span class="text-gray-500">(Max: {{ $formatCurrency($cartSubtotal) }})</span>
                            @endif
                        </label>
                        <div class="relative">
                            @if($discountType === 'fixed')
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-lg">$</span>
                            </div>
                            @endif
                            <input 
                                type="number" 
                                id="discountAmount"
                                wire:model.live="discountAmount"
                                step="0.01"
                                min="0"
                                max="{{ $discountType === 'percentage' ? 100 : $cartSubtotal }}"
                                class="block w-full {{ $discountType === 'fixed' ? 'pl-8' : 'pl-4' }} pr-12 py-3 text-lg font-semibold border-2 border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 @error('discountAmount') border-red-500 @enderror"
                                placeholder="0.00"
                            >
                            @if($discountType === 'percentage')
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-lg">%</span>
                            </div>
                            @endif
                        </div>
                        @error('discountAmount')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Discount Preview -->
                    @if($discountAmount > 0)
                    <div class="bg-green-50 border-2 border-green-200 rounded-lg p-4 mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-gray-700">Discount Amount</span>
                            <span class="text-lg font-bold text-green-600">-{{ $formatCurrency($this->calculatedDiscount) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-green-200">
                            <span class="text-sm font-medium text-gray-700">New Total</span>
                            <span class="text-xl font-bold text-gray-900">{{ $formatCurrency($this->newTotal) }}</span>
                        </div>
                    </div>
                    @endif

                    <!-- Reason Input -->
                    <div class="mb-6">
                        <label for="reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Reason <span class="text-gray-500">(Optional)</span>
                        </label>
                        <input 
                            type="text" 
                            id="reason"
                            wire:model="reason"
                            class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"
                            placeholder="e.g., Loyalty discount, Promotion, etc."
                            maxlength="255"
                        >
                        @error('reason')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex gap-3">
                    @if($currentDiscount > 0)
                    <button 
                        wire:click="removeDiscount"
                        wire:loading.attr="disabled"
                        class="flex-1 px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove wire:target="removeDiscount">Remove Discount</span>
                        <span wire:loading wire:target="removeDiscount">Removing...</span>
                    </button>
                    @endif
                    <button 
                        wire:click="closeModal"
                        class="px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                    >
                        Cancel
                    </button>
                    <button 
                        wire:click="applyDiscount"
                        wire:loading.attr="disabled"
                        class="flex-1 px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove wire:target="applyDiscount">Apply Discount</span>
                        <span wire:loading wire:target="applyDiscount">Applying...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>