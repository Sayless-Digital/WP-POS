/**
 * JPOS Products Module
 * Handles product catalog, filtering, and product selection
 */

class ProductsManager {
    constructor(stateManager) {
        this.stateManager = stateManager;
    }

    /**
     * Load products from API with pagination
     */
    async loadProducts(page = 1, perPage = 20) {
        try {
            const url = new URL('/wp-pos/api/products.php', window.location.origin);
            url.searchParams.set('page', page);
            url.searchParams.set('per_page', perPage);
            
            const response = await fetch(url);
            if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
            const result = await response.json();
            
            if (!result.success) throw new Error(result.data.message || 'Failed to load products.');
            
            // Update state with products and pagination info
            this.stateManager.updateState('products.all', result.data.products || []);
            this.stateManager.updateState('products.pagination', result.data.pagination || {});
            
            return result.data;
        } catch (error) {
            console.error('Failed to load products:', error);
            this.stateManager.updateState('products.all', []);
            throw error;
        }
    }

    /**
     * Build filter UI for categories and tags
     * @param {Array} categories - Available categories
     * @param {Array} tags - Available tags
     */
    buildFilterUI(categories, tags) {
        const filterContainer = document.getElementById('filter-container');
        if (!filterContainer) return;

        filterContainer.innerHTML = `
            <div class="flex flex-wrap gap-2 mb-4">
                <select id="category-filter" class="px-3 py-2 bg-slate-700 text-slate-200 rounded-lg border border-slate-600">
                    <option value="all">All Categories</option>
                    ${categories.map(cat => `<option value="${cat.slug}">${cat.name}</option>`).join('')}
                </select>
                <select id="tag-filter" class="px-3 py-2 bg-slate-700 text-slate-200 rounded-lg border border-slate-600">
                    <option value="all">All Tags</option>
                    ${tags.map(tag => `<option value="${tag.slug}">${tag.name}</option>`).join('')}
                </select>
                <select id="stock-filter" class="px-3 py-2 bg-slate-700 text-slate-200 rounded-lg border border-slate-600">
                    <option value="all">All Stock</option>
                    <option value="in-stock">In Stock</option>
                    <option value="out-of-stock">Out of Stock</option>
                    <option value="low-stock">Low Stock</option>
                </select>
            </div>
        `;

        // Add event listeners
        document.getElementById('category-filter').addEventListener('change', (e) => {
            this.stateManager.updateState('filters.category', e.target.value);
            this.renderProducts();
        });

        document.getElementById('tag-filter').addEventListener('change', (e) => {
            this.stateManager.updateState('filters.tag', e.target.value);
            this.renderProducts();
        });

        document.getElementById('stock-filter').addEventListener('change', (e) => {
            this.stateManager.updateState('filters.stock', e.target.value);
            this.renderProducts();
        });
    }

    /**
     * Handle product search
     * @param {Event} e - Search input event
     */
    handleSearch(e) {
        const query = e.target.value.toLowerCase();
        this.stateManager.updateState('filters.search', query);
        this.renderProducts();
    }

    /**
     * Render products based on current filters
     */
    renderProducts() {
        const products = this.stateManager.getState('products.all');
        const filters = this.stateManager.getState('filters');
        const container = document.getElementById('product-list');
        
        if (!container) return;

        // Filter products
        let filteredProducts = products.filter(product => {
            // Search filter
            if (filters.search) {
                const searchFields = [product.name, product.sku].join(' ').toLowerCase();
                if (!searchFields.includes(filters.search)) return false;
            }

            // Category filter
            if (filters.category !== 'all') {
                if (!product.categories || !product.categories.some(cat => cat.slug === filters.category)) {
                    return false;
                }
            }

            // Tag filter
            if (filters.tag !== 'all') {
                if (!product.tags || !product.tags.some(tag => tag.slug === filters.tag)) {
                    return false;
                }
            }

            // Stock filter
            if (filters.stock !== 'all') {
                const stockStatus = product.stock_status;
                if (filters.stock === 'in-stock' && stockStatus !== 'instock') return false;
                if (filters.stock === 'out-of-stock' && stockStatus !== 'outofstock') return false;
                if (filters.stock === 'low-stock' && (stockStatus !== 'instock' || product.stock_quantity > 5)) return false;
            }

            return true;
        });

        // Render products
        if (filteredProducts.length === 0) {
            container.innerHTML = '<div class="col-span-full text-center text-slate-400 py-8">No products found</div>';
            return;
        }

        container.innerHTML = filteredProducts.map(product => this.renderProductCard(product)).join('');
    }

    /**
     * Render individual product card
     * @param {Object} product - Product object
     * @returns {string} HTML string for product card
     */
    renderProductCard(product) {
        const stockClass = product.stock_status === 'instock' ? 'text-green-400' : 'text-red-400';
        const stockText = product.stock_status === 'instock' ? 'In Stock' : 'Out of Stock';
        
        // Use regular src for now to fix the disappearing image issue
        const imageHtml = product.image_url ? 
            `<img src="${product.image_url}" alt="${product.name}" class="w-full h-full object-cover rounded-lg" loading="lazy">` :
            `<div class="text-slate-400 text-4xl"><i class="fas fa-box"></i></div>`;
        
        return `
            <div class="bg-slate-800 rounded-lg p-4 border border-slate-700 hover:border-slate-600 transition-colors cursor-pointer"
                 onclick="productsManager.handleProductClick(${product.id})">
                <div class="aspect-square bg-slate-700 rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                    ${imageHtml}
                </div>
                <h3 class="font-semibold text-slate-200 mb-1 line-clamp-2">${product.name}</h3>
                <p class="text-sm text-slate-400 mb-2">SKU: ${product.sku || 'N/A'}</p>
                <div class="flex justify-between items-center">
                    <span class="text-lg font-bold text-green-400">$${product.price}</span>
                    <span class="text-sm ${stockClass}">${stockText}</span>
                </div>
            </div>
        `;
    }

    /**
     * Handle product click - show variation modal or add to cart
     * @param {number} productId - Product ID
     * @param {string} preselectedSku - Pre-selected SKU for variations
     */
    async handleProductClick(productId, preselectedSku = null) {
        const product = this.stateManager.getState('products.all').find(p => p.id === productId);
        if (!product) return;

        this.stateManager.updateState('products.currentForModal', product);

        if (product.type === 'variable' && product.variations && product.variations.length > 0) {
            await this.showVariationModal(preselectedSku);
        } else {
            // Simple product - add directly to cart
            this.addToCart(product, 1);
        }
    }

    /**
     * Show variation selection modal
     * @param {string} preselectedSku - Pre-selected SKU
     */
    async showVariationModal(preselectedSku = null) {
        const product = this.stateManager.getState('products.currentForModal');
        if (!product || !product.variations) return;

        // Create and show modal
        const modal = this.createVariationModal(product, preselectedSku);
        document.body.appendChild(modal);
        modal.classList.remove('hidden');
    }

    /**
     * Create variation modal HTML
     * @param {Object} product - Product with variations
     * @param {string} preselectedSku - Pre-selected SKU
     * @returns {HTMLElement} Modal element
     */
    createVariationModal(product, preselectedSku) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        modal.innerHTML = `
            <div class="bg-slate-800 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-white">${product.name}</h2>
                    <button onclick="this.closest('.fixed').remove()" class="text-slate-400 hover:text-white">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="variation-list">
                    ${product.variations.map(variation => this.renderVariationOption(variation, preselectedSku)).join('')}
                </div>
            </div>
        `;
        return modal;
    }

    /**
     * Render variation option
     * @param {Object} variation - Variation object
     * @param {string} preselectedSku - Pre-selected SKU
     * @returns {string} HTML for variation option
     */
    renderVariationOption(variation, preselectedSku) {
        const isSelected = preselectedSku === variation.sku;
        const stockClass = variation.stock_status === 'instock' ? 'border-green-500' : 'border-red-500';
        
        return `
            <div class="border-2 ${isSelected ? 'border-blue-500' : stockClass} rounded-lg p-4 cursor-pointer hover:bg-slate-700 transition-colors"
                 onclick="productsManager.selectVariation(${variation.id})">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-semibold text-slate-200">${variation.name}</h3>
                    <span class="text-sm ${variation.stock_status === 'instock' ? 'text-green-400' : 'text-red-400'}">
                        ${variation.stock_status === 'instock' ? 'In Stock' : 'Out of Stock'}
                    </span>
                </div>
                <p class="text-slate-400 text-sm mb-2">SKU: ${variation.sku}</p>
                <div class="flex justify-between items-center">
                    <span class="text-lg font-bold text-green-400">$${variation.price}</span>
                    <span class="text-sm text-slate-400">Stock: ${variation.stock_quantity || 0}</span>
                </div>
            </div>
        `;
    }

    /**
     * Select variation and add to cart
     * @param {number} variationId - Variation ID
     */
    selectVariation(variationId) {
        const product = this.stateManager.getState('products.currentForModal');
        const variation = product.variations.find(v => v.id === variationId);
        
        if (variation && variation.stock_status === 'instock') {
            this.addToCart(variation, 1);
            // Close modal
            document.querySelector('.fixed.inset-0').remove();
        }
    }

    /**
     * Add product to cart
     * @param {Object} product - Product to add
     * @param {number} quantity - Quantity to add
     */
    addToCart(product, quantity = 1) {
        const cartItems = this.stateManager.getState('cart.items');
        const existingItem = cartItems.find(item => item.id === product.id);
        
        if (existingItem) {
            existingItem.qty += quantity;
        } else {
            cartItems.push({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                qty: quantity,
                sku: product.sku,
                type: product.type
            });
        }
        
        this.stateManager.updateState('cart.items', cartItems);
        
        // Show success message
        if (window.toastManager) {
            window.toastManager.show(`${product.name} added to cart`);
        }
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProductsManager;
}

