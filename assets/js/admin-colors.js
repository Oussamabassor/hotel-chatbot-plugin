jQuery(document).ready(function($) {
    console.log('Hotel Chatbot Admin Colors - Script loaded');
    
    // Fonction utilitaire pour ajuster la luminosité d'une couleur hexadécimale
    function adjustBrightness(hex, steps) {
        steps = Math.max(-255, Math.min(255, steps));
        hex = hex.replace('#', '');
        // format court (#abc) => format long (#aabbcc)
        if (hex.length === 3) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        // convertir en decimal et appliquer l'ajustement
        let r = parseInt(hex.substr(0, 2), 16);
        let g = parseInt(hex.substr(2, 2), 16);
        let b = parseInt(hex.substr(4, 2), 16);

        r = Math.max(0, Math.min(255, r + steps));
        g = Math.max(0, Math.min(255, g + steps));
        b = Math.max(0, Math.min(255, b + steps));

        return '#' + ((r << 16) | (g << 8) | b).toString(16).padStart(6, '0');
    }
    
    // Fonction pour valider un code couleur hexadécimal
    function isValidHexColor(hex) {
        return /^#[0-9A-Fa-f]{6}$/.test(hex);
    }
    
    // Synchronisation entre color picker et champ de saisie
    $('.color-picker').on('change', function() {
        console.log('Color picker changed:', $(this).attr('name'), $(this).val());
        const colorValue = $(this).val();
        const fieldId = $(this).attr('id').replace('_picker', '_code');
        $('#' + fieldId).val(colorValue.toUpperCase());
        updatePreview();
    });

    // Synchronisation entre champ de saisie et color picker
    $('.color-code-field').on('input', function() {
        console.log('Color code field changed:', $(this).attr('name'), $(this).val());
        const colorValue = $(this).val();
        if (isValidHexColor(colorValue)) {
            const pickerId = $(this).attr('id').replace('_code', '_picker');
            $('#' + pickerId).val(colorValue);
            $(this).removeClass('invalid');
            updatePreview();
        } else {
            $(this).addClass('invalid');
        }
    });

    // Validation en temps réel du champ de saisie
    $('.color-code-field').on('blur', function() {
        const colorValue = $(this).val();
        if (!isValidHexColor(colorValue) && colorValue !== '') {
            // Restaurer la valeur précédente si invalide
            const pickerId = $(this).attr('id').replace('_code', '_picker');
            const validColor = $('#' + pickerId).val();
            $(this).val(validColor.toUpperCase());
            $(this).removeClass('invalid');
        }
    });

    // Fonction de copie du code couleur
    $('.color-copy-btn').on('click', function() {
        const codeField = $(this).siblings('.color-code-field');
        const colorCode = codeField.val();
        
        console.log('Copying color code:', colorCode);
        
        // Copier dans le presse-papiers
        navigator.clipboard.writeText(colorCode).then(() => {
            // Animation de succès
            $(this).addClass('copied');
            setTimeout(() => {
                $(this).removeClass('copied');
            }, 600);
        }).catch(() => {
            // Fallback pour les navigateurs plus anciens
            codeField.select();
            document.execCommand('copy');
            $(this).addClass('copied');
            setTimeout(() => {
                $(this).removeClass('copied');
            }, 600);
        });
    });
    
    // Initialiser les champs de code couleur au chargement
    $('.color-picker').each(function() {
        const colorValue = $(this).val();
        const fieldId = $(this).attr('id').replace('_picker', '_code');
        $('#' + fieldId).val(colorValue.toUpperCase());
    });
    
    console.log('Color pickers found:', $('.color-picker').length);
    console.log('Color code fields found:', $('.color-code-field').length);
    // Mise à jour de l'aperçu en temps réel
    function updatePreview() {
        // Récupérer les 7 couleurs essentielles uniquement
        const headerColor = $('input[name="hotel_chatbot_header_color"]').val();
        const floatingBtnColor = $('input[name="hotel_chatbot_floating_button_color"]').val();
        const sendBtnColor = $('input[name="hotel_chatbot_send_button_color"]').val();
        const userMsgColor = $('input[name="hotel_chatbot_user_message_color"]').val();
        const botMsgColor = $('input[name="hotel_chatbot_bot_message_color"]').val();
        const backgroundColor = $('input[name="hotel_chatbot_background_color"]').val();
        const textColor = $('input[name="hotel_chatbot_text_color"]').val();
        
        // Couleurs dérivées automatiquement
        const borderColor = adjustBrightness(backgroundColor, -20);
        const inputBgColor = backgroundColor;
        const inputTextColor = textColor;
        const inputBorderColor = adjustBrightness(backgroundColor, -30);
        const closeBtnColor = adjustBrightness(headerColor, -40);
        const linkColor = headerColor;
        const errorColor = '#ef4444';
        const successColor = sendBtnColor;
        
        // Mettre à jour l'aperçu avec les nouvelles couleurs
        $('.preview-header').css('background-color', headerColor);
        $('.preview-send-btn').css('background-color', sendBtnColor);
        $('.preview-user-message').css('background-color', userMsgColor);
        $('.preview-user-message').css('color', textColor);
        $('.preview-bot-message').css('background-color', botMsgColor);
        $('.preview-bot-message').css('color', textColor);
        $('.preview-messages').css('background-color', backgroundColor);
        $('.preview-input').css('background-color', backgroundColor);
        $('.mini-chatbot-preview').css('background-color', backgroundColor);
        
        // Appliquer les nouvelles couleurs aux bordures et suggestions
        $('.mini-chatbot-preview').css('border-color', borderColor);
        $('.preview-input-area input').css('border-color', inputBorderColor);
        $('.preview-header').css('border-bottom', '1px solid ' + borderColor);
        
        // Appliquer les nouvelles couleurs avancées
        $('.preview-input-area input').css({
            'background-color': inputBgColor,
            'color': inputTextColor,
            'border-color': inputBorderColor
        });
        
        $('.message-avatar').css('background-color', botMsgColor);
        $('.mini-chatbot-preview').css('box-shadow', '0 4px 20px ' + backgroundColor);
        
        // Simuler des liens dans l'aperçu
        $('.preview-message a, .message-content a').css('color', linkColor);
        
        // Mettre à jour les variables CSS pour l'aperçu (couleurs essentielles + dérivées)
        const previewContainer = $('.mini-chatbot-preview')[0];
        if (previewContainer) {
            previewContainer.style.setProperty('--header-color', headerColor);
            previewContainer.style.setProperty('--floating-btn-color', floatingBtnColor);
            previewContainer.style.setProperty('--send-button-color', sendBtnColor);
            previewContainer.style.setProperty('--user-message-color', userMsgColor);
            previewContainer.style.setProperty('--bot-message-color', botMsgColor);
            previewContainer.style.setProperty('--background-color', backgroundColor);
            previewContainer.style.setProperty('--text-color', textColor);
            
            // Variables dérivées automatiquement
            previewContainer.style.setProperty('--border-color', borderColor);
            previewContainer.style.setProperty('--input-bg-color', inputBgColor);
            previewContainer.style.setProperty('--input-text-color', inputTextColor);
            previewContainer.style.setProperty('--input-border-color', inputBorderColor);
            previewContainer.style.setProperty('--close-btn-color', closeBtnColor);
            previewContainer.style.setProperty('--link-color', linkColor);
            previewContainer.style.setProperty('--error-color', errorColor);
            previewContainer.style.setProperty('--success-color', successColor);
        }
        
        // Mettre à jour les codes couleur affichés
        $('.color-option').each(function() {
            const picker = $(this).find('.color-picker');
            const codeSpan = $(this).find('.color-code');
            if (picker.length && codeSpan.length) {
                codeSpan.text(picker.val());
            }
        });
    }
    
    // Écouteurs d'événements pour les sélecteurs de couleur
    $('.color-picker').on('input change', function() {
        updatePreview();
    });
    
    // Réinitialiser les couleurs
    $('#reset-colors').on('click', function() {
        const defaultColors = {
            'hotel_chatbot_header_color': '#2563eb',
            'hotel_chatbot_floating_button_color': '#3b82f6',
            'hotel_chatbot_send_button_color': '#10b981',
            'hotel_chatbot_user_message_color': '#3b82f6',
            'hotel_chatbot_bot_message_color': '#f3f4f6',
            'hotel_chatbot_accent_color': '#8b5cf6',
            'hotel_chatbot_background_color': '#ffffff',
            'hotel_chatbot_text_color': '#374151',
            'hotel_chatbot_border_color': '#e5e7eb',
            'hotel_chatbot_suggestion_color': '#f8f9fa',
            'hotel_chatbot_hover_color': '#f3f4f6',
            'hotel_chatbot_input_background_color': '#ffffff',
            'hotel_chatbot_input_text_color': '#374151',
            'hotel_chatbot_input_border_color': '#d1d5db',
            'hotel_chatbot_avatar_background_color': '#f3f4f6',
            'hotel_chatbot_close_button_color': '#6b7280',
            'hotel_chatbot_typing_indicator_color': '#9ca3af',
            'hotel_chatbot_shadow_color': '#00000020',
            'hotel_chatbot_link_color': '#2563eb',
            'hotel_chatbot_error_color': '#dc2626',
            'hotel_chatbot_success_color': '#059669'
        };
        
        Object.keys(defaultColors).forEach(function(key) {
            $('input[name="' + key + '"]').val(defaultColors[key]);
        });
        
        updatePreview();
    });
    
    // Copier le schéma de couleurs
    $('#copy-color-scheme').on('click', function() {
        const colorScheme = {};
        $('.color-picker').each(function() {
            const name = $(this).attr('name');
            const value = $(this).val();
            colorScheme[name] = value;
        });
        
        navigator.clipboard.writeText(JSON.stringify(colorScheme, null, 2)).then(function() {
            alert('Schéma de couleurs copié dans le presse-papiers !');
        });
    });
    
    // Thèmes prédéfinis
    $('.theme-button').on('click', function() {
        const theme = $(this).data('theme');
        let colors = {};
        
        switch(theme) {
            case 'blue':
                colors = {
                    header_color: '#2563eb',
                    floating_button_color: '#3b82f6',
                    send_button_color: '#1d4ed8',
                    user_message_color: '#3b82f6',
                    bot_message_color: '#dbeafe',
                    accent_color: '#2563eb',
                    background_color: '#ffffff',
                    text_color: '#374151',
                    border_color: '#dbeafe',
                    suggestion_color: '#eff6ff',
                    hover_color: '#dbeafe'
                };
                break;
            case 'green':
                colors = {
                    header_color: '#059669',
                    floating_button_color: '#10b981',
                    send_button_color: '#047857',
                    user_message_color: '#10b981',
                    bot_message_color: '#d1fae5',
                    accent_color: '#059669',
                    background_color: '#ffffff',
                    text_color: '#374151',
                    border_color: '#d1fae5',
                    suggestion_color: '#ecfdf5',
                    hover_color: '#d1fae5'
                };
                break;
            case 'orange':
                colors = {
                    header_color: '#ea580c',
                    floating_button_color: '#f97316',
                    send_button_color: '#ea580c',
                    user_message_color: '#f97316',
                    bot_message_color: '#fed7aa',
                    accent_color: '#ea580c',
                    background_color: '#ffffff',
                    text_color: '#374151',
                    border_color: '#fed7aa',
                    suggestion_color: '#fff7ed',
                    hover_color: '#fed7aa'
                };
                break;
            case 'purple':
                colors = {
                    header_color: '#7c3aed',
                    floating_button_color: '#8b5cf6',
                    send_button_color: '#7c3aed',
                    user_message_color: '#8b5cf6',
                    bot_message_color: '#ddd6fe',
                    accent_color: '#7c3aed',
                    background_color: '#ffffff',
                    text_color: '#374151',
                    border_color: '#ddd6fe',
                    suggestion_color: '#faf5ff',
                    hover_color: '#ddd6fe'
                };
                break;
        }
        
        // Appliquer les couleurs
        $('input[name="hotel_chatbot_header_color"]').val(colors.header_color);
        $('input[name="hotel_chatbot_floating_button_color"]').val(colors.floating_button_color);
        $('input[name="hotel_chatbot_send_button_color"]').val(colors.send_button_color);
        $('input[name="hotel_chatbot_user_message_color"]').val(colors.user_message_color);
        $('input[name="hotel_chatbot_bot_message_color"]').val(colors.bot_message_color);
        $('input[name="hotel_chatbot_accent_color"]').val(colors.accent_color);
        $('input[name="hotel_chatbot_background_color"]').val(colors.background_color);
        $('input[name="hotel_chatbot_text_color"]').val(colors.text_color);
        $('input[name="hotel_chatbot_border_color"]').val(colors.border_color);
        $('input[name="hotel_chatbot_suggestion_color"]').val(colors.suggestion_color);
        $('input[name="hotel_chatbot_hover_color"]').val(colors.hover_color);
        
        updatePreview();
    });
    
    // Initialiser l'aperçu
    updatePreview();
});

// Fonctions pour les contrôles de l'aperçu
function toggleErrorExample() {
    const errorExample = document.getElementById('error-example');
    if (errorExample) {
        if (errorExample.style.display === 'none') {
            errorExample.style.display = 'flex';
        } else {
            errorExample.style.display = 'none';
        }
    }
}

function toggleTypingIndicator() {
    const typingIndicators = document.querySelectorAll('.typing-indicator');
    typingIndicators.forEach(indicator => {
        if (indicator.style.display === 'none') {
            indicator.style.display = 'block';
            indicator.style.animation = 'pulse 1.5s infinite';
        } else {
            indicator.style.display = 'none';
        }
    });
}

function resetPreview() {
    // Masquer le message d'erreur
    const errorExample = document.getElementById('error-example');
    if (errorExample) {
        errorExample.style.display = 'none';
    }
    
    // Réafficher les indicateurs de frappe
    const typingIndicators = document.querySelectorAll('.typing-indicator');
    typingIndicators.forEach(indicator => {
        indicator.style.display = 'block';
        indicator.style.animation = 'pulse 1.5s infinite';
    });
    
    // Mettre à jour l'aperçu avec les couleurs actuelles
    if (typeof updatePreview === 'function') {
        updatePreview();
    }
}

// Nouvelles fonctions pour les contrôles de test de l'aperçu
function showTypingIndicator() {
    const messagesContainer = document.getElementById('preview-messages');
    if (!messagesContainer) return;
    
    // Supprimer l'indicateur existant s'il y en a un
    const existingTyping = messagesContainer.querySelector('.typing-indicator-message');
    if (existingTyping) {
        existingTyping.remove();
    }
    
    // Créer le message d'indicateur de frappe
    const typingMessage = document.createElement('div');
    typingMessage.className = 'preview-message bot-message typing-indicator-message';
    typingMessage.innerHTML = `
        <div class="message-avatar">🏨</div>
        <div class="message-content preview-bot-message">
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span style="font-size: 0.8rem; color: #6b7280; margin-left: 8px;">Assistant en train d'écrire...</span>
        </div>
    `;
    
    messagesContainer.appendChild(typingMessage);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function hideTypingIndicator() {
    const typingMessage = document.querySelector('.typing-indicator-message');
    if (typingMessage) {
        typingMessage.remove();
    }
}

function showErrorMessage() {
    const messagesContainer = document.getElementById('preview-messages');
    if (!messagesContainer) return;
    
    // Supprimer le message d'erreur existant s'il y en a un
    const existingError = messagesContainer.querySelector('.error-message-test');
    if (existingError) {
        existingError.remove();
    }
    
    // Créer le message d'erreur
    const errorMessage = document.createElement('div');
    errorMessage.className = 'preview-message bot-message error-message-test';
    errorMessage.innerHTML = `
        <div class="message-avatar">⚠️</div>
        <div class="message-content" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
            ❌ Désolé, une erreur s'est produite. Veuillez réessayer dans quelques instants.
        </div>
    `;
    
    messagesContainer.appendChild(errorMessage);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function resetPreviewMessages() {
    const messagesContainer = document.getElementById('preview-messages');
    if (!messagesContainer) return;
    
    // Supprimer tous les messages de test
    const testMessages = messagesContainer.querySelectorAll('.typing-indicator-message, .error-message-test');
    testMessages.forEach(message => message.remove());
    
    // Remettre les messages par défaut s'ils ont été supprimés
    if (messagesContainer.children.length < 3) {
        messagesContainer.innerHTML = `
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
        `;
    }
}

// Ajouter les gestionnaires d'événements pour les nouveaux contrôles
jQuery(document).ready(function($) {
    // Contrôles de test de l'aperçu
    $('#test-typing').on('click', function() {
        console.log('Test typing indicator clicked');
        showTypingIndicator();
        setTimeout(hideTypingIndicator, 3000);
    });
    
    $('#test-error').on('click', function() {
        console.log('Test error message clicked');
        showErrorMessage();
    });
    
    $('#reset-preview').on('click', function() {
        console.log('Reset preview clicked');
        resetPreviewMessages();
    });
});

// Styles CSS pour les points de frappe animés
const typingDotsCSS = `
<style>
.typing-dots {
    display: inline-flex;
    gap: 3px;
    align-items: center;
}

.typing-dots span {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #6b7280;
    animation: typing-bounce 1.4s infinite ease-in-out both;
}

.typing-dots span:nth-child(1) { animation-delay: -0.32s; }
.typing-dots span:nth-child(2) { animation-delay: -0.16s; }
.typing-dots span:nth-child(3) { animation-delay: 0s; }

@keyframes typing-bounce {
    0%, 80%, 100% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
`;

// Injecter les styles CSS
if (!document.getElementById('typing-dots-styles')) {
    const styleElement = document.createElement('div');
    styleElement.id = 'typing-dots-styles';
    styleElement.innerHTML = typingDotsCSS;
    document.head.appendChild(styleElement);
}
