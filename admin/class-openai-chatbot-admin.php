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
            
            <!-- Popup Configuration -->
            <div class="card">
                <h2>Popup Configuration</h2>
                <?php $popup_settings = get_option('openai_chatbot_popup_settings', array()); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Popup Style</th>
                        <td>
                            <select id="popup-style" name="popup_style">
                                <option value="minimizable" <?php selected($popup_settings['style'] ?? 'minimizable', 'minimizable'); ?>>Minimizable Widget</option>
                                <option value="classic" <?php selected($popup_settings['style'] ?? '', 'classic'); ?>>Classic Popup</option>
                            </select>
                        </td>
                    </tr>
                    
                    <!-- Classic Popup Settings -->
                    <tr class="classic-popup-settings">
                        <th scope="row">Popup Title</th>
                        <td>
                            <input type="text" name="popup_title" 
                                   value="<?php echo esc_attr($popup_settings['title'] ?? '🚀 Ready to Launch?'); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr class="classic-popup-settings">
                        <th scope="row">Popup Description</th>
                        <td>
                            <textarea name="popup_description" rows="3" class="large-text"><?php echo esc_textarea($popup_settings['description'] ?? "Let's build you a high-impact website that looks great and performs even better.<br><br>Want to learn how I can help your business grow?"); ?></textarea>
                        </td>
                    </tr>
                    <tr class="classic-popup-settings">
                        <th scope="row">Chat Button Text</th>
                        <td>
                            <input type="text" name="chat_button_text" 
                                   value="<?php echo esc_attr($popup_settings['chat_button'] ?? 'Ask the AI 🤖'); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr class="classic-popup-settings">
                        <th scope="row">Demo Button Text</th>
                        <td>
                            <input type="text" name="demo_button_text" 
                                   value="<?php echo esc_attr($popup_settings['demo_button'] ?? 'Book a Demo 👨‍💼'); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr class="classic-popup-settings">
                        <th scope="row">Demo Link URL</th>
                        <td>
                            <input type="url" name="demo_link" 
                                   value="<?php echo esc_attr($popup_settings['demo_link'] ?? 'https://appt.link/meet-with-pete-gypps/chat-with-pete-R9wR8KNd-zmg5pZYR'); ?>" 
                                   class="large-text" />
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Contact Form Configuration -->
            <div class="card">
                <h2>Contact Form Configuration</h2>
                <?php $form_settings = get_option('openai_chatbot_form_settings', array()); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Form Title</th>
                        <td>
                            <input type="text" name="form_title" 
                                   value="<?php echo esc_attr($form_settings['title'] ?? "Let's get to know each other!"); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Name Field Label</th>
                        <td>
                            <input type="text" name="name_label" 
                                   value="<?php echo esc_attr($form_settings['name_label'] ?? "Your Name (required)"); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Email Field Label</th>
                        <td>
                            <input type="text" name="email_label" 
                                   value="<?php echo esc_attr($form_settings['email_label'] ?? "Your Email (required)"); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Phone Field Label</th>
                        <td>
                            <input type="text" name="phone_label" 
                                   value="<?php echo esc_attr($form_settings['phone_label'] ?? "Your Phone"); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Website Field Label</th>
                        <td>
                            <input type="text" name="website_label" 
                                   value="<?php echo esc_attr($form_settings['website_label'] ?? "Your Website"); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Submit Button Text</th>
                        <td>
                            <input type="text" name="submit_button_text" 
                                   value="<?php echo esc_attr($form_settings['submit_button'] ?? "Start Chat"); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Service Selection Configuration -->
            <div class="card">
                <h2>Service Selection Configuration</h2>
                <?php $service_settings = get_option('openai_chatbot_service_settings', array()); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Selection Title</th>
                        <td>
                            <input type="text" name="service_title" 
                                   value="<?php echo esc_attr($service_settings['title'] ?? "What can I help you with?"); ?>" 
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Selection Subtitle</th>
                        <td>
                            <input type="text" name="service_subtitle" 
                                   value="<?php echo esc_attr($service_settings['subtitle'] ?? "Select the service you're most interested in:"); ?>" 
                                   class="large-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row" style="vertical-align: top;">Service Options</th>
                        <td>
                            <p class="description">Configure up to 5 service options. At least one must be filled.</p>
                            <?php 
                            // Always show exactly 5 option slots
                            $default_options = [
                                ['display' => 'Website Design', 'json_key' => 'website_design'],
                                ['display' => 'SEO (Search Engine Optimization)', 'json_key' => 'seo'],
                                ['display' => 'Website Care Plan', 'json_key' => 'maintenance'],
                                ['display' => 'Content Creation', 'json_key' => 'content'],
                                ['display' => 'Website Improvements', 'json_key' => 'improvements']
                            ];
                            
                            $service_options = $service_settings['options'] ?? $default_options;
                            
                            // Ensure we always have 5 slots
                            while (count($service_options) < 5) {
                                $service_options[] = ['display' => '', 'json_key' => ''];
                            }
                            
                            for ($i = 0; $i < 5; $i++) : 
                                $option = $service_options[$i] ?? ['display' => '', 'json_key' => ''];
                            ?>
                                <div class="service-option" style="margin-bottom: 15px;">
                                    <label style="display: inline-block; width: 80px;">Option <?php echo $i + 1; ?>:</label>
                                    <input type="text" 
                                           name="service_options[<?php echo $i; ?>][display]" 
                                           value="<?php echo esc_attr($option['display']); ?>" 
                                           placeholder="Display Text (e.g., Website Design)"
                                           style="width: 250px; margin-right: 10px;" />
                                    <input type="text" 
                                           name="service_options[<?php echo $i; ?>][json_key]" 
                                           value="<?php echo esc_attr($option['json_key']); ?>" 
                                           placeholder="JSON Key (e.g., website_design)"
                                           style="width: 200px;" />
                                    <?php if ($i === 0) : ?>
                                        <span class="required" style="color: red;">*</span>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                            <p class="description">The JSON key should match a section in your service data JSON for the AI to provide relevant information.</p>
                        </td>
                    </tr>
                </table>
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
        
        // Save popup settings
        if (isset($settings['popup_settings'])) {
            $popup_settings = array(
                'style' => sanitize_text_field($settings['popup_settings']['style']),
                'title' => sanitize_text_field($settings['popup_settings']['title']),
                'description' => wp_kses_post($settings['popup_settings']['description']),
                'chat_button' => sanitize_text_field($settings['popup_settings']['chat_button']),
                'demo_button' => sanitize_text_field($settings['popup_settings']['demo_button']),
                'demo_link' => esc_url_raw($settings['popup_settings']['demo_link'])
            );
            update_option('openai_chatbot_popup_settings', $popup_settings);
        }
        
        // Save form settings
        if (isset($settings['form_settings'])) {
            $form_settings = array(
                'title' => sanitize_text_field($settings['form_settings']['title']),
                'name_label' => sanitize_text_field($settings['form_settings']['name_label']),
                'email_label' => sanitize_text_field($settings['form_settings']['email_label']),
                'phone_label' => sanitize_text_field($settings['form_settings']['phone_label']),
                'website_label' => sanitize_text_field($settings['form_settings']['website_label']),
                'submit_button' => sanitize_text_field($settings['form_settings']['submit_button'])
            );
            update_option('openai_chatbot_form_settings', $form_settings);
        }
        
        // Save service selection settings
        if (isset($settings['service_settings'])) {
            $service_settings = array(
                'title' => sanitize_text_field($settings['service_settings']['title']),
                'subtitle' => sanitize_text_field($settings['service_settings']['subtitle']),
                'options' => array()
            );
            
            if (isset($settings['service_settings']['options'])) {
                foreach ($settings['service_settings']['options'] as $option) {
                    $service_settings['options'][] = array(
                        'display' => sanitize_text_field($option['display']),
                        'json_key' => sanitize_key($option['json_key'])
                    );
                }
            }
            
            update_option('openai_chatbot_service_settings', $service_settings);
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