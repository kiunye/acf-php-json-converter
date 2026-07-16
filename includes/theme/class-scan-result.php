<?php
/**
 * Result of a theme scan: discovered field groups plus any errors.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Theme
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Theme;

/**
 * Holds the field groups discovered in the active theme, annotated with their
 * origin (php registration or local json file) and source path.
 *
 * @since 2.0.0
 */
class Scan_Result {

	/**
	 * Discovered field groups keyed by a stable index.
	 *
	 * Each entry is an array with keys: group (array), source (php|json),
	 * file (string path).
	 *
	 * @since 2.0.0
	 * @var array<int,array>
	 */
	private array $items = array();

	/**
	 * Human readable errors encountered while scanning.
	 *
	 * @since 2.0.0
	 * @var array<int,string>
	 */
	private array $errors = array();

	/**
	 * Record a discovered field group.
	 *
	 * @since 2.0.0
	 * @param array  $group  Field group definition.
	 * @param string $source Origin of the group: "php" or "json".
	 * @param string $file   Path the group was discovered at.
	 * @return void
	 */
	public function add_group( array $group, string $source, string $file ): void {
		$this->items[] = array(
			'group'  => $group,
			'source' => $source,
			'file'   => $file,
		);
	}

	/**
	 * Record an error message.
	 *
	 * @since 2.0.0
	 * @param string $message Error text.
	 * @return void
	 */
	public function add_error( string $message ): void {
		$this->errors[] = $message;
	}

	/**
	 * Discovered field groups with their origin metadata.
	 *
	 * @since 2.0.0
	 * @return array<int,array>
	 */
	public function get_groups(): array {
		return $this->items;
	}

	/**
	 * Error messages recorded during the scan.
	 *
	 * @since 2.0.0
	 * @return array<int,string>
	 */
	public function get_errors(): array {
		return $this->errors;
	}

	/**
	 * Number of field groups discovered.
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function get_group_count(): int {
		return count( $this->items );
	}

	/**
	 * Whether any groups were discovered.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function has_groups(): bool {
		return count( $this->items ) > 0;
	}

	/**
	 * Whether any errors were recorded.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function has_errors(): bool {
		return count( $this->errors ) > 0;
	}
}
