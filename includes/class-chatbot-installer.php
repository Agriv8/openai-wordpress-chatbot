<?php
/**
 * Plugin activation/deactivation handlers
 */

if (!defined('ABSPATH')) {
    exit;
}

class ChatbotInstaller {
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create upload directory
        self::create_upload_directory();
        
        // Schedule cron events
        self::schedule_events();
        
        // Set activation flag for welcome message
        set_transient('smart_chatbot_activated', true, 300);
        
        // Clear any cache
        wp_cache_flush();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('smart_chatbot_cleanup');
        wp_clear_scheduled_hook('smart_chatbot_analytics_digest');
        
        // Clear cache
        wp_cache_flush();
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Only run if explicitly uninstalling
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }
        
        // Remove database tables (optional - ask user)
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}chatbot_analytics");
        
        // Remove options
        delete_option('openai_chatbot_settings');
        delete_option('openai_chatbot_email');
        delete_option('openai_chatbot_language');
        delete_option('chatbot_cache_preloaded');
        
        // Remove transients
        delete_transient('smart_chatbot_activated');
        
        // Remove upload directory
        $upload_dir = wp_upload_dir();
        $chatbot_dir = $upload_dir['basedir'] . '/smart-chatbot';
        if (is_dir($chatbot_dir)) {
            self::remove_directory($chatbot_dir);
        }
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chatbot_analytics (
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
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = array(
            'position' => 'right',
            'primary_color' => '#5850ec',
            'max_tokens' => 3500,
            'temperature' => 0.3,
            'language' => 'uk_english',
            'email_recipient' => get_option('admin_email'),
            'rate_limit' => 20,
            'cache_duration' => 300,
            'proactive_enabled' => true,
            'analytics_enabled' => true
        );
        
        $existing = get_option('openai_chatbot_settings', array());
        $merged = array_merge($defaults, $existing);
        update_option('openai_chatbot_settings', $merged);
    }
    
    /**
     * Create upload directory
     */
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $chatbot_dir = $upload_dir['basedir'] . '/smart-chatbot';
        
        if (!file_exists($chatbot_dir)) {
            wp_mkdir_p($chatbot_dir);
            
            // Add .htaccess for security
            $htaccess = $chatbot_dir . '/.htaccess';
            $rules = "Options -Indexes\n";
            $rules .= "<FilesMatch '\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$'>\n";
            $rules .= "    Order deny,allow\n";
            $rules .= "    Deny from all\n";
            $rules .= "</FilesMatch>\n";
            
            file_put_contents($htaccess, $rules);
        }
    }
    
    /**
     * Schedule events
     */
    private static function schedule_events() {
        // Daily cleanup
        if (!wp_next_scheduled('smart_chatbot_cleanup')) {
            wp_schedule_event(time(), 'daily', 'smart_chatbot_cleanup');
        }
        
        // Weekly analytics digest
        if (!wp_next_scheduled('smart_chatbot_analytics_digest')) {
            wp_schedule_event(time(), 'weekly', 'smart_chatbot_analytics_digest');
        }
    }
    
    /**
     * Remove directory recursively
     */
    private static function remove_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . "/" . $object)) {
                    self::remove_directory($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        rmdir($dir);
    }
}

// Register hooks
register_activation_hook(dirname(dirname(__FILE__)) . '/class-openai-chatbot.php', array('ChatbotInstaller', 'activate'));
register_deactivation_hook(dirname(dirname(__FILE__)) . '/class-openai-chatbot.php', array('ChatbotInstaller', 'deactivate'));