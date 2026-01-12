<?php
/**
 * Kafunel Optimizer Engine Class
 *
 * Handles the core optimization functionality for the Kafunel Optimizer AI plugin.
 * Provides both local optimization (GD/ImageMagick) and API-based optimization.
 *
 * @package KafunelOptimizerAI
 * @subpackage Includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Kafunel_Optimizer_Engine {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('add_attachment', array($this, 'auto_optimize_new_attachment'));
        add_action('wp_ajax_kafunel_reset_daily_counter', array($this, 'ajax_reset_daily_counter'));
    }

    /**
     * Auto-optimize new attachments if enabled
     */
    public function auto_optimize_new_attachment($attachment_id) {
        $enabled = get_option('kafunel_enabled', 1);
        
        if (!$enabled) {
            return;
        }

        $mime_type = get_post_mime_type($attachment_id);
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        
        if (!in_array($mime_type, $allowed_types)) {
            return;
        }

        // Only optimize on upload if we're under the daily limit
        if ($this->can_optimize_today()) {
            $this->optimize_attachment($attachment_id);
        }
    }

    /**
     * Optimize a single attachment
     */
    public function optimize_attachment($attachment_id) {
        // Check if we can optimize today
        if (!$this->can_optimize_today()) {
            return false;
        }

        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path || !file_exists($file_path)) {
            return false;
        }

        // Log the optimization attempt
        $this->log_optimization_attempt($attachment_id);

        try {
            // Get original file size
            $original_size = filesize($file_path);

            // Determine optimization method based on settings
            $compression_level = get_option('kafunel_compression_level', 'optimal');
            $output_format = get_option('kafunel_output_format', 'auto');
            $use_api = get_option('kafunel_ai_provider', 'local') !== 'local';

            $optimized_file_path = '';
            
            if ($use_api && !empty(get_option('kafunel_api_key'))) {
                // Use external API for optimization
                $optimized_file_path = $this->optimize_with_ai_api($file_path, $compression_level, $output_format);
            } else {
                // Use local optimization
                $optimized_file_path = $this->optimize_locally($file_path, $compression_level, $output_format);
            }

            if ($optimized_file_path && file_exists($optimized_file_path)) {
                // Replace original file with optimized version
                copy($optimized_file_path, $file_path);
                unlink($optimized_file_path); // Remove temporary optimized file
                
                // Update attachment metadata
                $this->update_attachment_metadata($attachment_id, $file_path);
                
                // Calculate savings
                $new_size = filesize($file_path);
                $savings = $original_size - $new_size;
                $ratio = $original_size > 0 ? round(($savings / $original_size) * 100, 2) : 0;
                
                // Log successful optimization
                $this->log_successful_optimization($attachment_id, $original_size, $new_size, $ratio, $output_format);
                
                // Increment daily usage counter
                $this->increment_daily_usage();
                
                return true;
            } else {
                // Log failed optimization
                $this->log_failed_optimization($attachment_id);
                return false;
            }
        } catch (Exception $e) {
            error_log('Kafunel Optimizer Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Optimize image using local methods (GD or ImageMagick)
     */
    public function optimize_locally($file_path, $compression_level, $output_format) {
        $image_info = getimagesize($file_path);
        if (!$image_info) {
            return false;
        }

        $mime_type = $image_info['mime'];
        $extension = pathinfo($file_path, PATHINFO_EXTENSION);
        
        // Determine output extension
        $output_extension = $this->get_output_extension($extension, $output_format);
        $output_path = $this->generate_output_path($file_path, $output_extension);
        
        // Copy original to temp location
        copy($file_path, $output_path);

        // Select appropriate optimization method based on available libraries
        if (extension_loaded('imagick')) {
            return $this->optimize_with_imagick($file_path, $output_path, $compression_level, $output_format);
        } elseif (extension_loaded('gd')) {
            return $this->optimize_with_gd($file_path, $output_path, $compression_level, $output_format);
        } else {
            // If no optimization libraries available, just copy the file
            copy($file_path, $output_path);
            return $output_path;
        }
    }

    /**
     * Optimize image using ImageMagick
     */
    private function optimize_with_imagick($input_path, $output_path, $compression_level, $output_format) {
        try {
            $image = new Imagick($input_path);
            
            // Apply compression based on level
            switch ($compression_level) {
                case 'lossless':
                    $quality = 100;
                    break;
                case 'optimal':
                    $quality = 85;
                    break;
                case 'lossy':
                    $quality = 70;
                    break;
                case 'maximum':
                    $quality = 50;
                    break;
                default:
                    $quality = 85;
            }
            
            // Handle format conversion
            if ($output_format !== 'auto') {
                $format_map = array(
                    'jpg' => 'jpeg',
                    'png' => 'png',
                    'webp' => 'webp',
                    'avif' => 'avif'
                );
                
                if (isset($format_map[$output_format])) {
                    $image->setImageFormat($format_map[$output_format]);
                }
            }
            
            // Apply compression quality
            if (in_array($image->getImageFormat(), array('jpeg', 'png', 'webp'))) {
                $image->setImageCompressionQuality($quality);
            }
            
            // Auto-orient image based on EXIF data
            $image->autoOrient();
            
            // Strip unnecessary metadata
            $image->stripImage();
            
            // Write optimized image
            $image->writeImage($output_path);
            $image->destroy();
            
            return $output_path;
        } catch (Exception $e) {
            error_log('ImageMagick optimization failed: ' . $e->getMessage());
            // Fallback to copying original if ImageMagick fails
            copy($input_path, $output_path);
            return $output_path;
        }
    }

    /**
     * Optimize image using GD library
     */
    private function optimize_with_gd($input_path, $output_path, $compression_level, $output_format) {
        try {
            $image_info = getimagesize($input_path);
            $mime_type = $image_info['mime'];
            
            // Create image resource based on original format
            switch ($mime_type) {
                case 'image/jpeg':
                    $image = imagecreatefromjpeg($input_path);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($input_path);
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($input_path);
                    break;
                case 'image/webp':
                    $image = imagecreatefromwebp($input_path);
                    break;
                default:
                    return false;
            }
            
            if (!$image) {
                return false;
            }
            
            // Apply compression based on level
            switch ($compression_level) {
                case 'lossless':
                    $quality = 100;
                    break;
                case 'optimal':
                    $quality = 85;
                    break;
                case 'lossy':
                    $quality = 70;
                    break;
                case 'maximum':
                    $quality = 50;
                    break;
                default:
                    $quality = 85;
            }
            
            // Handle format conversion
            $success = false;
            $temp_output_path = $output_path;
            
            // If converting format, update output path
            if ($output_format !== 'auto') {
                $extension_map = array(
                    'jpg' => '.jpg',
                    'png' => '.png',
                    'webp' => '.webp',
                    'avif' => '.avif'
                );
                
                if (isset($extension_map[$output_format])) {
                    $temp_output_path = substr($output_path, 0, strrpos($output_path, '.')) . $extension_map[$output_format];
                }
            }
            
            // Save image with appropriate format and quality
            switch (strtolower(pathinfo($temp_output_path, PATHINFO_EXTENSION))) {
                case 'jpg':
                case 'jpeg':
                    // Preserve transparency for PNG input
                    if ($mime_type === 'image/png') {
                        imagealphablending($image, true);
                        imagesavealpha($image, false);
                    }
                    $success = imagejpeg($image, $temp_output_path, $quality);
                    break;
                    
                case 'png':
                    // Preserve transparency
                    imagealphablending($image, false);
                    imagesavealpha($image, true);
                    
                    // Compression level for PNG (0-9, where 9 is highest compression)
                    $png_compression = 9 - floor($quality / 11);
                    if ($png_compression < 0) $png_compression = 0;
                    if ($png_compression > 9) $png_compression = 9;
                    
                    $success = imagepng($image, $temp_output_path, $png_compression);
                    break;
                    
                case 'webp':
                    // Check if webp is supported
                    if (function_exists('imagewebp')) {
                        $success = imagewebp($image, $temp_output_path, $quality);
                    }
                    break;
                    
                case 'gif':
                    $success = imagegif($image, $temp_output_path);
                    break;
            }
            
            // Clean up
            imagedestroy($image);
            
            if ($success) {
                return $temp_output_path;
            } else {
                return false;
            }
        } catch (Exception $e) {
            error_log('GD optimization failed: ' . $e->getMessage());
            // Fallback to copying original if GD fails
            copy($input_path, $output_path);
            return $output_path;
        }
    }

    /**
     * Optimize image using external AI API
     */
    public function optimize_with_ai_api($file_path, $compression_level, $output_format) {
        $api_key = get_option('kafunel_api_key');
        $provider = get_option('kafunel_ai_provider', 'local');
        
        if (empty($api_key)) {
            return false;
        }
        
        // Prepare file for upload
        $file_data = array(
            'file' => new CURLFile($file_path),
            'compression_level' => $compression_level,
            'output_format' => $output_format
        );
        
        // Set API endpoint based on provider
        $api_endpoint = $this->get_api_endpoint($provider);
        
        if (!$api_endpoint) {
            return false;
        }
        
        // Make API request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $api_key,
            'User-Agent: KafunelOptimizerAI/' . KAFUNEL_OPTIMIZER_AI_VERSION
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 60 second timeout
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            error_log('API request failed: ' . $error);
            return false;
        }
        
        if ($http_code !== 200) {
            error_log('API request failed with HTTP code: ' . $http_code);
            return false;
        }
        
        // Parse response
        $response_data = json_decode($response, true);
        
        if (!$response_data || !isset($response_data['optimized_image'])) {
            error_log('Invalid API response: ' . $response);
            return false;
        }
        
        // Save optimized image to temporary file
        $temp_file = $this->generate_output_path($file_path, pathinfo($file_path, PATHINFO_EXTENSION));
        $decoded_image = base64_decode($response_data['optimized_image']);
        
        if (!$decoded_image) {
            return false;
        }
        
        if (file_put_contents($temp_file, $decoded_image) === false) {
            return false;
        }
        
        return $temp_file;
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
     * Update attachment metadata after optimization
     */
    private function update_attachment_metadata($attachment_id, $file_path) {
        $image_meta = wp_generate_attachment_metadata($attachment_id, $file_path);
        
        if (!empty($image_meta)) {
            wp_update_attachment_metadata($attachment_id, $image_meta);
        }
        
        // Update file size in attachment meta
        update_post_meta($attachment_id, '_wp_attachment_filesize', filesize($file_path));
    }

    /**
     * Generate output path with new extension
     */
    private function generate_output_path($input_path, $extension) {
        $dir = dirname($input_path);
        $filename = basename($input_path, pathinfo($input_path, PATHINFO_EXTENSION));
        
        return trailingslashit($dir) . $filename . '.' . $extension;
    }

    /**
     * Get output extension based on format setting
     */
    private function get_output_extension($original_extension, $output_format) {
        if ($output_format === 'auto') {
            return $original_extension;
        }
        
        $format_extensions = array(
            'jpg' => 'jpg',
            'png' => 'png',
            'webp' => 'webp',
            'avif' => 'avif'
        );
        
        return isset($format_extensions[$output_format]) ? $format_extensions[$output_format] : $original_extension;
    }

    /**
     * Check if we can optimize images today (for free version)
     */
    private function can_optimize_today() {
        $is_pro = defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
        if ($is_pro) {
            return true;
        }
        
        $daily_limit = get_option('kafunel_daily_limit', 10);
        $used_today = get_option('kafunel_used_today', 0);
        
        return $used_today < $daily_limit;
    }

    /**
     * Increment daily usage counter
     */
    private function increment_daily_usage() {
        $is_pro = defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
        if ($is_pro) {
            return;
        }
        
        $used_today = get_option('kafunel_used_today', 0);
        $quota_reset_time = get_option('kafunel_quota_reset_time', date('Y-m-d'));
        
        // Check if we need to reset the counter (new day)
        if ($quota_reset_time !== date('Y-m-d')) {
            $used_today = 0;
            update_option('kafunel_quota_reset_time', date('Y-m-d'));
        }
        
        $used_today++;
        update_option('kafunel_used_today', $used_today);
    }

    /**
     * Log optimization attempt
     */
    private function log_optimization_attempt($attachment_id) {
        // This could be expanded to log to a custom table
        do_action('kafunel_optimization_attempted', $attachment_id);
    }

    /**
     * Log successful optimization
     */
    private function log_successful_optimization($attachment_id, $original_size, $new_size, $ratio, $format) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'kafunel_optimizer_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'attachment_id' => $attachment_id,
                'original_size' => $original_size,
                'optimized_size' => $new_size,
                'compression_ratio' => $ratio,
                'format' => $format
            ),
            array(
                '%d',
                '%d',
                '%d',
                '%f',
                '%s'
            )
        );
        
        do_action('kafunel_optimization_successful', $attachment_id, $original_size, $new_size, $ratio);
    }

    /**
     * Log failed optimization
     */
    private function log_failed_optimization($attachment_id) {
        do_action('kafunel_optimization_failed', $attachment_id);
    }

    /**
     * AJAX handler to reset daily counter
     */
    public function ajax_reset_daily_counter() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'kafunel_reset_counter') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'kafunel-optimizer-ai'));
        }
        
        // Only reset for free version
        $is_pro = defined('KAFUNEL_PRO_VERSION') && KAFUNEL_PRO_VERSION;
        if (!$is_pro) {
            update_option('kafunel_used_today', 0);
            update_option('kafunel_quota_reset_time', date('Y-m-d'));
            wp_send_json_success(__('Daily counter reset successfully', 'kafunel-optimizer-ai'));
        } else {
            wp_send_json_error(__('Counter reset not needed for Pro version', 'kafunel-optimizer-ai'));
        }
    }

    /**
     * Get optimization statistics
     */
    public function get_optimization_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'kafunel_optimizer_logs';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_optimized,
                SUM(original_size) as total_original_size,
                SUM(optimized_size) as total_optimized_size,
                AVG(compression_ratio) as avg_compression_ratio
            FROM {$table_name}
        ", ARRAY_A);
        
        if (!$stats) {
            $stats = array(
                'total_optimized' => 0,
                'total_original_size' => 0,
                'total_optimized_size' => 0,
                'avg_compression_ratio' => 0
            );
        }
        
        // Calculate total savings
        $stats['total_savings'] = $stats['total_original_size'] - $stats['total_optimized_size'];
        $stats['total_savings_percent'] = $stats['total_original_size'] > 0 ? 
            round(($stats['total_savings'] / $stats['total_original_size']) * 100, 2) : 0;
        
        return $stats;
    }
}