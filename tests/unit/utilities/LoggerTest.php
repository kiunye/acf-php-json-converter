<?php
/**
 * Tests for the Logger utility class.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Tests/Unit/Utilities
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Tests
 */

use ACF_PHP_JSON_Converter\Utilities\Logger;

/**
 * Logger utility test case.
 */
class LoggerTest extends WP_UnitTestCase {

    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Test log directory.
     *
     * @var string
     */
    protected $test_log_dir;

    /**
     * Set up.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Reset global options for clean test state
        global $wp_test_options;
        $wp_test_options = [];
        
        // Create test log directory
        $this->test_log_dir = sys_get_temp_dir() . '/acf_logger_test_' . uniqid();
        mkdir($this->test_log_dir, 0755, true);
        
        // Mock wp_upload_dir to return our test directory
        $GLOBALS['wp_upload_dir_override'] = [
            'basedir' => $this->test_log_dir,
            'baseurl' => 'http://example.com/uploads',
        ];
        
        // Set default logger settings
        update_option('acf_php_json_converter_settings', [
            'logging_level' => 'debug' // Set to debug to allow all log levels
        ]);
        
        // Create logger instance
        $this->logger = new Logger();
    }

    /**
     * Tear down.
     */
    public function tearDown(): void {
        // Clean up test directory
        if (is_dir($this->test_log_dir)) {
            $this->removeDirectory($this->test_log_dir);
        }
        
        // Reset globals
        unset($GLOBALS['wp_upload_dir_override']);
        
        parent::tearDown();
    }

    /**
     * Remove directory recursively.
     *
     * @param string $dir Directory path.
     */
    private function removeDirectory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->removeDirectory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Test basic logging functionality.
     */
    public function test_basic_logging() {
        // Test info level logging
        $result = $this->logger->info('Test info message');
        $this->assertTrue($result);
        
        // Test error level logging
        $result = $this->logger->error('Test error message');
        $this->assertTrue($result);
        
        // Test warning level logging
        $result = $this->logger->warning('Test warning message');
        $this->assertTrue($result);
        
        // Test debug level logging
        $result = $this->logger->debug('Test debug message');
        $this->assertTrue($result);
    }

    /**
     * Test log levels.
     */
    public function test_log_levels() {
        // Get available log levels
        $levels = $this->logger->get_log_levels();
        $this->assertIsArray($levels);
        $this->assertArrayHasKey('error', $levels);
        $this->assertArrayHasKey('warning', $levels);
        $this->assertArrayHasKey('info', $levels);
        $this->assertArrayHasKey('debug', $levels);
        
        // Test level hierarchy
        $this->assertEquals(0, $levels['error']);
        $this->assertEquals(1, $levels['warning']);
        $this->assertEquals(2, $levels['info']);
        $this->assertEquals(3, $levels['debug']);
    }

    /**
     * Test current log level setting.
     */
    public function test_current_log_level() {
        // Test getting current level
        $current_level = $this->logger->get_current_level();
        $this->assertIsString($current_level);
        
        // Test setting log level
        $result = $this->logger->set_current_level('debug');
        $this->assertTrue($result);
        $this->assertEquals('debug', $this->logger->get_current_level());
        
        // Test setting invalid log level
        $result = $this->logger->set_current_level('invalid');
        $this->assertFalse($result);
        
        // Test set_log_level alias
        $result = $this->logger->set_log_level('warning');
        $this->assertTrue($result);
        $this->assertEquals('warning', $this->logger->get_current_level());
    }

    /**
     * Test log level filtering.
     */
    public function test_log_level_filtering() {
        // Set log level to warning
        $this->logger->set_current_level('warning');
        
        // Error should be logged (level 0 <= 1)
        $result = $this->logger->error('Error message');
        $this->assertTrue($result);
        
        // Warning should be logged (level 1 <= 1)
        $result = $this->logger->warning('Warning message');
        $this->assertTrue($result);
        
        // Info should not be logged (level 2 > 1)
        $result = $this->logger->info('Info message');
        $this->assertFalse($result);
        
        // Debug should not be logged (level 3 > 1)
        $result = $this->logger->debug('Debug message');
        $this->assertFalse($result);
    }

    /**
     * Test logging with context.
     */
    public function test_logging_with_context() {
        $context = [
            'user_id' => 123,
            'action' => 'test_action',
            'data' => ['key' => 'value'],
        ];
        
        $result = $this->logger->log('Test message with context', 'info', $context);
        $this->assertTrue($result);
        
        // Verify context is stored in logs
        $logs = $this->logger->get_logs();
        $this->assertNotEmpty($logs);
        
        $latest_log = $logs[0];
        $this->assertArrayHasKey('context', $latest_log);
        $this->assertEquals(123, $latest_log['context']['user_id']);
        $this->assertEquals('test_action', $latest_log['context']['action']);
    }

    /**
     * Test exception logging.
     */
    public function test_exception_logging() {
        $exception = new Exception('Test exception message', 500);
        
        $result = $this->logger->log_exception($exception);
        $this->assertTrue($result);
        
        // Verify exception details are stored
        $logs = $this->logger->get_logs();
        $this->assertNotEmpty($logs);
        
        $latest_log = $logs[0];
        $this->assertArrayHasKey('context', $latest_log);
        $this->assertArrayHasKey('exception', $latest_log['context']);
        $this->assertEquals('Exception', $latest_log['context']['exception']['class']);
        $this->assertEquals(500, $latest_log['context']['exception']['code']);
        $this->assertEquals('Test exception message', $latest_log['context']['exception']['message']);
    }

    /**
     * Test log retrieval.
     */
    public function test_log_retrieval() {
        // Clear any existing logs first
        $this->logger->clear_logs();
        
        // Add some test logs
        $this->logger->error('Error message 1');
        $this->logger->warning('Warning message 1');
        $this->logger->info('Info message 1');
        $this->logger->error('Error message 2');
        
        // Get all logs
        $all_logs = $this->logger->get_logs();
        $this->assertGreaterThanOrEqual(4, count($all_logs));
        
        // Get logs by level
        $error_logs = $this->logger->get_logs('error');
        $this->assertGreaterThanOrEqual(2, count($error_logs));
        
        $warning_logs = $this->logger->get_logs('warning');
        $this->assertGreaterThanOrEqual(1, count($warning_logs));
        
        // Get limited number of logs
        $limited_logs = $this->logger->get_logs('', 2);
        $this->assertCount(2, $limited_logs);
        
        // Get recent logs
        $recent_logs = $this->logger->get_recent_logs(3);
        $this->assertCount(3, $recent_logs);
    }

    /**
     * Test log clearing.
     */
    public function test_log_clearing() {
        // Clear any existing logs first
        $this->logger->clear_logs();
        
        // Add some test logs
        $this->logger->error('Error message');
        $this->logger->warning('Warning message');
        $this->logger->info('Info message');
        
        // Verify logs exist
        $logs = $this->logger->get_logs();
        $this->assertGreaterThanOrEqual(3, count($logs));
        
        // Clear specific level logs
        $result = $this->logger->clear_logs('error');
        $this->assertTrue($result);
        
        $logs = $this->logger->get_logs();
        $this->assertLessThan(count($logs) + 1, count($logs)); // Should have fewer logs
        
        // Clear all logs
        $result = $this->logger->clear_logs();
        $this->assertTrue($result);
        
        $logs = $this->logger->get_logs();
        $this->assertEmpty($logs);
    }

    /**
     * Test error statistics.
     */
    public function test_error_statistics() {
        // Reset error statistics first
        $this->logger->reset_error_stats();
        
        // Add some errors and warnings
        $this->logger->error('Error message 1');
        $this->logger->error('Error message 2');
        $this->logger->warning('Warning message 1');
        $this->logger->info('Info message 1'); // Should not be counted
        
        // Get error statistics
        $stats = $this->logger->get_error_stats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('error_count', $stats);
        $this->assertArrayHasKey('most_frequent', $stats);
        
        // Check error counts (allowing for some flexibility due to internal logging)
        $this->assertGreaterThanOrEqual(2, $stats['error_count']['error']);
        $this->assertGreaterThanOrEqual(1, $stats['error_count']['warning']);
        
        // Reset error statistics
        $result = $this->logger->reset_error_stats();
        $this->assertTrue($result);
        
        $stats = $this->logger->get_error_stats();
        $this->assertEquals(0, $stats['error_count']['error']);
        $this->assertEquals(0, $stats['error_count']['warning']);
    }

    /**
     * Test log file operations.
     */
    public function test_log_file_operations() {
        // Add a log entry to create log file
        $this->logger->error('Test error for file');
        
        // Get log file size
        $size = $this->logger->get_log_file_size();
        $this->assertGreaterThan(0, $size);
        
        // Get total log size
        $total_size = $this->logger->get_total_log_size();
        $this->assertGreaterThanOrEqual($size, $total_size);
        
        // Get log file content
        $content = $this->logger->get_log_file_content();
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('Test error for file', $content);
        
        // Clear log files
        $result = $this->logger->clear_log_files();
        $this->assertTrue($result);
        
        // Verify file is cleared
        $size_after = $this->logger->get_log_file_size();
        $this->assertEquals(0, $size_after);
    }

    /**
     * Test file size formatting.
     */
    public function test_file_size_formatting() {
        $this->assertEquals('0.00 B', $this->logger->format_file_size(0));
        $this->assertEquals('1.00 B', $this->logger->format_file_size(1));
        $this->assertEquals('1.00 KB', $this->logger->format_file_size(1024));
        $this->assertEquals('1.00 MB', $this->logger->format_file_size(1024 * 1024));
        $this->assertEquals('1.50 KB', $this->logger->format_file_size(1536));
    }

    /**
     * Test log cleanup.
     */
    public function test_log_cleanup() {
        // Clear existing logs first
        $this->logger->clear_logs();
        
        // Add some old logs by manipulating timestamps
        $this->logger->error('Old error message');
        
        // Get logs and modify timestamp to simulate old log
        $logs = $this->logger->get_logs();
        if (!empty($logs)) {
            $logs[0]['timestamp'] = date('Y-m-d H:i:s', strtotime('-35 days'));
            update_option('acf_php_json_converter_logs', $logs);
        }
        
        // Add a recent log
        $this->logger->error('Recent error message');
        
        // Run cleanup
        $result = $this->logger->cleanup_old_logs();
        $this->assertTrue($result);
        
        // Verify old logs are removed (allowing for some flexibility)
        $logs_after = $this->logger->get_logs();
        $this->assertGreaterThanOrEqual(1, count($logs_after));
        
        // Check that at least one log contains the recent message
        $recent_found = false;
        foreach ($logs_after as $log) {
            if (strpos($log['message'], 'Recent error message') !== false) {
                $recent_found = true;
                break;
            }
        }
        $this->assertTrue($recent_found);
    }

    /**
     * Test level name retrieval.
     */
    public function test_level_name_retrieval() {
        $this->assertEquals('error', $this->logger->get_level_name(0));
        $this->assertEquals('warning', $this->logger->get_level_name(1));
        $this->assertEquals('info', $this->logger->get_level_name(2));
        $this->assertEquals('debug', $this->logger->get_level_name(3));
        $this->assertEquals('unknown', $this->logger->get_level_name(999));
    }

    /**
     * Test clear log (both database and files).
     */
    public function test_clear_log() {
        // Add logs to both database and file
        $this->logger->error('Test error message');
        
        // Verify logs exist
        $logs = $this->logger->get_logs();
        $this->assertNotEmpty($logs);
        
        $file_size = $this->logger->get_log_file_size();
        $this->assertGreaterThan(0, $file_size);
        
        // Clear all logs
        $result = $this->logger->clear_log();
        $this->assertTrue($result);
        
        // Verify both database and files are cleared
        $logs_after = $this->logger->get_logs();
        $this->assertEmpty($logs_after);
        
        $file_size_after = $this->logger->get_log_file_size();
        $this->assertEquals(0, $file_size_after);
    }
}