<?php
/**
 * Uninstall Field Group PHP-JSON Converter.
 *
 * Deletes plugin options, transients, scheduled hooks, and log files so the
 * site is left clean after uninstall.
 *
 * @package Field_Group_PHP_JSON_Converter
 */

// Exit if not invoked through WordPress's uninstall process.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete plugin options.
$options = array(
	'field_group_php_json_converter_settings',
	'field_group_php_json_converter_logs',
	'field_group_php_json_converter_error_stats',
);
foreach ( $options as $option ) {
	delete_option( $option );
}

// Delete named transients.
$named_transients = array(
	'field_group_php_json_converter_scan_cache',
	'field_group_php_json_converter_cpt_scan_cache',
	'field_group_php_json_converter_taxonomy_scan_cache',
	'field_group_php_json_converter_full_scan_cache',
);
foreach ( $named_transients as $transient ) {
	delete_transient( $transient );
}

// Delete dynamically named transients (progress + cancellation) by prefix.
if ( isset( $GLOBALS['wpdb'] ) ) {
	global $wpdb;

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_acf_php_json%',
			'_transient_timeout_acf_php_json%'
		)
	);
}

// Remove the scheduled log cleanup hook.
wp_clear_scheduled_hook( 'field_group_php_json_converter_log_cleanup' );

// Remove the log directory created under uploads.
$upload_dir = wp_upload_dir();
$log_dir    = trailingslashit( $upload_dir['basedir'] ) . 'field-group-php-json-converter-logs';

if ( file_exists( $log_dir ) ) {
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $log_dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ( $iterator as $file ) {
		if ( $file->isDir() ) {
			rmdir( $file->getRealPath() );
		} else {
			unlink( $file->getRealPath() );
		}
	}

	rmdir( $log_dir );
}
