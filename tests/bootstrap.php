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

if ( ! function_exists( 'add_action' ) ) {
	$GLOBALS['fgc_actions'] = array();
	function add_action( $hook, $callback, $priority = 10, $args = 1 ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$GLOBALS['fgc_actions'][ $hook ][] = $callback;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return $GLOBALS['fgc_is_admin'] ?? false;
	}
}

if ( ! function_exists( 'add_menu_page' ) ) {
	$GLOBALS['fgc_menu_pages'] = array();
	function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback = '', $icon_url = '', $position = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$GLOBALS['fgc_menu_pages'][ $menu_slug ] = compact( 'page_title', 'menu_title', 'capability', 'menu_slug', 'callback' );
	}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	$GLOBALS['fgc_valid_nonce'] = true;
	function wp_verify_nonce( $nonce, $action = -1 ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $GLOBALS['fgc_valid_nonce'];
	}
}

if ( ! function_exists( 'check_ajax_referer' ) ) {
	function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$valid = $GLOBALS['fgc_valid_nonce'] ?? true;
		if ( ! $valid && $die ) {
			return false;
		}
		return $valid;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return is_string( $value ) ? trim( strip_tags( $value ) ) : $value;
	}
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
	function wp_send_json_success( $data = null, $status_code = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$response = array( 'success' => true, 'data' => $data );
		$GLOBALS['fgc_json_response'] = $response;
		return $response;
	}
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
	function wp_send_json_error( $data = null, $status_code = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$response = array( 'success' => false, 'data' => $data );
		$GLOBALS['fgc_json_response'] = $response;
		return $response;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	$GLOBALS['fgc_transients'] = array();
	function set_transient( $key, $value, $expiration = 0 ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$GLOBALS['fgc_transients'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		return $GLOBALS['fgc_transients'][ $key ] ?? false;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $key ) {
		unset( $GLOBALS['fgc_transients'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '' ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $message;
	}
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
	function wp_safe_redirect( $location, $status = 302, $x_redirect_by = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$GLOBALS['fgc_redirect'] = $location;
		return true;
	}
}

if ( ! function_exists( 'post_type_exists' ) ) {
	function post_type_exists( $post_type ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return ( 'acf-field-group' === $post_type ) && ( $GLOBALS['fgc_acf_active'] ?? false );
	}
}

if ( ! function_exists( 'acf_get_field_groups' ) ) {
	function acf_get_field_groups( $args = array() ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $GLOBALS['fgc_acf_groups'] ?? array();
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $text;
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return $url;
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES );
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $echo = true ) {
		$result = ( $checked === $current ) ? 'checked="checked"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$field = '<input type="hidden" name="' . esc_attr( $name ) . '" value="nonce">';
		if ( $echo ) {
			echo $field;
		}
		return $field;
	}
}

