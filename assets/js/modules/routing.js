/**
 * JPOS Routing Module v1.5.10
 * Handles URL parameter-based routing for view persistence
 * Updated to support products-page instead of stock-page
 */

class RoutingManager {
    constructor() {
        this.currentView = 'pos-page';
        this.validViews = [
            'pos-page',
            'orders-page', 
            'reports-page',
            'sessions-page',
            'products-page',
            'held-carts-page',
            'settings-page'
        ];
        
        // Mapping between view IDs and menu button IDs
        this.viewToButtonMap = {
            'pos-page': 'menu-button-pos',
            'orders-page': 'menu-button-orders',
            'reports-page': 'menu-button-reports',
            'sessions-page': 'menu-button-sessions',
            'products-page': 'menu-button-products',
            'held-carts-page': 'menu-button-held-carts',
            'settings-page': 'menu-button-settings'
        };
        
        this.init();
    }

    /**
     * Initialize routing system
     */
    init() {
        // Listen for browser back/forward navigation
        window.addEventListener('popstate', (event) => {
            this.handleRouteChange(event.state?.view || 'pos-page');
        });

        // Get initial view from URL parameters
        this.loadViewFromURL();
    }

    /**
     * Load view from URL parameters on page load
     */
    loadViewFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const view = urlParams.get('view');
        
        if (view && this.validViews.includes(view)) {
            this.currentView = view;
        } else {
            // Default to pos-page if no valid view parameter
            this.currentView = 'pos-page';
        }
    }

    /**
     * Navigate to a specific view and update URL
     * @param {string} viewId - The view ID to navigate to
     * @param {boolean} updateURL - Whether to update the URL (default: true)
     */
    navigateToView(viewId, updateURL = true) {
        if (!this.validViews.includes(viewId)) {
            console.warn(`Invalid view: ${viewId}`);
            return;
        }

        this.currentView = viewId;

        if (updateURL) {
            // Update URL with view parameter
            const url = new URL(window.location);
            url.searchParams.set('view', viewId);
            
            // Use pushState to update URL without page reload
            window.history.pushState({ view: viewId }, '', url);
        }

        // Trigger view change event
        this.handleRouteChange(viewId);
    }

    /**
     * Handle route change and show the appropriate view
     * @param {string} viewId - The view ID to show
     */
    handleRouteChange(viewId) {
        // Hide all pages
        document.querySelectorAll('section.page-content').forEach(page => {
            page.classList.add('hidden');
        });

        // Show the target page
        const targetPage = document.getElementById(viewId);
        if (targetPage) {
            targetPage.classList.remove('hidden');
        }

        // Close menu if open
        const sideMenu = document.getElementById('side-menu');
        if (sideMenu && sideMenu.classList.contains('is-open')) {
            // Use global toggleMenu function if available
            if (typeof window.toggleMenu === 'function') {
                window.toggleMenu();
            } else {
                // Fallback to local method
                this.toggleMenu();
            }
        }

        // Update active menu button
        this.updateActiveMenuButton(viewId);

        // Load page-specific data
        this.loadPageData(viewId);
    }

    /**
     * Update active menu button styling
     * @param {string} viewId - The current view ID
     */
    updateActiveMenuButton(viewId) {
        // Remove active class from all menu buttons
        document.querySelectorAll('#side-menu button').forEach(btn => {
            btn.classList.remove('bg-indigo-600', 'text-white');
            btn.classList.add('text-slate-300', 'hover:bg-slate-700', 'hover:text-white');
        });

        // Add active class to current view button using the mapping
        const buttonId = this.viewToButtonMap[viewId];
        if (buttonId) {
            const activeButton = document.getElementById(buttonId);
            if (activeButton) {
                activeButton.classList.remove('text-slate-300', 'hover:bg-slate-700', 'hover:text-white');
                activeButton.classList.add('bg-indigo-600', 'text-white');
            }
        }
    }

    /**
     * Load page-specific data when navigating to a view
     * @param {string} viewId - The view ID
     */
    async loadPageData(viewId) {
        try {
            switch (viewId) {
                case 'orders-page':
                    if (typeof window.fetchOrders === 'function') {
                        await window.fetchOrders();
                    }
                    break;
                case 'reports-page':
                    if (typeof window.fetchReportsData === 'function') {
                        await window.fetchReportsData();
                    }
                    break;
                case 'sessions-page':
                    if (typeof window.fetchSessions === 'function') {
                        await window.fetchSessions();
                    }
                    break;
                case 'products-page':
                    if (typeof window.renderStockList === 'function') {
                        window.renderStockList();
                    }
                    break;
                case 'held-carts-page':
                    if (typeof window.renderHeldCarts === 'function') {
                        window.renderHeldCarts();
                    }
                    break;
                case 'settings-page':
                    if (typeof window.populateSettingsForm === 'function') {
                        window.populateSettingsForm();
                    }
                    break;
                case 'pos-page':
                default:
                    // POS page doesn't need special data loading
                    break;
            }
        } catch (error) {
            console.error(`Error loading data for ${viewId}:`, error);
        }
    }

    /**
     * Toggle side menu
     */
    toggleMenu() {
        const sideMenu = document.getElementById('side-menu');
        const menuOverlay = document.getElementById('menu-overlay');
        
        if (sideMenu) {
            sideMenu.classList.toggle('is-open');
        }
        
        if (menuOverlay) {
            menuOverlay.classList.toggle('hidden');
        }
    }

    /**
     * Get current view
     * @returns {string} Current view ID
     */
    getCurrentView() {
        return this.currentView;
    }

    /**
     * Check if a view is valid
     * @param {string} viewId - View ID to check
     * @returns {boolean} Whether the view is valid
     */
    isValidView(viewId) {
        return this.validViews.includes(viewId);
    }
}

// Export for use in other modules
window.RoutingManager = RoutingManager;
