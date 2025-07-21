<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 */

namespace ACF_PHP_JSON_Converter;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class Deactivator {

    /**
     * Plugin deactivation cleanup.
     *
     * Cleans up temporary files and caches.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear any transients used by the plugin
        delete_transient('acf_php_json_converter_scan_cache');
        
        // Clean up any temporary files
        self::cleanup_temp_files();
    }

    /**
     * Clean up temporary files created by the plugin.
     *
     * @since    1.0.0
     */
    private static function cleanup_temp_files() {
        // Get WordPress upload directory
        $upload_dir = wp_upload_dir();
        
        // Define temp directory path
        $temp_dir = trailingslashit($upload_dir['basedir']) . 'acf-php-json-converter-temp';
        
        // Check if directory exists
        if (is_dir($temp_dir)) {
            // Get all files in the directory
            $files = glob($temp_dir . '/*');
            
            // Loop through files and delete them
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            
            // Try to remove the directory
            @rmdir($temp_dir);
        }
    }
}