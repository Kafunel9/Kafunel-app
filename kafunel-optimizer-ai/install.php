<?php
/**
 * Kafunel Optimizer AI - Installation Script
 *
 * Handles plugin installation tasks like creating database tables
 * and setting default options.
 *
 * @package KafunelOptimizerAI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Run installation tasks
 */
function kafunel_install() {
    global $wpdb;

    // Create database tables
    kafunel_create_tables();

    // Set default options
    kafunel_set_defaults();

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Create database tables
 */
function kafunel_create_tables() {
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
function kafunel_set_defaults() {
    $defaults = array(
        'kafunel_enabled' => 1,
        'kafunel_compression_level' => 'optimal',
        'kafunel_output_format' => 'auto',
        'kafunel_auto_resize' => 0,
        'kafunel_max_width' => KAFUNEL_DEFAULT_MAX_WIDTH,
        'kafunel_max_height' => KAFUNEL_DEFAULT_MAX_HEIGHT,
        'kafunel_webp_support' => 0,
        'kafunel_avif_support' => 0,
        'kafunel_daily_limit' => KAFUNEL_DEFAULT_DAILY_LIMIT,
        'kafunel_images_per_batch' => KAFUNEL_DEFAULT_BATCH_SIZE,
        'kafunel_quota_reset_time' => date('Y-m-d'),
        'kafunel_used_today' => 0,
        'kafunel_api_key' => '',
        'kafunel_ai_provider' => 'local'
    );

    foreach ($defaults as $key => $value) {
        if (get_option($key) === false) {
            add_option($key, $value);
        }
    }
}

/**
 * Uninstall routine
 */
function kafunel_uninstall() {
    // Remove options
    $options = array(
        'kafunel_enabled',
        'kafunel_compression_level',
        'kafunel_output_format',
        'kafunel_auto_resize',
        'kafunel_max_width',
        'kafunel_max_height',
        'kafunel_webp_support',
        'kafunel_avif_support',
        'kafunel_daily_limit',
        'kafunel_images_per_batch',
        'kafunel_quota_reset_time',
        'kafunel_used_today',
        'kafunel_api_key',
        'kafunel_ai_provider',
        'kafunel_api_logs'
    );

    foreach ($options as $option) {
        delete_option($option);
    }

    // Optionally drop database tables (commented out to preserve data)
    /*
    global $wpdb;
    $table_name = $wpdb->prefix . 'kafunel_optimizer_logs';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    */
}

// Add activation hook
register_activation_hook(dirname(__FILE__) . '/kafunel-optimizer-ai.php', 'kafunel_install');

// Add uninstall hook
register_uninstall_hook(dirname(__FILE__) . '/kafunel-optimizer-ai.php', 'kafunel_uninstall');