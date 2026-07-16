<?php
/**
 * Outcome of a conversion operation.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Services
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Services;

/**
 * Holds the converted output (for in-memory conversions) and/or the list of
 * files written to disk, plus any errors encountered along the way.
 *
 * @since 2.0.0
 */
class Conversion_Result {

	/**
	 * Converted source output, or null when nothing was produced in memory.
	 *
	 * @since 2.0.0
	 * @var string|null
	 */
	private ?string $output = null;

	/**
	 * Paths of files written to disk.
	 *
	 * @since 2.0.0
	 * @var array<int,string>
	 */
	private array $written = array();

	/**
	 * Human readable error messages.
	 *
	 * @since 2.0.0
	 * @var array<int,string>
	 */
	private array $errors = array();

	/**
	 * Set the in-memory converted output.
	 *
	 * @since 2.0.0
	 * @param string $output Converted source code.
	 * @return void
	 */
	public function set_output( string $output ): void {
		$this->output = $output;
	}

	/**
	 * Record a path written to disk.
	 *
	 * @since 2.0.0
	 * @param string $path Written file path.
	 * @return void
	 */
	public function add_written_path( string $path ): void {
		$this->written[] = $path;
	}

	/**
	 * Record an error message.
	 *
	 * @since 2.0.0
	 * @param string $message Error message.
	 * @return void
	 */
	public function add_error( string $message ): void {
		$this->errors[] = $message;
	}

	/**
	 * Whether the operation completed without errors.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_success(): bool {
		return 0 === count( $this->errors );
	}

	/**
	 * In-memory output, if any.
	 *
	 * @since 2.0.0
	 * @return string|null
	 */
	public function get_output(): ?string {
		return $this->output;
	}

	/**
	 * Paths written to disk.
	 *
	 * @since 2.0.0
	 * @return array<int,string>
	 */
	public function get_written_paths(): array {
		return $this->written;
	}

	/**
	 * Error messages.
	 *
	 * @since 2.0.0
	 * @return array<int,string>
	 */
	public function get_errors(): array {
		return $this->errors;
	}
}
