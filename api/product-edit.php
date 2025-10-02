<?php
// FILE: /jpos/api/product-edit.php
// Comprehensive Product Editor API for JPOS

require_once __DIR__ . '/../../wp-load.php';
require_once __DIR__ . '/error_handler.php';

header('Content-Type: application/json');
global $wpdb;

JPOS_Error_Handler::check_auth();

$data = JPOS_Error_Handler::safe_json_decode(file_get_contents('php://input'));
$action = $data['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    JPOS_Error_Handler::send_error('Invalid request. Action parameter required.', 400);
}

// CSRF Protection: Verify nonce for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nonce = $data['nonce'] ?? '';
    JPOS_Error_Handler::check_nonce($nonce, 'jpos_product_edit_nonce');
}

if ($action === 'get_product_details') {
    $product_id = absint($_GET['id']);
    if (!$product_id) {
        JPOS_Error_Handler::send_error('Product ID is required.', 400);
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        JPOS_Error_Handler::handle_not_found_error('Product');
    }

    // Get comprehensive product data
    $product_data = [
        'id' => $product->get_id(),
        'name' => $product->get_name(),
        'slug' => $product->get_slug(),
        'description' => $product->get_description(),
        'short_description' => $product->get_short_description(),
        'sku' => $product->get_sku(),
        'barcode' => $product->get_meta('_barcode'),
        'regular_price' => $product->get_regular_price(),
        'sale_price' => $product->get_sale_price(),
        'status' => $product->get_status(),
        'featured' => $product->get_featured(),
        'catalog_visibility' => $product->get_catalog_visibility(),
        'tax_status' => $product->get_tax_status(),
        'tax_class' => $product->get_tax_class(),
        'manage_stock' => $product->get_manage_stock(),
        'stock_status' => $product->get_stock_status(),
        'stock_quantity' => $product->get_stock_quantity(),
        'backorders' => $product->get_backorders(),
        'weight' => $product->get_weight(),
        'dimensions' => [
            'length' => $product->get_length(),
            'width' => $product->get_width(),
            'height' => $product->get_height()
        ],
        'type' => $product->get_type(),
        'categories' => wp_get_post_terms($product_id, 'product_cat', ['fields' => 'all']),
        'tags' => wp_get_post_terms($product_id, 'product_tag', ['fields' => 'all']),
        'attributes' => [],
        'variations' => [],
        'meta_data' => []
    ];

    // Get product attributes
    if ($product->is_type('variable')) {
        $attributes = $product->get_attributes();
        foreach ($attributes as $attribute) {
            $product_data['attributes'][] = [
                'id' => $attribute->get_id(),
                'name' => $attribute->get_name(),
                'slug' => $attribute->get_name(),
                'type' => $attribute->is_taxonomy() ? 'taxonomy' : 'custom',
                'options' => $attribute->get_options(),
                'visible' => $attribute->get_visible(),
                'variation' => $attribute->get_variation(),
                'position' => $attribute->get_position()
            ];
        }

        // Get variations
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

    // Get custom meta data
    $meta_data = get_post_meta($product_id);
    foreach ($meta_data as $key => $value) {
        if (strpos($key, '_') !== 0 || in_array($key, ['_sku', '_price', '_regular_price', '_sale_price', '_stock_status', '_stock', '_manage_stock', '_backorders', '_weight', '_length', '_width', '_height', '_tax_status', '_tax_class', '_featured', '_visibility', '_barcode'])) {
            continue;
        }
        $product_data['meta_data'][] = [
            'key' => $key,
            'value' => is_array($value) ? $value[0] : $value
        ];
    }

    echo json_encode(['success' => true, 'data' => $product_data]);
    exit;

} elseif ($action === 'update_product') {
    $product_id = absint($data['product_id']);
    if (!$product_id) {
        JPOS_Error_Handler::send_error('Product ID is required.', 400);
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        JPOS_Error_Handler::handle_not_found_error('Product');
    }

    $updated_fields = [];
    $errors = [];

    try {
        // Update basic product fields
        if (isset($data['name']) && $data['name'] !== $product->get_name()) {
            $product->set_name(sanitize_text_field($data['name']));
            $updated_fields[] = 'name';
        }

        if (isset($data['description'])) {
            $product->set_description(wp_kses_post($data['description']));
            $updated_fields[] = 'description';
        }

        if (isset($data['short_description'])) {
            $product->set_short_description(wp_kses_post($data['short_description']));
            $updated_fields[] = 'short_description';
        }

        if (isset($data['sku']) && $data['sku'] !== $product->get_sku()) {
            $product->set_sku(sanitize_text_field($data['sku']));
            $updated_fields[] = 'sku';
        }

        if (isset($data['barcode'])) {
            $product->update_meta_data('_barcode', sanitize_text_field($data['barcode']));
            $updated_fields[] = 'barcode';
        }

        if (isset($data['regular_price'])) {
            $product->set_regular_price(wc_format_decimal($data['regular_price'], 2));
            $updated_fields[] = 'regular_price';
        }

        if (isset($data['sale_price'])) {
            $product->set_sale_price(wc_format_decimal($data['sale_price'], 2));
            $updated_fields[] = 'sale_price';
        }

        if (isset($data['status']) && $data['status'] !== $product->get_status()) {
            $product->set_status(sanitize_text_field($data['status']));
            $updated_fields[] = 'status';
        }

        if (isset($data['featured'])) {
            $product->set_featured($data['featured'] ? true : false);
            $updated_fields[] = 'featured';
        }

        if (isset($data['catalog_visibility'])) {
            $product->set_catalog_visibility(sanitize_text_field($data['catalog_visibility']));
            $updated_fields[] = 'catalog_visibility';
        }

        if (isset($data['tax_status'])) {
            $product->set_tax_status(sanitize_text_field($data['tax_status']));
            $updated_fields[] = 'tax_status';
        }

        if (isset($data['tax_class'])) {
            $product->set_tax_class(sanitize_text_field($data['tax_class']));
            $updated_fields[] = 'tax_class';
        }

        if (isset($data['manage_stock'])) {
            $product->set_manage_stock($data['manage_stock'] ? true : false);
            $updated_fields[] = 'manage_stock';
        }

        if (isset($data['stock_status'])) {
            $product->set_stock_status(sanitize_text_field($data['stock_status']));
            $updated_fields[] = 'stock_status';
        }

        if (isset($data['stock_quantity']) && $product->get_manage_stock()) {
            $product->set_stock_quantity(wc_stock_amount($data['stock_quantity']));
            $updated_fields[] = 'stock_quantity';
        }

        if (isset($data['backorders'])) {
            $product->set_backorders(sanitize_text_field($data['backorders']));
            $updated_fields[] = 'backorders';
        }

        if (isset($data['weight'])) {
            $product->set_weight(wc_format_decimal($data['weight'], 2));
            $updated_fields[] = 'weight';
        }

        if (isset($data['dimensions'])) {
            if (isset($data['dimensions']['length'])) {
                $product->set_length(wc_format_decimal($data['dimensions']['length'], 2));
            }
            if (isset($data['dimensions']['width'])) {
                $product->set_width(wc_format_decimal($data['dimensions']['width'], 2));
            }
            if (isset($data['dimensions']['height'])) {
                $product->set_height(wc_format_decimal($data['dimensions']['height'], 2));
            }
            $updated_fields[] = 'dimensions';
        }

        // Update categories
        if (isset($data['categories'])) {
            $category_ids = array_map('absint', $data['categories']);
            wp_set_post_terms($product_id, $category_ids, 'product_cat');
            $updated_fields[] = 'categories';
        }

        // Update tags
        if (isset($data['tags'])) {
            $tag_ids = array_map('absint', $data['tags']);
            wp_set_post_terms($product_id, $tag_ids, 'product_tag');
            $updated_fields[] = 'tags';
        }

        // Update custom meta data
        if (isset($data['meta_data']) && is_array($data['meta_data'])) {
            foreach ($data['meta_data'] as $meta) {
                if (isset($meta['key']) && isset($meta['value'])) {
                    $product->update_meta_data($meta['key'], $meta['value']);
                }
            }
            $updated_fields[] = 'meta_data';
        }

        // Save the product
        $product->save();

        // Update variations if provided
        if ($product->is_type('variable') && isset($data['variations']) && is_array($data['variations'])) {
            foreach ($data['variations'] as $variation_data) {
                if (!isset($variation_data['id'])) continue;
                
                $variation = wc_get_product($variation_data['id']);
                if (!$variation || $variation->get_parent_id() !== $product_id) continue;

                $variation_updated = false;

                if (isset($variation_data['sku'])) {
                    $variation->set_sku(sanitize_text_field($variation_data['sku']));
                    $variation_updated = true;
                }

                if (isset($variation_data['price'])) {
                    $variation->set_price(wc_format_decimal($variation_data['price'], 2));
                    $variation->set_regular_price(wc_format_decimal($variation_data['price'], 2));
                    $variation_updated = true;
                }

                if (isset($variation_data['sale_price'])) {
                    $variation->set_sale_price(wc_format_decimal($variation_data['sale_price'], 2));
                    $variation_updated = true;
                }

                if (isset($variation_data['stock_status'])) {
                    $variation->set_stock_status(sanitize_text_field($variation_data['stock_status']));
                    $variation_updated = true;
                }

                if (isset($variation_data['stock_quantity']) && $variation->get_manage_stock()) {
                    $variation->set_stock_quantity(wc_stock_amount($variation_data['stock_quantity']));
                    $variation_updated = true;
                }

                if ($variation_updated) {
                    $variation->save();
                }
            }

            // Sync variable product
            WC_Product_Variable::sync($product_id);
            $updated_fields[] = 'variations';
        }

        // Clear product transients
        wc_delete_product_transients($product_id);

        wp_send_json_success([
            'message' => 'Product updated successfully.',
            'updated_fields' => $updated_fields,
            'product_id' => $product_id
        ]);

    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Error updating product: ' . $e->getMessage()], 500);
    }

} elseif ($action === 'get_tax_classes') {
    // Get available tax classes
    $tax_classes = [];
    
    // Standard rate
    $tax_classes[] = [
        'slug' => '',
        'name' => 'Standard rate'
    ];

    // Get custom tax classes
    $custom_tax_classes = WC_Tax::get_tax_classes();
    foreach ($custom_tax_classes as $tax_class) {
        $tax_classes[] = [
            'slug' => sanitize_title($tax_class),
            'name' => $tax_class
        ];
    }

    wp_send_json_success($tax_classes);

} elseif ($action === 'get_categories') {
    // Get all product categories
    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'fields' => 'all'
    ]);

    wp_send_json_success($categories);

} elseif ($action === 'get_tags') {
    // Get all product tags
    $tags = get_terms([
        'taxonomy' => 'product_tag',
        'hide_empty' => false,
        'fields' => 'all'
    ]);

    wp_send_json_success($tags);

} else {
    JPOS_Error_Handler::send_error('Invalid action specified.', 400);
}
