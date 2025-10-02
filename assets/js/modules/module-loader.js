/**
 * JPOS Module Loader
 * Handles loading and initializing all modules
 */

class ModuleLoader {
    constructor() {
        this.modules = {};
        this.stateManager = null;
    }

    /**
     * Initialize all modules
     */
    async init() {
        try {
            // Initialize state manager first
            this.stateManager = window.stateManager;
            if (!this.stateManager) {
                throw new Error('State manager not found');
            }

            // Initialize modules
            await this.initAuthModule();
            await this.initProductsModule();
            await this.initCartModule();
            await this.initDrawerModule();
            await this.initOrdersModule();
            await this.initReportsModule();
            await this.initSettingsModule();
            await this.initUtilsModule();

            // Set up global references
            window.productsManager = this.modules.products;
            window.cartManager = this.modules.cart;
            window.authManager = this.modules.auth;
            window.drawerManager = this.modules.drawer;
            window.ordersManager = this.modules.orders;
            window.reportsManager = this.modules.reports;
            window.settingsManager = this.modules.settings;
            window.utilsManager = this.modules.utils;

            console.log('All modules loaded successfully');
            return true;
        } catch (error) {
            console.error('Failed to initialize modules:', error);
            return false;
        }
    }

    /**
     * Initialize authentication module
     */
    async initAuthModule() {
        if (typeof AuthManager !== 'undefined') {
            this.modules.auth = new AuthManager(this.stateManager);
            await this.modules.auth.init();
        } else {
            console.error('AuthManager class not found');
        }
    }

    /**
     * Initialize products module
     */
    async initProductsModule() {
        if (typeof ProductsManager !== 'undefined') {
            this.modules.products = new ProductsManager(this.stateManager);
        } else {
            console.error('ProductsManager class not found');
        }
    }

    /**
     * Initialize cart module
     */
    async initCartModule() {
        if (typeof CartManager !== 'undefined') {
            this.modules.cart = new CartManager(this.stateManager);
            this.modules.cart.loadCartState();
        } else {
            console.error('CartManager class not found');
        }
    }

    /**
     * Initialize drawer module
     */
    async initDrawerModule() {
        if (typeof DrawerManager !== 'undefined') {
            this.modules.drawer = new DrawerManager(this.stateManager);
        } else {
            console.error('DrawerManager class not found');
        }
    }

    /**
     * Initialize orders module
     */
    async initOrdersModule() {
        if (typeof OrdersManager !== 'undefined') {
            this.modules.orders = new OrdersManager(this.stateManager);
        } else {
            console.error('OrdersManager class not found');
        }
    }

    /**
     * Initialize reports module
     */
    async initReportsModule() {
        if (typeof ReportsManager !== 'undefined') {
            this.modules.reports = new ReportsManager(this.stateManager);
        } else {
            console.error('ReportsManager class not found');
        }
    }

    /**
     * Initialize settings module
     */
    async initSettingsModule() {
        if (typeof SettingsManager !== 'undefined') {
            this.modules.settings = new SettingsManager(this.stateManager);
        } else {
            console.error('SettingsManager class not found');
        }
    }

    /**
     * Initialize utilities module
     */
    async initUtilsModule() {
        if (typeof UtilsManager !== 'undefined') {
            this.modules.utils = new UtilsManager(this.stateManager);
        } else {
            console.error('UtilsManager class not found');
        }
    }


    /**
     * Get module by name
     * @param {string} name - Module name
     * @returns {Object|null} Module instance
     */
    getModule(name) {
        return this.modules[name] || null;
    }

    /**
     * Get all modules
     * @returns {Object} All modules
     */
    getAllModules() {
        return this.modules;
    }
}

// Create global instance
window.moduleLoader = new ModuleLoader();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModuleLoader;
}

