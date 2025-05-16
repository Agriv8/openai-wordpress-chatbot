<?php
/**
 * Admin page functionality for OpenAI Chatbot plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class OpenAIChatbotAdmin {
    private $api_key;
    private $json_file_path;
    
    public function __construct() {
        $this->api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
        $this->json_file_path = plugin_dir_path(dirname(__FILE__)) . 'chatbot-data.json';
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_test_openai_api', array($this, 'test_api_connection'));
        add_action('wp_ajax_expand_content_ai', array($this, 'expand_content_with_ai'));
        add_action('wp_ajax_save_chatbot_settings', array($this, 'save_settings'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Smart Chatbot',
            'Smart Chatbot',
            'manage_options',
            'smart-chatbot',
            array($this, 'admin_page'),
            'dashicons-format-chat',
            30
        );
    }
    
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'toplevel_page_smart-chatbot') {
            return;
        }
        
        wp_enqueue_style(
            'openai-chatbot-admin',
            plugin_dir_url(dirname(__FILE__)) . 'admin/css/admin.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'openai-chatbot-admin',
            plugin_dir_url(dirname(__FILE__)) . 'admin/js/admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('openai-chatbot-admin', 'openai_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('openai_admin_nonce')
        ));
    }
    
    public function admin_page() {
        $current_data = $this->get_current_data();
        ?>
        <div class="wrap">
            <h1>Smart Chatbot Configuration</h1>
            
            <!-- API Key Section -->
            <div class="card">
                <h2>OpenAI API Configuration</h2>
                <p>Add your OpenAI API key to <code>wp-config.php</code>:</p>
                <pre>define('OPENAI_API_KEY', 'sk-your-api-key-here');</pre>
                
                <div class="api-status">
                    <p>Current Status: <span id="api-status">Checking...</span></p>
                    <button type="button" class="button" id="test-api">Test API Connection</button>
                </div>
            </div>
            
            <!-- Company Configuration -->
            <div class="card">
                <h2>Company Configuration</h2>
                <form id="chatbot-settings-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">Company Name</th>
                            <td>
                                <input type="text" id="company-name" name="company" 
                                       value="<?php echo esc_attr($current_data['company'] ?? ''); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Language</th>
                            <td>
                                <select id="language" name="language">
                                    <option value="uk_english" selected>UK English</option>
                                    <option value="us_english">US English</option>
                                    <option value="spanish">Spanish</option>
                                    <option value="french">French</option>
                                    <option value="german">German</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Email Recipient</th>
                            <td>
                                <input type="email" id="email-recipient" name="email_recipient" 
                                       value="<?php echo esc_attr(get_option('openai_chatbot_email', 'hello@web-smart.co')); ?>" 
                                       class="regular-text" />
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            
            <!-- Service Data Configuration -->
            <div class="card">
                <h2>Service Data Configuration</h2>
                <p>Enter the information your chatbot can discuss with clients:</p>
                
                <div class="service-editor">
                    <label for="service-data">Service Information (JSON format):</label>
                    <textarea id="service-data" rows="20" class="large-text code">
<?php echo esc_textarea(json_encode($current_data, JSON_PRETTY_PRINT)); ?>
                    </textarea>
                    
                    <div class="ai-controls">
                        <button type="button" class="button" id="expand-with-ai">
                            Expand with AI
                        </button>
                        <label>
                            <input type="checkbox" id="auto-expand" />
                            Automatically expand content with AI
                        </label>
                    </div>
                    
                    <div id="ai-preview" style="display:none;">
                        <h3>AI-Enhanced Version</h3>
                        <textarea id="ai-enhanced-data" rows="20" class="large-text code" readonly></textarea>
                        <button type="button" class="button button-primary" id="apply-ai-version">
                            Apply AI Version
                        </button>
                        <button type="button" class="button" id="cancel-ai-version">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Save Button -->
            <p class="submit">
                <button type="button" class="button button-primary" id="save-settings">
                    Save All Settings
                </button>
                <span class="spinner"></span>
                <span id="save-message"></span>
            </p>
            
            <!-- Footer -->
            <div class="chatbot-footer">
                <p>Built by <a href="https://web-smart.co" target="_blank">Web-Smart.Co</a> &copy; <?php echo date('Y'); ?></p>
            </div>
        </div>
        <?php
    }
    
    private function get_current_data() {
        if (file_exists($this->json_file_path)) {
            $content = file_get_contents($this->json_file_path);
            return json_decode($content, true) ?? array();
        }
        return array();
    }
    
    public function test_api_connection() {
        check_ajax_referer('openai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        if (empty($this->api_key)) {
            wp_send_json_error('API key not configured - Please add OPENAI_API_KEY to wp-config.php');
            return;
        }
        
        $url = 'https://api.openai.com/v1/models';
        $headers = array(
            'Authorization: Bearer ' . $this->api_key
        );
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            wp_send_json_success('API connection successful');
        } else {
            wp_send_json_error('API connection failed. HTTP code: ' . $http_code);
        }
    }
    
    public function expand_content_with_ai() {
        check_ajax_referer('openai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $content = json_decode(stripslashes($_POST['content']), true);
        
        if (!$content) {
            wp_send_json_error('Invalid JSON data');
            return;
        }
        
        $prompt = "You are helping to create content for an AI chatbot's knowledge base. Take this business information and expand it with more detailed descriptions, benefits, and selling points. Maintain the same JSON structure but make the content more comprehensive and compelling. Keep UK English spelling. Here's the current content:\n\n" . json_encode($content, JSON_PRETTY_PRINT);
        
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = array(
            'model' => 'gpt-4-turbo',
            'messages' => array(
                array('role' => 'system', 'content' => 'You are a helpful assistant that expands business information for chatbots.'),
                array('role' => 'user', 'content' => $prompt)
            ),
            'temperature' => 0.7,
            'max_tokens' => 3000
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            $enhanced_content = $result['choices'][0]['message']['content'];
            
            // Try to extract JSON from the response
            if (preg_match('/```json\s*(.*?)\s*```/s', $enhanced_content, $matches)) {
                $enhanced_content = $matches[1];
            }
            
            $enhanced_json = json_decode($enhanced_content, true);
            
            if ($enhanced_json) {
                wp_send_json_success(json_encode($enhanced_json, JSON_PRETTY_PRINT));
            } else {
                wp_send_json_error('Failed to parse enhanced content as JSON');
            }
        } else {
            wp_send_json_error('API request failed');
        }
    }
    
    public function save_settings() {
        check_ajax_referer('openai_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $settings = json_decode(stripslashes($_POST['settings']), true);
        
        if (!$settings) {
            wp_send_json_error('Invalid settings data');
            return;
        }
        
        // Save email recipient
        if (isset($settings['email_recipient'])) {
            update_option('openai_chatbot_email', sanitize_email($settings['email_recipient']));
        }
        
        // Save language preference
        if (isset($settings['language'])) {
            update_option('openai_chatbot_language', sanitize_text_field($settings['language']));
        }
        
        // Save service data
        if (isset($settings['service_data'])) {
            $service_data = json_decode($settings['service_data'], true);
            
            if ($service_data === null) {
                wp_send_json_error('Invalid JSON in service data');
                return;
            }
            
            // Update company name if provided
            if (isset($settings['company'])) {
                $service_data['company'] = sanitize_text_field($settings['company']);
            }
            
            $json_string = json_encode($service_data, JSON_PRETTY_PRINT);
            
            if (file_put_contents($this->json_file_path, $json_string) === false) {
                wp_send_json_error('Failed to save JSON file');
                return;
            }
        }
        
        wp_send_json_success('Settings saved successfully');
    }
}

// Initialize admin class
new OpenAIChatbotAdmin();