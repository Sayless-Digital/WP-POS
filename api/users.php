
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

// Get action from GET or POST
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) :
          (isset($_POST['action']) ? sanitize_text_field($_POST['action']) : 'list');

/**
 * LIST - Get all WordPress users with their roles
 */
if ($action === 'list') {
    $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    $role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
    
    $args = [
        'orderby' => 'display_name',
        'order' => 'ASC'
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
    
    foreach ($users as $user) {
        $user_roles = $user->roles;
        $user_data[] = [
            'id' => $user->ID,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'display_name' => $user->display_name,
            'first_name' => get_user_meta($user->ID, 'first_name', true),
            'last_name' => get_user_meta($user->ID, 'last_name', true),
            'roles' => $user_roles,
            'role_names' => array_map(function($role) {
                $role_obj = get_role($role);
                return $role ? translate_user_role(ucwords(str_replace('_', ' ', $role))) : $role;
            }, $user_roles),
            'registered' => $user->user_registered
        ];
    }
    
    wp_send_json_success([
        'users' => $user_data,
        'total' => count($user_data)
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
