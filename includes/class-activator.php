<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    Field_Group_PHP_JSON_Converter
 */

namespace Field_Group_PHP_JSON_Converter;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Activator {

	/**
	 * Plugin activation setup.
	 *
	 * Creates necessary database tables, sets default options,
	 * and performs compatibility checks.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Set default plugin options
		self::set_default_options();

		// Check system requirements
		self::check_requirements();
	}

	/**
	 * Set default plugin options.
	 *
	 * @since    1.0.0
	 */
	private static function set_default_options() {
		$default_options = array(
			'auto_create_json_folder' => true,
			'default_export_location' => 'acf-json',
			'logging_level'           => 'error', // error, warning, info, debug
			'backup_before_write'     => true,
			'version'                 => FIELD_GROUP_PHP_JSON_CONVERTER_VERSION,
		);

		// Only add options if they don't exist
		if ( ! get_option( 'field_group_php_json_converter_settings' ) ) {
			add_option( 'field_group_php_json_converter_settings', $default_options );
		}
	}

	/**
	 * Check system requirements.
	 *
	 * @since    1.0.0
	 */
	private static function check_requirements() {
		// Check PHP version
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			// We won't deactivate the plugin, but we'll show a notice
			add_action(
				'admin_notices',
				function () {
					?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Field Group PHP-JSON Converter requires PHP 8.0 or higher. Please upgrade your PHP version.', 'field-group-php-json-converter' ); ?></p>
				</div>
					<?php
				}
			);
		}

		// Check WordPress version
		if ( version_compare( get_bloginfo( 'version' ), '6.5', '<' ) ) {
			// We won't deactivate the plugin, but we'll show a notice
			add_action(
				'admin_notices',
				function () {
					?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Field Group PHP-JSON Converter requires WordPress 6.5 or higher. Please upgrade your WordPress installation.', 'field-group-php-json-converter' ); ?></p>
				</div>
					<?php
				}
			);
		}
	}
}
