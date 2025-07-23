<?php
/**
 * Admin Controller.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Admin
 */

namespace ACF_PHP_JSON_Converter\Admin;

use ACF_PHP_JSON_Converter\Services\Scanner_Service;
use ACF_PHP_JSON_Converter\Services\Converter_Service;
use ACF_PHP_JSON_Converter\Services\File_Manager;
use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Security;

/**
 * Admin Controller Class.
 *
 * Handles admin interface and AJAX requests.
 */
class Admin_Controller {

    /**
     * Scanner Service instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Scanner_Service    $scanner    Scanner Service instance.
     */
    protected $scanner;

    /**
     * Converter Service instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Converter_Service    $converter    Converter Service instance.
     */
    protected $converter;

    /**
     * File Manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      File_Manager    $file_manager    File Manager instance.
     */
    protected $file_manager;

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
     * @param    Scanner_Service     $scanner       Scanner Service instance.
     * @param    Converter_Service   $converter     Converter Service instance.
     * @param    File_Manager        $file_manager  File Manager instance.
     * @param    Logger              $logger        Logger instance.
     * @param    Security            $security      Security instance.
     */
    public function __construct(
        Scanner_Service $scanner,
        Converter_Service $converter,
        File_Manager $file_manager,
        Logger $logger,
        Security $security
    ) {
        $this->scanner = $scanner;
        $this->converter = $converter;
        $this->file_manager = $file_manager;
        $this->logger = $logger;
        $this->security = $security;
    }

    /**
     * Add admin menu.
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        // Check if user has required capabilities
        if (!$this->security->check_capability('manage_options')) {
            return;
        }

        add_management_page(
            __('ACF PHP-JSON Converter', 'acf-php-json-converter'),
            __('ACF PHP-JSON Converter', 'acf-php-json-converter'),
            'manage_options',
            'acf-php-json-converter',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Render admin page.
     *
     * @since    1.0.0
     */
    public function render_admin_page() {
        // Check user capabilities
        if (!$this->security->check_capability('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'acf-php-json-converter'));
        }

        // Render the admin page template
        $this->render_template('admin-page');
    }

    /**
     * Render a template file.
     *
     * @since    1.0.0
     * @param    string    $template_name    The template name (without .php extension).
     * @param    array     $args             Arguments to pass to the template.
     */
    protected function render_template($template_name, $args = array()) {
        $template_path = ACF_PHP_JSON_CONVERTER_DIR . 'templates/' . $template_name . '.php';
        
        if (!file_exists($template_path)) {
            $this->logger->error('Template file not found: ' . $template_path);
            return;
        }

        // Extract args to make them available in template
        if (!empty($args)) {
            extract($args, EXTR_SKIP);
        }

        // Include the template
        include $template_path;
    }

    /**
     * Enqueue admin assets.
     *
     * @since    1.0.0
     * @param    string    $hook_suffix    The current admin page.
     */
    public function enqueue_assets($hook_suffix) {
        // This is a placeholder. Full implementation will be added in task 6.2
        if ('tools_page_acf-php-json-converter' !== $hook_suffix) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'acf-php-json-converter-admin',
            ACF_PHP_JSON_CONVERTER_URL . 'assets/css/admin.css',
            array(),
            ACF_PHP_JSON_CONVERTER_VERSION
        );

        // Enqueue JS
        wp_enqueue_script(
            'acf-php-json-converter-admin',
            ACF_PHP_JSON_CONVERTER_URL . 'assets/js/admin.js',
            array('jquery'),
            ACF_PHP_JSON_CONVERTER_VERSION,
            true
        );

        // Localize script
        wp_localize_script(
            'acf-php-json-converter-admin',
            'acfPhpJsonConverter',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('acf_php_json_converter_nonce'),
            )
        );
    }

    /**
     * AJAX handler for scanning theme.
     *
     * @since    1.0.0
     */
    public function ajax_scan_theme() {
        // Verify nonce and capabilities
        if (!$this->verify_ajax_request()) {
            return;
        }

        try {
            // Get theme path from request (optional)
            $theme_path = isset($_POST['theme_path']) ? $this->security->sanitize_text_field($_POST['theme_path']) : '';
            
            // Use cache by default, but allow forcing refresh
            $use_cache = !isset($_POST['force_refresh']) || $_POST['force_refresh'] !== 'true';
            
            $this->logger->info('Starting theme scan via AJAX', array(
                'theme_path' => $theme_path,
                'use_cache' => $use_cache
            ));
            
            // Perform the scan
            $scan_results = $this->scanner->scan_theme_files($theme_path, $use_cache);
            
            if ($scan_results['status'] === 'error') {
                wp_send_json_error(array(
                    'message' => __('Scan completed with errors. Check the error log for details.', 'acf-php-json-converter'),
                    'errors' => $scan_results['errors'],
                    'warnings' => $scan_results['warnings'],
                    'execution_time' => $scan_results['execution_time']
                ));
                return;
            }
            
            // Format field groups for display
            $formatted_field_groups = array();
            foreach ($scan_results['field_groups'] as $field_group) {
                $formatted_field_groups[] = array(
                    'key' => $field_group['key'],
                    'title' => $field_group['title'],
                    'source_file' => isset($field_group['_acf_php_json_converter']['source_file']) 
                        ? basename($field_group['_acf_php_json_converter']['source_file']) 
                        : 'Unknown',
                    'source_file_full' => isset($field_group['_acf_php_json_converter']['source_file']) 
                        ? $field_group['_acf_php_json_converter']['source_file'] 
                        : '',
                    'field_count' => is_array($field_group['fields']) ? count($field_group['fields']) : 0,
                    'modified_date' => isset($field_group['_acf_php_json_converter']['modified_date']) 
                        ? date('Y-m-d H:i:s', $field_group['_acf_php_json_converter']['modified_date']) 
                        : 'Unknown',
                    'has_location' => isset($field_group['location']) && !empty($field_group['location']),
                    'menu_order' => isset($field_group['menu_order']) ? $field_group['menu_order'] : 0,
                    'position' => isset($field_group['position']) ? $field_group['position'] : 'normal',
                    'style' => isset($field_group['style']) ? $field_group['style'] : 'default'
                );
            }
            
            wp_send_json_success(array(
                'message' => sprintf(
                    _n(
                        'Scan completed successfully! Found %d field group.',
                        'Scan completed successfully! Found %d field groups.',
                        $scan_results['count'],
                        'acf-php-json-converter'
                    ),
                    $scan_results['count']
                ),
                'field_groups' => $formatted_field_groups,
                'count' => $scan_results['count'],
                'warnings' => $scan_results['warnings'],
                'execution_time' => $scan_results['execution_time'],
                'timestamp' => $scan_results['timestamp']
            ));
            
        } catch (Exception $e) {
            $this->logger->error('AJAX scan theme error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while scanning theme files.', 'acf-php-json-converter'),
            ));
        }
    }

    /**
     * AJAX handler for converting PHP to JSON.
     *
     * @since    1.0.0
     */
    public function ajax_convert_php_to_json() {
        // Verify nonce and capabilities
        if (!$this->verify_ajax_request()) {
            return;
        }

        try {
            // Get field group key from request
            $field_group_key = isset($_POST['field_group_key']) ? $this->security->sanitize_text_field($_POST['field_group_key']) : '';
            
            if (empty($field_group_key)) {
                wp_send_json_error(array(
                    'message' => __('Field group key is required.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            $this->logger->info('Converting PHP field group to JSON', array(
                'field_group_key' => $field_group_key
            ));
            
            // Get cached scan results to find the field group
            $scan_results = $this->scanner->get_cached_results();
            
            if (!$scan_results || empty($scan_results['field_groups'])) {
                wp_send_json_error(array(
                    'message' => __('No scan results found. Please scan theme files first.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            // Find the specific field group
            $field_group = null;
            foreach ($scan_results['field_groups'] as $group) {
                if ($group['key'] === $field_group_key) {
                    $field_group = $group;
                    break;
                }
            }
            
            if (!$field_group) {
                wp_send_json_error(array(
                    'message' => __('Field group not found in scan results.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            // Convert to JSON format using converter service
            $conversion_result = $this->converter->convert_php_to_json($field_group);
            
            if (!$conversion_result['success']) {
                wp_send_json_error(array(
                    'message' => __('Failed to convert field group to JSON.', 'acf-php-json-converter'),
                    'errors' => $conversion_result['errors'],
                ));
                return;
            }
            
            // Create backup before saving
            $backup_files = array();
            $acf_json_dir = $this->file_manager->get_acf_json_directory();
            if (!empty($acf_json_dir)) {
                $existing_file = trailingslashit($acf_json_dir) . $field_group['key'] . '.json';
                if (file_exists($existing_file)) {
                    $backup_files[] = $existing_file;
                }
            }
            
            if (!empty($backup_files)) {
                $backup_path = $this->file_manager->create_backup($backup_files);
                if (empty($backup_path)) {
                    $this->logger->warning('Failed to create backup before conversion');
                }
            }
            
            // Write JSON file to acf-json directory
            $filename = $field_group['key'] . '.json';
            $write_result = $this->file_manager->write_json_file($filename, $conversion_result['data']);
            
            if (!$write_result) {
                wp_send_json_error(array(
                    'message' => __('Failed to write JSON file to acf-json directory.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Field group "%s" converted and saved to acf-json directory successfully!', 'acf-php-json-converter'),
                    $field_group['title']
                ),
                'field_group_key' => $field_group['key'],
                'field_group_title' => $field_group['title'],
                'filename' => $filename,
            ));
            
        } catch (Exception $e) {
            $this->logger->error('AJAX convert PHP to JSON error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while converting PHP to JSON.', 'acf-php-json-converter'),
            ));
        }
    }

    /**
     * AJAX handler for converting JSON to PHP.
     *
     * @since    1.0.0
     */
    public function ajax_convert_json_to_php() {
        // Verify nonce and capabilities
        if (!$this->verify_ajax_request()) {
            return;
        }

        try {
            // Get JSON input from request
            $json_input = isset($_POST['json_input']) ? wp_unslash($_POST['json_input']) : '';
            
            if (empty($json_input)) {
                wp_send_json_error(array(
                    'message' => __('JSON input is required.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            $this->logger->info('Converting JSON to PHP', array(
                'json_length' => strlen($json_input)
            ));
            
            // Validate JSON format
            $json_data = json_decode($json_input, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(array(
                    'message' => sprintf(
                        __('Invalid JSON format: %s', 'acf-php-json-converter'),
                        json_last_error_msg()
                    ),
                ));
                return;
            }
            
            // Basic ACF field group validation
            if (!is_array($json_data) || empty($json_data['key']) || empty($json_data['title'])) {
                wp_send_json_error(array(
                    'message' => __('JSON does not appear to be a valid ACF field group. Missing required "key" or "title" fields.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            // Convert JSON to PHP using converter service
            $conversion_result = $this->converter->convert_json_to_php($json_data);
            
            if (!$conversion_result['success']) {
                wp_send_json_error(array(
                    'message' => __('Failed to convert JSON to PHP.', 'acf-php-json-converter'),
                    'errors' => $conversion_result['errors'],
                ));
                return;
            }
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Field group "%s" converted to PHP successfully!', 'acf-php-json-converter'),
                    $json_data['title']
                ),
                'php_code' => $conversion_result['data'],
                'field_group_key' => $json_data['key'],
                'field_group_title' => $json_data['title'],
            ));
            
        } catch (Exception $e) {
            $this->logger->error('AJAX convert JSON to PHP error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while converting JSON to PHP.', 'acf-php-json-converter'),
            ));
        }
    }

    /**
     * AJAX handler for saving settings.
     *
     * @since    1.0.0
     */
    public function ajax_save_settings() {
        // Verify nonce and capabilities
        if (!$this->verify_ajax_request()) {
            return;
        }

        try {
            // This is a placeholder. Full implementation will be added in task 9.1
            wp_send_json_success(array(
                'message' => __('Settings functionality will be implemented soon.', 'acf-php-json-converter'),
            ));
        } catch (Exception $e) {
            $this->logger->error('AJAX save settings error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while saving settings.', 'acf-php-json-converter'),
            ));
        }
    }

    /**
     * AJAX handler for previewing field group conversion.
     *
     * @since    1.0.0
     */
    public function ajax_preview_field_group() {
        // Verify nonce and capabilities
        if (!$this->verify_ajax_request()) {
            return;
        }

        try {
            // Get field group key from request
            $field_group_key = isset($_POST['field_group_key']) ? $this->security->sanitize_text_field($_POST['field_group_key']) : '';
            
            if (empty($field_group_key)) {
                wp_send_json_error(array(
                    'message' => __('Field group key is required.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            $this->logger->info('Previewing field group conversion', array(
                'field_group_key' => $field_group_key
            ));
            
            // Get cached scan results to find the field group
            $scan_results = $this->scanner->get_cached_results();
            
            if (!$scan_results || empty($scan_results['field_groups'])) {
                wp_send_json_error(array(
                    'message' => __('No scan results found. Please scan theme files first.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            // Find the specific field group
            $field_group = null;
            foreach ($scan_results['field_groups'] as $group) {
                if ($group['key'] === $field_group_key) {
                    $field_group = $group;
                    break;
                }
            }
            
            if (!$field_group) {
                wp_send_json_error(array(
                    'message' => __('Field group not found in scan results.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            // Convert to JSON format using converter service
            $conversion_result = $this->converter->convert_php_to_json($field_group);
            
            if (!$conversion_result['success']) {
                wp_send_json_error(array(
                    'message' => __('Failed to convert field group to JSON.', 'acf-php-json-converter'),
                    'errors' => $conversion_result['errors'],
                ));
                return;
            }
            
            wp_send_json_success(array(
                'title' => $field_group['title'],
                'key' => $field_group['key'],
                'json' => $conversion_result['data'],
                'message' => __('Field group preview generated successfully.', 'acf-php-json-converter'),
            ));
            
        } catch (Exception $e) {
            $this->logger->error('AJAX preview field group error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while previewing field group.', 'acf-php-json-converter'),
            ));
        }
    }

    /**
     * AJAX handler for downloading field group JSON.
     *
     * @since    1.0.0
     */
    public function ajax_download_field_group() {
        // Verify nonce and capabilities
        if (!$this->verify_ajax_request()) {
            return;
        }

        try {
            // Get field group key from request
            $field_group_key = isset($_POST['field_group_key']) ? $this->security->sanitize_text_field($_POST['field_group_key']) : '';
            
            if (empty($field_group_key)) {
                wp_send_json_error(array(
                    'message' => __('Field group key is required.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            $this->logger->info('Downloading field group JSON', array(
                'field_group_key' => $field_group_key
            ));
            
            // Get cached scan results to find the field group
            $scan_results = $this->scanner->get_cached_results();
            
            if (!$scan_results || empty($scan_results['field_groups'])) {
                wp_send_json_error(array(
                    'message' => __('No scan results found. Please scan theme files first.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            // Find the specific field group
            $field_group = null;
            foreach ($scan_results['field_groups'] as $group) {
                if ($group['key'] === $field_group_key) {
                    $field_group = $group;
                    break;
                }
            }
            
            if (!$field_group) {
                wp_send_json_error(array(
                    'message' => __('Field group not found in scan results.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            // Convert to JSON format using converter service
            $conversion_result = $this->converter->convert_php_to_json($field_group);
            
            if (!$conversion_result['success']) {
                wp_send_json_error(array(
                    'message' => __('Failed to convert field group to JSON.', 'acf-php-json-converter'),
                    'errors' => $conversion_result['errors'],
                ));
                return;
            }
            
            // Generate filename
            $filename = $field_group['key'] . '.json';
            
            // Set headers for file download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen(wp_json_encode($conversion_result['data'], JSON_PRETTY_PRINT)));
            
            // Output JSON data
            echo wp_json_encode($conversion_result['data'], JSON_PRETTY_PRINT);
            
            // Log successful download
            $this->logger->info('Field group JSON downloaded successfully', array(
                'field_group_key' => $field_group_key,
                'filename' => $filename
            ));
            
            exit;
            
        } catch (Exception $e) {
            $this->logger->error('AJAX download field group error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while downloading field group.', 'acf-php-json-converter'),
            ));
        }
    }

    /**
     * AJAX handler for batch processing field groups.
     *
     * @since    1.0.0
     */
    public function ajax_batch_process() {
        // Verify nonce and capabilities
        if (!$this->verify_ajax_request()) {
            return;
        }

        try {
            // Get field group keys and direction from request
            $field_group_keys = isset($_POST['field_group_keys']) ? $_POST['field_group_keys'] : array();
            $direction = isset($_POST['direction']) ? $this->security->sanitize_text_field($_POST['direction']) : 'php_to_json';
            
            if (empty($field_group_keys) || !is_array($field_group_keys)) {
                wp_send_json_error(array(
                    'message' => __('No field groups selected for batch processing.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            // Sanitize field group keys
            $field_group_keys = array_map(array($this->security, 'sanitize_text_field'), $field_group_keys);
            
            $this->logger->info('Starting batch processing', array(
                'field_group_keys' => $field_group_keys,
                'direction' => $direction,
                'count' => count($field_group_keys)
            ));
            
            $results = array(
                'success' => array(),
                'errors' => array(),
                'total' => count($field_group_keys)
            );
            
            if ($direction === 'php_to_json') {
                $results = $this->batch_convert_php_to_json($field_group_keys);
            } else {
                wp_send_json_error(array(
                    'message' => __('JSON to PHP batch conversion is not yet implemented.', 'acf-php-json-converter'),
                ));
                return;
            }
            
            // Log batch processing results
            $this->logger->info('Batch processing completed', array(
                'total' => $results['total'],
                'success_count' => count($results['success']),
                'error_count' => count($results['errors'])
            ));
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Batch processing completed. %d successful, %d failed.', 'acf-php-json-converter'),
                    count($results['success']),
                    count($results['errors'])
                ),
                'results' => $results
            ));
            
        } catch (Exception $e) {
            $this->logger->error('AJAX batch process error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An error occurred while batch processing.', 'acf-php-json-converter'),
            ));
        }
    }
    
    /**
     * Batch convert PHP field groups to JSON.
     *
     * @since    1.0.0
     * @param    array    $field_group_keys    Array of field group keys to convert.
     * @return   array    Results of batch conversion.
     */
    protected function batch_convert_php_to_json($field_group_keys) {
        $results = array(
            'success' => array(),
            'errors' => array(),
            'total' => count($field_group_keys)
        );
        
        // Get cached scan results
        $scan_results = $this->scanner->get_cached_results();
        
        if (!$scan_results || empty($scan_results['field_groups'])) {
            $results['errors'][] = array(
                'key' => 'scan_results',
                'message' => __('No scan results found. Please scan theme files first.', 'acf-php-json-converter')
            );
            return $results;
        }
        
        // Create a lookup array for faster access
        $field_groups_lookup = array();
        foreach ($scan_results['field_groups'] as $group) {
            $field_groups_lookup[$group['key']] = $group;
        }
        
        // Process each field group
        foreach ($field_group_keys as $field_group_key) {
            try {
                // Find the field group
                if (!isset($field_groups_lookup[$field_group_key])) {
                    $results['errors'][] = array(
                        'key' => $field_group_key,
                        'message' => __('Field group not found in scan results.', 'acf-php-json-converter')
                    );
                    continue;
                }
                
                $field_group = $field_groups_lookup[$field_group_key];
                
                // Convert to JSON format
                $conversion_result = $this->converter->convert_php_to_json($field_group);
                
                if (!$conversion_result['success']) {
                    $results['errors'][] = array(
                        'key' => $field_group_key,
                        'title' => $field_group['title'],
                        'message' => implode(', ', $conversion_result['errors'])
                    );
                    continue;
                }
                
                // Create backup before saving
                $backup_files = array();
                $acf_json_dir = $this->file_manager->get_acf_json_directory();
                if (!empty($acf_json_dir)) {
                    $existing_file = trailingslashit($acf_json_dir) . $field_group['key'] . '.json';
                    if (file_exists($existing_file)) {
                        $backup_files[] = $existing_file;
                    }
                }
                
                if (!empty($backup_files)) {
                    $backup_path = $this->file_manager->create_backup($backup_files);
                    if (empty($backup_path)) {
                        $this->logger->warning('Failed to create backup for field group: ' . $field_group_key);
                    }
                }
                
                // Write JSON file
                $filename = $field_group['key'] . '.json';
                $write_result = $this->file_manager->write_json_file($filename, $conversion_result['data']);
                
                if (!$write_result) {
                    $results['errors'][] = array(
                        'key' => $field_group_key,
                        'title' => $field_group['title'],
                        'message' => __('Failed to write JSON file to acf-json directory.', 'acf-php-json-converter')
                    );
                    continue;
                }
                
                // Success
                $results['success'][] = array(
                    'key' => $field_group_key,
                    'title' => $field_group['title'],
                    'filename' => $filename,
                    'message' => __('Converted successfully', 'acf-php-json-converter')
                );
                
            } catch (Exception $e) {
                $this->logger->error('Error converting field group in batch: ' . $field_group_key . ' - ' . $e->getMessage());
                $results['errors'][] = array(
                    'key' => $field_group_key,
                    'message' => __('An error occurred during conversion.', 'acf-php-json-converter')
                );
            }
        }
        
        return $results;
    }

    /**
     * Verify AJAX request nonce and capabilities.
     *
     * @since    1.0.0
     * @return   bool    True if request is valid, false otherwise.
     */
    protected function verify_ajax_request() {
        // Check nonce
        if (!$this->security->verify_nonce($_POST['nonce'] ?? '', 'acf_php_json_converter_nonce')) {
            $this->logger->warning('AJAX request failed nonce verification');
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh the page and try again.', 'acf-php-json-converter'),
            ));
            return false;
        }

        // Check user capabilities
        if (!$this->security->check_capability('manage_options')) {
            $this->logger->warning('AJAX request failed capability check');
            wp_send_json_error(array(
                'message' => __('You do not have sufficient permissions to perform this action.', 'acf-php-json-converter'),
            ));
            return false;
        }

        return true;
    }
}