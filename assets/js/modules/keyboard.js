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
        this.isKeyboardInUse = false; // Track if keyboard is being actively used
        
        // Full QWERTY keyboard layout with numbers and symbols
        this.layout = [
            ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '-', '='],
            ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P', '[', ']'],
            ['A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', ';', "'"],
            ['Z', 'X', 'C', 'V', 'B', 'N', 'M', ',', '.', '/', '@'],
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
        container.className = 'fixed bottom-0 left-0 right-0 bg-slate-900/80 backdrop-blur-lg border-t border-slate-700 p-4 z-[9999] rounded-t-2xl transform translate-y-full transition-transform duration-300 ease-in-out';
        
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
        
        // Add control buttons
        const controlsContainer = document.createElement('div');
        controlsContainer.className = 'absolute top-2 right-2 flex gap-2';
        
        // Settings button - navigates to keyboard settings
        const settingsButton = document.createElement('button');
        settingsButton.id = 'keyboard-settings-btn';
        settingsButton.type = 'button';
        settingsButton.className = 'text-slate-400 hover:text-indigo-400 p-2 rounded transition-colors';
        settingsButton.innerHTML = '<i class="fas fa-cog"></i>';
        settingsButton.title = 'Keyboard Settings';
        settingsButton.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log('Keyboard settings button clicked');
            // Navigate to settings page with keyboard tab by updating URL
            const url = new URL(window.location);
            url.searchParams.set('view', 'settings-page');
            url.searchParams.set('tab', 'keyboard');
            console.log('Navigating to:', url.toString());
            window.location.href = url.toString();
        });
        controlsContainer.appendChild(settingsButton);
        
        // Close button
        const closeButton = document.createElement('button');
        closeButton.className = 'text-slate-400 hover:text-white p-2';
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        closeButton.title = 'Close Keyboard';
        closeButton.onclick = () => this.hide();
        controlsContainer.appendChild(closeButton);
        
        container.appendChild(controlsContainer);
        
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
        button.className = 'bg-gradient-to-b from-slate-600 to-slate-700 hover:from-slate-500 hover:to-slate-600 text-white rounded-lg font-semibold transition-all select-none shadow-[0_4px_0_0_rgb(30,41,59)] hover:shadow-[0_2px_0_0_rgb(30,41,59)] active:shadow-[0_0_0_0_rgb(30,41,59)] active:translate-y-1 border border-slate-500';
        button.dataset.key = key;
        
        // Size classes based on key type
        if (key === 'Space') {
            button.className += ' px-20 py-4 text-sm';
            button.textContent = 'Space';
        } else if (key === 'Backspace') {
            button.className += ' px-8 py-4 text-sm';
            button.innerHTML = '<i class="fas fa-backspace"></i> Back';
        } else if (key === 'Clear') {
            button.className += ' px-8 py-4 text-sm bg-gradient-to-b from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 shadow-[0_4px_0_0_rgb(127,29,29)] hover:shadow-[0_2px_0_0_rgb(127,29,29)] active:shadow-[0_0_0_0_rgb(127,29,29)] border-red-500';
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
        
        // Touch feedback - already handled by active state with translate
        
        return button;
    }

    /**
     * Handle key press event
     * @param {string} key - Key that was pressed
     */
    handleKeyPress(key) {
        if (!this.targetInput) return;
        
        // Set flag to indicate keyboard is in use
        this.isKeyboardInUse = true;
        
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
        
        // Reset flag after a short delay
        setTimeout(() => {
            this.isKeyboardInUse = false;
        }, 150);
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
        
        // Show keyboard with slide animation
        this.keyboardElement.classList.remove('translate-y-full');
        this.isVisible = true;
        
        // Adjust page padding to account for keyboard
        document.body.style.paddingBottom = '300px';
    }

    /**
     * Hide keyboard
     */
    hide() {
        if (!this.keyboardElement) return;
        
        this.keyboardElement.classList.add('translate-y-full');
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
        let settings = window.stateManager?.getState('settings') || {};
        
        // Handle case where settings might be wrapped in API response format
        if (settings.data) {
            settings = settings.data;
        }
        
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
            const focusHandler = () => {
                // Don't show keyboard if this is a programmatic focus from barcode scanning
                if (input.dataset.skipKeyboard === 'true') {
                    return;
                }
                this.show(input);
            };
            const blurHandler = (e) => {
                // Don't hide if keyboard is being actively used or clicking on keyboard
                setTimeout(() => {
                    if (this.isKeyboardInUse) {
                        return; // Keyboard is in use, don't hide
                    }
                    
                    const clickedInsideKeyboard = this.keyboardElement &&
                        this.keyboardElement.contains(document.activeElement);
                    
                    if (!clickedInsideKeyboard && this.isVisible) {
                        this.hide();
                    }
                }, 100);
            };
            
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