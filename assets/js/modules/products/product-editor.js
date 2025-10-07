
/**
 * WP POS Product Editor Manager
 * Handles complete product editing functionality including:
 * - Product form management
 * - Attributes and variations
 * - Meta data management
 * - Barcode generation
 * - JSON view
 * @version 1.0.0
 */

class ProductEditorManager {
    constructor(stateManager, uiHelpers) {
        this.state = stateManager;
        this.ui = uiHelpers;
        this.currentEditingProduct = null;
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Close button
        const closeBtn = document.getElementById('product-editor-close');
        if (closeBtn) closeBtn.addEventListener('click', () => this.closeEditor());

        // Cancel button
        const cancelBtn = document.getElementById('product-editor-cancel');
        if (cancelBtn) cancelBtn.addEventListener('click', () => this.closeEditor());

        // Save button
        const saveBtn = document.getElementById('product-editor-save');
        if (saveBtn) saveBtn.addEventListener('click', () => this.saveProductEditor());

        // Tab buttons
        const formTabBtn = document.getElementById('form-tab');
        if (formTabBtn) formTabBtn.addEventListener('click', () => this.switchToFormView());

        const jsonTabBtn = document.getElementById('json-tab');
        if (jsonTabBtn) jsonTabBtn.addEventListener('click', () => this.switchToJSONView());

        // JSON view buttons
        const cancelJsonBtn = document.getElementById('product-editor-cancel-json');
        if (cancelJsonBtn) cancelJsonBtn.addEventListener('click', () => this.closeEditor());

        const saveJsonBtn = document.getElementById('product-editor-save-json');
        if (saveJsonBtn) saveJsonBtn.addEventListener('click', () => this.saveProductEditor());

        // Meta data button
        const addMetaBtn = document.getElementById('add-meta-data');
        if (addMetaBtn) addMetaBtn.addEventListener('click', () => this.addMetaDataRow());

        // Accordion toggles
        const metaToggle = document.getElementById('meta-data-accordion-toggle');
        if (metaToggle) metaToggle.addEventListener('click', () => this.toggleMetaDataAccordion());

        const attrToggle = document.getElementById('attributes-accordion-toggle');
        if (attrToggle) attrToggle.addEventListener('click', () => this.toggleAttributesAccordion());

        const varToggle = document.getElementById('variations-accordion-toggle');
        if (varToggle) varToggle.addEventListener('click', () => this.toggleVariationsAccordion());

        // Add attribute button
        const addAttrBtn = document.getElementById('add-attribute');
        if (addAttrBtn) addAttrBtn.addEventListener('click', () => this.addAttributeRow());

        // Add variation button
        const addVarBtn = document.getElementById('add-variation');
        if (addVarBtn) addVarBtn.addEventListener('click', () => this.addVariationRow());

        // Barcode generation button
        const barcodeBtn = document.getElementById('generate-barcode-btn');
        if (barcodeBtn) barcodeBtn.addEventListener('click', () => this.handleBarcodeGeneration());

        // Attribute option management - event delegation
        this.setupAttributeEventDelegation();
    }

    setupAttributeEventDelegation() {
        // Focus event for attribute option inputs
        document.addEventListener('focus', (e) => {
            if (e.target.id && e.target.id.startsWith('attribute-option-input-')) {
                const attributeIndex = e.target.id.split('-')[3];
                this.showAttributeSuggestions(attributeIndex, e.target.value);
            }
        }, true);

        // Input event for attribute option inputs
        document.addEventListener('input', (e) => {
            if (e.target.id && e.target.id.startsWith('attribute-option-input-')) {
                const attributeIndex = e.target.id.split('-')[3];
                this.showAttributeSuggestions(attributeIndex, e.target.value);
            }
        });

        // Keypress event for attribute option inputs
        document.addEventListener('keypress', (e) => {
            if (e.target.id && e.target.id.startsWith('attribute-option-input-')) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const attributeIndex = e.target.id.split('-')[3];
                    this.addAttributeOption(attributeIndex, e.target.value);
                } else if (e.key === 'Escape') {
                    const attributeIndex = e.target.id.split('-')[3];
                    this.hideAttributeSuggestions(attributeIndex);
                }
            }
        });

        // Click outside to hide suggestions
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.relative')) {
                document.querySelectorAll('[id^="attribute-option-suggestions-"]').forEach(el => {
                    el.classList.add('hidden');
                });
            }
        });

        // Remove option button clicks
        document.addEventListener('click', (e) => {
            if (e.target.closest('.remove-option-btn')) {
                const button = e.target.closest('.remove-option-btn');
                const optionSpan = button.closest('span[data-option]');
                if (optionSpan) {
                    optionSpan.remove();
                }
            }
        });
    }

    async openProductEditor(productId = null) {
        // Only allow editing existing products
        if (!productId) {
            this.ui.showToast('Product ID is required. Product creation is disabled.');
            return;
        }

        this.currentEditingProduct = null;
        const modal = document.getElementById('product-editor-modal');
        modal.classList.remove('hidden');

        const titleEl = document.getElementById('product-editor-title');
        const saveBtn = document.getElementById('product-editor-save');

        // Clear form
        this.clearProductEditorForm();

        // Start with form view by default
        this.switchToFormView();

        // EDIT MODE ONLY
        titleEl.textContent = 'Loading Product...';
        saveBtn.textContent = 'Save Changes';
        saveBtn.setAttribute('data-mode', 'edit');

        try {
            // Get product details
            const response = await fetch(`api/product-edit-simple.php?action=get_product_details&id=${productId}`);
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message);

            this.currentEditingProduct = result.data;
            titleEl.textContent = `Edit: ${result.data.name}`;

            // Populate form
            this.populateProductEditorForm(result.data);

            // Load tax classes
            await this.loadTaxClasses();

        } catch (error) {
            console.error("Error loading product details:", error);
            titleEl.textContent = 'Error Loading Product';
            document.getElementById('product-editor-status').textContent = `Error: ${error.message}`;
            document.getElementById('product-editor-status').className = 'text-sm text-right h-5 mt-2 text-red-400';
        }
    }

    closeEditor() {
        document.getElementById('product-editor-modal').classList.add('hidden');
    }

    clearProductEditorForm() {
        document.getElementById('product-name').value = '';
        document.getElementById('product-sku').value = '';
        document.getElementById('product-barcode').value = '';
        document.getElementById('product-regular-price').value = '';
        document.getElementById('product-sale-price').value = '';
        document.getElementById('product-status').value = 'publish';
        document.getElementById('product-featured').checked = false;
        document.getElementById('product-tax-class').value = '';
        document.querySelector('input[name="tax-status"][value="taxable"]').checked = true;
        document.getElementById('product-stock-quantity').value = '';
        document.getElementById('product-manage-stock').checked = false;

        // Clear meta data
        document.getElementById('product-meta-data').innerHTML = '';

        // Clear attributes
        document.getElementById('product-attributes').innerHTML = '';

        // Clear variations and hide variations section
        document.getElementById('product-variations').innerHTML = '';
        document.getElementById('variations-section').classList.add('hidden');
    }

    populateProductEditorForm(product) {
        document.getElementById('product-name').value = product.name || '';
        document.getElementById('product-sku').value = product.sku || '';
        document.getElementById('product-barcode').value = product.barcode || '';
        document.getElementById('product-regular-price').value = product.regular_price || '';
        document.getElementById('product-sale-price').value = product.sale_price || '';
        document.getElementById('product-status').value = product.status || 'publish';
        document.getElementById('product-featured').checked = product.featured || false;
        document.getElementById('product-tax-class').value = product.tax_class || '';
        document.querySelector(`input[name="tax-status"][value="${product.tax_status || 'taxable'}"]`).checked = true;
        document.getElementById('product-stock-quantity').value = product.stock_quantity || '';
        document.getElementById('product-manage-stock').checked = product.manage_stock || false;

        // Populate meta data
        this.populateMetaData(product.meta_data || []);

        // Populate attributes
        this.populateAttributes(product.attributes || []);

        // Show variable product sections if applicable
        if (product.type === 'variable') {
            document.getElementById('variations-section').classList.remove('hidden');
            this.populateVariations(product.variations || []);
        } else {
            document.getElementById('variations-section').classList.add('hidden');
        }
    }

    populateMetaData(metaData) {
        const container = document.getElementById('product-meta-data');
        container.innerHTML = '';

        metaData.forEach((meta) => {
            const metaRow = document.createElement('div');
            metaRow.className = 'flex gap-2 items-center';
            metaRow.innerHTML = `
                <input type="text" placeholder="Meta Key" value="${meta.key}" class="flex-1 px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                <input type="text" placeholder="Meta Value" value="${meta.value}" class="flex-1 px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                <button type="button" onclick="window.productEditorManager.removeMetaDataRow(this)" class="px-2 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-500">Remove</button>
            `;
            container.appendChild(metaRow);
        });
    }

    populateAttributes(attributes) {
        const container = document.getElementById('product-attributes');
        container.innerHTML = '';

        attributes.forEach((attribute, index) => {
            const attrDiv = document.createElement('div');
            attrDiv.className = 'bg-slate-600 p-3 rounded border border-slate-500';
            attrDiv.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Attribute Name</label>
                        <input type="text" value="${attribute.friendly_name || attribute.name}" class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm" readonly>
                        <div class="text-xs text-slate-500 mt-1">Technical: ${attribute.name}</div>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Type</label>
                        <input type="text" value="${attribute.type}" class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm" readonly>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Options</label>
                        <div class="bg-slate-600 border border-slate-500 rounded p-2 min-h-[40px]">
                            <div id="attribute-options-${index}" class="flex flex-wrap gap-1 mb-2" data-attribute-index="${index}" data-attribute-name="${(attribute.friendly_name || attribute.name).toLowerCase()}" data-original-options='${JSON.stringify(attribute.friendly_options || attribute.options)}'>
                                ${(attribute.friendly_options || attribute.options).map(option => `
                                    <span class="inline-flex items-center px-2 py-1 bg-blue-600 text-white text-xs rounded" data-option="${option}">
                                        ${option}
                                        <button type="button" class="ml-1 text-blue-200 hover:text-white remove-option-btn">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </span>
                                `).join('')}
                            </div>
                            <div class="relative">
                                <input type="text" id="attribute-option-input-${index}" placeholder="Type to add option..." class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm focus:border-blue-500 focus:outline-none">
                                <div id="attribute-option-suggestions-${index}" class="absolute top-full left-0 right-0 bg-slate-700 border border-slate-500 rounded mt-1 max-h-32 overflow-y-auto hidden z-10">
                                    <!-- Suggestions will be populated here -->
                                </div>
                            </div>
                        </div>
                        ${attribute.friendly_options ? `<div class="text-xs text-slate-500 mt-1">Technical IDs: ${attribute.options.join(', ')}</div>` : ''}
                    </div>
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center">
                            <input type="checkbox" ${attribute.visible ? 'checked' : ''} class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500">
                            <span class="ml-2 text-xs text-slate-300">Visible</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" ${attribute.variation ? 'checked' : ''} class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500">
                            <span class="ml-2 text-xs text-slate-300">Variation</span>
                        </label>
                    </div>
                </div>
            `;
            container.appendChild(attrDiv);
        });
    }

    populateVariations(variations) {
        const container = document.getElementById('product-variations');
        container.innerHTML = '';

        variations.forEach((variation) => {
            const variationRow = document.createElement('div');
            variationRow.className = 'bg-slate-600 p-3 rounded border border-slate-500';

            // Create specific attribute values display
            const attributesHtml = Object.entries(variation.attributes || {})
                .map(([key, value]) => {
                    let friendlyKey = key;
                    if (key.startsWith('pa_')) {
                        friendlyKey = key.replace('pa_', '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    } else if (key.startsWith('_')) {
                        friendlyKey = key.replace('_', '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    }
                    return `<span class="inline-flex items-center px-2 py-1 bg-blue-600 text-white text-xs rounded mr-1 mb-1">${friendlyKey}: ${value}</span>`;
                })
                .join('');

            variationRow.innerHTML = `
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <h4 class="font-semibold text-slate-200 text-sm mb-2">${variation.parent_name || 'Product'} Variation</h4>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-xs text-slate-400">ID: ${variation.id}</span>
                            <span class="text-xs text-slate-400">Status: ${variation.status || 'publish'}</span>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            ${attributesHtml || '<span class="text-xs text-slate-500 italic">No attributes</span>'}
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-2">
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">SKU</label>
                        <input type="text" value="${variation.sku || ''}" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Price</label>
                        <input type="number" step="0.01" value="${variation.price || ''}" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Stock</label>
                        <input type="number" value="${variation.stock_quantity || ''}" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                    </div>
                </div>
            `;
            container.appendChild(variationRow);
        });
    }

    async loadTaxClasses() {
        try {
            const response = await fetch('api/product-edit-simple.php?action=get_tax_classes');
            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();
            if (!result.success) throw new Error(result.data.message);

            const select = document.getElementById('product-tax-class');
            select.innerHTML = '';

            result.tax_classes.forEach(taxClass => {
                const option = document.createElement('option');
                option.value = taxClass.slug;
                option.textContent = taxClass.name;
                select.appendChild(option);
            });
        } catch (error) {
            console.error('Error loading tax classes:', error);
        }
    }

    addMetaDataRow() {
        const container = document.getElementById('product-meta-data');
        const metaRow = document.createElement('div');
        metaRow.className = 'flex gap-2 items-center';
        metaRow.innerHTML = `
            <input type="text" placeholder="Meta Key" class="flex-1 px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
            <input type="text" placeholder="Meta Value" class="flex-1 px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
            <button type="button" onclick="window.productEditorManager.removeMetaDataRow(this)" class="px-2 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-500">Remove</button>
        `;
        container.appendChild(metaRow);
    }

    removeMetaDataRow(button) {
        button.parentElement.remove();
    }

    async addAttributeRow() {
        const container = document.getElementById('product-attributes');
        const attributeRow = document.createElement('div');
        attributeRow.className = 'bg-slate-600 p-3 rounded border border-slate-500';
        attributeRow.innerHTML = `
            <div class="text-center text-slate-400 py-4">
                Adding new attributes is not yet supported. Please use WooCommerce admin.
            </div>
            <div class="flex justify-end mt-2">
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="px-2 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-500">Remove</button>
            </div>
        `;
        container.appendChild(attributeRow);
    }

    addVariationRow() {
        const container = document.getElementById('product-variations');
        const variationRow = document.createElement('div');
        variationRow.className = 'bg-slate-600 p-3 rounded border border-slate-500';
        variationRow.innerHTML = `
            <div class="text-center text-slate-400 py-4">
                Adding new variations is not yet supported. Please use WooCommerce admin.
            </div>
            <div class="flex justify-end mt-2">
                <button type="button" onclick="this.parentElement.parentElement.remove()" class="px-2 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-500">Remove</button>
            </div>
        `;
        container.appendChild(variationRow);
    }

    addAttributeOption(attributeIndex, option) {
        if (!option.trim()) return;

        const optionsContainer = document.getElementById(`attribute-options-${attributeIndex}`);
        const input = document.getElementById(`attribute-option-input-${attributeIndex}`);

        // Check if option already exists
        const existingOptions = Array.from(optionsContainer.querySelectorAll('span')).map(el => 
            el.textContent.trim().split('×')[0].trim()
        );
        if (existingOptions.includes(option.trim())) {
            input.value = '';
            return;
        }

        // Add new option tag
        const optionTag = document.createElement('span');
        optionTag.className = 'inline-flex items-center px-2 py-1 bg-blue-600 text-white text-xs rounded';
        optionTag.setAttribute('data-option', option);
        optionTag.innerHTML = `
            ${option}
            <button type="button" class="ml-1 text-blue-200 hover:text-white remove-option-btn">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        optionsContainer.appendChild(optionTag);

        // Clear input
        input.value = '';
        this.hideAttributeSuggestions(attributeIndex);

        // Refresh suggestions
        this.refreshAttributeSuggestions(attributeIndex);
    }

    removeAttributeOption(attributeIndex, option) {
        const optionsContainer = document.getElementById(`attribute-options-${attributeIndex}`);
        if (!optionsContainer) return;

        const optionElements = optionsContainer.querySelectorAll('span');
        optionElements.forEach(element => {
            const textContent = element.textContent.trim();
            const optionText = textContent.replace(/×$/, '').trim();

            if (optionText === option) {
                element.remove();
            }
        });

        // Refresh suggestions
        this.refreshAttributeSuggestions(attributeIndex);
    }

    showAttributeSuggestions(attributeIndex, query) {
        const suggestionsContainer = document.getElementById(`attribute-option-suggestions-${attributeIndex}`);
        const optionsContainer = document.getElementById(`attribute-options-${attributeIndex}`);
        
        // Get original options from database
        const originalOptionsJson = optionsContainer.getAttribute('data-original-options') || '[]';
        const originalOptions = JSON.parse(originalOptionsJson);
        const suggestions = originalOptions;

        // Get currently added options
        const existingOptions = Array.from(optionsContainer.querySelectorAll('span[data-option]')).map(el => 
            el.getAttribute('data-option')
        );

        // Filter suggestions
        let filteredSuggestions;
        if (!query.trim()) {
            filteredSuggestions = suggestions;
        } else {
            filteredSuggestions = suggestions.filter(option => 
                option.toLowerCase().includes(query.toLowerCase())
            );
        }

        if (filteredSuggestions.length > 0) {
            suggestionsContainer.innerHTML = filteredSuggestions.map(suggestion => {
                const isAlreadyAdded = existingOptions.includes(suggestion);
                const bgColor = isAlreadyAdded ? 'bg-green-600' : 'hover:bg-slate-600';
                const textColor = isAlreadyAdded ? 'text-white' : 'text-slate-200';
                const icon = isAlreadyAdded ? 
                    '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>' : '';
                const action = isAlreadyAdded ? 
                    `window.productEditorManager.removeAttributeOption(${attributeIndex}, '${suggestion}')` : 
                    `window.productEditorManager.addAttributeOption(${attributeIndex}, '${suggestion}')`;

                return `
                    <div class="px-3 py-2 text-sm ${textColor} ${bgColor} cursor-pointer flex items-center" onclick="${action}">
                        ${icon}${suggestion}
                    </div>
                `;
            }).join('');
            suggestionsContainer.classList.remove('hidden');
        } else if (query.trim()) {
            suggestionsContainer.innerHTML = `
                <div class="px-3 py-2 text-sm text-blue-400 hover:bg-slate-600 cursor-pointer flex items-center" onclick="window.productEditorManager.addAttributeOption(${attributeIndex}, '${query}')">
                    + Create "${query}" as new option
                </div>
            `;
            suggestionsContainer.classList.remove('hidden');
        } else {
            this.hideAttributeSuggestions(attributeIndex);
        }
    }

    hideAttributeSuggestions(attributeIndex) {
        const suggestionsContainer = document.getElementById(`attribute-option-suggestions-${attributeIndex}`);
        if (suggestionsContainer) {
            suggestionsContainer.classList.add('hidden');
        }
    }

    refreshAttributeSuggestions(attributeIndex) {
        const input = document.getElementById(`attribute-option-input-${attributeIndex}`);
        if (input) {
            this.showAttributeSuggestions(attributeIndex, input.value);
        }
    }

    toggleMetaDataAccordion() {
        const content = document.getElementById('meta-data-accordion-content');
        const icon = document.getElementById('meta-data-accordion-icon');

        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }

    toggleAttributesAccordion() {
        const content = document.getElementById('attributes-accordion-content');
        const icon = document.getElementById('attributes-accordion-icon');

        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }

    toggleVariationsAccordion() {
        const content = document.getElementById('variations-accordion-content');
        const icon = document.getElementById('variations-accordion-icon');

        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.style.transform = 'rotate(180deg)';
        } else {
            content.classList.add('hidden');
            icon.style.transform = 'rotate(0deg)';
        }
    }

    switchToFormView() {
        document.getElementById('form-view').classList.remove('hidden');
        document.getElementById('json-view').classList.add('hidden');

        // Update tab styling
        document.getElementById('form-tab').className = 'px-4 py-2 text-sm font-medium text-slate-300 border-b-2 border-blue-500 bg-slate-700 rounded-t-lg';
        document.getElementById('json-tab').className = 'px-4 py-2 text-sm font-medium text-slate-400 border-b-2 border-transparent hover:text-slate-300 hover:border-slate-500';
    }

    switchToJSONView() {
        document.getElementById('form-view').classList.add('hidden');
        document.getElementById('json-view').classList.remove('hidden');

        // Update tab styling
        document.getElementById('form-tab').className = 'px-4 py-2 text-sm font-medium text-slate-400 border-b-2 border-transparent hover:text-slate-300 hover:border-slate-500';
        document.getElementById('json-tab').className = 'px-4 py-2 text-sm font-medium text-slate-300 border-b-2 border-blue-500 bg-slate-700 rounded-t-lg';

        // Update the full JSON preview
        this.updateFullJSONPreview();
    }

    updateFullJSONPreview() {
        if (!this.currentEditingProduct) return;

        const jsonString = JSON.stringify(this.currentEditingProduct, null, 2);
        const highlightedJSON = this.ui.highlightJSON(jsonString);
        document.getElementById('json-full-preview').innerHTML = highlightedJSON;
    }

    getProductEditorFormData() {
        const metaData = [];
        document.querySelectorAll('#product-meta-data > div').forEach(row => {
            const keyInput = row.querySelector('input[placeholder="Meta Key"]');
            const valueInput = row.querySelector('input[placeholder="Meta Value"]');
            if (keyInput.value.trim() && valueInput.value.trim()) {
                metaData.push({
                    key: keyInput.value.trim(),
                    value: valueInput.value.trim()
                });
            }
        });

        // Get variations data if it's a variable product
        const variations = [];
        if (this.currentEditingProduct?.type === 'variable') {
            document.querySelectorAll('#variations-list > div[data-variation-id]').forEach(variationDiv => {
                const variationId = variationDiv.getAttribute('data-variation-id');
                const variationData = {
                    id: parseInt(variationId),
                    sku: variationDiv.querySelector('[data-field="sku"]').value,
                    price: variationDiv.querySelector('[data-field="price"]').value,
                    sale_price: variationDiv.querySelector('[data-field="sale_price"]').value,
                    stock_quantity: variationDiv.querySelector('[data-field="stock_quantity"]').value,
                    stock_status: variationDiv.querySelector('[data-field="stock_status"]').value
                };
                variations.push(variationData);
            });
        }

        return {
            id: this.currentEditingProduct?.id,
            name: document.getElementById('product-name').value,
            sku: document.getElementById('product-sku').value,
            barcode: document.getElementById('product-barcode').value,
            regular_price: document.getElementById('product-regular-price').value,
            sale_price: document.getElementById('product-sale-price').value,
            status: document.getElementById('product-status').value,
            featured: document.getElementById('product-featured').checked,
            tax_class: document.getElementById('product-tax-class').value,
            tax_status: document.querySelector('input[name="tax-status"]:checked').value,
            stock_quantity: document.getElementById('product-stock-quantity').value,
            manage_stock: document.getElementById('product-manage-stock').checked,
            meta_data: metaData,
            variations: variations
        };
    }

    async saveProductEditor() {
        const statusEl = document.getElementById('product-editor-status');
        const saveBtn = document.getElementById('product-editor-save');
        const titleEl = document.getElementById('product-editor-title');

        statusEl.textContent = 'Saving...';
        statusEl.className = 'text-sm text-right h-5 mt-2 text-slate-400';

        const formData = this.getProductEditorFormData();
        formData.nonce = this.state.getState('nonces.productEdit');

        try {
            // Detect mode from save button attribute
            const mode = saveBtn.getAttribute('data-mode');

            if (mode === 'create') {
                // CREATE MODE - create new product
                const payload = {
                    action: 'create_product',
                    ...formData
                };

                // Remove id field for creation
                delete payload.id;

                const response = await fetch('api/product-edit-simple.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) throw new Error(`Server responded with ${response.status}`);
                const result = await response.json();
                if (!result.success) throw new Error(result.data?.message || result.message);

                // SUCCESS - Product created
                const newProductId = result.data?.product_id || result.product_id;

                statusEl.textContent = 'Product created successfully!';
                statusEl.className = 'text-sm text-right h-5 mt-2 text-green-400';

                // Update to edit mode with new product
                this.currentEditingProduct = {
                    id: newProductId,
                    ...formData
                };

                titleEl.textContent = `Edit: ${formData.name}`;
                saveBtn.textContent = 'Save Changes';
                saveBtn.setAttribute('data-mode', 'edit');

            } else {
                // EDIT MODE - update existing product
                if (!this.currentEditingProduct) {
                    throw new Error('No product loaded for editing');
                }

                const payload = {
                    action: 'update_product',
                    ...formData
                };

                const response = await fetch('api/product-edit-simple.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) throw new Error(`Server responded with ${response.status}`);
                const result = await response.json();
                
                // Log detailed debug information
                console.log('=== PRODUCT UPDATE DEBUG ===');
                console.log('Success:', result.success);
                console.log('Message:', result.message || result.error);
                if (result.debug) {
                    console.log('Debug Info:', result.debug);
                    console.log('Current Step:', result.debug.step);
                    if (result.debug.exception_class) {
                        console.error('Exception:', result.debug.exception_class);
                        console.error('File:', result.debug.exception_file);
                        console.error('Line:', result.debug.exception_line);
                        console.error('Stack Trace:', result.debug.stack_trace);
                    }
                }
                console.log('=========================');
                
                if (!result.success) {
                    const errorMsg = result.error || result.data?.message || result.message || 'Unknown error';
                    throw new Error(errorMsg);
                }

                // EDIT MODE SUCCESS
                statusEl.textContent = 'Product updated successfully!';
                statusEl.className = 'text-sm text-right h-5 mt-2 text-green-400';
                
                // Auto-refresh products list to show updated stock/details
                console.log('Refreshing products after save...');
                if (window.productsManager && typeof window.productsManager.fetchProducts === 'function') {
                    await window.productsManager.fetchProducts();
                }
            }

        } catch (error) {
            console.error('Error saving product:', error);
            statusEl.textContent = `Error: ${error.message}`;
            statusEl.className = 'text-sm text-right h-5 mt-2 text-red-400';
        }
    }

    async handleBarcodeGeneration() {
        if (!this.currentEditingProduct || !this.currentEditingProduct.id) {
            this.ui.showToast('No product loaded. Please save the product first.');
            return;
        }

        const btn = document.getElementById('generate-barcode-btn');
        const barcodeInput = document.getElementById('product-barcode');
        const btnIcon = btn.querySelector('i');
        const btnText = btn.querySelector('span');

        // Save original state
        const originalIcon = btnIcon.className;
        const originalText = btnText.textContent;

        // Set loading state
        btn.disabled = true;
        btnIcon.className = 'fas fa-spinner fa-spin';
        btnText.textContent = 'Generating...';

        try {
            const response = await fetch('api/barcode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'generate_barcode',
                    product_id: this.currentEditingProduct.id,
                    nonce: this.state.getState('nonces.barcode')
                })
            });

            if (!response.ok) {
                throw new Error(`Server responded with ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.barcode) {
                barcodeInput.value = result.barcode;
                this.ui.showToast('Barcode generated successfully!');
            } else {
                throw new Error(result.message || 'Failed to generate barcode');
            }
        } catch (error) {
            console.error('Barcode generation error:', error);
            let errorMessage = 'Failed to generate barcode';

            if (error.message.includes('401') || error.message.includes('403')) {
                errorMessage = 'Authentication failed. Please log in again.';
            } else if (error.message.includes('404')) {
                errorMessage = 'Product not found.';
            } else if (error.message.includes('500')) {
                errorMessage = 'Server error. Please try again later.';
            } else if (error.message) {
                errorMessage = error.message;
            }

            this.ui.showToast(errorMessage);
        } finally {
            // Restore button state
            btn.disabled = false;
            btnIcon.className = originalIcon;
            btnText.textContent = originalText;
        }
    }
}

// Export for global access
window.ProductEditorManager = ProductEditorManager;