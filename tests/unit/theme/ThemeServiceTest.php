<?php
/**
 * Tests for the Theme_Service.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Tests
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Tests;

use Field_Group_PHP_JSON_Converter\Theme\Theme_Service;
use PHPUnit\Framework\TestCase;

/**
 * Validate that discovered groups can be written to Local JSON (PHP -> JSON)
 * and turned into functions.php registration code (JSON -> PHP).
 *
 * @since 2.0.0
 */
class ThemeServiceTest extends TestCase {

	/**
	 * Scratch theme directory.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $theme;

	/**
	 * Build a fake theme with one PHP group and one Local JSON group.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function setUp(): void {
		$this->theme = sys_get_temp_dir() . '/fgc-theme-svc-' . uniqid();
		mkdir( $this->theme . '/acf-json', 0755, true );

		$php = "<?php\nacf_add_local_field_group( array(\n\t'key' => 'group_php',\n\t'title' => 'From PHP',\n) );\n";
		file_put_contents( $this->theme . '/fields.php', $php );

		$json = json_encode( array( 'key' => 'group_json', 'title' => 'From JSON' ) );
		file_put_contents( $this->theme . '/acf-json/group_json.json', $json );

		$GLOBALS['fgc_theme_dirs'] = array( $this->theme );
	}

	/**
	 * Remove the scratch theme.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function tearDown(): void {
		$paths = array(
			$this->theme . '/fields.php',
			$this->theme . '/acf-json/group_json.json',
			$this->theme . '/acf-json/group_php.json',
			$this->theme . '/acf-json',
			$this->theme,
		);
		foreach ( $paths as $path ) {
			if ( is_file( $path ) ) {
				unlink( $path );
			} elseif ( is_dir( $path ) ) {
				rmdir( $path );
			}
		}
	}

	/**
	 * PHP groups should be written into the theme's acf-json folder.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_php_groups_export_to_local_json(): void {
		$service = new Theme_Service();
		$result  = $service->export_php_groups_to_local_json();

		$expected = $this->theme . DIRECTORY_SEPARATOR . 'acf-json' . DIRECTORY_SEPARATOR . 'group_php.json';

		$this->assertTrue( $result->is_success() );
		$this->assertContains( $expected, $result->get_written_paths() );
		$this->assertFileExists( $expected );

		$decoded = json_decode( (string) file_get_contents( $this->theme . '/acf-json/group_php.json' ), true );
		$this->assertSame( 'group_php', $decoded['key'] );
	}

	/**
	 * Local JSON groups should become functions.php registration code.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_json_groups_export_to_php_code(): void {
		$service = new Theme_Service();
		$result  = $service->convert_json_groups_to_php_code();

		$this->assertTrue( $result->is_success() );
		$this->assertStringContainsString( 'acf_add_local_field_group', $result->get_output() );
		$this->assertStringContainsString( 'group_json', $result->get_output() );
		$this->assertStringContainsString( '<?php', $result->get_output() );
	}
}
