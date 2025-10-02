import { BaseComponent } from '../ui/BaseComponent.js';
import { AttributeSearch } from '../search/AttributeSearch.js';
import { OptionsSearch } from '../search/OptionsSearch.js';

/**
 * AttributeForm Component
 * Complete form for adding new attributes
 * Similar to React/Next.js component structure
 */
export class AttributeForm extends BaseComponent {
    get defaultProps() {
        return {
            availableAttributes: [],
            commonOptions: [],
            onSave: () => {},
            onCancel: () => {},
            className: ''
        };
    }

    componentDidMount() {
        this.setupComponents();
    }

    componentWillUnmount() {
        if (this.attributeSearch) {
            this.attributeSearch.unmount();
        }
        if (this.optionsSearch) {
            this.optionsSearch.unmount();
        }
    }

    setupComponents() {
        // Setup attribute search
        const attributeContainer = this.element.querySelector('#attribute-search-container');
        if (attributeContainer) {
            this.attributeSearch = new AttributeSearch({
                placeholder: 'Type attribute name...',
                suggestions: this.props.availableAttributes,
                inputId: 'new-attribute-name-input',
                suggestionsId: 'new-attribute-name-suggestions',
                onSelectAttribute: (attribute) => {
                    this.setState({ attributeName: attribute });
                }
            });
            this.attributeSearch.mount(attributeContainer);
            window.currentAttributeSearch = this.attributeSearch;
        }

        // Setup options search
        const optionsContainer = this.element.querySelector('#options-search-container');
        if (optionsContainer) {
            this.optionsSearch = new OptionsSearch({
                placeholder: 'Type to add option...',
                suggestions: this.props.commonOptions,
                inputId: 'new-attribute-option-input',
                suggestionsId: 'new-attribute-option-suggestions',
                optionsId: 'new-attribute-options',
                onSelectOption: (option) => {
                    const currentOptions = this.state.existingOptions || [];
                    this.setState({ 
                        existingOptions: [...currentOptions, option] 
                    });
                },
                onRemoveOption: (option) => {
                    const currentOptions = this.state.existingOptions || [];
                    this.setState({ 
                        existingOptions: currentOptions.filter(opt => opt !== option) 
                    });
                }
            });
            this.optionsSearch.mount(optionsContainer);
            window.currentOptionsSearch = this.optionsSearch;
        }
    }

    componentDidUpdate(prevProps, prevState) {
        // Update components when state changes
        if (this.optionsSearch && prevState.existingOptions !== this.state.existingOptions) {
            this.optionsSearch.setExistingOptions(this.state.existingOptions || []);
        }
    }

    render() {
        const { className } = this.props;

        this.element = this.createElement('div', {
            className: `bg-slate-600 p-3 rounded border border-slate-500 ${className}`
        }, [
            // Attribute Name and Type
            this.createElement('div', {
                className: 'grid grid-cols-1 md:grid-cols-2 gap-3 mb-2'
            }, [
                this.createElement('div', {}, [
                    this.createElement('label', {
                        className: 'block text-xs text-slate-300 mb-1'
                    }, ['Attribute Name']),
                    this.createElement('div', {
                        id: 'attribute-search-container'
                    })
                ]),
                this.createElement('div', {}, [
                    this.createElement('label', {
                        className: 'block text-xs text-slate-300 mb-1'
                    }, ['Type']),
                    this.createElement('select', {
                        id: 'new-attribute-type',
                        className: 'w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm'
                    }, [
                        this.createElement('option', { value: 'custom' }, ['Custom']),
                        this.createElement('option', { value: 'taxonomy' }, ['Taxonomy'])
                    ])
                ])
            ]),

            // Options
            this.createElement('div', {
                className: 'mb-2'
            }, [
                this.createElement('label', {
                    className: 'block text-xs text-slate-300 mb-1'
                }, ['Options']),
                this.createElement('div', {
                    className: 'bg-slate-600 border border-slate-500 rounded p-2 min-h-[40px]'
                }, [
                    this.createElement('div', {
                        id: 'options-search-container'
                    })
                ])
            ]),

            // Checkboxes
            this.createElement('div', {
                className: 'flex items-center space-x-4 mb-3'
            }, [
                this.createElement('label', {
                    className: 'flex items-center'
                }, [
                    this.createElement('input', {
                        type: 'checkbox',
                        id: 'visible-checkbox',
                        className: 'w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500'
                    }),
                    this.createElement('span', {
                        className: 'ml-2 text-xs text-slate-300'
                    }, ['Visible'])
                ]),
                this.createElement('label', {
                    className: 'flex items-center'
                }, [
                    this.createElement('input', {
                        type: 'checkbox',
                        id: 'variation-checkbox',
                        className: 'w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500'
                    }),
                    this.createElement('span', {
                        className: 'ml-2 text-xs text-slate-300'
                    }, ['Variation'])
                ])
            ]),

            // Action buttons
            this.createElement('div', {
                className: 'flex justify-end'
            }, [
                this.createElement('button', {
                    type: 'button',
                    className: 'px-2 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-500 mr-2',
                    onclick: () => this.handleCancel()
                }, ['Remove']),
                this.createElement('button', {
                    type: 'button',
                    className: 'px-2 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-500',
                    onclick: () => this.handleSave()
                }, ['Add Attribute'])
            ])
        ]);

        // Mount to container if provided
        if (this.container) {
            this.container.appendChild(this.element);
        }
    }

    handleSave() {
        const attributeName = this.attributeSearch?.getValue() || '';
        const attributeType = this.element.querySelector('#new-attribute-type')?.value || 'custom';
        const existingOptions = this.state.existingOptions || [];
        const visible = this.element.querySelector('#visible-checkbox')?.checked || false;
        const variation = this.element.querySelector('#variation-checkbox')?.checked || false;

        if (!attributeName.trim()) {
            alert('Please enter an attribute name');
            return;
        }

        if (existingOptions.length === 0) {
            alert('Please add at least one option');
            return;
        }

        const attributeData = {
            name: attributeName.trim(),
            type: attributeType,
            options: existingOptions,
            visible,
            variation
        };

        this.props.onSave(attributeData);
    }

    handleCancel() {
        this.props.onCancel();
    }

    // Public methods
    setAvailableAttributes(attributes) {
        this.setProps({ availableAttributes: attributes });
        if (this.attributeSearch) {
            this.attributeSearch.setSuggestions(attributes);
        }
    }

    setCommonOptions(options) {
        this.setProps({ commonOptions: options });
        if (this.optionsSearch) {
            this.optionsSearch.setSuggestions(options);
        }
    }

    clear() {
        if (this.attributeSearch) {
            this.attributeSearch.clear();
        }
        if (this.optionsSearch) {
            this.optionsSearch.clear();
        }
        this.setState({ existingOptions: [] });
    }
}
