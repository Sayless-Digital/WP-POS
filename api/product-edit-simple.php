<?php
// Simplified Product Editor API for JPOS
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../wp-load.php';
    require_once __DIR__ . '/error_handler.php';
    
    // Check authentication
    JPOS_Error_Handler::check_auth();
    
    // Get action and product ID
    $action = $_GET['action'] ?? null;
    $product_id = absint($_GET['id'] ?? 0);
    
    // For POST requests, also check JSON body for action
    if (!$action && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if ($data && isset($data['action'])) {
            $action = $data['action'];
        }
    }
    
    if (!$action) {
        JPOS_Error_Handler::send_error('Action parameter required', 400);
    }
    
    if ($action === 'get_product_details') {
        if (!$product_id) {
            JPOS_Error_Handler::send_error('Product ID required', 400);
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            JPOS_Error_Handler::send_error('Product not found', 404);
        }
        
        // Simple product data
        $product_data = [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'status' => $product->get_status(),
            'featured' => $product->get_featured(),
            'stock_quantity' => $product->get_stock_quantity(),
            'manage_stock' => $product->get_manage_stock(),
            'stock_status' => $product->get_stock_status(),
            'type' => $product->get_type(),
            'meta_data' => [],
            'attributes' => [],
            'variations' => []
        ];
        
        // Get custom meta data (excluding WooCommerce core fields)
        $meta_data = get_post_meta($product_id);
        foreach ($meta_data as $key => $value) {
            // Skip WooCommerce core fields and empty values
            if (strpos($key, '_') !== 0 || in_array($key, ['_sku', '_price', '_regular_price', '_sale_price', '_stock_status', '_stock', '_manage_stock', '_backorders', '_weight', '_length', '_width', '_height', '_tax_status', '_tax_class', '_featured', '_visibility', '_barcode', '_product_attributes', '_default_attributes', '_product_image_gallery', '_thumbnail_id'])) {
                continue;
            }
            
            $meta_value = is_array($value) ? $value[0] : $value;
            if (!empty($meta_value)) {
                $product_data['meta_data'][] = [
                    'key' => $key,
                    'value' => $meta_value
                ];
            }
        }
        
        // Get product attributes
        foreach ($product->get_attributes() as $attribute_name => $attribute) {
            // Convert technical attribute names to friendly names
            $friendly_name = $attribute_name;
            if (strpos($attribute_name, 'pa_') === 0) {
                // Convert pa_color to Color
                $friendly_name = str_replace('pa_', '', $attribute_name);
                $friendly_name = str_replace('_', ' ', $friendly_name);
                $friendly_name = ucwords($friendly_name);
            } elseif (strpos($attribute_name, '_') === 0) {
                // Convert _custom_attribute to Custom Attribute
                $friendly_name = str_replace('_', '', $attribute_name);
                $friendly_name = str_replace('_', ' ', $friendly_name);
                $friendly_name = ucwords($friendly_name);
            }
            
            // Get friendly option names
            $friendly_options = [];
            if ($attribute->is_taxonomy()) {
                // For taxonomy attributes, get term names instead of IDs
                $terms = get_terms([
                    'taxonomy' => $attribute_name,
                    'hide_empty' => false,
                ]);
                if (!is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $friendly_options[] = $term->name;
                    }
                }
            } else {
                // For custom attributes, use the options as-is
                $friendly_options = $attribute->get_options();
            }
            
            $product_data['attributes'][] = [
                'name' => $attribute_name, // Keep original for technical purposes
                'friendly_name' => $friendly_name, // Add friendly name for display
                'type' => $attribute->is_taxonomy() ? 'taxonomy' : 'custom',
                'options' => $attribute->get_options(), // Keep original options (IDs for taxonomy)
                'friendly_options' => $friendly_options, // Add friendly option names
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation(),
                'position' => $attribute->get_position()
            ];
        }
        
        // Get variations for variable products
        if ($product->get_type() === 'variable') {
            $variations = $product->get_children();
            foreach ($variations as $variation_id) {
                $variation = wc_get_product($variation_id);
                if ($variation) {
                    // Get variation attributes in readable format
                    $variation_attributes = [];
                    $variation_attrs = $variation->get_attributes();
                    foreach ($variation_attrs as $attr_name => $attr_value) {
                        $variation_attributes[$attr_name] = $attr_value;
                    }
                    
                    $product_data['variations'][] = [
                        'id' => $variation->get_id(),
                        'sku' => $variation->get_sku(),
                        'price' => $variation->get_price(),
                        'regular_price' => $variation->get_regular_price(),
                        'sale_price' => $variation->get_sale_price(),
                        'stock_status' => $variation->get_stock_status(),
                        'stock_quantity' => $variation->get_stock_quantity(),
                        'manage_stock' => $variation->get_manage_stock(),
                        'attributes' => $variation_attributes,
                        'status' => $variation->get_status(),
                        'parent_name' => $product->get_name() // Add parent product name
                    ];
                }
            }
        }
        
        // Featured Image
        $product_data['featured_image'] = [
            'id' => $product->get_image_id(),
            'url' => $product->get_image_id() ? wp_get_attachment_url($product->get_image_id()) : '',
            'thumbnail_url' => $product->get_image_id() ? wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') : ''
        ];
        
        // Gallery Images
        $product_data['gallery_images'] = array_map(function($id) {
            return [
                'id' => $id,
                'url' => wp_get_attachment_url($id),
                'thumbnail_url' => wp_get_attachment_image_url($id, 'thumbnail')
            ];
        }, $product->get_gallery_image_ids());
        
        // Get tax classes
        $tax_classes = [];
        if (function_exists('WC_Tax')) {
            $tax_classes_raw = WC_Tax::get_tax_classes();
            foreach ($tax_classes_raw as $tax_class) {
                $tax_classes[] = [
                    'slug' => sanitize_title($tax_class),
                    'name' => $tax_class
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $product_data,
            'tax_classes' => $tax_classes
        ]);
        exit;
        
    } elseif ($action === 'get_tax_classes') {
        // Get tax classes only
        $tax_classes = [];
        if (function_exists('WC_Tax')) {
            $tax_classes_raw = WC_Tax::get_tax_classes();
            foreach ($tax_classes_raw as $tax_class) {
                $tax_classes[] = [
                    'slug' => sanitize_title($tax_class),
                    'name' => $tax_class
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'tax_classes' => $tax_classes
        ]);
        exit;
        
    } elseif ($action === 'get_available_attributes') {
        // Get all available product attributes from the system
        $available_attributes = [];
        
        // Get global attributes (taxonomies)
        $global_attributes = wc_get_attribute_taxonomies();
        foreach ($global_attributes as $attribute) {
            $available_attributes[] = [
                'name' => $attribute->attribute_name,
                'label' => $attribute->attribute_label,
                'type' => 'taxonomy',
                'slug' => 'pa_' . $attribute->attribute_name
            ];
        }
        
        // Get common custom attribute names from existing products
        global $wpdb;
        $custom_attrs = $wpdb->get_results("
            SELECT DISTINCT meta_key 
            FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '_product_attributes%' 
            AND meta_key NOT LIKE '%pa_%'
            LIMIT 50
        ");
        
        foreach ($custom_attrs as $attr) {
            $attr_name = str_replace('_product_attributes_', '', $attr->meta_key);
            if (!empty($attr_name)) {
                $available_attributes[] = [
                    'name' => $attr_name,
                    'label' => ucwords(str_replace('_', ' ', $attr_name)),
                    'type' => 'custom',
                    'slug' => $attr_name
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'attributes' => $available_attributes
        ]);
        exit;
        
    } elseif ($action === 'update_product') {
        try {
            // Handle POST requests for updating products
            $input = file_get_contents('php://input');
            
            // Return detailed debug info in response
            $debug = [];
            $debug['step'] = 'Reading input';
            $debug['input_length'] = strlen($input);
            $debug['input_sample'] = substr($input, 0, 200);
            
            $data = json_decode($input, true);
            
            if (!$data) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid JSON data',
                    'debug' => array_merge($debug, [
                        'json_error' => json_last_error_msg(),
                        'input_full' => $input
                    ])
                ]);
                exit;
            }
            
            $debug['step'] = 'JSON decoded';
            $debug['data_keys'] = array_keys($data);
            
            // Accept both 'id' and 'product_id' for compatibility
            $product_id = absint($data['id'] ?? $data['product_id'] ?? 0);
            if (!$product_id) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Product ID required',
                    'debug' => array_merge($debug, [
                        'received_data' => $data
                    ])
                ]);
                exit;
            }
            
            $debug['step'] = 'Loading product';
            $debug['product_id'] = $product_id;
            
            $product = wc_get_product($product_id);
            if (!$product) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Product not found',
                    'debug' => $debug
                ]);
                exit;
            }
            
            $debug['step'] = 'Product loaded, updating fields';
            $debug['product_type'] = $product->get_type();
            
            // Update basic product data
            if (isset($data['name'])) {
                $debug['updating'][] = 'name';
                $product->set_name(sanitize_text_field($data['name']));
            }
            if (isset($data['sku'])) {
                $new_sku = sanitize_text_field($data['sku']);
                // Only update SKU if it has changed (avoid duplicate SKU error)
                if ($new_sku !== $product->get_sku()) {
                    $debug['updating'][] = 'sku (changed)';
                    $product->set_sku($new_sku);
                } else {
                    $debug['skipped'][] = 'sku (unchanged)';
                }
            }
            if (isset($data['price'])) {
                $debug['updating'][] = 'price';
                $product->set_price(sanitize_text_field($data['price']));
            }
            if (isset($data['regular_price'])) {
                $debug['updating'][] = 'regular_price';
                $product->set_regular_price(sanitize_text_field($data['regular_price']));
            }
            if (isset($data['sale_price'])) {
                $debug['updating'][] = 'sale_price';
                $product->set_sale_price(sanitize_text_field($data['sale_price']));
            }
            if (isset($data['status'])) {
                $debug['updating'][] = 'status';
                $product->set_status(sanitize_text_field($data['status']));
            }
            // Handle stock management - order matters!
            // 1. Set manage_stock first
            if (isset($data['manage_stock'])) {
                $debug['updating'][] = 'manage_stock';
                $product->set_manage_stock($data['manage_stock'] === true || $data['manage_stock'] === 'yes');
            }
            
            // 2. Set stock quantity (this auto-calculates stock_status if manage_stock is true)
            if (isset($data['stock_quantity'])) {
                $debug['updating'][] = 'stock_quantity';
                $product->set_stock_quantity(absint($data['stock_quantity']));
            }
            
            // 3. Only set stock_status manually if NOT managing stock
            // (WooCommerce auto-calculates it when managing stock)
            if (isset($data['stock_status']) && !$product->get_manage_stock()) {
                $debug['updating'][] = 'stock_status (manual)';
                $product->set_stock_status(sanitize_text_field($data['stock_status']));
            } else if ($product->get_manage_stock()) {
                $debug['skipped'][] = 'stock_status (auto-calculated by WooCommerce)';
            }
            
            // Handle new custom attributes
            if (isset($data['new_attributes']) && is_array($data['new_attributes'])) {
                $debug['updating'][] = 'new_attributes';
                $debug['new_attributes_count'] = count($data['new_attributes']);
                
                $existing_attributes = $product->get_attributes();
                
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
                    
                    $existing_attributes[$attr_name] = $attribute;
                    $debug['added_attribute'][] = $attr_name;
                }
                
                $product->set_attributes($existing_attributes);
            }
            
            // Handle new variations for variable products
            if (isset($data['new_variations']) && is_array($data['new_variations']) && $product->get_type() === 'variable') {
                $debug['updating'][] = 'new_variations';
                $debug['new_variations_count'] = count($data['new_variations']);
                
                foreach ($data['new_variations'] as $new_var) {
                    if (empty($new_var['attributes']) || empty($new_var['regular_price'])) {
                        $debug['skipped_variation'][] = 'Missing required fields';
                        continue;
                    }
                    
                    // Create new variation
                    $variation = new WC_Product_Variation();
                    $variation->set_parent_id($product_id);
                    
                    // Set attributes
                    $variation->set_attributes($new_var['attributes']);
                    
                    // Set pricing
                    $variation->set_regular_price(sanitize_text_field($new_var['regular_price']));
                    if (!empty($new_var['sale_price'])) {
                        $variation->set_sale_price(sanitize_text_field($new_var['sale_price']));
                    }
                    
                    // Set SKU if provided
                    if (!empty($new_var['sku'])) {
                        $variation->set_sku(sanitize_text_field($new_var['sku']));
                    }
                    
                    // Set stock
                    if (isset($new_var['stock_quantity']) && $new_var['stock_quantity'] !== '') {
                        $variation->set_manage_stock(true);
                        $variation->set_stock_quantity(absint($new_var['stock_quantity']));
                    }
                    
                    // Set status (enabled/disabled)
                    $variation->set_status(isset($new_var['enabled']) && $new_var['enabled'] ? 'publish' : 'private');
                    
                    // Save variation
                    $variation_id = $variation->save();
                    $debug['created_variation'][] = $variation_id;
                }
            }
            
            $debug['step'] = 'Saving product';
            
            // Save the product
            $product->save();
            
            $debug['step'] = 'Product saved successfully';
            
            echo json_encode([
                'success' => true,
                'message' => 'Product updated successfully',
                'debug' => $debug
            ]);
            exit;
            
        } catch (Exception $e) {
            // Return detailed error in JSON response
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => array_merge($debug ?? [], [
                    'exception_class' => get_class($e),
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine(),
                    'stack_trace' => $e->getTraceAsString()
                ])
            ]);
            exit;
        }
        
    } elseif ($action === 'check_sku') {
        // Check if SKU exists
        $sku = sanitize_text_field($_GET['sku'] ?? '');
        
        if (empty($sku)) {
            JPOS_Error_Handler::send_error('SKU parameter required', 400);
        }
        
        // Check if SKU exists in the database
        global $wpdb;
        $product_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s LIMIT 1",
            $sku
        ));
        
        echo json_encode([
            'success' => true,
            'exists' => !empty($product_id)
        ]);
        exit;
        
    } else {
        JPOS_Error_Handler::send_error('Invalid action', 400);
    }
    
} catch (Exception $e) {
    JPOS_Error_Handler::send_error('Server error: ' . $e->getMessage(), 500);
}
?>
