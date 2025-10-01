/**
 * WP-POS Modern Installer JavaScript
 * Handles interactive functionality and form validation
 */

class InstallerApp {
    constructor() {
        this.currentStep = 1;
        this.isLoading = false;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeTooltips();
        this.setupFormValidation();
        console.log('WP-POS Installer initialized');
    }

    bindEvents() {
        // Form submissions
        document.addEventListener('submit', (e) => {
            if (e.target.classList.contains('installer-form')) {
                this.handleFormSubmit(e);
            }
        });

        // Real-time validation
        document.addEventListener('input', (e) => {
            if (e.target.classList.contains('validate-input')) {
                this.validateField(e.target);
            }
        });

        // Button clicks
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('test-connection')) {
                this.testConnection(e);
            }
            if (e.target.classList.contains('show-password')) {
                this.togglePasswordVisibility(e);
            }
        });

        // Modal handling
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target.id);
            }
        });
    }

    handleFormSubmit(e) {
        e.preventDefault();
        
        if (this.isLoading) return;

        const form = e.target;
        const formData = new FormData(form);
        const action = e.submitter.name;

        // Validate form before submission
        if (!this.validateForm(form)) {
            this.showAlert('Please fix the errors before continuing.', 'error');
            return;
        }

        // Show loading state
        this.setLoadingState(true);

        // Submit form
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                window.location.reload();
            } else {
                throw new Error('Form submission failed');
            }
        })
        .catch(error => {
            this.setLoadingState(false);
            this.showAlert('An error occurred. Please try again.', 'error');
            console.error('Form submission error:', error);
        });
    }

    testConnection(e) {
        e.preventDefault();
        
        if (this.isLoading) return;

        const button = e.target;
        const form = button.closest('form');
        const formData = new FormData(form);
        
        // Add test connection flag
        formData.append('test_connection', '1');

        this.setLoadingState(true, button);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(html => {
            // Reload page to show results
            window.location.reload();
        })
        .catch(error => {
            this.setLoadingState(false, button);
            this.showAlert('Connection test failed. Please try again.', 'error');
            console.error('Connection test error:', error);
        });
    }

    validateForm(form) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        return isValid;
    }

    validateField(field) {
        const value = field.value.trim();
        const fieldType = field.type;
        const fieldName = field.name;
        let isValid = true;
        let errorMessage = '';

        // Remove existing error styling
        this.removeFieldError(field);

        // Required field validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'This field is required';
        }

        // Email validation
        if (fieldType === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }

        // Password validation
        if (fieldType === 'password' && value) {
            const passwordValidation = this.validatePasswordStrength(value);
            if (!passwordValidation.valid) {
                isValid = false;
                errorMessage = passwordValidation.errors[0];
            }
        }

        // URL validation
        if (fieldName === 'wc_url' && value) {
            try {
                new URL(value);
            } catch {
                isValid = false;
                errorMessage = 'Please enter a valid URL';
            }
        }

        // Database port validation
        if (fieldName === 'db_port' && value) {
            const port = parseInt(value);
            if (isNaN(port) || port < 1 || port > 65535) {
                isValid = false;
                errorMessage = 'Please enter a valid port number (1-65535)';
            }
        }

        // Show error if invalid
        if (!isValid) {
            this.showFieldError(field, errorMessage);
        }

        return isValid;
    }

    validatePasswordStrength(password) {
        const errors = [];
        
        if (password.length < 8) {
            errors.push('Password must be at least 8 characters long');
        }
        
        if (!/[A-Z]/.test(password)) {
            errors.push('Password must contain at least one uppercase letter');
        }
        
        if (!/[a-z]/.test(password)) {
            errors.push('Password must contain at least one lowercase letter');
        }
        
        if (!/[0-9]/.test(password)) {
            errors.push('Password must contain at least one number');
        }
        
        return {
            valid: errors.length === 0,
            errors: errors
        };
    }

    showFieldError(field, message) {
        field.classList.add('error');
        
        let errorElement = field.parentNode.querySelector('.field-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'field-error';
            field.parentNode.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
        errorElement.style.color = 'var(--error-color)';
        errorElement.style.fontSize = '0.8rem';
        errorElement.style.marginTop = '0.25rem';
    }

    removeFieldError(field) {
        field.classList.remove('error');
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    setLoadingState(loading, element = null) {
        this.isLoading = loading;
        
        if (element) {
            if (loading) {
                element.disabled = true;
                element.innerHTML = '<span class="loading-spinner"></span> Testing...';
            } else {
                element.disabled = false;
                element.innerHTML = element.dataset.originalText || 'Test Connection';
            }
        } else {
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(btn => {
                btn.disabled = loading;
                if (loading) {
                    btn.dataset.originalText = btn.textContent;
                    btn.innerHTML = '<span class="loading-spinner"></span> Loading...';
                } else {
                    btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
                }
            });
        }
    }

    showAlert(message, type = 'info') {
        // Remove existing alerts
        const existingAlerts = document.querySelectorAll('.dynamic-alert');
        existingAlerts.forEach(alert => alert.remove());

        // Create new alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} dynamic-alert`;
        
        const icon = this.getAlertIcon(type);
        alert.innerHTML = `
            <div class="alert-icon">${icon}</div>
            <div class="alert-content">
                <div class="alert-message">${message}</div>
            </div>
        `;

        // Insert at top of content
        const content = document.querySelector('.content-wrapper');
        content.insertBefore(alert, content.firstChild);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }

    getAlertIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        return icons[type] || icons.info;
    }

    togglePasswordVisibility(e) {
        e.preventDefault();
        const button = e.target;
        const input = button.previousElementSibling;
        
        if (input.type === 'password') {
            input.type = 'text';
            button.textContent = 'Hide';
        } else {
            input.type = 'password';
            button.textContent = 'Show';
        }
    }

    initializeTooltips() {
        // Add tooltips to form fields
        const tooltipFields = document.querySelectorAll('[data-tooltip]');
        tooltipFields.forEach(field => {
            this.addTooltip(field);
        });
    }

    addTooltip(element) {
        const tooltipText = element.dataset.tooltip;
        if (!tooltipText) return;

        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = tooltipText;
        
        element.addEventListener('mouseenter', () => {
            document.body.appendChild(tooltip);
            this.positionTooltip(tooltip, element);
        });
        
        element.addEventListener('mouseleave', () => {
            tooltip.remove();
        });
    }

    positionTooltip(tooltip, element) {
        const rect = element.getBoundingClientRect();
        tooltip.style.position = 'absolute';
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
        tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
        tooltip.style.background = 'var(--gray-800)';
        tooltip.style.color = 'white';
        tooltip.style.padding = '0.5rem 0.75rem';
        tooltip.style.borderRadius = 'var(--border-radius)';
        tooltip.style.fontSize = '0.8rem';
        tooltip.style.zIndex = '1000';
        tooltip.style.pointerEvents = 'none';
    }

    setupFormValidation() {
        // Add real-time validation to form fields
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.add('validate-input');
        });
    }
}

// Modal functions
function showHelp() {
    document.getElementById('helpModal').style.display = 'block';
}

function showAbout() {
    document.getElementById('aboutModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new InstallerApp();
});

// Add CSS for error states
const errorStyles = `
    .form-group input.error,
    .form-group select.error,
    .form-group textarea.error {
        border-color: var(--error-color);
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
    
    .field-error {
        color: var(--error-color);
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }
`;

const styleSheet = document.createElement('style');
styleSheet.textContent = errorStyles;
document.head.appendChild(styleSheet);
