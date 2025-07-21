<?php
/**
 * Tests for the Logger utility class.
 *
 * @package ACF_PHP_JSON_Converter
 */

use ACF_PHP_JSON_Converter\Utilities\Logger;

/**
 * Logger test case.
 */
class LoggerTest extends WP_UnitTestCase {

    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Original log level.
     *
     * @var string
     */
    protected $original_level;

    /**
     * Set up.
     */
    public function setUp() {
        parent::setUp();
        $this->logger = new Logger();
        
        // Store original log level
        $this->original_level = $this->logger->get_current_level();
        
        // Set log level to debug for testing
        $this->logger->set_current_level('debug');
    }

    /**
     * Tear down.
     */
    public function tearDown() {
        // Restore original log level
        $this->logger->set_current_level($this->original_level);
        
        // Clear test logs
        $this->logger->clear_logs();
        $this->logger->clear_log_files();
        
        parent::tearDown();
    }

    /**
     * Test log method with different levels.
     */
    public function test_log_levels() {
        // Log messages with different levels
        $this->assertTrue($this->logger->log('Error message', 'error'));
        $this->assertTrue($this->logger->log('Warning message', 'warning'));
        $this->assertTrue($this->logger->log('Info message', 'info'));
        $this->assertTrue($this->logger->log('Debug message', 'debug'));
        
        // Get logs
        $logs = $this->logger->get_logs('', 10);
        
        // Check if logs were created
        $this->assertCount(4, $logs);
        
        // Check log levels
        $this->assertEquals('debug', $logs[0]['level']);
        $this->assertEquals('info', $logs[1]['level']);
        $this->assertEquals('warning', $logs[2]['level']);
        $this->assertEquals('error', $logs[3]['level']);
    }

    /**
     * Test convenience methods for logging.
     */
    public function test_convenience_methods() {
        // Log messages using convenience methods
        $this->assertTrue($this->logger->error('Error message'));
        $this->assertTrue($this->logger->warning('Warning message'));
        $this->assertTrue($this->logger->info('Info message'));
        $this->assertTrue($this->logger->debug('Debug message'));
        
        // Get logs
        $logs = $this->logger->get_logs('', 10);
        
        // Check if logs were created
        $this->assertCount(4, $logs);
        
        // Check log levels and messages
        $this->assertEquals('debug', $logs[0]['level']);
        $this->assertEquals('Debug message', $logs[0]['message']);
        
        $this->assertEquals('info', $logs[1]['level']);
        $this->assertEquals('Info message', $logs[1]['message']);
        
        $this->assertEquals('warning', $logs[2]['level']);
        $this->assertEquals('Warning message', $logs[2]['message']);
        
        $this->assertEquals('error', $logs[3]['level']);
        $this->assertEquals('Error message', $logs[3]['message']);
    }

    /**
     * Test log level filtering.
     */
    public function test_log_level_filtering() {
        // Set log level to warning
        $this->logger->set_current_level('warning');
        
        // Log messages with different levels
        $this->assertTrue($this->logger->error('Error message'));
        $this->assertTrue($this->logger->warning('Warning message'));
        $this->assertFalse($this->logger->info('Info message')); // Should not be logged
        $this->assertFalse($this->logger->debug('Debug message')); // Should not be logged
        
        // Get logs
        $logs = $this->logger->get_logs('', 10);
        
        // Check if only error and warning logs were created
        $this->assertCount(2, $logs);
        
        // Check log levels
        $this->assertEquals('warning', $logs[0]['level']);
        $this->assertEquals('error', $logs[1]['level']);
    }

    /**
     * Test log context data.
     */
    public function test_log_context() {
        // Log with context data
        $context = array(
            'key1' => 'value1',
            'key2' => array(
                'nested_key' => 'nested_value',
            ),
        );
        
        $this->assertTrue($this->logger->log('Test message with context', 'info', $context));
        
        // Get logs
        $logs = $this->logger->get_logs('', 1);
        
        // Check if context data was included
        $this->assertArrayHasKey('context', $logs[0]);
        $this->assertArrayHasKey('key1', $logs[0]['context']);
        $this->assertEquals('value1', $logs[0]['context']['key1']);
        $this->assertArrayHasKey('key2', $logs[0]['context']);
        $this->assertArrayHasKey('nested_key', $logs[0]['context']['key2']);
        $this->assertEquals('nested_value', $logs[0]['context']['key2']['nested_key']);
    }

    /**
     * Test get_logs method with filtering.
     */
    public function test_get_logs_filtering() {
        // Log messages with different levels
        $this->logger->error('Error message 1');
        $this->logger->error('Error message 2');
        $this->logger->warning('Warning message');
        $this->logger->info('Info message');
        
        // Get only error logs
        $error_logs = $this->logger->get_logs('error');
        
        // Check if only error logs were returned
        $this->assertCount(2, $error_logs);
        $this->assertEquals('error', $error_logs[0]['level']);
        $this->assertEquals('error', $error_logs[1]['level']);
        
        // Get logs with limit
        $limited_logs = $this->logger->get_logs('', 2);
        
        // Check if only the specified number of logs were returned
        $this->assertCount(2, $limited_logs);
    }

    /**
     * Test clear_logs method.
     */
    public function test_clear_logs() {
        // Log messages with different levels
        $this->logger->error('Error message');
        $this->logger->warning('Warning message');
        $this->logger->info('Info message');
        
        // Clear only error logs
        $this->assertTrue($this->logger->clear_logs('error'));
        
        // Get logs
        $logs = $this->logger->get_logs();
        
        // Check if error logs were cleared
        $this->assertCount(2, $logs);
        $this->assertNotEquals('error', $logs[0]['level']);
        $this->assertNotEquals('error', $logs[1]['level']);
        
        // Clear all logs
        $this->assertTrue($this->logger->clear_logs());
        
        // Get logs
        $logs = $this->logger->get_logs();
        
        // Check if all logs were cleared
        $this->assertEmpty($logs);
    }

    /**
     * Test log file creation and content.
     */
    public function test_log_file_content() {
        // Log a message
        $this->logger->error('Test log file message');
        
        // Get log file content
        $content = $this->logger->get_log_file_content();
        
        // Check if log file contains the message
        $this->assertNotEmpty($content);
        $this->assertStringContainsString('Test log file message', $content);
        $this->assertStringContainsString('[ERROR]', $content);
    }

    /**
     * Test log level management.
     */
    public function test_log_level_management() {
        // Get available log levels
        $log_levels = $this->logger->get_log_levels();
        
        // Check if log levels are defined
        $this->assertArrayHasKey('error', $log_levels);
        $this->assertArrayHasKey('warning', $log_levels);
        $this->assertArrayHasKey('info', $log_levels);
        $this->assertArrayHasKey('debug', $log_levels);
        
        // Set log level to info
        $this->assertTrue($this->logger->set_current_level('info'));
        
        // Check if log level was set
        $this->assertEquals('info', $this->logger->get_current_level());
        
        // Try to set invalid log level
        $this->assertFalse($this->logger->set_current_level('invalid'));
        
        // Check if log level remains unchanged
        $this->assertEquals('info', $this->logger->get_current_level());
    }

    /**
     * Test get_level_name method.
     */
    public function test_get_level_name() {
        // Get level names
        $this->assertEquals('error', $this->logger->get_level_name(0));
        $this->assertEquals('warning', $this->logger->get_level_name(1));
        $this->assertEquals('info', $this->logger->get_level_name(2));
        $this->assertEquals('debug', $this->logger->get_level_name(3));
        
        // Get invalid level name
        $this->assertEquals('unknown', $this->logger->get_level_name(99));
    }
}