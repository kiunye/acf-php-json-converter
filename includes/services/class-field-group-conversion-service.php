<?php
/**
 * Orchestrate PHP <-> JSON field group conversions with backup safety.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Services
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Services;

use Field_Group_PHP_JSON_Converter\Backup\Field_Group_Writer;
use Field_Group_PHP_JSON_Converter\Converters\JSON_To_PHP_Converter;
use Field_Group_PHP_JSON_Converter\Converters\PHP_To_JSON_Converter;
use Field_Group_PHP_JSON_Converter\Parsers\Field_Group_Parser;

/**
 * High level conversion operations used by both the REST API and the admin UI.
 *
 * The service composes the parser, converters, and backup-aware writer so that
 * callers only deal with intents ("turn this PHP into JSON", "write these
 * groups to disk") rather than the individual moving parts.
 *
 * @since 2.0.0
 */
class Field_Group_Conversion_Service {

	/**
	 * PHP source parser.
	 *
	 * @since 2.0.0
	 * @var Field_Group_Parser
	 */
	private Field_Group_Parser $parser;

	/**
	 * PHP array to JSON converter.
	 *
	 * @since 2.0.0
	 * @var PHP_To_JSON_Converter
	 */
	private PHP_To_JSON_Converter $to_json;

	/**
	 * JSON array to PHP converter.
	 *
	 * @since 2.0.0
	 * @var JSON_To_PHP_Converter
	 */
	private JSON_To_PHP_Converter $to_php;

	/**
	 * Backup-aware file writer.
	 *
	 * @since 2.0.0
	 * @var Field_Group_Writer
	 */
	private Field_Group_Writer $writer;

	/**
	 * Inject collaborators.
	 *
	 * @since 2.0.0
	 * @param Field_Group_Parser|null    $parser Parser instance.
	 * @param PHP_To_JSON_Converter|null $to_json PHP to JSON converter.
	 * @param JSON_To_PHP_Converter|null $to_php  JSON to PHP converter.
	 * @param Field_Group_Writer|null    $writer Backup-aware writer.
	 */
	public function __construct(
		?Field_Group_Parser $parser = null,
		?PHP_To_JSON_Converter $to_json = null,
		?JSON_To_PHP_Converter $to_php = null,
		?Field_Group_Writer $writer = null
	) {
		$this->parser  = $parser ?? new Field_Group_Parser();
		$this->to_json = $to_json ?? new PHP_To_JSON_Converter();
		$this->to_php  = $to_php ?? new JSON_To_PHP_Converter();
		$this->writer  = $writer ?? new Field_Group_Writer();
	}

	/**
	 * Convert pasted PHP source into ACF JSON (single group or a list).
	 *
	 * @since 2.0.0
	 * @param string $php_code PHP source code.
	 * @return Conversion_Result
	 */
	public function php_source_to_json( string $php_code ): Conversion_Result {
		$result = new Conversion_Result();
		$parsed = $this->parser->parse_source( $php_code );

		$groups = $parsed->get_field_groups();
		if ( 0 === count( $groups ) ) {
			foreach ( $parsed->get_errors() as $error ) {
				$result->add_error( $error->get_message() );
			}
			if ( $result->is_success() ) {
				$result->add_error( 'No field groups were found in the supplied PHP.' );
			}
			return $result;
		}

		if ( 1 === count( $groups ) ) {
			$result->set_output( $this->to_json->convert( $groups[0] ) );
			return $result;
		}

		$payload = array();
		foreach ( $groups as $group ) {
			// Strip the trailing newline the converter adds for standalone output.
			$payload[] = json_decode( $this->to_json->convert( $group ), true );
		}
		$result->set_output( self::encode_json( $payload ) );

		return $result;
	}

	/**
	 * Convert pasted JSON source into a PHP registration file.
	 *
	 * @since 2.0.0
	 * @param string $json_code JSON source code.
	 * @return Conversion_Result
	 */
	public function json_source_to_php( string $json_code ): Conversion_Result {
		$result  = new Conversion_Result();
		$decoded = json_decode( $json_code, true );

		if ( null === $decoded && 'null' !== trim( $json_code ) ) {
			$result->add_error( 'The supplied JSON could not be decoded.' );
			return $result;
		}

		if ( ! is_array( $decoded ) ) {
			$result->add_error( 'JSON must contain a field group object or a list of them.' );
			return $result;
		}

		$result->set_output( $this->to_php->convert( $decoded ) );

		return $result;
	}

	/**
	 * Parse a PHP file and write each field group to its own JSON file.
	 *
	 * @since 2.0.0
	 * @param string $php_path Path to the PHP file.
	 * @param string $json_dir Directory to write JSON files into.
	 * @return Conversion_Result
	 */
	public function export_php_file( string $php_path, string $json_dir ): Conversion_Result {
		$result = new Conversion_Result();

		$parsed = $this->parser->parse_file( $php_path );
		foreach ( $parsed->get_errors() as $error ) {
			$result->add_error( $error->get_message() );
		}

		$groups = $parsed->get_field_groups();
		if ( 0 === count( $groups ) ) {
			if ( $result->is_success() ) {
				$result->add_error( 'No field groups were found in the PHP file.' );
			}
			return $result;
		}

		if ( ! is_dir( $json_dir ) ) {
			if ( ! mkdir( $json_dir, 0755, true ) && ! is_dir( $json_dir ) ) {
				$result->add_error( sprintf( 'Could not create the JSON directory "%s".', $json_dir ) );
				return $result;
			}
		}

		foreach ( $groups as $group ) {
			$key       = isset( $group['key'] ) ? (string) $group['key'] : 'group';
			$file_name = $this->safe_file_name( $key ) . '.json';
			$json_path = rtrim( $json_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $file_name;

			if ( $this->writer->write_json_file( $json_path, $group ) ) {
				$result->add_written_path( $json_path );
			} else {
				$result->add_error( sprintf( 'Failed to write JSON file for group "%s".', $key ) );
			}
		}

		return $result;
	}

	/**
	 * Convert a JSON file (single group or list) into a PHP registration file.
	 *
	 * @since 2.0.0
	 * @param string $json_path Path to the JSON file.
	 * @param string $php_path  Destination PHP file path.
	 * @return Conversion_Result
	 */
	public function import_json_file( string $json_path, string $php_path ): Conversion_Result {
		$result = new Conversion_Result();

		if ( ! is_file( $json_path ) || ! is_readable( $json_path ) ) {
			$result->add_error( sprintf( 'JSON file "%s" could not be read.', $json_path ) );
			return $result;
		}

		$decoded = json_decode( (string) file_get_contents( $json_path ), true );
		if ( null === $decoded ) {
			$result->add_error( 'The JSON file could not be decoded.' );
			return $result;
		}

		if ( ! is_array( $decoded ) ) {
			$result->add_error( 'JSON must contain a field group object or a list of them.' );
			return $result;
		}

		if ( $this->writer->write_php_file( $php_path, $decoded ) ) {
			$result->add_written_path( $php_path );
		} else {
			$result->add_error( sprintf( 'Failed to write PHP file "%s".', $php_path ) );
		}

		return $result;
	}

	/**
	 * Build a safe filesystem name from a group key.
	 *
	 * @since 2.0.0
	 * @param string $key Field group key.
	 * @return string
	 */
	private function safe_file_name( string $key ): string {
		$safe = preg_replace( '/[^a-zA-Z0-9_\-]/', '-', $key );
		$safe = trim( $safe, '-' );

		return '' !== $safe ? $safe : 'group';
	}

	/**
	 * Encode a value as pretty JSON without requiring WordPress.
	 *
	 * @since 2.0.0
	 * @param mixed $value Value to encode.
	 * @return string
	 */
	public static function encode_json( $value ): string {
		$json = json_encode( $value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return ( false === $json ) ? '' : $json . "\n";
	}
}
