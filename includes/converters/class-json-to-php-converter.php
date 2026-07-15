<?php
/**
 * Convert ACF local JSON field group definitions into PHP source code.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Converters
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Converters;

/**
 * Serialize a field group (as decoded from ACF JSON) into an
 * acf_add_local_field_group() call that registers it locally.
 *
 * @since 2.0.0
 */
class JSON_To_PHP_Converter {

	/**
	 * Keys that are JSON metadata only and must not appear in PHP registration.
	 *
	 * @since 2.0.0
	 * @var array<int,string>
	 */
	private const STRIP_KEYS = array( 'modified', 'local' );

	/**
	 * Convert one or more field groups into PHP source.
	 *
	 * A single group array yields one acf_add_local_field_group() call. A
	 * sequential list of groups yields one call per group.
	 *
	 * @since 2.0.0
	 * @param array $group Field group definition, or a list of them.
	 * @return string PHP source code.
	 */
	public function convert( array $group ): string {
		if ( $this->is_sequential( $group ) ) {
			$blocks = array();
			foreach ( $group as $single ) {
				if ( is_array( $single ) ) {
					$blocks[] = $this->convert_single( $single );
				}
			}
			$body = implode( "\n\n", $blocks );
		} else {
			$body = $this->convert_single( $group );
		}

		return "<?php\n\n" . $body . "\n";
	}

	/**
	 * Convert a single group into one acf_add_local_field_group() call.
	 *
	 * @since 2.0.0
	 * @param array $group Field group definition.
	 * @return string PHP source code.
	 */
	private function convert_single( array $group ): string {
		$clean = $this->strip_metadata( $group );
		$body  = $this->render_array( $clean, 1 );

		return "acf_add_local_field_group(\n" . $body . "\n);";
	}

	/**
	 * Remove ACF JSON-only metadata keys before emitting PHP.
	 *
	 * @since 2.0.0
	 * @param array $group Field group definition.
	 * @return array
	 */
	private function strip_metadata( array $group ): array {
		foreach ( self::STRIP_KEYS as $key ) {
			unset( $group[ $key ] );
		}
		return $group;
	}

	/**
	 * Render an array as a multi-line, tab-indented PHP array expression.
	 *
	 * @since 2.0.0
	 * @param array $value  Array to render.
	 * @param int   $depth  Indent level of the line holding "array(".
	 * @return string
	 */
	private function render_array( array $value, int $depth ): string {
		if ( empty( $value ) ) {
			return 'array()';
		}

		$lines = array( $this->pad( $depth ) . 'array(' );

		if ( $this->is_sequential( $value ) ) {
			foreach ( $value as $item ) {
				$lines[] = $this->render_element( $item, $depth + 1 );
			}
		} else {
			foreach ( $value as $key => $item ) {
				$lines[] = $this->render_assignment( $key, $item, $depth + 1 );
			}
		}

		$lines[] = $this->pad( $depth ) . ')';

		return implode( "\n", $lines );
	}

	/**
	 * Render a key/value pair as an assignment line (trailing comma included).
	 *
	 * @since 2.0.0
	 * @param string $key   Array key.
	 * @param mixed  $value Array value.
	 * @param int    $depth Indent level.
	 * @return string
	 */
	private function render_assignment( $key, $value, int $depth ): string {
		$line = $this->pad( $depth ) . $this->render_scalar( $key ) . ' => ';

		if ( is_array( $value ) ) {
			return $line . $this->render_array( $value, $depth ) . ',';
		}

		return $line . $this->render_scalar( $value ) . ',';
	}

	/**
	 * Render an array element (list item) as a line (trailing comma included).
	 *
	 * @since 2.0.0
	 * @param mixed $value Array element.
	 * @param int   $depth Indent level.
	 * @return string
	 */
	private function render_element( $value, int $depth ): string {
		if ( is_array( $value ) ) {
			return $this->render_array( $value, $depth ) . ',';
		}

		return $this->pad( $depth ) . $this->render_scalar( $value ) . ',';
	}

	/**
	 * Render a scalar value as PHP literal source.
	 *
	 * @since 2.0.0
	 * @param mixed $value Scalar value.
	 * @return string
	 */
	private function render_scalar( $value ): string {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		if ( is_null( $value ) ) {
			return 'null';
		}

		if ( is_int( $value ) || is_float( $value ) ) {
			return (string) $value;
		}

		if ( is_string( $value ) ) {
			return "'" . str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $value ) . "'";
		}

		return 'null';
	}

	/**
	 * Whether an array is a sequentially keyed list.
	 *
	 * @since 2.0.0
	 * @param array $items Array to inspect.
	 * @return bool
	 */
	private function is_sequential( array $items ): bool {
		if ( empty( $items ) ) {
			return true;
		}

		$expected = 0;
		foreach ( array_keys( $items ) as $key ) {
			if ( $key !== $expected ) {
				return false;
			}
			++$expected;
		}

		return true;
	}

	/**
	 * Tab-based indent for the given depth.
	 *
	 * @since 2.0.0
	 * @param int $depth Indent level.
	 * @return string
	 */
	private function pad( int $depth ): string {
		return str_repeat( "\t", $depth );
	}
}
