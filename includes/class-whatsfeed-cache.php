<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WhatsFeed_Cache {

    public static function get( $key ) {
        return get_transient( 'whatsfeed_' . $key );
    }

    public static function set( $key, $data, $expiration = 3600 ) {
        set_transient( 'whatsfeed_' . $key, $data, $expiration );
    }
}
