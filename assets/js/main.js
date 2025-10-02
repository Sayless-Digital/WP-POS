// JPOS v1.6.0 - Production-ready product editing system with database-driven attribute suggestions - CACHE BUST
document.addEventListener('DOMContentLoaded', () => {
    console.log('JPOS v1.6.0 loaded - Production-ready product editing system');
    // Initialize Routing Manager
    const routingManager = new RoutingManager();

    // Centralized State Management
    const appState = {
        // Authentication & User
        auth: {
            user: null,
            isLoggedIn: false
        },
        
        // Cash Drawer Management
        drawer: {
            isOpen: false,
            data: null,
            openingAmount: 0,
            closingAmount: 0
        },
        
        // Product & Inventory Management
        products: {
            all: [],
            currentForModal: null,
            stockList: [],
            editingProduct: null
        },
        
        // Shopping Cart & Checkout
        cart: {
            items: [],
            paymentMethod: 'Cash',
            fee: { amount: '', label: '', amountType: 'flat' },
            discount: { amount: '', label: '', amountType: 'flat' },
            feeDiscount: { type: null, amount: '', label: '', amountType: 'flat' },
            splitPayments: null
        },
        
        // Orders Management
        orders: {
            all: [],
            filters: { date: 'all', status: 'all', source: 'all', orderId: '' }
        },
        
        // Product Filters
        filters: {
            search: '',
            searchType: 'name',
            stock: 'all',
            category: 'all',
            tag: 'all'
        },
        
        // Stock Management Filters
        stockFilters: {
            stock: 'all',
            category: 'all',
            tag: 'all'
        },
        
        // Returns & Refunds
        returns: {
            fromOrderId: null,
            items: []
        },
        
        // Application Settings
        settings: {
            receipt: {},
            session: {
                sessions: [],
                charts: {}
            }
        },
        
        // Security Tokens
        nonces: {
            login: '',
            logout: '',
            checkout: '',
            settings: '',
            drawer: '',
            stock: '',
            refund: ''
        },
        
        // UI State
        ui: {
            currentPage: 'products',
            isLoading: false,
            error: null
        }
    };

    // State Management Utilities
    function updateState(path, value) {
        const keys = path.split('.');
        let current = appState;
        
        for (let i = 0; i < keys.length - 1; i++) {
            if (!current[keys[i]]) {
                current[keys[i]] = {};
            }
            current = current[keys[i]];
        }
        
        current[keys[keys.length - 1]] = value;
        return current[keys[keys.length - 1]];
    }
    
    function getState(path) {
        const keys = path.split('.');
        let current = appState;
        
        for (const key of keys) {
            if (current && typeof current === 'object' && key in current) {
                current = current[key];
            } else {
                return undefined;
            }
        }
        
        return current;
    }
    
    function validateState() {
        // Validate critical state consistency
        if (appState.auth.isLoggedIn && !appState.auth.user) {
            console.warn('State inconsistency: isLoggedIn is true but user is null');
            appState.auth.isLoggedIn = false;
        }
        
        if (appState.drawer.isOpen && !appState.drawer.data) {
            console.warn('State inconsistency: drawer is open but data is null');
        }
        
        // Validate cart consistency
        if (appState.cart.items && !Array.isArray(appState.cart.items)) {
            console.warn('State inconsistency: cart.items should be an array');
            appState.cart.items = [];
        }
    }
    
    function resetState() {
        // Reset state to initial values
        appState.auth = { user: null, isLoggedIn: false };
        appState.drawer = { isOpen: false, data: null, openingAmount: 0, closingAmount: 0 };
        appState.cart.items = [];
        appState.cart.paymentMethod = 'Cash';
        appState.cart.fee = { amount: '', label: '', amountType: 'flat' };
        appState.cart.discount = { amount: '', label: '', amountType: 'flat' };
        appState.cart.feeDiscount = { type: null, amount: '', label: '', amountType: 'flat' };
        appState.cart.splitPayments = null;
        appState.returns.fromOrderId = null;
        appState.returns.items = [];
        appState.ui.error = null;
    }

    function getSkeletonLoaderHtml(type = 'list-rows', count = null) {
        let rowHtml = '';
        let columns = 6;
        let actualCount;
        if (type === 'variation-edit-rows') {
            columns = 4;
            actualCount = count !== null ? count : 4;
        } else if (type === 'reports-page') {
            return `<div class="skeleton-loader reports-page">
                <div class="kpi-row">
                    <div class="kpi-block"><div class="block"></div><div class="block"></div></div>
                    <div class="kpi-block"><div class="block"></div><div class="block"></div></div>
                    <div class="kpi-block"><div class="block"></div><div class="block"></div></div>
                </div>
                <div class="chart-row">
                    <div class="chart-block"></div>
                    <div class="chart-block"></div>
                </div>
            </div>`;
        } else {
            actualCount = count !== null ? count : 20;
        }

        for (let i = 0; i < actualCount; i++) {
            let blocks = '';
            for (let j = 1; j <= columns; j++) {
                blocks += `<div class="block"></div>`;
            }
            rowHtml += `<div class="row">${blocks}</div>`;
        }
        return `<div class="skeleton-loader ${type}">${rowHtml}</div>`;
    }

    async function init() {
        setupEventListeners();
        await generateNonces(); // Generate nonces immediately for login form
        await checkAuthStatus();
    }

    async function checkAuthStatus() {
        try {
            const response = await fetch('/jpos/api/auth.php?action=check_status');
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            
            // Handle WordPress wp_send_json_success response structure
            const loggedIn = result.data?.loggedIn || result.loggedIn;
            const userData = result.data?.user || result.user;
            
            
            if (result.success && loggedIn && userData) {
                appState.auth.user = userData;
                appState.auth.isLoggedIn = true;
                await loadFullApp();
            } else {
                console.log('Authentication failed - showing login screen');
                showLoginScreen(true);
            }
        } catch (error) {
            console.error("Auth check failed:", error);
            showLoginScreen(true, "Could not connect to server.");
        }
    }
    
    async function checkDrawerStatus() {
        try {
            const response = await fetch(`/jpos/api/drawer.php?action=get_status`);
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            if (result.success) {
                appState.drawer.isOpen = result.isOpen;
                appState.drawer.data = result.drawer;
                if (!result.isOpen && !localStorage.getItem('jpos_drawer_dismissed_temp')) {
                     showDrawerModal('open');
                }
                localStorage.removeItem('jpos_drawer_dismissed_temp');
            } else { throw new Error(result.message); }
        } catch (error) {
            alert(`Critical Error: Could not check drawer status. ${error.message}`);
        } finally {
            updateDrawerUI();
        }
    }
    
    async function loadFullApp() {
        showLoginScreen(false);
        setupMainAppEventListeners();
        
        // Show main app but keep preloader visible until routing is complete
        document.getElementById('main-app').classList.remove('hidden');
        
        // Update user profile information safely
        const userDisplayName = document.getElementById('user-display-name'); // Side menu
        const headerUserDisplayName = document.getElementById('header-user-display-name'); // Header
        const userEmail = document.getElementById('user-email');
        const userData = getState('auth.user');
        
        const displayName = getState('auth.user.displayName') || 'User';
        const emailValue = getState('auth.user.email');
        
        
        if (userDisplayName) {
            userDisplayName.textContent = displayName;
        }
        if (headerUserDisplayName) {
            headerUserDisplayName.textContent = displayName;
        }
        if (userEmail) {
            userEmail.textContent = emailValue || 'No email';
        }
        await generateNonces();
        await loadReceiptSettings();
        await refreshAllData();
        
        // Initialize with current view from URL or default to pos-page
        const initialView = routingManager.getCurrentView();
        routingManager.navigateToView(initialView, false);
        
        // Hide preloader after routing is complete
        hideAppPreloader(); // Don't update URL on initial load
        
        await checkDrawerStatus();
        validateState(); // Validate state after loading
    }

    async function generateNonces() {
        try {
            // Generate nonces for CSRF protection
            appState.nonces.login = document.getElementById('jpos-login-nonce')?.value || '';
            appState.nonces.logout = document.getElementById('jpos-logout-nonce')?.value || '';
            appState.nonces.checkout = document.getElementById('jpos-checkout-nonce')?.value || '';
            appState.nonces.settings = document.getElementById('jpos-settings-nonce')?.value || '';
            appState.nonces.drawer = document.getElementById('jpos-drawer-nonce')?.value || '';
            appState.nonces.stock = document.getElementById('jpos-stock-nonce')?.value || '';
            appState.nonces.refund = document.getElementById('jpos-refund-nonce')?.value || '';
            appState.nonces.productEdit = document.getElementById('jpos-product-edit-nonce')?.value || '';
        } catch (error) {
            console.error('Error generating nonces:', error);
        }
    }

    async function loadReceiptSettings() {
        try {
            const response = await fetch('/jpos/api/settings.php');
            if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
            const result = await response.json();
            if (result.success) {
                appState.settings = result.data;
            } else { throw new Error(result.message || 'Failed to parse settings.'); }
        } catch (error) {
            console.error("Could not load receipt settings.", error);
            appState.settings = { name: "Store Name", email: "", phone: "", address: "", footer_message_1: "Thank you!", footer_message_2: "" };
            alert('Warning: Could not load store settings. Receipts may display default info.');
        }
    }

    async function refreshAllData() {
        showSkeletonLoader();
        try {
            const response = await fetch('/jpos/api/products.php');
            if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
            const result = await response.json();
            if(!result.success) throw new Error(result.data.message || 'Failed to load products.');

            appState.products.all = result.data.products || [];
            if (document.getElementById('category-filter').options.length <= 1) {
                buildFilterUI(result.data.categories || [], result.data.tags || []);
            }
            renderProducts();
        } catch (error) {
            document.getElementById('product-list').innerHTML = `<p class="col-span-full text-center text-red-400">Error: Could not load product data. ${error.message}</p>`;
        }
    }


    function showLoginScreen(show, message = '') {
        const loginScreen = document.getElementById('login-screen');
        const mainApp = document.getElementById('main-app');
        document.getElementById('login-error').textContent = message;
        loginScreen.classList.toggle('hidden', !show);
        mainApp.classList.toggle('hidden', show);
    }
    
    function hideAppPreloader() {
        const preloader = document.getElementById('app-preloader');
        if (preloader) {
            preloader.classList.add('hidden');
            // Remove from DOM after transition completes
            setTimeout(() => {
                if (preloader.parentNode) {
                    preloader.parentNode.removeChild(preloader);
                }
            }, 300); // Match CSS transition duration
        }
    }
    
    function showDrawerModal(view) {
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
                showDrawerModal(false);
            };
            form.appendChild(button);
        }

        if (!view) { modal.classList.add('hidden'); return; }
        modal.classList.remove('hidden');
        ['open', 'close', 'summary'].forEach(v => {
            document.getElementById(`drawer-${v}-view`).classList.toggle('hidden', v !== view);
        });
    }

    // Utility function for consistent date/time formatting
    function formatDateTime(dt) {
        if (!dt) return '';
        const date = typeof dt === 'string' ? new Date(dt) : dt;
        if (isNaN(date.getTime())) return dt;
        return date.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    function updateDrawerUI() {
        const indicator = document.getElementById('drawer-status-indicator');
        const checkoutBtn = document.getElementById('checkout-btn');
        const drawerBtn = document.getElementById('close-drawer-btn'); 

        if (appState.drawer.isOpen) {
            indicator.classList.remove('bg-gray-500');
            indicator.classList.add('bg-green-400');
            indicator.title = `Drawer open since ${formatDateTime(appState.drawer.data.time_opened)}`;
            checkoutBtn.disabled = false;
            drawerBtn.textContent = 'Close Drawer';
            drawerBtn.classList.remove('bg-green-600', 'hover:bg-green-500');
            drawerBtn.classList.add('bg-slate-700', 'hover:bg-red-600');
            drawerBtn.onclick = () => showDrawerModal('close');
        } else {
            indicator.classList.remove('bg-green-400');
            indicator.classList.add('bg-gray-500');
            indicator.title = 'Drawer Closed';
            checkoutBtn.disabled = true;
            drawerBtn.textContent = 'Open Drawer';
            drawerBtn.classList.remove('bg-slate-700', 'hover:bg-red-600');
            drawerBtn.classList.add('bg-green-600', 'hover:bg-green-500');
            drawerBtn.onclick = () => showDrawerModal('open', true);
        }
        renderProducts();
    }
    
    function toggleMenu() { 
        const sideMenu = document.getElementById('side-menu');
        const menuOverlay = document.getElementById('menu-overlay');
        
        if (sideMenu) {
            sideMenu.classList.toggle('is-open');
        }
        
        if (menuOverlay) {
            menuOverlay.classList.toggle('hidden');
        }
    }
    
    // Make toggleMenu globally available (needed early)
    window.toggleMenu = toggleMenu;
    
    async function showPage(pageId, closeMenu = true) {
        // Use routing manager for navigation
        routingManager.navigateToView(pageId, true);
    }

    async function handleLogin(e) {
        e.preventDefault();
        const form = e.target;
        const button = form.querySelector('button');
        button.disabled = true;
        document.getElementById('login-error').textContent = '';
        
        const data = { action: 'login', username: form.username.value, password: form.password.value, nonce: appState.nonces.login };
        try {
            const response = await fetch('/jpos/api/auth.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const responseText = await response.text();
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}, body: ${responseText}`);
            const result = JSON.parse(responseText);
            if (result.success) {
                // Handle WordPress wp_send_json_success response structure
                const userData = result.data?.user || result.user;
                if (userData) {
                    updateState('auth.user', userData);
                    updateState('auth.isLoggedIn', true);
                    form.reset();
                    await loadFullApp();
                } else {
                    showLoginScreen(true, 'Login successful but user data not received.');
                }
            } else {
                showLoginScreen(true, result.message || result.data?.message || 'Login failed.');
            }
        } catch (error) {
            console.error("Login error details:", error);
            showLoginScreen(true, `Network error during login. Please try again.`);
        } finally {
            button.disabled = false;
        }
    }
    
    async function handleLogout() {
        if (getState('drawer.isOpen')) { alert("Please close the cash drawer before logging out."); return; }
        await fetch('/jpos/api/auth.php?action=logout&nonce=' + encodeURIComponent(getState('nonces.logout')));
        resetState();
        updateDrawerUI();
        showLoginScreen(true);
    }

    async function handleOpenDrawer(e) {
        e.preventDefault();
        const amountInput = document.getElementById('opening-amount');
        const amount = parseFloat(amountInput.value);
        if (isNaN(amount) || amount < 0) { alert("Please enter a valid opening amount."); return; }
        const data = { action: 'open', openingAmount: amount, nonce: appState.nonces.drawer };
        try {
            const response = await fetch('/jpos/api/drawer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            if (result.success) {
                await checkDrawerStatus();
                showDrawerModal(false);
            } else { alert(`Error: ${result.message}`); }
        } catch (error) { alert(`Network Error: ${error.message}`); }
    }

    async function handleCloseDrawer(e) {
        e.preventDefault();
        const amountInput = document.getElementById('closing-amount');
        const amount = parseFloat(amountInput.value);
        if (isNaN(amount) || amount < 0) { alert("Please enter a valid closing amount."); return; }
        const data = { action: 'close', closingAmount: amount, nonce: appState.nonces.drawer };
        try {
            const response = await fetch('/jpos/api/drawer.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            if (result.success) {
                const summary = result.data;
                const contentEl = document.getElementById('drawer-summary-content');
                const difference = parseFloat(summary.difference || 0);
                let diffColor = 'text-green-400';
                if(difference < 0) diffColor = 'text-red-400';
                else if(difference > 0) diffColor = 'text-yellow-400';
                contentEl.innerHTML = `<div class="flex justify-between"><span>Opening Amount:</span><span>$${parseFloat(summary.opening_amount || 0).toFixed(2)}</span></div><div class="flex justify-between"><span>Cash Sales:</span><span>$${parseFloat(summary.cash_sales || 0).toFixed(2)}</span></div><div class="flex justify-between font-bold border-t border-slate-600 pt-2"><span>Expected in Drawer:</span><span>$${parseFloat(summary.expected_amount || 0).toFixed(2)}</span></div><div class="flex justify-between"><span>Amount Counted:</span><span>$${parseFloat(summary.closing_amount || 0).toFixed(2)}</span></div><div class="flex justify-between font-bold text-lg ${diffColor} border-t border-slate-600 pt-2"><span>Difference:</span><span>$${difference.toFixed(2)}</span></div>`;
                showDrawerModal('summary');
                await checkDrawerStatus();
                amountInput.value = '';
            } else { alert(`Error: ${result.message}`); }
        } catch (error) { alert(`Network Error: ${error.message}`); }
    }

    function setupEventListeners() {
        document.getElementById('login-form').addEventListener('submit', handleLogin);
    }
    
    function setupMainAppEventListeners() {
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) logoutBtn.addEventListener('click', handleLogout);
        const closeDrawerBtn = document.getElementById('close-drawer-btn');
        if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', updateDrawerUI); 
        const drawerOpenForm = document.getElementById('drawer-open-form');
        if (drawerOpenForm) drawerOpenForm.addEventListener('submit', handleOpenDrawer);
        const drawerCloseForm = document.getElementById('drawer-close-form');
        if (drawerCloseForm) drawerCloseForm.addEventListener('submit', handleCloseDrawer);
        const drawerCancelCloseBtn = document.getElementById('drawer-cancel-close-btn');
        if (drawerCancelCloseBtn) drawerCancelCloseBtn.addEventListener('click', () => showDrawerModal(false));
        const drawerSummaryOkBtn = document.getElementById('drawer-summary-ok-btn');
        if (drawerSummaryOkBtn) drawerSummaryOkBtn.addEventListener('click', () => { showDrawerModal(false); });
        
        document.querySelectorAll('.menu-toggle').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                toggleMenu();
            });
        });
        const menuOverlay = document.getElementById('menu-overlay');
        if (menuOverlay) {
            menuOverlay.addEventListener('click', (e) => {
                e.preventDefault();
                toggleMenu();
            });
        }
        
        const menuButtonPos = document.getElementById('menu-button-pos');
        if (menuButtonPos) menuButtonPos.addEventListener('click', () => routingManager.navigateToView('pos-page'));
        const menuButtonOrders = document.getElementById('menu-button-orders');
        if (menuButtonOrders) menuButtonOrders.addEventListener('click', () => routingManager.navigateToView('orders-page'));
        const menuButtonReports = document.getElementById('menu-button-reports');
        if (menuButtonReports) menuButtonReports.addEventListener('click', () => routingManager.navigateToView('reports-page'));
        const menuButtonSessions = document.getElementById('menu-button-sessions');
        if (menuButtonSessions) menuButtonSessions.addEventListener('click', () => routingManager.navigateToView('sessions-page'));
        const menuButtonProducts = document.getElementById('menu-button-products');
        if (menuButtonProducts) menuButtonProducts.addEventListener('click', () => routingManager.navigateToView('products-page'));
        const menuButtonSettings = document.getElementById('menu-button-settings');
        if (menuButtonSettings) menuButtonSettings.addEventListener('click', () => routingManager.navigateToView('settings-page'));
        
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', e => { appState.filters.search = e.target.value; renderProducts(); });
            searchInput.addEventListener('keypress', handlePOSSearch);
        }
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) categoryFilter.addEventListener('change', e => { appState.filters.category = e.target.value; renderProducts(); });
        const tagFilter = document.getElementById('tag-filter');
        if (tagFilter) tagFilter.addEventListener('change', e => { appState.filters.tag = e.target.value; renderProducts(); });
        const searchToggleBtn = document.getElementById('search-toggle-btn');
        if (searchToggleBtn) searchToggleBtn.addEventListener('click', () => { const isName = appState.filters.searchType === 'name'; appState.filters.searchType = isName ? 'sku' : 'name'; document.getElementById('search-input').placeholder = isName ? 'Search by name...' : 'Search by SKU...'; document.getElementById('search-icon-name').classList.toggle('hidden', !isName); document.getElementById('search-icon-sku').classList.toggle('hidden', isName); renderProducts(); });
        const stockFilter = document.getElementById('stock-filter');
        if (stockFilter) stockFilter.addEventListener('click', e => { const target = e.target.closest('button'); if (!target) return; appState.filters.stock = target.dataset.value; document.querySelectorAll('#stock-filter button').forEach(btn => btn.dataset.state = 'inactive'); target.dataset.state = 'active'; renderProducts(); });
        
        const checkoutBtn = document.getElementById('checkout-btn');
        if (checkoutBtn) checkoutBtn.addEventListener('click', processTransaction);
        const clearCartBtn = document.getElementById('clear-cart-btn');
        if (clearCartBtn) clearCartBtn.addEventListener('click', () => clearCart(true));
        const modalCancelBtn = document.getElementById('modal-cancel-btn');
        if (modalCancelBtn) modalCancelBtn.addEventListener('click', () => document.getElementById('variation-modal').classList.add('hidden'));
        const printReceiptBtn = document.getElementById('print-receipt-btn');
        if (printReceiptBtn) printReceiptBtn.addEventListener('click', printReceipt);
        const closeReceiptBtn = document.getElementById('close-receipt-btn');
        if (closeReceiptBtn) closeReceiptBtn.addEventListener('click', closeReceiptModal);
        
        const returnModalCancelBtn = document.getElementById('return-modal-cancel-btn');
        if (returnModalCancelBtn) returnModalCancelBtn.addEventListener('click', () => document.getElementById('return-modal').classList.add('hidden'));
        const returnModalAddToCartBtn = document.getElementById('return-modal-add-to-cart-btn');
        if (returnModalAddToCartBtn) returnModalAddToCartBtn.addEventListener('click', handleAddReturnItemsToCart);

        const addFeeBtn = document.getElementById('add-fee-btn');
        if (addFeeBtn) addFeeBtn.addEventListener('click', () => showFeeDiscountModal('fee'));
        const addDiscountBtn = document.getElementById('add-discount-btn');
        if (addDiscountBtn) addDiscountBtn.addEventListener('click', () => showFeeDiscountModal('discount'));
        document.querySelectorAll('.num-pad-btn').forEach(button => button.addEventListener('click', handleNumPadInput));
        const numPadBackspace = document.getElementById('num-pad-backspace');
        if (numPadBackspace) numPadBackspace.addEventListener('click', handleNumPadBackspace);
        const feeDiscountTypeSelector = document.getElementById('fee-discount-type-selector');
        if (feeDiscountTypeSelector) feeDiscountTypeSelector.addEventListener('click', handleFeeDiscountTypeToggle);
        const feeDiscountCancelBtn = document.getElementById('fee-discount-cancel-btn');
        if (feeDiscountCancelBtn) feeDiscountCancelBtn.addEventListener('click', () => { appState.feeDiscount = { type: null, amount: '', label: '', amountType: 'flat' }; hideFeeDiscountModal(); });
        const feeDiscountApplyBtn = document.getElementById('fee-discount-apply-btn');
        if (feeDiscountApplyBtn) feeDiscountApplyBtn.addEventListener('click', applyFeeDiscount);

        const orderDateFilter = document.getElementById('order-date-filter');
        if (orderDateFilter) orderDateFilter.addEventListener('change', e => { appState.orders.filters.date = e.target.value; fetchOrders(); });
        const orderSourceFilter = document.getElementById('order-source-filter');
        if (orderSourceFilter) orderSourceFilter.addEventListener('change', e => { appState.orders.filters.source = e.target.value; fetchOrders(); });
        const orderStatusFilter = document.getElementById('order-status-filter');
        if (orderStatusFilter) orderStatusFilter.addEventListener('change', e => { appState.orders.filters.status = e.target.value; fetchOrders(); });
        const settingsForm = document.getElementById('settings-form');
        if (settingsForm) settingsForm.addEventListener('submit', saveSettings);
        const productsListFilter = document.getElementById('products-list-filter');
        if (productsListFilter) productsListFilter.addEventListener('input', () => renderStockList());
        const stockEditCancelBtn = document.getElementById('stock-edit-cancel-btn');
        if (stockEditCancelBtn) stockEditCancelBtn.addEventListener('click', () => document.getElementById('stock-edit-modal').classList.add('hidden'));
        const stockEditSaveBtn = document.getElementById('stock-edit-save-btn');
        if (stockEditSaveBtn) stockEditSaveBtn.addEventListener('click', handleStockEditSave);
        
        // Product Editor Modal Event Listeners
        const productEditorCloseBtn = document.getElementById('product-editor-close');
        if (productEditorCloseBtn) productEditorCloseBtn.addEventListener('click', () => document.getElementById('product-editor-modal').classList.add('hidden'));
        
        const productEditorCancelBtn = document.getElementById('product-editor-cancel');
        if (productEditorCancelBtn) productEditorCancelBtn.addEventListener('click', () => document.getElementById('product-editor-modal').classList.add('hidden'));
        
        // Product Editor Tab Event Listeners
        const formTabBtn = document.getElementById('form-tab');
        if (formTabBtn) formTabBtn.addEventListener('click', switchToFormView);
        
        const jsonTabBtn = document.getElementById('json-tab');
        if (jsonTabBtn) jsonTabBtn.addEventListener('click', switchToJSONView);
        
        const productEditorSaveBtn = document.getElementById('product-editor-save');
        if (productEditorSaveBtn) productEditorSaveBtn.addEventListener('click', saveProductEditor);
        
        // JSON View Action Buttons
        const productEditorCancelJsonBtn = document.getElementById('product-editor-cancel-json');
        if (productEditorCancelJsonBtn) productEditorCancelJsonBtn.addEventListener('click', () => document.getElementById('product-editor-modal').classList.add('hidden'));
        
        const productEditorSaveJsonBtn = document.getElementById('product-editor-save-json');
        if (productEditorSaveJsonBtn) productEditorSaveJsonBtn.addEventListener('click', saveProductEditor);
        
        const addMetaDataBtn = document.getElementById('add-meta-data');
        if (addMetaDataBtn) addMetaDataBtn.addEventListener('click', addMetaDataRow);
        
        // Meta Data Accordion Toggle
        const metaDataAccordionToggle = document.getElementById('meta-data-accordion-toggle');
        if (metaDataAccordionToggle) {
            metaDataAccordionToggle.addEventListener('click', toggleMetaDataAccordion);
        }
        
        // Attributes Accordion Toggle
        const attributesAccordionToggle = document.getElementById('attributes-accordion-toggle');
        if (attributesAccordionToggle) {
            attributesAccordionToggle.addEventListener('click', toggleAttributesAccordion);
        }
        
        // Variations Accordion Toggle
        const variationsAccordionToggle = document.getElementById('variations-accordion-toggle');
        if (variationsAccordionToggle) {
            variationsAccordionToggle.addEventListener('click', toggleVariationsAccordion);
        }
        
        // Add Attribute Button
        const addAttributeBtn = document.getElementById('add-attribute');
        if (addAttributeBtn) addAttributeBtn.addEventListener('click', addAttributeRow);
        
        // Add Variation Button
        const addVariationBtn = document.getElementById('add-variation');
        if (addVariationBtn) addVariationBtn.addEventListener('click', addVariationRow);
        
        // Add event listeners for attribute option inputs (will be added dynamically)
        document.addEventListener('input', function(e) {
            if (e.target.id && e.target.id.startsWith('attribute-option-input-')) {
                const attributeIndex = e.target.id.split('-')[3];
                showAttributeSuggestions(attributeIndex, e.target.value);
            }
        });
        
        // Also add keyup listener for better compatibility
        document.addEventListener('keyup', function(e) {
            if (e.target.id && e.target.id.startsWith('attribute-option-input-')) {
                const attributeIndex = e.target.id.split('-')[3];
                showAttributeSuggestions(attributeIndex, e.target.value);
            }
        });
        
        // Add focus listener to show suggestions when input is focused
        document.addEventListener('focus', function(e) {
            if (e.target.id && e.target.id.startsWith('attribute-option-input-')) {
                const attributeIndex = e.target.id.split('-')[3];
                if (e.target.value.trim()) {
                    showAttributeSuggestions(attributeIndex, e.target.value);
                }
            }
        }, true);
        
        // Add keypress listener for Enter/comma
        document.addEventListener('keypress', function(e) {
            if (e.target.id && e.target.id.startsWith('attribute-option-input-')) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const attributeIndex = e.target.id.split('-')[3];
                    addAttributeOption(attributeIndex, e.target.value);
                } else if (e.key === 'Escape') {
                    const attributeIndex = e.target.id.split('-')[3];
                    hideAttributeSuggestions(attributeIndex);
                }
            }
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.relative')) {
                document.querySelectorAll('[id^="attribute-option-suggestions-"]').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });
        
        // Event delegation for remove option buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-option-btn')) {
                const button = e.target.closest('.remove-option-btn');
                const optionSpan = button.closest('span[data-option]');
                if (optionSpan) {
                    optionSpan.remove();
                }
            }
        });
        
        // Add event listeners for form field changes to update JSON preview
        // Form input event listeners removed - JSON preview now only in JSON tab
        
        const smCat = document.getElementById('products-category-filter');
        const smTag = document.getElementById('products-tag-filter');
        const smStock = document.getElementById('products-stock-filter');
        if (smCat) smCat.addEventListener('change', e => { appState.stockFilters.category = e.target.value; renderStockList(); });
        if (smTag) smTag.addEventListener('change', e => { appState.stockFilters.tag = e.target.value; renderStockList(); });
        if (smStock) smStock.addEventListener('click', e => { const target = e.target.closest('button'); if (!target) return; appState.stockFilters.stock = target.dataset.value; document.querySelectorAll('#products-stock-filter button').forEach(btn => btn.dataset.state = 'inactive'); target.dataset.state = 'active'; renderStockList(); });
        const refreshPosBtn = document.getElementById('refresh-pos-btn');
        if (refreshPosBtn) refreshPosBtn.addEventListener('click', () => {
            // Hard refresh: force reload from server, bypass cache
            window.location.reload(true);
        });
        
        // Add refresh buttons for all pages
        const refreshOrdersBtn = document.getElementById('refresh-orders-btn');
        if (refreshOrdersBtn) refreshOrdersBtn.addEventListener('click', () => {
            window.location.reload(true);
        });
        
        const refreshReportsBtn = document.getElementById('refresh-reports-btn');
        if (refreshReportsBtn) refreshReportsBtn.addEventListener('click', () => {
            window.location.reload(true);
        });
        
        const refreshSessionsBtn = document.getElementById('refresh-sessions-btn');
        if (refreshSessionsBtn) refreshSessionsBtn.addEventListener('click', () => {
            window.location.reload(true);
        });
        
        const refreshProductsBtn = document.getElementById('refresh-products-btn');
        if (refreshProductsBtn) refreshProductsBtn.addEventListener('click', () => {
            window.location.reload(true);
        });
        
        const refreshSettingsBtn = document.getElementById('refresh-settings-btn');
        if (refreshSettingsBtn) refreshSettingsBtn.addEventListener('click', () => {
            window.location.reload(true);
        });
        
        const refreshHeldCartsBtn = document.getElementById('refresh-held-carts-btn');
        if (refreshHeldCartsBtn) refreshHeldCartsBtn.addEventListener('click', () => {
            window.location.reload(true);
        });
        const menuButtonHeldCarts = document.getElementById('menu-button-held-carts');
        if (menuButtonHeldCarts) menuButtonHeldCarts.addEventListener('click', () => routingManager.navigateToView('held-carts-page'));
        addHoldCartButton();
        const holdCartBtn = document.getElementById('hold-cart-btn');
        if (holdCartBtn) holdCartBtn.addEventListener('click', holdCurrentCart);
        // Add this after other event listeners
        const orderIdSearch = document.getElementById('order-id-search');
        if (orderIdSearch) {
            orderIdSearch.addEventListener('input', e => {
                appState.orders.filters.orderId = e.target.value.trim();
                renderOrders();
            });
        }
        const splitPaymentBtn = document.getElementById('split-payment-btn');
        if (splitPaymentBtn) {
            splitPaymentBtn.addEventListener('click', openSplitPaymentModal);
        }
        const splitPaymentCancel = document.getElementById('split-payment-cancel');
        if (splitPaymentCancel) {
            splitPaymentCancel.addEventListener('click', () => {
                document.getElementById('split-payment-modal').classList.add('hidden');
            });
        }
        // PDF Export functionality
        const exportPdfBtn = document.getElementById('export-pdf-btn');
        if (exportPdfBtn) {
            exportPdfBtn.addEventListener('click', exportReportsToPDF);
        }
    }

    function showSkeletonLoader() {
        const container = document.getElementById('product-list'); 
        container.innerHTML = '';
        for (let i = 0; i < 30; i++) { 
            container.innerHTML += `<div class="border border-slate-700 rounded-xl bg-slate-800 animate-pulse"><div class="w-full aspect-square bg-slate-700 rounded-t-xl"></div><div class="p-3"><div class="h-4 bg-slate-700 rounded w-3/4 mb-2"></div><div class="h-3 bg-slate-700 rounded w-1/2"></div></div></div>`; 
        }
    }

    function buildFilterUI(categories, tags) {
        ['category-filter', 'products-category-filter'].forEach(id => {
            const catSelect = document.getElementById(id);
            if(catSelect) {
                catSelect.innerHTML = '<option value="all">All Categories</option>';
                categories.forEach(cat => catSelect.innerHTML += `<option value="${cat.term_id}">${cat.name}</option>`);
            }
        });
        ['tag-filter', 'products-tag-filter'].forEach(id => {
            const tagSelect = document.getElementById(id);
            if(tagSelect) {
                tagSelect.innerHTML = '<option value="all">All Tags</option>';
                tags.forEach(tag => tagSelect.innerHTML += `<option value="${tag.term_id}">${tag.name}</option>`);
            }
        });
    }

    async function handlePOSSearch(e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        if (appState.filters.searchType !== 'sku') return;
        const searchInput = document.getElementById('search-input');
        const searchValue = searchInput.value.trim();
        if (!searchValue) return;
        
        const foundProduct = appState.products.all.find(p => p.sku === searchValue || (p.variations && p.variations.some(v => v.sku === searchValue)));
        if(foundProduct) {
            await handleProductClick(foundProduct.id, searchValue);
            searchInput.value = '';
            appState.filters.search = '';
            renderProducts();
        } else {
            alert('SKU not found');
        }
    }

    function renderProducts() {
        const container = document.getElementById('product-list'); container.innerHTML = '';
        const filteredProducts = appState.products.all.filter(p => {
            if (appState.filters.searchType === 'sku') return true;
            const searchLower = appState.filters.search.toLowerCase();
            const categoryMatch = appState.filters.category === 'all' || (p.category_ids || []).includes(parseInt(appState.filters.category));
            const tagMatch = appState.filters.tag === 'all' || (p.tag_ids || []).includes(parseInt(appState.filters.tag));
            let stockMatch;
            if (appState.filters.stock === 'private') {
                stockMatch = p.post_status === 'private';
            } else {
                stockMatch = appState.filters.stock === 'all' || p.stock_status === appState.filters.stock;
            }
            const searchMatch = appState.filters.search === '' || (p.name && p.name.toLowerCase().includes(searchLower));
            return searchMatch && stockMatch && categoryMatch && tagMatch;
        });

        if (filteredProducts.length === 0) { container.innerHTML = '<p class="col-span-full text-center text-slate-400 p-10">No products match criteria.</p>'; return; }
        filteredProducts.forEach(p => {
            const el = document.createElement('div'); el.setAttribute('role', 'button'); el.setAttribute('tabindex', '0');
            const isOutOfStock = p.stock_status === 'outofstock';
            let highlightClass = '';
            let badgeHtml = '';
            if (p.post_status === 'private') {
                highlightClass = 'border-indigo-400 ring-2 ring-indigo-400';
                badgeHtml = `<div class="absolute top-2 left-2 bg-indigo-500/60 backdrop-blur-sm px-2 py-1 rounded-full text-xs font-bold text-white" style="height:1.5rem;display:flex;align-items:center;">Private</div>`;
            }
            el.className = `group flex flex-col cursor-pointer border border-slate-700 rounded-xl bg-slate-800 text-left hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all overflow-hidden relative ${isOutOfStock ? 'opacity-40' : ''} ${highlightClass}`;
            // Use regular src for now to fix the disappearing image issue
            const imageHTML = p.image_url ? 
                `<img src="${p.image_url}" alt="${p.name}" class="w-full h-full object-cover transition-transform group-hover:scale-105" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">` : 
                '';
            const placeholderHTML = `<div class="w-full h-full bg-slate-700 flex items-center justify-center text-slate-400 text-xs font-bold px-2 text-center" style="display: ${p.image_url ? 'none' : 'flex'}; line-height: 1.2;">${p.sku || 'N/A'}</div>`;
            
            let priceDisplay;
            if (p.type === 'variable' && p.min_price !== null) {
                priceDisplay = `$${parseFloat(p.min_price).toFixed(2)}`;
            } else if (p.price) {
                priceDisplay = `$${parseFloat(p.price || 0).toFixed(2)}`;
            } else {
                priceDisplay = 'N/A';
            }
            
            let stockDisplayHtml = '';
            if (p.manages_stock && p.stock_quantity !== null) {
                stockDisplayHtml = `<span class="font-bold ${p.stock_quantity > 5 ? 'text-green-300' : 'text-orange-300'}">${p.stock_quantity}</span> in stock`;
            } else {
                stockDisplayHtml = `<span class="text-slate-300">${isOutOfStock ? 'Out of stock' : 'In Stock'}</span>`;
            }

            el.innerHTML = `<div class="aspect-square w-full flex-shrink-0 overflow-hidden relative">${imageHTML}${placeholderHTML}${badgeHtml}<div class="absolute top-2 right-2 bg-slate-900/60 backdrop-blur-sm px-2 py-1 rounded-full text-xs">${stockDisplayHtml}</div></div><div class="p-3 flex flex-col flex-grow"><h3 class="font-semibold text-sm text-slate-100 leading-tight line-clamp-2 flex-grow">${p.name}</h3><p class="text-xs text-slate-400 font-mono mt-1">SKU: ${p.sku || 'N/A'}</p><p class="text-sm text-green-400 mt-2 font-bold">${priceDisplay}</p></div>`;
            if (!isOutOfStock) { el.onclick = () => handleProductClick(p.id); el.onkeydown = (e) => { if (e.key === 'Enter' || e.key === ' ') handleProductClick(p.id); }; }
            else { el.classList.add('cursor-not-allowed'); }
            container.appendChild(el);
        });
    }

    async function handleProductClick(productId, preselectedSku = null) {
        const product = appState.products.all.find(p => p.id === productId); if (!product || product.stock_status === 'outofstock') return;
        if (product.type === 'simple') { addToCart(product, 1); }
        else { appState.products.currentForModal = product; await showVariationModal(preselectedSku); }
    }

    async function showVariationModal(preselectedSku = null) {
        if (!appState.products.currentForModal) return;
        document.getElementById('modal-product-name').textContent = appState.products.currentForModal.name;
        document.getElementById('modal-product-sku').textContent = `SKU: ${appState.products.currentForModal.sku || 'N/A'}`;
        document.getElementById('modal-image').src = appState.products.currentForModal.image_url || '';
        const optionsContainer = document.getElementById('modal-options-container');
        optionsContainer.innerHTML = '';
        
        let priceDisplayEl = document.getElementById('modal-variation-price');
        if (!priceDisplayEl) {
            priceDisplayEl = document.createElement('p');
            priceDisplayEl.id = 'modal-variation-price';
            priceDisplayEl.className = 'text-xl font-bold text-green-400 mt-2 mb-4';
            optionsContainer.parentElement.insertBefore(priceDisplayEl, optionsContainer);
        }

        // --- BEGIN HELD STOCK LOGIC ---
        // Aggregate held quantities for all variations
        const heldCarts = JSON.parse(localStorage.getItem('jpos_held_carts') || '[]');
        const heldQtyByVariationId = {};
        heldCarts.forEach(held => {
            (held.cart || []).forEach(item => {
                if (item.id && typeof item.qty === 'number') {
                    heldQtyByVariationId[item.id] = (heldQtyByVariationId[item.id] || 0) + item.qty;
                }
            });
        });
        // --- END HELD STOCK LOGIC ---

        const attributes = {};
        (appState.products.currentForModal.variations || []).forEach(v => { for (const key in v.attributes) { if (!attributes[key]) attributes[key] = new Set(); attributes[key].add(v.attributes[key]); } });

        Object.entries(attributes).forEach(([attrKey, attrValues]) => {
            const attrDiv = document.createElement('div');
            const label = document.createElement('label');
            label.className = 'block text-sm font-medium text-slate-300 mb-2';
            label.textContent = attrKey.replace('pa_', '').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            attrDiv.appendChild(label);
            const swatchGroup = document.createElement('div');
            swatchGroup.className = 'flex flex-wrap gap-2';
            swatchGroup.dataset.attribute = attrKey;

            const attrValuesArr = [...attrValues].sort();
            const isSingleValue = attrValuesArr.length === 1;

            attrValuesArr.forEach(optionValue => {
                const swatch = document.createElement('button');
                swatch.className = 'px-3 py-1.5 border border-slate-600 rounded-md bg-slate-700 text-slate-200 hover:bg-slate-600 text-sm transition-colors';
                let isSwatchEffectivelyOutOfStock = true;
                let isSwatchHeld = false;
                let heldCount = 0;
                let totalStock = 0;
                for (const v of appState.products.currentForModal.variations) {
                    if (v.attributes[attrKey] === optionValue) {
                        let availableQty = v.stock_quantity;
                        if (typeof availableQty === 'number') {
                            const heldQty = heldQtyByVariationId[v.id] || 0;
                            totalStock = availableQty;
                            heldCount = heldQty;
                            if (availableQty > 0 && heldQty >= availableQty) {
                                isSwatchHeld = true;
                                isSwatchEffectivelyOutOfStock = true;
                            } else if (v.stock_status === 'instock' && availableQty - heldQty > 0) {
                                isSwatchEffectivelyOutOfStock = false;
                            } else if (availableQty === 0 && heldQty === 0) {
                                isSwatchEffectivelyOutOfStock = true;
                                isSwatchHeld = false;
                            }
                        } else if (v.stock_status === 'instock') {
                            // PATCH: If stock_quantity is null and status is instock, treat as available
                            isSwatchEffectivelyOutOfStock = false;
                        }
                    }
                }

                // If this attribute has only one value, always enable and allow selection
                if (isSingleValue) {
                    swatch.onclick = () => { selectSwatch(swatch); updateAddToCartButton(); updateVariationPriceDisplay(); };
                    swatch.textContent = optionValue.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    // Optionally, visually indicate out of stock
                    if (isSwatchEffectivelyOutOfStock) {
                        swatch.classList.add('opacity-50');
                    }
                } else if (isSwatchHeld) {
                    swatch.classList.add('opacity-50', 'line-through');
                    swatch.style.cursor = 'pointer';
                    swatch.disabled = false;
                    swatch.style.pointerEvents = 'auto';
                    swatch.innerHTML = `<span>${optionValue.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span> <span style='color: orange; font-weight: bold; text-decoration:underline;' class='ml-1'>(Held: ${heldCount})</span>`;
                    let swatchVariationId = null;
                    for (const v2 of appState.products.currentForModal.variations) {
                        if (v2.attributes[attrKey] === optionValue) {
                            swatchVariationId = v2.id;
                            break;
                        }
                    }
                    swatch.onclick = (e) => {
                        e.stopPropagation();
                        const heldCarts = JSON.parse(localStorage.getItem('jpos_held_carts') || '[]');
                        let foundCartId = null;
                        for (const held of heldCarts) {
                            if ((held.cart || []).some(item => item.id && item.id.toString() === String(swatchVariationId))) {
                                foundCartId = held.id;
                                break;
                            }
                        }
                        document.getElementById('variation-modal').classList.add('hidden');
                        routingManager.navigateToView('held-carts-page');
                        setTimeout(() => {
                            if (foundCartId) {
                                const heldDiv = document.querySelector(`[data-id='${foundCartId}']`);
                                if (heldDiv) {
                                    heldDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    heldDiv.classList.add('ring', 'ring-4', 'ring-orange-400');
                                    setTimeout(() => heldDiv.classList.remove('ring', 'ring-4', 'ring-orange-400'), 2000);
                                }
                            }
                        }, 300);
                    };
                } else if (isSwatchEffectivelyOutOfStock) {
                    swatch.classList.add('opacity-50', 'cursor-not-allowed', 'line-through');
                    swatch.disabled = true;
                    swatch.textContent = optionValue.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                } else {
                    swatch.onclick = () => { selectSwatch(swatch); updateAddToCartButton(); updateVariationPriceDisplay(); };
                    swatch.textContent = optionValue.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                }
                swatch.dataset.value = optionValue;
                swatchGroup.appendChild(swatch);
            });
            attrDiv.appendChild(swatchGroup);
            optionsContainer.appendChild(attrDiv);
        });
        
        Object.entries(attributes).forEach(([attrKey, attrValues]) => {
            const availableOptionsForAttr = [...attrValues].filter(optionValue => {
                for (const v of appState.products.currentForModal.variations) {
                    if (v.attributes[attrKey] === optionValue) {
                        // Check if this variation is effectively in stock (considering held stock)
                        if (v.stock_status === 'instock') {
                            if (v.manages_stock && v.stock_quantity !== null) {
                                const heldQty = heldQtyByVariationId[v.id] || 0;
                                if (v.stock_quantity > heldQty) {
                                    return true;
                                }
                            } else {
                                // Doesn't manage stock, so it's available
                                return true;
                            }
                        }
                    }
                }
                return false;
            });

            if (availableOptionsForAttr.length === 1) {
                const singleValue = availableOptionsForAttr[0];
                const swatch = document.querySelector(`#modal-options-container [data-attribute="${attrKey}"] [data-value="${singleValue}"]`);
                if (swatch && !swatch.disabled) {
                    selectSwatch(swatch);
                }
            }
        });
        
        const addToCartBtn = document.getElementById('modal-add-to-cart-btn');
        addToCartBtn.onclick = addVariationToCart;
        updateAddToCartButton();
        updateVariationPriceDisplay();
        document.getElementById('variation-modal').classList.remove('hidden');

        if(preselectedSku) {
            const targetVariation = appState.products.currentForModal.variations.find(v => v.sku === preselectedSku);
            if (targetVariation) {
                Object.entries(targetVariation.attributes).forEach(([attr, val]) => {
                    const swatch = document.querySelector(`#modal-options-container [data-attribute="${attr}"] [data-value="${val}"]`);
                    if(swatch) selectSwatch(swatch);
                });
                updateAddToCartButton();
                updateVariationPriceDisplay();
            }
        }
    }

    function selectSwatch(swatchElement) {
        if (swatchElement.disabled) return;
        const group = swatchElement.parentElement;
        group.querySelectorAll('button').forEach(btn => { 
            btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-500'); 
            if (!btn.disabled) {
                btn.classList.add('bg-slate-700', 'text-slate-200'); 
            }
        });
        swatchElement.classList.add('bg-indigo-600', 'text-white', 'border-indigo-500');
        swatchElement.classList.remove('bg-slate-700', 'text-slate-200');
    }

    function updateVariationPriceDisplay() {
        const priceDisplayEl = document.getElementById('modal-variation-price');
        const selectedOptions = {};
        let allOptionsSelected = true;
        document.querySelectorAll('#modal-options-container [data-attribute]').forEach(group => {
            const selectedBtn = group.querySelector('.bg-indigo-600');
            if (selectedBtn) { selectedOptions[group.dataset.attribute] = selectedBtn.dataset.value; }
            else { allOptionsSelected = false; }
        });

        if (allOptionsSelected) {
            const matchedVariation = appState.products.currentForModal.variations.find(v => Object.keys(selectedOptions).every(key => v.attributes[key] === selectedOptions[key]));
            if (matchedVariation && matchedVariation.stock_status === 'instock') {
                priceDisplayEl.textContent = `$${parseFloat(matchedVariation.price).toFixed(2)}`;
            } else {
                priceDisplayEl.textContent = 'N/A';
            }
        } else {
            priceDisplayEl.textContent = 'Select options';
        }
    }

    function updateAddToCartButton() {
        const selectedOptions = {};
        let allOptionsSelected = true;
        document.querySelectorAll('#modal-options-container [data-attribute]').forEach(group => {
            const selectedBtn = group.querySelector('.bg-indigo-600');
            if (selectedBtn) { selectedOptions[group.dataset.attribute] = selectedBtn.dataset.value; }
            else { allOptionsSelected = false; }
        });
        
        const addToCartBtn = document.getElementById('modal-add-to-cart-btn');
        const modalStockStatusEl = document.getElementById('modal-stock-status');
        
        modalStockStatusEl.textContent = ''; 
        modalStockStatusEl.className = 'text-sm text-slate-400';
        addToCartBtn.disabled = true;

        if (allOptionsSelected) {
            const matchedVariation = appState.products.currentForModal.variations.find(v => Object.keys(selectedOptions).every(key => v.attributes[key] === selectedOptions[key]));
            
            if (matchedVariation) {
                if (matchedVariation.stock_status === 'instock') {
                    addToCartBtn.disabled = false;
                    if (matchedVariation.manages_stock && matchedVariation.stock_quantity !== null) {
                        modalStockStatusEl.innerHTML = `<span class="font-bold text-green-400">${matchedVariation.stock_quantity}</span> in stock`;
                    } else {
                        modalStockStatusEl.textContent = 'In Stock';
                    }
                } else {
                    modalStockStatusEl.textContent = 'Out of Stock';
                    modalStockStatusEl.classList.add('text-red-400');
                }
            } else {
                modalStockStatusEl.textContent = 'Combination not available';
                modalStockStatusEl.classList.add('text-orange-400');
            }
        } else {
            modalStockStatusEl.textContent = 'Select options to see stock';
        }
    }

    function addVariationToCart() {
        if (!appState.drawer.isOpen) { showDrawerModal('open'); return; }
        const selectedOptions = {}; document.querySelectorAll('#modal-options-container [data-attribute]').forEach(group => { const selectedBtn = group.querySelector('.bg-indigo-600'); if (selectedBtn) { selectedOptions[group.dataset.attribute] = selectedBtn.dataset.value; } });
        const matchedVariation = appState.products.currentForModal.variations.find(v => Object.keys(selectedOptions).every(key => v.attributes[key] === selectedOptions[key]));
        if (matchedVariation) {
            const variationForCart = { ...matchedVariation, name: `${appState.products.currentForModal.name} - ${Object.values(matchedVariation.attributes).map(v => v.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())).join(', ')}` };
            addToCart(variationForCart, 1);
            document.getElementById('variation-modal').classList.add('hidden');
        }
    }

    function addToCart(product, quantity = 1) { 
        if (!appState.drawer.isOpen) { showDrawerModal('open'); return; }
        const existingItem = appState.cart.items.find(item => item.id === product.id); 
        if (existingItem) {
            existingItem.qty += quantity;
            if (existingItem.qty === 0) {
                appState.cart.items = appState.cart.items.filter(item => item.id !== product.id);
                showToast(`${product.name} removed from cart`);
            } else if (quantity > 0) {
                showToast(`${product.name} added to cart`);
            } else if (quantity < 0) {
                showToast(`${product.name} removed from cart`);
            }
        } else if (quantity !== 0) { 
            appState.cart.items.push({ ...product, qty: quantity }); 
            showToast(`${product.name} added to cart`);
        }
        renderCart(); 
    }

    function updateCartQuantity(id, change) {
        const item = appState.cart.items.find(item => item.id === id);
        if (item) {
            if (item.qty < 0) {
                const maxQty = appState.return_from_order_items.find(p => p.id === id)?.quantity || 0;
                if (item.qty + change > 0 || Math.abs(item.qty + change) > maxQty) return;
            }
            item.qty += change;
            if (item.qty === 0) {
                appState.cart.items = appState.cart.items.filter(i => i.id !== id);
                showToast(`${item.name} removed from cart`);
            } else if (change > 0) {
                showToast(`${item.name} added to cart`);
            } else if (change < 0) {
                showToast(`${item.name} removed from cart`);
            }
            renderCart();
        }
    }
    
    function renderCart() {
        const cartContainer = document.getElementById('cart-items');
        const totalEl = document.getElementById('cart-total');
        const totalBottomEl = document.getElementById('cart-total-bottom');
        const summaryEl = document.getElementById('cart-summary');
        const discountRow = document.getElementById('cart-discount-row');
        const feeRow = document.getElementById('cart-fee-row');
        const checkoutBtn = document.getElementById('checkout-btn');
        cartContainer.innerHTML = '';
        if (discountRow) discountRow.innerHTML = '';
        if (feeRow) feeRow.innerHTML = '';
        let total = 0;
        let itemCount = 0;
        let qtyCount = 0;
        
        appState.cart.items.forEach(item => {
            itemCount++;
            qtyCount += item.qty;
            const isReturn = item.qty < 0;
            const li = document.createElement('div');
            li.className = `flex items-center gap-2 p-1 rounded bg-slate-700/50 text-xs`;
            const imageHTML = item.image_url ? `<img src="${item.image_url}" alt="${item.name}" class="w-10 h-10 object-cover rounded-md flex-shrink-0">` : `<div class="w-10 h-10 placeholder-bg rounded-md flex-shrink-0"></div>`;
            
            const itemInfo = document.createElement('div');
            itemInfo.className = 'flex-grow truncate';
            itemInfo.innerHTML = `<span class="font-semibold text-slate-100 truncate block" title="${item.name}">${item.name}</span><span class="text-slate-400 font-mono block">$${parseFloat(item.price).toFixed(2)}</span>`;
            
            const qtyControls = document.createElement('div');
            qtyControls.className = 'flex items-center gap-1 flex-shrink-0';

            const minusBtn = document.createElement('button');
            minusBtn.className = 'w-5 h-5 rounded bg-slate-600 hover:bg-slate-500 transition-colors text-xs';
            minusBtn.textContent = '-';
            minusBtn.addEventListener('click', () => updateCartQuantity(item.id, -1));
            
            const qtySpan = document.createElement('span');
            qtySpan.className = 'w-5 text-center font-bold';
            qtySpan.textContent = item.qty;
    
            const plusBtn = document.createElement('button');
            plusBtn.className = 'w-5 h-5 rounded bg-slate-600 hover:bg-slate-500 transition-colors text-xs';
            plusBtn.textContent = '+';
            plusBtn.addEventListener('click', () => updateCartQuantity(item.id, 1));
    
            qtyControls.appendChild(minusBtn);
            qtyControls.appendChild(qtySpan);
            qtyControls.appendChild(plusBtn);
    
            li.innerHTML = imageHTML;
            li.appendChild(itemInfo);
            li.appendChild(qtyControls);
            
            cartContainer.appendChild(li);
            total += item.price * item.qty;
        });

        // Consistent structure for Discount and Fee rows
        const rowStyle = 'flex items-center justify-between text-xs text-slate-200 px-1 py-0.5';
        const labelStyle = 'flex items-center gap-1';
        // Discount
        if (appState.discount.amount && discountRow) {
            let calculatedValue = 0;
            if (appState.discount.amountType === 'percentage') {
                calculatedValue = total * (parseFloat(appState.discount.amount) / 100);
            } else {
                calculatedValue = parseFloat(appState.discount.amount);
            }
                calculatedValue = -Math.abs(calculatedValue);
            const displayAmount = `-$${Math.abs(calculatedValue).toFixed(2)}`;
            discountRow.innerHTML = `<div class='${rowStyle}'><span class='${labelStyle}'><button class='w-5 h-5 rounded bg-slate-600 hover:bg-red-500 text-white flex items-center justify-center mr-1' title='Remove Discount' id='remove-discount-btn'><i class='fa fa-times'></i></button>Discount</span><span>${displayAmount}</span></div>`;
            setTimeout(() => {
                const btn = document.getElementById('remove-discount-btn');
                if (btn) btn.onclick = () => { appState.discount = { amount: '', label: '', amountType: 'flat' }; showToast('Discount removed'); renderCart(); };
            }, 0);
            total += calculatedValue;
        }
        // Fee
        if (appState.fee.amount && feeRow) {
            let calculatedValue = 0;
            if (appState.fee.amountType === 'percentage') {
                calculatedValue = total * (parseFloat(appState.fee.amount) / 100);
            } else {
                calculatedValue = parseFloat(appState.fee.amount);
            }
            const displayAmount = `+$${calculatedValue.toFixed(2)}`;
            feeRow.innerHTML = `<div class='${rowStyle}'><span class='${labelStyle}'><button class='w-5 h-5 rounded bg-slate-600 hover:bg-red-500 text-white flex items-center justify-center mr-1' title='Remove Fee' id='remove-fee-btn'><i class='fa fa-times'></i></button>Fee</span><span>${displayAmount}</span></div>`;
            setTimeout(() => {
                const btn = document.getElementById('remove-fee-btn');
                if (btn) btn.onclick = () => { appState.fee = { amount: '', label: '', amountType: 'flat' }; showToast('Fee removed'); renderCart(); };
            }, 0);
            total += calculatedValue;
        }
        
        if (appState.cart.items.length === 0 && !appState.fee.amount && !appState.discount.amount) {
            cartContainer.innerHTML = '<p class="text-center text-slate-400 text-xs py-6">Your cart is empty.</p>';
        }

        totalEl.textContent = `$${total.toFixed(2)}`;
        if (totalBottomEl) totalBottomEl.textContent = `$${total.toFixed(2)}`;
        if (summaryEl) summaryEl.textContent = `(Items: ${itemCount}, quantity: ${qtyCount})`;

        if (total < 0) {
            checkoutBtn.textContent = 'Process Refund';
            checkoutBtn.classList.remove('bg-indigo-600', 'hover:bg-indigo-500');
            checkoutBtn.classList.add('bg-red-600', 'hover:bg-red-500');
        } else {
            checkoutBtn.textContent = 'Checkout';
            checkoutBtn.classList.remove('bg-red-600', 'hover:bg-red-500');
            checkoutBtn.classList.add('bg-indigo-600', 'hover:bg-indigo-500');
        }
        // At the end of renderCart, persist cart state
        saveCartState();
    }
    
    function removeFeeDiscount() {
        appState.feeDiscount = { type: null, amount: '', label: '', amountType: 'flat' };
        renderCart();
    }

    function clearCart(fullReset = false) { 
        appState.cart.items = []; 
        appState.fee = { amount: '', label: '', amountType: 'flat' };
        appState.discount = { amount: '', label: '', amountType: 'flat' };
        appState.feeDiscount = { type: null, amount: '', label: '', amountType: 'flat' };
        if (fullReset) {
            appState.return_from_order_id = null;
            appState.return_from_order_items = [];
        }
        renderCart(); 
        saveCartState();
    }

    async function processTransaction() {
        if (appState.cart.items.length === 0 || !appState.drawer.isOpen) return;

        // Show split payment modal instead of direct checkout
        openSplitPaymentModal();
    }

    async function fetchOrders() {
        const c = document.getElementById('order-list'); 
        c.innerHTML = getSkeletonLoaderHtml('list-rows', 20);
        const params = `date_filter=${appState.orders.filters.date}&status_filter=${appState.orders.filters.status}&source_filter=${appState.orders.filters.source}`;
        try {
            const response = await fetch(`/jpos/api/orders.php?${params}`); 
            if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message);
            appState.orders.all = result.data || [];
            renderOrders();
        } catch (error) { 
            console.error("Error in fetchOrders:", error);
            c.innerHTML = `<p class="p-10 text-center text-red-400">Error: Could not fetch order data. ${error.message || 'Unknown error'}</p>`; 
        }
    }
    
    function renderOrders() {
        const c = document.getElementById('order-list'); c.innerHTML = '';
        let filteredOrders = appState.orders.all;
        if (appState.orders.filters.orderId) {
            const search = appState.orders.filters.orderId.replace(/^#/, '').toLowerCase();
            filteredOrders = appState.orders.all.filter(o =>
                o.order_number.toString().toLowerCase().includes(search)
            );
        }
        if (filteredOrders.length === 0) {
            c.innerHTML = `<div class="p-10 text-center text-slate-400 col-span-12">No orders match criteria.</div>`;
            return;
        }
        filteredOrders.forEach(o => {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 gap-4 items-center bg-slate-800 hover:bg-slate-700/50 p-3 rounded-lg text-sm';
            const s = { completed: 'text-green-400', processing: 'text-blue-400', 'on-hold': 'text-yellow-400', cancelled: 'text-red-400', refunded: 'text-gray-400', failed: 'text-red-500' };
            
            const sourceColor = o.source === 'POS' ? 'text-green-400' : 'text-blue-400';
            row.innerHTML = `
                <div class="col-span-2 font-bold">#${o.order_number}</div>
                <div class="col-span-2 text-slate-400">${formatDateTime(o.date_created)}</div>
                <div class="col-span-1 font-semibold ${sourceColor} text-xs">${o.source}</div>
                <div class="col-span-2 font-semibold ${s[o.status]||'text-slate-300'}">${o.status.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</div>
                <div class="col-span-1 text-center">${o.item_count}</div>
                <div class="col-span-2 text-right font-mono">$${o.total}</div>
                <div class="col-span-2 text-right flex gap-2 justify-end">
                    <button class="view-receipt-btn px-3 py-1 bg-indigo-600 text-xs rounded hover:bg-indigo-500" data-order-id="${o.id}">Receipt</button>
                    ${o.status === 'completed' ? `<button class="return-order-btn px-3 py-1 bg-amber-600 text-xs rounded hover:bg-amber-500" data-order-id="${o.id}">Return</button>` : ''}
                </div>`;
            c.appendChild(row);
        });
        
        c.querySelectorAll('.view-receipt-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const orderId = parseInt(e.target.dataset.orderId);
                const order = appState.orders.all.find(o => o.id === orderId);
                if (order) showReceipt({ ...order, items: order.items, payment_method: order.payment_method, split_payments: order.split_payments });
            });
        });
        c.querySelectorAll('.return-order-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const orderId = parseInt(e.target.dataset.orderId);
                openReturnModal(orderId);
            });
        });
    }

    function openReturnModal(orderId) {
        const order = appState.orders.all.find(o => o.id === orderId);
        if (!order) { alert('Could not find order details.'); return; }
        if (appState.cart.items.length > 0) {
            if (!confirm('You have items in your cart. Starting a return will clear the current cart. Continue?')) return;
            clearCart(true);
        }

        appState.return_from_order_id = order.id;
        appState.return_from_order_items = order.items.map(item => ({...item}));
        renderReturnModalItems(order.items);
        document.getElementById('return-modal').classList.remove('hidden');
    }

    function renderReturnModalItems(items) {
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
                updateReturnModalButtonState();
            });
        });
        updateReturnModalButtonState();
    }

    function updateReturnModalButtonState() {
        const inputs = Array.from(document.querySelectorAll('#return-items-list input'));
        const hasValue = inputs.some(input => parseInt(input.value) > 0);
        document.getElementById('return-modal-add-to-cart-btn').disabled = !hasValue;
    }

    function handleAddReturnItemsToCart() {
        document.querySelectorAll('#return-items-list > div').forEach(row => {
            const quantity = parseInt(row.querySelector('input').value);
            if (quantity > 0) {
                const originalItem = appState.return_from_order_items.find(
                    item => item.id == row.dataset.productId
                );

                if (originalItem) {
                    // Find full product info to get image_url
                    const fullProductInfo = appState.products.all.find(p => p.id === originalItem.id) || (appState.products.all.find(p => p.variations && p.variations.find(v => v.id === originalItem.id))?.variations.find(v => v.id === originalItem.id));
                    
                    const itemDataForCart = {
                        ...originalItem,
                        price: parseFloat(row.dataset.price),
                        image_url: fullProductInfo ? fullProductInfo.image_url : '',
                        qty: -quantity
                    };
                    addToCart(itemDataForCart, itemDataForCart.qty);
                }
            }
        });

        document.getElementById('return-modal').classList.add('hidden');
        routingManager.navigateToView('pos-page');
    }
    
    async function fetchReportsData() {
        const contentArea = document.getElementById('reports-content-area');
        contentArea.innerHTML = getSkeletonLoaderHtml('reports-page');

        try {
            const response = await fetch('/jpos/api/reports.php');
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message || 'API error');
            renderReports(result.data);
        } catch (error) {
            console.error("Error fetching report data:", error);
            contentArea.innerHTML = `<p class="p-10 text-center text-red-400">Error: Could not fetch report data. ${error.message}</p>`;
        }
    }

    function renderReports(data) {
        const contentArea = document.getElementById('reports-content-area');
        // Helper for currency formatting
        function formatCurrency(val) {
            return Number(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        contentArea.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
                    <h3 class="text-sm font-medium text-slate-400">Cash</h3>
                    <p class="text-3xl font-bold mt-1">$${formatCurrency(data.summary.cash_revenue)}</p>
                    <div class="mt-2 text-xs text-slate-500">
                        <div class="flex justify-between">
                            <span>Orders: ${data.summary.cash_orders}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
                    <h3 class="text-sm font-medium text-slate-400">Card</h3>
                    <p class="text-3xl font-bold mt-1">$${formatCurrency(data.summary.card_revenue)}</p>
                    <div class="mt-2 text-xs text-slate-500">
                        <div class="flex justify-between">
                            <span>Orders: ${data.summary.card_orders}</span>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
                    <h3 class="text-sm font-medium text-slate-400">Other</h3>
                    <p class="text-3xl font-bold mt-1">$${formatCurrency(data.summary.other_revenue ? data.summary.other_revenue : 0)}</p>
                    <div class="mt-2 text-xs text-slate-500">
                        <div class="flex justify-between">
                            <span>Orders: ${(data.summary.other_orders ? data.summary.other_orders : 0)}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
                    <h3 class="text-sm font-medium text-slate-400 mb-2">Online</h3>
                    <p class="text-2xl font-bold text-green-400">$${formatCurrency(data.summary.online_revenue ? data.summary.online_revenue : 0)}</p>
                    <p class="text-xs text-slate-500 mt-1">${(data.summary.online_orders ? data.summary.online_orders : 0)} orders</p>
                </div>
                <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
                    <h3 class="text-sm font-medium text-slate-400 mb-2">POS</h3>
                    <p class="text-2xl font-bold text-blue-400">$${formatCurrency(data.summary.pos_revenue ? data.summary.pos_revenue : 0)}</p>
                    <p class="text-xs text-slate-500 mt-1">${(data.summary.pos_orders ? data.summary.pos_orders : 0)} orders</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
                    <h3 class="text-lg font-semibold mb-4">Revenue per Day (Last 30 Days)</h3>
                    <canvas id="revenue-chart"></canvas>
                </div>
                <div class="bg-slate-800 p-6 rounded-xl border border-slate-700">
                    <h3 class="text-lg font-semibold mb-4">Orders per Day (Last 30 Days)</h3>
                    <canvas id="orders-chart"></canvas>
                </div>
            </div>
        `;
        
        if (appState.charts.revenue) appState.charts.revenue.destroy();
        if (appState.charts.orders) appState.charts.orders.destroy();

        const labels = data.daily_data.map(d => new Date(d.order_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        const revenueData = data.daily_data.map(d => d.daily_revenue);
        const ordersData = data.daily_data.map(d => d.daily_orders);
        
        // Payment method breakdown data
        const cashRevenueData = data.daily_data.map(d => d.daily_cash_revenue);
        const cardRevenueData = data.daily_data.map(d => d.daily_card_revenue);
        const cashOrdersData = data.daily_data.map(d => d.daily_cash_orders);
        const cardOrdersData = data.daily_data.map(d => d.daily_card_orders);

        const chartOptions = { 
            plugins: { 
                legend: { 
                    display: true,
                    labels: {
                        color: '#94a3b8',
                        usePointStyle: true
                    }
                } 
            }, 
            scales: { 
                y: { 
                    beginAtZero: true, 
                    grid: { color: '#334155' }, 
                    ticks: { color: '#94a3b8' } 
                }, 
                x: { 
                    grid: { color: '#1e293b' }, 
                    ticks: { color: '#94a3b8' } 
                } 
            } 
        };
        
        const revenueCtx = document.getElementById('revenue-chart').getContext('2d');
        appState.charts.revenue = new Chart(revenueCtx, { 
            type: 'bar', 
            data: { 
                labels: labels, 
                datasets: [
                    {
                        label: 'Cash Revenue',
                        data: cashRevenueData,
                        backgroundColor: 'rgba(34, 197, 94, 0.6)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Card/Linx Revenue',
                        data: cardRevenueData,
                        backgroundColor: 'rgba(59, 130, 246, 0.6)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }
                ]
            }, 
            options: chartOptions 
        });
        
        const ordersCtx = document.getElementById('orders-chart').getContext('2d');
        appState.charts.orders = new Chart(ordersCtx, { 
            type: 'line', 
            data: { 
                labels: labels, 
                datasets: [
                    {
                        label: 'Cash Orders',
                        data: cashOrdersData,
                        fill: false,
                        backgroundColor: 'rgba(34, 197, 94, 0.2)',
                        borderColor: 'rgba(34, 197, 94, 1)',
                        tension: 0.3
                    },
                    {
                        label: 'Card/Linx Orders',
                        data: cardOrdersData,
                        fill: false,
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        tension: 0.3
                    }
                ]
            }, 
            options: chartOptions 
        });
    }
    
    async function fetchSessions() {
        const container = document.getElementById('session-list'); 
        container.innerHTML = getSkeletonLoaderHtml('list-rows', 20);
        try {
            const response = await fetch('/jpos/api/sessions.php');
            if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message);
            appState.sessions = result.data || [];
            renderSessions();
        } catch (error) { 
            console.error("Error in fetchSessions:", error); 
            container.innerHTML = `<p class="p-10 text-center text-red-400">Error: Could not fetch session data. ${error.message}</p>`; 
        }
    }

    function renderSessions() {
        const container = document.getElementById('session-list'); container.innerHTML = '';
        if (appState.sessions.length === 0) { container.innerHTML = '<p class="p-10 text-center text-slate-400">No past sessions found.</p>'; return; }
        appState.sessions.forEach(s => {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 gap-4 items-center bg-slate-800 hover:bg-slate-700/50 p-3 rounded-lg text-sm font-mono';
            const difference = parseFloat(s.difference || 0); let diffColor = 'text-green-400';
            if (difference < 0) diffColor = 'text-red-400'; else if (difference > 0) diffColor = 'text-yellow-400';
            row.innerHTML = `<div class="col-span-2 text-slate-200 font-sans">${s.user_name}</div><div class="col-span-3 text-slate-400">${formatDateTime(s.time_opened)}</div><div class="col-span-3 text-slate-400">${formatDateTime(s.time_closed)}</div><div class="col-span-1 text-right text-slate-300">$${s.opening_amount.toFixed(2)}</div><div class="col-span-1 text-right text-slate-300">$${s.closing_amount.toFixed(2)}</div><div class="col-span-2 text-right font-bold ${diffColor}">$${difference.toFixed(2)}</div>`;
            container.appendChild(row);
        });
    }

    function renderStockList() { 
        const container = document.getElementById('stock-list');
        container.innerHTML = '';
        const filterText = document.getElementById('products-list-filter').value.toLowerCase();
        
        // Check if appState is properly initialized
        if (!appState || !appState.products || !appState.stockFilters) {
            container.innerHTML = '<div class="p-10 text-center text-red-400 col-span-12">Error: State not initialized</div>';
            return;
        }
        
        const filteredList = appState.products.all.filter(p => { 
            const textMatch = p.name.toLowerCase().includes(filterText) || (p.sku && p.sku.toLowerCase().includes(filterText));
            const categoryMatch = appState.stockFilters.category === 'all' || (p.category_ids || []).includes(parseInt(appState.stockFilters.category));
            const tagMatch = appState.stockFilters.tag === 'all' || (p.tag_ids || []).includes(parseInt(appState.stockFilters.tag));
            let stockMatch;
            if (appState.stockFilters.stock === 'private') {
                stockMatch = p.post_status === 'private';
            } else {
                stockMatch = appState.stockFilters.stock === 'all' || p.stock_status === appState.stockFilters.stock;
            }
            return textMatch && categoryMatch && tagMatch && stockMatch;
        });

        if (filteredList.length === 0) { 
            container.innerHTML = '<div class="p-10 text-center text-slate-400 col-span-12">No products match your filter.</div>'; 
            return; 
        }
        
        filteredList.forEach(p => {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 gap-4 items-center bg-slate-800 hover:bg-slate-700/50 p-3 rounded-lg text-sm cursor-pointer';
            row.onclick = () => openProductEditor(p.id);
            
            let stockDisplayHtml = '';
            if (p.manages_stock && p.stock_quantity !== null) {
                stockDisplayHtml = `<span class="font-bold ${p.stock_quantity > 5 ? 'text-green-300' : 'text-orange-300'}">${p.stock_quantity}</span>`;
            } else {
                stockDisplayHtml = `<span class="${p.stock_status === 'instock' ? 'text-slate-300' : 'text-orange-300'}">${p.stock_status === 'instock' ? 'In Stock' : 'Out of Stock'}</span>`;
            }

            let priceDisplay = p.type === 'variable' && p.min_price !== null ? `$${parseFloat(p.min_price).toFixed(2)}` : p.price ? `$${parseFloat(p.price || 0).toFixed(2)}` : 'N/A';

            const imageHtml = p.image_url ? 
                `<img src="${p.image_url}" class="w-10 h-10 object-cover rounded-lg" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">` :
                '';
            const placeholderHtml = `<div class="w-10 h-10 bg-slate-700 rounded-lg flex items-center justify-center text-slate-400 text-xs font-bold px-1 text-center" style="display: ${p.image_url ? 'none' : 'flex'}; line-height: 1.1;">${p.sku || 'N/A'}</div>`;
            
            row.innerHTML = `
                <div class="col-span-1 flex justify-start">
                    ${imageHtml}${placeholderHtml}
                </div>
                <div class="col-span-3 font-semibold text-slate-200 truncate">${p.name}</div>
                <div class="col-span-2 text-slate-400 font-mono text-xs truncate">${p.sku || 'N/A'}</div>
                <div class="col-span-1 text-slate-400 text-xs capitalize">${p.type}</div>
                <div class="col-span-2 text-right font-mono text-green-400 font-bold">${priceDisplay}</div>
                <div class="col-span-2 text-right font-bold">${stockDisplayHtml}</div>
                <div class="col-span-1 text-center">
                    <button class="px-2 py-1 bg-indigo-600 text-xs rounded hover:bg-indigo-500 transition-colors" onclick="event.stopPropagation(); openProductEditor(${p.id})">
                        Edit
                    </button>
                </div>
            `;
            container.appendChild(row);
        });
    }

    async function openStockEditModal(productId) {
        appState.editingStockProduct = null;
        const modal = document.getElementById('stock-edit-modal');
        modal.classList.remove('hidden');
        const titleEl = document.getElementById('stock-edit-title');
        const varList = document.getElementById('stock-edit-variations-list');
        
        varList.innerHTML = getSkeletonLoaderHtml('variation-edit-rows', 4);
        titleEl.textContent = 'Edit Stock';

        try {
            const response = await fetch(`api/stock.php?action=get_details&id=${productId}`);
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message);
            
            appState.editingStockProduct = result.data;
            titleEl.textContent = `Edit: ${result.data.name}`;
            
            if (result.data.type === 'variable') {
                varList.innerHTML = '';
                result.data.variations.forEach(v => {
                    const attributes = Object.values(v.attributes).map(s => s.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())).join(', ');
                    const row = document.createElement('div');
                    row.className = 'grid grid-cols-12 gap-3 items-center bg-slate-900 p-3 rounded-lg';
                    row.dataset.vid = v.id;
                    row.innerHTML = `<div class="col-span-4 font-semibold text-sm">${attributes}</div><div class="col-span-2"><input type="text" value="${v.sku || ''}" class="form-input text-xs p-2" data-field="sku"></div><div class="col-span-2"><input type="number" value="${v.price}" step="0.01" class="form-input text-xs p-2 text-green-400" data-field="price"></div><div class="col-span-4">${ v.manages_stock ? `<div class="flex items-center border border-slate-600 rounded-md bg-slate-700"><button type="button" class="px-2 py-1 bg-slate-600 hover:bg-slate-500 rounded-l-md transition-colors stock-qty-minus">-</button><input type="number" value="${v.stock_quantity !== null ? v.stock_quantity : ''}" step="1" class="w-full text-center bg-transparent border-none focus:outline-none text-xs p-1" data-field="stock_quantity"><button type="button" class="px-2 py-1 bg-slate-600 hover:bg-slate-500 rounded-r-md transition-colors stock-qty-plus">+</button></div>` : '<span class="text-xs text-slate-500 p-2 block text-center">N/A</span>'}</div>`;
                    varList.appendChild(row);
                });
                varList.querySelectorAll('.stock-qty-minus').forEach(btn => btn.addEventListener('click', (e) => { const input = e.target.closest('div').querySelector('[data-field="stock_quantity"]'); if (input) input.value = Math.max(0, parseInt(input.value || 0) - 1); }));
                varList.querySelectorAll('.stock-qty-plus').forEach(btn => btn.addEventListener('click', (e) => { const input = e.target.closest('div').querySelector('[data-field="stock_quantity"]'); if (input) input.value = parseInt(input.value || 0) + 1; }));

            } else {
                varList.innerHTML = '<p class="text-slate-400 p-8 text-center">This is a simple product. Stock can be managed directly in WooCommerce.</p>';
            }
        } catch (error) {
            console.error("Error loading stock edit details:", error);
            varList.innerHTML = `<div class="p-8 text-center"><p class="text-red-400">Error loading details: ${error.message}</p></div>`;
        }
    }

    // Make openStockEditModal globally accessible immediately after definition
    window.openStockEditModal = openStockEditModal;

    async function handleStockEditSave() {
        const product = appState.editingStockProduct;
        if (!product || product.type !== 'variable') return;
        const statusEl = document.getElementById('stock-edit-status');
        statusEl.textContent = 'Saving...';
        statusEl.className = 'text-sm text-right h-5 text-slate-400';
        const variationsData = [];
        document.querySelectorAll('#stock-edit-variations-list > div[data-vid]').forEach(row => {
            const stockInput = row.querySelector('[data-field="stock_quantity"]');
            variationsData.push({ id: parseInt(row.dataset.vid, 10), sku: row.querySelector('[data-field="sku"]').value, price: parseFloat(row.querySelector('[data-field="price"]').value), stock_quantity: stockInput ? parseInt(stockInput.value, 10) : null });
        });
        const payload = { action: 'update_variations', parent_id: product.id, variations: variationsData, nonce: appState.nonces.stock };
        try {
            const response = await fetch('api/stock.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message || 'Failed to update');
            statusEl.textContent = result.data.message;
            statusEl.className = 'text-sm text-right h-5 text-green-400';
            await refreshAllData();
            setTimeout(() => { document.getElementById('stock-edit-modal').classList.add('hidden'); renderStockList(); }, 1500);
        } catch(error) {
            console.error("Error saving stock changes:", error);
            statusEl.textContent = `Error: ${error.message}`;
            statusEl.className = 'text-sm text-right h-5 text-red-400';
        }
    }

    // Comprehensive Product Editor Functions
    let currentEditingProduct = null;
    let productEditorNonce = '';

    async function openProductEditor(productId) {
        currentEditingProduct = null;
        const modal = document.getElementById('product-editor-modal');
        modal.classList.remove('hidden');
        
        const titleEl = document.getElementById('product-editor-title');
        titleEl.textContent = 'Loading Product...';
        
        // Clear form
        clearProductEditorForm();
        
        // Start with form view by default
        switchToFormView();
        
        try {
            // Get product details
            const response = await fetch(`api/product-edit-simple.php?action=get_product_details&id=${productId}`);
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message);
            
            currentEditingProduct = result.data;
            titleEl.textContent = `Edit: ${result.data.name}`;
            
            // Populate form
            populateProductEditorForm(result.data);
            
            // Load tax classes
            await loadTaxClasses();
            
            // JSON preview now only updates in JSON tab
            
        } catch (error) {
            console.error("Error loading product details:", error);
            titleEl.textContent = 'Error Loading Product';
            document.getElementById('product-editor-status').textContent = `Error: ${error.message}`;
            document.getElementById('product-editor-status').className = 'text-sm text-right h-5 mt-2 text-red-400';
        }
    }

    // Tab Switching Functions
    function switchToFormView() {
        document.getElementById('form-view').classList.remove('hidden');
        document.getElementById('json-view').classList.add('hidden');
        
        // Update tab styling
        document.getElementById('form-tab').className = 'px-4 py-2 text-sm font-medium text-slate-300 border-b-2 border-blue-500 bg-slate-700 rounded-t-lg';
        document.getElementById('json-tab').className = 'px-4 py-2 text-sm font-medium text-slate-400 border-b-2 border-transparent hover:text-slate-300 hover:border-slate-500';
    }
    
    function switchToJSONView() {
        document.getElementById('form-view').classList.add('hidden');
        document.getElementById('json-view').classList.remove('hidden');
        
        // Update tab styling
        document.getElementById('form-tab').className = 'px-4 py-2 text-sm font-medium text-slate-400 border-b-2 border-transparent hover:text-slate-300 hover:border-slate-500';
        document.getElementById('json-tab').className = 'px-4 py-2 text-sm font-medium text-slate-300 border-b-2 border-blue-500 bg-slate-700 rounded-t-lg';
        
        // Update the full JSON preview
        updateFullJSONPreview();
    }
    
    function updateFullJSONPreview() {
        if (!currentEditingProduct) return;
        
        const jsonString = JSON.stringify(currentEditingProduct, null, 2);
        const highlightedJSON = highlightJSON(jsonString);
        document.getElementById('json-full-preview').innerHTML = highlightedJSON;
    }

    function clearProductEditorForm() {
        document.getElementById('product-name').value = '';
        document.getElementById('product-sku').value = '';
        document.getElementById('product-barcode').value = '';
        document.getElementById('product-regular-price').value = '';
        document.getElementById('product-sale-price').value = '';
        document.getElementById('product-status').value = 'publish';
        document.getElementById('product-featured').checked = false;
        document.getElementById('product-tax-class').value = '';
        document.querySelector('input[name="tax-status"][value="taxable"]').checked = true;
        document.getElementById('product-stock-quantity').value = '';
        document.getElementById('product-manage-stock').checked = false;
        
        // Clear meta data
        document.getElementById('product-meta-data').innerHTML = '';
        
        // Clear attributes
        document.getElementById('product-attributes').innerHTML = '';
        
        // Clear variations and hide variations section
        document.getElementById('product-variations').innerHTML = '';
        document.getElementById('variations-section').classList.add('hidden');
    }

    function populateProductEditorForm(product) {
        document.getElementById('product-name').value = product.name || '';
        document.getElementById('product-sku').value = product.sku || '';
        document.getElementById('product-barcode').value = product.barcode || '';
        document.getElementById('product-regular-price').value = product.regular_price || '';
        document.getElementById('product-sale-price').value = product.sale_price || '';
        document.getElementById('product-status').value = product.status || 'publish';
        document.getElementById('product-featured').checked = product.featured || false;
        document.getElementById('product-tax-class').value = product.tax_class || '';
        document.querySelector(`input[name="tax-status"][value="${product.tax_status || 'taxable'}"]`).checked = true;
        document.getElementById('product-stock-quantity').value = product.stock_quantity || '';
        document.getElementById('product-manage-stock').checked = product.manage_stock || false;
        
        // Populate meta data
        populateMetaData(product.meta_data || []);
        
        // Populate attributes (always show attributes section)
        populateAttributes(product.attributes || []);
        
        // Show variable product sections if applicable
        if (product.type === 'variable') {
            document.getElementById('variations-section').classList.remove('hidden');
            populateVariations(product.variations || []);
        } else {
            document.getElementById('variations-section').classList.add('hidden');
        }
    }

    function populateMetaData(metaData) {
        const container = document.getElementById('product-meta-data');
        container.innerHTML = '';
        
        metaData.forEach((meta, index) => {
            const metaRow = document.createElement('div');
            metaRow.className = 'flex gap-2 items-center';
            metaRow.innerHTML = `
                <input type="text" placeholder="Meta Key" value="${meta.key}" class="flex-1 px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                <input type="text" placeholder="Meta Value" value="${meta.value}" class="flex-1 px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                <button type="button" onclick="removeMetaDataRow(this)" class="px-2 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-500">Remove</button>
            `;
            container.appendChild(metaRow);
        });
    }

    function populateVariations(variations) {
        const container = document.getElementById('variations-list');
        container.innerHTML = '';
        
        variations.forEach(variation => {
            const variationDiv = document.createElement('div');
            variationDiv.className = 'bg-slate-600 p-3 rounded border border-slate-500';
            variationDiv.setAttribute('data-variation-id', variation.id);
            variationDiv.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <h4 class="font-semibold text-slate-200">Variation #${variation.id}</h4>
                    <span class="text-sm text-slate-400">Status: ${variation.status || 'publish'}</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <label class="block text-slate-300 mb-1">SKU</label>
                        <input type="text" value="${variation.sku || ''}" class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500" data-field="sku">
                    </div>
                    <div>
                        <label class="block text-slate-300 mb-1">Price</label>
                        <input type="number" step="0.01" value="${variation.price || ''}" class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500" data-field="price">
                    </div>
                    <div>
                        <label class="block text-slate-300 mb-1">Sale Price</label>
                        <input type="number" step="0.01" value="${variation.sale_price || ''}" class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500" data-field="sale_price">
                    </div>
                    <div>
                        <label class="block text-slate-300 mb-1">Stock Quantity</label>
                        <input type="number" value="${variation.stock_quantity || ''}" class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500" data-field="stock_quantity">
                    </div>
                </div>
                <div class="mt-2">
                    <label class="block text-slate-300 mb-1">Stock Status</label>
                    <select class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500" data-field="stock_status">
                        <option value="instock" ${variation.stock_status === 'instock' ? 'selected' : ''}>In Stock</option>
                        <option value="outofstock" ${variation.stock_status === 'outofstock' ? 'selected' : ''}>Out of Stock</option>
                        <option value="onbackorder" ${variation.stock_status === 'onbackorder' ? 'selected' : ''}>On Backorder</option>
                    </select>
                </div>
            `;
            container.appendChild(variationDiv);
        });
    }

    function populateAttributes(attributes) {
        const container = document.getElementById('attributes-list');
        container.innerHTML = '';
        
        attributes.forEach(attribute => {
            const attrDiv = document.createElement('div');
            attrDiv.className = 'bg-slate-600 p-3 rounded border border-slate-500';
            attrDiv.innerHTML = `
                <h4 class="font-semibold text-slate-200 mb-2">${attribute.name}</h4>
                <div class="text-sm text-slate-300">
                    <p>Type: ${attribute.type}</p>
                    <p>Options: ${attribute.options.join(', ')}</p>
                    <p>Visible: ${attribute.visible ? 'Yes' : 'No'}</p>
                    <p>Variation: ${attribute.variation ? 'Yes' : 'No'}</p>
                </div>
            `;
            container.appendChild(attrDiv);
        });
    }

    async function loadTaxClasses() {
        try {
            const response = await fetch('api/product-edit-simple.php?action=get_tax_classes');
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message);
            
            const select = document.getElementById('product-tax-class');
            select.innerHTML = '';
            
            result.data.forEach(taxClass => {
                const option = document.createElement('option');
                option.value = taxClass.slug;
                option.textContent = taxClass.name;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading tax classes:', error);
        }
    }

    function addMetaDataRow() {
        const container = document.getElementById('product-meta-data');
        const metaRow = document.createElement('div');
        metaRow.className = 'flex gap-2 items-center';
        metaRow.innerHTML = `
            <input type="text" placeholder="Meta Key" class="flex-1 px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
            <input type="text" placeholder="Meta Value" class="flex-1 px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
            <button type="button" onclick="removeMetaDataRow(this)" class="px-2 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-500">Remove</button>
        `;
        container.appendChild(metaRow);
    }

    function toggleMetaDataAccordion() {
        const content = document.getElementById('meta-data-accordion-content');
        const icon = document.getElementById('meta-data-accordion-icon');
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }

    function toggleAttributesAccordion() {
        const content = document.getElementById('attributes-accordion-content');
        const icon = document.getElementById('attributes-accordion-icon');
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }

    function toggleVariationsAccordion() {
        const content = document.getElementById('variations-accordion-content');
        const icon = document.getElementById('variations-accordion-icon');
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }

    function removeMetaDataRow(button) {
        button.parentElement.remove();
        // JSON preview now only updates in JSON tab
    }

    function populateAttributes(attributes) {
        const container = document.getElementById('product-attributes');
        container.innerHTML = '';
        
        attributes.forEach((attribute, index) => {
            const attributeRow = document.createElement('div');
            attributeRow.className = 'bg-slate-600 p-3 rounded border border-slate-500';
            attributeRow.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Attribute Name</label>
                        <input type="text" value="${attribute.friendly_name || attribute.name}" class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm" readonly>
                        <div class="text-xs text-slate-500 mt-1">Technical: ${attribute.name}</div>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Type</label>
                        <input type="text" value="${attribute.type}" class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm" readonly>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Options</label>
                        <div class="bg-slate-600 border border-slate-500 rounded p-2 min-h-[40px]">
                            <div id="attribute-options-${index}" class="flex flex-wrap gap-1 mb-2" data-attribute-index="${index}" data-attribute-name="${(attribute.friendly_name || attribute.name).toLowerCase()}">
                                ${(attribute.friendly_options || attribute.options).map(option => `
                                    <span class="inline-flex items-center px-2 py-1 bg-blue-600 text-white text-xs rounded" data-option="${option}">
                                        ${option}
                                        <button type="button" class="ml-1 text-blue-200 hover:text-white remove-option-btn">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </span>
                                `).join('')}
                            </div>
                            <div class="relative">
                                <input type="text" id="attribute-option-input-${index}" placeholder="Type to add option..." class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm focus:border-blue-500 focus:outline-none">
                                <div id="attribute-option-suggestions-${index}" class="absolute top-full left-0 right-0 bg-slate-700 border border-slate-500 rounded mt-1 max-h-32 overflow-y-auto hidden z-10">
                                    <!-- Suggestions will be populated here -->
                                </div>
                            </div>
                        </div>
                        ${attribute.friendly_options ? `<div class="text-xs text-slate-500 mt-1">Technical IDs: ${attribute.options.join(', ')}</div>` : ''}
                    </div>
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input type="checkbox" ${attribute.visible ? 'checked' : ''} class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500">
                            <span class="ml-2 text-xs text-slate-300">Visible</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" ${attribute.variation ? 'checked' : ''} class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500">
                            <span class="ml-2 text-xs text-slate-300">Variation</span>
                        </label>
                    </div>
                </div>
            `;
            container.appendChild(attributeRow);
        });
    }

    function populateVariations(variations) {
        const container = document.getElementById('product-variations');
        container.innerHTML = '';
        
        variations.forEach((variation, index) => {
            const variationRow = document.createElement('div');
            variationRow.className = 'bg-slate-600 p-3 rounded border border-slate-500';
            variationRow.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-2">
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">SKU</label>
                        <input type="text" value="${variation.sku || ''}" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Price</label>
                        <input type="number" step="0.01" value="${variation.price || ''}" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Stock</label>
                        <input type="number" value="${variation.stock_quantity || ''}" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                    </div>
                </div>
                <div class="text-xs text-slate-400">
                    Attributes: ${Object.keys(variation.attributes || {}).map(attr => {
                        // Convert technical names to friendly names
                        if (attr.startsWith('pa_')) {
                            return attr.replace('pa_', '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        } else if (attr.startsWith('_')) {
                            return attr.replace('_', '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        }
                        return attr;
                    }).join(', ') || 'None'}
                </div>
            `;
            container.appendChild(variationRow);
        });
    }

    function addAttributeRow() {
        const container = document.getElementById('product-attributes');
        const attributeRow = document.createElement('div');
        attributeRow.className = 'bg-slate-600 p-3 rounded border border-slate-500';
        attributeRow.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
                <div>
                    <label class="block text-xs text-slate-300 mb-1">Attribute Name</label>
                    <input type="text" placeholder="e.g., Color, Size" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-300 mb-1">Type</label>
                    <select class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                        <option value="custom">Custom</option>
                        <option value="taxonomy">Taxonomy</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
                <div>
                    <label class="block text-xs text-slate-300 mb-1">Options (comma-separated)</label>
                    <input type="text" placeholder="e.g., Red, Blue, Green" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                </div>
                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500">
                        <span class="ml-2 text-xs text-slate-300">Visible</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500">
                        <span class="ml-2 text-xs text-slate-300">Variation</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="px-2 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-500">Remove</button>
            </div>
        `;
        container.appendChild(attributeRow);
    }

    function addVariationRow() {
        const container = document.getElementById('product-variations');
        const variationRow = document.createElement('div');
        variationRow.className = 'bg-slate-600 p-3 rounded border border-slate-500';
        variationRow.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-2">
                <div>
                    <label class="block text-xs text-slate-300 mb-1">SKU</label>
                    <input type="text" placeholder="Variation SKU" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-300 mb-1">Price</label>
                    <input type="number" step="0.01" placeholder="0.00" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-300 mb-1">Stock</label>
                    <input type="number" placeholder="0" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="px-2 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-500">Remove</button>
            </div>
        `;
        container.appendChild(variationRow);
    }

    // Attribute option management functions
    window.removeAttributeOption = function(attributeIndex, option) {
        const optionsContainer = document.getElementById(`attribute-options-${attributeIndex}`);
        if (!optionsContainer) return;
        
        const optionElements = optionsContainer.querySelectorAll('span');
        optionElements.forEach(element => {
            // Get the text content without the X button
            const textContent = element.textContent.trim();
            const optionText = textContent.replace(/×$/, '').trim();
            
            if (optionText === option) {
                element.remove();
            }
        });
    }

    window.addAttributeOption = function(attributeIndex, option) {
        if (!option.trim()) return;
        
        const optionsContainer = document.getElementById(`attribute-options-${attributeIndex}`);
        const input = document.getElementById(`attribute-option-input-${attributeIndex}`);
        
        // Check if option already exists
        const existingOptions = Array.from(optionsContainer.querySelectorAll('span')).map(el => el.textContent.trim().split('×')[0].trim());
        if (existingOptions.includes(option.trim())) {
            input.value = '';
            return;
        }
        
        // Add new option tag
        const optionTag = document.createElement('span');
        optionTag.className = 'inline-flex items-center px-2 py-1 bg-blue-600 text-white text-xs rounded';
        optionTag.setAttribute('data-option', option);
        optionTag.innerHTML = `
            ${option}
            <button type="button" class="ml-1 text-blue-200 hover:text-white remove-option-btn">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        optionsContainer.appendChild(optionTag);
        
        // Clear input
        input.value = '';
        hideAttributeSuggestions(attributeIndex);
    }

    function handleAttributeOptionKeypress(event, attributeIndex) {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            const input = document.getElementById(`attribute-option-input-${attributeIndex}`);
            addAttributeOption(attributeIndex, input.value);
        } else if (event.key === 'Escape') {
            hideAttributeSuggestions(attributeIndex);
        }
    }

    function showAttributeSuggestions(attributeIndex, query) {
        const suggestionsContainer = document.getElementById(`attribute-option-suggestions-${attributeIndex}`);
        
        if (!query.trim()) {
            hideAttributeSuggestions(attributeIndex);
            return;
        }
        
        // Get attribute name from data attribute - much more reliable!
        const optionsContainer = document.getElementById(`attribute-options-${attributeIndex}`);
        const attributeName = optionsContainer.getAttribute('data-attribute-name') || '';
        
        // Get existing options from the current attribute (from database)
        const existingOptions = Array.from(optionsContainer.querySelectorAll('span[data-option]')).map(el => el.getAttribute('data-option'));
        
        // Use only existing options from the database - no hardcoded lists
        const suggestions = existingOptions;
        
        // Filter suggestions based on query
        const filteredSuggestions = suggestions.filter(option => 
            option.toLowerCase().includes(query.toLowerCase())
        ).slice(0, 5);
        
        
        if (filteredSuggestions.length > 0) {
            // Get currently added options
            const optionsContainer = document.getElementById(`attribute-options-${attributeIndex}`);
            const existingOptions = Array.from(optionsContainer.querySelectorAll('span[data-option]')).map(el => el.getAttribute('data-option'));
            
            suggestionsContainer.innerHTML = filteredSuggestions.map(suggestion => {
                const isAlreadyAdded = existingOptions.includes(suggestion);
                const bgColor = isAlreadyAdded ? 'bg-green-600' : 'hover:bg-slate-600';
                const textColor = isAlreadyAdded ? 'text-white' : 'text-slate-200';
                const icon = isAlreadyAdded ? 
                    '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' : '';
                const action = isAlreadyAdded ? `removeAttributeOption(${attributeIndex}, '${suggestion}')` : `addAttributeOption(${attributeIndex}, '${suggestion}')`;
                
                return `
                    <div class="px-3 py-2 text-sm ${textColor} ${bgColor} cursor-pointer flex items-center" onclick="${action}">
                        ${icon}${suggestion}
                    </div>
                `;
            }).join('');
                suggestionsContainer.classList.remove('hidden');
            } else {
                hideAttributeSuggestions(attributeIndex);
            }
    }

    window.hideAttributeSuggestions = function(attributeIndex) {
        const suggestionsContainer = document.getElementById(`attribute-option-suggestions-${attributeIndex}`);
        suggestionsContainer.classList.add('hidden');
    }

    function highlightJSON(jsonString) {
        return jsonString
            .replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = '';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = ''; // Keys stay default color
                    } else {
                        cls = 'json-string'; // String values get colored
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean'; // Boolean values get colored
                } else if (/null/.test(match)) {
                    cls = 'json-null'; // Null values get colored
                } else if (/^-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?$/.test(match)) {
                    cls = 'json-number'; // Number values get colored
                }
                
                if (cls) {
                    return '<span class="' + cls + '">' + match + '</span>';
                } else {
                    return match; // Keys and punctuation stay uncolored
                }
            });
    }

    // updateJSONPreview function removed - JSON preview now only in JSON tab

    function getProductEditorFormData() {
        const metaData = [];
        document.querySelectorAll('#product-meta-data > div').forEach(row => {
            const keyInput = row.querySelector('input[placeholder="Meta Key"]');
            const valueInput = row.querySelector('input[placeholder="Meta Value"]');
            if (keyInput.value.trim() && valueInput.value.trim()) {
                metaData.push({
                    key: keyInput.value.trim(),
                    value: valueInput.value.trim()
                });
            }
        });

        // Get variations data if it's a variable product
        const variations = [];
        if (currentEditingProduct?.type === 'variable') {
            document.querySelectorAll('#variations-list > div[data-variation-id]').forEach(variationDiv => {
                const variationId = variationDiv.getAttribute('data-variation-id');
                const variationData = {
                    id: parseInt(variationId),
                    sku: variationDiv.querySelector('[data-field="sku"]').value,
                    price: variationDiv.querySelector('[data-field="price"]').value,
                    sale_price: variationDiv.querySelector('[data-field="sale_price"]').value,
                    stock_quantity: variationDiv.querySelector('[data-field="stock_quantity"]').value,
                    stock_status: variationDiv.querySelector('[data-field="stock_status"]').value
                };
                variations.push(variationData);
            });
        }

        return {
            product_id: currentEditingProduct?.id,
            name: document.getElementById('product-name').value,
            sku: document.getElementById('product-sku').value,
            barcode: document.getElementById('product-barcode').value,
            regular_price: document.getElementById('product-regular-price').value,
            sale_price: document.getElementById('product-sale-price').value,
            status: document.getElementById('product-status').value,
            featured: document.getElementById('product-featured').checked,
            tax_class: document.getElementById('product-tax-class').value,
            tax_status: document.querySelector('input[name="tax-status"]:checked').value,
            stock_quantity: document.getElementById('product-stock-quantity').value,
            manage_stock: document.getElementById('product-manage-stock').checked,
            meta_data: metaData,
            variations: variations
        };
    }

    async function saveProductEditor() {
        if (!currentEditingProduct) return;
        
        const statusEl = document.getElementById('product-editor-status');
        statusEl.textContent = 'Saving...';
        statusEl.className = 'text-sm text-right h-5 mt-2 text-slate-400';
        
        const formData = getProductEditorFormData();
        formData.nonce = appState.nonces.productEdit;
        
        try {
            const response = await fetch('api/product-edit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update_product',
                    ...formData
                })
            });
            
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message);
            
            statusEl.textContent = 'Product updated successfully!';
            statusEl.className = 'text-sm text-right h-5 mt-2 text-green-400';
            
            // Refresh data and close modal
            await refreshAllData();
            setTimeout(() => {
                document.getElementById('product-editor-modal').classList.add('hidden');
                renderStockList();
            }, 1500);
            
        } catch (error) {
            console.error('Error saving product:', error);
            statusEl.textContent = `Error: ${error.message}`;
            statusEl.className = 'text-sm text-right h-5 mt-2 text-red-400';
        }
    }

    // Make functions globally accessible
    window.openProductEditor = openProductEditor;
    window.addMetaDataRow = addMetaDataRow;
    window.removeMetaDataRow = removeMetaDataRow;

    function populateSettingsForm() {
        document.getElementById('setting-name').value = appState.settings.name || ''; document.getElementById('setting-logo-url').value = appState.settings.logo_url || ''; document.getElementById('setting-email').value = appState.settings.email || ''; document.getElementById('setting-phone').value = appState.settings.phone || ''; document.getElementById('setting-address').value = appState.settings.address || ''; document.getElementById('setting-footer1').value = appState.settings.footer_message_1 || ''; document.getElementById('setting-footer2').value = appState.settings.footer_message_2 || '';
    }

    async function saveSettings(event) {
        event.preventDefault(); const statusEl = document.getElementById('settings-status'); const saveBtn = event.target.querySelector('button[type="submit"]');
        saveBtn.disabled = true; statusEl.textContent = 'Saving...'; statusEl.className = 'ml-4 text-sm text-slate-400';
        const data = { name: document.getElementById('setting-name').value, logo_url: document.getElementById('setting-logo-url').value, email: document.getElementById('setting-email').value, phone: document.getElementById('setting-phone').value, address: document.getElementById('setting-address').value, footer_message_1: document.getElementById('setting-footer1').value, footer_message_2: document.getElementById('setting-footer2').value, nonce: appState.nonces.settings };
        try {
            const response = await fetch('/jpos/api/settings.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json(); if (!result.success) throw new Error(result.data.message || 'Failed to save settings.');
            statusEl.textContent = result.data.message || 'Settings saved successfully!'; statusEl.className = 'ml-4 text-sm text-green-400';
            await loadReceiptSettings();
        } catch (error) { 
            console.error("Error saving settings:", error); 
            statusEl.textContent = `Error: ${error.message}`; 
            statusEl.className = 'ml-4 text-sm text-red-400'; 
        }
        finally { saveBtn.disabled = false; setTimeout(() => { statusEl.textContent = ''; }, 5000); }
    }

    function showReceipt(data) {
        const c = document.getElementById('receipt-content');
        let itemsHTML = '';
        (data.items || []).forEach((item, index) => {
            itemsHTML += `<div class="grid grid-cols-12 gap-2 py-1 border-t border-dashed border-gray-400"><div class="col-span-1">${index + 1}</div><div class="col-span-6">${item.name}<br><span class="text-xs text-gray-500">SKU: ${item.sku || 'N/A'}</span></div><div class="col-span-2 text-center">${item.quantity}</div><div class="col-span-3 text-right">$${parseFloat(item.total).toFixed(2)}</div></div>`;
        });

        // Fallback: calculate subtotal if missing or not a number
        let subtotal = parseFloat(data.subtotal);
        if (isNaN(subtotal)) {
            subtotal = 0;
            (data.items || []).forEach(item => {
                subtotal += (parseFloat(item.total) || 0);
            });
        }

        let totalsHTML = `<div class="flex justify-between"><p>Subtotal:</p><p>$${subtotal.toFixed(2)}</p></div>`;
        
        // Show fee if present (check both possible data formats)
        if (data.fee && data.fee.amount) {
            const feeAmount = parseFloat(data.fee.amountType === 'percentage' ? (data.subtotal * (parseFloat(data.fee.amount) / 100)) : data.fee.amount);
            totalsHTML += `<div class="flex justify-between text-black"><p>${data.fee.label || ((data.fee.amountType === 'percentage' ? data.fee.amount + '%' : '$' + parseFloat(data.fee.amount).toFixed(2)) + ' Fee')}:</p><p>+$${feeAmount.toFixed(2)}</p></div>`;
        } else if (data.fee_discount && data.fee_discount.type === 'fee' && data.fee_discount.amount) {
            const feeAmount = parseFloat(data.fee_discount.amountType === 'percentage' ? (data.subtotal * (parseFloat(data.fee_discount.amount) / 100)) : data.fee_discount.amount);
            totalsHTML += `<div class="flex justify-between text-black"><p>${data.fee_discount.label || ((data.fee_discount.amountType === 'percentage' ? data.fee_discount.amount + '%' : '$' + parseFloat(data.fee_discount.amount).toFixed(2)) + ' Fee')}:</p><p>+$${feeAmount.toFixed(2)}</p></div>`;
        }
        
        // Show discount if present (check both possible data formats)
        if (data.discount && data.discount.amount) {
            const discountAmount = parseFloat(data.discount.amountType === 'percentage' ? (data.subtotal * (parseFloat(data.discount.amount) / 100)) : data.discount.amount);
            totalsHTML += `<div class="flex justify-between text-black"><p>${data.discount.label || ((data.discount.amountType === 'percentage' ? data.discount.amount + '%' : '$' + parseFloat(data.discount.amount).toFixed(2)) + ' Discount')}:</p><p>-$${Math.abs(discountAmount).toFixed(2)}</p></div>`;
        } else if (data.fee_discount && data.fee_discount.type === 'discount' && data.fee_discount.amount) {
            const discountAmount = parseFloat(data.fee_discount.amountType === 'percentage' ? (data.subtotal * (parseFloat(data.fee_discount.amount) / 100)) : data.fee_discount.amount);
            totalsHTML += `<div class="flex justify-between text-black"><p>${data.fee_discount.label || ((data.fee_discount.amountType === 'percentage' ? data.fee_discount.amount + '%' : '$' + parseFloat(data.fee_discount.amount).toFixed(2)) + ' Discount')}:</p><p>-$${Math.abs(discountAmount).toFixed(2)}</p></div>`;
        }
        totalsHTML += `<div class="flex justify-between font-bold text-lg border-t border-solid border-gray-400 mt-1 pt-1"><p>Total:</p><p>$${parseFloat(data.total).toFixed(2)}</p></div>`;
        
        // Payment methods
        let paymentHTML = '';
        if (data.split_payments && Array.isArray(data.split_payments) && data.split_payments.length > 1) {
            paymentHTML = `<div class='flex flex-col gap-1 mt-2'><p class='font-semibold'>Payment Methods:</p>`;
            data.split_payments.forEach(sp => {
                let method = sp.method === 'Other' ? 'Other' : sp.method;
                paymentHTML += `<div class='flex justify-between'><span>${method}</span><span>$${parseFloat(sp.amount).toFixed(2)}</span></div>`;
            });
            paymentHTML += '</div>';
        } else {
            let method = data.payment_method === 'Other' ? 'Other' : data.payment_method;
            paymentHTML = `<div class="flex justify-between mt-2"><p>Payment Method:</p><p>${method}</p></div>`;
        }
        
        // Ensure logo URL is absolute
        const logoUrl = appState.settings.logo_url ? 
            (appState.settings.logo_url.startsWith('http') ? appState.settings.logo_url : window.location.origin + appState.settings.logo_url) : 
            '';
        
        c.innerHTML = `
            <div class="text-center space-y-1 mb-4"> ${logoUrl ? `<img src="${logoUrl}" alt="Logo" class="w-24 h-auto mx-auto" onerror="this.style.display='none';">` : ''} <p class="font-bold text-lg">${appState.settings.name || 'Your Store'}</p><p>${appState.settings.email || ''}</p><p>Phone: ${appState.settings.phone || ''}</p><p>${appState.settings.address || ''}</p></div>
            <div class="space-y-1 border-t border-dashed border-gray-400 pt-2"><p>Order No: #${data.order_number}</p><p>Date: ${formatDateTime(data.date_created || data.date)}</p></div>
            <div class="mt-2"><div class="grid grid-cols-12 gap-2 font-bold py-1"><div class="col-span-1">#</div><div class="col-span-6">Item</div><div class="col-span-2 text-center">Qty</div><div class="col-span-3 text-right">Total</div></div>${itemsHTML}</div>
            <div class="mt-2 pt-2 border-t border-dashed border-gray-400 space-y-1">${totalsHTML}${paymentHTML}</div>
            <div class="text-center mt-4 pt-2 border-t border-dashed border-gray-400 space-y-1"><p>${appState.settings.footer_message_1 || ''}</p><p class="text-xs">${appState.settings.footer_message_2 || ''}</p></div>`;

        document.getElementById('receipt-modal').classList.remove('hidden');
    }

    function closeReceiptModal() { document.getElementById('receipt-modal').classList.add('hidden'); }

    function printReceipt() {
        const c = document.getElementById('receipt-content').innerHTML; 
        const p = window.open('', '', 'height=600,width=400');
        p.document.write('<html><head><title>Print Receipt</title>'); 
        p.document.write(`<style>body{font-family:monospace;font-size:12px;margin:20px}.grid{display:grid}.grid-cols-12{grid-template-columns:repeat(12,minmax(0,1fr))}.col-span-1{grid-column:span 1/span 1}.col-span-2{grid-column:span 2/span 2}.col-span-3{grid-column:span 3/span 3}.col-span-6{grid-column:span 6/span 6}.text-center{text-align:center}.text-right{text-align:right}.font-bold{font-weight:700}.text-lg{font-size:1.125rem}.mb-4{margin-bottom:1rem}.pt-2{padding-top:.5rem}.mt-1{margin-top:.25rem}.mt-2{margin-top:.5rem}.mt-4{margin-top:1rem}.py-1{padding-top:.25rem;padding-bottom:.25rem}.space-y-1>*:not([hidden])~*:not([hidden]){--tw-space-y-reverse:0;margin-top:calc(.25rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(.25rem * var(--tw-space-y-reverse))}.border-t{border-top-width:1px}.border-dashed{border-style:dashed}.border-solid{border-style:solid}.border-gray-400{border-color:#9ca3af}img{max-width:150px;margin:0 auto;display:block;height:auto}.text-black{color:#000}</style>`);
        p.document.write('</head><body>'); 
        p.document.write(c); 
        p.document.write('</body></html>');
        p.document.close(); 
        p.focus(); 
        setTimeout(() => { p.print(); p.close(); }, 500);
    }
    
    function showFeeDiscountModal(type) {
        if (!appState.drawer.isOpen) { showDrawerModal('open'); return; }
        const modal = document.getElementById('fee-discount-modal');
        const titleEl = document.getElementById('fee-discount-modal-title');
        const amountInput = document.getElementById('fee-discount-amount');
        const labelInput = document.getElementById('fee-discount-title');
        const typeSelector = document.getElementById('fee-discount-type-selector');
        const applyBtn = document.getElementById('fee-discount-apply-btn');

        modal.dataset.type = type;
        if (type === 'fee') {
            amountInput.value = appState.fee.amount || '';
            labelInput.value = appState.fee.label || '';
            typeSelector.querySelectorAll('button').forEach(btn => btn.dataset.state = (appState.fee.amountType === btn.dataset.value ? 'active' : 'inactive'));
        } else {
            amountInput.value = appState.discount.amount || '';
            labelInput.value = appState.discount.label || '';
            typeSelector.querySelectorAll('button').forEach(btn => btn.dataset.state = (appState.discount.amountType === btn.dataset.value ? 'active' : 'inactive'));
        }

        titleEl.textContent = `Add ${type.charAt(0).toUpperCase() + type.slice(1)}`;
        applyBtn.disabled = true;
        modal.classList.remove('hidden');

        // Remove readonly and add first-keypress-clears logic
        amountInput.removeAttribute('readonly');
        let feeDiscountFirstInput = true;
        amountInput.addEventListener('focus', () => {
            if (feeDiscountFirstInput) {
                amountInput.value = '';
                feeDiscountFirstInput = false;
            }
        });
        amountInput.addEventListener('keydown', (e) => {
            if (feeDiscountFirstInput) {
                if (
                    (e.key.length === 1 && /[0-9.]/.test(e.key)) ||
                    e.key === 'Backspace'
                ) {
                    e.preventDefault();
                    amountInput.value = '';
                    feeDiscountFirstInput = false;
                    if (e.key !== 'Backspace') {
                        amountInput.value = e.key;
                    }
                    amountInput.dispatchEvent(new Event('input'));
                }
            }
        });
    }

    function hideFeeDiscountModal() {
        document.getElementById('fee-discount-modal').classList.add('hidden');
        renderCart();
    }

    function handleNumPadInput(event) {
        const input = document.getElementById('fee-discount-amount');
        const value = event.target.textContent;
        if (value === '.' && input.value.includes('.')) return;
        input.value += value;
        const modal = document.getElementById('fee-discount-modal');
        if (modal.dataset.type === 'fee') {
            appState.fee.amount = input.value;
        } else {
            appState.discount.amount = input.value;
        }
        updateFeeDiscountApplyButton();
    }

    function handleNumPadBackspace() {
        const input = document.getElementById('fee-discount-amount');
        input.value = input.value.slice(0, -1);
        const modal = document.getElementById('fee-discount-modal');
        if (modal.dataset.type === 'fee') {
            appState.fee.amount = input.value;
        } else {
            appState.discount.amount = input.value;
        }
        updateFeeDiscountApplyButton();
    }

    function handleFeeDiscountTypeToggle(event) {
        const target = event.target.closest('button');
        if (!target) return;
        const modal = document.getElementById('fee-discount-modal');
        if (modal.dataset.type === 'fee') {
            appState.fee.amountType = target.dataset.value;
        } else {
            appState.discount.amountType = target.dataset.value;
        }
        document.querySelectorAll('#fee-discount-type-selector button').forEach(btn => btn.dataset.state = 'inactive');
        target.dataset.state = 'active';
        updateFeeDiscountApplyButton();
    }

    function updateFeeDiscountApplyButton() {
        const amountInput = document.getElementById('fee-discount-amount');
        const applyBtn = document.getElementById('fee-discount-apply-btn');
        const modal = document.getElementById('fee-discount-modal');
        const type = modal.dataset.type;
        const amount = parseFloat(amountInput.value);
        let amountType = 'flat';
        if (type === 'fee') {
            amountType = appState.fee.amountType;
        } else {
            amountType = appState.discount.amountType;
        }
        if (isNaN(amount) || amount <= 0) {
            applyBtn.disabled = true;
        } else if (amountType === 'percentage' && amount > 100) {
            applyBtn.disabled = true;
        } else {
            applyBtn.disabled = false;
        }
    }

    function applyFeeDiscount() {
        const amountInput = document.getElementById('fee-discount-amount');
        const labelInput = document.getElementById('fee-discount-title');
        const amount = parseFloat(amountInput.value);
        const modal = document.getElementById('fee-discount-modal');
        if (isNaN(amount) || amount <= 0) { alert('Please enter a valid amount.'); return; }
        if (modal.dataset.type === 'fee') {
            if (appState.fee.amountType === 'percentage' && amount > 100) { alert('Percentage cannot exceed 100%.'); return; }
            appState.fee.amount = amount;
            appState.fee.label = labelInput.value.trim();
            appState.feeDiscount = { type: 'fee', amount: amount, label: appState.fee.label, amountType: appState.fee.amountType };
            showToast(`${appState.fee.label ? appState.fee.label : 'Fee'} added`);
        } else {
            if (appState.discount.amountType === 'percentage' && amount > 100) { alert('Percentage cannot exceed 100%.'); return; }
            appState.discount.amount = amount;
            appState.discount.label = labelInput.value.trim();
            appState.feeDiscount = { type: 'discount', amount: amount, label: appState.discount.label, amountType: appState.discount.amountType };
            showToast(`${appState.discount.label ? appState.discount.label : 'Discount'} added`);
        }
        hideFeeDiscountModal();
    }

    // Utility: Save and load cart/fee/discount from localStorage
    function saveCartState() {
        localStorage.setItem('jpos_cart', JSON.stringify(appState.cart.items));
        localStorage.setItem('jpos_fee', JSON.stringify(appState.fee));
        localStorage.setItem('jpos_discount', JSON.stringify(appState.discount));
    }
    function loadCartState() {
        const savedCart = localStorage.getItem('jpos_cart');
        const savedFee = localStorage.getItem('jpos_fee');
        const savedDiscount = localStorage.getItem('jpos_discount');
        appState.cart.items = savedCart ? JSON.parse(savedCart) : [];
        appState.fee = savedFee ? JSON.parse(savedFee) : { amount: '', label: '', amountType: 'flat' };
        appState.discount = savedDiscount ? JSON.parse(savedDiscount) : { amount: '', label: '', amountType: 'flat' };
    }

    init();
    loadCartState();
    renderCart();

    // Add Hold button to cart area after DOMContentLoaded
    function addHoldCartButton() {
        const aside = document.querySelector('aside.w-96');
        if (!aside || document.getElementById('hold-cart-btn')) return;
        const btn = document.createElement('button');
        btn.id = 'hold-cart-btn';
        btn.className = 'w-full bg-amber-600 text-white p-3 rounded-lg font-bold text-base hover:bg-amber-500 transition-colors mt-2';
        btn.textContent = 'Hold Cart';
        btn.onclick = holdCurrentCart;
        const clearBtn = document.getElementById('clear-cart-btn');
        clearBtn.parentNode.insertBefore(btn, clearBtn.nextSibling);
    }

    function holdCurrentCart() {
        if (appState.cart.items.length === 0) { showToast('Cart is empty.'); return; }
        const heldCarts = JSON.parse(localStorage.getItem('jpos_held_carts') || '[]');
        const timestamp = new Date().toISOString();
        heldCarts.push({
            id: 'held_' + Date.now(),
            cart: JSON.parse(JSON.stringify(appState.cart.items)),
            fee: JSON.parse(JSON.stringify(appState.fee)),
            discount: JSON.parse(JSON.stringify(appState.discount)),
            time: timestamp
        });
        localStorage.setItem('jpos_held_carts', JSON.stringify(heldCarts));
        clearCart(true);
        showToast('Cart held successfully!');
        renderHeldCarts();
    }

    function renderHeldCarts() {
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
            <div class="grid grid-cols-12 gap-2 font-bold text-xs text-slate-400 border-b border-slate-700 py-2 mb-2">
                <div class="col-span-3">Date Held</div>
                <div class="col-span-2">Items</div>
                <div class="col-span-2">Fee</div>
                <div class="col-span-2">Discount</div>
                <div class="col-span-2">Total</div>
                <div class="col-span-1 text-right">Actions</div>
            </div>
        `;
        list.appendChild(table);
        heldCarts.forEach(held => {
            // Calculate total
            let total = 0;
            (held.cart || []).forEach(item => {
                total += (parseFloat(item.price) || 0) * (item.qty || 0);
            });
            // Apply fee
            if (held.fee && held.fee.amount) {
                let feeVal = 0;
                if (held.fee.amountType === 'percentage') {
                    feeVal = total * (parseFloat(held.fee.amount) / 100);
                } else {
                    feeVal = parseFloat(held.fee.amount);
                }
                total += feeVal;
            }
            // Apply discount
            if (held.discount && held.discount.amount) {
                let discountVal = 0;
                if (held.discount.amountType === 'percentage') {
                    discountVal = total * (parseFloat(held.discount.amount) / 100);
                } else {
                    discountVal = parseFloat(held.discount.amount);
                }
                total -= Math.abs(discountVal);
            }
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 gap-2 items-center bg-slate-800 border border-slate-700 rounded-lg mb-2 py-2 px-2 cursor-pointer hover:bg-slate-700/70';
            row.setAttribute('data-id', held.id);
            row.innerHTML = `
                <div class="col-span-3 text-slate-300">${formatDateTime(held.time)}</div>
                <div class="col-span-2 text-slate-300">${held.cart.length}</div>
                <div class="col-span-2 text-green-400">${held.fee.amount ? (held.fee.amountType === 'percentage' ? held.fee.amount + '%' : '$' + parseFloat(held.fee.amount).toFixed(2)) : '-'}</div>
                <div class="col-span-2 text-amber-400">${held.discount.amount ? (held.discount.amountType === 'percentage' ? held.discount.amount + '%' : '$' + parseFloat(held.discount.amount).toFixed(2)) : '-'}</div>
                <div class="col-span-2 text-slate-100 font-mono">$${total.toFixed(2)}</div>
                <div class="col-span-1 flex gap-2 justify-end">
                    <button class="restore-held-btn bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-1 rounded" data-id="${held.id}">Restore</button>
                    <button class="delete-held-btn bg-red-600 hover:bg-red-500 text-white px-4 py-1 rounded" data-id="${held.id}">Delete</button>
                </div>
            `;
            // Only open modal if not clicking on an action button
            row.addEventListener('click', function(e) {
                if (e.target.closest('button')) return;
                showHeldCartDetailsModal(held);
            });
            list.appendChild(row);
        });
        list.querySelectorAll('.restore-held-btn').forEach(btn => btn.addEventListener('click', e => {
            const id = btn.dataset.id;
            restoreHeldCart(id);
        }));
        list.querySelectorAll('.delete-held-btn').forEach(btn => btn.addEventListener('click', e => {
            const id = btn.dataset.id;
            deleteHeldCart(id);
        }));
    }

    function showHeldCartDetailsModal(held) {
        const modal = document.getElementById('held-cart-details-modal');
        const content = document.getElementById('held-cart-details-content');
        let html = `<div class="mb-2"><span class="font-bold text-slate-300">Date Held:</span> ${formatDateTime(held.time)}</div>`;
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

    function restoreHeldCart(id) {
        let heldCarts = JSON.parse(localStorage.getItem('jpos_held_carts') || '[]');
        const held = heldCarts.find(h => h.id === id);
        if (!held) return;
        appState.cart.items = held.cart;
        appState.fee = held.fee;
        appState.discount = held.discount;
        saveCartState();
        heldCarts = heldCarts.filter(h => h.id !== id);
        localStorage.setItem('jpos_held_carts', JSON.stringify(heldCarts));
        renderHeldCarts();
        routingManager.navigateToView('pos-page');
        renderCart();
        showToast('Held cart restored to cart');
    }

    function deleteHeldCart(id) {
        let heldCarts = JSON.parse(localStorage.getItem('jpos_held_carts') || '[]');
        heldCarts = heldCarts.filter(h => h.id !== id);
        localStorage.setItem('jpos_held_carts', JSON.stringify(heldCarts));
        renderHeldCarts();
        showToast('Held cart deleted');
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadCartState();
        renderCart();
        addHoldCartButton();
        // ... existing code ...
        // Held carts menu button is already handled in setupMainAppEventListeners()
        // Note: orderIdSearch event listener is already set up above
        const splitPaymentBtn = document.getElementById('split-payment-btn');
        if (splitPaymentBtn) {
            splitPaymentBtn.addEventListener('click', openSplitPaymentModal);
        }
        const splitPaymentCancel = document.getElementById('split-payment-cancel');
        if (splitPaymentCancel) {
            splitPaymentCancel.addEventListener('click', () => {
                document.getElementById('split-payment-modal').classList.add('hidden');
            });
        }
    });

    function showToast(message) {
        // Remove any existing static toast
        let oldToast = document.getElementById('jpos-toast');
        if (oldToast) oldToast.remove();
        // Create new toast
        let toast = document.createElement('div');
        toast.id = 'jpos-toast';
        toast.innerHTML = `<span id="jpos-toast-message"></span><div id="jpos-toast-loader"></div>`;
        toast.style.position = 'fixed';
        toast.style.left = '50%';
        toast.style.bottom = '32px';
        toast.style.transform = 'translateX(-50%) translateY(40px)';
        toast.style.minWidth = '120px';
        toast.style.maxWidth = '90vw';
        toast.style.background = 'rgba(255,255,255,0.60)';
        toast.style.color = '#111';
        toast.style.fontWeight = 'bold';
        toast.style.fontSize = '0.95rem';
        toast.style.padding = '0.5rem 1rem 0.7rem 1rem';
        toast.style.borderRadius = '0.6rem';
        toast.style.boxShadow = '0 4px 24px 0 rgba(0,0,0,0.10)';
        toast.style.backdropFilter = 'blur(8px)';
        toast.style.zIndex = '9999';
        toast.style.display = 'flex';
        toast.style.flexDirection = 'column';
        toast.style.alignItems = 'center';
        toast.style.opacity = '0';
        toast.style.pointerEvents = 'none';
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        toast.style.overflow = 'hidden';
        document.body.appendChild(toast);
        document.getElementById('jpos-toast-message').textContent = message;
        const loader = document.getElementById('jpos-toast-loader');
        loader.style.height = '3px';
        loader.style.width = '100%';
        loader.style.background = 'rgba(0,0,0,0.10)';
        loader.style.borderRadius = '0 0 0.6rem 0.6rem';
        loader.style.overflow = 'hidden';
        loader.style.position = 'absolute';
        loader.style.left = '0';
        loader.style.bottom = '0';
        loader.style.margin = '0';
        loader.innerHTML = `<div style="height:100%;width:100%;background:#111;border-radius:0 0 0.6rem 0.6rem;transform:scaleX(1);transform-origin:left;transition:transform 2.5s linear;"></div>`;
        toast.style.position = 'fixed';
        toast.style.bottom = '32px';
        toast.style.left = '50%';
        toast.style.transform = 'translateX(-50%) translateY(40px)';
        toast.style.overflow = 'visible';
        toast.appendChild(loader);
        setTimeout(() => {
            loader.firstChild.style.transform = 'scaleX(0)';
        }, 10);
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(-50%) translateY(0)';
        }, 10);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(40px)';
            setTimeout(() => { toast.remove(); }, 400);
        }, 2500);
        toast.style.height = '24px';
        toast.style.minHeight = '24px';
        toast.style.maxHeight = '24px';
        toast.style.background = 'rgba(255,255,255,0.6)'; // 60% transparency
        toast.style.backdropFilter = 'blur(8px)'; // glassmorphism
        toast.style.padding = '0 1rem 0 1rem'; // remove vertical padding to keep height at 24px
        toast.style.display = 'flex';
        toast.style.alignItems = 'center';
        toast.style.justifyContent = 'center';
        toast.style.position = 'fixed';
        toast.style.left = '50%';
        toast.style.bottom = '32px';
        toast.style.transform = 'translateX(-50%) translateY(40px)';
        toast.style.overflow = 'visible';
        // Adjust message style for vertical centering
        const msg = toast.querySelector('#jpos-toast-message');
        msg.style.lineHeight = '24px';
        msg.style.height = '24px';
        msg.style.display = 'block';
        // Loader bar at the bottom, overlay, not increasing height
        loader.style.height = '3px';
        loader.style.width = '100%';
        loader.style.background = 'rgba(0,0,0,0.10)';
        loader.style.borderRadius = '0 0 0.6rem 0.6rem';
        loader.style.position = 'absolute';
        loader.style.left = '0';
        loader.style.bottom = '0';
        loader.style.margin = '0';
        loader.innerHTML = `<div style="height:100%;width:100%;background:#111;border-radius:0 0 0.6rem 0.6rem;transform:scaleX(1);transform-origin:left;transition:transform 2.5s linear;"></div>`;
    }

    function openSplitPaymentModal() {
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
        const cartTotal = getCartTotal();
        let splits = [
            { method: 'Cash', amount: cartTotal }
        ];
        let activeInput = null;
        let inputFirstFocus = [];
        renderSplitRows();
        modal.classList.remove('hidden');

        function renderSplitRows() {
            list.innerHTML = '';
            inputFirstFocus = splits.map(() => true);
            splits.forEach((split, i) => {
                const row = document.createElement('div');
                row.className = 'flex items-center gap-2';
                row.innerHTML = `
                    <select class="split-method p-1 rounded bg-slate-700 border border-slate-600 text-xs w-24">${paymentMethods.map(opt => `<option value="${opt.value}"${split.method === opt.value ? ' selected' : ''}>${opt.label}</option>`).join('')}</select>
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
                    // (keep for mouse/touch users)
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
                // NEW: On first keydown, clear and replace
                amountInput.addEventListener('keydown', (e) => {
                    if (inputFirstFocus[i]) {
                        // Only allow number keys, dot, or backspace
                        if (
                            (e.key.length === 1 && /[0-9.]/.test(e.key)) ||
                            e.key === 'Backspace'
                        ) {
                            e.preventDefault();
                            amountInput.value = '';
                            splits[i].amount = '';
                            inputFirstFocus[i] = false;
                            // Insert the pressed key (if not backspace)
                            if (e.key !== 'Backspace') {
                                amountInput.value = e.key;
                                splits[i].amount = e.key;
                            }
                            // Optionally, trigger input event for any listeners
                            amountInput.dispatchEvent(new Event('input'));
                        }
                    }
                });
                // NEW: Update total as user types
                amountInput.addEventListener('input', () => {
                    splits[i].amount = parseFloat(amountInput.value) || 0;
                    updateTotal();
                });
                if (i === 0 && !activeInput) setActiveInput(amountInput, i);
                if (splits.length > 1) {
                    row.querySelector('.remove-split').addEventListener('click', () => {
                        splits.splice(i, 1);
                        renderSplitRows();
                        updateTotal();
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
                    // Add the first unused method
                    const used = splits.map(s => s.method);
                    const next = paymentMethods.find(m => !used.includes(m.value));
                    if (next) splits.push({ method: next.value, amount: 0 });
                    renderSplitRows();
                };
                list.appendChild(addBtn);
            }
            updateTotal();
        }
        function setActiveInput(input, idx) {
            if (activeInput) activeInput.classList.remove('ring', 'ring-indigo-400');
            activeInput = input;
            activeInput.classList.add('ring', 'ring-indigo-400');
            activeInput.dataset.splitIdx = idx;
        }
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
                        updateTotal();
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
                updateTotal();
            };
        }
        function updateTotal() {
            const sum = splits.reduce((a, b) => a + (parseFloat(b.amount) || 0), 0);
            const change = sum - cartTotal;
            
            // Calculate subtotal (before fees/discounts)
            let subtotal = 0;
            (appState.cart.items || []).forEach(item => {
                subtotal += (parseFloat(item.price) || 0) * (item.qty || 0);
            });
            
            let breakdownHTML = `<div class="text-sm text-slate-400">Subtotal: $${subtotal.toFixed(2)}</div>`;
            
            // Show fee if present
            if (appState.fee && appState.fee.amount) {
                let feeVal = 0;
                if (appState.fee.amountType === 'percentage') {
                    feeVal = subtotal * (parseFloat(appState.fee.amount) / 100);
                } else {
                    feeVal = parseFloat(appState.fee.amount);
                }
                breakdownHTML += `<div class="text-sm text-green-400">Fee: +$${feeVal.toFixed(2)}</div>`;
            }
            
            // Show discount if present
            if (appState.discount && appState.discount.amount) {
                let discountVal = 0;
                if (appState.discount.amountType === 'percentage') {
                    discountVal = subtotal * (parseFloat(appState.discount.amount) / 100);
                } else {
                    discountVal = parseFloat(appState.discount.amount);
                }
                breakdownHTML += `<div class="text-sm text-amber-400">Discount: -$${Math.abs(discountVal).toFixed(2)}</div>`;
            }
            
            if (change >= 0) {
                totalEl.innerHTML = `
                    ${breakdownHTML}
                    <div class="text-sm font-bold text-slate-300 border-t border-slate-600 pt-1 mt-1">Cart Total: $${cartTotal.toFixed(2)}</div>
                    <div class="text-sm text-slate-400">Amount Paid: $${sum.toFixed(2)}</div>
                    <div class="text-lg font-bold text-green-400">Change: $${change.toFixed(2)}</div>
                `;
            } else {
                totalEl.innerHTML = `
                    ${breakdownHTML}
                    <div class="text-sm font-bold text-slate-300 border-t border-slate-600 pt-1 mt-1">Cart Total: $${cartTotal.toFixed(2)}</div>
                    <div class="text-sm text-slate-400">Amount Paid: $${sum.toFixed(2)}</div>
                    <div class="text-lg font-bold text-red-400">Remaining: $${Math.abs(change).toFixed(2)}</div>
                `;
            }
            
            // Enable/disable pay button based on whether total covers the cart
            applyBtn.disabled = sum < cartTotal;
        }
        
        applyBtn.onclick = async () => {
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
                if (appState.return_from_order_id) {
                    const refund_items = appState.cart.items.filter(item => item.qty < 0);
                    const new_sale_items = appState.cart.items.filter(item => item.qty > 0);
                    
                    const payload = {
                        original_order_id: appState.return_from_order_id,
                        refund_items: refund_items,
                        new_sale_items: new_sale_items,
                        payment_method: splits[0].method,
                    };

                    payload.nonce = appState.nonces.refund;
                    const response = await fetch('/jpos/api/refund.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({message: `Server responded with ${response.status}`}));
                        throw new Error(errorData.message);
                    }
                    const result = await response.json();

                    if (result.success) {
                        alert('Refund/Exchange processed successfully!');
                        clearCart(true);
                        await fetchOrders();
                    } else {
                        throw new Error(result.message || 'Refund failed.');
                    }
                } else {
                    // Use split payments if multiple methods, otherwise single payment
                    let payload = {
                        cart_items: cart,
                        payment_method: splits[0].method,
                        fee_discount: appState.feeDiscount.type ? appState.feeDiscount : null
                    };
                    if (splits.length > 1) {
                        payload.split_payments = splits.map(s => ({ method: s.method, amount: parseFloat(s.amount) || 0 }));
                    }
                    payload.nonce = appState.nonces.checkout;
                    const response = await fetch('/jpos/api/checkout.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({message: `Server responded with ${response.status}`}));
                        throw new Error(errorData.message);
                    }
                    const result = await response.json();

                    if (result.success) {
                        clearCart(true);
                        await refreshAllData(); 
                        showReceipt(result.data.receipt_data);
                    } else {
                        throw new Error(result.message || result.data.message || 'Checkout failed.');
                    }
                }
            } catch (error) {
                alert(`An error occurred: ${error.message}`);
            } finally {
                checkoutBtn.disabled = !appState.drawer.isOpen;
                checkoutBtn.textContent = 'Checkout';
            }
        };
    }

    function getCartTotal() {
        let total = 0;
        (cart || []).forEach(item => {
            total += (parseFloat(item.price) || 0) * (item.qty || 0);
        });
        if (appState.fee && appState.fee.amount) {
            let feeVal = 0;
            if (appState.fee.amountType === 'percentage') {
                feeVal = total * (parseFloat(appState.fee.amount) / 100);
            } else {
                feeVal = parseFloat(appState.fee.amount);
            }
            total += feeVal;
        }
        if (appState.discount && appState.discount.amount) {
            let discountVal = 0;
            if (appState.discount.amountType === 'percentage') {
                discountVal = total * (parseFloat(appState.discount.amount) / 100);
            } else {
                discountVal = parseFloat(appState.discount.amount);
            }
            total -= Math.abs(discountVal);
        }
        return Math.max(0, total);
    }

    // PDF Export function for reports
    async function exportReportsToPDF() {
        const btn = document.getElementById('export-pdf-btn');
        if (btn) btn.disabled = true;
        try {
            const res = await fetch('/jpos/api/export-pdf.php');
            const result = await res.json();
            if (!result.success) throw new Error(result.data?.message || 'Failed to generate PDF');
            if (result.data && result.data.pdf_data) {
                // TCPDF server-side PDF: download
                const pdfBlob = b64toBlob(result.data.pdf_data, 'application/pdf');
                const url = URL.createObjectURL(pdfBlob);
                const a = document.createElement('a');
                a.href = url;
                a.download = result.data.filename || 'sales_report.pdf';
                document.body.appendChild(a);
                a.click();
                setTimeout(() => {
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                }, 100);
            } else if (result.data && result.data.fallback) {
                // Fallback: client-side PDF (simple)
                await generateClientSidePDF(result.data.data);
            } else {
                throw new Error('Unexpected response from server.');
            }
        } catch (err) {
            alert('Failed to export PDF: ' + err.message);
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    // Helper: base64 to Blob
    function b64toBlob(b64Data, contentType = '', sliceSize = 512) {
        const byteCharacters = atob(b64Data);
        const byteArrays = [];
        for (let offset = 0; offset < byteCharacters.length; offset += sliceSize) {
            const slice = byteCharacters.slice(offset, offset + sliceSize);
            const byteNumbers = new Array(slice.length);
            for (let i = 0; i < slice.length; i++) {
                byteNumbers[i] = slice.charCodeAt(i);
            }
            const byteArray = new Uint8Array(byteNumbers);
            byteArrays.push(byteArray);
        }
        return new Blob(byteArrays, { type: contentType });
    }

    // Fallback: very basic client-side PDF using jsPDF (if available)
async function generateClientSidePDF(data) {
    if (typeof window.jspdf === 'undefined') {
        alert('PDF export is not available (jsPDF not loaded and TCPDF not available on server).');
        return;
    }
    const doc = new window.jspdf.jsPDF();
        doc.setFontSize(16);
        doc.text(data.store_name + ' - Sales Report', 10, 15);
        doc.setFontSize(12);
        doc.text('Report Date: ' + data.report_date, 10, 25);
        doc.text('Total Revenue: $' + (data.summary.total_revenue || 0).toFixed(2), 10, 35);
        doc.text('Total Orders: ' + (data.summary.total_orders || 0), 10, 45);
        doc.text('Average Order Value: $' + (data.summary.average_order_value || 0).toFixed(2), 10, 55);
        doc.text('Cash Revenue: $' + (data.summary.cash_revenue || 0).toFixed(2), 10, 65);
        doc.text('Card/Linx Revenue: $' + (data.summary.card_revenue || 0).toFixed(2), 10, 75);
        doc.text('Cash Orders: ' + (data.summary.cash_orders || 0), 10, 85);
        doc.text('Card/Linx Orders: ' + (data.summary.card_orders || 0), 10, 95);
        doc.setFontSize(10);
        doc.text('Daily Breakdown:', 10, 110);
        let y = 120;
        doc.setFont('courier', 'normal');
        doc.text('Date      Revenue  Orders  CashRev  CardRev', 10, y);
        y += 7;
        data.daily_data.forEach(day => {
            if (y > 270) { doc.addPage(); y = 20; }
            doc.text(
                `${day.order_date}  $${(day.daily_revenue || 0).toFixed(2)}   ${day.daily_orders}   $${(day.daily_cash_revenue || 0).toFixed(2)}   $${(day.daily_card_revenue || 0).toFixed(2)}`,
                10, y
            );
            y += 7;
        });
        doc.save('sales_report_' + (data.report_date || '').replace(/\s+/g, '_') + '.pdf');
    }

    // Make all page data functions globally available for routing system
    window.fetchOrders = fetchOrders;
    window.fetchReportsData = fetchReportsData;
    window.fetchSessions = fetchSessions;
    window.renderStockList = renderStockList;
    window.populateSettingsForm = populateSettingsForm;
    window.renderHeldCarts = renderHeldCarts;
});