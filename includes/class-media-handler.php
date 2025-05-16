<?php
/**
 * Media handling for OpenAI Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class ChatbotMediaHandler {
    private $allowed_types;
    private $max_file_size;
    
    public function __construct() {
        $this->allowed_types = array(
            'image' => array('jpg', 'jpeg', 'png', 'gif', 'webp'),
            'document' => array('pdf', 'doc', 'docx', 'txt'),
            'video' => array('mp4', 'webm', 'avi', 'mov')
        );
        
        $this->max_file_size = 10 * 1024 * 1024; // 10MB
        
        add_action('wp_ajax_upload_chatbot_media', array($this, 'handle_upload'));
        add_action('wp_ajax_nopriv_upload_chatbot_media', array($this, 'handle_upload'));
    }
    
    /**
     * Handle file upload
     */
    public function handle_upload() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'openai_chatbot_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        if (!isset($_FILES['file'])) {
            wp_send_json_error('No file uploaded');
            return;
        }
        
        $file = $_FILES['file'];
        
        // Check file size
        if ($file['size'] > $this->max_file_size) {
            wp_send_json_error('File too large. Maximum size is 10MB');
            return;
        }
        
        // Check file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $file_type = $this->get_file_type($file_ext);
        
        if (!$file_type) {
            wp_send_json_error('File type not allowed');
            return;
        }
        
        // Handle upload
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error($upload['error']);
            return;
        }
        
        // Generate thumbnail for images
        $thumbnail_url = null;
        if ($file_type === 'image') {
            $thumbnail_url = $this->generate_thumbnail($upload['file']);
        }
        
        // Return file info
        wp_send_json_success(array(
            'url' => $upload['url'],
            'type' => $file_type,
            'name' => $file['name'],
            'size' => $file['size'],
            'thumbnail' => $thumbnail_url
        ));
    }
    
    /**
     * Get file type category
     */
    private function get_file_type($extension) {
        foreach ($this->allowed_types as $type => $extensions) {
            if (in_array($extension, $extensions)) {
                return $type;
            }
        }
        return false;
    }
    
    /**
     * Generate thumbnail for images
     */
    private function generate_thumbnail($file_path) {
        $image_editor = wp_get_image_editor($file_path);
        
        if (is_wp_error($image_editor)) {
            return null;
        }
        
        $image_editor->resize(150, 150, true);
        $thumb_path = str_replace('.', '-thumb.', $file_path);
        $result = $image_editor->save($thumb_path);
        
        if (is_wp_error($result)) {
            return null;
        }
        
        return str_replace(
            wp_upload_dir()['basedir'],
            wp_upload_dir()['baseurl'],
            $thumb_path
        );
    }
}

// Initialize media handler
new ChatbotMediaHandler();