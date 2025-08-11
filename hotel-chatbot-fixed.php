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
        
        // Shortcode
        add_shortcode('hotel_chatbot', array($this, 'chatbot_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_hotel_chatbot_message', array($this, 'handle_message'));
        add_action('wp_ajax_nopriv_hotel_chatbot_message', array($this, 'handle_message'));
        add_action('wp_ajax_hotel_chatbot_admin_message', array($this, 'handle_admin_message'));
        add_action('wp_ajax_hotel_chatbot_get_conversations', array($this, 'get_conversations'));
        add_action('wp_ajax_hotel_chatbot_get_conversation', array($this, 'get_conversation'));
        add_action('wp_ajax_hotel_chatbot_save_settings', array($this, 'save_settings'));
        
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
            language varchar(10) DEFAULT 'fr',
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
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
            'hotel_chatbot_enable_ai' => '1'
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
        }
    }
    
    public function include_admin_files() {
        if (is_admin()) {
            require_once HOTEL_CHATBOT_PATH . 'includes/admin-menu.php';
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        // Charger les styles et scripts admin seulement sur les pages du plugin
        if (strpos($hook, 'hotel-chatbot') !== false) {
            wp_enqueue_style(
                'hotel-chatbot-admin-style',
                HOTEL_CHATBOT_URL . 'assets/css/admin-styles.css',
                array(),
                HOTEL_CHATBOT_VERSION
            );
            
            wp_enqueue_script(
                'hotel-chatbot-admin-script',
                HOTEL_CHATBOT_URL . 'assets/js/admin.js',
                array('jquery'),
                HOTEL_CHATBOT_VERSION,
                true
            );
            
            wp_localize_script('hotel-chatbot-admin-script', 'hotelChatbotAdmin', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('hotel_chatbot_admin_nonce')
            ));
        }
    }

    public function enqueue_scripts() {
        // Styles du chatbot client
        wp_enqueue_style(
            'hotel-chatbot-client-style',
            HOTEL_CHATBOT_URL . 'assets/css/chatbot-client.css',
            array(),
            HOTEL_CHATBOT_VERSION
        );

        // Script du chatbot client intelligent
        wp_enqueue_script(
            'hotel-chatbot-client-script',
            HOTEL_CHATBOT_URL . 'assets/js/chatbot-client.js',
            array(),
            HOTEL_CHATBOT_VERSION,
            true
        );

        // Variables pour le JavaScript
        wp_localize_script('hotel-chatbot-client-script', 'hotelChatbotAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hotel_chatbot_nonce'),
            'enableSound' => get_option('hotel_chatbot_enable_sound', '0'),
            'autoOpen' => get_option('hotel_chatbot_auto_open', '0'),
            'defaultLanguage' => get_option('hotel_chatbot_default_language', 'fr'),
            'requireName' => get_option('hotel_chatbot_require_name', '1')
        ));
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
        $language = sanitize_text_field($_POST['language'] ?? 'fr');
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : null;
        
        // Sauvegarder le message client et créer/récupérer la conversation
        $result = $this->save_client_message($message, $client_name, $language, $conversation_id);
        
        if ($result && isset($result['conversation_id'])) {
            $response = $this->generate_intelligent_response($message, $client_name, $language, $result['conversation_id']);
            wp_send_json_success(array(
                'response' => $response,
                'conversation_id' => $result['conversation_id']
            ));
        } else {
            wp_send_json_error('Erreur lors de la sauvegarde du message');
        }
    }

    // Fonction pour traiter les messages admin
    public function handle_admin_message() {
        check_ajax_referer('hotel_chatbot_nonce', 'nonce');
        
        $conversation_id = intval($_POST['conversation_id']);
        $message = sanitize_text_field($_POST['message']);
        
        if ($conversation_id && $message) {
            $this->save_bot_message($conversation_id, $message);
            wp_send_json_success('Message envoyé');
        } else {
            wp_send_json_error('Paramètres manquants');
        }
    }

    // Fonction pour récupérer les conversations (UNIQUE)
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
        check_ajax_referer('hotel_chatbot_nonce', 'nonce');
        
        $conversation_id = intval($_POST['conversation_id']);
        if (!$conversation_id) {
            wp_send_json_error('ID de conversation manquant');
        }
        
        global $wpdb;
        $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $messages_table WHERE conversation_id = %d ORDER BY created_at ASC",
            $conversation_id
        ));
        
        wp_send_json_success($messages);
    }

    // Fonction pour sauvegarder un message client
    public function save_client_message($message = '', $client_name, $language, $conversation_id = null) {
        global $wpdb;
        
        $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
        $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
        
        // Créer ou récupérer la conversation
        if (!$conversation_id) {
            $wpdb->insert(
                $conversations_table,
                array(
                    'client_name' => $client_name,
                    'language' => $language,
                    'status' => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                )
            );
            $conversation_id = $wpdb->insert_id;
        } else {
            // Mettre à jour le timestamp de la conversation
            $wpdb->update(
                $conversations_table,
                array('updated_at' => current_time('mysql')),
                array('id' => $conversation_id)
            );
        }
        
        // Sauvegarder le message seulement s'il n'est pas vide
        if (!empty($message)) {
            $result = $wpdb->insert(
                $messages_table,
                array(
                    'conversation_id' => $conversation_id,
                    'sender_type' => 'client',
                    'message' => $message,
                    'created_at' => current_time('mysql')
                )
            );
        } else {
            $result = true; // Si pas de message, on considère que c'est un succès
        }
        
        if ($result !== false) {
            return array(
                'success' => true,
                'conversation_id' => $conversation_id,
                'message_id' => !empty($message) ? $wpdb->insert_id : null
            );
        }
        
        return false;
    }

    // Fonction pour sauvegarder un message bot
    public function save_bot_message($conversation_id, $message) {
        global $wpdb;
        
        $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
        
        $result = $wpdb->insert(
            $messages_table,
            array(
                'conversation_id' => $conversation_id,
                'sender_type' => 'bot',
                'message' => $message,
                'created_at' => current_time('mysql')
            )
        );
        
        return $result !== false;
    }

    // Fonction pour générer une réponse intelligente
    public function generate_intelligent_response($message, $client_name, $language, $conversation_id) {
        // Détecter l'intention du message
        $intent = $this->detect_intent($message, $language);
        
        // Générer une réponse basée sur l'intention
        $response_data = $this->get_response_by_intent($intent, $language, $message);
        $response = $response_data['message'];
        
        // Personnaliser avec le prénom du client
        $first_name = explode(' ', $client_name)[0];
        $response = str_replace('{name}', $first_name, $response);
        
        // Sauvegarder la réponse du bot
        $this->save_bot_message($conversation_id, $response);
        
        return $response;
    }

    // Fonction pour détecter l'intention du message
    private function detect_intent($message, $language) {
        $message_lower = strtolower($message);
        
        // Mots-clés pour différentes intentions
        $keywords = array(
            'reservation' => array('réserver', 'réservation', 'booking', 'book', 'chamber', 'chambre', 'room'),
            'price' => array('prix', 'tarif', 'coût', 'price', 'cost', 'rate', 'combien'),
            'amenities' => array('service', 'équipement', 'piscine', 'wifi', 'petit-déjeuner', 'amenities', 'facilities'),
            'location' => array('adresse', 'où', 'situé', 'location', 'address', 'where'),
            'contact' => array('contact', 'téléphone', 'email', 'joindre', 'appeler'),
            'greeting' => array('bonjour', 'salut', 'hello', 'hi', 'bonsoir')
        );
        
        foreach ($keywords as $intent => $words) {
            foreach ($words as $word) {
                if (strpos($message_lower, $word) !== false) {
                    return $intent;
                }
            }
        }
        
        return 'general';
    }

    // Fonction pour obtenir une réponse selon l'intention
    private function get_response_by_intent($intent, $language, $message) {
        $responses = array(
            'fr' => array(
                'greeting' => array(
                    'message' => 'Bonjour {name} ! Je suis votre assistant hôtelier intelligent. Comment puis-je vous aider avec votre réservation ?'
                ),
                'reservation' => array(
                    'message' => 'Parfait {name} ! Je serais ravi de vous aider avec votre réservation. Nos chambres sont disponibles toute l\'année avec des tarifs préférentiels selon la saison.'
                ),
                'price' => array(
                    'message' => 'Nos tarifs sont très compétitifs {name} ! Chambre simple : 80€/nuit, Chambre double : 120€/nuit, Suite : 200€/nuit. Promotions spéciales pour séjours de plus de 3 nuits.'
                ),
                'amenities' => array(
                    'message' => 'Notre hôtel 4 étoiles propose {name} : 🏊 Piscine extérieure, 📶 WiFi gratuit, 🍳 Petit-déjeuner buffet, 🅿️ Parking gratuit, 🛀 Spa & Wellness, 🏋️‍♀️ Salle de sport.'
                ),
                'location' => array(
                    'message' => 'Notre hôtel est idéalement situé {name}, au cœur de la ville, à 5 min à pied des principaux sites touristiques et à 2 min de la station de métro.'
                ),
                'contact' => array(
                    'message' => 'Vous pouvez nous contacter 24h/24 {name} : 📞 +33 1 23 45 67 89, 📧 contact@hotel.com. Notre équipe est toujours disponible pour vous aider !'
                ),
                'general' => array(
                    'message' => 'Je suis votre assistant hôtelier intelligent {name} ! Je peux vous aider avec vos réservations, vous renseigner sur nos services, tarifs et bien plus encore.'
                )
            )
        );
        
        $lang_responses = $responses[$language] ?? $responses['fr'];
        return $lang_responses[$intent] ?? $lang_responses['general'];
    }

    // Fonction pour les paramètres
    public function save_settings() {
        check_ajax_referer('hotel_chatbot_nonce', 'nonce');
        wp_send_json_success('Paramètres sauvegardés');
    }

    // Fonction d'initialisation des paramètres
    public function init_settings() {
        // Enregistrer les paramètres
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_api_key');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_welcome_message');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_primary_color');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_position');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_backend_url');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_enable_sound');
        register_setting('hotel_chatbot_settings_group', 'hotel_chatbot_auto_open');
    }
}

new HotelChatbot();
