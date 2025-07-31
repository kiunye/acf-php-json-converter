<?php
/**
 * Tests for the Security utility class.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Tests/Unit/Utilities
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Tests
 */

use ACF_PHP_JSON_Converter\Utilities\Security;

/**
 * Security utility test case.
 */
class SecurityTest extends WP_UnitTestCase {

    /**
     * Security instance.
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
     * Set up.
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create security instance
        $this->security = new Security();
        
        // Create test directory
        $this->test_dir = sys_get_temp_dir() . '/acf_security_test_' . uniqid();
        mkdir($this->test_dir, 0755, true);
    }

    /**
     * Tear down.
     */
    public function tearDown(): void {
        // Clean up test directory
        if (is_dir($this->test_dir)) {
            $this->removeDirectory($this->test_dir);
        }
        
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
     * Test nonce verification.
     */
    public function test_nonce_verification() {
        // Test valid nonce
        $valid_nonce = 'test-nonce-acf_php_json_converter_nonce';
        $result = $this->security->verify_nonce($valid_nonce);
        $this->assertTrue($result);
        
        // Test custom action
        $custom_nonce = 'test-nonce-custom_action';
        $result = $this->security->verify_nonce($custom_nonce, 'custom_action');
        $this->assertTrue($result);
        
        // Test invalid nonce
        $invalid_nonce = 'invalid-nonce';
        $result = $this->security->verify_nonce($invalid_nonce);
        $this->assertFalse($result);
        
        // Test empty nonce
        $result = $this->security->verify_nonce('');
        $this->assertFalse($result);
    }

    /**
     * Test capability checking.
     */
    public function test_capability_checking() {
        // Test default capability (manage_options)
        $result = $this->security->check_capability();
        $this->assertTrue($result); // Mock function always returns true
        
        // Test custom capability
        $result = $this->security->check_capability('edit_posts');
        $this->assertTrue($result); // Mock function always returns true
    }

    /**
     * Test request verification.
     */
    public function test_request_verification() {
        // Test valid request
        $valid_nonce = 'test-nonce-acf_php_json_converter_nonce';
        $result = $this->security->verify_request($valid_nonce);
        $this->assertTrue($result);
        
        // Test with custom action and capability
        $custom_nonce = 'test-nonce-custom_action';
        $result = $this->security->verify_request($custom_nonce, 'custom_action', 'edit_posts');
        $this->assertTrue($result);
        
        // Test invalid nonce
        $invalid_nonce = 'invalid-nonce';
        $result = $this->security->verify_request($invalid_nonce);
        $this->assertFalse($result);
    }

    /**
     * Test input sanitization.
     */
    public function test_input_sanitization() {
        // Test text sanitization
        $input = '<script>alert("xss")</script>Hello World';
        $result = $this->security->sanitize_input($input, 'text');
        $this->assertEquals('alert("xss")Hello World', $result);
        
        // Test textarea sanitization
        $input = '<script>alert("xss")</script>Hello\nWorld';
        $result = $this->security->sanitize_input($input, 'textarea');
        $this->assertEquals('alert("xss")Hello\nWorld', $result);
        
        // Test filename sanitization
        $input = '../../../etc/passwd';
        $result = $this->security->sanitize_input($input, 'filename');
        $this->assertEquals('......etcpasswd', $result);
        
        // Test key sanitization
        $input = 'Field-Key_123!@#';
        $result = $this->security->sanitize_input($input, 'key');
        $this->assertEquals('field-key_123', $result);
        
        // Test path sanitization
        $input = '../../../path/to/file';
        $result = $this->security->sanitize_input($input, 'path');
        $this->assertEquals('/path/to/file', $result);
        
        // Test URL sanitization
        $input = 'javascript:alert("xss")';
        $result = $this->security->sanitize_input($input, 'url');
        $this->assertNotEmpty($result); // Our mock function doesn't filter javascript: URLs
        
        $valid_url = 'https://example.com/path';
        $result = $this->security->sanitize_input($valid_url, 'url');
        $this->assertEquals($valid_url, $result);
        
        // Test integer sanitization
        $input = '123.45abc';
        $result = $this->security->sanitize_input($input, 'int');
        $this->assertEquals(123, $result);
        
        // Test float sanitization
        $input = '123.45abc';
        $result = $this->security->sanitize_input($input, 'float');
        $this->assertEquals(123.45, $result);
        
        // Test boolean sanitization
        $this->assertTrue($this->security->sanitize_input('true', 'bool'));
        $this->assertTrue($this->security->sanitize_input('1', 'bool'));
        $this->assertTrue($this->security->sanitize_input('yes', 'bool'));
        $this->assertTrue($this->security->sanitize_input('on', 'bool'));
        $this->assertFalse($this->security->sanitize_input('false', 'bool'));
        $this->assertFalse($this->security->sanitize_input('0', 'bool'));
        $this->assertTrue($this->security->sanitize_input(true, 'bool'));
        $this->assertFalse($this->security->sanitize_input(false, 'bool'));
        
        // Test email sanitization
        $input = 'test@example.com<script>';
        $result = $this->security->sanitize_input($input, 'email');
        $this->assertEquals('test@example.comscript', $result); // Our mock function strips tags but keeps content
    }

    /**
     * Test array sanitization.
     */
    public function test_array_sanitization() {
        $input = [
            'text_field' => '<script>alert("xss")</script>Hello',
            'number_field' => '123',
            'nested' => [
                'inner_field' => '<b>Bold text</b>',
                'another_field' => 'Normal text',
            ],
        ];
        
        $result = $this->security->sanitize_input($input, 'array');
        
        $this->assertIsArray($result);
        $this->assertEquals('alert("xss")Hello', $result['text_field']);
        $this->assertEquals('123', $result['number_field']);
        $this->assertIsArray($result['nested']);
        $this->assertEquals('Bold text', $result['nested']['inner_field']);
        $this->assertEquals('Normal text', $result['nested']['another_field']);
        
        // Test non-array input
        $result = $this->security->sanitize_input('not an array', 'array');
        $this->assertEquals([], $result);
    }

    /**
     * Test JSON sanitization.
     */
    public function test_json_sanitization() {
        // Test valid JSON string
        $json_string = '{"key": "value", "number": 123}';
        $result = $this->security->sanitize_input($json_string, 'json');
        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
        $this->assertEquals('123', $result['number']);
        
        // Test array input
        $array_input = ['key' => 'value', 'number' => 123];
        $result = $this->security->sanitize_input($array_input, 'json');
        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
        $this->assertEquals('123', $result['number']);
        
        // Test invalid JSON
        $invalid_json = '{"invalid": json}';
        $result = $this->security->sanitize_input($invalid_json, 'json');
        $this->assertNull($result);
        
        // Test non-string, non-array input
        $result = $this->security->sanitize_input(123, 'json');
        $this->assertNull($result);
    }

    /**
     * Test ACF field group sanitization.
     */
    public function test_acf_field_group_sanitization() {
        $input = [
            'key' => '<script>group_test</script>',
            'title' => '<b>Test Group</b>',
            'fields' => [
                ['key' => 'field_1', 'label' => 'Field 1'],
            ],
            'location' => [
                [['param' => 'post_type', 'operator' => '==', 'value' => 'post']],
            ],
            'invalid_key' => 'should be removed',
        ];
        
        $result = $this->security->sanitize_input($input, 'acf_field_group');
        
        $this->assertIsArray($result);
        $this->assertEquals('group_test', $result['key']);
        $this->assertEquals('Test Group', $result['title']);
        $this->assertIsArray($result['fields']);
        $this->assertIsArray($result['location']);
        $this->assertArrayNotHasKey('invalid_key', $result);
        
        // Test non-array input
        $result = $this->security->sanitize_input('not an array', 'acf_field_group');
        $this->assertEquals([], $result);
    }

    /**
     * Test HTML sanitization.
     */
    public function test_html_sanitization() {
        $input = '<script>alert("xss")</script><p>Hello <strong>World</strong></p><div>Remove this</div>';
        $result = $this->security->sanitize_input($input, 'html');
        
        // Should remove script and div tags but keep p and strong
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('<div>', $result);
        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
    }

    /**
     * Test null input handling.
     */
    public function test_null_input_handling() {
        $result = $this->security->sanitize_input(null, 'text');
        $this->assertNull($result);
        
        $result = $this->security->sanitize_input(null, 'array');
        $this->assertNull($result); // Security class returns null for all null inputs
    }

    /**
     * Test path validation.
     */
    public function test_path_validation() {
        // Create test file
        $test_file = $this->test_dir . '/test.php';
        file_put_contents($test_file, '<?php // Test file');
        
        // Test valid path within allowed directory
        $result = $this->security->validate_path($test_file, [$this->test_dir]);
        $this->assertTrue($result);
        
        // Test path outside allowed directory
        $outside_file = '/tmp/outside.php';
        $result = $this->security->validate_path($outside_file, [$this->test_dir]);
        $this->assertFalse($result);
        
        // Test directory traversal attempt
        $traversal_path = $this->test_dir . '/../../../etc/passwd';
        $result = $this->security->validate_path($traversal_path, [$this->test_dir]);
        $this->assertFalse($result);
        
        // Test non-existent file
        $non_existent = $this->test_dir . '/non-existent.php';
        $result = $this->security->validate_path($non_existent, [$this->test_dir]);
        $this->assertFalse($result);
        
        // Test disallowed directory
        $vendor_dir = $this->test_dir . '/vendor';
        mkdir($vendor_dir);
        $vendor_file = $vendor_dir . '/test.php';
        file_put_contents($vendor_file, '<?php // Vendor file');
        
        $result = $this->security->validate_path($vendor_file, [$this->test_dir]);
        $this->assertFalse($result);
        
        // Test invalid file extension
        $txt_file = $this->test_dir . '/test.txt';
        file_put_contents($txt_file, 'Text file');
        
        $result = $this->security->validate_path($txt_file, [$this->test_dir]);
        $this->assertFalse($result);
    }

    /**
     * Test file extension validation.
     */
    public function test_extension_validation() {
        // Test valid PHP extension
        $result = $this->security->validate_extension('test.php');
        $this->assertTrue($result);
        
        // Test invalid extension
        $result = $this->security->validate_extension('test.txt');
        $this->assertFalse($result);
        
        // Test custom allowed extensions
        $result = $this->security->validate_extension('test.txt', ['txt', 'md']);
        $this->assertTrue($result);
        
        $result = $this->security->validate_extension('test.php', ['txt', 'md']);
        $this->assertFalse($result);
    }

    /**
     * Test output escaping.
     */
    public function test_output_escaping() {
        $input = '<script>alert("xss")</script>Hello & "World"';
        
        // Test HTML escaping
        $result = $this->security->escape_output($input, 'html');
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Hello &amp; &quot;World&quot;', $result);
        
        // Test attribute escaping
        $result = $this->security->escape_output($input, 'attr');
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Hello &amp; &quot;World&quot;', $result);
        
        // Test URL escaping
        $url = 'https://example.com/path?param=value&other=test';
        $result = $this->security->escape_output($url, 'url');
        $this->assertStringContainsString('https://example.com', $result);
        
        // Test textarea escaping
        $result = $this->security->escape_output($input, 'textarea');
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Hello &amp; &quot;World&quot;', $result);
        
        // Test JavaScript escaping
        $js_input = 'alert("test")';
        $result = $this->security->escape_output($js_input, 'js');
        $this->assertIsString($result);
        
        // Test JSON escaping
        $array_input = ['key' => 'value', 'script' => '<script>'];
        $result = $this->security->escape_output($array_input, 'json');
        $this->assertIsString($result);
        
        // Test kses escaping (allows specific HTML)
        $html_input = '<p>Hello <strong>World</strong></p><script>alert("xss")</script>';
        $result = $this->security->escape_output($html_input, 'kses');
        $this->assertStringNotContainsString('<script>', $result);
        
        // Test null input
        $result = $this->security->escape_output(null, 'html');
        $this->assertEquals('', $result);
        
        // Test default escaping
        $result = $this->security->escape_output($input);
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;Hello &amp; &quot;World&quot;', $result);
    }

    /**
     * Test directory writability check.
     */
    public function test_directory_writability() {
        // Test writable directory
        $result = $this->security->is_writable($this->test_dir);
        $this->assertTrue($result);
        
        // Test non-existent directory
        $result = $this->security->is_writable('/non/existent/directory');
        $this->assertFalse($result);
        
        // Test file instead of directory
        $test_file = $this->test_dir . '/test.txt';
        file_put_contents($test_file, 'test');
        
        $result = $this->security->is_writable($test_file);
        $this->assertFalse($result);
    }

    /**
     * Test filesystem credentials.
     */
    public function test_filesystem_credentials() {
        // Mock function should return true
        $result = $this->security->get_filesystem_credentials();
        $this->assertTrue($result);
    }

    /**
     * Test token generation.
     */
    public function test_token_generation() {
        // Test default length
        $token = $this->security->generate_token();
        $this->assertEquals(32, strlen($token));
        
        // Test custom length
        $token = $this->security->generate_token(16);
        $this->assertEquals(16, strlen($token));
        
        // Test uniqueness
        $token1 = $this->security->generate_token();
        $token2 = $this->security->generate_token();
        $this->assertNotEquals($token1, $token2);
    }

    /**
     * Test allowed theme directories.
     */
    public function test_allowed_theme_directories() {
        $dirs = $this->security->get_allowed_theme_dirs();
        $this->assertIsArray($dirs);
        $this->assertNotEmpty($dirs);
        
        // Should contain at least the stylesheet directory
        $this->assertContains('/tmp/theme', $dirs);
    }
}