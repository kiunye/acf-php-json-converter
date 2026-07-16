<?php
/**
 * REST API controller for PHP <-> JSON field group conversions.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Rest
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Rest;

use Field_Group_PHP_JSON_Converter\Services\Field_Group_Conversion_Service;
use Field_Group_PHP_JSON_Converter\Theme\Theme_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Registers and handles REST endpoints that let headless clients convert field
 * groups between PHP and JSON without touching the admin UI.
 *
 * @since 2.0.0
 */
class Rest_Controller {

	/**
	 * REST namespace.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public const NAMESPACE = 'field-group-php-json-converter/v1';

	/**
	 * Conversion orchestration service.
	 *
	 * @since 2.0.0
	 * @var Field_Group_Conversion_Service
	 */
	private Field_Group_Conversion_Service $service;

	/**
	 * Theme scanning / conversion service.
	 *
	 * @since 2.0.0
	 * @var Theme_Service
	 */
	private Theme_Service $theme_service;

	/**
	 * Inject the conversion service.
	 *
	 * @since 2.0.0
	 * @param Field_Group_Conversion_Service|null $service       Conversion service.
	 * @param Theme_Service|null                  $theme_service Theme service.
	 */
	public function __construct(
		?Field_Group_Conversion_Service $service = null,
		?Theme_Service $theme_service = null
	) {
		$this->service       = $service ?? new Field_Group_Conversion_Service();
		$this->theme_service = $theme_service ?? new Theme_Service();
	}

	/**
	 * Register the conversion routes.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/php-to-json',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'php_to_json' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'source' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'wp_kses_post',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/json-to-php',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'json_to_php' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'source' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'wp_kses_post',
						),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/scan-theme',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'scan_theme' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/theme-to-json',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'theme_to_json' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/theme-to-php',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'theme_to_php' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
	}

	/**
	 * Permission check: require the "manage_options" capability (admins).
	 *
	 * @since 2.0.0
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'fgpjc_rest_forbidden',
				__( 'You are not allowed to perform conversions.', 'field-group-php-json-converter' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Convert pasted PHP source into ACF JSON.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function php_to_json( WP_REST_Request $request ) {
		$source = (string) $request->get_param( 'source' );

		if ( '' === trim( $source ) ) {
			return new WP_Error(
				'fgpjc_rest_invalid',
				__( 'The "source" parameter is required.', 'field-group-php-json-converter' ),
				array( 'status' => 400 )
			);
		}

		$result  = $this->service->php_source_to_json( $source );
		$payload = array(
			'success' => $result->is_success(),
			'output'  => $result->get_output(),
			'errors'  => $result->get_errors(),
		);

		return new WP_REST_Response( $payload, $result->is_success() ? 200 : 422 );
	}

	/**
	 * Convert pasted JSON source into a PHP registration file.
	 *
	 * @since 2.0.0
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function json_to_php( WP_REST_Request $request ) {
		$source = (string) $request->get_param( 'source' );

		if ( '' === trim( $source ) ) {
			return new WP_Error(
				'fgpjc_rest_invalid',
				__( 'The "source" parameter is required.', 'field-group-php-json-converter' ),
				array( 'status' => 400 )
			);
		}

		$result  = $this->service->json_source_to_php( $source );
		$payload = array(
			'success' => $result->is_success(),
			'output'  => $result->get_output(),
			'errors'  => $result->get_errors(),
		);

		return new WP_REST_Response( $payload, $result->is_success() ? 200 : 422 );
	}

	/**
	 * Scan the active theme for ACF field groups (PHP and Local JSON).
	 *
	 * @since 2.0.0
	 * @return WP_REST_Response
	 */
	public function scan_theme() {
		$scan   = $this->theme_service->scan_theme();
		$groups = array();
		foreach ( $scan->get_groups() as $item ) {
			$group    = $item['group'];
			$groups[] = array(
				'title'  => $group['title'] ?? '',
				'key'    => $group['key'] ?? '',
				'source' => $item['source'],
				'file'   => $item['file'],
			);
		}

		return new WP_REST_Response(
			array(
				'success' => $scan->has_groups() || ! $scan->has_errors(),
				'groups'  => $groups,
				'errors'  => $scan->get_errors(),
			),
			200
		);
	}

	/**
	 * Convert the theme's PHP-registered groups into Local JSON files.
	 *
	 * @since 2.0.0
	 * @return WP_REST_Response
	 */
	public function theme_to_json() {
		$result  = $this->theme_service->export_php_groups_to_local_json();
		$payload = array(
			'success' => $result->is_success(),
			'paths'   => $result->get_written_paths(),
			'errors'  => $result->get_errors(),
		);

		return new WP_REST_Response( $payload, $result->is_success() ? 200 : 422 );
	}

	/**
	 * Convert the theme's Local JSON groups into PHP registration code.
	 *
	 * @since 2.0.0
	 * @return WP_REST_Response
	 */
	public function theme_to_php() {
		$result  = $this->theme_service->convert_json_groups_to_php_code();
		$payload = array(
			'success' => $result->is_success(),
			'output'  => $result->get_output(),
			'errors'  => $result->get_errors(),
		);

		return new WP_REST_Response( $payload, $result->is_success() ? 200 : 422 );
	}
}
