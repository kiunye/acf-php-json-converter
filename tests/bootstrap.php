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

// Load plugin classes manually since we don't have WordPress autoloading
require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/utilities/class-logger.php';
require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/utilities/class-security.php';
require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/services/class-file-manager.php';
require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/services/class-scanner-service.php';
require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/services/class-converter-service.php';
require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/admin/class-admin-controller.php';

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