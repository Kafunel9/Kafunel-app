<?php
/**
 * Settings page for Kafunel Ultimate
 */
if (!defined('WPINC')) {
    die;
}

// Check if user has proper permissions
if (!current_user_can('manage_options')) {
    return;
}

// Handle form submission
if (isset($_POST['kafunel_save_settings'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['kafunel_settings_nonce'], 'kafunel_save_settings')) {
        wp_die('Security check failed');
    }

    // Sanitize and save API keys
    update_option('kafunel_api_football_key', sanitize_text_field($_POST['kafunel_api_football_key']));
    update_option('kafunel_rapidapi_key', sanitize_text_field($_POST['kafunel_rapidapi_key']));

    // Sanitize and save payment information
    update_option('kafunel_wave_number', sanitize_text_field($_POST['kafunel_wave_number']));
    update_option('kafunel_orange_money_number', sanitize_text_field($_POST['kafunel_orange_money_number']));
    update_option('kafunel_yas_money_number', sanitize_text_field($_POST['kafunel_yas_money_number']));
    update_option('kafunel_paypal_link', esc_url_raw($_POST['kafunel_paypal_link']));

    // Sanitize and save contact information
    update_option('kafunel_contact_email', sanitize_email($_POST['kafunel_contact_email']));
    update_option('kafunel_contact_phone', sanitize_text_field($_POST['kafunel_contact_phone']));
    update_option('kafunel_whatsapp_number', sanitize_text_field($_POST['kafunel_whatsapp_number']));

    // Sanitize and save custom messages
    update_option('kafunel_whatsapp_message', sanitize_textarea_field($_POST['kafunel_whatsapp_message']));

    echo '<div class="notice notice-success is-dismissible"><p>Paramètres sauvegardés avec succès!</p></div>';
}
?>

<div class="wrap">
    <h1>Paramètres Kafunel Ultimate</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('kafunel_save_settings', 'kafunel_settings_nonce'); ?>
        
        <h2>Clés API</h2>
        <table class="form-table">
            <tr>
                <th scope="row">API Football Key</th>
                <td>
                    <input type="password" name="kafunel_api_football_key" 
                           value="<?php echo esc_attr(get_option('kafunel_api_football_key', '')); ?>" 
                           class="regular-text" />
                    <p class="description">Clé API principale pour API-Football (laisser vide pour utiliser le mode démo)</p>
                </td>
            </tr>
            <tr>
                <th scope="row">RapidAPI Key (Secours)</th>
                <td>
                    <input type="password" name="kafunel_rapidapi_key" 
                           value="<?php echo esc_attr(get_option('kafunel_rapidapi_key', '')); ?>" 
                           class="regular-text" />
                    <p class="description">Clé API de secours pour RapidAPI</p>
                </td>
            </tr>
        </table>
        
        <h2>Informations de Paiement</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Numéro Wave</th>
                <td>
                    <input type="text" name="kafunel_wave_number" 
                           value="<?php echo esc_attr(get_option('kafunel_wave_number', '')); ?>" 
                           class="regular-text" />
                    <p class="description">Numéro de paiement Wave (format: 00221 77 541 82 31)</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Numéro Orange Money</th>
                <td>
                    <input type="text" name="kafunel_orange_money_number" 
                           value="<?php echo esc_attr(get_option('kafunel_orange_money_number', '')); ?>" 
                           class="regular-text" />
                    <p class="description">Numéro de paiement Orange Money</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Numéro YAS Money</th>
                <td>
                    <input type="text" name="kafunel_yas_money_number" 
                           value="<?php echo esc_attr(get_option('kafunel_yas_money_number', '')); ?>" 
                           class="regular-text" />
                    <p class="description">Numéro de paiement YAS Money</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Lien PayPal</th>
                <td>
                    <input type="url" name="kafunel_paypal_link" 
                           value="<?php echo esc_url(get_option('kafunel_paypal_link', '')); ?>" 
                           class="regular-text" />
                    <p class="description">Lien PayPal.Me ou autre lien de paiement PayPal</p>
                </td>
            </tr>
        </table>
        
        <h2>Informations de Contact</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Email de Contact</th>
                <td>
                    <input type="email" name="kafunel_contact_email" 
                           value="<?php echo esc_attr(get_option('kafunel_contact_email', '')); ?>" 
                           class="regular-text" />
                    <p class="description">Adresse email pour le support</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Téléphone de Contact</th>
                <td>
                    <input type="text" name="kafunel_contact_phone" 
                           value="<?php echo esc_attr(get_option('kafunel_contact_phone', '')); ?>" 
                           class="regular-text" />
                    <p class="description">Numéro de téléphone pour le support</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Numéro WhatsApp Business</th>
                <td>
                    <input type="text" name="kafunel_whatsapp_number" 
                           value="<?php echo esc_attr(get_option('kafunel_whatsapp_number', '')); ?>" 
                           class="regular-text" />
                    <p class="description">Numéro WhatsApp Business pour les notifications</p>
                </td>
            </tr>
        </table>
        
        <h2>Messages Personnalisés</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Message WhatsApp par défaut</th>
                <td>
                    <textarea name="kafunel_whatsapp_message" rows="5" cols="50" class="large-text"><?php 
                        echo esc_textarea(get_option('kafunel_whatsapp_message', "Découvrez les scores en direct de vos matchs préférés avec Kafunel Ultimate!")); 
                    ?></textarea>
                    <p class="description">Message par défaut pour le partage WhatsApp</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('Sauvegarder les paramètres', 'primary', 'kafunel_save_settings'); ?>
    </form>
</div>