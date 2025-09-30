<div>
    {{-- Modal Header --}}
    <div class="bg-white px-6 py-4 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ $isEditing ? 'Edit Customer' : 'Add New Customer' }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $isEditing ? 'Update customer information' : 'Create a new customer record' }}
                </p>
            </div>
            <button 
                wire:click="closeModal"
                class="text-gray-400 hover:text-gray-600 transition-colors"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Form Content --}}
    <div class="bg-white px-6 py-4 max-h-[calc(100vh-16rem)] overflow-y-auto">
        <form wire:submit.prevent="save">
            {{-- Tab Navigation --}}
            <div class="border-b border-gray-200 mb-6">
                <nav class="flex -mb-px space-x-8">
                    <button 
                        type="button"
                        wire:click="setActiveTab('basic')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'basic' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Basic Info
                    </button>
                    <button 
                        type="button"
                        wire:click="setActiveTab('contact')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'contact' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Contact & Address
                    </button>
                    <button 
                        type="button"
                        wire:click="setActiveTab('additional')"
                        class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'additional' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        Additional Info
                    </button>
                    @if($isEditing && $statistics)
                        <button 
                            type="button"
                            wire:click="setActiveTab('stats')"
                            class="pb-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'stats' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        >
                            Statistics
                        </button>
                    @endif
                </nav>
            </div>

            {{-- Tab Content --}}
            <div class="space-y-6">
                {{-- Basic Information Tab --}}
                @if($activeTab === 'basic')
                    <div class="space-y-4">
                        {{-- Name Fields --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    First Name <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text"
                                    wire:model="first_name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Enter first name"
                                    autofocus
                                >
                                @error('first_name') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Last Name <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text"
                                    wire:model="last_name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Enter last name"
                                >
                                @error('last_name') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Full Name Preview --}}
                        @if($first_name || $last_name)
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p class="text-sm text-gray-600">Full Name:</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $fullName }}</p>
                            </div>
                        @endif

                        {{-- Customer Group --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Customer Group
                            </label>
                            <select 
                                wire:model="customer_group_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                                <option value="">No Group</option>
                                @foreach($customerGroups as $group)
                                    <option value="{{ $group->id }}">
                                        {{ $group->name }}
                                        @if($group->discount_percentage > 0)
                                            ({{ $group->discount_percentage }}% discount)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_group_id') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                Assign customer to a group for special pricing
                            </p>
                        </div>
                    </div>
                @endif

                {{-- Contact & Address Tab --}}
                @if($activeTab === 'contact')
                    <div class="space-y-4">
                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address
                            </label>
                            <input 
                                type="email"
                                wire:model="email"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="customer@example.com"
                            >
                            @error('email') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number
                            </label>
                            <input 
                                type="tel"
                                wire:model="phone"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="+1 (555) 123-4567"
                            >
                            @error('phone') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Address --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Street Address
                            </label>
                            <textarea 
                                wire:model="address"
                                rows="2"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="123 Main Street, Apt 4B"
                            ></textarea>
                            @error('address') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- City and Postal Code --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    City
                                </label>
                                <input 
                                    type="text"
                                    wire:model="city"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="City name"
                                >
                                @error('city') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Postal Code
                                </label>
                                <input 
                                    type="text"
                                    wire:model="postal_code"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="12345"
                                >
                                @error('postal_code') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Additional Information Tab --}}
                @if($activeTab === 'additional')
                    <div class="space-y-4">
                        {{-- Notes --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Notes
                            </label>
                            <textarea 
                                wire:model="notes"
                                rows="4"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Add any additional notes about this customer..."
                            ></textarea>
                            @error('notes') 
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                Internal notes (not visible to customer)
                            </p>
                        </div>

                        @if($isEditing)
                            {{-- Loyalty Points (Read-only for editing) --}}
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-700">Loyalty Points</p>
                                        <p class="text-2xl font-bold text-orange-600">{{ number_format($loyalty_points) }}</p>
                                    </div>
                                    <div class="p-3 bg-orange-500 rounded-lg">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-gray-600">
                                    Manage loyalty points from the customer profile page
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Statistics Tab (Edit mode only) --}}
                @if($activeTab === 'stats' && $isEditing && $statistics)
                    <div class="space-y-4">
                        {{-- Statistics Grid --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
                                <p class="text-sm text-blue-600 font-medium">Total Orders</p>
                                <p class="text-2xl font-bold text-blue-900">{{ $statistics['total_orders'] }}</p>
                            </div>

                            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
                                <p class="text-sm text-green-600 font-medium">Total Spent</p>
                                <p class="text-2xl font-bold text-green-900">${{ number_format($statistics['total_spent'], 2) }}</p>
                            </div>

                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
                                <p class="text-sm text-purple-600 font-medium">Average Order</p>
                                <p class="text-2xl font-bold text-purple-900">${{ number_format($statistics['average_order_value'], 2) }}</p>
                            </div>

                            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4">
                                <p class="text-sm text-orange-600 font-medium">Loyalty Points</p>
                                <p class="text-2xl font-bold text-orange-900">{{ number_format($statistics['loyalty_points']) }}</p>
                            </div>
                        </div>

                        {{-- Additional Stats --}}
                        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Customer Since:</span>
                                <span class="font-medium text-gray-900">{{ $statistics['customer_since']->format('M d, Y') }}</span>
                            </div>
                            @if($statistics['last_order_date'])
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Last Order:</span>
                                    <span class="font-medium text-gray-900">{{ $statistics['last_order_date']->format('M d, Y') }}</span>
                                </div>
                                @if($statistics['days_since_last_order'])
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-600">Days Since Last Order:</span>
                                        <span class="font-medium text-gray-900">{{ $statistics['days_since_last_order'] }} days</span>
                                    </div>
                                @endif
                            @endif
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Total Items Purchased:</span>
                                <span class="font-medium text-gray-900">{{ number_format($statistics['total_items_purchased']) }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </form>
    </div>

    {{-- Modal Footer --}}
    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
            <button 
                type="button"
                wire:click="cancel"
                class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
            >
                Cancel
            </button>
            <div class="flex items-center space-x-3">
                @if(!$isEditing)
                    <button 
                        type="button"
                        wire:click="saveAndAddAnother"
                        class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors"
                    >
                        Save & Add Another
                    </button>
                @endif
                <button 
                    type="button"
                    wire:click="save"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                >
                    {{ $isEditing ? 'Update Customer' : 'Create Customer' }}
                </button>
            </div>
        </div>
    </div>
</div>