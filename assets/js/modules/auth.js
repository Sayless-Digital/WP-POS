/**
 * JPOS Authentication Module v1.9.0
 * Handles user authentication, login/logout, and session management
 * Enhanced for modular architecture with UI helpers integration
 */

class AuthManager {
    constructor(stateManager, uiHelpers) {
        this.stateManager = stateManager;
        this.ui = uiHelpers;
    }

    /**
     * Initialize authentication system
     */
    async init() {
        // Setup event listeners first
        this.setupEventListeners();
        
        await this.generateNonces(); // Generate nonces immediately for login form
        const isLoggedIn = await this.checkAuthStatus();
        
        if (isLoggedIn) {
            await this.loadFullApp();
        } else {
            this.showLoginScreen(true, "");
        }
        
        // Hide preloader after initialization
        this.hideAppPreloader();
    }

    /**
     * Check current authentication status
     */
    async checkAuthStatus() {
        try {
            const response = await fetch('/wp-pos/api/auth.php?action=check_status');
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            
            // Handle WordPress wp_send_json_success response structure
            const loggedIn = result.data?.loggedIn || result.loggedIn;
            const userData = result.data?.user || result.user;
            
            if (result.success && loggedIn && userData) {
                console.log('Auth check - User data received:', userData);
                console.log('Auth check - User roles:', userData.roles);
                this.stateManager.updateState('auth.user', userData);
                this.stateManager.updateState('auth.isLoggedIn', true);
                return true;
            } else {
                this.stateManager.updateState('auth.user', null);
                this.stateManager.updateState('auth.isLoggedIn', false);
                return false;
            }
        } catch (error) {
            console.error('Auth status check failed:', error);
            this.stateManager.updateState('auth.user', null);
            this.stateManager.updateState('auth.isLoggedIn', false);
            return false;
        }
    }
    
    /**
     * Hide app preloader
     */
    hideAppPreloader() {
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
    
    /**
     * Setup event listeners for login/logout
     */
    setupEventListeners() {
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
        
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', () => this.handleLogout());
        }
    }

    /**
     * Handle user login
     * @param {Event} e - Form submit event
     */
    async handleLogin(e) {
        e.preventDefault();
        const form = e.target;
        const button = form.querySelector('button');
        button.disabled = true;
        document.getElementById('login-error').textContent = '';
        
        const data = { 
            action: 'login', 
            username: form.username.value, 
            password: form.password.value, 
            nonce: this.stateManager.getState('nonces.login') 
        };
        
        try {
            const response = await fetch('/wp-pos/api/auth.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify(data) 
            });
            const responseText = await response.text();
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}, body: ${responseText}`);
            const result = JSON.parse(responseText);
            
            if (result.success) {
                // Handle WordPress wp_send_json_success response structure
                const userData = result.data?.user || result.user;
                if (userData) {
                    console.log('Login - User data received:', userData);
                    console.log('Login - User roles:', userData.roles);
                    this.stateManager.updateState('auth.user', userData);
                    this.stateManager.updateState('auth.isLoggedIn', true);
                    form.reset();
                    await this.loadFullApp();
                    return true;
                } else {
                    this.showLoginScreen(true, 'Login successful but user data not received.');
                    if (this.ui) this.ui.showToast('Login failed: No user data');
                    return false;
                }
            } else {
                const errorMsg = result.message || result.data?.message || 'Login failed.';
                this.showLoginScreen(true, errorMsg);
                if (this.ui) this.ui.showToast(errorMsg);
                return false;
            }
        } catch (error) {
            console.error("Login error details:", error);
            const errorMsg = 'Network error during login. Please try again.';
            this.showLoginScreen(true, errorMsg);
            if (this.ui) this.ui.showToast(errorMsg);
            return false;
        } finally {
            button.disabled = false;
        }
    }
    
    /**
     * Handle user logout
     */
    async handleLogout() {
        if (this.stateManager.getState('drawer.isOpen')) {
            alert("Please close the cash drawer before logging out.");
            if (this.ui) this.ui.showToast('Close drawer before logout');
            return false;
        }
        
        try {
            await fetch('/wp-pos/api/auth.php?action=logout&nonce=' +
                encodeURIComponent(this.stateManager.getState('nonces.logout')));
            this.stateManager.resetState();
            this.showLoginScreen(true);
            if (this.ui) this.ui.showToast('Logged out successfully');
            
            // Update drawer UI if available
            if (window.drawerManager) {
                window.drawerManager.updateDrawerUI();
            }
            return true;
        } catch (error) {
            console.error('Logout error:', error);
            if (this.ui) this.ui.showToast('Logout failed');
            return false;
        }
    }

    /**
     * Show/hide login screen
     * @param {boolean} show - Whether to show login screen
     * @param {string} message - Error message to display
     */
    showLoginScreen(show, message = '') {
        const loginScreen = document.getElementById('login-screen');
        const mainApp = document.getElementById('main-app');
        const errorElement = document.getElementById('login-error');
        
        if (show) {
            loginScreen.classList.remove('hidden');
            mainApp.classList.add('hidden');
            if (message) {
                errorElement.textContent = message;
            }
        } else {
            loginScreen.classList.add('hidden');
            mainApp.classList.remove('hidden');
            errorElement.textContent = '';
        }
    }

    /**
     * Generate and store nonces for CSRF protection
     */
    async generateNonces() {
        try {
            const nonces = {
                login: document.getElementById('jpos-login-nonce')?.value || '',
                logout: document.getElementById('jpos-logout-nonce')?.value || '',
                checkout: document.getElementById('jpos-checkout-nonce')?.value || '',
                settings: document.getElementById('jpos-settings-nonce')?.value || '',
                drawer: document.getElementById('jpos-drawer-nonce')?.value || '',
                stock: document.getElementById('jpos-stock-nonce')?.value || '',
                refund: document.getElementById('jpos-refund-nonce')?.value || '',
                productEdit: document.getElementById('jpos-product-edit-nonce')?.value || '',
                reports: document.getElementById('jpos-reports-nonce')?.value || '',
                barcode: document.getElementById('jpos-barcode-nonce')?.value || ''
            };
            
            this.stateManager.updateState('nonces', nonces);
        } catch (error) {
            console.error('Error generating nonces:', error);
            if (this.ui) this.ui.showToast('Failed to generate security tokens');
        }
    }

    /**
     * Check if user is authenticated
     * @returns {boolean} True if user is logged in
     */
    isAuthenticated() {
        return this.stateManager.getState('auth.isLoggedIn') === true;
    }

    /**
     * Get current user
     * @returns {Object|null} Current user object or null
     */
    getCurrentUser() {
        return this.stateManager.getState('auth.user');
    }

    /**
     * Load full application after successful login
     */
    async loadFullApp() {
        this.showLoginScreen(false);
        document.getElementById('main-app').classList.remove('hidden');
        
        // Update user profile information safely
        const userDisplayName = document.getElementById('user-display-name'); // Side menu
        const headerUserDisplayName = document.getElementById('header-user-display-name'); // Header
        const userEmail = document.getElementById('user-email');
        
        const displayName = this.stateManager.getState('auth.user.displayName') || 'User';
        const emailValue = this.stateManager.getState('auth.user.email');
        
        if (userDisplayName) {
            userDisplayName.textContent = displayName;
        }
        if (headerUserDisplayName) {
            headerUserDisplayName.textContent = displayName;
        }
        if (userEmail) {
            userEmail.textContent = emailValue || 'No email';
        }
        
        await this.generateNonces();
        
        // Load settings and initialize other managers
        if (window.settingsManager) {
            await window.settingsManager.loadReceiptSettings();
        }
        
        // Fetch product data and render
        if (window.productsManager) {
            await window.productsManager.fetchProducts();
            window.productsManager.renderProductGrid();
        }
        
        // Initialize routing to current view
        if (window.routingManager) {
            const initialView = window.routingManager.getCurrentView();
            window.routingManager.navigateToView(initialView, false);
        }
        
        // Check drawer status
        if (window.drawerManager) {
            await window.drawerManager.checkDrawerStatus();
        }
        
        // Load and render cart state
        if (window.cartManager) {
            window.cartManager.loadCartState();
            window.cartManager.renderCart();
        }
        
        // Setup all button event listeners (must be called after app is loaded)
        if (window.setupCartEventListeners) {
            window.setupCartEventListeners();
        }
        if (window.setupAllEventListeners) {
            window.setupAllEventListeners();
        }
        
        this.stateManager.validateState();
    }

    /**
     * Update user display information in UI
     * @private
     */
    updateUserDisplay() {
        const userDisplayName = document.getElementById('user-display-name');
        const headerUserDisplayName = document.getElementById('header-user-display-name');
        const userEmail = document.getElementById('user-email');
        
        const displayName = this.stateManager.getState('auth.user.displayName') || 'User';
        const emailValue = this.stateManager.getState('auth.user.email');
        
        if (userDisplayName) userDisplayName.textContent = displayName;
        if (headerUserDisplayName) headerUserDisplayName.textContent = displayName;
        if (userEmail) userEmail.textContent = emailValue || 'No email';
    }

    /**
     * Validate authentication state consistency
     * @returns {boolean} True if state is valid
     */
    validateAuthState() {
        const isLoggedIn = this.stateManager.getState('auth.isLoggedIn');
        const user = this.stateManager.getState('auth.user');
        
        if (isLoggedIn && !user) {
            console.warn('Auth state inconsistency: isLoggedIn is true but user is null');
            this.stateManager.updateState('auth.isLoggedIn', false);
            return false;
        }
        
        return true;
    }

    /**
     * Check if current user has a specific capability
     * @param {string} capability - The capability to check (e.g., 'wppos_view_reports')
     * @returns {boolean} True if user has the capability
     */
    userCan(capability) {
        const capabilities = this.stateManager.getState('auth.user.capabilities') || [];
        const roles = this.stateManager.getState('auth.user.roles') || {};
        
        // Convert roles to array if it's an object
        const rolesArray = Array.isArray(roles) ? roles : Object.keys(roles);
        
        // Administrators and shop managers have all capabilities
        if (rolesArray.includes('administrator') || rolesArray.includes('shop_manager')) {
            return true;
        }
        
        return capabilities.includes(capability);
    }

    /**
     * Check if current user has any of the specified capabilities
     * @param {Array<string>} capabilities - Array of capabilities to check
     * @returns {boolean} True if user has at least one capability
     */
    userCanAny(capabilities) {
        return capabilities.some(cap => this.userCan(cap));
    }

    /**
     * Check if current user has all of the specified capabilities
     * @param {Array<string>} capabilities - Array of capabilities to check
     * @returns {boolean} True if user has all capabilities
     */
    userCanAll(capabilities) {
        return capabilities.every(cap => this.userCan(cap));
    }

    /**
     * Get current user's capabilities
     * @returns {Array<string>} Array of capability slugs
     */
    getUserCapabilities() {
        return this.stateManager.getState('auth.user.capabilities') || [];
    }

    /**
     * Get current user's roles
     * @returns {Array<string>} Array of role slugs
     */
    getUserRoles() {
        const roles = this.stateManager.getState('auth.user.roles') || {};
        // Return as-is for compatibility - callers should handle both array and object
        return roles;
    }

    /**
     * Check if user has a specific role
     * @param {string} role - The role slug to check
     * @returns {boolean} True if user has the role
     */
    hasRole(role) {
        const roles = this.getUserRoles();
        // Handle both array and object formats
        if (Array.isArray(roles)) {
            return roles.includes(role);
        } else if (typeof roles === 'object') {
            return role in roles;
        }
        return false;
    }
}

// Export as singleton for global access
window.AuthManager = AuthManager;

// Export global capability checking functions for easy access
window.userCan = (capability) => {
    return window.authManager ? window.authManager.userCan(capability) : false;
};

window.userCanAny = (capabilities) => {
    return window.authManager ? window.authManager.userCanAny(capabilities) : false;
};

window.userCanAll = (capabilities) => {
    return window.authManager ? window.authManager.userCanAll(capabilities) : false;
};

window.hasRole = (role) => {
    return window.authManager ? window.authManager.hasRole(role) : false;
};

// Export for module usage (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthManager;
}

