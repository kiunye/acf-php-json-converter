<?php
/**
 * Tests for the admin UI controller.
 *
 * WordPress primitives are provided by the test bootstrap stubs so the admin
 * handlers can be exercised without a full WordPress installation.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Tests
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Tests;

use Field_Group_PHP_JSON_Converter\Admin\Admin;
use Field_Group_PHP_JSON_Converter\Services\Field_Group_Conversion_Service;
use PHPUnit\Framework\TestCase;

/**
 * Validate admin menu registration and conversion handlers.
 *
 * @since 2.0.0
 */
class AdminTest extends TestCase {

	/**
	 * Reset recorded globals between tests.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['fgc_actions']          = array();
		$GLOBALS['fgc_menu_pages']       = array();
		$GLOBALS['fgc_transients']       = array();
		$GLOBALS['fgc_valid_nonce']      = true;
		$GLOBALS['fgc_json_response']    = null;
		$GLOBALS['fgc_acf_groups']       = array();
		$GLOBALS['fgc_acf_active']       = false;
		$_POST                           = array();
	}

	/**
	 * Registering hooks wires the menu and the handlers.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_register_hooks(): void {
		( new Admin() )->register_hooks();

		$this->assertArrayHasKey( 'admin_menu', $GLOBALS['fgc_actions'] );
		$this->assertArrayHasKey( 'admin_post_fgpjc_convert', $GLOBALS['fgc_actions'] );
		$this->assertArrayHasKey( 'wp_ajax_fgpjc_convert', $GLOBALS['fgc_actions'] );
		$this->assertArrayHasKey( 'wp_ajax_fgpjc_bulk_export', $GLOBALS['fgc_actions'] );
	}

	/**
	 * The converter menu page is registered with the manage_options capability.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_menu_registered(): void {
		( new Admin() )->register_menu();

		$this->assertArrayHasKey( Admin::MENU_SLUG, $GLOBALS['fgc_menu_pages'] );
		$page = $GLOBALS['fgc_menu_pages'][ Admin::MENU_SLUG ];
		$this->assertSame( 'manage_options', $page['capability'] );
		$this->assertIsCallable( $page['callback'] );
	}

	/**
	 * The AJAX PHP-to-JSON handler returns a successful conversion payload.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_ajax_php_to_json(): void {
		$_POST['_wpnonce'] = 'nonce';
		$_POST['mode']     = 'php_to_json';
		$_POST['source']   = '<?php acf_add_local_field_group( array( "key" => "group_a", "title" => "A", "fields" => array(), "location" => array(), "menu_order" => 0, "position" => "normal", "style" => "default", "label_placement" => "top", "instruction_placement" => "label", "hide_on_screen" => "", "active" => true, "description" => "" ) );';

		$response = ( new Admin( new Field_Group_Conversion_Service() ) )->handle_ajax_convert();

		$this->assertTrue( $response['success'] );
		$this->assertArrayHasKey( 'key', json_decode( $response['data']['output'], true ) );
	}

	/**
	 * An invalid nonce fails the AJAX request.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_ajax_invalid_nonce(): void {
		$GLOBALS['fgc_valid_nonce'] = false;

		$response = ( new Admin() )->handle_ajax_convert();

		$this->assertFalse( $response['success'] );
	}

	/**
	 * The admin-post handler stores the conversion result in a transient.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_post_convert_stores_transient(): void {
		$_POST['_wpnonce'] = 'nonce';
		$_POST['mode']     = 'json_to_php';
		$_POST['source']   = '{"key":"group_a","title":"A","fields":[],"location":[],"menu_order":0,"position":"normal","style":"default","label_placement":"top","instruction_placement":"label","hide_on_screen":"","active":true,"description":""}';

		( new Admin( new Field_Group_Conversion_Service() ) )->handle_post_convert();

		$this->assertArrayHasKey( 'fgpjc_convert_result', $GLOBALS['fgc_transients'] );
		$stored = $GLOBALS['fgc_transients']['fgpjc_convert_result'];
		$this->assertStringContainsString( 'acf_add_local_field_group', $stored['output'] );
		$this->assertSame( 'json_to_php', $stored['mode'] );
	}

	/**
	 * The bulk export handler reports when ACF is unavailable.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_bulk_export_requires_acf(): void {
		$GLOBALS['fgc_acf_active'] = false;
		$_POST['_wpnonce']         = 'nonce';

		$response = ( new Admin() )->handle_bulk_export();

		$this->assertFalse( $response['success'] );
	}

	/**
	 * The bulk export handler returns every available ACF field group.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_bulk_export_returns_groups(): void {
		$GLOBALS['fgc_acf_active'] = true;
		$GLOBALS['fgc_acf_groups'] = array(
			array( 'key' => 'group_one', 'title' => 'One' ),
		);
		$_POST['_wpnonce']         = 'nonce';

		$response = ( new Admin() )->handle_bulk_export();

		$this->assertTrue( $response['success'] );
		$this->assertSame( 1, $response['data']['count'] );
		$this->assertStringContainsString( 'group_one', $response['data']['json'] );
	}

	/**
	 * The rendered page exposes aria-live regions for assistive technology.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_render_page_includes_aria_live_regions(): void {
		$GLOBALS['fgc_acf_active'] = true;
		$GLOBALS['fgc_acf_groups']  = array( array( 'key' => 'group_one' ) );
		$GLOBALS['fgc_transients']  = array();

		$admin = new Admin( new Field_Group_Conversion_Service() );
		ob_start();
		$admin->render_page();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'aria-live="polite"', $html );
		$this->assertStringContainsString( 'id="fgpjc-convert-status"', $html );
		$this->assertStringContainsString( 'id="fgpjc-export-status"', $html );
		$this->assertStringContainsString( 'id="fgpjc-import-file"', $html );
	}

	/**
	 * The issues handler aggregates environment, snippet, theme and DB checks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_ajax_issues_aggregates_results(): void {
		$_POST['_wpnonce'] = 'nonce';
		$_POST['source']   = '<?php acf_add_local_field_group( array( "title" => "No Key" ) );';
		$_POST['format']   = 'php';

		$dir = sys_get_temp_dir() . '/fgpjc-issues-' . uniqid();
		mkdir( $dir, 0777, true );
		file_put_contents( $dir . '/functions.php', '<?php acf_add_local_field_group( array( "title" => "Missing Key" ) );' );
		$GLOBALS['fgc_theme_dirs'] = array( $dir );

		( new Admin( new Field_Group_Conversion_Service() ) )->handle_ajax_issues();

		$response = $GLOBALS['fgc_json_response'];
		$this->assertTrue( $response['success'] );
		$this->assertArrayHasKey( 'issues', $response['data'] );
		$this->assertGreaterThan( 0, $response['data']['errors'] );

		array_map( 'unlink', glob( $dir . '/*' ) );
		rmdir( $dir );
	}
}
