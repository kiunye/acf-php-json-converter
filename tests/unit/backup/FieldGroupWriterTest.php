<?php
/**
 * Tests for the Field_Group_Writer (backup-before-write).
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Tests
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Tests;

use Field_Group_PHP_JSON_Converter\Backup\Field_Group_Writer;
use PHPUnit\Framework\TestCase;

/**
 * Validate that writing a converted group to disk backs up any existing file
 * first and produces re-parseable output.
 *
 * @since 2.0.0
 */
class FieldGroupWriterTest extends TestCase {

	/**
	 * Scratch directory for written files.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $work;

	/**
	 * Writer under test.
	 *
	 * @since 2.0.0
	 * @var Field_Group_Writer
	 */
	private Field_Group_Writer $writer;

	/**
	 * Sample field group.
	 *
	 * @since 2.0.0
	 * @var array
	 */
	private array $group;

	/**
	 * Create an isolated scratch directory and a sample group.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function setUp(): void {
		$this->work  = sys_get_temp_dir() . '/fgc-writer-' . uniqid();
		mkdir( $this->work, 0755, true );
		$this->writer = new Field_Group_Writer();
		$this->group  = array(
			'key'                   => 'group_x',
			'title'                => 'X',
			'fields'               => array(
				array(
					'key'  => 'f1',
					'name' => 'f1',
					'type' => 'text',
				),
			),
			'location'             => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'post',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
		);
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
	 * Writing a JSON file produces valid ACF JSON.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_write_json_creates_file(): void {
		$path = $this->work . '/group_x.json';

		$this->assertTrue( $this->writer->write_json_file( $path, $this->group ) );
		$this->assertFileExists( $path );

		$decoded = json_decode( file_get_contents( $path ), true );
		$this->assertIsArray( $decoded );
		$this->assertSame( 'group_x', $decoded['key'] );
	}

	/**
	 * Writing a PHP file produces a registrable PHP call.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_write_php_creates_file(): void {
		$path = $this->work . '/group_x.php';

		$this->assertTrue( $this->writer->write_php_file( $path, $this->group ) );
		$this->assertFileExists( $path );
		$this->assertStringContainsString( 'acf_add_local_field_group(', file_get_contents( $path ) );
	}

	/**
	 * Overwriting an existing file keeps a backup of the previous contents.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_write_backs_up_existing_file(): void {
		$path = $this->work . '/group_x.json';
		file_put_contents( $path, 'OLD' );

		$this->assertTrue( $this->writer->write_json_file( $path, $this->group ) );

		// New content written.
		$this->assertStringContainsString( 'group_x', file_get_contents( $path ) );

		// Exactly one backup holding the original contents.
		$backups = glob( $this->work . '/group_x.json.bak-*' );
		$this->assertCount( 1, $backups );
		$this->assertStringEqualsFile( $backups[0], 'OLD' );
	}
}
