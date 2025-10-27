// WP POS v1.9.0 - Cart Manager Module
// Handles all cart operations, calculations, and customer attachment

class CartManager {
    constructor(stateManager, uiHelpers) {
        this.state = stateManager;
        this.ui = uiHelpers;
        
        // Setup modal event listeners
        this.setupFeeDiscountModal();
        
        // Restore discount state from sessionStorage
        this.restoreDiscountState();
    }
    
    /**
     * Restore discount state from sessionStorage
     * @private
     */
    restoreDiscountState() {
        try {
            const storedDiscount = sessionStorage.getItem('jpos_return_discount');
            if (storedDiscount) {
                const discountData = JSON.parse(storedDiscount);
                
                // Check if the data is recent (within last hour)
                const oneHour = 60 * 60 * 1000;
                if (Date.now() - discountData.timestamp < oneHour) {
                    // Restore the discount state
                    this.state.updateState('returns.originalDiscount', discountData.discount);
                    this.state.updateState('returns.originalFee', discountData.fee);
                    this.state.updateState('returns.fromOrderId', discountData.orderId);
                } else {
                    // Clear expired data
                    sessionStorage.removeItem('jpos_return_discount');
                }
            }
        } catch (error) {
            console.warn('Failed to restore discount state:', error);
            sessionStorage.removeItem('jpos_return_discount');
        }
    }
    
    /**
     * Setup Fee/Discount modal event listeners
     */
    setupFeeDiscountModal() {
        const modal = document.getElementById('fee-discount-modal');
        const cancelBtn = document.getElementById('fee-discount-cancel-btn');
        const applyBtn = document.getElementById('fee-discount-apply-btn');
        const amountInput = document.getElementById('fee-discount-amount');
        const typeSelector = document.getElementById('fee-discount-type-selector');
        
        if (!modal) return;
        
        // Store separate values for flat and percentage
        let flatValue = '';
        let percentageValue = '';
        
        // Validate input to allow only numbers and decimal point
        if (amountInput) {
            amountInput.addEventListener('input', (e) => {
                let value = e.target.value;
                // Allow only numbers and one decimal point
                value = value.replace(/[^\d.]/g, '');
                // Ensure only one decimal point
                const parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }
                // Limit to 2 decimal places
                if (parts.length === 2 && parts[1].length > 2) {
                    value = parts[0] + '.' + parts[1].substring(0, 2);
                }
                e.target.value = value;
            });
        }
        
        // Cancel button
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                modal.classList.add('hidden');
                amountInput.value = '';
            });
        }
        
        // Apply button
        if (applyBtn) {
            applyBtn.addEventListener('click', () => {
                const amount = parseFloat(amountInput.value) || 0;
                if (amount <= 0) {
                    this.ui.showToast('Please enter a valid amount');
                    return;
                }
                
                const mode = modal.dataset.mode; // 'fee' or 'discount'
                const typeBtn = typeSelector?.querySelector('[data-state="active"]');
                const amountType = typeBtn?.dataset.value || 'flat';
                
                if (mode === 'fee') {
                    this.state.updateState('fee', { amount: amount.toString(), label: '', amountType });
                    this.ui.showToast(`Fee of $${amount.toFixed(2)} added`);
                } else if (mode === 'discount') {
                    this.state.updateState('discount', { amount: amount.toString(), label: '', amountType });
                    this.ui.showToast(`Discount of $${amount.toFixed(2)} added`);
                }
                
                this.renderCart();
                modal.classList.add('hidden');
                amountInput.value = '';
            });
        }
        
        // Numpad buttons
        document.querySelectorAll('.num-pad-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                let val = amountInput.value;
                const char = btn.textContent;
                
                // Handle decimal point
                if (char === '.' && val.includes('.')) return;
                
                // Replace initial 0
                if (val === '0' && char !== '.') val = '';
                
                val += char;
                
                // Only allow 2 decimals
                if (/^\d*(\.\d{0,2})?$/.test(val)) {
                    amountInput.value = val;
                }
            });
        });
        
        // Backspace button
        const backspaceBtn = document.getElementById('num-pad-backspace');
        if (backspaceBtn) {
            backspaceBtn.addEventListener('click', () => {
                let val = amountInput.value;
                val = val.slice(0, -1) || '';
                amountInput.value = val;
            });
        }
        
        // Type selector buttons - switch between flat and percentage
        if (typeSelector) {
            const percentSymbol = document.getElementById('fee-discount-percent-symbol');
            
            typeSelector.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', () => {
                    // Get current active type before switching
                    const wasFlat = typeSelector.querySelector('[data-state="active"]')?.dataset.value === 'flat';
                    
                    // Save current value to appropriate storage
                    if (wasFlat) {
                        flatValue = amountInput.value;
                    } else {
                        percentageValue = amountInput.value;
                    }
                    
                    // Update button states
                    typeSelector.querySelectorAll('button').forEach(b => {
                        b.dataset.state = 'inactive';
                        b.classList.remove('bg-slate-200', 'text-slate-900');
                    });
                    btn.dataset.state = 'active';
                    btn.classList.add('bg-slate-200', 'text-slate-900');
                    
                    // Load value for newly selected type
                    const nowFlat = btn.dataset.value === 'flat';
                    if (nowFlat) {
                        amountInput.value = flatValue;
                        // Hide percent symbol for flat
                        if (percentSymbol) percentSymbol.classList.add('hidden');
                        // Remove extra padding for flat mode
                        amountInput.classList.remove('pr-8');
                        amountInput.classList.add('px-2');
                        // Change placeholder for flat mode
                        amountInput.placeholder = '0.00';
                    } else {
                        amountInput.value = percentageValue;
                        // Show percent symbol for percentage
                        if (percentSymbol) percentSymbol.classList.remove('hidden');
                        // Add extra padding for percentage mode
                        amountInput.classList.remove('px-2');
                        amountInput.classList.add('pr-8', 'pl-2');
                        // Change placeholder for percentage mode
                        amountInput.placeholder = '00';
                    }
                });
            });
        }
    }

    /**
     * Add product to cart
     * @param {Object} product - Product object with id, name, price, etc.
     * @param {Number} quantity - Quantity to add (default: 1)
     */
    addToCart(product, quantity = 1) {
        const cart = this.state.getState('cart');
        
        if (!cart) {
            console.error('Cart state not found');
            return;
        }

        const existingItem = cart.items.find(item => item.id === product.id);
        
        if (existingItem) {
            existingItem.qty += quantity;
            if (existingItem.qty === 0) {
                cart.items = cart.items.filter(item => item.id !== product.id);
                this.ui.showToast(`${product.name} removed from cart`);
            } else if (quantity > 0) {
                this.ui.showToast(`${product.name} added to cart`);
            } else if (quantity < 0) {
                this.ui.showToast(`${product.name} removed from cart`);
            }
        } else if (quantity !== 0) {
            cart.items.push({ ...product, qty: quantity });
            this.ui.showToast(`${product.name} added to cart`);
        }
        
        this.state.updateState('cart.items', cart.items);
        this.renderCart();
    }

    /**
     * Update cart item quantity
     * @param {Number} id - Product ID
     * @param {Number} change - Quantity change (positive or negative)
     */
    updateCartQuantity(id, change) {
        const cart = this.state.getState('cart');
        const item = cart.items.find(item => item.id === id);
        
        if (item) {
            if (item.qty < 0) {
                const maxQty = this.state.getState('return_from_order_items')?.find(p => p.id === id)?.quantity || 0;
                if (item.qty + change > 0 || Math.abs(item.qty + change) > maxQty) return;
            }
            
            item.qty += change;
            
            if (item.qty === 0) {
                cart.items = cart.items.filter(i => i.id !== id);
                this.ui.showToast(`${item.name} removed from cart`);
            } else if (change > 0) {
                this.ui.showToast(`${item.name} added to cart`);
            } else if (change < 0) {
                this.ui.showToast(`${item.name} removed from cart`);
            }
            
            this.state.updateState('cart.items', cart.items);
            this.renderCart();
        }
    }

    /**
     * Clear the cart
     * @param {Boolean} fullReset - Whether to also clear customer and return data
     */
    clearCart(fullReset = false) {
        this.state.updateState('cart.items', []);
        this.state.updateState('fee', { amount: '', label: '', amountType: 'flat' });
        this.state.updateState('discount', { amount: '', label: '', amountType: 'flat' });
        this.state.updateState('feeDiscount', { type: null, amount: '', label: '', amountType: 'flat' });
        
        if (fullReset) {
            this.state.updateState('cart.customer', null);
            this.state.updateState('return_from_order_id', null);
            this.state.updateState('return_from_order_items', []);
        }
        
        this.renderCart();
        this.saveCartState();
    }

    /**
     * Get cart subtotal (before fees/discounts)
     * @returns {Number} Subtotal amount
     */
    getSubtotal() {
        const cart = this.state.getState('cart');
        let total = 0;
        
        (cart.items || []).forEach(item => {
            total += (parseFloat(item.price) || 0) * (item.qty || 0);
        });
        
        return total;
    }

    /**
     * Get tax amount
     * @returns {Number} Tax amount (currently 0, to be implemented)
     */
    getTax() {
        // TODO: Implement tax calculation based on settings
        return 0;
    }

    /**
     * Get fee amount
     * @returns {Number} Fee amount
     */
    getFees() {
        const subtotal = this.getSubtotal();
        const fee = this.state.getState('fee');
        
        if (!fee || !fee.amount) return 0;
        
        if (fee.amountType === 'percentage') {
            return subtotal * (parseFloat(fee.amount) / 100);
        } else {
            return parseFloat(fee.amount);
        }
    }

    /**
     * Get discount amount
     * @returns {Number} Discount amount (as positive number)
     */
    getDiscounts() {
        const subtotal = this.getSubtotal();
        const discount = this.state.getState('discount');
        
        if (!discount || !discount.amount) return 0;
        
        let discountVal = 0;
        if (discount.amountType === 'percentage') {
            discountVal = subtotal * (parseFloat(discount.amount) / 100);
        } else {
            discountVal = parseFloat(discount.amount);
        }
        
        return Math.abs(discountVal);
    }

    /**
     * Get cart total (subtotal + fees - discounts)
     * @returns {Number} Total amount (can be negative for returns)
     */
    getTotal() {
        let total = this.getSubtotal();
        total += this.getFees();
        total -= this.getDiscounts();
        return total;
    }

    /**
     * Attach customer to cart
     * @param {Number} id - Customer ID
     * @param {String} name - Customer name
     * @param {String} email - Customer email
     */
    attachCustomer(id, name, email) {
        const customer = { id, name, email };
        this.state.updateState('cart.customer', customer);
        this.renderCustomerDisplay();
        this.ui.showToast(`Customer ${name} attached to cart`);
        this.saveCartState();
    }

    /**
     * Detach customer from cart
     */
    detachCustomer() {
        this.state.updateState('cart.customer', null);
        this.renderCustomerDisplay();
        this.ui.showToast('Customer removed from cart');
        this.saveCartState();
    }

    /**
     * Get attached customer
     * @returns {Object|null} Customer object or null
     */
    getAttachedCustomer() {
        return this.state.getState('cart.customer');
    }

    /**
     * Render customer display in cart
     */
    renderCustomerDisplay() {
        const customer = this.getAttachedCustomer();
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
                        <button onclick="window.cartManager.detachCustomer()"
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
     * Render the cart UI
     */
    renderCart() {
        // Render customer display
        this.renderCustomerDisplay();
        
        const cartContainer = document.getElementById('cart-items');
        const subtotalEl = document.getElementById('cart-subtotal');
        const totalEl = document.getElementById('cart-total');
        const totalBottomEl = document.getElementById('cart-total-bottom');
        const summaryEl = document.getElementById('cart-summary');
        const discountRow = document.getElementById('cart-discount-row');
        const feeRow = document.getElementById('cart-fee-row');
        const checkoutBtn = document.getElementById('checkout-btn');
        
        if (!cartContainer) return;
        
        cartContainer.innerHTML = '';
        if (discountRow) discountRow.innerHTML = '';
        if (feeRow) feeRow.innerHTML = '';
        
        const cart = this.state.getState('cart');
        const fee = this.state.getState('fee');
        const discount = this.state.getState('discount');
        
        let itemCount = 0;
        let qtyCount = 0;
        
        // Render cart items
        (cart.items || []).forEach(item => {
            itemCount++;
            qtyCount += item.qty;
            
            const li = document.createElement('div');
            li.className = `flex items-center gap-2 p-1 rounded bg-slate-700/50 text-xs`;
            
            const imageHTML = item.image_url ? 
                `<img src="${item.image_url}" alt="${item.name}" class="w-10 h-10 object-cover rounded-md flex-shrink-0">` : 
                `<div class="w-10 h-10 placeholder-bg rounded-md flex-shrink-0"></div>`;
            
            const itemInfo = document.createElement('div');
            itemInfo.className = 'flex-grow truncate';
            
            // Check if this is a return item with original discount
            const originalDiscount = this.state.getState('returns.originalDiscount');
            const originalFee = this.state.getState('returns.originalFee');
            const isReturnItem = item.qty < 0;
            const hasOriginalDiscount = isReturnItem && (originalDiscount || originalFee);
            
            let priceDisplay = `$${parseFloat(item.price).toFixed(2)}`;
            if (hasOriginalDiscount) {
                // Calculate what the customer actually paid
                let adjustedPrice = parseFloat(item.price);
                if (originalDiscount && originalDiscount.amount && originalDiscount.amountType === 'percentage') {
                    adjustedPrice = adjustedPrice * (1 - parseFloat(originalDiscount.amount) / 100);
                }
                if (originalFee && originalFee.amount && originalFee.amountType === 'percentage') {
                    adjustedPrice = adjustedPrice * (1 + parseFloat(originalFee.amount) / 100);
                }
                priceDisplay = `<span class="text-slate-400 line-through">$${parseFloat(item.price).toFixed(2)}</span> <span class="text-amber-400 font-semibold">$${adjustedPrice.toFixed(2)}</span>`;
            }
            
            itemInfo.innerHTML = `<span class="font-semibold text-slate-100 truncate block" title="${item.name}">${item.name}</span><span class="font-mono block">${priceDisplay}</span>`;
            
            const qtyControls = document.createElement('div');
            qtyControls.className = 'flex items-center gap-1 flex-shrink-0';

            const minusBtn = document.createElement('button');
            minusBtn.className = 'w-5 h-5 rounded bg-slate-600 hover:bg-slate-500 transition-colors text-xs';
            minusBtn.textContent = '-';
            minusBtn.addEventListener('click', () => this.updateCartQuantity(item.id, -1));
            
            const qtySpan = document.createElement('span');
            qtySpan.className = 'w-5 text-center font-bold';
            qtySpan.textContent = item.qty;
    
            const plusBtn = document.createElement('button');
            plusBtn.className = 'w-5 h-5 rounded bg-slate-600 hover:bg-slate-500 transition-colors text-xs';
            plusBtn.textContent = '+';
            plusBtn.addEventListener('click', () => this.updateCartQuantity(item.id, 1));
    
            qtyControls.appendChild(minusBtn);
            qtyControls.appendChild(qtySpan);
            qtyControls.appendChild(plusBtn);
    
            li.innerHTML = imageHTML;
            li.appendChild(itemInfo);
            li.appendChild(qtyControls);
            
            cartContainer.appendChild(li);
        });

        // Calculate totals - separate returns from new items
        const cartItems = cart.items || [];
        let returnItemsTotal = 0;
        let newItemsTotal = 0;
        
        cartItems.forEach(item => {
            let itemPrice = parseFloat(item.price) || 0;
            
            // For return items, check if we should use adjusted price
            if (item.qty < 0) {
                const originalDiscount = this.state.getState('returns.originalDiscount');
                const originalFee = this.state.getState('returns.originalFee');
                const applyDiscountCheckbox = document.getElementById('apply-discount-checkbox');
                const shouldApplyDiscount = applyDiscountCheckbox ? applyDiscountCheckbox.checked : true;
                
                if (shouldApplyDiscount && (originalDiscount || originalFee)) {
                    // Calculate adjusted price (what customer actually paid)
                    if (originalDiscount && originalDiscount.amount && originalDiscount.amountType === 'percentage') {
                        itemPrice = itemPrice * (1 - parseFloat(originalDiscount.amount) / 100);
                    }
                    if (originalFee && originalFee.amount && originalFee.amountType === 'percentage') {
                        itemPrice = itemPrice * (1 + parseFloat(originalFee.amount) / 100);
                    }
                }
                
                returnItemsTotal += Math.abs(itemPrice * Math.abs(item.qty)); // Store as positive
            } else {
                newItemsTotal += itemPrice * item.qty;
            }
        });
        
        const discountAmount = this.getDiscounts();
        const feeAmount = this.getFees();
        
        // Calculate net amount (new items + fees - discounts - return credit)
        let netAmount = newItemsTotal;
        netAmount += feeAmount;
        netAmount -= discountAmount;
        netAmount -= returnItemsTotal; // Subtract return credit

        // Consistent structure for Discount and Fee rows
        const rowStyle = 'flex items-center justify-between text-sm text-slate-200';
        const labelStyle = 'flex items-center gap-1';
        
        // Update display based on transaction type
        if (subtotalEl) {
            if (returnItemsTotal > 0 && newItemsTotal > 0) {
                // Exchange: Show both
                subtotalEl.innerHTML = `
                    <div class="text-xs space-y-1">
                        <div class="flex justify-between">
                            <span class="text-slate-400">New Items:</span>
                            <span class="text-green-400">$${newItemsTotal.toFixed(2)}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Return Credit:</span>
                            <span class="text-amber-400">-$${returnItemsTotal.toFixed(2)}</span>
                        </div>
                    </div>
                `;
            } else if (returnItemsTotal > 0) {
                // Return only
                subtotalEl.innerHTML = `<span class="text-amber-400">Return Credit: $${returnItemsTotal.toFixed(2)}</span>`;
            } else {
                // Regular purchase
                subtotalEl.textContent = `$${newItemsTotal.toFixed(2)}`;
            }
        }
        
        // Render discount row
        if (discount && discount.amount && discountRow) {
            // Format display based on type
            let displayAmount;
            let displayLabel;
            if (discount.amountType === 'percentage') {
                displayLabel = `Discount (${discount.amount}%)`;
                displayAmount = `-$${discountAmount.toFixed(2)}`;
            } else {
                displayLabel = 'Discount';
                displayAmount = `-$${discountAmount.toFixed(2)}`;
            }
            
            discountRow.innerHTML = `<div class='${rowStyle}'><span class='${labelStyle}'><button class='w-5 h-5 rounded bg-slate-600 hover:bg-red-500 text-white flex items-center justify-center' title='Remove Discount' id='remove-discount-btn'><i class='fa fa-times text-xs'></i></button><span class="text-slate-300 ml-1">${displayLabel}</span></span><span class="text-red-400 font-medium">${displayAmount}</span></div>`;
            
            setTimeout(() => {
                const btn = document.getElementById('remove-discount-btn');
                if (btn) btn.onclick = () => {
                    this.state.updateState('discount', { amount: '', label: '', amountType: 'flat' });
                    this.ui.showToast('Discount removed');
                    this.renderCart();
                };
            }, 0);
        }
        
        // Render fee row
        if (fee && fee.amount && feeRow) {
            // Format display based on type
            let displayAmount;
            let displayLabel;
            if (fee.amountType === 'percentage') {
                displayLabel = `Fee (${fee.amount}%)`;
                displayAmount = `+$${feeAmount.toFixed(2)}`;
            } else {
                displayLabel = 'Fee';
                displayAmount = `+$${feeAmount.toFixed(2)}`;
            }
            
            feeRow.innerHTML = `<div class='${rowStyle}'><span class='${labelStyle}'><button class='w-5 h-5 rounded bg-slate-600 hover:bg-red-500 text-white flex items-center justify-center' title='Remove Fee' id='remove-fee-btn'><i class='fa fa-times text-xs'></i></button><span class="text-slate-300 ml-1">${displayLabel}</span></span><span class="text-green-400 font-medium">${displayAmount}</span></div>`;
            
            setTimeout(() => {
                const btn = document.getElementById('remove-fee-btn');
                if (btn) btn.onclick = () => {
                    this.state.updateState('fee', { amount: '', label: '', amountType: 'flat' });
                    this.ui.showToast('Fee removed');
                    this.renderCart();
                };
            }, 0);
        }
        
        // Show empty cart message
        if (cart.items.length === 0 && !fee.amount && !discount.amount) {
            cartContainer.innerHTML = '<p class="text-center text-slate-400 text-xs py-6">Your cart is empty.</p>';
        }

        // Update total display with clear labeling
        const isRefund = netAmount < 0;
        const displayAmount = Math.abs(netAmount);
        
        if (totalEl) {
            if (isRefund) {
                totalEl.innerHTML = `<span class="text-green-400">Refund Due: $${displayAmount.toFixed(2)}</span>`;
            } else {
                totalEl.textContent = `$${displayAmount.toFixed(2)}`;
            }
        }
        
        if (totalBottomEl) {
            if (isRefund) {
                totalBottomEl.innerHTML = `<span class="text-green-400">Refund Due: $${displayAmount.toFixed(2)}</span>`;
            } else {
                totalBottomEl.textContent = `$${displayAmount.toFixed(2)}`;
            }
        }
        
        // Update summary with item count
        if (summaryEl) summaryEl.textContent = `${itemCount} item${itemCount !== 1 ? 's' : ''} (${qtyCount} unit${qtyCount !== 1 ? 's' : ''})`;

        // Update checkout button based on transaction type
        if (checkoutBtn) {
            if (isRefund) {
                checkoutBtn.textContent = 'Process Refund';
                checkoutBtn.classList.remove('bg-indigo-600', 'hover:bg-indigo-500');
                checkoutBtn.classList.add('bg-green-600', 'hover:bg-green-500');
            } else if (returnItemsTotal > 0) {
                checkoutBtn.textContent = 'Process Exchange';
                checkoutBtn.classList.remove('bg-indigo-600', 'hover:bg-indigo-500', 'bg-green-600', 'hover:bg-green-500');
                checkoutBtn.classList.add('bg-amber-600', 'hover:bg-amber-500');
            } else {
                checkoutBtn.textContent = 'Checkout';
                checkoutBtn.classList.remove('bg-green-600', 'hover:bg-green-500', 'bg-amber-600', 'hover:bg-amber-500');
                checkoutBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-500');
            }
        }
        
        // Persist cart state
        this.saveCartState();
    }

    /**
     * Save cart state to localStorage
     */
    saveCartState() {
        const cart = this.state.getState('cart');
        const fee = this.state.getState('fee');
        const discount = this.state.getState('discount');
        
        localStorage.setItem('jpos_cart', JSON.stringify(cart.items || []));
        localStorage.setItem('jpos_fee', JSON.stringify(fee || { amount: '', label: '', amountType: 'flat' }));
        localStorage.setItem('jpos_discount', JSON.stringify(discount || { amount: '', label: '', amountType: 'flat' }));
        
        // Also save customer if attached
        if (cart.customer) {
            localStorage.setItem('jpos_cart_customer', JSON.stringify(cart.customer));
        } else {
            localStorage.removeItem('jpos_cart_customer');
        }
    }

    /**
     * Load cart state from localStorage
     */
    loadCartState() {
        const savedCart = localStorage.getItem('jpos_cart');
        const savedFee = localStorage.getItem('jpos_fee');
        const savedDiscount = localStorage.getItem('jpos_discount');
        const savedCustomer = localStorage.getItem('jpos_cart_customer');
        
        this.state.updateState('cart.items', savedCart ? JSON.parse(savedCart) : []);
        this.state.updateState('fee', savedFee ? JSON.parse(savedFee) : { amount: '', label: '', amountType: 'flat' });
        this.state.updateState('discount', savedDiscount ? JSON.parse(savedDiscount) : { amount: '', label: '', amountType: 'flat' });
        this.state.updateState('cart.customer', savedCustomer ? JSON.parse(savedCustomer) : null);
    }

    /**
     * Get cart state for external use
     * @returns {Object} Cart state with items, fees, discounts, customer
     */
    getState() {
        return {
            items: this.state.getState('cart.items') || [],
            fee: this.state.getState('fee') || { amount: '', label: '', amountType: 'flat' },
            discount: this.state.getState('discount') || { amount: '', label: '', amountType: 'flat' },
            customer: this.state.getState('cart.customer') || null,
            subtotal: this.getSubtotal(),
            total: this.getTotal()
        };
    }

    /**
     * Show customer search modal
     */
    showCustomerSearch() {
        const modal = document.getElementById('customer-search-modal');
        const input = document.getElementById('customer-search-input');
        const results = document.getElementById('customer-search-results');
        
        if (modal) {
            modal.classList.remove('hidden');
            if (input) {
                input.value = '';
                input.focus();
            }
            if (results) {
                results.innerHTML = '<div class="text-center text-slate-400 py-4">Start typing to search customers...</div>';
            }
        }
    }

    /**
     * Hide customer search modal
     */
    hideCustomerSearch() {
        const modal = document.getElementById('customer-search-modal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    /**
     * Search customers via API
     * @param {String} query - Search query (name or email)
     */
    async searchCustomers(query) {
        const resultsContainer = document.getElementById('customer-search-results');
        
        if (!resultsContainer) return;
        
        // Require minimum 2 characters
        if (query.length < 2) {
            resultsContainer.innerHTML = '<div class="text-center text-slate-400 py-4">Enter at least 2 characters to search</div>';
            return;
        }
        
        // Show loading state
        resultsContainer.innerHTML = '<div class="text-center text-slate-400 py-4"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';
        
        try {
            // Get nonce from hidden input
            const nonceInput = document.getElementById('jpos-customer-search-nonce');
            const nonce = nonceInput ? nonceInput.value : '';
            
            if (!nonce) {
                throw new Error('Security token not found');
            }
            
            // Make API request
            const response = await fetch(`api/customers.php?query=${encodeURIComponent(query)}&nonce=${nonce}`);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Search failed');
            }
            
            if (data.success && data.data && data.data.customers) {
                const customers = data.data.customers;
                
                if (customers.length === 0) {
                    resultsContainer.innerHTML = '<div class="text-center text-slate-400 py-4">No customers found</div>';
                } else {
                    // Render customer results
                    resultsContainer.innerHTML = customers.map(customer => `
                        <div class="customer-result p-3 hover:bg-slate-700 cursor-pointer border-b border-slate-700 last:border-0 rounded-lg transition-colors"
                             onclick="window.attachCustomer(${customer.id}, '${customer.name.replace(/'/g, "\\'")}', '${customer.email.replace(/'/g, "\\'")}'); window.hideCustomerSearch();">
                            <div class="font-semibold text-slate-200">${customer.name}</div>
                            <div class="text-xs text-slate-400">${customer.email}</div>
                        </div>
                    `).join('');
                }
            } else {
                throw new Error('Invalid response format');
            }
        } catch (error) {
            console.error('Customer search error:', error);
            resultsContainer.innerHTML = `<div class="text-center text-red-400 py-4">Error: ${error.message}</div>`;
        }
    }

    /**
     * Toggle customer keyboard
     */
    toggleCustomerKeyboard() {
        const input = document.getElementById('customer-search-input');
        if (window.onScreenKeyboard && input) {
            window.onScreenKeyboard.toggle(input);
        }
    }

    /**
     * Apply fee to cart
     */
    applyFee() {
        const modal = document.getElementById('fee-discount-modal');
        const title = document.getElementById('fee-discount-modal-title');
        const input = document.getElementById('fee-discount-amount');
        const typeSelector = document.getElementById('fee-discount-type-selector');
        const percentSymbol = document.getElementById('fee-discount-percent-symbol');
        
        if (modal && title && input) {
            title.textContent = 'Add Fee';
            input.value = '';
            modal.classList.remove('hidden');
            
            // Hide percent symbol for flat (default)
            if (percentSymbol) percentSymbol.classList.add('hidden');
            
            // Reset to flat mode styling
            input.classList.remove('pr-8', 'pl-2');
            input.classList.add('px-2');
            input.placeholder = '0.00';
            
            // Reset to flat type
            if (typeSelector) {
                typeSelector.querySelectorAll('button').forEach(b => {
                    b.dataset.state = b.dataset.value === 'flat' ? 'active' : 'inactive';
                    if (b.dataset.value === 'flat') {
                        b.classList.add('bg-slate-200', 'text-slate-900');
                    } else {
                        b.classList.remove('bg-slate-200', 'text-slate-900');
                    }
                });
            }
            
            // Store that we're in fee mode
            modal.dataset.mode = 'fee';
        }
    }

    /**
     * Apply discount to cart
     */
    applyDiscount() {
        const modal = document.getElementById('fee-discount-modal');
        const title = document.getElementById('fee-discount-modal-title');
        const input = document.getElementById('fee-discount-amount');
        const typeSelector = document.getElementById('fee-discount-type-selector');
        const percentSymbol = document.getElementById('fee-discount-percent-symbol');
        
        if (modal && title && input) {
            title.textContent = 'Add Discount';
            input.value = '';
            modal.classList.remove('hidden');
            
            // Hide percent symbol for flat (default)
            if (percentSymbol) percentSymbol.classList.add('hidden');
            
            // Reset to flat mode styling
            input.classList.remove('pr-8', 'pl-2');
            input.classList.add('px-2');
            input.placeholder = '0.00';
            
            // Reset to flat type
            if (typeSelector) {
                typeSelector.querySelectorAll('button').forEach(b => {
                    b.dataset.state = b.dataset.value === 'flat' ? 'active' : 'inactive';
                    if (b.dataset.value === 'flat') {
                        b.classList.add('bg-slate-200', 'text-slate-900');
                    } else {
                        b.classList.remove('bg-slate-200', 'text-slate-900');
                    }
                });
            }
            
            // Store that we're in discount mode
            modal.dataset.mode = 'discount';
        }
    }
}

// Export for use in main.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CartManager;
}