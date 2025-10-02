/**
 * JPOS Main Application - Modular Version
 * Entry point for the modular JPOS application
 */

document.addEventListener('DOMContentLoaded', async () => {
    console.log('JPOS Application Starting...');

    try {
        // Initialize modules
        const moduleLoader = window.moduleLoader;
        const success = await moduleLoader.init();
        
        if (!success) {
            throw new Error('Failed to initialize modules');
        }

        // Set up main application initializer
        window.appInitializer = {
            async init() {
                try {
                    // Load initial data
                    await this.loadInitialData();
                    
                    // Set up event listeners
                    this.setupEventListeners();
                    
                    // Initialize UI
                    this.initializeUI();
                    
                    console.log('JPOS Application initialized successfully');
                } catch (error) {
                    console.error('Failed to initialize application:', error);
                }
            },

            async loadInitialData() {
                // Initialize image optimizer (temporarily disabled for stability)
                if (window.imageOptimizer) {
                    // window.imageOptimizer.init(); // Disabled to fix image display issue
                    console.log('Image optimizer available but disabled for stability');
                }

                // Load products
                if (window.productsManager) {
                    const productsData = await window.productsManager.loadProducts();
                    if (productsData.categories && productsData.tags) {
                        window.productsManager.buildFilterUI(productsData.categories, productsData.tags);
                        window.productsManager.renderProducts();
                        
                        // Lazy loading temporarily disabled
                        // if (window.imageOptimizer) {
                        //     window.imageOptimizer.observeImages(document.getElementById('product-list'));
                        // }
                    }
                }

                // Load receipt settings
                if (window.settingsManager) {
                    await window.settingsManager.loadReceiptSettings();
                }

                // Check drawer status
                if (window.drawerManager) {
                    await window.drawerManager.checkDrawerStatus();
                }
            },

            setupEventListeners() {
                // Login form
                const loginForm = document.getElementById('login-form');
                if (loginForm && window.authManager) {
                    loginForm.addEventListener('submit', (e) => {
                        window.authManager.handleLogin(e).then(success => {
                            if (success) {
                                window.authManager.loadFullApp();
                            }
                        });
                    });
                }

                // Logout button
                const logoutBtn = document.getElementById('logout-btn');
                if (logoutBtn && window.authManager) {
                    logoutBtn.addEventListener('click', () => {
                        window.authManager.handleLogout();
                    });
                }

                // Product search
                const searchInput = document.getElementById('pos-search');
                if (searchInput && window.productsManager) {
                    searchInput.addEventListener('input', (e) => {
                        window.productsManager.handleSearch(e);
                    });
                }

                // Cart events
                const checkoutBtn = document.getElementById('checkout-btn');
                if (checkoutBtn && window.cartManager) {
                    checkoutBtn.addEventListener('click', () => {
                        window.cartManager.processTransaction();
                    });
                }

                const clearCartBtn = document.getElementById('clear-cart-btn');
                if (clearCartBtn && window.cartManager) {
                    clearCartBtn.addEventListener('click', () => {
                        window.cartManager.clearCart();
                    });
                }

                // Fee/Discount buttons
                const feeBtn = document.getElementById('add-fee-btn');
                const discountBtn = document.getElementById('add-discount-btn');
                if (feeBtn && window.cartManager) {
                    feeBtn.addEventListener('click', () => window.cartManager.showFeeDiscountModal('fee'));
                }
                if (discountBtn && window.cartManager) {
                    discountBtn.addEventListener('click', () => window.cartManager.showFeeDiscountModal('discount'));
                }

                // Drawer events
                const openDrawerBtn = document.getElementById('open-drawer-btn');
                const closeDrawerBtn = document.getElementById('close-drawer-btn');
                if (openDrawerBtn && window.drawerManager) {
                    openDrawerBtn.addEventListener('click', () => window.drawerManager.handleOpenDrawer());
                }
                if (closeDrawerBtn && window.drawerManager) {
                    closeDrawerBtn.addEventListener('click', () => window.drawerManager.handleCloseDrawer());
                }

                // Menu toggle
                const menuToggle = document.getElementById('menu-toggle');
                const sideMenu = document.getElementById('side-menu');
                if (menuToggle && sideMenu) {
                    menuToggle.addEventListener('click', () => {
                        sideMenu.classList.toggle('is-open');
                        document.getElementById('menu-overlay').classList.toggle('hidden');
                    });
                }

                // Page navigation
                const pageLinks = document.querySelectorAll('[data-page]');
                pageLinks.forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const pageId = link.dataset.page;
                        this.showPage(pageId);
                    });
                });

                // Fee/Discount modal events
                const feeDiscountModal = document.getElementById('fee-discount-modal');
                if (feeDiscountModal) {
                    const applyBtn = feeDiscountModal.querySelector('#apply-fee-discount');
                    const cancelBtn = feeDiscountModal.querySelector('#cancel-fee-discount');
                    
                    if (applyBtn && window.cartManager) {
                        applyBtn.addEventListener('click', () => window.cartManager.applyFeeDiscount());
                    }
                    if (cancelBtn && window.cartManager) {
                        cancelBtn.addEventListener('click', () => window.cartManager.hideFeeDiscountModal());
                    }
                }
            },

            initializeUI() {
                // Set initial page
                this.showPage('pos-page');
                
                // Render initial cart
                if (window.cartManager) {
                    window.cartManager.renderCart();
                }

                // Update drawer UI
                if (window.drawerManager) {
                    window.drawerManager.updateDrawerUI();
                }
            },

            async showPage(pageId, closeMenu = true) {
                // Hide all pages
                document.querySelectorAll('section.page-content').forEach(page => {
                    page.classList.add('hidden');
                });
                
                // Show selected page
                const targetPage = document.getElementById(pageId);
                if (targetPage) {
                    targetPage.classList.remove('hidden');
                }

                // Close menu if open
                if (closeMenu) {
                    const sideMenu = document.getElementById('side-menu');
                    if (sideMenu && sideMenu.classList.contains('is-open')) {
                        sideMenu.classList.remove('is-open');
                        document.getElementById('menu-overlay').classList.add('hidden');
                    }
                }

                // Load page-specific data
                switch (pageId) {
                    case 'orders-page':
                        if (window.ordersManager) {
                            await window.ordersManager.fetchOrders();
                        }
                        break;
                    case 'reports-page':
                        if (window.reportsManager) {
                            await window.reportsManager.fetchReportsData();
                        }
                        break;
                    case 'sessions-page':
                        if (window.drawerManager) {
                            await window.drawerManager.fetchSessions();
                        }
                        break;
                    case 'stock-page':
                        if (window.productsManager) {
                            window.productsManager.renderStockList();
                        }
                        break;
                    case 'settings-page':
                        if (window.settingsManager) {
                            window.settingsManager.populateSettingsForm();
                        }
                        break;
                }
            }
        };

        // Check authentication status
        const authManager = moduleLoader.getModule('auth');
        if (authManager && authManager.isAuthenticated()) {
            await authManager.loadFullApp();
        } else {
            authManager.showLoginScreen(true);
        }

    } catch (error) {
        console.error('Failed to start JPOS application:', error);
        document.body.innerHTML = `
            <div class="flex items-center justify-center min-h-screen bg-slate-900">
                <div class="text-center">
                    <h1 class="text-2xl font-bold text-red-400 mb-4">Application Error</h1>
                    <p class="text-slate-400">Failed to initialize the application. Please refresh the page.</p>
                    <button onclick="location.reload()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Refresh Page
                    </button>
                </div>
            </div>
        `;
    }
});

// Global utility functions for backward compatibility
window.showPage = (pageId, closeMenu = true) => {
    if (window.appInitializer) {
        window.appInitializer.showPage(pageId, closeMenu);
    }
};

window.toggleMenu = () => {
    const sideMenu = document.getElementById('side-menu');
    if (sideMenu) {
        sideMenu.classList.toggle('is-open');
        document.getElementById('menu-overlay').classList.toggle('hidden');
    }
};

