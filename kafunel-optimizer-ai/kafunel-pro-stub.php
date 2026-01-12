<?php
/**
 * Kafunel Optimizer AI Pro Stub
 *
 * This file serves as a placeholder for the Pro version functionality.
 * When the Pro version is installed, it will define the PRO constant
 * and include additional features.
 *
 * @package KafunelOptimizerAI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define Pro version constant
if (!defined('KAFUNEL_PRO_VERSION')) {
    define('KAFUNEL_PRO_VERSION', false);
}

// Include Pro-specific functionality if available
if (file_exists(KAFUNEL_OPTIMIZER_AI_PLUGIN_DIR . 'pro/pro-features.php')) {
    require_once KAFUNEL_OPTIMIZER_AI_PLUGIN_DIR . 'pro/pro-features.php';
} else {
    /**
     * Pro Features Placeholder
     * 
     * These functions serve as placeholders for Pro version features
     */
    
    if (!function_exists('kafunel_has_background_removal')) {
        /**
         * Check if background removal feature is available
         */
        function kafunel_has_background_removal() {
            return defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
        }
    }
    
    if (!function_exists('kafunel_has_upscale_ai')) {
        /**
         * Check if AI upscale feature is available
         */
        function kafunel_has_upscale_ai() {
            return defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
        }
    }
    
    if (!function_exists('kafunel_has_video_optimization')) {
        /**
         * Check if video optimization feature is available
         */
        function kafunel_has_video_optimization() {
            return defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
        }
    }
    
    if (!function_exists('kafunel_get_detailed_reports')) {
        /**
         * Get detailed optimization reports (Pro feature)
         */
        function kafunel_get_detailed_reports() {
            if (!kafunel_has_detailed_reports()) {
                return array(
                    'error' => true,
                    'message' => __('Detailed reports are only available in Pro version', 'kafunel-optimizer-ai')
                );
            }
            
            // Pro version would return actual reports
            return array();
        }
    }
    
    if (!function_exists('kafunel_has_detailed_reports')) {
        /**
         * Check if detailed reports feature is available
         */
        function kafunel_has_detailed_reports() {
            return defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
        }
    }
    
    if (!function_exists('kafunel_get_pro_features_list')) {
        /**
         * Get list of Pro features
         */
        function kafunel_get_pro_features_list() {
            return array(
                'unlimited_optimizations' => array(
                    'name' => __('Unlimited daily optimizations', 'kafunel-optimizer-ai'),
                    'available' => defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION
                ),
                'background_removal' => array(
                    'name' => __('Background removal using AI', 'kafunel-optimizer-ai'),
                    'available' => defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION
                ),
                'ai_upscale' => array(
                    'name' => __('AI-powered image upscale', 'kafunel-optimizer-ai'),
                    'available' => defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION
                ),
                'video_optimization' => array(
                    'name' => __('Advanced video optimization', 'kafunel-optimizer-ai'),
                    'available' => defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION
                ),
                'detailed_reports' => array(
                    'name' => __('Detailed optimization reports', 'kafunel-optimizer-ai'),
                    'available' => defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION
                ),
                'priority_support' => array(
                    'name' => __('Priority support', 'kafunel-optimizer-ai'),
                    'available' => defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION
                )
            );
        }
    }
}

// Add Pro upgrade notice in admin
add_action('admin_notices', 'kafunel_pro_upgrade_notice');
function kafunel_pro_upgrade_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Don't show if already Pro or on settings page
    $screen = get_current_screen();
    if (defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION || 
        (is_object($screen) && strpos($screen->id, 'kafunel-optimizer-ai') !== false)) {
        return;
    }
    
    // Check if notice has been dismissed
    $user_id = get_current_user_id();
    $dismissed = get_user_meta($user_id, 'kafunel_dismissed_upgrade_notice', true);
    
    if ($dismissed) {
        return;
    }
    
    echo '<div class="notice notice-info is-dismissible kafunel-upgrade-notice">';
    echo '<p>';
    echo '<strong>' . __('Upgrade to Kafunel Optimizer AI Pro', 'kafunel-optimizer-ai') . '</strong><br>';
    echo __('Unlock unlimited optimizations, AI background removal, upscale, and more!', 'kafunel-optimizer-ai');
    echo '</p>';
    echo '<p>';
    echo '<a href="' . admin_url('options-general.php?page=kafunel-optimizer-ai') . '" class="button button-primary">';
    _e('View Pro Features', 'kafunel-optimizer-ai');
    echo '</a> ';
    echo '<a href="https://kafunel.com/pricing" target="_blank" class="button button-secondary">';
    _e('Get Pro Version', 'kafunel-optimizer-ai');
    echo '</a>';
    echo '</p>';
    echo '</div>';
    
    echo '<script>
    jQuery(document).ready(function($) {
        $(".kafunel-upgrade-notice").on("click", ".notice-dismiss", function() {
            $.post(ajaxurl, {
                action: "kafunel_dismiss_upgrade_notice",
                nonce: "' . wp_create_nonce('kafunel_dismiss_notice') . '"
            });
        });
    });
    </script>';
}

// AJAX handler to dismiss notice
add_action('wp_ajax_kafunel_dismiss_upgrade_notice', 'kafunel_dismiss_upgrade_notice');
function kafunel_dismiss_upgrade_notice() {
    if (!wp_verify_nonce($_POST['nonce'], 'kafunel_dismiss_notice') || !current_user_can('manage_options')) {
        wp_die();
    }
    
    $user_id = get_current_user_id();
    update_user_meta($user_id, 'kafunel_dismissed_upgrade_notice', 1);
    wp_die();
}