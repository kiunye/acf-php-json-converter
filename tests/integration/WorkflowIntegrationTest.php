<?php
/**
 * Integration tests for complete workflow scenarios.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Tests/Integration
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Tests
 */

use ACF_PHP_JSON_Converter\Services\Scanner_Service;
use ACF_PHP_JSON_Converter\Services\Converter_Service;
use ACF_PHP_JSON_Converter\Services\File_Manager;
use ACF_PHP_JSON_Converter\Parsers\PHP_Parser;
use ACF_PHP_JSON_Converter\Converters\PHP_To_JSON_Converter;
use ACF_PHP_JSON_Converter\Converters\JSON_To_PHP_Converter;
use ACF_PHP_JSON_Converter\Converters\Validator;
use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Security;

/**
 * Workflow integration test case.
 */
class WorkflowIntegrationTest extends WP_UnitTestCase {

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
     * Set up.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create test directories
        $this->test_dir = sys_get_temp_dir() . '/acf_integration_test_' . uniqid();
        $this->test_theme_dir = $this->test_dir . '/theme';
        mkdir($this->test_theme_dir, 0755, true);
        
        // Create logger and security instances
        $this->logger = new Logger();
        $this->security = new Security();
        
        // Mock wp_upload_dir to return our test directory
        $GLOBALS['wp_upload_dir_override'] = [
            'basedir' => $this->test_dir,
            'baseurl' => 'http://example.com/uploads',
        ];
        
        // Mock get_stylesheet_directory to return our test theme directory
        $GLOBALS['stylesheet_directory_override'] = $this->test_theme_dir;
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
     * Test complete PHP to JSON workflow.
     */
    public function test_complete_php_to_json_workflow() {
        // Step 1: Create test PHP file with ACF field group
        $php_content = <<<'PHP'
<?php
acf_add_local_field_group(array(
    'key' => 'group_test_workflow',
    'title' => 'Test Workflow Field Group',
    'fields' => array(
        array(
            'key' => 'field_text_test',
            'label' => 'Text Field',
            'name' => 'text_field',
            'type' => 'text',
            'required' => 1,
            'default_value' => 'Default text',
        ),
        array(
            'key' => 'field_repeater_test',
            'label' => 'Repeater Field',
            'name' => 'repeater_field',
            'type' => 'repeater',
            'sub_fields' => array(
                array(
                    'key' => 'field_sub_text',
                    'label' => 'Sub Text Field',
                    'name' => 'sub_text_field',
                    'type' => 'text',
                ),
            ),
            'min' => 1,
            'max' => 5,
        ),
    ),
    'location' => array(
        array(
            array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'post',
            ),
        ),
    ),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'active' => true,
));
PHP;

        $test_php_file = $this->test_theme_dir . '/functions.php';
        file_put_contents($test_php_file, $php_content);
        
        // Step 2: Scan theme files
        $file_manager = new File_Manager($this->logger, $this->security);
        $scanner = new Scanner_Service($this->logger, $this->security, $file_manager);
        
        $scan_results = $scanner->scan_theme_files($this->test_theme_dir, false);
        
        $this->assertEquals('success', $scan_results['status']);
        $this->assertEquals(1, $scan_results['count']);
        $this->assertArrayHasKey('group_test_workflow', $scan_results['field_groups']);
        
        $field_group = $scan_results['field_groups']['group_test_workflow'];
        $this->assertEquals('Test Workflow Field Group', $field_group['title']);
        $this->assertCount(2, $field_group['fields']);
        
        // Step 3: Convert PHP to JSON
        $converter_service = new Converter_Service($this->logger, $this->security);
        $conversion_result = $converter_service->convert_php_to_json($field_group);
        
        $this->assertContains($conversion_result['status'], ['success', 'warning']);
        $this->assertArrayHasKey('data', $conversion_result);
        
        $json_data = $conversion_result['data'];
        $this->assertEquals('group_test_workflow', $json_data['key']);
        $this->assertEquals('Test Workflow Field Group', $json_data['title']);
        
        // Step 4: Validate conversion
        $validator = new Validator($this->logger);
        $validation_result = $validator->validate_conversion($field_group, $json_data);
        
        $this->assertTrue($validation_result['valid']);
        
        // Step 5: Save JSON file
        $save_result = $file_manager->write_json_file('group_test_workflow', $json_data);
        $this->assertTrue($save_result);
        
        // Step 6: Verify JSON file was created
        $acf_json_dir = $this->test_theme_dir . '/acf-json';
        $json_file = $acf_json_dir . '/group_test_workflow.json';
        $this->assertTrue(file_exists($json_file));
        
        // Step 7: Verify JSON file content
        $saved_json = json_decode(file_get_contents($json_file), true);
        $this->assertEquals($json_data['key'], $saved_json['key']);
        $this->assertEquals($json_data['title'], $saved_json['title']);
        $this->assertCount(2, $saved_json['fields']);
    }

    /**
     * Test complete JSON to PHP workflow.
     */
    public function test_complete_json_to_php_workflow() {
        // Step 1: Create test JSON data
        $json_data = [
            'key' => 'group_json_to_php_test',
            'title' => 'JSON to PHP Test Group',
            'fields' => [
                [
                    'key' => 'field_json_text',
                    'label' => 'JSON Text Field',
                    'name' => 'json_text_field',
                    'type' => 'text',
                    'required' => 1,
                ],
                [
                    'key' => 'field_json_select',
                    'label' => 'JSON Select Field',
                    'name' => 'json_select_field',
                    'type' => 'select',
                    'choices' => [
                        'option1' => 'Option 1',
                        'option2' => 'Option 2',
                        'option3' => 'Option 3',
                    ],
                    'default_value' => 'option1',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'active' => true,
        ];
        
        // Step 2: Validate JSON structure
        $validator = new Validator($this->logger);
        $validation_result = $validator->validate_field_group($json_data);
        
        $this->assertTrue($validation_result['valid']);
        
        // Step 3: Convert JSON to PHP
        $converter_service = new Converter_Service($this->logger, $this->security);
        $conversion_result = $converter_service->convert_json_to_php($json_data);
        
        $this->assertContains($conversion_result['status'], ['success', 'warning']);
        $this->assertArrayHasKey('data', $conversion_result);
        
        $php_code = $conversion_result['data'];
        
        // Step 4: Verify PHP code structure
        $this->assertStringStartsWith('<?php', $php_code);
        $this->assertStringContainsString('acf_add_local_field_group(', $php_code);
        $this->assertStringContainsString("'key' => 'group_json_to_php_test'", $php_code);
        $this->assertStringContainsString("'title' => 'JSON to PHP Test Group'", $php_code);
        $this->assertStringContainsString("'type' => 'text'", $php_code);
        $this->assertStringContainsString("'type' => 'select'", $php_code);
        $this->assertStringContainsString("'choices'", $php_code);
        
        // Step 5: Test that generated PHP is syntactically valid
        $temp_php_file = $this->test_dir . '/generated_test.php';
        file_put_contents($temp_php_file, $php_code);
        
        // Check PHP syntax
        $output = [];
        $return_var = 0;
        exec('php -l ' . escapeshellarg($temp_php_file) . ' 2>&1', $output, $return_var);
        
        $this->assertEquals(0, $return_var, 'Generated PHP code has syntax errors: ' . implode("\n", $output));
        
        // Clean up
        unlink($temp_php_file);
    }

    /**
     * Test batch processing workflow.
     */
    public function test_batch_processing_workflow() {
        // Step 1: Create multiple test PHP files with ACF field groups
        $field_groups_data = [
            'group_batch_1' => [
                'title' => 'Batch Test Group 1',
                'fields' => [
                    [
                        'key' => 'field_batch_1_text',
                        'label' => 'Batch 1 Text Field',
                        'name' => 'batch_1_text_field',
                        'type' => 'text',
                    ],
                ],
            ],
            'group_batch_2' => [
                'title' => 'Batch Test Group 2',
                'fields' => [
                    [
                        'key' => 'field_batch_2_textarea',
                        'label' => 'Batch 2 Textarea Field',
                        'name' => 'batch_2_textarea_field',
                        'type' => 'textarea',
                    ],
                ],
            ],
            'group_batch_3' => [
                'title' => 'Batch Test Group 3',
                'fields' => [
                    [
                        'key' => 'field_batch_3_number',
                        'label' => 'Batch 3 Number Field',
                        'name' => 'batch_3_number_field',
                        'type' => 'number',
                    ],
                ],
            ],
        ];
        
        foreach ($field_groups_data as $key => $data) {
            $php_content = "<?php\nacf_add_local_field_group(array(\n";
            $php_content .= "    'key' => '{$key}',\n";
            $php_content .= "    'title' => '{$data['title']}',\n";
            $php_content .= "    'fields' => " . var_export($data['fields'], true) . ",\n";
            $php_content .= "    'location' => array(),\n";
            $php_content .= "));";
            
            $test_file = $this->test_theme_dir . "/{$key}.php";
            file_put_contents($test_file, $php_content);
        }
        
        // Step 2: Scan all theme files
        $file_manager = new File_Manager($this->logger, $this->security);
        $scanner = new Scanner_Service($this->logger, $this->security, $file_manager);
        
        $scan_results = $scanner->scan_theme_files($this->test_theme_dir, false);
        
        $this->assertEquals('success', $scan_results['status']);
        $this->assertEquals(3, $scan_results['count']);
        
        // Step 3: Batch convert all field groups
        $converter_service = new Converter_Service($this->logger, $this->security);
        $field_groups = array_values($scan_results['field_groups']);
        
        $batch_result = $converter_service->batch_convert($field_groups, 'php_to_json');
        
        $this->assertContains($batch_result['status'], ['success', 'warning']);
        $this->assertArrayHasKey('results', $batch_result);
        $this->assertCount(3, $batch_result['results']);
        
        // Step 4: Verify each conversion result
        foreach ($field_groups_data as $key => $expected_data) {
            $this->assertArrayHasKey($key, $batch_result['results']);
            $result = $batch_result['results'][$key];
            $this->assertContains($result['status'], ['success', 'warning']);
            $this->assertEquals($expected_data['title'], $result['data']['title']);
        }
        
        // Step 5: Export all field groups as ZIP
        $export_result = $file_manager->export_files($field_groups, 'zip');
        
        $this->assertTrue($export_result['success']);
        $this->assertEquals('zip', $export_result['format']);
        $this->assertEquals(3, $export_result['file_count']);
        $this->assertArrayHasKey('download_url', $export_result);
    }

    /**
     * Test error handling and recovery workflow.
     */
    public function test_error_handling_workflow() {
        // Step 1: Create PHP file with syntax error
        $invalid_php_content = <<<'PHP'
<?php
acf_add_local_field_group(array(
    'key' => 'group_invalid',
    'title' => 'Invalid Group',
    'fields' => array(
        array(
            'key' => 'field_invalid',
            'label' => 'Invalid Field',
            // Missing required properties
        ),
    ),
    // Missing closing parenthesis
));
PHP;

        $invalid_php_file = $this->test_theme_dir . '/invalid.php';
        file_put_contents($invalid_php_file, $invalid_php_content);
        
        // Step 2: Create valid PHP file
        $valid_php_content = <<<'PHP'
<?php
acf_add_local_field_group(array(
    'key' => 'group_valid',
    'title' => 'Valid Group',
    'fields' => array(
        array(
            'key' => 'field_valid',
            'label' => 'Valid Field',
            'name' => 'valid_field',
            'type' => 'text',
        ),
    ),
    'location' => array(),
));
PHP;

        $valid_php_file = $this->test_theme_dir . '/valid.php';
        file_put_contents($valid_php_file, $valid_php_content);
        
        // Step 3: Scan theme files (should handle errors gracefully)
        $file_manager = new File_Manager($this->logger, $this->security);
        $scanner = new Scanner_Service($this->logger, $this->security, $file_manager);
        
        $scan_results = $scanner->scan_theme_files($this->test_theme_dir, false);
        
        // Should still succeed with valid files, despite invalid ones
        $this->assertEquals('success', $scan_results['status']);
        $this->assertGreaterThanOrEqual(1, $scan_results['count']);
        
        // Should have found the valid field group
        $this->assertArrayHasKey('group_valid', $scan_results['field_groups']);
        
        // Step 4: Test conversion with invalid data
        $invalid_field_group = [
            'key' => 'group_test',
            'title' => 'Test Group',
            // Missing required 'fields' property
        ];
        
        $converter_service = new Converter_Service($this->logger, $this->security);
        $conversion_result = $converter_service->convert_php_to_json($invalid_field_group);
        
        $this->assertEquals('error', $conversion_result['status']);
        $this->assertArrayHasKey('errors', $conversion_result);
        
        // Step 5: Test validation with invalid data
        $validator = new Validator($this->logger);
        $validation_result = $validator->validate_field_group($invalid_field_group);
        
        $this->assertEquals('error', $validation_result['status']);
        $this->assertFalse($validation_result['valid']);
        $this->assertArrayHasKey('errors', $validation_result);
    }

    /**
     * Test backup and restore workflow.
     */
    public function test_backup_restore_workflow() {
        // Step 1: Create original files
        $original_files = [];
        for ($i = 1; $i <= 3; $i++) {
            $file_path = $this->test_theme_dir . "/original_{$i}.php";
            $content = "<?php\n// Original content {$i}\necho 'Original {$i}';";
            file_put_contents($file_path, $content);
            $original_files[] = $file_path;
        }
        
        // Step 2: Create backup
        $file_manager = new File_Manager($this->logger, $this->security);
        $backup_path = $file_manager->create_backup($original_files);
        
        $this->assertNotEmpty($backup_path);
        $this->assertTrue(is_dir($backup_path));
        
        // Verify backup files exist
        foreach ($original_files as $original_file) {
            $backup_file = $backup_path . '/' . basename($original_file);
            $this->assertTrue(file_exists($backup_file));
            $this->assertEquals(file_get_contents($original_file), file_get_contents($backup_file));
        }
        
        // Step 3: Modify original files
        foreach ($original_files as $i => $file_path) {
            $modified_content = "<?php\n// Modified content " . ($i + 1) . "\necho 'Modified " . ($i + 1) . "';";
            file_put_contents($file_path, $modified_content);
        }
        
        // Verify files were modified
        foreach ($original_files as $file_path) {
            $this->assertStringContainsString('Modified', file_get_contents($file_path));
        }
        
        // Step 4: Get backup list
        $backups = $file_manager->get_backups();
        $this->assertNotEmpty($backups);
        
        $latest_backup = $backups[0];
        $this->assertArrayHasKey('name', $latest_backup);
        $this->assertArrayHasKey('file_count', $latest_backup);
        $this->assertEquals(3, $latest_backup['file_count']);
        
        // Step 5: Restore backup
        $restore_result = $file_manager->restore_backup($latest_backup['name']);
        $this->assertTrue($restore_result);
        
        // Verify files were restored
        foreach ($original_files as $i => $file_path) {
            $content = file_get_contents($file_path);
            $this->assertStringContainsString('Original ' . ($i + 1), $content);
            $this->assertStringNotContainsString('Modified', $content);
        }
    }

    /**
     * Test theme compatibility workflow.
     */
    public function test_theme_compatibility_workflow() {
        // Step 1: Create parent theme structure
        $parent_theme_dir = $this->test_dir . '/parent-theme';
        mkdir($parent_theme_dir, 0755, true);
        
        $parent_functions = $parent_theme_dir . '/functions.php';
        $parent_content = <<<'PHP'
<?php
// Parent theme functions
acf_add_local_field_group(array(
    'key' => 'group_parent',
    'title' => 'Parent Theme Group',
    'fields' => array(
        array(
            'key' => 'field_parent',
            'label' => 'Parent Field',
            'name' => 'parent_field',
            'type' => 'text',
        ),
    ),
    'location' => array(),
));
PHP;
        file_put_contents($parent_functions, $parent_content);
        
        // Step 2: Create child theme structure
        $child_theme_dir = $this->test_dir . '/child-theme';
        mkdir($child_theme_dir, 0755, true);
        
        $child_functions = $child_theme_dir . '/functions.php';
        $child_content = <<<'PHP'
<?php
// Child theme functions
acf_add_local_field_group(array(
    'key' => 'group_child',
    'title' => 'Child Theme Group',
    'fields' => array(
        array(
            'key' => 'field_child',
            'label' => 'Child Field',
            'name' => 'child_field',
            'type' => 'textarea',
        ),
    ),
    'location' => array(),
));
PHP;
        file_put_contents($child_functions, $child_content);
        
        // Step 3: Test scanning both parent and child themes
        $file_manager = new File_Manager($this->logger, $this->security);
        $scanner = new Scanner_Service($this->logger, $this->security, $file_manager);
        
        // Mock theme paths to include both parent and child
        $theme_paths = [$parent_theme_dir, $child_theme_dir];
        
        $all_field_groups = [];
        foreach ($theme_paths as $theme_path) {
            $scan_results = $scanner->scan_theme_files($theme_path, false);
            if ($scan_results['status'] === 'success') {
                $all_field_groups = array_merge($all_field_groups, $scan_results['field_groups']);
            }
        }
        
        // Should find field groups from both themes
        $this->assertCount(2, $all_field_groups);
        $this->assertArrayHasKey('group_parent', $all_field_groups);
        $this->assertArrayHasKey('group_child', $all_field_groups);
        
        // Step 4: Test conversion of field groups from different themes
        $converter_service = new Converter_Service($this->logger, $this->security);
        
        foreach ($all_field_groups as $field_group) {
            $conversion_result = $converter_service->convert_php_to_json($field_group);
            $this->assertContains($conversion_result['status'], ['success', 'warning']);
            $this->assertArrayHasKey('data', $conversion_result);
        }
    }
}