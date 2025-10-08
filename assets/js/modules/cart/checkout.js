// WP POS v1.9.0 - Checkout & Transaction Processing Module
// Handles split payments, transaction processing, and cart total calculations

class CheckoutManager {
    constructor(state, uiHelpers, cartManager) {
        this.state = state;
        this.ui = uiHelpers;
        this.cart = cartManager;
        
        // Setup modal event listeners
        this.setupSplitPaymentModal();
    }
    
    /**
     * Setup split payment modal event listeners
     */
    setupSplitPaymentModal() {
        const cancelBtn = document.getElementById('split-payment-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                const modal = document.getElementById('split-payment-modal');
                if (modal) modal.classList.add('hidden');
            });
        }
    }

    /**
     * Process transaction - main entry point for checkout
     * @returns {Promise<void>}
     */
    async processTransaction() {
        const cartItems = this.state.getState('cart.items') || [];
        const drawerIsOpen = this.state.getState('drawer.isOpen');
        
        if (cartItems.length === 0 || !drawerIsOpen) {
            return;
        }

        // Show split payment modal instead of direct checkout
        this.openSplitPaymentModal();
    }

    /**
     * Open split payment modal for payment processing
     */
    openSplitPaymentModal() {
        const modal = document.getElementById('split-payment-modal');
        const list = document.getElementById('split-payment-methods-list');
        const totalEl = document.getElementById('split-payment-total');
        const numpad = document.getElementById('split-payment-numpad');
        const applyBtn = document.getElementById('split-payment-apply');
        
        // Update button text
        applyBtn.textContent = 'Pay';
        
        // Default: single payment method
        const paymentMethods = [
            { label: 'Cash', value: 'Cash' },
            { label: 'Card', value: 'Card' },
            { label: 'Other', value: 'Other' }
        ];
        
        const cartTotal = this.getCartTotal();
        let splits = [
            { method: 'Cash', amount: cartTotal }
        ];
        let activeInput = null;
        let inputFirstFocus = [];
        
        this.renderSplitRows(splits, paymentMethods, list, inputFirstFocus, (input, idx) => {
            activeInput = input;
        });
        
        modal.classList.remove('hidden');

        // Setup numpad handlers
        if (numpad) {
            numpad.querySelectorAll('.split-numpad-btn').forEach(btn => {
                btn.onclick = () => {
                    if (!activeInput) return;
                    let idx = parseInt(activeInput.dataset.splitIdx);
                    let val = activeInput.value;
                    const char = btn.textContent;
                    if (char === '.' && val.includes('.')) return;
                    if (val === '0' && char !== '.') val = '';
                    val += char;
                    // Only allow 2 decimals
                    if (/^\d*(\.\d{0,2})?$/.test(val)) {
                        activeInput.value = val;
                        splits[idx].amount = parseFloat(val) || 0;
                        this.updateTotal(splits, cartTotal, totalEl, applyBtn);
                    }
                };
            });
            
            document.getElementById('split-numpad-backspace').onclick = () => {
                if (!activeInput) return;
                let idx = parseInt(activeInput.dataset.splitIdx);
                let val = activeInput.value;
                val = val.slice(0, -1) || '0';
                activeInput.value = val;
                splits[idx].amount = parseFloat(val) || 0;
                this.updateTotal(splits, cartTotal, totalEl, applyBtn);
            };
        }
        
        this.updateTotal(splits, cartTotal, totalEl, applyBtn);
        
        // Apply button handler
        applyBtn.onclick = async () => {
            await this.handlePayment(splits, cartTotal, modal);
        };
    }

    /**
     * Render split payment rows
     * @private
     */
    renderSplitRows(splits, paymentMethods, list, inputFirstFocus, setActiveInput) {
        list.innerHTML = '';
        inputFirstFocus = splits.map(() => true);
        
        splits.forEach((split, i) => {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <select class="split-method p-1 rounded bg-slate-700 border border-slate-600 text-xs w-24">
                    ${paymentMethods.map(opt => `<option value="${opt.value}"${split.method === opt.value ? ' selected' : ''}>${opt.label}</option>`).join('')}
                </select>
                <input type="text" class="split-amount p-1 rounded bg-slate-700 border border-slate-600 text-xs w-24 text-right" value="${split.amount}" />
                ${splits.length > 1 ? `<button class="remove-split px-2 py-1 bg-red-600 hover:bg-red-500 text-xs rounded">&times;</button>` : ''}
            `;
            
            row.querySelector('.split-method').addEventListener('change', e => {
                splits[i].method = e.target.value;
                inputFirstFocus[i] = true;
            });
            
            const amountInput = row.querySelector('.split-amount');
            amountInput.addEventListener('focus', () => {
                setActiveInput(amountInput, i);
                if (inputFirstFocus[i]) {
                    amountInput.value = '';
                    splits[i].amount = '';
                    inputFirstFocus[i] = false;
                }
            });
            
            amountInput.addEventListener('click', () => {
                setActiveInput(amountInput, i);
                if (inputFirstFocus[i]) {
                    amountInput.value = '';
                    splits[i].amount = '';
                    inputFirstFocus[i] = false;
                }
            });
            
            // On first keydown, clear and replace
            amountInput.addEventListener('keydown', (e) => {
                if (inputFirstFocus[i]) {
                    if ((e.key.length === 1 && /[0-9.]/.test(e.key)) || e.key === 'Backspace') {
                        e.preventDefault();
                        amountInput.value = '';
                        splits[i].amount = '';
                        inputFirstFocus[i] = false;
                        if (e.key !== 'Backspace') {
                            amountInput.value = e.key;
                            splits[i].amount = e.key;
                        }
                        amountInput.dispatchEvent(new Event('input'));
                    }
                }
            });
            
            amountInput.addEventListener('input', () => {
                splits[i].amount = parseFloat(amountInput.value) || 0;
                this.updateTotal(splits, this.getCartTotal(), document.getElementById('split-payment-total'), document.getElementById('split-payment-apply'));
            });
            
            if (i === 0 && !amountInput.classList.contains('ring')) {
                setActiveInput(amountInput, i);
            }
            
            if (splits.length > 1) {
                row.querySelector('.remove-split').addEventListener('click', () => {
                    splits.splice(i, 1);
                    this.renderSplitRows(splits, paymentMethods, list, inputFirstFocus, setActiveInput);
                    this.updateTotal(splits, this.getCartTotal(), document.getElementById('split-payment-total'), document.getElementById('split-payment-apply'));
                });
            }
            
            list.appendChild(row);
        });
        
        // Add button
        if (splits.length < paymentMethods.length) {
            const addBtn = document.createElement('button');
            addBtn.className = 'px-2 py-1 bg-slate-700 hover:bg-slate-600 text-xs rounded mt-2';
            addBtn.textContent = '+ Add Payment Method';
            addBtn.onclick = () => {
                const used = splits.map(s => s.method);
                const next = paymentMethods.find(m => !used.includes(m.value));
                if (next) splits.push({ method: next.value, amount: 0 });
                this.renderSplitRows(splits, paymentMethods, list, inputFirstFocus, setActiveInput);
            };
            list.appendChild(addBtn);
        }
    }

    /**
     * Update total display in split payment modal
     * @private
     */
    updateTotal(splits, cartTotal, totalEl, applyBtn) {
        const sum = splits.reduce((a, b) => a + (parseFloat(b.amount) || 0), 0);
        const change = sum - cartTotal;
        
        // Calculate subtotal (before fees/discounts)
        let subtotal = 0;
        const cartItems = this.state.getState('cart.items') || [];
        cartItems.forEach(item => {
            subtotal += (parseFloat(item.price) || 0) * (item.qty || 0);
        });
        
        // Update subtotal
        const subtotalEl = document.getElementById('split-payment-subtotal');
        if (subtotalEl) {
            subtotalEl.textContent = `$${subtotal.toFixed(2)}`;
        }
        
        // Show/hide and update fee if present
        const fee = this.state.getState('fee');
        const feeRow = document.getElementById('split-payment-fee-row');
        const feeEl = document.getElementById('split-payment-fee');
        if (fee && fee.amount) {
            let feeVal = 0;
            if (fee.amountType === 'percentage') {
                feeVal = subtotal * (parseFloat(fee.amount) / 100);
            } else {
                feeVal = parseFloat(fee.amount);
            }
            if (feeEl) feeEl.textContent = `$${feeVal.toFixed(2)}`;
            if (feeRow) feeRow.classList.remove('hidden');
        } else {
            if (feeRow) feeRow.classList.add('hidden');
        }
        
        // Show/hide and update discount if present
        const discount = this.state.getState('discount');
        const discountRow = document.getElementById('split-payment-discount-row');
        const discountEl = document.getElementById('split-payment-discount');
        if (discount && discount.amount) {
            let discountVal = 0;
            if (discount.amountType === 'percentage') {
                discountVal = subtotal * (parseFloat(discount.amount) / 100);
            } else {
                discountVal = parseFloat(discount.amount);
            }
            if (discountEl) discountEl.textContent = `-$${Math.abs(discountVal).toFixed(2)}`;
            if (discountRow) discountRow.classList.remove('hidden');
        } else {
            if (discountRow) discountRow.classList.add('hidden');
        }
        
        // Update total
        if (totalEl) {
            totalEl.textContent = `$${cartTotal.toFixed(2)}`;
        }
        
        // Update amount paid
        const paidEl = document.getElementById('split-payment-paid');
        if (paidEl) {
            paidEl.textContent = `$${sum.toFixed(2)}`;
        }
        
        // Update change
        const changeEl = document.getElementById('split-payment-change');
        const changeRow = document.getElementById('split-payment-change-row');
        if (changeEl && changeRow) {
            if (change >= 0) {
                changeEl.textContent = `$${change.toFixed(2)}`;
                changeEl.className = 'font-medium text-blue-400';
                changeRow.querySelector('.text-slate-300').textContent = 'Change';
            } else {
                changeEl.textContent = `$${Math.abs(change).toFixed(2)}`;
                changeEl.className = 'font-medium text-red-400';
                changeRow.querySelector('.text-slate-300').textContent = 'Remaining';
            }
        }
        
        // Enable/disable pay button
        applyBtn.disabled = sum < cartTotal;
    }

    /**
     * Handle payment processing
     * @private
     */
    async handlePayment(splits, cartTotal, modal) {
        const sum = splits.reduce((a, b) => a + (parseFloat(b.amount) || 0), 0);
        if (sum < cartTotal) {
            alert('Total payment amount must cover the cart total.');
            return;
        }
        
        // Close modal and process payment
        modal.classList.add('hidden');

        const checkoutBtn = document.getElementById('checkout-btn');
        checkoutBtn.disabled = true;
        checkoutBtn.textContent = 'Processing...';

        try {
            const returnFromOrderId = this.state.getState('returns.fromOrderId');
            if (returnFromOrderId) {
                await this.processRefund(splits);
            } else {
                await this.processCheckout(splits);
            }
        } catch (error) {
            alert(`An error occurred: ${error.message}`);
        } finally {
            checkoutBtn.disabled = !this.state.getState('drawer.isOpen');
            checkoutBtn.textContent = 'Checkout';
        }
    }

    /**
     * Process refund transaction
     * @private
     */
    async processRefund(splits) {
        const cartItems = this.state.getState('cart.items') || [];
        const refund_items = cartItems.filter(item => item.qty < 0);
        const new_sale_items = cartItems.filter(item => item.qty > 0);
        
        const payload = {
            original_order_id: this.state.getState('returns.fromOrderId'),
            refund_items: refund_items,
            new_sale_items: new_sale_items,
            payment_method: splits[0].method,
            nonce: this.state.getState('nonces.refund')
        };

        const response = await fetch('/wp-pos/api/refund.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({
                message: `Server responded with ${response.status}`
            }));
            throw new Error(errorData.message);
        }
        
        const result = await response.json();

        if (result.success) {
            alert('Refund/Exchange processed successfully!');
            this.cart.clearCart(true);
            if (window.fetchOrders) await window.fetchOrders();
        } else {
            throw new Error(result.message || 'Refund failed.');
        }
    }

    /**
     * Process checkout transaction
     * @private
     */
    async processCheckout(splits) {
        const feeDiscount = this.state.getState('cart.feeDiscount');
        
        let payload = {
            cart_items: this.state.getState('cart.items') || [],
            payment_method: splits[0].method,
            fee_discount: feeDiscount?.type ? feeDiscount : null
        };
        
        if (splits.length > 1) {
            payload.split_payments = splits.map(s => ({
                method: s.method,
                amount: parseFloat(s.amount) || 0
            }));
        }
        
        payload.nonce = this.state.getState('nonces.checkout');
        
        const response = await fetch('/wp-pos/api/checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({
                message: `Server responded with ${response.status}`
            }));
            throw new Error(errorData.message);
        }
        
        const result = await response.json();

        if (result.success) {
            this.cart.clearCart(true);
            
            if (window.refreshAllData) await window.refreshAllData();
            
            // Show receipt first
            if (window.receiptsManager) {
                window.receiptsManager.showReceipt(result.data.receipt_data);
            }
            
            // Then refresh products in background to show updated stock
            console.log('Refreshing products after checkout...');
            if (window.productsManager && typeof window.productsManager.fetchProducts === 'function') {
                window.productsManager.fetchProducts(); // Don't await - let it run in background
            }
        } else {
            throw new Error(result.message || result.data?.message || 'Checkout failed.');
        }
    }

    /**
     * Calculate total cart value including fees and discounts
     * @returns {number} Total cart value
     */
    getCartTotal() {
        let total = 0;
        const cartItems = this.state.getState('cart.items') || [];
        cartItems.forEach(item => {
            total += (parseFloat(item.price) || 0) * (item.qty || 0);
        });
        
        const fee = this.state.getState('fee');
        if (fee && fee.amount) {
            let feeVal = 0;
            if (fee.amountType === 'percentage') {
                feeVal = total * (parseFloat(fee.amount) / 100);
            } else {
                feeVal = parseFloat(fee.amount);
            }
            total += feeVal;
        }
        
        const discount = this.state.getState('discount');
        if (discount && discount.amount) {
            let discountVal = 0;
            if (discount.amountType === 'percentage') {
                discountVal = total * (parseFloat(discount.amount) / 100);
            } else {
                discountVal = parseFloat(discount.amount);
            }
            total -= Math.abs(discountVal);
        }
        
        return Math.max(0, total);
    }
}

// Export as singleton
window.CheckoutManager = CheckoutManager;