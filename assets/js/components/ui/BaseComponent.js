/**
 * Base Component Class for JPOS
 * Similar to React/Next.js component structure
 */
export class BaseComponent {
    constructor(props = {}) {
        this.props = { ...this.defaultProps, ...props };
        this.state = {};
        this.refs = {};
        this.eventListeners = [];
        this.mounted = false;
    }

    get defaultProps() {
        return {};
    }

    // Lifecycle methods similar to React
    componentDidMount() {
        // Override in child components
    }

    componentWillUnmount() {
        // Override in child components
    }

    componentDidUpdate(prevProps, prevState) {
        // Override in child components
    }

    // Mount the component
    mount(container) {
        if (this.mounted) return;
        
        this.container = container;
        this.render();
        this.componentDidMount();
        this.mounted = true;
    }

    // Unmount the component
    unmount() {
        if (!this.mounted) return;
        
        this.componentWillUnmount();
        this.removeEventListeners();
        if (this.container && this.element) {
            this.container.removeChild(this.element);
        }
        this.mounted = false;
    }

    // Render method - override in child components
    render() {
        throw new Error('Render method must be implemented');
    }

    // Update component with new props
    setProps(newProps) {
        const prevProps = { ...this.props };
        this.props = { ...this.props, ...newProps };
        this.componentDidUpdate(prevProps, this.state);
        this.render();
    }

    // Update component state
    setState(newState) {
        const prevState = { ...this.state };
        this.state = { ...this.state, ...newState };
        this.componentDidUpdate(this.props, prevState);
        this.render();
    }

    // Create DOM element
    createElement(tag, props = {}, children = []) {
        const element = document.createElement(tag);
        
        // Set attributes
        Object.keys(props).forEach(key => {
            if (key === 'className') {
                element.className = props[key];
            } else if (key === 'innerHTML') {
                element.innerHTML = props[key];
            } else if (key.startsWith('on')) {
                const eventName = key.slice(2).toLowerCase();
                this.addEventListener(element, eventName, props[key]);
            } else {
                element.setAttribute(key, props[key]);
            }
        });

        // Add children
        children.forEach(child => {
            if (typeof child === 'string') {
                element.appendChild(document.createTextNode(child));
            } else if (child instanceof HTMLElement) {
                element.appendChild(child);
            }
        });

        return element;
    }

    // Add event listener with cleanup tracking
    addEventListener(element, event, handler) {
        element.addEventListener(event, handler);
        this.eventListeners.push({ element, event, handler });
    }

    // Remove all event listeners
    removeEventListeners() {
        this.eventListeners.forEach(({ element, event, handler }) => {
            element.removeEventListener(event, handler);
        });
        this.eventListeners = [];
    }

    // Utility methods
    show(element) {
        element?.classList.remove('hidden');
    }

    hide(element) {
        element?.classList.add('hidden');
    }

    // Debounce utility
    debounce(func, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
}
