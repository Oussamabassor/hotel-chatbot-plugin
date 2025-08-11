/**
 * Hotel Chatbot Client - Interface Moderne et Intelligente
 * Chatbot spécialisé dans les réservations hôtelières avec workflow RGPD
 * Version 2.0 - Améliorations UX et logique métier
 */

class HotelChatbotClient {
    constructor() {
        this.elements = {};
        this.state = {
            userName: '',
            userEmail: '',
            conversationId: null,
            sessionId: null,
            isTyping: false,
            lastMessageTime: null,
            messageCount: 0,
            language: (typeof hotelChatbotAjax !== 'undefined') ? hotelChatbotAjax.defaultLanguage : 'fr'
        };
        
        // Configuration dynamique basée sur les paramètres admin
        this.config = {
            typingDelay: (typeof hotelChatbotAjax !== 'undefined') ? parseInt(hotelChatbotAjax.typingDelay) : 1500,
            welcomeDelay: 500,
            maxRetries: 3,
            maxMessages: (typeof hotelChatbotAjax !== 'undefined') ? parseInt(hotelChatbotAjax.maxMessages) : 100,
            enableSound: (typeof hotelChatbotAjax !== 'undefined') ? hotelChatbotAjax.enableSound === '1' : true,
            autoOpen: (typeof hotelChatbotAjax !== 'undefined') ? hotelChatbotAjax.autoOpen === '1' : false,
            requireName: (typeof hotelChatbotAjax !== 'undefined') ? hotelChatbotAjax.requireName === '1' : true,
            chatTitle: (typeof hotelChatbotAjax !== 'undefined') ? hotelChatbotAjax.chatTitle : 'Assistant Hôtel',
            avatarUrl: (typeof hotelChatbotAjax !== 'undefined') ? hotelChatbotAjax.avatarUrl : '',
            welcomeMessages: (typeof hotelChatbotAjax !== 'undefined') ? hotelChatbotAjax.welcomeMessages : {
                'fr': 'Bonjour ! Comment puis-je vous aider aujourd\'hui ?',
                'en': 'Hello! How can I help you today?',
                'es': '¡Hola! ¿Cómo puedo ayudarte hoy?',
                'ar': 'مرحبا! كيف يمكنني مساعدتك اليوم؟'
            },
            autoResponses: this.initAutoResponses(),
            // Configuration des cookies
            enableCookies: (typeof hotelChatbotAjax !== 'undefined') ? hotelChatbotAjax.enableCookies === '1' : true,
            cookieExpirationDays: (typeof hotelChatbotAjax !== 'undefined') ? parseInt(hotelChatbotAjax.cookieExpirationDays) : 30
        };
        
        // Initialiser le gestionnaire de cookies
        this.cookieManager = null;
        if (typeof HotelChatbotCookieManager !== 'undefined') {
            this.cookieManager = new HotelChatbotCookieManager({
                enabled: this.config.enableCookies,
                expirationDays: this.config.cookieExpirationDays,
                maxMessages: this.config.maxMessages
            });
        }
        
        this.init();
    }

    // Initialisation du chatbot
    init() {
        if (!this.validateElements()) {
            console.log('Chatbot elements not found, skipping initialization');
            return;
        }

        this.bindEvents();
        this.setupValidation();
        this.preloadResponses();
        this.updateAvatars();
        this.restoreSession();
        this.setupAutoOpen();
        this.trackEvent('chatbot_initialized');
    }

    // Validation des éléments DOM
    validateElements() {
        const requiredElements = {
            floatingBtn: 'chatbot-floating-btn',
            container: 'chatbot-container',
            closeBtn: 'chatbot-close',
            endConversationBtn: 'end-conversation',
            userEmailInput: 'user-email',
            userNameInput: 'user-name',
            startBtn: 'start-conversation',
            consentForm: 'consent-form',
            chatInterface: 'chat-interface',
            chatMessages: 'chat-messages',
            chatInput: 'chat-input',
            sendBtn: 'send-message',
            typingIndicator: 'typing-indicator'
        };

        for (const [key, id] of Object.entries(requiredElements)) {
            this.elements[key] = document.getElementById(id);
            if (!this.elements[key] && ['floatingBtn', 'container'].includes(key)) {
                return false;
            }
        }

        // Éléments par classe
        this.elements.suggestionBtns = document.querySelectorAll('.suggestion-btn');
        return true;
    }

    // Liaison des événements
    bindEvents() {
        // Événements principaux
        this.elements.floatingBtn?.addEventListener('click', () => this.toggleChatbot());
        this.elements.closeBtn?.addEventListener('click', () => this.closeChatbot());
        this.elements.endConversationBtn?.addEventListener('click', () => this.endConversation());
        this.elements.startBtn?.addEventListener('click', () => this.startConversation());
        this.elements.sendBtn?.addEventListener('click', () => this.sendMessage());

        // Événements clavier
        this.elements.chatInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });

        this.elements.userEmailInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.elements.startBtn && !this.elements.startBtn.disabled) {
                    this.startConversation();
                }
            }
        });

        this.elements.userNameInput?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.elements.startBtn && !this.elements.startBtn.disabled) {
                    this.startConversation();
                }
            }
        });

        // Suggestions intelligentes
        this.elements.suggestionBtns?.forEach(btn => {
            btn.addEventListener('click', () => this.handleSuggestionClick(btn));
        });

        // Auto-ouverture conditionnelle
        this.setupAutoOpen();
    }

    // Configuration de la validation en temps réel
    setupValidation() {
        if (!this.elements.userEmailInput || !this.elements.startBtn) return;

        this.elements.userEmailInput.addEventListener('input', () => {
            this.validateForm();
        });

        this.elements.userNameInput?.addEventListener('input', () => {
            this.validateForm();
        });

        // Validation initiale
        this.validateForm();
    }

    // Validation du formulaire
    validateForm() {
        const email = this.elements.userEmailInput?.value.trim();
        const name = this.elements.userNameInput?.value.trim();
        
        // L'email est toujours requis et doit être valide
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isEmailValid = email && emailRegex.test(email);
        
        // Le nom est optionnel maintenant
        const isValid = isEmailValid;
        
        if (this.elements.startBtn) {
            this.elements.startBtn.disabled = !isValid;
            this.elements.startBtn.classList.toggle('enabled', isValid);
        }

        return isValid;
    }

    // Toggle du chatbot (afficher/cacher) avec animation
    toggleChatbot() {
        if (!this.elements.container) return;

        const isVisible = this.elements.container.classList.contains('visible');
        
        if (isVisible) {
            // Fermer le chatbot
            this.closeChatbot();
        } else {
            // Ouvrir le chatbot
            this.openChatbot();
        }
    }

    // Ouverture du chatbot avec animation
    openChatbot() {
        if (!this.elements.container) return;
        
        // Masquer le bouton flottant quand le chatbot s'ouvre
        if (this.elements.floatingBtn) {
            this.elements.floatingBtn.style.display = 'none';
        }
        
        this.elements.container.style.display = 'flex';
        setTimeout(() => {
            this.elements.container.classList.add('visible');
            this.playNotificationSound('open');
            this.trackEvent('chatbot_opened');
        }, 10);
    }

    // Fermeture du chatbot avec animation
    closeChatbot() {
        if (!this.elements.container) return;

        this.elements.container.classList.remove('visible');
        setTimeout(() => {
            this.elements.container.style.display = 'none';
            
            // Réafficher le bouton flottant quand le chatbot se ferme
            if (this.elements.floatingBtn) {
                this.elements.floatingBtn.style.display = 'flex';
            }
            
            this.trackEvent('chatbot_closed');
        }, 300);
    }

    // Terminer la conversation proprement
    async endConversation() {
        if (!this.state.conversationId) {
            console.log('Aucune conversation active à terminer');
            return;
        }

        // Demander confirmation
        const confirmed = confirm(
            'Êtes-vous sûr de vouloir terminer cette conversation ?\n\n' +
            'Cette action supprimera l\'historique de vos messages et vous devrez recommencer une nouvelle conversation.'
        );

        if (!confirmed) {
            return;
        }

        try {
            // Marquer la conversation comme terminée côté serveur
            await this.markConversationAsEnded();

            // Supprimer la session locale
            this.clearCurrentSession();

            // Réinitialiser l'état
            this.resetConversationState();

            // Retourner au formulaire de consentement
            this.returnToConsentForm();

            // Afficher un message de confirmation
            this.showEndConversationMessage();

            this.trackEvent('conversation_ended_by_user');

        } catch (error) {
            console.error('Erreur lors de la fin de conversation:', error);
            alert('Une erreur est survenue lors de la fin de conversation. Veuillez réessayer.');
        }
    }

    // Démarrage de la conversation
    async startConversation() {
        if (!this.validateForm()) return;

        const email = this.elements.userEmailInput.value.trim();
        const name = this.elements.userNameInput?.value.trim() || '';
        
        // L'email est l'identifiant principal, le nom est optionnel
        this.state.userEmail = email;
        this.state.userName = name || 'Client';

        try {
            // Afficher le loader
            this.showLoader(this.elements.startBtn);

            // Vérifier s'il existe déjà une conversation pour cet email
            const existingConversation = await this.checkExistingConversation(email);
            
            if (existingConversation) {
                // Restaurer la conversation existante
                this.state.conversationId = existingConversation.id;
                console.log('Conversation existante trouvée:', existingConversation);
                
                // Mettre à jour le nom du client si différent
                if (this.state.userName && this.state.userName !== 'Client' && 
                    existingConversation.client_name !== this.state.userName) {
                    await this.updateClientName(existingConversation.id, this.state.userName);
                }
                
                // Transition vers l'interface de chat
                this.transitionToChat();
                
                // Restaurer les messages existants
                await this.loadExistingMessages(existingConversation.id);
            } else {
                // Créer une nouvelle conversation
                await this.createConversation(this.state.userName);
                
                // Transition vers l'interface de chat
                this.transitionToChat();
                
                // Message de bienvenue personnalisé
                setTimeout(() => {
                    this.showWelcomeMessage();
                }, this.config.welcomeDelay);
            }

            // Sauvegarder la session après le démarrage
            this.saveSessionData();

        } catch (error) {
            this.handleError('Erreur lors du démarrage de la conversation', error);
        } finally {
            this.hideLoader(this.elements.startBtn);
        }
    }

    // Transition vers l'interface de chat
    transitionToChat() {
        if (!this.elements.consentForm || !this.elements.chatInterface) return;

        this.elements.consentForm.style.display = 'none';
        this.elements.chatInterface.style.display = 'flex';
        
        // Afficher le bouton "Terminer la conversation"
        this.showEndConversationButton();
        
        // Sauvegarder immédiatement la session après la transition
        setTimeout(() => {
            this.saveSessionData();
        }, 500);
        
        // Focus sur l'input de chat
        setTimeout(() => {
            this.elements.chatInput?.focus();
        }, 300);

        this.trackEvent('conversation_started', { userName: this.state.userName });
    }

    // Afficher le bouton "Terminer la conversation"
    showEndConversationButton() {
        if (!this.elements.endConversationBtn) return;
        
        this.elements.endConversationBtn.style.display = 'flex';
    }

    // Message de bienvenue intelligent avec paramètres dynamiques
    showWelcomeMessage() {
        // Utiliser le message personnalisé depuis les paramètres admin
        const customWelcome = this.config.welcomeMessages[this.state.language] || 
                             this.config.welcomeMessages['fr'] || 
                             'Bonjour ! Comment puis-je vous aider aujourd\'hui ?';
        
        // Personnaliser avec le nom de l'utilisateur si requis
        let personalizedWelcome;
        if (this.config.requireName && this.state.userName) {
            // Ajouter le nom au début du message
            personalizedWelcome = `Bonjour ${this.state.userName} ! 👋\n\n${customWelcome}`;
        } else {
            personalizedWelcome = customWelcome;
        }
        
        // Messages de bienvenue séquentiels
        const welcomeMessages = [
            personalizedWelcome,
            `Vous pouvez utiliser les suggestions ci-dessous ou me poser directement vos questions. 💬`
        ];

        welcomeMessages.forEach((message, index) => {
            setTimeout(() => {
                this.addMessage('bot', message, index === 0);
            }, index * this.config.welcomeDelay);
        });
        
        // Mettre à jour le titre du chat si configuré
        this.updateChatTitle();
    }
    
    // Mettre à jour le titre du chat avec les paramètres dynamiques
    updateChatTitle() {
        const titleElement = document.querySelector('.chatbot-header h3, .chatbot-title');
        if (titleElement && this.config.chatTitle) {
            titleElement.textContent = this.config.chatTitle;
        }
    }

    // Gestion des clics sur suggestions
    handleSuggestionClick(btn) {
        const question = btn.getAttribute('data-question');
        const category = btn.getAttribute('data-category');
        
        if (!question) return;

        // Animation du bouton
        btn.style.transform = 'scale(0.95)';
        setTimeout(() => {
            btn.style.transform = '';
        }, 150);

        // Envoyer la question
        this.addMessage('user', question);
        this.processMessage(question, category);

        this.trackEvent('suggestion_clicked', { question, category });
    }

    // Envoi de message utilisateur
    sendMessage() {
        const input = this.elements.chatInput;
        if (!input) return;

        const message = input.value.trim();
        if (!message) return;

        // Ajouter le message utilisateur
        this.addMessage('user', message);
        input.value = '';

        // Traiter le message
        this.processMessage(message);

        this.trackEvent('message_sent', { message: message.substring(0, 50) });
    }

    // Traitement intelligent des messages
    async processMessage(message, category = null) {
        this.showTypingIndicator();

        try {
            // Vérifier les réponses automatiques
            const autoResponse = this.getAutoResponse(message, category);
            
            if (autoResponse) {
                setTimeout(() => {
                    this.hideTypingIndicator();
                    this.addMessage('bot', autoResponse);
                }, this.config.typingDelay);
            } else {
                // Envoyer au serveur pour traitement IA
                await this.sendMessageToServer(message);
            }

        } catch (error) {
            this.hideTypingIndicator();
            this.handleError('Erreur lors du traitement du message', error);
        }
    }

    // Système de réponses automatiques intelligentes
    getAutoResponse(message, category) {
        const responses = this.config.autoResponses;
        const messageLower = message.toLowerCase();

        // Réponses par catégorie
        if (category && responses.categories[category]) {
            return responses.categories[category];
        }

        // Réponses par mots-clés
        for (const [keywords, response] of Object.entries(responses.keywords)) {
            if (keywords.split('|').some(keyword => messageLower.includes(keyword))) {
                return typeof response === 'function' ? response(this.state.userName) : response;
            }
        }

        return null;
    }

    // Initialisation des réponses automatiques
    initAutoResponses() {
        return {
            categories: {
                availability: `Parfait ! Pour vérifier nos disponibilités, j'ai besoin de quelques informations :\n\n📅 Quelles sont vos dates de séjour ?\n👥 Combien de personnes ?\n🏨 Avez-vous une préférence pour le type de chambre ?`,
                pricing: `Je serais ravi de vous présenter nos tarifs ! 💰\n\nNos prix varient selon :\n• La période de séjour\n• Le type de chambre\n• Les services inclus\n\nPouvez-vous me préciser vos dates pour vous donner un devis personnalisé ?`,
                info: `⏰ Nos horaires :\n\n• Check-in : à partir de 15h00\n• Check-out : jusqu'à 11h00\n\n💡 Astuce : Un check-in anticipé ou check-out tardif peut être possible selon disponibilité (supplément éventuel).`,
                services: `Nous proposons de nombreux services pour rendre votre séjour agréable ! ✨\n\nSouhaitez-vous des informations sur :\n• Restaurant et petit-déjeuner 🍳\n• Parking et accès 🚗\n• WiFi et équipements 📶\n• Services de conciergerie 🛎️`,
                policies: `🐕 Politique animaux :\n\nNous accueillons vos compagnons à quatre pattes avec plaisir !\n• Supplément : 15€/nuit/animal\n• Animaux acceptés : chiens et chats\n• Poids maximum : 20kg\n\nMerci de nous prévenir lors de la réservation.`,
                booking: `📝 Gestion de réservation :\n\n• Modification : possible jusqu'à 24h avant l'arrivée\n• Annulation gratuite : jusqu'à 48h avant\n• Annulation tardive : 1 nuit facturée\n\nAvez-vous un numéro de réservation à modifier ?`
            },
            keywords: {
                'bonjour|salut|hello|bonsoir': (name) => `Bonjour ${name} ! Comment puis-je vous aider avec votre séjour ? 😊`,
                'merci|thanks': `Je vous en prie ! N'hésitez pas si vous avez d'autres questions. 😊`,
                'au revoir|bye|à bientôt': `Au revoir ! J'espère vous accueillir bientôt dans notre hôtel. À très bientôt ! 👋`,
                'prix|tarif|coût|combien': `Pour vous donner un tarif précis, j'ai besoin de connaître vos dates de séjour. Quand souhaitez-vous réserver ? 📅`,
                'disponible|libre|dispo': `Je vérifie nos disponibilités ! Quelles sont vos dates de séjour et pour combien de personnes ? 🏨`,
                'wifi|internet|connexion': `📶 WiFi gratuit et haut débit dans tout l'hôtel ! Code fourni à l'accueil.`,
                'parking|voiture|garer': `🚗 Parking privé sécurisé disponible :\n• Gratuit pour nos clients\n• Accès 24h/24\n• Places limitées (réservation recommandée)`,
                'petit-déjeuner|breakfast|déjeuner': `🍳 Petit-déjeuner buffet :\n• Continental et chaud\n• 7h-10h en semaine, 7h30-10h30 weekend\n• 18€/personne\n• Inclus dans certaines offres`,
                'annuler|annulation|modifier': `Pour modifier ou annuler votre réservation, j'ai besoin de votre numéro de confirmation. L'avez-vous ? 📝`,
                'problème|erreur|bug|aide': `Je suis désolé pour ce désagrément. Pouvez-vous me décrire le problème ? Je vais vous aider à le résoudre. 🛠️`
            }
        };
    }

    // Affichage de l'indicateur de frappe
    showTypingIndicator() {
        if (!this.elements.typingIndicator) return;
        
        this.state.isTyping = true;
        this.elements.typingIndicator.style.display = 'flex';
        this.scrollToBottom();
    }

    // Masquage de l'indicateur de frappe
    hideTypingIndicator() {
        if (!this.elements.typingIndicator) return;
        
        this.state.isTyping = false;
        this.elements.typingIndicator.style.display = 'none';
    }

    // Ajout de message avec animations
    addMessage(sender, content, isWelcome = false) {
        if (!this.elements.chatMessages) return;

        const messageElement = this.createMessageElement(sender, content, isWelcome);
        this.elements.chatMessages.appendChild(messageElement);
        
        // Jouer le son de notification pour les messages bot
        if (sender === 'bot' && !isWelcome) {
            this.playNotificationSound('message');
        }
        
        this.state.messageCount++;
        this.state.lastMessageTime = new Date();
        
        // Sauvegarder la session après chaque message
        this.updateSession();
        
        // Vérifier la limite de messages
        this.checkMessageLimit();
        
        this.scrollToBottom();
        this.trackEvent('message_added', { sender, messageCount: this.state.messageCount });
    }
    
    // Vérification de la limite de messages
    checkMessageLimit() {
        const limit = this.config.maxMessages;
        const warningThreshold = Math.floor(limit * 0.8); // Avertir à 80% de la limite
        
        if (this.state.messageCount >= limit) {
            // Limite atteinte - désactiver l'envoi de messages
            if (this.elements.chatInput) {
                this.elements.chatInput.disabled = true;
                this.elements.chatInput.placeholder = 'Limite de messages atteinte';
            }
            
            if (this.elements.sendBtn) {
                this.elements.sendBtn.disabled = true;
            }
            
            // Message d'information
            const limitMessage = `📊 Limite de conversation atteinte (${limit} messages).\n\n` +
                               `Pour continuer à discuter :\n` +
                               `📞 Appelez-nous : 01 23 45 67 89\n` +
                               `✉️ Email : contact@hotel-excellence.com\n` +
                               `🌐 Site web : www.hotel-excellence.com`;
            
            setTimeout(() => {
                this.addMessage('bot', limitMessage);
            }, 1000);
            
        } else if (this.state.messageCount >= warningThreshold) {
            // Avertissement proche de la limite
            const remaining = limit - this.state.messageCount;
            if (remaining === 5 || remaining === 10) {
                const warningMessage = `⚠️ Plus que ${remaining} messages disponibles dans cette conversation.`;
                setTimeout(() => {
                    this.addMessage('bot', warningMessage);
                }, 500);
            }
        }
    }

    // Création d'élément de message
    createMessageElement(sender, content, isWelcome = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}`;
        
        const avatar = this.createAvatarElement(sender);
        const contentDiv = this.createMessageContent(content, sender);
        
        if (sender === 'user') {
            messageDiv.appendChild(contentDiv);
            messageDiv.appendChild(avatar);
        } else {
            messageDiv.appendChild(avatar);
            messageDiv.appendChild(contentDiv);
        }

        return messageDiv;
    }

    // Création d'avatar
    createAvatarElement(sender) {
        const avatarDiv = document.createElement('div');
        avatarDiv.className = 'message-avatar';
        
        if (sender === 'bot') {
            // Utiliser l'avatar personnalisé si disponible, sinon le SVG par défaut
            if (this.config.avatarUrl) {
                avatarDiv.innerHTML = `
                    <img src="${this.config.avatarUrl}" 
                         alt="Avatar du chatbot" 
                         class="message-avatar-img"
                         style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" 
                         style="width: 100%; height: 100%; border-radius: 50%; display: none;">
                        <circle cx="12" cy="12" r="10" fill="url(#messageGradient)"></circle>
                        <path d="M12 15C14.2091 15 16 16.7909 16 19H8C8 16.7909 9.79086 15 12 15Z" fill="#ffffff"></path>
                        <circle cx="12" cy="10" r="3" fill="#ffffff"></circle>
                        <defs>
                            <linearGradient id="messageGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#1d4ed8;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                    </svg>
                `;
            } else {
                avatarDiv.innerHTML = `
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: 100%; border-radius: 50%;">
                        <circle cx="12" cy="12" r="10" fill="url(#messageGradient)"></circle>
                        <path d="M12 15C14.2091 15 16 16.7909 16 19H8C8 16.7909 9.79086 15 12 15Z" fill="#ffffff"></path>
                        <circle cx="12" cy="10" r="3" fill="#ffffff"></circle>
                        <defs>
                            <linearGradient id="messageGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                                <stop offset="100%" style="stop-color:#1d4ed8;stop-opacity:1" />
                            </linearGradient>
                        </defs>
                    </svg>
                `;
            }
        } else {
            avatarDiv.innerHTML = `
                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #6b7280, #4b5563); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">
                    ${this.state.userName.charAt(0).toUpperCase()}
                </div>
            `;
        }
        
        return avatarDiv;
    }

    // Création du contenu du message
    createMessageContent(content, sender) {
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        
        const textP = document.createElement('p');
        textP.className = 'message-text';
        textP.innerHTML = this.formatMessageContent(content);
        
        const timeSpan = document.createElement('div');
        timeSpan.className = 'message-time';
        timeSpan.textContent = this.formatTime(new Date());
        
        contentDiv.appendChild(textP);
        contentDiv.appendChild(timeSpan);
        
        return contentDiv;
    }

    // Formatage du contenu des messages
    formatMessageContent(content) {
        return content
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>');
    }

    // Formatage de l'heure
    formatTime(date) {
        return date.toLocaleTimeString('fr-FR', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }

    // Défilement automatique
    scrollToBottom() {
        if (!this.elements.chatMessages) return;
        
        setTimeout(() => {
            this.elements.chatMessages.scrollTop = this.elements.chatMessages.scrollHeight;
        }, 100);
    }

    // Création de conversation (AJAX)
    async createConversation(clientName) {
        if (typeof hotelChatbotAjax === 'undefined') {
            throw new Error('Configuration AJAX manquante');
        }

        const formData = new FormData();
        formData.append('action', 'hotel_chatbot_message');
        formData.append('nonce', hotelChatbotAjax.nonce);
        formData.append('client_name', clientName);
        formData.append('client_email', this.state.userEmail);
        formData.append('message', `Nouvelle conversation démarrée par ${clientName}`);

        const response = await fetch(hotelChatbotAjax.ajaxurl, {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`Erreur HTTP: ${response.status}`);
        }

        const data = await response.json();
        
        if (data.success) {
            this.state.conversationId = data.data.conversation_id;
        } else {
            throw new Error(data.data || 'Erreur lors de la création de la conversation');
        }
    }

    // Envoi de message au serveur avec gestion IA
    async sendMessageToServer(message) {
        try {
            // Afficher un indicateur de traitement intelligent
            this.showChatGPTIndicator();

            const formData = new FormData();
            formData.append('action', 'hotel_chatbot_message');
            formData.append('nonce', hotelChatbotAjax.nonce);
            formData.append('message', message);
            formData.append('client_name', this.state.userName);
            formData.append('client_email', this.state.userEmail);
            formData.append('language', this.state.language);
            
            if (this.state.conversationId) {
                formData.append('conversation_id', this.state.conversationId);
            }
            
            // Ajouter le session_id pour la persistance des cookies
            if (this.state.sessionId) {
                formData.append('session_id', this.state.sessionId);
            } else if (this.cookieManager) {
                // Générer un nouveau session_id si nécessaire
                this.state.sessionId = this.cookieManager.generateSessionId();
                formData.append('session_id', this.state.sessionId);
            }

            console.log('Envoi des données:', {
                message: message,
                client_name: this.state.userName,
                client_email: this.state.userEmail,
                language: this.state.language,
                conversation_id: this.state.conversationId
            });

            const response = await fetch(hotelChatbotAjax.ajaxurl, {
                method: 'POST',
                body: formData
            });

            console.log('Statut de la réponse:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Données reçues du serveur:', data);
            
            this.hideChatGPTIndicator();

            if (data.success) {
                // Sauvegarder l'ID de conversation et session_id
                if (data.data.conversation_id) {
                    this.state.conversationId = data.data.conversation_id;
                }
                if (data.data.session_id) {
                    this.state.sessionId = data.data.session_id;
                }

                // Afficher la réponse - utiliser addMessage normal au lieu de addAIMessage
                this.addMessage('bot', data.data.response);
                
                // Sauvegarder la session complète dans les cookies
                this.saveSessionData();
                this.saveConversationHistory();
                
                // Tracking pour analytics
                this.trackEvent('chatgpt_response_received', { 
                    message_length: data.data.response.length,
                    conversation_id: this.state.conversationId 
                });

            } else {
                console.error('Erreur du serveur:', data.data);
                // Afficher le message d'erreur du serveur s'il existe
                const errorMessage = data.data || 'Erreur inconnue du serveur';
                this.addMessage('bot', `Désolé, une erreur s'est produite : ${errorMessage}`);
            }

        } catch (error) {
            this.hideChatGPTIndicator();
            console.error('Erreur lors de l\'envoi du message:', error);
            
            // Message d'erreur plus informatif
            this.addMessage('bot', 
                `Désolé ${this.state.userName}, je rencontre une difficulté technique (${error.message}). ` +
                `Pouvez-vous reformuler votre question ou contacter notre réception ? 📞`
            );
            
            this.trackEvent('chatgpt_error', { error: error.message });
        }
    }

    // Indicateur de traitement intelligent
    showChatGPTIndicator() {
        this.hideTypingIndicator(); // Masquer l'indicateur normal
        
        if (!this.elements.chatMessages) return;

        // Créer un indicateur de traitement personnalisé
        const indicatorDiv = document.createElement('div');
        indicatorDiv.className = 'message bot-message ai-thinking';
        indicatorDiv.innerHTML = `
            <div class="message-avatar">
                <div class="avatar-gradient">🏨</div>
            </div>
            <div class="message-content">
                <div class="ai-loader">
                    <div class="ai-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <div class="ai-text">Notre assistant analyse votre demande...</div>
                </div>
            </div>
        `;
        
        indicatorDiv.id = 'ai-indicator';
        this.elements.chatMessages.appendChild(indicatorDiv);
        this.scrollToBottom();
    }

    // Masquer l'indicateur de traitement
    hideChatGPTIndicator() {
        const indicator = document.getElementById('ai-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    // Ajouter un message intelligent avec animation spéciale
    addChatGPTMessage(content) {
        if (!this.elements.chatMessages) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = 'message bot-message ai-message';
        
        messageDiv.innerHTML = `
            <div class="message-avatar">
                <div class="avatar-gradient">🏨</div>
            </div>
            <div class="message-content">
                <div class="message-text">${this.formatMessage(content)}</div>
                <div class="message-time">${this.getCurrentTime()}</div>
                <div class="ai-badge">Assistant Hôtelier</div>
            </div>
        `;

        // Animation d'apparition
        messageDiv.style.opacity = '0';
        messageDiv.style.transform = 'translateY(20px)';
        
        this.elements.chatMessages.appendChild(messageDiv);
        
        // Effet de typing pour le contenu
        this.typeWriterEffect(messageDiv.querySelector('.message-text'), content);
        
        // Animation d'entrée
        setTimeout(() => {
            messageDiv.style.transition = 'all 0.3s ease';
            messageDiv.style.opacity = '1';
            messageDiv.style.transform = 'translateY(0)';
        }, 100);

        this.scrollToBottom();
    }

    // Effet machine à écrire pour les réponses intelligentes
    typeWriterEffect(element, text) {
        element.innerHTML = '';
        let index = 0;
        
        const typeInterval = setInterval(() => {
            if (index < text.length) {
                element.innerHTML += text.charAt(index);
                index++;
                this.scrollToBottom();
            } else {
                clearInterval(typeInterval);
            }
        }, 30); // Vitesse de frappe
    }

    // Gestion des erreurs
    handleError(message, error) {
        console.error(message, error);
        
        // Jouer le son d'erreur
        this.playNotificationSound('error');
        
        if (this.elements.chatMessages) {
            const errorMessage = `Désolé ${this.state.userName || 'Client'}, ${message.toLowerCase()}. \n\n` +
                               `📞 Vous pouvez :\n` +
                               `• Réessayer dans quelques instants\n` +
                               `• Contacter notre réception au 01 23 45 67 89\n` +
                               `• Nous écrire à contact@hotel-excellence.com`;
            this.addMessage('bot', errorMessage);
        }
        
        this.trackEvent('error_occurred', { message, error: error.message });
    }

    // Affichage du loader
    showLoader(button) {
        if (!button) return;
        
        button.disabled = true;
        button.style.opacity = '0.7';
        button.innerHTML = '<span>Connexion...</span>';
    }

    // Masquage du loader
    hideLoader(button) {
        if (!button) return;
        
        button.disabled = false;
        button.style.opacity = '1';
        button.innerHTML = '<span class="btn-text">Commencer la conversation</span>';
    }

    // Configuration de l'auto-ouverture
    setupAutoOpen() {
        if (!this.config.autoOpen) {
            return;
        }
        
        // Délai d'auto-ouverture (par défaut 10 secondes)
        const autoOpenDelay = 10000;
        
        console.log('Auto-ouverture programmée dans', autoOpenDelay / 1000, 'secondes');
        
        setTimeout(() => {
            // Vérifier que le chatbot n'est pas déjà ouvert
            if (!this.elements.container?.classList.contains('visible')) {
                console.log('Auto-ouverture du chatbot');
                this.openChatbot();
                this.preloadResponses();
                this.updateAvatars();
                this.trackEvent('chatbot_initialized');
            }
        }, autoOpenDelay);
    }

    // Mise à jour des avatars avec l'image personnalisée
    updateAvatars() {
        if (!this.config.avatarUrl) {
            return; // Pas d'avatar personnalisé, garder les SVG par défaut
        }
        
        console.log('Mise à jour des avatars avec l\'image personnalisée:', this.config.avatarUrl);
        
        // Mettre à jour l'avatar du bouton flottant
        const floatingAvatar = document.querySelector('#chatbot-floating-btn .avatar-image');
        if (floatingAvatar && floatingAvatar.tagName === 'svg') {
            const img = document.createElement('img');
            img.src = this.config.avatarUrl;
            img.alt = 'Avatar du chatbot';
            img.className = 'avatar-image';
            img.style.cssText = 'width: 100%; height: 100%; border-radius: 50%; object-fit: cover;';
            
            floatingAvatar.parentNode.replaceChild(img, floatingAvatar);
        }
        
        // Mettre à jour l'avatar du message de bienvenue
        const welcomeAvatar = document.querySelector('.welcome-message .avatar');
        if (welcomeAvatar && welcomeAvatar.tagName === 'svg') {
            const img = document.createElement('img');
            img.src = this.config.avatarUrl;
            img.alt = 'Avatar du chatbot';
            img.className = 'avatar';
            img.style.cssText = 'width: 48px; height: 48px; border-radius: 50%; object-fit: cover;';
            
            welcomeAvatar.parentNode.replaceChild(img, welcomeAvatar);
        }
        
        // Mettre à jour tous les avatars existants dans les messages
        const messageAvatars = document.querySelectorAll('.message-avatar svg');
        messageAvatars.forEach(avatar => {
            const img = document.createElement('img');
            img.src = this.config.avatarUrl;
            img.alt = 'Avatar du chatbot';
            img.className = 'message-avatar-img';
            img.style.cssText = 'width: 32px; height: 32px; border-radius: 50%; object-fit: cover;';
            
            avatar.parentNode.replaceChild(img, avatar);
        });
    }

    // Préchargement des réponses
    preloadResponses() {
        // Précharger les réponses automatiques pour améliorer les performances
        this.config.autoResponses;
    }

    // Suivi des événements (analytics)
    trackEvent(eventName, data = {}) {
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, {
                event_category: 'hotel_chatbot',
                ...data
            });
        }
        
        console.log(`[Chatbot Event] ${eventName}:`, data);
    }
    
    // Système de sons de notification
    playNotificationSound(type = 'message') {
        // Vérifier si les sons sont activés dans les paramètres
        if (!this.config.enableSound) {
            return;
        }
        
        try {
            // Créer un son basique avec Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            // Configuration selon le type de son
            switch (type) {
                case 'message': // Son pour nouveau message
                    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                    oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);
                    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.3);
                    break;
                    
                case 'open': // Son pour ouverture du chat
                    oscillator.frequency.setValueAtTime(600, audioContext.currentTime);
                    oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.1);
                    oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.2);
                    gainNode.gain.setValueAtTime(0.2, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.4);
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.4);
                    break;
                    
                case 'error': // Son pour erreur
                    oscillator.frequency.setValueAtTime(300, audioContext.currentTime);
                    oscillator.frequency.setValueAtTime(200, audioContext.currentTime + 0.1);
                    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.5);
                    break;
            }
            
        } catch (error) {
            console.log('Son non disponible:', error);
        }
    }

    // === SYSTÈME DE PERSISTANCE DE SESSION ===
    
    // Sauvegarder l'état de la session dans localStorage
    saveSession() {
        try {
            const sessionData = {
                userEmail: this.state.userEmail,
                userName: this.state.userName,
                conversationId: this.state.conversationId,
                messageCount: this.state.messageCount,
                language: this.state.language,
                lastMessageTime: this.state.lastMessageTime,
                isInConversation: this.state.conversationId !== null,
                messages: this.getMessagesFromDOM(),
                timestamp: Date.now()
            };
            
            // Utiliser l'email comme clé unique pour la session
            const sessionKey = `hotelChatbot_session_${this.state.userEmail}`;
            localStorage.setItem(sessionKey, JSON.stringify(sessionData));
            console.log('Session sauvegardée pour:', this.state.userEmail, sessionData);
        } catch (error) {
            console.error('Erreur lors de la sauvegarde de session:', error);
        }
    }
    
    // Restaurer l'état de la session depuis localStorage
    restoreSession() {
        try {
            // Chercher toutes les sessions email existantes
            const emailSessions = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('hotelChatbot_session_')) {
                    const email = key.replace('hotelChatbot_session_', '');
                    const sessionData = JSON.parse(localStorage.getItem(key));
                    
                    // Vérifier si la session n'est pas expirée
                    const maxAge = 24 * 60 * 60 * 1000; // 24 heures
                    if (Date.now() - sessionData.timestamp <= maxAge) {
                        emailSessions.push({ email, sessionData, key });
                    } else {
                        // Supprimer les sessions expirées
                        localStorage.removeItem(key);
                    }
                }
            }
            
            if (emailSessions.length === 0) {
                console.log('Aucune session email valide trouvée');
                return;
            }
            
            // Prendre la session la plus récente
            const mostRecentSession = emailSessions.sort((a, b) => b.sessionData.timestamp - a.sessionData.timestamp)[0];
            const { email, sessionData } = mostRecentSession;
            
            // Restaurer l'état si une conversation était en cours
            if (sessionData.isInConversation && sessionData.userEmail) {
                console.log('Restauration de la session pour:', email, sessionData);
                
                // Restaurer l'état
                this.state.userEmail = sessionData.userEmail;
                this.state.userName = sessionData.userName || 'Client';
                this.state.conversationId = sessionData.conversationId;
                this.state.messageCount = sessionData.messageCount || 0;
                this.state.language = sessionData.language || 'fr';
                this.state.lastMessageTime = sessionData.lastMessageTime ? new Date(sessionData.lastMessageTime) : null;
                
                // Pré-remplir l'email et le nom si disponibles
                if (this.elements.userEmailInput) {
                    this.elements.userEmailInput.value = sessionData.userEmail;
                }
                if (this.elements.userNameInput && sessionData.userName) {
                    this.elements.userNameInput.value = sessionData.userName;
                }
                
                // Passer directement à l'interface de chat
                this.transitionToChat();
                
                // Restaurer les messages
                if (sessionData.messages && sessionData.messages.length > 0) {
                    this.restoreMessages(sessionData.messages);
                } else {
                    // Si pas de messages sauvegardés, charger depuis le serveur
                    this.loadExistingMessages(sessionData.conversationId);
                }
                
                console.log('Session restaurée avec succès pour:', email);
            }
            
        } catch (error) {
            console.error('Erreur lors de la restauration de session:', error);
            this.clearSession();
        }
    }
    
    // Récupérer les messages depuis le DOM
    getMessagesFromDOM() {
        const messages = [];
        const messageElements = this.elements.chatMessages?.querySelectorAll('.message');
        
        if (messageElements) {
            messageElements.forEach(element => {
                const isUser = element.classList.contains('user');
                const textElement = element.querySelector('.message-text');
                const timeElement = element.querySelector('.message-time');
                
                if (textElement) {
                    messages.push({
                        sender: isUser ? 'user' : 'bot',
                        content: textElement.textContent,
                        time: timeElement ? timeElement.textContent : '',
                        timestamp: Date.now()
                    });
                }
            });
        }
        
        return messages;
    }
    
    // Restaurer les messages dans le DOM
    restoreMessages(messages) {
        if (!this.elements.chatMessages || !messages.length) return;
        
        // Vider les messages existants
        this.elements.chatMessages.innerHTML = '';
        
        // Restaurer chaque message avec un délai pour l'animation
        messages.forEach((message, index) => {
            setTimeout(() => {
                this.addMessage(message.sender, message.content, false);
            }, index * 100); // 100ms entre chaque message
        });
    }
    
    // Effacer la session sauvegardée
    clearSession() {
        try {
            localStorage.removeItem('hotelChatbot_session');
            console.log('Session effacée');
        } catch (error) {
            console.error('Erreur lors de l\'effacement de session:', error);
        }
    }
    
    // Mettre à jour la session à chaque action importante
    updateSession() {
        if (this.state.conversationId) {
            this.saveSession();
        }
    }
    
    // === GESTION DES CONVERSATIONS BASÉES SUR L'EMAIL ===
    
    // Vérifier s'il existe une conversation active pour cet email
    async checkExistingConversation(email) {
        try {
            const response = await fetch(hotelChatbotAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hotel_chatbot_check_existing_conversation',
                    nonce: hotelChatbotAjax.nonce,
                    email: email
                })
            });
            
            const data = await response.json();
            
            if (data.success && data.data.conversation) {
                return data.data.conversation;
            }
            
            return null;
        } catch (error) {
            console.error('Erreur lors de la vérification de conversation existante:', error);
            return null;
        }
    }
    
    // Charger les messages existants d'une conversation
    async loadExistingMessages(conversationId) {
        try {
            const response = await fetch(hotelChatbotAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hotel_chatbot_get_conversation_messages',
                    nonce: hotelChatbotAjax.nonce,
                    conversation_id: conversationId
                })
            });
            
            const data = await response.json();
            
            if (data.success && data.data.messages) {
                // Vider les messages existants
                if (this.elements.chatMessages) {
                    this.elements.chatMessages.innerHTML = '';
                }
                
                // Restaurer chaque message avec animation
                data.data.messages.forEach((message, index) => {
                    setTimeout(() => {
                        this.addMessage(message.sender, message.message, false);
                    }, index * 100);
                });
                
                // Mettre à jour le compteur de messages
                this.state.messageCount = data.data.messages.length;
                
                console.log(`${data.data.messages.length} messages restaurés pour la conversation ${conversationId}`);
            }
        } catch (error) {
            console.error('Erreur lors du chargement des messages:', error);
            // En cas d'erreur, afficher le message de bienvenue
            setTimeout(() => {
                this.showWelcomeMessage();
            }, 300);
        }
    }
    
    // === MÉTHODES AUXILIAIRES POUR FIN DE CONVERSATION ===
    
    // Marquer la conversation comme terminée côté serveur
    async markConversationAsEnded() {
        try {
            const response = await fetch(hotelChatbotAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hotel_chatbot_end_conversation',
                    nonce: hotelChatbotAjax.nonce,
                    conversation_id: this.state.conversationId
                })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data || 'Erreur lors de la fin de conversation');
            }
            
            console.log('Conversation marquée comme terminée côté serveur');
        } catch (error) {
            console.error('Erreur lors du marquage de fin de conversation:', error);
            throw error;
        }
    }
    
    // Mettre à jour le nom du client dans la base de données
    async updateClientName(conversationId, newName) {
        try {
            const response = await fetch(hotelChatbotAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hotel_chatbot_update_client_name',
                    nonce: hotelChatbotAjax.nonce,
                    conversation_id: conversationId,
                    client_name: newName
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('Nom du client mis à jour:', newName);
            } else {
                console.error('Erreur lors de la mise à jour du nom:', data.data);
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour du nom du client:', error);
        }
    }
    
    // Supprimer la session locale actuelle
    clearCurrentSession() {
        if (this.state.userEmail) {
            const sessionKey = `hotelChatbot_session_${this.state.userEmail}`;
            localStorage.removeItem(sessionKey);
            console.log('Session locale supprimée pour:', this.state.userEmail);
        }
    }
    
    // Réinitialiser l'état de la conversation
    resetConversationState() {
        this.state.userName = '';
        this.state.userEmail = '';
        this.state.conversationId = null;
        this.state.messageCount = 0;
        this.state.lastMessageTime = null;
        this.state.isTyping = false;
        
        // Vider les champs du formulaire
        if (this.elements.userEmailInput) {
            this.elements.userEmailInput.value = '';
        }
        if (this.elements.userNameInput) {
            this.elements.userNameInput.value = '';
        }
        
        // Vider les messages
        if (this.elements.chatMessages) {
            this.elements.chatMessages.innerHTML = '';
        }
        
        console.log('État de conversation réinitialisé');
    }
    
    // Terminer la conversation
    endConversation() {
        // Nettoyer les cookies de session
        if (this.cookieManager) {
            this.cookieManager.clearSession();
        }
        
        // Réinitialiser l'état
        this.resetConversationState();
        
        // Retourner au formulaire de consentement
        this.returnToConsentForm();
        
        // Afficher le message de confirmation
        this.showEndConversationMessage();
        
        this.trackEvent('conversation_ended', {
            sessionId: this.state.sessionId,
            messageCount: this.state.messageCount
        });
        
        console.log('Conversation terminée et session nettoyée');
    }

    // Retourner au formulaire de consentement
    returnToConsentForm() {
        if (!this.elements.consentForm || !this.elements.chatInterface) return;
        
        // Masquer le bouton "Terminer la conversation"
        if (this.elements.endConversationBtn) {
            this.elements.endConversationBtn.style.display = 'none';
        }
        
        // Transition avec animation
        this.elements.chatInterface.style.display = 'none';
        this.elements.consentForm.style.display = 'flex';
        
        // Réinitialiser la validation du formulaire
        this.validateForm();
        
        console.log('Retour au formulaire de consentement');
    }
    
    // Afficher un message de confirmation de fin de conversation
    showEndConversationMessage() {
        // Créer une notification temporaire
        const notification = document.createElement('div');
        notification.className = 'end-conversation-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-icon">✓</div>
                <div class="notification-text">
                    <strong>Conversation terminée</strong><br>
                    <small>Vous pouvez commencer une nouvelle conversation quand vous le souhaitez.</small>
                </div>
            </div>
        `;
        
        // Ajouter les styles inline pour la notification
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            z-index: 10001;
            animation: slideInRight 0.3s ease-out;
            max-width: 300px;
        `;
        
        // Ajouter au DOM
        document.body.appendChild(notification);
        
        // Supprimer après 4 secondes
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }

    // ===== GESTION DES COOKIES ET PERSISTANCE =====

    /**
     * Restaurer une session existante depuis les cookies
     */
    restoreSession() {
        console.log('🍪 Tentative de restauration de session...');
        
        if (!this.cookieManager) {
            console.log('🍪 Cookie manager non disponible');
            return;
        }

        const sessionData = this.cookieManager.getSessionData();
        if (!sessionData) {
            console.log('🍪 Aucune session à restaurer');
            return;
        }

        console.log('🍪 Données de session trouvées:', sessionData);

        // Restaurer les données utilisateur
        if (sessionData.clientName) {
            this.state.userName = sessionData.clientName;
            if (this.elements.userNameInput) {
                this.elements.userNameInput.value = sessionData.clientName;
            }
        }

        if (sessionData.clientEmail) {
            this.state.userEmail = sessionData.clientEmail;
            if (this.elements.userEmailInput) {
                this.elements.userEmailInput.value = sessionData.clientEmail;
            }
        }

        if (sessionData.conversationId) {
            this.state.conversationId = sessionData.conversationId;
        }

        if (sessionData.sessionId) {
            this.state.sessionId = sessionData.sessionId;
        }

        // Si nous avons des données utilisateur valides, restaurer la session
        if (sessionData.clientName && sessionData.clientEmail) {
            console.log('🍪 Restauration de session avec utilisateur valide');
            
            // Restaurer les messages s'ils existent
            if (sessionData.messages && sessionData.messages.length > 0) {
                this.restoreMessages(sessionData.messages);
            }
            
            // Faire la transition vers l'interface de chat même sans messages
            this.transitionToChat();
            
            // Si pas de messages, afficher le message de bienvenue
            if (!sessionData.messages || sessionData.messages.length === 0) {
                setTimeout(() => {
                    this.showWelcomeMessage();
                }, 1000);
            }
        }

        this.trackEvent('session_restored', {
            sessionId: sessionData.sessionId,
            messageCount: sessionData.messages ? sessionData.messages.length : 0
        });
    }

    /**
     * Restaurer les messages dans l'interface
     */
    restoreMessages(messages) {
        if (!this.elements.chatMessages) return;

        // Vider les messages existants
        this.elements.chatMessages.innerHTML = '';

        messages.forEach(message => {
            this.displayMessage(message.content, message.sender, false);
        });

        // Faire défiler vers le bas
        setTimeout(() => {
            this.scrollToBottom();
        }, 100);
    }

    /**
     * Sauvegarder les données de session
     */
    saveSessionData() {
        console.log('🍪 Tentative de sauvegarde de session...');
        
        if (!this.cookieManager || !this.config.enableCookies) {
            console.log('🍪 Sauvegarde impossible - Cookie manager ou cookies désactivés');
            return;
        }

        // Générer un session_id si nécessaire
        if (!this.state.sessionId) {
            this.state.sessionId = this.cookieManager.generateSessionId();
        }

        const sessionData = {
            sessionId: this.state.sessionId,
            clientName: this.state.userName,
            clientEmail: this.state.userEmail,
            conversationId: this.state.conversationId,
            messages: this.getMessagesForStorage(),
            language: this.state.language
        };

        console.log('🍪 Données à sauvegarder:', sessionData);
        this.cookieManager.saveSessionData(sessionData);
        console.log('🍪 Session sauvegardée avec succès');
    }

    /**
     * Sauvegarder l'historique des conversations
     */
    saveConversationHistory() {
        if (!this.cookieManager || !this.config.enableCookies) {
            return;
        }

        const messages = this.getMessagesForStorage();
        this.cookieManager.saveConversationHistory(messages);
    }

    /**
     * Récupérer les messages pour le stockage
     */
    getMessagesForStorage() {
        if (!this.elements.chatMessages) {
            console.log('🍪 Pas d\'élément chatMessages pour récupérer les messages');
            return [];
        }

        // Essayer différents sélecteurs pour trouver les messages
        let messageElements = this.elements.chatMessages.querySelectorAll('.message');
        
        if (messageElements.length === 0) {
            // Essayer d'autres sélecteurs possibles
            messageElements = this.elements.chatMessages.querySelectorAll('.bot-message, .user-message');
        }
        
        if (messageElements.length === 0) {
            messageElements = this.elements.chatMessages.querySelectorAll('[class*="message"]');
        }

        console.log(`🍪 ${messageElements.length} messages trouvés pour sauvegarde`);
        
        const messages = [];

        messageElements.forEach((element, index) => {
            // Déterminer si c'est un message utilisateur ou bot
            const isUser = element.classList.contains('user-message') || 
                          element.classList.contains('user') ||
                          element.querySelector('.user') !== null;
            
            // Essayer différents sélecteurs pour le contenu
            let content = element.querySelector('.message-content');
            if (!content) {
                content = element.querySelector('.message-text');
            }
            if (!content) {
                content = element; // Utiliser l'élément lui-même
            }
            
            if (content && content.textContent.trim()) {
                const messageData = {
                    content: content.textContent.trim(),
                    sender: isUser ? 'user' : 'bot',
                    timestamp: Date.now() - (messageElements.length - index) * 1000 // Timestamps décroissants
                };
                messages.push(messageData);
                console.log(`🍪 Message ${index + 1}:`, messageData);
            }
        });

        console.log(`🍪 Total messages sauvegardés: ${messages.length}`);
        return messages;
    }

    /**
     * Nettoyer la session (déconnexion)
     */
    clearSession() {
        if (this.cookieManager) {
            this.cookieManager.clearSession();
        }

        // Réinitialiser l'état
        this.state.userName = '';
        this.state.userEmail = '';
        this.state.conversationId = null;
        this.state.sessionId = null;

        // Vider les champs
        if (this.elements.userNameInput) this.elements.userNameInput.value = '';
        if (this.elements.userEmailInput) this.elements.userEmailInput.value = '';
        if (this.elements.chatMessages) this.elements.chatMessages.innerHTML = '';

        console.log('🍪 Session nettoyée');
    }
}

// Fonctionnalité de navigation pour le carousel de suggestions
class SuggestionsCarousel {
    constructor() {
        this.currentIndex = 0;
        this.itemsPerView = 3; // Nombre de suggestions visibles à la fois
        this.init();
    }
    
    init() {
        this.container = document.getElementById('secondary-suggestions');
        this.leftBtn = document.getElementById('suggestions-nav-left');
        this.rightBtn = document.getElementById('suggestions-nav-right');
        
        if (!this.container || !this.leftBtn || !this.rightBtn) {
            return; // Éléments non trouvés, sortir
        }
        
        this.items = this.container.children;
        this.totalItems = this.items.length;
        this.maxIndex = Math.max(0, this.totalItems - this.itemsPerView);
        
        this.setupEventListeners();
        this.updateNavigation();
        this.updateItemsPerView();
        
        // Mise à jour responsive
        window.addEventListener('resize', () => this.updateItemsPerView());
    }
    
    setupEventListeners() {
        this.leftBtn.addEventListener('click', () => this.navigateLeft());
        this.rightBtn.addEventListener('click', () => this.navigateRight());
        
        // Support du clavier
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' && this.isCarouselFocused()) {
                e.preventDefault();
                this.navigateLeft();
            } else if (e.key === 'ArrowRight' && this.isCarouselFocused()) {
                e.preventDefault();
                this.navigateRight();
            }
        });
    }
    
    updateItemsPerView() {
        const containerWidth = this.container.parentElement.offsetWidth;
        if (containerWidth < 480) {
            this.itemsPerView = 2;
        } else if (containerWidth < 768) {
            this.itemsPerView = 3;
        } else {
            this.itemsPerView = 4;
        }
        
        this.maxIndex = Math.max(0, this.totalItems - this.itemsPerView);
        this.currentIndex = Math.min(this.currentIndex, this.maxIndex);
        this.updateCarousel();
    }
    
    navigateLeft() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.updateCarousel();
        }
    }
    
    navigateRight() {
        if (this.currentIndex < this.maxIndex) {
            this.currentIndex++;
            this.updateCarousel();
        }
    }
    
    updateCarousel() {
        const itemWidth = this.items[0] ? this.items[0].offsetWidth + 8 : 128; // 8px = gap
        const translateX = -(this.currentIndex * itemWidth);
        
        this.container.style.transform = `translateX(${translateX}px)`;
        this.updateNavigation();
    }
    
    updateNavigation() {
        // Désactiver le bouton gauche si on est au début
        this.leftBtn.disabled = this.currentIndex === 0;
        
        // Désactiver le bouton droit si on est à la fin
        this.rightBtn.disabled = this.currentIndex >= this.maxIndex;
        
        // Ajouter des classes pour le style
        this.leftBtn.classList.toggle('disabled', this.currentIndex === 0);
        this.rightBtn.classList.toggle('disabled', this.currentIndex >= this.maxIndex);
    }
    
    isCarouselFocused() {
        const activeElement = document.activeElement;
        return activeElement && (
            activeElement === this.leftBtn ||
            activeElement === this.rightBtn ||
            activeElement.closest('.suggestions-carousel')
        );
    }
    
    // Méthode pour ajouter de nouvelles suggestions dynamiquement
    addSuggestion(icon, text, question, category) {
        const button = document.createElement('button');
        button.className = 'suggestion-btn';
        button.setAttribute('data-question', question);
        button.setAttribute('data-category', category);
        button.innerHTML = `
            <span class="suggestion-icon">${icon}</span>
            <span class="suggestion-text">${text}</span>
        `;
        
        this.container.appendChild(button);
        this.totalItems = this.container.children.length;
        this.maxIndex = Math.max(0, this.totalItems - this.itemsPerView);
        this.updateNavigation();
        
        return button;
    }
    
    // Méthode pour réinitialiser la position
    reset() {
        this.currentIndex = 0;
        this.updateCarousel();
    }
}

// Initialisation automatique du chatbot
document.addEventListener('DOMContentLoaded', function() {
    window.hotelChatbot = new HotelChatbotClient();
    
    // Initialiser le carousel de suggestions après un délai pour s'assurer que le DOM est prêt
    setTimeout(() => {
        window.suggestionsCarousel = new SuggestionsCarousel();
    }, 500);
});
