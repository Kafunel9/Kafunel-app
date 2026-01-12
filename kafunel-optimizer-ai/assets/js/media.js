/**
 * Kafunel Optimizer AI - Media Library Scripts
 */

jQuery(document).ready(function($) {
    // Handle optimize button clicks in media grid view
    $(document).on('click', '.kafunel-optimize-btn', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var postId = $btn.data('id');
        
        if (!postId) {
            console.error('No post ID found');
            return;
        }
        
        // Show loading state
        var originalText = $btn.text();
        $btn.text('Optimizing...').addClass('kafunel-loading').prop('disabled', true);
        
        // Make AJAX request
        $.post(ajaxurl, {
            action: 'kafunel_optimize_image',
            post_id: postId,
            nonce: kafunel_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                $btn.text('Optimized!').removeClass('kafunel-loading').prop('disabled', false);
                
                // Add success indicator
                $btn.after('<span class="kafunel-success-indicator">✓</span>');
                
                // Update status badge if it exists
                var $statusBadge = $btn.closest('.attachment').find('.kafunel-status-badge');
                if ($statusBadge.length) {
                    $statusBadge.text('Optimized').css('background', 'rgba(0, 163, 42, 0.7)');
                } else {
                    $btn.parent().append('<span class="kafunel-status-badge" style="background: rgba(0, 163, 42, 0.7)">Optimized</span>');
                }
                
                // Update the media item display after a short delay
                setTimeout(function() {
                    if (typeof wp !== 'undefined' && wp.media && wp.media.frame) {
                        // Refresh the media frame if open
                        wp.media.frame.content.mode('browse');
                    } else {
                        // Standard refresh for media library page
                        location.reload();
                    }
                }, 1000);
            } else {
                $btn.text(originalText).removeClass('kafunel-loading').prop('disabled', false);
                alert(response.data.message || 'Failed to optimize image');
            }
        })
        .fail(function() {
            $btn.text(originalText).removeClass('kafunel-loading').prop('disabled', false);
            alert('An error occurred while optimizing the image');
        });
    });
    
    // Handle bulk optimize action
    $(document).on('click', '[id^="doaction"], [id^="doaction2"]', function(e) {
        var action = $(this).closest('form').find('select[name^="action"]').val();
        
        if (action === 'kafunel_optimize') {
            e.preventDefault();
            
            var $submitBtn = $(this);
            var $form = $(this).closest('form');
            var actionName = $(this).attr('id') === 'doaction2' ? 'action2' : 'action';
            
            // Get selected attachment IDs
            var postIds = [];
            $form.find('input[name="media[]"]:checked').each(function() {
                postIds.push(parseInt($(this).val()));
            });
            
            if (postIds.length === 0) {
                alert('Please select at least one image to optimize');
                return;
            }
            
            // Confirm bulk action
            if (!confirm(`Optimize ${postIds.length} selected images with Kafunel?`)) {
                return;
            }
            
            // Show loading state
            var originalText = $submitBtn.text();
            $submitBtn.text('Optimizing...').prop('disabled', true);
            
            // Make AJAX request
            $.post(ajaxurl, {
                action: 'kafunel_bulk_optimize',
                post_ids: postIds,
                nonce: kafunel_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    alert(`${response.data.success_count} of ${postIds.length} images optimized successfully`);
                    
                    // Refresh the page to show updated statuses
                    location.reload();
                } else {
                    alert(response.data.message || 'Bulk optimization failed');
                }
            })
            .fail(function() {
                alert('An error occurred during bulk optimization');
            })
            .always(function() {
                // Restore button state
                $submitBtn.text(originalText).prop('disabled', false);
            });
        }
    });
    
    // Add optimize buttons to media grid items
    function addOptimizeButtons() {
        $('.attachments .attachment').each(function() {
            var $attachment = $(this);
            var postId = $attachment.find('.attachment-preview').data('id');
            
            if (postId && !$attachment.find('.kafunel-optimize-btn').length) {
                // Add optimize button overlay
                var optimizeBtn = $('<div class="kafunel-optimize-overlay"><button type="button" class="button kafunel-optimize-btn" data-id="' + postId + '">Optimize</button></div>');
                $attachment.append(optimizeBtn);
            }
        });
    }
    
    // Monitor for changes in media grid
    if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    addOptimizeButtons();
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Initial button addition
    addOptimizeButtons();
    
    // Handle media modal optimization
    $(document).on('click', '.kafunel-optimize-single', function(e) {
        e.preventDefault();
        
        var postId = $(this).data('id');
        var $btn = $(this);
        
        $btn.addClass('kafunel-loading').find('.dashicons').addClass('kafunel-spinner');
        
        $.post(ajaxurl, {
            action: 'kafunel_optimize_image',
            post_id: postId,
            nonce: kafunel_ajax.nonce
        })
        .done(function(response) {
            if (response.success) {
                $btn.removeClass('kafunel-loading').find('.dashicons').removeClass('kafunel-spinner');
                
                // Update UI with optimization results
                if (response.data.status && response.data.status.optimized) {
                    var savings = response.data.status.savings_percent.toFixed(2) + '% smaller';
                    $btn.after('<span class="kafunel-success">Optimized (' + savings + ')</span>');
                    $btn.hide();
                }
            }
        })
        .fail(function() {
            $btn.removeClass('kafunel-loading').find('.dashicons').removeClass('kafunel-spinner');
            alert('Failed to optimize image');
        });
    });
});

// Utility function to format file sizes
window.kafunelFormatFileSize = function(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};