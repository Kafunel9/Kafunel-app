<?php
/**
 * Class to handle AI-powered match analysis
 */
class Kafunel_AI_Analysis {

    public function __construct() {
        // Initialize AI analysis features
    }

    /**
     * Generate match summary using AI
     */
    public function generate_match_summary($match_data) {
        if (empty($match_data)) {
            return 'Données de match indisponibles.';
        }

        $home_team = isset($match_data['teams']['home']['name']) ? $match_data['teams']['home']['name'] : 'Équipe Domicile';
        $away_team = isset($match_data['teams']['away']['name']) ? $match_data['teams']['away']['name'] : 'Équipe Extérieur';
        $home_score = isset($match_data['goals']['home']) ? $match_data['goals']['home'] : 0;
        $away_score = isset($match_data['goals']['away']) ? $match_data['goals']['away'] : 0;
        $league = isset($match_data['league']['name']) ? $match_data['league']['name'] : 'Compétition';
        $events = isset($match_data['events']) ? $match_data['events'] : array();
        
        $summary = "📊 Résumé du match - " . $league . "\n\n";
        $summary .= $home_team . " " . $home_score . " - " . $away_score . " " . $away_team . "\n\n";
        
        if (!empty($events)) {
            $goals = array_filter($events, function($event) {
                return isset($event['type']) && strtoupper($event['type']) === 'GOAL';
            });
            
            $cards = array_filter($events, function($event) {
                return isset($event['type']) && in_array(strtoupper($event['type']), ['CARD', 'YELLOW', 'RED']);
            });
            
            if (!empty($goals)) {
                $summary .= "🔥 Buts marqués:\n";
                foreach ($goals as $goal) {
                    if (isset($goal['time']['elapsed']) && isset($goal['player']['name'])) {
                        $team_name = $this->get_team_name_by_id($goal['team'], $match_data);
                        $summary .= "  " . $goal['time']['elapsed'] . "' " . $goal['player']['name'] . " (" . $team_name . ")\n";
                    }
                }
                $summary .= "\n";
            }
            
            if (!empty($cards)) {
                $summary .= "⚠️ Cartons:\n";
                foreach ($cards as $card) {
                    if (isset($card['time']['elapsed']) && isset($card['player']['name'])) {
                        $team_name = $this->get_team_name_by_id($card['team'], $match_data);
                        $summary .= "  " . $card['time']['elapsed'] . "' " . $card['player']['name'] . " (" . $team_name . ")\n";
                    }
                }
                $summary .= "\n";
            }
        }
        
        $winner = $this->determine_match_winner($home_score, $away_score, $home_team, $away_team);
        $summary .= "🏆 Résultat: " . $winner;
        
        return $summary;
    }

    /**
     * Generate minute-by-minute commentary
     */
    public function generate_commentary($match_data) {
        if (empty($match_data) || empty($match_data['events'])) {
            return array();
        }

        $events = $match_data['events'];
        $commentary = array();
        
        // Sort events by time
        usort($events, function($a, $b) {
            return $a['time']['elapsed'] - $b['time']['elapsed'];
        });
        
        foreach ($events as $event) {
            if (isset($event['time']['elapsed']) && isset($event['player']['name'])) {
                $minute = $event['time']['elapsed'];
                $player = $event['player']['name'];
                $type = isset($event['type']) ? $event['type'] : '';
                $team_id = isset($event['team']) ? $event['team'] : 0;
                
                $team_name = $this->get_team_name_by_id($team_id, $match_data);
                
                switch (strtoupper($type)) {
                    case 'GOAL':
                        $commentary[] = $minute . "' ⚽ BUT! " . $player . " marque pour " . $team_name . "!";
                        break;
                    case 'YELLOW':
                    case 'CARD':
                        $commentary[] = $minute . "' ⚠️ Carton jaune pour " . $player . " (" . $team_name . ")";
                        break;
                    case 'RED':
                        $commentary[] = $minute . "' 🟥 Carton rouge pour " . $player . " (" . $team_name . ")!";
                        break;
                    case 'SUBSTITUTION':
                        $commentary[] = $minute . "' 🔄 Remplacement: " . $player . " entre pour " . $team_name;
                        break;
                    default:
                        $commentary[] = $minute . "' " . $type . " - " . $player . " (" . $team_name . ")";
                }
            }
        }
        
        return $commentary;
    }

    /**
     * Generate match preview
     */
    public function generate_match_preview($match_data) {
        if (empty($match_data)) {
            return 'Données de match indisponibles.';
        }

        $home_team = isset($match_data['teams']['home']['name']) ? $match_data['teams']['home']['name'] : 'Équipe Domicile';
        $away_team = isset($match_data['teams']['away']['name']) ? $match_data['teams']['away']['name'] : 'Équipe Extérieur';
        $league = isset($match_data['league']['name']) ? $match_data['league']['name'] : 'Compétition';
        $match_date = isset($match_data['fixture']['date']) ? $match_data['fixture']['date'] : '';
        
        $preview = "🔮 Aperçu du match à venir\n\n";
        $preview .= "🏆 Compétition: " . $league . "\n";
        $preview .= "📅 Date: " . date('d/m/Y H:i', strtotime($match_date)) . "\n\n";
        $preview .= "🆚 Affrontement: " . $home_team . " vs " . $away_team . "\n\n";
        $preview .= "Prédictions: Ce match promet d'être intense avec deux équipes de qualité. " .
                    $home_team . " jouera à domicile et tentera de tirer profit de son avantage local, " .
                    "tandis que " . $away_team . " viendra avec l'objectif de repartir avec un résultat positif.";
        
        return $preview;
    }

    /**
     * Analyze team performance
     */
    public function analyze_team_performance($match_data) {
        if (empty($match_data)) {
            return array();
        }

        $analysis = array();
        
        // Analyze possession if available
        if (isset($match_data['statistics'])) {
            $analysis['possession'] = $this->analyze_possession($match_data['statistics']);
        }
        
        // Analyze shots if available
        if (isset($match_data['statistics'])) {
            $analysis['shots'] = $this->analyze_shots($match_data['statistics']);
        }
        
        // Analyze other stats
        $analysis['key_moments'] = $this->identify_key_moments($match_data);
        $analysis['player_performance'] = $this->identify_top_performers($match_data);
        
        return $analysis;
    }

    /**
     * Helper method to get team name by ID
     */
    private function get_team_name_by_id($team_id, $match_data) {
        if (empty($match_data) || !isset($match_data['teams'])) {
            return 'Équipe Inconnue';
        }
        
        $teams = $match_data['teams'];
        
        if (isset($teams['home']['id']) && $teams['home']['id'] == $team_id) {
            return $teams['home']['name'];
        } elseif (isset($teams['away']['id']) && $teams['away']['id'] == $team_id) {
            return $teams['away']['name'];
        }
        
        // If IDs don't match, try to match by position in events
        if (isset($match_data['fixture']['teams'])) {
            $fixture_teams = $match_data['fixture']['teams'];
            if (isset($fixture_teams['home']['id']) && $fixture_teams['home']['id'] == $team_id) {
                return $fixture_teams['home']['name'];
            } elseif (isset($fixture_teams['away']['id']) && $fixture_teams['away']['id'] == $team_id) {
                return $fixture_teams['away']['name'];
            }
        }
        
        return 'Équipe';
    }

    /**
     * Determine match winner
     */
    private function determine_match_winner($home_score, $away_score, $home_team, $away_team) {
        if ($home_score > $away_score) {
            return $home_team . " gagne le match!";
        } elseif ($away_score > $home_score) {
            return $away_team . " gagne le match!";
        } else {
            return "Match nul entre " . $home_team . " et " . $away_team . "!";
        }
    }

    /**
     * Analyze possession statistics
     */
    private function analyze_possession($statistics) {
        $possession = array();
        
        foreach ($statistics as $stat) {
            if (isset($stat['type']) && strtolower($stat['type']) === 'possession') {
                $possession = $stat['value'];
                break;
            }
        }
        
        if (!empty($possession)) {
            $home_possession = $possession[0]['value'];
            $away_possession = $possession[1]['value'];
            
            return array(
                'home' => $home_possession,
                'away' => $away_possession,
                'analysis' => 'Possession dominée par ' . ($home_possession > $away_possession ? 'l\'équipe domicile' : 'l\'équipe extérieure')
            );
        }
        
        return array();
    }

    /**
     * Analyze shots statistics
     */
    private function analyze_shots($statistics) {
        $shots = array();
        
        foreach ($statistics as $stat) {
            if (isset($stat['type']) && strtolower($stat['type']) === 'shots') {
                $shots = $stat['value'];
                break;
            }
        }
        
        if (!empty($shots)) {
            $home_shots = $shots[0]['value'];
            $away_shots = $shots[1]['value'];
            
            return array(
                'home' => $home_shots,
                'away' => $away_shots,
                'analysis' => 'Tirs cadrés: ' . ($home_shots > $away_shots ? 'Avantage domicile' : 'Avantage extérieur')
            );
        }
        
        return array();
    }

    /**
     * Identify key moments in the match
     */
    private function identify_key_moments($match_data) {
        $key_moments = array();
        $events = isset($match_data['events']) ? $match_data['events'] : array();
        
        foreach ($events as $event) {
            if (isset($event['type']) && in_array(strtoupper($event['type']), ['GOAL', 'RED', 'PENALTY'])) {
                $key_moments[] = array(
                    'minute' => isset($event['time']['elapsed']) ? $event['time']['elapsed'] : 0,
                    'type' => $event['type'],
                    'player' => isset($event['player']['name']) ? $event['player']['name'] : '',
                    'team' => $this->get_team_name_by_id($event['team'], $match_data)
                );
            }
        }
        
        return $key_moments;
    }

    /**
     * Identify top performing players
     */
    private function identify_top_performers($match_data) {
        $events = isset($match_data['events']) ? $match_data['events'] : array();
        $player_stats = array();
        
        foreach ($events as $event) {
            if (isset($event['player']['name'])) {
                $player_name = $event['player']['name'];
                
                if (!isset($player_stats[$player_name])) {
                    $player_stats[$player_name] = array(
                        'goals' => 0,
                        'cards' => 0,
                        'team' => $this->get_team_name_by_id($event['team'], $match_data)
                    );
                }
                
                if (isset($event['type'])) {
                    switch (strtoupper($event['type'])) {
                        case 'GOAL':
                            $player_stats[$player_name]['goals']++;
                            break;
                        case 'YELLOW':
                        case 'RED':
                        case 'CARD':
                            $player_stats[$player_name]['cards']++;
                            break;
                    }
                }
            }
        }
        
        // Sort by goals first, then by cards
        uasort($player_stats, function($a, $b) {
            if ($a['goals'] == $b['goals']) {
                return $b['cards'] - $a['cards']; // More cards = less favorable
            }
            return $b['goals'] - $a['goals']; // More goals = better
        });
        
        return array_slice($player_stats, 0, 5); // Top 5 performers
    }
}