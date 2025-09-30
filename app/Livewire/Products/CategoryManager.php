<?php

namespace App\Livewire\Products;

use App\Models\ProductCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategoryManager extends Component
{
    use WithPagination;

    // Search and filter properties
    public $search = '';
    public $parentFilter = '';
    public $statusFilter = '';
    public $perPage = 20;
    public $viewMode = 'tree'; // tree or list

    // Category form properties
    public $showModal = false;
    public $editMode = false;
    public $categoryId = null;
    public $name = '';
    public $slug = '';
    public $parentId = null;
    public $description = '';
    public $sortOrder = 0;
    public $isActive = true;

    // Bulk operations
    public $selectedCategories = [];
    public $selectAll = false;

    // Move category
    public $showMoveModal = false;
    public $moveCategoryId = null;
    public $newParentId = null;

    // Tree view expansion state
    public $expanded = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'parentFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'viewMode' => ['except' => 'tree'],
    ];

    protected $rules = [
        'name' => 'required|string|max:100',
        'slug' => 'required|string|max:100|unique:product_categories,slug',
        'parentId' => 'nullable|exists:product_categories,id',
        'description' => 'nullable|string',
        'sortOrder' => 'integer|min:0',
        'isActive' => 'boolean',
    ];

    protected $messages = [
        'name.required' => 'Category name is required',
        'slug.required' => 'Slug is required',
        'slug.unique' => 'This slug is already in use',
        'parentId.exists' => 'Selected parent category does not exist',
    ];

    public function mount()
    {
        $this->resetFilters();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingParentFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedName($value)
    {
        if (!$this->editMode) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedCategories = $this->getCategories()->pluck('id')->toArray();
        } else {
            $this->selectedCategories = [];
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->parentFilter = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function getCategories()
    {
        $query = ProductCategory::query()
            ->with(['parent', 'children'])
            ->withCount(['products', 'children'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('slug', 'like', '%' . $this->search . '%')
                          ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->parentFilter !== '', function ($q) {
                if ($this->parentFilter === 'root') {
                    $q->whereNull('parent_id');
                } else {
                    $q->where('parent_id', $this->parentFilter);
                }
            })
            ->when($this->statusFilter !== '', function ($q) {
                $q->where('is_active', $this->statusFilter === 'active');
            })
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($this->viewMode === 'list') {
            return $query->paginate($this->perPage);
        }

        return $query->get();
    }

    public function getCategoryTree()
    {
        $categories = ProductCategory::with(['children' => function ($query) {
            $query->orderBy('sort_order')->orderBy('name');
        }])
        ->withCount(['products', 'children'])
        ->whereNull('parent_id')
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();

        return $categories;
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

    public function editCategory($id)
    {
        $category = ProductCategory::findOrFail($id);
        
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->parentId = $category->parent_id;
        $this->description = $category->description;
        $this->sortOrder = $category->sort_order;
        $this->isActive = $category->is_active;
        $this->editMode = true;
        $this->showModal = true;
    }

    public function saveCategory()
    {
        // Update validation rules for edit mode
        if ($this->editMode) {
            $this->rules['slug'] = 'required|string|max:100|unique:product_categories,slug,' . $this->categoryId;
        }

        // Prevent circular parent relationship
        if ($this->editMode && $this->parentId) {
            $category = ProductCategory::find($this->categoryId);
            $descendants = $category->descendants()->pluck('id')->toArray();
            
            if (in_array($this->parentId, $descendants) || $this->parentId == $this->categoryId) {
                session()->flash('error', 'Cannot set a descendant or self as parent category');
                return;
            }
        }

        $this->validate();

        try {
            DB::beginTransaction();

            $data = [
                'name' => $this->name,
                'slug' => $this->slug,
                'parent_id' => $this->parentId,
                'description' => $this->description,
                'sort_order' => $this->sortOrder,
                'is_active' => $this->isActive,
            ];

            if ($this->editMode) {
                $category = ProductCategory::findOrFail($this->categoryId);
                $category->update($data);
                $message = 'Category updated successfully';
            } else {
                ProductCategory::create($data);
                $message = 'Category created successfully';
            }

            DB::commit();

            $this->dispatch('category-saved');
            session()->flash('success', $message);
            $this->closeModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to save category: ' . $e->getMessage());
        }
    }

    public function deleteCategory($id)
    {
        try {
            $category = ProductCategory::findOrFail($id);
            
            // Check if category has children
            if ($category->children()->count() > 0) {
                session()->flash('error', 'Cannot delete category with subcategories. Please delete or move subcategories first.');
                return;
            }

            // Check if category has products
            if ($category->products()->count() > 0) {
                session()->flash('error', 'Cannot delete category with products. Please reassign or delete products first.');
                return;
            }

            $category->delete();
            
            session()->flash('success', 'Category deleted successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete category: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $category = ProductCategory::findOrFail($id);
            $category->update(['is_active' => !$category->is_active]);
            
            session()->flash('success', 'Category status updated successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update category status: ' . $e->getMessage());
        }
    }

    public function bulkActivate()
    {
        if (empty($this->selectedCategories)) {
            session()->flash('error', 'No categories selected');
            return;
        }

        try {
            ProductCategory::whereIn('id', $this->selectedCategories)
                ->update(['is_active' => true]);
            
            session()->flash('success', count($this->selectedCategories) . ' categor(ies) activated successfully');
            $this->selectedCategories = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to activate categories: ' . $e->getMessage());
        }
    }

    public function bulkDeactivate()
    {
        if (empty($this->selectedCategories)) {
            session()->flash('error', 'No categories selected');
            return;
        }

        try {
            ProductCategory::whereIn('id', $this->selectedCategories)
                ->update(['is_active' => false]);
            
            session()->flash('success', count($this->selectedCategories) . ' categor(ies) deactivated successfully');
            $this->selectedCategories = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to deactivate categories: ' . $e->getMessage());
        }
    }

    public function bulkDelete()
    {
        if (empty($this->selectedCategories)) {
            session()->flash('error', 'No categories selected');
            return;
        }

        try {
            // Check if any selected category has children or products
            $categoriesWithChildren = ProductCategory::whereIn('id', $this->selectedCategories)
                ->has('children')
                ->count();

            $categoriesWithProducts = ProductCategory::whereIn('id', $this->selectedCategories)
                ->has('products')
                ->count();

            if ($categoriesWithChildren > 0) {
                session()->flash('error', 'Cannot delete categories with subcategories');
                return;
            }

            if ($categoriesWithProducts > 0) {
                session()->flash('error', 'Cannot delete categories with products');
                return;
            }

            ProductCategory::whereIn('id', $this->selectedCategories)->delete();
            
            session()->flash('success', count($this->selectedCategories) . ' categor(ies) deleted successfully');
            $this->selectedCategories = [];
            $this->selectAll = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete categories: ' . $e->getMessage());
        }
    }

    public function openMoveModal($id)
    {
        $this->moveCategoryId = $id;
        $this->newParentId = null;
        $this->showMoveModal = true;
    }

    public function closeMoveModal()
    {
        $this->showMoveModal = false;
        $this->moveCategoryId = null;
        $this->newParentId = null;
    }

    public function moveCategory()
    {
        try {
            $category = ProductCategory::findOrFail($this->moveCategoryId);
            
            // Prevent circular parent relationship
            if ($this->newParentId) {
                $descendants = $category->descendants()->pluck('id')->toArray();
                
                if (in_array($this->newParentId, $descendants) || $this->newParentId == $this->moveCategoryId) {
                    session()->flash('error', 'Cannot move to a descendant or self');
                    return;
                }
            }

            $category->update(['parent_id' => $this->newParentId]);
            
            session()->flash('success', 'Category moved successfully');
            $this->closeMoveModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to move category: ' . $e->getMessage());
        }
    }

    public function duplicateCategory($id)
    {
        try {
            DB::beginTransaction();

            $category = ProductCategory::findOrFail($id);
            
            $newCategory = $category->replicate();
            $newCategory->name = $category->name . ' (Copy)';
            $newCategory->slug = $category->slug . '-copy-' . time();
            $newCategory->save();

            DB::commit();

            session()->flash('success', 'Category duplicated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to duplicate category: ' . $e->getMessage());
        }
    }

    protected function resetForm()
    {
        $this->categoryId = null;
        $this->name = '';
        $this->slug = '';
        $this->parentId = null;
        $this->description = '';
        $this->sortOrder = 0;
        $this->isActive = true;
        $this->editMode = false;
    }

    public function render()
    {
        $data = [
            'allCategories' => ProductCategory::orderBy('name')->get(),
        ];

        if ($this->viewMode === 'tree') {
            $data['categoryTree'] = $this->getCategoryTree();
        } else {
            $data['categories'] = $this->getCategories();
        }

        return view('livewire.products.category-manager', $data);
    }
}