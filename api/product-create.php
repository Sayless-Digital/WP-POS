<?php
/**
 * Product Creation API for JPOS
 * Dedicated endpoint for creating new products
 * Handles text-based fields only - images must be managed through WooCommerce
 */
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../wp-load.php';
    require_once __DIR__ . '/error_handler.php';
    
    // Check authentication
    JPOS_Error_Handler::check_auth();
    
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        JPOS_Error_Handler::send_error('Only POST requests are allowed', 405);
    }
    
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'error' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Validate required fields
    $validation_errors = [];
    
    if (empty($data['name'])) {
        $validation_errors[] = 'Product name is required';
    }
    
    if (empty($data['regular_price'])) {
        $validation_errors[] = 'Regular price is required';
    } elseif (!is_numeric($data['regular_price']) || floatval($data['regular_price']) < 0) {
        $validation_errors[] = 'Regular price must be a valid positive number';
    }
    
    // Check SKU uniqueness if provided
    if (!empty($data['sku'])) {
        global $wpdb;
        $existing_product = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
            sanitize_text_field($data['sku'])
        ));
        
        if ($existing_product) {
            $validation_errors[] = 'SKU already exists';
        }
    }
    
    // Return validation errors if any
    if (!empty($validation_errors)) {
        echo json_encode([
            'success' => false,
            'error' => implode(', ', $validation_errors),
            'validation_errors' => $validation_errors
        ]);
        exit;
    }
    
    // Create new WooCommerce product
    $product = new WC_Product_Simple();
    
    // Set basic product data
    $product->set_name(sanitize_text_field($data['name']));
    
    // SKU
    if (!empty($data['sku'])) {
        $product->set_sku(sanitize_text_field($data['sku']));
    }
    
    // Barcode (custom meta)
    if (!empty($data['barcode'])) {
        $product->update_meta_data('_barcode', sanitize_text_field($data['barcode']));
    }
    
    // Pricing
    $product->set_regular_price(sanitize_text_field($data['regular_price']));
    
    if (!empty($data['sale_price']) && is_numeric($data['sale_price'])) {
        $product->set_sale_price(sanitize_text_field($data['sale_price']));
    }
    
    // Status - default to draft for new products
    if (!empty($data['status']) && in_array($data['status'], ['publish', 'draft', 'private'])) {
        $product->set_status(sanitize_text_field($data['status']));
    } else {
        $product->set_status('draft'); // Safe default
    }
    
    // Featured
    if (isset($data['featured'])) {
        $product->set_featured($data['featured'] === true || $data['featured'] === 'yes');
    }
    
    // Tax settings
    if (!empty($data['tax_class'])) {
        $product->set_tax_class(sanitize_text_field($data['tax_class']));
    }
    
    if (!empty($data['tax_status']) && in_array($data['tax_status'], ['taxable', 'shipping', 'none'])) {
        $product->set_tax_status(sanitize_text_field($data['tax_status']));
    } else {
        $product->set_tax_status('taxable'); // Default
    }
    
    // Stock management
    if (isset($data['manage_stock'])) {
        $manage_stock = $data['manage_stock'] === true || $data['manage_stock'] === 'yes';
        $product->set_manage_stock($manage_stock);
        
        if ($manage_stock && !empty($data['stock_quantity'])) {
            $product->set_stock_quantity(absint($data['stock_quantity']));
        }
    }
    
    // Description
    if (!empty($data['description'])) {
        $product->set_description(wp_kses_post($data['description']));
    }
    
    // Short description
    if (!empty($data['short_description'])) {
        $product->set_short_description(wp_kses_post($data['short_description']));
    }
    
    // Handle new custom attributes
    if (!empty($data['new_attributes']) && is_array($data['new_attributes'])) {
        $attributes = [];
        
        foreach ($data['new_attributes'] as $new_attr) {
            if (empty($new_attr['name']) || empty($new_attr['options'])) {
                continue;
            }
            
            // Convert name to lowercase with underscores
            $attr_name = strtolower(trim($new_attr['name']));
            $attr_name = preg_replace('/[^a-z0-9_]/', '_', $attr_name);
            $attr_name = preg_replace('/_+/', '_', $attr_name);
            
            // Create new custom attribute
            $attribute = new WC_Product_Attribute();
            $attribute->set_name($attr_name);
            $attribute->set_options($new_attr['options']);
            $attribute->set_visible($new_attr['visible'] ?? true);
            $attribute->set_variation($new_attr['variation'] ?? false);
            
            $attributes[$attr_name] = $attribute;
        }
        
        if (!empty($attributes)) {
            $product->set_attributes($attributes);
        }
    }
    
    // Save the product
    $product_id = $product->save();
    
    if (!$product_id) {
        throw new Exception('Failed to create product - save operation returned no ID');
    }
    
    // Add custom meta data if provided
    if (!empty($data['meta_data']) && is_array($data['meta_data'])) {
        foreach ($data['meta_data'] as $meta) {
            if (!empty($meta['key']) && isset($meta['value'])) {
                update_post_meta(
                    $product_id,
                    sanitize_text_field($meta['key']),
                    sanitize_text_field($meta['value'])
                );
            }
        }
    }
    
    // Get the created product data to return
    $created_product = wc_get_product($product_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Product created successfully',
        'product_id' => $product_id,
        'data' => [
            'id' => $product_id,
            'name' => $created_product->get_name(),
            'sku' => $created_product->get_sku(),
            'status' => $created_product->get_status(),
            'price' => $created_product->get_price(),
            'regular_price' => $created_product->get_regular_price(),
            'sale_price' => $created_product->get_sale_price()
        ]
    ]);
    exit;
    
} catch (Exception $e) {
    // Log error for debugging
    error_log('Product creation error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while creating the product: ' . $e->getMessage(),
        'error_details' => [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    exit;
}
?>