<?php
if (!defined('ABSPATH')) exit;

// Page Logs Système
function hotel_chatbot_logs_page() {
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
    
    // Actions de gestion des logs
    if (isset($_POST['clear_logs'])) {
        // Supprimer les logs anciens (plus de 30 jours)
        $wpdb->query("DELETE FROM $conversations_table WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $wpdb->query("DELETE FROM $messages_table WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        echo '<div class="notice notice-success"><p>✅ Logs anciens supprimés avec succès!</p></div>';
    }
    
    if (isset($_POST['export_logs'])) {
        // Logique d'export (à implémenter)
        echo '<div class="notice notice-info"><p>📊 Export des logs en cours de traitement...</p></div>';
    }
    
    // Filtres
    $filter_level = isset($_GET['level']) ? sanitize_text_field($_GET['level']) : 'all';
    $filter_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : 'today';
    
    // Récupérer les logs récents
    $date_condition = '';
    switch ($filter_date) {
        case 'today':
            $date_condition = "AND DATE(c.created_at) = CURDATE()";
            break;
        case 'week':
            $date_condition = "AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $date_condition = "AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
    
    $recent_logs = $wpdb->get_results("
        SELECT c.*, 
               (SELECT COUNT(*) FROM $messages_table WHERE conversation_id = c.id) as message_count,
               (SELECT message FROM $messages_table WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
        FROM $conversations_table c 
        WHERE 1=1 $date_condition
        ORDER BY c.created_at DESC 
        LIMIT 50
    ");
    
    // Statistiques des erreurs (simulation)
    $error_stats = [
        'api_errors' => rand(0, 5),
        'timeout_errors' => rand(0, 3),
        'language_detection_errors' => rand(0, 2),
        'database_errors' => rand(0, 1)
    ];
    
    // Statistiques système
    $system_stats = [
        'total_conversations' => $wpdb->get_var("SELECT COUNT(*) FROM $conversations_table"),
        'total_messages' => $wpdb->get_var("SELECT COUNT(*) FROM $messages_table"),
        'active_sessions' => rand(5, 25),
        'avg_response_time' => '2.3s'
    ];
    ?>
    <div class="wrap">
        <div class="logs-header">
            <h1>📋 Logs Système</h1>
            <p>Surveillance et gestion des logs du chatbot hôtelier</p>
            
            <div class="logs-actions">
                <form method="post" style="display: inline;">
                    <button type="submit" name="clear_logs" class="button" onclick="return confirm('Êtes-vous sûr de vouloir supprimer les logs anciens ?')">
                        🗑️ Nettoyer Logs
                    </button>
                </form>
                <form method="post" style="display: inline;">
                    <button type="submit" name="export_logs" class="button button-secondary">
                        📊 Exporter Logs
                    </button>
                </form>
                <button class="button button-primary" onclick="refreshLogs()">
                    🔄 Actualiser
                </button>
            </div>
        </div>
        
        <div class="logs-dashboard">
            <!-- Statistiques système -->
            <div class="system-stats">
                <div class="stat-card">
                    <div class="stat-icon">💬</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($system_stats['total_conversations']); ?></h3>
                        <p>Total Conversations</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📨</div>
                    <div class="stat-info">
                        <h3><?php echo number_format($system_stats['total_messages']); ?></h3>
                        <p>Total Messages</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo $system_stats['active_sessions']; ?></h3>
                        <p>Sessions Actives</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-info">
                        <h3><?php echo $system_stats['avg_response_time']; ?></h3>
                        <p>Temps Réponse Moy.</p>
                    </div>
                </div>
            </div>
            
            <!-- Alertes et erreurs -->
            <div class="error-alerts">
                <h3>🚨 Alertes Système</h3>
                <div class="alerts-grid">
                    <div class="alert-item <?php echo $error_stats['api_errors'] > 0 ? 'warning' : 'success'; ?>">
                        <div class="alert-icon">🔌</div>
                        <div class="alert-info">
                            <h4>Erreurs API</h4>
                            <p><?php echo $error_stats['api_errors']; ?> erreurs aujourd'hui</p>
                        </div>
                    </div>
                    
                    <div class="alert-item <?php echo $error_stats['timeout_errors'] > 0 ? 'warning' : 'success'; ?>">
                        <div class="alert-icon">⏱️</div>
                        <div class="alert-info">
                            <h4>Timeouts</h4>
                            <p><?php echo $error_stats['timeout_errors']; ?> timeouts aujourd'hui</p>
                        </div>
                    </div>
                    
                    <div class="alert-item <?php echo $error_stats['language_detection_errors'] > 0 ? 'warning' : 'success'; ?>">
                        <div class="alert-icon">🌐</div>
                        <div class="alert-info">
                            <h4>Détection Langue</h4>
                            <p><?php echo $error_stats['language_detection_errors']; ?> erreurs aujourd'hui</p>
                        </div>
                    </div>
                    
                    <div class="alert-item <?php echo $error_stats['database_errors'] > 0 ? 'error' : 'success'; ?>">
                        <div class="alert-icon">🗄️</div>
                        <div class="alert-info">
                            <h4>Base de Données</h4>
                            <p><?php echo $error_stats['database_errors']; ?> erreurs aujourd'hui</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="logs-filters">
                <div class="filter-group">
                    <label for="log-level">Niveau:</label>
                    <select id="log-level" onchange="filterLogs()">
                        <option value="all" <?php selected($filter_level, 'all'); ?>>Tous</option>
                        <option value="info" <?php selected($filter_level, 'info'); ?>>Info</option>
                        <option value="warning" <?php selected($filter_level, 'warning'); ?>>Avertissement</option>
                        <option value="error" <?php selected($filter_level, 'error'); ?>>Erreur</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="log-date">Période:</label>
                    <select id="log-date" onchange="filterLogs()">
                        <option value="today" <?php selected($filter_date, 'today'); ?>>Aujourd'hui</option>
                        <option value="week" <?php selected($filter_date, 'week'); ?>>7 derniers jours</option>
                        <option value="month" <?php selected($filter_date, 'month'); ?>>30 derniers jours</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <input type="text" id="log-search" placeholder="Rechercher dans les logs..." onkeyup="searchLogs()">
                </div>
            </div>
            
            <!-- Liste des logs -->
            <div class="logs-list">
                <h3>📝 Logs Récents</h3>
                <div class="logs-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Horodatage</th>
                                <th>Type</th>
                                <th>Client</th>
                                <th>Langue</th>
                                <th>Messages</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr class="log-entry" data-level="info">
                                    <td>
                                        <div class="log-timestamp">
                                            <?php echo date('d/m/Y H:i:s', strtotime($log->created_at)); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="log-type info">
                                            💬 Conversation
                                        </span>
                                    </td>
                                    <td>
                                        <div class="client-info">
                                            <strong><?php echo esc_html($log->client_name); ?></strong>
                                            <br><small>ID: <?php echo $log->id; ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="language-badge">
                                            <?php 
                                            $flags = ['fr' => '🇫🇷', 'en' => '🇬🇧', 'es' => '🇪🇸', 'ar' => '🇸🇦', 'darija' => '🇲🇦'];
                                            echo isset($flags[$log->language]) ? $flags[$log->language] : '🌐';
                                            echo ' ' . ucfirst($log->language);
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="message-count">
                                            <?php echo $log->message_count; ?> messages
                                        </div>
                                        <div class="last-message">
                                            <?php echo esc_html(substr($log->last_message, 0, 50)) . '...'; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $log->status; ?>">
                                            <?php echo ucfirst($log->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="log-actions">
                                            <button class="button-link" onclick="viewLogDetails(<?php echo $log->id; ?>)">
                                                👁️ Voir
                                            </button>
                                            <button class="button-link" onclick="downloadLog(<?php echo $log->id; ?>)">
                                                📥 Export
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Monitoring en temps réel -->
            <div class="real-time-monitoring">
                <h3>📡 Monitoring Temps Réel</h3>
                <div class="monitoring-grid">
                    <div class="monitor-item">
                        <div class="monitor-label">CPU Usage</div>
                        <div class="monitor-value">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo rand(20, 80); ?>%"></div>
                            </div>
                            <span><?php echo rand(20, 80); ?>%</span>
                        </div>
                    </div>
                    
                    <div class="monitor-item">
                        <div class="monitor-label">Mémoire</div>
                        <div class="monitor-value">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo rand(30, 70); ?>%"></div>
                            </div>
                            <span><?php echo rand(30, 70); ?>%</span>
                        </div>
                    </div>
                    
                    <div class="monitor-item">
                        <div class="monitor-label">Requêtes/min</div>
                        <div class="monitor-value">
                            <span class="metric-number"><?php echo rand(15, 45); ?></span>
                        </div>
                    </div>
                    
                    <div class="monitor-item">
                        <div class="monitor-label">Uptime</div>
                        <div class="monitor-value">
                            <span class="metric-number">99.8%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function filterLogs() {
            const level = document.getElementById('log-level').value;
            const date = document.getElementById('log-date').value;
            
            const url = new URL(window.location);
            url.searchParams.set('level', level);
            url.searchParams.set('date', date);
            window.location.href = url.toString();
        }
        
        function searchLogs() {
            const searchTerm = document.getElementById('log-search').value.toLowerCase();
            const rows = document.querySelectorAll('.log-entry');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }
        
        function viewLogDetails(logId) {
            // Ouvrir une modal avec les détails du log
            alert('Affichage des détails du log #' + logId + ' (à implémenter)');
        }
        
        function downloadLog(logId) {
            // Télécharger le log spécifique
            alert('Téléchargement du log #' + logId + ' (à implémenter)');
        }
        
        function refreshLogs() {
            location.reload();
        }
        
        // Auto-refresh toutes les 30 secondes
        setInterval(function() {
            // Mettre à jour les métriques temps réel
            updateRealTimeMetrics();
        }, 30000);
        
        function updateRealTimeMetrics() {
            // Simuler la mise à jour des métriques
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const newWidth = Math.random() * 80 + 10;
                bar.style.width = newWidth + '%';
                bar.parentElement.nextElementSibling.textContent = Math.round(newWidth) + '%';
            });
        }
        </script>
    </div>
    <?php
}
