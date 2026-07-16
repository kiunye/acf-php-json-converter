<?php
/**
 * Tests for the Field_Group_Conversion_Service.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Tests
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Tests;

use Field_Group_PHP_JSON_Converter\Services\Field_Group_Conversion_Service;
use PHPUnit\Framework\TestCase;

/**
 * Validate the orchestration layer that the REST API and admin UI build on.
 *
 * @since 2.0.0
 */
class ConversionServiceTest extends TestCase {

	/**
	 * Scratch directory.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $work;

	/**
	 * Conversion service under test.
	 *
	 * @since 2.0.0
	 * @var Field_Group_Conversion_Service
	 */
	private Field_Group_Conversion_Service $service;

	/**
	 * Create an isolated scratch directory.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function setUp(): void {
		$this->work    = sys_get_temp_dir() . '/fgc-svc-' . uniqid();
		mkdir( $this->work, 0755, true );
		$this->service = new Field_Group_Conversion_Service();
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
			} elseif ( is_dir( $file ) ) {
				array_map( 'unlink', glob( $file . '/*' ) );
				rmdir( $file );
			}
		}
		rmdir( $this->work );
	}

	/**
	 * Pasted PHP becomes valid JSON output.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_php_source_to_json(): void {
		$php = "<?php\nacf_add_local_field_group( array( 'key' => 'group_a', 'title' => 'A', 'fields' => array(), 'location' => array(), 'menu_order' => 0, 'position' => 'normal', 'style' => 'default', 'label_placement' => 'top', 'instruction_placement' => 'label', 'hide_on_screen' => '', 'active' => true, 'description' => '' ) );\n";

		$result = $this->service->php_source_to_json( $php );

		$this->assertTrue( $result->is_success() );
		$decoded = json_decode( $result->get_output(), true );
		$this->assertSame( 'group_a', $decoded['key'] );
	}

	/**
	 * A dynamically built group cannot be parsed and yields an error.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_php_source_with_dynamic_group_reports_error(): void {
		$php = '<?php
$fields = array( array( "key" => "broken" ) );
acf_add_local_field_group( array( "key" => "broken", "fields" => $fields ) );';

		$result = $this->service->php_source_to_json( $php );

		$this->assertFalse( $result->is_success() );
		$this->assertNull( $result->get_output() );
		$this->assertNotEmpty( $result->get_errors() );
	}

	/**
	 * Pasted JSON becomes a PHP registration string.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_json_source_to_php(): void {
		$json = \Field_Group_PHP_JSON_Converter\Services\Field_Group_Conversion_Service::encode_json(
			array(
				'key'     => 'group_b',
				'title'   => 'B',
				'fields'  => array(),
				'location' => array(),
			)
		);

		$result = $this->service->json_source_to_php( $json );

		$this->assertTrue( $result->is_success() );
		$this->assertStringContainsString( 'acf_add_local_field_group(', $result->get_output() );
	}

	/**
	 * Exporting a PHP file writes one JSON file per group and backs up any
	 * pre-existing target.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_export_php_file_writes_json(): void {
		$php_path  = $this->work . '/groups.php';
		$json_dir  = $this->work . '/json';
		file_put_contents(
			$php_path,
			"<?php\nacf_add_local_field_group( array( 'key' => 'group_c', 'title' => 'C', 'fields' => array(), 'location' => array(), 'menu_order' => 0, 'position' => 'normal', 'style' => 'default', 'label_placement' => 'top', 'instruction_placement' => 'label', 'hide_on_screen' => '', 'active' => true, 'description' => '' ) );\n"
		);

		// Pre-create a stale target so a backup is expected.
		mkdir( $json_dir, 0755, true );
		file_put_contents( $json_dir . '/group_c.json', 'STALE' );

		$result = $this->service->export_php_file( $php_path, $json_dir );

		$this->assertTrue( $result->is_success() );
		$this->assertCount( 1, $result->get_written_paths() );

		$written = $result->get_written_paths()[0];
		$this->assertFileExists( $written );
		$this->assertJson( (string) file_get_contents( $written ) );

		$backups = glob( $json_dir . '/group_c.json.bak-*' );
		$this->assertCount( 1, $backups );
		$this->assertStringEqualsFile( $backups[0], 'STALE' );
	}

	/**
	 * Importing a JSON file writes a PHP registration file.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_import_json_file_writes_php(): void {
		$json_path = $this->work . '/group_d.json';
		$php_path  = $this->work . '/group_d.php';
		file_put_contents(
			$json_path,
			\Field_Group_PHP_JSON_Converter\Services\Field_Group_Conversion_Service::encode_json(
				array(
					'key'      => 'group_d',
					'title'    => 'D',
					'fields'   => array(),
					'location' => array(),
				)
			)
		);

		$result = $this->service->import_json_file( $json_path, $php_path );

		$this->assertTrue( $result->is_success() );
		$this->assertFileExists( $php_path );
		$this->assertStringContainsString( 'acf_add_local_field_group(', (string) file_get_contents( $php_path ) );
	}

	/**
	 * The service can encode values to pretty JSON.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_json_encode_helper_exists(): void {
		$this->assertIsString( \Field_Group_PHP_JSON_Converter\Services\Field_Group_Conversion_Service::encode_json( array( 'a' => 1 ) ) );
	}
}
