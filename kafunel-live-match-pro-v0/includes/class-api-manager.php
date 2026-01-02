<?php
/**
 * Class to manage API calls with fallback functionality
 */
class Kafunel_API_Manager {

    private $api_football_key;
    private $rapidapi_key;
    private $api_football_url = 'https://v3.football.api-sports.io/';
    private $rapidapi_url = 'https://football98.p.rapidapi.com/';
    
    public function __construct() {
        $this->api_football_key = get_option('kafunel_api_football_key', '');
        $this->rapidapi_key = get_option('kafunel_rapidapi_key', '');
    }

    /**
     * Get match data with fallback mechanism
     */
    public function get_match_data($match_id) {
        // First try API-Football
        $data = $this->get_from_api_football($match_id);
        
        if ($data && $this->is_valid_response($data)) {
            return $data;
        }
        
        // Fallback to RapidAPI
        $data = $this->get_from_rapidapi($match_id);
        
        if ($data && $this->is_valid_response($data)) {
            return $data;
        }
        
        // If no API keys are configured, return demo data
        if (empty($this->api_football_key) && empty($this->rapidapi_key)) {
            return $this->get_demo_data($match_id);
        }
        
        return false;
    }

    /**
     * Get data from API-Football
     */
    private function get_from_api_football($match_id) {
        if (empty($this->api_football_key)) {
            return false;
        }
        
        $url = $this->api_football_url . 'fixtures/id/' . $match_id;
        
        $headers = array(
            'x-apisports-key: ' . $this->api_football_key,
            'Content-Type: application/json'
        );
        
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            error_log('API-Football Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Get data from RapidAPI
     */
    private function get_from_rapidapi($match_id) {
        if (empty($this->rapidapi_key)) {
            return false;
        }
        
        $url = $this->rapidapi_url . 'fixtures/get-by-id?id=' . $match_id;
        
        $headers = array(
            'X-RapidAPI-Key: ' . $this->rapidapi_key,
            'X-RapidAPI-Host: football98.p.rapidapi.com',
            'Content-Type: application/json'
        );
        
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            error_log('RapidAPI Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    /**
     * Get demo data when no API keys are configured
     */
    private function get_demo_data($match_id) {
        return array(
            'fixture' => array(
                'id' => $match_id,
                'date' => date('Y-m-d H:i:s'),
                'status' => array(
                    'long' => 'Live',
                    'short' => 'LIVE',
                    'elapsed' => 75
                )
            ),
            'league' => array(
                'name' => 'CAN 2025',
                'country' => 'Afrique',
                'logo' => 'https://example.com/can-logo.png'
            ),
            'teams' => array(
                'home' => array(
                    'id' => 1,
                    'name' => 'Sénégal',
                    'logo' => 'https://example.com/senegal-logo.png'
                ),
                'away' => array(
                    'id' => 2,
                    'name' => 'Côte d\'Ivoire',
                    'logo' => 'https://example.com/cotedivoire-logo.png'
                )
            ),
            'goals' => array(
                'home' => 2,
                'away' => 1
            ),
            'score' => array(
                'halftime' => array('home' => 1, 'away' => 0),
                'fulltime' => array('home' => 2, 'away' => 1),
                'extratime' => null,
                'penalty' => null
            ),
            'events' => array(
                array(
                    'time' => array('elapsed' => 23),
                    'team' => 1,
                    'player' => array('name' => 'Sadio Mané'),
                    'type' => 'Goal',
                    'detail' => 'Goal'
                ),
                array(
                    'time' => array('elapsed' => 45),
                    'team' => 2,
                    'player' => array('name' => 'Seri'),
                    'type' => 'Goal',
                    'detail' => 'Goal'
                ),
                array(
                    'time' => array('elapsed' => 67),
                    'team' => 1,
                    'player' => array('name' => 'Ismaïla Sarr'),
                    'type' => 'Goal',
                    'detail' => 'Goal'
                )
            ),
            'lineups' => array(
                array(
                    'team' => 1,
                    'formation' => '4-2-3-1',
                    'startXI' => array(
                        array('player' => array('name' => 'Édouard Mendy', 'number' => 16)),
                        array('player' => array('name' => 'Kalidou Koulibaly', 'number' => 3)),
                        array('player' => array('name' => 'Pape Gueye', 'number' => 14)),
                        array('player' => array('name' => 'Sadio Mané', 'number' => 10)),
                        array('player' => array('name' => 'Ismaïla Sarr', 'number' => 19)),
                    )
                ),
                array(
                    'team' => 2,
                    'formation' => '4-3-3',
                    'startXI' => array(
                        array('player' => array('name' => 'Simon Adingra', 'number' => 16)),
                        array('player' => array('name' => 'Seri', 'number' => 6)),
                        array('player' => array('name' => 'Zaha', 'number' => 11)),
                    )
                )
            )
        );
    }

    /**
     * Check if the response is valid
     */
    private function is_valid_response($data) {
        if (!is_array($data) || empty($data)) {
            return false;
        }
        
        // Check for API-specific error indicators
        if (isset($data['errors']) && !empty($data['errors'])) {
            return false;
        }
        
        if (isset($data['response']) && empty($data['response'])) {
            return false;
        }
        
        return true;
    }

    /**
     * Get live matches
     */
    public function get_live_matches() {
        // Try API-Football first
        $data = $this->get_live_from_api_football();
        
        if ($data && $this->is_valid_response($data)) {
            return $data;
        }
        
        // Fallback to RapidAPI
        $data = $this->get_live_from_rapidapi();
        
        if ($data && $this->is_valid_response($data)) {
            return $data;
        }
        
        // Demo data if no API keys
        if (empty($this->api_football_key) && empty($this->rapidapi_key)) {
            return $this->get_demo_live_data();
        }
        
        return false;
    }

    private function get_live_from_api_football() {
        if (empty($this->api_football_key)) {
            return false;
        }
        
        $url = $this->api_football_url . 'fixtures?live=all';
        
        $headers = array(
            'x-apisports-key: ' . $this->api_football_key,
            'Content-Type: application/json'
        );
        
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    private function get_live_from_rapidapi() {
        if (empty($this->rapidapi_key)) {
            return false;
        }
        
        $url = $this->rapidapi_url . 'fixtures/live';
        
        $headers = array(
            'X-RapidAPI-Key: ' . $this->rapidapi_key,
            'X-RapidAPI-Host: football98.p.rapidapi.com',
            'Content-Type: application/json'
        );
        
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    private function get_demo_live_data() {
        return array(
            'response' => array(
                array(
                    'fixture' => array(
                        'id' => 1,
                        'date' => date('Y-m-d H:i:s'),
                        'status' => array(
                            'long' => 'Live',
                            'short' => 'LIVE',
                            'elapsed' => 75
                        )
                    ),
                    'league' => array(
                        'name' => 'CAN 2025',
                        'country' => 'Afrique',
                        'logo' => 'https://example.com/can-logo.png'
                    ),
                    'teams' => array(
                        'home' => array(
                            'id' => 1,
                            'name' => 'Sénégal',
                            'logo' => 'https://example.com/senegal-logo.png'
                        ),
                        'away' => array(
                            'id' => 2,
                            'name' => 'Côte d\'Ivoire',
                            'logo' => 'https://example.com/cotedivoire-logo.png'
                        )
                    ),
                    'goals' => array(
                        'home' => 2,
                        'away' => 1
                    )
                )
            )
        );
    }
}