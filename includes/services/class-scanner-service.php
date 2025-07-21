<?php
/**
 * Scanner Service.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Services
 */

namespace ACF_PHP_JSON_Converter\Services;

use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Security;
use ACF_PHP_JSON_Converter\Parsers\PHP_Parser;

/**
 * Scanner Service Class.
 *
 * Responsible for scanning theme files and detecting ACF field groups.
 */
class Scanner_Service {

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
     * File Manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      File_Manager    $file_manager    File Manager instance.
     */
    protected $file_manager;

    /**
     * PHP Parser instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      PHP_Parser    $php_parser    PHP Parser instance.
     */
    protected $php_parser;

    /**
     * Scan errors.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $errors    Scan errors.
     */
    protected $errors = array();

    /**
     * Scan warnings.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $warnings    Scan warnings.
     */
    protected $warnings = array();

    /**
     * Cache transient name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $cache_transient    Cache transient name.
     */
    protected $cache_transient = 'acf_php_json_converter_scan_cache';

    /**
     * Cache expiration in seconds.
     *
     * @since    1.0.0
     * @access   protected
     * @var      int    $cache_expiration    Cache expiration in seconds.
     */
    protected $cache_expiration = 3600; // 1 hour

    /**
     * Disallowed directories.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $disallowed_dirs    Disallowed directories.
     */
    protected $disallowed_dirs = array(
        'node_modules',
        'vendor',
        'bower_components',
        '.git',
        '.svn',
        '.idea',
        '.vscode',
        'wp-admin',
        'wp-includes',
        'wp-content/plugins',
        'wp-content/uploads',
    );

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    Logger       $logger        Logger instance.
     * @param    Security     $security      Security instance.
     * @param    File_Manager $file_manager  File Manager instance.
     */
    public function __construct(Logger $logger, Security $security, File_Manager $file_manager) {
        $this->logger = $logger;
        $this->security = $security;
        $this->file_manager = $file_manager;
        
        // Create PHP Parser instance
        require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/parsers/class-php-parser.php';
        $this->php_parser = new PHP_Parser($logger, $security);
        
        // Apply filters to allow customization
        $this->disallowed_dirs = apply_filters('acf_php_json_converter_disallowed_dirs', $this->disallowed_dirs);
        $this->cache_expiration = apply_filters('acf_php_json_converter_scan_cache_expiration', $this->cache_expiration);
    }

    /**
     * Scan theme files for ACF field groups.
     *
     * @since    1.0.0
     * @param    string    $theme_path    Optional. Theme path to scan. Default is current theme.
     * @param    bool      $use_cache     Optional. Whether to use cached results. Default is true.
     * @return   array     Scan results.
     */
    public function scan_theme_files($theme_path = '', $use_cache = true) {
        // Reset errors and warnings
        $this->errors = array();
        $this->warnings = array();
        
        // Start timer
        $start_time = microtime(true);
        
        // Check if we should use cached results
        if ($use_cache) {
            $cached_results = $this->get_cached_results();
            if ($cached_results !== null) {
                $this->logger->info('Using cached scan results');
                return $cached_results;
            }
        }
        
        // Get theme paths
        $theme_paths = $this->get_theme_paths($theme_path);
        
        if (empty($theme_paths)) {
            $this->add_error('No valid theme paths found');
            return $this->format_scan_results(array(), $start_time);
        }
        
        $this->logger->info('Starting theme scan', array('paths' => $theme_paths));
        
        // Find PHP files in theme directories
        $php_files = $this->find_php_files($theme_paths);
        
        if (empty($php_files)) {
            $this->add_warning('No PHP files found in theme directories');
            return $this->format_scan_results(array(), $start_time);
        }
        
        $this->logger->info('Found PHP files', array('count' => count($php_files)));
        
        // Find ACF field groups in PHP files
        $field_groups = $this->find_field_groups($php_files);
        
        // Cache results
        if (!empty($field_groups)) {
            $this->cache_results($this->format_scan_results($field_groups, $start_time));
        }
        
        return $this->format_scan_results($field_groups, $start_time);
    }

    /**
     * Get theme paths.
     *
     * @since    1.0.0
     * @param    string    $theme_path    Optional. Theme path to scan. Default is current theme.
     * @return   array     Theme paths.
     */
    protected function get_theme_paths($theme_path = '') {
        if (!empty($theme_path)) {
            // Validate custom theme path
            if (!$this->security->validate_path($theme_path)) {
                $this->add_error('Invalid theme path: ' . $theme_path);
                return array();
            }
            
            return array($theme_path);
        }
        
        // Get current theme paths
        return $this->file_manager->get_theme_paths();
    }

    /**
     * Find PHP files in theme directories.
     *
     * @since    1.0.0
     * @param    array     $theme_paths    Theme paths.
     * @return   array     PHP files.
     */
    protected function find_php_files($theme_paths) {
        $php_files = array();
        
        foreach ($theme_paths as $theme_path) {
            // Check if directory exists
            if (!is_dir($theme_path)) {
                $this->add_warning('Theme directory does not exist: ' . $theme_path);
                continue;
            }
            
            // Create recursive directory iterator
            try {
                $directory_iterator = new \RecursiveDirectoryIterator($theme_path);
                $iterator = new \RecursiveIteratorIterator($directory_iterator);
                $regex_iterator = new \RegexIterator($iterator, '/\.php$/i', \RecursiveRegexIterator::GET_MATCH);
                
                foreach ($regex_iterator as $file) {
                    $file_path = $file[0];
                    
                    // Skip disallowed directories
                    if ($this->is_in_disallowed_dir($file_path)) {
                        continue;
                    }
                    
                    // Validate file path
                    if ($this->security->validate_path($file_path)) {
                        $php_files[] = $file_path;
                    }
                }
            } catch (\Exception $e) {
                $this->add_error('Error scanning directory: ' . $e->getMessage());
            }
        }
        
        return $php_files;
    }

    /**
     * Check if a file is in a disallowed directory.
     *
     * @since    1.0.0
     * @param    string    $file_path    File path.
     * @return   bool      True if in disallowed directory, false otherwise.
     */
    protected function is_in_disallowed_dir($file_path) {
        foreach ($this->disallowed_dirs as $disallowed_dir) {
            if (strpos($file_path, '/' . $disallowed_dir . '/') !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Find ACF field groups in PHP files.
     *
     * @since    1.0.0
     * @param    array     $php_files    PHP files.
     * @return   array     Field groups.
     */
    public function find_field_groups($php_files) {
        $field_groups = array();
        $files_processed = 0;
        $total_files = count($php_files);
        
        foreach ($php_files as $file_path) {
            $files_processed++;
            
            // Log progress every 50 files
            if ($files_processed % 50 === 0 || $files_processed === $total_files) {
                $this->logger->debug('Scanning progress', array(
                    'processed' => $files_processed,
                    'total' => $total_files,
                    'percentage' => round(($files_processed / $total_files) * 100)
                ));
            }
            
            // Parse file
            $file_field_groups = $this->php_parser->parse_file($file_path);
            
            if (!empty($file_field_groups)) {
                foreach ($file_field_groups as $field_group) {
                    // Add to field groups array, keyed by field group key
                    $field_groups[$field_group['key']] = $field_group;
                }
            }
        }
        
        return $field_groups;
    }

    /**
     * Format scan results.
     *
     * @since    1.0.0
     * @param    array     $field_groups    Field groups.
     * @param    float     $start_time      Start time.
     * @return   array     Formatted scan results.
     */
    protected function format_scan_results($field_groups, $start_time) {
        $execution_time = microtime(true) - $start_time;
        
        return array(
            'status' => empty($this->errors) ? 'success' : 'error',
            'field_groups' => $field_groups,
            'count' => count($field_groups),
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'execution_time' => round($execution_time, 2),
            'timestamp' => current_time('mysql'),
        );
    }

    /**
     * Cache scan results.
     *
     * @since    1.0.0
     * @param    array     $results    Scan results.
     * @return   bool      True on success, false on failure.
     */
    protected function cache_results($results) {
        return set_transient($this->cache_transient, $results, $this->cache_expiration);
    }

    /**
     * Get cached scan results.
     *
     * @since    1.0.0
     * @return   array|null    Cached scan results or null if no cache.
     */
    public function get_cached_results() {
        $cached = get_transient($this->cache_transient);
        
        if ($cached === false) {
            return null;
        }
        
        return $cached;
    }

    /**
     * Clear scan cache.
     *
     * @since    1.0.0
     * @return   bool    True on success, false on failure.
     */
    public function clear_cache() {
        return delete_transient($this->cache_transient);
    }

    /**
     * Add scan error.
     *
     * @since    1.0.0
     * @param    string    $message    Error message.
     */
    protected function add_error($message) {
        $this->errors[] = $message;
        $this->logger->error('Scanner: ' . $message);
    }

    /**
     * Add scan warning.
     *
     * @since    1.0.0
     * @param    string    $message    Warning message.
     */
    protected function add_warning($message) {
        $this->warnings[] = $message;
        $this->logger->warning('Scanner: ' . $message);
    }

    /**
     * Get scan errors.
     *
     * @since    1.0.0
     * @return   array    Scan errors.
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get scan warnings.
     *
     * @since    1.0.0
     * @return   array    Scan warnings.
     */
    public function get_warnings() {
        return $this->warnings;
    }

    /**
     * Set disallowed directories.
     *
     * @since    1.0.0
     * @param    array     $dirs    Disallowed directories.
     */
    public function set_disallowed_dirs($dirs) {
        if (is_array($dirs)) {
            $this->disallowed_dirs = $dirs;
        }
    }

    /**
     * Get disallowed directories.
     *
     * @since    1.0.0
     * @return   array    Disallowed directories.
     */
    public function get_disallowed_dirs() {
        return $this->disallowed_dirs;
    }

    /**
     * Set cache expiration.
     *
     * @since    1.0.0
     * @param    int       $seconds    Cache expiration in seconds.
     */
    public function set_cache_expiration($seconds) {
        $this->cache_expiration = intval($seconds);
    }

    /**
     * Get cache expiration.
     *
     * @since    1.0.0
     * @return   int    Cache expiration in seconds.
     */
    public function get_cache_expiration() {
        return $this->cache_expiration;
    }
}