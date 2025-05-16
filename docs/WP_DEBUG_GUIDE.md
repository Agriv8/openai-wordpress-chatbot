# WordPress Debugging Guide for Smart Chatbot

## Enabling Debug Mode

To diagnose issues with the Smart Chatbot plugin, enable WordPress debug mode by adding these lines to your `wp-config.php` file:

```php
// Enable debugging
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
```

## Log File Locations

With logging enabled, you'll find debug information in:

1. **General WordPress Debug Log**: `/wp-content/debug.log`
2. **Smart Chatbot Specific Log**: `/wp-content/debug-smart-chatbot.log`

## Common Issues and Solutions

### 1. Plugin Breaks WordPress

**Symptom**: White screen or error after activating plugin

**Solution**:
1. Check if `OPENAI_API_KEY` is defined in `wp-config.php`:
   ```php
   define('OPENAI_API_KEY', 'your-api-key-here');
   ```

2. Check debug logs for error messages

3. Ensure PHP version is 7.4 or higher

4. Verify WordPress version is 5.0 or higher

### 2. API Key Issues

**Symptom**: Plugin doesn't work, shows API error messages

**Check**:
```php
// In wp-config.php, add BEFORE "That's all, stop editing!"
define('OPENAI_API_KEY', 'sk-your-actual-api-key');
```

### 3. JSON File Errors

**Symptom**: "JSON file not found" in logs

**Solution**:
1. Ensure `chatbot-data.json` exists in plugin root
2. Check file permissions (should be readable)
3. Validate JSON syntax at jsonlint.com

### 4. JavaScript Errors

**Symptom**: Chatbot doesn't appear or respond

**Debug Steps**:
1. Open browser console (F12)
2. Look for JavaScript errors
3. Check if jQuery is loaded
4. Verify nonce is set correctly

## Checking Error Logs

To view the most recent errors:

```bash
# View last 50 lines of general WordPress debug log
tail -50 /path/to/wordpress/wp-content/debug.log

# View Smart Chatbot specific logs
tail -50 /path/to/wordpress/wp-content/debug-smart-chatbot.log

# Follow logs in real-time
tail -f /path/to/wordpress/wp-content/debug.log
```

## Plugin Status Check

The plugin now logs its initialization status. Look for these messages:

### Successful initialization:
```
[2025-05-16 10:30:00] Smart Chatbot: Starting initialization
[2025-05-16 10:30:00] Smart Chatbot: API key found
[2025-05-16 10:30:00] Smart Chatbot: JSON data loaded successfully
[2025-05-16 10:30:00] Smart Chatbot: Successfully initialized
```

### Failed initialization:
```
[2025-05-16 10:30:00] Smart Chatbot ERROR: OPENAI_API_KEY not defined in wp-config.php
[2025-05-16 10:30:00] Smart Chatbot ERROR: JSON file not found: /path/to/chatbot-data.json
```

## Testing API Connection

Use the admin panel to test API connection:

1. Go to WordPress Admin → Smart Chatbot
2. Click "Test API Connection"
3. Check for success/error messages

## Emergency Recovery

If the plugin completely breaks your site:

1. **Via FTP/File Manager**:
   - Navigate to `/wp-content/plugins/`
   - Rename `openai-chatbot` folder to `openai-chatbot-disabled`
   - WordPress will automatically deactivate the plugin

2. **Via Database**:
   - Access phpMyAdmin
   - Find `wp_options` table
   - Look for `active_plugins` option
   - Remove the plugin from the serialized array

3. **Via WP-CLI**:
   ```bash
   wp plugin deactivate openai-chatbot
   ```

## Performance Issues

If the plugin is slow:

1. Check API response times in logs
2. Verify caching is working:
   - Look for "Using cached response" in debug logs
3. Consider increasing timeout values
4. Check server resources (memory, CPU)

## Support

For additional help:

- Review all logs carefully
- Check [GitHub Issues](https://github.com/Agriv8/openai-wordpress-chatbot/issues)
- Email support@web-smart.co with debug logs attached