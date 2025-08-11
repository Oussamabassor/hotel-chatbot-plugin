/**
 * Hotel Chatbot Client - H-Hotels.com Style
 * Chatbot avec workflow de consentement RGPD et interface moderne
 */

// Initialisation du chatbot H-Hotels style
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si les éléments existent
    const floatingBtn = document.getElementById('chatbot-floating-btn');
    const overlay = document.getElementById('chatbot-overlay');
    const container = document.getElementById('chatbot-container');
    const closeBtn = document.getElementById('chatbot-close');
    const userNameInput = document.getElementById('user-name');
    const startBtn = document.getElementById('start-conversation');
    const consentForm = document.getElementById('consent-form');
    const chatInterface = document.getElementById('chat-interface');
    
    if (!floatingBtn || !overlay || !container) {
        console.log('Chatbot elements not found, skipping initialization');
        return;
    }
    
    let userName = '';
    let conversationId = null;
    
    // Ouvrir le chatbot
    floatingBtn.addEventListener('click', function() {
        overlay.classList.add('visible');
        container.classList.add('visible', 'chat-opening');
        setTimeout(() => container.classList.remove('chat-opening'), 400);
    });
    
    // Fermer le chatbot
    function closeChatbot() {
        container.classList.add('chat-closing');
        setTimeout(() => {
            overlay.classList.remove('visible');
            container.classList.remove('visible', 'chat-closing');
        }, 300);
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeChatbot);
    }
    
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeChatbot();
    });
    
    // Validation du nom
    if (userNameInput && startBtn) {
        userNameInput.addEventListener('input', function() {
            const isValid = this.value.trim().length >= 2;
            startBtn.disabled = !isValid;
            startBtn.classList.toggle('enabled', isValid);
        });
    }
    
    // Démarrer la conversation
    if (startBtn) {
        startBtn.addEventListener('click', function() {
            userName = userNameInput.value.trim();
            if (userName && consentForm && chatInterface) {
                consentForm.style.display = 'none';
                chatInterface.style.display = 'block';
                
                // Message de bienvenue personnalisé H-Hotels style
                addMessage('bot', `Bonjour ${userName.split(' ')[0]} !<br><br>Ici Velma, votre assistante virtuelle.<br><br>Posez-moi une question et je vous répondrai avec plaisir.`, true);
                
                // Créer une nouvelle conversation via AJAX
                createConversation(userName);
            }
        });
    }
    
    // Fonction pour créer une conversation
    function createConversation(clientName) {
        if (typeof hotelChatbotAjax !== 'undefined') {
            fetch(hotelChatbotAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hotel_chatbot_message',
                    message: 'Démarrage de conversation',
                    client_name: clientName,
                    language: 'fr',
                    nonce: hotelChatbotAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.conversation_id) {
                    conversationId = data.data.conversation_id;
                }
            })
            .catch(error => console.log('Erreur création conversation:', error));
        }
    }
    
    // Fonction pour ajouter un message
    function addMessage(sender, content, isWelcome = false) {
        const messagesContainer = document.getElementById('chat-messages');
        if (!messagesContainer) return;
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${sender}-message${isWelcome ? ' welcome-chat' : ''}`;
        
        if (sender === 'bot') {
            messageDiv.innerHTML = `
                <div class="message-avatar">
                    <img src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAYEBQYFBAYGBQYHBwYIChAKCgkJChQODwwQFxQYGBcUFhYaHSUfGhsjHBYWICwgIyYnKSopGR8tMC0oMCUoKSj/2wBDAQcHBwoIChMKChMoGhYaKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCgoKCj/wAARCAAoACgDASIAAhEBAxEB/8QAGwAAAQUBAQAAAAAAAAAAAAAABgIDBAUHAQj/xAAsEAACAQMDAgUEAwEAAAAAAAABAgMABBEFEiExQQYTUWFxByKBkRQyocH/xAAYAQADAQEAAAAAAAAAAAAAAAACAwQBBf/EACERAAICAgICAwEAAAAAAAAAAAABAhEDIRIxE0EiUWFx/9oADAMBAAIRAxEAPwDcaKKKACiiigAooooAKKKKACiiigD/2Q==" alt="Velma" />
                </div>
                <div class="message-content">
                    <div class="message-text">${content}</div>
                    <div class="message-time">Il y a quelques secondes</div>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="message-content">
                    <div class="message-text">${content}</div>
                    <div class="message-time">Maintenant</div>
                </div>
            `;
        }
        
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Fonction pour envoyer un message via AJAX
    function sendMessageToServer(message) {
        if (typeof hotelChatbotAjax !== 'undefined' && conversationId) {
            fetch(hotelChatbotAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'hotel_chatbot_message',
                    message: message,
                    client_name: userName,
                    conversation_id: conversationId,
                    language: 'fr',
                    nonce: hotelChatbotAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.response) {
                    setTimeout(() => {
                        addMessage('bot', data.data.response);
                    }, 1000);
                } else {
                    setTimeout(() => {
                        addMessage('bot', `Merci ${userName.split(' ')[0]} pour votre message. Un de nos conseillers va vous répondre dans quelques instants.`);
                    }, 1500);
                }
            })
            .catch(error => {
                console.log('Erreur envoi message:', error);
                setTimeout(() => {
                    addMessage('bot', `Merci ${userName.split(' ')[0]} pour votre message. Un de nos conseillers va vous répondre dans quelques instants.`);
                }, 1500);
            });
        }
    }
    
    // Boutons d'action rapide
    const voirPrixBtn = document.getElementById('voir-prix');
    const poserQuestionBtn = document.getElementById('poser-question');
    
    if (voirPrixBtn) {
        voirPrixBtn.addEventListener('click', function() {
            addMessage('user', 'Je souhaiterais voir les prix');
            sendMessageToServer('Je souhaiterais voir les prix');
        });
    }
    
    if (poserQuestionBtn) {
        poserQuestionBtn.addEventListener('click', function() {
            const chatInput = document.getElementById('chat-input');
            if (chatInput) chatInput.focus();
        });
    }
    
    // Envoi de message
    const chatInput = document.getElementById('chat-input');
    const sendBtn = document.getElementById('send-message');
    
    function sendMessage() {
        if (!chatInput) return;
        
        const message = chatInput.value.trim();
        if (message) {
            addMessage('user', message);
            chatInput.value = '';
            
            // Envoyer au serveur
            sendMessageToServer(message);
        }
    }
    
    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }
    
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });
    }
    
    // Auto-ouverture si configuré
    if (typeof hotelChatbotAjax !== 'undefined' && hotelChatbotAjax.autoOpen === '1') {
        setTimeout(() => {
            floatingBtn.click();
        }, 10000);
    }
});
