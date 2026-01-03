<?php
/**
 * Classe de gestion du cache pour le plugin Kafunel Ultimate
 * 
 * Fournit un système de mise en cache pour améliorer les performances
 */
class Kafunel_Cache_Manager {
    
    private $cache_prefix = 'kafunel_';
    private $default_expiration = 300; // 5 minutes par défaut
    private $logger;
    
    public function __construct($logger = null) {
        $this->logger = $logger;
    }
    
    /**
     * Obtenir une valeur depuis le cache
     */
    public function get($key, $group = 'default') {
        $cache_key = $this->build_cache_key($key, $group);
        
        try {
            $cached_data = wp_cache_get($cache_key, $this->cache_prefix . $group);
            
            if ($this->logger) {
                $this->logger->debug("Cache get: {$cache_key}", array(
                    'found' => $cached_data !== false
                ));
            }
            
            return $cached_data !== false ? $cached_data : null;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Erreur lors de la récupération du cache: " . $e->getMessage(), array(
                    'key' => $cache_key,
                    'group' => $group
                ));
            }
            throw new Kafunel_Cache_Exception("Erreur de cache lors de la récupération", $key, 0, $e);
        }
    }
    
    /**
     * Définir une valeur dans le cache
     */
    public function set($key, $data, $expiration = null, $group = 'default') {
        $cache_key = $this->build_cache_key($key, $group);
        $expiration = $expiration !== null ? $expiration : $this->default_expiration;
        
        try {
            $result = wp_cache_set($cache_key, $data, $this->cache_prefix . $group, $expiration);
            
            if ($this->logger) {
                $this->logger->debug("Cache set: {$cache_key}", array(
                    'expiration' => $expiration,
                    'success' => $result
                ));
            }
            
            return $result;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Erreur lors de la mise en cache: " . $e->getMessage(), array(
                    'key' => $cache_key,
                    'group' => $group
                ));
            }
            throw new Kafunel_Cache_Exception("Erreur de cache lors de la sauvegarde", $key, 0, $e);
        }
    }
    
    /**
     * Supprimer une entrée du cache
     */
    public function delete($key, $group = 'default') {
        $cache_key = $this->build_cache_key($key, $group);
        
        try {
            $result = wp_cache_delete($cache_key, $this->cache_prefix . $group);
            
            if ($this->logger) {
                $this->logger->debug("Cache delete: {$cache_key}", array(
                    'success' => $result
                ));
            }
            
            return $result;
        } catch (Exception $e) {
            if ($this->logger) {
                $this->logger->error("Erreur lors de la suppression du cache: " . $e->getMessage(), array(
                    'key' => $cache_key,
                    'group' => $group
                ));
            }
            throw new Kafunel_Cache_Exception("Erreur de cache lors de la suppression", $key, 0, $e);
        }
    }
    
    /**
     * Vérifier si une clé existe dans le cache
     */
    public function has($key, $group = 'default') {
        return $this->get($key, $group) !== null;
    }
    
    /**
     * Effacer le cache pour un groupe spécifique
     */
    public function flush_group($group = 'default') {
        // WordPress ne permet pas de vider un groupe spécifique directement
        // On va utiliser une approche alternative en stockant les clés dans un tableau
        $group_key = $this->cache_prefix . 'group_keys_' . $group;
        $keys = wp_cache_get($group_key, 'kafunel_groups');
        
        if (is_array($keys)) {
            foreach ($keys as $key) {
                wp_cache_delete($key, $this->cache_prefix . $group);
            }
            wp_cache_delete($group_key, 'kafunel_groups');
        }
        
        if ($this->logger) {
            $this->logger->info("Cache group flushed: {$group}");
        }
    }
    
    /**
     * Effacer tous les caches Kafunel
     */
    public function flush_all() {
        // Effacer tous les groupes de cache
        $groups = array('api', 'matches', 'teams', 'leagues', 'users', 'default');
        foreach ($groups as $group) {
            $this->flush_group($group);
        }
        
        if ($this->logger) {
            $this->logger->info("All Kafunel caches flushed");
        }
    }
    
    /**
     * Construire une clé de cache
     */
    private function build_cache_key($key, $group) {
        // Nettoyer la clé pour éviter les problèmes avec les systèmes de cache
        $clean_key = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
        return $this->cache_prefix . $group . '_' . $clean_key;
    }
    
    /**
     * Générer une clé de cache pour les données API
     */
    public function generate_api_cache_key($endpoint, $params = array()) {
        $param_string = !empty($params) ? md5(serialize($params)) : '';
        return $endpoint . ($param_string ? '_' . $param_string : '');
    }
    
    /**
     * Récupérer ou définir une valeur avec un callback
     */
    public function remember($key, $callback, $expiration = null, $group = 'default') {
        $cached_value = $this->get($key, $group);
        
        if ($cached_value !== null) {
            return $cached_value;
        }
        
        $value = call_user_func($callback);
        
        if ($value !== null) {
            $this->set($key, $value, $expiration, $group);
        }
        
        return $value;
    }
    
    /**
     * Récupérer ou définir une valeur avec expiration dynamique
     */
    public function remember_with_dynamic_ttl($key, $callback, $ttl_callback, $group = 'default') {
        $cached_value = $this->get($key, $group);
        
        if ($cached_value !== null) {
            return $cached_value;
        }
        
        $value = call_user_func($callback);
        $ttl = call_user_func($ttl_callback, $value);
        
        if ($value !== null) {
            $this->set($key, $value, $ttl, $group);
        }
        
        return $value;
    }
    
    /**
     * Définir une valeur avec expiration basée sur le type de données
     */
    public function set_with_smart_ttl($key, $data, $data_type = 'default', $group = 'default') {
        $ttl = $this->get_smart_ttl($data_type);
        return $this->set($key, $data, $ttl, $group);
    }
    
    /**
     * Obtenir le TTL intelligent basé sur le type de données
     */
    private function get_smart_ttl($data_type) {
        $ttl_map = array(
            'api_response' => 300,       // 5 minutes pour les réponses API
            'live_match' => 60,          // 1 minute pour les matchs en direct
            'match_result' => 3600,      // 1 heure pour les résultats de matchs terminés
            'team_info' => 7200,         // 2 heures pour les infos d'équipe
            'league_info' => 7200,       // 2 heures pour les infos de ligue
            'user_data' => 1800,         // 30 minutes pour les données utilisateur
            'default' => 900             // 15 minutes par défaut
        );
        
        return isset($ttl_map[$data_type]) ? $ttl_map[$data_type] : $this->default_expiration;
    }
    
    /**
     * Définir le TTL par défaut
     */
    public function set_default_expiration($seconds) {
        $this->default_expiration = $seconds;
    }
    
    /**
     * Obtenir le TTL par défaut
     */
    public function get_default_expiration() {
        return $this->default_expiration;
    }
}