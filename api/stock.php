<?php
// FILE: /jpos/api/stock.php

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_variations') {
    $nonce = $data['nonce'] ?? '';
    JPOS_Error_Handler::check_nonce($nonce, 'jpos_stock_nonce');
}

if ($action === 'get_details') {
    $product_id = absint($_GET['id']);
    if (!$product_id) {
        JPOS_Error_Handler::send_error('Product ID is required.', 400);
    }

    $product = wc_get_product($product_id);

    if (!$product) {
        JPOS_Error_Handler::handle_not_found_error('Product');
    }

    $details = [
        'id' => $product->get_id(),
        'name' => $product->get_name(),
        'type' => $product->get_type(),
        'sku' => $product->get_sku(),
        'price' => $product->get_price(),
        'stock_status' => $product->get_stock_status(),
        'stock_quantity' => $product->get_stock_quantity(),
        'manages_stock' => $product->get_manage_stock(),
    ];

    if ($product->is_type('variable')) {
        $details['variations'] = [];
        $variations_ids = $product->get_children();

        foreach ($variations_ids as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) continue;
            
            $details['variations'][] = [
                'id' => $variation->get_id(),
                'sku' => $variation->get_sku(),
                'price' => wc_format_decimal($variation->get_price(), 2),
                'stock_status' => $variation->get_stock_status(),
                'stock_quantity' => $variation->get_stock_quantity(),
                'manages_stock' => $variation->get_manage_stock(),
                'attributes' => $variation->get_variation_attributes(),
            ];
        }
    }

    wp_send_json_success($details);

} elseif ($action === 'update_variations') {
    $parent_id = absint($data['parent_id']);
    $variations_data = $data['variations'] ?? [];

    if (!$parent_id || empty($variations_data)) {
        wp_send_json_error(['message' => 'Invalid data provided.'], 400);
        exit;
    }

    $parent_product = wc_get_product($parent_id);
    if (!$parent_product || !$parent_product->is_type('variable')) {
        wp_send_json_error(['message' => 'Parent product not found or is not variable.'], 404);
        exit;
    }

    $updated_count = 0;
    $errors = [];
    
    foreach ($variations_data as $v_data) {
        $variation_id = absint($v_data['id']);
        if (!$variation_id) {
            $errors[] = "Invalid variation ID in data";
            continue;
        }

        $variation = wc_get_product($variation_id);
        if (!$variation) {
            $errors[] = "Variation ID {$variation_id} not found";
            continue;
        }
        
        if ($variation->get_parent_id() !== $parent_id) {
            $errors[] = "Variation ID {$variation_id} does not belong to parent product {$parent_id}";
            continue;
        }

        $changes_made = false;

        // Update SKU if provided and different
        if (isset($v_data['sku']) && $variation->get_sku() !== $v_data['sku']) {
            $variation->set_sku(sanitize_text_field($v_data['sku']));
            $changes_made = true;
        }

        // Update price if provided and different
        if (isset($v_data['price'])) {
            $new_price = wc_format_decimal($v_data['price'], 2);
            if ($variation->get_price() !== $new_price) {
                $variation->set_price($new_price);
                $variation->set_regular_price($new_price);
                $changes_made = true;
            }
        }

        // Handle stock management checkbox - set manage_stock based on checkbox value
        if (isset($v_data['manage_stock'])) {
            $new_manage_stock = $v_data['manage_stock'] === true || $v_data['manage_stock'] === 'true' || $v_data['manage_stock'] === 1 || $v_data['manage_stock'] === '1';
            $current_manage_stock = $variation->get_manage_stock();
            
            if ($current_manage_stock !== $new_manage_stock) {
                $variation->set_manage_stock($new_manage_stock);
                $changes_made = true;
                
                // If disabling stock management, clear stock quantity
                if (!$new_manage_stock) {
                    $variation->set_stock_quantity(null);
                }
            }
        }

        // Handle stock quantity - only if manage_stock is enabled
        if (isset($v_data['manage_stock']) && ($v_data['manage_stock'] === true || $v_data['manage_stock'] === 'true' || $v_data['manage_stock'] === 1 || $v_data['manage_stock'] === '1')) {
            if (isset($v_data['stock_quantity']) && $v_data['stock_quantity'] !== '' && $v_data['stock_quantity'] !== null) {
                $new_stock_qty = absint($v_data['stock_quantity']); // Use absint for consistency
                
                // Always update stock quantity when provided (ensures it's saved even if same value)
                // WooCommerce will auto-calculate stock_status based on quantity when manage_stock is true
                $variation->set_stock_quantity($new_stock_qty);
                $changes_made = true;
            }
        } elseif (isset($v_data['stock_quantity']) && $v_data['stock_quantity'] !== '' && $v_data['stock_quantity'] !== null) {
            // Legacy support: if stock_quantity is provided but manage_stock is not explicitly set,
            // enable stock management (backward compatibility)
            $new_stock_qty = absint($v_data['stock_quantity']);
            if (!$variation->get_manage_stock()) {
                $variation->set_manage_stock(true);
                $changes_made = true;
            }
            $variation->set_stock_quantity($new_stock_qty);
            $changes_made = true;
        }
        
        // Always save if changes were made to ensure persistence
        if ($changes_made) {
            $save_result = $variation->save();
            if ($save_result && !is_wp_error($save_result)) {
                $updated_count++;
            } else {
                $errors[] = "Failed to save variation ID {$variation_id}";
            }
        }
    }

    if ($updated_count > 0) {
        wc_delete_product_transients($parent_id);
        WC_Product_Variable::sync($parent_id);
        $parent_product->save();
    }

    // Build response message
    $message = "$updated_count variations updated successfully.";
    if (!empty($errors)) {
        $message .= " " . count($errors) . " error(s): " . implode(", ", $errors);
    }
    
    if ($updated_count > 0) {
        wp_send_json_success(['message' => $message, 'updated_count' => $updated_count, 'errors' => $errors]);
    } else {
        wp_send_json_error(['message' => 'No variations were updated. ' . (empty($errors) ? 'No changes detected.' : implode(", ", $errors)), 'errors' => $errors], 400);
    }

} else {
    wp_send_json_error(['message' => 'Invalid action.'], 400);
}
