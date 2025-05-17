/**
 * OpenAI Chatbot Popup Module
 * Handles the initial popup menu display and interactions
 */

(function($) {
    'use strict';
    
    // Export to global scope for main script
    window.OpenAIChatbotPopup = {
        init: function() {
            // Check if popup should be shown
            const chatState = window.OpenAIChatbot ? window.OpenAIChatbot.getChatState() : null;
            
            if (!chatState || !chatState.popupShown) {
                // Add 3 second delay before showing popup
                setTimeout(function() {
                    showPopupMenu();
                }, 3000);
            }
        }
    };
    
    function showPopupMenu() {
        // Check if it's a mobile device
        const isMobile = window.innerWidth <= 768;
        
        let popupHTML = '';
        
        if (isMobile) {
            // Simplified version for mobile
            popupHTML = `
                <div class="popup-menu mobile">
                    <div class="popup-controls">
                        <button class="popup-minimize" aria-label="Minimize popup">−</button>
                        <button class="popup-close" aria-label="Close popup">×</button>
                    </div>
                    <button id="ask-ai" class="popup-button">Ask Smart Bot 🤖</button>
                    <a href="https://appt.link/meet-with-pete-gypps/chat-with-pete-R9wR8KNd-zmg5pZYR" target="_blank" rel="noopener" class="popup-button">Book a Demo 👨‍💼</a>
                    <div class="popup-footer">
                        <a href="https://web-smart.co/privacy-policy/" class="privacy-link">Privacy policy</a>
                    </div>
                </div>
            `;
        } else {
            // Full version for desktop
            popupHTML = `
                <div class="popup-menu">
                    <div class="popup-controls">
                        <button class="popup-minimize" aria-label="Minimize popup">−</button>
                        <button class="popup-close" aria-label="Close popup">×</button>
                    </div>
                    <h2 class="popup-title">${openai_chatbot_data.popup_settings.title || '🚀 Ready to Launch?'}</h2>
                    <p class="popup-description">${openai_chatbot_data.popup_settings.description || "Let's build you a high-impact website that looks great and performs even better.<br><br>Want to learn how I can help your business grow?"}</p>
                    <button id="ask-ai" class="popup-button">${openai_chatbot_data.popup_settings.chat_button || 'Ask the AI 🤖'}</button>
                    <a href="${openai_chatbot_data.popup_settings.demo_link || 'https://appt.link/meet-with-pete-gypps/chat-with-pete-R9wR8KNd-zmg5pZYR'}" target="_blank" rel="noopener" class="popup-button">${openai_chatbot_data.popup_settings.demo_button || 'Book a Demo 👨‍💼'}</a>
                    <div class="popup-footer">
                        <a href="https://web-smart.co/privacy-policy/" class="privacy-link">Privacy policy</a>
                    </div>
                </div>
            `;
        }
        
        const popup = $(popupHTML).appendTo('body');
        
        setTimeout(() => popup.addClass('active'), 50);

        // Attach event handlers for the popup buttons
        $('#ask-ai').on('click', function() {
            // Trigger main chat opening via event
            $(document).trigger('openai-chatbot:open');
            popup.removeClass('active').remove();
        });
        
        // Add handlers for minimize and close buttons
        $('.popup-minimize').on('click', function(e) {
            e.stopPropagation();
            const popup = $(this).closest('.popup-menu');
            popup.toggleClass('minimized');
            
            // Change button text based on state
            if (popup.hasClass('minimized')) {
                $(this).html('+');
            } else {
                $(this).html('−');
            }
        });

        $('.popup-close').on('click', function(e) {
            e.stopPropagation();
            // Mark popup as shown
            $(document).trigger('openai-chatbot:popup-closed');
            $(this).closest('.popup-menu').remove();
        });
    }
    
})(jQuery);