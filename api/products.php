<?php
// FILE: /jpos/api/products.php

require_once __DIR__ . '/../../wp-load.php';

header('Content-Type: application/json');

if (!is_user_logged_in() || !current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Authentication required.'], 403);
    exit;
}

global $wpdb;

try {
    $all_posts = $wpdb->get_results("
        SELECT ID, post_title, post_parent, post_type, menu_order, post_status
        FROM {$wpdb->posts}
        WHERE post_type IN ('product', 'product_variation') AND post_status IN ('publish', 'private')
        ORDER BY menu_order, post_title ASC
    ", OBJECT_K);

    if (empty($all_posts)) {
        wp_send_json_success(['products' => [], 'categories' => [], 'tags' => []]);
        exit;
    }

    $all_post_ids = implode(',', array_keys($all_posts));
    
    $all_meta_results = $wpdb->get_results("
        SELECT post_id, meta_key, meta_value
        FROM {$wpdb->postmeta}
        WHERE post_id IN ({$all_post_ids})
        AND (meta_key LIKE 'attribute_%' OR meta_key IN ('_price', '_sku', '_stock_status', '_stock', '_manage_stock', '_thumbnail_id'))
    ");
    
    $meta_map = [];
    foreach ($all_meta_results as $meta) {
        $meta_map[$meta->post_id][$meta->meta_key] = $meta->meta_value;
    }

    $parent_ids = [];
    foreach($all_posts as $post) {
        if ($post->post_type === 'product' && $post->post_parent == 0) {
            $parent_ids[] = $post->ID;
        }
    }

    $terms_map = [];
    if(!empty($parent_ids)) {
        $terms_sql = "
            SELECT tr.object_id, t.term_id, t.name, tt.taxonomy
            FROM {$wpdb->term_relationships} tr
            JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE tr.object_id IN (" . implode(',', $parent_ids) . ")
            AND tt.taxonomy IN ('product_cat', 'product_tag', 'product_type')
        ";
        $all_terms = $wpdb->get_results($terms_sql);

        foreach ($all_terms as $term) {
            $terms_map[$term->object_id][$term->taxonomy][] = ['term_id' => (int)$term->term_id, 'name' => $term->name];
        }
    }
    
    $thumbnail_ids = [];
    foreach($meta_map as $metas) {
        if (!empty($metas['_thumbnail_id'])) {
            $thumbnail_ids[] = $metas['_thumbnail_id'];
        }
    }
    $image_urls = [];
    if (!empty($thumbnail_ids)) {
        $upload_dir_info = wp_get_upload_dir();
        $image_meta = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND post_id IN (" . implode(',', array_unique($thumbnail_ids)) . ")");
        foreach ($image_meta as $img) {
            $image_urls[$img->post_id] = $upload_dir_info['baseurl'] . '/' . $img->meta_value;
        }
    }
    
    $products = [];
    foreach ($all_posts as $post_id => $post) {
        if ($post->post_type !== 'product' || $post->post_parent != 0) continue;

        $meta = $meta_map[$post_id] ?? [];
        $terms = $terms_map[$post_id] ?? [];
        $type_terms = $terms['product_type'][0]['name'] ?? 'simple';

        $products[$post_id] = [
            'id'             => (int)$post_id, 'name' => $post->post_title,
            'sku'            => $meta['_sku'] ?? null, 'price' => $meta['_price'] ?? null,
            'stock_status'   => $meta['_stock_status'] ?? 'instock', 'manages_stock'  => ($meta['_manage_stock'] ?? 'no') === 'yes',
            'stock_quantity' => isset($meta['_stock']) ? (int)$meta['_stock'] : null,
            'type'           => $type_terms,
            'image_url'      => isset($meta['_thumbnail_id'], $image_urls[$meta['_thumbnail_id']]) ? $image_urls[$meta['_thumbnail_id']] : '',
            'category_ids'   => array_map(fn($t) => $t['term_id'], $terms['product_cat'] ?? []),
            'tag_ids'        => array_map(fn($t) => $t['term_id'], $terms['product_tag'] ?? []),
            'variations'     => [],
            'post_status'    => $post->post_status
        ];
    }

    foreach ($all_posts as $post_id => $post) {
        if ($post->post_type !== 'product_variation') continue;
        
        $parent_id = $post->post_parent;
        if (isset($products[$parent_id])) {
            $meta = $meta_map[$post_id] ?? [];
            $attributes = [];
            foreach ($meta as $key => $value) {
                if (strpos($key, 'attribute_') === 0) { $attributes[str_replace('attribute_', '', $key)] = $value; }
            }
            
            $products[$parent_id]['variations'][] = [
                'id' => (int)$post_id, 'parent_id' => (int)$parent_id, 'sku' => $meta['_sku'] ?? null,
                'price' => $meta['_price'] ?? null, 'stock_status' => $meta['_stock_status'] ?? 'instock',
                'manages_stock' => ($meta['_manage_stock'] ?? 'no') === 'yes',
                'stock_quantity' => isset($meta['_stock']) ? (int)$meta['_stock'] : null,
                'attributes' => $attributes,
                'image_url' => isset($meta['_thumbnail_id'], $image_urls[$meta['_thumbnail_id']]) ? $image_urls[$meta['_thumbnail_id']] : $products[$parent_id]['image_url'],
            ];
        }
    }

    foreach ($products as &$product) {
        if ($product['type'] === 'variable' && !empty($product['variations'])) {
            $in_stock_prices = [];
            $product['stock_status'] = 'outofstock';
            foreach($product['variations'] as $var) {
                if ($var['stock_status'] === 'instock') {
                    $product['stock_status'] = 'instock';
                    if (is_numeric($var['price'])) { $in_stock_prices[] = (float)$var['price']; }
                }
            }
            $product['min_price'] = !empty($in_stock_prices) ? min($in_stock_prices) : null;
        }
    }
    unset($product);

    $product_categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
    $product_tags = get_terms(['taxonomy' => 'product_tag', 'hide_empty' => true]);

    wp_send_json_success([
        'products'   => array_values($products),
        'categories' => $product_categories, 'tags' => $product_tags
    ]);

} catch (Exception $e) {
    wp_send_json_error(['message' => 'Error fetching products: ' . $e->getMessage()], 500);
}