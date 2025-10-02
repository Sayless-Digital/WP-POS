/**
 * JPOS Components Export
 * Next.js style component organization
 */

// UI Components
export { BaseComponent } from './ui/BaseComponent.js';

// Search Components
export { OptionsSearch } from './search/OptionsSearch.js';
export { AttributeSearch } from './search/AttributeSearch.js';

// Form Components
export { AttributeForm } from './forms/AttributeForm.js';

// Component Registry (similar to Next.js pages)
export const components = {
    'ui/BaseComponent': BaseComponent,
    'search/OptionsSearch': OptionsSearch,
    'search/AttributeSearch': AttributeSearch,
    'forms/AttributeForm': AttributeForm
};

// Helper function to create components (similar to Next.js dynamic imports)
export function createComponent(componentName, props = {}) {
    const Component = components[componentName];
    if (!Component) {
        throw new Error(`Component ${componentName} not found`);
    }
    return new Component(props);
}

// Make components globally available
window.JPOSComponents = {
    BaseComponent,
    OptionsSearch,
    AttributeSearch,
    AttributeForm,
    components,
    createComponent
};
