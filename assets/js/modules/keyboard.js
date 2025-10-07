/**
 * JPOS On-Screen Keyboard Module
 * Touch-friendly virtual keyboard for quick customer search
 * Compatible with both touch and mouse input
 */

class OnScreenKeyboard {
    constructor() {
        this.targetInput = null;
        this.keyboardElement = null;
        this.isVisible = false;
        
        // Keyboard layout - optimized for name/email search
        this.layout = [
            ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P'],
            ['A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L'],
            ['Z', 'X', 'C', 'V', 'B', 'N', 'M', '@', '.'],
            ['Space', 'Backspace', 'Clear']
        ];
    }

    /**
     * Create keyboard HTML structure
     * @returns {HTMLElement} Keyboard container element
     */
    createKeyboard() {
        const container = document.createElement('div');
        container.id = 'on-screen-keyboard';
        container.className = 'fixed bottom-0 left-0 right-0 bg-slate-800 border-t border-slate-700 p-4 z-[9999] hidden';
        container.style.transition = 'transform 0.3s ease-in-out';
        
        const keyboardGrid = document.createElement('div');
        keyboardGrid.className = 'max-w-4xl mx-auto space-y-2';
        
        // Create rows
        this.layout.forEach((row, rowIndex) => {
            const rowElement = document.createElement('div');
            rowElement.className = 'flex justify-center gap-2';
            
            row.forEach(key => {
                const button = this.createKey(key, rowIndex);
                rowElement.appendChild(button);
            });
            
            keyboardGrid.appendChild(rowElement);
        });
        
        // Add close button
        const closeButton = document.createElement('button');
        closeButton.className = 'absolute top-2 right-2 text-slate-400 hover:text-white p-2';
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        closeButton.onclick = () => this.hide();
        container.appendChild(closeButton);
        
        container.appendChild(keyboardGrid);
        this.keyboardElement = container;
        
        return container;
    }

    /**
     * Create individual key button
     * @param {string} key - Key label
     * @param {number} rowIndex - Row number for styling
     * @returns {HTMLElement} Key button element
     */
    createKey(key, rowIndex) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'bg-slate-700 hover:bg-slate-600 text-white rounded-lg font-semibold transition-colors active:bg-slate-500 select-none';
        button.dataset.key = key;
        
        // Size classes based on key type
        if (key === 'Space') {
            button.className += ' px-20 py-4 text-sm';
            button.textContent = 'Space';
        } else if (key === 'Backspace') {
            button.className += ' px-8 py-4 text-sm';
            button.innerHTML = '<i class="fas fa-backspace"></i> Back';
        } else if (key === 'Clear') {
            button.className += ' px-8 py-4 text-sm bg-red-700 hover:bg-red-600';
            button.textContent = 'Clear';
        } else {
            button.className += ' w-12 h-12 text-lg';
            button.textContent = key;
        }
        
        // Add touch and click event listeners
        button.addEventListener('click', (e) => {
            e.preventDefault();
            this.handleKeyPress(key);
        });
        
        // Touch feedback
        button.addEventListener('touchstart', (e) => {
            button.classList.add('bg-slate-500');
        });
        
        button.addEventListener('touchend', (e) => {
            button.classList.remove('bg-slate-500');
        });
        
        return button;
    }

    /**
     * Handle key press event
     * @param {string} key - Key that was pressed
     */
    handleKeyPress(key) {
        if (!this.targetInput) return;
        
        const currentValue = this.targetInput.value;
        
        switch (key) {
            case 'Space':
                this.targetInput.value = currentValue + ' ';
                break;
                
            case 'Backspace':
                this.targetInput.value = currentValue.slice(0, -1);
                break;
                
            case 'Clear':
                this.targetInput.value = '';
                break;
                
            default:
                this.targetInput.value = currentValue + key;
        }
        
        // Trigger input event for any listeners (like search functionality)
        const inputEvent = new Event('input', { bubbles: true });
        this.targetInput.dispatchEvent(inputEvent);
        
        // Keep focus on input
        this.targetInput.focus();
    }

    /**
     * Show keyboard for a specific input
     * @param {HTMLInputElement} inputElement - Target input element
     */
    show(inputElement) {
        if (!inputElement) return;
        
        this.targetInput = inputElement;
        
        // Create keyboard if not exists
        if (!this.keyboardElement) {
            const keyboard = this.createKeyboard();
            document.body.appendChild(keyboard);
        }
        
        // Show keyboard
        this.keyboardElement.classList.remove('hidden');
        this.isVisible = true;
        
        // Adjust page padding to account for keyboard
        document.body.style.paddingBottom = '300px';
    }

    /**
     * Hide keyboard
     */
    hide() {
        if (!this.keyboardElement) return;
        
        this.keyboardElement.classList.add('hidden');
        this.isVisible = false;
        this.targetInput = null;
        
        // Remove page padding
        document.body.style.paddingBottom = '0';
    }

    /**
     * Toggle keyboard visibility
     * @param {HTMLInputElement} inputElement - Target input element
     */
    toggle(inputElement) {
        if (this.isVisible) {
            this.hide();
        } else {
            this.show(inputElement);
        }
    }

    /**
     * Check if keyboard is currently visible
     * @returns {boolean} Visibility status
     */
    isKeyboardVisible() {
        return this.isVisible;
    }

    /**
     * Initialize auto-show functionality based on settings
     * Attaches focus listeners to input fields when enabled
     */
    initAutoShow() {
        // Remove existing listeners first
        this.removeAutoShowListeners();
        
        // Check if auto-show is enabled in settings
        const settings = window.stateManager?.getState('settings') || {};
        const keyboardEnabled = settings.virtual_keyboard_enabled !== false;
        const autoShowEnabled = settings.virtual_keyboard_auto_show === true;
        
        if (!keyboardEnabled || !autoShowEnabled) {
            return; // Auto-show is disabled
        }
        
        // Select all text, email, and search inputs (excluding specific modals)
        const inputs = document.querySelectorAll(
            'input[type="text"]:not(.no-keyboard), ' +
            'input[type="email"]:not(.no-keyboard), ' +
            'input[type="search"]:not(.no-keyboard)'
        );
        
        inputs.forEach(input => {
            // Skip inputs in product editor, fee/discount modals
            const modal = input.closest('#product-editor-modal, #fee-modal, #discount-modal');
            if (modal) return;
            
            // Create bound handler for this input
            const focusHandler = () => this.show(input);
            const blurHandler = () => this.hide();
            
            // Store handlers for later removal
            if (!this.autoShowHandlers) {
                this.autoShowHandlers = new Map();
            }
            this.autoShowHandlers.set(input, { focus: focusHandler, blur: blurHandler });
            
            // Attach listeners
            input.addEventListener('focus', focusHandler);
            input.addEventListener('blur', blurHandler);
        });
    }

    /**
     * Remove auto-show event listeners
     */
    removeAutoShowListeners() {
        if (!this.autoShowHandlers) return;
        
        this.autoShowHandlers.forEach((handlers, input) => {
            input.removeEventListener('focus', handlers.focus);
            input.removeEventListener('blur', handlers.blur);
        });
        
        this.autoShowHandlers.clear();
    }

    /**
     * Destroy keyboard instance
     */
    destroy() {
        this.removeAutoShowListeners();
        
        if (this.keyboardElement && this.keyboardElement.parentNode) {
            this.keyboardElement.parentNode.removeChild(this.keyboardElement);
        }
        this.keyboardElement = null;
        this.targetInput = null;
        this.isVisible = false;
        document.body.style.paddingBottom = '0';
    }
}

// Create global instance
window.onScreenKeyboard = new OnScreenKeyboard();

// Expose initAutoShow as global function for settings.js
window.initKeyboardAutoShow = function() {
    if (window.onScreenKeyboard) {
        window.onScreenKeyboard.initAutoShow();
    }
};

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OnScreenKeyboard;
}