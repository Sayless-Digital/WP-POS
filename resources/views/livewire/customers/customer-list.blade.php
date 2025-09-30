<div class="h-full flex flex-col bg-gray-50">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Customers</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage your customer database
                </p>
            </div>
            <div class="flex items-center space-x-3">
                {{-- View Mode Toggle --}}
                <button 
                    wire:click="toggleViewMode"
                    class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                    title="Toggle view mode"
                >
                    @if($viewMode === 'grid')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    @endif
                </button>

                {{-- Add Customer Button --}}
                <button 
                    wire:click="createCustomer"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Customer
                </button>
            </div>
        </div>

        {{-- Statistics Cards --}}
        <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-600">Total Customers</p>
                        <p class="text-2xl font-bold text-blue-900">{{ number_format($statistics['total_customers']) }}</p>
                    </div>
                    <div class="p-3 bg-blue-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-600">Total Spent</p>
                        <p class="text-2xl font-bold text-green-900">${{ number_format($statistics['total_spent'], 2) }}</p>
                    </div>
                    <div class="p-3 bg-green-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-purple-600">Average Spent</p>
                        <p class="text-2xl font-bold text-purple-900">${{ number_format($statistics['average_spent'], 2) }}</p>
                    </div>
                    <div class="p-3 bg-purple-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-orange-600">Loyalty Points</p>
                        <p class="text-2xl font-bold text-orange-900">{{ number_format($statistics['total_loyalty_points']) }}</p>
                    </div>
                    <div class="p-3 bg-orange-500 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Search and Filters Bar --}}
        <div class="mt-4 flex items-center space-x-3">
            {{-- Search --}}
            <div class="flex-1 relative">
                <input 
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search customers by name, email, or phone..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            {{-- Customer Group Filter --}}
            <select 
                wire:model.live="groupFilter"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">All Groups</option>
                @foreach($customerGroups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>

            {{-- Status Filter --}}
            <select 
                wire:model.live="statusFilter"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">All Status</option>
                <option value="vip">VIP Customers</option>
                <option value="active">Active (90 days)</option>
                <option value="inactive">Inactive</option>
                <option value="with_points">With Loyalty Points</option>
            </select>

            {{-- Clear Filters --}}
            @if($search || $groupFilter || $statusFilter)
                <button 
                    wire:click="clearFilters"
                    class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium transition-colors"
                >
                    Clear Filters
                </button>
            @endif
        </div>

        {{-- Filter Stats --}}
        <div class="mt-3 flex items-center space-x-4 text-sm">
            <span class="text-gray-600">
                <span class="font-semibold">{{ $filterCounts['all'] }}</span> Total
            </span>
            <span class="text-purple-600">
                <span class="font-semibold">{{ $filterCounts['vip'] }}</span> VIP
            </span>
            <span class="text-green-600">
                <span class="font-semibold">{{ $filterCounts['active'] }}</span> Active
            </span>
            <span class="text-gray-500">
                <span class="font-semibold">{{ $filterCounts['inactive'] }}</span> Inactive
            </span>
            @if($filterCounts['with_points'] > 0)
                <span class="text-orange-600">
                    <span class="font-semibold">{{ $filterCounts['with_points'] }}</span> With Points
                </span>
            @endif
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    @if(count($selectedCustomers) > 0)
        <div class="bg-blue-50 border-b border-blue-200 px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-blue-900">
                        {{ count($selectedCustomers) }} customer(s) selected
                    </span>
                    <button 
                        wire:click="deselectAll"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                    >
                        Deselect All
                    </button>
                </div>
                <div class="flex items-center space-x-2">
                    <select 
                        wire:change="bulkAssignGroup($event.target.value)"
                        class="px-3 py-1.5 border border-blue-300 rounded text-sm"
                    >
                        <option value="">Assign to Group...</option>
                        @foreach($customerGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                        <option value="">Remove from Group</option>
                    </select>
                    <button 
                        wire:click="exportSelected"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded transition-colors"
                    >
                        Export
                    </button>
                    <button 
                        wire:click="bulkDelete"
                        wire:confirm="Are you sure you want to delete the selected customers?"
                        class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded transition-colors"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Customers Content --}}
    <div class="flex-1 overflow-y-auto p-6">
        @if($customers->isEmpty())
            {{-- Empty State --}}
            <div class="flex flex-col items-center justify-center h-full text-center">
                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No customers found</h3>
                <p class="text-gray-500 mb-4">
                    @if($search || $groupFilter || $statusFilter)
                        Try adjusting your filters or search terms
                    @else
                        Get started by adding your first customer
                    @endif
                </p>
                @if(!$search && !$groupFilter && !$statusFilter)
                    <button 
                        wire:click="createCustomer"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Your First Customer
                    </button>
                @endif
            </div>
        @else
            {{-- Grid View --}}
            @if($viewMode === 'grid')
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($customers as $customer)
                        <div class="bg-white rounded-lg border border-gray-200 hover:shadow-lg transition-shadow">
                            {{-- Customer Header --}}
                            <div class="p-4 border-b border-gray-100">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center space-x-3 flex-1 min-w-0">
                                        {{-- Selection Checkbox --}}
                                        <input 
                                            type="checkbox"
                                            wire:click="toggleCustomerSelection({{ $customer->id }})"
                                            @checked(in_array($customer->id, $selectedCustomers))
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        >
                                        
                                        {{-- Avatar --}}
                                        <div class="flex-shrink-0">
                                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-lg">
                                                {{ strtoupper(substr($customer->first_name, 0, 1)) }}{{ strtoupper(substr($customer->last_name, 0, 1)) }}
                                            </div>
                                        </div>

                                        {{-- Name --}}
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-sm font-semibold text-gray-900 truncate">
                                                {{ $customer->full_name }}
                                            </h3>
                                            @if($customer->customerGroup)
                                                <p class="text-xs text-gray-500 truncate">
                                                    {{ $customer->customerGroup->name }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Badges --}}
                                <div class="mt-2 flex items-center space-x-2">
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

                            {{-- Customer Info --}}
                            <div class="p-4 space-y-3">
                                {{-- Contact --}}
                                @if($customer->email)
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <span class="truncate">{{ $customer->email }}</span>
                                    </div>
                                @endif

                                @if($customer->phone)
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                        <span>{{ $customer->phone }}</span>
                                    </div>
                                @endif

                                {{-- Statistics --}}
                                <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100">
                                    <div>
                                        <p class="text-xs text-gray-500">Total Spent</p>
                                        <p class="text-sm font-semibold text-gray-900">${{ number_format($customer->total_spent, 2) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Orders</p>
                                        <p class="text-sm font-semibold text-gray-900">{{ $customer->total_orders }}</p>
                                    </div>
                                </div>

                                @if($customer->loyalty_points > 0)
                                    <div class="pt-2 border-t border-gray-100">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-500">Loyalty Points</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                {{ number_format($customer->loyalty_points) }}
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Actions --}}
                            <div class="px-4 pb-4 flex items-center space-x-2">
                                <button 
                                    wire:click="viewCustomer({{ $customer->id }})"
                                    class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded text-center transition-colors"
                                >
                                    View
                                </button>
                                <button 
                                    wire:click="editCustomer({{ $customer->id }})"
                                    class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded text-center transition-colors"
                                >
                                    Edit
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                {{-- List View --}}
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input 
                                        type="checkbox"
                                        wire:click="selectAllOnPage"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    >
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('first_name')">
                                    <div class="flex items-center space-x-1">
                                        <span>Customer</span>
                                        @if($sortBy === 'first_name')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($sortDirection === 'asc')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                @endif
                                            </svg>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('total_spent')">
                                    <div class="flex items-center space-x-1">
                                        <span>Total Spent</span>
                                        @if($sortBy === 'total_spent')
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                @if($sortDirection === 'asc')
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                @endif
                                            </svg>
                                        @endif
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orders</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loyalty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($customers as $customer)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <input 
                                            type="checkbox"
                                            wire:click="toggleCustomerSelection({{ $customer->id }})"
                                            @checked(in_array($customer->id, $selectedCustomers))
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        >
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold">
                                                    {{ strtoupper(substr($customer->first_name, 0, 1)) }}{{ strtoupper(substr($customer->last_name, 0, 1)) }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $customer->full_name }}
                                                </div>
                                                @if($customer->customerGroup)
                                                    <div class="text-sm text-gray-500">
                                                        {{ $customer->customerGroup->name }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">{{ $customer->email ?? '-' }}</div>
                                        <div class="text-sm text-gray-500">{{ $customer->phone ?? '-' }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $customer->customerGroup?->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            ${{ number_format($customer->total_spent, 2) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Avg: ${{ number_format($customer->average_order_value, 2) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        {{ $customer->total_orders }}
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($customer->loyalty_points > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                </svg>
                                                {{ number_format($customer->loyalty_points) }}
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            @if($customer->isVip())
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                                                    VIP
                                                </span>
                                            @endif
                                            @if($customer->isActive())
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            @else
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    Inactive
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                        <button 
                                            wire:click="viewCustomer({{ $customer->id }})"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            View
                                        </button>
                                        <button 
                                            wire:click="editCustomer({{ $customer->id }})"
                                            class="text-gray-600 hover:text-gray-900"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            wire:click="deleteCustomer({{ $customer->id }})"
                                            wire:confirm="Are you sure you want to delete this customer?"
                                            class="text-red-600 hover:text-red-900"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

    {{-- Customer Form Modal --}}
    @if($showCustomerForm)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showCustomerForm', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <livewire:customers.customer-form :customer-id="$editingCustomerId" :key="$editingCustomerId ?? 'new'" />
                </div>
            </div>
        </div>
    @endif
</div>
                                <th class="px-6 py-3 text-right text-xs font-medium text-