jQuery(document).ready(function($) {
    'use strict';
    
    // Objet principal pour gérer l'admin du chatbot
    const AdminChatbot = {
        // Variables d'état
        currentConversationId: null,
        conversationsData: [],
        filteredConversations: [],
        sortState: { column: null, direction: 'asc' },
        selectedRows: new Set(),
        
        // Initialisation
        init() {
            this.bindEvents();
            this.initDashboard();
            this.initConversationsPage();
            this.setupAutoRefresh();
        },
        
        // Liaison des événements
        bindEvents() {
            // Gestion des conversations
            $(document).on('click', '.view-conversation', this.handleViewConversation.bind(this));
            $(document).on('click', '.refresh-conversations', this.handleRefreshConversations.bind(this));
            $(document).on('keyup', '#search-conversations', this.handleConversationSearch.bind(this));
            $(document).on('change', '#filter-status, #filter-language', this.handleConversationFilter.bind(this));
            
            // Gestion du tableau Excel
            $(document).on('click', '.sortable', this.handleTableSort.bind(this));
            $(document).on('change', '#select-all-conversations', this.handleSelectAll.bind(this));
            $(document).on('change', '.conversation-checkbox', this.handleRowSelect.bind(this));
            $(document).on('click', '.apply-bulk-action', this.handleBulkAction.bind(this));
            $(document).on('click', '.btn-edit', this.handleEditConversation.bind(this));
            $(document).on('click', '.btn-delete', this.handleDeleteConversation.bind(this));
            
            // Gestion du modal moderne
            $(document).on('click', '.modal-close, #close-modal', this.handleModalClose.bind(this));
            $(document).on('click', '.modal-overlay', this.handleModalOverlayClick.bind(this));
            $(document).on('click', '#send-admin-reply', this.handleSendReply.bind(this));
            $(document).on('keypress', '#admin-reply-text', this.handleReplyKeypress.bind(this));
            
            // Gestion de l'upload d'avatar
            $(document).on('click', '#upload-avatar-btn', this.handleAvatarUpload.bind(this));
            $(document).on('click', '#remove-avatar-btn', this.handleAvatarRemove.bind(this));
        },
        
        // Initialisation de la page des conversations
        initConversationsPage() {
            if ($('.excel-table').length) {
                this.loadConversationsData();
                this.initConversationFilters();
                this.initTableFeatures();
            }
        },
        
        // Initialisation du dashboard
        initDashboard() {
            if ($('.hotel-chatbot-dashboard').length) {
                this.loadDashboardData();
                this.initCharts();
            }
        },
        
        // Configuration de l'auto-refresh
        setupAutoRefresh() {
            // Actualiser les données toutes les 30 secondes
            setInterval(() => {
                if ($('.excel-table').length) {
                    this.refreshConversationsData();
                }
                if ($('.hotel-chatbot-dashboard').length) {
                    this.refreshDashboardData();
                }
            }, 30000);
        },
        
        // === FONCTIONNALITÉS DU TABLEAU EXCEL ===
        
        initTableFeatures() {
            this.initTableSorting();
            this.initTableSelection();
            this.updateTableInfo();
        },
        
        initTableSorting() {
            // Initialiser l'état de tri
            this.sortState = {
                column: null,
                direction: 'asc'
            };
        },
        
        initTableSelection() {
            // Initialiser la sélection
            this.selectedRows = new Set();
            this.updateSelectionCount();
        },
        
        // === GESTION DES ÉVÉNEMENTS ===
        
        handleViewConversation(e) {
            e.preventDefault();
            
            let conversationId = null;
            
            // Méthode 1: Dashboard cards (data-conversation-id)
            const $button = $(e.target).closest('.view-conversation');
            if ($button.length) {
                conversationId = $button.data('conversation-id') || $button.attr('data-conversation-id');
                console.log('ID from dashboard button:', conversationId);
            }
            
            // Méthode 2: Excel table (data-id sur la ligne)
            if (!conversationId) {
                const $row = $(e.target).closest('.conversation-row');
                if ($row.length) {
                    conversationId = $row.attr('data-id') || $row.data('id');
                    console.log('ID from table row:', conversationId);
                }
            }
            
            // Méthode 3: Bouton Excel table (data-id sur le bouton)
            if (!conversationId && $button.length) {
                conversationId = $button.data('id') || $button.attr('data-id');
                console.log('ID from table button:', conversationId);
            }
            
            console.log('Final conversation ID:', conversationId);
            
            if (!conversationId) {
                console.error('Aucun ID de conversation trouvé!');
                this.showNotification('Erreur: ID de conversation manquant', 'error');
                return;
            }
            
            this.currentConversationId = conversationId;
            this.loadConversation(conversationId);
        },
        
        handleEditConversation(e) {
            const conversationId = $(e.target).closest('.btn-edit').data('id');
            // TODO: Implémenter l'édition de conversation
            this.showNotification('Fonctionnalité d\'édition en cours de développement', 'info');
        },
        
        handleDeleteConversation(e) {
            const conversationId = $(e.target).closest('.btn-delete').data('id');
            
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette conversation ?')) {
                return;
            }
            
            $.ajax({
                url: hotelChatbotAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hotel_chatbot_delete_conversation',
                    conversation_id: conversationId,
                    nonce: hotelChatbotAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Conversation supprimée avec succès', 'success');
                        $(`tr[data-id="${conversationId}"]`).fadeOut(300, function() {
                            $(this).remove();
                        });
                        this.updateTableInfo();
                    } else {
                        this.showNotification('Erreur lors de la suppression: ' + (response.data || 'Erreur inconnue'), 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erreur AJAX:', error);
                    this.showNotification('Erreur de connexion', 'error');
                }
            });
        },
        
        handleModalClose() {
            this.closeConversationModal();
        },
        
        handleModalOverlayClick(e) {
            if (e.target === e.currentTarget) {
                this.closeConversationModal();
            }
        },
        
        handleSendReply(e) {
            e.preventDefault();
            const message = $('#admin-reply-text').val().trim();
            
            if (!message) {
                this.showNotification('Veuillez saisir un message', 'error');
                return;
            }
            
            if (!this.currentConversationId) {
                this.showNotification('Erreur: ID de conversation manquant', 'error');
                return;
            }
            
            this.sendAdminMessage(this.currentConversationId, message);
        },
        
        handleReplyKeypress(e) {
            if (e.which === 13 && e.ctrlKey) {
                e.preventDefault();
                $('#send-admin-reply').click();
            }
        },
        
        // === CHARGEMENT DES DONNÉES ===
        
        loadConversationsData() {
            // Simuler le chargement des données (à remplacer par un appel AJAX réel)
            this.conversationsData = this.getConversationsFromDOM();
            this.filteredConversations = [...this.conversationsData];
        },
        
        getConversationsFromDOM() {
            const conversations = [];
            $('.conversation-row').each(function() {
                const $row = $(this);
                conversations.push({
                    id: $row.data('id'),
                    status: $row.data('status'),
                    language: $row.data('language'),
                    clientName: $row.find('.client-name').text(),
                    clientEmail: $row.find('.client-email').text(),
                    lastMessage: $row.find('.last-message-preview').text(),
                    element: $row
                });
            });
            return conversations;
        },
        
        loadConversation(conversationId) {
            this.showLoadingModal();
            
            $.ajax({
                url: hotelChatbotAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hotel_chatbot_get_conversation',
                    conversation_id: conversationId,
                    nonce: hotelChatbotAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.displayConversation(response.data);
                    } else {
                        this.showError('Erreur lors du chargement: ' + (response.data || 'Erreur inconnue'));
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erreur AJAX:', error);
                    this.showError('Erreur de connexion');
                }
            });
        },
        
        // === AFFICHAGE DES MODALS ===
        
        showLoadingModal() {
            const modalHtml = `
                <div id="conversation-modal" class="modal-overlay">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3>💬 Chargement...</h3>
                            <button class="modal-close" id="close-modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                                <p>Chargement de la conversation...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            $('#conversation-modal').fadeIn(300);
        },
        
        displayConversation(data) {
            const conversation = data.conversation || {};
            const messages = data.messages || [];
            let messagesHtml = '';
            
            if (messages.length === 0) {
                messagesHtml = '<div class="no-messages">Aucun message dans cette conversation</div>';
            } else {
                messages.forEach(message => {
                    const messageClass = message.sender === 'client' ? 'client-message' : 
                                       message.sender === 'admin' ? 'admin-message' : 'bot-message';
                    const senderLabel = message.sender === 'client' ? 'Client' : 
                                      message.sender === 'admin' ? 'Admin' : 'Assistant';
                    
                    messagesHtml += `
                        <div class="message ${messageClass}">
                            <div class="message-header">
                                <strong>${senderLabel}</strong>
                                <span class="message-time">${new Date(message.created_at).toLocaleString()}</span>
                            </div>
                            <div class="message-content">${message.message}</div>
                        </div>
                    `;
                });
            }
            
            const modalHtml = `
                <div id="conversation-modal" class="modal-overlay">
                    <div class="modal-container">
                        <div class="modal-header">
                            <h3>💬 Conversation avec ${conversation.client_name || 'Client'}</h3>
                            <button class="modal-close" id="close-modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="conversation-info">
                                <div class="info-item">
                                    <strong>Email:</strong> ${conversation.client_email || 'Non renseigné'}
                                </div>
                                <div class="info-item">
                                    <strong>Langue:</strong> ${conversation.language || 'Non définie'}
                                </div>
                                <div class="info-item">
                                    <strong>Statut:</strong> 
                                    <span class="status-badge status-${conversation.status || 'unknown'}">
                                        ${conversation.status || 'Inconnu'}
                                    </span>
                                </div>
                            </div>
                            <div class="messages-container">
                                ${messagesHtml}
                            </div>
                            <div class="reply-section">
                                <textarea id="admin-reply-text" placeholder="Tapez votre réponse..."></textarea>
                                <div class="reply-actions">
                                    <button id="send-admin-reply" class="btn btn-primary">
                                        📤 Envoyer
                                    </button>
                                    <button class="btn btn-secondary" onclick="document.getElementById('admin-reply-text').value = ''">
                                        🗑️ Effacer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#conversation-modal').remove();
            $('body').append(modalHtml);
            $('#conversation-modal').fadeIn(300);
            
            // Faire défiler vers le bas
            setTimeout(() => {
                const container = $('.messages-container')[0];
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 100);
        },
        
        closeConversationModal() {
            $('#conversation-modal').fadeOut(300, function() {
                $(this).remove();
            });
            this.currentConversationId = null;
        },
        
        // === ENVOI DE MESSAGES ===
        
        sendAdminMessage(conversationId, message) {
            const $button = $('#send-admin-reply');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('📤 Envoi...');
            
            $.ajax({
                url: hotelChatbotAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hotel_chatbot_admin_message',
                    conversation_id: conversationId,
                    message: message,
                    nonce: hotelChatbotAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $('#admin-reply-text').val('');
                        this.showNotification('Message envoyé avec succès', 'success');
                        // Recharger la conversation
                        this.loadConversation(conversationId);
                    } else {
                        this.showNotification('Erreur lors de l\'envoi: ' + (response.data || 'Erreur inconnue'), 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erreur AJAX:', error);
                    this.showNotification('Erreur de connexion', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },
        
        // === FILTRAGE ET RECHERCHE ===
        
        initConversationFilters() {
            // Initialiser les filtres
        },
        
        handleConversationSearch() {
            this.filterConversations();
        },
        
        handleConversationFilter() {
            this.filterConversations();
        },
        
        filterConversations() {
            const searchTerm = $('#search-conversations').val().toLowerCase();
            const statusFilter = $('#filter-status').val();
            const languageFilter = $('#filter-language').val();
            
            $('.conversation-row').each(function() {
                const $row = $(this);
                const clientName = $row.find('.client-name').text().toLowerCase();
                const clientEmail = $row.find('.client-email').text().toLowerCase();
                const status = $row.data('status');
                const language = $row.data('language');
                
                let show = true;
                
                // Filtre de recherche
                if (searchTerm && !clientName.includes(searchTerm) && !clientEmail.includes(searchTerm)) {
                    show = false;
                }
                
                // Filtre de statut
                if (statusFilter && status !== statusFilter) {
                    show = false;
                }
                
                // Filtre de langue
                if (languageFilter && language !== languageFilter) {
                    show = false;
                }
                
                $row.toggle(show);
            });
            
            this.updateTableInfo();
        },
        
        // === UTILITAIRES ===
        
        updateTableInfo() {
            const total = $('.conversation-row').length;
            const visible = $('.conversation-row:visible').length;
            $('.total-count').text(`${visible} conversation(s) au total`);
        },
        
        showError(message) {
            this.showNotification(message, 'error');
            this.closeConversationModal();
        },
        
        showNotification(message, type = 'info') {
            // Supprimer les notifications existantes
            $('.admin-notification').remove();
            
            const notificationClass = {
                'success': 'notification-success',
                'error': 'notification-error', 
                'warning': 'notification-warning',
                'info': 'notification-info'
            }[type] || 'notification-info';
            
            const icon = {
                'success': '✅',
                'error': '❌',
                'warning': '⚠️',
                'info': 'ℹ️'
            }[type] || 'ℹ️';
            
            const notification = $(`
                <div class="admin-notification ${notificationClass}">
                    <span class="notification-icon">${icon}</span>
                    <span class="notification-message">${message}</span>
                    <button class="notification-close" onclick="$(this).parent().fadeOut()">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            notification.fadeIn(300);
            
            // Auto-masquer après 5 secondes
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        // Configuration du rafraîchissement automatique
        setupAutoRefresh() {
            // Rafraîchir toutes les 30 secondes si on est sur la page des conversations
            if ($('.conversations-table').length > 0) {
                this.autoRefreshInterval = setInterval(() => {
                    this.refreshConversationsData();
                }, 30000); // 30 secondes
                
                console.log('Auto-refresh activé pour les conversations (30s)');
            }
        },
        
        // Charger les données du dashboard
        loadDashboardData() {
            // Placeholder pour les statistiques du dashboard
            console.log('Dashboard data loading...');
        },
        
        // Initialiser les graphiques
        initCharts() {
            // Placeholder pour les graphiques
            console.log('Charts initialization...');
        },
        
        // Rafraîchir les données des conversations via AJAX
        refreshConversationsData() {
            if (typeof hotelChatbotAdmin === 'undefined') {
                console.error('hotelChatbotAdmin object not found!');
                return;
            }
            
            // Afficher un indicateur de chargement subtil
            $('.refresh-conversations').addClass('loading').prop('disabled', true);
            
            $.ajax({
                url: hotelChatbotAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hotel_chatbot_refresh_conversations',
                    nonce: hotelChatbotAdmin.nonce
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.updateConversationsTable(response.data);
                        console.log('Conversations refreshed successfully');
                    } else {
                        console.error('Failed to refresh conversations:', response);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error refreshing conversations:', error);
                },
                complete: () => {
                    $('.refresh-conversations').removeClass('loading').prop('disabled', false);
                }
            });
        },
        
        // Rafraîchir les données du dashboard
        refreshDashboardData() {
            console.log('Dashboard data refresh...');
        },
        
        // Gérer le rafraîchissement manuel
        handleRefreshConversations() {
            this.refreshConversationsData();
        },
        
        // Mettre à jour le tableau des conversations avec les nouvelles données
        updateConversationsTable(conversations) {
            const $tbody = $('.conversations-table tbody');
            
            if (!$tbody.length) {
                console.error('Table body not found');
                return;
            }
            
            // Sauvegarder les sélections actuelles
            const currentSelections = new Set();
            $('.conversation-checkbox:checked').each(function() {
                currentSelections.add($(this).val());
            });
            
            // Vider le tableau
            $tbody.empty();
            
            // Ajouter les nouvelles conversations
            conversations.forEach((conv, index) => {
                const isSelected = currentSelections.has(conv.id.toString());
                const rowHtml = this.generateConversationRow(conv, isSelected);
                $tbody.append(rowHtml);
            });
            
            // Restaurer les sélections
            this.selectedRows.clear();
            currentSelections.forEach(id => {
                if ($(`.conversation-checkbox[value="${id}"]`).length) {
                    this.selectedRows.add(id);
                    $(`.conversation-checkbox[value="${id}"]`).prop('checked', true);
                }
            });
            
            // Mettre à jour l'affichage
            this.updateSelectionCount();
            this.updateRowStyles();
            
            // Appliquer le tri actuel si il y en a un
            if (this.sortState.column) {
                this.sortConversations();
                // Mettre à jour l'icône de tri
                $('.sortable .sort-icon').text('↕️');
                $(`.sortable[data-sort="${this.sortState.column}"] .sort-icon`)
                    .text(this.sortState.direction === 'asc' ? '↑' : '↓');
            }
            
            // Mettre à jour le compteur total
            $('.total-conversations').text(`${conversations.length} conversation(s)`);
            
            // Afficher une notification subtile de mise à jour
            this.showRefreshNotification();
        },
        
        // Générer le HTML d'une ligne de conversation (structure identique au PHP)
        generateConversationRow(conv, isSelected = false) {
            const clientName = conv.client_name || 'Anonyme';
            const clientEmail = conv.client_email || 'Non renseigné';
            const language = conv.language || 'fr';
            const status = conv.status || 'active';
            const messageCount = conv.message_count || 0;
            const lastMessage = conv.last_message || '';
            
            // Formatage de la date comme dans le PHP
            const createdDate = conv.created_at ? new Date(conv.created_at) : new Date();
            const dateMain = createdDate.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const timeSub = createdDate.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            
            // Drapeaux pour les langues (comme dans le PHP)
            const flags = {
                'fr': '🇫🇷',
                'en': '🇬🇧',
                'es': '🇪🇸',
                'ar': '🇸🇦',
                'de': '🇩🇪'
            };
            const languageFlag = flags[language] || '🌐';
            
            const selectedClass = isSelected ? 'selected' : '';
            const checkedAttr = isSelected ? 'checked' : '';
            
            // Formatage du dernier message
            let lastMessageDisplay = '';
            if (lastMessage) {
                lastMessageDisplay = this.escapeHtml(lastMessage.length > 60 ? lastMessage.substring(0, 60) + '...' : lastMessage);
            } else {
                lastMessageDisplay = '<em style="color: #6b7280;">Aucun message</em>';
            }
            
            return `
                <tr class="conversation-row ${selectedClass}" 
                    data-id="${conv.id}"
                    data-status="${status}" 
                    data-language="${language}">
                    
                    <!-- Checkbox de sélection -->
                    <td class="col-select">
                        <input type="checkbox" class="conversation-checkbox table-checkbox" value="${conv.id}" ${checkedAttr}>
                    </td>
                    
                    <!-- Avatar -->
                    <td class="col-avatar">
                        <div class="client-avatar-small">
                            ${clientName.substring(0, 2).toUpperCase()}
                        </div>
                    </td>
                    
                    <!-- Nom du client -->
                    <td class="col-client-name">
                        <strong class="client-name">${this.escapeHtml(clientName)}</strong>
                    </td>
                    
                    <!-- Email du client -->
                    <td class="col-client-email">
                        <span class="client-email">${this.escapeHtml(clientEmail)}</span>
                    </td>
                    
                    <!-- Langue -->
                    <td class="col-language">
                        <span class="language-flag">${languageFlag}</span>
                        <span class="language-code">${language.toUpperCase()}</span>
                    </td>
                    
                    <!-- Nombre de messages -->
                    <td class="col-messages">
                        <span class="message-count-badge">
                            <i class="icon">💬</i>
                            ${messageCount}
                        </span>
                    </td>
                    
                    <!-- Dernier message -->
                    <td class="col-last-message">
                        <div class="last-message-preview">
                            ${lastMessageDisplay}
                        </div>
                    </td>
                    
                    <!-- Statut -->
                    <td class="col-status">
                        <span class="status-badge status-${status}">
                            <i class="status-icon"></i>
                            ${this.getStatusLabel(status)}
                        </span>
                    </td>
                    
                    <!-- Date -->
                    <td class="col-date">
                        <div class="date-cell">
                            <div class="date-main">${dateMain}</div>
                            <div class="time-sub">${timeSub}</div>
                        </div>
                    </td>
                    
                    <!-- Actions -->
                    <td class="col-actions">
                        <div class="action-buttons">
                            <button class="btn-action btn-view view-conversation" 
                                    data-id="${conv.id}" 
                                    title="Voir la conversation">
                                <i class="icon">👁️</i>
                            </button>
                            <button class="btn-action btn-delete" 
                                    data-id="${conv.id}" 
                                    title="Supprimer">
                                <i class="icon">🗑️</i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        },
        
        // Fonctions utilitaires
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        truncateText(text, maxLength) {
            if (text.length <= maxLength) return this.escapeHtml(text);
            return this.escapeHtml(text.substring(0, maxLength)) + '...';
        },
        
        getStatusLabel(status) {
            const labels = {
                'active': 'Actif',
                'closed': 'Fermé',
                'pending': 'En attente'
            };
            return labels[status] || status;
        },
        
        // Gérer l'affichage d'une conversation
        handleViewConversation(e) {
            e.preventDefault();
            
            // Récupérer l'ID depuis les différents formats d'attributs
            const $button = $(e.currentTarget);
            const conversationId = $button.data('id') || $button.data('conversation-id') || $button.attr('data-id') || $button.attr('data-conversation-id');
            
            console.log('Button clicked:', $button);
            console.log('Conversation ID found:', conversationId);
            console.log('Available data attributes:', $button.data());
            
            if (!conversationId) {
                console.error('ID de conversation manquant');
                console.error('Button element:', $button[0]);
                console.error('All attributes:', $button[0].attributes);
                this.showNotification('Erreur: ID de conversation manquant', 'error');
                return;
            }
            
            // Vérifier que les variables AJAX sont disponibles
            if (typeof hotelChatbotAdmin === 'undefined') {
                console.error('hotelChatbotAdmin object not found!');
                this.showNotification('Erreur: Configuration AJAX manquante', 'error');
                return;
            }
            
            // Afficher un loader
            const originalText = $button.html();
            $button.html('⏳ Chargement...').prop('disabled', true);
            
            // Appel AJAX pour récupérer la conversation et ses messages
            $.ajax({
                url: hotelChatbotAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hotel_chatbot_get_conversation',
                    conversation_id: conversationId,
                    nonce: hotelChatbotAdmin.nonce
                },
                success: (response) => {
                    if (response.success && response.data) {
                        // Afficher la conversation dans le modal
                        this.displayConversation(response.data);
                    } else {
                        console.error('Erreur lors du chargement de la conversation:', response);
                        this.showNotification(
                            response.data && response.data.message ? 
                            response.data.message : 
                            'Erreur lors du chargement de la conversation', 
                            'error'
                        );
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Erreur AJAX lors du chargement de la conversation:', error);
                    this.showNotification('Erreur de connexion lors du chargement', 'error');
                },
                complete: () => {
                    // Restaurer le bouton
                    $button.html(originalText).prop('disabled', false);
                }
            });
        },
        
        // Gérer la recherche de conversations
        handleConversationSearch(e) {
            const searchTerm = $(e.target).val().toLowerCase();
            
            $('.conversation-row').each(function() {
                const $row = $(this);
                const clientName = $row.find('.client-name').text().toLowerCase();
                const clientEmail = $row.find('.client-email').text().toLowerCase();
                
                if (clientName.includes(searchTerm) || clientEmail.includes(searchTerm)) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
            
            // Mettre à jour le compteur
            const visibleRows = $('.conversation-row:visible').length;
            $('.total-conversations').text(`${visibleRows} conversation(s)`);
        },
        
        // Gérer le filtrage des conversations
        handleConversationFilter() {
            const statusFilter = $('#filter-status').val();
            const languageFilter = $('#filter-language').val();
            
            $('.conversation-row').each(function() {
                const $row = $(this);
                const rowStatus = $row.data('status');
                const rowLanguage = $row.data('language');
                
                let showRow = true;
                
                // Filtrer par statut
                if (statusFilter && statusFilter !== 'all' && rowStatus !== statusFilter) {
                    showRow = false;
                }
                
                // Filtrer par langue
                if (languageFilter && languageFilter !== 'all' && rowLanguage !== languageFilter) {
                    showRow = false;
                }
                
                if (showRow) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
            
            // Mettre à jour le compteur
            const visibleRows = $('.conversation-row:visible').length;
            $('.total-conversations').text(`${visibleRows} conversation(s)`);
        },
        
        // Afficher une notification subtile de rafraîchissement
        showRefreshNotification() {
            // Éviter les notifications trop fréquentes
            if (this.lastRefreshNotification && Date.now() - this.lastRefreshNotification < 5000) {
                return;
            }
            
            this.lastRefreshNotification = Date.now();
            
            // Créer une notification discrète
            const notification = $(`
                <div class="refresh-notification">
                    <div class="refresh-icon">🔄</div>
                    <div class="refresh-text">Conversations mises à jour</div>
                </div>
            `);
            
            // Ajouter au DOM
            $('body').append(notification);
            
            // Animation d'apparition
            notification.fadeIn(200);
            
            // Auto-masquer après 2 secondes
            setTimeout(() => {
                notification.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 2000);
        },
        
        // Gestion du tri des colonnes
        handleTableSort(e) {
            const $header = $(e.currentTarget);
            const column = $header.data('sort');
            
            if (!column) return;
            
            // Basculer la direction du tri
            if (this.sortState.column === column) {
                this.sortState.direction = this.sortState.direction === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortState.column = column;
                this.sortState.direction = 'asc';
            }
            
            // Mettre à jour les icônes de tri
            $('.sortable .sort-icon').text('↕️');
            $header.find('.sort-icon').text(this.sortState.direction === 'asc' ? '↑' : '↓');
            
            // Trier les données
            this.sortConversations();
        },
        
        // Sélectionner/désélectionner toutes les conversations
        handleSelectAll(e) {
            const isChecked = $(e.target).is(':checked');
            
            // Cocher/décocher toutes les checkboxes individuelles
            $('.conversation-checkbox').prop('checked', isChecked);
            
            // Mettre à jour la sélection
            if (isChecked) {
                // Ajouter tous les IDs à la sélection
                $('.conversation-checkbox').each((index, checkbox) => {
                    this.selectedRows.add($(checkbox).val());
                });
            } else {
                // Vider la sélection
                this.selectedRows.clear();
            }
            
            // Mettre à jour l'affichage
            this.updateSelectionCount();
            this.updateRowStyles();
        },
        
        // Sélectionner/désélectionner une conversation individuelle
        handleRowSelect(e) {
            const $checkbox = $(e.target);
            const conversationId = $checkbox.val();
            const isChecked = $checkbox.is(':checked');
            
            if (isChecked) {
                this.selectedRows.add(conversationId);
            } else {
                this.selectedRows.delete(conversationId);
            }
            
            // Mettre à jour la checkbox "Tout sélectionner"
            const totalCheckboxes = $('.conversation-checkbox').length;
            const checkedCheckboxes = $('.conversation-checkbox:checked').length;
            
            $('#select-all-conversations').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
            $('#select-all-conversations').prop('checked', checkedCheckboxes === totalCheckboxes);
            
            // Mettre à jour l'affichage
            this.updateSelectionCount();
            this.updateRowStyles();
        },
        
        // Appliquer une action groupée
        handleBulkAction() {
            const action = $('.bulk-action-select').val();
            const selectedIds = Array.from(this.selectedRows);
            
            if (!action || selectedIds.length === 0) {
                this.showNotification('Veuillez sélectionner des conversations et une action', 'warning');
                return;
            }
            
            // Demander confirmation pour les actions destructives
            if (action === 'delete') {
                const confirmMessage = `Êtes-vous sûr de vouloir supprimer ${selectedIds.length} conversation(s) ?`;
                if (!confirm(confirmMessage)) {
                    return;
                }
            }
            
            // Afficher un loader
            $('.apply-bulk-action').prop('disabled', true).text('Traitement...');
            
            // Simuler le traitement (à remplacer par un appel AJAX réel)
            setTimeout(() => {
                this.showNotification(`Action "${action}" appliquée à ${selectedIds.length} conversation(s)`, 'success');
                
                // Réinitialiser
                $('.apply-bulk-action').prop('disabled', false).text('Appliquer');
                $('.bulk-action-select').val('');
                this.selectedRows.clear();
                this.updateSelectionCount();
                this.updateRowStyles();
                $('#select-all-conversations').prop('checked', false).prop('indeterminate', false);
                $('.conversation-checkbox').prop('checked', false);
            }, 1000);
        },
        
        // Mettre à jour le compteur de sélection
        updateSelectionCount() {
            const selectedCount = this.selectedRows.size;
            $('.selected-count').text(`${selectedCount} sélectionné(s)`);
            
            // Activer/désactiver les actions groupées
            $('.apply-bulk-action').prop('disabled', selectedCount === 0);
        },
        
        // Mettre à jour les styles des lignes sélectionnées
        updateRowStyles() {
            $('.conversation-row').each((index, row) => {
                const $row = $(row);
                const conversationId = $row.data('id').toString();
                
                if (this.selectedRows.has(conversationId)) {
                    $row.addClass('selected');
                } else {
                    $row.removeClass('selected');
                }
            });
        },
        
        // Trier les conversations
        sortConversations() {
            const { column, direction } = this.sortState;
            
            const $tbody = $('.conversations-table tbody');
            const $rows = $tbody.find('.conversation-row').get();
            
            $rows.sort((a, b) => {
                let aVal, bVal;
                
                switch (column) {
                    case 'client_name':
                        aVal = $(a).find('.client-name').text().toLowerCase();
                        bVal = $(b).find('.client-name').text().toLowerCase();
                        break;
                    case 'client_email':
                        aVal = $(a).find('.client-email').text().toLowerCase();
                        bVal = $(b).find('.client-email').text().toLowerCase();
                        break;
                    case 'language':
                        aVal = $(a).data('language');
                        bVal = $(b).data('language');
                        break;
                    case 'message_count':
                        aVal = parseInt($(a).find('.message-count').text()) || 0;
                        bVal = parseInt($(b).find('.message-count').text()) || 0;
                        break;
                    default:
                        return 0;
                }
                
                if (aVal < bVal) return direction === 'asc' ? -1 : 1;
                if (aVal > bVal) return direction === 'asc' ? 1 : -1;
                return 0;
            });
            
            // Réorganiser les lignes
            $tbody.empty().append($rows);
        },
        
        // Gestion de l'upload d'avatar
        handleAvatarUpload() {
            console.log('Avatar upload started');
            console.log('hotelChatbotAdmin object:', hotelChatbotAdmin);
            
            // Vérifier que les variables AJAX sont disponibles
            if (typeof hotelChatbotAdmin === 'undefined') {
                console.error('hotelChatbotAdmin object not found!');
                alert('Erreur: Variables AJAX non définies. Rechargez la page.');
                return;
            }
            
            if (!hotelChatbotAdmin.ajaxurl || !hotelChatbotAdmin.nonce) {
                console.error('AJAX URL or nonce missing:', hotelChatbotAdmin);
                alert('Erreur: Configuration AJAX incomplète.');
                return;
            }
            
            try {
                // Vérifier que l'interface est prête
                if (!$('#upload-avatar-btn').length) {
                    console.error('Upload button not found');
                    return;
                }
                
                // Créer un input file temporaire
                const fileInput = $('<input type="file" accept="image/*" style="display: none;">');
                $('body').append(fileInput);
                
                fileInput.on('change', (e) => {
                const file = e.target.files[0];
                if (!file) return;
                
                // Vérifier le type de fichier
                if (!file.type.match(/^image\/(jpeg|jpg|png|gif)$/)) {
                    this.showNotification('Veuillez sélectionner une image (JPG, PNG, GIF)', 'error');
                    return;
                }
                
                // Vérifier la taille (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    this.showNotification('L\'image doit faire moins de 2MB', 'error');
                    return;
                }
                
                // Afficher un loader
                $('#upload-avatar-btn').html('<span class="upload-icon">⏳</span> Upload en cours...');
                
                // Créer FormData pour l'upload
                const formData = new FormData();
                formData.append('action', 'hotel_chatbot_avatar_upload');
                formData.append('nonce', hotelChatbotAdmin.nonce);
                formData.append('avatar', file);
                
                // Envoyer via AJAX
                $.ajax({
                    url: hotelChatbotAdmin.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: (response) => {
                        console.log('Upload response:', response);
                        if (response.success) {
                            // Vérifier que l'URL existe dans la réponse
                            const avatarUrl = response.data.avatar_url || response.data.url;
                            console.log('Avatar URL:', avatarUrl);
                            
                            if (avatarUrl) {
                                // Mettre à jour l'aperçu
                                this.updateAvatarPreview(avatarUrl);
                                // Mettre à jour le champ caché
                                $('#avatar-url-input').val(avatarUrl);
                                this.showNotification('Avatar mis à jour avec succès!', 'success');
                            } else {
                                console.error('No avatar URL in response');
                                this.showNotification('Erreur: URL d\'avatar manquante', 'error');
                            }
                        } else {
                            console.error('Upload failed:', response.data);
                            this.showNotification(response.data || 'Erreur lors de l\'upload', 'error');
                        }
                    },
                    error: () => {
                        this.showNotification('Erreur lors de l\'upload de l\'avatar', 'error');
                    },
                    complete: () => {
                        // Restaurer le bouton
                        $('#upload-avatar-btn').html('<span class="upload-icon">📁</span> Changer l\'avatar');
                        fileInput.remove();
                    }
                });
                });
                
                // Déclencher le sélecteur de fichier
                fileInput.click();
                
            } catch (error) {
                console.error('Avatar upload error:', error);
                this.showNotification('Erreur lors de l\'initialisation de l\'upload', 'error');
            }
        },
        
        // Suppression de l'avatar
        handleAvatarRemove() {
            if (!confirm('Êtes-vous sûr de vouloir supprimer l\'avatar actuel ?')) {
                return;
            }
            
            // Afficher un loader
            $('#remove-avatar-btn').html('<span class="remove-icon">⏳</span> Suppression...');
            
            $.ajax({
                url: hotelChatbotAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'hotel_chatbot_avatar_remove',
                    nonce: hotelChatbotAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Réinitialiser l'aperçu
                        this.resetAvatarPreview();
                        // Vider le champ caché
                        $('#avatar-url-input').val('');
                        this.showNotification('Avatar supprimé avec succès!', 'success');
                    } else {
                        this.showNotification(response.data || 'Erreur lors de la suppression', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Erreur lors de la suppression de l\'avatar', 'error');
                },
                complete: () => {
                    // Restaurer le bouton
                    $('#remove-avatar-btn').html('<span class="remove-icon">🗑️</span> Supprimer');
                }
            });
        },
        
        // Mettre à jour l'aperçu de l'avatar
        updateAvatarPreview(imageUrl) {
            const preview = $('.avatar-preview');
            preview.html(`<img src="${imageUrl}" alt="Avatar actuel" class="current-avatar">`);
            
            // Mettre à jour les boutons
            $('#upload-avatar-btn').html('<span class="upload-icon">📁</span> Changer l\'avatar');
            
            // Ajouter le bouton de suppression s'il n'existe pas
            if (!$('#remove-avatar-btn').length) {
                $('.avatar-controls').append(`
                    <button type="button" class="btn-remove-avatar" id="remove-avatar-btn">
                        <span class="remove-icon">🗑️</span>
                        Supprimer
                    </button>
                `);
            }
        },
        
        // Réinitialiser l'aperçu de l'avatar
        resetAvatarPreview() {
            const preview = $('.avatar-preview');
            preview.html(`
                <div class="default-avatar">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 1H5C3.89 1 3 1.89 3 3V21C3 22.11 3.89 23 5 23H19C20.11 23 21 22.11 21 21V9M19 9H14V4H5V21H19V9Z"/>
                    </svg>
                    <span>Aucun avatar</span>
                </div>
            `);
            
            // Mettre à jour les boutons
            $('#upload-avatar-btn').html('<span class="upload-icon">📁</span> Choisir un avatar');
            $('#remove-avatar-btn').remove();
        }
    };
    
    // Initialiser l'application admin
    AdminChatbot.init();
    
    // Exposer l'objet globalement pour les appels externes
    window.AdminChatbot = AdminChatbot;
});
