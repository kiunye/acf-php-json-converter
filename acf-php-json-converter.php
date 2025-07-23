<?php
/**
 * Plugin Name: ACF PHP-to-JSON Converter
 * Plugin URI: https://example.com/acf-php-json-converter
 * Description: Automatically scans theme files for ACF field groups defined in PHP and converts them to JSON format for easy import/export and synchronization.
 * Version: 1.0.0
 * Author: Chris Araya
 * Author URI: https://github.com/kiunye
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: acf-php-json-converter
 * Domain Path: /languages
 * 
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * 
 * @package ACF_PHP_JSON_Converter
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('ACF_PHP_JSON_CONVERTER_VERSION', '1.0.0');

/**
 * Plugin base directory path.
 */
define('ACF_PHP_JSON_CONVERTER_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin base URL.
 */
define('ACF_PHP_JSON_CONVERTER_URL', plugin_dir_url(__FILE__));

/**
 * Autoload plugin classes.
 */
spl_autoload_register(function ($class_name) {
    // Check if the class name starts with our plugin prefix
    if (strpos($class_name, 'ACF_PHP_JSON_Converter\\') === 0) {
        // Remove the prefix
        $class_name = str_replace('ACF_PHP_JSON_Converter\\', '', $class_name);
        
        // Convert namespace separators to directory separators
        $class_file = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        
        // Build the full path to the class file
        $class_path = ACF_PHP_JSON_CONVERTER_DIR . 'includes' . DIRECTORY_SEPARATOR . $class_file . '.php';
        
        // If the file exists, require it
        if (file_exists($class_path)) {
            require_once $class_path;
        }
    }
});

/**
 * The code that runs during plugin activation.
 */
function activate_acf_php_json_converter() {
    require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/class-activator.php';
    ACF_PHP_JSON_Converter\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_acf_php_json_converter() {
    require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/class-deactivator.php';
    ACF_PHP_JSON_Converter\Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_acf_php_json_converter');
register_deactivation_hook(__FILE__, 'deactivate_acf_php_json_converter');

/**
 * Begins execution of the plugin.
 */
function run_acf_php_json_converter() {
    // Check if ACF is active
    if (!class_exists('ACF')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('ACF PHP-to-JSON Converter requires Advanced Custom Fields to be installed and activated.', 'acf-php-json-converter'); ?></p>
            </div>
            <?php
        });
        return;
    }
    
    // Initialize the plugin
    require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/class-plugin.php';
    $plugin = new ACF_PHP_JSON_Converter\Plugin();
    $plugin->run();
}

// Run the plugin
add_action('plugins_loaded', 'run_acf_php_json_converter');