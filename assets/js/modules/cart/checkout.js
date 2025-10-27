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
        
        const resetBtn = document.getElementById('split-payment-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', () => {
                this.resetCheckoutModal();
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
     * Reset checkout modal to initial state
     */
    resetCheckoutModal() {
        const modal = document.getElementById('split-payment-modal');
        if (modal && !modal.classList.contains('hidden')) {
            // Close and reopen the modal to reset everything
            modal.classList.add('hidden');
            this.ui.showToast('Payment calculations reset', 'info');
            // Reopen after a brief delay to ensure clean state
            setTimeout(() => {
                this.openSplitPaymentModal();
            }, 100);
        }
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
        const restoreStockContainer = document.getElementById('restore-stock-container');
        const restoreStockCheckbox = document.getElementById('restore-stock-checkbox');
        
        // Payment methods including Return/Refund Credit (at end)
        const paymentMethods = [
            { label: 'Cash', value: 'Cash' },
            { label: 'Card', value: 'Card' },
            { label: 'Other', value: 'Other' },
            { label: 'Return/Refund Credit', value: 'Return/Refund Credit' }
        ];
        
        const cartTotal = this.getCartTotal();
        const returnFromOrderId = this.state.getState('returns.fromOrderId');
        const cartItems = this.state.getState('cart.items') || [];
        let splits = [];
        
        // Calculate refund credit and new items total from cart items
        let refundCredit = 0;
        let newItemsTotal = 0;
        let hasReturnItems = false;
        
        cartItems.forEach(item => {
            const itemTotal = (parseFloat(item.price) || 0) * item.qty;
            if (item.qty < 0) {
                hasReturnItems = true;
                refundCredit += Math.abs(itemTotal);
            } else if (item.qty > 0) {
                newItemsTotal += itemTotal;
            }
        });
        
        // Show/hide restore stock checkbox based on whether there are return items
        if (restoreStockContainer) {
            if (hasReturnItems) {
                restoreStockContainer.classList.remove('hidden');
                // Reset to checked by default
                if (restoreStockCheckbox) {
                    restoreStockCheckbox.checked = true;
                }
            } else {
                restoreStockContainer.classList.add('hidden');
            }
        }
        
        // Show/hide apply discount checkbox based on return items with original discount
        const applyDiscountContainer = document.getElementById('apply-discount-container');
        const applyDiscountCheckbox = document.getElementById('apply-discount-checkbox');
        if (applyDiscountContainer) {
            const originalDiscount = this.state.getState('returns.originalDiscount');
            const originalFee = this.state.getState('returns.originalFee');
            
            if (hasReturnItems && (originalDiscount || originalFee)) {
                applyDiscountContainer.classList.remove('hidden');
                // Reset to checked by default
                if (applyDiscountCheckbox) {
                    applyDiscountCheckbox.checked = true;
                    
                    // Add event listener to trigger recalculation when checkbox changes
                    applyDiscountCheckbox.removeEventListener('change', this.handleDiscountCheckboxChange);
                    applyDiscountCheckbox.addEventListener('change', this.handleDiscountCheckboxChange.bind(this));
                }
            } else {
                applyDiscountContainer.classList.add('hidden');
            }
        }
        
        // Setup initial payment splits
        if (hasReturnItems && refundCredit > 0) {
            // Return/Exchange: Pre-fill refund credit first
            splits.push({ method: 'Return/Refund Credit', amount: refundCredit });
            
            // If new items total exceeds credit, add second payment method for remainder
            if (newItemsTotal > refundCredit) {
                splits.push({ method: 'Cash', amount: newItemsTotal - refundCredit });
            }
        } else {
            // Regular checkout: Default to Cash
            splits = [{ method: 'Cash', amount: cartTotal }];
        }
        
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
                <input type="text" class="split-amount p-1 rounded bg-slate-700 border border-slate-600 text-xs w-24 text-right" value="${split.amount}" data-split-idx="${i}" />
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
        
        // Add button (limit to 4 payment methods max)
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
     * Handle discount checkbox change - trigger recalculation
     * @private
     */
    handleDiscountCheckboxChange() {
        // Get current splits
        const splits = this.state.getState('checkout.splits') || [];
        
        // Recalculate refund credit based on checkbox state
        const applyDiscountCheckbox = document.getElementById('apply-discount-checkbox');
        const shouldApplyDiscount = applyDiscountCheckbox ? applyDiscountCheckbox.checked : true;
        
        const cartItems = this.state.getState('cart.items') || [];
        const originalDiscount = this.state.getState('returns.originalDiscount');
        const originalFee = this.state.getState('returns.originalFee');
        
        let returnItemsTotal = 0;
        let newItemsTotal = 0;
        
        // Calculate totals
        cartItems.forEach(item => {
            const itemTotal = (parseFloat(item.price) || 0) * Math.abs(item.qty || 0);
            if (item.qty < 0) {
                returnItemsTotal += itemTotal;
            } else {
                newItemsTotal += itemTotal;
            }
        });
        
        // Calculate adjusted return credit if discount should be applied
        let adjustedReturnCredit = returnItemsTotal;
        if (shouldApplyDiscount && (originalDiscount || originalFee)) {
            if (originalDiscount && originalDiscount.amount && originalDiscount.amountType === 'percentage') {
                adjustedReturnCredit -= (returnItemsTotal * (parseFloat(originalDiscount.amount) / 100));
            }
            if (originalFee && originalFee.amount && originalFee.amountType === 'percentage') {
                adjustedReturnCredit += (returnItemsTotal * (parseFloat(originalFee.amount) / 100));
            }
        }
        
        // Ensure Return/Refund Credit split exists and update its amount
        let refundSplit = splits.find(s => s.method === 'Return/Refund Credit');
        if (!refundSplit) {
            // Add Return/Refund Credit split if it doesn't exist
            refundSplit = { method: 'Return/Refund Credit', amount: adjustedReturnCredit };
            splits.push(refundSplit);
        } else {
            // Update existing Return/Refund Credit amount
            refundSplit.amount = adjustedReturnCredit;
        }
        
        // Update other payment method amounts
        const otherSplits = splits.filter(s => s.method !== 'Return/Refund Credit');
        const remainingAmount = newItemsTotal - adjustedReturnCredit;
        
        if (remainingAmount > 0) {
            // Need additional payment
            if (otherSplits.length === 0) {
                // Add Cash payment method
                splits.push({ method: 'Cash', amount: remainingAmount });
            } else {
                // Update existing other payment method
                otherSplits[0].amount = remainingAmount;
            }
        } else if (remainingAmount <= 0 && otherSplits.length > 0) {
            // Remove other payment methods if not needed
            splits.splice(splits.indexOf(otherSplits[0]), 1);
        }
        
        // Save updated splits
        this.state.updateState('checkout.splits', splits);
        
        // Re-render the split rows with updated amounts
        const list = document.getElementById('split-payment-methods-list');
        if (list) {
            list.innerHTML = ''; // Clear existing
            
            // Get payment methods
            const paymentMethods = [
                { label: 'Cash', value: 'Cash' },
                { label: 'Card', value: 'Card' },
                { label: 'Other', value: 'Other' },
                { label: 'Return/Refund Credit', value: 'Return/Refund Credit' }
            ];
            
            let inputFirstFocus = [];
            this.renderSplitRows(splits, paymentMethods, list, inputFirstFocus, () => {});
        }
        
        // Trigger cart update to recalculate totals
        if (window.cartManager) {
            window.cartManager.updateCartDisplay();
        }
        
        // Trigger checkout recalculation with updated splits
        this.updateTotal(
            splits,
            this.getCartTotal(),
            document.getElementById('split-payment-total'),
            document.getElementById('split-payment-apply')
        );
    }

    /**
     * Update total display in split payment modal
     * @private
     */
    updateTotal(splits, cartTotal, totalEl, applyBtn) {
        // Calculate sum of actual payments (exclude Return/Refund Credit)
        const sum = splits.reduce((a, b) => {
            if (b.method === 'Return/Refund Credit') {
                return a; // Don't count credits as payments
            }
            return a + (parseFloat(b.amount) || 0);
        }, 0);
        
        // Calculate subtotal - separate returns from new items
        const cartItems = this.state.getState('cart.items') || [];
        let returnItemsTotal = 0;
        let newItemsTotal = 0;
        
        cartItems.forEach(item => {
            const itemTotal = (parseFloat(item.price) || 0) * (item.qty || 0);
            if (item.qty < 0) {
                returnItemsTotal += Math.abs(itemTotal);
            } else {
                newItemsTotal += itemTotal;
            }
        });
        
        // Calculate actual refund credit being used from splits
        let actualRefundCredit = 0;
        splits.forEach(split => {
            if (split.method === 'Return/Refund Credit') {
                actualRefundCredit += (parseFloat(split.amount) || 0);
            }
        });
        
        // Calculate net amount
        let netAmount = newItemsTotal;
        
        // Apply current cart discount/fee to NEW ITEMS ONLY
        const fee = this.state.getState('fee');
        const discount = this.state.getState('discount');
        
        if (fee && fee.amount) {
            let feeVal = 0;
            if (fee.amountType === 'percentage') {
                feeVal = newItemsTotal * (parseFloat(fee.amount) / 100);
            } else {
                feeVal = parseFloat(fee.amount);
            }
            netAmount += feeVal;
        }
        
        if (discount && discount.amount) {
            let discountVal = 0;
            if (discount.amountType === 'percentage') {
                discountVal = newItemsTotal * (parseFloat(discount.amount) / 100);
            } else {
                discountVal = parseFloat(discount.amount);
            }
            netAmount -= Math.abs(discountVal);
        }
        
        // Apply original order discount/fee to RETURN CREDIT ONLY (if checkbox is checked)
        const originalDiscount = this.state.getState('returns.originalDiscount');
        const originalFee = this.state.getState('returns.originalFee');
        const applyDiscountCheckbox = document.getElementById('apply-discount-checkbox');
        const shouldApplyDiscount = applyDiscountCheckbox ? applyDiscountCheckbox.checked : true; // Default to true for backward compatibility
        
        if (returnItemsTotal > 0) {
            // Calculate what the return credit should be based on original discount/fee
            let adjustedReturnCredit = returnItemsTotal;
            
            if (shouldApplyDiscount && originalDiscount && originalDiscount.amount) {
                let originalDiscountVal = 0;
                if (originalDiscount.amountType === 'percentage') {
                    // Apply original discount percentage to return items
                    originalDiscountVal = returnItemsTotal * (parseFloat(originalDiscount.amount) / 100);
                } else {
                    // For flat discounts, calculate proportional amount
                    // This is tricky - we'd need to know original order total to calculate proportion
                    // For now, we'll skip flat discount adjustment on returns
                }
                adjustedReturnCredit -= originalDiscountVal;
            }
            
            if (shouldApplyDiscount && originalFee && originalFee.amount) {
                let originalFeeVal = 0;
                if (originalFee.amountType === 'percentage') {
                    // Apply original fee percentage to return items
                    originalFeeVal = returnItemsTotal * (parseFloat(originalFee.amount) / 100);
                } else {
                    // For flat fees, calculate proportional amount
                    // This is tricky - we'd need to know original order total to calculate proportion
                    // For now, we'll skip flat fee adjustment on returns
                }
                adjustedReturnCredit += originalFeeVal;
            }
            
            // Use adjusted return credit instead of raw return total
            netAmount -= adjustedReturnCredit;
        } else {
            // No return items, just subtract actual refund credit
            netAmount -= actualRefundCredit;
        }
        const change = sum - netAmount;
        
        // Update subtotal display - show adjusted return credit if applicable
        const subtotalEl = document.getElementById('split-payment-subtotal');
        if (subtotalEl) {
            if (returnItemsTotal > 0 && newItemsTotal > 0) {
                // Exchange - show adjusted refund credit
                let displayCredit = actualRefundCredit > 0 ? actualRefundCredit : returnItemsTotal;
                
                // If we have original discount/fee and checkbox is checked, show the adjustment
                if (shouldApplyDiscount && (originalDiscount || originalFee)) {
                    let adjustedCredit = returnItemsTotal;
                    if (originalDiscount && originalDiscount.amount && originalDiscount.amountType === 'percentage') {
                        adjustedCredit -= (returnItemsTotal * (parseFloat(originalDiscount.amount) / 100));
                    }
                    if (originalFee && originalFee.amount && originalFee.amountType === 'percentage') {
                        adjustedCredit += (returnItemsTotal * (parseFloat(originalFee.amount) / 100));
                    }
                    displayCredit = adjustedCredit;
                }
                
                subtotalEl.innerHTML = `
                    <div class="text-xs space-y-1 w-full">
                        <div class="flex justify-between">
                            <span>New Items:</span>
                            <span class="text-green-400">$${newItemsTotal.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Return Credit:</span>
                            <span class="text-amber-400">-$${displayCredit.toFixed(2)}</span>
                        </div>
                        ${(shouldApplyDiscount && (originalDiscount || originalFee)) ? '<div class="text-xs text-slate-400">(Adjusted for original discount/fee)</div>' : ''}
                    </div>
                `;
            } else if (returnItemsTotal > 0) {
                // Return only - show adjusted refund credit
                let displayCredit = actualRefundCredit > 0 ? actualRefundCredit : returnItemsTotal;
                
                if (shouldApplyDiscount && (originalDiscount || originalFee)) {
                    let adjustedCredit = returnItemsTotal;
                    if (originalDiscount && originalDiscount.amount && originalDiscount.amountType === 'percentage') {
                        adjustedCredit -= (returnItemsTotal * (parseFloat(originalDiscount.amount) / 100));
                    }
                    if (originalFee && originalFee.amount && originalFee.amountType === 'percentage') {
                        adjustedCredit += (returnItemsTotal * (parseFloat(originalFee.amount) / 100));
                    }
                    displayCredit = adjustedCredit;
                }
                
                subtotalEl.innerHTML = `<span class="text-amber-400">Return Credit: $${displayCredit.toFixed(2)}</span>`;
            } else {
                // Regular purchase
                subtotalEl.textContent = `$${newItemsTotal.toFixed(2)}`;
            }
        }
        
        // Show/hide and update fee if present
        const feeRow = document.getElementById('split-payment-fee-row');
        const feeEl = document.getElementById('split-payment-fee');
        if (fee && fee.amount) {
            let feeVal = 0;
            if (fee.amountType === 'percentage') {
                feeVal = newItemsTotal * (parseFloat(fee.amount) / 100);
            } else {
                feeVal = parseFloat(fee.amount);
            }
            if (feeEl) feeEl.textContent = `$${feeVal.toFixed(2)}`;
            if (feeRow) feeRow.classList.remove('hidden');
        } else {
            if (feeRow) feeRow.classList.add('hidden');
        }
        
        // Show/hide and update discount if present
        const discountRow = document.getElementById('split-payment-discount-row');
        const discountEl = document.getElementById('split-payment-discount');
        if (discount && discount.amount) {
            let discountVal = 0;
            if (discount.amountType === 'percentage') {
                discountVal = newItemsTotal * (parseFloat(discount.amount) / 100);
            } else {
                discountVal = parseFloat(discount.amount);
            }
            if (discountEl) discountEl.textContent = `-$${Math.abs(discountVal).toFixed(2)}`;
            if (discountRow) discountRow.classList.remove('hidden');
        } else {
            if (discountRow) discountRow.classList.add('hidden');
        }
        
        // Update total with clear labeling
        const isRefund = netAmount < 0;
        if (totalEl) {
            if (isRefund) {
                totalEl.innerHTML = `<span class="text-green-400">Refund Due: $${Math.abs(netAmount).toFixed(2)}</span>`;
            } else {
                totalEl.textContent = `$${Math.abs(netAmount).toFixed(2)}`;
            }
        }
        
        // For pure refunds (negative netAmount), hide payment tracking rows
        const paidRow = document.getElementById('split-payment-paid-row');
        const changeRow = document.getElementById('split-payment-change-row');
        const paidEl = document.getElementById('split-payment-paid');
        const changeEl = document.getElementById('split-payment-change');
        
        if (isRefund) {
            // Hide payment tracking for pure refunds - no payment needed
            if (paidRow) paidRow.classList.add('hidden');
            if (changeRow) changeRow.classList.add('hidden');
        } else {
            // Show and update payment tracking for purchases and exchanges
            if (paidRow) paidRow.classList.remove('hidden');
            if (changeRow) changeRow.classList.remove('hidden');
            
            // Update amount paid
            if (paidEl) {
                paidEl.textContent = `$${sum.toFixed(2)}`;
            }
            
            // Update change/remaining
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
        }
        
        // Update button text and color based on transaction type
        if (applyBtn) {
            // Remove all color classes first
            applyBtn.classList.remove('bg-indigo-600', 'hover:bg-indigo-500', 'bg-green-600', 'hover:bg-green-500', 'bg-amber-600', 'hover:bg-amber-500');
            
            if (isRefund) {
                // Pure refund - customer getting money back
                applyBtn.textContent = `Issue Refund $${Math.abs(netAmount).toFixed(2)}`;
                applyBtn.classList.add('bg-green-600', 'hover:bg-green-500');
                applyBtn.disabled = false; // Always enabled for refunds
            } else if (returnItemsTotal > 0 && newItemsTotal > 0) {
                // Exchange - has both returns and new items
                if (netAmount > 0) {
                    applyBtn.textContent = `Complete Exchange & Pay $${Math.abs(netAmount).toFixed(2)}`;
                    applyBtn.classList.add('bg-amber-600', 'hover:bg-amber-500');
                    applyBtn.disabled = sum < Math.abs(netAmount);
                } else {
                    applyBtn.textContent = `Complete Exchange`;
                    applyBtn.classList.add('bg-amber-600', 'hover:bg-amber-500');
                    applyBtn.disabled = false;
                }
            } else {
                // Regular purchase
                applyBtn.textContent = `Complete Payment $${Math.abs(netAmount).toFixed(2)}`;
                applyBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-500');
                applyBtn.disabled = sum < Math.abs(netAmount);
            }
        }
    }

    /**
     * Handle payment processing
     * @private
     */
    async handlePayment(splits, cartTotal, modal) {
        const sum = splits.reduce((a, b) => a + (parseFloat(b.amount) || 0), 0);
        // For refunds (negative total), no payment needed. For purchases, payment must cover total.
        if (cartTotal > 0 && sum < cartTotal) {
            this.ui.showToast('Total payment amount must cover the cart total.', 'error');
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
            this.ui.showToast(`An error occurred: ${error.message}`, 'error');
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
        
        // Validate cart items have required structure
        if (!Array.isArray(cartItems) || cartItems.length === 0) {
            throw new Error('No items in cart for refund/exchange');
        }
        
        // Filter and validate refund items (negative quantities)
        const refund_items = cartItems.filter(item => {
            if (!item || typeof item !== 'object') {
                console.error('Invalid cart item:', item);
                return false;
            }
            return item.qty < 0;
        });
        
        // Filter and validate new sale items (positive quantities)
        const new_sale_items = cartItems.filter(item => {
            if (!item || typeof item !== 'object') {
                console.error('Invalid cart item:', item);
                return false;
            }
            return item.qty > 0;
        });
        
        // Validate we have either refund items or new sale items
        if (refund_items.length === 0 && new_sale_items.length === 0) {
            throw new Error('No valid items found for refund/exchange');
        }
        
        // Validate each refund item has required properties
        for (const item of refund_items) {
            if (!item.id) {
                throw new Error(`Refund item missing 'id' property: ${JSON.stringify(item)}`);
            }
            if (typeof item.qty === 'undefined') {
                throw new Error(`Refund item missing 'qty' property: ${JSON.stringify(item)}`);
            }
            if (typeof item.price === 'undefined') {
                throw new Error(`Refund item missing 'price' property: ${JSON.stringify(item)}`);
            }
        }
        
        // Validate each new sale item has required properties
        for (const item of new_sale_items) {
            if (!item.id) {
                throw new Error(`New sale item missing 'id' property: ${JSON.stringify(item)}`);
            }
            if (typeof item.qty === 'undefined') {
                throw new Error(`New sale item missing 'qty' property: ${JSON.stringify(item)}`);
            }
            if (typeof item.price === 'undefined') {
                throw new Error(`New sale item missing 'price' property: ${JSON.stringify(item)}`);
            }
        }
        
        // Get and validate original order ID
        const originalOrderId = this.state.getState('returns.fromOrderId');
        if (!originalOrderId) {
            throw new Error('Original order ID not found for refund');
        }
        
        // Get and validate nonce
        const refundNonce = this.state.getState('nonces.refund');
        if (!refundNonce) {
            throw new Error('Security token not found for refund');
        }
        
        // Get restore stock checkbox value
        const restoreStockCheckbox = document.getElementById('restore-stock-checkbox');
        const restoreStock = restoreStockCheckbox ? restoreStockCheckbox.checked : true;
        
        const payload = {
            original_order_id: originalOrderId,
            refund_items: refund_items,
            new_sale_items: new_sale_items,
            payment_method: splits[0].method,
            restore_stock: restoreStock,
            nonce: refundNonce
        };
        
        console.log('Refund payload:', payload);

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
            this.ui.showToast('Refund/Exchange processed successfully!', 'success');
            this.cart.clearCart(true);
            
            // Clear stored discount state
            sessionStorage.removeItem('jpos_return_discount');
            
            // Show refund/exchange receipt
            if (result.data && result.data.receipt_data && window.receiptsManager) {
                window.receiptsManager.showRefundReceipt(result.data.receipt_data);
            }
            
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
        // Get fee and discount from separate state items (not cart.feeDiscount)
        const fee = this.state.getState('fee');
        const discount = this.state.getState('discount');
        
        // Construct fee_discount object for API
        let feeDiscount = null;
        if (discount && discount.amount) {
            // Discount takes priority if both exist
            feeDiscount = {
                type: 'discount',
                amount: discount.amount,
                label: discount.label || '',
                amountType: discount.amountType || 'flat'
            };
        } else if (fee && fee.amount) {
            feeDiscount = {
                type: 'fee',
                amount: fee.amount,
                label: fee.label || '',
                amountType: fee.amountType || 'flat'
            };
        }
        
        let payload = {
            cart_items: this.state.getState('cart.items') || [],
            payment_method: splits[0].method,
            fee_discount: feeDiscount
        };
        
        // Include attached customer if present
        const customer = this.state.getState('cart.customer');
        if (customer && customer.id) {
            payload.customer_id = customer.id;
        }
        
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
     * @returns {number} Total cart value (can be negative for returns)
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
        
        return total;
    }
}

// Export as singleton
window.CheckoutManager = CheckoutManager;