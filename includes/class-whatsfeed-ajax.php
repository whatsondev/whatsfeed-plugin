<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WhatsFeed AJAX Handler Class
 * 
 * Handles AJAX requests for the WhatsFeed plugin
 */
class WhatsFeed_AJAX {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_whatsfeed_test_connection', array($this, 'test_connection'));
    }
    
    /**
     * Test connection to Instagram or TikTok API
     */
    public function test_connection() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'whatsfeed_test_connection')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
        }
        
        // Check if platform is specified
        if (!isset($_POST['platform'])) {
            wp_send_json_error(array('message' => 'Platform not specified.'));
        }
        
        $platform = sanitize_text_field($_POST['platform']);
        
        // Test Instagram connection
        if ($platform === 'instagram') {
            $access_token = get_option('whatsfeed_access_token');
            $user_id = get_option('whatsfeed_user_id');
            
            if (empty($access_token) || empty($user_id)) {
                wp_send_json_error(array('message' => 'Instagram credentials not configured.'));
            }
            
            // Try to fetch a single post to test connection
            $instagram = new WhatsFeed_Instagram();
            $result = $instagram->fetch_feed(1);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            } else {
                wp_send_json_success(array('message' => 'Instagram connection successful!'));
            }
        }
        // Test TikTok connection
        elseif ($platform === 'tiktok') {
            $access_token = get_option('whatsfeed_tiktok_access_token');
            $open_id = get_option('whatsfeed_tiktok_open_id');
            
            if (empty($access_token) || empty($open_id)) {
                wp_send_json_error(array('message' => 'TikTok credentials not configured.'));
            }
            
            // Try to fetch a single video to test connection
            $tiktok = new WhatsFeed_TikTok();
            $result = $tiktok->fetch_feed(1);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            } else {
                wp_send_json_success(array('message' => 'TikTok connection successful!'));
            }
        }
        // Invalid platform
        else {
            wp_send_json_error(array('message' => 'Invalid platform specified.'));
        }
    }
}

// Initialize AJAX handler
new WhatsFeed_AJAX();