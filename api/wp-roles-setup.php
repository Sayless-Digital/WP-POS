<?php
/**
 * WP POS Role Management API - WordPress Native Implementation
 * Complete CRUD operations for custom POS roles using WordPress capability system
 */

require_once(dirname(__FILE__) . '/../../wp-load.php');

header('Content-Type: application/json');

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    http_response_code(403);
    wp_send_json_error(['message' => 'Unauthorized access']);
    exit;
}

// Get action from GET or POST
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) :
          (isset($_POST['action']) ? sanitize_text_field($_POST['action']) : 'list');

/**
 * LIST - Get all custom POS roles
 */
if ($action === 'list') {
    $all_roles = wp_roles()->roles;
    $wppos_roles = [];
    
    // Get list of all POS capabilities
    $wppos_capabilities = get_option('wppos_capabilities', []);
    
    // Define role types - only show relevant management roles
    $predefined_roles = ['wppos_manager', 'wppos_cashier', 'wppos_storekeeper'];
    $system_roles = ['administrator', 'shop_manager']; // Only show these system roles
    $always_show_roles = array_merge($predefined_roles, $system_roles);
    
    foreach ($all_roles as $role_slug => $role_data) {
        // Include roles that:
        // 1. Are in the always-show list (system or predefined POS roles), OR
        // 2. Start with wppos_ (custom POS roles), OR
        // 3. Have wppos capabilities (roles with POS permissions assigned)
        $should_include = in_array($role_slug, $always_show_roles) ||
                         strpos($role_slug, 'wppos_') === 0 ||
                         array_intersect(array_keys($role_data['capabilities']), array_keys($wppos_capabilities));
        
        if ($should_include) {
            // Determine role type
            $role_type = 'custom';
            if (in_array($role_slug, $predefined_roles)) {
                $role_type = 'predefined';
            } elseif ($role_slug === 'administrator') {
                $role_type = 'wordpress';
            } elseif ($role_slug === 'shop_manager') {
                $role_type = 'woocommerce';
            }
            
            // Shop Manager and custom roles are editable, predefined and administrator are not
            $is_editable = !in_array($role_slug, array_merge($predefined_roles, ['administrator']));
            
            $wppos_roles[] = [
                'slug' => $role_slug,
                'name' => $role_data['name'],
                'capabilities' => array_keys(array_filter($role_data['capabilities'])),
                'is_predefined' => in_array($role_slug, $predefined_roles),
                'is_protected' => $role_slug === 'administrator', // Only administrator is fully protected
                'is_editable' => $is_editable,
                'role_type' => $role_type
            ];
        }
    }
    
    wp_send_json_success([
        'roles' => $wppos_roles,
        'available_capabilities' => $wppos_capabilities
    ]);
    exit;
}

/**
 * CHECK - Check if predefined roles exist
 */
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

/**
 * CREATE - Create a new custom role
 */
if ($action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $role_slug = sanitize_key($data['slug']);
    $role_name = sanitize_text_field($data['name']);
    $capabilities = isset($data['capabilities']) ? $data['capabilities'] : [];
    
    // Validate input
    if (empty($role_slug) || empty($role_name)) {
        wp_send_json_error(['message' => 'Role slug and name are required']);
        exit;
    }
    
    // Check if role already exists
    if (get_role($role_slug)) {
        wp_send_json_error(['message' => 'A role with this slug already exists']);
        exit;
    }
    
    // Prefix slug if it doesn't start with wppos_
    if (strpos($role_slug, 'wppos_') !== 0) {
        $role_slug = 'wppos_' . $role_slug;
    }
    
    // Build capabilities array
    $caps = ['read' => true];
    foreach ($capabilities as $cap) {
        $caps[sanitize_key($cap)] = true;
    }
    
    // Create the role
    $result = add_role($role_slug, $role_name, $caps);
    
    if ($result) {
        wp_send_json_success([
            'message' => 'Role created successfully',
            'role' => [
                'slug' => $role_slug,
                'name' => $role_name,
                'capabilities' => array_keys($caps)
            ]
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to create role']);
    }
    exit;
}

/**
 * UPDATE - Update an existing role's capabilities
 */
if ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $role_slug = sanitize_key($data['slug']);
    $role_name = isset($data['name']) ? sanitize_text_field($data['name']) : null;
    $capabilities = isset($data['capabilities']) ? $data['capabilities'] : [];
    
    // Get the role
    $role = get_role($role_slug);
    if (!$role) {
        wp_send_json_error(['message' => 'Role not found']);
        exit;
    }
    
    // Get all POS capabilities
    $all_wppos_caps = array_keys(get_option('wppos_capabilities', []));
    
    // Remove all current POS capabilities
    foreach ($all_wppos_caps as $cap) {
        if ($role->has_cap($cap)) {
            $role->remove_cap($cap);
        }
    }
    
    // Add new capabilities
    foreach ($capabilities as $cap) {
        $role->add_cap(sanitize_key($cap));
    }
    
    // Update role name if provided (requires removing and re-adding)
    if ($role_name && $role_name !== $role_slug) {
        global $wp_roles;
        $wp_roles->roles[$role_slug]['name'] = $role_name;
        update_option($wp_roles->role_key, $wp_roles->roles);
    }
    
    wp_send_json_success([
        'message' => 'Role updated successfully',
        'role' => [
            'slug' => $role_slug,
            'name' => $role_name ?: $role_slug,
            'capabilities' => $capabilities
        ]
    ]);
    exit;
}

/**
 * DELETE - Remove a custom role
 */
if ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $role_slug = sanitize_key($data['slug']);
    
    // Prevent deletion of predefined roles
    $protected_roles = ['wppos_manager', 'wppos_cashier', 'wppos_storekeeper', 'administrator', 'shop_manager'];
    if (in_array($role_slug, $protected_roles)) {
        wp_send_json_error(['message' => 'Cannot delete protected roles. Use the Quick Start section to reinstall predefined roles.']);
        exit;
    }
    
    // Check if role exists
    if (!get_role($role_slug)) {
        wp_send_json_error(['message' => 'Role not found']);
        exit;
    }
    
    // Remove the role
    remove_role($role_slug);
    
    wp_send_json_success(['message' => 'Role deleted successfully']);
    exit;
}

/**
 * UNINSTALL - Remove all POS roles and capabilities
 */
if ($action === 'uninstall') {
    // Remove predefined POS roles
    $wppos_roles = ['wppos_manager', 'wppos_cashier', 'wppos_storekeeper'];
    foreach ($wppos_roles as $role_slug) {
        remove_role($role_slug);
    }
    
    // Get all POS capabilities
    $wppos_capabilities = get_option('wppos_capabilities', []);
    
    // Remove POS capabilities from all remaining roles
    global $wp_roles;
    if (!isset($wp_roles)) {
        $wp_roles = new WP_Roles();
    }
    
    foreach ($wp_roles->roles as $role_slug => $role_data) {
        $role = get_role($role_slug);
        if ($role) {
            foreach (array_keys($wppos_capabilities) as $cap) {
                if ($role->has_cap($cap)) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
    
    // Remove stored capabilities list
    delete_option('wppos_capabilities');
    
    wp_send_json_success([
        'message' => 'All POS roles and capabilities have been removed successfully',
        'roles_removed' => $wppos_roles,
        'capabilities_cleaned' => count($wppos_capabilities)
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