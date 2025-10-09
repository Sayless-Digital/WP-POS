// WP POS v1.9.133 - Refund & Exchange Reports Manager Module
// Handles refund/exchange reports data fetching and display

class RefundReportsManager {
    constructor(stateManager, uiHelpers) {
        this.state = stateManager;
        this.ui = uiHelpers;
    }

    /**
     * Fetch refund reports data from API
     * @param {String} dateRange - Date range for report (today, week, month, year, custom)
     */
    async fetchRefundReportsData(dateRange = null) {
        try {
            const period = dateRange || this.state.getState('refundReports.currentPeriod') || 'today';
            const customStart = document.getElementById('refund-custom-start-date')?.value || '';
            const customEnd = document.getElementById('refund-custom-end-date')?.value || '';
            
            let url = `api/refund-reports.php?period=${period}`;
            if (period === 'custom' && customStart && customEnd) {
                url += `&custom_start=${customStart}&custom_end=${customEnd}`;
            }
            
            const response = await fetch(url);
            const data = await response.json();
            
            console.log('Refund Reports API Response:', data);
            
            if (data.success) {
                const responseData = data.data;
                
                // Store complete data
                this.state.updateState('refundReports.data', responseData);
                this.state.updateState('refundReports.summary', responseData.summary);
                this.state.updateState('refundReports.refunds', responseData.refunds || []);
                
                console.log('Refund Reports Data:', responseData);
                console.log('Summary:', responseData.summary);
                console.log('Refunds:', responseData.refunds);
                
                this.updateRefundReportsDisplay();
            } else {
                console.error('Failed to fetch refund reports data:', data.message);
                this.ui.showToast('Failed to load refund reports data');
            }
        } catch (error) {
            console.error('Error fetching refund reports data:', error);
            this.ui.showToast('Error loading refund reports data');
        }
    }

    /**
     * Update refund reports display with summary data
     */
    updateRefundReportsDisplay() {
        const summary = this.state.getState('refundReports.summary');
        if (!summary) return;
        
        // Update summary statistics
        const totalRefundsEl = document.getElementById('total-refunds-count');
        const totalRefundedEl = document.getElementById('total-refunded-amount');
        const totalExchangesEl = document.getElementById('total-exchanges-count');
        const avgRefundEl = document.getElementById('avg-refund-amount');
        
        if (totalRefundsEl) totalRefundsEl.textContent = (summary.total_refunds || 0).toLocaleString();
        if (totalRefundedEl) totalRefundedEl.textContent = `$${(summary.total_refunded || 0).toFixed(2)}`;
        if (totalExchangesEl) totalExchangesEl.textContent = (summary.total_exchanges || 0).toLocaleString();
        if (avgRefundEl) avgRefundEl.textContent = `$${(summary.avg_refund_amount || 0).toFixed(2)}`;
        
        // Update period display
        const periodType = this.state.getState('refundReports.currentPeriod') || 'today';
        const periodText = periodType.charAt(0).toUpperCase() + periodType.slice(1).replace('_', ' ');
        const periodRangeEl = document.getElementById('refund-period-range');
        if (periodRangeEl) periodRangeEl.textContent = periodText;
        
        // Render refunds list
        this.renderRefundsList();
    }

    /**
     * Render the refunds list
     */
    renderRefundsList() {
        const container = document.getElementById('refunds-list');
        const refunds = this.state.getState('refundReports.refunds');
        
        console.log('Rendering refunds list:', refunds);
        
        if (!container) {
            console.error('Refunds list container not found');
            return;
        }
        
        if (!refunds) {
            console.error('No refunds data available');
            return;
        }
        
        container.innerHTML = '';
        
        if (refunds.length === 0) {
            container.innerHTML = '<div class="text-center text-slate-400 py-8">No refunds or exchanges found for this period</div>';
            return;
        }
        
        refunds.forEach(refund => {
            const refundElement = document.createElement('div');
            refundElement.className = 'grid grid-cols-12 gap-4 p-3 bg-slate-700/50 rounded-lg hover:bg-slate-700 transition-colors';
            
            const typeColor = refund.is_exchange ? 'bg-blue-900 text-blue-300' : 'bg-purple-900 text-purple-300';
            const typeText = refund.is_exchange ? 'Exchange' : 'Refund';
            
            refundElement.innerHTML = `
                <div class="col-span-2 font-mono text-sm">#${refund.refund_number}</div>
                <div class="col-span-2 text-sm">${refund.date}</div>
                <div class="col-span-1 text-sm">
                    <span class="px-2 py-1 rounded text-xs ${typeColor}">${typeText}</span>
                </div>
                <div class="col-span-2 text-sm">Order #${refund.parent_order_number}</div>
                <div class="col-span-2 text-right font-mono text-sm text-red-400">-$${(refund.amount || 0).toFixed(2)}</div>
                <div class="col-span-2 text-sm">${refund.customer || 'Guest'}</div>
                <div class="col-span-1 text-center">
                    <button onclick="window.showRefundDetails(${refund.id})" class="px-2 py-1 bg-indigo-600 hover:bg-indigo-500 rounded text-xs transition-colors">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(refundElement);
        });
    }

    /**
     * Show refund details modal
     * @param {Number} refundId - The refund ID
     */
    showRefundDetails(refundId) {
        const refunds = this.state.getState('refundReports.refunds');
        const refund = refunds.find(r => r.id === refundId);
        
        if (!refund) {
            console.error('Refund not found:', refundId);
            return;
        }
        
        const modal = document.getElementById('refund-details-modal');
        const content = document.getElementById('refund-details-content');
        
        if (!modal || !content) return;
        
        let itemsHtml = '<div class="space-y-2">';
        refund.items.forEach(item => {
            itemsHtml += `
                <div class="flex justify-between py-2 border-b border-slate-700">
                    <span class="text-slate-300">${item.quantity}x ${item.name}</span>
                    <span class="text-slate-200 font-mono">$${item.total.toFixed(2)}</span>
                </div>
            `;
        });
        itemsHtml += '</div>';
        
        content.innerHTML = `
            <div class="space-y-6">
                <!-- Header Card with Type Badge -->
                <div class="bg-gradient-to-r from-slate-700/50 to-slate-600/30 rounded-xl p-6 border border-slate-600">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="text-sm text-slate-400 mb-1">Refund Number</div>
                            <div class="text-2xl font-bold text-white">#${refund.refund_number}</div>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="px-4 py-2 rounded-lg text-sm font-semibold ${refund.is_exchange ? 'bg-blue-600 text-white' : 'bg-purple-600 text-white'}">
                                <i class="fas ${refund.is_exchange ? 'fa-exchange-alt' : 'fa-undo'} mr-2"></i>
                                ${refund.is_exchange ? 'Exchange' : 'Refund'}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-500/50">
                        <div>
                            <div class="text-xs text-slate-400 mb-1">
                                <i class="fas fa-calendar mr-1"></i>Date
                            </div>
                            <div class="text-sm font-medium text-white">${refund.date}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 mb-1">
                                <i class="fas fa-receipt mr-1"></i>Original Order
                            </div>
                            <div class="text-sm font-medium text-white">#${refund.parent_order_number}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 mb-1">
                                <i class="fas fa-user mr-1"></i>Customer
                            </div>
                            <div class="text-sm font-medium text-white">${refund.customer || 'Guest'}</div>
                        </div>
                    </div>
                </div>

                ${refund.reason ? `
                <!-- Reason Card -->
                <div class="bg-slate-700/30 rounded-lg p-4 border border-slate-600">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-900/30 flex items-center justify-center border border-amber-700">
                            <i class="fas fa-comment-alt text-amber-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <div class="text-xs font-medium text-slate-400 mb-1">Refund Reason</div>
                            <div class="text-sm text-slate-200">${refund.reason}</div>
                        </div>
                    </div>
                </div>
                ` : ''}

                <!-- Items Card -->
                <div class="bg-slate-700/30 rounded-lg border border-slate-600 overflow-hidden">
                    <div class="bg-slate-700/50 px-4 py-3 border-b border-slate-600">
                        <h4 class="text-sm font-semibold text-white flex items-center">
                            <i class="fas fa-box mr-2 text-slate-400"></i>
                            Refunded Items
                        </h4>
                    </div>
                    <div class="p-4">
                        <div class="space-y-3">
                            ${refund.items.map(item => `
                                <div class="flex justify-between items-center py-2 border-b border-slate-700/50 last:border-0">
                                    <div class="flex items-center gap-3">
                                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-indigo-900/30 flex items-center justify-center text-indigo-400 font-semibold text-sm border border-indigo-700">
                                            ${item.quantity}
                                        </span>
                                        <span class="text-slate-200">${item.name}</span>
                                    </div>
                                    <span class="text-slate-200 font-mono font-semibold">$${item.total.toFixed(2)}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>

                ${refund.is_exchange && refund.exchange_order_id ? `
                <!-- Exchange Info Card -->
                <div class="bg-gradient-to-r from-blue-900/20 to-blue-800/10 border border-blue-700/50 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-white"></i>
                        </div>
                        <div>
                            <div class="text-xs text-blue-400 font-medium mb-1">Exchange Information</div>
                            <div class="text-sm font-semibold text-white">New Order: #${refund.exchange_order_id}</div>
                        </div>
                    </div>
                </div>
                ` : ''}

                <!-- Total Card -->
                <div class="bg-gradient-to-r from-red-900/20 to-red-800/10 border border-red-700/50 rounded-lg p-5">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-red-600/30 flex items-center justify-center border border-red-700">
                                <i class="fas fa-money-bill-wave text-red-400"></i>
                            </div>
                            <span class="text-lg font-semibold text-slate-200">Total Refunded</span>
                        </div>
                        <span class="text-3xl font-bold text-red-400">-$${refund.amount.toFixed(2)}</span>
                    </div>
                </div>
            </div>
        `;
        
        modal.classList.remove('hidden');
    }

    /**
     * Update refund chart period
     * @param {String} period - Period to display (today, week, month, year, custom)
     */
    updateRefundPeriod(period) {
        this.state.updateState('refundReports.currentPeriod', period);
        
        const customDateRange = document.getElementById('refund-custom-date-range');
        if (customDateRange) {
            if (period === 'custom') {
                customDateRange.classList.remove('hidden');
                
                // Set default dates (last 30 days)
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(endDate.getDate() - 30);
                
                const startInput = document.getElementById('refund-custom-start-date');
                const endInput = document.getElementById('refund-custom-end-date');
                
                if (startInput) startInput.value = startDate.toISOString().split('T')[0];
                if (endInput) endInput.value = endDate.toISOString().split('T')[0];
            } else {
                customDateRange.classList.add('hidden');
            }
        }
        
        this.fetchRefundReportsData();
    }

    /**
     * Export refunds to CSV
     */
    exportToCSV() {
        const refunds = this.state.getState('refundReports.refunds');
        if (!refunds || refunds.length === 0) {
            this.ui.showToast('No data to export');
            return;
        }
        
        let csv = 'Refund #,Date,Type,Original Order,Amount,Customer,Reason\n';
        
        refunds.forEach(refund => {
            const type = refund.is_exchange ? 'Exchange' : 'Refund';
            const reason = (refund.reason || '').replace(/,/g, ';'); // Replace commas to avoid CSV issues
            csv += `${refund.refund_number},${refund.date},${type},${refund.parent_order_number},${refund.amount},${refund.customer || 'Guest'},"${reason}"\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `refunds-exchanges-report-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        
        this.ui.showToast('Report exported successfully');
    }
}

// Export for use in main.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RefundReportsManager;
}