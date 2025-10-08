// WP POS v1.9.0 - Settings Management Module
// Handles store settings, receipt configuration, and keyboard preferences

class SettingsManager {
    constructor(state, uiHelpers) {
        this.state = state;
        this.ui = uiHelpers;
    }

    /**
     * Load receipt settings from API
     * @returns {Promise<void>}
     */
    async loadReceiptSettings() {
        try {
            const response = await fetch('api/settings.php');
            if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
            
            const result = await response.json();
            if (result.success) {
                this.state.updateState('settings', result.data);
                
                // Initialize keyboard auto-show based on loaded settings
                if (window.initKeyboardAutoShow) {
                    window.initKeyboardAutoShow();
                }
            } else {
                throw new Error(result.message || 'Failed to parse settings.');
            }
        } catch (error) {
            console.error("Could not load receipt settings.", error);
            this.state.updateState('settings', {
                name: "Store Name",
                email: "",
                phone: "",
                address: "",
                footer_message_1: "Thank you!",
                footer_message_2: "",
                virtual_keyboard_enabled: true,
                virtual_keyboard_auto_show: false
            });
            alert('Warning: Could not load store settings. Receipts may display default info.');
            
            // Initialize with defaults
            if (window.initKeyboardAutoShow) {
                window.initKeyboardAutoShow();
            }
        }
    }

    /**
     * Populate settings form with current values
     */
    async populateSettingsForm() {
        // Reload settings to ensure we have the latest data
        await this.loadReceiptSettings();
        
        // Get settings from state
        let currentSettings = this.state.getState('settings') || {};
        
        // Handle case where settings might be wrapped in API response format
        if (currentSettings.data) {
            currentSettings = currentSettings.data;
        }
        
        // Populate form fields
        const fields = {
            'setting-name': currentSettings.name || '',
            'setting-logo-url': currentSettings.logo_url || '',
            'setting-email': currentSettings.email || '',
            'setting-phone': currentSettings.phone || '',
            'setting-address': currentSettings.address || '',
            'setting-footer1': currentSettings.footer_message_1 || '',
            'setting-footer2': currentSettings.footer_message_2 || ''
        };
        
        // Set field values safely
        Object.entries(fields).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) {
                element.value = value;
            } else {
                console.warn(`Settings form element not found: ${id}`);
            }
        });
        
        // Virtual keyboard settings
        const enableKeyboard = document.getElementById('setting-keyboard-enabled');
        const autoShowKeyboard = document.getElementById('setting-keyboard-auto-show');
        
        if (enableKeyboard) {
            enableKeyboard.checked = currentSettings.virtual_keyboard_enabled !== false; // Default to true
        }
        if (autoShowKeyboard) {
            autoShowKeyboard.checked = currentSettings.virtual_keyboard_auto_show === true;
        }
        
        // Initialize keyboard auto-show based on settings
        if (window.initKeyboardAutoShow) {
            window.initKeyboardAutoShow();
        }
        
        // Initialize settings tabs
        this.initSettingsTabs();
        
        // Check roles status if on roles tab
        this.checkRolesStatus();
    }
    
    /**
     * Check if POS roles exist and update UI
     */
    async checkRolesStatus() {
        const container = document.getElementById('roles-status-container');
        if (!container) return; // Not on roles tab
        
        try {
            const response = await fetch('api/wp-roles-setup.php?action=check');
            
            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                this.updateRolesUI(result.data);
            } else {
                throw new Error(result.data?.message || 'Failed to check roles');
            }
        } catch (error) {
            console.error('Error checking roles status:', error);
            
            // Show error message in UI
            container.innerHTML = `
                <div class="text-center space-y-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-900/20 border border-red-700/30 rounded-full">
                        <i class="fas fa-exclamation-circle text-red-400 text-2xl"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-white mb-2">Error Loading Roles</p>
                        <p class="text-sm text-slate-400 mb-4">${error.message}</p>
                        <button onclick="settingsManager.checkRolesStatus()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition-colors">
                            <i class="fas fa-redo mr-2"></i>Try Again
                        </button>
                    </div>
                </div>
            `;
        }
    }
    
    /**
     * Update roles UI based on status
     * @param {Object} data Role status data
     */
    updateRolesUI(data) {
        const container = document.getElementById('roles-status-container');
        const rolesInfo = document.getElementById('roles-info');
        
        if (!container) return;
        
        if (data.roles_exist) {
            // Roles exist - show status cards
            container.innerHTML = `
                <div class="flex items-center justify-between p-4 bg-green-900/20 border border-green-700/30 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-400 text-2xl mr-3"></i>
                        <div>
                            <p class="font-semibold text-white">POS Roles Installed</p>
                            <p class="text-sm text-slate-400">All POS roles and capabilities are active</p>
                        </div>
                    </div>
                    <button onclick="settingsManager.reinstallRoles()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>Reinstall
                    </button>
                </div>
            `;
            
            if (rolesInfo) {
                rolesInfo.classList.remove('hidden');
                
                // Update individual role statuses
                Object.entries(data.details).forEach(([role, exists]) => {
                    const statusEl = document.getElementById(`role-status-${role.replace('jpos_', '')}`);
                    if (statusEl) {
                        if (exists) {
                            statusEl.className = 'text-xs px-2 py-1 rounded bg-green-900/30 text-green-400';
                            statusEl.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Active';
                        } else {
                            statusEl.className = 'text-xs px-2 py-1 rounded bg-red-900/30 text-red-400';
                            statusEl.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Missing';
                        }
                    }
                });
            }
        } else {
            // Roles don't exist - show setup button
            container.innerHTML = `
                <div class="text-center space-y-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-900/20 border border-yellow-700/30 rounded-full">
                        <i class="fas fa-exclamation-triangle text-yellow-400 text-2xl"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-white mb-2">POS Roles Not Installed</p>
                        <p class="text-sm text-slate-400 mb-4">Install roles to enable access control and user management</p>
                        <button onclick="settingsManager.setupRoles()" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition-colors">
                            <i class="fas fa-plus-circle mr-2"></i>Install POS Roles
                        </button>
                    </div>
                </div>
            `;
            
            if (rolesInfo) {
                rolesInfo.classList.add('hidden');
            }
        }
    }
    
    /**
     * Setup POS roles
     */
    async setupRoles() {
        const container = document.getElementById('roles-status-container');
        
        // Show loading
        container.innerHTML = `
            <div class="flex items-center justify-center py-8">
                <i class="fas fa-spinner fa-spin text-3xl text-indigo-400 mr-3"></i>
                <span class="text-white">Installing POS roles...</span>
            </div>
        `;
        
        try {
            const response = await fetch('api/wp-roles-setup.php?action=setup');
            const result = await response.json();
            
            if (result.success) {
                this.ui.showToast('POS roles installed successfully!', 'success');
                
                // Refresh status
                setTimeout(() => {
                    this.checkRolesStatus();
                }, 500);
            } else {
                throw new Error(result.data?.message || 'Failed to install roles');
            }
        } catch (error) {
            console.error('Error setting up roles:', error);
            container.innerHTML = `
                <div class="text-center space-y-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-900/20 border border-red-700/30 rounded-full">
                        <i class="fas fa-times-circle text-red-400 text-2xl"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-white mb-2">Installation Failed</p>
                        <p class="text-sm text-slate-400 mb-4">${error.message}</p>
                        <button onclick="settingsManager.setupRoles()" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition-colors">
                            <i class="fas fa-redo mr-2"></i>Try Again
                        </button>
                    </div>
                </div>
            `;
        }
    }
    
    /**
     * Reinstall POS roles
     */
    async reinstallRoles() {
        if (!confirm('This will reinstall all POS roles and capabilities. Existing role assignments will be preserved. Continue?')) {
            return;
        }
        
        await this.setupRoles();
    }

    /**
     * Initialize settings tabs navigation
     * @private
     */
    initSettingsTabs() {
        const tabs = document.querySelectorAll('.settings-tab');
        const panels = document.querySelectorAll('.settings-panel');
        
        if (!tabs.length || !panels.length) return;
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Get the tab name from the ID (e.g., 'settings-tab-receipt' -> 'receipt')
                const tabName = tab.id.replace('settings-tab-', '');
                
                // Update active tab styling
                tabs.forEach(t => {
                    t.classList.remove('border-indigo-500', 'text-indigo-400', 'bg-slate-700/50');
                    t.classList.add('border-transparent', 'text-slate-400');
                });
                tab.classList.remove('border-transparent', 'text-slate-400');
                tab.classList.add('border-indigo-500', 'text-indigo-400', 'bg-slate-700/50');
                
                // Show corresponding panel
                panels.forEach(panel => {
                    panel.classList.add('hidden');
                });
                const activePanel = document.getElementById(`settings-panel-${tabName}`);
                if (activePanel) {
                    activePanel.classList.remove('hidden');
                }
            });
        });
    }

    /**
     * Save settings to API
     * @param {Event} event - Button click event
     * @returns {Promise<void>}
     */
    async saveSettings(event) {
        if (event) event.preventDefault();
        
        const statusEl = document.getElementById('settings-status');
        const saveBtn = document.getElementById('save-settings-btn');
        saveBtn.disabled = true;
        statusEl.textContent = 'Saving...';
        statusEl.className = 'text-sm text-slate-400';
        
        // Get virtual keyboard settings (using correct IDs from HTML)
        const enableKeyboard = document.getElementById('setting-keyboard-enabled');
        const autoShowKeyboard = document.getElementById('setting-keyboard-auto-show');
        
        const data = {
            name: document.getElementById('setting-name').value,
            logo_url: document.getElementById('setting-logo-url').value,
            email: document.getElementById('setting-email').value,
            phone: document.getElementById('setting-phone').value,
            address: document.getElementById('setting-address').value,
            footer_message_1: document.getElementById('setting-footer1').value,
            footer_message_2: document.getElementById('setting-footer2').value,
            virtual_keyboard_enabled: enableKeyboard ? enableKeyboard.checked : true,
            virtual_keyboard_auto_show: autoShowKeyboard ? autoShowKeyboard.checked : false,
            nonce: this.state.getState('nonces.settings')
        };
        
        try {
            const response = await fetch('api/settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            
            if (!result.success) throw new Error(result.data.message || 'Failed to save settings.');
            
            const apiMessage = result.data.message || 'Settings saved successfully!';
            statusEl.textContent = apiMessage;
            statusEl.className = 'ml-4 text-sm text-green-400';
            
            // Update appState with keyboard settings
            const currentSettings = this.state.getState('settings') || {};
            currentSettings.virtual_keyboard_enabled = data.virtual_keyboard_enabled;
            currentSettings.virtual_keyboard_auto_show = data.virtual_keyboard_auto_show;
            this.state.updateState('settings', currentSettings);
            
            await this.loadReceiptSettings();
            
            // Re-initialize keyboard auto-show with new settings
            if (window.initKeyboardAutoShow) {
                window.initKeyboardAutoShow();
            }
            
            this.ui.showToast(apiMessage);
        } catch (error) {
            console.error("Error saving settings:", error);
            statusEl.textContent = `Error: ${error.message}`;
            statusEl.className = 'ml-4 text-sm text-red-400';
            this.ui.showToast('Failed to save settings');
        } finally {
            saveBtn.disabled = false;
            setTimeout(() => { statusEl.textContent = ''; }, 5000);
        }
    }
}

// Export class
window.SettingsManager = SettingsManager;

// Create instance and expose methods globally for routing system
// These will be properly initialized in main.js after StateManager is ready
window.populateSettingsForm = null;
window.saveSettings = null;