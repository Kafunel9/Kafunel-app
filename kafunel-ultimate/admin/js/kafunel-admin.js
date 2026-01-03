/**
 * Admin JavaScript for Kafunel Ultimate
 * 
 * This file contains the JavaScript functionality for the admin interface of the Kafunel Ultimate plugin.
 * 
 * @package Kafunel_Ultimate
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize dashboard functionality
    initDashboard();
    
    /**
     * Initialize dashboard functionality
     */
    function initDashboard() {
        // Add any dashboard-specific JavaScript here
        console.log('Kafunel Ultimate Dashboard loaded');
        
        // Auto-refresh live matches if needed
        maybeRefreshLiveMatches();
    }
    
    /**
     * Refresh live matches if needed
     */
    function maybeRefreshLiveMatches() {
        // Check if we're on the dashboard page
        if ($('.kafunel-dashboard-container').length > 0) {
            // Auto-refresh every 60 seconds
            setInterval(function() {
                refreshLiveMatches();
            }, 60000);
        }
    }
    
    /**
     * Refresh live matches
     */
    function refreshLiveMatches() {
        // Placeholder for live match refresh functionality
        console.log('Refreshing live matches...');
    }
    
    /**
     * Handle quick action clicks
     */
    $('.action-card').on('click', function(e) {
        var action = $(this).data('action');
        if (action === 'sync-data') {
            e.preventDefault();
            syncDataWithAPI();
        }
    });
    
    /**
     * Sync data with API
     */
    function syncDataWithAPI() {
        // Show loading indicator
        var $syncButton = $('.action-card[data-action="sync-data"]');
        var originalText = $syncButton.find('h3').text();
        $syncButton.find('h3').text('Syncing...');
        
        // Make AJAX request to sync data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kafunel_sync_data',
                nonce: kafunel_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update dashboard stats
                    updateDashboardStats();
                    console.log('Data synced successfully');
                } else {
                    console.error('Error syncing data:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            },
            complete: function() {
                // Restore original text
                $syncButton.find('h3').text(originalText);
            }
        });
    }
    
    /**
     * Update dashboard stats
     */
    function updateDashboardStats() {
        // Make AJAX request to get updated stats
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'kafunel_get_dashboard_stats',
                nonce: kafunel_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update stat numbers
                    if (response.data.total_matches !== undefined) {
                        $('.stat-number').eq(0).text(response.data.total_matches);
                    }
                    if (response.data.live_matches !== undefined) {
                        $('.stat-number').eq(1).text(response.data.live_matches);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('Error updating stats:', error);
            }
        });
    }
    
    // Add any additional admin functionality here
});

// Export global object for other scripts to use
var kafunel_admin = {
    version: '1.0.0',
    nonce: typeof kafunel_ajax !== 'undefined' ? kafunel_ajax.nonce : ''
};