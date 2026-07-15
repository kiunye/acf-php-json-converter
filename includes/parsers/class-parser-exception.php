<?php
/**
 * Exception thrown when the parser cannot statically resolve a value.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Parsers
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Parsers;

/**
 * Signals that a field group definition could not be parsed as a literal.
 *
 * @since 2.0.0
 */
class Parser_Exception extends \RuntimeException {

	/**
	 * Source line where the failure was detected.
	 *
	 * @since 2.0.0
	 * @var int
	 */
	private int $source_line;

	/**
	 * Construct the exception with a source line.
	 *
	 * @since 2.0.0
	 * @param int    $line    Source line number.
	 * @param string $message Human readable message.
	 */
	public function __construct( int $line, string $message ) {
		$this->source_line = $line;
		parent::__construct( $message );
	}

	/**
	 * Source line where the failure was detected.
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function get_line(): int {
		return $this->source_line;
	}
}
