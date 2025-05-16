# Smart Chatbot Troubleshooting Guide

## Quick Diagnostics

### System Check
```bash
# Run this diagnostic script
curl -O https://example.com/chatbot-diagnostic.sh
bash chatbot-diagnostic.sh
```

### Common Issues Checklist
- [ ] Plugin activated?
- [ ] API key configured?
- [ ] SSL certificate valid?
- [ ] JavaScript errors in console?
- [ ] PHP errors in debug log?
- [ ] Correct file permissions?

## Common Problems & Solutions

### 1. Chatbot Not Appearing

#### Symptoms
- No chat widget on frontend
- No errors in console
- Plugin appears active

#### Solutions

**Check Theme Compatibility**
```php
// Add to theme's functions.php temporarily
add_action('wp_footer', function() {
    echo '<!-- Chatbot Debug: Footer Loaded -->';
});
```

**Verify JavaScript Loading**
```javascript
// Check in browser console
console.log('jQuery version:', jQuery.fn.jquery);
console.log('Chatbot loaded:', typeof OpenAIChatbot);
```

**Force Enqueue Scripts**
```php
// Add to theme's functions.php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('jquery');
}, 1);
```

### 2. API Connection Issues

#### Error: "Invalid API Key"

**Verify Configuration**
```php
// Test in wp-config.php
define('OPENAI_API_KEY', 'sk-...'); // Your actual key
echo OPENAI_API_KEY; // Temporary - remove after testing
```

**Check API Key Format**
```php
// Common mistakes
define('OPENAI_API_KEY', sk-abcd123);  // Wrong - missing quotes
define('OPENAI_API_KEY', "sk-abcd123 "); // Wrong - trailing space
define('OPENAI_API_KEY', 'sk-abcd123'); // Correct
```

**Test API Directly**
```bash
curl https://api.openai.com/v1/models \
  -H "Authorization: Bearer YOUR_API_KEY"
```

#### Error: "Rate Limit Exceeded"

**Temporary Solution**
```php
// Increase rate limit temporarily
add_filter('smart_chatbot_rate_limit', function() {
    return 50; // Increase from 20 to 50
});
```

**Check Current Usage**
```php
// Add to functions.php
add_action('admin_notices', function() {
    $session_key = 'chatbot_requests_' . session_id();
    $requests = get_transient($session_key) ?: [];
    echo '<div class="notice notice-info"><p>Current requests: ' . count($requests) . '</p></div>';
});
```

### 3. JavaScript Errors

#### jQuery Conflicts

**Solution 1: No-Conflict Mode**
```javascript
jQuery(document).ready(function($) {
    // Use $ safely here
});
```

**Solution 2: Force jQuery Loading**
```php
add_action('wp_enqueue_scripts', function() {
    wp_deregister_script('jquery');
    wp_register_script('jquery', 'https://code.jquery.com/jquery-3.6.0.min.js', [], '3.6.0');
    wp_enqueue_script('jquery');
}, 0);
```

#### Console Errors

**"Uncaught TypeError: Cannot read property"**
```javascript
// Debug approach
if (typeof OpenAIChatbot !== 'undefined') {
    OpenAIChatbot.init();
} else {
    console.error('Chatbot not loaded');
}
```

### 4. PHP Errors

#### Memory Limit Errors

**Increase Memory**
```php
// wp-config.php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

**Or in .htaccess**
```apache
php_value memory_limit 256M
```

#### Timeout Errors

**Increase Execution Time**
```php
// In plugin file
set_time_limit(300); // 5 minutes
ini_set('max_execution_time', 300);
```

### 5. Database Issues

#### Tables Not Created

**Manual Table Creation**
```sql
CREATE TABLE IF NOT EXISTS wp_chatbot_analytics (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    event_type varchar(50) NOT NULL,
    user_id varchar(100),
    session_id varchar(100),
    event_data longtext,
    timestamp datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY event_type (event_type),
    KEY timestamp (timestamp),
    KEY session_id (session_id)
) DEFAULT CHARSET=utf8mb4;
```

**Check Table Existence**
```php
global $wpdb;
$table = $wpdb->prefix . 'chatbot_analytics';
if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
    echo "Table doesn't exist";
}
```

### 6. Session Issues

#### Session Not Starting

**Force Session Start**
```php
// Add to plugin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
```

**Check Session Support**
```php
// Create test file
<?php
session_start();
$_SESSION['test'] = 'works';
echo isset($_SESSION['test']) ? 'Sessions working' : 'Sessions not working';
```

### 7. File Permission Issues

#### Upload Directory Not Writable

**Fix Permissions**
```bash
# Standard permissions
chmod 755 wp-content/uploads
chmod 755 wp-content/uploads/smart-chatbot
chown www-data:www-data wp-content/uploads/smart-chatbot
```

**Check Current Permissions**
```php
$upload_dir = wp_upload_dir();
$chatbot_dir = $upload_dir['basedir'] . '/smart-chatbot';
echo 'Directory writable: ' . (is_writable($chatbot_dir) ? 'Yes' : 'No');
```

### 8. Performance Issues

#### Slow Response Times

**Enable Caching**
```php
// Add to wp-config.php
define('WP_CACHE', true);
```

**Debug Queries**
```php
// Enable query monitoring
define('SAVEQUERIES', true);

// View queries
add_action('shutdown', function() {
    if (current_user_can('administrator')) {
        global $wpdb;
        echo '<pre>';
        print_r($wpdb->queries);
        echo '</pre>';
    }
});
```

**Optimize Database**
```sql
OPTIMIZE TABLE wp_chatbot_analytics;
ANALYZE TABLE wp_chatbot_analytics;
```

### 9. Mobile Issues

#### Chat Not Responsive

**Viewport Meta Tag**
```php
add_action('wp_head', function() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
});
```

**Force Mobile Styles**
```css
@media (max-width: 768px) {
    #openai-chatbot {
        width: 100% !important;
        max-width: 100% !important;
    }
}
```

### 10. SSL/HTTPS Issues

#### Mixed Content Warnings

**Force HTTPS**
```php
// wp-config.php
define('FORCE_SSL_ADMIN', true);

// .htaccess
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Update URLs**
```sql
UPDATE wp_options SET option_value = replace(option_value, 'http://yourdomain.com', 'https://yourdomain.com') WHERE option_name = 'home' OR option_name = 'siteurl';
```

## Debug Mode

### Enable Debugging

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
define('SAVEQUERIES', true);

// Custom debug function
function chatbot_debug($message, $data = null) {
    if (WP_DEBUG) {
        error_log('[Smart Chatbot] ' . $message);
        if ($data) {
            error_log(print_r($data, true));
        }
    }
}
```

### View Debug Logs

**Location:**
```
/wp-content/debug.log
```

**Real-time Monitoring:**
```bash
tail -f wp-content/debug.log | grep "Smart Chatbot"
```

### JavaScript Debugging

```javascript
// Add to browser console
window.CHATBOT_DEBUG = true;

// Wrap functions
const originalSend = OpenAIChatbot.sendMessage;
OpenAIChatbot.sendMessage = function(message) {
    console.log('Sending message:', message);
    return originalSend.apply(this, arguments);
};
```

## Testing Tools

### Browser Testing

```javascript
// Test chatbot state
console.log('Chat state:', OpenAIChatbot.getChatState());

// Test API connection
fetch('/wp-admin/admin-ajax.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'action=test_openai_api&nonce=' + openai_chatbot_data.nonce
}).then(r => r.json()).then(console.log);
```

### PHP Testing

```php
// Test file: test-chatbot.php
require_once('wp-load.php');

$chatbot = new OpenAIChatbot();
$response = $chatbot->getResponse('Hello', 'Test', []);
var_dump($response);
```

### cURL Testing

```bash
# Test AJAX endpoint
curl -X POST https://yoursite.com/wp-admin/admin-ajax.php \
  -d "action=openai_chatbot&message=Hello&nonce=YOUR_NONCE"

# Test with session
curl -X POST https://yoursite.com/wp-admin/admin-ajax.php \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d "action=openai_chatbot&message=Hello"
```

## Error Reference

### HTTP Status Codes
- **200**: Success
- **400**: Bad Request (check input)
- **401**: Unauthorized (check API key)
- **403**: Forbidden (check nonce/permissions)
- **429**: Rate Limited
- **500**: Server Error
- **503**: Service Unavailable

### Custom Error Codes
- **CHAT001**: Invalid API key
- **CHAT002**: Rate limit exceeded
- **CHAT003**: Invalid input format
- **CHAT004**: Session expired
- **CHAT005**: Database error
- **CHAT006**: File upload error

## Recovery Procedures

### Complete Reset

```php
// Add to functions.php temporarily
function reset_smart_chatbot() {
    // Clear transients
    delete_transient('chatbot_cache');
    
    // Clear options
    delete_option('openai_chatbot_settings');
    
    // Drop tables
    global $wpdb;
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_analytics");
    
    // Deactivate and reactivate
    deactivate_plugins('smart-chatbot/class-openai-chatbot.php');
    activate_plugins('smart-chatbot/class-openai-chatbot.php');
}
// reset_smart_chatbot(); // Uncomment to run
```

### Emergency Mode

```php
// Add to wp-config.php
define('CHATBOT_EMERGENCY_MODE', true);

// In plugin file
if (defined('CHATBOT_EMERGENCY_MODE') && CHATBOT_EMERGENCY_MODE) {
    // Disable non-essential features
    remove_action('wp_footer', [$this, 'add_chatbot_html']);
    // Log all operations
    add_action('all', function($tag) {
        if (strpos($tag, 'chatbot') !== false) {
            error_log("Emergency Mode: $tag");
        }
    });
}
```

## Support Escalation

### Level 1: Self-Service
1. Check this troubleshooting guide
2. Review error logs
3. Test in different environment

### Level 2: Community Support
1. WordPress.org forums
2. Stack Overflow
3. GitHub issues

### Level 3: Professional Support
1. Email: support@web-smart.co
2. Priority: urgent@web-smart.co
3. Phone: 01462 544738

### Information to Provide
1. WordPress version
2. PHP version
3. Error messages
4. Debug log excerpt
5. Steps to reproduce
6. Environment details

---

© 2024 Web-Smart.Co | Troubleshooting Guide v1.0