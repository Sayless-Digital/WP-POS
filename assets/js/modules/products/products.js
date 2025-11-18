// WP POS v1.9.206 - Products Manager Module - Added Manage Stock checkbox to variation stock editor
// Handles product display, variations, barcode scanning, and stock management
// Added: Toast notifications showing "Updating products..." during refresh

class ProductsManager {
    constructor(stateManager, uiHelpers, cartManager) {
        this.state = stateManager;
        this.ui = uiHelpers;
        this.cart = cartManager;
        
        // Setup modal event listeners
        this.setupVariationModal();
        this.setupStockEditModal();
    }
    
    /**
     * Setup variation modal event listeners
     */
    setupVariationModal() {
        const modal = document.getElementById('variation-modal');
        const cancelBtn = document.getElementById('modal-cancel-btn');
        const addToCartBtn = document.getElementById('modal-add-to-cart-btn');
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                if (modal) modal.classList.add('hidden');
            });
        }
        
        // Add to cart button handler is set in showVariationModal()
        // because it needs to be refreshed each time the modal opens
    }
    
    /**
     * Setup stock edit modal event listeners
     */
    setupStockEditModal() {
        const modal = document.getElementById('stock-edit-modal');
        const cancelBtn = document.getElementById('stock-edit-cancel-btn');
        const saveBtn = document.getElementById('stock-edit-save-btn');
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                if (modal) modal.classList.add('hidden');
            });
        }
        
        if (saveBtn) {
            saveBtn.addEventListener('click', async () => {
                await this.saveStockChanges();
            });
        }
        
        // Close modal if clicked outside (on overlay)
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        }
    }
    
    /**
     * Save stock changes from stock edit modal
     */
    async saveStockChanges() {
        console.log('=== STOCK EDIT SAVE STARTED ===');
        const modal = document.getElementById('stock-edit-modal');
        const rows = document.querySelectorAll('#stock-edit-variations-list .stock-edit-row');
        const variations = [];
        
        // Get parent product ID from state
        const parentProductId = this.state.getState('editingStockProduct.id');
        console.log('Parent Product ID:', parentProductId);
        if (!parentProductId) {
            this.ui.showToast('Error: Product ID not found');
            return;
        }
        
        rows.forEach(row => {
            const variationId = row.dataset.variationId;
            const sku = row.dataset.sku || '';
            const price = parseFloat(row.dataset.price) || 0;
            const manageStockCheckbox = row.querySelector('.manage-stock-checkbox');
            const stockInput = row.querySelector('.stock-quantity-input');
            
            if (variationId) {
                const manageStock = manageStockCheckbox ? manageStockCheckbox.checked : false;
                let stockQuantity = null;
                
                // Only include stock quantity if manage stock is enabled
                if (manageStock && stockInput) {
                    const stockValue = stockInput.value.trim();
                    stockQuantity = stockValue === '' ? 0 : parseInt(stockValue, 10) || 0;
                }
                
                variations.push({
                    id: parseInt(variationId),
                    sku: sku,
                    price: price,
                    manage_stock: manageStock,
                    stock_quantity: stockQuantity
                });
            }
        });
        
        if (variations.length === 0) {
            console.log('No variations to save');
            this.ui.showToast('No changes to save');
            return;
        }
        
        console.log(`Saving ${variations.length} variations for product ${parentProductId}`);
        console.log('Variations data:', variations);
        
        try {
            const nonce = this.state.getState('nonces.stock');
            const payload = { 
                action: 'update_variations',
                parent_id: parentProductId,
                variations: variations,
                nonce: nonce
            };
            console.log('Sending payload to API:', payload);
            
            const response = await fetch('/wp-pos/api/stock.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Stock update HTTP error:', response.status, errorText);
                throw new Error(`Failed to update stock: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Stock update API response:', result);
            
            // Handle WordPress wp_send_json_success format
            const success = result.success === true;
            const message = result.data?.message || result.message || '';
            
            if (success) {
                console.log('Stock update successful, refreshing products immediately...');
                
                // Show updating toast
                this.ui.showToast('Updating products...', 'info');
                
                // Refresh products IMMEDIATELY after save
                await this.fetchProducts();
                console.log('Products fetched, rendering views...');
                
                // Render both views to ensure data is up to date
                this.renderStockList();
                this.renderProductGrid();
                console.log('Both views rendered (stock list + product grid)');
                
                // Show success toast and close modal AFTER refresh
                this.ui.showToast(message || 'Products updated successfully!', 'success');
                
                // Small delay to let the user see the refresh happened
                setTimeout(() => {
                    if (modal) modal.classList.add('hidden');
                    console.log('Modal closed');
                }, 500);
            } else {
                const errorMsg = result.data?.message || result.message || 'Update failed';
                throw new Error(errorMsg);
            }
        } catch (error) {
            console.error('Stock update error:', error);
            this.ui.showToast('Failed to update stock: ' + error.message);
        }
    }
    
    /**
     * Open stock edit modal for a product
     */
    async openStockEditModal(productId) {
        const products = this.state.getState('products.all') || [];
        const product = products.find(p => p.id === productId);
        
        if (!product || product.type !== 'variable') {
            this.ui.showToast('Stock editing only available for variable products');
            return;
        }
        
        // Store the product ID and data in state for saving later
        this.state.updateState('editingStockProduct', {
            id: product.id,
            name: product.name
        });
        
        const modal = document.getElementById('stock-edit-modal');
        const title = document.getElementById('stock-edit-title');
        const list = document.getElementById('stock-edit-variations-list');
        
        if (title) title.textContent = `Edit Stock: ${product.name}`;
        
        if (list) {
            list.innerHTML = '';
            (product.variations || []).forEach(variation => {
                const row = document.createElement('div');
                row.className = 'stock-edit-row grid grid-cols-12 gap-4 items-center bg-slate-700/50 p-3 rounded-lg';
                row.dataset.variationId = variation.id;
                row.dataset.sku = variation.sku || '';
                row.dataset.price = variation.price || 0;
                
                const attrText = Object.values(variation.attributes || {})
                    .map(v => v.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase()))
                    .join(', ');
                
                const managesStock = variation.manages_stock || false;
                const stockQty = variation.stock_quantity || 0;
                
                row.innerHTML = `
                    <div class="col-span-3 font-semibold text-slate-200">${attrText}</div>
                    <div class="col-span-2 text-slate-400 text-sm">SKU: ${variation.sku || 'N/A'}</div>
                    <div class="col-span-2 flex items-center gap-2">
                        <label class="flex items-center gap-2 cursor-pointer hover:opacity-80">
                            <input type="checkbox" 
                                   class="manage-stock-checkbox w-5 h-5 rounded border-2 border-slate-500 bg-slate-700 text-green-600 focus:ring-2 focus:ring-green-500 focus:ring-offset-1 cursor-pointer"
                                   ${managesStock ? 'checked' : ''}
                                   style="accent-color: #16a34a;">
                            <span class="text-sm text-slate-300 font-medium">Manage Stock</span>
                        </label>
                    </div>
                    <div class="col-span-2 text-right">
                        <input type="number"
                               value="${stockQty}"
                               min="0"
                               class="stock-quantity-input w-full form-input text-sm py-1 px-2 text-right bg-slate-600 border-slate-500"
                               ${!managesStock ? 'disabled' : ''}>
                    </div>
                    <div class="col-span-3 text-right text-sm">
                        <span class="${stockQty > 5 ? 'text-green-400' : 'text-orange-400'}">
                            Current: ${stockQty}
                        </span>
                    </div>
                `;
                
                // Add event listener to enable/disable stock quantity input based on checkbox
                const checkbox = row.querySelector('.manage-stock-checkbox');
                const stockInput = row.querySelector('.stock-quantity-input');
                if (checkbox && stockInput) {
                    checkbox.addEventListener('change', (e) => {
                        stockInput.disabled = !e.target.checked;
                        if (!e.target.checked) {
                            stockInput.value = '0';
                        }
                    });
                }
                
                list.appendChild(row);
            });
        }
        
        if (modal) modal.classList.remove('hidden');
    }

    /**
     * Fetch products from API with optional filters
     * @param {Object} filters - Filter options
     * @returns {Promise<Array>} Array of products
     */
    async fetchProducts(filters = {}) {
        try {
            console.log('Fetching products from API...');
            // Load all products at once
            const response = await fetch('api/products.php');
            if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
            
            const result = await response.json();
            if (!result.success) throw new Error(result.data?.message || 'Failed to load products.');
            
            const products = result.data.products || [];
            console.log(`Fetched ${products.length} products, updating state...`);
            this.state.updateState('products.all', products);
            
            // Build filter UI if categories/tags available
            if (result.data.categories || result.data.tags) {
                this.buildFilterUI(result.data.categories || [], result.data.tags || []);
            }
            
            console.log('Products state updated successfully');
            return products;
        } catch (error) {
            console.error('Error fetching products:', error);
            throw error;
        }
    }

    /**
     * Build filter UI (categories and tags dropdowns)
     * @param {Array} categories - Category list
     * @param {Array} tags - Tag list
     */
    buildFilterUI(categories, tags) {
        ['category-filter', 'products-category-filter'].forEach(id => {
            const catSelect = document.getElementById(id);
            if (catSelect) {
                catSelect.innerHTML = '<option value="all">All Categories</option>';
                categories.forEach(cat => {
                    catSelect.innerHTML += `<option value="${cat.term_id}">${cat.name}</option>`;
                });
            }
        });
        
        ['tag-filter', 'products-tag-filter'].forEach(id => {
            const tagSelect = document.getElementById(id);
            if (tagSelect) {
                tagSelect.innerHTML = '<option value="all">All Tags</option>';
                tags.forEach(tag => {
                    tagSelect.innerHTML += `<option value="${tag.term_id}">${tag.name}</option>`;
                });
            }
        });
    }

    /**
     * Render product grid
     */
    renderProductGrid() {
        const container = document.getElementById('product-list');
        if (!container) return;
        
        container.innerHTML = '';
        
        const filters = this.state.getState('filters');
        const products = this.state.getState('products.all') || [];
        
        // Filter products based on current filters
        const filteredProducts = products.filter(p => {
            // SKU search mode
            if (filters.searchType === 'sku') {
                const searchValue = (filters.search || '').trim();
                
                // If no search value, show all products
                if (!searchValue) {
                    // Still apply other filters (category, tag, stock)
                    const categoryMatch = filters.category === 'all' || (p.category_ids || []).includes(parseInt(filters.category));
                    const tagMatch = filters.tag === 'all' || (p.tag_ids || []).includes(parseInt(filters.tag));
                    
                    let stockMatch;
                    if (filters.stock === 'private') {
                        stockMatch = p.post_status === 'private';
                    } else {
                        stockMatch = filters.stock === 'all' || p.stock_status === filters.stock;
                    }
                    
                    return categoryMatch && tagMatch && stockMatch;
                }
                
                // Search by SKU - check product SKU and variation SKUs
                const skuMatch = p.sku && String(p.sku).trim().toLowerCase() === searchValue.toLowerCase();
                
                // Check variation SKUs
                let variationSkuMatch = false;
                if (p.variations && p.variations.length > 0) {
                    variationSkuMatch = p.variations.some(v => 
                        v.sku && String(v.sku).trim().toLowerCase() === searchValue.toLowerCase()
                    );
                }
                
                // If SKU matches, still apply other filters
                if (skuMatch || variationSkuMatch) {
                    const categoryMatch = filters.category === 'all' || (p.category_ids || []).includes(parseInt(filters.category));
                    const tagMatch = filters.tag === 'all' || (p.tag_ids || []).includes(parseInt(filters.tag));
                    
                    let stockMatch;
                    if (filters.stock === 'private') {
                        stockMatch = p.post_status === 'private';
                    } else {
                        stockMatch = filters.stock === 'all' || p.stock_status === filters.stock;
                    }
                    
                    return categoryMatch && tagMatch && stockMatch;
                }
                
                return false;
            }
            
            // Name search mode (default)
            const searchLower = (filters.search || '').toLowerCase();
            const categoryMatch = filters.category === 'all' || (p.category_ids || []).includes(parseInt(filters.category));
            const tagMatch = filters.tag === 'all' || (p.tag_ids || []).includes(parseInt(filters.tag));
            
            let stockMatch;
            if (filters.stock === 'private') {
                stockMatch = p.post_status === 'private';
            } else {
                stockMatch = filters.stock === 'all' || p.stock_status === filters.stock;
            }
            
            const searchMatch = filters.search === '' || (p.name && p.name.toLowerCase().includes(searchLower));
            
            return searchMatch && stockMatch && categoryMatch && tagMatch;
        });

        if (filteredProducts.length === 0) {
            container.innerHTML = '<p class="col-span-full text-center text-slate-400 p-10">No products match criteria.</p>';
            return;
        }
        
        // Render each product card
        filteredProducts.forEach(p => {
            const el = document.createElement('div');
            el.setAttribute('role', 'button');
            el.setAttribute('tabindex', '0');
            
            const isOutOfStock = p.stock_status === 'outofstock';
            let highlightClass = '';
            let badgeHtml = '';
            
            if (p.post_status === 'private') {
                highlightClass = 'border-indigo-400 ring-2 ring-indigo-400';
                badgeHtml = `<div class="absolute top-2 left-2 bg-indigo-500/60 backdrop-blur-sm px-2 py-1 rounded-full text-xs font-bold text-white" style="height:1.5rem;display:flex;align-items:center;">Private</div>`;
            }
            
            el.className = `group flex flex-col cursor-pointer border border-slate-700 rounded-xl bg-slate-800 text-left hover:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all overflow-hidden relative ${isOutOfStock ? 'opacity-40' : ''} ${highlightClass}`;
            
            // Product image or placeholder
            const imageHTML = p.image_url ? 
                `<img src="${p.image_url}" alt="${p.name}" class="w-full h-full object-cover transition-transform group-hover:scale-105" loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">` : 
                '';
            const placeholderHTML = `<div class="w-full h-full bg-slate-700 flex items-center justify-center text-slate-400 text-xs font-bold px-2 text-center" style="display: ${p.image_url ? 'none' : 'flex'}; line-height: 1.2;">${p.sku || 'N/A'}</div>`;
            
            // Price display
            let priceDisplay;
            if (p.type === 'variable' && p.min_price !== null) {
                priceDisplay = `$${parseFloat(p.min_price).toFixed(2)}`;
            } else if (p.price) {
                priceDisplay = `$${parseFloat(p.price || 0).toFixed(2)}`;
            } else {
                priceDisplay = 'N/A';
            }
            
            // Stock display
            let stockDisplayHtml = '';
            if (p.manages_stock && p.stock_quantity !== null) {
                stockDisplayHtml = `<span class="font-bold ${p.stock_quantity > 5 ? 'text-green-300' : 'text-orange-300'}">${p.stock_quantity}</span> in stock`;
            } else {
                stockDisplayHtml = `<span class="text-slate-300">${isOutOfStock ? 'Out of stock' : 'In Stock'}</span>`;
            }

            el.innerHTML = `<div class="aspect-square w-full flex-shrink-0 overflow-hidden relative">${imageHTML}${placeholderHTML}${badgeHtml}<div class="absolute top-2 right-2 bg-slate-900/60 backdrop-blur-sm px-2 py-1 rounded-full text-xs">${stockDisplayHtml}</div></div><div class="p-3 flex flex-col flex-grow"><h3 class="font-semibold text-sm text-slate-100 leading-tight line-clamp-2 flex-grow">${p.name}</h3><p class="text-xs text-slate-400 font-mono mt-1">SKU: ${p.sku || 'N/A'}</p><p class="text-sm text-green-400 mt-2 font-bold">${priceDisplay}</p></div>`;
            
            if (!isOutOfStock) {
                el.onclick = () => this.handleProductClick(p.id);
                el.onkeydown = (e) => {
                    if (e.key === 'Enter' || e.key === ' ') this.handleProductClick(p.id);
                };
            } else {
                el.classList.add('cursor-not-allowed');
            }
            
            container.appendChild(el);
        });
    }

    /**
     * Handle product click
     * @param {Number} productId - Product ID
     * @param {String} preselectedSku - Optional SKU to preselect in variation modal
     */
    async handleProductClick(productId, preselectedSku = null) {
        const products = this.state.getState('products.all') || [];
        const product = products.find(p => p.id === productId);
        
        if (!product || product.stock_status === 'outofstock') return;
        
        if (product.type === 'simple') {
            this.cart.addToCart(product, 1);
        } else {
            this.state.updateState('products.currentForModal', product);
            await this.showVariationModal(preselectedSku);
        }
    }

    /**
     * Show variation modal for variable products
     * @param {String} preselectedSku - Optional SKU to preselect
     */
    async showVariationModal(preselectedSku = null) {
        const product = this.state.getState('products.currentForModal');
        if (!product) return;
        
        // Set modal content
        document.getElementById('modal-product-name').textContent = product.name;
        document.getElementById('modal-product-sku').textContent = `SKU: ${product.sku || 'N/A'}`;
        document.getElementById('modal-image').src = product.image_url || '';
        
        const optionsContainer = document.getElementById('modal-options-container');
        optionsContainer.innerHTML = '';
        
        // Create or get price display element
        let priceDisplayEl = document.getElementById('modal-variation-price');
        if (!priceDisplayEl) {
            priceDisplayEl = document.createElement('p');
            priceDisplayEl.id = 'modal-variation-price';
            priceDisplayEl.className = 'text-xl font-bold text-green-400 mt-2 mb-4';
            optionsContainer.parentElement.insertBefore(priceDisplayEl, optionsContainer);
        }

        // Get held stock quantities
        const heldCarts = JSON.parse(localStorage.getItem('jpos_held_carts') || '[]');
        const heldQtyByVariationId = {};
        heldCarts.forEach(held => {
            (held.cart || []).forEach(item => {
                if (item.id && typeof item.qty === 'number') {
                    heldQtyByVariationId[item.id] = (heldQtyByVariationId[item.id] || 0) + item.qty;
                }
            });
        });

        // Build attributes object
        const attributes = {};
        (product.variations || []).forEach(v => {
            for (const key in v.attributes) {
                if (!attributes[key]) attributes[key] = new Set();
                attributes[key].add(v.attributes[key]);
            }
        });

        // Render attribute swatches
        Object.entries(attributes).forEach(([attrKey, attrValues]) => {
            const attrDiv = document.createElement('div');
            const label = document.createElement('label');
            label.className = 'block text-sm font-medium text-slate-300 mb-2';
            label.textContent = attrKey.replace('pa_', '').replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            attrDiv.appendChild(label);
            
            const swatchGroup = document.createElement('div');
            swatchGroup.className = 'flex flex-wrap gap-2';
            swatchGroup.dataset.attribute = attrKey;

            const attrValuesArr = [...attrValues].sort();
            const isSingleValue = attrValuesArr.length === 1;

            attrValuesArr.forEach(optionValue => {
                const swatch = document.createElement('button');
                swatch.className = 'px-3 py-1.5 border border-slate-600 rounded-md bg-slate-700 text-slate-200 hover:bg-slate-600 text-sm transition-colors';
                
                let isSwatchEffectivelyOutOfStock = true;
                let isSwatchHeld = false;
                let heldCount = 0;
                
                // Check if this swatch option has available stock
                for (const v of product.variations) {
                    if (v.attributes[attrKey] === optionValue) {
                        let availableQty = v.stock_quantity;
                        if (typeof availableQty === 'number') {
                            const heldQty = heldQtyByVariationId[v.id] || 0;
                            heldCount = heldQty;
                            
                            if (availableQty > 0 && heldQty >= availableQty) {
                                isSwatchHeld = true;
                                isSwatchEffectivelyOutOfStock = true;
                            } else if (v.stock_status === 'instock' && availableQty - heldQty > 0) {
                                isSwatchEffectivelyOutOfStock = false;
                            }
                        } else if (v.stock_status === 'instock') {
                            isSwatchEffectivelyOutOfStock = false;
                        }
                    }
                }

                // Handle single-value attributes
                if (isSingleValue) {
                    swatch.onclick = () => {
                        this.selectSwatch(swatch);
                        this.updateAddToCartButton();
                        this.updateVariationPriceDisplay();
                    };
                    swatch.textContent = optionValue.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    if (isSwatchEffectivelyOutOfStock) {
                        swatch.classList.add('opacity-50');
                    }
                } else if (isSwatchHeld) {
                    // Held stock - show as unavailable but clickable to navigate to held cart
                    swatch.classList.add('opacity-50', 'line-through');
                    swatch.style.cursor = 'pointer';
                    swatch.disabled = false;
                    swatch.innerHTML = `<span>${optionValue.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span> <span style='color: orange; font-weight: bold; text-decoration:underline;' class='ml-1'>(Held: ${heldCount})</span>`;
                    
                    swatch.onclick = (e) => {
                        e.stopPropagation();
                        // Navigate to held carts page
                        document.getElementById('variation-modal').classList.add('hidden');
                        window.routingManager.navigateToView('held-carts-page');
                    };
                } else if (isSwatchEffectivelyOutOfStock) {
                    swatch.classList.add('opacity-50', 'cursor-not-allowed', 'line-through');
                    swatch.disabled = true;
                    swatch.textContent = optionValue.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                } else {
                    swatch.onclick = () => {
                        this.selectSwatch(swatch);
                        this.updateAddToCartButton();
                        this.updateVariationPriceDisplay();
                    };
                    swatch.textContent = optionValue.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                }
                
                swatch.dataset.value = optionValue;
                swatchGroup.appendChild(swatch);
            });
            
            attrDiv.appendChild(swatchGroup);
            optionsContainer.appendChild(attrDiv);
        });
        
        // Auto-select single available options
        Object.entries(attributes).forEach(([attrKey, attrValues]) => {
            const availableOptions = [...attrValues].filter(optionValue => {
                for (const v of product.variations) {
                    if (v.attributes[attrKey] === optionValue && v.stock_status === 'instock') {
                        if (v.manages_stock && v.stock_quantity !== null) {
                            const heldQty = heldQtyByVariationId[v.id] || 0;
                            if (v.stock_quantity > heldQty) return true;
                        } else {
                            return true;
                        }
                    }
                }
                return false;
            });

            if (availableOptions.length === 1) {
                const swatch = document.querySelector(`#modal-options-container [data-attribute="${attrKey}"] [data-value="${availableOptions[0]}"]`);
                if (swatch && !swatch.disabled) {
                    this.selectSwatch(swatch);
                }
            }
        });
        
        // Set up add to cart button
        const addToCartBtn = document.getElementById('modal-add-to-cart-btn');
        addToCartBtn.onclick = () => this.addVariationToCart();
        
        this.updateAddToCartButton();
        this.updateVariationPriceDisplay();
        document.getElementById('variation-modal').classList.remove('hidden');

        // Preselect variation if SKU provided
        if (preselectedSku) {
            const targetVariation = product.variations.find(v => v.sku === preselectedSku);
            if (targetVariation) {
                Object.entries(targetVariation.attributes).forEach(([attr, val]) => {
                    const swatch = document.querySelector(`#modal-options-container [data-attribute="${attr}"] [data-value="${val}"]`);
                    if (swatch) this.selectSwatch(swatch);
                });
                this.updateAddToCartButton();
                this.updateVariationPriceDisplay();
            }
        }
    }

    /**
     * Select a variation swatch
     * @param {HTMLElement} swatchElement - Swatch button element
     */
    selectSwatch(swatchElement) {
        if (swatchElement.disabled) return;
        
        const group = swatchElement.parentElement;
        group.querySelectorAll('button').forEach(btn => {
            btn.classList.remove('bg-indigo-600', 'text-white', 'border-indigo-500');
            if (!btn.disabled) {
                btn.classList.add('bg-slate-700', 'text-slate-200');
            }
        });
        
        swatchElement.classList.add('bg-indigo-600', 'text-white', 'border-indigo-500');
        swatchElement.classList.remove('bg-slate-700', 'text-slate-200');
    }

    /**
     * Update variation price display
     */
    updateVariationPriceDisplay() {
        const priceDisplayEl = document.getElementById('modal-variation-price');
        const product = this.state.getState('products.currentForModal');
        
        const selectedOptions = {};
        let allOptionsSelected = true;
        
        document.querySelectorAll('#modal-options-container [data-attribute]').forEach(group => {
            const selectedBtn = group.querySelector('.bg-indigo-600');
            if (selectedBtn) {
                selectedOptions[group.dataset.attribute] = selectedBtn.dataset.value;
            } else {
                allOptionsSelected = false;
            }
        });

        if (allOptionsSelected) {
            const matchedVariation = product.variations.find(v =>
                Object.keys(selectedOptions).every(key => v.attributes[key] === selectedOptions[key])
            );
            
            if (matchedVariation && matchedVariation.stock_status === 'instock') {
                priceDisplayEl.textContent = `$${parseFloat(matchedVariation.price).toFixed(2)}`;
            } else {
                priceDisplayEl.textContent = 'N/A';
            }
        } else {
            priceDisplayEl.textContent = 'Select options';
        }
    }

    /**
     * Update add to cart button state
     */
    updateAddToCartButton() {
        const product = this.state.getState('products.currentForModal');
        const selectedOptions = {};
        let allOptionsSelected = true;
        
        document.querySelectorAll('#modal-options-container [data-attribute]').forEach(group => {
            const selectedBtn = group.querySelector('.bg-indigo-600');
            if (selectedBtn) {
                selectedOptions[group.dataset.attribute] = selectedBtn.dataset.value;
            } else {
                allOptionsSelected = false;
            }
        });
        
        const addToCartBtn = document.getElementById('modal-add-to-cart-btn');
        const modalStockStatusEl = document.getElementById('modal-stock-status');
        
        modalStockStatusEl.textContent = '';
        modalStockStatusEl.className = 'text-sm text-slate-400';
        addToCartBtn.disabled = true;

        if (allOptionsSelected) {
            const matchedVariation = product.variations.find(v =>
                Object.keys(selectedOptions).every(key => v.attributes[key] === selectedOptions[key])
            );
            
            if (matchedVariation) {
                if (matchedVariation.stock_status === 'instock') {
                    addToCartBtn.disabled = false;
                    if (matchedVariation.manages_stock && matchedVariation.stock_quantity !== null) {
                        modalStockStatusEl.innerHTML = `<span class="font-bold text-green-400">${matchedVariation.stock_quantity}</span> in stock`;
                    } else {
                        modalStockStatusEl.textContent = 'In Stock';
                    }
                } else {
                    modalStockStatusEl.textContent = 'Out of Stock';
                    modalStockStatusEl.classList.add('text-red-400');
                }
            } else {
                modalStockStatusEl.textContent = 'Combination not available';
                modalStockStatusEl.classList.add('text-orange-400');
            }
        } else {
            modalStockStatusEl.textContent = 'Select options to see stock';
        }
    }

    /**
     * Add selected variation to cart
     */
    addVariationToCart() {
        const drawer = this.state.getState('drawer');
        if (!drawer.isOpen) {
            window.showDrawerModal('open');
            return;
        }
        
        const product = this.state.getState('products.currentForModal');
        const selectedOptions = {};
        
        document.querySelectorAll('#modal-options-container [data-attribute]').forEach(group => {
            const selectedBtn = group.querySelector('.bg-indigo-600');
            if (selectedBtn) {
                selectedOptions[group.dataset.attribute] = selectedBtn.dataset.value;
            }
        });
        
        const matchedVariation = product.variations.find(v =>
            Object.keys(selectedOptions).every(key => v.attributes[key] === selectedOptions[key])
        );
        
        if (matchedVariation) {
            const variationForCart = {
                ...matchedVariation,
                name: `${product.name} - ${Object.values(matchedVariation.attributes).map(v => 
                    v.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
                ).join(', ')}`
            };
            
            this.cart.addToCart(variationForCart, 1);
            document.getElementById('variation-modal').classList.add('hidden');
        }
    }

    /**
     * Handle barcode input/scanning
     * @param {String} barcode - Scanned barcode (usually SKU)
     */
    async handleBarcodeInput(barcode) {
        if (!barcode || !barcode.trim()) {
            console.log('handleBarcodeInput: Empty barcode');
            return;
        }
        
        const barcodeValue = barcode.trim();
        console.log('handleBarcodeInput: Searching for:', barcodeValue);
        
        const products = this.state.getState('products.all') || [];
        console.log('handleBarcodeInput: Total products:', products.length);
        
        // Search by SKU first (most common), then barcode
        // Normalize comparison - trim whitespace and handle null/undefined
        const foundProduct = products.find(p => {
            // Check product SKU (most common use case)
            if (p.sku && String(p.sku).trim() === barcodeValue) {
                console.log('Found by product SKU:', p.name, p.sku);
                return true;
            }
            
            // Check product barcode
            if (p.barcode && String(p.barcode).trim() === barcodeValue) {
                console.log('Found by product barcode:', p.name, p.barcode);
                return true;
            }
            
            // Check variation SKUs and barcodes
            if (p.variations && p.variations.length > 0) {
                const matchingVariation = p.variations.find(v => {
                    const skuMatch = v.sku && String(v.sku).trim() === barcodeValue;
                    const barcodeMatch = v.barcode && String(v.barcode).trim() === barcodeValue;
                    return skuMatch || barcodeMatch;
                });
                
                if (matchingVariation) {
                    console.log('Found by variation:', p.name, matchingVariation.sku || matchingVariation.barcode);
                    return true;
                }
            }
            
            return false;
        });
        
        if (foundProduct) {
            console.log('handleBarcodeInput: Product found:', foundProduct.name, foundProduct.id);
            
            // Find the matching variation SKU if this is a variable product
            let matchingVariationSku = null;
            if (foundProduct.variations && foundProduct.variations.length > 0) {
                const matchingVariation = foundProduct.variations.find(v => {
                    const skuMatch = v.sku && String(v.sku).trim() === barcodeValue;
                    const barcodeMatch = v.barcode && String(v.barcode).trim() === barcodeValue;
                    return skuMatch || barcodeMatch;
                });
                
                if (matchingVariation) {
                    matchingVariationSku = matchingVariation.sku ? String(matchingVariation.sku).trim() : barcodeValue;
                    console.log('handleBarcodeInput: Matching variation SKU:', matchingVariationSku);
                }
            }
            
            // Handle the product click
            await this.handleProductClick(foundProduct.id, matchingVariationSku || barcodeValue);
        } else {
            console.log('handleBarcodeInput: Product not found for:', barcodeValue);
            // Debug: show first few products with SKUs for debugging
            const sampleProducts = products.slice(0, 5).map(p => ({ name: p.name, sku: p.sku }));
            console.log('Sample products (first 5):', sampleProducts);
            this.ui.showToast('Product not found');
        }
    }

    /**
     * Find product by barcode
     * @param {String} barcode - Barcode to search for
     * @returns {Object|null} Product object or null
     */
    findProductByBarcode(barcode) {
        if (!barcode || !barcode.trim()) return null;
        
        const barcodeValue = barcode.trim();
        const products = this.state.getState('products.all') || [];
        
        return products.find(p => {
            // Check product barcode
            if (p.barcode === barcodeValue) return true;
            
            // Check product SKU
            if (p.sku === barcodeValue) return true;
            
            // Check variation barcodes and SKUs
            if (p.variations && p.variations.length > 0) {
                return p.variations.some(v => 
                    v.barcode === barcodeValue || v.sku === barcodeValue
                );
            }
            
            return false;
        }) || null;
    }

    /**
     * Handle search input event
     * @param {Event} e - Input event
     */
    handleSearch(e) {
        const searchValue = e.target ? e.target.value : e;
        this.state.updateState('filters.search', searchValue);
        this.renderProductGrid();
    }

    /**
     * Search products by query
     * @param {String} query - Search query
     */
    searchProducts(query) {
        this.state.updateState('filters.search', query);
        this.renderProductGrid();
    }

    /**
     * Filter products by category
     * @param {String} categoryId - Category ID
     */
    filterByCategory(categoryId) {
        this.state.updateState('filters.category', categoryId);
        this.renderProductGrid();
    }

    /**
     * Filter products by status
     * @param {String} status - Stock status (all, instock, outofstock, private)
     */
    filterByStatus(status) {
        this.state.updateState('filters.stock', status);
        this.renderProductGrid();
    }

    /**
     * Render stock list view
     */
    renderStockList() {
        const container = document.getElementById('stock-list');
        if (!container) return;
        
        container.innerHTML = '';
        
        const filterText = document.getElementById('products-list-filter')?.value.toLowerCase() || '';
        const stockFilters = this.state.getState('stockFilters') || { category: 'all', tag: 'all', stock: 'all' };
        const products = this.state.getState('products.all') || [];
        
        const filteredList = products.filter(p => {
            const textMatch = p.name.toLowerCase().includes(filterText) || (p.sku && p.sku.toLowerCase().includes(filterText));
            const categoryMatch = stockFilters.category === 'all' || (p.category_ids || []).includes(parseInt(stockFilters.category));
            const tagMatch = stockFilters.tag === 'all' || (p.tag_ids || []).includes(parseInt(stockFilters.tag));
            
            let stockMatch;
            if (stockFilters.stock === 'private') {
                stockMatch = p.post_status === 'private';
            } else {
                stockMatch = stockFilters.stock === 'all' || p.stock_status === stockFilters.stock;
            }
            
            return textMatch && categoryMatch && tagMatch && stockMatch;
        });

        if (filteredList.length === 0) {
            container.innerHTML = '<div class="p-10 text-center text-slate-400 col-span-12">No products match your filter.</div>';
            return;
        }
        
        filteredList.forEach(p => {
            const row = document.createElement('div');
            row.className = 'grid grid-cols-12 gap-4 items-center bg-slate-800 hover:bg-slate-700/50 p-3 rounded-lg text-sm cursor-pointer';
            row.onclick = () => window.openProductEditor(p.id);
            
            let stockDisplayHtml = '';
            if (p.manages_stock && p.stock_quantity !== null) {
                stockDisplayHtml = `<span class="font-bold ${p.stock_quantity > 5 ? 'text-green-300' : 'text-orange-300'}">${p.stock_quantity}</span>`;
            } else {
                stockDisplayHtml = `<span class="${p.stock_status === 'instock' ? 'text-slate-300' : 'text-orange-300'}">${p.stock_status === 'instock' ? 'In Stock' : 'Out of Stock'}</span>`;
            }

            let priceDisplay = p.type === 'variable' && p.min_price !== null ?
                `$${parseFloat(p.min_price).toFixed(2)}` :
                p.price ? `$${parseFloat(p.price || 0).toFixed(2)}` : 'N/A';

            const imageHtml = p.image_url ?
                `<img src="${p.image_url}" class="w-10 h-10 object-cover rounded-lg" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">` :
                '';
            const placeholderHtml = `<div class="w-10 h-10 bg-slate-700 rounded-lg flex items-center justify-center text-slate-400 text-xs font-bold px-1 text-center" style="display: ${p.image_url ? 'none' : 'flex'}; line-height: 1.1;">${p.sku || 'N/A'}</div>`;
            
            row.innerHTML = `
                <div class="col-span-1 flex justify-start">
                    ${imageHtml}${placeholderHtml}
                </div>
                <div class="col-span-3 font-semibold text-slate-200 truncate">${p.name}</div>
                <div class="col-span-2 text-slate-400 font-mono text-xs truncate">${p.sku || 'N/A'}</div>
                <div class="col-span-1 text-slate-400 text-xs capitalize">${p.type}</div>
                <div class="col-span-2 text-right font-mono text-green-400 font-bold">${priceDisplay}</div>
                <div class="col-span-2 text-right font-bold">${stockDisplayHtml}</div>
                <div class="col-span-1 text-center">
                    <button class="px-2 py-1 bg-indigo-600 text-xs rounded hover:bg-indigo-500 transition-colors" onclick="event.stopPropagation(); window.openProductEditor(${p.id})">
                        Edit
                    </button>
                </div>
            `;
            
            container.appendChild(row);
        });
    }
}

// Export for use in main.js
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProductsManager;
}