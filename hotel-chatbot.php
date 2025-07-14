<?php
/*
Plugin Name: Hotel Chatbot
Description: Integrates a hotel chatbot with landing pages
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

define('HOTEL_CHATBOT_PATH', plugin_dir_path(__FILE__));
define('HOTEL_CHATBOT_URL', plugin_dir_url(__FILE__));

class HotelChatbot {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('hotel_chatbot', array($this, 'chatbot_shortcode'));
        add_action('wp_ajax_hotel_chatbot_message', array($this, 'handle_message'));
        add_action('wp_ajax_nopriv_hotel_chatbot_message', array($this, 'handle_message'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style(
            'hotel-chatbot-style',
            HOTEL_CHATBOT_URL . 'assets/css/chatbot.css',
            array(),
            '1.0'
        );

        wp_enqueue_script(
            'hotel-chatbot-script',
            HOTEL_CHATBOT_URL . 'assets/js/chatbot.js',
            array('jquery'),
            '1.0',
            true
        );

        wp_localize_script('hotel-chatbot-script', 'hotelChatbotAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hotel_chatbot_nonce')
        ));
    }

    public function chatbot_shortcode($atts) {
        $atts = shortcode_atts(array(
            'hotel_id' => '1'
        ), $atts);

        $hotel_id = esc_attr($atts['hotel_id']);

        return '<div id="hotel-chatbot-widget"></div>
        <script src="http://localhost:3000/widget.js?hotel_id=' . $hotel_id . '"></script>';
    }


    public function handle_message() {
        check_ajax_referer('hotel_chatbot_nonce', 'nonce');

        $message = sanitize_text_field($_POST['message']);
        $hotel_id = intval($_POST['hotel_id']);

        $response = wp_remote_post("http://localhost:3000/api/v1/chatbot/{$hotel_id}/message", array(
            'body' => json_encode(array('message' => $message)),
            'headers' => array('Content-Type' => 'application/json'),
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('Error communicating with chatbot service');
        }

        $body = wp_remote_retrieve_body($response);
        wp_send_json_success(json_decode($body));
    }
}

new HotelChatbot();
