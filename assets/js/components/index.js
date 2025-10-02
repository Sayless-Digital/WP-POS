/**
 * JPOS Components Export
 * Next.js style component organization
 */

// Helper function to create components (similar to Next.js dynamic imports)
function createComponent(componentName, props = {}) {
    const components = {
        'ui/BaseComponent': window.BaseComponent,
        'search/OptionsSearch': window.OptionsSearch,
        'search/AttributeSearch': window.AttributeSearch,
        'forms/AttributeForm': window.AttributeForm
    };
    
    const Component = components[componentName];
    if (!Component) {
        throw new Error(`Component ${componentName} not found`);
    }
    return new Component(props);
}

// Component Registry (similar to Next.js pages)
const components = {
    'ui/BaseComponent': window.BaseComponent,
    'search/OptionsSearch': window.OptionsSearch,
    'search/AttributeSearch': window.AttributeSearch,
    'forms/AttributeForm': window.AttributeForm
};

// Make components globally available
window.JPOSComponents = {
    BaseComponent: window.BaseComponent,
    OptionsSearch: window.OptionsSearch,
    AttributeSearch: window.AttributeSearch,
    AttributeForm: window.AttributeForm,
    components,
    createComponent
};
