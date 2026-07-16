<?php
/**
 * Theme-level conversion orchestration built on top of the scanner and writer.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Theme
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Theme;

use Field_Group_PHP_JSON_Converter\Backup\Field_Group_Writer;
use Field_Group_PHP_JSON_Converter\Converters\JSON_To_PHP_Converter;
use Field_Group_PHP_JSON_Converter\Converters\PHP_To_JSON_Converter;
use Field_Group_PHP_JSON_Converter\Services\Conversion_Result;
use Field_Group_PHP_JSON_Converter\Utilities\Security;

/**
 * Tie the theme scanner to the file writer so discovered groups can be
 * converted both ways on disk: PHP registrations become Local JSON files, and
 * Local JSON definitions become ready-to-paste PHP registration code.
 *
 * @since 2.0.0
 */
class Theme_Service {

	/**
	 * Theme scanner.
	 *
	 * @since 2.0.0
	 * @var Theme_Scanner
	 */
	private Theme_Scanner $scanner;

	/**
	 * Backup-aware file writer.
	 *
	 * @since 2.0.0
	 * @var Field_Group_Writer
	 */
	private Field_Group_Writer $writer;

	/**
	 * PHP array to JSON converter.
	 *
	 * @since 2.0.0
	 * @var PHP_To_JSON_Converter
	 */
	private PHP_To_JSON_Converter $to_json;

	/**
	 * JSON to PHP registration converter.
	 *
	 * @since 2.0.0
	 * @var JSON_To_PHP_Converter
	 */
	private JSON_To_PHP_Converter $to_php;

	/**
	 * Security helper that knows the allowed theme directories.
	 *
	 * @since 2.0.0
	 * @var Security
	 */
	private Security $security;

	/**
	 * Inject collaborators.
	 *
	 * @since 2.0.0
	 * @param Theme_Scanner|null         $scanner  Theme scanner.
	 * @param Field_Group_Writer|null    $writer   Backup-aware writer.
	 * @param PHP_To_JSON_Converter|null $to_json  PHP to JSON converter.
	 * @param JSON_To_PHP_Converter|null $to_php   JSON to PHP converter.
	 * @param Security|null              $security Theme path helper.
	 */
	public function __construct(
		?Theme_Scanner $scanner = null,
		?Field_Group_Writer $writer = null,
		?PHP_To_JSON_Converter $to_json = null,
		?JSON_To_PHP_Converter $to_php = null,
		?Security $security = null
	) {
		$this->scanner  = $scanner ?? new Theme_Scanner();
		$this->writer   = $writer ?? new Field_Group_Writer();
		$this->to_json  = $to_json ?? new PHP_To_JSON_Converter();
		$this->to_php   = $to_php ?? new JSON_To_PHP_Converter();
		$this->security = $security ?? new Security();
	}

	/**
	 * Scan the active theme.
	 *
	 * @since 2.0.0
	 * @return Scan_Result
	 */
	public function scan_theme(): Scan_Result {
		return $this->scanner->scan();
	}

	/**
	 * Write every PHP-registered group discovered in the theme into the theme's
	 * Local JSON directory (acf-json/).
	 *
	 * @since 2.0.0
	 * @param Scan_Result|null $scan Optional pre-computed scan result.
	 * @return Conversion_Result
	 */
	public function export_php_groups_to_local_json( ?Scan_Result $scan = null ): Conversion_Result {
		$result = new Conversion_Result();
		$scan   = $scan ?? $this->scanner->scan();

		$json_dir = $this->local_json_dir();
		if ( null === $json_dir ) {
			$result->add_error( 'Could not determine the theme Local JSON directory.' );
			return $result;
		}

		foreach ( $scan->get_errors() as $error ) {
			$result->add_error( $error );
		}

		foreach ( $scan->get_notices() as $notice ) {
			$result->add_notice( $notice );
		}

		$written = 0;
		foreach ( $scan->get_groups() as $item ) {
			if ( 'php' !== $item['source'] ) {
				continue;
			}

			$group     = $item['group'];
			$key       = isset( $group['key'] ) ? (string) $group['key'] : 'group';
			$file_name = $this->safe_file_name( $key ) . '.json';
			$json_path = rtrim( $json_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . $file_name;

			if ( $this->writer->write_json_file( $json_path, $group ) ) {
				$result->add_written_path( $json_path );
				++$written;
			} else {
				$result->add_error( sprintf( 'Failed to write Local JSON file for group "%s".', $key ) );
			}
		}

		if ( 0 === $written && $result->is_success() ) {
			$result->add_error( 'No PHP-registered field groups were found to convert.' );
		}

		return $result;
	}

	/**
	 * Build PHP registration code for every Local JSON group discovered in the
	 * theme, suitable for pasting into the active theme's functions.php.
	 *
	 * @since 2.0.0
	 * @param Scan_Result|null $scan Optional pre-computed scan result.
	 * @return Conversion_Result
	 */
	public function convert_json_groups_to_php_code( ?Scan_Result $scan = null ): Conversion_Result {
		$result = new Conversion_Result();
		$scan   = $scan ?? $this->scanner->scan();

		foreach ( $scan->get_errors() as $error ) {
			$result->add_error( $error );
		}

		foreach ( $scan->get_notices() as $notice ) {
			$result->add_notice( $notice );
		}

		$groups = array();
		foreach ( $scan->get_groups() as $item ) {
			if ( 'json' !== $item['source'] ) {
				continue;
			}
			$groups[] = $item['group'];
		}

		if ( 0 === count( $groups ) ) {
			if ( $result->is_success() ) {
				$result->add_error( 'No Local JSON field groups were found to convert.' );
			}
			return $result;
		}

		$result->set_output( $this->to_php->convert( $groups ) );

		return $result;
	}

	/**
	 * Resolve the theme Local JSON directory, creating it if possible.
	 *
	 * @since 2.0.0
	 * @return string|null
	 */
	private function local_json_dir(): ?string {
		$dirs      = $this->security->get_allowed_theme_dirs();
		$theme_dir = ! empty( $dirs ) ? (string) $dirs[0] : '';
		if ( '' === $theme_dir || ! is_dir( $theme_dir ) ) {
			return null;
		}

		$json_dir = rtrim( $theme_dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'acf-json';
		if ( ! is_dir( $json_dir ) ) {
			if ( ! mkdir( $json_dir, 0755, true ) && ! is_dir( $json_dir ) ) {
				return null;
			}
		}

		return $json_dir;
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
}
