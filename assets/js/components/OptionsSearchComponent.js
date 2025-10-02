/**
 * Options Search Component
 * Reusable component for searching and selecting options with suggestions
 */
class OptionsSearchComponent extends BaseComponent {
    constructor(options = {}) {
        super(options);
    }

    get defaultOptions() {
        return {
            placeholder: 'Type to search...',
            suggestions: [],
            maxSuggestions: 8,
            allowCreateNew: true,
            createNewText: 'Create new',
            onSelect: null,
            onRemove: null,
            className: 'options-search-component',
            inputClassName: 'w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm focus:border-blue-500 focus:outline-none',
            suggestionsClassName: 'absolute top-full left-0 right-0 bg-slate-700 border border-slate-500 rounded mt-1 max-h-32 overflow-y-auto hidden z-10',
            tagClassName: 'inline-flex items-center px-2 py-1 bg-blue-600 text-white text-xs rounded'
        };
    }

    init() {
        this.createHTML();
        this.bindEvents();
    }

    createHTML() {
        this.element = this.createElement('div', this.options.className);
        
        this.element.innerHTML = `
            <div class="bg-slate-600 border border-slate-500 rounded p-2 min-h-[40px]">
                <div class="options-container flex flex-wrap gap-1 mb-2">
                    <!-- Selected options will appear here -->
                </div>
                <div class="relative">
                    <input type="text" 
                           class="${this.options.inputClassName}" 
                           placeholder="${this.options.placeholder}"
                           autocomplete="off">
                    <div class="${this.options.suggestionsClassName}">
                        <!-- Suggestions will be populated here -->
                    </div>
                </div>
            </div>
        `;

        this.input = this.element.querySelector('input');
        this.optionsContainer = this.element.querySelector('.options-container');
        this.suggestionsContainer = this.element.querySelector('.absolute');
    }

    bindEvents() {
        // Input events
        this.addEventListener(this.input, 'focus', () => {
            this.showSuggestions();
        });

        this.addEventListener(this.input, 'input', this.debounce((e) => {
            this.showSuggestions(e.target.value);
        }, 150));

        this.addEventListener(this.input, 'keypress', (e) => {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                this.addOption(e.target.value);
                e.target.value = '';
            } else if (e.key === 'Escape') {
                this.hideSuggestions();
            }
        });

        // Click outside to hide suggestions
        this.addEventListener(document, 'click', (e) => {
            if (!this.element.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }

    showSuggestions(query = '') {
        const filteredSuggestions = this.getFilteredSuggestions(query);
        
        if (filteredSuggestions.length > 0) {
            this.renderSuggestions(filteredSuggestions, query);
            this.show(this.suggestionsContainer);
        } else if (query.trim() && this.options.allowCreateNew) {
            this.renderCreateNewOption(query);
            this.show(this.suggestionsContainer);
        } else {
            this.hideSuggestions();
        }
    }

    getFilteredSuggestions(query) {
        const existingOptions = this.getExistingOptions();
        
        if (!query.trim()) {
            return this.options.suggestions.filter(option => 
                !existingOptions.includes(option)
            );
        }

        return this.options.suggestions.filter(option => 
            option.toLowerCase().includes(query.toLowerCase()) && 
            !existingOptions.includes(option)
        ).slice(0, this.options.maxSuggestions);
    }

    renderSuggestions(suggestions, query) {
        this.suggestionsContainer.innerHTML = suggestions.map(suggestion => {
            const isHighlighted = query && suggestion.toLowerCase().includes(query.toLowerCase());
            const highlightClass = isHighlighted ? 'bg-blue-600 text-white' : 'text-slate-200 hover:bg-slate-600';
            
            return `
                <div class="px-3 py-2 text-sm ${highlightClass} cursor-pointer flex items-center" 
                     onclick="this.selectOption('${suggestion}')">
                    ${suggestion}
                </div>
            `;
        }).join('');
    }

    renderCreateNewOption(query) {
        this.suggestionsContainer.innerHTML = `
            <div class="px-3 py-2 text-sm text-blue-400 hover:bg-slate-600 cursor-pointer flex items-center" 
                 onclick="this.selectOption('${query}')">
                + ${this.options.createNewText} "${query}"
            </div>
        `;
    }

    selectOption(option) {
        this.addOption(option);
        this.input.value = '';
        this.hideSuggestions();
    }

    addOption(option) {
        if (!option.trim()) return;
        
        const trimmedOption = option.trim();
        const existingOptions = this.getExistingOptions();
        
        if (existingOptions.includes(trimmedOption)) {
            return;
        }

        const optionTag = this.createElement('span', this.options.tagClassName);
        optionTag.innerHTML = `
            ${trimmedOption}
            <button type="button" class="ml-1 text-blue-200 hover:text-white remove-option-btn">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;

        // Add remove functionality
        const removeBtn = optionTag.querySelector('.remove-option-btn');
        this.addEventListener(removeBtn, 'click', () => {
            this.removeOption(optionTag, trimmedOption);
        });

        this.optionsContainer.appendChild(optionTag);
        
        // Callback
        if (this.options.onSelect) {
            this.options.onSelect(trimmedOption);
        }
    }

    removeOption(optionTag, option) {
        optionTag.remove();
        
        // Callback
        if (this.options.onRemove) {
            this.options.onRemove(option);
        }
    }

    getExistingOptions() {
        return Array.from(this.optionsContainer.querySelectorAll('span')).map(el => 
            el.textContent.trim().split('×')[0].trim()
        );
    }

    hideSuggestions() {
        this.hide(this.suggestionsContainer);
    }

    // Public API methods
    getSelectedOptions() {
        return this.getExistingOptions();
    }

    setSelectedOptions(options) {
        // Clear existing options
        this.optionsContainer.innerHTML = '';
        
        // Add new options
        options.forEach(option => {
            this.addOption(option);
        });
    }

    setSuggestions(suggestions) {
        this.options.suggestions = suggestions;
    }

    clear() {
        this.optionsContainer.innerHTML = '';
        this.input.value = '';
        this.hideSuggestions();
    }

    focus() {
        this.input.focus();
    }
}
