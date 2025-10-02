<?php
// FILE: /jpos/api/image-optimizer.php
// Image optimization utilities for JPOS

require_once __DIR__ . '/cache-manager.php';

class JPOS_Image_Optimizer {
    
    private static $cache_key_prefix = 'jpos_images_';
    private static $cache_duration = 3600; // 1 hour cache for image URLs
    
    /**
     * Get optimized image URL with caching and WebP support
     */
    public static function get_optimized_image_url($attachment_id, $size = 'medium', $enable_webp = true) {
        if (!$attachment_id) {
            return '';
        }
        
        $cache_key = self::$cache_key_prefix . $attachment_id . '_' . $size . ($enable_webp ? '_webp' : '');
        
        // Try to get from cache first
        $cached_url = JPOS_Cache_Manager::get($cache_key);
        if ($cached_url !== false) {
            return $cached_url;
        }
        
        // Get the image URL
        $image_url = wp_get_attachment_image_url($attachment_id, $size);
        if (!$image_url) {
            // Fallback to thumbnail if medium doesn't exist
            $image_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
        }
        if (!$image_url) {
            // Final fallback to full size
            $image_url = wp_get_attachment_image_url($attachment_id, 'full');
        }
        
        if (!$image_url) {
            return '';
        }
        
        // Check for WebP support if enabled
        if ($enable_webp && function_exists('imagewebp')) {
            $webp_url = self::get_webp_url($image_url);
            if ($webp_url) {
                $image_url = $webp_url;
            }
        }
        
        // Cache the result
        JPOS_Cache_Manager::set($cache_key, $image_url, self::$cache_duration);
        
        return $image_url;
    }
    
    /**
     * Check if WebP version of image exists and return URL
     */
    private static function get_webp_url($original_url) {
        // Convert URL to file path
        $upload_dir = wp_get_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $original_url);
        
        // Create WebP filename
        $path_info = pathinfo($file_path);
        $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        
        // Check if WebP version exists
        if (file_exists($webp_path)) {
            return str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $webp_path);
        }
        
        return false;
    }
    
    /**
     * Bulk load optimized image URLs for multiple attachments
     */
    public static function get_bulk_optimized_urls($attachment_ids, $size = 'medium', $enable_webp = true) {
        $urls = [];
        $uncached_ids = [];
        
        // Check cache for each attachment
        foreach ($attachment_ids as $attachment_id) {
            $cache_key = self::$cache_key_prefix . $attachment_id . '_' . $size . ($enable_webp ? '_webp' : '');
            $cached_url = JPOS_Cache_Manager::get($cache_key);
            
            if ($cached_url !== false) {
                $urls[$attachment_id] = $cached_url;
            } else {
                $uncached_ids[] = $attachment_id;
            }
        }
        
        // Process uncached attachments
        foreach ($uncached_ids as $attachment_id) {
            $urls[$attachment_id] = self::get_optimized_image_url($attachment_id, $size, $enable_webp);
        }
        
        return $urls;
    }
    
    /**
     * Clear image cache for specific attachment or all images
     */
    public static function clear_image_cache($attachment_id = null) {
        if ($attachment_id) {
            $cache_key = self::$cache_key_prefix . $attachment_id . '_*';
            JPOS_Cache_Manager::delete($cache_key);
        } else {
            // Clear all image caches
            $cache_dir = __DIR__ . '/../cache/';
            if (file_exists($cache_dir)) {
                $files = glob($cache_dir . md5(self::$cache_key_prefix . '*') . '.cache');
                foreach ($files as $file) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Generate WebP versions of images (utility function)
     */
    public static function generate_webp_versions($attachment_ids = []) {
        if (!function_exists('imagewebp')) {
            return false;
        }
        
        $upload_dir = wp_get_upload_dir();
        $generated = 0;
        
        foreach ($attachment_ids as $attachment_id) {
            $file_path = get_attached_file($attachment_id);
            if (!$file_path || !file_exists($file_path)) {
                continue;
            }
            
            $path_info = pathinfo($file_path);
            $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
            
            // Skip if WebP already exists
            if (file_exists($webp_path)) {
                continue;
            }
            
            // Create WebP version based on original image type
            $image = null;
            switch (strtolower($path_info['extension'])) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($file_path);
                    break;
                case 'png':
                    $image = imagecreatefrompng($file_path);
                    break;
                default:
                    continue 2; // Skip unsupported formats
            }
            
            if ($image && imagewebp($image, $webp_path, 80)) {
                imagedestroy($image);
                $generated++;
            }
        }
        
        return $generated;
    }
}

