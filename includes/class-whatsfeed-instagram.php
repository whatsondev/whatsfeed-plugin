<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WhatsFeed_Instagram {

    public static function fetch_feed( $limit = 6 ) {
        $cache = WhatsFeed_Cache::get('instagram');
        if ( $cache ) return $cache;

        $token = get_option('whatsfeed_instagram_token');
        if ( ! $token ) return [];

        $url = "https://graph.instagram.com/me/media?fields=id,caption,media_url,permalink&limit={$limit}&access_token={$token}";
        $response = wp_remote_get( $url );

        if ( is_wp_error( $response ) ) return [];

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $data = isset($body['data']) ? $body['data'] : [];

        WhatsFeed_Cache::set('instagram', $data);
        return $data;
    }
}
