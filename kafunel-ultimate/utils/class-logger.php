<?php
/**
 * Classe de journalisation pour le plugin Kafunel Ultimate
 * 
 * Fournit un système de journalisation structuré avec différents niveaux de log
 */
class Kafunel_Logger {
    
    private $log_level;
    private $log_file;
    
    const LOG_LEVEL_DEBUG = 'debug';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_CRITICAL = 'critical';
    
    public function __construct($log_level = self::LOG_LEVEL_INFO) {
        $this->log_level = $log_level;
        $this->log_file = WP_CONTENT_DIR . '/kafunel-logs.txt';
        
        // Créer le fichier de log s'il n'existe pas
        if (!file_exists($this->log_file)) {
            touch($this->log_file);
            chmod($this->log_file, 0664);
        }
    }
    
    /**
     * Journaliser un message
     */
    public function log($level, $message, $context = array()) {
        // Vérifier si le niveau de log est autorisé
        if (!$this->is_log_level_allowed($level)) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $user_info = $user_id ? get_userdata($user_id) : null;
        $username = $user_info ? $user_info->user_login : 'N/A';
        
        $log_entry = sprintf(
            "[%s] [%s] [User: %s] %s",
            $timestamp,
            strtoupper($level),
            $username,
            $message
        );
        
        // Ajouter le contexte si présent
        if (!empty($context)) {
            $log_entry .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $log_entry .= PHP_EOL;
        
        // Écrire dans le fichier de log
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // En production, on peut aussi envoyer les erreurs critiques à error_log
        if (in_array($level, [self::LOG_LEVEL_ERROR, self::LOG_LEVEL_CRITICAL])) {
            error_log('[Kafunel] ' . $message);
        }
    }
    
    /**
     * Vérifier si le niveau de log est autorisé
     */
    private function is_log_level_allowed($level) {
        $levels = [
            self::LOG_LEVEL_DEBUG => 0,
            self::LOG_LEVEL_INFO => 1,
            self::LOG_LEVEL_WARNING => 2,
            self::LOG_LEVEL_ERROR => 3,
            self::LOG_LEVEL_CRITICAL => 4
        ];
        
        $current_level = isset($levels[$this->log_level]) ? $levels[$this->log_level] : 1;
        $message_level = isset($levels[$level]) ? $levels[$level] : 1;
        
        return $message_level >= $current_level;
    }
    
    /**
     * Méthodes de niveau spécifique
     */
    public function debug($message, $context = array()) {
        $this->log(self::LOG_LEVEL_DEBUG, $message, $context);
    }
    
    public function info($message, $context = array()) {
        $this->log(self::LOG_LEVEL_INFO, $message, $context);
    }
    
    public function warning($message, $context = array()) {
        $this->log(self::LOG_LEVEL_WARNING, $message, $context);
    }
    
    public function error($message, $context = array()) {
        $this->log(self::LOG_LEVEL_ERROR, $message, $context);
    }
    
    public function critical($message, $context = array()) {
        $this->log(self::LOG_LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Obtenir le chemin du fichier de log
     */
    public function get_log_file_path() {
        return $this->log_file;
    }
    
    /**
     * Nettoyer le fichier de log
     */
    public function clear_log() {
        file_put_contents($this->log_file, '');
    }
    
    /**
     * Obtenir les dernières entrées du log
     */
    public function get_recent_logs($limit = 50) {
        if (!file_exists($this->log_file)) {
            return array();
        }
        
        $lines = array_reverse(file($this->log_file, FILE_IGNORE_NEW_LINES));
        return array_slice($lines, 0, $limit);
    }
}