<?php
/**
 * PHPUnit bootstrap file.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Tests
 */

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Define plugin constants for testing
if (!defined('ACF_PHP_JSON_CONVERTER_DIR')) {
    define('ACF_PHP_JSON_CONVERTER_DIR', __DIR__ . '/../');
}

if (!defined('ACF_PHP_JSON_CONVERTER_URL')) {
    define('ACF_PHP_JSON_CONVERTER_URL', 'http://example.com/wp-content/plugins/acf-php-json-converter/');
}

if (!defined('ACF_PHP_JSON_CONVERTER_VERSION')) {
    define('ACF_PHP_JSON_CONVERTER_VERSION', '1.0.0');
}

// Define WordPress constants for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

if (!defined('FS_CHMOD_DIR')) {
    define('FS_CHMOD_DIR', 0755);
}

if (!defined('FS_CHMOD_FILE')) {
    define('FS_CHMOD_FILE', 0644);
}

// Load plugin classes manually since we don't have WordPress autoloading
$plugin_files = [
    'includes/utilities/class-logger.php',
    'includes/utilities/class-security.php',
    'includes/utilities/class-error-handler.php',
    'includes/utilities/class-progress-tracker.php',
    'includes/parsers/class-php-parser.php',
    'includes/converters/class-php-to-json-converter.php',
    'includes/converters/class-json-to-php-converter.php',
    'includes/converters/class-validator.php',
    'includes/services/class-file-manager.php',
    'includes/services/class-scanner-service.php',
    'includes/services/class-converter-service.php',
    'includes/admin/class-admin-controller.php',
];

foreach ($plugin_files as $file) {
    $file_path = ACF_PHP_JSON_CONVERTER_DIR . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

// Mock WordPress functions that are commonly used
if (!function_exists('add_management_page')) {
    function add_management_page($page_title, $menu_title, $capability, $menu_slug, $callback) {
        return 'test-hook';
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message) {
        throw new Exception($message);
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data) {
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data) {
        echo json_encode(['success' => false, 'data' => $data]);
        exit;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src, $deps = [], $ver = false, $media = 'all') {
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src, $deps = [], $ver = false, $in_footer = false) {
        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        return true;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path) {
        return 'http://example.com/wp-admin/' . $path;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'test-nonce-' . $action;
    }
}

if (!function_exists('get_admin_page_title')) {
    function get_admin_page_title() {
        return 'ACF PHP-JSON Converter';
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo __($text, $domain);
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = 'default') {
        echo esc_attr(__($text, $domain));
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
// Mock WordPress functions that are commonly used
if (!function_exists('add_management_page')) {
    function add_management_page($page_title, $menu_title, $capability, $menu_slug, $callback) {
        return 'test-hook';
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message) {
        throw new Exception($message);
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data) {
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data) {
        echo json_encode(['success' => false, 'data' => $data]);
        exit;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src, $deps = [], $ver = false, $media = 'all') {
        return true;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src, $deps = [], $ver = false, $in_footer = false) {
        return true;
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        return true;
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path) {
        return 'http://example.com/wp-admin/' . $path;
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'test-nonce-' . $action;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return strpos($nonce, 'test-nonce-') === 0;
    }
}

if (!function_exists('get_admin_page_title')) {
    function get_admin_page_title() {
        return 'ACF PHP-JSON Converter';
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('_e')) {
    function _e($text, $domain = 'default') {
        echo __($text, $domain);
    }
}

if (!function_exists('esc_attr_e')) {
    function esc_attr_e($text, $domain = 'default') {
        echo esc_attr(__($text, $domain));
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_js')) {
    function esc_js($text) {
        return json_encode($text);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name($filename) {
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('wp_kses')) {
    function wp_kses($data, $allowed_html) {
        // Simple implementation that allows specific tags
        if (is_array($allowed_html) && !empty($allowed_html)) {
            $allowed_tags = '<' . implode('><', array_keys($allowed_html)) . '>';
            return strip_tags($data, $allowed_tags);
        }
        return strip_tags($data);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0) {
        return json_encode($data, $options);
    }
}

if (!function_exists('wp_normalize_path')) {
    function wp_normalize_path($path) {
        return str_replace('\\', '/', $path);
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        // Check for test override
        if (isset($GLOBALS['wp_upload_dir_override'])) {
            return array_merge([
                'path' => '/tmp/uploads',
                'url' => 'http://example.com/wp-content/uploads',
                'subdir' => '',
                'basedir' => '/tmp/uploads',
                'baseurl' => 'http://example.com/wp-content/uploads',
                'error' => false,
            ], $GLOBALS['wp_upload_dir_override']);
        }
        
        return [
            'path' => '/tmp/uploads',
            'url' => 'http://example.com/wp-content/uploads',
            'subdir' => '',
            'basedir' => '/tmp/uploads',
            'baseurl' => 'http://example.com/wp-content/uploads',
            'error' => false,
        ];
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($target) {
        return mkdir($target, 0755, true);
    }
}

if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory() {
        // Check for test override
        if (isset($GLOBALS['stylesheet_directory_override'])) {
            return $GLOBALS['stylesheet_directory_override'];
        }
        return '/tmp/theme';
    }
}

if (!function_exists('get_template_directory')) {
    function get_template_directory() {
        return '/tmp/theme-parent';
    }
}

if (!function_exists('is_child_theme')) {
    function is_child_theme() {
        return false;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true; // For testing, assume user has all capabilities
    }
}

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return true;
    }
}

if (!function_exists('wp_get_current_user')) {
    function wp_get_current_user() {
        return (object) [
            'ID' => 1,
            'user_login' => 'testuser',
        ];
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        if ($type === 'mysql') {
            return date('Y-m-d H:i:s');
        }
        return time();
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $wp_test_options;
        if (!isset($wp_test_options)) {
            $wp_test_options = [];
        }
        return isset($wp_test_options[$option]) ? $wp_test_options[$option] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        global $wp_test_options;
        if (!isset($wp_test_options)) {
            $wp_test_options = [];
        }
        $wp_test_options[$option] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        global $wp_test_options;
        if (!isset($wp_test_options)) {
            $wp_test_options = [];
        }
        unset($wp_test_options[$option]);
        return true;
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = []) {
        return true;
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook, $args = []) {
        return false;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) {
        return $value;
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit($string) {
        return rtrim($string, '/\\') . '/';
    }
}

if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        if ($special_chars) {
            $chars .= '!@#$%^&*()';
        }
        return substr(str_shuffle($chars), 0, $length);
    }
}

if (!function_exists('WP_Filesystem')) {
    function WP_Filesystem($args = false) {
        return true;
    }
}

if (!function_exists('request_filesystem_credentials')) {
    function request_filesystem_credentials($form_post, $type = '', $error = false, $context = false, $extra_fields = null) {
        return true;
    }
}

// Mock WP_UnitTestCase for PHPUnit compatibility
if (!class_exists('WP_UnitTestCase')) {
    class WP_UnitTestCase extends PHPUnit\Framework\TestCase {
        public function setUp(): void {
            parent::setUp();
        }
        
        public function tearDown(): void {
            parent::tearDown();
        }
    }
}

// Mock WP_Filesystem_Base
if (!class_exists('WP_Filesystem_Base')) {
    class WP_Filesystem_Base {
        public function mkdir($path, $chmod = false) {
            return mkdir($path, 0755, true);
        }
        
        public function put_contents($file, $contents, $mode = false) {
            return file_put_contents($file, $contents);
        }
        
        public function chmod($file, $mode = false, $recursive = false) {
            return chmod($file, $mode ?: 0644);
        }
        
        public function copy($source, $destination, $overwrite = false) {
            return copy($source, $destination);
        }
        
        public function move($source, $destination, $overwrite = false) {
            return rename($source, $destination);
        }
        
        public function rmdir($path, $recursive = false) {
            if ($recursive) {
                return $this->delete_recursive($path);
            }
            return rmdir($path);
        }
        
        private function delete_recursive($path) {
            if (is_dir($path)) {
                $objects = scandir($path);
                foreach ($objects as $object) {
                    if ($object != "." && $object != "..") {
                        if (is_dir($path . "/" . $object)) {
                            $this->delete_recursive($path . "/" . $object);
                        } else {
                            unlink($path . "/" . $object);
                        }
                    }
                }
                return rmdir($path);
            }
            return false;
        }
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = []) {
        if (is_object($args)) {
            $parsed_args = get_object_vars($args);
        } elseif (is_array($args)) {
            $parsed_args = &$args;
        } else {
            wp_parse_str($args, $parsed_args);
        }
        
        if (is_array($defaults)) {
            return array_merge($defaults, $parsed_args);
        }
        return $parsed_args;
    }
}

if (!function_exists('wp_parse_str')) {
    function wp_parse_str($string, &$array) {
        parse_str($string, $array);
        
        if (get_magic_quotes_gpc()) {
            $array = stripslashes_deep($array);
        }
        
        $array = apply_filters('wp_parse_str', $array);
    }
}

if (!function_exists('stripslashes_deep')) {
    function stripslashes_deep($value) {
        return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    }
}

if (!function_exists('get_magic_quotes_gpc')) {
    function get_magic_quotes_gpc() {
        return false; // PHP 5.4+ doesn't have magic quotes
    }
}

// Mock global $wp_filesystem
global $wp_filesystem;
if (!$wp_filesystem) {
    $wp_filesystem = new WP_Filesystem_Base();
}

// Add missing WordPress functions for integration tests
if (!function_exists('get_transient')) {
    function get_transient($transient) {
        return false; // Simulate no cached data
    }
}

if (!function_exists('set_transient')) {
    function set_transient($transient, $value, $expiration = 0) {
        return true;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient($transient) {
        return true;
    }
}

if (!function_exists('get_stylesheet_directory')) {
    function get_stylesheet_directory() {
        return '/tmp/test-theme';
    }
}

if (!function_exists('get_template_directory')) {
    function get_template_directory() {
        return '/tmp/test-theme';
    }
}

if (!function_exists('get_template')) {
    function get_template() {
        return 'test-theme';
    }
}

if (!function_exists('get_stylesheet')) {
    function get_stylesheet() {
        return 'test-theme';
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit($string) {
        return rtrim($string, '/\\') . '/';
    }
}

if (!function_exists('untrailingslashit')) {
    function untrailingslashit($string) {
        return rtrim($string, '/\\');
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512) {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        if (is_array($title)) {
            $args = $title;
            $title = '';
        }
        
        if (defined('DOING_AJAX') && DOING_AJAX) {
            echo json_encode(array('success' => false, 'data' => array('message' => $message)));
        } else {
            echo $message;
        }
        exit;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true; // Assume user has all capabilities in tests
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        if ($type === 'mysql') {
            return date('Y-m-d H:i:s');
        }
        return time();
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        return true;
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir($time = null, $create_dir = true, $refresh_cache = false) {
        $upload_dir = sys_get_temp_dir() . '/wp-uploads';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        return array(
            'path' => $upload_dir,
            'url' => 'http://example.com/wp-content/uploads',
            'subdir' => '',
            'basedir' => $upload_dir,
            'baseurl' => 'http://example.com/wp-content/uploads',
            'error' => false
        );
    }
}

if (!function_exists('wp_normalize_path')) {
    function wp_normalize_path($path) {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('|(?<=.)/+|', '/', $path);
        if (':' === substr($path, 1, 1)) {
            $path = ucfirst($path);
        }
        return $path;
    }
}