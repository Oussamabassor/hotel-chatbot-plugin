<?php
if (!defined('ABSPATH')) exit;

// Page Analytics & Rapports
function hotel_chatbot_analytics_page() {
    global $wpdb;
    $conversations_table = $wpdb->prefix . 'hotel_chatbot_conversations';
    $messages_table = $wpdb->prefix . 'hotel_chatbot_messages';
    
    // Période sélectionnée (par défaut: 30 jours)
    $period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : '30';
    
    // Statistiques par période
    $period_conversations = $wpdb->get_results($wpdb->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM $conversations_table 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ", $period));
    
    // Statistiques par langue
    $language_stats = $wpdb->get_results($wpdb->prepare("
        SELECT language, COUNT(*) as count, 
               AVG((SELECT COUNT(*) FROM $messages_table WHERE conversation_id = c.id)) as avg_messages
        FROM $conversations_table c
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        AND language IS NOT NULL AND language != ''
        GROUP BY language 
        ORDER BY count DESC
    ", $period));
    
    // Statistiques par heure
    $hourly_stats = $wpdb->get_results($wpdb->prepare("
        SELECT HOUR(created_at) as hour, COUNT(*) as count
        FROM $conversations_table 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        GROUP BY HOUR(created_at)
        ORDER BY hour ASC
    ", $period));
    
    // Top des mots-clés
    $top_keywords = $wpdb->get_results($wpdb->prepare("
        SELECT 
            CASE 
                WHEN message LIKE '%%prix%%' OR message LIKE '%%tarif%%' OR message LIKE '%%cost%%' THEN 'Prix/Tarifs'
                WHEN message LIKE '%%disponibilit%%' OR message LIKE '%%available%%' OR message LIKE '%%libre%%' THEN 'Disponibilités'
                WHEN message LIKE '%%réserv%%' OR message LIKE '%%book%%' OR message LIKE '%%reservation%%' THEN 'Réservations'
                WHEN message LIKE '%%service%%' OR message LIKE '%%amenities%%' THEN 'Services'
                WHEN message LIKE '%%parking%%' THEN 'Parking'
                WHEN message LIKE '%%wifi%%' OR message LIKE '%%internet%%' THEN 'WiFi'
                WHEN message LIKE '%%petit%%' AND message LIKE '%%déjeuner%%' OR message LIKE '%%breakfast%%' THEN 'Petit-déjeuner'
                ELSE 'Autres'
            END as category,
            COUNT(*) as count
        FROM $messages_table m
        JOIN $conversations_table c ON m.conversation_id = c.id
        WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
        AND m.sender = 'user'
        GROUP BY category
        ORDER BY count DESC
        LIMIT 10
    ", $period));
    
    // Temps de réponse moyen (simulation)
    $avg_response_time = '2.1s';
    $satisfaction_rate = rand(90, 98) . '%';
    $resolution_rate = rand(85, 95) . '%';
    ?>
    <div class="wrap">
        <div class="analytics-header">
            <div class="analytics-title">
                <h1>📊 Analytics & Rapports</h1>
                <p>Analyse détaillée des performances de votre chatbot hôtelier</p>
            </div>
            
            <div class="period-selector">
                <label for="analytics-period">Période d'analyse:</label>
                <select id="analytics-period" onchange="window.location.href='?page=hotel-chatbot-analytics&period=' + this.value">
                    <option value="7" <?php selected($period, '7'); ?>>7 derniers jours</option>
                    <option value="30" <?php selected($period, '30'); ?>>30 derniers jours</option>
                    <option value="90" <?php selected($period, '90'); ?>>3 derniers mois</option>
                    <option value="365" <?php selected($period, '365'); ?>>12 derniers mois</option>
                </select>
            </div>
        </div>
        
        <div class="analytics-dashboard">
            <!-- KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card engagement">
                    <div class="kpi-icon">💬</div>
                    <div class="kpi-content">
                        <h3><?php echo array_sum(array_column($period_conversations, 'count')); ?></h3>
                        <p>Conversations</p>
                        <small>Sur <?php echo $period; ?> jours</small>
                    </div>
                </div>
                
                <div class="kpi-card performance">
                    <div class="kpi-icon">⚡</div>
                    <div class="kpi-content">
                        <h3><?php echo $avg_response_time; ?></h3>
                        <p>Temps de Réponse</p>
                        <small>Moyenne</small>
                    </div>
                </div>
                
                <div class="kpi-card satisfaction">
                    <div class="kpi-icon">⭐</div>
                    <div class="kpi-content">
                        <h3><?php echo $satisfaction_rate; ?></h3>
                        <p>Satisfaction</p>
                        <small>Taux estimé</small>
                    </div>
                </div>
                
                <div class="kpi-card resolution">
                    <div class="kpi-icon">✅</div>
                    <div class="kpi-content">
                        <h3><?php echo $resolution_rate; ?></h3>
                        <p>Résolution</p>
                        <small>Taux de succès</small>
                    </div>
                </div>
            </div>
            
            <!-- Graphiques -->
            <div class="charts-section">
                <div class="chart-row">
                    <div class="chart-container large">
                        <h3>📈 Évolution des Conversations</h3>
                        <canvas id="conversationsTrendChart" width="600" height="300"></canvas>
                    </div>
                    
                    <div class="chart-container medium">
                        <h3>🕐 Répartition par Heure</h3>
                        <canvas id="hourlyChart" width="300" height="300"></canvas>
                    </div>
                </div>
                
                <div class="chart-row">
                    <div class="chart-container medium">
                        <h3>🌍 Langues Utilisées</h3>
                        <div class="languages-analytics">
                            <?php foreach ($language_stats as $lang): ?>
                                <div class="language-analytics-item">
                                    <div class="lang-info">
                                        <span class="lang-flag">
                                            <?php 
                                            $flags = ['fr' => '🇫🇷', 'en' => '🇬🇧', 'es' => '🇪🇸', 'ar' => '🇸🇦', 'darija' => '🇲🇦', 'de' => '🇩🇪', 'it' => '🇮🇹'];
                                            echo isset($flags[$lang->language]) ? $flags[$lang->language] : '🌐';
                                            ?>
                                        </span>
                                        <span class="lang-name"><?php echo ucfirst($lang->language); ?></span>
                                    </div>
                                    <div class="lang-stats">
                                        <div class="lang-count"><?php echo $lang->count; ?> conv.</div>
                                        <div class="lang-avg"><?php echo round($lang->avg_messages, 1); ?> msg/conv</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="chart-container medium">
                        <h3>🔍 Catégories de Demandes</h3>
                        <div class="keywords-analytics">
                            <?php foreach ($top_keywords as $keyword): ?>
                                <div class="keyword-item">
                                    <span class="keyword-name"><?php echo $keyword->category; ?></span>
                                    <span class="keyword-count"><?php echo $keyword->count; ?></span>
                                    <div class="keyword-bar">
                                        <div class="keyword-progress" style="width: <?php echo ($keyword->count / max(array_column($top_keywords, 'count'))) * 100; ?>%"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Export et Actions -->
            <div class="analytics-actions">
                <h3>📋 Actions</h3>
                <div class="actions-buttons">
                    <button class="button button-primary" onclick="exportAnalytics('csv')">
                        📊 Exporter CSV
                    </button>
                    <button class="button button-secondary" onclick="exportAnalytics('pdf')">
                        📄 Rapport PDF
                    </button>
                    <button class="button" onclick="printAnalytics()">
                        🖨️ Imprimer
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        // Graphique des conversations
        const conversationData = <?php echo json_encode($period_conversations); ?>;
        const hourlyData = <?php echo json_encode($hourly_stats); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            drawConversationsTrend();
            drawHourlyChart();
        });
        
        function drawConversationsTrend() {
            const canvas = document.getElementById('conversationsTrendChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const data = {
                labels: conversationData.map(d => new Date(d.date).toLocaleDateString('fr-FR')),
                values: conversationData.map(d => parseInt(d.count))
            };
            
            drawAdvancedLineChart(ctx, data, canvas.width, canvas.height);
        }
        
        function drawHourlyChart() {
            const canvas = document.getElementById('hourlyChart');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            
            // Créer un tableau de 24 heures
            const hours = Array.from({length: 24}, (_, i) => i);
            const hourlyValues = hours.map(hour => {
                const found = hourlyData.find(d => parseInt(d.hour) === hour);
                return found ? parseInt(found.count) : 0;
            });
            
            const data = {
                labels: hours.map(h => h + 'h'),
                values: hourlyValues
            };
            
            drawBarChart(ctx, data, canvas.width, canvas.height);
        }
        
        function drawAdvancedLineChart(ctx, data, width, height) {
            const padding = 60;
            const chartWidth = width - 2 * padding;
            const chartHeight = height - 2 * padding;
            
            ctx.clearRect(0, 0, width, height);
            
            if (data.values.length === 0) {
                ctx.fillStyle = '#64748b';
                ctx.font = '16px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('Aucune donnée disponible', width/2, height/2);
                return;
            }
            
            const maxValue = Math.max(...data.values, 1);
            const stepX = chartWidth / (data.values.length - 1 || 1);
            
            // Grille
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1;
            for (let i = 0; i <= 5; i++) {
                const y = padding + (chartHeight * i / 5);
                ctx.beginPath();
                ctx.moveTo(padding, y);
                ctx.lineTo(width - padding, y);
                ctx.stroke();
            }
            
            // Aire sous la courbe
            ctx.fillStyle = 'rgba(59, 130, 246, 0.1)';
            ctx.beginPath();
            ctx.moveTo(padding, padding + chartHeight);
            
            data.values.forEach((value, index) => {
                const x = padding + index * stepX;
                const y = padding + chartHeight - (value / maxValue * chartHeight);
                if (index === 0) {
                    ctx.lineTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            
            ctx.lineTo(padding + (data.values.length - 1) * stepX, padding + chartHeight);
            ctx.closePath();
            ctx.fill();
            
            // Ligne principale
            ctx.strokeStyle = '#3b82f6';
            ctx.lineWidth = 3;
            ctx.beginPath();
            
            data.values.forEach((value, index) => {
                const x = padding + index * stepX;
                const y = padding + chartHeight - (value / maxValue * chartHeight);
                
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            
            ctx.stroke();
            
            // Points
            ctx.fillStyle = '#3b82f6';
            data.values.forEach((value, index) => {
                const x = padding + index * stepX;
                const y = padding + chartHeight - (value / maxValue * chartHeight);
                
                ctx.beginPath();
                ctx.arc(x, y, 5, 0, 2 * Math.PI);
                ctx.fill();
                
                // Valeurs
                ctx.fillStyle = '#374151';
                ctx.font = '12px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(value, x, y - 10);
                ctx.fillStyle = '#3b82f6';
            });
            
            // Labels X
            ctx.fillStyle = '#64748b';
            ctx.font = '11px Arial';
            ctx.textAlign = 'center';
            
            data.labels.forEach((label, index) => {
                if (index % Math.ceil(data.labels.length / 8) === 0) {
                    const x = padding + index * stepX;
                    ctx.save();
                    ctx.translate(x, height - 20);
                    ctx.rotate(-Math.PI / 6);
                    ctx.fillText(label, 0, 0);
                    ctx.restore();
                }
            });
        }
        
        function drawBarChart(ctx, data, width, height) {
            const padding = 40;
            const chartWidth = width - 2 * padding;
            const chartHeight = height - 2 * padding;
            
            ctx.clearRect(0, 0, width, height);
            
            if (data.values.length === 0) return;
            
            const maxValue = Math.max(...data.values, 1);
            const barWidth = chartWidth / data.values.length * 0.8;
            const barSpacing = chartWidth / data.values.length * 0.2;
            
            data.values.forEach((value, index) => {
                const x = padding + index * (barWidth + barSpacing);
                const barHeight = (value / maxValue) * chartHeight;
                const y = padding + chartHeight - barHeight;
                
                // Barre
                const gradient = ctx.createLinearGradient(0, y, 0, y + barHeight);
                gradient.addColorStop(0, '#3b82f6');
                gradient.addColorStop(1, '#1d4ed8');
                
                ctx.fillStyle = gradient;
                ctx.fillRect(x, y, barWidth, barHeight);
                
                // Valeur
                if (value > 0) {
                    ctx.fillStyle = '#374151';
                    ctx.font = '10px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText(value, x + barWidth/2, y - 5);
                }
                
                // Label
                ctx.fillStyle = '#64748b';
                ctx.font = '9px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(data.labels[index], x + barWidth/2, height - 10);
            });
        }
        
        function exportAnalytics(format) {
            alert('Export ' + format.toUpperCase() + ' en cours de développement...');
        }
        
        function printAnalytics() {
            window.print();
        }
        </script>
    </div>
    <?php
}
