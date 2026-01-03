<?php
/**
 * Classes d'exceptions personnalisées pour le plugin Kafunel Ultimate
 */

/**
 * Exception de base pour le plugin
 */
class Kafunel_Exception extends Exception {
    
    public function __construct($message = "", $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    
    public function get_error_details() {
        return array(
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        );
    }
}

/**
 * Exception pour les erreurs liées à l'API
 */
class Kafunel_API_Exception extends Kafunel_Exception {
    
    private $api_response;
    private $api_error_code;
    
    public function __construct($message = "", $code = 0, $api_response = null, $api_error_code = null, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->api_response = $api_response;
        $this->api_error_code = $api_error_code;
    }
    
    public function get_api_response() {
        return $this->api_response;
    }
    
    public function get_api_error_code() {
        return $this->api_error_code;
    }
    
    public function get_error_details() {
        $details = parent::get_error_details();
        $details['api_response'] = $this->api_response;
        $details['api_error_code'] = $this->api_error_code;
        return $details;
    }
}

/**
 * Exception pour les erreurs de validation
 */
class Kafunel_Validation_Exception extends Kafunel_Exception {
    
    private $validation_errors;
    
    public function __construct($message = "", $validation_errors = array(), $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->validation_errors = $validation_errors;
    }
    
    public function get_validation_errors() {
        return $this->validation_errors;
    }
    
    public function get_error_details() {
        $details = parent::get_error_details();
        $details['validation_errors'] = $this->validation_errors;
        return $details;
    }
}

/**
 * Exception pour les erreurs de paiement
 */
class Kafunel_Payment_Exception extends Kafunel_Exception {
    
    private $payment_details;
    
    public function __construct($message = "", $payment_details = array(), $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->payment_details = $payment_details;
    }
    
    public function get_payment_details() {
        return $this->payment_details;
    }
    
    public function get_error_details() {
        $details = parent::get_error_details();
        $details['payment_details'] = $this->payment_details;
        return $details;
    }
}

/**
 * Exception pour les erreurs de sécurité
 */
class Kafunel_Security_Exception extends Kafunel_Exception {
    
    private $security_context;
    
    public function __construct($message = "", $security_context = array(), $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->security_context = $security_context;
    }
    
    public function get_security_context() {
        return $this->security_context;
    }
    
    public function get_error_details() {
        $details = parent::get_error_details();
        $details['security_context'] = $this->security_context;
        return $details;
    }
}

/**
 * Exception pour les erreurs de cache
 */
class Kafunel_Cache_Exception extends Kafunel_Exception {
    
    private $cache_key;
    
    public function __construct($message = "", $cache_key = '', $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->cache_key = $cache_key;
    }
    
    public function get_cache_key() {
        return $this->cache_key;
    }
    
    public function get_error_details() {
        $details = parent::get_error_details();
        $details['cache_key'] = $this->cache_key;
        return $details;
    }
}