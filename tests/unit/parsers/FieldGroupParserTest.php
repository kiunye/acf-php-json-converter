<?php
/**
 * Tests for the Field_Group_Parser tokenizer based extractor.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Tests
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Tests;

use Field_Group_PHP_JSON_Converter\Parsers\Field_Group_Parser;
use PHPUnit\Framework\TestCase;

/**
 * Validate that field groups registered via acf_add_local_field_group() /
 * acf_add_local_field_groups() are extracted correctly, and that dynamic
 * or syntactically broken definitions are reported as clear errors rather
 * than producing silent partial output.
 *
 * @since 2.0.0
 */
class FieldGroupParserTest extends TestCase {

	/**
	 * Fixture directory for field group PHP files.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	private string $fixtures;

	/**
	 * Set up the parser and fixture path.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->fixtures = __DIR__ . '/../../fixtures/field-groups/';
	}

	/**
	 * A plain single-group file yields exactly one field group with its key.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_extracts_single_field_group(): void {
		$result = ( new Field_Group_Parser() )->parse_file( $this->fixtures . 'simple.php' );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 1, $result->get_field_group_count() );

		$groups = $result->get_field_groups();
		$this->assertSame( 'group_simple', $groups[0]['key'] );
		$this->assertSame( 'Simple Group', $groups[0]['title'] );
		$this->assertCount( 1, $groups[0]['fields'] );
	}

	/**
	 * Nested repeaters and nested groups must be preserved structurally.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_extracts_nested_repeater(): void {
		$result = ( new Field_Group_Parser() )->parse_file( $this->fixtures . 'nested-repeater.php' );

		$this->assertTrue( $result->is_success() );

		$groups  = $result->get_field_groups();
		$repeater = $groups[0]['fields'][0];
		$this->assertSame( 'repeater', $repeater['type'] );
		$this->assertCount( 2, $repeater['sub_fields'] );

		$nested = $repeater['sub_fields'][0];
		$this->assertSame( 'repeater', $nested['type'] );
		$this->assertCount( 1, $nested['sub_fields'] );
		$this->assertSame( 'inner_text', $nested['sub_fields'][0]['name'] );
	}

	/**
	 * Flexible content layouts with nested sub fields are preserved.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_extracts_flexible_content_layouts(): void {
		$result = ( new Field_Group_Parser() )->parse_file( $this->fixtures . 'flexible-content.php' );

		$this->assertTrue( $result->is_success() );

		$groups     = $result->get_field_groups();
		$flex_field = $groups[0]['fields'][0];
		$this->assertSame( 'flexible_content', $flex_field['type'] );
		$this->assertCount( 2, $flex_field['layouts'] );
		$this->assertSame( 'text_block', $flex_field['layouts'][0]['name'] );
		$this->assertCount( 2, $flex_field['layouts'][0]['sub_fields'] );
	}

	/**
	 * Clone fields with prefix and display settings are preserved.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_extracts_clone_field(): void {
		$result = ( new Field_Group_Parser() )->parse_file( $this->fixtures . 'clone-field.php' );

		$this->assertTrue( $result->is_success() );

		$groups = $result->get_field_groups();
		$clone  = $groups[0]['fields'][0];
		$this->assertSame( 'clone', $clone['type'] );
		$this->assertTrue( $clone['prefix_name'] );
		$this->assertSame( 'group', $clone['display'] );
	}

	/**
	 * A file containing two acf_add_local_field_group() calls yields two groups.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_extracts_multiple_groups(): void {
		$result = ( new Field_Group_Parser() )->parse_file( $this->fixtures . 'multiple-groups.php' );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 2, $result->get_field_group_count() );

		$keys = array_column( $result->get_field_groups(), 'key' );
		$this->assertContains( 'group_one', $keys );
		$this->assertContains( 'group_two', $keys );
	}

	/**
	 * A group built from a variable cannot be parsed statically and must be
	 * skipped with a clear error instead of silently truncated.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_skips_dynamic_group_with_error(): void {
		$result = ( new Field_Group_Parser() )->parse_file( $this->fixtures . 'dynamic-group.php' );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_field_group_count() );
		$this->assertSame( 1, $result->get_error_count() );
		$this->assertSame( 'dynamic_group', $result->get_errors()[0]->get_code() );
	}

	/**
	 * array_merge() of literal arrays is folded into a single resolvable group.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_resolves_array_merge_of_literals(): void {
		$result = ( new Field_Group_Parser() )->parse_file( $this->fixtures . 'array-merge.php' );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 1, $result->get_field_group_count() );

		$group = $result->get_field_groups()[0];
		$this->assertSame( 'group_merged', $group['key'] );
		$this->assertSame( 'Merged Group', $group['title'] );
		$this->assertArrayHasKey( 'location', $group );
		$this->assertTrue( $group['active'] );
	}

	/**
	 * Nested array_merge() calls are resolved recursively.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_resolves_nested_array_merge(): void {
		$result = ( new Field_Group_Parser() )->parse_file( $this->fixtures . 'array-merge-nested.php' );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 1, $result->get_field_group_count() );

		$group = $result->get_field_groups()[0];
		$this->assertSame( 'group_nested', $group['key'] );
		$this->assertSame( 'Nested', $group['title'] );
	}

	/**
	 * array_merge() containing a non-literal (variable) argument cannot be
	 * resolved and is reported as a dynamic group.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_array_merge_with_variable_is_dynamic(): void {
		$source = "<?php\n"
			. "\$base = array( 'key' => 'group_dyn' );\n"
			. "acf_add_local_field_group( array_merge( \$base, array( 'title' => 'T' ) ) );\n";
		$result = ( new Field_Group_Parser() )->parse_source( $source );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_field_group_count() );
		$this->assertSame( 'dynamic_group', $result->get_errors()[0]->get_code() );
	}

	/**
	 * A syntactically broken file must surface a syntax error, never a partial group.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_reports_syntax_error(): void {
		$result = ( new Field_Group_Parser() )->parse_file( $this->fixtures . 'syntax-error.php' );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 0, $result->get_field_group_count() );

		$codes = array_map(
			static function ( $error ) {
				return $error->get_code();
			},
			$result->get_errors()
		);
		$this->assertContains( 'syntax_error', $codes );
	}

	/**
	 * A non-existent file is reported as an error, not a fatal.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_missing_file_reports_error(): void {
		$result = ( new Field_Group_Parser() )->parse_file( $this->fixtures . 'does-not-exist.php' );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 1, $result->get_error_count() );
		$this->assertSame( 'unreadable_file', $result->get_errors()[0]->get_code() );
	}
}
