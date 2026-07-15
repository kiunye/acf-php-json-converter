<?php
/**
 * Phase 0 foundation smoke test.
 *
 * Validates that the compliance foundation (shared utility classes and the
 * bootstrap composition root) loads cleanly. Feature wiring is added in
 * later phases and is out of scope for this test.
 *
 * @package ACF_PHP_JSON_Converter
 */

namespace ACF_PHP_JSON_Converter\Tests;

use PHPUnit\Framework\TestCase;

class FoundationTest extends TestCase {

	/**
	 * The shared utility classes must be present and loadable.
	 */
	public function test_utility_classes_exist(): void {
		$this->assertTrue( class_exists( \ACF_PHP_JSON_Converter\Utilities\Logger::class ) );
		$this->assertTrue( class_exists( \ACF_PHP_JSON_Converter\Utilities\Security::class ) );
		$this->assertTrue( class_exists( \ACF_PHP_JSON_Converter\Utilities\Error_Handler::class ) );
		$this->assertTrue( class_exists( \ACF_PHP_JSON_Converter\Utilities\Progress_Tracker::class ) );
		$this->assertTrue( class_exists( \ACF_PHP_JSON_Converter\Utilities\ACF_Version::class ) );
	}

	/**
	 * The bootstrap composition root must load and be safe to initialize.
	 */
	public function test_bootstrap_is_loadable_and_safe_to_init(): void {
		$this->assertTrue( class_exists( \ACF_PHP_JSON_Converter\Bootstrap::class ) );

		$bootstrap = \ACF_PHP_JSON_Converter\Bootstrap::instance();
		$bootstrap->init();

		$this->assertInstanceOf( \ACF_PHP_JSON_Converter\Bootstrap::class, $bootstrap );
	}
}
