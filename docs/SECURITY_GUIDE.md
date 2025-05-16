# Smart Chatbot Security Guide

## Overview

This guide covers security best practices, implementation details, and compliance considerations for the Smart Chatbot plugin. Security is paramount when handling user data and API communications.

## Security Architecture

```
┌─────────────────────────────────────────────────────┐
│                   User Browser                      │
│                     (HTTPS)                         │
└──────────────────────┬──────────────────────────────┘
                       │ SSL/TLS
┌──────────────────────▼──────────────────────────────┐
│                WordPress Server                     │
│  ┌──────────────────────────────────────────────┐  │
│  │            Smart Chatbot Plugin              │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────┐ │  │
│  │  │   Input    │  │   Rate     │  │  CSRF  │ │  │
│  │  │ Validation │  │  Limiting  │  │ Nonce  │ │  │
│  │  └────────────┘  └────────────┘  └────────┘ │  │
│  └──────────────────────┬───────────────────────┘  │
└─────────────────────────┼───────────────────────────┘
                          │ HTTPS
┌─────────────────────────▼───────────────────────────┐
│                  OpenAI API                         │
│                (API Key Auth)                       │
└─────────────────────────────────────────────────────┘
```

## Implementation Details

### 1. API Key Security

#### Storage
```php
// wp-config.php (Recommended)
define('OPENAI_API_KEY', 'sk-...');

// Never store in:
// - Database (unless encrypted)
// - JavaScript files
// - Version control
// - Client-side code
```

#### Access Control
```php
class OpenAIChatbot {
    private $api_key;
    
    public function __construct() {
        $this->api_key = $this->get_api_key();
        if (!$this->api_key) {
            throw new Exception('API key not configured');
        }
    }
    
    private function get_api_key() {
        if (defined('OPENAI_API_KEY')) {
            return OPENAI_API_KEY;
        }
        return false;
    }
}
```

### 2. Input Validation & Sanitization

#### User Input Validation
```php
public function validate_input($input, $type = 'text', $max_length = 255) {
    // Remove null bytes
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
            $input = preg_replace('/[^0-9+\-() ]/', '', $input);
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
            
        default:
            $input = sanitize_text_field($input);
    }
    
    // Length validation
    if (strlen($input) > $max_length) {
        throw new Exception("Input exceeds maximum length");
    }
    
    return $input;
}
```

#### File Upload Security
```php
public function validate_file_upload($file) {
    // Check file size
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB
        throw new Exception('File too large');
    }
    
    // Validate MIME type
    $allowed_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf'
    ];
    
    $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
    
    if (!in_array($file_info['type'], $allowed_types)) {
        throw new Exception('File type not allowed');
    }
    
    // Check for PHP in file
    $content = file_get_contents($file['tmp_name']);
    if (strpos($content, '<?php') !== false) {
        throw new Exception('Invalid file content');
    }
    
    return true;
}
```

### 3. CSRF Protection

#### Nonce Implementation
```php
// Generate nonce
wp_localize_script('openai-chatbot-script', 'openai_chatbot_data', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('openai_chatbot_nonce')
]);

// Verify nonce in AJAX handler
public function ajax_handler() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'openai_chatbot_nonce')) {
        wp_die('Security check failed', 403);
    }
    
    // Process request
}
```

#### JavaScript Implementation
```javascript
// Include nonce in AJAX requests
$.post(openai_chatbot_data.ajax_url, {
    action: 'openai_chatbot',
    nonce: openai_chatbot_data.nonce,
    message: userMessage
});
```

### 4. Rate Limiting

#### Session-based Rate Limiting
```php
private function check_rate_limit() {
    $session_key = 'chatbot_requests_' . session_id();
    $current_time = time();
    $window = 3600; // 1 hour
    $limit = 20; // requests per hour
    
    // Get existing requests
    $requests = get_transient($session_key) ?: [];
    
    // Remove old requests
    $requests = array_filter($requests, function($timestamp) use ($current_time, $window) {
        return ($current_time - $timestamp) < $window;
    });
    
    // Check limit
    if (count($requests) >= $limit) {
        throw new Exception('Rate limit exceeded');
    }
    
    // Add current request
    $requests[] = $current_time;
    set_transient($session_key, $requests, $window);
    
    return true;
}
```

#### IP-based Rate Limiting
```php
private function check_ip_rate_limit() {
    $ip = $this->get_client_ip();
    $key = 'rate_limit_' . md5($ip);
    $limit = 50; // requests per hour
    
    $count = get_transient($key) ?: 0;
    
    if ($count >= $limit) {
        throw new Exception('Rate limit exceeded for IP');
    }
    
    set_transient($key, $count + 1, 3600);
    return true;
}

private function get_client_ip() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR', 'HTTP_CLIENT_IP'];
    
    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key])) {
            $ip = filter_var($_SERVER[$key], FILTER_VALIDATE_IP);
            if ($ip !== false) {
                return $ip;
            }
        }
    }
    
    return '0.0.0.0';
}
```

### 5. Data Encryption

#### Sensitive Data Storage
```php
class DataEncryption {
    private $cipher = 'AES-256-CBC';
    
    public function encrypt($data) {
        $key = $this->get_encryption_key();
        $iv = openssl_random_pseudo_bytes(16);
        
        $encrypted = openssl_encrypt($data, $this->cipher, $key, 0, $iv);
        
        return base64_encode($encrypted . '::' . $iv);
    }
    
    public function decrypt($data) {
        $key = $this->get_encryption_key();
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        
        return openssl_decrypt($encrypted_data, $this->cipher, $key, 0, $iv);
    }
    
    private function get_encryption_key() {
        if (defined('CHATBOT_ENCRYPTION_KEY')) {
            return CHATBOT_ENCRYPTION_KEY;
        }
        return wp_salt('auth');
    }
}
```

### 6. Session Security

#### Secure Session Management
```php
class SecureSession {
    public function __construct() {
        if (session_status() == PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', is_ssl());
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
        }
        
        $this->regenerate_session();
    }
    
    private function regenerate_session() {
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) {
            // Session existed for 30 minutes, regenerate ID
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}
```

### 7. Content Security Policy

#### Headers Implementation
```php
add_action('send_headers', function() {
    if (!is_admin()) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://api.openai.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; connect-src 'self' https://api.openai.com;");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: no-referrer-when-downgrade");
    }
});
```

## Security Checklist

### Pre-Deployment
- [ ] API key stored securely in wp-config.php
- [ ] All inputs validated and sanitized
- [ ] CSRF protection implemented
- [ ] Rate limiting configured
- [ ] File upload restrictions in place
- [ ] SSL certificate installed
- [ ] Security headers configured

### Regular Maintenance
- [ ] Update WordPress core
- [ ] Update all plugins
- [ ] Monitor error logs
- [ ] Review security alerts
- [ ] Audit user permissions
- [ ] Check for suspicious activity
- [ ] Backup data regularly

### Incident Response
1. **Detection**
   - Monitor logs
   - Set up alerts
   - Regular audits

2. **Containment**
   - Disable affected features
   - Block suspicious IPs
   - Increase rate limiting

3. **Investigation**
   - Review logs
   - Identify attack vector
   - Document findings

4. **Recovery**
   - Patch vulnerabilities
   - Restore from backup
   - Update security measures

5. **Post-Incident**
   - Update documentation
   - Improve monitoring
   - Train team

## Compliance

### GDPR Compliance
```php
class GDPRCompliance {
    public function __construct() {
        add_action('init', [$this, 'handle_gdpr_requests']);
    }
    
    public function handle_gdpr_requests() {
        if (isset($_GET['gdpr_request'])) {
            switch ($_GET['gdpr_request']) {
                case 'export':
                    $this->export_user_data();
                    break;
                case 'delete':
                    $this->delete_user_data();
                    break;
            }
        }
    }
    
    private function export_user_data() {
        $user_email = sanitize_email($_GET['email']);
        
        // Verify user identity
        if (!$this->verify_user($user_email)) {
            wp_die('Unauthorized', 403);
        }
        
        // Collect user data
        $data = $this->collect_user_data($user_email);
        
        // Send data
        wp_send_json($data);
    }
    
    private function delete_user_data() {
        $user_email = sanitize_email($_GET['email']);
        
        // Verify user identity
        if (!$this->verify_user($user_email)) {
            wp_die('Unauthorized', 403);
        }
        
        // Delete data
        $this->delete_all_user_data($user_email);
        
        wp_die('Data deleted successfully');
    }
}
```

### Privacy Policy Integration
```php
add_filter('privacy_policy_template', function($content) {
    $content .= '<h2>Smart Chatbot</h2>';
    $content .= '<p>Our chatbot collects the following information:</p>';
    $content .= '<ul>';
    $content .= '<li>Chat messages and responses</li>';
    $content .= '<li>Name and email (if provided)</li>';
    $content .= '<li>Session information</li>';
    $content .= '<li>Analytics data</li>';
    $content .= '</ul>';
    $content .= '<p>This data is used to provide customer service and improve our services.</p>';
    
    return $content;
});
```

## Security Testing

### Penetration Testing
```bash
# Test for XSS
curl -X POST https://yoursite.com/wp-admin/admin-ajax.php \
  -d "action=openai_chatbot&message=<script>alert('XSS')</script>"

# Test for SQL Injection
curl -X POST https://yoursite.com/wp-admin/admin-ajax.php \
  -d "action=openai_chatbot&message=' OR '1'='1"

# Test rate limiting
for i in {1..30}; do
  curl -X POST https://yoursite.com/wp-admin/admin-ajax.php \
    -d "action=openai_chatbot&message=test"
done
```

### Security Scanning
```bash
# WPScan
wpscan --url https://yoursite.com --enumerate p

# Check file permissions
find . -type f -name "*.php" -exec chmod 644 {} +
find . -type d -exec chmod 755 {} +

# Search for vulnerabilities
grep -r "eval(" .
grep -r "base64_decode(" .
grep -r "\$_REQUEST" .
```

## Best Practices

### Code Security
1. **Never trust user input**
   - Validate everything
   - Sanitize all outputs
   - Use prepared statements

2. **Principle of least privilege**
   - Minimal permissions
   - Role-based access
   - Capability checks

3. **Defense in depth**
   - Multiple security layers
   - Fail safely
   - Log everything

### Operational Security
1. **Regular updates**
   - WordPress core
   - Plugins
   - PHP version

2. **Monitoring**
   - Error logs
   - Access logs
   - Security alerts

3. **Backups**
   - Regular schedule
   - Off-site storage
   - Test restoration

### Communication Security
1. **Always use HTTPS**
   - SSL certificates
   - Force SSL redirect
   - HSTS headers

2. **Secure API calls**
   - Authentication
   - Rate limiting
   - Input validation

## Emergency Contacts

### Security Team
- Email: security@web-smart.co
- Phone: [Emergency number]
- PGP Key: [Public key]

### Incident Response
1. Immediate: Block attack
2. Within 1 hour: Assess damage
3. Within 24 hours: Full report
4. Within 48 hours: Patch deployed

---

© 2024 Web-Smart.Co | Security Guide v1.0