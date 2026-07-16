<?php
/**
 * Theme scanner for ACF field groups (PHP registrations and Local JSON).
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Theme
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Theme;

use Field_Group_PHP_JSON_Converter\Parsers\Field_Group_Parser;
use Field_Group_PHP_JSON_Converter\Utilities\Security;

/**
 * Walk the active theme (parent and child) and collect every ACF field group
 * it defines, whether registered in PHP or stored as Local JSON.
 *
 * @since 2.0.0
 */
class Theme_Scanner {

	/**
	 * PHP source parser for acf_add_local_field_group() registrations.
	 *
	 * @since 2.0.0
	 * @var Field_Group_Parser
	 */
	private Field_Group_Parser $parser;

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
	 * @param Field_Group_Parser|null $parser   PHP source parser.
	 * @param Security|null           $security Theme path helper.
	 */
	public function __construct( ?Field_Group_Parser $parser = null, ?Security $security = null ) {
		$this->parser   = $parser ?? new Field_Group_Parser();
		$this->security = $security ?? new Security();
	}

	/**
	 * Scan the active theme for field groups defined in PHP or Local JSON.
	 *
	 * @since 2.0.0
	 * @return Scan_Result
	 */
	public function scan(): Scan_Result {
		$result = new Scan_Result();

		$this->scan_php( $result );
		$this->scan_json( $result );

		return $result;
	}

	/**
	 * Recursively find PHP files inside the theme directories and parse them.
	 *
	 * @since 2.0.0
	 * @param Scan_Result $result Result to populate.
	 * @return void
	 */
	private function scan_php( Scan_Result $result ): void {
		$dirs = $this->security->get_allowed_theme_dirs();

		foreach ( $dirs as $dir ) {
			$dir = (string) $dir;
			if ( ! is_dir( $dir ) ) {
				continue;
			}

			$files = $this->list_php_files( $dir );
			foreach ( $files as $file ) {
				if ( ! $this->security->validate_path( $file, array( $dir ) ) ) {
					continue;
				}

				$parsed = $this->parser->parse_file( $file );
				foreach ( $parsed->get_errors() as $error ) {
					$code = $error->get_code();
					if ( 'syntax_error' === $code || 'unreadable_file' === $code ) {
						$result->add_error( $error->get_message() );
					} else {
						// Dynamic or otherwise non-literal groups are skipped, not
						// treated as hard failures of the scan.
						$result->add_notice( $error->get_message() );
					}
				}

				foreach ( $parsed->get_field_groups() as $group ) {
					$result->add_group( $group, 'php', $file );
				}
			}
		}
	}

	/**
	 * Read ACF Local JSON files from each theme's acf-json directory.
	 *
	 * @since 2.0.0
	 * @param Scan_Result $result Result to populate.
	 * @return void
	 */
	private function scan_json( Scan_Result $result ): void {
		$dirs = $this->security->get_allowed_theme_dirs();

		foreach ( $dirs as $dir ) {
			$dir      = (string) $dir;
			$json_dir = rtrim( $dir, DIRECTORY_SEPARATOR ) . DIRECTORY_SEPARATOR . 'acf-json';
			if ( ! is_dir( $json_dir ) ) {
				continue;
			}

			$json_files = glob( $json_dir . DIRECTORY_SEPARATOR . '*.json' );
			$files      = false === $json_files ? array() : $json_files;
			foreach ( $files as $file ) {
				$group = $this->read_json_file( $file );
				if ( null !== $group ) {
					$result->add_group( $group, 'json', $file );
				}
			}
		}
	}

	/**
	 * Decode a single ACF Local JSON file into a field group array.
	 *
	 * @since 2.0.0
	 * @param string $file Path to the JSON file.
	 * @return array|null
	 */
	private function read_json_file( string $file ): ?array {
		if ( ! is_file( $file ) || ! is_readable( $file ) ) {
			return null;
		}

		$contents = file_get_contents( $file );
		if ( false === $contents ) {
			return null;
		}

		$decoded = json_decode( $contents, true );
		if ( ! is_array( $decoded ) ) {
			return null;
		}

		return $decoded;
	}

	/**
	 * Recursively collect every .php file under a directory, skipping
	 * disallowed directories such as vendor and node_modules.
	 *
	 * @since 2.0.0
	 * @param string $dir Root directory.
	 * @return array<int,string>
	 */
	private function list_php_files( string $dir ): array {
		$out        = array();
		$disallowed = array( 'node_modules', 'vendor', 'bower_components', '.git', '.svn', '.idea', '.vscode' );
		$iterator   = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS )
		);

		foreach ( $iterator as $item ) {
			if ( ! $item->isFile() ) {
				continue;
			}
			if ( 'php' !== strtolower( $item->getExtension() ) ) {
				continue;
			}
			foreach ( $disallowed as $skip ) {
				if ( false !== strpos( $item->getPathname(), DIRECTORY_SEPARATOR . $skip . DIRECTORY_SEPARATOR ) ) {
					continue 2;
				}
			}
			$out[] = $item->getPathname();
		}

		return $out;
	}
}
