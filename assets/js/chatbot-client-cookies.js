/**
 * Hotel Chatbot Cookie Manager
 * Gestion complète des cookies pour la persistance des conversations
 */

class HotelChatbotCookieManager {
    constructor(options = {}) {
        this.cookieName = 'hotel_chatbot_session';
        this.expirationDays = options.expirationDays || 30;
        this.maxMessages = options.maxMessages || 50;
        this.enabled = options.enabled !== false;
        
        console.log('🍪 Cookie Manager initialisé:', {
            enabled: this.enabled,
            expirationDays: this.expirationDays,
            maxMessages: this.maxMessages
        });
    }

    /**
     * Générer un ID de session unique
     */
    generateSessionId() {
        return 'hc_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Sauvegarder les données de session
     */
    saveSessionData(sessionData) {
        if (!this.enabled) {
            console.log('🍪 Cookies désactivés, session non sauvegardée');
            return false;
        }

        try {
            // Limiter le nombre de messages pour éviter des cookies trop volumineux
            if (sessionData.messages && sessionData.messages.length > this.maxMessages) {
                sessionData.messages = sessionData.messages.slice(-this.maxMessages);
            }

            const cookieData = {
                sessionId: sessionData.sessionId,
                clientName: sessionData.clientName,
                clientEmail: sessionData.clientEmail,
                conversationId: sessionData.conversationId,
                messages: sessionData.messages || [],
                lastActivity: new Date().toISOString(),
                version: '1.0'
            };

            this.setCookie(this.cookieName, JSON.stringify(cookieData), this.expirationDays);
            console.log('🍪 Session sauvegardée:', cookieData.sessionId);
            return true;
        } catch (error) {
            console.error('🍪 Erreur lors de la sauvegarde de session:', error);
            return false;
        }
    }

    /**
     * Récupérer les données de session
     */
    getSessionData() {
        if (!this.enabled) {
            return null;
        }

        try {
            const cookieValue = this.getCookie(this.cookieName);
            if (!cookieValue) {
                return null;
            }

            const sessionData = JSON.parse(cookieValue);
            
            // Vérifier si la session n'est pas expirée
            if (this.isSessionExpired(sessionData)) {
                this.clearSession();
                return null;
            }

            console.log('🍪 Session récupérée:', sessionData.sessionId);
            return sessionData;
        } catch (error) {
            console.error('🍪 Erreur lors de la récupération de session:', error);
            this.clearSession();
            return null;
        }
    }

    /**
     * Sauvegarder l'historique des conversations
     */
    saveConversationHistory(messages) {
        if (!this.enabled) {
            return false;
        }

        const sessionData = this.getSessionData();
        if (sessionData) {
            sessionData.messages = messages.slice(-this.maxMessages);
            sessionData.lastActivity = new Date().toISOString();
            return this.saveSessionData(sessionData);
        }
        return false;
    }

    /**
     * Vérifier si la session est expirée
     */
    isSessionExpired(sessionData) {
        if (!sessionData.lastActivity) {
            return true;
        }

        const lastActivity = new Date(sessionData.lastActivity);
        const now = new Date();
        const diffDays = Math.floor((now - lastActivity) / (1000 * 60 * 60 * 24));

        return diffDays > this.expirationDays;
    }

    /**
     * Nettoyer la session
     */
    clearSession() {
        this.deleteCookie(this.cookieName);
        console.log('🍪 Session nettoyée');
    }

    /**
     * Nettoyer les sessions expirées
     */
    cleanupExpiredSessions() {
        const sessionData = this.getSessionData();
        if (sessionData && this.isSessionExpired(sessionData)) {
            this.clearSession();
            console.log('🍪 Session expirée nettoyée');
        }
    }

    /**
     * Créer un cookie
     */
    setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        
        document.cookie = `${name}=${encodeURIComponent(value)}; expires=${expires.toUTCString()}; path=/; SameSite=Lax`;
    }

    /**
     * Récupérer un cookie
     */
    getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') {
                c = c.substring(1, c.length);
            }
            if (c.indexOf(nameEQ) === 0) {
                return decodeURIComponent(c.substring(nameEQ.length, c.length));
            }
        }
        return null;
    }

    /**
     * Supprimer un cookie
     */
    deleteCookie(name) {
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
    }

    /**
     * Nettoyer complètement la session
     */
    clearSession() {
        this.deleteCookie(this.sessionCookieName);
        this.deleteCookie(this.conversationCookieName);
        console.log('🍪 Session et cookies nettoyés');
    }

    /**
     * Obtenir des statistiques sur les cookies
     */
    getStats() {
        const sessionData = this.getSessionData();
        if (!sessionData) {
            return {
                hasSession: false,
                messageCount: 0,
                lastActivity: null
            };
        }

        return {
            hasSession: true,
            sessionId: sessionData.sessionId,
            messageCount: sessionData.messages ? sessionData.messages.length : 0,
            lastActivity: sessionData.lastActivity,
            clientName: sessionData.clientName,
            conversationId: sessionData.conversationId
        };
    }

    /**
     * Vérifier si les cookies sont supportés
     */
    areCookiesSupported() {
        try {
            const testCookie = 'hotel_chatbot_test';
            this.setCookie(testCookie, 'test', 1);
            const supported = this.getCookie(testCookie) === 'test';
            this.deleteCookie(testCookie);
            return supported;
        } catch (error) {
            return false;
        }
    }
}

// Export pour utilisation dans d'autres scripts
if (typeof window !== 'undefined') {
    window.HotelChatbotCookieManager = HotelChatbotCookieManager;
}
