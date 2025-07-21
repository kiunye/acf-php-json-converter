<?php
/**
 * Security Utility.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Utilities
 */

namespace ACF_PHP_JSON_Converter\Utilities;

/**
 * Security Utility Class.
 *
 * Handles security-related functionality including input sanitization,
 * capability checks, nonce verification, and path validation.
 */
class Security {

    /**
     * Required capability for plugin access.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $required_capability    Required capability.
     */
    private $required_capability = 'manage_options';

    /**
     * Allowed file extensions for scanning.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $allowed_extensions    Allowed file extensions.
     */
    private $allowed_extensions = array('php');

    /**
     * Disallowed directories for scanning.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $disallowed_dirs    Disallowed directories.
     */
    private $disallowed_dirs = array(
        'node_modules',
        'vendor',
        'bower_components',
        '.git',
        '.svn',
        '.idea',
        '.vscode',
    );

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Apply filters to allow customization of security settings
        $this->required_capability = apply_filters('acf_php_json_converter_required_capability', $this->required_capability);
        $this->allowed_extensions = apply_filters('acf_php_json_converter_allowed_extensions', $this->allowed_extensions);
        $this->disallowed_dirs = apply_filters('acf_php_json_converter_disallowed_dirs', $this->disallowed_dirs);
    }

    /**
     * Verify nonce.
     *
     * @since    1.0.0
     * @param    string    $nonce     Nonce to verify.
     * @param    string    $action    Action name.
     * @return   bool      True if nonce is valid, false otherwise.
     */
    public function verify_nonce($nonce, $action = 'acf_php_json_converter_nonce') {
        if (empty($nonce)) {
            return false;
        }
        
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Check user capability.
     *
     * @since    1.0.0
     * @param    string    $capability    Capability to check. Default is the required capability.
     * @return   bool      True if user has capability, false otherwise.
     */
    public function check_capability($capability = '') {
        if (empty($capability)) {
            $capability = $this->required_capability;
        }
        
        return current_user_can($capability);
    }

    /**
     * Verify request with nonce and capability check.
     *
     * @since    1.0.0
     * @param    string    $nonce         Nonce to verify.
     * @param    string    $action        Action name.
     * @param    string    $capability    Capability to check.
     * @return   bool      True if request is valid, false otherwise.
     */
    public function verify_request($nonce, $action = 'acf_php_json_converter_nonce', $capability = '') {
        // Check nonce
        if (!$this->verify_nonce($nonce, $action)) {
            return false;
        }
        
        // Check capability
        if (!$this->check_capability($capability)) {
            return false;
        }
        
        return true;
    }

    /**
     * Sanitize input.
     *
     * @since    1.0.0
     * @param    mixed     $input    Input to sanitize.
     * @param    string    $type     Type of sanitization (text, textarea, filename, etc.).
     * @return   mixed     Sanitized input.
     */
    public function sanitize_input($input, $type = 'text') {
        if ($input === null) {
            return null;
        }
        
        switch ($type) {
            case 'text':
                return sanitize_text_field($input);
                
            case 'textarea':
                return sanitize_textarea_field($input);
                
            case 'filename':
                return sanitize_file_name($input);
                
            case 'key':
                return preg_replace('/[^a-z0-9_-]/', '', strtolower($input));
                
            case 'path':
                // Remove any directory traversal attempts
                $input = str_replace('..', '', $input);
                $input = preg_replace('/[\/]{2,}/', '/', $input);
                return sanitize_text_field($input);
                
            case 'url':
                return esc_url_raw($input);
                
            case 'int':
                return intval($input);
                
            case 'float':
                return floatval($input);
                
            case 'bool':
                if (is_bool($input)) {
                    return $input;
                }
                if (is_string($input)) {
                    $input = strtolower($input);
                    return in_array($input, array('true', '1', 'yes', 'on'), true);
                }
                return (bool) $input;
                
            case 'email':
                return sanitize_email($input);
                
            case 'array':
                if (!is_array($input)) {
                    return array();
                }
                $sanitized = array();
                foreach ($input as $key => $value) {
                    $sanitized_key = is_numeric($key) ? $key : sanitize_text_field($key);
                    if (is_array($value)) {
                        $sanitized[$sanitized_key] = $this->sanitize_input($value, 'array');
                    } else {
                        $sanitized[$sanitized_key] = sanitize_text_field($value);
                    }
                }
                return $sanitized;
                
            case 'json':
                if (is_string($input)) {
                    $decoded = json_decode($input, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $this->sanitize_input($decoded, 'array');
                    }
                    return null;
                }
                if (is_array($input)) {
                    return $this->sanitize_input($input, 'array');
                }
                return null;
                
            case 'html':
                // Allow specific HTML tags
                return wp_kses($input, array(
                    'a' => array(
                        'href' => array(),
                        'title' => array(),
                        'target' => array(),
                    ),
                    'br' => array(),
                    'em' => array(),
                    'strong' => array(),
                    'p' => array(),
                    'ul' => array(),
                    'ol' => array(),
                    'li' => array(),
                    'code' => array(),
                    'pre' => array(),
                ));
                
            case 'acf_field_group':
                // Special sanitization for ACF field groups
                if (!is_array($input)) {
                    return array();
                }
                
                $sanitized = array();
                $allowed_keys = array(
                    'key', 'title', 'fields', 'location', 'menu_order', 'position',
                    'style', 'label_placement', 'instruction_placement', 'hide_on_screen',
                    'active', 'description', 'modified'
                );
                
                foreach ($allowed_keys as $key) {
                    if (isset($input[$key])) {
                        if ($key === 'fields' || $key === 'location' || $key === 'hide_on_screen') {
                            $sanitized[$key] = $this->sanitize_input($input[$key], 'array');
                        } else {
                            $sanitized[$key] = $this->sanitize_input($input[$key], 'text');
                        }
                    }
                }
                
                return $sanitized;
                
            default:
                return sanitize_text_field($input);
        }
    }

    /**
     * Validate file path.
     *
     * @since    1.0.0
     * @param    string    $path          Path to validate.
     * @param    array     $allowed_dirs  Allowed directories. If empty, uses theme directories.
     * @return   bool      True if path is valid, false otherwise.
     */
    public function validate_path($path, $allowed_dirs = array()) {
        // Normalize path
        $path = wp_normalize_path($path);
        
        // Remove any directory traversal attempts
        $path = str_replace('..', '', $path);
        
        // If no allowed directories specified, use theme directories
        if (empty($allowed_dirs)) {
            $allowed_dirs = $this->get_allowed_theme_dirs();
        }
        
        // Check if path is within allowed directories
        foreach ($allowed_dirs as $allowed_dir) {
            $allowed_dir = wp_normalize_path($allowed_dir);
            
            if (strpos($path, $allowed_dir) === 0) {
                // Check if path contains disallowed directories
                foreach ($this->disallowed_dirs as $disallowed_dir) {
                    if (strpos($path, '/' . $disallowed_dir . '/') !== false) {
                        return false;
                    }
                }
                
                // Check if file exists
                if (file_exists($path)) {
                    // Check file extension if it's a file
                    if (is_file($path)) {
                        $extension = pathinfo($path, PATHINFO_EXTENSION);
                        if (!in_array($extension, $this->allowed_extensions, true)) {
                            return false;
                        }
                    }
                    
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Get allowed theme directories.
     *
     * @since    1.0.0
     * @return   array    Allowed theme directories.
     */
    public function get_allowed_theme_dirs() {
        $allowed_dirs = array();
        
        // Add current theme directory
        $allowed_dirs[] = get_stylesheet_directory();
        
        // Add parent theme directory if using child theme
        if (is_child_theme()) {
            $allowed_dirs[] = get_template_directory();
        }
        
        return $allowed_dirs;
    }

    /**
     * Validate file extension.
     *
     * @since    1.0.0
     * @param    string    $file_path    File path.
     * @param    array     $extensions   Allowed extensions. If empty, uses allowed_extensions.
     * @return   bool      True if extension is valid, false otherwise.
     */
    public function validate_extension($file_path, $extensions = array()) {
        if (empty($extensions)) {
            $extensions = $this->allowed_extensions;
        }
        
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        
        return in_array($extension, $extensions, true);
    }

    /**
     * Escape output.
     *
     * @since    1.0.0
     * @param    mixed     $output    Output to escape.
     * @param    string    $type      Type of escaping (html, attr, url, etc.).
     * @return   mixed     Escaped output.
     */
    public function escape_output($output, $type = 'html') {
        if ($output === null) {
            return '';
        }
        
        switch ($type) {
            case 'html':
                return esc_html($output);
                
            case 'attr':
                return esc_attr($output);
                
            case 'url':
                return esc_url($output);
                
            case 'textarea':
                return esc_textarea($output);
                
            case 'js':
                return esc_js($output);
                
            case 'json':
                // Escape for use in HTML attributes
                return esc_attr(wp_json_encode($output));
                
            case 'kses':
                // Allow specific HTML tags
                return wp_kses($output, array(
                    'a' => array(
                        'href' => array(),
                        'title' => array(),
                        'target' => array(),
                    ),
                    'br' => array(),
                    'em' => array(),
                    'strong' => array(),
                    'p' => array(),
                    'ul' => array(),
                    'ol' => array(),
                    'li' => array(),
                    'code' => array(),
                    'pre' => array(),
                ));
                
            default:
                return esc_html($output);
        }
    }

    /**
     * Check if a directory is writable.
     *
     * @since    1.0.0
     * @param    string    $dir    Directory path.
     * @return   bool      True if writable, false otherwise.
     */
    public function is_writable($dir) {
        // Check if directory exists
        if (!is_dir($dir)) {
            return false;
        }
        
        // Check if directory is writable
        if (!is_writable($dir)) {
            return false;
        }
        
        // Try to create a temporary file
        $temp_file = trailingslashit($dir) . 'acf_php_json_write_test_' . time() . '.tmp';
        $handle = @fopen($temp_file, 'w');
        
        if ($handle) {
            fclose($handle);
            @unlink($temp_file);
            return true;
        }
        
        return false;
    }

    /**
     * Get WordPress filesystem credentials.
     *
     * @since    1.0.0
     * @return   array|false    Filesystem credentials or false on failure.
     */
    public function get_filesystem_credentials() {
        // Include WordPress filesystem functionality
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        // Initialize WordPress filesystem
        $credentials = request_filesystem_credentials('', '', false, false, null);
        
        if (false === $credentials || !WP_Filesystem($credentials)) {
            return false;
        }
        
        return $credentials;
    }

    /**
     * Generate a secure token.
     *
     * @since    1.0.0
     * @param    int       $length    Token length.
     * @return   string    Generated token.
     */
    public function generate_token($length = 32) {
        return wp_generate_password($length, false, false);
    }
}