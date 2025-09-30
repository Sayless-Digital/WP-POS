{{-- Category Tree Item Partial --}}
<div class="category-tree-item" style="margin-left: {{ $level * 24 }}px;">
    <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg border border-gray-200 mb-2">
        <div class="flex items-center flex-1">
            {{-- Expand/Collapse Icon --}}
            @if($category->children_count > 0)
                <button wire:click="$toggle('expanded.{{ $category->id }}')" 
                        class="mr-2 text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5 transform {{ isset($expanded[$category->id]) && $expanded[$category->id] ? 'rotate-90' : '' }}" 
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <span class="w-5 h-5 mr-2"></span>
            @endif

            {{-- Folder Icon --}}
            <svg class="w-5 h-5 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
            </svg>

            {{-- Category Info --}}
            <div class="flex-1">
                <div class="flex items-center">
                    <span class="font-medium text-gray-900">{{ $category->name }}</span>
                    
                    {{-- Status Badge --}}
                    <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $category->is_active ? 'Active' : 'Inactive' }}
                    </span>

                    {{-- Product Count --}}
                    @if($category->products_count > 0)
                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                            {{ $category->products_count }} {{ Str::plural('product', $category->products_count) }}
                        </span>
                    @endif

                    {{-- Children Count --}}
                    @if($category->children_count > 0)
                        <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                            {{ $category->children_count }} {{ Str::plural('subcategory', $category->children_count) }}
                        </span>
                    @endif
                </div>
                
                {{-- Slug --}}
                <div class="text-xs text-gray-500 mt-1">
                    {{ $category->slug }}
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center space-x-2 ml-4">
            <button wire:click="editCategory({{ $category->id }})" 
                    class="p-1 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded"
                    title="Edit">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </button>

            <button wire:click="openMoveModal({{ $category->id }})" 
                    class="p-1 text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded"
                    title="Move">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </button>

            <button wire:click="duplicateCategory({{ $category->id }})" 
                    class="p-1 text-green-600 hover:text-green-900 hover:bg-green-50 rounded"
                    title="Duplicate">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </button>

            <button wire:click="toggleStatus({{ $category->id }})" 
                    class="p-1 text-yellow-600 hover:text-yellow-900 hover:bg-yellow-50 rounded"
                    title="Toggle Status">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </button>

            <button wire:click="deleteCategory({{ $category->id }})" 
                    onclick="return confirm('Are you sure you want to delete this category?')"
                    class="p-1 text-red-600 hover:text-red-900 hover:bg-red-50 rounded"
                    title="Delete">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Children (Recursive) --}}
    @if($category->children_count > 0 && (!isset($expanded[$category->id]) || $expanded[$category->id]))
        <div class="children">
            @foreach($category->children as $child)
                @include('livewire.products.partials.category-tree-item', ['category' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>