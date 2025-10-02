/**
 * JPOS Authentication Module
 * Handles user authentication, login/logout, and session management
 */

class AuthManager {
    constructor(stateManager) {
        this.stateManager = stateManager;
    }

    /**
     * Initialize authentication system
     */
    async init() {
        await this.generateNonces(); // Generate nonces immediately for login form
        await this.checkAuthStatus();
    }

    /**
     * Check current authentication status
     */
    async checkAuthStatus() {
        try {
            const response = await fetch('/jpos/api/auth.php?action=check_status');
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            
            // Handle WordPress wp_send_json_success response structure
            const loggedIn = result.data?.loggedIn || result.loggedIn;
            const userData = result.data?.user || result.user;
            
            if (result.success && loggedIn && userData) {
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
            const response = await fetch('/jpos/api/auth.php', { 
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
                    this.stateManager.updateState('auth.user', userData);
                    this.stateManager.updateState('auth.isLoggedIn', true);
                    form.reset();
                    return true;
                } else {
                    this.showLoginScreen(true, 'Login successful but user data not received.');
                    return false;
                }
            } else {
                this.showLoginScreen(true, result.message || 'Login failed.');
                return false;
            }
        } catch (error) {
            console.error("Login error details:", error);
            this.showLoginScreen(true, `Network error during login. Please try again.`);
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
            return; 
        }
        
        try {
            await fetch('/jpos/api/auth.php?action=logout&nonce=' + 
                encodeURIComponent(this.stateManager.getState('nonces.logout')));
            this.stateManager.resetState();
            this.showLoginScreen(true);
            return true;
        } catch (error) {
            console.error('Logout error:', error);
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
                refund: document.getElementById('jpos-refund-nonce')?.value || ''
            };
            
            this.stateManager.updateState('nonces', nonces);
        } catch (error) {
            console.error('Error generating nonces:', error);
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
        this.stateManager.validateState();
        
        // Trigger app initialization
        if (window.appInitializer) {
            await window.appInitializer.init();
        }
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AuthManager;
}

