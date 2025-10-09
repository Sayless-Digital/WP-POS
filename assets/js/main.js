// WP POS v1.9.142 - Fixed Print Reports to Open New Window
document.addEventListener('DOMContentLoaded', async () => {
    console.log('WP POS v1.9.142 loaded - Print reports open in new window');
    
    // Initialize State Manager (already global from state.js)
    const state = window.stateManager;
    
    // Initialize Routing Manager
    const routing = new RoutingManager();
    window.routingManager = routing;  // Expose to window for menu navigation
    
    // Initialize Core Managers
    const uiHelpers = new UIHelpers();
    const authManager = new AuthManager(state, uiHelpers);
    const drawerManager = new DrawerManager(state, uiHelpers);
    
    // Initialize Cart Manager first (needed by ProductsManager)
    const cartManager = new CartManager(state, uiHelpers);
    
    // Initialize Product Managers (ProductsManager needs cartManager)
    const productsManager = new ProductsManager(state, uiHelpers, cartManager);
    const productEditorManager = new ProductEditorManager(state, uiHelpers);
    
    // Initialize other Cart Managers
    const checkoutManager = new CheckoutManager(state, uiHelpers, cartManager, drawerManager);
    const heldCartsManager = new HeldCartsManager(state, uiHelpers, cartManager);
    
    // Initialize Order Managers
    const ordersManager = new OrdersManager(state, uiHelpers);
    const receiptsManager = new ReceiptsManager(state, uiHelpers);
    
    // Initialize Financial Managers
    const reportsManager = new ReportsManager(state, uiHelpers);
    const refundReportsManager = new RefundReportsManager(state, uiHelpers);
    
    // Initialize Admin Managers
    const settingsManager = new SettingsManager(state, uiHelpers);
    const sessionsManager = new SessionsManager(state, uiHelpers);
    const usersManager = new UsersManager(state, uiHelpers);
    
    // Load settings into state on app initialization
    await settingsManager.loadReceiptSettings();
    
    // Expose managers globally for routing and cross-module access
    window.uiHelpers = uiHelpers;
    window.authManager = authManager;
    window.drawerManager = drawerManager;
    window.productsManager = productsManager;
    window.productEditorManager = productEditorManager;
    window.cartManager = cartManager;
    window.checkoutManager = checkoutManager;
    window.heldCartsManager = heldCartsManager;
    window.ordersManager = ordersManager;
    window.receiptsManager = receiptsManager;
    window.reportsManager = reportsManager;
    window.refundReportsManager = refundReportsManager;
    window.settingsManager = settingsManager;
    window.sessionsManager = sessionsManager;
    window.usersManager = usersManager;
    
    // Expose routing helper functions (called by routing.js)
    window.fetchOrders = () => ordersManager.fetchOrders();
    window.fetchReportsData = () => reportsManager.fetchReportsData();
    window.fetchRefundReportsData = () => refundReportsManager.fetchRefundReportsData();
    window.fetchSessions = () => sessionsManager.fetchSessions();
    window.renderStockList = () => productsManager.renderStockList();
    window.populateSettingsForm = () => settingsManager.populateSettingsForm();
    window.renderHeldCarts = () => heldCartsManager.renderHeldCarts();
    window.loadUsersPage = async () => {
        // Load available roles for filter
        const rolesResponse = await fetch('api/wp-roles-setup.php?action=list');
        const rolesResult = await rolesResponse.json();
        if (rolesResult.success) {
            const roleFilter = document.getElementById('users-role-filter');
            if (roleFilter) {
                const roles = rolesResult.data.roles;
                roleFilter.innerHTML = '<option value="all">All Roles</option>' +
                    roles.map(role => `<option value="${role.slug}">${role.name}</option>`).join('');
            }
        }
        // Load users
        const users = await usersManager.loadUsers();
        usersManager.renderUsersList(users);
    };
    
    // Expose customer functions for HTML onclick handlers
    window.showCustomerSearch = () => cartManager.showCustomerSearch();
    window.hideCustomerSearch = () => cartManager.hideCustomerSearch();
    window.searchCustomers = (query) => cartManager.searchCustomers(query);
    window.attachCustomer = (id, name, email) => cartManager.attachCustomer(id, name, email);
    window.detachCustomer = () => cartManager.detachCustomer();
    window.toggleCustomerKeyboard = () => cartManager.toggleCustomerKeyboard();
    
    // Expose product editor functions for HTML onclick handlers
    window.openProductEditor = (productId) => productEditorManager.openProductEditor(productId);
    window.openStockEditModal = (productId) => productsManager.openStockEditModal(productId);
    
    // Expose users management functions for HTML onclick handlers
    window.editUser = (userId) => usersManager.showEditUserDialog(userId);
    window.deleteUser = (userId, username) => usersManager.deleteUser(userId, username);
    
    // Expose refund reports functions for HTML onclick handlers
    window.showRefundDetails = (refundId) => refundReportsManager.showRefundDetails(refundId);
    
    // Expose menu toggle function (used by menu button)
    window.toggleMenu = function() {
        const sideMenu = document.getElementById('side-menu');
        const menuOverlay = document.getElementById('menu-overlay');
        if (sideMenu) sideMenu.classList.toggle('is-open');
        if (menuOverlay) menuOverlay.classList.toggle('hidden');
    };
    
    // Expose cart action functions for buttons
    window.holdCurrentCart = () => heldCartsManager.holdCurrentCart();
    window.applyFee = () => cartManager.applyFee();
    window.applyDiscount = () => cartManager.applyDiscount();
    window.clearCart = () => cartManager.clearCart();
    
    // Expose checkout function
    window.openSplitPaymentModal = () => checkoutManager.openSplitPaymentModal();
    
    // Setup cart button event listeners
    function setupCartEventListeners() {
        // Checkout button
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn && !checkoutBtn.hasAttribute('data-listener')) {
            checkoutBtn.addEventListener('click', () => checkoutManager.openSplitPaymentModal());
            checkoutBtn.setAttribute('data-listener', 'true');
        }
        
        // Hold cart button
        const holdCartBtn = document.getElementById('hold-cart-btn');
        if (holdCartBtn && !holdCartBtn.hasAttribute('data-listener')) {
            holdCartBtn.addEventListener('click', () => heldCartsManager.holdCurrentCart());
            holdCartBtn.setAttribute('data-listener', 'true');
        }
        
        // Clear cart button
        const clearCartBtn = document.getElementById('clear-cart-btn');
        if (clearCartBtn && !clearCartBtn.hasAttribute('data-listener')) {
            clearCartBtn.addEventListener('click', () => {
                if (confirm('Clear all items from cart?')) {
                    cartManager.clearCart();
                }
            });
            clearCartBtn.setAttribute('data-listener', 'true');
        }
        
        // Add fee button (correct ID is "add-fee-btn" not "apply-fee-btn")
        const addFeeBtn = document.getElementById('add-fee-btn');
        if (addFeeBtn && !addFeeBtn.hasAttribute('data-listener')) {
            addFeeBtn.addEventListener('click', () => cartManager.applyFee());
            addFeeBtn.setAttribute('data-listener', 'true');
        }
        
        // Add discount button (correct ID is "add-discount-btn" not "apply-discount-btn")
        const addDiscountBtn = document.getElementById('add-discount-btn');
        if (addDiscountBtn && !addDiscountBtn.hasAttribute('data-listener')) {
            addDiscountBtn.addEventListener('click', () => cartManager.applyDiscount());
            addDiscountBtn.setAttribute('data-listener', 'true');
        }
        
        // Attach customer button
        const attachCustomerBtn = document.getElementById('attach-customer-btn');
        if (attachCustomerBtn && !attachCustomerBtn.hasAttribute('data-listener')) {
            attachCustomerBtn.addEventListener('click', () => cartManager.showCustomerSearch());
            attachCustomerBtn.setAttribute('data-listener', 'true');
        }
    }
    
    /**
     * Apply RBAC restrictions to UI elements
     */
    function applyRBACRestrictions() {
        // Get user roles - check if user is admin or shop manager first
        const userRoles = window.authManager ? window.authManager.getUserRoles() : [];
        
        console.log('RBAC - User roles:', userRoles);
        
        // Convert roles object to array if needed
        // WordPress can return: {0: 'administrator', 2: 'yith_pos_cashier'} or {administrator: true}
        let rolesArray = [];
        if (userRoles && typeof userRoles === 'object') {
            if (Array.isArray(userRoles)) {
                rolesArray = userRoles;
            } else {
                // Use Object.values() to get role names, not indices
                rolesArray = Object.values(userRoles);
            }
        }
        
        console.log('RBAC - Roles array:', rolesArray);
        
        // If no roles loaded, don't apply any restrictions (safety fallback)
        if (!rolesArray || rolesArray.length === 0) {
            console.log('⚠️ No user roles loaded - RBAC restrictions disabled (safety fallback)');
            return; // Don't restrict anything if we can't determine roles
        }
        
        // Admins and shop managers see everything - no restrictions
        if (rolesArray.includes('administrator') || rolesArray.includes('shop_manager')) {
            console.log('✓ User is administrator or shop_manager - full access granted');
            return; // Exit early - no restrictions for admins
        }
        
        console.log('→ Applying capability-based restrictions for non-admin user');
        
        // Check if global userCan function is available
        if (!window.userCan) {
            console.warn('RBAC functions not initialized - skipping restrictions');
            return; // Don't hide anything if capability checking isn't ready
        }
        
        // Menu item restrictions based on capabilities
        const menuRestrictions = {
            'menu-button-reports': 'wppos_view_reports',
            'menu-button-sessions': 'wppos_view_sessions',
            'menu-button-settings': 'wppos_manage_settings',
            'menu-button-products': 'wppos_view_products'
        };
        
        Object.entries(menuRestrictions).forEach(([buttonId, capability]) => {
            const btn = document.getElementById(buttonId);
            if (btn && !window.userCan(capability)) {
                btn.style.display = 'none';
            }
        });
        
        // Feature-level restrictions
        // Hide drawer management buttons for cashiers
        if (!window.userCan('wppos_manage_drawer')) {
            const openDrawerBtn = document.getElementById('open-drawer-btn');
            const closeDrawerBtn = document.getElementById('close-drawer-btn');
            if (openDrawerBtn) openDrawerBtn.style.display = 'none';
            if (closeDrawerBtn) closeDrawerBtn.style.display = 'none';
        }
        
        // Hide product editing for cashiers
        if (!window.userCan('wppos_manage_products')) {
            // Product editing will be restricted when those buttons are clicked
            console.log('Product editing restricted for this user');
        }
        
        // Hide settings management
        if (!window.userCan('wppos_manage_settings')) {
            const settingsBtn = document.getElementById('menu-button-settings');
            if (settingsBtn) settingsBtn.style.display = 'none';
        }
    }
    
    // Setup all other event listeners
    function setupAllEventListeners() {
        // Menu toggle buttons (all pages)
        document.querySelectorAll('.menu-toggle').forEach(btn => {
            btn.addEventListener('click', () => window.toggleMenu());
        });
        
        // Menu overlay close
        const menuOverlay = document.getElementById('menu-overlay');
        if (menuOverlay) {
            menuOverlay.addEventListener('click', () => window.toggleMenu());
        }
        
        // Menu navigation buttons
        const menuButtons = {
            'menu-button-pos': 'pos-page',
            'menu-button-orders': 'orders-page',
            'menu-button-reports': 'reports-page',
            'menu-button-refunds': 'refunds-exchange-reports-page',
            'menu-button-sessions': 'sessions-page',
            'menu-button-products': 'products-page',
            'menu-button-held-carts': 'held-carts-page',
            'menu-button-settings': 'settings-page',
            'menu-button-users': 'users-page'
        };
        
        Object.entries(menuButtons).forEach(([buttonId, viewId]) => {
            const btn = document.getElementById(buttonId);
            if (btn) {
                btn.addEventListener('click', () => {
                    window.routingManager.navigateToView(viewId);
                });
            }
        });
        
        // Search toggle button (POS page)
        const searchToggleBtn = document.getElementById('search-toggle-btn');
        if (searchToggleBtn) {
            searchToggleBtn.addEventListener('click', () => {
                const currentType = state.getState('filters.searchType');
                const newType = currentType === 'name' ? 'sku' : 'name';
                state.updateState('filters.searchType', newType);
                
                const nameIcon = document.getElementById('search-icon-name');
                const skuIcon = document.getElementById('search-icon-sku');
                
                if (newType === 'sku') {
                    nameIcon.classList.add('hidden');
                    skuIcon.classList.remove('hidden');
                } else {
                    nameIcon.classList.remove('hidden');
                    skuIcon.classList.add('hidden');
                }
            });
        }
        
        // Stock filter segmented controls (both POS and Products pages)
        ['stock-filter', 'products-stock-filter'].forEach(filterId => {
            const filterControl = document.getElementById(filterId);
            if (filterControl) {
                filterControl.querySelectorAll('button').forEach(btn => {
                    btn.addEventListener('click', () => {
                        // Update active state
                        filterControl.querySelectorAll('button').forEach(b => {
                            b.dataset.state = 'inactive';
                        });
                        btn.dataset.state = 'active';
                        
                        // Update filter and re-render
                        if (filterId === 'stock-filter') {
                            state.updateState('filters.stock', btn.dataset.value);
                            productsManager.renderProductGrid();
                        } else {
                            state.updateState('stockFilters.stock', btn.dataset.value);
                            productsManager.renderStockList();
                        }
                    });
                });
            }
        });
        
        // Category and tag filters
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                state.updateState('filters.category', e.target.value);
                productsManager.renderProductGrid();
            });
        }
        
        const tagFilter = document.getElementById('tag-filter');
        if (tagFilter) {
            tagFilter.addEventListener('change', (e) => {
                state.updateState('filters.tag', e.target.value);
                productsManager.renderProductGrid();
            });
        }
        
        // Products page filters
        const productsCategoryFilter = document.getElementById('products-category-filter');
        if (productsCategoryFilter) {
            productsCategoryFilter.addEventListener('change', (e) => {
                state.updateState('stockFilters.category', e.target.value);
                productsManager.renderStockList();
            });
        }
        
        const productsTagFilter = document.getElementById('products-tag-filter');
        if (productsTagFilter) {
            productsTagFilter.addEventListener('change', (e) => {
                state.updateState('stockFilters.tag', e.target.value);
                productsManager.renderStockList();
            });
        }
        
        // Search input
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                state.updateState('filters.search', e.target.value);
                productsManager.renderProductGrid();
            });
        }
        
        // Products list filter
        const productsListFilter = document.getElementById('products-list-filter');
        if (productsListFilter) {
            productsListFilter.addEventListener('input', () => {
                productsManager.renderStockList();
            });
        }
        
        // Refresh buttons
        const refreshButtons = {
            'refresh-pos-btn': () => {
                productsManager.fetchProducts().then(() => {
                    productsManager.renderProductGrid();
                    uiHelpers.showToast('POS data refreshed');
                });
            },
            'refresh-orders-btn': () => ordersManager.fetchOrders(),
            'refresh-reports-btn': () => reportsManager.fetchReportsData(),
            'refresh-refunds-btn': () => refundReportsManager.fetchRefundReportsData(),
            'refresh-sessions-btn': () => sessionsManager.fetchSessions(),
            'refresh-products-btn': () => {
                productsManager.fetchProducts().then(() => {
                    productsManager.renderStockList();
                    uiHelpers.showToast('Products refreshed');
                });
            },
            'refresh-settings-btn': () => settingsManager.populateSettingsForm(),
            'refresh-held-carts-btn': () => heldCartsManager.renderHeldCarts(),
            'refresh-users-btn': async () => {
                const searchTerm = document.getElementById('users-search')?.value || '';
                const roleFilter = document.getElementById('users-role-filter')?.value || 'all';
                const users = await usersManager.loadUsers(searchTerm, roleFilter);
                usersManager.renderUsersList(users);
                uiHelpers.showToast('Users refreshed');
            }
        };
        
        Object.entries(refreshButtons).forEach(([buttonId, handler]) => {
            const btn = document.getElementById(buttonId);
            if (btn) btn.addEventListener('click', handler);
        });
        
        // Orders page filters
        const orderDateFilter = document.getElementById('order-date-filter');
        if (orderDateFilter) {
            orderDateFilter.addEventListener('change', (e) => {
                state.updateState('orders.filters.date', e.target.value);
                ordersManager.fetchOrders();
            });
        }
        
        const orderSourceFilter = document.getElementById('order-source-filter');
        if (orderSourceFilter) {
            orderSourceFilter.addEventListener('change', (e) => {
                state.updateState('orders.filters.source', e.target.value);
                ordersManager.fetchOrders();
            });
        }
        
        const orderStatusFilter = document.getElementById('order-status-filter');
        if (orderStatusFilter) {
            orderStatusFilter.addEventListener('change', (e) => {
                state.updateState('orders.filters.status', e.target.value);
                ordersManager.fetchOrders();
            });
        }
        
        const orderIdSearch = document.getElementById('order-id-search');
        if (orderIdSearch) {
            orderIdSearch.addEventListener('input', (e) => {
                state.updateState('orders.filters.orderId', e.target.value);
                ordersManager.renderOrders();
            });
        }
        
        // Customer filter for orders
        const orderCustomerFilter = document.getElementById('order-customer-filter');
        if (orderCustomerFilter) {
            let debounceTimer;
            orderCustomerFilter.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    ordersManager.searchCustomersForFilter(e.target.value);
                }, 300);
            });
        }
        
        const clearCustomerFilterBtn = document.getElementById('clear-customer-filter-btn');
        if (clearCustomerFilterBtn) {
            clearCustomerFilterBtn.addEventListener('click', () => {
                ordersManager.clearCustomerFilter();
            });
        }
        
        // Reports page controls
        const reportsPeriodSelect = document.getElementById('reports-period-select');
        if (reportsPeriodSelect) {
            reportsPeriodSelect.addEventListener('change', (e) => {
                const customDateRange = document.getElementById('custom-date-range');
                if (e.target.value === 'custom') {
                    customDateRange.classList.remove('hidden');
                } else {
                    customDateRange.classList.add('hidden');
                    reportsManager.updateChartPeriod(e.target.value);
                }
            });
        }
        
        const customStartDate = document.getElementById('custom-start-date');
        const customEndDate = document.getElementById('custom-end-date');
        if (customStartDate && customEndDate) {
            customStartDate.addEventListener('change', () => {
                if (document.getElementById('reports-period-select').value === 'custom') {
                    reportsManager.updateChartPeriod('custom');
                }
            });
            customEndDate.addEventListener('change', () => {
                if (document.getElementById('reports-period-select').value === 'custom') {
                    reportsManager.updateChartPeriod('custom');
                }
            });
        }
        
        const printReportsBtn = document.getElementById('print-reports-btn');
        if (printReportsBtn) {
            printReportsBtn.addEventListener('click', () => {
                reportsManager.generatePrintReport();
                document.getElementById('print-report-modal').classList.remove('hidden');
            });
        }
        
        const printReportPrintBtn = document.getElementById('print-report-print-btn');
        if (printReportPrintBtn) {
            printReportPrintBtn.addEventListener('click', () => {
                const modalTitle = document.querySelector('#print-report-modal h2').textContent;
                if (modalTitle.includes('Refunds')) {
                    refundReportsManager.printReport();
                } else {
                    reportsManager.printReport();
                }
            });
        }
        
        const printReportCloseBtn = document.getElementById('print-report-close-btn');
        if (printReportCloseBtn) {
            printReportCloseBtn.addEventListener('click', () => {
                document.getElementById('print-report-modal').classList.add('hidden');
            });
        }
        
        // Refund Reports page controls
        const refundsPeriodSelect = document.getElementById('refunds-period-select');
        if (refundsPeriodSelect) {
            refundsPeriodSelect.addEventListener('change', (e) => {
                const customDateRange = document.getElementById('refund-custom-date-range');
                if (e.target.value === 'custom') {
                    customDateRange.classList.remove('hidden');
                } else {
                    customDateRange.classList.add('hidden');
                    refundReportsManager.updateRefundPeriod(e.target.value);
                }
            });
        }
        
        const refundCustomStartDate = document.getElementById('refund-custom-start-date');
        const refundCustomEndDate = document.getElementById('refund-custom-end-date');
        if (refundCustomStartDate && refundCustomEndDate) {
            refundCustomStartDate.addEventListener('change', () => {
                if (document.getElementById('refunds-period-select').value === 'custom') {
                    refundReportsManager.updateRefundPeriod('custom');
                }
            });
            refundCustomEndDate.addEventListener('change', () => {
                if (document.getElementById('refunds-period-select').value === 'custom') {
                    refundReportsManager.updateRefundPeriod('custom');
                }
            });
        }
        
        const printRefundsBtn = document.getElementById('print-refunds-btn');
        if (printRefundsBtn) {
            printRefundsBtn.addEventListener('click', () => {
                refundReportsManager.generatePrintReport();
                document.getElementById('print-report-modal').classList.remove('hidden');
            });
        }
        
        // Settings tabs
        const settingsTabs = {
            'settings-tab-receipt': 'settings-panel-receipt',
            'settings-tab-keyboard': 'settings-panel-keyboard',
            'settings-tab-general': 'settings-panel-general'
        };
        
        Object.entries(settingsTabs).forEach(([tabId, panelId]) => {
            const tab = document.getElementById(tabId);
            if (tab) {
                tab.addEventListener('click', () => {
                    // Hide all panels
                    document.querySelectorAll('.settings-panel').forEach(panel => {
                        panel.classList.add('hidden');
                    });
                    
                    // Remove active from all tabs
                    document.querySelectorAll('.settings-tab').forEach(t => {
                        t.classList.remove('border-indigo-500', 'text-indigo-400', 'bg-slate-700/50');
                        t.classList.add('border-transparent', 'text-slate-400');
                    });
                    
                    // Show selected panel
                    document.getElementById(panelId).classList.remove('hidden');
                    
                    // Set active tab
                    tab.classList.add('border-indigo-500', 'text-indigo-400', 'bg-slate-700/50');
                    tab.classList.remove('border-transparent', 'text-slate-400');
                });
            }
        });
        
        // Save settings button (in header)
        const saveSettingsBtn = document.getElementById('save-settings-btn');
        if (saveSettingsBtn) {
            saveSettingsBtn.addEventListener('click', (e) => {
                settingsManager.saveSettings(e);
            });
        }
        
        // Settings form submit (for form submit via enter key)
        const settingsForm = document.getElementById('settings-form');
        if (settingsForm) {
            settingsForm.addEventListener('submit', (e) => {
                settingsManager.saveSettings(e);
            });
        }
        
        // Held cart details close
        const heldCartDetailsClose = document.getElementById('held-cart-details-close');
        if (heldCartDetailsClose) {
            heldCartDetailsClose.addEventListener('click', () => {
                document.getElementById('held-cart-details-modal').classList.add('hidden');
            });
        }
        
        // Customer search input with debouncing
        const customerSearchInput = document.getElementById('customer-search-input');
        if (customerSearchInput) {
            let debounceTimer;
            customerSearchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    cartManager.searchCustomers(e.target.value);
                }, 300);
            });
        }
        
        // Users page event listeners
        usersManager.setupEventListeners();
        
        // Apply RBAC restrictions after setting up listeners
        applyRBACRestrictions();
    }
    
    // Expose setup functions
    window.setupCartEventListeners = setupCartEventListeners;
    window.setupAllEventListeners = setupAllEventListeners;
    window.applyRBACRestrictions = applyRBACRestrictions;
    
    // Initialize authentication and load app
    // Event listeners will be set up by auth.js after successful login
    await authManager.init();
});
