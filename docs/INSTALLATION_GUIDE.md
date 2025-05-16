# Smart Chatbot Installation Guide

## Prerequisites

Before installing Smart Chatbot, ensure you have:

- WordPress 5.0 or higher
- PHP 7.4 or higher
- An OpenAI API key
- Administrator access to WordPress
- SSL certificate (recommended)
- At least 50MB free disk space

## Step-by-Step Installation

### Step 1: Obtain OpenAI API Key

1. Visit [OpenAI Platform](https://platform.openai.com/)
2. Sign up or log in to your account
3. Navigate to API Keys section
4. Click "Create new secret key"
5. Copy the key (starts with `sk-`)
6. Save it securely - you won't see it again!

### Step 2: Download the Plugin

1. Download `smart-chatbot.zip` from your purchase
2. Do NOT unzip the file
3. Keep the ZIP file ready for upload

### Step 3: Install via WordPress Admin

1. Log in to your WordPress admin panel
2. Navigate to **Plugins > Add New**
3. Click **Upload Plugin** button
4. Click **Choose File** and select `smart-chatbot.zip`
5. Click **Install Now**
6. Wait for installation to complete

### Step 4: Configure API Key

Add the following to your `wp-config.php` file (above the line that says `/* That's all, stop editing! */`):

```php
define('OPENAI_API_KEY', 'sk-your-actual-api-key-here');
```

**Important**: Replace `sk-your-actual-api-key-here` with your actual OpenAI API key.

### Step 5: Activate the Plugin

1. After installation, click **Activate Plugin**
2. You should see a success message
3. Check for "Smart Chatbot" in your admin menu

### Step 6: Initial Configuration

1. Navigate to **Smart Chatbot** in your admin menu
2. Go to the **Settings** tab
3. Configure the following:
   - Company Name
   - Email for notifications
   - Language preference
   - Chat position (left/right)
   - Primary color

### Step 7: Configure Service Data

1. In the **Service Data** section:
   - Enter your business information
   - Add services you offer
   - Include pricing (optional)
   - Add contact details

2. Click **Expand with AI** to enhance your content
3. Review and **Save Settings**

### Step 8: Test the Chatbot

1. Visit your website's frontend
2. Look for the chat widget (bottom right by default)
3. Wait 3 seconds for the welcome popup
4. Click "Ask the AI" to start chatting
5. Test various questions about your services

## Alternative Installation Methods

### Via FTP

1. Unzip `smart-chatbot.zip` on your computer
2. Connect to your server via FTP
3. Navigate to `/wp-content/plugins/`
4. Upload the entire `smart-chatbot` folder
5. Go to WordPress admin > Plugins
6. Activate Smart Chatbot

### Via Command Line

```bash
# Navigate to WordPress directory
cd /path/to/wordpress

# Go to plugins directory
cd wp-content/plugins/

# Upload and unzip
unzip /path/to/smart-chatbot.zip

# Set permissions
chmod -R 755 smart-chatbot/
```

## Post-Installation Steps

### 1. Verify Installation

Check the following:
- [ ] Plugin appears in Plugins list
- [ ] Smart Chatbot menu is visible
- [ ] API test shows "Connected"
- [ ] Chat widget appears on frontend

### 2. Configure Permissions

Ensure proper file permissions:
```bash
chmod -R 755 wp-content/plugins/smart-chatbot/
chmod -R 775 wp-content/uploads/smart-chatbot/
```

### 3. Set Up Cron Jobs

For optimal performance, ensure WordPress cron is running:
```bash
# Add to crontab
*/15 * * * * wget -q -O - https://yoursite.com/wp-cron.php >/dev/null 2>&1
```

### 4. Configure Email Settings

Ensure WordPress can send emails for notifications:
1. Install an SMTP plugin if needed
2. Test email functionality
3. Verify notification recipient

## Troubleshooting Installation

### Plugin Upload Failed

**Error**: "The uploaded file exceeds the upload_max_filesize directive"

**Solution**:
1. Edit `php.ini`:
   ```ini
   upload_max_filesize = 10M
   post_max_size = 10M
   ```
2. Or add to `.htaccess`:
   ```apache
   php_value upload_max_filesize 10M
   php_value post_max_size 10M
   ```

### API Key Not Working

**Error**: "Invalid API key"

**Solution**:
1. Verify key in `wp-config.php`
2. Check for extra spaces
3. Ensure quotes are correct:
   ```php
   define('OPENAI_API_KEY', 'sk-...');  // Correct
   define("OPENAI_API_KEY", "sk-...");  // Also correct
   ```

### Chat Widget Not Appearing

**Checklist**:
1. Clear browser cache
2. Check browser console for errors
3. Disable other plugins temporarily
4. Switch to default theme temporarily
5. Check for JavaScript conflicts

### Database Table Creation Failed

**Solution**:
1. Check MySQL user permissions
2. Manually create tables:
   ```sql
   CREATE TABLE wp_chatbot_analytics (
       id bigint(20) NOT NULL AUTO_INCREMENT,
       event_type varchar(50) NOT NULL,
       user_id varchar(100),
       session_id varchar(100),
       event_data longtext,
       timestamp datetime DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (id)
   );
   ```

## Security Considerations

### 1. Protect API Key
- Never commit API key to version control
- Use environment variables when possible
- Restrict file permissions on `wp-config.php`

### 2. SSL Certificate
- Always use HTTPS for production sites
- Protects chat data in transit
- Required for some features

### 3. File Permissions
```bash
# Secure permissions
chmod 644 wp-config.php
chmod 755 wp-content/plugins/smart-chatbot/
chmod 775 wp-content/uploads/smart-chatbot/
```

### 4. Regular Updates
- Keep WordPress updated
- Update plugin regularly
- Monitor security advisories

## Uninstallation

To completely remove Smart Chatbot:

1. **Deactivate** the plugin in WordPress admin
2. Click **Delete** to remove files
3. Remove from `wp-config.php`:
   ```php
   // Remove this line
   define('OPENAI_API_KEY', 'sk-...');
   ```
4. Optionally, clean database:
   ```sql
   DROP TABLE IF EXISTS wp_chatbot_analytics;
   DELETE FROM wp_options WHERE option_name LIKE '%chatbot%';
   ```

## Getting Help

### Support Resources
- Documentation: [Full Documentation](DOCUMENTATION.md)
- Email: support@web-smart.co
- Forum: https://web-smart.co/support
- Phone: 01462 544738

### Before Contacting Support
1. Check error logs
2. Test with default theme
3. Disable other plugins
4. Clear all caches
5. Document error messages

## Next Steps

After successful installation:

1. Read the [User Guide](USER_GUIDE.md)
2. Configure [Advanced Settings](DOCUMENTATION.md#configuration)
3. Set up [Analytics Tracking](DOCUMENTATION.md#analytics-dashboard)
4. Customize [Appearance](DOCUMENTATION.md#appearance)
5. Test [Proactive Features](DOCUMENTATION.md#proactive-engagement)

---

© 2024 Web-Smart.Co | Smart Chatbot v1.0.0