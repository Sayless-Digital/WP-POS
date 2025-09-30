<div class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Tax Configuration</h2>
            <p class="mt-1 text-sm text-gray-600">Manage tax rates and settings for your POS system</p>
        </div>

        @if (session()->has('message'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
                {{ session('message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Tax Settings -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Tax Settings</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" wire:model="enableTax" 
                                class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Enable Tax</span>
                                <p class="text-xs text-gray-500">Apply tax to all transactions</p>
                            </div>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" wire:model="taxInclusive" 
                                class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Tax Inclusive Pricing</span>
                                <p class="text-xs text-gray-500">Prices include tax</p>
                            </div>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" wire:model="showTaxOnReceipt" 
                                class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Show Tax on Receipt</span>
                                <p class="text-xs text-gray-500">Display tax breakdown on receipts</p>
                            </div>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" wire:model="compoundTax" 
                                class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Compound Tax</span>
                                <p class="text-xs text-gray-500">Apply tax on tax (for multiple tax rates)</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Add New Tax Rate -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Add Tax Rate</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tax Name</label>
                                <input type="text" wire:model="newTaxName" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., VAT, GST">
                                @error('newTaxName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tax Rate</label>
                                <input type="number" wire:model="newTaxRate" step="0.01" min="0" max="100"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="0.00">
                                @error('newTaxRate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                                <select wire:model="newTaxType" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button wire:click="addTaxRate" 
                                class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all shadow-md">
                                Add Tax Rate
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tax Rates List -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Tax Rates</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Default</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($taxRates as $index => $rate)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <input type="text" wire:model="taxRates.{{ $index }}.name" 
                                                class="w-full px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="number" wire:model="taxRates.{{ $index }}.rate" step="0.01"
                                                class="w-24 px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        </td>
                                        <td class="px-6 py-4">
                                            <select wire:model="taxRates.{{ $index }}.type" 
                                                class="px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="percentage">%</option>
                                                <option value="fixed">Fixed</option>
                                            </select>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button wire:click="toggleTaxRate({{ $index }})" 
                                                class="px-3 py-1 text-xs font-medium rounded-full {{ $rate['active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                {{ $rate['active'] ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button wire:click="setDefaultTax('{{ $rate['id'] }}')" 
                                                class="text-sm {{ $defaultTaxId === $rate['id'] ? 'text-blue-600 font-medium' : 'text-gray-400 hover:text-blue-600' }}">
                                                {{ $defaultTaxId === $rate['id'] ? '★ Default' : '☆ Set Default' }}
                                            </button>
                                        </td>
                                        <td class="px-6 py-4">
                                            <button wire:click="removeTaxRate({{ $index }})" 
                                                class="text-red-600 hover:text-red-800 text-sm font-medium">
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            No tax rates configured
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center">
                    <button wire:click="resetToDefaults" 
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        Reset to Defaults
                    </button>
                    <button wire:click="save" 
                        class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-md">
                        Save Configuration
                    </button>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Tax Calculator -->
                <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-sm p-6 text-white">
                    <h3 class="text-lg font-semibold mb-4">Tax Calculator</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm opacity-90 mb-1">Amount</label>
                            <input type="number" 
                                class="w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/50"
                                placeholder="100.00">
                        </div>
                        <div>
                            <label class="block text-sm opacity-90 mb-1">Tax Rate</label>
                            <select class="w-full px-3 py-2 bg-white/20 border border-white/30 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-white/50">
                                <option value="">Select tax rate</option>
                                @foreach($taxRates as $rate)
                                    @if($rate['active'])
                                        <option value="{{ $rate['id'] }}">{{ $rate['name'] }} ({{ $rate['rate'] }}{{ $rate['type'] === 'percentage' ? '%' : '' }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="pt-3 border-t border-white/30">
                            <div class="flex justify-between items-center text-sm">
                                <span class="opacity-90">Subtotal:</span>
                                <span class="font-bold">$0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-sm mt-2">
                                <span class="opacity-90">Tax:</span>
                                <span class="font-bold">$0.00</span>
                            </div>
                            <div class="flex justify-between items-center text-lg font-bold mt-3 pt-3 border-t border-white/30">
                                <span>Total:</span>
                                <span>$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Info -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Tax Information</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Tax Rates:</span>
                            <span class="font-medium text-gray-900">{{ count($taxRates) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Active Rates:</span>
                            <span class="font-medium text-gray-900">{{ collect($taxRates)->where('active', true)->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Default Rate:</span>
                            <span class="font-medium text-gray-900">
                                @php
                                    $defaultRate = collect($taxRates)->firstWhere('id', $defaultTaxId);
                                @endphp
                                {{ $defaultRate ? $defaultRate['name'] : 'None' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Help -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h4 class="text-sm font-semibold text-blue-900 mb-2">💡 Tax Configuration Tips</h4>
                    <ul class="text-xs text-blue-800 space-y-1">
                        <li>• Set a default tax rate for quick checkout</li>
                        <li>• Use percentage for VAT/GST taxes</li>
                        <li>• Use fixed amount for flat fees</li>
                        <li>• Enable compound tax for multiple rates</li>
                        <li>• Tax inclusive pricing shows final price</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>