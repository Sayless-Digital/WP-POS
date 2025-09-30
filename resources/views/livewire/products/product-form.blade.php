<div class="h-full flex flex-col bg-gray-50">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">
                    {{ $isEditing ? 'Edit Product' : 'Add New Product' }}
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $isEditing ? 'Update product information' : 'Create a new product in your catalog' }}
                </p>
            </div>
            <button 
                wire:click="cancel"
                class="px-4 py-2 text-gray-600 hover:text-gray-900 font-medium transition-colors"
            >
                Cancel
            </button>
        </div>
    </div>

    {{-- Form Content --}}
    <div class="flex-1 overflow-y-auto p-6">
        <div class="max-w-4xl mx-auto">
            <form wire:submit.prevent="save">
                {{-- Tab Navigation --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px">
                            <button 
                                type="button"
                                wire:click="setActiveTab('basic')"
                                class="px-6 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'basic' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            >
                                Basic Information
                            </button>
                            <button 
                                type="button"
                                wire:click="setActiveTab('pricing')"
                                class="px-6 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'pricing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            >
                                Pricing & Tax
                            </button>
                            <button 
                                type="button"
                                wire:click="setActiveTab('inventory')"
                                class="px-6 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'inventory' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            >
                                Inventory
                            </button>
                            <button 
                                type="button"
                                wire:click="setActiveTab('media')"
                                class="px-6 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'media' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                            >
                                Media & Barcode
                            </button>
                        </nav>
                    </div>

                    {{-- Tab Content --}}
                    <div class="p-6">
                        {{-- Basic Information Tab --}}
                        @if($activeTab === 'basic')
                            <div class="space-y-6">
                                {{-- Product Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Product Name <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text"
                                        wire:model.blur="name"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Enter product name"
                                    >
                                    @error('name') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- SKU --}}
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <label class="block text-sm font-medium text-gray-700">
                                            SKU <span class="text-red-500">*</span>
                                        </label>
                                        @if(!$isEditing)
                                            <label class="flex items-center text-sm text-gray-600">
                                                <input 
                                                    type="checkbox"
                                                    wire:model.live="autoGenerateSku"
                                                    class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                >
                                                Auto-generate
                                            </label>
                                        @endif
                                    </div>
                                    <input 
                                        type="text"
                                        wire:model="sku"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $autoGenerateSku ? 'bg-gray-50' : '' }}"
                                        placeholder="Enter SKU"
                                        {{ $autoGenerateSku ? 'readonly' : '' }}
                                    >
                                    @error('sku') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Product Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Product Type <span class="text-red-500">*</span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors {{ $type === 'simple' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                                            <input 
                                                type="radio"
                                                wire:model="type"
                                                value="simple"
                                                class="sr-only"
                                            >
                                            <div class="flex-1">
                                                <div class="flex items-center">
                                                    <svg class="w-5 h-5 mr-2 {{ $type === 'simple' ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                    </svg>
                                                    <span class="font-medium {{ $type === 'simple' ? 'text-blue-900' : 'text-gray-900' }}">Simple Product</span>
                                                </div>
                                                <p class="mt-1 text-sm text-gray-500">A single product with no variations</p>
                                            </div>
                                        </label>

                                        <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors {{ $type === 'variable' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                                            <input 
                                                type="radio"
                                                wire:model="type"
                                                value="variable"
                                                class="sr-only"
                                            >
                                            <div class="flex-1">
                                                <div class="flex items-center">
                                                    <svg class="w-5 h-5 mr-2 {{ $type === 'variable' ? 'text-blue-600' : 'text-gray-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                                    </svg>
                                                    <span class="font-medium {{ $type === 'variable' ? 'text-blue-900' : 'text-gray-900' }}">Variable Product</span>
                                                </div>
                                                <p class="mt-1 text-sm text-gray-500">Product with variations (size, color, etc.)</p>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                {{-- Category --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Category
                                    </label>
                                    <select 
                                        wire:model="category_id"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        <option value="">Select a category</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Description --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea 
                                        wire:model="description"
                                        rows="4"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Enter product description"
                                    ></textarea>
                                    @error('description') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Status --}}
                                <div class="flex items-center">
                                    <input 
                                        type="checkbox"
                                        wire:model="is_active"
                                        id="is_active"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    >
                                    <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">
                                        Product is active and available for sale
                                    </label>
                                </div>
                            </div>
                        @endif

                        {{-- Pricing & Tax Tab --}}
                        @if($activeTab === 'pricing')
                            <div class="space-y-6">
                                <div class="grid grid-cols-2 gap-6">
                                    {{-- Price --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Selling Price <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-2.5 text-gray-500">$</span>
                                            <input 
                                                type="number"
                                                wire:model.blur="price"
                                                step="0.01"
                                                min="0"
                                                class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="0.00"
                                            >
                                        </div>
                                        @error('price') 
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    {{-- Cost Price --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Cost Price
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-3 top-2.5 text-gray-500">$</span>
                                            <input 
                                                type="number"
                                                wire:model.blur="cost_price"
                                                step="0.01"
                                                min="0"
                                                class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="0.00"
                                            >
                                        </div>
                                        @error('cost_price') 
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Profit Margin Display --}}
                                @if($profitMargin !== null)
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-sm text-gray-600 mb-1">Profit Margin</p>
                                                <p class="text-2xl font-bold {{ $profitMargin >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ number_format($profitMargin, 2) }}%
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-600 mb-1">Markup</p>
                                                <p class="text-2xl font-bold {{ $markup >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                                    {{ number_format($markup, 2) }}%
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Tax Rate --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Tax Rate (%)
                                    </label>
                                    <input 
                                        type="number"
                                        wire:model="tax_rate"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="0.00"
                                    >
                                    @error('tax_rate') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-sm text-gray-500">
                                        Price with tax: ${{ number_format($price * (1 + ($tax_rate / 100)), 2) }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        {{-- Inventory Tab --}}
                        @if($activeTab === 'inventory')
                            <div class="space-y-6">
                                {{-- Track Inventory --}}
                                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                    <input 
                                        type="checkbox"
                                        wire:model.live="track_inventory"
                                        id="track_inventory"
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    >
                                    <label for="track_inventory" class="ml-3">
                                        <span class="text-sm font-medium text-gray-900">Track inventory for this product</span>
                                        <p class="text-sm text-gray-500">Enable stock management and low stock alerts</p>
                                    </label>
                                </div>

                                @if($track_inventory)
                                    <div class="grid grid-cols-2 gap-6">
                                        {{-- Initial Quantity --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                {{ $isEditing ? 'Current Stock' : 'Initial Quantity' }}
                                            </label>
                                            <input 
                                                type="number"
                                                wire:model="initial_quantity"
                                                min="0"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="0"
                                            >
                                            @error('initial_quantity') 
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        {{-- Low Stock Threshold --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                Low Stock Threshold
                                            </label>
                                            <input 
                                                type="number"
                                                wire:model="low_stock_threshold"
                                                min="0"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="10"
                                            >
                                            @error('low_stock_threshold') 
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                            <p class="mt-1 text-sm text-gray-500">
                                                Alert when stock falls below this level
                                            </p>
                                        </div>
                                    </div>
                                @else
                                    <div class="text-center py-8 text-gray-500">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        <p>Inventory tracking is disabled for this product</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Media & Barcode Tab --}}
                        @if($activeTab === 'media')
                            <div class="space-y-6">
                                {{-- Product Image --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Product Image
                                    </label>
                                    
                                    @if($existing_image_url && !$remove_image)
                                        <div class="mb-4">
                                            <div class="relative inline-block">
                                                <img 
                                                    src="{{ $existing_image_url }}" 
                                                    alt="Current product image"
                                                    class="w-48 h-48 object-cover rounded-lg border border-gray-200"
                                                >
                                                <button 
                                                    type="button"
                                                    wire:click="removeExistingImage"
                                                    class="absolute top-2 right-2 p-1 bg-red-600 hover:bg-red-700 text-white rounded-full transition-colors"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    @if($image)
                                        <div class="mb-4">
                                            <img 
                                                src="{{ $image->temporaryUrl() }}" 
                                                alt="New product image"
                                                class="w-48 h-48 object-cover rounded-lg border border-gray-200"
                                            >
                                        </div>
                                    @endif

                                    <input 
                                        type="file"
                                        wire:model="image"
                                        accept="image/*"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    >
                                    @error('image') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-sm text-gray-500">
                                        Maximum file size: 2MB. Supported formats: JPG, PNG, GIF
                                    </p>
                                </div>

                                {{-- Barcode --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Barcode
                                    </label>
                                    <div class="grid grid-cols-3 gap-4">
                                        <div class="col-span-2">
                                            <input 
                                                type="text"
                                                wire:model="barcode"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                placeholder="Enter barcode"
                                            >
                                            @error('barcode') 
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <select 
                                                wire:model="barcode_type"
                                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            >
                                                <option value="EAN13">EAN-13</option>
                                                <option value="UPC">UPC</option>
                                                <option value="CODE128">CODE-128</option>
                                                <option value="CODE39">CODE-39</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <button 
                            type="button"
                            wire:click="cancel"
                            class="px-6 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <div class="flex items-center space-x-3">
                            @if(!$isEditing)
                                <button 
                                    type="button"
                                    wire:click="saveAndAddAnother"
                                    class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors"
                                >
                                    Save & Add Another
                                </button>
                            @endif
                            <button 
                                type="submit"
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                            >
                                {{ $isEditing ? 'Update Product' : 'Create Product' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>