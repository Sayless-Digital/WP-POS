<div class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">WooCommerce Sync Settings</h2>
            <p class="mt-1 text-sm text-gray-600">Configure WooCommerce integration and synchronization</p>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Settings -->
            <div class="lg:col-span-2 space-y-6">
                <form wire:submit.prevent="save">
                    <!-- Connection Settings -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Connection Settings</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">WooCommerce URL *</label>
                                <input type="url" wire:model="woocommerceUrl" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="https://yourstore.com">
                                @error('woocommerceUrl') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Consumer Key *</label>
                                <input type="text" wire:model="consumerKey" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="ck_xxxxxxxxxxxxx">
                                @error('consumerKey') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Consumer Secret *</label>
                                <input type="password" wire:model="consumerSecret" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="cs_xxxxxxxxxxxxx">
                                @error('consumerSecret') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="flex items-center justify-between pt-4">
                                <button type="button" wire:click="testConnection" 
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors disabled:opacity-50">
                                    <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                                    <span wire:loading wire:target="testConnection">Testing...</span>
                                </button>

                                @if($connectionStatus === 'success')
                                    <span class="text-green-600 text-sm font-medium">✓ Connected</span>
                                @elseif($connectionStatus === 'error')
                                    <span class="text-red-600 text-sm font-medium">✗ Connection Failed</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Sync Settings -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Sync Settings</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" wire:model="syncEnabled" 
                                    class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700">Enable Synchronization</span>
                            </label>

                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" wire:model="autoSync" 
                                    class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm font-medium text-gray-700">Enable Auto Sync</span>
                            </label>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Sync Interval (minutes) *</label>
                                <input type="number" wire:model="syncInterval" min="5" max="1440"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @error('syncInterval') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div class="pt-4 border-t border-gray-200">
                                <p class="text-sm font-medium text-gray-700 mb-3">Sync Options:</p>
                                <div class="space-y-2">
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" wire:model="syncProducts" 
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Sync Products</span>
                                    </label>

                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" wire:model="syncOrders" 
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Sync Orders</span>
                                    </label>

                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" wire:model="syncCustomers" 
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Sync Customers</span>
                                    </label>

                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="checkbox" wire:model="syncInventory" 
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Sync Inventory</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end">
                        <button type="submit" 
                            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md">
                            Save Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Sync Status -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Sync Status</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <p class="text-sm text-gray-600 mb-1">Last Sync</p>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $lastSyncTime ? $lastSyncTime->diffForHumans() : 'Never' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600 mb-1">Queued Items</p>
                            <p class="text-sm font-medium text-gray-900">{{ $queuedItems }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-600 mb-1">Failed Items</p>
                            <p class="text-sm font-medium text-red-600">{{ $failedItems }}</p>
                        </div>

                        <div class="pt-4 space-y-2">
                            <button wire:click="syncNow" 
                                wire:loading.attr="disabled"
                                class="w-full px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all disabled:opacity-50">
                                <span wire:loading.remove wire:target="syncNow">Sync Now</span>
                                <span wire:loading wire:target="syncNow">Syncing...</span>
                            </button>

                            <button wire:click="clearSyncQueue" 
                                class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                Clear Queue
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-sm p-6 text-white">
                    <h3 class="text-lg font-semibold mb-4">Sync Statistics</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm opacity-90">Total Syncs</span>
                            <span class="text-lg font-bold">{{ $syncLogs->count() }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm opacity-90">Success Rate</span>
                            <span class="text-lg font-bold">
                                {{ $syncLogs->count() > 0 ? round(($syncLogs->where('status', 'success')->count() / $syncLogs->count()) * 100) : 0 }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sync Logs -->
        <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Recent Sync Logs</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($syncLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $log->created_at->format('M d, H:i') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ $log->model_type }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ ucfirst($log->action) }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($log->status === 'success')
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">
                                            Success
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">
                                            Failed
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ Str::limit($log->message, 50) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    No sync logs available
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>