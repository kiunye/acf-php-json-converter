<?php
/**
 * Tests for the Backup_Manager.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Tests
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Tests;

use Field_Group_PHP_JSON_Converter\Backup\Backup_Manager;
use PHPUnit\Framework\TestCase;

/**
 * Validate timestamped backup creation, listing, and pruning.
 *
 * @since 2.0.0
 */
class BackupManagerTest extends TestCase {

	/**
	 * Scratch directory for backups.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $work;

	/**
	 * Backup coordinator under test.
	 *
	 * @since 2.0.0
	 * @var Backup_Manager
	 */
	private Backup_Manager $manager;

	/**
	 * Create an isolated scratch directory.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function setUp(): void {
		$this->work    = sys_get_temp_dir() . '/fgc-backup-' . uniqid();
		mkdir( $this->work, 0755, true );
		$this->manager = new Backup_Manager();
	}

	/**
	 * Remove the scratch directory.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function tearDown(): void {
		$files = glob( $this->work . '/*' );
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				unlink( $file );
			}
		}
		rmdir( $this->work );
	}

	/**
	 * Backup path is colocated and carries a .bak- timestamp suffix.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_get_backup_path_format(): void {
		$target = $this->work . '/group.json';
		$path   = $this->manager->get_backup_path( $target );

		$this->assertStringStartsWith( $this->work . DIRECTORY_SEPARATOR, $path );
		$this->assertStringContainsString( 'group.json.bak-', $path );
	}

	/**
	 * Backing up an existing file copies its contents and leaves it intact.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_backup_copies_existing_file(): void {
		$target = $this->work . '/group.json';
		file_put_contents( $target, 'original' );

		$backup = $this->manager->backup( $target );

		$this->assertNotSame( '', $backup );
		$this->assertFileExists( $backup );
		$this->assertStringEqualsFile( $backup, 'original' );
		$this->assertStringEqualsFile( $target, 'original' );
	}

	/**
	 * Backing up a missing file returns an empty string and creates nothing.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_backup_of_missing_file_returns_empty(): void {
		$target = $this->work . '/nope.json';

		$this->assertSame( '', $this->manager->backup( $target ) );
		$this->assertSame( array(), $this->manager->list_backups( $target ) );
	}

	/**
	 * Stale backups are listed oldest first and pruned to the keep count.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_list_and_prune_backups(): void {
		$target = $this->work . '/g.json';
		file_put_contents( $target, 'x' );

		for ( $i = 0; $i < 7; $i++ ) {
			copy( $target, $this->work . '/g.json.bak-' . str_pad( (string) ( 1000 + $i ), 6, '0', STR_PAD_LEFT ) );
		}

		$list = $this->manager->list_backups( $target );
		$this->assertCount( 7, $list );

		$removed = $this->manager->prune_backups( $target, 3 );
		$this->assertSame( 4, $removed );
		$this->assertCount( 3, $this->manager->list_backups( $target ) );
	}
}
