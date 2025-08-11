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
            const conversationId = $(e.target).closest('.view-conversation').data('id');
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
        
        // Méthodes placeholder pour éviter les erreurs
        loadDashboardData() {},
        initCharts() {},
        refreshConversationsData() {},
        refreshDashboardData() {},
        handleRefreshConversations() {},
        handleTableSort() {},
        handleSelectAll() {},
        handleRowSelect() {},
        handleBulkAction() {},
        updateSelectionCount() {}
    };
    
    // Initialiser l'application admin
    AdminChatbot.init();
    
    // Exposer l'objet globalement pour les appels externes
    window.AdminChatbot = AdminChatbot;
});
