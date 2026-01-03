jQuery(document).ready(function($) {
    // Check if we're on a match page
    if ($('.kafunel-live-match-container').length > 0) {
        // Start live updates
        startLiveUpdates();
        
        // Update match time counter
        setInterval(updateMatchTime, 60000); // Update every minute
    }
    
    function startLiveUpdates() {
        // Update every 30 seconds
        setInterval(function() {
            updateMatchData();
        }, 30000);
    }
    
    function updateMatchData() {
        // Get match ID from the container data attribute or URL
        var matchId = $('.kafunel-live-match-container').data('match-id') || getMatchIdFromUrl();
        
        if (!matchId) {
            return;
        }
        
        // Prepare AJAX data
        var data = {
            action: 'kafunel_update_match_data',
            match_id: matchId,
            nonce: kafunel_ajax.nonce
        };
        
        $.post(kafunel_ajax.ajax_url, data, function(response) {
            if (response.success) {
                updateMatchUI(response.data);
            } else {
                console.log('Error updating match data:', response.data);
            }
        }).fail(function() {
            console.log('Failed to update match data');
        });
    }
    
    function updateMatchUI(data) {
        // Update scores
        if (data.goals) {
            $('.kafunel-score-home').text(data.goals.home || 0);
            $('.kafunel-score-away').text(data.goals.away || 0);
        }
        
        // Update match time
        if (data.status && data.status.elapsed) {
            var timeText = data.status.elapsed + "'";
            if (data.status.elapsed > 90 && data.status.elapsed <= 120) {
                timeText += " (Temps additionnel)";
            } else if (data.status.elapsed > 120) {
                timeText += " (Pénaltys)";
            }
            $('#match-time').text(timeText);
        }
        
        // Update events if any new ones
        if (data.events && data.events.length > 0) {
            updateEvents(data.events);
        }
        
        // Update live indicator if match status changed
        if (data.status && data.status.short) {
            var isLive = ['LIVE', 'HT', 'FT', 'BT'].includes(data.status.short.toUpperCase());
            if (isLive) {
                $('.kafunel-live-indicator').show();
            } else {
                $('.kafunel-live-indicator').hide();
            }
        }
    }
    
    function updateEvents(events) {
        var eventsContainer = $('.kafunel-events-container');
        var currentEvents = eventsContainer.find('.kafunel-event').length;
        
        // For now, we'll just append new events if there are more than currently displayed
        if (events.length > currentEvents) {
            // Clear and repopulate events (in a real implementation, you'd want to be more sophisticated)
            eventsContainer.empty();
            
            events.forEach(function(event) {
                if (event.time && event.time.elapsed && event.player && event.player.name) {
                    var eventElement = $('<div class="kafunel-event">' +
                        '<span class="kafunel-event-time">' + event.time.elapsed + "' </span>" +
                        '<span class="kafunel-event-type ' + event.type.toLowerCase() + '">' + event.type + '</span>' +
                        '<span class="kafunel-event-player">' + event.player.name + '</span>' +
                        '</div>');
                    eventsContainer.append(eventElement);
                }
            });
        }
    }
    
    function updateMatchTime() {
        // Update the match time counter if the match is live
        var timeElement = $('#match-time');
        if (timeElement.length > 0) {
            var currentTime = timeElement.text();
            if (currentTime.includes("'")) {
                var currentMinute = parseInt(currentTime);
                if (!isNaN(currentMinute) && currentMinute < 90) {
                    timeElement.text((currentMinute + 1) + "'");
                }
            }
        }
    }
    
    function getMatchIdFromUrl() {
        // Try to extract match ID from URL
        var urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('match_id') || $('.kafunel-live-match-container').data('match-id');
    }
    
    // WhatsApp sharing functionality
    window.shareOnWhatsApp = function() {
        var homeTeam = $('.kafunel-team.home-team .kafunel-team-name').text();
        var awayTeam = $('.kafunel-team.away-team .kafunel-team-name').text();
        var homeScore = $('.kafunel-score-home').text();
        var awayScore = $('.kafunel-score-away').text();
        var league = $('.kafunel-match-league').text();
        
        var message = `Découvrez le match en direct: ${homeTeam} ${homeScore} - ${awayScore} ${awayTeam}. Compétition: ${league}. Suivez tous les détails sur notre plateforme!`;
        var whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
        
        window.open(whatsappUrl, '_blank');
    };
    
    // Facebook sharing functionality
    window.shareOnFacebook = function() {
        var shareUrl = window.location.href;
        var homeTeam = $('.kafunel-team.home-team .kafunel-team-name').text();
        var awayTeam = $('.kafunel-team.away-team .kafunel-team-name').text();
        var homeScore = $('.kafunel-score-home').text();
        var awayScore = $('.kafunel-score-away').text();
        
        var shareMessage = `Découvrez le match en direct: ${homeTeam} ${homeScore} - ${awayScore} ${awayTeam}`;
        var facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}&quote=${encodeURIComponent(shareMessage)}`;
        
        window.open(facebookUrl, '_blank');
    };
    
    // Payment button functionality
    $('.kafunel-paypal-button').on('click', function(e) {
        e.preventDefault();
        var paypalLink = $(this).attr('href');
        window.open(paypalLink, '_blank');
    });
});

// Add blinking animation for live indicator
document.addEventListener('DOMContentLoaded', function() {
    // Add CSS for blinking animation if not already present
    var style = document.createElement('style');
    style.textContent = `
        .live-blinking {
            animation: blink-animation 1s infinite;
            color: #ff0000;
            font-weight: bold;
            display: inline-block;
        }
        
        @keyframes blink-animation {
            0% { opacity: 1; }
            50% { opacity: 0.3; }
            100% { opacity: 1; }
        }
        
        .kafunel-live-match-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: Arial, sans-serif;
        }
        
        .kafunel-match-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .kafunel-league-logo {
            max-width: 80px;
            display: block;
            margin: 0 auto 10px;
        }
        
        .kafunel-match-league {
            margin: 0;
            color: #333;
        }
        
        .kafunel-match-date {
            color: #666;
            font-size: 0.9em;
        }
        
        .kafunel-live-indicator {
            text-align: center;
            margin: 15px 0;
        }
        
        .kafunel-match-teams {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }
        
        .kafunel-team {
            text-align: center;
            flex: 1;
        }
        
        .kafunel-team-logo {
            max-width: 60px;
            display: block;
            margin: 0 auto 5px;
        }
        
        .kafunel-team-name {
            font-weight: bold;
        }
        
        .kafunel-match-score {
            text-align: center;
            min-width: 120px;
        }
        
        .kafunel-score-home, .kafunel-score-away {
            display: inline-block;
            font-size: 2em;
            font-weight: bold;
            min-width: 40px;
        }
        
        .kafunel-score-separator {
            display: inline-block;
            margin: 0 10px;
            font-size: 1.5em;
        }
        
        .kafunel-match-time {
            font-size: 0.9em;
            color: #666;
        }
        
        .kafunel-stats-row {
            display: flex;
            justify-content: space-around;
            margin: 15px 0;
        }
        
        .kafunel-stat {
            text-align: center;
            flex: 1;
            margin: 0 10px;
        }
        
        .kafunel-stat-label {
            display: block;
            font-size: 0.8em;
            color: #666;
        }
        
        .kafunel-progress-bar {
            width: 100%;
            height: 8px;
            background-color: #e0e0e0;
            border-radius: 4px;
            margin: 5px 0;
            overflow: hidden;
        }
        
        .kafunel-progress {
            height: 100%;
            background-color: #0073aa;
        }
        
        .kafunel-stat-value {
            font-weight: bold;
        }
        
        .kafunel-match-events, .kafunel-match-lineups {
            margin: 20px 0;
        }
        
        .kafunel-events-container {
            margin-top: 10px;
        }
        
        .kafunel-event {
            padding: 8px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
        }
        
        .kafunel-event-time {
            font-weight: bold;
            min-width: 40px;
        }
        
        .kafunel-event-type {
            text-transform: uppercase;
            font-size: 0.8em;
            padding: 2px 5px;
            border-radius: 3px;
            margin: 0 5px;
        }
        
        .kafunel-event-type.goal {
            background-color: #4CAF50;
            color: white;
        }
        
        .kafunel-event-type.card {
            background-color: #FFC107;
        }
        
        .kafunel-event-type.substitution {
            background-color: #2196F3;
            color: white;
        }
        
        .kafunel-lineups-container {
            display: flex;
            justify-content: space-around;
        }
        
        .kafunel-lineup-team {
            flex: 1;
            margin: 0 10px;
        }
        
        .kafunel-formation {
            font-style: italic;
            margin: 5px 0;
        }
        
        .kafunel-players {
            margin-top: 10px;
        }
        
        .kafunel-player {
            padding: 5px 0;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
        }
        
        .kafunel-player-number {
            font-weight: bold;
            min-width: 30px;
        }
        
        .kafunel-match-actions {
            text-align: center;
            margin: 20px 0;
        }
        
        .kafunel-share-whatsapp, .kafunel-share-facebook {
            padding: 10px 20px;
            margin: 0 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }
        
        .kafunel-share-whatsapp {
            background-color: #25D366;
            color: white;
        }
        
        .kafunel-share-facebook {
            background-color: #4267B2;
            color: white;
        }
        
        .kafunel-payment-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .kafunel-payment-options {
            margin-top: 10px;
        }
        
        .kafunel-payment-option {
            margin: 5px 0;
            padding: 8px;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        
        .kafunel-paypal-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: #0070ba;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .kafunel-error {
            color: #d63638;
            padding: 10px;
            background-color: #f9e7e7;
            border: 1px solid #f0c5c5;
            border-radius: 4px;
            text-align: center;
        }
    `;
    document.head.appendChild(style);
});