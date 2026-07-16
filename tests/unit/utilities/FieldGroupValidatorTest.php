<?php
/**
 * Tests for the field group validator.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Tests
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Tests;

use Field_Group_PHP_JSON_Converter\Utilities\Field_Group_Validator;
use Field_Group_PHP_JSON_Converter\Utilities\Validation_Result;
use PHPUnit\Framework\TestCase;

/**
 * Validate structural checks for malformed field groups.
 *
 * @since 2.0.0
 */
class FieldGroupValidatorTest extends TestCase {

	/**
	 * Validator instance.
	 *
	 * @since 2.0.0
	 * @var Field_Group_Validator
	 */
	private Field_Group_Validator $validator;

	/**
	 * Create the validator.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->validator = new Field_Group_Validator();
	}

	/**
	 * A well formed group produces no issues.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_valid_group_has_no_issues(): void {
		$groups = array(
			array(
				'key'    => 'group_ok',
				'title'  => 'OK',
				'fields' => array(
					array( 'key' => 'field_1', 'name' => 'one', 'type' => 'text' ),
				),
			),
		);

		$result = $this->validator->validate_scan_items(
			array( array( 'group' => $groups[0], 'source' => 'php', 'file' => 'x.php' ) )
		);

		$this->assertFalse( $result->has_errors() );
	}

	/**
	 * A group missing a key is reported as an error.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_missing_key_is_error(): void {
		$result = $this->validator->validate_scan_items(
			array( array( 'group' => array( 'title' => 'No Key' ), 'source' => 'php', 'file' => 'x.php' ) )
		);

		$this->assertTrue( $result->has_errors() );
		$this->assertStringContainsString( 'key', $result->get_issues()[0]['message'] );
	}

	/**
	 * Duplicate group keys across the set are flagged.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_duplicate_group_key_is_error(): void {
		$items = array(
			array( 'group' => array( 'key' => 'dup', 'title' => 'A' ), 'source' => 'php', 'file' => 'a.php' ),
			array( 'group' => array( 'key' => 'dup', 'title' => 'B' ), 'source' => 'php', 'file' => 'b.php' ),
		);

		$result = $this->validator->validate_scan_items( $items );

		$duplicates = 0;
		foreach ( $result->get_issues() as $issue ) {
			if ( 'error' === $issue['severity'] && str_contains( $issue['message'], 'Duplicate group key' ) ) {
				++$duplicates;
			}
		}
		$this->assertSame( 1, $duplicates );
	}

	/**
	 * A field missing a type is an error; an unknown type is a warning.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_field_problems_are_flagged(): void {
		$group = array(
			'key'    => 'group_fields',
			'title'  => 'Fields',
			'fields' => array(
				array( 'key' => 'field_no_type', 'name' => 'x' ),
				array( 'key' => 'field_weird', 'name' => 'y', 'type' => 'made_up_type' ),
			),
		);

		$result = $this->validator->validate_scan_items(
			array( array( 'group' => $group, 'source' => 'php', 'file' => 'x.php' ) )
		);

		$has_type_error  = false;
		$has_unknown_warn = false;
		foreach ( $result->get_issues() as $issue ) {
			if ( str_contains( $issue['message'], 'missing a "type"' ) ) {
				$has_type_error = true;
			}
			if ( str_contains( $issue['message'], 'unknown type' ) ) {
				$has_unknown_warn = true;
			}
		}
		$this->assertTrue( $has_type_error );
		$this->assertTrue( $has_unknown_warn );
	}

	/**
	 * A pasted JSON snippet is validated like a theme group.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_validate_json_source(): void {
		$json = '{"key":"group_json","title":"From JSON","fields":[{"key":"f1","name":"n","type":"text"}]}';

		$result = $this->validator->validate_source( $json, 'json' );

		$this->assertFalse( $result->has_errors() );
	}

	/**
	 * An invalid JSON snippet surfaces a decode error.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_invalid_json_source(): void {
		$result = $this->validator->validate_source( '{not valid json', 'json' );

		$this->assertTrue( $result->has_errors() );
	}

	/**
	 * The environment check reports ACF availability (stub dependent).
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_environment_reports_acf_status(): void {
		$GLOBALS['fgc_acf_active'] = true;

		$result = $this->validator->validate_environment();

		$this->assertFalse( $result->has_errors() );
		$this->assertStringContainsString( 'ready', $result->get_issues()[0]['message'] );
	}
}
