<?php
/**
 * Test file for error handling functionality
 * This file can be run to test the error handling system
 */

// Include WordPress if not already loaded
if (!defined('ABSPATH')) {
    // This would need to be adjusted based on your WordPress installation path
    require_once('../../../wp-load.php');
}

// Include the error handler
require_once('includes/utilities/class-logger.php');
require_once('includes/utilities/class-error-handler.php');
require_once('includes/utilities/class-progress-tracker.php');

use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Error_Handler;
use ACF_PHP_JSON_Converter\Utilities\Progress_Tracker;

// Test the error handler
echo "<h1>Testing ACF PHP-JSON Converter Error Handling</h1>\n";

// Initialize logger and error handler
$logger = new Logger();
$settings = array(
    'error_display_mode' => 'both',
    'max_error_notices' => 5,
    'enable_error_recovery' => true,
    'log_user_actions' => true
);
$error_handler = new Error_Handler($logger, $settings);

echo "<h2>1. Testing Basic Error Handling</h2>\n";

// Test basic error
$error_result = $error_handler->handle_error(
    'test_error',
    'This is a test error message',
    array('test_context' => 'test_value'),
    'error'
);

echo "<pre>";
print_r($error_result);
echo "</pre>";

echo "<h2>2. Testing Success Handling</h2>\n";

// Test success
$success_result = $error_handler->handle_success(
    'Test operation completed successfully!',
    array('items_processed' => 5),
    array('Next step: Review results')
);

echo "<pre>";
print_r($success_result);
echo "</pre>";

echo "<h2>3. Testing Warning Handling</h2>\n";

// Test warning
$warning_result = $error_handler->handle_warning(
    'This is a test warning',
    array('warning_context' => 'test'),
    array('Consider reviewing the configuration')
);

echo "<pre>";
print_r($warning_result);
echo "</pre>";

echo "<h2>4. Testing Progress Tracker</h2>\n";

// Test progress tracker
$progress_tracker = new Progress_Tracker('test_operation_123', 10, 'Test Operation', $logger);
$progress_tracker->start('Starting test operation...');

for ($i = 1; $i <= 10; $i++) {
    $progress_tracker->update_progress($i, "Processing item $i of 10");
    
    if ($i === 3) {
        $progress_tracker->add_warning("Warning at item $i");
    }
    
    if ($i === 7) {
        $progress_tracker->add_error("Error at item $i", array('item_id' => $i));
    }
    
    // Simulate some processing time
    usleep(100000); // 0.1 seconds
}

$progress_tracker->complete('Test operation completed!', true);

echo "<h3>Progress Summary:</h3>";
echo "<pre>";
print_r($progress_tracker->get_summary());
echo "</pre>";

echo "<h2>5. Testing Batch Operation</h2>\n";

// Test batch operation
$test_items = array('item1', 'item2', 'item3', 'item4', 'item5');

$processor = function($item, $index, $options) {
    // Simulate processing
    if ($item === 'item3') {
        return array(
            'success' => false,
            'message' => "Failed to process $item"
        );
    }
    
    if ($item === 'item4') {
        return array(
            'success' => true,
            'message' => "Successfully processed $item",
            'warning' => "Warning for $item"
        );
    }
    
    return array(
        'success' => true,
        'message' => "Successfully processed $item"
    );
};

$batch_result = $error_handler->handle_batch_operation(
    'Test Batch Operation',
    $test_items,
    $processor
);

echo "<h3>Batch Operation Results:</h3>";
echo "<pre>";
print_r($batch_result);
echo "</pre>";

echo "<h2>6. Testing Error Recovery Suggestions</h2>\n";

// Test error recovery suggestions
$test_errors = array(
    array('error_code' => 'file_not_found'),
    array('error_code' => 'file_not_found'),
    array('error_code' => 'permission_denied'),
    array('error_code' => 'conversion_failed')
);

$recovery_suggestions = $error_handler->get_error_recovery_suggestions($test_errors);

echo "<h3>Recovery Suggestions:</h3>";
echo "<pre>";
print_r($recovery_suggestions);
echo "</pre>";

echo "<h2>7. Testing Queued Messages</h2>\n";

$queued_messages = $error_handler->get_queued_messages();

echo "<h3>Queued Messages:</h3>";
echo "<pre>";
print_r($queued_messages);
echo "</pre>";

echo "<h2>Testing Complete!</h2>\n";
echo "<p>Check the error log for detailed logging information.</p>\n";
?>