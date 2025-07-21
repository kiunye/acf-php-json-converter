<?php
/**
 * Tests for the Scanner Service class.
 *
 * @package ACF_PHP_JSON_Converter
 */

use ACF_PHP_JSON_Converter\Services\Scanner_Service;
use ACF_PHP_JSON_Converter\Services\File_Manager;
use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Security;
use ACF_PHP_JSON_Converter\Parsers\PHP_Parser;

/**
 * Scanner Service test case.
 */
class Scanner_ServiceTest extends WP_UnitTestCase {

    /**
     * Scanner Service instance.
     *
     * @var Scanner_Service
     */
    protected $scanner;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Security instance.
     *
     * @var Security
     */
    protected $security;

    /**
     * File Manager instance.
     *
     * @var File_Manager
     */
    protected $file_manager;

    /**
     * Test directory path.
     *
     * @var string
     */
    protected $test_dir;

    /**
     * Set up.
     */
    public function setUp() {
        parent::setUp();
        
        // Create mock objects
        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->security = $this->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->file_manager = $this->getMockBuilder(File_Manager::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Always return true for validate_path
        $this->security->method('validate_path')
            ->willReturn(true);
        
        // Create test directory
        $this->test_dir = sys_get_temp_dir() . '/acf_scanner_test_' . uniqid();
        mkdir($this->test_dir);
        
        // Create scanner service
        $this->scanner = new Scanner_Service($this->logger, $this->security, $this->file_manager);
    }

    /**
     * Tear down.
     */
    public function tearDown() {
        // Remove test directory and files
        $this->remove_directory($this->test_dir);
        
        parent::tearDown();
    }

    /**
     * Recursively remove a directory.
     *
     * @param string $dir Directory path.
     */
    protected function remove_directory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->remove_directory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Create test PHP file with ACF field group.
     *
     * @param string $file_path File path.
     * @param string $key       Field group key.
     * @param string $title     Field group title.
     */
    protected function create_test_php_file($file_path, $key = 'group_123', $title = 'Test Group') {
        $content = <<<PHP
<?php
acf_add_local_field_group(array(
    'key' => '{$key}',
    'title' => '{$title}',
    'fields' => array(
        array(
            'key' => 'field_123',
            'label' => 'Test Field',
            'name' => 'test_field',
            'type' => 'text'
        )
    ),
    'location' => array()
));
PHP;

        file_put_contents($file_path, $content);
    }

    /**
     * Test scan_theme_files method.
     */
    public function test_scan_theme_files() {
        // Create test PHP file with ACF field group
        $test_file = $this->test_dir . '/test.php';
        $this->create_test_php_file($test_file);
        
        // Mock get_theme_paths to return test directory
        $this->file_manager->method('get_theme_paths')
            ->willReturn(array($this->test_dir));
        
        // Run scan
        $results = $this->scanner->scan_theme_files('', false);
        
        // Check results
        $this->assertEquals('success', $results['status']);
        $this->assertEquals(1, $results['count']);
        $this->assertArrayHasKey('group_123', $results['field_groups']);
        $this->assertEquals('Test Group', $results['field_groups']['group_123']['title']);
    }

    /**
     * Test scan_theme_files method with multiple field groups.
     */
    public function test_scan_theme_files_multiple() {
        // Create test PHP files with ACF field groups
        $test_file1 = $this->test_dir . '/test1.php';
        $this->create_test_php_file($test_file1, 'group_123', 'Test Group 1');
        
        $test_file2 = $this->test_dir . '/test2.php';
        $this->create_test_php_file($test_file2, 'group_456', 'Test Group 2');
        
        // Mock get_theme_paths to return test directory
        $this->file_manager->method('get_theme_paths')
            ->willReturn(array($this->test_dir));
        
        // Run scan
        $results = $this->scanner->scan_theme_files('', false);
        
        // Check results
        $this->assertEquals('success', $results['status']);
        $this->assertEquals(2, $results['count']);
        $this->assertArrayHasKey('group_123', $results['field_groups']);
        $this->assertArrayHasKey('group_456', $results['field_groups']);
        $this->assertEquals('Test Group 1', $results['field_groups']['group_123']['title']);
        $this->assertEquals('Test Group 2', $results['field_groups']['group_456']['title']);
    }

    /**
     * Test scan_theme_files method with subdirectories.
     */
    public function test_scan_theme_files_subdirectories() {
        // Create subdirectory
        $sub_dir = $this->test_dir . '/subdir';
        mkdir($sub_dir);
        
        // Create test PHP files with ACF field groups
        $test_file1 = $this->test_dir . '/test1.php';
        $this->create_test_php_file($test_file1, 'group_123', 'Test Group 1');
        
        $test_file2 = $sub_dir . '/test2.php';
        $this->create_test_php_file($test_file2, 'group_456', 'Test Group 2');
        
        // Mock get_theme_paths to return test directory
        $this->file_manager->method('get_theme_paths')
            ->willReturn(array($this->test_dir));
        
        // Run scan
        $results = $this->scanner->scan_theme_files('', false);
        
        // Check results
        $this->assertEquals('success', $results['status']);
        $this->assertEquals(2, $results['count']);
        $this->assertArrayHasKey('group_123', $results['field_groups']);
        $this->assertArrayHasKey('group_456', $results['field_groups']);
    }

    /**
     * Test scan_theme_files method with disallowed directories.
     */
    public function test_scan_theme_files_disallowed_dirs() {
        // Create disallowed directory
        $disallowed_dir = $this->test_dir . '/vendor';
        mkdir($disallowed_dir);
        
        // Create test PHP files with ACF field groups
        $test_file1 = $this->test_dir . '/test1.php';
        $this->create_test_php_file($test_file1, 'group_123', 'Test Group 1');
        
        $test_file2 = $disallowed_dir . '/test2.php';
        $this->create_test_php_file($test_file2, 'group_456', 'Test Group 2');
        
        // Mock get_theme_paths to return test directory
        $this->file_manager->method('get_theme_paths')
            ->willReturn(array($this->test_dir));
        
        // Run scan
        $results = $this->scanner->scan_theme_files('', false);
        
        // Check results - should only find the file in the main directory, not in vendor
        $this->assertEquals('success', $results['status']);
        $this->assertEquals(1, $results['count']);
        $this->assertArrayHasKey('group_123', $results['field_groups']);
        $this->assertArrayNotHasKey('group_456', $results['field_groups']);
    }

    /**
     * Test scan_theme_files method with custom theme path.
     */
    public function test_scan_theme_files_custom_path() {
        // Create test PHP file with ACF field group
        $test_file = $this->test_dir . '/test.php';
        $this->create_test_php_file($test_file);
        
        // Run scan with custom theme path
        $results = $this->scanner->scan_theme_files($this->test_dir, false);
        
        // Check results
        $this->assertEquals('success', $results['status']);
        $this->assertEquals(1, $results['count']);
        $this->assertArrayHasKey('group_123', $results['field_groups']);
    }

    /**
     * Test scan_theme_files method with invalid theme path.
     */
    public function test_scan_theme_files_invalid_path() {
        // Mock validate_path to return false for invalid path
        $this->security->method('validate_path')
            ->will($this->returnCallback(function($path) {
                return $path !== 'invalid/path';
            }));
        
        // Run scan with invalid theme path
        $results = $this->scanner->scan_theme_files('invalid/path', false);
        
        // Check results
        $this->assertNotEquals('success', $results['status']);
        $this->assertEquals(0, $results['count']);
        $this->assertEmpty($results['field_groups']);
        $this->assertNotEmpty($results['errors']);
    }

    /**
     * Test scan_theme_files method with no PHP files.
     */
    public function test_scan_theme_files_no_php_files() {
        // Create empty directory
        $empty_dir = $this->test_dir . '/empty';
        mkdir($empty_dir);
        
        // Mock get_theme_paths to return empty directory
        $this->file_manager->method('get_theme_paths')
            ->willReturn(array($empty_dir));
        
        // Run scan
        $results = $this->scanner->scan_theme_files('', false);
        
        // Check results
        $this->assertEquals('success', $results['status']);
        $this->assertEquals(0, $results['count']);
        $this->assertEmpty($results['field_groups']);
        $this->assertNotEmpty($results['warnings']);
    }

    /**
     * Test scan_theme_files method with cache.
     */
    public function test_scan_theme_files_cache() {
        // Create test PHP file with ACF field group
        $test_file = $this->test_dir . '/test.php';
        $this->create_test_php_file($test_file);
        
        // Mock get_theme_paths to return test directory
        $this->file_manager->method('get_theme_paths')
            ->willReturn(array($this->test_dir));
        
        // Run scan without cache
        $results1 = $this->scanner->scan_theme_files('', false);
        
        // Run scan with cache
        $results2 = $this->scanner->scan_theme_files('', true);
        
        // Check results
        $this->assertEquals($results1['count'], $results2['count']);
        $this->assertEquals($results1['field_groups'], $results2['field_groups']);
    }

    /**
     * Test clear_cache method.
     */
    public function test_clear_cache() {
        // Create test PHP file with ACF field group
        $test_file = $this->test_dir . '/test.php';
        $this->create_test_php_file($test_file);
        
        // Mock get_theme_paths to return test directory
        $this->file_manager->method('get_theme_paths')
            ->willReturn(array($this->test_dir));
        
        // Run scan to populate cache
        $this->scanner->scan_theme_files('', true);
        
        // Check if cache exists
        $this->assertNotNull($this->scanner->get_cached_results());
        
        // Clear cache
        $this->assertTrue($this->scanner->clear_cache());
        
        // Check if cache is cleared
        $this->assertNull($this->scanner->get_cached_results());
    }

    /**
     * Test set_disallowed_dirs and get_disallowed_dirs methods.
     */
    public function test_set_get_disallowed_dirs() {
        $default_dirs = $this->scanner->get_disallowed_dirs();
        $this->assertNotEmpty($default_dirs);
        
        $new_dirs = array('test1', 'test2');
        $this->scanner->set_disallowed_dirs($new_dirs);
        
        $this->assertEquals($new_dirs, $this->scanner->get_disallowed_dirs());
    }

    /**
     * Test set_cache_expiration and get_cache_expiration methods.
     */
    public function test_set_get_cache_expiration() {
        $default_expiration = $this->scanner->get_cache_expiration();
        $this->assertEquals(3600, $default_expiration);
        
        $new_expiration = 7200;
        $this->scanner->set_cache_expiration($new_expiration);
        
        $this->assertEquals($new_expiration, $this->scanner->get_cache_expiration());
    }
}