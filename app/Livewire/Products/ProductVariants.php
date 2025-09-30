<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductService;
use Livewire\Component;

class ProductVariants extends Component
{
    public Product $product;
    public $variants = [];
    
    // Modal state
    public $showModal = false;
    public $editingVariant = null;
    public $isEditing = false;
    
    // Form fields
    public $variant_name = '';
    public $variant_sku = '';
    public $variant_price = '';
    public $variant_cost_price = '';
    public $variant_attributes = [];
    public $variant_barcode = '';
    public $variant_initial_quantity = 0;
    public $variant_low_stock_threshold = 10;
    public $variant_is_active = true;
    
    // Attribute management
    public $attribute_name = '';
    public $attribute_value = '';
    
    // Bulk actions
    public $selectedVariants = [];
    public $selectAll = false;

    protected $productService;

    /**
     * Validation rules
     */
    protected function rules()
    {
        return [
            'variant_name' => 'required|string|max:255',
            'variant_sku' => [
                'required',
                'string',
                'max:100',
                'unique:product_variants,sku' . ($this->isEditing ? ',' . $this->editingVariant->id : ''),
            ],
            'variant_price' => 'required|numeric|min:0',
            'variant_cost_price' => 'nullable|numeric|min:0',
            'variant_attributes' => 'nullable|array',
            'variant_barcode' => 'nullable|string|max:100',
            'variant_initial_quantity' => 'nullable|integer|min:0',
            'variant_low_stock_threshold' => 'nullable|integer|min:0',
            'variant_is_active' => 'boolean',
        ];
    }

    /**
     * Mount the component
     */
    public function mount(Product $product)
    {
        $this->product = $product;
        $this->productService = app(ProductService::class);
        $this->loadVariants();
    }

    /**
     * Load variants
     */
    public function loadVariants()
    {
        $this->variants = $this->product->variants()
            ->with(['inventory', 'barcodes'])
            ->get()
            ->toArray();
    }

    /**
     * Open modal for new variant
     */
    public function openCreateModal()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
        
        // Pre-fill with product defaults
        $this->variant_price = $this->product->price;
        $this->variant_cost_price = $this->product->cost_price;
    }

    /**
     * Open modal for editing variant
     */
    public function openEditModal($variantId)
    {
        $variant = ProductVariant::with(['inventory', 'barcodes'])->findOrFail($variantId);
        
        $this->editingVariant = $variant;
        $this->isEditing = true;
        $this->showModal = true;
        
        $this->variant_name = $variant->name;
        $this->variant_sku = $variant->sku;
        $this->variant_price = $variant->price;
        $this->variant_cost_price = $variant->cost_price;
        $this->variant_attributes = $variant->attributes ?? [];
        $this->variant_is_active = $variant->is_active;
        
        if ($variant->inventory) {
            $this->variant_initial_quantity = $variant->inventory->quantity;
            $this->variant_low_stock_threshold = $variant->inventory->low_stock_threshold;
        }
        
        $firstBarcode = $variant->barcodes->first();
        if ($firstBarcode) {
            $this->variant_barcode = $firstBarcode->barcode;
        }
    }

    /**
     * Close modal
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Reset form fields
     */
    protected function resetForm()
    {
        $this->reset([
            'editingVariant',
            'isEditing',
            'variant_name',
            'variant_sku',
            'variant_price',
            'variant_cost_price',
            'variant_attributes',
            'variant_barcode',
            'variant_initial_quantity',
            'variant_low_stock_threshold',
            'variant_is_active',
            'attribute_name',
            'attribute_value',
        ]);
    }

    /**
     * Add attribute to variant
     */
    public function addAttribute()
    {
        if (empty($this->attribute_name) || empty($this->attribute_value)) {
            return;
        }

        $this->variant_attributes[$this->attribute_name] = $this->attribute_value;
        
        $this->reset(['attribute_name', 'attribute_value']);
    }

    /**
     * Remove attribute from variant
     */
    public function removeAttribute($key)
    {
        unset($this->variant_attributes[$key]);
    }

    /**
     * Auto-generate variant SKU
     */
    public function generateVariantSku()
    {
        $base = $this->product->sku;
        $random = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        $this->variant_sku = $base . '-' . $random;
    }

    /**
     * Save variant
     */
    public function saveVariant()
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->variant_name,
                'sku' => $this->variant_sku,
                'price' => $this->variant_price,
                'cost_price' => $this->variant_cost_price,
                'attributes' => $this->variant_attributes,
                'is_active' => $this->variant_is_active,
                'barcode' => $this->variant_barcode,
                'initial_quantity' => $this->variant_initial_quantity,
                'low_stock_threshold' => $this->variant_low_stock_threshold,
            ];

            if ($this->isEditing) {
                // Update existing variant
                $this->editingVariant->update([
                    'name' => $data['name'],
                    'sku' => $data['sku'],
                    'price' => $data['price'],
                    'cost_price' => $data['cost_price'],
                    'attributes' => $data['attributes'],
                    'is_active' => $data['is_active'],
                ]);

                // Update inventory
                if ($this->editingVariant->inventory) {
                    $this->editingVariant->inventory->update([
                        'quantity' => $data['initial_quantity'],
                        'low_stock_threshold' => $data['low_stock_threshold'],
                    ]);
                }

                $message = 'Variant updated successfully';
            } else {
                // Create new variant
                $this->productService->createVariant($this->product, $data);
                $message = 'Variant created successfully';
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $message
            ]);

            $this->loadVariants();
            $this->closeModal();

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error saving variant: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete variant
     */
    public function deleteVariant($variantId)
    {
        try {
            $variant = ProductVariant::findOrFail($variantId);
            
            // Check if variant has order history
            if ($variant->orderItems()->exists()) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Cannot delete variant with order history. Deactivate it instead.'
                ]);
                return;
            }

            $variant->delete();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Variant deleted successfully'
            ]);

            $this->loadVariants();

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error deleting variant: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Toggle variant selection
     */
    public function toggleVariantSelection($variantId)
    {
        if (in_array($variantId, $this->selectedVariants)) {
            $this->selectedVariants = array_diff($this->selectedVariants, [$variantId]);
        } else {
            $this->selectedVariants[] = $variantId;
        }
    }

    /**
     * Select all variants
     */
    public function selectAllVariants()
    {
        $this->selectedVariants = collect($this->variants)->pluck('id')->toArray();
        $this->selectAll = true;
    }

    /**
     * Deselect all variants
     */
    public function deselectAll()
    {
        $this->selectedVariants = [];
        $this->selectAll = false;
    }

    /**
     * Bulk activate variants
     */
    public function bulkActivate()
    {
        if (empty($this->selectedVariants)) {
            return;
        }

        ProductVariant::whereIn('id', $this->selectedVariants)->update(['is_active' => true]);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => count($this->selectedVariants) . ' variants activated'
        ]);
        
        $this->loadVariants();
        $this->deselectAll();
    }

    /**
     * Bulk deactivate variants
     */
    public function bulkDeactivate()
    {
        if (empty($this->selectedVariants)) {
            return;
        }

        ProductVariant::whereIn('id', $this->selectedVariants)->update(['is_active' => false]);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => count($this->selectedVariants) . ' variants deactivated'
        ]);
        
        $this->loadVariants();
        $this->deselectAll();
    }

    /**
     * Bulk delete variants
     */
    public function bulkDelete()
    {
        if (empty($this->selectedVariants)) {
            return;
        }

        // Check if any variants have order history
        $variantsWithOrders = ProductVariant::whereIn('id', $this->selectedVariants)
            ->whereHas('orderItems')
            ->count();

        if ($variantsWithOrders > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Cannot delete variants with order history. Deactivate them instead.'
            ]);
            return;
        }

        ProductVariant::whereIn('id', $this->selectedVariants)->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => count($this->selectedVariants) . ' variants deleted'
        ]);
        
        $this->loadVariants();
        $this->deselectAll();
    }

    /**
     * Duplicate variant
     */
    public function duplicateVariant($variantId)
    {
        try {
            $variant = ProductVariant::findOrFail($variantId);
            
            $data = [
                'name' => $variant->name . ' (Copy)',
                'price' => $variant->price,
                'cost_price' => $variant->cost_price,
                'attributes' => $variant->attributes,
                'is_active' => false,
                'initial_quantity' => 0,
                'low_stock_threshold' => $variant->inventory?->low_stock_threshold ?? 10,
            ];

            $this->productService->createVariant($this->product, $data);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Variant duplicated successfully'
            ]);

            $this->loadVariants();

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error duplicating variant: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.products.product-variants');
    }
}