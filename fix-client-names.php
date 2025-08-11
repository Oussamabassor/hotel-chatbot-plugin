<?php
/**
 * Script de correction des noms de clients
 * Corrige les données où l'email apparaît dans la colonne nom
 */

// Sécurité : ne pas exécuter directement
if (!defined('ABSPATH')) {
    die('Accès direct interdit');
}

function hotel_chatbot_fix_client_names() {
    global $wpdb;
    
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    
    echo "<h3>🔧 Correction des noms de clients</h3>";
    
    // 1. Identifier les conversations avec problème
    $problematic_conversations = $wpdb->get_results(
        "SELECT id, client_name, client_email 
         FROM $conversations_table 
         WHERE client_name LIKE '%@%' 
         OR client_name = client_email"
    );
    
    if (empty($problematic_conversations)) {
        echo "<p>✅ Aucune donnée problématique trouvée.</p>";
        return;
    }
    
    echo "<p>📊 Trouvé " . count($problematic_conversations) . " conversation(s) avec des problèmes de nom.</p>";
    
    $fixed_count = 0;
    
    foreach ($problematic_conversations as $conv) {
        echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<strong>Conversation ID: {$conv->id}</strong><br>";
        echo "Nom actuel: <code>{$conv->client_name}</code><br>";
        echo "Email actuel: <code>{$conv->client_email}</code><br>";
        
        $new_name = 'Client';
        $new_email = $conv->client_email;
        
        // Si le nom contient un email et l'email est vide, déplacer l'email
        if (strpos($conv->client_name, '@') !== false && empty($conv->client_email)) {
            $new_email = $conv->client_name;
            $new_name = 'Client';
            echo "Action: Déplacer l'email du nom vers le champ email<br>";
        }
        // Si le nom est identique à l'email, utiliser un nom par défaut
        else if ($conv->client_name === $conv->client_email) {
            $new_name = 'Client';
            echo "Action: Remplacer le nom par 'Client'<br>";
        }
        // Si le nom contient un @ mais on a déjà un email différent
        else if (strpos($conv->client_name, '@') !== false && !empty($conv->client_email)) {
            $new_name = 'Client';
            echo "Action: Utiliser nom par défaut car nom contient @<br>";
        }
        
        // Appliquer la correction
        $result = $wpdb->update(
            $conversations_table,
            array(
                'client_name' => $new_name,
                'client_email' => $new_email
            ),
            array('id' => $conv->id)
        );
        
        if ($result !== false) {
            echo "✅ <strong>Corrigé:</strong> Nom: <code>{$new_name}</code>, Email: <code>{$new_email}</code><br>";
            $fixed_count++;
        } else {
            echo "❌ <strong>Erreur lors de la correction</strong><br>";
        }
        
        echo "</div>";
    }
    
    echo "<p><strong>🎉 Résumé: {$fixed_count} conversation(s) corrigée(s) sur " . count($problematic_conversations) . "</strong></p>";
}

// Exécuter seulement si appelé par l'admin WordPress
if (is_admin() && current_user_can('manage_options')) {
    add_action('admin_init', function() {
        if (isset($_GET['fix_client_names']) && $_GET['fix_client_names'] === '1') {
            hotel_chatbot_fix_client_names();
            exit;
        }
    });
}
?>
