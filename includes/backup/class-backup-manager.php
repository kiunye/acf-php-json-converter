<?php
/**
 * Create timestamped backups of files before they are overwritten.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Backup
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Backup;

/**
 * Safely back up a file prior to writing, so conversions can be reverted if
 * a write goes wrong or the operator changes their mind.
 *
 * @since 2.0.0
 */
class Backup_Manager {

	/**
	 * Compute the path a backup of the given target would be written to.
	 *
	 * Backups are colocated with the target and named
	 * "{basename}.bak-{YYYYMMDDHHMMSS}" with a numeric suffix when more than
	 * one backup is created within the same second.
	 *
	 * @since 2.0.0
	 * @param string $target File that is about to be written.
	 * @return string
	 */
	public function get_backup_path( string $target ): string {
		$dir   = dirname( $target );
		$base  = basename( $target );
		$stamp = $this->timestamp();
		$path  = $dir . DIRECTORY_SEPARATOR . $base . '.bak-' . $stamp;

		$suffix = 1;
		while ( file_exists( $path ) ) {
			$path = $dir . DIRECTORY_SEPARATOR . $base . '.bak-' . $stamp . '-' . $suffix;
			++$suffix;
		}

		return $path;
	}

	/**
	 * Back up an existing, readable file.
	 *
	 * @since 2.0.0
	 * @param string $target File about to be overwritten.
	 * @return string Backup path on success, or empty string when there is
	 *                nothing to back up (file absent) or the copy failed.
	 */
	public function backup( string $target ): string {
		if ( ! is_file( $target ) || ! is_readable( $target ) ) {
			return '';
		}

		$backup = $this->get_backup_path( $target );
		if ( false === copy( $target, $backup ) ) {
			return '';
		}

		return $backup;
	}

	/**
	 * List backups that exist for a given target, oldest first.
	 *
	 * @since 2.0.0
	 * @param string $target Original (pre-write) file path.
	 * @return array<int,string>
	 */
	public function list_backups( string $target ): array {
		$dir  = dirname( $target );
		$base = basename( $target );
		$base = str_replace( array( '*', '?', '[', ']' ), array( '\*', '\?', '\[', '\]' ), $base );

		$files = glob( $dir . DIRECTORY_SEPARATOR . $base . '.bak-*' );
		if ( false === $files ) {
			return array();
		}

		sort( $files );

		return $files;
	}

	/**
	 * Remove the oldest backups for a target, keeping the most recent ones.
	 *
	 * @since 2.0.0
	 * @param string $target Original (pre-write) file path.
	 * @param int    $keep   Number of most recent backups to retain.
	 * @return int Number of backups removed.
	 */
	public function prune_backups( string $target, int $keep = 5 ): int {
		$backups = $this->list_backups( $target );
		$total   = count( $backups );

		if ( $total <= $keep ) {
			return 0;
		}

		$to_remove = array_slice( $backups, 0, $total - $keep );
		$removed   = 0;

		foreach ( $to_remove as $file ) {
			if ( is_file( $file ) && unlink( $file ) ) {
				++$removed;
			}
		}

		return $removed;
	}

	/**
	 * Current timestamp used for backup file names.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function timestamp(): string {
		return gmdate( 'YmdHis' );
	}
}
