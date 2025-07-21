<?php
/**
 * Tests for the Validator class.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Tests/Unit/Converters
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Tests
 */

use ACF_PHP_JSON_Converter\Converters\Validator;
use ACF_PHP_JSON_Converter\Utilities\Logger;

/**
 * Validator test case.
 */
class Validator_Test extends WP_UnitTestCase {

    /**
     * Validator instance.
     *
     * @var Validator
     */
    protected $validator;

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
        
        // Create validator instance
        $this->validator = new Validator($this->logger);
    }

    /**
     * Test field group validation with valid data.
     */
    public function test_valid_field_group() {
        // Create a valid field group
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
        
        // Validate field group
        $result = $this->validator->validate_field_group($field_group);
        
        // Check result
        $this->assertEquals('warning', $result['status']);
        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('warnings', $result);
        
        // Should have warning about key format
        $this->assertCount(1, $result['warnings']);
        $this->assertStringContainsString('key', $result['warnings'][0]);
    }

    /**
     * Test field group validation with invalid data.
     */
    public function test_invalid_field_group() {
        // Test with non-array input
        $result = $this->validator->validate_field_group('not an array');
        $this->assertEquals('error', $result['status']);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
        
        // Test with missing required fields
        $result = $this->validator->validate_field_group(['title' => 'Missing Fields']);
        $this->assertEquals('error', $result['status']);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
        
        // Test with fields not being an array
        $result = $this->validator->validate_field_group([
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => 'not an array',
        ]);
        $this->assertEquals('error', $result['status']);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
    }

    /**
     * Test field validation.
     */
    public function test_field_validation() {
        // Create field group with invalid field
        $field_group = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_test',
                    // Missing label
                    'name' => 'test_field',
                    'type' => 'text',
                ],
            ],
        ];
        
        // Validate field group
        $result = $this->validator->validate_field_group($field_group);
        
        // Check result
        $this->assertEquals('error', $result['status']);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertStringContainsString('label', $result['errors'][0]);
    }

    /**
     * Test repeater field validation.
     */
    public function test_repeater_field_validation() {
        // Create field group with repeater field missing sub_fields
        $field_group = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_repeater',
                    'label' => 'Repeater Field',
                    'name' => 'repeater_field',
                    'type' => 'repeater',
                    // Missing sub_fields
                ],
            ],
        ];
        
        // Validate field group
        $result = $this->validator->validate_field_group($field_group);
        
        // Check result
        $this->assertEquals('error', $result['status']);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertStringContainsString('sub_fields', $result['errors'][0]);
        
        // Create field group with valid repeater field
        $field_group = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
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
        
        // Validate field group
        $result = $this->validator->validate_field_group($field_group);
        
        // Check result (should have warnings about key format)
        $this->assertEquals('warning', $result['status']);
        $this->assertTrue($result['valid']);
    }

    /**
     * Test flexible content field validation.
     */
    public function test_flexible_content_field_validation() {
        // Create field group with flexible content field missing layouts
        $field_group = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_flexible',
                    'label' => 'Flexible Content Field',
                    'name' => 'flexible_field',
                    'type' => 'flexible_content',
                    // Missing layouts
                ],
            ],
        ];
        
        // Validate field group
        $result = $this->validator->validate_field_group($field_group);
        
        // Check result
        $this->assertEquals('error', $result['status']);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertStringContainsString('layouts', $result['errors'][0]);
        
        // Create field group with flexible content layout missing sub_fields
        $field_group = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
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
                            // Missing sub_fields
                        ],
                    ],
                ],
            ],
        ];
        
        // Validate field group
        $result = $this->validator->validate_field_group($field_group);
        
        // Check result
        $this->assertEquals('error', $result['status']);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertStringContainsString('sub_fields', $result['errors'][0]);
    }

    /**
     * Test location rules validation.
     */
    public function test_location_rules_validation() {
        // Create field group with invalid location rules
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
            'location' => [
                [
                    [
                        // Missing param
                        'operator' => '==',
                        'value' => 'post',
                    ],
                ],
            ],
        ];
        
        // Validate field group
        $result = $this->validator->validate_field_group($field_group);
        
        // Check result
        $this->assertEquals('error', $result['status']);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertStringContainsString('param', $result['errors'][0]);
        
        // Create field group with valid location rules
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
        
        // Validate field group
        $result = $this->validator->validate_field_group($field_group);
        
        // Check result (should have warnings about key format)
        $this->assertEquals('warning', $result['status']);
        $this->assertTrue($result['valid']);
    }

    /**
     * Test conditional logic validation.
     */
    public function test_conditional_logic_validation() {
        // Create field group with invalid conditional logic
        $field_group = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
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
                                // Missing field
                                'operator' => '==',
                                'value' => 'show',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        
        // Validate field group
        $result = $this->validator->validate_field_group($field_group);
        
        // Check result
        $this->assertEquals('error', $result['status']);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertStringContainsString('field', $result['errors'][0]);
    }

    /**
     * Test conversion validation with identical data.
     */
    public function test_conversion_validation_identical() {
        // Create original and converted data (identical)
        $original = [
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
        
        $converted = $original;
        
        // Validate conversion
        $result = $this->validator->validate_conversion($original, $converted);
        
        // Check result
        $this->assertEquals('success', $result['status']);
        $this->assertTrue($result['valid']);
    }

    /**
     * Test conversion validation with differences.
     */
    public function test_conversion_validation_differences() {
        // Create original and converted data with differences
        $original = [
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
        
        $converted = [
            'key' => 'group_test',
            'title' => 'Modified Field Group', // Different title
            'fields' => [
                [
                    'key' => 'field_test',
                    'label' => 'Test Field',
                    'name' => 'test_field',
                    'type' => 'text',
                ],
            ],
        ];
        
        // Validate conversion
        $result = $this->validator->validate_conversion($original, $converted);
        
        // Check result
        $this->assertEquals('warning', $result['status']);
        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertStringContainsString('title', $result['warnings'][0]);
    }

    /**
     * Test conversion validation with missing fields.
     */
    public function test_conversion_validation_missing_fields() {
        // Create original and converted data with missing fields
        $original = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_test1',
                    'label' => 'Test Field 1',
                    'name' => 'test_field1',
                    'type' => 'text',
                ],
                [
                    'key' => 'field_test2',
                    'label' => 'Test Field 2',
                    'name' => 'test_field2',
                    'type' => 'text',
                ],
            ],
        ];
        
        $converted = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_test1',
                    'label' => 'Test Field 1',
                    'name' => 'test_field1',
                    'type' => 'text',
                ],
                // Missing field_test2
            ],
        ];
        
        // Validate conversion
        $result = $this->validator->validate_conversion($original, $converted);
        
        // Check result
        $this->assertEquals('error', $result['status']);
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertStringContainsString('field_test2', $result['errors'][0]);
    }

    /**
     * Test conversion validation with extra fields.
     */
    public function test_conversion_validation_extra_fields() {
        // Create original and converted data with extra fields
        $original = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_test1',
                    'label' => 'Test Field 1',
                    'name' => 'test_field1',
                    'type' => 'text',
                ],
            ],
        ];
        
        $converted = [
            'key' => 'group_test',
            'title' => 'Test Field Group',
            'fields' => [
                [
                    'key' => 'field_test1',
                    'label' => 'Test Field 1',
                    'name' => 'test_field1',
                    'type' => 'text',
                ],
                [
                    'key' => 'field_test2', // Extra field
                    'label' => 'Test Field 2',
                    'name' => 'test_field2',
                    'type' => 'text',
                ],
            ],
        ];
        
        // Validate conversion
        $result = $this->validator->validate_conversion($original, $converted);
        
        // Check result
        $this->assertEquals('warning', $result['status']);
        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertStringContainsString('field_test2', $result['warnings'][0]);
    }

    /**
     * Test conversion validation with complex field groups.
     */
    public function test_conversion_validation_complex() {
        // Create original and converted data with complex structure
        $original = [
            'key' => 'group_complex',
            'title' => 'Complex Field Group',
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
                [
                    'key' => 'field_flexible',
                    'label' => 'Flexible Content Field',
                    'name' => 'flexible_field',
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
        
        // Create a copy with a small difference
        $converted = json_decode(json_encode($original), true);
        $converted['fields'][0]['sub_fields'][0]['label'] = 'Modified Sub Field';
        
        // Validate conversion
        $result = $this->validator->validate_conversion($original, $converted);
        
        // Check result
        $this->assertEquals('warning', $result['status']);
        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('warnings', $result);
    }
}