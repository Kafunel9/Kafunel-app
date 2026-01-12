<?php
/**
 * Template for the Kafunel Optimizer AI settings page.
 *
 * @package KafunelOptimizerAI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

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

    <!-- Plugin Information Section -->
    <div class="card">
        <h2><?php _e('Plugin Information', 'kafunel-optimizer-ai'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Plugin Version', 'kafunel-optimizer-ai'); ?></th>
                <td><?php echo KAFUNEL_OPTIMIZER_AI_VERSION; ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('PHP Extensions', 'kafunel-optimizer-ai'); ?></th>
                <td>
                    <?php
                    $gd_available = extension_loaded('gd') ? '<span style="color: #00a32a;">' . __('Available', 'kafunel-optimizer-ai') . '</span>' : '<span style="color: #dc3232;">' . __('Not available', 'kafunel-optimizer-ai') . '</span>';
                    $imagick_available = extension_loaded('imagick') ? '<span style="color: #00a32a;">' . __('Available', 'kafunel-optimizer-ai') . '</span>' : '<span style="color: #dc3232;">' . __('Not available', 'kafunel-optimizer-ai') . '</span>';
                    
                    echo sprintf(
                        __('GD Library: %s | ImageMagick: %s', 'kafunel-optimizer-ai'),
                        $gd_available,
                        $imagick_available
                    );
                    ?>
                </td>
            </tr>
        </table>
    </div>

    <!-- Usage Statistics Section -->
    <div class="card">
        <h2><?php _e('Usage Statistics', 'kafunel-optimizer-ai'); ?></h2>
        <?php
        $engine = new Kafunel_Optimizer_Engine();
        $stats = $engine->get_optimization_stats();
        
        $used_today = get_option('kafunel_used_today', 0);
        $daily_limit = get_option('kafunel_daily_limit', 10);
        $is_pro = defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Total Optimized', 'kafunel-optimizer-ai'); ?></th>
                <td><?php echo number_format_i18n($stats['total_optimized']); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Total Savings', 'kafunel-optimizer-ai'); ?></th>
                <td>
                    <?php echo size_format($stats['total_savings']); ?> 
                    (<?php echo number_format_i18n($stats['total_savings_percent']); ?>%)
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Avg. Compression', 'kafunel-optimizer-ai'); ?></th>
                <td><?php echo number_format_i18n($stats['avg_compression_ratio'], 2); ?>%</td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Today\'s Usage', 'kafunel-optimizer-ai'); ?></th>
                <td>
                    <?php
                    if ($is_pro) {
                        echo '<span class="pro-badge">' . __('Pro Version - Unlimited', 'kafunel-optimizer-ai') . '</span>';
                    } else {
                        $usage_percentage = $daily_limit > 0 ? ($used_today / $daily_limit) * 100 : 0;
                        $bar_color = $usage_percentage > 80 ? '#dc3232' : ($usage_percentage > 50 ? '#ffba00' : '#00a32a');
                        
                        echo sprintf(
                            __('%d out of %d optimizations used', 'kafunel-optimizer-ai'),
                            $used_today,
                            $daily_limit
                        );
                        
                        echo '<div style="margin-top: 8px; width: 200px; height: 10px; background-color: #ddd; border-radius: 5px;">';
                        echo '<div style="width: ' . $usage_percentage . '%; height: 100%; background-color: ' . $bar_color . '; border-radius: 5px;"></div>';
                        echo '</div>';
                        
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

    <!-- API Connection Test -->
    <div class="card">
        <h2><?php _e('API Connection', 'kafunel-optimizer-ai'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Test Connection', 'kafunel-optimizer-ai'); ?></th>
                <td>
                    <button type="button" id="test-api-connection" class="button button-secondary">
                        <?php _e('Test API Connection', 'kafunel-optimizer-ai'); ?>
                    </button>
                    <span id="api-test-result"></span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Upgrade to Pro Section -->
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

    <!-- Support Information -->
    <div class="card">
        <h2><?php _e('Support Information', 'kafunel-optimizer-ai'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Contact Support', 'kafunel-optimizer-ai'); ?></th>
                <td>
                    <p>
                        <?php _e('For assistance, please contact our support team:', 'kafunel-optimizer-ai'); ?><br>
                        <strong><?php _e('Email:', 'kafunel-optimizer-ai'); ?></strong> 
                        <a href="mailto:support@kafunel.com">support@kafunel.com</a><br>
                        <strong><?php _e('Commercial:', 'kafunel-optimizer-ai'); ?></strong> 
                        <a href="mailto:kafunel9@gmail.com">kafunel9@gmail.com</a>
                    </p>
                    <p>
                        <strong><?php _e('Phone / WhatsApp:', 'kafunel-optimizer-ai'); ?></strong><br>
                        +221 33 867 42 76 / +221 77 541 82 31
                    </p>
                </td>
            </tr>
        </table>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Reset quota counter
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
                alert(response.data || '<?php _e('Error resetting counter', 'kafunel-optimizer-ai'); ?>');
            }
        }).fail(function() {
            alert('<?php _e('Connection error', 'kafunel-optimizer-ai'); ?>');
        });
    });
    
    // Test API connection
    $('#test-api-connection').on('click', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var $result = $('#api-test-result');
        
        $btn.prop('disabled', true).text('<?php _e('Testing...', 'kafunel-optimizer-ai'); ?>');
        $result.html('<em><?php _e('Testing API connection...', 'kafunel-optimizer-ai'); ?></em>');
        
        $.post(ajaxurl, {
            action: 'kafunel_test_api_connection',
            nonce: '<?php echo wp_create_nonce("kafunel_ajax_nonce"); ?>'
        }, function(response) {
            if(response.success) {
                $result.html('<span style="color: #00a32a;">✓ <?php _e('API connection successful!', 'kafunel-optimizer-ai'); ?></span>');
            } else {
                $result.html('<span style="color: #dc3232;">✗ ' + (response.data || '<?php _e('API connection failed', 'kafunel-optimizer-ai'); ?>') + '</span>');
            }
        }).fail(function() {
            $result.html('<span style="color: #dc3232;">✗ <?php _e('Connection error', 'kafunel-optimizer-ai'); ?></span>');
        }).always(function() {
            $btn.prop('disabled', false).text('<?php _e('Test API Connection', 'kafunel-optimizer-ai'); ?>');
        });
    });
});
</script>

<style>
.pro-badge {
    background-color: #00a32a;
    color: white;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.9em;
}
.card {
    margin-top: 20px;
    padding: 20px;
    border: 1px solid #ccd0d4;
    background: #fff;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
</style>