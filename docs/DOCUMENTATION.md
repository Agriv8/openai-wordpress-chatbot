# Smart Chatbot - Complete Documentation

## Table of Contents
1. [Overview](#overview)
2. [Installation](#installation)
3. [Configuration](#configuration)
4. [Features](#features)
5. [API Reference](#api-reference)
6. [Security](#security)
7. [Performance](#performance)
8. [Troubleshooting](#troubleshooting)
9. [Developer Guide](#developer-guide)
10. [Changelog](#changelog)

## Overview

Smart Chatbot is an advanced WordPress plugin that integrates OpenAI's GPT-4 to provide intelligent customer service, lead generation, and support automation.

### GitHub Repository

Source code available at: https://github.com/Agriv8/openai-wordpress-chatbot

### Key Features
- AI-powered conversations using GPT-4
- Cross-page session persistence
- Rich media support (images, documents)
- Comprehensive analytics dashboard
- Proactive engagement (exit intent, scroll tracking)
- Mobile-optimized responsive design
- Full accessibility compliance
- Multi-language support

### Requirements
- WordPress 5.0+
- PHP 7.4+
- OpenAI API key
- SSL certificate (recommended)

## Installation

### Step 1: Download and Upload
1. Download the plugin ZIP file
2. Navigate to WordPress Admin > Plugins > Add New
3. Click "Upload Plugin" and select the ZIP file
4. Click "Install Now"

### Step 2: Activate Plugin
1. Click "Activate" after installation
2. You'll see "Smart Chatbot" in the admin menu

### Step 3: Configure API Key
Add to your `wp-config.php` file:
```php
define('OPENAI_API_KEY', 'sk-your-actual-api-key-here');
```

### Step 4: Initial Setup
1. Navigate to Smart Chatbot > Settings
2. Configure company information
3. Set language preferences
4. Customize appearance

## Configuration

### Admin Menu Structure
```
Smart Chatbot
├── Dashboard
├── Settings
├── Analytics
└── Help
```

### Settings Options

#### Company Information
- **Company Name**: Your business name
- **Language**: UK English (default), US English, Spanish, French, German
- **Email Recipient**: Where chat transcripts are sent

#### Service Data
Configure what your chatbot can discuss:
```json
{
  "company": "Your Company",
  "services": ["Web Design", "SEO", "Marketing"],
  "pricing": {
    "web_design": "From £1,000",
    "seo": "From £350/month"
  }
}
```

#### Appearance
- **Position**: Left or Right
- **Primary Color**: Hex color code
- **Widget Size**: Default 400x700px

#### Behavior
- **Proactive Triggers**: Enable/disable
- **Session Timeout**: 30 minutes default
- **Rate Limiting**: 20 requests/hour

### API Configuration

#### OpenAI Settings
```php
// Model selection
'model' => 'gpt-4-turbo'

// Response parameters
'max_tokens' => 3500
'temperature' => 0.3
'top_p' => 1
'frequency_penalty' => 0
'presence_penalty' => 0
```

## Features

### 1. Chat Interface

#### Components
- **Message Display**: Markdown support, link handling
- **Input Field**: 1000 character limit
- **File Upload**: Images, PDFs, documents (10MB max)
- **Quick Actions**: Suggestions, promotions button

#### Session Management
- LocalStorage persistence
- Cross-page continuity
- 30-minute timeout
- Automatic session recovery

### 2. Analytics Dashboard

#### Metrics Tracked
- Total sessions
- Message count
- Conversion rate
- User satisfaction
- Pain points analysis

#### Events
```javascript
// Event types
'chat_opened'
'message_sent'
'response_received'
'file_uploaded'
'service_selected'
'contact_form_submitted'
'chat_ended'
'satisfaction_rating'
```

### 3. Proactive Engagement

#### Triggers
1. **Exit Intent**: Mouse leaves viewport
2. **Time-based**: 30 seconds, 2 minutes inactivity
3. **Scroll Depth**: 75% page scroll

#### Messages
```javascript
'Wait! Before you go, can I help?'
'I see you're exploring. Any questions?'
'Still browsing? Need assistance?'
```

### 4. Media Handling

#### Supported Types
- Images: JPG, PNG, GIF, WebP
- Documents: PDF, DOC, DOCX, TXT
- Video: MP4, WebM (display only)

#### Upload Process
1. File validation (type, size)
2. WordPress media library integration
3. Thumbnail generation for images
4. Secure file handling

### 5. Multi-language Support

#### Available Languages
- UK English (default)
- US English
- Spanish
- French
- German

#### Implementation
```php
$language_setting = get_option('openai_chatbot_language', 'uk_english');
$language_instruction = $this->getLanguageInstruction($language_setting);
```

### 6. Security Features

#### Input Validation
```php
// XSS prevention
$input = wp_kses_post($input);

// Length limits
if (strlen($input) > $max_length) {
    throw new Exception("Input too long");
}

// Type validation
switch ($type) {
    case 'email':
        if (!is_email($input)) {
            throw new Exception('Invalid email');
        }
        break;
}
```

#### Rate Limiting
```php
private $rate_limit = 20; // requests per hour
private $rate_window = 3600; // 1 hour in seconds
```

#### AJAX Security
```php
// Nonce verification
if (!wp_verify_nonce($_POST['nonce'], 'openai_chatbot_nonce')) {
    wp_die('Security check failed');
}
```

### 7. Performance Optimization

#### Caching
```php
// Response caching for common questions
private $cache_duration = 300; // 5 minutes

// Cache key generation
$cache_key = 'chatbot_response_' . md5(strtolower(trim($input)));
```

#### Code Splitting
- Main chat: `openai-chatbot.js`
- Popup module: `openai-chatbot-popup.js` (lazy loaded)
- Proactive features: `chatbot-proactive.js`

#### CSS Optimization
- CSS custom properties
- Reduced specificity
- Minimal !important usage

### 8. Accessibility

#### ARIA Implementation
```html
<div id="openai-chatbot" 
     aria-live="polite" 
     aria-label="Chat widget" 
     role="dialog">
```

#### Keyboard Navigation
- Tab navigation
- ESC to close
- Enter to send
- Focus management

#### Screen Reader Support
```javascript
function announceToScreenReader(message) {
    const announcement = $('<div>');
    announcement.attr('role', 'status');
    announcement.attr('aria-live', 'polite');
    announcement.text(message);
}
```

## API Reference

### PHP Classes

#### OpenAIChatbot
Main plugin class handling core functionality.

```php
class OpenAIChatbot {
    public function __construct()
    public function enqueue_chatbot_assets()
    public function add_chatbot_html()
    public function getResponse($user_input, $user_name, $conversation_history)
    public function ajax_handler()
    public function contact_form_handler()
}
```

#### ChatbotAnalytics
Analytics tracking and reporting.

```php
class ChatbotAnalytics {
    public function create_tables()
    public function track_event()
    public function render_analytics_page()
    private function get_conversion_rate($start_date)
    private function get_satisfaction_rate($start_date)
}
```

#### ChatbotMediaHandler
File upload and media processing.

```php
class ChatbotMediaHandler {
    public function handle_upload()
    private function get_file_type($extension)
    private function generate_thumbnail($file_path)
}
```

### JavaScript Functions

#### Core Functions
```javascript
// State management
loadChatState()
saveChatState()

// UI functions
showContactForm()
showServiceSelection()
initializeChatInterface()

// Message handling
sendMessage(message)
addMessage(sender, message)

// Analytics
trackEvent(eventType, eventData)
```

#### Proactive Engagement
```javascript
// Trigger functions
setupExitIntent()
setupScrollDepth()
setupTimeTriggers()

// Display function
showProactiveMessage(message)
```

### AJAX Endpoints

#### Chat Message
```javascript
action: 'openai_chatbot'
data: {
    user_question: string,
    user_name: string,
    conversation_history: array,
    nonce: string
}
```

#### File Upload
```javascript
action: 'upload_chatbot_media'
data: FormData {
    file: File,
    nonce: string
}
```

#### Analytics Tracking
```javascript
action: 'track_chatbot_event'
data: {
    event_type: string,
    event_data: object,
    session_id: string,
    user_id: string,
    nonce: string
}
```

## Security

### Best Practices Implemented

1. **Input Sanitization**
   - All user inputs sanitized
   - HTML entities escaped
   - SQL injection prevention

2. **Authentication**
   - Nonce verification on all AJAX calls
   - Capability checks for admin functions

3. **File Security**
   - File type validation
   - Size limits (10MB)
   - Secure upload directory

4. **Rate Limiting**
   - Session-based tracking
   - 20 requests per hour limit
   - Prevents API abuse

5. **Data Protection**
   - Minimal data storage
   - Transient caching
   - Secure API key storage

### Security Headers
```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
```

## Performance

### Optimization Techniques

1. **Lazy Loading**
   - Popup module loaded on demand
   - Images lazy loaded
   - Scripts deferred

2. **Caching Strategy**
   - Common responses cached
   - 5-minute cache duration
   - Transient API usage

3. **Database Optimization**
   - Indexed columns
   - Efficient queries
   - Regular cleanup

4. **Asset Optimization**
   - Minified CSS/JS
   - Gzipped responses
   - CDN ready

### Performance Metrics
- First paint: < 1s
- Interactive: < 3s
- Full load: < 5s

## Troubleshooting

### Common Issues

#### API Key Not Working
```bash
# Check wp-config.php
define('OPENAI_API_KEY', 'sk-...');

# Verify in admin
Smart Chatbot > Settings > Test API
```

#### Chat Not Appearing
1. Check browser console for errors
2. Verify plugin is activated
3. Clear cache
4. Check theme conflicts

#### Database Errors
```sql
-- Check tables exist
SHOW TABLES LIKE '%chatbot_analytics%';

-- Recreate if needed
-- Deactivate and reactivate plugin
```

#### JavaScript Conflicts
```javascript
// Check for jQuery conflicts
jQuery.noConflict();

// Check console for errors
console.log('Chatbot loaded:', window.OpenAIChatbot);
```

### Debug Mode
```php
// Enable in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Error Logs
```bash
# WordPress debug log
/wp-content/debug.log

# PHP error log
/var/log/php_errors.log

# Plugin specific log
/wp-content/uploads/smart-chatbot/debug.log
```

## Developer Guide

### File Structure
```
smart-chatbot/
├── admin/
│   ├── class-openai-chatbot-admin.php
│   ├── css/admin.css
│   └── js/admin.js
├── includes/
│   ├── class-chatbot-analytics.php
│   ├── class-chatbot-installer.php
│   └── class-media-handler.php
├── css/
│   └── openai-chatbot.css
├── js/
│   ├── openai-chatbot.js
│   ├── openai-chatbot-popup.js
│   └── chatbot-proactive.js
├── languages/
├── class-openai-chatbot.php
├── chatbot-data.json
└── readme.txt
```

### Hooks and Filters

#### Actions
```php
// Plugin lifecycle
do_action('smart_chatbot_activated');
do_action('smart_chatbot_deactivated');

// Chat events
do_action('smart_chatbot_message_sent', $message);
do_action('smart_chatbot_response_received', $response);
```

#### Filters
```php
// Modify behavior
apply_filters('smart_chatbot_response', $response);
apply_filters('smart_chatbot_settings', $settings);
apply_filters('smart_chatbot_allowed_file_types', $types);
```

### Extending the Plugin

#### Custom Service Provider
```php
class CustomServiceProvider {
    public function __construct() {
        add_filter('smart_chatbot_response', [$this, 'modify_response'], 10, 2);
    }
    
    public function modify_response($response, $context) {
        // Custom logic
        return $response;
    }
}
```

#### Custom Analytics
```php
add_action('smart_chatbot_track_event', function($event_type, $data) {
    // Send to custom analytics service
    my_custom_analytics($event_type, $data);
}, 10, 2);
```

### Testing

#### Unit Tests
```php
class TestChatbot extends WP_UnitTestCase {
    public function test_api_response() {
        $chatbot = new OpenAIChatbot();
        $response = $chatbot->getResponse('Hello', 'Test');
        $this->assertNotEmpty($response);
    }
}
```

#### Integration Tests
```javascript
// Jest example
describe('Chatbot Widget', () => {
    test('should open on button click', () => {
        $('#ask-ai').click();
        expect($('#openai-chatbot')).toHaveClass('open');
    });
});
```

### Contributing

#### Code Standards
- WordPress Coding Standards
- PSR-4 autoloading
- ESLint for JavaScript
- Proper documentation

#### Pull Request Process
1. Fork repository
2. Create feature branch
3. Write tests
4. Update documentation
5. Submit PR

## Changelog

### Version 4.1.0 (2024-05-16)
- Complete admin interface overhaul
- Added GitHub integration
- Moved all documentation to /docs folder
- Added comprehensive development guides
- Initial commit to GitHub repository
- Added security enhancements (rate limiting, nonce verification)
- Cross-page session persistence
- AI content expansion tool
- Live API testing
- Multi-language support
- Popup style configuration (minimizable vs classic)
- Contact form customization
- Service selection customization
- Analytics system implementation
- Media upload support
- Proactive engagement features

### Version 1.0.0 (2024-01-01)
- Initial release
- GPT-4 integration
- Analytics dashboard
- Media support
- Proactive engagement
- Mobile optimization
- Accessibility features

### Roadmap
- v1.1: Voice input support
- v1.2: Custom widget themes
- v1.3: Advanced analytics
- v2.0: SaaS platform migration

## Support

### Resources
- GitHub Repository: https://github.com/Agriv8/openai-wordpress-chatbot
- Documentation: https://web-smart.co/smart-chatbot/docs
- Support Forum: https://web-smart.co/support
- Email: support@web-smart.co
- Phone: 01462 544738

### License
GPL v2 or later
Copyright © 2024 Web-Smart.Co

---

Last updated: May 16, 2025