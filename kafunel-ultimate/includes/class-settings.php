<?php
/**
 * Class to handle plugin settings
 */
class Kafunel_Ultimate_Settings {

    public function __construct() {
        // Initialize settings
    }

    /**
     * Initialize plugin settings
     */
    public function init_settings() {
        // Register settings
        register_setting('kafunel_settings_group', 'kafunel_api_football_key');
        register_setting('kafunel_settings_group', 'kafunel_rapidapi_key');
        register_setting('kafunel_settings_group', 'kafunel_wave_number');
        register_setting('kafunel_settings_group', 'kafunel_orange_money_number');
        register_setting('kafunel_settings_group', 'kafunel_yas_money_number');
        register_setting('kafunel_settings_group', 'kafunel_paypal_link');
        register_setting('kafunel_settings_group', 'kafunel_contact_email');
        register_setting('kafunel_settings_group', 'kafunel_contact_phone');
        register_setting('kafunel_settings_group', 'kafunel_whatsapp_number');
        register_setting('kafunel_settings_group', 'kafunel_whatsapp_message');
        
        // Add settings section
        add_settings_section(
            'kafunel_settings_section',
            'Paramètres Kafunel Ultimate',
            array($this, 'settings_section_callback'),
            'kafunel_settings'
        );
        
        // Add API settings fields
        add_settings_field(
            'kafunel_api_football_key',
            'API Football Key',
            array($this, 'api_football_key_callback'),
            'kafunel_settings',
            'kafunel_settings_section'
        );
        
        add_settings_field(
            'kafunel_rapidapi_key',
            'RapidAPI Key (Secours)',
            array($this, 'rapidapi_key_callback'),
            'kafunel_settings',
            'kafunel_settings_section'
        );
        
        // Add payment settings fields
        add_settings_field(
            'kafunel_wave_number',
            'Numéro Wave',
            array($this, 'wave_number_callback'),
            'kafunel_settings',
            'kafunel_settings_section'
        );
        
        add_settings_field(
            'kafunel_orange_money_number',
            'Numéro Orange Money',
            array($this, 'orange_money_number_callback'),
            'kafunel_settings',
            'kafunel_settings_section'
        );
        
        add_settings_field(
            'kafunel_yas_money_number',
            'Numéro YAS Money',
            array($this, 'yas_money_number_callback'),
            'kafunel_settings',
            'kafunel_settings_section'
        );
        
        add_settings_field(
            'kafunel_paypal_link',
            'Lien PayPal',
            array($this, 'paypal_link_callback'),
            'kafunel_settings',
            'kafunel_settings_section'
        );
        
        // Add contact settings fields
        add_settings_field(
            'kafunel_contact_email',
            'Email de Contact',
            array($this, 'contact_email_callback'),
            'kafunel_settings',
            'kafunel_settings_section'
        );
        
        add_settings_field(
            'kafunel_contact_phone',
            'Téléphone de Contact',
            array($this, 'contact_phone_callback'),
            'kafunel_settings',
            'kafunel_settings_section'
        );
        
        add_settings_field(
            'kafunel_whatsapp_number',
            'Numéro WhatsApp Business',
            array($this, 'whatsapp_number_callback'),
            'kafunel_settings',
            'kafunel_settings_section'
        );
        
        add_settings_field(
            'kafunel_whatsapp_message',
            'Message WhatsApp par défaut',
            array($this, 'whatsapp_message_callback'),
            'kafunel_settings',
            'kafunel_settings_section'
        );
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Gérez les paramètres de votre plugin Kafunel Ultimate ici.</p>';
    }

    /**
     * API Football Key field callback
     */
    public function api_football_key_callback() {
        $value = get_option('kafunel_api_football_key', '');
        echo '<input type="password" id="kafunel_api_football_key" name="kafunel_api_football_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Clé API principale pour API-Football (laisser vide pour utiliser le mode démo)</p>';
    }

    /**
     * RapidAPI Key field callback
     */
    public function rapidapi_key_callback() {
        $value = get_option('kafunel_rapidapi_key', '');
        echo '<input type="password" id="kafunel_rapidapi_key" name="kafunel_rapidapi_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Clé API de secours pour RapidAPI</p>';
    }

    /**
     * Wave Number field callback
     */
    public function wave_number_callback() {
        $value = get_option('kafunel_wave_number', '');
        echo '<input type="text" id="kafunel_wave_number" name="kafunel_wave_number" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Numéro de paiement Wave (format: 00221 77 541 82 31)</p>';
    }

    /**
     * Orange Money Number field callback
     */
    public function orange_money_number_callback() {
        $value = get_option('kafunel_orange_money_number', '');
        echo '<input type="text" id="kafunel_orange_money_number" name="kafunel_orange_money_number" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Numéro de paiement Orange Money</p>';
    }

    /**
     * YAS Money Number field callback
     */
    public function yas_money_number_callback() {
        $value = get_option('kafunel_yas_money_number', '');
        echo '<input type="text" id="kafunel_yas_money_number" name="kafunel_yas_money_number" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Numéro de paiement YAS Money</p>';
    }

    /**
     * PayPal Link field callback
     */
    public function paypal_link_callback() {
        $value = get_option('kafunel_paypal_link', '');
        echo '<input type="url" id="kafunel_paypal_link" name="kafunel_paypal_link" value="' . esc_url($value) . '" class="regular-text" />';
        echo '<p class="description">Lien PayPal.Me ou autre lien de paiement PayPal</p>';
    }

    /**
     * Contact Email field callback
     */
    public function contact_email_callback() {
        $value = get_option('kafunel_contact_email', '');
        echo '<input type="email" id="kafunel_contact_email" name="kafunel_contact_email" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Adresse email pour le support</p>';
    }

    /**
     * Contact Phone field callback
     */
    public function contact_phone_callback() {
        $value = get_option('kafunel_contact_phone', '');
        echo '<input type="text" id="kafunel_contact_phone" name="kafunel_contact_phone" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Numéro de téléphone pour le support</p>';
    }

    /**
     * WhatsApp Number field callback
     */
    public function whatsapp_number_callback() {
        $value = get_option('kafunel_whatsapp_number', '');
        echo '<input type="text" id="kafunel_whatsapp_number" name="kafunel_whatsapp_number" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Numéro WhatsApp Business pour les notifications</p>';
    }

    /**
     * WhatsApp Message field callback
     */
    public function whatsapp_message_callback() {
        $value = get_option('kafunel_whatsapp_message', 'Découvrez les scores en direct de vos matchs préférés avec Kafunel Ultimate!');
        echo '<textarea id="kafunel_whatsapp_message" name="kafunel_whatsapp_message" rows="5" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Message par défaut pour le partage WhatsApp</p>';
    }

    /**
     * Validate settings input
     */
    public function validate_settings($input) {
        $validated = array();
        
        // Validate API keys
        $validated['kafunel_api_football_key'] = sanitize_text_field($input['kafunel_api_football_key']);
        $validated['kafunel_rapidapi_key'] = sanitize_text_field($input['kafunel_rapidapi_key']);
        
        // Validate payment information
        $validated['kafunel_wave_number'] = sanitize_text_field($input['kafunel_wave_number']);
        $validated['kafunel_orange_money_number'] = sanitize_text_field($input['kafunel_orange_money_number']);
        $validated['kafunel_yas_money_number'] = sanitize_text_field($input['kafunel_yas_money_number']);
        $validated['kafunel_paypal_link'] = esc_url_raw($input['kafunel_paypal_link']);
        
        // Validate contact information
        $validated['kafunel_contact_email'] = sanitize_email($input['kafunel_contact_email']);
        $validated['kafunel_contact_phone'] = sanitize_text_field($input['kafunel_contact_phone']);
        $validated['kafunel_whatsapp_number'] = sanitize_text_field($input['kafunel_whatsapp_number']);
        
        // Validate custom message
        $validated['kafunel_whatsapp_message'] = sanitize_textarea_field($input['kafunel_whatsapp_message']);
        
        return $validated;
    }
}