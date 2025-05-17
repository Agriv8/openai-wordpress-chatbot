<?php
/**
 * WhatsApp Business Integration
 * 
 * Handles live agent handoff and message synchronization
 * between website chat and WhatsApp Business
 */

if (!defined('ABSPATH')) {
    exit;
}

class WhatsAppIntegration {
    private $api_key;
    private $phone_number;
    private $webhook_url;
    private $chatbot_instance;
    
    public function __construct($chatbot_instance) {
        $this->chatbot_instance = $chatbot_instance;
        $this->load_settings();
        
        // Hook WordPress actions
        add_action('wp_ajax_request_live_agent', array($this, 'handle_live_agent_request'));
        add_action('wp_ajax_nopriv_request_live_agent', array($this, 'handle_live_agent_request'));
        add_action('wp_ajax_whatsapp_webhook', array($this, 'handle_webhook'));
        add_action('wp_ajax_nopriv_whatsapp_webhook', array($this, 'handle_webhook'));
    }
    
    /**
     * Load WhatsApp settings from database
     */
    private function load_settings() {
        $settings = get_option('openai_chatbot_whatsapp_settings', array());
        $this->api_key = $settings['api_key'] ?? '';
        $this->phone_number = $settings['phone_number'] ?? '';
        $this->webhook_url = site_url('/wp-admin/admin-ajax.php?action=whatsapp_webhook');
    }
    
    /**
     * Handle live agent request from website chat
     */
    public function handle_live_agent_request() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'openai_chatbot_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_name = sanitize_text_field($_POST['user_name']);
        $user_phone = sanitize_text_field($_POST['user_phone']);
        $conversation_history = json_decode(stripslashes($_POST['conversation_history']), true);
        
        // Check agent availability
        $available = $this->check_agent_availability();
        
        if ($available) {
            // Create WhatsApp session
            $whatsapp_url = $this->create_whatsapp_session($session_id, $user_name, $user_phone, $conversation_history);
            
            wp_send_json_success(array(
                'available' => true,
                'whatsapp_url' => $whatsapp_url,
                'message' => 'An agent is available. Click the link to continue on WhatsApp.'
            ));
        } else {
            wp_send_json_success(array(
                'available' => false,
                'message' => 'No agents available right now. Would you like to schedule a callback?',
                'next_available' => $this->get_next_available_time()
            ));
        }
    }
    
    /**
     * Check if live agents are available
     */
    private function check_agent_availability() {
        $settings = get_option('openai_chatbot_whatsapp_settings', array());
        $hours = $settings['agent_hours'] ?? '9-17'; // 9 AM to 5 PM default
        
        list($start, $end) = explode('-', $hours);
        $current_hour = date('G');
        
        return $current_hour >= $start && $current_hour < $end;
    }
    
    /**
     * Create WhatsApp session and return connection URL
     */
    private function create_whatsapp_session($session_id, $user_name, $user_phone, $conversation_history) {
        // Format conversation history for WhatsApp
        $context = $this->format_conversation_context($conversation_history);
        
        // Generate WhatsApp deep link
        $message = urlencode("Hi, I'm $user_name. I was chatting with your AI assistant and would like to speak with a human agent. Session ID: $session_id\n\nConversation context:\n$context");
        
        $whatsapp_url = "https://wa.me/{$this->phone_number}?text=$message";
        
        // Store session data for synchronization
        $this->store_session_data($session_id, array(
            'user_name' => $user_name,
            'user_phone' => $user_phone,
            'conversation_history' => $conversation_history,
            'whatsapp_connected' => true,
            'created_at' => current_time('mysql')
        ));
        
        return $whatsapp_url;
    }
    
    /**
     * Format conversation history for WhatsApp message
     */
    private function format_conversation_context($conversation_history) {
        $context = '';
        $recent_messages = array_slice($conversation_history, -5); // Last 5 messages
        
        foreach ($recent_messages as $message) {
            $role = $message['role'] === 'user' ? 'Customer' : 'AI';
            $content = wp_trim_words($message['content'], 20);
            $context .= "$role: $content\n";
        }
        
        return $context;
    }
    
    /**
     * Handle incoming WhatsApp webhook
     */
    public function handle_webhook() {
        // Verify webhook signature
        if (!$this->verify_webhook_signature()) {
            wp_die('Unauthorized', 401);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['message'])) {
            $this->process_incoming_message($data['message']);
        }
        
        wp_send_json_success();
    }
    
    /**
     * Process incoming WhatsApp message
     */
    private function process_incoming_message($message) {
        $session_id = $this->extract_session_id($message['text']);
        $agent_message = $this->extract_agent_message($message['text']);
        
        if ($session_id && $agent_message) {
            // Store message in database
            $this->store_message($session_id, $agent_message, 'agent');
            
            // Push to website chat via Server-Sent Events or WebSocket
            $this->push_to_website($session_id, $agent_message);
        }
    }
    
    /**
     * Push message to website chat
     */
    private function push_to_website($session_id, $message) {
        // Store in transient for website to pick up
        set_transient('whatsapp_message_' . $session_id, array(
            'message' => $message,
            'timestamp' => time(),
            'from' => 'agent'
        ), 60); // Expire after 60 seconds
    }
    
    /**
     * Store session data
     */
    private function store_session_data($session_id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_sessions';
        
        $wpdb->replace(
            $table_name,
            array(
                'session_id' => $session_id,
                'data' => json_encode($data),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Store individual message
     */
    private function store_message($session_id, $message, $sender) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'chatbot_messages';
        
        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'message' => $message,
                'sender' => $sender,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get next available agent time
     */
    private function get_next_available_time() {
        $settings = get_option('openai_chatbot_whatsapp_settings', array());
        $hours = $settings['agent_hours'] ?? '9-17';
        
        list($start, $end) = explode('-', $hours);
        $current_hour = date('G');
        
        if ($current_hour >= $end) {
            // Next day
            return "tomorrow at {$start}:00 AM";
        } else {
            // Later today
            return "today at {$start}:00 AM";
        }
    }
    
    /**
     * Verify webhook signature for security
     */
    private function verify_webhook_signature() {
        $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
        $body = file_get_contents('php://input');
        
        $expected_signature = hash_hmac('sha256', $body, $this->api_key);
        
        return hash_equals($expected_signature, $signature);
    }
    
    /**
     * Extract session ID from WhatsApp message
     */
    private function extract_session_id($text) {
        if (preg_match('/Session ID: ([a-zA-Z0-9_-]+)/', $text, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    /**
     * Extract agent message from WhatsApp message
     */
    private function extract_agent_message($text) {
        // Remove session ID and get actual message
        $text = preg_replace('/Session ID: [a-zA-Z0-9_-]+/', '', $text);
        return trim($text);
    }
    
    /**
     * Create database tables for sessions and messages
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Sessions table
        $sessions_table = $wpdb->prefix . 'chatbot_sessions';
        $sql1 = "CREATE TABLE $sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            data longtext,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY session_id (session_id)
        ) $charset_collate;";
        
        // Messages table
        $messages_table = $wpdb->prefix . 'chatbot_messages';
        $sql2 = "CREATE TABLE $messages_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(100) NOT NULL,
            message text NOT NULL,
            sender varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }
}

// Initialize on plugin activation
register_activation_hook(__FILE__, array('WhatsAppIntegration', 'create_tables'));