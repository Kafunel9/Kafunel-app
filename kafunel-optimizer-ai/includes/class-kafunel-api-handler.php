<?php
/**
 * Kafunel Optimizer API Handler Class
 *
 * Handles external API integration for the Kafunel Optimizer AI plugin.
 *
 * @package KafunelOptimizerAI
 * @subpackage Includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kafunel_Optimizer_API_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_ajax_kafunel_test_api_connection', array($this, 'ajax_test_api_connection'));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Optimize single image
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
                ),
                'compression_level' => array(
                    'required' => false,
                    'validate_callback' => function($param, $request, $key) {
                        $valid_levels = array('lossless', 'optimal', 'lossy', 'maximum');
                        return in_array($param, $valid_levels);
                    }
                ),
                'output_format' => array(
                    'required' => false,
                    'validate_callback' => function($param, $request, $key) {
                        $valid_formats = array('auto', 'jpg', 'png', 'webp', 'avif');
                        return in_array($param, $valid_formats);
                    }
                )
            )
        ));

        // Bulk optimize
        register_rest_route('kafunel-optimizer/v1', '/bulk-optimize', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_bulk_optimize'),
            'permission_callback' => array($this, 'rest_permission_check'),
            'args' => array(
                'ids' => array(
                    'required' => true,
                    'validate_callback' => function($param, $request, $key) {
                        return is_array($param) && count($param) > 0;
                    }
                ),
                'compression_level' => array(
                    'required' => false,
                    'validate_callback' => function($param, $request, $key) {
                        $valid_levels = array('lossless', 'optimal', 'lossy', 'maximum');
                        return in_array($param, $valid_levels);
                    }
                ),
                'output_format' => array(
                    'required' => false,
                    'validate_callback' => function($param, $request, $key) {
                        $valid_formats = array('auto', 'jpg', 'png', 'webp', 'avif');
                        return in_array($param, $valid_formats);
                    }
                )
            )
        ));

        // Get optimization stats
        register_rest_route('kafunel-optimizer/v1', '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_stats'),
            'permission_callback' => array($this, 'rest_permission_check')
        ));

        // Test API connection
        register_rest_route('kafunel-optimizer/v1', '/test-api', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_test_api_connection'),
            'permission_callback' => array($this, 'rest_permission_check')
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
        $compression_level = $request->get_param('compression_level');
        $output_format = $request->get_param('output_format');

        // Validate post ID
        if (!$post_id || !get_post($post_id) || !wp_attachment_is_image($post_id)) {
            return new WP_Error('invalid_post', __('Invalid image attachment', 'kafunel-optimizer-ai'), array('status' => 400));
        }

        // Override settings if provided
        if ($compression_level) {
            update_option('kafunel_compression_level_override', $compression_level);
        }
        if ($output_format) {
            update_option('kafunel_output_format_override', $output_format);
        }

        // Perform optimization
        $engine = new Kafunel_Optimizer_Engine();
        $result = $engine->optimize_attachment($post_id);

        // Clean up overrides
        delete_option('kafunel_compression_level_override');
        delete_option('kafunel_output_format_override');

        if ($result) {
            $status = new Kafunel_Optimizer_Media_Integration();
            $optimization_status = $status->get_optimization_status($post_id);
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('Image optimized successfully', 'kafunel-optimizer-ai'),
                'post_id' => $post_id,
                'status' => $optimization_status
            ), 200);
        } else {
            return new WP_Error('optimization_failed', __('Failed to optimize image', 'kafunel-optimizer-ai'), array('status' => 500));
        }
    }

    /**
     * REST API bulk optimize handler
     */
    public function rest_bulk_optimize($request) {
        $post_ids = $request->get_param('ids');
        $compression_level = $request->get_param('compression_level');
        $output_format = $request->get_param('output_format');

        // Validate post IDs
        foreach ($post_ids as $post_id) {
            if (!$post_id || !get_post($post_id) || !wp_attachment_is_image($post_id)) {
                return new WP_Error('invalid_post', sprintf(__('Invalid image attachment: %d', 'kafunel-optimizer-ai'), $post_id), array('status' => 400));
            }
        }

        // Override settings if provided
        if ($compression_level) {
            update_option('kafunel_compression_level_override', $compression_level);
        }
        if ($output_format) {
            update_option('kafunel_output_format_override', $output_format);
        }

        // Perform optimizations
        $engine = new Kafunel_Optimizer_Engine();
        $results = array();
        $success_count = 0;
        $failed_count = 0;

        foreach ($post_ids as $post_id) {
            $result = $engine->optimize_attachment($post_id);
            
            if ($result) {
                $success_count++;
                $results[$post_id] = array(
                    'status' => 'success',
                    'message' => __('Optimized', 'kafunel-optimizer-ai')
                );
            } else {
                $failed_count++;
                $results[$post_id] = array(
                    'status' => 'error',
                    'message' => __('Failed', 'kafunel-optimizer-ai')
                );
            }
        }

        // Clean up overrides
        delete_option('kafunel_compression_level_override');
        delete_option('kafunel_output_format_override');

        return new WP_REST_Response(array(
            'success' => true,
            'message' => sprintf(
                __('Bulk optimization completed: %d successful, %d failed', 'kafunel-optimizer-ai'),
                $success_count,
                $failed_count
            ),
            'results' => $results,
            'summary' => array(
                'total' => count($post_ids),
                'successful' => $success_count,
                'failed' => $failed_count
            )
        ), 200);
    }

    /**
     * REST API get stats handler
     */
    public function rest_get_stats($request) {
        $engine = new Kafunel_Optimizer_Engine();
        $stats = $engine->get_optimization_stats();

        return new WP_REST_Response(array(
            'success' => true,
            'stats' => $stats,
            'usage' => array(
                'used_today' => get_option('kafunel_used_today', 0),
                'daily_limit' => get_option('kafunel_daily_limit', 10),
                'is_pro' => defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION
            )
        ), 200);
    }

    /**
     * REST API test API connection handler
     */
    public function rest_test_api_connection($request) {
        $api_key = get_option('kafunel_api_key');
        $provider = get_option('kafunel_ai_provider', 'local');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('No API key configured', 'kafunel-optimizer-ai'), array('status' => 400));
        }

        if ($provider === 'local') {
            return new WP_Error('local_provider', __('Local optimization does not require API connection', 'kafunel-optimizer-ai'), array('status' => 400));
        }

        // Test API connection with a simple request
        $test_result = $this->test_api_connection($api_key, $provider);

        if ($test_result) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => __('API connection successful', 'kafunel-optimizer-ai'),
                'provider' => $provider
            ), 200);
        } else {
            return new WP_Error('api_connection_failed', __('API connection failed', 'kafunel-optimizer-ai'), array('status' => 500));
        }
    }

    /**
     * Test API connection
     */
    private function test_api_connection($api_key, $provider) {
        $api_endpoint = $this->get_api_endpoint($provider);
        
        if (!$api_endpoint) {
            return false;
        }

        // Create a small dummy image for testing
        $dummy_image = $this->create_dummy_image();
        if (!$dummy_image) {
            return false;
        }

        $file_data = array(
            'file' => new CURLFile($dummy_image),
            'compression_level' => 'optimal',
            'output_format' => 'auto'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $api_key,
            'User-Agent: KafunelOptimizerAI/' . KAFUNEL_OPTIMIZER_AI_VERSION
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Clean up dummy image
        unlink($dummy_image);

        if ($error) {
            error_log('API test failed: ' . $error);
            return false;
        }

        return $http_code >= 200 && $http_code < 300;
    }

    /**
     * Create a dummy image for API testing
     */
    private function create_dummy_image() {
        $image = imagecreate(100, 100);
        $bg_color = imagecolorallocate($image, 255, 255, 255); // White background
        $text_color = imagecolorallocate($image, 0, 0, 0); // Black text
        
        imagestring($image, 5, 30, 40, 'TEST', $text_color);
        
        $temp_file = tempnam(sys_get_temp_dir(), 'kafunel_test_');
        $temp_file .= '.jpg';
        
        $result = imagejpeg($image, $temp_file, 85);
        imagedestroy($image);
        
        if ($result) {
            return $temp_file;
        }
        
        return false;
    }

    /**
     * Get API endpoint URL based on provider
     */
    private function get_api_endpoint($provider) {
        $endpoints = array(
            'cloudinary' => 'https://api.cloudinary.com/v1_1/kafunel/image/upload',
            'imgix' => 'https://api.imgix.com/api/v1/optimize',
            'custom' => apply_filters('kafunel_custom_api_endpoint', '')
        );
        
        return isset($endpoints[$provider]) ? $endpoints[$provider] : false;
    }

    /**
     * AJAX handler to test API connection
     */
    public function ajax_test_api_connection() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'kafunel_ajax_nonce') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'kafunel-optimizer-ai'));
        }

        $api_key = get_option('kafunel_api_key');
        $provider = get_option('kafunel_ai_provider', 'local');

        if (empty($api_key)) {
            wp_send_json_error(__('No API key configured', 'kafunel-optimizer-ai'));
        }

        if ($provider === 'local') {
            wp_send_json_error(__('Local optimization does not require API connection', 'kafunel-optimizer-ai'));
        }

        // Test API connection
        $test_result = $this->test_api_connection($api_key, $provider);

        if ($test_result) {
            wp_send_json_success(__('API connection successful', 'kafunel-optimizer-ai'));
        } else {
            wp_send_json_error(__('API connection failed', 'kafunel-optimizer-ai'));
        }
    }

    /**
     * Validate API key format
     */
    public function validate_api_key($api_key) {
        // Basic validation - can be expanded based on provider requirements
        if (empty($api_key)) {
            return false;
        }

        // Remove any whitespace
        $api_key = trim($api_key);

        // Basic format check (length, characters)
        if (strlen($api_key) < 10) {
            return false;
        }

        return true;
    }

    /**
     * Format API response
     */
    public function format_api_response($data, $success = true, $message = '') {
        return array(
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'timestamp' => current_time('mysql')
        );
    }

    /**
     * Log API requests
     */
    public function log_api_request($endpoint, $method, $response_code, $duration = null) {
        $log_entry = array(
            'endpoint' => $endpoint,
            'method' => $method,
            'response_code' => $response_code,
            'request_time' => current_time('mysql'),
            'duration' => $duration
        );

        // Store in WordPress options or custom table
        $logs = get_option('kafunel_api_logs', array());
        $logs[] = $log_entry;

        // Keep only the last 100 logs
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }

        update_option('kafunel_api_logs', $logs);
    }
}