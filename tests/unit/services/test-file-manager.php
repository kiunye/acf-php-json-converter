<?php
/**
 * Tests for the File Manager Service class.
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
 * File Manager Service test case.
 */
class File_Manager_Test extends WP_UnitTestCase {

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
     * Test theme directory.
     *
     * @var string
     */
    protected $test_theme_dir;

    /**
     * Set up.
     */
    public function setUp() {
        parent::setUp();
        
        // Create logger mock
        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Create security mock with specific method implementations
        $this->security = $this->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Configure security mock to validate paths
        $this->security->method('validate_path')
            ->will($this->returnCallback(function($path) {
                return strpos($path, $this->test_theme_dir) === 0;
            }));
        
        // Configure security mock to check if directory is writable
        $this->security->method('is_writable')
            ->will($this->returnCallback(function($dir) {
                return is_dir($dir) && is_writable($dir);
            }));
        
        // Configure security mock to sanitize input
        $this->security->method('sanitize_input')
            ->will($this->returnCallback(function($input, $type) {
                if ($type === 'filename') {
                    return sanitize_file_name($input);
                }
                return $input;
            }));
        
        // Create test theme directory
        $this->test_theme_dir = get_temp_dir() . 'acf_php_json_test_theme_' . uniqid();
        mkdir($this->test_theme_dir, 0755, true);
        
        // Create file manager instance
        $this->file_manager = new File_Manager($this->logger, $this->security);
        
        // Use reflection to set the wp_filesystem property
        $reflection = new ReflectionClass($this->file_manager);
        $wp_filesystem_property = $reflection->getProperty('wp_filesystem');
        $wp_filesystem_property->setAccessible(true);
        
        // Create a mock filesystem
        $wp_filesystem = $this->getMockBuilder('WP_Filesystem_Direct')
            ->disableOriginalConstructor()
            ->getMock();
        
        // Configure filesystem mock methods
        $wp_filesystem->method('mkdir')
            ->will($this->returnCallback(function($dir) {
                return mkdir($dir, 0755, true);
            }));
        
        $wp_filesystem->method('put_contents')
            ->will($this->returnCallback(function($file, $contents) {
                return file_put_contents($file, $contents) !== false;
            }));
        
        $wp_filesystem->method('chmod')
            ->will($this->returnCallback(function($file, $mode) {
                return chmod($file, $mode);
            }));
        
        // Set the mock filesystem
        $wp_filesystem_property->setValue($this->file_manager, $wp_filesystem);
    }

    /**
     * Tear down.
     */
    public function tearDown() {
        // Clean up test theme directory
        $this->recursiveRemoveDirectory($this->test_theme_dir);
        
        parent::tearDown();
    }

    /**
     * Recursively remove a directory.
     *
     * @param string $dir Directory to remove.
     */
    protected function recursiveRemoveDirectory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->recursiveRemoveDirectory($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Test create_acf_json_directory method.
     */
    public function test_create_acf_json_directory() {
        // Test creating directory
        $result = $this->file_manager->create_acf_json_directory($this->test_theme_dir);
        $this->assertTrue($result);
        
        // Check if directory was created
        $acf_json_dir = $this->test_theme_dir . '/acf-json';
        $this->assertTrue(is_dir($acf_json_dir));
        
        // Check if index.php was created
        $index_file = $acf_json_dir . '/index.php';
        $this->assertTrue(file_exists($index_file));
        
        // Test creating directory that already exists
        $result = $this->file_manager->create_acf_json_directory($this->test_theme_dir);
        $this->assertTrue($result);
    }

    /**
     * Test create_acf_json_directory with invalid path.
     */
    public function test_create_acf_json_directory_invalid_path() {
        // Configure security mock to reject this path
        $this->security = $this->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->security->method('validate_path')
            ->willReturn(false);
        
        // Create new file manager with updated security mock
        $file_manager = new File_Manager($this->logger, $this->security);
        
        // Use reflection to set the wp_filesystem property
        $reflection = new ReflectionClass($file_manager);
        $wp_filesystem_property = $reflection->getProperty('wp_filesystem');
        $wp_filesystem_property->setAccessible(true);
        $wp_filesystem_property->setValue($file_manager, $this->file_manager->wp_filesystem);
        
        // Test creating directory with invalid path
        $result = $file_manager->create_acf_json_directory('/invalid/path');
        $this->assertFalse($result);
    }

    /**
     * Test write_json_file method.
     */
    public function test_write_json_file() {
        // Create test data
        $data = [
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
        
        // Test writing file
        $result = $this->file_manager->write_json_file('test-field-group', $data);
        $this->assertTrue($result);
        
        // Check if file was created
        $file_path = $this->test_theme_dir . '/acf-json/test-field-group.json';
        $this->assertTrue(file_exists($file_path));
        
        // Check file contents
        $file_contents = file_get_contents($file_path);
        $decoded_data = json_decode($file_contents, true);
        $this->assertEquals($data['key'], $decoded_data['key']);
        $this->assertEquals($data['title'], $decoded_data['title']);
        $this->assertCount(1, $decoded_data['fields']);
    }

    /**
     * Test write_json_file with invalid data.
     */
    public function test_write_json_file_invalid_data() {
        // Test with non-array data
        $result = $this->file_manager->write_json_file('test-invalid', 'not an array');
        $this->assertFalse($result);
        
        // Test with empty filename
        $result = $this->file_manager->write_json_file('', ['key' => 'value']);
        $this->assertFalse($result);
    }

    /**
     * Test get_acf_json_directory method.
     */
    public function test_get_acf_json_directory() {
        // Create ACF JSON directory
        $this->file_manager->create_acf_json_directory($this->test_theme_dir);
        
        // Test getting directory path
        $path = $this->file_manager->get_acf_json_directory($this->test_theme_dir);
        $this->assertNotEmpty($path);
        $this->assertEquals($this->test_theme_dir . '/acf-json', $path);
        
        // Test with non-existent directory
        $path = $this->file_manager->get_acf_json_directory($this->test_theme_dir . '/non-existent');
        $this->assertEmpty($path);
    }

    /**
     * Test acf_json_directory_exists method.
     */
    public function test_acf_json_directory_exists() {
        // Test before directory exists
        $exists = $this->file_manager->acf_json_directory_exists($this->test_theme_dir);
        $this->assertFalse($exists);
        
        // Create directory
        $this->file_manager->create_acf_json_directory($this->test_theme_dir);
        
        // Test after directory exists
        $exists = $this->file_manager->acf_json_directory_exists($this->test_theme_dir);
        $this->assertTrue($exists);
    }
    
    /**
     * Test create_backup method.
     */
    public function test_create_backup() {
        // Create test files
        $test_file1 = $this->test_theme_dir . '/test-file1.json';
        $test_file2 = $this->test_theme_dir . '/test-file2.json';
        file_put_contents($test_file1, '{"key": "test1"}');
        file_put_contents($test_file2, '{"key": "test2"}');
        
        // Create backup
        $backup_path = $this->file_manager->create_backup([$test_file1, $test_file2]);
        
        // Check if backup was created
        $this->assertNotEmpty($backup_path);
        $this->assertTrue(is_dir($backup_path));
        
        // Check if files were backed up
        $this->assertTrue(file_exists($backup_path . '/test-file1.json'));
        $this->assertTrue(file_exists($backup_path . '/test-file2.json'));
        
        // Check file contents
        $this->assertEquals('{"key": "test1"}', file_get_contents($backup_path . '/test-file1.json'));
        $this->assertEquals('{"key": "test2"}', file_get_contents($backup_path . '/test-file2.json'));
    }
    
    /**
     * Test create_backup with invalid files.
     */
    public function test_create_backup_invalid_files() {
        // Test with empty array
        $backup_path = $this->file_manager->create_backup([]);
        $this->assertEmpty($backup_path);
        
        // Test with non-existent files
        $backup_path = $this->file_manager->create_backup([$this->test_theme_dir . '/non-existent.json']);
        $this->assertEmpty($backup_path);
    }
    
    /**
     * Test get_backup_directory method.
     */
    public function test_get_backup_directory() {
        // Get backup directory
        $backup_dir = $this->file_manager->get_backup_directory();
        
        // Check if directory was created
        $this->assertNotEmpty($backup_dir);
        $this->assertTrue(is_dir($backup_dir));
        
        // Check if security files were created
        $this->assertTrue(file_exists($backup_dir . '/index.php'));
        $this->assertTrue(file_exists($backup_dir . '/.htaccess'));
    }
    
    /**
     * Test cleanup_old_backups method.
     */
    public function test_cleanup_old_backups() {
        // Create backup directory
        $backup_dir = $this->file_manager->get_backup_directory();
        
        // Create test backup subdirectories (more than max_backups)
        $subdirs = [];
        for ($i = 1; $i <= 12; $i++) {
            $subdir = $backup_dir . '/2023-01-01-' . sprintf('%02d', $i) . '-00-00';
            mkdir($subdir, 0755, true);
            $subdirs[] = $subdir;
            
            // Add a test file to each backup
            file_put_contents($subdir . '/test.json', '{"key": "test"}');
        }
        
        // Run cleanup
        $result = $this->file_manager->cleanup_old_backups();
        
        // Check if cleanup was successful
        $this->assertTrue($result);
        
        // Check if oldest backups were removed
        $this->assertFalse(is_dir($subdirs[0]));
        $this->assertFalse(is_dir($subdirs[1]));
        
        // Check if newest backups were kept
        $this->assertTrue(is_dir($subdirs[11]));
    }
    
    /**
     * Test export_files method.
     */
    public function test_export_files() {
        // Create test field groups
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
        
        // Export field groups
        $download_url = $this->file_manager->export_files($field_groups);
        
        // Check if export was successful
        $this->assertNotEmpty($download_url);
        
        // For single field group export
        $single_field_group = [$field_groups[0]];
        $single_download_url = $this->file_manager->export_files($single_field_group);
        $this->assertNotEmpty($single_download_url);
    }
    
    /**
     * Test export_files with invalid field groups.
     */
    public function test_export_files_invalid_field_groups() {
        // Test with empty array
        $download_url = $this->file_manager->export_files([]);
        $this->assertEmpty($download_url);
        
        // Test with invalid field groups
        $download_url = $this->file_manager->export_files(['not a field group']);
        $this->assertEmpty($download_url);
    }
    
    /**
     * Test create_zip_archive method.
     */
    public function test_create_zip_archive() {
        // Skip test if ZipArchive is not available
        if (!class_exists('ZipArchive')) {
            $this->markTestSkipped('ZipArchive not available');
        }
        
        // Create test files
        $test_file1 = $this->test_theme_dir . '/test-file1.json';
        $test_file2 = $this->test_theme_dir . '/test-file2.json';
        file_put_contents($test_file1, '{"key": "test1"}');
        file_put_contents($test_file2, '{"key": "test2"}');
        
        // Create ZIP archive
        $zip_file = $this->test_theme_dir . '/test.zip';
        $result = $this->file_manager->create_zip_archive([$test_file1, $test_file2], $zip_file);
        
        // Check if ZIP was created
        $this->assertTrue($result);
        $this->assertTrue(file_exists($zip_file));
        
        // Check ZIP contents
        $zip = new ZipArchive();
        $zip->open($zip_file);
        $this->assertEquals(2, $zip->numFiles);
        $zip->close();
    }
    
    /**
     * Test get_backups method.
     */
    public function test_get_backups() {
        // Create backup directory
        $backup_dir = $this->file_manager->get_backup_directory();
        
        // Create test backup subdirectories
        $subdir1 = $backup_dir . '/2023-01-01-01-00-00';
        $subdir2 = $backup_dir . '/2023-01-02-01-00-00';
        mkdir($subdir1, 0755, true);
        mkdir($subdir2, 0755, true);
        
        // Add test files
        file_put_contents($subdir1 . '/test1.json', '{"key": "test1"}');
        file_put_contents($subdir2 . '/test2.json', '{"key": "test2"}');
        
        // Get backups
        $backups = $this->file_manager->get_backups();
        
        // Check backups
        $this->assertCount(2, $backups);
        $this->assertEquals('2023-01-02-01-00-00', $backups[0]['name']); // Most recent first
        $this->assertEquals('2023-01-01-01-00-00', $backups[1]['name']);
        $this->assertEquals(1, $backups[0]['file_count']);
        $this->assertEquals(1, $backups[1]['file_count']);
    }
    
    /**
     * Test restore_backup method.
     */
    public function test_restore_backup() {
        // Create ACF JSON directory
        $this->file_manager->create_acf_json_directory($this->test_theme_dir);
        $acf_json_dir = $this->test_theme_dir . '/acf-json';
        
        // Create backup directory
        $backup_dir = $this->file_manager->get_backup_directory();
        $backup_name = '2023-01-01-01-00-00';
        $backup_path = $backup_dir . '/' . $backup_name;
        mkdir($backup_path, 0755, true);
        
        // Add test files to backup
        file_put_contents($backup_path . '/test1.json', '{"key": "test1"}');
        file_put_contents($backup_path . '/test2.json', '{"key": "test2"}');
        file_put_contents($backup_path . '/index.php', '<?php // Silence is golden');
        
        // Mock get_theme_paths to return our test directory
        $this->file_manager = $this->getMockBuilder(File_Manager::class)
            ->setConstructorArgs([$this->logger, $this->security])
            ->setMethods(['get_theme_paths'])
            ->getMock();
        
        $this->file_manager->method('get_theme_paths')
            ->willReturn(['current' => $this->test_theme_dir]);
        
        // Use reflection to set the wp_filesystem property
        $reflection = new ReflectionClass($this->file_manager);
        $wp_filesystem_property = $reflection->getProperty('wp_filesystem');
        $wp_filesystem_property->setAccessible(true);
        $wp_filesystem_property->setValue($this->file_manager, $this->getMockBuilder('WP_Filesystem_Direct')
            ->disableOriginalConstructor()
            ->getMock());
        
        // Restore backup
        $result = $this->file_manager->restore_backup($backup_name);
        
        // Check if restore was successful
        $this->assertTrue($result);
        
        // Check if files were restored
        $this->assertTrue(file_exists($acf_json_dir . '/test1.json'));
        $this->assertTrue(file_exists($acf_json_dir . '/test2.json'));
    }
    
    /**
     * Test delete_backup method.
     */
    public function test_delete_backup() {
        // Create backup directory
        $backup_dir = $this->file_manager->get_backup_directory();
        $backup_name = '2023-01-01-01-00-00';
        $backup_path = $backup_dir . '/' . $backup_name;
        mkdir($backup_path, 0755, true);
        
        // Add test file to backup
        file_put_contents($backup_path . '/test.json', '{"key": "test"}');
        
        // Delete backup
        $result = $this->file_manager->delete_backup($backup_name);
        
        // Check if delete was successful
        $this->assertTrue($result);
        $this->assertFalse(is_dir($backup_path));
    }
}