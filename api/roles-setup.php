<?php
/**
 * WP POS Role-Based Access Control - Database Setup
 * Creates necessary tables for RBAC system
 */

require_once(dirname(__FILE__) . '/../../../../wp-load.php');

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    die('Unauthorized access');
}

global $wpdb;
$charset_collate = $wpdb->get_charset_collate();

// Table 1: Roles
$table_roles = $wpdb->prefix . 'jpos_roles';
$sql_roles = "CREATE TABLE IF NOT EXISTS $table_roles (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    role_name varchar(50) NOT NULL,
    role_slug varchar(50) NOT NULL UNIQUE,
    description text,
    is_system_role tinyint(1) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY role_slug (role_slug)
) $charset_collate;";

// Table 2: Permissions
$table_permissions = $wpdb->prefix . 'jpos_permissions';
$sql_permissions = "CREATE TABLE IF NOT EXISTS $table_permissions (
    id mediumint(9) NOT NULL AUTO_INCREMENT,
    permission_name varchar(100) NOT NULL,
    permission_slug varchar(100) NOT NULL UNIQUE,
    category varchar(50) NOT NULL,
    description text,
    PRIMARY KEY (id),
    KEY category (category),
    KEY permission_slug (permission_slug)
) $charset_collate;";

// Table 3: Role Permissions (junction table)
$table_role_permissions = $wpdb->prefix . 'jpos_role_permissions';
$sql_role_permissions = "CREATE TABLE IF NOT EXISTS $table_role_permissions (
    role_id mediumint(9) NOT NULL,
    permission_id mediumint(9) NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    KEY role_id (role_id),
    KEY permission_id (permission_id)
) $charset_collate;";

// Table 4: User Roles
$table_user_roles = $wpdb->prefix . 'jpos_user_roles';
$sql_user_roles = "CREATE TABLE IF NOT EXISTS $table_user_roles (
    user_id bigint(20) NOT NULL,
    role_id mediumint(9) NOT NULL,
    assigned_by bigint(20),
    assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    KEY user_id (user_id),
    KEY role_id (role_id)
) $charset_collate;";

// Execute table creation
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql_roles);
dbDelta($sql_permissions);
dbDelta($sql_role_permissions);
dbDelta($sql_user_roles);

// Insert default permissions
$default_permissions = [
    // View Access
    ['View POS', 'view_pos', 'views', 'Access to POS page'],
    ['View Products', 'view_products', 'views', 'Access to products page'],
    ['View Orders', 'view_orders', 'views', 'Access to orders page'],
    ['View Reports', 'view_reports', 'views', 'Access to reports page'],
    ['View Sessions', 'view_sessions', 'views', 'Access to sessions page'],
    ['View Settings', 'view_settings', 'views', 'Access to settings page'],
    ['View Held Carts', 'view_held_carts', 'views', 'Access to held carts page'],
    
    // Product Actions
    ['Create Products', 'create_products', 'products', 'Create new products'],
    ['Edit Products', 'edit_products', 'products', 'Edit existing products'],
    ['Delete Products', 'delete_products', 'products', 'Delete products'],
    ['Manage Stock', 'manage_stock', 'products', 'Update product stock levels'],
    
    // Order Actions
    ['Create Orders', 'create_orders', 'orders', 'Process new orders'],
    ['Edit Orders', 'edit_orders', 'orders', 'Modify existing orders'],
    ['Delete Orders', 'delete_orders', 'orders', 'Delete orders'],
    ['Refund Orders', 'refund_orders', 'orders', 'Process refunds'],
    
    // Financial Actions
    ['Open Drawer', 'open_drawer', 'financial', 'Open cash drawer'],
    ['Close Drawer', 'close_drawer', 'financial', 'Close cash drawer'],
    ['View Financial Reports', 'view_financial_reports', 'financial', 'Access financial data'],
    
    // System Actions
    ['Manage Roles', 'manage_roles', 'system', 'Create and edit roles'],
    ['Assign Roles', 'assign_roles', 'system', 'Assign roles to users'],
    ['Manage Settings', 'manage_settings', 'system', 'Modify system settings'],
    ['View Audit Log', 'view_audit_log', 'system', 'Access audit logs'],
];

foreach ($default_permissions as $perm) {
    $wpdb->insert(
        $table_permissions,
        [
            'permission_name' => $perm[0],
            'permission_slug' => $perm[1],
            'category' => $perm[2],
            'description' => $perm[3]
        ],
        ['%s', '%s', '%s', '%s']
    );
}

// Insert default roles
$default_roles = [
    [
        'role_name' => 'Administrator',
        'role_slug' => 'administrator',
        'description' => 'Full access to all features and settings',
        'is_system_role' => 1,
        'permissions' => 'all' // Special case: all permissions
    ],
    [
        'role_name' => 'Manager',
        'role_slug' => 'manager',
        'description' => 'Access to reports, orders, products, and basic settings',
        'is_system_role' => 1,
        'permissions' => [
            'view_pos', 'view_products', 'view_orders', 'view_reports', 
            'view_sessions', 'view_held_carts', 'create_orders', 'edit_orders',
            'refund_orders', 'edit_products', 'manage_stock', 'open_drawer',
            'close_drawer', 'view_financial_reports'
        ]
    ],
    [
        'role_name' => 'Cashier',
        'role_slug' => 'cashier',
        'description' => 'Access to POS, orders, and held carts only',
        'is_system_role' => 1,
        'permissions' => [
            'view_pos', 'view_orders', 'view_held_carts', 'create_orders'
        ]
    ],
    [
        'role_name' => 'Storekeeper',
        'role_slug' => 'storekeeper',
        'description' => 'Access to products and stock management only',
        'is_system_role' => 1,
        'permissions' => [
            'view_products', 'create_products', 'edit_products', 'manage_stock'
        ]
    ]
];

foreach ($default_roles as $role) {
    // Insert role
    $wpdb->insert(
        $table_roles,
        [
            'role_name' => $role['role_name'],
            'role_slug' => $role['role_slug'],
            'description' => $role['description'],
            'is_system_role' => $role['is_system_role']
        ],
        ['%s', '%s', '%s', '%d']
    );
    
    $role_id = $wpdb->insert_id;
    
    // Assign permissions
    if ($role['permissions'] === 'all') {
        // Admin gets all permissions
        $all_perms = $wpdb->get_results("SELECT id FROM $table_permissions");
        foreach ($all_perms as $perm) {
            $wpdb->insert(
                $table_role_permissions,
                ['role_id' => $role_id, 'permission_id' => $perm->id],
                ['%d', '%d']
            );
        }
    } else {
        // Assign specific permissions
        foreach ($role['permissions'] as $perm_slug) {
            $perm = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_permissions WHERE permission_slug = %s",
                $perm_slug
            ));
            if ($perm) {
                $wpdb->insert(
                    $table_role_permissions,
                    ['role_id' => $role_id, 'permission_id' => $perm->id],
                    ['%d', '%d']
                );
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'message' => 'RBAC database tables created successfully',
    'tables_created' => [
        $table_roles,
        $table_permissions,
        $table_role_permissions,
        $table_user_roles
    ],
    'default_roles_created' => count($default_roles),
    'default_permissions_created' => count($default_permissions)
]);