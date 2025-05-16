<?php
/**
 * Analytics tracking for OpenAI Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class ChatbotAnalytics {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'chatbot_analytics';
        
        // Hook into various events
        add_action('wp_ajax_track_chatbot_event', array($this, 'track_event'));
        add_action('wp_ajax_nopriv_track_chatbot_event', array($this, 'track_event'));
        add_action('admin_menu', array($this, 'add_analytics_page'));
    }
    
    /**
     * Create analytics database table
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
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
     * Track an event
     */
    public function track_event() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'openai_chatbot_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        global $wpdb;
        
        $event_type = sanitize_text_field($_POST['event_type']);
        $event_data = isset($_POST['event_data']) ? $_POST['event_data'] : array();
        $session_id = sanitize_text_field($_POST['session_id']);
        $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : null;
        
        $wpdb->insert(
            $this->table_name,
            array(
                'event_type' => $event_type,
                'user_id' => $user_id,
                'session_id' => $session_id,
                'event_data' => json_encode($event_data),
                'timestamp' => current_time('mysql')
            )
        );
        
        wp_send_json_success('Event tracked');
    }
    
    /**
     * Add analytics page to admin
     */
    public function add_analytics_page() {
        add_submenu_page(
            'smart-chatbot',
            'Chatbot Analytics',
            'Analytics',
            'manage_options',
            'chatbot-analytics',
            array($this, 'render_analytics_page')
        );
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        global $wpdb;
        
        // Get date range
        $days = isset($_GET['days']) ? intval($_GET['days']) : 7;
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        // Get overview stats
        $total_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM {$this->table_name} WHERE timestamp >= %s",
            $start_date
        ));
        
        $total_messages = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE event_type = 'message_sent' AND timestamp >= %s",
            $start_date
        ));
        
        $conversion_rate = $this->get_conversion_rate($start_date);
        $satisfaction_rate = $this->get_satisfaction_rate($start_date);
        
        // Get top events
        $top_events = $wpdb->get_results($wpdb->prepare(
            "SELECT event_type, COUNT(*) as count 
             FROM {$this->table_name} 
             WHERE timestamp >= %s 
             GROUP BY event_type 
             ORDER BY count DESC 
             LIMIT 10",
            $start_date
        ));
        
        // Get pain points
        $pain_points = $this->get_pain_points($start_date);
        
        ?>
        <div class="wrap">
            <h1>Chatbot Analytics</h1>
            
            <!-- Date Range Selector -->
            <div class="analytics-header">
                <form method="get" action="">
                    <input type="hidden" name="page" value="chatbot-analytics">
                    <select name="days" onchange="this.form.submit()">
                        <option value="7" <?php selected($days, 7); ?>>Last 7 days</option>
                        <option value="30" <?php selected($days, 30); ?>>Last 30 days</option>
                        <option value="90" <?php selected($days, 90); ?>>Last 90 days</option>
                    </select>
                </form>
            </div>
            
            <!-- Overview Stats -->
            <div class="analytics-overview">
                <div class="stat-card">
                    <h3>Total Sessions</h3>
                    <p class="stat-number"><?php echo number_format($total_sessions); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Messages</h3>
                    <p class="stat-number"><?php echo number_format($total_messages); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Conversion Rate</h3>
                    <p class="stat-number"><?php echo number_format($conversion_rate, 1); ?>%</p>
                </div>
                <div class="stat-card">
                    <h3>Satisfaction Rate</h3>
                    <p class="stat-number"><?php echo number_format($satisfaction_rate, 1); ?>%</p>
                </div>
            </div>
            
            <!-- Event Breakdown -->
            <div class="analytics-section">
                <h2>Event Breakdown</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_events as $event): ?>
                        <tr>
                            <td><?php echo esc_html($event->event_type); ?></td>
                            <td><?php echo number_format($event->count); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pain Points -->
            <div class="analytics-section">
                <h2>User Pain Points</h2>
                <p>Messages where users showed frustration or confusion:</p>
                <ul>
                    <?php foreach ($pain_points as $point): ?>
                    <li><?php echo esc_html($point); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Conversation Flow Chart (placeholder) -->
            <div class="analytics-section">
                <h2>Conversation Flow</h2>
                <canvas id="conversation-flow-chart"></canvas>
            </div>
        </div>
        
        <style>
        .analytics-header {
            margin: 20px 0;
        }
        .analytics-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
            color: #5850ec;
        }
        .analytics-section {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        </style>
        <?php
    }
    
    /**
     * Calculate conversion rate
     */
    private function get_conversion_rate($start_date) {
        global $wpdb;
        
        $total_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM {$this->table_name} WHERE timestamp >= %s",
            $start_date
        ));
        
        $converted_sessions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT session_id) FROM {$this->table_name} 
             WHERE event_type = 'contact_form_submitted' AND timestamp >= %s",
            $start_date
        ));
        
        return $total_sessions > 0 ? ($converted_sessions / $total_sessions) * 100 : 0;
    }
    
    /**
     * Calculate satisfaction rate
     */
    private function get_satisfaction_rate($start_date) {
        global $wpdb;
        
        $total_ratings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE event_type = 'satisfaction_rating' AND timestamp >= %s",
            $start_date
        ));
        
        $positive_ratings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE event_type = 'satisfaction_rating' 
             AND JSON_EXTRACT(event_data, '$.rating') >= 4 
             AND timestamp >= %s",
            $start_date
        ));
        
        return $total_ratings > 0 ? ($positive_ratings / $total_ratings) * 100 : 0;
    }
    
    /**
     * Identify pain points from conversations
     */
    private function get_pain_points($start_date) {
        global $wpdb;
        
        $pain_indicators = array('confused', 'don\'t understand', 'what?', 'frustrated', 'help', 'not working');
        $pain_points = array();
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT event_data FROM {$this->table_name} 
             WHERE event_type = 'message_sent' 
             AND timestamp >= %s",
            $start_date
        ));
        
        foreach ($messages as $message) {
            $data = json_decode($message->event_data, true);
            if (isset($data['message'])) {
                $msg_lower = strtolower($data['message']);
                foreach ($pain_indicators as $indicator) {
                    if (strpos($msg_lower, $indicator) !== false) {
                        $pain_points[] = substr($data['message'], 0, 100) . '...';
                        break;
                    }
                }
            }
        }
        
        return array_slice($pain_points, 0, 10); // Return top 10
    }
}

// Initialize analytics on plugin activation
register_activation_hook(__FILE__, function() {
    $analytics = new ChatbotAnalytics();
    $analytics->create_tables();
});