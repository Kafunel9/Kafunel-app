<?php
/**
 * Kafunel Optimizer AI - Configuration File
 *
 * Contains configuration constants and settings for the plugin.
 *
 * @package KafunelOptimizerAI
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin identification
define('KAFUNEL_TEAM', 'Kafunel Team');
define('KAFUNEL_SUPPORT_EMAIL', 'support@kafunel.com');
define('KAFUNEL_COMMERCIAL_EMAIL', 'kafunel9@gmail.com');
define('KAFUNEL_PHONE_NUMBERS', array(
    '+221 33 867 42 76',
    '+221 77 541 82 31'
));

// API Providers
define('KAFUNEL_SUPPORTED_PROVIDERS', array(
    'local' => array(
        'name' => 'Local Optimization',
        'description' => 'Uses GD or ImageMagick libraries on your server'
    ),
    'cloudinary' => array(
        'name' => 'Cloudinary',
        'description' => 'Cloud-based image optimization service'
    ),
    'imgix' => array(
        'name' => 'ImgIX',
        'description' => 'Professional image processing platform'
    ),
    'custom' => array(
        'name' => 'Custom API',
        'description' => 'Your own image optimization service'
    )
));

// Supported formats
define('KAFUNEL_SUPPORTED_INPUT_FORMATS', array('jpeg', 'jpg', 'png', 'gif', 'webp'));
define('KAFUNEL_SUPPORTED_OUTPUT_FORMATS', array('jpeg', 'jpg', 'png', 'webp', 'avif'));

// Compression levels
define('KAFUNEL_COMPRESSION_LEVELS', array(
    'lossless' => array(
        'name' => 'Lossless',
        'description' => 'Perfect quality with minimal file size reduction',
        'value' => 100
    ),
    'optimal' => array(
        'name' => 'Optimal',
        'description' => 'Good balance between quality and file size',
        'value' => 85
    ),
    'lossy' => array(
        'name' => 'Lossy',
        'description' => 'Smaller file sizes with noticeable quality loss',
        'value' => 70
    ),
    'maximum' => array(
        'name' => 'Maximum',
        'description' => 'Maximum compression with significant quality loss',
        'value' => 50
    )
));

// Default settings
define('KAFUNEL_DEFAULT_MAX_WIDTH', 1920);
define('KAFUNEL_DEFAULT_MAX_HEIGHT', 1080);
define('KAFUNEL_DEFAULT_DAILY_LIMIT', 10);
define('KAFUNEL_DEFAULT_BATCH_SIZE', 20);

// Pro features availability
define('KAFUNEL_HAS_BACKGROUND_REMOVAL', true);
define('KAFUNEL_HAS_UPSCALE_AI', true);
define('KAFUNEL_HAS_VIDEO_OPTIMIZATION', true);
define('KAFUNEL_HAS_DETAILED_REPORTS', true);
define('KAFUNEL_HAS_PRIORITY_SUPPORT', true);

// Feature limits for free version
define('KAFUNEL_FREE_DAILY_LIMIT', 10);
define('KAFUNEL_FREE_BATCH_LIMIT', 20);
define('KAFUNEL_FREE_WEBP_AVIF_ENABLED', false);

// Security settings
define('KAFUNEL_NONCE_ACTION', 'kafunel_ajax_nonce');
define('KAFUNEL_CAPABILITY_REQUIREMENT', 'manage_options');
define('KAFUNEL_API_TIMEOUT', 60); // seconds

// File size thresholds
define('KAFUNEL_MIN_FILE_SIZE_OPTIMIZE', 1024); // 1KB minimum
define('KAFUNEL_LARGE_FILE_THRESHOLD', 5 * 1024 * 1024); // 5MB threshold for warnings

// Database table names
if (!defined('KAFUNEL_LOG_TABLE')) {
    define('KAFUNEL_LOG_TABLE', $GLOBALS['wpdb']->prefix . 'kafunel_optimizer_logs');
}