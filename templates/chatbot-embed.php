<?php
if ( ! function_exists( 'esc_attr' ) ) {
	require_once( dirname( __FILE__, 4 ) . '/wp-load.php' );
}
?>

<!-- Bouton flottant avec avatar professionnel -->
<div id="chatbot-floating-btn" class="chatbot-floating-btn" title="Besoin d'aide ? Cliquez pour discuter !">
    <div class="avatar-container">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="avatar-image">
            <circle cx="12" cy="12" r="10" fill="url(#avatarGradient)"></circle>
            <path d="M12 15C14.2091 15 16 16.7909 16 19H8C8 16.7909 9.79086 15 12 15Z" fill="#ffffff"></path>
            <circle cx="12" cy="10" r="3" fill="#ffffff"></circle>
        </svg>
        <defs>
            <linearGradient id="avatarGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#1d4ed8;stop-opacity:1" />
            </linearGradient>
        </defs>
    </div>
    <div class="status-indicator online"></div>
</div>

<!-- Container principal du chatbot -->
<div id="chatbot-container" class="chatbot-container">
    <!-- Header amélioré -->
    <div class="chatbot-header">
        <div class="header-info">
            <div class="hotel-branding">
                <span class="hotel-name">Hotel Chatbot</span>
                <span class="assistant-role">Assistant Réservations</span>
            </div>
            <div class="status-info">
                <div class="status-dot online"></div>
                <span class="response-time">En ligne • Répond immédiatement</span>
            </div>
        </div>
        <div class="header-actions">
            <button id="end-conversation" class="end-conversation-btn" title="Terminer la conversation" style="display: none;">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                </svg>
                <span class="btn-text">Terminer</span>
            </button>
            <button id="chatbot-close" class="close-btn" title="Fermer le chat">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M12.854 4.854a.5.5 0 0 0-.708-.708L8 8.293 3.854 4.146a.5.5 0 1 0-.708.708L7.293 9l-4.147 4.146a.5.5 0 0 0 .708.708L8 9.707l4.146 4.147a.5.5 0 0 0 .708-.708L8.707 9l4.147-4.146z"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Étape 1: Formulaire de consentement amélioré -->
    <div id="consent-form" class="consent-form">
        <div class="consent-content">
            <div class="welcome-message">
                <div class="avatar-container">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="avatar">
                        <circle cx="12" cy="12" r="10" fill="url(#welcomeGradient)"></circle>
                        <path d="M12 15C14.2091 15 16 16.7909 16 19H8C8 16.7909 9.79086 15 12 15Z" fill="#ffffff"></path>
                        <circle cx="12" cy="10" r="3" fill="#ffffff"></circle>
                    </svg>
                    <defs>
                        <linearGradient id="welcomeGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:1" />
                            <stop offset="100%" style="stop-color:#1d4ed8;stop-opacity:1" />
                        </linearGradient>
                    </defs>
                </div>
                <div class="message-content">
                    <h3>👋 Bonjour et bienvenue !</h3>
                    <p>Je suis votre assistant virtuel spécialisé dans les <strong>réservations hôtelières</strong>. Je suis là pour vous aider à trouver la chambre parfaite et répondre à toutes vos questions.</p>
                    <div class="features-preview">
                        <span class="feature">🏨 Disponibilités</span>
                        <span class="feature">💰 Tarifs</span>
                        <span class="feature">📅 Réservations</span>
                    </div>
                </div>
            </div>

            <div class="consent-section">
                <div class="privacy-notice">
                    <h4>🔒 Protection de vos données</h4>
                    <p>En démarrant cette conversation, vous acceptez notre <a href="#" class="privacy-link">politique de confidentialité</a> et le traitement de vos données personnelles pour vous offrir le meilleur service.</p>
                </div>

                <div class="form-group">
                    <label for="user-email">📧 Votre adresse email *</label>
                    <input type="email" id="user-email" name="user-email" placeholder="Ex: jean.dupont@email.com" required />
                    <small class="input-help">Votre email nous permet de retrouver vos conversations précédentes</small>
                </div>
                
                <div class="form-group">
                    <label for="user-name">👤 Votre prénom (optionnel)</label>
                    <input type="text" id="user-name" name="user-name" placeholder="Ex: Jean" />
                    <small class="input-help">Pour personnaliser nos échanges</small>
                </div>

                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="promotional-consent" name="promotional-consent" />
                        <span class="checkmark"></span>
                        <span class="checkbox-text">
                            📧 J'aimerais recevoir des offres spéciales et promotions par email
                            <small>(Optionnel - vous pouvez vous désabonner à tout moment)</small>
                        </span>
                    </label>
                </div>

                <div class="form-actions">
                    <button id="start-conversation" class="start-btn" disabled>
                        <span class="btn-text">Commencer la conversation</span>
                        <svg class="btn-icon" width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 12l-4-4h8l-4 4z"/>
                        </svg>
                    </button>
                </div>

                <div class="gdpr-footer">
                    <p>🛡️ <a href="#" class="gdpr-link">Gérer mes données personnelles</a> • Conforme RGPD</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Étape 2: Interface de chat améliorée -->
    <div id="chat-interface" class="chat-interface" style="display: none;">
        <!-- Zone des messages -->
        <div class="chat-messages" id="chat-messages">
            <!-- Les messages seront ajoutés ici dynamiquement -->
        </div>

        <!-- Suggestions intelligentes avec navigation -->
        <div class="smart-suggestions">
            <div class="suggestions-header">
                <span class="header-text">💡 Questions fréquentes</span>
                <small class="header-subtitle">Cliquez pour une réponse rapide</small>
            </div>
            
            <!-- Suggestions prioritaires -->
            <div class="priority-suggestions">
                <button class="suggestion-btn priority" data-question="Avez-vous des chambres disponibles pour ce soir ?" data-category="availability">
                    <span class="suggestion-icon">🏨</span>
                    <span class="suggestion-text">Disponibilités</span>
                </button>
                <button class="suggestion-btn priority" data-question="Quels sont vos tarifs actuels ?" data-category="pricing">
                    <span class="suggestion-icon">💰</span>
                    <span class="suggestion-text">Voir les tarifs</span>
                </button>
            </div>
            
            <!-- Suggestions secondaires avec navigation -->
            <div class="suggestions-carousel">
                <button class="nav-arrow nav-left" id="suggestions-nav-left" title="Suggestions précédentes">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/>
                    </svg>
                </button>
                
                <div class="suggestions-container">
                    <div class="secondary-suggestions" id="secondary-suggestions">
                        <button class="suggestion-btn" data-question="À quelle heure est le check-in et check-out ?" data-category="info">
                            <span class="suggestion-icon">⏰</span>
                            <span class="suggestion-text">Horaires</span>
                        </button>
                        <button class="suggestion-btn" data-question="Proposez-vous le petit-déjeuner ?" data-category="services">
                            <span class="suggestion-icon">🍳</span>
                            <span class="suggestion-text">Petit-déjeuner</span>
                        </button>
                        <button class="suggestion-btn" data-question="Y a-t-il un parking disponible ?" data-category="services">
                            <span class="suggestion-icon">🚗</span>
                            <span class="suggestion-text">Parking</span>
                        </button>
                        <button class="suggestion-btn" data-question="Acceptez-vous les animaux de compagnie ?" data-category="policies">
                            <span class="suggestion-icon">🐕</span>
                            <span class="suggestion-text">Animaux</span>
                        </button>
                        <button class="suggestion-btn" data-question="Avez-vous le WiFi gratuit ?" data-category="services">
                            <span class="suggestion-icon">📶</span>
                            <span class="suggestion-text">WiFi</span>
                        </button>
                        <button class="suggestion-btn" data-question="Comment puis-je modifier ou annuler ma réservation ?" data-category="booking">
                            <span class="suggestion-icon">📝</span>
                            <span class="suggestion-text">Gérer réservation</span>
                        </button>
                        <!-- Suggestions supplémentaires -->
                        <button class="suggestion-btn" data-question="Quels sont vos services de spa et bien-être ?" data-category="services">
                            <span class="suggestion-icon">🧘</span>
                            <span class="suggestion-text">Spa & Bien-être</span>
                        </button>
                        <button class="suggestion-btn" data-question="Avez-vous un service de navette aéroport ?" data-category="transport">
                            <span class="suggestion-icon">✈️</span>
                            <span class="suggestion-text">Navette aéroport</span>
                        </button>
                        <button class="suggestion-btn" data-question="Proposez-vous des activités touristiques ?" data-category="activities">
                            <span class="suggestion-icon">🎯</span>
                            <span class="suggestion-text">Activités</span>
                        </button>
                        <button class="suggestion-btn" data-question="Comment contacter la réception ?" data-category="contact">
                            <span class="suggestion-icon">📞</span>
                            <span class="suggestion-text">Contact</span>
                        </button>
                    </div>
                </div>
                
                <button class="nav-arrow nav-right" id="suggestions-nav-right" title="Suggestions suivantes">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Zone de saisie améliorée -->
        <div class="chat-input-area">
            <div class="input-container">
                <div class="typing-indicator" id="typing-indicator" style="display: none;">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <input type="text" id="chat-input" placeholder="Tapez votre question ici..." autocomplete="off" />
                <button id="send-message" class="send-btn" title="Envoyer le message">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                    </svg>
                </button>
            </div>
            <div class="input-help">
                <small>💬 Posez-moi toute question sur les réservations, tarifs, services...</small>
            </div>
        </div>
    </div>

    <!-- Footer professionnel -->
    <div class="chatbot-footer">
        <div class="footer-content">
            <span class="powered-by">Propulsé par <strong>Hotel Chatbot</strong></span>
            <div class="footer-links">
                <a href="#" class="footer-link">Aide</a>
                <a href="#" class="footer-link">Confidentialité</a>
            </div>
        </div>
    </div>
</div>
