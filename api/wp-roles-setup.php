<?php
/**
 * WP POS Role Setup - WordPress Native Implementation
 * Creates custom POS roles using WordPress capability system
 */

require_once(dirname(__FILE__) . '/../../wp-load.php');

header('Content-Type: application/json');

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    http_response_code(403);
    wp_send_json_error(['message' => 'Unauthorized access']);
    exit;
}

// Get action
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'setup';

// Check roles status
if ($action === 'check') {
    $wppos_roles = ['wppos_manager', 'wppos_cashier', 'wppos_storekeeper'];
    $roles_exist = [];
    
    foreach ($wppos_roles as $role_slug) {
        $role = get_role($role_slug);
        $roles_exist[$role_slug] = $role !== null;
    }
    
    $all_exist = !in_array(false, $roles_exist);
    
    wp_send_json_success([
        'roles_exist' => $all_exist,
        'details' => $roles_exist,
        'roles' => [
            'wppos_manager' => 'POS Manager',
            'wppos_cashier' => 'POS Cashier',
            'wppos_storekeeper' => 'POS Storekeeper'
        ]
    ]);
    exit;
}

// Setup roles (action === 'setup')

/**
 * Define all POS capabilities
 */
$wppos_capabilities = [
    // View Access
    'wppos_view_pos' => 'Access POS page',
    'wppos_view_products' => 'View products page',
    'wppos_view_orders' => 'View orders page',
    'wppos_view_reports' => 'View reports page',
    'wppos_view_sessions' => 'View sessions page',
    'wppos_view_settings' => 'View settings page',
    'wppos_view_held_carts' => 'View held carts',
    
    // Product Management
    'wppos_create_products' => 'Create new products',
    'wppos_edit_products' => 'Edit existing products',
    'wppos_delete_products' => 'Delete products',
    'wppos_manage_stock' => 'Manage product stock levels',
    
    // Order Management
    'wppos_create_orders' => 'Process new orders',
    'wppos_edit_orders' => 'Edit existing orders',
    'wppos_delete_orders' => 'Delete orders',
    'wppos_refund_orders' => 'Process refunds',
    'wppos_view_order_details' => 'View detailed order information',
    
    // Financial
    'wppos_open_drawer' => 'Open cash drawer',
    'wppos_close_drawer' => 'Close cash drawer',
    'wppos_view_financial_reports' => 'View financial reports and analytics',
    
    // System Administration
    'wppos_manage_settings' => 'Modify system settings',
    'wppos_manage_users' => 'Manage POS users and assign roles',
    'wppos_view_audit_log' => 'Access system audit logs',
];

/**
 * Remove any existing custom POS roles
 */
$existing_roles = ['wppos_manager', 'wppos_cashier', 'wppos_storekeeper'];
foreach ($existing_roles as $role_slug) {
    remove_role($role_slug);
}

/**
 * Create POS Manager Role
 * Full access to POS operations except user management
 */
add_role('wppos_manager', 'POS Manager', [
    // View Access
    'wppos_view_pos' => true,
    'wppos_view_products' => true,
    'wppos_view_orders' => true,
    'wppos_view_reports' => true,
    'wppos_view_sessions' => true,
    'wppos_view_settings' => true,
    'wppos_view_held_carts' => true,
    
    // Product Management
    'wppos_edit_products' => true,
    'wppos_manage_stock' => true,
    
    // Order Management
    'wppos_create_orders' => true,
    'wppos_edit_orders' => true,
    'wppos_delete_orders' => true,
    'wppos_refund_orders' => true,
    'wppos_view_order_details' => true,
    
    // Financial
    'wppos_open_drawer' => true,
    'wppos_close_drawer' => true,
    'wppos_view_financial_reports' => true,
    
    // System
    'wppos_manage_settings' => true,
    'wppos_view_audit_log' => true,
    
    // Read access
    'read' => true,
]);

/**
 * Create POS Cashier Role
 * Basic POS operations only - no access to reports, settings, or management features
 */
add_role('wppos_cashier', 'POS Cashier', [
    // View Access
    'wppos_view_pos' => true,
    'wppos_view_orders' => true,
    'wppos_view_held_carts' => true,
    
    // Order Management
    'wppos_create_orders' => true,
    'wppos_view_order_details' => true,
    
    // Read access
    'read' => true,
]);

/**
 * Create POS Storekeeper Role
 * Product and inventory management only
 */
add_role('wppos_storekeeper', 'POS Storekeeper', [
    // View Access
    'wppos_view_products' => true,
    
    // Product Management
    'wppos_create_products' => true,
    'wppos_edit_products' => true,
    'wppos_manage_stock' => true,
    
    // Read access
    'read' => true,
]);

/**
 * Add all POS capabilities to Administrator role
 */
$admin_role = get_role('administrator');
if ($admin_role) {
    foreach (array_keys($wppos_capabilities) as $cap) {
        $admin_role->add_cap($cap);
    }
}

/**
 * Add selected POS capabilities to Shop Manager role (if it exists from WooCommerce)
 */
$shop_manager_role = get_role('shop_manager');
if ($shop_manager_role) {
    $shop_manager_caps = [
        'wppos_view_pos',
        'wppos_view_products',
        'wppos_view_orders',
        'wppos_view_reports',
        'wppos_view_held_carts',
        'wppos_create_orders',
        'wppos_edit_orders',
        'wppos_refund_orders',
        'wppos_view_order_details',
        'wppos_edit_products',
        'wppos_manage_stock',
        'wppos_view_financial_reports',
    ];
    
    foreach ($shop_manager_caps as $cap) {
        $shop_manager_role->add_cap($cap);
    }
}

// Store capability list for reference
update_option('wppos_capabilities', $wppos_capabilities);

// Return success response
wp_send_json_success([
    'message' => 'POS roles and capabilities created successfully',
    'roles_created' => [
        'wppos_manager' => 'POS Manager - Full POS access',
        'wppos_cashier' => 'POS Cashier - Basic operations only',
        'wppos_storekeeper' => 'POS Storekeeper - Inventory management only'
    ],
    'capabilities_added' => count($wppos_capabilities),
    'administrator_updated' => true,
    'shop_manager_updated' => $shop_manager_role ? true : false
]);