// WP POS v1.9.0 - Reports Manager Module
// Handles reports data fetching, chart rendering, and analytics

class ReportsManager {
    constructor(stateManager, uiHelpers) {
        this.state = stateManager;
        this.ui = uiHelpers;
        this.chart = null;
    }

    /**
     * Fetch reports data from API
     * @param {String} dateRange - Date range for report (today, week, month, year, custom)
     */
    async fetchReportsData(dateRange = null) {
        try {
            const period = dateRange || this.state.getState('reports.currentPeriod') || 'today';
            const customStart = document.getElementById('custom-start-date')?.value || '';
            const customEnd = document.getElementById('custom-end-date')?.value || '';
            
            let url = `api/reports.php?period=${period}`;
            if (period === 'custom' && customStart && customEnd) {
                url += `&custom_start=${customStart}&custom_end=${customEnd}`;
            }
            
            const response = await fetch(url);
            const data = await response.json();
            
            console.log('Reports API Response:', data);
            
            if (data.success) {
                // Handle both old and new API response structures
                const responseData = data.data;
                
                // Store complete data
                this.state.updateState('reports.chartData', responseData);
                this.state.updateState('reports.summary', responseData.summary);
                
                // Handle orders - may be in data.orders or missing
                const orders = responseData.orders || [];
                this.state.updateState('reports.orders', orders);
                
                // Transform daily_data to chart format if needed
                if (responseData.daily_data && !responseData.chart_labels) {
                    console.log('Sample daily_data item:', responseData.daily_data[0]);
                    
                    // API returns: order_date, daily_revenue, daily_orders
                    const labels = responseData.daily_data.map(item => item.order_date || item.period || item.date || '');
                    const values = responseData.daily_data.map(item => parseFloat(item.daily_revenue || item.total_amount || 0));
                    const counts = responseData.daily_data.map(item => parseInt(item.daily_orders || item.order_count || 0));
                    
                    responseData.chart_labels = labels;
                    responseData.chart_values = values;
                    responseData.chart_order_counts = counts;
                    
                    console.log('Transformed - First label:', labels[0], 'First value:', values[0], 'First count:', counts[0]);
                }
                
                console.log('Processed Chart Data:', responseData);
                console.log('Summary:', responseData.summary);
                console.log('Orders:', orders);
                
                this.updateReportsDisplay();
                this.renderReportsChart();
            } else {
                console.error('Failed to fetch reports data:', data.message);
                this.ui.showToast('Failed to load reports data');
            }
        } catch (error) {
            console.error('Error fetching reports data:', error);
            this.ui.showToast('Error loading reports data');
        }
    }

    /**
     * Fetch sales data
     */
    async fetchSalesData() {
        const chartData = this.state.getState('reports.chartData');
        return chartData?.chart_values || [];
    }

    /**
     * Fetch top products
     */
    async fetchTopProducts() {
        const chartData = this.state.getState('reports.chartData');
        return chartData?.top_products || [];
    }

    /**
     * Fetch revenue data
     */
    async fetchRevenueData() {
        const summary = this.state.getState('reports.summary');
        return {
            total: summary?.total_revenue || 0,
            average: summary?.avg_order_value || 0,
            orders: summary?.total_orders || 0
        };
    }

    /**
     * Update reports display with summary data
     */
    updateReportsDisplay() {
        const summary = this.state.getState('reports.summary');
        if (!summary) return;
        
        const totalOrdersEl = document.getElementById('total-orders');
        const totalRevenueEl = document.getElementById('total-revenue');
        const avgOrderValueEl = document.getElementById('avg-order-value');
        
        if (totalOrdersEl) totalOrdersEl.textContent = (summary.total_orders || 0).toLocaleString();
        if (totalRevenueEl) totalRevenueEl.textContent = `$${(summary.total_revenue || 0).toFixed(2)}`;
        if (avgOrderValueEl) avgOrderValueEl.textContent = `$${(summary.avg_order_value || 0).toFixed(2)}`;
        
        // Update period display
        const periodType = this.state.getState('reports.currentPeriod') || 'today';
        const periodText = periodType.charAt(0).toUpperCase() + periodType.slice(1).replace('_', ' ');
        const periodRangeEl = document.getElementById('period-range');
        if (periodRangeEl) periodRangeEl.textContent = periodText;
        
        // Render orders list
        this.renderReportsOrdersList();
    }

    /**
     * Render the reports chart using Chart.js
     */
    renderReportsChart() {
        const ctx = document.getElementById('sales-chart');
        if (!ctx) {
            console.error('Chart canvas element not found');
            return;
        }
        
        // Ensure canvas has proper dimensions
        const parent = ctx.parentElement;
        if (parent) {
            ctx.width = parent.clientWidth;
            ctx.height = parent.clientHeight;
            console.log('Canvas sized:', ctx.width, 'x', ctx.height);
        }
        
        const chartData = this.state.getState('reports.chartData');
        if (!chartData) {
            console.error('No chart data available');
            return;
        }
        
        console.log('Rendering chart with data:', chartData);
        
        // Check if we have the required arrays
        if (!chartData.chart_labels || !chartData.chart_values || !chartData.chart_order_counts) {
            console.error('Missing required chart data arrays');
            return;
        }
        
        if (chartData.chart_labels.length === 0) {
            console.warn('No data points to display in chart');
            // Show empty state with placeholder chart
            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['No Data'],
                    datasets: [
                        {
                            label: 'Revenue ($)',
                            data: [0],
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Orders',
                            data: [0],
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'No data available for this period',
                            color: '#94a3b8',
                            font: {
                                size: 14
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                color: '#94a3b8'
                            },
                            grid: {
                                color: '#374151'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            ticks: {
                                color: '#94a3b8',
                                callback: function(value) {
                                    return '$' + value.toFixed(0);
                                }
                            },
                            grid: {
                                color: '#374151'
                            }
                        }
                    }
                }
            });
            
            this.state.updateState('reports.chart', this.chart);
            return;
        }
        
        // Remove empty message if it exists
        const emptyMsg = document.getElementById('chart-empty-message');
        if (emptyMsg) emptyMsg.remove();
        
        // Destroy existing chart if it exists
        if (this.chart) {
            this.chart.destroy();
        }
        
        // Check if Chart.js is loaded
        if (typeof Chart === 'undefined') {
            console.error('Chart.js library not loaded!');
            return;
        }
        
        console.log('Creating chart with:', {
            labels: chartData.chart_labels,
            values: chartData.chart_values,
            counts: chartData.chart_order_counts
        });
        
        // Log first few actual values to verify data
        console.log('First 5 labels:', chartData.chart_labels.slice(0, 5));
        console.log('First 5 values:', chartData.chart_values.slice(0, 5));
        console.log('First 5 counts:', chartData.chart_order_counts.slice(0, 5));
        console.log('Max value:', Math.max(...chartData.chart_values));
        console.log('Max count:', Math.max(...chartData.chart_order_counts));
        
        try {
            this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.chart_labels,
                datasets: [
                    {
                        label: 'Revenue ($)',
                        data: chartData.chart_values,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Orders',
                        data: chartData.chart_order_counts,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#94a3b8'
                        },
                        grid: {
                            color: '#374151'
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        ticks: {
                            color: '#94a3b8',
                            callback: function(value) {
                                return '$' + value.toFixed(0);
                            }
                        },
                        grid: {
                            color: '#374151'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        ticks: {
                            color: '#94a3b8'
                        },
                        grid: {
                            drawOnChartArea: false,
                        }
                    }
                }
            }
            });
            
            console.log('Chart created successfully:', this.chart);
            this.state.updateState('reports.chart', this.chart);
        } catch (error) {
            console.error('Error creating chart:', error);
        }
    }

    /**
     * Render the orders list for reports
     */
    renderReportsOrdersList() {
        const container = document.getElementById('reports-order-list');
        const orders = this.state.getState('reports.orders');
        
        console.log('Rendering orders list:', orders);
        
        if (!container) {
            console.error('Orders list container not found');
            return;
        }
        
        if (!orders) {
            console.error('No orders data available');
            return;
        }
        
        container.innerHTML = '';
        
        if (orders.length === 0) {
            container.innerHTML = '<div class="text-center text-slate-400 py-8">No orders found for this period</div>';
            return;
        }
        
        orders.forEach(order => {
            const orderElement = document.createElement('div');
            orderElement.className = 'grid grid-cols-12 gap-4 p-3 bg-slate-700/50 rounded-lg hover:bg-slate-700 transition-colors';
            
            const statusColor = {
                'completed': 'text-green-400',
                'processing': 'text-blue-400',
                'on-hold': 'text-yellow-400',
                'cancelled': 'text-red-400',
                'refunded': 'text-purple-400',
                'failed': 'text-red-400'
            }[order.status] || 'text-slate-400';
            
            orderElement.innerHTML = `
                <div class="col-span-2 font-mono text-sm">#${order.number}</div>
                <div class="col-span-2 text-sm">${order.date}</div>
                <div class="col-span-1 text-sm">
                    <span class="px-2 py-1 rounded text-xs ${order.source === 'POS' ? 'bg-blue-900 text-blue-300' : 'bg-green-900 text-green-300'}">${order.source}</span>
                </div>
                <div class="col-span-2 text-sm">
                    <span class="px-2 py-1 rounded text-xs ${statusColor}">${order.status}</span>
                </div>
                <div class="col-span-1 text-center text-sm">${order.item_count}</div>
                <div class="col-span-2 text-right font-mono text-sm">$${(order.total || 0).toFixed(2)}</div>
                <div class="col-span-2 text-center text-sm">${order.customer || 'Guest'}</div>
            `;
            
            container.appendChild(orderElement);
        });
    }

    /**
     * Update chart period
     * @param {String} period - Period to display (today, week, month, year, custom)
     */
    updateChartPeriod(period) {
        this.state.updateState('reports.currentPeriod', period);
        
        const customDateRange = document.getElementById('custom-date-range');
        if (customDateRange) {
            if (period === 'custom') {
                customDateRange.classList.remove('hidden');
                
                // Set default dates (last 30 days)
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(endDate.getDate() - 30);
                
                const startInput = document.getElementById('custom-start-date');
                const endInput = document.getElementById('custom-end-date');
                
                if (startInput) startInput.value = startDate.toISOString().split('T')[0];
                if (endInput) endInput.value = endDate.toISOString().split('T')[0];
            } else {
                customDateRange.classList.add('hidden');
            }
        }
        
        this.fetchReportsData();
    }

    /**
     * Generate print report
     */
    generatePrintReport() {
        const summary = this.state.getState('reports.summary');
        const orders = this.state.getState('reports.orders');
        const chartData = this.state.getState('reports.chartData');
        const periodData = chartData?.period;
        
        if (!summary || !orders) return;
        
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
                <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 8px; color: #333333;">Sales Report</h2>
                <div style="font-size: 13px; color: #666666; background: #f8f8f8; padding: 6px 12px; border-radius: 6px; display: inline-block;">
                    <span style="margin-right: 12px;"><strong>Period:</strong> ${periodDisplay}</span>
                    <span><strong>Generated:</strong> ${currentDate}</span>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;">
                <div style="padding: 10px; background: white; border-radius: 8px; border-left: 4px solid #000000; border: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: #666666; margin-bottom: 4px;">Total Orders</div>
                    <div style="font-size: 20px; font-weight: bold; color: #000000;">${summary.total_orders || 0}</div>
                    <div style="font-size: 12px; color: #666666; margin-top: 6px;">Total Revenue</div>
                    <div style="font-size: 18px; font-weight: bold; color: #000000;">$${(summary.total_revenue || 0).toFixed(2)}</div>
                </div>
                <div style="padding: 10px; background: white; border-radius: 8px; border-left: 4px solid #000000; border: 1px solid #e2e8f0;">
                    <div style="font-size: 12px; color: #666666; margin-bottom: 4px;">Average Order</div>
                    <div style="font-size: 20px; font-weight: bold; color: #000000;">$${(summary.avg_order_value || 0).toFixed(2)}</div>
                    <div style="font-size: 12px; color: #666666; margin-top: 6px;">Range</div>
                    <div style="font-size: 14px; font-weight: 600; color: #000000;">$${(summary.min_order_value || 0).toFixed(2)} - $${(summary.max_order_value || 0).toFixed(2)}</div>
                </div>
            </div>
            
            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #000000; padding: 8px; background: #f8f8f8; border-radius: 6px; border-left: 4px solid #000000;">Order Details</h3>
        `;
        
        orders.forEach(order => {
            reportContent += `
                <div style="margin-bottom: 12px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; page-break-inside: avoid;">
                    <div style="padding: 10px 12px; background: #f8f8f8;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div>
                                <div style="font-size: 16px; font-weight: bold; margin-bottom: 2px; color: #000000;">Order #${order.number}</div>
                                <div style="font-size: 12px; color: #666666;">${order.date} • ${order.customer || 'Guest'}</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="display: inline-block; padding: 3px 8px; background: #f0f0f0; border-radius: 4px; font-size: 11px; font-weight: 600; color: #333333; margin-bottom: 3px;">${order.payment_method}</div>
                                <div style="display: inline-block; padding: 3px 8px; background: #f5f5f5; border: 1px solid #cccccc; border-radius: 4px; font-size: 11px; font-weight: 600; color: #333333; text-transform: capitalize;">${order.status}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="padding: 10px; background: white; border-radius: 0 0 8px 8px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                            <thead>
                                <tr style="background: #f5f5f5;">
                                    <th style="text-align: left; padding: 6px 8px; font-weight: 600; color: #333333;">Item</th>
                                    <th style="text-align: right; padding: 6px 8px; font-weight: 600; color: #333333;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
            `;
            
            if (order.items && typeof order.items === 'object') {
                const itemsArray = Object.values(order.items);
                itemsArray.forEach((item, index) => {
                    const bgColor = index % 2 === 0 ? '#ffffff' : '#f9fafb';
                    reportContent += `
                        <tr style="background: ${bgColor};">
                            <td style="padding: 6px 8px; color: #000000;">
                                <span style="font-weight: 600; color: #000000;">${item.quantity}x</span> ${item.name}
                            </td>
                            <td style="padding: 6px 8px; text-align: right; font-weight: 500; color: #000000;">$${(item.total || 0).toFixed(2)}</td>
                        </tr>
                    `;
                });
            }
            
            reportContent += `
                            </tbody>
                        </table>
                        
                        <div style="padding: 8px; background: #f8f8f8; border-radius: 6px; margin-top: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 14px; font-weight: 600; color: #333333;">Total:</span>
                                <span style="font-size: 18px; font-weight: bold; color: #000000;">$${(order.total || 0).toFixed(2)}</span>
                            </div>
                        </div>
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
     * Export to CSV
     */
    exportToCSV() {
        const orders = this.state.getState('reports.orders');
        if (!orders || orders.length === 0) {
            this.ui.showToast('No data to export');
            return;
        }
        
        let csv = 'Order Number,Date,Source,Status,Items,Total,Customer\n';
        
        orders.forEach(order => {
            csv += `${order.number},${order.date},${order.source},${order.status},${order.item_count},${order.total},${order.customer || 'Guest'}\n`;
        });
        
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `sales-report-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
        
        this.ui.showToast('Report exported successfully');
    }

    /**
     * Export to PDF (placeholder - would need jsPDF library)
     */
    exportToPDF() {
        this.ui.showToast('PDF export not yet implemented');
    }

    /**
     * Calculate growth
     * @param {Number} current - Current period value
     * @param {Number} previous - Previous period value
     * @returns {Object} Growth data with percentage and direction
     */
    calculateGrowth(current, previous) {
        if (previous === 0) {
            return { percentage: 0, direction: 'neutral' };
        }
        
        const growth = ((current - previous) / previous) * 100;
        return {
            percentage: Math.abs(growth).toFixed(1),
            direction: growth > 0 ? 'up' : growth < 0 ? 'down' : 'neutral'
        };
    }

    /**
     * Format chart data
     * @param {Array} rawData - Raw data array
     * @returns {Object} Formatted chart data
     */
    formatChartData(rawData) {
        return {
            labels: rawData.map(d => d.label),
            values: rawData.map(d => d.value)
        };
    }

    /**
     * Aggregate data by period
     * @param {Array} data - Data to aggregate
     * @param {String} period - Period type (day, week, month)
     * @returns {Array} Aggregated data
     */
    aggregateByPeriod(data, period) {
        // Implementation depends on data structure
        return data;
    }
}

// Export for use in main.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ReportsManager;
}