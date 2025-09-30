<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6 flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Cash Drawer Management</h2>
                <p class="mt-1 text-sm text-gray-600">Manage your cash drawer sessions</p>
            </div>
            <div class="flex gap-3">
                @if($this->hasActiveSession)
                    <button wire:click="closeDrawer" 
                            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Close Drawer
                    </button>
                @else
                    <button wire:click="openDrawer" 
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                        Open Drawer
                    </button>
                @endif
            </div>
        </div>

        <!-- Flash Messages -->
        @if (session()->has('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if (session()->has('warning'))
            <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('warning') }}</span>
            </div>
        @endif

        <!-- Active Session Stats -->
        @if($this->hasActiveSession && $this->sessionStats)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-gradient-to-r from-green-50 to-blue-50 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Active Session</h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <span class="w-2 h-2 mr-2 bg-green-400 rounded-full animate-pulse"></span>
                            Open
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <!-- Opening Amount -->
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="text-sm text-gray-600 mb-1">Opening Amount</div>
                            <div class="text-2xl font-bold text-gray-900">${{ number_format($this->sessionStats['opening_amount'], 2) }}</div>
                        </div>

                        <!-- Cash Sales -->
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="text-sm text-gray-600 mb-1">Cash Sales</div>
                            <div class="text-2xl font-bold text-green-600">${{ number_format($this->sessionStats['cash_sales'], 2) }}</div>
                        </div>

                        <!-- Cash In -->
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="text-sm text-gray-600 mb-1">Cash In</div>
                            <div class="text-2xl font-bold text-blue-600">${{ number_format($this->sessionStats['cash_in'], 2) }}</div>
                        </div>

                        <!-- Cash Out -->
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="text-sm text-gray-600 mb-1">Cash Out</div>
                            <div class="text-2xl font-bold text-red-600">${{ number_format($this->sessionStats['cash_out'], 2) }}</div>
                        </div>

                        <!-- Expected Amount -->
                        <div class="bg-white rounded-lg p-4 shadow-sm border-2 border-blue-200">
                            <div class="text-sm text-gray-600 mb-1">Expected Amount</div>
                            <div class="text-2xl font-bold text-blue-900">${{ number_format($this->sessionStats['expected_amount'], 2) }}</div>
                        </div>

                        <!-- Current Balance -->
                        <div class="bg-white rounded-lg p-4 shadow-sm border-2 border-green-200">
                            <div class="text-sm text-gray-600 mb-1">Current Balance</div>
                            <div class="text-2xl font-bold text-green-900">${{ number_format($this->sessionStats['current_balance'], 2) }}</div>
                        </div>

                        <!-- Duration -->
                        <div class="bg-white rounded-lg p-4 shadow-sm">
                            <div class="text-sm text-gray-600 mb-1">Duration</div>
                            <div class="text-2xl font-bold text-gray-900">{{ $this->sessionStats['duration'] }}</div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white rounded-lg p-4 shadow-sm flex items-center justify-center">
                            <a href="{{ route('cash-drawer.movements') }}" 
                               class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                View Movements →
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- No Active Session -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <h3 class="mt-2 text-lg font-medium text-gray-900">No Active Session</h3>
                    <p class="mt-1 text-sm text-gray-500">Open a cash drawer session to start processing transactions.</p>
                    <div class="mt-6">
                        <button wire:click="openDrawer" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            Open Cash Drawer
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Recent Sessions -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Recent Sessions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opened</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Opening</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expected</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Closing</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Difference</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($this->recentSessions as $session)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $session->opened_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $session->closed_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($session->opening_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($session->expected_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($session->closing_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($session->hasDiscrepancy())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $session->isOver() ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $session->isOver() ? '+' : '' }}${{ number_format($session->difference, 2) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            $0.00
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $session->formatted_duration }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                    No recent sessions found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Open Drawer Modal -->
    @if($showOpenModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" wire:click="$set('showOpenModal', false)"></div>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg" wire:click.stop>
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left flex-1">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900">Open Cash Drawer</h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label for="openingAmount" class="block text-sm font-medium text-gray-700">Opening Amount</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <span class="text-gray-500 sm:text-sm">$</span>
                                            </div>
                                            <input type="number" 
                                                   wire:model="openingAmount" 
                                                   id="openingAmount"
                                                   step="0.01"
                                                   class="block w-full rounded-md border-gray-300 pl-7 pr-12 focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                                   placeholder="0.00">
                                        </div>
                                        @error('openingAmount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                        <textarea wire:model="notes" 
                                                  id="notes"
                                                  rows="3"
                                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                                                  placeholder="Add any notes about this session..."></textarea>
                                        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" 
                                wire:click="confirmOpen"
                                class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 sm:ml-3 sm:w-auto">
                            Open Drawer
                        </button>
                        <button type="button" 
                                wire:click="$set('showOpenModal', false)"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Close Drawer Modal -->
    @if($showCloseModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50" wire:click="$set('showCloseModal', false)"></div>
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg" wire:click.stop>
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left flex-1">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900">Close Cash Drawer</h3>
                                
                                @if($this->sessionStats)
                                    <div class="mt-4 p-4 bg-blue-50 rounded-lg">
                                        <div class="text-sm text-gray-600 mb-2">Expected Amount</div>
                                        <div class="text-2xl font-bold text-blue-900">${{ number_format($this->sessionStats['expected_amount'], 2) }}</div>
                                    </div>
                                @endif

                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label for="closingAmount" class="block text-sm font-medium text-gray-700">Actual Closing Amount</label>
                                        <div class="mt-1 relative rounded-md shadow-sm">
                                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                <span class="text-gray-500 sm:text-sm">$</span>
                                            </div>
                                            <input type="number" 
                                                   wire:model="closingAmount" 
                                                   id="closingAmount"
                                                   step="0.01"
                                                   class="block w-full rounded-md border-gray-300 pl-7 pr-12 focus:border-red-500 focus:ring-red-500 sm:text-sm"
                                                   placeholder="0.00">
                                        </div>
                                        @error('closingAmount') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div>
                                        <label for="closeNotes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                        <textarea wire:model="notes" 
                                                  id="closeNotes"
                                                  rows="3"
                                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                                                  placeholder="Add any notes about discrepancies or issues..."></textarea>
                                        @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" 
                                wire:click="confirmClose"
                                class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">
                            Close Drawer
                        </button>
                        <button type="button" 
                                wire:click="$set('showCloseModal', false)"
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>