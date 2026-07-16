<?php
/**
 * Tests for the Theme_Scanner.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Tests
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Tests;

use Field_Group_PHP_JSON_Converter\Theme\Theme_Scanner;
use PHPUnit\Framework\TestCase;

/**
 * Confirm the scanner finds PHP-registered and Local JSON field groups in the
 * active theme directories.
 *
 * @since 2.0.0
 */
class ThemeScannerTest extends TestCase {

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
		$this->theme = sys_get_temp_dir() . '/fgc-theme-' . uniqid();
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
	 * The scanner should discover both a PHP and a Local JSON group.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_scans_php_and_json_groups(): void {
		$scanner = new Theme_Scanner();
		$result  = $scanner->scan();

		$this->assertTrue( $result->has_groups() );
		$this->assertSame( 2, $result->get_group_count() );

		$sources = array_column( array_map( 'array_values', $result->get_groups() ), null );
		$source_values = array();
		foreach ( $result->get_groups() as $item ) {
			$source_values[] = $item['source'];
		}
		sort( $source_values );

		$this->assertSame( array( 'json', 'php' ), $source_values );
	}

	/**
	 * Each discovered group records its origin file.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_groups_carry_origin_file(): void {
		$scanner = new Theme_Scanner();
		$result  = $scanner->scan();

		$found_php = false;
		foreach ( $result->get_groups() as $item ) {
			if ( 'php' === $item['source'] ) {
				$this->assertStringContainsString( 'fields.php', $item['file'] );
				$found_php = true;
			}
		}

		$this->assertTrue( $found_php );
	}
}
