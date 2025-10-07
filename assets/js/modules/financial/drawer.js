// WP POS v1.9.0 - Cash Drawer Management Module
// Handles drawer open/close operations, status checking, and UI updates

class DrawerManager {
    constructor(state, uiHelpers) {
        this.state = state;
        this.ui = uiHelpers;
        
        // Setup drawer modal event listeners
        this.setupDrawerModal();
    }
    
    /**
     * Setup drawer modal event listeners
     */
    setupDrawerModal() {
        // Open drawer form submit
        const openForm = document.getElementById('drawer-open-form');
        if (openForm) {
            openForm.addEventListener('submit', (e) => this.handleOpenDrawer(e));
        }
        
        // Close drawer form submit
        const closeForm = document.getElementById('drawer-close-form');
        if (closeForm) {
            closeForm.addEventListener('submit', (e) => this.handleCloseDrawer(e));
        }
        
        // Cancel close button
        const cancelCloseBtn = document.getElementById('drawer-cancel-close-btn');
        if (cancelCloseBtn) {
            cancelCloseBtn.addEventListener('click', () => {
                this.showDrawerModal(false);
            });
        }
        
        // Summary OK button
        const summaryOkBtn = document.getElementById('drawer-summary-ok-btn');
        if (summaryOkBtn) {
            summaryOkBtn.addEventListener('click', () => {
                this.showDrawerModal(false);
            });
        }
    }

    /**
     * Check the current status of the cash drawer
     * @returns {Promise<void>}
     */
    async checkDrawerStatus() {
        try {
            const response = await fetch(`/jpos/api/drawer.php?action=get_status`);
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            
            if (result.success) {
                this.state.updateState('drawer.isOpen', result.isOpen);
                this.state.updateState('drawer.data', result.drawer);
                
                if (!result.isOpen && !localStorage.getItem('jpos_drawer_dismissed_temp')) {
                    this.showDrawerModal('open');
                }
                localStorage.removeItem('jpos_drawer_dismissed_temp');
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            alert(`Critical Error: Could not check drawer status. ${error.message}`);
        } finally {
            this.updateDrawerUI();
        }
    }

    /**
     * Display drawer modal with specified view
     * @param {string|boolean} view - The view to show ('open', 'close', 'summary', or false to hide)
     */
    showDrawerModal(view) {
        const modal = document.getElementById('drawer-modal');
        const form = document.getElementById('drawer-open-form');
        let cancelButton = form.querySelector('.cancel-drawer-btn');
        if (cancelButton) cancelButton.remove();
        
        if (view === 'open') {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = 'Do Later';
            button.className = 'cancel-drawer-btn w-full mt-1 text-sm text-slate-400 hover:text-white';
            button.onclick = () => {
                localStorage.setItem('jpos_drawer_dismissed_temp', 'true');
                this.showDrawerModal(false);
            };
            form.appendChild(button);
        }

        if (!view) {
            modal.classList.add('hidden');
            return;
        }
        
        modal.classList.remove('hidden');
        ['open', 'close', 'summary'].forEach(v => {
            document.getElementById(`drawer-${v}-view`).classList.toggle('hidden', v !== view);
        });
    }

    /**
     * Update drawer UI indicators and buttons based on current state
     */
    updateDrawerUI() {
        const indicator = document.getElementById('drawer-status-indicator');
        const checkoutBtn = document.getElementById('checkout-btn');
        const drawerBtn = document.getElementById('close-drawer-btn');

        const isOpen = this.state.getState('drawer.isOpen');
        const drawerData = this.state.getState('drawer.data');
        
        if (isOpen) {
            indicator.classList.remove('bg-gray-500');
            indicator.classList.add('bg-green-400');
            indicator.title = drawerData?.time_opened ? `Drawer open since ${this.ui.formatDateTime(drawerData.time_opened)}` : 'Drawer Open';
            checkoutBtn.disabled = false;
            drawerBtn.textContent = 'Close Drawer';
            drawerBtn.classList.remove('bg-green-600', 'hover:bg-green-500');
            drawerBtn.classList.add('bg-slate-700', 'hover:bg-red-600');
            drawerBtn.onclick = () => this.showDrawerModal('close');
        } else {
            indicator.classList.remove('bg-green-400');
            indicator.classList.add('bg-gray-500');
            indicator.title = 'Drawer Closed';
            checkoutBtn.disabled = true;
            drawerBtn.textContent = 'Open Drawer';
            drawerBtn.classList.remove('bg-slate-700', 'hover:bg-red-600');
            drawerBtn.classList.add('bg-green-600', 'hover:bg-green-500');
            drawerBtn.onclick = () => this.showDrawerModal('open', true);
        }
        
        // Trigger product re-render if available
        if (window.renderProducts) {
            window.renderProducts();
        }
    }

    /**
     * Handle opening the cash drawer
     * @param {Event} e - Form submit event
     * @returns {Promise<void>}
     */
    async handleOpenDrawer(e) {
        e.preventDefault();
        const amountInput = document.getElementById('opening-amount');
        const amount = parseFloat(amountInput.value);
        
        if (isNaN(amount) || amount < 0) {
            alert("Please enter a valid opening amount.");
            return;
        }
        
        const data = {
            action: 'open',
            openingAmount: amount,
            nonce: this.state.getState('nonces.drawer')
        };
        
        try {
            const response = await fetch('/jpos/api/drawer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            
            if (result.success) {
                await this.checkDrawerStatus();
                this.showDrawerModal(false);
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            alert(`Network Error: ${error.message}`);
        }
    }

    /**
     * Handle closing the cash drawer
     * @param {Event} e - Form submit event
     * @returns {Promise<void>}
     */
    async handleCloseDrawer(e) {
        e.preventDefault();
        const amountInput = document.getElementById('closing-amount');
        const amount = parseFloat(amountInput.value);
        
        if (isNaN(amount) || amount < 0) {
            alert("Please enter a valid closing amount.");
            return;
        }
        
        const data = {
            action: 'close',
            closingAmount: amount,
            nonce: this.state.getState('nonces.drawer')
        };
        
        try {
            const response = await fetch('/jpos/api/drawer.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            
            if (result.success) {
                const summary = result.data;
                const contentEl = document.getElementById('drawer-summary-content');
                const difference = parseFloat(summary.difference || 0);
                let diffColor = 'text-green-400';
                
                if (difference < 0) diffColor = 'text-red-400';
                else if (difference > 0) diffColor = 'text-yellow-400';
                
                contentEl.innerHTML = `
                    <div class="flex justify-between"><span>Opening Amount:</span><span>$${parseFloat(summary.opening_amount || 0).toFixed(2)}</span></div>
                    <div class="flex justify-between"><span>Cash Sales:</span><span>$${parseFloat(summary.cash_sales || 0).toFixed(2)}</span></div>
                    <div class="flex justify-between font-bold border-t border-slate-600 pt-2"><span>Expected in Drawer:</span><span>$${parseFloat(summary.expected_amount || 0).toFixed(2)}</span></div>
                    <div class="flex justify-between"><span>Amount Counted:</span><span>$${parseFloat(summary.closing_amount || 0).toFixed(2)}</span></div>
                    <div class="flex justify-between font-bold text-lg ${diffColor} border-t border-slate-600 pt-2"><span>Difference:</span><span>$${difference.toFixed(2)}</span></div>
                `;
                
                this.showDrawerModal('summary');
                await this.checkDrawerStatus();
                amountInput.value = '';
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            alert(`Network Error: ${error.message}`);
        }
    }
}

// Export as singleton
window.DrawerManager = DrawerManager;