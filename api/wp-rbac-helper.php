<?php
/**
 * WP POS RBAC Helper - WordPress Native Implementation
 * Simple wrapper functions for WordPress capability system
 */

/**
 * Check if current user can access POS system
 * @return bool
 */
function wppos_can_access_pos() {
    return current_user_can('wppos_view_pos') || current_user_can('manage_options');
}

/**
 * Check if user has specific POS capability
 * @param string $capability Capability slug
 * @return bool
 */
function wppos_user_can($capability) {
    return current_user_can($capability) || current_user_can('manage_options');
}

/**
 * Require specific capability or die with error
 * @param string $capability Required capability
 * @param string $message Error message
 */
function wppos_require_cap($capability, $message = 'Insufficient permissions') {
    if (!wppos_user_can($capability)) {
        http_response_code(403);
        wp_send_json_error(['message' => $message, 'required_capability' => $capability]);
        exit;
    }
}

/**
 * Get all POS capabilities for current user
 * @param int|null $user_id User ID (null for current user)
 * @return array Array of capability slugs
 */
function wppos_get_user_capabilities($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return [];
    }
    
    $capabilities = [];
    $wppos_caps = get_option('wppos_capabilities', []);
    
    foreach (array_keys($wppos_caps) as $cap) {
        if ($user->has_cap($cap)) {
            $capabilities[] = $cap;
        }
    }
    
    return $capabilities;
}

/**
 * Get user's POS roles
 * @param int|null $user_id User ID (null for current user)
 * @return array Array of role names
 */
function wppos_get_user_roles($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        return [];
    }
    
    // Return ALL WordPress roles - let the frontend decide what to do with them
    // This ensures administrators, shop_managers, and any other roles are properly returned
    return $user->roles;
}

/**
 * Get role display name
 * @param string $role_slug Role slug
 * @return string Display name
 */
function wppos_get_role_name($role_slug) {
    $names = [
        'administrator' => 'Administrator',
        'shop_manager' => 'Shop Manager',
        'wppos_manager' => 'POS Manager',
        'wppos_cashier' => 'POS Cashier',
        'wppos_storekeeper' => 'POS Storekeeper'
    ];
    
    return isset($names[$role_slug]) ? $names[$role_slug] : ucwords(str_replace('_', ' ', $role_slug));
}

/**
 * Check if user has any POS role
 * @param int|null $user_id User ID (null for current user)
 * @return bool
 */
function wppos_has_pos_role($user_id = null) {
    $roles = wppos_get_user_roles($user_id);
    return !empty($roles);
}

/**
 * Get all available POS roles
 * @return array Array of role objects with slug, name, and capabilities
 */
function wppos_get_all_roles() {
    global $wp_roles;
    
    $wppos_role_slugs = ['wppos_manager', 'wppos_cashier', 'wppos_storekeeper'];
    $roles_data = [];
    
    foreach ($wppos_role_slugs as $role_slug) {
        $role = get_role($role_slug);
        if ($role) {
            $roles_data[] = [
                'slug' => $role_slug,
                'name' => wppos_get_role_name($role_slug),
                'capabilities' => array_keys(array_filter($role->capabilities))
            ];
        }
    }
    
    return $roles_data;
}

/**
 * Assign POS role to user
 * @param int $user_id User ID
 * @param string $role_slug Role slug to assign
 * @return bool Success
 */
function wppos_assign_role($user_id, $role_slug) {
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    $user->add_role($role_slug);
    return true;
}

/**
 * Remove POS role from user
 * @param int $user_id User ID
 * @param string $role_slug Role slug to remove
 * @return bool Success
 */
function wppos_remove_role($user_id, $role_slug) {
    $user = get_userdata($user_id);
    if (!$user) {
        return false;
    }
    
    $user->remove_role($role_slug);
    return true;
}