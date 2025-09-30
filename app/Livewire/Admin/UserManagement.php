<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public $showModal = false;
    public $editMode = false;
    public $userId;
    
    // Form fields
    public $name;
    public $email;
    public $password;
    public $password_confirmation;
    public $role;
    public $is_active = true;

    protected $queryString = ['search', 'roleFilter', 'statusFilter'];

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|exists:roles,name',
            'is_active' => 'boolean',
        ];

        if ($this->editMode) {
            $rules['email'] = 'required|email|max:255|unique:users,email,' . $this->userId;
            $rules['password'] = 'nullable|min:8|confirmed';
        } else {
            $rules['password'] = 'required|min:8|confirmed';
        }

        return $rules;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function openEditModal($userId)
    {
        $this->resetForm();
        $this->editMode = true;
        $this->userId = $userId;
        
        $user = User::findOrFail($userId);
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()?->name ?? '';
        $this->is_active = $user->is_active ?? true;
        
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        try {
            if ($this->editMode) {
                $user = User::findOrFail($this->userId);
                $user->update([
                    'name' => $this->name,
                    'email' => $this->email,
                    'is_active' => $this->is_active,
                ]);

                if ($this->password) {
                    $user->update(['password' => Hash::make($this->password)]);
                }
            } else {
                $user = User::create([
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($this->password),
                    'is_active' => $this->is_active,
                ]);
            }

            // Sync role
            $user->syncRoles([$this->role]);

            session()->flash('message', $this->editMode ? 'User updated successfully.' : 'User created successfully.');
            $this->closeModal();
        } catch (\Exception $e) {
            session()->flash('error', 'Error saving user: ' . $e->getMessage());
        }
    }

    public function deleteUser($userId)
    {
        try {
            $user = User::findOrFail($userId);
            
            if ($user->id === auth()->id()) {
                session()->flash('error', 'You cannot delete your own account.');
                return;
            }

            $user->delete();
            session()->flash('message', 'User deleted successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting user: ' . $e->getMessage());
        }
    }

    public function toggleStatus($userId)
    {
        try {
            $user = User::findOrFail($userId);
            
            if ($user->id === auth()->id()) {
                session()->flash('error', 'You cannot deactivate your own account.');
                return;
            }

            $user->update(['is_active' => !$user->is_active]);
            session()->flash('message', 'User status updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error updating user status: ' . $e->getMessage());
        }
    }

    public function resetPassword($userId)
    {
        try {
            $user = User::findOrFail($userId);
            $newPassword = 'password123'; // In production, generate random password and email it
            
            $user->update(['password' => Hash::make($newPassword)]);
            session()->flash('message', 'Password reset successfully. New password: ' . $newPassword);
        } catch (\Exception $e) {
            session()->flash('error', 'Error resetting password: ' . $e->getMessage());
        }
    }

    private function resetForm()
    {
        $this->reset(['name', 'email', 'password', 'password_confirmation', 'role', 'is_active', 'userId']);
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function render()
    {
        $query = User::with('roles')
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, function ($q) {
                $q->whereHas('roles', function ($query) {
                    $query->where('name', $this->roleFilter);
                });
            })
            ->when($this->statusFilter !== '', function ($q) {
                $q->where('is_active', $this->statusFilter);
            })
            ->latest();

        $users = $query->paginate(15);
        $roles = Role::all();

        return view('livewire.admin.user-management', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }
}