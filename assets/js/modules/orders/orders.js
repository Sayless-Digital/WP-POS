// WP POS v1.9.0 - Orders Management Module
// Handles order fetching, rendering, filtering, and return processing

class OrdersManager {
    constructor(state, uiHelpers) {
        this.state = state;
        this.ui = uiHelpers;
        
        // Setup modal event listeners
        this.setupReturnModal();
    }
    
    /**
     * Setup return modal event listeners
     */
    setupReturnModal() {
        const modal = document.getElementById('return-modal');
        const cancelBtn = document.getElementById('return-modal-cancel-btn');
        const addToCartBtn = document.getElementById('return-modal-add-to-cart-btn');
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                if (modal) modal.classList.add('hidden');
                this.state.updateState('returns.fromOrderId', null);
                this.state.updateState('returns.items', []);
            });
        }
        
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', () => {
                this.handleAddReturnItemsToCart();
            });
        }
    }

    /**
     * Fetch orders from API with current filters
     * @returns {Promise<void>}
     */
    async fetchOrders() {
        const container = document.getElementById('order-list');
        container.innerHTML = this.ui.getSkeletonLoaderHtml('list-rows', 20);
        
        const filters = this.state.getState('orders.filters') || { date: 'all', status: 'all', source: 'all', customer: '' };
        const params = `date_filter=${filters.date}&status_filter=${filters.status}&source_filter=${filters.source}&customer_filter=${filters.customer}`;
        
        try {
            const response = await fetch(`/jpos/api/orders.php?${params}`);
            if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
            
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message);
            
            this.state.updateState('orders.all', result.data || []);
            this.renderOrders();
        } catch (error) {
            console.error("Error in fetchOrders:", error);
            container.innerHTML = `<p class="p-10 text-center text-red-400">Error: Could not fetch order data. ${error.message || 'Unknown error'}</p>`;
        }
    }

    /**
     * Render orders list with filtering
     */
    renderOrders() {
        const container = document.getElementById('order-list');
        container.innerHTML = '';
        
        const orders = this.state.getState('orders.all') || [];
        let filteredOrders = orders;
        
        // Apply order ID filter
        const filters = this.state.getState('orders.filters') || { orderId: '' };
        if (filters.orderId) {
            const search = filters.orderId.replace(/^#/, '').toLowerCase();
            filteredOrders = orders.filter(o =>
                o.order_number.toString().toLowerCase().includes(search)
            );
        }
        
        if (filteredOrders.length === 0) {
            container.innerHTML = `<div class="p-10 text-center text-slate-400 col-span-12">No orders match criteria.</div>`;
            return;
        }
        
        filteredOrders.forEach(order => {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 gap-4 items-center bg-slate-800 hover:bg-slate-700/50 p-3 rounded-lg text-sm';
            
            const statusColors = {
                completed: 'text-green-400',
                processing: 'text-blue-400',
                'on-hold': 'text-yellow-400',
                cancelled: 'text-red-400',
                refunded: 'text-gray-400',
                failed: 'text-red-500'
            };
            
            const sourceColor = order.source === 'POS' ? 'text-green-400' : 'text-blue-400';
            
            row.innerHTML = `
                <div class="col-span-2 font-bold">#${order.order_number}</div>
                <div class="col-span-2 text-slate-400">${this.ui.formatDateTime(order.date_created)}</div>
                <div class="col-span-1 font-semibold ${sourceColor} text-xs">${order.source}</div>
                <div class="col-span-2 font-semibold ${statusColors[order.status] || 'text-slate-300'}">${order.status.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</div>
                <div class="col-span-1 text-center">${order.item_count}</div>
                <div class="col-span-2 text-right font-mono">$${order.total}</div>
                <div class="col-span-2 text-right flex gap-2 justify-end">
                    <button class="view-receipt-btn px-3 py-1 bg-indigo-600 text-xs rounded hover:bg-indigo-500" data-order-id="${order.id}">Receipt</button>
                    ${order.status === 'completed' ? `<button class="return-order-btn px-3 py-1 bg-amber-600 text-xs rounded hover:bg-amber-500" data-order-id="${order.id}">Return</button>` : ''}
                    <button class="delete-order-btn px-3 py-1 bg-red-600 text-xs rounded hover:bg-red-500" data-order-id="${order.id}" data-order-number="${order.order_number}">Delete</button>
                </div>
            `;
            
            container.appendChild(row);
        });
        
        // Attach event listeners
        container.querySelectorAll('.view-receipt-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const orderId = parseInt(e.target.dataset.orderId);
                const orders = this.state.getState('orders.all') || [];
                const order = orders.find(o => o.id === orderId);
                if (order && window.receiptsManager) {
                    window.receiptsManager.showReceipt({
                        ...order,
                        items: order.items,
                        payment_method: order.payment_method,
                        split_payments: order.split_payments
                    });
                }
            });
        });
        
        container.querySelectorAll('.return-order-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const orderId = parseInt(e.target.dataset.orderId);
                this.openReturnModal(orderId);
            });
        });
        
        container.querySelectorAll('.delete-order-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const orderId = parseInt(e.target.dataset.orderId);
                const orderNumber = e.target.dataset.orderNumber;
                this.openDeleteOrderModal(orderId, orderNumber);
            });
        });
    }

    /**
     * Open delete order confirmation modal
     * @param {number} orderId - The order ID to delete
     * @param {string} orderNumber - The order number for display
     */
    openDeleteOrderModal(orderId, orderNumber) {
        const modal = document.getElementById('delete-order-modal');
        const orderNumberSpan = document.getElementById('delete-order-number');
        const withoutStockBtn = document.getElementById('delete-order-without-stock-btn');
        const withStockBtn = document.getElementById('delete-order-with-stock-btn');
        const cancelBtn = document.getElementById('delete-order-cancel-btn');
        
        if (orderNumberSpan) {
            orderNumberSpan.textContent = `#${orderNumber}`;
        }
        
        // Remove any existing event listeners by cloning buttons
        const newWithoutStockBtn = withoutStockBtn.cloneNode(true);
        const newWithStockBtn = withStockBtn.cloneNode(true);
        const newCancelBtn = cancelBtn.cloneNode(true);
        
        withoutStockBtn.parentNode.replaceChild(newWithoutStockBtn, withoutStockBtn);
        withStockBtn.parentNode.replaceChild(newWithStockBtn, withStockBtn);
        cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
        
        // Add new event listeners
        newWithoutStockBtn.addEventListener('click', () => {
            this.deleteOrder(orderId, false);
        });
        
        newWithStockBtn.addEventListener('click', () => {
            this.deleteOrder(orderId, true);
        });
        
        newCancelBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
        
        modal.classList.remove('hidden');
    }

    /**
     * Delete order with optional stock restoration
     * @param {number} orderId - The order ID to delete
     * @param {boolean} restoreStock - Whether to restore stock levels
     */
    async deleteOrder(orderId, restoreStock) {
        const modal = document.getElementById('delete-order-modal');
        const withoutStockBtn = document.getElementById('delete-order-without-stock-btn');
        const withStockBtn = document.getElementById('delete-order-with-stock-btn');
        
        // Disable buttons during deletion
        withoutStockBtn.disabled = true;
        withStockBtn.disabled = true;
        withoutStockBtn.textContent = 'Deleting...';
        withStockBtn.textContent = 'Deleting...';
        
        try {
            console.log('Sending DELETE request for order:', orderId, 'restore stock:', restoreStock);
            
            const response = await fetch('/jpos/api/orders.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    order_id: orderId,
                    restore_stock: restoreStock
                })
            });
            
            console.log('DELETE response status:', response.status, response.statusText);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Delete response error:', errorText);
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('DELETE API Response:', result);
            console.log('Response data type:', typeof result.data, Array.isArray(result.data) ? 'Array' : 'Object');
            
            // Check if we got an orders array instead of delete confirmation
            if (Array.isArray(result.data)) {
                console.error('ERROR: Got orders array instead of delete confirmation!');
                throw new Error('API returned wrong response - please check server configuration');
            }
            
            if (result.success) {
                // WordPress wp_send_json_success wraps data
                const message = result.data?.message || result.message || 'Order deleted successfully';
                console.log('Success! Message:', message);
                this.ui.showToast(message, 'success');
                modal.classList.add('hidden');
                
                // Refresh orders list
                console.log('Refreshing orders list...');
                await this.fetchOrders();
            } else {
                const errorMsg = result.data?.message || result.message || 'Failed to delete order';
                throw new Error(errorMsg);
            }
        } catch (error) {
            console.error('Delete order error:', error);
            this.ui.showToast(`Error: ${error.message}`, 'error');
        } finally {
            // Re-enable buttons
            withoutStockBtn.disabled = false;
            withStockBtn.disabled = false;
            withoutStockBtn.textContent = 'Delete Without Restoring Stock';
            withStockBtn.textContent = 'Delete and Restore Stock';
        }
    }

    /**
     * Open return/refund modal for an order
     * @param {number} orderId - The order ID to process return for
     */
    openReturnModal(orderId) {
        const orders = this.state.getState('orders.all') || [];
        const order = orders.find(o => o.id === orderId);
        if (!order) {
            alert('Could not find order details.');
            return;
        }
        
        const cart = this.state.getState('cart') || { items: [] };
        if (cart.items.length > 0) {
            if (!confirm('You have items in your cart. Starting a return will clear the current cart. Continue?')) {
                return;
            }
            if (window.cartManager) {
                window.cartManager.clearCart(true);
            }
        }

        this.state.updateState('returns.fromOrderId', order.id);
        this.state.updateState('returns.items', order.items.map(item => ({...item})));
        
        this.renderReturnModalItems(order.items);
        document.getElementById('return-modal').classList.remove('hidden');
    }

    /**
     * Render items in return modal
     * @private
     */
    renderReturnModalItems(items) {
        const container = document.getElementById('return-items-list');
        container.innerHTML = '';

        items.forEach(item => {
            const el = document.createElement('div');
            el.className = 'grid grid-cols-12 gap-4 items-center bg-slate-700/50 p-3 rounded-lg';
            el.dataset.productId = item.id;
            el.dataset.price = item.total / item.quantity;
            el.dataset.name = item.name;
            el.dataset.sku = item.sku;

            el.innerHTML = `
                <div class="col-span-6 font-semibold">${item.name}</div>
                <div class="col-span-2 text-slate-400">Qty: ${item.quantity}</div>
                <div class="col-span-4 flex items-center justify-end gap-2">
                    <button class="w-8 h-8 rounded bg-slate-600 hover:bg-slate-500" data-action="decrease">-</button>
                    <input type="number" value="0" min="0" max="${item.quantity}" class="w-16 text-center form-input p-1 text-sm" readonly>
                    <button class="w-8 h-8 rounded bg-slate-600 hover:bg-slate-500" data-action="increase">+</button>
                </div>
            `;
            container.appendChild(el);
        });

        container.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', e => {
                const input = e.target.parentElement.querySelector('input');
                let value = parseInt(input.value);
                const max = parseInt(input.max);
                
                if (e.target.dataset.action === 'increase' && value < max) {
                    input.value = value + 1;
                } else if (e.target.dataset.action === 'decrease' && value > 0) {
                    input.value = value - 1;
                }
                this.updateReturnModalButtonState();
            });
        });
        
        this.updateReturnModalButtonState();
    }

    /**
     * Update return modal button state based on selected quantities
     * @private
     */
    updateReturnModalButtonState() {
        const inputs = Array.from(document.querySelectorAll('#return-items-list input'));
        const hasValue = inputs.some(input => parseInt(input.value) > 0);
        document.getElementById('return-modal-add-to-cart-btn').disabled = !hasValue;
    }

    /**
     * Handle adding return items to cart
     */
    handleAddReturnItemsToCart() {
        const returnItems = this.state.getState('returns.items') || [];
        
        document.querySelectorAll('#return-items-list > div').forEach(row => {
            const quantity = parseInt(row.querySelector('input').value);
            if (quantity > 0) {
                const originalItem = returnItems.find(
                    item => item.id == row.dataset.productId
                );

                if (originalItem) {
                    // Find full product info to get image_url
                    const products = this.state.getState('products.all') || [];
                    const fullProductInfo = products.find(p => p.id === originalItem.id) ||
                        (products.find(p => p.variations && p.variations.find(v => v.id === originalItem.id))?.variations.find(v => v.id === originalItem.id));
                    
                    const itemDataForCart = {
                        ...originalItem,
                        price: parseFloat(row.dataset.price),
                        image_url: fullProductInfo ? fullProductInfo.image_url : '',
                        qty: -quantity
                    };
                    
                    if (window.cartManager) {
                        window.cartManager.addToCart(itemDataForCart, itemDataForCart.qty);
                    }
                }
            }
        });

        document.getElementById('return-modal').classList.add('hidden');
        
        if (window.routingManager) {
            window.routingManager.navigateToView('pos-page');
        }
    }

    /**
     * Search customers for filter dropdown
     * @param {string} query - Search query
     */
    async searchCustomersForFilter(query) {
        if (!query || query.length < 2) {
            this.hideCustomerFilterResults();
            return;
        }

        try {
            const nonce = document.getElementById('jpos-customer-search-nonce')?.value;
            const response = await fetch(`api/customers.php?query=${encodeURIComponent(query)}&nonce=${nonce}`);
            
            if (!response.ok) throw new Error('Search failed');
            const data = await response.json();
            
            if (data.success) {
                this.displayCustomerFilterResults(data.data.customers);
            } else {
                throw new Error(data.message || 'Search failed');
            }
        } catch (error) {
            console.error('Customer filter search error:', error);
            this.hideCustomerFilterResults();
        }
    }

    /**
     * Display customer filter search results
     * @private
     */
    displayCustomerFilterResults(customers) {
        const results = document.getElementById('order-customer-filter-results');
        if (!results) return;

        if (customers.length === 0) {
            results.innerHTML = '<div class="px-3 py-2 text-sm text-slate-400">No customers found</div>';
            results.classList.remove('hidden');
            return;
        }

        results.innerHTML = customers.map(customer => `
            <div class="px-3 py-2 text-sm text-slate-200 hover:bg-slate-600 cursor-pointer" 
                 onclick="window.ordersManager.selectCustomerForFilter(${customer.id}, '${customer.name.replace(/'/g, "\\'")}')">
                <div class="font-semibold">${customer.name}</div>
                <div class="text-xs text-slate-400">${customer.email}</div>
            </div>
        `).join('');
        
        results.classList.remove('hidden');
    }

    /**
     * Hide customer filter results
     * @private
     */
    hideCustomerFilterResults() {
        const results = document.getElementById('order-customer-filter-results');
        if (results) results.classList.add('hidden');
    }

    /**
     * Select customer for filter
     * @param {number} customerId - Customer ID
     * @param {string} customerName - Customer name
     */
    selectCustomerForFilter(customerId, customerName) {
        const input = document.getElementById('order-customer-filter');
        const clearBtn = document.getElementById('clear-customer-filter-btn');
        
        if (input) input.value = customerName;
        this.state.updateState('orders.filters.customer', customerId.toString());
        
        if (clearBtn) clearBtn.classList.remove('hidden');
        this.hideCustomerFilterResults();
        this.fetchOrders();
    }

    /**
     * Clear customer filter
     */
    clearCustomerFilter() {
        this.state.updateState('orders.filters.customer', 'all');
        const input = document.getElementById('order-customer-filter');
        const clearBtn = document.getElementById('clear-customer-filter-btn');
        
        if (input) input.value = '';
        if (clearBtn) clearBtn.classList.add('hidden');
        
        this.hideCustomerFilterResults();
        this.fetchOrders();
    }
}

// Export as singleton
window.OrdersManager = OrdersManager;