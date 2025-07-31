<?php
/**
 * Integration tests for ACF compatibility across different versions.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Tests/Integration
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Tests
 */

use ACF_PHP_JSON_Converter\Converters\PHP_To_JSON_Converter;
use ACF_PHP_JSON_Converter\Converters\JSON_To_PHP_Converter;
use ACF_PHP_JSON_Converter\Converters\Validator;
use ACF_PHP_JSON_Converter\Utilities\Logger;

/**
 * ACF compatibility test case.
 */
class ACFCompatibilityTest extends WP_UnitTestCase {

    /**
     * Logger instance.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * PHP to JSON converter.
     *
     * @var PHP_To_JSON_Converter
     */
    protected $php_to_json;

    /**
     * JSON to PHP converter.
     *
     * @var JSON_To_PHP_Converter
     */
    protected $json_to_php;

    /**
     * Validator instance.
     *
     * @var Validator
     */
    protected $validator;

    /**
     * Set up.
     */
    public function setUp(): void {
        parent::setUp();
        
        $this->logger = new Logger();
        $this->php_to_json = new PHP_To_JSON_Converter($this->logger);
        $this->json_to_php = new JSON_To_PHP_Converter($this->logger);
        $this->validator = new Validator($this->logger);
    }

    /**
     * Test compatibility with ACF 5.x field group structure.
     */
    public function test_acf_5x_field_group_compatibility() {
        // ACF 5.x style field group
        $acf_5x_field_group = [
            'key' => 'group_5f8a1b2c3d4e5',
            'title' => 'ACF 5.x Field Group',
            'fields' => [
                [
                    'key' => 'field_5f8a1b2c3d4e6',
                    'label' => 'Text Field',
                    'name' => 'text_field',
                    'type' => 'text',
                    'instructions' => 'Enter some text',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'default_value' => '',
                    'placeholder' => '',
                    'prepend' => '',
                    'append' => '',
                    'maxlength' => '',
                ],
                [
                    'key' => 'field_5f8a1b2c3d4e7',
                    'label' => 'Select Field',
                    'name' => 'select_field',
                    'type' => 'select',
                    'instructions' => '',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'choices' => [
                        'value1' => 'Label 1',
                        'value2' => 'Label 2',
                        'value3' => 'Label 3',
                    ],
                    'default_value' => [],
                    'allow_null' => 0,
                    'multiple' => 0,
                    'ui' => 0,
                    'return_format' => 'value',
                    'ajax' => 0,
                    'placeholder' => '',
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
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
        ];
        
        // Test validation
        $validation_result = $this->validator->validate_field_group($acf_5x_field_group);
        $this->assertTrue($validation_result['valid']);
        
        // Test PHP to JSON conversion
        $json_result = $this->php_to_json->convert($acf_5x_field_group);
        $this->assertEquals('success', $json_result['status']);
        
        // Test JSON to PHP conversion
        $php_result = $this->json_to_php->convert($json_result['data']);
        $this->assertEquals('success', $php_result['status']);
        
        // Test bidirectional conversion accuracy
        $conversion_validation = $this->validator->validate_conversion($acf_5x_field_group, $json_result['data']);
        $this->assertTrue($conversion_validation['valid']);
    }

    /**
     * Test compatibility with ACF 6.x field group structure.
     */
    public function test_acf_6x_field_group_compatibility() {
        // ACF 6.x style field group with new features
        $acf_6x_field_group = [
            'key' => 'group_6f8a1b2c3d4e5',
            'title' => 'ACF 6.x Field Group',
            'fields' => [
                [
                    'key' => 'field_6f8a1b2c3d4e6',
                    'label' => 'Block Field',
                    'name' => 'block_field',
                    'type' => 'text',
                    'instructions' => 'This field supports blocks',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '50',
                        'class' => 'custom-class',
                        'id' => 'custom-id',
                    ],
                    'default_value' => '',
                    'placeholder' => 'Enter text...',
                    'prepend' => '$',
                    'append' => '.00',
                    'maxlength' => 100,
                ],
                [
                    'key' => 'field_6f8a1b2c3d4e7',
                    'label' => 'Link Field',
                    'name' => 'link_field',
                    'type' => 'link',
                    'instructions' => 'Select a link',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '50',
                        'class' => '',
                        'id' => '',
                    ],
                    'return_format' => 'array',
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'block',
                        'operator' => '==',
                        'value' => 'acf/testimonial',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => 'Field group for ACF 6.x blocks',
            'show_in_rest' => 1,
        ];
        
        // Test validation
        $validation_result = $this->validator->validate_field_group($acf_6x_field_group);
        $this->assertTrue($validation_result['valid']);
        
        // Test PHP to JSON conversion
        $json_result = $this->php_to_json->convert($acf_6x_field_group);
        $this->assertEquals('success', $json_result['status']);
        
        // Test JSON to PHP conversion
        $php_result = $this->json_to_php->convert($json_result['data']);
        $this->assertEquals('success', $php_result['status']);
        
        // Verify ACF 6.x specific properties are preserved
        $this->assertArrayHasKey('show_in_rest', $json_result['data']);
        $this->assertEquals(1, $json_result['data']['show_in_rest']);
    }

    /**
     * Test compatibility with complex field types.
     */
    public function test_complex_field_types_compatibility() {
        $complex_field_group = [
            'key' => 'group_complex_fields',
            'title' => 'Complex Field Types',
            'fields' => [
                // Repeater field
                [
                    'key' => 'field_repeater_complex',
                    'label' => 'Complex Repeater',
                    'name' => 'complex_repeater',
                    'type' => 'repeater',
                    'instructions' => 'Add repeater items',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'collapsed' => '',
                    'min' => 1,
                    'max' => 10,
                    'layout' => 'table',
                    'button_label' => 'Add Item',
                    'sub_fields' => [
                        [
                            'key' => 'field_repeater_text',
                            'label' => 'Repeater Text',
                            'name' => 'repeater_text',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '50',
                                'class' => '',
                                'id' => '',
                            ],
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ],
                        [
                            'key' => 'field_repeater_image',
                            'label' => 'Repeater Image',
                            'name' => 'repeater_image',
                            'type' => 'image',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '50',
                                'class' => '',
                                'id' => '',
                            ],
                            'return_format' => 'array',
                            'preview_size' => 'medium',
                            'library' => 'all',
                            'min_width' => '',
                            'min_height' => '',
                            'min_size' => '',
                            'max_width' => '',
                            'max_height' => '',
                            'max_size' => '',
                            'mime_types' => '',
                        ],
                    ],
                ],
                // Flexible content field
                [
                    'key' => 'field_flexible_complex',
                    'label' => 'Complex Flexible Content',
                    'name' => 'complex_flexible',
                    'type' => 'flexible_content',
                    'instructions' => 'Add flexible content layouts',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'layouts' => [
                        'layout_text' => [
                            'key' => 'layout_text_block',
                            'name' => 'text_block',
                            'label' => 'Text Block',
                            'display' => 'block',
                            'sub_fields' => [
                                [
                                    'key' => 'field_text_content',
                                    'label' => 'Text Content',
                                    'name' => 'text_content',
                                    'type' => 'wysiwyg',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'tabs' => 'all',
                                    'toolbar' => 'full',
                                    'media_upload' => 1,
                                    'delay' => 0,
                                ],
                            ],
                            'min' => '',
                            'max' => '',
                        ],
                        'layout_image' => [
                            'key' => 'layout_image_block',
                            'name' => 'image_block',
                            'label' => 'Image Block',
                            'display' => 'block',
                            'sub_fields' => [
                                [
                                    'key' => 'field_image_content',
                                    'label' => 'Image',
                                    'name' => 'image',
                                    'type' => 'image',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '50',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'return_format' => 'array',
                                    'preview_size' => 'medium',
                                    'library' => 'all',
                                ],
                                [
                                    'key' => 'field_image_caption',
                                    'label' => 'Caption',
                                    'name' => 'caption',
                                    'type' => 'text',
                                    'instructions' => '',
                                    'required' => 0,
                                    'conditional_logic' => 0,
                                    'wrapper' => [
                                        'width' => '50',
                                        'class' => '',
                                        'id' => '',
                                    ],
                                    'default_value' => '',
                                    'placeholder' => '',
                                    'prepend' => '',
                                    'append' => '',
                                    'maxlength' => '',
                                ],
                            ],
                            'min' => '',
                            'max' => '',
                        ],
                    ],
                    'button_label' => 'Add Layout',
                    'min' => '',
                    'max' => '',
                ],
                // Group field
                [
                    'key' => 'field_group_complex',
                    'label' => 'Complex Group',
                    'name' => 'complex_group',
                    'type' => 'group',
                    'instructions' => 'Group of related fields',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '',
                        'class' => '',
                        'id' => '',
                    ],
                    'layout' => 'block',
                    'sub_fields' => [
                        [
                            'key' => 'field_group_title',
                            'label' => 'Group Title',
                            'name' => 'title',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 1,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'default_value' => '',
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'maxlength' => '',
                        ],
                        [
                            'key' => 'field_group_description',
                            'label' => 'Group Description',
                            'name' => 'description',
                            'type' => 'textarea',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => [
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ],
                            'default_value' => '',
                            'placeholder' => '',
                            'maxlength' => '',
                            'rows' => '',
                            'new_lines' => '',
                        ],
                    ],
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
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => '',
            'active' => true,
            'description' => '',
        ];
        
        // Test validation
        $validation_result = $this->validator->validate_field_group($complex_field_group);
        $this->assertTrue($validation_result['valid']);
        
        // Test PHP to JSON conversion
        $json_result = $this->php_to_json->convert($complex_field_group);
        $this->assertEquals('success', $json_result['status']);
        
        // Verify complex field structures are preserved
        $json_data = $json_result['data'];
        
        // Check repeater field
        $repeater_field = null;
        foreach ($json_data['fields'] as $field) {
            if ($field['type'] === 'repeater') {
                $repeater_field = $field;
                break;
            }
        }
        $this->assertNotNull($repeater_field);
        $this->assertArrayHasKey('sub_fields', $repeater_field);
        $this->assertCount(2, $repeater_field['sub_fields']);
        $this->assertEquals('table', $repeater_field['layout']);
        $this->assertEquals(1, $repeater_field['min']);
        $this->assertEquals(10, $repeater_field['max']);
        
        // Check flexible content field
        $flexible_field = null;
        foreach ($json_data['fields'] as $field) {
            if ($field['type'] === 'flexible_content') {
                $flexible_field = $field;
                break;
            }
        }
        $this->assertNotNull($flexible_field);
        $this->assertArrayHasKey('layouts', $flexible_field);
        $this->assertCount(2, $flexible_field['layouts']);
        
        // Check group field
        $group_field = null;
        foreach ($json_data['fields'] as $field) {
            if ($field['type'] === 'group') {
                $group_field = $field;
                break;
            }
        }
        $this->assertNotNull($group_field);
        $this->assertArrayHasKey('sub_fields', $group_field);
        $this->assertCount(2, $group_field['sub_fields']);
        $this->assertEquals('block', $group_field['layout']);
        
        // Test JSON to PHP conversion
        $php_result = $this->json_to_php->convert($json_data);
        $this->assertEquals('success', $php_result['status']);
        
        // Verify PHP code contains complex structures
        $php_code = $php_result['data'];
        $this->assertStringContainsString("'type' => 'repeater'", $php_code);
        $this->assertStringContainsString("'sub_fields'", $php_code);
        $this->assertStringContainsString("'type' => 'flexible_content'", $php_code);
        $this->assertStringContainsString("'layouts'", $php_code);
        $this->assertStringContainsString("'type' => 'group'", $php_code);
    }

    /**
     * Test compatibility with conditional logic.
     */
    public function test_conditional_logic_compatibility() {
        $field_group_with_conditional_logic = [
            'key' => 'group_conditional',
            'title' => 'Conditional Logic Test',
            'fields' => [
                [
                    'key' => 'field_trigger',
                    'label' => 'Trigger Field',
                    'name' => 'trigger_field',
                    'type' => 'select',
                    'choices' => [
                        'show' => 'Show Fields',
                        'hide' => 'Hide Fields',
                    ],
                    'default_value' => 'hide',
                ],
                [
                    'key' => 'field_conditional_simple',
                    'label' => 'Simple Conditional Field',
                    'name' => 'conditional_simple',
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
                [
                    'key' => 'field_conditional_complex',
                    'label' => 'Complex Conditional Field',
                    'name' => 'conditional_complex',
                    'type' => 'textarea',
                    'conditional_logic' => [
                        [
                            [
                                'field' => 'field_trigger',
                                'operator' => '==',
                                'value' => 'show',
                            ],
                            [
                                'field' => 'field_conditional_simple',
                                'operator' => '!=',
                                'value' => '',
                            ],
                        ],
                        [
                            [
                                'field' => 'field_trigger',
                                'operator' => '==',
                                'value' => 'hide',
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
        
        // Test validation
        $validation_result = $this->validator->validate_field_group($field_group_with_conditional_logic);
        $this->assertTrue($validation_result['valid']);
        
        // Test PHP to JSON conversion
        $json_result = $this->php_to_json->convert($field_group_with_conditional_logic);
        $this->assertEquals('success', $json_result['status']);
        
        // Verify conditional logic is preserved
        $json_data = $json_result['data'];
        
        $conditional_fields = array_filter($json_data['fields'], function($field) {
            return isset($field['conditional_logic']);
        });
        
        $this->assertCount(2, $conditional_fields);
        
        // Test JSON to PHP conversion
        $php_result = $this->json_to_php->convert($json_data);
        $this->assertEquals('success', $php_result['status']);
        
        // Verify PHP code contains conditional logic
        $php_code = $php_result['data'];
        $this->assertStringContainsString("'conditional_logic'", $php_code);
        $this->assertStringContainsString("'field' => 'field_trigger'", $php_code);
        $this->assertStringContainsString("'operator' => '=='", $php_code);
    }

    /**
     * Test compatibility with location rules.
     */
    public function test_location_rules_compatibility() {
        $field_group_with_location_rules = [
            'key' => 'group_location_rules',
            'title' => 'Location Rules Test',
            'fields' => [
                [
                    'key' => 'field_location_test',
                    'label' => 'Location Test Field',
                    'name' => 'location_test',
                    'type' => 'text',
                ],
            ],
            'location' => [
                // OR group 1: Post type is 'post' AND post template is 'custom'
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ],
                    [
                        'param' => 'post_template',
                        'operator' => '==',
                        'value' => 'custom-template.php',
                    ],
                ],
                // OR group 2: Post type is 'page' AND page template is 'front-page'
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
                    ],
                    [
                        'param' => 'page_template',
                        'operator' => '==',
                        'value' => 'front-page.php',
                    ],
                ],
                // OR group 3: User role is 'administrator'
                [
                    [
                        'param' => 'current_user_role',
                        'operator' => '==',
                        'value' => 'administrator',
                    ],
                ],
            ],
        ];
        
        // Test validation
        $validation_result = $this->validator->validate_field_group($field_group_with_location_rules);
        $this->assertTrue($validation_result['valid']);
        
        // Test PHP to JSON conversion
        $json_result = $this->php_to_json->convert($field_group_with_location_rules);
        $this->assertEquals('success', $json_result['status']);
        
        // Verify location rules are preserved
        $json_data = $json_result['data'];
        $this->assertArrayHasKey('location', $json_data);
        $this->assertCount(3, $json_data['location']); // 3 OR groups
        
        // Check first OR group (2 AND conditions)
        $this->assertCount(2, $json_data['location'][0]);
        $this->assertEquals('post_type', $json_data['location'][0][0]['param']);
        $this->assertEquals('post_template', $json_data['location'][0][1]['param']);
        
        // Check second OR group (2 AND conditions)
        $this->assertCount(2, $json_data['location'][1]);
        $this->assertEquals('post_type', $json_data['location'][1][0]['param']);
        $this->assertEquals('page_template', $json_data['location'][1][1]['param']);
        
        // Check third OR group (1 condition)
        $this->assertCount(1, $json_data['location'][2]);
        $this->assertEquals('current_user_role', $json_data['location'][2][0]['param']);
        
        // Test JSON to PHP conversion
        $php_result = $this->json_to_php->convert($json_data);
        $this->assertEquals('success', $php_result['status']);
        
        // Verify PHP code contains location rules
        $php_code = $php_result['data'];
        $this->assertStringContainsString("'location'", $php_code);
        $this->assertStringContainsString("'param' => 'post_type'", $php_code);
        $this->assertStringContainsString("'param' => 'post_template'", $php_code);
        $this->assertStringContainsString("'param' => 'current_user_role'", $php_code);
    }

    /**
     * Test compatibility with field settings and options.
     */
    public function test_field_settings_compatibility() {
        $field_group_with_settings = [
            'key' => 'group_field_settings',
            'title' => 'Field Settings Test',
            'fields' => [
                [
                    'key' => 'field_text_with_settings',
                    'label' => 'Text Field with Settings',
                    'name' => 'text_with_settings',
                    'type' => 'text',
                    'instructions' => 'This field has various settings',
                    'required' => 1,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '50',
                        'class' => 'custom-text-field',
                        'id' => 'custom-text-id',
                    ],
                    'default_value' => 'Default text value',
                    'placeholder' => 'Enter text here...',
                    'prepend' => 'Prefix:',
                    'append' => ':Suffix',
                    'maxlength' => 255,
                ],
                [
                    'key' => 'field_number_with_settings',
                    'label' => 'Number Field with Settings',
                    'name' => 'number_with_settings',
                    'type' => 'number',
                    'instructions' => 'Enter a number within range',
                    'required' => 0,
                    'conditional_logic' => 0,
                    'wrapper' => [
                        'width' => '50',
                        'class' => '',
                        'id' => '',
                    ],
                    'default_value' => 10,
                    'placeholder' => '',
                    'prepend' => '$',
                    'append' => '.00',
                    'min' => 1,
                    'max' => 100,
                    'step' => 1,
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
            'menu_order' => 5,
            'position' => 'side',
            'style' => 'seamless',
            'label_placement' => 'left',
            'instruction_placement' => 'field',
            'hide_on_screen' => [
                'permalink',
                'the_content',
                'excerpt',
                'discussion',
                'comments',
                'revisions',
                'slug',
                'author',
                'format',
                'page_attributes',
                'featured_image',
                'categories',
                'tags',
                'send-trackbacks',
            ],
            'active' => true,
            'description' => 'This field group tests various field settings and options',
        ];
        
        // Test validation
        $validation_result = $this->validator->validate_field_group($field_group_with_settings);
        $this->assertTrue($validation_result['valid']);
        
        // Test PHP to JSON conversion
        $json_result = $this->php_to_json->convert($field_group_with_settings);
        $this->assertEquals('success', $json_result['status']);
        
        // Verify all settings are preserved
        $json_data = $json_result['data'];
        
        // Check field group settings
        $this->assertEquals(5, $json_data['menu_order']);
        $this->assertEquals('side', $json_data['position']);
        $this->assertEquals('seamless', $json_data['style']);
        $this->assertEquals('left', $json_data['label_placement']);
        $this->assertEquals('field', $json_data['instruction_placement']);
        $this->assertIsArray($json_data['hide_on_screen']);
        $this->assertContains('permalink', $json_data['hide_on_screen']);
        
        // Check field settings
        $text_field = $json_data['fields'][0];
        $this->assertEquals(1, $text_field['required']);
        $this->assertEquals('50', $text_field['wrapper']['width']);
        $this->assertEquals('custom-text-field', $text_field['wrapper']['class']);
        $this->assertEquals('Default text value', $text_field['default_value']);
        $this->assertEquals('Enter text here...', $text_field['placeholder']);
        $this->assertEquals('Prefix:', $text_field['prepend']);
        $this->assertEquals(':Suffix', $text_field['append']);
        $this->assertEquals(255, $text_field['maxlength']);
        
        $number_field = $json_data['fields'][1];
        $this->assertEquals(10, $number_field['default_value']);
        $this->assertEquals('$', $number_field['prepend']);
        $this->assertEquals('.00', $number_field['append']);
        $this->assertEquals(1, $number_field['min']);
        $this->assertEquals(100, $number_field['max']);
        $this->assertEquals(1, $number_field['step']);
        
        // Test JSON to PHP conversion
        $php_result = $this->json_to_php->convert($json_data);
        $this->assertEquals('success', $php_result['status']);
        
        // Verify PHP code contains all settings
        $php_code = $php_result['data'];
        $this->assertStringContainsString("'menu_order' => 5", $php_code);
        $this->assertStringContainsString("'position' => 'side'", $php_code);
        $this->assertStringContainsString("'style' => 'seamless'", $php_code);
        $this->assertStringContainsString("'label_placement' => 'left'", $php_code);
        $this->assertStringContainsString("'instruction_placement' => 'field'", $php_code);
        $this->assertStringContainsString("'hide_on_screen'", $php_code);
        $this->assertStringContainsString("'required' => 1", $php_code);
        $this->assertStringContainsString("'maxlength' => 255", $php_code);
        $this->assertStringContainsString("'min' => 1", $php_code);
        $this->assertStringContainsString("'max' => 100", $php_code);
    }
}