<?php
/**
 * Class to handle live match functionality
 */
class Kafunel_Live_Match {

    private $api_manager;

    public function __construct() {
        $this->api_manager = new Kafunel_API_Manager();
        
        // Hook for AJAX calls
        add_action('wp_ajax_kafunel_update_match_data', array($this, 'handle_ajax_update_match_data'));
        add_action('wp_ajax_nopriv_kafunel_update_match_data', array($this, 'handle_ajax_update_match_data'));
    }

    /**
     * Handle AJAX request to update match data
     */
    public function handle_ajax_update_match_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kafunel_nonce')) {
            wp_die('Security check failed');
        }

        $match_id = intval($_POST['match_id']);
        
        if (!$match_id) {
            wp_send_json_error('Invalid match ID');
        }

        $match_data = $this->api_manager->get_match_data($match_id);
        
        if (!$match_data) {
            wp_send_json_error('Could not retrieve match data');
        }

        // Extract the relevant data to send back
        $response = array(
            'fixture' => isset($match_data['fixture']) ? $match_data['fixture'] : array(),
            'league' => isset($match_data['league']) ? $match_data['league'] : array(),
            'teams' => isset($match_data['teams']) ? $match_data['teams'] : array(),
            'goals' => isset($match_data['goals']) ? $match_data['goals'] : array(),
            'score' => isset($match_data['score']) ? $match_data['score'] : array(),
            'events' => isset($match_data['events']) ? $match_data['events'] : array(),
            'lineups' => isset($match_data['lineups']) ? $match_data['lineups'] : array(),
            'status' => isset($match_data['fixture']['status']) ? $match_data['fixture']['status'] : array()
        );

        wp_send_json_success($response);
    }

    /**
     * Get formatted match data for display
     */
    public function get_formatted_match_data($match_id) {
        $match_data = $this->api_manager->get_match_data($match_id);
        
        if (!$match_data) {
            return false;
        }

        return array(
            'id' => $match_id,
            'home_team' => isset($match_data['teams']['home']['name']) ? $match_data['teams']['home']['name'] : 'Équipe Domicile',
            'away_team' => isset($match_data['teams']['away']['name']) ? $match_data['teams']['away']['name'] : 'Équipe Extérieur',
            'home_logo' => isset($match_data['teams']['home']['logo']) ? $match_data['teams']['home']['logo'] : '',
            'away_logo' => isset($match_data['teams']['away']['logo']) ? $match_data['teams']['away']['logo'] : '',
            'home_score' => isset($match_data['goals']['home']) ? $match_data['goals']['home'] : 0,
            'away_score' => isset($match_data['goals']['away']) ? $match_data['goals']['away'] : 0,
            'status' => isset($match_data['fixture']['status']['short']) ? $match_data['fixture']['status']['short'] : 'NS',
            'elapsed' => isset($match_data['fixture']['status']['elapsed']) ? $match_data['fixture']['status']['elapsed'] : 0,
            'league' => isset($match_data['league']['name']) ? $match_data['league']['name'] : 'Compétition',
            'events' => isset($match_data['events']) ? $match_data['events'] : array(),
            'lineups' => isset($match_data['lineups']) ? $match_data['lineups'] : array(),
            'date' => isset($match_data['fixture']['date']) ? $match_data['fixture']['date'] : ''
        );
    }

    /**
     * Format time for display
     */
    public function format_match_time($elapsed, $status) {
        if (in_array(strtoupper($status), ['FT', 'AET', 'PEN'])) {
            return 'Terminé';
        }
        
        if (in_array(strtoupper($status), ['HT', 'BT'])) {
            return 'Mi-temps';
        }
        
        if ($elapsed > 90 && $elapsed <= 120) {
            return $elapsed . "' (Temps additionnel)";
        } elseif ($elapsed > 120) {
            return $elapsed . "' (Pénaltys)";
        }
        
        return $elapsed . "'";
    }
}