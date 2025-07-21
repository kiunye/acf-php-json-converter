<?php
/**
 * Conversion Validator.
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
 * Validator Class.
 *
 * Validates conversion accuracy and ACF compatibility.
 *
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Converters
 * @author     Your Name <your.email@example.com>
 * @license    GPL-2.0+
 * @link       https://example.com
 * @category   Converters
 */
class Validator {

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Logger    $logger    Logger instance.
     */
    protected $logger;

    /**
     * Validation errors.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $errors    Validation errors.
     */
    protected $errors = [];

    /**
     * Validation warnings.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $warnings    Validation warnings.
     */
    protected $warnings = [];

    /**
     * Required field group fields.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $required_field_group_fields    Required field group fields.
     */
    protected $required_field_group_fields = [
        'key',
        'title',
        'fields',
    ];

    /**
     * Required field fields.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $required_field_fields    Required field fields.
     */
    protected $required_field_fields = [
        'key',
        'label',
        'name',
        'type',
    ];

    /**
     * Valid field types.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $valid_field_types    Valid field types.
     */
    protected $valid_field_types = [
        'text',
        'textarea',
        'number',
        'range',
        'email',
        'url',
        'password',
        'wysiwyg',
        'oembed',
        'image',
        'file',
        'gallery',
        'select',
        'checkbox',
        'radio',
        'button_group',
        'true_false',
        'link',
        'post_object',
        'page_link',
        'relationship',
        'taxonomy',
        'user',
        'google_map',
        'date_picker',
        'date_time_picker',
        'time_picker',
        'color_picker',
        'message',
        'accordion',
        'tab',
        'group',
        'repeater',
        'flexible_content',
        'clone',
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
     * Validate field group.
     *
     * @since    1.0.0
     * @param    array    $field_group    Field group to validate.
     * @return   array    Validation result.
     */
    public function validate_field_group($field_group) {
        // Reset errors and warnings
        $this->errors = [];
        $this->warnings = [];

        // Check if field group is an array
        if (!is_array($field_group)) {
            $this->add_error('Field group must be an array');
            return $this->get_validation_result();
        }

        // Check required fields
        foreach ($this->required_field_group_fields as $field) {
            if (!isset($field_group[$field])) {
                $this->add_error("Field group is missing required field: {$field}");
                return $this->get_validation_result();
            }
        }

        // Check key format
        if (!preg_match('/^group_[a-zA-Z0-9]+$/', $field_group['key'])) {
            $this->add_warning("Field group key '{$field_group['key']}' does not follow ACF naming convention (should start with 'group_')");
        }

        // Check if fields is an array
        if (!is_array($field_group['fields'])) {
            $this->add_error('Field group fields must be an array');
            return $this->get_validation_result();
        }

        // Validate fields
        foreach ($field_group['fields'] as $field) {
            $this->validate_field($field);
        }

        // Validate location rules if present
        if (isset($field_group['location'])) {
            $this->validate_location_rules($field_group['location']);
        }

        return $this->get_validation_result();
    }

    /**
     * Validate field.
     *
     * @since    1.0.0
     * @param    array    $field    Field to validate.
     */
    protected function validate_field($field) {
        // Check if field is an array
        if (!is_array($field)) {
            $this->add_error('Field must be an array');
            return;
        }

        // Check required fields
        foreach ($this->required_field_fields as $required_field) {
            if (!isset($field[$required_field])) {
                $this->add_error("Field is missing required property: {$required_field}");
                return;
            }
        }

        // Check key format
        if (!preg_match('/^field_[a-zA-Z0-9]+$/', $field['key'])) {
            $this->add_warning("Field key '{$field['key']}' does not follow ACF naming convention (should start with 'field_')");
        }

        // Check field type
        if (!in_array($field['type'], $this->valid_field_types)) {
            $this->add_warning("Field type '{$field['type']}' may not be a valid ACF field type");
        }

        // Validate sub-fields for certain field types
        switch ($field['type']) {
            case 'repeater':
            case 'group':
                if (isset($field['sub_fields']) && is_array($field['sub_fields'])) {
                    foreach ($field['sub_fields'] as $sub_field) {
                        $this->validate_field($sub_field);
                    }
                } else {
                    $this->add_error("Field '{$field['key']}' of type '{$field['type']}' must have sub_fields array");
                }
                break;

            case 'flexible_content':
                if (isset($field['layouts']) && is_array($field['layouts'])) {
                    foreach ($field['layouts'] as $layout) {
                        if (is_array($layout)) {
                            if (!isset($layout['key'])) {
                                $this->add_error("Flexible content layout is missing required property: key");
                            }
                            
                            if (!isset($layout['name'])) {
                                $this->add_error("Flexible content layout is missing required property: name");
                            }
                            
                            if (!isset($layout['label'])) {
                                $this->add_error("Flexible content layout is missing required property: label");
                            }
                            
                            if (isset($layout['sub_fields']) && is_array($layout['sub_fields'])) {
                                foreach ($layout['sub_fields'] as $sub_field) {
                                    $this->validate_field($sub_field);
                                }
                            } else {
                                $this->add_error("Flexible content layout '{$layout['key']}' must have sub_fields array");
                            }
                        } else {
                            $this->add_error("Flexible content layout must be an array");
                        }
                    }
                } else {
                    $this->add_error("Field '{$field['key']}' of type 'flexible_content' must have layouts array");
                }
                break;

            case 'clone':
                if (!isset($field['clone']) || !is_array($field['clone'])) {
                    $this->add_error("Field '{$field['key']}' of type 'clone' must have clone array");
                }
                break;
        }

        // Validate conditional logic if present
        if (isset($field['conditional_logic']) && $field['conditional_logic'] !== 0) {
            $this->validate_conditional_logic($field['conditional_logic'], $field['key']);
        }
    }

    /**
     * Validate location rules.
     *
     * @since    1.0.0
     * @param    array    $location    Location rules to validate.
     */
    protected function validate_location_rules($location) {
        // Check if location is an array
        if (!is_array($location)) {
            $this->add_error('Location rules must be an array');
            return;
        }

        // Check if location is empty
        if (empty($location)) {
            $this->add_warning('Location rules array is empty');
            return;
        }

        // Validate each rule group
        foreach ($location as $group_index => $rule_group) {
            if (!is_array($rule_group)) {
                $this->add_error("Location rule group {$group_index} must be an array");
                continue;
            }

            // Validate each rule in the group
            foreach ($rule_group as $rule_index => $rule) {
                if (!is_array($rule)) {
                    $this->add_error("Location rule {$group_index}.{$rule_index} must be an array");
                    continue;
                }

                // Check required rule properties
                if (!isset($rule['param'])) {
                    $this->add_error("Location rule {$group_index}.{$rule_index} is missing required property: param");
                }

                if (!isset($rule['operator'])) {
                    $this->add_error("Location rule {$group_index}.{$rule_index} is missing required property: operator");
                }

                if (!isset($rule['value'])) {
                    $this->add_error("Location rule {$group_index}.{$rule_index} is missing required property: value");
                }
            }
        }
    }

    /**
     * Validate conditional logic.
     *
     * @since    1.0.0
     * @param    array     $conditional_logic    Conditional logic to validate.
     * @param    string    $field_key           Field key for error messages.
     */
    protected function validate_conditional_logic($conditional_logic, $field_key) {
        // Check if conditional logic is an array
        if (!is_array($conditional_logic)) {
            $this->add_error("Conditional logic for field '{$field_key}' must be an array");
            return;
        }

        // Validate each rule group
        foreach ($conditional_logic as $group_index => $rule_group) {
            if (!is_array($rule_group)) {
                $this->add_error("Conditional logic rule group {$group_index} for field '{$field_key}' must be an array");
                continue;
            }

            // Validate each rule in the group
            foreach ($rule_group as $rule_index => $rule) {
                if (!is_array($rule)) {
                    $this->add_error("Conditional logic rule {$group_index}.{$rule_index} for field '{$field_key}' must be an array");
                    continue;
                }

                // Check required rule properties
                if (!isset($rule['field'])) {
                    $this->add_error("Conditional logic rule {$group_index}.{$rule_index} for field '{$field_key}' is missing required property: field");
                }

                if (!isset($rule['operator'])) {
                    $this->add_error("Conditional logic rule {$group_index}.{$rule_index} for field '{$field_key}' is missing required property: operator");
                }

                if (!isset($rule['value'])) {
                    $this->add_error("Conditional logic rule {$group_index}.{$rule_index} for field '{$field_key}' is missing required property: value");
                }
            }
        }
    }

    /**
     * Validate conversion accuracy.
     *
     * @since    1.0.0
     * @param    array    $original     Original data.
     * @param    array    $converted    Converted data.
     * @return   array    Validation result.
     */
    public function validate_conversion($original, $converted) {
        // Reset errors and warnings
        $this->errors = [];
        $this->warnings = [];

        // Check if both are arrays
        if (!is_array($original) || !is_array($converted)) {
            $this->add_error('Both original and converted data must be arrays');
            return $this->get_validation_result();
        }

        // Check required fields in both
        foreach ($this->required_field_group_fields as $field) {
            if (!isset($original[$field])) {
                $this->add_error("Original data is missing required field: {$field}");
            }
            
            if (!isset($converted[$field])) {
                $this->add_error("Converted data is missing required field: {$field}");
            }
        }

        // If there are errors, return early
        if (!empty($this->errors)) {
            return $this->get_validation_result();
        }

        // Compare key fields
        $this->compare_values('key', $original, $converted);
        $this->compare_values('title', $original, $converted);

        // Compare fields array
        if (isset($original['fields']) && isset($converted['fields'])) {
            $this->compare_fields_array($original['fields'], $converted['fields']);
        }

        // Compare location rules if present
        if (isset($original['location']) && isset($converted['location'])) {
            $this->compare_location_rules($original['location'], $converted['location']);
        }

        return $this->get_validation_result();
    }

    /**
     * Compare fields array.
     *
     * @since    1.0.0
     * @param    array    $original     Original fields.
     * @param    array    $converted    Converted fields.
     */
    protected function compare_fields_array($original, $converted) {
        // Check if both are arrays
        if (!is_array($original) || !is_array($converted)) {
            $this->add_error('Both original and converted fields must be arrays');
            return;
        }

        // Check field count
        if (count($original) !== count($converted)) {
            $this->add_warning('Field count mismatch: original has ' . count($original) . ' fields, converted has ' . count($converted) . ' fields');
        }

        // Create field maps by key
        $original_fields = [];
        $converted_fields = [];

        foreach ($original as $field) {
            if (isset($field['key'])) {
                $original_fields[$field['key']] = $field;
            }
        }

        foreach ($converted as $field) {
            if (isset($field['key'])) {
                $converted_fields[$field['key']] = $field;
            }
        }

        // Check for missing fields
        foreach ($original_fields as $key => $field) {
            if (!isset($converted_fields[$key])) {
                $this->add_error("Field '{$key}' is missing from converted data");
            }
        }

        // Check for extra fields
        foreach ($converted_fields as $key => $field) {
            if (!isset($original_fields[$key])) {
                $this->add_warning("Field '{$key}' is present in converted data but not in original data");
            }
        }

        // Compare fields that exist in both
        foreach ($original_fields as $key => $original_field) {
            if (isset($converted_fields[$key])) {
                $this->compare_field($original_field, $converted_fields[$key]);
            }
        }
    }

    /**
     * Compare individual field.
     *
     * @since    1.0.0
     * @param    array    $original     Original field.
     * @param    array    $converted    Converted field.
     */
    protected function compare_field($original, $converted) {
        // Check required fields
        foreach ($this->required_field_fields as $field) {
            $this->compare_values($field, $original, $converted, "Field '{$original['key']}'");
        }

        // Compare sub-fields for certain field types
        if (isset($original['type']) && isset($converted['type'])) {
            switch ($original['type']) {
                case 'repeater':
                case 'group':
                    if (isset($original['sub_fields']) && isset($converted['sub_fields'])) {
                        $this->compare_fields_array($original['sub_fields'], $converted['sub_fields']);
                    }
                    break;

                case 'flexible_content':
                    if (isset($original['layouts']) && isset($converted['layouts'])) {
                        $this->compare_layouts($original['layouts'], $converted['layouts']);
                    }
                    break;
            }
        }
    }

    /**
     * Compare flexible content layouts.
     *
     * @since    1.0.0
     * @param    array    $original     Original layouts.
     * @param    array    $converted    Converted layouts.
     */
    protected function compare_layouts($original, $converted) {
        // Check if both are arrays
        if (!is_array($original) || !is_array($converted)) {
            $this->add_error('Both original and converted layouts must be arrays');
            return;
        }

        // Create layout maps by key
        $original_layouts = [];
        $converted_layouts = [];

        foreach ($original as $key => $layout) {
            if (is_array($layout) && isset($layout['key'])) {
                $original_layouts[$layout['key']] = $layout;
            } else {
                $original_layouts[$key] = $layout;
            }
        }

        foreach ($converted as $key => $layout) {
            if (is_array($layout) && isset($layout['key'])) {
                $converted_layouts[$layout['key']] = $layout;
            } else {
                $converted_layouts[$key] = $layout;
            }
        }

        // Check for missing layouts
        foreach ($original_layouts as $key => $layout) {
            if (!isset($converted_layouts[$key])) {
                $this->add_error("Layout '{$key}' is missing from converted data");
            }
        }

        // Compare layouts that exist in both
        foreach ($original_layouts as $key => $original_layout) {
            if (isset($converted_layouts[$key])) {
                // Compare layout properties
                if (is_array($original_layout) && is_array($converted_layouts[$key])) {
                    // Compare name and label
                    if (isset($original_layout['name']) && isset($converted_layouts[$key]['name'])) {
                        $this->compare_values('name', $original_layout, $converted_layouts[$key], "Layout '{$key}'");
                    }
                    
                    if (isset($original_layout['label']) && isset($converted_layouts[$key]['label'])) {
                        $this->compare_values('label', $original_layout, $converted_layouts[$key], "Layout '{$key}'");
                    }
                    
                    // Compare sub-fields
                    if (isset($original_layout['sub_fields']) && isset($converted_layouts[$key]['sub_fields'])) {
                        $this->compare_fields_array($original_layout['sub_fields'], $converted_layouts[$key]['sub_fields']);
                    }
                }
            }
        }
    }

    /**
     * Compare location rules.
     *
     * @since    1.0.0
     * @param    array    $original     Original location rules.
     * @param    array    $converted    Converted location rules.
     */
    protected function compare_location_rules($original, $converted) {
        // Check if both are arrays
        if (!is_array($original) || !is_array($converted)) {
            $this->add_error('Both original and converted location rules must be arrays');
            return;
        }

        // Check rule group count
        if (count($original) !== count($converted)) {
            $this->add_warning('Location rule group count mismatch: original has ' . count($original) . ' groups, converted has ' . count($converted) . ' groups');
        }

        // Compare rule groups
        $min_groups = min(count($original), count($converted));
        for ($i = 0; $i < $min_groups; $i++) {
            if (is_array($original[$i]) && is_array($converted[$i])) {
                // Check rule count in group
                if (count($original[$i]) !== count($converted[$i])) {
                    $this->add_warning("Location rule count mismatch in group {$i}: original has " . count($original[$i]) . " rules, converted has " . count($converted[$i]) . " rules");
                }

                // Compare rules in group
                $min_rules = min(count($original[$i]), count($converted[$i]));
                for ($j = 0; $j < $min_rules; $j++) {
                    if (is_array($original[$i][$j]) && is_array($converted[$i][$j])) {
                        // Compare rule properties
                        $this->compare_values('param', $original[$i][$j], $converted[$i][$j], "Location rule {$i}.{$j}");
                        $this->compare_values('operator', $original[$i][$j], $converted[$i][$j], "Location rule {$i}.{$j}");
                        $this->compare_values('value', $original[$i][$j], $converted[$i][$j], "Location rule {$i}.{$j}");
                    }
                }
            }
        }
    }

    /**
     * Compare values.
     *
     * @since    1.0.0
     * @param    string    $key         Key to compare.
     * @param    array     $original    Original data.
     * @param    array     $converted   Converted data.
     * @param    string    $context     Context for error messages.
     */
    protected function compare_values($key, $original, $converted, $context = '') {
        $context_prefix = $context ? "{$context} " : '';

        // Check if key exists in both
        if (!isset($original[$key])) {
            $this->add_warning("{$context_prefix}Key '{$key}' is missing from original data");
            return;
        }

        if (!isset($converted[$key])) {
            $this->add_error("{$context_prefix}Key '{$key}' is missing from converted data");
            return;
        }

        // Compare values
        if ($original[$key] !== $converted[$key]) {
            // Special handling for arrays
            if (is_array($original[$key]) && is_array($converted[$key])) {
                // For arrays, just check if they have the same number of elements
                if (count($original[$key]) !== count($converted[$key])) {
                    $this->add_warning("{$context_prefix}Array '{$key}' has different element count: original has " . count($original[$key]) . ", converted has " . count($converted[$key]));
                }
            } else {
                // For scalar values, check if they're equal
                $this->add_warning("{$context_prefix}Value mismatch for '{$key}': original is '" . $this->format_value($original[$key]) . "', converted is '" . $this->format_value($converted[$key]) . "'");
            }
        }
    }

    /**
     * Format value for display in error messages.
     *
     * @since    1.0.0
     * @param    mixed    $value    Value to format.
     * @return   string   Formatted value.
     */
    protected function format_value($value) {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_null($value)) {
            return 'null';
        } elseif (is_array($value)) {
            return 'array';
        } elseif (is_object($value)) {
            return 'object';
        } else {
            return (string) $value;
        }
    }

    /**
     * Add validation error.
     *
     * @since    1.0.0
     * @param    string    $message    Error message.
     */
    protected function add_error($message) {
        $this->errors[] = $message;
        $this->logger->error('Validator: ' . $message);
    }

    /**
     * Add validation warning.
     *
     * @since    1.0.0
     * @param    string    $message    Warning message.
     */
    protected function add_warning($message) {
        $this->warnings[] = $message;
        $this->logger->warning('Validator: ' . $message);
    }

    /**
     * Get validation result.
     *
     * @since    1.0.0
     * @return   array    Validation result.
     */
    protected function get_validation_result() {
        if (!empty($this->errors)) {
            return [
                'valid' => false,
                'status' => 'error',
                'errors' => $this->errors,
                'warnings' => $this->warnings,
            ];
        } elseif (!empty($this->warnings)) {
            return [
                'valid' => true,
                'status' => 'warning',
                'warnings' => $this->warnings,
            ];
        } else {
            return [
                'valid' => true,
                'status' => 'success',
            ];
        }
    }

    /**
     * Get validation errors.
     *
     * @since    1.0.0
     * @return   array    Validation errors.
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get validation warnings.
     *
     * @since    1.0.0
     * @return   array    Validation warnings.
     */
    public function get_warnings() {
        return $this->warnings;
    }
}