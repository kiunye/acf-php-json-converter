<?php
/**
 * The main plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @since      1.0.0
 * @package    ACF_PHP_JSON_Converter
 */

namespace ACF_PHP_JSON_Converter;

/**
 * The core plugin class.
 */
class Plugin {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The services container.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $services    Container for all service instances.
     */
    protected $services = [];

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->load_dependencies();
        $this->register_services();
        $this->define_admin_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Core plugin loader
        require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/class-loader.php';
        $this->loader = new Loader();

        // Load service classes
        require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/services/class-scanner-service.php';
        require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/services/class-converter-service.php';
        require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/services/class-file-manager.php';
        
        // Load utility classes
        require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/utilities/class-logger.php';
        require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/utilities/class-security.php';
        require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/utilities/class-error-handler.php';
        require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/utilities/class-progress-tracker.php';
        
        // Load admin classes
        require_once ACF_PHP_JSON_CONVERTER_DIR . 'includes/admin/class-admin-controller.php';
    }

    /**
     * Register all services used by the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_services() {
        // Register utility services
        $this->services['logger'] = new Utilities\Logger();
        $this->services['security'] = new Utilities\Security();
        
        // Register core services
        $this->services['file_manager'] = new Services\File_Manager(
            $this->services['logger'],
            $this->services['security']
        );
        
        $this->services['scanner'] = new Services\Scanner_Service(
            $this->services['logger'],
            $this->services['security'],
            $this->services['file_manager']
        );
        
        $this->services['converter'] = new Services\Converter_Service(
            $this->services['logger'],
            $this->services['security']
        );
        
        // Register admin controller
        $this->services['admin'] = new Admin\Admin_Controller(
            $this->services['scanner'],
            $this->services['converter'],
            $this->services['file_manager'],
            $this->services['logger'],
            $this->services['security']
        );
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        // Admin menu and pages
        $this->loader->add_action('admin_menu', $this->services['admin'], 'add_admin_menu');
        $this->loader->add_action('admin_enqueue_scripts', $this->services['admin'], 'enqueue_assets');
        
        // AJAX handlers
        $this->loader->add_action('wp_ajax_acf_php_json_scan_theme', $this->services['admin'], 'ajax_scan_theme');
        $this->loader->add_action('wp_ajax_acf_php_json_convert_php_to_json', $this->services['admin'], 'ajax_convert_php_to_json');
        $this->loader->add_action('wp_ajax_acf_php_json_convert_json_to_php', $this->services['admin'], 'ajax_convert_json_to_php');
        $this->loader->add_action('wp_ajax_acf_php_json_save_settings', $this->services['admin'], 'ajax_save_settings');
        $this->loader->add_action('wp_ajax_acf_php_json_load_settings', $this->services['admin'], 'ajax_load_settings');
        $this->loader->add_action('wp_ajax_acf_php_json_clear_log', $this->services['admin'], 'ajax_clear_log');
        $this->loader->add_action('wp_ajax_acf_php_json_get_error_log', $this->services['admin'], 'ajax_get_error_log');
        $this->loader->add_action('wp_ajax_acf_php_json_reset_settings', $this->services['admin'], 'ajax_reset_settings');
        $this->loader->add_action('wp_ajax_acf_php_json_cleanup_logs', $this->services['admin'], 'ajax_cleanup_logs');
        $this->loader->add_action('wp_ajax_acf_php_json_preview_field_group', $this->services['admin'], 'ajax_preview_field_group');
        $this->loader->add_action('wp_ajax_acf_php_json_download_field_group', $this->services['admin'], 'ajax_download_field_group');
        $this->loader->add_action('wp_ajax_acf_php_json_batch_process', $this->services['admin'], 'ajax_batch_process');
        
        // Enhanced error handling and progress tracking AJAX handlers
        $this->loader->add_action('wp_ajax_acf_php_json_get_progress', $this->services['admin'], 'ajax_get_progress');
        $this->loader->add_action('wp_ajax_acf_php_json_cancel_operation', $this->services['admin'], 'ajax_cancel_operation');
        $this->loader->add_action('wp_ajax_acf_php_json_batch_convert_with_progress', $this->services['admin'], 'ajax_batch_convert_with_progress');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Get a service instance.
     *
     * @since     1.0.0
     * @param     string    $service    The service name.
     * @return    object    The service instance.
     */
    public function get_service($service) {
        if (isset($this->services[$service])) {
            return $this->services[$service];
        }
        return null;
    }
}