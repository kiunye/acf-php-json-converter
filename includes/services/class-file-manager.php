<?php
/**
 * File Manager Service.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 * @subpackage ACF_PHP_JSON_Converter/Services
 */

namespace ACF_PHP_JSON_Converter\Services;

use ACF_PHP_JSON_Converter\Utilities\Logger;
use ACF_PHP_JSON_Converter\Utilities\Security;

/**
 * File Manager Service Class.
 *
 * Responsible for file system operations.
 */
class File_Manager {

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
     * ACF JSON directory name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $acf_json_dir    ACF JSON directory name.
     */
    protected $acf_json_dir = 'acf-json';

    /**
     * WordPress filesystem.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WP_Filesystem_Base    $wp_filesystem    WordPress filesystem.
     */
    protected $wp_filesystem;

    /**
     * Create ACF JSON directory in theme.
     *
     * @since    1.0.0
     * @param    string    $theme_path    Theme path. If empty, uses active theme.
     * @return   bool      True on success, false on failure.
     */
    public function create_acf_json_directory($theme_path = '') {
        // Initialize WordPress filesystem
        if (!$this->initialize_filesystem()) {
            $this->logger->error('Failed to initialize filesystem');
            return false;
        }

        // If no theme path provided, use active theme
        if (empty($theme_path)) {
            $theme_path = get_stylesheet_directory();
        }

        // Validate theme path
        if (!$this->security->validate_path($theme_path) || !is_dir($theme_path)) {
            $this->logger->error('Invalid theme path: ' . $theme_path);
            return false;
        }

        // Create acf-json directory path
        $acf_json_path = trailingslashit($theme_path) . $this->acf_json_dir;

        // Check if directory already exists
        if (is_dir($acf_json_path)) {
            $this->logger->info('ACF JSON directory already exists: ' . $acf_json_path);
            
            // Check if directory is writable
            if (!$this->security->is_writable($acf_json_path)) {
                $this->logger->error('ACF JSON directory exists but is not writable: ' . $acf_json_path);
                return false;
            }
            
            return true;
        }

        // Create directory
        $result = $this->wp_filesystem->mkdir($acf_json_path);
        
        if (!$result) {
            $this->logger->error('Failed to create ACF JSON directory: ' . $acf_json_path);
            return false;
        }

        // Set proper permissions (755 for directories)
        $this->wp_filesystem->chmod($acf_json_path, FS_CHMOD_DIR);

        // Create index.php file for security
        $index_file = trailingslashit($acf_json_path) . 'index.php';
        $this->wp_filesystem->put_contents($index_file, "<?php\n// Silence is golden.");
        $this->wp_filesystem->chmod($index_file, FS_CHMOD_FILE);

        $this->logger->info('Created ACF JSON directory: ' . $acf_json_path);
        return true;
    }

    /**
     * Write JSON file.
     *
     * @since    1.0.0
     * @param    string    $filename    File name.
     * @param    array     $data        JSON data.
     * @return   bool      True on success, false on failure.
     */
    public function write_json_file($filename, $data) {
        // Initialize WordPress filesystem
        if (!$this->initialize_filesystem()) {
            $this->logger->error('Failed to initialize filesystem');
            return false;
        }

        // Validate filename
        $filename = $this->security->sanitize_input($filename, 'filename');
        
        if (empty($filename)) {
            $this->logger->error('Invalid filename');
            return false;
        }

        // Ensure data is an array
        if (!is_array($data)) {
            $this->logger->error('JSON data must be an array');
            return false;
        }

        // Get active theme path
        $theme_path = get_stylesheet_directory();
        
        // Create ACF JSON directory if it doesn't exist
        if (!$this->create_acf_json_directory($theme_path)) {
            return false;
        }

        // Create file path
        $acf_json_path = trailingslashit($theme_path) . $this->acf_json_dir;
        $file_path = trailingslashit($acf_json_path) . $filename;

        // Ensure filename has .json extension
        if (pathinfo($file_path, PATHINFO_EXTENSION) !== 'json') {
            $file_path .= '.json';
        }

        // Convert data to JSON
        $json_data = wp_json_encode($data, JSON_PRETTY_PRINT);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Failed to encode JSON data: ' . json_last_error_msg());
            return false;
        }

        // Write file
        $result = $this->wp_filesystem->put_contents($file_path, $json_data);
        
        if (!$result) {
            $this->logger->error('Failed to write JSON file: ' . $file_path);
            return false;
        }

        // Set proper permissions (644 for files)
        $this->wp_filesystem->chmod($file_path, FS_CHMOD_FILE);

        $this->logger->info('Wrote JSON file: ' . $file_path);
        return true;
    }
    
    /**
     * Get ACF JSON directory path.
     *
     * @since    1.0.0
     * @param    string    $theme_path    Theme path. If empty, uses active theme.
     * @return   string    ACF JSON directory path or empty string on failure.
     */
    public function get_acf_json_directory($theme_path = '') {
        // If no theme path provided, use active theme
        if (empty($theme_path)) {
            $theme_path = get_stylesheet_directory();
        }

        // Validate theme path
        if (!$this->security->validate_path($theme_path) || !is_dir($theme_path)) {
            $this->logger->error('Invalid theme path: ' . $theme_path);
            return '';
        }

        // Create acf-json directory path
        $acf_json_path = trailingslashit($theme_path) . $this->acf_json_dir;

        // Check if directory exists
        if (!is_dir($acf_json_path)) {
            return '';
        }

        return $acf_json_path;
    }
    
    /**
     * Check if ACF JSON directory exists.
     *
     * @since    1.0.0
     * @param    string    $theme_path    Theme path. If empty, uses active theme.
     * @return   bool      True if exists, false otherwise.
     */
    public function acf_json_directory_exists($theme_path = '') {
        $acf_json_path = $this->get_acf_json_directory($theme_path);
        return !empty($acf_json_path);
    }
    
    /**
     * Initialize WordPress filesystem.
     *
     * @since    1.0.0
     * @access   protected
     * @return   bool      True on success, false on failure.
     */
    protected function initialize_filesystem() {
        global $wp_filesystem;
        
        // If filesystem already initialized
        if ($this->wp_filesystem) {
            return true;
        }
        
        // Include WordPress filesystem functionality
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        // Initialize WordPress filesystem
        if (!WP_Filesystem()) {
            return false;
        }
        
        $this->wp_filesystem = $wp_filesystem;
        return true;
    }

    /**
     * Backup directory name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $backup_dir    Backup directory name.
     */
    protected $backup_dir = 'acf-php-json-backups';

    /**
     * Maximum number of backups to keep.
     *
     * @since    1.0.0
     * @access   protected
     * @var      int    $max_backups    Maximum number of backups.
     */
    protected $max_backups = 10;

    /**
     * Create backup of files.
     *
     * @since    1.0.0
     * @param    array     $files    Files to backup.
     * @return   string    Backup path or empty string on failure.
     */
    public function create_backup($files) {
        // Initialize WordPress filesystem
        if (!$this->initialize_filesystem()) {
            $this->logger->error('Failed to initialize filesystem');
            return '';
        }

        // Validate files
        if (!is_array($files) || empty($files)) {
            $this->logger->error('No files provided for backup');
            return '';
        }

        // Create backup directory
        $backup_dir = $this->get_backup_directory();
        if (empty($backup_dir)) {
            return '';
        }

        // Create timestamped backup directory
        $timestamp = current_time('timestamp');
        $backup_subdir = trailingslashit($backup_dir) . date('Y-m-d-H-i-s', $timestamp);
        
        if (!$this->wp_filesystem->mkdir($backup_subdir)) {
            $this->logger->error('Failed to create backup subdirectory: ' . $backup_subdir);
            return '';
        }

        // Set proper permissions
        $this->wp_filesystem->chmod($backup_subdir, FS_CHMOD_DIR);

        // Copy files to backup directory
        $copied_files = [];
        $failed_files = [];
        
        foreach ($files as $file) {
            // Validate file path
            if (!$this->security->validate_path($file) || !file_exists($file)) {
                $failed_files[] = $file;
                continue;
            }
            
            // Get file name
            $file_name = basename($file);
            $backup_file = trailingslashit($backup_subdir) . $file_name;
            
            // Copy file
            if ($this->wp_filesystem->copy($file, $backup_file, true)) {
                $copied_files[] = $file;
                $this->wp_filesystem->chmod($backup_file, FS_CHMOD_FILE);
            } else {
                $failed_files[] = $file;
            }
        }

        // Log results
        if (!empty($copied_files)) {
            $this->logger->info('Backed up ' . count($copied_files) . ' files to ' . $backup_subdir);
        }
        
        if (!empty($failed_files)) {
            $this->logger->warning('Failed to backup ' . count($failed_files) . ' files', [
                'files' => $failed_files,
            ]);
        }

        // Clean up old backups
        $this->cleanup_old_backups();

        return empty($copied_files) ? '' : $backup_subdir;
    }

    /**
     * Export field groups as downloadable files.
     *
     * @since    1.0.0
     * @param    array     $field_groups    Field groups to export.
     * @return   string    Download URL or empty string on failure.
     */
    public function export_files($field_groups) {
        // Initialize WordPress filesystem
        if (!$this->initialize_filesystem()) {
            $this->logger->error('Failed to initialize filesystem');
            return '';
        }

        // Validate field groups
        if (!is_array($field_groups) || empty($field_groups)) {
            $this->logger->error('No field groups provided for export');
            return '';
        }

        // Create temporary directory for export
        $upload_dir = wp_upload_dir();
        $temp_dir = trailingslashit($upload_dir['basedir']) . 'acf-php-json-export-' . uniqid();
        
        if (!$this->wp_filesystem->mkdir($temp_dir)) {
            $this->logger->error('Failed to create temporary export directory');
            return '';
        }

        // Set proper permissions
        $this->wp_filesystem->chmod($temp_dir, FS_CHMOD_DIR);

        // Write field groups to JSON files
        $exported_files = [];
        
        foreach ($field_groups as $field_group) {
            // Skip invalid field groups
            if (!is_array($field_group) || !isset($field_group['key'])) {
                continue;
            }
            
            // Generate filename from key
            $filename = $field_group['key'] . '.json';
            $file_path = trailingslashit($temp_dir) . $filename;
            
            // Convert to JSON
            $json_data = wp_json_encode($field_group, JSON_PRETTY_PRINT);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Failed to encode field group to JSON: ' . json_last_error_msg(), [
                    'field_group_key' => $field_group['key'],
                ]);
                continue;
            }
            
            // Write file
            if ($this->wp_filesystem->put_contents($file_path, $json_data)) {
                $this->wp_filesystem->chmod($file_path, FS_CHMOD_FILE);
                $exported_files[] = $file_path;
            } else {
                $this->logger->error('Failed to write export file: ' . $file_path);
            }
        }

        // If no files were exported, clean up and return
        if (empty($exported_files)) {
            $this->wp_filesystem->rmdir($temp_dir, true);
            return '';
        }

        // Create ZIP archive if multiple field groups
        if (count($exported_files) > 1) {
            $zip_file = trailingslashit($upload_dir['basedir']) . 'acf-field-groups-' . date('Y-m-d') . '.zip';
            $result = $this->create_zip_archive($exported_files, $zip_file, $temp_dir);
            
            // Clean up temporary directory
            $this->wp_filesystem->rmdir($temp_dir, true);
            
            if ($result) {
                return $upload_dir['baseurl'] . '/' . basename($zip_file);
            } else {
                return '';
            }
        } else {
            // Single file export
            $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $exported_files[0]);
            return $file_url;
        }
    }

    /**
     * Get backup directory.
     *
     * @since    1.0.0
     * @return   string    Backup directory path or empty string on failure.
     */
    public function get_backup_directory() {
        // Initialize WordPress filesystem
        if (!$this->initialize_filesystem()) {
            $this->logger->error('Failed to initialize filesystem');
            return '';
        }

        // Create backup directory in uploads
        $upload_dir = wp_upload_dir();
        $backup_dir = trailingslashit($upload_dir['basedir']) . $this->backup_dir;
        
        // Check if directory exists
        if (is_dir($backup_dir)) {
            // Check if directory is writable
            if (!$this->security->is_writable($backup_dir)) {
                $this->logger->error('Backup directory exists but is not writable: ' . $backup_dir);
                return '';
            }
            
            return $backup_dir;
        }
        
        // Create directory
        if (!$this->wp_filesystem->mkdir($backup_dir)) {
            $this->logger->error('Failed to create backup directory: ' . $backup_dir);
            return '';
        }
        
        // Set proper permissions
        $this->wp_filesystem->chmod($backup_dir, FS_CHMOD_DIR);
        
        // Create index.php file for security
        $index_file = trailingslashit($backup_dir) . 'index.php';
        $this->wp_filesystem->put_contents($index_file, "<?php\n// Silence is golden.");
        $this->wp_filesystem->chmod($index_file, FS_CHMOD_FILE);
        
        // Create .htaccess file for security
        $htaccess_file = trailingslashit($backup_dir) . '.htaccess';
        $htaccess_content = "# Deny access to all files\n";
        $htaccess_content .= "<Files ~ \".*\">\n";
        $htaccess_content .= "    Order Allow,Deny\n";
        $htaccess_content .= "    Deny from all\n";
        $htaccess_content .= "</Files>\n";
        $this->wp_filesystem->put_contents($htaccess_file, $htaccess_content);
        $this->wp_filesystem->chmod($htaccess_file, FS_CHMOD_FILE);
        
        $this->logger->info('Created backup directory: ' . $backup_dir);
        return $backup_dir;
    }

    /**
     * Cleanup old backups.
     *
     * @since    1.0.0
     * @return   bool      True on success, false on failure.
     */
    public function cleanup_old_backups() {
        // Initialize WordPress filesystem
        if (!$this->initialize_filesystem()) {
            $this->logger->error('Failed to initialize filesystem');
            return false;
        }

        // Get backup directory
        $backup_dir = $this->get_backup_directory();
        if (empty($backup_dir)) {
            return false;
        }

        // Get all backup subdirectories
        $subdirs = glob(trailingslashit($backup_dir) . '*', GLOB_ONLYDIR);
        
        if (empty($subdirs) || count($subdirs) <= $this->max_backups) {
            return true;
        }

        // Sort by name (timestamp) in ascending order
        sort($subdirs);

        // Remove oldest backups
        $to_remove = count($subdirs) - $this->max_backups;
        $removed = 0;
        
        for ($i = 0; $i < $to_remove; $i++) {
            if ($this->wp_filesystem->rmdir($subdirs[$i], true)) {
                $removed++;
                $this->logger->info('Removed old backup: ' . $subdirs[$i]);
            } else {
                $this->logger->warning('Failed to remove old backup: ' . $subdirs[$i]);
            }
        }

        return $removed === $to_remove;
    }

    /**
     * Create ZIP archive.
     *
     * @since    1.0.0
     * @param    array     $files       Files to include in ZIP.
     * @param    string    $zip_file    ZIP file path.
     * @param    string    $base_dir    Base directory for relative paths.
     * @return   bool      True on success, false on failure.
     */
    public function create_zip_archive($files, $zip_file, $base_dir = '') {
        // Check if ZIP extension is available
        if (!class_exists('ZipArchive')) {
            $this->logger->error('ZIP extension not available');
            return false;
        }

        // Create ZIP archive
        $zip = new ZipArchive();
        
        if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->logger->error('Failed to create ZIP file: ' . $zip_file);
            return false;
        }

        // Add files to ZIP
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $this->logger->warning('File does not exist: ' . $file);
                continue;
            }
            
            // Get relative path if base directory is provided
            if (!empty($base_dir) && strpos($file, $base_dir) === 0) {
                $relative_path = substr($file, strlen($base_dir) + 1);
                $zip->addFile($file, $relative_path);
            } else {
                $zip->addFile($file, basename($file));
            }
        }

        // Close ZIP file
        $result = $zip->close();
        
        if ($result) {
            $this->logger->info('Created ZIP archive: ' . $zip_file);
            
            // Set proper permissions
            $this->wp_filesystem->chmod($zip_file, FS_CHMOD_FILE);
        } else {
            $this->logger->error('Failed to close ZIP file: ' . $zip_file);
        }
        
        return $result;
    }

    /**
     * Get list of backups.
     *
     * @since    1.0.0
     * @return   array     List of backups.
     */
    public function get_backups() {
        // Get backup directory
        $backup_dir = $this->get_backup_directory();
        if (empty($backup_dir)) {
            return [];
        }

        // Get all backup subdirectories
        $subdirs = glob(trailingslashit($backup_dir) . '*', GLOB_ONLYDIR);
        if (empty($subdirs)) {
            return [];
        }

        // Sort by name (timestamp) in descending order
        rsort($subdirs);

        // Format backup information
        $backups = [];
        
        foreach ($subdirs as $subdir) {
            $name = basename($subdir);
            
            // Parse timestamp from directory name
            $timestamp = strtotime(str_replace('-', ' ', $name));
            
            // Get files in backup
            $files = glob(trailingslashit($subdir) . '*');
            $file_count = count($files);
            
            $backups[] = [
                'name' => $name,
                'path' => $subdir,
                'timestamp' => $timestamp,
                'date' => date('Y-m-d H:i:s', $timestamp),
                'file_count' => $file_count,
            ];
        }

        return $backups;
    }

    /**
     * Restore backup.
     *
     * @since    1.0.0
     * @param    string    $backup_name    Backup name (directory name).
     * @return   bool      True on success, false on failure.
     */
    public function restore_backup($backup_name) {
        // Initialize WordPress filesystem
        if (!$this->initialize_filesystem()) {
            $this->logger->error('Failed to initialize filesystem');
            return false;
        }

        // Get backup directory
        $backup_dir = $this->get_backup_directory();
        if (empty($backup_dir)) {
            return false;
        }

        // Validate backup name
        $backup_name = $this->security->sanitize_input($backup_name, 'filename');
        $backup_path = trailingslashit($backup_dir) . $backup_name;
        
        if (!is_dir($backup_path)) {
            $this->logger->error('Backup not found: ' . $backup_name);
            return false;
        }

        // Get files in backup
        $files = glob(trailingslashit($backup_path) . '*');
        if (empty($files)) {
            $this->logger->warning('No files found in backup: ' . $backup_name);
            return false;
        }

        // Get theme paths
        $theme_paths = $this->get_theme_paths();
        
        // Create ACF JSON directories if they don't exist
        foreach ($theme_paths as $theme_path) {
            $this->create_acf_json_directory($theme_path);
        }

        // Restore files
        $restored_files = [];
        $failed_files = [];
        
        foreach ($files as $file) {
            $file_name = basename($file);
            
            // Skip index.php and other non-JSON files
            if ($file_name === 'index.php' || pathinfo($file, PATHINFO_EXTENSION) !== 'json') {
                continue;
            }
            
            // Determine destination path (active theme)
            $dest_path = trailingslashit($theme_paths['current']) . $this->acf_json_dir . '/' . $file_name;
            
            // Copy file
            if ($this->wp_filesystem->copy($file, $dest_path, true)) {
                $restored_files[] = $dest_path;
                $this->wp_filesystem->chmod($dest_path, FS_CHMOD_FILE);
            } else {
                $failed_files[] = $file;
            }
        }

        // Log results
        if (!empty($restored_files)) {
            $this->logger->info('Restored ' . count($restored_files) . ' files from backup: ' . $backup_name);
        }
        
        if (!empty($failed_files)) {
            $this->logger->warning('Failed to restore ' . count($failed_files) . ' files', [
                'files' => $failed_files,
            ]);
        }

        return !empty($restored_files);
    }

    /**
     * Delete backup.
     *
     * @since    1.0.0
     * @param    string    $backup_name    Backup name (directory name).
     * @return   bool      True on success, false on failure.
     */
    public function delete_backup($backup_name) {
        // Initialize WordPress filesystem
        if (!$this->initialize_filesystem()) {
            $this->logger->error('Failed to initialize filesystem');
            return false;
        }

        // Get backup directory
        $backup_dir = $this->get_backup_directory();
        if (empty($backup_dir)) {
            return false;
        }

        // Validate backup name
        $backup_name = $this->security->sanitize_input($backup_name, 'filename');
        $backup_path = trailingslashit($backup_dir) . $backup_name;
        
        if (!is_dir($backup_path)) {
            $this->logger->error('Backup not found: ' . $backup_name);
            return false;
        }

        // Delete backup directory
        $result = $this->wp_filesystem->rmdir($backup_path, true);
        
        if ($result) {
            $this->logger->info('Deleted backup: ' . $backup_name);
        } else {
            $this->logger->error('Failed to delete backup: ' . $backup_name);
        }
        
        return $result;
    }

    /**
     * Get theme paths (parent and child if applicable).
     *
     * @since    1.0.0
     * @return   array    Theme paths.
     */
    public function get_theme_paths() {
        $theme_paths = array();
        
        // Get current theme directory
        $theme_paths['current'] = get_stylesheet_directory();
        
        // If using child theme, also include parent theme
        if (is_child_theme()) {
            $theme_paths['parent'] = get_template_directory();
        }
        
        return $theme_paths;
    }
}