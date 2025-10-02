<?php
// FILE: /jpos/api/bundle-optimizer.php
// JavaScript and CSS bundling and optimization

class JPOS_Bundle_Optimizer {
    
    private static $js_dir;
    private static $css_dir;
    private static $build_dir;
    private static $minify_enabled = true;
    
    /**
     * Initialize bundle optimizer
     */
    public static function init() {
        self::$js_dir = __DIR__ . '/../assets/js/';
        self::$css_dir = __DIR__ . '/../assets/css/';
        self::$build_dir = __DIR__ . '/../assets/build/';
        
        // Create build directory if it doesn't exist
        if (!file_exists(self::$build_dir)) {
            wp_mkdir_p(self::$build_dir);
        }
    }
    
    /**
     * Create optimized JavaScript bundle
     */
    public static function create_js_bundle($modules = []) {
        $bundle_content = '';
        $bundle_hash = '';
        
        // Default modules if none specified
        if (empty($modules)) {
            $modules = [
                'modules/state.js',
                'modules/auth.js',
                'modules/products.js',
                'modules/cart.js',
                'modules/module-loader.js',
                'main-modular.js'
            ];
        }
        
        // Combine modules
        foreach ($modules as $module) {
            $file_path = self::$js_dir . $module;
            
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                
                // Remove comments and unnecessary whitespace
                if (self::$minify_enabled) {
                    $content = self::minify_js($content);
                }
                
                $bundle_content .= $content . "\n";
                $bundle_hash .= md5_file($file_path);
            }
        }
        
        // Generate bundle filename with hash
        $bundle_filename = 'jpos-bundle-' . substr(md5($bundle_hash), 0, 8) . '.js';
        $bundle_path = self::$build_dir . $bundle_filename;
        
        // Write bundle file
        file_put_contents($bundle_path, $bundle_content);
        
        return [
            'filename' => $bundle_filename,
            'path' => $bundle_path,
            'size' => filesize($bundle_path),
            'url' => '/wp-pos/assets/build/' . $bundle_filename
        ];
    }
    
    /**
     * Create optimized CSS bundle
     */
    public static function create_css_bundle($files = []) {
        $bundle_content = '';
        $bundle_hash = '';
        
        // Default CSS files if none specified
        if (empty($files)) {
            $files = [
                'main.css',
                'components.css'
            ];
        }
        
        // Combine CSS files
        foreach ($files as $file) {
            $file_path = self::$css_dir . $file;
            
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                
                // Minify CSS
                if (self::$minify_enabled) {
                    $content = self::minify_css($content);
                }
                
                $bundle_content .= $content . "\n";
                $bundle_hash .= md5_file($file_path);
            }
        }
        
        // Generate bundle filename with hash
        $bundle_filename = 'jpos-styles-' . substr(md5($bundle_hash), 0, 8) . '.css';
        $bundle_path = self::$build_dir . $bundle_filename;
        
        // Write bundle file
        file_put_contents($bundle_path, $bundle_content);
        
        return [
            'filename' => $bundle_filename,
            'path' => $bundle_path,
            'size' => filesize($bundle_path),
            'url' => '/wp-pos/assets/build/' . $bundle_filename
        ];
    }
    
    /**
     * Minify JavaScript
     */
    private static function minify_js($js) {
        // Remove single-line comments
        $js = preg_replace('#//.*$#m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('#/\*.*?\*/#s', '', $js);
        
        // Remove unnecessary whitespace
        $js = preg_replace('#\s+#', ' ', $js);
        $js = preg_replace('#\n\s*\n#', "\n", $js);
        
        // Remove whitespace around operators
        $js = preg_replace('#\s*([{}();,=+\-*/])\s*#', '$1', $js);
        
        return trim($js);
    }
    
    /**
     * Minify CSS
     */
    private static function minify_css($css) {
        // Remove comments
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        
        // Remove unnecessary whitespace
        $css = preg_replace('#\s+#', ' ', $css);
        $css = preg_replace('#\n\s*\n#', "\n", $css);
        
        // Remove whitespace around selectors and properties
        $css = preg_replace('#\s*([{}:;,>+~])\s*#', '$1', $css);
        
        return trim($css);
    }
    
    /**
     * Get bundle statistics
     */
    public static function get_bundle_stats() {
        $stats = [
            'js_files' => [],
            'css_files' => [],
            'total_size' => 0
        ];
        
        if (!file_exists(self::$build_dir)) {
            return $stats;
        }
        
        $js_files = glob(self::$build_dir . '*.js');
        $css_files = glob(self::$build_dir . '*.css');
        
        foreach ($js_files as $file) {
            $file_info = [
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
            $stats['js_files'][] = $file_info;
            $stats['total_size'] += $file_info['size'];
        }
        
        foreach ($css_files as $file) {
            $file_info = [
                'name' => basename($file),
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
            $stats['css_files'][] = $file_info;
            $stats['total_size'] += $file_info['size'];
        }
        
        return $stats;
    }
    
    /**
     * Clean old bundle files
     */
    public static function clean_old_bundles($keep_recent = 3) {
        if (!file_exists(self::$build_dir)) {
            return 0;
        }
        
        $js_files = glob(self::$build_dir . 'jpos-bundle-*.js');
        $css_files = glob(self::$build_dir . 'jpos-styles-*.css');
        $all_files = array_merge($js_files, $css_files);
        
        // Sort by modification time (newest first)
        usort($all_files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $cleaned = 0;
        $count = 0;
        
        foreach ($all_files as $file) {
            $count++;
            if ($count > $keep_recent) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Enable/disable minification
     */
    public static function set_minify_enabled($enabled) {
        self::$minify_enabled = $enabled;
    }
    
    /**
     * Generate bundle HTML tags
     */
    public static function generate_bundle_tags($js_bundle = null, $css_bundle = null) {
        $tags = [];
        
        if ($css_bundle && isset($css_bundle['url'])) {
            $tags[] = '<link rel="stylesheet" href="' . $css_bundle['url'] . '">';
        }
        
        if ($js_bundle && isset($js_bundle['url'])) {
            $tags[] = '<script src="' . $js_bundle['url'] . '"></script>';
        }
        
        return implode("\n    ", $tags);
    }
}

// Initialize bundle optimizer
JPOS_Bundle_Optimizer::init();
