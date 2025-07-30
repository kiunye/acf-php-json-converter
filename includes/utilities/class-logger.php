<?php
/**
 * Logger Utility.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Utilities
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Utilities
 */

namespace ACF_PHP_JSON_Converter\Utilities;

/**
 * Logger Utility Class.
 *
 * Handles logging for the plugin with different log levels,
 * log rotation, log retrieval functionality, and error tracking.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Utilities
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Utilities
 */
class Logger {

    /**
     * Log levels.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $log_levels    Available log levels.
     */
    private $log_levels = array(
        'error'   => 0,
        'warning' => 1,
        'info'    => 2,
        'debug'   => 3,
    );

    /**
     * Current log level.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $current_level    Current log level.
     */
    private $current_level;

    /**
     * Log file path.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $log_file    Log file path.
     */
    private $log_file;

    /**
     * Maximum log file size in bytes.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $max_file_size    Maximum log file size.
     */
    private $max_file_size = 5242880; // 5MB

    /**
     * Maximum number of log files to keep.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $max_files    Maximum number of log files.
     */
    private $max_files = 5;

    /**
     * Maximum number of log entries to store in database.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $max_db_entries    Maximum number of log entries.
     */
    private $max_db_entries = 1000;

    /**
     * Option name for storing logs in database.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $option_name    Option name.
     */
    private $option_name = 'acf_php_json_converter_logs';
    
    /**
     * Option name for storing error statistics.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $error_stats_option    Option name for error statistics.
     */
    private $error_stats_option = 'acf_php_json_converter_error_stats';
    
    /**
     * Maximum age of logs in days before cleanup.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $max_log_age    Maximum age of logs in days.
     */
    private $max_log_age = 30;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Get log level from settings
        $settings = get_option('acf_php_json_converter_settings', array());
        $this->current_level = isset($settings['logging_level']) ? $settings['logging_level'] : 'error';
        
        // Set log file path
        $upload_dir = wp_upload_dir();
        $log_dir = trailingslashit($upload_dir['basedir']) . 'acf-php-json-converter-logs';
        
        // Create log directory if it doesn't exist
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            
            // Create .htaccess file to protect logs
            $htaccess_file = trailingslashit($log_dir) . '.htaccess';
            if (!file_exists($htaccess_file)) {
                $htaccess_content = "# Deny access to all files\n";
                $htaccess_content .= "<Files ~ \".*\">\n";
                $htaccess_content .= "    Order Allow,Deny\n";
                $htaccess_content .= "    Deny from all\n";
                $htaccess_content .= "</Files>\n";
                
                file_put_contents($htaccess_file, $htaccess_content);
            }
            
            // Create index.php file for extra security
            $index_file = trailingslashit($log_dir) . 'index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, "<?php\n// Silence is golden.");
            }
        }
        
        $this->log_file = trailingslashit($log_dir) . 'acf-php-json-converter.log';
        
        // Apply filters to allow customization of logger settings
        $this->max_file_size = apply_filters('acf_php_json_converter_max_log_file_size', $this->max_file_size);
        $this->max_files = apply_filters('acf_php_json_converter_max_log_files', $this->max_files);
        $this->max_db_entries = apply_filters('acf_php_json_converter_max_db_entries', $this->max_db_entries);
        $this->max_log_age = apply_filters('acf_php_json_converter_max_log_age', $this->max_log_age);
        
        // Schedule log cleanup if not already scheduled
        if (!wp_next_scheduled('acf_php_json_converter_log_cleanup')) {
            wp_schedule_event(time(), 'daily', 'acf_php_json_converter_log_cleanup');
        }
        
        // Initialize error statistics if they don't exist
        if (false === get_option($this->error_stats_option)) {
            $this->initialize_error_stats();
        }
    }

    /**
     * Log a message.
     *
     * @since    1.0.0
     * @param    string    $message    Message to log.
     * @param    string    $level      Log level (error, warning, info, debug).
     * @param    array     $context    Additional context data.
     * @return   bool      True on success, false on failure.
     */
    public function log($message, $level = 'info', $context = array()) {
        // Check if we should log this message based on level
        if (!$this->should_log($level)) {
            return false;
        }

        // Format the log entry
        $log_entry = $this->format_log_entry($message, $level, $context);

        // Store the log entry
        $file_result = $this->write_log_to_file($log_entry);
        $db_result = $this->write_log_to_db($log_entry);
        
        return ($file_result || $db_result);
    }

    /**
     * Log an error message.
     *
     * @since    1.0.0
     * @param    string    $message    Error message.
     * @param    array     $context    Additional context data.
     * @return   bool      True on success, false on failure.
     */
    public function error($message, $context = array()) {
        return $this->log($message, 'error', $context);
    }

    /**
     * Log a warning message.
     *
     * @since    1.0.0
     * @param    string    $message    Warning message.
     * @param    array     $context    Additional context data.
     * @return   bool      True on success, false on failure.
     */
    public function warning($message, $context = array()) {
        return $this->log($message, 'warning', $context);
    }

    /**
     * Log an info message.
     *
     * @since    1.0.0
     * @param    string    $message    Info message.
     * @param    array     $context    Additional context data.
     * @return   bool      True on success, false on failure.
     */
    public function info($message, $context = array()) {
        return $this->log($message, 'info', $context);
    }

    /**
     * Log a debug message.
     *
     * @since    1.0.0
     * @param    string    $message    Debug message.
     * @param    array     $context    Additional context data.
     * @return   bool      True on success, false on failure.
     */
    public function debug($message, $context = array()) {
        return $this->log($message, 'debug', $context);
    }

    /**
     * Check if a message should be logged based on level.
     *
     * @since    1.0.0
     * @param    string    $level    Log level.
     * @return   bool      True if should log, false otherwise.
     */
    private function should_log($level) {
        // If level doesn't exist, default to error
        if (!isset($this->log_levels[$level])) {
            $level = 'error';
        }

        // If current level doesn't exist, default to error
        if (!isset($this->log_levels[$this->current_level])) {
            $this->current_level = 'error';
        }

        // Log if level is less than or equal to current level
        return $this->log_levels[$level] <= $this->log_levels[$this->current_level];
    }

    /**
     * Format a log entry.
     *
     * @since    1.0.0
     * @param    string    $message    Message to log.
     * @param    string    $level      Log level.
     * @param    array     $context    Additional context data.
     * @return   array     Formatted log entry.
     */
    private function format_log_entry($message, $level, $context) {
        // Get backtrace information
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = isset($backtrace[2]) ? $backtrace[2] : array();
        
        // Extract file and line information
        $file = isset($caller['file']) ? basename($caller['file']) : 'unknown';
        $line = isset($caller['line']) ? $caller['line'] : 0;
        $function = isset($caller['function']) ? $caller['function'] : 'unknown';
        $class = isset($caller['class']) ? $caller['class'] : '';
        
        // Format caller information
        $caller_info = $class ? "$class::$function" : $function;
        
        // Add request information to context
        $context['request'] = array(
            'url' => isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '',
            'method' => isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field($_SERVER['REQUEST_METHOD']) : '',
            'ip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '',
        );
        
        // Add user information if available
        if (function_exists('wp_get_current_user') && is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $context['user'] = array(
                'id' => $current_user->ID,
                'login' => $current_user->user_login,
            );
        }
        
        return array(
            'timestamp' => current_time('mysql'),
            'level'     => $level,
            'message'   => $message,
            'context'   => $context,
            'file'      => $file,
            'line'      => $line,
            'caller'    => $caller_info,
        );
    }

    /**
     * Write a log entry to file.
     *
     * @since    1.0.0
     * @param    array     $log_entry    Log entry.
     * @return   bool      True on success, false on failure.
     */
    private function write_log_to_file($log_entry) {
        // Check if log file is too large
        if (file_exists($this->log_file) && filesize($this->log_file) > $this->max_file_size) {
            $this->rotate_logs();
        }
        
        // Format log entry for file
        $formatted_entry = $this->format_log_entry_for_file($log_entry);
        
        // Write to log file
        $result = file_put_contents($this->log_file, $formatted_entry, FILE_APPEND);
        
        return ($result !== false);
    }

    /**
     * Write a log entry to database.
     *
     * @since    1.0.0
     * @param    array     $log_entry    Log entry.
     * @return   bool      True on success, false on failure.
     */
    private function write_log_to_db($log_entry) {
        // Get existing logs
        $logs = get_option($this->option_name, array());
        
        // Add new log entry
        array_unshift($logs, $log_entry);
        
        // Limit number of logs
        if (count($logs) > $this->max_db_entries) {
            $logs = array_slice($logs, 0, $this->max_db_entries);
        }
        
        // Update error statistics for errors and warnings
        if (in_array($log_entry['level'], array('error', 'warning'))) {
            $this->update_error_stats($log_entry);
        }
        
        // Update logs in database
        return update_option($this->option_name, $logs);
    }

    /**
     * Format a log entry for file output.
     *
     * @since    1.0.0
     * @param    array     $log_entry    Log entry.
     * @return   string    Formatted log entry.
     */
    private function format_log_entry_for_file($log_entry) {
        // Format context as JSON
        $context_json = wp_json_encode($log_entry['context']);
        
        // Format log entry
        $formatted_entry = sprintf(
            "[%s] [%s] [%s:%d] [%s] %s %s\n",
            $log_entry['timestamp'],
            strtoupper($log_entry['level']),
            $log_entry['file'],
            $log_entry['line'],
            $log_entry['caller'],
            $log_entry['message'],
            $context_json ? "Context: $context_json" : ""
        );
        
        return $formatted_entry;
    }

    /**
     * Rotate log files.
     *
     * @since    1.0.0
     * @return   bool      True on success, false on failure.
     */
    private function rotate_logs() {
        // Get log directory
        $log_dir = dirname($this->log_file);
        
        // Remove oldest log file if max files reached
        $oldest_log = trailingslashit($log_dir) . 'acf-php-json-converter.' . $this->max_files . '.log';
        if (file_exists($oldest_log)) {
            @unlink($oldest_log);
        }
        
        // Rotate existing log files
        for ($i = $this->max_files - 1; $i >= 1; $i--) {
            $old_log = trailingslashit($log_dir) . 'acf-php-json-converter.' . $i . '.log';
            $new_log = trailingslashit($log_dir) . 'acf-php-json-converter.' . ($i + 1) . '.log';
            
            if (file_exists($old_log)) {
                @rename($old_log, $new_log);
            }
        }
        
        // Rename current log file
        $new_log = trailingslashit($log_dir) . 'acf-php-json-converter.1.log';
        @rename($this->log_file, $new_log);
        
        return true;
    }

    /**
     * Get all logs from database.
     *
     * @since    1.0.0
     * @param    string    $level    Optional. Filter by log level.
     * @param    int       $limit    Optional. Limit number of logs.
     * @return   array     Logs.
     */
    public function get_logs($level = '', $limit = 100) {
        // Get logs from database
        $logs = get_option($this->option_name, array());
        
        // Filter by level if specified
        if ($level && isset($this->log_levels[$level])) {
            $logs = array_filter($logs, function($log) use ($level) {
                return $log['level'] === $level;
            });
        }
        
        // Limit number of logs
        if ($limit > 0 && count($logs) > $limit) {
            $logs = array_slice($logs, 0, $limit);
        }
        
        return $logs;
    }

    /**
     * Get log file content.
     *
     * @since    1.0.0
     * @param    int       $file_number    Log file number (0 for current, 1-5 for rotated logs).
     * @param    int       $lines          Optional. Number of lines to get. -1 for all lines.
     * @return   string    Log file content.
     */
    public function get_log_file_content($file_number = 0, $lines = -1) {
        // Determine log file path
        $log_file = $this->log_file;
        if ($file_number > 0) {
            $log_dir = dirname($this->log_file);
            $log_file = trailingslashit($log_dir) . 'acf-php-json-converter.' . $file_number . '.log';
        }
        
        // Check if file exists
        if (!file_exists($log_file)) {
            return '';
        }
        
        // Get file content
        if ($lines <= 0) {
            // Get all lines
            return file_get_contents($log_file);
        } else {
            // Get specific number of lines
            $content = '';
            $file = new \SplFileObject($log_file, 'r');
            $file->seek(PHP_INT_MAX); // Seek to end of file
            $total_lines = $file->key(); // Get total lines
            
            // Calculate starting line
            $start_line = max(0, $total_lines - $lines);
            
            // Read lines
            $file->seek($start_line);
            while (!$file->eof()) {
                $content .= $file->fgets();
            }
            
            return $content;
        }
    }

    /**
     * Clear logs from database.
     *
     * @since    1.0.0
     * @param    string    $level    Optional. Clear only logs of this level.
     * @return   bool      True on success, false on failure.
     */
    public function clear_logs($level = '') {
        if (empty($level)) {
            // Clear all logs
            return delete_option($this->option_name);
        } else {
            // Clear logs of specific level
            $logs = get_option($this->option_name, array());
            
            $logs = array_filter($logs, function($log) use ($level) {
                return $log['level'] !== $level;
            });
            
            return update_option($this->option_name, $logs);
        }
    }

    /**
     * Clear log files.
     *
     * @since    1.0.0
     * @return   bool      True on success, false on failure.
     */
    public function clear_log_files() {
        // Get log directory
        $log_dir = dirname($this->log_file);
        
        // Clear current log file
        if (file_exists($this->log_file)) {
            @unlink($this->log_file);
        }
        
        // Clear rotated log files
        for ($i = 1; $i <= $this->max_files; $i++) {
            $log_file = trailingslashit($log_dir) . 'acf-php-json-converter.' . $i . '.log';
            if (file_exists($log_file)) {
                @unlink($log_file);
            }
        }
        
        return true;
    }

    /**
     * Get log level name.
     *
     * @since    1.0.0
     * @param    int       $level_value    Log level value.
     * @return   string    Log level name.
     */
    public function get_level_name($level_value) {
        $levels = array_flip($this->log_levels);
        
        return isset($levels[$level_value]) ? $levels[$level_value] : 'unknown';
    }

    /**
     * Get available log levels.
     *
     * @since    1.0.0
     * @return   array    Log levels.
     */
    public function get_log_levels() {
        return $this->log_levels;
    }

    /**
     * Get current log level.
     *
     * @since    1.0.0
     * @return   string    Current log level.
     */
    public function get_current_level() {
        return $this->current_level;
    }

    /**
     * Set current log level.
     *
     * @since    1.0.0
     * @param    string    $level    Log level.
     * @return   bool      True on success, false on failure.
     */
    public function set_current_level($level) {
        if (!isset($this->log_levels[$level])) {
            return false;
        }
        
        $this->current_level = $level;
        
        // Update settings
        $settings = get_option('acf_php_json_converter_settings', array());
        $settings['logging_level'] = $level;
        
        return update_option('acf_php_json_converter_settings', $settings);
    }

    /**
     * Set log level (alias for set_current_level).
     *
     * @since    1.0.0
     * @param    string    $level    Log level.
     * @return   bool      True on success, false on failure.
     */
    public function set_log_level($level) {
        return $this->set_current_level($level);
    }

    /**
     * Get recent logs from database.
     *
     * @since    1.0.0
     * @param    int       $limit    Number of logs to retrieve.
     * @return   array     Recent logs.
     */
    public function get_recent_logs($limit = 100) {
        return $this->get_logs('', $limit);
    }

    /**
     * Clear all logs (both database and files).
     *
     * @since    1.0.0
     * @return   bool      True on success, false on failure.
     */
    public function clear_log() {
        $db_result = $this->clear_logs();
        $file_result = $this->clear_log_files();
        
        return ($db_result && $file_result);
    }
    /**
     * Initialize error statistics.
     *
     * @since    1.0.0
     * @return   bool      True on success, false on failure.
     */
    private function initialize_error_stats() {
        $stats = array(
            'error_count' => array(
                'error'   => 0,
                'warning' => 0,
                'info'    => 0,
                'debug'   => 0,
            ),
            'first_error_time' => current_time('mysql'),
            'last_error_time'  => current_time('mysql'),
            'most_frequent'    => array(),
        );
        
        return update_option($this->error_stats_option, $stats);
    }

    /**
     * Update error statistics.
     *
     * @since    1.0.0
     * @param    array     $log_entry    Log entry.
     * @return   bool      True on success, false on failure.
     */
    private function update_error_stats($log_entry) {
        // Only track errors and warnings
        if (!in_array($log_entry['level'], array('error', 'warning'))) {
            return false;
        }
        
        // Get current stats
        $stats = get_option($this->error_stats_option, array());
        
        // If stats don't exist, initialize them
        if (empty($stats)) {
            $this->initialize_error_stats();
            $stats = get_option($this->error_stats_option, array());
        }
        
        // Update error count
        if (isset($stats['error_count'][$log_entry['level']])) {
            $stats['error_count'][$log_entry['level']]++;
        } else {
            $stats['error_count'][$log_entry['level']] = 1;
        }
        
        // Update last error time
        $stats['last_error_time'] = $log_entry['timestamp'];
        
        // Update most frequent errors
        $error_key = md5($log_entry['message'] . $log_entry['file'] . $log_entry['line']);
        
        if (isset($stats['most_frequent'][$error_key])) {
            $stats['most_frequent'][$error_key]['count']++;
            $stats['most_frequent'][$error_key]['last_time'] = $log_entry['timestamp'];
        } else {
            $stats['most_frequent'][$error_key] = array(
                'message'   => $log_entry['message'],
                'level'     => $log_entry['level'],
                'file'      => $log_entry['file'],
                'line'      => $log_entry['line'],
                'count'     => 1,
                'first_time' => $log_entry['timestamp'],
                'last_time'  => $log_entry['timestamp'],
            );
        }
        
        // Limit most frequent errors to top 10
        if (count($stats['most_frequent']) > 10) {
            // Sort by count in descending order
            uasort($stats['most_frequent'], function($a, $b) {
                return $b['count'] - $a['count'];
            });
            
            // Keep only top 10
            $stats['most_frequent'] = array_slice($stats['most_frequent'], 0, 10, true);
        }
        
        return update_option($this->error_stats_option, $stats);
    }

    /**
     * Get error statistics.
     *
     * @since    1.0.0
     * @return   array     Error statistics.
     */
    public function get_error_stats() {
        return get_option($this->error_stats_option, array());
    }

    /**
     * Reset error statistics.
     *
     * @since    1.0.0
     * @return   bool      True on success, false on failure.
     */
    public function reset_error_stats() {
        return $this->initialize_error_stats();
    }

    /**
     * Log an exception.
     *
     * @since    1.0.0
     * @param    \Exception|\Throwable $exception    Exception to log.
     * @param    string                $level        Log level (error, warning, info, debug).
     * @param    array                 $context      Additional context data.
     * @return   bool                  True on success, false on failure.
     */
    public function log_exception($exception, $level = 'error', $context = array()) {
        // Add exception details to context
        $context['exception'] = array(
            'class'     => get_class($exception),
            'code'      => $exception->getCode(),
            'message'   => $exception->getMessage(),
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'trace'     => $exception->getTraceAsString(),
        );
        
        // Log the exception
        return $this->log(
            sprintf('Exception: %s: %s', get_class($exception), $exception->getMessage()),
            $level,
            $context
        );
    }

    /**
     * Clean up old logs.
     *
     * @since    1.0.0
     * @return   bool      True on success, false on failure.
     */
    public function cleanup_old_logs() {
        // Get logs from database
        $logs = get_option($this->option_name, array());
        
        if (empty($logs)) {
            return true;
        }
        
        // Calculate cutoff time
        $cutoff_time = strtotime('-' . $this->max_log_age . ' days');
        
        // Filter logs to keep only recent ones
        $filtered_logs = array_filter($logs, function($log) use ($cutoff_time) {
            $log_time = strtotime($log['timestamp']);
            return $log_time >= $cutoff_time;
        });
        
        // Update logs in database if any were removed
        if (count($filtered_logs) < count($logs)) {
            return update_option($this->option_name, $filtered_logs);
        }
        
        return true;
    }

    /**
     * Get log file size.
     *
     * @since    1.0.0
     * @param    int       $file_number    Log file number (0 for current, 1-5 for rotated logs).
     * @return   int       File size in bytes.
     */
    public function get_log_file_size($file_number = 0) {
        // Determine log file path
        $log_file = $this->log_file;
        if ($file_number > 0) {
            $log_dir = dirname($this->log_file);
            $log_file = trailingslashit($log_dir) . 'acf-php-json-converter.' . $file_number . '.log';
        }
        
        // Check if file exists
        if (!file_exists($log_file)) {
            return 0;
        }
        
        return filesize($log_file);
    }

    /**
     * Get total log size.
     *
     * @since    1.0.0
     * @return   int       Total size of all log files in bytes.
     */
    public function get_total_log_size() {
        $total_size = 0;
        
        // Add current log file size
        $total_size += $this->get_log_file_size();
        
        // Add rotated log file sizes
        for ($i = 1; $i <= $this->max_files; $i++) {
            $total_size += $this->get_log_file_size($i);
        }
        
        return $total_size;
    }

    /**
     * Format file size for display.
     *
     * @since    1.0.0
     * @param    int       $size    Size in bytes.
     * @return   string    Formatted size.
     */
    public function format_file_size($size) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
}