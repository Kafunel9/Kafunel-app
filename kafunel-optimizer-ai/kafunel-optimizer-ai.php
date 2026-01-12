<?php
/**
 * Plugin Name: Kafunel Optimizer AI
 * Plugin URI: https://kafunel.com/
 * Description: Advanced image optimization plugin with AI integration for WordPress. Optimize images using local methods (GD/ImageMagick) and external AI APIs.
 * Version: 1.0.0
 * Author: Kafunel Team
 * Author URI: https://kafunel.com/
 * License: GPL v3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: kafunel-optimizer-ai
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load configuration
require_once plugin_dir_path(__FILE__) . 'config.php';

// Define plugin constants
define('KAFUNEL_OPTIMIZER_AI_VERSION', '1.0.0');
define('KAFUNEL_OPTIMIZER_AI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KAFUNEL_OPTIMIZER_AI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KAFUNEL_OPTIMIZER_AI_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class Kafunel_Optimizer_AI {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Load plugin after WordPress is ready
        add_action('plugins_loaded', array($this, 'load_plugin'));
        
        // Admin related hooks
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Media library integration
        add_filter('manage_media_columns', array($this, 'add_media_columns'));
        add_action('manage_media_custom_column', array($this, 'manage_media_custom_column'), 10, 2);
        add_filter('bulk_actions-upload', array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-upload', array($this, 'handle_bulk_actions'), 10, 3);
        
        // AJAX handlers
        add_action('wp_ajax_kafunel_optimize_image', array($this, 'ajax_optimize_image'));
        add_action('wp_ajax_kafunel_bulk_optimize', array($this, 'ajax_bulk_optimize'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Cleanup if needed
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'kafunel_optimizer_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            attachment_id bigint(20) NOT NULL,
            original_size bigint(20) DEFAULT 0,
            optimized_size bigint(20) DEFAULT 0,
            compression_ratio decimal(5,2) DEFAULT 0.00,
            format varchar(10) DEFAULT '',
            optimization_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY attachment_id (attachment_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Set default options
     */
    private function set_default_options() {
        $defaults = array(
            'kafunel_enabled' => 1,
            'kafunel_compression_level' => 'optimal',
            'kafunel_output_format' => 'auto',
            'kafunel_auto_resize' => 0,
            'kafunel_max_width' => 1920,
            'kafunel_max_height' => 1080,
            'kafunel_webp_support' => 0,
            'kafunel_avif_support' => 0,
            'kafunel_daily_limit' => 10,
            'kafunel_images_per_batch' => 20,
            'kafunel_quota_reset_time' => date('Y-m-d'),
            'kafunel_used_today' => 0,
            'kafunel_api_key' => '',
            'kafunel_ai_provider' => 'local'
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }

    /**
     * Load the plugin
     */
    public function load_plugin() {
        // Load required files
        $this->load_dependencies();
        
        // Load installer
        require_once KAFUNEL_OPTIMIZER_AI_PLUGIN_DIR . 'install.php';
        
        // Load Pro stub
        require_once KAFUNEL_OPTIMIZER_AI_PLUGIN_DIR . 'kafunel-pro-stub.php';
        
        // Initialize components
        $this->init_components();
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        require_once KAFUNEL_OPTIMIZER_AI_PLUGIN_DIR . 'includes/class-kafunel-settings.php';
        require_once KAFUNEL_OPTIMIZER_AI_PLUGIN_DIR . 'includes/class-kafunel-engine.php';
        require_once KAFUNEL_OPTIMIZER_AI_PLUGIN_DIR . 'includes/class-kafunel-media-integration.php';
        require_once KAFUNEL_OPTIMIZER_AI_PLUGIN_DIR . 'includes/class-kafunel-api-handler.php';
    }

    /**
     * Initialize components
     */
    private function init_components() {
        if (is_admin()) {
            new Kafunel_Optimizer_Settings();
        }
        
        new Kafunel_Optimizer_Engine();
        new Kafunel_Optimizer_Media_Integration();
        new Kafunel_Optimizer_API_Handler();
    }

    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings
        register_setting('kafunel_optimizer_settings', 'kafunel_enabled');
        register_setting('kafunel_optimizer_settings', 'kafunel_compression_level');
        register_setting('kafunel_optimizer_settings', 'kafunel_output_format');
        register_setting('kafunel_optimizer_settings', 'kafunel_auto_resize');
        register_setting('kafunel_optimizer_settings', 'kafunel_max_width');
        register_setting('kafunel_optimizer_settings', 'kafunel_max_height');
        register_setting('kafunel_optimizer_settings', 'kafunel_webp_support');
        register_setting('kafunel_optimizer_settings', 'kafunel_avif_support');
        register_setting('kafunel_optimizer_settings', 'kafunel_api_key');
        register_setting('kafunel_optimizer_settings', 'kafunel_ai_provider');
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Kafunel Optimizer AI Settings', 'kafunel-optimizer-ai'),
            __('Kafunel Optimizer AI', 'kafunel-optimizer-ai'),
            'manage_options',
            'kafunel-optimizer-ai',
            array($this, 'settings_page')
        );
    }

    /**
     * Settings page callback
     */
    public function settings_page() {
        include KAFUNEL_OPTIMIZER_AI_PLUGIN_DIR . 'templates/settings-page.php';
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_kafunel-optimizer-ai') {
            return;
        }
        
        wp_enqueue_style(
            'kafunel-admin-style',
            KAFUNEL_OPTIMIZER_AI_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            KAFUNEL_OPTIMIZER_AI_VERSION
        );
        
        wp_enqueue_script(
            'kafunel-admin-script',
            KAFUNEL_OPTIMIZER_AI_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            KAFUNEL_OPTIMIZER_AI_VERSION,
            true
        );
        
        wp_localize_script('kafunel-admin-script', 'kafunel_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kafunel_ajax_nonce')
        ));
    }

    /**
     * Add media columns
     */
    public function add_media_columns($columns) {
        $columns['kafunel_optimization'] = __('Optimization', 'kafunel-optimizer-ai');
        return $columns;
    }

    /**
     * Manage media custom column
     */
    public function manage_media_custom_column($column_name, $post_id) {
        if ($column_name === 'kafunel_optimization') {
            echo '<button type="button" class="button button-secondary kafunel-optimize-btn" data-id="' . esc_attr($post_id) . '">' . 
                 __('Optimize with Kafunel', 'kafunel-optimizer-ai') . '</button>';
        }
    }

    /**
     * Add bulk actions
     */
    public function add_bulk_actions($actions) {
        $actions['kafunel_optimize'] = __('Optimize with Kafunel', 'kafunel-optimizer-ai');
        return $actions;
    }

    /**
     * Handle bulk actions
     */
    public function handle_bulk_actions($redirect_to, $action, $post_ids) {
        if ($action !== 'kafunel_optimize') {
            return $redirect_to;
        }
        
        // Process optimization for selected images
        $optimized_count = 0;
        
        foreach ($post_ids as $post_id) {
            if ($this->can_optimize_image($post_id)) {
                $engine = new Kafunel_Optimizer_Engine();
                if ($engine->optimize_attachment($post_id)) {
                    $optimized_count++;
                }
            }
        }
        
        $redirect_to = add_query_arg('kafunel_optimized', $optimized_count, $redirect_to);
        return $redirect_to;
    }

    /**
     * Check if we can optimize an image
     */
    private function can_optimize_image($post_id) {
        // Check if it's an image
        $mime_type = get_post_mime_type($post_id);
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        
        if (!in_array($mime_type, $allowed_types)) {
            return false;
        }
        
        // Check daily quota for free version
        $daily_limit = get_option('kafunel_daily_limit', 10);
        $used_today = get_option('kafunel_used_today', 0);
        
        // For free version, check if we've reached the limit
        $is_pro = defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
        if (!$is_pro && $used_today >= $daily_limit) {
            return false;
        }
        
        return true;
    }

    /**
     * AJAX handler for optimizing single image
     */
    public function ajax_optimize_image() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'kafunel_ajax_nonce') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'kafunel-optimizer-ai'));
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$this->can_optimize_image($post_id)) {
            wp_send_json_error(__('Cannot optimize this image', 'kafunel-optimizer-ai'));
        }
        
        $engine = new Kafunel_Optimizer_Engine();
        $result = $engine->optimize_attachment($post_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Image optimized successfully', 'kafunel-optimizer-ai'),
                'post_id' => $post_id
            ));
        } else {
            wp_send_json_error(__('Failed to optimize image', 'kafunel-optimizer-ai'));
        }
    }

    /**
     * AJAX handler for bulk optimization
     */
    public function ajax_bulk_optimize() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'kafunel_ajax_nonce') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'kafunel-optimizer-ai'));
        }
        
        $post_ids = array_map('intval', $_POST['post_ids']);
        $results = array();
        $success_count = 0;
        
        foreach ($post_ids as $post_id) {
            if ($this->can_optimize_image($post_id)) {
                $engine = new Kafunel_Optimizer_Engine();
                $result = $engine->optimize_attachment($post_id);
                
                if ($result) {
                    $success_count++;
                    $results[$post_id] = array('status' => 'success', 'message' => __('Optimized', 'kafunel-optimizer-ai'));
                } else {
                    $results[$post_id] = array('status' => 'error', 'message' => __('Failed', 'kafunel-optimizer-ai'));
                }
            } else {
                $results[$post_id] = array('status' => 'error', 'message' => __('Cannot optimize', 'kafunel-optimizer-ai'));
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d images processed', 'kafunel-optimizer-ai'), count($post_ids)),
            'results' => $results,
            'success_count' => $success_count
        ));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('kafunel-optimizer/v1', '/optimize/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_optimize_image'),
            'permission_callback' => array($this, 'rest_permission_check'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                )
            )
        ));
    }

    /**
     * REST API permission check
     */
    public function rest_permission_check($request) {
        return current_user_can('manage_options');
    }

    /**
     * REST API optimize image handler
     */
    public function rest_optimize_image($request) {
        $post_id = $request->get_param('id');
        
        if (!$this->can_optimize_image($post_id)) {
            return new WP_REST_Response(array('error' => __('Cannot optimize this image', 'kafunel-optimizer-ai')), 400);
        }
        
        $engine = new Kafunel_Optimizer_Engine();
        $result = $engine->optimize_attachment($post_id);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Image optimized successfully', 'kafunel-optimizer-ai'),
                'post_id' => $post_id
            ), 200);
        } else {
            return new WP_REST_Response(array('error' => __('Failed to optimize image', 'kafunel-optimizer-ai')), 500);
        }
    }

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

/**
 * Initialize the plugin
 */
function kafunel_optimizer_ai_init() {
    Kafunel_Optimizer_AI::get_instance();
}
add_action('plugins_loaded', 'kafunel_optimizer_ai_init');