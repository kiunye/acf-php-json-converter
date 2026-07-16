<?php
/**
 * Plugin Name: Field Group PHP-JSON Converter
 * Plugin URI: https://github.com/kiunye/field-group-php-json-converter
 * Description: Automatically scans theme files for ACF field groups defined
 * in PHP and converts them to JSON format for easy import/export and
 * synchronization.
 * Version: 2.0.0
 * Author: Chris Araya
 * Author URI: https://github.com/kiunye
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: field-group-php-json-converter
 * Domain Path: /languages
 *
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * Requires Plugins: advanced-custom-fields
 *
 * @package Field_Group_PHP_JSON_Converter
 *
 * This plugin provides a comprehensive solution for managing ACF field groups
 * by enabling seamless conversion between PHP and JSON formats. It includes:
 *
 * - Automatic theme scanning for ACF field groups
 * - Bidirectional conversion (PHP ↔ JSON)
 * - Batch processing with progress tracking
 * - Comprehensive error handling and logging
 * - Security features and input validation
 * - Integration with ACF Local JSON functionality
 *
 * The plugin is designed with a modular architecture using dependency injection
 * and follows WordPress coding standards and best practices.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'FIELD_GROUP_PHP_JSON_CONVERTER_VERSION', '2.0.0' );

/**
 * Plugin base directory path.
 */
define( 'FIELD_GROUP_PHP_JSON_CONVERTER_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin base URL.
 */
define( 'FIELD_GROUP_PHP_JSON_CONVERTER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register the plugin class autoloader.
 */
require_once FIELD_GROUP_PHP_JSON_CONVERTER_DIR . 'includes/autoload.php';

/**
 * The code that runs during plugin activation.
 */
function activate_field_group_php_json_converter() {
	require_once FIELD_GROUP_PHP_JSON_CONVERTER_DIR . 'includes/class-activator.php';
	Field_Group_PHP_JSON_Converter\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_field_group_php_json_converter() {
	require_once FIELD_GROUP_PHP_JSON_CONVERTER_DIR . 'includes/class-deactivator.php';
	Field_Group_PHP_JSON_Converter\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_field_group_php_json_converter' );
register_deactivation_hook( __FILE__, 'deactivate_field_group_php_json_converter' );

/**
 * Begins execution of the plugin.
 */
function run_field_group_php_json_converter() {
	require_once FIELD_GROUP_PHP_JSON_CONVERTER_DIR . 'includes/Bootstrap.php';

	Field_Group_PHP_JSON_Converter\Bootstrap::instance()->init();
}

// Run the plugin.
add_action( 'plugins_loaded', 'run_field_group_php_json_converter' );
