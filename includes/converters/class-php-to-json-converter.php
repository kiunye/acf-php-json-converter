<?php
/**
 * Convert parsed ACF local field group arrays into ACF-compatible JSON.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Converters
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Converters;

/**
 * Serialize a field group (as produced by Field_Group_Parser) into the JSON
 * representation used by Advanced Custom Fields local JSON.
 *
 * @since 2.0.0
 */
class PHP_To_JSON_Converter {

	/**
	 * Canonical key order for ACF field group JSON.
	 *
	 * @since 2.0.0
	 * @var array<int,string>
	 */
	private const FIELD_ORDER = array(
		'key',
		'title',
		'fields',
		'location',
		'menu_order',
		'position',
		'style',
		'label_placement',
		'instruction_placement',
		'hide_on_screen',
		'active',
		'description',
		'modified',
	);

	/**
	 * Convert a single field group array into a JSON string.
	 *
	 * @since 2.0.0
	 * @param array $group Field group definition.
	 * @return string Pretty-printed JSON (4 space indent, trailing newline).
	 */
	public function convert( array $group ): string {
		$ordered = $this->order_keys( $group );
		$json    = json_encode( $ordered, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		return ( false === $json ) ? '' : $json . "\n";
	}

	/**
	 * Re-order a group array so exported JSON follows ACF's canonical key order.
	 *
	 * @since 2.0.0
	 * @param array $group Field group definition.
	 * @return array
	 */
	private function order_keys( array $group ): array {
		$ordered = array();

		foreach ( self::FIELD_ORDER as $key ) {
			if ( array_key_exists( $key, $group ) ) {
				$ordered[ $key ] = $group[ $key ];
			}
		}

		foreach ( $group as $key => $value ) {
			if ( ! array_key_exists( $key, $ordered ) ) {
				$ordered[ $key ] = $value;
			}
		}

		return $ordered;
	}
}
