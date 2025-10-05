<?php
// FILE: /jpos/api/product-images.php
// Product image upload and management API for JPOS

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../wp-load.php';
    require_once __DIR__ . '/error_handler.php';
    
    // Check authentication
    JPOS_Error_Handler::check_auth('manage_woocommerce');
    
    // Get action
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        JPOS_Error_Handler::send_error('Action parameter required', 400);
    }
    
    // Verify nonce for all actions
    $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
    JPOS_Error_Handler::check_nonce($nonce, 'jpos-product-edit-nonce');
    
    /**
     * Validate image file
     * @param array $file File array from $_FILES
     * @return array|string Array with validated data or error message
     */
    function validate_image_file($file) {
        // Check if file exists
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return 'No file uploaded or invalid upload';
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
            ];
            return $error_messages[$file['error']] ?? 'Unknown upload error';
        }
        
        // Validate file size (5MB max)
        $max_size = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $max_size) {
            return 'File size exceeds 5MB limit';
        }
        
        // Validate MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mime_types = [
            'image/png',
            'image/jpeg',
            'image/jpg',
            'image/webp',
            'image/gif'
        ];
        
        if (!in_array($mime_type, $allowed_mime_types)) {
            return 'Invalid file type. Only PNG, JPG, JPEG, WebP, and GIF are allowed';
        }
        
        // Validate image dimensions
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return 'File is not a valid image';
        }
        
        list($width, $height) = $image_info;
        $max_dimension = 4000;
        
        if ($width > $max_dimension || $height > $max_dimension) {
            return "Image dimensions exceed {$max_dimension}x{$max_dimension}px limit";
        }
        
        return [
            'valid' => true,
            'mime_type' => $mime_type,
            'width' => $width,
            'height' => $height,
            'size' => $file['size']
        ];
    }
    
    /**
     * Upload file to WordPress media library
     * @param array $file File array from $_FILES
     * @param int $product_id Product ID to attach image to
     * @return array Array with attachment_id and URLs or error
     */
    function upload_to_media_library($file, $product_id) {
        // Validate file first
        $validation = validate_image_file($file);
        if (!is_array($validation) || !isset($validation['valid'])) {
            return ['error' => $validation];
        }
        
        // Setup WordPress file upload
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        // Handle the file upload
        $upload_overrides = [
            'test_form' => false,
            'mimes' => [
                'jpg|jpeg|jpe' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ]
        ];
        
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            return ['error' => $uploaded_file['error']];
        }
        
        // Prepare attachment data
        $file_path = $uploaded_file['file'];
        $file_name = basename($file_path);
        $file_type = $uploaded_file['type'];
        
        $attachment_data = [
            'post_mime_type' => $file_type,
            'post_title' => sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $product_id
        ];
        
        // Insert attachment into media library
        $attachment_id = wp_insert_attachment($attachment_data, $file_path, $product_id);
        
        if (is_wp_error($attachment_id)) {
            return ['error' => 'Failed to create attachment: ' . $attachment_id->get_error_message()];
        }
        
        // Generate attachment metadata (thumbnails)
        $attachment_metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_metadata);
        
        // Get URLs
        $full_url = wp_get_attachment_url($attachment_id);
        $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
        
        return [
            'attachment_id' => $attachment_id,
            'url' => $full_url,
            'thumbnail_url' => $thumbnail_url ?: $full_url,
            'filename' => $file_name
        ];
    }
    
    // Handle upload_featured action
    if ($action === 'upload_featured') {
        $product_id = absint($_POST['product_id'] ?? 0);
        
        if (!$product_id) {
            JPOS_Error_Handler::send_error('Product ID required', 400);
        }
        
        // Verify product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            JPOS_Error_Handler::send_error('Product not found', 404);
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['image'])) {
            JPOS_Error_Handler::send_error('No image file provided', 400);
        }
        
        // Upload image to media library
        $upload_result = upload_to_media_library($_FILES['image'], $product_id);
        
        if (isset($upload_result['error'])) {
            JPOS_Error_Handler::send_error($upload_result['error'], 400);
        }
        
        // Set as featured image
        $product->set_image_id($upload_result['attachment_id']);
        $product->save();
        
        // Send success response
        wp_send_json_success([
            'attachment_id' => $upload_result['attachment_id'],
            'url' => $upload_result['url'],
            'thumbnail_url' => $upload_result['thumbnail_url']
        ]);
        exit;
    }
    
    // Handle upload_gallery action
    elseif ($action === 'upload_gallery') {
        $product_id = absint($_POST['product_id'] ?? 0);
        
        if (!$product_id) {
            JPOS_Error_Handler::send_error('Product ID required', 400);
        }
        
        // Verify product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            JPOS_Error_Handler::send_error('Product not found', 404);
        }
        
        // Check if files were uploaded
        if (!isset($_FILES['images'])) {
            JPOS_Error_Handler::send_error('No image files provided', 400);
        }
        
        if (!is_array($_FILES['images']['name'])) {
            JPOS_Error_Handler::send_error('Invalid image files format', 400);
        }
        
        // Get current gallery images
        $current_gallery_ids = $product->get_gallery_image_ids();
        
        // Check gallery limit (max 10 images)
        $files_count = count($_FILES['images']['name']);
        $total_after_upload = count($current_gallery_ids) + $files_count;
        
        if ($total_after_upload > 10) {
            JPOS_Error_Handler::send_error('Gallery limit exceeded. Maximum 10 images allowed', 400);
        }
        
        $uploaded = [];
        $errors = [];
        
        // Process each uploaded file
        for ($i = 0; $i < $files_count; $i++) {
            // Reconstruct file array for single file
            $file = [
                'name' => $_FILES['images']['name'][$i],
                'type' => $_FILES['images']['type'][$i],
                'tmp_name' => $_FILES['images']['tmp_name'][$i],
                'error' => $_FILES['images']['error'][$i],
                'size' => $_FILES['images']['size'][$i]
            ];
            
            // Upload file
            $upload_result = upload_to_media_library($file, $product_id);
            
            if (isset($upload_result['error'])) {
                $errors[] = [
                    'filename' => $file['name'],
                    'error' => $upload_result['error']
                ];
            } else {
                $uploaded[] = $upload_result;
                $current_gallery_ids[] = $upload_result['attachment_id'];
            }
        }
        
        // Update product gallery if any uploads succeeded
        if (!empty($uploaded)) {
            $product->set_gallery_image_ids($current_gallery_ids);
            $product->save();
        }
        
        // Send response
        wp_send_json_success([
            'uploaded' => $uploaded,
            'errors' => $errors,
            'gallery_ids' => $current_gallery_ids
        ]);
        exit;
    }
    
    // Handle remove_featured action
    elseif ($action === 'remove_featured') {
        $product_id = absint($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        
        if (!$product_id) {
            JPOS_Error_Handler::send_error('Product ID required', 400);
        }
        
        // Verify product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            JPOS_Error_Handler::send_error('Product not found', 404);
        }
        
        // Remove featured image
        $product->set_image_id(0);
        $product->save();
        
        wp_send_json_success([
            'message' => 'Featured image removed successfully'
        ]);
        exit;
    }
    
    // Handle remove_gallery action
    elseif ($action === 'remove_gallery') {
        $product_id = absint($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
        $attachment_id = absint($_POST['attachment_id'] ?? $_GET['attachment_id'] ?? 0);
        
        if (!$product_id) {
            JPOS_Error_Handler::send_error('Product ID required', 400);
        }
        
        if (!$attachment_id) {
            JPOS_Error_Handler::send_error('Attachment ID required', 400);
        }
        
        // Verify product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            JPOS_Error_Handler::send_error('Product not found', 404);
        }
        
        // Get current gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        
        // Remove the specified attachment ID
        $gallery_ids = array_filter($gallery_ids, function($id) use ($attachment_id) {
            return $id != $attachment_id;
        });
        
        // Re-index array to avoid gaps
        $gallery_ids = array_values($gallery_ids);
        
        // Update product gallery
        $product->set_gallery_image_ids($gallery_ids);
        $product->save();
        
        wp_send_json_success([
            'message' => 'Gallery image removed successfully',
            'gallery_ids' => $gallery_ids
        ]);
        exit;
    }
    
    // Handle reorder_gallery action
    elseif ($action === 'reorder_gallery') {
        $product_id = absint($_POST['product_id'] ?? 0);
        
        if (!$product_id) {
            JPOS_Error_Handler::send_error('Product ID required', 400);
        }
        
        // Verify product exists
        $product = wc_get_product($product_id);
        if (!$product) {
            JPOS_Error_Handler::send_error('Product not found', 404);
        }
        
        // Get new order from POST data
        $input = file_get_contents('php://input');
        $data = JPOS_Error_Handler::safe_json_decode($input);
        
        if (!isset($data['gallery_ids']) || !is_array($data['gallery_ids'])) {
            JPOS_Error_Handler::send_error('Gallery IDs array required', 400);
        }
        
        $gallery_ids = array_map('absint', $data['gallery_ids']);
        
        // Validate gallery limit
        if (count($gallery_ids) > 10) {
            JPOS_Error_Handler::send_error('Gallery limit exceeded. Maximum 10 images allowed', 400);
        }
        
        // Update product gallery order
        $product->set_gallery_image_ids($gallery_ids);
        $product->save();
        
        wp_send_json_success([
            'message' => 'Gallery order updated successfully',
            'gallery_ids' => $gallery_ids
        ]);
        exit;
    }
    
    else {
        JPOS_Error_Handler::send_error('Invalid action', 400);
    }
    
} catch (Exception $e) {
    JPOS_Error_Handler::handle_exception($e, 'product-images');
}
?>