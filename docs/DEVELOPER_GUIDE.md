# Smart Chatbot Developer Guide

## Introduction

This guide is for developers who want to extend, customize, or integrate with the Smart Chatbot plugin. It covers the plugin architecture, APIs, hooks, and best practices for development.

## Architecture Overview

```
┌─────────────────────────────────────────────────────┐
│                   WordPress Core                    │
├─────────────────────────────────────────────────────┤
│                  Smart Chatbot Plugin               │
├──────────────┬──────────────┬───────────────────────┤
│   Frontend   │   Backend    │      Admin UI         │
│  JavaScript  │     PHP      │    PHP + React        │
├──────────────┼──────────────┼───────────────────────┤
│   Chat UI    │  Database    │    Settings           │
│   Popup      │  API Calls   │    Analytics          │
│   Proactive  │  Security    │    Media Handler      │
└──────────────┴──────────────┴───────────────────────┘
```

## Development Setup

### Prerequisites

- Local WordPress development environment
- PHP 7.4+ with debugging enabled
- Node.js 14+ for JavaScript development
- Git for version control
- Code editor with WordPress support

### Local Environment Setup

1. **Install WordPress Locally**
   ```bash
   # Using Local by Flywheel or
   wp core download
   wp config create --dbname=chatbot_dev --dbuser=root
   wp core install --url=localhost --title="Dev Site"
   ```

2. **Clone Plugin Repository**
   ```bash
   cd wp-content/plugins
   git clone [repository-url] smart-chatbot
   cd smart-chatbot
   ```

3. **Install Dependencies**
   ```bash
   npm install
   composer install
   ```

4. **Configure Development API Key**
   ```php
   // wp-config.php
   define('OPENAI_API_KEY', 'sk-development-key');
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```

## Plugin Structure

### Core Files

```
smart-chatbot/
├── class-openai-chatbot.php      # Main plugin class
├── admin/
│   ├── class-openai-chatbot-admin.php
│   ├── css/admin.css
│   └── js/admin.js
├── includes/
│   ├── class-chatbot-analytics.php
│   ├── class-chatbot-installer.php
│   └── class-media-handler.php
├── js/
│   ├── openai-chatbot.js         # Main chat functionality
│   ├── openai-chatbot-popup.js   # Popup module
│   └── chatbot-proactive.js      # Proactive features
├── css/
│   └── openai-chatbot.css        # Frontend styles
└── languages/                    # Translation files
```

### Class Structure

```php
// Main Plugin Class
class OpenAIChatbot {
    private $api_key;
    private $json_data;
    private $settings;
    
    public function __construct()
    public function enqueue_chatbot_assets()
    public function getResponse($user_input, $user_name, $history)
    public function ajax_handler()
}

// Analytics Class
class ChatbotAnalytics {
    public function track_event()
    public function get_conversion_rate()
    public function render_analytics_page()
}

// Media Handler Class
class ChatbotMediaHandler {
    public function handle_upload()
    private function validate_file()
    private function generate_thumbnail()
}
```

## Hooks & Filters

### Actions

```php
// Plugin Lifecycle
add_action('smart_chatbot_activated', 'your_activation_function');
add_action('smart_chatbot_deactivated', 'your_deactivation_function');

// Chat Events
add_action('smart_chatbot_message_sent', 'track_message', 10, 2);
add_action('smart_chatbot_response_received', 'log_response', 10, 2);
add_action('smart_chatbot_session_started', 'init_session', 10, 1);
add_action('smart_chatbot_session_ended', 'cleanup_session', 10, 1);

// Example Usage
add_action('smart_chatbot_message_sent', function($message, $session_id) {
    // Custom tracking
    error_log("Message sent: $message in session: $session_id");
}, 10, 2);
```

### Filters

```php
// Response Modification
add_filter('smart_chatbot_response', 'modify_response', 10, 3);
add_filter('smart_chatbot_system_prompt', 'customize_prompt', 10, 2);

// Settings
add_filter('smart_chatbot_default_settings', 'custom_defaults', 10, 1);
add_filter('smart_chatbot_allowed_file_types', 'add_file_types', 10, 1);

// Example Usage
add_filter('smart_chatbot_response', function($response, $input, $context) {
    // Add custom signature
    return $response . "\n\n-- Powered by YourCompany";
}, 10, 3);

// Add custom file types
add_filter('smart_chatbot_allowed_file_types', function($types) {
    $types['video'][] = 'mp4';
    $types['audio'] = ['mp3', 'wav'];
    return $types;
});
```

## API Reference

### JavaScript API

```javascript
// Global chatbot object
window.OpenAIChatbot = {
    // Get current chat state
    getChatState: function() {
        return {
            conversationHistory: [],
            userInfo: {},
            chatOpen: false
        };
    },
    
    // Track custom events
    trackEvent: function(eventType, eventData) {
        // Sends to analytics
    },
    
    // Send message programmatically
    sendMessage: function(message) {
        // Sends message to chat
    },
    
    // Open/close chat
    openChat: function() {},
    closeChat: function() {},
    
    // Custom events
    on: function(event, callback) {
        // Event listener
    }
};

// Usage Examples
// Listen for chat open
OpenAIChatbot.on('chat:opened', function() {
    console.log('Chat opened');
});

// Send automated message
OpenAIChatbot.sendMessage('Show me your services');

// Track custom event
OpenAIChatbot.trackEvent('custom_action', {
    category: 'engagement',
    value: 100
});
```

### PHP API

```php
// Get chatbot instance
$chatbot = OpenAIChatbot::get_instance();

// Get response programmatically
$response = $chatbot->getResponse(
    'What services do you offer?',
    'John',
    [] // conversation history
);

// Track custom event
do_action('smart_chatbot_track_event', 'custom_event', [
    'user_id' => get_current_user_id(),
    'value' => 100
]);

// Modify settings
add_filter('smart_chatbot_settings', function($settings) {
    $settings['rate_limit'] = 50; // Increase rate limit
    return $settings;
});
```

## Extending Functionality

### Creating Custom Modules

```php
// custom-module.php
class ChatbotCustomModule {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_filter('smart_chatbot_response', [$this, 'enhance_response'], 10, 3);
    }
    
    public function init() {
        // Module initialization
    }
    
    public function enhance_response($response, $input, $context) {
        // Check for specific keywords
        if (strpos($input, 'appointment') !== false) {
            $response .= "\n\n[Book Appointment](https://calendly.com/your-link)";
        }
        
        return $response;
    }
}

// Initialize module
new ChatbotCustomModule();
```

### Custom Analytics Provider

```php
class CustomAnalyticsProvider {
    public function __construct() {
        add_action('smart_chatbot_track_event', [$this, 'send_to_analytics'], 10, 2);
    }
    
    public function send_to_analytics($event_type, $event_data) {
        // Send to Google Analytics
        if (function_exists('gtag')) {
            echo "<script>
                gtag('event', '{$event_type}', {
                    'event_category': 'chatbot',
                    'event_label': json_encode($event_data)
                });
            </script>";
        }
        
        // Send to custom analytics
        wp_remote_post('https://analytics.yourcompany.com/events', [
            'body' => json_encode([
                'event' => $event_type,
                'data' => $event_data,
                'timestamp' => time()
            ])
        ]);
    }
}
```

### Custom Response Provider

```php
class CustomResponseProvider {
    private $custom_responses = [
        'pricing' => 'Our pricing starts at $X. Would you like details?',
        'hours' => 'We are open Monday-Friday, 9 AM to 5 PM.',
        'location' => 'We are located at 123 Main St.'
    ];
    
    public function __construct() {
        add_filter('smart_chatbot_response', [$this, 'check_custom_responses'], 5, 3);
    }
    
    public function check_custom_responses($response, $input, $context) {
        $input_lower = strtolower($input);
        
        foreach ($this->custom_responses as $keyword => $custom_response) {
            if (strpos($input_lower, $keyword) !== false) {
                return $custom_response;
            }
        }
        
        return $response;
    }
}
```

## Advanced Customization

### Custom Chat UI Theme

```javascript
// custom-theme.js
class ChatbotCustomTheme {
    constructor() {
        this.applyTheme();
        this.addCustomButtons();
    }
    
    applyTheme() {
        const style = document.createElement('style');
        style.textContent = `
            #openai-chatbot {
                --primary-color: #your-color;
                --chat-bg: #your-bg;
            }
            
            .message {
                border-radius: 20px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            
            .message.user {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
        `;
        document.head.appendChild(style);
    }
    
    addCustomButtons() {
        const buttonContainer = document.querySelector('#chatbot-buttons');
        const customButton = document.createElement('button');
        customButton.className = 'chatbot-button custom';
        customButton.textContent = 'Custom Action';
        customButton.onclick = () => this.handleCustomAction();
        buttonContainer.appendChild(customButton);
    }
    
    handleCustomAction() {
        window.OpenAIChatbot.sendMessage('Execute custom action');
    }
}

// Initialize theme
document.addEventListener('DOMContentLoaded', () => {
    new ChatbotCustomTheme();
});
```

### Integration with External Services

```php
// CRM Integration
class ChatbotCRMIntegration {
    public function __construct() {
        add_action('smart_chatbot_contact_form_submitted', [$this, 'sync_to_crm'], 10, 1);
        add_action('smart_chatbot_session_ended', [$this, 'update_crm_activity'], 10, 1);
    }
    
    public function sync_to_crm($form_data) {
        $crm_data = [
            'first_name' => $form_data['first_name'],
            'last_name' => $form_data['last_name'],
            'email' => $form_data['email'],
            'source' => 'chatbot',
            'conversation_id' => $form_data['session_id']
        ];
        
        // Send to HubSpot
        wp_remote_post('https://api.hubspot.com/contacts/v1/contact/', [
            'headers' => [
                'Authorization' => 'Bearer ' . HUBSPOT_API_KEY,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode(['properties' => $crm_data])
        ]);
    }
}

// Slack Notifications
class ChatbotSlackNotifications {
    private $webhook_url = 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL';
    
    public function __construct() {
        add_action('smart_chatbot_high_value_lead', [$this, 'notify_slack'], 10, 1);
    }
    
    public function notify_slack($lead_data) {
        $message = [
            'text' => "🎯 New High-Value Lead from Chatbot!",
            'attachments' => [[
                'color' => 'good',
                'fields' => [
                    ['title' => 'Name', 'value' => $lead_data['name'], 'short' => true],
                    ['title' => 'Email', 'value' => $lead_data['email'], 'short' => true],
                    ['title' => 'Interest', 'value' => $lead_data['interest'], 'short' => false]
                ]
            ]]
        ];
        
        wp_remote_post($this->webhook_url, [
            'body' => json_encode($message),
            'headers' => ['Content-Type' => 'application/json']
        ]);
    }
}
```

## Testing

### Unit Testing

```php
// tests/test-chatbot.php
class Test_Smart_Chatbot extends WP_UnitTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->chatbot = new OpenAIChatbot();
    }
    
    public function test_api_response() {
        $response = $this->chatbot->getResponse('Hello', 'Test User', []);
        $this->assertNotEmpty($response);
        $this->assertIsString($response);
    }
    
    public function test_rate_limiting() {
        // Simulate multiple requests
        for ($i = 0; i < 25; $i++) {
            $response = $this->chatbot->getResponse('Test', 'User', []);
        }
        
        // 21st request should fail
        $this->expectException(Exception::class);
        $this->chatbot->getResponse('Test', 'User', []);
    }
    
    public function test_input_validation() {
        // Test XSS prevention
        $malicious_input = '<script>alert("XSS")</script>';
        $response = $this->chatbot->getResponse($malicious_input, 'User', []);
        $this->assertNotContains('<script>', $response);
    }
}
```

### Integration Testing

```javascript
// tests/integration/chat.test.js
describe('Chat Integration', () => {
    beforeEach(() => {
        cy.visit('/');
        cy.wait(3000); // Wait for popup
    });
    
    it('should open chat on button click', () => {
        cy.get('#ask-ai').click();
        cy.get('#openai-chatbot').should('have.class', 'open');
    });
    
    it('should complete contact form', () => {
        cy.get('#ask-ai').click();
        cy.get('#name').type('Test User');
        cy.get('#email').type('test@example.com');
        cy.get('#phone').type('1234567890');
        cy.get('#contact-form').submit();
        cy.get('.service-selection').should('be.visible');
    });
    
    it('should handle file uploads', () => {
        cy.get('#ask-ai').click();
        // Complete contact form...
        cy.get('#file-upload').attachFile('test-image.jpg');
        cy.get('.message').should('contain', 'Uploading file');
    });
});
```

## Performance Optimization

### Caching Strategy

```php
class ChatbotCache {
    private $cache_group = 'smart_chatbot';
    
    public function get($key) {
        return wp_cache_get($key, $this->cache_group);
    }
    
    public function set($key, $value, $expiration = 300) {
        return wp_cache_set($key, $value, $this->cache_group, $expiration);
    }
    
    public function delete($key) {
        return wp_cache_delete($key, $this->cache_group);
    }
    
    public function flush() {
        return wp_cache_flush();
    }
}

// Usage in main plugin
public function getResponse($input, $user, $history) {
    $cache = new ChatbotCache();
    $cache_key = md5($input . serialize($history));
    
    // Check cache first
    if ($cached_response = $cache->get($cache_key)) {
        return $cached_response;
    }
    
    // Get response from API
    $response = $this->callOpenAIAPI($messages);
    
    // Cache response
    $cache->set($cache_key, $response, 300);
    
    return $response;
}
```

### Database Queries Optimization

```php
// Optimized analytics queries
class OptimizedAnalytics {
    public function get_metrics($start_date, $end_date) {
        global $wpdb;
        
        // Use single query with subqueries
        $sql = "
            SELECT 
                COUNT(DISTINCT session_id) as total_sessions,
                COUNT(*) as total_events,
                AVG(CASE WHEN event_type = 'satisfaction_rating' 
                    THEN JSON_EXTRACT(event_data, '$.rating') 
                    ELSE NULL END) as avg_satisfaction,
                COUNT(CASE WHEN event_type = 'contact_form_submitted' 
                    THEN 1 ELSE NULL END) as conversions
            FROM {$wpdb->prefix}chatbot_analytics
            WHERE timestamp BETWEEN %s AND %s
        ";
        
        return $wpdb->get_row($wpdb->prepare($sql, $start_date, $end_date));
    }
}
```

## Security Best Practices

### Input Sanitization

```php
class ChatbotSecurity {
    public static function sanitize_input($input, $type = 'text') {
        switch ($type) {
            case 'email':
                return sanitize_email($input);
                
            case 'url':
                return esc_url_raw($input);
                
            case 'html':
                return wp_kses_post($input);
                
            case 'js':
                return esc_js($input);
                
            default:
                return sanitize_text_field($input);
        }
    }
    
    public static function validate_file_upload($file) {
        $allowed_types = [
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf'
        ];
        
        $file_info = wp_check_filetype($file['name'], $allowed_types);
        
        if (!$file_info['type']) {
            throw new Exception('File type not allowed');
        }
        
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('File too large');
        }
        
        return true;
    }
}
```

### API Security

```php
// Secure API calls
class SecureAPIClient {
    private $api_key;
    
    public function __construct() {
        $this->api_key = $this->get_secure_api_key();
    }
    
    private function get_secure_api_key() {
        // Get from environment or wp-config
        if (defined('OPENAI_API_KEY')) {
            return OPENAI_API_KEY;
        }
        
        // Fallback to database (encrypted)
        $encrypted = get_option('openai_api_key_encrypted');
        return $this->decrypt($encrypted);
    }
    
    private function decrypt($encrypted) {
        // Implement secure decryption
        return openssl_decrypt($encrypted, 'AES-256-CBC', AUTH_KEY, 0, AUTH_SALT);
    }
}
```

## Deployment

### Build Process

```bash
#!/bin/bash
# build.sh

# Clean build directory
rm -rf build/
mkdir build/

# Copy files
cp -r *.php admin/ includes/ js/ css/ languages/ build/

# Minify JavaScript
for file in build/js/*.js; do
    uglifyjs "$file" -o "$file" -c -m
done

# Minify CSS
for file in build/css/*.css; do
    cssnano "$file" "$file"
done

# Create ZIP
cd build/
zip -r ../smart-chatbot.zip .
cd ..

echo "Build complete: smart-chatbot.zip"
```

### Continuous Integration

```yaml
# .github/workflows/ci.yml
name: CI/CD Pipeline

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '7.4'
        
    - name: Install dependencies
      run: composer install
      
    - name: Run PHP tests
      run: vendor/bin/phpunit
      
    - name: JavaScript tests
      run: |
        npm install
        npm test
        
    - name: Build plugin
      run: ./build.sh
      
    - name: Upload artifact
      uses: actions/upload-artifact@v2
      with:
        name: smart-chatbot
        path: smart-chatbot.zip
```

## Resources

### Documentation
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [OpenAI API Documentation](https://platform.openai.com/docs/)
- [PHP Standards](https://www.php-fig.org/psr/)
- [JavaScript Best Practices](https://github.com/airbnb/javascript)

### Tools
- [WP-CLI](https://wp-cli.org/) - Command line interface
- [Query Monitor](https://querymonitor.com/) - Debugging plugin
- [Debug Bar](https://wordpress.org/plugins/debug-bar/) - Debug tools
- [Theme Check](https://wordpress.org/plugins/theme-check/) - Standards check

### Community
- [WordPress Stack Exchange](https://wordpress.stackexchange.com/)
- [Advanced WordPress Facebook Group](https://www.facebook.com/groups/advancedwp/)
- [WordPress Development Slack](https://make.wordpress.org/chat/)

---

© 2024 Web-Smart.Co | Developer Guide v1.0