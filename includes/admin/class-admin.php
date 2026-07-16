<?php
/**
 * Admin UI for the PHP <-> JSON field group converter.
 *
 * @package Field_Group_PHP_JSON_Converter
 * @subpackage Field_Group_PHP_JSON_Converter/Admin
 * @since 2.0.0
 */

namespace Field_Group_PHP_JSON_Converter\Admin;

use Field_Group_PHP_JSON_Converter\Services\Field_Group_Conversion_Service;

/**
 * Registers the settings/tool page and the conversion handlers used by the
 * admin UI (both the server-rendered tool and the AJAX live converter).
 *
 * @since 2.0.0
 */
class Admin {

	/**
	 * Top level menu slug.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public const MENU_SLUG = 'field-group-php-json-converter';

	/**
	 * AJAX / admin-post action base.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public const ACTION = 'fgpjc_convert';

	/**
	 * Conversion orchestration service.
	 *
	 * @since 2.0.0
	 * @var Field_Group_Conversion_Service
	 */
	private Field_Group_Conversion_Service $service;

	/**
	 * Inject the conversion service.
	 *
	 * @since 2.0.0
	 * @param Field_Group_Conversion_Service|null $service Conversion service.
	 */
	public function __construct( ?Field_Group_Conversion_Service $service = null ) {
		$this->service = $service ?? new Field_Group_Conversion_Service();
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_post_convert' ) );
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle_ajax_convert' ) );
		add_action( 'wp_ajax_fgpjc_bulk_export', array( $this, 'handle_bulk_export' ) );
	}

	/**
	 * Add the top level menu and tool page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'Field Group Converter', 'field-group-php-json-converter' ),
			__( 'Field Group Converter', 'field-group-php-json-converter' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' ),
			'dashicons-controls-repeat',
			80
		);
	}

	/**
	 * Enqueue the admin stylesheet and script on the converter page only.
	 *
	 * @since 2.0.0
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook ) {
			return;
		}

		$base = defined( 'FIELD_GROUP_PHP_JSON_CONVERTER_URL' )
			? FIELD_GROUP_PHP_JSON_CONVERTER_URL
			: plugin_dir_url( dirname( __DIR__, 2 ) . '/field-group-php-json-converter.php' );

		wp_enqueue_style(
			'fgpjc-admin',
			$base . 'assets/css/admin.css',
			array(),
			'2.0.0'
		);

		wp_enqueue_script(
			'fgpjc-admin',
			$base . 'assets/js/admin.js',
			array( 'jquery' ),
			'2.0.0',
			true
		);

		wp_localize_script(
			'fgpjc-admin',
			'fgpjcAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::ACTION,
				'nonce'   => wp_create_nonce( self::ACTION ),
			)
		);
	}

	/**
	 * Render the converter tool page.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to view this page.', 'field-group-php-json-converter' ) );
		}

		$result  = get_transient( 'fgpjc_convert_result' );
		$input   = is_array( $result ) && isset( $result['input'] ) ? $result['input'] : '';
		$output  = is_array( $result ) && isset( $result['output'] ) ? $result['output'] : '';
		$errors  = is_array( $result ) && isset( $result['errors'] ) ? $result['errors'] : array();
		$mode    = is_array( $result ) && isset( $result['mode'] ) ? $result['mode'] : 'php_to_json';
		$has_acf = $this->acf_available();

		delete_transient( 'fgpjc_convert_result' );

		?>
		<div class="wrap fgpjc-wrap">
			<h1><?php echo esc_html__( 'Field Group PHP &harr; JSON Converter', 'field-group-php-json-converter' ); ?></h1>

			<?php if ( ! empty( $errors ) ) : ?>
				<div class="notice notice-error">
					<ul>
						<?php foreach ( $errors as $error ) : ?>
							<li><?php echo esc_html( $error ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fgpjc-form">
				<?php wp_nonce_field( self::ACTION ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">

				<fieldset class="fgpjc-mode">
					<label>
						<input type="radio" name="mode" value="php_to_json" <?php checked( $mode, 'php_to_json' ); ?>>
						<?php echo esc_html__( 'PHP &rarr; JSON', 'field-group-php-json-converter' ); ?>
					</label>
					<label>
						<input type="radio" name="mode" value="json_to_php" <?php checked( $mode, 'json_to_php' ); ?>>
						<?php echo esc_html__( 'JSON &rarr; PHP', 'field-group-php-json-converter' ); ?>
					</label>
				</fieldset>

				<p>
					<label for="fgpjc-input"><?php echo esc_html__( 'Source', 'field-group-php-json-converter' ); ?></label>
					<textarea id="fgpjc-input" name="source" class="fgpjc-input" spellcheck="false" placeholder="<?php echo esc_attr__( 'Paste ACF PHP or JSON here…', 'field-group-php-json-converter' ); ?>"><?php echo esc_textarea( $input ); ?></textarea>
				</p>

				<p class="fgpjc-actions">
					<button type="submit" class="button button-primary"><?php echo esc_html__( 'Convert', 'field-group-php-json-converter' ); ?></button>
					<button type="button" class="button fgpjc-copy" data-target="fgpjc-output"><?php echo esc_html__( 'Copy output', 'field-group-php-json-converter' ); ?></button>
				</p>

				<p>
					<label for="fgpjc-output"><?php echo esc_html__( 'Output', 'field-group-php-json-converter' ); ?></label>
					<textarea id="fgpjc-output" name="output" class="fgpjc-output" spellcheck="false" readonly><?php echo esc_textarea( $output ); ?></textarea>
				</p>
			</form>

			<?php if ( $has_acf ) : ?>
				<hr>
				<h2><?php echo esc_html__( 'Bulk export ACF field groups', 'field-group-php-json-converter' ); ?></h2>
				<p><?php echo esc_html__( 'Export every ACF field group currently registered or saved in the database to a single JSON file.', 'field-group-php-json-converter' ); ?></p>
				<button type="button" class="button" id="fgpjc-bulk-export"><?php echo esc_html__( 'Export all field groups', 'field-group-php-json-converter' ); ?></button>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle the server-rendered convert form (admin-post).
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_post_convert(): void {
		if ( ! $this->verify_admin_request() ) {
			wp_die( esc_html__( 'Invalid request.', 'field-group-php-json-converter' ) );
		}

		$mode   = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'php_to_json'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$source = isset( $_POST['source'] ) ? (string) wp_unslash( $_POST['source'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$result = ( 'json_to_php' === $mode )
			? $this->service->json_source_to_php( $source )
			: $this->service->php_source_to_json( $source );

		set_transient(
			'fgpjc_convert_result',
			array(
				'mode'   => $mode,
				'input'  => $source,
				'output' => $result->get_output(),
				'errors' => $result->get_errors(),
			),
			60
		);

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
		return; // phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
	}

	/**
	 * Handle the live AJAX converter used by the page script.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function handle_ajax_convert(): array {
		if ( ! $this->verify_admin_request() ) {
			return wp_send_json_error( array( 'message' => __( 'Invalid request.', 'field-group-php-json-converter' ) ), 403 );
		}

		$mode   = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'php_to_json'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$source = isset( $_POST['source'] ) ? (string) wp_unslash( $_POST['source'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$result = ( 'json_to_php' === $mode )
			? $this->service->json_source_to_php( $source )
			: $this->service->php_source_to_json( $source );

		return wp_send_json_success(
			array(
				'success' => $result->is_success(),
				'output'  => $result->get_output(),
				'errors'  => $result->get_errors(),
			)
		);
	}

	/**
	 * Export all ACF field groups (admin-ajax) as a downloadable JSON string.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function handle_bulk_export(): array {
		if ( ! $this->verify_admin_request() ) {
			return wp_send_json_error( array( 'message' => __( 'Invalid request.', 'field-group-php-json-converter' ) ), 403 );
		}

		if ( ! $this->acf_available() ) {
			return wp_send_json_error( array( 'message' => __( 'ACF is not active.', 'field-group-php-json-converter' ) ), 400 );
		}

		$groups  = acf_get_field_groups();
		$payload = array();

		foreach ( $groups as $group ) {
			$key       = isset( $group['key'] ) ? $group['key'] : '';
			$full      = function_exists( 'acf_get_field_group' ) ? acf_get_field_group( $key ) : $group;
			$payload[] = $full;
		}

		return wp_send_json_success(
			array(
				'count' => count( $payload ),
				'json'  => Field_Group_Conversion_Service::encode_json( $payload ),
			)
		);
	}

	/**
	 * Whether ACF is available for bulk operations.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	private function acf_available(): bool {
		return function_exists( 'acf_get_field_groups' ) && post_type_exists( 'acf-field-group' );
	}

	/**
	 * Verify an admin (post or ajax) request's nonce and capability.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	private function verify_admin_request(): bool {
		if ( ! check_ajax_referer( self::ACTION, '_wpnonce', false ) ) {
			return false;
		}

		return current_user_can( 'manage_options' );
	}
}
