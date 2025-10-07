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
            const response = await fetch('/jpos/api/settings.php');
            if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
            
            const result = await response.json();
            if (result.success) {
                this.state.settings = result.data;
                
                // Initialize keyboard auto-show based on loaded settings
                if (window.initKeyboardAutoShow) {
                    window.initKeyboardAutoShow();
                }
            } else {
                throw new Error(result.message || 'Failed to parse settings.');
            }
        } catch (error) {
            console.error("Could not load receipt settings.", error);
            this.state.settings = {
                name: "Store Name",
                email: "",
                phone: "",
                address: "",
                footer_message_1: "Thank you!",
                footer_message_2: "",
                virtual_keyboard_enabled: true,
                virtual_keyboard_auto_show: false
            };
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
    populateSettingsForm() {
        document.getElementById('setting-name').value = this.state.settings.name || '';
        document.getElementById('setting-logo-url').value = this.state.settings.logo_url || '';
        document.getElementById('setting-email').value = this.state.settings.email || '';
        document.getElementById('setting-phone').value = this.state.settings.phone || '';
        document.getElementById('setting-address').value = this.state.settings.address || '';
        document.getElementById('setting-footer1').value = this.state.settings.footer_message_1 || '';
        document.getElementById('setting-footer2').value = this.state.settings.footer_message_2 || '';
        
        // Virtual keyboard settings
        const enableKeyboard = document.getElementById('setting-keyboard-enabled');
        const autoShowKeyboard = document.getElementById('setting-keyboard-auto-show');
        
        if (enableKeyboard) {
            enableKeyboard.checked = this.state.settings.virtual_keyboard_enabled !== false; // Default to true
        }
        if (autoShowKeyboard) {
            autoShowKeyboard.checked = this.state.settings.virtual_keyboard_auto_show === true;
        }
        
        // Initialize keyboard auto-show based on settings
        if (window.initKeyboardAutoShow) {
            window.initKeyboardAutoShow();
        }
        
        // Initialize settings tabs
        this.initSettingsTabs();
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
     * @param {Event} event - Form submit event
     * @returns {Promise<void>}
     */
    async saveSettings(event) {
        event.preventDefault();
        
        const statusEl = document.getElementById('settings-status');
        const saveBtn = event.target.querySelector('button[type="submit"]');
        saveBtn.disabled = true;
        statusEl.textContent = 'Saving...';
        statusEl.className = 'ml-4 text-sm text-slate-400';
        
        // Get virtual keyboard settings
        const enableKeyboard = document.getElementById('enable-virtual-keyboard');
        const autoShowKeyboard = document.getElementById('auto-show-keyboard');
        
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
            nonce: this.state.nonces.settings
        };
        
        try {
            const response = await fetch('/jpos/api/settings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            
            if (!result.success) throw new Error(result.data.message || 'Failed to save settings.');
            
            statusEl.textContent = result.data.message || 'Settings saved successfully!';
            statusEl.className = 'ml-4 text-sm text-green-400';
            
            // Update appState with keyboard settings
            this.state.settings.virtual_keyboard_enabled = data.virtual_keyboard_enabled;
            this.state.settings.virtual_keyboard_auto_show = data.virtual_keyboard_auto_show;
            
            await this.loadReceiptSettings();
            
            // Re-initialize keyboard auto-show with new settings
            if (window.initKeyboardAutoShow) {
                window.initKeyboardAutoShow();
            }
            
            this.ui.showToast('Settings saved successfully!');
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

// Export as singleton
window.SettingsManager = SettingsManager;