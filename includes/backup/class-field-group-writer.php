<?php
/**
 * Write converted field groups to disk, backing up any existing file first.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Backup
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Backup;

use Field_Group_PHP_JSON_Converter\Converters\JSON_To_PHP_Converter;
use Field_Group_PHP_JSON_Converter\Converters\PHP_To_JSON_Converter;

/**
 * Persist a field group to a JSON or PHP file, creating a timestamped backup
 * of any file it is about to overwrite.
 *
 * @since 2.0.0
 */
class Field_Group_Writer {

	/**
	 * Backup coordinator.
	 *
	 * @since 2.0.0
	 * @var Backup_Manager
	 */
	private Backup_Manager $backups;

	/**
	 * PHP array to JSON serializer.
	 *
	 * @since 2.0.0
	 * @var PHP_To_JSON_Converter
	 */
	private PHP_To_JSON_Converter $to_json;

	/**
	 * JSON array to PHP serializer.
	 *
	 * @since 2.0.0
	 * @var JSON_To_PHP_Converter
	 */
	private JSON_To_PHP_Converter $to_php;

	/**
	 * Set up the writer with its collaborators.
	 *
	 * @since 2.0.0
	 * @param Backup_Manager|null        $backups Backup coordinator.
	 * @param PHP_To_JSON_Converter|null $to_json PHP to JSON converter.
	 * @param JSON_To_PHP_Converter|null $to_php  JSON to PHP converter.
	 */
	public function __construct(
		?Backup_Manager $backups = null,
		?PHP_To_JSON_Converter $to_json = null,
		?JSON_To_PHP_Converter $to_php = null
	) {
		$this->backups = $backups ?? new Backup_Manager();
		$this->to_json = $to_json ?? new PHP_To_JSON_Converter();
		$this->to_php  = $to_php ?? new JSON_To_PHP_Converter();
	}

	/**
	 * Serialize a group to ACF JSON and write it to disk.
	 *
	 * @since 2.0.0
	 * @param string $path  Destination file path.
	 * @param array  $group Field group definition.
	 * @return bool True on success.
	 */
	public function write_json_file( string $path, array $group ): bool {
		return $this->write( $path, $this->to_json->convert( $group ) );
	}

	/**
	 * Serialize a group to a PHP registration call and write it to disk.
	 *
	 * @since 2.0.0
	 * @param string $path  Destination file path.
	 * @param array  $group Field group definition.
	 * @return bool True on success.
	 */
	public function write_php_file( string $path, array $group ): bool {
		return $this->write( $path, $this->to_php->convert( $group ) );
	}

	/**
	 * Back up any existing file at the destination, then write the contents.
	 *
	 * @since 2.0.0
	 * @param string $path     Destination file path.
	 * @param string $contents File contents to write.
	 * @return bool True on success.
	 */
	public function write( string $path, string $contents ): bool {
		$this->backups->backup( $path );

		$dir = dirname( $path );
		if ( ! is_dir( $dir ) ) {
			if ( ! mkdir( $dir, 0755, true ) && ! is_dir( $dir ) ) {
				return false;
			}
		}

		return false !== file_put_contents( $path, $contents );
	}
}
