<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleManagement extends Component
{
    public $roles;
    public $permissions;
    public $showModal = false;
    public $editMode = false;
    public $roleId;
    
    // Form fields
    public $name;
    public $selectedPermissions = [];

    protected $rules = [
        'name' => 'required|string|max:255|unique:roles,name',
        'selectedPermissions' => 'array',
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->roles = Role::with('permissions')->get();
        $this->permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEditModal($roleId)
    {
        $this->resetForm();
        $this->editMode = true;
        $this->roleId = $roleId;
        
        $role = Role::with('permissions')->findOrFail($roleId);
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        
        $this->showModal = true;
    }

    public function save()
    {
        if ($this->editMode) {
            $this->rules['name'] = 'required|string|max:255|unique:roles,name,' . $this->roleId;
        }

        $this->validate();

        try {
            if ($this->editMode) {
                $role = Role::findOrFail($this->roleId);
                $role->update(['name' => $this->name]);
            } else {
                $role = Role::create(['name' => $this->name]);
            }

            // Sync permissions
            $role->syncPermissions($this->selectedPermissions);

            session()->flash('message', $this->editMode ? 'Role updated successfully.' : 'Role created successfully.');
            $this->closeModal();
            $this->loadData();
        } catch (\Exception $e) {
            session()->flash('error', 'Error saving role: ' . $e->getMessage());
        }
    }

    public function deleteRole($roleId)
    {
        try {
            $role = Role::findOrFail($roleId);
            
            // Prevent deletion of super-admin role
            if ($role->name === 'super-admin') {
                session()->flash('error', 'Cannot delete super-admin role.');
                return;
            }

            // Check if role is assigned to any users
            if ($role->users()->count() > 0) {
                session()->flash('error', 'Cannot delete role that is assigned to users.');
                return;
            }

            $role->delete();
            session()->flash('message', 'Role deleted successfully.');
            $this->loadData();
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting role: ' . $e->getMessage());
        }
    }

    public function togglePermission($permissionName)
    {
        if (in_array($permissionName, $this->selectedPermissions)) {
            $this->selectedPermissions = array_diff($this->selectedPermissions, [$permissionName]);
        } else {
            $this->selectedPermissions[] = $permissionName;
        }
    }

    public function selectAllPermissions($group)
    {
        $groupPermissions = $this->permissions[$group]->pluck('name')->toArray();
        $this->selectedPermissions = array_unique(array_merge($this->selectedPermissions, $groupPermissions));
    }

    public function deselectAllPermissions($group)
    {
        $groupPermissions = $this->permissions[$group]->pluck('name')->toArray();
        $this->selectedPermissions = array_diff($this->selectedPermissions, $groupPermissions);
    }

    private function resetForm()
    {
        $this->reset(['name', 'selectedPermissions', 'roleId']);
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.admin.role-management');
    }
}