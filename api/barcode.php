<?php
// Barcode Generation API for WP POS
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../wp-load.php';
    require_once __DIR__ . '/error_handler.php';
    
    // Check authentication
    JPOS_Error_Handler::check_auth();
    
    // Get action from POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        JPOS_Error_Handler::send_error('Invalid JSON data', 400);
    }
    
    // CSRF Protection: Verify nonce
    if (!isset($data['nonce']) || !wp_verify_nonce($data['nonce'], 'jpos_barcode_nonce')) {
        JPOS_Error_Handler::send_error('Invalid security token', 403);
    }
    
    $action = $data['action'] ?? null;
    
    if (!$action) {
        JPOS_Error_Handler::send_error('Action parameter required', 400);
    }
    
    if ($action === 'generate_barcode') {
        // Get and validate product ID
        $product_id = absint($data['product_id'] ?? 0);
        
        if (!$product_id) {
            JPOS_Error_Handler::send_error('Product ID required', 400);
        }
        
        // Check if product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            JPOS_Error_Handler::send_error('Product not found', 404);
        }
        
        // Check if user has permission to edit products
        if (!current_user_can('edit_products')) {
            JPOS_Error_Handler::send_error('Insufficient permissions', 403);
        }
        
        // Generate barcode with retry logic
        $max_retries = 3;
        $barcode = null;
        $attempt = 0;
        
        while ($attempt < $max_retries && !$barcode) {
            $attempt++;
            
            try {
                $generated_barcode = generate_unique_barcode();
                
                // Verify uniqueness
                if (is_barcode_unique($generated_barcode)) {
                    $barcode = $generated_barcode;
                } else {
                    error_log("WP POS Barcode: Duplicate detected on attempt {$attempt}: {$generated_barcode}");
                    
                    if ($attempt >= $max_retries) {
                        JPOS_Error_Handler::send_error('Failed to generate unique barcode after ' . $max_retries . ' attempts', 500);
                    }
                    
                    // Add small delay before retry to reduce collision probability
                    usleep(100000); // 100ms
                }
            } catch (Exception $e) {
                error_log("WP POS Barcode: Generation error on attempt {$attempt}: " . $e->getMessage());
                
                if ($attempt >= $max_retries) {
                    JPOS_Error_Handler::send_error('Barcode generation failed: ' . $e->getMessage(), 500);
                }
            }
        }
        
        // Update product meta with generated barcode
        $updated = update_post_meta($product_id, '_barcode', $barcode);
        
        if ($updated === false) {
            error_log("WP POS Barcode: Failed to update product meta for product {$product_id}");
            JPOS_Error_Handler::send_error('Failed to save barcode to product', 500);
        }
        
        error_log("WP POS Barcode: Successfully generated and saved barcode {$barcode} for product {$product_id}");
        
        echo json_encode([
            'success' => true,
            'barcode' => $barcode,
            'product_id' => $product_id,
            'message' => 'Barcode generated successfully'
        ]);
        exit;
        
    } else {
        JPOS_Error_Handler::send_error('Invalid action', 400);
    }
    
} catch (Exception $e) {
    error_log("WP POS Barcode: Unexpected error - " . $e->getMessage());
    JPOS_Error_Handler::send_error('Server error: ' . $e->getMessage(), 500);
}

/**
 * Generate a unique barcode using the format: YYYYMMDDHHMMSS-RAND
 *
 * @return string The generated barcode
 * @throws Exception If barcode generation fails
 */
function generate_unique_barcode() {
    // Generate timestamp-based barcode (YYYYMMDDHHMMSS)
    $timestamp = date('YmdHis');
    
    // Generate 4-character random alphanumeric string
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random = '';
    for ($i = 0; $i < 4; $i++) {
        $random .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    // Complete barcode without prefix
    $barcode = $timestamp . '-' . $random;
    
    error_log("WP POS Barcode: Generated barcode candidate: {$barcode}");
    
    return $barcode;
}

/**
 * Check if a barcode is unique in the database
 * 
 * @param string $barcode The barcode to check
 * @return bool True if unique, false if duplicate exists
 */
function is_barcode_unique($barcode) {
    global $wpdb;
    
    $query = $wpdb->prepare(
        "SELECT COUNT(*) 
         FROM {$wpdb->postmeta} 
         WHERE meta_key = '_barcode' 
         AND meta_value = %s",
        $barcode
    );
    
    $count = $wpdb->get_var($query);
    
    return ($count == 0);
}

/**
 * Calculate CRC16 checksum for barcode validation
 * Uses CRC-16-CCITT polynomial (0x1021)
 * 
 * @param string $data The data to calculate checksum for
 * @return int The CRC16 checksum value
 */
function calculate_crc16($data) {
    $crc = 0xFFFF; // Initial value
    $polynomial = 0x1021; // CRC-16-CCITT polynomial
    
    $length = strlen($data);
    for ($i = 0; $i < $length; $i++) {
        $byte = ord($data[$i]);
        $crc ^= ($byte << 8);
        
        for ($bit = 0; $bit < 8; $bit++) {
            if ($crc & 0x8000) {
                $crc = (($crc << 1) ^ $polynomial) & 0xFFFF;
            } else {
                $crc = ($crc << 1) & 0xFFFF;
            }
        }
    }
    
    return $crc & 0xFFFF;
}

/**
 * Validate a barcode format
 *
 * @param string $barcode The barcode to validate
 * @return bool True if valid, false otherwise
 */
function validate_barcode($barcode) {
    // Check format: YYYYMMDDHHMMSS-RAND (timestamp-random format)
    if (!preg_match('/^\d{14}-[0-9A-Z]{4}$/', $barcode)) {
        return false;
    }
    
    return true;
}

?>