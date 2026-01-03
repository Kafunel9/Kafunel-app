<?php
/**
 * Plugin Name: Kafunel Live Match Ultimate
 * Plugin URI: https://kafunel.com/live-match-ultimate
 * Description: Écosystème hybride pour la couverture de matchs de football (CAN, Coupe du Monde, LDC, CAF) incluant un Plugin WordPress Pro et une structure d'Application Mobile.
 * Version: 1.0.0
 * Author: Kafunel
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kafunel-ultimate
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('KAFUNEL_ULTIMATE_VERSION', '1.0.0');
define('KAFUNEL_ULTIMATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KAFUNEL_ULTIMATE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include utility classes
require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'utils/class-logger.php';
require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'utils/class-exceptions.php';
require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'utils/class-validator.php';
require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'utils/class-cache-manager.php';

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
class Kafunel_Ultimate {

    /**
     * The loader that maintains and registers all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Kafunel_Ultimate_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;
    
    /**
     * Logger instance
     *
     * @since    1.1.0
     * @access   private
     * @var      Kafunel_Logger    $logger    Logger for the plugin.
     */
    private $logger;
    
    /**
     * Cache manager instance
     *
     * @since    1.1.0
     * @access   private
     * @var      Kafunel_Cache_Manager    $cache_manager    Cache manager for the plugin.
     */
    private $cache_manager;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->plugin_name = 'kafunel-ultimate';
        
        // Initialize utilities
        $this->logger = new Kafunel_Logger();
        $this->cache_manager = new Kafunel_Cache_Manager($this->logger);

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Kafunel_Ultimate_Loader. Orchestrates the hooks of the plugin.
     * - Kafunel_Ultimate_i18n. Defines internationalization functionality.
     * - Kafunel_Ultimate_Admin. Defines all hooks for the admin area.
     * - Kafunel_Ultimate_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'includes/class-api-manager.php';
        require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'includes/class-live-match.php';
        require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'includes/class-payment-manager.php';
        require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'includes/class-whatsapp-business.php';
        require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'includes/class-ai-analysis.php';
        require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'includes/class-mobile-integration.php';
        require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'includes/class-settings.php';

        $this->loader = new Kafunel_Ultimate_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Kafunel_Ultimate_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Kafunel_Ultimate_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Kafunel_Ultimate_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'settings_init');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new Kafunel_Ultimate_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
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
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Kafunel_Ultimate_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return KAFUNEL_ULTIMATE_VERSION;
    }
    
    /**
     * Get the logger instance
     *
     * @since     1.1.0
     * @return    Kafunel_Logger    The logger instance.
     */
    public function get_logger() {
        return $this->logger;
    }
    
    /**
     * Get the cache manager instance
     *
     * @since     1.1.0
     * @return    Kafunel_Cache_Manager    The cache manager instance.
     */
    public function get_cache_manager() {
        return $this->cache_manager;
    }
}

/**
 * The loader that's responsible for maintaining and registering all hooks that power
 * the plugin.
 */
class Kafunel_Ultimate_Loader {

    /**
     * The array of actions registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
     */
    protected $actions;

    /**
     * The array of filters registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
     */
    protected $filters;

    /**
     * Initialize the collections used to maintain the actions and filters.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string               $hook             The name of the WordPress action that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the action is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     *
     * @since    1.0.0
     * @param    string               $hook             The name of the WordPress filter that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the filter is defined.
     * @param    string               $callback         The name of the function definition on the $component.
     * @param    int                  $priority         Optional. The priority at which the function should be fired. Default is 10.
     * @param    int                  $accepted_args    Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @since    1.0.0
     * @access   private
     * @param    array                $hooks            The collection of hooks that is being registered (that is, actions or filters).
     * @param    string               $hook             The name of the WordPress filter that is being registered.
     * @param    object               $component        A reference to the instance of the object on which the filter is defined.
     * @param    string               $callback         The name of the function definition on the $callback.
     * @param    int                  $priority         The priority at which the function should be fired.
     * @param    int                  $accepted_args    The number of arguments that should be passed to the $callback.
     * @return   array                                  The collection of actions and filters registered with WordPress.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Register the filters and actions with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}

/**
 * The class responsible for defining internationalization functionality
 * of the plugin.
 */
class Kafunel_Ultimate_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'kafunel-ultimate',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }
}

/**
 * The admin-specific functionality of the plugin.
 */
class Kafunel_Ultimate_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'admin/css/kafunel-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'admin/js/kafunel-admin.js', array('jquery'), $this->version, false);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Kafunel Ultimate',
            'Kafunel Ultimate',
            'manage_options',
            'kafunel-ultimate',
            array($this, 'dashboard_page'),
            'dashicons-tickets-alt',
            20
        );
        
        add_submenu_page(
            'kafunel-ultimate',
            'Paramètres',
            'Paramètres',
            'manage_options',
            'kafunel-settings',
            array($this, 'settings_page')
        );
    }

    public function dashboard_page() {
        include_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'admin/dashboard-pro.php';
    }

    public function settings_page() {
        include_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'admin/settings-page.php';
    }

    public function settings_init() {
        include_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'includes/class-settings.php';
        $settings = new Kafunel_Ultimate_Settings();
        $settings->init_settings();
    }
}

/**
 * The public-facing functionality of the plugin.
 */
class Kafunel_Ultimate_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'public/css/live-match.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'public/js/live-update.js', array('jquery'), $this->version, true);
        wp_localize_script($this->plugin_name, 'kafunel_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kafunel_nonce')
        ));
    }

    public function register_shortcodes() {
        add_shortcode('kafunel_live_match', array($this, 'live_match_shortcode'));
    }

    public function live_match_shortcode($atts) {
        $atts = shortcode_atts(array(
            'match_id' => 0
        ), $atts);

        ob_start();
        include_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'public/templates/single-match.php';
        return ob_get_clean();
    }
}

// Initialize the plugin
function run_kafunel_ultimate() {
    global $kafunel_ultimate_instance;
    $plugin = new Kafunel_Ultimate();
    $kafunel_ultimate_instance = $plugin;
    $plugin->run();
}

run_kafunel_ultimate();