<?php
/**
 * Tests for the PHP <-> JSON field group converters.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Tests
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Tests;

use Field_Group_PHP_JSON_Converter\Converters\JSON_To_PHP_Converter;
use Field_Group_PHP_JSON_Converter\Converters\PHP_To_JSON_Converter;
use Field_Group_PHP_JSON_Converter\Parsers\Field_Group_Parser;
use PHPUnit\Framework\TestCase;

/**
 * Validate that parsed field groups serialize to ACF-compatible JSON and that
 * ACF JSON serializes back to valid, re-parseable PHP registration code, with
 * no loss of structure across a full round trip.
 *
 * @since 2.0.0
 */
class FieldGroupConverterTest extends TestCase {

	/**
	 * Fixture directory for field group PHP files.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $php_fixtures;

	/**
	 * Fixture directory for JSON files.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $json_fixtures;

	/**
	 * Set up fixture paths.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->php_fixtures  = __DIR__ . '/../../fixtures/field-groups/';
		$this->json_fixtures = __DIR__ . '/../../fixtures/json/';
	}

	/**
	 * Parse a PHP fixture into its first field group.
	 *
	 * @since 2.0.0
	 * @param string $file Fixture file name.
	 * @return array
	 */
	private function parse_first_group( string $file ): array {
		$result = ( new Field_Group_Parser() )->parse_file( $this->php_fixtures . $file );
		$this->assertTrue( $result->is_success(), 'Fixture should parse without errors.' );

		$groups = $result->get_field_groups();
		return $groups[0];
	}

	/**
	 * PHP field groups serialize to pretty-printed, 4-space-indented JSON.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_php_to_json_uses_acf_format(): void {
		$group = $this->parse_first_group( 'simple.php' );
		$json  = ( new PHP_To_JSON_Converter() )->convert( $group );

		$decoded = json_decode( $json, true );
		$this->assertIsArray( $decoded );
		$this->assertSame( 'group_simple', $decoded['key'] );
		$this->assertSame( 'text', $decoded['fields'][0]['name'] );

		// ACF local JSON uses 4 space indentation and a trailing newline.
		$this->assertStringContainsString( "\n    \"key\": \"group_simple\"", $json );
		$this->assertStringEndsWith( "\n", $json );

		// Canonical key order: "key" precedes "title" precedes "fields".
		$this->assertLessThan(
			strpos( $json, '"title"' ),
			strpos( $json, '"key"' )
		);
		$this->assertLessThan(
			strpos( $json, '"fields"' ),
			strpos( $json, '"title"' )
		);
	}

	/**
	 * ACF JSON with metadata converts to a PHP registration call, stripping
	 * JSON-only keys, and re-parses back to the same group.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_json_to_php_emits_registration_and_strips_meta(): void {
		$raw     = json_decode( file_get_contents( $this->json_fixtures . 'group-with-meta.json' ), true );
		$php     = ( new JSON_To_PHP_Converter() )->convert( $raw );

		$this->assertStringContainsString( 'acf_add_local_field_group(', $php );
		$this->assertStringContainsString( "\tarray(", $php );
		$this->assertStringContainsString( "'active' => true,", $php );
		// "modified" is JSON metadata and must not appear in PHP.
		$this->assertStringNotContainsString( 'modified', $php );

		$result  = ( new Field_Group_Parser() )->parse_source( $php );
		$this->assertTrue( $result->is_success() );

		$reparsed = $result->get_field_groups()[0];
		unset( $raw['modified'] );
		$this->assertEquals( $raw, $reparsed );
	}

	/**
	 * A nested repeater survives a full PHP -> JSON -> PHP round trip.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_round_trip_preserves_nested_structure(): void {
		$original = $this->parse_first_group( 'nested-repeater.php' );

		$json     = ( new PHP_To_JSON_Converter() )->convert( $original );
		$decoded  = json_decode( $json, true );
		$php      = ( new JSON_To_PHP_Converter() )->convert( $decoded );

		$result   = ( new Field_Group_Parser() )->parse_source( $php );
		$this->assertTrue( $result->is_success() );

		$restored = $result->get_field_groups()[0];
		$this->assertEquals( $original, $restored );
	}

	/**
	 * A sequential list of groups yields one registration call per group.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_convert_multiple_groups(): void {
		$list = array(
			$this->parse_first_group( 'simple.php' ),
			$this->parse_first_group( 'clone-field.php' ),
		);

		$php = ( new JSON_To_PHP_Converter() )->convert( $list );

		$this->assertSame( 2, substr_count( $php, 'acf_add_local_field_group(' ) );

		$result = ( new Field_Group_Parser() )->parse_source( $php );
		$this->assertTrue( $result->is_success() );
		$this->assertSame( 2, $result->get_field_group_count() );
	}
}
