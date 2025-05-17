/**
 * WhatsApp Integration for OpenAI Chatbot
 * Handles live agent requests and message synchronization
 */

(function($) {
    'use strict';
    
    // WhatsApp integration module
    window.ChatbotWhatsApp = {
        sessionId: null,
        pollingInterval: null,
        
        init: function(sessionId) {
            this.sessionId = sessionId;
            this.addLiveAgentButton();
            this.startMessagePolling();
        },
        
        // Add live agent button to chat interface
        addLiveAgentButton: function() {
            const buttonHtml = `
                <button id="request-live-agent" class="live-agent-button">
                    <svg width="20" height="20" fill="currentColor">
                        <path d="M10 0C4.5 0 0 4.5 0 10s4.5 10 10 10 10-4.5 10-10S15.5 0 10 0zm1 17H9v-2h2v2zm2.1-7.7l-.9.9C11.4 11 11 11.5 11 12.5V13H9v-.5c0-1.4.6-2.1 1.3-2.8l1.3-1.3c.4-.4.6-.9.6-1.4 0-1.1-.9-2-2-2s-2 .9-2 2H6c0-2.2 1.8-4 4-4s4 1.8 4 4c0 .9-.4 1.7-.9 2.3z"/>
                    </svg>
                    Speak to Human
                </button>
            `;
            
            $('#chatbot-buttons-section').append(buttonHtml);
            
            $('#request-live-agent').on('click', this.requestLiveAgent.bind(this));
        },
        
        // Request live agent
        requestLiveAgent: function() {
            const userInfo = window.OpenAIChatbot.getChatState().userInfo;
            const conversationHistory = window.OpenAIChatbot.getChatState().conversationHistory;
            
            // Show loading state
            this.showAgentOptionsLoading();
            
            $.ajax({
                url: openai_chatbot_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'request_live_agent',
                    nonce: openai_chatbot_data.nonce,
                    session_id: this.sessionId,
                    user_name: userInfo.name,
                    user_phone: userInfo.phone || '',
                    conversation_history: JSON.stringify(conversationHistory)
                },
                success: this.handleAgentResponse.bind(this),
                error: this.handleAgentError.bind(this)
            });
        },
        
        // Show loading state while checking availability
        showAgentOptionsLoading: function() {
            const loadingHtml = `
                <div class="agent-options-container">
                    <div class="loading-spinner">
                        <div class="spinner"></div>
                        <p>Checking agent availability...</p>
                    </div>
                </div>
            `;
            
            $('#chat-messages').append(loadingHtml);
            this.scrollToBottom();
        },
        
        // Handle agent availability response
        handleAgentResponse: function(response) {
            $('.agent-options-container').remove();
            
            if (response.success && response.data.available) {
                this.showWhatsAppOption(response.data);
            } else {
                this.showUnavailableOptions(response.data);
            }
        },
        
        // Show WhatsApp connection option
        showWhatsAppOption: function(data) {
            const optionsHtml = `
                <div class="agent-options-container">
                    <div class="success-message">
                        <svg width="20" height="20" fill="green">
                            <path d="M10 0C4.5 0 0 4.5 0 10s4.5 10 10 10 10-4.5 10-10S15.5 0 10 0zm-2 15l-5-5 1.4-1.4L8 12.2l7.6-7.6L17 6l-9 9z"/>
                        </svg>
                        ${data.message}
                    </div>
                    <div class="whatsapp-options">
                        <a href="${data.whatsapp_url}" target="_blank" class="whatsapp-button">
                            <svg width="20" height="20" fill="white">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                            Continue on WhatsApp
                        </a>
                        <button class="cancel-button" onclick="ChatbotWhatsApp.cancelLiveAgent()">
                            Stay in Chat
                        </button>
                    </div>
                    <p class="whatsapp-note">
                        Your conversation will continue seamlessly on WhatsApp
                    </p>
                </div>
            `;
            
            $('#chat-messages').append(optionsHtml);
            this.scrollToBottom();
        },
        
        // Show unavailable options
        showUnavailableOptions: function(data) {
            const optionsHtml = `
                <div class="agent-options-container">
                    <div class="warning-message">
                        <svg width="20" height="20" fill="orange">
                            <path d="M10 0C4.5 0 0 4.5 0 10s4.5 10 10 10 10-4.5 10-10S15.5 0 10 0zm1 15H9v-2h2v2zm0-4H9V5h2v6z"/>
                        </svg>
                        ${data.message}
                    </div>
                    <div class="unavailable-options">
                        <button class="schedule-button" onclick="ChatbotWhatsApp.scheduleCallback()">
                            Schedule Callback
                        </button>
                        <button class="email-button" onclick="ChatbotWhatsApp.sendEmail()">
                            Send Email Instead
                        </button>
                        <button class="continue-button" onclick="ChatbotWhatsApp.continueWithAI()">
                            Continue with AI
                        </button>
                    </div>
                    <p class="next-available">
                        Next available: ${data.next_available}
                    </p>
                </div>
            `;
            
            $('#chat-messages').append(optionsHtml);
            this.scrollToBottom();
        },
        
        // Cancel live agent request
        cancelLiveAgent: function() {
            $('.agent-options-container').remove();
            this.addMessage('system', 'Live agent request cancelled. How else can I help you?');
        },
        
        // Schedule callback
        scheduleCallback: function() {
            const callbackForm = `
                <div class="callback-form">
                    <h3>Schedule a Callback</h3>
                    <form onsubmit="ChatbotWhatsApp.submitCallback(event)">
                        <input type="date" id="callback-date" required min="${new Date().toISOString().split('T')[0]}">
                        <select id="callback-time" required>
                            <option value="">Select time</option>
                            <option value="09:00">9:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="14:00">2:00 PM</option>
                            <option value="15:00">3:00 PM</option>
                            <option value="16:00">4:00 PM</option>
                        </select>
                        <input type="tel" id="callback-phone" placeholder="Phone number" required>
                        <textarea id="callback-notes" placeholder="Additional notes (optional)"></textarea>
                        <button type="submit">Schedule Call</button>
                    </form>
                </div>
            `;
            
            $('.agent-options-container').html(callbackForm);
        },
        
        // Submit callback request
        submitCallback: function(event) {
            event.preventDefault();
            
            const callbackData = {
                date: $('#callback-date').val(),
                time: $('#callback-time').val(),
                phone: $('#callback-phone').val(),
                notes: $('#callback-notes').val()
            };
            
            // Send callback request
            // TODO: Implement AJAX call to save callback request
            
            $('.callback-form').html('<p class="success">Callback scheduled! We\'ll call you at the requested time.</p>');
        },
        
        // Continue with AI
        continueWithAI: function() {
            $('.agent-options-container').remove();
            this.addMessage('assistant', 'I understand you were looking for a human agent. While they\'re not available right now, I\'m here to help with any questions you have. What can I assist you with?');
        },
        
        // Start polling for WhatsApp messages
        startMessagePolling: function() {
            // Poll every 2 seconds for new messages
            this.pollingInterval = setInterval(() => {
                this.checkForNewMessages();
            }, 2000);
        },
        
        // Check for new WhatsApp messages
        checkForNewMessages: function() {
            $.ajax({
                url: openai_chatbot_data.ajax_url,
                type: 'POST',
                data: {
                    action: 'check_whatsapp_messages',
                    nonce: openai_chatbot_data.nonce,
                    session_id: this.sessionId
                },
                success: function(response) {
                    if (response.success && response.data.messages) {
                        response.data.messages.forEach(message => {
                            this.addMessage('agent', message.content, true);
                        });
                    }
                }.bind(this)
            });
        },
        
        // Add message to chat
        addMessage: function(sender, content, isWhatsApp = false) {
            const messageClass = sender === 'agent' ? 'agent-message' : 'system-message';
            const label = isWhatsApp ? 'Agent (WhatsApp)' : sender.charAt(0).toUpperCase() + sender.slice(1);
            
            const messageHtml = `
                <div class="message ${messageClass}">
                    <strong>${label}:</strong> ${content}
                    ${isWhatsApp ? '<span class="whatsapp-badge">WhatsApp</span>' : ''}
                </div>
            `;
            
            $('#chat-messages').append(messageHtml);
            this.scrollToBottom();
        },
        
        // Scroll chat to bottom
        scrollToBottom: function() {
            $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
        },
        
        // Stop polling when chat closes
        stopPolling: function() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        }
    };
    
})(jQuery);