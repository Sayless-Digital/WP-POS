<div class="p-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Barcode Manager</h2>
                <p class="mt-1 text-sm text-gray-600">Manage product and variant barcodes</p>
            </div>
            <div class="flex space-x-3">
                <button wire:click="openGenerateModal" 
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Generate Barcodes
                </button>
                <button wire:click="openModal" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Barcode
                </button>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Search and Filters --}}
    <div class="mb-6 bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search" 
                       placeholder="Search barcodes..."
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select wire:model.live="typeFilter" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Types</option>
                    <option value="EAN13">EAN-13</option>
                    <option value="EAN8">EAN-8</option>
                    <option value="UPC">UPC</option>
                    <option value="CODE128">CODE-128</option>
                    <option value="CODE39">CODE-39</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Entity</label>
                <select wire:model.live="entityFilter" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Entities</option>
                    <option value="product">Products</option>
                    <option value="variant">Variants</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Per Page</label>
                <select wire:model.live="perPage" 
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>
        <div class="mt-4 flex justify-between items-center">
            <button wire:click="resetFilters" 
                    class="text-sm text-gray-600 hover:text-gray-900">
                Reset Filters
            </button>
            @if(count($selectedBarcodes) > 0)
                <div class="flex space-x-2">
                    <button wire:click="openPrintModal" 
                            class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                        Print Selected ({{ count($selectedBarcodes) }})
                    </button>
                    <button wire:click="bulkDelete" 
                            onclick="return confirm('Are you sure you want to delete {{ count($selectedBarcodes) }} barcode(s)?')"
                            class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                        Delete Selected
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Barcodes Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left">
                        <input type="checkbox" 
                               wire:model.live="selectAll"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Barcode
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Type
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Entity
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Created
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($barcodes as $barcode)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <input type="checkbox" 
                                   wire:model.live="selectedBarcodes" 
                                   value="{{ $barcode->id }}"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="font-mono text-sm font-medium text-gray-900">
                                    {{ $barcode->barcode }}
                                </div>
                                @if($barcode->is_primary)
                                    <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Primary
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                {{ $barcode->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($barcode->barcodeable)
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900">
                                        {{ $barcode->barcodeable->name ?? 'N/A' }}
                                    </div>
                                    <div class="text-gray-500">
                                        {{ class_basename($barcode->barcodeable_type) }}
                                    </div>
                                </div>
                            @else
                                <span class="text-sm text-gray-500">Unassigned</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($barcode->isValidFormat())
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Valid
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    Invalid
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $barcode->created_at->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <button wire:click="editBarcode({{ $barcode->id }})" 
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                Edit
                            </button>
                            <button wire:click="deleteBarcode({{ $barcode->id }})" 
                                    onclick="return confirm('Are you sure you want to delete this barcode?')"
                                    class="text-red-600 hover:text-red-900">
                                Delete
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                            </svg>
                            <p class="mt-2">No barcodes found</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $barcodes->links() }}
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="saveBarcode">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                {{ $editMode ? 'Edit Barcode' : 'Add New Barcode' }}
                            </h3>

                            <div class="space-y-4">
                                {{-- Barcode --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Barcode <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           wire:model="barcode" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('barcode') border-red-500 @enderror">
                                    @error('barcode')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Barcode Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Type <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="barcodeType" 
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('barcodeType') border-red-500 @enderror">
                                        <option value="">Select Type</option>
                                        <option value="EAN13">EAN-13</option>
                                        <option value="EAN8">EAN-8</option>
                                        <option value="UPC">UPC</option>
                                        <option value="CODE128">CODE-128</option>
                                        <option value="CODE39">CODE-39</option>
                                    </select>
                                    @error('barcodeType')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Entity Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Entity Type <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model.live="entityType" 
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('entityType') border-red-500 @enderror">
                                        <option value="">Select Entity Type</option>
                                        <option value="product">Product</option>
                                        <option value="variant">Variant</option>
                                    </select>
                                    @error('entityType')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Entity Selection --}}
                                @if($entityType)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ $entityType === 'product' ? 'Product' : 'Variant' }} <span class="text-red-500">*</span>
                                        </label>
                                        <select wire:model="entityId" 
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('entityId') border-red-500 @enderror">
                                            <option value="">Select {{ $entityType === 'product' ? 'Product' : 'Variant' }}</option>
                                            @if($entityType === 'product')
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                @endforeach
                                            @else
                                                @foreach($variants as $variant)
                                                    <option value="{{ $variant->id }}">
                                                        {{ $variant->product->name }} - {{ $variant->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('entityId')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif

                                {{-- Primary Barcode --}}
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           wire:model="isPrimary" 
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <label class="ml-2 text-sm text-gray-700">
                                        Set as primary barcode
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                {{ $editMode ? 'Update' : 'Create' }}
                            </button>
                            <button type="button" 
                                    wire:click="closeModal"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Generate Barcodes Modal --}}
    @if($showGenerateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeGenerateModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="generateBarcodes">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Generate Barcodes
                            </h3>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Number of Barcodes <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" 
                                           wire:model="generateCount" 
                                           min="1" 
                                           max="100"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <p class="mt-1 text-xs text-gray-500">Maximum 100 barcodes at once</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Barcode Type <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="generateType" 
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="EAN13">EAN-13</option>
                                        <option value="EAN8">EAN-8</option>
                                        <option value="UPC">UPC</option>
                                        <option value="CODE128">CODE-128</option>
                                        <option value="CODE39">CODE-39</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Prefix (Optional)
                                    </label>
                                    <input type="text" 
                                           wire:model="generatePrefix" 
                                           maxlength="5"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <p class="mt-1 text-xs text-gray-500">Add a prefix to generated barcodes (max 5 characters)</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Generate
                            </button>
                            <button type="button" 
                                    wire:click="closeGenerateModal"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Print Modal --}}
    @if($showPrintModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closePrintModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                Print Barcodes
                            </h3>
                            <button wire:click="print" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                Print
                            </button>
                        </div>

                        {{-- Print Settings --}}
                        <div class="mb-4 grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Layout</label>
                                <select wire:model.live="printLayout" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="grid">Grid</option>
                                    <option value="list">List</option>
                                    <option value="labels">Labels</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Size</label>
                                <select wire:model.live="printSize" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="small">Small</option>
                                    <option value="medium">Medium</option>
                                    <option value="large">Large</option>
                                </select>
                            </div>
                            <div class="flex items-end space-x-4">
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model.live="includeProductName" class="rounded">
                                    <span class="ml-2 text-sm">Name</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" wire:model.live="includePrice" class="rounded">
                                    <span class="ml-2 text-sm">Price</span>
                                </label>
                            </div>
                        </div>

                        {{-- Print Preview --}}
                        <div id="print-area" class="border border-gray-300 rounded p-4 bg-white max-h-96 overflow-y-auto">
                            <div class="grid {{ $printLayout === 'grid' ? 'grid-cols-3' : 'grid-cols-1' }} gap-4">
                                @foreach($printBarcodes as $barcode)
                                    <div class="border border-gray-200 p-3 text-center {{ $printSize === 'small' ? 'text-xs' : ($printSize === 'large' ? 'text-lg' : 'text-sm') }}">
                                        @if($includeProductName && isset($barcode['barcodeable']['name']))
                                            <div class="font-medium mb-1">{{ $barcode['barcodeable']['name'] }}</div>
                                        @endif
                                        <div class="font-mono font-bold">{{ $barcode['barcode'] }}</div>
                                        <div class="text-xs text-gray-500 mt-1">{{ $barcode['type'] }}</div>
                                        @if($includePrice && isset($barcode['barcodeable']['selling_price']))
                                            <div class="font-semibold mt-1">${{ number_format($barcode['barcodeable']['selling_price'], 2) }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="closePrintModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Print Script --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('print-barcodes', () => {
                const printArea = document.getElementById('print-area');
                const printWindow = window.open('', '', 'height=600,width=800');
                printWindow.document.write('<html><head><title>Print Barcodes</title>');
                printWindow.document.write('<style>body{font-family:sans-serif;padding:20px;}@media print{body{padding:0;}}</style>');
                printWindow.document.write('</head><body>');
                printWindow.document.write(printArea.innerHTML);
                printWindow.document.write('</body></html>');
                printWindow.document.close();
                printWindow.print();
            });
        });
    </script>
</div>