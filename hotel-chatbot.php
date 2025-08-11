<?php
/*
Plugin Name: Hotel Chatbot
Description: Plugin de chatbot intelligent pour réservations hôtelières avec interface admin complète
Version: 2.0
Author: Hotel Chatbot Team
Text Domain: hotel-chatbot
*/

if (!defined('ABSPATH')) exit;

define('HOTEL_CHATBOT_PATH', plugin_dir_path(__FILE__));
define('HOTEL_CHATBOT_URL', plugin_dir_url(__FILE__));
define('HOTEL_CHATBOT_VERSION', '2.0');

class HotelChatbot {
    private $db_version = '1.0';
    
    public function __construct() {
        // Hooks d'activation/désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Scripts et styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_head', array($this, 'inject_dynamic_styles'));
        add_action('wp_footer', array($this, 'inject_dynamic_styles_footer'));
        
        // Shortcode
        add_shortcode('hotel_chatbot', array($this, 'chatbot_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_hotel_chatbot_message', array($this, 'handle_message'));
        add_action('wp_ajax_nopriv_hotel_chatbot_message', array($this, 'handle_message'));
        add_action('wp_ajax_hotel_chatbot_admin_message', array($this, 'handle_admin_message'));
        add_action('wp_ajax_hotel_chatbot_get_conversations', array($this, 'get_conversations'));
        add_action('wp_ajax_hotel_chatbot_get_conversation', array($this, 'get_conversation'));
        add_action('wp_ajax_hotel_chatbot_delete_conversation', array($this, 'delete_conversation'));
        add_action('wp_ajax_hotel_chatbot_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_hotel_chatbot_upload_avatar', array($this, 'handle_avatar_upload'));
        add_action('wp_ajax_hotel_chatbot_remove_avatar', array($this, 'handle_avatar_remove'));
        add_action('wp_ajax_hotel_chatbot_test_ajax', array($this, 'handle_test_ajax'));
        
        // Handlers AJAX pour gestion email-based
        add_action('wp_ajax_hotel_chatbot_check_existing_conversation', array($this, 'check_existing_conversation'));
        add_action('wp_ajax_nopriv_hotel_chatbot_check_existing_conversation', array($this, 'check_existing_conversation'));
        add_action('wp_ajax_hotel_chatbot_get_conversation_messages', array($this, 'get_conversation_messages'));
        add_action('wp_ajax_nopriv_hotel_chatbot_get_conversation_messages', array($this, 'get_conversation_messages'));
        add_action('wp_ajax_hotel_chatbot_end_conversation', array($this, 'end_conversation'));

        // Handler AJAX pour la migration des données sera ajouté plus bas
        
        // Menu admin
        add_action('admin_menu', 'hotel_chatbot_add_admin_menu');
        add_action('admin_init', array($this, 'init_settings'));
        
        // Initialiser la base de données
        add_action('init', array($this, 'init_database'));
        
        // Inclure les fichiers admin
        $this->include_admin_files();
    }
    
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
    }
    
    public function deactivate() {
        // Nettoyer si nécessaire
    }
    
    public function create_tables() {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
        $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table des conversations
        $sql1 = "CREATE TABLE $conversations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            client_name varchar(100) NOT NULL,
            client_email varchar(100),
            client_phone varchar(20),
            session_id varchar(64),
            language varchar(10) DEFAULT 'fr',
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_session_id (session_id)
        ) $charset_collate;";
        
        // Table des messages
        $sql2 = "CREATE TABLE $messages_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            conversation_id mediumint(9) NOT NULL,
            sender_type varchar(10) NOT NULL,
            message text NOT NULL,
            message_type varchar(20) DEFAULT 'text',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (conversation_id) REFERENCES $conversations_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }
    
    public function set_default_options() {
        $defaults = array(
            'hotel_chatbot_primary_color' => '#2563eb',
            'hotel_chatbot_secondary_color' => '#1e40af',
            'hotel_chatbot_position' => 'bottom-right',
            'hotel_chatbot_welcome_message_fr' => 'Bonjour ! Je suis votre assistant hôtelier. Comment puis-je vous aider avec votre réservation ?',
            'hotel_chatbot_welcome_message_en' => 'Hello! I am your hotel assistant. How can I help you with your reservation?',
            'hotel_chatbot_welcome_message_es' => '¡Hola! Soy tu asistente hotelero. ¿Cómo puedo ayudarte con tu reserva?',
            'hotel_chatbot_default_language' => 'fr',
            'hotel_chatbot_enable_multilingual' => '1',
            'hotel_chatbot_require_name' => '1',
            'hotel_chatbot_enable_sound' => '1',
            'hotel_chatbot_auto_open' => '0',
            'hotel_chatbot_backend_url' => 'http://localhost:3000',
            'hotel_chatbot_openai_api_key' => '',
            'hotel_chatbot_enable_ai' => '1',
            'hotel_chatbot_enable_cookies' => '1',
            'hotel_chatbot_cookie_expiration_days' => '30'
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    public function init_database() {
        // Vérifier si les tables existent
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
        if ($wpdb->get_var("SHOW TABLES LIKE '$conversations_table'") != $conversations_table) {
            $this->create_tables();
        } else {
            // Mettre à jour le schéma de la base de données si nécessaire
            $this->update_database_schema();
        }
    }
    
    public function update_database_schema() {
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
        
        // Vérifier si la colonne session_id existe
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM $conversations_table LIKE %s",
            'session_id'
        ));
        
        if (empty($column_exists)) {
            // Ajouter la colonne session_id
            $wpdb->query("ALTER TABLE $conversations_table ADD COLUMN session_id varchar(64) AFTER client_phone");
            $wpdb->query("ALTER TABLE $conversations_table ADD INDEX idx_session_id (session_id)");
            
            error_log('Hotel Chatbot: Colonne session_id ajoutée à la table conversations');
        }
    }
    
    public function include_admin_files() {
        if (is_admin()) {
            require_once HOTEL_CHATBOT_PATH . 'includes/admin-menu.php';
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        // Charger seulement sur les pages du plugin
        if (strpos($hook, 'hotel-chatbot') === false) {
            return;
        }
        
        wp_enqueue_style(
            'hotel-chatbot-admin-styles',
            HOTEL_CHATBOT_URL . 'assets/css/admin-styles.css',
            array(),
            HOTEL_CHATBOT_VERSION
        );
        
        wp_enqueue_style(
            'hotel-chatbot-admin',
            HOTEL_CHATBOT_URL . 'assets/css/admin.css',
            array(),
            HOTEL_CHATBOT_VERSION
        );
        
        // Nouveau fichier CSS pour les fonctionnalités avancées
        wp_enqueue_style(
            'hotel-chatbot-admin-enhanced',
            HOTEL_CHATBOT_URL . 'assets/css/admin-enhanced.css',
            array(),
            HOTEL_CHATBOT_VERSION
        );
        
        wp_enqueue_script(
            'hotel-chatbot-admin',
            HOTEL_CHATBOT_URL . 'assets/js/admin.js',
            array('jquery'),
            HOTEL_CHATBOT_VERSION,
            true
        );
        
        // Script pour la personnalisation des couleurs
        wp_enqueue_script(
            'hotel-chatbot-admin-colors',
            HOTEL_CHATBOT_URL . 'assets/js/admin-colors.js',
            array('jquery'),
            HOTEL_CHATBOT_VERSION,
            true
        );
        
        // Localiser le script pour AJAX
        wp_localize_script('hotel-chatbot-admin', 'hotelChatbotAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hotel_chatbot_admin_nonce')
        ));
    }

    public function enqueue_scripts() {
        // Styles du chatbot client
        wp_enqueue_style(
            'hotel-chatbot-client-style',
            HOTEL_CHATBOT_URL . 'assets/css/chatbot-client.css',
            array(),
            HOTEL_CHATBOT_VERSION
        );
        
        // Ajouter le CSS dynamique basé sur les paramètres
        $this->add_dynamic_styles();

        // Script du gestionnaire de cookies
        wp_enqueue_script(
            'hotel-chatbot-cookies',
            HOTEL_CHATBOT_URL . 'assets/js/chatbot-client-cookies.js',
            array(),
            HOTEL_CHATBOT_VERSION,
            true
        );

        // Script du chatbot client intelligent
        wp_enqueue_script(
            'hotel-chatbot-client-script',
            HOTEL_CHATBOT_URL . 'assets/js/chatbot-client.js',
            array('hotel-chatbot-cookies'),
            HOTEL_CHATBOT_VERSION,
            true
        );

        // Variables pour le JavaScript avec tous les paramètres
        wp_localize_script('hotel-chatbot-client-script', 'hotelChatbotAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hotel_chatbot_nonce'),
            'enableSound' => get_option('hotel_chatbot_enable_sound', '1'),
            'autoOpen' => get_option('hotel_chatbot_auto_open', '0'),
            'defaultLanguage' => get_option('hotel_chatbot_default_language', 'fr'),
            'requireName' => get_option('hotel_chatbot_require_name', '1'),
            'primaryColor' => get_option('hotel_chatbot_primary_color', '#2563eb'),
            'secondaryColor' => get_option('hotel_chatbot_secondary_color', '#1e40af'),
            'position' => get_option('hotel_chatbot_position', 'bottom-right'),
            'chatTitle' => get_option('hotel_chatbot_chat_title', 'Assistant Hôtel'),
            'typingDelay' => get_option('hotel_chatbot_typing_delay', 1500),
            'maxMessages' => get_option('hotel_chatbot_max_messages', 100),
            'avatarUrl' => get_option('hotel_chatbot_avatar_url', ''),
            'welcomeMessages' => array(
                'fr' => get_option('hotel_chatbot_welcome_message_fr', 'Bonjour ! Comment puis-je vous aider aujourd\'hui ?'),
                'en' => get_option('hotel_chatbot_welcome_message_en', 'Hello! How can I help you today?'),
                'es' => get_option('hotel_chatbot_welcome_message_es', '¡Hola! ¿Cómo puedo ayudarte hoy?'),
                'ar' => get_option('hotel_chatbot_welcome_message_ar', 'مرحبا! كيف يمكنني مساعدتك اليوم؟')
            ),
            // Paramètres des cookies pour la persistance des conversations
            'enableCookies' => get_option('hotel_chatbot_enable_cookies', '1'),
            'cookieExpirationDays' => get_option('hotel_chatbot_cookie_expiration_days', '30')
        ));
    }
    
    /**
     * Ajouter les styles dynamiques basés sur les paramètres admin
     */
    public function add_dynamic_styles() {
        $primary_color = get_option('hotel_chatbot_primary_color', '#2563eb');
        $secondary_color = get_option('hotel_chatbot_secondary_color', '#1e40af');
        $position = get_option('hotel_chatbot_position', 'bottom-right');
        
        // Récupérer toutes les couleurs personnalisées
        $bot_message_color = get_option('hotel_chatbot_bot_message_color', '#f3f4f6');
        $user_message_color = get_option('hotel_chatbot_user_message_color', '#3b82f6');
        $header_color = get_option('hotel_chatbot_header_color', '#2563eb');
        $floating_button_color = get_option('hotel_chatbot_floating_button_color', '#3b82f6');
        $send_button_color = get_option('hotel_chatbot_send_button_color', '#10b981');
        
        // Créer une couleur d'arrière-plan harmonieuse basée sur la couleur des messages bot
        $chat_background = $bot_message_color;
        
        // Générer le CSS dynamique (sans les balises <style>)
        $custom_css = "
        :root {
            --chatbot-primary-color: {$primary_color};
            --chatbot-secondary-color: {$secondary_color};
            --chatbot-primary-rgb: " . $this->hex_to_rgb($primary_color) . ";
            --chatbot-secondary-rgb: " . $this->hex_to_rgb($secondary_color) . ";
            --chatbot-bot-message-color: {$bot_message_color};
            --chatbot-user-message-color: {$user_message_color};
            --chatbot-header-color: {$header_color};
            --chatbot-floating-button-color: {$floating_button_color};
            --chatbot-send-button-color: {$send_button_color};
            --chatbot-chat-background: {$chat_background};
        }
        
        /* Couleurs personnalisées */
        .hotel-chatbot-container .chatbot-avatar,
        .hotel-chatbot-container .btn-primary {
            background: linear-gradient(135deg, {$primary_color}, {$secondary_color}) !important;
        }
        
        .hotel-chatbot-container .chatbot-header {
            background: linear-gradient(135deg, {$primary_color}, {$secondary_color}) !important;
        }
        
        .hotel-chatbot-container .message.bot .message-content {
            background-color: {$primary_color} !important;
            color: white !important;
        }
        
        .hotel-chatbot-container .quick-suggestions .suggestion-btn:hover {
            background-color: {$primary_color} !important;
            border-color: {$primary_color} !important;
        }
        
        .hotel-chatbot-container .send-button {
            background-color: {$primary_color} !important;
        }
        
        .hotel-chatbot-container .send-button:hover {
            background-color: {$secondary_color} !important;
        }";
        
        // Appliquer la position
        switch ($position) {
            case 'bottom-left':
                $custom_css .= "
                .hotel-chatbot-container {
                    bottom: 20px !important;
                    left: 20px !important;
                    right: auto !important;
                }";
                break;
            case 'bottom-right':
                $custom_css .= "
                .hotel-chatbot-container {
                    bottom: 20px !important;
                    right: 20px !important;
                    left: auto !important;
                }";
                break;
            case 'top-left':
                $custom_css .= "
                .hotel-chatbot-container {
                    top: 20px !important;
                    left: 20px !important;
                    right: auto !important;
                    bottom: auto !important;
                }";
                break;
            case 'top-right':
                $custom_css .= "
                .hotel-chatbot-container {
                    top: 20px !important;
                    right: 20px !important;
                    left: auto !important;
                    bottom: auto !important;
                }";
                break;
        }
        
        // Utiliser wp_add_inline_style pour injecter le CSS
        wp_add_inline_style('hotel-chatbot-client-style', $custom_css);
    }
    
    /**
     * Injecter les styles dynamiques dans le head de la page
     */
    public function inject_dynamic_styles() {
        // Récupérer les 7 couleurs essentielles uniquement
        $options = [
            'header'            => get_option('hotel_chatbot_header_color', '#2563eb'),
            'floating_btn'      => get_option('hotel_chatbot_floating_button_color', '#2563eb'),
            'send_btn'          => get_option('hotel_chatbot_send_button_color', '#10b981'),
            'user_msg'          => get_option('hotel_chatbot_user_message_color', '#3b82f6'),
            'bot_msg'           => get_option('hotel_chatbot_bot_message_color', '#f3f4f6'),
            'background'        => get_option('hotel_chatbot_background_color', '#ffffff'),
            'text'              => get_option('hotel_chatbot_text_color', '#374151'),
        ];
        
        // Variables dérivées pour la compatibilité
        $derived_options = [
            'borders'           => $this->adjust_brightness($options['background'], -20),
            'input_bg'          => $options['background'],
            'input_text'        => $options['text'],
            'input_border'      => $this->adjust_brightness($options['background'], -30),
            'close_btn'         => $this->adjust_brightness($options['header'], -40),
            'link'              => $options['header'],
            'error'             => '#ef4444', // couleur fixe pour les erreurs
            'success'           => $options['send_btn'], // utilise la couleur du bouton d'envoi
        ];
        
        // Fusionner les options principales et dérivées
        $all_options = array_merge($options, $derived_options);

        // Variables clés déjà utilisées dans le CSS client par défaut
        $legacy_primary = get_option('hotel_chatbot_primary_color', '#2563eb');
        $primary_color   = $options['header'] ?: $legacy_primary;
        $secondary_color = $options['header']; // utilise la même couleur que le header
        $success_color   = $all_options['success'];
        $error_color     = $all_options['error'];
        $position        = get_option('hotel_chatbot_position', 'bottom-right');

        // Générer une version plus sombre pour --primary-dark
        $primary_dark = $this->adjust_brightness($primary_color, -30);

        echo "<style id='hotel-chatbot-dynamic-styles'>\n";
        echo "/* Hotel Chatbot Dynamic Styles - Generated: " . date('Y-m-d H:i:s') . " */\n";
        echo ":root {\n";
        // Variables génériques du thème existant
        echo "    --primary-color: {$primary_color};\n";
        echo "    --primary-dark: {$primary_dark};\n";
        echo "    --success-color: {$success_color};\n";
        echo "    --error-color: {$error_color};\n";
        echo "    --background-white: {$options['background']};\n";
        echo "    --text-primary: {$options['text']};\n";
        echo "    --border-light: {$options['borders']};\n";

        // Variables spécifiques du système avancé
        foreach ($all_options as $key => $value) {
            echo "    --chatbot-{$key}-color: {$value};\n";
        }
        echo "}\n";

        // Styles CSS spécifiques avec haute spécificité pour forcer l'application
        echo "/* Couleurs personnalisées appliquées */\n";
        
        // Container principal et arrière-plan
        echo "html body .chatbot-container { background: {$options['background']} !important; color: {$options['text']} !important; }\n";
        
        // Header du chatbot
        echo "html body .chatbot-header { background: linear-gradient(135deg, {$options['header']}, " . $this->adjust_brightness($options['header'], -20) . ") !important; }\n";
        
        // Bouton flottant
        echo "html body .chatbot-floating-btn { background: {$options['floating_btn']} !important; }\n";
        echo "html body .chatbot-floating-btn .avatar-container { background: {$options['floating_btn']} !important; }\n";
        
        // Messages utilisateur et bot (classes génériques)
        echo "html body .message.user, html body .user-message, html body .message-user { background: {$options['user_msg']} !important; color: white !important; }\n";
        echo "html body .message.bot, html body .bot-message, html body .message-bot { background: {$options['bot_msg']} !important; color: {$options['text']} !important; }\n";
        
        // Boutons d'envoi
        echo "html body .send-btn, html body .chat-send, html body #send-button, html body .submit-btn { background: {$options['send_btn']} !important; }\n";
        
        // Zone de saisie
        echo "html body .message-input, html body #chat-input, html body .chat-input { background: {$options['background']} !important; color: {$options['text']} !important; border-color: " . $this->adjust_brightness($options['background'], -20) . " !important; }\n";
        
        // Boutons et liens
        echo "html body .chatbot-container .close-btn { color: rgba(255,255,255,0.8) !important; }\n";
        echo "html body .chatbot-container a { color: {$options['header']} !important; }\n";
        
        // Suggestions et boutons d'action
        echo "html body .suggestion-btn, html body .action-btn { background: {$options['background']} !important; color: {$options['text']} !important; border-color: " . $this->adjust_brightness($options['background'], -20) . " !important; }\n";
        echo "html body .suggestion-btn:hover, html body .action-btn:hover { background: {$options['header']} !important; color: white !important; }\n";
        
        // Messages d'état
        echo "html body .success-message, html body .message-success { background: {$options['send_btn']} !important; }\n";
        echo "html body .error-message, html body .message-error { background: #ef4444 !important; }\n";
        
        // Éléments spécifiques du chatbot
        echo "html body .chat-messages { background: {$options['background']} !important; }\n";
        echo "html body .chat-footer { background: {$options['background']} !important; border-top-color: " . $this->adjust_brightness($options['background'], -10) . " !important; }\n";
        echo "html body .welcome-message { background: {$options['bot_msg']} !important; color: {$options['text']} !important; }\n";
        echo "html body .typing-indicator { color: {$options['text']} !important; }\n";
        
        // Formulaire de consentement
        echo "html body .consent-form { background: {$options['background']} !important; color: {$options['text']} !important; }\n";
        echo "html body .consent-form input[type='text'] { background: {$options['background']} !important; color: {$options['text']} !important; border-color: " . $this->adjust_brightness($options['background'], -20) . " !important; }\n";
        echo "html body .consent-form .submit-btn { background: {$options['send_btn']} !important; }\n";
        
        // Suggestions et boutons d'action améliorés
        echo "html body .suggestions-container { background: {$options['background']} !important; }\n";
        echo "html body .suggestion-grid .suggestion-btn { background: {$options['background']} !important; color: {$options['text']} !important; border: 1px solid " . $this->adjust_brightness($options['background'], -15) . " !important; }\n";
        echo "html body .suggestion-grid .suggestion-btn:hover { background: {$options['header']} !important; color: white !important; }\n";
        
        // Scrollbar personnalisée
        echo "html body .chat-messages::-webkit-scrollbar-track { background: {$options['background']} !important; }\n";
        echo "html body .chat-messages::-webkit-scrollbar-thumb { background: " . $this->adjust_brightness($options['background'], -30) . " !important; }\n";
        
        // Avatar et indicateurs
        echo "html body .message-avatar { background: {$options['header']} !important; }\n";
        echo "html body .status-dot.online { background: {$options['send_btn']} !important; }\n";
        
        // JavaScript de secours pour forcer l'application des couleurs
        echo "</style>\n";
        echo "<script>\n";
        echo "document.addEventListener('DOMContentLoaded', function() {\n";
        echo "  const root = document.documentElement;\n";
        
        // Variables CSS principales
        foreach ($all_options as $key => $value) {
            echo "  root.style.setProperty('--chatbot-{$key}-color', '{$value}');\n";
        }
        echo "  root.style.setProperty('--primary-color', '{$primary_color}');\n";
        echo "  root.style.setProperty('--primary-dark', '{$primary_dark}');\n";
        echo "  root.style.setProperty('--success-color', '{$success_color}');\n";
        echo "  root.style.setProperty('--error-color', '{$error_color}');\n";
        echo "  root.style.setProperty('--background-white', '{$options['background']}');\n";
        echo "  root.style.setProperty('--text-primary', '{$options['text']}');\n";
        
        // Fonction pour appliquer les styles directement aux éléments
        echo "  function applyCustomColors() {\n";
        echo "    // Container principal\n";
        echo "    const container = document.querySelector('.chatbot-container');\n";
        echo "    if (container) {\n";
        echo "      container.style.backgroundColor = '{$options['background']}';\n";
        echo "      container.style.color = '{$options['text']}';\n";
        echo "    }\n";
        
        echo "    // Header\n";
        echo "    const header = document.querySelector('.chatbot-header');\n";
        echo "    if (header) {\n";
        echo "      header.style.background = 'linear-gradient(135deg, {$options['header']}, " . $this->adjust_brightness($options['header'], -20) . ")';\n";
        echo "    }\n";
        
        echo "    // Bouton flottant\n";
        echo "    const floatingBtn = document.querySelector('.chatbot-floating-btn');\n";
        echo "    if (floatingBtn) {\n";
        echo "      floatingBtn.style.backgroundColor = '{$options['floating_btn']}';\n";
        echo "    }\n";
        
        echo "    // Messages utilisateur\n";
        echo "    document.querySelectorAll('.message.user, .user-message, .message-user').forEach(el => {\n";
        echo "      el.style.backgroundColor = '{$options['user_msg']}';\n";
        echo "      el.style.color = 'white';\n";
        echo "    });\n";
        
        echo "    // Messages bot\n";
        echo "    document.querySelectorAll('.message.bot, .bot-message, .message-bot').forEach(el => {\n";
        echo "      el.style.backgroundColor = '{$options['bot_msg']}';\n";
        echo "      el.style.color = '{$options['text']}';\n";
        echo "    });\n";
        
        echo "    // Boutons d'envoi\n";
        echo "    document.querySelectorAll('.send-btn, .chat-send, #send-button, .submit-btn').forEach(el => {\n";
        echo "      el.style.backgroundColor = '{$options['send_btn']}';\n";
        echo "    });\n";
        
        echo "    // Zone de saisie\n";
        echo "    document.querySelectorAll('.message-input, #chat-input, .chat-input').forEach(el => {\n";
        echo "      el.style.backgroundColor = '{$options['background']}';\n";
        echo "      el.style.color = '{$options['text']}';\n";
        echo "    });\n";
        echo "  }\n";
        
        echo "  // Appliquer immédiatement\n";
        echo "  applyCustomColors();\n";
        
        echo "  // Observer pour les éléments ajoutés dynamiquement\n";
        echo "  const observer = new MutationObserver(function(mutations) {\n";
        echo "    mutations.forEach(function(mutation) {\n";
        echo "      if (mutation.type === 'childList') {\n";
        echo "        applyCustomColors();\n";
        echo "      }\n";
        echo "    });\n";
        echo "  });\n";
        
        echo "  // Observer le container du chatbot\n";
        echo "  const chatContainer = document.querySelector('.chatbot-container');\n";
        echo "  if (chatContainer) {\n";
        echo "    observer.observe(chatContainer, { childList: true, subtree: true });\n";
        echo "  }\n";
        
        echo "  // Appliquer aussi quand le chatbot s'ouvre\n";
        echo "  const floatingBtn = document.querySelector('.chatbot-floating-btn');\n";
        echo "  if (floatingBtn) {\n";
        echo "    floatingBtn.addEventListener('click', function() {\n";
        echo "      setTimeout(applyCustomColors, 100);\n";
        echo "    });\n";
        echo "  }\n";
        
        echo "});\n";
        echo "</script>\n";
        return;
        echo "}\n";
        
        echo "/* Couleurs personnalisées du chatbot */\n";
        echo "#chatbot-floating-btn .avatar-container,\n";
        echo ".chatbot-floating-btn .avatar-container,\n";
        echo ".btn-primary {\n";
        echo "    background: linear-gradient(135deg, {$primary_color}, {$secondary_color}) !important;\n";
        echo "}\n";
        
        echo ".chatbot-header {\n";
        echo "    background: linear-gradient(135deg, {$primary_color}, {$secondary_color}) !important;\n";
        echo "}\n";
        
        echo ".message.bot .message-content {\n";
        echo "    background-color: {$primary_color} !important;\n";
        echo "    color: white !important;\n";
        echo "}\n";
        
        echo ".quick-suggestions .suggestion-btn:hover {\n";
        echo "    background-color: {$primary_color} !important;\n";
        echo "    border-color: {$primary_color} !important;\n";
        echo "}\n";
        
        echo ".send-button {\n";
        echo "    background-color: {$primary_color} !important;\n";
        echo "}\n";
        
        echo ".send-button:hover {\n";
        echo "    background-color: {$secondary_color} !important;\n";
        echo "}\n";
        
        // Position du chatbot
        switch ($position) {
            case 'bottom-left':
                echo "#chatbot-container.chatbot-container {\n";
                echo "    bottom: 20px !important;\n";
                echo "    left: 20px !important;\n";
                echo "    right: auto !important;\n";
                echo "}\n";
                echo "#chatbot-floating-btn.chatbot-floating-btn {\n";
                echo "    bottom: 20px !important;\n";
                echo "    left: 20px !important;\n";
                echo "    right: auto !important;\n";
                echo "}\n";
                break;
            case 'bottom-right':
                echo "#chatbot-container.chatbot-container {\n";
                echo "    bottom: 20px !important;\n";
                echo "    right: 20px !important;\n";
                echo "    left: auto !important;\n";
                echo "}\n";
                echo "#chatbot-floating-btn.chatbot-floating-btn {\n";
                echo "    bottom: 20px !important;\n";
                echo "    right: 20px !important;\n";
                echo "    left: auto !important;\n";
                echo "}\n";
                break;
            case 'top-left':
                echo "#chatbot-container.chatbot-container {\n";
                echo "    top: 20px !important;\n";
                echo "    left: 20px !important;\n";
                echo "    right: auto !important;\n";
                echo "    bottom: auto !important;\n";
                echo "}\n";
                echo "#chatbot-floating-btn.chatbot-floating-btn {\n";
                echo "    top: 20px !important;\n";
                echo "    left: 20px !important;\n";
                echo "    right: auto !important;\n";
                echo "    bottom: auto !important;\n";
                echo "}\n";
                break;
            case 'top-right':
                echo "#chatbot-container.chatbot-container {\n";
                echo "    top: 20px !important;\n";
                echo "    right: 20px !important;\n";
                echo "    left: auto !important;\n";
                echo "    bottom: auto !important;\n";
                echo "}\n";
                echo "#chatbot-floating-btn.chatbot-floating-btn {\n";
                echo "    top: 20px !important;\n";
                echo "    right: 20px !important;\n";
                echo "    left: auto !important;\n";
                echo "    bottom: auto !important;\n";
                echo "}\n";
                break;
        }
        
        echo "</style>\n";
    }
    
    /**
     * Injecter les styles dynamiques dans le footer (backup method)
     */
    public function inject_dynamic_styles_footer() {
        $primary_color = get_option('hotel_chatbot_primary_color', '#2563eb');
        $secondary_color = get_option('hotel_chatbot_secondary_color', '#1e40af');
        $position = get_option('hotel_chatbot_position', 'bottom-right');
        
        echo "<style id='hotel-chatbot-dynamic-styles-footer'>\n";
        echo "/* Hotel Chatbot Dynamic Styles FOOTER - Generated: " . date('Y-m-d H:i:s') . " */\n";
        echo "/* TESTING: Primary: {$primary_color}, Secondary: {$secondary_color}, Position: {$position} */\n";
        
        // Test très visible
        echo "#chatbot-floating-btn {\n";
        echo "    background: {$primary_color} !important;\n";
        echo "    border: 3px solid {$secondary_color} !important;\n";
        echo "}\n";
        
        echo ".chatbot-header {\n";
        echo "    background: {$primary_color} !important;\n";
        echo "}\n";
        
        // Position très visible
        switch ($position) {
            case 'top-left':
                echo "#chatbot-container, #chatbot-floating-btn {\n";
                echo "    top: 50px !important;\n";
                echo "    left: 50px !important;\n";
                echo "    right: auto !important;\n";
                echo "    bottom: auto !important;\n";
                echo "}\n";
                break;
            case 'top-right':
                echo "#chatbot-container, #chatbot-floating-btn {\n";
                echo "    top: 50px !important;\n";
                echo "    right: 50px !important;\n";
                echo "    left: auto !important;\n";
                echo "    bottom: auto !important;\n";
                echo "}\n";
                break;
            case 'bottom-left':
                echo "#chatbot-container, #chatbot-floating-btn {\n";
                echo "    bottom: 50px !important;\n";
                echo "    left: 50px !important;\n";
                echo "    right: auto !important;\n";
                echo "}\n";
                break;
            default: // bottom-right
                echo "#chatbot-container, #chatbot-floating-btn {\n";
                echo "    bottom: 50px !important;\n";
                echo "    right: 50px !important;\n";
                echo "    left: auto !important;\n";
                echo "}\n";
                break;
        }
        
        echo "</style>\n";
    }

    public function chatbot_shortcode($atts) {
        $atts = shortcode_atts(array(
            'hotel_id' => '1',
            'title' => 'Assistant Hôtelier',
            'mode' => 'floating',
            'theme' => 'blue'
        ), $atts);
        
        // Charger le template HTML
        ob_start();
        include HOTEL_CHATBOT_PATH . 'templates/chatbot-embed.php';
        return ob_get_clean();
    }

    // Fonction pour traiter les messages
    public function handle_message() {
        check_ajax_referer('hotel_chatbot_nonce', 'nonce');
        
        $message = sanitize_text_field($_POST['message']);
        $client_name = sanitize_text_field($_POST['client_name']);
        $client_email = sanitize_email($_POST['client_email'] ?? '');
        $language = sanitize_text_field($_POST['language'] ?? 'fr');
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : null;
        
        // CORRECTION : Vérifier si l'email est dans le champ nom par erreur
        if (!empty($client_email) && $client_name === $client_email) {
            $client_name = 'Client'; // Utiliser un nom par défaut si l'email est dans le champ nom
        }
        
        // Si le nom contient un @ mais qu'on a déjà un email, utiliser un nom par défaut
        if (strpos($client_name, '@') !== false && !empty($client_email)) {
            $client_name = 'Client';
        }
        
        // Debug: Log des données reçues (après correction)
        error_log('Hotel Chatbot - Données reçues (après correction): ' . json_encode([
            'message' => $message,
            'client_name' => $client_name,
            'client_email' => $client_email,
            'language' => $language,
            'conversation_id' => $conversation_id
        ]));
        
        // Validation des données requises
        if (empty($message) || empty($client_name)) {
            error_log('Hotel Chatbot - Erreur: message ou client_name manquant');
            wp_send_json_error('Message ou nom du client manquant');
            return;
        }
        
        // Sauvegarder le message client et créer/récupérer la conversation
        $result = $this->save_client_message($message, $client_name, $language, $conversation_id, $client_email);
        
        if ($result && isset($result['conversation_id'])) {
            error_log('Hotel Chatbot - Conversation sauvegardée, ID: ' . $result['conversation_id']);
            
            try {
                $response = $this->generate_intelligent_response($message, $client_name, $language, $result['conversation_id']);
                error_log('Hotel Chatbot - Réponse générée: ' . substr($response, 0, 100) . '...');
                
                wp_send_json_success(array(
                    'response' => $response,
                    'conversation_id' => $result['conversation_id']
                ));
            } catch (Exception $e) {
                error_log('Hotel Chatbot - Erreur lors de la génération de réponse: ' . $e->getMessage());
                wp_send_json_error('Erreur lors de la génération de la réponse: ' . $e->getMessage());
            }
        } else {
            error_log('Hotel Chatbot - Erreur lors de la sauvegarde du message');
            wp_send_json_error('Erreur lors de la sauvegarde du message');
        }
    }

    // Fonction pour traiter les messages admin
    public function handle_admin_message() {
        check_ajax_referer('hotel_chatbot_admin_nonce', 'nonce');
        
        $conversation_id = intval($_POST['conversation_id']);
        $message = sanitize_text_field($_POST['message']);
        
        if (!$conversation_id || !$message) {
            wp_send_json_error('Paramètres manquants');
        }
        
        global $wpdb;
        $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
        
        // Insérer le message admin
        $result = $wpdb->insert(
            $messages_table,
            [
                'conversation_id' => $conversation_id,
                'message' => $message,
                'sender_type' => 'admin',
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s']
        );
        
        if ($result === false) {
            wp_send_json_error('Erreur lors de l\'enregistrement du message');
        }
        
        // Mettre à jour la conversation
        $wpdb->update(
            $wpdb->prefix . 'hotel_chatbot_conversations',
            ['updated_at' => current_time('mysql')],
            ['id' => $conversation_id],
            ['%s'],
            ['%d']
        );
        
        wp_send_json_success('Message envoyé avec succès');
    }

    // Fonction pour récupérer les conversations
    public function get_conversations() {
        check_ajax_referer('hotel_chatbot_nonce', 'nonce');
        
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
        
        $conversations = $wpdb->get_results(
            "SELECT * FROM $conversations_table ORDER BY updated_at DESC LIMIT 50"
        );
        
        wp_send_json_success($conversations);
    }

    // Fonction pour récupérer une conversation spécifique
    public function get_conversation() {
        check_ajax_referer('hotel_chatbot_admin_nonce', 'nonce');
        
        $conversation_id = intval($_POST['conversation_id']);
        if (!$conversation_id) {
            wp_send_json_error('ID de conversation manquant');
        }
        
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
        $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
        
        // Récupérer les informations de la conversation
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $conversations_table WHERE id = %d",
            $conversation_id
        ));
        
        if (!$conversation) {
            wp_send_json_error('Conversation non trouvée');
        }
        
        // Récupérer tous les messages de la conversation
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $messages_table WHERE conversation_id = %d ORDER BY created_at ASC",
            $conversation_id
        ));
        
        $response_data = [
            'conversation' => $conversation,
            'messages' => $messages
        ];
        
        wp_send_json_success($response_data);
    }
    
    // Fonction pour supprimer une conversation
    public function delete_conversation() {
        check_ajax_referer('hotel_chatbot_admin_nonce', 'nonce');
        
        $conversation_id = intval($_POST['conversation_id']);
        if (!$conversation_id) {
            wp_send_json_error('ID de conversation manquant');
        }
        
        global $wpdb;
        $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
        $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
        
        // Supprimer d'abord tous les messages de la conversation
        $messages_deleted = $wpdb->delete(
            $messages_table,
            ['conversation_id' => $conversation_id],
            ['%d']
        );
        
        // Ensuite supprimer la conversation
        $conversation_deleted = $wpdb->delete(
            $conversations_table,
            ['id' => $conversation_id],
            ['%d']
        );
        
        if ($conversation_deleted === false) {
            wp_send_json_error('Erreur lors de la suppression de la conversation');
        }
        
        wp_send_json_success([
            'message' => 'Conversation supprimée avec succès',
            'messages_deleted' => $messages_deleted,
            'conversation_deleted' => $conversation_deleted
        ]);
    }

    // Fonction pour sauvegarder un message client
    public function save_client_message($message, $client_name, $language, $conversation_id = null, $client_email = null) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
        $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
        
        // Créer ou récupérer la conversation
        if (!$conversation_id) {
            $conversation_data = array(
                'client_name' => $client_name,
                'language' => $language,
                'status' => 'active',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );
            
            // Ajouter l'email si fourni
            if (!empty($client_email)) {
                $conversation_data['client_email'] = $client_email;
            }
            
            $wpdb->insert($conversations_table, $conversation_data);
            $conversation_id = $wpdb->insert_id;
        } else {
            // Mettre à jour le timestamp de la conversation
            $wpdb->update(
                $conversations_table,
                array('updated_at' => current_time('mysql')),
                array('id' => $conversation_id)
            );
        }
        
        // Sauvegarder le message
        $result = $wpdb->insert(
            $messages_table,
            array(
                'conversation_id' => $conversation_id,
                'message' => $message,
                'sender_type' => 'client',
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result !== false) {
            return array(
                'success' => true,
                'conversation_id' => $conversation_id,
                'message_id' => $wpdb->insert_id
            );
        }
        
        return false;
    }

    // Fonction pour générer une réponse intelligente
    public function generate_intelligent_response($message, $client_name, $language, $conversation_id) {
        // Vérifier si l'IA est activée et si une clé API est configurée
        $enable_ai = get_option('hotel_chatbot_enable_ai', '1');
        $api_key = get_option('hotel_chatbot_openai_api_key', '');
        
        if ($enable_ai === '1' && !empty($api_key)) {
            // Utiliser ChatGPT
            $ai_response = $this->generate_ai_response($message, $client_name, $language, $conversation_id);
            if ($ai_response) {
                return $ai_response;
            }
        }
        
        // Fallback vers les réponses automatiques
        return $this->generate_fallback_response($message, $client_name, $language);
    }

    // Fonction pour générer une réponse IA avec OpenAI
    public function generate_ai_response($message, $client_name, $language, $conversation_id) {
        $api_key = get_option('hotel_chatbot_openai_api_key');
        
        error_log('Hotel Chatbot DEBUG - generate_ai_response appelée');
        error_log('- Message: ' . $message);
        error_log('- Client: ' . $client_name);
        error_log('- Langue: ' . $language);
        
        if (!$api_key) {
            error_log('Hotel Chatbot DEBUG - Pas de clé API trouvée dans generate_ai_response');
            return false;
        }
        
        error_log('Hotel Chatbot DEBUG - Clé API trouvée, longueur: ' . strlen($api_key));
        
        // Récupérer l'historique de la conversation
        $history = $this->get_conversation_history($conversation_id);
        error_log('Hotel Chatbot DEBUG - Historique récupéré, ' . count($history) . ' messages');
        
        // Préparer les messages pour l'API OpenAI
        $messages = array(
            array(
                'role' => 'system',
                'content' => $this->get_system_prompt($language, $client_name)
            )
        );
        
        // Ajouter l'historique
        foreach ($history as $msg) {
            $role = $msg->sender_type === 'client' ? 'user' : 'assistant';
            $messages[] = array(
                'role' => $role,
                'content' => $msg->message
            );
        }
        
        // Ajouter le message actuel
        $messages[] = array(
            'role' => 'user',
            'content' => $message
        );
        
        error_log('Hotel Chatbot DEBUG - Messages préparés pour OpenAI, total: ' . count($messages));
        
        // Appeler l'API OpenAI
        $ai_response = $this->call_openai_api($api_key, $messages);
        
        if ($ai_response) {
            error_log('Hotel Chatbot DEBUG - Réponse IA reçue: ' . substr($ai_response, 0, 100) . '...');
            // Sauvegarder la réponse du bot
            $this->save_bot_message($conversation_id, $ai_response);
            return $ai_response;
        } else {
            error_log('Hotel Chatbot DEBUG - Aucune réponse de OpenAI ou erreur');
        }
        
        return false;
    }

    // Fonction pour appeler l'API OpenAI avec sélection intelligente du modèle
    public function call_openai_api($api_key, $messages) {
        error_log('Hotel Chatbot DEBUG - call_openai_api appelée');
        error_log('- Nombre de messages: ' . count($messages));
        error_log('- Clé API longueur: ' . strlen($api_key));
        
        $url = 'https://api.openai.com/v1/chat/completions';
        
        // Analyser la complexité de la dernière question utilisateur
        $last_user_message = '';
        foreach (array_reverse($messages) as $msg) {
            if ($msg['role'] === 'user') {
                $last_user_message = $msg['content'];
                break;
            }
        }
        
        error_log('Hotel Chatbot DEBUG - Message utilisateur pour analyse: ' . $last_user_message);
        
        // Déterminer le modèle à utiliser selon la complexité
        $complexity = $this->analyze_question_complexity($last_user_message);
        $model = ($complexity === 'complex') ? 'gpt-4' : 'gpt-3.5-turbo';
        $max_tokens = ($complexity === 'complex') ? 300 : 150;
        
        error_log('Hotel Chatbot DEBUG - Complexité détectée: ' . $complexity);
        error_log('Hotel Chatbot DEBUG - Modèle sélectionné: ' . $model);
        error_log('Hotel Chatbot DEBUG - Max tokens: ' . $max_tokens);
        
        $data = array(
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $max_tokens,
            'temperature' => 0.7
        );
        
        error_log('Hotel Chatbot DEBUG - Données API préparées');
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 45 // Augmenté pour GPT-4
        ));
        
        error_log('Hotel Chatbot DEBUG - Appel API effectué');
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('Hotel Chatbot DEBUG - Erreur wp_remote_post: ' . $error_message);
            return false;
        }
        
        // Récupérer le code de statut HTTP
        $http_code = wp_remote_retrieve_response_code($response);
        error_log('Hotel Chatbot DEBUG - Code HTTP: ' . $http_code);
        
        $body = wp_remote_retrieve_body($response);
        error_log('Hotel Chatbot DEBUG - Corps de la réponse (premiers 500 chars): ' . substr($body, 0, 500));
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Hotel Chatbot DEBUG - Erreur JSON decode: ' . json_last_error_msg());
            return false;
        }
        
        // Vérifier si la réponse contient une erreur
        if (isset($data['error'])) {
            error_log('Hotel Chatbot DEBUG - Erreur API OpenAI: ' . json_encode($data['error']));
            return false;
        }
        
        if (isset($data['choices'][0]['message']['content'])) {
            $ai_response = $data['choices'][0]['message']['content'];
            error_log('Hotel Chatbot DEBUG - Réponse IA extraite: ' . substr($ai_response, 0, 200) . '...');
            return $ai_response;
        }
        
        error_log('Hotel Chatbot DEBUG - Structure de réponse inattendue: ' . json_encode($data));
        return false;
    }
        
    // Fonction de réponse de fallback avec logique intelligente et détection de langue
    private function generate_fallback_response($message, $client_name, $language) {
        error_log('Hotel Chatbot DEBUG - generate_fallback_response appelée avec message: ' . $message);
        
        // Détecter automatiquement la langue du message
        $detected_language = $this->detect_language($message);
        error_log('Hotel Chatbot DEBUG - Langue détectée: ' . $detected_language . ' (langue originale: ' . $language . ')');
        
        // Utiliser la langue détectée au lieu de celle passée en paramètre
        $final_language = $detected_language;
        
        // Détecter l'intention du message
        $intent = $this->detect_intent($message, $final_language);
        error_log('Hotel Chatbot DEBUG - Intention détectée: ' . $intent);
        
        // Obtenir la réponse selon l'intention et la langue détectée
        $response_data = $this->get_response_by_intent($intent, $final_language, $message);
        
        // Personnaliser la réponse avec le nom du client
        $personalized_message = str_replace('{client_name}', $client_name, $response_data['message']);
        
        error_log('Hotel Chatbot DEBUG - Réponse générée: ' . substr($personalized_message, 0, 100) . '...');
        
        return $personalized_message;
    }

    // Fonction pour récupérer l'historique d'une conversation
    public function get_conversation_history($conversation_id, $limit = 10) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $messages_table WHERE conversation_id = %d ORDER BY created_at DESC LIMIT %d",
            $conversation_id, $limit
        ));
    }
    // Fonction pour analyser la complexité d'une question
    private function analyze_question_complexity($message) {
        $message_lower = strtolower($message);
        
        // Mots-clés indiquant une question complexe
        $complex_keywords = array(
            'problème', 'réclamation', 'plainte', 'insatisfait', 'remboursement', 
            'litige', 'juridique', 'avocat', 'tribunal', 'dommages', 'compensation',
            'urgence', 'urgente', 'grave', 'sérieux', 'important', 'critique',
            'pourquoi', 'comment ça se fait', 'expliquez-moi', 'je ne comprends pas',
            'complexe', 'compliqué', 'détaillé', 'approfondi', 'analyse',
            'plusieurs', 'différents', 'comparaison', 'alternative', 'options',
            'problem', 'complaint', 'issue', 'unsatisfied', 'refund', 
            'dispute', 'legal', 'lawyer', 'court', 'damages', 'compensation',
            'urgent', 'emergency', 'serious', 'important', 'critical',
            'why', 'how come', 'explain', 'don\'t understand',
            'complex', 'complicated', 'detailed', 'thorough', 'analysis',
            'multiple', 'different', 'comparison', 'alternative', 'options'
        );

        // Vérifier la longueur du message (plus de 50 mots = complexe)
        $word_count = str_word_count($message);
        if ($word_count > 50) {
            return 'complex';
        }

        // Vérifier la présence de mots-clés complexes
        foreach ($complex_keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                return 'complex';
            }
        }

        // Vérifier la présence de plusieurs questions (points d'interrogation)
        if (substr_count($message, '?') > 1) {
            return 'complex';
        }

        return 'basic';
    }
    
    private function save_bot_message($conversation_id, $message) {
        global $wpdb;
        $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
        
        $wpdb->insert(
            $messages_table,
            array(
                'conversation_id' => $conversation_id,
                'message' => $message,
                'sender_type' => 'bot',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
    
    // Fonction pour détecter automatiquement la langue du message (support universel)
    private function detect_language($message) {
        $message_lower = strtolower($message);
        
        // Mots-clés caractéristiques par langue (étendu)
        $language_indicators = array(
            'fr' => array('bonjour', 'salut', 'bonsoir', 'merci', 'oui', 'non', 'chambre', 'réservation', 'prix', 'tarif', 'hôtel', 'service', 'je', 'vous', 'nous', 'avec', 'pour', 'dans', 'sur', 'slm', 'salam', 'bsr', 'bjr'),
            'en' => array('hello', 'hi', 'good', 'thank', 'yes', 'no', 'room', 'booking', 'price', 'hotel', 'service', 'with', 'for', 'the', 'and', 'you', 'we', 'can', 'will', 'morning', 'evening'),
            'es' => array('hola', 'buenos', 'gracias', 'sí', 'no', 'habitación', 'reserva', 'precio', 'hotel', 'servicio', 'con', 'para', 'en', 'de', 'que', 'el', 'la', 'días', 'tardes'),
            'ar' => array('سلام', 'مرحبا', 'أهلا', 'شكرا', 'نعم', 'لا', 'غرفة', 'حجز', 'سعر', 'فندق', 'خدمة', 'مع', 'في', 'من', 'على', 'صباح', 'مساء'),
            'darija' => array('salam', 'ahlan', 'marhaba', 'shukran', 'wakha', 'la', 'bit', 'chambre', 'hotel', 'fhel', 'bghit', 'bgha', 'kif', 'kifash', 'fin', 'fein', 'chhal', 'bezaf', 'zwina', 'mzyan', 'baraka', 'allah', 'inshallah', 'makayn', 'kayn', 'walu', 'hna', 'ghi', 'ghir', 'daba', 'gheda', 'lyoum', 'nhar', 'lila', 'sbah', 'masa', 'prix', 'tarif', 'flous', 'derham', 'euro'),
            'de' => array('hallo', 'guten', 'danke', 'ja', 'nein', 'zimmer', 'buchung', 'preis', 'hotel', 'service', 'mit', 'für', 'in', 'und', 'der', 'die', 'morgen', 'abend'),
            'it' => array('ciao', 'buongiorno', 'grazie', 'sì', 'no', 'camera', 'prenotazione', 'prezzo', 'hotel', 'servizio', 'con', 'per', 'in', 'di', 'che', 'il', 'la', 'giorno', 'sera'),
            'pt' => array('olá', 'oi', 'bom', 'obrigado', 'sim', 'não', 'quarto', 'reserva', 'preço', 'hotel', 'serviço', 'com', 'para', 'em', 'de', 'que', 'o', 'a'),
            'ru' => array('привет', 'здравствуйте', 'спасибо', 'да', 'нет', 'номер', 'бронирование', 'цена', 'отель', 'сервис', 'с', 'для', 'в', 'и', 'вы', 'мы'),
            'zh' => array('你好', '您好', '谢谢', '是', '不', '房间', '预订', '价格', '酒店', '服务', '和', '的', '在', '有', '我', '您'),
            'ja' => array('こんにちは', 'ありがとう', 'はい', 'いいえ', '部屋', '予約', '価格', 'ホテル', 'サービス', 'と', 'の', 'に', 'で', 'です', 'ます'),
            'ko' => array('안녕하세요', '감사합니다', '네', '아니오', '방', '예약', '가격', '호텔', '서비스', '와', '의', '에', '에서', '입니다'),
            'nl' => array('hallo', 'dank', 'ja', 'nee', 'kamer', 'reservering', 'prijs', 'hotel', 'service', 'met', 'voor', 'in', 'en', 'de', 'het'),
            'tr' => array('merhaba', 'teşekkür', 'evet', 'hayır', 'oda', 'rezervasyon', 'fiyat', 'otel', 'hizmet', 'ile', 'için', 've', 'bir', 'bu'),
            'pl' => array('cześć', 'dziękuję', 'tak', 'nie', 'pokój', 'rezerwacja', 'cena', 'hotel', 'usługa', 'z', 'dla', 'w', 'i', 'to'),
            'sv' => array('hej', 'tack', 'ja', 'nej', 'rum', 'bokning', 'pris', 'hotell', 'service', 'med', 'för', 'i', 'och', 'det'),
            'da' => array('hej', 'tak', 'ja', 'nej', 'værelse', 'booking', 'pris', 'hotel', 'service', 'med', 'for', 'i', 'og', 'det'),
            'no' => array('hei', 'takk', 'ja', 'nei', 'rom', 'bestilling', 'pris', 'hotell', 'service', 'med', 'for', 'i', 'og', 'det'),
            'fi' => array('hei', 'kiitos', 'kyllä', 'ei', 'huone', 'varaus', 'hinta', 'hotelli', 'palvelu', 'kanssa', 'varten', 'ja', 'se'),
            'hi' => array('नमस्ते', 'धन्यवाद', 'हाँ', 'नहीं', 'कमरा', 'बुकिंग', 'कीमत', 'होटल', 'सेवा', 'के', 'में', 'और', 'है'),
            'th' => array('สวัสดี', 'ขอบคุณ', 'ใช่', 'ไม่', 'ห้อง', 'จอง', 'ราคา', 'โรงแรม', 'บริการ', 'กับ', 'ใน', 'และ', 'ที่'),
            'vi' => array('xin chào', 'cảm ơn', 'có', 'không', 'phòng', 'đặt', 'giá', 'khách sạn', 'dịch vụ', 'với', 'trong', 'và', 'của')
        );
        
        $language_scores = array();
        
        // Calculer le score pour chaque langue
        foreach ($language_indicators as $lang => $words) {
            $score = 0;
            foreach ($words as $word) {
                if (strpos($message_lower, $word) !== false) {
                    $score++;
                }
            }
            $language_scores[$lang] = $score;
        }
        
        // Retourner la langue avec le meilleur score
        $max_score = max($language_scores);
        $detected_language = array_keys($language_scores, $max_score)[0];
        
        // Si aucun mot n'est détecté, analyser les caractères pour détecter d'autres langues
        if ($max_score == 0) {
            $detected_language = $this->detect_language_by_script($message);
        }
        
        error_log('Hotel Chatbot DEBUG - Langue détectée: ' . $detected_language . ' (scores: ' . json_encode($language_scores) . ')');
        
        return $detected_language;
    }
    
    // Fonction pour générer une réponse IA pour les langues non supportées
    private function generate_ai_response_for_unsupported_language($intent, $language, $message) {
        // Créer un prompt spécial pour les langues non supportées
        $universal_prompt = $this->get_universal_system_prompt($language);
        
        // Essayer d'utiliser l'IA si disponible
        $api_key = get_option('hotel_chatbot_openai_api_key', '');
        if (!empty($api_key)) {
            $messages = array(
                array(
                    'role' => 'system',
                    'content' => $universal_prompt
                ),
                array(
                    'role' => 'user',
                    'content' => $message
                )
            );
            
            $ai_response = $this->call_openai_api($api_key, $messages);
            if ($ai_response) {
                return array(
                    'message' => $ai_response,
                    'suggestions' => array()
                );
            }
        }
        
        // Fallback : réponse générique dans la langue détectée
        return $this->get_universal_fallback_response($intent, $language);
    }
    
    // Fonction pour générer un prompt universel selon la langue
    private function get_universal_system_prompt($language) {
        $language_names = array(
            'pt' => 'português',
            'ru' => 'русский',
            'zh' => '中文',
            'ja' => '日本語',
            'ko' => '한국어',
            'nl' => 'Nederlands',
            'tr' => 'Türkçe',
            'pl' => 'polski',
            'sv' => 'svenska',
            'da' => 'dansk',
            'no' => 'norsk',
            'fi' => 'suomi',
            'hi' => 'हिन्दी',
            'th' => 'ไทย',
            'vi' => 'Tiếng Việt'
        );
        
        $lang_name = isset($language_names[$language]) ? $language_names[$language] : $language;
        
        return "You are the professional hotel assistant for Hotel Excellence, a 4-star establishment. Respond ONLY in $lang_name language. Your role is to help guests with hotel reservations and information.\n\nHOTEL INFORMATION:\n- Rooms: single (from €80/night), double (from €120/night), family suite (from €200/night)\n- Services: outdoor pool, free WiFi, buffet breakfast, free parking, spa & wellness, fitness center\n- Check-in: 3:00 PM | Check-out: 11:00 AM\n- Contact: +33 1 23 45 67 89 | Email: contact@hotel.com\n\nINSTRUCTIONS:\n1. Respond warmly and professionally in $lang_name\n2. Provide helpful hotel information\n3. Limit responses to 200 words maximum\n4. Focus on hotel services and reservations only\n5. Be conversational and natural";
    }
    
    // Fonction pour générer une réponse de fallback universelle
    private function get_universal_fallback_response($intent, $language) {
        // Réponses de base dans quelques langues supplémentaires
        $universal_responses = array(
            'pt' => array(
                'message' => 'Olá! Sou o assistente do Hotel Excellence. Posso ajudá-lo com informações sobre nossos quartos, serviços e reservas. Como posso ajudá-lo hoje?',
                'suggestions' => array('Reservar quarto', 'Ver preços', 'Serviços', 'Contato')
            ),
            'ru' => array(
                'message' => 'Привет! Я ассистент отеля Excellence. Могу помочь с информацией о номерах, услугах и бронировании. Как могу помочь?',
                'suggestions' => array('Забронировать', 'Цены', 'Услуги', 'Контакты')
            ),
            'zh' => array(
                'message' => '您好！我是卓越酒店的助手。我可以帮助您了解我们的房间、服务和预订信息。今天我可以为您做什么？',
                'suggestions' => array('预订房间', '查看价格', '服务介绍', '联系我们')
            ),
            'ja' => array(
                'message' => 'こんにちは！ホテルエクセレンスのアシスタントです。お部屋、サービス、ご予約についてお手伝いいたします。今日はいかがでしょうか？',
                'suggestions' => array('予約する', '料金を見る', 'サービス', 'お問い合わせ')
            ),
            'ko' => array(
                'message' => '안녕하세요! 호텔 엑셀런스의 어시스턴트입니다. 객실, 서비스, 예약에 대해 도와드릴 수 있습니다. 오늘 어떻게 도와드릴까요?',
                'suggestions' => array('객실 예약', '요금 보기', '서비스', '문의하기')
            ),
            'darija' => array(
                'message' => 'Salam! Ana l-assistant dyal Hotel Excellence. Ymken naawenek b les informations 3la les chambres, les services o les réservations dyalna. Kifash ymken n3awnek lyoum?',
                'suggestions' => array('Réserver chambre', 'Chuf les prix', 'Les services', 'Contacti-na')
            )
        );
        
        // Si on a une réponse prédéfinie pour cette langue, l'utiliser
        if (isset($universal_responses[$language])) {
            return $universal_responses[$language];
        }
        
        // Sinon, utiliser une réponse générique en anglais
        return array(
            'message' => 'Hello! I am the assistant for Hotel Excellence. I can help you with information about our rooms, services and reservations. How can I help you today?',
            'suggestions' => array('Book room', 'View prices', 'Services', 'Contact us')
        );
    }
    
    // Fonction pour détecter la langue par script/alphabet
    private function detect_language_by_script($message) {
        // Détecter par script/alphabet pour les langues non reconnues par mots-clés
        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $message)) {
            return 'zh'; // Chinois
        }
        if (preg_match('/[\x{3040}-\x{309f}\x{30a0}-\x{30ff}]/u', $message)) {
            return 'ja'; // Japonais
        }
        if (preg_match('/[\x{ac00}-\x{d7af}]/u', $message)) {
            return 'ko'; // Coréen
        }
        if (preg_match('/[\x{0400}-\x{04ff}]/u', $message)) {
            return 'ru'; // Russe/Cyrillique
        }
        if (preg_match('/[\x{0600}-\x{06ff}]/u', $message)) {
            return 'ar'; // Arabe
        }
        if (preg_match('/[\x{0e00}-\x{0e7f}]/u', $message)) {
            return 'th'; // Thaï
        }
        if (preg_match('/[\x{0900}-\x{097f}]/u', $message)) {
            return 'hi'; // Hindi
        }
        
        // Par défaut, utiliser le français
        return 'fr';
    }
    
    private function detect_intent($message, $language) {
        $message_lower = strtolower($message);
        
        // Mots-clés pour différentes intentions selon la langue
        $keywords = array(
            'fr' => array(
                'reservation' => array('réserver', 'réservation', 'booking', 'chambre', 'disponibilité'),
                'price' => array('prix', 'tarif', 'coût', 'combien', 'euro'),
                'amenities' => array('service', 'piscine', 'wifi', 'petit-déjeuner', 'parking'),
                'location' => array('adresse', 'où', 'localisation', 'transport'),
                'greeting' => array('bonjour', 'salut', 'hello', 'bonsoir')
            ),
            'en' => array(
                'reservation' => array('book', 'booking', 'reserve', 'room', 'availability'),
                'price' => array('price', 'cost', 'rate', 'how much', 'dollar'),
                'amenities' => array('service', 'pool', 'wifi', 'breakfast', 'parking'),
                'location' => array('address', 'where', 'location', 'transport'),
                'greeting' => array('hello', 'hi', 'good morning', 'good evening')
            ),
            'es' => array(
                'reservation' => array('reservar', 'reserva', 'habitación', 'disponibilidad'),
                'price' => array('precio', 'tarifa', 'costo', 'cuánto', 'euro'),
                'amenities' => array('servicio', 'piscina', 'wifi', 'desayuno', 'parking'),
                'location' => array('dirección', 'dónde', 'ubicación', 'transporte'),
                'greeting' => array('hola', 'buenos días', 'buenas tardes')
            ),
            'ar' => array(
                'reservation' => array('حجز', 'حجوزات', 'غرفة', 'بغيت', 'نحجز'),
                'price' => array('سعر', 'ثمن', 'فلوس', 'بشحال', 'كم'),
                'amenities' => array('خدمات', 'مسبح', 'وايفاي', 'فطور', 'باركينغ'),
                'location' => array('عنوان', 'فين', 'موقع', 'مواصلات'),
                'greeting' => array('سلام', 'أهلا', 'مرحبا', 'صباح الخير')
            ),
            'de' => array(
                'reservation' => array('buchen', 'reservierung', 'zimmer', 'verfügbarkeit'),
                'price' => array('preis', 'kosten', 'tarif', 'wieviel', 'euro'),
                'amenities' => array('service', 'pool', 'wifi', 'frühstück', 'parkplatz'),
                'location' => array('adresse', 'wo', 'lage', 'transport'),
                'greeting' => array('hallo', 'guten tag', 'guten morgen', 'guten abend')
            ),
            'it' => array(
                'reservation' => array('prenotare', 'prenotazione', 'camera', 'disponibilità'),
                'price' => array('prezzo', 'costo', 'tariffa', 'quanto', 'euro'),
                'amenities' => array('servizio', 'piscina', 'wifi', 'colazione', 'parcheggio'),
                'location' => array('indirizzo', 'dove', 'posizione', 'trasporto'),
                'greeting' => array('ciao', 'buongiorno', 'buonasera', 'salve')
            )
        );
        
        $lang_keywords = $keywords[$language] ?? $keywords['fr'];
        
        foreach ($lang_keywords as $intent => $words) {
            foreach ($words as $word) {
                if (strpos($message_lower, $word) !== false) {
                    return $intent;
                }
            }
        }
        
        return 'general';
    }
    
    private function get_response_by_intent($intent, $language, $message) {
        // Langues avec réponses prédéfinies
        $supported_languages = array('fr', 'en', 'es', 'ar', 'de', 'it');
        
        // Si la langue n'est pas supportée, utiliser l'IA pour générer une réponse
        if (!in_array($language, $supported_languages)) {
            return $this->generate_ai_response_for_unsupported_language($intent, $language, $message);
        }
        
        $responses = array(
            'fr' => array(
                'greeting' => array(
                    'message' => 'Bonjour ! Je suis votre assistant hôtelier intelligent. Pour mieux vous aider, pourriez-vous me donner votre nom ?',
                    'suggestions' => []
                ),
                'reservation' => array(
                    'message' => 'Parfait {client_name} ! Je vais vous aider avec votre réservation. Nous avons plusieurs types de chambres disponibles : simples, doubles, et suites familiales. Nos tarifs varient selon la saison et nous proposons des promotions régulières. Pour mieux vous conseiller, pourriez-vous me préciser vos dates de séjour et le nombre de personnes ?',
                    'suggestions' => [
                        'Voir nos chambres',
                        'Tarifs actuels',
                        'Promotions en cours',
                        'Vérifier disponibilités'
                    ]
                ),
                'price' => array(
                    'message' => 'Nos tarifs sont très attractifs {client_name} ! Chambre simple : à partir de 80€/nuit, Chambre double : à partir de 120€/nuit, Suite familiale : à partir de 200€/nuit. Tous nos prix incluent le WiFi gratuit et l\'accès à nos équipements. Nous avons souvent des promotions selon la période !',
                    'suggestions' => [
                        'Promotions actuelles',
                        'Ce qui est inclus',
                        'Tarifs par saison',
                        'Demander un devis'
                    ]
                ),
                'amenities' => array(
                    'message' => 'Notre hôtel 4 étoiles propose : 🏈 Piscine extérieure, 📶 WiFi gratuit, 🍳 Petit-déjeuner buffet, 🅿️ Parking gratuit, 🛀 Spa & Wellness, 🏋️‍♀️ Salle de sport.',
                    'suggestions' => [
                        'Horaires piscine',
                        'Menu petit-déjeuner',
                        'Services spa',
                        'Autres services'
                    ]
                ),
                'location' => array(
                    'message' => 'Notre hôtel est idéalement situé au cœur de la ville, à 5 min à pied des principaux sites touristiques et à 2 min de la station de métro.',
                    'suggestions' => [
                        'Comment nous rejoindre',
                        'Attractions à proximité',
                        'Transports publics',
                        'Parking et accès'
                    ]
                ),
                'contact' => array(
                    'message' => 'Vous pouvez nous contacter 24h/24 : 📞 +33 1 23 45 67 89, 📧 contact@hotel.com. Notre équipe est toujours disponible pour vous aider !',
                    'suggestions' => [
                        'Horaires réception',
                        'Service client',
                        'Urgences 24h/24',
                        'Réservation téléphonique'
                    ]
                ),
                'general' => array(
                    'message' => 'Bonjour {client_name} ! Je suis l\'assistant de l\'Hôtel Excellence. Je suis là pour vous aider avec toutes vos questions concernant nos chambres, services, tarifs et réservations. Comment puis-je vous aider aujourd\'hui ?',
                    'suggestions' => [
                        'Réserver une chambre',
                        'Voir nos tarifs',
                        'Services disponibles',
                        'Informations pratiques'
                    ]
                )
            ),
            'en' => array(
                'greeting' => array(
                    'message' => 'Hello! I am your intelligent hotel assistant. To better help you, could you please tell me your name?',
                    'suggestions' => []
                ),
                'reservation' => array(
                    'message' => 'Perfect {client_name}! I\'ll help you with your reservation. We have several room types available: single, double, and family suites. Our rates vary by season and we offer regular promotions. To better assist you, could you please specify your travel dates and number of guests?',
                    'suggestions' => [
                        'View our rooms',
                        'Current rates',
                        'Current promotions',
                        'Check availability'
                    ]
                ),
                'price' => array(
                    'message' => 'Our rates are very attractive {client_name}! Single room: from €80/night, Double room: from €120/night, Family suite: from €200/night. All prices include free WiFi and access to our facilities. We often have promotions depending on the period!',
                    'suggestions' => [
                        'Current promotions',
                        'What\'s included',
                        'Seasonal rates',
                        'Request a quote'
                    ]
                ),
                'amenities' => array(
                    'message' => 'Our 4-star hotel offers: 🏈 Outdoor pool, 📶 Free WiFi, 🍳 Buffet breakfast, 🅿️ Free parking, 🛀 Spa & Wellness, 🏋️‍♀️ Fitness center.',
                    'suggestions' => [
                        'Pool hours',
                        'Breakfast menu',
                        'Spa services',
                        'Other services'
                    ]
                ),
                'location' => array(
                    'message' => 'Our hotel is ideally located in the heart of the city, 5 minutes walk from main tourist sites and 2 minutes from the metro station.',
                    'suggestions' => [
                        'How to reach us',
                        'Nearby attractions',
                        'Public transport',
                        'Parking & access'
                    ]
                ),
                'contact' => array(
                    'message' => 'You can contact us 24/7: 📞 +33 1 23 45 67 89, 📧 contact@hotel.com. Our team is always available to help you!',
                    'suggestions' => [
                        'Reception hours',
                        'Customer service',
                        '24/7 emergency',
                        'Phone booking'
                    ]
                ),
                'general' => array(
                    'message' => 'Hello {client_name}! I am the assistant for Hotel Excellence. I\'m here to help you with all your questions about our rooms, services, rates and reservations. How can I help you today?',
                    'suggestions' => [
                        'Book a room',
                        'View our rates',
                        'Available services',
                        'Practical information'
                    ]
                )
            ),
            'es' => array(
                'greeting' => array(
                    'message' => '¡Hola! Soy tu asistente hotelero inteligente. Para ayudarte mejor, ¿podrías decirme tu nombre?',
                    'suggestions' => []
                ),
                'reservation' => array(
                    'message' => '¡Perfecto {client_name}! Te ayudaré con tu reserva. Tenemos varios tipos de habitaciones disponibles: individuales, dobles y suites familiares. Nuestras tarifas varían según la temporada y ofrecemos promociones regulares. Para aconsejarte mejor, ¿podrías especificar tus fechas de estancia y el número de huéspedes?',
                    'suggestions' => [
                        'Ver nuestras habitaciones',
                        'Tarifas actuales',
                        'Promociones vigentes',
                        'Verificar disponibilidad'
                    ]
                ),
                'price' => array(
                    'message' => '¡Nuestras tarifas son muy atractivas {client_name}! Habitación individual: desde 80€/noche, Habitación doble: desde 120€/noche, Suite familiar: desde 200€/noche. Todos los precios incluyen WiFi gratuito y acceso a nuestras instalaciones. ¡A menudo tenemos promociones según el período!',
                    'suggestions' => [
                        'Promociones actuales',
                        'Qué está incluido',
                        'Tarifas por temporada',
                        'Solicitar presupuesto'
                    ]
                ),
                'amenities' => array(
                    'message' => 'Nuestro hotel 4 estrellas ofrece: 🏈 Piscina exterior, 📶 WiFi gratuito, 🍳 Desayuno buffet, 🅿️ Parking gratuito, 🛀 Spa & Wellness, 🏋️‍♀️ Gimnasio.',
                    'suggestions' => [
                        'Horarios piscina',
                        'Menú desayuno',
                        'Servicios spa',
                        'Otros servicios'
                    ]
                ),
                'location' => array(
                    'message' => 'Nuestro hotel está idealmente ubicado en el corazón de la ciudad, a 5 minutos a pie de los principales sitios turísticos y a 2 minutos de la estación de metro.',
                    'suggestions' => [
                        'Cómo llegar',
                        'Atracciones cercanas',
                        'Transporte público',
                        'Parking y acceso'
                    ]
                ),
                'contact' => array(
                    'message' => 'Puedes contactarnos 24/7: 📞 +33 1 23 45 67 89, 📧 contact@hotel.com. ¡Nuestro equipo siempre está disponible para ayudarte!',
                    'suggestions' => [
                        'Horarios recepción',
                        'Servicio al cliente',
                        'Emergencias 24h',
                        'Reserva telefónica'
                    ]
                ),
                'general' => array(
                    'message' => '¡Hola {client_name}! Soy el asistente del Hotel Excellence. Estoy aquí para ayudarte con todas tus preguntas sobre nuestras habitaciones, servicios, tarifas y reservas. ¿Cómo puedo ayudarte hoy?',
                    'suggestions' => [
                        'Reservar una habitación',
                        'Ver nuestras tarifas',
                        'Servicios disponibles',
                        'Información práctica'
                    ]
                )
            ),
            'ar' => array(
                'greeting' => array(
                    'message' => 'مرحبا ! أنا مساعدك الذكي في الفندق. باش نقدر نعاونك مزيان، ممكن تقول لي سميتك؟',
                    'suggestions' => []
                ),
                'reservation' => array(
                    'message' => 'زوين ! غادي نعاونك ف الحجز. عندنا غرف متاحة طول العام بأسعار مناسبة حسب الموسم.',
                    'suggestions' => [
                        'شوف الغرف المتاحة',
                        'أنواع الغرف',
                        'الأسعار والعروض',
                        'احجز دابا'
                    ]
                ),
                'price' => array(
                    'message' => 'أسعارنا منافسة بزاف ! غرفة فردية: 80€/ليلة، غرفة مزدوجة: 120€/ليلة، جناح: 200€/ليلة. عروض خاصة للإقامة أكثر من 3 ليالي.',
                    'suggestions' => [
                        'شوف العروض',
                        'قارن الغرف',
                        'شنو داخل ف الثمن',
                        'احجز دابا'
                    ]
                ),
                'amenities' => array(
                    'message' => 'فندقنا 4 نجوم فيه: 🏈 مسبح خارجي، 📶 وايفاي مجاني، 🍳 فطور بوفيه، 🅿️ باركينغ مجاني، 🛀 سبا وعلاج، 🏋️‍♀️ قاعة رياضة.',
                    'suggestions' => [
                        'مواعيد المسبح',
                        'قائمة الفطور',
                        'خدمات السبا',
                        'خدمات أخرى'
                    ]
                ),
                'location' => array(
                    'message' => 'فندقنا ف موقع ممتاز ف وسط المدينة، 5 دقايق مشي من المعالم السياحية و 2 دقايق من محطة الميترو.',
                    'suggestions' => [
                        'كيفاش توصل لعندنا',
                        'معالم قريبة',
                        'المواصلات العمومية',
                        'الباركينغ والوصول'
                    ]
                ),
                'contact' => array(
                    'message' => 'يمكنك تتصل بينا 24/7: 📞 +33 1 23 45 67 89، 📧 contact@hotel.com. فريقنا دايما متاح باش يعاونك!',
                    'suggestions' => [
                        'مواعيد الاستقبال',
                        'خدمة العملاء',
                        'طوارئ 24 ساعة',
                        'حجز بالتليفون'
                    ]
                ),
                'general' => array(
                    'message' => 'أنا مساعدك الذكي ف الفندق ! يمكنني نعاونك ف الحجوزات، نعطيك معلومات على خدماتنا وأسعارنا.',
                    'suggestions' => [
                        'دير حجز',
                        'شوف الأسعار',
                        'خدمات الفندق',
                        'تواصل معنا'
                    ]
                )
            ),
            'de' => array(
                'greeting' => array(
                    'message' => 'Hallo ! Ich bin Ihr intelligenter Hotel-Assistent. Um Ihnen besser helfen zu können, könnten Sie mir bitte Ihren Namen sagen?',
                    'suggestions' => []
                ),
                'reservation' => array(
                    'message' => 'Perfekt ! Ich helfe Ihnen gerne bei Ihrer Reservierung. Unsere Zimmer sind das ganze Jahr über verfügbar mit Vorzugspreisen je nach Saison.',
                    'suggestions' => [
                        'Verfügbarkeit prüfen',
                        'Zimmertypen',
                        'Preise & Angebote',
                        'Jetzt buchen'
                    ]
                ),
                'price' => array(
                    'message' => 'Unsere Preise sind sehr wettbewerbsfähig ! Einzelzimmer: 80€/Nacht, Doppelzimmer: 120€/Nacht, Suite: 200€/Nacht. Spezielle Angebote für Aufenthalte über 3 Nächte.',
                    'suggestions' => [
                        'Angebote ansehen',
                        'Zimmer vergleichen',
                        'Was ist inbegriffen',
                        'Jetzt buchen'
                    ]
                ),
                'amenities' => array(
                    'message' => 'Unser 4-Sterne-Hotel bietet: 🏈 Außenpool, 📶 Kostenloses WLAN, 🍳 Buffet-Frühstück, 🅿️ Kostenloser Parkplatz, 🛀 Spa & Wellness, 🏋️‍♀️ Fitnesscenter.',
                    'suggestions' => [
                        'Pool-Öffnungszeiten',
                        'Frühstücksmenü',
                        'Spa-Services',
                        'Weitere Services'
                    ]
                ),
                'location' => array(
                    'message' => 'Unser Hotel liegt ideal im Herzen der Stadt, 5 Gehminuten von den wichtigsten Sehenswürdigkeiten und 2 Minuten von der U-Bahn-Station entfernt.',
                    'suggestions' => [
                        'Anfahrt zu uns',
                        'Sehenswürdigkeiten in der Nähe',
                        'Öffentliche Verkehrsmittel',
                        'Parken & Zugang'
                    ]
                ),
                'contact' => array(
                    'message' => 'Sie können uns 24/7 kontaktieren: 📞 +33 1 23 45 67 89, 📧 contact@hotel.com. Unser Team ist immer verfügbar, um Ihnen zu helfen!',
                    'suggestions' => [
                        'Rezeptionszeiten',
                        'Kundenservice',
                        '24/7 Notfall',
                        'Telefonische Buchung'
                    ]
                ),
                'general' => array(
                    'message' => 'Ich bin Ihr intelligenter Hotel-Assistent ! Ich kann Ihnen bei Reservierungen helfen, Sie über unsere Services und Preise informieren.',
                    'suggestions' => [
                        'Reservierung vornehmen',
                        'Unsere Preise ansehen',
                        'Hotel-Services',
                        'Kontakt aufnehmen'
                    ]
                )
            ),
            'it' => array(
                'greeting' => array(
                    'message' => 'Ciao ! Sono il tuo assistente intelligente dell\'hotel. Per aiutarti meglio, potresti dirmi il tuo nome?',
                    'suggestions' => []
                ),
                'reservation' => array(
                    'message' => 'Perfetto ! Sarei felice di aiutarti con la tua prenotazione. Le nostre camere sono disponibili tutto l\'anno con tariffe preferenziali a seconda della stagione.',
                    'suggestions' => [
                        'Controlla disponibilità',
                        'Tipi di camere',
                        'Tariffe e promozioni',
                        'Prenota ora'
                    ]
                ),
                'price' => array(
                    'message' => 'Le nostre tariffe sono molto competitive ! Camera singola: 80€/notte, Camera doppia: 120€/notte, Suite: 200€/notte. Promozioni speciali per soggiorni oltre 3 notti.',
                    'suggestions' => [
                        'Vedi promozioni',
                        'Confronta camere',
                        'Cosa è incluso',
                        'Prenota ora'
                    ]
                ),
                'amenities' => array(
                    'message' => 'Il nostro hotel 4 stelle offre: 🏈 Piscina esterna, 📶 WiFi gratuito, 🍳 Colazione a buffet, 🅿️ Parcheggio gratuito, 🛀 Spa & Wellness, 🏋️‍♀️ Centro fitness.',
                    'suggestions' => [
                        'Orari piscina',
                        'Menù colazione',
                        'Servizi spa',
                        'Altri servizi'
                    ]
                ),
                'location' => array(
                    'message' => 'Il nostro hotel è situato idealmente nel cuore della città, a 5 minuti a piedi dai principali siti turistici e a 2 minuti dalla stazione della metropolitana.',
                    'suggestions' => [
                        'Come raggiungerci',
                        'Attrazioni vicine',
                        'Trasporti pubblici',
                        'Parcheggio e accesso'
                    ]
                ),
                'contact' => array(
                    'message' => 'Puoi contattarci 24/7: 📞 +33 1 23 45 67 89, 📧 contact@hotel.com. Il nostro team è sempre disponibile per aiutarti!',
                    'suggestions' => [
                        'Orari reception',
                        'Servizio clienti',
                        'Emergenze 24h',
                        'Prenotazione telefonica'
                    ]
                ),
                'general' => array(
                    'message' => 'Sono il tuo assistente intelligente dell\'hotel ! Posso aiutarti con le prenotazioni, informarti sui nostri servizi e tariffe.',
                    'suggestions' => [
                        'Fare una prenotazione',
                        'Vedere le nostre tariffe',
                        'Servizi hotel',
                        'Contattaci'
                    ]
                )
            )
        );
        
        $lang_responses = $responses[$language] ?? $responses['fr'];
        $response_data = $lang_responses[$intent] ?? $lang_responses['general'];
        
        return $response_data;
    }
    
    // Fonctions utilitaires pour les couleurs
    private function hex_to_rgb($hex) {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "$r, $g, $b"; // Format pour CSS
    }
    
    
    /**
     * Ajuste la luminosité d'une couleur hexadécimale.
     * @param string $hex  Couleur hexadécimale (#rrggbb ou #rgb)
     * @param int    $steps Valeur entre -255 (plus sombre) et 255 (plus claire)
     * @return string Couleur hexadécimale ajustée
     */
    private function adjust_brightness($hex, $steps) {
        $steps = max(-255, min(255, $steps));
        $hex   = str_replace('#', '', $hex);
        // format court (#abc) => format long (#aabbcc)
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0] . $hex[1].$hex[1] . $hex[2].$hex[2];
        }
        // convertir en decimal et appliquer l'ajustement
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private function rgb_to_hex($r, $g, $b) {
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
    
    private function darken_color($hex, $percent) {
        $rgb = $this->hex_to_rgb($hex);
        $rgb['r'] = max(0, $rgb['r'] - ($rgb['r'] * $percent / 100));
        $rgb['g'] = max(0, $rgb['g'] - ($rgb['g'] * $percent / 100));
        $rgb['b'] = max(0, $rgb['b'] - ($rgb['b'] * $percent / 100));
        return $this->rgb_to_hex($rgb['r'], $rgb['g'], $rgb['b']);
    }
    
    private function lighten_color($hex, $percent) {
        $rgb = $this->hex_to_rgb($hex);
        $rgb['r'] = min(255, $rgb['r'] + ((255 - $rgb['r']) * $percent / 100));
        $rgb['g'] = min(255, $rgb['g'] + ((255 - $rgb['g']) * $percent / 100));
        $rgb['b'] = min(255, $rgb['b'] + ((255 - $rgb['b']) * $percent / 100));
        return $this->rgb_to_hex($rgb['r'], $rgb['g'], $rgb['b']);
    }
    
    private function hex_to_rgba($hex, $alpha) {
        $rgb = $this->hex_to_rgb($hex);
        return "rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, $alpha)";
    }

    public function add_admin_menu() {
        add_menu_page(
            'Hotel Chatbot Admin', // Page title
            'Chatbot Admin', // Menu title
            'manage_options', // Capability
            'hotel-chatbot-admin', // Menu slug
            array($this, 'admin_page'), // Callback function
            'dashicons-format-chat', // Icon
            30 // Position
        );
        
        // Ajouter la sous-page de réglages
        add_submenu_page(
            'hotel-chatbot-admin', // Parent slug
            'Réglages du Chatbot', // Page title
            'Réglages', // Menu title
            'manage_options', // Capability
            'hotel-chatbot-settings', // Menu slug
            array($this, 'settings_page') // Callback function
        );
    }

    private function get_system_prompt($language, $client_name) {
        $hotel_info = array(
            'name' => 'Hôtel Excellence',
            'stars' => '4 étoiles',
            'location' => 'centre-ville',
            'rooms' => 'chambres simples (€80/nuit), doubles (€120/nuit), suites (€200/nuit)',
            'services' => 'piscine extérieure, WiFi gratuit, petit-déjeuner buffet, parking gratuit, spa & wellness, salle de fitness',
            'checkin' => '15h00',
            'checkout' => '11h00',
            'contact' => '+33 1 23 45 67 89',
            'email' => 'contact@hotel.com'
        );
        
        $prompts = array(
            'fr' => "Vous êtes l'assistant hôtelier professionnel de l'Hôtel Excellence, un établissement 4 étoiles situé en centre-ville. Votre rôle est d'aider {$client_name} avec ses besoins de réservation et de fournir des informations détaillées sur l'hôtel.\n\nINFORMATIONS HÔTEL :\n- Chambres : {$hotel_info['rooms']}\n- Services : {$hotel_info['services']}\n- Check-in : {$hotel_info['checkin']} | Check-out : {$hotel_info['checkout']}\n- Contact : {$hotel_info['contact']} | Email : {$hotel_info['email']}\n\nINSTRUCTIONS :\n1. Répondez de manière chaleureuse et professionnelle\n2. Fournissez des informations détaillées et précises\n3. Proposez toujours des solutions et alternatives\n4. Utilisez le prénom du client : {$client_name}\n5. Limitez vos réponses à 250 mots maximum\n6. Pour les réservations, dirigez vers notre service de réservation\n7. Soyez proactif en proposant des services complémentaires\n\nRépondez uniquement aux questions liées à l'hôtel et aux réservations.",
            
            'en' => "You are the professional hotel assistant of Hotel Excellence, a 4-star establishment located in the city center. Your role is to help {$client_name} with their reservation needs and provide detailed information about the hotel.\n\nHOTEL INFORMATION:\n- Rooms: single rooms (€80/night), double rooms (€120/night), suites (€200/night)\n- Services: outdoor pool, free WiFi, buffet breakfast, free parking, spa & wellness, fitness center\n- Check-in: 3:00 PM | Check-out: 11:00 AM\n- Contact: {$hotel_info['contact']} | Email: {$hotel_info['email']}\n\nINSTRUCTIONS:\n1. Respond warmly and professionally\n2. Provide detailed and accurate information\n3. Always offer solutions and alternatives\n4. Use the client's first name: {$client_name}\n5. Limit your responses to 250 words maximum\n6. For reservations, direct to our booking service\n7. Be proactive in suggesting additional services\n\nOnly respond to hotel and reservation-related questions.",
            
            'es' => "Eres el asistente hotelero profesional del Hotel Excellence, un establecimiento de 4 estrellas ubicado en el centro de la ciudad. Tu papel es ayudar a {$client_name} con sus necesidades de reserva y proporcionar información detallada sobre el hotel.\n\nINFORMACIÓN DEL HOTEL:\n- Habitaciones: individuales (€80/noche), dobles (€120/noche), suites (€200/noche)\n- Servicios: piscina exterior, WiFi gratuito, desayuno buffet, aparcamiento gratuito, spa y bienestar, gimnasio\n- Check-in: 15:00 | Check-out: 11:00\n- Contacto: {$hotel_info['contact']} | Email: {$hotel_info['email']}\n\nINSTRUCCIONES:\n1. Responde de manera cálida y profesional\n2. Proporciona información detallada y precisa\n3. Siempre ofrece soluciones y alternativas\n4. Usa el nombre del cliente: {$client_name}\n5. Limita tus respuestas a 250 palabras máximo\n6. Para reservas, dirige a nuestro servicio de reservas\n7. Sé proactivo sugiriendo servicios adicionales\n\nSolo responde a preguntas relacionadas con el hotel y reservas."
        );

        return $prompts[$language] ?? $prompts['fr'];
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>🏨 Hotel Chatbot - Gestion des Réservations</h1>
            <p>Interface de gestion des demandes de réservation en temps réel.</p>
            
            <div class="hotel-chatbot-admin-container">
                <div class="admin-header">
                    <h2>📅 Demandes de Réservation</h2>
                    <p>Gérez les demandes de réservation de vos clients via le chatbot</p>
                </div>
                
                <!-- Panneau de gestion des réservations -->
                <div class="reservations-dashboard">
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <h4>📅 Nouvelles Demandes</h4>
                            <span class="stat-number">3</span>
                        </div>
                        <div class="stat-card">
                            <h4>✅ Confirmées Aujourd'hui</h4>
                            <span class="stat-number">7</span>
                        </div>
                        <div class="stat-card">
                            <h4>💰 Revenus du Jour</h4>
                            <span class="stat-number">1,240€</span>
                        </div>
                        <div class="stat-card">
                            <h4>🏨 Taux d'Occupation</h4>
                            <span class="stat-number">78%</span>
                        </div>
                    </div>
                </div>
                
                <!-- Interface de chat admin native -->
                <div id="admin-chatbot-widget" class="admin-widget-container">
                    <div class="admin-chat-interface">
                        <div class="chat-header-admin">
                            <h4>💬 Chat en Direct</h4>
                            <span class="status-indicator online">🟢 En ligne</span>
                        </div>
                        
                        <div class="conversations-list">
                            <h5>Demandes de Réservation</h5>
                            <div id="conversations-container">
                                <div class="conversation-item active" data-client-id="client-001">
                                    <div class="client-info">
                                        <span class="client-name">📅 Réservation #001</span>
                                        <span class="last-message">Chambre double - 15-17 août</span>
                                        <span class="reservation-status pending">⏳ En attente</span>
                                    </div>
                                    <span class="unread-badge">2</span>
                                </div>
                                <div class="conversation-item" data-client-id="client-002">
                                    <div class="client-info">
                                        <span class="client-name">🏨 Réservation #002</span>
                                        <span class="last-message">Suite familiale - septembre</span>
                                        <span class="reservation-status inquiry">❓ Demande d'info</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chat-area">
                            <div class="chat-messages-admin" id="admin-messages">
                                <div class="message-admin client">
                                    <div class="message-content">
                                        <strong>Client #001:</strong> Bonjour, je souhaite réserver une chambre double pour le 15-17 août. Avez-vous de la disponibilité ?
                                    </div>
                                    <div class="message-time">10:45</div>
                                </div>
                                <div class="message-admin admin">
                                    <div class="message-content">
                                        <strong>Vous:</strong> Bonjour ! Oui, nous avons des chambres doubles disponibles pour ces dates. Le tarif est de 120€/nuit. Souhaitez-vous procéder à la réservation ?
                                    </div>
                                    <div class="message-time">10:46</div>
                                </div>
                                <div class="message-admin client">
                                    <div class="message-content">
                                        <strong>Client #001:</strong> Parfait ! Oui, je confirme la réservation. Faut-il un acompte ?
                                    </div>
                                    <div class="message-time">10:47</div>
                                </div>
                            </div>
                            
                            <div class="chat-input-admin">
                                <input type="text" id="admin-message-input" placeholder="Tapez votre réponse..." class="admin-input">
                                <button id="admin-send-btn" class="admin-send-button">Envoyer</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="admin-instructions">
                    <h3>📋 Instructions pour les Réservations</h3>
                    <ul>
                        <li>📅 Gérez les demandes de réservation en temps réel</li>
                        <li>💬 Répondez aux questions sur les disponibilités et tarifs</li>
                        <li>✅ Confirmez les réservations directement via le chat</li>
                        <li>💳 Collectez les informations de paiement si nécessaire</li>
                        <li>📧 Envoyez les confirmations de réservation</li>
                    </ul>
                    
                    <h3>⚙️ Configuration</h3>
                    <p><strong>Serveur Backend:</strong> http://localhost:3000</p>
                    <p><strong>Hotel ID:</strong> 1 (par défaut)</p>
                    
                    <h3>🚀 Démarrage du serveur</h3>
                    <p>Si le widget ne se charge pas, démarrez le serveur backend :</p>
                    <code>cd backend-chatbot && npm start</code>
                </div>
            </div>
        </div>
        
        <script>
            // JavaScript pour l'interface admin
            (function() {
                const adminInput = document.getElementById('admin-message-input');
                const adminSendBtn = document.getElementById('admin-send-btn');
                const adminMessages = document.getElementById('admin-messages');
                const conversationItems = document.querySelectorAll('.conversation-item');
                
                let currentClientId = 'client-001';
                
                // Fonction pour ajouter un message admin
                function addAdminMessage(message, isAdmin = true) {
                    const messageDiv = document.createElement('div');
                    messageDiv.className = 'message-admin ' + (isAdmin ? 'admin' : 'client');
                    
                    const now = new Date();
                    const timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                                   now.getMinutes().toString().padStart(2, '0');
                    
                    messageDiv.innerHTML = `
                        <div class="message-content">
                            <strong>${isAdmin ? 'Vous:' : 'Client #' + currentClientId.split('-')[1] + ':'}</strong> ${message}
                        </div>
                        <div class="message-time">${timeStr}</div>
                    `;
                    
                    adminMessages.appendChild(messageDiv);
                    adminMessages.scrollTop = adminMessages.scrollHeight;
                    
                    // Animation d'apparition
                    messageDiv.style.opacity = '0';
                    messageDiv.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        messageDiv.style.transition = 'all 0.3s ease';
                        messageDiv.style.opacity = '1';
                        messageDiv.style.transform = 'translateY(0)';
                    }, 10);
                }
                
                // Fonction pour envoyer un message admin
                function sendAdminMessage() {
                    const message = adminInput.value.trim();
                    if (!message) return;
                    
                    addAdminMessage(message, true);
                    adminInput.value = '';
                    
                    // Simuler une réponse client après 2-3 secondes
                    setTimeout(() => {
                        const responses = [
                            'Merci ! Puis-je avoir les détails de la réservation par email ?',
                            'Parfait, je procède au paiement. Acceptez-vous les cartes ?',
                            'Avez-vous des chambres avec vue sur mer disponibles ?',
                            'Excellent ! Y a-t-il un petit-déjeuner inclus ?',
                            'D\'accord, je confirme pour ces dates. Merci !'
                        ];
                        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                        addAdminMessage(randomResponse, false);
                        
                        // Mettre à jour le badge de notification
                        updateNotificationBadge();
                    }, 2000 + Math.random() * 2000);
                }
                
                // Fonction pour mettre à jour les badges de notification
                function updateNotificationBadge() {
                    const activeConversation = document.querySelector('.conversation-item.active');
                    const badge = activeConversation.querySelector('.unread-badge');
                    if (badge) {
                        const currentCount = parseInt(badge.textContent) || 0;
                        badge.textContent = currentCount + 1;
                        badge.style.display = 'flex';
                    }
                }
                
                // Gestion des événements
                adminSendBtn.addEventListener('click', sendAdminMessage);
                adminInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        sendAdminMessage();
                    }
                });
                
                // Gestion du changement de conversation
                conversationItems.forEach(item => {
                    item.addEventListener('click', function() {
                        // Retirer la classe active de tous les éléments
                        conversationItems.forEach(conv => conv.classList.remove('active'));
                        // Ajouter la classe active à l'élément cliqué
                        this.classList.add('active');
                        
                        // Mettre à jour l'ID client actuel
                        currentClientId = this.getAttribute('data-client-id');
                        
                        // Cacher le badge de notification
                        const badge = this.querySelector('.unread-badge');
                        if (badge) {
                            badge.style.display = 'none';
                        }
                        
                        // Simuler le chargement de l'historique de conversation
                        loadConversationHistory(currentClientId);
                    });
                });
                
                // Fonction pour charger l'historique de conversation
                function loadConversationHistory(clientId) {
                    // Vider les messages actuels
                    adminMessages.innerHTML = '';
                    
                    // Simuler différents historiques selon le client
                    if (clientId === 'client-001') {
                        addAdminMessage('Bonjour, je souhaite réserver une chambre double pour le 15-17 août. Avez-vous de la disponibilité ?', false);
                        setTimeout(() => {
                            addAdminMessage('Bonjour ! Oui, nous avons des chambres doubles disponibles pour ces dates. Le tarif est de 120€/nuit. Souhaitez-vous procéder à la réservation ?', true);
                        }, 500);
                        setTimeout(() => {
                            addAdminMessage('Parfait ! Oui, je confirme la réservation. Faut-il un acompte ?', false);
                        }, 1000);
                    } else if (clientId === 'client-002') {
                        addAdminMessage('Bonjour, quels sont vos tarifs pour une suite familiale en septembre ?', false);
                        setTimeout(() => {
                            addAdminMessage('Bonjour ! Nos suites familiales sont à 180€/nuit en septembre. Elles peuvent accueillir jusqu\'à 4 personnes. Souhaitez-vous réserver ?', true);
                        }, 500);
                    }
                }
                
                // Initialiser avec un message de bienvenue
                setTimeout(() => {
                    addAdminMessage('Interface admin chargée avec succès ! Vous pouvez maintenant communiquer avec vos clients.', true);
                }, 1000);
                
            })();
        </script>
        
        <style>
            .hotel-chatbot-admin-container {
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                margin-top: 20px;
            }
            
            .admin-header {
                border-bottom: 2px solid #25d366;
                padding-bottom: 15px;
                margin-bottom: 20px;
            }
            
            .admin-header h2 {
                color: #25d366;
                margin: 0;
            }
            
            .admin-widget-container {
                min-height: 600px;
                border: 1px solid #25d366;
                border-radius: 8px;
                padding: 0;
                margin: 20px 0;
                background: white;
                overflow: hidden;
            }
            
            .admin-chat-interface {
                display: flex;
                flex-direction: column;
                height: 600px;
            }
            
            .chat-header-admin {
                background: #25d366;
                color: white;
                padding: 15px 20px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .chat-header-admin h4 {
                margin: 0;
                font-size: 18px;
            }
            
            .status-indicator {
                background: rgba(255, 255, 255, 0.2);
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 14px;
            }
            
            .conversations-list {
                background: #f8f9fa;
                padding: 15px;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .conversations-list h5 {
                margin: 0 0 10px 0;
                color: #333;
                font-size: 14px;
            }
            
            .conversation-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px;
                margin: 5px 0;
                background: white;
                border-radius: 6px;
                cursor: pointer;
                transition: background-color 0.2s;
            }
            
            .conversation-item:hover {
                background: #e8f5e8;
            }
            
            .conversation-item.active {
                background: #e8f5e8;
                border-left: 3px solid #25d366;
            }
            
            .client-info {
                display: flex;
                flex-direction: column;
            }
            
            .client-name {
                font-weight: 600;
                color: #333;
                font-size: 14px;
            }
            
            .last-message {
                font-size: 12px;
                color: #666;
            }
            
            .unread-badge {
                background: #dc3545;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: bold;
            }
            
            .chat-area {
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            
            .chat-messages-admin {
                flex: 1;
                padding: 20px;
                overflow-y: auto;
                background: white;
            }
            
            .message-admin {
                margin: 15px 0;
                padding: 12px;
                border-radius: 8px;
                max-width: 80%;
            }
            
            .message-admin.client {
                background: #f1f3f4;
                margin-right: auto;
            }
            
            .message-admin.admin {
                background: #e8f5e8;
                margin-left: auto;
            }
            
            .message-content {
                margin-bottom: 5px;
            }
            
            .message-time {
                font-size: 11px;
                color: #666;
                text-align: right;
            }
            
            .chat-input-admin {
                padding: 15px 20px;
                border-top: 1px solid #e0e0e0;
                display: flex;
                gap: 10px;
                background: #f8f9fa;
            }
            
            .admin-input {
                flex: 1;
                padding: 12px 16px;
                border: 1px solid #ddd;
                border-radius: 25px;
                outline: none;
                font-size: 14px;
            }
            
            .admin-input:focus {
                border-color: #25d366;
                box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
            }
            
            .admin-send-button {
                background: #25d366;
                color: white;
                border: none;
                padding: 12px 20px;
                border-radius: 25px;
                cursor: pointer;
                font-weight: 600;
                transition: background-color 0.2s;
            }
            
            .admin-send-button:hover {
                background: #22c55e;
            }
            
            .loading-message {
                color: #666;
                font-size: 16px;
            }
            
            .error-message {
                color: #dc3545;
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                border-radius: 4px;
                padding: 15px;
                margin: 10px 0;
            }
            
            .admin-instructions {
                margin-top: 30px;
                padding: 20px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
            }
            
            .admin-instructions h3 {
                color: #25d366;
                margin-top: 0;
            }
            
            .admin-instructions ul {
                list-style: none;
                padding-left: 0;
            }
            
            .admin-instructions li {
                margin: 8px 0;
                padding-left: 0;
            }
            
            .admin-instructions code {
                background: #f1f1f1;
                padding: 8px 12px;
                border-radius: 4px;
                display: inline-block;
                font-family: monospace;
                color: #333;
            }
        </style>
        
        <?php
    }

    public function init_settings() {
        // Enregistrer les paramètres
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_api_key');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_welcome_message');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_primary_color');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_position');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_backend_url');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_enable_sound');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_auto_open');
        
        // Ajouter les sections de paramètres
        add_settings_section(
            'hotel_chatbot_general_section',
            'Paramètres Généraux',
            array($this, 'general_section_callback'),
            'hotel-chatbot-settings'
        );
        
        add_settings_section(
            'hotel_chatbot_appearance_section',
            'Apparence',
            array($this, 'appearance_section_callback'),
            'hotel-chatbot-settings'
        );
        
        add_settings_section(
            'hotel_chatbot_advanced_section',
            'Paramètres Avancés',
            array($this, 'advanced_section_callback'),
            'hotel-chatbot-settings'
        );
        
        // Ajouter les champs de paramètres
        add_settings_field(
            'hotel_chatbot_api_key',
            'Clé API',
            array($this, 'api_key_field_callback'),
            'hotel-chatbot-settings',
            'hotel_chatbot_general_section'
        );
        
        add_settings_field(
            'hotel_chatbot_welcome_message',
            'Message de Bienvenue',
            array($this, 'welcome_message_field_callback'),
            'hotel-chatbot-settings',
            'hotel_chatbot_general_section'
        );
        
        add_settings_field(
            'hotel_chatbot_primary_color',
            'Couleur Principale',
            array($this, 'primary_color_field_callback'),
            'hotel-chatbot-settings',
            'hotel_chatbot_appearance_section'
        );
        
        add_settings_field(
            'hotel_chatbot_position',
            'Position du Bouton',
            array($this, 'position_field_callback'),
            'hotel-chatbot-settings',
            'hotel_chatbot_appearance_section'
        );
        
        add_settings_field(
            'hotel_chatbot_backend_url',
            'URL du Serveur Backend',
            array($this, 'backend_url_field_callback'),
            'hotel-chatbot-settings',
            'hotel_chatbot_advanced_section'
        );
        
        add_settings_field(
            'hotel_chatbot_enable_sound',
            'Activer les Sons',
            array($this, 'enable_sound_field_callback'),
            'hotel-chatbot-settings',
            'hotel_chatbot_advanced_section'
        );
        
        add_settings_field(
            'hotel_chatbot_auto_open',
            'Ouverture Automatique',
            array($this, 'auto_open_field_callback'),
            'hotel-chatbot-settings',
            'hotel_chatbot_advanced_section'
        );
    }
    
    // Callbacks pour les sections
    public function general_section_callback() {
        echo '<p>Configurez les paramètres de base du chatbot.</p>';
    }
    
    public function appearance_section_callback() {
        echo '<p>Personnalisez l\'apparence du chatbot.</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p>Paramètres avancés pour les utilisateurs expérimentés.</p>';
    }
    
    // Callbacks pour les champs
    public function api_key_field_callback() {
        $value = get_option('hotel_chatbot_api_key', '');
        echo '<input type="password" name="hotel_chatbot_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Clé API pour l\'intégration avec le service de chatbot (ex: OpenAI, Dialogflow).</p>';
    }
    
    public function welcome_message_field_callback() {
        $value = get_option('hotel_chatbot_welcome_message', 'Bonjour ! Je suis votre assistant hôtelier. Comment puis-je vous aider avec votre réservation ?');
        echo '<textarea name="hotel_chatbot_welcome_message" rows="3" class="large-text">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Message affiché lors de l\'ouverture du chatbot.</p>';
    }
    
    public function primary_color_field_callback() {
        $value = get_option('hotel_chatbot_primary_color', '#25d366');
        echo '<input type="color" name="hotel_chatbot_primary_color" value="' . esc_attr($value) . '" />';
        echo '<p class="description">Couleur principale du chatbot (boutons, header, etc.).</p>';
    }
    
    public function position_field_callback() {
        $value = get_option('hotel_chatbot_position', 'bottom-right');
        $positions = array(
            'bottom-right' => 'Bas Droite',
            'bottom-left' => 'Bas Gauche',
            'top-right' => 'Haut Droite',
            'top-left' => 'Haut Gauche'
        );
        echo '<select name="hotel_chatbot_position">';
        foreach ($positions as $key => $label) {
            $selected = selected($value, $key, false);
            echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Position du bouton flottant du chatbot.</p>';
    }
    
    public function backend_url_field_callback() {
        $value = get_option('hotel_chatbot_backend_url', 'http://localhost:3000');
        echo '<input type="url" name="hotel_chatbot_backend_url" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">URL du serveur backend pour l\'API du chatbot.</p>';
    }
    
    public function enable_sound_field_callback() {
        $value = get_option('hotel_chatbot_enable_sound', '0');
        echo '<input type="checkbox" name="hotel_chatbot_enable_sound" value="1" ' . checked($value, '1', false) . ' />';
        echo '<label for="hotel_chatbot_enable_sound">Jouer un son lors de la réception de nouveaux messages</label>';
    }
    
    public function auto_open_field_callback() {
        $value = get_option('hotel_chatbot_auto_open', '0');
        echo '<input type="checkbox" name="hotel_chatbot_auto_open" value="1" ' . checked($value, '1', false) . ' />';
        echo '<label for="hotel_chatbot_auto_open">Ouvrir automatiquement le chatbot après 10 secondes</label>';
    }
    
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>🏨 Réglages du Chatbot</h1>
            <p>Configurez les paramètres de votre chatbot hôtelier.</p>
            
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>✅ Paramètres sauvegardés avec succès !</p>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('hotel_chatbot_settings_group');
                do_settings_sections('hotel-chatbot-settings');
                submit_button('Sauvegarder les Paramètres');
                ?>
            </form>
            
            <div class="hotel-chatbot-settings-info">
                <h2>📋 Informations</h2>
                <div class="settings-info-grid">
                    <div class="info-card">
                        <h3>🎨 Personnalisation</h3>
                        <p>Modifiez les couleurs et la position du chatbot pour qu'il s'intègre parfaitement à votre site.</p>
                    </div>
                    <div class="info-card">
                        <h3>🔧 Configuration</h3>
                        <p>Configurez l'URL du backend et les paramètres avancés selon vos besoins.</p>
                    </div>
                    <div class="info-card">
                        <h3>💬 Messages</h3>
                        <p>Personnalisez le message de bienvenue pour refléter l'identité de votre hôtel.</p>
                    </div>
                </div>
                
                <h3>🚀 Utilisation du Shortcode</h3>
                <p>Utilisez le shortcode suivant pour afficher le chatbot sur vos pages :</p>
                <code>[hotel_chatbot]</code>
                <p>Avec des paramètres personnalisés :</p>
                <code>[hotel_chatbot hotel_id="123" title="Assistant Hôtel Luxe"]</code>
            </div>
        </div>
        
        <style>
            .hotel-chatbot-settings-info {
                margin-top: 30px;
                padding: 20px;
                background: white;
                border: 1px solid #ddd;
                border-radius: 8px;
            }
            
            .settings-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            
            .info-card {
                padding: 15px;
                background: #f8f9fa;
                border-left: 4px solid #25d366;
                border-radius: 4px;
            }
            
            .info-card h3 {
                margin-top: 0;
                color: #25d366;
            }
            
            .hotel-chatbot-settings-info code {
                background: #f1f1f1;
                padding: 8px 12px;
                border-radius: 4px;
                display: inline-block;
                margin: 5px 0;
                font-family: monospace;
            }
        </style>
        <?php
    }
}

// ===== HANDLERS AJAX POUR L'INTERFACE ADMIN =====

// Handler pour récupérer la liste des conversations
function get_conversations() {
    // Vérifier les permissions admin
    if (!current_user_can('manage_options')) {
        wp_die('Accès non autorisé');
    }
    
    // Vérifier le nonce si présent
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'hotel_chatbot_admin_nonce')) {
        wp_send_json_error('Nonce invalide');
    }
    
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
    
    $conversations = $wpdb->get_results("
        SELECT c.*, 
               (SELECT COUNT(*) FROM $messages_table WHERE conversation_id = c.id) as message_count,
               (SELECT message FROM $messages_table WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
        FROM $conversations_table c 
        ORDER BY c.updated_at DESC
    ");
    
    wp_send_json_success($conversations);
}

// Handler pour récupérer une conversation spécifique
function get_conversation() {
    // Vérifier les permissions admin
    if (!current_user_can('manage_options')) {
        wp_die('Accès non autorisé');
    }
    
    // Vérifier le nonce si présent
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'hotel_chatbot_admin_nonce')) {
        wp_send_json_error('Nonce invalide');
    }
    
    $conversation_id = intval($_POST['conversation_id']);
    if (!$conversation_id) {
        wp_send_json_error('ID de conversation invalide');
    }
    
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
    
    // Récupérer les informations de la conversation
    $conversation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $conversations_table WHERE id = %d",
        $conversation_id
    ));
    
    if (!$conversation) {
        wp_send_json_error('Conversation non trouvée');
    }
    
    // Récupérer les messages de la conversation
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $messages_table WHERE conversation_id = %d ORDER BY created_at ASC",
        $conversation_id
    ));
    
    wp_send_json_success(array(
        'conversation' => $conversation,
        'messages' => $messages
    ));
}

// Handler pour envoyer une réponse admin
function handle_admin_message() {
    // Vérifier les permissions admin
    if (!current_user_can('manage_options')) {
        wp_die('Accès non autorisé');
    }
    
    // Vérifier le nonce si présent
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'hotel_chatbot_admin_nonce')) {
        wp_send_json_error('Nonce invalide');
    }
    
    $conversation_id = intval($_POST['conversation_id']);
    $message = sanitize_textarea_field($_POST['message']);
    
    if (!$conversation_id || !$message) {
        wp_send_json_error('Données invalides');
    }
    
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
    
    // Vérifier que la conversation existe
    $conversation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $conversations_table WHERE id = %d",
        $conversation_id
    ));
    
    if (!$conversation) {
        wp_send_json_error('Conversation non trouvée');
    }
    
    // Insérer le message admin
    $result = $wpdb->insert(
        $messages_table,
        array(
            'conversation_id' => $conversation_id,
            'message' => $message,
            'sender_type' => 'admin',
            'created_at' => current_time('mysql')
        ),
        array('%d', '%s', '%s', '%s')
    );
    
    if ($result === false) {
        wp_send_json_error('Erreur lors de l\'envoi du message');
    }
    
    // Mettre à jour le timestamp de la conversation
    $wpdb->update(
        $conversations_table,
        array('updated_at' => current_time('mysql')),
        array('id' => $conversation_id),
        array('%s'),
        array('%d')
    );
    
    wp_send_json_success(array(
        'message' => 'Message envoyé avec succès',
        'message_id' => $wpdb->insert_id
    ));
}

// Handler pour l'upload d'avatar
function handle_avatar_upload() {
    // Test simple pour vérifier que la fonction est appelée
    error_log('=== AVATAR UPLOAD STARTED ===');
    
    try {
        // Vérifier les permissions admin
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissions insuffisantes');
        }
        
        // Vérifier le nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'hotel_chatbot_admin_nonce')) {
            wp_send_json_error('Sécurité : nonce invalide');
        }
        
        // Vérifier qu'un fichier a été uploadé
        if (!isset($_FILES['avatar'])) {
            wp_send_json_error('Aucun fichier reçu');
        }
        
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Erreur d\'upload du fichier (code: ' . $_FILES['avatar']['error'] . ')');
        }
    
        $file = $_FILES['avatar'];
        
        // Vérifier le type de fichier
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error('Type de fichier non autorisé (' . $file['type'] . '). Utilisez JPG, PNG ou GIF.');
        }
        
        // Vérifier la taille du fichier (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            wp_send_json_error('Le fichier est trop volumineux (' . round($file['size']/1024/1024, 2) . 'MB). Taille maximum : 2MB.');
        }
        
        // Supprimer l'ancien avatar s'il existe
        $old_avatar_url = get_option('hotel_chatbot_avatar_url', '');
        if ($old_avatar_url) {
            $old_avatar_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $old_avatar_url);
            if (file_exists($old_avatar_path)) {
                wp_delete_file($old_avatar_path);
            }
        }
        
        // Configurer l'upload
        $upload_overrides = array(
            'test_form' => false,
            'unique_filename_callback' => function($dir, $name, $ext) {
                return 'chatbot-avatar-' . time() . $ext;
            }
        );
        
        // Effectuer l'upload
        $uploaded_file = wp_handle_upload($file, $upload_overrides);
        
        if (isset($uploaded_file['error'])) {
            wp_send_json_error('Erreur lors de l\'upload : ' . $uploaded_file['error']);
        }
        
        if (!isset($uploaded_file['url']) || !isset($uploaded_file['file'])) {
            wp_send_json_error('Erreur: Résultat d\'upload incomplet');
        }
        
        // Sauvegarder l'URL de l'avatar
        update_option('hotel_chatbot_avatar_url', $uploaded_file['url']);
        
        wp_send_json_success(array(
            'message' => 'Avatar uploadé avec succès',
            'avatar_url' => $uploaded_file['url']
        ));
        
    } catch (Exception $e) {
        error_log('Avatar upload exception: ' . $e->getMessage());
        wp_send_json_error('Erreur: ' . $e->getMessage());
    }
}

// Handler pour la suppression d'avatar
function handle_avatar_remove() {
    // Vérifier les permissions admin
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Accès non autorisé');
    }
    
    // Vérifier le nonce
    if (!wp_verify_nonce($_POST['nonce'], 'hotel_chatbot_admin_nonce')) {
        wp_send_json_error('Nonce invalide');
    }
    
    // Récupérer l'URL de l'avatar actuel
    $avatar_url = get_option('hotel_chatbot_avatar_url', '');
    
    if ($avatar_url) {
        // Supprimer le fichier physique
        $avatar_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $avatar_url);
        if (file_exists($avatar_path)) {
            wp_delete_file($avatar_path);
        }
        
        // Supprimer l'option
        delete_option('hotel_chatbot_avatar_url');
    }
    
    wp_send_json_success(array(
        'message' => 'Avatar supprimé avec succès'
    ));
}

// Handler de test simple
function test_avatar_handler() {
    wp_send_json_success(array(
        'message' => 'Handler AJAX fonctionne !',
        'received_data' => $_POST,
        'files_data' => $_FILES
    ));
}

// === GESTION DES CONVERSATIONS BASÉES SUR L'EMAIL ===

// Vérifier s'il existe une conversation active pour un email donné
function check_existing_conversation() {
    check_ajax_referer('hotel_chatbot_nonce', 'nonce');
    
    $email = sanitize_email($_POST['email'] ?? '');
    
    if (empty($email) || !is_email($email)) {
        wp_send_json_error('Email invalide');
    }
    
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    
    // Chercher la conversation la plus récente pour cet email
    $conversation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $conversations_table 
         WHERE user_email = %s 
         AND status = 'active' 
         ORDER BY updated_at DESC 
         LIMIT 1",
        $email
    ));
    
    if ($conversation) {
        wp_send_json_success(array(
            'conversation' => $conversation,
            'message' => 'Conversation existante trouvée'
        ));
    } else {
        wp_send_json_success(array(
            'conversation' => null,
            'message' => 'Aucune conversation active trouvée'
        ));
    }
}

// Récupérer les messages d'une conversation
function get_conversation_messages() {
    check_ajax_referer('hotel_chatbot_nonce', 'nonce');
    
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    
    if (!$conversation_id) {
        wp_send_json_error('ID de conversation manquant');
    }
    
    global $wpdb;
    $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
    
    // Récupérer tous les messages de la conversation
    $messages = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $messages_table 
         WHERE conversation_id = %d 
         ORDER BY created_at ASC",
        $conversation_id
    ));
    
    wp_send_json_success(array(
        'messages' => $messages,
        'count' => count($messages),
        'message' => 'Messages récupérés avec succès'
    ));
}

// Terminer une conversation (changer son statut)
function end_conversation() {
    check_ajax_referer('hotel_chatbot_nonce', 'nonce');
    
    $conversation_id = intval($_POST['conversation_id'] ?? 0);
    
    if (!$conversation_id) {
        wp_send_json_error('ID de conversation manquant');
    }
    
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    
    // Mettre à jour le statut de la conversation
    $result = $wpdb->update(
        $conversations_table,
        array(
            'status' => 'ended',
            'updated_at' => current_time('mysql')
        ),
        array('id' => $conversation_id),
        array('%s', '%s'),
        array('%d')
    );
    
    if ($result === false) {
        wp_send_json_error('Erreur lors de la fin de conversation');
    }
    
    wp_send_json_success(array(
        'message' => 'Conversation terminée avec succès',
        'conversation_id' => $conversation_id
    ));
}

// Fonction de migration pour corriger les données existantes
function hotel_chatbot_migrate_data() {
    check_ajax_referer('hotel_chatbot_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes');
        return;
    }
    
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    
    // Récupérer toutes les conversations où client_name contient un @
    $conversations = $wpdb->get_results(
        "SELECT id, client_name, client_email FROM $conversations_table WHERE client_name LIKE '%@%'"
    );
    
    $migrated_count = 0;
    
    foreach ($conversations as $conversation) {
        // Si client_name contient un email et client_email est vide
        if (strpos($conversation->client_name, '@') !== false && empty($conversation->client_email)) {
            // Déplacer l'email de client_name vers client_email
            $email = $conversation->client_name;
            $name = 'Client'; // Nom par défaut
            
            $wpdb->update(
                $conversations_table,
                array(
                    'client_name' => $name,
                    'client_email' => $email
                ),
                array('id' => $conversation->id),
                array('%s', '%s'),
                array('%d')
            );
            
            $migrated_count++;
        }
    }
    
    wp_send_json_success(array(
        'migrated_count' => $migrated_count,
        'message' => "Migration terminée. {$migrated_count} conversations corrigées."
    ));
}

// Fonction pour mettre à jour le nom du client
function hotel_chatbot_update_client_name() {
    check_ajax_referer('hotel_chatbot_nonce', 'nonce');
    
    $conversation_id = intval($_POST['conversation_id']);
    $client_name = sanitize_text_field($_POST['client_name']);
    
    if (!$conversation_id || empty($client_name)) {
        wp_send_json_error('Paramètres manquants');
        return;
    }
    
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    
    $result = $wpdb->update(
        $conversations_table,
        array(
            'client_name' => $client_name,
            'updated_at' => current_time('mysql')
        ),
        array('id' => $conversation_id),
        array('%s', '%s'),
        array('%d')
    );
    
    if ($result !== false) {
        wp_send_json_success('Nom du client mis à jour');
    } else {
        wp_send_json_error('Erreur lors de la mise à jour');
    }
}

add_action('wp_ajax_hotel_chatbot_get_conversations', 'get_conversations');
add_action('wp_ajax_hotel_chatbot_get_conversation', 'get_conversation');
add_action('wp_ajax_hotel_chatbot_admin_message', 'handle_admin_message');
add_action('wp_ajax_hotel_chatbot_avatar_upload', 'handle_avatar_upload');
add_action('wp_ajax_hotel_chatbot_avatar_remove', 'handle_avatar_remove');
add_action('wp_ajax_hotel_chatbot_test_avatar', 'test_avatar_handler');

// Handlers AJAX pour les conversations basées sur l'email
add_action('wp_ajax_hotel_chatbot_check_existing_conversation', 'check_existing_conversation');
add_action('wp_ajax_nopriv_hotel_chatbot_check_existing_conversation', 'check_existing_conversation');
add_action('wp_ajax_hotel_chatbot_get_conversation_messages', 'get_conversation_messages');
add_action('wp_ajax_nopriv_hotel_chatbot_get_conversation_messages', 'get_conversation_messages');
add_action('wp_ajax_hotel_chatbot_end_conversation', 'end_conversation');
add_action('wp_ajax_nopriv_hotel_chatbot_end_conversation', 'end_conversation');
add_action('wp_ajax_hotel_chatbot_migrate_data', 'hotel_chatbot_migrate_data');
add_action('wp_ajax_hotel_chatbot_update_client_name', 'hotel_chatbot_update_client_name');
add_action('wp_ajax_nopriv_hotel_chatbot_update_client_name', 'hotel_chatbot_update_client_name');

new HotelChatbot();
