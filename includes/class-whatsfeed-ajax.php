<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WhatsFeed AJAX Handler
 * 
 * Handles AJAX requests for the WhatsFeed plugin
 */

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
            
            if (empty($access_token)) {
                wp_send_json_error(array('message' => 'Instagram access token not configured.'));
            }
            
            // Try to fetch a single post to test connection
            // Check if we have a username set
            $username = get_option('whatsfeed_instagram_username', '');
            
            // If username is empty, try the other option name
            if (empty($username)) {
                $username = get_option('whatsfeed_username', '');
                // If we found a username in the other option, update the primary option
                if (!empty($username)) {
                    update_option('whatsfeed_instagram_username', $username);
                    error_log('Updated whatsfeed_instagram_username from whatsfeed_username: ' . $username);
                }
            }
            
            if (!empty($username)) {
                // If we have a username, use the username-based method
                $result = WhatsFeed_Instagram::fetch_by_username($username, 1);
            } else {
                // Otherwise, use the token-based method
                $result = WhatsFeed_Instagram::fetch_feed_with_token(1);
            }
            
            if (is_wp_error($result)) {
                $error_code = $result->get_error_code();
                $error_message = $result->get_error_message();
                
                // Provide more helpful messages based on error code
                if ($error_code === 'invalid_token_format') {
                    wp_send_json_error(array(
                        'message' => $error_message,
                        'suggestion' => 'Try using the One-Click Token Generation feature to create a new token.'
                    ));
                } elseif ($error_code === 'corrupted_token') {
                    wp_send_json_error(array(
                        'message' => $error_message,
                        'suggestion' => 'Your token appears to be corrupted. Please regenerate it using the One-Click Token Generation feature.'
                    ));
                } elseif ($error_code === 'expired_token') {
                    wp_send_json_error(array(
                        'message' => $error_message,
                        'suggestion' => 'Your token has expired. Please reconnect your Instagram account or use the One-Click Token Generation feature.'
                    ));
                } else {
                    // Check if this is a decryption error
                if (strpos($error_message, 'Failed to decrypt') !== false || strpos($error_message, 'Code: 190') !== false) {
                    wp_send_json_error(array(
                        'message' => $error_message,
                        'suggestion' => 'This is a token decryption error. Please regenerate your token using the One-Click Token Generation feature or manually enter a valid token in the settings.'
                    ));
                } else {
                    wp_send_json_error(array('message' => $error_message));
                }
                }
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
            // WhatsFeed_TikTok is a static class, so we call the static method directly
            $result = WhatsFeed_TikTok::fetch_feed(1);
            
            if (is_wp_error($result)) {
                $error_message = $result->get_error_message();
                
                // Check if this is a decryption error
                if (strpos($error_message, 'Failed to decrypt') !== false || strpos($error_message, 'Code: 190') !== false) {
                    wp_send_json_error(array(
                        'message' => $error_message,
                        'suggestion' => 'This is a token decryption error. Please regenerate your token using the One-Click Token Generation feature or manually enter a valid token in the settings.'
                    ));
                } else {
                    wp_send_json_error(array('message' => $error_message));
                }
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