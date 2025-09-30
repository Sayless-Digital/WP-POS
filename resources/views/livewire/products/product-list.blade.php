<div class="h-full flex flex-col bg-gray-50">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Products</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage your product catalog
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

                {{-- Add Product Button --}}
                <a 
                    href="{{ route('products.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Product
                </a>
            </div>
        </div>

        {{-- Search and Filters Bar --}}
        <div class="mt-4 flex items-center space-x-3">
            {{-- Search --}}
            <div class="flex-1 relative">
                <input 
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search products by name, SKU, or description..."
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            {{-- Category Filter --}}
            <select 
                wire:model.live="categoryFilter"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            {{-- Type Filter --}}
            <select 
                wire:model.live="typeFilter"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">All Types</option>
                <option value="simple">Simple</option>
                <option value="variable">Variable</option>
            </select>

            {{-- Stock Filter --}}
            <select 
                wire:model.live="stockFilter"
                class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">All Stock</option>
                <option value="in_stock">In Stock</option>
                <option value="low_stock">Low Stock</option>
                <option value="out_of_stock">Out of Stock</option>
            </select>

            {{-- Clear Filters --}}
            @if($search || $categoryFilter || $typeFilter || $stockFilter)
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
            <span class="text-green-600">
                <span class="font-semibold">{{ $filterCounts['active'] }}</span> Active
            </span>
            <span class="text-gray-500">
                <span class="font-semibold">{{ $filterCounts['inactive'] }}</span> Inactive
            </span>
            @if($filterCounts['low_stock'] > 0)
                <span class="text-orange-600">
                    <span class="font-semibold">{{ $filterCounts['low_stock'] }}</span> Low Stock
                </span>
            @endif
        </div>
    </div>

    {{-- Bulk Actions Bar --}}
    @if(count($selectedProducts) > 0)
        <div class="bg-blue-50 border-b border-blue-200 px-6 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-blue-900">
                        {{ count($selectedProducts) }} product(s) selected
                    </span>
                    <button 
                        wire:click="deselectAll"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                    >
                        Deselect All
                    </button>
                </div>
                <div class="flex items-center space-x-2">
                    <button 
                        wire:click="bulkActivate"
                        class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded transition-colors"
                    >
                        Activate
                    </button>
                    <button 
                        wire:click="bulkDeactivate"
                        class="px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded transition-colors"
                    >
                        Deactivate
                    </button>
                    <button 
                        wire:click="exportSelected"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded transition-colors"
                    >
                        Export
                    </button>
                    <button 
                        wire:click="bulkDelete"
                        wire:confirm="Are you sure you want to delete the selected products?"
                        class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded transition-colors"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Products Content --}}
    <div class="flex-1 overflow-y-auto p-6">
        @if($products->isEmpty())
            {{-- Empty State --}}
            <div class="flex flex-col items-center justify-center h-full text-center">
                <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No products found</h3>
                <p class="text-gray-500 mb-4">
                    @if($search || $categoryFilter || $typeFilter || $stockFilter)
                        Try adjusting your filters or search terms
                    @else
                        Get started by adding your first product
                    @endif
                </p>
                @if(!$search && !$categoryFilter && !$typeFilter && !$stockFilter)
                    <a 
                        href="{{ route('products.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add Your First Product
                    </a>
                @endif
            </div>
        @else
            {{-- Grid View --}}
            @if($viewMode === 'grid')
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($products as $product)
                        <div class="bg-white rounded-lg border border-gray-200 hover:shadow-lg transition-shadow">
                            {{-- Product Image --}}
                            <div class="relative aspect-square bg-gray-100 rounded-t-lg overflow-hidden">
                                @if($product->image_url)
                                    <img 
                                        src="{{ $product->image_url }}" 
                                        alt="{{ $product->name }}"
                                        class="w-full h-full object-cover"
                                    >
                                @else
                                    <div class="flex items-center justify-center h-full">
                                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                        </svg>
                                    </div>
                                @endif

                                {{-- Selection Checkbox --}}
                                <div class="absolute top-2 left-2">
                                    <input 
                                        type="checkbox"
                                        wire:click="toggleProductSelection({{ $product->id }})"
                                        @checked(in_array($product->id, $selectedProducts))
                                        class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    >
                                </div>

                                {{-- Status Badge --}}
                                <div class="absolute top-2 right-2">
                                    @if(!$product->is_active)
                                        <span class="px-2 py-1 bg-gray-900 bg-opacity-75 text-white text-xs font-medium rounded">
                                            Inactive
                                        </span>
                                    @elseif($product->isLowStock())
                                        <span class="px-2 py-1 bg-orange-500 bg-opacity-90 text-white text-xs font-medium rounded">
                                            Low Stock
                                        </span>
                                    @elseif(!$product->isInStock())
                                        <span class="px-2 py-1 bg-red-500 bg-opacity-90 text-white text-xs font-medium rounded">
                                            Out of Stock
                                        </span>
                                    @endif
                                </div>
                            </div>

                            {{-- Product Info --}}
                            <div class="p-4">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-semibold text-gray-900 truncate">
                                            {{ $product->name }}
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            SKU: {{ $product->sku }}
                                        </p>
                                    </div>
                                </div>

                                @if($product->category)
                                    <p class="text-xs text-gray-500 mb-2">
                                        {{ $product->category->name }}
                                    </p>
                                @endif

                                <div class="flex items-center justify-between mb-3">
                                    <div>
                                        <p class="text-lg font-bold text-gray-900">
                                            ${{ number_format($product->price, 2) }}
                                        </p>
                                        @if($product->cost_price)
                                            <p class="text-xs text-gray-500">
                                                Cost: ${{ number_format($product->cost_price, 2) }}
                                            </p>
                                        @endif
                                    </div>
                                    @if($product->track_inventory)
                                        <div class="text-right">
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ $product->stock_quantity }}
                                            </p>
                                            <p class="text-xs text-gray-500">in stock</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Type Badge --}}
                                @if($product->type === 'variable')
                                    <div class="mb-3">
                                        <span class="inline-flex items-center px-2 py-1 bg-purple-100 text-purple-800 text-xs font-medium rounded">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                            </svg>
                                            {{ $product->variants->count() }} Variants
                                        </span>
                                    </div>
                                @endif

                                {{-- Actions --}}
                                <div class="flex items-center space-x-2">
                                    <a 
                                        href="{{ route('products.edit', $product) }}"
                                        class="flex-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded text-center transition-colors"
                                    >
                                        Edit
                                    </a>
                                    <a 
                                        href="{{ route('products.show', $product) }}"
                                        class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded text-center transition-colors"
                                    >
                                        View
                                    </a>
                                </div>
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('name')">
                                    <div class="flex items-center space-x-1">
                                        <span>Product</span>
                                        @if($sortBy === 'name')
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('price')">
                                    <div class="flex items-center space-x-1">
                                        <span>Price</span>
                                        @if($sortBy === 'price')
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($products as $product)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <input 
                                            type="checkbox"
                                            wire:click="toggleProductSelection({{ $product->id }})"
                                            @checked(in_array($product->id, $selectedProducts))
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                        >
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-12 w-12">
                                                @if($product->image_url)
                                                    <img 
                                                        src="{{ $product->image_url }}" 
                                                        alt="{{ $product->name }}"
                                                        class="h-12 w-12 rounded object-cover"
                                                    >
                                                @else
                                                    <div class="h-12 w-12 rounded bg-gray-100 flex items-center justify-center">
                                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $product->name }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    SKU: {{ $product->sku }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $product->category?->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            ${{ number_format($product->price, 2) }}
                                        </div>
                                        @if($product->cost_price)
                                            <div class="text-xs text-gray-500">
                                                Cost: ${{ number_format($product->cost_price, 2) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($product->track_inventory)
                                            <div class="text-sm text-gray-900">
                                                {{ $product->stock_quantity }} units
                                            </div>
                                            @if($product->isLowStock())
                                                <span class="text-xs text-orange-600 font-medium">Low Stock</span>
                                            @elseif(!$product->isInStock())
                                                <span class="text-xs text-red-600 font-medium">Out of Stock</span>
                                            @endif
                                        @else
                                            <span class="text-sm text-gray-500">Not tracked</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($product->is_active)
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                        <a 
                                            href="{{ route('products.show', $product) }}"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            View
                                        </a>
                                        <a 
                                            href="{{ route('products.edit', $product) }}"
                                            class="text-gray-600 hover:text-gray-900"
                                        >
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>