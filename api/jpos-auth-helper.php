<?php
/**
 * JPOS Authentication Helper
 * Ultra-simplified - just check if logged in
 */

/**
 * Require authentication - exits with 403 if not logged in
 * TEMPORARY: Allow ANY logged-in user for debugging
 */
function jpos_require_auth() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 403);
        exit;
    }
    // If we get here, user is logged in - allow access
}