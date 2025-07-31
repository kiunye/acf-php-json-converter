<?php
/**
 * Tests for the File Manager service class.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Tests/Unit/Services
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Tests
 */

use ACF_PHP_JSON_Converter\Services\File_Manager;
use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Security;

/**
 * File Manager service test case.
 */
class FileManagerTest extends WP_UnitTestCase {

    /**
     * File Manager instance.
     *
     * @var File_Manager
     */
    protected $file_manager;

    /**
     * Logger mock.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Security mock.
     *
     * @var Security
     */
    protected $security;

    /**
     * Test directory.
     *
     * @var string
     */
    protected $test_dir;

    /**
     * Test theme directory.
     *
     * @var string
     */
    protected $test_theme_dir;

    /**
     * Set up.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create test directories
        $this->test_dir = sys_get_temp_dir() . '/acf_file_manager_test_' . uniqid();
        $this->test_theme_dir = $this->test_dir . '/theme';
        mkdir($this->test_theme_dir, 0755, true);
        
        // Create logger mock
        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Create security mock
        $this->security = $this->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Configure security mock to allow test paths
        $this->security->method('validate_path')
            ->willReturnCallback(function($path) {
                return strpos($path, $this->test_dir) === 0;
            });
        
        $this->security->method('is_writable')
            ->willReturnCallback(function($path) {
                return is_writable($path);
            });
        
        // Mock wp_upload_dir to return our test directory
        $GLOBALS['wp_upload_dir_override'] = [
            'basedir' => $this->test_dir,
            'baseurl' => 'http://example.com/uploads',
        ];
        
        // Mock get_stylesheet_directory to return our test theme directory
        $GLOBALS['stylesheet_directory_override'] = $this->test_theme_dir;
        
        // Create file manager instance
        $this->file_manager = new File_Manager($this->logger, $this->security);
    }

    /**
     * Tear down.
     */
    public function tearDown(): void {
        // Clean up test directory
        if (is_dir($this->test_dir)) {
            $this->removeDirectory($this->test_dir);
        }
        
        // Reset globals
        unset($GLOBALS['wp_upload_dir_override']);
        unset($GLOBALS['stylesheet_directory_override']);
        
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
     * Test ACF JSON directory creation.
     */
    public function test_acf_json_directory_creation() {
        // Test creating ACF JSON directory
        $result = $this->file_manager->create_acf_json_directory($this->test_theme_dir);
        $this->assertTrue($result);
        
        // Verify directory was created
        $acf_json_dir = $this->test_theme_dir . '/acf-json';
        $this->assertTrue(is_dir($acf_json_dir));
        
        // Verify index.php was created for security
        $index_file = $acf_json_dir . '/index.php';
        $this->assertTrue(file_exists($index_file));
        
        // Test creating directory when it already exists
        $result = $this->file_manager->create_acf_json_directory($this->test_theme_dir);
        $this->assertTrue($result);
    }

    /**
     * Test ACF JSON directory creation with invalid path.
     */
    public function test_acf_json_directory_creation_invalid_path() {
        // Configure security mock to reject invalid paths
        $this->security->method('validate_path')
            ->willReturn(false);
        
        $result = $this->file_manager->create_acf_json_directory('/invalid/path');
        $this->assertFalse($result);
    }

    /**
     * Test getting ACF JSON directory path.
     */
    public function test_get_acf_json_directory() {
        // Test when directory doesn't exist
        $result = $this->file_manager->get_acf_json_directory($this->test_theme_dir);
        $this->assertEquals('', $result);
        
        // Create directory and test again
        $this->file_manager->create_acf_json_directory($this->test_theme_dir);
        $result = $this->file_manager->get_acf_json_directory($this->test_theme_dir);
        $this->assertEquals($this->test_theme_dir . '/acf-json/', $result);
    }

    /**
     * Test checking if ACF JSON directory exists.
     */
    public function test_acf_json_directory_exists() {
        // Test when directory doesn't exist
        $result = $this->file_manager->acf_json_directory_exists($this->test_theme_dir);
        $this->assertFalse($result);
        
        // Create directory and test again
        $this->file_manager->create_acf_json_directory($this->test_theme_dir);
        $result = $this->file_manager->acf_json_directory_exists($this->test_theme_dir);
        $this->assertTrue($result);
    }

    /**
     * Test writing JSON files.
     */
    public function test_write_json_file() {
        // Test data
        $test_data = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_test',
                    'label' => 'Test Field',
                    'name' => 'test_field',
                    'type' => 'text',
                ],
            ],
        ];
        
        // Write JSON file
        $result = $this->file_manager->write_json_file('group_test', $test_data);
        $this->assertTrue($result);
        
        // Verify file was created
        $acf_json_dir = $this->test_theme_dir . '/acf-json';
        $json_file = $acf_json_dir . '/group_test.json';
        $this->assertTrue(file_exists($json_file));
        
        // Verify file content
        $file_content = file_get_contents($json_file);
        $decoded_data = json_decode($file_content, true);
        $this->assertEquals($test_data, $decoded_data);
    }

    /**
     * Test writing JSON file with invalid data.
     */
    public function test_write_json_file_invalid_data() {
        // Test with non-array data
        $result = $this->file_manager->write_json_file('test', 'not an array');
        $this->assertFalse($result);
        
        // Test with empty filename
        $result = $this->file_manager->write_json_file('', ['key' => 'value']);
        $this->assertFalse($result);
    }

    /**
     * Test creating backups.
     */
    public function test_create_backup() {
        // Create test files to backup
        $test_file1 = $this->test_theme_dir . '/test1.php';
        $test_file2 = $this->test_theme_dir . '/test2.php';
        file_put_contents($test_file1, '<?php // Test file 1');
        file_put_contents($test_file2, '<?php // Test file 2');
        
        // Create backup
        $backup_path = $this->file_manager->create_backup([$test_file1, $test_file2]);
        $this->assertNotEmpty($backup_path);
        $this->assertTrue(is_dir($backup_path));
        
        // Verify backup files exist
        $this->assertTrue(file_exists($backup_path . '/test1.php'));
        $this->assertTrue(file_exists($backup_path . '/test2.php'));
        
        // Verify backup file content
        $backup_content1 = file_get_contents($backup_path . '/test1.php');
        $this->assertEquals('<?php // Test file 1', $backup_content1);
    }

    /**
     * Test creating backup with invalid files.
     */
    public function test_create_backup_invalid_files() {
        // Test with empty files array
        $result = $this->file_manager->create_backup([]);
        $this->assertEquals('', $result);
        
        // Test with non-array input
        $result = $this->file_manager->create_backup('not an array');
        $this->assertEquals('', $result);
        
        // Test with non-existent files
        $result = $this->file_manager->create_backup(['/non/existent/file.php']);
        $this->assertEquals('', $result);
    }

    /**
     * Test exporting field groups as JSON.
     */
    public function test_export_field_group_as_json() {
        $field_group = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_test',
                    'label' => 'Test Field',
                    'name' => 'test_field',
                    'type' => 'text',
                ],
            ],
            '_acf_php_json_converter' => [
                'source_file' => '/path/to/file.php',
            ],
        ];
        
        $result = $this->file_manager->export_field_group_as_json($field_group);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('json', $result);
        $this->assertEquals('group_test', $result['key']);
        $this->assertEquals('Test Field Group', $result['title']);
        
        // Verify JSON content
        $json_data = json_decode($result['json'], true);
        $this->assertEquals('group_test', $json_data['key']);
        $this->assertEquals('Test Field Group', $json_data['title']);
        
        // Verify internal metadata was removed
        $this->assertArrayNotHasKey('_acf_php_json_converter', $json_data);
        
        // Verify default values were added
        $this->assertArrayHasKey('location', $json_data);
        $this->assertArrayHasKey('active', $json_data);
        $this->assertTrue($json_data['active']);
    }

    /**
     * Test exporting invalid field group as JSON.
     */
    public function test_export_invalid_field_group_as_json() {
        // Test with non-array input
        $result = $this->file_manager->export_field_group_as_json('not an array');
        $this->assertFalse($result['success']);
        
        // Test with missing key
        $result = $this->file_manager->export_field_group_as_json(['title' => 'Test']);
        $this->assertFalse($result['success']);
    }

    /**
     * Test exporting multiple field groups.
     */
    public function test_export_files() {
        $field_groups = [
            [
                'key' => 'group_test1',
                'title' => 'Test Field Group 1',
                'fields' => [
                    [
                        'key' => 'field_test1',
                        'label' => 'Test Field 1',
                        'name' => 'test_field1',
                        'type' => 'text',
                    ],
                ],
            ],
            [
                'key' => 'group_test2',
                'title' => 'Test Field Group 2',
                'fields' => [
                    [
                        'key' => 'field_test2',
                        'label' => 'Test Field 2',
                        'name' => 'test_field2',
                        'type' => 'text',
                    ],
                ],
            ],
        ];
        
        // Test ZIP export (multiple files)
        $result = $this->file_manager->export_files($field_groups, 'zip');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('zip', $result['format']);
        $this->assertEquals(2, $result['file_count']);
        $this->assertArrayHasKey('download_url', $result);
        $this->assertArrayHasKey('exported_files', $result);
        $this->assertCount(2, $result['exported_files']);
    }

    /**
     * Test exporting single field group.
     */
    public function test_export_single_field_group() {
        $field_group = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_test',
                    'label' => 'Test Field',
                    'name' => 'test_field',
                    'type' => 'text',
                ],
            ],
        ];
        
        // Test JSON export (single file)
        $result = $this->file_manager->export_files([$field_group], 'json');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('json', $result['format']);
        $this->assertEquals(1, $result['file_count']);
        $this->assertArrayHasKey('download_url', $result);
        $this->assertStringContainsString('group_test.json', $result['filename']);
    }

    /**
     * Test exporting with invalid field groups.
     */
    public function test_export_files_invalid_input() {
        // Test with empty array
        $result = $this->file_manager->export_files([]);
        $this->assertFalse($result['success']);
        
        // Test with non-array input
        $result = $this->file_manager->export_files('not an array');
        $this->assertFalse($result['success']);
        
        // Test with invalid field groups
        $invalid_field_groups = [
            ['title' => 'Missing key'],
            'not an array',
        ];
        
        $result = $this->file_manager->export_files($invalid_field_groups);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('failed_exports', $result);
        $this->assertCount(2, $result['failed_exports']);
    }

    /**
     * Test getting backup directory.
     */
    public function test_get_backup_directory() {
        $backup_dir = $this->file_manager->get_backup_directory();
        $this->assertNotEmpty($backup_dir);
        $this->assertTrue(is_dir($backup_dir));
        
        // Verify security files were created
        $index_file = $backup_dir . '/index.php';
        $htaccess_file = $backup_dir . '/.htaccess';
        $this->assertTrue(file_exists($index_file));
        $this->assertTrue(file_exists($htaccess_file));
    }

    /**
     * Test getting list of backups.
     */
    public function test_get_backups() {
        // Create some test backups
        $test_file = $this->test_theme_dir . '/test.php';
        file_put_contents($test_file, '<?php // Test file');
        
        $backup1 = $this->file_manager->create_backup([$test_file]);
        sleep(1); // Ensure different timestamps
        $backup2 = $this->file_manager->create_backup([$test_file]);
        
        // Get backups list
        $backups = $this->file_manager->get_backups();
        
        $this->assertIsArray($backups);
        $this->assertCount(2, $backups);
        
        // Verify backup structure
        $backup = $backups[0];
        $this->assertArrayHasKey('name', $backup);
        $this->assertArrayHasKey('path', $backup);
        $this->assertArrayHasKey('timestamp', $backup);
        $this->assertArrayHasKey('date', $backup);
        $this->assertArrayHasKey('file_count', $backup);
        $this->assertEquals(1, $backup['file_count']);
    }

    /**
     * Test backup cleanup.
     */
    public function test_cleanup_old_backups() {
        // Create multiple backups to test cleanup
        $test_file = $this->test_theme_dir . '/test.php';
        file_put_contents($test_file, '<?php // Test file');
        
        // Create 12 backups (more than the default max of 10)
        for ($i = 0; $i < 12; $i++) {
            $this->file_manager->create_backup([$test_file]);
            usleep(100000); // Small delay to ensure different timestamps
        }
        
        // Get backups before cleanup
        $backups_before = $this->file_manager->get_backups();
        $this->assertGreaterThan(10, count($backups_before));
        
        // Run cleanup
        $result = $this->file_manager->cleanup_old_backups();
        $this->assertTrue($result);
        
        // Get backups after cleanup
        $backups_after = $this->file_manager->get_backups();
        $this->assertLessThanOrEqual(10, count($backups_after));
    }

    /**
     * Test ZIP archive creation.
     */
    public function test_create_zip_archive() {
        // Skip test if ZipArchive is not available
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive class not available');
        }
        
        // Create test files
        $test_file1 = $this->test_dir . '/test1.txt';
        $test_file2 = $this->test_dir . '/test2.txt';
        file_put_contents($test_file1, 'Test content 1');
        file_put_contents($test_file2, 'Test content 2');
        
        $zip_file = $this->test_dir . '/test.zip';
        
        // Create ZIP archive
        $result = $this->file_manager->create_zip_archive(
            [$test_file1, $test_file2],
            $zip_file,
            $this->test_dir
        );
        
        $this->assertTrue($result);
        $this->assertTrue(file_exists($zip_file));
        
        // Verify ZIP content
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zip_file) === true);
        $this->assertEquals(2, $zip->numFiles);
        $zip->close();
    }

    /**
     * Test restore backup functionality.
     */
    public function test_restore_backup() {
        // Create test file and backup
        $test_file = $this->test_theme_dir . '/test.php';
        $original_content = '<?php // Original content';
        file_put_contents($test_file, $original_content);
        
        $backup_path = $this->file_manager->create_backup([$test_file]);
        $this->assertNotEmpty($backup_path);
        
        // Modify original file
        $modified_content = '<?php // Modified content';
        file_put_contents($test_file, $modified_content);
        
        // Verify file was modified
        $this->assertEquals($modified_content, file_get_contents($test_file));
        
        // Get backup name from path
        $backup_name = basename($backup_path);
        
        // Restore backup
        $result = $this->file_manager->restore_backup($backup_name);
        $this->assertTrue($result);
        
        // Verify file was restored
        $this->assertEquals($original_content, file_get_contents($test_file));
    }

    /**
     * Test restore backup with invalid backup name.
     */
    public function test_restore_backup_invalid() {
        $result = $this->file_manager->restore_backup('non-existent-backup');
        $this->assertFalse($result);
    }
}