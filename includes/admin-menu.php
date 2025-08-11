<?php
if (!defined('ABSPATH')) exit;

// Masquer le footer WordPress dans les pages admin du chatbot
function hotel_chatbot_hide_admin_footer() {
    $screen = get_current_screen();
    
    // Vérifier si nous sommes sur une page du chatbot
    if ($screen && strpos($screen->id, 'hotel-chatbot') !== false) {
        // Masquer le footer WordPress
        add_filter('admin_footer_text', '__return_empty_string', 11);
        add_filter('update_footer', '__return_empty_string', 11);
        
        // Ajouter du CSS personnalisé pour masquer complètement le footer
        add_action('admin_head', function() {
            echo '<style>
            #wpfooter { display: none !important; }
            #wpcontent { padding-bottom: 20px !important; }
            </style>';
        });
    }
}
add_action('current_screen', 'hotel_chatbot_hide_admin_footer');

// Ajouter le menu principal Hotel Chatbot dans la barre de navigation WordPress
function hotel_chatbot_add_admin_menu() {
    // Menu principal
    add_menu_page(
        'Hotel Chatbot',
        'Hotel Chatbot',
        'manage_options',
        'hotel-chatbot',
        'hotel_chatbot_admin_dashboard',
        'dashicons-format-chat',
        30
    );
    
    // Sous-menu: Chatbot Admin (historique des chats)
    add_submenu_page(
        'hotel-chatbot',
        'Chatbot Admin',
        'Chatbot Admin',
        'manage_options',
        'hotel-chatbot-admin',
        'hotel_chatbot_admin_page'
    );
    
    // Sous-menu: Réglages
    add_submenu_page(
        'hotel-chatbot',
        'Réglages',
        'Réglages',
        'manage_options',
        'hotel-chatbot-settings',
        'hotel_chatbot_settings_page'
    );
    
    // Sous-menu: Analytics
    add_submenu_page(
        'hotel-chatbot',
        'Analytics & Rapports',
        'Analytics',
        'manage_options',
        'hotel-chatbot-analytics',
        'hotel_chatbot_analytics_page'
    );
    
    // Sous-menu: Gestion des Réponses IA
    add_submenu_page(
        'hotel-chatbot',
        'Réponses IA',
        'Réponses IA',
        'manage_options',
        'hotel-chatbot-responses',
        'hotel_chatbot_responses_page'
    );
    
    // Sous-menu: Logs Système
    add_submenu_page(
        'hotel-chatbot',
        'Logs Système',
        'Logs',
        'manage_options',
        'hotel-chatbot-logs',
        'hotel_chatbot_logs_page'
    );
}

// Page principale du dashboard
function hotel_chatbot_admin_dashboard() {
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
    
    // Statistiques avancées
    $total_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table");
    $active_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table WHERE status = 'active'");
    $today_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table WHERE DATE(created_at) = CURDATE()");
    $week_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $month_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    // Statistiques des messages
    $total_messages = $wpdb->get_var("SELECT COUNT(*) FROM $messages_table");
    $avg_messages_per_conversation = $total_conversations > 0 ? round($total_messages / $total_conversations, 1) : 0;
    
    // Langues utilisées
    $language_stats = $wpdb->get_results("
        SELECT language, COUNT(*) as count 
        FROM $conversations_table 
        WHERE language IS NOT NULL AND language != '' 
        GROUP BY language 
        ORDER BY count DESC 
        LIMIT 5
    ");
    
    // Conversations récentes
    $recent_conversations = $wpdb->get_results("
        SELECT c.*, 
               (SELECT message FROM $messages_table WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
        FROM $conversations_table c 
        ORDER BY c.updated_at DESC 
        LIMIT 5
    ");
    
    // Données pour graphiques (derniers 7 jours)
    $daily_stats = $wpdb->get_results("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM $conversations_table 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    
    // Temps de réponse moyen (simulation)
    $avg_response_time = '2.3s';
    $satisfaction_rate = '94%';
    ?>
    <div class="wrap hotel-chatbot-admin">
        <div class="admin-header-enhanced">
            <h1>🏨 Hotel Chatbot Dashboard</h1>
            <p class="dashboard-subtitle">Vue d'ensemble de votre assistant hôtelier intelligent</p>
            <div class="last-update">Dernière mise à jour: <?php echo current_time('d/m/Y H:i'); ?></div>
        </div>
        
        <div class="hotel-chatbot-dashboard">
            <!-- Statistiques principales -->
            <div class="stats-grid">
                <div class="stat-card primary">
                    <div class="stat-card-header">
                        <h4 class="stat-card-title">Total Conversations</h4>
                        <div class="stat-card-icon primary">💬</div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($total_conversations); ?></div>
                    <div class="stat-card-change positive">+12% ce mois</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-card-header">
                        <h4 class="stat-card-title">Conversations Actives</h4>
                        <div class="stat-card-icon success">🟢</div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($active_conversations); ?></div>
                    <div class="stat-card-change positive">+5% cette semaine</div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-card-header">
                        <h4 class="stat-card-title">Aujourd'hui</h4>
                        <div class="stat-card-icon warning">📅</div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($today_conversations); ?></div>
                    <div class="stat-card-change neutral">Nouvelles conversations</div>
                </div>
                
                <div class="stat-card primary">
                    <div class="stat-card-header">
                        <h4 class="stat-card-title">Cette Semaine</h4>
                        <div class="stat-card-icon primary">📊</div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($week_conversations); ?></div>
                    <div class="stat-card-change positive">+8% vs semaine passée</div>
                </div>
                
                <div class="stat-card error">
                    <div class="stat-card-header">
                        <h4 class="stat-card-title">Ce Mois</h4>
                        <div class="stat-card-icon error">📈</div>
                    </div>
                    <div class="stat-card-value"><?php echo number_format($month_conversations); ?></div>
                    <div class="stat-card-change positive">+15% vs mois passé</div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-card-header">
                        <h4 class="stat-card-title">Messages/Conv</h4>
                        <div class="stat-card-icon success">💭</div>
                    </div>
                    <div class="stat-card-value"><?php echo $avg_messages_per_conversation; ?></div>
                    <div class="stat-card-change neutral">Moyenne par conversation</div>
                </div>
            </div>

            <!-- Graphiques et analyses -->
            <div class="dashboard-charts">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3 class="chart-title">📈 Évolution des Conversations</h3>
                        <p class="chart-subtitle">Derniers 7 jours</p>
                    </div>
                    <canvas id="conversationsChart" width="400" height="200"></canvas>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const canvas = document.getElementById('conversationsChart');
                        if (canvas) {
                            const ctx = canvas.getContext('2d');
                            const data = <?php echo json_encode($daily_stats); ?>;
                            
                            // Simple line chart
                            ctx.strokeStyle = '#3b82f6';
                            ctx.lineWidth = 3;
                            ctx.beginPath();
                            
                            const maxValue = Math.max(...data.map(d => parseInt(d.count))) || 1;
                            const width = canvas.width - 40;
                            const height = canvas.height - 40;
                            
                            data.forEach((point, index) => {
                                const x = 20 + (index * width / (data.length - 1 || 1));
                                const y = height - (parseInt(point.count) / maxValue * (height - 20)) + 20;
                                
                                if (index === 0) {
                                    ctx.moveTo(x, y);
                                } else {
                                    ctx.lineTo(x, y);
                                }
                                
                                // Points
                                ctx.fillStyle = '#3b82f6';
                                ctx.beginPath();
                                ctx.arc(x, y, 4, 0, 2 * Math.PI);
                                ctx.fill();
                                ctx.beginPath();
                            });
                            
                            ctx.stroke();
                        }
                    });
                    </script>
                </div>
                
                <div class="chart-container">
                    <div class="chart-header">
                        <h3 class="chart-title">🌍 Langues Utilisées</h3>
                        <p class="chart-subtitle">Top 5 langues</p>
                    </div>
                    <div class="languages-list">
                        <?php if (!empty($language_stats)): ?>
                            <?php foreach ($language_stats as $lang): ?>
                                <?php 
                                $percentage = $total_conversations > 0 ? round(($lang->count / $total_conversations) * 100, 1) : 0;
                                $flag = '';
                                switch(strtoupper($lang->language)) {
                                    case 'FR': $flag = '🇫🇷'; break;
                                    case 'EN': $flag = '🇬🇧'; break;
                                    case 'ES': $flag = '🇪🇸'; break;
                                    case 'AR': $flag = '🇸🇦'; break;
                                    case 'DE': $flag = '🇩🇪'; break;
                                    case 'DARIJA': $flag = '🇲🇦'; break;
                                    default: $flag = '🌐'; break;
                                }
                                ?>
                                <div class="language-item">
                                    <div class="language-info">
                                        <span class="language-flag"><?php echo $flag; ?></span>
                                        <span class="language-name"><?php echo strtoupper($lang->language); ?></span>
                                        <span class="language-count"><?php echo $lang->count; ?> conversations</span>
                                    </div>
                                    <div class="language-bar">
                                        <div class="language-progress" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                    <div class="language-percentage"><?php echo $percentage; ?>%</div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-data">Aucune donnée de langue disponible</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Conversations récentes -->
            <div class="recent-conversations">
                <div class="section-header">
                    <h3>💬 Conversations Récentes</h3>
                    <a href="<?php echo admin_url('admin.php?page=hotel-chatbot-admin'); ?>" class="btn primary">Voir toutes</a>
                </div>
                
                <div class="conversations-list">
                    <?php if (!empty($recent_conversations)): ?>
                        <?php foreach ($recent_conversations as $conversation): ?>
                            <div class="conversation-item">
                                <div class="conversation-info">
                                    <div class="conversation-client">
                                        <div class="client-name">
                                            👤 <?php echo esc_html($conversation->client_name ?: 'Client anonyme'); ?>
                                        </div>
                                        <?php if (!empty($conversation->client_email)): ?>
                                            <div class="client-email">
                                                📧 <?php echo esc_html($conversation->client_email); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="conversation-preview">
                                        <?php echo esc_html(wp_trim_words($conversation->last_message ?: 'Aucun message', 10)); ?>
                                    </div>
                                    <div class="conversation-meta">
                                        <span class="status-badge <?php echo $conversation->status; ?>">
                                            <?php echo ucfirst($conversation->status); ?>
                                        </span>
                                        <span class="conversation-time">
                                            📅 <?php echo date('d/m/Y H:i', strtotime($conversation->updated_at)); ?>
                                        </span>
                                        <?php if ($conversation->language): ?>
                                            <span class="conversation-lang">🌐 <?php echo strtoupper($conversation->language); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="conversation-actions">
                                    <button class="btn small view-conversation" data-id="<?php echo $conversation->id; ?>">
                                        👁️ Voir
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-conversations">
                            <p>🤷‍♂️ Aucune conversation trouvée</p>
                            <p><small>Les conversations apparaîtront ici dès que des clients utiliseront le chatbot.</small></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="quick-actions">
                <a href="<?php echo admin_url('admin.php?page=hotel-chatbot-admin'); ?>" class="quick-action-card">
                    <div class="quick-action-icon">👥</div>
                    <div class="quick-action-title">Gérer Conversations</div>
                    <div class="quick-action-desc">Voir et répondre aux clients</div>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=hotel-chatbot-analytics'); ?>" class="quick-action-card">
                    <div class="quick-action-icon">📊</div>
                    <div class="quick-action-title">Analytics</div>
                    <div class="quick-action-desc">Rapports détaillés</div>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=hotel-chatbot-responses'); ?>" class="quick-action-card">
                    <div class="quick-action-icon">🤖</div>
                    <div class="quick-action-title">Réponses IA</div>
                    <div class="quick-action-desc">Personnaliser l'IA</div>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=hotel-chatbot-settings'); ?>" class="quick-action-card">
                    <div class="quick-action-icon">⚙️</div>
                    <div class="quick-action-title">Paramètres</div>
                    <div class="quick-action-desc">Configuration générale</div>
                </a>
            </div>
        </div>
    </div>
    <?php
}

// Page Chatbot Admin (historique des chats)
function hotel_chatbot_admin_page() {
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
    
    // NETTOYAGE : Supprimer les anciennes données de test
    $wpdb->query("DELETE FROM $messages_table WHERE conversation_id IN (SELECT id FROM $conversations_table WHERE client_email IN ('zohar@example.com', 'jawad@example.com', 'ahmedbdenalla1992@gmail.com'))");
    $wpdb->query("DELETE FROM $conversations_table WHERE client_email IN ('zohar@example.com', 'jawad@example.com', 'ahmedbdenalla1992@gmail.com')");
    
    // Gérer la correction automatique des données si demandée
    if (isset($_GET['fix_data']) && $_GET['fix_data'] === '1') {
        hotel_chatbot_fix_client_names_admin();
        return;
    }
    
    // Vérifier s'il y a des données problématiques
    $problematic_count = $wpdb->get_var(
        "SELECT COUNT(*) FROM $conversations_table 
         WHERE client_name LIKE '%@%' OR client_name = client_email"
    );
    
    // CORRECTION AUTOMATIQUE : Fixer les données où l'email est dans client_name
    // Seulement si le champ email est vraiment vide ET que client_name contient un email valide
    $wpdb->query(
        "UPDATE $conversations_table 
         SET client_email = client_name, client_name = 'Client' 
         WHERE client_name LIKE '%@%' 
         AND client_name REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'
         AND (client_email IS NULL OR client_email = '' OR client_email = client_name)"
    );
    
    // Récupérer les conversations avec compteur de messages et dernier message
    $conversations = $wpdb->get_results(
        "SELECT c.*, 
                COUNT(m.id) as message_count,
                MAX(m.created_at) as last_message_time,
                (
                    SELECT m2.message 
                    FROM $messages_table m2 
                    WHERE m2.conversation_id = c.id 
                    ORDER BY m2.created_at DESC 
                    LIMIT 1
                ) as last_message
         FROM $conversations_table c 
         LEFT JOIN $messages_table m ON c.id = m.conversation_id 
         GROUP BY c.id 
         ORDER BY c.updated_at DESC"
    );
    
    // Statistiques rapides
    $total_conversations = count($conversations);
    $active_conversations = count(array_filter($conversations, function($c) { return $c->status === 'active'; }));
    $today_conversations = count(array_filter($conversations, function($c) { 
        return date('Y-m-d', strtotime($c->created_at)) === date('Y-m-d'); 
    }));
    ?>
    <div class="wrap hotel-chatbot-admin">
        <!-- Header enrichi -->
        <div class="admin-header-enhanced">
            <h1>💬 Historique des Conversations</h1>
            <p class="admin-subtitle">Gérez et consultez toutes les conversations clients</p>
        </div>

        <!-- Statistiques rapides -->
        <div class="stats-grid stats-mini">
            <div class="stat-card primary">
                <div class="stat-card-header">
                    <h4 class="stat-card-title">Total</h4>
                    <div class="stat-card-icon primary">💬</div>
                </div>
                <div class="stat-card-value"><?php echo $total_conversations; ?></div>
                <div class="stat-card-change">Conversations</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-card-header">
                    <h4 class="stat-card-title">Actives</h4>
                    <div class="stat-card-icon success">🟢</div>
                </div>
                <div class="stat-card-value"><?php echo $active_conversations; ?></div>
                <div class="stat-card-change">En cours</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-card-header">
                    <h4 class="stat-card-title">Aujourd'hui</h4>
                    <div class="stat-card-icon info">📅</div>
                </div>
                <div class="stat-card-value"><?php echo $today_conversations; ?></div>
                <div class="stat-card-change">Nouvelles</div>
            </div>
        </div>
        
        <!-- Filtres et recherche -->
        <div class="admin-filters">
            <div class="filter-group">
                <input type="text" id="search-conversations" placeholder="🔍 Rechercher par nom ou email..." class="search-input">
                <select id="filter-status" class="filter-select">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actif</option>
                    <option value="closed">Fermé</option>
                    <option value="pending">En attente</option>
                </select>
                <select id="filter-language" class="filter-select">
                    <option value="">Toutes les langues</option>
                    <option value="fr">Français</option>
                    <option value="en">English</option>
                    <option value="es">Español</option>
                </select>
            </div>
            
            <?php if ($problematic_count > 0): ?>
            <div class="admin-alert warning">
                <div class="alert-icon">⚠️</div>
                <div class="alert-content">
                    <strong>Données à corriger :</strong> <?php echo $problematic_count; ?> conversation(s) ont des problèmes avec les noms de clients.
                    <a href="<?php echo admin_url('admin.php?page=hotel-chatbot&fix_data=1'); ?>" class="btn btn-warning" style="margin-left: 10px;">
                        🔧 Corriger automatiquement
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
            
            <div class="conversations-list">
                <?php if (empty($conversations)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">💬</div>
                        <h3>Aucune conversation</h3>
                        <p>Les conversations clients apparaîtront ici une fois qu'ils commenceront à utiliser le chatbot.</p>
                    </div>
                <?php else: ?>
                    <!-- Tableau Excel-style -->
                    <div class="excel-table-container">
                        <table class="excel-table conversations-table">
                            <thead>
                                <tr>
                                    <th class="col-select">
                                        <input type="checkbox" id="select-all-conversations" class="table-checkbox">
                                    </th>
                                    <th class="col-avatar">👤</th>
                                    <th class="col-client-name sortable" data-sort="client_name">
                                        <span>Nom</span>
                                        <i class="sort-icon">↕️</i>
                                    </th>
                                    <th class="col-client-email sortable" data-sort="client_email">
                                        <span>Email</span>
                                        <i class="sort-icon">↕️</i>
                                    </th>
                                    <th class="col-language sortable" data-sort="language">
                                        <span>Langue</span>
                                        <i class="sort-icon">↕️</i>
                                    </th>
                                    <th class="col-messages sortable" data-sort="message_count">
                                        <span>Messages</span>
                                        <i class="sort-icon">↕️</i>
                                    </th>
                                    <th class="col-last-message">Dernier Message</th>
                                    <th class="col-status sortable" data-sort="status">
                                        <span>Statut</span>
                                        <i class="sort-icon">↕️</i>
                                    </th>
                                    <th class="col-date sortable" data-sort="created_at">
                                        <span>Date</span>
                                        <i class="sort-icon">↕️</i>
                                    </th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($conversations as $index => $conv): ?>
                                    <tr class="conversation-row" 
                                        data-id="<?php echo $conv->id; ?>"
                                        data-status="<?php echo $conv->status; ?>" 
                                        data-language="<?php echo $conv->language; ?>">
                                        
                                        <!-- Checkbox de sélection -->
                                        <td class="col-select">
                                            <input type="checkbox" class="conversation-checkbox table-checkbox" value="<?php echo $conv->id; ?>">
                                        </td>
                                        
                                        <!-- Avatar -->
                                        <td class="col-avatar">
                                            <div class="client-avatar-small">
                                                <?php echo strtoupper(substr($conv->client_name, 0, 2)); ?>
                                            </div>
                                        </td>
                                        
                                        <!-- Nom du client -->
                                        <td class="col-client-name">
                                            <strong class="client-name"><?php echo esc_html($conv->client_name ?: 'Anonyme'); ?></strong>
                                        </td>
                                        
                                        <!-- Email du client -->
                                        <td class="col-client-email">
                                            <span class="client-email"><?php echo esc_html($conv->client_email ?: 'Non renseigné'); ?></span>
                                        </td>
                                        
                                        <!-- Langue -->
                                        <td class="col-language">
                                            <span class="language-flag">
                                                <?php 
                                                $flags = [
                                                    'fr' => '🇫🇷',
                                                    'en' => '🇬🇧', 
                                                    'es' => '🇪🇸',
                                                    'ar' => '🇸🇦',
                                                    'de' => '🇩🇪'
                                                ];
                                                echo $flags[$conv->language] ?? '🌐';
                                                ?>
                                            </span>
                                            <span class="language-code"><?php echo strtoupper($conv->language); ?></span>
                                        </td>
                                        
                                        <!-- Nombre de messages -->
                                        <td class="col-messages">
                                            <span class="message-count-badge">
                                                <i class="icon">💬</i>
                                                <?php echo $conv->message_count; ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Dernier message -->
                                        <td class="col-last-message">
                                            <div class="last-message-preview">
                                                <?php 
                                                if (!empty($conv->last_message)) {
                                                    echo esc_html(substr($conv->last_message, 0, 60)) . (strlen($conv->last_message) > 60 ? '...' : '');
                                                } else {
                                                    echo '<em style="color: #6b7280;">Aucun message</em>';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        
                                        <!-- Statut -->
                                        <td class="col-status">
                                            <span class="status-badge status-<?php echo $conv->status; ?>">
                                                <i class="status-icon"></i>
                                                <?php echo ucfirst($conv->status); ?>
                                            </span>
                                        </td>
                                        
                                        <!-- Date -->
                                        <td class="col-date">
                                            <div class="date-cell">
                                                <div class="date-main"><?php echo date('d/m/Y', strtotime($conv->created_at)); ?></div>
                                                <div class="time-sub"><?php echo date('H:i', strtotime($conv->created_at)); ?></div>
                                            </div>
                                        </td>
                                        
                                        <!-- Actions -->
                                        <td class="col-actions">
                                            <div class="action-buttons">
                                                <button class="btn-action btn-view view-conversation" 
                                                        data-id="<?php echo $conv->id; ?>" 
                                                        title="Voir la conversation">
                                                    <i class="icon">👁️</i>
                                                </button>
                                                <button class="btn-action btn-delete" 
                                                        data-id="<?php echo $conv->id; ?>" 
                                                        title="Supprimer">
                                                    <i class="icon">🗑️</i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Pagination et actions en bas -->
                        <div class="table-footer">
                            <div class="table-info">
                                <span class="selected-count">0 sélectionné(s)</span>
                                <span class="total-count"><?php echo count($conversations); ?> conversation(s) au total</span>
                            </div>
                            <div class="bulk-actions">
                                <select class="bulk-action-select">
                                    <option value="">Actions groupées</option>
                                    <option value="mark-read">Marquer comme lu</option>
                                    <option value="mark-unread">Marquer comme non lu</option>
                                    <option value="close">Fermer</option>
                                    <option value="delete">Supprimer</option>
                                </select>
                                <button class="btn btn-secondary apply-bulk-action">Appliquer</button>
                            </div>
                            <div class="table-pagination">
                                <button class="btn btn-sm" disabled>← Précédent</button>
                                <span class="page-info">Page 1 sur 1</span>
                                <button class="btn btn-sm" disabled>Suivant →</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
            
        <!-- Modal de conversation -->
        <div id="conversation-modal" class="modal-overlay" style="display: none;">
            <div class="modal-container">
                <div class="modal-header">
                    <h3>💬 Détails de la Conversation</h3>
                    <button class="modal-close" id="close-modal">&times;</button>
                </div>
                
                <div class="modal-body">
                    <div id="conversation-messages" class="messages-container"></div>
                </div>
                
                <div class="modal-footer">
                    <div class="admin-reply-section">
                        <textarea id="admin-reply-text" placeholder="💭 Tapez votre réponse..." class="admin-textarea"></textarea>
                        <div class="reply-actions">
                            <button id="send-admin-reply" class="btn btn-primary">
                                📤 Envoyer la réponse
                            </button>
                            <button class="btn btn-secondary" onclick="document.getElementById('admin-reply-text').value = ''">
                                🗑️ Effacer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Fonction helper pour générer un sélecteur de couleur amélioré
function render_color_input($name, $default_color, $icon, $title, $description) {
    $current_value = get_option($name, $default_color);
    $field_id = str_replace('hotel_chatbot_', '', $name);
    ?>
    <div class="color-option">
        <div class="color-card">
            <div class="color-icon"><?php echo $icon; ?></div>
            <h4><?php echo $title; ?></h4>
            <p><?php echo $description; ?></p>
            <div class="color-input-wrapper">
                <input type="color" name="<?php echo $name; ?>" 
                       value="<?php echo $current_value; ?>" 
                       class="color-picker" id="<?php echo $field_id; ?>_picker">
                <div class="color-code-input">
                    <input type="text" name="<?php echo $name; ?>_code" 
                           value="<?php echo $current_value; ?>" 
                           class="color-code-field" id="<?php echo $field_id; ?>_code" 
                           pattern="^#[0-9A-Fa-f]{6}$" 
                           placeholder="<?php echo $default_color; ?>" 
                           maxlength="7">
                    <button type="button" class="color-copy-btn" title="Copier le code couleur">
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Fonction pour récupérer la couleur (priorité au champ texte)
function get_color_value($base_name, $default) {
    $code_field = $base_name . '_code';
    $picker_field = $base_name;
    
    if (!empty($_POST[$code_field]) && $_POST[$code_field] !== '') {
        $color = sanitize_hex_color($_POST[$code_field]);
        return $color ?: $default;
    } elseif (!empty($_POST[$picker_field]) && $_POST[$picker_field] !== '') {
        $color = sanitize_hex_color($_POST[$picker_field]);
        return $color ?: $default;
    }
    return $default;
}

// Page des réglages modernisée
function hotel_chatbot_settings_page() {
    // Vérification de sécurité
    if (isset($_POST['submit']) && !wp_verify_nonce($_POST['hotel_chatbot_settings_nonce'], 'hotel_chatbot_save_settings')) {
        wp_die('Erreur de sécurité - Nonce invalide');
    }
    
    // Traitement de la sauvegarde
    if (isset($_POST['submit']) && wp_verify_nonce($_POST['hotel_chatbot_settings_nonce'], 'hotel_chatbot_save_settings')) {
        
        // Sauvegarder les réglages avec validation
        $settings = array(
            // Couleurs principales (legacy)
            'hotel_chatbot_primary_color' => get_color_value('hotel_chatbot_primary_color', '#2563eb'),
            'hotel_chatbot_secondary_color' => get_color_value('hotel_chatbot_secondary_color', '#1d4ed8'),
            
            // Couleurs essentielles - priorité aux champs de code couleur
            'hotel_chatbot_header_color' => get_color_value('hotel_chatbot_header_color', '#2563eb'),
            'hotel_chatbot_floating_button_color' => get_color_value('hotel_chatbot_floating_button_color', '#3b82f6'),
            'hotel_chatbot_send_button_color' => get_color_value('hotel_chatbot_send_button_color', '#10b981'),
            'hotel_chatbot_user_message_color' => get_color_value('hotel_chatbot_user_message_color', '#3b82f6'),
            'hotel_chatbot_bot_message_color' => get_color_value('hotel_chatbot_bot_message_color', '#f3f4f6'),
            'hotel_chatbot_background_color' => get_color_value('hotel_chatbot_background_color', '#ffffff'),
            'hotel_chatbot_text_color' => get_color_value('hotel_chatbot_text_color', '#374151'),
            'hotel_chatbot_position' => sanitize_text_field($_POST['hotel_chatbot_position'] ?? 'bottom-right'),
            'hotel_chatbot_welcome_message_fr' => sanitize_textarea_field($_POST['hotel_chatbot_welcome_message_fr'] ?? ''),
            'hotel_chatbot_welcome_message_en' => sanitize_textarea_field($_POST['hotel_chatbot_welcome_message_en'] ?? ''),
            'hotel_chatbot_welcome_message_es' => sanitize_textarea_field($_POST['hotel_chatbot_welcome_message_es'] ?? ''),
            'hotel_chatbot_welcome_message_ar' => sanitize_textarea_field($_POST['hotel_chatbot_welcome_message_ar'] ?? ''),
            'hotel_chatbot_default_language' => sanitize_text_field($_POST['hotel_chatbot_default_language'] ?? 'fr'),
            'hotel_chatbot_enable_multilingual' => isset($_POST['hotel_chatbot_enable_multilingual']) ? '1' : '0',
            'hotel_chatbot_require_name' => isset($_POST['hotel_chatbot_require_name']) ? '1' : '0',
            'hotel_chatbot_enable_sound' => isset($_POST['hotel_chatbot_enable_sound']) ? '1' : '0',
            'hotel_chatbot_auto_open' => isset($_POST['hotel_chatbot_auto_open']) ? '1' : '0',
            'hotel_chatbot_enable_ai' => isset($_POST['hotel_chatbot_enable_ai']) ? '1' : '0',
            'hotel_chatbot_openai_api_key' => sanitize_text_field($_POST['hotel_chatbot_openai_api_key'] ?? ''),
            'hotel_chatbot_chat_title' => sanitize_text_field($_POST['hotel_chatbot_chat_title'] ?? 'Assistant Hôtel'),
            'hotel_chatbot_offline_message' => sanitize_textarea_field($_POST['hotel_chatbot_offline_message'] ?? ''),
            'hotel_chatbot_max_messages' => intval($_POST['hotel_chatbot_max_messages'] ?? 100),
            'hotel_chatbot_typing_delay' => intval($_POST['hotel_chatbot_typing_delay'] ?? 1500),
            'hotel_chatbot_avatar_url' => esc_url_raw($_POST['hotel_chatbot_avatar_url'] ?? '')
        );
        
        // Sauvegarder chaque option
        $saved_count = 0;
        foreach ($settings as $key => $value) {
            $result = update_option($key, $value);
            if ($result || get_option($key) === $value) {
                $saved_count++;
            }
        }
        
        // Message de succès
        $success_message = "Paramètres sauvegardés avec succès ! ({$saved_count}/" . count($settings) . " options)";
    }
    
    // Récupérer les valeurs actuelles
    $current_settings = array(
        'primary_color' => get_option('hotel_chatbot_primary_color', '#2563eb'),
        'secondary_color' => get_option('hotel_chatbot_secondary_color', '#1d4ed8'),
        'position' => get_option('hotel_chatbot_position', 'bottom-right'),
        'welcome_fr' => get_option('hotel_chatbot_welcome_message_fr', 'Bonjour ! Comment puis-je vous aider aujourd\'hui ?'),
        'welcome_en' => get_option('hotel_chatbot_welcome_message_en', 'Hello! How can I help you today?'),
        'welcome_es' => get_option('hotel_chatbot_welcome_message_es', '¡Hola! ¿Cómo puedo ayudarte hoy?'),
        'welcome_ar' => get_option('hotel_chatbot_welcome_message_ar', 'مرحبا! كيف يمكنني مساعدتك اليوم؟'),
        'default_language' => get_option('hotel_chatbot_default_language', 'fr'),
        'enable_multilingual' => get_option('hotel_chatbot_enable_multilingual', '1'),
        'require_name' => get_option('hotel_chatbot_require_name', '0'),
        'enable_sound' => get_option('hotel_chatbot_enable_sound', '1'),
        'auto_open' => get_option('hotel_chatbot_auto_open', '0'),
        'enable_ai' => get_option('hotel_chatbot_enable_ai', '1'),
        'openai_api_key' => get_option('hotel_chatbot_openai_api_key', ''),
        'chat_title' => get_option('hotel_chatbot_chat_title', 'Assistant Hôtel'),
        'offline_message' => get_option('hotel_chatbot_offline_message', 'Nous ne sommes pas disponibles pour le moment. Laissez-nous un message !'),
        'max_messages' => get_option('hotel_chatbot_max_messages', 100),
        'typing_delay' => get_option('hotel_chatbot_typing_delay', 1500),
        'avatar_url' => get_option('hotel_chatbot_avatar_url', ''),
        'enable_cookies' => get_option('hotel_chatbot_enable_cookies', '1'),
        'cookie_expiration_days' => get_option('hotel_chatbot_cookie_expiration_days', '30')
    );
    
    // Statistiques
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
    
    $total_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table");
    $total_messages = $wpdb->get_var("SELECT COUNT(*) FROM $messages_table");
    $active_conversations = $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table WHERE status = 'active'");
    $today_conversations = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $conversations_table WHERE DATE(created_at) = %s",
        current_time('Y-m-d')
    ));
    ?>
    <div class="wrap hotel-chatbot-admin">
        <!-- Header enrichi -->
        <div class="admin-header-enhanced">
            <h1>⚙️ Configuration du Chatbot</h1>
            <p class="admin-subtitle">Personnalisez l'apparence et le comportement de votre assistant hôtelier</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="notice notice-success is-dismissible" style="border-left-color: #10b981; background: #f0fdf4;">
                <p><strong>🎉 <?php echo esc_html($success_message); ?></strong></p>
                <p style="margin: 5px 0 0 0; font-size: 13px; color: #059669;">
                    Les nouvelles couleurs sont maintenant actives sur votre chatbot !
                </p>
            </div>
        <?php endif; ?>

        <!-- Statistiques rapides -->
        <div class="stats-grid stats-mini">
            <div class="stat-card primary">
                <div class="stat-card-header">
                    <h4 class="stat-card-title">Conversations</h4>
                    <div class="stat-card-icon primary">💬</div>
                </div>
                <div class="stat-card-value"><?php echo $total_conversations ?: 0; ?></div>
                <div class="stat-card-change">Total</div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-card-header">
                    <h4 class="stat-card-title">Messages</h4>
                    <div class="stat-card-icon success">📨</div>
                </div>
                <div class="stat-card-value"><?php echo $total_messages ?: 0; ?></div>
                <div class="stat-card-change">Échangés</div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-card-header">
                    <h4 class="stat-card-title">Actives</h4>
                    <div class="stat-card-icon info">🟢</div>
                </div>
                <div class="stat-card-value"><?php echo $active_conversations ?: 0; ?></div>
                <div class="stat-card-change">En cours</div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-card-header">
                    <h4 class="stat-card-title">Aujourd'hui</h4>
                    <div class="stat-card-icon warning">📅</div>
                </div>
                <div class="stat-card-value"><?php echo $today_conversations ?: 0; ?></div>
                <div class="stat-card-change">Nouvelles</div>
            </div>
        </div>

        <form method="post" action="" class="hotel-chatbot-settings-form">
            <?php wp_nonce_field('hotel_chatbot_save_settings', 'hotel_chatbot_settings_nonce'); ?>
            
            <div class="settings-container">
                <!-- Section Personnalisation des Couleurs avec Aperçu -->
                <div class="admin-content-card">
                    <div class="card-header">
                        <h3>🎨 Personnalisation des Couleurs</h3>
                        <p>Personnalisez chaque élément du chatbot avec des couleurs spécifiques et voyez le résultat en temps réel</p>
                    </div>
                    <div class="card-content">
                        <!-- Layout côte à côte : Couleurs + Aperçu -->
                        <div class="colors-and-preview-layout">
                            <!-- Zone de personnalisation des couleurs -->
                            <div class="color-customization-section">
                                <h4>🎨 Sélection des Couleurs</h4>
                                <div class="color-customization-grid">
                                    <!-- Couleurs Essentielles (7 options) -->
                                    <?php 
                                    render_color_input('hotel_chatbot_header_color', '#2563eb', '🏷️', 'En-tête du Chatbot', 'Couleur de l\'en-tête de la fenêtre de chat');
                                    render_color_input('hotel_chatbot_floating_button_color', '#3b82f6', '🔘', 'Bouton Flottant', 'Couleur du bouton d\'ouverture du chatbot');
                                    render_color_input('hotel_chatbot_send_button_color', '#10b981', '📤', 'Bouton d\'Envoi', 'Couleur du bouton pour envoyer les messages');
                                    render_color_input('hotel_chatbot_user_message_color', '#3b82f6', '💬', 'Messages Utilisateur', 'Couleur des bulles de messages des clients');
                                    render_color_input('hotel_chatbot_bot_message_color', '#f3f4f6', '🤖', 'Messages Assistant', 'Couleur des bulles de messages de l\'assistant');
                                    render_color_input('hotel_chatbot_background_color', '#ffffff', '🎨', 'Arrière-plan', 'Couleur de fond de la fenêtre de chat');
                                    render_color_input('hotel_chatbot_text_color', '#374151', '📝', 'Couleur du Texte', 'Couleur principale du texte dans le chatbot');
                                    ?>
                                </div>
                                
                                <!-- Actions rapides -->
                                <div class="color-actions">
                                    <button type="button" class="button" id="reset-colors">🔄 Réinitialiser</button>
                                    <button type="button" class="button" id="copy-color-scheme">📋 Copier le schéma</button>
                                    <div class="preset-themes">
                                        <h5>🎨 Thèmes Prédéfinis</h5>
                                        <div class="theme-buttons">
                                            <button type="button" class="button theme-button" data-theme="blue">🔵 Bleu</button>
                                            <button type="button" class="button theme-button" data-theme="green">🟢 Vert</button>
                                            <button type="button" class="button theme-button" data-theme="orange">🟠 Orange</button>
                                            <button type="button" class="button theme-button" data-theme="purple">🟣 Violet</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Aperçu en temps réel -->
                            <div class="color-preview-section">
                                <h4>🔍 Aperçu en Temps Réel</h4>
                                <p class="description">Voyez vos couleurs appliquées instantanément</p>
                                
                                <div class="mini-chatbot-preview" id="mini-chatbot-preview">
                                    <div class="preview-header" id="preview-header">
                                        <span class="preview-title">🏨 Hotel Chatbot - Assistant Réservations</span>
                                        <span class="preview-status">● En ligne</span>
                                    </div>
                                    
                                    <div class="preview-messages" id="preview-messages">
                                        <div class="preview-message bot-message">
                                            <div class="message-avatar">🏨</div>
                                            <div class="message-content preview-bot-message">
                                                Bonjour ! Je suis votre assistant hôtelier. Comment puis-je vous aider aujourd'hui ?
                                            </div>
                                        </div>
                                        
                                        <div class="preview-message user-message">
                                            <div class="message-content preview-user-message">
                                                Je voudrais réserver une chambre pour ce soir
                                            </div>
                                            <div class="message-avatar">👤</div>
                                        </div>
                                        
                                        <div class="preview-message bot-message">
                                            <div class="message-avatar">🏨</div>
                                            <div class="message-content success-message">
                                                ✅ Parfait ! Nous avons des chambres disponibles pour ce soir.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="preview-input-area" id="preview-input-area">
                                        <input type="text" placeholder="Tapez votre message..." disabled>
                                        <button class="preview-send-button preview-send-btn" id="preview-send-button">📤</button>
                                    </div>
                                </div>
                                
                                <!-- Contrôles de test de l'aperçu -->
                                <div class="preview-controls">
                                    <button type="button" class="button button-small" id="test-typing">💬 Test frappe</button>
                                    <button type="button" class="button button-small" id="test-error">⚠️ Test erreur</button>
                                    <button type="button" class="button button-small" id="reset-preview">🔄 Reset</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">
                                    <span class="label-text">Position du Chatbot</span>
                                    <span class="label-desc">Où afficher le bouton du chatbot</span>
                                </label>
                                <select name="hotel_chatbot_position" class="modern-select">
                                    <option value="bottom-right" <?php selected($current_settings['position'], 'bottom-right'); ?>>🔽➡️ Bas Droite</option>
                                    <option value="bottom-left" <?php selected($current_settings['position'], 'bottom-left'); ?>>🔽⬅️ Bas Gauche</option>
                                    <option value="top-right" <?php selected($current_settings['position'], 'top-right'); ?>>🔼➡️ Haut Droite</option>
                                    <option value="top-left" <?php selected($current_settings['position'], 'top-left'); ?>>🔼⬅️ Haut Gauche</option>
                                </select>
                            </div>
                            
                            <div class="setting-item avatar-upload-item">
                                <label class="setting-label">
                                    <span class="label-text">Avatar du Chatbot</span>
                                    <span class="label-desc">Image personnalisée pour l'avatar (recommandé : 64x64px, PNG/JPG)</span>
                                </label>
                                <div class="avatar-upload-wrapper">
                                    <?php 
                                    $current_avatar = isset($current_settings['avatar_url']) ? $current_settings['avatar_url'] : '';
                                    ?>
                                    <div class="avatar-preview">
                                        <?php if ($current_avatar): ?>
                                            <img src="<?php echo esc_url($current_avatar); ?>" alt="Avatar actuel" class="current-avatar">
                                        <?php else: ?>
                                            <div class="default-avatar">
                                                <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9M19 9H14V4H5V21H19V9Z"/>
                                                </svg>
                                                <span>Aucun avatar</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="avatar-controls">
                                        <button type="button" class="btn-upload-avatar" id="upload-avatar-btn">
                                            <span class="upload-icon">📁</span>
                                            <?php echo $current_avatar ? 'Changer l\'avatar' : 'Choisir un avatar'; ?>
                                        </button>
                                        <?php if ($current_avatar): ?>
                                            <button type="button" class="btn-remove-avatar" id="remove-avatar-btn">
                                                <span class="remove-icon">🗑️</span>
                                                Supprimer
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <input type="hidden" name="hotel_chatbot_avatar_url" id="avatar-url-input" value="<?php echo esc_attr($current_avatar); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section Messages Multilingues -->
                <div class="admin-content-card">
                    <div class="card-header">
                        <h3>🌍 Messages de Bienvenue</h3>
                        <p>Configurez les messages d'accueil dans différentes langues</p>
                    </div>
                    <div class="card-content">
                        <div class="language-tabs">
                            <div class="tab-nav">
                                <button type="button" class="tab-btn active" data-tab="fr">🇫🇷 Français</button>
                                <button type="button" class="tab-btn" data-tab="en">🇺🇸 English</button>
                                <button type="button" class="tab-btn" data-tab="es">🇪🇸 Español</button>
                                <button type="button" class="tab-btn" data-tab="ar">🇸🇦 العربية</button>
                            </div>
                            <div class="tab-content">
                                <div class="tab-pane active" id="tab-fr">
                                    <textarea name="hotel_chatbot_welcome_message_fr" class="modern-textarea" 
                                              placeholder="Bonjour ! Comment puis-je vous aider aujourd'hui ?"><?php echo esc_textarea($current_settings['welcome_fr']); ?></textarea>
                                </div>
                                <div class="tab-pane" id="tab-en">
                                    <textarea name="hotel_chatbot_welcome_message_en" class="modern-textarea" 
                                              placeholder="Hello! How can I help you today?"><?php echo esc_textarea($current_settings['welcome_en']); ?></textarea>
                                </div>
                                <div class="tab-pane" id="tab-es">
                                    <textarea name="hotel_chatbot_welcome_message_es" class="modern-textarea" 
                                              placeholder="¡Hola! ¿Cómo puedo ayudarte hoy?"><?php echo esc_textarea($current_settings['welcome_es']); ?></textarea>
                                </div>
                                <div class="tab-pane" id="tab-ar">
                                    <textarea name="hotel_chatbot_welcome_message_ar" class="modern-textarea" 
                                              placeholder="مرحبا! كيف يمكنني مساعدتك اليوم؟"><?php echo esc_textarea($current_settings['welcome_ar']); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section Intelligence Artificielle -->
                <div class="admin-content-card">
                    <div class="card-header">
                        <h3>🤖 Intelligence Artificielle</h3>
                        <p>Configurez les réponses automatiques intelligentes</p>
                    </div>
                    <div class="card-content">
                        <div class="settings-grid">
                            <div class="setting-item toggle-item">
                                <label class="setting-label">
                                    <span class="label-text">Activer l'IA</span>
                                    <span class="label-desc">Active les réponses intelligentes automatiques</span>
                                </label>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="hotel_chatbot_enable_ai" value="1" 
                                           <?php checked($current_settings['enable_ai'], '1'); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">
                                    <span class="label-text">Clé API OpenAI</span>
                                    <span class="label-desc">Votre clé API pour les réponses IA</span>
                                </label>
                                <div class="api-key-wrapper">
                                    <input type="password" name="hotel_chatbot_openai_api_key" 
                                           value="<?php echo esc_attr($current_settings['openai_api_key']); ?>" 
                                           class="modern-input api-key-input" placeholder="sk-...">
                                    <a href="https://platform.openai.com/api-keys" target="_blank" class="btn-link">Obtenir une clé</a>
                                </div>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">
                                    <span class="label-text">Langue par Défaut</span>
                                    <span class="label-desc">Langue utilisée par défaut</span>
                                </label>
                                <select name="hotel_chatbot_default_language" class="modern-select">
                                    <option value="fr" <?php selected($current_settings['default_language'], 'fr'); ?>>🇫🇷 Français</option>
                                    <option value="en" <?php selected($current_settings['default_language'], 'en'); ?>>🇺🇸 English</option>
                                    <option value="es" <?php selected($current_settings['default_language'], 'es'); ?>>🇪🇸 Español</option>
                                    <option value="ar" <?php selected($current_settings['default_language'], 'ar'); ?>>🇸🇦 العربية</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section Persistance des Conversations -->
                <div class="admin-content-card">
                    <div class="card-header">
                        <h3>🍪 Persistance des Conversations</h3>
                        <p>Permettez aux clients de reprendre leurs conversations après plusieurs jours</p>
                    </div>
                    <div class="card-content">
                        <div class="cookie-info-panel">
                            <div class="info-icon">ℹ️</div>
                            <div class="info-content">
                                <h4>Comment ça fonctionne ?</h4>
                                <p>Lorsque cette fonctionnalité est activée, le chatbot sauvegarde automatiquement les conversations des clients dans des cookies sécurisés. Ainsi, si un client revient sur votre site après quelques jours, il peut reprendre sa conversation là où il l'avait laissée.</p>
                                <ul>
                                    <li><strong>Sécurisé :</strong> Les données sont stockées localement sur l'appareil du client</li>
                                    <li><strong>Respectueux :</strong> Aucune donnée personnelle n'est transmise à des tiers</li>
                                    <li><strong>Pratique :</strong> Améliore l'expérience client et réduit les répétitions</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="settings-grid">
                            <div class="setting-item toggle-item">
                                <label class="setting-label">
                                    <span class="label-text">Activer les Cookies</span>
                                    <span class="label-desc">Permet aux clients de reprendre leurs conversations</span>
                                </label>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="hotel_chatbot_enable_cookies" value="1" 
                                           <?php checked($current_settings['enable_cookies'], '1'); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">
                                    <span class="label-text">Durée de Conservation</span>
                                    <span class="label-desc">Combien de temps conserver les conversations (recommandé: 30 jours)</span>
                                </label>
                                <select name="hotel_chatbot_cookie_expiration_days" class="modern-select">
                                    <option value="7" <?php selected($current_settings['cookie_expiration_days'], '7'); ?>>7 jours</option>
                                    <option value="14" <?php selected($current_settings['cookie_expiration_days'], '14'); ?>>14 jours</option>
                                    <option value="30" <?php selected($current_settings['cookie_expiration_days'], '30'); ?>>30 jours (recommandé)</option>
                                    <option value="60" <?php selected($current_settings['cookie_expiration_days'], '60'); ?>>60 jours</option>
                                    <option value="90" <?php selected($current_settings['cookie_expiration_days'], '90'); ?>>90 jours</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section Comportement -->
                <div class="admin-content-card">
                    <div class="card-header">
                        <h3>⚡ Comportement & Performance</h3>
                        <p>Configurez le comportement du chatbot</p>
                    </div>
                    <div class="card-content">
                        <div class="settings-grid">
                            <div class="setting-item toggle-item">
                                <label class="setting-label">
                                    <span class="label-text">Support Multilingue</span>
                                    <span class="label-desc">Détection automatique de la langue</span>
                                </label>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="hotel_chatbot_enable_multilingual" value="1" 
                                           <?php checked($current_settings['enable_multilingual'], '1'); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item toggle-item">
                                <label class="setting-label">
                                    <span class="label-text">Sons Activés</span>
                                    <span class="label-desc">Sons de notification pour nouveaux messages</span>
                                </label>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="hotel_chatbot_enable_sound" value="1" 
                                           <?php checked($current_settings['enable_sound'], '1'); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">
                                    <span class="label-text">Délai de Frappe</span>
                                    <span class="label-desc">Délai avant affichage des réponses (ms)</span>
                                </label>
                                <input type="number" name="hotel_chatbot_typing_delay" 
                                       value="<?php echo esc_attr($current_settings['typing_delay']); ?>" 
                                       class="modern-input" min="500" max="5000" step="100">
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">
                                    <span class="label-text">Messages Maximum</span>
                                    <span class="label-desc">Nombre max de messages par conversation</span>
                                </label>
                                <input type="number" name="hotel_chatbot_max_messages" 
                                       value="<?php echo esc_attr($current_settings['max_messages']); ?>" 
                                       class="modern-input" min="10" max="1000" step="10">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions de sauvegarde -->
            <div class="settings-actions">
                <button type="submit" name="submit" class="btn btn-primary btn-large">
                    💾 Sauvegarder les Paramètres
                </button>
                <button type="button" class="btn btn-secondary" onclick="location.reload()">
                    🔄 Annuler
                </button>
            </div>
        </form>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Charger le script admin des couleurs
        if (typeof adminColorsScript !== 'undefined') {
            adminColorsScript.init();
        }
        
        // Debug du formulaire
        console.log('Hotel Chatbot Admin - Form debug loaded');
        
        // Vérifier le formulaire
        const form = $('.hotel-chatbot-settings-form');
        console.log('Form found:', form.length > 0);
        
        // Vérifier le bouton submit
        const submitBtn = $('button[name="submit"]');
        console.log('Submit button found:', submitBtn.length > 0);
        
        // Intercepter la soumission du formulaire (debug silencieux)
        form.on('submit', function(e) {
            console.log('Form submission intercepted!');
            console.log('Form data:', $(this).serialize());
            
            // Vérifier les champs de couleur (debug silencieux)
            $('.color-code-field').each(function() {
                const name = $(this).attr('name');
                const value = $(this).val();
                console.log('Color field:', name, '=', value);
            });
            
            $('.color-picker').each(function() {
                const name = $(this).attr('name');
                const value = $(this).val();
                console.log('Color picker:', name, '=', value);
            });
            
            // Laisser le formulaire se soumettre normalement
            console.log('Allowing form submission...');
        });
        
        // Test du bouton submit
        submitBtn.on('click', function(e) {
            console.log('Submit button clicked!');
            console.log('Button type:', $(this).attr('type'));
            console.log('Button name:', $(this).attr('name'));
        });
    });
    </script>
    
    <style>
    .settings-container {
        display: grid;
        gap: 2rem;
        margin-bottom: 2rem;
    }
    
    .settings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        padding: 1.5rem;
    }
    
    .setting-item {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .setting-label {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .label-text {
        font-weight: 600;
        color: var(--text-dark);
        font-size: 0.95rem;
    }
    
    .label-desc {
        font-size: 0.85rem;
        color: var(--text-muted);
        line-height: 1.4;
    }
    
    .modern-input, .modern-select, .modern-textarea {
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        background: white;
    }
    
    .modern-input:focus, .modern-select:focus, .modern-textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .modern-textarea {
        min-height: 80px;
        resize: vertical;
        font-family: inherit;
    }
    
    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .color-picker {
        width: 60px;
        height: 40px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
    }
    
    .color-preview {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: 2px solid #e5e7eb;
    }
    
    .toggle-switch {
        position: relative;
        width: 60px;
        height: 30px;
        cursor: pointer;
    }
    
    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .toggle-slider {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        border-radius: 30px;
        transition: 0.3s;
    }
    
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 22px;
        width: 22px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        border-radius: 50%;
        transition: 0.3s;
    }
    
    .toggle-switch input:checked + .toggle-slider {
        background-color: var(--primary-color);
    }
    
    .toggle-switch input:checked + .toggle-slider:before {
        transform: translateX(30px);
    }
    
    .language-tabs .tab-nav {
        display: flex;
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 1rem;
    }
    
    .tab-btn {
        padding: 0.75rem 1.5rem;
        border: none;
        background: none;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
        font-weight: 500;
    }
    
    .tab-btn.active {
        border-bottom-color: var(--primary-color);
        color: var(--primary-color);
    }
    
    .tab-pane {
        display: none;
    }
    
    .tab-pane.active {
        display: block;
    }
    
    .settings-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        padding: 2rem;
        border-top: 1px solid #e5e7eb;
        background: #f8fafc;
    }
    
    .btn-large {
        padding: 1rem 2rem;
        font-size: 1rem;
        font-weight: 600;
    }
    
    .api-key-wrapper {
        display: flex;
        gap: 1rem;
        align-items: center;
    }
    
    .api-key-input {
        flex: 1;
    }
    
    .btn-link {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border: 2px solid var(--primary-color);
        border-radius: 6px;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .btn-link:hover {
        background: var(--primary-color);
        color: white;
    }
    </style>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des onglets
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                
                // Désactiver tous les onglets
                tabBtns.forEach(b => b.classList.remove('active'));
                tabPanes.forEach(p => p.classList.remove('active'));
                
                // Activer l'onglet sélectionné
                this.classList.add('active');
                document.getElementById('tab-' + tabId).classList.add('active');
            });
        });
    });
    </script>
    <?php
}

// Fonction de correction des noms de clients pour l'admin
function hotel_chatbot_fix_client_names_admin() {
    global $wpdb;
    
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    
    // Sécurité : vérifier les permissions
    if (!current_user_can('manage_options')) {
        wp_die('Vous n\'avez pas les permissions nécessaires.');
    }
    
    ?>
    <div class="wrap hotel-chatbot-admin">
        <div class="admin-header-enhanced">
            <h1>🔧 Correction des Données Client</h1>
            <p class="admin-subtitle">Correction automatique des noms de clients problématiques</p>
        </div>
        
        <div class="admin-content-card">
    <?php
    
    // Identifier les conversations avec problème
    $problematic_conversations = $wpdb->get_results(
        "SELECT id, client_name, client_email 
         FROM $conversations_table 
         WHERE client_name LIKE '%@%' 
         OR client_name = client_email"
    );
    
    if (empty($problematic_conversations)) {
        echo '<div class="admin-alert success">';
        echo '<div class="alert-icon">✅</div>';
        echo '<div class="alert-content">';
        echo '<strong>Aucune donnée problématique trouvée.</strong><br>';
        echo 'Toutes les conversations ont des noms de clients corrects.';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="admin-alert info">';
        echo '<div class="alert-icon">📊</div>';
        echo '<div class="alert-content">';
        echo '<strong>Trouvé ' . count($problematic_conversations) . ' conversation(s) avec des problèmes de nom.</strong><br>';
        echo 'Correction en cours...';
        echo '</div>';
        echo '</div>';
        
        $fixed_count = 0;
        
        echo '<div class="correction-log">';
        
        foreach ($problematic_conversations as $conv) {
            echo '<div class="correction-item">';
            echo '<strong>Conversation ID: ' . $conv->id . '</strong><br>';
            echo 'Nom actuel: <code>' . esc_html($conv->client_name) . '</code><br>';
            echo 'Email actuel: <code>' . esc_html($conv->client_email) . '</code><br>';
            
            $new_name = 'Client';
            $new_email = $conv->client_email;
            
            // Si le nom contient un email et l'email est vide, déplacer l'email
            if (strpos($conv->client_name, '@') !== false && empty($conv->client_email)) {
                $new_email = $conv->client_name;
                $new_name = 'Client';
                echo '<span class="action-info">Action: Déplacer l\'email du nom vers le champ email</span><br>';
            }
            // Si le nom est identique à l'email, utiliser un nom par défaut
            else if ($conv->client_name === $conv->client_email) {
                $new_name = 'Client';
                echo '<span class="action-info">Action: Remplacer le nom par \'Client\'</span><br>';
            }
            // Si le nom contient un @ mais on a déjà un email différent
            else if (strpos($conv->client_name, '@') !== false && !empty($conv->client_email)) {
                $new_name = 'Client';
                echo '<span class="action-info">Action: Utiliser nom par défaut car nom contient @</span><br>';
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
                echo '<span class="correction-success">✅ <strong>Corrigé:</strong> Nom: <code>' . esc_html($new_name) . '</code>, Email: <code>' . esc_html($new_email) . '</code></span><br>';
                $fixed_count++;
            } else {
                echo '<span class="correction-error">❌ <strong>Erreur lors de la correction</strong></span><br>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
        
        echo '<div class="admin-alert success">';
        echo '<div class="alert-icon">🎉</div>';
        echo '<div class="alert-content">';
        echo '<strong>Résumé: ' . $fixed_count . ' conversation(s) corrigée(s) sur ' . count($problematic_conversations) . '</strong>';
        echo '</div>';
        echo '</div>';
    }
    
    ?>
            <div class="card-actions">
                <a href="<?php echo admin_url('admin.php?page=hotel-chatbot'); ?>" class="btn btn-primary">
                    ← Retour à la liste des conversations
                </a>
            </div>
        </div>
    </div>
    
    <style>
    .correction-log {
        margin: 20px 0;
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        background: #f9f9f9;
    }
    
    .correction-item {
        margin: 15px 0;
        padding: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        background: white;
    }
    
    .action-info {
        color: #0073aa;
        font-style: italic;
    }
    
    .correction-success {
        color: #46b450;
        font-weight: bold;
    }
    
    .correction-error {
        color: #dc3232;
        font-weight: bold;
    }
    
    .admin-alert {
        display: flex;
        align-items: center;
        padding: 15px;
        margin: 15px 0;
        border-radius: 5px;
        border-left: 4px solid;
    }
    
    .admin-alert.success {
        background: #d4edda;
        border-color: #28a745;
        color: #155724;
    }
    
    .admin-alert.info {
        background: #d1ecf1;
        border-color: #17a2b8;
        color: #0c5460;
    }
    
    .admin-alert.warning {
        background: #fff3cd;
        border-color: #ffc107;
        color: #856404;
    }
    
    .alert-icon {
        font-size: 20px;
        margin-right: 10px;
    }
    
    .alert-content {
        flex: 1;
    }
    </style>
    <?php
}

// Handler AJAX pour le rafraîchissement des conversations
add_action('wp_ajax_hotel_chatbot_refresh_conversations', 'hotel_chatbot_refresh_conversations_ajax');

// Handler AJAX pour récupérer une conversation spécifique
add_action('wp_ajax_hotel_chatbot_get_conversation', 'hotel_chatbot_get_conversation_ajax');

function hotel_chatbot_refresh_conversations_ajax() {
    // Vérifier le nonce pour la sécurité
    if (!wp_verify_nonce($_POST['nonce'], 'hotel_chatbot_admin_nonce')) {
        wp_die('Erreur de sécurité');
    }
    
    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes');
        return;
    }
    
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
    
    try {
        // Récupérer les conversations avec les informations complètes
        $conversations = $wpdb->get_results(
            "SELECT c.*, 
                    COUNT(m.id) as message_count,
                    MAX(m.created_at) as last_message_time,
                    (
                        SELECT m2.message 
                        FROM $messages_table m2 
                        WHERE m2.conversation_id = c.id 
                        ORDER BY m2.created_at DESC 
                        LIMIT 1
                    ) as last_message
             FROM $conversations_table c 
             LEFT JOIN $messages_table m ON c.id = m.conversation_id 
             GROUP BY c.id 
             ORDER BY c.updated_at DESC"
        );
        
        // Préparer les données pour le JavaScript
        $formatted_conversations = array();
        
        foreach ($conversations as $conv) {
            // Correction automatique des noms si nécessaire
            $client_name = $conv->client_name;
            $client_email = $conv->client_email;
            
            // Si le nom contient un email et que le champ email est vide ou identique
            if (preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $client_name) && 
                (empty($client_email) || $client_email === $client_name)) {
                $client_email = $client_name;
                $client_name = 'Client';
            }
            
            $formatted_conversations[] = array(
                'id' => intval($conv->id),
                'client_name' => $client_name ?: 'Anonyme',
                'client_email' => $client_email ?: 'Non renseigné',
                'language' => $conv->language ?: 'fr',
                'status' => $conv->status ?: 'active',
                'message_count' => intval($conv->message_count ?: 0),
                'last_message' => $conv->last_message ?: 'Aucun message',
                'last_message_time' => $conv->last_message_time,
                'created_at' => $conv->created_at,
                'updated_at' => $conv->updated_at
            );
        }
        
        // Envoyer la réponse JSON
        wp_send_json_success($formatted_conversations);
        
    } catch (Exception $e) {
        error_log('Erreur lors du rafraîchissement des conversations: ' . $e->getMessage());
        wp_send_json_error('Erreur lors du chargement des conversations');
    }
}

function hotel_chatbot_get_conversation_ajax() {
    // Vérifier le nonce pour la sécurité
    if (!wp_verify_nonce($_POST['nonce'], 'hotel_chatbot_admin_nonce')) {
        wp_die('Erreur de sécurité');
    }
    
    // Vérifier les permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissions insuffisantes');
        return;
    }
    
    // Vérifier que l'ID de conversation est fourni
    $conversation_id = intval($_POST['conversation_id']);
    if (!$conversation_id) {
        wp_send_json_error('ID de conversation manquant');
        return;
    }
    
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
    
    try {
        // Récupérer la conversation
        $conversation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $conversations_table WHERE id = %d",
                $conversation_id
            )
        );
        
        if (!$conversation) {
            wp_send_json_error('Conversation non trouvée');
            return;
        }
        
        // Récupérer les messages de la conversation
        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $messages_table 
                 WHERE conversation_id = %d 
                 ORDER BY created_at ASC",
                $conversation_id
            )
        );
        
        // Correction automatique des noms si nécessaire
        $client_name = $conversation->client_name;
        $client_email = $conversation->client_email;
        
        // Si le nom contient un email et que le champ email est vide ou identique
        if (preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $client_name) && 
            (empty($client_email) || $client_email === $client_name)) {
            $client_email = $client_name;
            $client_name = 'Client';
        }
        
        // Préparer les données de la conversation
        $conversation_data = array(
            'id' => intval($conversation->id),
            'client_name' => $client_name ?: 'Anonyme',
            'client_email' => $client_email ?: 'Non renseigné',
            'language' => $conversation->language ?: 'fr',
            'status' => $conversation->status ?: 'active',
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at
        );
        
        // Préparer les messages
        $messages_data = array();
        foreach ($messages as $message) {
            $messages_data[] = array(
                'id' => intval($message->id),
                'message' => $message->message,
                'sender' => $message->sender_type ?: 'client',
                'created_at' => $message->created_at
            );
        }
        
        // Envoyer la réponse
        wp_send_json_success(array(
            'conversation' => $conversation_data,
            'messages' => $messages_data
        ));
        
    } catch (Exception $e) {
        error_log('Erreur lors du chargement de la conversation: ' . $e->getMessage());
        wp_send_json_error('Erreur lors du chargement de la conversation');
    }
}

// Inclure les nouvelles pages admin
require_once HOTEL_CHATBOT_PATH . 'includes/admin-analytics.php';
require_once HOTEL_CHATBOT_PATH . 'includes/admin-responses.php';
require_once HOTEL_CHATBOT_PATH . 'includes/admin-logs.php';
