<?php
/**
 * Kafunel Optimizer Media Integration Class
 *
 * Handles integration with WordPress media library for the Kafunel Optimizer AI plugin.
 *
 * @package KafunelOptimizerAI
 * @subpackage Includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kafunel_Optimizer_Media_Integration {

    /**
     * Constructor
     */
    public function __construct() {
        add_filter('media_row_actions', array($this, 'add_media_row_action'), 10, 2);
        add_action('admin_footer-upload.php', array($this, 'add_bulk_actions_js'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_media_scripts'));
    }

    /**
     * Add optimize action to media row actions
     */
    public function add_media_row_action($actions, $post) {
        // Only add action for image attachments
        if (!wp_attachment_is_image($post)) {
            return $actions;
        }

        // Check if this is an allowed image type
        $mime_type = get_post_mime_type($post);
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        
        if (!in_array($mime_type, $allowed_types)) {
            return $actions;
        }

        // Check if optimization is possible today
        $is_pro = defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
        $daily_limit = get_option('kafunel_daily_limit', 10);
        $used_today = get_option('kafunel_used_today', 0);
        
        $can_optimize = $is_pro || ($used_today < $daily_limit);
        
        if (!$can_optimize) {
            $actions['kafunel_disabled'] = '<span title="' . esc_attr__('Daily limit reached', 'kafunel-optimizer-ai') . '">' . 
                                          __('Optimize with Kafunel', 'kafunel-optimizer-ai') . '</span>';
            return $actions;
        }

        // Add the optimize link
        $optimize_url = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'kafunel_optimize_single',
                    'post' => $post->ID
                ),
                admin_url('admin-ajax.php')
            ),
            'kafunel_optimize_single_' . $post->ID
        );

        $actions['kafunel_optimize'] = sprintf(
            '<a href="%s" aria-label="%s">%s</a>',
            esc_url($optimize_url),
            esc_attr__('Optimize this image with Kafunel', 'kafunel-optimizer-ai'),
            __('Optimize with Kafunel', 'kafunel-optimizer-ai')
        );

        return $actions;
    }

    /**
     * Add JavaScript for bulk actions
     */
    public function add_bulk_actions_js() {
        global $pagenow;
        
        if ($pagenow !== 'upload.php') {
            return;
        }
        
        $is_pro = defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
        $daily_limit = get_option('kafunel_daily_limit', 10);
        $used_today = get_option('kafunel_used_today', 0);
        $remaining = $daily_limit - $used_today;
        
        $can_optimize = $is_pro || ($used_today < $daily_limit);
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Handle single optimize action
            $('tr').on('click', 'a[href*="kafunel_optimize_single"]', function(e) {
                e.preventDefault();
                
                var $link = $(this);
                var postId = $link.closest('tr').find('.check-column input[type="checkbox"]').val();
                
                // Show loading indicator
                var originalText = $link.text();
                $link.text('<?php _e('Optimizing...', 'kafunel-optimizer-ai'); ?>');
                
                // Make AJAX request
                $.post(ajaxurl, {
                    action: 'kafunel_optimize_image',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce('kafunel_ajax_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $link.text('<?php _e('Optimized!', 'kafunel-optimizer-ai'); ?>');
                        
                        // Add success indicator
                        $link.after('<span class="kafunel-success-indicator">✓</span>');
                        
                        // Update usage counter display if present
                        var $usageCounter = $('.kafunel-usage-counter');
                        if ($usageCounter.length) {
                            var currentCount = parseInt($usageCounter.text().match(/\d+/)[0]);
                            $usageCounter.html($usageCounter.html().replace(/\d+/, currentCount + 1));
                        }
                        
                        // Refresh the row to show updated info
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        $link.text(originalText);
                        alert(response.data.message || '<?php _e('Failed to optimize image', 'kafunel-optimizer-ai'); ?>');
                    }
                }).fail(function() {
                    $link.text(originalText);
                    alert('<?php _e('An error occurred while optimizing the image', 'kafunel-optimizer-ai'); ?>');
                });
            });
            
            // Handle bulk optimize action
            $('#doaction, #doaction2').on('click', function(e) {
                var action = $(this).closest('form').find('select[name^="action"]').val();
                
                if (action === 'kafunel_optimize') {
                    e.preventDefault();
                    
                    var $submitBtn = $(this);
                    var $form = $(this).closest('form');
                    var actionName = $(this).attr('id') === 'doaction2' ? 'action2' : 'action';
                    
                    // Get selected attachment IDs
                    var postIds = [];
                    $form.find('input[name="media[]"]:checked').each(function() {
                        postIds.push($(this).val());
                    });
                    
                    if (postIds.length === 0) {
                        alert('<?php _e('Please select at least one image to optimize', 'kafunel-optimizer-ai'); ?>');
                        return;
                    }
                    
                    // Check daily limit for free version
                    <?php if (!$is_pro): ?>
                    if (postIds.length > <?php echo $remaining; ?>) {
                        if (!confirm('<?php printf(__('You are about to optimize %d images, but only %d optimizations remain today. Continue?', 'kafunel-optimizer-ai'), count($postIds), $remaining); ?>')) {
                            return;
                        }
                    }
                    <?php endif; ?>
                    
                    // Show confirmation
                    if (!confirm('<?php printf(__('Optimize %d selected images with Kafunel?', 'kafunel-optimizer-ai'), count($postIds)); ?>')) {
                        return;
                    }
                    
                    // Show loading state
                    var originalText = $submitBtn.text();
                    $submitBtn.text('<?php _e('Optimizing...', 'kafunel-optimizer-ai'); ?>').prop('disabled', true);
                    
                    // Make AJAX request
                    $.post(ajaxurl, {
                        action: 'kafunel_bulk_optimize',
                        post_ids: postIds,
                        nonce: '<?php echo wp_create_nonce('kafunel_ajax_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            
                            // Update usage counter display if present
                            var $usageCounter = $('.kafunel-usage-counter');
                            if ($usageCounter.length) {
                                var currentCount = parseInt($usageCounter.text().match(/\d+/)[0]);
                                $usageCounter.html($usageCounter.html().replace(/\d+/, currentCount + response.data.success_count));
                            }
                            
                            // Refresh the page to show updated info
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php _e('Bulk optimization failed', 'kafunel-optimizer-ai'); ?>');
                        }
                    }).always(function() {
                        // Restore button state
                        $submitBtn.text(originalText).prop('disabled', false);
                    });
                }
            });
        });
        </script>
        
        <style>
        .kafunel-success-indicator {
            color: #00a32a;
            margin-left: 5px;
        }
        
        .kafunel-optimize-btn {
            margin-top: 5px;
        }
        
        .kafunel-usage-warning {
            color: #ca4a1f;
            font-weight: bold;
        }
        </style>
        <?php
    }

    /**
     * Enqueue media scripts
     */
    public function enqueue_media_scripts($hook) {
        if ($hook !== 'upload.php') {
            return;
        }
        
        wp_enqueue_script(
            'kafunel-media-script',
            KAFUNEL_OPTIMIZER_AI_PLUGIN_URL . 'assets/js/media.js',
            array('jquery'),
            KAFUNEL_OPTIMIZER_AI_VERSION,
            true
        );
        
        wp_enqueue_style(
            'kafunel-media-style',
            KAFUNEL_OPTIMIZER_AI_PLUGIN_URL . 'assets/css/media.css',
            array(),
            KAFUNEL_OPTIMIZER_AI_VERSION
        );
    }

    /**
     * Add custom column to media list table
     */
    public function add_media_columns($columns) {
        $columns['kafunel_status'] = __('Kafunel Status', 'kafunel-optimizer-ai');
        return $columns;
    }

    /**
     * Display content for custom media column
     */
    public function manage_media_custom_column($column_name, $post_id) {
        if ($column_name !== 'kafunel_status') {
            return;
        }

        if (!wp_attachment_is_image($post_id)) {
            echo '-';
            return;
        }

        // Check if this image has been optimized
        global $wpdb;
        $table_name = $wpdb->prefix . 'kafunel_optimizer_logs';
        
        $log_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE attachment_id = %d ORDER BY optimization_date DESC LIMIT 1",
            $post_id
        ));

        if ($log_entry) {
            $savings = $log_entry->original_size - $log_entry->optimized_size;
            $savings_percent = $log_entry->original_size > 0 ? 
                round(($savings / $log_entry->original_size) * 100, 2) : 0;
                
            echo sprintf(
                '<span class="kafunel-optimized" title="%s">%s<br><small>(-%s, %s%%)</small></span>',
                esc_attr__('Optimized with Kafunel', 'kafunel-optimizer-ai'),
                esc_html__('Optimized', 'kafunel-optimizer-ai'),
                size_format($savings),
                number_format_i18n($savings_percent)
            );
        } else {
            $is_pro = defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
            $daily_limit = get_option('kafunel_daily_limit', 10);
            $used_today = get_option('kafunel_used_today', 0);
            
            if (!$is_pro && $used_today >= $daily_limit) {
                echo sprintf(
                    '<span class="kafunel-limit-reached" title="%s">%s</span>',
                    esc_attr__('Daily optimization limit reached', 'kafunel-optimizer-ai'),
                    esc_html__('Limit Reached', 'kafunel-optimizer-ai')
                );
            } else {
                echo sprintf(
                    '<span class="kafunel-not-optimized" title="%s">%s</span>',
                    esc_attr__('Not optimized yet', 'kafunel-optimizer-ai'),
                    esc_html__('Pending', 'kafunel-optimizer-ai')
                );
            }
        }
    }

    /**
     * Get optimization status for an attachment
     */
    public function get_optimization_status($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kafunel_optimizer_logs';
        
        $log_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE attachment_id = %d ORDER BY optimization_date DESC LIMIT 1",
            $post_id
        ), ARRAY_A);

        if ($log_entry) {
            return array(
                'optimized' => true,
                'original_size' => $log_entry['original_size'],
                'optimized_size' => $log_entry['optimized_size'],
                'savings' => $log_entry['original_size'] - $log_entry['optimized_size'],
                'savings_percent' => $log_entry['compression_ratio'],
                'format' => $log_entry['format'],
                'date' => $log_entry['optimization_date']
            );
        }

        return array(
            'optimized' => false,
            'original_size' => 0,
            'optimized_size' => 0,
            'savings' => 0,
            'savings_percent' => 0,
            'format' => '',
            'date' => null
        );
    }

    /**
     * Add optimization status to attachment details modal
     */
    public function add_optimization_details_to_modal($response, $attachment, $meta) {
        if (!wp_attachment_is_image($attachment)) {
            return $response;
        }

        $status = $this->get_optimization_status($attachment->ID);
        
        $response['kafunel_status'] = $status;
        
        if ($status['optimized']) {
            $response['compat']['item'][] = array(
                'label' => __('Kafunel Optimization', 'kafunel-optimizer-ai'),
                'value' => sprintf(
                    __('Optimized: %s → %s (-%s, %s%%)', 'kafunel-optimizer-ai'),
                    size_format($status['original_size']),
                    size_format($status['optimized_size']),
                    size_format($status['savings']),
                    number_format_i18n($status['savings_percent'])
                )
            );
        } else {
            $response['compat']['item'][] = array(
                'label' => __('Kafunel Optimization', 'kafunel-optimizer-ai'),
                'value' => __('Not optimized', 'kafunel-optimizer-ai')
            );
        }

        return $response;
    }
}