/**
 * Kafunel Optimizer AI - Admin Scripts
 */

jQuery(document).ready(function($) {
    // Handle API key visibility toggle
    $('#kafunel_api_key').after('<button type="button" id="toggle-api-key" class="button-secondary">Show</button>');
    
    $('#toggle-api-key').on('click', function() {
        var $apiKeyField = $('#kafunel_api_key');
        var $btn = $(this);
        
        if ($apiKeyField.attr('type') === 'password') {
            $apiKeyField.attr('type', 'text');
            $btn.text('Hide');
        } else {
            $apiKeyField.attr('type', 'password');
            $btn.text('Show');
        }
    });
    
    // Handle compression level change
    $('#kafunel_compression_level').on('change', function() {
        var selectedLevel = $(this).val();
        var $infoBox = $('#compression-info');
        
        if ($infoBox.length === 0) {
            $(this).parent().append('<p id="compression-info" class="description"></p>');
            $infoBox = $('#compression-info');
        }
        
        var descriptions = {
            'lossless': 'Perfect quality with minimal file size reduction. Best for high-quality requirements.',
            'optimal': 'Good balance between quality and file size. Recommended for most websites.',
            'lossy': 'Smaller file sizes with noticeable quality loss. Good for web use.',
            'maximum': 'Maximum compression with significant quality loss. Best for thumbnails.'
        };
        
        $infoBox.text(descriptions[selectedLevel] || '');
    });
    
    // Handle output format change
    $('#kafunel_output_format').on('change', function() {
        var selectedFormat = $(this).val();
        var $infoBox = $('#format-info');
        
        if ($infoBox.length === 0) {
            $(this).parent().append('<p id="format-info" class="description"></p>');
            $infoBox = $('#format-info');
        }
        
        var descriptions = {
            'auto': 'Keep original format unless conversion is specifically needed.',
            'jpg': 'JPEG format, good for photos and complex images with many colors.',
            'png': 'PNG format, best for graphics, logos, and images with transparency.',
            'webp': 'Modern format with excellent compression. Supported by most browsers.',
            'avif': 'Latest format with superior compression. Limited browser support.'
        };
        
        $infoBox.text(descriptions[selectedFormat] || '');
    });
    
    // Enable/disable WebP/AVIF options based on plan
    var isPro = $('.pro-badge').length > 0;
    if (!isPro) {
        $('#kafunel_webp_support, #kafunel_avif_support').prop('disabled', true).parent().fadeTo(0.5, 0.5);
    }
    
    // Handle resize settings
    $('#kafunel_auto_resize').on('change', function() {
        var isChecked = $(this).is(':checked');
        if (isChecked) {
            $('.kafunel-resize-settings').show();
        } else {
            $('.kafunel-resize-settings').hide();
        }
    }).trigger('change'); // Trigger on page load
    
    // Initialize tooltips
    $('.kafunel-tooltip').tooltip({
        content: function() {
            return $(this).attr('title');
        }
    });
});

// Utility function for formatting file sizes
function kafunelFormatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}