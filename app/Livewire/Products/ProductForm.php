<?php

namespace App\Livewire\Products;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class ProductForm extends Component
{
    use WithFileUploads;

    // Product properties
    public ?Product $product = null;
    public $isEditing = false;
    
    // Form fields
    public $name = '';
    public $sku = '';
    public $description = '';
    public $type = 'simple';
    public $price = '';
    public $cost_price = '';
    public $category_id = '';
    public $tax_rate = '';
    public $is_active = true;
    public $track_inventory = true;
    public $initial_quantity = 0;
    public $low_stock_threshold = 10;
    
    // Barcode
    public $barcode = '';
    public $barcode_type = 'EAN13';
    
    // Image
    public $image;
    public $existing_image_url = '';
    public $remove_image = false;
    
    // UI State
    public $activeTab = 'basic';
    public $showBarcodeScanner = false;
    public $autoGenerateSku = true;

    protected $productService;

    /**
     * Validation rules
     */
    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'sku' => [
                'required',
                'string',
                'max:100',
                'unique:products,sku' . ($this->isEditing ? ',' . $this->product->id : ''),
            ],
            'description' => 'nullable|string',
            'type' => 'required|in:simple,variable',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:product_categories,id',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'track_inventory' => 'boolean',
            'initial_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'barcode' => 'nullable|string|max:100',
            'barcode_type' => 'nullable|in:EAN13,UPC,CODE128,CODE39',
            'image' => 'nullable|image|max:2048', // 2MB max
        ];

        return $rules;
    }

    /**
     * Custom validation messages
     */
    protected $messages = [
        'name.required' => 'Product name is required',
        'sku.required' => 'SKU is required',
        'sku.unique' => 'This SKU is already in use',
        'price.required' => 'Price is required',
        'price.min' => 'Price must be greater than or equal to 0',
        'image.max' => 'Image size must not exceed 2MB',
    ];

    /**
     * Mount the component
     */
    public function mount(?Product $product = null)
    {
        $this->productService = app(ProductService::class);
        
        if ($product && $product->exists) {
            $this->isEditing = true;
            $this->product = $product;
            $this->loadProduct();
        } else {
            $this->tax_rate = config('pos.tax_rate', 0);
        }
    }

    /**
     * Load product data for editing
     */
    protected function loadProduct()
    {
        $this->name = $this->product->name;
        $this->sku = $this->product->sku;
        $this->description = $this->product->description;
        $this->type = $this->product->type;
        $this->price = $this->product->price;
        $this->cost_price = $this->product->cost_price;
        $this->category_id = $this->product->category_id;
        $this->tax_rate = $this->product->tax_rate;
        $this->is_active = $this->product->is_active;
        $this->track_inventory = $this->product->track_inventory;
        $this->existing_image_url = $this->product->image_url;
        
        if ($this->product->inventory) {
            $this->initial_quantity = $this->product->inventory->quantity;
            $this->low_stock_threshold = $this->product->inventory->low_stock_threshold;
        }
        
        // Load first barcode if exists
        $firstBarcode = $this->product->barcodes->first();
        if ($firstBarcode) {
            $this->barcode = $firstBarcode->barcode;
            $this->barcode_type = $firstBarcode->type;
        }
        
        $this->autoGenerateSku = false;
    }

    /**
     * Auto-generate SKU from product name
     */
    public function updatedName()
    {
        if ($this->autoGenerateSku && !$this->isEditing) {
            $this->sku = $this->generateSkuFromName($this->name);
        }
    }

    /**
     * Generate SKU from name
     */
    protected function generateSkuFromName($name)
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 6));
        $random = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        return $base . '-' . $random;
    }

    /**
     * Toggle auto-generate SKU
     */
    public function toggleAutoGenerateSku()
    {
        $this->autoGenerateSku = !$this->autoGenerateSku;
        if ($this->autoGenerateSku && $this->name) {
            $this->sku = $this->generateSkuFromName($this->name);
        }
    }

    /**
     * Switch active tab
     */
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    /**
     * Remove existing image
     */
    public function removeExistingImage()
    {
        $this->remove_image = true;
        $this->existing_image_url = '';
    }

    /**
     * Calculate profit margin
     */
    public function getProfitMarginProperty()
    {
        if (!$this->price || !$this->cost_price || $this->cost_price == 0) {
            return null;
        }
        
        return (($this->price - $this->cost_price) / $this->cost_price) * 100;
    }

    /**
     * Calculate markup
     */
    public function getMarkupProperty()
    {
        if (!$this->price || !$this->cost_price || $this->cost_price == 0) {
            return null;
        }
        
        return (($this->price - $this->cost_price) / $this->price) * 100;
    }

    /**
     * Save product
     */
    public function save()
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'sku' => $this->sku,
                'description' => $this->description,
                'type' => $this->type,
                'price' => $this->price,
                'cost_price' => $this->cost_price,
                'category_id' => $this->category_id ?: null,
                'tax_rate' => $this->tax_rate ?: 0,
                'is_active' => $this->is_active,
                'track_inventory' => $this->track_inventory,
                'initial_quantity' => $this->initial_quantity,
                'low_stock_threshold' => $this->low_stock_threshold,
                'barcode' => $this->barcode,
                'barcode_type' => $this->barcode_type,
            ];

            // Handle image upload
            if ($this->image) {
                $path = $this->image->store('products', 'public');
                $data['image_url'] = Storage::url($path);
            } elseif ($this->remove_image && $this->isEditing) {
                $data['image_url'] = null;
                // Delete old image
                if ($this->product->image_url) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $this->product->image_url));
                }
            }

            if ($this->isEditing) {
                $product = $this->productService->updateProduct($this->product, $data);
                $message = 'Product updated successfully';
            } else {
                $product = $this->productService->createProduct($data);
                $message = 'Product created successfully';
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $message
            ]);

            return redirect()->route('products.show', $product);

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error saving product: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Save and add another
     */
    public function saveAndAddAnother()
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'sku' => $this->sku,
                'description' => $this->description,
                'type' => $this->type,
                'price' => $this->price,
                'cost_price' => $this->cost_price,
                'category_id' => $this->category_id ?: null,
                'tax_rate' => $this->tax_rate ?: 0,
                'is_active' => $this->is_active,
                'track_inventory' => $this->track_inventory,
                'initial_quantity' => $this->initial_quantity,
                'low_stock_threshold' => $this->low_stock_threshold,
                'barcode' => $this->barcode,
                'barcode_type' => $this->barcode_type,
            ];

            // Handle image upload
            if ($this->image) {
                $path = $this->image->store('products', 'public');
                $data['image_url'] = Storage::url($path);
            }

            $this->productService->createProduct($data);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Product created successfully'
            ]);

            // Reset form for next product
            $this->reset([
                'name', 'sku', 'description', 'price', 'cost_price',
                'initial_quantity', 'barcode', 'image'
            ]);
            $this->autoGenerateSku = true;

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error saving product: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cancel and return to list
     */
    public function cancel()
    {
        return redirect()->route('products.index');
    }

    /**
     * Get categories for dropdown
     */
    public function getCategoriesProperty()
    {
        return ProductCategory::orderBy('name')->get();
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.products.product-form', [
            'categories' => $this->categories,
            'profitMargin' => $this->profitMargin,
            'markup' => $this->markup,
        ]);
    }
}