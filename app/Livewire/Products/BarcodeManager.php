<?php

namespace App\Livewire\Products;

use App\Models\Barcode;
use App\Models\Product;
use App\Models\ProductVariant;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class BarcodeManager extends Component
{
    use WithPagination;

    // Search and filter properties
    public $search = '';
    public $typeFilter = '';
    public $entityFilter = '';
    public $perPage = 20;

    // Barcode form properties
    public $showModal = false;
    public $editMode = false;
    public $barcodeId = null;
    public $barcode = '';
    public $barcodeType = 'EAN13';
    public $entityType = '';
    public $entityId = null;
    public $isPrimary = false;

    // Bulk operations
    public $selectedBarcodes = [];
    public $selectAll = false;

    // Print settings
    public $showPrintModal = false;
    public $printBarcodes = [];
    public $printLayout = 'grid'; // grid, list, labels
    public $printSize = 'medium'; // small, medium, large
    public $includePrice = false;
    public $includeProductName = true;

    // Generation settings
    public $showGenerateModal = false;
    public $generateCount = 1;
    public $generatePrefix = '';
    public $generateType = 'EAN13';

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'entityFilter' => ['except' => ''],
        'perPage' => ['except' => 20],
    ];

    protected $rules = [
        'barcode' => 'required|string|max:128|unique:barcodes,barcode',
        'barcodeType' => 'required|in:EAN13,EAN8,UPC,CODE128,CODE39',
        'entityType' => 'required|in:product,variant',
        'entityId' => 'required|integer|exists:products,id',
    ];

    protected $messages = [
        'barcode.required' => 'Barcode is required',
        'barcode.unique' => 'This barcode already exists',
        'barcodeType.required' => 'Barcode type is required',
        'entityType.required' => 'Entity type is required',
        'entityId.required' => 'Please select a product or variant',
        'entityId.exists' => 'Selected product does not exist',
    ];

    public function mount()
    {
        $this->resetFilters();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingEntityFilter()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedBarcodes = $this->getBarcodes()->pluck('id')->toArray();
        } else {
            $this->selectedBarcodes = [];
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->typeFilter = '';
        $this->entityFilter = '';
        $this->resetPage();
    }

    public function getBarcodes()
    {
        $query = Barcode::query()
            ->with(['barcodeable'])
            ->when($this->search, function ($q) {
                $q->where('barcode', 'like', '%' . $this->search . '%');
            })
            ->when($this->typeFilter, function ($q) {
                $q->where('type', $this->typeFilter);
            })
            ->when($this->entityFilter, function ($q) {
                if ($this->entityFilter === 'product') {
                    $q->where('barcodeable_type', Product::class);
                } elseif ($this->entityFilter === 'variant') {
                    $q->where('barcodeable_type', ProductVariant::class);
                }
            })
            ->latest();

        return $query->paginate($this->perPage);
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function editBarcode($id)
    {
        $barcode = Barcode::findOrFail($id);
        
        $this->barcodeId = $barcode->id;
        $this->barcode = $barcode->barcode;
        $this->barcodeType = $barcode->type;
        $this->isPrimary = $barcode->is_primary;
        
        // Determine entity type
        if ($barcode->barcodeable_type === Product::class) {
            $this->entityType = 'product';
        } else {
            $this->entityType = 'variant';
        }
        
        $this->entityId = $barcode->barcodeable_id;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function saveBarcode()
    {
        // Update validation rules for edit mode
        if ($this->editMode) {
            $this->rules['barcode'] = 'required|string|max:128|unique:barcodes,barcode,' . $this->barcodeId;
        }

        // Update entity validation based on type
        if ($this->entityType === 'variant') {
            $this->rules['entityId'] = 'required|integer|exists:product_variants,id';
        }

        $this->validate();

        try {
            DB::beginTransaction();

            $entityClass = $this->entityType === 'product' ? Product::class : ProductVariant::class;

            $data = [
                'barcode' => $this->barcode,
                'type' => $this->barcodeType,
                'barcodeable_type' => $entityClass,
                'barcodeable_id' => $this->entityId,
                'is_primary' => $this->isPrimary,
            ];

            if ($this->editMode) {
                $barcode = Barcode::findOrFail($this->barcodeId);
                $barcode->update($data);
                $message = 'Barcode updated successfully';
            } else {
                Barcode::create($data);
                $message = 'Barcode created successfully';
            }

            DB::commit();

            $this->dispatch('barcode-saved');
            session()->flash('success', $message);
            $this->closeModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to save barcode: ' . $e->getMessage());
        }
    }

    public function deleteBarcode($id)
    {
        try {
            $barcode = Barcode::findOrFail($id);
            $barcode->delete();
            
            session()->flash('success', 'Barcode deleted successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete barcode: ' . $e->getMessage());
        }
    }

    public function bulkDelete()
    {
        if (empty($this->selectedBarcodes)) {
            session()->flash('error', 'No barcodes selected');
            return;
        }

        try {
            Barcode::whereIn('id', $this->selectedBarcodes)->delete();
            
            session()->flash('success', count($this->selectedBarcodes) . ' barcode(s) deleted successfully');
            $this->selectedBarcodes = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete barcodes: ' . $e->getMessage());
        }
    }

    public function openPrintModal()
    {
        if (empty($this->selectedBarcodes)) {
            session()->flash('error', 'No barcodes selected for printing');
            return;
        }

        $this->printBarcodes = Barcode::with(['barcodeable'])
            ->whereIn('id', $this->selectedBarcodes)
            ->get()
            ->toArray();

        $this->showPrintModal = true;
    }

    public function closePrintModal()
    {
        $this->showPrintModal = false;
        $this->printBarcodes = [];
    }

    public function print()
    {
        // This will trigger JavaScript to print
        $this->dispatch('print-barcodes');
    }

    public function openGenerateModal()
    {
        $this->resetGenerateForm();
        $this->showGenerateModal = true;
    }

    public function closeGenerateModal()
    {
        $this->showGenerateModal = false;
        $this->resetGenerateForm();
    }

    public function generateBarcodes()
    {
        $this->validate([
            'generateCount' => 'required|integer|min:1|max:100',
            'generateType' => 'required|in:EAN13,EAN8,UPC,CODE128,CODE39',
        ]);

        try {
            DB::beginTransaction();

            $generated = [];
            
            for ($i = 0; $i < $this->generateCount; $i++) {
                $barcode = $this->generateBarcodeNumber($this->generateType);
                
                // Ensure uniqueness
                while (Barcode::where('barcode', $barcode)->exists()) {
                    $barcode = $this->generateBarcodeNumber($this->generateType);
                }

                $generated[] = [
                    'barcode' => $barcode,
                    'type' => $this->generateType,
                    'barcodeable_type' => null,
                    'barcodeable_id' => null,
                    'is_primary' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Barcode::insert($generated);

            DB::commit();

            session()->flash('success', $this->generateCount . ' barcode(s) generated successfully');
            $this->closeGenerateModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to generate barcodes: ' . $e->getMessage());
        }
    }

    protected function generateBarcodeNumber($type)
    {
        $prefix = $this->generatePrefix ?: '';
        
        return match($type) {
            'EAN13' => $this->generateEAN13($prefix),
            'EAN8' => $this->generateEAN8($prefix),
            'UPC' => $this->generateUPC($prefix),
            'CODE128', 'CODE39' => $this->generateCODE128($prefix),
            default => $this->generateEAN13($prefix),
        };
    }

    protected function generateEAN13($prefix = '')
    {
        // Generate 12 digits (13th is check digit)
        $code = $prefix . str_pad(rand(0, 999999999999), 12 - strlen($prefix), '0', STR_PAD_LEFT);
        
        // Calculate check digit
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$code[$i] * (($i % 2 === 0) ? 1 : 3);
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return $code . $checkDigit;
    }

    protected function generateEAN8($prefix = '')
    {
        // Generate 7 digits (8th is check digit)
        $code = $prefix . str_pad(rand(0, 9999999), 7 - strlen($prefix), '0', STR_PAD_LEFT);
        
        // Calculate check digit
        $sum = 0;
        for ($i = 0; $i < 7; $i++) {
            $sum += (int)$code[$i] * (($i % 2 === 0) ? 3 : 1);
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return $code . $checkDigit;
    }

    protected function generateUPC($prefix = '')
    {
        // Generate 11 digits (12th is check digit)
        $code = $prefix . str_pad(rand(0, 99999999999), 11 - strlen($prefix), '0', STR_PAD_LEFT);
        
        // Calculate check digit
        $sum = 0;
        for ($i = 0; $i < 11; $i++) {
            $sum += (int)$code[$i] * (($i % 2 === 0) ? 3 : 1);
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return $code . $checkDigit;
    }

    protected function generateCODE128($prefix = '')
    {
        // Generate alphanumeric code
        $length = 12 - strlen($prefix);
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = $prefix;
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $code;
    }

    protected function resetForm()
    {
        $this->barcodeId = null;
        $this->barcode = '';
        $this->barcodeType = 'EAN13';
        $this->entityType = '';
        $this->entityId = null;
        $this->isPrimary = false;
        $this->editMode = false;
    }

    protected function resetGenerateForm()
    {
        $this->generateCount = 1;
        $this->generatePrefix = '';
        $this->generateType = 'EAN13';
    }

    public function render()
    {
        return view('livewire.products.barcode-manager', [
            'barcodes' => $this->getBarcodes(),
            'products' => Product::where('is_active', true)->get(),
            'variants' => ProductVariant::with('product')->get(),
        ]);
    }
}