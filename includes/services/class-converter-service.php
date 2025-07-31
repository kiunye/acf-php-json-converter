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
     * Batch convert multiple field groups with progress tracking.
     *
     * @since    1.0.0
     * @param    array     $field_groups    Field groups to convert.
     * @param    string    $direction       Conversion direction ('php_to_json' or 'json_to_php').
     * @param    string    $operation_id    Optional operation ID for progress tracking.
     * @return   array     Conversion results.
     */
    public function batch_convert($field_groups, $direction, $operation_id = null) {
        if (!is_array($field_groups) || empty($field_groups)) {
            return [
                'status' => 'error',
                'message' => 'No field groups provided for conversion',
                'results' => [],
            ];
        }
        
        // Initialize progress tracking if operation ID provided
        if ($operation_id) {
            $this->init_progress_tracking($operation_id, count($field_groups));
        }
        
        $this->logger->info('Starting batch conversion', [
            'direction' => $direction,
            'count' => count($field_groups),
            'operation_id' => $operation_id,
        ]);
        
        $results = [];
        $success_count = 0;
        $warning_count = 0;
        $error_count = 0;
        $processed_count = 0;
        
        foreach ($field_groups as $index => $field_group) {
            // Check for cancellation
            if ($operation_id && $this->is_operation_cancelled($operation_id)) {
                $this->logger->info('Batch conversion cancelled by user', ['operation_id' => $operation_id]);
                break;
            }
            
            $result = null;
            $key = isset($field_group['key']) ? $field_group['key'] : "field_group_{$index}";
            
            // Update progress
            if ($operation_id) {
                $this->update_progress($operation_id, $processed_count, "Converting {$key}...");
            }
            
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
                
                // Add field group metadata to result
                $result['key'] = $key;
                $result['title'] = isset($field_group['title']) ? $field_group['title'] : $key;
                $result['processed_at'] = current_time('mysql');
                
                $results[$key] = $result;
                
            } catch (\Exception $e) {
                $this->logger->error('Exception during batch conversion', [
                    'message' => $e->getMessage(),
                    'field_group' => $key,
                    'operation_id' => $operation_id,
                ]);
                
                $results[$key] = [
                    'status' => 'error',
                    'errors' => [$e->getMessage()],
                    'key' => $key,
                    'title' => isset($field_group['title']) ? $field_group['title'] : $key,
                    'processed_at' => current_time('mysql'),
                ];
                $error_count++;
            }
            
            $processed_count++;
            
            // Update progress after processing
            if ($operation_id) {
                $this->update_progress($operation_id, $processed_count, "Processed {$key}");
            }
        }
        
        // Determine overall status
        $status = 'success';
        if ($error_count > 0) {
            $status = $warning_count === 0 && $success_count === 0 ? 'error' : 'partial';
        } elseif ($warning_count > 0) {
            $status = $success_count === 0 ? 'warning' : 'partial';
        }
        
        // Complete progress tracking
        if ($operation_id) {
            $this->complete_progress($operation_id, $status);
        }
        
        $this->logger->info('Batch conversion completed', [
            'status' => $status,
            'success_count' => $success_count,
            'warning_count' => $warning_count,
            'error_count' => $error_count,
            'processed_count' => $processed_count,
            'operation_id' => $operation_id,
        ]);
        
        return [
            'status' => $status,
            'message' => "Converted {$success_count} field groups successfully, {$warning_count} with warnings, {$error_count} with errors",
            'success_count' => $success_count,
            'warning_count' => $warning_count,
            'error_count' => $error_count,
            'processed_count' => $processed_count,
            'total_count' => count($field_groups),
            'results' => $results,
            'operation_id' => $operation_id,
        ];
    }

    /**
     * Initialize progress tracking for batch operation.
     *
     * @since    1.0.0
     * @param    string    $operation_id    Operation ID.
     * @param    int       $total_items     Total items to process.
     */
    protected function init_progress_tracking($operation_id, $total_items) {
        $progress_data = [
            'operation_id' => $operation_id,
            'status' => 'running',
            'current' => 0,
            'total' => $total_items,
            'percentage' => 0,
            'message' => 'Initializing batch conversion...',
            'started_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];
        
        set_transient('acf_php_json_progress_' . $operation_id, $progress_data, 3600);
    }

    /**
     * Update progress for batch operation.
     *
     * @since    1.0.0
     * @param    string    $operation_id    Operation ID.
     * @param    int       $current         Current progress.
     * @param    string    $message         Progress message.
     */
    protected function update_progress($operation_id, $current, $message) {
        $progress_data = get_transient('acf_php_json_progress_' . $operation_id);
        
        if ($progress_data) {
            $progress_data['current'] = $current;
            $progress_data['percentage'] = $progress_data['total'] > 0 ? round(($current / $progress_data['total']) * 100) : 0;
            $progress_data['message'] = $message;
            $progress_data['updated_at'] = current_time('mysql');
            
            set_transient('acf_php_json_progress_' . $operation_id, $progress_data, 3600);
        }
    }

    /**
     * Complete progress tracking for batch operation.
     *
     * @since    1.0.0
     * @param    string    $operation_id    Operation ID.
     * @param    string    $status          Final status.
     */
    protected function complete_progress($operation_id, $status) {
        $progress_data = get_transient('acf_php_json_progress_' . $operation_id);
        
        if ($progress_data) {
            $progress_data['status'] = 'completed';
            $progress_data['final_status'] = $status;
            $progress_data['current'] = $progress_data['total'];
            $progress_data['percentage'] = 100;
            $progress_data['message'] = 'Batch conversion completed';
            $progress_data['completed_at'] = current_time('mysql');
            $progress_data['updated_at'] = current_time('mysql');
            
            set_transient('acf_php_json_progress_' . $operation_id, $progress_data, 3600);
        }
    }

    /**
     * Check if operation is cancelled.
     *
     * @since    1.0.0
     * @param    string    $operation_id    Operation ID.
     * @return   bool      True if cancelled, false otherwise.
     */
    protected function is_operation_cancelled($operation_id) {
        $cancellation_key = 'acf_php_json_cancel_' . $operation_id;
        return get_transient($cancellation_key) === true;
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