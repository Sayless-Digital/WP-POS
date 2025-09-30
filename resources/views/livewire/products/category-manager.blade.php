<div class="p-6">
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Category Manager</h2>
                <p class="mt-1 text-sm text-gray-600">Manage product categories and hierarchy</p>
            </div>
            <div class="flex space-x-3">
                <button wire:click="openModal" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Category
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

    {{-- View Mode Toggle and Filters --}}
    <div class="mb-6 bg-white rounded-lg shadow p-4">
        <div class="flex justify-between items-center mb-4">
            <div class="flex space-x-2">
                <button wire:click="$set('viewMode', 'tree')" 
                        class="px-4 py-2 rounded {{ $viewMode === 'tree' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                    Tree View
                </button>
                <button wire:click="$set('viewMode', 'list')" 
                        class="px-4 py-2 rounded {{ $viewMode === 'list' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                    List View
                </button>
            </div>
            @if(count($selectedCategories) > 0)
                <div class="flex space-x-2">
                    <button wire:click="bulkActivate" 
                            class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                        Activate ({{ count($selectedCategories) }})
                    </button>
                    <button wire:click="bulkDeactivate" 
                            class="px-3 py-1 bg-yellow-600 text-white text-sm rounded hover:bg-yellow-700">
                        Deactivate ({{ count($selectedCategories) }})
                    </button>
                    <button wire:click="bulkDelete" 
                            onclick="return confirm('Are you sure you want to delete {{ count($selectedCategories) }} categor(ies)?')"
                            class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                        Delete
                    </button>
                </div>
            @endif
        </div>

        @if($viewMode === 'list')
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                    <input type="text" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search categories..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Parent</label>
                    <select wire:model.live="parentFilter" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        <option value="root">Root Categories</option>
                        @foreach($allCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select wire:model.live="statusFilter" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
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
            <div class="mt-4">
                <button wire:click="resetFilters" 
                        class="text-sm text-gray-600 hover:text-gray-900">
                    Reset Filters
                </button>
            </div>
        @endif
    </div>

    {{-- Tree View --}}
    @if($viewMode === 'tree')
        <div class="bg-white rounded-lg shadow overflow-hidden p-6">
            @if($categoryTree->count() > 0)
                <div class="space-y-2">
                    @foreach($categoryTree as $category)
                        @include('livewire.products.partials.category-tree-item', ['category' => $category, 'level' => 0])
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                    </svg>
                    <p class="mt-2">No categories found</p>
                </div>
            @endif
        </div>
    @endif

    {{-- List View --}}
    @if($viewMode === 'list')
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
                            Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Slug
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Parent
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Products
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
                    @forelse($categories as $category)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <input type="checkbox" 
                                       wire:model.live="selectedCategories" 
                                       value="{{ $category->id }}"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $category->name }}
                                        </div>
                                        @if($category->children_count > 0)
                                            <div class="text-xs text-gray-500">
                                                {{ $category->children_count }} subcategor{{ $category->children_count === 1 ? 'y' : 'ies' }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $category->slug }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $category->parent ? $category->parent->name : 'Root' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $category->products_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="toggleStatus({{ $category->id }})"
                                        class="px-2 py-1 text-xs font-semibold rounded-full {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $category->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <button wire:click="editCategory({{ $category->id }})" 
                                            class="text-blue-600 hover:text-blue-900">
                                        Edit
                                    </button>
                                    <button wire:click="openMoveModal({{ $category->id }})" 
                                            class="text-indigo-600 hover:text-indigo-900">
                                        Move
                                    </button>
                                    <button wire:click="duplicateCategory({{ $category->id }})" 
                                            class="text-green-600 hover:text-green-900">
                                        Duplicate
                                    </button>
                                    <button wire:click="deleteCategory({{ $category->id }})" 
                                            onclick="return confirm('Are you sure you want to delete this category?')"
                                            class="text-red-600 hover:text-red-900">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                                <p class="mt-2">No categories found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $categories->links() }}
            </div>
        </div>
    @endif

    {{-- Create/Edit Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="saveCategory">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                {{ $editMode ? 'Edit Category' : 'Add New Category' }}
                            </h3>

                            <div class="space-y-4">
                                {{-- Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           wire:model.live="name" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-500 @enderror">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Slug --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Slug <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           wire:model="slug" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('slug') border-red-500 @enderror">
                                    @error('slug')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Parent Category --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Parent Category
                                    </label>
                                    <select wire:model="parentId" 
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('parentId') border-red-500 @enderror">
                                        <option value="">None (Root Category)</option>
                                        @foreach($allCategories as $cat)
                                            @if(!$editMode || $cat->id != $categoryId)
                                                <option value="{{ $cat->id }}">{{ $cat->full_path }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    @error('parentId')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Description --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Description
                                    </label>
                                    <textarea wire:model="description" 
                                              rows="3"
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                </div>

                                {{-- Sort Order --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Sort Order
                                    </label>
                                    <input type="number" 
                                           wire:model="sortOrder" 
                                           min="0"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>

                                {{-- Active Status --}}
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           wire:model="isActive" 
                                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <label class="ml-2 text-sm text-gray-700">
                                        Active
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

    {{-- Move Category Modal --}}
    @if($showMoveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeMoveModal"></div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form wire:submit.prevent="moveCategory">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                Move Category
                            </h3>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    New Parent Category
                                </label>
                                <select wire:model="newParentId" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">None (Root Category)</option>
                                    @foreach($allCategories as $cat)
                                        @if($cat->id != $moveCategoryId)
                                            <option value="{{ $cat->id }}">{{ $cat->full_path }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" 
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Move
                            </button>
                            <button type="button" 
                                    wire:click="closeMoveModal"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>