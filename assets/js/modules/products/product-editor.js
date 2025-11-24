
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

        // Image upload event listeners
        const featuredImageInput = document.getElementById('featured-image-input');
        if (featuredImageInput) featuredImageInput.addEventListener('change', (e) => this.handleFeaturedImageUpload(e));

        const galleryImagesInput = document.getElementById('gallery-images-input');
        if (galleryImagesInput) galleryImagesInput.addEventListener('change', (e) => this.handleGalleryImagesUpload(e));

        const removeFeaturedBtn = document.getElementById('remove-featured-image-btn');
        if (removeFeaturedBtn) removeFeaturedBtn.addEventListener('click', () => this.removeFeaturedImage());

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
        this.currentEditingProduct = null;
        const modal = document.getElementById('product-editor-modal');
        modal.classList.remove('hidden');

        const titleEl = document.getElementById('product-editor-title');
        const saveBtn = document.getElementById('product-editor-save');

        // Clear form
        this.clearProductEditorForm();

        // Start with form view by default
        this.switchToFormView();

        if (!productId) {
            // CREATE MODE
            titleEl.textContent = 'Create New Product';
            saveBtn.textContent = 'Create Product';
            saveBtn.setAttribute('data-mode', 'create');
            
            // Load tax classes for new product
            await this.loadTaxClasses();
            
            // Setup image interface for create mode
            this.setupImageInterface('create');
            
            // Show info message
            document.getElementById('product-editor-status').textContent = 'Note: Images can be added after creating the product';
            document.getElementById('product-editor-status').className = 'text-sm text-right h-5 mt-2 text-blue-400';
        } else {
            // EDIT MODE
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
                
                // Setup image interface for edit mode
                this.setupImageInterface('edit');

            } catch (error) {
                console.error("Error loading product details:", error);
                titleEl.textContent = 'Error Loading Product';
                document.getElementById('product-editor-status').textContent = `Error: ${error.message}`;
                document.getElementById('product-editor-status').className = 'text-sm text-right h-5 mt-2 text-red-400';
            }
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
                        <input type="text" value="${variation.sku || ''}" data-field="sku" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Price</label>
                        <input type="number" step="0.01" value="${variation.price || ''}" data-field="price" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-300 mb-1">Stock</label>
                        <input type="number" value="${variation.stock_quantity || ''}" data-field="stock_quantity" class="w-full px-2 py-1 bg-slate-600 text-slate-200 rounded border border-slate-500 text-sm">
                    </div>
                </div>
                <div class="mt-2">
                    <label class="flex items-center">
                        <input type="checkbox" ${variation.manage_stock ? 'checked' : ''} data-field="manage_stock" class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500">
                        <span class="ml-2 text-xs text-slate-300">Manage stock</span>
                    </label>
                </div>
            `;
            variationRow.setAttribute('data-variation-id', variation.id);
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
        const existingAttributes = container.querySelectorAll('.bg-slate-600').length;
        const newIndex = existingAttributes;
        
        // Fetch available attributes from API
        let availableAttributes = [];
        try {
            const response = await fetch('api/product-edit-simple.php?action=get_available_attributes');
            if (response.ok) {
                const result = await response.json();
                if (result.success && result.attributes) {
                    availableAttributes = result.attributes;
                }
            }
        } catch (error) {
            console.error('Error fetching available attributes:', error);
        }
        
        const attributeRow = document.createElement('div');
        attributeRow.className = 'bg-slate-600 p-3 rounded border border-slate-500';
        attributeRow.setAttribute('data-new-attribute', 'true');
        attributeRow.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
                <div>
                    <label class="block text-xs text-slate-300 mb-1">Attribute Name *</label>
                    <div class="relative">
                        <input type="text"
                               class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm focus:border-blue-500 focus:outline-none"
                               placeholder="Type to search or create new..."
                               data-attribute-name-input="true"
                               data-attribute-index="${newIndex}"
                               data-available-attributes='${JSON.stringify(availableAttributes)}'
                               autocomplete="off">
                        <div class="absolute top-full left-0 right-0 bg-slate-700 border border-slate-500 rounded mt-1 max-h-48 overflow-y-auto hidden z-20"
                             data-attribute-name-suggestions="${newIndex}">
                            <!-- Suggestions will be populated here -->
                        </div>
                    </div>
                    <div class="text-xs text-slate-500 mt-1">Type to search existing or create new attribute</div>
                </div>
                <div>
                    <label class="block text-xs text-slate-300 mb-1">Type</label>
                    <input type="text" value="Custom" class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm" readonly data-attribute-type="custom">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-2">
                <div>
                    <label class="block text-xs text-slate-300 mb-1">Options</label>
                    <div class="bg-slate-600 border border-slate-500 rounded p-2 min-h-[40px]">
                        <div id="new-attribute-options-${newIndex}" class="flex flex-wrap gap-1 mb-2" data-attribute-index="${newIndex}">
                            <!-- Options will be added here -->
                        </div>
                        <div class="relative">
                            <input type="text"
                                   id="new-attribute-option-input-${newIndex}"
                                   placeholder="Type option and press Enter..."
                                   class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm focus:border-blue-500 focus:outline-none"
                                   data-new-attribute-input="${newIndex}">
                            <div class="text-xs text-slate-500 mt-1">Press Enter or comma to add each option</div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col justify-center space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" checked class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500" data-visible-checkbox="true">
                        <span class="ml-2 text-xs text-slate-300">Visible on product page</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500" data-variation-checkbox="true">
                        <span class="ml-2 text-xs text-slate-300">Used for variations</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end mt-2">
                <button type="button" onclick="this.closest('.bg-slate-600').remove()" class="px-3 py-1 bg-red-600 text-white text-sm rounded hover:bg-red-500">
                    <i class="fas fa-trash mr-1"></i>Remove Attribute
                </button>
            </div>
        `;
        container.appendChild(attributeRow);
        
        // Setup event listener for new attribute option input
        const optionInput = attributeRow.querySelector(`#new-attribute-option-input-${newIndex}`);
        if (optionInput) {
            optionInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    this.addNewAttributeOption(newIndex, optionInput.value.trim());
                    optionInput.value = '';
                }
            });
        }
        
        // Setup searchable attribute name input
        this.setupAttributeNameSearchHandler(attributeRow, newIndex, availableAttributes);
        
        // Show success message
        this.ui.showToast('New attribute added. Type to search attributes or create new.');
    }
    
    setupAttributeNameSearchHandler(attributeRow, attributeIndex, availableAttributes) {
        const input = attributeRow.querySelector(`[data-attribute-index="${attributeIndex}"]`);
        const suggestionsDiv = attributeRow.querySelector(`[data-attribute-name-suggestions="${attributeIndex}"]`);
        const typeInput = attributeRow.querySelector('[data-attribute-type]');
        
        if (!input || !suggestionsDiv) return;
        
        // Filter and display attribute suggestions
        const filterAttributes = (query) => {
            const filtered = query.trim() === ''
                ? availableAttributes
                : availableAttributes.filter(attr => 
                    attr.label.toLowerCase().includes(query.toLowerCase()) ||
                    attr.name.toLowerCase().includes(query.toLowerCase())
                );
            
            if (filtered.length > 0) {
                suggestionsDiv.innerHTML = filtered.map(attr => `
                    <div class="px-3 py-2 text-sm text-slate-200 hover:bg-slate-600 cursor-pointer"
                         data-attr-name="${attr.name}"
                         data-attr-label="${attr.label}"
                         data-attr-type="${attr.type}">
                        <div class="font-medium">${attr.label}</div>
                        <div class="text-xs text-slate-400">${attr.type === 'taxonomy' ? 'Global attribute' : 'Custom attribute'}</div>
                    </div>
                `).join('');
                suggestionsDiv.classList.remove('hidden');
                
                // Re-attach click handlers to new elements
                suggestionsDiv.querySelectorAll('[data-attr-name]').forEach(attrDiv => {
                    attrDiv.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const name = attrDiv.getAttribute('data-attr-name');
                        const label = attrDiv.getAttribute('data-attr-label');
                        const type = attrDiv.getAttribute('data-attr-type');
                        
                        // Set the selected value
                        input.value = label;
                        input.setAttribute('data-selected-name', name);
                        input.setAttribute('data-selected-label', label);
                        
                        // Update type display
                        if (typeInput) {
                            typeInput.value = type === 'taxonomy' ? 'Global' : 'Custom';
                        }
                        
                        // Hide dropdown
                        suggestionsDiv.classList.add('hidden');
                        
                        // Visual feedback
                        input.classList.add('border-green-500');
                        setTimeout(() => input.classList.remove('border-green-500'), 1000);
                    });
                });
            } else if (query.trim()) {
                // Show "Create new" option
                suggestionsDiv.innerHTML = `
                    <div class="px-3 py-2 text-sm text-blue-400 hover:bg-slate-600 cursor-pointer"
                         data-create-new="${query.trim()}">
                        <div class="font-medium">+ Create "${query.trim()}" as new attribute</div>
                        <div class="text-xs text-slate-400">Will be created as custom attribute</div>
                    </div>
                `;
                suggestionsDiv.classList.remove('hidden');
                
                // Attach click handler for creating new
                const createDiv = suggestionsDiv.querySelector('[data-create-new]');
                if (createDiv) {
                    createDiv.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const newName = createDiv.getAttribute('data-create-new');
                        
                        input.value = newName;
                        input.setAttribute('data-selected-name', newName.toLowerCase().replace(/\s+/g, '_'));
                        input.setAttribute('data-selected-label', newName);
                        
                        // Update type display
                        if (typeInput) {
                            typeInput.value = 'Custom';
                        }
                        
                        suggestionsDiv.classList.add('hidden');
                        
                        // Visual feedback
                        input.classList.add('border-green-500');
                        setTimeout(() => input.classList.remove('border-green-500'), 1000);
                    });
                }
            } else {
                suggestionsDiv.classList.add('hidden');
            }
        };
        
        // Show dropdown and filter on focus
        input.addEventListener('focus', (e) => {
            e.stopPropagation();
            // Hide all other dropdowns first
            document.querySelectorAll('[data-attribute-name-suggestions]').forEach(div => {
                if (div !== suggestionsDiv) div.classList.add('hidden');
            });
            filterAttributes(input.value);
        });
        
        // Filter as user types
        input.addEventListener('input', (e) => {
            filterAttributes(e.target.value);
        });
        
        // Show dropdown on click
        input.addEventListener('click', (e) => {
            e.stopPropagation();
            if (suggestionsDiv.classList.contains('hidden')) {
                filterAttributes(input.value);
            }
        });
        
        // Handle keyboard navigation
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                suggestionsDiv.classList.add('hidden');
            } else if (e.key === 'Enter') {
                e.preventDefault();
                const firstOption = suggestionsDiv.querySelector('[data-attr-name], [data-create-new]');
                if (firstOption) {
                    firstOption.click();
                }
            }
        });
        
        // Click outside to close dropdown
        const closeHandler = (e) => {
            if (!e.target.closest(`[data-attribute-index="${attributeIndex}"]`) &&
                !e.target.closest(`[data-attribute-name-suggestions="${attributeIndex}"]`)) {
                suggestionsDiv.classList.add('hidden');
            }
        };
        document.addEventListener('click', closeHandler);
    }
    
    addNewAttributeOption(attributeIndex, optionValue) {
        if (!optionValue) return;
        
        const optionsContainer = document.getElementById(`new-attribute-options-${attributeIndex}`);
        if (!optionsContainer) return;
        
        // Check if option already exists
        const existingOptions = Array.from(optionsContainer.querySelectorAll('span[data-option]'));
        const optionExists = existingOptions.some(span =>
            span.getAttribute('data-option').toLowerCase() === optionValue.toLowerCase()
        );
        
        if (optionExists) {
            this.ui.showToast('Option already exists');
            return;
        }
        
        // Create option tag
        const optionTag = document.createElement('span');
        optionTag.className = 'inline-flex items-center px-2 py-1 bg-blue-600 text-white text-xs rounded';
        optionTag.setAttribute('data-option', optionValue);
        optionTag.innerHTML = `
            ${optionValue}
            <button type="button" class="ml-1 text-blue-200 hover:text-white" onclick="this.parentElement.remove()">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;
        optionsContainer.appendChild(optionTag);
    }

    addVariationRow() {
        if (!this.currentEditingProduct || this.currentEditingProduct.type !== 'variable') {
            this.ui.showToast('Product must be a variable product to add variations');
            return;
        }
        
        const container = document.getElementById('product-variations');
        const existingVariations = container.querySelectorAll('[data-new-variation="true"]').length;
        const newIndex = existingVariations;
        
        // Get attributes that are marked for variations
        const variationAttributes = [];
        this.currentEditingProduct.attributes.forEach(attr => {
            if (attr.variation) {
                variationAttributes.push({
                    name: attr.name,
                    friendly_name: attr.friendly_name || attr.name,
                    options: attr.friendly_options || attr.options || []
                });
            }
        });
        
        if (variationAttributes.length === 0) {
            this.ui.showToast('Please add attributes with "Used for variations" enabled first');
            return;
        }
        
        // Build attribute selection HTML with searchable dropdowns
        const attributeSelectsHtml = variationAttributes.map((attr, attrIndex) => `
            <div>
                <label class="block text-xs text-slate-300 mb-1">${attr.friendly_name}</label>
                <div class="relative">
                    <input type="text"
                           class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm focus:border-blue-500 focus:outline-none"
                           placeholder="Type to search ${attr.friendly_name}..."
                           data-variation-attribute="${attr.name}"
                           data-variation-attr-index="${attrIndex}"
                           data-selected-value=""
                           data-all-options='${JSON.stringify(attr.options)}'
                           autocomplete="off">
                    <div class="absolute top-full left-0 right-0 bg-slate-700 border border-slate-500 rounded mt-1 max-h-48 overflow-y-auto hidden z-20"
                         data-variation-suggestions="${attrIndex}">
                        ${attr.options.map(opt => `
                            <div class="px-3 py-2 text-sm text-slate-200 hover:bg-slate-600 cursor-pointer"
                                 data-option-value="${opt}">
                                ${opt}
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `).join('');
        
        const variationRow = document.createElement('div');
        variationRow.className = 'bg-slate-600 p-3 rounded border border-slate-500';
        variationRow.setAttribute('data-new-variation', 'true');
        variationRow.setAttribute('data-variation-index', newIndex);
        variationRow.innerHTML = `
            <div class="flex justify-between items-start mb-3">
                <h4 class="font-semibold text-slate-200 text-sm">New Variation #${newIndex + 1}</h4>
                <button type="button" onclick="this.closest('[data-new-variation]').remove()"
                        class="px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-500">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-${Math.min(3, variationAttributes.length)} gap-3 mb-3">
                ${attributeSelectsHtml}
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs text-slate-300 mb-1">SKU</label>
                    <input type="text" placeholder="Optional"
                           class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm"
                           data-variation-field="sku">
                </div>
                <div>
                    <label class="block text-xs text-slate-300 mb-1">Regular Price *</label>
                    <input type="number" step="0.01" placeholder="0.00"
                           class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm"
                           data-variation-field="regular_price" required>
                </div>
                <div>
                    <label class="block text-xs text-slate-300 mb-1">Sale Price</label>
                    <input type="number" step="0.01" placeholder="Optional"
                           class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm"
                           data-variation-field="sale_price">
                </div>
                <div>
                    <label class="block text-xs text-slate-300 mb-1">Stock Quantity</label>
                    <input type="number" placeholder="0"
                           class="w-full px-2 py-1 bg-slate-700 text-slate-200 rounded border border-slate-500 text-sm"
                           data-variation-field="stock_quantity">
                </div>
            </div>
            
            <div class="mt-2 flex gap-4">
                <label class="flex items-center">
                    <input type="checkbox"
                           class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500"
                           data-variation-field="manage_stock">
                    <span class="ml-2 text-xs text-slate-300">Manage stock</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" checked
                           class="w-4 h-4 text-blue-600 bg-slate-600 border-slate-500 rounded focus:ring-blue-500"
                           data-variation-field="enabled">
                    <span class="ml-2 text-xs text-slate-300">Enabled (published)</span>
                </label>
            </div>
        `;
        
        container.appendChild(variationRow);
        
        // Setup searchable dropdown event handlers
        this.setupVariationAttributeHandlers(variationRow, variationAttributes);
        
        this.ui.showToast('New variation added. Select attributes and set pricing.');
    }
    
    setupVariationAttributeHandlers(variationRow, variationAttributes) {
        // Handle input typing and filtering
        variationRow.querySelectorAll('input[data-variation-attribute]').forEach((input, index) => {
            const suggestionsDiv = variationRow.querySelector(`[data-variation-suggestions="${index}"]`);
            if (!suggestionsDiv) return;
            
            const allOptions = JSON.parse(input.getAttribute('data-all-options') || '[]');
            
            // Filter and display options based on input
            const filterOptions = (query) => {
                const filtered = query.trim() === ''
                    ? allOptions
                    : allOptions.filter(opt => opt.toLowerCase().includes(query.toLowerCase()));
                
                if (filtered.length > 0) {
                    suggestionsDiv.innerHTML = filtered.map(opt => `
                        <div class="px-3 py-2 text-sm text-slate-200 hover:bg-slate-600 cursor-pointer"
                             data-option-value="${opt}">
                            ${opt}
                        </div>
                    `).join('');
                    suggestionsDiv.classList.remove('hidden');
                    
                    // Re-attach click handlers to new elements
                    suggestionsDiv.querySelectorAll('[data-option-value]').forEach(optionDiv => {
                        optionDiv.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const value = optionDiv.getAttribute('data-option-value');
                            
                            // Set the selected value
                            input.value = value;
                            input.setAttribute('data-selected-value', value);
                            
                            // Hide dropdown
                            suggestionsDiv.classList.add('hidden');
                            
                            // Visual feedback
                            input.classList.add('border-green-500');
                            setTimeout(() => input.classList.remove('border-green-500'), 1000);
                        });
                    });
                } else {
                    suggestionsDiv.innerHTML = '<div class="px-3 py-2 text-sm text-slate-400">No matches found</div>';
                    suggestionsDiv.classList.remove('hidden');
                }
            };
            
            // Show dropdown and filter on focus
            input.addEventListener('focus', (e) => {
                e.stopPropagation();
                // Hide all other dropdowns first
                document.querySelectorAll('[data-variation-suggestions]').forEach(div => {
                    if (div !== suggestionsDiv) div.classList.add('hidden');
                });
                filterOptions(input.value);
            });
            
            // Filter as user types
            input.addEventListener('input', (e) => {
                filterOptions(e.target.value);
            });
            
            // Show dropdown on click
            input.addEventListener('click', (e) => {
                e.stopPropagation();
                if (suggestionsDiv.classList.contains('hidden')) {
                    filterOptions(input.value);
                }
            });
            
            // Handle keyboard navigation
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    suggestionsDiv.classList.add('hidden');
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const firstOption = suggestionsDiv.querySelector('[data-option-value]');
                    if (firstOption) {
                        firstOption.click();
                    }
                }
            });
        });
        
        // Click outside to close all dropdowns
        const closeHandler = (e) => {
            if (!e.target.closest('[data-variation-attribute]') &&
                !e.target.closest('[data-variation-suggestions]')) {
                variationRow.querySelectorAll('[data-variation-suggestions]').forEach(div => {
                    div.classList.add('hidden');
                });
            }
        };
        document.addEventListener('click', closeHandler);
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

        // Collect new attributes
        const newAttributes = [];
        document.querySelectorAll('#product-attributes [data-new-attribute="true"]').forEach(attrRow => {
            const nameInput = attrRow.querySelector('[data-attribute-name-input="true"]');
            const visibleCheckbox = attrRow.querySelector('[data-visible-checkbox="true"]');
            const variationCheckbox = attrRow.querySelector('[data-variation-checkbox="true"]');
            
            if (!nameInput || !nameInput.value.trim()) return;
            
            // Get options from the options container
            const optionsContainer = attrRow.querySelector('[id^="new-attribute-options-"]');
            const options = [];
            if (optionsContainer) {
                optionsContainer.querySelectorAll('span[data-option]').forEach(span => {
                    const option = span.getAttribute('data-option');
                    if (option) options.push(option);
                });
            }
            
            if (options.length > 0) {
                newAttributes.push({
                    name: nameInput.value.trim(),
                    options: options,
                    visible: visibleCheckbox ? visibleCheckbox.checked : true,
                    variation: variationCheckbox ? variationCheckbox.checked : false
                });
            }
        });

        // Collect new variations for variable products
        const newVariations = [];
        if (this.currentEditingProduct?.type === 'variable') {
            document.querySelectorAll('#product-variations [data-new-variation="true"]').forEach(variationRow => {
                // Collect attribute selections from searchable inputs
                const attributes = {};
                variationRow.querySelectorAll('input[data-variation-attribute]').forEach(input => {
                    const attrName = input.getAttribute('data-variation-attribute');
                    const attrValue = input.getAttribute('data-selected-value') || input.value;
                    if (attrValue) {
                        attributes[attrName] = attrValue;
                    }
                });
                
                // Only include variation if all required attributes are selected
                const requiredAttributesCount = variationRow.querySelectorAll('input[data-variation-attribute]').length;
                const selectedAttributesCount = Object.keys(attributes).length;
                
                if (selectedAttributesCount !== requiredAttributesCount) {
                    return; // Skip incomplete variations
                }
                
                // Get pricing and stock data
                const regularPrice = variationRow.querySelector('[data-variation-field="regular_price"]').value;
                if (!regularPrice || parseFloat(regularPrice) <= 0) {
                    return; // Skip variations without valid pricing
                }
                
                const variationData = {
                    attributes: attributes,
                    sku: variationRow.querySelector('[data-variation-field="sku"]').value || '',
                    regular_price: regularPrice,
                    sale_price: variationRow.querySelector('[data-variation-field="sale_price"]').value || '',
                    stock_quantity: variationRow.querySelector('[data-variation-field="stock_quantity"]').value || '',
                    manage_stock: variationRow.querySelector('[data-variation-field="manage_stock"]').checked,
                    enabled: variationRow.querySelector('[data-variation-field="enabled"]').checked
                };
                
                newVariations.push(variationData);
            });
        }

        // Get existing variations data if it's a variable product (for editing)
        const variations = [];
        if (this.currentEditingProduct?.type === 'variable') {
            document.querySelectorAll('#product-variations > div[data-variation-id]').forEach(variationDiv => {
                const variationId = variationDiv.getAttribute('data-variation-id');
                const manageStockCheckbox = variationDiv.querySelector('[data-field="manage_stock"]');
                const variationData = {
                    id: parseInt(variationId),
                    sku: variationDiv.querySelector('[data-field="sku"]')?.value || '',
                    price: variationDiv.querySelector('[data-field="price"]')?.value || '',
                    sale_price: variationDiv.querySelector('[data-field="sale_price"]')?.value || '',
                    stock_quantity: variationDiv.querySelector('[data-field="stock_quantity"]')?.value || '',
                    manage_stock: manageStockCheckbox ? manageStockCheckbox.checked : false,
                    stock_status: variationDiv.querySelector('[data-field="stock_status"]')?.value || 'instock'
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
            new_attributes: newAttributes,
            new_variations: newVariations,
            variations: variations
        };
    }

    async saveProductEditor() {
        const statusEl = document.getElementById('product-editor-status');
        const saveBtn = document.getElementById('product-editor-save');
        const saveJsonBtn = document.getElementById('product-editor-save-json');
        const titleEl = document.getElementById('product-editor-title');

        // Store original button state
        const originalBtnText = saveBtn.textContent;
        const originalBtnHtml = saveBtn.innerHTML;
        
        // Set loading state on both save buttons
        saveBtn.disabled = true;
        saveJsonBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
        saveJsonBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
        saveBtn.classList.add('opacity-75', 'cursor-not-allowed');
        saveJsonBtn.classList.add('opacity-75', 'cursor-not-allowed');
        
        statusEl.textContent = 'Saving...';
        statusEl.className = 'text-sm text-right h-5 mt-2 text-slate-400';

        const formData = this.getProductEditorFormData();
        formData.nonce = this.state.getState('nonces.productEdit');

        try {
            // Detect mode from save button attribute
            const mode = saveBtn.getAttribute('data-mode');

            if (mode === 'create') {
                // CREATE MODE - create new product using dedicated API
                const payload = {
                    ...formData
                };

                // Remove id field for creation
                delete payload.id;

                const response = await fetch('api/product-create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                if (!response.ok) throw new Error(`Server responded with ${response.status}`);
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.error || 'Failed to create product');
                }

                // SUCCESS - Product created
                const newProductId = result.product_id;

                statusEl.textContent = 'Product created successfully! You can now add images in WooCommerce.';
                statusEl.className = 'text-sm text-right h-5 mt-2 text-green-400';

                // Update to edit mode with new product
                this.currentEditingProduct = {
                    id: newProductId,
                    ...formData,
                    ...result.data
                };

                titleEl.textContent = `Edit: ${formData.name}`;
                saveBtn.textContent = 'Save Changes';
                saveBtn.setAttribute('data-mode', 'edit');
                
                // Setup image interface for edit mode (product now has ID)
                this.setupImageInterface('edit');
                
                // Show success toast
                this.ui.showToast(`Product "${formData.name}" created successfully!`);
                
                // Refresh products list to show new product
                console.log('Refreshing products after product creation...');
                if (window.productsManager && typeof window.productsManager.fetchProducts === 'function') {
                    // Show loading state on both containers
                    const productGridContainer = document.getElementById('product-list');
                    const stockListContainer = document.getElementById('stock-list');
                    
                    if (productGridContainer) {
                        productGridContainer.innerHTML = this.ui.getSkeletonLoaderHtml('grid', 12);
                    }
                    if (stockListContainer) {
                        stockListContainer.innerHTML = this.ui.getSkeletonLoaderHtml('list-rows', 10);
                    }
                    
                    this.ui.showToast('Updating products...', 'info');
                    await window.productsManager.fetchProducts();
                    // Also render both views
                    window.productsManager.renderStockList();
                    window.productsManager.renderProductGrid();
                    console.log('Products refreshed and both views rendered');
                    this.ui.showToast('Products updated successfully!', 'success');
                }

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

                // EDIT MODE SUCCESS - Keep modal open and show refresh progress
                statusEl.textContent = '✓ Product saved successfully!';
                statusEl.className = 'text-sm text-right h-5 mt-2 text-green-400 font-semibold';
                
                // Show prominent initial success toast
                this.ui.showToast('Product updated successfully!', 'success');
                
                // Add a small delay so user can see the success message before refresh starts
                await new Promise(resolve => setTimeout(resolve, 800));
                
                // Show refresh status with prominent indicator
                statusEl.textContent = '🔄 Refreshing products list...';
                statusEl.className = 'text-sm text-right h-5 mt-2 text-blue-400 font-semibold animate-pulse';
                
                // Show second toast about the refresh
                this.ui.showToast('Updating products list...', 'info');
                
                // Auto-refresh products list to show updated stock/details
                console.log('Refreshing products after product editor save...');
                if (window.productsManager && typeof window.productsManager.fetchProducts === 'function') {
                    // Show loading state on both containers
                    const productGridContainer = document.getElementById('product-list');
                    const stockListContainer = document.getElementById('stock-list');
                    
                    if (productGridContainer) {
                        productGridContainer.innerHTML = this.ui.getSkeletonLoaderHtml('grid', 12);
                    }
                    if (stockListContainer) {
                        stockListContainer.innerHTML = this.ui.getSkeletonLoaderHtml('list-rows', 10);
                    }
                    
                    await window.productsManager.fetchProducts();
                    // Also render both views to ensure they're updated
                    window.productsManager.renderStockList();
                    window.productsManager.renderProductGrid();
                    console.log('Products refreshed and both views rendered');
                    
                    // Final success message with checkmark
                    statusEl.textContent = '✓ Products list updated!';
                    statusEl.className = 'text-sm text-right h-5 mt-2 text-green-400 font-semibold';
                    this.ui.showToast('Products list refreshed!', 'success');
                }
            }

        } catch (error) {
            console.error('Error saving product:', error);
            statusEl.textContent = `Error: ${error.message}`;
            statusEl.className = 'text-sm text-right h-5 mt-2 text-red-400';
            this.ui.showToast(`Error: ${error.message}`, 'error');
        } finally {
            // Restore button state
            saveBtn.disabled = false;
            saveJsonBtn.disabled = false;
            saveBtn.textContent = originalBtnText;
            saveJsonBtn.textContent = originalBtnText;
            saveBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            saveJsonBtn.classList.remove('opacity-75', 'cursor-not-allowed');
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

    /**
     * Show or hide image upload interface based on mode
     * @param {string} mode - 'create' or 'edit'
     */
    setupImageInterface(mode) {
        const createMessage = document.getElementById('image-create-mode-message');
        const uploadInterface = document.getElementById('image-upload-interface');

        if (mode === 'create') {
            createMessage.classList.remove('hidden');
            uploadInterface.classList.add('hidden');
        } else {
            createMessage.classList.add('hidden');
            uploadInterface.classList.remove('hidden');
            
            // Load existing images if in edit mode
            if (this.currentEditingProduct) {
                this.loadExistingImages();
            }
        }
    }

    /**
     * Load and display existing product images
     */
    loadExistingImages() {
        const product = this.currentEditingProduct;
        
        // Display featured image if exists
        if (product.featured_image && product.featured_image.url) {
            this.displayFeaturedImage(product.featured_image.url);
        }
        
        // Display gallery images if exist
        if (product.gallery_images && product.gallery_images.length > 0) {
            this.displayGalleryImages(product.gallery_images);
        }
    }

    /**
     * Display featured image preview
     * @param {string} imageUrl - URL of the featured image
     */
    displayFeaturedImage(imageUrl) {
        const preview = document.getElementById('featured-image-preview');
        const img = document.getElementById('featured-image-display');
        
        img.src = imageUrl;
        preview.classList.remove('hidden');
    }

    /**
     * Display gallery images previews
     * @param {Array} images - Array of gallery image objects with id and url
     */
    displayGalleryImages(images) {
        const preview = document.getElementById('gallery-images-preview');
        const container = document.getElementById('gallery-images-container');
        
        container.innerHTML = '';
        
        images.forEach(image => {
            const imageDiv = document.createElement('div');
            imageDiv.className = 'relative inline-block';
            imageDiv.innerHTML = `
                <img src="${image.url}" alt="Gallery" class="w-24 h-24 object-cover rounded border border-slate-500">
                <button type="button"
                        class="absolute top-1 right-1 bg-red-600 text-white rounded-full p-1 hover:bg-red-500"
                        onclick="window.productEditorManager.removeGalleryImage(${image.id})"
                        title="Remove image">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            container.appendChild(imageDiv);
        });
        
        preview.classList.remove('hidden');
    }

    /**
     * Handle featured image upload
     * @param {Event} event - Change event from file input
     */
    async handleFeaturedImageUpload(event) {
        const file = event.target.files[0];
        if (!file) return;

        const statusEl = document.getElementById('featured-image-status');
        statusEl.textContent = 'Uploading...';
        statusEl.className = 'text-xs text-blue-400 mt-1';

        // Validate file
        if (!file.type.startsWith('image/')) {
            statusEl.textContent = 'Error: Please select an image file';
            statusEl.className = 'text-xs text-red-400 mt-1';
            event.target.value = '';
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            statusEl.textContent = 'Error: File size exceeds 5MB';
            statusEl.className = 'text-xs text-red-400 mt-1';
            event.target.value = '';
            return;
        }

        if (!this.currentEditingProduct || !this.currentEditingProduct.id) {
            statusEl.textContent = 'Error: Please save the product first';
            statusEl.className = 'text-xs text-red-400 mt-1';
            event.target.value = '';
            return;
        }

        try {
            const formData = new FormData();
            formData.append('image', file);
            formData.append('product_id', this.currentEditingProduct.id);
            formData.append('nonce', this.state.getState('nonces.productEdit'));

            const response = await fetch('api/product-images.php?action=upload_featured', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || result.error || 'Failed to upload image');
            }

            // Display the uploaded image
            this.displayFeaturedImage(result.data.url);
            
            statusEl.textContent = 'Featured image uploaded successfully!';
            statusEl.className = 'text-xs text-green-400 mt-1';
            
            this.ui.showToast('Featured image uploaded successfully!');
            
            // Clear the file input
            event.target.value = '';

        } catch (error) {
            console.error('Featured image upload error:', error);
            statusEl.textContent = `Error: ${error.message}`;
            statusEl.className = 'text-xs text-red-400 mt-1';
            event.target.value = '';
        }
    }

    /**
     * Handle gallery images upload
     * @param {Event} event - Change event from file input
     */
    async handleGalleryImagesUpload(event) {
        const files = Array.from(event.target.files);
        if (files.length === 0) return;

        const statusEl = document.getElementById('gallery-images-status');
        statusEl.textContent = `Uploading ${files.length} image(s)...`;
        statusEl.className = 'text-xs text-blue-400 mt-1';

        // Validate files
        for (const file of files) {
            if (!file.type.startsWith('image/')) {
                statusEl.textContent = 'Error: All files must be images';
                statusEl.className = 'text-xs text-red-400 mt-1';
                event.target.value = '';
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                statusEl.textContent = `Error: ${file.name} exceeds 5MB`;
                statusEl.className = 'text-xs text-red-400 mt-1';
                event.target.value = '';
                return;
            }
        }

        if (!this.currentEditingProduct || !this.currentEditingProduct.id) {
            statusEl.textContent = 'Error: Please save the product first';
            statusEl.className = 'text-xs text-red-400 mt-1';
            event.target.value = '';
            return;
        }

        // Check current gallery count
        const currentGalleryCount = this.currentEditingProduct.gallery_images?.length || 0;
        if (currentGalleryCount + files.length > 10) {
            statusEl.textContent = `Error: Gallery limit is 10 images (currently ${currentGalleryCount})`;
            statusEl.className = 'text-xs text-red-400 mt-1';
            event.target.value = '';
            return;
        }

        try {
            const formData = new FormData();
            files.forEach(file => {
                formData.append('images[]', file);
            });
            formData.append('product_id', this.currentEditingProduct.id);
            formData.append('nonce', this.state.getState('nonces.productEdit'));

            const response = await fetch('api/product-images.php?action=upload_gallery', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || result.error || 'Failed to upload images');
            }

            // Update current product's gallery images
            if (!this.currentEditingProduct.gallery_images) {
                this.currentEditingProduct.gallery_images = [];
            }
            
            // Add newly uploaded images
            result.data.uploaded.forEach(img => {
                this.currentEditingProduct.gallery_images.push({
                    id: img.attachment_id,
                    url: img.url
                });
            });

            // Display updated gallery
            this.displayGalleryImages(this.currentEditingProduct.gallery_images);
            
            const uploadedCount = result.data.uploaded.length;
            const errorCount = result.data.errors?.length || 0;
            
            if (errorCount > 0) {
                statusEl.textContent = `${uploadedCount} uploaded, ${errorCount} failed`;
                statusEl.className = 'text-xs text-yellow-400 mt-1';
            } else {
                statusEl.textContent = `${uploadedCount} image(s) uploaded successfully!`;
                statusEl.className = 'text-xs text-green-400 mt-1';
            }
            
            this.ui.showToast(`${uploadedCount} gallery image(s) uploaded!`);
            
            // Clear the file input
            event.target.value = '';

        } catch (error) {
            console.error('Gallery images upload error:', error);
            statusEl.textContent = `Error: ${error.message}`;
            statusEl.className = 'text-xs text-red-400 mt-1';
            event.target.value = '';
        }
    }

    /**
     * Remove featured image
     */
    async removeFeaturedImage() {
        if (!this.currentEditingProduct || !this.currentEditingProduct.id) {
            this.ui.showToast('No product loaded');
            return;
        }

        if (!confirm('Remove featured image?')) return;

        try {
            const response = await fetch(`api/product-images.php?action=remove_featured&product_id=${this.currentEditingProduct.id}&nonce=${this.state.getState('nonces.productEdit')}`, {
                method: 'POST'
            });

            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || result.error || 'Failed to remove image');
            }

            // Hide preview
            document.getElementById('featured-image-preview').classList.add('hidden');
            document.getElementById('featured-image-status').textContent = 'Featured image removed';
            document.getElementById('featured-image-status').className = 'text-xs text-green-400 mt-1';
            
            this.ui.showToast('Featured image removed');

        } catch (error) {
            console.error('Remove featured image error:', error);
            this.ui.showToast(`Error: ${error.message}`);
        }
    }

    /**
     * Remove gallery image
     * @param {number} attachmentId - ID of the attachment to remove
     */
    async removeGalleryImage(attachmentId) {
        if (!this.currentEditingProduct || !this.currentEditingProduct.id) {
            this.ui.showToast('No product loaded');
            return;
        }

        if (!confirm('Remove this image from gallery?')) return;

        try {
            const response = await fetch(`api/product-images.php?action=remove_gallery&product_id=${this.currentEditingProduct.id}&attachment_id=${attachmentId}&nonce=${this.state.getState('nonces.productEdit')}`, {
                method: 'POST'
            });

            if (!response.ok) throw new Error(`Server responded with ${response.status}`);
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || result.error || 'Failed to remove image');
            }

            // Update current product's gallery images
            this.currentEditingProduct.gallery_images = this.currentEditingProduct.gallery_images.filter(
                img => img.id !== attachmentId
            );

            // Re-display gallery
            if (this.currentEditingProduct.gallery_images.length > 0) {
                this.displayGalleryImages(this.currentEditingProduct.gallery_images);
            } else {
                document.getElementById('gallery-images-preview').classList.add('hidden');
            }
            
            this.ui.showToast('Gallery image removed');

        } catch (error) {
            console.error('Remove gallery image error:', error);
            this.ui.showToast(`Error: ${error.message}`);
        }
    }
}

// Export for global access
window.ProductEditorManager = ProductEditorManager;