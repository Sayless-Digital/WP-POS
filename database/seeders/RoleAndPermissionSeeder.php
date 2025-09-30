<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Product permissions
            'view products',
            'create products',
            'edit products',
            'delete products',
            'manage inventory',
            
            // Order permissions
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',
            'refund orders',
            
            // Customer permissions
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',
            
            // Cash drawer permissions
            'open cash drawer',
            'close cash drawer',
            'view cash drawer',
            'manage cash movements',
            
            // Report permissions
            'view reports',
            'export reports',
            
            // Settings permissions
            'manage settings',
            'manage users',
            'manage roles',
            
            // Sync permissions
            'sync woocommerce',
            'view sync logs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Admin role - has all permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Manager role - can do most things except manage users/roles
        $managerRole = Role::create(['name' => 'manager']);
        $managerRole->givePermissionTo([
            'view products',
            'create products',
            'edit products',
            'delete products',
            'manage inventory',
            'view orders',
            'create orders',
            'edit orders',
            'delete orders',
            'refund orders',
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',
            'open cash drawer',
            'close cash drawer',
            'view cash drawer',
            'manage cash movements',
            'view reports',
            'export reports',
            'sync woocommerce',
            'view sync logs',
        ]);

        // Cashier role - basic POS operations
        $cashierRole = Role::create(['name' => 'cashier']);
        $cashierRole->givePermissionTo([
            'view products',
            'view orders',
            'create orders',
            'view customers',
            'create customers',
            'open cash drawer',
            'close cash drawer',
            'view cash drawer',
        ]);

        // Inventory Manager role - focused on inventory
        $inventoryRole = Role::create(['name' => 'inventory_manager']);
        $inventoryRole->givePermissionTo([
            'view products',
            'create products',
            'edit products',
            'delete products',
            'manage inventory',
            'view orders',
            'view reports',
            'export reports',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created roles: admin, manager, cashier, inventory_manager');
        $this->command->info('Created ' . count($permissions) . ' permissions');
    }
}