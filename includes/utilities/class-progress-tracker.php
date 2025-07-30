<?php
/**
 * Progress Tracker Utility.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Utilities
 */

namespace ACF_PHP_JSON_Converter\Utilities;

/**
 * Progress Tracker Class.
 *
 * Tracks progress of long-running operations and provides user feedback.
 */
class Progress_Tracker {

    /**
     * Operation ID.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $operation_id    Unique operation identifier.
     */
    protected $operation_id;

    /**
     * Total items to process.
     *
     * @since    1.0.0
     * @access   protected
     * @var      int    $total_items    Total number of items.
     */
    protected $total_items;

    /**
     * Operation name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $operation_name    Human-readable operation name.
     */
    protected $operation_name;

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Logger    $logger    Logger instance.
     */
    protected $logger;

    /**
     * Current progress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      int    $current_progress    Current progress count.
     */
    protected $current_progress = 0;

    /**
     * Start time.
     *
     * @since    1.0.0
     * @access   protected
     * @var      float    $start_time    Operation start time.
     */
    protected $start_time;

    /**
     * Progress messages.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $messages    Progress messages.
     */
    protected $messages = array();

    /**
     * Progress data for client updates.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $progress_data    Progress data.
     */
    protected $progress_data = array();

    /**
     * Initialize the progress tracker.
     *
     * @since    1.0.0
     * @param    string    $operation_id      Unique operation identifier.
     * @param    int       $total_items       Total number of items to process.
     * @param    string    $operation_name    Human-readable operation name.
     * @param    Logger    $logger           Logger instance.
     */
    public function __construct($operation_id, $total_items, $operation_name, Logger $logger) {
        $this->operation_id = $operation_id;
        $this->total_items = max(1, intval($total_items)); // Ensure at least 1 to avoid division by zero
        $this->operation_name = $operation_name;
        $this->logger = $logger;
        $this->start_time = microtime(true);
        
        $this->progress_data = array(
            'operation_id' => $this->operation_id,
            'operation_name' => $this->operation_name,
            'total_items' => $this->total_items,
            'current_progress' => 0,
            'percentage' => 0,
            'status' => 'initialized',
            'start_time' => $this->start_time,
            'estimated_completion' => null,
            'current_message' => '',
            'messages' => array(),
            'errors' => array(),
            'warnings' => array()
        );
    }

    /**
     * Start the operation.
     *
     * @since    1.0.0
     * @param    string    $initial_message    Initial progress message.
     */
    public function start($initial_message = '') {
        $this->progress_data['status'] = 'running';
        $this->progress_data['start_time'] = microtime(true);
        
        if (empty($initial_message)) {
            $initial_message = sprintf(__('Starting %s...', 'acf-php-json-converter'), $this->operation_name);
        }
        
        $this->add_message($initial_message, 'info');
        $this->logger->info(sprintf('Progress tracker started: %s (ID: %s)', $this->operation_name, $this->operation_id));
        
        // Store progress data for client retrieval
        $this->store_progress_data();
    }

    /**
     * Update progress.
     *
     * @since    1.0.0
     * @param    int       $current_progress    Current progress count.
     * @param    string    $message            Progress message.
     * @param    array     $context            Additional context data.
     */
    public function update_progress($current_progress, $message = '', $context = array()) {
        $this->current_progress = max(0, min($current_progress, $this->total_items));
        $this->progress_data['current_progress'] = $this->current_progress;
        $this->progress_data['percentage'] = round(($this->current_progress / $this->total_items) * 100, 1);
        
        // Calculate estimated completion time
        if ($this->current_progress > 0) {
            $elapsed_time = microtime(true) - $this->start_time;
            $rate = $this->current_progress / $elapsed_time;
            $remaining_items = $this->total_items - $this->current_progress;
            $estimated_remaining_time = $remaining_items / $rate;
            $this->progress_data['estimated_completion'] = time() + $estimated_remaining_time;
        }
        
        if (!empty($message)) {
            $this->progress_data['current_message'] = $message;
            $this->add_message($message, 'info', $context);
        }
        
        // Store updated progress data
        $this->store_progress_data();
        
        // Log progress at intervals
        if ($this->current_progress % max(1, floor($this->total_items / 10)) === 0 || $this->current_progress === $this->total_items) {
            $this->logger->info(sprintf(
                'Progress update: %s - %d/%d (%s%%) - %s',
                $this->operation_name,
                $this->current_progress,
                $this->total_items,
                $this->progress_data['percentage'],
                $message
            ));
        }
    }

    /**
     * Add error to progress tracking.
     *
     * @since    1.0.0
     * @param    string    $error_message    Error message.
     * @param    array     $context         Error context.
     */
    public function add_error($error_message, $context = array()) {
        $error_data = array(
            'message' => $error_message,
            'timestamp' => current_time('mysql'),
            'context' => $context
        );
        
        $this->progress_data['errors'][] = $error_data;
        $this->add_message($error_message, 'error', $context);
        $this->store_progress_data();
    }

    /**
     * Add warning to progress tracking.
     *
     * @since    1.0.0
     * @param    string    $warning_message    Warning message.
     * @param    array     $context           Warning context.
     */
    public function add_warning($warning_message, $context = array()) {
        $warning_data = array(
            'message' => $warning_message,
            'timestamp' => current_time('mysql'),
            'context' => $context
        );
        
        $this->progress_data['warnings'][] = $warning_data;
        $this->add_message($warning_message, 'warning', $context);
        $this->store_progress_data();
    }

    /**
     * Complete the operation.
     *
     * @since    1.0.0
     * @param    string    $completion_message    Completion message.
     * @param    bool      $success              Whether operation was successful.
     */
    public function complete($completion_message = '', $success = true) {
        $this->progress_data['status'] = $success ? 'completed' : 'failed';
        $this->progress_data['end_time'] = microtime(true);
        $this->progress_data['execution_time'] = round($this->progress_data['end_time'] - $this->start_time, 2);
        
        if (empty($completion_message)) {
            if ($success) {
                $completion_message = sprintf(__('%s completed successfully in %s seconds.', 'acf-php-json-converter'), $this->operation_name, $this->progress_data['execution_time']);
            } else {
                $completion_message = sprintf(__('%s failed after %s seconds.', 'acf-php-json-converter'), $this->operation_name, $this->progress_data['execution_time']);
            }
        }
        
        $this->add_message($completion_message, $success ? 'success' : 'error');
        
        $this->logger->info(sprintf(
            'Progress tracker completed: %s (ID: %s) - Status: %s, Time: %s seconds, Errors: %d, Warnings: %d',
            $this->operation_name,
            $this->operation_id,
            $this->progress_data['status'],
            $this->progress_data['execution_time'],
            count($this->progress_data['errors']),
            count($this->progress_data['warnings'])
        ));
        
        $this->store_progress_data();
    }

    /**
     * Cancel the operation.
     *
     * @since    1.0.0
     * @param    string    $cancellation_message    Cancellation message.
     */
    public function cancel($cancellation_message = '') {
        $this->progress_data['status'] = 'cancelled';
        $this->progress_data['end_time'] = microtime(true);
        $this->progress_data['execution_time'] = round($this->progress_data['end_time'] - $this->start_time, 2);
        
        if (empty($cancellation_message)) {
            $cancellation_message = sprintf(__('%s was cancelled after %s seconds.', 'acf-php-json-converter'), $this->operation_name, $this->progress_data['execution_time']);
        }
        
        $this->add_message($cancellation_message, 'warning');
        $this->logger->warning(sprintf('Progress tracker cancelled: %s (ID: %s)', $this->operation_name, $this->operation_id));
        
        $this->store_progress_data();
    }

    /**
     * Add message to progress tracking.
     *
     * @since    1.0.0
     * @param    string    $message    Message text.
     * @param    string    $type       Message type (info, success, warning, error).
     * @param    array     $context    Additional context data.
     */
    protected function add_message($message, $type = 'info', $context = array()) {
        $message_data = array(
            'message' => $message,
            'type' => $type,
            'timestamp' => current_time('mysql'),
            'context' => $context
        );
        
        $this->messages[] = $message_data;
        $this->progress_data['messages'][] = $message_data;
        
        // Keep only the last 50 messages to prevent memory issues
        if (count($this->progress_data['messages']) > 50) {
            $this->progress_data['messages'] = array_slice($this->progress_data['messages'], -50);
        }
    }

    /**
     * Store progress data for client retrieval.
     *
     * @since    1.0.0
     */
    protected function store_progress_data() {
        // Store in WordPress transients for client-side retrieval
        $transient_key = 'acf_php_json_progress_' . $this->operation_id;
        set_transient($transient_key, $this->progress_data, 3600); // Store for 1 hour
    }

    /**
     * Get current progress data.
     *
     * @since    1.0.0
     * @return   array    Progress data.
     */
    public function get_progress_data() {
        return $this->progress_data;
    }

    /**
     * Get progress percentage.
     *
     * @since    1.0.0
     * @return   float    Progress percentage.
     */
    public function get_percentage() {
        return $this->progress_data['percentage'];
    }

    /**
     * Get estimated completion time.
     *
     * @since    1.0.0
     * @return   int|null    Estimated completion timestamp or null if not available.
     */
    public function get_estimated_completion() {
        return $this->progress_data['estimated_completion'];
    }

    /**
     * Get all messages.
     *
     * @since    1.0.0
     * @return   array    All progress messages.
     */
    public function get_messages() {
        return $this->messages;
    }

    /**
     * Get messages by type.
     *
     * @since    1.0.0
     * @param    string    $type    Message type to filter by.
     * @return   array     Filtered messages.
     */
    public function get_messages_by_type($type) {
        return array_filter($this->messages, function($message) use ($type) {
            return $message['type'] === $type;
        });
    }

    /**
     * Check if operation is running.
     *
     * @since    1.0.0
     * @return   bool    Whether operation is running.
     */
    public function is_running() {
        return $this->progress_data['status'] === 'running';
    }

    /**
     * Check if operation is completed.
     *
     * @since    1.0.0
     * @return   bool    Whether operation is completed.
     */
    public function is_completed() {
        return in_array($this->progress_data['status'], array('completed', 'failed', 'cancelled'));
    }

    /**
     * Get operation summary.
     *
     * @since    1.0.0
     * @return   array    Operation summary.
     */
    public function get_summary() {
        return array(
            'operation_id' => $this->operation_id,
            'operation_name' => $this->operation_name,
            'status' => $this->progress_data['status'],
            'total_items' => $this->total_items,
            'processed_items' => $this->current_progress,
            'percentage' => $this->progress_data['percentage'],
            'execution_time' => isset($this->progress_data['execution_time']) ? $this->progress_data['execution_time'] : null,
            'error_count' => count($this->progress_data['errors']),
            'warning_count' => count($this->progress_data['warnings']),
            'message_count' => count($this->messages)
        );
    }

    /**
     * Static method to retrieve progress data by operation ID.
     *
     * @since    1.0.0
     * @param    string    $operation_id    Operation ID.
     * @return   array|false    Progress data or false if not found.
     */
    public static function get_progress_by_id($operation_id) {
        $transient_key = 'acf_php_json_progress_' . $operation_id;
        return get_transient($transient_key);
    }

    /**
     * Static method to clean up old progress data.
     *
     * @since    1.0.0
     * @param    int    $max_age_hours    Maximum age in hours.
     */
    public static function cleanup_old_progress($max_age_hours = 24) {
        global $wpdb;
        
        $cutoff_time = time() - ($max_age_hours * 3600);
        
        // Clean up transients older than the cutoff time
        $transient_prefix = '_transient_acf_php_json_progress_';
        $timeout_prefix = '_transient_timeout_acf_php_json_progress_';
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d",
            $timeout_prefix . '%',
            $cutoff_time
        ));
        
        // Clean up the corresponding transient data
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT IN (
                SELECT REPLACE(option_name, '_transient_timeout_', '_transient_') 
                FROM {$wpdb->options} 
                WHERE option_name LIKE %s
            )",
            $transient_prefix . '%',
            $timeout_prefix . '%'
        ));
    }
}