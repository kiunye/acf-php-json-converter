<?php
/**
 * Test bootstrap.
 *
 * Loads Composer's autoloader (PHPUnit, etc.) and registers the plugin's
 * class autoloader so the shared utility and feature classes resolve. The
 * full plugin runtime is intentionally not bootstrapped here.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/includes/autoload.php';
