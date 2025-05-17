jQuery(document).ready(function($) {
    // Generate unique session ID
    var sessionId = localStorage.getItem('chatbot_session_id') || generateSessionId();
    localStorage.setItem('chatbot_session_id', sessionId);
    
    // Global state - Load from localStorage for persistence
    var chatState = loadChatState() || {
        conversationHistory: [],
        userInfo: {},
        chatActive: false,
        chatOpen: false,
        popupShown: false,
        lastActivity: Date.now()
    };
    
    // Export to global for popup module and proactive features
    window.OpenAIChatbot = {
        getChatState: function() { return chatState; },
        trackEvent: trackEvent
    };
    
    // Analytics tracking function
    function trackEvent(eventType, eventData) {
        $.post(openai_chatbot_data.ajax_url, {
            action: 'track_chatbot_event',
            event_type: eventType,
            event_data: eventData,
            session_id: sessionId,
            user_id: chatState.userInfo.email || null,
            nonce: openai_chatbot_data.nonce
        });
    }
    
    // Generate unique session ID
    function generateSessionId() {
        return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    // Save state to localStorage whenever it changes
    function saveChatState() {
        chatState.lastActivity = Date.now();
        localStorage.setItem('openai_chatbot_state', JSON.stringify(chatState));
    }
    
    // Load state from localStorage
    function loadChatState() {
        const saved = localStorage.getItem('openai_chatbot_state');
        if (saved) {
            try {
                const state = JSON.parse(saved);
                // Check if state is less than 30 minutes old
                if (Date.now() - state.lastActivity < 30 * 60 * 1000) {
                    return state;
                }
            } catch (e) {
                console.error('Failed to load chat state:', e);
            }
        }
        return null;
    }

    // Initial responses for each service
    const initialResponses = {
        'all-services': "Hi there! Before I tell you about our web services, I'd like to know if you're:\n\n1) Ready to start a project now\n2) Looking for pricing information\n3) Just researching options?",
        'web-design': "Thanks for asking about web design! To help you better, could you tell me if you're:\n\n1) Ready to start a project now\n2) Looking for pricing information\n3) Just researching options?",
        'marketing': "Great interest in marketing! First, let me know if you're:\n\n1) Ready to start right away\n2) Looking for pricing information\n3) Just researching options?",
        'content-writing': "Thanks for asking about content writing! Are you:\n\n1) Ready to start creating content\n2) Looking for pricing information\n3) Just researching options?",
        'ai-content': "Excellent choice! Regarding AI content creation, are you:\n\n1) Ready to implement now\n2) Looking for pricing information\n3) Just researching options?",
        'other': "I'd be happy to help! First, could you tell me if you're:\n\n1) Ready to start a project now\n2) Looking for pricing information\n3) Just researching options?"
    };

    // Initialize based on state
    if (chatState.chatOpen) {
        // Restore open chat
        $('#openai-chatbot').addClass('open');
        restoreChatInterface();
        trackEvent('chat_restored', { source: 'page_load' });
    } else {
        // Check which popup style to use
        const useClassicPopup = openai_chatbot_data.popup_style === 'classic';
        if (useClassicPopup) {
            loadPopupModule();
        }
        // Otherwise the new widget will be shown automatically
    }
    
    // Listen for popup events
    $(document).on('openai-chatbot:open', function() {
        chatState.chatActive = true;
        chatState.chatOpen = true;
        chatState.popupShown = true;
        saveChatState();
        $('#openai-chatbot').addClass('open');
        trackEvent('chat_opened', { source: 'popup' });
        if (chatState.userInfo.fullName) {
            restoreChatInterface();
        } else {
            showContactForm();
        }
    });
    
    $(document).on('openai-chatbot:popup-closed', function() {
        chatState.popupShown = true;
        saveChatState();
    });

    // Lazy load popup module
    function loadPopupModule() {
        if (!chatState.popupShown && typeof window.OpenAIChatbotPopup === 'undefined') {
            // Dynamically load popup script
            const script = document.createElement('script');
            script.src = openai_chatbot_data.plugin_url + 'js/openai-chatbot-popup.js';
            script.onload = function() {
                if (window.OpenAIChatbotPopup) {
                    window.OpenAIChatbotPopup.init();
                }
            };
            document.head.appendChild(script);
        }
    }

    // Show contact form
    function showContactForm() {
        const formSettings = openai_chatbot_data.form_settings || {};
        $('#chat-messages').html(`
            <h3>${formSettings.title || "Let's get to know each other!"}</h3>
            <form id="contact-form" class="contact-form">
                <input type="text" id="name" placeholder="${formSettings.name_label || 'Your Name'}" required>
                <input type="email" id="email" placeholder="${formSettings.email_label || 'Your Email'}" required>
                <input type="tel" id="phone" placeholder="${formSettings.phone_label || 'Your Phone Number'}" required>
                <input type="text" id="website" placeholder="${formSettings.website_label || 'Your Website (if you have one)'}">
                <button type="submit">${formSettings.submit_button || 'Start Chat'}</button>
            </form>
        `);

        $('#chat-form').hide();
        $('#chatbot-buttons-section').hide();
        
        $('#contact-form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate form inputs
            const name = $('#name').val().trim();
            const email = $('#email').val().trim();
            const phone = $('#phone').val().trim();
            const website = $('#website').val().trim();
            
            // Name validation
            if (!name || name.length < 2 || name.length > 100) {
                alert('Please enter a valid name (2-100 characters)');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return;
            }
            
            // Phone validation
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            if (!phone || phone.length < 7 || !phoneRegex.test(phone)) {
                alert('Please enter a valid phone number');
                return;
            }
            
            // URL validation (optional)
            if (website && !website.match(/^https?:\/\/.+/)) {
                alert('Please enter a valid URL starting with http:// or https://');
                return;
            }
            
            chatState.userInfo = {
                fullName: name,
                firstName: name.split(' ')[0],
                email: email,
                phone: phone,
                website: website
            };
            saveChatState();
            trackEvent('contact_form_submitted', {
                has_website: !!chatState.userInfo.website
            });
            showServiceSelection();
        });
    }

    // Show service selection
    function showServiceSelection() {
        const serviceSettings = openai_chatbot_data.service_settings || {};
        const options = serviceSettings.options || [
            {display: 'Website Design', json_key: 'website_design'},
            {display: 'SEO (Search Engine Optimization)', json_key: 'seo'},
            {display: 'Website Care Plan', json_key: 'maintenance'},
            {display: 'Content Creation', json_key: 'content'},
            {display: 'Website Improvements', json_key: 'improvements'},
            {display: 'Something else', json_key: 'general'}
        ];
        
        let buttonsHtml = '';
        options.forEach(option => {
            buttonsHtml += `<button class="service-button" data-service="${option.json_key}">${option.display}</button>`;
        });
        
        $('#chat-messages').html(`
            <div class="service-selection">
                <h3 class="service-title">${serviceSettings.title || 'What can I help you with?'}</h3>
                <p class="service-subtitle">${serviceSettings.subtitle || 'Select the service you\'re most interested in:'}</p>
                <div class="service-options">
                    ${buttonsHtml}
                </div>
            </div>
        `);
        
        // Show suggestions after service selection
        showResponseSuggestions();

        $('.service-button').on('click', function() {
            const service = $(this).data('service');
            $('.service-selection').remove();
            initializeChatInterface();

            // Add initial response to chat
            const initialResponse = initialResponses[service];
            addMessage('Smart Bot', initialResponse);
            
            // Add to conversation history
            chatState.conversationHistory.push({
                role: 'assistant',
                content: initialResponse
            });
            saveChatState();
            
            // Show relevant suggestions
            showResponseSuggestions(service);
            
            // Track service selection
            trackEvent('service_selected', { service: service });
        });
    }

    // Initialize chat interface
    function initializeChatInterface() {
        if (!$('#chat-form').length) {
            $('#chatbot-content').append(`
                <form id="chat-form">
                    <input type="text" id="user-input" placeholder="Type your message..." aria-label="Type your message">
                    <label for="file-upload" class="file-upload-label" aria-label="Upload file">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M16.88 9.1A4 4 0 0 1 16 17H5a5 5 0 0 1-1-9.9V7a3 3 0 0 1 4.52-2.59A4.98 4.98 0 0 1 17 8c0 .38-.04.74-.12 1.1zM11 11h3l-4-4-4 4h3v3h2v-3z" />
                        </svg>
                    </label>
                    <input type="file" id="file-upload" style="display: none;" accept="image/*,application/pdf,.doc,.docx,.txt">
                    <button type="submit">Send</button>
                </form>
            `);
        }

        $('#chat-form').show();
        $('#chatbot-buttons-section').show();

        // Attach event handlers for chat
        $('#chat-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            const message = $('#user-input').val().trim();
            if (message) {
                $('#user-input').val('');
                sendMessage(message);
            }
        });

        // Handle enter key
        $('#user-input').off('keypress').on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                $('#chat-form').submit();
            }
        });
    }

    // Send message to API
    function sendMessage(message) {
        // Validate input
        if (!message || message.trim().length === 0) {
            return;
        }
        
        // Limit message length
        if (message.length > 1000) {
            alert('Message is too long. Please limit to 1000 characters.');
            return;
        }
        
        // Basic XSS prevention
        message = message.replace(/[<>"'&]/g, function(char) {
            const entities = {
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '&': '&amp;'
            };
            return entities[char];
        });
        
        addMessage('You', message);
        chatState.conversationHistory.push({ role: 'user', content: message });
        saveChatState();
        
        // Track message sent
        trackEvent('message_sent', {
            message: message,
            conversation_length: chatState.conversationHistory.length
        });

        // Add animated typing indicator
        $('#chat-messages').append(`
            <div class="typing-indicator">
                <span>Smart Bot is typing</span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
                <span class="typing-dot"></span>
            </div>
        `);
        
        scrollToBottom();

        $.post(openai_chatbot_data.ajax_url, {
            action: 'openai_chatbot',
            user_question: message,
            user_name: chatState.userInfo.firstName,
            conversation_history: JSON.stringify(chatState.conversationHistory),
            nonce: openai_chatbot_data.nonce
        })
        .done(function(response) {
            if (response.success) {
                // Dynamic typing duration based on response complexity
                const responseLength = response.data.response.length;
                const typingDuration = Math.min(
                    responseLength * 15 + 500, // base calculation
                    3000 // maximum duration
                );
                
                setTimeout(function() {
                    $('.typing-indicator').remove();
                    addMessage('Smart Bot', response.data.response);
                    chatState.conversationHistory.push({ 
                        role: 'assistant', 
                        content: response.data.response 
                    });
                    saveChatState();
                    scrollToBottom();
                    
                    // Track response received
                    trackEvent('response_received', {
                        response_length: response.data.response.length,
                        typing_duration: typingDuration
                    });
                }, typingDuration);
            } else {
                $('.typing-indicator').remove();
                addMessage('Error', 'Failed to get response');
            }
        })
        .fail(function() {
            $('.typing-indicator').remove();
            addMessage('Error', 'Failed to send message');
        });
    }

    // Add message to chat
    function addMessage(sender, message) {
        const escaped = message
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

        const formatted = escaped
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\[(.*?)\]\((https?:\/\/[^\s]+)\)/g, 
                '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>'
            );

        $('#chat-messages').append(`
            <div class="message ${sender === 'You' ? 'user' : ''}">
                <strong>${sender}:</strong> ${formatted}
            </div>
        `);
        
        scrollToBottom();
    }

    // Scroll chat to bottom
    function scrollToBottom() {
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Handle minimize/close buttons
    $('.chatbot-minimize').on('click', function() {
        $('#openai-chatbot').removeClass('open');
        chatState.chatOpen = false;
        saveChatState();
        trackEvent('chat_minimized', {});
    });

    $('.chatbot-close').on('click', function() {
        $('#openai-chatbot').removeClass('open');
        chatState.chatOpen = false;
        saveChatState();
        trackEvent('chat_closed', {});
    });

    // Handle end chat
    $('#end-chat').on('click', function() {
        const finalMessage = 'Thank you for chatting with us. We will contact you soon.';
        addMessage('Bot', finalMessage);

        $.post(openai_chatbot_data.ajax_url, {
            action: 'openai_chatbot_contact',
            name: chatState.userInfo.fullName,
            email: chatState.userInfo.email,
            phone: chatState.userInfo.phone,
            website: chatState.userInfo.website,
            conversation_history: JSON.stringify(chatState.conversationHistory),
            nonce: openai_chatbot_data.nonce
        });

        setTimeout(function() {
            chatState.chatActive = false;
            chatState.chatOpen = false;
            chatState.conversationHistory = [];
            chatState.userInfo = {};
            saveChatState();
            $('#openai-chatbot').removeClass('open');
        }, 3000);
        
        // Track end chat
        trackEvent('chat_ended', {
            total_messages: chatState.conversationHistory.length
        });
        
        // Show rating button
        $('#rate-chat').show();
    });

    // Handle promotions button
    $('#promotions-button').on('click', function() {
        sendMessage('Show me current promotions');
        trackEvent('promotions_clicked', {});
    });
    
    // Function to restore chat interface from saved state
    function restoreChatInterface() {
        if (chatState.userInfo.fullName) {
            // Skip contact form if we have user info
            if (chatState.conversationHistory.length > 0) {
                // Restore previous conversation
                $('#chat-messages').empty();
                chatState.conversationHistory.forEach(function(msg) {
                    if (msg.role === 'user') {
                        addMessage('You', msg.content);
                    } else {
                        addMessage(chatState.userInfo.firstName || 'Bot', msg.content);
                    }
                });
                initializeChatInterface();
            } else {
                // Show service selection if no conversation yet
                showServiceSelection();
            }
        } else {
            // Show contact form if no user info
            showContactForm();
        }
    }
    
    // Clear old sessions periodically
    setInterval(function() {
        const saved = localStorage.getItem('openai_chatbot_state');
        if (saved) {
            try {
                const state = JSON.parse(saved);
                // Clear if older than 30 minutes
                if (Date.now() - state.lastActivity > 30 * 60 * 1000) {
                    localStorage.removeItem('openai_chatbot_state');
                }
            } catch (e) {
                console.error('Failed to check session age:', e);
            }
        }
    }, 5 * 60 * 1000); // Check every 5 minutes
    
    // Add swipe gestures for mobile
    if ('ontouchstart' in window) {
        let touchStartX = 0;
        let touchEndX = 0;
        const swipeThreshold = 100;
        
        $('#openai-chatbot').on('touchstart', function(e) {
            touchStartX = e.touches[0].clientX;
        });
        
        $('#openai-chatbot').on('touchend', function(e) {
            touchEndX = e.changedTouches[0].clientX;
            handleSwipe();
        });
        
        function handleSwipe() {
            const swipeDistance = touchEndX - touchStartX;
            
            // Swipe right to close
            if (swipeDistance > swipeThreshold && $('#openai-chatbot').hasClass('open')) {
                $('#openai-chatbot').removeClass('open');
                chatState.chatOpen = false;
                saveChatState();
            }
        }
    }
    
    // Optimize keyboard behavior on mobile
    if (/iPhone|iPad|iPod|Android/i.test(navigator.userAgent)) {
        $('#user-input').on('focus', function() {
            // Scroll chat to bottom when keyboard opens
            setTimeout(function() {
                scrollToBottom();
            }, 300);
        });
        
        // Prevent zoom on input focus (iOS)
        $('input[type="text"], input[type="email"], input[type="tel"], textarea').on('focus', function() {
            $(this).attr('data-font-size', $(this).css('font-size'));
            $(this).css('font-size', '16px');
        }).on('blur', function() {
            $(this).css('font-size', $(this).attr('data-font-size'));
        });
    }
    
    // Keyboard navigation support
    $(document).on('keydown', function(e) {
        // ESC key to close chat
        if (e.keyCode === 27 && $('#openai-chatbot').hasClass('open')) {
            $('#openai-chatbot').removeClass('open');
            chatState.chatOpen = false;
            saveChatState();
        }
        
        // Tab navigation within chat
        if (e.keyCode === 9 && $('#openai-chatbot').hasClass('open')) {
            const focusableElements = $('#openai-chatbot').find('button:visible, input:visible, a:visible').filter(':not([disabled])');
            const firstElement = focusableElements.first();
            const lastElement = focusableElements.last();
            
            if (e.shiftKey && document.activeElement === firstElement[0]) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement[0]) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    });
    
    // Screen reader announcements
    function announceToScreenReader(message) {
        const announcement = $('<div>');
        announcement.attr('role', 'status');
        announcement.attr('aria-live', 'polite');
        announcement.css({
            'position': 'absolute',
            'left': '-10000px',
            'width': '1px',
            'height': '1px',
            'overflow': 'hidden'
        });
        announcement.text(message);
        $('body').append(announcement);
        
        setTimeout(function() {
            announcement.remove();
        }, 1000);
    }
    
    // Announce chat state changes
    $('.chatbot-minimize').on('click', function() {
        announceToScreenReader('Chat minimized');
    });
    
    $('.chatbot-close').on('click', function() {
        announceToScreenReader('Chat closed');
    });
    
    // Response suggestions functionality
    function showResponseSuggestions(context) {
        const suggestions = getSuggestionsForContext(context);
        
        if (suggestions.length === 0) return;
        
        const suggestionsHtml = `
            <div class="response-suggestions">
                <p class="suggestions-label">Quick responses:</p>
                <div class="suggestions-list">
                    ${suggestions.map(s => `<button class="suggestion-button" data-text="${s}">${s}</button>`).join('')}
                </div>
            </div>
        `;
        
        $('#chat-form').before(suggestionsHtml);
        
        // Handle suggestion clicks
        $('.suggestion-button').on('click', function() {
            const text = $(this).data('text');
            $('#user-input').val(text);
            sendMessage(text);
            $('.response-suggestions').remove();
            trackEvent('suggestion_clicked', { suggestion: text });
        });
    }
    
    function getSuggestionsForContext(context) {
        const suggestions = {
            'web-design': [
                'What does a basic website include?',
                'How long does it take?',
                'Can you show me examples?',
                'What about hosting?'
            ],
            'marketing': [
                'Tell me about SEO packages',
                'Do you manage Google Ads?',
                'What results can I expect?',
                'How do you track success?'
            ],
            'content-writing': [
                'How many blogs per month?',
                'What topics do you cover?',
                'Do you include images?',
                'Can I review before publishing?'
            ],
            'default': [
                'Tell me more',
                'What are the prices?',
                'How do I get started?',
                'Can I schedule a call?'
            ]
        };
        
        return suggestions[context] || suggestions['default'];
    }
    
    // Handle file uploads
    $(document).on('change', '#file-upload', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Check file size (10MB limit)
        if (file.size > 10 * 1024 * 1024) {
            alert('File too large. Maximum size is 10MB');
            return;
        }
        
        const formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'upload_chatbot_media');
        formData.append('nonce', openai_chatbot_data.nonce);
        
        // Show uploading indicator
        addMessage('You', 'Uploading file...');
        
        $.ajax({
            url: openai_chatbot_data.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let message = '';
                    
                    if (data.type === 'image') {
                        message = `<img src="${data.url}" alt="${data.name}" style="max-width: 100%; height: auto; border-radius: 10px; margin: 10px 0;">`;
                    } else {
                        message = `File uploaded: <a href="${data.url}" target="_blank">${data.name}</a>`;
                    }
                    
                    // Replace uploading message
                    $('#chat-messages .message:last-child').html('<strong>You:</strong> ' + message);
                    
                    // Track file upload
                    trackEvent('file_uploaded', {
                        type: data.type,
                        size: data.size
                    });
                } else {
                    $('#chat-messages .message:last-child').html('<strong>Error:</strong> ' + response.data);
                }
            },
            error: function() {
                $('#chat-messages .message:last-child').html('<strong>Error:</strong> Failed to upload file');
            }
        });
        
        // Clear file input
        $(this).val('');
    });
    
    // Handle chat rating
    $(document).on('click', '#rate-chat', function() {
        const ratingHTML = `
            <div class="rating-modal">
                <div class="rating-content">
                    <h3>How was your experience?</h3>
                    <div class="rating-stars">
                        <span class="star" data-rating="1">★</span>
                        <span class="star" data-rating="2">★</span>
                        <span class="star" data-rating="3">★</span>
                        <span class="star" data-rating="4">★</span>
                        <span class="star" data-rating="5">★</span>
                    </div>
                    <textarea id="rating-feedback" placeholder="Any feedback? (optional)"></textarea>
                    <button id="submit-rating" class="chatbot-button">Submit</button>
                    <button id="cancel-rating" class="chatbot-button">Cancel</button>
                </div>
            </div>
        `;
        
        $('body').append(ratingHTML);
        
        let selectedRating = 0;
        
        $('.star').on('click', function() {
            selectedRating = parseInt($(this).data('rating'));
            $('.star').removeClass('selected');
            for (let i = 0; i < selectedRating; i++) {
                $('.star').eq(i).addClass('selected');
            }
        });
        
        $('#submit-rating').on('click', function() {
            if (selectedRating > 0) {
                const feedback = $('#rating-feedback').val();
                trackEvent('satisfaction_rating', {
                    rating: selectedRating,
                    feedback: feedback
                });
                $('.rating-modal').remove();
                $('#rate-chat').hide();
                addMessage('System', 'Thank you for your feedback!');
            } else {
                alert('Please select a rating');
            }
        });
        
        $('#cancel-rating').on('click', function() {
            $('.rating-modal').remove();
        });
    });
    
    // Initialize proactive engagement if available
    if (window.ChatbotProactive) {
        window.ChatbotProactive.init();
    }
});