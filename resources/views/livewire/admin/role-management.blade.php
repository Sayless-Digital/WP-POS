<div class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Role & Permissions Management</h2>
                <p class="mt-1 text-sm text-gray-600">Manage user roles and their permissions</p>
            </div>
            <button wire:click="openCreateModal" 
                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md">
                + Add Role
            </button>
        </div>

        @if (session()->has('message'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                {{ session('error') }}
            </div>
        @endif

        <!-- Roles Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($roles as $role)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600">
                        <h3 class="text-lg font-semibold text-white">{{ ucfirst($role->name) }}</h3>
                        <p class="text-sm text-blue-100 mt-1">{{ $role->users()->count() }} users assigned</p>
                    </div>
                    
                    <div class="p-6">
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Permissions ({{ $role->permissions->count() }}):</p>
                            <div class="space-y-1 max-h-40 overflow-y-auto">
                                @forelse($role->permissions->take(5) as $permission)
                                    <div class="flex items-center text-xs text-gray-600">
                                        <svg class="w-3 h-3 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $permission->name }}
                                    </div>
                                @empty
                                    <p class="text-xs text-gray-500">No permissions assigned</p>
                                @endforelse
                                @if($role->permissions->count() > 5)
                                    <p class="text-xs text-gray-500 italic">+ {{ $role->permissions->count() - 5 }} more...</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex space-x-2 pt-4 border-t border-gray-200">
                            <button wire:click="openEditModal({{ $role->id }})" 
                                class="flex-1 px-3 py-2 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition-colors text-sm font-medium">
                                Edit
                            </button>
                            @if($role->name !== 'super-admin')
                                <button wire:click="deleteRole({{ $role->id }})" 
                                    wire:confirm="Are you sure you want to delete this role?"
                                    class="flex-1 px-3 py-2 bg-red-50 text-red-700 rounded-lg hover:bg-red-100 transition-colors text-sm font-medium">
                                    Delete
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Create/Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $editMode ? 'Edit Role' : 'Create New Role' }}
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="save">
                    <div class="p-6 space-y-6">
                        <!-- Role Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Role Name *</label>
                            <input type="text" wire:model="name" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="e.g., cashier, manager">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <!-- Permissions -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
                            <div class="space-y-4">
                                @foreach($permissions as $group => $groupPermissions)
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex justify-between items-center mb-3">
                                            <h4 class="text-sm font-semibold text-gray-900 uppercase">{{ $group }}</h4>
                                            <div class="space-x-2">
                                                <button type="button" wire:click="selectAllPermissions('{{ $group }}')" 
                                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                                    Select All
                                                </button>
                                                <button type="button" wire:click="deselectAllPermissions('{{ $group }}')" 
                                                    class="text-xs text-gray-600 hover:text-gray-800 font-medium">
                                                    Deselect All
                                                </button>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                            @foreach($groupPermissions as $permission)
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" 
                                                        wire:click="togglePermission('{{ $permission->name }}')"
                                                        {{ in_array($permission->name, $selectedPermissions) ? 'checked' : '' }}
                                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                    <span class="text-sm text-gray-700">
                                                        {{ ucfirst(str_replace(['-', '_'], ' ', explode('.', $permission->name)[1] ?? $permission->name)) }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Selected Permissions Summary -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p class="text-sm font-medium text-blue-900 mb-2">
                                Selected Permissions: {{ count($selectedPermissions) }}
                            </p>
                            @if(count($selectedPermissions) > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($selectedPermissions as $permissionName)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                            {{ $permissionName }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-blue-700">No permissions selected</p>
                            @endif
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3 sticky bottom-0 bg-white">
                        <button type="button" wire:click="closeModal" 
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md">
                            {{ $editMode ? 'Update Role' : 'Create Role' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>