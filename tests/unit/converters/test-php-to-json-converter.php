<?php
/**
 * Tests for the PHP to JSON Converter class.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Tests/Unit/Converters
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Tests
 */

use ACF_PHP_JSON_Converter\Converters\PHP_To_JSON_Converter;
use ACF_PHP_JSON_Converter\Utilities\Logger;

/**
 * PHP to JSON Converter test case.
 */
class PHP_To_JSON_Converter_Test extends WP_UnitTestCase {

    /**
     * PHP to JSON Converter instance.
     *
     * @var PHP_To_JSON_Converter
     */
    protected $converter;

    /**
     * Logger mock.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Set up.
     */
    public function setUp() {
        parent::setUp();
        
        // Create logger mock
        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Create converter instance
        $this->converter = new PHP_To_JSON_Converter($this->logger);
    }

    /**
     * Test basic conversion.
     */
    public function test_basic_conversion() {
        // Create a simple field group
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
        
        // Convert to JSON
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check converted data
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('group_test', $result['data']['key']);
        $this->assertEquals('Test Field Group', $result['data']['title']);
        $this->assertCount(1, $result['data']['fields']);
        $this->assertEquals('field_test', $result['data']['fields'][0]['key']);
    }

    /**
     * Test conversion with invalid input.
     */
    public function test_invalid_input() {
        // Test with non-array input
        $result = $this->converter->convert('not an array');
        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('errors', $result);
        
        // Test with missing required fields
        $result = $this->converter->convert(['title' => 'Missing Fields']);
        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('errors', $result);
        
        // Test with fields not being an array
        $result = $this->converter->convert([
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => 'not an array',
        ]);
        $this->assertEquals('error', $result['status']);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * Test key formatting.
     */
    public function test_key_formatting() {
        // Create field group with invalid key format
        $field_group = [
            'key' => 'invalid_key_format',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'invalid_field_key',
                    'label' => 'Test Field',
                    'name' => 'test_field',
                    'type' => 'text',
                ],
            ],
        ];
        
        // Convert to JSON
        $result = $this->converter->convert($field_group);
        
        // Check result status (should be warning due to key reformatting)
        $this->assertEquals('warning', $result['status']);
        $this->assertArrayHasKey('warnings', $result);
        
        // Check if keys were reformatted
        $this->assertStringStartsWith('group_', $result['data']['key']);
        $this->assertStringStartsWith('field_', $result['data']['fields'][0]['key']);
    }

    /**
     * Test repeater field conversion.
     */
    public function test_repeater_field_conversion() {
        // Create field group with repeater field
        $field_group = [
            'key' => 'group_repeater_test',
            'title' => 'Repeater Test',
            'fields' => [
                [
                    'key' => 'field_repeater',
                    'label' => 'Repeater Field',
                    'name' => 'repeater_field',
                    'type' => 'repeater',
                    'sub_fields' => [
                        [
                            'key' => 'field_subfield',
                            'label' => 'Sub Field',
                            'name' => 'sub_field',
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];
        
        // Convert to JSON
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check if repeater field was properly converted
        $this->assertEquals('repeater', $result['data']['fields'][0]['type']);
        $this->assertArrayHasKey('sub_fields', $result['data']['fields'][0]);
        $this->assertCount(1, $result['data']['fields'][0]['sub_fields']);
        $this->assertEquals('field_subfield', $result['data']['fields'][0]['sub_fields'][0]['key']);
    }

    /**
     * Test flexible content field conversion.
     */
    public function test_flexible_content_field_conversion() {
        // Create field group with flexible content field
        $field_group = [
            'key' => 'group_flexible_test',
            'title' => 'Flexible Content Test',
            'fields' => [
                [
                    'key' => 'field_flexible',
                    'label' => 'Flexible Content Field',
                    'name' => 'flexible_field',
                    'type' => 'flexible_content',
                    'layouts' => [
                        [
                            'key' => 'layout_text',
                            'name' => 'text_layout',
                            'label' => 'Text Layout',
                            'sub_fields' => [
                                [
                                    'key' => 'field_layout_text',
                                    'label' => 'Text Field',
                                    'name' => 'text_field',
                                    'type' => 'text',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        
        // Convert to JSON
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check if flexible content field was properly converted
        $this->assertEquals('flexible_content', $result['data']['fields'][0]['type']);
        $this->assertArrayHasKey('layouts', $result['data']['fields'][0]);
        $this->assertCount(1, $result['data']['fields'][0]['layouts']);
        $this->assertEquals('layout_text', $result['data']['fields'][0]['layouts'][0]['key']);
        $this->assertArrayHasKey('sub_fields', $result['data']['fields'][0]['layouts'][0]);
        $this->assertCount(1, $result['data']['fields'][0]['layouts'][0]['sub_fields']);
    }

    /**
     * Test group field conversion.
     */
    public function test_group_field_conversion() {
        // Create field group with group field
        $field_group = [
            'key' => 'group_test',
            'title' => 'Group Field Test',
            'fields' => [
                [
                    'key' => 'field_group',
                    'label' => 'Group Field',
                    'name' => 'group_field',
                    'type' => 'group',
                    'sub_fields' => [
                        [
                            'key' => 'field_in_group',
                            'label' => 'Field in Group',
                            'name' => 'field_in_group',
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];
        
        // Convert to JSON
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check if group field was properly converted
        $this->assertEquals('group', $result['data']['fields'][0]['type']);
        $this->assertArrayHasKey('sub_fields', $result['data']['fields'][0]);
        $this->assertCount(1, $result['data']['fields'][0]['sub_fields']);
        $this->assertEquals('field_in_group', $result['data']['fields'][0]['sub_fields'][0]['key']);
    }

    /**
     * Test clone field conversion.
     */
    public function test_clone_field_conversion() {
        // Create field group with clone field
        $field_group = [
            'key' => 'group_clone_test',
            'title' => 'Clone Field Test',
            'fields' => [
                [
                    'key' => 'field_clone',
                    'label' => 'Clone Field',
                    'name' => 'clone_field',
                    'type' => 'clone',
                    'clone' => [
                        'invalid_clone_key',
                    ],
                ],
            ],
        ];
        
        // Convert to JSON
        $result = $this->converter->convert($field_group);
        
        // Check result status (should be warning due to key reformatting)
        $this->assertEquals('warning', $result['status']);
        
        // Check if clone field was properly converted
        $this->assertEquals('clone', $result['data']['fields'][0]['type']);
        $this->assertArrayHasKey('clone', $result['data']['fields'][0]);
        $this->assertCount(1, $result['data']['fields'][0]['clone']);
        $this->assertStringStartsWith('field_', $result['data']['fields'][0]['clone'][0]);
    }

    /**
     * Test location rules preservation.
     */
    public function test_location_rules_preservation() {
        // Create field group with location rules
        $field_group = [
            'key' => 'group_location_test',
            'title' => 'Location Rules Test',
            'fields' => [
                [
                    'key' => 'field_test',
                    'label' => 'Test Field',
                    'name' => 'test_field',
                    'type' => 'text',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ],
                ],
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
                    ],
                ],
            ],
        ];
        
        // Convert to JSON
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check if location rules were preserved
        $this->assertArrayHasKey('location', $result['data']);
        $this->assertCount(2, $result['data']['location']);
        $this->assertEquals('post_type', $result['data']['location'][0][0]['param']);
        $this->assertEquals('post', $result['data']['location'][0][0]['value']);
        $this->assertEquals('page', $result['data']['location'][1][0]['value']);
    }

    /**
     * Test conditional logic preservation.
     */
    public function test_conditional_logic_preservation() {
        // Create field group with conditional logic
        $field_group = [
            'key' => 'group_conditional_test',
            'title' => 'Conditional Logic Test',
            'fields' => [
                [
                    'key' => 'field_trigger',
                    'label' => 'Trigger Field',
                    'name' => 'trigger_field',
                    'type' => 'text',
                ],
                [
                    'key' => 'field_conditional',
                    'label' => 'Conditional Field',
                    'name' => 'conditional_field',
                    'type' => 'text',
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_trigger',
                                'operator' => '==',
                                'value' => 'show',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        
        // Convert to JSON
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check if conditional logic was preserved
        $this->assertArrayHasKey('conditional_logic', $result['data']['fields'][1]);
        $this->assertEquals('field_trigger', $result['data']['fields'][1]['conditional_logic'][0][0]['field']);
        $this->assertEquals('==', $result['data']['fields'][1]['conditional_logic'][0][0]['operator']);
        $this->assertEquals('show', $result['data']['fields'][1]['conditional_logic'][0][0]['value']);
    }

    /**
     * Test source file information handling.
     */
    public function test_source_file_info_handling() {
        // Create field group with source file information
        $field_group = [
            'key' => 'group_source_test',
            'title' => 'Source Info Test',
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
                'modified_date' => 1626912000,
            ],
        ];
        
        // Convert to JSON
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check if source info was removed and modified date was preserved
        $this->assertArrayNotHasKey('_acf_php_json_converter', $result['data']);
        $this->assertArrayHasKey('modified', $result['data']);
        $this->assertEquals(1626912000, $result['data']['modified']);
    }
}