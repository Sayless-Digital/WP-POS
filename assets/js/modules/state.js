/**
 * JPOS State Management Module
 * Handles centralized state management with validation and utilities
 */

class StateManager {
    constructor() {
        this.state = {
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
                customer: null, // { id, name, email }
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
    }

    /**
     * Update state using dot notation path
     * @param {string} path - Dot notation path (e.g., 'cart.items')
     * @param {*} value - Value to set
     * @returns {*} The updated value
     */
    updateState(path, value) {
        const keys = path.split('.');
        let current = this.state;
        
        for (let i = 0; i < keys.length - 1; i++) {
            if (!current[keys[i]]) {
                current[keys[i]] = {};
            }
            current = current[keys[i]];
        }
        
        current[keys[keys.length - 1]] = value;
        return current[keys[keys.length - 1]];
    }
    
    /**
     * Get state value using dot notation path
     * @param {string} path - Dot notation path (e.g., 'cart.items')
     * @returns {*} The state value
     */
    getState(path) {
        const keys = path.split('.');
        let current = this.state;
        
        for (const key of keys) {
            if (current && typeof current === 'object' && key in current) {
                current = current[key];
            } else {
                return undefined;
            }
        }
        
        return current;
    }
    
    /**
     * Validate state consistency
     */
    validateState() {
        // Validate critical state consistency
        if (this.state.auth.isLoggedIn && !this.state.auth.user) {
            console.warn('State inconsistency: isLoggedIn is true but user is null');
            this.state.auth.isLoggedIn = false;
        }
        
        if (this.state.drawer.isOpen && !this.state.drawer.data) {
            console.warn('State inconsistency: drawer is open but data is null');
        }
        
        // Validate cart consistency
        if (this.state.cart.items && !Array.isArray(this.state.cart.items)) {
            console.warn('State inconsistency: cart.items should be an array');
            this.state.cart.items = [];
        }
    }
    
    /**
     * Reset state to initial values
     */
    resetState() {
        this.state.auth = { user: null, isLoggedIn: false };
        this.state.drawer = { isOpen: false, data: null, openingAmount: 0, closingAmount: 0 };
        this.state.cart.items = [];
        this.state.cart.customer = null;
        this.state.cart.paymentMethod = 'Cash';
        this.state.cart.fee = { amount: '', label: '', amountType: 'flat' };
        this.state.cart.discount = { amount: '', label: '', amountType: 'flat' };
        this.state.cart.feeDiscount = { type: null, amount: '', label: '', amountType: 'flat' };
        this.state.cart.splitPayments = null;
        this.state.returns.fromOrderId = null;
        this.state.returns.items = [];
        this.state.ui.error = null;
    }

    /**
     * Get the entire state object
     * @returns {Object} The complete state
     */
    getFullState() {
        return this.state;
    }

    /**
     * Subscribe to state changes
     * @param {string} path - Path to watch
     * @param {Function} callback - Callback function
     */
    subscribe(path, callback) {
        // Simple subscription mechanism
        // In a more advanced implementation, this would use a proper event system
        this._subscribers = this._subscribers || {};
        this._subscribers[path] = this._subscribers[path] || [];
        this._subscribers[path].push(callback);
    }

    /**
     * Notify subscribers of state changes
     * @param {string} path - Path that changed
     * @param {*} value - New value
     */
    notify(path, value) {
        if (this._subscribers && this._subscribers[path]) {
            this._subscribers[path].forEach(callback => callback(value));
        }
    }
}

// Create global instance
window.stateManager = new StateManager();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StateManager;
}

