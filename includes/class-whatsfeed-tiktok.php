<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WhatsFeed_TikTok {

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
            return self::error_response('TikTok credentials not configured');
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
        if ( $response_code !== 200 ) {
            return self::error_response('TikTok API returned error code: ' . $response_code);
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        
        if ( empty($body['data']['videos']) ) {
            if ( isset($body['error']) ) {
                return self::error_response('TikTok API Error: ' . $body['error']['message']);
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
}
