/**
 * AttributeSearch Component
 * Reusable search component for attribute names
 * Similar to React/Next.js component structure
 */
class AttributeSearch extends BaseComponent {
    get defaultProps() {
        return {
            placeholder: 'Type attribute name...',
            suggestions: [],
            maxSuggestions: 5,
            allowCreate: true,
            onSelectAttribute: () => {},
            className: '',
            inputId: '',
            suggestionsId: ''
        };
    }

    componentDidMount() {
        this.setupEventListeners();
    }

    componentWillUnmount() {
        this.removeEventListeners();
    }

    setupEventListeners() {
        if (!this.inputElement) return;

        // Focus event - show all suggestions
        this.addEventListener(this.inputElement, 'focus', () => {
            this.showSuggestions();
        });

        // Input event - filter suggestions
        this.addEventListener(this.inputElement, 'input', 
            this.debounce((e) => {
                this.filterSuggestions(e.target.value);
            }, 150)
        );

        // Keypress events
        this.addEventListener(this.inputElement, 'keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.selectAttribute(this.inputElement.value);
            } else if (e.key === 'Escape') {
                this.hideSuggestions();
            }
        });

        // Click outside to hide suggestions
        this.addEventListener(document, 'click', (e) => {
            if (!this.element?.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }

    render() {
        const { 
            placeholder, 
            className, 
            inputId, 
            suggestionsId 
        } = this.props;

        this.element = this.createElement('div', {
            className: `relative ${className}`
        }, [
            // Input with suggestions
            this.createElement('div', { className: 'relative' }, [
                this.createElement('input', {
                    id: inputId,
                    type: 'text',
                    placeholder,
                    className: 'w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm focus:border-blue-500 focus:outline-none'
                }),
                
                this.createElement('div', {
                    id: suggestionsId,
                    className: 'absolute top-full left-0 right-0 bg-slate-700 border border-slate-500 rounded mt-1 max-h-32 overflow-y-auto hidden z-10'
                })
            ])
        ]);

        // Store references
        this.inputElement = this.element.querySelector(`#${inputId}`);
        this.suggestionsElement = this.element.querySelector(`#${suggestionsId}`);

        // Mount to container if provided
        if (this.container) {
            this.container.appendChild(this.element);
        }
    }

    showSuggestions() {
        if (!this.suggestionsElement) return;
        
        this.renderSuggestions(this.props.suggestions);
        this.show(this.suggestionsElement);
    }

    filterSuggestions(query) {
        if (!this.suggestionsElement) return;

        const { suggestions, allowCreate } = this.props;
        
        let filteredSuggestions;
        
        if (!query.trim()) {
            // Show all suggestions when no query
            filteredSuggestions = suggestions;
        } else {
            // Filter suggestions based on query
            filteredSuggestions = suggestions.filter(attr => 
                attr.toLowerCase().includes(query.toLowerCase())
            );
        }

        if (filteredSuggestions.length > 0) {
            this.renderSuggestions(filteredSuggestions);
            this.show(this.suggestionsElement);
        } else if (query.trim() && allowCreate) {
            // Show create option
            this.renderCreateOption(query);
            this.show(this.suggestionsElement);
        } else {
            this.hideSuggestions();
        }
    }

    renderSuggestions(suggestions) {
        if (!this.suggestionsElement) return;

        this.suggestionsElement.innerHTML = suggestions
            .slice(0, this.props.maxSuggestions)
            .map(suggestion => `
                <div class="px-3 py-2 text-sm text-slate-200 hover:bg-slate-600 cursor-pointer flex items-center" 
                     onclick="window.attributeSearchSelect('${suggestion}')">
                    ${suggestion}
                </div>
            `).join('');
    }

    renderCreateOption(query) {
        if (!this.suggestionsElement) return;

        this.suggestionsElement.innerHTML = `
            <div class="px-3 py-2 text-sm text-blue-400 hover:bg-slate-600 cursor-pointer flex items-center" 
                 onclick="window.attributeSearchCreate('${query}')">
                + Create "${query}" as new attribute
            </div>
        `;
    }

    hideSuggestions() {
        this.hide(this.suggestionsElement);
    }

    selectAttribute(attribute) {
        if (!attribute.trim()) return;
        
        const trimmedAttribute = attribute.trim();
        this.props.onSelectAttribute(trimmedAttribute);
        this.inputElement.value = trimmedAttribute;
        this.hideSuggestions();
    }

    // Public methods
    setSuggestions(suggestions) {
        this.setProps({ suggestions });
    }

    getValue() {
        return this.inputElement?.value || '';
    }

    setValue(value) {
        if (this.inputElement) {
            this.inputElement.value = value;
        }
    }

    clear() {
        this.inputElement.value = '';
        this.hideSuggestions();
    }
}

// Global functions for onclick handlers
window.attributeSearchSelect = function(attribute) {
    if (window.currentAttributeSearch) {
        window.currentAttributeSearch.selectAttribute(attribute);
    }
};

window.attributeSearchCreate = function(attribute) {
    if (window.currentAttributeSearch) {
        window.currentAttributeSearch.selectAttribute(attribute);
    }
};

// Make AttributeSearch globally available
window.AttributeSearch = AttributeSearch;
