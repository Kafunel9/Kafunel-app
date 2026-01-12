<?php
/**
 * Kafunel Optimizer Settings Class
 *
 * Handles the admin settings page for the Kafunel Optimizer AI plugin.
 *
 * @package KafunelOptimizerAI
 * @subpackage Includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kafunel_Optimizer_Settings {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'init_settings'));
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_options_page(
            __('Kafunel Optimizer AI Settings', 'kafunel-optimizer-ai'),
            __('Kafunel Optimizer AI', 'kafunel-optimizer-ai'),
            'manage_options',
            'kafunel-optimizer-ai',
            array($this, 'settings_page_html')
        );
    }

    /**
     * Initialize settings
     */
    public function init_settings() {
        // Register settings
        register_setting(
            'kafunel_optimizer_settings',
            'kafunel_enabled',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'kafunel_sanitize_checkbox',
                'default' => 1
            )
        );

        register_setting(
            'kafunel_optimizer_settings',
            'kafunel_compression_level',
            array(
                'type' => 'string',
                'sanitize_callback' => 'kafunel_sanitize_compression_level',
                'default' => 'optimal'
            )
        );

        register_setting(
            'kafunel_optimizer_settings',
            'kafunel_output_format',
            array(
                'type' => 'string',
                'sanitize_callback' => 'kafunel_sanitize_output_format',
                'default' => 'auto'
            )
        );

        register_setting(
            'kafunel_optimizer_settings',
            'kafunel_auto_resize',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'kafunel_sanitize_checkbox',
                'default' => 0
            )
        );

        register_setting(
            'kafunel_optimizer_settings',
            'kafunel_max_width',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'kafunel_sanitize_dimension',
                'default' => 1920
            )
        );

        register_setting(
            'kafunel_optimizer_settings',
            'kafunel_max_height',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'kafunel_sanitize_dimension',
                'default' => 1080
            )
        );

        register_setting(
            'kafunel_optimizer_settings',
            'kafunel_webp_support',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'kafunel_sanitize_checkbox',
                'default' => 0
            )
        );

        register_setting(
            'kafunel_optimizer_settings',
            'kafunel_avif_support',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'kafunel_sanitize_checkbox',
                'default' => 0
            )
        );

        register_setting(
            'kafunel_optimizer_settings',
            'kafunel_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => 'kafunel_sanitize_api_key',
                'default' => ''
            )
        );

        register_setting(
            'kafunel_optimizer_settings',
            'kafunel_ai_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => 'kafunel_sanitize_ai_provider',
                'default' => 'local'
            )
        );
    }

    /**
     * Sanitize checkbox values
     */
    public function sanitize_checkbox($input) {
        return isset($input) ? 1 : 0;
    }

    /**
     * Sanitize compression level
     */
    public function sanitize_compression_level($input) {
        $valid_levels = array('lossless', 'optimal', 'lossy', 'maximum');
        return in_array($input, $valid_levels) ? $input : 'optimal';
    }

    /**
     * Sanitize output format
     */
    public function sanitize_output_format($input) {
        $valid_formats = array('auto', 'jpg', 'png', 'webp', 'avif');
        return in_array($input, $valid_formats) ? $input : 'auto';
    }

    /**
     * Sanitize dimension values
     */
    public function sanitize_dimension($input) {
        $input = absint($input);
        return $input > 0 ? $input : 1920;
    }

    /**
     * Sanitize API key
     */
    public function sanitize_api_key($input) {
        // Basic sanitization - remove whitespace and special characters that might be problematic
        return sanitize_text_field(trim($input));
    }

    /**
     * Sanitize AI provider selection
     */
    public function sanitize_ai_provider($input) {
        $valid_providers = array('local', 'cloudinary', 'imgix', 'custom');
        return in_array($input, $valid_providers) ? $input : 'local';
    }

    /**
     * Settings page HTML
     */
    public function settings_page_html() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Show error/update messages
        settings_errors('kafunel_optimizer_messages');

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                // Output security fields
                settings_fields('kafunel_optimizer_settings');
                // Output setting sections and fields
                do_settings_sections('kafunel-optimizer-ai');
                // Output save settings button
                submit_button(__('Save Settings', 'kafunel-optimizer-ai'));
                ?>
            </form>

            <!-- Quota information section -->
            <div class="card">
                <h2><?php _e('Usage Information', 'kafunel-optimizer-ai'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Today\'s Usage', 'kafunel-optimizer-ai'); ?></th>
                        <td>
                            <?php
                            $used_today = get_option('kafunel_used_today', 0);
                            $daily_limit = get_option('kafunel_daily_limit', 10);
                            $is_pro = defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
                            
                            if ($is_pro) {
                                echo '<span class="pro-badge">' . __('Pro Version - Unlimited', 'kafunel-optimizer-ai') . '</span>';
                            } else {
                                echo sprintf(
                                    __('%d out of %d optimizations used today', 'kafunel-optimizer-ai'),
                                    $used_today,
                                    $daily_limit
                                );
                                
                                // Show reset button for free version
                                echo '<br><br>';
                                echo '<button type="button" id="reset-quota-btn" class="button button-secondary">';
                                _e('Reset Daily Counter', 'kafunel-optimizer-ai');
                                echo '</button>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Current Plan', 'kafunel-optimizer-ai'); ?></th>
                        <td>
                            <?php 
                            if ($is_pro) {
                                echo '<strong style="color: #00a32a;">' . __('Pro Version', 'kafunel-optimizer-ai') . '</strong>';
                            } else {
                                echo '<strong style="color: #e66100;">' . __('Free Version', 'kafunel-optimizer-ai') . '</strong>';
                                echo '<p class="description">' . __('Limited to 10 optimizations per day', 'kafunel-optimizer-ai') . '</p>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Upgrade to Pro section -->
            <div class="card">
                <h2><?php _e('Upgrade to Pro', 'kafunel-optimizer-ai'); ?></h2>
                <p><?php _e('Unlock advanced features with Kafunel Optimizer AI Pro:', 'kafunel-optimizer-ai'); ?></p>
                <ul>
                    <li><?php _e('Unlimited daily optimizations', 'kafunel-optimizer-ai'); ?></li>
                    <li><?php _e('Background removal using AI', 'kafunel-optimizer-ai'); ?></li>
                    <li><?php _e('AI-powered image upscale', 'kafunel-optimizer-ai'); ?></li>
                    <li><?php _e('Advanced video optimization', 'kafunel-optimizer-ai'); ?></li>
                    <li><?php _e('Detailed optimization reports', 'kafunel-optimizer-ai'); ?></li>
                    <li><?php _e('Priority support', 'kafunel-optimizer-ai'); ?></li>
                </ul>
                <p>
                    <a href="https://kafunel.com/pricing" target="_blank" class="button button-primary">
                        <?php _e('Get Pro Version', 'kafunel-optimizer-ai'); ?>
                    </a>
                </p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#reset-quota-btn').on('click', function(e) {
                e.preventDefault();
                
                $.post(ajaxurl, {
                    action: 'kafunel_reset_daily_counter',
                    nonce: '<?php echo wp_create_nonce("kafunel_reset_counter"); ?>'
                }, function(response) {
                    if(response.success) {
                        alert('<?php _e('Daily counter reset successfully', 'kafunel-optimizer-ai'); ?>');
                        location.reload();
                    } else {
                        alert('<?php _e('Error resetting counter', 'kafunel-optimizer-ai'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// Additional helper functions for sanitization
function kafunel_sanitize_checkbox($input) {
    return isset($input) ? 1 : 0;
}

function kafunel_sanitize_compression_level($input) {
    $valid_levels = array('lossless', 'optimal', 'lossy', 'maximum');
    return in_array($input, $valid_levels) ? $input : 'optimal';
}

function kafunel_sanitize_output_format($input) {
    $valid_formats = array('auto', 'jpg', 'png', 'webp', 'avif');
    return in_array($input, $valid_formats) ? $input : 'auto';
}

function kafunel_sanitize_dimension($input) {
    $input = absint($input);
    return $input > 0 ? $input : 1920;
}

function kafunel_sanitize_api_key($input) {
    return sanitize_text_field(trim($input));
}

function kafunel_sanitize_ai_provider($input) {
    $valid_providers = array('local', 'cloudinary', 'imgix', 'custom');
    return in_array($input, $valid_providers) ? $input : 'local';
}