<?php
/**
 * Structured description of a failure encountered while parsing a file.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Parsers
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Parsers;

/**
 * Captures one parse failure with a stable code and readable message.
 *
 * @since 2.0.0
 */
class Parser_Error {

	/**
	 * File the error relates to.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $file;

	/**
	 * Source line, when known.
	 *
	 * @since 2.0.0
	 * @var int
	 */
	private int $line;

	/**
	 * Stable machine readable code.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $code;

	/**
	 * Human readable message.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $message;

	/**
	 * Construct a parse error.
	 *
	 * @since 2.0.0
	 * @param string $file    File path or name.
	 * @param int    $line    Source line (0 when unknown).
	 * @param string $code    Stable error code.
	 * @param string $message Readable message.
	 */
	public function __construct( string $file, int $line, string $code, string $message ) {
		$this->file    = $file;
		$this->line    = $line;
		$this->code    = $code;
		$this->message = $message;
	}

	/**
	 * File the error relates to.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_file(): string {
		return $this->file;
	}

	/**
	 * Source line, when known.
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function get_line(): int {
		return $this->line;
	}

	/**
	 * Stable machine readable code.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_code(): string {
		return $this->code;
	}

	/**
	 * Human readable message.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_message(): string {
		return $this->message;
	}
}
