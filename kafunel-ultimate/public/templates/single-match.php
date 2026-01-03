<?php
/**
 * Template for displaying a single live match
 */
if (!defined('ABSPATH')) {
    die;
}

// Get match ID from shortcode attributes or default to a demo match
$match_id = !empty($atts['match_id']) ? intval($atts['match_id']) : 1;

// Initialize API manager
$api_manager = new Kafunel_API_Manager();
$match_data = $api_manager->get_match_data($match_id);

if (!$match_data) {
    echo '<div class="kafunel-error">Impossible de récupérer les données du match. Veuillez vérifier vos paramètres API.</div>';
    return;
}

// Extract match information
$fixture = isset($match_data['fixture']) ? $match_data['fixture'] : array();
$league = isset($match_data['league']) ? $match_data['league'] : array();
$teams = isset($match_data['teams']) ? $match_data['teams'] : array();
$goals = isset($match_data['goals']) ? $match_data['goals'] : array();
$events = isset($match_data['events']) ? $match_data['events'] : array();
$lineups = isset($match_data['lineups']) ? $match_data['lineups'] : array();
$status = isset($fixture['status']) ? $fixture['status'] : array();

$is_live = isset($status['short']) && in_array(strtoupper($status['short']), ['LIVE', 'HT', 'FT', 'BT']);
$elapsed_time = isset($status['elapsed']) ? $status['elapsed'] : 0;

// Determine if we're in extra time or penalties
$extra_time = '';
if ($elapsed_time > 90 && $elapsed_time <= 120) {
    $extra_time = ' (Temps additionnel)';
} elseif ($elapsed_time > 120) {
    $extra_time = ' (Pénaltys)';
}
?>

<div class="kafunel-live-match-container">
    <div class="kafunel-match-header">
        <?php if (!empty($league['logo'])): ?>
            <img src="<?php echo esc_url($league['logo']); ?>" alt="<?php echo esc_attr($league['name']); ?>" class="kafunel-league-logo">
        <?php endif; ?>
        <h2 class="kafunel-match-league"><?php echo !empty($league['name']) ? esc_html($league['name']) : 'Compétition'; ?></h2>
        <div class="kafunel-match-date"><?php echo !empty($fixture['date']) ? esc_html(date('d/m/Y H:i', strtotime($fixture['date']))) : ''; ?></div>
    </div>
    
    <?php if ($is_live): ?>
        <div class="kafunel-live-indicator">
            <span class="live-blinking">LIVE STREAMING / DIRECT EN COURS</span>
        </div>
    <?php endif; ?>
    
    <div class="kafunel-match-teams">
        <div class="kafunel-team home-team">
            <?php if (!empty($teams['home']['logo'])): ?>
                <img src="<?php echo esc_url($teams['home']['logo']); ?>" alt="<?php echo esc_attr($teams['home']['name']); ?>" class="kafunel-team-logo">
            <?php endif; ?>
            <div class="kafunel-team-name"><?php echo !empty($teams['home']['name']) ? esc_html($teams['home']['name']) : 'Équipe Domicile'; ?></div>
        </div>
        
        <div class="kafunel-match-score">
            <div class="kafunel-score-home"><?php echo isset($goals['home']) ? esc_html($goals['home']) : '0'; ?></div>
            <div class="kafunel-score-separator">-</div>
            <div class="kafunel-score-away"><?php echo isset($goals['away']) ? esc_html($goals['away']) : '0'; ?></div>
            <div class="kafunel-match-time">
                <?php if ($is_live): ?>
                    <span id="match-time"><?php echo esc_html($elapsed_time); ?>'<?php echo esc_html($extra_time); ?></span>
                <?php else: ?>
                    <span>Terminé</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="kafunel-team away-team">
            <?php if (!empty($teams['away']['logo'])): ?>
                <img src="<?php echo esc_url($teams['away']['logo']); ?>" alt="<?php echo esc_attr($teams['away']['name']); ?>" class="kafunel-team-logo">
            <?php endif; ?>
            <div class="kafunel-team-name"><?php echo !empty($teams['away']['name']) ? esc_html($teams['away']['name']) : 'Équipe Extérieur'; ?></div>
        </div>
    </div>
    
    <?php if ($is_live): ?>
        <div class="kafunel-live-stats">
            <div class="kafunel-stats-row">
                <div class="kafunel-stat">
                    <span class="kafunel-stat-label">Possession</span>
                    <div class="kafunel-progress-bar">
                        <div class="kafunel-progress home" style="width: 55%"></div>
                    </div>
                    <span class="kafunel-stat-value">55%</span>
                </div>
                <div class="kafunel-stat">
                    <span class="kafunel-stat-label">Tirs</span>
                    <div class="kafunel-progress-bar">
                        <div class="kafunel-progress home" style="width: 60%"></div>
                    </div>
                    <span class="kafunel-stat-value">12-8</span>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="kafunel-match-events">
        <h3>Événements du Match</h3>
        <div class="kafunel-events-container">
            <?php if (!empty($events)): ?>
                <?php foreach ($events as $event): ?>
                    <?php if (isset($event['time']['elapsed']) && isset($event['player']['name'])): ?>
                        <div class="kafunel-event">
                            <span class="kafunel-event-time"><?php echo esc_html($event['time']['elapsed']); ?>' </span>
                            <span class="kafunel-event-type <?php echo esc_attr(strtolower($event['type'])); ?>"><?php echo esc_html($event['type']); ?></span>
                            <span class="kafunel-event-player"><?php echo esc_html($event['player']['name']); ?></span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucun événement enregistré pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="kafunel-match-lineups">
        <h3>Compositions (Onze Type)</h3>
        <div class="kafunel-lineups-container">
            <?php if (!empty($lineups)): ?>
                <?php foreach ($lineups as $lineup): ?>
                    <?php 
                    $team_id = isset($lineup['team']) ? $lineup['team'] : 0;
                    $team_info = ($team_id == $teams['home']['id']) ? $teams['home'] : $teams['away'];
                    ?>
                    <div class="kafunel-lineup-team">
                        <h4><?php echo esc_html($team_info['name']); ?></h4>
                        <div class="kafunel-formation"><?php echo isset($lineup['formation']) ? esc_html('Formation: ' . $lineup['formation']) : ''; ?></div>
                        <div class="kafunel-players">
                            <?php if (isset($lineup['startXI'])): ?>
                                <?php foreach ($lineup['startXI'] as $player): ?>
                                    <?php if (isset($player['player']['name'])): ?>
                                        <div class="kafunel-player">
                                            <span class="kafunel-player-number"><?php echo isset($player['player']['number']) ? '#' . esc_html($player['player']['number']) : ''; ?></span>
                                            <span class="kafunel-player-name"><?php echo esc_html($player['player']['name']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Les compositions ne sont pas encore disponibles.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="kafunel-match-actions">
        <button class="kafunel-share-whatsapp" onclick="shareOnWhatsApp()">Partager sur WhatsApp</button>
        <button class="kafunel-share-facebook" onclick="shareOnFacebook()">Partager sur Facebook</button>
    </div>
    
    <div class="kafunel-payment-section">
        <h3>Accès Premium</h3>
        <p>Pour accéder aux fonctionnalités complètes, effectuez un paiement via l'un des moyens suivants :</p>
        <div class="kafunel-payment-options">
            <?php 
            $wave_number = get_option('kafunel_wave_number', '00221 77 541 82 31');
            $orange_number = get_option('kafunel_orange_money_number', '00221 76 142 25 92');
            $yas_number = get_option('kafunel_yas_money_number', '');
            $paypal_link = get_option('kafunel_paypal_link', '');
            ?>
            
            <?php if (!empty($wave_number)): ?>
                <div class="kafunel-payment-option">
                    <strong>Wave:</strong> <?php echo esc_html($wave_number); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($orange_number)): ?>
                <div class="kafunel-payment-option">
                    <strong>Orange Money:</strong> <?php echo esc_html($orange_number); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($yas_number)): ?>
                <div class="kafunel-payment-option">
                    <strong>YAS Money:</strong> <?php echo esc_html($yas_number); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($paypal_link)): ?>
                <div class="kafunel-payment-option">
                    <a href="<?php echo esc_url($paypal_link); ?>" target="_blank" class="kafunel-paypal-button">Payer avec PayPal</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function shareOnWhatsApp() {
    const message = `Découvrez le match en direct: <?php 
        echo !empty($teams['home']['name']) ? esc_js($teams['home']['name']) : 'Équipe Domicile'; 
    ?> <?php 
        echo isset($goals['home']) ? esc_js($goals['home']) : '0'; 
    ?> - <?php 
        echo isset($goals['away']) ? esc_js($goals['away']) : '0'; 
    ?> <?php 
        echo !empty($teams['away']['name']) ? esc_js($teams['away']['name']) : 'Équipe Extérieur'; 
    ?>. Compétition: <?php 
        echo !empty($league['name']) ? esc_js($league['name']) : 'Compétition'; 
    ?>. Suivez tous les détails sur notre plateforme!`;
    
    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

function shareOnFacebook() {
    const shareUrl = window.location.href;
    const shareMessage = `Découvrez le match en direct: <?php 
        echo !empty($teams['home']['name']) ? esc_js($teams['home']['name']) . ' ' . (isset($goals['home']) ? $goals['home'] : '0') . ' - ' . (isset($goals['away']) ? $goals['away'] : '0') . ' ' . esc_js($teams['away']['name']) : 'Match en cours'; 
    ?>`;
    
    const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}&quote=${encodeURIComponent(shareMessage)}`;
    window.open(facebookUrl, '_blank');
}
</script>