/**
 * Proactive engagement features for OpenAI Chatbot
 */

(function($) {
    'use strict';
    
    let exitIntentShown = false;
    let scrollDepthReached = false;
    let timeOnPageShown = false;
    
    // Export to global
    window.ChatbotProactive = {
        init: function() {
            // Exit intent detection
            setupExitIntent();
            
            // Scroll depth tracking
            setupScrollDepth();
            
            // Time-based triggers
            setupTimeTriggers();
        }
    };
    
    // Exit intent detection
    function setupExitIntent() {
        $(document).on('mouseleave', function(e) {
            if (e.clientY <= 0 && !exitIntentShown && !isChatOpen()) {
                exitIntentShown = true;
                showProactiveMessage('Wait! Before you go, can I help you with anything?');
                trackEvent('exit_intent_triggered');
            }
        });
    }
    
    // Scroll depth tracking
    function setupScrollDepth() {
        let maxScroll = 0;
        
        $(window).on('scroll', function() {
            const scrollPercent = ($(window).scrollTop() / ($(document).height() - $(window).height())) * 100;
            
            if (scrollPercent > maxScroll) {
                maxScroll = scrollPercent;
            }
            
            // Trigger at 75% scroll depth
            if (scrollPercent > 75 && !scrollDepthReached && !isChatOpen()) {
                scrollDepthReached = true;
                showProactiveMessage('I see you\'re exploring our content. Any questions I can answer?');
                trackEvent('scroll_depth_triggered', { depth: 75 });
            }
        });
    }
    
    // Time-based triggers
    function setupTimeTriggers() {
        // Show after 30 seconds on page
        setTimeout(function() {
            if (!timeOnPageShown && !isChatOpen()) {
                timeOnPageShown = true;
                showProactiveMessage('Hi there! I\'m here if you need any assistance.');
                trackEvent('time_trigger_30s');
            }
        }, 30000);
        
        // Show after 2 minutes of inactivity
        let inactivityTimer;
        let lastActivity = Date.now();
        
        function resetInactivityTimer() {
            lastActivity = Date.now();
            clearTimeout(inactivityTimer);
            
            inactivityTimer = setTimeout(function() {
                if (!isChatOpen()) {
                    showProactiveMessage('Still browsing? Let me know if you need help finding something specific.');
                    trackEvent('inactivity_trigger', { duration: 120 });
                }
            }, 120000); // 2 minutes
        }
        
        $(document).on('mousemove keypress', resetInactivityTimer);
        resetInactivityTimer();
    }
    
    // Show proactive message
    function showProactiveMessage(message) {
        const proactiveHtml = `
            <div class="proactive-message">
                <div class="proactive-content">
                    <button class="proactive-close">×</button>
                    <p>${message}</p>
                    <button class="proactive-chat-btn">Start Chat</button>
                </div>
            </div>
        `;
        
        $('body').append(proactiveHtml);
        
        $('.proactive-message').fadeIn(300);
        
        // Handle close button
        $('.proactive-close').on('click', function() {
            $('.proactive-message').fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Handle chat button
        $('.proactive-chat-btn').on('click', function() {
            $('.proactive-message').remove();
            $(document).trigger('openai-chatbot:open');
        });
        
        // Auto-hide after 10 seconds
        setTimeout(function() {
            $('.proactive-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 10000);
    }
    
    // Check if chat is open
    function isChatOpen() {
        return $('#openai-chatbot').hasClass('open');
    }
    
    // Track event wrapper
    function trackEvent(eventType, data) {
        if (window.OpenAIChatbot && window.OpenAIChatbot.trackEvent) {
            window.OpenAIChatbot.trackEvent(eventType, data);
        }
    }
    
})(jQuery);