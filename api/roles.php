<?php
/**
 * WP POS Role-Based Access Control API
 * Handles role management, permissions, and user assignments
 */

require_once(dirname(__FILE__) . '/../../../../wp-load.php');
header('Content-Type: application/json');

// Verify WordPress authentication
if (!is_user_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

global $wpdb;
$current_user = wp_get_current_user();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

// Helper function to check if user has permission
function user_has_permission($user_id, $permission_slug) {
    global $wpdb;
    
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

// Helper function to get user roles
function get_user_roles($user_id) {
    global $wpdb;
    
    $table_user_roles = $wpdb->prefix . 'jpos_user_roles';
    $table_roles = $wpdb->prefix . 'jpos_roles';
    
    return $wpdb->get_results($wpdb->prepare("
        SELECT r.* FROM $table_roles r
        INNER JOIN $table_user_roles ur ON r.id = ur.role_id
        WHERE ur.user_id = %d
    ", $user_id), ARRAY_A);
}

// Helper function to get role permissions
function get_role_permissions($role_id) {
    global $wpdb;
    
    $table_role_permissions = $wpdb->prefix . 'jpos_role_permissions';
    $table_permissions = $wpdb->prefix . 'jpos_permissions';
    
    return $wpdb->get_results($wpdb->prepare("
        SELECT p.* FROM $table_permissions p
        INNER JOIN $table_role_permissions rp ON p.id = rp.permission_id
        WHERE rp.role_id = %d
    ", $role_id), ARRAY_A);
}

// Route requests based on method and action
switch ($method) {
    case 'GET':
        handle_get_request($action, $current_user);
        break;
    case 'POST':
        handle_post_request($action, $current_user);
        break;
    case 'PUT':
        handle_put_request($action, $current_user);
        break;
    case 'DELETE':
        handle_delete_request($action, $current_user);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}

// GET request handler
function handle_get_request($action, $current_user) {
    global $wpdb;
    
    switch ($action) {
        case 'roles':
            // Get all roles
            if (!user_has_permission($current_user->ID, 'manage_roles') && !current_user_can('manage_options')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                return;
            }
            
            $table_roles = $wpdb->prefix . 'jpos_roles';
            $roles = $wpdb->get_results("SELECT * FROM $table_roles ORDER BY role_name", ARRAY_A);
            
            // Add permission count to each role
            foreach ($roles as &$role) {
                $role['permissions'] = get_role_permissions($role['id']);
                $role['permission_count'] = count($role['permissions']);
            }
            
            echo json_encode(['success' => true, 'data' => $roles]);
            break;
            
        case 'role':
            // Get specific role
            $role_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if (!$role_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Role ID required']);
                return;
            }
            
            $table_roles = $wpdb->prefix . 'jpos_roles';
            $role = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_roles WHERE id = %d", $role_id), ARRAY_A);
            
            if (!$role) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Role not found']);
                return;
            }
            
            $role['permissions'] = get_role_permissions($role_id);
            echo json_encode(['success' => true, 'data' => $role]);
            break;
            
        case 'permissions':
            // Get all permissions
            $table_permissions = $wpdb->prefix . 'jpos_permissions';
            $permissions = $wpdb->get_results("SELECT * FROM $table_permissions ORDER BY category, permission_name", ARRAY_A);
            
            // Group by category
            $grouped = [];
            foreach ($permissions as $perm) {
                $category = $perm['category'];
                if (!isset($grouped[$category])) {
                    $grouped[$category] = [];
                }
                $grouped[$category][] = $perm;
            }
            
            echo json_encode(['success' => true, 'data' => $grouped]);
            break;
            
        case 'user_roles':
            // Get roles for a specific user
            $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user->ID;
            $roles = get_user_roles($user_id);
            
            echo json_encode(['success' => true, 'data' => $roles]);
            break;
            
        case 'user_permissions':
            // Get all permissions for current user
            $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user->ID;
            
            $table_user_roles = $wpdb->prefix . 'jpos_user_roles';
            $table_role_permissions = $wpdb->prefix . 'jpos_role_permissions';
            $table_permissions = $wpdb->prefix . 'jpos_permissions';
            
            $permissions = $wpdb->get_results($wpdb->prepare("
                SELECT DISTINCT p.permission_slug, p.permission_name, p.category
                FROM $table_permissions p
                INNER JOIN $table_role_permissions rp ON p.id = rp.permission_id
                INNER JOIN $table_user_roles ur ON rp.role_id = ur.role_id
                WHERE ur.user_id = %d
            ", $user_id), ARRAY_A);
            
            echo json_encode(['success' => true, 'data' => $permissions]);
            break;
            
        case 'users':
            // Get all users with their roles
            if (!user_has_permission($current_user->ID, 'assign_roles') && !current_user_can('manage_options')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                return;
            }
            
            $users = get_users(['fields' => ['ID', 'display_name', 'user_email', 'user_login']]);
            $users_with_roles = [];
            
            foreach ($users as $user) {
                $user_roles = get_user_roles($user->ID);
                $users_with_roles[] = [
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'email' => $user->user_email,
                    'username' => $user->user_login,
                    'roles' => $user_roles
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $users_with_roles]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}

// POST request handler
function handle_post_request($action, $current_user) {
    global $wpdb;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create_role':
            // Create new role
            if (!user_has_permission($current_user->ID, 'manage_roles') && !current_user_can('manage_options')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                return;
            }
            
            $role_name = sanitize_text_field($data['role_name']);
            $role_slug = sanitize_title($data['role_slug']);
            $description = sanitize_textarea_field($data['description']);
            $permissions = isset($data['permissions']) ? $data['permissions'] : [];
            
            $table_roles = $wpdb->prefix . 'jpos_roles';
            $table_role_permissions = $wpdb->prefix . 'jpos_role_permissions';
            
            // Insert role
            $result = $wpdb->insert(
                $table_roles,
                [
                    'role_name' => $role_name,
                    'role_slug' => $role_slug,
                    'description' => $description,
                    'is_system_role' => 0
                ],
                ['%s', '%s', '%s', '%d']
            );
            
            if (!$result) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to create role']);
                return;
            }
            
            $role_id = $wpdb->insert_id;
            
            // Assign permissions
            foreach ($permissions as $perm_id) {
                $wpdb->insert(
                    $table_role_permissions,
                    ['role_id' => $role_id, 'permission_id' => intval($perm_id)],
                    ['%d', '%d']
                );
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => ['role_id' => $role_id]
            ]);
            break;
            
        case 'assign_role':
            // Assign role to user
            if (!user_has_permission($current_user->ID, 'assign_roles') && !current_user_can('manage_options')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                return;
            }
            
            $user_id = intval($data['user_id']);
            $role_id = intval($data['role_id']);
            
            $table_user_roles = $wpdb->prefix . 'jpos_user_roles';
            
            $result = $wpdb->insert(
                $table_user_roles,
                [
                    'user_id' => $user_id,
                    'role_id' => $role_id,
                    'assigned_by' => $current_user->ID
                ],
                ['%d', '%d', '%d']
            );
            
            if (!$result) {
                echo json_encode(['success' => false, 'error' => 'Role already assigned or invalid data']);
                return;
            }
            
            echo json_encode(['success' => true, 'message' => 'Role assigned successfully']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}

// PUT request handler
function handle_put_request($action, $current_user) {
    global $wpdb;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update_role':
            // Update existing role
            if (!user_has_permission($current_user->ID, 'manage_roles') && !current_user_can('manage_options')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                return;
            }
            
            $role_id = intval($data['role_id']);
            $role_name = sanitize_text_field($data['role_name']);
            $description = sanitize_textarea_field($data['description']);
            $permissions = isset($data['permissions']) ? $data['permissions'] : [];
            
            $table_roles = $wpdb->prefix . 'jpos_roles';
            $table_role_permissions = $wpdb->prefix . 'jpos_role_permissions';
            
            // Check if system role
            $is_system = $wpdb->get_var($wpdb->prepare(
                "SELECT is_system_role FROM $table_roles WHERE id = %d",
                $role_id
            ));
            
            if ($is_system && !current_user_can('manage_options')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Cannot modify system roles']);
                return;
            }
            
            // Update role
            $wpdb->update(
                $table_roles,
                [
                    'role_name' => $role_name,
                    'description' => $description
                ],
                ['id' => $role_id],
                ['%s', '%s'],
                ['%d']
            );
            
            // Update permissions
            $wpdb->delete($table_role_permissions, ['role_id' => $role_id], ['%d']);
            
            foreach ($permissions as $perm_id) {
                $wpdb->insert(
                    $table_role_permissions,
                    ['role_id' => $role_id, 'permission_id' => intval($perm_id)],
                    ['%d', '%d']
                );
            }
            
            echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}

// DELETE request handler
function handle_delete_request($action, $current_user) {
    global $wpdb;
    
    switch ($action) {
        case 'delete_role':
            // Delete role
            if (!user_has_permission($current_user->ID, 'manage_roles') && !current_user_can('manage_options')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                return;
            }
            
            $role_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            $table_roles = $wpdb->prefix . 'jpos_roles';
            
            // Check if system role
            $is_system = $wpdb->get_var($wpdb->prepare(
                "SELECT is_system_role FROM $table_roles WHERE id = %d",
                $role_id
            ));
            
            if ($is_system) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Cannot delete system roles']);
                return;
            }
            
            // Delete role and related data
            $table_role_permissions = $wpdb->prefix . 'jpos_role_permissions';
            $table_user_roles = $wpdb->prefix . 'jpos_user_roles';
            
            $wpdb->delete($table_role_permissions, ['role_id' => $role_id], ['%d']);
            $wpdb->delete($table_user_roles, ['role_id' => $role_id], ['%d']);
            $wpdb->delete($table_roles, ['id' => $role_id], ['%d']);
            
            echo json_encode(['success' => true, 'message' => 'Role deleted successfully']);
            break;
            
        case 'remove_user_role':
            // Remove role from user
            if (!user_has_permission($current_user->ID, 'assign_roles') && !current_user_can('manage_options')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
                return;
            }
            
            $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
            $role_id = isset($_GET['role_id']) ? intval($_GET['role_id']) : 0;
            
            $table_user_roles = $wpdb->prefix . 'jpos_user_roles';
            
            $wpdb->delete(
                $table_user_roles,
                ['user_id' => $user_id, 'role_id' => $role_id],
                ['%d', '%d']
            );
            
            echo json_encode(['success' => true, 'message' => 'Role removed from user']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
}