<?php
/**
 * Class autoloader for the plugin's Field_Group_PHP_JSON_Converter namespace.
 *
 * Resolves both PSR-4 style filenames and the legacy `class-{slug}.php`
 * convention used by the shared utility classes, without relying on a
 * bundled third party autoloader.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

namespace Field_Group_PHP_JSON_Converter;

spl_autoload_register(
	function ( $class_name ) {
		$prefix = 'Field_Group_PHP_JSON_Converter\\';
		if ( strncmp( $class_name, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$segments = explode( '\\', $relative );
		$class    = array_pop( $segments );
		$ns_path  = implode( '/', $segments );

		$slug = strtolower( (string) preg_replace( '/[^a-zA-Z0-9]+/', '-', $class ) );
		$slug = trim( $slug, '-' );

		$base_variants = array(
			dirname( __DIR__ ) . '/includes/' . $ns_path . '/',
			dirname( __DIR__ ) . '/includes/' . strtolower( $ns_path ) . '/',
		);

		$file_variants = array( $class, strtolower( $class ), 'class-' . $slug );

		foreach ( $base_variants as $base ) {
			foreach ( $file_variants as $file ) {
				$path = $base . $file . '.php';
				if ( file_exists( $path ) ) {
					require_once $path;
					return;
				}
			}
		}
	}
);
