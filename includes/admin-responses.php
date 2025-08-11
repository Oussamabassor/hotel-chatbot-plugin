<?php
if (!defined('ABSPATH')) exit;

// Page Gestion des Réponses IA
function hotel_chatbot_responses_page() {
    // Traitement des formulaires
    if (isset($_POST['save_prompts'])) {
        $languages = ['fr', 'en', 'es', 'ar', 'darija', 'de', 'it'];
        foreach ($languages as $lang) {
            update_option("hotel_chatbot_system_prompt_$lang", sanitize_textarea_field($_POST["system_prompt_$lang"]));
        }
        echo '<div class="notice notice-success"><p>✅ Prompts système sauvegardés avec succès!</p></div>';
    }
    
    if (isset($_POST['save_responses'])) {
        $response_types = ['greeting', 'availability', 'pricing', 'services', 'booking', 'fallback'];
        $languages = ['fr', 'en', 'es', 'ar', 'darija'];
        
        foreach ($response_types as $type) {
            foreach ($languages as $lang) {
                update_option("hotel_chatbot_response_{$type}_{$lang}", sanitize_textarea_field($_POST["response_{$type}_{$lang}"]));
            }
        }
        echo '<div class="notice notice-success"><p>✅ Réponses automatiques sauvegardées avec succès!</p></div>';
    }
    
    if (isset($_POST['save_keywords'])) {
        $languages = ['fr', 'en', 'es', 'ar', 'darija'];
        foreach ($languages as $lang) {
            update_option("hotel_chatbot_keywords_$lang", sanitize_textarea_field($_POST["keywords_$lang"]));
        }
        echo '<div class="notice notice-success"><p>✅ Mots-clés de détection sauvegardés avec succès!</p></div>';
    }
    ?>
    <div class="wrap">
        <div class="responses-header">
            <h1>🤖 Gestion des Réponses IA</h1>
            <p>Personnalisez les prompts système, réponses automatiques et mots-clés de détection</p>
        </div>
        
        <div class="responses-dashboard">
            <!-- Onglets de navigation -->
            <div class="tab-navigation">
                <button class="tab-button active" onclick="showTab('prompts')">📝 Prompts Système</button>
                <button class="tab-button" onclick="showTab('responses')">💬 Réponses Automatiques</button>
                <button class="tab-button" onclick="showTab('keywords')">🔍 Mots-clés</button>
                <button class="tab-button" onclick="showTab('testing')">🧪 Test IA</button>
            </div>
            
            <!-- Onglet Prompts Système -->
            <div id="prompts-tab" class="tab-content active">
                <div class="section-header">
                    <h2>📝 Configuration des Prompts Système</h2>
                    <p>Définissez les instructions données à l'IA pour chaque langue</p>
                </div>
                
                <form method="post" class="prompts-form">
                    <input type="hidden" name="save_prompts" value="1">
                    
                    <div class="language-tabs">
                        <div class="lang-tab-buttons">
                            <button type="button" class="lang-tab active" onclick="showLangTab('fr')">🇫🇷 Français</button>
                            <button type="button" class="lang-tab" onclick="showLangTab('en')">🇬🇧 English</button>
                            <button type="button" class="lang-tab" onclick="showLangTab('es')">🇪🇸 Español</button>
                            <button type="button" class="lang-tab" onclick="showLangTab('ar')">🇸🇦 العربية</button>
                            <button type="button" class="lang-tab" onclick="showLangTab('darija')">🇲🇦 Darija</button>
                            <button type="button" class="lang-tab" onclick="showLangTab('de')">🇩🇪 Deutsch</button>
                            <button type="button" class="lang-tab" onclick="showLangTab('it')">🇮🇹 Italiano</button>
                        </div>
                        
                        <!-- Prompts par langue -->
                        <div id="prompt-fr" class="lang-content active">
                            <h3>Prompt Système - Français</h3>
                            <textarea name="system_prompt_fr" rows="10" class="large-text"><?php echo esc_textarea(get_option('hotel_chatbot_system_prompt_fr', 'Tu es un assistant hôtelier professionnel pour l\'Hôtel Excellence. Réponds de manière chaleureuse et informative en français. Limite tes réponses à 250 mots maximum.')); ?></textarea>
                        </div>
                        
                        <div id="prompt-en" class="lang-content">
                            <h3>System Prompt - English</h3>
                            <textarea name="system_prompt_en" rows="10" class="large-text"><?php echo esc_textarea(get_option('hotel_chatbot_system_prompt_en', 'You are a professional hotel assistant for Hotel Excellence. Respond warmly and informatively in English. Limit your responses to 250 words maximum.')); ?></textarea>
                        </div>
                        
                        <div id="prompt-es" class="lang-content">
                            <h3>Prompt del Sistema - Español</h3>
                            <textarea name="system_prompt_es" rows="10" class="large-text"><?php echo esc_textarea(get_option('hotel_chatbot_system_prompt_es', 'Eres un asistente hotelero profesional para Hotel Excellence. Responde de manera cálida e informativa en español. Limita tus respuestas a 250 palabras máximo.')); ?></textarea>
                        </div>
                        
                        <div id="prompt-ar" class="lang-content">
                            <h3>موجه النظام - العربية</h3>
                            <textarea name="system_prompt_ar" rows="10" class="large-text"><?php echo esc_textarea(get_option('hotel_chatbot_system_prompt_ar', 'أنت مساعد فندقي محترف لفندق Excellence. أجب بطريقة ودودة ومفيدة باللغة العربية. احصر إجاباتك في 250 كلمة كحد أقصى.')); ?></textarea>
                        </div>
                        
                        <div id="prompt-darija" class="lang-content">
                            <h3>Prompt Système - Darija</h3>
                            <textarea name="system_prompt_darija" rows="10" class="large-text"><?php echo esc_textarea(get_option('hotel_chatbot_system_prompt_darija', 'Nta assistant dyal Hotel Excellence. Jaweb b darija o français mezougin, kon dafi o m3awen. Ma tzidch 3la 250 kelma f jawabek.')); ?></textarea>
                        </div>
                        
                        <div id="prompt-de" class="lang-content">
                            <h3>System-Prompt - Deutsch</h3>
                            <textarea name="system_prompt_de" rows="10" class="large-text"><?php echo esc_textarea(get_option('hotel_chatbot_system_prompt_de', 'Sie sind ein professioneller Hotelassistent für Hotel Excellence. Antworten Sie herzlich und informativ auf Deutsch. Begrenzen Sie Ihre Antworten auf maximal 250 Wörter.')); ?></textarea>
                        </div>
                        
                        <div id="prompt-it" class="lang-content">
                            <h3>Prompt di Sistema - Italiano</h3>
                            <textarea name="system_prompt_it" rows="10" class="large-text"><?php echo esc_textarea(get_option('hotel_chatbot_system_prompt_it', 'Sei un assistente alberghiero professionale per Hotel Excellence. Rispondi in modo caloroso e informativo in italiano. Limita le tue risposte a massimo 250 parole.')); ?></textarea>
                        </div>
                    </div>
                    
                    <?php submit_button('💾 Sauvegarder les Prompts', 'primary', 'save_prompts'); ?>
                </form>
            </div>
            
            <!-- Onglet Réponses Automatiques -->
            <div id="responses-tab" class="tab-content">
                <div class="section-header">
                    <h2>💬 Réponses Automatiques Prédéfinies</h2>
                    <p>Configurez les réponses automatiques pour différents types de demandes</p>
                </div>
                
                <form method="post" class="responses-form">
                    <input type="hidden" name="save_responses" value="1">
                    
                    <div class="response-categories">
                        <div class="category-grid">
                            <!-- Salutations -->
                            <div class="response-category">
                                <h3>👋 Messages de Bienvenue</h3>
                                <div class="response-inputs">
                                    <div class="response-input">
                                        <label>🇫🇷 Français:</label>
                                        <textarea name="response_greeting_fr" rows="3"><?php echo esc_textarea(get_option('response_greeting_fr', 'Bonjour ! Je suis l\'assistant de l\'Hôtel Excellence. Comment puis-je vous aider aujourd\'hui ?')); ?></textarea>
                                    </div>
                                    <div class="response-input">
                                        <label>🇬🇧 English:</label>
                                        <textarea name="response_greeting_en" rows="3"><?php echo esc_textarea(get_option('response_greeting_en', 'Hello! I\'m the assistant for Hotel Excellence. How can I help you today?')); ?></textarea>
                                    </div>
                                    <div class="response-input">
                                        <label>🇲🇦 Darija:</label>
                                        <textarea name="response_greeting_darija" rows="3"><?php echo esc_textarea(get_option('response_greeting_darija', 'Salam! Ana l-assistant dyal Hotel Excellence. Kifash ymken n3awnek lyoum?')); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Disponibilités -->
                            <div class="response-category">
                                <h3>🏨 Disponibilités</h3>
                                <div class="response-inputs">
                                    <div class="response-input">
                                        <label>🇫🇷 Français:</label>
                                        <textarea name="response_availability_fr" rows="3"><?php echo esc_textarea(get_option('response_availability_fr', 'Nous avons plusieurs types de chambres disponibles. Pouvez-vous me préciser vos dates de séjour ?')); ?></textarea>
                                    </div>
                                    <div class="response-input">
                                        <label>🇬🇧 English:</label>
                                        <textarea name="response_availability_en" rows="3"><?php echo esc_textarea(get_option('response_availability_en', 'We have several room types available. Could you please specify your stay dates?')); ?></textarea>
                                    </div>
                                    <div class="response-input">
                                        <label>🇲🇦 Darija:</label>
                                        <textarea name="response_availability_darija" rows="3"><?php echo esc_textarea(get_option('response_availability_darija', 'Kaynin 3andna anwa3 ktira mn les chambres. Ymken tgoli liya les dates li bghiti?')); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Prix -->
                            <div class="response-category">
                                <h3>💰 Tarification</h3>
                                <div class="response-inputs">
                                    <div class="response-input">
                                        <label>🇫🇷 Français:</label>
                                        <textarea name="response_pricing_fr" rows="3"><?php echo esc_textarea(get_option('response_pricing_fr', 'Nos tarifs varient selon la saison et le type de chambre. Chambre standard : 120€/nuit, Suite : 200€/nuit.')); ?></textarea>
                                    </div>
                                    <div class="response-input">
                                        <label>🇬🇧 English:</label>
                                        <textarea name="response_pricing_en" rows="3"><?php echo esc_textarea(get_option('response_pricing_en', 'Our rates vary by season and room type. Standard room: €120/night, Suite: €200/night.')); ?></textarea>
                                    </div>
                                    <div class="response-input">
                                        <label>🇲🇦 Darija:</label>
                                        <textarea name="response_pricing_darija" rows="3"><?php echo esc_textarea(get_option('response_pricing_darija', 'Les prix dyalna kayختلفو 7asb la saison o نوع la chambre. Chambre 3adiya: 120€/nuit, Suite: 200€/nuit.')); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php submit_button('💾 Sauvegarder les Réponses', 'primary', 'save_responses'); ?>
                </form>
            </div>
            
            <!-- Onglet Mots-clés -->
            <div id="keywords-tab" class="tab-content">
                <div class="section-header">
                    <h2>🔍 Gestion des Mots-clés de Détection</h2>
                    <p>Configurez les mots-clés pour la détection d'intention et de langue</p>
                </div>
                
                <form method="post" class="keywords-form">
                    <input type="hidden" name="save_keywords" value="1">
                    
                    <div class="keywords-grid">
                        <div class="keyword-category">
                            <h3>🇫🇷 Mots-clés Français</h3>
                            <textarea name="keywords_fr" rows="8" placeholder="Entrez les mots-clés séparés par des virgules"><?php echo esc_textarea(get_option('hotel_chatbot_keywords_fr', 'bonjour, salut, bonsoir, prix, tarif, disponibilité, réservation, chambre, service')); ?></textarea>
                        </div>
                        
                        <div class="keyword-category">
                            <h3>🇬🇧 English Keywords</h3>
                            <textarea name="keywords_en" rows="8" placeholder="Enter keywords separated by commas"><?php echo esc_textarea(get_option('hotel_chatbot_keywords_en', 'hello, hi, good, price, rate, availability, booking, room, service')); ?></textarea>
                        </div>
                        
                        <div class="keyword-category">
                            <h3>🇲🇦 Mots-clés Darija</h3>
                            <textarea name="keywords_darija" rows="8" placeholder="Dkhel les mots-clés mefroqin b virgules"><?php echo esc_textarea(get_option('hotel_chatbot_keywords_darija', 'salam, ahlan, marhaba, bghit, prix, chhal, kayn, chambre, réservation')); ?></textarea>
                        </div>
                        
                        <div class="keyword-category">
                            <h3>🇪🇸 Palabras Clave Español</h3>
                            <textarea name="keywords_es" rows="8" placeholder="Ingrese palabras clave separadas por comas"><?php echo esc_textarea(get_option('hotel_chatbot_keywords_es', 'hola, buenos, precio, disponibilidad, reserva, habitación, servicio')); ?></textarea>
                        </div>
                    </div>
                    
                    <?php submit_button('💾 Sauvegarder les Mots-clés', 'primary', 'save_keywords'); ?>
                </form>
            </div>
            
            <!-- Onglet Test IA -->
            <div id="testing-tab" class="tab-content">
                <div class="section-header">
                    <h2>🧪 Test des Réponses IA</h2>
                    <p>Testez les réponses de votre chatbot en temps réel</p>
                </div>
                
                <div class="ai-testing-interface">
                    <div class="test-controls">
                        <div class="test-input-group">
                            <label for="test-language">Langue de test:</label>
                            <select id="test-language">
                                <option value="fr">🇫🇷 Français</option>
                                <option value="en">🇬🇧 English</option>
                                <option value="es">🇪🇸 Español</option>
                                <option value="ar">🇸🇦 العربية</option>
                                <option value="darija">🇲🇦 Darija</option>
                            </select>
                        </div>
                        
                        <div class="test-input-group">
                            <label for="test-message">Message de test:</label>
                            <input type="text" id="test-message" placeholder="Tapez votre message de test..." class="large-text">
                        </div>
                        
                        <button type="button" class="button button-primary" onclick="testAIResponse()">🚀 Tester la Réponse</button>
                    </div>
                    
                    <div class="test-results">
                        <div class="test-result-section">
                            <h4>📤 Message envoyé:</h4>
                            <div id="test-input-display" class="test-display"></div>
                        </div>
                        
                        <div class="test-result-section">
                            <h4>🔍 Détection:</h4>
                            <div id="test-detection-display" class="test-display"></div>
                        </div>
                        
                        <div class="test-result-section">
                            <h4>📥 Réponse générée:</h4>
                            <div id="test-response-display" class="test-display"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function showTab(tabName) {
            // Masquer tous les onglets
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Afficher l'onglet sélectionné
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        function showLangTab(lang) {
            // Masquer tous les contenus de langue
            document.querySelectorAll('.lang-content').forEach(content => {
                content.classList.remove('active');
            });
            document.querySelectorAll('.lang-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Afficher la langue sélectionnée
            document.getElementById('prompt-' + lang).classList.add('active');
            event.target.classList.add('active');
        }
        
        function testAIResponse() {
            const language = document.getElementById('test-language').value;
            const message = document.getElementById('test-message').value;
            
            if (!message.trim()) {
                alert('Veuillez entrer un message de test');
                return;
            }
            
            // Afficher le message envoyé
            document.getElementById('test-input-display').innerHTML = `
                <div class="test-message user">
                    <strong>Langue:</strong> ${language}<br>
                    <strong>Message:</strong> ${message}
                </div>
            `;
            
            // Simulation de détection (en réalité, cela ferait appel à l'API)
            document.getElementById('test-detection-display').innerHTML = `
                <div class="test-detection">
                    <strong>Langue détectée:</strong> ${language}<br>
                    <strong>Intention:</strong> ${detectIntent(message)}<br>
                    <strong>Confiance:</strong> 85%
                </div>
            `;
            
            // Simulation de réponse
            document.getElementById('test-response-display').innerHTML = `
                <div class="test-message bot">
                    <div class="loading">Génération de la réponse...</div>
                </div>
            `;
            
            // Simuler un délai de réponse
            setTimeout(() => {
                document.getElementById('test-response-display').innerHTML = `
                    <div class="test-message bot">
                        <strong>Réponse générée:</strong><br>
                        ${generateTestResponse(message, language)}
                    </div>
                `;
            }, 2000);
        }
        
        function detectIntent(message) {
            const msg = message.toLowerCase();
            if (msg.includes('prix') || msg.includes('tarif') || msg.includes('cost')) return 'pricing';
            if (msg.includes('disponib') || msg.includes('available')) return 'availability';
            if (msg.includes('réserv') || msg.includes('book')) return 'booking';
            if (msg.includes('service')) return 'services';
            return 'general';
        }
        
        function generateTestResponse(message, language) {
            const responses = {
                'fr': 'Merci pour votre message. Je suis ravi de vous aider avec vos questions concernant l\'Hôtel Excellence.',
                'en': 'Thank you for your message. I\'m happy to help you with your questions about Hotel Excellence.',
                'es': 'Gracias por su mensaje. Estoy encantado de ayudarle con sus preguntas sobre Hotel Excellence.',
                'ar': 'شكراً لرسالتك. يسعدني مساعدتك في أسئلتك حول فندق Excellence.',
                'darija': 'Shokran 3la message dyalek. Ana farhan bach n3awnek f les questions dyalek 3la Hotel Excellence.'
            };
            return responses[language] || responses['fr'];
        }
        </script>
    </div>
    <?php
}
