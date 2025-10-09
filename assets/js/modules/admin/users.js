/**
 * Users Management Module
 * Handles user CRUD operations and role assignment
 */

class UsersManager {
    constructor(stateManager, uiHelpers) {
        this.state = stateManager;
        this.ui = uiHelpers;
        this.apiUrl = 'api/users.php';
        this.currentOffset = 0;
        this.limit = 20;
        this.hasMore = true;
        this.isLoading = false;
    }

    /**
     * Load users from API
     */
    async loadUsers(searchTerm = '', roleFilter = 'all', append = false) {
        if (this.isLoading) return [];
        
        const container = document.getElementById('users-list');
        if (!container) return [];
        
        // Show skeleton loader while fetching (only if not appending)
        if (!append) {
            container.innerHTML = this.ui.getSkeletonLoaderHtml('list-rows', 10);
            this.currentOffset = 0;
        } else {
            // Show loading indicator at bottom when appending
            const loadingDiv = document.createElement('div');
            loadingDiv.id = 'users-loading-more';
            loadingDiv.className = 'col-span-12 text-center py-4';
            loadingDiv.innerHTML = '<div class="text-slate-400 text-sm"><i class="fas fa-circle-notch fa-spin mr-2"></i>Loading more users...</div>';
            container.appendChild(loadingDiv);
        }
        
        this.isLoading = true;
        
        try {
            const params = new URLSearchParams();
            params.append('action', 'list');
            params.append('offset', this.currentOffset.toString());
            params.append('limit', this.limit.toString());
            
            if (searchTerm) params.append('search', searchTerm);
            if (roleFilter && roleFilter !== 'all') params.append('role', roleFilter);
            
            const url = `${this.apiUrl}?${params.toString()}`;
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                // Store has_more flag
                this.hasMore = result.data.has_more || false;
                this.currentOffset += this.limit;
                
                // Store in state for reference
                if (!this.state.users) {
                    this.state.users = {};
                }
                
                if (append) {
                    // Append to existing list
                    this.state.users.list = [...(this.state.users.list || []), ...result.data.users];
                } else {
                    // Replace list
                    this.state.users.list = result.data.users;
                }
                
                this.state.users.total = result.data.total;
                
                return result.data.users;
            } else {
                throw new Error(result.data?.message || 'Failed to load users');
            }
        } catch (error) {
            console.error('Error loading users:', error);
            this.ui.showToast(error.message, 'error');
            return [];
        } finally {
            this.isLoading = false;
            // Remove loading indicator if it exists
            const loadingDiv = document.getElementById('users-loading-more');
            if (loadingDiv) {
                loadingDiv.remove();
            }
        }
    }

    /**
     * Render users list in the table
     */
    renderUsersList(users, append = false) {
        const container = document.getElementById('users-list');
        if (!container) return;

        if (!users || users.length === 0) {
            if (!append) {
                container.innerHTML = `
                    <div class="col-span-12 text-center py-12 text-slate-400">
                        <i class="fas fa-users text-4xl mb-3"></i>
                        <p>No users found</p>
                    </div>
                `;
            }
            return;
        }

        const userHtml = users.map(user => {
            const rolesList = user.role_names && user.role_names.length > 0
                ? user.role_names.join(', ')
                : 'No role';
            
            const registeredDate = new Date(user.registered).toLocaleDateString();
            
            return `
                <div class="grid grid-cols-12 gap-4 items-center p-3 bg-slate-800/50 rounded-lg border border-slate-700 hover:border-slate-600 transition-colors">
                    <div class="col-span-3">
                        <div class="font-semibold text-slate-200">${this.ui.escapeHtml(user.display_name)}</div>
                        <div class="text-xs text-slate-400">@${this.ui.escapeHtml(user.username)}</div>
                    </div>
                    <div class="col-span-3 text-sm text-slate-300">${this.ui.escapeHtml(user.email)}</div>
                    <div class="col-span-2 text-sm text-slate-400">${rolesList}</div>
                    <div class="col-span-2 text-sm text-slate-400">${registeredDate}</div>
                    <div class="col-span-2 flex gap-2 justify-end">
                        <button onclick="window.editUser(${user.id})"
                                class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white text-sm rounded transition-colors"
                                title="Edit User">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="window.deleteUser(${user.id}, '${this.ui.escapeHtml(user.username)}')"
                                class="px-3 py-1 bg-red-600 hover:bg-red-500 text-white text-sm rounded transition-colors"
                                title="Delete User">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
        
        if (append) {
            container.insertAdjacentHTML('beforeend', userHtml);
        } else {
            container.innerHTML = userHtml;
        }
    }
    
    /**
     * Toggle include customers setting
     */
    toggleIncludeCustomers(include) {
        this.includeCustomers = include;
        this.currentOffset = 0;
        this.hasMore = true;
        
        // Reload users with new setting
        const searchTerm = document.getElementById('users-search')?.value || '';
        const roleFilter = document.getElementById('users-role-filter')?.value || 'all';
        this.loadUsers(searchTerm, roleFilter).then(users => {
            this.renderUsersList(users);
        });
    }

    /**
     * Show create user dialog
     */
    showCreateUserDialog() {
        const dialog = document.getElementById('user-dialog');
        const title = document.getElementById('user-dialog-title');
        const form = document.getElementById('user-form');
        
        if (!dialog || !title || !form) return;
        
        title.textContent = 'Create New User';
        form.reset();
        
        // Show password field for new users
        const passwordField = document.getElementById('user-password-field');
        if (passwordField) {
            passwordField.classList.remove('hidden');
            document.getElementById('user-password').required = true;
        }
        
        // Store that we're creating (not editing)
        form.dataset.mode = 'create';
        form.dataset.userId = '';
        
        // Load available roles
        this.loadRolesForDialog();
        
        dialog.classList.remove('hidden');
    }

    /**
     * Show edit user dialog
     */
    async showEditUserDialog(userId) {
        try {
            const response = await fetch(`${this.apiUrl}?action=get&user_id=${userId}`);
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.data?.message || 'Failed to load user');
            }
            
            const user = result.data.user;
            const dialog = document.getElementById('user-dialog');
            const title = document.getElementById('user-dialog-title');
            const form = document.getElementById('user-form');
            
            if (!dialog || !title || !form) return;
            
            title.textContent = 'Edit User';
            
            // Fill form with user data
            document.getElementById('user-username').value = user.username;
            document.getElementById('user-username').disabled = true; // Can't change username
            document.getElementById('user-email').value = user.email;
            document.getElementById('user-first-name').value = user.first_name || '';
            document.getElementById('user-last-name').value = user.last_name || '';
            
            // Hide password field for editing (optional change)
            const passwordField = document.getElementById('user-password-field');
            if (passwordField) {
                passwordField.classList.add('hidden');
                document.getElementById('user-password').required = false;
            }
            
            // Store that we're editing
            form.dataset.mode = 'edit';
            form.dataset.userId = userId;
            
            // Load roles and select user's current roles
            await this.loadRolesForDialog(user.roles);
            
            dialog.classList.remove('hidden');
        } catch (error) {
            console.error('Error loading user:', error);
            this.ui.showToast(error.message, 'error');
        }
    }

    /**
     * Load available roles for the dialog
     */
    async loadRolesForDialog(selectedRoles = []) {
        try {
            const response = await fetch('api/wp-roles-setup.php?action=list');
            const result = await response.json();
            
            if (!result.success) {
                throw new Error('Failed to load roles');
            }
            
            const rolesContainer = document.getElementById('user-roles-list');
            if (!rolesContainer) return;
            
            const roles = result.data.roles;
            
            rolesContainer.innerHTML = roles.map(role => {
                const isChecked = selectedRoles.includes(role.slug);
                const isDisabled = role.slug === 'administrator'; // Can't assign admin role
                
                return `
                    <label class="flex items-center py-2 px-3 hover:bg-slate-700/30 rounded cursor-pointer ${isDisabled ? 'opacity-50' : ''}">
                        <input type="checkbox" 
                               name="user_roles" 
                               value="${role.slug}"
                               ${isChecked ? 'checked' : ''}
                               ${isDisabled ? 'disabled' : ''}
                               class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500">
                        <span class="ml-3 text-sm text-slate-200">
                            ${this.ui.escapeHtml(role.name)}
                            ${role.is_protected ? '<i class="fas fa-lock text-xs text-slate-500 ml-1"></i>' : ''}
                        </span>
                    </label>
                `;
            }).join('');
        } catch (error) {
            console.error('Error loading roles:', error);
            this.ui.showToast('Failed to load roles', 'error');
        }
    }

    /**
     * Close user dialog
     */
    closeUserDialog() {
        const dialog = document.getElementById('user-dialog');
        const form = document.getElementById('user-form');
        
        if (dialog) dialog.classList.add('hidden');
        if (form) {
            form.reset();
            document.getElementById('user-username').disabled = false;
        }
    }

    /**
     * Save user (create or update)
     */
    async saveUser(formData) {
        // Get save button and show loading state
        const saveBtn = document.getElementById('user-dialog-save');
        const originalText = saveBtn ? saveBtn.innerHTML : '';
        
        try {
            // Disable button during save
            if (saveBtn) {
                saveBtn.disabled = true;
            }
            
            const form = document.getElementById('user-form');
            const mode = form.dataset.mode;
            const userId = form.dataset.userId;
            
            // Get selected roles
            const roleCheckboxes = document.querySelectorAll('input[name="user_roles"]:checked');
            const roles = Array.from(roleCheckboxes).map(cb => cb.value);
            
            if (roles.length === 0) {
                this.ui.showToast('Please select at least one role');
                return;
            }
            
            const userData = {
                action: mode === 'create' ? 'create' : 'update',
                username: formData.get('username'),
                email: formData.get('email'),
                first_name: formData.get('first_name'),
                last_name: formData.get('last_name'),
                roles: roles
            };
            
            // Add password if creating or if provided
            const password = formData.get('password');
            if (mode === 'create' || password) {
                userData.password = password;
            }
            
            // Add user ID if editing
            if (mode === 'edit') {
                userData.user_id = parseInt(userId);
            }
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(userData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Show success message - check both possible response structures
                const message = result.data?.message || result.message ||
                              (mode === 'create' ? 'User created successfully' : 'User updated successfully');
                this.ui.showToast(message);
                this.closeUserDialog();
                
                // Reload users list (reset to first page)
                this.currentOffset = 0;
                this.hasMore = true;
                const searchTerm = document.getElementById('users-search')?.value || '';
                const roleFilter = document.getElementById('users-role-filter')?.value || 'all';
                const users = await this.loadUsers(searchTerm, roleFilter, false);
                this.renderUsersList(users, false);
            } else {
                throw new Error(result.data?.message || result.message || 'Failed to save user');
            }
        } catch (error) {
            console.error('Error saving user:', error);
            this.ui.showToast(error.message || 'An error occurred');
        } finally {
            // Restore button state
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }
    }

    /**
     * Delete user with confirmation
     */
    async deleteUser(userId, username) {
        if (!confirm(`Are you sure you want to delete user "${username}"?\n\nThis action cannot be undone and all their content will be reassigned.`)) {
            return;
        }
        
        // Find the delete button for this user
        const deleteBtn = event?.target?.closest('button');
        const originalHTML = deleteBtn ? deleteBtn.innerHTML : '';
        
        try {
            // Disable button during delete
            if (deleteBtn) {
                deleteBtn.disabled = true;
            }
            
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    user_id: userId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Extract message with fallback chain
                const message = result.data?.message || result.message || 'User deleted successfully';
                this.ui.showToast(message, 'success');
                
                // Reload users list (reset to first page)
                this.currentOffset = 0;
                this.hasMore = true;
                const searchTerm = document.getElementById('users-search')?.value || '';
                const roleFilter = document.getElementById('users-role-filter')?.value || 'all';
                const users = await this.loadUsers(searchTerm, roleFilter, false);
                this.renderUsersList(users, false);
            } else {
                throw new Error(result.data?.message || result.message || 'Failed to delete user');
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            this.ui.showToast(error.message, 'error');
            
            // Restore button state on error
            if (deleteBtn) {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = originalHTML;
            }
        }
    }

    /**
     * Setup infinite scroll for users list
     */
    setupInfiniteScroll() {
        const container = document.getElementById('users-list');
        if (!container) return;
        
        const parentContainer = container.parentElement;
        if (!parentContainer) return;
        
        parentContainer.addEventListener('scroll', async () => {
            // Check if we're near the bottom (within 200px)
            const scrollTop = parentContainer.scrollTop;
            const scrollHeight = parentContainer.scrollHeight;
            const clientHeight = parentContainer.clientHeight;
            
            if (scrollHeight - scrollTop - clientHeight < 200 && this.hasMore && !this.isLoading) {
                const searchTerm = document.getElementById('users-search')?.value || '';
                const roleFilter = document.getElementById('users-role-filter')?.value || 'all';
                const newUsers = await this.loadUsers(searchTerm, roleFilter, true);
                this.renderUsersList(newUsers, true);
            }
        });
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Create user button
        const createBtn = document.getElementById('create-user-btn');
        if (createBtn) {
            createBtn.addEventListener('click', () => this.showCreateUserDialog());
        }
        
        // Close dialog buttons
        const closeButtons = ['user-dialog-close', 'user-dialog-cancel'];
        closeButtons.forEach(id => {
            const btn = document.getElementById(id);
            if (btn) {
                btn.addEventListener('click', () => this.closeUserDialog());
            }
        });
        
        // Form submission
        const form = document.getElementById('user-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                await this.saveUser(formData);
            });
        }
        
        // Search input
        const searchInput = document.getElementById('users-search');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(async () => {
                    this.currentOffset = 0;
                    this.hasMore = true;
                    const roleFilter = document.getElementById('users-role-filter')?.value || 'all';
                    const users = await this.loadUsers(e.target.value, roleFilter, false);
                    this.renderUsersList(users, false);
                }, 300);
            });
        }
        
        // Role filter
        const roleFilter = document.getElementById('users-role-filter');
        if (roleFilter) {
            roleFilter.addEventListener('change', async (e) => {
                this.currentOffset = 0;
                this.hasMore = true;
                const searchTerm = document.getElementById('users-search')?.value || '';
                const users = await this.loadUsers(searchTerm, e.target.value, false);
                this.renderUsersList(users, false);
            });
        }
        
        // Setup infinite scroll
        this.setupInfiniteScroll();
    }
}

// Export for use in main.js
window.UsersManager = UsersManager;