<?php
/**
 * Outcome of parsing one PHP source file.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Parsers
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Parsers;

/**
 * Holds the field groups extracted from a file plus any errors encountered.
 *
 * @since 2.0.0
 */
class Parser_Result {

	/**
	 * Extracted field groups.
	 *
	 * @since 2.0.0
	 * @var array<int,array>
	 */
	private array $field_groups = array();

	/**
	 * Errors encountered while parsing.
	 *
	 * @since 2.0.0
	 * @var array<int,Parser_Error>
	 */
	private array $errors = array();

	/**
	 * Record an extracted field group.
	 *
	 * @since 2.0.0
	 * @param array $group Normalized field group structure.
	 * @return void
	 */
	public function add_field_group( array $group ): void {
		$this->field_groups[] = $group;
	}

	/**
	 * Record a parse error.
	 *
	 * @since 2.0.0
	 * @param Parser_Error $error Error to record.
	 * @return void
	 */
	public function add_error( Parser_Error $error ): void {
		$this->errors[] = $error;
	}

	/**
	 * Extracted field groups.
	 *
	 * @since 2.0.0
	 * @return array<int,array>
	 */
	public function get_field_groups(): array {
		return $this->field_groups;
	}

	/**
	 * Errors encountered while parsing.
	 *
	 * @since 2.0.0
	 * @return array<int,Parser_Error>
	 */
	public function get_errors(): array {
		return $this->errors;
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

	/**
	 * Whether parsing completed without recording any errors.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_success(): bool {
		return ! $this->has_errors();
	}

	/**
	 * Number of field groups extracted from the source.
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function get_field_group_count(): int {
		return count( $this->field_groups );
	}

	/**
	 * Number of errors encountered while parsing.
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function get_error_count(): int {
		return count( $this->errors );
	}

	/**
	 * Whether nothing was extracted and no errors were recorded.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_empty(): bool {
		return count( $this->field_groups ) === 0 && count( $this->errors ) === 0;
	}
}
