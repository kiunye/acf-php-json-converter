<?php
/**
 * Converter Service.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Services
 */

namespace ACF_PHP_JSON_Converter\Services;

use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Security;

/**
 * Converter Service Class.
 *
 * Responsible for converting between PHP and JSON formats.
 */
class Converter_Service {

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
     * PHP to JSON converter instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      \ACF_PHP_JSON_Converter\Converters\PHP_To_JSON_Converter    $php_to_json_converter    PHP to JSON converter instance.
     */
    protected $php_to_json_converter;

    /**
     * Convert PHP field group to JSON.
     *
     * @since    1.0.0
     * @param    array    $php_data    PHP field group data.
     * @return   array    Converted JSON data and status.
     */
    public function convert_php_to_json($php_data) {
        // Lazy load the converter
        if (!$this->php_to_json_converter) {
            require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/converters/class-php-to-json-converter.php';
            $this->php_to_json_converter = new \ACF_PHP_JSON_Converter\Converters\PHP_To_JSON_Converter($this->logger);
        }
        
        $this->logger->info('Converting PHP field group to JSON', [
            'field_group_key' => isset($php_data['key']) ? $php_data['key'] : 'unknown',
        ]);
        
        // Perform the conversion
        $result = $this->php_to_json_converter->convert($php_data);
        
        // Log the result
        if ($result['status'] === 'success') {
            $this->logger->info('PHP to JSON conversion successful', [
                'field_group_key' => isset($result['data']['key']) ? $result['data']['key'] : 'unknown',
            ]);
        } elseif ($result['status'] === 'warning') {
            $this->logger->warning('PHP to JSON conversion completed with warnings', [
                'field_group_key' => isset($result['data']['key']) ? $result['data']['key'] : 'unknown',
                'warnings' => $result['warnings'],
            ]);
        } else {
            $this->logger->error('PHP to JSON conversion failed', [
                'errors' => isset($result['errors']) ? $result['errors'] : ['Unknown error'],
            ]);
        }
        
        return $result;
    }

    /**
     * JSON to PHP converter instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      \ACF_PHP_JSON_Converter\Converters\JSON_To_PHP_Converter    $json_to_php_converter    JSON to PHP converter instance.
     */
    protected $json_to_php_converter;

    /**
     * Convert JSON field group to PHP.
     *
     * @since    1.0.0
     * @param    array    $json_data    JSON field group data.
     * @return   string   PHP code.
     */
    public function convert_json_to_php($json_data) {
        // Lazy load the converter
        if (!$this->json_to_php_converter) {
            $this->json_to_php_converter = new \ACF_PHP_JSON_Converter\Converters\JSON_To_PHP_Converter($this->logger);
        }
        
        $this->logger->info('Converting JSON field group to PHP', [
            'field_group_key' => isset($json_data['key']) ? $json_data['key'] : 'unknown',
        ]);
        
        // Perform the conversion
        $result = $this->json_to_php_converter->convert($json_data);
        
        // Log the result
        if ($result['status'] === 'success') {
            $this->logger->info('JSON to PHP conversion successful', [
                'field_group_key' => isset($json_data['key']) ? $json_data['key'] : 'unknown',
            ]);
        } elseif ($result['status'] === 'warning') {
            $this->logger->warning('JSON to PHP conversion completed with warnings', [
                'field_group_key' => isset($json_data['key']) ? $json_data['key'] : 'unknown',
                'warnings' => $result['warnings'],
            ]);
        } else {
            $this->logger->error('JSON to PHP conversion failed', [
                'errors' => isset($result['errors']) ? $result['errors'] : ['Unknown error'],
            ]);
            return '';
        }
        
        return $result['data'];
    }

    /**
     * Batch convert multiple field groups.
     *
     * @since    1.0.0
     * @param    array     $field_groups    Field groups to convert.
     * @param    string    $direction       Conversion direction ('php_to_json' or 'json_to_php').
     * @return   array     Conversion results.
     */
    public function batch_convert($field_groups, $direction) {
        if (!is_array($field_groups) || empty($field_groups)) {
            return [
                'status' => 'error',
                'message' => 'No field groups provided for conversion',
                'results' => [],
            ];
        }
        
        $this->logger->info('Starting batch conversion', [
            'direction' => $direction,
            'count' => count($field_groups),
        ]);
        
        $results = [];
        $success_count = 0;
        $warning_count = 0;
        $error_count = 0;
        
        foreach ($field_groups as $index => $field_group) {
            $result = null;
            
            try {
                if ($direction === 'php_to_json') {
                    $result = $this->convert_php_to_json($field_group);
                } elseif ($direction === 'json_to_php') {
                    $php_code = $this->convert_json_to_php($field_group);
                    $result = [
                        'status' => !empty($php_code) ? 'success' : 'error',
                        'data' => $php_code,
                    ];
                } else {
                    $result = [
                        'status' => 'error',
                        'errors' => ['Invalid conversion direction'],
                    ];
                }
                
                // Count results by status
                if ($result['status'] === 'success') {
                    $success_count++;
                } elseif ($result['status'] === 'warning') {
                    $warning_count++;
                } else {
                    $error_count++;
                }
                
                // Add field group key to result for identification
                $key = isset($field_group['key']) ? $field_group['key'] : "field_group_{$index}";
                $result['key'] = $key;
                
                $results[$key] = $result;
            } catch (\Exception $e) {
                $this->logger->error('Exception during batch conversion', [
                    'message' => $e->getMessage(),
                    'field_group' => isset($field_group['key']) ? $field_group['key'] : "field_group_{$index}",
                ]);
                
                $key = isset($field_group['key']) ? $field_group['key'] : "field_group_{$index}";
                $results[$key] = [
                    'status' => 'error',
                    'errors' => [$e->getMessage()],
                    'key' => $key,
                ];
                $error_count++;
            }
        }
        
        // Determine overall status
        $status = 'success';
        if ($error_count > 0) {
            $status = $warning_count === 0 && $success_count === 0 ? 'error' : 'partial';
        } elseif ($warning_count > 0) {
            $status = $success_count === 0 ? 'warning' : 'partial';
        }
        
        $this->logger->info('Batch conversion completed', [
            'status' => $status,
            'success_count' => $success_count,
            'warning_count' => $warning_count,
            'error_count' => $error_count,
        ]);
        
        return [
            'status' => $status,
            'message' => "Converted {$success_count} field groups successfully, {$warning_count} with warnings, {$error_count} with errors",
            'success_count' => $success_count,
            'warning_count' => $warning_count,
            'error_count' => $error_count,
            'results' => $results,
        ];
    }

    /**
     * Validator instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      \ACF_PHP_JSON_Converter\Converters\Validator    $validator    Validator instance.
     */
    protected $validator;

    /**
     * Validate conversion result.
     *
     * @since    1.0.0
     * @param    array    $original     Original data.
     * @param    array    $converted    Converted data.
     * @return   array    Validation result.
     */
    public function validate_conversion($original, $converted) {
        // Lazy load the validator
        if (!$this->validator) {
            $this->validator = new \ACF_PHP_JSON_Converter\Converters\Validator($this->logger);
        }
        
        $this->logger->info('Validating conversion', [
            'original_key' => isset($original['key']) ? $original['key'] : 'unknown',
            'converted_key' => isset($converted['key']) ? $converted['key'] : 'unknown',
        ]);
        
        // Perform validation
        $result = $this->validator->validate_conversion($original, $converted);
        
        // Log the result
        if ($result['status'] === 'success') {
            $this->logger->info('Validation successful', [
                'field_group_key' => isset($original['key']) ? $original['key'] : 'unknown',
            ]);
        } elseif ($result['status'] === 'warning') {
            $this->logger->warning('Validation completed with warnings', [
                'field_group_key' => isset($original['key']) ? $original['key'] : 'unknown',
                'warnings' => $result['warnings'],
            ]);
        } else {
            $this->logger->error('Validation failed', [
                'field_group_key' => isset($original['key']) ? $original['key'] : 'unknown',
                'errors' => $result['errors'],
            ]);
        }
        
        return $result;
    }
    
    /**
     * Validate field group.
     *
     * @since    1.0.0
     * @param    array    $field_group    Field group to validate.
     * @return   array    Validation result.
     */
    public function validate_field_group($field_group) {
        // Lazy load the validator
        if (!$this->validator) {
            $this->validator = new \ACF_PHP_JSON_Converter\Converters\Validator($this->logger);
        }
        
        $this->logger->info('Validating field group', [
            'field_group_key' => isset($field_group['key']) ? $field_group['key'] : 'unknown',
        ]);
        
        // Perform validation
        $result = $this->validator->validate_field_group($field_group);
        
        // Log the result
        if ($result['status'] === 'success') {
            $this->logger->info('Field group validation successful', [
                'field_group_key' => isset($field_group['key']) ? $field_group['key'] : 'unknown',
            ]);
        } elseif ($result['status'] === 'warning') {
            $this->logger->warning('Field group validation completed with warnings', [
                'field_group_key' => isset($field_group['key']) ? $field_group['key'] : 'unknown',
                'warnings' => $result['warnings'],
            ]);
        } else {
            $this->logger->error('Field group validation failed', [
                'field_group_key' => isset($field_group['key']) ? $field_group['key'] : 'unknown',
                'errors' => $result['errors'],
            ]);
        }
        
        return $result;
    }
}