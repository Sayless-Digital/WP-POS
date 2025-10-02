import { BaseComponent } from '../ui/BaseComponent.js';

/**
 * OptionsSearch Component
 * Reusable search component for attribute options
 * Similar to React/Next.js component structure
 */
export class OptionsSearch extends BaseComponent {
    get defaultProps() {
        return {
            placeholder: 'Type to add option...',
            suggestions: [],
            maxSuggestions: 8,
            allowCreate: true,
            onCreateOption: () => {},
            onSelectOption: () => {},
            onRemoveOption: () => {},
            existingOptions: [],
            className: '',
            inputId: '',
            suggestionsId: '',
            optionsId: ''
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
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                this.addOption(this.inputElement.value);
                this.inputElement.value = '';
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
            suggestionsId, 
            optionsId 
        } = this.props;

        this.element = this.createElement('div', {
            className: `relative ${className}`
        }, [
            // Options container
            this.createElement('div', {
                id: optionsId,
                className: 'flex flex-wrap gap-1 mb-2'
            }),
            
            // Input with suggestions
            this.createElement('div', { className: 'relative' }, [
                this.createElement('input', {
                    id: inputId,
                    type: 'text',
                    placeholder,
                    className: 'w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm focus:border-blue-500 focus:outline-none'
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
        this.optionsElement = this.element.querySelector(`#${optionsId}`);

        // Mount to container if provided
        if (this.container) {
            this.container.appendChild(this.element);
        }
    }

    showSuggestions() {
        if (!this.suggestionsElement) return;
        
        const filteredSuggestions = this.props.suggestions.filter(option => 
            !this.props.existingOptions.includes(option)
        );
        
        this.renderSuggestions(filteredSuggestions);
        this.show(this.suggestionsElement);
    }

    filterSuggestions(query) {
        if (!this.suggestionsElement) return;

        const { suggestions, existingOptions, allowCreate } = this.props;
        
        let filteredSuggestions;
        
        if (!query.trim()) {
            // Show all suggestions when no query
            filteredSuggestions = suggestions.filter(option => 
                !existingOptions.includes(option)
            );
        } else {
            // Filter suggestions based on query
            filteredSuggestions = suggestions.filter(option => 
                option.toLowerCase().includes(query.toLowerCase()) && 
                !existingOptions.includes(option)
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
                     onclick="window.optionsSearchSelectOption('${suggestion}')">
                    ${suggestion}
                </div>
            `).join('');
    }

    renderCreateOption(query) {
        if (!this.suggestionsElement) return;

        this.suggestionsElement.innerHTML = `
            <div class="px-3 py-2 text-sm text-blue-400 hover:bg-slate-600 cursor-pointer flex items-center" 
                 onclick="window.optionsSearchCreateOption('${query}')">
                + Create "${query}" as new option
            </div>
        `;
    }

    hideSuggestions() {
        this.hide(this.suggestionsElement);
    }

    addOption(option) {
        if (!option.trim()) return;
        
        const trimmedOption = option.trim();
        
        if (this.props.existingOptions.includes(trimmedOption)) {
            return;
        }

        this.props.onSelectOption(trimmedOption);
        this.renderOptions();
        this.hideSuggestions();
    }

    renderOptions() {
        if (!this.optionsElement) return;

        this.optionsElement.innerHTML = this.props.existingOptions.map(option => `
            <span class="inline-flex items-center px-2 py-1 bg-blue-600 text-white text-xs rounded">
                ${option}
                <button type="button" class="ml-1 text-blue-200 hover:text-white remove-option-btn" 
                        onclick="window.optionsSearchRemoveOption('${option}')">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
        `).join('');
    }

    // Public methods
    setSuggestions(suggestions) {
        this.setProps({ suggestions });
    }

    setExistingOptions(options) {
        this.setProps({ existingOptions: options });
        this.renderOptions();
    }

    clear() {
        this.inputElement.value = '';
        this.hideSuggestions();
    }
}

// Global functions for onclick handlers
window.optionsSearchSelectOption = function(option) {
    // This will be set by the component instance
    if (window.currentOptionsSearch) {
        window.currentOptionsSearch.addOption(option);
    }
};

window.optionsSearchCreateOption = function(option) {
    if (window.currentOptionsSearch) {
        window.currentOptionsSearch.addOption(option);
    }
};

window.optionsSearchRemoveOption = function(option) {
    if (window.currentOptionsSearch) {
        window.currentOptionsSearch.props.onRemoveOption(option);
        window.currentOptionsSearch.renderOptions();
    }
};
