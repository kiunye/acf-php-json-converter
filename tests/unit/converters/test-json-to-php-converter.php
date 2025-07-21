<?php
/**
 * Tests for the JSON to PHP Converter class.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Tests/Unit/Converters
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Tests
 */

use ACF_PHP_JSON_Converter\Converters\JSON_To_PHP_Converter;
use ACF_PHP_JSON_Converter\Utilities\Logger;

/**
 * JSON to PHP Converter test case.
 */
class JSON_To_PHP_Converter_Test extends WP_UnitTestCase {

    /**
     * JSON to PHP Converter instance.
     *
     * @var JSON_To_PHP_Converter
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
        $this->converter = new JSON_To_PHP_Converter($this->logger);
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
        
        // Convert to PHP
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check converted data
        $this->assertArrayHasKey('data', $result);
        $php_code = $result['data'];
        
        // Check PHP code structure
        $this->assertStringStartsWith('<?php', $php_code);
        $this->assertContains('acf_add_local_field_group(', $php_code);
        $this->assertContains("'key' => 'group_test'", $php_code);
        $this->assertContains("'title' => 'Test Field Group'", $php_code);
        $this->assertContains("'key' => 'field_test'", $php_code);
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
     * Test key format warning.
     */
    public function test_key_format_warning() {
        // Create field group with invalid key format
        $field_group = [
            'key' => 'invalid_key_format',
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
        
        // Convert to PHP
        $result = $this->converter->convert($field_group);
        
        // Check result status (should be warning due to key format)
        $this->assertEquals('warning', $result['status']);
        $this->assertArrayHasKey('warnings', $result);
        
        // Check PHP code structure
        $php_code = $result['data'];
        $this->assertContains("'key' => 'invalid_key_format'", $php_code);
    }

    /**
     * Test complex field group conversion.
     */
    public function test_complex_field_group_conversion() {
        // Create a complex field group with various field types
        $field_group = [
            'key' => 'group_complex',
            'title' => 'Complex Field Group',
            'fields' => [
                [
                    'key' => 'field_text',
                    'label' => 'Text Field',
                    'name' => 'text_field',
                    'type' => 'text',
                ],
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
                [
                    'key' => 'field_select',
                    'label' => 'Select Field',
                    'name' => 'select_field',
                    'type' => 'select',
                    'choices' => [
                        'option1' => 'Option 1',
                        'option2' => 'Option 2',
                    ],
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
            ],
        ];
        
        // Convert to PHP
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check PHP code structure
        $php_code = $result['data'];
        $this->assertContains("'key' => 'group_complex'", $php_code);
        $this->assertContains("'type' => 'repeater'", $php_code);
        $this->assertContains("'sub_fields'", $php_code);
        $this->assertContains("'choices'", $php_code);
        $this->assertContains("'option1' => 'Option 1'", $php_code);
        $this->assertContains("'location'", $php_code);
        $this->assertContains("'param' => 'post_type'", $php_code);
    }

    /**
     * Test boolean and null values conversion.
     */
    public function test_boolean_and_null_values() {
        // Create field group with boolean and null values
        $field_group = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_test',
                    'label' => 'Test Field',
                    'name' => 'test_field',
                    'type' => 'text',
                    'required' => true,
                    'allow_null' => false,
                    'default_value' => null,
                ],
            ],
        ];
        
        // Convert to PHP
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check PHP code structure
        $php_code = $result['data'];
        $this->assertContains("'required' => true", $php_code);
        $this->assertContains("'allow_null' => false", $php_code);
        $this->assertContains("'default_value' => null", $php_code);
    }

    /**
     * Test nested arrays conversion.
     */
    public function test_nested_arrays() {
        // Create field group with nested arrays
        $field_group = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_test',
                    'label' => 'Test Field',
                    'name' => 'test_field',
                    'type' => 'flexible_content',
                    'layouts' => [
                        'layout1' => [
                            'key' => 'layout_1',
                            'name' => 'layout_1',
                            'label' => 'Layout 1',
                            'sub_fields' => [
                                [
                                    'key' => 'field_layout1_text',
                                    'label' => 'Text in Layout 1',
                                    'name' => 'text_in_layout_1',
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'layout2' => [
                            'key' => 'layout_2',
                            'name' => 'layout_2',
                            'label' => 'Layout 2',
                            'sub_fields' => [
                                [
                                    'key' => 'field_layout2_text',
                                    'label' => 'Text in Layout 2',
                                    'name' => 'text_in_layout_2',
                                    'type' => 'text',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        
        // Convert to PHP
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check PHP code structure
        $php_code = $result['data'];
        $this->assertContains("'type' => 'flexible_content'", $php_code);
        $this->assertContains("'layouts'", $php_code);
        $this->assertContains("'layout1'", $php_code);
        $this->assertContains("'layout2'", $php_code);
        $this->assertContains("'key' => 'layout_1'", $php_code);
        $this->assertContains("'key' => 'layout_2'", $php_code);
        $this->assertContains("'field_layout1_text'", $php_code);
        $this->assertContains("'field_layout2_text'", $php_code);
    }

    /**
     * Test special characters in strings.
     */
    public function test_special_characters() {
        // Create field group with special characters
        $field_group = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_test',
                    'label' => "Field with 'quotes'",
                    'name' => 'test_field',
                    'type' => 'text',
                    'instructions' => "This is a field with 'single' and \"double\" quotes",
                ],
            ],
        ];
        
        // Convert to PHP
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check PHP code structure
        $php_code = $result['data'];
        $this->assertContains("'label' => 'Field with \\'quotes\\''", $php_code);
        $this->assertContains("'instructions' => 'This is a field with \\'single\\' and \"double\" quotes'", $php_code);
    }

    /**
     * Test modified date handling.
     */
    public function test_modified_date() {
        // Create field group with modified date
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
            'modified' => 1626912000,
        ];
        
        // Convert to PHP
        $result = $this->converter->convert($field_group);
        
        // Check result status
        $this->assertEquals('success', $result['status']);
        
        // Check PHP code structure
        $php_code = $result['data'];
        $this->assertContains("'modified' => 1626912000", $php_code);
        $this->assertContains("@modified", $php_code);
    }
}