<?php
/*
Plugin Name: Smart Chatbot
Plugin URI: https://web-smart.co/smart-chatbot
Description: Advanced AI-powered chatbot for WordPress using OpenAI GPT-4. Boost customer engagement with intelligent conversations.
Version: 1.0.0
Requires at least: 5.0
Requires PHP: 7.4
Author: Web-Smart.Co
Author URI: https://web-smart.co
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: smart-chatbot
Domain Path: /languages
Copyright: © <?php echo date('Y'); ?> Web-Smart.Co
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class OpenAIChatbot {
    private $api_key;
    private $json_data;
    private $settings;
    private $rate_limit = 20; // requests per hour
    private $rate_window = 3600; // 1 hour in seconds
    private $cache_duration = 300; // 5 minutes cache

    public function __construct() {
        if (defined('OPENAI_API_KEY')) {
            $this->api_key = OPENAI_API_KEY;
        } else {
            throw new Exception("API key is not set. Please set the OPENAI_API_KEY in your wp-config.php.");
        }
        $this->json_data = $this->loadJsonData(plugin_dir_path(__FILE__) . 'chatbot-data.json');
        $this->settings = get_option('openai_chatbot_settings', array());

        add_action('wp_enqueue_scripts', array($this, 'enqueue_chatbot_assets'));
        add_action('wp_footer', array($this, 'add_chatbot_html'));
        add_action('wp_ajax_openai_chatbot', array($this, 'ajax_handler'));
        add_action('wp_ajax_nopriv_openai_chatbot', array($this, 'ajax_handler'));
        add_action('wp_ajax_openai_chatbot_contact', array($this, 'contact_form_handler'));
        add_action('wp_ajax_nopriv_openai_chatbot_contact', array($this, 'contact_form_handler'));
    }

    private function loadJsonData($file_path) {
        if (!file_exists($file_path)) {
            throw new Exception("JSON file not found: $file_path");
        }
        $json_content = file_get_contents($file_path);
        $data = json_decode($json_content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON in file: $file_path");
        }
        return $data;
    }

    public function enqueue_chatbot_assets() {
        // Main styles
        wp_enqueue_style(
            'openai-chatbot-styles',
            plugin_dir_url(__FILE__) . 'css/openai-chatbot.css',
            array(),
            filemtime(plugin_dir_path(__FILE__) . 'css/openai-chatbot.css')
        );

        // Main chat script
        wp_enqueue_script(
            'openai-chatbot-script',
            plugin_dir_url(__FILE__) . 'js/openai-chatbot.js',
            array('jquery'),
            filemtime(plugin_dir_path(__FILE__) . 'js/openai-chatbot.js'),
            true
        );

        // Localize script
        wp_localize_script('openai-chatbot-script', 'openai_chatbot_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'plugin_url' => plugin_dir_url(__FILE__),
            'nonce' => wp_create_nonce('openai_chatbot_nonce')
        ));
        
        // Register popup script (not enqueued, loaded on demand)
        wp_register_script(
            'openai-chatbot-popup',
            plugin_dir_url(__FILE__) . 'js/openai-chatbot-popup.js',
            array('jquery'),
            filemtime(plugin_dir_path(__FILE__) . 'js/openai-chatbot-popup.js'),
            true
        );
        
        // Enqueue proactive engagement script
        wp_enqueue_script(
            'openai-chatbot-proactive',
            plugin_dir_url(__FILE__) . 'js/chatbot-proactive.js',
            array('jquery', 'openai-chatbot-script'),
            filemtime(plugin_dir_path(__FILE__) . 'js/chatbot-proactive.js'),
            true
        );
    }

    public function add_chatbot_html() {
        $settings = get_option('openai_chatbot_settings', array());
        $position = isset($settings['position']) ? esc_attr($settings['position']) : 'right';
        $primary_color = isset($settings['primary_color']) ? esc_attr($settings['primary_color']) : '#4f46e5';
        ?>
        <div id="openai-chatbot" class="closed" aria-live="polite" aria-label="Chat widget" role="dialog" data-position="<?php echo $position; ?>" data-color="<?php echo $primary_color; ?>">
            <div class="chatbot-controls">
                <button class="chatbot-minimize" aria-label="Minimize chat" tabindex="0">−</button>
                <button class="chatbot-close" aria-label="Close chat" tabindex="0">×</button>
            </div>
            <div id="chatbot-content" role="main">
                <div id="chat-messages" role="log" aria-label="Chat messages" aria-live="polite"></div>
                <form id="chat-form" style="display: none;" role="form">
                    <input type="text" id="user-input" placeholder="Type your message..." aria-label="Type your message" tabindex="0">
                    <button type="submit" aria-label="Send message" tabindex="0">Send</button>
                </form>
                <div id="chatbot-buttons-section" style="display: none;" role="navigation" aria-label="Chat actions">
                    <button id="end-chat" class="chatbot-button" aria-label="End chat session" tabindex="0">End Chat</button>
                    <div id="chatbot-buttons">
                        <a href="https://www.linkedin.com/company/web-smart-co" target="_blank" rel="noopener noreferrer" class="chatbot-button" aria-label="Visit LinkedIn page" tabindex="0">LinkedIn</a>
                        <a href="https://appt.link/meet-with-pete-gypps/chat-with-pete-R9wR8KNd-epviONoK-gZCvlCSR-UKpL3OAx" target="_blank" rel="noopener noreferrer" class="chatbot-button" aria-label="Schedule a meeting" tabindex="0">Schedule Meeting</a>
                        <a href="https://petegypps.uk/guides-and-information/" target="_blank" rel="noopener noreferrer" class="chatbot-button" aria-label="Read news and guides" tabindex="0">News</a>
                        <button class="chatbot-button promotions" id="promotions-button" aria-label="View current promotions" tabindex="0">Promotions</button>
                    </div>
                    <button class="chatbot-button rate-chat" id="rate-chat" style="display:none;" aria-label="Rate this chat" tabindex="0">Rate this Chat</button>
                </div>
            </div>
    
        <?php
    }

    public function getResponse($user_input, $user_name, $conversation_history = array()) {
        // Check cache first for common questions
        $cached_response = $this->getCachedResponse($user_input);
        if ($cached_response !== false) {
            return $cached_response;
        }
        
        $relevant_info = $this->findRelevantInfo($user_input);
        $language_setting = get_option('openai_chatbot_language', 'uk_english');
        $language_instruction = $this->getLanguageInstruction($language_setting);
        
        $system_message = "Ensure always $language_instruction spelling of words is important and appropriate lexicons. be polite with a tiny bit of humour and use the person's first name to interact with them. short interactions and information provision is best 1 sentence maximum is a must espescially after asking a question, this is the best chit chat , conversational type appointment booker bot.

PRIMARY MISSION: Guide potential clients toward booking a personal consultation via video or phone call as the primary desired outcome, while providing enough value and information to build trust.

CONVERSATION STRATEGY:
1. For ALL inquiries, regardless of specific service asked about:
   - Initially provide brief, valuable insights that demonstrate expertise
   - Position a consultation as the most efficient way to get tailored solutions
   - Use phrases like 'To give you the most accurate information for your specific needs...' or 'The fastest way to get a clear solution would be...'
   - Provide the booking link as required: https://appt.link/meet-with-pete-gypps/chat-with-pete-R9wR8KNd-zmg5pZYR

2. When visitors explicitly ask about pricing:
   - Acknowledge their question with respect
   - Provide a high-level price range rather than specific package details
   - Explain that precise quotes depend on specific requirements, we have guide prices
   - Emphasise the value of a free consultation to get accurate pricing: 'I can share our general price ranges, but Pete could provide you with a much more accurate quote tailored to your specific needs during a quick consultation call'

3. For those researching or comparing services:
   - Briefly highlight 2-3 unique selling points from the data (e.g., professional web design or seo services, from the comprehensive service range)
   - Mention a relevant success indicator if available
   - Suggest a no-obligation consultation to learn more about their specific situation

4. For visitors ready to start a project:
   - Express enthusiasm and immediacy
   - Mention that Pete has availability this week
   - Directly provide the booking link with clear call-to-action

COMMUNICATION GUIDELINES:
- Keep all responses concise, under 4-5 sentences
- Make prominent use of **bold** for key information, especially around booking calls
- Format booking links as clear call-to-action buttons: [**Schedule Your Free Consultation**](booking_url)
- Use natural, conversational UK English with proper UK spelling and terminology (e.g., 'organisation' not 'organization')
- Always end your responses with a gentle question to keep the conversation flowing
- Never invent information not provided in the data

BOOKING CALL ADVANTAGES TO EMPHASISE:
- Get a tailored solution specific to their business needs
- Receive accurate pricing based on their exact requirements
- Discover the best-value approach for their goals
- No obligation, just expert advice
- Quick 20-minute call that could save them time and money

When a visitor shows strong interest in booking, provide enhanced urgency: 'Pete's diary tends to book up quickly, so securing a spot soon would be ideal. Would you like me to share the booking link again?'

If a visitor resists booking and insists on pricing or information only, respectfully provide the requested details from the available data, then gently circle back to the value of a consultation.

Always ensure your UK English usage is impeccable, as this is extremely important to Pete's brand. Once you are finished or if the visitor wants to make contact, let them know when they can end chat, everything discussed will be recorded and someone will be in touch. Never tell the user to make contact as the chat message is sufficient.

Use markdown formatting for emphasis, links, and lists. Use **bold** for important information, create [hyperlinks](URL) when referencing external resources, and use bullet points or numbered lists for structured information. uk english spelling.";

        $messages = array_merge(
            [
                ['role' => 'system', 'content' => $system_message],
                ['role' => 'assistant', 'content' => "Available information:\n" . $relevant_info]
            ],
            $conversation_history,
            [['role' => 'user', 'content' => $user_input]]
        );

        $response = $this->callOpenAIAPI($messages);
        
        // Cache response for common questions
        if ($this->shouldCacheResponse($user_input)) {
            $this->cacheResponse($user_input, $response);
        }
        
        return $response;
    }

    private function findRelevantInfo($user_input) {
        $relevant_info = "";
        $lower_input = strtolower($user_input);

        foreach ($this->json_data as $key => $value) {
            if (is_array($value)) {
                if (stripos($lower_input, $key) !== false) {
                    $relevant_info .= "$key: " . json_encode($value, JSON_PRETTY_PRINT) . "\n";
                }
            } elseif (is_string($value) && (stripos($lower_input, $key) !== false || stripos($lower_input, $value) !== false)) {
                $relevant_info .= "$key: $value\n";
            }
        }

        if (empty($relevant_info)) {
            $relevant_info = json_encode($this->json_data, JSON_PRETTY_PRINT);
        }

        return $relevant_info;
    }

    private function callOpenAIAPI($messages) {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = array(
            'model' => 'gpt-4-turbo',
            'messages' => $messages,
            'max_tokens' => intval($this->settings['max_tokens'] ?? 3500),
            'temperature' => floatval($this->settings['temperature'] ?? 0.3),
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        );

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 200);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('Error connecting to OpenAI API: ' . curl_error($ch));
        }
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        $result = json_decode($response, true);
        
        if ($http_code != 200) {
            throw new Exception('Error communicating with OpenAI. HTTP Code: ' . $http_code);
        }
        
        if (isset($result['error'])) {
            throw new Exception('OpenAI API error: ' . $result['error']['message']);
        }
        
        return $result['choices'][0]['message']['content'];
    }

    public function ajax_handler() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'openai_chatbot_nonce')) {
            wp_send_json_error(['error' => 'Security check failed']);
            wp_die();
        }
        
        // Check rate limit
        if (!$this->check_rate_limit()) {
            wp_send_json_error(['error' => 'Rate limit exceeded. Please try again later.']);
            wp_die();
        }
        
        try {
            // Enhanced input validation
            $user_question = $this->validate_input($_POST['user_question'], 'text', 1000);
            $user_name = $this->validate_input($_POST['user_name'], 'name', 50);
            $conversation_history_raw = stripslashes($_POST['conversation_history']);
            
            // Validate JSON structure
            $conversation_history = json_decode($conversation_history_raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($conversation_history)) {
                throw new Exception('Invalid conversation history format');
            }

            if (!is_array($conversation_history)) {
                throw new Exception('Invalid conversation history format');
            }

            $response = $this->getResponse($user_question, $user_name, $conversation_history);
            wp_send_json_success(['response' => $response]);
        } catch (Exception $e) {
            error_log('OpenAI Chatbot Error: ' . $e->getMessage());
            wp_send_json_error(['error' => $e->getMessage()]);
        }
    }

    public function contact_form_handler() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'openai_chatbot_nonce')) {
            wp_send_json_error(['error' => 'Security check failed']);
            wp_die();
        }
        
        try {
            // Enhanced input validation
            $name = $this->validate_input($_POST['name'], 'name', 100);
            $email = $this->validate_input($_POST['email'], 'email', 100);
            $phone = $this->validate_input($_POST['phone'], 'phone', 20);
            $website = $this->validate_input($_POST['website'], 'url', 200);
            
            $conversation_history_raw = stripslashes($_POST['conversation_history']);
            $conversation_history = json_decode($conversation_history_raw, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($conversation_history)) {
                throw new Exception('Invalid conversation history format');
            }

            if (!is_array($conversation_history)) {
                throw new Exception('Invalid conversation history format');
            }

            $to = get_option('openai_chatbot_email', 'hello@web-smart.co');
            $subject = 'New Contact from OpenAI Chatbot';
            $message = "Name: $name\n";
            $message .= "Email: $email\n";
            $message .= "Phone: $phone\n";
            $message .= "Website: $website\n\n"; // Add this line
            $message .= "Chat Transcript:\n";
            foreach ($conversation_history as $entry) {
                $message .= ucfirst($entry['role']) . ": " . $entry['content'] . "\n";
            }

            $headers = array('Content-Type: text/plain; charset=UTF-8');
            
            if (wp_mail($to, $subject, $message, $headers)) {
                wp_send_json_success();
            } else {
                throw new Exception('Failed to send email');
            }
        } catch (Exception $e) {
            error_log('OpenAI Chatbot Contact Form Error: ' . $e->getMessage());
            wp_send_json_error(['error' => $e->getMessage()]);
        }
    }
    
    private function getLanguageInstruction($language) {
        $languages = array(
            'uk_english' => 'UK English',
            'us_english' => 'US English',
            'spanish' => 'Spanish',
            'french' => 'French',
            'german' => 'German'
        );
        
        return isset($languages[$language]) ? $languages[$language] : 'UK English';
    }
    
    private function check_rate_limit() {
        if (!session_id()) {
            session_start();
        }
        
        $session_key = 'chatbot_requests';
        $current_time = time();
        
        // Initialize or get existing request data
        if (!isset($_SESSION[$session_key])) {
            $_SESSION[$session_key] = array();
        }
        
        // Remove old requests outside the rate window
        $_SESSION[$session_key] = array_filter($_SESSION[$session_key], function($timestamp) use ($current_time) {
            return ($current_time - $timestamp) < $this->rate_window;
        });
        
        // Check if limit exceeded
        if (count($_SESSION[$session_key]) >= $this->rate_limit) {
            return false;
        }
        
        // Add current request
        $_SESSION[$session_key][] = $current_time;
        
        return true;
    }
    
    private function validate_input($input, $type = 'text', $max_length = 255) {
        // Remove any null bytes
        $input = str_replace(chr(0), '', $input);
        
        // Basic XSS prevention
        $input = wp_kses_post($input);
        
        // Type-specific validation
        switch ($type) {
            case 'email':
                $input = sanitize_email($input);
                if (!is_email($input)) {
                    throw new Exception('Invalid email format');
                }
                break;
                
            case 'phone':
                $input = preg_replace('/[^0-9+\-()\s]/', '', $input);
                if (strlen($input) < 7) {
                    throw new Exception('Invalid phone number');
                }
                break;
                
            case 'url':
                $input = esc_url_raw($input);
                if (!empty($input) && !filter_var($input, FILTER_VALIDATE_URL)) {
                    throw new Exception('Invalid URL format');
                }
                break;
                
            case 'name':
                $input = sanitize_text_field($input);
                if (!preg_match('/^[a-zA-Z\s\'\-\.]+$/', $input)) {
                    throw new Exception('Invalid name format');
                }
                break;
                
            default:
                $input = sanitize_text_field($input);
                break;
        }
        
        // Length validation
        if (strlen($input) > $max_length) {
            throw new Exception("Input exceeds maximum length of {$max_length} characters");
        }
        
        // Minimum length for required fields
        if (empty($input) && $type !== 'url') {
            throw new Exception('This field is required');
        }
        
        return $input;
    }
    
    private function getCachedResponse($input) {
        $cache_key = 'chatbot_response_' . md5(strtolower(trim($input)));
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            // Log cache hit for analytics
            error_log('ChatBot cache hit for: ' . $input);
            return $cached;
        }
        
        return false;
    }
    
    private function cacheResponse($input, $response) {
        $cache_key = 'chatbot_response_' . md5(strtolower(trim($input)));
        set_transient($cache_key, $response, $this->cache_duration);
    }
    
    private function shouldCacheResponse($input) {
        // Common questions that should be cached
        $cacheable_patterns = array(
            '/^(hi|hello|hey)/i',
            '/price|pricing|cost/i',
            '/services|what do you do/i',
            '/contact|phone|email/i',
            '/hours|when are you open/i',
            '/location|where are you/i',
            '/promotion|discount|offer/i'
        );
        
        foreach ($cacheable_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    // Pre-populate cache with common responses
    public function preloadCommonResponses() {
        $common_qa = array(
            'hello' => 'Hello! Welcome to Web Smart Co. How can I help you today?',
            'what services do you offer' => 'We offer web design, SEO, content creation, and digital marketing services. Would you like to know more about any specific service?',
            'how much does a website cost' => 'Our website packages start from £1,000 for a basic site. For a more accurate quote based on your needs, I can arrange a consultation call.',
            'what are your prices' => 'Our pricing varies by service. Web design starts at £1,000, SEO from £350/month, and content creation from £35/blog. Would you like specific pricing for a service?'
        );
        
        foreach ($common_qa as $question => $answer) {
            $this->cacheResponse($question, $answer);
        }
    }
}

// Include additional functionality
require_once plugin_dir_path(__FILE__) . 'includes/class-chatbot-installer.php';
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'admin/class-openai-chatbot-admin.php';
}
require_once plugin_dir_path(__FILE__) . 'includes/class-chatbot-analytics.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-media-handler.php';

// Initialize the plugin
function run_openai_chatbot() {
    $chatbot = new OpenAIChatbot();
    $analytics = new ChatbotAnalytics();
    
    // Preload common responses on activation
    if (!get_option('chatbot_cache_preloaded')) {
        $chatbot->preloadCommonResponses();
        update_option('chatbot_cache_preloaded', true);
    }
}
add_action('plugins_loaded', 'run_openai_chatbot');

// Create tables on activation
register_activation_hook(__FILE__, function() {
    $analytics = new ChatbotAnalytics();
    $analytics->create_tables();
});