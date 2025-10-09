// WP POS v1.9.142 - Refund & Exchange Reports Manager Module with New Window Print
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
                    <div class="grid grid-cols-3 gap-3 pt-4 border-t border-slate-500/50">
                        <!-- Date Info Card -->
                        <div class="bg-slate-700/40 rounded-lg p-3 border border-slate-600/50">
                            <div class="flex items-center gap-2">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-900/30 flex items-center justify-center border border-blue-700/50">
                                    <i class="fas fa-calendar text-blue-400 text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs text-slate-400 mb-0.5">Date</div>
                                    <div class="text-sm font-semibold text-white truncate">${refund.date}</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Original Order Info Card -->
                        <div class="bg-slate-700/40 rounded-lg p-3 border border-slate-600/50">
                            <div class="flex items-center gap-2">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-indigo-900/30 flex items-center justify-center border border-indigo-700/50">
                                    <i class="fas fa-receipt text-indigo-400 text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs text-slate-400 mb-0.5">Original Order</div>
                                    <div class="text-sm font-semibold text-white truncate">#${refund.parent_order_number}</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Customer Info Card -->
                        <div class="bg-slate-700/40 rounded-lg p-3 border border-slate-600/50">
                            <div class="flex items-center gap-2">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-900/30 flex items-center justify-center border border-emerald-700/50">
                                    <i class="fas fa-user text-emerald-400 text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs text-slate-400 mb-0.5">Customer</div>
                                    <div class="text-sm font-semibold text-white truncate">${refund.customer || 'Guest'}</div>
                                </div>
                            </div>
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
     * Generate print report for refunds
     */
    generatePrintReport() {
        const summary = this.state.getState('refundReports.summary');
        const refunds = this.state.getState('refundReports.refunds');
        const reportData = this.state.getState('refundReports.data');
        const periodData = reportData?.period;
        
        if (!summary || !refunds) return;
        
        const settings = this.state.getState('settings');
        const storeName = settings?.name || 'Store';
        const currentDate = new Date().toLocaleDateString();
        
        // Format period display
        let periodDisplay = '';
        if (periodData) {
            const startDate = new Date(periodData.start);
            const endDate = new Date(periodData.end);
            
            if (startDate.toDateString() === endDate.toDateString()) {
                periodDisplay = startDate.toLocaleDateString();
            } else {
                periodDisplay = `${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()}`;
            }
        }
        
        let reportContent = `
            <div style="text-align: center; margin-bottom: 16px; padding: 12px; background: white; border-radius: 8px; border: 2px solid #e2e8f0;">
                <h1 style="font-size: 24px; font-weight: bold; margin-bottom: 4px; color: #000000;">${storeName}</h1>
                <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #333333;">Refunds & Exchanges Report</h2>
                <div style="font-size: 13px; color: #666666; background: #f8f8f8; padding: 6px 12px; border-radius: 6px; display: inline-block;">
                    <span style="margin-right: 12px;"><strong>Period:</strong> ${periodDisplay}</span>
                    <span><strong>Generated:</strong> ${currentDate}</span>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 16px;">
                <div style="padding: 10px; background: white; border-radius: 8px; border-left: 4px solid #9333ea; border: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: #666666; margin-bottom: 4px;">Total Refunds</div>
                    <div style="font-size: 20px; font-weight: bold; color: #000000;">${summary.total_refunds || 0}</div>
                    <div style="font-size: 12px; color: #666666; margin-top: 6px;">Total Amount</div>
                    <div style="font-size: 18px; font-weight: bold; color: #dc2626;">-$${(summary.total_refunded || 0).toFixed(2)}</div>
                </div>
                <div style="padding: 10px; background: white; border-radius: 8px; border-left: 4px solid #2563eb; border: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: #666666; margin-bottom: 4px;">Total Exchanges</div>
                    <div style="font-size: 20px; font-weight: bold; color: #000000;">${summary.total_exchanges || 0}</div>
                </div>
                <div style="padding: 10px; background: white; border-radius: 8px; border-left: 4px solid #000000; border: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: #666666; margin-bottom: 4px;">Average Refund</div>
                    <div style="font-size: 20px; font-weight: bold; color: #000000;">$${(summary.avg_refund_amount || 0).toFixed(2)}</div>
                </div>
            </div>
            
            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #000000; padding: 8px; background: #f8f8f8; border-radius: 6px; border-left: 4px solid #000000;">Refund & Exchange Details</h3>
        `;
        
        refunds.forEach(refund => {
            const isExchange = refund.is_exchange;
            const typeColor = isExchange ? '#2563eb' : '#9333ea';
            const typeText = isExchange ? 'Exchange' : 'Refund';
            const typeIcon = isExchange ? '⇄' : '↶';
            
            reportContent += `
                <div style="margin-bottom: 12px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; page-break-inside: avoid;">
                    <div style="padding: 10px 12px; background: #f8f8f8;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div style="font-size: 16px; font-weight: bold; margin-bottom: 2px; color: #000000;">Refund #${refund.refund_number}</div>
                                <div style="font-size: 12px; color: #666666;">${refund.date} • ${refund.customer || 'Guest'}</div>
                                <div style="font-size: 12px; color: #666666; margin-top: 2px;">Original Order: #${refund.parent_order_number}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="display: inline-block; padding: 4px 10px; background: ${typeColor}; color: white; border-radius: 4px; font-size: 11px; font-weight: 600; margin-bottom: 4px;">
                                    ${typeIcon} ${typeText}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding: 10px; background: white; border-radius: 0 0 8px 8px;">
                        ${refund.reason ? `
                        <div style="padding: 8px; background: #fef3c7; border-left: 3px solid #f59e0b; border-radius: 4px; margin-bottom: 10px;">
                            <div style="font-size: 11px; font-weight: 600; color: #92400e; margin-bottom: 2px;">REASON</div>
                            <div style="font-size: 12px; color: #78350f;">${refund.reason}</div>
                        </div>
                        ` : ''}
                        
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <thead>
                                <tr style="background: #f5f5f5;">
                                    <th style="text-align: left; padding: 6px 8px; font-weight: 600; color: #333333;">Item</th>
                                    <th style="text-align: center; padding: 6px 8px; font-weight: 600; color: #333333;">Qty</th>
                                    <th style="text-align: right; padding: 6px 8px; font-weight: 600; color: #333333;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            if (refund.items && Array.isArray(refund.items)) {
                refund.items.forEach((item, index) => {
                    const bgColor = index % 2 === 0 ? '#ffffff' : '#f9fafb';
                    reportContent += `
                        <tr style="background: ${bgColor};">
                            <td style="padding: 6px 8px; color: #000000;">${item.name}</td>
                            <td style="padding: 6px 8px; text-align: center; font-weight: 600; color: #000000;">${item.quantity}</td>
                            <td style="padding: 6px 8px; text-align: right; font-weight: 500; color: #000000;">$${(item.total || 0).toFixed(2)}</td>
                        </tr>
                    `;
                });
            }
            
            reportContent += `
                            </tbody>
                        </table>
                        
                        <div style="padding: 8px; background: #fee2e2; border-radius: 6px; margin-top: 8px; border: 1px solid #fca5a5;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 14px; font-weight: 600; color: #991b1b;">Total Refunded:</span>
                                <span style="font-size: 18px; font-weight: bold; color: #dc2626;">-$${(refund.amount || 0).toFixed(2)}</span>
                            </div>
                        </div>
                        
                        ${isExchange && refund.exchange_order_id ? `
                        <div style="padding: 8px; background: #dbeafe; border-radius: 6px; margin-top: 8px; border: 1px solid #93c5fd;">
                            <div style="font-size: 11px; font-weight: 600; color: #1e40af; margin-bottom: 2px;">EXCHANGE ORDER</div>
                            <div style="font-size: 13px; font-weight: bold; color: #1e3a8a;">#${refund.exchange_order_id}</div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        const printContent = document.getElementById('print-report-content');
        if (printContent) {
            printContent.innerHTML = reportContent;
        }
    }

    /**
     * Print report in new window (like receipts)
     */
    printReport() {
        const content = document.getElementById('print-report-content').innerHTML;
        const printWindow = window.open('', '', 'height=800,width=900');
        
        printWindow.document.write('<html><head><title>Print Report</title>');
        printWindow.document.write(`
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    font-size: 12px;
                    margin: 20px;
                    color: #000;
                }
                * { box-sizing: border-box; }
                h1, h2, h3 { color: #000; margin: 0; }
                table { border-collapse: collapse; width: 100%; }
                th, td { padding: 8px; text-align: left; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                @media print {
                    body { margin: 0.5in; }
                }
            </style>
        `);
        printWindow.document.write('</head><body>');
        printWindow.document.write(content);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
            printWindow.close();
        }, 500);
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