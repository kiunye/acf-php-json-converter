<?php
/**
 * Plugin bootstrap / composition root.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

namespace Field_Group_PHP_JSON_Converter;

/**
 * Main bootstrap for wiring plugin services.
 *
 * @package Field_Group_PHP_JSON_Converter
 */
final class Bootstrap {
	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Bootstrap constructor.
	 *
	 * @return void
	 */
	private function __construct() {}

	/**
	 * Initialize plugin runtime.
	 *
	 * Wires the REST API controller (always available to authenticated
	 * clients) and the admin UI (only inside the WordPress admin).
	 *
	 * @return void
	 */
	public function init(): void {
		$rest = new \Field_Group_PHP_JSON_Converter\Rest\Rest_Controller();
		add_action( 'rest_api_init', array( $rest, 'register_routes' ) );

		if ( is_admin() ) {
			$admin = new \Field_Group_PHP_JSON_Converter\Admin\Admin();
			$admin->register_hooks();
		}
	}
}
