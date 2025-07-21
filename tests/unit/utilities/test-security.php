<?php
/**
 * Tests for the Security utility class.
 *
 * @package ACF_PHP_JSON_Converter
 */

use ACF_PHP_JSON_Converter\Utilities\Security;

/**
 * Security test case.
 */
class SecurityTest extends WP_UnitTestCase {

    /**
     * Security instance.
     *
     * @var Security
     */
    protected $security;

    /**
     * Set up.
     */
    public function setUp() {
        parent::setUp();
        $this->security = new Security();
    }

    /**
     * Test sanitize_input method with text input.
     */
    public function test_sanitize_input_text() {
        $input = 'Test <script>alert("XSS")</script> string';
        $expected = 'Test alert("XSS") string';
        $this->assertEquals($expected, $this->security->sanitize_input($input, 'text'));
    }

    /**
     * Test sanitize_input method with textarea input.
     */
    public function test_sanitize_input_textarea() {
        $input = "Line 1\nLine 2 <script>alert('XSS')</script>";
        $expected = "Line 1\nLine 2 alert('XSS')";
        $this->assertEquals($expected, $this->security->sanitize_input($input, 'textarea'));
    }

    /**
     * Test sanitize_input method with filename input.
     */
    public function test_sanitize_input_filename() {
        $input = 'file-name../with../traversal.php';
        $expected = 'file-namewithtraversal.php';
        $this->assertEquals($expected, $this->security->sanitize_input($input, 'filename'));
    }

    /**
     * Test sanitize_input method with path input.
     */
    public function test_sanitize_input_path() {
        $input = '/path/to/../../../etc/passwd';
        $expected = '/path/to/etc/passwd';
        $this->assertEquals($expected, $this->security->sanitize_input($input, 'path'));
    }

    /**
     * Test sanitize_input method with URL input.
     */
    public function test_sanitize_input_url() {
        $input = 'http://example.com/?param=<script>alert("XSS")</script>';
        $expected = 'http://example.com/?param=alert%28%22XSS%22%29';
        $this->assertEquals($expected, $this->security->sanitize_input($input, 'url'));
    }

    /**
     * Test sanitize_input method with integer input.
     */
    public function test_sanitize_input_int() {
        $input = '123abc';
        $expected = 123;
        $this->assertEquals($expected, $this->security->sanitize_input($input, 'int'));
    }

    /**
     * Test sanitize_input method with boolean input.
     */
    public function test_sanitize_input_bool() {
        $this->assertTrue($this->security->sanitize_input('true', 'bool'));
        $this->assertTrue($this->security->sanitize_input('1', 'bool'));
        $this->assertTrue($this->security->sanitize_input('yes', 'bool'));
        $this->assertTrue($this->security->sanitize_input('on', 'bool'));
        $this->assertTrue($this->security->sanitize_input(true, 'bool'));
        $this->assertTrue($this->security->sanitize_input(1, 'bool'));
        
        $this->assertFalse($this->security->sanitize_input('false', 'bool'));
        $this->assertFalse($this->security->sanitize_input('0', 'bool'));
        $this->assertFalse($this->security->sanitize_input('no', 'bool'));
        $this->assertFalse($this->security->sanitize_input('off', 'bool'));
        $this->assertFalse($this->security->sanitize_input(false, 'bool'));
        $this->assertFalse($this->security->sanitize_input(0, 'bool'));
    }

    /**
     * Test sanitize_input method with array input.
     */
    public function test_sanitize_input_array() {
        $input = array(
            'key1' => 'value1 <script>alert("XSS")</script>',
            'key2' => array(
                'nested_key' => 'nested_value <script>alert("XSS")</script>',
            ),
        );
        
        $expected = array(
            'key1' => 'value1 alert("XSS")',
            'key2' => array(
                'nested_key' => 'nested_value alert("XSS")',
            ),
        );
        
        $this->assertEquals($expected, $this->security->sanitize_input($input, 'array'));
    }

    /**
     * Test sanitize_input method with JSON input.
     */
    public function test_sanitize_input_json() {
        $input = '{"key1":"value1 <script>alert(\\"XSS\\")</script>","key2":{"nested_key":"nested_value <script>alert(\\"XSS\\")</script>"}}';
        
        $expected = array(
            'key1' => 'value1 alert("XSS")',
            'key2' => array(
                'nested_key' => 'nested_value alert("XSS")',
            ),
        );
        
        $this->assertEquals($expected, $this->security->sanitize_input($input, 'json'));
    }

    /**
     * Test sanitize_input method with HTML input.
     */
    public function test_sanitize_input_html() {
        $input = '<p>Paragraph <strong>bold</strong> <script>alert("XSS")</script></p>';
        $expected = '<p>Paragraph <strong>bold</strong> </p>';
        $this->assertEquals($expected, $this->security->sanitize_input($input, 'html'));
    }

    /**
     * Test escape_output method with HTML output.
     */
    public function test_escape_output_html() {
        $output = '<p>Paragraph <strong>bold</strong></p>';
        $expected = '&lt;p&gt;Paragraph &lt;strong&gt;bold&lt;/strong&gt;&lt;/p&gt;';
        $this->assertEquals($expected, $this->security->escape_output($output, 'html'));
    }

    /**
     * Test escape_output method with attribute output.
     */
    public function test_escape_output_attr() {
        $output = 'Attribute "value" with <script>';
        $expected = 'Attribute &quot;value&quot; with &lt;script&gt;';
        $this->assertEquals($expected, $this->security->escape_output($output, 'attr'));
    }

    /**
     * Test escape_output method with URL output.
     */
    public function test_escape_output_url() {
        $output = 'http://example.com/?param=<script>alert("XSS")</script>';
        $expected = 'http://example.com/?param=alert%28%22XSS%22%29';
        $this->assertEquals($expected, $this->security->escape_output($output, 'url'));
    }

    /**
     * Test escape_output method with JSON output.
     */
    public function test_escape_output_json() {
        $output = array(
            'key' => 'value with "quotes" and <script>',
        );
        $expected = esc_attr(wp_json_encode($output));
        $this->assertEquals($expected, $this->security->escape_output($output, 'json'));
    }

    /**
     * Test validate_path method.
     */
    public function test_validate_path() {
        // Create a test file in the theme directory
        $theme_dir = get_stylesheet_directory();
        $test_file = $theme_dir . '/test-security.php';
        file_put_contents($test_file, '<?php // Test file');
        
        // Valid path
        $this->assertTrue($this->security->validate_path($test_file));
        
        // Invalid path (outside theme directory)
        $this->assertFalse($this->security->validate_path(ABSPATH . 'wp-config.php'));
        
        // Invalid path (disallowed directory)
        $this->assertFalse($this->security->validate_path($theme_dir . '/vendor/test.php'));
        
        // Clean up
        unlink($test_file);
    }

    /**
     * Test validate_extension method.
     */
    public function test_validate_extension() {
        // Valid extension
        $this->assertTrue($this->security->validate_extension('file.php'));
        
        // Invalid extension
        $this->assertFalse($this->security->validate_extension('file.js'));
        
        // Custom extensions
        $this->assertTrue($this->security->validate_extension('file.js', array('js')));
    }

    /**
     * Test generate_token method.
     */
    public function test_generate_token() {
        $token = $this->security->generate_token();
        $this->assertEquals(32, strlen($token));
        
        $token = $this->security->generate_token(16);
        $this->assertEquals(16, strlen($token));
    }
}