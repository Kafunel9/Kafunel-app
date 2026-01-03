<?php
/**
 * Class to manage API calls with fallback functionality
 */
class Kafunel_API_Manager {

    private $api_football_key;
    private $rapidapi_key;
    private $api_football_url = 'https://v3.football.api-sports.io/';
    private $rapidapi_url = 'https://football98.p.rapidapi.com/';
    private $logger;
    private $cache_manager;
    private $validator;
    
    public function __construct($logger = null, $cache_manager = null, $validator = null) {
        $this->api_football_key = get_option('kafunel_api_football_key', '');
        $this->rapidapi_key = get_option('kafunel_rapidapi_key', '');
        
        // Utiliser les utilitaires du plugin principal s'ils sont disponibles
        $main_plugin = $this->get_main_plugin_instance();
        if ($main_plugin) {
            $this->logger = $main_plugin->get_logger();
            $this->cache_manager = $main_plugin->get_cache_manager();
        } else {
            $this->logger = $logger ?: new Kafunel_Logger();
            $this->cache_manager = $cache_manager ?: new Kafunel_Cache_Manager($this->logger);
        }
        
        $this->validator = $validator ?: new Kafunel_Validator();
    }

    /**
     * Get main plugin instance
     */
    private function get_main_plugin_instance() {
        global $kafunel_ultimate_instance;
        return $kafunel_ultimate_instance ?? null;
    }

    /**
     * Get match data with fallback mechanism
     */
    public function get_match_data($match_id) {
        try {
            // Validation
            if (!$this->validator->validate_match_id($match_id)) {
                $this->logger->warning('Invalid match ID provided', array('match_id' => $match_id));
                return false;
            }
            
            // Try cache first
            $cache_key = "match_{$match_id}";
            $cached_data = $this->cache_manager->get($cache_key, 'api');
            
            if ($cached_data !== null) {
                $this->logger->debug("Cache hit for match: {$match_id}");
                return $cached_data;
            }
            
            // First try API-Football
            $data = $this->get_from_api_football($match_id);
            
            if ($data && $this->is_valid_response($data)) {
                // Cache the data with smart TTL based on match status
                $this->cache_manager->set_with_smart_ttl($cache_key, $data, $this->get_data_type_for_match($data), 'api');
                return $data;
            }
            
            // Fallback to RapidAPI
            $data = $this->get_from_rapidapi($match_id);
            
            if ($data && $this->is_valid_response($data)) {
                // Cache the data
                $this->cache_manager->set_with_smart_ttl($cache_key, $data, $this->get_data_type_for_match($data), 'api');
                return $data;
            }
            
            // If no API keys are configured, return demo data
            if (empty($this->api_football_key) && empty($this->rapidapi_key)) {
                $demo_data = $this->get_demo_data($match_id);
                // Cache demo data for shorter period
                $this->cache_manager->set($cache_key, $demo_data, 300, 'api'); // 5 minutes
                return $demo_data;
            }
            
            return false;
        } catch (Kafunel_Exception $e) {
            $this->logger->error('API Manager error: ' . $e->getMessage(), $e->get_error_details());
            return false;
        } catch (Exception $e) {
            $this->logger->error('Unexpected error in API Manager: ' . $e->getMessage(), array(
                'match_id' => $match_id
            ));
            return false;
        }
    }

    /**
     * Get data from API-Football
     */
    private function get_from_api_football($match_id) {
        if (empty($this->api_football_key)) {
            $this->logger->debug('API-Football key not configured');
            return false;
        }
        
        $cache_key = $this->cache_manager->generate_api_cache_key('api_football_match', array('id' => $match_id));
        $cached_response = $this->cache_manager->get($cache_key, 'api');
        
        if ($cached_response !== null) {
            $this->logger->debug("API-Football cache hit for match: {$match_id}");
            return $cached_response;
        }
        
        $url = $this->api_football_url . 'fixtures/id/' . $match_id;
        
        $headers = array(
            'x-apisports-key: ' . $this->api_football_key,
            'Content-Type: application/json'
        );
        
        $this->logger->info("Calling API-Football for match: {$match_id}", array('url' => $url));
        
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 15,
            'user-agent' => 'Kafunel Ultimate Plugin'
        ));
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->error('API-Football Error: ' . $error_message, array(
                'match_id' => $match_id,
                'error_code' => $response->get_error_code()
            ));
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            $this->logger->warning('API-Football returned non-200 status', array(
                'match_id' => $match_id,
                'status_code' => $status_code,
                'response_body' => $body
            ));
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('API-Football returned invalid JSON', array(
                'match_id' => $match_id,
                'json_error' => json_last_error_msg()
            ));
            return false;
        }
        
        // Cache the response
        $this->cache_manager->set_with_smart_ttl($cache_key, $data, 'api_response', 'api');
        
        return $data;
    }

    /**
     * Get data from RapidAPI
     */
    private function get_from_rapidapi($match_id) {
        if (empty($this->rapidapi_key)) {
            $this->logger->debug('RapidAPI key not configured');
            return false;
        }
        
        $cache_key = $this->cache_manager->generate_api_cache_key('rapidapi_match', array('id' => $match_id));
        $cached_response = $this->cache_manager->get($cache_key, 'api');
        
        if ($cached_response !== null) {
            $this->logger->debug("RapidAPI cache hit for match: {$match_id}");
            return $cached_response;
        }
        
        $url = $this->rapidapi_url . 'fixtures/get-by-id?id=' . $match_id;
        
        $headers = array(
            'X-RapidAPI-Key: ' . $this->rapidapi_key,
            'X-RapidAPI-Host: football98.p.rapidapi.com',
            'Content-Type: application/json'
        );
        
        $this->logger->info("Calling RapidAPI for match: {$match_id}", array('url' => $url));
        
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 15,
            'user-agent' => 'Kafunel Ultimate Plugin'
        ));
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->error('RapidAPI Error: ' . $error_message, array(
                'match_id' => $match_id,
                'error_code' => $response->get_error_code()
            ));
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            $this->logger->warning('RapidAPI returned non-200 status', array(
                'match_id' => $match_id,
                'status_code' => $status_code,
                'response_body' => $body
            ));
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('RapidAPI returned invalid JSON', array(
                'match_id' => $match_id,
                'json_error' => json_last_error_msg()
            ));
            return false;
        }
        
        // Cache the response
        $this->cache_manager->set_with_smart_ttl($cache_key, $data, 'api_response', 'api');
        
        return $data;
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
            $this->logger->warning('API returned errors', array('errors' => $data['errors']));
            return false;
        }
        
        if (isset($data['response']) && empty($data['response'])) {
            $this->logger->warning('API returned empty response array');
            return false;
        }
        
        return true;
    }

    /**
     * Get live matches
     */
    public function get_live_matches() {
        try {
            // Try cache first
            $cache_key = 'live_matches';
            $cached_data = $this->cache_manager->get($cache_key, 'api');
            
            if ($cached_data !== null) {
                $this->logger->debug("Cache hit for live matches");
                return $cached_data;
            }
            
            // Try API-Football first
            $data = $this->get_live_from_api_football();
            
            if ($data && $this->is_valid_response($data)) {
                // Cache live matches for shorter period
                $this->cache_manager->set($cache_key, $data, 60, 'api'); // 1 minute
                return $data;
            }
            
            // Fallback to RapidAPI
            $data = $this->get_live_from_rapidapi();
            
            if ($data && $this->is_valid_response($data)) {
                // Cache live matches for shorter period
                $this->cache_manager->set($cache_key, $data, 60, 'api'); // 1 minute
                return $data;
            }
            
            // Demo data if no API keys
            if (empty($this->api_football_key) && empty($this->rapidapi_key)) {
                $demo_data = $this->get_demo_live_data();
                // Cache demo data for shorter period
                $this->cache_manager->set($cache_key, $demo_data, 300, 'api'); // 5 minutes
                return $demo_data;
            }
            
            return false;
        } catch (Kafunel_Exception $e) {
            $this->logger->error('API Manager error getting live matches: ' . $e->getMessage(), $e->get_error_details());
            return false;
        } catch (Exception $e) {
            $this->logger->error('Unexpected error in API Manager getting live matches: ' . $e->getMessage());
            return false;
        }
    }

    private function get_live_from_api_football() {
        if (empty($this->api_football_key)) {
            $this->logger->debug('API-Football key not configured for live matches');
            return false;
        }
        
        $cache_key = $this->cache_manager->generate_api_cache_key('api_football_live');
        $cached_response = $this->cache_manager->get($cache_key, 'api');
        
        if ($cached_response !== null) {
            $this->logger->debug("API-Football live matches cache hit");
            return $cached_response;
        }
        
        $url = $this->api_football_url . 'fixtures?live=all';
        
        $headers = array(
            'x-apisports-key: ' . $this->api_football_key,
            'Content-Type: application/json'
        );
        
        $this->logger->info("Calling API-Football for live matches", array('url' => $url));
        
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 15,
            'user-agent' => 'Kafunel Ultimate Plugin'
        ));
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->error('API-Football Error getting live matches: ' . $error_message, array(
                'error_code' => $response->get_error_code()
            ));
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            $this->logger->warning('API-Football returned non-200 status for live matches', array(
                'status_code' => $status_code,
                'response_body' => $body
            ));
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('API-Football returned invalid JSON for live matches', array(
                'json_error' => json_last_error_msg()
            ));
            return false;
        }
        
        // Cache the response
        $this->cache_manager->set($cache_key, $data, 60, 'api'); // 1 minute for live data
        
        return $data;
    }

    private function get_live_from_rapidapi() {
        if (empty($this->rapidapi_key)) {
            $this->logger->debug('RapidAPI key not configured for live matches');
            return false;
        }
        
        $cache_key = $this->cache_manager->generate_api_cache_key('rapidapi_live');
        $cached_response = $this->cache_manager->get($cache_key, 'api');
        
        if ($cached_response !== null) {
            $this->logger->debug("RapidAPI live matches cache hit");
            return $cached_response;
        }
        
        $url = $this->rapidapi_url . 'fixtures/live';
        
        $headers = array(
            'X-RapidAPI-Key: ' . $this->rapidapi_key,
            'X-RapidAPI-Host: football98.p.rapidapi.com',
            'Content-Type: application/json'
        );
        
        $this->logger->info("Calling RapidAPI for live matches", array('url' => $url));
        
        $response = wp_remote_get($url, array(
            'headers' => $headers,
            'timeout' => 15,
            'user-agent' => 'Kafunel Ultimate Plugin'
        ));
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->logger->error('RapidAPI Error getting live matches: ' . $error_message, array(
                'error_code' => $response->get_error_code()
            ));
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            $this->logger->warning('RapidAPI returned non-200 status for live matches', array(
                'status_code' => $status_code,
                'response_body' => $body
            ));
            return false;
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('RapidAPI returned invalid JSON for live matches', array(
                'json_error' => json_last_error_msg()
            ));
            return false;
        }
        
        // Cache the response
        $this->cache_manager->set($cache_key, $data, 60, 'api'); // 1 minute for live data
        
        return $data;
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
    
    /**
     * Determine data type for smart caching based on match status
     */
    private function get_data_type_for_match($match_data) {
        if (isset($match_data['fixture']['status']['short'])) {
            $status = strtoupper($match_data['fixture']['status']['short']);
            
            if (in_array($status, ['LIVE', '1H', '2H', 'ET', 'P'])) {
                return 'live_match';  // Cache for 1 minute
            } else {
                return 'match_result'; // Cache for 1 hour
            }
        }
        
        return 'default';
    }
}