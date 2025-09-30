<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Product Variants</h3>
            <p class="mt-1 text-sm text-gray-500">
                Manage variations of {{ $product->name }}
            </p>
        </div>
        <button 
            wire:click="openCreateModal"
            class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Variant
        </button>
    </div>

    {{-- Bulk Actions Bar --}}
    @if(count($selectedVariants) > 0)
        <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-blue-900">
                        {{ count($selectedVariants) }} variant(s) selected
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
                        wire:click="bulkDelete"
                        wire:confirm="Are you sure you want to delete the selected variants?"
                        class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded transition-colors"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Variants List --}}
    @if(empty($variants))
        <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No variants yet</h3>
            <p class="text-gray-500 mb-4">
                Add variations of this product with different attributes like size, color, etc.
            </p>
            <button 
                wire:click="openCreateModal"
                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add First Variant
            </button>
        </div>
    @else
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input 
                                type="checkbox"
                                wire:click="selectAllVariants"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Variant
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Attributes
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Price
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stock
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($variants as $variant)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <input 
                                    type="checkbox"
                                    wire:click="toggleVariantSelection({{ $variant['id'] }})"
                                    @checked(in_array($variant['id'], $selectedVariants))
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                >
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $variant['name'] }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    SKU: {{ $variant['sku'] }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if(!empty($variant['attributes']))
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($variant['attributes'] as $key => $value)
                                            <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded">
                                                {{ $key }}: {{ $value }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">
                                    ${{ number_format($variant['price'], 2) }}
                                </div>
                                @if($variant['cost_price'])
                                    <div class="text-xs text-gray-500">
                                        Cost: ${{ number_format($variant['cost_price'], 2) }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($product->track_inventory && isset($variant['inventory']))
                                    <div class="text-sm text-gray-900">
                                        {{ $variant['inventory']['quantity'] ?? 0 }} units
                                    </div>
                                    @php
                                        $quantity = $variant['inventory']['quantity'] ?? 0;
                                        $threshold = $variant['inventory']['low_stock_threshold'] ?? 0;
                                        $reserved = $variant['inventory']['reserved_quantity'] ?? 0;
                                    @endphp
                                    @if($quantity <= $threshold)
                                        <span class="text-xs text-orange-600 font-medium">Low Stock</span>
                                    @elseif($quantity <= $reserved)
                                        <span class="text-xs text-red-600 font-medium">Out of Stock</span>
                                    @endif
                                @else
                                    <span class="text-sm text-gray-500">Not tracked</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($variant['is_active'])
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <button 
                                        wire:click="openEditModal({{ $variant['id'] }})"
                                        class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                                    >
                                        Edit
                                    </button>
                                    <button 
                                        wire:click="duplicateVariant({{ $variant['id'] }})"
                                        class="text-gray-600 hover:text-gray-900 text-sm font-medium"
                                    >
                                        Duplicate
                                    </button>
                                    <button 
                                        wire:click="deleteVariant({{ $variant['id'] }})"
                                        wire:confirm="Are you sure you want to delete this variant?"
                                        class="text-red-600 hover:text-red-900 text-sm font-medium"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Variant Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                {{-- Background overlay --}}
                <div 
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                    wire:click="closeModal"
                ></div>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit.prevent="saveVariant">
                        {{-- Modal Header --}}
                        <div class="bg-white px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ $isEditing ? 'Edit Variant' : 'Add New Variant' }}
                                </h3>
                                <button 
                                    type="button"
                                    wire:click="closeModal"
                                    class="text-gray-400 hover:text-gray-600"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Modal Body --}}
                        <div class="bg-white px-6 py-4 space-y-4 max-h-[calc(100vh-200px)] overflow-y-auto">
                            {{-- Variant Name --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Variant Name <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text"
                                    wire:model="variant_name"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., Large Blue T-Shirt"
                                >
                                @error('variant_name') 
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
                                        <button 
                                            type="button"
                                            wire:click="generateVariantSku"
                                            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
                                        >
                                            Generate SKU
                                        </button>
                                    @endif
                                </div>
                                <input 
                                    type="text"
                                    wire:model="variant_sku"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Enter SKU"
                                >
                                @error('variant_sku') 
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Attributes --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Attributes
                                </label>
                                
                                {{-- Existing Attributes --}}
                                @if(!empty($variant_attributes))
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        @foreach($variant_attributes as $key => $value)
                                            <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                                                {{ $key }}: {{ $value }}
                                                <button 
                                                    type="button"
                                                    wire:click="removeAttribute('{{ $key }}')"
                                                    class="ml-2 text-blue-600 hover:text-blue-800"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Add Attribute --}}
                                <div class="grid grid-cols-3 gap-2">
                                    <input 
                                        type="text"
                                        wire:model="attribute_name"
                                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Name (e.g., Size)"
                                    >
                                    <input 
                                        type="text"
                                        wire:model="attribute_value"
                                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        placeholder="Value (e.g., Large)"
                                    >
                                    <button 
                                        type="button"
                                        wire:click="addAttribute"
                                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors"
                                    >
                                        Add
                                    </button>
                                </div>
                            </div>

                            {{-- Pricing --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Price <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2.5 text-gray-500">$</span>
                                        <input 
                                            type="number"
                                            wire:model="variant_price"
                                            step="0.01"
                                            min="0"
                                            class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0.00"
                                        >
                                    </div>
                                    @error('variant_price') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Cost Price
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-2.5 text-gray-500">$</span>
                                        <input 
                                            type="number"
                                            wire:model="variant_cost_price"
                                            step="0.01"
                                            min="0"
                                            class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0.00"
                                        >
                                    </div>
                                    @error('variant_cost_price') 
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Inventory --}}
                            @if($product->track_inventory)
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            {{ $isEditing ? 'Current Stock' : 'Initial Quantity' }}
                                        </label>
                                        <input 
                                            type="number"
                                            wire:model="variant_initial_quantity"
                                            min="0"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Low Stock Threshold
                                        </label>
                                        <input 
                                            type="number"
                                            wire:model="variant_low_stock_threshold"
                                            min="0"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="10"
                                        >
                                    </div>
                                </div>
                            @endif

                            {{-- Barcode --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Barcode
                                </label>
                                <input 
                                    type="text"
                                    wire:model="variant_barcode"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="Enter barcode"
                                >
                            </div>

                            {{-- Status --}}
                            <div class="flex items-center">
                                <input 
                                    type="checkbox"
                                    wire:model="variant_is_active"
                                    id="variant_is_active"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                >
                                <label for="variant_is_active" class="ml-2 text-sm font-medium text-gray-700">
                                    Variant is active
                                </label>
                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3">
                            <button 
                                type="button"
                                wire:click="closeModal"
                                class="px-4 py-2 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                            >
                                {{ $isEditing ? 'Update Variant' : 'Create Variant' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>