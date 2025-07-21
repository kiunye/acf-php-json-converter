<?php
/**
 * JSON to PHP Converter.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Converters
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Converters
 */

namespace ACF_PHP_JSON_Converter\Converters;

use ACF_PHP_JSON_Converter\Utilities\Logger;

/**
 * JSON to PHP Converter Class.
 *
 * Converts ACF JSON field groups to PHP code with acf_add_local_field_group() calls.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Converters
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Converters
 */
class JSON_To_PHP_Converter {

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Logger    $logger    Logger instance.
     */
    protected $logger;

    /**
     * Conversion errors.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $errors    Conversion errors.
     */
    protected $errors = [];

    /**
     * Conversion warnings.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $warnings    Conversion warnings.
     */
    protected $warnings = [];

    /**
     * Indentation string.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $indent    Indentation string.
     */
    protected $indent = '    '; // 4 spaces

    /**
     * Current indentation level.
     *
     * @since    1.0.0
     * @access   protected
     * @var      int    $indent_level    Current indentation level.
     */
    protected $indent_level = 0;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    Logger    $logger    Logger instance.
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Convert JSON field group to PHP code.
     *
     * @since    1.0.0
     * @param    array    $json_data    JSON field group data.
     * @return   array    Conversion result with PHP code.
     */
    public function convert($json_data) {
        // Reset errors and warnings
        $this->errors = [];
        $this->warnings = [];
        $this->indent_level = 0;

        // Validate input
        if (!$this->validate_input($json_data)) {
            return $this->get_error_response();
        }

        try {
            // Generate PHP code
            $php_code = $this->generate_php_code($json_data);

            // Check for warnings
            if (!empty($this->warnings)) {
                return [
                    'status' => 'warning',
                    'data' => $php_code,
                    'warnings' => $this->warnings,
                ];
            }

            return [
                'status' => 'success',
                'data' => $php_code,
            ];
        } catch (\Exception $e) {
            $this->add_error('Conversion failed: ' . $e->getMessage());
            $this->logger->log_exception($e);
            return $this->get_error_response();
        }
    }

    /**
     * Generate PHP code from JSON field group.
     *
     * @since    1.0.0
     * @param    array    $field_group    Field group data.
     * @return   string   Generated PHP code.
     */
    protected function generate_php_code($field_group) {
        // Start with PHP opening tag and comments
        $php_code = "<?php\n";
        $php_code .= "/**\n";
        $php_code .= " * ACF Field Group: {$field_group['title']}\n";
        $php_code .= " *\n";
        $php_code .= " * @package     Your_Theme\n";
        $php_code .= " * @subpackage  ACF_Field_Groups\n";
        
        // Add modified date if available
        if (isset($field_group['modified'])) {
            $date = date('Y-m-d H:i:s', $field_group['modified']);
            $php_code .= " * @modified    {$date}\n";
        }
        
        $php_code .= " */\n\n";
        
        // Add if statement to check if function exists
        $php_code .= "if (function_exists('acf_add_local_field_group')) {\n";
        
        // Start the acf_add_local_field_group function call
        $php_code .= $this->indent . "acf_add_local_field_group(";
        
        // Convert field group to PHP array syntax
        $this->indent_level = 1;
        $php_code .= $this->array_to_php($field_group);
        
        // Close the function call and if statement
        $php_code .= ");\n";
        $php_code .= "}\n";
        
        return $php_code;
    }

    /**
     * Convert array to PHP array syntax.
     *
     * @since    1.0.0
     * @param    mixed    $data    Data to convert.
     * @return   string   PHP array syntax.
     */
    protected function array_to_php($data) {
        if (!is_array($data)) {
            return $this->scalar_to_php($data);
        }
        
        // Check if array is associative or sequential
        $is_associative = $this->is_associative_array($data);
        
        // Start array
        $php = "array(\n";
        $this->indent_level++;
        
        $items = [];
        foreach ($data as $key => $value) {
            $current_indent = str_repeat($this->indent, $this->indent_level);
            
            if ($is_associative) {
                // Format key based on type
                $formatted_key = is_string($key) ? "'{$key}'" : $key;
                $items[] = "{$current_indent}{$formatted_key} => " . $this->array_to_php($value);
            } else {
                $items[] = "{$current_indent}" . $this->array_to_php($value);
            }
        }
        
        $this->indent_level--;
        $closing_indent = str_repeat($this->indent, $this->indent_level);
        
        // Join items and close array
        return $php . implode(",\n", $items) . "\n{$closing_indent})";
    }

    /**
     * Convert scalar value to PHP syntax.
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to convert.
     * @return   string   PHP syntax.
     */
    protected function scalar_to_php($value) {
        if (is_string($value)) {
            // Escape single quotes and wrap in single quotes
            $escaped = str_replace("'", "\\'", $value);
            return "'{$escaped}'";
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return 'null';
        } else {
            // Numbers and other types
            return (string) $value;
        }
    }

    /**
     * Check if array is associative.
     *
     * @since    1.0.0
     * @param    array    $array    Array to check.
     * @return   bool     True if associative, false if sequential.
     */
    protected function is_associative_array($array) {
        if (!is_array($array)) {
            return false;
        }
        
        // If array is empty, consider it associative
        if (empty($array)) {
            return true;
        }
        
        // Check if keys are sequential integers starting from 0
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Validate input data.
     *
     * @since    1.0.0
     * @param    array    $data    Input data to validate.
     * @return   bool     True if valid, false otherwise.
     */
    protected function validate_input($data) {
        // Check if data is an array
        if (!is_array($data)) {
            $this->add_error('Input data must be an array');
            return false;
        }

        // Check required fields
        $required_fields = ['key', 'title', 'fields'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                $this->add_error("Missing required field: {$field}");
                return false;
            }
        }

        // Check if fields is an array
        if (!is_array($data['fields'])) {
            $this->add_error('Fields must be an array');
            return false;
        }

        // Validate field group key format
        if (!preg_match('/^group_[a-zA-Z0-9]+$/', $data['key'])) {
            $this->add_warning("Field group key '{$data['key']}' does not follow ACF naming convention (should start with 'group_')");
        }

        return true;
    }

    /**
     * Add conversion error.
     *
     * @since    1.0.0
     * @param    string    $message    Error message.
     */
    protected function add_error($message) {
        $this->errors[] = $message;
        $this->logger->error('JSON to PHP Converter: ' . $message);
    }

    /**
     * Add conversion warning.
     *
     * @since    1.0.0
     * @param    string    $message    Warning message.
     */
    protected function add_warning($message) {
        $this->warnings[] = $message;
        $this->logger->warning('JSON to PHP Converter: ' . $message);
    }

    /**
     * Get error response.
     *
     * @since    1.0.0
     * @return   array    Error response.
     */
    protected function get_error_response() {
        return [
            'status' => 'error',
            'errors' => $this->errors,
        ];
    }

    /**
     * Get conversion errors.
     *
     * @since    1.0.0
     * @return   array    Conversion errors.
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get conversion warnings.
     *
     * @since    1.0.0
     * @return   array    Conversion warnings.
     */
    public function get_warnings() {
        return $this->warnings;
    }
}