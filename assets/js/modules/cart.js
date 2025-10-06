/**
 * JPOS Cart Module
 * Handles shopping cart management, checkout, and payment processing
 */

class CartManager {
    constructor(stateManager) {
        this.stateManager = stateManager;
    }

    /**
     * Render cart items
     */
    renderCart() {
        const cartItems = this.stateManager.getState('cart.items');
        const container = document.getElementById('cart-items');
        
        if (!container) return;

        if (cartItems.length === 0) {
            container.innerHTML = '<div class="text-center text-slate-400 py-8">Cart is empty</div>';
            this.updateCartTotal();
            return;
        }

        container.innerHTML = cartItems.map(item => this.renderCartItem(item)).join('');
        this.updateCartTotal();
    }

    /**
     * Render individual cart item
     * @param {Object} item - Cart item
     * @returns {string} HTML for cart item
     */
    renderCartItem(item) {
        return `
            <div class="bg-slate-700 rounded-lg p-3 flex items-center justify-between">
                <div class="flex-1">
                    <h4 class="font-semibold text-slate-200">${item.name}</h4>
                    <p class="text-sm text-slate-400">$${item.price.toFixed(2)} each</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="cartManager.updateQuantity(${item.id}, -1)" 
                            class="w-8 h-8 bg-slate-600 text-white rounded-full hover:bg-slate-500">
                        -
                    </button>
                    <span class="w-8 text-center text-slate-200">${item.qty}</span>
                    <button onclick="cartManager.updateQuantity(${item.id}, 1)" 
                            class="w-8 h-8 bg-slate-600 text-white rounded-full hover:bg-slate-500">
                        +
                    </button>
                    <button onclick="cartManager.removeItem(${item.id})" 
                            class="ml-2 text-red-400 hover:text-red-300">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Update item quantity in cart
     * @param {number} itemId - Item ID
     * @param {number} change - Quantity change (+1 or -1)
     */
    updateQuantity(itemId, change) {
        const cartItems = this.stateManager.getState('cart.items');
        const item = cartItems.find(item => item.id === itemId);
        
        if (!item) return;

        item.qty += change;
        
        if (item.qty <= 0) {
            this.removeItem(itemId);
        } else {
            this.stateManager.updateState('cart.items', cartItems);
            this.renderCart();
        }
    }

    /**
     * Remove item from cart
     * @param {number} itemId - Item ID to remove
     */
    removeItem(itemId) {
        const cartItems = this.stateManager.getState('cart.items');
        const filteredItems = cartItems.filter(item => item.id !== itemId);
        this.stateManager.updateState('cart.items', filteredItems);
        this.renderCart();
    }

    /**
     * Update cart total display
     */
    updateCartTotal() {
        const cartItems = this.stateManager.getState('cart.items');
        const subtotal = cartItems.reduce((sum, item) => sum + (item.price * item.qty), 0);
        const feeDiscount = this.stateManager.getState('cart.feeDiscount');
        
        let total = subtotal;
        if (feeDiscount && feeDiscount.type && feeDiscount.amount) {
            const amount = parseFloat(feeDiscount.amount);
            if (feeDiscount.type === 'discount') {
                total -= amount;
            } else if (feeDiscount.type === 'fee') {
                total += amount;
            }
        }

        const subtotalElement = document.getElementById('cart-subtotal');
        const totalElement = document.getElementById('cart-total');
        
        if (subtotalElement) subtotalElement.textContent = `$${subtotal.toFixed(2)}`;
        if (totalElement) totalElement.textContent = `$${Math.max(0, total).toFixed(2)}`;
    }

    /**
     * Clear cart
     * @param {boolean} fullReset - Whether to reset all cart state
     */
    clearCart(fullReset = false) {
        this.stateManager.updateState('cart.items', []);
        
        if (fullReset) {
            this.stateManager.updateState('cart.customer', null);
            this.stateManager.updateState('cart.paymentMethod', 'Cash');
            this.stateManager.updateState('cart.fee', { amount: '', label: '', amountType: 'flat' });
            this.stateManager.updateState('cart.discount', { amount: '', label: '', amountType: 'flat' });
            this.stateManager.updateState('cart.feeDiscount', { type: null, amount: '', label: '', amountType: 'flat' });
            this.stateManager.updateState('cart.splitPayments', null);
        }
        
        this.renderCart();
        this.renderCustomerDisplay();
    }

    /**
     * Show fee/discount modal
     * @param {string} type - 'fee' or 'discount'
     */
    showFeeDiscountModal(type) {
        const modal = document.getElementById('fee-discount-modal');
        if (!modal) return;

        this.stateManager.updateState('cart.feeDiscount.type', type);
        
        // Update modal title and inputs
        const title = document.querySelector('#fee-discount-modal h2');
        const amountLabel = document.querySelector('#fee-discount-modal label[for="amount"]');
        
        if (title) title.textContent = `Add ${type === 'fee' ? 'Fee' : 'Discount'}`;
        if (amountLabel) amountLabel.textContent = `${type === 'fee' ? 'Fee' : 'Discount'} Amount`;
        
        modal.classList.remove('hidden');
    }

    /**
     * Hide fee/discount modal
     */
    hideFeeDiscountModal() {
        const modal = document.getElementById('fee-discount-modal');
        if (modal) modal.classList.add('hidden');
    }

    /**
     * Apply fee or discount
     */
    applyFeeDiscount() {
        const type = this.stateManager.getState('cart.feeDiscount.type');
        const amount = document.getElementById('fee-amount').value;
        const label = document.getElementById('fee-label').value;
        const amountType = document.querySelector('input[name="amount-type"]:checked')?.value || 'flat';

        if (!amount || parseFloat(amount) <= 0) {
            alert('Please enter a valid amount');
            return;
        }

        const feeDiscount = {
            type: type,
            amount: amount,
            label: label || `${type === 'fee' ? 'Fee' : 'Discount'} - $${amount}`,
            amountType: amountType
        };

        this.stateManager.updateState('cart.feeDiscount', feeDiscount);
        this.hideFeeDiscountModal();
        this.updateCartTotal();
    }

    /**
     * Remove fee/discount
     */
    removeFeeDiscount() {
        this.stateManager.updateState('cart.feeDiscount', { type: null, amount: '', label: '', amountType: 'flat' });
        this.updateCartTotal();
    }

    /**
     * Process transaction/checkout
     */
    async processTransaction() {
        const cartItems = this.stateManager.getState('cart.items');
        const paymentMethod = this.stateManager.getState('cart.paymentMethod');
        const feeDiscount = this.stateManager.getState('cart.feeDiscount');
        const splitPayments = this.stateManager.getState('cart.splitPayments');

        if (cartItems.length === 0) {
            alert('Cart is empty');
            return;
        }

        try {
            const payload = {
                cart_items: cartItems,
                payment_method: paymentMethod,
                fee_discount: feeDiscount.type ? feeDiscount : null,
                nonce: this.stateManager.getState('nonces.checkout')
            };

            if (splitPayments && splitPayments.length > 1) {
                payload.split_payments = splitPayments;
            }

            const response = await fetch('/jpos/api/checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({message: `Server responded with ${response.status}`}));
                throw new Error(errorData.message);
            }

            const result = await response.json();
            
            if (result.success) {
                // Show receipt
                if (window.receiptManager) {
                    window.receiptManager.showReceipt(result.data.receipt_data);
                }
                
                // Clear cart
                this.clearCart();
                
                // Show success message
                if (window.toastManager) {
                    window.toastManager.show('Transaction completed successfully!');
                }
            } else {
                throw new Error(result.message || 'Transaction failed');
            }
        } catch (error) {
            console.error('Transaction error:', error);
            alert('Transaction failed: ' + error.message);
        }
    }

    /**
     * Save cart state to localStorage
     */
    saveCartState() {
        const cartData = {
            items: this.stateManager.getState('cart.items'),
            customer: this.stateManager.getState('cart.customer'),
            paymentMethod: this.stateManager.getState('cart.paymentMethod'),
            feeDiscount: this.stateManager.getState('cart.feeDiscount')
        };
        localStorage.setItem('jpos_cart_state', JSON.stringify(cartData));
    }

    /**
     * Load cart state from localStorage
     */
    loadCartState() {
        try {
            const saved = localStorage.getItem('jpos_cart_state');
            if (saved) {
                const cartData = JSON.parse(saved);
                this.stateManager.updateState('cart.items', cartData.items || []);
                this.stateManager.updateState('cart.customer', cartData.customer || null);
                this.stateManager.updateState('cart.paymentMethod', cartData.paymentMethod || 'Cash');
                this.stateManager.updateState('cart.feeDiscount', cartData.feeDiscount || { type: null, amount: '', label: '', amountType: 'flat' });
                this.renderCart();
                this.renderCustomerDisplay();
            }
        } catch (error) {
            console.error('Failed to load cart state:', error);
        }
    }

    /**
     * Hold current cart
     */
    holdCurrentCart() {
        const cartItems = this.stateManager.getState('cart.items');
        if (cartItems.length === 0) return;

        const heldCarts = JSON.parse(localStorage.getItem('jpos_held_carts') || '[]');
        const cartData = {
            id: Date.now(),
            timestamp: new Date().toISOString(),
            items: cartItems,
            customer: this.stateManager.getState('cart.customer'),
            total: this.getCartTotal()
        };

        heldCarts.push(cartData);
        localStorage.setItem('jpos_held_carts', JSON.stringify(heldCarts));
        
        this.clearCart(true);
        
        if (window.toastManager) {
            window.toastManager.show('Cart held successfully');
        }
    }

    /**
     * Get cart total
     * @returns {number} Cart total
     */
    getCartTotal() {
        const cartItems = this.stateManager.getState('cart.items');
        const subtotal = cartItems.reduce((sum, item) => sum + (item.price * item.qty), 0);
        const feeDiscount = this.stateManager.getState('cart.feeDiscount');
        
        let total = subtotal;
        if (feeDiscount && feeDiscount.type && feeDiscount.amount) {
            const amount = parseFloat(feeDiscount.amount);
            if (feeDiscount.type === 'discount') {
                total -= amount;
            } else if (feeDiscount.type === 'fee') {
                total += amount;
            }
        }

        return Math.max(0, total);
    }

    /**
     * Render customer display at top of cart
     */
    renderCustomerDisplay() {
        const customer = this.stateManager.getState('cart.customer');
        const container = document.getElementById('cart-customer-display');
        
        if (!container) return;
        
        if (customer) {
            container.innerHTML = `
                <div class="bg-indigo-600/20 border border-indigo-500/50 rounded-lg p-2 mb-2">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-indigo-200 truncate">${customer.name}</div>
                            <div class="text-xs text-indigo-300/70 truncate">${customer.email}</div>
                        </div>
                        <button onclick="cartManager.detachCustomer()"
                                class="ml-2 p-1 text-indigo-300 hover:text-white rounded transition-colors"
                                title="Remove customer">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            container.classList.remove('hidden');
        } else {
            container.innerHTML = '';
            container.classList.add('hidden');
        }
    }

    /**
     * Show customer search modal
     */
    showCustomerSearch() {
        const modal = document.getElementById('customer-search-modal');
        if (!modal) return;
        
        // Clear previous search
        const searchInput = document.getElementById('customer-search-input');
        if (searchInput) searchInput.value = '';
        
        const results = document.getElementById('customer-search-results');
        if (results) results.innerHTML = '<div class="text-center text-slate-400 py-4">Enter at least 2 characters to search</div>';
        
        modal.classList.remove('hidden');
    }

    /**
     * Hide customer search modal
     */
    hideCustomerSearch() {
        const modal = document.getElementById('customer-search-modal');
        if (modal) modal.classList.add('hidden');
        
        // Hide keyboard if visible
        if (window.onScreenKeyboard && window.onScreenKeyboard.isKeyboardVisible()) {
            window.onScreenKeyboard.hide();
        }
    }

    /**
     * Search for customers
     * @param {string} query - Search query
     */
    async searchCustomers(query) {
        if (!query || query.length < 2) {
            const results = document.getElementById('customer-search-results');
            if (results) {
                results.innerHTML = '<div class="text-center text-slate-400 py-4">Enter at least 2 characters to search</div>';
            }
            return;
        }

        try {
            const nonce = document.getElementById('jpos-customer-search-nonce')?.value;
            const response = await fetch(`api/customers.php?query=${encodeURIComponent(query)}&nonce=${nonce}`);
            
            if (!response.ok) {
                throw new Error('Search failed');
            }

            const data = await response.json();
            
            if (data.success) {
                this.renderCustomerResults(data.data.customers);
            } else {
                throw new Error(data.message || 'Search failed');
            }
        } catch (error) {
            console.error('Customer search error:', error);
            const results = document.getElementById('customer-search-results');
            if (results) {
                results.innerHTML = '<div class="text-center text-red-400 py-4">Search failed. Please try again.</div>';
            }
        }
    }

    /**
     * Render customer search results
     * @param {Array} customers - Array of customer objects
     */
    renderCustomerResults(customers) {
        const results = document.getElementById('customer-search-results');
        if (!results) return;

        if (customers.length === 0) {
            results.innerHTML = '<div class="text-center text-slate-400 py-4">No customers found</div>';
            return;
        }

        results.innerHTML = customers.map(customer => `
            <button onclick="cartManager.attachCustomer(${customer.id}, '${customer.name.replace(/'/g, "\\'")}', '${customer.email}')"
                    class="w-full text-left p-3 bg-slate-700 hover:bg-slate-600 rounded-lg transition-colors">
                <div class="font-semibold text-slate-200">${customer.name}</div>
                <div class="text-sm text-slate-400">${customer.email}</div>
            </button>
        `).join('');
    }

    /**
     * Attach customer to cart
     * @param {number} id - Customer ID
     * @param {string} name - Customer name
     * @param {string} email - Customer email
     */
    attachCustomer(id, name, email) {
        const customer = { id, name, email };
        this.stateManager.updateState('cart.customer', customer);
        this.renderCustomerDisplay();
        this.hideCustomerSearch();
        
        if (window.toastManager) {
            window.toastManager.show(`Customer ${name} attached to cart`);
        }
    }

    /**
     * Detach customer from cart
     */
    detachCustomer() {
        this.stateManager.updateState('cart.customer', null);
        this.renderCustomerDisplay();
        
        if (window.toastManager) {
            window.toastManager.show('Customer removed from cart');
        }
    }

    /**
     * Toggle on-screen keyboard for customer search
     */
    toggleKeyboard() {
        const searchInput = document.getElementById('customer-search-input');
        if (searchInput && window.onScreenKeyboard) {
            window.onScreenKeyboard.toggle(searchInput);
        }
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CartManager;
}

