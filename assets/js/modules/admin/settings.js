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
                virtual_keyboard_auto_show: false,
                ui_scale: 100
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
        
        // UI scale setting
        const uiScaleSlider = document.getElementById('setting-ui-scale');
        const uiScaleValue = document.getElementById('ui-scale-value');
        const uiScaleDecrease = document.getElementById('ui-scale-decrease');
        const uiScaleIncrease = document.getElementById('ui-scale-increase');
        
        if (uiScaleSlider && uiScaleValue) {
            const scale = currentSettings.ui_scale || 100;
            uiScaleSlider.value = scale;
            uiScaleValue.textContent = `${scale}%`;
            
            // Apply scale immediately
            this.applyUIScale(scale);
            
            // Update value display and apply scale as slider moves
            uiScaleSlider.addEventListener('input', (e) => {
                const newScale = parseInt(e.target.value);
                uiScaleValue.textContent = `${newScale}%`;
                this.applyUIScale(newScale);
            });
            
            // Decrease button - reduce by 5%
            if (uiScaleDecrease) {
                uiScaleDecrease.addEventListener('click', () => {
                    const currentValue = parseInt(uiScaleSlider.value);
                    const newValue = Math.max(50, currentValue - 5);
                    uiScaleSlider.value = newValue;
                    uiScaleValue.textContent = `${newValue}%`;
                    this.applyUIScale(newValue);
                });
            }
            
            // Increase button - increase by 5%
            if (uiScaleIncrease) {
                uiScaleIncrease.addEventListener('click', () => {
                    const currentValue = parseInt(uiScaleSlider.value);
                    const newValue = Math.min(150, currentValue + 5);
                    uiScaleSlider.value = newValue;
                    uiScaleValue.textContent = `${newValue}%`;
                    this.applyUIScale(newValue);
                });
            }
        }
        
        // Initialize keyboard auto-show based on settings
        if (window.initKeyboardAutoShow) {
            window.initKeyboardAutoShow();
        }
        
        // Initialize settings tabs
        this.initSettingsTabs();
        
        // Load roles list
        await this.loadRolesList();
        
        // Check predefined roles status
        this.checkRolesStatus();
        
        // Setup role management event listeners
        this.setupRoleManagementListeners();
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
            // Roles exist - show simple status
            container.innerHTML = `
                <div class="flex items-center justify-between p-3 bg-green-900/20 border border-green-700/30 rounded">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-green-400"></i>
                        <span class="text-sm text-white">Roles Installed</span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="settingsManager.reinstallRoles()" class="px-3 py-1.5 text-xs bg-indigo-600 hover:bg-indigo-500 text-white rounded transition-colors">
                            <i class="fas fa-sync-alt mr-1"></i>Reinstall
                        </button>
                        <button onclick="settingsManager.uninstallRoles()" class="px-3 py-1.5 text-xs bg-red-600 hover:bg-red-500 text-white rounded transition-colors">
                            <i class="fas fa-trash mr-1"></i>Uninstall
                        </button>
                    </div>
                </div>
            `;
            
            if (rolesInfo) {
                rolesInfo.classList.remove('hidden');
                
                // Update individual role statuses
                Object.entries(data.details).forEach(([role, exists]) => {
                    const statusEl = document.getElementById(`role-status-${role.replace('wppos_', '')}`);
                    if (statusEl) {
                        if (exists) {
                            statusEl.className = 'text-xs px-2 py-1 rounded bg-green-900/30 text-green-400';
                            statusEl.innerHTML = '<i class="fas fa-check mr-1"></i>Active';
                        } else {
                            statusEl.className = 'text-xs px-2 py-1 rounded bg-slate-600 text-slate-400';
                            statusEl.innerHTML = 'Missing';
                        }
                    }
                });
            }
        } else {
            // Roles don't exist - show setup button
            container.innerHTML = `
                <div class="text-center py-4">
                    <p class="text-sm text-slate-400 mb-3">POS roles not installed</p>
                    <button onclick="settingsManager.setupRoles()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded transition-colors text-sm">
                        <i class="fas fa-plus mr-1"></i>Install Roles
                    </button>
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
     * Uninstall POS roles and capabilities
     */
    async uninstallRoles() {
        if (!confirm('WARNING: This will remove all POS roles and capabilities. Users with POS roles will lose access. This cannot be undone. Continue?')) {
            return;
        }
        
        const container = document.getElementById('roles-status-container');
        container.innerHTML = '<div class="flex items-center justify-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-red-400 mr-3"></i><span class="text-white">Uninstalling...</span></div>';
        
        try {
            const response = await fetch('api/wp-roles-setup.php?action=uninstall');
            const result = await response.json();
            
            if (result.success) {
                this.ui.showToast('POS roles uninstalled successfully!', 'success');
                setTimeout(() => { this.checkRolesStatus(); this.loadRolesList(); }, 500);
            } else {
                throw new Error(result.data?.message || 'Failed to uninstall roles');
            }
        } catch (error) {
            console.error('Error uninstalling roles:', error);
            this.ui.showToast(error.message || 'Failed to uninstall roles', 'error');
            setTimeout(() => this.checkRolesStatus(), 500);
        }
    }
    
    /**
     * Load and display all POS roles
     */
    async loadRolesList() {
        try {
            const response = await fetch('api/wp-roles-setup.php?action=list');
            const result = await response.json();
            
            if (result.success) {
                this.renderRolesList(result.data.roles);
                this.renderCapabilitiesCheckboxes(result.data.available_capabilities);
            } else {
                throw new Error(result.data?.message || 'Failed to load roles');
            }
        } catch (error) {
            console.error('Error loading roles list:', error);
            this.ui.showToast('Error loading roles list', 'error');
        }
    }
    
    /**
     * Render the list of existing roles
     * @param {Array} roles Array of role objects
     */
    renderRolesList(roles) {
        const container = document.getElementById('roles-list');
        if (!container) return;
        
        if (roles.length === 0) {
            container.innerHTML = `
                <div class="text-center py-6 text-slate-400">
                    <i class="fas fa-user-slash text-2xl mb-2"></i>
                    <p class="text-sm">No roles found</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = roles.map(role => {
            // Ensure capabilities is an array of strings
            const caps = Array.isArray(role.capabilities)
                ? role.capabilities.filter(cap => typeof cap === 'string' && cap.startsWith('wppos_'))
                : [];
            
            // Determine badge based on role type
            let badge = '';
            if (role.role_type === 'predefined') {
                badge = '<span class="text-xs px-2 py-0.5 rounded bg-yellow-900/30 text-yellow-400">Predefined</span>';
            } else if (role.role_type === 'woocommerce') {
                badge = '<span class="text-xs px-2 py-0.5 rounded bg-purple-900/30 text-purple-400">WooCommerce</span>';
            } else if (role.role_type === 'wordpress') {
                badge = '<span class="text-xs px-2 py-0.5 rounded bg-blue-900/30 text-blue-400">WordPress</span>';
            } else {
                badge = '<span class="text-xs px-2 py-0.5 rounded bg-green-900/30 text-green-400">Custom</span>';
            }
            
            return `
            <div class="bg-slate-800/50 p-3 rounded border border-slate-600 hover:border-slate-500 transition-colors">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <h4 class="text-sm font-medium text-white truncate">${role.name}</h4>
                            ${badge}
                        </div>
                        <p class="text-xs text-slate-400 font-mono mb-2">${role.slug}</p>
                        <div class="flex flex-wrap gap-1">
                            ${caps.slice(0, 5).map(cap =>
                                `<span class="text-xs px-2 py-0.5 rounded bg-slate-700 text-slate-300">${cap.replace('wppos_', '')}</span>`
                            ).join('')}
                            ${caps.length > 5 ?
                                `<span class="text-xs px-2 py-0.5 rounded bg-slate-700 text-slate-300">+${caps.length - 5} more</span>`
                                : ''}
                        </div>
                    </div>
                    <div class="flex gap-1 flex-shrink-0">
                        ${role.is_editable ? `
                            <button onclick="settingsManager.editRole('${role.slug}')" class="w-8 h-8 flex items-center justify-center bg-indigo-600 hover:bg-indigo-500 text-white rounded text-sm transition-colors" title="Edit role capabilities">
                                <i class="fas fa-edit"></i>
                            </button>
                        ` : ''}
                        ${role.is_editable && !role.is_predefined && role.role_type !== 'woocommerce' && role.role_type !== 'wordpress' ? `
                            <button onclick="settingsManager.deleteRole('${role.slug}')" class="w-8 h-8 flex items-center justify-center bg-red-600 hover:bg-red-500 text-white rounded text-sm transition-colors" title="Delete role">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                        ${!role.is_editable ? `
                            <span class="text-xs text-slate-500 px-2 py-1">Protected</span>
                        ` : ''}
                    </div>
                </div>
            </div>
            `;
        }).join('');
    }
    
    /**
     * Render capabilities as table rows with switches for role creation
     * @param {Object} capabilities Object of capability slug => description
     */
    renderCapabilitiesCheckboxes(capabilities) {
        const container = document.getElementById('capabilities-checkboxes');
        if (!container) return;
        
        container.innerHTML = Object.entries(capabilities).map(([cap, desc]) => `
            <tr class="border-b border-slate-600 hover:bg-slate-700/30">
                <td class="py-2 px-3 text-sm text-white">${desc}</td>
                <td class="py-2 px-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="capability" value="${cap}" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </td>
            </tr>
        `).join('');
    }
    
    /**
     * Setup event listeners for role management
     */
    setupRoleManagementListeners() {
        // Create role form
        const createForm = document.getElementById('create-role-form');
        if (createForm) {
            createForm.addEventListener('submit', (e) => this.handleCreateRole(e));
        }
        
        // Auto-generate slug from name
        const nameInput = document.getElementById('new-role-name');
        const slugInput = document.getElementById('new-role-slug');
        if (nameInput && slugInput) {
            nameInput.addEventListener('input', (e) => {
                if (!slugInput.dataset.manuallyEdited) {
                    slugInput.value = e.target.value
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '');
                }
            });
            
            slugInput.addEventListener('input', () => {
                slugInput.dataset.manuallyEdited = 'true';
            });
        }
        
        // Show/hide create role dialog
        const showDialogBtn = document.getElementById('show-create-dialog-btn');
        const closeDialogBtn = document.getElementById('close-create-dialog-btn');
        const cancelBtn = document.getElementById('cancel-create-btn');
        const dialog = document.getElementById('create-role-dialog');
        
        if (showDialogBtn && dialog) {
            showDialogBtn.addEventListener('click', () => {
                dialog.classList.remove('hidden');
                // Clear form when opening
                if (createForm) createForm.reset();
                if (slugInput) slugInput.dataset.manuallyEdited = '';
            });
        }
        
        if (closeDialogBtn && dialog) {
            closeDialogBtn.addEventListener('click', () => {
                dialog.classList.add('hidden');
            });
        }
        
        if (cancelBtn && dialog) {
            cancelBtn.addEventListener('click', () => {
                dialog.classList.add('hidden');
            });
        }
        
        // Close dialog when clicking overlay
        if (dialog) {
            dialog.addEventListener('click', (e) => {
                if (e.target === dialog) {
                    dialog.classList.add('hidden');
                }
            });
        }
        
        // Templates toggle (renamed from quickstart)
        const templatesToggle = document.getElementById('templates-toggle');
        const templatesContent = document.getElementById('templates-content');
        const templatesIcon = document.getElementById('templates-icon');
        
        if (templatesToggle && templatesContent) {
            templatesToggle.addEventListener('click', () => {
                templatesContent.classList.toggle('hidden');
                if (templatesIcon) {
                    templatesIcon.classList.toggle('fa-chevron-down');
                    templatesIcon.classList.toggle('fa-chevron-up');
                }
            });
        }
    }
    
    /**
     * Handle create role form submission
     * @param {Event} event Form submit event
     */
    async handleCreateRole(event) {
        event.preventDefault();
        
        const nameInput = document.getElementById('new-role-name');
        const slugInput = document.getElementById('new-role-slug');
        const checkboxes = document.querySelectorAll('#capabilities-checkboxes input[type="checkbox"]:checked');
        
        const name = nameInput.value.trim();
        const slug = slugInput.value.trim();
        const capabilities = Array.from(checkboxes).map(cb => cb.value);
        
        if (!name || !slug) {
            this.ui.showToast('Please enter both name and slug', 'error');
            return;
        }
        
        if (capabilities.length === 0) {
            this.ui.showToast('Please select at least one capability', 'error');
            return;
        }
        
        try {
            const response = await fetch('api/wp-roles-setup.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, slug, capabilities })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.ui.showToast('Role created successfully!', 'success');
                
                // Reset form
                nameInput.value = '';
                slugInput.value = '';
                slugInput.dataset.manuallyEdited = '';
                checkboxes.forEach(cb => cb.checked = false);
                
                // Close dialog after successful creation
                const dialog = document.getElementById('create-role-dialog');
                if (dialog) {
                    dialog.classList.add('hidden');
                }
                
                // Reload roles list
                await this.loadRolesList();
            } else {
                throw new Error(result.data?.message || 'Failed to create role');
            }
        } catch (error) {
            console.error('Error creating role:', error);
            this.ui.showToast(error.message || 'Failed to create role', 'error');
        }
    }
    
    /**
     * Edit an existing role
     * @param {string} roleSlug The role slug to edit
     */
    async editRole(roleSlug) {
        try {
            // Fetch current role data
            const response = await fetch('api/wp-roles-setup.php?action=list');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error('Failed to load role data');
            }
            
            const role = result.data.roles.find(r => r.slug === roleSlug);
            if (!role) {
                throw new Error('Role not found');
            }
            
            // Show edit modal with table and switches
            const capabilities = result.data.available_capabilities;
            const capabilitiesHTML = Object.entries(capabilities).map(([cap, desc]) => {
                const isChecked = role.capabilities.includes(cap);
                return `
                <tr class="border-b border-slate-600 hover:bg-slate-700/30">
                    <td class="py-2 px-3 text-sm text-white">${desc}</td>
                    <td class="py-2 px-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="edit-capability" value="${cap}"
                                   ${isChecked ? 'checked' : ''}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </td>
                </tr>
                `;
            }).join('');
            
            // Create modal
            const modal = document.createElement('div');
            modal.className = 'app-overlay';
            modal.innerHTML = `
                <div class="bg-slate-800 border border-slate-700 rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
                    <div class="p-4 border-b border-slate-700 flex items-center justify-between flex-shrink-0">
                        <div>
                            <h2 class="text-lg font-bold text-white">Edit Role: ${role.name}</h2>
                            <p class="text-xs text-slate-400 mt-1">Slug: <code class="font-mono">${roleSlug}</code></p>
                        </div>
                        <button id="edit-role-cancel" class="text-slate-400 hover:text-white transition-colors">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                    
                    <div class="flex-1 overflow-y-auto p-6">
                        <div class="space-y-6 h-full flex flex-col">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Role Name</label>
                                <input type="text" id="edit-role-name" value="${role.name}" class="w-full px-4 py-3 bg-slate-700 text-slate-200 rounded-lg border border-slate-600 focus:border-indigo-500 focus:outline-none">
                            </div>
                            
                            <div class="flex-1 overflow-hidden flex flex-col">
                                <label class="block text-sm font-medium text-slate-300 mb-2">Capabilities</label>
                                <div class="flex-1 overflow-y-auto bg-slate-700/30 rounded border border-slate-600">
                                    <table class="w-full text-left">
                                        <thead class="sticky top-0 bg-slate-800 z-10">
                                            <tr class="border-b border-slate-600">
                                                <th class="py-2 px-3 text-xs font-medium text-slate-400 uppercase">Capability</th>
                                                <th class="py-2 px-3 text-xs font-medium text-slate-400 uppercase w-24">Enabled</th>
                                            </tr>
                                        </thead>
                                        <tbody id="edit-capabilities-table">
                                            ${capabilitiesHTML}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 border-t border-slate-700 flex gap-2 flex-shrink-0">
                        <button id="edit-role-cancel-2" class="flex-1 px-4 py-3 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button id="edit-role-save" class="flex-1 px-4 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg transition-colors font-semibold">
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Setup modal events
            const closeModal = () => document.body.removeChild(modal);
            
            document.getElementById('edit-role-cancel').addEventListener('click', closeModal);
            document.getElementById('edit-role-cancel-2').addEventListener('click', closeModal);
            
            document.getElementById('edit-role-save').addEventListener('click', async () => {
                const newName = document.getElementById('edit-role-name').value.trim();
                const checkboxes = document.querySelectorAll('#edit-capabilities-table input[type="checkbox"]:checked');
                const newCapabilities = Array.from(checkboxes).map(cb => cb.value);
                
                if (newCapabilities.length === 0) {
                    this.ui.showToast('Please select at least one capability', 'error');
                    return;
                }
                
                try {
                    const updateResponse = await fetch('api/wp-roles-setup.php?action=update', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            slug: roleSlug,
                            name: newName,
                            capabilities: newCapabilities
                        })
                    });
                    
                    const updateResult = await updateResponse.json();
                    
                    if (updateResult.success) {
                        this.ui.showToast('Role updated successfully!', 'success');
                        closeModal();
                        await this.loadRolesList();
                    } else {
                        throw new Error(updateResult.data?.message || 'Failed to update role');
                    }
                } catch (error) {
                    console.error('Error updating role:', error);
                    this.ui.showToast(error.message || 'Failed to update role', 'error');
                }
            });
            
        } catch (error) {
            console.error('Error editing role:', error);
            this.ui.showToast(error.message || 'Failed to load role for editing', 'error');
        }
    }
    
    /**
     * Delete a role
     * @param {string} roleSlug The role slug to delete
     */
    async deleteRole(roleSlug) {
        if (!confirm(`Are you sure you want to delete this role?\n\nThis action cannot be undone.`)) {
            return;
        }
        
        try {
            const response = await fetch('api/wp-roles-setup.php?action=delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ slug: roleSlug })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.ui.showToast('Role deleted successfully!', 'success');
                await this.loadRolesList();
            } else {
                throw new Error(result.data?.message || 'Failed to delete role');
            }
        } catch (error) {
            console.error('Error deleting role:', error);
            this.ui.showToast(error.message || 'Failed to delete role', 'error');
        }
    }

    /**
     * Initialize settings tabs navigation with URL parameter persistence
     * @private
     */
    initSettingsTabs() {
        const tabs = document.querySelectorAll('.settings-tab');
        const panels = document.querySelectorAll('.settings-panel');
        
        if (!tabs.length || !panels.length) return;
        
        // Get current tab from URL parameter (default to 'receipt')
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'receipt';
        
        // Function to switch to a specific tab
        const switchToTab = (tabName) => {
            // Update active tab styling
            tabs.forEach(t => {
                t.classList.remove('border-indigo-500', 'text-indigo-400', 'bg-slate-700/50');
                t.classList.add('border-transparent', 'text-slate-400');
            });
            
            const activeTab = document.getElementById(`settings-tab-${tabName}`);
            if (activeTab) {
                activeTab.classList.remove('border-transparent', 'text-slate-400');
                activeTab.classList.add('border-indigo-500', 'text-indigo-400', 'bg-slate-700/50');
            }
            
            // Show corresponding panel
            panels.forEach(panel => {
                panel.classList.add('hidden');
            });
            const activePanel = document.getElementById(`settings-panel-${tabName}`);
            if (activePanel) {
                activePanel.classList.remove('hidden');
            }
            
            // Update URL without page reload
            const newUrl = new URL(window.location);
            newUrl.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', newUrl);
        };
        
        // Add click handlers to tabs
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const tabName = tab.id.replace('settings-tab-', '');
                switchToTab(tabName);
            });
        });
        
        // Restore active tab from URL on page load
        switchToTab(currentTab);
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
        const uiScaleSlider = document.getElementById('setting-ui-scale');
        
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
            ui_scale: uiScaleSlider ? parseInt(uiScaleSlider.value) : 100,
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
    
    /**
     * Apply UI scale to the interface
     * @param {number} scale - Scale percentage (50-150)
     */
    applyUIScale(scale) {
        // Convert percentage to decimal for CSS (100% = 1.0)
        const scaleFactor = scale / 100;
        
        // Apply scale using CSS custom property to scale font sizes and spacing
        // This approach scales content but maintains viewport heights (h-screen, h-full, etc.)
        document.documentElement.style.setProperty('--ui-scale', scaleFactor);
        
        // Apply font-size scaling to root element
        // Base font size is 16px, scale it proportionally
        const baseFontSize = 16;
        const scaledFontSize = baseFontSize * scaleFactor;
        document.documentElement.style.fontSize = `${scaledFontSize}px`;
        
        // Store in localStorage for immediate access on next page load
        localStorage.setItem('jpos_ui_scale', scale);
    }
}

// Export class
window.SettingsManager = SettingsManager;