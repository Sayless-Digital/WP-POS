// WP POS v1.9.0 - Held Carts Management Module
// Handles cart holding, restoration, and management

class HeldCartsManager {
    constructor(state, uiHelpers, cartManager) {
        this.state = state;
        this.ui = uiHelpers;
        this.cart = cartManager;
    }

    /**
     * Hold the current cart
     */
    holdCurrentCart() {
        const cart = this.state.getState('cart');
        const cartItems = cart?.items || [];
        
        if (cartItems.length === 0) {
            this.ui.showToast('Cart is empty.');
            return;
        }
        
        const heldCarts = JSON.parse(localStorage.getItem('jpos_held_carts') || '[]');
        const timestamp = new Date().toISOString();
        const fee = this.state.getState('fee') || { amount: '', label: '', amountType: 'flat' };
        const discount = this.state.getState('discount') || { amount: '', label: '', amountType: 'flat' };
        
        heldCarts.push({
            id: 'held_' + Date.now(),
            cart: JSON.parse(JSON.stringify(cartItems)),
            fee: JSON.parse(JSON.stringify(fee)),
            discount: JSON.parse(JSON.stringify(discount)),
            customer: cart.customer ? JSON.parse(JSON.stringify(cart.customer)) : null,
            time: timestamp
        });
        
        localStorage.setItem('jpos_held_carts', JSON.stringify(heldCarts));
        this.cart.clearCart(true);
        this.ui.showToast('Cart held successfully!');
        this.renderHeldCarts();
    }

    /**
     * Render held carts list
     */
    renderHeldCarts() {
        const list = document.getElementById('held-carts-list');
        const heldCarts = JSON.parse(localStorage.getItem('jpos_held_carts') || '[]');
        
        if (!list) return;
        
        list.innerHTML = '';
        
        if (heldCarts.length === 0) {
            list.innerHTML = '<div class="text-center text-slate-400 py-10">No held carts.</div>';
            return;
        }
        
        // Table header
        const table = document.createElement('div');
        table.className = 'w-full';
        table.innerHTML = `
            <div class="grid grid-cols-[auto,auto,auto,auto,auto,auto,auto] gap-3 font-bold text-xs text-slate-400 border-b border-slate-700 py-2 mb-2">
                <div class="w-44 text-left">Date Held</div>
                <div class="w-16 text-center">Items</div>
                <div class="w-32 text-center truncate">Customer</div>
                <div class="w-20 text-center">Fee</div>
                <div class="w-20 text-center">Discount</div>
                <div class="w-24 text-center">Total</div>
                <div class="w-40 text-right">Actions</div>
            </div>
        `;
        list.appendChild(table);
        
        heldCarts.forEach(held => {
            // Calculate subtotal
            let subtotal = 0;
            (held.cart || []).forEach(item => {
                subtotal += (parseFloat(item.price) || 0) * (item.qty || 0);
            });
            
            // Calculate fee
            let feeVal = 0;
            let feeDisplay = '-';
            if (held.fee && held.fee.amount) {
                if (held.fee.amountType === 'percentage') {
                    feeVal = subtotal * (parseFloat(held.fee.amount) / 100);
                    feeDisplay = `${held.fee.amount}%`;
                } else {
                    feeVal = parseFloat(held.fee.amount);
                    feeDisplay = `$${feeVal.toFixed(2)}`;
                }
            }
            
            // Calculate discount
            let discountVal = 0;
            let discountDisplay = '-';
            if (held.discount && held.discount.amount) {
                if (held.discount.amountType === 'percentage') {
                    discountVal = subtotal * (parseFloat(held.discount.amount) / 100);
                    discountDisplay = `${held.discount.amount}%`;
                } else {
                    discountVal = parseFloat(held.discount.amount);
                    discountDisplay = `$${discountVal.toFixed(2)}`;
                }
            }
            
            // Calculate total
            const total = subtotal + feeVal - Math.abs(discountVal);
            
            const row = document.createElement('div');
            row.className = 'grid grid-cols-[auto,auto,auto,auto,auto,auto,auto] gap-3 items-center bg-slate-800 border border-slate-700 rounded-lg mb-2 py-3 px-3 cursor-pointer hover:bg-slate-700/70';
            row.setAttribute('data-id', held.id);
            
            const customerDisplay = held.customer 
                ? `<div class="truncate" title="${held.customer.name}">${held.customer.name}</div>` 
                : '<div class="text-slate-500 text-sm">No customer</div>';
            
            row.innerHTML = `
                <div class="w-44 text-left text-slate-300 text-sm">${this.ui.formatRelativeDate(held.time)}</div>
                <div class="w-16 text-center text-slate-300 text-sm">${held.cart.length}</div>
                <div class="w-32 text-center truncate text-slate-300 text-sm">${customerDisplay}</div>
                <div class="w-20 text-center text-green-400 text-sm font-mono">${feeDisplay}</div>
                <div class="w-20 text-center text-amber-400 text-sm font-mono">${discountDisplay}</div>
                <div class="w-24 text-center text-slate-100 font-mono text-sm font-semibold">$${total.toFixed(2)}</div>
                <div class="w-40 flex justify-end gap-2 justify-self-end">
                    <button class="restore-held-btn bg-indigo-600 hover:bg-indigo-500 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors" data-id="${held.id}">Restore</button>
                    <button class="delete-held-btn bg-red-600 hover:bg-red-500 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors" data-id="${held.id}">Delete</button>
                </div>
            `;
            
            // Only open modal if not clicking on an action button
            row.addEventListener('click', (e) => {
                if (e.target.closest('button')) return;
                this.showHeldCartDetailsModal(held);
            });
            
            list.appendChild(row);
        });
        
        // Attach event listeners for restore and delete buttons
        list.querySelectorAll('.restore-held-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = btn.dataset.id;
                this.restoreHeldCart(id);
            });
        });
        
        list.querySelectorAll('.delete-held-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = btn.dataset.id;
                this.deleteHeldCart(id);
            });
        });
    }

    /**
     * Show held cart details modal
     * @private
     */
    showHeldCartDetailsModal(held) {
        const modal = document.getElementById('held-cart-details-modal');
        const content = document.getElementById('held-cart-details-content');
        
        let html = `<div class="mb-2"><span class="font-bold text-slate-300">Date Held:</span> ${this.ui.formatDateTime(held.time)}</div>`;
        html += `<div class="mb-2"><span class="font-bold text-slate-300">Items:</span></div>`;
        html += `<ul class="mb-4 pl-4 list-disc">`;
        
        (held.cart || []).forEach(item => {
            html += `<li class="text-slate-200">${item.name} <span class="text-xs text-slate-400">x${item.qty}</span> <span class="text-xs text-slate-500">($${parseFloat(item.price).toFixed(2)} each)</span></li>`;
        });
        
        html += `</ul>`;
        html += `<div class="mb-2"><span class="font-bold text-slate-300">Fee:</span> <span class="text-green-400">${held.fee.amount ? (held.fee.amountType === 'percentage' ? held.fee.amount + '%' : '$' + parseFloat(held.fee.amount).toFixed(2)) : '-'}</span></div>`;
        html += `<div class="mb-2"><span class="font-bold text-slate-300">Discount:</span> <span class="text-amber-400">${held.discount.amount ? (held.discount.amountType === 'percentage' ? held.discount.amount + '%' : '$' + parseFloat(held.discount.amount).toFixed(2)) : '-'}</span></div>`;
        
        let total = 0;
        (held.cart || []).forEach(item => {
            total += (parseFloat(item.price) || 0) * (item.qty || 0);
        });
        
        if (held.fee && held.fee.amount) {
            let feeVal = 0;
            if (held.fee.amountType === 'percentage') {
                feeVal = total * (parseFloat(held.fee.amount) / 100);
            } else {
                feeVal = parseFloat(held.fee.amount);
            }
            total += feeVal;
        }
        
        if (held.discount && held.discount.amount) {
            let discountVal = 0;
            if (held.discount.amountType === 'percentage') {
                discountVal = total * (parseFloat(held.discount.amount) / 100);
            } else {
                discountVal = parseFloat(held.discount.amount);
            }
            total -= Math.abs(discountVal);
        }
        
        html += `<div class="mb-2"><span class="font-bold text-slate-300">Total:</span> <span class="text-slate-100 font-mono">$${total.toFixed(2)}</span></div>`;
        
        content.innerHTML = html;
        modal.classList.remove('hidden');
        
        document.getElementById('held-cart-details-close').onclick = () => {
            modal.classList.add('hidden');
        };
    }

    /**
     * Restore a held cart to active cart
     * @param {string} id - Held cart ID
     */
    restoreHeldCart(id) {
        let heldCarts = JSON.parse(localStorage.getItem('jpos_held_carts') || '[]');
        const held = heldCarts.find(h => h.id === id);
        
        if (!held) return;
        
        // Use StateManager API to update state
        this.state.updateState('cart.items', held.cart);
        this.state.updateState('fee', held.fee);
        this.state.updateState('discount', held.discount);
        this.state.updateState('cart.customer', held.customer || null);
        
        this.cart.saveCartState();
        
        heldCarts = heldCarts.filter(h => h.id !== id);
        localStorage.setItem('jpos_held_carts', JSON.stringify(heldCarts));
        
        this.renderHeldCarts();
        
        if (window.routingManager) {
            window.routingManager.navigateToView('pos-page');
        }
        
        if (this.cart.renderCart) {
            this.cart.renderCart();
        }
        
        this.ui.showToast('Held cart restored to cart');
    }

    /**
     * Delete a held cart
     * @param {string} id - Held cart ID
     */
    deleteHeldCart(id) {
        let heldCarts = JSON.parse(localStorage.getItem('jpos_held_carts') || '[]');
        heldCarts = heldCarts.filter(h => h.id !== id);
        localStorage.setItem('jpos_held_carts', JSON.stringify(heldCarts));
        
        this.renderHeldCarts();
        this.ui.showToast('Held cart deleted');
    }
}

// Export as singleton
window.HeldCartsManager = HeldCartsManager;