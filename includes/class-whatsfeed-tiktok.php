<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WhatsFeed_TikTok {

    public static function fetch_feed( $limit = 6 ) {
        $cache = WhatsFeed_Cache::get('tiktok');
        if ( $cache ) return $cache;

        $token = get_option('whatsfeed_tiktok_token');
        if ( ! $token ) return [];

        $url = "https://open.tiktokapis.com/v2/video/list/?max_count={$limit}";
        $response = wp_remote_get( $url, [
            'headers' => [ 'Authorization' => "Bearer $token" ]
        ]);

        if ( is_wp_error( $response ) ) return [];

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $data = isset($body['data']) ? $body['data'] : [];

        WhatsFeed_Cache::set('tiktok', $data);
        return $data;
    }
}
