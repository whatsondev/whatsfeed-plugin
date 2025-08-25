<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WhatsFeed_TikTok {

    /**
     * Fetch TikTok feed data by username
     * 
     * @param string $username TikTok username
     * @param int $limit Number of posts to fetch
     * @return array Array of TikTok posts
     */
    public static function fetch_by_username( $username, $limit = 6 ) {
        // Store the username for use in the formatted data
        update_option('whatsfeed_tiktok_username', $username);
        
        // Use the regular fetch method as TikTok API doesn't support username-based fetching directly
        // We'll just use the authenticated user's feed and display the username from the parameter
        return self::fetch_feed($limit);
    }

    /**
     * Fetch TikTok feed data
     * 
     * @param int $limit Number of posts to fetch
     * @return array Array of TikTok posts
     */
    public static function fetch_feed( $limit = 6 ) {
        // Check cache first
        $cache_key = 'tiktok_feed_' . md5($limit);
        $cache = WhatsFeed_Cache::get($cache_key);
        if ( $cache ) return $cache;

        // Get credentials
        $access_token = get_option('whatsfeed_tiktok_access_token');
        $open_id = get_option('whatsfeed_tiktok_open_id');
        
        if ( empty($access_token) || empty($open_id) ) {
            // Return empty array instead of error when credentials are not configured
            // This allows the shortcode to fall back to Instagram if available
            return [];
        }
        
        // Check if token is valid, attempt to refresh if not
        static $token_refresh_attempted = false;
        if (!self::is_token_valid() && !$token_refresh_attempted) {
            $token_refresh_attempted = true;
            
            // Attempt to refresh the token
            if (self::refresh_token()) {
                // Get the new token
                $access_token = get_option('whatsfeed_tiktok_access_token');
                
                // Clear the cache
                self::clear_cache();
                
                // Log the refresh
                error_log('TikTok token refreshed automatically');
            } else {
                return self::error_response('Failed to decrypt TikTok token (Type: OAuthException, Code: 190). Your access token appears to be invalid or expired and could not be refreshed automatically. Please regenerate your token in the WhatsFeed settings.');
            }
        }

        // Fetch videos from TikTok API
        $url = "https://open.tiktokapis.com/v2/video/list/";
        $response = wp_remote_post( $url, [
            'headers' => [ 
                'Authorization' => "Bearer $access_token",
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'max_count' => intval($limit),
                'fields' => [
                    'id', 'title', 'cover_image_url', 'share_url', 'video_description', 
                    'duration', 'height', 'width', 'create_time'
                ]
            ])
        ]);

        if ( is_wp_error( $response ) ) {
            return self::error_response($response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body( $response );
        
        // Log the response for debugging
        error_log('TikTok API Response Code: ' . $response_code);
        error_log('TikTok API Response Body: ' . $response_body);
        
        if ( $response_code !== 200 ) {
            $body = json_decode( $response_body, true );
            
            // Check for specific OAuth errors
            if (isset($body['error']) && isset($body['error']['code'])) {
                $error_message = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown error';
                $error_type = isset($body['error']['type']) ? $body['error']['type'] : 'Unknown';
                $error_code = $body['error']['code'];
                
                // Handle specific error codes
                if ($error_code == 190) {
                    // This is a token decryption error
                    // Set a transient to trigger admin notice
                    set_transient('whatsfeed_token_decryption_error', 'tiktok', DAY_IN_SECONDS);
                    
                    return self::error_response("Error: Failed to decrypt (Type: {$error_type}, Code: {$error_code}). Your TikTok access token appears to be invalid or expired. Please regenerate your token in the settings.");
                }
                
                return self::error_response("TikTok API Error: {$error_message} (Type: {$error_type}, Code: {$error_code})");
            }
            
            return self::error_response('TikTok API returned error code: ' . $response_code);
        }

        $body = json_decode( $response_body, true );
        
        if ( empty($body['data']['videos']) ) {
            if ( isset($body['error']) ) {
                $error_message = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown error';
                $error_type = isset($body['error']['type']) ? $body['error']['type'] : 'Unknown';
                $error_code = isset($body['error']['code']) ? $body['error']['code'] : 0;
                
                // Handle specific error codes
                if ($error_code == 190) {
                    // This is a token decryption error
                    // Set a transient to trigger admin notice
                    set_transient('whatsfeed_token_decryption_error', 'tiktok', DAY_IN_SECONDS);
                    
                    return self::error_response("Error: Failed to decrypt (Type: {$error_type}, Code: {$error_code}). Your TikTok access token appears to be invalid or expired. Please regenerate your token in the settings.");
                }
                
                return self::error_response("TikTok API Error: {$error_message} (Type: {$error_type}, Code: {$error_code})");
            }
            return self::error_response('No TikTok videos found');
        }

        // Process and format the data
        $videos = $body['data']['videos'];
        $formatted_data = self::format_videos($videos);
        
        // Cache the results
        WhatsFeed_Cache::set($cache_key, $formatted_data, HOUR_IN_SECONDS);
        
        return $formatted_data;
    }
    
    /**
     * Format TikTok videos data for consistent display
     * 
     * @param array $videos Raw video data from TikTok API
     * @return array Formatted video data
     */
    private static function format_videos($videos) {
        $formatted = [];
        
        foreach ($videos as $video) {
            $formatted[] = [
                'id' => $video['id'],
                'media_type' => 'VIDEO',
                'media_url' => $video['cover_image_url'],
                'permalink' => $video['share_url'],
                'caption' => $video['video_description'] ?? '',
                'timestamp' => $video['create_time'] ?? '',
                'username' => get_option('whatsfeed_tiktok_username', ''),
                'thumbnail_url' => $video['cover_image_url'],
                'duration' => $video['duration'] ?? 0,
                'width' => $video['width'] ?? 0,
                'height' => $video['height'] ?? 0
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Generate error response in a consistent format
     * 
     * @param string $message Error message
     * @return array Error data
     */
    private static function error_response($message) {
        return [
            'error' => true,
            'message' => $message
        ];
    }
    
    /**
     * Clear TikTok cache
     * 
     * @return bool Success status
     */
    public static function clear_cache() {
        global $wpdb;
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_whatsfeed_tiktok_%' 
             OR option_name LIKE '_transient_timeout_whatsfeed_tiktok_%'"
        );
        
        return $deleted > 0;
    }
    
    /**
     * Check if the TikTok token is valid
     * 
     * @return bool True if token is valid, false otherwise
     */
    public static function is_token_valid() {
        $access_token = get_option('whatsfeed_tiktok_access_token');
        $open_id = get_option('whatsfeed_tiktok_open_id');
        
        if (empty($access_token) || empty($open_id)) {
            return false;
        }
        
        // Make a simple API call to check token validity
        $url = "https://open.tiktokapis.com/v2/user/info/";
        $response = wp_remote_post($url, [
            'headers' => [ 
                'Authorization' => "Bearer $access_token",
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'fields' => ['open_id', 'union_id', 'avatar_url']
            ])
        ]);
        
        if (is_wp_error($response)) {
            error_log('TikTok token validation error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // If we get a 200 response, the token is valid
        if ($response_code === 200) {
            // If this was a manually entered token that works, clear any error transients
            delete_transient('whatsfeed_token_decryption_error');
            return true;
        }
        
        // Check for token-related errors
        if (isset($body['error'])) {
            $error_code = isset($body['error']['code']) ? $body['error']['code'] : 0;
            $error_message = isset($body['error']['message']) ? $body['error']['message'] : '';
            
            // Log the error for debugging
            error_log('TikTok token validation error: ' . $error_code . ' - ' . $error_message);
            
            // 190 is the code for token decryption issues
            if ($error_code == 190 || strpos($error_message, 'decrypt') !== false) {
                // Set a transient to trigger admin notice
                set_transient('whatsfeed_token_decryption_error', 'tiktok', DAY_IN_SECONDS);
                return false;
            }
        }
        
        // Default to assuming the token is valid if we can't determine otherwise
        return true;
    }
    
    /**
     * Refresh the TikTok access token
     * 
     * @return bool True if token was refreshed successfully, false otherwise
     */
    public static function refresh_token() {
        $client_key = get_option('whatsfeed_tiktok_client_key');
        $client_secret = get_option('whatsfeed_tiktok_client_secret');
        $refresh_token = get_option('whatsfeed_tiktok_refresh_token');
        
        if (empty($client_key) || empty($client_secret) || empty($refresh_token)) {
            return false;
        }
        
        $url = 'https://open-api.tiktok.com/oauth/refresh_token/';
        
        $args = array(
            'body' => array(
                'client_key' => $client_key,
                'client_secret' => $client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token,
            ),
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            error_log('TikTok token refresh error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['data']['access_token']) && isset($body['data']['refresh_token'])) {
            update_option('whatsfeed_tiktok_access_token', $body['data']['access_token']);
            update_option('whatsfeed_tiktok_refresh_token', $body['data']['refresh_token']);
            
            // Set a transient to trigger admin notice
            set_transient('whatsfeed_tiktok_token_refreshed', true, 60 * 60 * 24);
            
            // Clear any decryption error transients since we now have a valid token
            delete_transient('whatsfeed_token_decryption_error');
            
            return true;
        }
        
        error_log('TikTok token refresh failed: Invalid response format');
        return false;
    }
}
