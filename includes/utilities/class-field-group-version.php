<?php
/**
 * ACF version detection helpers.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

namespace Field_Group_PHP_JSON_Converter\Utilities;

/**
 * Detects ACF version for schema differences.
 *
 * @package Field_Group_PHP_JSON_Converter
 */
final class Field_Group_Version {
	/**
	 * Get detected ACF version.
	 *
	 * @return string
	 */
	public static function get(): string {
		if ( defined( 'ACF_VERSION' ) ) {
			return (string) ACF_VERSION;
		}

		return '0.0.0';
	}

	/**
	 * Whether ACF is v6 or later.
	 *
	 * @return bool
	 */
	public static function is_v6_or_later(): bool {
		return version_compare( self::get(), '6.0.0', '>=' );
	}
}
