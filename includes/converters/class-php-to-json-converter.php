<?php
/**
 * PHP to JSON Converter.
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
 * PHP to JSON Converter Class.
 *
 * Converts PHP field group arrays to ACF-compatible JSON format.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Converters
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Converters
 */
class PHP_To_JSON_Converter {

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
     * ACF field types that require special handling.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $special_field_types    Special field types.
     */
    protected $special_field_types = [
        'repeater',
        'flexible_content',
        'group',
        'clone',
        'relationship',
        'post_object',
        'page_link',
        'taxonomy',
        'user',
    ];

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
     * Convert PHP field group to JSON format.
     *
     * @since    1.0.0
     * @param    array    $php_data    PHP field group data.
     * @return   array    Converted JSON data and status.
     */
    public function convert($php_data) {
        // Reset errors and warnings
        $this->errors = [];
        $this->warnings = [];

        // Validate input
        if (!$this->validate_input($php_data)) {
            return $this->get_error_response();
        }

        try {
            // Process the field group
            $json_data = $this->process_field_group($php_data);

            // Check for warnings
            if (!empty($this->warnings)) {
                return [
                    'status' => 'warning',
                    'data' => $json_data,
                    'warnings' => $this->warnings,
                ];
            }

            return [
                'status' => 'success',
                'data' => $json_data,
            ];
        } catch (\Exception $e) {
            $this->add_error('Conversion failed: ' . $e->getMessage());
            $this->logger->log_exception($e);
            return $this->get_error_response();
        }
    }

    /**
     * Process field group for conversion.
     *
     * @since    1.0.0
     * @param    array    $field_group    Field group data.
     * @return   array    Processed field group.
     */
    protected function process_field_group($field_group) {
        // Create a copy to avoid modifying the original
        $processed = $field_group;

        // Extract source file information if available
        $source_info = [];
        if (isset($processed['_acf_php_json_converter'])) {
            $source_info = $processed['_acf_php_json_converter'];
            unset($processed['_acf_php_json_converter']);
        }

        // Process fields
        if (isset($processed['fields']) && is_array($processed['fields'])) {
            $processed['fields'] = $this->process_fields($processed['fields']);
        }

        // Process location rules
        if (isset($processed['location']) && is_array($processed['location'])) {
            $processed['location'] = $this->process_location_rules($processed['location']);
        }

        // Add modified timestamp for ACF
        if (!isset($processed['modified']) && isset($source_info['modified_date'])) {
            $processed['modified'] = $source_info['modified_date'];
        }

        // Ensure key is properly formatted
        if (isset($processed['key']) && !preg_match('/^group_[a-zA-Z0-9]+$/', $processed['key'])) {
            $original_key = $processed['key'];
            $processed['key'] = 'group_' . substr(md5($original_key), 0, 13);
            $this->add_warning("Field group key '{$original_key}' was reformatted to '{$processed['key']}'");
        }

        return $processed;
    }

    /**
     * Process fields recursively.
     *
     * @since    1.0.0
     * @param    array    $fields    Fields to process.
     * @return   array    Processed fields.
     */
    protected function process_fields($fields) {
        $processed_fields = [];

        foreach ($fields as $field) {
            // Process each field
            $processed_field = $this->process_field($field);
            if ($processed_field) {
                $processed_fields[] = $processed_field;
            }
        }

        return $processed_fields;
    }

    /**
     * Process individual field.
     *
     * @since    1.0.0
     * @param    array    $field    Field to process.
     * @return   array    Processed field.
     */
    protected function process_field($field) {
        // Create a copy to avoid modifying the original
        $processed = $field;

        // Ensure key is properly formatted
        if (isset($processed['key']) && !preg_match('/^field_[a-zA-Z0-9]+$/', $processed['key'])) {
            $original_key = $processed['key'];
            $processed['key'] = 'field_' . substr(md5($original_key), 0, 13);
            $this->add_warning("Field key '{$original_key}' was reformatted to '{$processed['key']}'");
        }

        // Process field based on type
        if (isset($processed['type'])) {
            switch ($processed['type']) {
                case 'repeater':
                    if (isset($processed['sub_fields']) && is_array($processed['sub_fields'])) {
                        $processed['sub_fields'] = $this->process_fields($processed['sub_fields']);
                    }
                    break;

                case 'flexible_content':
                    if (isset($processed['layouts']) && is_array($processed['layouts'])) {
                        foreach ($processed['layouts'] as &$layout) {
                            if (isset($layout['sub_fields']) && is_array($layout['sub_fields'])) {
                                $layout['sub_fields'] = $this->process_fields($layout['sub_fields']);
                            }
                        }
                    }
                    break;

                case 'group':
                    if (isset($processed['sub_fields']) && is_array($processed['sub_fields'])) {
                        $processed['sub_fields'] = $this->process_fields($processed['sub_fields']);
                    }
                    break;

                case 'clone':
                    // Ensure clone fields are properly formatted
                    if (isset($processed['clone']) && is_array($processed['clone'])) {
                        foreach ($processed['clone'] as &$clone_key) {
                            if (!preg_match('/^(field|group)_[a-zA-Z0-9]+$/', $clone_key)) {
                                $original_clone_key = $clone_key;
                                $clone_key = 'field_' . substr(md5($clone_key), 0, 13);
                                $this->add_warning("Clone field key '{$original_clone_key}' was reformatted to '{$clone_key}'");
                            }
                        }
                    }
                    break;
            }
        }

        // Process conditional logic
        if (isset($processed['conditional_logic']) && is_array($processed['conditional_logic'])) {
            $processed['conditional_logic'] = $this->process_conditional_logic($processed['conditional_logic']);
        }

        return $processed;
    }

    /**
     * Process location rules.
     *
     * @since    1.0.0
     * @param    array    $location    Location rules.
     * @return   array    Processed location rules.
     */
    protected function process_location_rules($location) {
        // Location rules are already in the correct format for JSON
        // This method exists for potential future processing needs
        return $location;
    }

    /**
     * Process conditional logic.
     *
     * @since    1.0.0
     * @param    array    $conditional_logic    Conditional logic rules.
     * @return   array    Processed conditional logic.
     */
    protected function process_conditional_logic($conditional_logic) {
        // Conditional logic is already in the correct format for JSON
        // This method exists for potential future processing needs
        return $conditional_logic;
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
        $this->logger->error('PHP to JSON Converter: ' . $message);
    }

    /**
     * Add conversion warning.
     *
     * @since    1.0.0
     * @param    string    $message    Warning message.
     */
    protected function add_warning($message) {
        $this->warnings[] = $message;
        $this->logger->warning('PHP to JSON Converter: ' . $message);
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