import React, { useState, useEffect } from 'react';
import {
  StyleSheet,
  View,
  Text,
  ScrollView,
  TouchableOpacity,
  RefreshControl,
  Image,
  ActivityIndicator,
} from 'react-native';

import axios from 'axios';

const App = () => {
  const [matches, setMatches] = useState([]);
  const [liveMatches, setLiveMatches] = useState([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedMatch, setSelectedMatch] = useState(null);

  // Replace with your WordPress site URL
  const WORDPRESS_SITE_URL = 'https://yoursite.com'; // TODO: Replace with actual site URL

  useEffect(() => {
    fetchLiveMatches();
  }, []);

  const fetchLiveMatches = async () => {
    try {
      setLoading(true);
      // WordPress REST API endpoint for Kafunel matches
      const response = await axios.get(
        `${WORDPRESS_SITE_URL}/wp-json/kafunel/v1/live-matches`
      );
      
      if (response.data && response.data.matches) {
        setLiveMatches(response.data.matches);
        setMatches(response.data.matches);
      }
    } catch (error) {
      console.error('Error fetching matches:', error);
      // In case of error, show demo data
      setMatches(getDemoMatches());
    } finally {
      setLoading(false);
    }
  };

  const getDemoMatches = () => {
    return [
      {
        id: 1,
        homeTeam: 'Sénégal',
        awayTeam: 'Côte d\'Ivoire',
        homeScore: 2,
        awayScore: 1,
        status: 'LIVE',
        elapsed: 75,
        league: 'CAN 2025',
        events: [
          { minute: 23, type: 'Goal', player: 'Sadio Mané', team: 'home' },
          { minute: 45, type: 'Goal', player: 'Seri', team: 'away' },
          { minute: 67, type: 'Goal', player: 'Ismaïla Sarr', team: 'home' },
        ],
        homeFormation: '4-2-3-1',
        awayFormation: '4-3-3',
        homePlayers: [
          { number: 16, name: 'Édouard Mendy' },
          { number: 3, name: 'Kalidou Koulibaly' },
          { number: 14, name: 'Pape Gueye' },
          { number: 10, name: 'Sadio Mané' },
          { number: 19, name: 'Ismaïla Sarr' },
        ],
        awayPlayers: [
          { number: 16, name: 'Simon Adingra' },
          { number: 6, name: 'Seri' },
          { number: 11, name: 'Zaha' },
        ],
      }
    ];
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchLiveMatches();
    setRefreshing(false);
  };

  const renderMatchCard = (match) => {
    const isLive = match.status === 'LIVE';
    
    return (
      <TouchableOpacity
        key={match.id}
        style={styles.matchCard}
        onPress={() => setSelectedMatch(match)}
      >
        <View style={styles.matchHeader}>
          <Text style={styles.leagueText}>{match.league}</Text>
          {isLive && (
            <View style={styles.liveIndicator}>
              <Text style={styles.liveText}>LIVE</Text>
            </View>
          )}
        </View>
        
        <View style={styles.teamsContainer}>
          <View style={styles.teamContainer}>
            <Text style={styles.teamName}>{match.homeTeam}</Text>
          </View>
          
          <View style={styles.scoreContainer}>
            <Text style={styles.scoreText}>
              {match.homeScore} - {match.awayScore}
            </Text>
            {isLive && (
              <Text style={styles.timeText}>{match.elapsed}'</Text>
            )}
          </View>
          
          <View style={styles.teamContainer}>
            <Text style={styles.teamName}>{match.awayTeam}</Text>
          </View>
        </View>
        
        <View style={styles.matchFooter}>
          <Text style={styles.statusText}>
            {isLive ? `${match.elapsed}'` : 'Terminé'}
          </Text>
        </View>
      </TouchableOpacity>
    );
  };

  const renderMatchDetail = (match) => {
    if (!match) return null;

    return (
      <View style={styles.matchDetailContainer}>
        <View style={styles.detailHeader}>
          <Text style={styles.detailLeague}>{match.league}</Text>
          <TouchableOpacity
            onPress={() => setSelectedMatch(null)}
            style={styles.closeButton}
          >
            <Text style={styles.closeButtonText}>×</Text>
          </TouchableOpacity>
        </View>

        <View style={styles.detailTeamsContainer}>
          <View style={styles.detailTeam}>
            <Text style={styles.detailTeamName}>{match.homeTeam}</Text>
            <Text style={styles.detailScore}>{match.homeScore}</Text>
          </View>
          <Text style={styles.detailVs}>VS</Text>
          <View style={styles.detailTeam}>
            <Text style={styles.detailScore}>{match.awayScore}</Text>
            <Text style={styles.detailTeamName}>{match.awayTeam}</Text>
          </View>
        </View>

        {match.status === 'LIVE' && (
          <View style={styles.liveTimeContainer}>
            <Text style={styles.liveTime}>{match.elapsed}'</Text>
          </View>
        )}

        {/* Events */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Événements</Text>
          {match.events && match.events.map((event, index) => (
            <View key={index} style={styles.eventItem}>
              <Text style={styles.eventMinute}>{event.minute}'</Text>
              <Text style={[
                styles.eventType,
                event.team === 'home' ? styles.homeEvent : styles.awayEvent
              ]}>
                {event.type}
              </Text>
              <Text style={styles.eventPlayer}>{event.player}</Text>
            </View>
          ))}
        </View>

        {/* Lineups */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Compositions</Text>
          <View style={styles.lineupsContainer}>
            <View style={styles.lineupTeam}>
              <Text style={styles.lineupTeamName}>{match.homeTeam}</Text>
              <Text style={styles.formationText}>{match.homeFormation}</Text>
              {match.homePlayers && match.homePlayers.map((player, index) => (
                <View key={index} style={styles.playerItem}>
                  <Text style={styles.playerNumber}>#{player.number}</Text>
                  <Text style={styles.playerName}>{player.name}</Text>
                </View>
              ))}
            </View>
            <View style={styles.lineupTeam}>
              <Text style={styles.lineupTeamName}>{match.awayTeam}</Text>
              <Text style={styles.formationText}>{match.awayFormation}</Text>
              {match.awayPlayers && match.awayPlayers.map((player, index) => (
                <View key={index} style={styles.playerItem}>
                  <Text style={styles.playerNumber}>#{player.number}</Text>
                  <Text style={styles.playerName}>{player.name}</Text>
                </View>
              ))}
            </View>
          </View>
        </View>
      </View>
    );
  };

  if (loading && !refreshing) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color="#007AFF" />
        <Text style={styles.loadingText}>Chargement des matchs...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>Kafunel Live Match</Text>
        <Text style={styles.headerSubtitle}>Suivez vos matchs en direct</Text>
      </View>

      {selectedMatch ? (
        renderMatchDetail(selectedMatch)
      ) : (
        <ScrollView
          style={styles.scrollView}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
          }
        >
          {matches.length > 0 ? (
            matches.map(renderMatchCard)
          ) : (
            <View style={styles.noMatchesContainer}>
              <Text style={styles.noMatchesText}>Aucun match en cours</Text>
            </View>
          )}
        </ScrollView>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 10,
    fontSize: 16,
    color: '#666',
  },
  header: {
    backgroundColor: '#007AFF',
    padding: 20,
    paddingTop: 50,
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: 'bold',
    color: 'white',
  },
  headerSubtitle: {
    fontSize: 16,
    color: 'rgba(255, 255, 255, 0.8)',
    marginTop: 5,
  },
  scrollView: {
    flex: 1,
  },
  matchCard: {
    backgroundColor: 'white',
    margin: 10,
    padding: 15,
    borderRadius: 10,
    elevation: 3,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 3,
  },
  matchHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  leagueText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
  },
  liveIndicator: {
    backgroundColor: '#FF3B30',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 10,
  },
  liveText: {
    color: 'white',
    fontSize: 12,
    fontWeight: 'bold',
  },
  teamsContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  teamContainer: {
    flex: 1,
    alignItems: 'center',
  },
  teamName: {
    fontSize: 16,
    fontWeight: '600',
    textAlign: 'center',
  },
  scoreContainer: {
    alignItems: 'center',
    marginHorizontal: 15,
  },
  scoreText: {
    fontSize: 24,
    fontWeight: 'bold',
  },
  timeText: {
    fontSize: 14,
    color: '#666',
    marginTop: 5,
  },
  matchFooter: {
    alignItems: 'center',
  },
  statusText: {
    fontSize: 14,
    color: '#666',
  },
  noMatchesContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  noMatchesText: {
    fontSize: 18,
    color: '#666',
  },
  matchDetailContainer: {
    flex: 1,
    padding: 15,
  },
  detailHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  detailLeague: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
  },
  closeButton: {
    padding: 10,
  },
  closeButtonText: {
    fontSize: 24,
    color: '#666',
  },
  detailTeamsContainer: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 20,
  },
  detailTeam: {
    flex: 1,
    alignItems: 'center',
  },
  detailTeamName: {
    fontSize: 16,
    fontWeight: '600',
  },
  detailScore: {
    fontSize: 32,
    fontWeight: 'bold',
    marginVertical: 10,
  },
  detailVs: {
    fontSize: 16,
    color: '#666',
    marginHorizontal: 10,
  },
  liveTimeContainer: {
    alignItems: 'center',
    marginBottom: 20,
  },
  liveTime: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FF3B30',
  },
  section: {
    marginBottom: 25,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    marginBottom: 10,
    color: '#333',
  },
  eventItem: {
    flexDirection: 'row',
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#eee',
  },
  eventMinute: {
    width: 40,
    fontWeight: 'bold',
  },
  eventType: {
    flex: 1,
    textTransform: 'uppercase',
    fontSize: 12,
    marginRight: 10,
  },
  homeEvent: {
    color: '#007AFF',
  },
  awayEvent: {
    color: '#FF9500',
  },
  eventPlayer: {
    flex: 2,
  },
  lineupsContainer: {
    flexDirection: 'row',
  },
  lineupTeam: {
    flex: 1,
    marginRight: 10,
  },
  lineupTeamName: {
    fontWeight: 'bold',
    marginBottom: 5,
  },
  formationText: {
    fontStyle: 'italic',
    marginBottom: 10,
    color: '#666',
  },
  playerItem: {
    flexDirection: 'row',
    paddingVertical: 5,
  },
  playerNumber: {
    width: 30,
    fontWeight: 'bold',
  },
  playerName: {
    flex: 1,
  },
});

export default App;