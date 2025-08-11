<?php
/**
 * Script de diagnostic et correction des données client
 * À exécuter une seule fois pour corriger le problème nom/email
 */

// Chargement de WordPress
require_once('../../../wp-config.php');
require_once('../../../wp-load.php');

global $wpdb;
$conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';

echo "<h2>🔍 DIAGNOSTIC DES DONNÉES CLIENT</h2>";

// 1. Afficher toutes les conversations actuelles
echo "<h3>📊 Conversations actuelles :</h3>";
$conversations = $wpdb->get_results("SELECT * FROM $conversations_table ORDER BY created_at DESC LIMIT 10");

if ($conversations) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>client_name</th><th>client_email</th><th>status</th><th>created_at</th></tr>";
    
    foreach ($conversations as $conv) {
        $name_color = (strpos($conv->client_name, '@') !== false) ? 'style="background-color: #ffcccc;"' : '';
        echo "<tr>";
        echo "<td>{$conv->id}</td>";
        echo "<td {$name_color}>{$conv->client_name}</td>";
        echo "<td>{$conv->client_email}</td>";
        echo "<td>{$conv->status}</td>";
        echo "<td>{$conv->created_at}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Aucune conversation trouvée.</p>";
}

// 2. Identifier les problèmes
echo "<h3>⚠️ Problèmes détectés :</h3>";
$problematic_conversations = $wpdb->get_results(
    "SELECT * FROM $conversations_table WHERE client_name LIKE '%@%'"
);

if ($problematic_conversations) {
    echo "<p style='color: red;'>🚨 " . count($problematic_conversations) . " conversation(s) avec email dans client_name :</p>";
    foreach ($problematic_conversations as $conv) {
        echo "<li>ID {$conv->id}: '{$conv->client_name}' (devrait être dans client_email)</li>";
    }
} else {
    echo "<p style='color: green;'>✅ Aucun problème détecté dans les données actuelles.</p>";
}

// 3. Correction automatique
if ($problematic_conversations && isset($_GET['fix']) && $_GET['fix'] === 'true') {
    echo "<h3>🔧 CORRECTION EN COURS...</h3>";
    
    $fixed_count = 0;
    foreach ($problematic_conversations as $conv) {
        if (strpos($conv->client_name, '@') !== false && empty($conv->client_email)) {
            // Déplacer l'email de client_name vers client_email
            $email = $conv->client_name;
            $name = 'Client'; // Nom par défaut
            
            $result = $wpdb->update(
                $conversations_table,
                array(
                    'client_name' => $name,
                    'client_email' => $email,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $conv->id),
                array('%s', '%s', '%s'),
                array('%d')
            );
            
            if ($result !== false) {
                echo "<p style='color: green;'>✅ Conversation {$conv->id} corrigée : '{$email}' → Nom: '{$name}', Email: '{$email}'</p>";
                $fixed_count++;
            } else {
                echo "<p style='color: red;'>❌ Erreur lors de la correction de la conversation {$conv->id}</p>";
            }
        }
    }
    
    echo "<h3>📈 RÉSULTAT :</h3>";
    echo "<p><strong>{$fixed_count} conversation(s) corrigée(s) avec succès !</strong></p>";
    
    // Afficher les données après correction
    echo "<h3>📊 Données après correction :</h3>";
    $conversations_after = $wpdb->get_results("SELECT * FROM $conversations_table ORDER BY created_at DESC LIMIT 10");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>client_name</th><th>client_email</th><th>status</th><th>updated_at</th></tr>";
    
    foreach ($conversations_after as $conv) {
        echo "<tr>";
        echo "<td>{$conv->id}</td>";
        echo "<td style='background-color: #ccffcc;'>{$conv->client_name}</td>";
        echo "<td style='background-color: #ccffcc;'>{$conv->client_email}</td>";
        echo "<td>{$conv->status}</td>";
        echo "<td>{$conv->updated_at}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else if ($problematic_conversations) {
    echo "<h3>🔧 CORRECTION DISPONIBLE :</h3>";
    echo "<p><a href='?fix=true' style='background: #ff6b35; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 CORRIGER MAINTENANT</a></p>";
}

// 4. Instructions pour l'utilisateur
echo "<h3>📋 INSTRUCTIONS :</h3>";
echo "<ol>";
echo "<li><strong>Si vous voyez des lignes rouges ci-dessus</strong> : Cliquez sur 'CORRIGER MAINTENANT'</li>";
echo "<li><strong>Après correction</strong> : Actualisez votre interface admin WordPress</li>";
echo "<li><strong>Testez</strong> : Créez une nouvelle conversation avec nom + email</li>";
echo "<li><strong>Supprimez ce fichier</strong> après utilisation pour des raisons de sécurité</li>";
echo "</ol>";

echo "<h3>🎯 ÉTAPES SUIVANTES :</h3>";
echo "<p>1. Allez dans WordPress Admin → Hotel Chatbot → Chatbot Admin</p>";
echo "<p>2. Vérifiez que les colonnes 'Nom' et 'Email' affichent les bonnes données</p>";
echo "<p>3. Testez une nouvelle conversation pour confirmer que tout fonctionne</p>";

?>
