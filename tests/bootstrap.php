<?php
/**
 * Test bootstrap.
 *
 * Loads Composer's autoloader (PHPUnit, etc.) and registers the plugin's
 * class autoloader so the shared utility and feature classes resolve. The
 * full plugin runtime is intentionally not bootstrapped here.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once dirname( __DIR__ ) . '/includes/autoload.php';

/**
 * Lightweight WordPress stand-ins so classes that call WP functions/classes
 * (REST controller, etc.) can be unit tested without a full WordPress
 * environment. These are intentionally minimal and only cover what the tests
 * exercise.
 */
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;
		public $data;
		public function __construct( $code = '', $message = '', $data = array() ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		public $data;
		public $status;
		public $headers = array();
		public function __construct( $data = null, $status = 200, $headers = array() ) {
			$this->data    = $data;
			$this->status  = $status;
			$this->headers = $headers;
		}
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		protected $params = array();
		public function get_param( $key ) {
			return $this->params[ $key ] ?? null;
		}
		public function set_param( $key, $value ) {
			$this->params[ $key ] = $value;
		}
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	class WP_REST_Server {
		const CREATABLE = 'POST';
		const READABLE  = 'GET';
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	$GLOBALS['fgc_rest_routes'] = array();
	function register_rest_route( $namespace, $route, $args ) {
		$GLOBALS['fgc_rest_routes'][] = $namespace . $route;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	$GLOBALS['fgc_current_user_can'] = true;
	function current_user_can( $capability ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $GLOBALS['fgc_current_user_can'];
	}
}

if ( ! function_exists( 'rest_authorization_required_code' ) ) {
	function rest_authorization_required_code() {
		return 401;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $text;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $value ) {
		return $value;
	}
}

