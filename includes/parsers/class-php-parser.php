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
     * Function names to search for.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $function_names    Function names to search for.
     */
    protected $function_names = array(
        'acf_add_local_field_group',
        'acf_register_field_group',
        'register_field_group'
    );

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

        // Extract function calls for all supported function names
        $field_groups = array();
        foreach ($this->function_names as $function_name) {
            $function_field_groups = $this->extract_function_calls($content, $function_name);
            $field_groups = array_merge($field_groups, $function_field_groups);
        }

        // Special handling for functions.php files
        if (basename($file_path) === 'functions.php') {
            $field_groups = array_merge($field_groups, $this->parse_functions_php_patterns($content));
        }

        // Add file path to field groups
        foreach ($field_groups as &$field_group) {
            $field_group['_acf_php_json_converter'] = array(
                'source_file' => $file_path,
                'source_type' => basename($file_path) === 'functions.php' ? 'functions_php' : 'theme_file',
                'modified_date' => filemtime($file_path),
            );
        }

        return $field_groups;
    }

    /**
     * Parse functions.php specific patterns.
     *
     * @since    1.0.0
     * @param    string    $content    PHP content.
     * @return   array     Extracted field groups.
     */
    public function parse_functions_php_patterns($content) {
        $field_groups = array();
        
        // Look for field groups defined in action hooks
        $hook_patterns = array(
            'acf/init',
            'acf/include_fields',
            'init',
            'after_setup_theme'
        );
        
        foreach ($hook_patterns as $hook) {
            $field_groups = array_merge($field_groups, $this->extract_from_action_hook($content, $hook));
        }
        
        // Look for field groups in conditional statements
        $field_groups = array_merge($field_groups, $this->extract_from_conditionals($content));
        
        return $field_groups;
    }

    /**
     * Extract field groups from action hooks.
     *
     * @since    1.0.0
     * @param    string    $content    PHP content.
     * @param    string    $hook       Hook name.
     * @return   array     Extracted field groups.
     */
    protected function extract_from_action_hook($content, $hook) {
        $field_groups = array();
        
        // Pattern 1: Named function callbacks
        $pattern = '/add_action\s*\(\s*[\'"]' . preg_quote($hook, '/') . '[\'"]\s*,\s*[\'"]?([^,\)]+)[\'"]?\s*\)/';
        
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $callback_function) {
                // Clean up callback function name
                $callback_function = trim($callback_function, '\'"');
                
                // Skip if it looks like an anonymous function
                if (strpos($callback_function, 'function') !== false) {
                    continue;
                }
                
                // Find the callback function definition
                $function_pattern = '/function\s+' . preg_quote($callback_function, '/') . '\s*\([^)]*\)\s*\{([^}]*(?:\{[^}]*\}[^}]*)*)\}/s';
                
                if (preg_match($function_pattern, $content, $function_matches)) {
                    $function_content = $function_matches[1];
                    
                    // Extract field groups from the function content
                    foreach ($this->function_names as $function_name) {
                        $function_field_groups = $this->extract_function_calls($function_content, $function_name);
                        $field_groups = array_merge($field_groups, $function_field_groups);
                    }
                }
            }
        }
        
        // Pattern 2: Anonymous function callbacks
        $field_groups = array_merge($field_groups, $this->extract_anonymous_function_callbacks($content, $hook));
        
        return $field_groups;
    }

    /**
     * Extract field groups from anonymous function callbacks.
     *
     * @since    1.0.0
     * @param    string    $content    PHP content.
     * @param    string    $hook       Hook name.
     * @return   array     Extracted field groups.
     */
    public function extract_anonymous_function_callbacks($content, $hook) {
        $field_groups = array();
        
        // Find add_action calls with the specific hook
        $pattern = '/add_action\s*\(\s*[\'"]' . preg_quote($hook, '/') . '[\'"]\s*,\s*function\s*\([^)]*\)\s*\{/';
        
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $start_pos = $match[1] + strlen($match[0]) - 1; // Position of opening brace
                $function_content = $this->extract_balanced_braces($content, $start_pos);
                
                if ($function_content !== null) {
                    // Extract field groups from the anonymous function content
                    foreach ($this->function_names as $function_name) {
                        $function_field_groups = $this->extract_function_calls($function_content, $function_name);
                        if (!empty($function_field_groups)) {
                            $this->logger->debug('Found field groups in anonymous function', array(
                                'hook' => $hook,
                                'function_name' => $function_name,
                                'count' => count($function_field_groups)
                            ));
                        }
                        $field_groups = array_merge($field_groups, $function_field_groups);
                    }
                }
            }
        }
        
        return $field_groups;
    }

    /**
     * Extract field groups from conditional statements.
     *
     * @since    1.0.0
     * @param    string    $content    PHP content.
     * @return   array     Extracted field groups.
     */
    protected function extract_from_conditionals($content) {
        $field_groups = array();
        
        // Common conditional patterns in functions.php
        $conditional_patterns = array(
            'if\s*\(\s*function_exists\s*\(\s*[\'"]acf_add_local_field_group[\'"]\s*\)\s*\)',
            'if\s*\(\s*class_exists\s*\(\s*[\'"]ACF[\'"]\s*\)\s*\)',
            'if\s*\(\s*is_plugin_active\s*\([^)]*acf[^)]*\)\s*\)'
        );
        
        foreach ($conditional_patterns as $pattern) {
            if (preg_match_all('/' . $pattern . '\s*\{([^}]*(?:\{[^}]*\}[^}]*)*)\}/s', $content, $matches)) {
                foreach ($matches[1] as $conditional_content) {
                    // Extract field groups from the conditional content
                    foreach ($this->function_names as $function_name) {
                        $conditional_field_groups = $this->extract_function_calls($conditional_content, $function_name);
                        $field_groups = array_merge($field_groups, $conditional_field_groups);
                    }
                }
            }
        }
        
        return $field_groups;
    }

    /**
     * Extract content between balanced braces.
     *
     * @since    1.0.0
     * @param    string    $content    PHP content.
     * @param    int       $start_pos  Starting position of opening brace.
     * @return   string|null    Content between braces or null if not found.
     */
    public function extract_balanced_braces($content, $start_pos) {
        $length = strlen($content);
        $brace_count = 0;
        $start_content = -1;
        
        for ($i = $start_pos; $i < $length; $i++) {
            $char = $content[$i];
            
            if ($char === '{') {
                if ($brace_count === 0) {
                    $start_content = $i + 1; // Start after opening brace
                }
                $brace_count++;
            } elseif ($char === '}') {
                $brace_count--;
                if ($brace_count === 0 && $start_content !== -1) {
                    // Found matching closing brace
                    return substr($content, $start_content, $i - $start_content);
                }
            }
        }
        
        return null; // No matching brace found
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
        $tokens = token_get_all('<?php ' . $content);
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
    public function safely_evaluate_array($array_string) {
        try {
            // Log the original array string for debugging
            $this->logger->debug('Parsing PHP array string', ['original' => substr($array_string, 0, 200) . '...']);
            
            // Replace PHP array() syntax with JSON-compatible syntax
            $json_compatible = $this->convert_php_array_to_json_compatible($array_string);
            
            if (empty($json_compatible)) {
                $this->add_error('Failed to convert PHP array to JSON-compatible format');
                return null;
            }
            
            // Log the JSON-compatible version for debugging
            $this->logger->debug('Converted to JSON-compatible', ['json_compatible' => substr($json_compatible, 0, 200) . '...']);
            
            // Convert to JSON
            $json = $this->convert_to_json($json_compatible);
            
            // Log the final JSON for debugging
            $this->logger->debug('Final JSON string', ['json' => substr($json, 0, 200) . '...']);
            
            // Decode JSON
            $result = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error_msg = json_last_error_msg();
                
                // Log the JSON conversion failure but don't treat it as a fatal error
                $this->logger->debug('JSON decode failed, trying fallback', [
                    'error' => $error_msg,
                    'json_snippet' => substr($json, 0, 200)
                ]);
                
                // Try the fallback approach immediately
                $fallback_result = $this->fallback_array_evaluation($array_string);
                
                if ($fallback_result !== null) {
                    $this->logger->debug('Fallback parsing succeeded');
                    return $fallback_result;
                }
                
                // Only add error if fallback also fails
                $this->add_error('JSON decode error: ' . $error_msg);
                $this->logger->error('Both JSON and fallback parsing failed', [
                    'json_error' => $error_msg,
                    'original_snippet' => substr($array_string, 0, 200)
                ]);
                
                return null;
            }
            
            $this->logger->debug('Successfully parsed PHP array', ['field_count' => is_array($result) ? count($result) : 0]);
            return $result;
            
        } catch (Exception $e) {
            $this->add_error('Exception during array evaluation: ' . $e->getMessage());
            $this->logger->error('Exception in safely_evaluate_array', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Try fallback method on exception
            $this->logger->info('Attempting fallback parsing method after exception');
            $fallback_result = $this->fallback_array_evaluation($array_string);
            
            if ($fallback_result !== null) {
                $this->logger->info('Fallback parsing succeeded after exception');
                return $fallback_result;
            }
            
            return null;
        }
    }

    /**
     * Fallback method for parsing PHP arrays using simpler regex-based approach.
     *
     * @since    1.0.0
     * @param    string    $array_string    Array string.
     * @return   array|null    Evaluated array or null on failure.
     */
    private function fallback_array_evaluation($array_string) {
        try {
            $this->logger->debug('Starting fallback array evaluation');
            
            // Simple regex-based approach for basic array structures
            $simplified = $array_string;
            
            // Step 1: Convert array( to { for associative arrays (PHP arrays are typically associative)
            $simplified = preg_replace('/array\s*\(/i', '{', $simplified);
            
            // Step 2: Convert closing ) to }
            $simplified = preg_replace('/\)/', '}', $simplified);
            
            // Step 3: Convert => to : with proper spacing
            $simplified = preg_replace('/\s*=>\s*/', ': ', $simplified);
            
            // Step 4: Convert single quotes to double quotes for strings
            $simplified = preg_replace_callback("/'([^']*)'/", function($matches) {
                return '"' . str_replace('"', '\\"', $matches[1]) . '"';
            }, $simplified);
            
            // Step 5: Add quotes to unquoted keys
            $simplified = preg_replace_callback('/([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', function($matches) {
                return '"' . $matches[1] . '": ';
            }, $simplified);
            
            // Step 6: Handle boolean and null values
            $simplified = preg_replace('/:\s*true\b/i', ': true', $simplified);
            $simplified = preg_replace('/:\s*false\b/i', ': false', $simplified);
            $simplified = preg_replace('/:\s*null\b/i', ': null', $simplified);
            
            // Step 7: Clean up trailing commas
            $simplified = preg_replace('/,\s*([}\]])/', '$1', $simplified);
            
            // Step 8: Normalize spacing
            $simplified = preg_replace('/:\s+/', ': ', $simplified);
            
            // Step 9: Handle numeric arrays that should be JSON arrays
            // Look for patterns like { { "key": "value" }, { "key": "value" } }
            // and convert the outer braces to brackets for arrays of objects
            $simplified = preg_replace_callback('/\{\s*(\{[^}]+\}(?:\s*,\s*\{[^}]+\})*)\s*\}/', function($matches) {
                // This looks like an array of objects, convert outer braces to brackets
                return '[' . $matches[1] . ']';
            }, $simplified);
            
            $this->logger->debug('Fallback conversion result', ['simplified' => substr($simplified, 0, 200) . '...']);
            
            // Try to decode
            $result = json_decode($simplified, true);
            
            if (json_last_error() === JSON_ERROR_NONE && is_array($result)) {
                $this->logger->debug('Fallback parsing successful', ['method' => 'regex']);
                return $result;
            } else {
                $this->logger->debug('Fallback JSON decode failed', [
                    'error' => json_last_error_msg(),
                    'json_snippet' => substr($simplified, 0, 300)
                ]);
            }
            
            // Final fallback: try to use eval() safely if all else fails
            return $this->safe_eval_fallback($array_string);
            
        } catch (Exception $e) {
            $this->logger->debug('Fallback parsing failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Safe eval fallback for parsing PHP arrays.
     *
     * @since    1.0.0
     * @param    string    $array_string    PHP array string.
     * @return   array|null    Parsed array or null on failure.
     */
    private function safe_eval_fallback($array_string) {
        try {
            // Only use eval as a last resort and with strict validation
            if (!$this->is_safe_for_eval($array_string)) {
                $this->logger->debug('Array string not safe for eval');
                return null;
            }
            
            // Wrap in return statement and evaluate
            $code = 'return ' . trim($array_string, ';') . ';';
            
            // Suppress errors and capture result
            $result = @eval($code);
            
            if (is_array($result)) {
                $this->logger->debug('Safe eval fallback successful');
                return $result;
            }
            
            return null;
            
        } catch (Exception $e) {
            $this->logger->debug('Safe eval fallback failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Check if array string is safe for eval.
     *
     * @since    1.0.0
     * @param    string    $array_string    PHP array string.
     * @return   bool      True if safe, false otherwise.
     */
    private function is_safe_for_eval($array_string) {
        // List of dangerous functions and constructs
        $dangerous_patterns = [
            '/\b(eval|exec|system|shell_exec|passthru|file_get_contents|file_put_contents|fopen|fwrite|include|require|__halt_compiler)\s*\(/i',
            '/\$\w+\s*\(/i', // Variable functions
            '/\$\{/i', // Variable variables
            '/`[^`]*`/i', // Backticks
            '/\bclass\s+/i', // Class definitions
            '/\bfunction\s+/i', // Function definitions
            '/\becho\s+/i', // Echo statements
            '/\bprint\s+/i', // Print statements
            '/\bexit\s*\(/i', // Exit calls
            '/\bdie\s*\(/i', // Die calls
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $array_string)) {
                return false;
            }
        }
        
        // Only allow array syntax, strings, numbers, and basic PHP constructs
        if (!preg_match('/^[\s\w\'"=>\[\](),.\-:\/\\\\_]*$/', $array_string)) {
            return false;
        }
        
        return true;
    }

    /**
     * Convert PHP array syntax to JSON-compatible syntax.
     *
     * @since    1.0.0
     * @param    string    $php_array    PHP array string.
     * @return   string    JSON-compatible string.
     */
    public function convert_php_array_to_json_compatible($php_array) {
        try {
            // Use a simpler, more reliable approach
            $converted = $php_array;
            
            // Step 1: Convert array( to {
            $converted = preg_replace('/\barray\s*\(/', '{', $converted);
            
            // Step 2: Convert => to :
            $converted = preg_replace('/\s*=>\s*/', ':', $converted);
            
            // Step 3: Convert closing ) to }
            $converted = str_replace(')', '}', $converted);
            
            // Step 4: Add quotes around unquoted keys
            $converted = preg_replace('/([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '"$1":', $converted);
            
            // Step 5: Convert single quotes to double quotes for strings
            $converted = preg_replace_callback("/'([^']*)'/", function($matches) {
                // Escape any double quotes in the string
                $escaped = str_replace('"', '\\"', $matches[1]);
                return '"' . $escaped . '"';
            }, $converted);
            
            // Step 6: Handle boolean and null values
            $converted = preg_replace('/:\s*true\b/i', ':true', $converted);
            $converted = preg_replace('/:\s*false\b/i', ':false', $converted);
            $converted = preg_replace('/:\s*null\b/i', ':null', $converted);
            
            // Step 7: Clean up trailing commas
            $converted = preg_replace('/,\s*}/', '}', $converted);
            
            // Step 8: Convert numeric arrays (objects with only numeric keys) to JSON arrays
            // This is a complex step, so we'll handle it in post-processing
            
            // Step 9: Clean up whitespace
            $converted = preg_replace('/\s+/', ' ', $converted);
            $converted = preg_replace('/\s*([{}:,])\s*/', '$1', $converted);
            
            return trim($converted);
            
        } catch (Exception $e) {
            $this->add_error('Error converting PHP array to JSON: ' . $e->getMessage());
            $this->logger->error('PHP to JSON conversion failed', [
                'error' => $e->getMessage(),
                'input' => substr($php_array, 0, 200)
            ]);
            return '';
        }
    }
    
    /**
     * Get next non-whitespace token.
     *
     * @since    1.0.0
     * @param    array    $tokens    Token array.
     * @param    int      $index     Current index.
     * @return   mixed    Next non-whitespace token or null.
     */
    private function get_next_non_whitespace_token($tokens, $index) {
        for ($i = $index + 1; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_WHITESPACE) {
                continue;
            }
            return is_array($token) ? $token[1] : $token;
        }
        return null;
    }



    /**
     * Convert to JSON.
     *
     * @since    1.0.0
     * @param    string    $json_compatible    JSON-compatible string.
     * @return   string    JSON string.
     */
    public function convert_to_json($json_compatible) {
        $json = $json_compatible;
        
        // Convert numeric arrays to JSON arrays
        // Look for patterns like {"0":{"key":"value"},"1":{"key":"value"}}
        // and convert to [{"key":"value"},{"key":"value"}]
        $json = preg_replace_callback('/\{("0":\{[^}]+\}(?:,"[0-9]+":\{[^}]+\})*)\}/', function($matches) {
            // Remove the numeric keys
            $content = preg_replace('/"[0-9]+":/', '', $matches[1]);
            return '[' . $content . ']';
        }, $json);
        
        // Handle nested numeric arrays
        $json = preg_replace_callback('/\{("0":\[[^\]]+\](?:,"[0-9]+":\[[^\]]+\])*)\}/', function($matches) {
            // Remove the numeric keys
            $content = preg_replace('/"[0-9]+":/', '', $matches[1]);
            return '[' . $content . ']';
        }, $json);
        
        // Clean up any remaining issues
        $json = preg_replace('/,\s*}/', '}', $json);
        $json = preg_replace('/,\s*\]/', ']', $json);
        
        // Final cleanup
        $json = preg_replace('/\s+/', ' ', $json);
        $json = preg_replace('/\s*([{}[\]:,])\s*/', '$1', $json);
        
        return trim($json);
    }

    /**
     * Validate PHP syntax.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to PHP file.
     * @return   bool      True if valid, false otherwise.
     */
    public function validate_php_syntax($file_path) {
        // For functions.php files, we'll be more lenient with syntax checking
        // since they often contain complex code that might not validate in isolation
        if (basename($file_path) === 'functions.php') {
            // Just check if the file is readable and contains PHP opening tag
            $content = file_get_contents($file_path);
            if ($content === false) {
                $this->add_error('Cannot read file: ' . $file_path);
                return false;
            }
            
            // Check if it looks like a PHP file
            if (strpos($content, '<?php') === false && strpos($content, '<?') === false) {
                $this->add_error('File does not appear to contain PHP code: ' . $file_path);
                return false;
            }
            
            return true;
        }
        
        // For other files, use PHP's built-in syntax check if available
        if (function_exists('exec')) {
            $output = array();
            $return_var = 0;
            
            exec('php -l ' . escapeshellarg($file_path) . ' 2>&1', $output, $return_var);
            
            if ($return_var !== 0) {
                $error_message = implode("\n", $output);
                $this->add_error('PHP syntax error in ' . basename($file_path) . ': ' . $error_message);
                return false;
            }
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
        
        // Validate key format (be more lenient)
        if (!preg_match('/^group_[a-zA-Z0-9_]+$/', $field_group['key'])) {
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
     * Set function names to search for.
     *
     * @since    1.0.0
     * @param    array    $function_names    Function names.
     */
    public function set_function_names($function_names) {
        if (is_array($function_names)) {
            $this->function_names = $function_names;
        }
    }

    /**
     * Add function name to search for.
     *
     * @since    1.0.0
     * @param    string    $function_name    Function name.
     */
    public function add_function_name($function_name) {
        if (!in_array($function_name, $this->function_names)) {
            $this->function_names[] = $function_name;
        }
    }

    /**
     * Get function names being searched for.
     *
     * @since    1.0.0
     * @return   array    Function names.
     */
    public function get_function_names() {
        return $this->function_names;
    }

    /**
     * Set function name to search for (backward compatibility).
     *
     * @since    1.0.0
     * @param    string    $function_name    Function name.
     */
    public function set_function_name($function_name) {
        $this->function_names = array($function_name);
    }

    /**
     * Get function name being searched for (backward compatibility).
     *
     * @since    1.0.0
     * @return   string    Function name.
     */
    public function get_function_name() {
        return isset($this->function_names[0]) ? $this->function_names[0] : 'acf_add_local_field_group';
    }
}