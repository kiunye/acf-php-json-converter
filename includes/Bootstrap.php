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
	 * The Phase 0 foundation contains only the compliance scaffolding
	 * (activation/deactivation, uninstall, and the shared utility classes).
	 * Feature wiring is added in later phases.
	 *
	 * @return void
	 */
	public function init(): void {
		// Intentional no-op for the Phase 0 compliance baseline.
	}
}
