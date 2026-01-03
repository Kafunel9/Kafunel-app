<?php
/**
 * Classe de validation pour le plugin Kafunel Ultimate
 * 
 * Fournit des méthodes de validation des données d'entrée
 */
class Kafunel_Validator {
    
    private $errors = array();
    
    /**
     * Valider un ID de match
     */
    public function validate_match_id($match_id) {
        if (!is_numeric($match_id) || $match_id <= 0) {
            $this->add_error('match_id', 'ID de match invalide');
            return false;
        }
        return true;
    }
    
    /**
     * Valider une adresse email
     */
    public function validate_email($email) {
        if (!is_email($email)) {
            $this->add_error('email', 'Adresse email invalide');
            return false;
        }
        return true;
    }
    
    /**
     * Valider un numéro de téléphone
     */
    public function validate_phone($phone) {
        // Format international simple
        $pattern = '/^[\+]?[0-9\s\-\(\)]+$/';
        if (!preg_match($pattern, $phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 8) {
            $this->add_error('phone', 'Numéro de téléphone invalide');
            return false;
        }
        return true;
    }
    
    /**
     * Valider un numéro de paiement mobile
     */
    public function validate_mobile_payment_number($number) {
        // Format pour les services mobiles (ex: 771234567)
        $pattern = '/^[\+]?[0-9]{9,15}$/';
        if (!preg_match($pattern, preg_replace('/[^0-9\+]/', '', $number))) {
            $this->add_error('payment_number', 'Numéro de paiement invalide');
            return false;
        }
        return true;
    }
    
    /**
     * Valider une URL
     */
    public function validate_url($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->add_error('url', 'URL invalide');
            return false;
        }
        return true;
    }
    
    /**
     * Valider un montant
     */
    public function validate_amount($amount) {
        if (!is_numeric($amount) || $amount <= 0) {
            $this->add_error('amount', 'Montant invalide');
            return false;
        }
        return true;
    }
    
    /**
     * Valider une méthode de paiement
     */
    public function validate_payment_method($method) {
        $valid_methods = array('wave', 'orange_money', 'yas_money', 'paypal');
        if (!in_array($method, $valid_methods)) {
            $this->add_error('payment_method', 'Méthode de paiement non supportée');
            return false;
        }
        return true;
    }
    
    /**
     * Valider un nonce WordPress
     */
    public function validate_nonce($nonce, $action = 'kafunel_nonce') {
        if (!wp_verify_nonce($nonce, $action)) {
            $this->add_error('nonce', 'Vérification de sécurité échouée');
            return false;
        }
        return true;
    }
    
    /**
     * Valider un tableau de données selon des règles spécifiées
     */
    public function validate($data, $rules) {
        $this->errors = array(); // Réinitialiser les erreurs
        
        foreach ($rules as $field => $rule) {
            $value = isset($data[$field]) ? $data[$field] : null;
            
            // Vérifier si le champ est requis
            if (strpos($rule, 'required') !== false && (is_null($value) || $value === '')) {
                $this->add_error($field, "Le champ {$field} est requis");
                continue;
            }
            
            // Si le champ est requis ou non vide, valider selon le type
            if (!is_null($value) && $value !== '') {
                $rule_parts = explode('|', $rule);
                
                foreach ($rule_parts as $rule_part) {
                    $rule_part = trim($rule_part);
                    
                    switch ($rule_part) {
                        case 'email':
                            if (!$this->validate_email($value)) {
                                $this->add_error($field, "Format d'email invalide pour {$field}");
                            }
                            break;
                        case 'url':
                            if (!$this->validate_url($value)) {
                                $this->add_error($field, "URL invalide pour {$field}");
                            }
                            break;
                        case 'numeric':
                            if (!is_numeric($value)) {
                                $this->add_error($field, "Valeur numérique requise pour {$field}");
                            }
                            break;
                        case 'positive':
                            if (!is_numeric($value) || $value <= 0) {
                                $this->add_error($field, "Valeur positive requise pour {$field}");
                            }
                            break;
                        case 'phone':
                            if (!$this->validate_phone($value)) {
                                $this->add_error($field, "Numéro de téléphone invalide pour {$field}");
                            }
                            break;
                        case 'mobile_payment':
                            if (!$this->validate_mobile_payment_number($value)) {
                                $this->add_error($field, "Numéro de paiement mobile invalide pour {$field}");
                            }
                            break;
                    }
                }
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Ajouter une erreur
     */
    private function add_error($field, $message) {
        $this->errors[$field] = $message;
    }
    
    /**
     * Obtenir les erreurs de validation
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Vérifier s'il y a des erreurs
     */
    public function has_errors() {
        return !empty($this->errors);
    }
    
    /**
     * Obtenir le premier message d'erreur
     */
    public function get_first_error() {
        if (!empty($this->errors)) {
            $errors = array_values($this->errors);
            return $errors[0];
        }
        return '';
    }
    
    /**
     * Nettoyer et valider les données POST
     */
    public function sanitize_and_validate_post_data($required_fields = array(), $optional_fields = array()) {
        $data = array();
        $errors = array();
        
        // Champs requis
        foreach ($required_fields as $field => $validation) {
            $value = isset($_POST[$field]) ? $_POST[$field] : null;
            
            if (is_null($value) || $value === '') {
                $errors[$field] = "Le champ {$field} est requis";
                continue;
            }
            
            // Sanitize selon le type
            switch ($validation) {
                case 'text':
                    $data[$field] = sanitize_text_field($value);
                    break;
                case 'email':
                    $data[$field] = sanitize_email($value);
                    if (empty($data[$field])) {
                        $errors[$field] = "Email invalide pour {$field}";
                    }
                    break;
                case 'url':
                    $data[$field] = esc_url_raw($value);
                    if (empty($data[$field])) {
                        $errors[$field] = "URL invalide pour {$field}";
                    }
                    break;
                case 'numeric':
                    $data[$field] = intval($value);
                    if ($data[$field] <= 0) {
                        $errors[$field] = "Valeur numérique positive requise pour {$field}";
                    }
                    break;
                case 'textarea':
                    $data[$field] = sanitize_textarea_field($value);
                    break;
                default:
                    $data[$field] = sanitize_text_field($value);
            }
            
            // Vérifier si la validation a échoué
            if (isset($errors[$field])) {
                continue;
            }
        }
        
        // Champs optionnels
        foreach ($optional_fields as $field => $validation) {
            if (isset($_POST[$field]) && $_POST[$field] !== '') {
                switch ($validation) {
                    case 'text':
                        $data[$field] = sanitize_text_field($_POST[$field]);
                        break;
                    case 'email':
                        $data[$field] = sanitize_email($_POST[$field]);
                        break;
                    case 'url':
                        $data[$field] = esc_url_raw($_POST[$field]);
                        break;
                    case 'numeric':
                        $data[$field] = intval($_POST[$field]);
                        break;
                    case 'textarea':
                        $data[$field] = sanitize_textarea_field($_POST[$field]);
                        break;
                    default:
                        $data[$field] = sanitize_text_field($_POST[$field]);
                }
            }
        }
        
        return array(
            'data' => $data,
            'errors' => $errors,
            'is_valid' => empty($errors)
        );
    }
}