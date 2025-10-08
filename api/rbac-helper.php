<?php
/**
 * WP POS RBAC Helper Functions
 * Reusable functions for role-based access control
 */

/**
 * Check if user has specific permission
 * @param int $user_id WordPress user ID
 * @param string $permission_slug Permission slug to check
 * @return bool True if user has permission
 */
function jpos_user_has_permission($user_id, $permission_slug) {
    global $wpdb;
    
    // WordPress admins always have all permissions
    $user = get_userdata($user_id);
    if ($user && in_array('administrator', $user->roles)) {
        return true;
    }
    
    $table_user_roles = $wpdb->prefix . 'jpos_user_roles';
    $table_role_permissions = $wpdb->prefix . 'jpos_role_permissions';
    $table_permissions = $wpdb->prefix . 'jpos_permissions';
    
    $has_permission = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*) FROM $table_user_roles ur
        INNER JOIN $table_role_permissions rp ON ur.role_id = rp.role_id
        INNER JOIN $table_permissions p ON rp.permission_id = p.id
        WHERE ur.user_id = %d AND p.permission_slug = %s
    ", $user_id, $permission_slug));
    
    return $has_permission > 0;
}

/**
 * Check if user has any of the specified permissions
 * @param int $user_id WordPress user ID
 * @param array $permission_slugs Array of permission slugs
 * @return bool True if user has at least one permission
 */
function jpos_user_has_any_permission($user_id, $permission_slugs) {
    foreach ($permission_slugs as $perm) {
        if (jpos_user_has_permission($user_id, $perm)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has all specified permissions
 * @param int $user_id WordPress user ID
 * @param array $permission_slugs Array of permission slugs
 * @return bool True if user has all permissions
 */
function jpos_user_has_all_permissions($user_id, $permission_slugs) {
    foreach ($permission_slugs as $perm) {
        if (!jpos_user_has_permission($user_id, $perm)) {
            return false;
        }
    }
    return true;
}

/**
 * Get all roles for a user
 * @param int $user_id WordPress user ID
 * @return array Array of role objects
 */
function jpos_get_user_roles($user_id) {
    global $wpdb;
    
    $table_user_roles = $wpdb->prefix . 'jpos_user_roles';
    $table_roles = $wpdb->prefix . 'jpos_roles';
    
    return $wpdb->get_results($wpdb->prepare("
        SELECT r.* FROM $table_roles r
        INNER JOIN $table_user_roles ur ON r.id = ur.role_id
        WHERE ur.user_id = %d
    ", $user_id), ARRAY_A);
}

/**
 * Get all permissions for a user
 * @param int $user_id WordPress user ID
 * @return array Array of permission slugs
 */
function jpos_get_user_permissions($user_id) {
    global $wpdb;
    
    // WordPress admins get all permissions
    $user = get_userdata($user_id);
    if ($user && in_array('administrator', $user->roles)) {
        $table_permissions = $wpdb->prefix . 'jpos_permissions';
        $all_perms = $wpdb->get_results("SELECT permission_slug FROM $table_permissions", ARRAY_A);
        return array_column($all_perms, 'permission_slug');
    }
    
    $table_user_roles = $wpdb->prefix . 'jpos_user_roles';
    $table_role_permissions = $wpdb->prefix . 'jpos_role_permissions';
    $table_permissions = $wpdb->prefix . 'jpos_permissions';
    
    $permissions = $wpdb->get_results($wpdb->prepare("
        SELECT DISTINCT p.permission_slug
        FROM $table_permissions p
        INNER JOIN $table_role_permissions rp ON p.id = rp.permission_id
        INNER JOIN $table_user_roles ur ON rp.role_id = ur.role_id
        WHERE ur.user_id = %d
    ", $user_id), ARRAY_A);
    
    return array_column($permissions, 'permission_slug');
}

/**
 * Verify permission and return 403 if not authorized
 * @param int $user_id WordPress user ID
 * @param string $permission_slug Permission slug to check
 * @param bool $json_response Return JSON response (true) or exit with message (false)
 */
function jpos_require_permission($user_id, $permission_slug, $json_response = true) {
    if (!jpos_user_has_permission($user_id, $permission_slug)) {
        http_response_code(403);
        if ($json_response) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Insufficient permissions',
                'required_permission' => $permission_slug
            ]);
        } else {
            echo 'Access denied: Insufficient permissions';
        }
        exit;
    }
}

/**
 * Check if tables exist (for setup verification)
 * @return bool True if all RBAC tables exist
 */
function jpos_rbac_tables_exist() {
    global $wpdb;
    
    $tables = [
        $wpdb->prefix . 'jpos_roles',
        $wpdb->prefix . 'jpos_permissions',
        $wpdb->prefix . 'jpos_role_permissions',
        $wpdb->prefix . 'jpos_user_roles'
    ];
    
    foreach ($tables as $table) {
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return false;
        }
    }
    
    return true;
}

/**
 * Assign role to user
 * @param int $user_id WordPress user ID
 * @param int $role_id Role ID
 * @param int $assigned_by User ID who is assigning the role
 * @return bool True on success
 */
function jpos_assign_role_to_user($user_id, $role_id, $assigned_by) {
    global $wpdb;
    
    $table_user_roles = $wpdb->prefix . 'jpos_user_roles';
    
    $result = $wpdb->insert(
        $table_user_roles,
        [
            'user_id' => $user_id,
            'role_id' => $role_id,
            'assigned_by' => $assigned_by
        ],
        ['%d', '%d', '%d']
    );
    
    return $result !== false;
}

/**
 * Remove role from user
 * @param int $user_id WordPress user ID
 * @param int $role_id Role ID
 * @return bool True on success
 */
function jpos_remove_role_from_user($user_id, $role_id) {
    global $wpdb;
    
    $table_user_roles = $wpdb->prefix . 'jpos_user_roles';
    
    return $wpdb->delete(
        $table_user_roles,
        ['user_id' => $user_id, 'role_id' => $role_id],
        ['%d', '%d']
    ) !== false;
}