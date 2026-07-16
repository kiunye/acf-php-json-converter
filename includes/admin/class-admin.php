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
use Field_Group_PHP_JSON_Converter\Theme\Theme_Service;
use Field_Group_PHP_JSON_Converter\Utilities\Field_Group_Validator;
use Field_Group_PHP_JSON_Converter\Utilities\Validation_Result;

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
	 * Action used for the theme scan and theme convert operations.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public const THEME_ACTION = 'fgpjc_theme';

	/**
	 * Action used for the field group issue checks.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public const ISSUES_ACTION = 'fgpjc_issues';

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
	 * Field group validator.
	 *
	 * @since 2.0.0
	 * @var Field_Group_Validator
	 */
	private Field_Group_Validator $validator;

	/**
	 * Inject the conversion service.
	 *
	 * @since 2.0.0
	 * @param Field_Group_Conversion_Service|null $service Conversion service.
	 * @param Theme_Service|null                  $theme_service Theme service.
	 * @param Field_Group_Validator|null          $validator Field group validator.
	 */
	public function __construct(
		?Field_Group_Conversion_Service $service = null,
		?Theme_Service $theme_service = null,
		?Field_Group_Validator $validator = null
	) {
		$this->service       = $service ?? new Field_Group_Conversion_Service();
		$this->theme_service = $theme_service ?? new Theme_Service();
		$this->validator     = $validator ?? new Field_Group_Validator();
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
		add_action( 'admin_post_' . self::THEME_ACTION, array( $this, 'handle_post_theme' ) );
		add_action( 'wp_ajax_' . self::THEME_ACTION, array( $this, 'handle_ajax_theme' ) );
		add_action( 'wp_ajax_' . self::ISSUES_ACTION, array( $this, 'handle_ajax_issues' ) );
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
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'action'       => self::ACTION,
				'nonce'        => wp_create_nonce( self::ACTION ),
				'themeAction'  => self::THEME_ACTION,
				'themeNonce'   => wp_create_nonce( self::THEME_ACTION ),
				'issuesAction' => self::ISSUES_ACTION,
				'issuesNonce'  => wp_create_nonce( self::ISSUES_ACTION ),
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

		$theme_result    = get_transient( 'fgpjc_theme_result' );
		$theme_output    = is_array( $theme_result ) && isset( $theme_result['output'] ) ? $theme_result['output'] : '';
		$theme_message   = is_array( $theme_result ) && isset( $theme_result['message'] ) ? $theme_result['message'] : '';
		$theme_errors    = is_array( $theme_result ) && isset( $theme_result['errors'] ) ? $theme_result['errors'] : array();
		$theme_direction = is_array( $theme_result ) && isset( $theme_result['direction'] ) ? $theme_result['direction'] : 'php_to_json';

		delete_transient( 'fgpjc_convert_result' );
		delete_transient( 'fgpjc_theme_result' );

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

			<nav class="fgpjc-tabs" role="tablist" aria-label="<?php echo esc_attr__( 'Converter sections', 'field-group-php-json-converter' ); ?>">
				<button type="button" class="fgpjc-tab is-active" role="tab" aria-selected="true" aria-controls="fgpjc-panel-convert" id="fgpjc-tab-convert"><?php echo esc_html__( 'Convert', 'field-group-php-json-converter' ); ?></button>
				<button type="button" class="fgpjc-tab" role="tab" aria-selected="false" aria-controls="fgpjc-panel-theme" id="fgpjc-tab-theme"><?php echo esc_html__( 'Scan theme', 'field-group-php-json-converter' ); ?></button>
				<button type="button" class="fgpjc-tab" role="tab" aria-selected="false" aria-controls="fgpjc-panel-issues" id="fgpjc-tab-issues"><?php echo esc_html__( 'Field issues', 'field-group-php-json-converter' ); ?></button>
			</nav>

			<div class="fgpjc-panels">
				<section class="fgpjc-panel fgpjc-section" id="fgpjc-panel-convert" role="tabpanel" aria-labelledby="fgpjc-tab-convert">
					<h2 id="fgpjc-convert-heading"><?php echo esc_html__( 'Convert', 'field-group-php-json-converter' ); ?></h2>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fgpjc-form">
						<?php wp_nonce_field( self::ACTION ); ?>
						<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">

						<fieldset class="fgpjc-mode">
							<legend><?php echo esc_html__( 'Conversion direction', 'field-group-php-json-converter' ); ?></legend>
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
							<textarea id="fgpjc-output" name="output" class="fgpjc-output" spellcheck="false" readonly aria-describedby="fgpjc-convert-status"><?php echo esc_textarea( $output ); ?></textarea>
						</p>

						<p id="fgpjc-convert-status" class="fgpjc-status" role="status" aria-live="polite"></p>
					</form>

					<?php if ( $has_acf ) : ?>
						<section class="fgpjc-subsection" aria-labelledby="fgpjc-acf-heading">
							<h3 id="fgpjc-acf-heading"><?php echo esc_html__( 'ACF field groups', 'field-group-php-json-converter' ); ?></h3>
							<p><?php echo esc_html__( 'Export every ACF field group currently registered or saved in the database to a single JSON file, or import a JSON file to generate PHP registration code.', 'field-group-php-json-converter' ); ?></p>

							<p class="fgpjc-actions">
								<button type="button" class="button" id="fgpjc-bulk-export"><?php echo esc_html__( 'Export all field groups', 'field-group-php-json-converter' ); ?></button>
								<label class="button">
									<?php echo esc_html__( 'Import JSON file', 'field-group-php-json-converter' ); ?>
									<input type="file" id="fgpjc-import-file" accept=".json,application/json" class="fgpjc-import-input">
								</label>
							</p>

							<p id="fgpjc-export-status" class="fgpjc-status" role="status" aria-live="polite"></p>
						</section>
					<?php endif; ?>
				</section>

				<section class="fgpjc-panel fgpjc-section" id="fgpjc-panel-theme" role="tabpanel" aria-labelledby="fgpjc-tab-theme" hidden>
					<h2 id="fgpjc-theme-heading"><?php echo esc_html__( 'Scan active theme', 'field-group-php-json-converter' ); ?></h2>
					<p><?php echo esc_html__( 'Discover every ACF field group the active theme registers in PHP or stores as Local JSON, then convert between the two formats.', 'field-group-php-json-converter' ); ?></p>

					<?php if ( ! empty( $theme_errors ) ) : ?>
						<div class="notice notice-error">
							<ul>
								<?php foreach ( $theme_errors as $error ) : ?>
									<li><?php echo esc_html( $error ); ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<?php if ( '' !== $theme_message ) : ?>
						<div class="notice notice-success"><p><?php echo esc_html( $theme_message ); ?></p></div>
					<?php endif; ?>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="fgpjc-form">
						<?php wp_nonce_field( self::THEME_ACTION ); ?>
						<input type="hidden" name="action" value="<?php echo esc_attr( self::THEME_ACTION ); ?>">

						<fieldset class="fgpjc-mode">
							<legend><?php echo esc_html__( 'Conversion direction', 'field-group-php-json-converter' ); ?></legend>
							<label>
								<input type="radio" name="direction" value="php_to_json" <?php checked( $theme_direction, 'php_to_json' ); ?>>
								<?php echo esc_html__( 'PHP &rarr; Local JSON (write to theme acf-json folder)', 'field-group-php-json-converter' ); ?>
							</label>
							<label>
								<input type="radio" name="direction" value="json_to_php" <?php checked( $theme_direction, 'json_to_php' ); ?>>
								<?php echo esc_html__( 'Local JSON &rarr; PHP (functions.php code)', 'field-group-php-json-converter' ); ?>
							</label>
						</fieldset>

						<p class="fgpjc-actions">
							<button type="button" class="button" id="fgpjc-scan"><?php echo esc_html__( 'Scan theme', 'field-group-php-json-converter' ); ?></button>
							<button type="submit" class="button button-primary"><?php echo esc_html__( 'Convert discovered groups', 'field-group-php-json-converter' ); ?></button>
						</p>

						<?php if ( '' !== $theme_output ) : ?>
							<p>
								<label for="fgpjc-theme-output"><?php echo esc_html__( 'Result', 'field-group-php-json-converter' ); ?></label>
								<textarea id="fgpjc-theme-output" class="fgpjc-output" spellcheck="false" readonly><?php echo esc_textarea( $theme_output ); ?></textarea>
							</p>
						<?php endif; ?>

						<p id="fgpjc-scan-status" class="fgpjc-status" role="status" aria-live="polite"></p>
					</form>

					<div id="fgpjc-scan-results" class="fgpjc-scan-results" hidden>
						<h3><?php echo esc_html__( 'Discovered field groups', 'field-group-php-json-converter' ); ?></h3>
						<ul></ul>
					</div>

					<div id="fgpjc-scan-notices" class="fgpjc-scan-notices notice notice-info" hidden>
						<p><strong><?php echo esc_html__( 'Skipped groups', 'field-group-php-json-converter' ); ?></strong></p>
						<ul></ul>
					</div>
				</section>

				<section class="fgpjc-panel fgpjc-section" id="fgpjc-panel-issues" role="tabpanel" aria-labelledby="fgpjc-tab-issues" hidden>
					<h2 id="fgpjc-issues-heading"><?php echo esc_html__( 'Field issues', 'field-group-php-json-converter' ); ?></h2>
					<p><?php echo esc_html__( 'Checks the ACF environment, the snippet on the Convert tab, the active theme, and the database for malformed or duplicate field groups and unsupported field types.', 'field-group-php-json-converter' ); ?></p>

					<p class="fgpjc-actions">
						<button type="button" class="button button-primary" id="fgpjc-check-issues"><?php echo esc_html__( 'Check for issues', 'field-group-php-json-converter' ); ?></button>
					</p>

					<p id="fgpjc-issues-status" class="fgpjc-status" role="status" aria-live="polite"></p>

					<div id="fgpjc-issues-summary" class="fgpjc-issues-summary" hidden></div>

					<ul id="fgpjc-issues-list" class="fgpjc-issues-list"></ul>
				</section>
			</div>
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
	 * Handle the server-rendered theme convert form (admin-post).
	 *
	 * Direction "php_to_json" writes discovered PHP groups into the theme's
	 * Local JSON folder. Direction "json_to_php" produces functions.php code
	 * for discovered Local JSON groups.
	 *
	 * @since 2.0.0
	 * @return void
	 */
	public function handle_post_theme(): void {
		if ( ! $this->verify_theme_request() ) {
			wp_die( esc_html__( 'Invalid request.', 'field-group-php-json-converter' ) );
		}

		$direction = isset( $_POST['direction'] ) ? sanitize_key( wp_unslash( $_POST['direction'] ) ) : 'php_to_json'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( 'json_to_php' === $direction ) {
			$result  = $this->theme_service->convert_json_groups_to_php_code();
			$output  = $result->get_output();
			$message = $result->is_success()
				? __( 'PHP registration code generated. Paste this into your theme\'s functions.php.', 'field-group-php-json-converter' )
				: __( 'No Local JSON groups were converted.', 'field-group-php-json-converter' );
		} else {
			$result  = $this->theme_service->export_php_groups_to_local_json();
			$output  = implode( "\n", $result->get_written_paths() );
			$message = $result->is_success()
				? __( 'PHP field groups were written to the theme\'s acf-json folder.', 'field-group-php-json-converter' )
				: __( 'No PHP field groups were written.', 'field-group-php-json-converter' );
		}

		set_transient(
			'fgpjc_theme_result',
			array(
				'direction' => $direction,
				'output'    => $output,
				'message'   => $message,
				'errors'    => $result->get_errors(),
				'notices'   => $result->get_notices(),
			),
			60
		);

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
		return; // phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
	}

	/**
	 * Handle the AJAX theme scan/convert request used by the page script.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function handle_ajax_theme(): array {
		if ( ! $this->verify_theme_request() ) {
			return wp_send_json_error( array( 'message' => __( 'Invalid request.', 'field-group-php-json-converter' ) ), 403 );
		}

		$action = isset( $_POST['theme_action'] ) ? sanitize_key( wp_unslash( $_POST['theme_action'] ) ) : 'scan'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( 'scan' === $action ) {
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
			return wp_send_json_success(
				array(
					'groups'  => $groups,
					'errors'  => $scan->get_errors(),
					'notices' => $scan->get_notices(),
				)
			);
		}

		if ( 'json_to_php' === $action ) {
			$result = $this->theme_service->convert_json_groups_to_php_code();
			return wp_send_json_success(
				array(
					'success' => $result->is_success(),
					'output'  => $result->get_output(),
					'errors'  => $result->get_errors(),
					'notices' => $result->get_notices(),
				)
			);
		}

		$result = $this->theme_service->export_php_groups_to_local_json();
		return wp_send_json_success(
			array(
				'success' => $result->is_success(),
				'paths'   => $result->get_written_paths(),
				'errors'  => $result->get_errors(),
				'notices' => $result->get_notices(),
			)
		);
	}

	/**
	 * Verify a theme request's nonce and capability.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	private function verify_theme_request(): bool {
		if ( ! check_ajax_referer( self::THEME_ACTION, '_wpnonce', false ) ) {
			return false;
		}

		return current_user_can( 'manage_options' );
	}

	/**
	 * Run every field-group issue check and return the aggregated results.
	 *
	 * Accepts an optional "source" snippet and its "format" so the pasted
	 * content on the Convert tab can also be validated.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function handle_ajax_issues(): array {
		if ( ! $this->verify_issues_request() ) {
			return wp_send_json_error( array( 'message' => __( 'Invalid request.', 'field-group-php-json-converter' ) ), 403 );
		}

		$source = isset( $_POST['source'] ) ? (string) wp_unslash( $_POST['source'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$format = isset( $_POST['format'] ) ? sanitize_key( wp_unslash( $_POST['format'] ) ) : 'php'; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! in_array( $format, array( 'php', 'json' ), true ) ) {
			$format = 'php';
		}

		$result = new Validation_Result();

		$env = $this->validator->validate_environment();
		foreach ( $env->get_issues() as $issue ) {
			$result->add_issue( $issue['severity'], $issue['message'], $issue['context'] );
		}

		if ( '' !== trim( $source ) ) {
			$snippet = $this->validator->validate_source( $source, $format );
			foreach ( $snippet->get_issues() as $issue ) {
				$result->add_issue( $issue['severity'], $issue['message'], $issue['context'] );
			}
		}

		$scan  = $this->theme_service->scan_theme();
		$theme = $this->validator->validate_scan_items( $scan->get_groups() );
		foreach ( $theme->get_issues() as $issue ) {
			$result->add_issue( $issue['severity'], $issue['message'], $issue['context'] );
		}

		$db = $this->validator->validate_db_groups();
		foreach ( $db->get_issues() as $issue ) {
			$result->add_issue( $issue['severity'], $issue['message'], $issue['context'] );
		}

		if ( ! $result->has_issues() ) {
			$result->add_issue( 'ok', __( 'No issues were detected.', 'field-group-php-json-converter' ) );
		}

		$errors   = 0;
		$warnings = 0;
		foreach ( $result->get_issues() as $issue ) {
			if ( 'error' === $issue['severity'] ) {
				++$errors;
			} elseif ( 'warning' === $issue['severity'] ) {
				++$warnings;
			}
		}

		return wp_send_json_success(
			array(
				'issues'   => $result->get_issues(),
				'errors'   => $errors,
				'warnings' => $warnings,
			)
		);
	}

	/**
	 * Verify an issues request's nonce and capability.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	private function verify_issues_request(): bool {
		if ( ! check_ajax_referer( self::ISSUES_ACTION, '_wpnonce', false ) ) {
			return false;
		}

		return current_user_can( 'manage_options' );
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
