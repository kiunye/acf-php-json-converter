<?php
/**
 * Tests for the PHP Parser class.
 *
 * @package ACF_PHP_JSON_Converter
 */

use ACF_PHP_JSON_Converter\Parsers\PHP_Parser;
use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Security;

use PHPUnit\Framework\TestCase;

/**
 * PHP Parser test case.
 */
class PHP_ParserTest extends TestCase {

    /**
     * PHP Parser instance.
     *
     * @var PHP_Parser
     */
    protected $parser;

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
     * Test file path.
     *
     * @var string
     */
    protected $test_file;

    /**
     * Set up.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create mock objects
        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->security = $this->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Always return true for validate_path
        $this->security->method('validate_path')
            ->willReturn(true);
        
        $this->parser = new PHP_Parser($this->logger, $this->security);
        
        // Create test file
        $this->test_file = sys_get_temp_dir() . '/acf_test_' . uniqid() . '.php';
    }

    /**
     * Tear down.
     */
    public function tearDown(): void {
        // Remove test file
        if (file_exists($this->test_file)) {
            unlink($this->test_file);
        }
        
        parent::tearDown();
    }

    /**
     * Test extract_function_calls method with simple field group.
     */
    public function test_extract_function_calls_simple() {
        $content = <<<'PHP'
<?php
acf_add_local_field_group(array(
    'key' => 'group_123',
    'title' => 'Test Group',
    'fields' => array(
        array(
            'key' => 'field_123',
            'label' => 'Test Field',
            'name' => 'test_field',
            'type' => 'text'
        )
    ),
    'location' => array(
        array(
            array(
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'post'
            )
        )
    )
));
PHP;

        $result = $this->parser->extract_function_calls($content, 'acf_add_local_field_group');
        
        $this->assertCount(1, $result);
        $this->assertEquals('group_123', $result[0]['key']);
        $this->assertEquals('Test Group', $result[0]['title']);
        $this->assertCount(1, $result[0]['fields']);
        $this->assertEquals('field_123', $result[0]['fields'][0]['key']);
    }

    /**
     * Test extract_function_calls method with multiple field groups.
     */
    public function test_extract_function_calls_multiple() {
        $content = <<<'PHP'
<?php
acf_add_local_field_group(array(
    'key' => 'group_123',
    'title' => 'Test Group 1',
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

acf_add_local_field_group(array(
    'key' => 'group_456',
    'title' => 'Test Group 2',
    'fields' => array(
        array(
            'key' => 'field_456',
            'label' => 'Test Field 2',
            'name' => 'test_field_2',
            'type' => 'text'
        )
    ),
    'location' => array()
));
PHP;

        $result = $this->parser->extract_function_calls($content, 'acf_add_local_field_group');
        
        $this->assertCount(2, $result);
        $this->assertEquals('group_123', $result[0]['key']);
        $this->assertEquals('Test Group 1', $result[0]['title']);
        $this->assertEquals('group_456', $result[1]['key']);
        $this->assertEquals('Test Group 2', $result[1]['title']);
    }

    /**
     * Test extract_function_calls method with complex field group.
     */
    public function test_extract_function_calls_complex() {
        $content = <<<'PHP'
<?php
acf_add_local_field_group(array(
    'key' => 'group_123',
    'title' => 'Complex Group',
    'fields' => array(
        array(
            'key' => 'field_123',
            'label' => 'Repeater Field',
            'name' => 'repeater_field',
            'type' => 'repeater',
            'sub_fields' => array(
                array(
                    'key' => 'field_123_1',
                    'label' => 'Sub Field',
                    'name' => 'sub_field',
                    'type' => 'text'
                ),
                array(
                    'key' => 'field_123_2',
                    'label' => 'Sub Field 2',
                    'name' => 'sub_field_2',
                    'type' => 'text'
                )
            )
        ),
        array(
            'key' => 'field_456',
            'label' => 'Flexible Content',
            'name' => 'flexible_content',
            'type' => 'flexible_content',
            'layouts' => array(
                'layout_1' => array(
                    'key' => 'layout_123',
                    'name' => 'layout_1',
                    'label' => 'Layout 1',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_456_1',
                            'label' => 'Layout Field',
                            'name' => 'layout_field',
                            'type' => 'text'
                        )
                    )
                )
            )
        )
    ),
    'location' => array(),
    'menu_order' => 0,
    'position' => 'normal',
    'style' => 'default',
    'label_placement' => 'top',
    'instruction_placement' => 'label',
    'hide_on_screen' => array('permalink', 'the_content'),
    'active' => true,
    'description' => 'This is a complex field group'
));
PHP;

        $result = $this->parser->extract_function_calls($content, 'acf_add_local_field_group');
        
        $this->assertCount(1, $result);
        $this->assertEquals('group_123', $result[0]['key']);
        $this->assertEquals('Complex Group', $result[0]['title']);
        $this->assertCount(2, $result[0]['fields']);
        
        // Check repeater field
        $this->assertEquals('repeater', $result[0]['fields'][0]['type']);
        $this->assertCount(2, $result[0]['fields'][0]['sub_fields']);
        
        // Check flexible content field
        $this->assertEquals('flexible_content', $result[0]['fields'][1]['type']);
        $this->assertArrayHasKey('layouts', $result[0]['fields'][1]);
        
        // Check additional properties
        $this->assertEquals(0, $result[0]['menu_order']);
        $this->assertEquals('normal', $result[0]['position']);
        $this->assertEquals('This is a complex field group', $result[0]['description']);
        $this->assertTrue($result[0]['active']);
    }

    /**
     * Test parse_file method.
     */
    public function test_parse_file() {
        // Create test file
        $content = <<<'PHP'
<?php
acf_add_local_field_group(array(
    'key' => 'group_123',
    'title' => 'Test Group',
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

        file_put_contents($this->test_file, $content);
        
        $result = $this->parser->parse_file($this->test_file);
        
        $this->assertCount(1, $result);
        $this->assertEquals('group_123', $result[0]['key']);
        $this->assertEquals('Test Group', $result[0]['title']);
        
        // Check if source file information was added
        $this->assertArrayHasKey('_acf_php_json_converter', $result[0]);
        $this->assertEquals($this->test_file, $result[0]['_acf_php_json_converter']['source_file']);
    }

    /**
     * Test validate_field_structure method with valid field group.
     */
    public function test_validate_field_structure_valid() {
        $field_group = array(
            'key' => 'group_123',
            'title' => 'Test Group',
            'fields' => array(
                array(
                    'key' => 'field_123',
                    'label' => 'Test Field',
                    'name' => 'test_field',
                    'type' => 'text'
                )
            ),
            'location' => array()
        );
        
        $result = $this->parser->validate_field_structure($field_group);
        
        $this->assertTrue($result);
    }

    /**
     * Test validate_field_structure method with invalid field group.
     */
    public function test_validate_field_structure_invalid() {
        // Missing required fields
        $field_group = array(
            'key' => 'group_123',
            'title' => 'Test Group',
            // Missing 'fields'
            'location' => array()
        );
        
        $result = $this->parser->validate_field_structure($field_group);
        
        $this->assertFalse($result);
    }

    /**
     * Test validate_field_structure method with invalid field.
     */
    public function test_validate_field_structure_invalid_field() {
        // Field missing required properties
        $field_group = array(
            'key' => 'group_123',
            'title' => 'Test Group',
            'fields' => array(
                array(
                    'key' => 'field_123',
                    // Missing 'label'
                    'name' => 'test_field',
                    'type' => 'text'
                )
            ),
            'location' => array()
        );
        
        $result = $this->parser->validate_field_structure($field_group);
        
        $this->assertFalse($result);
    }

    /**
     * Test validate_field_structure method with repeater field.
     */
    public function test_validate_field_structure_repeater() {
        // Repeater field with sub_fields
        $field_group = array(
            'key' => 'group_123',
            'title' => 'Test Group',
            'fields' => array(
                array(
                    'key' => 'field_123',
                    'label' => 'Repeater Field',
                    'name' => 'repeater_field',
                    'type' => 'repeater',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_123_1',
                            'label' => 'Sub Field',
                            'name' => 'sub_field',
                            'type' => 'text'
                        )
                    )
                )
            ),
            'location' => array()
        );
        
        $result = $this->parser->validate_field_structure($field_group);
        
        $this->assertTrue($result);
    }

    /**
     * Test validate_field_structure method with invalid repeater field.
     */
    public function test_validate_field_structure_invalid_repeater() {
        // Repeater field without sub_fields
        $field_group = array(
            'key' => 'group_123',
            'title' => 'Test Group',
            'fields' => array(
                array(
                    'key' => 'field_123',
                    'label' => 'Repeater Field',
                    'name' => 'repeater_field',
                    'type' => 'repeater',
                    // Missing 'sub_fields'
                )
            ),
            'location' => array()
        );
        
        $result = $this->parser->validate_field_structure($field_group);
        
        $this->assertFalse($result);
    }

    /**
     * Test get_parsing_errors method.
     */
    public function test_get_parsing_errors() {
        // Create test file with invalid field group
        $content = <<<'PHP'
<?php
acf_add_local_field_group(array(
    'key' => 'group_123',
    'title' => 'Test Group',
    // Missing 'fields'
    'location' => array()
));
PHP;

        file_put_contents($this->test_file, $content);
        
        $this->parser->parse_file($this->test_file);
        
        $errors = $this->parser->get_parsing_errors();
        
        $this->assertNotEmpty($errors);
    }

    /**
     * Test set_function_name and get_function_name methods.
     */
    public function test_set_get_function_name() {
        $this->assertEquals('acf_add_local_field_group', $this->parser->get_function_name());
        
        $this->parser->set_function_name('custom_function');
        
        $this->assertEquals('custom_function', $this->parser->get_function_name());
    }
}