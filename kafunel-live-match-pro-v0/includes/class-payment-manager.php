<?php
/**
 * Class to handle payment integration
 */
class Kafunel_Payment_Manager {

    public function __construct() {
        // Initialize payment methods
        add_action('wp_ajax_kafunel_process_payment', array($this, 'handle_payment'));
        add_action('wp_ajax_nopriv_kafunel_process_payment', array($this, 'handle_payment'));
    }

    /**
     * Handle payment processing
     */
    public function handle_payment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'kafunel_nonce')) {
            wp_die('Security check failed');
        }

        $payment_method = sanitize_text_field($_POST['payment_method']);
        $amount = floatval($_POST['amount']);
        $user_id = intval($_POST['user_id']);

        switch ($payment_method) {
            case 'wave':
                $result = $this->process_wave_payment($amount, $user_id);
                break;
            case 'orange_money':
                $result = $this->process_orange_money_payment($amount, $user_id);
                break;
            case 'yas_money':
                $result = $this->process_yas_money_payment($amount, $user_id);
                break;
            case 'paypal':
                $result = $this->process_paypal_payment($amount, $user_id);
                break;
            default:
                $result = array('success' => false, 'message' => 'Méthode de paiement non supportée');
        }

        wp_send_json($result);
    }

    /**
     * Process Wave payment
     */
    private function process_wave_payment($amount, $user_id) {
        $wave_number = get_option('kafunel_wave_number', '');
        
        if (empty($wave_number)) {
            return array(
                'success' => false, 
                'message' => 'Numéro Wave non configuré'
            );
        }

        // In a real implementation, you would integrate with Wave API
        // For now, return success with payment info
        return array(
            'success' => true,
            'message' => 'Veuillez effectuer le paiement de ' . $amount . ' FCFA sur le numéro Wave: ' . $wave_number,
            'payment_info' => array(
                'method' => 'wave',
                'number' => $wave_number,
                'amount' => $amount
            )
        );
    }

    /**
     * Process Orange Money payment
     */
    private function process_orange_money_payment($amount, $user_id) {
        $orange_number = get_option('kafunel_orange_money_number', '');
        
        if (empty($orange_number)) {
            return array(
                'success' => false, 
                'message' => 'Numéro Orange Money non configuré'
            );
        }

        // In a real implementation, you would integrate with Orange Money API
        return array(
            'success' => true,
            'message' => 'Veuillez effectuer le paiement de ' . $amount . ' FCFA sur le numéro Orange Money: ' . $orange_number,
            'payment_info' => array(
                'method' => 'orange_money',
                'number' => $orange_number,
                'amount' => $amount
            )
        );
    }

    /**
     * Process YAS Money payment
     */
    private function process_yas_money_payment($amount, $user_id) {
        $yas_number = get_option('kafunel_yas_money_number', '');
        
        if (empty($yas_number)) {
            return array(
                'success' => false, 
                'message' => 'Numéro YAS Money non configuré'
            );
        }

        // In a real implementation, you would integrate with YAS Money API
        return array(
            'success' => true,
            'message' => 'Veuillez effectuer le paiement de ' . $amount . ' FCFA sur le numéro YAS Money: ' . $yas_number,
            'payment_info' => array(
                'method' => 'yas_money',
                'number' => $yas_number,
                'amount' => $amount
            )
        );
    }

    /**
     * Process PayPal payment
     */
    private function process_paypal_payment($amount, $user_id) {
        $paypal_link = get_option('kafunel_paypal_link', '');
        
        if (empty($paypal_link)) {
            return array(
                'success' => false, 
                'message' => 'Lien PayPal non configuré'
            );
        }

        return array(
            'success' => true,
            'message' => 'Redirection vers PayPal pour le paiement',
            'payment_info' => array(
                'method' => 'paypal',
                'link' => $paypal_link,
                'amount' => $amount
            )
        );
    }

    /**
     * Get available payment methods
     */
    public function get_available_payment_methods() {
        $methods = array();

        if (!empty(get_option('kafunel_wave_number', ''))) {
            $methods['wave'] = array(
                'name' => 'Wave',
                'icon' => 'wave-icon',
                'enabled' => true
            );
        }

        if (!empty(get_option('kafunel_orange_money_number', ''))) {
            $methods['orange_money'] = array(
                'name' => 'Orange Money',
                'icon' => 'orange-icon',
                'enabled' => true
            );
        }

        if (!empty(get_option('kafunel_yas_money_number', ''))) {
            $methods['yas_money'] = array(
                'name' => 'YAS Money',
                'icon' => 'yas-icon',
                'enabled' => true
            );
        }

        if (!empty(get_option('kafunel_paypal_link', ''))) {
            $methods['paypal'] = array(
                'name' => 'PayPal',
                'icon' => 'paypal-icon',
                'enabled' => true
            );
        }

        return $methods;
    }

    /**
     * Display payment options
     */
    public function display_payment_options() {
        $methods = $this->get_available_payment_methods();
        $output = '<div class="kafunel-payment-options">';

        foreach ($methods as $method => $details) {
            $output .= '<div class="kafunel-payment-method" data-method="' . $method . '">';
            $output .= '<h4>' . $details['name'] . '</h4>';
            
            switch ($method) {
                case 'wave':
                    $output .= '<p>Numéro: ' . get_option('kafunel_wave_number', '') . '</p>';
                    break;
                case 'orange_money':
                    $output .= '<p>Numéro: ' . get_option('kafunel_orange_money_number', '') . '</p>';
                    break;
                case 'yas_money':
                    $output .= '<p>Numéro: ' . get_option('kafunel_yas_money_number', '') . '</p>';
                    break;
                case 'paypal':
                    $output .= '<a href="' . esc_url(get_option('kafunel_paypal_link', '')) . '" target="_blank" class="kafunel-paypal-button">Payer avec PayPal</a>';
                    break;
            }
            
            $output .= '</div>';
        }

        $output .= '</div>';
        return $output;
    }
}