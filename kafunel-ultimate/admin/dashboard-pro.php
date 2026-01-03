<?php
/**
 * Dashboard Pro Page for Kafunel Ultimate
 * 
 * This file displays the main dashboard for the Kafunel Ultimate plugin.
 * 
 * @package Kafunel_Ultimate
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'kafunel-ultimate'));
}

// Include necessary dependencies
require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'includes/class-api-manager.php';
require_once KAFUNEL_ULTIMATE_PLUGIN_DIR . 'includes/class-live-match.php';

// Initialize the API manager
$api_manager = new Kafunel_API_Manager(
    get_option('kafunel_api_key', ''),
    get_option('kafunel_api_secret', ''),
    get_option('kafunel_api_base_url', 'https://api-football-v1.p.rapidapi.com/v3')
);

// Initialize the live match handler
$live_match = new Kafunel_Live_Match($api_manager);

// Get dashboard statistics
$stats = array(
    'total_matches' => get_option('kafunel_total_matches', 0),
    'live_matches' => count($live_match->get_live_matches()),
    'total_users' => count_users()['total_users'],
    'plugin_version' => KAFUNEL_ULTIMATE_VERSION
);

?>

<div class="wrap">
    <h1><?php _e('Kafunel Ultimate Dashboard', 'kafunel-ultimate'); ?></h1>
    
    <div class="kafunel-dashboard-container">
        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3><?php _e('Total Matches', 'kafunel-ultimate'); ?></h3>
                <p class="stat-number"><?php echo $stats['total_matches']; ?></p>
            </div>
            <div class="stat-card">
                <h3><?php _e('Live Matches', 'kafunel-ultimate'); ?></h3>
                <p class="stat-number"><?php echo $stats['live_matches']; ?></p>
            </div>
            <div class="stat-card">
                <h3><?php _e('Total Users', 'kafunel-ultimate'); ?></h3>
                <p class="stat-number"><?php echo $stats['total_users']; ?></p>
            </div>
            <div class="stat-card">
                <h3><?php _e('Plugin Version', 'kafunel-ultimate'); ?></h3>
                <p class="stat-number"><?php echo $stats['plugin_version']; ?></p>
            </div>
        </div>
        
        <!-- Recent Matches -->
        <div class="recent-matches">
            <h2><?php _e('Recent Matches', 'kafunel-ultimate'); ?></h2>
            <div id="kafunel-recent-matches-container">
                <?php
                $recent_matches = $live_match->get_recent_matches(5);
                if (!empty($recent_matches)) {
                    echo '<ul class="kafunel-matches-list">';
                    foreach ($recent_matches as $match) {
                        $status = isset($match['status']) ? $match['status']['long'] : 'Not started';
                        $home_team = isset($match['teams']['home']['name']) ? $match['teams']['home']['name'] : 'Unknown';
                        $away_team = isset($match['teams']['away']['name']) ? $match['teams']['away']['name'] : 'Unknown';
                        $home_score = isset($match['score']['fulltime']['home']) ? $match['score']['fulltime']['home'] : '-';
                        $away_score = isset($match['score']['fulltime']['away']) ? $match['score']['fulltime']['away'] : '-';
                        
                        echo '<li class="match-item">';
                        echo '<span class="match-teams">' . esc_html($home_team) . ' vs ' . esc_html($away_team) . '</span>';
                        echo '<span class="match-score">' . $home_score . ' - ' . $away_score . '</span>';
                        echo '<span class="match-status">' . esc_html($status) . '</span>';
                        echo '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>' . __('No recent matches available.', 'kafunel-ultimate') . '</p>';
                }
                ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><?php _e('Quick Actions', 'kafunel-ultimate'); ?></h2>
            <div class="actions-grid">
                <a href="<?php echo admin_url('admin.php?page=kafunel-settings'); ?>" class="action-card">
                    <div class="action-icon">⚙️</div>
                    <h3><?php _e('Settings', 'kafunel-ultimate'); ?></h3>
                    <p><?php _e('Configure plugin settings', 'kafunel-ultimate'); ?></p>
                </a>
                
                <a href="#" class="action-card">
                    <div class="action-icon">🔄</div>
                    <h3><?php _e('Sync Data', 'kafunel-ultimate'); ?></h3>
                    <p><?php _e('Synchronize with API', 'kafunel-ultimate'); ?></p>
                </a>
                
                <a href="#" class="action-card">
                    <div class="action-icon">📊</div>
                    <h3><?php _e('Reports', 'kafunel-ultimate'); ?></h3>
                    <p><?php _e('View detailed reports', 'kafunel-ultimate'); ?></p>
                </a>
                
                <a href="#" class="action-card">
                    <div class="action-icon">💬</div>
                    <h3><?php _e('Support', 'kafunel-ultimate'); ?></h3>
                    <p><?php _e('Get plugin support', 'kafunel-ultimate'); ?></p>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.kafunel-dashboard-container {
    margin-top: 20px;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #f8f9f9;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #646970;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    margin: 0;
    color: #1d2327;
}

.recent-matches {
    margin-bottom: 30px;
}

.kafunel-matches-list {
    list-style: none;
    padding: 0;
}

.match-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    border-bottom: 1px solid #ccd0d4;
}

.match-teams, .match-score, .match-status {
    flex: 1;
    text-align: center;
}

.quick-actions h2 {
    margin-bottom: 20px;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.action-card {
    display: block;
    background: #f8f9f9;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    text-decoration: none;
    color: #1d2327;
    transition: all 0.3s ease;
}

.action-card:hover {
    background: #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.action-icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.action-card h3 {
    margin: 10px 0 5px 0;
    font-size: 16px;
    color: #1d2327;
}

.action-card p {
    margin: 0;
    font-size: 13px;
    color: #646970;
}
</style>