<?php
/**
 * Unit tests for Admin Controller.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Tests
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ACF_PHP_JSON_Converter\Admin\Admin_Controller;
use ACF_PHP_JSON_Converter\Services\Scanner_Service;
use ACF_PHP_JSON_Converter\Services\Converter_Service;
use ACF_PHP_JSON_Converter\Services\File_Manager;
use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Security;
use ACF_PHP_JSON_Converter\Utilities\Error_Handler;

/**
 * Test Admin Controller functionality.
 */
class AdminControllerTest extends TestCase {

    /**
     * Admin Controller instance.
     *
     * @var Admin_Controller
     */
    private $admin_controller;

    /**
     * Mock Scanner Service.
     *
     * @var MockObject|Scanner_Service
     */
    private $mock_scanner;

    /**
     * Mock Converter Service.
     *
     * @var MockObject|Converter_Service
     */
    private $mock_converter;

    /**
     * Mock File Manager.
     *
     * @var MockObject|File_Manager
     */
    private $mock_file_manager;

    /**
     * Mock Logger.
     *
     * @var MockObject|Logger
     */
    private $mock_logger;

    /**
     * Mock Security.
     *
     * @var MockObject|Security
     */
    private $mock_security;

    /**
     * Mock Error Handler.
     *
     * @var MockObject|Error_Handler
     */
    private $mock_error_handler;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void {
        parent::setUp();

        // Create mocks
        $this->mock_scanner = $this->createMock(Scanner_Service::class);
        $this->mock_converter = $this->createMock(Converter_Service::class);
        $this->mock_file_manager = $this->createMock(File_Manager::class);
        $this->mock_logger = $this->createMock(Logger::class);
        $this->mock_security = $this->createMock(Security::class);
        $this->mock_error_handler = $this->createMock(Error_Handler::class);

        // Create admin controller instance
        $this->admin_controller = new Admin_Controller(
            $this->mock_scanner,
            $this->mock_converter,
            $this->mock_file_manager,
            $this->mock_logger,
            $this->mock_security,
            $this->mock_error_handler
        );
    }

    /**
     * Test admin menu registration with proper capabilities.
     */
    public function test_add_admin_menu_with_capabilities() {
        // Mock security check to return true
        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(true);

        // Call the method
        $result = $this->admin_controller->add_admin_menu();

        // Assert that the method completes without error
        $this->assertNull($result);
    }

    /**
     * Test admin menu registration without capabilities.
     */
    public function test_add_admin_menu_without_capabilities() {
        // Mock security check to return false
        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(false);

        // Call the method
        $result = $this->admin_controller->add_admin_menu();

        // Assert that the method returns early
        $this->assertNull($result);
    }

    /**
     * Test admin page rendering with proper capabilities.
     */
    public function test_render_admin_page_with_capabilities() {
        // Mock security check to return true
        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(true);

        // Mock logger to expect no error calls since template exists
        $this->mock_logger->expects($this->never())
            ->method('error');

        // Capture output
        ob_start();
        $this->admin_controller->render_admin_page();
        $output = ob_get_clean();

        // Assert that the method completes without throwing an exception
        $this->assertIsString($output);
    }

    /**
     * Test admin page rendering without capabilities.
     */
    public function test_render_admin_page_without_capabilities() {
        // Mock security check to return false
        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(false);

        // Expect wp_die to be called
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('You do not have sufficient permissions to access this page.');

        // Call the method
        $this->admin_controller->render_admin_page();
    }

    /**
     * Test asset enqueuing on correct page.
     */
    public function test_enqueue_assets_on_correct_page() {
        // Call the method with correct hook suffix
        $result = $this->admin_controller->enqueue_assets('tools_page_acf-php-json-converter');

        // Assert that the method completes without error
        $this->assertNull($result);
    }

    /**
     * Test asset enqueuing on incorrect page.
     */
    public function test_enqueue_assets_on_incorrect_page() {
        // Call the method with incorrect hook suffix
        $result = $this->admin_controller->enqueue_assets('edit.php');

        // Assert that the method returns early
        $this->assertNull($result);
    }

    /**
     * Test AJAX scan theme with valid request.
     */
    public function test_ajax_scan_theme_with_valid_request() {
        // Mock security checks to return true
        $this->mock_security->expects($this->once())
            ->method('verify_nonce')
            ->willReturn(true);

        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(true);

        // Mock $_POST data
        $_POST['nonce'] = 'test-nonce';

        // Expect JSON success response
        $this->expectOutputString('{"success":true,"data":{"message":"Scanning functionality will be implemented soon."}}');

        // Call the method
        $this->admin_controller->ajax_scan_theme();
    }

    /**
     * Test AJAX scan theme with invalid nonce.
     */
    public function test_ajax_scan_theme_with_invalid_nonce() {
        // Mock security checks
        $this->mock_security->expects($this->once())
            ->method('verify_nonce')
            ->willReturn(false);

        $this->mock_logger->expects($this->once())
            ->method('warning')
            ->with('AJAX request failed nonce verification');

        // Mock $_POST data
        $_POST['nonce'] = 'invalid-nonce';

        // Expect JSON error response
        $this->expectOutputString('{"success":false,"data":{"message":"Security check failed. Please refresh the page and try again."}}');

        // Call the method
        $this->admin_controller->ajax_scan_theme();
    }

    /**
     * Test AJAX scan theme without capabilities.
     */
    public function test_ajax_scan_theme_without_capabilities() {
        // Mock security checks
        $this->mock_security->expects($this->once())
            ->method('verify_nonce')
            ->willReturn(true);

        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(false);

        $this->mock_logger->expects($this->once())
            ->method('warning')
            ->with('AJAX request failed capability check');

        // Mock $_POST data
        $_POST['nonce'] = 'test-nonce';

        // Expect JSON error response
        $this->expectOutputString('{"success":false,"data":{"message":"You do not have sufficient permissions to perform this action."}}');

        // Call the method
        $this->admin_controller->ajax_scan_theme();
    }

    /**
     * Test AJAX convert PHP to JSON with valid request.
     */
    public function test_ajax_convert_php_to_json_with_valid_request() {
        // Mock security checks to return true
        $this->mock_security->expects($this->once())
            ->method('verify_nonce')
            ->willReturn(true);

        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(true);

        // Mock $_POST data
        $_POST['nonce'] = 'test-nonce';

        // Expect JSON success response
        $this->expectOutputString('{"success":true,"data":{"message":"Conversion functionality will be implemented soon."}}');

        // Call the method
        $this->admin_controller->ajax_convert_php_to_json();
    }

    /**
     * Test AJAX convert JSON to PHP with valid request.
     */
    public function test_ajax_convert_json_to_php_with_valid_request() {
        // Mock security checks to return true
        $this->mock_security->expects($this->once())
            ->method('verify_nonce')
            ->willReturn(true);

        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(true);

        // Mock $_POST data
        $_POST['nonce'] = 'test-nonce';

        // Expect JSON success response
        $this->expectOutputString('{"success":true,"data":{"message":"Conversion functionality will be implemented soon."}}');

        // Call the method
        $this->admin_controller->ajax_convert_json_to_php();
    }

    /**
     * Test AJAX save settings with valid request.
     */
    public function test_ajax_save_settings_with_valid_request() {
        // Mock security checks to return true
        $this->mock_security->expects($this->once())
            ->method('verify_nonce')
            ->willReturn(true);

        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(true);

        // Mock $_POST data
        $_POST['nonce'] = 'test-nonce';

        // Expect JSON success response
        $this->expectOutputString('{"success":true,"data":{"message":"Settings functionality will be implemented soon."}}');

        // Call the method
        $this->admin_controller->ajax_save_settings();
    }

    /**
     * Test AJAX preview field group with valid request.
     */
    public function test_ajax_preview_field_group_with_valid_request() {
        // Mock security checks to return true
        $this->mock_security->expects($this->once())
            ->method('verify_nonce')
            ->willReturn(true);

        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(true);

        // Mock $_POST data
        $_POST['nonce'] = 'test-nonce';

        // Expect JSON success response
        $this->expectOutputString('{"success":true,"data":{"message":"Preview functionality will be implemented soon."}}');

        // Call the method
        $this->admin_controller->ajax_preview_field_group();
    }

    /**
     * Test AJAX download field group with valid request.
     */
    public function test_ajax_download_field_group_with_valid_request() {
        // Mock security checks to return true
        $this->mock_security->expects($this->once())
            ->method('verify_nonce')
            ->willReturn(true);

        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(true);

        // Mock $_POST data
        $_POST['nonce'] = 'test-nonce';

        // Expect JSON success response
        $this->expectOutputString('{"success":true,"data":{"message":"Download functionality will be implemented soon."}}');

        // Call the method
        $this->admin_controller->ajax_download_field_group();
    }

    /**
     * Test AJAX batch process with valid request.
     */
    public function test_ajax_batch_process_with_valid_request() {
        // Mock security checks to return true
        $this->mock_security->expects($this->once())
            ->method('verify_nonce')
            ->willReturn(true);

        $this->mock_security->expects($this->once())
            ->method('check_capability')
            ->with('manage_options')
            ->willReturn(true);

        // Mock $_POST data
        $_POST['nonce'] = 'test-nonce';

        // Expect JSON success response
        $this->expectOutputString('{"success":true,"data":{"message":"Batch processing functionality will be implemented soon."}}');

        // Call the method
        $this->admin_controller->ajax_batch_process();
    }

    /**
     * Clean up after tests.
     */
    protected function tearDown(): void {
        // Clean up $_POST
        $_POST = [];
        
        parent::tearDown();
    }
}