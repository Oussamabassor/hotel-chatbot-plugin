<?php
if (!defined('ABSPATH')) {
    exit; // Arrêter l'exécution si ce n'est pas WordPress
}
function send_message_to_chatbot_api($message) {
    $hotel_id = 'ID_HOTEL_HNA'; // t9dr tjibha dynamic mn DB
    $url = 'http://localhost:3000/api/v1/chatbot/' . $hotel_id . '/message';

    $body = json_encode([
        'message' => $message,
        'sessionId' => uniqid('session_', true),
        'context' => new stdClass()
    ]);

    $response = wp_remote_post($url, [
        'method' => 'POST',
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => $body,
    ]);

    if (is_wp_error($response)) {
        return 'Erreur: ' . $response->get_error_message();
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    return $data['data']['message'] ?? 'Aucune réponse.';
}
