<?php
/**
 * Error Handler Utility.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Utilities
 */

namespace ACF_PHP_JSON_Converter\Utilities;

/**
 * Error Handler Class.
 *
 * Provides comprehensive error handling, user feedback, and recovery mechanisms.
 */
class Error_Handler {

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Logger    $logger    Logger instance.
     */
    protected $logger;

    /**
     * Plugin settings.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $settings    Plugin settings.
     */
    protected $settings;

    /**
     * Error queue for batch display.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $error_queue    Queued errors.
     */
    protected $error_queue = array();

    /**
     * Success queue for batch display.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $success_queue    Queued success messages.
     */
    protected $success_queue = array();

    /**
     * Warning queue for batch display.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $warning_queue    Queued warning messages.
     */
    protected $warning_queue = array();

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    Logger    $logger      Logger instance.
     * @param    array     $settings    Plugin settings.
     */
    public function __construct(Logger $logger, $settings = array()) {
        $this->logger = $logger;
        $this->settings = $settings;
    }

    /**
     * Handle error with comprehensive logging and user feedback.
     *
     * @since    1.0.0
     * @param    string    $error_code     Error code for identification.
     * @param    string    $message        User-friendly error message.
     * @param    array     $context        Additional context data.
     * @param    string    $severity       Error severity (error, warning, info).
     * @param    array     $recovery_options Recovery options for the user.
     * @return   array     Formatted error response.
     */
    public function handle_error($error_code, $message, $context = array(), $severity = 'error', $recovery_options = array()) {
        // Log the error with full context
        $log_context = array_merge($context, array(
            'error_code' => $error_code,
            'severity' => $severity,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql'),
            'request_uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        ));

        // Log based on severity
        switch ($severity) {
            case 'error':
                $this->logger->error($message, $log_context);
                break;
            case 'warning':
                $this->logger->warning($message, $log_context);
                break;
            case 'info':
                $this->logger->info($message, $log_context);
                break;
            default:
                $this->logger->error($message, $log_context);
        }

        // Create user-friendly error response
        $error_response = array(
            'success' => false,
            'error_code' => $error_code,
            'message' => $this->get_user_friendly_message($error_code, $message),
            'severity' => $severity,
            'recovery_options' => $this->get_recovery_options($error_code, $recovery_options),
            'support_info' => $this->get_support_info($error_code, $context),
            'timestamp' => current_time('mysql')
        );

        // Queue error for display if enabled
        if ($this->should_display_error($severity)) {
            $this->queue_error($error_response);
        }

        return $error_response;
    }

    /**
     * Handle success with user feedback.
     *
     * @since    1.0.0
     * @param    string    $message        Success message.
     * @param    array     $context        Additional context data.
     * @param    array     $next_actions   Suggested next actions.
     * @return   array     Formatted success response.
     */
    public function handle_success($message, $context = array(), $next_actions = array()) {
        // Log success if user actions logging is enabled
        if (isset($this->settings['log_user_actions']) && $this->settings['log_user_actions']) {
            $this->logger->info('Success: ' . $message, $context);
        }

        $success_response = array(
            'success' => true,
            'message' => $message,
            'context' => $context,
            'next_actions' => $next_actions,
            'timestamp' => current_time('mysql')
        );

        // Queue success for display
        $this->queue_success($success_response);

        return $success_response;
    }

    /**
     * Handle warning with user feedback.
     *
     * @since    1.0.0
     * @param    string    $message        Warning message.
     * @param    array     $context        Additional context data.
     * @param    array     $recommendations Recommendations to address the warning.
     * @return   array     Formatted warning response.
     */
    public function handle_warning($message, $context = array(), $recommendations = array()) {
        // Log warning
        $this->logger->warning($message, $context);

        $warning_response = array(
            'success' => true,
            'warning' => true,
            'message' => $message,
            'context' => $context,
            'recommendations' => $recommendations,
            'timestamp' => current_time('mysql')
        );

        // Queue warning for display
        $this->queue_warning($warning_response);

        return $warning_response;
    }

    /**
     * Get user-friendly error message.
     *
     * @since    1.0.0
     * @param    string    $error_code    Error code.
     * @param    string    $default_message Default message.
     * @return   string    User-friendly message.
     */
    protected function get_user_friendly_message($error_code, $default_message) {
        $messages = array(
            'file_not_found' => __('The requested file could not be found. Please check the file path and try again.', 'acf-php-json-converter'),
            'permission_denied' => __('You do not have permission to perform this action. Please contact your administrator.', 'acf-php-json-converter'),
            'invalid_json' => __('The JSON format is invalid. Please check your JSON syntax and try again.', 'acf-php-json-converter'),
            'invalid_php' => __('The PHP code contains syntax errors. Please review and correct the code.', 'acf-php-json-converter'),
            'conversion_failed' => __('The conversion process failed. This may be due to unsupported field types or corrupted data.', 'acf-php-json-converter'),
            'file_write_failed' => __('Unable to write the file. Please check file permissions and available disk space.', 'acf-php-json-converter'),
            'backup_failed' => __('Failed to create backup. The operation was cancelled to prevent data loss.', 'acf-php-json-converter'),
            'scan_failed' => __('Theme scanning failed. This may be due to file permission issues or corrupted theme files.', 'acf-php-json-converter'),
            'acf_not_active' => __('Advanced Custom Fields plugin is not active. Please activate ACF to use this converter.', 'acf-php-json-converter'),
            'theme_not_found' => __('The active theme could not be found. Please check your WordPress installation.', 'acf-php-json-converter'),
            'memory_limit' => __('The operation exceeded available memory. Try processing fewer items at once.', 'acf-php-json-converter'),
            'timeout' => __('The operation timed out. Try processing fewer items or increase the server timeout limit.', 'acf-php-json-converter'),
            'network_error' => __('A network error occurred. Please check your internet connection and try again.', 'acf-php-json-converter'),
            'database_error' => __('A database error occurred. Please try again or contact support if the problem persists.', 'acf-php-json-converter')
        );

        return isset($messages[$error_code]) ? $messages[$error_code] : $default_message;
    }

    /**
     * Get recovery options for error.
     *
     * @since    1.0.0
     * @param    string    $error_code    Error code.
     * @param    array     $custom_options Custom recovery options.
     * @return   array     Recovery options.
     */
    protected function get_recovery_options($error_code, $custom_options = array()) {
        $default_options = array(
            'file_not_found' => array(
                array(
                    'label' => __('Check File Path', 'acf-php-json-converter'),
                    'action' => 'verify_path',
                    'description' => __('Verify the file path is correct and the file exists.')
                ),
                array(
                    'label' => __('Refresh Scan', 'acf-php-json-converter'),
                    'action' => 'refresh_scan',
                    'description' => __('Perform a fresh scan of theme files.')
                )
            ),
            'permission_denied' => array(
                array(
                    'label' => __('Check Permissions', 'acf-php-json-converter'),
                    'action' => 'check_permissions',
                    'description' => __('Verify you have the required user role and file permissions.')
                ),
                array(
                    'label' => __('Contact Administrator', 'acf-php-json-converter'),
                    'action' => 'contact_admin',
                    'description' => __('Contact your site administrator for assistance.')
                )
            ),
            'invalid_json' => array(
                array(
                    'label' => __('Validate JSON', 'acf-php-json-converter'),
                    'action' => 'validate_json',
                    'description' => __('Use a JSON validator to check your JSON syntax.')
                ),
                array(
                    'label' => __('Use Sample JSON', 'acf-php-json-converter'),
                    'action' => 'use_sample',
                    'description' => __('Try with a sample ACF field group JSON.')
                )
            ),
            'conversion_failed' => array(
                array(
                    'label' => __('Try Individual Conversion', 'acf-php-json-converter'),
                    'action' => 'individual_conversion',
                    'description' => __('Convert field groups one at a time to identify problematic items.')
                ),
                array(
                    'label' => __('Check Field Types', 'acf-php-json-converter'),
                    'action' => 'check_field_types',
                    'description' => __('Ensure all field types are supported by your ACF version.')
                )
            ),
            'file_write_failed' => array(
                array(
                    'label' => __('Check Permissions', 'acf-php-json-converter'),
                    'action' => 'check_file_permissions',
                    'description' => __('Verify write permissions for the theme directory.')
                ),
                array(
                    'label' => __('Download Instead', 'acf-php-json-converter'),
                    'action' => 'download_file',
                    'description' => __('Download the JSON file and upload it manually.')
                )
            ),
            'memory_limit' => array(
                array(
                    'label' => __('Process Fewer Items', 'acf-php-json-converter'),
                    'action' => 'reduce_batch_size',
                    'description' => __('Select fewer field groups for batch processing.')
                ),
                array(
                    'label' => __('Increase Memory Limit', 'acf-php-json-converter'),
                    'action' => 'increase_memory',
                    'description' => __('Contact your host to increase PHP memory limit.')
                )
            )
        );

        $options = isset($default_options[$error_code]) ? $default_options[$error_code] : array();
        
        // Merge with custom options
        if (!empty($custom_options)) {
            $options = array_merge($options, $custom_options);
        }

        // Add generic recovery options if none exist
        if (empty($options)) {
            $options = array(
                array(
                    'label' => __('Try Again', 'acf-php-json-converter'),
                    'action' => 'retry',
                    'description' => __('Retry the operation.')
                ),
                array(
                    'label' => __('Contact Support', 'acf-php-json-converter'),
                    'action' => 'contact_support',
                    'description' => __('Contact support if the problem persists.')
                )
            );
        }

        return $options;
    }

    /**
     * Get support information for error.
     *
     * @since    1.0.0
     * @param    string    $error_code    Error code.
     * @param    array     $context       Error context.
     * @return   array     Support information.
     */
    protected function get_support_info($error_code, $context = array()) {
        return array(
            'error_code' => $error_code,
            'timestamp' => current_time('mysql'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => defined('ACF_PHP_JSON_CONVERTER_VERSION') ? ACF_PHP_JSON_CONVERTER_VERSION : 'unknown',
            'acf_version' => defined('ACF_VERSION') ? ACF_VERSION : 'not_installed',
            'theme' => get_template(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'context_summary' => $this->sanitize_context_for_support($context)
        );
    }

    /**
     * Sanitize context data for support information.
     *
     * @since    1.0.0
     * @param    array    $context    Context data.
     * @return   array    Sanitized context.
     */
    protected function sanitize_context_for_support($context) {
        // Remove sensitive information
        $sensitive_keys = array('password', 'token', 'key', 'secret', 'auth');
        $sanitized = array();

        foreach ($context as $key => $value) {
            $key_lower = strtolower($key);
            $is_sensitive = false;

            foreach ($sensitive_keys as $sensitive_key) {
                if (strpos($key_lower, $sensitive_key) !== false) {
                    $is_sensitive = true;
                    break;
                }
            }

            if ($is_sensitive) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_string($value) && strlen($value) > 200) {
                $sanitized[$key] = substr($value, 0, 200) . '... [TRUNCATED]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Check if error should be displayed to user.
     *
     * @since    1.0.0
     * @param    string    $severity    Error severity.
     * @return   bool      Whether to display error.
     */
    protected function should_display_error($severity) {
        $display_mode = isset($this->settings['error_display_mode']) ? $this->settings['error_display_mode'] : 'both';
        
        if ($display_mode === 'log_only') {
            return false;
        }

        // Check if we've reached the maximum number of error notices
        $max_notices = isset($this->settings['max_error_notices']) ? intval($this->settings['max_error_notices']) : 3;
        
        return count($this->error_queue) < $max_notices;
    }

    /**
     * Queue error for display.
     *
     * @since    1.0.0
     * @param    array    $error    Error data.
     */
    protected function queue_error($error) {
        $this->error_queue[] = $error;
    }

    /**
     * Queue success for display.
     *
     * @since    1.0.0
     * @param    array    $success    Success data.
     */
    protected function queue_success($success) {
        $this->success_queue[] = $success;
    }

    /**
     * Queue warning for display.
     *
     * @since    1.0.0
     * @param    array    $warning    Warning data.
     */
    protected function queue_warning($warning) {
        $this->warning_queue[] = $warning;
    }

    /**
     * Get all queued messages.
     *
     * @since    1.0.0
     * @return   array    All queued messages.
     */
    public function get_queued_messages() {
        return array(
            'errors' => $this->error_queue,
            'successes' => $this->success_queue,
            'warnings' => $this->warning_queue
        );
    }

    /**
     * Clear all queued messages.
     *
     * @since    1.0.0
     */
    public function clear_queued_messages() {
        $this->error_queue = array();
        $this->success_queue = array();
        $this->warning_queue = array();
    }

    /**
     * Create progress tracker for long-running operations.
     *
     * @since    1.0.0
     * @param    string    $operation_id    Unique operation identifier.
     * @param    int       $total_items     Total number of items to process.
     * @param    string    $operation_name  Human-readable operation name.
     * @return   Progress_Tracker    Progress tracker instance.
     */
    public function create_progress_tracker($operation_id, $total_items, $operation_name) {
        return new Progress_Tracker($operation_id, $total_items, $operation_name, $this->logger);
    }

    /**
     * Handle batch operation with progress tracking.
     *
     * @since    1.0.0
     * @param    string    $operation_name    Operation name.
     * @param    array     $items            Items to process.
     * @param    callable  $processor        Processing function.
     * @param    array     $options          Processing options.
     * @return   array     Batch operation results.
     */
    public function handle_batch_operation($operation_name, $items, $processor, $options = array()) {
        $operation_id = uniqid('batch_' . time() . '_');
        $progress_tracker = $this->create_progress_tracker($operation_id, count($items), $operation_name);
        
        $results = array(
            'operation_id' => $operation_id,
            'operation_name' => $operation_name,
            'total_items' => count($items),
            'processed_items' => 0,
            'successful_items' => 0,
            'failed_items' => 0,
            'errors' => array(),
            'warnings' => array(),
            'successes' => array(),
            'start_time' => microtime(true),
            'end_time' => null,
            'execution_time' => null
        );

        $progress_tracker->start();

        foreach ($items as $index => $item) {
            try {
                $progress_tracker->update_progress($index + 1, sprintf(__('Processing item %d of %d', 'acf-php-json-converter'), $index + 1, count($items)));
                
                $result = call_user_func($processor, $item, $index, $options);
                
                $results['processed_items']++;
                
                if (isset($result['success']) && $result['success']) {
                    $results['successful_items']++;
                    if (isset($result['message'])) {
                        $results['successes'][] = $result['message'];
                    }
                } else {
                    $results['failed_items']++;
                    if (isset($result['message'])) {
                        $results['errors'][] = $result['message'];
                    }
                }

                if (isset($result['warning'])) {
                    $results['warnings'][] = $result['warning'];
                }

            } catch (Exception $e) {
                $results['processed_items']++;
                $results['failed_items']++;
                $error_message = sprintf(__('Error processing item %d: %s', 'acf-php-json-converter'), $index + 1, $e->getMessage());
                $results['errors'][] = $error_message;
                $this->logger->error($error_message, array('item' => $item, 'exception' => $e));
            }

            // Check for memory or time limits
            if ($this->should_pause_batch_operation()) {
                $results['paused'] = true;
                $results['pause_reason'] = __('Operation paused due to resource limits', 'acf-php-json-converter');
                break;
            }
        }

        $results['end_time'] = microtime(true);
        $results['execution_time'] = round($results['end_time'] - $results['start_time'], 2);
        
        $progress_tracker->complete();

        // Log batch operation summary
        $this->logger->info(sprintf(
            'Batch operation completed: %s. Processed: %d, Successful: %d, Failed: %d, Time: %s seconds',
            $operation_name,
            $results['processed_items'],
            $results['successful_items'],
            $results['failed_items'],
            $results['execution_time']
        ), $results);

        return $results;
    }

    /**
     * Check if batch operation should be paused.
     *
     * @since    1.0.0
     * @return   bool    Whether to pause operation.
     */
    protected function should_pause_batch_operation() {
        // Check memory usage
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit !== '-1') {
            $memory_limit_bytes = $this->convert_to_bytes($memory_limit);
            $current_memory = memory_get_usage(true);
            
            if ($current_memory > ($memory_limit_bytes * 0.8)) {
                return true;
            }
        }

        // Check execution time
        $max_execution_time = ini_get('max_execution_time');
        if ($max_execution_time > 0) {
            $script_start_time = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : $_SERVER['REQUEST_TIME'];
            $current_time = microtime(true);
            $elapsed_time = $current_time - $script_start_time;
            
            if ($elapsed_time > ($max_execution_time * 0.8)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert memory limit string to bytes.
     *
     * @since    1.0.0
     * @param    string    $memory_limit    Memory limit string.
     * @return   int       Memory limit in bytes.
     */
    protected function convert_to_bytes($memory_limit) {
        $memory_limit = trim($memory_limit);
        $last = strtolower($memory_limit[strlen($memory_limit) - 1]);
        $memory_limit = (int) $memory_limit;

        switch ($last) {
            case 'g':
                $memory_limit *= 1024;
            case 'm':
                $memory_limit *= 1024;
            case 'k':
                $memory_limit *= 1024;
        }

        return $memory_limit;
    }

    /**
     * Get error recovery suggestions based on error patterns.
     *
     * @since    1.0.0
     * @param    array    $errors    Array of errors.
     * @return   array    Recovery suggestions.
     */
    public function get_error_recovery_suggestions($errors) {
        $suggestions = array();
        $error_patterns = array();

        // Analyze error patterns
        foreach ($errors as $error) {
            $error_code = isset($error['error_code']) ? $error['error_code'] : 'unknown';
            if (!isset($error_patterns[$error_code])) {
                $error_patterns[$error_code] = 0;
            }
            $error_patterns[$error_code]++;
        }

        // Generate suggestions based on patterns
        foreach ($error_patterns as $error_code => $count) {
            if ($count > 1) {
                $suggestions[] = array(
                    'type' => 'pattern',
                    'message' => sprintf(
                        __('Multiple %s errors detected (%d occurrences). This suggests a systematic issue.', 'acf-php-json-converter'),
                        $error_code,
                        $count
                    ),
                    'recommendations' => $this->get_pattern_recommendations($error_code)
                );
            }
        }

        // Add general suggestions if no specific patterns found
        if (empty($suggestions) && !empty($errors)) {
            $suggestions[] = array(
                'type' => 'general',
                'message' => __('Consider the following general troubleshooting steps:', 'acf-php-json-converter'),
                'recommendations' => array(
                    __('Check your WordPress and ACF plugin versions are up to date', 'acf-php-json-converter'),
                    __('Verify file permissions in your theme directory', 'acf-php-json-converter'),
                    __('Try processing items individually to isolate problematic field groups', 'acf-php-json-converter'),
                    __('Check the error log for more detailed information', 'acf-php-json-converter')
                )
            );
        }

        return $suggestions;
    }

    /**
     * Get recommendations for error patterns.
     *
     * @since    1.0.0
     * @param    string    $error_code    Error code.
     * @return   array     Recommendations.
     */
    protected function get_pattern_recommendations($error_code) {
        $recommendations = array(
            'file_not_found' => array(
                __('Check if theme files have been moved or deleted', 'acf-php-json-converter'),
                __('Verify the active theme is correctly installed', 'acf-php-json-converter'),
                __('Perform a fresh theme scan to update file paths', 'acf-php-json-converter')
            ),
            'permission_denied' => array(
                __('Check file and directory permissions (should be 644 for files, 755 for directories)', 'acf-php-json-converter'),
                __('Verify your user account has the required WordPress capabilities', 'acf-php-json-converter'),
                __('Contact your hosting provider if permission issues persist', 'acf-php-json-converter')
            ),
            'conversion_failed' => array(
                __('Check for unsupported or custom field types in your field groups', 'acf-php-json-converter'),
                __('Verify ACF field group structure is valid', 'acf-php-json-converter'),
                __('Update ACF plugin to the latest version', 'acf-php-json-converter')
            ),
            'memory_limit' => array(
                __('Process fewer items at once to reduce memory usage', 'acf-php-json-converter'),
                __('Ask your hosting provider to increase PHP memory limit', 'acf-php-json-converter'),
                __('Consider upgrading your hosting plan for more resources', 'acf-php-json-converter')
            )
        );

        return isset($recommendations[$error_code]) ? $recommendations[$error_code] : array(
            __('Review the specific error details in the log', 'acf-php-json-converter'),
            __('Try the operation again after addressing any obvious issues', 'acf-php-json-converter'),
            __('Contact support if the problem persists', 'acf-php-json-converter')
        );
    }
}