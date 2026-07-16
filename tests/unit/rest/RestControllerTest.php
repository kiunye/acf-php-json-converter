<?php
/**
 * Tests for the REST conversion controller.
 *
 * WordPress primitives are provided by the test bootstrap stubs so the
 * controller can be exercised without a full WordPress installation.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Tests
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Tests;

use Field_Group_PHP_JSON_Converter\Rest\Rest_Controller;
use Field_Group_PHP_JSON_Converter\Services\Field_Group_Conversion_Service;
use PHPUnit\Framework\TestCase;

/**
 * Validate REST route registration and request handling.
 *
 * @since 2.0.0
 */
class RestControllerTest extends TestCase {

	/**
	 * Restore the default "allowed" state and clear recorded routes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['fgc_rest_routes']      = array();
		$GLOBALS['fgc_current_user_can'] = true;
	}

	/**
	 * Routes for php-to-json and json-to-php are registered.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_routes_registered(): void {
		( new Rest_Controller() )->register_routes();

		$this->assertContains( Rest_Controller::NAMESPACE . '/php-to-json', $GLOBALS['fgc_rest_routes'] );
		$this->assertContains( Rest_Controller::NAMESPACE . '/json-to-php', $GLOBALS['fgc_rest_routes'] );
	}

	/**
	 * A valid PHP source converts to JSON via the REST handler.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_php_to_json_endpoint(): void {
		$controller = new Rest_Controller( new Field_Group_Conversion_Service() );

		$request = new \WP_REST_Request();
		$request->set_param(
			'source',
			'<?php acf_add_local_field_group( array( "key" => "group_rest", "title" => "Rest", "fields" => array(), "location" => array(), "menu_order" => 0, "position" => "normal", "style" => "default", "label_placement" => "top", "instruction_placement" => "label", "hide_on_screen" => "", "active" => true, "description" => "" ) );'
		);

		$response = $controller->php_to_json( $request );

		$this->assertInstanceOf( \WP_REST_Response::class, $response );
		$data = $response->data;
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'key', json_decode( $data['output'], true ) );
	}

	/**
	 * The permission check blocks disallowed users.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_permission_blocks_disallowed(): void {
		$GLOBALS['fgc_current_user_can'] = false;
		$controller                     = new Rest_Controller();

		$check = $controller->check_permission();

		$this->assertInstanceOf( \WP_Error::class, $check );
		$this->assertSame( 401, $check->data['status'] );
	}

	/**
	 * A missing source parameter is rejected with a 400 error.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function test_missing_source_rejected(): void {
		$controller = new Rest_Controller( new Field_Group_Conversion_Service() );
		$request    = new \WP_REST_Request();

		$response = $controller->json_to_php( $request );

		$this->assertInstanceOf( \WP_Error::class, $response );
		$this->assertSame( 400, $response->data['status'] );
	}
}
