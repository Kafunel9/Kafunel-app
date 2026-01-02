<?php
/**
 * Class to handle WhatsApp Business integration
 */
class Kafunel_Whatsapp_Business {

    public function __construct() {
        // Initialize WhatsApp integration
    }

    /**
     * Generate WhatsApp share message for a match
     */
    public function generate_whatsapp_message($match_data) {
        $home_team = isset($match_data['home_team']) ? $match_data['home_team'] : 'Équipe Domicile';
        $away_team = isset($match_data['away_team']) ? $match_data['away_team'] : 'Équipe Extérieur';
        $home_score = isset($match_data['home_score']) ? $match_data['home_score'] : 0;
        $away_score = isset($match_data['away_score']) ? $match_data['away_score'] : 0;
        $league = isset($match_data['league']) ? $match_data['league'] : 'Compétition';
        $status = isset($match_data['status']) ? $match_data['status'] : 'NS';
        $elapsed = isset($match_data['elapsed']) ? $match_data['elapsed'] : 0;
        
        $status_text = $this->get_status_text($status, $elapsed);
        
        $default_message = get_option('kafunel_whatsapp_message', 'Découvrez les scores en direct de vos matchs préférés avec Kafunel Ultimate!');
        
        $message = $default_message . "\n\n";
        $message .= "🏆 " . $league . "\n";
        $message .= "⏱️ " . $status_text . "\n\n";
        $message .= "⚽ " . $home_team . " " . $home_score . " - " . $away_score . " " . $away_team . "\n\n";
        $message .= "Suivez tous les détails sur notre plateforme!";
        
        return $message;
    }

    /**
     * Get status text based on match status
     */
    private function get_status_text($status, $elapsed) {
        switch (strtoupper($status)) {
            case 'NS':
                return 'À venir';
            case '1H':
                return $elapsed . "' - 1ère mi-temps";
            case 'HT':
                return 'Mi-temps';
            case '2H':
                return $elapsed . "' - 2ème mi-temps";
            case 'ET':
                return $elapsed . "' - Prolongations";
            case 'P':
                return $elapsed . "' - Pénaltys";
            case 'FT':
            case 'AET':
            case 'PEN':
                return 'Terminé';
            case 'LIVE':
                return $elapsed . "' - En direct";
            default:
                return $elapsed . "'";
        }
    }

    /**
     * Generate WhatsApp share URL
     */
    public function generate_whatsapp_share_url($match_data) {
        $message = $this->generate_whatsapp_message($match_data);
        $encoded_message = urlencode($message);
        
        return 'https://wa.me/?text=' . $encoded_message;
    }

    /**
     * Send automated WhatsApp notification
     */
    public function send_match_notification($match_id, $notification_type) {
        // In a real implementation, you would integrate with WhatsApp Business API
        // For now, we'll just log the notification
        
        $api_manager = new Kafunel_API_Manager();
        $match_data = $api_manager->get_match_data($match_id);
        
        if (!$match_data) {
            return false;
        }
        
        $whatsapp_number = get_option('kafunel_whatsapp_number', '');
        
        if (empty($whatsapp_number)) {
            error_log('WhatsApp Business number not configured');
            return false;
        }
        
        $message = $this->generate_notification_message($match_data, $notification_type);
        
        // Log the notification attempt
        error_log('WhatsApp notification prepared: ' . $message);
        
        // In a real implementation, you would call the WhatsApp Business API here
        // $this->call_whatsapp_business_api($whatsapp_number, $message);
        
        return true;
    }

    /**
     * Generate notification message based on notification type
     */
    private function generate_notification_message($match_data, $notification_type) {
        $home_team = isset($match_data['teams']['home']['name']) ? $match_data['teams']['home']['name'] : 'Équipe Domicile';
        $away_team = isset($match_data['teams']['away']['name']) ? $match_data['teams']['away']['name'] : 'Équipe Extérieur';
        $home_score = isset($match_data['goals']['home']) ? $match_data['goals']['home'] : 0;
        $away_score = isset($match_data['goals']['away']) ? $match_data['goals']['away'] : 0;
        $league = isset($match_data['league']['name']) ? $match_data['league']['name'] : 'Compétition';
        
        switch ($notification_type) {
            case 'match_start':
                return "⚽ Le match commence maintenant!\n" . $home_team . " vs " . $away_team . "\nCompétition: " . $league;
            case 'goal':
                return "🔥 BUT! Nouveau score: " . $home_team . " " . $home_score . " - " . $away_score . " " . $away_team;
            case 'half_time':
                return "⏸️ Fin de la première mi-temps\n" . $home_team . " " . $home_score . " - " . $away_score . " " . $away_team;
            case 'full_time':
                return "✅ Match terminé!\n" . $home_team . " " . $home_score . " - " . $away_score . " " . $away_team;
            case 'red_card':
                return "🟥 Carton rouge dans le match " . $home_team . " vs " . $away_team;
            default:
                return "Mise à jour: " . $home_team . " " . $home_score . " - " . $away_score . " " . $away_team;
        }
    }

    /**
     * Prepare share content for social media
     */
    public function prepare_share_content($match_data) {
        $content = array();
        
        // WhatsApp ready text
        $content['whatsapp'] = $this->generate_whatsapp_message($match_data);
        
        // Facebook share content
        $content['facebook'] = array(
            'quote' => "Découvrez le match en direct: " . 
                      (isset($match_data['home_team']) ? $match_data['home_team'] : 'Équipe Domicile') . " " .
                      (isset($match_data['home_score']) ? $match_data['home_score'] : '0') . " - " .
                      (isset($match_data['away_score']) ? $match_data['away_score'] : '0') . " " .
                      (isset($match_data['away_team']) ? $match_data['away_team'] : 'Équipe Extérieur'),
            'url' => home_url()
        );
        
        // Twitter/X share content
        $content['twitter'] = array(
            'text' => "Live: " . 
                     (isset($match_data['home_team']) ? $match_data['home_team'] : 'Équipe Domicile') . " " .
                     (isset($match_data['home_score']) ? $match_data['home_score'] : '0') . " - " .
                     (isset($match_data['away_score']) ? $match_data['away_score'] : '0') . " " .
                     (isset($match_data['away_team']) ? $match_data['away_team'] : 'Équipe Extérieur') . " #KafunelLive",
            'url' => home_url()
        );
        
        return $content;
    }
}