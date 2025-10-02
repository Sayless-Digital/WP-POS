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
                    $product_data['variations'][] = [
                        'id' => $variation->get_id(),
                        'sku' => $variation->get_sku(),
                        'price' => $variation->get_price(),
                        'regular_price' => $variation->get_regular_price(),
                        'sale_price' => $variation->get_sale_price(),
                        'stock_status' => $variation->get_stock_status(),
                        'stock_quantity' => $variation->get_stock_quantity(),
                        'manage_stock' => $variation->get_manage_stock(),
                        'attributes' => $variation->get_attributes(),
                        'status' => $variation->get_status()
                    ];
                }
            }
        }
        
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
        
    } else {
        JPOS_Error_Handler::send_error('Invalid action', 400);
    }
    
} catch (Exception $e) {
    JPOS_Error_Handler::send_error('Server error: ' . $e->getMessage(), 500);
}
?>
