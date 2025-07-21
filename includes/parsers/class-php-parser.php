<?php
/**
 * PHP Parser.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Parsers
 */

namespace ACF_PHP_JSON_Converter\Parsers;

use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Security;

/**
 * PHP Parser Class.
 *
 * Parses PHP files to extract ACF field group definitions.
 */
class PHP_Parser {

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Logger    $logger    Logger instance.
     */
    protected $logger;

    /**
     * Security instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Security    $security    Security instance.
     */
    protected $security;

    /**
     * Parsing errors.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $errors    Parsing errors.
     */
    protected $errors = array();

    /**
     * Function name to search for.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $function_name    Function name to search for.
     */
    protected $function_name = 'acf_add_local_field_group';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    Logger       $logger        Logger instance.
     * @param    Security     $security      Security instance.
     */
    public function __construct(Logger $logger, Security $security) {
        $this->logger = $logger;
        $this->security = $security;
    }

    /**
     * Parse a PHP file to extract ACF field groups.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to PHP file.
     * @return   array     Extracted field groups.
     */
    public function parse_file($file_path) {
        // Reset errors
        $this->errors = array();

        // Validate file path
        if (!$this->security->validate_path($file_path)) {
            $this->add_error('Invalid file path: ' . $file_path);
            return array();
        }

        // Check if file exists
        if (!file_exists($file_path)) {
            $this->add_error('File does not exist: ' . $file_path);
            return array();
        }

        // Validate PHP syntax
        if (!$this->validate_php_syntax($file_path)) {
            return array();
        }

        // Get file content
        $content = file_get_contents($file_path);
        if ($content === false) {
            $this->add_error('Failed to read file: ' . $file_path);
            return array();
        }

        // Extract function calls
        $field_groups = $this->extract_function_calls($content, $this->function_name);

        // Add file path to field groups
        foreach ($field_groups as &$field_group) {
            $field_group['_acf_php_json_converter'] = array(
                'source_file' => $file_path,
                'modified_date' => filemtime($file_path),
            );
        }

        return $field_groups;
    }

    /**
     * Extract function calls from PHP content.
     *
     * @since    1.0.0
     * @param    string    $content        PHP content.
     * @param    string    $function_name  Function name to extract.
     * @return   array     Extracted function call arguments.
     */
    public function extract_function_calls($content, $function_name) {
        $results = array();
        $tokens = token_get_all($content);
        $count = count($tokens);
        
        // Find function calls
        for ($i = 0; $i < $count; $i++) {
            // Look for the function name
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING && $tokens[$i][1] === $function_name) {
                // Check if it's a function call (next token should be an opening parenthesis)
                if ($i + 1 < $count && $tokens[$i + 1] === '(') {
                    // Extract the function arguments
                    $args = $this->extract_function_arguments($tokens, $i + 1);
                    if ($args) {
                        try {
                            // Evaluate the arguments to get the field group array
                            $field_group = $this->safely_evaluate_array($args);
                            if ($field_group && is_array($field_group)) {
                                // Validate field group structure
                                if ($this->validate_field_structure($field_group)) {
                                    $results[] = $field_group;
                                }
                            }
                        } catch (\Exception $e) {
                            $this->add_error('Failed to evaluate field group: ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Extract function arguments from tokens.
     *
     * @since    1.0.0
     * @param    array     $tokens    Tokens.
     * @param    int       $start     Start position.
     * @return   string    Function arguments as string.
     */
    protected function extract_function_arguments($tokens, $start) {
        $count = count($tokens);
        $level = 0;
        $args = '';
        
        for ($i = $start; $i < $count; $i++) {
            $token = $tokens[$i];
            
            // Track parenthesis level
            if ($token === '(') {
                $level++;
            } elseif ($token === ')') {
                $level--;
                if ($level === 0) {
                    break; // End of function arguments
                }
            }
            
            // Add token to arguments
            if (is_array($token)) {
                $args .= $token[1];
            } else {
                $args .= $token;
            }
        }
        
        // Remove the outer parentheses
        return substr($args, 1, -1);
    }

    /**
     * Safely evaluate an array string without executing code.
     *
     * @since    1.0.0
     * @param    string    $array_string    Array string.
     * @return   array     Evaluated array.
     */
    protected function safely_evaluate_array($array_string) {
        // Replace PHP array() syntax with JSON-compatible syntax
        $json_compatible = $this->convert_php_array_to_json_compatible($array_string);
        
        // Convert to JSON
        $json = $this->convert_to_json($json_compatible);
        
        // Decode JSON
        $result = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->add_error('JSON decode error: ' . json_last_error_msg());
            return null;
        }
        
        return $result;
    }

    /**
     * Convert PHP array syntax to JSON-compatible syntax.
     *
     * @since    1.0.0
     * @param    string    $php_array    PHP array string.
     * @return   string    JSON-compatible string.
     */
    protected function convert_php_array_to_json_compatible($php_array) {
        // Replace array() with []
        $result = preg_replace('/array\s*\(/i', '[', $php_array);
        $result = preg_replace('/\)(?![\w\s\[\'":,])/', ']', $result);
        
        // Replace => with :
        $result = preg_replace('/=>/i', ':', $result);
        
        // Handle string keys
        $result = preg_replace_callback('/\'([^\']+)\'\s*:/', function($matches) {
            return '"' . str_replace('"', '\\"', $matches[1]) . '":';
        }, $result);
        
        // Handle string values
        $result = preg_replace_callback('/:\s*\'([^\']*)\'/', function($matches) {
            return ':"' . str_replace('"', '\\"', $matches[1]) . '"';
        }, $result);
        
        // Handle unquoted keys
        $result = preg_replace_callback('/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\s*:/', function($matches) {
            return '"' . $matches[1] . '":';
        }, $result);
        
        // Handle true/false/null
        $result = preg_replace('/:\s*true/i', ':true', $result);
        $result = preg_replace('/:\s*false/i', ':false', $result);
        $result = preg_replace('/:\s*null/i', ':null', $result);
        
        return $result;
    }

    /**
     * Convert to JSON.
     *
     * @since    1.0.0
     * @param    string    $json_compatible    JSON-compatible string.
     * @return   string    JSON string.
     */
    protected function convert_to_json($json_compatible) {
        // Add quotes to unquoted string values
        $json = preg_replace_callback('/:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', function($matches) {
            // Skip true/false/null
            if (in_array(strtolower($matches[1]), array('true', 'false', 'null'))) {
                return ':' . $matches[1];
            }
            return ':"' . $matches[1] . '"';
        }, $json_compatible);
        
        return $json;
    }

    /**
     * Validate PHP syntax.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to PHP file.
     * @return   bool      True if valid, false otherwise.
     */
    public function validate_php_syntax($file_path) {
        // Use PHP's built-in syntax check
        $output = array();
        $return_var = 0;
        
        exec('php -l ' . escapeshellarg($file_path), $output, $return_var);
        
        if ($return_var !== 0) {
            $error_message = implode("\n", $output);
            $this->add_error('PHP syntax error: ' . $error_message);
            return false;
        }
        
        return true;
    }

    /**
     * Validate field group structure.
     *
     * @since    1.0.0
     * @param    array     $field_group    Field group.
     * @return   bool      True if valid, false otherwise.
     */
    public function validate_field_structure($field_group) {
        // Check required fields
        $required_fields = array('key', 'title', 'fields');
        
        foreach ($required_fields as $field) {
            if (!isset($field_group[$field])) {
                $this->add_error('Missing required field: ' . $field);
                return false;
            }
        }
        
        // Validate key format
        if (!preg_match('/^group_[a-zA-Z0-9]+$/', $field_group['key'])) {
            $this->add_error('Invalid field group key format: ' . $field_group['key']);
            // Don't return false here, just warn about the format
        }
        
        // Validate fields array
        if (!is_array($field_group['fields'])) {
            $this->add_error('Fields must be an array');
            return false;
        }
        
        // Validate each field
        foreach ($field_group['fields'] as $field) {
            if (!$this->validate_field($field)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Validate field structure.
     *
     * @since    1.0.0
     * @param    array     $field    Field.
     * @return   bool      True if valid, false otherwise.
     */
    protected function validate_field($field) {
        // Check required fields
        $required_fields = array('key', 'label', 'name', 'type');
        
        foreach ($required_fields as $required) {
            if (!isset($field[$required])) {
                $this->add_error('Missing required field property: ' . $required);
                return false;
            }
        }
        
        // Validate sub-fields for certain field types
        $types_with_sub_fields = array('repeater', 'flexible_content', 'group');
        
        if (in_array($field['type'], $types_with_sub_fields)) {
            if ($field['type'] === 'flexible_content') {
                // Validate layouts
                if (!isset($field['layouts']) || !is_array($field['layouts'])) {
                    $this->add_error('Flexible content field must have layouts');
                    return false;
                }
                
                foreach ($field['layouts'] as $layout) {
                    if (!isset($layout['sub_fields']) || !is_array($layout['sub_fields'])) {
                        $this->add_error('Flexible content layout must have sub_fields');
                        return false;
                    }
                    
                    foreach ($layout['sub_fields'] as $sub_field) {
                        if (!$this->validate_field($sub_field)) {
                            return false;
                        }
                    }
                }
            } else {
                // Validate sub_fields for repeater and group
                if (!isset($field['sub_fields']) || !is_array($field['sub_fields'])) {
                    $this->add_error($field['type'] . ' field must have sub_fields');
                    return false;
                }
                
                foreach ($field['sub_fields'] as $sub_field) {
                    if (!$this->validate_field($sub_field)) {
                        return false;
                    }
                }
            }
        }
        
        return true;
    }

    /**
     * Add parsing error.
     *
     * @since    1.0.0
     * @param    string    $message    Error message.
     */
    protected function add_error($message) {
        $this->errors[] = $message;
        $this->logger->error('PHP Parser: ' . $message);
    }

    /**
     * Get parsing errors.
     *
     * @since    1.0.0
     * @return   array    Parsing errors.
     */
    public function get_parsing_errors() {
        return $this->errors;
    }

    /**
     * Set function name to search for.
     *
     * @since    1.0.0
     * @param    string    $function_name    Function name.
     */
    public function set_function_name($function_name) {
        $this->function_name = $function_name;
    }

    /**
     * Get function name being searched for.
     *
     * @since    1.0.0
     * @return   string    Function name.
     */
    public function get_function_name() {
        return $this->function_name;
    }
}