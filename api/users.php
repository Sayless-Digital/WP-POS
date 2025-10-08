<?php
/**
 * WP POS User Management API
 * Complete CRUD operations for WordPress users with role assignment
 */

require_once(dirname(__FILE__) . '/../../wp-load.php');

header('Content-Type: application/json');

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    http_response_code(403);
    wp_send_json_error(['message' => 'Unauthorized access']);
    exit;
}

// Get action from GET, POST, or JSON body
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';

if (empty($action) && isset($_POST['action'])) {
    $action = sanitize_text_field($_POST['action']);
}

if (empty($action)) {
    $json_data = json_decode(file_get_contents('php://input'), true);
    if (isset($json_data['action'])) {
        $action = sanitize_text_field($json_data['action']);
    }
}

if (empty($action)) {
    $action = 'list';
}

/**
 * LIST - Get all WordPress users with their roles
 */
if ($action === 'list') {
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    
    $args = [
        'orderby' => 'display_name',
        'order' => 'ASC',
        'number' => $limit,
        'offset' => $offset
    ];
    
    if (!empty($search)) {
        $args['search'] = '*' . $search . '*';
        $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
    }
    
    if (!empty($role) && $role !== 'all') {
        $args['role'] = $role;
    }
    
    $users = get_users($args);
    $user_data = [];
    
    // Pre-load all WordPress roles once (performance optimization)
    $wp_roles = wp_roles();
    $role_names_map = [];
    foreach ($wp_roles->roles as $role_slug => $role_info) {
        $role_names_map[$role_slug] = translate_user_role($role_info['name']);
    }
    
    foreach ($users as $user) {
        $user_roles = $user->roles;
        
        // Map role slugs to display names using pre-loaded data
        $display_role_names = array_map(function($role) use ($role_names_map) {
            return isset($role_names_map[$role]) ? $role_names_map[$role] : ucwords(str_replace('_', ' ', $role));
        }, $user_roles);
        
        $user_data[] = [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'roles' => $user_roles,
            'role_names' => $display_role_names,
            'registered' => $user->user_registered
        ];
    }
    
    // Get total count for pagination
    $count_args = $args;
    unset($count_args['number']);
    unset($count_args['offset']);
    $total_users = count(get_users($count_args));
    
    wp_send_json_success([
        'users' => $user_data,
        'total' => count($user_data),
        'has_more' => ($offset + $limit) < $total_users,
        'offset' => $offset,
        'limit' => $limit
    ]);
    exit;
}

/**
 * GET - Get single user details
 */
if ($action === 'get') {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if (!$user_id) {
        wp_send_json_error(['message' => 'User ID is required']);
        exit;
    }
    
    $user = get_userdata($user_id);
    
    if (!$user) {
        wp_send_json_error(['message' => 'User not found']);
        exit;
    }
    
    $user_data = [
        'id' => $user->ID,
        'username' => $user->user_login,
        'email' => $user->user_email,
        'display_name' => $user->display_name,
        'first_name' => get_user_meta($user->ID, 'first_name', true),
        'last_name' => get_user_meta($user->ID, 'last_name', true),
        'roles' => $user->roles,
        'registered' => $user->user_registered
    ];
    
    wp_send_json_success(['user' => $user_data]);
    exit;
}

/**
 * CREATE - Create a new user
 */
if ($action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $username = sanitize_user($data['username'] ?? '');
    $email = sanitize_email($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $first_name = sanitize_text_field($data['first_name'] ?? '');
    $last_name = sanitize_text_field($data['last_name'] ?? '');
    $roles = isset($data['roles']) ? array_map('sanitize_key', $data['roles']) : ['customer'];
    
    // Validate required fields
    if (empty($username)) {
        wp_send_json_error(['message' => 'Username is required']);
        exit;
    }
    
    if (empty($email)) {
        wp_send_json_error(['message' => 'Email is required']);
        exit;
    }
    
    if (empty($password)) {
        wp_send_json_error(['message' => 'Password is required']);
        exit;
    }
    
    // Check if username already exists
    if (username_exists($username)) {
        wp_send_json_error(['message' => 'Username already exists']);
        exit;
    }
    
    // Check if email already exists
    if (email_exists($email)) {
        wp_send_json_error(['message' => 'Email already exists']);
        exit;
    }
    
    // Create the user
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
        exit;
    }
    
    // Update user meta
    if ($first_name) {
        update_user_meta($user_id, 'first_name', $first_name);
    }
    if ($last_name) {
        update_user_meta($user_id, 'last_name', $last_name);
    }
    
    // Set display name
    $display_name = trim("$first_name $last_name");
    if (empty($display_name)) {
        $display_name = $username;
    }
    wp_update_user([
        'ID' => $user_id,
        'display_name' => $display_name
    ]);
    
    // Assign roles
    $user = new WP_User($user_id);
    // Remove default role
    $user->set_role('');
    // Add selected roles
    foreach ($roles as $role) {
        $user->add_role($role);
    }
    
    wp_send_json_success([
        'message' => 'User created successfully',
        'user_id' => $user_id,
        'username' => $username
    ]);
    exit;
}

/**
 * UPDATE - Update existing user
 */
if ($action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    
    if (!$user_id) {
        wp_send_json_error(['message' => 'User ID is required']);
        exit;
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        wp_send_json_error(['message' => 'User not found']);
        exit;
    }
    
    // Prevent editing administrator if current user is not administrator
    if (in_array('administrator', $user->roles) && !current_user_can('administrator')) {
        wp_send_json_error(['message' => 'Cannot edit administrator users']);
        exit;
    }
    
    // Update user data
    $user_data = ['ID' => $user_id];
    
    if (isset($data['email'])) {
        $email = sanitize_email($data['email']);
        if (email_exists($email) && $email !== $user->user_email) {
            wp_send_json_error(['message' => 'Email already exists']);
            exit;
        }
        $user_data['user_email'] = $email;
    }
    
    if (isset($data['first_name'])) {
        update_user_meta($user_id, 'first_name', sanitize_text_field($data['first_name']));
    }
    
    if (isset($data['last_name'])) {
        update_user_meta($user_id, 'last_name', sanitize_text_field($data['last_name']));
    }
    
    // Update display name if first/last name changed
    if (isset($data['first_name']) || isset($data['last_name'])) {
        $first_name = isset($data['first_name']) ? sanitize_text_field($data['first_name']) : get_user_meta($user_id, 'first_name', true);
        $last_name = isset($data['last_name']) ? sanitize_text_field($data['last_name']) : get_user_meta($user_id, 'last_name', true);
        $display_name = trim("$first_name $last_name");
        if (!empty($display_name)) {
            $user_data['display_name'] = $display_name;
        }
    }
    
    // Update password if provided
    if (isset($data['password']) && !empty($data['password'])) {
        $user_data['user_pass'] = $data['password'];
    }
    
    // Update user
    if (count($user_data) > 1) { // More than just ID
        $result = wp_update_user($user_data);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
            exit;
        }
    }
    
    // Update roles if provided
    if (isset($data['roles'])) {
        $roles = array_map('sanitize_key', $data['roles']);
        
        // Don't allow removing administrator role from administrators
        if (in_array('administrator', $user->roles) && !in_array('administrator', $roles)) {
            wp_send_json_error(['message' => 'Cannot remove administrator role']);
            exit;
        }
        
        $user_obj = new WP_User($user_id);
        // Remove all current roles
        foreach ($user->roles as $role) {
            $user_obj->remove_role($role);
        }
        // Add new roles
        foreach ($roles as $role) {
            $user_obj->add_role($role);
        }
    }
    
    wp_send_json_success([
        'message' => 'User updated successfully',
        'user_id' => $user_id
    ]);
    exit;
}

/**
 * DELETE - Delete a user
 */
if ($action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    
    if (!$user_id) {
        wp_send_json_error(['message' => 'User ID is required']);
        exit;
    }
    
    // Prevent deleting current user
    if ($user_id === get_current_user_id()) {
        wp_send_json_error(['message' => 'Cannot delete your own account']);
        exit;
    }
    
    $user = get_userdata($user_id);
    if (!$user) {
        wp_send_json_error(['message' => 'User not found']);
        exit;
    }
    
    // Prevent deleting administrators
    if (in_array('administrator', $user->roles)) {
        wp_send_json_error(['message' => 'Cannot delete administrator users']);
        exit;
    }
    
    // Delete the user and reassign their content to current user
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    $result = wp_delete_user($user_id, get_current_user_id());
    
    if (!$result) {
        wp_send_json_error(['message' => 'Failed to delete user']);
        exit;
    }
    
    wp_send_json_success(['message' => 'User deleted successfully']);
    exit;
}

// Invalid action
wp_send_json_error(['message' => 'Invalid action']);