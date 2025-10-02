/**
 * Base Component Class for JPOS
 * Provides common functionality for all components
 */
class BaseComponent {
    constructor(options = {}) {
        this.options = { ...this.defaultOptions, ...options };
        this.element = null;
        this.eventListeners = [];
        this.init();
    }

    get defaultOptions() {
        return {};
    }

    init() {
        // Override in child classes
    }

    destroy() {
        // Remove all event listeners
        this.eventListeners.forEach(({ element, event, handler }) => {
            element.removeEventListener(event, handler);
        });
        this.eventListeners = [];
    }

    addEventListener(element, event, handler) {
        element.addEventListener(event, handler);
        this.eventListeners.push({ element, event, handler });
    }

    createElement(tag, className = '', innerHTML = '') {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (innerHTML) element.innerHTML = innerHTML;
        return element;
    }

    show(element) {
        element.classList.remove('hidden');
    }

    hide(element) {
        element.classList.add('hidden');
    }

    // Utility method to debounce function calls
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}
