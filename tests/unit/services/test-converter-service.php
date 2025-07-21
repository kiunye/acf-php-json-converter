<?php
/**
 * Tests for the Converter Service class.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Tests/Unit/Services
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Tests
 */

use ACF_PHP_JSON_Converter\Services\Converter_Service;
use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Security;

/**
 * Converter Service test case.
 */
class Converter_Service_Test extends WP_UnitTestCase {

    /**
     * Converter Service instance.
     *
     * @var Converter_Service
     */
    protected $service;

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
     * Set up.
     */
    public function setUp() {
        parent::setUp();
        
        // Create logger mock
        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Create security mock
        $this->security = $this->getMockBuilder(Security::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Create service instance
        $this->service = new Converter_Service($this->logger, $this->security);
    }

    /**
     * Test PHP to JSON conversion.
     */
    public function test_php_to_json_conversion() {
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
        $result = $this->service->convert_php_to_json($field_group);
        
        // Check result
        $this->assertArrayHasKey('status', $result);
        $this->assertContains($result['status'], ['success', 'warning', 'error']);
        
        if ($result['status'] === 'success' || $result['status'] === 'warning') {
            $this->assertArrayHasKey('data', $result);
            $this->assertEquals('group_test', $result['data']['key']);
            $this->assertEquals('Test Field Group', $result['data']['title']);
        }
    }

    /**
     * Test batch conversion.
     */
    public function test_batch_conversion() {
        // Create multiple field groups
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
        
        // Batch convert to JSON
        $result = $this->service->batch_convert($field_groups, 'php_to_json');
        
        // Check result
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('results', $result);
        $this->assertCount(2, $result['results']);
        
        // Check individual results
        $this->assertArrayHasKey('group_test1', $result['results']);
        $this->assertArrayHasKey('group_test2', $result['results']);
    }

    /**
     * Test batch conversion with empty input.
     */
    public function test_batch_conversion_empty_input() {
        // Test with empty array
        $result = $this->service->batch_convert([], 'php_to_json');
        $this->assertEquals('error', $result['status']);
        
        // Test with non-array
        $result = $this->service->batch_convert('not an array', 'php_to_json');
        $this->assertEquals('error', $result['status']);
    }

    /**
     * Test batch conversion with invalid direction.
     */
    public function test_batch_conversion_invalid_direction() {
        // Create a field group
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
        
        // Test with invalid direction
        $result = $this->service->batch_convert([$field_group], 'invalid_direction');
        
        // Check if error is reported for each field group
        $this->assertArrayHasKey('group_test', $result['results']);
        $this->assertEquals('error', $result['results']['group_test']['status']);
    }
}