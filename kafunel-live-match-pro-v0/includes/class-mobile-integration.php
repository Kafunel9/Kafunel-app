<?php
/**
 * Class to handle mobile app integration via REST API
 */
class Kafunel_Mobile_Integration {

    public function __construct() {
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Get live matches
        register_rest_route('kafunel/v1', '/live-matches', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_live_matches'),
            'permission_callback' => '__return_true',
        ));

        // Get specific match
        register_rest_route('kafunel/v1', '/match/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_match'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));

        // Get matches by league
        register_rest_route('kafunel/v1', '/matches/(?P<league_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_matches_by_league'),
            'permission_callback' => '__return_true',
            'args' => array(
                'league_id' => array(
                    'validate_callback' => function($param, $request, $key) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }

    /**
     * Get live matches
     */
    public function get_live_matches($request) {
        $api_manager = new Kafunel_API_Manager();
        $raw_data = $api_manager->get_live_matches();
        
        if (!$raw_data) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Impossible de récupérer les données des matchs',
                'matches' => array()
            ), 200);
        }

        $formatted_matches = array();
        
        if (isset($raw_data['response']) && is_array($raw_data['response'])) {
            foreach ($raw_data['response'] as $match) {
                $formatted_matches[] = $this->format_match_for_mobile($match);
            }
        } else {
            // If it's demo data, format it directly
            $formatted_matches[] = $this->format_match_for_mobile($raw_data);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'matches' => $formatted_matches
        ), 200);
    }

    /**
     * Get specific match by ID
     */
    public function get_match($request) {
        $match_id = $request->get_param('id');
        
        $api_manager = new Kafunel_API_Manager();
        $raw_data = $api_manager->get_match_data($match_id);
        
        if (!$raw_data) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Match non trouvé'
            ), 404);
        }

        $formatted_match = $this->format_match_for_mobile($raw_data);

        return new WP_REST_Response(array(
            'success' => true,
            'match' => $formatted_match
        ), 200);
    }

    /**
     * Get matches by league
     */
    public function get_matches_by_league($request) {
        $league_id = $request->get_param('league_id');
        
        // In a real implementation, you would fetch matches for a specific league
        // For now, we'll return demo data or all live matches
        $api_manager = new Kafunel_API_Manager();
        $raw_data = $api_manager->get_live_matches();
        
        if (!$raw_data) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Impossible de récupérer les données des matchs',
                'matches' => array()
            ), 200);
        }

        $formatted_matches = array();
        
        if (isset($raw_data['response']) && is_array($raw_data['response'])) {
            foreach ($raw_data['response'] as $match) {
                // Filter by league if needed
                $formatted_matches[] = $this->format_match_for_mobile($match);
            }
        }

        return new WP_REST_Response(array(
            'success' => true,
            'matches' => $formatted_matches
        ), 200);
    }

    /**
     * Format match data for mobile app consumption
     */
    private function format_match_for_mobile($raw_match) {
        // Extract basic match info
        $fixture = isset($raw_match['fixture']) ? $raw_match['fixture'] : array();
        $league = isset($raw_match['league']) ? $raw_match['league'] : array();
        $teams = isset($raw_match['teams']) ? $raw_match['teams'] : array();
        $goals = isset($raw_match['goals']) ? $raw_match['goals'] : array();
        $events = isset($raw_match['events']) ? $raw_match['events'] : array();
        $lineups = isset($raw_match['lineups']) ? $raw_match['lineups'] : array();
        $status = isset($fixture['status']) ? $fixture['status'] : array();
        
        // Format events
        $formatted_events = array();
        if (!empty($events)) {
            foreach ($events as $event) {
                if (isset($event['time']['elapsed']) && isset($event['player']['name'])) {
                    $formatted_events[] = array(
                        'minute' => $event['time']['elapsed'],
                        'type' => isset($event['type']) ? $event['type'] : 'Event',
                        'player' => $event['player']['name'],
                        'team' => $this->get_team_side_by_id($event['team'], $teams)
                    );
                }
            }
        }
        
        // Format lineups
        $formatted_lineups = array();
        if (!empty($lineups)) {
            foreach ($lineups as $lineup) {
                $team_side = $this->get_team_side_by_id($lineup['team'], $teams);
                
                $formatted_lineups[$team_side] = array(
                    'formation' => isset($lineup['formation']) ? $lineup['formation'] : 'Formation inconnue',
                    'players' => array()
                );
                
                if (isset($lineup['startXI']) && is_array($lineup['startXI'])) {
                    foreach ($lineup['startXI'] as $player) {
                        if (isset($player['player']['name'])) {
                            $formatted_lineups[$team_side]['players'][] = array(
                                'number' => isset($player['player']['number']) ? $player['player']['number'] : 0,
                                'name' => $player['player']['name']
                            );
                        }
                    }
                }
            }
        }
        
        // Determine if match is live
        $is_live = isset($status['short']) && in_array(strtoupper($status['short']), ['LIVE', '1H', '2H', 'ET', 'P']);
        
        return array(
            'id' => isset($fixture['id']) ? $fixture['id'] : 0,
            'homeTeam' => isset($teams['home']['name']) ? $teams['home']['name'] : 'Équipe Domicile',
            'awayTeam' => isset($teams['away']['name']) ? $teams['away']['name'] : 'Équipe Extérieur',
            'homeScore' => isset($goals['home']) ? $goals['home'] : 0,
            'awayScore' => isset($goals['away']) ? $goals['away'] : 0,
            'status' => isset($status['short']) ? $status['short'] : 'NS',
            'elapsed' => isset($status['elapsed']) ? $status['elapsed'] : 0,
            'league' => isset($league['name']) ? $league['name'] : 'Compétition',
            'isLive' => $is_live,
            'events' => $formatted_events,
            'homeFormation' => isset($formatted_lineups['home']['formation']) ? $formatted_lineups['home']['formation'] : 'N/A',
            'awayFormation' => isset($formatted_lineups['away']['formation']) ? $formatted_lineups['away']['formation'] : 'N/A',
            'homePlayers' => isset($formatted_lineups['home']['players']) ? $formatted_lineups['home']['players'] : array(),
            'awayPlayers' => isset($formatted_lineups['away']['players']) ? $formatted_lineups['away']['players'] : array(),
            'date' => isset($fixture['date']) ? $fixture['date'] : ''
        );
    }

    /**
     * Helper to determine team side (home/away) by ID
     */
    private function get_team_side_by_id($team_id, $teams) {
        if (isset($teams['home']['id']) && $teams['home']['id'] == $team_id) {
            return 'home';
        } elseif (isset($teams['away']['id']) && $teams['away']['id'] == $team_id) {
            return 'away';
        }
        
        // If IDs don't match, try to determine by position in the data
        return 'home'; // Default to home if uncertain
    }

    /**
     * Prepare response for mobile app
     */
    public function prepare_mobile_response($data, $success = true, $message = '') {
        $response = array(
            'success' => $success,
            'data' => $data
        );
        
        if (!empty($message)) {
            $response['message'] = $message;
        }
        
        return new WP_REST_Response($response, $success ? 200 : 400);
    }
}