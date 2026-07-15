<?php
/**
 * Minimal test bootstrap for the Phase 0 compliance foundation.
 *
 * Loads Composer's autoloader (PHPUnit, etc.) and explicitly requires the
 * shared utility classes, which are not wired into the runtime boot path
 * yet. The full plugin runtime is intentionally not bootstrapped here.
 *
 * @package ACF_PHP_JSON_Converter
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

foreach ( glob( dirname( __DIR__ ) . '/includes/utilities/*.php' ) as $file ) {
	require_once $file;
}
